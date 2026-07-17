<?php
/**
 * Public contact form and WhatsApp handoff.
 *
 * @package UninetCore
 */

namespace Uninet\Core\Contact;

if (! defined('ABSPATH')) {
    exit;
}

final class Form
{
    const ACTION = 'uninet_contact_submit';
    const TRANSIENT_PREFIX = 'uninet_contact_';

    /**
     * Memoized one-time response for repeated content renders.
     *
     * @var array|null
     */
    private $state = null;

    /**
     * Register hooks.
     */
    public function register()
    {
        add_shortcode('uninet_contact_form', [$this, 'render']);
        add_filter('the_content', [$this, 'append_to_contact_page']);
        add_action('admin_post_' . self::ACTION, [$this, 'handle_submission']);
        add_action('admin_post_nopriv_' . self::ACTION, [$this, 'handle_submission']);
    }

    /**
     * Preserve existing contact-page copy while guaranteeing the form is present.
     *
     * @param string $content Page content.
     */
    public function append_to_contact_page($content)
    {
        if (
            ! is_page('contact-us')
            || ! in_the_loop()
            || ! is_main_query()
            || has_shortcode($content, 'uninet_contact_form')
        ) {
            return $content;
        }

        return $content . do_shortcode('[uninet_contact_form]');
    }

