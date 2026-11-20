<?php
/**
 * Buyer-facing email confirmation for Call to Order requests.
 *
 * @package UninetCore
 */

namespace Uninet\Core\CallToOrder;

use Uninet\Core\Admin\Settings;

if (! defined('ABSPATH')) {
    exit;
}

final class CustomerNotification
{
    const ACTION = 'uninet_send_customer_order_email';
    const ACTION_GROUP = 'uninet-core';
    const STATUS_META = '_uninet_order_customer_email_status';
    const QUEUED_AT_META = '_uninet_order_customer_email_queued_at';
    const ATTEMPTED_AT_META = '_uninet_order_customer_email_attempted_at';
    const PROCESSED_AT_META = '_uninet_order_customer_email_processed_at';
    const ATTEMPTS_META = '_uninet_order_customer_email_attempts';
    const ACTION_ID_META = '_uninet_order_customer_email_action_id';

    /** Register buyer-email hooks. */
    public function register()
    {
        add_action('uninet_call_to_order_created', [$this, 'queue'], 10, 2);
        add_action(self::ACTION, [$this, 'process'], 10, 1);
    }

    /**
     * Queue the buyer email after the WooCommerce order is durably saved.
     *
     * @param int   $order_id WooCommerce order ID.
     * @param array $data     Validated buyer data.
     */
    public function queue($order_id, $data = [])
    {
        unset($data);
        $order = $this->get_order($order_id);

        if (! $order) {
            return;
        }

        $status = (string) $order->get_meta(self::STATUS_META, true);

        if (in_array($status, ['queued', 'processing', 'processed'], true)) {
            return;
        }

        try {
            $order->update_meta_data(self::STATUS_META, 'queued');
            $order->update_meta_data(self::QUEUED_AT_META, current_time('mysql'));
            $order->save_meta_data();

            if (function_exists('as_enqueue_async_action')) {
                $action_id = as_enqueue_async_action(
                    self::ACTION,
                    [$order->get_id()],
                    self::ACTION_GROUP,
                    true
                );

                if ($action_id) {
                    $order->update_meta_data(self::ACTION_ID_META, absint($action_id));
                    $order->save_meta_data();
                    return;
                }
            }

            // A mail transport problem must never invalidate the saved order request.
            $this->send($order->get_id());
        } catch (\Throwable $exception) {
            $order->update_meta_data(self::STATUS_META, 'failed');
            $order->save_meta_data();
        }
    }

    /**
     * Process a queued buyer email.
     *
     * @param int $order_id WooCommerce order ID.
     *
     * @throws \RuntimeException When WordPress cannot hand the message to the mail transport.
     */
    public function process($order_id)
    {
        if (! $this->send($order_id)) {
            throw new \RuntimeException(
                sprintf('Customer order email processing failed for order %d.', absint($order_id))
            );
        }
    }

    /**
     * Pass the customer-safe order summary to wp_mail().
     *
     * @param int $order_id WooCommerce order ID.
     * @return bool Whether WordPress accepted the message for mail processing.
     */
    private function send($order_id)
    {
        $order = $this->get_order($order_id);

        if (! $order) {
            return false;
        }

        if ('processed' === (string) $order->get_meta(self::STATUS_META, true)) {
            return true;
        }

        $attempts = absint($order->get_meta(self::ATTEMPTS_META, true)) + 1;
        $order->update_meta_data(self::ATTEMPTS_META, $attempts);
        $order->update_meta_data(self::ATTEMPTED_AT_META, current_time('mysql'));
        $order->update_meta_data(self::STATUS_META, 'processing');
        $order->save_meta_data();

        try {
            $recipient = sanitize_email($order->get_billing_email());

            if (! is_email($recipient)) {
                $order->update_meta_data(self::STATUS_META, 'failed');
                $order->save_meta_data();
                return false;
            }

            $subject = sprintf(
                'Your Uninet pre-tax order request - #%s',
                $order->get_order_number()
            );
            $processed = wp_mail(
                $recipient,
                $subject,
                $this->message($order),
                ['Content-Type: text/html; charset=UTF-8']
            );

            if ($processed) {
                $order->update_meta_data(self::STATUS_META, 'processed');
                $order->update_meta_data(self::PROCESSED_AT_META, current_time('mysql'));
                $order->save_meta_data();
                return true;
            }
        } catch (\Throwable $exception) {
            // FluentSMTP records transport details; the pending order remains safely stored.
        }

        $order->update_meta_data(self::STATUS_META, 'failed');
        $order->save_meta_data();
        return false;
    }

