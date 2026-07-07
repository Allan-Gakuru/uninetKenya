<?php
/**
 * Cart and checkout visibility.
 *
 * @package UninetCore
 */

namespace Uninet\Core\WooCommerce;

if (! defined('ABSPATH')) {
    exit;
}

final class CartCheckoutVisibility
{
    /**
     * Register hooks.
     */
    public function register()
    {
        add_action('init', [$this, 'prepare_hooks']);
    }

    /**
     * Prepare cart/checkout visibility changes.
     */
    public function prepare_hooks()
    {
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
        remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
        remove_action('storefront_before_footer', 'storefront_sticky_single_add_to_cart', 999);

        add_filter('woocommerce_cart_needs_payment', '__return_false');
        add_filter('woocommerce_widget_cart_is_hidden', '__return_true');
        add_filter('storefront_sticky_single_add_to_cart', '__return_false');
        add_action('wp_enqueue_scripts', [$this, 'hide_storefront_sticky_add_to_cart'], 20);
        add_action('template_redirect', [$this, 'redirect_cart_checkout_pages']);
    }

    /**
     * Hide Storefront sticky Add to Cart markup if a theme/plugin still prints it.
     */
    public function hide_storefront_sticky_add_to_cart()
    {
        wp_add_inline_style(
            'uninet-core',
            '.storefront-sticky-add-to-cart{display:none!important;}'
        );
    }

    /**
     * Keep public buyers out of cart and checkout pages in phase one.
     */
    public function redirect_cart_checkout_pages()
    {
        if ((is_cart() || is_checkout()) && ! is_wc_endpoint_url('order-received')) {
            wp_safe_redirect(wc_get_page_permalink('shop'));
            exit;
        }
    }
}
