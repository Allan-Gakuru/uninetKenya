<?php
/**
 * Internal email notifications for new quote requests.
 *
 * @package UninetCore
 */

namespace Uninet\Core\Quote;

use Uninet\Core\Admin\Settings;

if (! defined('ABSPATH')) {
    exit;
}

final class Notification
{
    const ACTION = 'uninet_send_quote_notification';
    const ACTION_GROUP = 'uninet-core';
    const STATUS_META = '_uninet_quote_notification_status';
    const QUEUED_AT_META = '_uninet_quote_notification_queued_at';
    const ATTEMPTED_AT_META = '_uninet_quote_notification_attempted_at';
    const PROCESSED_AT_META = '_uninet_quote_notification_processed_at';
    const ATTEMPTS_META = '_uninet_quote_notification_attempts';
    const ACTION_ID_META = '_uninet_quote_notification_action_id';

    /**
     * Register quote notification hooks.
     */
    public function register()
    {
        add_action('uninet_quote_request_created', [$this, 'queue'], 10, 3);
        add_action(self::ACTION, [$this, 'process'], 10, 1);
    }

    /**
     * Queue a notification after the complete dashboard record has been saved.
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

            // Keep the request successful even when the background queue is unavailable.
            $this->send($post_id);
        } catch (\Throwable $exception) {
            $this->set_status($post_id, 'failed');
        }
    }

    /**
     * Process a queued notification.
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
                    'Quote notification mail processing failed for %s.',
                    RequestPostType::reference($post_id)
                )
            );
        }
    }

    /**
     * Build and pass the internal notification to wp_mail().
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
            $recipient = sanitize_email(Settings::get('quote_notification_email'));

            if (! is_email($recipient)) {
                $recipient = 'uninettechnologies@gmail.com';
            }

            $reference = $this->meta($post_id, 'reference');
            $organisation = $this->meta($post_id, 'organisation_name');
            $items = get_post_meta($post_id, '_uninet_quote_items', true);
            $items = is_array($items) ? $items : [];
            $subject = sprintf(
                '[Uninet Quote] %1$s - %2$s - %3$d product lines',
                $reference ?: RequestPostType::reference($post_id),
                $this->limit($organisation ?: __('New business enquiry', 'uninet-core'), 80),
                count($items)
            );
            $headers = ['Content-Type: text/html; charset=UTF-8'];
            $buyer_email = sanitize_email($this->meta($post_id, 'email'));

            if (is_email($buyer_email)) {
                $buyer_name = str_replace(
                    ["\r", "\n"],
                    '',
                    wp_strip_all_tags($this->meta($post_id, 'full_name'))
                );
                $headers[] = sprintf('Reply-To: %1$s <%2$s>', $buyer_name, $buyer_email);
            }

            $processed = wp_mail(
                $recipient,
                $subject,
                $this->message($post_id, $items),
                $headers
            );

            if ($processed) {
                $this->set_status($post_id, 'processed');
                update_post_meta($post_id, self::PROCESSED_AT_META, current_time('mysql'));
                return true;
            }
        } catch (\Throwable $exception) {
            // FluentSMTP records transport details; the dashboard keeps the durable request.
        }

        $this->set_status($post_id, 'failed');
        return false;
    }

    /**
     * Build the staff email from durable quote metadata.
     *
     * @param int   $post_id Quote request ID.
     * @param array $items   Stored quote lines.
     */
    private function message($post_id, $items)
    {
        $reference = $this->meta($post_id, 'reference') ?: RequestPostType::reference($post_id);
        $organisation = $this->meta($post_id, 'organisation_name');
        $contact = $this->meta($post_id, 'full_name');
        $phone = $this->meta($post_id, 'phone');
        $email = $this->meta($post_id, 'email');
        $county = $this->meta($post_id, 'county');
        $town = $this->meta($post_id, 'town');
        $fulfilment = $this->meta($post_id, 'fulfilment');
        $required_by = $this->meta($post_id, 'required_by');
        $notes = $this->meta($post_id, 'notes');
        $subtotal = get_post_meta($post_id, '_uninet_quote_subtotal', true);
        $unpriced_count = absint(get_post_meta($post_id, '_uninet_quote_unpriced_count', true));
        $received_at = (string) get_post_field('post_date', $post_id);
        $received = $received_at
            ? mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $received_at)
            : current_time('mysql');
        $admin_url = admin_url('post.php?post=' . absint($post_id) . '&action=edit');
        $phone_url = preg_replace('/[^0-9+]/', '', $phone);
        $line_count = count($items);
        $unit_count = array_sum(array_map(static function ($item) {
            return max(1, absint($item['quantity'] ?? 1));
        }, $items));

        $html = '<!doctype html><html><body style="margin:0;padding:0;background:#f3f6f9;color:#122033;font-family:Arial,sans-serif;">';
        $html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f6f9;padding:24px 12px;"><tr><td align="center">';
        $html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:760px;background:#ffffff;border:1px solid #d9e1e8;">';
        $html .= '<tr><td style="padding:24px 28px;background:#173f76;color:#ffffff;">';
        $html .= '<p style="margin:0 0 6px;font-size:13px;font-weight:700;text-transform:uppercase;">Uninet Technologies</p>';
        $html .= '<h1 style="margin:0;font-size:24px;line-height:1.3;">New quote request</h1>';
        $html .= '<p style="margin:8px 0 0;font-size:14px;">' . esc_html($reference) . ' &middot; ' . esc_html($received) . '</p>';
        $html .= '</td></tr>';

