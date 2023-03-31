<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_AngellEYE_PayPal_PPCP_Payment_Token {

    protected static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function angelleye_ppcp_add_paypal_generated_customer_id($customer_id, $is_sandbox) {
        try {
            if (is_user_logged_in()) {
                $user = wp_get_current_user();
                $user_id = (int) $user->ID;
                $prefix_ppcp_paypal_customer_id = ($is_sandbox === true) ? 'sandbox_' : '';
                update_user_meta($user_id, $prefix_ppcp_paypal_customer_id . 'angelleye_ppcp_paypal_customer_id', $customer_id);
            }
        } catch (Exception $ex) {

        }
    }

    public function angelleye_ppcp_get_paypal_generated_customer_id_for_renewal($is_sandbox, $user_id) {
        try {
            $angelleye_ppcp_paypal_customer_id = '';
            $prefix_ppcp_paypal_customer_id = ($is_sandbox === true) ? 'sandbox_' : '';
            if (!get_user_meta($user_id, $prefix_ppcp_paypal_customer_id . 'angelleye_ppcp_paypal_customer_id')) {
                return false;
            } else {
                $angelleye_ppcp_paypal_customer_id = get_user_meta($user_id, $prefix_ppcp_paypal_customer_id . 'angelleye_ppcp_paypal_customer_id', true);
            }
            if (!empty($angelleye_ppcp_paypal_customer_id)) {
                return $angelleye_ppcp_paypal_customer_id;
            } else {
                return false;
            }
        } catch (Exception $ex) {
            return false;
        }
    }

    public function angelleye_ppcp_get_paypal_generated_customer_id($is_sandbox) {
        try {
            if (is_user_logged_in()) {
                $angelleye_ppcp_paypal_customer_id = '';
                $user = wp_get_current_user();
                $user_id = (int) $user->ID;
                $prefix_ppcp_paypal_customer_id = ($is_sandbox === true) ? 'sandbox_' : '';
                if (!get_user_meta($user_id, $prefix_ppcp_paypal_customer_id . 'angelleye_ppcp_paypal_customer_id')) {
                    return false;
                } else {
                    $angelleye_ppcp_paypal_customer_id = get_user_meta($user_id, $prefix_ppcp_paypal_customer_id . 'angelleye_ppcp_paypal_customer_id', true);
                }
                if (!empty($angelleye_ppcp_paypal_customer_id)) {
                    return $angelleye_ppcp_paypal_customer_id;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } catch (Exception $ex) {
            return false;
        }
    }

    public function angelleye_ppcp_is_paypal_generated_customer_id_exist($is_sandbox) {
        try {
            if (is_user_logged_in()) {
                $angelleye_ppcp_paypal_customer_id = '';
                $user = wp_get_current_user();
                $user_id = (int) $user->ID;
                $prefix_ppcp_paypal_customer_id = ($is_sandbox === true) ? 'sandbox_' : '';
                if (!get_user_meta($user_id, $prefix_ppcp_paypal_customer_id . 'angelleye_ppcp_paypal_customer_id')) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return false;
            }
        } catch (Exception $ex) {
            return false;
        }
    }

}
