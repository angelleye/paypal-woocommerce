<?php

namespace PayPal\Api;

use PayPal\Common\PayPalModel;

/**
 * Class CreditCardToken
 *
 * A resource representing a credit card that can be used to fund a payment.
 *
 * @package PayPal\Api
 *
 * @property string credit_card_id
 * @property string payer_id
 * @property string last4
 * @property string type
 * @property int expire_month
 * @property int expire_year
 */
class CreditCardToken extends PayPalModel
{
    /**
     * ID of a previously saved Credit Card resource using /vault/credit-card API.
     *
     * @param string $credit_card_id
     * 
     * @return $this
     */
    public function setCreditCardId($credit_card_id)
    {
        $this->credit_card_id = $credit_card_id;
        return $this;
    }

    /**
     * ID of a previously saved Credit Card resource using /vault/credit-card API.
     *
     * @return string
     */
    public function getCreditCardId()
    {
        return $this->credit_card_id;
    }

    /**
     * The unique identifier of the payer used when saving this credit card using /vault/credit-card API.
     *
     * @param string $payer_id
     * 
     * @return $this
     */
    public function setPayerId($payer_id)
    {
        $this->payer_id = $payer_id;
        return $this;
    }

    /**
     * The unique identifier of the payer used when saving this credit card using /vault/credit-card API.
     *
     * @return string
     */
    public function getPayerId()
    {
        return $this->payer_id;
    }

    /**
     * Last 4 digits of the card number from the saved card.
     *
     * @param string $last4
     * 
     * @return $this
     */
    public function setLast4($last4)
    {
        $this->last4 = $last4;
        return $this;
    }

    /**
     * Last 4 digits of the card number from the saved card.
     *
     * @return string
     */
    public function getLast4()
    {
        return $this->last4;
    }

    /**
     * Type of the Card (eg. visa, mastercard, etc.) from the saved card. Please note that the values are always in lowercase and not meant to be used directly for display.
     *
     * @param string $type
     * 
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Type of the Card (eg. visa, mastercard, etc.) from the saved card. Please note that the values are always in lowercase and not meant to be used directly for display.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * card expiry month from the saved card with value 1 - 12
     *
     * @param int $expire_month
     * 
     * @return $this
     */
    public function setExpireMonth($expire_month)
    {
        $this->expire_month = $expire_month;
        return $this;
    }

    /**
     * card expiry month from the saved card with value 1 - 12
     *
     * @return int
     */
    public function getExpireMonth()
    {
        return $this->expire_month;
    }

    /**
     * 4 digit card expiry year from the saved card
     *
     * @param int $expire_year
     * 
     * @return $this
     */
    public function setExpireYear($expire_year)
    {
        $this->expire_year = $expire_year;
        return $this;
    }

    /**
     * 4 digit card expiry year from the saved card
     *
     * @return int
     */
    public function getExpireYear()
    {
        return $this->expire_year;
    }

}
