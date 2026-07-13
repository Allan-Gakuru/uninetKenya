<?php
/**
 * Plugin Name: Uninet Core
 * Description: Custom WooCommerce functionality for Uninet Kenya.
 * Version: 0.2.0
 * Author: Uninet Kenya
 * Text Domain: uninet-core
 */

if (! defined('ABSPATH')) {
    exit;
}

define('UNINET_CORE_VERSION', '0.2.0');
define('UNINET_CORE_FILE', __FILE__);
define('UNINET_CORE_PATH', plugin_dir_path(__FILE__));
define('UNINET_CORE_URL', plugin_dir_url(__FILE__));

$uninet_core_files = [
    'includes/Helpers/Sanitization.php',
    'includes/Admin/Settings.php',
    'includes/Helpers/Assets.php',
    'includes/CallToOrder/Form.php',
    'includes/CallToOrder/Validation.php',
    'includes/CallToOrder/Metadata.php',
    'includes/CallToOrder/OrderFactory.php',
    'includes/CallToOrder/AjaxController.php',
    'includes/WooCommerce/ProductCards.php',
    'includes/WooCommerce/ProductPage.php',
    'includes/WooCommerce/ProductArchives.php',
    'includes/WooCommerce/CartCheckoutVisibility.php',
    'includes/Tracking/Events.php',
    'includes/Plugin.php',
];

foreach ($uninet_core_files as $uninet_core_file) {
    require_once UNINET_CORE_PATH . $uninet_core_file;
}

add_action('plugins_loaded', ['Uninet\\Core\\Plugin', 'init']);
