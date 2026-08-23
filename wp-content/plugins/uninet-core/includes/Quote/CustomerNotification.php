<?php
/**
 * Buyer-facing email confirmation for new quote requests.
 *
 * @package UninetCore
 */

namespace Uninet\Core\Quote;

if (! defined('ABSPATH')) {
    exit;
}

final class CustomerNotification
{
    const ACTION = 'uninet_send_customer_quote_email';
    const ACTION_GROUP = 'uninet-core';
    const STATUS_META = '_uninet_quote_customer_email_status';
    const QUEUED_AT_META = '_uninet_quote_customer_email_queued_at';
    const ATTEMPTED_AT_META = '_uninet_quote_customer_email_attempted_at';
    const PROCESSED_AT_META = '_uninet_quote_customer_email_processed_at';
    const ATTEMPTS_META = '_uninet_quote_customer_email_attempts';
    const ACTION_ID_META = '_uninet_quote_customer_email_action_id';

    /**
     * Register buyer-email hooks.
     */
    public function register()
    {
        add_action('uninet_quote_request_created', [$this, 'queue'], 10, 3);
        add_action(self::ACTION, [$this, 'process'], 10, 1);
    }

    /**
     * Queue the buyer email only after the durable quote record is complete.
     *
     * @param int   $post_id Quote request ID.
     * @param array $values  Validated buyer values.
     * @param array $items   Server-normalized quote lines.
     */
    public function queue($post_id, $values = [], $items = [])
    {
        unset($values, $items);

        $post_id = absint($post_id);

        if (! $post_id || RequestPostType::POST_TYPE !== get_post_type($post_id)) {
            return;
        }

        $status = (string) get_post_meta($post_id, self::STATUS_META, true);

        if (in_array($status, ['queued', 'processing', 'processed'], true)) {
            return;
        }

        try {
            $this->set_status($post_id, 'queued');
            update_post_meta($post_id, self::QUEUED_AT_META, current_time('mysql'));

            if (function_exists('as_enqueue_async_action')) {
                $action_id = as_enqueue_async_action(
                    self::ACTION,
                    [$post_id],
                    self::ACTION_GROUP,
                    true
                );

                if ($action_id) {
                    update_post_meta($post_id, self::ACTION_ID_META, absint($action_id));
                    return;
                }
            }

            // Never make a mail transport problem invalidate the saved quote.
            $this->send($post_id);
        } catch (\Throwable $exception) {
            $this->set_status($post_id, 'failed');
        }
    }

    /**
     * Process a queued buyer email.
     *
     * @param int $post_id Quote request ID.
     *
     * @throws \RuntimeException When WordPress cannot hand the message to the mail transport.
     */
    public function process($post_id)
    {
        $post_id = absint($post_id);

        if (! $this->send($post_id)) {
            throw new \RuntimeException(
                sprintf(
                    'Customer quote email processing failed for %s.',
                    RequestPostType::reference($post_id)
                )
            );
        }
    }

    /**
     * Pass the customer-safe quote summary to wp_mail().
     *
     * @param int $post_id Quote request ID.
     *
     * @return bool Whether WordPress accepted the message for mail processing.
     */
    private function send($post_id)
    {
        if (! $post_id || RequestPostType::POST_TYPE !== get_post_type($post_id)) {
            return false;
        }

        if ('processed' === (string) get_post_meta($post_id, self::STATUS_META, true)) {
            return true;
        }

        $attempts = absint(get_post_meta($post_id, self::ATTEMPTS_META, true)) + 1;
        update_post_meta($post_id, self::ATTEMPTS_META, $attempts);
        update_post_meta($post_id, self::ATTEMPTED_AT_META, current_time('mysql'));
        $this->set_status($post_id, 'processing');

        try {
            $recipient = sanitize_email($this->meta($post_id, 'email'));

            if (! is_email($recipient)) {
                $this->set_status($post_id, 'failed');
                return false;
            }

            $reference = $this->meta($post_id, 'reference') ?: RequestPostType::reference($post_id);
            $items = get_post_meta($post_id, '_uninet_quote_items', true);
            $items = is_array($items) ? $items : [];
            $subject = sprintf(
                'Your Uninet pre-tax quote summary - %s',
                $reference
            );
            $processed = wp_mail(
                $recipient,
                $subject,
                $this->message($post_id, $items),
                ['Content-Type: text/html; charset=UTF-8']
            );

            if ($processed) {
                $this->set_status($post_id, 'processed');
                update_post_meta($post_id, self::PROCESSED_AT_META, current_time('mysql'));
                return true;
            }
        } catch (\Throwable $exception) {
            // FluentSMTP records transport details; the quote remains safely stored.
        }

        $this->set_status($post_id, 'failed');
        return false;
    }

