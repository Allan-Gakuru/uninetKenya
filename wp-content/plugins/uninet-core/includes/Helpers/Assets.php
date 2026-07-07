<?php
/**
 * Frontend assets.
 *
 * @package UninetCore
 */

namespace Uninet\Core\Helpers;

use Uninet\Core\Admin\Settings;

if (! defined('ABSPATH')) {
    exit;
}

final class Assets
{
    /**
     * Register hooks.
     */
    public function register()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
    }

    /**
     * Enqueue plugin assets.
     */
    public function enqueue_frontend_assets()
    {
        wp_enqueue_style(
            'uninet-core',
            UNINET_CORE_URL . 'assets/css/uninet-core.css',
            [],
            $this->asset_version('assets/css/uninet-core.css')
        );

        wp_enqueue_script(
            'uninet-call-to-order',
            UNINET_CORE_URL . 'assets/js/call-to-order.js',
            [],
            $this->asset_version('assets/js/call-to-order.js'),
            true
        );

        wp_localize_script(
            'uninet-call-to-order',
            'uninetCore',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('uninet_call_to_order'),
                'salesPhone' => Settings::get('sales_phone'),
                'whatsappPhone' => Settings::get('whatsapp_phone'),
                'events' => [
                    'callOrderOpen' => 'uninet_call_order_open',
                    'callOrderSubmit' => 'uninet_call_order_submit',
                    'phoneClick' => 'uninet_phone_click',
                    'whatsappClick' => 'uninet_whatsapp_click',
                    'productView' => 'uninet_product_view',
                    'searchUsed' => 'uninet_search_used',
                ],
            ]
        );
    }

    /**
     * Get an asset version that changes when the file changes.
     *
     * @param string $relative_path Asset path relative to the plugin root.
     */
    private function asset_version($relative_path)
    {
        $path = UNINET_CORE_PATH . ltrim($relative_path, '/');

        if (file_exists($path)) {
            return (string) filemtime($path);
        }

        return UNINET_CORE_VERSION;
    }
}
