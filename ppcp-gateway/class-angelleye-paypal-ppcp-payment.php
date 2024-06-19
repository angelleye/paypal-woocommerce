<?php

defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Payment {

    use WC_PPCP_Pre_Orders_Trait;

    public $is_sandbox;
    protected static $_instance = null;
    public AngellEYE_PayPal_PPCP_Request $api_request;
    public $api_response;
    public $api_log;
    public $checkout_details;
    public $payment_complete = 0;
    public $paypal_transaction = 0;
    public $setting_obj;
    public WC_AngellEYE_PayPal_PPCP_Payment_Token $ppcp_payment_token;
    public WC_Gateway_PPCP_AngellEYE_Subscriptions_Helper $subscriptions_helper;
    public $enable_tokenized_payments;
    public $setup_tokens_url;
    public $payment_tokens_url;
    public $angelleye_ppcp_used_payment_method;
    public $is_auto_capture_auth;
    public $token_url;
    public $order_url;
    public $paypal_order_api;
    public $paypal_refund_api;
    public $auth;
    public $generate_token_url;
    public $generate_id_token;
    public $merchant_id;
    public $partner_client_id;
    public $title;
    public $brand_name;
    public $paymentaction;
    public $paymentstatus;
    public $landing_page;
    public $payee_preferred;
    public $invoice_prefix;
    public $soft_descriptor;
    public $advanced_card_payments;
    public $checkout_disable_smart_button;
    public $error_email_notification;
    public $enable_paypal_checkout_page;
    public $send_items;
    public $AVSCodes;
    public $CVV2Codes;
    public $client_token;
    public $ppcp_error_handler;
    public $avs_code;
    public $cvv_code;
    public $response_code;
    public $payment_advice_code;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->angelleye_ppcp_load_class();
        $this->is_sandbox = 'yes' === $this->setting_obj->get('testmode', 'no');
        if ($this->is_sandbox) {
            $this->token_url = 'https://api-m.sandbox.paypal.com/v1/oauth2/token';
            $this->order_url = 'https://api-m.sandbox.paypal.com/v2/checkout/orders/';
            $this->paypal_order_api = 'https://api-m.sandbox.paypal.com/v2/checkout/orders/';
            $this->paypal_refund_api = 'https://api-m.sandbox.paypal.com/v2/payments/captures/';
            $this->auth = 'https://api-m.sandbox.paypal.com/v2/payments/authorizations/';
            $this->generate_token_url = 'https://api-m.sandbox.paypal.com/v1/identity/generate-token';
            $this->generate_id_token = 'https://api-m.sandbox.paypal.com/v1/oauth2/token';
            $this->setup_tokens_url = 'https://api-m.sandbox.paypal.com/v3/vault/setup-tokens';
            $this->payment_tokens_url = 'https://api-m.sandbox.paypal.com/v3/vault/payment-tokens';
            $this->merchant_id = $this->setting_obj->get('sandbox_merchant_id', '');
            $this->partner_client_id = PAYPAL_PPCP_SANDBOX_PARTNER_CLIENT_ID;
        } else {
            $this->token_url = 'https://api-m.paypal.com/v1/oauth2/token';
            $this->order_url = 'https://api-m.paypal.com/v2/checkout/orders/';
            $this->paypal_order_api = 'https://api-m.paypal.com/v2/checkout/orders/';
            $this->paypal_refund_api = 'https://api-m.paypal.com/v2/payments/captures/';
            $this->auth = 'https://api-m.paypal.com/v2/payments/authorizations/';
            $this->generate_token_url = 'https://api-m.paypal.com/v1/identity/generate-token';
            $this->generate_id_token = 'https://api-m.paypal.com/v1/oauth2/token';
            $this->setup_tokens_url = 'https://api-m.paypal.com/v3/vault/setup-tokens';
            $this->payment_tokens_url = 'https://api-m.paypal.com/v3/vault/payment-tokens';
            $this->merchant_id = $this->setting_obj->get('live_merchant_id', '');
            $this->partner_client_id = PAYPAL_PPCP_PARTNER_CLIENT_ID;
        }
        $this->title = $this->setting_obj->get('title', sprintf('%s - Built by Angelleye', AE_PPCP_NAME));
        $this->brand_name = $this->setting_obj->get('brand_name', get_bloginfo('name'));
        $this->paymentaction = $this->setting_obj->get('paymentaction', 'capture');
        $this->paymentstatus = $this->setting_obj->get('paymentstatus', 'wc-default');
        $this->landing_page = $this->setting_obj->get('landing_page', 'NO_PREFERENCE');
        $this->payee_preferred = 'yes' === $this->setting_obj->get('payee_preferred', 'no');
        $this->invoice_prefix = $this->setting_obj->get('invoice_prefix', 'WC-PPCP');
        $this->soft_descriptor = $this->setting_obj->get('soft_descriptor', substr(get_bloginfo('name'), 0, 21));
        $this->advanced_card_payments = 'yes' === $this->setting_obj->get('enable_advanced_card_payments', 'no');
        $this->checkout_disable_smart_button = 'yes' === $this->setting_obj->get('checkout_disable_smart_button', 'no');
        $this->error_email_notification = 'yes' === $this->setting_obj->get('error_email_notification', 'yes');
        $this->enable_paypal_checkout_page = 'yes' === $this->setting_obj->get('enable_paypal_checkout_page', 'yes');
        $this->send_items = 'yes' === $this->setting_obj->get('send_items', 'yes');
        $this->enable_tokenized_payments = 'yes' === $this->setting_obj->get('enable_tokenized_payments', 'no');
        $this->avs_code = $this->ppcp_error_handler->avs_code;
        $this->cvv_code = $this->ppcp_error_handler->cvv_code;
        $this->response_code = $this->ppcp_error_handler->response_code;
        $this->payment_advice_code = $this->ppcp_error_handler->payment_advice_code;
        $this->is_auto_capture_auth = false;
        if ($this->paymentaction === 'authorize') {
            $this->is_auto_capture_auth = 'yes' === $this->setting_obj->get('auto_capture_auth', 'yes');
        }
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
            if (!class_exists('WC_AngellEYE_PayPal_PPCP_Payment_Token')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/ppcp-payment-token/class-angelleye-paypal-ppcp-payment-token.php';
            }
            if (!class_exists('WC_Gateway_PPCP_AngellEYE_Subscriptions_Helper')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/subscriptions/class-wc-gateway-ppcp-angelleye-subscriptions-helper.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Error')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-error.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Front_Action')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-front-action.php';
            }
            $this->api_log = AngellEYE_PayPal_PPCP_Log::instance();
            $this->setting_obj = WC_Gateway_PPCP_AngellEYE_Settings::instance();
            $this->api_request = AngellEYE_PayPal_PPCP_Request::instance();
            $this->ppcp_payment_token = WC_AngellEYE_PayPal_PPCP_Payment_Token::instance();
            $this->subscriptions_helper = WC_Gateway_PPCP_AngellEYE_Subscriptions_Helper::instance();
            $this->ppcp_error_handler = AngellEYE_PayPal_PPCP_Error::instance();
            add_filter('angelleye_ppcp_add_payment_source', array($this, 'angelleye_ppcp_add_payment_source'), 10, 2);
            
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    /**
     * If the user logged in status has been changed between the before and after checkout process
     * then we need to refresh the nonce on form to process subsequent requests
     * @param array $response
     * @return array
     */
    private function add_nonce_in_response(array $response): array {
        $current_login_status = is_user_logged_in();
        if (AngellEYE_PayPal_PPCP_Front_Action::$is_user_logged_in_before_checkout != $current_login_status) {
            $response['nonce'] = wp_create_nonce('woocommerce-process_checkout');
        }
        return $response;
    }

    public function angelleye_ppcp_create_order_request($woo_order_id = null) {
        try {
            $return_response = [];
            if (angelleye_ppcp_get_order_total($woo_order_id) === 0) {
                $wc_notice = __('Sorry, your session has expired.', 'paypal-for-woocommerce');
                wc_add_notice($wc_notice);
                wp_send_json_error($wc_notice);
                exit();
            }
            $this->paymentaction = apply_filters('angelleye_ppcp_paymentaction', $this->paymentaction, $woo_order_id);
            if ($woo_order_id == null) {
                $cart = $this->angelleye_ppcp_get_details_from_cart();
            } else {
                $cart = $this->angelleye_ppcp_get_details_from_order($woo_order_id);
            }
            $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
            $reference_id = wc_generate_order_key();
            AngellEye_Session_Manager::set('reference_id', $reference_id);
            $payment_method = wc_clean(!empty($_POST['angelleye_ppcp_payment_method_title']) ? $_POST['angelleye_ppcp_payment_method_title'] : '');
            $payment_method_id = wc_clean(!empty($_POST['payment_method']) ? $_POST['payment_method'] : '');
            if (!empty($payment_method_id)) {
                AngellEye_Session_Manager::set('payment_method_id', $payment_method_id);
            }
            if (!empty($payment_method)) {
                $payment_method_title = angelleye_ppcp_get_payment_method_title($payment_method);
                AngellEye_Session_Manager::set('payment_method_title', $payment_method_title);
                AngellEye_Session_Manager::set('used_payment_method', $payment_method);
                $this->angelleye_ppcp_used_payment_method = $payment_method;
            } elseif (!empty($_POST['angelleye_ppcp_cc_payment_method_title'])) {
                $payment_method_title = angelleye_ppcp_get_payment_method_title(wc_clean($_POST['angelleye_ppcp_cc_payment_method_title']));
                AngellEye_Session_Manager::set('payment_method_title', $payment_method_title);
                AngellEye_Session_Manager::set('used_payment_method', 'card');
                $this->angelleye_ppcp_used_payment_method = 'card';
            }
            $intent = ($this->paymentaction === 'capture') ? 'CAPTURE' : 'AUTHORIZE';
            $currency_code = apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($woo_order_id), $cart['order_total']);
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
                            'currency_code' => $currency_code,
                            'value' => $cart['order_total'],
                            'breakdown' => array()
                        )
                    ),
                ),
            );
            $country_code = '';
            $full_name = '';
            if ($woo_order_id != null) {
                $order = wc_get_order($woo_order_id);
                $body_request['purchase_units'][0]['invoice_id'] = $this->invoice_prefix . str_replace("#", "", $order->get_order_number());
                $body_request['purchase_units'][0]['custom_id'] = apply_filters('angelleye_ppcp_custom_id', $this->invoice_prefix . str_replace("#", "", $order->get_order_number()), $order);
            } else {
                $body_request['purchase_units'][0]['invoice_id'] = $reference_id;
                $body_request['purchase_units'][0]['custom_id'] = apply_filters('angelleye_ppcp_custom_id', $reference_id, '');
            }
            $country_code = "";
            $full_name = "";
            if (isset($cart['billing_address'])) {
                $country_code = isset($cart['billing_address']['country']) ? $cart['billing_address']['country'] : "";
                $first_name = isset($cart['billing_address']['first_name']) ? $cart['billing_address']['first_name'] : "";
                $last_name = isset($cart['billing_address']['last_name']) ? $cart['billing_address']['last_name'] : "";
                $full_name = $first_name . ' ' . $last_name;
            }
            $body_request['purchase_units'][0]['invoice_id'] = $reference_id;
            $body_request['purchase_units'][0]['custom_id'] = apply_filters('angelleye_ppcp_custom_id', $reference_id, '');
            if (strtolower($payment_method) == 'ideal') {
                $body_request['payment_source'] = ['ideal' => ["country_code" => strtoupper($country_code), 'name' => trim($full_name)]];
                $body_request['processing_instruction'] = 'ORDER_COMPLETE_ON_PAYMENT_APPROVAL';
            }
            $body_request['purchase_units'][0]['soft_descriptor'] = angelleye_ppcp_get_value('soft_descriptor', $this->soft_descriptor);
            $body_request['purchase_units'][0]['payee']['merchant_id'] = $this->merchant_id;
            if ($this->send_items === true) {
                if (isset($cart['total_item_amount']) && $cart['total_item_amount'] > 0) {
                    $body_request['purchase_units'][0]['amount']['breakdown']['item_total'] = array(
                        'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($woo_order_id), $cart['total_item_amount']),
                        'value' => $cart['total_item_amount'],
                    );
                }
                if (isset($cart['shipping']) && $cart['shipping'] > 0) {
                    $body_request['purchase_units'][0]['amount']['breakdown']['shipping'] = array(
                        'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($woo_order_id), $cart['shipping']),
                        'value' => $cart['shipping'],
                    );
                }
                if (isset($cart['ship_discount_amount']) && $cart['ship_discount_amount'] > 0) {
                    $body_request['purchase_units'][0]['amount']['breakdown']['shipping_discount'] = array(
                        'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($woo_order_id), angelleye_ppcp_round($cart['ship_discount_amount'], $decimals)),
                        'value' => angelleye_ppcp_round($cart['ship_discount_amount'], $decimals),
                    );
                }
                if (isset($cart['order_tax']) && $cart['order_tax'] > 0) {
                    $body_request['purchase_units'][0]['amount']['breakdown']['tax_total'] = array(
                        'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($woo_order_id), $cart['order_tax']),
                        'value' => $cart['order_tax'],
                    );
                }
                if (isset($cart['discount']) && $cart['discount'] > 0) {
                    $body_request['purchase_units'][0]['amount']['breakdown']['discount'] = array(
                        'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($woo_order_id), $cart['discount']),
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
                                'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($woo_order_id), $order_items['amount']),
                                'value' => $order_items['amount'],
                            ),
                        );
                    }
                }
            }
            if ($woo_order_id != null) {
                $order = wc_get_order($woo_order_id);
                $order->update_meta_data('_paypal_reference_id', $reference_id);
                $angelleye_ppcp_payment_method_title = AngellEye_Session_Manager::get('payment_method_title', false);
                if (!empty($angelleye_ppcp_payment_method_title)) {
                    $order->set_payment_method_title($angelleye_ppcp_payment_method_title);
                }
                $payment_method_id = AngellEye_Session_Manager::get('payment_method_id', false);
                if (!empty($payment_method_id)) {
                    $order->set_payment_method($payment_method_id);
                    // set transaction id as blank as previous checkout request with
                    // other payment gateway might have added a transaction id
                    $order->set_transaction_id('');
                }
                $angelleye_ppcp_used_payment_method = AngellEye_Session_Manager::get('used_payment_method', false);
                if (!empty($angelleye_ppcp_used_payment_method)) {
                    $order->update_meta_data('_angelleye_ppcp_used_payment_method', $angelleye_ppcp_used_payment_method);
                }
                $order->save();
                if ($order->has_shipping_address()) {
                    $shipping_first_name = $order->get_shipping_first_name();
                    $shipping_last_name = $order->get_shipping_last_name();
                    $shipping_address_1 = $order->get_shipping_address_1();
                    $shipping_address_2 = $order->get_shipping_address_2();
                    $shipping_city = $order->get_shipping_city();
                    $shipping_state = $order->get_shipping_state();
                    $shipping_postcode = $order->get_shipping_postcode();
                    $shipping_country = $order->get_shipping_country();
                } else {
                    $shipping_first_name = $order->get_billing_first_name();
                    $shipping_last_name = $order->get_billing_last_name();
                    $shipping_address_1 = $order->get_billing_address_1();
                    $shipping_address_2 = $order->get_billing_address_2();
                    $shipping_city = $order->get_billing_city();
                    $shipping_state = $order->get_billing_state();
                    $shipping_postcode = $order->get_billing_postcode();
                    $shipping_country = $order->get_billing_country();
                }
                $shipping_country = strtoupper($shipping_country);
                if ($order->needs_shipping_address() || WC()->cart->needs_shipping()) {
                    if (!empty($shipping_first_name) && !empty($shipping_last_name)) {
                        $body_request['purchase_units'][0]['shipping']['name']['full_name'] = $shipping_first_name . ' ' . $shipping_last_name;
                    }
                    // TODO Confirm about this fix
                    if (!empty($shipping_address_1) && !empty($shipping_country)) {
                        AngellEye_Session_Manager::set('is_shipping_added', 'yes');
                        $body_request['purchase_units'][0]['shipping']['address'] = array(
                            'address_line_1' => $shipping_address_1,
                            'address_line_2' => $shipping_address_2,
                            'admin_area_2' => $shipping_city,
                            'admin_area_1' => $shipping_state,
                            'postal_code' => $shipping_postcode,
                            'country_code' => $shipping_country,
                        );
                    } else {
                        $body_request['application_context']['shipping_preference'] = 'GET_FROM_FILE';
                    }
                }
            } else {
                if (true === WC()->cart->needs_shipping()) {
                    if (!empty($cart['shipping_address']['first_name']) && !empty($cart['shipping_address']['last_name'])) {
                        $body_request['purchase_units'][0]['shipping']['name']['full_name'] = $cart['shipping_address']['first_name'] . ' ' . $cart['shipping_address']['last_name'];
                    }
                    if (!empty($cart['shipping_address']['address_1']) && !empty($cart['shipping_address']['city']) && !empty($cart['shipping_address']['country'])) {
                        $body_request['purchase_units'][0]['shipping']['address'] = array(
                            'address_line_1' => $cart['shipping_address']['address_1'],
                            'address_line_2' => $cart['shipping_address']['address_2'],
                            'admin_area_2' => $cart['shipping_address']['city'],
                            'admin_area_1' => $cart['shipping_address']['state'],
                            'postal_code' => $cart['shipping_address']['postcode'],
                            'country_code' => strtoupper($cart['shipping_address']['country']),
                        );
                        AngellEye_Session_Manager::set('is_shipping_added', 'yes');
                    }
                }
            }
            if ($this->angelleye_ppcp_used_payment_method === 'venmo') {
                if (is_user_logged_in()) {
                    if (!empty($cart['shipping_address']['first_name']) && !empty($cart['shipping_address']['last_name'])) {
                        $body_request['purchase_units'][0]['shipping']['name']['full_name'] = $cart['shipping_address']['first_name'] . '' . $cart['shipping_address']['last_name'];
                    }
                    if (!empty($cart['shipping_address']['address_1']) && !empty($cart['shipping_address']['city']) && !empty($cart['shipping_address']['country'])) {
                        $body_request['purchase_units'][0]['shipping']['address'] = array(
                            'address_line_1' => $cart['shipping_address']['address_1'],
                            'address_line_2' => $cart['shipping_address']['address_2'],
                            'admin_area_2' => $cart['shipping_address']['city'],
                            'admin_area_1' => $cart['shipping_address']['state'],
                            'postal_code' => $cart['shipping_address']['postcode'],
                            'country_code' => strtoupper($cart['shipping_address']['country']),
                        );
                        AngellEye_Session_Manager::set('is_shipping_added', 'yes');
                    }
                }
            }
            $body_request = $this->angelleye_ppcp_set_payer_details($woo_order_id, $body_request);
            if (angelleye_ppcp_is_save_payment_method($this->enable_tokenized_payments)) {
                $body_request = $this->angelleye_ppcp_add_payment_source_parameter($body_request);
            } elseif ($this->angelleye_ppcp_used_payment_method === 'venmo') {
                if (isset($body_request['purchase_units'][0]['shipping']['address'])) {
                    $body_request['payment_source']['venmo']['experience_context']['shipping_preference'] = 'SET_PROVIDED_ADDRESS';
                } else {
                    $body_request['payment_source']['venmo']['experience_context']['shipping_preference'] = $this->angelleye_ppcp_shipping_preference();
                }
                unset($body_request['application_context']);
            }
            $body_request = angelleye_ppcp_remove_empty_key($body_request);
            $args = array(
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'body' => $body_request
            );
            $args = apply_filters('angelleye_ppcp_request_args', $args, 'create_order', $woo_order_id);
            $this->api_response = $this->api_request->request($this->paypal_order_api, $args, 'create_order');
            if (ob_get_length()) {
                ob_end_clean();
            }
            if (!empty($this->api_response['status'])) {
                $return_response = $this->add_nonce_in_response($return_response);
                // Add currency code and total for the apple pay orders
                $response = $this->ae_get_updated_checkout_payment_data((!empty($order) ? $order : null));
                $return_response = array_merge($return_response, $response);
                $return_response['currencyCode'] = $this->api_response['purchase_units'][0]['amount']['currency_code'];
                $return_response['totalAmount'] = $this->api_response['purchase_units'][0]['amount']['value'];
                $return_response['orderID'] = $this->api_response['id'];
                if (!empty($order)) {
                    $order->update_meta_data('_paypal_order_id', $this->api_response['id']);
                    $order->save_meta_data();
                }
                wp_send_json($return_response, 200);
                exit();
            } else {
                $error_email_notification_param = array(
                    'request' => 'create_order',
                    'order_id' => $woo_order_id
                );
                $errorMessage = $this->angelleye_ppcp_get_readable_message($this->api_response, $error_email_notification_param);
                !empty($order) && $order->add_order_note($errorMessage);
                if (str_contains($errorMessage, 'CURRENCY_NOT_SUPPORTED')) {
                    wp_send_json_error(sprintf(__('Currency code (%s) is not currently supported.', 'paypal-for-woocommerce'), $currency_code));
                } else {
                    wp_send_json_error(__('We were unable to process your order, please try again with same or other payment method(s).', 'paypal-for-woocommerce'));
                }
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    /**
     * @param null $order
     * @return array
     */
    public function ae_get_updated_checkout_payment_data($order = null) {
        $details = [];
        $totalAmount = 0;
        $shippingRequired = false;
        if (!empty($order)) {
            $details = $this->getOrderLineItems($order);
            $totalAmount = $order->get_total('');
            $shippingRequired = $order->needs_shipping_address();
        } elseif (isset(WC()->cart)) {
            $totalAmount = WC()->cart->get_total('');
            $shippingRequired = WC()->cart->needs_shipping();
            $details = $this->getCartLineItems();
        }
        return [
            'currencyCode' => get_woocommerce_currency(),
            'totalAmount' => $totalAmount,
            'lineItems' => $details,
            'shippingRequired' => $shippingRequired,
            'isSubscriptionRequired' => $this->isSubscriptionRequired($order)
        ];
    }

    public function isSubscriptionRequired($order = null): bool {
        if (!empty($order) && class_exists('WC_Subscriptions_Order')) {
            return WC_Subscriptions_Order::order_contains_subscription($order);
        }
        if (class_exists('WC_Subscriptions_Cart')) {
            return WC_Subscriptions_Cart::cart_contains_subscription();
        }
        return false;
    }

    public function angelleye_ppcp_get_discount_amount_from_cart_item() {
        $cart_item_discount_amount = 0;
        $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
        foreach (WC()->cart->cart_contents as $cart_item_key => $values) {
            $amount = angelleye_ppcp_round($values['line_subtotal'] / $values['quantity'], $decimals);
            if ($amount < 0) {
                $cart_item_discount_amount += angelleye_ppcp_round($amount * $values['quantity'], $decimals);
            }
        }
        foreach (WC()->cart->get_fees() as $cart_item_key => $fee_values) {
            if ($fee_values->amount < 0) {
                $cart_item_discount_amount += angelleye_ppcp_round($fee_values->amount * 1, $decimals);
            }
        }
        return absint($cart_item_discount_amount);
    }

    public function angelleye_ppcp_get_details_from_cart() {
        try {
            $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
            $rounded_total = $this->angelleye_ppcp_get_rounded_total_in_cart();
            $discounts = WC()->cart->get_cart_discount_total();
            $cart_item_discount_amount = $this->angelleye_ppcp_get_discount_amount_from_cart_item();
            $discounts = $cart_item_discount_amount + $discounts;
            // TODO Verify why this has been added here and in HPOS branch??
            $cart_contents_total = $rounded_total;
            $order_tax = WC()->cart->tax_total + WC()->cart->shipping_tax_total;
            $shipping_total = WC()->cart->shipping_total;
            $cart_total = WC()->cart->total;
            $items = $this->angelleye_ppcp_get_paypal_line_items_from_cart();
            if (function_exists("scd_get_bool_option")) {
                $multicurrency_payment = scd_get_bool_option('scd_general_options', 'multiCurrencyPayment');
            } else {
                $scd_option = get_option('scd_general_options');
                $multicurrency_payment = ( isset($scd_option['multiCurrencyPayment']) && $scd_option['multiCurrencyPayment'] == true ) ? true : false;
            }
            if (function_exists("scd_get_target_currency") && $multicurrency_payment) {
                $target_currency = scd_get_target_currency();
                // Get the woocommerce base currency
                $base_currency = get_option('woocommerce_currency');
                $rate = scd_get_conversion_rate_origine($target_currency, $base_currency);
                $rate_c = scd_get_conversion_rate($base_currency, $target_currency);
                $cart_contents_total = $cart_contents_total * $rate_c;
                $order_tax = $order_tax * $rate_c;
                $shipping_total = $shipping_total * $rate_c;
                $cart_total = $cart_total * $rate_c;
                $rounded_total = angelleye_ppcp_round($rounded_total * $rate_c, $decimals);
                $discounts = $discounts * $rate_c;
                foreach ($items as $key => $item) {
                    $items[$key]['amount'] = angelleye_ppcp_round($item['amount'] * $rate_c, $decimals);
                }
            }
            /**
             * SCM Multicurrency plugin compatibility END
             */
            $details = array(
                'total_item_amount' => angelleye_ppcp_round($cart_contents_total, $decimals),
                'order_tax' => angelleye_ppcp_round($order_tax, $decimals),
                'shipping' => angelleye_ppcp_round($shipping_total, $decimals),
                'items' => $items,
                'shipping_address' => $this->angelleye_ppcp_get_address_from_customer(),
                'email' => WC()->customer->get_billing_email(),
            );
            if ((float) $details['total_item_amount'] == 0) {
                $details['total_item_amount'] = WC()->cart->fee_total;
            }
            return $this->angelleye_ppcp_get_details($details, $discounts, $rounded_total, $cart_total);
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    /**
     * This method returns the line items for an order on order pay page
     * @param WC_Order $order
     * @return array
     */
    public function getOrderLineItems(WC_Order $order): array {
        $lineItems = [];
        $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
        foreach ($order->get_items() as $item) {
            $lineItems[] = [
                'label' => $item->get_name(''),
                'amount' => angelleye_ppcp_round($order->get_line_total($item), $decimals)
            ];
        }

        if ($order->needs_shipping_address()) {
            $lineItems[] = [
                'label' => 'Shipping',
                'amount' => angelleye_ppcp_round($order->get_shipping_total(''), $decimals)
            ];
        }

        foreach ($order->get_fees() as $item) {
            $amount = $order->get_line_total($item);
            $lineItems[] = [
                'label' => $item->get_name(''),
                'amount' => angelleye_ppcp_round($amount, $decimals)
            ];
        }

        $tax = $order->get_total_tax('');
        if ($tax > 0) {
            $lineItems[] = [
                'label' => 'Tax',
                'amount' => angelleye_ppcp_round($tax, $decimals)
            ];
        }

        return $lineItems;
    }

    public function getCartLineItems(): array {
        $lineItems = [];
        $details = $this->angelleye_ppcp_get_details_from_cart();
        // Trigger this call so that hooked cart action/filters are executed before calculating the line items etc
        WC()->cart->calculate_totals();
        $cart = WC()->cart->get_cart();
        $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
        foreach ($cart as $cart_item) {
            $lineItems[] = [
                'label' => $cart_item['data']->get_title(),
                'amount' => angelleye_ppcp_round($cart_item['data']->get_price(), $decimals)
            ];
        }

        if (WC()->cart->needs_shipping()) {
            $lineItems[] = [
                'label' => 'Shipping',
                'amount' => angelleye_ppcp_round($details['shipping'], $decimals)
            ];
        }

        foreach (WC()->cart->get_fees() as $item) {
            $lineItems[] = [
                'label' => html_entity_decode(wc_trim_string($item->name ?: __('Fee', 'paypal-for-woocommerce'), 127), ENT_NOQUOTES, 'UTF-8'),
                'amount' => angelleye_ppcp_round($item->amount, $decimals)
            ];
        }

        $tax = WC()->cart->get_total_tax();
        if ($tax > 0) {
            $lineItems[] = [
                'label' => 'Tax',
                'amount' => $details['order_tax']
            ];
        }
        return $lineItems;
    }

    public function angelleye_ppcp_get_number_of_decimal_digits() {
        try {
            return $this->angelleye_ppcp_is_currency_supports_zero_decimal() ? 0 : 2;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_is_currency_supports_zero_decimal() {
        try {
            return in_array(get_woocommerce_currency(), array('HUF', 'JPY', 'TWD'));
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_get_rounded_total_in_cart() {
        try {
            $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
            $rounded_total = 0;
            foreach (WC()->cart->cart_contents as $cart_item_key => $values) {
                $amount = angelleye_ppcp_round($values['line_subtotal'] / $values['quantity'], $decimals);
                if ($amount > 0) {
                    $rounded_total += angelleye_ppcp_round($amount * $values['quantity'], $decimals);
                }
            }
            foreach (WC()->cart->get_fees() as $cart_item_key => $fee_values) {
                if ($fee_values->amount > 0) {
                    $rounded_total += angelleye_ppcp_round($fee_values->amount * 1, $decimals);
                }
            }
            return angelleye_ppcp_round($rounded_total, $decimals);
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
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
                $product = $values['data'];
                $name = $product->get_name();
                $sku = $product->get_sku();
                $category = $product->needs_shipping() ? 'PHYSICAL_GOODS' : 'DIGITAL_GOODS';
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
                $desc = !empty($desc) ? $desc : '';
                if (strlen($desc) > 127) {
                    $desc = substr($desc, 0, 124) . '...';
                }
                $desc = strip_shortcodes($desc);
                $desc = str_replace("\n", " ", $desc);
                $desc = preg_replace('/\s+/', ' ', $desc);
                if ($amount > 0) {
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
            }
            foreach (WC()->cart->get_fees() as $cart_item_key => $fee_values) {
                $amount = AngellEYE_Gateway_Paypal::number_format($fee_values->amount);
                if ($amount > 0) {
                    $fee_item = array(
                        'name' => html_entity_decode(wc_trim_string($fee_values->name ? $fee_values->name : __('Fee', 'paypal-for-woocommerce'), 127), ENT_NOQUOTES, 'UTF-8'),
                        'description' => '',
                        'quantity' => 1,
                        'amount' => AngellEYE_Gateway_Paypal::number_format($fee_values->amount),
                    );
                    $items[] = $fee_item;
                }
            }
            return $items;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_get_address_from_customer() {
        try {
            $customer = WC()->customer;
            if ($customer->get_shipping_address() || $customer->get_shipping_address_2()) {
                return array(
                    'first_name' => $customer->get_shipping_first_name(),
                    'last_name' => $customer->get_shipping_last_name(),
                    'company' => '',
                    'address_1' => $customer->get_shipping_address(),
                    'address_2' => $customer->get_shipping_address_2(),
                    'city' => $customer->get_shipping_city(),
                    'state' => $customer->get_shipping_state(),
                    'postcode' => $customer->get_shipping_postcode(),
                    'country' => $customer->get_shipping_country(),
                    'phone' => $customer->get_billing_phone(),
                );
            } else {
                return array(
                    'first_name' => $customer->get_billing_first_name(),
                    'last_name' => $customer->get_billing_last_name(),
                    'company' => '',
                    'address_1' => $customer->get_billing_address_1(),
                    'address_2' => $customer->get_billing_address_2(),
                    'city' => $customer->get_billing_city(),
                    'state' => $customer->get_billing_state(),
                    'postcode' => $customer->get_billing_postcode(),
                    'country' => $customer->get_billing_country(),
                    'phone' => $customer->get_billing_phone(),
                );
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
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
                if ($diff > 0.000001 && 0.0 !== (float) $diff) {
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
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
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

    public function angelleye_ppcp_application_context($return_url = false) {
        $smart_button = AngellEYE_PayPal_PPCP_Smart_Button::instance();
        $application_context = array(
            'brand_name' => $this->brand_name,
            'locale' => 'en-US',
            'landing_page' => $this->landing_page,
            'shipping_preference' => $this->angelleye_ppcp_shipping_preference(),
            'user_action' => $smart_button->angelleye_ppcp_is_skip_final_review() ? 'PAY_NOW' : 'CONTINUE'
        );
        if ($this->checkout_disable_smart_button === true || $return_url === true) {
            $application_context['return_url'] = add_query_arg(array('angelleye_ppcp_action' => 'regular_capture', 'utm_nooverride' => '1'), untrailingslashit(WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action')));
            $application_context['cancel_url'] = add_query_arg(array('angelleye_ppcp_action' => 'regular_cancel', 'utm_nooverride' => '1'), untrailingslashit(WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action')));
        }

        return $application_context;
    }

    public function angelleye_ppcp_shipping_preference() {
        $shipping_preference = 'GET_FROM_FILE';
        $page = null;
        if (isset($_GET) && !empty($_GET['from'])) {
            $page = $_GET['from'];
        } elseif (is_cart() && !WC()->cart->is_empty()) {
            $page = 'cart';
        } elseif (is_checkout() || is_checkout_pay_page()) {
            $page = 'checkout';
        } elseif (is_product()) {
            $page = 'product';
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
            case 'pay_page':
                $shipping_preference = WC()->cart->needs_shipping() ? 'SET_PROVIDED_ADDRESS' : 'NO_SHIPPING';
                break;
        }
        return $shipping_preference;
    }

    public function angelleye_ppcp_set_payer_details($woo_order_id, $body_request) {
        if ($woo_order_id != null) {
            $order = wc_get_order($woo_order_id);
            $first_name = $order->get_billing_first_name();
            $last_name = $order->get_billing_last_name();
            $billing_phone = $order->get_billing_phone();
            if (!empty($order->get_billing_email())) {
                $body_request['payer']['email_address'] = $order->get_billing_email();
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
            if (!empty($order->get_billing_first_name())) {
                $body_request['payer']['name']['given_name'] = $order->get_billing_first_name();
            }
            if (!empty($order->get_billing_last_name())) {
                $body_request['payer']['name']['surname'] = $order->get_billing_last_name();
            }
            if (!empty($order->get_billing_address_1()) && !empty($order->get_billing_city()) && !empty($order->get_billing_state()) && !empty($order->get_billing_postcode()) && !empty($order->get_billing_country())) {
                $body_request['payer']['address'] = array(
                    'address_line_1' => $order->get_billing_address_1(),
                    'address_line_2' => $order->get_billing_address_2(),
                    'admin_area_2' => $order->get_billing_city(),
                    'admin_area_1' => $order->get_billing_state(),
                    'postal_code' => $order->get_billing_postcode(),
                    'country_code' => strtoupper($order->get_billing_country()),
                );
            }
        } else {
            $customer = WC()->customer;
            $first_name = $customer->get_billing_first_name();
            $last_name = $customer->get_billing_last_name();
            $address_1 = $customer->get_billing_address_1();
            $address_2 = $customer->get_billing_address_2();
            $city = $customer->get_billing_city();
            $state = $customer->get_billing_state();
            $postcode = $customer->get_billing_postcode();
            $country = strtoupper($customer->get_billing_country());
            $email_address = $customer->get_billing_email();
            $billing_phone = $customer->get_billing_phone();
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

    /**
     * Get the Order detail using PayPal API
     *
     * We already have another method angelleye_ppcp_get_checkout_details but the response returned by that is not proper
     * due to decoding the object and other places its being used
     * @param $paypal_order_id
     * @return false|mixed
     */
    public function angelleye_ppcp_get_paypal_order($paypal_order_id) {
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
            $api_response = $this->api_request->request($this->paypal_order_api . $paypal_order_id, $args, 'get_order');
            $api_response = json_decode(json_encode($api_response), true);
            if (isset($api_response['id'])) {
                return $api_response;
            }
            $this->api_log->log("Unable to find the PayPal order: " . $paypal_order_id, 'error');
            $this->api_log->log(print_r($api_response, true), 'error');
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
        return false;
    }

    /**
     * Validates PayPal order response to see if order capture API call was triggered for the order
     * @param $order_details
     * @return bool
     */
    public function is_paypal_order_capture_triggered($order_details): bool {
        $purchase_units = $order_details['purchase_units'] ?? [];
        foreach ($purchase_units as $purchase_unit) {
            if (isset($purchase_unit['payments']['captures'])) {
                return true;
            }
        }
        return false;
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
            AngellEye_Session_Manager::set('paypal_order_id', $paypal_order_id);
            AngellEye_Session_Manager::set('paypal_transaction_details', $this->api_response);
            return $this->api_response;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function get_set_payment_method_title_from_session($woo_order_id = null) {
        $order = wc_get_order($woo_order_id);
        $angelleye_ppcp_payment_method_title = AngellEye_Session_Manager::get('payment_method_title');
        $payment_method_id = AngellEye_Session_Manager::get('payment_method_id');
        if (!empty($payment_method_id)) {
            $order->set_payment_method($payment_method_id);
            $order->save();
        }
        if (!empty($angelleye_ppcp_payment_method_title) && !empty($woo_order_id)) {
            $order->set_payment_method_title($angelleye_ppcp_payment_method_title);
            $order->save();
        } else {
            $angelleye_ppcp_payment_method_title = $this->title;
        }
        return $angelleye_ppcp_payment_method_title;
    }

    public function get_payment_method_title_for_order($woo_order_id = null) {
        if (!is_object($woo_order_id)) {
            $order = wc_get_order($woo_order_id);
        }
        if (!is_a($order, 'WC_Order')) {
            return;
        }
        return $order->get_payment_method_title();
    }

    public function angelleye_ppcp_order_capture_request($woo_order_id, $need_to_update_order = true) {
        try {
            $order = wc_get_order($woo_order_id);
            if ($need_to_update_order) {
                $this->angelleye_ppcp_update_order($order);
            }
            $paypal_order_id = AngellEye_Session_Manager::get('paypal_order_id', false);
            $args = array(
                'method' => 'POST',
                'timeout' => 60,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking' => true,
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
            );
            $this->api_response = $this->api_request->request($this->paypal_order_api . $paypal_order_id . '/capture', $args, 'capture_order');
            $angelleye_ppcp_payment_method_title = $this->get_set_payment_method_title_from_session($woo_order_id);
            if (!empty($angelleye_ppcp_payment_method_title)) {
                $order->set_payment_method_title($angelleye_ppcp_payment_method_title);
            }
            $payment_method_id = AngellEye_Session_Manager::get('payment_method_id', false);
            if (!empty($payment_method_id)) {
                $order->set_payment_method($payment_method_id);
            }
            $angelleye_ppcp_used_payment_method = AngellEye_Session_Manager::get('used_payment_method');
            if (!empty($angelleye_ppcp_used_payment_method)) {
                $order->update_meta_data('_angelleye_ppcp_used_payment_method', $angelleye_ppcp_used_payment_method);
            }
            $order->save();
            if (isset($this->api_response['id']) && !empty($this->api_response['id'])) {
                do_action('angelleye_ppcp_order_data', $this->api_response, $woo_order_id);
                $order->update_meta_data('_paypal_order_id', $this->api_response['id']);
                $order->save_meta_data();
                if ($this->api_response['status'] == 'COMPLETED') {
                    if (isset($this->api_response['payment_source']['card']['attributes']['vault']['status']) && 'APPROVED' === $this->api_response['payment_source']['card']['attributes']['vault']['status']) {
                        $setup_token = $this->api_response['payment_source']['card']['attributes']['vault']['setup_token'];
                        $body_request = array();
                        $body_request['payment_source']['token'] = array(
                            'id' => wc_clean($setup_token),
                            'type' => 'SETUP_TOKEN'
                        );
                        $args = array(
                            'method' => 'POST',
                            'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                            'body' => $body_request
                        );
                        $api_response = $this->api_request->request($this->payment_tokens_url, $args, 'create_payment_token');
                        if (!empty($api_response['id'])) {
                            $customer_id = $api_response['customer']['id'] ?? '';
                            if (isset($customer_id) && !empty($customer_id)) {
                                $this->ppcp_payment_token->angelleye_ppcp_add_paypal_generated_customer_id($customer_id, $this->is_sandbox);
                            }
                            $this->subscriptions_helper->angelleye_ppcp_wc_save_payment_token($woo_order_id, $api_response);
                        }
                    } elseif (isset($this->api_response['payment_source']['card']['attributes']['vault']['status']) && 'VAULTED' === $this->api_response['payment_source']['card']['attributes']['vault']['status']) {
                        $customer_id = $this->api_response['payment_source']['card']['attributes']['vault']['customer']['id'] ?? '';
                        if (isset($customer_id) && !empty($customer_id)) {
                            $this->ppcp_payment_token->angelleye_ppcp_add_paypal_generated_customer_id($customer_id, $this->is_sandbox);
                        }
                        $this->subscriptions_helper->angelleye_ppcp_wc_save_payment_token($woo_order_id, $this->api_response);
                    } elseif (isset($this->api_response['payment_source']['paypal']['attributes']['vault']['status']) && 'VAULTED' === $this->api_response['payment_source']['paypal']['attributes']['vault']['status']) {
                        $customer_id = $this->api_response['payment_source']['paypal']['attributes']['vault']['customer']['id'] ?? '';
                        if (isset($customer_id) && !empty($customer_id)) {
                            $this->ppcp_payment_token->angelleye_ppcp_add_paypal_generated_customer_id($customer_id, $this->is_sandbox);
                        }
                        $this->subscriptions_helper->angelleye_ppcp_wc_save_payment_token($woo_order_id, $this->api_response);
                    } elseif (isset($this->api_response['payment_source']['venmo']['attributes']['vault']['status']) && 'VAULTED' === $this->api_response['payment_source']['venmo']['attributes']['vault']['status']) {
                        $customer_id = $this->api_response['payment_source']['venmo']['attributes']['vault']['customer']['id'] ?? '';
                        if (isset($customer_id) && !empty($customer_id)) {
                            $this->ppcp_payment_token->angelleye_ppcp_add_paypal_generated_customer_id($customer_id, $this->is_sandbox);
                        }
                        $this->subscriptions_helper->angelleye_ppcp_wc_save_payment_token($woo_order_id, $this->api_response);
                    } elseif (isset($this->api_response['payment_source']['apple_pay']['attributes']['vault']['status']) && 'VAULTED' === $this->api_response['payment_source']['apple_pay']['attributes']['vault']['status']) {
                        $customer_id = $this->api_response['payment_source']['apple_pay']['attributes']['vault']['customer']['id'] ?? '';
                        if (isset($customer_id) && !empty($customer_id)) {
                            $this->ppcp_payment_token->angelleye_ppcp_add_paypal_generated_customer_id($customer_id, $this->is_sandbox);
                        }
                        $this->subscriptions_helper->angelleye_ppcp_wc_save_payment_token($woo_order_id, $this->api_response);
                    }
                    $payment_source = $this->api_response['payment_source'] ?? '';
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
                    foreach ($this->api_response['purchase_units'] as $captures_key => $captures) {
                        
                        $processor_response = isset($this->api_response['purchase_units'][$captures_key]['payments']['captures']['0']['processor_response']) ? $this->api_response['purchase_units'][$captures_key]['payments']['captures']['0']['processor_response'] : '';
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
                            if (isset($this->response_code[$processor_response['response_code']])) {
                                $response_code .= ' : ' . $this->response_code[$processor_response['response_code']];
                            }
                            $order->add_order_note($response_code);
                        }
                        $currency_code = isset($this->api_response['purchase_units'][$captures_key]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['currency_code']) ? $this->api_response['purchase_units'][$captures_key]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['currency_code'] : '';
                        $value = isset($this->api_response['purchase_units'][$captures_key]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['value']) ? $this->api_response['purchase_units'][$captures_key]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['value'] : '';
                        if (!empty($value)) {
                            $this->paypal_transaction = $this->paypal_transaction + $value;
                        }
                        $transaction_id = isset($this->api_response['purchase_units'][$captures_key]['payments']['captures']['0']['id']) ? $this->api_response['purchase_units'][$captures_key]['payments']['captures']['0']['id'] : '';
                        $seller_protection = isset($this->api_response['purchase_units'][$captures_key]['payments']['captures']['0']['seller_protection']['status']) ? $this->api_response['purchase_units'][$captures_key]['payments']['captures']['0']['seller_protection']['status'] : '';
                        $payment_status = isset($this->api_response['purchase_units'][$captures_key]['payments']['captures']['0']['status']) ? $this->api_response['purchase_units'][$captures_key]['payments']['captures']['0']['status'] : '';
                        $order->update_meta_data('_payment_status', $payment_status);
                        $order->add_order_note(sprintf(__('%s Transaction ID: %s', 'paypal-for-woocommerce'), 'PayPal', $transaction_id));
                        $order->add_order_note(sprintf(__('Payment via %s: %s.', 'paypal-for-woocommerce'), $order->get_payment_method_title(), ucfirst(strtolower($payment_status))));
                        $order->add_order_note('Seller Protection Status: ' . angelleye_ppcp_readable($seller_protection));
                        $payment_status_reason = isset($this->api_response['purchase_units'][$captures_key]['payments']['captures']['0']['status_details']['reason']) ? $this->api_response['purchase_units'][$captures_key]['payments']['captures']['0']['status_details']['reason'] : '';
                        $this->angelleye_ppcp_payment_status_woo_order_note($woo_order_id, $payment_status, $payment_status_reason);
                        if ($payment_status == 'COMPLETED') {
                            $this->payment_complete = $this->payment_complete + 1;
                        }
                    }
                    if ($this->paypal_transaction > 0) {
                        $order->update_meta_data('_paypal_fee', $this->paypal_transaction);
                        $order->update_meta_data('_paypal_transaction_fee', $this->paypal_transaction);
                        $order->update_meta_data('_paypal_fee_currency_code', $currency_code);
                    }
                    if (count($this->api_response['purchase_units']) === 1) {
                        if ($payment_status == 'COMPLETED') {
                            $order->payment_complete($transaction_id);
                            $order->add_order_note(sprintf(__('Payment via %s: %s.', 'paypal-for-woocommerce'), $order->get_payment_method_title(), ucfirst(strtolower($payment_status))));
                        } else {
                            $payment_status_reason = isset($this->api_response['purchase_units']['0']['payments']['captures']['0']['status_details']['reason']) ? $this->api_response['purchase_units']['0']['payments']['captures']['0']['status_details']['reason'] : '';
                            $this->angelleye_ppcp_update_woo_order_status($woo_order_id, $payment_status, $payment_status_reason);
                        }
                    }
                    if (!empty($processor_response['payment_advice_code'])) {
                        $payment_advice_code = __('Payment Advice Codes Result', 'paypal-for-woocommerce');
                        $payment_advice_code .= "\n";
                        $payment_advice_code .= $processor_response['payment_advice_code'];
                        if (isset($this->payment_advice_code[$processor_response['payment_advice_code']])) {
                            $payment_advice_code .= ' : ' . $this->payment_advice_code[$processor_response['payment_advice_code']];
                        }
                        $order->add_order_note($payment_advice_code);
                    }
                    $currency_code = $this->api_response['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['currency_code'] ?? '';
                    $value = $this->api_response['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['value'] ?? '';
                    $order->update_meta_data('_paypal_fee', $value);
                    $order->update_meta_data('_paypal_transaction_fee', $value);
                    $order->update_meta_data('_paypal_fee_currency_code', $currency_code);
                    $transaction_id = $this->api_response['purchase_units']['0']['payments']['captures']['0']['id'] ?? '';
                    $seller_protection = $this->api_response['purchase_units']['0']['payments']['captures']['0']['seller_protection']['status'] ?? '';
                    $payment_status = $this->api_response['purchase_units']['0']['payments']['captures']['0']['status'] ?? '';
                    // Update the transaction id for the order, For pending orders we need to save transaction id as well
                    $order->set_transaction_id($transaction_id);
                    if ($payment_status == 'COMPLETED') {
                        add_filter('woocommerce_payment_complete_order_status', function ($payment_status, $woo_order_id) {
                            return $this->get_preferred_order_status($payment_status, $woo_order_id);
                        }, 20, 2);
                        $order->payment_complete($transaction_id);
                        $order->add_order_note(sprintf(__('Payment via %s: %s.', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title, ucfirst(strtolower($payment_status))));
                    } elseif ($payment_status === 'DECLINED') {
                        $order->update_status('failed', sprintf(__('Payment via %s declined.', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title));
                        $order->save();
                        wc_add_notice(__('Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'paypal-for-woocommerce'), 'error');
                        return false;
                    } else {
                        if ($this->payment_complete === count($this->api_response['purchase_units'])) {
                            $order->payment_complete($transaction_id);
                        } else {
                            $order->update_status('on-hold');
                        }
                        $payment_status_reason = $this->api_response['purchase_units']['0']['payments']['captures']['0']['status_details']['reason'] ?? '';
                        $this->angelleye_ppcp_update_woo_order_status($woo_order_id, $payment_status, $payment_status_reason);
                    }
                    $order->update_meta_data('_payment_status', $payment_status);
                    $order->save();
                    $order->add_order_note(sprintf(__('%s Capture Transaction ID: %s', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title, $transaction_id));
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
                wc_add_notice(__('This payment was unable to be processed successfully. Please try again with another payment method.', 'paypal-for-woocommerce'), 'error');
                $order->add_order_note($error_message);
                return false;
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_update_order($order) {
        try {
            $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
            $patch_request = array();
            $reference_id = AngellEye_Session_Manager::get('reference_id');
            $order_id = $order->get_id();
            $paypal_order_id = AngellEye_Session_Manager::get('paypal_order_id');
            if (empty($paypal_order_id)) {
                $paypal_order_id = angelleye_ppcp_get_post_meta($order_id, '_paypal_order_id');
                if (empty($paypal_order_id)) {
                    angelleye_session_expired_exception('_paypal_order_id missing in the update_order call.');
                }
            }
            // Note: This could create issue with old orders where we don't have reference_id
            if (empty($reference_id)) {
                $reference_id = angelleye_ppcp_get_post_meta($order_id, '_paypal_reference_id');
                if (empty($reference_id)) {
                    angelleye_session_expired_exception('_paypal_reference_id is missing in the update_order call.');
                }
            }
            $cart = $this->angelleye_ppcp_get_details_from_order($order_id);
            if (isset($cart['total_item_amount']) && $cart['total_item_amount'] > 0) {
                $update_amount_request['item_total'] = array(
                    'currency_code' => angelleye_ppcp_get_currency($order_id),
                    'value' => $cart['total_item_amount'],
                );
            }
            if (isset($cart['discount']) && $cart['discount'] > 0) {
                $update_amount_request['discount'] = array(
                    'currency_code' => angelleye_ppcp_get_currency($order_id),
                    'value' => $cart['discount'],
                );
            }
            if (isset($cart['shipping']) && $cart['shipping'] > 0) {
                $update_amount_request['shipping'] = array(
                    'currency_code' => angelleye_ppcp_get_currency($order_id),
                    'value' => $cart['shipping'],
                );
            }
            if (isset($cart['ship_discount_amount']) && $cart['ship_discount_amount'] > 0) {
                $update_amount_request['shipping_discount'] = array(
                    'currency_code' => angelleye_ppcp_get_currency($order_id),
                    'value' => angelleye_ppcp_round($cart['ship_discount_amount'], $decimals),
                );
            }
            if (isset($cart['order_tax']) && $cart['order_tax'] > 0) {
                $update_amount_request['tax_total'] = array(
                    'currency_code' => angelleye_ppcp_get_currency($order_id),
                    'value' => $cart['order_tax'],
                );
            }

            $purchase_units = array(
                'reference_id' => $reference_id,
                'soft_descriptor' => angelleye_ppcp_get_value('soft_descriptor', $this->soft_descriptor),
                'amount' =>
                array(
                    'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($order_id), $cart['order_total']),
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
                        'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($order_id), $cart['total_item_amount']),
                        'value' => $cart['total_item_amount'],
                    );
                }
                if (isset($cart['shipping']) && $cart['shipping'] > 0) {
                    $purchase_units['amount']['breakdown']['shipping'] = array(
                        'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($order_id), $cart['shipping']),
                        'value' => $cart['shipping'],
                    );
                }
                if (isset($cart['ship_discount_amount']) && $cart['ship_discount_amount'] > 0) {
                    $purchase_units['amount']['breakdown']['shipping_discount'] = array(
                        'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($order_id), angelleye_ppcp_round($cart['ship_discount_amount'], $decimals)),
                        'value' => angelleye_ppcp_round($cart['ship_discount_amount'], $decimals),
                    );
                }
                if (isset($cart['order_tax']) && $cart['order_tax'] > 0) {
                    $purchase_units['amount']['breakdown']['tax_total'] = array(
                        'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($order_id), $cart['order_tax']),
                        'value' => $cart['order_tax'],
                    );
                }
                if (isset($cart['discount']) && $cart['discount'] > 0) {
                    $purchase_units['amount']['breakdown']['discount'] = array(
                        'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($order_id), $cart['discount']),
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
                                'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($order_id), $order_items['amount']),
                                'value' => $order_items['amount'],
                            ),
                        );
                    }
                }
            }
            if ($order->has_shipping_address()) {
                $shipping_first_name = $order->get_shipping_first_name();
                $shipping_last_name = $order->get_shipping_last_name();
                $shipping_address_1 = $order->get_shipping_address_1();
                $shipping_address_2 = $order->get_shipping_address_2();
                $shipping_city = $order->get_shipping_city();
                $shipping_state = $order->get_shipping_state();
                $shipping_postcode = $order->get_shipping_postcode();
                $shipping_country = $order->get_shipping_country();
            } else {
                $shipping_first_name = $order->get_billing_first_name();
                $shipping_last_name = $order->get_billing_last_name();
                $shipping_address_1 = $order->get_billing_address_1();
                $shipping_address_2 = $order->get_billing_address_2();
                $shipping_city = $order->get_billing_city();
                $shipping_state = $order->get_billing_state();
                $shipping_postcode = $order->get_billing_postcode();
                $shipping_country = $order->get_billing_country();
            }
            $shipping_country = strtoupper($shipping_country);
            if ($order->needs_shipping_address() || WC()->cart->needs_shipping()) {
                if (!empty($shipping_first_name) && !empty($shipping_last_name)) {
                    $purchase_units['shipping']['name']['full_name'] = $shipping_first_name . ' ' . $shipping_last_name;
                }
                AngellEye_Session_Manager::set('is_shipping_added', 'yes');
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
            $intent = ($this->paymentaction === 'capture') ? 'CAPTURE' : 'AUTHORIZE';
            $patch_request[] = array(
                'op' => 'replace',
                'path' => "/intent",
                'value' => $intent
            );
            $patch_request = apply_filters('angelleye_ppcp_request_args', $patch_request, 'update_order', $order_id);
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
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');

            // Redirect the user to the checkout page in case order update fails
            if ($ex->getCode() == 302) {
                $this->api_log->log('UpdateOrder Request URL: ' . $this->paypal_order_api . $paypal_order_id);
                $this->api_log->log('UpdateOrder Request Body: ' . wc_print_r($args, true));
                // Commenting this as I've seen this creates an issue when someone starts the checkout process from
                // product page with Authorize intent, and on complete my order page if due to some reason this update
                // order function fails then on complete order action this tries to redirect the user to checkout page
                // during ajax call, and user starts seeing the "unexpected <" error.
                // and if its not ajax call, then they will be redirected to checkout page with
                // "session expired message", that creates issue reported in AHD-20796
                /* wc_add_notice(__('Sorry, your session has expired.', 'woocommerce'));
                  wp_redirect(wc_get_checkout_url()); */
            }
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
                'shipping' => angelleye_ppcp_round($order->get_shipping_total(), $decimals),
                'items' => $this->angelleye_ppcp_get_paypal_line_items_from_order($order),
            );
            if ((float) $details['total_item_amount'] == 0) {
                $details['total_item_amount'] = $order->get_total_fees();
            }
            $details = $this->angelleye_ppcp_get_details($details, $order->get_total_discount(), $rounded_total, $order->get_total());
            return $details;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
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
                if ($amount > 0) {
                    $rounded_total += angelleye_ppcp_round($amount * $values['qty'], $decimals);
                }
            }
            foreach ($order->get_fees() as $cart_item_key => $fee_values) {
                $amount = $order->get_line_total($fee_values);
                if ($amount > 0) {
                    $rounded_total += angelleye_ppcp_round($amount * 1, $decimals);
                }
            }
            return $rounded_total;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
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
                $product = $values->get_product();
                $name = $product->get_name();
                $sku = $product->get_sku();
                $category = $product->needs_shipping() ? 'PHYSICAL_GOODS' : 'DIGITAL_GOODS';
                if (is_object($product)) {
                    if ($product->is_type('variation') && is_a($product, 'WC_Product_Variation')) {
                        $desc = '';
                        $attributes = $product->get_attributes();
                        if (!empty($attributes) && is_array($attributes)) {
                            foreach ($attributes as $key => $value) {
                                $desc .= ' ' . ucwords(str_replace('pa_', '', $key)) . ': ' . $value;
                            }
                        }
                        $desc = trim($desc);
                    }
                }
                $product_name = !empty($name) ? $name : '';
                $product_name = apply_filters('angelleye_ppcp_product_name', $product_name, $product, $desc, $values);
                $product_name = wp_strip_all_tags($product_name);
                if (strlen($product_name) > 127) {
                    $product_name = substr($product_name, 0, 124) . '...';
                }
                if (strlen($desc) > 127) {
                    $desc = substr($desc, 0, 124) . '...';
                }
                $desc = strip_shortcodes($desc);
                $desc = str_replace("\n", " ", $desc);
                $desc = preg_replace('/\s+/', ' ', $desc);
                if ($amount > 0) {
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
            }
            foreach ($order->get_fees() as $fee_values) {
                $fee_item_name = $fee_values->get_name();
                $amount = $order->get_line_total($fee_values);
                if ($amount > 0) {
                    $item = array(
                        'name' => html_entity_decode(wc_trim_string($fee_item_name ? $fee_item_name : __('Fee', 'paypal-for-woocommerce'), 127), ENT_NOQUOTES, 'UTF-8'),
                        'description' => '',
                        'quantity' => 1,
                        'amount' => angelleye_ppcp_round($order->get_line_total($fee_values), $decimals)
                    );
                    $items[] = $item;
                }
            }
            return $items;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_refund_order($order_id, $amount, $reason, $transaction_id) {
        try {
            $order = wc_get_order($order_id);
            $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
            $angelleye_ppcp_payment_method_title = $this->get_payment_method_title_for_order($order_id);
            $reason = !empty($reason) ? $reason : 'Refund';
            $body_request['note_to_payer'] = $reason;
            $currency_code = apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($order_id), angelleye_ppcp_round($amount, $decimals));
            if (!empty($amount) && $amount > 0) {
                $body_request['amount'] = array(
                    'value' => angelleye_ppcp_round($amount, $decimals),
                    'currency_code' => $currency_code
                );
            }

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
                $gross_amount = $this->api_response['seller_payable_breakdown']['gross_amount']['value'] ?? '';
                $refund_transaction_id = $this->api_response['id'] ?? '';
                $order->add_order_note(
                        sprintf(__('Refunded %1$s - Refund ID: %2$s', 'paypal-for-woocommerce'), wc_price($gross_amount, array('currency' => $currency_code)), $refund_transaction_id)
                );
            } else if (isset($this->api_response['status']) && $this->api_response['status'] == "PENDING") {
                $gross_amount = $this->api_response['seller_payable_breakdown']['gross_amount']['value'] ?? '';
                $refund_transaction_id = $this->api_response['id'] ?? '';
                $pending_reason_text = $this->api_response['status_details']['reason'] ?? '';
                $order->add_order_note(sprintf(__('Payment via %s Pending. Pending reason: %s.', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title, $pending_reason_text));
                $order->add_order_note(
                        sprintf(__('Refund Amount %1$s - Refund ID: %2$s', 'paypal-for-woocommerce'), wc_price($gross_amount, array('currency' => $currency_code)), $refund_transaction_id)
                );
            } else {
                $this->paymentaction = apply_filters('angelleye_ppcp_paymentaction', $this->paymentaction, $order_id);
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
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
            return new WP_Error('error', $ex->getMessage());
        }
    }

    public function angelleye_ppcp_order_auth_request($woo_order_id) {
        try {
            $this->paymentaction = apply_filters('angelleye_ppcp_paymentaction', $this->paymentaction, $woo_order_id);
            $order = wc_get_order($woo_order_id);
            $this->angelleye_ppcp_update_order($order);
            $paypal_order_id = AngellEye_Session_Manager::get('paypal_order_id');
            $args = array(
                'method' => 'POST',
                'timeout' => 60,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking' => true,
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
            );
            $this->api_response = $this->api_request->request($this->paypal_order_api . $paypal_order_id . '/authorize', $args, 'authorize_order');
            $angelleye_ppcp_payment_method_title = $this->get_set_payment_method_title_from_session($woo_order_id);
            if (!empty($angelleye_ppcp_payment_method_title)) {
                $order->set_payment_method_title($angelleye_ppcp_payment_method_title);
            }
            $payment_method_id = AngellEye_Session_Manager::get('payment_method_id', false);
            if (!empty($payment_method_id)) {
                $order->set_payment_method($payment_method_id);
            }
            $angelleye_ppcp_used_payment_method = AngellEye_Session_Manager::get('used_payment_method');
            if (!empty($angelleye_ppcp_used_payment_method)) {
                $order->update_meta_data('_angelleye_ppcp_used_payment_method', $angelleye_ppcp_used_payment_method);
            }
            if (!empty($this->api_response['id'])) {
                if (isset($woo_order_id) && !empty($woo_order_id)) {
                    $order->update_meta_data('_paypal_order_id', $this->api_response['id']);
                }
                $payment_status = $this->api_response['purchase_units']['0']['payments']['authorizations']['0']['status'] ?? '';
                if ($this->api_response['status'] == 'COMPLETED' && strtolower($payment_status) != "denied") {
                    if (isset($this->api_response['payment_source']['card']['attributes']['vault']['status']) && 'APPROVED' === $this->api_response['payment_source']['card']['attributes']['vault']['status']) {
                        $setup_token = $this->api_response['payment_source']['card']['attributes']['vault']['setup_token'];
                        $body_request = array();
                        $body_request['payment_source']['token'] = array(
                            'id' => wc_clean($setup_token),
                            'type' => 'SETUP_TOKEN'
                        );
                        $args = array(
                            'method' => 'POST',
                            'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                            'body' => $body_request
                        );
                        $api_response = $this->api_request->request($this->payment_tokens_url, $args, 'create_payment_token');
                        if (!empty($api_response['id'])) {
                            $customer_id = $api_response['customer']['id'] ?? '';
                            if (isset($customer_id) && !empty($customer_id)) {
                                $this->ppcp_payment_token->angelleye_ppcp_add_paypal_generated_customer_id($customer_id, $this->is_sandbox);
                            }
                            $this->subscriptions_helper->angelleye_ppcp_wc_save_payment_token($woo_order_id, $api_response);
                        }
                    } elseif (isset($this->api_response['payment_source']['card']['attributes']['vault']['status']) && 'VAULTED' === $this->api_response['payment_source']['card']['attributes']['vault']['status']) {
                        $customer_id = $this->api_response['payment_source']['card']['attributes']['vault']['customer']['id'] ?? '';
                        if (isset($customer_id) && !empty($customer_id)) {
                            $this->ppcp_payment_token->angelleye_ppcp_add_paypal_generated_customer_id($customer_id, $this->is_sandbox);
                        }
                        $this->subscriptions_helper->angelleye_ppcp_wc_save_payment_token($woo_order_id, $this->api_response);
                    } elseif (isset($this->api_response['payment_source']['paypal']['attributes']['vault']['status']) && 'VAULTED' === $this->api_response['payment_source']['paypal']['attributes']['vault']['status']) {
                        $customer_id = $this->api_response['payment_source']['paypal']['attributes']['vault']['customer']['id'] ?? '';
                        if (isset($customer_id) && !empty($customer_id)) {
                            $this->ppcp_payment_token->angelleye_ppcp_add_paypal_generated_customer_id($customer_id, $this->is_sandbox);
                        }
                        $this->subscriptions_helper->angelleye_ppcp_wc_save_payment_token($woo_order_id, $this->api_response);
                    } elseif (isset($this->api_response['payment_source']['venmo']['attributes']['vault']['status']) && 'VAULTED' === $this->api_response['payment_source']['venmo']['attributes']['vault']['status']) {
                        $customer_id = $this->api_response['payment_source']['venmo']['attributes']['vault']['customer']['id'] ?? '';
                        if (isset($customer_id) && !empty($customer_id)) {
                            $this->ppcp_payment_token->angelleye_ppcp_add_paypal_generated_customer_id($customer_id, $this->is_sandbox);
                        }
                        $this->subscriptions_helper->angelleye_ppcp_wc_save_payment_token($woo_order_id, $this->api_response);
                    }
                    $payment_source = $this->api_response['payment_source'] ?? '';
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
                    $processor_response = $this->api_response['purchase_units']['0']['payments']['authorizations']['0']['processor_response'] ?? '';
                    if (!empty($processor_response['avs_code'])) {
                        $avs_response_order_note = __('Address Verification Result', 'paypal-for-woocommerce');
                        $avs_response_order_note .= "\n";
                        $avs_response_order_note .= $processor_response['avs_code'];
                        if (isset($this->avs_code[$processor_response['avs_code']][$payment_source['card']['brand']])) {
                            $avs_response_order_note .= ' : ' . $this->avs_code[$processor_response['avs_code']][$payment_source['card']['brand']];
                        }
                        $order->add_order_note($avs_response_order_note);
                    }
                    if (!empty($processor_response['cvv_code'])) {
                        $cvv2_response_code = __('Card Security Code Result', 'paypal-for-woocommerce');
                        $cvv2_response_code .= "\n";
                        $cvv2_response_code .= $processor_response['cvv_code'];
                        if (isset($this->cvv_code[$processor_response['cvv_code']][$payment_source['card']['brand']])) {
                            $cvv2_response_code .= ' : ' . $this->cvv_code[$processor_response['cvv_code']][$payment_source['card']['brand']];
                        }
                        $order->add_order_note($cvv2_response_code);
                    }
                    if (!empty($processor_response['response_code'])) {
                        $response_code = __('Processor response code Result', 'paypal-for-woocommerce');
                        $response_code .= "\n";
                        $response_code .= $processor_response['response_code'];
                        if (isset($this->response_code[$processor_response['response_code']])) {
                            $response_code .= ' : ' . $this->response_code[$processor_response['response_code']];
                        }
                        $order->add_order_note($response_code);
                    }
                    if (!empty($processor_response['payment_advice_code'])) {
                        $payment_advice_code = __('Payment Advice Codes Result', 'paypal-for-woocommerce');
                        $payment_advice_code .= "\n";
                        $payment_advice_code .= $processor_response['payment_advice_code'];
                        if (isset($this->payment_advice_code[$processor_response['payment_advice_code']])) {
                            $payment_advice_code .= ' : ' . $this->payment_advice_code[$processor_response['payment_advice_code']];
                        }
                        $order->add_order_note($payment_advice_code);
                    }
                    $currency_code = $this->api_response['purchase_units'][0]['payments']['authorizations'][0]['seller_receivable_breakdown']['paypal_fee']['currency_code'] ?? '';
                    $value = $this->api_response['purchase_units'][0]['payments']['authorizations'][0]['seller_receivable_breakdown']['paypal_fee']['value'] ?? '';
                    $order->update_meta_data('_paypal_fee', $value);
                    $order->update_meta_data('_paypal_transaction_fee', $value);
                    $order->update_meta_data('_paypal_fee_currency_code', $currency_code);
                    $transaction_id = $this->api_response['purchase_units']['0']['payments']['authorizations']['0']['id'] ?? '';
                    $seller_protection = $this->api_response['purchase_units']['0']['payments']['authorizations']['0']['seller_protection']['status'] ?? '';
                    $payment_status = $this->api_response['purchase_units']['0']['payments']['authorizations']['0']['status'] ?? '';
                    if ($payment_status == 'COMPLETED') {
                        $order->payment_complete($transaction_id);
                        $order->add_order_note(sprintf(__('Payment via %s: %s.', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title, ucfirst(strtolower($payment_status))));
                    } elseif ($payment_status === 'DECLINED') {
                        $order->update_status('failed', sprintf(__('Payment via %s declined.', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title));
                        wc_add_notice(__('Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'paypal-for-woocommerce'), 'error');
                        $order->save();
                        return false;
                    } else {
                        $payment_status_reason = $this->api_response['purchase_units']['0']['payments']['authorizations']['0']['status_details']['reason'] ?? '';
                        $this->angelleye_ppcp_update_woo_order_status($woo_order_id, $payment_status, $payment_status_reason);
                    }
                    $order->update_meta_data('_payment_status', $payment_status);
                    $order->set_transaction_id($transaction_id);
                    $order->update_meta_data('_auth_transaction_id', $transaction_id);
                    $order->update_meta_data('_paymentaction', 'authorize');
                    $order->add_order_note(sprintf(__('%s Authorization Transaction ID: %s', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title, $transaction_id));
                    $order->add_order_note('Seller Protection Status: ' . angelleye_ppcp_readable($seller_protection));
                    if (class_exists('AngellEYE_PayPal_PPCP_Admin_Action')) {
                        AngellEYE_PayPal_PPCP_Admin_Action::instance()->removeAutoCaptureHooks();
                    }
                    $order->update_status($this->get_preferred_order_status('on-hold', $woo_order_id));
                    if ($this->is_auto_capture_auth) {
                        $order->add_order_note(__('Payment authorized. Change payment status to processing or complete to capture funds.', 'paypal-for-woocommerce'));
                    }
                    $order->save();
                    return true;
                } else {
                    $response_code = __('Processor authorization status: ', 'paypal-for-woocommerce');
                    $response_code .= $payment_status;
                    $order->add_order_note($response_code);
                    $order->update_status('failed', sprintf(__('Payment via %s declined.', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title));
                    wc_add_notice(__('Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.'), 'error');
                    $order->save();
                    return false;
                }
            } else {
                $error_email_notification_param = array(
                    'request' => 'authorize_order',
                    'order_id' => $woo_order_id
                );
                $error_message = $this->angelleye_ppcp_get_readable_message($this->api_response, $error_email_notification_param);
                $order->add_order_note($error_message);
                wc_add_notice(__('This payment was unable to be processed successfully. Please try again with another payment method.', 'paypal-for-woocommerce'), 'error');
                return false;
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
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
            AngellEye_Session_Manager::set('paypal_transaction_details', $this->api_response);
            return $this->api_response;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_void_authorized_payment($authorization_id, $note_to_payer = null) {
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
            if (!empty($note_to_payer)) {
                $void_arg = array(
                    'note_to_payer' => $note_to_payer,
                );
                $args['body'] = $void_arg;
            }
            $this->api_response = $this->api_request->request($this->auth . $authorization_id . '/void', $args, 'void_authorized');
            $this->api_response = json_decode(json_encode($this->api_response), true);
            if (isset($this->api_response['status']) && strtolower($this->api_response['status']) == 'voided') {
                return $this->api_response;
            } else {
                $error_message = $this->angelleye_ppcp_get_readable_message($this->api_response);
                return new WP_Error('auth_void_error', $error_message);
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
            return new WP_Error('auth_void_exception', $ex->getMessage());
        }
    }

    public function angelleye_ppcp_capture_authorized_payment($woo_order_id) {
        try {
            $order = wc_get_order($woo_order_id);
            if ($order === false) {
                return false;
            }
            $order_data = [
                'ppcp_refund_amount' => $order->get_total(''),
                'refund_line_total' => []
            ];
            $line_items = $order->get_items(apply_filters('woocommerce_admin_order_item_types', 'line_item'));
            foreach ($line_items as $single_item) {
                $order_data['refund_line_total'][$single_item->get_id()] = $single_item->get_total();
            }

            $line_items_shipping = $order->get_items('shipping');
            if ($line_items_shipping) {
                foreach ($line_items_shipping as $single_item) {
                    $order_data['refund_line_total'][$single_item->get_id()] = $single_item->get_total();
                }
            }

            $line_items_shipping = $order->get_items('fee');
            if ($line_items_shipping) {
                foreach ($line_items_shipping as $single_item) {
                    $order_data['refund_line_total'][$single_item->get_id()] = $single_item->get_total();
                }
            }

            $this->angelleye_ppcp_capture_authorized_payment_admin($order, $order_data);
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_update_woo_order_data($paypal_order_id) {
        $this->checkout_details = $this->angelleye_ppcp_get_checkout_details($paypal_order_id);
        AngellEye_Session_Manager::set('paypal_transaction_details', $this->checkout_details);
        if (empty($this->checkout_details)) {
            return false;
        }
        if (!empty($this->checkout_details)) {
            $shipping_details = angelleye_ppcp_get_mapped_shipping_address($this->checkout_details);
            $billing_details = angelleye_ppcp_get_mapped_billing_address($this->checkout_details);
            angelleye_ppcp_update_customer_addresses_from_paypal($shipping_details, $billing_details);
        }
        $order_id = angelleye_ppcp_get_awaiting_payment_order_id();
        $order = wc_get_order($order_id);
        $this->paymentaction = apply_filters('angelleye_ppcp_paymentaction', $this->paymentaction, $order_id);
        $angelleye_ppcp_payment_method_title = $this->get_set_payment_method_title_from_session($order_id);
        if ($this->paymentaction === 'capture' && !empty($this->checkout_details->status) && $this->checkout_details->status == 'COMPLETED' && $order !== false) {
            $transaction_id = isset($this->checkout_details->purchase_units[0]->payments->captures[0]->id) ? $this->checkout_details->purchase_units['0']->payments->captures[0]->id : '';
            $seller_protection = $this->checkout_details->purchase_units[0]->payments->captures[0]->seller_protection->status ?? '';
            $payment_source = $this->checkout_details->payment_source ?? '';
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
            $processor_response = $this->checkout_details->purchase_units[0]->payments->captures[0]->processor_response ?? '';
            if (!empty($processor_response->avs_code)) {
                $avs_response_order_note = __('Address Verification Result', 'paypal-for-woocommerce');
                $avs_response_order_note .= "\n";
                $avs_response_order_note .= $processor_response->avs_code;
                if (isset($this->avs_code[$processor_response->avs_code][$payment_source->card->brand])) {
                    $avs_response_order_note .= ' : ' . $this->avs_code[$processor_response->avs_code][$payment_source->card->brand];
                }
                $order->add_order_note($avs_response_order_note);
            }
            if (!empty($processor_response->cvv_code)) {
                $cvv2_response_code = __('Card Security Code Result', 'paypal-for-woocommerce');
                $cvv2_response_code .= "\n";
                $cvv2_response_code .= $processor_response->cvv_code;
                if (isset($this->cvv_code[$processor_response->cvv_code][$payment_source->card->brand])) {
                    $cvv2_response_code .= ' : ' . $this->cvv_code[$processor_response->cvv_code][$payment_source->card->brand];
                }
                $order->add_order_note($cvv2_response_code);
            }
            if (!empty($processor_response->response_code)) {
                $response_code = __('Processor response code Result', 'paypal-for-woocommerce');
                $response_code .= "\n";
                $response_code .= $processor_response->response_code;
                if (isset($this->response_code[$processor_response->response_code])) {
                    $response_code .= ' : ' . $this->response_code[$processor_response->response_code];
                }
                $order->add_order_note($response_code);
            }
            $currency_code = isset($this->checkout_details->purchase_units[0]->payments->captures[0]->seller_receivable_breakdown->paypal_fee->currency_code) ? $this->checkout_details->purchase_units[0]->payments->captures[0]->seller_receivable_breakdown->paypal_fee->currency_code : '';
            $value = isset($this->checkout_details->purchase_units[0]->payments->captures[0]->seller_receivable_breakdown->paypal_fee->value) ? $this->checkout_details->purchase_units[0]->payments->captures[0]->seller_receivable_breakdown->paypal_fee->value : '';
            $order->update_meta_data('_paypal_fee', $value);
            $order->update_meta_data('_paypal_transaction_fee', $value);
            $order->update_meta_data('_paypal_fee_currency_code', $currency_code);
            $order->save();
            $payment_status = isset($this->checkout_details->purchase_units[0]->payments->captures[0]->status) ? $this->checkout_details->purchase_units[0]->payments->captures[0]->status : '';
            if (!empty($processor_response->payment_advice_code)) {
                $payment_advice_code = __('Payment Advice Codes Result', 'paypal-for-woocommerce');
                $payment_advice_code .= "\n";
                $payment_advice_code .= $processor_response->payment_advice_code;
                if (isset($this->payment_advice_code[$processor_response->payment_advice_code])) {
                    $payment_advice_code .= ' : ' . $this->payment_advice_code[$processor_response->payment_advice_code];
                }
                $order->add_order_note($payment_advice_code);
            }
            if ($payment_status == 'COMPLETED') {
                add_filter('woocommerce_payment_complete_order_status', function ($payment_status, $order_id) {
                    return $this->get_preferred_order_status($payment_status, $order_id);
                }, 20, 2);
                $order->payment_complete($transaction_id);
                $order->add_order_note(sprintf(__('Payment via %s: %s .', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title, ucfirst(strtolower($payment_status))));
            } else {
                $payment_status_reason = $this->checkout_details->purchase_units[0]->payments->captures[0]->status_details->reason ?? '';
                $this->angelleye_ppcp_update_woo_order_status($order_id, $payment_status, $payment_status_reason);
            }
            $order->add_order_note(sprintf(__('%s Capture Transaction ID: %s', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title, $transaction_id));
            $order->add_order_note('Seller Protection Status: ' . angelleye_ppcp_readable($seller_protection));
        } elseif ($this->paymentaction === 'authorize' && !empty($this->checkout_details->status) && $this->checkout_details->status == 'COMPLETED' && $order !== false) {
            $transaction_id = isset($this->checkout_details->purchase_units[0]->payments->authorizations[0]->id) ? $this->checkout_details->purchase_units['0']->payments->authorizations[0]->id : '';
            $seller_protection = $this->checkout_details->purchase_units[0]->payments->authorizations[0]->seller_protection->status ?? '';
            $payment_status = $this->checkout_details->purchase_units[0]->payments->authorizations[0]->status ?? '';
            $payment_status_reason = $this->checkout_details->purchase_units[0]->payments->authorizations[0]->status_details->reason ?? '';
            if (!empty($payment_status_reason)) {
                $order->add_order_note(sprintf(__('Payment via %s Pending. PayPal reason: %s.', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title, $payment_status_reason));
            }
            $order->set_transaction_id($transaction_id);
            $order->update_meta_data('_payment_status', $payment_status);
            $order->update_meta_data('_auth_transaction_id', $transaction_id);
            $order->update_meta_data('_paymentaction', $this->paymentaction);
            $order->add_order_note(sprintf(__('%s Authorization Transaction ID: %s', 'paypal-for-woocommerce'), $this->title, $transaction_id));
            $order->add_order_note('Seller Protection Status: ' . angelleye_ppcp_readable($seller_protection));
            if (class_exists('AngellEYE_PayPal_PPCP_Admin_Action')) {
                AngellEYE_PayPal_PPCP_Admin_Action::instance()->removeAutoCaptureHooks();
            }
            $order->update_status($this->get_preferred_order_status('on-hold', $order_id));
            $order->save();
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
    
    public function angelleye_ppcp_multi_account_paypalauthassertion($value) {
        if(!empty($value['merchant_id'])) {
            $merchant_id = $value['merchant_id'];
        } else {
            $merchant_id = $this->merchant_id;
        }
        
        $temp = array(
            "alg" => "none"
        );
        $returnData = base64_encode(json_encode($temp)) . '.';
        $temp = array(
            "iss" => $this->partner_client_id,
            "payer_id" => $merchant_id
        );
        $returnData .= base64_encode(json_encode($temp)) . '.';
        return $returnData;
    }

    public function angelleye_ppcp_get_generate_token() {
        try {
            $id_token_key = 'client_token' . (is_user_logged_in() ? '_user_' . get_current_user_id() : '');
            $body = null;
            if ($this->enable_tokenized_payments) {
                $paypal_generated_customer_id = $this->ppcp_payment_token->angelleye_ppcp_get_paypal_generated_customer_id($this->is_sandbox);
                if (!empty($paypal_generated_customer_id)) {
                    $body = [
                        'customer_id' => $paypal_generated_customer_id,
                    ];
                    $id_token_key .= '_customer_' . $paypal_generated_customer_id;
                }
            }
            $id_token_data = AngellEye_Session_Manager::get($id_token_key, null);
            if (!empty($id_token_data) && isset($id_token_data['expires_in'], $id_token_data['client_token'])) {
                // Make sure to keep the 15 mins threshold for token expiration, so that a customer has got min
                // 15 mins time to finish his checkout
                $next_15_mins = time() + 900;
                if ($id_token_data['expires_in'] > $next_15_mins) {
                    $this->client_token = $id_token_data['client_token'];
                    return $this->client_token;
                } else {
                    $this->api_log->log('Client token has been expired. ' . $id_token_key, 'error');
                }
            }

            $args = array(
                'method' => 'POST',
                'timeout' => 60,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking' => true,
                'headers' => ['Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()],
                'cookies' => [],
                'body' => $body
            );
            $response = $this->api_request->request($this->generate_token_url, $args, 'get_client_token');
            if (!empty($response['client_token'])) {
                $this->client_token = $response['client_token'];
                $response['expires_in'] = time() + intval($response['expires_in']);
                AngellEye_Session_Manager::set($id_token_key, $response);
                return $this->client_token;
            }
            $this->handle_generate_token_error_response($response);
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_get_generate_id_token() {
        try {
            $body = null;
            $id_token_key = 'id_token' . (is_user_logged_in() ? '_user_' . get_current_user_id() : '');
            if ($this->enable_tokenized_payments) {
                $paypal_generated_customer_id = $this->ppcp_payment_token->angelleye_ppcp_get_paypal_generated_customer_id($this->is_sandbox);
                if (!empty($paypal_generated_customer_id)) {
                    $body = ['target_customer_id' => $paypal_generated_customer_id];
                    $id_token_key .= '_customer_' . $paypal_generated_customer_id;
                }
            }

            $id_token_data = AngellEye_Session_Manager::get($id_token_key, null);
            if (!empty($id_token_data) && isset($id_token_data['expires_in'], $id_token_data['id_token'])) {
                // Make sure to keep the 15 mins threshold for token expiration, so that a customer has got min
                // 15 mins time to finish his checkout
                $next_15_mins = time() + 900;
                if ($id_token_data['expires_in'] > $next_15_mins) {
                    $this->client_token = $id_token_data['id_token'];
                    return $this->client_token;
                } else {
                    $this->api_log->log('ID token has been expired. ' . $id_token_key, 'error');
                }
            }
            $args = array(
                'method' => 'POST',
                'timeout' => 60,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking' => true,
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'cookies' => array(),
                'body' => $body
            );
            $response = $this->api_request->request($this->generate_id_token, $args, 'generate_id_token');
            if (!empty($response['id_token'])) {
                $response['expires_in'] = time() + intval($response['expires_in']);
                AngellEye_Session_Manager::set($id_token_key, $response);
                $this->client_token = $response['id_token'];
                return $this->client_token;
            }
            $this->handle_generate_token_error_response($response);
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    private function handle_generate_token_error_response($response) {
        if (isset($response['error'], $response['error_description']) && str_contains(strtolower($response['error_description']), 'no permissions')) {
            // display a notice to the users based on this flag and clear the flag only when call is successful
            update_option('ae_ppcp_account_reconnect_notice', 'generate_token_error');
        }
    }

    public function angelleye_ppcp_regular_create_order_request($woo_order_id = null, $return_url = true) {
        try {
            $return_response = [];
            if (angelleye_ppcp_get_order_total($woo_order_id) === 0) {
                $wc_notice = __('Sorry, your session has expired.', 'paypal-for-woocommerce');
                wc_add_notice($wc_notice);
                wp_send_json_error($wc_notice);
                exit();
            }
            if ($woo_order_id == null) {
                $cart = $this->angelleye_ppcp_get_details_from_cart();
            } else {
                $cart = $this->angelleye_ppcp_get_details_from_order($woo_order_id);
            }
            $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
            $reference_id = wc_generate_order_key();
            AngellEye_Session_Manager::set('reference_id', $reference_id);
            $intent = ($this->paymentaction === 'capture') ? 'CAPTURE' : 'AUTHORIZE';
            $body_request = array(
                'intent' => $intent,
                'application_context' => $this->angelleye_ppcp_application_context($return_url),
                'payment_method' => array('payee_preferred' => ($this->payee_preferred) ? 'IMMEDIATE_PAYMENT_REQUIRED' : 'UNRESTRICTED'),
                'purchase_units' =>
                array(
                    0 =>
                    array(
                        'reference_id' => $reference_id,
                        'amount' =>
                        array(
                            'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($woo_order_id), $cart['order_total']),
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
                        'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($woo_order_id), $cart['total_item_amount']),
                        'value' => $cart['total_item_amount'],
                    );
                }
                if (isset($cart['shipping']) && $cart['shipping'] > 0) {
                    $body_request['purchase_units'][0]['amount']['breakdown']['shipping'] = array(
                        'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($woo_order_id), $cart['shipping']),
                        'value' => $cart['shipping'],
                    );
                }
                if (isset($cart['ship_discount_amount']) && $cart['ship_discount_amount'] > 0) {
                    $body_request['purchase_units'][0]['amount']['breakdown']['shipping_discount'] = array(
                        'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($woo_order_id), angelleye_ppcp_round($cart['ship_discount_amount'], $decimals)),
                        'value' => angelleye_ppcp_round($cart['ship_discount_amount'], $decimals),
                    );
                }
                if (isset($cart['order_tax']) && $cart['order_tax'] > 0) {
                    $body_request['purchase_units'][0]['amount']['breakdown']['tax_total'] = array(
                        'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($woo_order_id), $cart['order_tax']),
                        'value' => $cart['order_tax'],
                    );
                }
                if (isset($cart['discount']) && $cart['discount'] > 0) {
                    $body_request['purchase_units'][0]['amount']['breakdown']['discount'] = array(
                        'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($woo_order_id), $cart['discount']),
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
                                'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($woo_order_id), $order_items['amount']),
                                'value' => $order_items['amount'],
                            ),
                        );
                    }
                }
            }
            if ($woo_order_id != null) {
                $order = wc_get_order($woo_order_id);
                if ($order->has_shipping_address()) {
                    $shipping_first_name = $order->get_shipping_first_name();
                    $shipping_last_name = $order->get_shipping_last_name();
                    $shipping_address_1 = $order->get_shipping_address_1();
                    $shipping_address_2 = $order->get_shipping_address_2();
                    $shipping_city = $order->get_shipping_city();
                    $shipping_state = $order->get_shipping_state();
                    $shipping_postcode = $order->get_shipping_postcode();
                    $shipping_country = $order->get_shipping_country();
                } else {
                    $shipping_first_name = $order->get_billing_first_name();
                    $shipping_last_name = $order->get_billing_last_name();
                    $shipping_address_1 = $order->get_billing_address_1();
                    $shipping_address_2 = $order->get_billing_address_2();
                    $shipping_city = $order->get_billing_city();
                    $shipping_state = $order->get_billing_state();
                    $shipping_postcode = $order->get_billing_postcode();
                    $shipping_country = $order->get_billing_country();
                }
                $shipping_country = strtoupper($shipping_country);
                if ($order->needs_shipping_address() || WC()->cart->needs_shipping()) {
                    if (!empty($shipping_first_name) && !empty($shipping_last_name)) {
                        $body_request['purchase_units'][0]['shipping']['name']['full_name'] = $shipping_first_name . ' ' . $shipping_last_name;
                    }
                    AngellEye_Session_Manager::set('is_shipping_added', 'yes');
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
                if (true === WC()->cart->needs_shipping()) {
                    if (is_user_logged_in()) {
                        if (!empty($cart['shipping_address']['first_name']) && !empty($cart['shipping_address']['last_name'])) {
                            $body_request['purchase_units'][0]['shipping']['name']['full_name'] = $cart['shipping_address']['first_name'] . ' ' . $cart['shipping_address']['last_name'];
                        }
                        if (!empty($cart['shipping_address']['address_1']) && !empty($cart['shipping_address']['city']) && !empty($cart['shipping_address']['country'])) {
                            $body_request['purchase_units'][0]['shipping']['address'] = array(
                                'address_line_1' => $cart['shipping_address']['address_1'],
                                'address_line_2' => $cart['shipping_address']['address_2'],
                                'admin_area_2' => $cart['shipping_address']['city'],
                                'admin_area_1' => $cart['shipping_address']['state'],
                                'postal_code' => $cart['shipping_address']['postcode'],
                                'country_code' => strtoupper($cart['shipping_address']['country']),
                            );
                            AngellEye_Session_Manager::set('is_shipping_added', 'yes');
                        }
                    }
                }
            }
            $body_request = $this->angelleye_ppcp_set_payer_details($woo_order_id, $body_request);
            if (angelleye_ppcp_is_save_payment_method($this->enable_tokenized_payments)) {
                $body_request = $this->angelleye_ppcp_add_payment_source_parameter($body_request);
            }
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
                if (!empty(isset($woo_order_id) && !empty($woo_order_id))) {
                    $order->update_meta_data('_paypal_order_id', $this->api_response['id']);
                    $order->save();
                }
                if (!empty($this->api_response['links'])) {
                    foreach ($this->api_response['links'] as $key => $link_result) {
                        if ('approve' === $link_result['rel'] || 'payer-action' === $link_result['rel']) {
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
                $error_message = $this->angelleye_ppcp_get_readable_message($this->api_response, $error_email_notification_param);
                if (!empty(isset($woo_order_id) && !empty($woo_order_id))) {
                    $order->add_order_note($error_message);
                }
                wc_add_notice(__('This payment was unable to be processed successfully. Please try again with another payment method.', 'paypal-for-woocommerce'), 'error');
                return array(
                    'result' => 'fail',
                    'redirect' => ''
                );
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_error_email_notification($error_email_notification_param, $error_message) {
        if (function_exists('WC')) {
            try {
                $mailer = WC()->mailer();
                $error_email_notify_subject = apply_filters('ae_ppec_error_email_subject', sprintf('%s Error Notification', AE_PPCP_NAME));
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
                $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
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
            $angelleye_ppcp_payment_method_title = $this->get_payment_method_title_for_order($orderid);
            $_paypal_order_id = angelleye_ppcp_get_post_meta($order, '_paypal_order_id');
            $respnse = $this->angelleye_ppcp_get_paypal_order($_paypal_order_id);
            $payment_status = isset($respnse['status']) ? $respnse['status'] : $payment_status;
            switch (strtoupper($payment_status)) :
                case 'COMPLETED' :
                    $order->payment_complete();
                    $order->add_order_note(sprintf(__('Payment via %s: %s.', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title, ucfirst(strtolower($payment_status))));
                    break;
                case 'DECLINED' :
                    $order->update_status('failed', sprintf(__('Payment via %s declined.', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title));
                    break;
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
                        $order->update_status('on-hold', sprintf(__('Payment via %s Pending. PayPal Pending reason: %s.', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title, $pending_reason_text));
                    }
                    if ($payment_status === 'DECLINED') {
                        $order->update_status('failed', sprintf(__('Payment via %s declined. PayPal declined reason: %s.', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title, $pending_reason_text));
                    }
                    break;
                case 'PARTIALLY_REFUNDED' :
                    $order->update_status('on-hold');
                    $order->add_order_note(sprintf(__('Payment via %s partially refunded. PayPal reason: %s.', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title, $pending_reason));
                    break;
                case 'REFUNDED' :
                    $order->update_status('refunded');
                    $order->add_order_note(sprintf(__('Payment via %s refunded. PayPal reason: %s.', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title, $pending_reason));
                    break;
                case 'FAILED' :
                    $order->update_status('failed', sprintf(__('Payment via %s failed. PayPal reason: %s.', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title, $pending_reason));
                    break;
                case 'VOIDED' :
                    $order->update_status('cancelled', sprintf(__('Payment via %s Voided.', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title));
                    break;
                default:
                    break;
            endswitch;
            return;
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_payment_status_woo_order_note($orderid, $payment_status, $pending_reason) {
        try {
            $order = wc_get_order($orderid);
            switch (strtoupper($payment_status)) :
                case 'DECLINED' :
                    $order->add_order_note(sprintf(__('Payment via %s declined.', 'paypal-for-woocommerce'), $order->get_payment_method_title()));
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
                        $order->add_order_note(sprintf(__('Payment via %s Pending. PayPal Pending reason: %s.', 'paypal-for-woocommerce'), $order->get_payment_method_title(), $pending_reason_text));
                    }
                    if ($payment_status === 'DECLINED') {
                        $order->add_order_note(sprintf(__('Payment via %s declined. PayPal declined reason: %s.', 'paypal-for-woocommerce'), $order->get_payment_method_title(), $pending_reason_text));
                    }
                    break;
                case 'PARTIALLY_REFUNDED' :

                    $order->add_order_note(sprintf(__('Payment via %s partially refunded. PayPal reason: %s.', 'paypal-for-woocommerce'), $order->get_payment_method_title(), $pending_reason));
                case 'REFUNDED' :

                    $order->add_order_note(sprintf(__('Payment via %s refunded. PayPal reason: %s.', 'paypal-for-woocommerce'), $order->get_payment_method_title(), $pending_reason));
                case 'FAILED' :
                    $order->add_order_note(sprintf(__('Payment via %s failed. PayPal reason: %s.', 'paypal-for-woocommerce'), $order->get_payment_method_title(), $pending_reason));
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
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
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
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_void_authorized_payment_admin($order, $order_data) {
        try {
            $order_id = $order->get_id();
            $note_to_payer = $order_data['angelleye_ppcp_note_to_buyer_void'] ?? '';
            if (strlen($note_to_payer) > 255) {
                $note_to_payer = substr($note_to_payer, 0, 252) . '...';
            }
            $authorization_id = angelleye_ppcp_get_post_meta($order, '_auth_transaction_id');
            $response = $this->angelleye_ppcp_void_authorized_payment($authorization_id, $note_to_payer);
            if (!is_wp_error($response)) {
                $payment_status = $response['status'] ?? '';
                $this->angelleye_ppcp_update_woo_order_status($order_id, $payment_status, $pending_reason = '');
            } else {
                $order->add_order_note(__("Void Authorization Failed:", 'paypal-for-woocommerce') . ': ' . $response->get_error_message());
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_multi_account_refund_order_third_party($order_id, $value, $testmode) {
        try {
            if(!isset($value['transaction_id'])) {
                return;
            }
            $transaction_id = $value['transaction_id'];
            if ($testmode) {
                $paypal_refund_api = 'https://api-m.sandbox.paypal.com/v2/payments/captures/';
            } else {
                $paypal_refund_api = 'https://api-m.paypal.com/v2/payments/captures/';
            }
            $order = wc_get_order($order_id);
            $reason = !empty($reason) ? $reason : 'Refund';
            $body_request['note_to_payer'] = $reason;
            $args = array(
                'method' => 'POST',
                'timeout' => 60,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking' => true,
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_multi_account_paypalauthassertion($value)),
                'body' => wp_json_encode($body_request),
                'cookies' => array()
            );
            $this->api_response = $this->api_request->request($paypal_refund_api . $transaction_id . '/refund', $args, 'refund_order');
            if (isset($this->api_response['status']) && $this->api_response['status'] == "COMPLETED") {
                $gross_amount = isset($this->api_response['seller_payable_breakdown']['gross_amount']['value']) ? $this->api_response['seller_payable_breakdown']['gross_amount']['value'] : '';
                $refund_transaction_id = isset($this->api_response['id']) ? $this->api_response['id'] : '';
                $order->add_order_note(
                        sprintf(__('Refunded %1$s - Refund ID: %2$s', 'paypal-for-woocommerce'), $gross_amount, $refund_transaction_id)
                );
            } else if (isset($$this->api_response['status']) && $$this->api_response['status'] == "PENDING") {
                $gross_amount = isset($$this->api_response['seller_payable_breakdown']['gross_amount']['value']) ? $$this->api_response['seller_payable_breakdown']['gross_amount']['value'] : '';
                $refund_transaction_id = isset($$this->api_response['id']) ? $$this->api_response['id'] : '';
                $pending_reason_text = isset($$this->api_response['status_details']['reason']) ? $$this->api_response['status_details']['reason'] : '';
                $order->add_order_note(sprintf(__('Payment via %s Pending. Pending reason: %s.', 'paypal-for-woocommerce'), $order->get_payment_method_title(), $pending_reason_text));
                $order->add_order_note(
                        sprintf(__('Refund Amount %1$s - Refund ID: %2$s', 'paypal-for-woocommerce'), $gross_amount, $refund_transaction_id)
                );
            } else {
                if ($this->paymentaction === 'authorize' && !empty($this->api_response['details'][0]['issue']) && 'INVALID_RESOURCE_ID' === $this->api_response['details'][0]['issue']) {
                    $this->angelleye_ppcp_void_authorized_payment($transaction_id);
                    return true;
                }
                if (!empty($this->api_response['details'][0]['description'])) {
                    $order->add_order_note('Error Message : ' . wc_print_r($this->api_response['details'][0]['description'], true));
                    throw new Exception($this->api_response['details'][0]['description']);
                }
                return false;
            }
            return $this->api_response;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
            return new WP_Error('error', $ex->getMessage());
        }
    }


    /**
     * @param WC_Order $order
     * @param $order_data
     * @return false|void
     */
    public function angelleye_ppcp_capture_authorized_payment_admin($order, $order_data) {
        try {
            if ($order === false) {
                return false;
            }
            $note_to_payer = $order_data['angelleye_ppcp_note_to_buyer_capture'] ?? '';
            if (strlen($note_to_payer) > 255) {
                $note_to_payer = substr($note_to_payer, 0, 252) . '...';
            }
            $order_id = $order->get_id();
            $total_order_value = floatval($order->get_total(''));
            $angelleye_ppcp_payment_method_title = $this->get_payment_method_title_for_order($order_id);
            $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
            $amount_value = isset($order_data['ppcp_refund_amount']) ? angelleye_ppcp_round($order_data['ppcp_refund_amount'], $decimals) : '';
            $capture_arg = array(
                'amount' =>
                array(
                    'value' => $amount_value,
                    'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($order_id), $amount_value),
                ),
                'note_to_payer' => $note_to_payer,
                'payment_instruction' => array('payee' => array('merchant_id' => $this->merchant_id)),
                'invoice_id' => $this->invoice_prefix . str_replace("#", "", $order->get_order_number())
            );
            $final_capture = false;
            if (isset($order_data['additionalCapture']) && 'no' === $order_data['additionalCapture']) {
                $final_capture = true;
            }
            $body_request = angelleye_ppcp_remove_empty_key($capture_arg);
            $body_request['final_capture'] = $final_capture;
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
                $payment_source = $this->api_response['payment_source'] ?? '';
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
                $processor_response = $this->api_response['purchase_units']['0']['payments']['captures']['0']['processor_response'] ?? '';
                if (!empty($processor_response['avs_code'])) {
                    $avs_response_order_note = __('Address Verification Result', 'paypal-for-woocommerce');
                    $avs_response_order_note .= "\n";
                    $avs_response_order_note .= $processor_response['avs_code'];
                    if (isset($this->avs_code[$processor_response['avs_code']][$payment_source['card']['brand']])) {
                        $avs_response_order_note .= ' : ' . $this->avs_code[$processor_response['avs_code']][$payment_source['card']['brand']];
                    }
                    $order->add_order_note($avs_response_order_note);
                }
                if (!empty($processor_response['cvv_code'])) {
                    $cvv2_response_code = __('Card Security Code Result', 'paypal-for-woocommerce');
                    $cvv2_response_code .= "\n";
                    $cvv2_response_code .= $processor_response['cvv_code'];
                    if (isset($this->cvv_code[$processor_response['cvv_code']][$payment_source['card']['brand']])) {
                        $cvv2_response_code .= ' : ' . $this->cvv_code[$processor_response['cvv_code']][$payment_source['card']['brand']];
                    }
                    $order->add_order_note($cvv2_response_code);
                }
                if (!empty($processor_response['response_code'])) {
                    $response_code = __('Processor response code Result', 'paypal-for-woocommerce');
                    $response_code .= "\n";
                    $response_code .= $processor_response['response_code'];
                    if (isset($this->response_code[$processor_response['response_code']])) {
                        $response_code .= ' : ' . $this->response_code[$processor_response['response_code']];
                    }
                    $order->add_order_note($response_code);
                }
                if (!empty($processor_response['payment_advice_code'])) {
                    $payment_advice_code = __('Payment Advice Codes Result', 'paypal-for-woocommerce');
                    $payment_advice_code .= "\n";
                    $payment_advice_code .= $processor_response['payment_advice_code'];
                    if (isset($this->payment_advice_code[$processor_response['payment_advice_code']])) {
                        $payment_advice_code .= ' : ' . $this->payment_advice_code[$processor_response['payment_advice_code']];
                    }
                    $order->add_order_note($payment_advice_code);
                }
                $transaction_id = isset($this->api_response['id']) ? $this->api_response['id'] : '';
                if (!empty($order_data['refund_line_total'])) {
                    foreach ($order_data['refund_line_total'] as $item_id => $item_amount) {
                        $ppcp_capture_details = [];
                        if (!empty($item_amount)) {
                            $transaction_id = isset($this->api_response['transaction_id']) ? $this->api_response['transaction_id'] : $transaction_id;
                            $transaction_amount = isset($this->api_response['amount']['value']) ? $this->api_response['amount']['value'] : $item_amount;
                            $transaction_date = date('m/d/y H:i', strtotime($this->api_response['update_time']));
                            $ppcp_capture_details[] = array(
                                '_ppcp_transaction_id' => $transaction_id,
                                '_ppcp_transaction_date' => $transaction_date,
                                '_ppcp_transaction_amount' => $transaction_amount
                            );
                            $_ppcp_capture_details = wc_get_order_item_meta($item_id, '_ppcp_capture_details', true);
                            if (!empty($_ppcp_capture_details)) {
                                $ppcp_capture_details = array_merge($_ppcp_capture_details, $ppcp_capture_details);
                            }
                            wc_update_order_item_meta($item_id, '_ppcp_capture_details', $ppcp_capture_details);
                        }
                    }
                }
                $seller_protection = $this->api_response['seller_protection']['status'] ?? '';
                $captured_amount = $this->api_response['amount']['value'];
                $this->api_response = $this->angelleye_ppcp_get_authorized_payment($authorization_id);
                $payment_status = $this->api_response['status'] ?? '';
                $order->update_meta_data('_payment_status', $payment_status);
                $order->save();
                $order->add_order_note(sprintf(__('%s Capture Transaction ID: %s', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title, $transaction_id));
                $order->add_order_note('Seller Protection Status: ' . angelleye_ppcp_readable($seller_protection));
                // PFW-1693 - We need to mark the order as completed if the order total is less than or equal to the captured amount
                if ('PARTIALLY_CAPTURED' === $payment_status && $total_order_value <= $captured_amount) {
                    $payment_status = 'CAPTURED';
                }

                if ($payment_status === 'COMPLETED' || 'CAPTURED' === $payment_status) {
                    $order->payment_complete();
                    $order->add_order_note(sprintf(__('Payment via %s: %s.', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title, ucfirst(strtolower($payment_status))));
                } elseif ('PARTIALLY_CAPTURED' === $payment_status) {
                    $order->update_status('wc-partial-payment');
                } elseif ($payment_status === 'DECLINED') {
                    $order->update_status('failed', sprintf(__('Payment via %s declined.', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title));
                    if (function_exists('wc_add_notice')) {
                        wc_add_notice(__('Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'paypal-for-woocommerce'), 'error');
                    }
                    return false;
                } else {
                    $payment_status_reason = $this->api_response['status_details']['reason'] ?? '';
                    $this->angelleye_ppcp_update_woo_order_status($order_id, $payment_status, $payment_status_reason);
                }
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
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_refund_order_admin($order, $order_data) {
        try {
            if ($order === false) {
                return false;
            }
            $note_to_payer = $order_data['angelleye_ppcp_note_to_buyer_capture'] ?? '';
            if (strlen($note_to_payer) > 255) {
                $note_to_payer = substr($note_to_payer, 0, 252) . '...';
            }
            $order_id = $order->get_id();
            $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
            $reason = !empty($reason) ? $reason : 'Refund';
            $body_request['note_to_payer'] = $reason;
            $amount_value = isset($order_data['_angelleye_ppcp_refund_price']) ? angelleye_ppcp_round($order_data['_angelleye_ppcp_refund_price'], $decimals) : '';
            $body_request['amount'] = array(
                'value' => $amount_value,
                'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($order_id), $amount_value)
            );
            $body_request = angelleye_ppcp_remove_empty_key($body_request);
            $transaction_id = $order_data['angelleye_ppcp_refund_data'] ?? '';
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
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
            return new WP_Error('error', $ex->getMessage());
        }
    }

    public function angelleye_ppcp_add_payment_source_parameter($request) {
        try {
            $payment_method_name = '';
            $angelleye_ppcp_used_payment_method = AngellEye_Session_Manager::get('used_payment_method', 'paypal');
            if (!empty($angelleye_ppcp_used_payment_method)) {
                $payment_method_name = '';
                $billing_address = array();
                $billing_full_name = '';
                $attributes = array();
                switch ($angelleye_ppcp_used_payment_method) {
                    case 'card':
                        $payment_method_name = 'card';
                        $attributes = array('vault' => array('store_in_vault' => 'ON_SUCCESS', 'usage_type' => 'MERCHANT'));
                        if (!empty($request['payer']['address'])) {
                            $billing_address = array(
                                'address_line_1' => $request['payer']['address']['address_line_1'] ?? '',
                                'address_line_2' => $request['payer']['address']['address_line_2'] ?? '',
                                'admin_area_2' => $request['payer']['address']['admin_area_2'] ?? '',
                                'admin_area_1' => $request['payer']['address']['admin_area_1'] ?? '',
                                'postal_code' => $request['payer']['address']['postal_code'] ?? '',
                                'country_code' => strtoupper($request['payer']['address']['country_code'] ?? ''),
                            );
                        }
                        $first_name = $request['payer']['name']['given_name'] ?? '';
                        $last_name = $request['payer']['name']['surname'] ?? '';
                        $billing_full_name = $first_name . ' ' . $last_name;
                        $paypal_generated_customer_id = $this->ppcp_payment_token->angelleye_ppcp_get_paypal_generated_customer_id($this->is_sandbox);
                        if (!empty($paypal_generated_customer_id)) {
                            $attributes['customer'] = array('id' => $paypal_generated_customer_id);
                        }
                        $request['payment_source'][$payment_method_name]['name'] = $billing_full_name;
                        $request['payment_source'][$payment_method_name]['billing_address'] = $billing_address;
                        $request['payment_source'][$payment_method_name]['attributes'] = $attributes;
                        $request['payment_source'][$payment_method_name]['stored_credential'] = array(
                            'payment_initiator' => 'CUSTOMER',
                            'payment_type' => 'UNSCHEDULED',
                            'usage' => 'SUBSEQUENT'
                        );
                        break;
                    case 'credit':
                    case 'paypal':
                        $payment_method_name = 'paypal';
                        $attributes = array('vault' => array('store_in_vault' => 'ON_SUCCESS', 'usage_type' => 'MERCHANT', 'permit_multiple_payment_tokens' => true));
                        $paypal_generated_customer_id = $this->ppcp_payment_token->angelleye_ppcp_get_paypal_generated_customer_id($this->is_sandbox);
                        if (!empty($paypal_generated_customer_id)) {
                            $attributes['customer'] = array('id' => $paypal_generated_customer_id);
                        }
                        $request['payment_source'][$payment_method_name]['attributes'] = $attributes;
                        //$request['payment_source'][$payment_method_name]['experience_context']['shipping_preference'] = $this->angelleye_ppcp_shipping_preference();
                        if(!isset($request['application_context']['return_url'])) {
                            $request['payment_source'][$payment_method_name]['experience_context']['return_url'] = add_query_arg(array('angelleye_ppcp_action' => 'regular_capture', 'utm_nooverride' => '1'), untrailingslashit(WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action')));
                            $request['payment_source'][$payment_method_name]['experience_context']['cancel_url'] = add_query_arg(array('angelleye_ppcp_action' => 'regular_cancel', 'utm_nooverride' => '1'), untrailingslashit(WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action')));
                        }
                        break;
                    case 'venmo':
                        $payment_method_name = 'venmo';
                        $attributes = array('vault' => array('store_in_vault' => 'ON_SUCCESS', 'usage_type' => 'MERCHANT', 'permit_multiple_payment_tokens ' => true));
                        $paypal_generated_customer_id = $this->ppcp_payment_token->angelleye_ppcp_get_paypal_generated_customer_id($this->is_sandbox);
                        if (!empty($paypal_generated_customer_id)) {
                            $attributes['customer'] = array('id' => $paypal_generated_customer_id);
                        }
                        $request['payment_source'][$payment_method_name]['attributes'] = $attributes;

                        $request['payment_source'][$payment_method_name]['experience_context']['return_url'] = add_query_arg(array('angelleye_ppcp_action' => 'regular_capture', 'utm_nooverride' => '1'), untrailingslashit(WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action')));
                        $request['payment_source'][$payment_method_name]['experience_context']['cancel_url'] = add_query_arg(array('angelleye_ppcp_action' => 'regular_cancel', 'utm_nooverride' => '1'), untrailingslashit(WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action')));
                        if (isset($request['purchase_units'][0]['shipping']['address'])) {
                            $request['payment_source'][$payment_method_name]['experience_context']['shipping_preference'] = 'SET_PROVIDED_ADDRESS';
                        } else {
                            $request['payment_source'][$payment_method_name]['experience_context']['shipping_preference'] = $this->angelleye_ppcp_shipping_preference();
                        }
                        unset($request['application_context']);
                        break;
                    case 'apple_pay':
                        $payment_method_name = 'apple_pay';
                        $attributes = array('vault' => array('store_in_vault' => 'ON_SUCCESS', 'usage_type' => 'MERCHANT', 'permit_multiple_payment_tokens' => true));
                        // If existing PayPal Customer ID is available then add it so that PayPal can add new payment method to same user account.
                        $paypal_generated_customer_id = $this->ppcp_payment_token->angelleye_ppcp_get_paypal_generated_customer_id($this->is_sandbox);
                        if (!empty($paypal_generated_customer_id)) {
                            $attributes['customer'] = ['id' => $paypal_generated_customer_id];
                        }
                        $request['payment_source'][$payment_method_name]['attributes'] = $attributes;
                        $request['payment_source'][$payment_method_name]['stored_credential'] = [
                            "payment_initiator" => "CUSTOMER", "payment_type" => "RECURRING"
                        ];
                        break;
                    default:
                        break;
                }
            }
            return $request;
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_capture_order_using_payment_method_token($order_id) {
        try {
            $order = wc_get_order($order_id);
            $angelleye_ppcp_payment_method_title = $this->get_payment_method_title_for_order($order_id);
            $this->paymentaction = apply_filters('angelleye_ppcp_paymentaction', $this->paymentaction, $order_id);
            $cart = $this->angelleye_ppcp_get_details_from_order($order_id);
            $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
            $intent = ($this->paymentaction === 'capture') ? 'CAPTURE' : 'AUTHORIZE';
            $reference_id = $order->get_order_key();
            $body_request = array(
                'intent' => $intent,
                'payment_method' => array('payee_preferred' => ($this->payee_preferred) ? 'IMMEDIATE_PAYMENT_REQUIRED' : 'UNRESTRICTED'),
                'purchase_units' =>
                array(
                    0 =>
                    array(
                        'reference_id' => $reference_id,
                        'amount' =>
                        array(
                            'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($order_id), $cart['order_total']),
                            'value' => $cart['order_total'],
                            'breakdown' => array()
                        )
                    ),
                ),
            );
            $body_request['purchase_units'][0]['invoice_id'] = $this->invoice_prefix . str_replace("#", "", $order->get_order_number());
            $body_request['purchase_units'][0]['custom_id'] = apply_filters('angelleye_ppcp_custom_id', $this->invoice_prefix . str_replace("#", "", $order->get_order_number()), $order);
            $body_request['purchase_units'][0]['soft_descriptor'] = angelleye_ppcp_get_value('soft_descriptor', $this->soft_descriptor);
            $body_request['purchase_units'][0]['payee']['merchant_id'] = $this->merchant_id;
            if ($this->send_items === true) {
                if (isset($cart['total_item_amount']) && $cart['total_item_amount'] > 0) {
                    $body_request['purchase_units'][0]['amount']['breakdown']['item_total'] = array(
                        'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($order_id), $cart['total_item_amount']),
                        'value' => $cart['total_item_amount'],
                    );
                }
                if (isset($cart['shipping']) && $cart['shipping'] > 0) {
                    $body_request['purchase_units'][0]['amount']['breakdown']['shipping'] = array(
                        'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($order_id), $cart['shipping']),
                        'value' => $cart['shipping'],
                    );
                }
                if (isset($cart['ship_discount_amount']) && $cart['ship_discount_amount'] > 0) {
                    $body_request['purchase_units'][0]['amount']['breakdown']['shipping_discount'] = array(
                        'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($order_id), angelleye_ppcp_round($cart['ship_discount_amount'], $decimals)),
                        'value' => angelleye_ppcp_round($cart['ship_discount_amount'], $decimals),
                    );
                }
                if (isset($cart['order_tax']) && $cart['order_tax'] > 0) {
                    $body_request['purchase_units'][0]['amount']['breakdown']['tax_total'] = array(
                        'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($order_id), $cart['order_tax']),
                        'value' => $cart['order_tax'],
                    );
                }
                if (isset($cart['discount']) && $cart['discount'] > 0) {
                    $body_request['purchase_units'][0]['amount']['breakdown']['discount'] = array(
                        'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($order_id), $cart['discount']),
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
                                'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', angelleye_ppcp_get_currency($order_id), $order_items['amount']),
                                'value' => $order_items['amount'],
                            ),
                        );
                    }
                }
            }
            if ($order->needs_shipping_address()) {
                if ($order->has_shipping_address()) {
                    $shipping_first_name = $order->get_shipping_first_name();
                    $shipping_last_name = $order->get_shipping_last_name();
                    $shipping_address_1 = $order->get_shipping_address_1();
                    $shipping_address_2 = $order->get_shipping_address_2();
                    $shipping_city = $order->get_shipping_city();
                    $shipping_state = $order->get_shipping_state();
                    $shipping_postcode = $order->get_shipping_postcode();
                    $shipping_country = $order->get_shipping_country();
                } else {
                    $shipping_first_name = $order->get_billing_first_name();
                    $shipping_last_name = $order->get_billing_last_name();
                    $shipping_address_1 = $order->get_billing_address_1();
                    $shipping_address_2 = $order->get_billing_address_2();
                    $shipping_city = $order->get_billing_city();
                    $shipping_state = $order->get_billing_state();
                    $shipping_postcode = $order->get_billing_postcode();
                    $shipping_country = $order->get_billing_country();
                }
                if (!empty($shipping_first_name) && !empty($shipping_last_name)) {
                    $body_request['purchase_units'][0]['shipping']['name']['full_name'] = $shipping_first_name . ' ' . $shipping_last_name;
                }
                $shipping_country = strtoupper($shipping_country);
                $body_request['purchase_units'][0]['shipping']['address'] = array(
                    'address_line_1' => $shipping_address_1,
                    'address_line_2' => $shipping_address_2,
                    'admin_area_2' => $shipping_city,
                    'admin_area_1' => $shipping_state,
                    'postal_code' => $shipping_postcode,
                    'country_code' => $shipping_country,
                );
            }
            $body_request = $this->angelleye_ppcp_set_payer_details($order_id, $body_request);
            $body_request = apply_filters('angelleye_ppcp_add_payment_source', $body_request, $order_id);
            $body_request = angelleye_ppcp_remove_empty_key($body_request);
            $args = array(
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'body' => $body_request
            );
          //  $args = apply_filters('angelleye_ppcp_request_args', $args, 'create_order', $order_id);
            $this->api_response = $this->api_request->request($this->paypal_order_api, $args, 'create_order');
            if (ob_get_length()) {
                ob_end_clean();
            }
            if (isset($this->api_response['id']) && !empty($this->api_response['id'])) {
                $order->update_meta_data('_paypal_order_id', $this->api_response['id']);
                $order->save();
                if ($this->api_response['status'] == 'COMPLETED') {
                    $payment_source = $this->api_response['payment_source'] ?? '';
                    if (!empty($payment_source['card'])) {
                        if (isset($this->api_response['payment_source']['card']['from_request']['expiry'])) {
                            $token_id = '';
                            if (!empty($_POST['wc-angelleye_ppcp_cc-payment-token']) && $_POST['wc-angelleye_ppcp_cc-payment-token'] != 'new') {
                                $token_id = wc_clean($_POST['wc-angelleye_ppcp_cc-payment-token']);
                            } else {
                                $payment_tokens_id = angelleye_ppcp_get_post_meta($order, '_payment_tokens_id', true);
                                $token_id = angelleye_ppcp_get_token_id_by_token($payment_tokens_id);
                            }
                            if (!empty($token_id)) {
                                $token = WC_Payment_Tokens::get($token_id);
                                $token->set_last4($this->api_response['payment_source']['card']['last_digits']);
                                if (isset($this->api_response['payment_source']['card']['expiry'])) {
                                    $card_expiry = array_map('trim', explode('-', $this->api_response['payment_source']['card']['expiry']));
                                    $card_exp_year = str_pad($card_expiry[0], 4, "0", STR_PAD_LEFT);
                                    $card_exp_month = $card_expiry[1] ?? '';
                                    $token->set_expiry_month($card_exp_month);
                                    $token->set_expiry_year($card_exp_year);
                                } else {
                                    $card_details = $this->angelleye_ppcp_get_payment_token_details($token->get_token());
                                    if (isset($card_details['payment_source']['card']['expiry'])) {
                                        $card_expiry = array_map('trim', explode('-', $card_details['payment_source']['card']['expiry']));
                                        $card_exp_year = str_pad($card_expiry[0], 4, "0", STR_PAD_LEFT);
                                        $card_exp_month = $card_expiry[1] ?? '';
                                        $token->set_expiry_month($card_exp_month);
                                        $token->set_expiry_year($card_exp_year);
                                    } else {
                                        $token->set_expiry_month(date('m'));
                                        $token->set_expiry_year(date('Y', strtotime('+5 years')));
                                    }
                                }
                                if ($token->validate()) {
                                    $token->save();
                                }
                            }
                        }
                    }
                    if ($this->paymentaction === 'capture') {
                        $processor_response = isset($this->api_response['purchase_units']['0']['payments']['captures']['0']['processor_response']) ? $this->api_response['purchase_units']['0']['payments']['captures']['0']['processor_response'] : '';
                        $payment_source = isset($this->api_response['payment_source']) ? $this->api_response['payment_source'] : '';
                        if (!empty($payment_source['card']['last_digits'])) {
                            $card_response_order_note = __('Card Details', 'paypal-for-woocommerce');
                            $card_response_order_note .= "\n";
                            $card_response_order_note .= 'Last digits : ' . $payment_source['card']['last_digits'];
                            $card_response_order_note .= "\n";
                            $card_response_order_note .= 'Brand : ' . angelleye_ppcp_readable($payment_source['card']['brand']);
                            $card_response_order_note .= "\n";
                            $card_response_order_note .= 'Card type : ' . angelleye_ppcp_readable($payment_source['card']['type']);
                            $order->add_order_note($card_response_order_note);
                        }
                        if (!empty($processor_response['avs_code'])) {
                            $avs_response_order_note = __('Address Verification Result', 'paypal-for-woocommerce');
                            $avs_response_order_note .= "\n";
                            $avs_response_order_note .= $processor_response['avs_code'];
                            if (isset($this->avs_code[$processor_response['avs_code']][$payment_source['card']['brand']])) {
                                $avs_response_order_note .= ' : ' . $this->avs_code[$processor_response['avs_code']][$payment_source['card']['brand']];
                            }
                            $order->add_order_note($avs_response_order_note);
                        }
                        if (!empty($processor_response['cvv_code'])) {
                            $cvv2_response_code = __('Card Security Code Result', 'paypal-for-woocommerce');
                            $cvv2_response_code .= "\n";
                            $cvv2_response_code .= $processor_response['cvv_code'];
                            if (isset($this->cvv_code[$processor_response['cvv_code']][$payment_source['card']['brand']])) {
                                $cvv2_response_code .= ' : ' . $this->cvv_code[$processor_response['cvv_code']][$payment_source['card']['brand']];
                            }
                            $order->add_order_note($cvv2_response_code);
                        }
                        if (!empty($processor_response['response_code'])) {
                            $response_code = __('Processor response code Result', 'paypal-for-woocommerce');
                            $response_code .= "\n";
                            $response_code .= $processor_response['response_code'];
                            if (isset($this->response_code[$processor_response['response_code']])) {
                                $response_code .= ' : ' . $this->response_code[$processor_response['response_code']];
                            }
                            $order->add_order_note($response_code);
                        }
                        $currency_code = isset($this->api_response['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['currency_code']) ? $this->api_response['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['currency_code'] : '';
                        $value = isset($this->api_response['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['value']) ? $this->api_response['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['value'] : '';
                        $order->update_meta_data('_paypal_fee', $value);
                        $order->update_meta_data('_paypal_transaction_fee', $value);
                        $order->update_meta_data('_paypal_fee_currency_code', $currency_code);
                        $order->save();
                        $transaction_id = isset($this->api_response['purchase_units']['0']['payments']['captures']['0']['id']) ? $this->api_response['purchase_units']['0']['payments']['captures']['0']['id'] : '';
                        $seller_protection = isset($this->api_response['purchase_units']['0']['payments']['captures']['0']['seller_protection']['status']) ? $this->api_response['purchase_units']['0']['payments']['captures']['0']['seller_protection']['status'] : '';
                        $payment_status = isset($this->api_response['purchase_units']['0']['payments']['captures']['0']['status']) ? $this->api_response['purchase_units']['0']['payments']['captures']['0']['status'] : '';
                        if (!empty($processor_response['payment_advice_code'])) {
                            $payment_advice_code = __('Payment Advice Codes Result', 'paypal-for-woocommerce');
                            $payment_advice_code .= "\n";
                            $payment_advice_code .= $processor_response['payment_advice_code'];
                            if (isset($this->payment_advice_code[$processor_response['payment_advice_code']])) {
                                $payment_advice_code .= ' : ' . $this->payment_advice_code[$processor_response['payment_advice_code']];
                            }
                            $order->add_order_note($payment_advice_code);
                        }
                        if ($payment_status == 'COMPLETED') {
                            $order->payment_complete($transaction_id);
                            $order->add_order_note(sprintf(__('Payment via %s: %s.', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title, ucfirst(strtolower($payment_status))));
                        } elseif ($payment_status === 'DECLINED') {
                            $order->update_status('failed', sprintf(__('Payment via %s declined.', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title));
                            if (function_exists('wc_add_notice')) {
                                wc_add_notice(__('Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'paypal-for-woocommerce'), 'error');
                            }
                            return false;
                        } else {
                            $payment_status_reason = $this->api_response['purchase_units']['0']['payments']['captures']['0']['status_details']['reason'] ?? '';
                            $this->angelleye_ppcp_update_woo_order_status($order_id, $payment_status, $payment_status_reason);
                        }
                        $order->update_meta_data('_payment_status', $payment_status);
                        $order->save();
                        $order->add_order_note(sprintf(__('%s Capture Transaction ID: %s', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title, $transaction_id));
                        $order->add_order_note('Seller Protection Status: ' . angelleye_ppcp_readable($seller_protection));
                        return true;
                    } else {
                        $processor_response = $this->api_response['purchase_units']['0']['payments']['authorizations']['0']['processor_response'] ?? '';
                        if (!empty($processor_response['avs_code'])) {
                            $avs_response_order_note = __('Address Verification Result', 'paypal-for-woocommerce');
                            $avs_response_order_note .= "\n";
                            $avs_response_order_note .= $processor_response['avs_code'];
                            if (isset($this->avs_code[$processor_response['avs_code']][$payment_source['card']['brand']])) {
                                $avs_response_order_note .= ' : ' . $this->avs_code[$processor_response['avs_code']][$payment_source['card']['brand']];
                            }
                            $order->add_order_note($avs_response_order_note);
                        }
                        if (!empty($processor_response['cvv_code'])) {
                            $cvv2_response_code = __('Card Security Code Result', 'paypal-for-woocommerce');
                            $cvv2_response_code .= "\n";
                            $cvv2_response_code .= $processor_response['cvv_code'];
                            if (isset($this->cvv_code[$processor_response['cvv_code']][$payment_source['card']['brand']])) {
                                $cvv2_response_code .= ' : ' . $this->cvv_code[$processor_response['cvv_code']][$payment_source['card']['brand']];
                            }
                            $order->add_order_note($cvv2_response_code);
                        }
                        if (!empty($processor_response['response_code'])) {
                            $response_code = __('Processor response code Result', 'paypal-for-woocommerce');
                            $response_code .= "\n";
                            $response_code .= $processor_response['response_code'];
                            if ($this->response_code[$processor_response['response_code']]) {
                                $response_code .= ' : ' . $this->response_code[$processor_response['response_code']];
                            }
                            $order->add_order_note($response_code);
                        }
                        $currency_code = isset($this->api_response['purchase_units'][0]['payments']['authorizations'][0]['seller_receivable_breakdown']['paypal_fee']['currency_code']) ? $this->api_response['purchase_units'][0]['payments']['authorizations'][0]['seller_receivable_breakdown']['paypal_fee']['currency_code'] : '';
                        $value = isset($this->api_response['purchase_units'][0]['payments']['authorizations'][0]['seller_receivable_breakdown']['paypal_fee']['value']) ? $this->api_response['purchase_units'][0]['payments']['authorizations'][0]['seller_receivable_breakdown']['paypal_fee']['value'] : '';
                        $order->update_meta_data('_paypal_fee', $value);
                        $order->update_meta_data('_paypal_transaction_fee', $value);
                        $order->update_meta_data('_paypal_fee_currency_code', $currency_code);
                        $order->save();
                        $transaction_id = isset($this->api_response['purchase_units']['0']['payments']['authorizations']['0']['id']) ? $this->api_response['purchase_units']['0']['payments']['authorizations']['0']['id'] : '';
                        $seller_protection = isset($this->api_response['purchase_units']['0']['payments']['authorizations']['0']['seller_protection']['status']) ? $this->api_response['purchase_units']['0']['payments']['authorizations']['0']['seller_protection']['status'] : '';
                        $payment_status = isset($this->api_response['purchase_units']['0']['payments']['authorizations']['0']['status']) ? $this->api_response['purchase_units']['0']['payments']['authorizations']['0']['status'] : '';
                        if (!empty($processor_response['payment_advice_code'])) {
                            $payment_advice_code = __('Payment Advice Codes Result', 'paypal-for-woocommerce');
                            $payment_advice_code .= "\n";
                            $payment_advice_code .= $processor_response['payment_advice_code'];
                            if (isset($this->payment_advice_code[$processor_response['payment_advice_code']])) {
                                $payment_advice_code .= ' : ' . $this->payment_advice_code[$processor_response['payment_advice_code']];
                            }
                            $order->add_order_note($payment_advice_code);
                        }
                        if ($payment_status == 'COMPLETED') {
                            $order->payment_complete($transaction_id);
                            $order->add_order_note(sprintf(__('Payment via %s: %s.', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title, ucfirst(strtolower($payment_status))));
                        } elseif ($payment_status === 'DECLINED') {
                            $order->update_status('failed', sprintf(__('Payment via %s declined.', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title));
                            if (function_exists('wc_add_notice')) {
                                wc_add_notice(__('Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'paypal-for-woocommerce'), 'error');
                            }
                            return false;
                        } else {
                            $payment_status_reason = isset($this->api_response['purchase_units']['0']['payments']['authorizations']['0']['status_details']['reason']) ? $this->api_response['purchase_units']['0']['payments']['authorizations']['0']['status_details']['reason'] : '';
                            $this->angelleye_ppcp_update_woo_order_status($order_id, $payment_status, $payment_status_reason);
                        }
                        $order->update_meta_data('_payment_status', $payment_status);
                        $order->set_transaction_id($transaction_id);
                        $order->update_meta_data('_auth_transaction_id', $transaction_id);
                        $order->update_meta_data('_paymentaction', 'authorize');
                        $order->add_order_note(sprintf(__('%s Authorization Transaction ID: %s', 'paypal-for-woocommerce'), $angelleye_ppcp_payment_method_title, $transaction_id));
                        $order->add_order_note('Seller Protection Status: ' . angelleye_ppcp_readable($seller_protection));
                        if (class_exists('AngellEYE_PayPal_PPCP_Admin_Action')) {
                            AngellEYE_PayPal_PPCP_Admin_Action::instance()->removeAutoCaptureHooks();
                        }
                        $order->update_status($this->get_preferred_order_status('on-hold', $order_id));
                        $order->save();
                        if ($this->is_auto_capture_auth) {
                            $order->add_order_note(__('Payment authorized. Change payment status to processing or complete to capture funds.', 'paypal-for-woocommerce'));
                        }
                        return true;
                    }
                } else {
                    return false;
                }
            } else {
                $error_email_notification_param = array(
                    'request' => 'capture_order',
                    'order_id' => $order_id
                );
                $readable_message = $this->angelleye_ppcp_get_readable_message($this->api_response, $error_email_notification_param);
                $order->add_order_note($readable_message);
                return false;
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function save_payment_token($order, $payment_tokens_id) {
        $order_id = $order->get_id();
        if (function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($order_id)) {
            $subscriptions = wcs_get_subscriptions_for_order($order_id);
        } elseif (function_exists('wcs_order_contains_renewal') && wcs_order_contains_renewal($order_id)) {
            $subscriptions = wcs_get_subscriptions_for_renewal_order($order_id);
        } else {
            $subscriptions = array();
        }
        if (!empty($subscriptions)) {
            foreach ($subscriptions as $subscription) {
                $subscription->update_meta_data('_payment_tokens_id', $payment_tokens_id);
                $subscription->save();
            }
        } else {
            $order->update_meta_data('_payment_tokens_id', $payment_tokens_id);
            $order->save();
        }
    }

    public function angelleye_ppcp_get_order_return_url($order = null) {
        if ($order) {
            $return_url = $order->get_checkout_order_received_url();
        } else {
            $return_url = wc_get_endpoint_url('order-received', '', wc_get_checkout_url());
        }
        return apply_filters('woocommerce_get_return_url', $return_url, $order);
    }

    public function angelleye_ppcp_paypal_setup_tokens() {
        try {
            $body_request = array();
            $body_request['payment_source']['paypal']['description'] = "Billing Agreement";
            $body_request['payment_source']['paypal']['permit_multiple_payment_tokens'] = true;
            $body_request['payment_source']['paypal']['usage_pattern'] = 'IMMEDIATE';
            $body_request['payment_source']['paypal']['usage_type'] = 'MERCHANT';
            $body_request['payment_source']['paypal']['customer_type'] = 'CONSUMER';
            $body_request['payment_source']['paypal']['experience_context'] = array(
                'shipping_preference' => 'GET_FROM_FILE',
                'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                'brand_name' => $this->brand_name,
                'locale' => 'en-US',
                'return_url' => add_query_arg(array('angelleye_ppcp_action' => 'paypal_create_payment_token', 'utm_nooverride' => '1', 'customer_id' => get_current_user_id()), untrailingslashit(WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action'))),
                'cancel_url' => wc_get_account_endpoint_url('add-payment-method')
            );
            $paypal_generated_customer_id = $this->ppcp_payment_token->angelleye_ppcp_get_paypal_generated_customer_id($this->is_sandbox);
            if (!empty($paypal_generated_customer_id)) {
                $body_request['customer']['id'] = $paypal_generated_customer_id;
            }
            $args = array(
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'body' => $body_request
            );
            $this->api_response = $this->api_request->request($this->setup_tokens_url, $args, 'setup tokens');
            if (ob_get_length()) {
                ob_end_clean();
            }
            if (!empty($this->api_response['id'])) {
                if (!empty($this->api_response['links'])) {
                    foreach ($this->api_response['links'] as $key => $link_result) {
                        if ('approve' === $link_result['rel']) {
                            return array(
                                'result' => '',
                                'redirect' => $link_result['href']
                            );
                        }
                    }
                }
                return array(
                    'result' => 'failure',
                    'redirect' => wc_get_account_endpoint_url('payment-methods')
                );
            } else {
                $error_email_notification_param = array(
                    'request' => 'setup_tokens'
                );
                $error_message = $this->angelleye_ppcp_get_readable_message($this->api_response, $error_email_notification_param);
                wc_add_notice($error_message, 'error');
                return array(
                    'result' => 'failure',
                    'redirect' => wc_get_account_endpoint_url('payment-methods')
                );
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_paypal_setup_tokens_free_signup_with_free_trial($order_id) {
        try {
            $body_request = array();
            $body_request['payment_source']['paypal']['description'] = "Billing Agreement";
            $body_request['payment_source']['paypal']['permit_multiple_payment_tokens'] = true;
            $body_request['payment_source']['paypal']['usage_pattern'] = 'IMMEDIATE';
            $body_request['payment_source']['paypal']['usage_type'] = 'MERCHANT';
            $body_request['payment_source']['paypal']['customer_type'] = 'CONSUMER';
            $body_request['payment_source']['paypal']['experience_context'] = array(
                'shipping_preference' => 'GET_FROM_FILE',
                'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                'brand_name' => $this->brand_name,
                'locale' => 'en-US',
                'return_url' => add_query_arg(array('angelleye_ppcp_action' => 'paypal_create_payment_token_free_signup_with_free_trial', 'utm_nooverride' => '1', 'customer_id' => get_current_user_id(), 'order_id' => $order_id), untrailingslashit(WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action'))),
                'cancel_url' => wc_get_checkout_url()
            );
            $paypal_generated_customer_id = $this->ppcp_payment_token->angelleye_ppcp_get_paypal_generated_customer_id($this->is_sandbox);
            if (!empty($paypal_generated_customer_id)) {
                $body_request['customer']['id'] = $paypal_generated_customer_id;
            }
            $args = array(
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'body' => $body_request
            );
            $this->api_response = $this->api_request->request($this->setup_tokens_url, $args, 'setup tokens');
            if (ob_get_length()) {
                ob_end_clean();
            }
            if (!empty($this->api_response['id'])) {
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
                    'result' => 'failure',
                    'redirect' => wc_get_checkout_url()
                );
            } else {
                $error_email_notification_param = array(
                    'request' => 'setup_tokens'
                );
                $error_message = $this->angelleye_ppcp_get_readable_message($this->api_response, $error_email_notification_param);
                wc_add_notice($error_message, 'error');
                return array(
                    'result' => 'failure',
                    'redirect' => wc_get_checkout_url()
                );
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_paypal_create_payment_token_free_signup_with_free_trial() {
        try {
            $body_request = array();
            if (isset($_GET[APPROVAL_TOKEN_ID_PARAM_NAME]) && isset($_GET['order_id'])) {
                $body_request['payment_source']['token'] = array(
                    'id' => wc_clean($_GET[APPROVAL_TOKEN_ID_PARAM_NAME]),
                    'type' => 'SETUP_TOKEN'
                );
                $args = array(
                    'method' => 'POST',
                    'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                    'body' => $body_request
                );
                $this->api_response = $this->api_request->request($this->payment_tokens_url, $args, 'create_payment_token');
                if (ob_get_length()) {
                    ob_end_clean();
                }
                if (!empty($this->api_response['id'])) {
                    $customer_id = $this->api_response['customer']['id'] ?? '';
                    if (isset($customer_id) && !empty($customer_id)) {
                        $this->ppcp_payment_token->angelleye_ppcp_add_paypal_generated_customer_id($customer_id, $this->is_sandbox);
                    }
                    $order_id = wc_clean($_GET['order_id']);
                    $order = wc_get_order($order_id);
                    $order->update_meta_data('_angelleye_ppcp_used_payment_method', 'paypal');
                    $order->save();
                    $this->save_payment_token($order, $this->api_response['id']);
                    if (angelleye_ppcp_get_token_id_by_token($this->api_response['id']) === '') {
                        $token = new WC_Payment_Token_CC();
                        if (0 != $order->get_user_id()) {
                            $customer_id = $order->get_user_id();
                        } else {
                            $customer_id = get_current_user_id();
                        }
                        if (isset($this->api_response['payment_source']['paypal']['email_address'])) {
                            $email_address = $this->api_response['payment_source']['paypal']['email_address'];
                        } elseif ($this->api_response['payment_source']['paypal']['payer_id']) {
                            $email_address = $this->api_response['payment_source']['paypal']['payer_id'];
                        } else {
                            $email_address = 'PayPal Vault';
                        }
                        $token->set_token($this->api_response['id']);
                        $token->set_gateway_id($order->get_payment_method());
                        $token->set_card_type($email_address);
                        $token->set_last4(substr($this->api_response['id'], -4));
                        $token->set_expiry_month(date('m'));
                        $token->set_expiry_year(date('Y', strtotime('+20 years')));
                        $token->set_user_id($customer_id);
                        if ($token->validate()) {
                            $token->save();
                            update_metadata('payment_token', $token->get_id(), '_angelleye_ppcp_used_payment_method', 'paypal');
                            add_filter('woocommerce_payment_complete_order_status', function ($payment_status, $order_id) {
                                return $this->get_preferred_order_status($payment_status, $order_id);
                            }, 20, 2);
                            $order->payment_complete();
                            WC()->cart->empty_cart();
                            wp_redirect($this->angelleye_ppcp_get_order_return_url($order));
                            exit();
                        } else {
                            $order->add_order_note('ERROR MESSAGE: ' . __('Invalid or missing payment token fields.', 'paypal-for-woocommerce'));
                        }
                    } else {
                        add_filter('woocommerce_payment_complete_order_status', function ($payment_status, $order_id) {
                            return $this->get_preferred_order_status($payment_status, $order_id);
                        }, 20, 2);
                        $order->payment_complete();
                        WC()->cart->empty_cart();
                        wp_redirect($this->angelleye_ppcp_get_order_return_url($order));
                        exit();
                    }
                    wp_redirect(wc_get_checkout_url());
                    exit();
                } else {
                    $error_email_notification_param = array(
                        'request' => 'create_payment_token'
                    );
                    $error_message = $this->angelleye_ppcp_get_readable_message($this->api_response, $error_email_notification_param);
                    wc_add_notice($error_message, 'error');
                    wp_redirect(wc_get_checkout_url());
                    exit();
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_paypal_create_payment_token() {
        try {
            $body_request = array();
            if (isset($_GET[APPROVAL_TOKEN_ID_PARAM_NAME])) {
                // Clear the notices as WooCommerce PayPal Payments tries to handle the approval_token_id parameter
                // before our handler and sets an error in session [RESOURCE_NOT_FOUND] The specified resource does not exist.
                // so clear those notices to show the clean notice to users
                wc_clear_notices();
                $body_request['payment_source']['token'] = array(
                    'id' => wc_clean($_GET[APPROVAL_TOKEN_ID_PARAM_NAME]),
                    'type' => 'SETUP_TOKEN'
                );
                $args = array(
                    'method' => 'POST',
                    'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                    'body' => $body_request
                );
                $this->api_response = $this->api_request->request($this->payment_tokens_url, $args, 'create_payment_token');
                if (ob_get_length()) {
                    ob_end_clean();
                }
                if (!empty($this->api_response['id'])) {
                    $customer_id = $this->api_response['customer']['id'] ?? '';
                    if (isset($customer_id) && !empty($customer_id)) {
                        $this->ppcp_payment_token->angelleye_ppcp_add_paypal_generated_customer_id($customer_id, $this->is_sandbox);
                    }
                    if (angelleye_ppcp_get_token_id_by_token($this->api_response['id']) === '') {
                        $token = new WC_Payment_Token_CC();
                        $wc_customer_id = get_current_user_id();
                        if (isset($this->api_response['payment_source']['paypal']['email_address'])) {
                            $email_address = $this->api_response['payment_source']['paypal']['email_address'];
                        } elseif ($this->api_response['payment_source']['paypal']['payer_id']) {
                            $email_address = $this->api_response['payment_source']['paypal']['payer_id'];
                        } else {
                            $email_address = 'PayPal Vault';
                        }
                        $token->set_token($this->api_response['id']);
                        $token->set_gateway_id('angelleye_ppcp');
                        $token->set_card_type($email_address);
                        $token->set_last4(substr($this->api_response['id'], -4));
                        $token->set_expiry_month(date('m'));
                        $token->set_expiry_year(date('Y', strtotime('+20 years')));
                        $token->set_user_id($wc_customer_id);
                        if ($token->validate()) {
                            $token->save();
                            update_metadata('payment_token', $token->get_id(), '_angelleye_ppcp_used_payment_method', 'paypal');
                            wc_add_notice(__('Payment method successfully added.', 'woocommerce'));
                        } else {
                            wc_add_notice(__('Unable to add payment method to your account.', 'woocommerce'), 'error');
                        }
                    } else {
                        wc_add_notice(__('Payment method already exist in your account.', 'woocommerce'), 'notice');
                    }
                    wp_redirect(wc_get_account_endpoint_url('payment-methods'));
                    exit();
                } else {
                    $error_email_notification_param = array(
                        'request' => 'create_payment_token'
                    );
                    $error_message = $this->angelleye_ppcp_get_readable_message($this->api_response, $error_email_notification_param);
                    wc_add_notice($error_message, 'error');
                    wc_add_notice(__('Unable to add payment method to your account.', 'woocommerce'), 'error');
                    wp_redirect(wc_get_account_endpoint_url('payment-methods'));
                    exit();
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_advanced_credit_card_setup_tokens() {
        try {
            $body_request = array();
            $customer = WC()->customer;
            $first_name = $customer->get_billing_first_name();
            $last_name = $customer->get_billing_last_name();
            $address_1 = $customer->get_billing_address_1();
            $address_2 = $customer->get_billing_address_2();
            $city = $customer->get_billing_city();
            $state = $customer->get_billing_state();
            $postcode = $customer->get_billing_postcode();
            $country = strtoupper($customer->get_billing_country());
            $name = $first_name . ' ' . $last_name;
            // TODO verify this change
            if (!empty($name)) {
                $body_request['payment_source']['card'] = array(
                    'name' => $name
                );
            }
            if (!empty($country) && !empty($postcode) && !empty($city)) {
                $body_request['payment_source']['card']['billing_address'] = array(
                    'address_line_1' => $address_1,
                    'address_line_2' => $address_2,
                    'admin_area_1' => $state,
                    'admin_area_2' => $city,
                    'postal_code' => $postcode,
                    'country_code' => $country
                );
            }
            $body_request['payment_source']['card']['experience_context'] = array(
                'brand_name' => $this->brand_name,
                'locale' => 'en-US',
                'return_url' => add_query_arg(array('angelleye_ppcp_action' => 'advanced_credit_card_create_payment_token', 'utm_nooverride' => '1', 'customer_id' => get_current_user_id()), untrailingslashit(WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action'))),
                'cancel_url' => wc_get_account_endpoint_url('add-payment-method')
            );
            $paypal_generated_customer_id = $this->ppcp_payment_token->angelleye_ppcp_get_paypal_generated_customer_id($this->is_sandbox);
            if (!empty($paypal_generated_customer_id)) {
                $body_request['customer']['id'] = $paypal_generated_customer_id;
            }
            $args = array(
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'body' => $body_request
            );
            $this->api_response = $this->api_request->request($this->setup_tokens_url, $args, 'setup tokens');
            if (ob_get_length()) {
                ob_end_clean();
            }
            if (!empty($this->api_response['id'])) {
                $return_response['id'] = $this->api_response['id'];
                wp_send_json($return_response, 200);
            } else {
                $error_email_notification_param = array(
                    'request' => 'setup_tokens'
                );
                $error_message = $this->angelleye_ppcp_get_readable_message($this->api_response, $error_email_notification_param);
                wc_add_notice($error_message, 'error');
                wp_send_json(array(
                    'result' => 'failure',
                    'redirect' => wc_get_account_endpoint_url('payment-methods')
                ));
            }
            exit();
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_advanced_credit_card_create_payment_token() {
        try {
            $body_request = array();
            if (isset($_GET[APPROVAL_TOKEN_ID_PARAM_NAME])) {
                $body_request['payment_source']['token'] = array(
                    'id' => wc_clean($_GET[APPROVAL_TOKEN_ID_PARAM_NAME]),
                    'type' => 'SETUP_TOKEN'
                );
                $args = array(
                    'method' => 'POST',
                    'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                    'body' => $body_request
                );
                $this->api_response = $this->api_request->request($this->payment_tokens_url, $args, 'create_payment_token');
                if (ob_get_length()) {
                    ob_end_clean();
                }
                if (!empty($this->api_response['id'])) {
                    $customer_id = $this->api_response['customer']['id'] ?? '';
                    if (isset($customer_id) && !empty($customer_id)) {
                        $this->ppcp_payment_token->angelleye_ppcp_add_paypal_generated_customer_id($customer_id, $this->is_sandbox);
                    }
                    if (angelleye_ppcp_get_token_id_by_token($this->api_response['id']) === '') {
                        $token = new WC_Payment_Token_CC();
                        $wc_customer_id = get_current_user_id();
                        $token->set_token($this->api_response['id']);
                        $token->set_gateway_id('angelleye_ppcp_cc');
                        $token->set_card_type($this->api_response['payment_source']['card']['brand']);
                        $token->set_last4($this->api_response['payment_source']['card']['last_digits']);
                        if (isset($this->api_response['payment_source']['card']['expiry'])) {
                            $card_expiry = array_map('trim', explode('-', $this->api_response['payment_source']['card']['expiry']));
                            $card_exp_year = str_pad($card_expiry[0], 4, "0", STR_PAD_LEFT);
                            $card_exp_month = $card_expiry[1] ?? '';
                            $token->set_expiry_month($card_exp_month);
                            $token->set_expiry_year($card_exp_year);
                        } else {
                            $card_details = $this->angelleye_ppcp_get_payment_token_details($this->api_response['id']);
                            if (isset($card_details['payment_source']['card']['expiry'])) {
                                $card_expiry = array_map('trim', explode('-', $card_details['payment_source']['card']['expiry']));
                                $card_exp_year = str_pad($card_expiry[0], 4, "0", STR_PAD_LEFT);
                                $card_exp_month = $card_expiry[1] ?? '';
                                $token->set_expiry_month($card_exp_month);
                                $token->set_expiry_year($card_exp_year);
                            } else {
                                $token->set_expiry_month(date('m'));
                                $token->set_expiry_year(date('Y', strtotime('+5 years')));
                            }
                        }
                        $token->set_user_id($wc_customer_id);
                        if ($token->validate()) {
                            $token->save();
                            update_metadata('payment_token', $token->get_id(), '_angelleye_ppcp_used_payment_method', 'card');
                            wc_add_notice(__('Payment method has been added successfully.', 'woocommerce'));
                        } else {
                            wc_add_notice(__('Unable to add payment method to your account.', 'woocommerce'), 'error');
                        }
                    } else {
                        wc_add_notice(__('Payment method already exist in your account.', 'woocommerce'), 'notice');
                    }
                    wp_send_json(array(
                        'result' => 'success',
                        'redirect' => wc_get_account_endpoint_url('payment-methods'),
                    ));

                    exit();
                } else {
                    $error_email_notification_param = array(
                        'request' => 'create_payment_token'
                    );
                    $error_message = $this->angelleye_ppcp_get_readable_message($this->api_response, $error_email_notification_param);
                    wc_add_notice($error_message, 'error');
                    wc_add_notice(__('Unable to add payment method to your account.', 'woocommerce'), 'error');
                    wp_send_json(array(
                        'result' => 'failure',
                        'redirect' => wc_get_account_endpoint_url('payment-methods'),
                    ));
                    exit();
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_advanced_credit_card_create_payment_token_free_signup_with_free_trial() {
        try {
            $body_request = array();
            if (isset($_GET[APPROVAL_TOKEN_ID_PARAM_NAME]) && isset($_GET['order_id'])) {
                $body_request['payment_source']['token'] = array(
                    'id' => wc_clean($_GET[APPROVAL_TOKEN_ID_PARAM_NAME]),
                    'type' => 'SETUP_TOKEN'
                );
                $args = array(
                    'method' => 'POST',
                    'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                    'body' => $body_request
                );
                $this->api_response = $this->api_request->request($this->payment_tokens_url, $args, 'create_payment_token');
                if (ob_get_length()) {
                    ob_end_clean();
                }
                if (!empty($this->api_response['id'])) {
                    $customer_id = $this->api_response['customer']['id'] ?? '';
                    if (isset($customer_id) && !empty($customer_id)) {
                        $this->ppcp_payment_token->angelleye_ppcp_add_paypal_generated_customer_id($customer_id, $this->is_sandbox);
                    }
                    $order_id = wc_clean($_GET['order_id']);
                    $order = wc_get_order(wc_clean($_GET['order_id']));
                    $order->update_meta_data('_angelleye_ppcp_used_payment_method', 'card');
                    $order->save();
                    $this->save_payment_token($order, $this->api_response['id']);
                    if (angelleye_ppcp_get_token_id_by_token($this->api_response['id']) === '') {
                        $token = new WC_Payment_Token_CC();
                        if (0 != $order->get_user_id()) {
                            $customer_id = $order->get_user_id();
                        } else {
                            $customer_id = get_current_user_id();
                        }
                        $token->set_token($this->api_response['id']);
                        $token->set_gateway_id($order->get_payment_method());
                        $token->set_card_type($this->api_response['payment_source']['card']['brand']);
                        $token->set_last4($this->api_response['payment_source']['card']['last_digits']);
                        if (isset($this->api_response['payment_source']['card']['expiry'])) {
                            $card_expiry = array_map('trim', explode('-', $this->api_response['payment_source']['card']['expiry']));
                            $card_exp_year = str_pad($card_expiry[0], 4, "0", STR_PAD_LEFT);
                            $card_exp_month = $card_expiry[1] ?? '';
                            $token->set_expiry_month($card_exp_month);
                            $token->set_expiry_year($card_exp_year);
                        } else {
                            $card_details = $this->angelleye_ppcp_get_payment_token_details($this->api_response['id']);
                            if (isset($card_details['payment_source']['card']['expiry'])) {
                                $card_expiry = array_map('trim', explode('-', $card_details['payment_source']['card']['expiry']));
                                $card_exp_year = str_pad($card_expiry[0], 4, "0", STR_PAD_LEFT);
                                $card_exp_month = $card_expiry[1] ?? '';
                                $token->set_expiry_month($card_exp_month);
                                $token->set_expiry_year($card_exp_year);
                            } else {
                                $token->set_expiry_month(date('m'));
                                $token->set_expiry_year(date('Y', strtotime('+5 years')));
                            }
                        }
                        $token->set_user_id($customer_id);
                        if ($token->validate()) {
                            $token->save();
                            update_metadata('payment_token', $token->get_id(), '_angelleye_ppcp_used_payment_method', 'card');
                            $order->payment_complete();
                            WC()->cart->empty_cart();
                            wp_redirect($this->angelleye_ppcp_get_order_return_url($order));
                            exit();
                        } else {
                            wc_add_notice(__('Unable to add payment method to your account.', 'woocommerce'), 'error');
                        }
                    } else {
                        $order->payment_complete();
                        WC()->cart->empty_cart();
                        wp_redirect($this->angelleye_ppcp_get_order_return_url($order));
                        exit();
                    }
                    wp_redirect(wc_get_checkout_url());
                    exit();
                } else {
                    $error_email_notification_param = array(
                        'request' => 'create_payment_token'
                    );
                    $error_message = $this->angelleye_ppcp_get_readable_message($this->api_response, $error_email_notification_param);
                    wc_add_notice($error_message, 'error');
                    wc_add_notice(__('Unable to add payment method to your account.', 'woocommerce'), 'error');
                    wp_redirect(wc_get_checkout_url());
                    exit();
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_advanced_credit_card_setup_tokens_free_signup_with_free_trial($posted_card, $order_id) {
        try {
            $body_request = array();
            $customer = WC()->customer;
            $first_name = $customer->get_billing_first_name();
            $last_name = $customer->get_billing_last_name();
            $address_1 = $customer->get_billing_address_1();
            $address_2 = $customer->get_billing_address_2();
            $city = $customer->get_billing_city();
            $state = $customer->get_billing_state();
            $postcode = $customer->get_billing_postcode();
            $country = strtoupper($customer->get_billing_country());
            $name = $first_name . ' ' . $last_name;
            $body_request['payment_source']['card'] = array(
                'number' => $posted_card->number,
                'expiry' => $posted_card->exp_year . '-' . $posted_card->exp_month,
                'name' => $name
            );
            if (!empty($country) && !empty($postcode) && !empty($city)) {
                $body_request['payment_source']['card']['billing_address'] = array(
                    'address_line_1' => $address_1,
                    'address_line_2' => $address_2,
                    'admin_area_1' => $state,
                    'admin_area_2' => $city,
                    'postal_code' => $postcode,
                    'country_code' => $country
                );
            }
            $body_request['payment_source']['card']['verification_method'] = 'SCA_WHEN_REQUIRED';
            $body_request['payment_source']['card']['experience_context'] = array(
                'brand_name' => $this->brand_name,
                'locale' => 'en-US',
                'return_url' => add_query_arg(array('angelleye_ppcp_action' => 'advanced_credit_card_create_payment_token_free_signup_with_free_trial', 'utm_nooverride' => '1', 'customer_id' => get_current_user_id(), 'order_id' => $order_id), untrailingslashit(WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action'))),
                'cancel_url' => wc_get_checkout_url()
            );
            $body_request['payment_source']['card']['stored_credential'] = array(
                'payment_initiator' => 'CUSTOMER',
                'payment_type' => 'UNSCHEDULED',
                'usage' => 'SUBSEQUENT'
            );
            $paypal_generated_customer_id = $this->ppcp_payment_token->angelleye_ppcp_get_paypal_generated_customer_id($this->is_sandbox);
            if (!empty($paypal_generated_customer_id)) {
                $body_request['customer']['id'] = $paypal_generated_customer_id;
            }
            $args = array(
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'body' => $body_request
            );
            $this->api_response = $this->api_request->request($this->setup_tokens_url, $args, 'setup tokens');
            if (ob_get_length()) {
                ob_end_clean();
            }
            if (!empty($this->api_response['id'])) {
                if (isset($this->api_response['status']) && 'APPROVED' === $this->api_response['status']) {
                    return array(
                        'result' => 'success',
                        'redirect' => add_query_arg(array(APPROVAL_TOKEN_ID_PARAM_NAME => $this->api_response['id'], 'angelleye_ppcp_action' => 'advanced_credit_card_create_payment_token_free_signup_with_free_trial', 'utm_nooverride' => '1', 'customer_id' => get_current_user_id(), 'order_id' => $order_id), untrailingslashit(WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action')))
                    );
                } elseif (isset($this->api_response['status']) && 'PAYER_ACTION_REQUIRED' === $this->api_response['status']) {
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
                }

                return array(
                    'result' => 'failure',
                    'redirect' => wc_get_checkout_url()
                );
            } else {
                $error_email_notification_param = array(
                    'request' => 'setup_tokens'
                );
                $error_message = $this->angelleye_ppcp_get_readable_message($this->api_response, $error_email_notification_param);
                wc_add_notice($error_message, 'error');
                return array(
                    'result' => 'failure',
                    'redirect' => wc_get_checkout_url()
                );
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_advanced_credit_card_setup_tokens_sub_change_payment($posted_card, $order_id) {
        try {
            $body_request = array();
            $customer = WC()->customer;
            $first_name = $customer->get_billing_first_name();
            $last_name = $customer->get_billing_last_name();
            $address_1 = $customer->get_billing_address_1();
            $address_2 = $customer->get_billing_address_2();
            $city = $customer->get_billing_city();
            $state = $customer->get_billing_state();
            $postcode = $customer->get_billing_postcode();
            $country = strtoupper($customer->get_billing_country());
            $name = $first_name . ' ' . $last_name;
            $order = wc_get_order($order_id);
            $body_request['payment_source']['card'] = array(
                'number' => $posted_card->number,
                'expiry' => $posted_card->exp_year . '-' . $posted_card->exp_month,
                'name' => $name
            );
            if (!empty($country) && !empty($postcode) && !empty($city)) {
                $body_request['payment_source']['card']['billing_address'] = array(
                    'address_line_1' => $address_1,
                    'address_line_2' => $address_2,
                    'admin_area_1' => $state,
                    'admin_area_2' => $city,
                    'postal_code' => $postcode,
                    'country_code' => $country
                );
            }
            $body_request['payment_source']['card']['verification_method'] = 'SCA_WHEN_REQUIRED';
            $body_request['payment_source']['card']['experience_context'] = array(
                'brand_name' => $this->brand_name,
                'locale' => 'en-US',
                'return_url' => add_query_arg(array('angelleye_ppcp_action' => 'advanced_credit_card_create_payment_token_sub_change_payment', 'utm_nooverride' => '1', 'customer_id' => get_current_user_id(), 'order_id' => $order_id), untrailingslashit(WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action'))),
                'cancel_url' => wc_get_checkout_url()
            );
            $body_request['payment_source']['card']['stored_credential'] = array(
                'payment_initiator' => 'CUSTOMER',
                'payment_type' => 'UNSCHEDULED',
                'usage' => 'SUBSEQUENT'
            );
            $paypal_generated_customer_id = $this->ppcp_payment_token->angelleye_ppcp_get_paypal_generated_customer_id($this->is_sandbox);
            if (!empty($paypal_generated_customer_id)) {
                $body_request['customer']['id'] = $paypal_generated_customer_id;
            }
            $args = array(
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'body' => $body_request
            );
            $this->api_response = $this->api_request->request($this->setup_tokens_url, $args, 'setup tokens');
            if (ob_get_length()) {
                ob_end_clean();
            }
            if (!empty($this->api_response['id'])) {
                if (isset($this->api_response['status']) && 'APPROVED' === $this->api_response['status']) {
                    return array(
                        'result' => 'success',
                        'redirect' => add_query_arg(array(APPROVAL_TOKEN_ID_PARAM_NAME => $this->api_response['id'], 'angelleye_ppcp_action' => 'advanced_credit_card_create_payment_token_sub_change_payment', 'utm_nooverride' => '1', 'customer_id' => get_current_user_id(), 'order_id' => $order_id), untrailingslashit(WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action')))
                    );
                } elseif (isset($this->api_response['status']) && 'PAYER_ACTION_REQUIRED' === $this->api_response['status']) {
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
                }
                return array(
                    'result' => 'failure',
                    'redirect' => angelleye_ppcp_get_view_sub_order_url($order_id)
                );
            } else {
                $error_email_notification_param = array(
                    'request' => 'setup_tokens'
                );
                $error_message = $this->angelleye_ppcp_get_readable_message($this->api_response, $error_email_notification_param);
                wc_add_notice($error_message, 'error');
                return array(
                    'result' => 'failure',
                    'redirect' => angelleye_ppcp_get_view_sub_order_url($order_id)
                );
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_advanced_credit_card_create_payment_token_sub_change_payment() {
        try {
            $body_request = array();
            if (isset($_GET[APPROVAL_TOKEN_ID_PARAM_NAME]) && isset($_GET['order_id'])) {
                $body_request['payment_source']['token'] = array(
                    'id' => wc_clean($_GET[APPROVAL_TOKEN_ID_PARAM_NAME]),
                    'type' => 'SETUP_TOKEN'
                );
                $args = array(
                    'method' => 'POST',
                    'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                    'body' => $body_request
                );
                $this->api_response = $this->api_request->request($this->payment_tokens_url, $args, 'create_payment_token');
                if (ob_get_length()) {
                    ob_end_clean();
                }
                $order_id = wc_clean($_GET['order_id']);
                $order = wc_get_order(wc_clean($_GET['order_id']));
                if (!empty($this->api_response['id'])) {
                    $customer_id = $this->api_response['customer']['id'] ?? '';
                    if (isset($customer_id) && !empty($customer_id)) {
                        $this->ppcp_payment_token->angelleye_ppcp_add_paypal_generated_customer_id($customer_id, $this->is_sandbox);
                    }
                    $order->update_meta_data('_angelleye_ppcp_used_payment_method', 'card');
                    $order->save();
                    $this->save_payment_token($order, $this->api_response['id']);
                    if (angelleye_ppcp_get_token_id_by_token($this->api_response['id']) === '') {
                        $token = new WC_Payment_Token_CC();
                        if (0 != $order->get_user_id()) {
                            $customer_id = $order->get_user_id();
                        } else {
                            $customer_id = get_current_user_id();
                        }
                        $token->set_token($this->api_response['id']);
                        $token->set_gateway_id($order->get_payment_method());
                        $token->set_card_type($this->api_response['payment_source']['card']['brand']);
                        $token->set_last4($this->api_response['payment_source']['card']['last_digits']);
                        if (isset($this->api_response['payment_source']['card']['expiry'])) {
                            $card_expiry = array_map('trim', explode('-', $this->api_response['payment_source']['card']['expiry']));
                            $card_exp_year = str_pad($card_expiry[0], 4, "0", STR_PAD_LEFT);
                            $card_exp_month = $card_expiry[1] ?? '';
                            $token->set_expiry_month($card_exp_month);
                            $token->set_expiry_year($card_exp_year);
                        } else {
                            $card_details = $this->angelleye_ppcp_get_payment_token_details($this->api_response['id']);
                            if (isset($card_details['payment_source']['card']['expiry'])) {
                                $card_expiry = array_map('trim', explode('-', $card_details['payment_source']['card']['expiry']));
                                $card_exp_year = str_pad($card_expiry[0], 4, "0", STR_PAD_LEFT);
                                $card_exp_month = $card_expiry[1] ?? '';
                                $token->set_expiry_month($card_exp_month);
                                $token->set_expiry_year($card_exp_year);
                            } else {
                                $token->set_expiry_month(date('m'));
                                $token->set_expiry_year(date('Y', strtotime('+5 years')));
                            }
                        }
                        $token->set_user_id($customer_id);
                        if ($token->validate()) {
                            $token->save();
                            update_metadata('payment_token', $token->get_id(), '_angelleye_ppcp_used_payment_method', 'card');
                            wp_redirect(angelleye_ppcp_get_view_sub_order_url($order_id));
                            exit();
                        } else {
                            wc_add_notice(__('Unable to change payment method.', 'woocommerce'), 'error');
                        }
                    }
                    wp_redirect(angelleye_ppcp_get_view_sub_order_url($order_id));
                    exit();
                } else {
                    $error_email_notification_param = array(
                        'request' => 'create_payment_token'
                    );
                    $error_message = $this->angelleye_ppcp_get_readable_message($this->api_response, $error_email_notification_param);
                    wc_add_notice($error_message, 'error');
                    wp_redirect(angelleye_ppcp_get_view_sub_order_url($order_id));
                    exit();
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_paypal_setup_tokens_sub_change_payment($order_id) {
        try {
            $body_request = array();
            $body_request['payment_source']['paypal']['description'] = "Billing Agreement";
            $body_request['payment_source']['paypal']['permit_multiple_payment_tokens'] = true;
            $body_request['payment_source']['paypal']['usage_pattern'] = 'IMMEDIATE';
            $body_request['payment_source']['paypal']['usage_type'] = 'MERCHANT';
            $body_request['payment_source']['paypal']['customer_type'] = 'CONSUMER';
            $body_request['payment_source']['paypal']['experience_context'] = array(
                'shipping_preference' => 'GET_FROM_FILE',
                'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                'brand_name' => $this->brand_name,
                'locale' => 'en-US',
                'return_url' => add_query_arg(array('angelleye_ppcp_action' => 'paypal_create_payment_token_sub_change_payment', 'utm_nooverride' => '1', 'customer_id' => get_current_user_id(), 'order_id' => $order_id), untrailingslashit(WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action'))),
                'cancel_url' => wc_get_checkout_url()
            );
            $paypal_generated_customer_id = $this->ppcp_payment_token->angelleye_ppcp_get_paypal_generated_customer_id($this->is_sandbox);
            if (!empty($paypal_generated_customer_id)) {
                $body_request['customer']['id'] = $paypal_generated_customer_id;
            }
            $args = array(
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'body' => $body_request
            );
            $this->api_response = $this->api_request->request($this->setup_tokens_url, $args, 'setup tokens');
            if (ob_get_length()) {
                ob_end_clean();
            }
            if (!empty($this->api_response['id'])) {
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
                    'result' => 'failure',
                    'redirect' => angelleye_ppcp_get_view_sub_order_url($order_id)
                );
            } else {
                $error_email_notification_param = array(
                    'request' => 'setup_tokens'
                );
                $error_message = $this->angelleye_ppcp_get_readable_message($this->api_response, $error_email_notification_param);
                wc_add_notice($error_message, 'error');
                return array(
                    'result' => 'failure',
                    'redirect' => angelleye_ppcp_get_view_sub_order_url($order_id)
                );
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_paypal_create_payment_token_sub_change_payment() {
        try {
            $body_request = array();
            if (isset($_GET[APPROVAL_TOKEN_ID_PARAM_NAME]) && isset($_GET['order_id'])) {
                $body_request['payment_source']['token'] = array(
                    'id' => wc_clean($_GET[APPROVAL_TOKEN_ID_PARAM_NAME]),
                    'type' => 'SETUP_TOKEN'
                );
                $args = array(
                    'method' => 'POST',
                    'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                    'body' => $body_request
                );
                $this->api_response = $this->api_request->request($this->payment_tokens_url, $args, 'create_payment_token');
                if (ob_get_length()) {
                    ob_end_clean();
                }
                $order_id = wc_clean($_GET['order_id']);
                $order = wc_get_order(wc_clean($_GET['order_id']));
                if (!empty($this->api_response['id'])) {
                    $customer_id = $this->api_response['customer']['id'] ?? '';
                    if (isset($customer_id) && !empty($customer_id)) {
                        $this->ppcp_payment_token->angelleye_ppcp_add_paypal_generated_customer_id($customer_id, $this->is_sandbox);
                    }
                    $order->update_meta_data('_angelleye_ppcp_used_payment_method', 'paypal');
                    $order->save();
                    $this->save_payment_token($order, $this->api_response['id']);
                    if (angelleye_ppcp_get_token_id_by_token($this->api_response['id']) === '') {
                        $token = new WC_Payment_Token_CC();
                        if (0 != $order->get_user_id()) {
                            $wc_customer_id = $order->get_user_id();
                        } else {
                            $wc_customer_id = get_current_user_id();
                        }
                        if (isset($this->api_response['payment_source']['paypal']['email_address'])) {
                            $email_address = $this->api_response['payment_source']['paypal']['email_address'];
                        } elseif ($this->api_response['payment_source']['paypal']['payer_id']) {
                            $email_address = $this->api_response['payment_source']['paypal']['payer_id'];
                        } else {
                            $email_address = 'PayPal Vault';
                        }
                        $token->set_token($this->api_response['id']);
                        $token->set_gateway_id($order->get_payment_method());
                        $token->set_card_type('PayPal Vault');
                        $token->set_last4(substr($this->api_response['id'], -4));
                        $token->set_expiry_month(date('m'));
                        $token->set_expiry_year(date('Y', strtotime('+20 years')));
                        $token->set_user_id($wc_customer_id);
                        if ($token->validate()) {
                            $token->save();
                            update_metadata('payment_token', $token->get_id(), '_angelleye_ppcp_used_payment_method', 'paypal');
                            wp_redirect(angelleye_ppcp_get_view_sub_order_url($order_id));
                            exit();
                        } else {
                            $order->add_order_note('ERROR MESSAGE: ' . __('Invalid or missing payment token fields.', 'paypal-for-woocommerce'));
                        }
                    }
                    wp_redirect(angelleye_ppcp_get_view_sub_order_url($order_id));
                    exit();
                } else {
                    $error_email_notification_param = array(
                        'request' => 'create_payment_token'
                    );
                    $error_message = $this->angelleye_ppcp_get_readable_message($this->api_response, $error_email_notification_param);
                    wc_add_notice($error_message, 'error');
                    wp_redirect(angelleye_ppcp_get_view_sub_order_url($order_id));
                    exit();
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_get_all_payment_tokens_for_renewal($user_id) {
        try {
            $paypal_generated_customer_id = $this->ppcp_payment_token->angelleye_ppcp_get_paypal_generated_customer_id_for_renewal($this->is_sandbox, $user_id);
            if ($paypal_generated_customer_id === false) {
                return false;
            }
            $args = array(
                'method' => 'GET',
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'body' => array()
            );
            $payment_tokens_url = add_query_arg(array('customer_id' => $paypal_generated_customer_id), untrailingslashit($this->payment_tokens_url));
            $api_response = $this->api_request->request($payment_tokens_url, $args, 'list_all_payment_tokens');
            if (ob_get_length()) {
                ob_end_clean();
            }
            if (!empty($api_response['customer']['id']) && isset($api_response['payment_tokens'])) {
                return $api_response['payment_tokens'];
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_get_all_payment_tokens() {
        try {
            $paypal_generated_customer_id = $this->ppcp_payment_token->angelleye_ppcp_get_paypal_generated_customer_id($this->is_sandbox);
            if ($paypal_generated_customer_id === false) {
                return false;
            }
            $args = array(
                'method' => 'GET',
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'body' => array()
            );
            $payment_tokens_url = add_query_arg(array('customer_id' => $paypal_generated_customer_id), untrailingslashit($this->payment_tokens_url));
            $api_response = $this->api_request->request($payment_tokens_url, $args, 'list_all_payment_tokens');
            if (!empty($api_response['customer']['id']) && isset($api_response['payment_tokens'])) {
                return $api_response['payment_tokens'];
            } else {
                return array();
            }
        } catch (Exception $ex) {
            return array();
        }
    }

    public function angelleye_ppcp_get_payment_token_details($id) {
        try {
            $args = array(
                'method' => 'GET',
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'body' => array()
            );
            $api_response = $this->api_request->request($this->payment_tokens_url . '/' . $id, $args, 'get_payment_token_details');
            if (ob_get_length()) {
                ob_end_clean();
            }
            if (!empty($api_response['customer']['id']) && isset($api_response['payment_source'])) {
                return $api_response;
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_add_payment_source($body_request, $order_id) {
        try {
            $order = wc_get_order($order_id);
            $user_id = (int) $order->get_customer_id();
            $all_payment_tokens = $this->angelleye_ppcp_get_all_payment_tokens_for_renewal($user_id);
            $payment_tokens_id = angelleye_ppcp_get_post_meta($order, '_payment_tokens_id', true);
            if (empty($payment_tokens_id)) {
                $payment_tokens_id = angelleye_ppcp_get_post_meta($order, 'payment_token_id', true);
            }
            if (empty($payment_tokens_id)) {
                $payment_tokens_id = angelleye_ppcp_get_post_meta($order, '_ppec_billing_agreement_id', true);
            }
            $paypal_subscription_id = angelleye_ppcp_get_post_meta($order, '_paypal_subscription_id', true);
            if (empty($all_payment_tokens) && empty($payment_tokens_id) && empty($paypal_subscription_id)) {
                return $body_request;
            } elseif (!empty($paypal_subscription_id)) {
                $payment_tokens_id = $paypal_subscription_id;
            }
            if (!empty($all_payment_tokens) && !empty($payment_tokens_id)) {
                foreach ($all_payment_tokens as $key => $paypal_payment_token) {
                    if ($paypal_payment_token['id'] === $payment_tokens_id) {
                        foreach ($paypal_payment_token['payment_source'] as $type_key => $payment_tokens_data) {
                            $body_request['payment_source'] = array($type_key => array('vault_id' => $payment_tokens_id));
                            $this->applyStoredCredentialParameter($type_key, $body_request);
                            $angelleye_ppcp_payment_method_title = angelleye_ppcp_get_payment_method_title($type_key);
                            $order->set_payment_method_title($angelleye_ppcp_payment_method_title);
                            $order->update_meta_data('_angelleye_ppcp_used_payment_method', $type_key);
                            $order->save();
                            return $body_request;
                        }
                    }
                }
            }
            if (!empty($all_payment_tokens)) {
                foreach ($all_payment_tokens as $key => $paypal_payment_token) {
                    foreach ($paypal_payment_token['payment_source'] as $type_key => $payment_tokens_data) {
                        $order->update_meta_data('_payment_tokens_id', $paypal_payment_token['id']);
                        $body_request['payment_source'] = array($type_key => array('vault_id' => $paypal_payment_token['id']));
                        $this->applyStoredCredentialParameter($type_key, $body_request);
                        $angelleye_ppcp_payment_method_title = angelleye_ppcp_get_payment_method_title($type_key);
                        $order->set_payment_method_title($angelleye_ppcp_payment_method_title);
                        $order->update_meta_data('_angelleye_ppcp_used_payment_method', $type_key);
                        $order->save();
                        return $body_request;
                    }
                }
            }
            $angelleye_ppcp_old_payment_method = angelleye_ppcp_get_post_meta($order, '_angelleye_ppcp_old_payment_method', true);
            if (empty($angelleye_ppcp_old_payment_method)) {
                $angelleye_ppcp_old_payment_method = angelleye_ppcp_get_post_meta($order, '_old_payment_method', true);
            }
            if (!empty($angelleye_ppcp_old_payment_method)) {
                $tokenType = '';
                switch ($angelleye_ppcp_old_payment_method) {
                    case 'paypal_express':
                    case 'paypal':
                    case 'ppec_paypal':
                    case 'paypal_credit_card_rest':
                        $tokenType = 'BILLING_AGREEMENT';
                        break;
                    case 'paypal_advanced':
                    case 'paypal_pro_payflow':
                        $tokenType = 'PNREF';
                        break;
                    case 'paypal_pro':
                        $tokenType = 'PAYPAL_TRANSACTION_ID';
                        break;
                }

                if (!empty($tokenType)) {
                    $body_request['payment_source'] = [
                        'token' => ['id' => $payment_tokens_id, 'type' => $tokenType]
                    ];
                }
            }

            if (!isset($body_request['payment_source'])) {
                if (empty($all_payment_tokens) && !empty($payment_tokens_id)) {
                    $payment_method = angelleye_ppcp_get_post_meta($order, '_angelleye_ppcp_used_payment_method', true);
                    if (in_array($payment_method, ['PayPal Checkout', 'PayPal Credit'])) {
                        $payment_method = 'paypal';
                    }
                    $body_request['payment_source'] = array($payment_method => array('vault_id' => $payment_tokens_id));
                    $this->applyStoredCredentialParameter($payment_method, $body_request);
                } elseif (!empty($payment_tokens_id)) {
                    $body_request['payment_source'] = array('paypal' => array('vault_id' => $payment_tokens_id));
                }
            }
        } catch (Exception $ex) {
            return $body_request;
        }
        $angelleye_ppcp_payment_method_title = angelleye_ppcp_get_payment_method_title($payment_method);
        $order->set_payment_method_title($angelleye_ppcp_payment_method_title);
        $order->save();
        return $body_request;
    }

    private function applyStoredCredentialParameter($paymentMethod, &$bodyRequest) {
        $storedCredentials = [];
        switch ($paymentMethod) {
            case 'card':
            case 'apple_pay':
                $storedCredentials = array(
                    'payment_initiator' => 'MERCHANT',
                    'payment_type' => 'UNSCHEDULED',
                    'usage' => 'SUBSEQUENT'
                );
                break;
        }
        if (!empty($storedCredentials)) {
            $bodyRequest['payment_source'][$paymentMethod]['stored_credential'] = $storedCredentials;
        }
    }

    public function angelleye_ppcp_delete_payment_token($payment_token) {
        try {
            $args = array(
                'method' => 'DELETE',
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'body' => array()
            );
            $this->api_request->request($this->payment_tokens_url . '/' . $payment_token, $args, 'delete_payment_token');
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_get_all_payment_tokens_by_user_id($user_id) {
        try {
            $paypal_generated_customer_id = $this->ppcp_payment_token->angelleye_ppcp_get_paypal_generated_customer_id_by_user_id($this->is_sandbox, $user_id);
            if ($paypal_generated_customer_id === false) {
                return false;
            }
            $args = array(
                'method' => 'GET',
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'body' => array()
            );
            $payment_tokens_url = add_query_arg(array('customer_id' => $paypal_generated_customer_id), untrailingslashit($this->payment_tokens_url));
            $api_response = $this->api_request->request($payment_tokens_url, $args, 'list_all_payment_tokens');
            if (!empty($api_response['customer']['id']) && isset($api_response['payment_tokens'])) {
                return $api_response['payment_tokens'];
            } else {
                return array();
            }
        } catch (Exception $ex) {
            return array();
        }
    }

    public function angelleye_ppcp_prepare_refund_request_data_for_capture($order, $amount) {
        try {
            $ppcp_refunded_amount = 0;
            $prepare_refund_data = [];
            $used_transaction_id = [];
            $closest_item_id = '';
            $refund_amount = isset($_POST['refund_amount']) ? wc_format_decimal(sanitize_text_field(wp_unslash($_POST['refund_amount'])), wc_get_price_decimals()) : 0;
            $line_item_totals = !empty($_POST['line_item_totals']) ? json_decode(sanitize_text_field(wp_unslash($_POST['line_item_totals'])), true) : [];
            $capture_data_list = $this->angelleye_ppcp_get_capture_data_with_line_item_id($order);
            foreach ($line_item_totals as $item_id => $amount) {
                if ($amount > 0 && isset($capture_data_list[$item_id])) {
                    foreach ($capture_data_list[$item_id] as $transaction_id => $capture_amount) {
                        $remaining_refund = $refund_amount - $ppcp_refunded_amount;
                        if ($remaining_refund <= 0) {
                            return $prepare_refund_data;
                        }
                        $transaction_data = [];
                        foreach ($capture_data_list[$item_id] as $temp_transaction_id => $temp_capture_amount) {
                            if (!array_key_exists($temp_transaction_id, $used_transaction_id)) {
                                if ($temp_capture_amount > 0) {
                                    $transaction_data[] = $temp_capture_amount;
                                }
                            }
                        }
                        if (!empty($transaction_data)) {
                            sort($transaction_data);
                            $refund_to_add = min($remaining_refund, $amount);
                            $closest_amount = angelleye_ppcp_binary_search($transaction_data, $refund_to_add);
                            if ($closest_amount !== null) {
                                foreach ($capture_data_list[$item_id] as $temp_transaction_id => $temp_capture_amount) {
                                    if (!array_key_exists($temp_transaction_id, $used_transaction_id)) {
                                        if ($closest_amount == $temp_capture_amount) {
                                            $closest_transaction_id = $temp_transaction_id;
                                            break;
                                        }
                                    }
                                }
                                if (!empty($closest_transaction_id)) {
                                    $refund_to_add = min($remaining_refund, $closest_amount);
                                    $prepare_refund_data[$item_id][$closest_transaction_id] = $refund_to_add;
                                    $ppcp_refunded_amount += $refund_to_add;
                                    $used_transaction_id[$closest_transaction_id] = $refund_to_add;
                                }
                            } else {
                                $refund_to_add = min($remaining_refund, $amount, $capture_amount);
                                $prepare_refund_data[$item_id][$transaction_id] = $refund_to_add;
                                $ppcp_refunded_amount += $refund_to_add;
                                $used_transaction_id[$transaction_id] = $refund_to_add;
                            }
                        }
                    }
                }
            }
            if ($refund_amount > $ppcp_refunded_amount) {
                foreach ($line_item_totals as $item_id => $amount) {
                    if ($amount > 0 && isset($capture_data_list[$item_id])) {
                        $remaining_refund = $refund_amount - $ppcp_refunded_amount;
                        if ($remaining_refund <= 0) {
                            return $prepare_refund_data;
                        }
                        foreach ($capture_data_list[$item_id] as $transaction_id => $capture_amount) {
                            if ($capture_amount - $used_transaction_id[$transaction_id] > 0) {
                                $prepare_refund_data[$item_id][$transaction_id] = $capture_amount - $used_transaction_id[$transaction_id];
                                $ppcp_refunded_amount += $capture_amount - $used_transaction_id[$transaction_id];
                                $used_transaction_id[$closest_transaction_id] = $capture_amount - $used_transaction_id[$transaction_id];
                            }
                        }
                    }
                }
            }
            if ($refund_amount > $ppcp_refunded_amount) {
                $capture_data_list = $this->angelleye_ppcp_get_capture_data($order);
                if (!empty($capture_data_list)) {
                    foreach ($capture_data_list as $transaction_id => $capture_amount) {
                        $refund_to_add = $refund_amount - $ppcp_refunded_amount;
                        if ($refund_to_add <= 0) {
                            return $prepare_refund_data;
                        }
                        $transaction_data = [];
                        foreach ($capture_amount as $temp_transaction_id => $temp_capture_amount) {
                            if (!array_key_exists($temp_transaction_id, $used_transaction_id)) {
                                if ($temp_capture_amount > 0) {
                                    $transaction_data[] = $temp_capture_amount;
                                }
                            }
                        }
                        if (!empty($transaction_data)) {
                            sort($transaction_data);
                            $closest_amount = angelleye_ppcp_binary_search($transaction_data, $refund_to_add);
                            if ($closest_amount !== null) {
                                foreach ($capture_data_list as $inner_item_is => $inner_capture_data) {
                                    foreach ($inner_capture_data as $inner_transaction_id => $inner_transaction_amount) {
                                        if (!array_key_exists($inner_transaction_id, $used_transaction_id)) {
                                            if ($closest_amount == $inner_transaction_amount) {
                                                $closest_transaction_id = $inner_transaction_id;
                                                $closest_item_id = $inner_item_is;
                                                break;
                                            }
                                        }
                                    }
                                }
                                if (!empty($closest_transaction_id)) {
                                    $refund_to_add = min($refund_to_add, $closest_amount);
                                    $prepare_refund_data[$closest_item_id][$closest_transaction_id] = $refund_to_add;
                                    $ppcp_refunded_amount += $refund_to_add;
                                    $used_transaction_id[$closest_transaction_id] = $refund_to_add;
                                }
                            } else {
                                $refund_to_add = min($refund_to_add, $capture_amount);
                                $prepare_refund_data[$item_id][$transaction_id] = $refund_to_add;
                                $ppcp_refunded_amount += $refund_to_add;
                                $used_transaction_id[$transaction_id] = $refund_to_add;
                            }
                        }
                    }
                }
            }
            return $prepare_refund_data;
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_get_capture_data_with_line_item_id($order) {
        $capture_data_list = array();
        foreach ($order->get_items(array( 'line_item', 'tax', 'shipping', 'fee', 'coupon'  )) as $item) {
            if ($item->meta_exists('_ppcp_capture_details')) {
                $ppcp_capture_details = $item->get_meta('_ppcp_capture_details');
                if (!empty($ppcp_capture_details)) {
                    foreach ($ppcp_capture_details as $key => $capture_data) {
                        if (isset($capture_data['total_refund_amount'])) {
                            $capture_data_list[$item->get_id()][$capture_data['_ppcp_transaction_id']] = $capture_data['_ppcp_transaction_amount'] - $capture_data['total_refund_amount'];
                        } else {
                            $capture_data_list[$item->get_id()][$capture_data['_ppcp_transaction_id']] = $capture_data['_ppcp_transaction_amount'];
                        }
                    }
                }
            }
        }
        if (!empty($capture_data_list)) {
            foreach ($capture_data_list as &$capture_data) {
                asort($capture_data);
            }
        }
        return $capture_data_list;
    }

    public function angelleye_ppcp_get_capture_data($order) {
        $capture_data_list = array();
        foreach ($order->get_items(array( 'line_item', 'tax', 'shipping', 'fee', 'coupon'  )) as $item) {
            if ($item->meta_exists('_ppcp_capture_details')) {
                $ppcp_capture_details = $item->get_meta('_ppcp_capture_details');
                if (!empty($ppcp_capture_details)) {
                    foreach ($ppcp_capture_details as $key => $capture_data) {
                        if (isset($capture_data['total_refund_amount'])) {
                            $capture_data_list[$item->get_id()][$capture_data['_ppcp_transaction_id']] = $capture_data['_ppcp_transaction_amount'] - $capture_data['total_refund_amount'];
                        } else {
                            $capture_data_list[$item->get_id()][$capture_data['_ppcp_transaction_id']] = $capture_data['_ppcp_transaction_amount'];
                        }
                    }
                }
            }
        }
        if (!empty($capture_data_list)) {
            asort($capture_data_list);
        }
        return $capture_data_list;
    }

    public function angelleye_ppcp_refund_capture_order($order_id, $amount, $note_to_payer, $transaction_id, $item_id) {
        try {
            $order = wc_get_order($order_id);
            if ($order === false) {
                return false;
            }
            if (strlen($note_to_payer) > 255) {
                $note_to_payer = substr($note_to_payer, 0, 252) . '...';
            }
            $order_id = $order->get_id();
            $decimals = $this->angelleye_ppcp_get_number_of_decimal_digits();
            $reason = !empty($reason) ? $reason : 'Refund';
            $body_request['note_to_payer'] = $reason;
            $currency_code = angelleye_ppcp_get_currency($order_id);
            if (!empty($amount) && $amount > 0) {
                $body_request['amount'] = array(
                    'value' => angelleye_ppcp_round($amount, $decimals),
                    'currency_code' => apply_filters('angelleye_ppcp_woocommerce_currency', $currency_code, $amount)
                );
            }
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
                $gross_amount = $this->api_response['seller_payable_breakdown']['gross_amount']['value'] ?? '';
                $refund_transaction_id = $this->api_response['id'] ?? '';
                $order->add_order_note(
                        sprintf(__('Refunded %1$s - Refund ID: %2$s', 'paypal-for-woocommerce'), wc_price($gross_amount, array('currency' => $currency_code)), $refund_transaction_id)
                );
                $refund_date = date('m/d/y H:i', strtotime($this->api_response['update_time']));
                $ppcp_refund_details[] = array(
                    '_ppcp_refund_id' => $refund_transaction_id,
                    '_ppcp_refund_date' => $refund_date,
                    '_ppcp_refund_amount' => $gross_amount
                );
                $_ppcp_refund_details = wc_get_order_item_meta($item_id, '_ppcp_refund_details', true);
                if (!empty($_ppcp_refund_details)) {
                    $ppcp_refund_details = array_merge($_ppcp_refund_details, $ppcp_refund_details);
                }
                wc_update_order_item_meta($item_id, '_ppcp_refund_details', $ppcp_refund_details);
                $this->angelleye_ppcp_update_capture_details($transaction_id, $refund_transaction_id, $gross_amount, $item_id);
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
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
            return new WP_Error('error', $ex->getMessage());
        }
    }

    public function angelleye_ppcp_get_capture_details($capture_id) {
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
            $api_response = $this->api_request->request($this->paypal_refund_api . $capture_id, $args, 'get_capture');
            $api_response = json_decode(json_encode($api_response), true);
            if (isset($api_response['id'])) {
                return $api_response;
            }
            $this->api_log->log("Unable to find the PayPal capture: " . $capture_id, 'error');
            $this->api_log->log(print_r($api_response, true), 'error');
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_sync_ppcp_capture_details($order_id) {
        try {
            $order = wc_get_order($order_id);
            if ($order === false) {
                return false;
            }
            $capture_data_list = $this->angelleye_ppcp_get_capture_data_with_line_item_id($order);
            if (!empty($capture_data_list)) {
                foreach ($capture_data_list as $item_id => $capture) {
                    foreach ($capture as $capture_id => $capture_amount) {
                        $capture_details = $this->angelleye_ppcp_get_capture_details($capture_id);
                        if (!empty($capture_details)) {
                            
                        }
                    }
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function ppcp_send_paypal_tracking_info($body_request, $request_url) {
        try {
            $args = array(
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'body' => $body_request
            );
            $this->api_response = $this->api_request->request($request_url, $args, 'track_order');
            if (ob_get_length()) {
                ob_end_clean();
            }
            return $this->api_response;
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_update_capture_details($capture_id, $refund_id, $refund_amount, $item_id) {
        try {
            $ppcp_capture = wc_get_order_item_meta($item_id, '_ppcp_capture_details', true);
            if (empty($ppcp_capture)) {
                return;
            }
            foreach ($ppcp_capture as $key => $ppcp_capture_details) {
                if ($capture_id === $ppcp_capture_details['_ppcp_transaction_id']) {
                    $ppcp_capture[$key]['refund'][] = array('refund_id' => $refund_id, 'refund_amount' => $refund_amount);
                    if (isset($ppcp_capture[$key]['total_refund_amount'])) {
                        $ppcp_capture[$key]['total_refund_amount'] = $ppcp_capture[$key]['total_refund_amount'] + $refund_amount;
                    } else {
                        $ppcp_capture[$key]['total_refund_amount'] = $refund_amount;
                    }
                }
            }
            wc_update_order_item_meta($item_id, '_ppcp_capture_details', $ppcp_capture);
        } catch (Exception $ex) {
            
        }
    }

    public function get_preferred_order_status($payment_status, $order_id) {
        if ($this->has_pre_order($order_id)) {
            return 'pre-ordered';
        }
        return $this->paymentstatus === 'wc-default' ? strtolower($payment_status) : $this->paymentstatus;
    }
}
