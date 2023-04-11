<?php

/**
 * @since      1.0.0
 * @package    AngellEYE_PayPal_PPCP_Product
 * @subpackage AngellEYE_PayPal_PPCP_Product/includes
 * @author     AngellEYE <andrew@angelleye.com>
 */
defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Vault_Sync {

    protected static $_instance = null;
    public $payment_request;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->angelleye_ppcp_load_class();
    }

    public function angelleye_ppcp_wc_get_customer_saved_methods_list() {
        $saved_methods = wc_get_customer_saved_methods_list(get_current_user_id());
        $paypal_payment_list = $this->payment_request->angelleye_ppcp_get_all_payment_tokens();
        foreach ($paypal_payment_list as $paypal_payment_list_key => $paypal_payment_list_data) {
            if (isset($paypal_payment_list_data['id'])) {
                if (empty($saved_methods) || $this->angelleye_ppcp_is_paypal_vault_id_exist_in_woo_payment_list($saved_methods, $paypal_payment_list_data['id']) === false) {
                    $this->angelleye_ppcp_wc_add_payment_token($paypal_payment_list_data);
                } else {
                    $this->angelleye_ppcp_wc_update_payment_token($paypal_payment_list_data);
                }
            }
        }
        $saved_methods = wc_get_customer_saved_methods_list(get_current_user_id());
        if (!empty($saved_methods['cc'])) {
            foreach ($saved_methods['cc'] as $woo_save_method_key => $woo_saved_methods_list) {
                if (isset($woo_saved_methods_list['_angelleye_ppcp_used_payment_method'])) {
                    if ($this->angelleye_ppcp_is_woo_vault_id_exist_in_paypal_method_list($paypal_payment_list, $woo_saved_methods_list['vault_id']) === false) {
                        unset($saved_methods['cc'][$woo_save_method_key]);
                    }
                }
            }
        }
        return $saved_methods;
    }

    public function angelleye_ppcp_load_class() {
        if (!class_exists('AngellEYE_PayPal_PPCP_Payment')) {
            include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-payment.php');
        }
        $this->payment_request = AngellEYE_PayPal_PPCP_Payment::instance();
    }

    public function angelleye_ppcp_is_woo_vault_id_exist_in_paypal_method_list($paypal_payment_list, $vault_id) {
        try {
            foreach ($paypal_payment_list as $paypal_key => $paypal_saved_methods_list) {
                if ($paypal_saved_methods_list['id'] === $vault_id) {
                    return true;
                }
            }
            return false;
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_wc_add_payment_token($api_response) {
        try {
            $payment_token = '';
            if (isset($api_response['id'])) {
                $payment_token = $api_response['id'];
            }
            $token = new WC_Payment_Token_CC();
            $customer_id = get_current_user_id();
            $token->set_token($payment_token);
            $token->set_user_id($customer_id);
            if (!empty($api_response['payment_source']['card'])) {
                $token->set_gateway_id('angelleye_ppcp_cc');
                $token->set_card_type($api_response['payment_source']['card']['brand']);
                $token->set_last4($api_response['payment_source']['card']['last_digits']);
                $card_expiry = array_map('trim', explode('-', $api_response['payment_source']['card']['expiry']));
                $card_exp_year = str_pad($card_expiry[0], 4, "0", STR_PAD_LEFT);
                $card_exp_month = isset($card_expiry[1]) ? $card_expiry[1] : '';
                $token->set_expiry_month($card_exp_month);
                $token->set_expiry_year($card_exp_year);
                if ($token->validate()) {
                    $token->save();
                    update_metadata('payment_token', $token->get_id(), '_angelleye_ppcp_used_payment_method', 'card');
                }
            } elseif (!empty($api_response['payment_source']['paypal'])) {
                if (isset($api_response['payment_source']['paypal']['email_address'])) {
                    $email_address = $api_response['payment_source']['paypal']['email_address'];
                } elseif ($api_response['payment_source']['paypal']['payer_id']) {
                    $email_address = $api_response['payment_source']['paypal']['payer_id'];
                } else {
                    $email_address = 'PayPal';
                }
                $token->set_gateway_id('angelleye_ppcp');
                $token->set_card_type($email_address);
                $token->set_last4(substr($payment_token, -4));
                $token->set_expiry_month(date('m'));
                $token->set_expiry_year(date('Y', strtotime('+20 years')));
                if ($token->validate()) {
                    $token->save();
                    update_metadata('payment_token', $token->get_id(), '_angelleye_ppcp_used_payment_method', 'paypal');
                }
            } elseif (!empty($api_response['payment_source']['venmo'])) {
                if (isset($api_response['payment_source']['venmo']['email_address'])) {
                    $email_address = $api_response['payment_source']['venmo']['email_address'];
                } elseif ($api_response['payment_source']['venmo']['payer_id']) {
                    $email_address = $api_response['payment_source']['venmo']['payer_id'];
                } else {
                    $email_address = 'Venmo';
                }
                $token->set_gateway_id('angelleye_ppcp');
                $token->set_card_type($email_address);
                $token->set_last4(substr($payment_token, -4));
                $token->set_expiry_month(date('m'));
                $token->set_expiry_year(date('Y', strtotime('+20 years')));
                if ($token->validate()) {
                    $token->save();
                    update_metadata('payment_token', $token->get_id(), '_angelleye_ppcp_used_payment_method', 'venmo');
                }
            }
        } catch (Exception $ex) {
            
        }
    }
    
    public function angelleye_ppcp_wc_update_payment_token($api_response) {
        try {
            $payment_token = '';
            if (isset($api_response['id'])) {
                $payment_token = $api_response['id'];
            }
            $token_id = angelleye_ppcp_get_token_id_by_token($payment_token);
            $token = WC_Payment_Tokens::get($token_id);
            $customer_id = get_current_user_id();
            $token->set_token($payment_token);
            $token->set_user_id($customer_id);
            if (!empty($api_response['payment_source']['card'])) {
                $token->set_gateway_id('angelleye_ppcp_cc');
                $token->set_card_type($api_response['payment_source']['card']['brand']);
                $token->set_last4($api_response['payment_source']['card']['last_digits']);
                $card_expiry = array_map('trim', explode('-', $api_response['payment_source']['card']['expiry']));
                $card_exp_year = str_pad($card_expiry[0], 4, "0", STR_PAD_LEFT);
                $card_exp_month = isset($card_expiry[1]) ? $card_expiry[1] : '';
                $token->set_expiry_month($card_exp_month);
                $token->set_expiry_year($card_exp_year);
                if ($token->validate()) {
                    $token->save();
                    update_metadata('payment_token', $token->get_id(), '_angelleye_ppcp_used_payment_method', 'card');
                }
            } elseif (!empty($api_response['payment_source']['paypal'])) {
                if (isset($api_response['payment_source']['paypal']['email_address'])) {
                    $email_address = $api_response['payment_source']['paypal']['email_address'];
                } elseif ($api_response['payment_source']['paypal']['payer_id']) {
                    $email_address = $api_response['payment_source']['paypal']['payer_id'];
                } else {
                    $email_address = 'PayPal';
                }
                $token->set_gateway_id('angelleye_ppcp');
                $token->set_card_type($email_address);
                $token->set_last4(substr($payment_token, -4));
                $token->set_expiry_month(date('m'));
                $token->set_expiry_year(date('Y', strtotime('+20 years')));
                if ($token->validate()) {
                    $token->save();
                    update_metadata('payment_token', $token->get_id(), '_angelleye_ppcp_used_payment_method', 'paypal');
                }
            } elseif (!empty($api_response['payment_source']['venmo'])) {
                if (isset($api_response['payment_source']['venmo']['email_address'])) {
                    $email_address = $api_response['payment_source']['venmo']['email_address'];
                } elseif ($api_response['payment_source']['venmo']['payer_id']) {
                    $email_address = $api_response['payment_source']['venmo']['payer_id'];
                } else {
                    $email_address = 'Venmo';
                }
                $token->set_gateway_id('angelleye_ppcp');
                $token->set_card_type($email_address);
                $token->set_last4(substr($payment_token, -4));
                $token->set_expiry_month(date('m'));
                $token->set_expiry_year(date('Y', strtotime('+20 years')));
                if ($token->validate()) {
                    $token->save();
                    update_metadata('payment_token', $token->get_id(), '_angelleye_ppcp_used_payment_method', 'venmo');
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_is_paypal_vault_id_exist_in_woo_payment_list($saved_methods, $vault_id) {
        try {
            if (!empty($saved_methods['cc'])) {
                foreach ($saved_methods['cc'] as $woo_save_method_key => $woo_saved_methods_list) {
                    if (isset($woo_saved_methods_list['_angelleye_ppcp_used_payment_method'])) {
                        if (isset($woo_saved_methods_list['vault_id']) && $woo_saved_methods_list['vault_id'] === $vault_id) {
                            return true;
                        }
                    }
                }
                return false;
            }
        } catch (Exception $ex) {
            
        }
    }

}
