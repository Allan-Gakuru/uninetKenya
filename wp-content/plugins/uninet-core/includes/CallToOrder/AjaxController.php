<?php
/**
 * Call to Order AJAX controller.
 *
 * @package UninetCore
 */

namespace Uninet\Core\CallToOrder;

use Uninet\Core\Admin\Settings;

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
        if (! check_ajax_referer('uninet_call_to_order', 'nonce', false)) {
            wp_send_json_error(
                [
                    'message' => __('Your session expired. Please refresh the page and try again.', 'uninet-core'),
                ],
                403
            );
        }

        $submitted = wp_unslash($_POST);
        $validated = $this->validation->validate($submitted);

        if (is_wp_error($validated)) {
            $error_data = $validated->get_error_data('uninet_call_to_order_invalid');

            wp_send_json_error(
                [
                    'message' => $validated->get_error_message(),
                    'field' => is_array($error_data) && isset($error_data['field']) ? $error_data['field'] : '',
                ],
                400
            );
        }

        $order = $this->order_factory->create($validated);

        if (is_wp_error($order)) {
            wp_send_json_error(
                [
                    'message' => $order->get_error_message(),
                ],
                500
            );
        }

        $sales_phone = Settings::get('sales_phone');

        wp_send_json_success(
            [
                'message' => __('Your order details have been saved. Call our sales team to finish confirmation.', 'uninet-core'),
                'orderId' => $order->get_id(),
                'orderNumber' => $order->get_order_number(),
                'salesPhone' => $sales_phone,
                'telUrl' => $this->build_tel_url($sales_phone),
            ],
            201
        );
    }

    /**
     * Build a tel URL from the configured sales phone.
     *
     * @param string $phone Phone number.
     */
    private function build_tel_url($phone)
    {
        $tel = preg_replace('/[^0-9+]/', '', $phone);

        return $tel ? 'tel:' . $tel : '';
    }
}
