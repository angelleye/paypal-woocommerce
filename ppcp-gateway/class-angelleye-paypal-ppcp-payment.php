<?php

defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Payment {

    public $is_sandbox;
    protected static $_instance = null;
    public $api_request;
    public $api_response;
    public $api_log;
    public $checkout_details;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->angelleye_ppcp_load_class();
        $this->is_sandbox = 'yes' === $this->settings->get('testmode', 'no');
        if ($this->is_sandbox) {
            $this->token_url = 'https://api-m.sandbox.paypal.com/v1/oauth2/token';
            $this->order_url = 'https://api-m.sandbox.paypal.com/v2/checkout/orders/';
            $this->paypal_order_api = 'https://api-m.sandbox.paypal.com/v2/checkout/orders/';
            $this->paypal_refund_api = 'https://api-m.sandbox.paypal.com/v2/payments/captures/';
            $this->auth = 'https://api-m.sandbox.paypal.com/v2/payments/authorizations/';
            $this->generate_token_url = 'https://api-m.sandbox.paypal.com/v1/identity/generate-token';
            $this->merchant_id = $this->settings->get('sandbox_merchant_id', '');
            $this->partner_client_id = PAYPAL_PPCP_SNADBOX_PARTNER_CLIENT_ID;
        } else {
            $this->token_url = 'https://api-m.paypal.com/v1/oauth2/token';
            $this->order_url = 'https://api-m.paypal.com/v2/checkout/orders/';
            $this->paypal_order_api = 'https://api-m.paypal.com/v2/checkout/orders/';
            $this->paypal_refund_api = 'https://api-m.paypal.com/v2/payments/captures/';
            $this->auth = 'https://api-m.paypal.com/v2/payments/authorizations/';
            $this->generate_token_url = 'https://api-m.paypal.com/v1/identity/generate-token';
            $this->merchant_id = $this->settings->get('live_merchant_id', '');
            $this->partner_client_id = PAYPAL_PPCP_PARTNER_CLIENT_ID;
        }
        $this->title = $this->settings->get('title', 'PayPal Complete Payments');
        $this->brand_name = $this->settings->get('brand_name', get_bloginfo('name'));
        $this->paymentaction = $this->settings->get('paymentaction', 'capture');
        $this->landing_page = $this->settings->get('landing_page', 'NO_PREFERENCE');
        $this->payee_preferred = 'yes' === $this->settings->get('payee_preferred', 'no');
        $this->invoice_prefix = $this->settings->get('invoice_prefix', 'WC-PPCP');
        $this->soft_descriptor = $this->settings->get('soft_descriptor', '');
        $this->advanced_card_payments = 'yes' === $this->settings->get('enable_advanced_card_payments', 'no');
        $this->checkout_disable_smart_button = 'yes' === $this->settings->get('checkout_disable_smart_button', 'no');
        $this->error_email_notification = 'yes' === $this->settings->get('error_email_notification', 'yes');
        $this->enable_paypal_checkout_page = 'yes' === $this->settings->get('enable_paypal_checkout_page', 'yes');
        $this->send_items = 'yes' === $this->settings->get('send_items', 'yes');
        $this->AVSCodes = array("A" => "Address Matches Only (No ZIP)",
            "B" => "Address Matches Only (No ZIP)",
            "C" => "This tranaction was declined.",
            "D" => "Address and Postal Code Match",
            "E" => "This transaction was declined.",
            "F" => "Address and Postal Code Match",
            "G" => "Global Unavailable - N/A",
            "I" => "International Unavailable - N/A",
            "N" => "None - Transaction was declined.",
            "P" => "Postal Code Match Only (No Address)",
            "R" => "Retry - N/A",
            "S" => "Service not supported - N/A",
            "U" => "Unavailable - N/A",
            "W" => "Nine-Digit ZIP Code Match (No Address)",
            "X" => "Exact Match - Address and Nine-Digit ZIP",
            "Y" => "Address and five-digit Zip match",
            "Z" => "Five-Digit ZIP Matches (No Address)");

        $this->CVV2Codes = array(
            "E" => "N/A",
            "M" => "Match",
            "N" => "No Match",
            "P" => "Not Processed - N/A",
            "S" => "Service Not Supported - N/A",
            "U" => "Service Unavailable - N/A",
            "X" => "No Response - N/A"
        );
    }

    public function angelleye_ppcp_load_class() {
        try {
            if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-ppcp-angelleye-settings.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Request')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-request.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Log')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-log.php';
            }
            $this->api_log = AngellEYE_PayPal_PPCP_Log::instance();
            $this->settings = WC_Gateway_PPCP_AngellEYE_Settings::instance();
            $this->api_request = AngellEYE_PayPal_PPCP_Request::instance();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_create_order_request($woo_order_id = null) {
        try {
            $this->paymentaction = apply_filters('angelleye_ppcp_paymentaction', $this->paymentaction, $woo_order_id);
            if ($woo_order_id == null) {
                $cart = $this->angelleye_ppcp_get_details_from_cart();
            } else {
                $cart = $this->angelleye_ppcp_get_details_from_order($woo_order_id);
            }
            $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
            $reference_id = wc_generate_order_key();
            angelleye_ppcp_set_session('angelleye_ppcp_reference_id', $reference_id);
            if (!empty($_POST['angelleye_ppcp_payment_method_title'])) {
                $payment_method_title = angelleye_ppcp_get_payment_method_title(wc_clean($_POST['angelleye_ppcp_payment_method_title']));
                angelleye_ppcp_set_session('angelleye_ppcp_payment_method_title', $payment_method_title);
            }
            $intent = ($this->paymentaction === 'capture') ? 'CAPTURE' : 'AUTHORIZE';
            $body_request = array(
                'intent' => $intent,
                'application_context' => $this->angelleye_ppcp_application_context(),
                'payment_method' => array('payee_preferred' => ($this->payee_preferred) ? 'IMMEDIATE_PAYMENT_REQUIRED' : 'UNRESTRICTED'),
                'purchase_units' =>
                array(
                    0 =>
                    array(
                        'reference_id' => $reference_id,
                        'amount' =>
                        array(
                            'currency_code' => angelleye_ppcp_get_currency($woo_order_id),
                            'value' => $cart['order_total'],
                            'breakdown' => array()
                        )
                    ),
                ),
            );
            if ($woo_order_id != null) {
                $order = wc_get_order($woo_order_id);
                $body_request['purchase_units'][0]['invoice_id'] = $this->invoice_prefix . str_replace("#", "", $order->get_order_number());
                $body_request['purchase_units'][0]['custom_id'] = apply_filters('angelleye_ppcp_custom_id', $this->invoice_prefix . str_replace("#", "", $order->get_order_number()), $order);
            } else {
                $body_request['purchase_units'][0]['invoice_id'] = $reference_id;
                $body_request['purchase_units'][0]['custom_id'] = apply_filters('angelleye_ppcp_custom_id', $reference_id, '');
            }
            $body_request['purchase_units'][0]['soft_descriptor'] = $this->soft_descriptor;
            $body_request['purchase_units'][0]['payee']['merchant_id'] = $this->merchant_id;
            if ($this->send_items === true) {
                if (isset($cart['total_item_amount']) && $cart['total_item_amount'] > 0) {
                    $body_request['purchase_units'][0]['amount']['breakdown']['item_total'] = array(
                        'currency_code' => angelleye_ppcp_get_currency($woo_order_id),
                        'value' => $cart['total_item_amount'],
                    );
                }
                if (isset($cart['shipping']) && $cart['shipping'] > 0) {
                    $body_request['purchase_units'][0]['amount']['breakdown']['shipping'] = array(
                        'currency_code' => angelleye_ppcp_get_currency($woo_order_id),
                        'value' => $cart['shipping'],
                    );
                }
                if (isset($cart['ship_discount_amount']) && $cart['ship_discount_amount'] > 0) {
                    $body_request['purchase_units'][0]['amount']['breakdown']['shipping_discount'] = array(
                        'currency_code' => angelleye_ppcp_get_currency($woo_order_id),
                        'value' => angelleye_ppcp_round($cart['ship_discount_amount'], $decimals),
                    );
                }
                if (isset($cart['order_tax']) && $cart['order_tax'] > 0) {
                    $body_request['purchase_units'][0]['amount']['breakdown']['tax_total'] = array(
                        'currency_code' => angelleye_ppcp_get_currency($woo_order_id),
                        'value' => $cart['order_tax'],
                    );
                }
                if (isset($cart['discount']) && $cart['discount'] > 0) {
                    $body_request['purchase_units'][0]['amount']['breakdown']['discount'] = array(
                        'currency_code' => angelleye_ppcp_get_currency($woo_order_id),
                        'value' => $cart['discount'],
                    );
                }

                if (isset($cart['items']) && !empty($cart['items'])) {
                    foreach ($cart['items'] as $key => $order_items) {
                        $description = !empty($order_items['description']) ? strip_shortcodes($order_items['description']) : '';
                        $product_name = !empty($order_items['name']) ? $order_items['name'] : '';
                        $body_request['purchase_units'][0]['items'][$key] = array(
                            'name' => $product_name,
                            'description' => html_entity_decode($description, ENT_NOQUOTES, 'UTF-8'),
                            'sku' => $order_items['sku'],
                            'category' => $order_items['category'],
                            'quantity' => $order_items['quantity'],
                            'unit_amount' =>
                            array(
                                'currency_code' => angelleye_ppcp_get_currency($woo_order_id),
                                'value' => $order_items['amount'],
                            ),
                        );
                    }
                }
            }
            if ($woo_order_id != null) {
                $order = wc_get_order($woo_order_id);
                $old_wc = version_compare(WC_VERSION, '3.0', '<');
                if (( $old_wc && ( $order->shipping_address_1 || $order->shipping_address_2 ) ) || (!$old_wc && $order->has_shipping_address() )) {
                    $shipping_first_name = $old_wc ? $order->shipping_first_name : $order->get_shipping_first_name();
                    $shipping_last_name = $old_wc ? $order->shipping_last_name : $order->get_shipping_last_name();
                    $shipping_address_1 = $old_wc ? $order->shipping_address_1 : $order->get_shipping_address_1();
                    $shipping_address_2 = $old_wc ? $order->shipping_address_2 : $order->get_shipping_address_2();
                    $shipping_city = $old_wc ? $order->shipping_city : $order->get_shipping_city();
                    $shipping_state = $old_wc ? $order->shipping_state : $order->get_shipping_state();
                    $shipping_postcode = $old_wc ? $order->shipping_postcode : $order->get_shipping_postcode();
                    $shipping_country = $old_wc ? $order->shipping_country : $order->get_shipping_country();
                } else {
                    $shipping_first_name = $old_wc ? $order->billing_first_name : $order->get_billing_first_name();
                    $shipping_last_name = $old_wc ? $order->billing_last_name : $order->get_billing_last_name();
                    $shipping_address_1 = $old_wc ? $order->billing_address_1 : $order->get_billing_address_1();
                    $shipping_address_2 = $old_wc ? $order->billing_address_2 : $order->get_billing_address_2();
                    $shipping_city = $old_wc ? $order->billing_city : $order->get_billing_city();
                    $shipping_state = $old_wc ? $order->billing_state : $order->get_billing_state();
                    $shipping_postcode = $old_wc ? $order->billing_postcode : $order->get_billing_postcode();
                    $shipping_country = $old_wc ? $order->billing_country : $order->get_billing_country();
                }
                if ($order->needs_shipping_address() || WC()->cart->needs_shipping_address()) {
                    if (!empty($shipping_first_name) && !empty($shipping_last_name)) {
                        $body_request['purchase_units'][0]['shipping']['name']['full_name'] = $shipping_first_name . ' ' . $shipping_last_name;
                    }
                    angelleye_ppcp_set_session('angelleye_ppcp_is_shipping_added', 'yes');
                    $body_request['purchase_units'][0]['shipping']['address'] = array(
                        'address_line_1' => $shipping_address_1,
                        'address_line_2' => $shipping_address_2,
                        'admin_area_2' => $shipping_city,
                        'admin_area_1' => $shipping_state,
                        'postal_code' => $shipping_postcode,
                        'country_code' => $shipping_country,
                    );
                }
            } else {
                if (true === WC()->cart->needs_shipping_address()) {
                    if (is_user_logged_in()) {
                        if (!empty($cart['shipping_address']['first_name']) && !empty($cart['shipping_address']['last_name'])) {
                            $body_request['purchase_units'][0]['shipping']['name']['full_name'] = $cart['shipping_address']['first_name'] . ' ' . $cart['shipping_address']['last_name'];
                        }
                        if (!empty($cart['shipping_address']['address_1']) && !empty($cart['shipping_address']['city']) && !empty($cart['shipping_address']['state']) && !empty($cart['shipping_address']['postcode']) && !empty($cart['shipping_address']['country'])) {
                            $body_request['purchase_units'][0]['shipping']['address'] = array(
                                'address_line_1' => $cart['shipping_address']['address_1'],
                                'address_line_2' => $cart['shipping_address']['address_2'],
                                'admin_area_2' => $cart['shipping_address']['city'],
                                'admin_area_1' => $cart['shipping_address']['state'],
                                'postal_code' => $cart['shipping_address']['postcode'],
                                'country_code' => $cart['shipping_address']['country'],
                            );
                            angelleye_ppcp_set_session('angelleye_ppcp_is_shipping_added', 'yes');
                        }
                    }
                }
            }
            $body_request = $this->angelleye_ppcp_set_payer_details($woo_order_id, $body_request);
            $body_request = angelleye_ppcp_remove_empty_key($body_request);
            $args = array(
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'body' => $body_request
            );
            $this->api_response = $this->api_request->request($this->paypal_order_api, $args, 'create_order');
            if (ob_get_length()) {
                ob_end_clean();
            }
            if (!empty($this->api_response['status'])) {
                $return_response['orderID'] = $this->api_response['id'];
                if (!empty(isset($woo_order_id) && !empty($woo_order_id))) {
                    angelleye_ppcp_update_post_meta($order, '_paypal_order_id', $this->api_response['id']);
                }
                wp_send_json($return_response, 200);
                exit();
            } else {
                $error_email_notification_param = array(
                    'request' => 'create_order',
                    'order_id' => $woo_order_id
                );
                $error_message = $this->angelleye_ppcp_get_readable_message($this->api_response, $error_email_notification_param);
                wc_add_notice($error_message, 'error');
                wp_send_json_error($error_message);
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_get_details_from_cart() {
        try {
            $old_wc = version_compare(WC_VERSION, '3.0', '<');
            $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
            $rounded_total = $this->angelleye_ppcp_get_rounded_total_in_cart();
            $discounts = WC()->cart->get_cart_discount_total();
            $details = array(
                'total_item_amount' => angelleye_ppcp_round(WC()->cart->cart_contents_total + $discounts, $decimals),
                'order_tax' => angelleye_ppcp_round(WC()->cart->tax_total + WC()->cart->shipping_tax_total, $decimals),
                'shipping' => angelleye_ppcp_round(WC()->cart->shipping_total, $decimals),
                'items' => $this->angelleye_ppcp_get_paypal_line_items_from_cart(),
                'shipping_address' => $this->angelleye_ppcp_get_address_from_customer(),
                'email' => $old_wc ? WC()->customer->billing_email : WC()->customer->get_billing_email(),
            );
            return $this->angelleye_ppcp_get_details($details, $discounts, $rounded_total, WC()->cart->total);
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_get_number_of_decimal_digits() {
        try {
            return $this->angelleye_ppcp_is_currency_supports_zero_decimal() ? 0 : 2;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_is_currency_supports_zero_decimal() {
        try {
            return in_array(get_woocommerce_currency(), array('HUF', 'JPY', 'TWD'));
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_get_rounded_total_in_cart() {
        try {
            $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
            $rounded_total = 0;
            foreach (WC()->cart->cart_contents as $cart_item_key => $values) {
                $amount = angelleye_ppcp_round($values['line_subtotal'] / $values['quantity'], $decimals);
                $rounded_total += angelleye_ppcp_round($amount * $values['quantity'], $decimals);
            }
            return $rounded_total;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_get_paypal_line_items_from_cart() {
        try {
            $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
            $items = array();
            foreach (WC()->cart->cart_contents as $cart_item_key => $values) {
                $desc = '';
                $amount = angelleye_ppcp_round($values['line_subtotal'] / $values['quantity'], $decimals);
                if (version_compare(WC_VERSION, '3.0', '<')) {
                    $product = $values['data'];
                    $name = $values['data']->post->post_title;
                    $sku = '';
                    $category = '';
                } else {
                    $product = $values['data'];
                    $name = $product->get_name();
                    $sku = $product->get_sku();
                    $category = $product->needs_shipping() ? 'PHYSICAL_GOODS' : 'DIGITAL_GOODS';
                }
                if (is_object($product)) {
                    if ($product->is_type('variation')) {
                        if (!empty($values['variation']) && is_array($values['variation'])) {
                            foreach ($values['variation'] as $key => $value) {
                                $key = str_replace(array('attribute_pa_', 'attribute_', 'Pa_', 'pa_'), '', $key);
                                $desc .= ' ' . ucwords($key) . ': ' . $value;
                            }
                            $desc = trim($desc);
                        }
                    }
                }
                if (!empty($values['addons'])) {
                    foreach ($values['addons'] as $key => $value) {
                        if (!empty($value['name'])) {
                            $desc .= ' ' . ucwords($value['name']);
                        }
                        if (!empty($value['price'])) {
                            $desc .= ' (' . strip_tags(wc_price($value['price'], array('currency' => get_woocommerce_currency()))) . ')';
                        }
                        if (!empty($value['value'])) {
                            $desc .= ': ' . $value['value'];
                        }
                    }
                }
                $product_name = !empty($name) ? $name : '';
                $product_name = apply_filters('angelleye_ppcp_product_name', $product_name, $product, $desc, $values);
                $product_name = wp_strip_all_tags($product_name);
                if (strlen($product_name) > 127) {
                    $product_name = substr($product_name, 0, 124) . '...';
                }
                if (empty($desc)) {
                    $desc = $product_name;
                }

                $desc = !empty($desc) ? $desc : '';
                if (strlen($desc) > 127) {
                    $desc = substr($desc, 0, 124) . '...';
                }
                
                $desc = strip_shortcodes($desc);

                $item = array(
                    'name' => $product_name,
                    'description' => apply_filters('angelleye_ppcp_product_description', $desc),
                    'sku' => $sku,
                    'category' => $category,
                    'quantity' => $values['quantity'],
                    'amount' => $amount,
                );
                $items[] = $item;
            }
            return $items;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_get_address_from_customer() {
        try {
            $customer = WC()->customer;
            $old_wc = version_compare(WC_VERSION, '3.0', '<');
            if ($customer->get_shipping_address() || $customer->get_shipping_address_2()) {
                $shipping_first_name = $old_wc ? $customer->shipping_first_name : $customer->get_shipping_first_name();
                $shipping_last_name = $old_wc ? $customer->shipping_last_name : $customer->get_shipping_last_name();
                $shipping_address_1 = $customer->get_shipping_address();
                $shipping_address_2 = $customer->get_shipping_address_2();
                $shipping_city = $customer->get_shipping_city();
                $shipping_state = $customer->get_shipping_state();
                $shipping_postcode = $customer->get_shipping_postcode();
                $shipping_country = $customer->get_shipping_country();
                return array(
                    'first_name' => $shipping_first_name,
                    'last_name' => $shipping_last_name,
                    'company' => '',
                    'address_1' => $shipping_address_1,
                    'address_2' => $shipping_address_2,
                    'city' => $shipping_city,
                    'state' => $shipping_state,
                    'postcode' => $shipping_postcode,
                    'country' => $shipping_country,
                    'phone' => $old_wc ? $customer->billing_phone : $customer->get_billing_phone(),
                );
            } else {
                return array();
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_get_details($details, $discounts, $rounded_total, $total) {
        try {
            $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
            $discounts = angelleye_ppcp_round($discounts, $decimals);
            $details['order_total'] = angelleye_ppcp_round(
                    $details['total_item_amount'] + $details['order_tax'] + $details['shipping'] - $discounts, $decimals
            );
            $diff = 0;
            if ($details['total_item_amount'] != $rounded_total) {
                $diff = round($details['total_item_amount'] + $discounts - $rounded_total, $decimals);
                if (abs($diff) > 0.000001 && 0.0 !== (float) $diff) {
                    $extra_line_item = $this->angelleye_ppcp_get_extra_offset_line_item($diff);
                    $details['items'][] = $extra_line_item;
                    $details['total_item_amount'] += $extra_line_item['amount'];
                    $details['total_item_amount'] = angelleye_ppcp_round($details['total_item_amount'], $decimals);
                    $details['order_total'] += $extra_line_item['amount'];
                    $details['order_total'] = angelleye_ppcp_round($details['order_total'], $decimals);
                }
            }
            if (0 == $details['total_item_amount']) {
                unset($details['items']);
            }
            if ($details['total_item_amount'] != $rounded_total) {
                unset($details['items']);
            }
            if ($details['total_item_amount'] == $discounts) {
                unset($details['items']);
            } else if ($discounts > 0 && $discounts < $details['total_item_amount'] && !empty($details['items'])) {
                $details['discount'] = $discounts;
            }
            $details['discount'] = $discounts;
            $details['ship_discount_amount'] = 0;
            $wc_order_total = angelleye_ppcp_round($total, $decimals);
            $discounted_total = angelleye_ppcp_round($details['order_total'], $decimals);
            if ($wc_order_total != $discounted_total) {
                if ($discounted_total < $wc_order_total) {
                    $details['order_tax'] += $wc_order_total - $discounted_total;
                    $details['order_tax'] = angelleye_ppcp_round($details['order_tax'], $decimals);
                } else {
                    $details['ship_discount_amount'] += $wc_order_total - $discounted_total;
                    $details['ship_discount_amount'] = angelleye_ppcp_round($details['ship_discount_amount'], $decimals);
                    $details['ship_discount_amount'] = abs($details['ship_discount_amount']);
                }
                $details['order_total'] = $wc_order_total;
            }
            if (!is_numeric($details['shipping'])) {
                $details['shipping'] = 0;
            }
            $lisum = 0;
            if (!empty($details['items'])) {
                foreach ($details['items'] as $li => $values) {
                    $lisum += $values['quantity'] * $values['amount'];
                }
            }
            if (abs($lisum) > 0.000001 && 0.0 !== (float) $diff) {
                $details['items'][] = $this->angelleye_ppcp_get_extra_offset_line_item($details['total_item_amount'] - $lisum);
            }
            return $details;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_get_extra_offset_line_item($amount) {
        try {
            $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
            return array(
                'name' => 'Line Item Amount Offset',
                'description' => 'Adjust cart calculation discrepancy',
                'quantity' => 1,
                'amount' => angelleye_ppcp_round($amount, $decimals),
            );
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_application_context() {
        $smart_button = AngellEYE_PayPal_PPCP_Smart_Button::instance();
        $application_context = array(
            'brand_name' => $this->brand_name,
            'locale' => 'en-US',
            'landing_page' => $this->landing_page,
            'shipping_preference' => $this->angelleye_ppcp_shipping_preference(),
            'user_action' => $smart_button->angelleye_ppcp_is_skip_final_review() ? 'PAY_NOW' : 'CONTINUE',
            'return_url' => '',
            'cancel_url' => ''
        );
        if ($this->checkout_disable_smart_button === true && $this->advanced_card_payments === false) {
            $application_context['return_url'] = add_query_arg(array('angelleye_ppcp_action' => 'regular_capture', 'utm_nooverride' => '1'), untrailingslashit(WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action')));
            $application_context['cancel_url'] = add_query_arg(array('angelleye_ppcp_action' => 'regular_cancel', 'utm_nooverride' => '1'), untrailingslashit(WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action')));
        }

        return $application_context;
    }

    public function angelleye_ppcp_shipping_preference() {
        $shipping_preference = 'GET_FROM_FILE';
        $page = null;
        if (is_cart() && !WC()->cart->is_empty()) {
            $page = 'cart';
        } elseif (is_checkout() || is_checkout_pay_page()) {
            $page = 'checkout';
        } elseif (is_product()) {
            $page = 'product';
        } elseif (isset($_GET) && !empty($_GET['from'])) {
            $page = $_GET['from'];
        }
        if ($page === null) {
            return $shipping_preference = WC()->cart->needs_shipping() ? 'GET_FROM_FILE' : 'NO_SHIPPING';
        }
        switch ($page) {
            case 'product':
                $shipping_preference = WC()->cart->needs_shipping() ? 'GET_FROM_FILE' : 'NO_SHIPPING';
            case 'cart':
                $shipping_preference = WC()->cart->needs_shipping() ? 'GET_FROM_FILE' : 'NO_SHIPPING';
                break;
            case 'checkout':
                $shipping_preference = WC()->cart->needs_shipping() ? 'SET_PROVIDED_ADDRESS' : 'NO_SHIPPING';
                break;
            case 'pay_page' :
                $shipping_preference = WC()->cart->needs_shipping() ? 'SET_PROVIDED_ADDRESS' : 'NO_SHIPPING';
                break;
        }
        return $shipping_preference;
    }

    public function angelleye_ppcp_set_payer_details($woo_order_id, $body_request) {
        if ($woo_order_id != null) {
            $order = wc_get_order($woo_order_id);
            $old_wc = version_compare(WC_VERSION, '3.0', '<');
            $first_name = $old_wc ? $order->billing_first_name : $order->get_billing_first_name();
            $last_name = $old_wc ? $order->billing_last_name : $order->get_billing_last_name();
            $billing_email = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_email : $order->get_billing_email();
            $billing_phone = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_phone : $order->get_billing_phone();
            if (!empty($billing_email)) {
                $body_request['payer']['email_address'] = $billing_email;
            }
            if (!empty($billing_phone)) {
                $billing_phone = preg_replace('/[^0-9]/', '', $billing_phone);
                if (strlen($billing_phone) > 15) {
                    $billing_phone = preg_replace('/^0+/', '', $billing_phone);
                } elseif (strlen($billing_phone) > 14) {
                    $billing_phone = preg_replace('/^0/', '', $billing_phone);
                }
                $billing_phone = substr($billing_phone, 0, 14);
                if (!empty($billing_phone)) {
                    $body_request['payer']['phone']['phone_type'] = 'HOME';
                    $body_request['payer']['phone']['phone_number']['national_number'] = $billing_phone;
                }
            }
            if (!empty($first_name)) {
                $body_request['payer']['name']['given_name'] = $first_name;
            }
            if (!empty($last_name)) {
                $body_request['payer']['name']['surname'] = $last_name;
            }
            $address_1 = $old_wc ? $order->billing_address_1 : $order->get_billing_address_1();
            $address_2 = $old_wc ? $order->billing_address_2 : $order->get_billing_address_2();
            $city = $old_wc ? $order->billing_city : $order->get_billing_city();
            $state = $old_wc ? $order->billing_state : $order->get_billing_state();
            $postcode = $old_wc ? $order->billing_postcode : $order->get_billing_postcode();
            $country = $old_wc ? $order->billing_country : $order->get_billing_country();
            if (!empty($address_1) && !empty($city) && !empty($state) && !empty($postcode) && !empty($country)) {
                $body_request['payer']['address'] = array(
                    'address_line_1' => $address_1,
                    'address_line_2' => $address_2,
                    'admin_area_2' => $city,
                    'admin_area_1' => $state,
                    'postal_code' => $postcode,
                    'country_code' => $country,
                );
            }
        } else {
            if (is_user_logged_in()) {
                $customer = WC()->customer;
                $old_wc = version_compare(WC_VERSION, '3.0', '<');
                $first_name = $old_wc ? $customer->billing_first_name : $customer->get_billing_first_name();
                $last_name = $old_wc ? $customer->billing_last_name : $customer->get_billing_last_name();
                $address_1 = $old_wc ? $customer->get_address() : $customer->get_billing_address_1();
                $address_2 = $old_wc ? $customer->get_address_2() : $customer->get_billing_address_2();
                $city = $old_wc ? $customer->get_city() : $customer->get_billing_city();
                $state = $old_wc ? $customer->get_state() : $customer->get_billing_state();
                $postcode = $old_wc ? $customer->get_postcode() : $customer->get_billing_postcode();
                $country = $old_wc ? $customer->get_country() : $customer->get_billing_country();
                $email_address = $old_wc ? WC()->customer->billing_email : WC()->customer->get_billing_email();
                $billing_phone = $old_wc ? $customer->billing_phone : $customer->get_billing_phone();
                if (!empty($first_name)) {
                    $body_request['payer']['name']['given_name'] = $first_name;
                }
                if (!empty($last_name)) {
                    $body_request['payer']['name']['surname'] = $last_name;
                }
                if (!empty($email_address)) {
                    $body_request['payer']['email_address'] = $email_address;
                }
                if (!empty($billing_phone)) {
                    $billing_phone = preg_replace('/[^0-9]/', '', $billing_phone);
                    if (strlen($billing_phone) > 15) {
                        $billing_phone = preg_replace('/^0+/', '', $billing_phone);
                    } elseif (strlen($billing_phone) > 14) {
                        $billing_phone = preg_replace('/^0/', '', $billing_phone);
                    }
                    $billing_phone = substr($billing_phone, 0, 14);
                    if (!empty($billing_phone)) {
                        $body_request['payer']['phone']['phone_type'] = 'HOME';
                        $body_request['payer']['phone']['phone_number']['national_number'] = $billing_phone;
                    }
                }
                if (!empty($address_1) && !empty($city) && !empty($state) && !empty($postcode) && !empty($country)) {
                    $body_request['payer']['address'] = array(
                        'address_line_1' => $address_1,
                        'address_line_2' => $address_2,
                        'admin_area_2' => $city,
                        'admin_area_1' => $state,
                        'postal_code' => $postcode,
                        'country_code' => $country,
                    );
                }
            }
        }
        return $body_request;
    }

    public function generate_request_id() {
        static $pid = -1;
        static $addr = -1;

        if ($pid == -1) {
            $pid = uniqid('angelleye-pfw', true);
        }

        if ($addr == -1) {
            if (array_key_exists('SERVER_ADDR', $_SERVER)) {
                $addr = ip2long($_SERVER['SERVER_ADDR']);
            } else {
                $addr = php_uname('n');
            }
        }

        return $addr . $pid . $_SERVER['REQUEST_TIME'] . mt_rand(0, 0xffff);
    }

    public function angelleye_ppcp_get_readable_message($error, $error_email_notification_param = array()) {
        $message = '';
        if (isset($error['name'])) {
            switch ($error['name']) {
                case 'VALIDATION_ERROR':
                    foreach ($error['details'] as $e) {
                        $message .= "\t" . $e['field'] . "\n\t" . $e['issue'] . "\n\n";
                    }
                    break;
                case 'INVALID_REQUEST':
                    foreach ($error['details'] as $e) {
                        if (isset($e['field']) && isset($e['description'])) {
                            $message .= "\t" . $e['field'] . "\n\t" . $e['description'] . "\n\n";
                        } elseif (isset($e['issue'])) {
                            $message .= "\t" . $e['issue'] . "n\n";
                        }
                    }
                    break;
                case 'BUSINESS_ERROR':
                    $message .= $error['message'];
                    break;
                case 'UNPROCESSABLE_ENTITY' :
                    foreach ($error['details'] as $e) {
                        $message .= "\t" . $e['issue'] . ": " . $e['description'] . "\n\n";
                    }
                    break;
            }
        }
        if (!empty($message)) {
            
        } else if (!empty($error['message'])) {
            $message = $error['message'];
        } else if (!empty($error['error_description'])) {
            $message = $error['error_description'];
        } else {
            $message = $error;
        }
        if ($this->error_email_notification) {
            $this->angelleye_ppcp_error_email_notification($error_email_notification_param, $message);
        }
        return $message;
    }

    public function angelleye_ppcp_get_checkout_details($paypal_order_id) {
        try {
            $args = array(
                'timeout' => 60,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking' => true,
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                //'body' => array(),
                'cookies' => array()
            );
            $this->api_response = $this->api_request->request($this->paypal_order_api . $paypal_order_id, $args, 'get_order');
            $this->api_response = json_decode(json_encode($this->api_response), FALSE);
            angelleye_ppcp_set_session('angelleye_ppcp_paypal_order_id', $paypal_order_id);
            angelleye_ppcp_set_session('angelleye_ppcp_paypal_transaction_details', $this->api_response);
            return $this->api_response;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_order_capture_request($woo_order_id, $need_to_update_order = true) {
        try {
            $order = wc_get_order($woo_order_id);
            if ($need_to_update_order) {
                $this->angelleye_ppcp_update_order($order);
            }
            $paypal_order_id = angelleye_ppcp_get_session('angelleye_ppcp_paypal_order_id');
            $args = array(
                'method' => 'POST',
                'timeout' => 60,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking' => true,
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
            );
            $this->api_response = $this->api_request->request($this->paypal_order_api . $paypal_order_id . '/capture', $args, 'capture_order');
            $angelleye_ppcp_payment_method_title = angelleye_ppcp_get_session('angelleye_ppcp_payment_method_title');
            if (!empty($angelleye_ppcp_payment_method_title)) {
                update_post_meta($woo_order_id, '_payment_method_title', $angelleye_ppcp_payment_method_title);
                update_post_meta($woo_order_id, 'payment_method_title', $angelleye_ppcp_payment_method_title);
            }
            if (isset($this->api_response['id']) && !empty($this->api_response['id'])) {
                $return_response['paypal_order_id'] = $this->api_response['id'];
                angelleye_ppcp_update_post_meta($order, '_paypal_order_id', $this->api_response['id']);
                $angelleye_ppcp_payment_method_title = angelleye_ppcp_get_session('angelleye_ppcp_payment_method_title');
                if (!empty($angelleye_ppcp_payment_method_title)) {
                    update_post_meta($woo_order_id, '_payment_method_title', $angelleye_ppcp_payment_method_title);
                    update_post_meta($woo_order_id, 'payment_method_title', $angelleye_ppcp_payment_method_title);
                }
                if ($this->api_response['status'] == 'COMPLETED') {
                    $payment_source = isset($this->api_response['payment_source']) ? $this->api_response['payment_source'] : '';
                    if (!empty($payment_source['card'])) {
                        $card_response_order_note = __('Card Details', 'paypal-for-woocommerce');
                        $card_response_order_note .= "\n";
                        $card_response_order_note .= 'Last digits : ' . $payment_source['card']['last_digits'];
                        $card_response_order_note .= "\n";
                        $card_response_order_note .= 'Brand : ' . angelleye_ppcp_readable($payment_source['card']['brand']);
                        $card_response_order_note .= "\n";
                        $card_response_order_note .= 'Card type : ' . angelleye_ppcp_readable($payment_source['card']['type']);
                        $order->add_order_note($card_response_order_note);
                    }
                    $processor_response = isset($this->api_response['purchase_units']['0']['payments']['captures']['0']['processor_response']) ? $this->api_response['purchase_units']['0']['payments']['captures']['0']['processor_response'] : '';
                    if (!empty($processor_response['avs_code'])) {
                        $avs_response_order_note = __('Address Verification Result', 'paypal-for-woocommerce');
                        $avs_response_order_note .= "\n";
                        $avs_response_order_note .= $processor_response['avs_code'];
                        if (isset($this->AVSCodes[$processor_response['avs_code']])) {
                            $avs_response_order_note .= ' : ' . $this->AVSCodes[$processor_response['avs_code']];
                        }
                        $order->add_order_note($avs_response_order_note);
                    }
                    if (!empty($processor_response['cvv_code'])) {
                        $cvv2_response_code = __('Card Security Code Result', 'paypal-for-woocommerce');
                        $cvv2_response_code .= "\n";
                        $cvv2_response_code .= $processor_response['cvv_code'];
                        if (isset($this->CVV2Codes[$processor_response['cvv_code']])) {
                            $cvv2_response_code .= ' : ' . $this->CVV2Codes[$processor_response['cvv_code']];
                        }
                        $order->add_order_note($cvv2_response_code);
                    }
                    if (!empty($processor_response['response_code'])) {
                        $response_code = __('Processor response code Result', 'paypal-for-woocommerce');
                        $response_code .= "\n";
                        $response_code .= $processor_response['response_code'];
                        if (angelleye_ppcp_processor_response_code($processor_response['response_code'])) {
                            $response_code .= ' : ' . angelleye_ppcp_processor_response_code($processor_response['response_code']);
                        }
                        $order->add_order_note($response_code);
                    }
                    $currency_code = isset($this->api_response['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['currency_code']) ? $this->api_response['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['currency_code'] : '';
                    $value = isset($this->api_response['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['value']) ? $this->api_response['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['value'] : '';
                    angelleye_ppcp_update_post_meta($order, '_paypal_fee', $value);
                    angelleye_ppcp_update_post_meta($order, '_paypal_transaction_fee', $value);
                    angelleye_ppcp_update_post_meta($order, '_paypal_fee_currency_code', $currency_code);
                    $transaction_id = isset($this->api_response['purchase_units']['0']['payments']['captures']['0']['id']) ? $this->api_response['purchase_units']['0']['payments']['captures']['0']['id'] : '';
                    $seller_protection = isset($this->api_response['purchase_units']['0']['payments']['captures']['0']['seller_protection']['status']) ? $this->api_response['purchase_units']['0']['payments']['captures']['0']['seller_protection']['status'] : '';
                    $payment_status = isset($this->api_response['purchase_units']['0']['payments']['captures']['0']['status']) ? $this->api_response['purchase_units']['0']['payments']['captures']['0']['status'] : '';
                    if ($payment_status == 'COMPLETED') {
                        $order->payment_complete($transaction_id);
                        $order->add_order_note(sprintf(__('Payment via %s: %s.', 'paypal-for-woocommerce'), $this->title, ucfirst(strtolower($payment_status))));
                    } elseif ($payment_status === 'DECLINED') {
                        $order->update_status('failed', sprintf(__('Payment via %s declined.', 'paypal-for-woocommerce'), $this->title));
                        wc_add_notice(__('Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'paypal-for-woocommerce'), 'error');
                        return false;
                    } else {
                        $payment_status_reason = isset($this->api_response['purchase_units']['0']['payments']['captures']['0']['status_details']['reason']) ? $this->api_response['purchase_units']['0']['payments']['captures']['0']['status_details']['reason'] : '';
                        $this->angelleye_ppcp_update_woo_order_status($woo_order_id, $payment_status, $payment_status_reason);
                    }
                    angelleye_ppcp_update_post_meta($order, '_payment_status', $payment_status);
                    $order->add_order_note(sprintf(__('%s Transaction ID: %s', 'paypal-for-woocommerce'), 'PayPal', $transaction_id));
                    $order->add_order_note('Seller Protection Status: ' . angelleye_ppcp_readable($seller_protection));
                    return true;
                } else {
                    return false;
                }
            } else {
                $error_email_notification_param = array(
                    'request' => 'capture_order',
                    'order_id' => $woo_order_id
                );
                $error_message = $this->angelleye_ppcp_get_readable_message($this->api_response, $error_email_notification_param);
                wc_add_notice($error_message, 'error');
                return false;
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_update_order($order) {
        try {
            $old_wc = version_compare(WC_VERSION, '3.0', '<');
            $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
            $patch_request = array();
            $reference_id = angelleye_ppcp_get_session('angelleye_ppcp_reference_id');
            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            $cart = $this->angelleye_ppcp_get_details_from_order($order_id);
            $purchase_units = array(
                'reference_id' => $reference_id,
                'amount' =>
                array(
                    'currency_code' => angelleye_ppcp_get_currency($order_id),
                    'value' => $cart['order_total'],
                    'breakdown' => array()
                )
            );
            $purchase_units['invoice_id'] = $this->invoice_prefix . str_replace("#", "", $order->get_order_number());
            $purchase_units['custom_id'] = apply_filters('angelleye_ppcp_custom_id', $this->invoice_prefix . str_replace("#", "", $order->get_order_number()), $order);
            $purchase_units['payee']['merchant_id'] = $this->merchant_id;
            if ($this->send_items === true) {
                if (isset($cart['total_item_amount']) && $cart['total_item_amount'] > 0) {
                    $purchase_units['amount']['breakdown']['item_total'] = array(
                        'currency_code' => angelleye_ppcp_get_currency($order_id),
                        'value' => $cart['total_item_amount'],
                    );
                }
                if (isset($cart['shipping']) && $cart['shipping'] > 0) {
                    $purchase_units['amount']['breakdown']['shipping'] = array(
                        'currency_code' => angelleye_ppcp_get_currency($order_id),
                        'value' => $cart['shipping'],
                    );
                }
                if (isset($cart['ship_discount_amount']) && $cart['ship_discount_amount'] > 0) {
                    $purchase_units['amount']['breakdown']['shipping_discount'] = array(
                        'currency_code' => angelleye_ppcp_get_currency($order_id),
                        'value' => angelleye_ppcp_round($cart['ship_discount_amount'], $decimals),
                    );
                }
                if (isset($cart['order_tax']) && $cart['order_tax'] > 0) {
                    $purchase_units['amount']['breakdown']['tax_total'] = array(
                        'currency_code' => angelleye_ppcp_get_currency($order_id),
                        'value' => $cart['order_tax'],
                    );
                }
                if (isset($cart['discount']) && $cart['discount'] > 0) {
                    $purchase_units['amount']['breakdown']['discount'] = array(
                        'currency_code' => angelleye_ppcp_get_currency($woo_order_id),
                        'value' => $cart['discount'],
                    );
                }

                if (isset($cart['items']) && !empty($cart['items'])) {
                    foreach ($cart['items'] as $key => $order_items) {
                        $description = !empty($order_items['description']) ? strip_shortcodes($order_items['description']) : '';
                        $product_name = !empty($order_items['name']) ? $order_items['name'] : '';
                        $purchase_units['items'][$key] = array(
                            'name' => $product_name,
                            'description' => html_entity_decode($description, ENT_NOQUOTES, 'UTF-8'),
                            'sku' => $order_items['sku'],
                            'category' => $order_items['category'],
                            'quantity' => $order_items['quantity'],
                            'unit_amount' =>
                            array(
                                'currency_code' => angelleye_ppcp_get_currency($order_id),
                                'value' => $order_items['amount'],
                            ),
                        );
                    }
                }
            }
            if (( $old_wc && ( $order->shipping_address_1 || $order->shipping_address_2 ) ) || (!$old_wc && $order->has_shipping_address() )) {
                $shipping_first_name = $old_wc ? $order->shipping_first_name : $order->get_shipping_first_name();
                $shipping_last_name = $old_wc ? $order->shipping_last_name : $order->get_shipping_last_name();
                $shipping_address_1 = $old_wc ? $order->shipping_address_1 : $order->get_shipping_address_1();
                $shipping_address_2 = $old_wc ? $order->shipping_address_2 : $order->get_shipping_address_2();
                $shipping_city = $old_wc ? $order->shipping_city : $order->get_shipping_city();
                $shipping_state = $old_wc ? $order->shipping_state : $order->get_shipping_state();
                $shipping_postcode = $old_wc ? $order->shipping_postcode : $order->get_shipping_postcode();
                $shipping_country = $old_wc ? $order->shipping_country : $order->get_shipping_country();
            } else {
                $shipping_first_name = $old_wc ? $order->billing_first_name : $order->get_billing_first_name();
                $shipping_last_name = $old_wc ? $order->billing_last_name : $order->get_billing_last_name();
                $shipping_address_1 = $old_wc ? $order->billing_address_1 : $order->get_billing_address_1();
                $shipping_address_2 = $old_wc ? $order->billing_address_2 : $order->get_billing_address_2();
                $shipping_city = $old_wc ? $order->billing_city : $order->get_billing_city();
                $shipping_state = $old_wc ? $order->billing_state : $order->get_billing_state();
                $shipping_postcode = $old_wc ? $order->billing_postcode : $order->get_billing_postcode();
                $shipping_country = $old_wc ? $order->billing_country : $order->get_billing_country();
            }
            if ($order->needs_shipping_address() || WC()->cart->needs_shipping_address()) {
                if (!empty($shipping_first_name) && !empty($shipping_last_name)) {
                    $purchase_units['shipping']['name']['full_name'] = $shipping_first_name . ' ' . $shipping_last_name;
                }
                angelleye_ppcp_set_session('angelleye_ppcp_is_shipping_added', 'yes');
                $purchase_units['shipping']['address'] = array(
                    'address_line_1' => $shipping_address_1,
                    'address_line_2' => $shipping_address_2,
                    'admin_area_2' => $shipping_city,
                    'admin_area_1' => $shipping_state,
                    'postal_code' => $shipping_postcode,
                    'country_code' => $shipping_country,
                );
            }
            $body_request = angelleye_ppcp_remove_empty_key($purchase_units);
            $patch_request[] = array(
                'op' => 'replace',
                'path' => "/purchase_units/@reference_id=='$reference_id'",
                'value' => $body_request
            );
            $paypal_order_id = angelleye_ppcp_get_session('angelleye_ppcp_paypal_order_id');
            $args = array(
                'timeout' => 70,
                'method' => 'PATCH',
                'httpversion' => '1.1',
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'body' => $patch_request,
                'user-agent' => 'PPCP/' . VERSION_PFW,
            );
            $this->api_request->request($this->paypal_order_api . $paypal_order_id, $args, 'update_order');
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_get_details_from_order($order_id) {
        try {
            $order = wc_get_order($order_id);
            $decimals = $this->angelleye_ppcp_is_currency_supports_zero_decimal() ? 0 : 2;
            $rounded_total = $this->angelleye_ppcp_get_rounded_total_in_order($order);
            $details = array(
                'total_item_amount' => angelleye_ppcp_round($order->get_subtotal(), $decimals),
                'order_tax' => angelleye_ppcp_round($order->get_total_tax(), $decimals),
                'shipping' => angelleye_ppcp_round(( version_compare(WC_VERSION, '3.0', '<') ? $order->get_total_shipping() : $order->get_shipping_total()), $decimals),
                'items' => $this->angelleye_ppcp_get_paypal_line_items_from_order($order),
            );
            $details = $this->angelleye_ppcp_get_details($details, $order->get_total_discount(), $rounded_total, $order->get_total());
            return $details;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_get_rounded_total_in_order($order) {
        try {
            $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
            $order = wc_get_order($order);
            $rounded_total = 0;
            foreach ($order->get_items() as $cart_item_key => $values) {
                $amount = angelleye_ppcp_round($values['line_subtotal'] / $values['qty'], $decimals);
                $rounded_total += angelleye_ppcp_round($amount * $values['qty'], $decimals);
            }
            return $rounded_total;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_get_paypal_line_items_from_order($order) {
        try {
            $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
            $items = array();
            foreach ($order->get_items() as $cart_item_key => $values) {
                $desc = '';
                $amount = angelleye_ppcp_round($values['line_subtotal'] / $values['qty'], $decimals);
                $product = version_compare(WC_VERSION, '3.0', '<') ? $order->get_product_from_item($values) : $values->get_product();
                $name = $product->get_name();
                $sku = $product->get_sku();
                $category = $product->needs_shipping() ? 'PHYSICAL_GOODS' : 'DIGITAL_GOODS';
                if (is_object($product)) {
                    if ($product->is_type('variation') && is_a($product, 'WC_Product_Variation')) {
                        $desc = '';
                        if (version_compare(WC_VERSION, '3.0', '<')) {
                            $attributes = $product->get_variation_attributes();
                            if (!empty($attributes) && is_array($attributes)) {
                                foreach ($attributes as $key => $value) {
                                    $key = str_replace(array('attribute_pa_', 'attribute_'), '', $key);
                                    $desc .= ' ' . ucwords(str_replace('pa_', '', $key)) . ': ' . $value;
                                }
                                $desc = trim($desc);
                            }
                        } else {
                            $attributes = $product->get_attributes();
                            if (!empty($attributes) && is_array($attributes)) {
                                foreach ($attributes as $key => $value) {
                                    $desc .= ' ' . ucwords(str_replace('pa_', '', $key)) . ': ' . $value;
                                }
                            }
                            $desc = trim($desc);
                        }
                    }
                }
                $product_name = !empty($name) ? $name : '';
                $product_name = apply_filters('angelleye_ppcp_product_name', $product_name, $product, $desc, $values);
                $product_name = wp_strip_all_tags($product_name);
                if (strlen($product_name) > 127) {
                    $product_name = substr($product_name, 0, 124) . '...';
                }
                if (empty($desc)) {
                    $desc = $product_name;
                }
                if (strlen($desc) > 127) {
                    $desc = substr($desc, 0, 124) . '...';
                }
                $desc = strip_shortcodes($desc);
                $item = array(
                    'name' => $product_name,
                    'description' => apply_filters('angelleye_ppcp_product_description', $desc),
                    'sku' => $sku,
                    'category' => $category,
                    'quantity' => $values['quantity'],
                    'amount' => $amount,
                );
                $items[] = $item;
            }
            return $items;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_refund_order($order_id, $amount, $reason, $transaction_id) {
        try {
            $order = wc_get_order($order_id);
            $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
            $reason = !empty($reason) ? $reason : 'Refund';
            $body_request['note_to_payer'] = $reason;
            if (!empty($amount) && $amount > 0) {
                $body_request['amount'] = array(
                    'value' => angelleye_ppcp_round($amount, $decimals),
                    'currency_code' => $order->get_currency()
                );
            }
            $body_request = angelleye_ppcp_remove_empty_key($body_request);
            $args = array(
                'method' => 'POST',
                'timeout' => 60,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking' => true,
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'body' => $body_request,
                'cookies' => array()
            );
            $this->api_response = $this->api_request->request($this->paypal_refund_api . $transaction_id . '/refund', $args, 'refund_order');
            if (isset($this->api_response['status']) && $this->api_response['status'] == "COMPLETED") {
                $gross_amount = isset($this->api_response['seller_payable_breakdown']['gross_amount']['value']) ? $this->api_response['seller_payable_breakdown']['gross_amount']['value'] : '';
                $refund_transaction_id = isset($this->api_response['id']) ? $this->api_response['id'] : '';
                $order->add_order_note(
                        sprintf(__('Refunded %1$s - Refund ID: %2$s', 'paypal-for-woocommerce'), $gross_amount, $refund_transaction_id)
                );
            } else if (isset($this->api_response['status']) && $this->api_response['status'] == "PENDING") {
                $gross_amount = isset($this->api_response['seller_payable_breakdown']['gross_amount']['value']) ? $this->api_response['seller_payable_breakdown']['gross_amount']['value'] : '';
                $refund_transaction_id = isset($this->api_response['id']) ? $this->api_response['id'] : '';
                $pending_reason_text = isset($this->api_response['status_details']['reason']) ? $this->api_response['status_details']['reason'] : '';
                $order->add_order_note(sprintf(__('Payment via %s Pending. Pending reason: %s.', 'paypal-for-woocommerce'), $this->title, $pending_reason_text));
                $order->add_order_note(
                        sprintf(__('Refund Amount %1$s - Refund ID: %2$s', 'paypal-for-woocommerce'), $gross_amount, $refund_transaction_id)
                );
            } else {
                $this->paymentaction = apply_filters('angelleye_ppcp_paymentaction', $this->paymentaction, $order_id);
                if ($this->paymentaction === 'authorize' && !empty($this->api_response['details'][0]['issue']) && 'INVALID_RESOURCE_ID' === $this->api_response['details'][0]['issue']) {
                    $this->angelleye_ppcp_void_authorized_payment($transaction_id);
                    return true;
                }
                $error_email_notification_param = array(
                    'request' => 'refund_order',
                    'order_id' => $order_id
                );
                $error_message = $this->angelleye_ppcp_get_readable_message($this->api_response, $error_email_notification_param);
                if (!empty($error_message)) {
                    $order->add_order_note('Error Message : ' . $error_message);
                    throw new Exception($error_message);
                }
                return false;
            }
            return true;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
            return new WP_Error('error', $ex->getMessage());
        }
    }

    public function angelleye_ppcp_order_auth_request($woo_order_id) {
        try {
            $order = wc_get_order($woo_order_id);
            $this->angelleye_ppcp_update_order($order);
            $paypal_order_id = angelleye_ppcp_get_session('angelleye_ppcp_paypal_order_id');
            $args = array(
                'method' => 'POST',
                'timeout' => 60,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking' => true,
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
            );
            $this->api_response = $this->api_request->request($this->paypal_order_api . $paypal_order_id . '/authorize', $args, 'authorize_order');
            $angelleye_ppcp_payment_method_title = angelleye_ppcp_get_session('angelleye_ppcp_payment_method_title');
            if (!empty($angelleye_ppcp_payment_method_title)) {
                update_post_meta($woo_order_id, '_payment_method_title', $angelleye_ppcp_payment_method_title);
                update_post_meta($woo_order_id, 'payment_method_title', $angelleye_ppcp_payment_method_title);
            }
            if (!empty($this->api_response['id'])) {
                if (isset($woo_order_id) && !empty($woo_order_id)) {
                    angelleye_ppcp_update_post_meta($order, '_paypal_order_id', $this->api_response['id']);
                }
                if ($this->api_response['status'] == 'COMPLETED') {
                    $transaction_id = isset($this->api_response['purchase_units']['0']['payments']['authorizations']['0']['id']) ? $this->api_response['purchase_units']['0']['payments']['authorizations']['0']['id'] : '';
                    $seller_protection = isset($this->api_response['purchase_units']['0']['payments']['authorizations']['0']['seller_protection']['status']) ? $this->api_response['purchase_units']['0']['payments']['authorizations']['0']['seller_protection']['status'] : '';
                    $payment_status = isset($this->api_response['purchase_units']['0']['payments']['authorizations']['0']['status']) ? $this->api_response['purchase_units']['0']['payments']['authorizations']['0']['status'] : '';
                    angelleye_ppcp_update_post_meta($order, '_transaction_id', $transaction_id);
                    angelleye_ppcp_update_post_meta($order, '_payment_status', $payment_status);
                    angelleye_ppcp_update_post_meta($order, '_auth_transaction_id', $transaction_id);
                    angelleye_ppcp_update_post_meta($order, '_paymentaction', $this->paymentaction);
                    $order->add_order_note(sprintf(__('%s Transaction ID: %s', 'paypal-for-woocommerce'), $this->title, $transaction_id));
                    $order->add_order_note('Seller Protection Status: ' . angelleye_ppcp_readable($seller_protection));
                    $order->update_status('on-hold');
                    $order->add_order_note(__('Payment authorized. Change payment status to processing or complete to capture funds.', 'paypal-for-woocommerce'));
                }
                $angelleye_ppcp_payment_method_title = angelleye_ppcp_get_session('angelleye_ppcp_payment_method_title');
                if (!empty($angelleye_ppcp_payment_method_title)) {
                    update_post_meta($woo_order_id, '_payment_method_title', $angelleye_ppcp_payment_method_title);
                    update_post_meta($woo_order_id, 'payment_method_title', $angelleye_ppcp_payment_method_title);
                }
                WC()->cart->empty_cart();
                return true;
            } else {
                $error_email_notification_param = array(
                    'request' => 'authorize_order',
                    'order_id' => $woo_order_id
                );
                $error_message = $this->angelleye_ppcp_get_readable_message($this->api_response, $error_email_notification_param);
                wc_add_notice($error_message, 'error');
                return false;
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_show_details_authorized_payment($authorization_id) {
        try {
            $args = array(
                'timeout' => 60,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking' => true,
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                //'body' => array(),
                'cookies' => array()
            );
            $this->api_response = $this->api_request->request($this->auth . $authorization_id, $args, 'get_authorized');
            $this->api_response = json_decode(json_encode($this->api_response), FALSE);
            angelleye_ppcp_set_session('angelleye_ppcp_paypal_transaction_details', $this->api_response);
            return $this->api_response;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_void_authorized_payment($authorization_id) {
        try {
            $args = array(
                'method' => 'POST',
                'timeout' => 60,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking' => true,
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                //'body' => array(),
                'cookies' => array()
            );
            $this->api_response = $this->api_request->request($this->auth . $authorization_id . '/void', $args, 'void_authorized');
            $this->api_response = json_decode(json_encode($this->api_response), FALSE);
            return $this->api_response;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_capture_authorized_payment($woo_order_id) {
        try {
            $order = wc_get_order($woo_order_id);
            if ($order === false) {
                return false;
            }
            $capture_arg = array(
                'amount' =>
                array(
                    'value' => $order->get_total(),
                    'currency_code' => version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency(),
                ),
                'invoice_id' => $this->invoice_prefix . str_replace("#", "", $order->get_order_number()),
                'final_capture' => true,
            );
            $body_request = angelleye_ppcp_remove_empty_key($capture_arg);
            $authorization_id = angelleye_ppcp_get_post_meta($order, '_auth_transaction_id');
            $args = array(
                'method' => 'POST',
                'timeout' => 60,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking' => true,
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'body' => $body_request,
                'cookies' => array()
            );
            $this->api_response = $this->api_request->request($this->auth . $authorization_id . '/capture', $args, 'capture_authorized');
            $angelleye_ppcp_payment_method_title = angelleye_ppcp_get_session('angelleye_ppcp_payment_method_title');
            if (!empty($angelleye_ppcp_payment_method_title)) {
                update_post_meta($woo_order_id, '_payment_method_title', $angelleye_ppcp_payment_method_title);
                update_post_meta($woo_order_id, 'payment_method_title', $angelleye_ppcp_payment_method_title);
            }
            if (!empty($this->api_response['id'])) {
                angelleye_ppcp_update_post_meta($order, '_paypal_order_id', $this->api_response['id']);
                $payment_source = isset($this->api_response['payment_source']) ? $this->api_response['payment_source'] : '';
                if (!empty($payment_source['card'])) {
                    $card_response_order_note = __('Card Details', 'paypal-for-woocommerce');
                    $card_response_order_note .= "\n";
                    $card_response_order_note .= 'Last digits : ' . $payment_source['card']['last_digits'];
                    $card_response_order_note .= "\n";
                    $card_response_order_note .= 'Brand : ' . $payment_source['card']['brand'];
                    $card_response_order_note .= "\n";
                    $card_response_order_note .= 'Card type : ' . $payment_source['card']['type'];
                    $order->add_order_note($card_response_order_note);
                }
                $processor_response = isset($this->api_response['purchase_units']['0']['payments']['captures']['0']['processor_response']) ? $this->api_response['purchase_units']['0']['payments']['captures']['0']['processor_response'] : '';
                if (!empty($processor_response['avs_code'])) {
                    $avs_response_order_note = __('Address Verification Result', 'paypal-for-woocommerce');
                    $avs_response_order_note .= "\n";
                    $avs_response_order_note .= $processor_response['avs_code'];
                    if (isset($this->AVSCodes[$processor_response['avs_code']])) {
                        $avs_response_order_note .= ' : ' . $this->AVSCodes[$processor_response['avs_code']];
                    }
                    $order->add_order_note($avs_response_order_note);
                }
                if (!empty($processor_response['cvv_code'])) {
                    $cvv2_response_code = __('Card Security Code Result', 'paypal-for-woocommerce');
                    $cvv2_response_code .= "\n";
                    $cvv2_response_code .= $processor_response['cvv_code'];
                    if (isset($this->CVV2Codes[$processor_response['cvv_code']])) {
                        $cvv2_response_code .= ' : ' . $this->CVV2Codes[$processor_response['cvv_code']];
                    }
                    $order->add_order_note($cvv2_response_code);
                }
                if (!empty($processor_response['response_code'])) {
                    $response_code = __('Processor response code Result', 'paypal-for-woocommerce');
                    $response_code .= "\n";
                    $response_code .= $processor_response['response_code'];
                    if (angelleye_ppcp_processor_response_code($processor_response['response_code'])) {
                        $response_code .= ' : ' . angelleye_ppcp_processor_response_code($processor_response['response_code']);
                    }
                    $order->add_order_note($response_code);
                }
                $currency_code = isset($this->api_response['seller_receivable_breakdown']['paypal_fee']['currency_code']) ? $this->api_response['seller_receivable_breakdown']['paypal_fee']['currency_code'] : '';
                $value = isset($this->api_response['seller_receivable_breakdown']['paypal_fee']['value']) ? $this->api_response['seller_receivable_breakdown']['paypal_fee']['value'] : '';
                angelleye_ppcp_update_post_meta($order, '_paypal_fee', $value);
                angelleye_ppcp_update_post_meta($order, '_paypal_transaction_fee', $value);
                angelleye_ppcp_update_post_meta($order, '_paypal_fee_currency_code', $currency_code);
                $transaction_id = isset($this->api_response['id']) ? $this->api_response['id'] : '';
                $seller_protection = isset($this->api_response['seller_protection']['status']) ? $this->api_response['seller_protection']['status'] : '';
                $payment_status = isset($this->api_response['status']) ? $this->api_response['status'] : '';
                angelleye_ppcp_update_post_meta($order, '_payment_status', $payment_status);
                $order->add_order_note(sprintf(__('%s Transaction ID: %s', 'paypal-for-woocommerce'), $this->title, $transaction_id));
                $order->add_order_note('Seller Protection Status: ' . angelleye_ppcp_readable($seller_protection));
                if ($payment_status === 'COMPLETED') {
                    $order->payment_complete($transaction_id);
                    $order->add_order_note(sprintf(__('Payment via %s: %s.', 'paypal-for-woocommerce'), $this->title, ucfirst(strtolower($payment_status))));
                } elseif ($payment_status === 'DECLINED') {
                    $order->update_status('failed', sprintf(__('Payment via %s declined.', 'paypal-for-woocommerce'), $this->title));
                    if (function_exists('wc_add_notice')) {
                        wc_add_notice(__('Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'paypal-for-woocommerce'), 'error');
                    }
                    return false;
                } else {
                    $payment_status_reason = isset($this->api_response['status_details']['reason']) ? $this->api_response['status_details']['reason'] : '';
                    $this->angelleye_ppcp_update_woo_order_status($woo_order_id, $payment_status, $payment_status_reason);
                }
                update_post_meta($woo_order_id, '_transaction_id', $transaction_id);
                angelleye_ppcp_update_post_meta($order, '_transaction_id', $transaction_id);
                return true;
            } else {
                $error_email_notification_param = array(
                    'request' => 'capture_authorized',
                    'order_id' => $woo_order_id
                );
                $error_message = $this->angelleye_ppcp_get_readable_message($this->api_response, $error_email_notification_param);
                if (function_exists('wc_add_notice')) {
                    wc_add_notice($error_message, 'error');
                }
                if (!empty($error_message)) {
                    $order->add_order_note('Error Message : ' . $error_message);
                    throw new Exception($error_message);
                }
                return false;
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_update_woo_order_data($paypal_order_id) {
        $this->checkout_details = $this->angelleye_ppcp_get_checkout_details($paypal_order_id);
        angelleye_ppcp_set_session('angelleye_ppcp_paypal_transaction_details', $this->checkout_details);
        if (empty($this->checkout_details)) {
            return false;
        }
        if (!empty($this->checkout_details)) {
            $shipping_details = angelleye_ppcp_get_mapped_shipping_address($this->checkout_details);
            $billing_details = angelleye_ppcp_get_mapped_billing_address($this->checkout_details);
            angelleye_ppcp_update_customer_addresses_from_paypal($shipping_details, $billing_details);
        }
        $order_id = angelleye_ppcp_get_session('angelleye_ppcp_woo_order_id');
        if (empty($order_id)) {
            $order_id = absint(WC()->session->get('order_awaiting_payment'));
        }
        $order = wc_get_order($order_id);
        $this->checkout_details = $this->checkout_details;
        $this->paymentaction = apply_filters('angelleye_ppcp_paymentaction', $this->paymentaction, $order_id);
        $angelleye_ppcp_payment_method_title = angelleye_ppcp_get_session('angelleye_ppcp_payment_method_title');
        if (!empty($angelleye_ppcp_payment_method_title)) {
            update_post_meta($order_id, '_payment_method_title', $angelleye_ppcp_payment_method_title);
            update_post_meta($order_id, 'payment_method_title', $angelleye_ppcp_payment_method_title);
        }
        if ($this->paymentaction === 'capture' && !empty($this->checkout_details->status) && $this->checkout_details->status == 'COMPLETED' && $order !== false) {
            $transaction_id = isset($this->checkout_details->purchase_units[0]->payments->captures[0]->id) ? $this->checkout_details->purchase_units['0']->payments->captures[0]->id : '';
            $seller_protection = isset($this->checkout_details->purchase_units[0]->payments->captures[0]->seller_protection->status) ? $this->checkout_details->purchase_units[0]->payments->captures[0]->seller_protection->status : '';
            $payment_source = isset($this->checkout_details->payment_source) ? $this->checkout_details->payment_source : '';
            if (!empty($payment_source->card)) {
                $card_response_order_note = __('Card Details', 'paypal-for-woocommerce');
                $card_response_order_note .= "\n";
                $card_response_order_note .= 'Last digits : ' . $payment_source->card->last_digits;
                $card_response_order_note .= "\n";
                $card_response_order_note .= 'Brand : ' . angelleye_ppcp_readable($payment_source->card->brand);
                $card_response_order_note .= "\n";
                $card_response_order_note .= 'Card type : ' . angelleye_ppcp_readable($payment_source->card->type);
                $order->add_order_note($card_response_order_note);
            }
            $processor_response = isset($this->checkout_details->purchase_units[0]->payments->captures[0]->processor_response) ? $this->checkout_details->purchase_units[0]->payments->captures[0]->processor_response : '';
            if (!empty($processor_response->avs_code)) {
                $avs_response_order_note = __('Address Verification Result', 'paypal-for-woocommerce');
                $avs_response_order_note .= "\n";
                $avs_response_order_note .= $processor_response->avs_code;
                if (isset($this->AVSCodes[$processor_response->avs_code])) {
                    $avs_response_order_note .= ' : ' . $this->AVSCodes[$processor_response->avs_code];
                }
                $order->add_order_note($avs_response_order_note);
            }
            if (!empty($processor_response->cvv_code)) {
                $cvv2_response_code = __('Card Security Code Result', 'paypal-for-woocommerce');
                $cvv2_response_code .= "\n";
                $cvv2_response_code .= $processor_response->cvv_code;
                if (isset($this->CVV2Codes[$processor_response->cvv_code])) {
                    $cvv2_response_code .= ' : ' . $this->CVV2Codes[$processor_response->cvv_code];
                }
                $order->add_order_note($cvv2_response_code);
            }
            if (!empty($processor_response['response_code'])) {
                $response_code = __('Processor response code Result', 'paypal-for-woocommerce');
                $response_code .= "\n";
                $response_code .= $processor_response['response_code'];
                if (angelleye_ppcp_processor_response_code($processor_response['response_code'])) {
                    $response_code .= ' : ' . angelleye_ppcp_processor_response_code($processor_response['response_code']);
                }
                $order->add_order_note($response_code);
            }
            $currency_code = isset($this->checkout_details->purchase_units[0]->payments->captures[0]->seller_receivable_breakdown->paypal_fee->currency_code) ? $this->checkout_details->purchase_units[0]->payments->captures[0]->seller_receivable_breakdown->paypal_fee->currency_code : '';
            $value = isset($this->checkout_details->purchase_units[0]->payments->captures[0]->seller_receivable_breakdown->paypal_fee->value) ? $this->checkout_details->purchase_units[0]->payments->captures[0]->seller_receivable_breakdown->paypal_fee->value : '';
            angelleye_ppcp_update_post_meta($order, '_paypal_fee', $value);
            angelleye_ppcp_update_post_meta($order, '_paypal_transaction_fee', $value);
            angelleye_ppcp_update_post_meta($order, '_paypal_fee_currency_code', $currency_code);
            $payment_status = isset($this->checkout_details->purchase_units[0]->payments->captures[0]->status) ? $this->checkout_details->purchase_units[0]->payments->captures[0]->status : '';
            if ($payment_status == 'COMPLETED') {
                $order->payment_complete($transaction_id);
                $order->add_order_note(sprintf(__('Payment via %s: %s .', 'paypal-for-woocommerce'), $this->title, ucfirst(strtolower($payment_status))));
            } else {
                $payment_status_reason = isset($this->checkout_details->purchase_units[0]->payments->captures[0]->status_details->reason) ? $this->checkout_details->purchase_units[0]->payments->captures[0]->status_details->reason : '';
                $this->angelleye_ppcp_update_woo_order_status($order_id, $payment_status, $payment_status_reason);
            }
            $order->add_order_note(sprintf(__('%s Transaction ID: %s', 'paypal-for-woocommerce'), $this->title, $transaction_id));
            $order->add_order_note('Seller Protection Status: ' . angelleye_ppcp_readable($seller_protection));
        } elseif ($this->paymentaction === 'authorize' && !empty($this->checkout_details->status) && $this->checkout_details->status == 'COMPLETED' && $order !== false) {
            $transaction_id = isset($this->checkout_details->purchase_units[0]->payments->authorizations[0]->id) ? $this->checkout_details->purchase_units['0']->payments->authorizations[0]->id : '';
            $seller_protection = isset($this->checkout_details->purchase_units[0]->payments->authorizations[0]->seller_protection->status) ? $this->checkout_details->purchase_units[0]->payments->authorizations[0]->seller_protection->status : '';
            $payment_status = isset($this->checkout_details->purchase_units[0]->payments->authorizations[0]->status) ? $this->checkout_details->purchase_units[0]->payments->authorizations[0]->status : '';
            $payment_status_reason = isset($this->checkout_details->purchase_units[0]->payments->authorizations[0]->status_details->reason) ? $this->checkout_details->purchase_units[0]->payments->authorizations[0]->status_details->reason : '';
            if (!empty($payment_status_reason)) {
                $order->add_order_note(sprintf(__('Payment via %s Pending. PayPal reason: %s.', 'paypal-for-woocommerce'), $this->title, $payment_status_reason));
            }
            angelleye_ppcp_update_post_meta($order, '_transaction_id', $transaction_id);
            angelleye_ppcp_update_post_meta($order, '_payment_status', $payment_status);
            angelleye_ppcp_update_post_meta($order, '_auth_transaction_id', $transaction_id);
            angelleye_ppcp_update_post_meta($order, '_paymentaction', $this->paymentaction);
            $order->add_order_note(sprintf(__('%s Transaction ID: %s', 'paypal-for-woocommerce'), $this->title, $transaction_id));
            $order->add_order_note('Seller Protection Status: ' . angelleye_ppcp_readable($seller_protection));
            $order->update_status('on-hold');
            $order->add_order_note(__('Payment authorized. Change order status to processing or complete for capture funds.', 'paypal-for-woocommerce'));
        }
    }

    public function angelleye_ppcp_paypalauthassertion() {
        $temp = array(
            "alg" => "none"
        );
        $returnData = base64_encode(json_encode($temp)) . '.';
        $temp = array(
            "iss" => $this->partner_client_id,
            "payer_id" => $this->merchant_id
        );
        $returnData .= base64_encode(json_encode($temp)) . '.';
        return $returnData;
    }

    public function angelleye_ppcp_get_generate_token() {
        try {
            $args = array(
                'method' => 'POST',
                'timeout' => 60,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking' => true,
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'cookies' => array(),
                    //'body' => array(), //json_encode(array('customer_id' => 'customer_1234_wow'))
            );
            $response = $this->api_request->request($this->generate_token_url, $args, 'get client token');
            if (!empty($response['client_token'])) {
                $this->client_token = $response['client_token'];
                return $this->client_token;
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_regular_create_order_request($woo_order_id = null) {
        try {
            if ($woo_order_id == null) {
                $cart = $this->angelleye_ppcp_get_details_from_cart();
            } else {
                $cart = $this->angelleye_ppcp_get_details_from_order($woo_order_id);
            }
            $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
            $reference_id = wc_generate_order_key();
            angelleye_ppcp_set_session('angelleye_ppcp_reference_id', $reference_id);
            $intent = ($this->paymentaction === 'capture') ? 'CAPTURE' : 'AUTHORIZE';
            $body_request = array(
                'intent' => $intent,
                'application_context' => $this->angelleye_ppcp_application_context(),
                'payment_method' => array('payee_preferred' => ($this->payee_preferred) ? 'IMMEDIATE_PAYMENT_REQUIRED' : 'UNRESTRICTED'),
                'purchase_units' =>
                array(
                    0 =>
                    array(
                        'reference_id' => $reference_id,
                        'amount' =>
                        array(
                            'currency_code' => angelleye_ppcp_get_currency($woo_order_id),
                            'value' => $cart['order_total'],
                            'breakdown' => array()
                        )
                    ),
                ),
            );
            if ($woo_order_id != null) {
                $order = wc_get_order($woo_order_id);
                $body_request['purchase_units'][0]['invoice_id'] = $this->invoice_prefix . str_replace("#", "", $order->get_order_number());
                $body_request['purchase_units'][0]['custom_id'] = apply_filters('angelleye_ppcp_custom_id', $this->invoice_prefix . str_replace("#", "", $order->get_order_number()), $order);
            } else {
                $body_request['purchase_units'][0]['invoice_id'] = $reference_id;
                $body_request['purchase_units'][0]['custom_id'] = apply_filters('angelleye_ppcp_custom_id', $reference_id, '');
            }
            $body_request['purchase_units'][0]['payee']['merchant_id'] = $this->merchant_id;
            if ($this->send_items === true) {
                if (isset($cart['total_item_amount']) && $cart['total_item_amount'] > 0) {
                    $body_request['purchase_units'][0]['amount']['breakdown']['item_total'] = array(
                        'currency_code' => angelleye_ppcp_get_currency($woo_order_id),
                        'value' => $cart['total_item_amount'],
                    );
                }
                if (isset($cart['shipping']) && $cart['shipping'] > 0) {
                    $body_request['purchase_units'][0]['amount']['breakdown']['shipping'] = array(
                        'currency_code' => angelleye_ppcp_get_currency($woo_order_id),
                        'value' => $cart['shipping'],
                    );
                }
                if (isset($cart['ship_discount_amount']) && $cart['ship_discount_amount'] > 0) {
                    $body_request['purchase_units'][0]['amount']['breakdown']['shipping_discount'] = array(
                        'currency_code' => angelleye_ppcp_get_currency($woo_order_id),
                        'value' => angelleye_ppcp_round($cart['ship_discount_amount'], $decimals),
                    );
                }
                if (isset($cart['order_tax']) && $cart['order_tax'] > 0) {
                    $body_request['purchase_units'][0]['amount']['breakdown']['tax_total'] = array(
                        'currency_code' => angelleye_ppcp_get_currency($woo_order_id),
                        'value' => $cart['order_tax'],
                    );
                }
                if (isset($cart['discount']) && $cart['discount'] > 0) {
                    $body_request['purchase_units'][0]['amount']['breakdown']['discount'] = array(
                        'currency_code' => angelleye_ppcp_get_currency($woo_order_id),
                        'value' => $cart['discount'],
                    );
                }

                if (isset($cart['items']) && !empty($cart['items'])) {
                    foreach ($cart['items'] as $key => $order_items) {
                        $description = !empty($order_items['description']) ? strip_shortcodes($order_items['description']) : '';
                        $product_name = !empty($order_items['name']) ? $order_items['name'] : '';
                        $body_request['purchase_units'][0]['items'][$key] = array(
                            'name' => $product_name,
                            'description' => html_entity_decode($description, ENT_NOQUOTES, 'UTF-8'),
                            'sku' => $order_items['sku'],
                            'category' => $order_items['category'],
                            'quantity' => $order_items['quantity'],
                            'unit_amount' =>
                            array(
                                'currency_code' => angelleye_ppcp_get_currency($woo_order_id),
                                'value' => $order_items['amount'],
                            ),
                        );
                    }
                }
            }
            if ($woo_order_id != null) {
                $order = wc_get_order($woo_order_id);
                $old_wc = version_compare(WC_VERSION, '3.0', '<');
                if (( $old_wc && ( $order->shipping_address_1 || $order->shipping_address_2 ) ) || (!$old_wc && $order->has_shipping_address() )) {
                    $shipping_first_name = $old_wc ? $order->shipping_first_name : $order->get_shipping_first_name();
                    $shipping_last_name = $old_wc ? $order->shipping_last_name : $order->get_shipping_last_name();
                    $shipping_address_1 = $old_wc ? $order->shipping_address_1 : $order->get_shipping_address_1();
                    $shipping_address_2 = $old_wc ? $order->shipping_address_2 : $order->get_shipping_address_2();
                    $shipping_city = $old_wc ? $order->shipping_city : $order->get_shipping_city();
                    $shipping_state = $old_wc ? $order->shipping_state : $order->get_shipping_state();
                    $shipping_postcode = $old_wc ? $order->shipping_postcode : $order->get_shipping_postcode();
                    $shipping_country = $old_wc ? $order->shipping_country : $order->get_shipping_country();
                } else {
                    $shipping_first_name = $old_wc ? $order->billing_first_name : $order->get_billing_first_name();
                    $shipping_last_name = $old_wc ? $order->billing_last_name : $order->get_billing_last_name();
                    $shipping_address_1 = $old_wc ? $order->billing_address_1 : $order->get_billing_address_1();
                    $shipping_address_2 = $old_wc ? $order->billing_address_2 : $order->get_billing_address_2();
                    $shipping_city = $old_wc ? $order->billing_city : $order->get_billing_city();
                    $shipping_state = $old_wc ? $order->billing_state : $order->get_billing_state();
                    $shipping_postcode = $old_wc ? $order->billing_postcode : $order->get_billing_postcode();
                    $shipping_country = $old_wc ? $order->billing_country : $order->get_billing_country();
                }
                if ($order->needs_shipping_address() || WC()->cart->needs_shipping_address()) {
                    if (!empty($shipping_first_name) && !empty($shipping_last_name)) {
                        $body_request['purchase_units'][0]['shipping']['name']['full_name'] = $shipping_first_name . ' ' . $shipping_last_name;
                    }
                    angelleye_ppcp_set_session('angelleye_ppcp_is_shipping_added', 'yes');
                    $body_request['purchase_units'][0]['shipping']['address'] = array(
                        'address_line_1' => $shipping_address_1,
                        'address_line_2' => $shipping_address_2,
                        'admin_area_2' => $shipping_city,
                        'admin_area_1' => $shipping_state,
                        'postal_code' => $shipping_postcode,
                        'country_code' => $shipping_country,
                    );
                }
            } else {
                if (true === WC()->cart->needs_shipping_address()) {
                    if (is_user_logged_in()) {
                        if (!empty($cart['shipping_address']['first_name']) && !empty($cart['shipping_address']['last_name'])) {
                            $body_request['purchase_units'][0]['shipping']['name']['full_name'] = $cart['shipping_address']['first_name'] . ' ' . $cart['shipping_address']['last_name'];
                        }
                        if (!empty($cart['shipping_address']['address_1']) && !empty($cart['shipping_address']['city']) && !empty($cart['shipping_address']['state']) && !empty($cart['shipping_address']['postcode']) && !empty($cart['shipping_address']['country'])) {
                            $body_request['purchase_units'][0]['shipping']['address'] = array(
                                'address_line_1' => $cart['shipping_address']['address_1'],
                                'address_line_2' => $cart['shipping_address']['address_2'],
                                'admin_area_2' => $cart['shipping_address']['city'],
                                'admin_area_1' => $cart['shipping_address']['state'],
                                'postal_code' => $cart['shipping_address']['postcode'],
                                'country_code' => $cart['shipping_address']['country'],
                            );
                            angelleye_ppcp_set_session('angelleye_ppcp_is_shipping_added', 'yes');
                        }
                    }
                }
            }
            $body_request = $this->angelleye_ppcp_set_payer_details($woo_order_id, $body_request);
            $body_request = angelleye_ppcp_remove_empty_key($body_request);
            $args = array(
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'body' => $body_request
            );
            $this->api_response = $this->api_request->request($this->paypal_order_api, $args, 'create_order');
            if (ob_get_length()) {
                ob_end_clean();
            }
            if (!empty($this->api_response['status'])) {
                $return_response['orderID'] = $this->api_response['id'];
                if (!empty(isset($woo_order_id) && !empty($woo_order_id))) {
                    angelleye_ppcp_update_post_meta($order, '_paypal_order_id', $this->api_response['id']);
                    //angelleye_ppcp_set_session('angelleye_ppcp_paypal_order_id', $this->api_response['id']);
                }
                if (!empty($this->api_response['links'])) {
                    foreach ($this->api_response['links'] as $key => $link_result) {
                        if ('approve' === $link_result['rel']) {

                            return array(
                                'result' => 'success',
                                'redirect' => $link_result['href']
                            );
                        }
                    }
                }
                return array(
                    'result' => 'fail',
                    'redirect' => ''
                );
            } else {
                $error_email_notification_param = array(
                    'request' => 'create_order',
                    'order_id' => $woo_order_id
                );
                $error_message = $this->angelleye_ppcp_get_readable_message($this->api_response);
                wc_add_notice($error_message, 'error');
                return array(
                    'result' => 'fail',
                    'redirect' => ''
                );
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_error_email_notification($error_email_notification_param, $error_message) {
        if (function_exists('WC')) {
            try {
                $mailer = WC()->mailer();
                $error_email_notify_subject = apply_filters('ae_ppec_error_email_subject', 'PayPal Complete Payments Error Notification');
                $message = '';
                if (!empty($error_email_notification_param['request'])) {
                    $message .= "<strong>" . __('Action: ', 'paypal-for-woocommerce') . "</strong>" . ucwords(str_replace('_', ' ', $error_email_notification_param['request'])) . PHP_EOL;
                }
                if (!empty($error_message)) {
                    $message .= "<strong>" . __('Error: ', 'paypal-for-woocommerce') . "</strong>" . $error_message . PHP_EOL;
                }
                if (!empty($error_email_notification_param['order_id'])) {
                    $message .= "<strong>" . __('Order ID: ', 'paypal-for-woocommerce') . "</strong>" . $error_email_notification_param['order_id'] . PHP_EOL;
                }
                if (is_user_logged_in()) {
                    $userLogined = wp_get_current_user();
                    $message .= "<strong>" . __('User ID: ', 'paypal-for-woocommerce') . "</strong>" . $userLogined->ID . PHP_EOL;
                    $message .= "<strong>" . __('User Email: ', 'paypal-for-woocommerce') . "</strong>" . $userLogined->user_email . PHP_EOL;
                }
                $message .= "<strong>" . __('User IP: ', 'paypal-for-woocommerce') . "</strong>" . WC_Geolocation::get_ip_address() . PHP_EOL;
                $message = apply_filters('ae_ppec_error_email_message', $message);
                $message = $mailer->wrap_message($error_email_notify_subject, $message);
                $mailer->send(get_option('admin_email'), strip_tags($error_email_notify_subject), $message);
            } catch (Exception $ex) {
                $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
                $this->api_log->log($ex->getMessage(), 'error');
            }
        }
    }

    public function angelleye_ppcp_update_woo_order_status($orderid, $payment_status, $pending_reason) {
        try {
            if (empty($pending_reason)) {
                $pending_reason = $payment_status;
            }
            $order = wc_get_order($orderid);
            switch (strtoupper($payment_status)) :
                case 'DECLINED' :
                    $order->update_status('failed', sprintf(__('Payment via %s declined.', 'paypal-for-woocommerce'), $this->title));
                case 'PENDING' :
                    switch (strtoupper($pending_reason)) {
                        case 'BUYER_COMPLAINT':
                            $pending_reason_text = __('BUYER_COMPLAINT: The payer initiated a dispute for this captured payment with PayPal.', 'paypal-for-woocommerce');
                            break;
                        case 'CHARGEBACK':
                            $pending_reason_text = __('CHARGEBACK: The captured funds were reversed in response to the payer disputing this captured payment with the issuer of the financial instrument used to pay for this captured payment.', 'paypal-for-woocommerce');
                            break;
                        case 'ECHECK':
                            $pending_reason_text = __('ECHECK: The payer paid by an eCheck that has not yet cleared.', 'paypal-for-woocommerce');
                            break;
                        case 'INTERNATIONAL_WITHDRAWAL':
                            $pending_reason_text = __('INTERNATIONAL_WITHDRAWAL: Visit your online account. In your **Account Overview**, accept and deny this payment.', 'paypal-for-woocommerce');
                            break;
                        case 'OTHER':
                            $pending_reason_text = __('No additional specific reason can be provided. For more information about this captured payment, visit your account online or contact PayPal.', 'paypal-for-woocommerce');
                            break;
                        case 'PENDING_REVIEW':
                            $pending_reason_text = __('PENDING_REVIEW: The captured payment is pending manual review.', 'paypal-for-woocommerce');
                            break;
                        case 'RECEIVING_PREFERENCE_MANDATES_MANUAL_ACTION':
                            $pending_reason_text = __('RECEIVING_PREFERENCE_MANDATES_MANUAL_ACTION: The payee has not yet set up appropriate receiving preferences for their account. For more information about how to accept or deny this payment, visit your account online. This reason is typically offered in scenarios such as when the currency of the captured payment is different from the primary holding currency of the payee.', 'paypal-for-woocommerce');
                            break;
                        case 'REFUNDED':
                            $pending_reason_text = __('REFUNDED: The captured funds were refunded.', 'paypal-for-woocommerce');
                            break;
                        case 'TRANSACTION_APPROVED_AWAITING_FUNDING':
                            $pending_reason_text = __('TRANSACTION_APPROVED_AWAITING_FUNDING: The payer must send the funds for this captured payment. This code generally appears for manual EFTs.', 'paypal-for-woocommerce');
                            break;
                        case 'UNILATERAL':
                            $pending_reason_text = __('UNILATERAL: The payee does not have a PayPal account.', 'paypal-for-woocommerce');
                            break;
                        case 'VERIFICATION_REQUIRED':
                            $pending_reason_text = __('VERIFICATION_REQUIRED: The payee\'s PayPal account is not verified.', 'paypal-for-woocommerce');
                            break;
                        case 'none':
                        default:
                            $pending_reason_text = __('No pending reason provided.', 'paypal-for-woocommerce');
                            break;
                    }
                    if ($payment_status === 'PENDING') {
                        $order->update_status('on-hold', sprintf(__('Payment via %s Pending. PayPal Pending reason: %s.', 'paypal-for-woocommerce'), $this->title, $pending_reason_text));
                    }
                    if ($payment_status === 'DECLINED') {
                        $order->update_status('failed', sprintf(__('Payment via %s declined. PayPal declined reason: %s.', 'paypal-for-woocommerce'), $this->title, $pending_reason_text));
                    }
                    break;
                case 'PARTIALLY_REFUNDED' :
                    $order->update_status('on-hold');
                    $order->add_order_note(sprintf(__('Payment via %s partially refunded. PayPal reason: %s.', 'paypal-for-woocommerce'), $this->title, $pending_reason));
                case 'REFUNDED' :
                    $order->update_status('refunded');
                    $order->add_order_note(sprintf(__('Payment via %s refunded. PayPal reason: %s.', 'paypal-for-woocommerce'), $this->title, $pending_reason));
                case 'FAILED' :
                    $order->update_status('failed', sprintf(__('Payment via %s failed. PayPal reason: %s.', 'paypal-for-woocommerce'), $this->title, $pending_reason));
                    break;
                case 'VOIDED' :
                    $order->update_status('cancelled', sprintf(__('Payment via %s Voided.', 'paypal-for-woocommerce'), $this->title));
                    break;
                default:
                    break;
            endswitch;
            return;
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_get_paypal_order_details($paypal_order_id) {
        try {
            $args = array(
                'timeout' => 60,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking' => true,
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                //'body' => array(),
                'cookies' => array()
            );
            $this->api_response = $this->api_request->request($this->paypal_order_api . $paypal_order_id, $args, 'get_order');
            $this->api_response = json_decode(json_encode($this->api_response), true);
            return $this->api_response;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_get_authorized_payment($authorization_id) {
        try {
            $args = array(
                'timeout' => 60,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking' => true,
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                //'body' => array(),
                'cookies' => array()
            );
            $this->api_response = $this->api_request->request($this->auth . $authorization_id, $args, 'get_authorized');
            $this->api_response = json_decode(json_encode($this->api_response), true);
            return $this->api_response;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_void_authorized_payment_admin($order, $order_data) {
        try {
            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            $note_to_payer = isset($order_data['angelleye_ppcp_note_to_buyer_void']) ? $order_data['angelleye_ppcp_note_to_buyer_void'] : '';
            if (strlen($note_to_payer) > 255) {
                $note_to_payer = substr($note_to_payer, 0, 252) . '...';
            }
            $authorization_id = angelleye_ppcp_get_post_meta($order, '_auth_transaction_id');
            $args = array(
                'method' => 'POST',
                'timeout' => 60,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking' => true,
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'cookies' => array()
            );
            if (!empty($note_to_payer)) {
                $void_arg = array(
                    'note_to_payer' => $note_to_payer,
                );
                $args['body'] = $void_arg;
            }
            $this->api_response = $this->api_request->request($this->auth . $authorization_id . '/void', $args, 'void_authorized');
            $this->api_response = json_decode(json_encode($this->api_response), true);
            if (!empty($this->api_response['id'])) {
                $payment_status = isset($this->api_response['status']) ? $this->api_response['status'] : '';
                $this->angelleye_ppcp_update_woo_order_status($order_id, $payment_status, $pending_reason = '');
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_capture_authorized_payment_admin($order, $order_data) {
        try {
            if ($order === false) {
                return false;
            }
            $note_to_payer = isset($order_data['angelleye_ppcp_note_to_buyer_capture']) ? $order_data['angelleye_ppcp_note_to_buyer_capture'] : '';
            if (strlen($note_to_payer) > 255) {
                $note_to_payer = substr($note_to_payer, 0, 252) . '...';
            }
            $final_capture = false;
            if (isset($order_data['additionalCapture']) && 'no' === $order_data['additionalCapture']) {
                $final_capture = true;
            }
            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
            $capture_arg = array(
                'amount' =>
                array(
                    'value' => isset($order_data['_angelleye_ppcp_regular_price']) ? angelleye_ppcp_round($order_data['_angelleye_ppcp_regular_price'], $decimals) : '',
                    'currency_code' => version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency(),
                ),
                'note_to_payer' => $note_to_payer,
                'invoice_id' => $this->invoice_prefix . str_replace("#", "", $order->get_order_number()),
                'final_capture' => $final_capture,
            );
            $body_request = angelleye_ppcp_remove_empty_key($capture_arg);
            $authorization_id = angelleye_ppcp_get_post_meta($order, '_auth_transaction_id');
            $args = array(
                'method' => 'POST',
                'timeout' => 60,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking' => true,
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'body' => $body_request,
                'cookies' => array()
            );
            $this->api_response = $this->api_request->request($this->auth . $authorization_id . '/capture', $args, 'capture_authorized');
            if (!empty($this->api_response['id'])) {
                
            } else {
                $error_email_notification_param = array(
                    'request' => 'capture_authorized',
                    'order_id' => $order_id
                );
                $error_message = $this->angelleye_ppcp_get_readable_message($this->api_response, $error_email_notification_param);
                if (!empty($error_message)) {
                    $order->add_order_note('Error Message : ' . $error_message);
                }
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_refund_order_admin($order, $order_data) {
        try {
            if ($order === false) {
                return false;
            }
            $note_to_payer = isset($order_data['angelleye_ppcp_note_to_buyer_capture']) ? $order_data['angelleye_ppcp_note_to_buyer_capture'] : '';
            if (strlen($note_to_payer) > 255) {
                $note_to_payer = substr($note_to_payer, 0, 252) . '...';
            }
            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
            $reason = !empty($reason) ? $reason : 'Refund';
            $body_request['note_to_payer'] = $reason;
            $body_request['amount'] = array(
                'value' => isset($order_data['_angelleye_ppcp_refund_price']) ? angelleye_ppcp_round($order_data['_angelleye_ppcp_refund_price'], $decimals) : '',
                'currency_code' => $order->get_currency()
            );
            $body_request = angelleye_ppcp_remove_empty_key($body_request);
            $transaction_id = isset($order_data['angelleye_ppcp_refund_data']) ? $order_data['angelleye_ppcp_refund_data'] : '';
            $args = array(
                'method' => 'POST',
                'timeout' => 60,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking' => true,
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'body' => $body_request,
                'cookies' => array()
            );
            $this->api_response = $this->api_request->request($this->paypal_refund_api . $transaction_id . '/refund', $args, 'refund_order');
            if (isset($this->api_response['status'])) {
                
            } else {
                $error_email_notification_param = array(
                    'request' => 'refund_order',
                    'order_id' => $order_id
                );
                $error_message = $this->angelleye_ppcp_get_readable_message($this->api_response, $error_email_notification_param);
                if (!empty($error_message)) {
                    $order->add_order_note('Error Message : ' . $error_message);
                }
                return false;
            }
            return true;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
            return new WP_Error('error', $ex->getMessage());
        }
    }

}
