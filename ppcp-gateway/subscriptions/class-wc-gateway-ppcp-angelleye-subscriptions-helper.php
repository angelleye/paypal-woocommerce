<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Gateway_PPCP_AngellEYE_Subscriptions_Helper {

    public function angelleye_ppcp_is_save_payment_token($current, $order_id) {
        if ((!empty($_POST['wc-' . $current->id . '-new-payment-method']) && $_POST['wc-' . $current->id . '-new-payment-method'] == true) || $this->is_subscription($order_id) || $this->angelleye_paypal_for_woo_wc_autoship_cart_has_autoship_item()) {
            return true;
        }
        return false;
    }

    public function save_payment_token($order, $payment_tokens_id) {
        // Store source in the order
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        if (!empty($payment_tokens_id)) {
            update_post_meta($order_id, '_payment_tokens_id', $payment_tokens_id);
        }
    }

    public function angelleye_ppcp_wc_save_payment_token($order_id) {
        if ($this->angelleye_is_save_payment_token($this, $order_id)) {
            if (!empty($_POST['wc-paypal_pro-payment-token']) && $_POST['wc-paypal_pro-payment-token'] != 'new') {
                $token_id = wc_clean($_POST['wc-paypal_pro-payment-token']);
                $token = WC_Payment_Tokens::get($token_id);
                $order->add_payment_token($token);
                if ($this->is_subscription($order_id)) {
                    $TRANSACTIONID = $PayPalResult['TRANSACTIONID'];
                    $this->save_payment_token($order, $TRANSACTIONID);
                }
            } else {
                $TRANSACTIONID = $PayPalResult['TRANSACTIONID'];
                $token = new WC_Payment_Token_CC();
                if (0 != $order->get_user_id()) {
                    $customer_id = $order->get_user_id();
                } else {
                    $customer_id = get_current_user_id();
                }
                $token->set_token($TRANSACTIONID);
                $token->set_gateway_id($this->id);
                $token->set_card_type($this->card_type_from_account_number($PayPalRequestData['CCDetails']['acct']));
                $token->set_last4(substr($PayPalRequestData['CCDetails']['acct'], -4));
                $token->set_expiry_month(substr($PayPalRequestData['CCDetails']['expdate'], 0, 2));
                $token->set_expiry_year(substr($PayPalRequestData['CCDetails']['expdate'], 2, 5));
                $token->set_user_id($customer_id);
                if ($token->validate()) {
                    $this->save_payment_token($order, $TRANSACTIONID);
                    $save_result = $token->save();
                    if ($save_result) {
                        $order->add_payment_token($token);
                    }
                } else {
                    $order->add_order_note('ERROR MESSAGE: ' . __('Invalid or missing payment token fields.', 'paypal-for-woocommerce'));
                }
            }
        }
    }

    public function is_subscription($order_id) {
        return ( function_exists('wcs_order_contains_subscription') && ( wcs_order_contains_subscription($order_id) || wcs_is_subscription($order_id) || wcs_order_contains_renewal($order_id) ) );
    }

    public function angelleye_paypal_for_woo_wc_autoship_cart_has_autoship_item() {
        if (!function_exists('WC')) {
            return false;
        }
        $cart = WC()->cart;
        if (empty($cart)) {
            return false;
        }
        $has_autoship_items = false;
        foreach ($cart->get_cart() as $item) {
            if (isset($item['wc_autoship_frequency'])) {
                $has_autoship_items = true;
                break;
            }
        }
        return $has_autoship_items;
    }

}