    /**
     * Render the contact form and any one-time response state.
     */
    public function render()
    {
        $state = $this->response_state();
        $values = isset($state['values']) && is_array($state['values']) ? $state['values'] : [];
        $errors = isset($state['errors']) && is_array($state['errors']) ? $state['errors'] : [];

        ob_start();
        ?>
        <section class="uninet-contact" aria-labelledby="uninet-contact-heading">
            <div class="uninet-contact__intro">
                <p class="uninet-contact__lead"><?php esc_html_e('Tell us what your organisation needs, what is getting in the way, and what a useful next step would look like. A member of the Uninet team will review the message before following up.', 'uninet-core'); ?></p>
                <div class="uninet-contact__direct">
                    <div>
                        <strong><?php esc_html_e('Prefer to call?', 'uninet-core'); ?></strong>
                        <a href="tel:+254770313200">0770 313 200</a>
                    </div>
                    <p><?php esc_html_e('Messages are saved securely in the Uninet dashboard for staff follow-up.', 'uninet-core'); ?></p>
                </div>
            </div>

            <?php if (! empty($state['success'])) : ?>
                <div class="uninet-contact-success" role="status" tabindex="-1" data-uninet-contact-success>
                    <div class="uninet-contact-success__icon" aria-hidden="true">&#10003;</div>
                    <div>
                        <h2 id="uninet-contact-heading"><?php esc_html_e('Your message has been saved.', 'uninet-core'); ?></h2>
                        <p><?php esc_html_e('Continue to WhatsApp to send a concise summary directly to our sales team. Your complete answers remain available to staff in the dashboard, and WhatsApp will let you review the summary before sending.', 'uninet-core'); ?></p>
                        <a class="button uninet-contact-success__button" href="<?php echo esc_url($state['whatsapp_url']); ?>" target="_blank" rel="noopener noreferrer">
                            <?php esc_html_e('Continue on WhatsApp', 'uninet-core'); ?>
                        </a>
                    </div>
                </div>
            <?php else : ?>
                <div class="uninet-contact__form-wrap">
                    <div class="uninet-contact__form-heading">
                        <h2 id="uninet-contact-heading"><?php esc_html_e('Send us a message', 'uninet-core'); ?></h2>
                        <p><?php esc_html_e('Required fields are marked with an asterisk.', 'uninet-core'); ?></p>
                    </div>

                    <?php if ($errors) : ?>
                        <div class="uninet-contact-errors" role="alert">
                            <strong><?php esc_html_e('Please correct the following:', 'uninet-core'); ?></strong>
                            <ul>
                                <?php foreach ($errors as $error) : ?>
                                    <li><?php echo esc_html($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form class="uninet-contact-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION); ?>">
                        <?php wp_nonce_field(self::ACTION, 'uninet_contact_nonce'); ?>
                        <div class="uninet-contact-form__honeypot" aria-hidden="true">
                            <label for="uninet-contact-website"><?php esc_html_e('Website', 'uninet-core'); ?></label>
                            <input id="uninet-contact-website" type="text" name="website" value="" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="uninet-contact-form__grid">
                            <?php $this->text_field('full_name', __('Full name', 'uninet-core'), __('Jane Wanjiku', 'uninet-core'), $values, true, 'text', 'name'); ?>
                            <?php $this->text_field('phone', __('Phone number', 'uninet-core'), __('e.g. 0770 313 200', 'uninet-core'), $values, true, 'tel', 'tel'); ?>
                            <?php $this->text_field('email', __('Email address', 'uninet-core'), __('name@business.co.ke', 'uninet-core'), $values, true, 'email', 'email'); ?>
                            <?php $this->text_field('business_name', __('Business name', 'uninet-core'), __('Optional', 'uninet-core'), $values, false, 'text', 'organization'); ?>
                        </div>

                        <?php $this->text_field('subject', __('Subject', 'uninet-core'), __('What would you like help with?', 'uninet-core'), $values, true); ?>
                        <?php $this->textarea_field('challenge', __('What is the biggest challenge you are trying to solve right now?', 'uninet-core'), __('Describe the business or technology problem and who it affects.', 'uninet-core'), $values, true); ?>
                        <?php $this->textarea_field('already_tried', __('What have you already tried?', 'uninet-core'), __('Tell us what has worked, what has not, or what you have considered.', 'uninet-core'), $values, true); ?>
                        <?php $this->textarea_field('why_now', __('Why is now the right time to address it?', 'uninet-core'), __('Share any deadline, growth plan, operational risk, or immediate need.', 'uninet-core'), $values, true); ?>
                        <?php $this->textarea_field('message', __('Anything else we should know?', 'uninet-core'), __('Optional: quantities, preferred brands, budget range, delivery location, or other context.', 'uninet-core'), $values, false); ?>

                        <div class="uninet-contact-form__consent">
                            <p><?php esc_html_e('By submitting, you agree that Uninet Technologies may use these details to respond to your enquiry. You can then choose whether to continue on WhatsApp.', 'uninet-core'); ?></p>
                        </div>

                        <button type="submit" class="button uninet-contact-form__submit">
                            <?php esc_html_e('Send Message', 'uninet-core'); ?>
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </section>
        <?php

        return (string) ob_get_clean();
    }

    /**
     * Process and store a public submission.
     */
    public function handle_submission()
    {
        $return_url = $this->contact_url();

        if (
            empty($_POST['uninet_contact_nonce'])
            || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['uninet_contact_nonce'])), self::ACTION)
        ) {
            $this->redirect_with_state(
                $return_url,
                [
                    'errors' => [__('Your session expired. Please refresh the page and try again.', 'uninet-core')],
                    'values' => [],
                ]
            );
        }

        if (! empty($_POST['website'])) {
            $this->redirect_with_state($return_url, ['success' => true, 'whatsapp_url' => $this->whatsapp_url([])]);
        }

        $values = $this->sanitize_submission(wp_unslash($_POST));
        $errors = $this->validate($values);

        if (! $errors && $this->is_rate_limited()) {
            $errors[] = __('Your previous message was received. Please wait one minute before submitting another.', 'uninet-core');
        }

        if ($errors) {
            $this->redirect_with_state($return_url, ['errors' => $errors, 'values' => $values]);
        }

        $post_id = wp_insert_post(
            [
                'post_type' => InquiryPostType::POST_TYPE,
                'post_status' => 'publish',
                'post_title' => $values['full_name'] . ' - ' . $values['subject'],
            ],
            true
        );

        if (is_wp_error($post_id)) {
            $this->redirect_with_state(
                $return_url,
                [
                    'errors' => [__('We could not save your message. Please try again or call 0770 313 200.', 'uninet-core')],
                    'values' => $values,
                ]
            );
        }

        foreach ($values as $key => $value) {
            update_post_meta($post_id, '_uninet_contact_' . $key, $value);
        }

        update_post_meta($post_id, '_uninet_contact_source', 'contact-page');
        update_post_meta($post_id, '_uninet_contact_whatsapp_offered', 'yes');
        $this->set_rate_limit();

        $this->redirect_with_state(
            $return_url,
            [
                'success' => true,
                'whatsapp_url' => $this->whatsapp_url($values),
            ]
        );
    }

    /**
     * Render a text input.
     */
    private function text_field($name, $label, $placeholder, $values, $required = false, $type = 'text', $autocomplete = '')
    {
        $id = 'uninet-contact-' . str_replace('_', '-', $name);
        $maxlength = 'phone' === $name ? 30 : 190;
        ?>
        <div class="uninet-contact-field">
            <label for="<?php echo esc_attr($id); ?>">
                <?php echo esc_html($label); ?><?php if ($required) : ?> <span aria-hidden="true">*</span><?php endif; ?>
            </label>
            <input
                id="<?php echo esc_attr($id); ?>"
                type="<?php echo esc_attr($type); ?>"
                name="<?php echo esc_attr($name); ?>"
                value="<?php echo esc_attr($values[$name] ?? ''); ?>"
                placeholder="<?php echo esc_attr($placeholder); ?>"
                maxlength="<?php echo esc_attr($maxlength); ?>"
                <?php echo $required ? 'required' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php echo $autocomplete ? 'autocomplete="' . esc_attr($autocomplete) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            >
        </div>
        <?php
    }

    /**
     * Render a textarea.
     */
    private function textarea_field($name, $label, $placeholder, $values, $required)
    {
        $id = 'uninet-contact-' . str_replace('_', '-', $name);
        $maxlength = 'message' === $name ? 3000 : 2000;
        ?>
        <div class="uninet-contact-field uninet-contact-field--wide">
            <label for="<?php echo esc_attr($id); ?>">
                <?php echo esc_html($label); ?><?php if ($required) : ?> <span aria-hidden="true">*</span><?php endif; ?>
            </label>
            <textarea
                id="<?php echo esc_attr($id); ?>"
                name="<?php echo esc_attr($name); ?>"
                rows="4"
                placeholder="<?php echo esc_attr($placeholder); ?>"
                maxlength="<?php echo esc_attr($maxlength); ?>"
                <?php echo $required ? 'required' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            ><?php echo esc_textarea($values[$name] ?? ''); ?></textarea>
        </div>
        <?php
    }

    /**
     * Sanitize submitted fields.
     */
    private function sanitize_submission($source)
    {
        return [
            'full_name' => $this->limit(sanitize_text_field($source['full_name'] ?? ''), 190),
            'phone' => $this->limit(sanitize_text_field($source['phone'] ?? ''), 30),
            'email' => $this->limit(sanitize_email($source['email'] ?? ''), 190),
            'business_name' => $this->limit(sanitize_text_field($source['business_name'] ?? ''), 190),
            'subject' => $this->limit(sanitize_text_field($source['subject'] ?? ''), 190),
            'challenge' => $this->limit(sanitize_textarea_field($source['challenge'] ?? ''), 2000),
            'already_tried' => $this->limit(sanitize_textarea_field($source['already_tried'] ?? ''), 2000),
            'why_now' => $this->limit(sanitize_textarea_field($source['why_now'] ?? ''), 2000),
            'message' => $this->limit(sanitize_textarea_field($source['message'] ?? ''), 3000),
        ];
    }

    /**
     * Limit a sanitized value without breaking multibyte text.
     */
    private function limit($value, $length)
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length);
        }

        return substr($value, 0, $length);
    }

    /**
     * Validate required contact details.
     */
    private function validate($values)
    {
        $errors = [];
        $required = [
            'full_name' => __('Enter your full name.', 'uninet-core'),
            'phone' => __('Enter your phone number.', 'uninet-core'),
            'email' => __('Enter your email address.', 'uninet-core'),
            'subject' => __('Enter a subject.', 'uninet-core'),
            'challenge' => __('Describe the biggest challenge you are trying to solve.', 'uninet-core'),
            'already_tried' => __('Tell us what you have already tried.', 'uninet-core'),
            'why_now' => __('Tell us why now is the right time to address it.', 'uninet-core'),
        ];

        foreach ($required as $key => $message) {
            if ('' === trim($values[$key])) {
                $errors[] = $message;
            }
        }

        if ($values['email'] && ! is_email($values['email'])) {
            $errors[] = __('Enter a valid email address.', 'uninet-core');
        }

        $phone_digits = preg_replace('/\D+/', '', $values['phone']);

        if ($values['phone'] && (strlen($phone_digits) < 9 || strlen($phone_digits) > 15)) {
            $errors[] = __('Enter a valid phone number.', 'uninet-core');
        }

        return $errors;
    }

    /**
     * Build the prefilled WhatsApp URL.
     */
    private function whatsapp_url($values)
    {
        $lines = [
            'Hello Uninet Technologies, I have submitted an enquiry through your website.',
        ];

        $labels = [
            'full_name' => 'Name',
            'phone' => 'Phone',
            'email' => 'Email',
            'business_name' => 'Business',
            'subject' => 'Subject',
            'challenge' => 'Biggest challenge',
            'already_tried' => 'Already tried',
            'why_now' => 'Why now',
            'message' => 'Additional context',
        ];
        $limits = [
            'full_name' => 100,
            'phone' => 30,
            'email' => 120,
            'business_name' => 120,
            'subject' => 160,
            'challenge' => 260,
            'already_tried' => 260,
            'why_now' => 260,
            'message' => 180,
        ];

        foreach ($labels as $key => $label) {
            if (! empty($values[$key])) {
                $lines[] = $label . ': ' . $this->limit($values[$key], $limits[$key]);
            }
        }

        return 'https://wa.me/254770313200?text=' . rawurlencode(implode("\n\n", $lines));
    }

    /**
     * Whether this browser recently created an enquiry.
     */
    private function is_rate_limited()
    {
        return (bool) get_transient($this->rate_limit_key());
    }

    /**
     * Prevent accidental repeats and simple automated submission bursts.
     */
    private function set_rate_limit()
    {
        set_transient($this->rate_limit_key(), '1', MINUTE_IN_SECONDS);
    }

    /**
     * Build a privacy-preserving request fingerprint without storing the IP.
     */
    private function rate_limit_key()
    {
        $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $agent = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
        $fingerprint = hash_hmac('sha256', $ip . '|' . $agent, wp_salt('nonce'));

        return 'uninet_contact_rate_' . substr($fingerprint, 0, 32);
    }

    /**
     * Store a one-time response state and redirect back to the contact page.
     */
    private function redirect_with_state($url, $state)
    {
        $token = sanitize_key(wp_generate_password(32, false, false));
        set_transient(self::TRANSIENT_PREFIX . $token, $state, 10 * MINUTE_IN_SECONDS);
        wp_safe_redirect(add_query_arg('contact_state', $token, $url));
        exit;
    }

    /**
     * Read and remove the one-time response state.
     */
    private function response_state()
    {
        if (null !== $this->state) {
            return $this->state;
        }

        if (empty($_GET['contact_state'])) {
            $this->state = [];
            return $this->state;
        }

        $token = sanitize_key(wp_unslash($_GET['contact_state']));
        $key = self::TRANSIENT_PREFIX . $token;
        $state = get_transient($key);
        delete_transient($key);

        $this->state = is_array($state) ? $state : [];
        return $this->state;
    }

    /**
     * Resolve the contact page URL.
     */
    private function contact_url()
    {
        $page = get_page_by_path('contact-us');
        return $page instanceof \WP_Post ? get_permalink($page) : home_url('/contact-us/');
    }
}