    /**
     * Build a customer-safe, mobile-friendly HTML quote summary.
     *
     * @param int   $post_id Quote request ID.
     * @param array $items   Stored quote lines.
     */
    private function message($post_id, $items)
    {
        $reference = $this->meta($post_id, 'reference') ?: RequestPostType::reference($post_id);
        $contact = $this->meta($post_id, 'full_name');
        $organisation = $this->meta($post_id, 'organisation_name');
        $subtotal = get_post_meta($post_id, '_uninet_quote_subtotal', true);
        $unpriced_count = absint(get_post_meta($post_id, '_uninet_quote_unpriced_count', true));
        $received_at = (string) get_post_field('post_date', $post_id);
        $received = $received_at
            ? mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $received_at)
            : current_time('mysql');
        $unit_count = array_sum(array_map(static function ($item) {
            return max(1, absint($item['quantity'] ?? 1));
        }, $items));
        $whatsapp_text = rawurlencode(
            sprintf(
                'Hello Uninet Technologies. I am following up on quote request %s for %s.',
                $reference,
                $organisation
            )
        );
        $whatsapp_url = 'https://wa.me/254770313200?text=' . $whatsapp_text;

        $html = '<!doctype html><html><body style="margin:0;padding:0;background:#f3f6f9;color:#122033;font-family:Arial,sans-serif;">';
        $html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f6f9;padding:24px 10px;"><tr><td align="center">';
        $html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:760px;background:#ffffff;border:1px solid #d9e1e8;">';
        $html .= '<tr><td style="padding:24px 28px;background:#173f76;color:#ffffff;">';
        $html .= '<p style="margin:0 0 6px;font-size:13px;font-weight:700;text-transform:uppercase;">Uninet Technologies</p>';
        $html .= '<h1 style="margin:0;font-size:24px;line-height:1.3;">Your pre-tax quote summary</h1>';
        $html .= '<p style="margin:8px 0 0;font-size:14px;">' . esc_html($reference) . ' &middot; ' . esc_html($received) . '</p>';
        $html .= '</td></tr>';

        $html .= '<tr><td style="padding:24px 28px 8px;">';
        $html .= '<p style="margin:0 0 12px;font-size:16px;line-height:1.6;">Hello ' . esc_html($contact) . ',</p>';
        $html .= '<p style="margin:0;color:#445366;font-size:14px;line-height:1.7;">We received the quote request for <strong>' . esc_html($organisation) . '</strong>. This email confirms the product list and indicative pre-tax pricing submitted for our team to review.</p>';
        $html .= '</td></tr>';

        $html .= '<tr><td style="padding:16px 28px 8px;">';
        $html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">';
        $html .= $this->detail_row(__('Product lines', 'uninet-core'), (string) count($items));
        $html .= $this->detail_row(__('Total units', 'uninet-core'), (string) $unit_count);
        $html .= $this->detail_row(__('Indicative pre-tax subtotal', 'uninet-core'), $this->format_price($subtotal));

        if ($unpriced_count) {
            $html .= $this->detail_row(__('Products awaiting staff pricing', 'uninet-core'), (string) $unpriced_count);
        }

        $html .= '</table></td></tr>';
        $html .= '<tr><td style="padding:16px 28px 8px;">';
        $html .= '<h2 style="margin:0 0 14px;font-size:18px;">Requested products</h2>';
        $html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="8" style="border-collapse:collapse;border:1px solid #d9e1e8;font-size:13px;">';
        $html .= '<thead><tr style="background:#edf2f7;text-align:left;">';
        $html .= '<th style="border-bottom:1px solid #d9e1e8;">Product and quantity</th>';
        $html .= '<th style="width:34%;border-bottom:1px solid #d9e1e8;">Pre-tax total</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($items as $item) {
            $name = (string) ($item['name'] ?? __('Catalogue product', 'uninet-core'));
            $sku = (string) ($item['sku'] ?? '');
            $quantity = max(1, absint($item['quantity'] ?? 1));
            $unit_price = array_key_exists('unit_price', $item) ? $item['unit_price'] : null;
            $line_total = array_key_exists('line_total', $item) ? $item['line_total'] : null;
            $line_note = (string) ($item['note'] ?? '');
            $product_id = absint($item['product_id'] ?? 0);
            $product_url = $product_id ? get_permalink($product_id) : '';
            $product_name = esc_html($name);

