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