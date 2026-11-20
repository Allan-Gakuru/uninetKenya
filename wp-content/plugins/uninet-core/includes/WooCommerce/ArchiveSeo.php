<?php
/**
 * Search metadata defaults for the public product categories and brand archives.
 *
 * @package UninetCore
 */

namespace Uninet\Core\WooCommerce;

use RankMath\Helper;
use RankMath\Term;

if (! defined('ABSPATH')) {
    exit;
}

final class ArchiveSeo
{
    private const COPY = [
        'product_cat' => [
            'power-backup-solutions' => ['Power Backup Solutions', 'Explore power backup options for office computers and network equipment in Kenya. Ask Uninet about runtime, sizing and a quote for your setup.'],
            'accessories-and-cables' => ['Computer Accessories and Cables', 'Find computer accessories and cables for workstations in Kenya. Compare mice, keyboards and laptop stands, or ask Uninet about compatibility.'],
            'computer-mouse' => ['Computer Mice', 'Looking for computer mice in Kenya? Ask Uninet about wired and wireless options for shared desks, everyday office work and team rollouts.'],
            'keyboards' => ['Computer Keyboards', 'Source computer keyboards for office desks in Kenya. Ask Uninet about wired or wireless connections, layouts and quantities for your team.'],
            'laptop-stands' => ['Laptop Stands', 'Find a laptop stand for your desk in Kenya. Ask Uninet about suitable options for your laptop size and workstation setup.'],
            'mousepads' => ['Mousepads', 'Source mousepads for office workstations in Kenya. Ask Uninet about sizes, desk fit and ordering for multiple workstations.'],
            'security-products' => ['CCTV and Security Equipment', 'Plan a CCTV or access control setup for your business in Kenya. Explore cameras and recorders, or ask Uninet for help choosing compatible equipment.'],
            'access-control' => ['Access Control Equipment', 'Source access control equipment for offices and commercial premises in Kenya. Ask Uninet about door types, entry methods and compatible hardware.'],
            'cctv-cameras' => ['CCTV Cameras', 'Looking for CCTV cameras in Kenya? Ask Uninet about indoor or outdoor coverage and compatibility with your existing or planned recorder.'],
            'digital-video-recorders' => ['CCTV Digital Video Recorders', 'Source CCTV digital video recorders in Kenya. Ask Uninet about channel count, camera compatibility and recording needs for your premises.'],
            'desktops' => ['Desktop Computers', 'Explore desktop computers for offices and workstations in Kenya. Ask Uninet to help match a desktop and monitor setup to your team.'],
            'all-in-one-computers' => ['All-in-One Computers', 'Looking for all-in-one computers in Kenya? Ask Uninet about integrated desktop and display options for offices, reception and shared workspaces.'],
            'complete-desktop-computers' => ['Complete Desktop PCs', 'Plan a complete desktop setup in Kenya. Ask Uninet which computers, monitors, keyboards and mice can be quoted together for your team.'],
            'high-end-desktop-computers' => ['High-End Desktop Computers', 'Source high-end desktop computers in Kenya. Share your software and workload with Uninet to discuss a suitable PC configuration and quote.'],
            'office-desktop-computers' => ['Office Desktop Computers', 'Equip your office with desktop computers in Kenya. Ask Uninet about options for admin work, shared desks and multi-user office rollouts.'],
            'laptops' => ['Laptops', 'Compare business and office laptops in Kenya. Browse by use case, request a quote, or call Uninet Technologies to confirm availability and pricing.'],
            'business-laptops' => ['Business Laptops', 'Compare business laptops in Kenya for office, hybrid and field teams. Request a quote from Uninet for a single device or a staff rollout.'],
            'entry-level-laptops' => ['Entry-Level Laptops', 'Browse entry-level laptops in Kenya for email, web apps and daily office tasks. Ask Uninet to help match your budget and workload.'],
            'executive-laptops' => ['Executive Laptops', 'Browse executive laptops in Kenya for meetings, travel and hybrid work. Ask Uninet about portable options and request a quote for your team.'],
            'laptops-for-power-users' => ['Laptops for Power Users', 'Browse laptops for demanding workloads in Kenya. Tell Uninet which applications you use so the team can help match a device to your work.'],
            'student-laptops' => ['Student Laptops', 'Browse student laptops in Kenya for coursework, online learning and everyday use. Ask Uninet about options that suit your course and budget.'],
            'monitors' => ['Computer Monitors', 'Looking for computer monitors in Kenya? Ask Uninet about screen sizes, connections and options for single or dual-monitor office desks.'],
            'networking' => ['Networking Equipment', 'Source networking equipment for small businesses in Kenya. Ask Uninet about switches, wireless connectivity and fit with your current network.'],
            'printers' => ['Printers and Office Equipment', 'Browse printers and office equipment in Kenya. Compare ink tank and laser printing options, or request a quote based on your print volume.'],
            'ink-tank' => ['Ink Tank Printers', 'Browse ink tank printers in Kenya for offices and shared workspaces. Ask Uninet to help compare print volume, refill needs and running costs.'],
            'laser-printers' => ['Laser Printers', 'Browse laser printers in Kenya for office documents and shared desks. Ask Uninet about print volume, connectivity and toner needs.'],
        ],
        'product_brand' => [
            'brother' => ['Brother Printers', 'Looking for Brother printers in Kenya? Ask Uninet about options for shared office printing, supplies and a business quote.'],
            'canon' => ['Canon Products', 'Browse Canon products in Kenya for business and office use. Ask Uninet about model availability, consumables and a quote for your team.'],
            'dell' => ['Dell Products', 'Browse Dell products in Kenya for business IT procurement. Compare current catalogue options and request a quote from Uninet.'],
            'epson' => ['Epson Products', 'Looking for Epson equipment in Kenya? Ask Uninet about office printing options, consumables and a quote for your requirements.'],
            'hik-vision' => ['Hikvision Security Equipment', 'Source Hikvision CCTV equipment in Kenya. Ask Uninet about camera and recorder compatibility for a new installation or an existing system.'],
            'hp' => ['HP Products', 'Browse HP products for business use in Kenya. Compare the current Uninet catalogue and request a quote for your office or team.'],
            'kyocera' => ['Kyocera Printers', 'Looking for Kyocera printers in Kenya? Ask Uninet about office print volumes, supply requirements and a business quote.'],
            'lenovo' => ['Lenovo Products', 'Browse Lenovo products for business use in Kenya. Compare current catalogue options and ask Uninet about a quote for your team.'],
        ],
    ];

    public function register()
    {
        add_filter('rank_math/frontend/title', [$this, 'filter_title']);
        add_filter('rank_math/frontend/description', [$this, 'filter_description']);
    }

    public function filter_title($title)
    {
        $term = $this->get_archive_term();

        if (! $term || '' !== Term::get_meta('title', $term, $term->taxonomy)) {
            return $title;
        }

        $label = self::COPY[$term->taxonomy][$term->slug][0];

        return Helper::replace_vars($label . ' in Kenya %page% %sep% %sitename%', $term);
    }

    public function filter_description($description)
    {
        $term = $this->get_archive_term();

        if (! $term || '' !== Term::get_meta('description', $term, $term->taxonomy)) {
            return $description;
        }

        return self::COPY[$term->taxonomy][$term->slug][1];
    }

    private function get_archive_term()
    {
        if (! is_tax(['product_cat', 'product_brand'])) {
            return null;
        }

        $term = get_queried_object();

        return $term instanceof \WP_Term && isset(self::COPY[$term->taxonomy][$term->slug]) ? $term : null;
    }
}
