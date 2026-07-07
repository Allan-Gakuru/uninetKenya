<?php
/**
 * WooCommerce product archive and category UX.
 *
 * @package UninetCore
 */

namespace Uninet\Core\WooCommerce;

if (! defined('ABSPATH')) {
    exit;
}

final class ProductArchives
{
    /**
     * Register hooks.
     */
    public function register()
    {
        add_action('woocommerce_archive_description', [$this, 'render_category_buying_note'], 20);
        add_action('woocommerce_before_shop_loop', [$this, 'render_archive_tools'], 5);
    }

    /**
     * Render search and top-level category navigation on product archives.
     */
    public function render_archive_tools()
    {
        if (! is_shop() && ! is_product_taxonomy()) {
            return;
        }

        echo '<div class="uninet-archive-tools">';
        echo '<div class="uninet-archive-search">';
        echo '<p class="uninet-archive-search__label">' . esc_html__('Find products faster', 'uninet-core') . '</p>';
        echo $this->render_search();
        echo '</div>';
        $this->render_category_nav();
        echo '</div>';
    }

    /**
     * Render category-specific buyer guidance.
     */
    public function render_category_buying_note()
    {
        if (! is_product_category()) {
            return;
        }

        $term = get_queried_object();

        if (! $term instanceof \WP_Term) {
            return;
        }

        echo '<div class="uninet-category-buying-note">';
        echo '<p>' . esc_html($this->category_note($term)) . '</p>';
        echo '</div>';
    }

    /**
     * Render the configured search experience.
     */
    private function render_search()
    {
        if (shortcode_exists('fibosearch')) {
            return do_shortcode('[fibosearch]');
        }

        return get_product_search_form(false);
    }

    /**
     * Render top-level product category navigation.
     */
    private function render_category_nav()
    {
        $terms = get_terms(
            [
                'taxonomy' => 'product_cat',
                'parent' => 0,
                'hide_empty' => false,
                'orderby' => 'menu_order',
                'order' => 'ASC',
            ]
        );

        if (is_wp_error($terms) || empty($terms)) {
            return;
        }

        $current_id = is_product_category() ? (int) get_queried_object_id() : 0;

        echo '<nav class="uninet-category-nav" aria-label="' . esc_attr__('Product categories', 'uninet-core') . '">';
        echo '<a class="uninet-category-nav__link ' . esc_attr(is_shop() ? 'is-active' : '') . '" href="' . esc_url(wc_get_page_permalink('shop')) . '">';
        echo esc_html__('All products', 'uninet-core');
        echo '</a>';

        foreach ($terms as $term) {
            $term_link = get_term_link($term);

            if (is_wp_error($term_link)) {
                continue;
            }

            $classes = ['uninet-category-nav__link'];

            if ($current_id === (int) $term->term_id) {
                $classes[] = 'is-active';
            }

            echo '<a class="' . esc_attr(implode(' ', $classes)) . '" href="' . esc_url($term_link) . '">';
            echo esc_html($term->name);
            echo '</a>';
        }

        echo '</nav>';
    }

    /**
     * Get category-specific procurement guidance.
     *
     * @param \WP_Term $term Current category term.
     */
    private function category_note(\WP_Term $term)
    {
        $name = strtolower($term->name . ' ' . $term->slug);

        if (false !== strpos($name, 'laptop')) {
            return __('Compare business-ready laptops by processor, memory, storage, display size, and warranty before placing a call-to-order request.', 'uninet-core');
        }

        if (false !== strpos($name, 'desktop')) {
            return __('Choose office desktops and workstations by workload, expandability, monitor pairing, and staff deployment needs.', 'uninet-core');
        }

        if (false !== strpos($name, 'monitor')) {
            return __('Match monitors by screen size, resolution, ports, desk setup, and multi-display productivity needs.', 'uninet-core');
        }

        if (false !== strpos($name, 'cctv') || false !== strpos($name, 'security')) {
            return __('Plan CCTV, surveillance, and access-control purchases around premises size, camera count, recording needs, and installation support.', 'uninet-core');
        }

        if (false !== strpos($name, 'network')) {
            return __('Select networking equipment by coverage, user count, port requirements, internet handoff, and future expansion.', 'uninet-core');
        }

        if (false !== strpos($name, 'printer') || false !== strpos($name, 'office')) {
            return __('Equip the office with printers, office equipment, and power backup options that fit daily workload and support expectations.', 'uninet-core');
        }

        if (false !== strpos($name, 'accessor') || false !== strpos($name, 'cable')) {
            return __('Find practical accessories, cables, storage, and input devices for business users, with staff confirmation before payment.', 'uninet-core');
        }

        return __('Browse business technology products, compare the key details, then submit a call-to-order request for staff confirmation.', 'uninet-core');
    }
}
