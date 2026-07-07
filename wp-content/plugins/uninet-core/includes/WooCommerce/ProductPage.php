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
        add_action('wp_footer', [$this, 'render_sticky_order_bar']);
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

        echo '<section class="uninet-product-callout" aria-label="' . esc_attr__('Make an order', 'uninet-core') . '">';
        echo '<div class="uninet-product-callout__body">';
        echo '<h2 class="uninet-product-callout__title">' . esc_html__('Make an order', 'uninet-core') . '</h2>';
        echo '<p class="uninet-product-callout__note">';
        echo esc_html__('Click Call to Order, fill in your details, then view the phone number to call. Our team will confirm availability, tax, and invoice total before payment.', 'uninet-core');
        echo '</p>';
        echo '</div>';
        echo '<div class="uninet-product-callout__action">';
        echo '<button type="button" class="button uninet-call-to-order-button" data-uninet-call-open data-product-id="' . esc_attr($product->get_id()) . '" data-product-name="' . esc_attr($product->get_name()) . '">';
        echo $this->call_icon_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<span>' . esc_html__('Call to Order', 'uninet-core') . '</span>';
        echo '</button>';
        echo '</div>';
        echo '</section>';
    }

    /**
     * Render a compact sticky order bar once the main callout scrolls away.
     */
    public function render_sticky_order_bar()
    {
        $product = is_product() ? wc_get_product(get_queried_object_id()) : null;

        if (! $product instanceof \WC_Product) {
            return;
        }

        echo '<aside class="uninet-sticky-order" data-uninet-sticky-order aria-hidden="true" aria-label="' . esc_attr__('Make an order', 'uninet-core') . '">';
        echo '<div class="uninet-sticky-order__inner">';
        echo '<div class="uninet-sticky-order__copy">';
        echo '<p class="uninet-sticky-order__label">' . esc_html__('Make an order', 'uninet-core') . '</p>';
        echo '<p class="uninet-sticky-order__product">' . esc_html($product->get_name()) . '</p>';
        echo '<p class="uninet-sticky-order__note">' . esc_html__('Staff confirms availability, tax, and invoice total before payment.', 'uninet-core') . '</p>';
        echo '</div>';
        echo '<button type="button" class="button uninet-call-to-order-button uninet-sticky-order__button" data-uninet-call-open data-product-id="' . esc_attr($product->get_id()) . '" data-product-name="' . esc_attr($product->get_name()) . '" tabindex="-1">';
        echo $this->call_icon_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<span>' . esc_html__('Call to Order', 'uninet-core') . '</span>';
        echo '</button>';
        echo '</div>';
        echo '</aside>';
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
        echo '<li><strong>' . esc_html__('Submit the request', 'uninet-core') . '</strong><span>' . esc_html__('Your details create a pending order request for staff follow-up.', 'uninet-core') . '</span></li>';
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
     * Inline phone icon used in order buttons.
     */
    private function call_icon_svg()
    {
        return '<svg class="uninet-call-to-order-button__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5.5 4.75c0 8.01 5.74 13.75 13.75 13.75.69 0 1.25-.56 1.25-1.25v-2.1c0-.56-.38-1.05-.92-1.2l-3.18-.84c-.46-.12-.95.03-1.27.39l-.88 1c-1.78-.86-3.21-2.29-4.07-4.07l1-.88c.36-.32.51-.81.39-1.27l-.84-3.18c-.15-.54-.64-.92-1.2-.92h-2.1c-.69 0-1.25.56-1.25 1.25z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
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
