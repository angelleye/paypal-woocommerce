<?php

namespace PayPal\Api;

use PayPal\Common\PayPalModel;

/**
 * Class FundingDetail
 *
 * Additional detail of the funding.
 *
 * @package PayPal\Api
 *
 * @property string clearing_time
 * @property string payment_hold_date
 */
class FundingDetail extends PayPalModel
{
    /**
     * Expected clearing time
     *
     * @param string $clearing_time
     * 
     * @return $this
     */
    public function setClearingTime($clearing_time)
    {
        $this->clearing_time = $clearing_time;
        return $this;
    }

    /**
     * Expected clearing time
     *
     * @return string
     */
    public function getClearingTime()
    {
        return $this->clearing_time;
    }

    /**
     * Hold-off duration of the payment
     *
     * @param string $payment_hold_date
     * 
     * @return $this
     */
    public function setPaymentHoldDate($payment_hold_date)
    {
        $this->payment_hold_date = $payment_hold_date;
        return $this;
    }

    /**
     * Hold-off duration of the payment
     *
     * @return string
     */
    public function getPaymentHoldDate()
    {
        return $this->payment_hold_date;
    }

}
