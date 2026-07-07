<?php
/**
 * Template Name: Uninet Homepage
 * Template Post Type: page
 *
 * @package UninetChild
 */

if (! defined('ABSPATH')) {
    exit;
}

$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');

$find_category_url = static function (array $slugs, $name = '') use ($shop_url) {
    if (! taxonomy_exists('product_cat')) {
        return $shop_url;
    }

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', sanitize_title($slug), 'product_cat');

        if ($term && ! is_wp_error($term)) {
            $link = get_term_link($term);

            return is_wp_error($link) ? $shop_url : $link;
        }
    }

    if ($name) {
        $term = get_term_by('name', $name, 'product_cat');

        if ($term && ! is_wp_error($term)) {
            $link = get_term_link($term);

            return is_wp_error($link) ? $shop_url : $link;
        }
    }

    return $shop_url;
};

$get_products = static function ($limit = 4, $featured_first = false) {
    if (! function_exists('wc_get_products')) {
        return [];
    }

    $products = [];

    if ($featured_first) {
        $products = wc_get_products([
            'status' => 'publish',
            'limit' => $limit,
            'featured' => true,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
    }

    if (count($products) < $limit) {
        $existing_ids = array_map(static function ($product) {
            return $product instanceof WC_Product ? $product->get_id() : 0;
        }, $products);

        $recent_products = wc_get_products([
            'status' => 'publish',
            'limit' => $limit - count($products),
            'exclude' => array_filter($existing_ids),
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $products = array_merge($products, $recent_products);
    }

    return array_filter(array_slice($products, 0, $limit), static function ($product) {
        return $product instanceof WC_Product;
    });
};

$render_product_loop = static function (array $products) {
    if (empty($products) || ! function_exists('wc_get_template_part')) {
        return;
    }

    global $post, $product;

    $previous_post = $post;
    $previous_product = $product;

    woocommerce_product_loop_start();

    foreach ($products as $loop_product) {
        $post_object = get_post($loop_product->get_id());

        if (! $post_object) {
            continue;
        }

        $post = $post_object; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        $product = $loop_product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

        setup_postdata($post_object);
        wc_get_template_part('content', 'product');
    }

    woocommerce_product_loop_end();
    wp_reset_postdata();

    $post = $previous_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
    $product = $previous_product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
};

$featured_products = $get_products(4, true);
$hero_image_id = get_post_thumbnail_id();
$hero_image_url = $hero_image_id ? wp_get_attachment_image_url($hero_image_id, 'large') : 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=1200&q=80';
$hero_image_srcset = $hero_image_id ? wp_get_attachment_image_srcset($hero_image_id, 'large') : '';
$hero_image_sizes = '(min-width: 900px) 38vw, 100vw';
$hero_image_alt = $hero_image_id ? get_post_meta($hero_image_id, '_wp_attachment_image_alt', true) : '';
$hero_image_alt = $hero_image_alt ? $hero_image_alt : __('Business laptops and technology devices arranged for office procurement.', 'uninet-child');

$use_cases = [
    [
        'title' => __('Empower your team', 'uninet-child'),
        'body' => __('Business laptops, desktops, and monitors for productive staff, reliable office workstations, and focused day-to-day operations.', 'uninet-child'),
        'links' => [
            ['label' => __('Laptops', 'uninet-child'), 'url' => $find_category_url(['laptops', 'laptop'], __('Laptops', 'uninet-child'))],
            ['label' => __('Desktops', 'uninet-child'), 'url' => $find_category_url(['desktops', 'desktop'], __('Desktops', 'uninet-child'))],
            ['label' => __('Monitors', 'uninet-child'), 'url' => $find_category_url(['monitors', 'monitor'], __('Monitors', 'uninet-child'))],
        ],
    ],
    [
        'title' => __('Equip your office', 'uninet-child'),
        'body' => __('Printers, office equipment, and power backup options that keep paperwork, service desks, and daily operations moving.', 'uninet-child'),
        'links' => [
            ['label' => __('Printers & Office', 'uninet-child'), 'url' => $find_category_url(['printers-office', 'printers-and-office', 'printers-office-equipment', 'printers'], __('Printers & Office', 'uninet-child'))],
            ['label' => __('Power backup', 'uninet-child'), 'url' => $find_category_url(['power-backup', 'ups'], __('Power backup', 'uninet-child'))],
            ['label' => __('Accessories', 'uninet-child'), 'url' => $find_category_url(['accessories', 'cables-accessories'], __('Accessories', 'uninet-child'))],
        ],
    ],
    [
        'title' => __('Secure your premises', 'uninet-child'),
        'body' => __('CCTV, access control, and security equipment for shops, offices, schools, warehouses, and multi-site teams.', 'uninet-child'),
        'links' => [
            ['label' => __('CCTV & Security', 'uninet-child'), 'url' => $find_category_url(['cctv-security', 'cctv-and-security', 'security'], __('CCTV & Security', 'uninet-child'))],
            ['label' => __('Networking', 'uninet-child'), 'url' => $find_category_url(['networking', 'networking-equipment'], __('Networking', 'uninet-child'))],
        ],
    ],
    [
        'title' => __('Procure with confidence', 'uninet-child'),
        'body' => __('Use Uninet as a practical IT partner: confirm fit, availability, warranty, delivery, tax, and invoice details before payment.', 'uninet-child'),
        'links' => [
            ['label' => __('Browse all products', 'uninet-child'), 'url' => $shop_url],
            ['label' => __('View bundles', 'uninet-child'), 'url' => $find_category_url(['bundles', 'product-bundles'], __('Bundles', 'uninet-child'))],
        ],
    ],
];

$category_links = [
    ['label' => __('Laptops', 'uninet-child'), 'url' => $find_category_url(['laptops', 'laptop'], __('Laptops', 'uninet-child'))],
    ['label' => __('Desktops', 'uninet-child'), 'url' => $find_category_url(['desktops', 'desktop'], __('Desktops', 'uninet-child'))],
    ['label' => __('Monitors', 'uninet-child'), 'url' => $find_category_url(['monitors', 'monitor'], __('Monitors', 'uninet-child'))],
    ['label' => __('CCTV & Security', 'uninet-child'), 'url' => $find_category_url(['cctv-security', 'cctv-and-security', 'security'], __('CCTV & Security', 'uninet-child'))],
    ['label' => __('Networking', 'uninet-child'), 'url' => $find_category_url(['networking', 'networking-equipment'], __('Networking', 'uninet-child'))],
    ['label' => __('Printers & Office', 'uninet-child'), 'url' => $find_category_url(['printers-office', 'printers-and-office', 'printers-office-equipment', 'printers'], __('Printers & Office', 'uninet-child'))],
    ['label' => __('Accessories', 'uninet-child'), 'url' => $find_category_url(['accessories', 'cables-accessories'], __('Accessories', 'uninet-child'))],
];

get_header();
?>

<main id="primary" class="site-main uninet-home">
    <section class="uninet-home-hero" aria-labelledby="uninet-home-title">
        <div class="uninet-container uninet-home-hero__inner">
            <div class="uninet-home-hero__copy">
                <p class="uninet-home-hero__brand"><?php esc_html_e('Uninet Technologies', 'uninet-child'); ?></p>
                <h1 id="uninet-home-title"><?php esc_html_e('Business technology procurement for Kenyan companies.', 'uninet-child'); ?></h1>
                <p><?php esc_html_e('Source laptops, desktops, monitors, CCTV, networking equipment, printers, accessories, and office-ready bundles with staff confirmation before payment.', 'uninet-child'); ?></p>
                <div class="uninet-home-hero__actions">
                    <a class="button" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('Browse products', 'uninet-child'); ?></a>
                    <a class="uninet-home-link-button" href="#uninet-home-use-cases"><?php esc_html_e('Shop by business need', 'uninet-child'); ?></a>
                </div>
            </div>

            <figure class="uninet-home-hero__media">
                <img
                    src="<?php echo esc_url($hero_image_url); ?>"
                    <?php if ($hero_image_srcset) : ?>
                        srcset="<?php echo esc_attr($hero_image_srcset); ?>"
                        sizes="<?php echo esc_attr($hero_image_sizes); ?>"
                    <?php endif; ?>
                    alt="<?php echo esc_attr($hero_image_alt); ?>"
                    loading="eager"
                    decoding="async"
                    fetchpriority="high"
                />
            </figure>
        </div>
    </section>

    <section id="uninet-home-use-cases" class="uninet-home-section" aria-labelledby="uninet-use-cases-title">
        <div class="uninet-container">
            <div class="uninet-home-section__header">
                <h2 id="uninet-use-cases-title"><?php esc_html_e('Start with the business outcome.', 'uninet-child'); ?></h2>
                <p><?php esc_html_e('Choose the buying path that matches what your team needs to get done, then compare real products inside each category.', 'uninet-child'); ?></p>
            </div>

            <div class="uninet-use-case-grid">
                <?php foreach ($use_cases as $use_case) : ?>
                    <article class="uninet-use-case">
                        <h3><?php echo esc_html($use_case['title']); ?></h3>
                        <p><?php echo esc_html($use_case['body']); ?></p>
                        <div class="uninet-use-case__links">
                            <?php foreach ($use_case['links'] as $link) : ?>
                                <a href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a>
                            <?php endforeach; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php if (! empty($featured_products)) : ?>
        <section class="uninet-home-section uninet-home-section--soft" aria-labelledby="uninet-featured-title">
            <div class="uninet-container">
                <div class="uninet-home-section__header uninet-home-section__header--row">
                    <div>
                        <h2 id="uninet-featured-title"><?php esc_html_e('Featured products and bundles', 'uninet-child'); ?></h2>
                        <p><?php esc_html_e('Review practical options, then request a call so staff can confirm stock, tax, delivery, and invoice details.', 'uninet-child'); ?></p>
                    </div>
                    <a class="uninet-home-link-button uninet-home-link-button--dark" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('View all products', 'uninet-child'); ?></a>
                </div>

                <div class="uninet-home-products">
                    <?php $render_product_loop($featured_products); ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="uninet-home-section" aria-labelledby="uninet-categories-title">
        <div class="uninet-container">
            <div class="uninet-home-section__header uninet-home-section__header--row">
                <div>
                    <h2 id="uninet-categories-title"><?php esc_html_e('Browse core product categories.', 'uninet-child'); ?></h2>
                    <p><?php esc_html_e('Each category page is structured for comparison, product search, and fast call-to-order requests.', 'uninet-child'); ?></p>
                </div>
            </div>

            <nav class="uninet-home-category-grid" aria-label="<?php esc_attr_e('Product categories', 'uninet-child'); ?>">
                <?php foreach ($category_links as $category_link) : ?>
                    <a href="<?php echo esc_url($category_link['url']); ?>">
                        <span><?php echo esc_html($category_link['label']); ?></span>
                        <strong><?php esc_html_e('View products', 'uninet-child'); ?></strong>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </section>

    <section class="uninet-home-procurement" aria-labelledby="uninet-procurement-title">
        <div class="uninet-container uninet-home-procurement__inner">
            <div>
                <h2 id="uninet-procurement-title"><?php esc_html_e('A buying flow built for business decisions.', 'uninet-child'); ?></h2>
                <p><?php esc_html_e('Instead of rushing buyers through a cart, Uninet captures the request, reserves the conversation, and confirms the practical details before payment.', 'uninet-child'); ?></p>
            </div>
            <ol>
                <li>
                    <strong><?php esc_html_e('Choose one product', 'uninet-child'); ?></strong>
                    <span><?php esc_html_e('Open the product page and review the visible specifications.', 'uninet-child'); ?></span>
                </li>
                <li>
                    <strong><?php esc_html_e('Submit order details', 'uninet-child'); ?></strong>
                    <span><?php esc_html_e('Share contact, quantity, location, and invoice details where needed.', 'uninet-child'); ?></span>
                </li>
                <li>
                    <strong><?php esc_html_e('Confirm before payment', 'uninet-child'); ?></strong>
                    <span><?php esc_html_e('Staff verifies availability, tax, delivery, warranty, and final invoice total.', 'uninet-child'); ?></span>
                </li>
            </ol>
        </div>
    </section>

    <section class="uninet-home-section" aria-labelledby="uninet-trust-title">
        <div class="uninet-container">
            <div class="uninet-home-section__header">
                <h2 id="uninet-trust-title"><?php esc_html_e('The details business buyers ask about first.', 'uninet-child'); ?></h2>
            </div>

            <div class="uninet-trust-grid">
                <div>
                    <strong><?php esc_html_e('Six-month warranty', 'uninet-child'); ?></strong>
                    <p><?php esc_html_e('Covered for component failure, excluding physical and water damage.', 'uninet-child'); ?></p>
                </div>
                <div>
                    <strong><?php esc_html_e('Nairobi delivery support', 'uninet-child'); ?></strong>
                    <p><?php esc_html_e('Same-day delivery may be available within Nairobi and the metropolitan area after confirmation.', 'uninet-child'); ?></p>
                </div>
                <div>
                    <strong><?php esc_html_e('Business invoicing', 'uninet-child'); ?></strong>
                    <p><?php esc_html_e('Staff confirms final tax and e-TIMS invoice totals before payment.', 'uninet-child'); ?></p>
                </div>
                <div>
                    <strong><?php esc_html_e('Flexible payment options', 'uninet-child'); ?></strong>
                    <p><?php esc_html_e('M-Pesa, bank transfer, and approved payment options are supported. Cheques are not accepted.', 'uninet-child'); ?></p>
                </div>
            </div>
        </div>
    </section>

    <section class="uninet-home-final" aria-labelledby="uninet-home-final-title">
        <div class="uninet-container uninet-home-final__inner">
            <h2 id="uninet-home-final-title"><?php esc_html_e('Ready to compare business-ready technology?', 'uninet-child'); ?></h2>
            <p><?php esc_html_e('Browse live products, open the detail page, and use Call to Order when you are ready for staff confirmation.', 'uninet-child'); ?></p>
            <a class="button" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('Browse products', 'uninet-child'); ?></a>
        </div>
    </section>
</main>

<?php
get_footer();
