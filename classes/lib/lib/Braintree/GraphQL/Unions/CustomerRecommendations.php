<?php

namespace Braintree\GraphQL\Unions;

use Braintree\Base;

/**
 * A union of all possible customer recommendations associated with a PayPal customer session.
 *
 * @experimental This class is experimental and may change in future releases.
 */
class CustomerRecommendations extends Base
{
    // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
    protected function _initialize($attributes)
    {
        $this->_attributes = $attributes;

        if (isset($attributes['paymentRecommendations'])) {
            $this->_set('paymentOptions', $attributes['paymentRecommendations']);
        }

        if (isset($attributes['paymentRecommendations'])) {
            $this->_set('paymentRecommendations', $attributes['paymentRecommendations']);
        }
    }

    // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
    public static function factory($attributes)
    {
        $instance = new self();
        $instance->_initialize($attributes);
        return $instance;
    }
}
