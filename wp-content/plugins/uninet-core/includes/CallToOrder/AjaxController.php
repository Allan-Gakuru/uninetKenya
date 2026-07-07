<?php
/**
 * Call to Order AJAX controller shell.
 *
 * @package UninetCore
 */

namespace Uninet\Core\CallToOrder;

if (! defined('ABSPATH')) {
    exit;
}

final class AjaxController
{
    /**
     * Validation service.
     *
     * @var Validation
     */
    private $validation;

    /**
     * Order factory.
     *
     * @var OrderFactory
     */
    private $order_factory;

    /**
     * Constructor.
     *
     * @param Validation   $validation Validation service.
     * @param OrderFactory $order_factory Order factory.
     */
    public function __construct(Validation $validation, OrderFactory $order_factory)
    {
        $this->validation = $validation;
        $this->order_factory = $order_factory;
    }

    /**
     * Register hooks.
     */
    public function register()
    {
        add_action('wp_ajax_uninet_call_to_order', [$this, 'handle']);
        add_action('wp_ajax_nopriv_uninet_call_to_order', [$this, 'handle']);
    }

    /**
     * Handle AJAX request.
     */
    public function handle()
    {
        check_ajax_referer('uninet_call_to_order', 'nonce');

        wp_send_json_error(
            [
                'message' => __('Call to Order is being prepared and is not ready yet.', 'uninet-core'),
            ],
            501
        );
    }
}
