<?php
/**
 * Call to Order form rendering.
 *
 * @package UninetCore
 */

namespace Uninet\Core\CallToOrder;

if (! defined('ABSPATH')) {
    exit;
}

final class Form
{
    /**
     * Register hooks.
     */
    public function register()
    {
        add_action('wp_footer', [$this, 'render_modal_shell']);
    }

    /**
     * Render the modal form shell.
     */
    public function render_modal_shell()
    {
        if (! is_product()) {
            return;
        }

        echo '<div class="uninet-call-modal" data-uninet-call-modal hidden>';
        echo '<div class="uninet-call-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="uninet-call-modal-title" aria-describedby="uninet-call-modal-description">';
        echo '<button type="button" class="uninet-call-modal__close" data-uninet-call-close aria-label="' . esc_attr__('Close', 'uninet-core') . '">&times;</button>';
        echo '<h2 id="uninet-call-modal-title">' . esc_html__('Call to Order', 'uninet-core') . '</h2>';
        echo '<p id="uninet-call-modal-description" class="uninet-call-modal__description">';
        echo esc_html__('Send us your details. We will save a pending order, then show the sales number to call for final confirmation.', 'uninet-core');
        echo '</p>';

        echo '<form class="uninet-call-form" data-uninet-call-form novalidate>';
        echo '<input type="hidden" name="product_id" data-uninet-call-product-id value="" />';
        echo '<div class="uninet-call-form__product" data-uninet-call-product-name></div>';
        echo '<div class="uninet-call-form__status" data-uninet-call-status role="status" aria-live="polite"></div>';

        $this->render_input('full_name', __('Full name', 'uninet-core'), 'text', true, 'name');
        $this->render_input('phone', __('Phone number', 'uninet-core'), 'tel', true, 'tel');
        $this->render_input('quantity', __('Quantity', 'uninet-core'), 'number', true, '', '1', 'min="1" step="1"');
        $this->render_input('county', __('County', 'uninet-core'), 'text', true, 'address-level1');
        $this->render_input('town', __('Town', 'uninet-core'), 'text', true, 'address-level2');
        $this->render_input('pickup_location', __('Pickup point / delivery location', 'uninet-core'), 'text', true, 'street-address');
        $this->render_input('business_name', __('Business name', 'uninet-core'), 'text', false, 'organization');
        $this->render_input('email', __('Email for iTax invoice', 'uninet-core'), 'email', false, 'email', '', 'data-uninet-business-email aria-describedby="uninet-call-email-help"');
        echo '<p id="uninet-call-email-help" class="uninet-call-form__help">' . esc_html__('Required when business name is filled.', 'uninet-core') . '</p>';
        $this->render_input('kra_pin', __('Business KRA PIN', 'uninet-core'), 'text', false);
        $this->render_textarea('notes', __('Additional notes', 'uninet-core'));

        echo '<button type="submit" class="button uninet-call-form__submit" data-uninet-call-submit>';
        echo esc_html__('Finish order to call', 'uninet-core');
        echo '</button>';
        echo '</form>';

        echo '<div class="uninet-call-success" data-uninet-call-success hidden></div>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render a form input field.
     *
     * @param string $name Field name.
     * @param string $label Field label.
     * @param string $type Input type.
     * @param bool   $required Whether field is required.
     * @param string $autocomplete Autocomplete value.
     * @param string $value Default value.
     * @param string $extra_attrs Extra input attributes.
     */
    private function render_input($name, $label, $type = 'text', $required = false, $autocomplete = '', $value = '', $extra_attrs = '')
    {
        $id = 'uninet-call-' . str_replace('_', '-', $name);

        echo '<div class="uninet-call-form__field">';
        echo '<label for="' . esc_attr($id) . '">' . esc_html($label);

        if ($required) {
            echo ' <span class="uninet-call-form__required">' . esc_html__('Required', 'uninet-core') . '</span>';
        }

        echo '</label>';
        printf(
            '<input id="%1$s" name="%2$s" type="%3$s" value="%4$s" %5$s %6$s %7$s />',
            esc_attr($id),
            esc_attr($name),
            esc_attr($type),
            esc_attr($value),
            $required ? 'required aria-required="true"' : '',
            $autocomplete ? 'autocomplete="' . esc_attr($autocomplete) . '"' : '',
            $extra_attrs
        );
        echo '</div>';
    }

    /**
     * Render a textarea field.
     *
     * @param string $name Field name.
     * @param string $label Field label.
     */
    private function render_textarea($name, $label)
    {
        $id = 'uninet-call-' . str_replace('_', '-', $name);

        echo '<div class="uninet-call-form__field">';
        echo '<label for="' . esc_attr($id) . '">' . esc_html($label) . '</label>';
        echo '<textarea id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" rows="3"></textarea>';
        echo '</div>';
    }
}
