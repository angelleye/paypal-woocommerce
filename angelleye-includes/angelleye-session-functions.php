<?php

if (!function_exists('angelleye_set_session')) {

    function angelleye_set_session($key, $value) {
        try {
            if (!class_exists('WooCommerce') || !function_exists('WC')) {
                return false;
            }
            if (WC()->session) {
                WC()->session->set($key, $value);
            } else {
                return false;
            }
        } catch (Exception $ex) {

        }
    }

}
if (!function_exists('angelleye_get_session')) {

    function angelleye_get_session($key) {
        try {
            if (!class_exists('WooCommerce') || !function_exists('WC')) {
                return false;
            }
            if (WC()->session) {
                $angelleye_session = WC()->session->get($key);
                return $angelleye_session;
            } else {
                return false;
            }
        } catch (Exception $ex) {

        }
    }

}
if (!function_exists('angelleye_unset_session')) {

    try {

        function angelleye_unset_session($key) {
            if (!class_exists('WooCommerce') || !function_exists('WC')) {
                return false;
            }
            if (WC()->session) {
                WC()->session->__unset($key);
                unset(WC()->session->$key);
            }
        }

    } catch (Exception $ex) {

    }
}

if (!function_exists('angelleye_session_init')) {

    function angelleye_session_init() {
        if (is_admin()) {
            return false;
        }
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        if(!$old_wc) {
            $session_class = apply_filters('woocommerce_session_handler', 'WC_Session_Handler');
            $session = new $session_class();
            $session->init();
        } else {
            return false;
        }
    }

}

/**
 * This function creates backup of the PayPal express checkouts sessions before the YITH deposit creates suborder
 */
function angelleye_backup_express_checkout_session() {
    $save_session_keys = [
        'paypal_express_checkout', 'paypal_express_terms', 'ec_save_to_account', 'post_data',
        'shiptoname', 'payeremail', 'validate_data', 'angelleye_fraudnet_f'
    ];
    $backup_array = [];
    foreach ($save_session_keys as $session_key) {
        $backup_array[$session_key] = angelleye_get_session($session_key);
    }

    angelleye_set_session("ae_yith_session_backup", $backup_array);
}

/**
 * This function restores the paypal express checkout sessions after the yith deposit creates suborder
 */
function angelleye_restore_express_checkout_session() {
    $ae_yith_session_backup = angelleye_get_session("ae_yith_session_backup");

    if (is_array($ae_yith_session_backup)) {
        foreach ($ae_yith_session_backup as $session_key => $data) {
            angelleye_set_session($session_key, $data);
        }
    }

    angelleye_unset_session("ae_yith_session_backup");
}
