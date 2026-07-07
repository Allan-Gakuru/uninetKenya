<?php
/**
 * Call to Order form rendering shell.
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
     * Render an empty modal shell. The full form arrives in the Call to Order phase.
     */
    public function render_modal_shell()
    {
        if (! is_product()) {
            return;
        }

        echo '<div class="uninet-call-modal" data-uninet-call-modal hidden>';
        echo '<div class="uninet-call-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="uninet-call-modal-title">';
        echo '<button type="button" class="uninet-call-modal__close" data-uninet-call-close aria-label="' . esc_attr__('Close', 'uninet-core') . '">&times;</button>';
        echo '<h2 id="uninet-call-modal-title">' . esc_html__('Call to Order', 'uninet-core') . '</h2>';
        echo '<div data-uninet-call-modal-content></div>';
        echo '</div>';
        echo '</div>';
    }
}
