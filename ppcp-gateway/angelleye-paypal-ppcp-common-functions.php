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

if (!function_exists('angelleye_ppcp_has_active_session')) {

    function angelleye_ppcp_has_active_session() {
        $checkout_details = AngellEye_Session_Manager::get('paypal_transaction_details');
        $angelleye_ppcp_paypal_order_id = AngellEye_Session_Manager::get('paypal_order_id');
        if (is_ajax() && !empty($checkout_details) && !empty($angelleye_ppcp_paypal_order_id)) {
            return true;
        } elseif (!empty($checkout_details) && !empty($angelleye_ppcp_paypal_order_id) && isset($_GET['paypal_order_id'])) {
            return true;
        }
        return false;
    }

}

if (!function_exists('angelleye_ppcp_get_post_meta')) {

    function angelleye_ppcp_get_post_meta($order, $key, $bool = true) {
        $order_meta_value = false;
        if (!is_object($order)) {
            $order = wc_get_order($order);
        }
        if (!is_a($order, 'WC_Order')) {
            return;
        }
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        if ($old_wc) {
            $order_meta_value = get_post_meta($order->id, $key, $bool);
        } else {
            $order_meta_value = $order->get_meta($key, $bool);
        }
        if (empty($order_meta_value) && $key === '_paymentaction') {
            $order_meta_value = $order->get_meta('_payment_action', $bool);
        } elseif (empty($order_meta_value) && $key === '_payment_action') {
            $order_meta_value = $order->get_meta('_paymentaction', $bool);
        } elseif ($key === '_payment_method_title') {
            $angelleye_ppcp_used_payment_method = $order->get_meta('_angelleye_ppcp_used_payment_method', $bool);
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
        if (!empty($tex)) {
            $tex = ucwords(strtolower(str_replace('_', ' ', $tex)));
        }
        return $tex;
    }

}

if (!function_exists('angelleye_split_name')) {

    function angelleye_split_name($fullName) {
        $parts = explode(' ', $fullName);
        $firstName = array_shift($parts);
        $lastName = implode(' ', $parts);
        return [$firstName, $lastName];
    }

}

if (!function_exists('angelleye_ppcp_get_mapped_billing_address')) {

    function angelleye_ppcp_get_mapped_billing_address($checkout_details, $is_name_only = false) {
        global $woocommerce;
        $billing_address = [
            'first_name' => $woocommerce->customer->get_billing_first_name(),
            'last_name' => $woocommerce->customer->get_billing_last_name(),
            'email' => $woocommerce->customer->get_billing_email(),
            'country' => $woocommerce->customer->get_billing_country(),
            'address_1' => $woocommerce->customer->get_billing_address_1(),
            'address_2' => $woocommerce->customer->get_billing_address_2(),
            'city' => $woocommerce->customer->get_billing_city(),
            'state' => $woocommerce->customer->get_billing_state(),
            'postcode' => $woocommerce->customer->get_billing_postcode(),
            'phone' => $woocommerce->customer->get_billing_phone(),
            'company' => $woocommerce->customer->get_billing_company()
        ];
        $angelleye_ppcp_checkout_post = AngellEye_Session_Manager::get('checkout_post');
        if (!empty($angelleye_ppcp_checkout_post)) {
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
        } elseif (!empty($checkout_details->payer)) {
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
        if (empty($billing_address['phone'])) {
            $billing_address['phone'] = $woocommerce->customer->get_billing_phone();
        }

        return $billing_address;
    }

}

if (!function_exists('angelleye_ppcp_get_mapped_shipping_address')) {

    function angelleye_ppcp_get_mapped_shipping_address($checkout_details) {
        $initialData = [];
        $isOverridden = AngellEye_Session_Manager::get('shipping_address_updated_from_callback');
        if ($isOverridden) {
            $initialData = angelleye_ppcp_get_overridden_shipping_address();
        }
        if (empty($checkout_details->purchase_units[0]) || empty($checkout_details->purchase_units[0]->shipping)) {
            return $initialData;
        }
        if (!empty($checkout_details->purchase_units[0]->shipping->name->full_name)) {
            $name = angelleye_split_name($checkout_details->purchase_units[0]->shipping->name->full_name);
            $first_name = $name[0];
            $last_name = $name[1];
        } else {
            $first_name = '';
            $last_name = '';
        }

        // Apple Pay payment sends the email address as part of shipping_address info
        $email_address = null;
        if (!empty($checkout_details->purchase_units[0]->shipping->email_address)) {
            $email_address = $checkout_details->purchase_units[0]->shipping->email_address;
        }
        $result = array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email_address' => $email_address,
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
        return array_merge($result, $initialData);
    }

}

if (!function_exists('angelleye_ppcp_get_overridden_shipping_address')) {

    function angelleye_ppcp_get_overridden_shipping_address() {
        global $woocommerce;
        return array(
            'first_name' => $woocommerce->customer->get_shipping_first_name(),
            'last_name' => $woocommerce->customer->get_shipping_last_name(),
            'address_1' => $woocommerce->customer->get_shipping_address_1(),
            'address_2' => $woocommerce->customer->get_shipping_address_2(),
            'city' => $woocommerce->customer->get_shipping_city(),
            'state' => $woocommerce->customer->get_shipping_state(),
            'postcode' => $woocommerce->customer->get_shipping_postcode(),
            'country' => $woocommerce->customer->get_shipping_country(),
        );
    }

}

if (!function_exists('angelleye_ppcp_update_customer_addresses_from_paypal')) {

    function angelleye_ppcp_update_customer_addresses_from_paypal($shipping_details, $billing_details) {
        if (!empty(WC()->customer)) {
            $customer = WC()->customer;
            if (!empty($billing_details['first_name'])) {
                $customer->set_billing_first_name($billing_details['first_name']);
            }
            if (!empty($billing_details['last_name'])) {
                $customer->set_billing_last_name($billing_details['last_name']);
            }
            if (!empty($billing_details['address_1'])) {
                $customer->set_billing_address_1($billing_details['address_1']);
                $customer->set_billing_address($billing_details['address_1']);
            }
            if (!empty($billing_details['address_2'])) {
                $customer->set_billing_address_2($billing_details['address_2']);
            }
            if (!empty($billing_details['city'])) {
                $customer->set_billing_city($billing_details['city']);
            }
            if (!empty($billing_details['email'])) {
                $customer->set_email($billing_details['email']);
                $customer->set_billing_email($billing_details['email']);
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
            if (!empty($billing_details['phone'])) {
                $customer->set_billing_phone($billing_details['phone']);
            }
            if (!empty($shipping_details['first_name'])) {
                $customer->set_shipping_first_name($shipping_details['first_name']);
            }
            if (!empty($shipping_details['last_name'])) {
                $customer->set_shipping_last_name($shipping_details['last_name']);
            }
            if (!empty($shipping_details['address_1'])) {
                $customer->set_shipping_address($shipping_details['address_1']);
                $customer->set_shipping_address_1($shipping_details['address_1']);
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
            $customer->save();
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
        try {
            $price = (float) $price;
            $round_price = round($price, $precision);
            $price = number_format($round_price, $precision, '.', '');
        } catch (Exception $ex) {
            
        }

        return $price;
    }

}

if (!function_exists('angelleye_ppcp_number_format')) {

    function angelleye_ppcp_number_format($price, $order = null) {
        $decimals = 2;

        if (!empty($order) && !angelleye_ppcp_currency_has_decimals($order->get_currency())) {
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
        $currency_code = '';

        if ($woo_order_id != null) {
            $order = wc_get_order($woo_order_id);
            $currency_code = $order->get_currency();
        } else {
            $currency_code = get_woocommerce_currency();
        }

        return $currency_code;
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
            'apple_pay' => __('Apple Pay', 'paypal-for-woocommerce'),
            'google_pay' => __('Google Pay', 'paypal-for-woocommerce'),
        );
        if (!empty($payment_name)) {
            $final_payment_method_name = $list_payment_method[$payment_name] ?? $payment_name;
        }
        return apply_filters('angelleye_ppcp_get_payment_method_title', $final_payment_method_name, $payment_name, $list_payment_method);
    }

}

if (!function_exists('angelleye_ppcp_is_product_purchasable')) {

    function angelleye_ppcp_is_product_purchasable($product, $enable_tokenized_payments) {
        if ($enable_tokenized_payments === false && $product->is_type('subscription')) {
            return apply_filters('angelleye_ppcp_is_product_purchasable', false, $product);
        }
        if (!$product->is_in_stock() || $product->is_type('external') || ($product->get_price() == '' || $product->get_price() == 0)) {
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
        if (!wp_doing_ajax()) {
            wp_enqueue_script('jquery-blockui');
            wp_enqueue_script('angelleye_ppcp-common-functions');
            wp_enqueue_script('angelleye_ppcp-apple-pay');
            wp_enqueue_script('angelleye_ppcp-google-pay');
            wp_enqueue_script('angelleye-paypal-checkout-sdk');
            wp_enqueue_script('angelleye_ppcp');
            wp_enqueue_script('angelleye-pay-later-messaging');
            wp_enqueue_style('angelleye_ppcp');
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

if (!function_exists('angelleye_is_acdc_payments_enable')) {

    function angelleye_is_acdc_payments_enable($result) {
        if (isset($result['products']) && isset($result['capabilities']) && !empty($result['products']) && !empty($result['products'])) {
            foreach ($result['products'] as $key => $product) {
                if (isset($product['vetting_status']) && ('SUBSCRIBED' === $product['vetting_status'] || 'APPROVED' === $product['vetting_status'] ) && isset($product['capabilities']) && is_array($product['capabilities']) && in_array('CUSTOM_CARD_PROCESSING', $product['capabilities'])) {
                    foreach ($result['capabilities'] as $key => $capabilities) {
                        if (isset($capabilities['name']) && 'CUSTOM_CARD_PROCESSING' === $capabilities['name'] && 'ACTIVE' === $capabilities['status']) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
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

                if ($product->is_type('variable')) {
                    $variation_id = $product->get_id();
                    $is_default_variation = false;

                    $available_variations = $product->get_available_variations();

                    if (!empty($available_variations) && is_array($available_variations)) {

                        foreach ($available_variations as $variation_values) {

                            $attributes = !empty($variation_values['attributes']) ? $variation_values['attributes'] : '';

                            if (!empty($attributes) && is_array($attributes)) {

                                foreach ($attributes as $key => $attribute_value) {

                                    $attribute_name = str_replace('attribute_', '', $key);
                                    $default_value = $product->get_variation_default_attribute($attribute_name);
                                    if ($default_value == $attribute_value) {
                                        $is_default_variation = true;
                                    } else {
                                        $is_default_variation = false;
                                        break;
                                    }
                                }
                            }

                            if ($is_default_variation) {
                                $variation_id = !empty($variation_values['variation_id']) ? $variation_values['variation_id'] : 0;
                                break;
                            }
                        }
                    }

                    $variable_product = wc_get_product($variation_id);
                    $total = ( is_a($product, \WC_Product::class) ) ? wc_get_price_including_tax($variable_product) : 1;
                } else {
                    $total = ( is_a($product, \WC_Product::class) ) ? wc_get_price_including_tax($product) : 1;
                }
            } elseif (0 < $order_id) {
                $order = wc_get_order($order_id);
                if ($order === false) {
                    if (isset(WC()->cart) && 0 < WC()->cart->total) {
                        $total = (float) WC()->cart->total;
                    } else {
                        return 0;
                    }
                } else {
                    $total = (float) $order->get_total();
                }
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
        $new_payment_methods_to_check = [
            'wc-angelleye_ppcp-new-payment-method',
            'wc-angelleye_ppcp_cc-new-payment-method',
            'wc-angelleye_ppcp_apple_pay-new-payment-method'
        ];
        if (angelleye_ppcp_is_cart_subscription() && $enable_tokenized_payments === true) {
            $is_enable = true;
        }
        foreach ($new_payment_methods_to_check as $item) {
            if (isset($_POST[$item]) && 'true' === $_POST[$item]) {
                $is_enable = true;
                break;
            }
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
        try {
            if (function_exists('wcs_get_subscriptions_for_order')) {
                $subscriptions = wcs_get_subscriptions_for_order($order_id);
                if (!empty($subscriptions)) {
                    foreach ($subscriptions as $subscription) {
                        $order = wc_get_order($order_id);
                        $angelleye_ppcp_used_payment_method = $order->get_meta('_angelleye_ppcp_used_payment_method', true);
                        if (!empty($angelleye_ppcp_used_payment_method)) {
                            $subscription->update_meta_data('_angelleye_ppcp_used_payment_method', $angelleye_ppcp_used_payment_method);
                            $subscription->save_meta_data();
                        }
                    }
                }
            }
        } catch (Exception $ex) {
            
        }
    }

}

if (!function_exists('angelleye_ppcp_account_ready_to_paid')) {

    function angelleye_ppcp_account_ready_to_paid($is_sandbox, $client_id, $secret_id, $email) {
        if ($is_sandbox) {
            $paypal_order_api = 'https://api-m.sandbox.paypal.com/v2/checkout/orders/';
        } else {
            $paypal_order_api = 'https://api-m.paypal.com/v2/checkout/orders/';
        }
        $basicAuth = base64_encode($client_id . ":" . $secret_id);
        $data = array(
            'intent' => 'CAPTURE',
            'purchase_units' =>
            array(
                0 =>
                array(
                    'reference_id' => time(),
                    'amount' =>
                    array(
                        'currency_code' => angelleye_ppcp_get_currency(),
                        'value' => '10.00'
                    ),
                    'payee' => array(
                        'email_address' => $email,
                    )
                ),
            ),
            'application_context' => array(
                'user_action' => 'CONTINUE',
                'landing_page' => 'LOGIN',
                'brand_name' => html_entity_decode(get_bloginfo('name'), ENT_NOQUOTES, 'UTF-8')
            ),
            'payment_method' => array(
                'payee_preferred' => 'IMMEDIATE_PAYMENT_REQUIRED'
            )
        );
        $args = array(
            'timeout' => 60,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
            'headers' => array('Content-Type' => 'application/json', "prefer" => "return=representation", 'PayPal-Request-Id' => time()),
            'cookies' => array(),
            'body' => wp_json_encode($data)
        );
        $args['headers']['Authorization'] = "Basic " . $basicAuth;
        $result = wp_remote_post($paypal_order_api, $args);
        $body = wp_remote_retrieve_body($result);
        $response = !empty($body) ? json_decode($body, true) : '';
        if (!empty($response['status']) && 'CREATED' === $response['status']) {
            return true;
        } else {
            return false;
        }
    }

}

if (!function_exists('angelleye_is_vaulting_enable')) {

    function angelleye_is_vaulting_enable($result) {
        if (isset($result['products']) && isset($result['capabilities']) && !empty($result['products']) && !empty($result['products'])) {
            foreach ($result['products'] as $product) {
                if ($product['name'] === 'ADVANCED_VAULTING' &&
                        isset($product['vetting_status']) && $product['vetting_status'] === 'SUBSCRIBED' &&
                        isset($product['capabilities']) && in_array('PAYPAL_WALLET_VAULTING_ADVANCED', $product['capabilities'])) {
                    return true;
                }
            }
        }
        return false;
    }

}

if (!function_exists('angelleye_is_ppcp_third_party_enable')) {

    function angelleye_is_ppcp_third_party_enable($sandbox) {
        if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {
            include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-ppcp-angelleye-settings.php';
        }
        $settings = WC_Gateway_PPCP_AngellEYE_Settings::instance();
        if ($sandbox) {
            $sandbox_client_id = $settings->get('sandbox_client_id', '');
            $sandbox_secret_id = $settings->get('sandbox_api_secret', '');
            $sandbox_merchant_id = $settings->get('sandbox_merchant_id', '');
            if (!empty($sandbox_client_id) && !empty($sandbox_secret_id)) {
                return false;
            } else if (!empty($sandbox_merchant_id)) {
                return true;
            } else {
                return '';
            }
        } else {
            $live_client_id = $settings->get('api_client_id', '');
            $live_secret_id = $settings->get('api_secret', '');
            $live_merchant_id = $settings->get('merchant_id', '');
            if (!empty($live_client_id) && !empty($live_secret_id)) {
                return false;
            } else if (!empty($live_merchant_id)) {
                return true;
            } else {
                return '';
            }
        }
    }

}

if (!function_exists('angelleye_ppcp_display_upgrade_notice_type')) {

    function angelleye_ppcp_display_upgrade_notice_type($result = '') {
        try {
            $paypal_vault_supported_country = angelleye_ppcp_apple_google_vault_supported_country();
            $notice_type = array();
            $notice_type['vault_upgrade'] = false;
            $notice_type['classic_upgrade'] = false;
            $notice_type['outside_us'] = false;
            $is_subscriptions = false;
            $is_us = false;
            $is_classic = false;
            if (isset($result['country']) && !empty($result['country']) && in_array($result['country'], $paypal_vault_supported_country)) {
                $is_us = true;
            } elseif (function_exists('wc_get_base_location')) {
                $default = wc_get_base_location();
                $country = apply_filters('woocommerce_countries_base_country', $default['country']);
                if (in_array($country, $paypal_vault_supported_country)) {
                    $is_us = true;
                }
            }
            if (defined('PPCP_PAYPAL_COUNTRY')) {
                if (in_array(PPCP_PAYPAL_COUNTRY, $paypal_vault_supported_country)) {
                    $is_us = true;
                } else {
                    $is_us = false;
                }
            }
            if (class_exists('WC_Subscriptions_Order')) {
                $is_subscriptions = true;
            }
            $ppcp_gateway_list = ['angelleye_ppcp', 'angelleye_ppcp_apple_pay', 'angelleye_ppcp_google_pay'];
            $active_ppcp_gateways = [];
            $angelleye_classic_gateway_id_list = array('paypal_express', 'paypal_pro', 'paypal_pro_payflow', 'paypal_advanced', 'paypal_credit_card_rest');
            $active_classic_gateway_list = array();
            foreach (WC()->payment_gateways->get_available_payment_gateways() as $gateway) {
                if ('yes' === $gateway->enabled && $gateway->is_available() === true) {
                    if (in_array($gateway->id, $angelleye_classic_gateway_id_list)) {
                        $active_classic_gateway_list[$gateway->id] = $gateway->id;
                    }
                    if (in_array($gateway->id, $ppcp_gateway_list)) {
                        $active_ppcp_gateways[$gateway->id] = $gateway->id;
                    }
                }
            }
            $notice_type['active_ppcp_gateways'] = $active_ppcp_gateways;
            if (count($active_classic_gateway_list) > 0) {
                $is_classic = true;
            }
            if ($is_classic === true && $is_us === false && $is_subscriptions === true) {
                $notice_type['outside_us'] = true;
            } elseif ($is_classic === true && $is_subscriptions === true && $is_us === true) {
                $notice_type['classic_upgrade'] = true;
            } elseif ($is_classic === true && $is_subscriptions === false) {
                $notice_type['classic_upgrade'] = true;
            }
            foreach (WC()->payment_gateways->get_available_payment_gateways() as $gateway) {
                if (in_array($gateway->id, array('angelleye_ppcp')) && 'yes' === $gateway->enabled && $gateway->is_available() === true) {
                    if (empty($result)) {
                        $notice_type['vault_upgrade'] = false;
                    } elseif (angelleye_is_vaulting_enable($result)) {
                        $notice_type['vault_upgrade'] = false;
                    } elseif ($is_us === true && angelleye_is_vaulting_enable($result) === false) {
                        $notice_type['vault_upgrade'] = true;
                    }
                }
                if (in_array($gateway->id, array('angelleye_ppcp')) && 'yes' === $gateway->enabled && $gateway->is_available() === true) {
                    if (empty($result)) {
                        $notice_type['enable_apple_pay'] = false;
                    } elseif (angelleye_is_apple_pay_enable($result)) {
                        $notice_type['enable_apple_pay'] = false;
                    } elseif ($is_us === true && angelleye_is_apple_pay_enable($result) === false) {
                        $notice_type['enable_apple_pay'] = true;
                    }
                }
            }
            return $notice_type;
        } catch (Exception $ex) {
            return $notice_type;
        }
    }

}


if (!function_exists('angelleye_ppcp_display_notice')) {

    function angelleye_ppcp_display_notice($response_data) {
        global $current_user;
        $user_id = $current_user->ID;
        if (get_user_meta($user_id, $response_data->id)) {
            return;
        }
        $message = '<div class="notice notice-warning angelleye-notice" style="display:none;" id="' . $response_data->id . '">'
                . '<div class="angelleye-notice-logo-push"><span> <img width="60px"src="' . $response_data->ans_company_logo . '"> </span></div>'
                . '<div class="angelleye-notice-message">';
        if (!empty($response_data->ans_message_title)) {
            $message .= '<h2>' . $response_data->ans_message_title . '</h2>';
        }
        $message .= '<div class="angelleye-notice-message-inner">'
                . '<p style="margin-top: 15px !important;line-height: 20px;">' . $response_data->ans_message_description . '</p><div class="angelleye-notice-action">';
        if (!empty($response_data->ans_button_url)) {
            $message .= '<a href="' . $response_data->ans_button_url . '" class="button button-primary">' . $response_data->ans_button_label . '</a>';
        }

        if (isset($response_data->is_button_secondary) && $response_data->is_button_secondary === true) {
            $message .= '&nbsp&nbsp&nbsp<a target="_blank" href="' . $response_data->ans_secondary_button_url . '" class="button button-secondary">' . $response_data->ans_secondary_button_label . '</a>';
        }
        $message .= '</div></div>'
                . '</div>';
        if ($response_data->is_dismiss) {
            $message .= '<div class="angelleye-notice-cta">'
                    . '<button class="angelleye-notice-dismiss angelleye-dismiss-welcome" data-msg="' . $response_data->id . '">Dismiss</button>'
                    . '</div>'
                    . '</div>';
        } else {
            $message .= '</div>';
        }
        echo $message;
    }

}


global $change_proceed_checkout_button_text;
$change_proceed_checkout_button_text = get_option('change_proceed_checkout_button_text');
if (!empty($change_proceed_checkout_button_text)) {
    if (!function_exists('woocommerce_button_proceed_to_checkout')) {

        function woocommerce_button_proceed_to_checkout() {
            global $change_proceed_checkout_button_text;
            ?>
            <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="checkout-button button alt wc-forward<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>">
            <?php echo!empty($change_proceed_checkout_button_text) ? apply_filters('angelleye_ppcp_proceed_to_checkout_button', $change_proceed_checkout_button_text) : esc_html_e('Proceed to checkout', 'paypal-for-woocommerce'); ?>
            </a>
            <?php
        }

    }
}

if (!function_exists('angelleye_ppcp_is_subscription_support_enabled')) {

    function angelleye_ppcp_is_subscription_support_enabled() {
        try {
            if (class_exists('WC_Subscriptions') && function_exists('wcs_create_renewal_order')) {
                return true;
            }
            /* $angelleye_classic_gateway_id_list = array('paypal_express', 'paypal_pro', 'paypal_pro_payflow', 'paypal_advanced', 'paypal_credit_card_rest');
              foreach (WC()->payment_gateways->get_available_payment_gateways() as $gateway) {
              if (in_array($gateway->id, $angelleye_classic_gateway_id_list) && 'yes' === $gateway->enabled && $gateway->is_available() === true && ('yes' === $gateway->enable_tokenized_payments || $gateway->enable_tokenized_payments === true)) {
              return true;
              }
              } */
            return false;
        } catch (Exception $ex) {
            return false;
        }
    }

}

if (!function_exists('angelleye_ppcp_get_paypal_details')) {

    function angelleye_ppcp_get_paypal_details($account_details) {
        try {
            $PayPalConfig = array(
                'Sandbox' => isset($account_details['testmode']) ? $account_details['testmode'] : '',
                'APIUsername' => isset($account_details['api_username']) ? $account_details['api_username'] : '',
                'APIPassword' => isset($account_details['api_password']) ? $account_details['api_password'] : '',
                'APISignature' => isset($account_details['api_signature']) ? $account_details['api_signature'] : ''
            );
            if (!class_exists('Angelleye_PayPal_WC')) {
                require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.class.php' );
            }
            $PayPal = new Angelleye_PayPal_WC($PayPalConfig);
            $PayPalResult = $PayPal->GetPalDetails();
            if (isset($PayPalResult['ACK']) && $PayPalResult['ACK'] == 'Success') {
                if (isset($PayPalResult['PAL']) && !empty($PayPalResult['PAL'])) {
                    return $PayPalResult['PAL'];
                }
            }
            return '';
        } catch (Exception $ex) {
            
        }
    }

}

if (!function_exists('angelleye_ppcp_get_classic_paypal_details')) {

    function angelleye_ppcp_get_classic_paypal_details($gateway_id) {
        try {
            foreach (WC()->payment_gateways->get_available_payment_gateways() as $gateway) {
                if ($gateway->id === $gateway_id && 'yes' === $gateway->enabled && $gateway->is_available() === true) {
                    switch ($gateway_id) {
                        case 'paypal_express':
                            $account_details['testmode'] = $gateway->testmode;
                            $account_details['api_username'] = $gateway->api_username;
                            $account_details['api_password'] = $gateway->api_password;
                            $account_details['api_signature'] = $gateway->api_signature;
                            $account_id = angelleye_ppcp_get_paypal_details($account_details);
                            return $account_id;

                        case 'paypal_pro':
                            $account_details['testmode'] = $gateway->testmode;
                            $account_details['api_username'] = $gateway->api_username;
                            $account_details['api_password'] = $gateway->api_password;
                            $account_details['api_signature'] = $gateway->api_signature;
                            $account_id = angelleye_ppcp_get_paypal_details($account_details);
                            return $account_id;

                        /* case 'paypal_credit_card_rest':
                          $gateway->rest_client_id;
                          $gateway->rest_secret_id;
                          break; */
                        default:
                            break;
                    }
                }
            }
        } catch (Exception $ex) {
            
        }
    }

}

if (!function_exists('angelleye_is_apple_pay_enable')) {

    function angelleye_is_apple_pay_enable($result) {
        if (isset($result['products']) && isset($result['capabilities']) && !empty($result['products']) && !empty($result['products'])) {
            foreach ($result['products'] as $key => $product) {
                if (isset($product['vetting_status']) && ('SUBSCRIBED' === $product['vetting_status'] || 'APPROVED' === $product['vetting_status'] ) && isset($product['capabilities']) && is_array($product['capabilities']) && in_array('APPLE_PAY', $product['capabilities'])) {
                    foreach ($result['capabilities'] as $key => $capabilities) {
                        if (isset($capabilities['name']) && 'APPLE_PAY' === $capabilities['name'] && 'ACTIVE' === $capabilities['status']) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

}

if (!function_exists('angelleye_session_expired_exception')) {

    /**
     * Throws session not found exception message
     * @throws Exception
     */
    function angelleye_session_expired_exception($error = '') {
        throw new Exception($error, 302);
    }

}

if (!function_exists('angelleye_ppcp_short_payment_method')) {

    function angelleye_ppcp_short_payment_method(&$array, $keyX, $keyY, $position = 'before') {
        if (array_key_exists($keyX, $array) && array_key_exists($keyY, $array)) {
            $valueY = $array[$keyY];
            unset($array[$keyY]);

            $keys = array_keys($array);
            $indexX = array_search($keyX, $keys, true);

            if ($position === 'before') {
                $array = array_slice($array, 0, $indexX, true) +
                        array($keyY => $valueY) +
                        $array;
            } elseif ($position === 'after') {
                $array = array_slice($array, 0, $indexX + 1, true) +
                        array($keyY => $valueY) +
                        $array;
            }
        }
        return $array;
    }

}

if (!function_exists('is_used_save_payment_token')) {

    function is_used_save_payment_token() {
        $saved_tokens = ['wc-angelleye_ppcp_apple_pay-payment-token', 'wc-angelleye_ppcp-payment-token', 'wc-angelleye_ppcp_cc-payment-token'];
        $is_save_payment_used = false;
        foreach ($saved_tokens as $saved_token) {
            if (!empty($_POST[$saved_token]) && $_POST[$saved_token] !== 'new') {
                return $is_save_payment_used;
            }
        }
        return $is_save_payment_used;
    }

}

if (!function_exists('ae_get_checkout_url')) {

    function ae_get_checkout_url(): string {
        $checkout_page_url = wc_get_checkout_url();
        if (isset($_REQUEST['wfacp_id'])) {
            $checkout_page_url = get_permalink($_REQUEST['wfacp_id']);
        }
        return $checkout_page_url;
    }

}

if (!function_exists('angelleye_ppcp_order_item_meta_key_exists')) {

    function angelleye_ppcp_order_item_meta_key_exists($order, $key) {
        foreach ($order->get_items(array('line_item', 'tax', 'shipping', 'fee', 'coupon')) as $item) {
            if ($item->meta_exists($key)) {
                return true;
            }
        }
        return false;
    }

}

if (!function_exists('angelleye_ppcp_binary_search')) {

    function angelleye_ppcp_binary_search($array, $target) {
        $low = 0;
        $high = count($array) - 1;
        $closest = null;
        while ($low <= $high) {
            $mid = (int) (($low + $high) / 2);
            $amount = (float) $array[$mid];

            if ($amount >= $target) {
                $closest = $array[$mid];
                $high = $mid - 1;
            } else {
                $low = $mid + 1;
            }
        }
        if ($closest === null) {
            $closest = max($array);
        }
        return $closest;
    }

}

if (!function_exists('pfw_print_filters_for')) {

    function pfw_print_filters_for($hook = '') {
        global $wp_filter;
        if (empty($hook) || !isset($wp_filter[$hook]))
            return;

        print '<pre>';
        print_r($wp_filter[$hook]);
        print '</pre>';
    }

}

if (!function_exists('angelleye_ppcp_get_platform_fee_refund_amount')) {

    function angelleye_ppcp_get_platform_fee_refund_amount() {
        return 0.00;
    }

}

if (!function_exists('angelleye_get_matched_shortcode_attributes')) {

    function angelleye_get_matched_shortcode_attributes($tag, $text) {
        preg_match_all('/' . get_shortcode_regex() . '/s', $text, $matches);
        $out = array();
        if (isset($matches[2])) {
            foreach ((array) $matches[2] as $key => $value) {
                if ($tag === $value)
                    $out[] = shortcode_parse_atts($matches[3][$key]);
            }
        }
        return $out;
    }

}

if (!function_exists('angelleye_ppcp_get_awaiting_payment_order_id')) {

    function angelleye_ppcp_get_awaiting_payment_order_id() {
        try {
            $order_id = absint(WC()->session->get('order_awaiting_payment'));
            if (!$order_id) {
                $order_id = absint(wc()->session->get('store_api_draft_order', 0));
            }
            if ($order_id) {
                $order = wc_get_order($order_id);
                if ($order && in_array($order->get_status(), array('pending', 'failed', 'checkout-draft'))) {
                    return $order_id;
                }
            }
            return 0;
        } catch (Exception $ex) {
            
        }
    }

}

if (!function_exists('angelleye_ppcp_is_cart_contains_free_trial')) {

    function angelleye_ppcp_is_cart_contains_free_trial() {
        global $product;
        if (!class_exists('WC_Subscriptions_Product')) {
            return false;
        }
        if (is_product()) {
            if (WC_Subscriptions_Product::get_trial_length($product) > 0) {
                return true;
            }
        }
        $cart_contains_free_trial = false;
        if (angelleye_ppcp_is_cart_contains_subscription()) {
            foreach (WC()->cart->cart_contents as $cart_item) {
                if (WC_Subscriptions_Product::get_trial_length($cart_item['data']) > 0) {
                    $cart_contains_free_trial = true;
                    break;
                }
            }
        }
        return $cart_contains_free_trial;
    }

}

if (!function_exists('angelleye_ppcp_apple_google_vault_supported_country')) {

    function angelleye_ppcp_apple_google_vault_supported_country() {
        return array(
            'AU', 'AT', 'BE', 'BG', 'CA', 'CY', 'CZ', 'DK', 'EE', 'FI',
            'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LI', 'LT', 'LU',
            'MT', 'NL', 'NO', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
            'GB', 'US'
        );
    }

}

