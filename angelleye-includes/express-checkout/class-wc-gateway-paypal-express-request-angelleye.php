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

    public function __construct($gateway) {
        try {
            $this->gateway = $gateway;
            $this->skip_final_review = $this->gateway->get_option('skip_final_review', 'no');
            $this->credentials = array(
                'Sandbox' => $this->gateway->testmode == 'yes' ? TRUE : FALSE,
                'APIUsername' => $this->gateway->api_username,
                'APIPassword' => $this->gateway->api_password,
                'APISignature' => $this->gateway->api_signature,
                'Force_tls_one_point_two' => $this->gateway->Force_tls_one_point_two
            );
            $this->angelleye_load_paypal_class();
            if (!class_exists('WC_Gateway_Calculation_AngellEYE')) {
                require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/wc-gateway-gateway-calculations-angelleye.php' );
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
                // todo
                // need to display notice and redirect to cart page.
            }
            $token = esc_attr($_GET['token']);
            $this->paypal_response = $this->paypal->GetExpresscheckoutDetails($token);
            $this->angelleye_write_paypal_request_log($paypal_action_name = 'GetExpresscheckoutDetails');
            if ($this->response_helper->ec_is_response_success($this->paypal_response)) {
                WC()->session->paypal_express_checkout = array(
                    'token' => $token,
                    'shipping_details' => $this->response_helper->ec_get_shipping_details($this->paypal_response),
                    'order_note' => $this->response_helper->ec_get_note_text($this->paypal_response),
                    'payer_id' => $this->response_helper->ec_get_payer_id($this->paypal_response),
                    'ExpresscheckoutDetails' => $this->paypal_response
                );
                WC()->session->shiptoname = $this->paypal_response['FIRSTNAME'] . ' ' . $this->paypal_response['LASTNAME'];
                WC()->session->payeremail = $this->paypal_response['EMAIL'];
                WC()->session->chosen_payment_method = get_class($this->gateway);
                if ($this->skip_final_review == 'no') {
                    wp_redirect(WC()->cart->get_checkout_url());
                    exit();
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
            $this->confirm_order_id = esc_attr($_GET['order_id']);
            $order = new WC_Order($this->confirm_order_id);
            $this->angelleye_do_express_checkout_payment_request();
            $this->angelleye_add_order_note($order);
            $this->angelleye_add_extra_order_meta($order);
            if ($this->response_helper->ec_is_response_success_or_successwithwarning($this->paypal_response)) {
                $is_sandbox = $this->gateway->testmode == 'yes' ? true : false;
                update_post_meta($order->id, 'is_sandbox', $is_sandbox);
                if ($this->paypal_response['PAYMENTINFO_0_PAYMENTSTATUS'] == 'Completed') {
                    $order->payment_complete($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']);
                } else {
                    $this->update_payment_status_by_paypal_responce($this->confirm_order_id, $this->paypal_response);
                    update_post_meta($order->id, '_transaction_id', $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']);
                    $order->reduce_order_stock();
                    WC()->cart->empty_cart();
                }
                update_post_meta($order->id, '_express_chekout_transactionid', isset($this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']) ? $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'] : '');
                WC()->cart->empty_cart();
                wc_clear_notices();
                wp_redirect($this->gateway->get_return_url($order));
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
                $invoice_number = preg_replace("/[^a-zA-Z0-9]/", "", $order->get_order_number());
                if ($order->customer_note) {
                    $customer_notes = wptexturize($order->customer_note);
                }
            } else {
                // error
            }
            $this->order_param = $this->gateway_calculation->order_calculation($this->confirm_order_id);
            $DECPFields = array(
                'token' => WC()->session->paypal_express_checkout['token'],
                'payerid' => (!empty(WC()->session->paypal_express_checkout['payer_id']) ) ? WC()->session->paypal_express_checkout['payer_id'] : null,
                'returnfmfdetails' => 1,
                'buyermarketingemail' => '',
                'allowedpaymentmethod' => ''
            );
            $Payments = array();
            $Payment = array(
                'amt' => AngellEYE_Gateway_Paypal::number_format($order->order_total),
                'currencycode' => $order->get_order_currency(),
                'shippingdiscamt' => '',
                'insuranceoptionoffered' => '',
                'handlingamt' => '',
                'desc' => '',
                'custom' => '',
                'invnum' => $this->gateway->invoice_id_prefix . $invoice_number,
                'notetext' => !empty($customer_notes) ? $customer_notes : '',
                'allowedpaymentmethod' => '',
                'paymentaction' => $this->gateway->payment_action,
                'paymentrequestid' => '',
                'sellerpaypalaccountid' => '',
                'sellerid' => '',
                'sellerusername' => '',
                'sellerregistrationdate' => '',
                'softdescriptor' => ''
            );
            if (isset($this->gateway->notifyurl) && !empty($this->gateway->notifyurl)) {
                $Payment['notifyurl'] = $this->gateway->notifyurl;
            }
            if ($this->gateway->send_items) {
                $Payment['order_items'] = $this->cart_param['order_items'];
            } else {
                $Payment['order_items'] = array();
            }
            $Payment['taxamt'] = AngellEYE_Gateway_Paypal::number_format($this->order_param['taxamt']);
            $Payment['shippingamt'] = AngellEYE_Gateway_Paypal::number_format($this->order_param['shippingamt']);
            $Payment['itemamt'] = AngellEYE_Gateway_Paypal::number_format($this->order_param['itemamt']);
            $REVIEW_RESULT = !empty(WC()->session->paypal_express_checkout['ExpresscheckoutDetails']) ? WC()->session->paypal_express_checkout['ExpresscheckoutDetails'] : array();
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
                array_push($Payments, $Payment);
            } else {
                array_push($Payments, $Payment);
            }
            if (WC()->cart->needs_shipping()) {
                $shipping_first_name = $order->shipping_first_name;
                $shipping_last_name = $order->shipping_last_name;
                $shipping_address_1 = $order->shipping_address_1;
                $shipping_address_2 = $order->shipping_address_2;
                $shipping_city = $order->shipping_city;
                $shipping_state = $order->shipping_state;
                $shipping_postcode = $order->shipping_postcode;
                $shipping_country = $order->shipping_country;
                $Payment = array('shiptoname' => $shipping_first_name . ' ' . $shipping_last_name,
                    'shiptostreet' => $shipping_address_1,
                    'shiptostreet2' => $shipping_address_2,
                    'shiptocity' => wc_clean(stripslashes($shipping_city)),
                    'shiptostate' => $shipping_state,
                    'shiptozip' => $shipping_postcode,
                    'shiptocountrycode' => $shipping_country,
                    'shiptophonenum' => '',
                );
                array_push($Payments, $Payment);
            }
            $this->paypal_request = array(
                'DECPFields' => $DECPFields,
                'Payments' => $Payments
            );
            $this->paypal_response = $this->paypal->DoExpressCheckoutPayment($this->paypal_request);
            $this->angelleye_write_paypal_request_log($paypal_action_name = 'DoExpressCheckoutPayment');
        } catch (Exception $ex) {

        }
    }

    public function angelleye_load_paypal_class() {
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
            $cancel_url = !empty($this->gateway->cancel_page_id) ? get_permalink($this->gateway->cancel_page_id) : WC()->cart->get_cart_url();
            $this->cart_param = $this->gateway_calculation->cart_calculation();
            $SECFields = array(
                'maxamt' => '',
                'returnurl' => urldecode(add_query_arg('pp_action', 'get_express_checkout_details', WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE'))),
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
                'skipdetails' => $this->gateway->skip_final_review == 'yes' ? '1' : '0',
                'email' => '',
                'channeltype' => '',
                'giropaysuccessurl' => '',
                'giropaycancelurl' => '',
                'banktxnpendingurl' => '',
                'brandname' => $this->gateway->brand_name,
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
            $Payments = array();
            $Payment = array(
                'amt' => AngellEYE_Gateway_Paypal::number_format(WC()->cart->total),
                'currencycode' => get_woocommerce_currency(),
                'custom' => apply_filters('ae_ppec_custom_parameter', ''),
                'notetext' => '',
                'paymentaction' => $this->gateway->payment_action,
            );
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
            $this->paypal_response = $this->paypal->SetExpressCheckout($this->paypal_request);
            $this->angelleye_write_paypal_request_log($paypal_action_name = 'SetExpressCheckout');
            return $this->paypal_response;
        } catch (Exception $ex) {

        }
    }

    public function angelleye_add_billing_agreement_param($PayPalRequestData, $tokenization) {
        try {
            if (sizeof(WC()->cart->get_cart()) != 0) {
                foreach (WC()->cart->get_cart() as $key => $value) {
                    $_product = $value['data'];
                    if (isset($_product->id) && !empty($_product->id)) {
                        $_paypal_billing_agreement = get_post_meta($_product->id, '_paypal_billing_agreement', true);
                        if ($_paypal_billing_agreement == 'yes' || $tokenization == true) {
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
            }
            return $PayPalRequestData;
        } catch (Exception $ex) {

        }
    }

    public function update_payment_status_by_paypal_responce($orderid, $result) {
        try {
            $order = wc_get_order($orderid);
            switch (strtolower($result['PAYMENTINFO_0_PAYMENTSTATUS'])) :
                case 'completed' :
                    if ($order->status == 'completed') {
                        break;
                    }
                    if (!in_array(strtolower($result['PAYMENTINFO_0_TRANSACTIONTYPE']), array('cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money'))) {
                        break;
                    }
                    $order->add_order_note(__('Payment Completed via Express Checkout', 'express-checkout'));
                    $order->payment_complete($result['PAYMENTINFO_0_TRANSACTIONID']);
                    break;
                case 'pending' :
                    if (!in_array(strtolower($result['PAYMENTINFO_0_TRANSACTIONTYPE']), array('cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money'))) {
                        break;
                    }
                    switch (strtolower($result['PAYMENTINFO_0_PENDINGREASON'])) {
                        case 'address':
                            $pending_reason = __('Address: The payment is pending because your customer did not include a confirmed shipping address and your Payment Receiving Preferences is set such that you want to manually accept or deny each of these payments. To change your preference, go to the Preferences section of your Profile.', 'express-checkout');
                            break;
                        case 'authorization':
                            $pending_reason = __('Authorization: The payment is pending because it has been authorized but not settled. You must capture the funds first.', 'express-checkout');
                            break;
                        case 'echeck':
                            $pending_reason = __('eCheck: The payment is pending because it was made by an eCheck that has not yet cleared.', 'express-checkout');
                            break;
                        case 'intl':
                            $pending_reason = __('intl: The payment is pending because you hold a non-U.S. account and do not have a withdrawal mechanism. You must manually accept or deny this payment from your Account Overview.', 'express-checkout');
                            break;
                        case 'multicurrency':
                        case 'multi-currency':
                            $pending_reason = __('Multi-currency: You do not have a balance in the currency sent, and you do not have your Payment Receiving Preferences set to automatically convert and accept this payment. You must manually accept or deny this payment.', 'express-checkout');
                            break;
                        case 'order':
                            $pending_reason = __('Order: The payment is pending because it is part of an order that has been authorized but not settled.', 'express-checkout');
                            break;
                        case 'paymentreview':
                            $pending_reason = __('Payment Review: The payment is pending while it is being reviewed by PayPal for risk.', 'express-checkout');
                            break;
                        case 'unilateral':
                            $pending_reason = __('Unilateral: The payment is pending because it was made to an email address that is not yet registered or confirmed.', 'express-checkout');
                            break;
                        case 'verify':
                            $pending_reason = __('Verify: The payment is pending because you are not yet verified. You must verify your account before you can accept this payment.', 'express-checkout');
                            break;
                        case 'other':
                            $pending_reason = __('Other: For more information, contact PayPal customer service.', 'express-checkout');
                            break;
                        case 'none':
                        default:
                            $pending_reason = __('No pending reason provided.', 'express-checkout');
                            break;
                    }
                    $order->add_order_note(sprintf(__('Payment via Express Checkout Pending. PayPal reason: %s.', 'express-checkout'), $pending_reason));
                    $order->update_status('on-hold');
                    break;
                case 'denied' :
                case 'expired' :
                case 'failed' :
                case 'voided' :
                    $order->update_status('failed', sprintf(__('Payment %s via Express Checkout.', 'express-checkout'), strtolower($result['PAYMENTINFO_0_PAYMENTSTATUS'])));
                    break;
                default:
                    break;
            endswitch;
            return;
        } catch (Exception $ex) {

        }
    }

    public function angelleye_add_extra_order_meta($order) {
        if (!empty($this->gateway->payment_action) && $this->gateway->payment_action != 'Sale') {
            $payment_order_meta = array('_transaction_id' => $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID'], '_payment_action' => $this->gateway->payment_action, '_express_checkout_token' => WC()->session->paypal_express_checkout['token'], '_first_transaction_id' => $this->paypal_response['PAYMENTINFO_0_TRANSACTIONID']);
            AngellEYE_Utility::angelleye_add_order_meta($order->id, $payment_order_meta);
        }
    }

    public function angelleye_add_order_note($order) {
        if (!empty(WC()->session->paypal_express_checkout['ExpresscheckoutDetails']['PAYERSTATUS'])) {
            $order->add_order_note(sprintf(__('Payer Status: %s', 'paypal-for-woocommerce'), '<strong>' . WC()->session->paypal_express_checkout['ExpresscheckoutDetails']['PAYERSTATUS'] . '</strong>'));
        }
        if (!empty(WC()->session->paypal_express_checkout['ExpresscheckoutDetails']['ADDRESSSTATUS'])) {
            $order->add_order_note(sprintf(__('Address Status: %s', 'paypal-for-woocommerce'), '<strong>' . WC()->session->paypal_express_checkout['ExpresscheckoutDetails']['ADDRESSSTATUS'] . '</strong>'));
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
        $order->add_order_note(sprintf(__('PayPal %s API call failed:', 'woocommerce') . __('Detailed Error Message:', 'woocommerce') . PHP_EOL . __('Short Error Message:', 'woocommerce') . PHP_EOL . __('Error Code:', 'woocommerce') . PHP_EOL . __('Error Severity Code:', 'woocommerce'), $paypal_action_name, $ErrorLongMsg, $ErrorShortMsg, $ErrorCode, $ErrorSeverityCode));
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
            $message = sprintf(__('PayPal %s API call failed:', 'woocommerce') . __('Detailed Error Message:', 'woocommerce') . PHP_EOL . __('Short Error Message:', 'woocommerce') . PHP_EOL . __('Error Code:', 'woocommerce') . PHP_EOL . __('Error Severity Code:', 'woocommerce'), $paypal_action_name, $ErrorLongMsg, $ErrorShortMsg, $ErrorCode, $ErrorSeverityCode);
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
        wc_add_notice($error_display_type_message, 'error');
    }

    public function angelleye_write_paypal_request_log($paypal_action_name) {
        if ($paypal_action_name == 'SetExpressCheckout') {
            WC_Gateway_PayPal_Express_AngellEYE::log('Redirecting to PayPal');
            WC_Gateway_PayPal_Express_AngellEYE::log(sprintf(__('PayPal for WooCommerce Version: %s', 'express-checkout'), VERSION_PFW));
            WC_Gateway_PayPal_Express_AngellEYE::log(sprintf(__('WooCommerce Version: %s', 'express-checkout'), WC_VERSION));
            WC_Gateway_PayPal_Express_AngellEYE::log('Test Mode: ' . $this->gateway->testmode);
            WC_Gateway_PayPal_Express_AngellEYE::log('Endpoint: ' . $this->gateway->API_Endpoint);
        }
        $PayPalRequest = isset($this->paypal_response['RAWREQUEST']) ? $this->paypal_response['RAWREQUEST'] : '';
        $PayPalResponse = isset($this->paypal_response['RAWRESPONSE']) ? $this->paypal_response['RAWRESPONSE'] : '';
        WC_Gateway_PayPal_Express_AngellEYE::log($paypal_action_name . ' Request: ' . print_r($this->paypal->NVPToArray($this->paypal->MaskAPIResult($PayPalRequest)), true));
        WC_Gateway_PayPal_Express_AngellEYE::log($paypal_action_name . ' Response: ' . print_r($this->paypal->NVPToArray($this->paypal->MaskAPIResult($PayPalResponse)), true));
    }

}
