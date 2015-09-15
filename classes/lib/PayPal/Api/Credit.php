<?php

namespace PayPal\Api;

use PayPal\Common\PayPalModel;

/**
 * Class Credit
 *
 * A resource representing a credit instrument.
 *
 * @package PayPal\Api
 *
 * @property string id
 * @property string type
 * @property string terms
 */
class Credit extends PayPalModel
{
    /**
     * Unique identifier of credit resource.
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
     * Unique identifier of credit resource.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Specifies the type of credit.
     * Valid Values: ["BILL_ME_LATER", "PAYPAL_EXTRAS_MASTERCARD", "EBAY_MASTERCARD", "PAYPAL_SMART_CONNECT"]
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
     * Specifies the type of credit
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * URI to the associated terms.
     *
     * @param string $terms
     *
     * @return $this
     */
    public function setTerms($terms)
    {
        $this->terms = $terms;
        return $this;
    }

    /**
     * URI to the associated terms.
     *
     * @return string
     */
    public function getTerms()
    {
        return $this->terms;
    }

}
