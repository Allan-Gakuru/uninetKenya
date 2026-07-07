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
        add_action('woocommerce_single_product_summary', [$this, 'render_price_note'], 11);
        add_action('woocommerce_single_product_summary', [$this, 'render_call_to_order_cta'], 31);
    }

    /**
     * Render the pre-tax price note on product pages.
     */
    public function render_price_note()
    {
        if (! is_product()) {
            return;
        }

        echo '<p class="uninet-product-price-note">';
        echo esc_html__('Price shown is pre-tax. Staff will confirm final tax and invoice totals before payment.', 'uninet-core');
        echo '</p>';
    }

    /**
     * Render the product page Call to Order CTA.
     */
    public function render_call_to_order_cta()
    {
        global $product;

        if (! is_product() || ! $product instanceof \WC_Product) {
            return;
        }

        echo '<div class="uninet-product-callout">';
        echo '<button type="button" class="button uninet-call-to-order-button" data-uninet-call-open data-product-id="' . esc_attr($product->get_id()) . '" data-product-name="' . esc_attr($product->get_name()) . '">';
        echo esc_html__('Call to Order', 'uninet-core');
        echo '</button>';
        echo '<p class="uninet-product-callout__note">';
        echo esc_html__('Submit your details and our team will confirm availability, delivery, and invoice totals before payment.', 'uninet-core');
        echo '</p>';
        echo '</div>';
    }
}
