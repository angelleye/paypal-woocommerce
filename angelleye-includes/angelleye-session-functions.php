<?php

if (!function_exists('angelleye_set_session')) {

    function angelleye_set_session($key, $value) {
        if (!class_exists('WooCommerce') || !function_exists('WC')) {
            return false;
        }
        if (WC()->session == null) {
            WC()->initialize_session();
        }
        if (WC()->session) {
            WC()->session->set($key, $value);
        } else {
            return false;
        }
    }

}
if (!function_exists('angelleye_get_session')) {

    function angelleye_get_session($key) {

        if (!class_exists('WooCommerce') || !function_exists('WC')) {
            return false;
        }
        if (WC()->session == null) {
            WC()->initialize_session();
        }
        if (WC()->session) {
            $angelleye_session = WC()->session->get($key);
            return $angelleye_session;
        } else {
            return false;
        }
    }

}
if (!function_exists('angelleye_unset_session')) {

    function angelleye_unset_session($key) {
        if (!class_exists('WooCommerce') || !function_exists('WC')) {
            return false;
        }
        if (WC()->session == null) {
            WC()->initialize_session();
        }
        if (WC()->session) {
            WC()->session->__unset($key);
            unset(WC()->session->$key);
        }
    }

}