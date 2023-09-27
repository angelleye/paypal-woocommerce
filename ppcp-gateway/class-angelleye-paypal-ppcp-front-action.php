<?php

defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Front_Action {

    public static bool $is_user_logged_in_before_checkout;
    public static string $checkout_started_from;
    private $angelleye_ppcp_plugin_name;
    public $api_log;
    public AngellEYE_PayPal_PPCP_Payment $payment_request;
    public $setting_obj;
    public AngellEYE_PayPal_PPCP_Smart_Button $smart_button;
    protected static $_instance = null;
    public $procceed = 1;
    public $reject = 2;
    public $retry = 3;
    public $is_sandbox;
    public $merchant_id;
    public $dcc_applies;
    public $paymentaction;
    public $title;
    public $advanced_card_payments;
    public $three_d_secure_contingency;
    public $product;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        self::$is_user_logged_in_before_checkout = function_exists('is_user_logged_in') && is_user_logged_in();
        $this->angelleye_ppcp_plugin_name = 'angelleye_ppcp';
        $this->angelleye_ppcp_load_class();
        $this->paymentaction = $this->setting_obj->get('paymentaction', 'capture');
        $this->title = $this->setting_obj->get('title', 'PayPal Commerce - Built by Angelleye');
        $this->advanced_card_payments = 'yes' === $this->setting_obj->get('enable_advanced_card_payments', 'no');
        $this->is_sandbox = 'yes' === $this->setting_obj->get('testmode', 'no');
        if ($this->dcc_applies->for_country_currency() === false) {
            $this->advanced_card_payments = false;
        }
        $this->three_d_secure_contingency = $this->setting_obj->get('3d_secure_contingency', 'SCA_WHEN_REQUIRED');
        if (!has_action('woocommerce_api_' . strtolower('AngellEYE_PayPal_PPCP_Front_Action'))) {
            add_action('woocommerce_api_' . strtolower('AngellEYE_PayPal_PPCP_Front_Action'), array($this, 'handle_wc_api'));
        }
        add_action('set_logged_in_cookie', [$this, 'handle_logged_in_cookie_nonce_on_checkout'], 1000, 6);
    }

    public function angelleye_ppcp_load_class() {
        try {
            if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-ppcp-angelleye-settings.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Log')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-log.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Payment')) {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-payment.php');
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_DCC_Validate')) {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-dcc-validate.php');
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Smart_Button')) {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-smart-button.php');
            }
            $this->setting_obj = WC_Gateway_PPCP_AngellEYE_Settings::instance();
            $this->api_log = AngellEYE_PayPal_PPCP_Log::instance();
            $this->payment_request = AngellEYE_PayPal_PPCP_Payment::instance();
            $this->dcc_applies = AngellEYE_PayPal_PPCP_DCC_Validate::instance();
            $this->smart_button = AngellEYE_PayPal_PPCP_Smart_Button::instance();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' .$ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    /**
     * This hook adds the logged in user data in cookie so that any function that creates the nonce will get latest
     * nonce data without needing to trigger another api call to get valid nonce.
     * @param $logged_in_cookie
     * @param $expire
     * @param $expiration
     * @param $user_id
     * @param $scheme
     * @param $token
     */
    function handle_logged_in_cookie_nonce_on_checkout($logged_in_cookie, $expire, $expiration, $user_id, $scheme, $token) {
        $_COOKIE[LOGGED_IN_COOKIE] = $logged_in_cookie;
    }

    public function handle_wc_api() {
        global $wp;
        if (!empty($_GET['angelleye_ppcp_action'])) {
            switch ($_GET['angelleye_ppcp_action']) {
                case "cancel_order":
                    AngellEye_Session_Manager::clear();
                    wp_redirect(wc_get_cart_url());
                    exit();
                case "create_order":
                    // check if billing and shipping details posted from frontend then update cart
                    global $woocommerce;
                    if (isset($_REQUEST['billing_address_source'])) {
                        $billing_address = json_decode(stripslashes($_REQUEST['billing_address_source']), true);
                        if (!empty($billing_address)) {
                            !empty($billing_address['addressLines'][0]) ? $woocommerce->customer->set_billing_address_1($billing_address['addressLines'][0]) : null;
                            !empty($billing_address['addressLines'][1]) ? $woocommerce->customer->set_billing_address_2($billing_address['addressLines'][1]) : null;
                            $woocommerce->customer->set_billing_first_name($billing_address['givenName']);
                            $woocommerce->customer->set_billing_last_name($billing_address['familyName']);
                            $woocommerce->customer->set_billing_postcode($billing_address['postalCode']);
                            $woocommerce->customer->set_billing_country($billing_address['countryCode']);
                            $woocommerce->customer->set_billing_city($billing_address['locality']);
                            $woocommerce->customer->set_billing_state($billing_address['administrativeArea']);
                        }
                    }
                    if (isset($_REQUEST['shipping_address_source'])) {
                        $shipping_address = json_decode(stripslashes($_REQUEST['shipping_address_source']), true);
                        if (!empty($shipping_address)) {
                            !empty($shipping_address['addressLines'][0]) ? $woocommerce->customer->set_shipping_address_1($shipping_address['addressLines'][0]) : null;
                            !empty($shipping_address['addressLines'][1]) ? $woocommerce->customer->set_shipping_address_2($shipping_address['addressLines'][1]) : null;
                            $woocommerce->customer->set_billing_email($shipping_address['emailAddress']);
                            $woocommerce->customer->set_shipping_first_name($shipping_address['givenName']);
                            $woocommerce->customer->set_shipping_last_name($shipping_address['familyName']);
                            $woocommerce->customer->set_shipping_postcode($shipping_address['postalCode']);
                            $woocommerce->customer->set_shipping_country($shipping_address['countryCode']);
                            $woocommerce->customer->set_shipping_city($shipping_address['locality']);
                            $woocommerce->customer->set_shipping_state($shipping_address['administrativeArea']);
                        }
                    }

                    $request_from_page = $_GET['from'] ?? '';
                    self::$checkout_started_from = $request_from_page;

                    if ('pay_page' === $request_from_page) {
                        $woo_order_id = $_POST['woo_order_id'];
                        if (isset(WC()->session) && !WC()->session->has_session()) {
                            WC()->session->set_customer_session_cookie(true);
                        }
                        WC()->session->set('order_awaiting_payment', $woo_order_id);
                        $order = wc_get_order($woo_order_id);
                        do_action('woocommerce_before_pay_action', $order);
                        $error_messages = wc_get_notices('error');
                        wc_clear_notices();
                        if (empty($error_messages)) {
                            $this->payment_request->angelleye_ppcp_create_order_request($woo_order_id);
                        } else {
                            $errors = [];
                            foreach ($error_messages as $error) {
                                $errors[] = $error['notice'];
                            }
                            ob_start();
                            wp_send_json_error(array('messages' => $errors));
                        }
                        exit();
                    } elseif ('checkout' === $request_from_page) {
                        if (isset($_POST) && !empty($_POST)) {
                            self::$is_user_logged_in_before_checkout = is_user_logged_in();
                            AngellEye_Session_Manager::set('checkout_post', wc_clean($_POST));
                            add_action('woocommerce_after_checkout_validation', array($this, 'maybe_start_checkout'), 10, 2);
                            WC()->checkout->process_checkout();
                            if (wc_notice_count('error') > 0) {
                                WC()->session->set('reload_checkout', true);
                                $error_messages_data = wc_get_notices('error');
                                $error_messages = array();
                                foreach ($error_messages_data as $key => $value) {
                                    $error_messages[] = $value['notice'];
                                }
                                wc_clear_notices();
                                ob_start();
                                wp_send_json_error(array('messages' => $error_messages));
                            }
                        } else {
                            $_GET['from'] = 'cart';
                            $this->payment_request->angelleye_ppcp_create_order_request();
                        }
                        exit();
                    } elseif ('product' === $request_from_page) {
                        try {
                            if (!class_exists('AngellEYE_PayPal_PPCP_Product')) {
                                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-product.php');
                            }
                            $paymentMethod = $_REQUEST['angelleye_ppcp_payment_method_title'] ?? null;
                            if (angelleye_ppcp_get_order_total() === 0 || $paymentMethod !== WC_Gateway_Apple_Pay_AngellEYE::PAYMENT_METHOD) {
                                $this->product = AngellEYE_PayPal_PPCP_Product::instance();
                                $this->product::angelleye_ppcp_add_to_cart_action();
                            }
                            if (angelleye_ppcp_get_order_total() === 0) {
                                $wc_notice = __('Sorry, your session has expired.', 'woocommerce');
                                wc_add_notice($wc_notice);
                                wp_send_json_error($wc_notice);
                            } else {
                                $this->payment_request->angelleye_ppcp_create_order_request();
                            }
                            exit();
                        } catch (Exception $ex) {
                            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' .$ex->getLine(), 'error');
                            $this->api_log->log($ex->getMessage(), 'error');
                        }
                    } else {
                        $this->payment_request->angelleye_ppcp_create_order_request();
                        exit();
                    }
                    break;
                case 'shipping_address_update':
                    global $woocommerce;
                    $paymentMethod = $_REQUEST['angelleye_ppcp_payment_method_title'] ?? null;
                    if (isset($_REQUEST['shipping_address_source'])) {
                        $shipping_address = json_decode(stripslashes($_REQUEST['shipping_address_source']), true);
                        $shipping_address = $shipping_address['shippingDetails'] ?? null;
                        if (!empty($shipping_address)) {
                            !empty($shipping_address['addressLines'][0]) ? $woocommerce->customer->set_shipping_address_1($shipping_address['addressLines'][0]) : null;
                            !empty($shipping_address['address1']) ? $woocommerce->customer->set_shipping_address_1($shipping_address['address1']) : null;
                            !empty($shipping_address['addressLines'][1]) ? $woocommerce->customer->set_shipping_address_2($shipping_address['addressLines'][1]) : null;
                            !empty($shipping_address['address2']) ? $woocommerce->customer->set_shipping_address_2($shipping_address['address2']) : null;
                            isset($shipping_address['emailAddress']) && $woocommerce->customer->set_billing_email($shipping_address['emailAddress']);
                            isset($shipping_address['givenName']) &&  $woocommerce->customer->set_shipping_first_name($shipping_address['givenName']);
                            if (isset($shipping_address['name'])) {
                                $splitName = angelleye_split_name($shipping_address['name']);
                                $woocommerce->customer->set_shipping_first_name($splitName[0]);
                                $woocommerce->customer->set_shipping_last_name($splitName[1]);
                            }
                            isset($shipping_address['familyName']) && $woocommerce->customer->set_shipping_last_name($shipping_address['familyName']);
                            isset($shipping_address['postalCode']) && $woocommerce->customer->set_shipping_postcode($shipping_address['postalCode']);
                            isset($shipping_address['countryCode']) && $woocommerce->customer->set_shipping_country($shipping_address['countryCode']);
                            isset($shipping_address['locality']) && $woocommerce->customer->set_shipping_city($shipping_address['locality']);
                            isset($shipping_address['administrativeArea']) && $woocommerce->customer->set_shipping_state($shipping_address['administrativeArea']);
                        }
                    }
                    if (isset($_REQUEST['billing_address_source'])) {
                        $billing_address = json_decode(stripslashes($_REQUEST['billing_address_source']), true);
                        $billing_address = $billing_address['billingDetails'] ?? null;
                        if (!empty($billing_address)) {
                            isset($billing_address['givenName']) &&  $woocommerce->customer->set_billing_first_name($billing_address['givenName']);

                            if (isset($billing_address['name'])) {
                                $splitName = angelleye_split_name($billing_address['name']);
                                $woocommerce->customer->set_billing_first_name($splitName[0]);
                                $woocommerce->customer->set_billing_last_name($splitName[1]);
                            }
                            isset($billing_address['familyName']) && $woocommerce->customer->set_billing_last_name($billing_address['familyName']);

                            !empty($billing_address['addressLines'][0]) ? $woocommerce->customer->set_billing_address_1($billing_address['addressLines'][0]) : null;
                            !empty($billing_address['address1']) ? $woocommerce->customer->set_billing_address_1($billing_address['address1']) : null;
                            !empty($billing_address['addressLines'][1]) ? $woocommerce->customer->set_billing_address_2($billing_address['addressLines'][1]) : null;
                            !empty($billing_address['address2']) ? $woocommerce->customer->set_billing_address_2($billing_address['address2']) : null;

                            isset($billing_address['emailAddress']) && $woocommerce->customer->set_billing_email($billing_address['emailAddress']);


                            isset($billing_address['postalCode']) && $woocommerce->customer->set_billing_postcode($billing_address['postalCode']);
                            isset($billing_address['countryCode']) && $woocommerce->customer->set_billing_country($billing_address['countryCode']);
                            isset($billing_address['locality']) && $woocommerce->customer->set_billing_city($billing_address['locality']);
                            isset($billing_address['administrativeArea']) && $woocommerce->customer->set_billing_state($billing_address['administrativeArea']);
                        }
                    }
                    $orderTotal = WC()->cart->get_total('');
                    if (isset($_GET['from']) && 'product' === $_GET['from'] && $paymentMethod == WC_Gateway_Apple_Pay_AngellEYE::PAYMENT_METHOD) {
                        try {
                            if (!class_exists('AngellEYE_PayPal_PPCP_Product')) {
                                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-product.php');
                            }
                            $this->product = AngellEYE_PayPal_PPCP_Product::instance();
                            $this->product::angelleye_ppcp_add_to_cart_action();
                        } catch (Exception $ex) {
                            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' .$ex->getLine(), 'error');
                            $this->api_log->log($ex->getMessage(), 'error');
                        }
                    }
                    $details = $this->payment_request->getCartLineItems();
                    wp_send_json([
                        'currencyCode' => get_woocommerce_currency(),
                        'totalAmount' => WC()->cart->get_total(''),
                        'lineItems' => $details,
                        'shippingRequired' => WC()->cart->needs_shipping(),
                        'isSubscriptionRequired' => $this->smart_button->isSubscriptionRequired()
                    ]);
                    break;
                case "display_order_page":
                    $this->angelleye_ppcp_display_order_page();
                    break;
                case "cc_capture":
                    wc_clear_notices();
                    // Required for order pay form, as there will be no data in session
                    AngellEye_Session_Manager::set('paypal_order_id', wc_clean($_GET['paypal_order_id']));

                    $from = AngellEye_Session_Manager::get('from', '');
                    if ('checkout_top' === $from) {
                        if (ob_get_length()) {
                            ob_end_clean();
                        }
                        WC()->session->set( 'reload_checkout', true );
                        wp_send_json_success(array(
                            'result' => 'success',
                            'redirect' => add_query_arg(array('paypal_order_id' => wc_clean($_GET['paypal_order_id']), 'utm_nooverride' => '1', 'wfacp_is_checkout_override' => 'yes'), untrailingslashit(wc_get_checkout_url())),
                        ));
                        exit();
                    } else {
                        $this->angelleye_ppcp_cc_capture();
                    }
                    break;
                case "direct_capture":
                    AngellEye_Session_Manager::set('paypal_order_id', wc_clean($_GET['paypal_order_id']));
                    AngellEye_Session_Manager::set('paypal_payer_id', wc_clean($_GET['paypal_payer_id']));
                    $this->angelleye_ppcp_direct_capture();
                    break;
                case "regular_capture":
                    $this->angelleye_ppcp_regular_capture();
                    break;
                case "regular_cancel":
                    AngellEye_Session_Manager::clear();
                    wp_redirect(wc_get_checkout_url());
                    exit();
                    break;
                case "paypal_create_payment_token":
                    $this->payment_request->angelleye_ppcp_paypal_create_payment_token();
                    exit();
                case "paypal_create_payment_token_free_signup_with_free_trial":
                    $this->payment_request->angelleye_ppcp_paypal_create_payment_token_free_signup_with_free_trial();
                    exit();
                case "advanced_credit_card_create_payment_token":
                    $this->payment_request->angelleye_ppcp_advanced_credit_card_create_payment_token();
                    exit();
                case "advanced_credit_card_create_payment_token_free_signup_with_free_trial":
                    $this->payment_request->angelleye_ppcp_advanced_credit_card_create_payment_token_free_signup_with_free_trial();
                    exit();
                case "advanced_credit_card_create_payment_token_sub_change_payment":
                    $this->payment_request->angelleye_ppcp_advanced_credit_card_create_payment_token_sub_change_payment();
                    exit();
                case "paypal_create_payment_token_sub_change_payment":
                    $this->payment_request->angelleye_ppcp_paypal_create_payment_token_sub_change_payment();
                    exit();
            }
        }
    }

    public function angelleye_ppcp_regular_capture() {
        if (isset($_GET['token']) && !empty($_GET['token'])) {
            AngellEye_Session_Manager::set('paypal_order_id', wc_clean($_GET['token']));
        } else {
            wp_redirect(wc_get_checkout_url());
            exit();
        }
        $order_id = (int) WC()->session->get('order_awaiting_payment');
        if (angelleye_ppcp_is_valid_order($order_id) === false || empty($order_id)) {
            wp_redirect(wc_get_checkout_url());
            exit();
        }
        $this->paymentaction = apply_filters('angelleye_ppcp_paymentaction', $this->paymentaction, $order_id);
        $order = wc_get_order($order_id);
        if ($this->paymentaction === 'capture') {
            $is_success = $this->payment_request->angelleye_ppcp_order_capture_request($order_id, $need_to_update_order = false);
        } else {
            $is_success = $this->payment_request->angelleye_ppcp_order_auth_request($order_id);
        }
        angelleye_ppcp_update_post_meta($order, '_paymentaction', $this->paymentaction);
        angelleye_ppcp_update_post_meta($order, '_enviorment', ($this->is_sandbox) ? 'sandbox' : 'live');
        AngellEye_Session_Manager::clear();
        if ($is_success) {
            WC()->cart->empty_cart();
            wp_redirect($this->angelleye_ppcp_get_return_url($order));
            exit();
        } else {
            wp_redirect(wc_get_checkout_url());
            exit();
        }
    }

    public function angelleye_ppcp_get_return_url($order = null) {
        if ($order) {
            $return_url = $order->get_checkout_order_received_url();
        } else {
            $return_url = wc_get_endpoint_url('order-received', '', wc_get_checkout_url());
        }

        return apply_filters('woocommerce_get_return_url', $return_url, $order);
    }

    public function angelleye_ppcp_cc_capture() {
        try {
            $this->paymentaction = apply_filters('angelleye_ppcp_paymentaction', $this->paymentaction, null);
            $angelleye_ppcp_paypal_order_id = AngellEye_Session_Manager::get('paypal_order_id', false);
            if (!empty($angelleye_ppcp_paypal_order_id)) {
                $order_id = (int) WC()->session->get('order_awaiting_payment');
                $order = wc_get_order($order_id);
                if ($order === false) {
                    if (!class_exists('AngellEYE_PayPal_PPCP_Checkout')) {
                        include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-checkout.php';
                    }
                    $ppcp_checkout = AngellEYE_PayPal_PPCP_Checkout::instance();
                    try {
                        $order_id = $ppcp_checkout->angelleye_ppcp_create_order();
                        $order = wc_get_order($order_id);
                    } catch (Exception $exception) {
                        AngellEye_Session_Manager::unset('paypal_transaction_details');
                        AngellEye_Session_Manager::unset('paypal_order_id');
                        remove_filter('woocommerce_get_checkout_url', [$this->smart_button, 'angelleye_ppcp_woocommerce_get_checkout_url']);
                        wc_add_notice($exception->getMessage(), 'error');
                        if (ob_get_length()) {
                            ob_end_clean();
                        }
                        wp_send_json_success(array(
                            'result' => 'failure',
                            'redirect' => ae_get_checkout_url()
                        ));
                        exit();
                    }
                    $this->payment_request->angelleye_ppcp_update_order($order);
                }
                $liability_shift_result = 1;
                if ($this->advanced_card_payments) {
                    $api_response = $this->payment_request->angelleye_ppcp_get_checkout_details($angelleye_ppcp_paypal_order_id);
                    $liability_shift_result = $this->angelleye_ppcp_liability_shift($order, $api_response);
                }
                if ($liability_shift_result === 1) {
                    if ($this->paymentaction === 'capture') {
                        $is_success = $this->payment_request->angelleye_ppcp_order_capture_request($order_id, false);
                    } else {
                        $is_success = $this->payment_request->angelleye_ppcp_order_auth_request($order_id);
                    }
                    angelleye_ppcp_update_post_meta($order, '_paymentaction', $this->paymentaction);
                    angelleye_ppcp_update_post_meta($order, '_enviorment', ($this->is_sandbox) ? 'sandbox' : 'live');
                } elseif ($liability_shift_result === 2) {
                    $is_success = false;
                    wc_add_notice(__('We cannot process your order with the payment information that you provided. Please use an alternate payment method.', 'paypal-for-woocommerce'), 'error');
                } elseif ($liability_shift_result === 3) {
                    $is_success = false;
                    wc_add_notice(__('Something went wrong. Please try again.', 'paypal-for-woocommerce'), 'error');
                }
                if ($is_success) {
                    WC()->cart->empty_cart();
                    AngellEye_Session_Manager::clear();
                    if (ob_get_length())
                        ob_end_clean();
                    wp_send_json_success(array(
                        'result' => 'success',
                        'redirect' => apply_filters('woocommerce_get_return_url', $order->get_checkout_order_received_url(), $order),
                    ));
                } else {
                    AngellEye_Session_Manager::clear();
                    if (ob_get_length()) {
                        ob_end_clean();
                    }
                    if (isset($_GET['is_pay_page']) && 'yes' === $_GET['is_pay_page']) {
                        wp_send_json_success(array(
                            'result' => 'failure',
                            'redirect' => $order->get_checkout_payment_url()
                        ));
                    } else {
                        remove_filter('woocommerce_get_checkout_url', [$this->smart_button, 'angelleye_ppcp_woocommerce_get_checkout_url']);
                        wp_send_json_success(array(
                            'result' => 'failure',
                            'redirect' => ae_get_checkout_url()
                        ));
                    }
                }
                exit();
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' .$ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_display_order_page() {
        try {
            $order_id = (int) WC()->session->get('order_awaiting_payment');
            if (angelleye_ppcp_is_valid_order($order_id) === false || empty($order_id)) {
                wp_redirect(wc_get_cart_url());
                exit();
            }
            $order = wc_get_order($order_id);
            $this->payment_request->angelleye_ppcp_update_woo_order_data($_GET['paypal_order_id']);
            WC()->cart->empty_cart();
            AngellEye_Session_Manager::clear();
            wp_safe_redirect(apply_filters('woocommerce_get_return_url', $order->get_checkout_order_received_url(), $order));
            exit();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' .$ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function maybe_start_checkout($data, $errors = null) {
        try {
            if (is_null($errors)) {
                $error_messages = wc_get_notices('error');
                wc_clear_notices();
            } else {
                $error_messages = $errors->get_error_messages();
            }
            if (empty($error_messages)) {
                $this->angelleye_ppcp_set_customer_data($_POST);
            } else {
                ob_start();
                wp_send_json_error(array('messages' => $error_messages));
                exit;
            }
            if (is_used_save_payment_token() === false) {
                $this->payment_request->angelleye_ppcp_create_order_request();
                exit();
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' .$ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_set_customer_data($data) {
        try {
            $customer = WC()->customer;
            $billing_first_name = empty($data['billing_first_name']) ? '' : wc_clean($data['billing_first_name']);
            $billing_last_name = empty($data['billing_last_name']) ? '' : wc_clean($data['billing_last_name']);
            $billing_country = empty($data['billing_country']) ? '' : wc_clean($data['billing_country']);
            $billing_address_1 = empty($data['billing_address_1']) ? '' : wc_clean($data['billing_address_1']);
            $billing_address_2 = empty($data['billing_address_2']) ? '' : wc_clean($data['billing_address_2']);
            $billing_city = empty($data['billing_city']) ? '' : wc_clean($data['billing_city']);
            $billing_state = empty($data['billing_state']) ? '' : wc_clean($data['billing_state']);
            $billing_postcode = empty($data['billing_postcode']) ? '' : wc_clean($data['billing_postcode']);
            $billing_phone = empty($data['billing_phone']) ? '' : wc_clean($data['billing_phone']);
            $billing_email = empty($data['billing_email']) ? '' : wc_clean($data['billing_email']);
            if (isset($data['ship_to_different_address'])) {
                $shipping_first_name = empty($data['shipping_first_name']) ? '' : wc_clean($data['shipping_first_name']);
                $shipping_last_name = empty($data['shipping_last_name']) ? '' : wc_clean($data['shipping_last_name']);
                $shipping_country = empty($data['shipping_country']) ? '' : wc_clean($data['shipping_country']);
                $shipping_address_1 = empty($data['shipping_address_1']) ? '' : wc_clean($data['shipping_address_1']);
                $shipping_address_2 = empty($data['shipping_address_2']) ? '' : wc_clean($data['shipping_address_2']);
                $shipping_city = empty($data['shipping_city']) ? '' : wc_clean($data['shipping_city']);
                $shipping_state = empty($data['shipping_state']) ? '' : wc_clean($data['shipping_state']);
                $shipping_postcode = empty($data['shipping_postcode']) ? '' : wc_clean($data['shipping_postcode']);
            } else {
                $shipping_first_name = $billing_first_name;
                $shipping_last_name = $billing_last_name;
                $shipping_country = $billing_country;
                $shipping_address_1 = $billing_address_1;
                $shipping_address_2 = $billing_address_2;
                $shipping_city = $billing_city;
                $shipping_state = $billing_state;
                $shipping_postcode = $billing_postcode;
            }
            $customer->set_shipping_country($shipping_country);
            $customer->set_shipping_address($shipping_address_1);
            $customer->set_shipping_address_2($shipping_address_2);
            $customer->set_shipping_city($shipping_city);
            $customer->set_shipping_state($shipping_state);
            $customer->set_shipping_postcode($shipping_postcode);
            if (version_compare(WC_VERSION, '3.0', '<')) {
                $customer->shipping_first_name = $shipping_first_name;
                $customer->shipping_last_name = $shipping_last_name;
                $customer->billing_first_name = $billing_first_name;
                $customer->billing_last_name = $billing_last_name;
                $customer->set_country($billing_country);
                $customer->set_address($billing_address_1);
                $customer->set_address_2($billing_address_2);
                $customer->set_city($billing_city);
                $customer->set_state($billing_state);
                $customer->set_postcode($billing_postcode);
                $customer->billing_phone = $billing_phone;
                $customer->billing_email = $billing_email;
            } else {
                $customer->set_shipping_first_name($shipping_first_name);
                $customer->set_shipping_last_name($shipping_last_name);
                $customer->set_billing_first_name($billing_first_name);
                $customer->set_billing_last_name($billing_last_name);
                $customer->set_billing_country($billing_country);
                $customer->set_billing_address_1($billing_address_1);
                $customer->set_billing_address_2($billing_address_2);
                $customer->set_billing_city($billing_city);
                $customer->set_billing_state($billing_state);
                $customer->set_billing_postcode($billing_postcode);
                $customer->set_billing_phone($billing_phone);
                $customer->set_billing_email($billing_email);
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' .$ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_liability_shift($order, $response_object) {
        if (!empty($response_object)) {
            $response = json_decode(json_encode($response_object), true);
            if (!empty($response['payment_source']['card']['authentication_result']['liability_shift'])) {
                $LiabilityShift = isset($response['payment_source']['card']['authentication_result']['liability_shift']) ? strtoupper($response['payment_source']['card']['authentication_result']['liability_shift']) : '';
                $EnrollmentStatus = isset($response['payment_source']['card']['authentication_result']['three_d_secure']['enrollment_status']) ? strtoupper($response['payment_source']['card']['authentication_result']['three_d_secure']['enrollment_status']) : '';
                $AuthenticationResult = isset($response['payment_source']['card']['authentication_result']['three_d_secure']['authentication_status']) ? strtoupper($response['payment_source']['card']['authentication_result']['three_d_secure']['authentication_status']) : '';
                $liability_shift_order_note = __('3D Secure response', 'paypal-for-woocommerce');
                $liability_shift_order_note .= "\n";
                $liability_shift_order_note .= 'Liability Shift : ' . angelleye_ppcp_readable($LiabilityShift);
                $liability_shift_order_note .= "\n";
                $liability_shift_order_note .= 'Enrollment Status : ' . $EnrollmentStatus;
                $liability_shift_order_note .= "\n";
                $liability_shift_order_note .= 'Authentication Status : ' . $AuthenticationResult;
                if ($order) {
                    $order->add_order_note($liability_shift_order_note);
                }
                if ($LiabilityShift === 'POSSIBLE') {
                    return $this->procceed;
                }
                if ($LiabilityShift === 'UNKNOWN') {
                    return $this->retry;
                }
                if ($LiabilityShift === 'NO') {
                    if ($EnrollmentStatus === 'B' && empty($AuthenticationResult)) {
                        return $this->procceed;
                    }
                    if ($EnrollmentStatus === 'U' && empty($AuthenticationResult)) {
                        return $this->procceed;
                    }
                    if ($EnrollmentStatus === 'N' && empty($AuthenticationResult)) {
                        return $this->procceed;
                    }
                    if ($AuthenticationResult === 'R') {
                        return $this->reject;
                    }
                    if ($AuthenticationResult === 'N') {
                        return $this->reject;
                    }
                    if ($AuthenticationResult === 'U') {
                        return $this->retry;
                    }
                    if (!$AuthenticationResult) {
                        return $this->retry;
                    }
                    return $this->procceed;
                }
                return $this->procceed;
            } else {
                return $this->procceed;
            }
        }
        return $this->retry;
    }

    public function angelleye_ppcp_create_woo_order() {
        if (!class_exists('AngellEYE_PayPal_PPCP_Checkout')) {
            include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-checkout.php';
        }
        $ppcp_checkout = AngellEYE_PayPal_PPCP_Checkout::instance();
        $ppcp_checkout->process_checkout();
    }

    public function angelleye_ppcp_direct_capture() {
        $this->angelleye_ppcp_create_woo_order();
    }

    private function no_liability_shift(AuthResult $result): int {

    }
}
