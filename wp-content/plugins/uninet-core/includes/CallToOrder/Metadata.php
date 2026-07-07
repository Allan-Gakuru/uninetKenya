<?php
/**
 * Call to Order order metadata keys.
 *
 * @package UninetCore
 */

namespace Uninet\Core\CallToOrder;

if (! defined('ABSPATH')) {
    exit;
}

final class Metadata
{
    const ORDER_SOURCE = '_uninet_order_source';
    const FULL_NAME = '_uninet_customer_full_name';
    const PHONE = '_uninet_customer_phone';
    const QUANTITY = '_uninet_quantity_requested';
    const COUNTY = '_uninet_county';
    const TOWN = '_uninet_town';
    const PICKUP_LOCATION = '_uninet_pickup_location';
    const BUSINESS_NAME = '_uninet_business_name';
    const EMAIL = '_uninet_email';
    const KRA_PIN = '_uninet_kra_pin';
    const NOTES = '_uninet_additional_notes';
    const SALES_NUMBER_SHOWN = '_uninet_sales_number_shown';

    /**
     * Source label shown in order metadata.
     */
    public function source_label()
    {
        return 'Call to Order - Product Page';
    }
}
