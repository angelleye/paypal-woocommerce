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
            add_action('wcs_resubscribe_order_created', array($this, 'delete_resubscribe_meta'), 10);
            add_action('woocommerce_subscription_failing_payment_method_updated_stripe', array($this, 'update_failing_payment_method'), 10, 2);
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
        $payment_meta[$this->id] = array(
            'post_meta' => array(
                '_payment_tokens_id' => array(
                    'value' => get_post_meta($subscription->id, '_payment_tokens_id', true),
                    'label' => 'Payment Tokens ID',
                )
            )
        );
        return $payment_meta;
    }

    public function validate_subscription_payment_meta($payment_method_id, $payment_meta) {
        if ($this->id === $payment_method_id) {
            if (!empty($payment_meta['post_meta']['_payment_tokens_id']['value']) && empty($payment_meta['post_meta']['_payment_tokens_id']['value'])) {
                throw new Exception('A "_payment_tokens_id" value is required.');
            }
        }
    }

    public function save_payment_token($order, $payment_tokens_id) {
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        parent::save_payment_token($order, $payment_tokens_id);
        // Also store it on the subscriptions being purchased or paid for in the order
        if (function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($order_id)) {
            $subscriptions = wcs_get_subscriptions_for_order($order_id);
        } elseif (function_exists('wcs_order_contains_renewal') && wcs_order_contains_renewal($order_id)) {
            $subscriptions = wcs_get_subscriptions_for_renewal_order($order_id);
        } else {
            $subscriptions = array();
        }
        if (!empty($subscriptions)) {
            foreach ($subscriptions as $subscription) {
                update_post_meta($subscription->id, '_payment_tokens_id', $payment_tokens_id);
            }
        }
    }

    public function delete_resubscribe_meta($resubscribe_order) {
        delete_post_meta($resubscribe_order->id, '_payment_tokens_id');
    }

    public function update_failing_payment_method($subscription, $renewal_order) {
        update_post_meta($subscription->id, '_payment_tokens_id', $renewal_order->payment_tokens_id);
    }

}
