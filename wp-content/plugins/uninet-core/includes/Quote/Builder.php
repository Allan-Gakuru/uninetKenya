<?php
/**
 * Public Build a Quote workspace and secure catalogue-backed submission flow.
 *
 * @package UninetCore
 */

namespace Uninet\Core\Quote;

if (! defined('ABSPATH')) {
    exit;
}

final class Builder
{
    const SEARCH_ACTION = 'uninet_quote_search';
    const SUBMIT_ACTION = 'uninet_quote_submit';
    const NONCE_ACTION = 'uninet_quote_builder';
    const MAX_ITEMS = 40;

    /**
     * Register frontend and AJAX hooks.
     */
    public function register()
    {
        add_shortcode('uninet_quote_builder', [$this, 'render']);
        add_filter('the_content', [$this, 'append_to_quote_page']);
        add_filter('body_class', [$this, 'body_class']);
        add_action('wp_ajax_' . self::SEARCH_ACTION, [$this, 'search_products']);
        add_action('wp_ajax_nopriv_' . self::SEARCH_ACTION, [$this, 'search_products']);
        add_action('wp_ajax_' . self::SUBMIT_ACTION, [$this, 'submit_quote']);
        add_action('wp_ajax_nopriv_' . self::SUBMIT_ACTION, [$this, 'submit_quote']);
    }

    /**
     * Return the published quote page URL with optional workflow context.
     *
     * @param array $query_args Optional query parameters.
     */
    public static function page_url($query_args = [])
    {
        $page = get_page_by_path('build-a-quote', OBJECT, 'page');
        $url = home_url('/build-a-quote/');

        if ($page instanceof \WP_Post && 'publish' === $page->post_status) {
            $url = get_permalink($page);
        }

        return $query_args ? add_query_arg($query_args, $url) : $url;
    }

    /**
     * Ensure the managed quote page always renders the builder shortcode.
     *
     * @param string $content Page content.
     */
    public function append_to_quote_page($content)
    {
        if (
            ! is_page('build-a-quote')
            || ! in_the_loop()
            || ! is_main_query()
            || has_shortcode($content, 'uninet_quote_builder')
        ) {
            return $content;
        }

        return $content . do_shortcode('[uninet_quote_builder]');
    }

    /**
     * Add a scoped body class for the full-width procurement workspace.
     *
     * @param array $classes Existing body classes.
     */
    public function body_class($classes)
    {
        if (is_page('build-a-quote')) {
            $classes[] = 'uninet-quote-page';
        }

        return $classes;
    }

    /**
     * Render the quote builder, review dialog, and success state.
     */
    public function render()
    {
        if (! class_exists('WooCommerce')) {
            return '<p>' . esc_html__('The product catalogue is temporarily unavailable. Please call 0770 313 200 for assistance.', 'uninet-core') . '</p>';
        }

        $categories = $this->categories();
        $source = $this->request_source();
        $initial_product = $this->initial_product();
        $initial_product_json = $initial_product ? wp_json_encode($initial_product) : '';

        ob_start();
        ?>
        <section
            class="uninet-quote"
            data-uninet-quote-builder
            data-uninet-quote-source="<?php echo esc_attr($source); ?>"
            data-uninet-quote-initial-product="<?php echo esc_attr($initial_product_json); ?>"
            aria-labelledby="uninet-quote-title"
        >
            <header class="uninet-quote__header">
                <div>
                    <h1 id="uninet-quote-title"><?php esc_html_e('Build a Quote', 'uninet-core'); ?></h1>
                    <p><?php esc_html_e('Add the products your organisation needs. We will confirm availability, delivery, tax, and the final quotation.', 'uninet-core'); ?></p>
                </div>
                <ul class="uninet-quote__assurances" aria-label="<?php esc_attr_e('Quote assurances', 'uninet-core'); ?>">
                    <li><?php esc_html_e('Catalogue products only', 'uninet-core'); ?></li>
                    <li><?php esc_html_e('Indicative pre-tax totals', 'uninet-core'); ?></li>
                    <li><?php esc_html_e('No stock is reserved', 'uninet-core'); ?></li>
                </ul>
            </header>

            <form class="uninet-quote__form" data-uninet-quote-form method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
                <input type="hidden" name="action" value="<?php echo esc_attr(self::SUBMIT_ACTION); ?>">
                <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce(self::NONCE_ACTION)); ?>">
                <input type="hidden" name="items" value="[]" data-uninet-quote-items>
                <input type="hidden" name="quote_source" value="<?php echo esc_attr($source); ?>">

                <div class="uninet-quote__honeypot" aria-hidden="true">
                    <label for="uninet-quote-website"><?php esc_html_e('Website', 'uninet-core'); ?></label>
                    <input id="uninet-quote-website" type="text" name="website" value="" tabindex="-1" autocomplete="off">
                </div>

