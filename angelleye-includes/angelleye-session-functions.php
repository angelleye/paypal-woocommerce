<?php

if (!function_exists('angelleye_set_session')) {

    function angelleye_set_session($key, $value) {
        if (!class_exists('WooCommerce') || WC()->session == null) {
            return false;
        }
        WC()->session->set($key, $value);
    }

}
if (!function_exists('angelleye_get_session')) {

    function angelleye_get_session($key) {
        if (!class_exists('WooCommerce') || WC()->session == null) {
            return false;
        }
        $angelleye_session = WC()->session->get($key);
        return $angelleye_session;
    }

}
if (!function_exists('angelleye_unset_session')) {

    function angelleye_unset_session($key) {
        if (!class_exists('WooCommerce') || WC()->session == null) {
            return false;
        }
        WC()->session->__unset($key);
        unset(WC()->session->$key);
    }

}