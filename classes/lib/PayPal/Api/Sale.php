<?php

namespace PayPal\Api;

use PayPal\Common\PayPalResourceModel;
use PayPal\Rest\ApiContext;
use PayPal\Transport\PayPalRestCall;
use PayPal\Validation\ArgumentValidator;

/**
 * Class Sale
 *
 * A sale transaction.
 *
 * @package PayPal\Api
 *
 * @property string id
 * @property string purchase_unit_reference_id
 * @property string create_time
 * @property string update_time
 * @property \PayPal\Api\Amount amount
 * @property string payment_mode
 * @property string pending_reason
 * @property string state
 * @property string reason_code
 * @property string protection_eligibility
 * @property string protection_eligibility_type
 * @property string clearing_time
 * @property string recipient_fund_status
 * @property string hold_reason
 * @property \PayPal\Api\Currency transaction_fee
 * @property \PayPal\Api\Currency receivable_amount
 * @property string exchange_rate
 * @property \PayPal\Api\FmfDetails fmf_details
 * @property string receipt_id
 * @property string parent_payment
 * @property \PayPal\Api\Links[] links
 */
class Sale extends PayPalResourceModel
{
    /**
     * Identifier of the sale transaction.
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
     * Identifier of the sale transaction.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Identifier to the purchase unit corresponding to this sale transaction.
     *
     * @param string $purchase_unit_reference_id
     * 
     * @return $this
     */
    public function setPurchaseUnitReferenceId($purchase_unit_reference_id)
    {
        $this->purchase_unit_reference_id = $purchase_unit_reference_id;
        return $this;
    }

    /**
     * Identifier to the purchase unit corresponding to this sale transaction.
     *
     * @return string
     */
    public function getPurchaseUnitReferenceId()
    {
        return $this->purchase_unit_reference_id;
    }

    /**
     * Amount being collected.
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
     * Amount being collected.
     *
     * @return \PayPal\Api\Amount
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * specifies payment mode of the transaction
     * Valid Values: ["INSTANT_TRANSFER", "MANUAL_BANK_TRANSFER", "DELAYED_TRANSFER", "ECHECK"]
     *
     * @param string $payment_mode
     * 
     * @return $this
     */
    public function setPaymentMode($payment_mode)
    {
        $this->payment_mode = $payment_mode;
        return $this;
    }

    /**
     * specifies payment mode of the transaction
     *
     * @return string
     */
    public function getPaymentMode()
    {
        return $this->payment_mode;
    }

    /**
     * Reason of Pending transaction.
     *
     *
     *
     * @param  string  $pending_reason
     * @return $this
     */
    public function setPendingReason($pending_reason)
    {
        $this->pending_reason = $pending_reason;
        return $this;
    }

    /**
     * Reason of Pending transaction.
     *
     * @return string
     */
    public function getPendingReason()
    {
        return $this->pending_reason;
    }

    /**
     * State of the sale transaction.
     * Valid Values: ["completed", "partially_refunded", "pending", "refunded"]
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
     * State of the sale transaction.
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Reason code for the transaction state being Pending or Reversed.
     * Valid Values: ["CHARGEBACK", "GUARANTEE", "BUYER_COMPLAINT", "REFUND", "UNCONFIRMED_SHIPPING_ADDRESS", "ECHECK", "INTERNATIONAL_WITHDRAWAL", "RECEIVING_PREFERENCE_MANDATES_MANUAL_ACTION", "PAYMENT_REVIEW", "REGULATORY_REVIEW", "UNILATERAL", "VERIFICATION_REQUIRED"]
     *
     * @param string $reason_code
     * 
     * @return $this
     */
    public function setReasonCode($reason_code)
    {
        $this->reason_code = $reason_code;
        return $this;
    }

    /**
     * Reason code for the transaction state being Pending or Reversed.
     *
     * @return string
     */
    public function getReasonCode()
    {
        return $this->reason_code;
    }

    /**
     * Protection Eligibility of the Payer 
     * Valid Values: ["ELIGIBLE", "PARTIALLY_ELIGIBLE", "INELIGIBLE"]
     *
     * @param string $protection_eligibility
     * 
     * @return $this
     */
    public function setProtectionEligibility($protection_eligibility)
    {
        $this->protection_eligibility = $protection_eligibility;
        return $this;
    }

    /**
     * Protection Eligibility of the Payer 
     *
     * @return string
     */
    public function getProtectionEligibility()
    {
        return $this->protection_eligibility;
    }

    /**
     * Protection Eligibility Type of the Payer 
     * Valid Values: ["ELIGIBLE", "ITEM_NOT_RECEIVED_ELIGIBLE", "INELIGIBLE", "UNAUTHORIZED_PAYMENT_ELIGIBLE"]
     *
     * @param string $protection_eligibility_type
     * 
     * @return $this
     */
    public function setProtectionEligibilityType($protection_eligibility_type)
    {
        $this->protection_eligibility_type = $protection_eligibility_type;
        return $this;
    }

