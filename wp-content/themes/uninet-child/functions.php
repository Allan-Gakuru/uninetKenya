<?php
/**
 * Uninet Child theme functions.
 */

if (! defined('ABSPATH')) {
    exit;
}

define('UNINET_CHILD_VERSION', wp_get_theme()->get('Version'));
define('UNINET_CHILD_PATH', get_stylesheet_directory());
define('UNINET_CHILD_URL', get_stylesheet_directory_uri());

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'storefront-style',
        get_template_directory_uri() . '/style.css',
        [],
        wp_get_theme('storefront')->get('Version')
    );

    wp_enqueue_style(
        'uninet-child-style',
        get_stylesheet_uri(),
        ['storefront-style'],
        UNINET_CHILD_VERSION
    );

    wp_enqueue_style(
        'uninet-child-theme',
        UNINET_CHILD_URL . '/assets/css/theme.css',
        ['uninet-child-style'],
        UNINET_CHILD_VERSION
    );

    wp_enqueue_script(
        'uninet-child-theme',
        UNINET_CHILD_URL . '/assets/js/theme.js',
        [],
        UNINET_CHILD_VERSION,
        true
    );
}, 20);

/**
 * Replace Storefront's default commerce header with Uninet's procurement header.
 */
function uninet_child_prepare_header()
{
    remove_action('storefront_header', 'storefront_header_container', 0);
    remove_action('storefront_header', 'storefront_social_icons', 10);
    remove_action('storefront_header', 'storefront_site_branding', 20);
    remove_action('storefront_header', 'storefront_secondary_navigation', 30);
    remove_action('storefront_header', 'storefront_product_search', 40);
    remove_action('storefront_header', 'storefront_header_container_close', 41);
    remove_action('storefront_header', 'storefront_primary_navigation_wrapper', 42);
    remove_action('storefront_header', 'storefront_primary_navigation', 50);
    remove_action('storefront_header', 'storefront_header_cart', 60);
    remove_action('storefront_header', 'storefront_primary_navigation_wrapper_close', 68);

    add_action('storefront_header', 'uninet_child_render_header', 10);
}
add_action('after_setup_theme', 'uninet_child_prepare_header', 20);

/**
 * Replace Storefront's platform credit with a concise Uninet copyright.
 */
function uninet_child_prepare_footer()
{
    remove_action('storefront_footer', 'storefront_credit', 20);
    add_action('storefront_footer', 'uninet_child_render_footer_credit', 20);
}
add_action('after_setup_theme', 'uninet_child_prepare_footer', 20);

/**
 * Render the client-facing footer credit.
 */
function uninet_child_render_footer_credit()
{
    ?>
    <div class="site-info">
        &copy; <?php echo esc_html(wp_date('Y')); ?> <?php esc_html_e('Uninet Technologies. All rights reserved.', 'uninet-child'); ?>
    </div>
    <?php
}

/**
 * Render the Uninet site header.
 */
