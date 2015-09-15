<?php

namespace PayPal\Api;

use PayPal\Common\PayPalModel;
use PayPal\Validation\UrlValidator;

/**
 * Class RedirectUrls
 *
 * Redirect urls required only when using payment_method as PayPal - the only settings supported are return and cancel urls.
 *
 * @package PayPal\Api
 *
 * @property string return_url
 * @property string cancel_url
 */
class RedirectUrls extends PayPalModel
{
    /**
     * Url where the payer would be redirected to after approving the payment.
     *
     * @param string $return_url
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setReturnUrl($return_url)
    {
        UrlValidator::validate($return_url, "ReturnUrl");
        $this->return_url = $return_url;
        return $this;
    }

    /**
     * Url where the payer would be redirected to after approving the payment.
     *
     * @return string
     */
    public function getReturnUrl()
    {
        return $this->return_url;
    }

    /**
     * Url where the payer would be redirected to after canceling the payment.
     *
     * @param string $cancel_url
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setCancelUrl($cancel_url)
    {
        UrlValidator::validate($cancel_url, "CancelUrl");
        $this->cancel_url = $cancel_url;
        return $this;
    }

    /**
     * Url where the payer would be redirected to after canceling the payment.
     *
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->cancel_url;
    }

}
