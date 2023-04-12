<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Gateway_PPCP_AngellEYE_Subscriptions_Helper {

    protected static $_instance = null;
    public $payment_request;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function angelleye_ppcp_load_class() {
        try {
            if (!class_exists('AngellEYE_PayPal_PPCP_Payment')) {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-payment.php');
            }
            $this->payment_request = AngellEYE_PayPal_PPCP_Payment::instance();
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_is_save_payment_token($current, $order_id) {
        if ((!empty($_POST['wc-angelleye_ppcp_cc-new-payment-method']) && $_POST['wc-angelleye_ppcp_cc-new-payment-method'] == true) || $this->is_subscription($order_id) || $this->angelleye_paypal_for_woo_wc_autoship_cart_has_autoship_item()) {
            return true;
        }
        if ((!empty($_POST['wc-angelleye_ppcp-new-payment-method']) && $_POST['wc-angelleye_ppcp-new-payment-method'] == true) || $this->is_subscription($order_id) || $this->angelleye_paypal_for_woo_wc_autoship_cart_has_autoship_item()) {
            return true;
        }
        return false;
    }

    public function save_payment_token($order, $payment_tokens_id) {
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        $angelleye_ppcp_used_payment_method = get_post_meta($order_id, '_angelleye_ppcp_used_payment_method', true);
        if (function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($order_id)) {
            $subscriptions = wcs_get_subscriptions_for_order($order_id);
        } elseif (function_exists('wcs_order_contains_renewal') && wcs_order_contains_renewal($order_id)) {
            $subscriptions = wcs_get_subscriptions_for_renewal_order($order_id);
        } else {
            $subscriptions = array();
        }
        if (!empty($subscriptions)) {
            foreach ($subscriptions as $subscription) {
                $subscription_id = version_compare(WC_VERSION, '3.0', '<') ? $subscription->id : $subscription->get_id();
                update_post_meta($subscription_id, '_payment_tokens_id', $payment_tokens_id);
                if (!empty($angelleye_ppcp_used_payment_method)) {
                    update_post_meta($subscription_id, '_angelleye_ppcp_used_payment_method', $angelleye_ppcp_used_payment_method);
                }
            }
        } else {
            update_post_meta($order_id, '_payment_tokens_id', $payment_tokens_id);
        }
    }

    public function angelleye_ppcp_wc_save_payment_token($order_id, $api_response) {
        $payment_token = '';
        if (isset($api_response['payment_source']['card']['attributes']['vault']['id'])) {
            $payment_token = $api_response['payment_source']['card']['attributes']['vault']['id'];
        } elseif (isset($api_response['payment_source']['paypal']['attributes']['vault']['id'])) {
            $payment_token = $api_response['payment_source']['paypal']['attributes']['vault']['id'];
        } elseif (isset($api_response['payment_source']['venmo']['attributes']['vault']['id'])) {
            $payment_token = $api_response['payment_source']['venmo']['attributes']['vault']['id'];
        }
        $order = wc_get_order($order_id);
        $this->save_payment_token($order, $payment_token);
        if (angelleye_ppcp_get_token_id_by_token($payment_token) === '') {
            if (!empty($api_response['payment_source']['card']['attributes']['vault']['id'])) {
                $token = new WC_Payment_Token_CC();
                $order = wc_get_order($order_id);
                if (0 != $order->get_user_id()) {
                    $customer_id = $order->get_user_id();
                } else {
                    $customer_id = get_current_user_id();
                }
                $token->set_token($payment_token);
                $token->set_gateway_id($order->get_payment_method());
                $token->set_card_type($api_response['payment_source']['card']['brand']);
                $token->set_last4($api_response['payment_source']['card']['last_digits']);
                if (isset($api_response['payment_source']['card']['expiry'])) {
                    $card_expiry = array_map('trim', explode('-', $api_response['payment_source']['card']['expiry']));
                    $card_exp_year = str_pad($card_expiry[0], 4, "0", STR_PAD_LEFT);
                    $card_exp_month = isset($card_expiry[1]) ? $card_expiry[1] : '';
                    $token->set_expiry_month($card_exp_month);
                    $token->set_expiry_year($card_exp_year);
                } else {
                    $this->angelleye_ppcp_load_class();
                    $card_details = $this->payment_request->angelleye_ppcp_get_payment_token_details($api_response['payment_source']['card']['attributes']['vault']['id']);
                    if (isset($card_details['payment_source']['card']['expiry'])) {
                        $card_expiry = array_map('trim', explode('-', $card_details['payment_source']['card']['expiry']));
                        $card_exp_year = str_pad($card_expiry[0], 4, "0", STR_PAD_LEFT);
                        $card_exp_month = isset($card_expiry[1]) ? $card_expiry[1] : '';
                        $token->set_expiry_month($card_exp_month);
                        $token->set_expiry_year($card_exp_year);
                    } else {
                        $token->set_expiry_month(date('m'));
                        $token->set_expiry_year(date('Y', strtotime('+5 years')));
                    }
                }
                $token->set_user_id($customer_id);
                if ($token->validate()) {
                    $token->save();
                    update_metadata('payment_token', $token->get_id(), '_angelleye_ppcp_used_payment_method', 'card');
                } else {
                    $order->add_order_note('ERROR MESSAGE: ' . __('Invalid or missing payment token fields.', 'paypal-for-woocommerce'));
                }
            } elseif (!empty($api_response['payment_source']['paypal']['attributes']['vault']['id'])) {
                $token = new WC_Payment_Token_CC();
                $order = wc_get_order($order_id);
                if (0 != $order->get_user_id()) {
                    $customer_id = $order->get_user_id();
                } else {
                    $customer_id = get_current_user_id();
                }
                if (isset($api_response['payment_source']['paypal']['email_address'])) {
                    $email_address = $api_response['payment_source']['paypal']['email_address'];
                } elseif ($api_response['payment_source']['paypal']['payer_id']) {
                    $email_address = $api_response['payment_source']['paypal']['payer_id'];
                } else {
                    $email_address = 'PayPal';
                }
                $token->set_token($payment_token);
                $token->set_gateway_id($order->get_payment_method());
                $token->set_card_type($email_address);
                $token->set_last4(substr($payment_token, -4));
                $token->set_expiry_month(date('m'));
                $token->set_expiry_year(date('Y', strtotime('+20 years')));
                $token->set_user_id($customer_id);
                if ($token->validate()) {
                    $token->save();
                    update_metadata('payment_token', $token->get_id(), '_angelleye_ppcp_used_payment_method', 'paypal');
                } else {
                    $order->add_order_note('ERROR MESSAGE: ' . __('Invalid or missing payment token fields.', 'paypal-for-woocommerce'));
                }
            } elseif (!empty($api_response['payment_source']['venmo']['attributes']['vault']['id'])) {
                $token = new WC_Payment_Token_CC();
                $order = wc_get_order($order_id);
                if (0 != $order->get_user_id()) {
                    $customer_id = $order->get_user_id();
                } else {
                    $customer_id = get_current_user_id();
                }
                if (isset($api_response['payment_source']['venmo']['email_address'])) {
                    $email_address = $api_response['payment_source']['venmo']['email_address'];
                } elseif ($api_response['payment_source']['venmo']['payer_id']) {
                    $email_address = $api_response['payment_source']['venmo']['payer_id'];
                } else {
                    $email_address = 'Venmo';
                }
                $token->set_token($payment_token);
                $token->set_gateway_id($order->get_payment_method());
                $token->set_card_type($email_address);
                $token->set_last4(substr($payment_token, -4));
                $token->set_expiry_month(date('m'));
                $token->set_expiry_year(date('Y', strtotime('+20 years')));
                $token->set_user_id($customer_id);
                if ($token->validate()) {
                    $token->save();
                    update_metadata('payment_token', $token->get_id(), '_angelleye_ppcp_used_payment_method', 'venmo');
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