        $html .= '<tr><td style="padding:24px 28px 8px;">';
        $html .= '<h2 style="margin:0 0 14px;font-size:18px;">Buyer contact</h2>';
        $html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">';
        $html .= $this->detail_row(__('Organisation', 'uninet-core'), $organisation);
        $html .= $this->detail_row(__('Contact person', 'uninet-core'), $contact);
        $html .= $this->detail_row(
            __('Buyer phone', 'uninet-core'),
            '<a href="tel:' . esc_attr($phone_url) . '" style="color:#0b6e3d;font-size:18px;font-weight:700;text-decoration:none;">' . esc_html($phone) . '</a>',
            true
        );
        $html .= $this->detail_row(
            __('Business email', 'uninet-core'),
            '<a href="mailto:' . esc_attr($email) . '" style="color:#173f76;">' . esc_html($email) . '</a>',
            true
        );
        $html .= '</table></td></tr>';

        $html .= '<tr><td style="padding:16px 28px 8px;">';
        $html .= '<h2 style="margin:0 0 14px;font-size:18px;">Request summary</h2>';
        $html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">';
        $html .= $this->detail_row(__('Product lines / units', 'uninet-core'), sprintf('%1$d / %2$d', $line_count, $unit_count));
        $html .= $this->detail_row(__('Indicative pre-tax subtotal', 'uninet-core'), $this->format_price($subtotal));

        if ($unpriced_count) {
            $html .= $this->detail_row(__('Products awaiting staff pricing', 'uninet-core'), (string) $unpriced_count);
        }

        $optional_fields = [
            __('Fulfilment', 'uninet-core') => $fulfilment,
            __('County', 'uninet-core') => $county,
            __('Town', 'uninet-core') => $town,
            __('Required by', 'uninet-core') => $required_by,
            __('Procurement notes', 'uninet-core') => $notes,
        ];

        foreach ($optional_fields as $label => $value) {
            if ('' !== $value) {
                $html .= $this->detail_row($label, nl2br(esc_html($value)), true);
            }
        }

        $html .= '</table></td></tr>';
        $html .= '<tr><td style="padding:16px 28px 8px;">';
        $html .= '<h2 style="margin:0 0 14px;font-size:18px;">Requested products</h2>';
        $html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="8" style="border-collapse:collapse;border:1px solid #d9e1e8;font-size:13px;">';
        $html .= '<thead><tr style="background:#edf2f7;text-align:left;">';
        $html .= '<th style="border-bottom:1px solid #d9e1e8;">Product</th><th style="border-bottom:1px solid #d9e1e8;">Qty</th><th style="border-bottom:1px solid #d9e1e8;">Pre-tax line total</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($items as $item) {
            $name = (string) ($item['name'] ?? __('Catalogue product', 'uninet-core'));
            $sku = (string) ($item['sku'] ?? '');
            $quantity = max(1, absint($item['quantity'] ?? 1));
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

            $html .= '<tr>';
            $html .= '<td style="border-bottom:1px solid #e5eaf0;">' . $product_name . '</td>';
            $html .= '<td style="border-bottom:1px solid #e5eaf0;">' . esc_html((string) $quantity) . '</td>';
            $html .= '<td style="border-bottom:1px solid #e5eaf0;">' . esc_html($this->format_price($line_total)) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></td></tr>';
        $html .= '<tr><td style="padding:24px 28px 28px;">';
        $html .= '<p style="margin:0 0 16px;color:#445366;font-size:13px;line-height:1.6;">Prices are indicative and pre-tax. Confirm availability, tax, delivery charges, and the final quotation before payment. KRA PIN, business address, and complete request details remain in the protected dashboard record.</p>';
        $html .= '<a href="' . esc_url($admin_url) . '" style="display:inline-block;padding:12px 18px;background:#2dbf64;color:#0d253f;font-weight:700;text-decoration:none;">Open quote request</a>';
        $html .= '</td></tr></table></td></tr></table></body></html>';

        return $html;
    }

    /**
     * Render one email detail row.
     *
     * @param string $label      Row label.
     * @param string $value      Row value.
     * @param bool   $allow_html Whether the value has already been escaped as safe HTML.
     */
    private function detail_row($label, $value, $allow_html = false)
    {
        $display_value = $allow_html ? $value : esc_html($value);

        return '<tr>'
            . '<th scope="row" align="left" valign="top" style="width:34%;padding:8px 12px 8px 0;border-bottom:1px solid #e5eaf0;color:#5f6b78;font-size:13px;">' . esc_html($label) . '</th>'
            . '<td valign="top" style="padding:8px 0;border-bottom:1px solid #e5eaf0;font-size:14px;line-height:1.5;">' . $display_value . '</td>'
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
     * Save the internal mail-processing state.
     */
    private function set_status($post_id, $status)
    {
        update_post_meta($post_id, self::STATUS_META, sanitize_key($status));
    }

    /**
     * Human-readable notification statuses for staff.
     */
    public static function status_labels()
    {
        return [
            'queued' => __('Queued for email processing', 'uninet-core'),
            'processing' => __('Passing to the email service', 'uninet-core'),
            'processed' => __('Accepted for email processing', 'uninet-core'),
            'failed' => __('Email processing failed', 'uninet-core'),
        ];
    }

    /**
     * Limit a subject value without breaking multibyte text.
     */
    private function limit($value, $length)
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length);
        }

        return substr($value, 0, $length);
    }
}
