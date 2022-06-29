<?php

namespace Braintree;

/**
 * @property-read string $customerDeviceId
 * @property-read string $customerLocationZip
 * @property-read string $customerTenure
 * @property-read string $decision
 * @property-read array $decisionReasons
 * @property-read boolean $deviceDataCaptured
 * @property-read string $id
 * @property-read string $transactionRiskScore
 */
class RiskData extends Base
{
    public static function factory($attributes)
    {
        $instance = new self();
        $instance->_initialize($attributes);

        return $instance;
    }

    protected function _initialize($attributes)
    {
        $this->_attributes = $attributes;
    }

    public function decisionReasons()
    {
        return $this->_attributes['decisionReasons'];
    }


    /**
     * returns a string representation of the risk data
     * @return string
     */
    public function __toString()
    {
        return __CLASS__ . '[' .
                Util::attributesToString($this->_attributes) . ']';
    }
}