                <div class="uninet-quote__workspace">
                    <main class="uninet-quote__main">
                        <section class="uninet-quote-finder" aria-labelledby="uninet-quote-finder-title">
                            <div class="uninet-quote-section-heading">
                                <div>
                                    <h2 id="uninet-quote-finder-title"><?php esc_html_e('Find catalogue products', 'uninet-core'); ?></h2>
                                    <p><?php esc_html_e('Search by product name, model, or SKU. Newly published catalogue products appear automatically.', 'uninet-core'); ?></p>
                                </div>
                            </div>

                            <div class="uninet-quote-search" data-uninet-quote-search-wrap>
                                <label for="uninet-quote-search"><?php esc_html_e('Search the product catalogue', 'uninet-core'); ?></label>
                                <div class="uninet-quote-search__control">
                                    <span class="uninet-quote-search__icon" aria-hidden="true"></span>
                                    <input
                                        id="uninet-quote-search"
                                        type="search"
                                        placeholder="<?php esc_attr_e('Search by product, model, or SKU', 'uninet-core'); ?>"
                                        autocomplete="off"
                                        enterkeyhint="search"
                                        data-uninet-quote-search
                                    >
                                    <button type="button" class="uninet-quote-search__clear" data-uninet-quote-search-clear hidden>
                                        <span aria-hidden="true">&times;</span>
                                        <span class="screen-reader-text"><?php esc_html_e('Clear product search', 'uninet-core'); ?></span>
                                    </button>
                                </div>

                                <div class="uninet-quote-search__categories" aria-label="<?php esc_attr_e('Filter by product category', 'uninet-core'); ?>">
                                    <button type="button" class="is-active" data-uninet-quote-category="" aria-pressed="true">
                                        <?php esc_html_e('All products', 'uninet-core'); ?>
                                    </button>
                                    <?php foreach ($categories as $category) : ?>
                                        <button type="button" data-uninet-quote-category="<?php echo esc_attr($category->slug); ?>" aria-pressed="false">
                                            <?php echo esc_html($category->name); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>

                                <div class="uninet-quote-search__results" data-uninet-quote-results hidden>
                                    <div class="uninet-quote-search__status" data-uninet-quote-search-status role="status" aria-live="polite"></div>
                                    <div data-uninet-quote-result-list></div>
                                </div>
                            </div>
                        </section>

                        <section class="uninet-quote-lines" aria-labelledby="uninet-quote-lines-title">
                            <div class="uninet-quote-section-heading">
                                <div>
                                    <h2 id="uninet-quote-lines-title"><?php esc_html_e('Selected products', 'uninet-core'); ?></h2>
                                    <p><?php esc_html_e('Set the quantity and add any model-specific requirement before reviewing the request.', 'uninet-core'); ?></p>
                                </div>
                                <span class="uninet-quote-lines__count" data-uninet-quote-line-count><?php esc_html_e('0 products', 'uninet-core'); ?></span>
                            </div>

                            <div class="uninet-quote-lines__empty" data-uninet-quote-empty>
                                <strong><?php esc_html_e('No products selected yet', 'uninet-core'); ?></strong>
                                <p><?php esc_html_e('Use the catalogue search above to add the first product to this quote request.', 'uninet-core'); ?></p>
                            </div>

                            <div class="uninet-quote-lines__table" data-uninet-quote-lines hidden>
                                <div class="uninet-quote-lines__header" aria-hidden="true">
                                    <span><?php esc_html_e('Product / model', 'uninet-core'); ?></span>
                                    <span><?php esc_html_e('Quantity', 'uninet-core'); ?></span>
                                    <span><?php esc_html_e('Pre-tax price', 'uninet-core'); ?></span>
                                    <span><?php esc_html_e('Line total', 'uninet-core'); ?></span>
                                    <span><?php esc_html_e('Actions', 'uninet-core'); ?></span>
                                </div>
                                <div data-uninet-quote-line-list></div>
                            </div>

                            <p class="uninet-quote-lines__price-note" data-uninet-quote-price-note hidden>
                                <?php esc_html_e('Displayed prices are indicative and pre-tax. Unpriced products are excluded from the subtotal until staff confirms their price.', 'uninet-core'); ?>
                            </p>
                        </section>

                        <section class="uninet-quote-details" aria-labelledby="uninet-quote-details-title">
                            <div class="uninet-quote-section-heading">
                                <div>
                                    <h2 id="uninet-quote-details-title"><?php esc_html_e('Your organisation', 'uninet-core'); ?></h2>
                                    <p><?php esc_html_e('These details let our team prepare a useful, business-ready quotation.', 'uninet-core'); ?></p>
                                </div>
                                <span><?php esc_html_e('Required fields are marked *', 'uninet-core'); ?></span>
                            </div>

