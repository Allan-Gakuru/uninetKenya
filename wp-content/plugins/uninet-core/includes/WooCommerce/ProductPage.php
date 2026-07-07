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
        add_filter('woocommerce_product_tabs', [$this, 'customize_product_tabs'], 30);
    }

    /**
     * Keep the product page tabs focused on useful B2B content.
     *
     * @param array $tabs Product tabs.
     */
    public function customize_product_tabs($tabs)
    {
        unset($tabs['additional_information'], $tabs['reviews']);

        if (isset($tabs['description'])) {
            $tabs['description']['title'] = __('Product overview', 'uninet-core');
            $tabs['description']['priority'] = 10;
        }

        return $tabs;
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
        echo '<span>' . esc_html__('Pre-tax price. Final e-TIMS invoice totals are confirmed by staff.', 'uninet-core') . '</span>';
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

        $availability = $this->availability_summary($product);

        echo '<section class="uninet-product-callout" aria-label="' . esc_attr__('Business order request', 'uninet-core') . '">';
        echo '<div class="uninet-product-callout__body">';
        echo '<h2 class="uninet-product-callout__title">' . esc_html__('Business order request', 'uninet-core') . '</h2>';
        echo '<p class="uninet-product-callout__note">';
        echo esc_html__('Send your details and our team will confirm availability, delivery, tax, and invoice totals before payment.', 'uninet-core');
        echo '</p>';
        echo '<ul class="uninet-product-callout__checks" aria-label="' . esc_attr__('Order handling notes', 'uninet-core') . '">';
        echo '<li>' . esc_html__('One product per request', 'uninet-core') . '</li>';
        echo '<li>' . esc_html__('Pending order created for staff follow-up', 'uninet-core') . '</li>';
        echo '<li>' . esc_html__('Stock is confirmed manually', 'uninet-core') . '</li>';
        echo '</ul>';
        echo '</div>';
        echo '<div class="uninet-product-callout__action">';
        echo '<button type="button" class="button uninet-call-to-order-button" data-uninet-call-open data-product-id="' . esc_attr($product->get_id()) . '" data-product-name="' . esc_attr($product->get_name()) . '">';
        echo esc_html__('Call to Order', 'uninet-core');
        echo '</button>';
        echo '<p class="uninet-product-callout__availability">' . esc_html($availability) . '</p>';
        echo '</div>';
        echo '</section>';
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
        echo '<h2 id="' . esc_attr($heading_id) . '">' . esc_html__('Specifications and buying details', 'uninet-core') . '</h2>';
        echo '<p>' . esc_html__('Use these details to confirm fit for your office, then request a call so staff can verify stock, delivery, tax, and invoicing before payment.', 'uninet-core') . '</p>';
        echo '</div>';

        echo '<div class="uninet-product-details__grid">';
        echo '<article class="uninet-product-specs-panel">';
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

        echo '</article>';
        echo '<article class="uninet-procurement-panel">';
        echo '<h3>' . esc_html__('Procurement support', 'uninet-core') . '</h3>';
        echo '<div class="uninet-procurement-notes">';
        $this->render_procurement_note(__('Availability', 'uninet-core'), __('Stock and final availability are confirmed by staff before payment.', 'uninet-core'));
        $this->render_procurement_note(__('Warranty', 'uninet-core'), __('Six-month warranty on component failure. Physical and water damage are excluded.', 'uninet-core'));
        $this->render_procurement_note(__('Delivery', 'uninet-core'), __('Same-day delivery may be available within Nairobi and the metropolitan area after confirmation.', 'uninet-core'));
        $this->render_procurement_note(__('Invoice & tax', 'uninet-core'), __('Displayed price is pre-tax. Staff confirms final tax and e-TIMS invoice totals.', 'uninet-core'));
        $this->render_procurement_note(__('Payment', 'uninet-core'), __('M-Pesa, bank transfer, and approved payment options are supported. Cheques are not accepted.', 'uninet-core'));
        echo '</div>';
        echo '</article>';
        echo '</div>';

        echo '<div class="uninet-product-order-steps" aria-label="' . esc_attr__('How this order is handled', 'uninet-core') . '">';
        echo '<h3>' . esc_html__('How this order is handled', 'uninet-core') . '</h3>';
        echo '<ol>';
        echo '<li><strong>' . esc_html__('Submit the request', 'uninet-core') . '</strong><span>' . esc_html__('Your details create a pending WooCommerce order for staff follow-up.', 'uninet-core') . '</span></li>';
        echo '<li><strong>' . esc_html__('Staff confirms details', 'uninet-core') . '</strong><span>' . esc_html__('We confirm stock, delivery timing, final tax, and e-TIMS invoice information.', 'uninet-core') . '</span></li>';
        echo '<li><strong>' . esc_html__('Pay after confirmation', 'uninet-core') . '</strong><span>' . esc_html__('Use M-Pesa, bank transfer, or another approved option after totals are confirmed.', 'uninet-core') . '</span></li>';
        echo '</ol>';
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
     * Get a buyer-facing availability summary.
     *
     * @param \WC_Product $product Product.
     */
    private function availability_summary(\WC_Product $product)
    {
        if (! $product->is_in_stock()) {
            return __('Currently unavailable. Staff can advise on alternatives.', 'uninet-core');
        }

        if ($product->is_on_backorder()) {
            return __('Available on backorder. Staff will confirm timing.', 'uninet-core');
        }

        return __('Availability is confirmed by staff before payment.', 'uninet-core');
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
