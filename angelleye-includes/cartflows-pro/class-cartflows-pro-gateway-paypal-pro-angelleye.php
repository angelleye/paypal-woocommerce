<?php

/**
 * PayPal Pro Gateway.
 *
 * @package cartflows
 */

/**
 * Class Cartflows_Pro_Gateway_PayPal_Pro_AngellEYE.
 */
class Cartflows_Pro_Gateway_PayPal_Pro_AngellEYE {

    /**
     * Member Variable
     *
     * @var instance
     */
    private static $instance;

    /**
     * Key name variable
     *
     * @var key
     */
    public $key = 'paypal_pro';
    public $is_api_refund = true;

    /**
     *  Initiator
     */
    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        
    }

    /**
     * Get WooCommerce payment geteways.
     *
     * @return array
     */
    public function get_wc_gateway() {

        global $woocommerce;

        $gateways = $woocommerce->payment_gateways->payment_gateways();

        return $gateways[$this->key];
    }

    /**
     * After payment process.
     *
     * @param array $order order data.
     * @param array $product product data.
     * @return array
     */
    public function process_offer_payment($order, $product) {
        try {
            $gateway = $this->get_wc_gateway();
            $gateway->angelleye_load_paypal_pro_class(null, $gateway, $order);
            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            $description = sprintf(__('%1$s - Order %2$s - One Time offer', 'paypal-for-woocommerce'), wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES), $order->get_order_number());
            $DPFields = array(
                'paymentaction' => 'Sale',
                'ipaddress' => WC_Geolocation::get_ip_address(),
                'returnfmfdetails' => '1',
                'softdescriptor' => $gateway->softdescriptor
            );
            $billing_first_name = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_first_name : $order->get_billing_first_name();
            $billing_last_name = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_last_name : $order->get_billing_last_name();
            $billing_address_1 = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_address_1 : $order->get_billing_address_1();
            $billing_address_2 = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_address_2 : $order->get_billing_address_2();
            $billing_city = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_city : $order->get_billing_city();
            $billing_postcode = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_postcode : $order->get_billing_postcode();
            $billing_country = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_country : $order->get_billing_country();
            $billing_state = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_state : $order->get_billing_state();
            $billing_email = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_email : $order->get_billing_email();
            $billing_phone = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_phone : $order->get_billing_phone();

            $PayerInfo = array(
                'email' => $billing_email,
                'firstname' => $billing_first_name,
                'lastname' => $billing_last_name
            );
            $BillingAddress = array(
                'street' => $billing_address_1,
                'street2' => $billing_address_2,
                'city' => $billing_city,
                'state' => $billing_state,
                'countrycode' => $billing_country,
                'zip' => $billing_postcode,
                'phonenum' => $billing_phone
            );

            $shipping_first_name = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_first_name : $order->get_shipping_first_name();
            $shipping_last_name = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_last_name : $order->get_shipping_last_name();

            $ShippingAddress = array(
                'shiptoname' => $shipping_first_name . ' ' . $shipping_last_name,
                'shiptostreet' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_address_1 : $order->get_shipping_address_1(),
                'shiptostreet2' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_address_2 : $order->get_shipping_address_2(),
                'shiptocity' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_city : $order->get_shipping_city(),
                'shiptostate' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_state : $order->get_shipping_state(),
                'shiptozip' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_postcode : $order->get_shipping_postcode(),
                'shiptocountry' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_country : $order->get_shipping_country(),
                'shiptophonenum' => version_compare(WC_VERSION, '3.0', '<') ? $order->billing_phone : $order->get_billing_phone()
            );

            $customer_note_value = version_compare(WC_VERSION, '3.0', '<') ? wptexturize($order->customer_note) : wptexturize($order->get_customer_note());
            $customer_note = $customer_note_value ? substr(preg_replace("/[^A-Za-z0-9 ]/", "", $customer_note_value), 0, 256) : '';
            $PaymentDetails = array(
                'amt' => AngellEYE_Gateway_Paypal::number_format($product['price'], $order),
                'currencycode' => version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency(),
                'desc' => $description,
                'custom' => apply_filters('ae_ppddp_custom_parameter', $customer_note, $order),
                'invnum' => $gateway->invoice_id_prefix . str_replace("#", "", $order->get_order_number()) . '-' . $product['step_id'],
                'recurring' => ''
            );
            if (isset($gateway->notifyurl) && !empty($gateway->notifyurl)) {
                $PaymentDetails['notifyurl'] = $gateway->notifyurl;
            }

            $OrderItems = array();
            $PayPalRequestData = array(
                'DPFields' => $DPFields,
                'PayerInfo' => $PayerInfo,
                'BillingAddress' => $BillingAddress,
                'ShippingAddress' => $ShippingAddress,
                'PaymentDetails' => $PaymentDetails,
                'OrderItems' => $OrderItems
            );
            $PayPalRequestData['DRTFields'] = array(
                'referenceid' => $order->get_transaction_id(),
                'paymentaction' => !empty($gateway->payment_action) ? $gateway->payment_action : 'Sale',
                'returnfmfdetails' => '1',
                'softdescriptor' => $gateway->softdescriptor
            );
            $PayPalResult = $gateway->PayPal->DoReferenceTransaction($PayPalRequestData);
            AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($PayPalResult, $methos_name = 'DoReferenceTransaction', $method = 'PayPal Website Payments Pro (DoDirectPayment)', $gateway->error_email_notify);
            $PayPalRequest = isset($PayPalResult['RAWREQUEST']) ? $PayPalResult['RAWREQUEST'] : '';
            $PayPalResponse = isset($PayPalResult['RAWRESPONSE']) ? $PayPalResult['RAWRESPONSE'] : '';
            wcf()->logger->log('Request: ' . print_r($gateway->PayPal->NVPToArray($gateway->PayPal->MaskAPIResult($PayPalRequest)), true));
            wcf()->logger->log('Response: ' . print_r($gateway->PayPal->NVPToArray($gateway->PayPal->MaskAPIResult($PayPalResponse)), true));
            if (empty($PayPalResult['RAWRESPONSE'])) {
                return false;
            }
            if ($gateway->PayPal->APICallSuccessful($PayPalResult['ACK'])) {
                $gateway->save_payment_token($order, $PayPalResult['TRANSACTIONID']);
                $this->store_offer_transaction($order, $PayPalResult['TRANSACTIONID'], $product);
                return true;
            } else {
                $error_code = isset($PayPalResult['ERRORS'][0]['L_ERRORCODE']) ? $PayPalResult['ERRORS'][0]['L_ERRORCODE'] : '';
                $long_message = isset($PayPalResult['ERRORS'][0]['L_LONGMESSAGE']) ? $PayPalResult['ERRORS'][0]['L_LONGMESSAGE'] : '';
                $error_message = $error_code . '-' . $long_message;
                if ($gateway->error_email_notify) {
                    $admin_email = get_option("admin_email");
                    $message = __("DoDirectPayment API call failed.", "paypal-for-woocommerce") . "\n\n";
                    $message .= __('Error Code: ', 'paypal-for-woocommerce') . $error_code . "\n";
                    $message .= __('Detailed Error Message: ', 'paypal-for-woocommerce') . $long_message . "\n";
                    $message .= __('User IP: ', 'paypal-for-woocommerce') . WC_Geolocation::get_ip_address() . "\n";
                    $message .= __('Order ID: ') . $order_id . "\n";
                    $message .= __('Customer Name: ') . $billing_first_name . ' ' . $billing_last_name . "\n";
                    $message .= __('Customer Email: ') . $billing_email . "\n";
                    $pc_error_email_message = apply_filters('ae_ppddp_error_email_message', $message, $error_code, $long_message);
                    $pc_error_email_subject = apply_filters('ae_ppddp_error_email_subject', "PayPal Pro Error Notification", $error_code, $long_message);
                    wp_mail($admin_email, $pc_error_email_subject, $pc_error_email_message);
                }
                wcf()->logger->log('Error ' . print_r($PayPalResult['ERRORS'], true));
                return false;
            }
        } catch (Exception $ex) {
            return false;
        }
    }

    public function is_api_refund() {
        return $this->is_api_refund;
    }

    public function process_offer_refund($order, $offer_data) {
        $order_id = $offer_data['order_id'];
        $transaction_id = $offer_data['transaction_id'];
        $refund_amount = $offer_data['refund_amount'];
        $reason = $offer_data['refund_reason'];
        $response_id = false;
        wcf()->logger->log('Begin Refund');
        wcf()->logger->log('Order ID: ' . print_r($order_id, true));
        wcf()->logger->log('Transaction ID: ' . print_r($order->get_transaction_id(), true));
        $gateway = $this->get_wc_gateway();
        $gateway->angelleye_load_paypal_pro_class(null, $gateway, $order);
        if ($reason) {
            if (255 < strlen($reason)) {
                $reason = substr($reason, 0, 252) . '...';
            }
            $reason = html_entity_decode($reason, ENT_NOQUOTES, 'UTF-8');
        }
        $RTFields = array(
            'transactionid' => $transaction_id,
            'payerid' => '',
            'invoiceid' => '',
            'refundtype' => $order->get_total() == $refund_amount ? 'Full' : 'Partial',
            'amt' => AngellEYE_Gateway_Paypal::number_format($refund_amount, $order),
            'currencycode' => version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency(),
            'note' => $reason,
            'retryuntil' => '',
            'refundsource' => '',
            'merchantstoredetail' => '',
            'refundadvice' => '',
            'refunditemdetails' => '',
            'msgsubid' => '',
            'storeid' => '',
            'terminalid' => ''
        );
        $PayPalRequestData = array('RTFields' => $RTFields);
        $PayPalResult = $gateway->PayPal->RefundTransaction(apply_filters('angelleye_woocommerce_paypal_pro_refund_request_args', $PayPalRequestData));
        AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($PayPalResult, 'RefundTransaction', 'PayPal Website Payments Pro (DoDirectPayment)', $this->error_email_notify);
        $PayPalRequest = isset($PayPalResult['RAWREQUEST']) ? $PayPalResult['RAWREQUEST'] : '';
        $PayPalResponse = isset($PayPalResult['RAWRESPONSE']) ? $PayPalResult['RAWRESPONSE'] : '';
        wcf()->logger->log('Refund Request: ' . print_r($gateway->PayPal->NVPToArray($gateway->PayPal->MaskAPIResult($PayPalRequest)), true));
        wcf()->logger->log('Refund Response: ' . print_r($gateway->PayPal->NVPToArray($gateway->PayPal->MaskAPIResult($PayPalResponse)), true));
        if ($gateway->PayPal->APICallSuccessful($PayPalResult['ACK'])) {
            update_post_meta($order_id, 'Refund Transaction ID', $PayPalResult['REFUNDTRANSACTIONID']);
            $order->add_order_note('Refund Transaction ID:' . $PayPalResult['REFUNDTRANSACTIONID']);
            $response_id = $PayPalResult['REFUNDTRANSACTIONID'];
        } else {
            $response_id = false;
        }
        return $response_id;
    }

    public function store_offer_transaction($order, $response, $product) {
        $order->update_meta_data('cartflows_offer_txn_resp_' . $product['step_id'], $response);
        $order->save();
    }

}

/**
 *  Prepare if class 'Cartflows_Pro_Gateway_PayPal_Pro_AngellEYE' exist.
 *  Kicking this off by calling 'get_instance()' method
 */
Cartflows_Pro_Gateway_PayPal_Pro_AngellEYE::get_instance();