                            <div class="uninet-quote-fields">
                                <?php $this->text_field('organisation_name', __('Organisation name', 'uninet-core'), __('e.g. Karibu Consulting Ltd', 'uninet-core'), true, 'text', 'organization'); ?>
                                <?php $this->text_field('full_name', __('Contact person', 'uninet-core'), __('Your full name', 'uninet-core'), true, 'text', 'name'); ?>
                                <?php $this->text_field('phone', __('Phone number', 'uninet-core'), __('e.g. 0770 313 200', 'uninet-core'), true, 'tel', 'tel'); ?>
                                <?php $this->text_field('email', __('Business email', 'uninet-core'), __('name@organisation.co.ke', 'uninet-core'), true, 'email', 'email'); ?>
                                <?php $this->kra_pin_field(); ?>
                                <?php $this->select_field('organisation_type', __('Organisation type', 'uninet-core'), $this->organisation_types(), true); ?>
                                <?php $this->text_field('business_address', __('Business address', 'uninet-core'), __('Building, road, or postal address', 'uninet-core'), true, 'text', 'street-address', 'uninet-quote-field--wide'); ?>
                            </div>
                        </section>

                        <section class="uninet-quote-details" aria-labelledby="uninet-quote-delivery-title">
                            <div class="uninet-quote-section-heading">
                                <div>
                                    <h2 id="uninet-quote-delivery-title"><?php esc_html_e('Delivery and timing', 'uninet-core'); ?></h2>
                                    <p><?php esc_html_e('Delivery timing and transport charges remain subject to staff confirmation.', 'uninet-core'); ?></p>
                                </div>
                            </div>

                            <div class="uninet-quote-fields">
                                <?php $this->select_field('county', __('County', 'uninet-core'), array_combine($this->counties(), $this->counties()), true); ?>
                                <?php $this->text_field('town', __('Town', 'uninet-core'), __('e.g. Nairobi, Thika, Kisumu', 'uninet-core'), true, 'text', 'address-level2'); ?>
                                <?php $this->select_field('fulfilment', __('How should we fulfil the quote?', 'uninet-core'), ['delivery' => __('Delivery', 'uninet-core'), 'pickup' => __('Pickup', 'uninet-core')], true); ?>
                                <?php $this->text_field('required_by', __('Required by', 'uninet-core'), '', false, 'date', '', '', current_time('Y-m-d')); ?>
                                <?php $this->textarea_field('delivery_details', __('Delivery or pickup details', 'uninet-core'), __('Optional: building, floor, landmark, pickup contact, or access instructions.', 'uninet-core')); ?>
                                <?php $this->textarea_field('notes', __('Procurement notes', 'uninet-core'), __('Optional: preferred brands, compatibility requirements, approval deadline, or other context.', 'uninet-core')); ?>
                            </div>
                        </section>

                        <div class="uninet-quote-consent">
                            <label>
                                <input type="checkbox" name="consent" value="yes" required>
                                <span><?php esc_html_e('I agree that Uninet Technologies may use these details to prepare and follow up this quote request. This request is not a confirmed order.', 'uninet-core'); ?> *</span>
                            </label>
                            <a href="<?php echo esc_url(get_privacy_policy_url() ?: home_url('/privacy-policy/')); ?>"><?php esc_html_e('Read our privacy policy', 'uninet-core'); ?></a>
                        </div>

                        <div class="uninet-quote-errors" data-uninet-quote-errors role="alert" tabindex="-1" hidden></div>
                    </main>

                    <aside class="uninet-quote-summary" aria-labelledby="uninet-quote-summary-title">
                        <div class="uninet-quote-summary__sticky">
                            <h2 id="uninet-quote-summary-title"><?php esc_html_e('Quote summary', 'uninet-core'); ?></h2>
                            <p class="uninet-quote-summary__count" data-uninet-quote-summary-count><?php esc_html_e('0 products', 'uninet-core'); ?></p>
                            <div class="uninet-quote-summary__total">
                                <span><?php esc_html_e('Indicative pre-tax subtotal', 'uninet-core'); ?></span>
                                <strong data-uninet-quote-subtotal>KSh&nbsp;0</strong>
                            </div>
                            <p class="uninet-quote-summary__unpriced" data-uninet-quote-unpriced hidden></p>
                            <ul class="uninet-quote-summary__notes">
                                <li><?php esc_html_e('Prices shown are pre-tax.', 'uninet-core'); ?></li>
                                <li><?php esc_html_e('Staff confirms availability and final tax.', 'uninet-core'); ?></li>
                                <li><?php esc_html_e('Delivery timing and charges are confirmed separately.', 'uninet-core'); ?></li>
                            </ul>
                            <button type="button" class="button uninet-quote-summary__review" data-uninet-quote-review disabled>
                                <?php esc_html_e('Review quote request', 'uninet-core'); ?>
                            </button>
                            <p class="uninet-quote-summary__support">
                                <?php esc_html_e('Need help choosing?', 'uninet-core'); ?>
                                <a href="tel:+254770313200">0770 313 200</a>
                            </p>
                        </div>
                    </aside>
                </div>

                <div class="uninet-quote-mobile-bar" data-uninet-quote-mobile-bar hidden>
                    <div>
                        <strong data-uninet-quote-mobile-count><?php esc_html_e('0 products', 'uninet-core'); ?></strong>
                        <span data-uninet-quote-mobile-total><?php esc_html_e('KSh 0 pre-tax', 'uninet-core'); ?></span>
                    </div>
                    <button type="button" class="button" data-uninet-quote-review><?php esc_html_e('Review', 'uninet-core'); ?></button>
                </div>

