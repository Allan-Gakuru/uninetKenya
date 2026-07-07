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
        add_action('woocommerce_after_single_product_summary', [$this, 'render_business_product_details'], 8);
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

    /**
     * Render B2B product decision support before the default WooCommerce tabs.
     */
    public function render_business_product_details()
    {
        global $product;

        if (! is_product() || ! $product instanceof \WC_Product) {
            return;
        }

        $attributes = $this->visible_attributes($product);
        $heading_id = 'uninet-product-details-' . $product->get_id();

        echo '<section class="uninet-product-details" aria-labelledby="' . esc_attr($heading_id) . '">';
        echo '<div class="uninet-product-details__header">';
        echo '<h2 id="' . esc_attr($heading_id) . '">' . esc_html__('Product details for business buyers', 'uninet-core') . '</h2>';
        echo '<p>' . esc_html__('Review the key specifications, then submit a call-to-order request so our team can confirm fit, availability, delivery, and invoice totals.', 'uninet-core') . '</p>';
        echo '</div>';

        echo '<div class="uninet-product-details__grid">';
        echo '<div class="uninet-product-specs-panel">';
        echo '<h3>' . esc_html__('Key specifications', 'uninet-core') . '</h3>';

        if (! empty($attributes)) {
            echo '<dl class="uninet-product-specs">';

            foreach ($attributes as $attribute) {
                echo '<div class="uninet-product-specs__row">';
                echo '<dt>' . esc_html($attribute['label']) . '</dt>';
                echo '<dd>' . esc_html($attribute['value']) . '</dd>';
                echo '</div>';
            }

            echo '</dl>';
        } else {
            echo '<p class="uninet-product-specs-panel__empty">' . esc_html__('Detailed specifications will be confirmed by our team before payment.', 'uninet-core') . '</p>';
        }

        echo '</div>';
        echo '<div class="uninet-procurement-panel">';
        echo '<h3>' . esc_html__('Procurement notes', 'uninet-core') . '</h3>';
        echo '<div class="uninet-procurement-notes">';
        $this->render_procurement_note(__('Availability', 'uninet-core'), __('Stock and final availability are confirmed by staff before payment.', 'uninet-core'));
        $this->render_procurement_note(__('Warranty', 'uninet-core'), __('Six-month warranty on component failure. Physical and water damage are excluded.', 'uninet-core'));
        $this->render_procurement_note(__('Delivery', 'uninet-core'), __('Same-day delivery may be available within Nairobi and the metropolitan area after confirmation.', 'uninet-core'));
        $this->render_procurement_note(__('Invoice & tax', 'uninet-core'), __('Displayed price is pre-tax. Staff confirms final tax and e-TIMS invoice totals.', 'uninet-core'));
        $this->render_procurement_note(__('Payment', 'uninet-core'), __('M-Pesa, bank transfer, and approved payment options are supported. Cheques are not accepted.', 'uninet-core'));
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</section>';
    }

    /**
     * Render one procurement note.
     *
     * @param string $title Note title.
     * @param string $body Note body.
     */
    private function render_procurement_note($title, $body)
    {
        echo '<div class="uninet-procurement-note">';
        echo '<h4>' . esc_html($title) . '</h4>';
        echo '<p>' . esc_html($body) . '</p>';
        echo '</div>';
    }

    /**
     * Get visible product attributes for display.
     *
     * @param \WC_Product $product Product.
     */
    private function visible_attributes(\WC_Product $product)
    {
        $attributes = [];

        foreach ($product->get_attributes() as $attribute) {
            if (! $attribute->get_visible()) {
                continue;
            }

            $values = $this->attribute_values($product, $attribute);

            if (empty($values)) {
                continue;
            }

            $attributes[] = [
                'label' => wc_attribute_label($attribute->get_name()),
                'value' => implode(', ', $values),
            ];
        }

        return $attributes;
    }

    /**
     * Get display values for an attribute.
     *
     * @param \WC_Product           $product Product.
     * @param \WC_Product_Attribute $attribute Attribute.
     */
    private function attribute_values(\WC_Product $product, \WC_Product_Attribute $attribute)
    {
        if ($attribute->is_taxonomy()) {
            $terms = wc_get_product_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names']);

            return is_wp_error($terms) ? [] : $terms;
        }

        return array_map('wc_clean', $attribute->get_options());
    }
}
