<?php
/**
 * Shared sanitization helpers.
 *
 * @package UninetCore
 */

namespace Uninet\Core\Helpers;

if (! defined('ABSPATH')) {
    exit;
}

final class Sanitization
{
    /**
     * Normalize a phone-ish string while keeping useful dialing characters.
     *
     * @param string $value Raw value.
     */
    public static function phone($value)
    {
        $value = sanitize_text_field($value);

        return preg_replace('/[^0-9+()\-\s]/', '', $value);
    }

    /**
     * Return a positive integer.
     *
     * @param mixed $value Raw value.
     */
    public static function positive_int($value)
    {
        return max(1, absint($value));
    }
}