            if ($product_url) {
                $product_name = '<a href="' . esc_url($product_url) . '" style="color:#173f76;font-weight:700;">' . $product_name . '</a>';
            }

            if ($sku) {
                $product_name .= '<br><span style="color:#5f6b78;">SKU: ' . esc_html($sku) . '</span>';
            }

            if ($line_note) {
                $product_name .= '<br><span style="color:#5f6b78;">Note: ' . nl2br(esc_html($line_note)) . '</span>';
            }

            $product_name .= '<br><span style="color:#5f6b78;">Quantity: ' . esc_html((string) $quantity) . ' &middot; Unit: ' . esc_html($this->format_price($unit_price)) . '</span>';

            $html .= '<tr>';
            $html .= '<td style="border-bottom:1px solid #e5eaf0;word-break:break-word;">' . $product_name . '</td>';
            $html .= '<td style="border-bottom:1px solid #e5eaf0;">' . esc_html($this->format_price($line_total)) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></td></tr>';
        $html .= '<tr><td style="padding:24px 28px 28px;">';
        $html .= '<div style="padding:16px;border-left:4px solid #2dbf64;background:#f2fbf5;color:#35455d;font-size:13px;line-height:1.7;">This is an automated request summary, not a final quotation, pro forma invoice, or stock reservation. Prices shown are indicative and exclude tax and delivery. Products awaiting staff pricing are not included in the subtotal. Our team will confirm availability, tax, delivery charges, and the final quotation before payment.</div>';
        $html .= '<p style="margin:20px 0 14px;color:#445366;font-size:14px;line-height:1.7;">Need to add context or discuss alternatives? Reply to this email, call us, or continue on WhatsApp using your quote reference.</p>';
        $html .= '<a href="tel:+254770313200" style="display:inline-block;margin:0 8px 8px 0;padding:12px 18px;background:#2dbf64;color:#0d253f;font-weight:700;text-decoration:none;">Call 0770 313 200</a>';
        $html .= '<a href="' . esc_url($whatsapp_url) . '" style="display:inline-block;margin:0 0 8px;padding:12px 18px;border:1px solid #173f76;color:#173f76;font-weight:700;text-decoration:none;">Continue on WhatsApp</a>';
        $html .= '</td></tr></table></td></tr></table></body></html>';

        return $html;
    }

    /**
     * Render one escaped summary row.
     */
    private function detail_row($label, $value)
    {
        return '<tr>'
            . '<th scope="row" align="left" valign="top" style="width:45%;padding:8px 12px 8px 0;border-bottom:1px solid #e5eaf0;color:#5f6b78;font-size:13px;">' . esc_html($label) . '</th>'
            . '<td valign="top" style="padding:8px 0;border-bottom:1px solid #e5eaf0;font-size:14px;line-height:1.5;">' . esc_html($value) . '</td>'
            . '</tr>';
    }

    /**
     * Read one quote field from post meta.
     */
    private function meta($post_id, $key)
    {
        return (string) get_post_meta($post_id, '_uninet_quote_' . $key, true);
    }

    /**
     * Format a stored pre-tax amount as plain email-safe text.
     */
    private function format_price($amount)
    {
        if (null === $amount || '' === $amount) {
            return __('Staff confirmation', 'uninet-core');
        }

        return html_entity_decode(
            wp_strip_all_tags(wc_price((float) $amount, ['currency' => 'KES'])),
            ENT_QUOTES,
            'UTF-8'
        );
    }

    /**
     * Save the buyer-email processing state.
     */
    private function set_status($post_id, $status)
    {
        update_post_meta($post_id, self::STATUS_META, sanitize_key($status));
    }
}
