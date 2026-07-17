<?php
/**
 * Dashboard storage for contact enquiries.
 *
 * @package UninetCore
 */

namespace Uninet\Core\Contact;

if (! defined('ABSPATH')) {
    exit;
}

final class InquiryPostType
{
    const POST_TYPE = 'uninet_inquiry';

    /**
     * Register hooks.
     */
    public function register()
    {
        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes', [$this, 'register_meta_box']);
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'columns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'render_column'], 10, 2);
    }

    /**
     * Register the private dashboard post type.
     */
    public function register_post_type()
    {
        register_post_type(
            self::POST_TYPE,
            [
                'labels' => [
                    'name' => __('Contact Messages', 'uninet-core'),
                    'singular_name' => __('Contact Message', 'uninet-core'),
                    'menu_name' => __('Contact Messages', 'uninet-core'),
                    'all_items' => __('All Messages', 'uninet-core'),
                    'view_item' => __('View Message', 'uninet-core'),
                    'search_items' => __('Search Messages', 'uninet-core'),
                    'not_found' => __('No contact messages found.', 'uninet-core'),
                    'not_found_in_trash' => __('No contact messages found in Trash.', 'uninet-core'),
                ],
                'public' => false,
                'publicly_queryable' => false,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_rest' => false,
                'menu_icon' => 'dashicons-email-alt',
                'menu_position' => 26,
                'supports' => ['title'],
                'map_meta_cap' => true,
                'capability_type' => 'post',
            ]
        );
    }

    /**
     * Register the read-only enquiry details panel.
     */
    public function register_meta_box()
    {
        add_meta_box(
            'uninet-contact-message-details',
            __('Message Details', 'uninet-core'),
            [$this, 'render_meta_box'],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    /**
     * Render enquiry details for staff.
     *
     * @param \WP_Post $post Current enquiry.
     */
    public function render_meta_box($post)
    {
        $fields = [
            'full_name' => __('Full name', 'uninet-core'),
            'phone' => __('Phone', 'uninet-core'),
            'email' => __('Email', 'uninet-core'),
            'business_name' => __('Business name', 'uninet-core'),
            'subject' => __('Subject', 'uninet-core'),
            'challenge' => __('Biggest challenge', 'uninet-core'),
            'already_tried' => __('What they have already tried', 'uninet-core'),
            'why_now' => __('Why now is the right time', 'uninet-core'),
            'message' => __('Additional message', 'uninet-core'),
        ];

        echo '<table class="widefat striped" style="border:0">';
        echo '<tbody>';

        foreach ($fields as $key => $label) {
            $value = (string) get_post_meta($post->ID, '_uninet_contact_' . $key, true);

            if ('' === $value) {
                $value = __('Not provided', 'uninet-core');
            }

            echo '<tr>';
            echo '<th scope="row" style="width:220px">' . esc_html($label) . '</th>';
            echo '<td>' . nl2br(esc_html($value)) . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Customize the dashboard list columns.
     *
     * @param array $columns Existing columns.
     */
    public function columns($columns)
    {
        return [
            'cb' => $columns['cb'],
            'title' => __('Enquiry', 'uninet-core'),
            'uninet_contact_details' => __('Contact', 'uninet-core'),
            'uninet_contact_challenge' => __('Biggest challenge', 'uninet-core'),
            'date' => __('Received', 'uninet-core'),
        ];
    }

    /**
     * Render a custom dashboard list column.
     *
     * @param string $column  Column key.
     * @param int    $post_id Enquiry ID.
     */
    public function render_column($column, $post_id)
    {
        if ('uninet_contact_details' === $column) {
            $phone = get_post_meta($post_id, '_uninet_contact_phone', true);
            $email = get_post_meta($post_id, '_uninet_contact_email', true);

            echo esc_html($phone);

            if ($email) {
                echo '<br><a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
            }

            return;
        }

        if ('uninet_contact_challenge' === $column) {
            $challenge = get_post_meta($post_id, '_uninet_contact_challenge', true);
            echo esc_html(wp_trim_words($challenge, 18));
        }
    }
}
