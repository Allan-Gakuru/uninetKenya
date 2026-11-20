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
    const SALES_PHONE = '0708 990 595';

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
            'sales_phone' => self::SALES_PHONE,
            'whatsapp_phone' => '',
            'business_hours' => '',
            'footer_contact' => '',
            'footer_location' => '',
            'quote_notification_email' => 'uninettechnologies@gmail.com',
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

        $settings = wp_parse_args($saved, self::defaults());
        $sales_phone = trim((string) $settings['sales_phone']);
        $digits = preg_replace('/\D+/', '', $sales_phone);

        if ('' === $digits || in_array($digits, ['0770313200', '254770313200', '00254770313200'], true)) {
            $settings['sales_phone'] = self::SALES_PHONE;
        }

        return $settings;
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
     * Return a dialable sales link, including the Kenya country code for local numbers.
     */
    public static function sales_tel_url($phone = null)
    {
        $number = preg_replace('/[^0-9+]/', '', $phone ?? self::get('sales_phone'));

        if (preg_match('/^0[0-9]{9}$/', $number)) {
            $number = '+254' . substr($number, 1);
        } elseif (preg_match('/^254[0-9]{9}$/', $number)) {
            $number = '+' . $number;
        } elseif (0 === strpos($number, '00')) {
            $number = '+' . substr($number, 2);
        }

        return $number ? 'tel:' . $number : '';
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

        add_settings_section(
            'uninet_core_quote_notifications',
            __('Quote Notifications', 'uninet-core'),
            function () {
                echo '<p>' . esc_html__('New Build a Quote requests are sent to this internal inbox after the dashboard record is saved.', 'uninet-core') . '</p>';
            },
            'uninet-core'
        );

        add_settings_field(
            'quote_notification_email',
            __('Notification recipient', 'uninet-core'),
            [$this, 'render_email_field'],
            'uninet-core',
            'uninet_core_quote_notifications',
            [
                'key' => 'quote_notification_email',
            ]
        );
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
            if ('quote_notification_email' === $key) {
                $email = sanitize_email($input[$key] ?? '');
                $output[$key] = is_email($email) ? $email : self::defaults()[$key];
                continue;
            }

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
     * Render an email field.
     *
     * @param array $args Field args.
     */
    public function render_email_field($args)
    {
        $settings = self::get_all();
        $key = $args['key'];
        $value = isset($settings[$key]) ? $settings[$key] : '';

        printf(
            '<input type="email" class="regular-text" id="%1$s" name="%2$s[%1$s]" value="%3$s" required />',
            esc_attr($key),
            esc_attr(self::OPTION_KEY),
            esc_attr($value)
        );
        echo '<p class="description">' . esc_html__('Delivery, server response, and resend controls remain available in FluentSMTP email logs.', 'uninet-core') . '</p>';
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
