<?php

namespace PayPal\Api;

use PayPal\Common\PayPalResourceModel;
use PayPal\Rest\ApiContext;
use PayPal\Transport\PayPalRestCall;
use PayPal\Validation\ArgumentValidator;

/**
 * Class Refund
 *
 * A refund transaction.
 *
 * @package PayPal\Api
 *
 * @property string id
 * @property string create_time
 * @property string update_time
 * @property \PayPal\Api\Amount amount
 * @property string state
 * @property string reason
 * @property string sale_id
 * @property string capture_id
 * @property string parent_payment
 * @property string description
 * @property \PayPal\Api\Links[] links
 */
class Refund extends PayPalResourceModel
{
    /**
     * Identifier of the refund transaction in UTC ISO8601 format.
     *
     * @param string $id
     * 
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Identifier of the refund transaction in UTC ISO8601 format.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Details including both refunded amount (to Payer) and refunded fee (to Payee).If amount is not specified, it's assumed to be full refund.
     *
     * @param \PayPal\Api\Amount $amount
     * 
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Details including both refunded amount (to Payer) and refunded fee (to Payee).If amount is not specified, it's assumed to be full refund.
     *
     * @return \PayPal\Api\Amount
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * State of the refund transaction.
     * Valid Values: ["pending", "completed", "failed"]
     *
     * @param string $state
     * 
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * State of the refund transaction.
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Reason description for the Sale transaction being refunded.
     *
     * @param string $reason
     * 
     * @return $this
     */
    public function setReason($reason)
    {
        $this->reason = $reason;
        return $this;
    }

    /**
     * Reason description for the Sale transaction being refunded.
     *
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * ID of the Sale transaction being refunded. 
     *
     * @param string $sale_id
     * 
     * @return $this
     */
    public function setSaleId($sale_id)
    {
        $this->sale_id = $sale_id;
        return $this;
    }

    /**
     * ID of the Sale transaction being refunded. 
     *
     * @return string
     */
    public function getSaleId()
    {
        return $this->sale_id;
    }

    /**
     * ID of the Capture transaction being refunded. 
     *
     * @param string $capture_id
     * 
     * @return $this
     */
    public function setCaptureId($capture_id)
    {
        $this->capture_id = $capture_id;
        return $this;
    }

    /**
     * ID of the Capture transaction being refunded. 
     *
     * @return string
     */
    public function getCaptureId()
    {
        return $this->capture_id;
    }

    /**
     * ID of the Payment resource that this transaction is based on.
     *
     * @param string $parent_payment
     * 
     * @return $this
     */
    public function setParentPayment($parent_payment)
    {
        $this->parent_payment = $parent_payment;
        return $this;
    }

    /**
     * ID of the Payment resource that this transaction is based on.
     *
     * @return string
     */
    public function getParentPayment()
    {
        return $this->parent_payment;
    }

    /**
     * Description of what is being refunded for.
     *
     * @param string $description
     * 
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Description of what is being refunded for.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Time the resource was created in UTC ISO8601 format.
     *
     * @param string $create_time
     * 
     * @return $this
     */
    public function setCreateTime($create_time)
    {
        $this->create_time = $create_time;
        return $this;
    }

    /**
     * Time the resource was created in UTC ISO8601 format.
     *
     * @return string
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * Time the resource was last updated in UTC ISO8601 format.
     *
     * @param string $update_time
     * 
     * @return $this
     */
    public function setUpdateTime($update_time)
    {
        $this->update_time = $update_time;
        return $this;
    }

    /**
     * Time the resource was last updated in UTC ISO8601 format.
     *
     * @return string
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }

    /**
     * Retrieve details about a specific refund by passing the refund_id in the request URI.
     *
     * @param string $refundId
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return Refund
     */
    public static function get($refundId, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($refundId, 'refundId');
        $payLoad = "";
        $json = self::executeCall(
            "/v1/payments/refund/$refundId",
            "GET",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new Refund();
        $ret->fromJson($json);
        return $ret;
    }

}
