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
    const BRAND_TAXONOMY = 'product_brand';

    /**
     * Filter groups available in the current archive context.
     *
     * @var array|null
     */
    private $filter_groups = null;

    /**
     * Product IDs available before applying buyer filters.
     *
     * @var array|null
     */
    private $context_product_ids = null;

    /**
     * Current context price boundaries.
     *
     * @var array|null
     */
    private $price_bounds = null;

    /**
     * Register hooks.
     */
    public function register()
    {
        remove_action('woocommerce_no_products_found', 'wc_no_products_found', 10);
        add_action('wp', [$this, 'remove_default_archive_controls']);

        add_filter('woocommerce_product_query_tax_query', [$this, 'apply_archive_filters'], 20);
        add_filter('woocommerce_product_query_meta_query', [$this, 'apply_archive_price_filter'], 20);
        add_filter('woocommerce_catalog_orderby', [$this, 'catalog_orderby_labels']);
        add_filter('loop_shop_per_page', [$this, 'products_per_page'], 20);
        add_filter('loop_shop_columns', [$this, 'archive_columns'], 20);

        add_action('woocommerce_archive_description', [$this, 'render_category_buying_note'], 20);
        add_action('woocommerce_archive_description', [$this, 'render_category_navigation'], 30);
        add_action('woocommerce_before_shop_loop', [$this, 'render_archive_layout_open'], 8);
        add_action('woocommerce_before_shop_loop', [$this, 'render_archive_controls'], 15);
        add_action('woocommerce_after_shop_loop', [$this, 'render_archive_layout_close'], 35);
        add_action('woocommerce_no_products_found', [$this, 'render_empty_state'], 10);
    }

    /**
     * Remove Storefront's duplicate archive controls after the theme registers them.
     */
    public function remove_default_archive_controls()
    {
        if (! $this->is_supported_archive()) {
            return;
        }

        foreach ([10, 30] as $priority) {
            remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', $priority);
            remove_action('woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', $priority);
        }

        remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
        remove_action('woocommerce_after_shop_loop', 'woocommerce_result_count', 20);

        // Keep pagination below the products, but remove Storefront's duplicate above the grid.
        remove_action('woocommerce_before_shop_loop', 'storefront_woocommerce_pagination', 30);
        remove_action('woocommerce_before_shop_loop', 'woocommerce_pagination', 10);
    }

    /**
     * Keep archive pages dense enough for comparison without becoming endless.
     */
    public function products_per_page()
    {
        return 12;
    }

    /**
     * Keep the product grid at the approved desktop column count.
     */
    public function archive_columns()
    {
        return 3;
    }

    /**
     * Apply selected brand and attribute terms to the main product query.
     *
     * @param array $tax_query Existing product tax query.
     */
    public function apply_archive_filters($tax_query)
    {
        if (is_admin() || ! $this->is_supported_archive()) {
            return $tax_query;
        }

        foreach ($this->supported_filter_taxonomies() as $taxonomy => $label) {
            $selected = $this->selected_terms($taxonomy);

            if (empty($selected)) {
                continue;
            }

            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field' => 'slug',
                'terms' => $selected,
                'operator' => 'IN',
            ];
        }

        return $tax_query;
    }

    /**
     * Apply selected price boundaries to the main product query.
     *
     * @param array $meta_query Existing product meta query.
     */
    public function apply_archive_price_filter($meta_query)
    {
        if (is_admin() || ! $this->is_supported_archive()) {
            return $meta_query;
        }

        $minimum = $this->requested_price('minimum');
        $maximum = $this->requested_price('maximum');

        if (null === $minimum && null === $maximum) {
            return $meta_query;
        }

        if (null !== $minimum && null !== $maximum && $minimum > $maximum) {
            $temporary = $minimum;
            $minimum = $maximum;
            $maximum = $temporary;
        }

        if (null !== $minimum && null !== $maximum) {
            $meta_query[] = [
                'key' => '_price',
                'value' => [$minimum, $maximum],
                'compare' => 'BETWEEN',
                'type' => 'DECIMAL(20,4)',
            ];

            return $meta_query;
        }

        $meta_query[] = [
            'key' => '_price',
            'value' => null !== $minimum ? $minimum : $maximum,
            'compare' => null !== $minimum ? '>=' : '<=',
            'type' => 'DECIMAL(20,4)',
        ];

        return $meta_query;
    }

    /**
     * Use buyer-friendly sorting labels.
     *
     * @param array $options Existing ordering options.
     */
    public function catalog_orderby_labels($options)
    {
        $labels = [
            'menu_order' => __('Recommended', 'uninet-core'),
            'popularity' => __('Most popular', 'uninet-core'),
            'date' => __('Newest arrivals', 'uninet-core'),
            'price' => __('Price: low to high', 'uninet-core'),
            'price-desc' => __('Price: high to low', 'uninet-core'),
        ];

        foreach ($options as $key => $label) {
            if (isset($labels[$key])) {
                $options[$key] = $labels[$key];
            }
        }

        return $options;
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

        echo '<p class="uninet-category-buying-note">' . esc_html($this->category_note($term)) . '</p>';
    }

    /**
     * Render relevant category shortcuts on every product archive state.
     */
    public function render_category_navigation()
    {
        if (! $this->is_supported_archive()) {
            return;
        }

        $navigation = $this->category_navigation_items();

        if (empty($navigation['items'])) {
            return;
        }

        echo '<div class="uninet-archive-category-bar">';
        echo '<p class="uninet-archive-category-bar__label">' . esc_html($navigation['label']) . '</p>';
        echo '<nav class="uninet-category-nav" aria-label="' . esc_attr__('Product categories', 'uninet-core') . '">';

        foreach ($navigation['items'] as $item) {
            $classes = ['uninet-category-nav__link'];

            if (! empty($item['active'])) {
                $classes[] = 'is-active';
            }

            echo '<a class="' . esc_attr(implode(' ', $classes)) . '" href="' . esc_url($item['url']) . '">';
            echo esc_html($item['label']);
            echo '</a>';
        }

        echo '</nav>';
        echo '</div>';
    }

    /**
     * Open the filter-and-results archive layout.
     */
    public function render_archive_layout_open()
    {
        if (! $this->is_supported_archive()) {
            return;
        }

        $has_filters = $this->has_filter_options();
        $classes = ['uninet-archive-shell'];

        if (! $has_filters) {
            $classes[] = 'uninet-archive-shell--without-filters';
        }

        echo '<div class="' . esc_attr(implode(' ', $classes)) . '">';

        if ($has_filters) {
            $this->render_filter_panel();
        }

        echo '<section class="uninet-archive-results" aria-label="' . esc_attr__('Products', 'uninet-core') . '">';
    }

    /**
     * Render result count, filter access, active filters, and sorting.
     */
    public function render_archive_controls()
    {
        if (! $this->is_supported_archive()) {
            return;
        }

        $total = (int) wc_get_loop_prop('total');
        $has_filters = $this->has_filter_options();
        $active_count = $this->active_filter_count();

        echo '<div class="uninet-archive-controls">';
        echo '<div class="uninet-archive-controls__primary">';

        if ($has_filters) {
            echo '<button class="uninet-filter-toggle" type="button" data-uninet-filter-open aria-controls="uninet-product-filters" aria-expanded="false">';
            echo $this->filter_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '<span>' . esc_html__('Filters', 'uninet-core') . '</span>';

            if ($active_count > 0) {
                echo '<span class="uninet-filter-toggle__count" aria-label="' . esc_attr(sprintf(_n('%d active filter', '%d active filters', $active_count, 'uninet-core'), $active_count)) . '">';
                echo esc_html($active_count);
                echo '</span>';
            }

            echo '</button>';
        }

        echo '<p class="uninet-archive-result-count">' . esc_html($this->result_count_text()) . '</p>';
        echo '</div>';

        if ($total > 1) {
            echo '<div class="uninet-archive-sort">';
            echo '<span class="uninet-archive-sort__label">' . esc_html__('Sort by', 'uninet-core') . '</span>';
            woocommerce_catalog_ordering();
            echo '</div>';
        }

        echo '</div>';
        $this->render_active_filters();
    }

    /**
     * Close the filter-and-results archive layout after pagination.
     */
    public function render_archive_layout_close()
    {
        if (! $this->is_supported_archive()) {
            return;
        }

        echo '</section>';
        echo '</div>';
    }

    /**
     * Render a useful no-results state, including filter recovery when needed.
     */
    public function render_empty_state()
    {
        $has_active_filters = $this->has_active_filters();

        if ($has_active_filters) {
            $this->render_archive_layout_open();
            $this->render_archive_controls();
        }

        $title = __('No products are listed here yet', 'uninet-core');
        $message = __('Browse another category or contact our team for help sourcing the equipment your business needs.', 'uninet-core');
        $action_url = wc_get_page_permalink('shop');
        $action_label = __('Browse all products', 'uninet-core');

        if ($has_active_filters) {
            $title = __('No products match these filters', 'uninet-core');
            $message = __('Remove one or more filters to widen the results, then compare the available specifications again.', 'uninet-core');
            $action_url = $this->clear_filters_url();
            $action_label = __('Clear all filters', 'uninet-core');
        } elseif (is_tax(self::BRAND_TAXONOMY)) {
            $term = get_queried_object();

            if ($term instanceof \WP_Term) {
                $title = sprintf(__('No %s products are listed yet', 'uninet-core'), $term->name);
            }
        }

        echo '<section class="uninet-archive-empty" role="status">';
        echo '<span class="uninet-archive-empty__icon" aria-hidden="true">' . $this->search_icon() . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<h2>' . esc_html($title) . '</h2>';
        echo '<p>' . esc_html($message) . '</p>';
        echo '<a class="button" href="' . esc_url($action_url) . '">' . esc_html($action_label) . '</a>';
        $this->render_empty_category_links();
        echo '</section>';

        if ($has_active_filters) {
            $this->render_archive_layout_close();
        }
    }

    /**
     * Render the filter panel.
     */
    private function render_filter_panel()
    {
        $groups = $this->get_filter_groups();
        $bounds = $this->get_price_bounds();

        echo '<aside id="uninet-product-filters" class="uninet-archive-filters" aria-label="' . esc_attr__('Filter products', 'uninet-core') . '">';
        echo '<div class="uninet-archive-filters__header">';
        echo '<div>';
        echo '<p class="uninet-archive-filters__title">' . esc_html__('Filter products', 'uninet-core') . '</p>';
        echo '<p class="uninet-archive-filters__hint">' . esc_html__('Narrow by the specifications that matter to your team.', 'uninet-core') . '</p>';
        echo '</div>';
        echo '<button class="uninet-filter-close" type="button" data-uninet-filter-close aria-label="' . esc_attr__('Close filters', 'uninet-core') . '">';
        echo $this->close_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</button>';
        echo '</div>';

        echo '<form class="uninet-filter-form" action="' . esc_url($this->archive_url()) . '" method="get">';
        $this->render_preserved_query_inputs();

        foreach ($groups as $group) {
            echo '<fieldset class="uninet-filter-group">';
            echo '<legend>' . esc_html($group['label']) . '</legend>';

            foreach ($group['terms'] as $term_data) {
                $term = $term_data['term'];
                $selected = in_array($term->slug, $group['selected'], true);
                $input_id = 'uninet-filter-' . sanitize_html_class($group['key'] . '-' . $term->slug);

                echo '<label class="uninet-filter-option" for="' . esc_attr($input_id) . '">';
                echo '<input id="' . esc_attr($input_id) . '" type="checkbox" name="' . esc_attr($group['key']) . '[]" value="' . esc_attr($term->slug) . '" ' . checked($selected, true, false) . ' />';
                echo '<span class="uninet-filter-option__name">' . esc_html($term->name) . '</span>';
                echo '<span class="uninet-filter-option__count" aria-label="' . esc_attr(sprintf(_n('%d product', '%d products', $term_data['count'], 'uninet-core'), $term_data['count'])) . '">';
                echo esc_html($term_data['count']);
                echo '</span>';
                echo '</label>';
            }

            echo '</fieldset>';
        }

        if (null !== $bounds && $bounds['maximum'] > $bounds['minimum']) {
            $this->render_price_filter($bounds);
        }

        echo '<div class="uninet-filter-form__actions">';
        echo '<button class="button uninet-filter-apply" type="submit">' . esc_html__('Apply filters', 'uninet-core') . '</button>';

        if ($this->has_active_filters()) {
            echo '<a class="uninet-filter-clear" href="' . esc_url($this->clear_filters_url()) . '">' . esc_html__('Clear all', 'uninet-core') . '</a>';
        }

        echo '</div>';
        echo '</form>';
        echo '</aside>';
        echo '<button class="uninet-filter-backdrop" type="button" data-uninet-filter-close tabindex="-1" aria-label="' . esc_attr__('Close filters', 'uninet-core') . '"></button>';
    }

    /**
     * Render price boundary inputs when the current archive has a useful range.
     *
     * @param array $bounds Minimum and maximum catalog prices.
     */
    private function render_price_filter($bounds)
    {
        $minimum       = $this->requested_price('minimum');
        $maximum       = $this->requested_price('maximum');
        $range_minimum = (int) floor($bounds['minimum']);
        $range_maximum = (int) ceil($bounds['maximum']);
        $range_span    = $range_maximum - $range_minimum;
        $step          = 1;

        if ($range_span >= 100000) {
            $step = 1000;
        } elseif ($range_span >= 10000) {
            $step = 500;
        } elseif ($range_span >= 1000) {
            $step = 100;
        } elseif ($range_span >= 100) {
            $step = 10;
        }

        $minimum_value = null !== $minimum ? max($range_minimum, min($range_maximum, (int) round($minimum))) : $range_minimum;
        $maximum_value = null !== $maximum ? max($range_minimum, min($range_maximum, (int) round($maximum))) : $range_maximum;

        if ($minimum_value > $maximum_value) {
            $temporary     = $minimum_value;
            $minimum_value = $maximum_value;
            $maximum_value = $temporary;
        }

        echo '<fieldset class="uninet-filter-group uninet-filter-group--price">';
        echo '<legend>' . esc_html__('Price range', 'uninet-core') . '</legend>';
        echo '<div class="uninet-price-filter" data-uninet-price-filter>';
        echo '<div class="uninet-price-filter__values" aria-live="polite">';
        echo '<span><small>' . esc_html__('From', 'uninet-core') . '</small><output data-uninet-price-min-output>KSh ' . esc_html(number_format_i18n($minimum_value, 0)) . '</output></span>';
        echo '<span><small>' . esc_html__('To', 'uninet-core') . '</small><output data-uninet-price-max-output>KSh ' . esc_html(number_format_i18n($maximum_value, 0)) . '</output></span>';
        echo '</div>';
        echo '<div class="uninet-price-slider">';
        echo '<span class="uninet-price-slider__track" aria-hidden="true"><span data-uninet-price-track></span></span>';
        echo '<input type="range" min="' . esc_attr($range_minimum) . '" max="' . esc_attr($range_maximum) . '" step="' . esc_attr($step) . '" value="' . esc_attr($minimum_value) . '" data-uninet-price-min-range aria-label="' . esc_attr__('Minimum price', 'uninet-core') . '" />';
        echo '<input type="range" min="' . esc_attr($range_minimum) . '" max="' . esc_attr($range_maximum) . '" step="' . esc_attr($step) . '" value="' . esc_attr($maximum_value) . '" data-uninet-price-max-range aria-label="' . esc_attr__('Maximum price', 'uninet-core') . '" />';
        echo '</div>';
        echo '<div class="uninet-price-filter__inputs">';
        echo '<label>';
        echo '<span>' . esc_html__('Minimum (KSh)', 'uninet-core') . '</span>';
        echo '<input type="number" name="uninet_min_price" min="' . esc_attr($range_minimum) . '" max="' . esc_attr($range_maximum) . '" step="' . esc_attr($step) . '" placeholder="' . esc_attr($range_minimum) . '" value="' . esc_attr(null !== $minimum ? $minimum_value : '') . '" data-uninet-price-min-input />';
        echo '</label>';
        echo '<label>';
        echo '<span>' . esc_html__('Maximum (KSh)', 'uninet-core') . '</span>';
        echo '<input type="number" name="uninet_max_price" min="' . esc_attr($range_minimum) . '" max="' . esc_attr($range_maximum) . '" step="' . esc_attr($step) . '" placeholder="' . esc_attr($range_maximum) . '" value="' . esc_attr(null !== $maximum ? $maximum_value : '') . '" data-uninet-price-max-input />';
        echo '</label>';
        echo '</div>';
        echo '</div>';
        echo '<p class="uninet-price-filter__note">' . esc_html__('Displayed prices are pre-tax.', 'uninet-core') . '</p>';
        echo '</fieldset>';
    }

    /**
     * Render active filter chips with one-click removal.
     */
    private function render_active_filters()
    {
        $items = $this->active_filter_items();

        if (empty($items)) {
            return;
        }

        echo '<div class="uninet-active-filters" aria-label="' . esc_attr__('Active product filters', 'uninet-core') . '">';
        echo '<span class="uninet-active-filters__label">' . esc_html__('Active filters:', 'uninet-core') . '</span>';

        foreach ($items as $item) {
            echo '<a class="uninet-active-filter" href="' . esc_url($item['url']) . '">';
            echo '<span>' . esc_html($item['label']) . '</span>';
            echo $this->close_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '<span class="screen-reader-text">' . esc_html__('Remove filter', 'uninet-core') . '</span>';
            echo '</a>';
        }

        echo '<a class="uninet-active-filters__clear" href="' . esc_url($this->clear_filters_url()) . '">' . esc_html__('Clear all', 'uninet-core') . '</a>';
        echo '</div>';
    }

    /**
     * Get filter groups backed by brands and global product attributes.
     */
    private function get_filter_groups()
    {
        if (null !== $this->filter_groups) {
            return $this->filter_groups;
        }

        $this->filter_groups = [];
        $product_ids = $this->get_context_product_ids();

        if (empty($product_ids)) {
            return $this->filter_groups;
        }

        foreach ($this->supported_filter_taxonomies() as $taxonomy => $label) {
            if (is_tax($taxonomy)) {
                continue;
            }

            $terms = $this->terms_for_products($product_ids, $taxonomy);
            $selected = $this->selected_terms($taxonomy);

            if (empty($terms) && empty($selected)) {
                continue;
            }

            $this->filter_groups[] = [
                'taxonomy' => $taxonomy,
                'label' => $label,
                'key' => $this->filter_key($taxonomy),
                'terms' => array_slice($terms, 0, 16),
                'selected' => $selected,
            ];
        }

        return $this->filter_groups;
    }

    /**
     * Get brand and global attribute taxonomies, deduplicated by slug.
     */
    private function supported_filter_taxonomies()
    {
        $taxonomies = [];

        if (taxonomy_exists(self::BRAND_TAXONOMY)) {
            $taxonomies[self::BRAND_TAXONOMY] = __('Brand', 'uninet-core');
        }

        if (! function_exists('wc_get_attribute_taxonomies')) {
            return $taxonomies;
        }

        foreach (wc_get_attribute_taxonomies() as $attribute) {
            $taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);

            if (isset($taxonomies[$taxonomy]) || ! taxonomy_exists($taxonomy)) {
                continue;
            }

            $taxonomies[$taxonomy] = $attribute->attribute_label;
        }

        return $taxonomies;
    }

    /**
     * Get terms and archive-scoped counts in one relationship query.
     *
     * @param array  $product_ids Product IDs.
     * @param string $taxonomy    Filter taxonomy.
     */
    private function terms_for_products($product_ids, $taxonomy)
    {
        $relationships = wp_get_object_terms(
            $product_ids,
            $taxonomy,
            [
                'fields' => 'all_with_object_id',
                'orderby' => 'name',
                'order' => 'ASC',
            ]
        );

        if (is_wp_error($relationships) || empty($relationships)) {
            return [];
        }

        $terms = [];

        foreach ($relationships as $term) {
            if (! $term instanceof \WP_Term) {
                continue;
            }

            if (! isset($terms[$term->term_id])) {
                $terms[$term->term_id] = [
                    'term' => $term,
                    'count' => 0,
                ];
            }

            ++$terms[$term->term_id]['count'];
        }

        uasort(
            $terms,
            function ($first, $second) {
                return strnatcasecmp($first['term']->name, $second['term']->name);
            }
        );

        return array_values($terms);
    }

    /**
     * Get product IDs for the current archive before buyer filters are applied.
     */
    private function get_context_product_ids()
    {
        if (null !== $this->context_product_ids) {
            return $this->context_product_ids;
        }

        $arguments = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'fields' => 'ids',
            'posts_per_page' => 500,
            'no_found_rows' => true,
            'orderby' => 'ID',
            'order' => 'ASC',
            'suppress_filters' => true,
        ];

        if (is_product_taxonomy()) {
            $term = get_queried_object();

            if ($term instanceof \WP_Term) {
                $arguments['tax_query'] = [
                    [
                        'taxonomy' => $term->taxonomy,
                        'field' => 'term_id',
                        'terms' => [(int) $term->term_id],
                        'include_children' => 'product_cat' === $term->taxonomy,
                    ],
                ];
            }
        } elseif ($this->is_product_search()) {
            $arguments['s'] = get_search_query();
        }

        $this->context_product_ids = array_map('absint', get_posts($arguments));

        return $this->context_product_ids;
    }

    /**
     * Get the catalog price range for the current unfiltered archive context.
     */
    private function get_price_bounds()
    {
        if (null !== $this->price_bounds) {
            return $this->price_bounds;
        }

        $this->price_bounds = null;
        $product_ids = $this->get_context_product_ids();

        if (empty($product_ids)) {
            return null;
        }

        global $wpdb;

        $placeholders = implode(', ', array_fill(0, count($product_ids), '%d'));
        $query = $wpdb->prepare(
            "SELECT MIN(CAST(meta_value AS DECIMAL(20,4))) AS minimum, MAX(CAST(meta_value AS DECIMAL(20,4))) AS maximum
            FROM {$wpdb->postmeta}
            WHERE post_id IN ({$placeholders})
            AND meta_key = '_price'
            AND meta_value <> ''",
            $product_ids
        );
        $result = $wpdb->get_row($query); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        if (! $result || null === $result->minimum || null === $result->maximum) {
            return null;
        }

        $this->price_bounds = [
            'minimum' => (float) floor((float) $result->minimum),
            'maximum' => (float) ceil((float) $result->maximum),
        ];

        return $this->price_bounds;
    }

    /**
     * Get selected term slugs for a filter taxonomy.
     *
     * @param string $taxonomy Taxonomy name.
     */
    private function selected_terms($taxonomy)
    {
        $key = $this->filter_key($taxonomy);

        if (! isset($_GET[$key])) {
            return [];
        }

        $values = wp_unslash($_GET[$key]);

        if (! is_array($values)) {
            $values = explode(',', (string) $values);
        }

        $clean_values = [];

        foreach ($values as $value) {
            if (! is_scalar($value)) {
                continue;
            }

            $clean_values[] = sanitize_title((string) $value);
        }

        $values = array_values(array_unique(array_filter($clean_values)));

        return array_slice($values, 0, 20);
    }

    /**
     * Get a requested price boundary.
     *
     * @param string $boundary Minimum or maximum.
     */
    private function requested_price($boundary)
    {
        $key = 'minimum' === $boundary ? 'uninet_min_price' : 'uninet_max_price';

        if (! isset($_GET[$key]) || ! is_scalar($_GET[$key]) || '' === $_GET[$key]) {
            return null;
        }

        $value = wc_format_decimal(wp_unslash($_GET[$key]));

        if (! is_numeric($value)) {
            return null;
        }

        return max(0, (float) $value);
    }

    /**
     * Get a sanitized catalog ordering request.
     */
    private function requested_orderby()
    {
        if (! isset($_GET['orderby']) || ! is_scalar($_GET['orderby'])) {
            return '';
        }

        return sanitize_key((string) wp_unslash($_GET['orderby']));
    }

    /**
     * Build active-filter display items and removal URLs.
     */
    private function active_filter_items()
    {
        $items = [];

        foreach ($this->supported_filter_taxonomies() as $taxonomy => $group_label) {
            $key = $this->filter_key($taxonomy);

            foreach ($this->selected_terms($taxonomy) as $slug) {
                $term = get_term_by('slug', $slug, $taxonomy);

                if (! $term || is_wp_error($term)) {
                    $term_name = ucwords(str_replace('-', ' ', $slug));
                } else {
                    $term_name = $term->name;
                }

                $items[] = [
                    'label' => $group_label . ': ' . $term_name,
                    'url' => $this->remove_filter_url($key, $slug),
                ];
            }
        }

        foreach (['minimum', 'maximum'] as $boundary) {
            $price = $this->requested_price($boundary);

            if (null === $price) {
                continue;
            }

            $key = 'minimum' === $boundary ? 'uninet_min_price' : 'uninet_max_price';
            $label = 'minimum' === $boundary ? __('From', 'uninet-core') : __('Up to', 'uninet-core');
            $items[] = [
                'label' => sprintf('%s KSh %s', $label, number_format_i18n($price, 0)),
                'url' => $this->remove_filter_url($key),
            ];
        }

        return $items;
    }

    /**
     * Count active term and price filters.
     */
    private function active_filter_count()
    {
        return count($this->active_filter_items());
    }

    /**
     * Whether any buyer filters are active.
     */
    private function has_active_filters()
    {
        foreach ($this->supported_filter_taxonomies() as $taxonomy => $label) {
            if (! empty($this->selected_terms($taxonomy))) {
                return true;
            }
        }

        return null !== $this->requested_price('minimum') || null !== $this->requested_price('maximum');
    }

    /**
     * Whether this archive can display at least one filter control.
     */
    private function has_filter_options()
    {
        if (! empty($this->get_filter_groups())) {
            return true;
        }

        $bounds = $this->get_price_bounds();

        return null !== $bounds && $bounds['maximum'] > $bounds['minimum'];
    }

    /**
     * Render hidden values that should survive a filter submission.
     */
    private function render_preserved_query_inputs()
    {
        if ($this->is_product_search()) {
            echo '<input type="hidden" name="s" value="' . esc_attr(get_search_query()) . '" />';
            echo '<input type="hidden" name="post_type" value="product" />';
        }

        $orderby = $this->requested_orderby();

        if ($orderby) {
            echo '<input type="hidden" name="orderby" value="' . esc_attr($orderby) . '" />';
        }
    }

    /**
     * Build a URL that removes one filter while retaining the rest.
     *
     * @param string      $key   Query key.
     * @param string|null $value Optional array value to remove.
     */
    private function remove_filter_url($key, $value = null)
    {
        $arguments = $this->current_filter_query_arguments();

        if (null !== $value && isset($arguments[$key]) && is_array($arguments[$key])) {
            $arguments[$key] = array_values(array_diff($arguments[$key], [$value]));

            if (empty($arguments[$key])) {
                unset($arguments[$key]);
            }
        } else {
            unset($arguments[$key]);
        }

        return add_query_arg($arguments, $this->archive_url());
    }

    /**
     * Build the URL that clears buyer filters but preserves search and sorting.
     */
    private function clear_filters_url()
    {
        $arguments = [];

        if ($this->is_product_search()) {
            $arguments['s'] = get_search_query();
            $arguments['post_type'] = 'product';
        }

        $orderby = $this->requested_orderby();

        if ($orderby) {
            $arguments['orderby'] = $orderby;
        }

        return add_query_arg($arguments, $this->archive_url());
    }

    /**
     * Get sanitized current buyer-filter query arguments.
     */
    private function current_filter_query_arguments()
    {
        $arguments = [];

        if ($this->is_product_search()) {
            $arguments['s'] = get_search_query();
            $arguments['post_type'] = 'product';
        }

        $orderby = $this->requested_orderby();

        if ($orderby) {
            $arguments['orderby'] = $orderby;
        }

        foreach ($this->supported_filter_taxonomies() as $taxonomy => $label) {
            $selected = $this->selected_terms($taxonomy);

            if (! empty($selected)) {
                $arguments[$this->filter_key($taxonomy)] = $selected;
            }
        }

        foreach (['minimum', 'maximum'] as $boundary) {
            $price = $this->requested_price($boundary);

            if (null !== $price) {
                $arguments['minimum' === $boundary ? 'uninet_min_price' : 'uninet_max_price'] = wc_format_decimal($price);
            }
        }

        return $arguments;
    }

    /**
     * Build contextual category navigation items.
     */
    private function category_navigation_items()
    {
        $items = [];
        $label = __('Shop by category', 'uninet-core');

        if (is_product_category()) {
            $current = get_queried_object();

            if ($current instanceof \WP_Term) {
                $parent = $current->parent ? get_term($current->parent, 'product_cat') : $current;

                if ($parent instanceof \WP_Term && ! is_wp_error($parent)) {
                    $children = $this->product_categories((int) $parent->term_id);
                    $label = sprintf(__('Browse within %s', 'uninet-core'), $parent->name);
                    $items[] = $this->category_navigation_item($parent, (int) $current->term_id === (int) $parent->term_id, sprintf(__('All %s', 'uninet-core'), $parent->name));

                    foreach ($children as $child) {
                        $items[] = $this->category_navigation_item($child, (int) $current->term_id === (int) $child->term_id);
                    }

                    if (count($items) > 1) {
                        return compact('label', 'items');
                    }
                }
            }
        }

        $shop_url = wc_get_page_permalink('shop');
        $items[] = [
            'label' => __('All products', 'uninet-core'),
            'url' => $shop_url,
            'active' => is_shop(),
        ];

        foreach ($this->product_categories(0) as $category) {
            $items[] = $this->category_navigation_item($category, is_product_category($category->slug));
        }

        return compact('label', 'items');
    }

    /**
     * Build one category navigation item.
     *
     * @param \WP_Term    $term   Category term.
     * @param bool        $active Whether the category is current.
     * @param string|null $label  Optional display label.
     */
    private function category_navigation_item(\WP_Term $term, $active = false, $label = null)
    {
        $url = get_term_link($term);

        return [
            'label' => null !== $label ? $label : $term->name,
            'url' => is_wp_error($url) ? wc_get_page_permalink('shop') : $url,
            'active' => (bool) $active,
        ];
    }

    /**
     * Get product categories while excluding WooCommerce's fallback category.
     *
     * @param int $parent Parent category ID.
     */
    private function product_categories($parent)
    {
        $terms = get_terms(
            [
                'taxonomy' => 'product_cat',
                'parent' => $parent,
                'hide_empty' => false,
                'orderby' => 'menu_order',
                'order' => 'ASC',
            ]
        );

        if (is_wp_error($terms)) {
            return [];
        }

        return array_values(
            array_filter(
                $terms,
                function ($term) {
                    return 'uncategorized' !== $term->slug;
                }
            )
        );
    }

    /**
     * Render fallback category links beneath an empty archive.
     */
    private function render_empty_category_links()
    {
        $categories = array_slice($this->product_categories(0), 0, 4);

        if (empty($categories)) {
            return;
        }

        echo '<nav class="uninet-archive-empty__categories" aria-label="' . esc_attr__('Browse product categories', 'uninet-core') . '">';

        foreach ($categories as $category) {
            $url = get_term_link($category);

            if (is_wp_error($url)) {
                continue;
            }

            echo '<a href="' . esc_url($url) . '">' . esc_html($category->name) . '</a>';
        }

        echo '</nav>';
    }

    /**
     * Get concise result count text for the current loop page.
     */
    private function result_count_text()
    {
        $total = (int) wc_get_loop_prop('total');
        $per_page = max(1, (int) wc_get_loop_prop('per_page'));
        $current = max(1, (int) wc_get_loop_prop('current_page'));

        if (0 === $total) {
            return __('0 products', 'uninet-core');
        }

        if (1 === $total) {
            return __('1 product', 'uninet-core');
        }

        if ($total <= $per_page) {
            return sprintf(_n('%d product', '%d products', $total, 'uninet-core'), $total);
        }

        $first = (($current - 1) * $per_page) + 1;
        $last = min($total, $current * $per_page);

        return sprintf(__('Showing %1$d-%2$d of %3$d products', 'uninet-core'), $first, $last, $total);
    }

    /**
     * Build a stable query key for a filter taxonomy.
     *
     * @param string $taxonomy Taxonomy name.
     */
    private function filter_key($taxonomy)
    {
        if (self::BRAND_TAXONOMY === $taxonomy) {
            return 'uninet_brand';
        }

        return 'uninet_attr_' . sanitize_title(preg_replace('/^pa_/', '', $taxonomy));
    }

    /**
     * Get the canonical URL for the current product archive.
     */
    private function archive_url()
    {
        if (is_product_taxonomy()) {
            $term = get_queried_object();

            if ($term instanceof \WP_Term) {
                $url = get_term_link($term);

                if (! is_wp_error($url)) {
                    return $url;
                }
            }
        }

        if ($this->is_product_search()) {
            return home_url('/');
        }

        return wc_get_page_permalink('shop');
    }

    /**
     * Whether the current request is a product search archive.
     */
    private function is_product_search()
    {
        if (! is_search()) {
            return false;
        }

        $post_type = get_query_var('post_type');

        return 'product' === $post_type || (is_array($post_type) && in_array('product', $post_type, true));
    }

    /**
     * Whether current conditional tags describe a product archive.
     */
    private function is_supported_archive()
    {
        return is_shop() || is_product_taxonomy() || $this->is_product_search();
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
            return __('Compare business-ready laptops by processor, memory, storage, display size, and warranty before sending an order request.', 'uninet-core');
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

        return __('Compare the key product details, then send an order request for staff confirmation before payment.', 'uninet-core');
    }

    /**
     * Filter control icon.
     */
    private function filter_icon()
    {
        return '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M4 7h10"></path><path d="M18 7h2"></path><circle cx="16" cy="7" r="2"></circle><path d="M4 17h2"></path><path d="M10 17h10"></path><circle cx="8" cy="17" r="2"></circle></svg>';
    }

    /**
     * Close control icon.
     */
    private function close_icon()
    {
        return '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="m6 6 12 12"></path><path d="m18 6-12 12"></path></svg>';
    }

    /**
     * Empty-state search icon.
     */
    private function search_icon()
    {
        return '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><circle cx="11" cy="11" r="6"></circle><path d="m16 16 4 4"></path></svg>';
    }
}
