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
            'pay_button',
            'pre-orders'
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
    
    public function is_paypal_vault_used_for_pre_order() {
        return 'vault' === $this->setting_obj->get('woo_pre_order_payment_mode');
    }

    public function is_credentials_set(): bool {
        if (!empty($this->merchant_id) || (!empty($this->client_id) && !empty($this->secret_id))) {
            return true;
        } else {
            return false;
        }
    }

    public function getApplePayRecurringParams()
    {
        return [
            'paymentDescription' => $this->setting_obj->get('apple_pay_rec_payment_desc'),
            'billingAgreement' => $this->setting_obj->get('apple_pay_rec_billing_agreement_desc'),
            'managementURL' => wc_get_account_endpoint_url( 'payment-methods' )
        ];
    }

    public function can_refund_order($order) {
        $has_api_creds = false;
        if ($this->is_credentials_set()) {
            $has_api_creds = true;
        }
        return $order && $order->get_transaction_id() && $has_api_creds;
    }
    
    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        if (apply_filters('angelleye_is_ppcp_parallel_payment_not_used', true, $order_id)) {
            if($order && $this->can_refund_order($order) && angelleye_ppcp_order_item_meta_key_exists($order, '_ppcp_capture_details')) {
                $capture_data_list = $this->payment_request->angelleye_ppcp_prepare_refund_request_data_for_capture($order, $amount);
                if(empty($capture_data_list)) {
                    throw new Exception( __( 'No Capture transactions available for refund.', 'woocommerce' ) );
                }
                $failed_result_count = 0;
                $successful_transaction = 0;
                foreach ($capture_data_list as $item_id => $capture_data) {
                    foreach ($capture_data as $transaction_id => $amount) {
                        if ($this->payment_request->angelleye_ppcp_refund_capture_order($order_id, $amount, $reason, $transaction_id, $item_id)) {
                            $successful_transaction++;
                        } else {
                            $failed_result_count++;
                        }
                    }
                }
                if($failed_result_count > 0) {
                    return false;
                }
                return true;
            } else {
                if (!$this->can_refund_order($order)) {
                    return new WP_Error('error', __('Refund failed.', 'paypal-for-woocommerce'));
                }
                $transaction_id = $order->get_transaction_id();
                $bool = $this->payment_request->angelleye_ppcp_refund_order($order_id, $amount, $reason, $transaction_id);
                return $bool;
            }
        } else {
            return apply_filters('angelleye_is_ppcp_parallel_payment_handle', true, $order_id, $this);
        }
    }
}

