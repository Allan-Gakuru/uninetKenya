<?php
/**
 * Call to Order order creation.
 *
 * @package UninetCore
 */

namespace Uninet\Core\CallToOrder;

use Uninet\Core\Admin\Settings;

if (! defined('ABSPATH')) {
    exit;
}

final class OrderFactory
{
    /**
     * Metadata helper.
     *
     * @var Metadata
     */
    private $metadata;

    /**
     * Constructor.
     *
     * @param Metadata $metadata Metadata helper.
     */
    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Create a WooCommerce pending-payment order.
     *
     * @param array $data Validated data.
     */
    public function create(array $data)
    {
        $product = wc_get_product($data['product_id']);

        if (! $product) {
            return new \WP_Error(
                'uninet_invalid_product',
                __('The selected product could not be found.', 'uninet-core')
            );
        }

        $order = wc_create_order(
            [
                'created_via' => 'uninet_call_to_order',
                'status' => 'pending',
            ]
        );

        if (is_wp_error($order)) {
            return $order;
        }

        $item_id = $order->add_product($product, $data['quantity']);

        if (! $item_id) {
            return new \WP_Error(
                'uninet_order_item_failed',
                __('The product could not be added to the order.', 'uninet-core')
            );
        }

        $sales_phone = Settings::get('sales_phone');

        $order->set_created_via('uninet_call_to_order');
        $order->set_billing_first_name($data['full_name']);
        $order->set_billing_phone($data['phone']);
        $order->set_billing_state($data['county']);
        $order->set_billing_city($data['town']);
        $order->set_billing_address_1($data['pickup_location']);

        if ('' !== $data['business_name']) {
            $order->set_billing_company($data['business_name']);
        }

        if ('' !== $data['email']) {
            $order->set_billing_email($data['email']);
        }

        if ('' !== $data['notes']) {
            $order->set_customer_note($data['notes']);
        }

        $order->update_meta_data(Metadata::ORDER_SOURCE, $this->metadata->source_label());
        $order->update_meta_data(Metadata::FULL_NAME, $data['full_name']);
        $order->update_meta_data(Metadata::PHONE, $data['phone']);
        $order->update_meta_data(Metadata::QUANTITY, $data['quantity']);
        $order->update_meta_data(Metadata::COUNTY, $data['county']);
        $order->update_meta_data(Metadata::TOWN, $data['town']);
        $order->update_meta_data(Metadata::PICKUP_LOCATION, $data['pickup_location']);
        $order->update_meta_data(Metadata::BUSINESS_NAME, $data['business_name']);
        $order->update_meta_data(Metadata::EMAIL, $data['email']);
        $order->update_meta_data(Metadata::KRA_PIN, $data['kra_pin']);
        $order->update_meta_data(Metadata::NOTES, $data['notes']);
        $order->update_meta_data(Metadata::SALES_NUMBER_SHOWN, $sales_phone);

        $order->calculate_totals();
        $order->set_status('pending');
        $order->add_order_note($this->build_internal_note($data, $product, $sales_phone), false);
        $order->save();

        return $order;
    }

    /**
     * Build the internal order note for staff.
     *
     * @param array       $data Validated data.
     * @param \WC_Product $product Product.
     * @param string      $sales_phone Sales phone shown to buyer.
     */
    private function build_internal_note(array $data, \WC_Product $product, $sales_phone)
    {
        $lines = [
            __('Call to Order submission from product page.', 'uninet-core'),
            __('Stock has not been reduced. Staff must confirm availability before payment.', 'uninet-core'),
            '',
            sprintf(__('Product: %s', 'uninet-core'), $product->get_name()),
            sprintf(__('Quantity requested: %d', 'uninet-core'), $data['quantity']),
            sprintf(__('Buyer: %s', 'uninet-core'), $data['full_name']),
            sprintf(__('Phone: %s', 'uninet-core'), $data['phone']),
            sprintf(__('County: %s', 'uninet-core'), $data['county']),
            sprintf(__('Town: %s', 'uninet-core'), $data['town']),
            sprintf(__('Pickup point / delivery location: %s', 'uninet-core'), $data['pickup_location']),
        ];

        if ('' !== $data['business_name']) {
            $lines[] = sprintf(__('Business: %s', 'uninet-core'), $data['business_name']);
        }

        if ('' !== $data['email']) {
            $lines[] = sprintf(__('Email: %s', 'uninet-core'), $data['email']);
        }

        if ('' !== $data['kra_pin']) {
            $lines[] = sprintf(__('KRA PIN: %s', 'uninet-core'), $data['kra_pin']);
        }

        if ('' !== $data['notes']) {
            $lines[] = sprintf(__('Additional notes: %s', 'uninet-core'), $data['notes']);
        }

        if ('' !== $sales_phone) {
            $lines[] = sprintf(__('Sales number shown after submission: %s', 'uninet-core'), $sales_phone);
        }

        return implode("\n", $lines);
    }
}
