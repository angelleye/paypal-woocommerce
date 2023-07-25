<?php

/**
 * @since      1.0.0
 * @package    AngellEYE_PayPal_PPCP_Migration_Revert
 * @subpackage AngellEYE_PayPal_PPCP_Migration_Revert/includes
 * @author     AngellEYE <andrew@angelleye.com>
 */
defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Migration_Revert {

    protected static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function angelleye_ppcp_to_paypal_express() {
        try {
            $woocommerce_paypal_express_settings = get_option('woocommerce_paypal_express_settings');
            $woocommerce_paypal_express_settings['enabled'] = 'yes';
            $paypal_api_keys = array('sandbox_api_username', 'sandbox_api_password', 'sandbox_api_signature', 'api_username', 'api_password', 'api_signature');
            foreach ($paypal_api_keys as $gateway_settings_key => $gateway_settings_value) {
                if (!empty($woocommerce_paypal_express_settings[$gateway_settings_value])) {
                    $woocommerce_paypal_express_settings[$gateway_settings_value] = AngellEYE_Utility::crypting($woocommerce_paypal_express_settings[$gateway_settings_value], $action = 'e');
                }
            }
            update_option('woocommerce_paypal_express_settings', $woocommerce_paypal_express_settings);
        } catch (Exception $ex) {

        }
    }

    public function angelleye_ppcp_to_paypal_pro() {
        try {

            $woocommerce_paypal_pro_settings = get_option('woocommerce_paypal_pro_settings');
            $woocommerce_paypal_pro_settings['enabled'] = 'yes';
            $gateway_settings_key_array = array('sandbox_api_username', 'sandbox_api_password', 'sandbox_api_signature', 'api_username', 'api_password', 'api_signature');
            foreach ($gateway_settings_key_array as $gateway_settings_key => $gateway_settings_value) {
                if (!empty($woocommerce_paypal_pro_settings[$gateway_settings_value])) {
                    $woocommerce_paypal_pro_settings[$gateway_settings_value] = AngellEYE_Utility::crypting($woocommerce_paypal_pro_settings[$gateway_settings_value], $action = 'e');
                }
            }
            update_option('woocommerce_paypal_pro_settings', $woocommerce_paypal_pro_settings);
        } catch (Exception $ex) {

        }
    }

    public function angelleye_ppcp_to_paypal_pro_payflow() {
        try {

            $woocommerce_paypal_pro_payflow_settings = get_option('woocommerce_paypal_pro_payflow_settings');
            $woocommerce_paypal_pro_payflow_settings['enabled'] = 'yes';
            $gateway_settings_key_array = array('sandbox_paypal_vendor', 'sandbox_paypal_password', 'sandbox_paypal_user', 'sandbox_paypal_partner', 'paypal_vendor', 'paypal_password', 'paypal_user', 'paypal_partner');
            foreach ($gateway_settings_key_array as $gateway_settings_key => $gateway_settings_value) {
                if (!empty($woocommerce_paypal_pro_payflow_settings[$gateway_settings_value])) {
                    $woocommerce_paypal_pro_payflow_settings[$gateway_settings_value] = AngellEYE_Utility::crypting($woocommerce_paypal_pro_payflow_settings[$gateway_settings_value], $action = 'e');
                }
            }
            update_option('woocommerce_paypal_pro_payflow_settings', $woocommerce_paypal_pro_payflow_settings);
        } catch (Exception $ex) {

        }
    }

    public function angelleye_ppcp_to_paypal_advanced() {
        try {

            $woocommerce_paypal_advanced_settings = get_option('woocommerce_paypal_advanced_settings');
            $woocommerce_paypal_advanced_settings['enabled'] = 'yes';
            $gateway_settings_key_array = array('loginid', 'resellerid', 'user', 'password');
            foreach ($gateway_settings_key_array as $gateway_settings_key => $gateway_settings_value) {
                if (!empty($woocommerce_paypal_advanced_settings[$gateway_settings_value])) {
                    $woocommerce_paypal_advanced_settings[$gateway_settings_value] = AngellEYE_Utility::crypting($woocommerce_paypal_advanced_settings[$gateway_settings_value], $action = 'e');
                }
            }
            update_option('woocommerce_paypal_advanced_settings', $woocommerce_paypal_advanced_settings);
        } catch (Exception $ex) {

        }
    }

    public function angelleye_ppcp_to_paypal_credit_card_rest() {
        try {

            $woocommerce_paypal_credit_card_rest_settings = get_option('woocommerce_paypal_credit_card_rest_settings');
            $woocommerce_paypal_credit_card_rest_settings['enabled'] = 'yes';
            $gateway_settings_key_array = array('rest_client_id_sandbox', 'rest_secret_id_sandbox', 'rest_client_id', 'rest_secret_id');
            foreach ($gateway_settings_key_array as $gateway_settings_key => $gateway_settings_value) {
                if (!empty($woocommerce_paypal_credit_card_rest_settings[$gateway_settings_value])) {
                    $woocommerce_paypal_credit_card_rest_settings[$gateway_settings_value] = AngellEYE_Utility::crypting($woocommerce_paypal_credit_card_rest_settings[$gateway_settings_value], $action = 'e');
                }
            }
            update_option('woocommerce_paypal_credit_card_rest_settings', $woocommerce_paypal_credit_card_rest_settings);
        } catch (Exception $ex) {

        }
    }

    public function angelleye_ppcp_to_paypal() {
        try {
            $woocommerce_paypal_settings = get_option('woocommerce_paypal_settings');
            $woocommerce_paypal_settings['enabled'] = 'yes';
            update_option('woocommerce_paypal_settings', $woocommerce_paypal_settings);
        } catch (Exception $ex) {

        }
    }

    public function angelleye_ppcp_to_ppec_paypal() {
        try {
            $woocommerce_ppec_paypal_settings = get_option('woocommerce_ppec_paypal_settings');
            $woocommerce_ppec_paypal_settings['enabled'] = 'yes';
            update_option('woocommerce_ppec_paypal_settings', $woocommerce_ppec_paypal_settings);
        } catch (Exception $ex) {

        }
    }

}
