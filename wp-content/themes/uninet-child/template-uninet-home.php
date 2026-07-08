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
$hero_image_url = $hero_image_id ? wp_get_attachment_image_url($hero_image_id, 'large') : get_theme_file_uri('assets/images/uninet-home-hero.jpg');

$outcome_paths = [
    [
        'label' => __('Team devices', 'uninet-child'),
        'title' => __('Outfit productive workstations', 'uninet-child'),
        'summary' => __('Match employees with the right computing setup for their role, desk, budget, and performance needs.', 'uninet-child'),
        'image' => get_theme_file_uri('assets/images/outcome-workstations.jpg'),
        'image_alt' => __('Laptops, desktop computers, and monitors arranged for a business workstation setup.', 'uninet-child'),
        'categories' => [
            [
                'name' => __('Laptops', 'uninet-child'),
                'url' => $find_category_url(['laptops', 'laptop'], __('Laptops', 'uninet-child')),
                'description' => __('Portable machines for field work, office staff, executives, and student users.', 'uninet-child'),
                'items' => [
                    ['label' => __('Shop laptops', 'uninet-child'), 'url' => $find_category_url(['laptops', 'laptop'], __('Laptops', 'uninet-child'))],
                ],
            ],
            [
                'name' => __('Desktops', 'uninet-child'),
                'url' => $find_category_url(['desktops', 'desktop'], __('Desktops', 'uninet-child')),
                'description' => __('Fixed workstations for offices, front desks, labs, and performance-heavy desks.', 'uninet-child'),
                'items' => [
                    ['label' => __('Shop desktops', 'uninet-child'), 'url' => $find_category_url(['desktops', 'desktop'], __('Desktops', 'uninet-child'))],
                ],
            ],
            [
                'name' => __('Monitors', 'uninet-child'),
                'url' => $find_category_url(['monitors', 'monitor'], __('Monitors', 'uninet-child')),
                'description' => __('Screens selected by size, resolution, port compatibility, and multi-display productivity.', 'uninet-child'),
                'items' => [
                    ['label' => __('Shop monitors', 'uninet-child'), 'url' => $find_category_url(['monitors', 'monitor'], __('Monitors', 'uninet-child'))],
                ],
            ],
        ],
    ],
    [
        'label' => __('Desk essentials', 'uninet-child'),
        'title' => __('Complete everyday office setups', 'uninet-child'),
        'summary' => __('Finish workstations with the accessories staff touch every day, from input devices to ergonomic desk support.', 'uninet-child'),
        'image' => get_theme_file_uri('assets/images/outcome-office-essentials.jpg'),
        'image_alt' => __('Office desk accessories including a keyboard, mouse, laptop stand, cables, and storage devices.', 'uninet-child'),
        'categories' => [
            [
                'name' => __('Accessories & Cables', 'uninet-child'),
                'url' => $find_category_url(['accessories-cables', 'accessories-and-cables', 'accessories-and-kibbles', 'cables-accessories', 'accessories'], __('Accessories & Cables', 'uninet-child')),
                'description' => __('Practical add-ons for cleaner desks, better comfort, and faster replacement procurement.', 'uninet-child'),
                'items' => [
                    ['label' => __('Shop accessories', 'uninet-child'), 'url' => $find_category_url(['accessories-cables', 'accessories-and-cables', 'accessories-and-kibbles', 'cables-accessories', 'accessories'], __('Accessories & Cables', 'uninet-child'))],
                ],
            ],
        ],
    ],
    [
        'label' => __('Premises security', 'uninet-child'),
        'title' => __('Secure and monitor your premises', 'uninet-child'),
        'summary' => __('Plan CCTV and access control around the site, not just the device list, so staff can confirm fit before payment.', 'uninet-child'),
        'image' => get_theme_file_uri('assets/images/outcome-security.jpg'),
        'image_alt' => __('CCTV cameras, access control hardware, network equipment, and security cables on an office desk.', 'uninet-child'),
        'categories' => [
            [
                'name' => __('CCTV & Security', 'uninet-child'),
                'url' => $find_category_url(['cctv-security', 'cctv-and-security', 'security'], __('CCTV & Security', 'uninet-child')),
                'description' => __('Security equipment for offices, shops, schools, warehouses, gates, and multi-room sites.', 'uninet-child'),
                'items' => [
                    ['label' => __('Shop CCTV & Security', 'uninet-child'), 'url' => $find_category_url(['cctv-security', 'cctv-and-security', 'security'], __('CCTV & Security', 'uninet-child'))],
                ],
            ],
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
    <section class="uninet-home-hero" style="--uninet-home-hero-image: url('<?php echo esc_url($hero_image_url); ?>');" aria-labelledby="uninet-home-title">
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
        </div>
    </section>

    <section id="uninet-home-use-cases" class="uninet-home-section uninet-home-section--outcomes" aria-labelledby="uninet-use-cases-title">
        <div class="uninet-container">
            <div class="uninet-outcome-header">
                <div>
                    <h2 id="uninet-use-cases-title"><?php esc_html_e('Start with the business outcome.', 'uninet-child'); ?></h2>
                    <p><?php esc_html_e('Use the procurement map below to move from business need to the right product family, then compare real stock on the category pages.', 'uninet-child'); ?></p>
                </div>
                <a class="button uninet-outcome-header__cta" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('Browse all products', 'uninet-child'); ?></a>
            </div>

            <div class="uninet-outcome-map">
                <?php foreach ($outcome_paths as $path) : ?>
                    <article class="uninet-outcome-lane">
                        <?php if (! empty($path['image'])) : ?>
                            <figure class="uninet-outcome-lane__media">
                                <img src="<?php echo esc_url($path['image']); ?>" alt="<?php echo esc_attr($path['image_alt'] ?? ''); ?>" width="1680" height="945" loading="lazy" decoding="async" />
                            </figure>
                        <?php endif; ?>

                        <div class="uninet-outcome-lane__intro">
                            <span><?php echo esc_html($path['label']); ?></span>
                            <h3><?php echo esc_html($path['title']); ?></h3>
                            <p><?php echo esc_html($path['summary']); ?></p>
                        </div>

                        <?php foreach ($path['categories'] as $category) : ?>
                            <div class="uninet-outcome-category">
                                <a class="uninet-outcome-category__title" href="<?php echo esc_url($category['url']); ?>"><?php echo esc_html($category['name']); ?></a>
                                <p><?php echo esc_html($category['description']); ?></p>

                                <div class="uninet-outcome-items">
                                    <?php foreach ($category['items'] as $item) : ?>
                                        <?php if (! empty($item['url'])) : ?>
                                            <a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></a>
                                        <?php else : ?>
                                            <span><?php echo esc_html($item['label']); ?></span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
