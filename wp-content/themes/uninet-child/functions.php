<?php
/**
 * Uninet Child theme functions.
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'uninet-child-style',
        get_stylesheet_uri(),
        [],
        wp_get_theme()->get('Version')
    );
});