    /**
     * Protection Eligibility Type of the Payer 
     *
     * @return string
     */
    public function getProtectionEligibilityType()
    {
        return $this->protection_eligibility_type;
    }

    /**
     * Expected clearing time for eCheck Transactions
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
     * Expected clearing time for eCheck Transactions
     *
     * @return string
     */
    public function getClearingTime()
    {
        return $this->clearing_time;
    }

    /**
     * Indicates the credit status of fund to the recipient. It will be returned only when payment status is 'completed' 
     * Valid Values: ["COMPLETED", "HELD"]
     *
     * @param string $recipient_fund_status
     * 
     * @return $this
     */
    public function setRecipientFundStatus($recipient_fund_status)
    {
        $this->recipient_fund_status = $recipient_fund_status;
        return $this;
    }

    /**
     * Indicates the credit status of fund to the recipient. It will be returned only when payment status is 'completed' 
     *
     * @return string
     */
    public function getRecipientFundStatus()
    {
        return $this->recipient_fund_status;
    }

    /**
     * Reason for holding the funds.
     * Valid Values: ["NEW_SELLER_PAYMENT_HOLD", "PAYMENT_HOLD"]
     *
     * @param string $hold_reason
     * 
     * @return $this
     */
    public function setHoldReason($hold_reason)
    {
        $this->hold_reason = $hold_reason;
        return $this;
    }

    /**
     * Reason for holding the funds.
     *
     * @return string
     */
    public function getHoldReason()
    {
        return $this->hold_reason;
    }

    /**
     * Transaction fee applicable for this payment.
     *
     * @param \PayPal\Api\Currency $transaction_fee
     * 
     * @return $this
     */
    public function setTransactionFee($transaction_fee)
    {
        $this->transaction_fee = $transaction_fee;
        return $this;
    }

    /**
     * Transaction fee applicable for this payment.
     *
     * @return \PayPal\Api\Currency
     */
    public function getTransactionFee()
    {
        return $this->transaction_fee;
    }

    /**
     * Net amount payee receives for this transaction after deducting transaction fee.
     *
     * @param \PayPal\Api\Currency $receivable_amount
     * 
     * @return $this
     */
    public function setReceivableAmount($receivable_amount)
    {
        $this->receivable_amount = $receivable_amount;
        return $this;
    }

    /**
     * Net amount payee receives for this transaction after deducting transaction fee.
     *
     * @return \PayPal\Api\Currency
     */
    public function getReceivableAmount()
    {
        return $this->receivable_amount;
    }

    /**
     * Exchange rate applied for this transaction.
     *
     * @param string $exchange_rate
     * 
     * @return $this
     */
    public function setExchangeRate($exchange_rate)
    {
        $this->exchange_rate = $exchange_rate;
        return $this;
    }

    /**
     * Exchange rate applied for this transaction.
     *
     * @return string
     */
    public function getExchangeRate()
    {
        return $this->exchange_rate;
    }

    /**
     * Fraud Management Filter (FMF) details applied for the payment that could result in accept/deny/pending action.
     *
     * @param \PayPal\Api\FmfDetails $fmf_details
     * 
     * @return $this
     */
    public function setFmfDetails($fmf_details)
    {
        $this->fmf_details = $fmf_details;
        return $this;
    }

    /**
     * Fraud Management Filter (FMF) details applied for the payment that could result in accept/deny/pending action.
     *
     * @return \PayPal\Api\FmfDetails
     */
    public function getFmfDetails()
    {
        return $this->fmf_details;
    }

    /**
     * Receipt id is 16 digit number payment identification number returned for guest users to identify the payment.
     *
     * @param string $receipt_id
     * 
     * @return $this
     */
    public function setReceiptId($receipt_id)
    {
        $this->receipt_id = $receipt_id;
        return $this;
    }

    /**
     * Receipt id is 16 digit number payment identification number returned for guest users to identify the payment.
     *
     * @return string
     */
    public function getReceiptId()
    {
        return $this->receipt_id;
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
     * Retrieve details about a sale transaction by passing the sale_id in the request URI. This request returns only the sales that were created via the REST API.
     *
     * @param string $saleId
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return Sale
     */
    public static function get($saleId, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($saleId, 'saleId');
        $payLoad = "";
        $json = self::executeCall(
            "/v1/payments/sale/$saleId",
            "GET",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new Sale();
        $ret->fromJson($json);
        return $ret;
    }

    /**
     * Refund a completed payment by passing the sale_id in the request URI. In addition, include an empty JSON payload in the request body for a full refund. For a partial refund, include an amount object in the request body.
     *
     * @param Refund $refund
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return Refund
     */
    public function refund($refund, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($this->getId(), "Id");
        ArgumentValidator::validate($refund, 'refund');
        $payLoad = $refund->toJSON();
        $json = self::executeCall(
            "/v1/payments/sale/{$this->getId()}/refund",
            "POST",
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
