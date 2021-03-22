<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_PayPal_Express_Request_AngellEYE {

    public $gateway;
    public $gateway_calculation;
    public $credentials;
    public $paypal;
    public $cart_param;
    public $paypal_request;
    public $paypal_response;
    public $response_helper;
    public $function_helper;
    public $confirm_order_id;
    public $order_param;
    public $user_email_address;
    public $send_items;

    public function __construct($gateway) {
        try {
            $this->gateway = $gateway;
            $this->skip_final_review = $this->gateway->get_option('skip_final_review', 'no');
            $this->billing_address = 'yes' === $this->gateway->get_option('billing_address', 'no');
            $this->disable_term = 'yes' === $this->gateway->get_option('disable_term', 'no');
            $this->softdescriptor = $this->gateway->get_option('softdescriptor', '');
            $this->testmode = 'yes' === $this->gateway->get_option('testmode', 'yes');
            $this->fraud_management_filters = $this->gateway->get_option('fraud_management_filters', 'place_order_on_hold_for_further_review');
            $this->email_notify_order_cancellations = $this->gateway->get_option('email_notify_order_cancellations', 'no');
            $this->pending_authorization_order_status = $this->gateway->get_option('pending_authorization_order_status', 'On Hold');
            $this->enable_in_context_checkout_flow = $this->gateway->get_option('enable_in_context_checkout_flow', 'yes');
            $this->send_items = 'yes' === $this->gateway->get_option('send_items', 'yes');
            $this->id = 'paypal_express';
            if ($this->testmode == false) {
                $this->testmode = AngellEYE_Utility::angelleye_paypal_for_woocommerce_is_set_sandbox_product();
            }
            if ($this->testmode == true) {
                $this->API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
                $this->PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
                $this->api_username = $this->gateway->get_option('sandbox_api_username');
                $this->api_password = $this->gateway->get_option('sandbox_api_password');
                $this->api_signature = $this->gateway->get_option('sandbox_api_signature');
            } else {
                $this->API_Endpoint = "https://api-3t.paypal.com/nvp";
                $this->PAYPAL_URL = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
                $this->api_username = $this->gateway->get_option('api_username');
                $this->api_password = $this->gateway->get_option('api_password');
                $this->api_signature = $this->gateway->get_option('api_signature');
            }
            $this->Force_tls_one_point_two = get_option('Force_tls_one_point_two', 'no');
            $this->angelleye_load_paypal_class($this->gateway, $this);
            if (!class_exists('WC_Gateway_Calculation_AngellEYE')) {
                require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/wc-gateway-calculations-angelleye.php' );
            }
            $this->gateway_calculation = new WC_Gateway_Calculation_AngellEYE(null, $this->gateway->subtotal_mismatch_behavior);
            if (!class_exists('WC_Gateway_PayPal_Express_Response_AngellEYE')) {
                require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/express-checkout/class-wc-gateway-paypal-express-response-angelleye.php' );
            }
            $this->response_helper = new WC_Gateway_PayPal_Express_Response_AngellEYE();
            if (!class_exists('WC_Gateway_PayPal_Express_Function_AngellEYE')) {
                require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/express-checkout/class-wc-gateway-paypal-express-function-angelleye.php' );
            }
            $this->function_helper = new WC_Gateway_PayPal_Express_Function_AngellEYE();
            add_action('angelleye_save_angelleye_fraudnet', array($this, 'angelleye_save_angelleye_fraudnet'));
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_redirect() {
        $post_data = angelleye_get_session('post_data');
        if (!empty($post_data)) {
            if (!empty($post_data['_wcf_checkout_id'])) {
                $return_url = get_permalink($post_data['_wcf_checkout_id']);
            }
        }
        if (empty($return_url)) {
            $return_url = wc_get_cart_url();
        }
        $paypal_express_checkout = angelleye_get_session('paypal_express_checkout');
        $payPalURL = $this->PAYPAL_URL . $paypal_express_checkout['token'];
        if (!empty($this->paypal_response['L_ERRORCODE0']) && $this->paypal_response['L_ERRORCODE0'] == '10486') {
            if (!empty($paypal_express_checkout['token'])) {
                angelleye_set_session('is_smart_button_popup_closed', 'yes');
                wc_clear_notices();
                if (!empty($_REQUEST['request_from']) && $_REQUEST['request_from'] == 'JSv4') {
                    if (ob_get_length()) {
                        ob_end_clean();
                    }
                    ob_start();
                    wp_send_json(array(
                        'url' => $payPalURL
                    ));
                    exit();
                } else {
                    wp_redirect($payPalURL, 302);
                    exit;
                }
            }
        }
        $this->function_helper->ec_clear_session_data();
        if (!is_ajax()) {
            if (!empty($_REQUEST['request_from']) && $_REQUEST['request_from'] == 'JSv4') {
                if (ob_get_length()) {
                    ob_end_clean();
                }
                ob_start();
                if (wc_notice_count('error') > 0) {
                    wp_send_json(array(
                        'url' => $return_url
                    ));
                    exit();
                } else {
                    return array(
                        'url' => $payPalURL
                    );
                    exit();
                }
            } else {
                wp_redirect($return_url);
                exit;
            }
        } else {
            if (!empty($_REQUEST['request_from']) && $_REQUEST['request_from'] == 'JSv4') {
                if (ob_get_length()) {
                    ob_end_clean();
                }
                ob_start();
                if (wc_notice_count('error') > 0) {
                    wp_send_json(array(
                        'url' => $return_url
                    ));
                    exit();
                }
                return $payPalURL;
                exit();
            } else {
                $args = array(
                    'result' => 'failure',
                    'redirect' => $return_url,
                );
                if ($this->function_helper->ec_is_version_gte_2_4()) {
                    if (ob_get_length()) {
                        ob_end_clean();
                    }
                    ob_start();
                    wp_send_json($args);
                } else {
                    echo '<!--WC_START-->' . json_encode($args) . '<!--WC_END-->';
                }
            }
        }
    }

    public function angelleye_redirect_action($url) {
        if (!empty($url)) {

            if (!empty($_REQUEST['request_from']) && $_REQUEST['request_from'] == 'JSv4') {
                if (ob_get_length()) {
                    ob_end_clean();
                }
                ob_start();
                $query_str = parse_url($url, PHP_URL_QUERY);
                parse_str($query_str, $query_params);
                wp_send_json(array(
                    'token' => wc_clean($query_params['token'])
                ));
                exit();
            }


            if (!is_ajax()) {
                wp_redirect($url);
                exit;
            } else {
                if (ob_get_length()) {
                    ob_end_clean();
                }
                ob_start();
                $args = array(
                    'result' => 'success',
                    'redirect' => $url,
                );
                wp_send_json($args);
            }
        }
    }

    public function angelleye_set_express_checkout() {
        try {
            $this->angelleye_set_express_checkout_request();
            // @note set token in session so return can be matched - Skylar
            if (!empty($this->paypal_response['TOKEN'])) {
                if (null === ($paypalSession = angelleye_get_session('paypal_express_checkout')) || empty($paypalSession['token']) || $paypalSession['token'] != $this->paypal_response['TOKEN']) { // only set if not present or mismatched
                    angelleye_set_session('paypal_express_checkout', ['token' => $this->paypal_response['TOKEN']]);
                }
            }
            if ($this->response_helper->ec_is_response_success($this->paypal_response)) {
                $this->angelleye_redirect_action($this->paypal_response['REDIRECTURL']);
                exit;
            } elseif ($this->response_helper->ec_is_response_successwithwarning($this->paypal_response)) {
                if (!empty($this->paypal_response['L_ERRORCODE0']) && $this->paypal_response['L_ERRORCODE0'] == '11452') {
                    $this->angelleye_write_error_log_and_send_email_notification($paypal_action_name = 'SetExpressCheckout');
                    $this->angelleye_redirect();
                } else {
                    $this->angelleye_redirect_action($this->paypal_response['REDIRECTURL']);
                    exit;
                }
            } else {
                $this->angelleye_write_error_log_and_send_email_notification($paypal_action_name = 'SetExpressCheckout');
                $this->angelleye_redirect();
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_get_express_checkout_details() {
        try {
            // @note make sure token matches set express checkout response token - Skylar L
            if (!isset($_GET['token']) || null === ($paypalSession = angelleye_get_session('paypal_express_checkout')) || empty($paypalSession['token']) || $paypalSession['token'] != $_GET['token']) {
                $this->function_helper->ec_clear_session_data();
                wc_clear_notices();
                wc_add_notice(__('Your PayPal session has expired', 'paypal-for-woocommerce'), 'error');
                wp_redirect(wc_get_checkout_url(), 303);
                exit;
            }
            $token = esc_attr($_GET['token']);
            $this->angelleye_load_paypal_class($this->gateway, $this, null);
            $this->paypal_response = $this->paypal->GetExpresscheckoutDetails($token);
            $this->angelleye_write_paypal_request_log($paypal_action_name = 'GetExpresscheckoutDetails');
            if ($this->response_helper->ec_is_response_success($this->paypal_response)) {
                if ($this->is_angelleye_baid_required() == true) {
                    if (empty($this->paypal_response['BILLINGAGREEMENTACCEPTEDSTATUS']) || $this->paypal_response['BILLINGAGREEMENTACCEPTEDSTATUS'] == 0) {
                        $mailer = WC()->mailer();
                        $subject = __('PayPal billing agreement was not created successfully', 'paypal-for-woocommerce');
                        $message = 'An order was placed that requires a PayPal billing agreement for reference transactions, however, this billing agreement was not created successfully.  Please contact PayPal to verify that you have Reference Transactions enabled on your account.  This is required for Woo token payments (including Woo Subscriptions orders.)';
                        $message = $mailer->wrap_message($subject, $message);
                        $mailer->send($this->paypal_response['EMAIL'], strip_tags($subject), $message);
                        $this->angelleye_redirect();
                    }
                }
                $paypal_express_checkout = array(
                    'token' => $token,
                    'shipping_details' => $this->response_helper->ec_get_shipping_details($this->paypal_response),
                    'order_note' => $this->response_helper->ec_get_note_text($this->paypal_response),
                    'payer_id' => $this->response_helper->ec_get_payer_id($this->paypal_response),
                    'ExpresscheckoutDetails' => $this->paypal_response
                );
                angelleye_set_session('paypal_express_checkout', $paypal_express_checkout);
                angelleye_set_session('shiptoname', $this->paypal_response['FIRSTNAME'] . ' ' . $this->paypal_response['LASTNAME']);
                angelleye_set_session('payeremail', $this->paypal_response['EMAIL']);
                angelleye_set_session('chosen_payment_method', $this->gateway->id);
                $post_data = angelleye_get_session('post_data');
                if (empty($post_data)) {
                    $this->angelleye_ec_load_customer_data_using_ec_details();
                }
                if (!defined('WOOCOMMERCE_CHECKOUT')) {
                    define('WOOCOMMERCE_CHECKOUT', true);
                }
                if (!defined('WOOCOMMERCE_CART')) {
                    define('WOOCOMMERCE_CART', true);
                }
                WC()->cart->calculate_shipping();
                if (version_compare(WC_VERSION, '3.0', '<')) {
                    WC()->customer->calculated_shipping(true);
                } else {
                    WC()->customer->set_calculated_shipping(true);
                }
                if ($this->angelleye_ec_force_to_display_checkout_page()) {
                    if (!empty($_GET['pay_for_order']) && $_GET['pay_for_order'] == true && !empty($_GET['key'])) {
                        angelleye_set_session('order_awaiting_payment', absint(wp_unslash($_GET['order_id'])));
                    } else {
                        $this->angelleye_wp_safe_redirect(wc_get_checkout_url(), 'get_express_checkout_details');
                    }
                }
            } else {
                $this->angelleye_write_error_log_and_send_email_notification($paypal_action_name = 'GetExpresscheckoutDetails');
                $this->angelleye_redirect();
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_do_express_checkout_payment() {
        try {
            if (!isset($_GET['order_id'])) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->angelleye_redirect(); 
            }
            $this->confirm_order_id = absint(wp_unslash($_GET['order_id']));
            do_action('angelleye_save_angelleye_fraudnet', $this->confirm_order_id);
            $order = wc_get_order($this->confirm_order_id);
            if ($order === false) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->angelleye_redirect();
            }
            if (WC()->cart->needs_shipping()) {
                $errors = new WP_Error();
                $shipping_country = WC()->customer->get_shipping_country();
                if (empty($shipping_country)) {
                    $errors->add('shipping', __('Please enter an address to continue.', 'paypal-for-woocommerce'));
                } elseif (!in_array(WC()->customer->get_shipping_country(), array_keys(WC()->countries->get_shipping_countries()))) {
                    $errors->add('shipping', sprintf(__('Unfortunately <strong>we do not ship %s</strong>. Please enter an alternative shipping address.', 'paypal-for-woocommerce'), WC()->countries->shipping_to_prefix() . ' ' . WC()->customer->get_shipping_country()));
                } 
                foreach ($errors->get_error_messages() as $message) {
                    wc_add_notice($message, 'error');
                }
                if (wc_notice_count('error') > 0) {
                    $this->angelleye_redirect();
                }
            }


            $old_wc = version_compare(WC_VERSION, '3.0', '<');
            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            if ($old_wc) {
                update_post_meta($order_id, '_payment_method', $this->gateway->id);
                update_post_meta($order_id, '_payment_method_title', $this->gateway->title);
            } else {
                $order->set_payment_method($this->gateway);
            }
            $this->angelleye_load_paypal_class($this->gateway, $this, $order_id);
            if ($order->get_total() > 0) {
                $this->angelleye_do_express_checkout_payment_request();
            } else {
                $paypal_express_checkout = angelleye_get_session('paypal_express_checkout');
                if (empty($paypal_express_checkout['token'])) {
                    $this->angelleye_redirect();
                }
                $this->paypal_response = $this->paypal->CreateBillingAgreement($paypal_express_checkout['token']);
                $PayPalRequest = isset($this->paypal_response['RAWREQUEST']) ? $this->paypal_response['RAWREQUEST'] : '';
                $PayPalResponse = isset($this->paypal_response['RAWRESPONSE']) ? $this->paypal_response['RAWRESPONSE'] : '';
                WC_Gateway_PayPal_Express_AngellEYE::log('CreateBillingAgreement Request: ' . print_r($this->paypal->NVPToArray($this->paypal->MaskAPIResult($PayPalRequest)), true));
                WC_Gateway_PayPal_Express_AngellEYE::log('CreateBillingAgreement Response: ' . print_r($this->paypal->NVPToArray($this->paypal->MaskAPIResult($PayPalResponse)), true));
            }
            $this->angelleye_add_order_note($order);
            $this->angelleye_add_extra_order_meta($order);
            if ($this->gateway->payment_action != 'Sale') {
                AngellEYE_Utility::angelleye_paypal_for_woocommerce_add_paypal_transaction($this->paypal_response, $order, $this->gateway->payment_action);
            }
            $payment_meta = array('Payment type' => !empty($this->paypal_response['PAYMENTINFO_0_PAYMENTTYPE']) ? $this->paypal_response['PAYMENTINFO_0_PAYMENTTYPE'] : '', 'PayPal Transaction Fee' => !empty($this->paypal_response['PAYMENTINFO_0_FEEAMT']) ? $this->paypal_response['PAYMENTINFO_0_FEEAMT'] : '');
            AngellEYE_Utility::angelleye_add_paypal_payment_meta($order_id, $payment_meta);
            if ($this->response_helper->ec_is_response_success($this->paypal_response)) {
                $order->set_transaction_id(isset($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']) ? $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'] : '');
                do_action('ae_add_custom_order_note', $order, $card = null, $token = null, $this->paypal_response);
                apply_filters('woocommerce_payment_successful_result', array('result' => 'success'), $order_id);
                do_action('woocommerce_before_pay_action', $order);
                do_action('angelleye_express_checkout_order_data', $this->paypal_response, $order_id);
                $this->angelleye_ec_get_customer_email_address($this->confirm_order_id);
                if ($order->get_total() > 0) {
                    $this->angelleye_ec_sellerprotection_handler($this->confirm_order_id);
                }
                $this->angelleye_ec_save_billing_agreement($order_id);
                update_post_meta($order_id, 'is_sandbox', $this->testmode);
                if (empty($this->paypal_response['PAYMENTINFO_0_PAYMENTSTATUS'])) {
                    $this->paypal_response['PAYMENTINFO_0_PAYMENTSTATUS'] = '';
                }
                if ($this->is_angelleye_baid_required() == true) {
                    if (empty($this->paypal_response['BILLINGAGREEMENTID'])) {
                        $order->update_status('on-hold', __('Billing Agreement required for tokenized payments', 'paypal-for-woocommerce'));
                        $mailer = WC()->mailer();
                        $subject = __('PayPal billing agreement was not created successfully', 'paypal-for-woocommerce');
                        $subject .= __('Order #', 'paypal-for-woocommerce') . $order_id;
                        $message = 'We\'re sorry, but something went wrong with your order. Someone from our service department will contact you about this soon.';
                        $message = $mailer->wrap_message($subject, $message);
                        $payeremail = angelleye_get_session('payeremail');
                        if (!empty($payeremail)) {
                            $mailer->send($payeremail, strip_tags($subject), $message);
                        }
                        $admin_email = get_option("admin_email");
                        $mailer = WC()->mailer();
                        $message = 'This order requires a Billing Agreement ID for Woo token payments, but this value was not returned by PayPal.  This typically means that Reference Transactions are not enabled for Express Checkout on the PayPal account.  Please contact PayPal to resolve this issue, and then have your customer try again.';
                        $message = $mailer->wrap_message($subject, $message);
                        $mailer->send($this->user_email_address, strip_tags($subject), $message);
                        $mailer->send($admin_email, strip_tags($subject), $message);
                    } else {
                        $order->payment_complete($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']);
                    }
                } elseif ($this->paypal_response['PAYMENTINFO_0_PAYMENTSTATUS'] == 'Completed') {
                    $order->payment_complete($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']);
                } elseif (empty($this->paypal_response['PAYMENTINFO_0_PAYMENTSTATUS']) && !empty($this->paypal_response['BILLINGAGREEMENTID'])) {
                    $order->payment_complete($this->paypal_response['BILLINGAGREEMENTID']);
                } else {
                    $this->update_payment_status_by_paypal_responce($this->confirm_order_id, $this->paypal_response);
                }
                $payeremail = angelleye_get_session('payeremail');
                if ($old_wc) {
                    update_post_meta($order_id, '_express_chekout_transactionid', isset($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']) ? $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'] : '');
                    update_post_meta($order_id, 'paypal_email', $payeremail);
                } else {
                    update_post_meta($order->get_id(), '_express_chekout_transactionid', isset($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']) ? $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'] : '' );
                    update_post_meta($order->get_id(), 'paypal_email', $payeremail);
                }
                if (!empty($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'])) {
                    $order->add_order_note(sprintf(__('%s payment Transaction ID: %s', 'paypal-for-woocommerce'), $this->gateway->title, isset($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']) ? $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'] : ''));
                }
                WC()->cart->empty_cart();
                wc_clear_notices();
                $this->angelleye_wp_safe_redirect(add_query_arg('utm_nooverride', '1', $this->gateway->get_return_url($order)), 'do_express_checkout_payment');
            } elseif ($this->response_helper->ec_is_response_successwithwarning($this->paypal_response)) {
                $order->set_transaction_id(isset($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']) ? $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'] : '');
                do_action('angelleye_express_checkout_order_data', $this->paypal_response, $order_id);
                apply_filters('woocommerce_payment_successful_result', array('result' => 'success'), $order_id);
                do_action('woocommerce_before_pay_action', $order);
                $this->angelleye_ec_get_customer_email_address($this->confirm_order_id);
                if ($order->get_total() > 0) {
                    $this->angelleye_ec_sellerprotection_handler($this->confirm_order_id);
                }
                $this->angelleye_ec_save_billing_agreement($order_id);
                if ($old_wc) {
                    update_post_meta($order_id, 'is_sandbox', $this->testmode);
                } else {
                    update_post_meta($order->get_id(), 'is_sandbox', $this->testmode);
                }
                if ($this->is_angelleye_baid_required() == true) {
                    if (empty($this->paypal_response['BILLINGAGREEMENTID'])) {
                        $order->update_status('on-hold', __('Billing Agreement required for tokenized payments', 'paypal-for-woocommerce'));
                    } else {
                        $order->payment_complete($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']);
                    }
                } elseif ($this->paypal_response['PAYMENTINFO_0_PAYMENTSTATUS'] == 'Completed') {
                    $order->payment_complete($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']);
                } else {
                    if ($this->fraud_management_filters == 'place_order_on_hold_for_further_review' && (!empty($this->paypal_response['L_ERRORCODE0']) && $this->paypal_response['L_ERRORCODE0'] == '11610')) {
                        $error = !empty($this->paypal_response['L_LONGMESSAGE0']) ? $this->paypal_response['L_LONGMESSAGE0'] : $this->paypal_response['L_SHORTMESSAGE0'];
                        $order->update_status('on-hold', $error);
                        $old_wc = version_compare(WC_VERSION, '3.0', '<');
                        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
                        if ($old_wc) {
                            if (!get_post_meta($order_id, '_order_stock_reduced', true)) {
                                $order->reduce_order_stock();
                            }
                        } else {
                            wc_maybe_reduce_stock_levels($order_id);
                        }
                    } else {
                        $this->update_payment_status_by_paypal_responce($this->confirm_order_id, $this->paypal_response);
                    }
                    WC()->cart->empty_cart();
                }
                if ($old_wc) {
                    update_post_meta($order_id, '_express_chekout_transactionid', isset($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']) ? $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'] : '');
                } else {
                    update_post_meta($order->get_id(), '_express_chekout_transactionid', isset($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']) ? $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'] : '' );
                }
                if (!empty($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'])) {
                    $order->add_order_note(sprintf(__('%s payment Transaction ID: %s', 'paypal-for-woocommerce'), $this->gateway->title, $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']));
                }
                WC()->cart->empty_cart();
                wc_clear_notices();
                $this->angelleye_wp_safe_redirect(add_query_arg('utm_nooverride', '1', $this->gateway->get_return_url($order)), 'do_express_checkout_payment');
                exit();
            } elseif ($this->response_helper->ec_is_response_partialsuccess($this->paypal_response)) {
                $order->set_transaction_id(isset($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']) ? $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'] : '');
                do_action('angelleye_express_checkout_order_data', $this->paypal_response, $order_id);
                apply_filters('woocommerce_payment_successful_result', array('result' => 'success'), $order_id);
                do_action('woocommerce_before_pay_action', $order);
                update_post_meta($order_id, 'is_sandbox', $this->testmode);
                $order->update_status('wc-partial-payment');
                if ($old_wc) {
                    if (!get_post_meta($orderid, '_order_stock_reduced', true)) {
                        $order->reduce_order_stock();
                    }
                } else {
                    wc_maybe_reduce_stock_levels($orderid);
                }
                WC()->cart->empty_cart();
                wc_clear_notices();
                $this->angelleye_wp_safe_redirect(add_query_arg('utm_nooverride', '1', $this->gateway->get_return_url($order)), 'do_express_checkout_payment');
                exit();
            } else {
                $this->angelleye_add_order_note_with_error($order, $paypal_action_name = 'DoExpressCheckoutPayment');
                $this->angelleye_write_error_log_and_send_email_notification($paypal_action_name = 'DoExpressCheckoutPayment');
                $this->angelleye_redirect();
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_do_express_checkout_payment_request() {
        try {
            if (!empty($this->confirm_order_id)) {
                $order = wc_get_order($this->confirm_order_id);
                $customer_note_value = version_compare(WC_VERSION, '3.0', '<') ? wptexturize($order->customer_note) : wptexturize($order->get_customer_note());
                $customer_note = $customer_note_value ? substr(preg_replace("/[^A-Za-z0-9 ]/", "", $customer_note_value), 0, 256) : '';
            } else {
                
            }
            do_action('angelleye_paypal_for_woocommerce_product_level_payment_action', $this->gateway);
            if ($this->send_items) {
                $this->order_param = $this->gateway_calculation->order_calculation($this->confirm_order_id);
            } else {
                $this->order_param = array('is_calculation_mismatch' => true);
            }
            $this->angelleye_load_paypal_class($this->gateway, $this, $this->confirm_order_id);
            $paypal_express_checkout = angelleye_get_session('paypal_express_checkout');
            if (empty($paypal_express_checkout['token'])) {
                $this->angelleye_redirect();
            }
            $DECPFields = array(
                'token' => $paypal_express_checkout['token'],
                'payerid' => (!empty($paypal_express_checkout['payer_id']) ) ? $paypal_express_checkout['payer_id'] : null,
                'returnfmfdetails' => 1,
                'buyermarketingemail' => '',
                'allowedpaymentmethod' => ''
            );
            $Payments = array();
            $Payment = array(
                'amt' => AngellEYE_Gateway_Paypal::number_format($order->get_total(), $order),
                'currencycode' => version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency(),
                'insuranceoptionoffered' => '',
                'desc' => '',
                'custom' => apply_filters('ae_ppec_custom_parameter', json_encode(array('order_id' => version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id(), 'order_key' => version_compare(WC_VERSION, '3.0', '<') ? $order->order_key : $order->get_order_key()))),
                'invnum' => $this->gateway->invoice_id_prefix . str_replace("#", "", $order->get_order_number()),
                'notetext' => !empty($customer_notes) ? $customer_notes : '',
                'allowedpaymentmethod' => '',
                'paymentaction' => ($this->gateway->payment_action == 'Authorization' || $order->get_total() == 0 ) ? 'Authorization' : $this->gateway->payment_action,
                'paymentrequestid' => '',
                'sellerpaypalaccountid' => '',
                'sellerid' => '',
                'sellerusername' => '',
                'sellerregistrationdate' => '',
                'softdescriptor' => $this->softdescriptor
            );
            if (isset($this->gateway->notifyurl) && !empty($this->gateway->notifyurl)) {
                $Payment['notifyurl'] = $this->gateway->notifyurl;
            }
            if ($this->order_param['is_calculation_mismatch'] == false) {
                $Payment['order_items'] = $this->order_param['order_items'];
                $Payment['taxamt'] = AngellEYE_Gateway_Paypal::number_format($this->order_param['taxamt'], $order);
                $Payment['shippingamt'] = AngellEYE_Gateway_Paypal::number_format($this->order_param['shippingamt'], $order);
                $Payment['itemamt'] = AngellEYE_Gateway_Paypal::number_format($this->order_param['itemamt'], $order);
                if ($order->get_total() != $Payment['shippingamt']) {
                    $Payment['shippingamt'] = $Payment['shippingamt'];
                } else {
                    $Payment['shippingamt'] = 0.00;
                }
            } else {
                $Payment['order_items'] = array();
            }

            $REVIEW_RESULT = !empty($paypal_express_checkout['ExpresscheckoutDetails']) ? $paypal_express_checkout['ExpresscheckoutDetails'] : array();
            $PaymentRedeemedOffers = array();
            if ((isset($REVIEW_RESULT) && !empty($REVIEW_RESULT)) && isset($REVIEW_RESULT['WALLETTYPE0'])) {
                $i = 0;
                while (isset($REVIEW_RESULT['WALLETTYPE' . $i])) {
                    $RedeemedOffer = array(
                        'redeemedoffername' => $REVIEW_RESULT['WALLETDESCRIPTION' . $i],
                        'redeemedofferdescription' => '',
                        'redeemedofferamount' => '',
                        'redeemedoffertype' => $REVIEW_RESULT['WALLETTYPE' . $i],
                        'redeemedofferid' => $REVIEW_RESULT['WALLETID' . $i],
                        'redeemedofferpointsaccrued' => '',
                        'cummulativepointsname' => '',
                        'cummulativepointsdescription' => '',
                        'cummulativepointstype' => '',
                        'cummulativepointsid' => '',
                        'cummulativepointsaccrued' => '',
                    );
                    $i = $i + 1;
                    array_push($PaymentRedeemedOffers, $RedeemedOffer);
                }
                $Payment['redeemed_offers'] = $PaymentRedeemedOffers;
            }
            if (WC()->cart->needs_shipping()) {
                $shipping_first_name = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_first_name : $order->get_shipping_first_name();
                $shipping_last_name = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_last_name : $order->get_shipping_last_name();
                $shipping_address_1 = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_address_1 : $order->get_shipping_address_1();
                $shipping_address_2 = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_address_2 : $order->get_shipping_address_2();
                $shipping_city = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_city : $order->get_shipping_city();
                $shipping_state = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_state : $order->get_shipping_state();
                $shipping_postcode = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_postcode : $order->get_shipping_postcode();
                $shipping_country = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_country : $order->get_shipping_country();
                $billing_phone = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_phone : $order->get_billing_phone();
                $Payment['shiptoname'] = wc_clean(stripslashes($shipping_first_name . ' ' . $shipping_last_name));
                $Payment['shiptostreet'] = $shipping_address_1;
                $Payment['shiptostreet2'] = $shipping_address_2;
                $Payment['shiptocity'] = wc_clean(stripslashes($shipping_city));
                $Payment['shiptostate'] = $shipping_state;
                $Payment['shiptozip'] = $shipping_postcode;
                $Payment['shiptocountrycode'] = $shipping_country;
                $Payment['shiptophonenum'] = $billing_phone;
            }
            array_push($Payments, $Payment);
            $this->paypal_request = array(
                'DECPFields' => $DECPFields,
                'Payments' => $Payments
            );

            $this->paypal_response = $this->paypal->DoExpressCheckoutPayment(apply_filters('angelleye_woocommerce_express_checkout_do_express_checkout_payment_request_args', $this->paypal_request, $this->gateway, $this, $this->confirm_order_id));
            $this->angelleye_write_paypal_request_log($paypal_action_name = 'DoExpressCheckoutPayment');
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_load_paypal_class($gateway, $current, $order_id = null) {
        if ($this->testmode == false) {
            $this->testmode = AngellEYE_Utility::angelleye_paypal_for_woocommerce_is_set_sandbox_product($order_id);
        }
        if ($this->testmode == true) {
            $this->API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
            $this->PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
            $this->api_username = $this->gateway->get_option('sandbox_api_username');
            $this->api_password = $this->gateway->get_option('sandbox_api_password');
            $this->api_signature = $this->gateway->get_option('sandbox_api_signature');
        } else {
            $this->API_Endpoint = "https://api-3t.paypal.com/nvp";
            $this->PAYPAL_URL = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
            $this->api_username = $this->gateway->get_option('api_username');
            $this->api_password = $this->gateway->get_option('api_password');
            $this->api_signature = $this->gateway->get_option('api_signature');
        }
        do_action('angelleye_paypal_for_woocommerce_multi_account_api_paypal_express', $gateway, $current, $order_id);
        $this->credentials = array(
            'Sandbox' => $this->testmode,
            'APIUsername' => $this->api_username,
            'APIPassword' => $this->api_password,
            'APISignature' => $this->api_signature,
            'Force_tls_one_point_two' => $this->gateway->Force_tls_one_point_two
        );
        try {
            if (!class_exists('Angelleye_PayPal_WC')) {
                require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.class.php' );
            }
            $this->paypal = new Angelleye_PayPal_WC($this->credentials);
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_set_express_checkout_request() {
        do_action('angelleye_paypal_for_woocommerce_product_level_payment_action', $this->gateway);
        try {
            $order_id = '';
            $Payments = array();
            $order = null;
            $cancel_url = !empty($this->gateway->cancel_page_id) ? get_permalink($this->gateway->cancel_page_id) : wc_get_cart_url();
            if ($cancel_url == false) {
                $cancel_url = wc_get_checkout_url();
            }
            $cancel_url = add_query_arg('utm_nooverride', '1', $cancel_url);
            $order_total = '';
            if (!empty($_REQUEST['request_from']) && $_REQUEST['request_from'] == 'JSv4') {
                $returnurl = urldecode(add_query_arg(array('pp_action' => 'get_express_checkout_details', 'utm_nooverride' => 1, 'request_from' => 'JSv4'), untrailingslashit(WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE'))));
            } else {
                $returnurl = urldecode(add_query_arg(array('pp_action' => 'get_express_checkout_details', 'utm_nooverride' => 1), untrailingslashit(WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE'))));
            }

            if (!empty($_GET['pay_for_order']) && $_GET['pay_for_order'] == true && !empty($_GET['key'])) {
                if (version_compare(WC_VERSION, '3.0', '<')) {
                    $order_id = woocommerce_get_order_id_by_order_key($_GET['key']);
                } else {
                    $order_id = wc_get_order_id_by_order_key($_GET['key']);
                }
                if ($this->send_items) {
                    $this->cart_param = $this->gateway_calculation->order_calculation($order_id);
                } else {
                    $this->cart_param = array('is_calculation_mismatch' => true);
                }
                $order = wc_get_order($order_id);
                $order_total = $order->get_total();
                if (!empty($_REQUEST['request_from']) && $_REQUEST['request_from'] == 'JSv4') {
                    $returnurl = urldecode(add_query_arg(array(
                        'pp_action' => 'get_express_checkout_details',
                        'pay_for_order' => true,
                        'request_from' => 'JSv4',
                        'key' => wc_clean($_GET['key']),
                        'order_id' => $order_id,
                        'utm_nooverride' => 1
                                    ), untrailingslashit(WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE'))));
                } else {
                    $returnurl = urldecode(add_query_arg(array(
                        'pp_action' => 'get_express_checkout_details',
                        'pay_for_order' => true,
                        'key' => wc_clean($_GET['key']),
                        'order_id' => $order_id,
                        'utm_nooverride' => 1
                                    ), untrailingslashit(WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE'))));
                }
                angelleye_set_session('order_awaiting_payment', absint(wp_unslash($order_id)));
            } else {
                if ($this->send_items) {
                    $this->cart_param = $this->gateway_calculation->cart_calculation();
                } else {
                    $this->cart_param = array('is_calculation_mismatch' => true);
                }
                $order_total = WC()->cart->total;
            }
            $SECFields = array(
                'maxamt' => '',
                'returnurl' => $returnurl,
                'cancelurl' => urldecode($cancel_url),
                'callback' => '',
                'callbacktimeout' => '',
                'callbackversion' => '',
                'reqconfirmshipping' => '',
                'noshipping' => '',
                'allownote' => 1,
                'addroverride' => '',
                'localecode' => ($this->gateway->use_wp_locale_code == 'yes' && AngellEYE_Utility::get_button_locale_code() != '') ? AngellEYE_Utility::get_button_locale_code() : '',
                'pagestyle' => $this->gateway->page_style,
                'hdrimg' => $this->gateway->checkout_logo_hdrimg,
                'logoimg' => $this->gateway->checkout_logo,
                'hdrbordercolor' => '',
                'hdrbackcolor' => '',
                'payflowcolor' => '',
                'skipdetails' => $this->angelleye_ec_force_to_display_checkout_page() == true ? '0' : '1',
                'email' => '',
                'channeltype' => '',
                'giropaysuccessurl' => '',
                'giropaycancelurl' => '',
                'banktxnpendingurl' => '',
                'brandname' => wc_clean(stripslashes($this->gateway->brand_name)),
                'customerservicenumber' => $this->gateway->customer_service_number,
                'buyeremailoptionenable' => '',
                'surveyquestion' => '',
                'surveyenable' => '',
                'totaltype' => '',
                'notetobuyer' => '',
                'buyerid' => '',
                'buyerusername' => '',
                'buyerregistrationdate' => '',
                'allowpushfunding' => '',
                'taxidtype' => '',
                'taxid' => ''
            );

            if (empty($_REQUEST['request_from']) && $_REQUEST['request_from'] == 'JSv4') {
                if(is_angelleye_multi_account_active() === false) {
                    $SECFields['returnurl'] = 'https://www.paypal.com/checkoutnow/error';
                    $SECFields['cancelurl'] = 'https://www.paypal.com/checkoutnow/error';
                }
            }

            $usePayPalCredit = (!empty($_GET['use_paypal_credit']) && $_GET['use_paypal_credit'] == true) ? true : false;
            if ($usePayPalCredit) {
                $SECFields['solutiontype'] = 'Sole';
                $SECFields['landingpage'] = 'Billing';
                $SECFields['userselectedfundingsource'] = 'BML';
            } elseif (strtolower($this->gateway->paypal_account_optional) == 'yes' && strtolower($this->gateway->landing_page) == 'billing') {
                $SECFields['solutiontype'] = 'Sole';
                $SECFields['landingpage'] = 'Billing';
                $SECFields['userselectedfundingsource'] = 'CreditCard';
            } elseif (strtolower($this->gateway->paypal_account_optional) == 'yes' && strtolower($this->gateway->landing_page) == 'login') {
                $SECFields['solutiontype'] = 'Sole';
                $SECFields['landingpage'] = 'Login';
            }
            $SECFields = $this->function_helper->angelleye_paypal_for_woocommerce_needs_shipping($SECFields);
            if (is_object($order)) {
                $currencycode = version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency();
            } else {
                $currencycode = get_woocommerce_currency();
            }
            $Payment = array(
                'amt' => AngellEYE_Gateway_Paypal::number_format($order_total, $order),
                'currencycode' => $currencycode,
                'custom' => apply_filters('ae_ppec_custom_parameter', ''),
                'notetext' => '',
                'paymentaction' => ($this->gateway->payment_action == 'Authorization' || WC()->cart->total == 0 ) ? 'Authorization' : $this->gateway->payment_action,
            );

            if (empty($_GET['pay_for_order'])) {

                $post_data = angelleye_get_session('post_data');
                if (!empty($post_data)) {
                    $SECFields['addroverride'] = 1;
                    if (!empty($post_data['ship_to_different_address'])) {
                        $shiptoname = '';
                        if (!empty($post_data['shipping_first_name']) && !empty($post_data['shipping_last_name'])) {
                            $shiptoname = $post_data['shipping_first_name'] . ' ' . $post_data['shipping_last_name'];
                        } elseif (!empty($post_data['shipping_first_name'])) {
                            $shiptoname = $post_data['shipping_first_name'];
                        } elseif (!empty($post_data['shipping_last_name'])) {
                            $shiptoname = $post_data['shipping_last_name'];
                        }

                        if (!empty($post_data['shipping_company'])) {
                            $shipping_company = $post_data['shipping_company'];
                            $Payment['shiptoname'] = wc_clean(stripslashes($shipping_company . ' - ' . $shiptoname));
                        } else {
                            $Payment['shiptoname'] = wc_clean(stripslashes($shiptoname));
                        }
                        $SECFields['email'] = !empty($post_data['billing_email']) ? $post_data['billing_email'] : '';
                        $Payment['shiptostreet'] = !empty($post_data['shipping_address_1']) ? $post_data['shipping_address_1'] : '';
                        $Payment['shiptostreet2'] = !empty($post_data['shipping_address_2']) ? $post_data['shipping_address_2'] : '';
                        $Payment['shiptocity'] = !empty($post_data['shipping_city']) ? wc_clean(stripslashes($post_data['shipping_city'])) : '';
                        $Payment['shiptostate'] = !empty($post_data['shipping_state']) ? $post_data['shipping_state'] : '';
                        $Payment['shiptozip'] = !empty($post_data['shipping_postcode']) ? $post_data['shipping_postcode'] : '';
                        $Payment['shiptocountrycode'] = !empty($post_data['shipping_country']) ? $post_data['shipping_country'] : '';
                        $Payment['shiptophonenum'] = !empty($post_data['billing_phone']) ? $post_data['billing_phone'] : '';
                    } else {
                        $shiptoname = '';
                        if (!empty($post_data['billing_first_name']) && !empty($post_data['billing_last_name'])) {
                            $shiptoname = $post_data['billing_first_name'] . ' ' . $post_data['billing_last_name'];
                        } elseif (!empty($post_data['billing_first_name'])) {
                            $shiptoname = $post_data['billing_first_name'];
                        } elseif (!empty($post_data['billing_last_name'])) {
                            $shiptoname = $post_data['billing_last_name'];
                        }

                        if (!empty($post_data['billing_company'])) {
                            $billing_company = $post_data['billing_company'];
                            $Payment['shiptoname'] = wc_clean(stripslashes($billing_company . ' - ' . $shiptoname));
                        } else {
                            $Payment['shiptoname'] = wc_clean(stripslashes($shiptoname));
                        }
                        $SECFields['email'] = !empty($post_data['billing_email']) ? $post_data['billing_email'] : '';
                        $Payment['shiptostreet'] = !empty($post_data['billing_address_1']) ? wc_clean($post_data['billing_address_1']) : '';
                        $Payment['shiptostreet2'] = !empty($post_data['billing_address_2']) ? wc_clean($post_data['billing_address_2']) : '';
                        $Payment['shiptocity'] = !empty($post_data['billing_city']) ? wc_clean(stripslashes($post_data['billing_city'])) : '';
                        $Payment['shiptostate'] = !empty($post_data['billing_state']) ? wc_clean($post_data['billing_state']) : '';
                        $Payment['shiptozip'] = !empty($post_data['billing_postcode']) ? wc_clean($post_data['billing_postcode']) : '';
                        $Payment['shiptocountrycode'] = !empty($post_data['billing_country']) ? wc_clean($post_data['billing_country']) : '';
                        $Payment['shiptophonenum'] = !empty($post_data['billing_phone']) ? wc_clean($post_data['billing_phone']) : '';
                    }
                } elseif (is_user_logged_in()) {
                    if (version_compare(WC_VERSION, '3.0', '<')) {
                        $firstname = WC()->customer->firstname;
                    } else {
                        $firstname = WC()->customer->get_shipping_first_name();
                    }
                    if (version_compare(WC_VERSION, '3.0', '<')) {
                        $lastname = WC()->customer->lastname;
                    } else {
                        $lastname = WC()->customer->get_shipping_last_name();
                    }
                    $shiptostreet = WC()->customer->get_shipping_address();
                    $shiptostreet_two = WC()->customer->get_shipping_address_2();
                    $shipping_city = WC()->customer->get_shipping_city();
                    $shipping_country = WC()->customer->get_shipping_country();
                    $shipping_state = WC()->customer->get_shipping_state();
                    $shipping_postcode = WC()->customer->get_shipping_postcode();
                    if (version_compare(WC_VERSION, '3.0', '<')) {
                        $billing_shiptostreet = WC()->customer->get_address();
                        $billing_shiptostreet_two = WC()->customer->get_address_2();
                        $billing_city = WC()->customer->get_city();
                        $billing_country = WC()->customer->get_country();
                        $billing_state = WC()->customer->get_state();
                        $billing_postcode = WC()->customer->get_postcode();
                        $billing_phone = '';
                    } else {
                        $billing_shiptostreet = WC()->customer->get_billing_address_1();
                        $billing_shiptostreet_two = WC()->customer->get_billing_address_2();
                        $billing_city = WC()->customer->get_billing_city();
                        $billing_country = WC()->customer->get_billing_country();
                        $billing_state = WC()->customer->get_billing_state();
                        $billing_postcode = WC()->customer->get_billing_postcode();
                        $billing_phone = WC()->customer->get_billing_phone();
                        $billing_email = WC()->customer->get_billing_email();
                    }
                    $SECFields['email'] = !empty($billing_email) ? $billing_email : '';
                    $Payment['shiptoname'] = wc_clean(stripslashes($firstname . ' ' . $lastname));
                    $Payment['shiptostreet'] = !empty($shiptostreet) ? wc_clean(stripslashes($shiptostreet)) : wc_clean(stripslashes($billing_shiptostreet));
                    $Payment['shiptostreet2'] = !empty($shiptostreet_two) ? wc_clean(stripslashes($shiptostreet_two)) : wc_clean(stripslashes($billing_shiptostreet_two));
                    $Payment['shiptocity'] = !empty($shipping_city) ? wc_clean(stripslashes($shipping_city)) : wc_clean(stripslashes($billing_city));
                    $Payment['shiptostate'] = !empty($shipping_state) ? wc_clean(stripslashes($shipping_state)) : wc_clean(stripslashes($billing_state));
                    $Payment['shiptozip'] = !empty($shipping_postcode) ? wc_clean(stripslashes($shipping_postcode)) : wc_clean(stripslashes($billing_postcode));
                    $Payment['shiptocountrycode'] = !empty($shipping_country) ? wc_clean(stripslashes($shipping_country)) : wc_clean(stripslashes($billing_country));
                    $Payment['shiptophonenum'] = !empty($billing_phone) ? wc_clean(stripslashes($billing_phone)) : '';
                }
            } else {
                $shipping_first_name = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_first_name : $order->get_shipping_first_name();
                $shipping_last_name = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_last_name : $order->get_shipping_last_name();
                $shipping_address_1 = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_address_1 : $order->get_shipping_address_1();
                $shipping_address_2 = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_address_2 : $order->get_shipping_address_2();
                $shipping_city = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_city : $order->get_shipping_city();
                $shipping_state = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_state : $order->get_shipping_state();
                $shipping_postcode = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_postcode : $order->get_shipping_postcode();
                $shipping_country = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_country : $order->get_shipping_country();
                $Payment['shiptoname'] = wc_clean(stripslashes($shipping_first_name . ' ' . $shipping_last_name));
                $billing_email = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_email : $order->get_billing_email();
                $Payment['shiptostreet'] = $shipping_address_1;
                $Payment['shiptostreet2'] = $shipping_address_2;
                $Payment['shiptocity'] = wc_clean(stripslashes($shipping_city));
                $Payment['shiptostate'] = $shipping_state;
                $Payment['shiptozip'] = $shipping_postcode;
                $Payment['shiptocountrycode'] = $shipping_country;
                $SECFields['email'] = !empty($billing_email) ? $billing_email : '';
            }
            if (isset($this->cart_param['is_calculation_mismatch']) && $this->cart_param['is_calculation_mismatch'] == false) {
                $Payment['order_items'] = $this->cart_param['order_items'];
                $Payment['taxamt'] = $this->cart_param['taxamt'];
                $Payment['shippingamt'] = $this->cart_param['shippingamt'];
                $Payment['itemamt'] = $this->cart_param['itemamt'];
            }
            array_push($Payments, $Payment);
            $PayPalRequestData = array(
                'SECFields' => $SECFields,
                'Payments' => $Payments,
            );
            $this->paypal_request = $this->angelleye_add_billing_agreement_param($PayPalRequestData, $this->gateway->supports('tokenization'));
            $this->paypal_request = AngellEYE_Utility::angelleye_express_checkout_validate_shipping_address($this->paypal_request);

            $this->paypal_response = $this->paypal->SetExpressCheckout(apply_filters('angelleye_woocommerce_express_checkout_set_express_checkout_request_args', $this->paypal_request, $this->gateway, $this, $order_id));
            $this->angelleye_write_paypal_request_log($paypal_action_name = 'SetExpressCheckout');
            return $this->paypal_response;
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_add_billing_agreement_param($PayPalRequestData, $tokenization) {
        try {
            if (sizeof(WC()->cart->get_cart()) != 0) {
                foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                    $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
                    $_paypal_billing_agreement = get_post_meta($product_id, '_paypal_billing_agreement', true);
                    $ec_save_to_account = angelleye_get_session('ec_save_to_account');
                    if ($_paypal_billing_agreement == 'yes' || ( isset($ec_save_to_account) && $ec_save_to_account == 'on') || AngellEYE_Utility::angelleye_paypal_for_woo_wc_autoship_cart_has_autoship_item() || AngellEYE_Utility::is_cart_contains_subscription() == true || AngellEYE_Utility::is_subs_change_payment() == true) {
                        $BillingAgreements = array();
                        $Item = array(
                            'l_billingtype' => '',
                            'l_billingtype' => 'MerchantInitiatedBilling',
                            'l_billingagreementdescription' => '',
                            'l_paymenttype' => '',
                            'l_paymenttype' => 'Any',
                            'l_billingagreementcustom' => ''
                        );
                        array_push($BillingAgreements, $Item);
                        $PayPalRequestData['BillingAgreements'] = $BillingAgreements;
                        return $PayPalRequestData;
                    }
                }
            }
            return $PayPalRequestData;
        } catch (Exception $ex) {
            
        }
    }

    public function update_payment_status_by_paypal_responce($orderid, $result) {
        try {
            do_action('angelleye_save_angelleye_fraudnet', $orderid);
            $order = wc_get_order($orderid);
            $old_wc = version_compare(WC_VERSION, '3.0', '<');
            if (!empty($result['PAYMENTINFO_0_PAYMENTSTATUS'])) {
                $payment_status = $result['PAYMENTINFO_0_PAYMENTSTATUS'];
            } elseif (!empty($result['PAYMENTSTATUS'])) {
                $payment_status = $result['PAYMENTSTATUS'];
            }
            if (!empty($result['PAYMENTINFO_0_TRANSACTIONTYPE'])) {
                $transaction_type = $result['PAYMENTINFO_0_TRANSACTIONTYPE'];
            } elseif (!empty($result['TRANSACTIONTYPE'])) {
                $transaction_type = $result['TRANSACTIONTYPE'];
            }
            if (!empty($result['TRANSACTIONID'])) {
                $transaction_id = $result['TRANSACTIONID'];
            } elseif (!empty($result['PAYMENTINFO_0_TRANSACTIONID'])) {
                $transaction_id = $result['PAYMENTINFO_0_TRANSACTIONID'];
            } elseif (!empty($result['BILLINGAGREEMENTID'])) {
                $transaction_id = $result['BILLINGAGREEMENTID'];
            }
            if (!empty($result['PAYMENTINFO_0_PENDINGREASON'])) {
                $pending_reason = $result['PAYMENTINFO_0_PENDINGREASON'];
            } elseif (!empty($result['PENDINGREASON'])) {
                $pending_reason = $result['PENDINGREASON'];
            }
            switch (strtolower($payment_status)) :
                case 'completed' :
                    $order_status = version_compare(WC_VERSION, '3.0', '<') ? $order->status : $order->get_status();
                    if ($order_status == 'completed') {
                        break;
                    }
                    if (!in_array(strtolower($transaction_type), array('merchtpmt', 'cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money', 'expresscheckout', 'paypal_here'))) {
                        break;
                    }
                    $order->add_order_note(__('Payment Completed via Express Checkout', 'paypal-for-woocommerce'));
                    $order->payment_complete($transaction_id);
                    break;
                case 'completed_funds_held' :
                    $order_status = version_compare(WC_VERSION, '3.0', '<') ? $order->status : $order->get_status();
                    if ($order_status == 'completed') {
                        break;
                    }
                    if (!in_array(strtolower($transaction_type), array('merchtpmt', 'cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money', 'expresscheckout', 'paypal_here'))) {
                        break;
                    }
                    $order->payment_complete($transaction_id);
                    $url = $this->angelleye_wc_gateway()->get_transaction_url($order);
                    $order->add_order_note(__('The payment for this order has completed successfully, but PayPal has placed the funds on hold.  Please review the <a href="' . esc_url($url) . '">PayPal transaction details</a> for more information.', 'paypal-for-woocommerce'));
                    break;
                case 'pending' :
                    if (!in_array(strtolower($transaction_type), array('merchtpmt', 'cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money', 'expresscheckout', 'paypal_here'))) {
                        break;
                    }
                    switch (strtolower($pending_reason)) {
                        case 'address':
                            $pending_reason_text = __('Address: The payment is pending because your customer did not include a confirmed shipping address and your Payment Receiving Preferences is set such that you want to manually accept or deny each of these payments. To change your preference, go to the Preferences section of your Profile.', 'paypal-for-woocommerce');
                            break;
                        case 'authorization':
                            $pending_reason_text = __('Authorization: The payment is pending because it has been authorized but not settled. You must capture the funds first.', 'paypal-for-woocommerce');
                            break;
                        case 'echeck':
                            $pending_reason_text = __('eCheck: The payment is pending because it was made by an eCheck that has not yet cleared.', 'paypal-for-woocommerce');
                            break;
                        case 'intl':
                            $pending_reason_text = __('intl: The payment is pending because you hold a non-U.S. account and do not have a withdrawal mechanism. You must manually accept or deny this payment from your Account Overview.', 'paypal-for-woocommerce');
                            break;
                        case 'multicurrency':
                        case 'multi-currency':
                            $pending_reason_text = __('Multi-currency: You do not have a balance in the currency sent, and you do not have your Payment Receiving Preferences set to automatically convert and accept this payment. You must manually accept or deny this payment.', 'paypal-for-woocommerce');
                            break;
                        case 'order':
                            $pending_reason_text = __('Order: The payment is pending because it is part of an order that has been authorized but not settled.', 'paypal-for-woocommerce');
                            break;
                        case 'paymentreview':
                            $pending_reason_text = __('Payment Review: The payment is pending while it is being reviewed by PayPal for risk.', 'paypal-for-woocommerce');
                            break;
                        case 'unilateral':
                            $pending_reason_text = __('Unilateral: The payment is pending because it was made to an email address that is not yet registered or confirmed.', 'paypal-for-woocommerce');
                            break;
                        case 'verify':
                            $pending_reason_text = __('Verify: The payment is pending because you are not yet verified. You must verify your account before you can accept this payment.', 'paypal-for-woocommerce');
                            break;
                        case 'other':
                            $pending_reason_text = __('Other: For more information, contact PayPal customer service.', 'paypal-for-woocommerce');
                            break;
                        case 'none':
                        default:
                            $pending_reason_text = __('No pending reason provided.', 'paypal-for-woocommerce');
                            break;
                    }
                    $order->add_order_note(sprintf(__('Payment via Express Checkout Pending. PayPal reason: %s.', 'paypal-for-woocommerce'), $pending_reason_text));
                    if (strtolower($pending_reason) == 'authorization' && $this->pending_authorization_order_status == 'Processing') {
                        $order->payment_complete($transaction_id);
                    } else {
                        $order->update_status('on-hold');
                        if ($old_wc) {
                            if (!get_post_meta($orderid, '_order_stock_reduced', true)) {
                                $order->reduce_order_stock();
                            }
                        } else {
                            wc_maybe_reduce_stock_levels($orderid);
                        }
                    }
                    break;
                case 'denied' :
                case 'expired' :
                case 'failed' :
                case 'voided' :
                    $order->update_status('failed', sprintf(__('Payment %s via Express Checkout.', 'paypal-for-woocommerce'), strtolower($payment_status)));
                    break;
                default:
                    break;
            endswitch;
            return;
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_add_extra_order_meta($order) {
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        if (!empty($this->gateway->payment_action) && $this->gateway->payment_action != 'Sale') {
            if (!class_exists('WooCommerce') || WC()->session == null) {
                $payment_order_meta = array('_payment_action' => $this->gateway->payment_action, '_first_transaction_id' => isset($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']) ? $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'] : $this->paypal_response['TRANSACTIONID']);
            } else {
                $paypal_express_checkout = angelleye_get_session('paypal_express_checkout');
                $payment_order_meta = array('_payment_action' => $this->gateway->payment_action, '_express_checkout_token' => $paypal_express_checkout['token'], '_first_transaction_id' => isset($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']) ? $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'] : $this->paypal_response['TRANSACTIONID']);
            }
            $order->set_transaction_id(isset($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']) ? $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'] : '');
            AngellEYE_Utility::angelleye_add_order_meta($order_id, $payment_order_meta);
        }
    }

    public function angelleye_add_order_note($order) {
        $paypal_express_checkout = angelleye_get_session('paypal_express_checkout');
        if (!empty($paypal_express_checkout['ExpresscheckoutDetails']['PAYERSTATUS'])) {
            $order->add_order_note(sprintf(__('Payer Status: %s', 'paypal-for-woocommerce'), '<strong>' . $paypal_express_checkout['ExpresscheckoutDetails']['PAYERSTATUS'] . '</strong>'));
        }
        if (!empty($paypal_express_checkout['ExpresscheckoutDetails']['ADDRESSSTATUS'])) {
            $order->add_order_note(sprintf(__('Address Status: %s', 'paypal-for-woocommerce'), '<strong>' . $paypal_express_checkout['ExpresscheckoutDetails']['ADDRESSSTATUS'] . '</strong>'));
        }
    }

    public function angelleye_add_order_note_with_error($order, $paypal_action_name = null) {
        if (!empty($this->paypal_response['L_ERRORCODE0'])) {
            $ErrorCode = urldecode($this->paypal_response['L_ERRORCODE0']);
        } else {
            $ErrorCode = '';
        }
        if (!empty($this->paypal_response['L_SHORTMESSAGE0'])) {
            $ErrorShortMsg = urldecode($this->paypal_response['L_SHORTMESSAGE0']);
        } else {
            $ErrorShortMsg = '';
        }
        if (!empty($this->paypal_response['L_LONGMESSAGE0'])) {
            $ErrorLongMsg = urldecode($this->paypal_response['L_LONGMESSAGE0']);
        } else {
            $ErrorLongMsg = '';
        }
        if (!empty($this->paypal_response['L_SEVERITYCODE0'])) {
            $ErrorSeverityCode = urldecode($this->paypal_response['L_SEVERITYCODE0']);
        } else {
            $ErrorSeverityCode = '';
        }
        $order->add_order_note(sprintf(__('PayPal %s API call failed:', 'paypal-for-woocommerce') . __('Detailed Error Message: %s', 'paypal-for-woocommerce') . PHP_EOL . __('Short Error Message: %s', 'paypal-for-woocommerce') . PHP_EOL . __('Error Code: %s', 'paypal-for-woocommerce') . PHP_EOL . __('Error Severity Code: %s', 'paypal-for-woocommerce'), $paypal_action_name, $ErrorLongMsg, $ErrorShortMsg, $ErrorCode, $ErrorSeverityCode));
    }

    public function angelleye_write_error_log_and_send_email_notification($paypal_action_name) {
        if (!empty($this->paypal_response['L_ERRORCODE0'])) {
            $ErrorCode = urldecode($this->paypal_response['L_ERRORCODE0']);
        } else {
            $ErrorCode = '';
        }
        if (!empty($this->paypal_response['L_SHORTMESSAGE0'])) {
            $ErrorShortMsg = urldecode($this->paypal_response['L_SHORTMESSAGE0']);
        } else {
            $ErrorShortMsg = '';
        }
        if (!empty($this->paypal_response['L_LONGMESSAGE0'])) {
            $ErrorLongMsg = urldecode($this->paypal_response['L_LONGMESSAGE0']);
        } else {
            $ErrorLongMsg = '';
        }
        if (!empty($this->paypal_response['L_SEVERITYCODE0'])) {
            $ErrorSeverityCode = urldecode($this->paypal_response['L_SEVERITYCODE0']);
        } else {
            $ErrorSeverityCode = '';
        }
        if ($this->gateway->error_email_notify) {
            $mailer = WC()->mailer();
            $error_email_notify_subject = apply_filters('ae_ppec_error_email_subject', 'PayPal Express Checkout Error Notification');
            $message = sprintf(
                    "<strong>" . __('PayPal %s API call failed', 'paypal-for-woocommerce') . "</strong>" . PHP_EOL . PHP_EOL
                    . __('<strong>Error Code:</strong> %s', 'paypal-for-woocommerce') . PHP_EOL
                    . __('<strong>Error Severity Code:</strong> %s', 'paypal-for-woocommerce') . PHP_EOL
                    . __('<strong>Short Error Message:</strong> %s', 'paypal-for-woocommerce') . PHP_EOL
                    . __('<strong>Long Error Message:</strong> %s', 'paypal-for-woocommerce'), $paypal_action_name, $ErrorCode, $ErrorSeverityCode, $ErrorShortMsg, $ErrorLongMsg
            );
            $message = apply_filters('ae_ppec_error_email_message', $message, $ErrorCode, $ErrorSeverityCode, $ErrorShortMsg, $ErrorLongMsg);
            $message = $mailer->wrap_message($error_email_notify_subject, $message);
            $mailer->send(get_option('admin_email'), strip_tags($error_email_notify_subject), $message);
        }
        if ($this->gateway->error_display_type == 'detailed') {
            $sec_error_notice = $ErrorCode . ' - ' . $ErrorLongMsg;
            $error_display_type_message = sprintf(__($sec_error_notice, 'paypal-for-woocommerce'));
        } else {
            $error_display_type_message = sprintf(__('There was a problem paying with PayPal.  Please try another method.', 'paypal-for-woocommerce'));
        }
        $error_display_type_message = apply_filters('ae_ppec_error_user_display_message', $error_display_type_message, $ErrorCode, $ErrorLongMsg);
        if (function_exists('wc_add_notice')) {
            wc_add_notice($error_display_type_message, 'error');
        }
    }

    public function angelleye_write_paypal_request_log($paypal_action_name) {
        if ($paypal_action_name == 'SetExpressCheckout') {
            WC_Gateway_PayPal_Express_AngellEYE::log('Redirecting to PayPal');
            WC_Gateway_PayPal_Express_AngellEYE::log(sprintf(__('PayPal for WooCommerce Version: %s', 'paypal-for-woocommerce'), VERSION_PFW));
            WC_Gateway_PayPal_Express_AngellEYE::log(sprintf(__('WooCommerce Version: %s', 'paypal-for-woocommerce'), WC_VERSION));
            WC_Gateway_PayPal_Express_AngellEYE::log('Test Mode: ' . $this->testmode);
            WC_Gateway_PayPal_Express_AngellEYE::log('Endpoint: ' . $this->gateway->API_Endpoint);
        }
        $PayPalRequest = isset($this->paypal_response['RAWREQUEST']) ? $this->paypal_response['RAWREQUEST'] : '';
        $PayPalResponse = isset($this->paypal_response['RAWRESPONSE']) ? $this->paypal_response['RAWRESPONSE'] : '';
        WC_Gateway_PayPal_Express_AngellEYE::log($paypal_action_name . ' Request: ' . print_r($this->paypal->NVPToArray($this->paypal->MaskAPIResult($PayPalRequest)), true));
        WC_Gateway_PayPal_Express_AngellEYE::log($paypal_action_name . ' Response: ' . print_r($this->paypal->NVPToArray($this->paypal->MaskAPIResult($PayPalResponse)), true));
    }

    public function angelleye_ec_load_customer_data_using_ec_details() {
        if (!empty($this->paypal_response['SHIPTOCOUNTRYCODE'])) {
            if (!array_key_exists($this->paypal_response['SHIPTOCOUNTRYCODE'], WC()->countries->get_allowed_countries())) {
                if (AngellEYE_Utility::is_cart_contains_subscription() == false) {
                    wc_add_notice(sprintf(__('We do not sell in your country, please try again with another address.', 'paypal-for-woocommerce')), 'error');
                }
                $this->angelleye_redirect();
            }
        }
        if (isset($this->paypal_response['FIRSTNAME'])) {
            if (version_compare(WC_VERSION, '3.0', '<')) {
                WC()->customer->firstname = $this->paypal_response['FIRSTNAME'];
            } else {
                WC()->customer->set_shipping_first_name($this->paypal_response['FIRSTNAME']);
            }
        }
        if (isset($this->paypal_response['LASTNAME'])) {
            if (version_compare(WC_VERSION, '3.0', '<')) {
                WC()->customer->lastname = $this->paypal_response['LASTNAME'];
            } else {
                WC()->customer->set_shipping_last_name($this->paypal_response['LASTNAME']);
            }
        }
        if (isset($this->paypal_response['SHIPTOSTREET'])) {
            WC()->customer->set_shipping_address($this->paypal_response['SHIPTOSTREET']);
        }
        if (isset($this->paypal_response['SHIPTOSTREET2'])) {
            WC()->customer->set_shipping_address_2($this->paypal_response['SHIPTOSTREET2']);
        }
        if (isset($this->paypal_response['SHIPTOCITY'])) {
            WC()->customer->set_shipping_city($this->paypal_response['SHIPTOCITY']);
        }
        if (isset($this->paypal_response['SHIPTOCOUNTRYCODE'])) {
            WC()->customer->set_shipping_country($this->paypal_response['SHIPTOCOUNTRYCODE']);
        }
        if (isset($this->paypal_response['SHIPTOSTATE'])) {
            WC()->customer->set_shipping_state($this->get_state_code($this->paypal_response['SHIPTOCOUNTRYCODE'], $this->paypal_response['SHIPTOSTATE']));
        }
        if (isset($this->paypal_response['SHIPTOZIP'])) {
            WC()->customer->set_shipping_postcode($this->paypal_response['SHIPTOZIP']);
        }

        if (version_compare(WC_VERSION, '3.0', '<')) {
            if ($this->billing_address) {
                if (isset($this->paypal_response['SHIPTOSTREET'])) {
                    WC()->customer->set_address($this->paypal_response['SHIPTOSTREET']);
                }
                if (isset($this->paypal_response['SHIPTOSTREET2'])) {
                    WC()->customer->set_address_2($this->paypal_response['SHIPTOSTREET2']);
                }
                if (isset($this->paypal_response['SHIPTOCITY'])) {
                    WC()->customer->set_city($this->paypal_response['SHIPTOCITY']);
                }
                if (isset($this->paypal_response['SHIPTOCOUNTRYCODE'])) {
                    WC()->customer->set_country($this->paypal_response['SHIPTOCOUNTRYCODE']);
                }
                if (isset($this->paypal_response['SHIPTOSTATE'])) {
                    WC()->customer->set_state($this->get_state_code($this->paypal_response['SHIPTOCOUNTRYCODE'], $this->paypal_response['SHIPTOSTATE']));
                }
                if (isset($this->paypal_response['SHIPTOZIP'])) {
                    WC()->customer->set_postcode($this->paypal_response['SHIPTOZIP']);
                }
            }
            WC()->customer->calculated_shipping(true);
        } else {
            if ($this->billing_address) {
                if (isset($this->paypal_response['SHIPTOSTREET'])) {
                    WC()->customer->set_billing_address_1($this->paypal_response['SHIPTOSTREET']);
                }
                if (isset($this->paypal_response['SHIPTOSTREET2'])) {
                    WC()->customer->set_billing_address_2($this->paypal_response['SHIPTOSTREET2']);
                }
                if (isset($this->paypal_response['SHIPTOCITY'])) {
                    WC()->customer->set_billing_city($this->paypal_response['SHIPTOCITY']);
                }
                if (isset($this->paypal_response['SHIPTOCOUNTRYCODE'])) {
                    WC()->customer->set_billing_country($this->paypal_response['SHIPTOCOUNTRYCODE']);
                }
                if (isset($this->paypal_response['SHIPTOSTATE'])) {
                    WC()->customer->set_billing_state($this->get_state_code($this->paypal_response['SHIPTOCOUNTRYCODE'], $this->paypal_response['SHIPTOSTATE']));
                }
                if (isset($this->paypal_response['SHIPTOZIP'])) {
                    WC()->customer->set_billing_postcode($this->paypal_response['SHIPTOZIP']);
                }
            }
            WC()->customer->set_calculated_shipping(true);
        }
    }

    public function get_state_code($cc, $state) {
        if ('US' === $cc) {
            return $state;
        }
        $states = WC()->countries->get_states($cc);
        if (!empty($states)) {
            foreach ($states as $state_code => $state_value) {
                if (strtolower($state_code) == strtolower($state)) {
                    return strtoupper($state_code);
                }
            }
        }
        return $state;
    }

    public function angelleye_ec_save_billing_agreement($order_id) {
        if (empty($this->paypal_response)) {
            return false;
        }
        $order = wc_get_order($order_id);
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        update_post_meta($order_id, '_first_transaction_id', isset($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']) ? $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'] : '' );
        do_action('before_save_payment_token', $order_id);
        if (isset($this->paypal_response['BILLINGAGREEMENTID']) && !empty($this->paypal_response['BILLINGAGREEMENTID']) && is_user_logged_in()) {
            update_post_meta($order_id, 'BILLINGAGREEMENTID', isset($this->paypal_response['BILLINGAGREEMENTID']) ? $this->paypal_response['BILLINGAGREEMENTID'] : '');
            if (0 != $order->get_user_id()) {
                $customer_id = $order->get_user_id();
            } else {
                $customer_id = get_current_user_id();
            }
            $billing_agreement_id = $this->paypal_response['BILLINGAGREEMENTID'];
            $token = new WC_Payment_Token_CC();
            $token->set_token($billing_agreement_id);
            $token->set_gateway_id($this->gateway->id);
            $token->set_card_type('PayPal Billing Agreement');
            $token->set_last4(substr($billing_agreement_id, -4));
            $token->set_expiry_month(date('m'));
            $token->set_expiry_year(date('Y', strtotime('+20 years')));
            $token->set_user_id($customer_id);
            if ($token->validate()) {
                $save_result = $token->save();
                $angelleye_fraudnet_f = angelleye_get_session('angelleye_fraudnet_f');
                if( !empty($angelleye_fraudnet_f)) {
                    update_metadata('payment_token', $token->get_id(), 'angelleye_fraudnet_f', $angelleye_fraudnet_f);
                }
                if ($save_result) {
                    $_multi_account_api_username = get_post_meta($order_id, '_multi_account_api_username', true);
                    if (!empty($_multi_account_api_username)) {
                        add_metadata('payment_token', $save_result, '_multi_account_api_username', $_multi_account_api_username);
                    }
                    $order->add_payment_token($token);
                }
            } else {
                throw new Exception(__('Invalid or missing payment token fields.', 'paypal-for-woocommerce'));
            }
        }
        if (!empty($this->paypal_response['BILLINGAGREEMENTID'])) {
            update_post_meta($order_id, 'BILLINGAGREEMENTID', isset($this->paypal_response['BILLINGAGREEMENTID']) ? $this->paypal_response['BILLINGAGREEMENTID'] : '');
            update_post_meta($order_id, '_payment_tokens_id', isset($this->paypal_response['BILLINGAGREEMENTID']) ? $this->paypal_response['BILLINGAGREEMENTID'] : '');
            do_action('angelleye_save_angelleye_fraudnet', $order_id);
            $this->save_payment_token($order, isset($this->paypal_response['BILLINGAGREEMENTID']) ? $this->paypal_response['BILLINGAGREEMENTID'] : '');
        }
    }

    public function save_payment_token($order, $payment_tokens_id) {
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        // Also store it on the subscriptions being purchased or paid for in the order
        if (function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($order_id)) {
            $subscriptions = wcs_get_subscriptions_for_order($order_id);
        } elseif (function_exists('wcs_order_contains_renewal') && wcs_order_contains_renewal($order_id)) {
            $subscriptions = wcs_get_subscriptions_for_renewal_order($order_id);
        } else {
            $subscriptions = array();
        }
        if (!empty($subscriptions)) {
            foreach ($subscriptions as $subscription) {
                $subscription_id = version_compare(WC_VERSION, '3.0', '<') ? $subscription->id : $subscription->get_id();
                update_post_meta($subscription_id, '_payment_tokens_id', $payment_tokens_id);
                do_action('angelleye_save_angelleye_fraudnet', $subscription_id);
            }
        } else {
            do_action('angelleye_save_angelleye_fraudnet', $subscription_id);
            update_post_meta($order_id, '_payment_tokens_id', $payment_tokens_id);
        }
    }

    public function angelleye_ec_get_customer_email_address($order_id) {
        $this->user_email_address = '';
        $order = wc_get_order($order_id);
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        if (is_user_logged_in()) {
            $userLogined = wp_get_current_user();
            $this->user_email_address = $userLogined->user_email;
            if ($old_wc) {
                update_post_meta($order_id, '_customer_user', $userLogined->ID);
            } else {
                update_post_meta($order->get_id(), '_customer_user', $userLogined->ID);
            }
        }
    }

    public function angelleye_ec_sellerprotection_handler($order_id) {
        $order = wc_get_order($order_id);
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        if ($this->angelleye_woocommerce_sellerprotection_should_cancel_order()) {
            WC_Gateway_PayPal_Express_AngellEYE::log('Order ' . $order_id . ' (' . $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'] . ') did not meet our Seller Protection requirements. Cancelling and refunding order.');
            $order->add_order_note(__('Transaction did not meet our Seller Protection requirements. Cancelling and refunding order.', 'paypal-for-woocommerce'));
            $admin_email = get_option("admin_email");
            if ($this->email_notify_order_cancellations == true) {
                if (isset($this->user_email_address) && !empty($this->user_email_address)) {
                    $mailer = WC()->mailer();
                    $subject = __('PayPal Express Checkout payment declined due to our Seller Protection Settings', 'paypal-for-woocommerce');
                    $message = $mailer->wrap_message($subject, __('Order #', 'paypal-for-woocommerce') . $order_id);
                    $mailer->send($this->user_email_address, strip_tags($subject), $message);
                    $mailer->send($admin_email, strip_tags($subject), $message);
                }
            }
            $order->set_transaction_id(isset($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']) ? $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'] : '');
            $this->gateway->process_refund($order_id, $order->get_total(), __('There was a problem processing your order. Please contact customer support.', 'paypal-for-woocommerce'));
            $order->update_status('cancelled');
            if (AngellEYE_Utility::is_cart_contains_subscription() == false) {
                wc_add_notice(__('Thank you for your recent order. Unfortunately it has been cancelled and refunded. Please contact our customer support team.', 'paypal-for-woocommerce'), 'error');
            }
            $this->angelleye_redirect();
        } else {
            if (!empty($this->paypal_response['PAYMENTINFO_0_PROTECTIONELIGIBILITY'])) {
                $order->add_order_note('Seller Protection Status: ' . $this->paypal_response['PAYMENTINFO_0_PROTECTIONELIGIBILITY']);
            }
        }
    }

    public function DoReferenceTransaction($order_id) {
        $PayPalRequestData = array();
        $referenceid = get_post_meta($order_id, '_payment_tokens_id', true);
        $angelleye_fraudnet_f = get_post_meta($order_id, 'angelleye_fraudnet_f', true);
        if (!empty($_POST['wc-paypal_express-payment-token'])) {
            $token_id = wc_clean($_POST['wc-paypal_express-payment-token']);
            $token = WC_Payment_Tokens::get($token_id);
            $referenceid = $token->get_token();
            $angelleye_fraudnet_f_data = get_metadata('payment_token', $token_id, 'angelleye_fraudnet_f');
            $angelleye_fraudnet_f = isset($angelleye_fraudnet_f_data[0]) ? $angelleye_fraudnet_f_data[0] : '';
            do_action('angelleye_set_multi_account', $token_id, $order_id);
        } else {
            $wc_existing_token = $this->get_token_by_token($referenceid);
            if ($wc_existing_token != null) {
                do_action('angelleye_set_multi_account', $wc_existing_token, $order_id);
            }
        }
        $this->angelleye_load_paypal_class($this->gateway, $this, $order_id);
        $order = wc_get_order($order_id);
        $customer_note_value = version_compare(WC_VERSION, '3.0', '<') ? wptexturize($order->customer_note) : wptexturize($order->get_customer_note());
        $customer_notes = $customer_note_value ? substr(preg_replace("/[^A-Za-z0-9 ]/", "", $customer_note_value), 0, 256) : '';
        $DRTFields = array(
            'referenceid' => $referenceid,
            'paymentaction' => ($this->gateway->payment_action == 'Authorization' || $order->get_total() == 0 ) ? 'Authorization' : $this->gateway->payment_action,
            'returnfmfdetails' => '1',
            'softdescriptor' => $this->softdescriptor
        );
        
        if( !empty($angelleye_fraudnet_f) ) {
            $DRTFields['RiskSessionCorrelationID'] = $angelleye_fraudnet_f;
        }
        $PayPalRequestData['DRTFields'] = $DRTFields;
        $PaymentDetails = array(
            'amt' => AngellEYE_Gateway_Paypal::number_format($order->get_total(), $order), // Required. Total amount of the order, including shipping, handling, and tax.
            'currencycode' => version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency(), // A three-character currency code.  Default is USD.
            'insuranceoptionoffered' => '', // If true, the insurance drop-down on the PayPal review page displays Yes and shows the amount.
            'desc' => '', // Description of items on the order.  127 char max.
            'custom' => apply_filters('ae_ppec_custom_parameter', json_encode(array('order_id' => version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id(), 'order_key' => version_compare(WC_VERSION, '3.0', '<') ? $order->order_key : $order->get_order_key()))), // Free-form field for your own use.  256 char max.
            'invnum' => $this->gateway->invoice_id_prefix . str_replace("#", "", $order->get_order_number()), // Your own invoice or tracking number.  127 char max.
        );
        if (isset($this->gateway->notifyurl) && !empty($this->gateway->notifyurl)) {
            $PaymentDetails['notifyurl'] = $this->gateway->notifyurl;
        }
        if ($order->needs_shipping_address()) {
            $shipping_first_name = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_first_name : $order->get_shipping_first_name();
            $shipping_last_name = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_last_name : $order->get_shipping_last_name();
            $shipping_address_1 = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_address_1 : $order->get_shipping_address_1();
            $shipping_address_2 = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_address_2 : $order->get_shipping_address_2();
            $shipping_city = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_city : $order->get_shipping_city();
            $shipping_state = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_state : $order->get_shipping_state();
            $shipping_postcode = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_postcode : $order->get_shipping_postcode();
            $shipping_country = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_country : $order->get_shipping_country();
            $ShippingAddress = array('shiptoname' => wc_clean(stripslashes($shipping_first_name . ' ' . $shipping_last_name)), // Required if shipping is included.  Person's name associated with this address.  32 char max.
                'shiptostreet' => $shipping_address_1, // Required if shipping is included.  First street address.  100 char max.
                'shiptostreet2' => $shipping_address_2, // Second street address.  100 char max.
                'shiptocity' => wc_clean(stripslashes($shipping_city)), // Required if shipping is included.  Name of city.  40 char max.
                'shiptostate' => $shipping_state, // Required if shipping is included.  Name of state or province.  40 char max.
                'shiptozip' => $shipping_postcode, // Required if shipping is included.  Postal code of shipping address.  20 char max.
                'shiptocountrycode' => $shipping_country, // Required if shipping is included.  Country code of shipping address.  2 char max.
                'shiptophonenum' => '', // Phone number for shipping address.  20 char max.
            );
            $PayPalRequestData['ShippingAddress'] = $ShippingAddress;
        }
        if ($this->send_items) {
            $this->order_param = $this->gateway_calculation->order_calculation($order_id);
        } else {
            $this->order_param = array('is_calculation_mismatch' => true);
        }
        if ($this->order_param['is_calculation_mismatch'] == false) {
            $Payment['order_items'] = $this->order_param['order_items'];
            $PaymentDetails['taxamt'] = AngellEYE_Gateway_Paypal::number_format($this->order_param['taxamt'], $order);
            $PaymentDetails['shippingamt'] = AngellEYE_Gateway_Paypal::number_format($this->order_param['shippingamt'], $order);
            $PaymentDetails['itemamt'] = AngellEYE_Gateway_Paypal::number_format($this->order_param['itemamt'], $order);
            if ($order->get_total() != $PaymentDetails['shippingamt']) {
                $PaymentDetails['shippingamt'] = $PaymentDetails['shippingamt'];
            } else {
                $PaymentDetails['shippingamt'] = 0.00;
            }
        } else {
            $Payment['order_items'] = array();
        }
        $PayPalRequestData['PaymentDetails'] = $PaymentDetails;
        $this->paypal_response = $this->paypal->DoReferenceTransaction(apply_filters('angelleye_woocommerce_express_checkout_do_reference_transaction_request_args', $PayPalRequestData));
        AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($this->paypal_response, $methos_name = 'DoExpressCheckoutPayment', $gateway = 'PayPal Express Checkout', $this->gateway->error_email_notify);
        $this->save_payment_token($order, $referenceid);
        WC_Gateway_PayPal_Express_AngellEYE::log('Test Mode: ' . $this->testmode);
        WC_Gateway_PayPal_Express_AngellEYE::log('Endpoint: ' . $this->gateway->API_Endpoint);
        $PayPalRequest = isset($this->paypal_response['RAWREQUEST']) ? $this->paypal_response['RAWREQUEST'] : '';
        $PayPalResponse = isset($this->paypal_response['RAWRESPONSE']) ? $this->paypal_response['RAWRESPONSE'] : '';
        WC_Gateway_PayPal_Express_AngellEYE::log('Request: ' . print_r($this->paypal->NVPToArray($this->paypal->MaskAPIResult($PayPalRequest)), true));
        WC_Gateway_PayPal_Express_AngellEYE::log('Response: ' . print_r($this->paypal->NVPToArray($this->paypal->MaskAPIResult($PayPalResponse)), true));
        $this->angelleye_add_extra_order_meta($order);
        if ($this->gateway->payment_action != 'Sale') {
            AngellEYE_Utility::angelleye_paypal_for_woocommerce_add_paypal_transaction($this->paypal_response, $order, $this->gateway->payment_action);
        }
        $payment_meta = array('Payment type' => !empty($this->paypal_response['PAYMENTINFO_0_PAYMENTTYPE']) ? $this->paypal_response['PAYMENTINFO_0_PAYMENTTYPE'] : '', 'PayPal Transaction Fee' => !empty($this->paypal_response['PAYMENTINFO_0_FEEAMT']) ? $this->paypal_response['PAYMENTINFO_0_FEEAMT'] : '');
        AngellEYE_Utility::angelleye_add_paypal_payment_meta($order_id, $payment_meta);
        return $this->paypal_response;
    }

    public function angelleye_ec_force_to_display_checkout_page() {
        $this->enable_guest_checkout = get_option('woocommerce_enable_guest_checkout') == 'yes' ? true : false;
        $this->must_create_account = $this->enable_guest_checkout || is_user_logged_in() ? false : true;
        $force_to_display_checkout_page = true;
        if ($this->skip_final_review == 'no') {
            return apply_filters('angelleye_ec_force_to_display_checkout_page', true);
        }
        if ('yes' === get_option('woocommerce_registration_generate_username') && 'yes' === get_option('woocommerce_registration_generate_password')) {
            $this->must_create_account = false;
        }
        if ($this->must_create_account) {
            return apply_filters('angelleye_ec_force_to_display_checkout_page', true);
        }
        if (AngellEYE_Utility::is_cart_contains_subscription() == true) {
            return apply_filters('angelleye_ec_force_to_display_checkout_page', true);
        }
        $paypal_express_terms = angelleye_get_session('paypal_express_terms');
        if (wc_get_page_id('terms') > 0 && apply_filters('woocommerce_checkout_show_terms', true)) {
            if ($this->disable_term) {
                return apply_filters('angelleye_ec_force_to_display_checkout_page', false);
            } elseif ((isset($_POST['terms']) || isset($_POST['legal'])) && $_POST['terms'] == 'on') {
                return apply_filters('angelleye_ec_force_to_display_checkout_page', false);
            } elseif (!empty($paypal_express_terms) && $paypal_express_terms == true) {
                return apply_filters('angelleye_ec_force_to_display_checkout_page', false);
            }
        }
        if ($this->skip_final_review == 'yes') {
            return apply_filters('angelleye_ec_force_to_display_checkout_page', false);
        }
        return apply_filters('angelleye_ec_force_to_display_checkout_page', $force_to_display_checkout_page);
    }

    public function angelleye_woocommerce_sellerprotection_should_cancel_order() {
        $order_cancellation_setting = $this->gateway->order_cancellations;
        $txn_protection_eligibility_response = isset($this->paypal_response['PAYMENTINFO_0_PROTECTIONELIGIBILITY']) ? $this->paypal_response['PAYMENTINFO_0_PROTECTIONELIGIBILITY'] : 'ERROR!';
        $txn_id = isset($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']) ? $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'] : 'ERROR!';
        switch ($order_cancellation_setting) {
            case 'no_seller_protection':
                if ($txn_protection_eligibility_response != 'Eligible' && $txn_protection_eligibility_response != 'PartiallyEligible') {
                    WC_Gateway_PayPal_Express_AngellEYE::log('Transaction ' . $txn_id . ' is BAD. Setting: no_seller_protection, Response: ' . $txn_protection_eligibility_response);
                    return true;
                }
                WC_Gateway_PayPal_Express_AngellEYE::log('Transaction ' . $txn_id . ' is OK. Setting: no_seller_protection, Response: ' . $txn_protection_eligibility_response);
                return false;
            case 'no_unauthorized_payment_protection':
                if ($txn_protection_eligibility_response != 'Eligible') {
                    WC_Gateway_PayPal_Express_AngellEYE::log('Transaction ' . $txn_id . ' is BAD. Setting: no_unauthorized_payment_protection, Response: ' . $txn_protection_eligibility_response);
                    return true;
                }
                WC_Gateway_PayPal_Express_AngellEYE::log('Transaction ' . $txn_id . ' is OK. Setting: no_unauthorized_payment_protection, Response: ' . $txn_protection_eligibility_response);
                return false;
            case 'disabled':
                WC_Gateway_PayPal_Express_AngellEYE::log('Transaction ' . $txn_id . ' is OK. Setting: disabled, Response: ' . $txn_protection_eligibility_response);
                return false;
            default:
                WC_Gateway_PayPal_Express_AngellEYE::log('ERROR! order_cancellations setting for ' . $this->gateway->method_title . ' is not valid!');
                return true;
        }
    }

    public function angelleye_process_refund($order_id, $amount = null, $reason = '') {
        $this->angelleye_load_paypal_class($this->gateway, $this, $order_id);
        $order = wc_get_order($order_id);
        WC_Gateway_PayPal_Express_AngellEYE::log('Begin Refund');
        WC_Gateway_PayPal_Express_AngellEYE::log('Transaction ID: ' . print_r($order->get_transaction_id(), true));
        if (!$order || !$order->get_transaction_id()) {
            return false;
        }
        if ($reason) {
            if (255 < strlen($reason)) {
                $reason = substr($reason, 0, 252) . '...';
            }
            $reason = html_entity_decode($reason, ENT_NOQUOTES, 'UTF-8');
        }
        $RTFields = array(
            'transactionid' => $order->get_transaction_id(),
            'refundtype' => $order->get_total() == $amount ? 'Full' : 'Partial',
            'amt' => AngellEYE_Gateway_Paypal::number_format($amount, $order),
            'currencycode' => version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency(),
            'note' => $reason,
        );
        $PayPalRequestData = array('RTFields' => $RTFields);
        WC_Gateway_PayPal_Express_AngellEYE::log('Refund Request: ' . print_r($PayPalRequestData, true));
        $this->paypal_response = $this->paypal->RefundTransaction($PayPalRequestData);


        AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($this->paypal_response, $methos_name = 'RefundTransaction', $gateway = 'PayPal Express Checkout', $this->gateway->error_email_notify);
        WC_Gateway_PayPal_Express_AngellEYE::log('Test Mode: ' . $this->testmode);
        WC_Gateway_PayPal_Express_AngellEYE::log('Endpoint: ' . $this->gateway->API_Endpoint);
        $PayPalRequest = isset($this->paypal_response['RAWREQUEST']) ? $this->paypal_response['RAWREQUEST'] : '';
        $PayPalResponse = isset($this->paypal_response['RAWRESPONSE']) ? $this->paypal_response['RAWRESPONSE'] : '';
        WC_Gateway_PayPal_Express_AngellEYE::log('Request: ' . print_r($this->paypal->NVPToArray($this->paypal->MaskAPIResult($PayPalRequest)), true));
        WC_Gateway_PayPal_Express_AngellEYE::log('Response: ' . print_r($this->paypal->NVPToArray($this->paypal->MaskAPIResult($PayPalResponse)), true));
        if ($this->paypal->APICallSuccessful($this->paypal_response['ACK'])) {
            $order->add_order_note('Refund Transaction ID:' . $this->paypal_response['REFUNDTRANSACTIONID']);
            update_post_meta($order_id, 'Refund Transaction ID', $this->paypal_response['REFUNDTRANSACTIONID']);
            if (ob_get_length())
                ob_end_clean();
            return true;
        } else {
            $ec_message = apply_filters('ae_ppec_refund_error_message', $this->paypal_response['L_LONGMESSAGE0'], $this->paypal_response['L_ERRORCODE0'], $this->paypal_response);
            return new WP_Error('ec_refund-error', $ec_message);
        }
    }

    public function angelleye_process_customer($order_id) {
        $post_data = angelleye_get_session('post_data');
        $paypal_express_checkout = angelleye_get_session('paypal_express_checkout');
        if (!empty($post_data) && !empty($post_data['billing_first_name']) && !empty($post_data['billing_last_name']) && !empty($post_data['billing_email'])) {
            $first_name = !empty($post_data['billing_first_name']) ? $post_data['billing_first_name'] : '';
            $last_name = !empty($post_data['billing_last_name']) ? $post_data['billing_last_name'] : '';
            $email = !empty($post_data['billing_email']) ? $post_data['billing_email'] : '';
        } else {
            if (!empty($paypal_express_checkout)) {
                $first_name = !empty($paypal_express_checkout['ExpresscheckoutDetails']['FIRSTNAME']) ? $paypal_express_checkout['ExpresscheckoutDetails']['FIRSTNAME'] : '';
                $last_name = !empty($paypal_express_checkout['ExpresscheckoutDetails']['LASTNAME']) ? $paypal_express_checkout['ExpresscheckoutDetails']['LASTNAME'] : '';
                $email = !empty($paypal_express_checkout['ExpresscheckoutDetails']['EMAIL']) ? $paypal_express_checkout['ExpresscheckoutDetails']['EMAIL'] : '';
            }
        }
        if (!empty($email)) {
            if (email_exists($email)) {
                $customer_id = email_exists($email);
            } else {
                $username = sanitize_user(current(explode('@', $email)), true);
                $append = 1;
                $o_username = $username;
                while (username_exists($username)) {
                    $username = $o_username . $append;
                    $append++;
                }
                if ('yes' === get_option('woocommerce_registration_generate_password')) {
                    $password = '';
                } else {
                    $password = wp_generate_password();
                }
                angelleye_set_session('before_wc_create_new_customer', true);
                $new_customer = wc_create_new_customer($email, $username, $password);
                if (is_wp_error($new_customer)) {
                    throw new Exception($new_customer->get_error_message());
                } else {
                    $customer_id = absint($new_customer);
                    do_action('woocommerce_guest_customer_new_account_notification', $customer_id);
                }
            }
            wc_set_customer_auth_cookie($customer_id);
            angelleye_set_session('reload_checkout', true);
            if ($first_name && apply_filters('woocommerce_checkout_update_customer_data', true, WC()->customer)) {
                $userdata = array(
                    'ID' => $customer_id,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'display_name' => $first_name
                );
                update_post_meta($order_id, '_customer_user', $customer_id);
                wp_update_user(apply_filters('woocommerce_checkout_customer_userdata', $userdata, WC()->customer));
                wc_clear_notices();
            }
            WC()->session->set('paypal_express_checkout', $paypal_express_checkout);
            WC()->session->set('post_data', $post_data);
        }
    }

    public function angelleye_wp_safe_redirect($url, $action = null) {
        $is_smart_button_popup_closed = angelleye_get_session('is_smart_button_popup_closed');
        if (!empty($is_smart_button_popup_closed) && $is_smart_button_popup_closed == 'yes') {
            unset(WC()->session->is_smart_button_popup_closed);
            wp_safe_redirect($url);
            exit;
        }
        if (!empty($_REQUEST['request_from']) && $_REQUEST['request_from'] == 'JSv4') {
            if (ob_get_length()) {
                ob_end_clean();
            }
            ob_start();
            wp_send_json(array(
                'url' => $url
            ));
            exit();
        } else {
            wp_safe_redirect($url);
            exit;
        }
    }

    public function is_angelleye_baid_required() {
        $ec_save_to_account = angelleye_get_session('ec_save_to_account');
        if (( isset($ec_save_to_account) && $ec_save_to_account == 'on') || AngellEYE_Utility::angelleye_paypal_for_woo_wc_autoship_cart_has_autoship_item() || AngellEYE_Utility::is_cart_contains_subscription() == true || AngellEYE_Utility::is_subs_change_payment() == true) {
            return true;
        }
        return false;
    }

    public function get_token_by_token($token_id, $token_result = null) {
        global $wpdb;
        if (is_null($token_result)) {
            $token_result = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE token = %s", $token_id
            ));
            if (empty($token_result)) {
                return null;
            }
        }
        if (isset($token_result->token_id) && !empty($token_result->token_id)) {
            return $token_result->token_id;
        } else {
            return null;
        }
    }

    public function angelleye_wc_gateway() {
        global $woocommerce;
        $gateways = $woocommerce->payment_gateways->payment_gateways();
        return $gateways['paypal_express'];
    }

    public function angelleye_get_paldetails($gateways) {
        try {
            if (!empty($gateways)) {
                $this->angelleye_load_paypal_class($gateways, $this);
                $PayPalResult = $this->paypal->GetPalDetails();
                if (isset($PayPalResult['ACK']) && $PayPalResult['ACK'] == 'Success') {
                    if (isset($PayPalResult['PAL']) && !empty($PayPalResult['PAL'])) {
                        $merchant_account_id = $PayPalResult['PAL'];
                        update_option('angelleye_express_checkout_default_pal', array('Sandbox' => $this->testmode, 'APIUsername' => $this->api_username, 'PAL' => $merchant_account_id));
                        return $merchant_account_id;
                    }
                }
            }
        } catch (Exception $ex) {
            return false;
        }
        return false;
    }
    
    public function angelleye_save_angelleye_fraudnet($order_id) {
        $angelleye_fraudnet_f = angelleye_get_session('angelleye_fraudnet_f');
        if( !empty($angelleye_fraudnet_f)) {
            update_post_meta($order_id, 'angelleye_fraudnet_f', $angelleye_fraudnet_f);
        }
    }

}
