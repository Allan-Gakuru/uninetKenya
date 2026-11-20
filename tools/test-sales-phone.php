<?php
/** Run with: php -f tools/test-sales-phone.php */

use Uninet\Core\Admin\Settings;

define('ABSPATH', __DIR__);

function get_option($key, $default = false)
{
    return $GLOBALS['test_options'][$key] ?? $default;
}

function wp_parse_args($args, $defaults)
{
    return array_merge($defaults, $args);
}

function check($condition, $message)
{
    if (! $condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

require __DIR__ . '/../wp-content/plugins/uninet-core/includes/Admin/Settings.php';
require __DIR__ . '/../wp-content/plugins/uninet-core/includes/Conversion/WhatsAppButton.php';
require __DIR__ . '/../wp-content/plugins/uninet-core/includes/Setup/SitePages.php';

$whatsapp = '+254770313200';
foreach (['', '0770313200', '0770 313 200', '+254 (770) 313-200', '00254770313200', '0708 990 595'] as $phone) {
    $test_options[Settings::OPTION_KEY] = ['sales_phone' => $phone, 'whatsapp_phone' => $whatsapp];
    check('0708 990 595' === Settings::get('sales_phone'), 'The default or legacy sales setting did not resolve to the new number');
    check('tel:+254708990595' === Settings::sales_tel_url(), 'A new sales call link must be dialable internationally');
    check($whatsapp === Settings::get('whatsapp_phone'), 'The WhatsApp setting must not be changed');
}

foreach (['0708990595', '254708990595', '+254 708 990 595', '00254 708 990 595'] as $phone) {
    check('tel:+254708990595' === Settings::sales_tel_url($phone), 'The country code was not normalized correctly');
}

$test_options[Settings::OPTION_KEY]['sales_phone'] = '+254 700 000 000';
check('+254 700 000 000' === Settings::get('sales_phone'), 'A different configured sales number must be preserved');
check('tel:+254700000000' === Settings::sales_tel_url(), 'A configured sales link must use the configured number');
check('254770313200' === \Uninet\Core\Conversion\WhatsAppButton::PHONE, 'The floating WhatsApp destination must remain unchanged');

$replace_call = new ReflectionMethod(\Uninet\Core\Setup\SitePages::class, 'replace_legacy_call_number');
$legacy_html = '<p>Call <strong>0770313200</strong> for final confirmation. Call us at +254 770 313 200.</p>'
    . '<a href="tel:0770313200">Call us</a>'
    . '<p>WhatsApp <b>0770313200</b> or <a href="https://wa.me/254770313200">chat with us</a>.</p>'
    . '<p>Unrelated product data: 0770313200.</p>';
$updated_html = $replace_call->invoke(null, $legacy_html, '0708 990 595', 'tel:+254708990595');
$expected_html = '<p>Call <strong>0708 990 595</strong> for final confirmation. Call us at 0708 990 595.</p>'
    . '<a href="tel:+254708990595">Call us</a>'
    . '<p>WhatsApp <b>0770313200</b> or <a href="https://wa.me/254770313200">chat with us</a>.</p>'
    . '<p>Unrelated product data: 0770313200.</p>';
check($expected_html === $updated_html, 'Only saved call links and explicit call wording should change');
check($updated_html === $replace_call->invoke(null, $updated_html, '0708 990 595', 'tel:+254708990595'), 'The content migration must be safe to retry');

echo "Sales phone and WhatsApp separation checks passed.\n";
