<?php
/**
 * Dashboard storage and workflow state for quote requests.
 *
 * @package UninetCore
 */

namespace Uninet\Core\Quote;

if (! defined('ABSPATH')) {
    exit;
}

final class RequestPostType
{
    const POST_TYPE = 'uninet_quote';
    const STATUS_META = '_uninet_quote_status';
    const STATUS_UPDATED_META = '_uninet_quote_status_updated_at';
    const INTERNAL_NOTE_META = '_uninet_quote_internal_note';

    /**
     * Register dashboard hooks.
     */
    public function register()
    {
        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'save_status']);
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'columns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'render_column'], 10, 2);
        add_action('restrict_manage_posts', [$this, 'render_status_filter']);
        add_action('pre_get_posts', [$this, 'filter_admin_query']);
    }

    /**
     * Register private quote requests in the WordPress dashboard.
     */
    public function register_post_type()
    {
        register_post_type(
            self::POST_TYPE,
            [
                'labels' => [
                    'name' => __('Quote Requests', 'uninet-core'),
                    'singular_name' => __('Quote Request', 'uninet-core'),
                    'menu_name' => __('Quote Requests', 'uninet-core'),
                    'all_items' => __('All Quote Requests', 'uninet-core'),
                    'view_item' => __('View Quote Request', 'uninet-core'),
                    'search_items' => __('Search Quote Requests', 'uninet-core'),
                    'not_found' => __('No quote requests found.', 'uninet-core'),
                    'not_found_in_trash' => __('No quote requests found in Trash.', 'uninet-core'),
                ],
                'public' => false,
                'publicly_queryable' => false,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_rest' => false,
                'menu_icon' => 'dashicons-clipboard',
                'menu_position' => 25,
                'supports' => ['title'],
                'map_meta_cap' => true,
                'capability_type' => 'post',
            ]
        );
    }

    /**
     * Register staff-facing quote details and status panels.
     */
    public function register_meta_boxes()
    {
        add_meta_box(
            'uninet-quote-request-details',
            __('Quote Request Details', 'uninet-core'),
            [$this, 'render_details'],
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'uninet-quote-request-status',
            __('Quote Status', 'uninet-core'),
            [$this, 'render_status'],
            self::POST_TYPE,
            'side',
            'high'
        );
    }

    /**
     * Render contact, fulfilment, and line-item details for staff.
     *
     * @param \WP_Post $post Current quote request.
     */
    public function render_details($post)
    {
        $reference = (string) get_post_meta($post->ID, '_uninet_quote_reference', true);
        $fields = [
            'organisation_name' => __('Organisation name', 'uninet-core'),
            'organisation_type' => __('Organisation type', 'uninet-core'),
            'full_name' => __('Contact person', 'uninet-core'),
            'phone' => __('Phone', 'uninet-core'),
            'email' => __('Business email', 'uninet-core'),
            'kra_pin' => __('KRA PIN', 'uninet-core'),
            'business_address' => __('Business address', 'uninet-core'),
            'county' => __('County', 'uninet-core'),
            'town' => __('Town', 'uninet-core'),
            'fulfilment' => __('Fulfilment', 'uninet-core'),
            'required_by' => __('Required by', 'uninet-core'),
            'delivery_details' => __('Delivery or pickup details', 'uninet-core'),
            'notes' => __('Procurement notes', 'uninet-core'),
        ];

        echo '<p><strong>' . esc_html__('Reference:', 'uninet-core') . '</strong> ' . esc_html($reference) . '</p>';
        echo '<table class="widefat striped"><tbody>';

        foreach ($fields as $key => $label) {
            $value = (string) get_post_meta($post->ID, '_uninet_quote_' . $key, true);

            if ('' === $value) {
                $value = __('Not provided', 'uninet-core');
            }

            echo '<tr>';
            echo '<th scope="row">' . esc_html($label) . '</th>';
            echo '<td>' . nl2br(esc_html($value)) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        $items = get_post_meta($post->ID, '_uninet_quote_items', true);
        $items = is_array($items) ? $items : [];

        echo '<h3>' . esc_html__('Requested products', 'uninet-core') . '</h3>';
        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Product', 'uninet-core') . '</th>';
        echo '<th>' . esc_html__('SKU', 'uninet-core') . '</th>';
        echo '<th>' . esc_html__('Quantity', 'uninet-core') . '</th>';
        echo '<th>' . esc_html__('Pre-tax unit price', 'uninet-core') . '</th>';
        echo '<th>' . esc_html__('Pre-tax line total', 'uninet-core') . '</th>';
        echo '<th>' . esc_html__('Line notes', 'uninet-core') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($items as $item) {
            $product_id = isset($item['product_id']) ? absint($item['product_id']) : 0;
            $product_url = $product_id ? get_permalink($product_id) : '';
            $name = isset($item['name']) ? (string) $item['name'] : '';
            $unit_price = array_key_exists('unit_price', $item) ? $item['unit_price'] : null;
            $line_total = array_key_exists('line_total', $item) ? $item['line_total'] : null;

            echo '<tr>';
            echo '<td>';
            if ($product_url) {
                echo '<a href="' . esc_url($product_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($name) . '</a>';
            } else {
                echo esc_html($name);
            }
            echo '</td>';
            echo '<td>' . esc_html($item['sku'] ?? '') . '</td>';
            echo '<td>' . esc_html((string) ($item['quantity'] ?? 1)) . '</td>';
            echo '<td>' . $this->format_price($unit_price) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '<td>' . $this->format_price($line_total) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '<td>' . nl2br(esc_html((string) ($item['note'] ?? ''))) . '</td>';
            echo '</tr>';
        }

        if (! $items) {
            echo '<tr><td colspan="6">' . esc_html__('No valid line items were stored.', 'uninet-core') . '</td></tr>';
        }

        echo '</tbody></table>';

        $subtotal = get_post_meta($post->ID, '_uninet_quote_subtotal', true);
        $unpriced_count = absint(get_post_meta($post->ID, '_uninet_quote_unpriced_count', true));

        echo '<p><strong>' . esc_html__('Indicative pre-tax subtotal:', 'uninet-core') . '</strong> ';
        echo $this->format_price($subtotal); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</p>';

        if ($unpriced_count) {
            echo '<p>' . esc_html(
                sprintf(
                    /* translators: %d: number of unpriced products. */
                    _n('%d product requires staff pricing.', '%d products require staff pricing.', $unpriced_count, 'uninet-core'),
                    $unpriced_count
                )
            ) . '</p>';
        }

        echo '<p><em>' . esc_html__('This request does not reserve stock or create a WooCommerce order. Staff must confirm availability, tax, delivery, and the final quotation.', 'uninet-core') . '</em></p>';
    }

    /**
     * Render the editable internal workflow status.
     *
     * @param \WP_Post $post Current quote request.
     */
    public function render_status($post)
    {
        $current = (string) get_post_meta($post->ID, self::STATUS_META, true);
        $current = $current ?: 'new';
        $internal_note = (string) get_post_meta($post->ID, self::INTERNAL_NOTE_META, true);
        $updated_at = (string) get_post_meta($post->ID, self::STATUS_UPDATED_META, true);

        wp_nonce_field('uninet_quote_status_' . $post->ID, 'uninet_quote_status_nonce');

        echo '<label class="screen-reader-text" for="uninet-quote-status">' . esc_html__('Quote status', 'uninet-core') . '</label>';
        echo '<select id="uninet-quote-status" name="uninet_quote_status">';

        foreach (self::statuses() as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($current, $value, false) . '>' . esc_html($label) . '</option>';
        }

        echo '</select>';
        echo '<p class="description">' . esc_html__('Use this status for internal follow-up. It is not shown publicly.', 'uninet-core') . '</p>';
        echo '<hr>';
        echo '<label for="uninet-quote-internal-note"><strong>' . esc_html__('Internal follow-up note', 'uninet-core') . '</strong></label>';
        echo '<textarea id="uninet-quote-internal-note" name="uninet_quote_internal_note" rows="6" maxlength="2000" style="width:100%;margin-top:6px;">';
        echo esc_textarea($internal_note);
        echo '</textarea>';
        echo '<p class="description">' . esc_html__('Record the next action, availability check, or quotation follow-up. Customers cannot see this note.', 'uninet-core') . '</p>';

        if ($updated_at) {
            echo '<p class="description"><strong>' . esc_html__('Last workflow update:', 'uninet-core') . '</strong><br>';
            echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $updated_at));
            echo '</p>';
        }

        echo '<p class="description">' . esc_html__('Delete synthetic tests after validation. Move completed requests to Closed and review retained records periodically.', 'uninet-core') . '</p>';
    }

    /**
     * Save the internal quote workflow status.
     *
     * @param int $post_id Quote request ID.
     */
    public function save_status($post_id)
    {
        if (
            empty($_POST['uninet_quote_status_nonce'])
            || ! wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['uninet_quote_status_nonce'])),
                'uninet_quote_status_' . $post_id
            )
            || ! current_user_can('edit_post', $post_id)
            || wp_is_post_autosave($post_id)
            || wp_is_post_revision($post_id)
        ) {
            return;
        }

        $status = sanitize_key(wp_unslash($_POST['uninet_quote_status'] ?? ''));
        $internal_note = sanitize_textarea_field(wp_unslash($_POST['uninet_quote_internal_note'] ?? ''));
        $internal_note = $this->limit($internal_note, 2000);
        $changed = false;

        if (
            isset(self::statuses()[$status])
            && $status !== (string) get_post_meta($post_id, self::STATUS_META, true)
        ) {
            update_post_meta($post_id, self::STATUS_META, $status);
            $changed = true;
        }

        if ($internal_note !== (string) get_post_meta($post_id, self::INTERNAL_NOTE_META, true)) {
            update_post_meta($post_id, self::INTERNAL_NOTE_META, $internal_note);
            $changed = true;
        }

        if ($changed) {
            update_post_meta($post_id, self::STATUS_UPDATED_META, current_time('mysql'));
        }
    }

    /**
     * Add an internal workflow status filter above the quote request table.
     *
     * @param string $post_type Current admin post type.
     */
    public function render_status_filter($post_type)
    {
        if (self::POST_TYPE !== $post_type) {
            return;
        }

        $selected_status = sanitize_key(wp_unslash($_GET['uninet_quote_status'] ?? ''));

        echo '<label class="screen-reader-text" for="uninet-quote-status-filter">' . esc_html__('Filter quote requests by status', 'uninet-core') . '</label>';
        echo '<select id="uninet-quote-status-filter" name="uninet_quote_status">';
        echo '<option value="">' . esc_html__('All workflow statuses', 'uninet-core') . '</option>';

        foreach (self::statuses() as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($selected_status, $value, false) . '>' . esc_html($label) . '</option>';
        }

        echo '</select>';
    }

    /**
     * Apply the selected workflow status to the quote request admin query.
     *
     * @param \WP_Query $query Current admin query.
     */
    public function filter_admin_query($query)
    {
        if (
            ! is_admin()
            || ! $query->is_main_query()
            || self::POST_TYPE !== $query->get('post_type')
        ) {
            return;
        }

        $status = sanitize_key(wp_unslash($_GET['uninet_quote_status'] ?? ''));

        if (! isset(self::statuses()[$status])) {
            return;
        }

        $meta_query = (array) $query->get('meta_query');
        $meta_query[] = [
            'key' => self::STATUS_META,
            'value' => $status,
            'compare' => '=',
        ];
        $query->set('meta_query', $meta_query);
    }

    /**
     * Customize quote-request list columns.
     *
     * @param array $columns Existing columns.
     */
    public function columns($columns)
    {
        return [
            'cb' => $columns['cb'],
            'title' => __('Quote request', 'uninet-core'),
            'uninet_quote_contact' => __('Contact', 'uninet-core'),
            'uninet_quote_items' => __('Products', 'uninet-core'),
            'uninet_quote_subtotal' => __('Pre-tax subtotal', 'uninet-core'),
            'uninet_quote_status' => __('Status', 'uninet-core'),
            'date' => __('Received', 'uninet-core'),
        ];
    }

    /**
     * Render quote-request list column values.
     *
     * @param string $column  Column key.
     * @param int    $post_id Quote request ID.
     */
    public function render_column($column, $post_id)
    {
        if ('uninet_quote_contact' === $column) {
            $name = (string) get_post_meta($post_id, '_uninet_quote_full_name', true);
            $phone = (string) get_post_meta($post_id, '_uninet_quote_phone', true);
            $email = (string) get_post_meta($post_id, '_uninet_quote_email', true);

            echo esc_html($name);
            if ($phone) {
                echo '<br>' . esc_html($phone);
            }
            if ($email) {
                echo '<br><a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
            }
            return;
        }

        if ('uninet_quote_items' === $column) {
            $items = get_post_meta($post_id, '_uninet_quote_items', true);
            $items = is_array($items) ? $items : [];
            $quantity = array_sum(array_map(static function ($item) {
                return absint($item['quantity'] ?? 0);
            }, $items));

            echo esc_html(
                sprintf(
                    /* translators: 1: product line count, 2: total units. */
                    __('%1$d lines / %2$d units', 'uninet-core'),
                    count($items),
                    $quantity
                )
            );
            return;
        }

        if ('uninet_quote_subtotal' === $column) {
            echo $this->format_price(get_post_meta($post_id, '_uninet_quote_subtotal', true)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            return;
        }

        if ('uninet_quote_status' === $column) {
            $status = (string) get_post_meta($post_id, self::STATUS_META, true);
            echo esc_html(self::statuses()[$status] ?? self::statuses()['new']);

            $updated_at = (string) get_post_meta($post_id, self::STATUS_UPDATED_META, true);
            if ($updated_at) {
                echo '<br><span style="color:#646970;">';
                echo esc_html(mysql2date(get_option('date_format'), $updated_at));
                echo '</span>';
            }
        }
    }

    /**
     * Return supported internal workflow statuses.
     */
    public static function statuses()
    {
        return [
            'new' => __('New', 'uninet-core'),
            'reviewing' => __('Reviewing', 'uninet-core'),
            'quoted' => __('Quote prepared', 'uninet-core'),
            'closed' => __('Closed', 'uninet-core'),
        ];
    }

    /**
     * Build a stable public reference after the post ID exists.
     *
     * @param int $post_id Quote request ID.
     */
    public static function reference($post_id)
    {
        return 'UNQ-' . gmdate('Ym') . '-' . str_pad((string) absint($post_id), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Format a stored pre-tax amount for dashboard display.
     *
     * @param mixed $amount Stored numeric amount or null.
     */
    private function format_price($amount)
    {
        if (null === $amount || '' === $amount) {
            return esc_html__('Staff confirmation', 'uninet-core');
        }

        return wp_kses_post(wc_price((float) $amount, ['currency' => 'KES']));
    }

    /**
     * Limit a sanitized internal value without breaking multibyte text.
     */
    private function limit($value, $length)
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length);
        }

        return substr($value, 0, $length);
    }
}
