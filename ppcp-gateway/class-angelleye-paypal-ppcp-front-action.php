<?php

defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Front_Action {

    private $angelleye_ppcp_plugin_name;
    public $api_log;
    public $payment_request;
    public $settings;
    protected static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->angelleye_ppcp_plugin_name = 'angelleye_ppcp';
        $this->angelleye_ppcp_load_class();
        $this->paymentaction = $this->settings->get('paymentaction', 'capture');
        $this->title = $this->settings->get('title', 'PayPal Complete Payments');
        $this->advanced_card_payments = 'yes' === $this->settings->get('enable_advanced_card_payments', 'no');
        $this->is_sandbox = 'yes' === $this->settings->get('testmode', 'no');
        if ($this->dcc_applies->for_country_currency() === false) {
            $this->advanced_card_payments = false;
        }
        if ($this->advanced_card_payments) {
            $this->threed_secure_enabled = 'yes' === $this->settings->get('threed_secure_enabled', 'no');
        } else {
            $this->threed_secure_enabled = false;
        }
        if (!has_action('woocommerce_api_' . strtolower('AngellEYE_PayPal_PPCP_Front_Action'))) {
            add_action('woocommerce_api_' . strtolower('AngellEYE_PayPal_PPCP_Front_Action'), array($this, 'handle_wc_api'));
        }
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
            if (!class_exists('AngellEYE_PayPal_PPCP_Product')) {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-product.php');
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_DCC_Validate')) {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-dcc-validate.php');
            }
            $this->settings = WC_Gateway_PPCP_AngellEYE_Settings::instance();
            $this->product = AngellEYE_PayPal_PPCP_Product::instance();
            $this->api_log = AngellEYE_PayPal_PPCP_Log::instance();
            $this->payment_request = AngellEYE_PayPal_PPCP_Payment::instance();
            $this->dcc_applies = AngellEYE_PayPal_PPCP_DCC_Validate::instance();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function handle_wc_api() {
        global $wp;
        if (!empty($_GET['angelleye_ppcp_action'])) {
            switch ($_GET['angelleye_ppcp_action']) {
                case "cancel_order":
                    unset(WC()->session->angelleye_ppcp_session);
                    wp_redirect(wc_get_cart_url());
                    exit();
                    break;
                case "create_order":
                    if (isset($_GET['from']) && 'pay_page' === $_GET['from']) {
                        $woo_order_id = $_POST['woo_order_id'];
                        angelleye_ppcp_set_session('angelleye_ppcp_woo_order_id', $woo_order_id);
                        $this->payment_request->angelleye_ppcp_create_order_request($woo_order_id);
                        exit();
                    } elseif (isset($_GET['from']) && 'checkout' === $_GET['from']) {
                        add_action('woocommerce_after_checkout_validation', array($this, 'maybe_start_checkout'), 10, 2);
                        WC()->checkout->process_checkout();
                    } elseif (isset($_GET['from']) && 'product' === $_GET['from']) {
                        try {
                            $this->product::angelleye_ppcp_add_to_cart_action();
                            $this->payment_request->angelleye_ppcp_create_order_request();
                            exit();
                        } catch (Exception $ex) {
                            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
                            $this->api_log->log($ex->getMessage(), 'error');
                        }
                    } else {
                        $this->payment_request->angelleye_ppcp_create_order_request();
                        exit();
                    }
                    break;
                case "display_order_page":
                    $this->angelleye_ppcp_display_order_page();
                    break;
                case "cc_capture":
                    wc_clear_notices();
                    angelleye_ppcp_set_session('angelleye_ppcp_paypal_order_id', wc_clean($_GET['paypal_order_id']));
                    $this->angelleye_ppcp_cc_capture();
                    break;

                case "direct_capture":
                    angelleye_ppcp_set_session('angelleye_ppcp_paypal_order_id', wc_clean($_GET['paypal_order_id']));
                    $this->angelleye_ppcp_direct_capture();
                    break;
            }
        }
    }

    public function angelleye_ppcp_cc_capture() {
        try {
            $angelleye_ppcp_paypal_order_id = angelleye_ppcp_get_session('angelleye_ppcp_paypal_order_id');
            if (!empty($angelleye_ppcp_paypal_order_id)) {
                $api_response = $this->payment_request->angelleye_ppcp_get_checkout_details($angelleye_ppcp_paypal_order_id);
                $order_id = absint(WC()->session->get('order_awaiting_payment'));
                if (empty($order_id)) {
                    $order_id = angelleye_ppcp_get_session('angelleye_ppcp_woo_order_id');
                }
                $order = wc_get_order($order_id);
                if ($this->angelleye_ppcp_liability_shift($order, $api_response)) {
                    if ($this->paymentaction === 'capture') {
                        $is_success = $this->payment_request->angelleye_ppcp_order_capture_request($order_id, false);
                    } else {
                        $is_success = $this->payment_request->angelleye_ppcp_order_auth_request($order_id);
                    }
                    angelleye_ppcp_update_post_meta($order, '_payment_action', $this->paymentaction);
                    angelleye_ppcp_update_post_meta($order, '_enviorment', ($this->is_sandbox) ? 'sandbox' : 'live');
                    WC()->cart->empty_cart();
                } else {
                    $is_success = false;
                    wc_add_notice(__('We cannot process your order with the payment information that you provided. Please use an alternate payment method.', 'paypal-for-woocommerce'), 'error');
                }
                if ($is_success) {
                    unset(WC()->session->angelleye_ppcp_session);
                    if (ob_get_length())
                        ob_end_clean();
                    wp_send_json_success(array(
                        'result' => 'success',
                        'redirect' => apply_filters('woocommerce_get_return_url', $order->get_checkout_order_received_url(), $order),
                    ));
                    exit();
                } else {
                    unset(WC()->session->angelleye_ppcp_session);
                    if (ob_get_length())
                        ob_end_clean();
                    wp_send_json_success(array(
                        'result' => 'failure',
                        'redirect' => wc_get_checkout_url()
                    ));
                    exit();
                }
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_display_order_page() {
        try {
            $order_id = absint(angelleye_ppcp_get_session('order_awaiting_payment'));
            if (empty($order_id)) {
                $order_id = angelleye_ppcp_get_session('angelleye_ppcp_woo_order_id');
            }
            if (angelleye_ppcp_is_valid_order($order_id) === false || empty($order_id)) {
                wp_redirect(wc_get_cart_url());
                exit();
            }
            $order = wc_get_order($order_id);
            $this->payment_request->angelleye_ppcp_update_woo_order_data($_GET['paypal_order_id']);
            WC()->cart->empty_cart();
            unset(WC()->session->angelleye_ppcp_session);
            wp_safe_redirect($order->get_checkout_order_received_url());
            exit();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
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
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
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
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_liability_shift($order, $response_object) {
        if ($this->threed_secure_enabled === false) {
            return true;
        }
        if (!empty($response_object)) {
            $response = json_decode(json_encode($response_object), true);
            if (!empty($response['payment_source']['card']['authentication_result']['liability_shift'])) {
                $LiabilityShift = $response['payment_source']['card']['authentication_result']['liability_shift'];
                $EnrollmentStatus = isset($response['payment_source']['card']['authentication_result']['three_d_secure']['enrollment_status']) ? $response['payment_source']['card']['authentication_result']['three_d_secure']['enrollment_status'] : '';
                $AuthenticationResult = isset($response['payment_source']['card']['authentication_result']['three_d_secure']['authentication_status']) ? $response['payment_source']['card']['authentication_result']['three_d_secure']['authentication_status'] : '';
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
                if ($EnrollmentStatus === 'Y' && $AuthenticationResult === 'Y' && $LiabilityShift === 'POSSIBLE') {
                    return true;
                } elseif ($EnrollmentStatus === 'Y' && $AuthenticationResult === 'A' && $LiabilityShift === 'POSSIBLE') {
                    return true;
                } elseif ($EnrollmentStatus === 'N' && $LiabilityShift === 'No') {
                    return true;
                } elseif ($EnrollmentStatus === 'U' && $LiabilityShift === 'No') {
                    return true;
                } elseif ($EnrollmentStatus === 'B' && $LiabilityShift === 'No') {
                    return true;
                }
            }
        }
        return false;
    }

    public function angelleye_ppcp_create_woo_order() {
        if (!class_exists('AngellEYE_PayPal_PPCP_Checkout')) {
            include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-checkout.php';
        }
        $ppcp_checkout = AngellEYE_PayPal_PPCP_Checkout::instance();
        return $ppcp_checkout->process_checkout();
    }

    public function angelleye_ppcp_direct_capture() {
        $this->angelleye_ppcp_create_woo_order();
    }

}