                <dialog class="uninet-quote-review" data-uninet-quote-dialog aria-labelledby="uninet-quote-review-title">
                    <div class="uninet-quote-review__header">
                        <div>
                            <span><?php esc_html_e('Final check', 'uninet-core'); ?></span>
                            <h2 id="uninet-quote-review-title"><?php esc_html_e('Review quote request', 'uninet-core'); ?></h2>
                        </div>
                        <button type="button" class="uninet-quote-review__close" data-uninet-quote-dialog-close>
                            <span aria-hidden="true">&times;</span>
                            <span class="screen-reader-text"><?php esc_html_e('Close quote review', 'uninet-core'); ?></span>
                        </button>
                    </div>
                    <div class="uninet-quote-review__body">
                        <div data-uninet-quote-review-content></div>
                        <p><?php esc_html_e('Submitting sends this request to the Uninet dashboard for staff review. It does not reserve stock or create an order.', 'uninet-core'); ?></p>
                    </div>
                    <div class="uninet-quote-review__actions">
                        <button type="button" class="button uninet-quote-review__edit" data-uninet-quote-dialog-close><?php esc_html_e('Keep editing', 'uninet-core'); ?></button>
                        <button type="button" class="button uninet-quote-review__submit" data-uninet-quote-submit><?php esc_html_e('Send quote request', 'uninet-core'); ?></button>
                    </div>
                    <div class="uninet-quote-review__status" data-uninet-quote-submit-status role="status" aria-live="polite"></div>
                </dialog>
            </form>

            <section class="uninet-quote-success" data-uninet-quote-success hidden tabindex="-1" aria-labelledby="uninet-quote-success-title">
                <div class="uninet-quote-success__mark" aria-hidden="true">&#10003;</div>
                <div>
                    <h2 id="uninet-quote-success-title"><?php esc_html_e('Your quote request is with our team', 'uninet-core'); ?></h2>
                    <p data-uninet-quote-success-message></p>
                    <p class="uninet-quote-success__reference" data-uninet-quote-reference></p>
                    <div class="uninet-quote-success__actions">
                        <a class="button" data-uninet-quote-whatsapp target="_blank" rel="noopener noreferrer"><?php esc_html_e('Continue on WhatsApp', 'uninet-core'); ?></a>
                        <a class="button uninet-quote-success__call" href="tel:+254770313200"><?php esc_html_e('Call 0770 313 200', 'uninet-core'); ?></a>
                    </div>
                    <p><?php esc_html_e('Staff will confirm product availability, tax, delivery, and the final quotation before any payment.', 'uninet-core'); ?></p>
                </div>
            </section>

            <noscript>
                <p class="uninet-quote-noscript"><?php esc_html_e('This quote builder needs JavaScript for catalogue search and line-item totals. Please enable JavaScript or call 0770 313 200.', 'uninet-core'); ?></p>
            </noscript>
        </section>
        <?php

