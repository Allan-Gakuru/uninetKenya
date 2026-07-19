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
     * Add a defensive analytics bridge and declarative conversion tracking.
     */
    public function render_event_bridge()
    {
        echo '<script>(function(){';
        echo 'window.uninetTrack=window.uninetTrack||function(name,params){if(typeof window.gtag==="function"){window.gtag("event",name,params||{});}};';
        echo 'function params(element){var result={};var location=element.getAttribute("data-uninet-track-location");var productId=element.getAttribute("data-product-id");var source=element.getAttribute("data-uninet-track-source");if(location){result.location=location;}if(productId){result.product_id=productId;}if(source){result.source=source;}return result;}';
        echo 'document.addEventListener("click",function(event){var origin=event.target;if(!origin||typeof origin.closest!=="function"){return;}var target=origin.closest("[data-uninet-track]");if(!target){return;}window.uninetTrack(target.getAttribute("data-uninet-track"),params(target));});';
        echo 'function views(){document.querySelectorAll("[data-uninet-track-view]").forEach(function(target){if(target.dataset.uninetTracked){return;}target.dataset.uninetTracked="true";window.uninetTrack(target.getAttribute("data-uninet-track-view"),params(target));});}';
        echo 'if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",views,{once:true});}else{views();}';
        echo '})();</script>';
    }
}
