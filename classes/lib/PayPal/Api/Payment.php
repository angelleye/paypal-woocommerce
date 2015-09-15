<?php

namespace PayPal\Api;

use PayPal\Common\PayPalResourceModel;
use PayPal\Core\PayPalConstants;
use PayPal\Rest\ApiContext;
use PayPal\Transport\PayPalRestCall;
use PayPal\Validation\ArgumentValidator;

/**
 * Class Payment
 *
 * Lets you create, process and manage payments.
 *
 * @package PayPal\Api
 *
 * @property string id
 * @property string create_time
 * @property string update_time
 * @property string intent
 * @property \PayPal\Api\Payer payer
 * @property \PayPal\Api\Payee payee
 * @property string cart
 * @property \PayPal\Api\Transaction[] transactions
 * @property \PayPal\Api\Error[] failed_transactions
 * @property \PayPal\Api\PaymentInstruction payment_instruction
 * @property string state
 * @property \PayPal\Api\RedirectUrls redirect_urls
 * @property string experience_profile_id
 */
class Payment extends PayPalResourceModel
{
    /**
     * Identifier of the payment resource created.
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
     * Identifier of the payment resource created.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Intent of the payment - Sale or Authorization or Order.
     * Valid Values: ["sale", "authorize", "order"]
     *
     * @param string $intent
     * 
     * @return $this
     */
    public function setIntent($intent)
    {
        $this->intent = $intent;
        return $this;
    }

    /**
     * Intent of the payment - Sale or Authorization or Order.
     *
     * @return string
     */
    public function getIntent()
    {
        return $this->intent;
    }

    /**
     * Source of the funds for this payment represented by a PayPal account or a direct credit card.
     *
     * @param \PayPal\Api\Payer $payer
     * 
     * @return $this
     */
    public function setPayer($payer)
    {
        $this->payer = $payer;
        return $this;
    }

    /**
     * Source of the funds for this payment represented by a PayPal account or a direct credit card.
     *
     * @return \PayPal\Api\Payer
     */
    public function getPayer()
    {
        return $this->payer;
    }

    /**
     * .
     *
     * @param \PayPal\Api\Payee $payee
     * 
     * @return $this
     */
    public function setPayee($payee)
    {
        $this->payee = $payee;
        return $this;
    }

    /**
     * .
     *
     * @return \PayPal\Api\Payee
     */
    public function getPayee()
    {
        return $this->payee;
    }

    /**
     * ID of the cart to execute the payment.
     *
     * @param string $cart
     * 
     * @return $this
     */
    public function setCart($cart)
    {
        $this->cart = $cart;
        return $this;
    }

    /**
     * ID of the cart to execute the payment.
     *
     * @return string
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * A payment can have more than one transaction, with each transaction establishing a contract between the payer and a payee
     *
     * @param \PayPal\Api\Transaction[] $transactions
     * 
     * @return $this
     */
    public function setTransactions($transactions)
    {
        $this->transactions = $transactions;
        return $this;
    }

    /**
     * A payment can have more than one transaction, with each transaction establishing a contract between the payer and a payee
     *
     * @return \PayPal\Api\Transaction[]
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * Append Transactions to the list.
     *
     * @param \PayPal\Api\Transaction $transaction
     * @return $this
     */
    public function addTransaction($transaction)
    {
        if (!$this->getTransactions()) {
            return $this->setTransactions(array($transaction));
        } else {
            return $this->setTransactions(
                array_merge($this->getTransactions(), array($transaction))
            );
        }
    }

    /**
     * Remove Transactions from the list.
     *
     * @param \PayPal\Api\Transaction $transaction
     * @return $this
     */
    public function removeTransaction($transaction)
    {
        return $this->setTransactions(
            array_diff($this->getTransactions(), array($transaction))
        );
    }

    /**
     * Applicable for advanced payments like multi seller payment (MSP) to support partial failures
     *
     * @param \PayPal\Api\Error[] $failed_transactions
     * 
     * @return $this
     */
    public function setFailedTransactions($failed_transactions)
    {
        $this->failed_transactions = $failed_transactions;
        return $this;
    }

    /**
     * Applicable for advanced payments like multi seller payment (MSP) to support partial failures
     *
     * @return \PayPal\Api\Error[]
     */
    public function getFailedTransactions()
    {
        return $this->failed_transactions;
    }

    /**
     * Append FailedTransactions to the list.
     *
     * @param \PayPal\Api\Error $error
     * @return $this
     */
    public function addFailedTransaction($error)
    {
        if (!$this->getFailedTransactions()) {
            return $this->setFailedTransactions(array($error));
        } else {
            return $this->setFailedTransactions(
                array_merge($this->getFailedTransactions(), array($error))
            );
        }
    }

    /**
     * Remove FailedTransactions from the list.
     *
     * @param \PayPal\Api\Error $error
     * @return $this
     */
    public function removeFailedTransaction($error)
    {
        return $this->setFailedTransactions(
            array_diff($this->getFailedTransactions(), array($error))
        );
    }

