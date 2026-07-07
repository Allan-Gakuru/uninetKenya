<?php
/**
 * Cart and checkout visibility shell.
 *
 * @package UninetCore
 */

namespace Uninet\Core\WooCommerce;

if (! defined('ABSPATH')) {
    exit;
}

final class CartCheckoutVisibility
{
    /**
     * Register hooks.
     */
    public function register()
    {
        add_action('init', [$this, 'prepare_hooks']);
    }

    /**
     * Prepare cart/checkout visibility changes.
     */
    public function prepare_hooks()
    {
        // Cart/checkout hiding will be implemented after product UI behavior is finalized.
    }
}
