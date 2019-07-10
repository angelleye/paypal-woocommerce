<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Gateway_PayPal_Express_Subscriptions_AngellEYE extends WC_Gateway_PayPal_Express_AngellEYE {
    public $wc_pre_30;
    public function __construct() {
        parent::__construct();
        if (class_exists('WC_Subscriptions_Order')) {
            add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'scheduled_subscription_payment'), 10, 2);
            add_filter('woocommerce_subscription_payment_meta', array($this, 'add_subscription_payment_meta'), 10, 2);
            add_filter('woocommerce_subscription_validate_payment_meta', array($this, 'validate_subscription_payment_meta'), 10, 2);
            add_action('wcs_resubscribe_order_created', array($this, 'delete_resubscribe_meta'), 10);
            add_action('woocommerce_subscription_failing_payment_method_updated_' . $this->id, array($this, 'update_failing_payment_method'), 10, 2);
        }
        $this->wc_pre_30 = version_compare( WC_VERSION, '3.0.0', '<' );
    }

    public function is_subscription($order_id) {
        return ( function_exists('wcs_order_contains_subscription') && ( wcs_order_contains_subscription($order_id) || wcs_is_subscription($order_id) || wcs_order_contains_renewal($order_id) ) );
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        if ($this->is_subscription($order_id) && $this->function_helper->ec_is_express_checkout() == false) {
            if(AngellEYE_Utility::is_subs_change_payment()) {
                return parent::subscription_change_payment($order_id);
            } elseif ($this->free_signup_with_token_payment_tokenization($order_id) == true) {
                return parent::free_signup_order_payment($order_id);
            } else {
                return parent::process_subscription_payment($order_id);
            }
        } else {
            return parent::process_payment($order_id);
        }
    }

    public function scheduled_subscription_payment($amount_to_charge, $renewal_order) {
        $renewal_order_id = $this->wc_pre_30 ? $renewal_order->id : $renewal_order->get_id();
        if ($renewal_order->get_total() > 0) {
            parent::process_subscription_payment($renewal_order_id);
        } else {
            parent::process_subscription_payment($renewal_order_id);
        }
    }

    public function add_subscription_payment_meta($payment_meta, $subscription) {
        $subscription_id = $this->wc_pre_30 ? $subscription->id : $subscription->get_id();
        $payment_meta[$this->id] = array(
            'post_meta' => array(
                '_payment_tokens_id' => array(
                    'value' => get_post_meta($subscription_id, '_payment_tokens_id', true),
                    'label' => 'Payment Tokens ID',
                )
            )
        );
        return $payment_meta;
    }

    public function validate_subscription_payment_meta($payment_method_id, $payment_meta) {
        if ($this->id === $payment_method_id) {
            if (empty($payment_meta['post_meta']['_payment_tokens_id']['value'])) {
                throw new Exception('A "_payment_tokens_id" value is required.');
            }
        }
    }

    public function delete_resubscribe_meta($resubscribe_order) {
        $resubscribe_order_id = $this->wc_pre_30 ? $resubscribe_order->id : $resubscribe_order->get_id();
        delete_post_meta($resubscribe_order_id, '_payment_tokens_id');
    }

    public function update_failing_payment_method($subscription, $renewal_order) {
        $subscription_id = $this->wc_pre_30 ? $subscription->id : $subscription->get_id();
        update_post_meta($subscription_id, '_payment_tokens_id', $renewal_order->payment_tokens_id);
    }
    
    public function free_signup_with_token_payment_tokenization($order_id) {
        if (!empty($_POST['wc-paypal_express-payment-token']) && $_POST['wc-paypal_express-payment-token'] != 'new') {
            $order = new WC_Order($order_id);
            if ($order->get_total() == 0) {
                return true;
            }
        }
        return false;
    }

}
