<?php

namespace Braintree;

/**
 * Braintree LocalPayment module
 *
 * Represents a local payment context
 */
class LocalPayment extends Base
{
    protected $_attributes = [
        'amount' => null,
        'approvalUrl' => '',
        'approvedAt' => '',
        'createdAt' => '',
        'expiredAt' => '',
        'id' => '',
        'legacyId' => '',
        'merchantAccountId' => '',
        'orderId' => '',
        'paymentId' => '',
        'transactedAt' => '',
        'type' => '',
        'updatedAt' => ''
    ];

    protected function _initialize($localPaymentAttribs)
    {
        $this->_attributes = $localPaymentAttribs;

        if (isset($localPaymentAttribs['amount'])) {
            $this->_set('amount', MonetaryAmount::factory($localPaymentAttribs['amount']));
        }

        if (isset($localPaymentAttribs['approvalUrl'])) {
            $this->_set('approvalUrl', $localPaymentAttribs['approvalUrl']);
        }

        if (isset($localPaymentAttribs['approvedAt'])) {
            $this->_set('approvedAt', $localPaymentAttribs['approvedAt']);
        }

        if (isset($localPaymentAttribs['createdAt'])) {
            $this->_set('createdAt', $localPaymentAttribs['createdAt']);
        }

        if (isset($localPaymentAttribs['expiredAt'])) {
            $this->_set('expiredAt', $localPaymentAttribs['expiredAt']);
        }

        if (isset($localPaymentAttribs['id'])) {
            $this->_set('id', $localPaymentAttribs['id']);
        }

        if (isset($localPaymentAttribs['legacyId'])) {
            $this->_set('legacyId', $localPaymentAttribs['legacyId']);
        }

        if (isset($localPaymentAttribs['merchantAccountId'])) {
            $this->_set('merchantAccountId', $localPaymentAttribs['merchantAccountId']);
        }

        if (isset($localPaymentAttribs['orderId'])) {
            $this->_set('orderId', $localPaymentAttribs['orderId']);
        }

        if (isset($localPaymentAttribs['paymentId'])) {
            $this->_set('paymentId', $localPaymentAttribs['paymentId']);
        }

        if (isset($localPaymentAttribs['transactedAt'])) {
            $this->_set('transactedAt', $localPaymentAttribs['transactedAt']);
        }

        if (isset($localPaymentAttribs['type'])) {
            $this->_set('type', $localPaymentAttribs['type']);
        }

        if (isset($localPaymentAttribs['updatedAt'])) {
            $this->_set('updatedAt', $localPaymentAttribs['updatedAt']);
        }
    }

    /**
     * Creates an instance of LocalPayment from given attributes
     *
     * @param array $attributes response object attributes
     *
     * @return LocalPayment
     */
    public static function factory($attributes)
    {
        $instance = new self();
        $instance->_initialize($attributes);
        return $instance;
    }

    /**
     * String representation of LocalPayment object
     *
     * @return string
     */
    public function __toString()
    {
        return __CLASS__ . '[' .
                Util::attributesToString($this->_attributes) . ']';
    }
}
