<?php
/**
 * Call to Order validation.
 *
 * @package UninetCore
 */

namespace Uninet\Core\CallToOrder;

if (! defined('ABSPATH')) {
    exit;
}

final class Validation
{
    /**
     * Validate submitted data.
     *
     * @param array $data Submitted data.
     */
    public function validate(array $data)
    {
        $validated = [
            'product_id' => isset($data['product_id']) ? absint($data['product_id']) : 0,
            'full_name' => isset($data['full_name']) ? sanitize_text_field($data['full_name']) : '',
            'phone' => isset($data['phone']) ? sanitize_text_field($data['phone']) : '',
            'quantity' => isset($data['quantity']) ? absint($data['quantity']) : 0,
            'county' => isset($data['county']) ? sanitize_text_field($data['county']) : '',
            'town' => isset($data['town']) ? sanitize_text_field($data['town']) : '',
            'pickup_location' => isset($data['pickup_location']) ? sanitize_text_field($data['pickup_location']) : '',
            'business_name' => isset($data['business_name']) ? sanitize_text_field($data['business_name']) : '',
            'email' => isset($data['email']) ? sanitize_email($data['email']) : '',
            'kra_pin' => isset($data['kra_pin']) ? strtoupper(sanitize_text_field($data['kra_pin'])) : '',
            'notes' => isset($data['notes']) ? sanitize_textarea_field($data['notes']) : '',
        ];

        if (! $validated['product_id'] || ! wc_get_product($validated['product_id'])) {
            return $this->error('product_id', __('Please choose a valid product.', 'uninet-core'));
        }

        if ('' === $validated['full_name']) {
            return $this->error('full_name', __('Please enter your full name.', 'uninet-core'));
        }

        if ('' === $validated['phone']) {
            return $this->error('phone', __('Please enter your phone number.', 'uninet-core'));
        }

        if ($validated['quantity'] < 1) {
            return $this->error('quantity', __('Please enter a valid quantity.', 'uninet-core'));
        }

        if ('' === $validated['county']) {
            return $this->error('county', __('Please enter your county.', 'uninet-core'));
        }

        if ('' === $validated['town']) {
            return $this->error('town', __('Please enter your town.', 'uninet-core'));
        }

        if ('' === $validated['pickup_location']) {
            return $this->error('pickup_location', __('Please enter your pickup point or delivery location.', 'uninet-core'));
        }

        if ('' !== $validated['business_name'] && '' === $validated['email']) {
            return $this->error('email', __('Please enter an email address when ordering for a business.', 'uninet-core'));
        }

        if ('' !== $validated['email'] && ! is_email($validated['email'])) {
            return $this->error('email', __('Please enter a valid email address.', 'uninet-core'));
        }

        return $validated;
    }

    /**
     * Build a field-specific error.
     *
     * @param string $field Field key.
     * @param string $message Error message.
     */
    private function error($field, $message)
    {
        return new \WP_Error(
            'uninet_call_to_order_invalid',
            $message,
            [
                'field' => $field,
            ]
        );
    }
}
