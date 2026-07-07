<?php
/**
 * WooCommerce product card hooks.
 *
 * @package UninetCore
 */

namespace Uninet\Core\WooCommerce;

if (! defined('ABSPATH')) {
    exit;
}

final class ProductCards
{
    /**
     * Register hooks.
     */
    public function register()
    {
        add_action('init', [$this, 'prepare_hooks']);
    }

    /**
     * Prepare product-card hook changes.
     */
    public function prepare_hooks()
    {
        remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
        add_action('woocommerce_after_shop_loop_item', [$this, 'render_details_link'], 10);
    }

    /**
     * Render the product-card action link.
     */
    public function render_details_link()
    {
        global $product;

        if (! $product instanceof \WC_Product) {
            return;
        }

        echo '<a class="button uninet-product-card-details" href="' . esc_url(get_permalink($product->get_id())) . '">';
        echo esc_html__('View Details', 'uninet-core');
        echo '</a>';
    }
}