function uninet_child_render_header()
{
    ?>
    <div class="uninet-header">
        <div class="uninet-header__trust">
            <div class="col-full uninet-header__trust-inner">
                <span class="uninet-header__trust-copy"><?php esc_html_e('Business technology for Kenyan organisations', 'uninet-child'); ?></span>
                <ul class="uninet-header__trust-list" aria-label="<?php esc_attr_e('Buyer assurances', 'uninet-child'); ?>">
                    <li class="uninet-header__phone">
                        <a href="tel:+254770313200" aria-label="<?php esc_attr_e('Call Uninet Technologies on 0770 313 200', 'uninet-child'); ?>">
                            <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.69 2.8a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.33 1.84.56 2.8.69A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                            <span>0770 313 200</span>
                        </a>
                    </li>
                    <li><?php esc_html_e('6-month warranty', 'uninet-child'); ?></li>
                    <li><?php esc_html_e('Same-day Nairobi delivery may be available', 'uninet-child'); ?></li>
                </ul>
            </div>
        </div>

        <div class="uninet-header__main">
            <div class="col-full uninet-header__main-inner">
                <div class="uninet-header__brand">
                    <?php uninet_child_render_branding(); ?>
                </div>
                <div class="uninet-header__search">
                    <?php uninet_child_render_product_search(); ?>
                </div>
                <?php uninet_child_render_header_actions(); ?>
            </div>
        </div>

        <div class="uninet-header__navigation">
            <div class="col-full uninet-header__navigation-inner">
                <?php uninet_child_render_primary_navigation(); ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Return the Contact Us page URL, with a stable fallback while the page is created.
 */
function uninet_child_get_contact_url()
{
    $contact_page = get_page_by_path('contact-us');

    if ($contact_page instanceof WP_Post) {
        return get_permalink($contact_page);
    }

    return home_url('/contact-us/');
}

/**
 * Return editable social profile URLs for the header.
 */
function uninet_child_get_header_social_links()
{
    return [
        'facebook' => [
            'label' => __('Facebook', 'uninet-child'),
            'url' => get_theme_mod('uninet_header_facebook_url', 'https://www.facebook.com/UniNietTechnologies'),
        ],
        'instagram' => [
            'label' => __('Instagram', 'uninet-child'),
            'url' => get_theme_mod('uninet_header_instagram_url', 'https://www.instagram.com/uninetkenya/'),
        ],
        'x' => [
            'label' => __('X', 'uninet-child'),
            'url' => get_theme_mod('uninet_header_x_url', 'https://x.com/UninetKenya'),
        ],
    ];
}

/**
 * Return the inline icon for a supported social network.
 */
function uninet_child_get_social_icon($network)
{
    $icons = [
        'facebook' => '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M13.5 22v-8h2.7l.4-3h-3.1V9.1c0-.9.3-1.5 1.6-1.5h1.7V5a22 22 0 0 0-2.5-.1c-2.5 0-4.2 1.5-4.2 4.3V11H7.3v3h2.8v8h3.4Z"></path></svg>',
        'instagram' => '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><rect x="3" y="3" width="18" height="18" rx="5"></rect><circle cx="12" cy="12" r="4.25"></circle><circle cx="17.4" cy="6.7" r="1"></circle></svg>',
        'x' => '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M18.9 2H22l-6.8 7.8L23.2 22H17l-4.9-6.4L6.5 22H3.4l7.2-8.3L2.8 2h6.4l4.4 5.8L18.9 2Zm-1.1 17.8h1.7L8.3 4H6.5l11.3 15.8Z"></path></svg>',
    ];

    return isset($icons[$network]) ? $icons[$network] : '';
}

/**
 * Render contact and social actions beside product search.
 */
function uninet_child_render_header_actions()
{
    $social_links = uninet_child_get_header_social_links();
    ?>
    <div class="uninet-header__actions">
        <a class="uninet-header__contact" href="<?php echo esc_url(uninet_child_get_contact_url()); ?>">
            <?php esc_html_e('Contact us', 'uninet-child'); ?>
        </a>
        <nav class="uninet-header__socials" aria-label="<?php esc_attr_e('Uninet social media', 'uninet-child'); ?>">
            <?php foreach ($social_links as $network => $social_link) : ?>
                <?php if (empty($social_link['url'])) : ?>
                    <?php continue; ?>
                <?php endif; ?>
                <a
                    class="uninet-header__social uninet-header__social--<?php echo esc_attr($network); ?>"
                    href="<?php echo esc_url($social_link['url']); ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="<?php echo esc_attr($social_link['label']); ?>"
                    title="<?php echo esc_attr($social_link['label']); ?>"
                >
                    <?php echo uninet_child_get_social_icon($network); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
    <?php
}

/**
 * Add editable header social profile URLs to the Customizer.
 */
function uninet_child_customize_header($wp_customize)
{
    $wp_customize->add_section('uninet_header', [
        'title' => __('Uninet Header', 'uninet-child'),
        'priority' => 35,
    ]);

    $social_settings = [
        'facebook' => [
            'label' => __('Facebook profile URL', 'uninet-child'),
            'default' => 'https://www.facebook.com/UniNietTechnologies',
        ],
        'instagram' => [
            'label' => __('Instagram profile URL', 'uninet-child'),
            'default' => 'https://www.instagram.com/uninetkenya/',
        ],
        'x' => [
            'label' => __('X profile URL', 'uninet-child'),
            'default' => 'https://x.com/UninetKenya',
        ],
    ];

    foreach ($social_settings as $network => $setting) {
        $setting_id = 'uninet_header_' . $network . '_url';

        $wp_customize->add_setting($setting_id, [
            'default' => $setting['default'],
            'sanitize_callback' => 'esc_url_raw',
        ]);

        $wp_customize->add_control($setting_id, [
            'type' => 'url',
            'section' => 'uninet_header',
            'label' => $setting['label'],
            'input_attrs' => [
                'placeholder' => 'https://',
            ],
        ]);
    }
}
add_action('customize_register', 'uninet_child_customize_header');

/**
 * Render the configured WordPress logo and site title fallback.
 */
function uninet_child_render_branding()
{
    if (function_exists('storefront_site_branding')) {
        storefront_site_branding();
        return;
    }

    if (has_custom_logo()) {
        the_custom_logo();
        return;
    }

    echo '<a class="uninet-header__site-title" href="' . esc_url(home_url('/')) . '" rel="home">';
    echo esc_html(get_bloginfo('name'));
    echo '</a>';
}

/**
 * Render FiboSearch when available, with a native product-search fallback.
 */
function uninet_child_render_product_search()
{
    if (shortcode_exists('fibosearch')) {
        echo do_shortcode('[fibosearch]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        return;
    }

    ?>
    <form role="search" method="get" class="uninet-product-search" action="<?php echo esc_url(home_url('/')); ?>">
        <label class="screen-reader-text" for="uninet-product-search-field"><?php esc_html_e('Search products', 'uninet-child'); ?></label>
        <input
            type="search"
            id="uninet-product-search-field"
            class="uninet-product-search__field"
            placeholder="<?php esc_attr_e('Search laptops, printers, CCTV and more', 'uninet-child'); ?>"
            value="<?php echo get_search_query(); ?>"
            name="s"
            autocomplete="off"
        />
        <button type="submit" class="uninet-product-search__submit" aria-label="<?php esc_attr_e('Search products', 'uninet-child'); ?>">
            <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-3.8-3.8"></path>
            </svg>
        </button>
        <input type="hidden" name="post_type" value="product" />
    </form>
    <?php
}

/**
 * Return desktop mega-menu content for a product category menu item.
 */
function uninet_child_get_mega_menu_config($item)
{
    static $categories = null;

    if (null === $categories) {
        $categories = [
            'laptops' => [
                'title' => __('Laptops for every business role', 'uninet-child'),
                'description' => __('Compare practical specifications for field teams, office staff, executives, and students, then request staff confirmation before payment.', 'uninet-child'),
                'benefits' => [
                    __('Business-grade options from trusted brands', 'uninet-child'),
                    __('Clear processor, memory, storage, and display details', 'uninet-child'),
                    __('Local advice and after-sales support', 'uninet-child'),
                ],
                'cta' => __('Shop all laptops', 'uninet-child'),
                'image' => 'laptops.webp',
                'alt' => __('Business laptop, docking station, and desk accessories in a modern office.', 'uninet-child'),
            ],
            'desktops' => [
                'title' => __('Reliable desktops for productive teams', 'uninet-child'),
                'description' => __('Choose complete office PCs, all-in-one computers, and performance workstations suited to the role, desk space, and workload.', 'uninet-child'),
                'benefits' => [
                    __('Office-ready configurations for everyday work', 'uninet-child'),
                    __('Expandable systems for demanding workloads', 'uninet-child'),
                    __('Staff confirmation before payment', 'uninet-child'),
                ],
                'cta' => __('Shop all desktops', 'uninet-child'),
                'image' => 'desktops.webp',
                'alt' => __('Desktop computer, monitor, keyboard, and mouse prepared for an office workstation.', 'uninet-child'),
            ],
            'monitors' => [
                'title' => __('Displays that fit the way your team works', 'uninet-child'),
                'description' => __('Compare screen size, resolution, ports, and multi-display suitability before selecting monitors for focused business work.', 'uninet-child'),
                'benefits' => [
                    __('Options for single and multi-monitor desks', 'uninet-child'),
                    __('Port compatibility checked before ordering', 'uninet-child'),
                    __('Practical guidance for office environments', 'uninet-child'),
                ],
                'cta' => __('Shop all monitors', 'uninet-child'),
                'image' => 'monitors.webp',
                'alt' => __('Dual business monitors arranged on a clean office desk.', 'uninet-child'),
            ],
            'networking' => [
                'title' => __('Build a dependable business network', 'uninet-child'),
                'description' => __('Source the switching, routing, wireless, and cabling equipment needed to keep teams and business systems connected.', 'uninet-child'),
                'benefits' => [
                    __('Equipment matched to users and site coverage', 'uninet-child'),
                    __('Structured cabling and expansion considerations', 'uninet-child'),
                    __('Availability confirmed by technical staff', 'uninet-child'),
                ],
                'cta' => __('Shop networking equipment', 'uninet-child'),
                'image' => 'networking.webp',
                'alt' => __('Network switch, router, and organised Ethernet connections for a business network.', 'uninet-child'),
            ],
            'printers' => [
                'title' => __('Equip the office for everyday output', 'uninet-child'),
                'description' => __('Compare printers, multifunction devices, office equipment, and power backup options around the volume and workflow your team handles.', 'uninet-child'),
                'benefits' => [
                    __('Print, scan, and copy requirements considered', 'uninet-child'),
                    __('Power backup options for essential equipment', 'uninet-child'),
                    __('Business invoice details confirmed by staff', 'uninet-child'),
                ],
                'cta' => __('Shop printers and office equipment', 'uninet-child'),
                'image' => 'printers-office.webp',
                'alt' => __('Multifunction office printer and compact power backup unit on a workspace.', 'uninet-child'),
            ],
            'accessories-and-cables' => [
                'title' => __('Complete every workstation', 'uninet-child'),
                'description' => __('Finish business setups with keyboards, mice, stands, storage, flash drives, adapters, and the right cables for each device.', 'uninet-child'),
                'benefits' => [
                    __('Practical accessories for comfort and productivity', 'uninet-child'),
                    __('Storage and connectivity replacements', 'uninet-child'),
                    __('Compatibility checked before confirmation', 'uninet-child'),
                ],
                'cta' => __('Shop accessories and cables', 'uninet-child'),
                'image' => 'accessories.webp',
                'alt' => __('Keyboard, mouse, storage devices, cables, and laptop stand arranged for business use.', 'uninet-child'),
            ],
            'security-products' => [
                'title' => __('Protect and monitor your premises', 'uninet-child'),
                'description' => __('Plan CCTV and access control around the site, recording needs, entry points, and future expansion instead of buying devices in isolation.', 'uninet-child'),
                'benefits' => [
                    __('Camera and recorder compatibility considered', 'uninet-child'),
                    __('Access control for offices and business sites', 'uninet-child'),
                    __('Staff confirms component availability', 'uninet-child'),
                ],
                'cta' => __('Shop CCTV and security', 'uninet-child'),
                'image' => 'cctv-security.webp',
                'alt' => __('CCTV cameras, digital recorder, and access control keypad for business premises.', 'uninet-child'),
            ],
        ];
    }

    if ('taxonomy' !== $item->type || 'product_cat' !== $item->object) {
        return null;
    }

    $term = get_term((int) $item->object_id, 'product_cat');

    if (! $term || is_wp_error($term)) {
        return null;
    }

    return $categories[$term->slug] ?? null;
}

/**
 * Render the descriptive and image portion of a desktop mega menu.
 */
function uninet_child_get_mega_menu_feature($item, $config)
{
    $label = sprintf(__('%s category overview', 'uninet-child'), $item->title);
    $image_url = UNINET_CHILD_URL . '/assets/images/mega-menu/' . $config['image'];

    ob_start();
    ?>
    <section class="uninet-mega-feature" aria-label="<?php echo esc_attr($label); ?>">
        <div class="uninet-mega-feature__copy">
            <h3><?php echo esc_html($config['title']); ?></h3>
            <p><?php echo esc_html($config['description']); ?></p>
            <div class="uninet-mega-feature__benefits" aria-label="<?php esc_attr_e('Buying considerations', 'uninet-child'); ?>">
                <?php foreach ($config['benefits'] as $benefit) : ?>
                    <span><?php echo esc_html($benefit); ?></span>
                <?php endforeach; ?>
            </div>
            <a class="uninet-mega-feature__cta" href="<?php echo esc_url($item->url); ?>">
                <?php echo esc_html($config['cta']); ?>
            </a>
        </div>
        <figure class="uninet-mega-feature__media">
            <img
                src="<?php echo esc_url($image_url); ?>"
                alt="<?php echo esc_attr($config['alt']); ?>"
                width="960"
                height="640"
                loading="lazy"
                decoding="async"
            />
        </figure>
    </section>
    <?php

    return (string) ob_get_clean();
}

if (class_exists('Walker_Nav_Menu') && ! class_exists('Uninet_Child_Mega_Menu_Walker')) {
    /**
     * Add rich category panels only to the desktop primary navigation.
     */
    class Uninet_Child_Mega_Menu_Walker extends Walker_Nav_Menu
    {
        public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
        {
            $config = 0 === $depth ? uninet_child_get_mega_menu_config($item) : null;

            if ($config) {
                $item->classes[] = 'uninet-mega-item';

                if (! in_array('menu-item-has-children', $item->classes, true)) {
                    $item->classes[] = 'uninet-mega-item--solo';
                }
            }

            parent::start_el($output, $item, $depth, $args, $id);
        }

        public function end_el(&$output, $item, $depth = 0, $args = null)
        {
            if (0 === $depth) {
                $config = uninet_child_get_mega_menu_config($item);

                if ($config) {
                    $output .= uninet_child_get_mega_menu_feature($item, $config);
                }
            }

            parent::end_el($output, $item, $depth, $args);
        }
    }
}

add_filter('nav_menu_link_attributes', function ($atts, $item, $args, $depth) {
    if (
        0 !== $depth
        || ! is_object($args)
        || empty($args->walker)
        || ! ($args->walker instanceof Uninet_Child_Mega_Menu_Walker)
    ) {
        return $atts;
    }

    if (! uninet_child_get_mega_menu_config($item)) {
        return $atts;
    }

    $atts['class'] = trim(($atts['class'] ?? '') . ' uninet-mega-trigger');
    $atts['aria-haspopup'] = 'true';
    $atts['aria-expanded'] = 'false';

    return $atts;
}, 10, 4);

/**
 * Add rich panels only to Storefront's desktop primary menu.
 */
function uninet_child_add_mega_menu_walker($args)
{
    if (
        'primary' === ($args['theme_location'] ?? '')
        && class_exists('Uninet_Child_Mega_Menu_Walker')
    ) {
        $args['walker'] = new Uninet_Child_Mega_Menu_Walker();
        $args['depth'] = 2;
    }

    return $args;
}
add_filter('wp_nav_menu_args', 'uninet_child_add_mega_menu_walker', 20);

/**
 * Keep Storefront's accessible handheld navigation behavior intact.
 */
function uninet_child_render_primary_navigation()
{
    if (function_exists('storefront_primary_navigation')) {
        storefront_primary_navigation();
        return;
    }

    wp_nav_menu(
        [
            'theme_location' => 'primary',
            'container' => 'nav',
            'container_class' => 'main-navigation',
            'container_aria_label' => __('Primary navigation', 'uninet-child'),
            'fallback_cb' => false,
            'depth' => 2,
        ]
    );
}
