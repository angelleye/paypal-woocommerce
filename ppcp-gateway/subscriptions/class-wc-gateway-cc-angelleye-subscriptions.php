<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Gateway_CC_AngellEYE_Subscriptions extends WC_Gateway_CC_AngellEYE {

    public $wc_pre_30;

    public function __construct() {
        parent::__construct();
        if (class_exists('WC_Subscriptions_Order')) {
            add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'scheduled_subscription_payment'), 10, 2);
            add_filter('woocommerce_subscription_payment_meta', array($this, 'add_subscription_payment_meta'), 10, 2);
            add_filter('woocommerce_subscription_validate_payment_meta', array($this, 'validate_subscription_payment_meta'), 10, 3);
            add_action('wcs_resubscribe_order_created', array($this, 'delete_resubscribe_meta'), 10);
            add_action('woocommerce_subscription_failing_payment_method_updated_' . $this->id, array($this, 'update_failing_payment_method'), 10, 2);
        }
        $this->wc_pre_30 = version_compare(WC_VERSION, '3.0.0', '<');
    }

    public function is_subscription($order_id) {
        return ( function_exists('wcs_order_contains_subscription') && ( wcs_order_contains_subscription($order_id) || wcs_is_subscription($order_id) || wcs_order_contains_renewal($order_id) ) );
    }

    public function process_payment($order_id) {
        if ($this->is_subscription($order_id)) {
            if (angelleye_ppcp_is_subs_change_payment()) {
                return parent::subscription_change_payment($order_id);
            } elseif ($this->free_signup_with_token_payment_tokenization($order_id) == true) {
                return parent::free_signup_order_payment($order_id);
            } elseif ($this->angelleye_ppcp_is_free_signup_with_free_trial() === true) {
                return parent::angelleye_ppcp_process_free_signup_with_free_trial($order_id);
            } else {
                return parent::process_payment($order_id);
            }
        } else {
            return parent::process_payment($order_id);
        }
    }

    public function angelleye_ppcp_is_free_signup_with_free_trial() {
        if (angelleye_ppcp_get_order_total() === 0) {
            return true;
        }
        return false;
    }

    public function scheduled_subscription_payment($amount_to_charge, $renewal_order) {
        $renewal_order_id = $this->wc_pre_30 ? $renewal_order->id : $renewal_order->get_id();
        $payment_tokens_id = get_post_meta($renewal_order_id, '_payment_tokens_id', true);
        if (empty($payment_tokens_id) || $payment_tokens_id == false) {
            $this->angelleye_scheduled_subscription_payment_retry_compability($renewal_order);
        }

        if (function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($renewal_order_id)) {
            $subscriptions = wcs_get_subscriptions_for_order($renewal_order_id);
        } elseif (function_exists('wcs_order_contains_renewal') && wcs_order_contains_renewal($renewal_order_id)) {
            $subscriptions = wcs_get_subscriptions_for_renewal_order($renewal_order_id);
        } else {
            $subscriptions = array();
        }
        if (!empty($subscriptions)) {
            foreach ($subscriptions as $subscription) {
                $subscription_parent_id = $this->wc_pre_30 ? $subscription->parent_id : $subscription->get_parent_id();
                $angelleye_ppcp_used_payment_method = get_post_meta($subscription_parent_id, '_angelleye_ppcp_used_payment_method', true);
                if (!empty($angelleye_ppcp_used_payment_method)) {
                    update_post_meta($renewal_order_id, '_angelleye_ppcp_used_payment_method', $angelleye_ppcp_used_payment_method);
                }
            }
        }
        parent::process_subscription_payment($renewal_order, $amount_to_charge);
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

    public function validate_subscription_payment_meta($payment_method_id, $payment_meta, $subscription) {
        if ($this->id === $payment_method_id) {
            if (empty($payment_meta['post_meta']['_payment_tokens_id']['value'])) {
                $subscription_id = $this->wc_pre_30 ? $subscription->id : $subscription->get_id();
                $subscription_parent_id = $this->wc_pre_30 ? $subscription->parent_id : $subscription->get_parent_id();
                $payment_tokens_id = get_post_meta($subscription_parent_id, '_payment_tokens_id', true);
                if (!empty($payment_tokens_id)) {
                    update_post_meta($subscription_id, '_payment_tokens_id', $payment_tokens_id);
                } else {
                    throw new Exception('A "_payment_tokens_id" value is required.');
                }
            }
        }
    }

    public function delete_resubscribe_meta($resubscribe_order) {
        $subscription_id = $this->wc_pre_30 ? $resubscribe_order->id : $resubscribe_order->get_id();
        delete_post_meta($subscription_id, '_payment_tokens_id');
    }

    public function update_failing_payment_method($subscription, $renewal_order) {
        if ($this->wc_pre_30) {
            update_post_meta($subscription->id, '_payment_tokens_id', $renewal_order->payment_tokens_id);
        } else {
            $subscription->update_meta_data('_payment_tokens_id', $renewal_order->get_meta('_payment_tokens_id', true));
        }
    }

    public function free_signup_with_token_payment_tokenization($order_id) {
        if ((!empty($_POST['wc-angelleye_ppcp-payment-token']) && $_POST['wc-angelleye_ppcp-payment-token'] != 'new') || (!empty($_POST['wc-angelleye_ppcp_cc-payment-token']) && $_POST['wc-angelleye_ppcp_cc-payment-token'] != 'new' )) {


            $order = wc_get_order($order_id);
            if ($order->get_total() == 0) {
                return true;
            }
        }
        return false;
    }

    public function angelleye_scheduled_subscription_payment_retry_compability($renewal_order) {
        $renewal_order_id = $this->wc_pre_30 ? $renewal_order->id : $renewal_order->get_id();
        $payment_tokens_id = get_post_meta($renewal_order_id, '_payment_tokens_id', true);
        if (empty($payment_tokens_id) || $payment_tokens_id == false) {
            if (function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($renewal_order_id)) {
                $subscriptions = wcs_get_subscriptions_for_order($renewal_order_id);
            } elseif (function_exists('wcs_order_contains_renewal') && wcs_order_contains_renewal($renewal_order_id)) {
                $subscriptions = wcs_get_subscriptions_for_renewal_order($renewal_order_id);
            } else {
                $subscriptions = array();
            }
            if (!empty($subscriptions)) {
                foreach ($subscriptions as $subscription) {
                    $subscription_id = $this->wc_pre_30 ? $subscription->id : $subscription->get_id();
                    $subscription_parent_id = $this->wc_pre_30 ? $subscription->parent_id : $subscription->get_parent_id();
                    $payment_tokens_id = get_post_meta($subscription_parent_id, '_payment_tokens_id', true);
                    if (!empty($payment_tokens_id)) {
                        update_post_meta($subscription_id, '_payment_tokens_id', $payment_tokens_id);
                        update_post_meta($renewal_order_id, '_payment_tokens_id', $payment_tokens_id);
                    }
                }
            }
        }
    }

}
