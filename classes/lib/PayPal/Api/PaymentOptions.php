<?php

namespace PayPal\Api;

use PayPal\Common\PayPalModel;

/**
 * Class PaymentOptions
 *
 * Payment options requested for this purchase unit
 *
 * @package PayPal\Api
 *
 * @property string allowed_payment_method
 */
class PaymentOptions extends PayPalModel
{
    /**
     * Payment method requested for this purchase unit
     * Valid Values: ["UNRESTRICTED", "INSTANT_FUNDING_SOURCE", "IMMEDIATE_PAY"]
     *
     * @param string $allowed_payment_method
     * 
     * @return $this
     */
    public function setAllowedPaymentMethod($allowed_payment_method)
    {
        $this->allowed_payment_method = $allowed_payment_method;
        return $this;
    }

    /**
     * Payment method requested for this purchase unit
     *
     * @return string
     */
    public function getAllowedPaymentMethod()
    {
        return $this->allowed_payment_method;
    }

}
