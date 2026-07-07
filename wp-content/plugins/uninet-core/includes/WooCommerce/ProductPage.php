<?php
/**
 * WooCommerce product detail page hooks.
 *
 * @package UninetCore
 */

namespace Uninet\Core\WooCommerce;

if (! defined('ABSPATH')) {
    exit;
}

final class ProductPage
{
    /**
     * Register hooks.
     */
    public function register()
    {
        add_action('woocommerce_single_product_summary', [$this, 'render_call_to_order_placeholder'], 31);
    }

    /**
     * Temporary placeholder for the future Call to Order CTA.
     */
    public function render_call_to_order_placeholder()
    {
        if (! is_product() || ! current_user_can('manage_options')) {
            return;
        }

        echo '<div class="uninet-product-action-placeholder">';
        echo esc_html__('Call to Order action will be added here.', 'uninet-core');
        echo '</div>';
    }
}