    /**
     * Build a customer-safe, mobile-friendly HTML order summary.
     *
     * @param \WC_Order $order WooCommerce order.
     */
    private function message(\WC_Order $order)
    {
        $items = $order->get_items('line_item');
        $contact = (string) $order->get_meta(Metadata::FULL_NAME, true);
        $contact = $contact ?: $order->get_formatted_billing_full_name();
        $business = (string) $order->get_meta(Metadata::BUSINESS_NAME, true);
        $phone = (string) $order->get_meta(Metadata::PHONE, true);
        $county = (string) $order->get_meta(Metadata::COUNTY, true);
        $town = (string) $order->get_meta(Metadata::TOWN, true);
        $location = implode(', ', array_filter([$town, $county]));
        $sales_phone = Settings::get('sales_phone');
        $reference = '#' . $order->get_order_number();
        $unit_count = 0;
        $pre_tax_total = null;
        $first_product_name = '';

        foreach ($items as $item) {
            $quantity = max(1, absint($item->get_quantity()));
            $prices = $this->item_prices($item);
            $unit_count += $quantity;

            if (null !== $prices['line_total']) {
                $pre_tax_total = (null === $pre_tax_total ? 0.0 : $pre_tax_total) + $prices['line_total'];
            }

            if ('' === $first_product_name) {
                $first_product_name = $item->get_name();
            }
        }

        $first_product_name = $first_product_name ?: __('requested technology product', 'uninet-core');
        $created = $order->get_date_created();
        $received = $created
            ? $created->date_i18n(get_option('date_format') . ' ' . get_option('time_format'))
            : current_time('mysql');
        $whatsapp_text = rawurlencode(
            sprintf(
                'Hello Uninet Technologies. I am following up on order request %s for %s.',
                $reference,
                $first_product_name
            )
        );
        $whatsapp_url = 'https://wa.me/254770313200?text=' . $whatsapp_text;
        $tel_url = Settings::sales_tel_url($sales_phone);

        $html = '<!doctype html><html><body style="margin:0;padding:0;background:#f3f6f9;color:#122033;font-family:Arial,sans-serif;">';
        $html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f6f9;padding:24px 10px;"><tr><td align="center">';
        $html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:760px;background:#ffffff;border:1px solid #d9e1e8;">';
        $html .= '<tr><td style="padding:24px 28px;background:#173f76;color:#ffffff;">';
        $html .= '<p style="margin:0 0 6px;font-size:13px;font-weight:700;text-transform:uppercase;">Uninet Technologies</p>';
        $html .= '<h1 style="margin:0;font-size:24px;line-height:1.3;">Your pre-tax order request</h1>';
        $html .= '<p style="margin:8px 0 0;font-size:14px;">' . esc_html($reference) . ' &middot; ' . esc_html($received) . '</p>';
        $html .= '</td></tr>';

        $html .= '<tr><td style="padding:24px 28px 8px;">';
        $html .= '<p style="margin:0 0 12px;font-size:16px;line-height:1.6;">Hello ' . esc_html($contact) . ',</p>';
        $html .= '<p style="margin:0;color:#445366;font-size:14px;line-height:1.7;">We received your Call to Order request. This email confirms the product, quantity, and indicative pre-tax pricing submitted for our team to review.</p>';
        $html .= '</td></tr>';

        $html .= '<tr><td style="padding:16px 28px 8px;">';
        $html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">';
        $html .= $this->detail_row(__('Order request reference', 'uninet-core'), $reference);
        $html .= $this->detail_row(__('Total units requested', 'uninet-core'), (string) $unit_count);
        $html .= $this->detail_row(__('Indicative pre-tax subtotal', 'uninet-core'), $this->format_price($pre_tax_total, $order->get_currency()));
        $html .= $this->detail_row(__('Phone number submitted', 'uninet-core'), $phone);

        if ($location) {
            $html .= $this->detail_row(__('Requested location', 'uninet-core'), $location);
        }

        if ($business) {
            $html .= $this->detail_row(__('Business name', 'uninet-core'), $business);
        }

        $html .= '</table></td></tr>';
        $html .= '<tr><td style="padding:16px 28px 8px;">';
        $html .= '<h2 style="margin:0 0 14px;font-size:18px;">Requested product</h2>';
        $html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="8" style="border-collapse:collapse;border:1px solid #d9e1e8;font-size:13px;">';
        $html .= '<thead><tr style="background:#edf2f7;text-align:left;">';
        $html .= '<th style="border-bottom:1px solid #d9e1e8;">Product and quantity</th>';
        $html .= '<th style="width:34%;border-bottom:1px solid #d9e1e8;">Pre-tax total</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($items as $item) {
            $product = $item->get_product();
            $product_id = absint($item->get_product_id());
            $product_url = $product_id ? get_permalink($product_id) : '';
            $product_name = esc_html($item->get_name());
            $sku = $product ? (string) $product->get_sku() : '';
            $quantity = max(1, absint($item->get_quantity()));
            $prices = $this->item_prices($item);

            if ($product_url) {
                $product_name = '<a href="' . esc_url($product_url) . '" style="color:#173f76;font-weight:700;">' . $product_name . '</a>';
            }

            if ($sku) {
                $product_name .= '<br><span style="color:#5f6b78;">SKU: ' . esc_html($sku) . '</span>';
            }

            $product_name .= '<br><span style="color:#5f6b78;">Quantity: ' . esc_html((string) $quantity) . ' &middot; Unit: ' . esc_html($this->format_price($prices['unit_price'], $order->get_currency())) . '</span>';
            $html .= '<tr>';
            $html .= '<td style="border-bottom:1px solid #e5eaf0;word-break:break-word;">' . $product_name . '</td>';
            $html .= '<td style="border-bottom:1px solid #e5eaf0;">' . esc_html($this->format_price($prices['line_total'], $order->get_currency())) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></td></tr>';
        $html .= '<tr><td style="padding:24px 28px 28px;">';
        $html .= '<div style="padding:16px;border-left:4px solid #2dbf64;background:#f2fbf5;color:#35455d;font-size:13px;line-height:1.7;">This is an automated order-request summary, not a final invoice, payment request, stock confirmation, or stock reservation. Prices shown are indicative and exclude tax and delivery. Our team will confirm availability, tax, delivery charges, and the final invoice total before payment.</div>';
        $html .= '<p style="margin:20px 0 14px;color:#445366;font-size:14px;line-height:1.7;">To finish confirmation, call our sales team or continue on WhatsApp using your order request reference.</p>';

        if ($tel_url) {
            $html .= '<a href="' . esc_url($tel_url) . '" style="display:inline-block;margin:0 8px 8px 0;padding:12px 18px;background:#2dbf64;color:#0d253f;font-weight:700;text-decoration:none;">Call ' . esc_html($sales_phone) . '</a>';
        }

        $html .= '<a href="' . esc_url($whatsapp_url) . '" style="display:inline-block;margin:0 0 8px;padding:12px 18px;border:1px solid #173f76;color:#173f76;font-weight:700;text-decoration:none;">Continue on WhatsApp</a>';
        $html .= '</td></tr></table></td></tr></table></body></html>';

        return $html;
    }

