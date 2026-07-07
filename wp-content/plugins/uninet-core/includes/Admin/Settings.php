<?php
/**
 * Admin settings for Uninet Core.
 *
 * @package UninetCore
 */

namespace Uninet\Core\Admin;

if (! defined('ABSPATH')) {
    exit;
}

final class Settings
{
    const OPTION_KEY = 'uninet_core_settings';

    /**
     * Register settings hooks.
     */
    public function register()
    {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Default option values.
     */
    public static function defaults()
    {
        return [
            'sales_phone' => '',
            'whatsapp_phone' => '',
            'business_hours' => '',
            'footer_contact' => '',
            'footer_location' => '',
        ];
    }

    /**
     * Get all settings merged with defaults.
     */
    public static function get_all()
    {
        $saved = get_option(self::OPTION_KEY, []);

        if (! is_array($saved)) {
            $saved = [];
        }

        return wp_parse_args($saved, self::defaults());
    }

    /**
     * Get one setting.
     *
     * @param string $key Option key.
     */
    public static function get($key)
    {
        $settings = self::get_all();

        return isset($settings[$key]) ? $settings[$key] : '';
    }

    /**
     * Add admin settings page.
     */
    public function add_menu_page()
    {
        add_options_page(
            __('Uninet Core', 'uninet-core'),
            __('Uninet Core', 'uninet-core'),
            'manage_options',
            'uninet-core',
            [$this, 'render_page']
        );
    }

    /**
     * Register WordPress option and fields.
     */
    public function register_settings()
    {
        register_setting(
            'uninet_core_settings',
            self::OPTION_KEY,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize'],
                'default' => self::defaults(),
            ]
        );

        add_settings_section(
            'uninet_core_contact',
            __('Contact & Store Details', 'uninet-core'),
            function () {
                echo '<p>' . esc_html__('These values power Call to Order, footer contact information, and future tracking events.', 'uninet-core') . '</p>';
            },
            'uninet-core'
        );

        $fields = [
            'sales_phone' => __('Sales phone number', 'uninet-core'),
            'whatsapp_phone' => __('WhatsApp number', 'uninet-core'),
            'business_hours' => __('Business hours', 'uninet-core'),
            'footer_contact' => __('Footer contact line', 'uninet-core'),
            'footer_location' => __('Footer location / service area', 'uninet-core'),
        ];

        foreach ($fields as $key => $label) {
            add_settings_field(
                $key,
                $label,
                [$this, 'render_text_field'],
                'uninet-core',
                'uninet_core_contact',
                [
                    'key' => $key,
                ]
            );
        }
    }

    /**
     * Sanitize settings.
     *
     * @param array $input Submitted option values.
     */
    public function sanitize($input)
    {
        $output = self::defaults();

        if (! is_array($input)) {
            return $output;
        }

        foreach ($output as $key => $value) {
            $output[$key] = isset($input[$key]) ? sanitize_text_field($input[$key]) : '';
        }

        return $output;
    }

    /**
     * Render a text field.
     *
     * @param array $args Field args.
     */
    public function render_text_field($args)
    {
        $settings = self::get_all();
        $key = $args['key'];
        $value = isset($settings[$key]) ? $settings[$key] : '';

        printf(
            '<input type="text" class="regular-text" id="%1$s" name="%2$s[%1$s]" value="%3$s" />',
            esc_attr($key),
            esc_attr(self::OPTION_KEY),
            esc_attr($value)
        );
    }

    /**
     * Render settings page.
     */
    public function render_page()
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Uninet Core Settings', 'uninet-core') . '</h1>';
        echo '<form action="options.php" method="post">';
        settings_fields('uninet_core_settings');
        do_settings_sections('uninet-core');
        submit_button();
        echo '</form>';
        echo '</div>';
    }
}