        return (string) ob_get_clean();
    }

    /**
     * Search current published WooCommerce products by text, SKU, and category.
     */
    public function search_products()
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        $term = $this->limit(sanitize_text_field(wp_unslash($_GET['query'] ?? '')), 100);
        $category = sanitize_title(wp_unslash($_GET['category'] ?? ''));

        if (strlen($term) < 2 && ! $category) {
            wp_send_json_success(['products' => []]);
        }

        if ($category && ! term_exists($category, 'product_cat')) {
            wp_send_json_error(['message' => __('That product category is unavailable.', 'uninet-core')], 400);
        }

        $tax_query = $this->product_tax_query($category);
        $common = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'fields' => 'ids',
            'no_found_rows' => true,
            'tax_query' => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
        ];

        $title_args = $common;

        if ($term) {
            $title_args['s'] = $term;
            $title_args['orderby'] = 'relevance';
        } else {
            $title_args['orderby'] = ['menu_order' => 'ASC', 'date' => 'DESC'];
        }

        $ids = (new \WP_Query($title_args))->posts;

        if ($term) {
            $sku_args = $common;
            $sku_args['meta_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                [
                    'key' => '_sku',
                    'value' => $term,
                    'compare' => 'LIKE',
                ],
            ];
            $sku_args['orderby'] = 'date';
            $ids = array_merge($ids, (new \WP_Query($sku_args))->posts);
        }

        $ids = array_slice(array_values(array_unique(array_map('absint', $ids))), 0, 10);
        $products = [];

        foreach ($ids as $product_id) {
            $product = wc_get_product($product_id);

            if (! $product instanceof \WC_Product || ! $product->is_visible()) {
                continue;
            }

            $products[] = $this->product_payload($product);
        }

        wp_send_json_success(['products' => $products]);
    }

    /**
     * Validate, price, and store a private quote request.
     */
    public function submit_quote()
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        if (! empty($_POST['website'])) {
            wp_send_json_success([
                'reference' => __('Received', 'uninet-core'),
                'message' => __('Your quote request has been received.', 'uninet-core'),
                'whatsappUrl' => 'https://wa.me/254770313200',
            ]);
        }

        $values = $this->sanitize_submission(wp_unslash($_POST));
        $validation = $this->validate_submission($values);

        if ($validation) {
            wp_send_json_error($validation, 422);
        }

        if ($this->is_rate_limited()) {
            wp_send_json_error([
                'message' => __('Your previous quote request was received. Please wait one minute before submitting another.', 'uninet-core'),
            ], 429);
        }

        $raw_items = json_decode((string) ($values['items'] ?? ''), true);

        if (! is_array($raw_items) || ! $raw_items) {
            wp_send_json_error([
                'message' => __('Add at least one catalogue product before submitting the quote request.', 'uninet-core'),
                'field' => 'items',
            ], 422);
        }

        if (count($raw_items) > self::MAX_ITEMS) {
            wp_send_json_error([
                'message' => sprintf(
                    /* translators: %d: maximum product lines. */
                    __('A quote request can contain up to %d product lines.', 'uninet-core'),
                    self::MAX_ITEMS
                ),
                'field' => 'items',
            ], 422);
        }

        $priced = $this->normalize_items($raw_items);

        if (is_wp_error($priced)) {
            wp_send_json_error([
                'message' => $priced->get_error_message(),
                'field' => 'items',
            ], 422);
        }

        $items = $priced['items'];
        $subtotal = $priced['subtotal'];
        $unpriced_count = $priced['unpriced_count'];

        $post_id = wp_insert_post(
            [
                'post_type' => RequestPostType::POST_TYPE,
                'post_status' => 'publish',
                'post_title' => $values['organisation_name'] . ' - ' . $values['full_name'],
            ],
            true
        );

        if (is_wp_error($post_id)) {
            wp_send_json_error([
                'message' => __('We could not save the quote request. Please try again or call 0770 313 200.', 'uninet-core'),
            ], 500);
        }

        $reference = RequestPostType::reference($post_id);
        $meta_fields = [
            'organisation_name',
            'organisation_type',
            'full_name',
            'phone',
            'email',
            'kra_pin',
            'business_address',
            'county',
            'town',
            'fulfilment',
            'required_by',
            'delivery_details',
            'notes',
        ];

        foreach ($meta_fields as $field) {
            update_post_meta($post_id, '_uninet_quote_' . $field, $values[$field]);
        }

        update_post_meta($post_id, '_uninet_quote_reference', $reference);
        update_post_meta($post_id, '_uninet_quote_items', $items);
        update_post_meta($post_id, '_uninet_quote_subtotal', $subtotal);
        update_post_meta($post_id, '_uninet_quote_unpriced_count', $unpriced_count);
        update_post_meta($post_id, '_uninet_quote_currency', 'KES');
        update_post_meta($post_id, '_uninet_quote_price_context', 'pre-tax-indicative');
        update_post_meta($post_id, '_uninet_quote_source', 'build-a-quote-' . $values['source']);
        update_post_meta($post_id, RequestPostType::STATUS_META, 'new');
        update_post_meta($post_id, RequestPostType::STATUS_UPDATED_META, current_time('mysql'));

        wp_update_post([
            'ID' => $post_id,
            'post_title' => $reference . ' - ' . $values['organisation_name'],
        ]);

        $this->set_rate_limit();
        do_action('uninet_quote_request_created', $post_id, $values, $items);

        wp_send_json_success([
            'quoteId' => $post_id,
            'reference' => $reference,
            'message' => __('We saved your product list and organisation details for staff review.', 'uninet-core'),
            'salesPhone' => '0770 313 200',
            'telUrl' => 'tel:+254770313200',
            'whatsappUrl' => $this->whatsapp_url($reference, $values, $items, $subtotal, $unpriced_count),
        ]);
    }

    /**
     * Return product data needed by the browser workspace.
     *
     * @param \WC_Product $product Product object.
     */
    private function product_payload($product)
    {
        $image_id = $product->get_image_id();
        $image = $image_id ? wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail') : '';
        $price = $this->pre_tax_price($product);

        return [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'sku' => $product->get_sku(),
            'image' => $image ?: wc_placeholder_img_src('woocommerce_thumbnail'),
            'price' => $price,
            'pricePrefix' => $product->is_type('variable') && null !== $price ? __('From', 'uninet-core') : '',
            'availability' => __('Staff confirms availability', 'uninet-core'),
            'permalink' => get_permalink($product->get_id()),
        ];
    }

    /**
     * Build product visibility and category constraints.
     *
     * @param string $category Product-category slug.
     */
    private function product_tax_query($category)
    {
        $visibility = wc_get_product_visibility_term_ids();
        $excluded = array_filter([
            $visibility['exclude-from-search'] ?? 0,
            $visibility['exclude-from-catalog'] ?? 0,
        ]);
        $query = ['relation' => 'AND'];

        if ($excluded) {
            $query[] = [
                'taxonomy' => 'product_visibility',
                'field' => 'term_taxonomy_id',
                'terms' => $excluded,
                'operator' => 'NOT IN',
            ];
        }

        if ($category) {
            $query[] = [
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => [$category],
            ];
        }

        return $query;
    }

    /**
     * Rebuild trusted line items and current pre-tax prices server-side.
     *
     * @param array $raw_items Browser-submitted product IDs, quantities, and notes.
     */
    private function normalize_items($raw_items)
    {
        $normalized = [];

        foreach ($raw_items as $raw_item) {
            if (! is_array($raw_item)) {
                continue;
            }

            $product_id = absint($raw_item['product_id'] ?? 0);
            $quantity = max(1, min(999, absint($raw_item['quantity'] ?? 1)));
            $note = $this->limit(sanitize_textarea_field($raw_item['note'] ?? ''), 500);

            if (! $product_id) {
                continue;
            }

            if (isset($normalized[$product_id])) {
                $normalized[$product_id]['quantity'] = min(999, $normalized[$product_id]['quantity'] + $quantity);
                if ($note && ! $normalized[$product_id]['note']) {
                    $normalized[$product_id]['note'] = $note;
                }
                continue;
            }

            $product = wc_get_product($product_id);

            if (! $product instanceof \WC_Product || 'publish' !== get_post_status($product_id) || ! $product->is_visible()) {
                return new \WP_Error('invalid_quote_product', __('One selected product is no longer available in the public catalogue. Remove it and choose another product.', 'uninet-core'));
            }

            $unit_price = $this->pre_tax_price($product);
            $normalized[$product_id] = [
                'product_id' => $product_id,
                'name' => $product->get_name(),
                'sku' => $product->get_sku(),
                'quantity' => $quantity,
                'unit_price' => $unit_price,
                'line_total' => null === $unit_price ? null : round($unit_price * $quantity, wc_get_price_decimals()),
                'note' => $note,
            ];
        }

        if (! $normalized) {
            return new \WP_Error('empty_quote', __('Add at least one valid catalogue product.', 'uninet-core'));
        }

        $items = array_values($normalized);
        $subtotal = 0.0;
        $unpriced_count = 0;

        foreach ($items as $item) {
            if (null === $item['line_total']) {
                ++$unpriced_count;
            } else {
                $subtotal += (float) $item['line_total'];
            }
        }

        return [
            'items' => $items,
            'subtotal' => round($subtotal, wc_get_price_decimals()),
            'unpriced_count' => $unpriced_count,
        ];
    }

    /**
     * Return the current product price excluding tax.
     *
     * @param \WC_Product $product Product object.
     */
    private function pre_tax_price($product)
    {
        $raw_price = $product->get_price();

        if ('' === $raw_price) {
            return null;
        }

        return (float) wc_get_price_excluding_tax(
            $product,
            [
                'qty' => 1,
                'price' => (float) $raw_price,
            ]
        );
    }

    /**
     * Sanitize browser-submitted buyer and fulfilment fields.
     *
     * @param array $source Unslashed request data.
     */
    private function sanitize_submission($source)
    {
        return [
            'organisation_name' => $this->limit(sanitize_text_field($source['organisation_name'] ?? ''), 190),
            'organisation_type' => sanitize_key($source['organisation_type'] ?? ''),
            'full_name' => $this->limit(sanitize_text_field($source['full_name'] ?? ''), 190),
            'phone' => $this->limit(sanitize_text_field($source['phone'] ?? ''), 30),
            'email' => $this->limit(sanitize_email($source['email'] ?? ''), 190),
            'kra_pin' => strtoupper($this->limit(sanitize_text_field($source['kra_pin'] ?? ''), 11)),
            'business_address' => $this->limit(sanitize_text_field($source['business_address'] ?? ''), 300),
            'county' => $this->limit(sanitize_text_field($source['county'] ?? ''), 80),
            'town' => $this->limit(sanitize_text_field($source['town'] ?? ''), 120),
            'fulfilment' => sanitize_key($source['fulfilment'] ?? ''),
            'required_by' => $this->limit(sanitize_text_field($source['required_by'] ?? ''), 10),
            'delivery_details' => $this->limit(sanitize_textarea_field($source['delivery_details'] ?? ''), 1200),
            'notes' => $this->limit(sanitize_textarea_field($source['notes'] ?? ''), 2000),
            'consent' => sanitize_key($source['consent'] ?? ''),
            'source' => $this->normalize_source($source['quote_source'] ?? ''),
            'items' => (string) ($source['items'] ?? ''),
        ];
    }

    /**
     * Return the current quote entry source.
     */
    private function request_source()
    {
        return $this->normalize_source(wp_unslash($_GET['quote_source'] ?? ''));
    }

    /**
     * Normalize analytics context to a short allow-list.
     *
     * @param string $source Raw source.
     */
    private function normalize_source($source)
    {
        $source = sanitize_key($source);
        $allowed = ['archive', 'direct', 'footer', 'header', 'product'];

        return in_array($source, $allowed, true) ? $source : 'direct';
    }

    /**
     * Return a trusted catalogue product requested by a product-page entry point.
     */
    private function initial_product()
    {
        $product_id = absint(wp_unslash($_GET['quote_product'] ?? 0));

        if (! $product_id) {
            return null;
        }

        $product = wc_get_product($product_id);

        if (
            ! $product instanceof \WC_Product
            || 'publish' !== get_post_status($product_id)
            || ! $product->is_visible()
        ) {
            return null;
        }

        return $this->product_payload($product);
    }

    /**
     * Validate required business details and Kenyan field formats.
     *
     * @param array $values Sanitized values.
     */
    private function validate_submission($values)
    {
        $required = [
            'organisation_name' => __('Enter the organisation name.', 'uninet-core'),
            'organisation_type' => __('Select the organisation type.', 'uninet-core'),
            'full_name' => __("Enter the contact person's full name.", 'uninet-core'),
            'phone' => __('Enter a phone number.', 'uninet-core'),
            'email' => __('Enter a business email address.', 'uninet-core'),
            'kra_pin' => __('Enter the organisation KRA PIN.', 'uninet-core'),
            'business_address' => __('Enter the business address.', 'uninet-core'),
            'county' => __('Select a county.', 'uninet-core'),
            'town' => __('Enter the town.', 'uninet-core'),
            'fulfilment' => __('Choose delivery or pickup.', 'uninet-core'),
        ];

        foreach ($required as $field => $message) {
            if ('' === trim((string) $values[$field])) {
                return ['message' => $message, 'field' => $field];
            }
        }

        if (! is_email($values['email'])) {
            return ['message' => __('Enter a valid business email address.', 'uninet-core'), 'field' => 'email'];
        }

        $phone_digits = preg_replace('/\D+/', '', $values['phone']);
        if (strlen($phone_digits) < 9 || strlen($phone_digits) > 15) {
            return ['message' => __('Enter a valid phone number with 9 to 15 digits.', 'uninet-core'), 'field' => 'phone'];
        }

        if (! preg_match('/^[AP][0-9]{9}[A-Z]$/', $values['kra_pin'])) {
            return ['message' => __('KRA PIN must be 11 characters, such as P123456789Z.', 'uninet-core'), 'field' => 'kra_pin'];
        }

        if (! isset($this->organisation_types()[$values['organisation_type']])) {
            return ['message' => __('Select a valid organisation type.', 'uninet-core'), 'field' => 'organisation_type'];
        }

        if (! in_array($values['county'], $this->counties(), true)) {
            return ['message' => __('Select a valid Kenyan county.', 'uninet-core'), 'field' => 'county'];
        }

        if (! in_array($values['fulfilment'], ['delivery', 'pickup'], true)) {
            return ['message' => __('Choose delivery or pickup.', 'uninet-core'), 'field' => 'fulfilment'];
        }

        if ($values['required_by']) {
            $date = \DateTime::createFromFormat('Y-m-d', $values['required_by']);
            if (! $date || $date->format('Y-m-d') !== $values['required_by']) {
                return ['message' => __('Choose a valid required-by date.', 'uninet-core'), 'field' => 'required_by'];
            }

            if ($values['required_by'] < current_time('Y-m-d')) {
                return ['message' => __('The required-by date cannot be in the past.', 'uninet-core'), 'field' => 'required_by'];
            }
        }

        if ('yes' !== $values['consent']) {
            return ['message' => __('Confirm that Uninet may use these details to prepare and follow up the quote.', 'uninet-core'), 'field' => 'consent'];
        }

        return [];
    }

    /**
     * Render a standard text field.
     */
    private function text_field($name, $label, $placeholder, $required, $type = 'text', $autocomplete = '', $class = '', $min = '')
    {
        $id = 'uninet-quote-' . str_replace('_', '-', $name);
        $maxlength = in_array($name, ['phone', 'required_by'], true) ? 30 : 300;
        ?>
        <div class="uninet-quote-field <?php echo esc_attr($class); ?>">
            <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?><?php if ($required) : ?> <span aria-hidden="true">*</span><?php endif; ?></label>
            <input
                id="<?php echo esc_attr($id); ?>"
                type="<?php echo esc_attr($type); ?>"
                name="<?php echo esc_attr($name); ?>"
                placeholder="<?php echo esc_attr($placeholder); ?>"
                maxlength="<?php echo esc_attr($maxlength); ?>"
                <?php echo $required ? 'required' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php echo $autocomplete ? 'autocomplete="' . esc_attr($autocomplete) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php echo $min ? 'min="' . esc_attr($min) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            >
        </div>
        <?php
    }

    /**
     * Render the required KRA PIN field with visible format guidance.
     */
    private function kra_pin_field()
    {
        ?>
        <div class="uninet-quote-field">
            <label for="uninet-quote-kra-pin"><?php esc_html_e('Organisation KRA PIN', 'uninet-core'); ?> <span aria-hidden="true">*</span></label>
            <input
                id="uninet-quote-kra-pin"
                type="text"
                name="kra_pin"
                placeholder="P123456789Z"
                maxlength="11"
                pattern="[APap][0-9]{9}[A-Za-z]"
                autocomplete="off"
                aria-describedby="uninet-quote-kra-help"
                required
            >
            <span id="uninet-quote-kra-help"><?php esc_html_e('11 characters: A or P, nine digits, then a letter.', 'uninet-core'); ?></span>
        </div>
        <?php
    }

    /**
     * Render a select field.
     */
    private function select_field($name, $label, $options, $required)
    {
        $id = 'uninet-quote-' . str_replace('_', '-', $name);
        ?>
        <div class="uninet-quote-field">
            <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?><?php if ($required) : ?> <span aria-hidden="true">*</span><?php endif; ?></label>
            <select id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" <?php echo $required ? 'required' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                <option value=""><?php esc_html_e('Select an option', 'uninet-core'); ?></option>
                <?php foreach ($options as $value => $option_label) : ?>
                    <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($option_label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    /**
     * Render an optional wide textarea field.
     */
    private function textarea_field($name, $label, $placeholder)
    {
        $id = 'uninet-quote-' . str_replace('_', '-', $name);
        ?>
        <div class="uninet-quote-field uninet-quote-field--wide">
            <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label>
            <textarea id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" rows="3" maxlength="<?php echo 'notes' === $name ? '2000' : '1200'; ?>" placeholder="<?php echo esc_attr($placeholder); ?>"></textarea>
        </div>
        <?php
    }

    /**
     * Return top-level public catalogue categories.
     */
    private function categories()
    {
        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'parent' => 0,
            'hide_empty' => true,
            'number' => 10,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        if (is_wp_error($terms)) {
            return [];
        }

        return array_values(array_filter($terms, static function ($term) {
            return 'uncategorized' !== $term->slug;
        }));
    }

    /**
     * Return supported organisation types.
     */
    private function organisation_types()
    {
        return [
            'company' => __('Company', 'uninet-core'),
            'sole-proprietorship' => __('Sole proprietorship', 'uninet-core'),
            'partnership' => __('Partnership', 'uninet-core'),
            'ngo' => __('NGO or non-profit', 'uninet-core'),
            'education' => __('School or education institution', 'uninet-core'),
            'public-sector' => __('Government or public-sector organisation', 'uninet-core'),
            'other' => __('Other organisation', 'uninet-core'),
        ];
    }

    /**
     * Return Kenya's 47 counties in alphabetical order.
     */
    private function counties()
    {
        return [
            'Baringo', 'Bomet', 'Bungoma', 'Busia', 'Elgeyo-Marakwet', 'Embu', 'Garissa', 'Homa Bay',
            'Isiolo', 'Kajiado', 'Kakamega', 'Kericho', 'Kiambu', 'Kilifi', 'Kirinyaga', 'Kisii',
            'Kisumu', 'Kitui', 'Kwale', 'Laikipia', 'Lamu', 'Machakos', 'Makueni', 'Mandera',
            'Marsabit', 'Meru', 'Migori', 'Mombasa', "Murang'a", 'Nairobi', 'Nakuru', 'Nandi',
            'Narok', 'Nyamira', 'Nyandarua', 'Nyeri', 'Samburu', 'Siaya', 'Taita-Taveta', 'Tana River',
            'Tharaka-Nithi', 'Trans Nzoia', 'Turkana', 'Uasin Gishu', 'Vihiga', 'Wajir', 'West Pokot',
        ];
    }

    /**
     * Build a bounded WhatsApp handoff after the dashboard record exists.
     */
    private function whatsapp_url($reference, $values, $items, $subtotal, $unpriced_count)
    {
        $units = array_sum(array_map(static function ($item) {
            return absint($item['quantity'] ?? 0);
        }, $items));

        $lines = [
            'Hello Uninet Technologies, I submitted a quote request through your website.',
            'Reference: ' . $reference,
            'Organisation: ' . $this->limit($values['organisation_name'], 120),
            'Contact: ' . $this->limit($values['full_name'], 100),
            'Products: ' . count($items) . ' lines / ' . $units . ' units',
            'Indicative pre-tax subtotal: KSh ' . number_format((float) $subtotal, 0),
        ];

        if ($unpriced_count) {
            $lines[] = 'Products awaiting staff pricing: ' . $unpriced_count;
        }

        $lines[] = 'Please confirm availability, tax, delivery, and the final quotation.';

        return 'https://wa.me/254770313200?text=' . rawurlencode(implode("\n\n", $lines));
    }

    /**
     * Whether this browser recently submitted another quote request.
     */
    private function is_rate_limited()
    {
        return (bool) get_transient($this->rate_limit_key());
    }

    /**
     * Set a short duplicate-submission throttle.
     */
    private function set_rate_limit()
    {
        set_transient($this->rate_limit_key(), '1', MINUTE_IN_SECONDS);
    }

    /**
     * Build a privacy-preserving request fingerprint without storing the IP.
     */
    private function rate_limit_key()
    {
        $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $agent = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
        $fingerprint = hash_hmac('sha256', $ip . '|' . $agent, wp_salt('nonce'));

        return 'uninet_quote_rate_' . substr($fingerprint, 0, 32);
    }

    /**
     * Limit a sanitized value without breaking multibyte text.
     */
    private function limit($value, $length)
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length);
        }

        return substr($value, 0, $length);
    }
}
