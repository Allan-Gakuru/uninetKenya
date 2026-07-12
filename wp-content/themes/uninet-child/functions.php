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
 * Keep Storefront's accessible mobile navigation behavior with the assigned menu.
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
        ]
    );
}
