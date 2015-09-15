<?php

namespace PayPal\Api;

use PayPal\Common\PayPalModel;

/**
 * Class RelatedResources
 *
 * Each one representing a financial transaction (Sale, Authorization, Capture, Refund) related to the payment.
 *
 * @package PayPal\Api
 *
 * @property \PayPal\Api\Sale sale
 * @property \PayPal\Api\Authorization authorization
 * @property \PayPal\Api\Order order
 * @property \PayPal\Api\Capture capture
 * @property \PayPal\Api\Refund refund
 */
class RelatedResources extends PayPalModel
{
    /**
     * A sale transaction
     *
     *
     * @param \PayPal\Api\Sale $sale
     *
     * @return $this
     */
    public function setSale($sale)
    {
        $this->sale = $sale;
        return $this;
    }

    /**
     * A sale transaction
     *
     * @return \PayPal\Api\Sale
     */
    public function getSale()
    {
        return $this->sale;
    }

    /**
     * An authorization transaction
     *
     *
     * @param \PayPal\Api\Authorization $authorization
     *
     * @return $this
     */
    public function setAuthorization($authorization)
    {
        $this->authorization = $authorization;
        return $this;
    }

    /**
     * An authorization transaction
     *
     * @return \PayPal\Api\Authorization
     */
    public function getAuthorization()
    {
        return $this->authorization;
    }

    /**
     * An order transaction
     *
     *
     * @param \PayPal\Api\Order $order
     *
     * @return $this
     */
    public function setOrder($order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * An order transaction
     *
     * @return \PayPal\Api\Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * A capture transaction
     *
     *
     * @param \PayPal\Api\Capture $capture
     *
     * @return $this
     */
    public function setCapture($capture)
    {
        $this->capture = $capture;
        return $this;
    }

    /**
     * A capture transaction
     *
     * @return \PayPal\Api\Capture
     */
    public function getCapture()
    {
        return $this->capture;
    }

    /**
     * A refund transaction
     *
     *
     * @param \PayPal\Api\Refund $refund
     *
     * @return $this
     */
    public function setRefund($refund)
    {
        $this->refund = $refund;
        return $this;
    }

    /**
     * A refund transaction
     *
     * @return \PayPal\Api\Refund
     */
    public function getRefund()
    {
        return $this->refund;
    }

}
