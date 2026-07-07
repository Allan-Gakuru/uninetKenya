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
        echo esc_html__('Fill in the required details first. We will reserve your order, then show the sales number to call for final confirmation.', 'uninet-core');
        echo '</p>';

        echo '<form class="uninet-call-form" data-uninet-call-form novalidate>';
        echo '<input type="hidden" name="product_id" data-uninet-call-product-id value="" />';
        echo '<div class="uninet-call-form__product">';
        echo '<span>' . esc_html__('Product', 'uninet-core') . '</span>';
        echo '<strong data-uninet-call-product-name></strong>';
        echo '</div>';
        echo '<div class="uninet-call-form__status" data-uninet-call-status role="status" aria-live="polite"></div>';

        $this->render_group_start(__('Your details', 'uninet-core'), __('Required', 'uninet-core'));
        $this->render_input('full_name', __('Full name', 'uninet-core'), 'text', true, 'name', '', __('e.g. Allan Mugo', 'uninet-core'));
        $this->render_input('phone', __('Phone number', 'uninet-core'), 'tel', true, 'tel', '', __('e.g. 0712 345 678', 'uninet-core'));
        $this->render_input('quantity', __('Quantity', 'uninet-core'), 'number', true, '', '1', __('e.g. 2', 'uninet-core'), 'min="1" step="1"');
        $this->render_group_end();

        $this->render_group_start(__('Location', 'uninet-core'), __('Required', 'uninet-core'));
        $this->render_select('county', __('County', 'uninet-core'), $this->kenya_counties(), true, __('Select county', 'uninet-core'), 'address-level1');
        $this->render_input('town', __('Town', 'uninet-core'), 'text', true, 'address-level2', '', __('e.g. Nairobi CBD', 'uninet-core'));
        $this->render_input('pickup_location', __('Pickup point / delivery location', 'uninet-core'), 'text', true, 'street-address', '', __('e.g. Westlands office or CBD pickup point', 'uninet-core'));
        $this->render_group_end();

        echo '<div class="uninet-call-form__business-toggle">';
        echo '<label>';
        echo '<input type="checkbox" name="business_purchase" value="1" data-uninet-business-purchase aria-controls="uninet-call-business-group" />';
        echo '<span>' . esc_html__('Is this a business purchase?', 'uninet-core') . '</span>';
        echo '</label>';
        echo '<p>' . esc_html__('Check this if you want us to capture business invoice details for iTax or e-TIMS follow-up.', 'uninet-core') . '</p>';
        echo '</div>';

        $this->render_group_start(__('Business invoice details', 'uninet-core'), __('Required', 'uninet-core'), 'id="uninet-call-business-group" data-uninet-business-fields hidden disabled');
        $this->render_input('business_name', __('Business name', 'uninet-core'), 'text', true, 'organization', '', __('e.g. Uninet Technologies Ltd', 'uninet-core'), 'data-uninet-business-required');
        $this->render_input('email', __('Email for iTax invoice', 'uninet-core'), 'email', true, 'email', '', __('e.g. accounts@company.co.ke', 'uninet-core'), 'data-uninet-business-email data-uninet-business-required aria-describedby="uninet-call-email-help"');
        echo '<p id="uninet-call-email-help" class="uninet-call-form__help">' . esc_html__('Required for business purchases so staff can follow up on invoice details.', 'uninet-core') . '</p>';
        $this->render_input('kra_pin', __('Business KRA PIN', 'uninet-core'), 'text', true, '', '', __('e.g. P051234567A', 'uninet-core'), 'data-uninet-business-required');
        $this->render_group_end();

        $this->render_group_start(__('Final note', 'uninet-core'), __('Optional', 'uninet-core'));
        $this->render_textarea('notes', __('Additional notes', 'uninet-core'), __('Optional', 'uninet-core'), __('Preferred delivery time, product questions, or invoice notes', 'uninet-core'));
        $this->render_group_end();

        echo '<button type="submit" class="button uninet-call-form__submit" data-uninet-call-submit>';
        echo esc_html__('Finish order to call', 'uninet-core');
        echo '</button>';
        echo '</form>';

        echo '<div class="uninet-call-success" data-uninet-call-success hidden></div>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Open a grouped form section.
     *
     * @param string $legend Section legend.
     * @param string $meta Section meta label.
     * @param string $attrs Extra fieldset attributes.
     */
    private function render_group_start($legend, $meta, $attrs = '')
    {
        echo '<fieldset class="uninet-call-form__group" ' . $attrs . '>';
        echo '<legend>';
        echo '<span>' . esc_html($legend) . '</span>';
        echo '<em>' . esc_html($meta) . '</em>';
        echo '</legend>';
    }

    /**
     * Close a grouped form section.
     */
    private function render_group_end()
    {
        echo '</fieldset>';
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
     * @param string $placeholder Placeholder text.
     * @param string $extra_attrs Extra input attributes.
     * @param string $badge Label badge override.
     */
    private function render_input($name, $label, $type = 'text', $required = false, $autocomplete = '', $value = '', $placeholder = '', $extra_attrs = '', $badge = '')
    {
        $id = 'uninet-call-' . str_replace('_', '-', $name);
        $field_class = 'uninet-call-form__field uninet-call-form__field--' . str_replace('_', '-', $name);

        echo '<div class="' . esc_attr($field_class) . '">';
        echo '<label for="' . esc_attr($id) . '">' . esc_html($label);

        if ($required) {
            echo ' <span class="uninet-call-form__required">' . esc_html__('Required', 'uninet-core') . '</span>';
        } elseif ('' !== $badge) {
            echo ' <span class="uninet-call-form__optional">' . esc_html($badge) . '</span>';
        }

        echo '</label>';
        printf(
            '<input id="%1$s" name="%2$s" type="%3$s" value="%4$s" %5$s %6$s %7$s %8$s />',
            esc_attr($id),
            esc_attr($name),
            esc_attr($type),
            esc_attr($value),
            $required ? 'required aria-required="true"' : '',
            $autocomplete ? 'autocomplete="' . esc_attr($autocomplete) . '"' : '',
            $placeholder ? 'placeholder="' . esc_attr($placeholder) . '"' : '',
            $extra_attrs
        );
        echo '</div>';
    }

    /**
     * Render a select field.
     *
     * @param string $name Field name.
     * @param string $label Field label.
     * @param array  $options Select options.
     * @param bool   $required Whether field is required.
     * @param string $placeholder Placeholder option.
     * @param string $autocomplete Autocomplete value.
     */
    private function render_select($name, $label, array $options, $required = false, $placeholder = '', $autocomplete = '')
    {
        $id = 'uninet-call-' . str_replace('_', '-', $name);
        $field_class = 'uninet-call-form__field uninet-call-form__field--' . str_replace('_', '-', $name);

        echo '<div class="' . esc_attr($field_class) . '">';
        echo '<label for="' . esc_attr($id) . '">' . esc_html($label);

        if ($required) {
            echo ' <span class="uninet-call-form__required">' . esc_html__('Required', 'uninet-core') . '</span>';
        }

        echo '</label>';
        printf(
            '<select id="%1$s" name="%2$s" %3$s %4$s>',
            esc_attr($id),
            esc_attr($name),
            $required ? 'required aria-required="true"' : '',
            $autocomplete ? 'autocomplete="' . esc_attr($autocomplete) . '"' : ''
        );
        echo '<option value="">' . esc_html($placeholder) . '</option>';

        foreach ($options as $option) {
            echo '<option value="' . esc_attr($option) . '">' . esc_html($option) . '</option>';
        }

        echo '</select>';
        echo '</div>';
    }

    /**
     * Render a textarea field.
     *
     * @param string $name Field name.
     * @param string $label Field label.
     * @param string $badge Label badge.
     * @param string $placeholder Placeholder text.
     */
    private function render_textarea($name, $label, $badge = '', $placeholder = '')
    {
        $id = 'uninet-call-' . str_replace('_', '-', $name);
        $field_class = 'uninet-call-form__field uninet-call-form__field--' . str_replace('_', '-', $name);

        echo '<div class="' . esc_attr($field_class) . '">';
        echo '<label for="' . esc_attr($id) . '">' . esc_html($label);

        if ('' !== $badge) {
            echo ' <span class="uninet-call-form__optional">' . esc_html($badge) . '</span>';
        }

        echo '</label>';
        echo '<textarea id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" rows="3" placeholder="' . esc_attr($placeholder) . '"></textarea>';
        echo '</div>';
    }

    /**
     * Kenya counties in county-code order.
     */
    private function kenya_counties()
    {
        return [
            'Mombasa',
            'Kwale',
            'Kilifi',
            'Tana River',
            'Lamu',
            'Taita-Taveta',
            'Garissa',
            'Wajir',
            'Mandera',
            'Marsabit',
            'Isiolo',
            'Meru',
            'Tharaka-Nithi',
            'Embu',
            'Kitui',
            'Machakos',
            'Makueni',
            'Nyandarua',
            'Nyeri',
            'Kirinyaga',
            "Murang'a",
            'Kiambu',
            'Turkana',
            'West Pokot',
            'Samburu',
            'Trans-Nzoia',
            'Uasin Gishu',
            'Elgeyo-Marakwet',
            'Nandi',
            'Baringo',
            'Laikipia',
            'Nakuru',
            'Narok',
            'Kajiado',
            'Kericho',
            'Bomet',
            'Kakamega',
            'Vihiga',
            'Bungoma',
            'Busia',
            'Siaya',
            'Kisumu',
            'Homa Bay',
            'Migori',
            'Kisii',
            'Nyamira',
            'Nairobi',
        ];
    }
}
