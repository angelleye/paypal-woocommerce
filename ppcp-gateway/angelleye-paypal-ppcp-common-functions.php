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
        if (is_ajax() && !empty($checkout_details) && !empty($angelleye_ppcp_paypal_order_id)) {
            return true;
        } elseif (!empty($checkout_details) && !empty($angelleye_ppcp_paypal_order_id) && isset($_GET['paypal_order_id'])) {
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
        if (!is_a($order, 'WC_Order')) {
            return;
        }
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        if ($old_wc) {
            update_post_meta($order->id, $key, $value);
        } else {
            $order->update_meta_data($key, $value);
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
        if (empty($order_meta_value) && $key === '_paymentaction') {
            if ($old_wc) {
                $order_meta_value = get_post_meta($order->id, '_payment_action', $bool);
            } else {
                $order_meta_value = $order->get_meta('_payment_action', $bool);
            }
        } elseif (empty($order_meta_value) && $key === '_payment_action') {
            if ($old_wc) {
                $order_meta_value = get_post_meta($order->id, '_paymentaction', $bool);
            } else {
                $order_meta_value = $order->get_meta('_paymentaction', $bool);
            }
        } elseif ($key === '_payment_method_title') {
            if ($old_wc) {
                $angelleye_ppcp_used_payment_method = get_post_meta($order->id, '_angelleye_ppcp_used_payment_method', $bool);
            } else {
                $angelleye_ppcp_used_payment_method = $order->get_meta('_angelleye_ppcp_used_payment_method', $bool);
            }
            if (!empty($angelleye_ppcp_used_payment_method)) {
                return angelleye_ppcp_get_payment_method_title($angelleye_ppcp_used_payment_method);
            }
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
    }

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
            $billing_address['state'] = !empty($angelleye_ppcp_checkout_post['billing_state']) ? angelleye_ppcp_validate_checkout($angelleye_ppcp_checkout_post['billing_country'], $angelleye_ppcp_checkout_post['billing_state'], 'shipping') : '';
            $billing_address['postcode'] = !empty($angelleye_ppcp_checkout_post['billing_postcode']) ? $angelleye_ppcp_checkout_post['billing_postcode'] : '';
            $billing_address['phone'] = !empty($angelleye_ppcp_checkout_post['billing_phone']) ? $angelleye_ppcp_checkout_post['billing_phone'] : '';
            $billing_address['email'] = !empty($angelleye_ppcp_checkout_post['billing_email']) ? $angelleye_ppcp_checkout_post['billing_email'] : '';
        } else {
            $phone = '';
            if (!empty($checkout_details->payer->phone->phone_number->national_number)) {
                $phone = $checkout_details->payer->phone->phone_number->national_number;
            } elseif (!empty($_POST['billing_phone'])) {
                $phone = wc_clean($_POST['billing_phone']);
            }
            $billing_address = array();
            $billing_address['first_name'] = !empty($checkout_details->payer->name->given_name) ? $checkout_details->payer->name->given_name : '';
            $billing_address['last_name'] = !empty($checkout_details->payer->name->surname) ? $checkout_details->payer->name->surname : '';
            $billing_address['company'] = !empty($checkout_details->payer->business_name) ? $checkout_details->payer->business_name : '';
            $billing_address['email'] = !empty($checkout_details->payer->email_address) ? $checkout_details->payer->email_address : '';
            if ($is_name_only === false || (wc_ship_to_billing_address_only() && WC()->cart->needs_shipping())) {
                if (!empty($checkout_details->payer->address->address_line_1) && !empty($checkout_details->payer->address->postal_code)) {
                    $billing_address['address_1'] = !empty($checkout_details->payer->address->address_line_1) ? $checkout_details->payer->address->address_line_1 : '';
                    $billing_address['address_2'] = !empty($checkout_details->payer->address->address_line_2) ? $checkout_details->payer->address->address_line_2 : '';
                    $billing_address['city'] = !empty($checkout_details->payer->address->admin_area_2) ? $checkout_details->payer->address->admin_area_2 : '';
                    $billing_address['state'] = !empty($checkout_details->payer->address->admin_area_1) ? angelleye_ppcp_validate_checkout($checkout_details->payer->address->country_code, $checkout_details->payer->address->admin_area_1, 'shipping') : '';
                    $billing_address['postcode'] = !empty($checkout_details->payer->address->postal_code) ? $checkout_details->payer->address->postal_code : '';
                    $billing_address['country'] = !empty($checkout_details->payer->address->country_code) ? $checkout_details->payer->address->country_code : '';
                    $billing_address['phone'] = $phone;
                } else {
                    $billing_address['address_1'] = !empty($checkout_details->purchase_units[0]->shipping->address->address_line_1) ? $checkout_details->purchase_units[0]->shipping->address->address_line_1 : '';
                    $billing_address['address_2'] = !empty($checkout_details->purchase_units[0]->shipping->address->address_line_2) ? $checkout_details->purchase_units[0]->shipping->address->address_line_2 : '';
                    $billing_address['city'] = !empty($checkout_details->purchase_units[0]->shipping->address->admin_area_2) ? $checkout_details->purchase_units[0]->shipping->address->admin_area_2 : '';
                    $billing_address['state'] = !empty($checkout_details->purchase_units[0]->shipping->address->admin_area_1) ? angelleye_ppcp_validate_checkout($checkout_details->purchase_units[0]->shipping->address->country_code, $checkout_details->purchase_units[0]->shipping->address->admin_area_1, 'shipping') : '';
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
            'state' => !empty($checkout_details->purchase_units[0]->shipping->address->admin_area_1) ? angelleye_ppcp_validate_checkout($checkout_details->purchase_units[0]->shipping->address->country_code, $checkout_details->purchase_units[0]->shipping->address->admin_area_1, 'shipping') : '',
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

if (!function_exists('is_angelleye_aws_down')) {

    function is_angelleye_aws_down() {
        if (false === ( $status = get_transient('is_angelleye_aws_down') )) {
            $args['method'] = 'POST';
            $args['timeout'] = 10;
            $args['user-agent'] = 'PFW_PPCP';
            $response = wp_remote_get(PAYPAL_FOR_WOOCOMMERCE_PPCP_AWS_WEB_SERVICE . 'ppcp-request', $args);
            $status_code = (int) wp_remote_retrieve_response_code($response);
            if (200 < $status_code || empty($response)) {
                $status = 'yes';
                set_transient('is_angelleye_aws_down', $status, 15 * MINUTE_IN_SECONDS);
            } else {
                $status = 'no';
                set_transient('is_angelleye_aws_down', $status, 24 * HOUR_IN_SECONDS);
            }
        }
        if ($status === 'yes') {
            return true;
        }
        return false;
    }

}
if (!function_exists('angelleye_ppcp_processor_response_code')) {

    function angelleye_ppcp_processor_response_code($code = '') {
        $code_list = array('0000' => 'APPROVED',
            '0100' => 'REFERRAL',
            '0800' => 'BAD_RESPONSE_REVERSAL_REQUIRED',
            '1000' => 'PARTIAL_AUTHORIZATION',
            '1300' => 'INVALID_DATA_FORMAT',
            '1310' => 'INVALID_AMOUNT',
            '1312' => 'INVALID_TRANSACTION_CARD_ISSUER_ACQUIRER',
            '1317' => 'INVALID_CAPTURE_DATE',
            '1320' => 'INVALID_CURRENCY_CODE',
            '1330' => 'INVALID_ACCOUNT',
            '1335' => 'INVALID_ACCOUNT_RECURRING',
            '1340' => 'INVALID_TERMINAL',
            '1350' => 'INVALID_MERCHANT',
            '1360' => 'BAD_PROCESSING_CODE',
            '1370' => 'INVALID_MCC',
            '1380' => 'INVALID_EXPIRATION',
            '1382' => 'INVALID_CARD_VERIFICATION_VALUE',
            '1384' => 'INVALID_LIFE_CYCLE_OF_TRANSACTION',
            '1390' => 'INVALID_ORDER',
            '1393' => 'TRANSACTION_CANNOT_BE_COMPLETED',
            '0500' => 'DO_NOT_HONOR',
            '5100' => 'GENERIC_DECLINE',
            '5110' => 'CVV2_FAILURE',
            '5120' => 'INSUFFICIENT_FUNDS',
            '5130' => 'INVALID_PIN',
            '5140' => 'CARD_CLOSED',
            '5150' => 'PICKUP_CARD_SPECIAL_CONDITIONS. Try using another card. Do not retry the same card.',
            '5160' => 'UNAUTHORIZED_USER',
            '5170' => 'AVS_FAILURE',
            '5180' => 'INVALID_OR_RESTRICTED_CARD. Try using another card. Do not retry the same card',
            '5190' => 'SOFT_AVS',
            '5200' => 'DUPLICATE_TRANSACTION',
            '5210' => 'INVALID_TRANSACTION',
            '5400' => 'EXPIRED_CARD',
            '5500' => 'INCORRECT_PIN_REENTER',
            '5700' => 'TRANSACTION_NOT_PERMITTED. Outside of scope of accepted business.',
            '5800' => 'REVERSAL_REJECTED',
            '5900' => 'INVALID_ISSUE',
            '5910' => 'ISSUER_NOT_AVAILABLE_NOT_RETRIABLE',
            '5920' => 'ISSUER_NOT_AVAILABLE_RETRIABLE',
            '6300' => 'ACCOUNT_NOT_ON_FILE',
            '7600' => 'APPROVED_NON_CAPTURE',
            '7700' => 'ERROR_3DS',
            '7710' => 'AUTHENTICATION_FAILED',
            '7800' => 'BIN_ERROR',
            '7900' => 'PIN_ERROR',
            '8000' => 'PROCESSOR_SYSTEM_ERROR',
            '8010' => 'HOST_KEY_ERROR',
            '8020' => 'CONFIGURATION_ERROR',
            '8030' => 'UNSUPPORTED_OPERATION',
            '8100' => 'FATAL_COMMUNICATION_ERROR',
            '8110' => 'RETRIABLE_COMMUNICATION_ERROR',
            '8220' => 'SYSTEM_UNAVAILABLE',
            '9100' => 'DECLINED_PLEASE_RETRY. Retry.',
            '9500' => 'SUSPECTED_FRAUD. Try using another card. Do not retry the same card',
            '9510' => 'SECURITY_VIOLATION',
            '9520' => 'LOST_OR_STOLEN. Try using another card. Do not retry the same card',
            '9530' => 'HOLD_CALL_CENTER. The merchant must call the number on the back of the card. POS scenario',
            '9540' => 'REFUSED_CARD',
            '9600' => 'UNRECOGNIZED_RESPONSE_CODE',
            '5930' => 'CARD_NOT_ACTIVATED',
            'PPMD' => 'PPMD',
            'PPCE' => 'CE_REGISTRATION_INCOMPLETE',
            'PPNT' => 'NETWORK_ERROR',
            'PPCT' => 'CARD_TYPE_UNSUPPORTED',
            'PPTT' => 'TRANSACTION_TYPE_UNSUPPORTED',
            'PPCU' => 'CURRENCY_USED_INVALID',
            'PPQC' => 'QUASI_CASH_UNSUPPORTED',
            'PPVE' => 'VALIDATION_ERROR',
            'PPVT' => 'VIRTUAL_TERMINAL_UNSUPPORTED',
            'PPDC' => 'DCC_UNSUPPORTED',
            'PPER' => 'INTERNAL_SYSTEM_ERROR',
            'PPIM' => 'ID_MISMATCH',
            'PPH1' => 'H1_ERROR',
            'PPSD' => 'STATUS_DESCRIPTION',
            'PPAG' => 'ADULT_GAMING_UNSUPPORTED',
            'PPLS' => 'LARGE_STATUS_CODE',
            'PPCO' => 'COUNTRY',
            'PPAD' => 'BILLING_ADDRESS',
            'PPAU' => 'MCC_CODE',
            'PPUC' => 'CURRENCY_CODE_UNSUPPORTED',
            'PPUR' => 'UNSUPPORTED_REVERSAL',
            'PPVC' => 'VALIDATE_CURRENCY',
            'PPS0' => 'BANKAUTH_ROW_MISMATCH',
            'PPS1' => 'BANKAUTH_ROW_SETTLED',
            'PPS2' => 'BANKAUTH_ROW_VOIDED',
            'PPS3' => 'BANKAUTH_EXPIRED',
            'PPS4' => 'CURRENCY_MISMATCH',
            'PPS5' => 'CREDITCARD_MISMATCH',
            'PPS6' => 'AMOUNT_MISMATCH',
            'PPRF' => 'INVALID_PARENT_TRANSACTION_STATUS',
            'PPEX' => 'EXPIRY_DATE',
            'PPAX' => 'AMOUNT_EXCEEDED',
            'PPDV' => 'AUTH_MESSAGE',
            'PPDI' => 'DINERS_REJECT',
            'PPAR' => 'AUTH_RESULT',
            'PPBG' => 'BAD_GAMING',
            'PPGR' => 'GAMING_REFUND_ERROR',
            'PPCR' => 'CREDIT_ERROR',
            'PPAI' => 'AMOUNT_INCOMPATIBLE',
            'PPIF' => 'IDEMPOTENCY_FAILURE',
            'PPMC' => 'BLOCKED_Mastercard',
            'PPAE' => 'AMEX_DISABLED',
            'PPFV' => 'FIELD_VALIDATION_FAILED',
            'PPII' => 'INVALID_INPUT_FAILURE',
            'PPPM' => 'INVALID_PAYMENT_METHOD',
            'PPUA' => 'USER_NOT_AUTHORIZED',
            'PPFI' => 'INVALID_FUNDING_INSTRUMENT',
            'PPEF' => 'EXPIRED_FUNDING_INSTRUMENT',
            'PPFR' => 'RESTRICTED_FUNDING_INSTRUMENT',
            'PPEL' => 'EXCEEDS_FREQUENCY_LIMIT',
            'PCVV' => 'CVV_FAILURE',
            'PPTV' => 'INVALID_VERIFICATION_TOKEN',
            'PPTE' => 'VERIFICATION_TOKEN_EXPIRED',
            'PPPI' => 'INVALID_PRODUCT',
            'PPIT' => 'INVALID_TRACE_ID',
            'PPTF' => 'INVALID_TRACE_REFERENCE',
            'PPFE' => 'FUNDING_SOURCE_ALREADY_EXISTS',
            'PPTR' => 'VERIFICATION_TOKEN_REVOKED',
            'PPTI' => 'INVALID_TRANSACTION_ID',
            'PPD3' => 'SECURE_ERROR_3DS',
            'PPPH' => 'NO_PHONE_FOR_DCC_TRANSACTION',
            'PPAV' => 'ARC_AVS',
            'PPC2' => 'ARC_CVV',
            'PPLR' => 'LATE_REVERSAL',
            'PPNC' => 'NOT_SUPPORTED_NRC',
            'PPRR' => 'MERCHANT_NOT_REGISTERED',
            'PPSC' => 'ARC_SCORE',
            'PPSE' => 'AMEX_DENIED',
            'PPUE' => 'UNSUPPORT_ENTITY',
            'PPUI' => 'UNSUPPORT_INSTALLMENT',
            'PPUP' => 'UNSUPPORT_POS_FLAG',
            'PPRE' => 'UNSUPPORT_REFUND_ON_PENDING_BC',
        );

        if (isset($code_list[$code])) {
            return $code_list[$code];
        }
        return false;
    }

}

if (!function_exists('angelleye_ppcp_get_payment_method_title')) {

    function angelleye_ppcp_get_payment_method_title($payment_name = '') {
        $final_payment_method_name = '';
        $list_payment_method = array(
            'card' => __('Credit or Debit Card', 'paypal-for-woocommerce'),
            'credit' => __('PayPal Credit', 'paypal-for-woocommerce'),
            'bancontact' => __('Bancontact', 'paypal-for-woocommerce'),
            'blik' => __('BLIK', 'paypal-for-woocommerce'),
            'eps' => __('eps', 'paypal-for-woocommerce'),
            'giropay' => __('giropay', 'paypal-for-woocommerce'),
            'ideal' => __('iDEAL', 'paypal-for-woocommerce'),
            'mercadopago' => __('Mercado Pago', 'paypal-for-woocommerce'),
            'mybank' => __('MyBank', 'paypal-for-woocommerce'),
            'p24' => __('Przelewy24', 'paypal-for-woocommerce'),
            'sepa' => __('SEPA-Lastschrift', 'paypal-for-woocommerce'),
            'sofort' => __('Sofort', 'paypal-for-woocommerce'),
            'venmo' => __('Venmo', 'paypal-for-woocommerce'),
            'paylater' => __('PayPal Pay Later', 'paypal-for-woocommerce'),
            'paypal' => __('PayPal Checkout', 'paypal-for-woocommerce'),
        );
        if (!empty($payment_name)) {
            if (isset($list_payment_method[$payment_name])) {
                $final_payment_method_name = $list_payment_method[$payment_name];
            } else {
                $final_payment_method_name = $payment_name;
            }
        }
        return apply_filters('angelleye_ppcp_get_payment_method_title', $final_payment_method_name, $payment_name, $list_payment_method);
    }

}

if (!function_exists('angelleye_ppcp_is_product_purchasable')) {

    function angelleye_ppcp_is_product_purchasable($product, $enable_tokenized_payments) {
        if ($enable_tokenized_payments === false && $product->is_type('subscription')) {
            return apply_filters('angelleye_ppcp_is_product_purchasable', false, $product);
        }
        if (!is_product() || !$product->is_in_stock() || $product->is_type('external') || ($product->get_price() == '' || $product->get_price() == 0)) {
            return apply_filters('angelleye_ppcp_is_product_purchasable', false, $product);
        }
        return apply_filters('angelleye_ppcp_is_product_purchasable', true, $product);
    }

}

if (!function_exists('angelleye_ppcp_validate_checkout')) {

    function angelleye_ppcp_validate_checkout($country, $state, $sec) {
        $state_value = '';
        $valid_states = WC()->countries->get_states(isset($country) ? $country : ( 'billing' === $sec ? WC()->customer->get_country() : WC()->customer->get_shipping_country() ));
        if (!empty($valid_states) && is_array($valid_states)) {
            $valid_state_values = array_flip(array_map('strtolower', $valid_states));
            if (isset($valid_state_values[strtolower($state)])) {
                $state_value = $valid_state_values[strtolower($state)];
                return $state_value;
            }
        } else {
            return $state;
        }
        if (!empty($valid_states) && is_array($valid_states) && sizeof($valid_states) > 0) {
            if (!in_array($state, array_keys($valid_states))) {
                return false;
            } else {
                return $state;
            }
        }
        return $state_value;
    }

    if (!function_exists('own_angelleye_sendy_list')) {

        function own_angelleye_sendy_list($email) {
            global $wp;
            $name = '';
            if (is_user_logged_in()) {
                $first_name = get_user_meta(get_current_user_id(), 'billing_first_name', true);
                $last_name = get_user_meta(get_current_user_id(), 'billing_last_name', true);
                if (empty($first_name) || empty($last_name)) {
                    $first_name = get_user_meta(get_current_user_id(), 'first_name', true);
                    $last_name = get_user_meta(get_current_user_id(), 'last_name', true);
                }
                $name = $first_name . ' ' . $last_name;
            }
            if (!empty($_SERVER['HTTP_REFERER'])) {
                $current_url = $_SERVER['HTTP_REFERER'];
            } else {
                $current_url = home_url(add_query_arg(array(), $wp->request));
            }
            $url = 'https://sendy.angelleye.com/subscribe';
            $response = wp_remote_post($url, array(
                'method' => 'POST',
                'timeout' => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(),
                'body' => array('list' => 'oV0I12rDwJdMDL2jYzvwPQ',
                    'boolean' => 'true',
                    'email' => $email,
                    'name' => $name,
                    'gdpr' => 'true',
                    'silent' => 'true',
                    'api_key' => 'qFcoVlU2uG3AMYabNTrC',
                    'referrer' => $current_url
                ),
                'cookies' => array()
                    )
            );
            return $response;
        }

    }
}
if (!function_exists('angelleye_ppcp_add_css_js')) {

    function angelleye_ppcp_add_css_js() {
        wp_enqueue_script('angelleye-paypal-checkout-sdk');
        wp_enqueue_script('angelleye_ppcp');
        wp_enqueue_style('angelleye_ppcp');
    }

}

if (!function_exists('angelleye_ppcp_add_async_js')) {

    function angelleye_ppcp_add_async_js() {
        AngellEYE_PayPal_PPCP_Smart_Button::instance();
        $jsUrl = AngellEYE_PayPal_PPCP_Smart_Button::$jsUrl;
        if (!empty($jsUrl)) {
            wp_register_script('angelleye-paypal-checkout-sdk-async', $jsUrl, [], null, true);
            wp_enqueue_script('angelleye-paypal-checkout-sdk-async');
        }
    }

}

if (!function_exists('angelleye_ppcp_get_value')) {

    function angelleye_ppcp_get_value($key, $value) {
        switch ($key) {
            case 'soft_descriptor':
                if (!empty($value)) {
                    return substr($value, 0, 21);
                }
                break;
            default:
                break;
        }
        return $value;
    }

}

if (!function_exists('angelleye_ppcp_is_cart_contains_subscription')) {

    function angelleye_ppcp_is_cart_contains_subscription() {
        $cart_contains_subscription = false;
        if (class_exists('WC_Subscriptions_Order') && class_exists('WC_Subscriptions_Cart')) {
            $cart_contains_subscription = WC_Subscriptions_Cart::cart_contains_subscription();
        }
        return apply_filters('angelleye_ppcp_sdk_parameter_vault', $cart_contains_subscription);
    }

}

if (!function_exists('angelleye_ppcp_is_subs_change_payment')) {

    function angelleye_ppcp_is_subs_change_payment() {
        return ( isset($_GET['pay_for_order']) && ( isset($_GET['change_payment_method']) || isset($_GET['change_gateway_flag'])) );
    }

}

if (!function_exists('angelleye_ppcp_get_order_total')) {

    function angelleye_ppcp_get_order_total($order_id = null) {
        try {
            global $product;
            $total = 0;
            if (is_null($order_id)) {
                $order_id = absint(get_query_var('order-pay'));
            }
            if (is_product()) {
                $total = ( is_a($product, \WC_Product::class) ) ? wc_get_price_including_tax($product) : 1;
            } elseif (0 < $order_id) {
                $order = wc_get_order($order_id);
                if ($order === false) {
                    if (isset(WC()->cart) && 0 < WC()->cart->total) {
                        $total = (float) WC()->cart->total;
                    } else {
                        return 0;
                    }
                }
                $total = (float) $order->get_total();
            } elseif (isset(WC()->cart) && 0 < WC()->cart->total) {
                $total = (float) WC()->cart->total;
            }
            return $total;
        } catch (Exception $ex) {
            return 0;
        }
    }

}

if (!function_exists('angelleye_ppcp_get_view_sub_order_url')) {

    function angelleye_ppcp_get_view_sub_order_url($order_id) {
        $view_subscription_url = wc_get_endpoint_url('view-subscription', $order_id, wc_get_page_permalink('myaccount'));
        return apply_filters('wcs_get_view_subscription_url', $view_subscription_url, $order_id);
    }

}

if (!function_exists('angelleye_ppcp_is_vault_required')) {

    function angelleye_ppcp_is_vault_required($enable_tokenized_payments) {
        global $post, $product;
        $is_enable = false;
        if ($enable_tokenized_payments === false) {
            $is_enable = false;
        } elseif (angelleye_ppcp_is_cart_subscription()) {
            $is_enable = true;
        } elseif ((is_checkout() || is_checkout_pay_page()) && $enable_tokenized_payments === true) {
            $is_enable = true;
        } elseif (is_product()) {
            $product_id = $post->ID;
            $product = wc_get_product($product_id);
            if ($product->is_type('subscription')) {
                $is_enable = true;
            }
        }
        return apply_filters('angelleye_ppcp_vault_attribute', $is_enable);
    }

}

if (!function_exists('angelleye_ppcp_is_cart_subscription')) {

    function angelleye_ppcp_is_cart_subscription() {
        $is_enable = false;
        if (angelleye_ppcp_is_cart_contains_subscription() || angelleye_ppcp_is_subs_change_payment()) {
            $is_enable = true;
        }
        return apply_filters('angelleye_ppcp_is_cart_subscription', $is_enable);
    }

}

if (!function_exists('angelleye_ppcp_is_save_payment_method')) {

    function angelleye_ppcp_is_save_payment_method($enable_tokenized_payments) {
        $is_enable = false;
        if (angelleye_ppcp_is_cart_subscription() && $enable_tokenized_payments === true) {
            $is_enable = true;
        } elseif (isset($_POST['wc-angelleye_ppcp-new-payment-method']) && 'true' === $_POST['wc-angelleye_ppcp-new-payment-method']) {
            $is_enable = true;
        } elseif (isset($_POST['wc-angelleye_ppcp_cc-new-payment-method']) && 'true' === $_POST['wc-angelleye_ppcp_cc-new-payment-method']) {
            $is_enable = true;
        }
        return apply_filters('angelleye_ppcp_is_save_payment_method', $is_enable);
    }

}

if (!function_exists('angelleye_ppcp_get_token_id_by_token')) {

    function angelleye_ppcp_get_token_id_by_token($token_id) {
        try {
            global $wpdb;
            $tokens = $wpdb->get_row(
                    $wpdb->prepare(
                            "SELECT token_id FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE token = %s",
                            $token_id
                    )
            );
            if (isset($tokens->token_id)) {
                return $tokens->token_id;
            }
            return '';
        } catch (Exception $ex) {
            
        }
    }

}


if (!function_exists('angelleye_ppcp_add_used_payment_method_name_to_subscription')) {

    function angelleye_ppcp_add_used_payment_method_name_to_subscription($order_id) {
        $wc_pre_30 = version_compare(WC_VERSION, '3.0.0', '<');
        try {
            $subscriptions = wcs_get_subscriptions_for_order($order_id);
            if (!empty($subscriptions)) {
                foreach ($subscriptions as $subscription) {
                    $subscription_id = $wc_pre_30 ? $subscription->id : $subscription->get_id();
                    $angelleye_ppcp_used_payment_method = get_post_meta($order_id, '_angelleye_ppcp_used_payment_method', true);
                    if (!empty($angelleye_ppcp_used_payment_method)) {
                        update_post_meta($subscription_id, '_angelleye_ppcp_used_payment_method', $angelleye_ppcp_used_payment_method);
                    }
                }
            }
        } catch (Exception $ex) {
            
        }
    }

}



    