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

    public function __construct($gateway) {
        try {
            $this->gateway = $gateway;
            $this->skip_final_review = $this->gateway->get_option('skip_final_review', 'no');
            $this->billing_address = 'yes' === $this->gateway->get_option('billing_address', 'no');
            $this->disable_term = 'yes' === $this->gateway->get_option('disable_term', 'no');
            $this->save_abandoned_checkout = 'yes' === $this->gateway->get_option('save_abandoned_checkout', 'no');
            $this->softdescriptor = $this->gateway->get_option('softdescriptor', '');
            $this->testmode = 'yes' === $this->gateway->get_option('testmode', 'yes');
            $this->fraud_management_filters = $this->gateway->get_option('fraud_management_filters', 'place_order_on_hold_for_further_review');
            $this->email_notify_order_cancellations = $this->gateway->get_option('email_notify_order_cancellations', 'no');
            $this->pending_authorization_order_status = $this->gateway->get_option('pending_authorization_order_status', 'On Hold');
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
            $this->gateway_calculation = new WC_Gateway_Calculation_AngellEYE();
            if (!class_exists('WC_Gateway_PayPal_Express_Response_AngellEYE')) {
                require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/express-checkout/class-wc-gateway-paypal-express-response-angelleye.php' );
            }
            $this->response_helper = new WC_Gateway_PayPal_Express_Response_AngellEYE();
            if (!class_exists('WC_Gateway_PayPal_Express_Function_AngellEYE')) {
                require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/express-checkout/class-wc-gateway-paypal-express-function-angelleye.php' );
            }
            $this->function_helper = new WC_Gateway_PayPal_Express_Function_AngellEYE();
            
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_redirect() {
        if (!empty($this->paypal_response['L_ERRORCODE0']) && $this->paypal_response['L_ERRORCODE0'] == '10486') {
            $paypal_express_checkout = WC()->session->get('paypal_express_checkout');
            if( !empty($paypal_express_checkout['token'] ) ) {
                $payPalURL = $this->PAYPAL_URL . $paypal_express_checkout['token'];
                wc_clear_notices();
                wp_redirect($payPalURL, 302);
                exit;
            } 
        }
        unset(WC()->session->paypal_express_checkout);
        if (!is_ajax()) {
            wp_redirect(get_permalink(wc_get_page_id('cart')));
            exit;
        } else {
            $args = array(
                'result' => 'failure',
                'redirect' => get_permalink(wc_get_page_id('cart')),
            );
            if ($this->function_helper->ec_is_version_gte_2_4()) {
                wp_send_json($args);
            } else {
                echo '<!--WC_START-->' . json_encode($args) . '<!--WC_END-->';
            }
        }
    }

    public function angelleye_redirect_action($url) {
        if (!empty($url)) {
            if (!is_ajax()) {
                wp_redirect($url);
                exit;
            } else {
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
            if ($this->response_helper->ec_is_response_success_or_successwithwarning($this->paypal_response)) {
                $this->angelleye_redirect_action($this->paypal_response['REDIRECTURL']);
                exit;
            } else {
                $this->angelleye_write_error_log_and_send_email_notification($paypal_action_name = 'SetExpressCheckout');
                $this->angelleye_redirect();
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_get_express_checkout_details() {
        try {
            if (!isset($_GET['token'])) {
                $this->angelleye_redirect();
            }
            $token = esc_attr($_GET['token']);
            $this->angelleye_load_paypal_class($this->gateway, $this, null);
            $this->paypal_response = $this->paypal->GetExpresscheckoutDetails($token);
            $this->angelleye_write_paypal_request_log($paypal_action_name = 'GetExpresscheckoutDetails');
            if ($this->response_helper->ec_is_response_success($this->paypal_response)) {
                $paypal_express_checkout = array(
                    'token' => $token,
                    'shipping_details' => $this->response_helper->ec_get_shipping_details($this->paypal_response),
                    'order_note' => $this->response_helper->ec_get_note_text($this->paypal_response),
                    'payer_id' => $this->response_helper->ec_get_payer_id($this->paypal_response),
                    'ExpresscheckoutDetails' => $this->paypal_response
                );
                WC()->session->set('paypal_express_checkout', $paypal_express_checkout);
                WC()->session->set('shiptoname', $this->paypal_response['FIRSTNAME'] . ' ' . $this->paypal_response['LASTNAME']);
                WC()->session->set('payeremail', $this->paypal_response['EMAIL']);
                WC()->session->set('chosen_payment_method', $this->gateway->id);
                $post_data = WC()->session->get('post_data');
                if(empty($post_data)) {
                    $this->angelleye_ec_load_customer_data_using_ec_details();
                }
                if (!defined('WOOCOMMERCE_CHECKOUT')) {
                    define('WOOCOMMERCE_CHECKOUT', true);
                }
                if (!defined('WOOCOMMERCE_CART')) {
                    define('WOOCOMMERCE_CART', true);
                }
                WC()->cart->calculate_totals();
                WC()->cart->calculate_shipping();
                if (version_compare(WC_VERSION, '3.0', '<')) {
                    WC()->customer->calculated_shipping(true);
                } else {
                    WC()->customer->set_calculated_shipping(true);
                }
                if ($this->angelleye_ec_force_to_display_checkout_page()) {
                    if ($this->angelleye_ec_force_to_display_checkout_page()) {
                        if( !empty($_GET['pay_for_order']) && $_GET['pay_for_order'] == true && !empty($_GET['key'])) {
                           WC()->session->set( 'order_awaiting_payment', $_GET['order_id'] );
                        } else {
                            wp_safe_redirect( wc_get_checkout_url() );
                            exit;
                        }
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
                // todo need to redirect to cart page.
            }
            
            if ( WC()->cart->needs_shipping() ) {
                $errors      = new WP_Error();
                $shipping_country = WC()->customer->get_shipping_country();
                if ( empty( $shipping_country ) ) {
                        $errors->add( 'shipping', __( 'Please enter an address to continue.', 'woocommerce' ) );
                } elseif ( ! in_array( WC()->customer->get_shipping_country(), array_keys( WC()->countries->get_shipping_countries() ) ) ) {
                        $errors->add( 'shipping', sprintf( __( 'Unfortunately <strong>we do not ship %s</strong>. Please enter an alternative shipping address.', 'woocommerce' ), WC()->countries->shipping_to_prefix() . ' ' . WC()->customer->get_shipping_country() ) );
                } else {
                    $chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
                    foreach ( WC()->shipping->get_packages() as $i => $package ) {
                        if ( ! isset( $chosen_shipping_methods[ $i ], $package['rates'][ $chosen_shipping_methods[ $i ] ] ) ) {
                            $errors->add( 'shipping', __( 'No shipping method has been selected. Please double check your address, or contact us if you need any help.', 'woocommerce' ) );
                        }
                    }
                }
                foreach ( $errors->get_error_messages() as $message ) {
                    wc_add_notice( $message, 'error' );
                }
                if ( wc_notice_count( 'error' ) > 0 ) {
                    wp_redirect(get_permalink(wc_get_page_id('cart')));
                    exit;
                }
            }
            
            $this->confirm_order_id = esc_attr($_GET['order_id']);
            $order = new WC_Order($this->confirm_order_id);
            $old_wc = version_compare(WC_VERSION, '3.0', '<');
            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            $this->angelleye_load_paypal_class($this->gateway, $this, $order_id);
            if ($order->get_total() > 0) {
                $this->angelleye_do_express_checkout_payment_request();
            } else {
                $paypal_express_checkout = WC()->session->get('paypal_express_checkout');
                if(empty($paypal_express_checkout['token'])) {
                    $this->angelleye_redirect();
                }
                $this->paypal_response = $this->paypal->CreateBillingAgreement($paypal_express_checkout['token']);
            }
            $this->angelleye_add_order_note($order);
            $this->angelleye_add_extra_order_meta($order);
            if ($this->gateway->payment_action != 'Sale') {
                AngellEYE_Utility::angelleye_paypal_for_woocommerce_add_paypal_transaction($this->paypal_response, $order, $this->gateway->payment_action);
            }
            if ($this->response_helper->ec_is_response_success($this->paypal_response)) {
                apply_filters( 'woocommerce_payment_successful_result', array('result' => 'success'), $order_id );
                do_action( 'woocommerce_before_pay_action', $order );
                $this->angelleye_ec_get_customer_email_address($this->confirm_order_id);
                $this->angelleye_ec_sellerprotection_handler($this->confirm_order_id);
                $this->angelleye_ec_save_billing_agreement($order_id);
                update_post_meta($order_id, 'is_sandbox', $this->testmode);
                if (empty($this->paypal_response['PAYMENTINFO_0_PAYMENTSTATUS'])) {
                    $this->paypal_response['PAYMENTINFO_0_PAYMENTSTATUS'] = '';
                }
                if ($this->paypal_response['PAYMENTINFO_0_PAYMENTSTATUS'] == 'Completed') {
                    $order->payment_complete($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']);
                } elseif (empty($this->paypal_response['PAYMENTINFO_0_PAYMENTSTATUS']) && !empty($this->paypal_response['BILLINGAGREEMENTID'])) {
                    $order->payment_complete($this->paypal_response['BILLINGAGREEMENTID']);
                } else {
                    $this->update_payment_status_by_paypal_responce($this->confirm_order_id, $this->paypal_response);
                    if ($old_wc) {
                        update_post_meta($order_id, '_transaction_id', isset($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']) ? $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'] : '');
                    } else {
                        update_post_meta($order->get_id(), '_transaction_id', isset($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']) ? $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'] : '' );
                    }
                    WC()->cart->empty_cart();
                }
                $payeremail = WC()->session->get('payeremail');
                if ($old_wc) {
                    update_post_meta($order_id, '_express_chekout_transactionid', isset($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']) ? $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'] : '');
                    update_post_meta($order_id, 'paypal_email', $payeremail);
                } else {
                    update_post_meta($order->get_id(), '_express_chekout_transactionid', isset($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']) ? $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'] : '' );
                    update_post_meta($order->get_id(), 'paypal_email', $payeremail);
                }
                $order->add_order_note(sprintf(__('%s payment Transaction ID: %s', 'paypal-for-woocommerce'), $this->gateway->title, isset($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']) ? $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'] : ''));
                WC()->cart->empty_cart();
                wc_clear_notices();
                wp_redirect(add_query_arg( 'utm_nooverride', '1', $this->gateway->get_return_url($order) ));
                exit();
            } elseif ($this->response_helper->ec_is_response_successwithwarning($this->paypal_response)) {
                apply_filters( 'woocommerce_payment_successful_result', array('result' => 'success'), $order_id );
                do_action( 'woocommerce_before_pay_action', $order );
                $this->angelleye_ec_get_customer_email_address($this->confirm_order_id);
                $this->angelleye_ec_sellerprotection_handler($this->confirm_order_id);
                $this->angelleye_ec_save_billing_agreement($order_id);
                if ($old_wc) {
                    update_post_meta($order_id, 'is_sandbox', $this->testmode);
                } else {
                    update_post_meta($order->get_id(), 'is_sandbox', $this->testmode);
                }

                if ($this->paypal_response['PAYMENTINFO_0_PAYMENTSTATUS'] == 'Completed') {
                    $order->payment_complete($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']);
                } else {
                    if ($this->fraud_management_filters == 'place_order_on_hold_for_further_review' && (!empty($this->paypal_response['L_ERRORCODE0']) && $this->paypal_response['L_ERRORCODE0'] == '11610')) {
                        $error = !empty($this->paypal_response['L_LONGMESSAGE0']) ? $this->paypal_response['L_LONGMESSAGE0'] : $this->paypal_response['L_SHORTMESSAGE0'];
                        $order->update_status('on-hold', $error);
                        $old_wc = version_compare(WC_VERSION, '3.0', '<');
                        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
                        if ( $old_wc ) {
                            if ( ! get_post_meta( $order_id, '_order_stock_reduced', true ) ) {
                                $order->reduce_order_stock();
                            } 
                        } else {
                            wc_maybe_reduce_stock_levels( $order_id );
                        }
                    } else {
                        $this->update_payment_status_by_paypal_responce($this->confirm_order_id, $this->paypal_response);
                    }
                    if ($old_wc) {
                        update_post_meta($order_id, '_transaction_id', $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']);
                    } else {
                        update_post_meta($order->get_id(), '_transaction_id', $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']);
                    }
                    WC()->cart->empty_cart();
                }
                if ($old_wc) {
                    update_post_meta($order_id, '_express_chekout_transactionid', isset($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']) ? $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'] : '');
                } else {
                    update_post_meta($order->get_id(), '_express_chekout_transactionid', isset($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']) ? $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'] : '' );
                }

                $order->add_order_note(sprintf(__('%s payment Transaction ID: %s', 'paypal-for-woocommerce'), $this->gateway->title, $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']));
                
                WC()->cart->empty_cart();
                wc_clear_notices();
                wp_redirect(add_query_arg( 'utm_nooverride', '1', $this->gateway->get_return_url($order) ));
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
                $order = new WC_Order($this->confirm_order_id);
                $customer_note_value = version_compare(WC_VERSION, '3.0', '<') ? wptexturize($order->customer_note) : wptexturize($order->get_customer_note());
                $customer_note = $customer_note_value ? substr(preg_replace("/[^A-Za-z0-9 ]/", "", $customer_note_value), 0, 256) : '';
            } else {
                
            }
            $this->order_param = $this->gateway_calculation->order_calculation($this->confirm_order_id);
            $this->angelleye_load_paypal_class($this->gateway, $this, $this->confirm_order_id);
            $paypal_express_checkout = WC()->session->get('paypal_express_checkout');
            if( empty($paypal_express_checkout['token'])) {
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
                'amt' => AngellEYE_Gateway_Paypal::number_format($order->get_total()),
                'currencycode' => version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency(),
                'shippingdiscamt' => '',
                'insuranceoptionoffered' => '',
                'handlingamt' => '',
                'desc' => '',
                'custom' => apply_filters('ae_ppec_custom_parameter', json_encode(array('order_id' => version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id(), 'order_key' => version_compare(WC_VERSION, '3.0', '<') ? $order->order_key : $order->get_order_key()))),
                'invnum' => $this->gateway->invoice_id_prefix . preg_replace("/[^a-zA-Z0-9]/", "", str_replace("#", "", $order->get_order_number())),
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
            if ($this->gateway->send_items) {
                $Payment['order_items'] = $this->order_param['order_items'];
            } else {
                $Payment['order_items'] = array();
            }
            $Payment['taxamt'] = AngellEYE_Gateway_Paypal::number_format($this->order_param['taxamt']);
            $Payment['shippingamt'] = AngellEYE_Gateway_Paypal::number_format($this->order_param['shippingamt']);
            $Payment['itemamt'] = AngellEYE_Gateway_Paypal::number_format($this->order_param['itemamt']);

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
            $this->paypal_response = $this->paypal->DoExpressCheckoutPayment(apply_filters('angelleye_woocommerce_express_checkout_do_express_checkout_payment_request_args', $this->paypal_request));
            $this->angelleye_write_paypal_request_log($paypal_action_name = 'DoExpressCheckoutPayment');
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_load_paypal_class($gateway, $current, $order_id = null) {
        do_action( 'angelleye_paypal_for_woocommerce_multi_account_api_paypal_express', $gateway, $current, $order_id);
         $this->credentials = array(
            'Sandbox' => $this->testmode,
            'APIUsername' => $this->api_username,
            'APIPassword' => $this->api_password,
            'APISignature' => $this->api_signature,
            'Force_tls_one_point_two' => $this->gateway->Force_tls_one_point_two
        );
        try {
            if (!class_exists('Angelleye_PayPal')) {
                require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.class.php' );
            }
            $this->paypal = new Angelleye_PayPal($this->credentials);
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_set_express_checkout_request() {
        try {
            $Payments = array();
            $cancel_url = !empty($this->gateway->cancel_page_id) ? get_permalink($this->gateway->cancel_page_id) : wc_get_cart_url();
            if($cancel_url == false) {
                $cancel_url = wc_get_cart_url();
            }
            $cancel_url = add_query_arg( 'utm_nooverride', '1', $cancel_url );
            $order_total = '';
            $returnurl = urldecode(add_query_arg( array('pp_action' => 'get_express_checkout_details', 'utm_nooverride' => 1), WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE')));
            if( !empty($_GET['pay_for_order']) && $_GET['pay_for_order'] == true && !empty($_GET['key'])) {
                if (version_compare(WC_VERSION, '3.0', '<')) {
                    $order_id = woocommerce_get_order_id_by_order_key($_GET['key']);
                } else {
                    $order_id = wc_get_order_id_by_order_key($_GET['key']);
                }
                $this->cart_param = $this->gateway_calculation->order_calculation($order_id);
                $order = wc_get_order($order_id);
                $order_total = $order->get_total();
                $returnurl = urldecode( add_query_arg( array(
                    'pp_action' => 'get_express_checkout_details',
                    'pay_for_order' => true,
                    'key' => $_GET['key'],
                    'order_id' => $order_id,
                    'utm_nooverride' => 1
                ), WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE') ) );
                WC()->session->set( 'order_awaiting_payment', $order_id );
            } else {
                $this->cart_param = $this->gateway_calculation->cart_calculation();
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
                'localecode' => ($this->gateway->use_wp_locale_code == 'yes' && get_locale() != '') ? get_locale() : '',
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
            $Payment = array(
                'amt' => AngellEYE_Gateway_Paypal::number_format($order_total),
                'currencycode' => get_woocommerce_currency(),
                'custom' => apply_filters('ae_ppec_custom_parameter', ''),
                'notetext' => '',
                'paymentaction' => ($this->gateway->payment_action == 'Authorization' || WC()->cart->total == 0 ) ? 'Authorization' : $this->gateway->payment_action,
            );
            
            if( empty($_GET['pay_for_order']) ) {
                
                $post_data = WC()->session->get('post_data');
                if (!empty($post_data)) {
                    $SECFields['addroverride'] = WC()->cart->needs_shipping() ? 1 : 0;
                    if ( !empty($post_data['ship_to_different_address'])) {
                        $shiptoname = '';
                        if( !empty($post_data['shipping_first_name']) && !empty($post_data['shipping_last_name'])) {
                            $shiptoname = $post_data['shipping_first_name'] . ' ' . $post_data['shipping_last_name'];
                        } elseif (!empty($post_data['shipping_first_name'])) {
                            $shiptoname = $post_data['shipping_first_name'];
                        } elseif (!empty($post_data['shipping_last_name'])) {
                            $shiptoname = $post_data['shipping_last_name'];
                        }
                        
                        if( !empty($post_data['shipping_company']) ) {
                            $shipping_company = $post_data['shipping_company'];
                            $Payment['shiptoname'] = wc_clean(stripslashes($shipping_company .' - '. $shiptoname));
                        } else {
                            $Payment['shiptoname'] = wc_clean(stripslashes($shiptoname));
                        }
                        
                        $Payment['shiptostreet'] = !empty($post_data['shipping_address_1']) ? $post_data['shipping_address_1'] : '';
                        $Payment['shiptostreet2'] = !empty($post_data['shipping_address_2']) ? $post_data['shipping_address_2'] : '';
                        $Payment['shiptocity'] = !empty($post_data['shipping_city']) ? wc_clean(stripslashes($post_data['shipping_city'])) : ''; 
                        $Payment['shiptostate'] = !empty($post_data['shipping_state']) ? $post_data['shipping_state'] : '';
                        $Payment['shiptozip'] = !empty($post_data['shipping_postcode']) ? $post_data['shipping_postcode'] : '';
                        $Payment['shiptocountrycode'] = !empty($post_data['shipping_country']) ? $post_data['shipping_country'] : '';
                        $Payment['shiptophonenum'] = !empty($post_data['billing_phone']) ? $post_data['billing_phone'] : '';
                    } else {
                        $shiptoname = '';
                        if( !empty($post_data['billing_first_name']) && !empty($post_data['billing_last_name'])) {
                            $shiptoname = $post_data['billing_first_name'] . ' ' . $post_data['billing_last_name'];
                        } elseif (!empty($post_data['billing_first_name'])) {
                            $shiptoname = $post_data['billing_first_name'];
                        } elseif (!empty($post_data['billing_last_name'])) {
                            $shiptoname = $post_data['billing_last_name'];
                        }
                        
                        if( !empty($post_data['billing_company']) ) {
                            $billing_company = $post_data['billing_company'];
                            $Payment['shiptoname'] = wc_clean(stripslashes($billing_company .' - '. $shiptoname));
                        } else {
                            $Payment['shiptoname'] = wc_clean(stripslashes($shiptoname));
                        }
                        
                        $Payment['shiptostreet'] = !empty($post_data['billing_address_1']) ? $post_data['billing_address_1'] : '';
                        $Payment['shiptostreet2'] = !empty($post_data['billing_address_2']) ? $post_data['billing_address_2'] : ''; 
                        $Payment['shiptocity'] = !empty($post_data['billing_city']) ? wc_clean(stripslashes($post_data['billing_city'])) : ''; 
                        $Payment['shiptostate'] = !empty($post_data['billing_state']) ? $post_data['billing_state'] : '';
                        $Payment['shiptozip'] = !empty($post_data['billing_postcode']) ? $post_data['billing_postcode'] : '';
                        $Payment['shiptocountrycode'] = !empty($post_data['billing_country']) ? $post_data['billing_country'] : '';
                        $Payment['shiptophonenum'] = !empty($post_data['billing_phone']) ? $post_data['billing_phone'] : '';
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
                    }
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
                $Payment['shiptostreet'] = $shipping_address_1;
                $Payment['shiptostreet2'] = $shipping_address_2;
                $Payment['shiptocity'] = wc_clean(stripslashes($shipping_city));
                $Payment['shiptostate'] = $shipping_state;
                $Payment['shiptozip'] = $shipping_postcode;
                $Payment['shiptocountrycode'] = $shipping_country;
            }
            if ($this->gateway->send_items) {
                $Payment['order_items'] = $this->cart_param['order_items'];
            } else {
                $Payment['order_items'] = array();
            }
            $Payment['taxamt'] = $this->cart_param['taxamt'];
            $Payment['shippingamt'] = $this->cart_param['shippingamt'];
            $Payment['itemamt'] = $this->cart_param['itemamt'];
            array_push($Payments, $Payment);
            $PayPalRequestData = array(
                'SECFields' => $SECFields,
                'Payments' => $Payments,
            );
            $this->paypal_request = $this->angelleye_add_billing_agreement_param($PayPalRequestData, $this->gateway->supports('tokenization'));
            $this->paypal_request = AngellEYE_Utility::angelleye_express_checkout_validate_shipping_address($this->paypal_request);
            $this->paypal_response = $this->paypal->SetExpressCheckout(apply_filters('angelleye_woocommerce_express_checkout_set_express_checkout_request_args', $this->paypal_request));
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
                    $ec_save_to_account = WC()->session->get('ec_save_to_account');
                    if ($_paypal_billing_agreement == 'yes' || ( isset($ec_save_to_account) && $ec_save_to_account == 'on') || AngellEYE_Utility::angelleye_paypal_for_woo_wc_autoship_cart_has_autoship_item() || AngellEYE_Utility::is_cart_contains_subscription() == true) {
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
            $order = wc_get_order($orderid);
            $old_wc = version_compare( WC_VERSION, '3.0', '<' );
            if(!empty($result['PAYMENTINFO_0_PAYMENTSTATUS'])) {
                $payment_status = $result['PAYMENTINFO_0_PAYMENTSTATUS'];
            } elseif ( !empty ($result['PAYMENTSTATUS'])) {
                $payment_status = $result['PAYMENTSTATUS'];
            }
            if( !empty($result['PAYMENTINFO_0_TRANSACTIONTYPE']) ) {
                $transaction_type = $result['PAYMENTINFO_0_TRANSACTIONTYPE'];
            } elseif ( !empty ($result['TRANSACTIONTYPE'])) {
                $transaction_type = $result['TRANSACTIONTYPE'];
            }
            if( !empty($result['PAYMENTINFO_0_TRANSACTIONID']) ) {
                $transaction_id = $result['PAYMENTINFO_0_TRANSACTIONID'];
            } elseif ( !empty ($result['BILLINGAGREEMENTID'])) {
                $transaction_id = $result['BILLINGAGREEMENTID'];
            }
            if( !empty($result['PAYMENTINFO_0_PENDINGREASON']) ) {
                $pending_reason = $result['PAYMENTINFO_0_PENDINGREASON'];
            } elseif ( !empty ($result['PENDINGREASON'])) {
                $pending_reason = $result['PENDINGREASON'];
            }
            switch (strtolower($payment_status)) :
                case 'completed' :
                    $order_status = version_compare(WC_VERSION, '3.0', '<') ? $order->status : $this->order->get_status();
                    if ($order_status == 'completed') {
                        break;
                    }
                    if (!in_array(strtolower($transaction_type), array('merchtpmt', 'cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money'))) {
                        break;
                    }
                    $order->add_order_note(__('Payment Completed via Express Checkout', 'paypal-for-woocommerce'));
                    $order->payment_complete($transaction_id);
                    break;
                case 'pending' :
                    if (!in_array(strtolower($transaction_type), array('merchtpmt', 'cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money', 'expresscheckout'))) {
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
                    if ( strtolower($pending_reason) == 'authorization' && $this->pending_authorization_order_status == 'Processing' ) {
                        $order->payment_complete($transaction_id);
                    } else {
                        $order->update_status('on-hold');
                        if ( $old_wc ) {
                            if ( ! get_post_meta( $orderid, '_order_stock_reduced', true ) ) {
                                $order->reduce_order_stock();
                            } 
                        } else {
                            wc_maybe_reduce_stock_levels( $orderid );
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
            $paypal_express_checkout = WC()->session->get('paypal_express_checkout');
            $payment_order_meta = array('_transaction_id' => $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'], '_payment_action' => $this->gateway->payment_action, '_express_checkout_token' => $paypal_express_checkout['token'], '_first_transaction_id' => $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']);
            AngellEYE_Utility::angelleye_add_order_meta($order_id, $payment_order_meta);
        }
    }

    public function angelleye_add_order_note($order) {
        $paypal_express_checkout = WC()->session->get('paypal_express_checkout');
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
        $order->add_order_note(sprintf(__('PayPal %s API call failed:', 'paypal-for-woocommerce') . __('Detailed Error Message:', 'paypal-for-woocommerce') . PHP_EOL . __('Short Error Message:', 'paypal-for-woocommerce') . PHP_EOL . __('Error Code:', 'paypal-for-woocommerce') . PHP_EOL . __('Error Severity Code:', 'paypal-for-woocommerce'), $paypal_action_name, $ErrorLongMsg, $ErrorShortMsg, $ErrorCode, $ErrorSeverityCode));
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
            $message = sprintf(__('PayPal %s API call failed', 'paypal-for-woocommerce') . PHP_EOL . __('Detailed Error Message: %s', 'paypal-for-woocommerce') . PHP_EOL . __('Short Error Message: %s', 'paypal-for-woocommerce') . PHP_EOL . __('Error Code: %s', 'paypal-for-woocommerce') . PHP_EOL . __('Error Severity Code: %s', 'paypal-for-woocommerce'), $paypal_action_name, $ErrorLongMsg, $ErrorShortMsg, $ErrorCode, $ErrorSeverityCode);
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
        if( AngellEYE_Utility::is_cart_contains_subscription() == false ) {
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
                if( AngellEYE_Utility::is_cart_contains_subscription() == false ) {
                     wc_add_notice(sprintf(__('We do not sell in your country, please try again with another address.', 'paypal-for-woocommerce')), 'error');
                }
                wp_redirect(get_permalink(wc_get_page_id('cart')));
                exit;
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
        if ( 'US' === $cc ) {
            return $state;
        }
        $states = WC()->countries->get_states( $cc );
        if ( isset( $states[ $state ] ) ) {
            return $states[ $state ];
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
            if ( 0 != $order->get_user_id() ) {
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
            if( $token->validate() ) {
                $save_result = $token->save();
                if ($save_result) {
                    $order->add_payment_token($token);
                }
            } else {
                throw new Exception( __( 'Invalid or missing payment token fields.', 'paypal-for-woocommerce' ) );
            }
        }
        if (!empty($this->paypal_response['BILLINGAGREEMENTID'])) {
            update_post_meta($order_id, 'BILLINGAGREEMENTID', isset($this->paypal_response['BILLINGAGREEMENTID']) ? $this->paypal_response['BILLINGAGREEMENTID'] : '');
            update_post_meta($order_id, '_payment_tokens_id', isset($this->paypal_response['BILLINGAGREEMENTID']) ? $this->paypal_response['BILLINGAGREEMENTID'] : '');
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
                $subscription_id = version_compare( WC_VERSION, '3.0', '<' ) ? $subscription->id : $subscription->get_id();
                update_post_meta($subscription->id, '_payment_tokens_id', $payment_tokens_id);
            }
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
                update_post_meta($order_id, '_billing_email', $userLogined->user_email);
                update_post_meta($order_id, '_customer_user', $userLogined->ID);
            } else {
                update_post_meta($order->get_id(), '_customer_user', $userLogined->ID);
                update_post_meta($order->get_id(), '_billing_email', $userLogined->user_email);
            }
        } else {
            $_billing_email = get_post_meta($order_id, '_billing_email', true);
            if (!empty($_billing_email)) {
                $this->user_email_address = $_billing_email;
            } else {
                $payeremail = WC()->session->get('payeremail');
                $this->user_email_address = $payeremail;
                if ($old_wc) {
                    update_post_meta($order_id, '_billing_email', $payeremail);
                } else {
                    update_post_meta($order->get_id(), '_billing_email', $payeremail);
                }
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
            if ($old_wc) {
                update_post_meta($order_id, '_transaction_id', $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']);
            } else {
                update_post_meta($order->get_id(), '_transaction_id', $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']);
            }
            $this->gateway->process_refund($order_id, $order->get_total(), __('There was a problem processing your order. Please contact customer support.', 'paypal-for-woocommerce'));
            $order->update_status('cancelled');
            if( AngellEYE_Utility::is_cart_contains_subscription() == false ) {
                 wc_add_notice(__('Thank you for your recent order. Unfortunately it has been cancelled and refunded. Please contact our customer support team.', 'paypal-for-woocommerce'), 'error');
            }
            wp_redirect(get_permalink(wc_get_page_id('cart')));
            exit();
        }
    }

    public function DoReferenceTransaction($order_id) {
        $this->angelleye_load_paypal_class($this->gateway, $this, $order_id);
        $PayPalRequestData = array();
        $referenceid = get_post_meta($order_id, '_payment_tokens_id', true);
        if( !empty($_POST['wc-paypal_express-payment-token'])) {
            $token_id = $_POST['wc-paypal_express-payment-token'];
            $token = WC_Payment_Tokens::get($token_id);
            $referenceid = $token->get_token();
        }
        
        $order = wc_get_order($order_id);
        $customer_note_value = version_compare(WC_VERSION, '3.0', '<') ? wptexturize($order->customer_note) : wptexturize($order->get_customer_note());
        $customer_notes = $customer_note_value ? substr(preg_replace("/[^A-Za-z0-9 ]/", "", $customer_note_value), 0, 256) : '';
        $DRTFields = array(
            'referenceid' => $referenceid,
            'paymentaction' => ($this->gateway->payment_action == 'Authorization' || $order->get_total() == 0 ) ? 'Authorization' : $this->gateway->payment_action,
            'returnfmfdetails' => '1',
            'softdescriptor' => $this->softdescriptor
        );
        $PayPalRequestData['DRTFields'] = $DRTFields;
        $PaymentDetails = array(
            'amt' => AngellEYE_Gateway_Paypal::number_format($order->get_total()), // Required. Total amount of the order, including shipping, handling, and tax.
            'currencycode' => version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency(), // A three-character currency code.  Default is USD.
            'itemamt' => '', // Required if you specify itemized L_AMT fields. Sum of cost of all items in this order.
            'shippingamt' => '', // Total shipping costs for this order.  If you specify SHIPPINGAMT you mut also specify a value for ITEMAMT.
            'insuranceamt' => '',
            'shippingdiscount' => '',
            'handlingamt' => '', // Total handling costs for this order.  If you specify HANDLINGAMT you mut also specify a value for ITEMAMT.
            'taxamt' => '', // Required if you specify itemized L_TAXAMT fields.  Sum of all tax items in this order.
            'insuranceoptionoffered' => '', // If true, the insurance drop-down on the PayPal review page displays Yes and shows the amount.
            'desc' => '', // Description of items on the order.  127 char max.
            'custom' => apply_filters('ae_ppec_custom_parameter', json_encode(array('order_id' => version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id(), 'order_key' => version_compare(WC_VERSION, '3.0', '<') ? $order->order_key : $order->get_order_key()))), // Free-form field for your own use.  256 char max.
            'invnum' => $this->gateway->invoice_id_prefix . preg_replace("/[^a-zA-Z0-9]/", "", str_replace("#", "", $order->get_order_number())), // Your own invoice or tracking number.  127 char max.
            'buttonsource' => ''     // URL for receiving Instant Payment Notifications
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
        $this->order_param = $this->gateway_calculation->order_calculation($order_id);
        if ($this->gateway->send_items) {
            $Payment['order_items'] = $this->order_param['order_items'];
        } else {
            $Payment['order_items'] = array();
        }
        $PaymentDetails['taxamt'] = AngellEYE_Gateway_Paypal::number_format($this->order_param['taxamt']);
        $PaymentDetails['shippingamt'] = AngellEYE_Gateway_Paypal::number_format($this->order_param['shippingamt']);
        $PaymentDetails['itemamt'] = AngellEYE_Gateway_Paypal::number_format($this->order_param['itemamt']);
        $PayPalRequestData['PaymentDetails'] = $PaymentDetails;
        $this->paypal_response = $this->paypal->DoReferenceTransaction($PayPalRequestData);
        AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($this->paypal_response, $methos_name = 'DoExpressCheckoutPayment', $gateway = 'PayPal Express Checkout', $this->gateway->error_email_notify);
        WC_Gateway_PayPal_Express_AngellEYE::log('Test Mode: ' . $this->testmode);
        WC_Gateway_PayPal_Express_AngellEYE::log('Endpoint: ' . $this->gateway->API_Endpoint);
        $PayPalRequest = isset($this->paypal_response['RAWREQUEST']) ? $this->paypal_response['RAWREQUEST'] : '';
        $PayPalResponse = isset($this->paypal_response['RAWRESPONSE']) ? $this->paypal_response['RAWRESPONSE'] : '';
        WC_Gateway_PayPal_Express_AngellEYE::log('Request: ' . print_r($this->paypal->NVPToArray($this->paypal->MaskAPIResult($PayPalRequest)), true));
        WC_Gateway_PayPal_Express_AngellEYE::log('Response: ' . print_r($this->paypal->NVPToArray($this->paypal->MaskAPIResult($PayPalResponse)), true));
        return $this->paypal_response;
    }

    public function angelleye_ec_force_to_display_checkout_page() {
        $this->enable_guest_checkout = get_option('woocommerce_enable_guest_checkout') == 'yes' ? true : false;
        $this->must_create_account = $this->enable_guest_checkout || is_user_logged_in() ? false : true;
        $force_to_display_checkout_page = true;
        if ($this->skip_final_review == 'no') {
            return apply_filters('angelleye_ec_force_to_display_checkout_page', true);
        }
        if( 'yes' === get_option( 'woocommerce_registration_generate_username' ) && 'yes' === get_option( 'woocommerce_registration_generate_password' ) ) {
            $this->must_create_account = false;
        }
        if ($this->must_create_account) {
            return apply_filters('angelleye_ec_force_to_display_checkout_page', true);
        }
        if(AngellEYE_Utility::is_cart_contains_subscription() == true) {
            return apply_filters('angelleye_ec_force_to_display_checkout_page', true);
        }
        $paypal_express_terms = WC()->session->get('paypal_express_terms');
        if (wc_get_page_id('terms') > 0 && apply_filters('woocommerce_checkout_show_terms', true)) {
            if ($this->disable_term) {
                return apply_filters('angelleye_ec_force_to_display_checkout_page', false);
            } elseif ( (isset($_POST['terms']) || isset ($_POST['legal'])) && $_POST['terms'] == 'on') {
                return apply_filters('angelleye_ec_force_to_display_checkout_page', false);
            } elseif ( !empty($paypal_express_terms) && $paypal_express_terms == true ) {
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
            'amt' => AngellEYE_Gateway_Paypal::number_format($amount),
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
            $max_remaining_refund = wc_format_decimal($order->get_total() - $order->get_total_refunded());
            if (!$max_remaining_refund > 0) {
                $order->update_status('refunded');
            }
            if (ob_get_length())
                ob_end_clean();
            return true;
        } else {
            $ec_message = apply_filters('ae_ppec_refund_error_message', $this->paypal_response['L_LONGMESSAGE0'], $this->paypal_response['L_ERRORCODE0'], $this->paypal_response);
            return new WP_Error('ec_refund-error', $ec_message);
        }
    }
    
    public function angelleye_process_customer($order_id) {
        $post_data = WC()->session->get( 'post_data' );
        $paypal_express_checkout = WC()->session->get('paypal_express_checkout');
        if( !empty($post_data) && !empty($post_data['billing_first_name']) && !empty($post_data['billing_last_name']) && !empty($post_data['billing_email']) ) {
            $first_name = !empty($post_data['billing_first_name']) ? $post_data['billing_first_name'] : '';
            $last_name = !empty($post_data['billing_last_name']) ? $post_data['billing_last_name'] : '';
            $email = !empty($post_data['billing_email']) ? $post_data['billing_email'] : '';
        } else {
            if( !empty ($paypal_express_checkout) ) {
                $first_name = !empty($paypal_express_checkout['ExpresscheckoutDetails']['FIRSTNAME']) ? $paypal_express_checkout['ExpresscheckoutDetails']['FIRSTNAME'] : '';
                $last_name = !empty($paypal_express_checkout['ExpresscheckoutDetails']['LASTNAME']) ? $paypal_express_checkout['ExpresscheckoutDetails']['LASTNAME'] : '';
                $email = !empty($paypal_express_checkout['ExpresscheckoutDetails']['EMAIL']) ? $paypal_express_checkout['ExpresscheckoutDetails']['EMAIL'] : '';
            } 
        }
        if( !empty($email)) {
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
                if ( 'yes' === get_option( 'woocommerce_registration_generate_password' ) ) {
                    $password = '';
                } else {
                    $password = wp_generate_password();
                }
                WC()->session->set('before_wc_create_new_customer', true);
                $new_customer = wc_create_new_customer($email, $username, $password);
                if (is_wp_error($new_customer)) {
                    throw new Exception($new_customer->get_error_message());
                } else {
                    $customer_id = absint($new_customer);
                    do_action('woocommerce_guest_customer_new_account_notification', $customer_id);
                }
            }
            wc_set_customer_auth_cookie($customer_id);
            WC()->session->set('reload_checkout', true);
            WC()->cart->calculate_totals();
            if ($first_name && apply_filters('woocommerce_checkout_update_customer_data', true, WC()->customer)) {
                $userdata = array(
                    'ID' => $customer_id,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'display_name' => $first_name
                );
                update_post_meta( $order_id, '_customer_user', $customer_id );
                wp_update_user(apply_filters('woocommerce_checkout_customer_userdata', $userdata, WC()->customer));
                wc_clear_notices();
            }
        }
    }
}
