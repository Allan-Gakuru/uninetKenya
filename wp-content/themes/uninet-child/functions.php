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
