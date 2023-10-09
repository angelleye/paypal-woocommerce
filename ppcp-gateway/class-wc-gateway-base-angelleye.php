<?php

trait WC_Gateway_Base_AngellEYE
{
    use AngellEye_PPCP_Core;

    public function isSubscriptionsSupported(): bool
    {
        return true;
    }

    protected function setGatewaySupports($additionalSupports = [])
    {
        $this->enable_tokenized_payments = 'yes' === $this->setting_obj->get('enable_tokenized_payments', 'no');

        $baseSupports = array_merge([
            'products',
            'refunds',
            'pay_button'
        ], $additionalSupports);

        if ($this->isSubscriptionsSupported()) {
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
        } else {
            $subscriptionSupports = [];
        }

        if (isset($_GET['paypal_order_id']) && isset($_GET['paypal_payer_id']) && $this->enable_tokenized_payments) {
            $this->supports = array_merge($baseSupports, $subscriptionSupports);
        } elseif ($this->enable_tokenized_payments ||
            (isset($_GET['page']) && isset($_GET['tab']) && 'wc-settings' === $_GET['page'] && 'checkout' === $_GET['tab'])) {
            $this->supports = array_merge($baseSupports, $subscriptionSupports, array('tokenization'));
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

    public function isSubscriptionRequired($orderId = null): bool
    {
        if (!empty($orderId) && class_exists('WC_Subscriptions_Order')) {
            return WC_Subscriptions_Order::order_contains_subscription($orderId);
        }
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

