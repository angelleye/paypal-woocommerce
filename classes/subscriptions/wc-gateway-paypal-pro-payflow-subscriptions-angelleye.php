<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Gateway_PayPal_Pro_PayFlow_Subscriptions_AngellEYE extends WC_Gateway_PayPal_Pro_PayFlow_AngellEYE {

    public function __construct() {
        parent::__construct();
        if (class_exists('WC_Subscriptions_Order')) {
            add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'scheduled_subscription_payment'), 10, 2);
            add_filter('woocommerce_subscription_payment_meta', array($this, 'add_subscription_payment_meta'), 10, 2);
            add_filter('woocommerce_subscription_validate_payment_meta', array($this, 'validate_subscription_payment_meta'), 10, 2);
        }
    }

    protected function is_subscription($order_id) {
        return ( function_exists('wcs_order_contains_subscription') && ( wcs_order_contains_subscription($order_id) || wcs_is_subscription($order_id) || wcs_order_contains_renewal($order_id) ) );
    }

    public function process_payment($order_id) {
        if ($this->is_subscription($order_id)) {
            return parent::process_payment($order_id);
        } else {
            return parent::process_payment($order_id);
        }
    }

    public function scheduled_subscription_payment($amount_to_charge, $renewal_order) {
        parent::process_subscription_payment($renewal_order, $amount_to_charge);
    }

    public function add_subscription_payment_meta($payment_meta, $subscription) {
        $token = WC_Payment_Tokens::get_order_tokens($subscription->order->id);
        foreach ($token as $key => $value) {
            $token_id = $value->get_token();
            break;
        }
        $payment_meta[$this->id] = array(
            'post_meta' => array(
                '_transaction_id' => array(
                    'value' => get_post_meta($subscription->order->id, '_transaction_id', true),
                    'label' => 'Transaction Id',
                ),
                '_payment_tokens' => array(
                    'value' => $token_id,
                    'label' => 'Payment Tokens',
                ),
            ),
        );
        return $payment_meta;
    }

    public function validate_subscription_payment_meta($payment_method_id, $payment_meta) {
        if ($this->id === $payment_method_id) {
            if (!isset($payment_meta['post_meta']['_transaction_id']['value']) || empty($payment_meta['post_meta']['_transaction_id']['value'])) {
                throw new Exception('A "_transaction_id" value is required.');
            }
            if (!empty($payment_meta['post_meta']['_payment_tokens']['value']) && empty($payment_meta['post_meta']['_payment_tokens']['value'])) {
                throw new Exception('A "_payment_tokens" value is required.');
            }
        }
    }

}
