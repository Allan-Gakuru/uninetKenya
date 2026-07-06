<?php
/**
 * Plugin Name: Uninet Core
 * Description: Custom WooCommerce functionality for Uninet Kenya.
 * Version: 0.1.0
 * Author: Uninet Kenya
 * Text Domain: uninet-core
 */

if (! defined('ABSPATH')) {
    exit;
}

define('UNINET_CORE_VERSION', '0.1.0');
define('UNINET_CORE_FILE', __FILE__);
define('UNINET_CORE_PATH', plugin_dir_path(__FILE__));
define('UNINET_CORE_URL', plugin_dir_url(__FILE__));

add_action('plugins_loaded', function () {
    if (! class_exists('WooCommerce')) {
        return;
    }

    // WooCommerce-specific hooks will be registered here.
});
