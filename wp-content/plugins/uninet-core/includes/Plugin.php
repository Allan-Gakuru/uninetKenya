<?php
/**
 * Main plugin coordinator.
 *
 * @package UninetCore
 */

namespace Uninet\Core;

use Uninet\Core\Admin\Settings;
use Uninet\Core\CallToOrder\AjaxController;
use Uninet\Core\CallToOrder\Form;
use Uninet\Core\CallToOrder\Metadata;
use Uninet\Core\CallToOrder\OrderFactory;
use Uninet\Core\CallToOrder\Validation;
use Uninet\Core\Contact\Form as ContactForm;
use Uninet\Core\Contact\InquiryPostType;
use Uninet\Core\Helpers\Assets;
use Uninet\Core\Quote\Builder as QuoteBuilder;
use Uninet\Core\Quote\RequestPostType as QuoteRequestPostType;
use Uninet\Core\Setup\SitePages;
use Uninet\Core\Tracking\Events;
use Uninet\Core\WooCommerce\CartCheckoutVisibility;
use Uninet\Core\WooCommerce\ProductArchives;
use Uninet\Core\WooCommerce\ProductCards;
use Uninet\Core\WooCommerce\ProductPage;

if (! defined('ABSPATH')) {
    exit;
}

final class Plugin
{
    /**
     * Plugin singleton.
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Boot the plugin once WordPress plugins are loaded.
     */
    public static function init()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Register modules.
     */
    private function __construct()
    {
        $settings = new Settings();
        $settings->register();

        $assets = new Assets();
        $assets->register();

        $events = new Events();
        $events->register();

        (new InquiryPostType())->register();
        (new ContactForm())->register();
        (new SitePages())->register();

        if (! class_exists('WooCommerce')) {
            add_action('admin_notices', [$this, 'render_woocommerce_notice']);
            return;
        }

        (new QuoteRequestPostType())->register();
        (new QuoteBuilder())->register();

        $metadata = new Metadata();
        $validation = new Validation();
        $order_factory = new OrderFactory($metadata);

        (new Form())->register();
        (new AjaxController($validation, $order_factory))->register();
        (new ProductCards())->register();
        (new ProductPage())->register();
        (new ProductArchives())->register();
        (new CartCheckoutVisibility())->register();
    }

    /**
     * Show a gentle admin notice if WooCommerce is missing.
     */
    public function render_woocommerce_notice()
    {
        if (! current_user_can('activate_plugins')) {
            return;
        }

        echo '<div class="notice notice-warning"><p>';
        echo esc_html__('Uninet Core is active, but WooCommerce is not active. WooCommerce-specific features will load after WooCommerce is activated.', 'uninet-core');
        echo '</p></div>';
    }
}
