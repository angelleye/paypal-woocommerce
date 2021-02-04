<?php

if (!function_exists('angelleye_ppcp_remove_empty_key')) {

    function angelleye_ppcp_remove_empty_key($data) {
        $original = $data;
        $data = array_filter($data);
        $data = array_map(function ($e) {
            return is_array($e) ? angelleye_ppcp_remove_empty_key($e) : $e;
        }, $data);
        return $original === $data ? $data : angelleye_ppcp_remove_empty_key($data);
    }

}
if (!function_exists('angelleye_ppcp_set_session')) {

    function angelleye_ppcp_set_session($key, $value) {
        if (!class_exists('WooCommerce') || WC()->session == null) {
            return false;
        }
        $angelleye_ppcp_session = WC()->session->get('angelleye_ppcp_session');
        if (!is_array($angelleye_ppcp_session)) {
            $angelleye_ppcp_session = array();
        }
        $angelleye_ppcp_session[$key] = $value;
        WC()->session->set('angelleye_ppcp_session', $angelleye_ppcp_session);
    }

}
if (!function_exists('angelleye_ppcp_get_session')) {

    function angelleye_ppcp_get_session($key) {
        if (!class_exists('WooCommerce') || WC()->session == null) {
            return false;
        }

        $angelleye_ppcp_session = WC()->session->get('angelleye_ppcp_session');
        if (!empty($angelleye_ppcp_session[$key])) {
            return $angelleye_ppcp_session[$key];
        }
        return false;
    }

}
if (!function_exists('angelleye_ppcp_unset_session')) {

    function angelleye_ppcp_unset_session($key) {
        if (!class_exists('WooCommerce') || WC()->session == null) {
            return false;
        }
        $angelleye_ppcp_session = WC()->session->get('angelleye_ppcp_session');
        if (!empty($angelleye_ppcp_session[$key])) {
            unset($angelleye_ppcp_session[$key]);
            WC()->session->set('angelleye_ppcp_session', $angelleye_ppcp_session);
        }
    }

}
if (!function_exists('angelleye_ppcp_has_active_session')) {

    function angelleye_ppcp_has_active_session() {
        $checkout_details = angelleye_ppcp_get_session('angelleye_ppcp_paypal_transaction_details');
        $angelleye_ppcp_paypal_order_id = angelleye_ppcp_get_session('angelleye_ppcp_paypal_order_id');
        if (!empty($checkout_details) && !empty($angelleye_ppcp_paypal_order_id) && isset($_GET['paypal_order_id'])) {
            return true;
        }
        return false;
    }

}
if (!function_exists('angelleye_ppcp_update_post_meta')) {

    function angelleye_ppcp_update_post_meta($order, $key, $value) {
        if (!is_object($order)) {
            $order = wc_get_order($order);
        }
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        if ($old_wc) {
            update_post_meta($order->id, $key, $value);
        } else {
            $order->update_meta_data($key, $value);
        }
        if (!$old_wc) {
            $order->save_meta_data();
        }
    }

}
if (!function_exists('angelleye_ppcp_get_post_meta')) {

    function angelleye_ppcp_get_post_meta($order, $key, $bool = true) {
        $order_meta_value = false;
        if (!is_object($order)) {
            $order = wc_get_order($order);
        }
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        if ($old_wc) {
            $order_meta_value = get_post_meta($order->id, $key, $bool);
        } else {
            $order_meta_value = $order->get_meta($key, $bool);
        }
        return $order_meta_value;
    }

}
if (!function_exists('angelleye_ppcp_get_button_locale_code')) {

    function angelleye_ppcp_get_button_locale_code() {
        $_supportedLocale = array(
            'en_US', 'fr_XC', 'es_XC', 'zh_XC', 'en_AU', 'de_DE', 'nl_NL',
            'fr_FR', 'pt_BR', 'fr_CA', 'zh_CN', 'ru_RU', 'en_GB', 'zh_HK',
            'he_IL', 'it_IT', 'ja_JP', 'pl_PL', 'pt_PT', 'es_ES', 'sv_SE', 'zh_TW', 'tr_TR'
        );
        $wpml_locale = angelleye_ppcp_get_wpml_locale();
        if ($wpml_locale) {
            if (in_array($wpml_locale, $_supportedLocale)) {
                return $wpml_locale;
            }
        }
        $locale = get_locale();
        if (get_locale() != '') {
            $locale = substr(get_locale(), 0, 5);
        }
        if (!in_array($locale, $_supportedLocale)) {
            $locale = 'en_US';
        }
        return $locale;
    }

}
if (!function_exists('angelleye_ppcp_get_wpml_locale')) {

    function angelleye_ppcp_get_wpml_locale() {
        $locale = false;
        if (defined('ICL_LANGUAGE_CODE') && function_exists('icl_object_id')) {
            global $sitepress;
            if (isset($sitepress)) {
                $locale = $sitepress->get_current_language();
            } else if (function_exists('pll_current_language')) {
                $locale = pll_current_language('locale');
            } else if (function_exists('pll_default_language')) {
                $locale = pll_default_language('locale');
            }
        }
        return $locale;
    }

}


if (!function_exists('angelleye_ppcp_is_local_server')) {

    function angelleye_ppcp_is_local_server() {
        if (!isset($_SERVER['HTTP_HOST'])) {
            return;
        }
        if ($_SERVER['HTTP_HOST'] === 'localhost' || substr($_SERVER['REMOTE_ADDR'], 0, 3) === '10.' || substr($_SERVER['REMOTE_ADDR'], 0, 7) === '192.168') {
            return true;
        }
        $live_sites = [
            'HTTP_CLIENT_IP',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
        ];
        foreach ($live_sites as $ip) {
            if (!empty($_SERVER[$ip])) {
                return false;
            }
        }
        if (in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'))) {
            return true;
        }
        $fragments = explode('.', site_url());
        if (in_array(end($fragments), array('dev', 'local', 'localhost', 'test'))) {
            return true;
        }
        return false;
    }

    if (!function_exists('angelleye_ppcp_get_raw_data')) {

        function angelleye_ppcp_get_raw_data() {
            try {
                if (function_exists('phpversion') && version_compare(phpversion(), '5.6', '>=')) {
                    return file_get_contents('php://input');
                }
                global $HTTP_RAW_POST_DATA;
                if (!isset($HTTP_RAW_POST_DATA)) {
                    $HTTP_RAW_POST_DATA = file_get_contents('php://input');
                }
                return $HTTP_RAW_POST_DATA;
            } catch (Exception $ex) {
                
            }
        }

    }

    if (!function_exists('angelleye_ppcp_remove_empty_key')) {

        function angelleye_ppcp_remove_empty_key($data) {
            $original = $data;
            $data = array_filter($data);
            $data = array_map(function ($e) {
                return is_array($e) ? angelleye_ppcp_remove_empty_key($e) : $e;
            }, $data);
            return $original === $data ? $data : angelleye_ppcp_remove_empty_key($data);
        }

    }
    if (!function_exists('angelleye_ppcp_readable')) {

        function angelleye_ppcp_readable($tex) {
            $tex = ucwords(strtolower(str_replace('_', ' ', $tex)));
            return $tex;
        }

    }
}
