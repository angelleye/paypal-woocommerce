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
        return false;
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

    if (!function_exists('angelleye_ppcp_get_mapped_billing_address')) {

        function angelleye_ppcp_get_mapped_billing_address($checkout_details, $is_name_only = false) {
            if (empty($checkout_details->payer)) {
                return array();
            }
            $angelleye_ppcp_checkout_post = angelleye_ppcp_get_session('angelleye_ppcp_checkout_post');
            if (!empty($angelleye_ppcp_checkout_post)) {
                $billing_address = array();
                $billing_address['first_name'] = !empty($angelleye_ppcp_checkout_post['billing_first_name']) ? $angelleye_ppcp_checkout_post['billing_first_name'] : '';
                $billing_address['last_name'] = !empty($angelleye_ppcp_checkout_post['billing_last_name']) ? $angelleye_ppcp_checkout_post['billing_last_name'] : '';
                $billing_address['company'] = !empty($angelleye_ppcp_checkout_post['billing_company']) ? $angelleye_ppcp_checkout_post['billing_company'] : '';
                $billing_address['country'] = !empty($angelleye_ppcp_checkout_post['billing_country']) ? $angelleye_ppcp_checkout_post['billing_country'] : '';
                $billing_address['address_1'] = !empty($angelleye_ppcp_checkout_post['billing_address_1']) ? $angelleye_ppcp_checkout_post['billing_address_1'] : '';
                $billing_address['address_2'] = !empty($angelleye_ppcp_checkout_post['billing_address_2']) ? $angelleye_ppcp_checkout_post['billing_address_2'] : '';
                $billing_address['city'] = !empty($angelleye_ppcp_checkout_post['billing_city']) ? $angelleye_ppcp_checkout_post['billing_city'] : '';
                $billing_address['state'] = !empty($angelleye_ppcp_checkout_post['billing_state']) ? $angelleye_ppcp_checkout_post['billing_state'] : '';
                $billing_address['postcode'] = !empty($angelleye_ppcp_checkout_post['billing_postcode']) ? $angelleye_ppcp_checkout_post['billing_postcode'] : '';
                $billing_address['phone'] = !empty($angelleye_ppcp_checkout_post['billing_phone']) ? $angelleye_ppcp_checkout_post['billing_phone'] : '';
                $billing_address['email'] = !empty($angelleye_ppcp_checkout_post['billing_email']) ? $angelleye_ppcp_checkout_post['billing_email'] : '';
            } else {
                $phone = '';
                if (!empty($checkout_details->payer->phone_number)) {
                    $phone = $checkout_details->payer->phone_number;
                } elseif (!empty($_POST['billing_phone'])) {
                    $phone = wc_clean($_POST['billing_phone']);
                }
                $billing_address = array();
                $billing_address['first_name'] = !empty($checkout_details->payer->name->given_name) ? $checkout_details->payer->name->given_name : '';
                $billing_address['last_name'] = !empty($checkout_details->payer->name->surname) ? $checkout_details->payer->name->surname : '';
                $billing_address['company'] = !empty($checkout_details->payer->business_name) ? $checkout_details->payer->business_name : '';
                $billing_address['email'] = !empty($checkout_details->payer->email_address) ? $checkout_details->payer->email_address : '';
                if ($is_name_only === false) {
                    if (!empty($checkout_details->payer->address->address_line_1) && !empty($checkout_details->payer->address->postal_code)) {
                        $billing_address['address_1'] = !empty($checkout_details->payer->address->address_line_1) ? $checkout_details->payer->address->address_line_1 : '';
                        $billing_address['address_2'] = !empty($checkout_details->payer->address->address_line_2) ? $checkout_details->payer->address->address_line_2 : '';
                        $billing_address['city'] = !empty($checkout_details->payer->address->admin_area_2) ? $checkout_details->payer->address->admin_area_2 : '';
                        $billing_address['state'] = !empty($checkout_details->payer->address->admin_area_1) ? $checkout_details->payer->address->admin_area_1 : '';
                        $billing_address['postcode'] = !empty($checkout_details->payer->address->postal_code) ? $checkout_details->payer->address->postal_code : '';
                        $billing_address['country'] = !empty($checkout_details->payer->address->country_code) ? $checkout_details->payer->address->country_code : '';
                        $billing_address['phone'] = $phone;
                    } else {
                        $billing_address['address_1'] = !empty($checkout_details->purchase_units[0]->shipping->address->address_line_1) ? $checkout_details->purchase_units[0]->shipping->address->address_line_1 : '';
                        $billing_address['address_2'] = !empty($checkout_details->purchase_units[0]->shipping->address->address_line_2) ? $checkout_details->purchase_units[0]->shipping->address->address_line_2 : '';
                        $billing_address['city'] = !empty($checkout_details->purchase_units[0]->shipping->address->admin_area_2) ? $checkout_details->purchase_units[0]->shipping->address->admin_area_2 : '';
                        $billing_address['state'] = !empty($checkout_details->purchase_units[0]->shipping->address->admin_area_1) ? $checkout_details->purchase_units[0]->shipping->address->admin_area_1 : '';
                        $billing_address['postcode'] = !empty($checkout_details->purchase_units[0]->shipping->address->postal_code) ? $checkout_details->purchase_units[0]->shipping->address->postal_code : '';
                        $billing_address['country'] = !empty($checkout_details->purchase_units[0]->shipping->address->country_code) ? $checkout_details->purchase_units[0]->shipping->address->country_code : '';
                        $billing_address['phone'] = $phone;
                    }
                }
            }
            return $billing_address;
        }

    }

    if (!function_exists('angelleye_ppcp_get_mapped_shipping_address')) {

        function angelleye_ppcp_get_mapped_shipping_address($checkout_details) {
            if (empty($checkout_details->purchase_units[0]) || empty($checkout_details->purchase_units[0]->shipping)) {
                return array();
            }
            if (!empty($checkout_details->purchase_units[0]->shipping->name->full_name)) {
                $name = explode(' ', $checkout_details->purchase_units[0]->shipping->name->full_name);
                $first_name = array_shift($name);
                $last_name = implode(' ', $name);
            } else {
                $first_name = '';
                $last_name = '';
            }
            $result = array(
                'first_name' => $first_name,
                'last_name' => $last_name,
                'address_1' => !empty($checkout_details->purchase_units[0]->shipping->address->address_line_1) ? $checkout_details->purchase_units[0]->shipping->address->address_line_1 : '',
                'address_2' => !empty($checkout_details->purchase_units[0]->shipping->address->address_line_2) ? $checkout_details->purchase_units[0]->shipping->address->address_line_2 : '',
                'city' => !empty($checkout_details->purchase_units[0]->shipping->address->admin_area_2) ? $checkout_details->purchase_units[0]->shipping->address->admin_area_2 : '',
                'state' => !empty($checkout_details->purchase_units[0]->shipping->address->admin_area_1) ? $checkout_details->purchase_units[0]->shipping->address->admin_area_1 : '',
                'postcode' => !empty($checkout_details->purchase_units[0]->shipping->address->postal_code) ? $checkout_details->purchase_units[0]->shipping->address->postal_code : '',
                'country' => !empty($checkout_details->purchase_units[0]->shipping->address->country_code) ? $checkout_details->purchase_units[0]->shipping->address->country_code : '',
            );
            if (!empty($checkout_details->payer->business_name)) {
                $result['company'] = $checkout_details->payer->business_name;
            }
            return $result;
        }

    }

    if (!function_exists('angelleye_ppcp_update_customer_addresses_from_paypal')) {

        function angelleye_ppcp_update_customer_addresses_from_paypal($shipping_details, $billing_details) {
            if (!empty(WC()->customer)) {
                $customer = WC()->customer;

                if (!empty($billing_details['address_1'])) {
                    $customer->set_billing_address($billing_details['address_1']);
                }
                if (!empty($billing_details['address_2'])) {
                    $customer->set_billing_address_2($billing_details['address_2']);
                }
                if (!empty($billing_details['city'])) {
                    $customer->set_billing_city($billing_details['city']);
                }
                if (!empty($billing_details['postcode'])) {
                    $customer->set_billing_postcode($billing_details['postcode']);
                }
                if (!empty($billing_details['state'])) {
                    $customer->set_billing_state($billing_details['state']);
                }
                if (!empty($billing_details['country'])) {
                    $customer->set_billing_country($billing_details['country']);
                }
                if (!empty($shipping_details['address_1'])) {
                    $customer->set_shipping_address($shipping_details['address_1']);
                }
                if (!empty($shipping_details['address_2'])) {
                    $customer->set_shipping_address_2($shipping_details['address_2']);
                }
                if (!empty($shipping_details['city'])) {
                    $customer->set_shipping_city($shipping_details['city']);
                }
                if (!empty($shipping_details['postcode'])) {
                    $customer->set_shipping_postcode($shipping_details['postcode']);
                }
                if (!empty($shipping_details['state'])) {
                    $customer->set_shipping_state($shipping_details['state']);
                }
                if (!empty($shipping_details['country'])) {
                    $customer->set_shipping_country($shipping_details['country']);
                }
            }
        }

    }

    if (!function_exists('angelleye_ppcp_currency_has_decimals')) {

        function angelleye_ppcp_currency_has_decimals($currency) {
            if (in_array($currency, array('HUF', 'JPY', 'TWD'), true)) {
                return false;
            }

            return true;
        }

    }

    if (!function_exists('angelleye_ppcp_round')) {

        function angelleye_ppcp_round($price, $precision) {
            $round_price = round($price, $precision);
            return number_format($round_price, $precision, '.', '');
        }

    }

    if (!function_exists('angelleye_ppcp_number_format')) {

        function angelleye_ppcp_number_format($price, $order) {
            $decimals = 2;

            if (!$this->angelleye_ppcp_currency_has_decimals($order->get_currency())) {
                $decimals = 0;
            }

            return number_format($price, $decimals, '.', '');
        }

    }

    if (!function_exists('angelleye_ppcp_is_valid_order')) {

        function angelleye_ppcp_is_valid_order($order_id) {
            $order = $order_id ? wc_get_order($order_id) : null;
            if ($order) {
                return true;
            }
            return false;
        }

    }

    if (!function_exists('angelleye_ppcp_get_currency')) {

        function angelleye_ppcp_get_currency($woo_order_id = null) {

            if ($woo_order_id != null) {
                $order = wc_get_order($woo_order_id);
                return version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency();
            }

            return get_woocommerce_currency();
        }

    }

    if (!function_exists('angelleye_key_generator')) {

        function angelleye_key_generator() {
            $key = md5(microtime());
            $new_key = '';
            for ($i = 1; $i <= 19; $i++) {
                $new_key .= $key[$i];
                if ($i % 5 == 0 && $i != 19)
                    $new_key .= '';
            }
            return strtoupper($new_key);
        }

    }
}
