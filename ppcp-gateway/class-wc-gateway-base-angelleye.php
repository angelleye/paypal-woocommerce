<?php

trait WC_Gateway_Base_AngellEYE
{
    public ?WC_Gateway_PPCP_AngellEYE_Settings $setting_obj;
    public ?AngellEYE_PayPal_PPCP_Log $api_log;
    public ?AngellEYE_PayPal_PPCP_Request $api_request;
    public ?AngellEYE_PayPal_PPCP_DCC_Validate $dcc_applies;
    public ?AngellEYE_PayPal_PPCP_Payment $payment_request;
    public $setting_obj_fields;

    public function angelleye_ppcp_load_class($loadSettingsFields = false) {
        try {
            if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-ppcp-angelleye-settings.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Log')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-log.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Request')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-request.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_DCC_Validate')) {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-dcc-validate.php');
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Payment')) {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-payment.php');
            }
            $this->setting_obj = WC_Gateway_PPCP_AngellEYE_Settings::instance();
            $this->api_log = AngellEYE_PayPal_PPCP_Log::instance();
            $this->api_request = AngellEYE_PayPal_PPCP_Request::instance();
            $this->dcc_applies = AngellEYE_PayPal_PPCP_DCC_Validate::instance();
            $this->payment_request = AngellEYE_PayPal_PPCP_Payment::instance();
            if ($loadSettingsFields) {
                $this->setting_obj_fields = $this->setting_obj->angelleye_ppcp_setting_fields();
            }
            $this->enable_tokenized_payments = 'yes' === $this->setting_obj->get('enable_tokenized_payments', 'no');
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    protected function isSubscriptionSupported(): bool
    {
        return true;
    }

    protected function setGatewaySupports($additionalSupports = [])
    {
        $baseSupports = array_merge([
            'products',
            'refunds',
            'pay_button'
        ], $additionalSupports);

        $subscriptionSupports = [
            'subscriptions',
            'subscription_cancellation',
            'subscription_reactivation',
            'subscription_suspension',
            'subscription_amount_changes',
            'subscription_payment_method_change', // Subs 1.n compatibility.
            'subscription_payment_method_change_customer',
            'subscription_payment_method_change_admin',
            'subscription_date_changes',
            'multiple_subscriptions',
            'add_payment_method'
        ];

        if (isset($_GET['paypal_order_id']) && isset($_GET['paypal_payer_id']) && $this->enable_tokenized_payments) {
            $this->supports = array_merge($baseSupports, $subscriptionSupports);
        } elseif ($this->isSubscriptionSupported() && ($this->enable_tokenized_payments ||
            (isset($_GET['page']) && isset($_GET['tab']) && 'wc-settings' === $_GET['page'] && 'checkout' === $_GET['tab']))) {
            $this->supports = array_merge($baseSupports, $subscriptionSupports, ['tokenization']);
        } else {
            $this->supports = $baseSupports;
        }
    }

    public function is_credentials_set(): bool {
        if (!empty($this->merchant_id) || (!empty($this->client_id) && !empty($this->secret_id))) {
            return true;
        } else {
            return false;
        }
    }

    public function isSubscriptionRequired(): bool
    {
        if (class_exists('WC_Subscriptions_Cart')) {
            return WC_Subscriptions_Cart::cart_contains_subscription();
        }
        return false;
    }

    public function getApplePayRecurringParams()
    {
        return [
            'paymentDescription' => $this->setting_obj->get('apple_pay_rec_payment_desc'),
            'billingAgreement' => $this->setting_obj->get('apple_pay_rec_billing_agreement_desc'),
            'managementURL' => wc_get_account_endpoint_url( 'payment-methods' )
        ];
    }
}

