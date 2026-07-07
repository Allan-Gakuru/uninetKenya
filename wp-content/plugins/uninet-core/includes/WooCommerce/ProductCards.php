<?php
/**
 * WooCommerce product card hooks.
 *
 * @package UninetCore
 */

namespace Uninet\Core\WooCommerce;

if (! defined('ABSPATH')) {
    exit;
}

final class ProductCards
{
    /**
     * Register hooks.
     */
    public function register()
    {
        add_action('init', [$this, 'prepare_hooks']);
    }

    /**
     * Prepare product-card hook changes.
     */
    public function prepare_hooks()
    {
        // Product card behavior will be implemented in the WooCommerce UI phase.
    }
}
