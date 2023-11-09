<?php

if (class_exists("UpStroke_Subscriptions_AngellEYE_PPCP_CC") || !class_exists("WFOCU_Paypal_For_WC_Gateway_AngellEYE_PPCP_CC")) {
    return;
}

class UpStroke_Subscriptions_AngellEYE_PPCP_CC extends WFOCU_Paypal_For_WC_Gateway_AngellEYE_PPCP_CC {

    public function __construct() {
        add_action('wfocu_subscription_created_for_upsell', array($this, 'save_payment_token_to_subscription'), 10, 3);
        add_filter('wfocu_order_copy_meta_keys', array($this, 'set_paypal_keys_to_copy'), 10, 1);
    }

    public function save_payment_token_to_subscription($subscription, $key, $order) {
        try {
            if (!$order instanceof WC_Order) {
                return;
            }
            $get_source_id = $order->get_meta('_payment_tokens_id', true);
            if (!$order instanceof WC_Order) {
                return;
            }
            if ($this->get_key() !== $order->get_payment_method()) {
                return;
            }
            $subscription->update_meta_data('payment_token_id', $get_source_id);
            $subscription->update_meta_data('_payment_tokens_id', $get_source_id);
            $subscription->save();
        } catch (Exception $ex) {

        }
    }

    public function set_paypal_keys_to_copy($meta_keys) {
        return $meta_keys;
    }
}

if (class_exists('WC_Subscriptions')) {
    new UpStroke_Subscriptions_AngellEYE_PPCP_CC();
}