    /** Return pre-tax unit and line prices for an order item. */
    private function item_prices($item)
    {
        $product = $item->get_product();
        $quantity = max(1, absint($item->get_quantity()));
        $subtotal = (float) $item->get_subtotal();
        $has_price = $product && '' !== $product->get_price();

        if (! $has_price && $subtotal > 0) {
            $has_price = true;
        }

        return [
            'unit_price' => $has_price ? $subtotal / $quantity : null,
            'line_total' => $has_price ? $subtotal : null,
        ];
    }

    /** Render one escaped summary row. */
    private function detail_row($label, $value)
    {
        return '<tr>'
            . '<th scope="row" align="left" valign="top" style="width:45%;padding:8px 12px 8px 0;border-bottom:1px solid #e5eaf0;color:#5f6b78;font-size:13px;">' . esc_html($label) . '</th>'
            . '<td valign="top" style="padding:8px 0;border-bottom:1px solid #e5eaf0;font-size:14px;line-height:1.5;">' . esc_html($value) . '</td>'
            . '</tr>';
    }

    /** Format a pre-tax amount as plain email-safe text. */
    private function format_price($amount, $currency)
    {
        if (null === $amount || '' === $amount) {
            return __('Staff confirmation', 'uninet-core');
        }

        return html_entity_decode(
            wp_strip_all_tags(wc_price((float) $amount, ['currency' => $currency])),
            ENT_QUOTES,
            'UTF-8'
        );
    }

    /** Load an order created by the Call to Order workflow. */
    private function get_order($order_id)
    {
        $order = wc_get_order(absint($order_id));

        if (! $order || 'uninet_call_to_order' !== $order->get_created_via()) {
            return false;
        }

        return $order;
    }
}
