<?php

namespace PayPal\Api;

use PayPal\Common\PayPalModel;

/**
 * Class FundingInstrument
 *
 * A resource representing a Payer's funding instrument.
 *
 * @package PayPal\Api
 *
 * @property \PayPal\Api\CreditCard credit_card
 * @property \PayPal\Api\ExtendedBankAccount bank_account
 * @property \PayPal\Api\CreditCardToken credit_card_token
 * @property \PayPal\Api\Incentive incentive
 * @property \PayPal\Api\PaymentCard payment_card
 * @property \PayPal\Api\PaymentCardToken payment_card_token
 * @property \PayPal\Api\BankToken bank_account_token
 * @property \PayPal\Api\Credit credit
 * @property \PayPal\Api\CarrierAccountToken carrier_account_token
 *
 */
class FundingInstrument extends PayPalModel
{
    /**
     * Credit Card information.
     *
     * @param \PayPal\Api\CreditCard $credit_card
     * 
     * @return $this
     */
    public function setCreditCard($credit_card)
    {
        $this->credit_card = $credit_card;
        return $this;
    }

    /**
     * Credit Card information.
     *
     * @return \PayPal\Api\CreditCard
     */
    public function getCreditCard()
    {
        return $this->credit_card;
    }

    /**
     * Credit Card information.
     *
     * @param \PayPal\Api\CreditCardToken $credit_card_token
     * 
     * @return $this
     */
    public function setCreditCardToken($credit_card_token)
    {
        $this->credit_card_token = $credit_card_token;
        return $this;
    }

    /**
     * Credit Card information.
     *
     * @return \PayPal\Api\CreditCardToken
     */
    public function getCreditCardToken()
    {
        return $this->credit_card_token;
    }

    /**
     * Payment Card information.
     *
     * @param \PayPal\Api\PaymentCard $payment_card
     * 
     * @return $this
     */
    public function setPaymentCard($payment_card)
    {
        $this->payment_card = $payment_card;
        return $this;
    }

    /**
     * Payment Card information.
     *
     * @return \PayPal\Api\PaymentCard
     */
    public function getPaymentCard()
    {
        return $this->payment_card;
    }

    /**
     * Payment card token information.
     *
     * @param \PayPal\Api\PaymentCardToken $payment_card_token
     * 
     * @return $this
     */
    public function setPaymentCardToken($payment_card_token)
    {
        $this->payment_card_token = $payment_card_token;
        return $this;
    }

    /**
     * Payment card token information.
     *
     * @return \PayPal\Api\PaymentCardToken
     */
    public function getPaymentCardToken()
    {
        return $this->payment_card_token;
    }

    /**
     * Bank Account information.
     *
     * @param \PayPal\Api\ExtendedBankAccount $bank_account
     * 
     * @return $this
     */
    public function setBankAccount($bank_account)
    {
        $this->bank_account = $bank_account;
        return $this;
    }

    /**
     * Bank Account information.
     *
     * @return \PayPal\Api\ExtendedBankAccount
     */
    public function getBankAccount()
    {
        return $this->bank_account;
    }

    /**
     * Bank Account information.
     *
     * @param \PayPal\Api\BankToken $bank_account_token
     * 
     * @return $this
     */
    public function setBankAccountToken($bank_account_token)
    {
        $this->bank_account_token = $bank_account_token;
        return $this;
    }

    /**
     * Bank Account information.
     *
     * @return \PayPal\Api\BankToken
     */
    public function getBankAccountToken()
    {
        return $this->bank_account_token;
    }

    /**
     * Credit funding information.
     *
     * @param \PayPal\Api\Credit $credit
     * 
     * @return $this
     */
    public function setCredit($credit)
    {
        $this->credit = $credit;
        return $this;
    }

    /**
     * Credit funding information.
     *
     * @return \PayPal\Api\Credit
     */
    public function getCredit()
    {
        return $this->credit;
    }

    /**
     * Incentive funding information.
     *
     * @param \PayPal\Api\Incentive $incentive
     *
     * @return $this
     */
    public function setIncentive($incentive)
    {
        $this->incentive = $incentive;
        return $this;
    }

    /**
     * Incentive funding information.
     *
     * @return \PayPal\Api\Incentive
     */
    public function getIncentive()
    {
        return $this->incentive;
    }

    /**
     * Carrier account token information.
     *
     * @param \PayPal\Api\CarrierAccountToken $carrier_account_token
     *
     * @return $this
     */
    public function setCarrierAccountToken($carrier_account_token)
    {
        $this->carrier_account_token = $carrier_account_token;
        return $this;
    }

    /**
     * Carrier account token information.
     *
     * @return \PayPal\Api\CarrierAccountToken
     */
    public function getCarrierAccountToken()
    {
        return $this->carrier_account_token;
    }

}