    /**
     * A payment instruction resource
     *
     * @param \PayPal\Api\PaymentInstruction $payment_instruction
     * 
     * @return $this
     */
    public function setPaymentInstruction($payment_instruction)
    {
        $this->payment_instruction = $payment_instruction;
        return $this;
    }

    /**
     * A payment instruction resource
     *
     * @return \PayPal\Api\PaymentInstruction
     */
    public function getPaymentInstruction()
    {
        return $this->payment_instruction;
    }

    /**
     * state of the payment
     * Valid Values: ["created", "approved", "completed", "partially_completed", "failed", "canceled", "expired", "in_progress"]
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
     * state of the payment
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Identifier for the payment experience.
     *
     * @param string $experience_profile_id
     * 
     * @return $this
     */
    public function setExperienceProfileId($experience_profile_id)
    {
        $this->experience_profile_id = $experience_profile_id;
        return $this;
    }

    /**
     * Identifier for the payment experience.
     *
     * @return string
     */
    public function getExperienceProfileId()
    {
        return $this->experience_profile_id;
    }

    /**
     * Redirect urls required only when using payment_method as PayPal - the only settings supported are return and cancel urls.
     *
     * @param \PayPal\Api\RedirectUrls $redirect_urls
     * 
     * @return $this
     */
    public function setRedirectUrls($redirect_urls)
    {
        $this->redirect_urls = $redirect_urls;
        return $this;
    }

    /**
     * Redirect urls required only when using payment_method as PayPal - the only settings supported are return and cancel urls.
     *
     * @return \PayPal\Api\RedirectUrls
     */
    public function getRedirectUrls()
    {
        return $this->redirect_urls;
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
     * Get Approval Link
     *
     * @return null|string
     */
    public function getApprovalLink()
    {
        return $this->getLink(PayPalConstants::APPROVAL_URL);
    }

    /**
     * Create and process a payment by passing a payment object that includes the intent, payer, and transactions in the body of the request JSON. For PayPal payments, include redirect URLs in the payment object.
     *
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return Payment
     */
    public function create($apiContext = null, $restCall = null)
    {
        $payLoad = $this->toJSON();
        $json = self::executeCall(
            "/v1/payments/payment",
            "POST",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $this->fromJson($json);
        return $this;
    }

    /**
     * Look up a particular payment resource by passing the payment_id in the request URI.
     *
     * @param string $paymentId
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return Payment
     */
    public static function get($paymentId, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($paymentId, 'paymentId');
        $payLoad = "";
        $json = self::executeCall(
            "/v1/payments/payment/$paymentId",
            "GET",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new Payment();
        $ret->fromJson($json);
        return $ret;
    }

    /**
     * Partially update a payment resource by by passing the payment_id in the request URI. In addition, pass a patch_request_object in the body of the request JSON that specifies the operation to perform, path of the target location, and new value to apply. Please note that it is not possible to use patch after execute has been called.
     *
     * @param PatchRequest $patchRequest
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return boolean
     */
    public function update($patchRequest, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($this->getId(), "Id");
        ArgumentValidator::validate($patchRequest, 'patchRequest');
        $payLoad = $patchRequest->toJSON();
        self::executeCall(
            "/v1/payments/payment/{$this->getId()}",
            "PATCH",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        return true;
    }

    /**
     * Execute (complete) a PayPal payment that has been approved by the payer by passing the payment_id in the request URI. This request only works after a buyer has approved the payment using the provided PayPal approval URL. Optionally update transaction information when executing the payment by passing in one or more transactions.
     *
     * @param PaymentExecution $paymentExecution
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return Payment
     */
    public function execute($paymentExecution, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($this->getId(), "Id");
        ArgumentValidator::validate($paymentExecution, 'paymentExecution');
        $payLoad = $paymentExecution->toJSON();
        $json = self::executeCall(
            "/v1/payments/payment/{$this->getId()}/execute",
            "POST",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $this->fromJson($json);
        return $this;
    }

    /**
     * List payments in any state (created, approved, failed, etc.). Payments returned are the payments made to the merchant issuing the request.
     *
     * @param array $params
     * @param ApiContext $apiContext is the APIContext for this call. It can be used to pass dynamic configuration and credentials.
     * @param PayPalRestCall $restCall is the Rest Call Service that is used to make rest calls
     * @return PaymentHistory
     */
    public static function all($params, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($params, 'params');
        $payLoad = "";
        $allowedParams = array(
            'count' => 1,
            'start_id' => 1,
            'start_index' => 1,
            'start_time' => 1,
            'end_time' => 1,
            'payee_id' => 1,
            'sort_by' => 1,
            'sort_order' => 1,
        );
        $json = self::executeCall(
            "/v1/payments/payment?" . http_build_query(array_intersect_key($params, $allowedParams)),
            "GET",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new PaymentHistory();
        $ret->fromJson($json);
        return $ret;
    }

}
