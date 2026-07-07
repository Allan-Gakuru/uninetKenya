<?php
/**
 * Call to Order order creation shell.
 *
 * @package UninetCore
 */

namespace Uninet\Core\CallToOrder;

if (! defined('ABSPATH')) {
    exit;
}

final class OrderFactory
{
    /**
     * Metadata helper.
     *
     * @var Metadata
     */
    private $metadata;

    /**
     * Constructor.
     *
     * @param Metadata $metadata Metadata helper.
     */
    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Create a WooCommerce pending order. Full implementation lands later.
     *
     * @param array $data Validated data.
     */
    public function create(array $data)
    {
        return new \WP_Error(
            'uninet_not_implemented',
            __('Call to Order order creation is not implemented yet.', 'uninet-core')
        );
    }
}
