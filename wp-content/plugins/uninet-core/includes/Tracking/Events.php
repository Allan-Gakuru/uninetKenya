<?php
/**
 * Tracking event shell.
 *
 * @package UninetCore
 */

namespace Uninet\Core\Tracking;

if (! defined('ABSPATH')) {
    exit;
}

final class Events
{
    /**
     * Register hooks.
     */
    public function register()
    {
        add_action('wp_footer', [$this, 'render_event_bridge']);
    }

    /**
     * Add a tiny defensive event bridge for future custom events.
     */
    public function render_event_bridge()
    {
        echo '<script>window.uninetTrack=window.uninetTrack||function(name,params){if(typeof window.gtag==="function"){window.gtag("event",name,params||{});}};</script>';
    }
}
