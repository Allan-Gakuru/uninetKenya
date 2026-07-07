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
        remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5);
        remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
        add_action('woocommerce_after_shop_loop_item_title', [$this, 'render_spec_line'], 8);
        add_action('woocommerce_after_shop_loop_item_title', [$this, 'render_availability'], 12);
        add_action('woocommerce_after_shop_loop_item', [$this, 'render_details_link'], 10);
    }

    /**
     * Render a compact specification line from visible WooCommerce attributes.
     */
    public function render_spec_line()
    {
        global $product;

        if (! $product instanceof \WC_Product) {
            return;
        }

        $specs = $this->get_card_specs($product);

        if (empty($specs)) {
            return;
        }

        echo '<p class="uninet-product-card-specs">';

        if (isset($specs['summary'])) {
            echo '<span class="uninet-product-card-specs__summary">' . esc_html($specs['summary']) . '</span>';
        } else {
            foreach ($specs as $index => $spec) {
                echo '<span class="uninet-product-card-specs__item">';
                echo '<strong>' . esc_html($spec['label']) . ':</strong> ';
                echo '<span>' . esc_html($spec['value']) . '</span>';
                echo '</span>';

                if ($index < count($specs) - 1) {
                    echo '<span class="uninet-product-card-specs__separator" aria-hidden="true">|</span>';
                }
            }
        }

        echo '</p>';
    }

    /**
     * Render availability language for product cards.
     */
    public function render_availability()
    {
        global $product;

        if (! $product instanceof \WC_Product) {
            return;
        }

        $class = $product->is_in_stock() ? 'is-available' : 'is-unavailable';
        $label = $this->availability_label($product);

        echo '<p class="uninet-product-card-availability ' . esc_attr($class) . '">' . esc_html($label) . '</p>';
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

    /**
     * Get compact card specs.
     *
     * @param \WC_Product $product Product.
     */
    private function get_card_specs(\WC_Product $product)
    {
        $parts = [];

        foreach ($product->get_attributes() as $attribute) {
            if (! $attribute->get_visible()) {
                continue;
            }

            $label = wc_attribute_label($attribute->get_name());
            $values = $this->attribute_values($product, $attribute);

            if (empty($values)) {
                continue;
            }

            $parts[] = [
                'label' => $label,
                'value' => implode(', ', array_slice($values, 0, 2)),
            ];

            if (3 === count($parts)) {
                break;
            }
        }

        if (! empty($parts)) {
            return $parts;
        }

        $summary = wp_strip_all_tags($product->get_short_description());

        return $summary ? ['summary' => wp_trim_words($summary, 12, '...')] : [];
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

    /**
     * Get buyer-facing availability label.
     *
     * @param \WC_Product $product Product.
     */
    private function availability_label(\WC_Product $product)
    {
        if (! $product->is_in_stock()) {
            return __('Currently unavailable', 'uninet-core');
        }

        if ($product->is_on_backorder()) {
            return __('Available on backorder', 'uninet-core');
        }

        return __('Available - staff will confirm', 'uninet-core');
    }
}
