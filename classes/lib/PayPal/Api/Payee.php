<?php

namespace PayPal\Api;

use PayPal\Common\PayPalModel;

/**
 * Class Payee
 *
 * A resource representing a Payee that received the funds and fulfills the order. Only one of the following identifiers need to be supplied.
 *
 * @package PayPal\Api
 *
 * @property string email
 * @property string merchant_id
 * @property \PayPal\Api\Phone phone
 */
class Payee extends PayPalModel
{
    /**
     * Email Address associated with the Payee's PayPal Account. If the provided email address is not associated with any PayPal Account, the payee can only receiver PayPal Wallet Payments. Direct Credit Card Payments will be denied due to card compliance requirements.
     * 
     *
     * @param string $email
     * 
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Email Address associated with the Payee's PayPal Account. If the provided email address is not associated with any PayPal Account, the payee can only receiver PayPal Wallet Payments. Direct Credit Card Payments will be denied due to card compliance requirements.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Encrypted PayPal Account identifier for the Payee.
     * 
     *
     * @param string $merchant_id
     * 
     * @return $this
     */
    public function setMerchantId($merchant_id)
    {
        $this->merchant_id = $merchant_id;
        return $this;
    }

    /**
     * Encrypted PayPal Account identifier for the Payee.
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->merchant_id;
    }

    /**
     * Information related to the Payer. In case of PayPal Wallet payment, this information will be filled in by PayPal after the user approves the payment using their PayPal Wallet. 
     * 
     *
     * @param \PayPal\Api\Phone $phone
     * 
     * @return $this
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * Information related to the Payer. In case of PayPal Wallet payment, this information will be filled in by PayPal after the user approves the payment using their PayPal Wallet. 
     *
     * @return \PayPal\Api\Phone
     */
    public function getPhone()
    {
        return $this->phone;
    }

}
