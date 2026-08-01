<?php
/**
 * Site-wide WhatsApp conversion control.
 *
 * @package UninetCore
 */

namespace Uninet\Core\Conversion;

if (! defined('ABSPATH')) {
    exit;
}

final class WhatsAppButton
{
    const PHONE = '254770313200';

    /**
     * Register frontend hooks.
     */
    public function register()
    {
        add_action('wp_footer', [$this, 'render'], 40);
    }

    /**
     * Render the customer-initiated WhatsApp link.
     */
    public function render()
    {
        if (is_admin() || wp_doing_ajax() || is_feed() || is_embed()) {
            return;
        }

        $message = $this->message();
        $url = 'https://wa.me/' . self::PHONE . '?text=' . rawurlencode($message);
        ?>
        <a
            class="uninet-whatsapp-float"
            href="<?php echo esc_url($url); ?>"
            target="_blank"
            rel="nofollow noopener noreferrer"
            aria-label="<?php esc_attr_e('Chat with Uninet Technologies on WhatsApp; opens in a new tab', 'uninet-core'); ?>"
            title="<?php esc_attr_e('Chat with Uninet on WhatsApp', 'uninet-core'); ?>"
            data-uninet-track-location="floating-whatsapp"
        >
            <svg class="uninet-whatsapp-float__icon" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93a7.898 7.898 0 0 0-2.327-5.607ZM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592Zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.115.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.17-1.1-1.367-.114-.198-.012-.305.087-.404.09-.088.197-.232.296-.346.1-.116.133-.198.198-.33.065-.134.034-.248-.016-.347-.05-.099-.445-1.076-.61-1.47-.161-.389-.325-.337-.445-.344-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.527.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.13 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.13 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232Z" />
            </svg>
            <span><?php esc_html_e('WhatsApp us', 'uninet-core'); ?></span>
        </a>
        <?php
    }

    /**
     * Build a short message for the current buying context.
     */
    private function message()
    {
        $product = $this->current_product();

        if ($product instanceof \WC_Product) {
            $name = wp_html_excerpt(wp_strip_all_tags($product->get_name()), 160, '...');
            $permalink = get_permalink($product->get_id());

            return sprintf(
                /* translators: 1: product name, 2: product page URL. */
                __("Hello Uninet Technologies, I am considering %1\$s for my business. Please confirm suitability, availability, the current pre-tax price, delivery options, and quotation details.\n\nProduct page: %2\$s", 'uninet-core'),
                $name,
                $permalink
            );
        }

        return __('Hello Uninet Technologies, I am sourcing technology for my business and would like guidance on the right solution. Please recommend suitable options and confirm availability, delivery, and quotation details.', 'uninet-core');
    }

    /**
     * Return the current WooCommerce product when viewing a product page.
     */
    private function current_product()
    {
        if (! function_exists('is_product') || ! function_exists('wc_get_product') || ! is_product()) {
            return null;
        }

        $product_id = get_queried_object_id();

        if (! $product_id) {
            return null;
        }

        return wc_get_product($product_id);
    }
}
