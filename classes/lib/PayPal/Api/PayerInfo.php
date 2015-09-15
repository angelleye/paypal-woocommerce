<?php

namespace PayPal\Api;

use PayPal\Common\PayPalModel;

/**
 * Class PayerInfo
 *
 * A resource representing a information about Payer.
 *
 * @package PayPal\Api
 *
 * @property string email
 * @property string external_remember_me_id
 * @property string buyer_account_number
 * @property string salutation
 * @property string first_name
 * @property string middle_name
 * @property string last_name
 * @property string suffix
 * @property string payer_id
 * @property string phone
 * @property string phone_type
 * @property string birth_date
 * @property string tax_id
 * @property string tax_id_type
 * @property string country_code
 * @property \PayPal\Api\Address billing_address
 * @property \PayPal\Api\ShippingAddress shipping_address
 */
class PayerInfo extends PayPalModel
{
    /**
     * Email address representing the Payer.
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
     * Email address representing the Payer.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * External Remember Me id representing the Payer
     *
     * @param string $external_remember_me_id
     * 
     * @return $this
     */
    public function setExternalRememberMeId($external_remember_me_id)
    {
        $this->external_remember_me_id = $external_remember_me_id;
        return $this;
    }

    /**
     * External Remember Me id representing the Payer
     *
     * @return string
     */
    public function getExternalRememberMeId()
    {
        return $this->external_remember_me_id;
    }

    /**
     * Account Number representing the Payer
     *
     * @param string $buyer_account_number
     * 
     * @return $this
     */
    public function setBuyerAccountNumber($buyer_account_number)
    {
        $this->buyer_account_number = $buyer_account_number;
        return $this;
    }

    /**
     * Account Number representing the Payer
     *
     * @return string
     */
    public function getBuyerAccountNumber()
    {
        return $this->buyer_account_number;
    }

    /**
     * Salutation of the Payer.
     *
     * @param string $salutation
     * 
     * @return $this
     */
    public function setSalutation($salutation)
    {
        $this->salutation = $salutation;
        return $this;
    }

    /**
     * Salutation of the Payer.
     *
     * @return string
     */
    public function getSalutation()
    {
        return $this->salutation;
    }

    /**
     * First Name of the Payer.
     *
     * @param string $first_name
     * 
     * @return $this
     */
    public function setFirstName($first_name)
    {
        $this->first_name = $first_name;
        return $this;
    }

    /**
     * First Name of the Payer.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Middle Name of the Payer.
     *
     * @param string $middle_name
     * 
     * @return $this
     */
    public function setMiddleName($middle_name)
    {
        $this->middle_name = $middle_name;
        return $this;
    }

    /**
     * Middle Name of the Payer.
     *
     * @return string
     */
    public function getMiddleName()
    {
        return $this->middle_name;
    }

    /**
     * Last Name of the Payer.
     *
     * @param string $last_name
     * 
     * @return $this
     */
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;
        return $this;
    }

    /**
     * Last Name of the Payer.
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * Suffix of the Payer.
     *
     * @param string $suffix
     * 
     * @return $this
     */
    public function setSuffix($suffix)
    {
        $this->suffix = $suffix;
        return $this;
    }

    /**
     * Suffix of the Payer.
     *
     * @return string
     */
    public function getSuffix()
    {
        return $this->suffix;
    }

    /**
     * PayPal assigned Payer ID.
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
     * PayPal assigned Payer ID.
     *
     * @return string
     */
    public function getPayerId()
    {
        return $this->payer_id;
    }

    /**
     * Phone number representing the Payer.
     *
     * @param string $phone
     * 
     * @return $this
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * Phone number representing the Payer.
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Phone type
     * Valid Values: ["HOME", "WORK", "MOBILE", "OTHER"]
     *
     * @param string $phone_type
     * 
     * @return $this
     */
    public function setPhoneType($phone_type)
    {
        $this->phone_type = $phone_type;
        return $this;
    }

    /**
     * Phone type
     *
     * @return string
     */
    public function getPhoneType()
    {
        return $this->phone_type;
    }

    /**
     * Birth date of the Payer in ISO8601 format (yyyy-mm-dd).
     *
     * @param string $birth_date
     * 
     * @return $this
     */
    public function setBirthDate($birth_date)
    {
        $this->birth_date = $birth_date;
        return $this;
    }

    /**
     * Birth date of the Payer in ISO8601 format (yyyy-mm-dd).
     *
     * @return string
     */
    public function getBirthDate()
    {
        return $this->birth_date;
    }

    /**
     * Payer's tax ID.
     *
     * @param string $tax_id
     * 
     * @return $this
     */
    public function setTaxId($tax_id)
    {
        $this->tax_id = $tax_id;
        return $this;
    }

    /**
     * Payer's tax ID.
     *
     * @return string
     */
    public function getTaxId()
    {
        return $this->tax_id;
    }

    /**
     * Payer's tax ID type.
     * Valid Values: ["BR_CPF", "BR_CNPJ"]
     *
     * @param string $tax_id_type
     * 
     * @return $this
     */
    public function setTaxIdType($tax_id_type)
    {
        $this->tax_id_type = $tax_id_type;
        return $this;
    }

    /**
     * Payer's tax ID type.
     *
     * @return string
     */
    public function getTaxIdType()
    {
        return $this->tax_id_type;
    }

    /**
     * 2 letter registered country code of the payer to identify the buyer country
     *
     * @param string $country_code
     * 
     * @return $this
     */
    public function setCountryCode($country_code)
    {
        $this->country_code = $country_code;
        return $this;
    }

    /**
     * 2 letter registered country code of the payer to identify the buyer country
     *
     * @return string
     */
    public function getCountryCode()
    {
        return $this->country_code;
    }

    /**
     * Billing address of the Payer.
     *
     * @param \PayPal\Api\Address $billing_address
     * 
     * @return $this
     */
    public function setBillingAddress($billing_address)
    {
        $this->billing_address = $billing_address;
        return $this;
    }

    /**
     * Billing address of the Payer.
     *
     * @return \PayPal\Api\Address
     */
    public function getBillingAddress()
    {
        return $this->billing_address;
    }

    /**
     * Obsolete. Use shipping address present in purchase unit.
     *
     * @param \PayPal\Api\ShippingAddress $shipping_address
     * 
     * @return $this
     */
    public function setShippingAddress($shipping_address)
    {
        $this->shipping_address = $shipping_address;
        return $this;
    }

    /**
     * Obsolete. Use shipping address present in purchase unit.
     *
     * @return \PayPal\Api\ShippingAddress
     */
    public function getShippingAddress()
    {
        return $this->shipping_address;
    }

}
