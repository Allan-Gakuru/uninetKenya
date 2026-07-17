<?php
/**
 * Create the essential public pages required by the phase-one site.
 *
 * @package UninetCore
 */

namespace Uninet\Core\Setup;

if (! defined('ABSPATH')) {
    exit;
}

final class SitePages
{
    const SETUP_VERSION = '2';

    /**
     * Register the one-time admin setup.
     */
    public function register()
    {
        add_action('admin_init', [$this, 'maybe_create_pages']);
    }

    /**
     * Create missing pages without overwriting existing staff content.
     */
    public function maybe_create_pages()
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        if (self::SETUP_VERSION === get_option('uninet_core_site_pages_version')) {
            return;
        }

        $this->ensure_page(
            'contact-us',
            __('Contact Us', 'uninet-core'),
            '[uninet_contact_form]'
        );

        $privacy_content = $this->privacy_content();
        $privacy_id = $this->ensure_page(
            'privacy-policy',
            __('Privacy Policy', 'uninet-core'),
            $privacy_content
        );

        $this->update_legacy_privacy_content($privacy_id, $privacy_content);

        if ($privacy_id && ! (int) get_option('wp_page_for_privacy_policy')) {
            update_option('wp_page_for_privacy_policy', $privacy_id);
        }

        update_option('uninet_core_site_pages_version', self::SETUP_VERSION, false);
    }

    /**
     * Create one page if its slug is not already present.
     */
    private function ensure_page($slug, $title, $content)
    {
        $existing = get_page_by_path($slug);

        if ($existing instanceof \WP_Post) {
            return (int) $existing->ID;
        }

        $page_id = wp_insert_post(
            [
                'post_type' => 'page',
                'post_status' => 'publish',
                'post_title' => $title,
                'post_name' => $slug,
                'post_content' => $content,
                'comment_status' => 'closed',
            ],
            true
        );

        if (is_wp_error($page_id)) {
            return 0;
        }

        update_post_meta($page_id, '_uninet_core_managed_page', $slug);
        return (int) $page_id;
    }

    /**
     * Correct the original generated policy without overwriting staff edits.
     */
    private function update_legacy_privacy_content($page_id, $content)
    {
        if (! $page_id) {
            return;
        }

        $current = trim((string) get_post_field('post_content', $page_id));
        $legacy = trim($this->legacy_privacy_content());

        if ($current !== $legacy) {
            return;
        }

        wp_update_post(
            [
                'ID' => $page_id,
                'post_content' => $content,
            ]
        );

        update_post_meta($page_id, '_uninet_core_managed_page', 'privacy-policy');
    }

    /**
     * Return the exact first generated policy so edited pages are never replaced.
     */
    private function legacy_privacy_content()
    {
        return '<h2>Information we collect</h2>'
            . '<p>Uninet Technologies collects the information you provide through contact and order-request forms, including your name, phone number, email address, business and invoicing details, location, product requirements, and messages.</p>'
            . '<h2>How we use your information</h2>'
            . '<p>We use this information to respond to enquiries, confirm product availability, prepare quotations and invoices, arrange delivery, provide support, prevent misuse, and improve our website and services.</p>'
            . '<h2>WhatsApp and external services</h2>'
            . '<p>When you choose to continue an enquiry through WhatsApp, you leave this website and share the prepared message with WhatsApp. Their privacy terms then apply. The website may also use essential cookies, security services, search tools, and privacy-conscious analytics configured by Uninet Technologies.</p>'
            . '<h2>Sharing and retention</h2>'
            . '<p>We only share information where needed to fulfil your request, operate the website, comply with the law, or protect the business and its customers. Enquiries and order records are retained only for as long as reasonably necessary for follow-up, accounting, warranty, security, and legal obligations.</p>'
            . '<h2>Your choices</h2>'
            . '<p>You may ask to access, correct, or delete personal information that we hold, subject to applicable legal and business record requirements.</p>'
            . '<h2>Contact us about privacy</h2>'
            . '<p>Call <a href="tel:+254770313200">0770 313 200</a> or use the <a href="' . esc_url(home_url('/contact-us/')) . '">contact page</a> for privacy questions or requests.</p>';
    }

    /**
     * Return the starter privacy notice for the current site behavior.
     */
    private function privacy_content()
    {
        return '<h2>Information we collect</h2>'
            . '<p>Uninet Technologies collects the information you provide through contact and order-request forms, including your name, phone number, email address, business and invoicing details, location, product requirements, and messages.</p>'
            . '<h2>How we use your information</h2>'
            . '<p>We use this information to respond to enquiries, confirm product availability, prepare quotations and invoices, arrange delivery, provide support, prevent misuse, and improve our website and services.</p>'
            . '<h2>WhatsApp</h2>'
            . '<p>When you choose to continue an enquiry through WhatsApp, you leave this website and share a prepared summary with WhatsApp. You can review the message before sending it. WhatsApp and Meta then process that information under their own privacy terms.</p>'
            . '<h2>Fonts, analytics, search, and security services</h2>'
            . '<p>The site may connect to third-party services used for typography, analytics, search, backups, and security. Poppins may be delivered through Google Fonts, and Google Site Kit may connect measurement or search services configured by Uninet Technologies. These providers may receive technical information such as your IP address, browser details, device information, cookies, and the page requested, according to their own terms and the site configuration.</p>'
            . '<h2>Sharing and retention</h2>'
            . '<p>We only share information where needed to fulfil your request, operate the website, comply with the law, or protect the business and its customers. Enquiries and order records are retained only for as long as reasonably necessary for follow-up, accounting, warranty, security, and legal obligations.</p>'
            . '<h2>Your choices</h2>'
            . '<p>You may ask to access, correct, or delete personal information that we hold, subject to applicable legal and business record requirements.</p>'
            . '<h2>Contact us about privacy</h2>'
            . '<p>Call <a href="tel:+254770313200">0770 313 200</a> or use the <a href="' . esc_url(home_url('/contact-us/')) . '">contact page</a> for privacy questions or requests.</p>';
    }
}
