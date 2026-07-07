<?php
/**
 * Call to Order validation shell.
 *
 * @package UninetCore
 */

namespace Uninet\Core\CallToOrder;

if (! defined('ABSPATH')) {
    exit;
}

final class Validation
{
    /**
     * Validate submitted data. Full implementation lands in the Call to Order phase.
     *
     * @param array $data Submitted data.
     */
    public function validate(array $data)
    {
        return new \WP_Error(
            'uninet_not_implemented',
            __('Call to Order submission is not implemented yet.', 'uninet-core')
        );
    }
}
