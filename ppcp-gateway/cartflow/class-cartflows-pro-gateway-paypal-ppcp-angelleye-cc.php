<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class Cartflows_Pro_Gateway_PayPal_PPCP_AngellEYE.
 */
class Cartflows_Pro_Gateway_PayPal_PPCP_CC_AngellEYE extends Cartflows_Pro_Paypal_Gateway_helper {

    private static $instance;
    public $key = 'angelleye_ppcp_cc';
    public $is_api_refund = true;
    public $is_sandbox;
    public $paymentaction;
    public $setting_obj;

    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->angelleye_ppcp_load_class();
        $this->is_sandbox = 'yes' === $this->setting_obj->get('testmode', 'no');
        $this->paymentaction = $this->setting_obj->get('paymentaction', 'capture');
        if ($this->is_sandbox) {
            $this->merchant_id = $this->setting_obj->get('sandbox_merchant_id', '');
        } else {
            $this->merchant_id = $this->setting_obj->get('live_merchant_id', '');
        }
        $this->invoice_prefix = $this->setting_obj->get('invoice_prefix', 'WC-PPCP');
        $this->soft_descriptor = $this->setting_obj->get('soft_descriptor', substr(get_bloginfo('name'), 0, 21));
        add_filter('cartflows_offer_supported_payment_gateway_slugs', array($this, 'angelleye_ppcp_cartflows_offer_supported_payment_gateway_slugs'));
        add_filter('cartflows_offer_js_localize', array($this, 'angelleye_ppcp_cartflows_offer_js_localize'));
        add_action('wp_enqueue_scripts', array($this, 'angelleye_ppcp_frontend_scripts'));
        add_filter('woocommerce_paypal_refund_request', array($this, 'angelleye_ppcp_offer_refund_request_data'), 10, 4);
        //add_action('cartflows_offer_subscription_created', array($this, 'add_subscription_payment_meta_for_paypal'), 10, 3);
        add_action('cartflows_offer_child_order_created_' . $this->key, array($this, 'angelleye_ppcp_store_required_meta_keys_for_refund'), 10, 3);
        add_action('wp_ajax_wcf_create_paypal_ppcp_angelleye_payments_order', array($this, 'angelleye_ppcp_create_paypal_order'));
        add_action('wp_ajax_nopriv_wcf_create_paypal_ppcp_angelleye_payments_order', array($this, 'angelleye_ppcp_create_paypal_order'));
        add_action('wp_ajax_wcf_capture_paypal_ppcp_angelleye_order', array($this, 'angelleye_ppcp_capture_paypal_order'));
        add_action('wp_ajax_nopriv_wcf_capture_paypal_ppcp_angelleye_order', array($this, 'angelleye_ppcp_capture_paypal_order'));
    }

    public function angelleye_ppcp_frontend_scripts() {
        wp_enqueue_script('angelleye-paypal-ppcp-cartflow', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/cartflow/js/cartflow-frontend.js', array('jquery'), VERSION_PFW, true);
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
            $this->setting_obj = WC_Gateway_PPCP_AngellEYE_Settings::instance();
            $this->api_request = AngellEYE_PayPal_PPCP_Request::instance();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function get_ppcp_meta() {
        $this->paymentaction = apply_filters('angelleye_ppcp_paymentaction', $this->paymentaction, null);
        return array(
            'environment' => ($this->is_sandbox) ? 'sandbox' : '',
            'intent' => ($this->paymentaction === 'capture') ? 'CAPTURE' : 'AUTHORIZE',
            'merchant_id' => $this->merchant_id,
            'invoice_prefix' => $this->invoice_prefix,
        );
    }

    public function process_offer_payment($order, $product) {
        $is_successful = false;
        $txn_id = '';
        $txn_id = $order->get_meta('cartflows_offer_paypal_txn_id_' . $order->get_id());
        if (empty($txn_id)) {
            wcf()->logger->log('PayPal order captured but no txn ID found, so order is failed.');
            $is_successful = false;
        } else {
            $is_successful = true;
            $response = array(
                'id' => $txn_id,
            );
            $this->store_offer_transaction($order, $response, $product);
        }
        return $is_successful;
    }

    public function angelleye_ppcp_create_paypal_order() {
        
        $data = array();
        $step_id = isset($_POST['step_id']) ? intval($_POST['step_id']) : 0;
        $flow_id = isset($_POST['flow_id']) ? intval($_POST['flow_id']) : 0;
        $order_id = isset($_POST['order_id']) ? sanitize_text_field(wp_unslash($_POST['order_id'])) : 0;
        if (angelleye_ppcp_get_order_total($order_id) === 0) {
            $wc_notice = __( 'Sorry, your session has expired.', 'woocommerce' );
            wc_add_notice($wc_notice);
            wp_send_json_error($wc_notice);
            exit();
        }
        $order_key = isset($_POST['order_key']) ? sanitize_text_field(wp_unslash($_POST['order_key'])) : '';
        $session_key = isset($_COOKIE[CARTFLOWS_SESSION_COOKIE . $flow_id]) ? sanitize_text_field(wp_unslash($_COOKIE[CARTFLOWS_SESSION_COOKIE . $flow_id])) : '';
        $order = wc_get_order($order_id);
        $variation_id = '';
        $input_qty = '';
        $invoice_id = '';
        $args = array(
            'step_id' => $step_id,
            'flow_id' => $flow_id,
            'order_id' => $order_id,
            'order_key' => $order_key,
            'order_currency' => wcf_pro()->wc_common->get_currency($order),
            'ppcp_data' => $this->get_ppcp_meta(),
        );
        if (isset($_POST['variation_id']) && !empty($_POST['variation_id'])) {
            $variation_id = intval($_POST['variation_id']);
        }
        if (isset($_POST['input_qty']) && !empty($_POST['input_qty'])) {
            $input_qty = intval($_POST['input_qty']);
        }
        $offer_product = wcf_pro()->utils->get_offer_data($step_id, $variation_id, $input_qty, $order_id);
        if (isset($offer_product['price']) && ( floatval(0) === floatval($offer_product['price']) || '' === trim($offer_product['price']) )) {
            wcf()->logger->log(
                    "Cannot create PayPal Payments Order. The selected product's price is zero. Order: {$order_id}"
            );
            wp_send_json(
                    array(
                        'result' => 'fail',
                        'message' => __('Cannot make the Payment for Zero value product', 'paypal-for-woocommerce'),
                    )
            );
        } else {
            $data = array(
                'intent' => $args['ppcp_data']['intent'],
                'purchase_units' => $this->get_purchase_units($order, $offer_product, $args),
                'application_context' => array(
                    'user_action' => 'CONTINUE',
                    'landing_page' => 'LOGIN',
                    'brand_name' => html_entity_decode(get_bloginfo('name'), ENT_NOQUOTES, 'UTF-8'),
                    'return_url' => $this->get_return_or_cancel_url($args, $session_key),
                    'cancel_url' => $this->get_return_or_cancel_url($args, $session_key, true),
                ),
                'payment_method' => array(
                    'payee_preferred' => 'UNRESTRICTED',
                    'payer_selected' => 'PAYPAL',
                )
            );
            $arguments = array(
                'method' => 'POST',
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => ''
                ),
                'body' => $data,
            );
            $url = 'https://api-m.' . $args['ppcp_data']['environment'] . '.paypal.com/v2/checkout/orders';
            $response = $this->api_request->request($url, $arguments, 'create_order');
            if (ob_get_length()) {
                ob_end_clean();
            }
            if (is_wp_error($response)) {
                $json_response = array(
                    'status' => false,
                    'message' => $response->get_error_message(),
                    'paypal_order_id' => '',
                    'redirect_url' => '',
                    'response' => $response,
                );
                wcf()->logger->log(
                        "PayPal order is not created. Order: {$order_id}, Error: " .
                        wp_json_encode($response->get_error_message())
                );
                return wp_send_json($json_response);
            } else {
                $json_response = array(
                    'result' => false,
                    'message' => __('PayPal order is not created', 'paypal-for-woocommerce'),
                    'paypal_order_id' => '',
                    'redirect_url' => '',
                    'response' => $response,
                );
                if (isset($response['status']) && 'CREATED' === $response['status']) {
                    $approve_link = $response['links'][1]['href'];
                    $order->update_meta_data('cartflows_paypal_order_id_' . $order->get_id(), $response['id']);
                    $order->save();
                    wcf()->logger->log(
                            "Order Created for WC-Order: {$order_id}"
                    );
                    $json_response = array(
                        'status' => 'success',
                        'message' => __('Order created successfully', 'paypal-for-woocommerce'),
                        'paypal_order_id' => $response['id'],
                        'redirect' => $approve_link,
                        'response' => $response,
                    );
                }
            }
            return wp_send_json($json_response);
        }
    }

    public function angelleye_ppcp_capture_paypal_order() {
        $order_id = isset($_POST['order_id']) ? sanitize_text_field(wp_unslash($_POST['order_id'])) : 0;
        $order = wc_get_order($order_id);
        $paypal_order_id = $order->get_meta('cartflows_paypal_order_id_' . $order->get_id());
        $environment = ($this->is_sandbox) ? 'sandbox' : '';
        $capture_args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => '',
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation'
            ),
        );
        $capture_url = 'https://api-m.' . $environment . '.paypal.com/v2/checkout/orders/' . $paypal_order_id . '/capture';
        $resp_body = $this->api_request->request($capture_url, $capture_args, 'capture_order');
        if (is_wp_error($resp_body)) {
            $json_response = array(
                'status' => false,
                'message' => $resp_body->get_error_message(),
                'paypal_order_id' => '',
                'redirect_url' => '',
                'response' => $resp_body,
            );
            wcf()->logger->log(
                    "Order Created but not captured. For WC-Order: {$order_id}, Error: " .
                    wp_json_encode($resp_body->get_error_message())
            );
        } else {
            $json_response = array(
                'result' => false,
                'message' => __('PayPal order is not created', 'paypal-for-woocommerce'),
                'paypal_order_id' => '',
                'redirect_url' => '',
                'response' => $resp_body,
            );
            if (isset($resp_body['status']) && 'COMPLETED' === $resp_body['status']) {
                $txn_id = $resp_body['purchase_units']['0']['payments']['captures']['0']['id'];
                $order->update_meta_data('cartflows_offer_paypal_txn_id_' . $order->get_id(), $txn_id);
                $order->save();
                wcf()->logger->log(
                        "Order Created and captured. Order: {$order_id}"
                );
                $json_response = array(
                    'status' => 'success',
                    'message' => __('Order Captured successfully', 'paypal-for-woocommerce'),
                    'paypal_order_id' => $resp_body['id'],
                    'response' => $resp_body,
                );
            }
        }
        return wp_send_json($json_response);
    }

    public function get_purchase_units($order, $offer_product, $args) {
        $invoice_id = $args['ppcp_data']['invoice_prefix'] . '-wcf-' . $args['order_id'] . '_' . $args['step_id'];
        $purchase_unit = array(
            'reference_id' => 'default',
            'amount' => array(
                'currency_code' => $args['order_currency'],
                'value' => $offer_product['price'],
                'breakdown' => $this->get_item_breakdown($order, $offer_product),
            ),
            'description' => __('One Time Offer - ' . $order->get_id(), 'paypal-for-woocommerce'), // phpcs:ignore
            'items' => array(
                $this->add_offer_item_data($order, $offer_product),
            ),
            'payee' => array(
                'merchant_id' => $args['ppcp_data']['merchant_id']
            ),
            'shipping' => array(
                'name' => array(
                    'full_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                ),
            ),
            'custom_id' => apply_filters('angelleye_ppcp_custom_id', $invoice_id, $order),
            'invoice_id' => $invoice_id,
            'soft_descriptor' => angelleye_ppcp_get_value('soft_descriptor', $this->soft_descriptor)
        );
        return array($purchase_unit);
    }

    public function get_item_breakdown($order, $offer_product) {
        $breakdown = array();
        $breakdown['item_total'] = array(
            'currency_code' => wcf_pro()->wc_common->get_currency($order),
            'value' => $offer_product['unit_price_tax'],
        );
        if (!empty($offer_product['shipping_fee'])) {
            $breakdown['shipping'] = array(
                'currency_code' => wcf_pro()->wc_common->get_currency($order),
                'value' => $offer_product['shipping_fee_tax'],
            );
        }
        return $breakdown;
    }

    public function add_offer_item_data($order, $offer_product) {
        $description = wp_strip_all_tags($offer_product['desc']);
        if (strlen($description) > 127) {
            $description = substr($description, 0, 124) . '...';
        }
        $offer_items = array(
            'name' => $offer_product['name'],
            'unit_amount' => array(
                'currency_code' => wcf_pro()->wc_common->get_currency($order),
                'value' => $offer_product['unit_price_tax'],
            ),
            'quantity' => $offer_product['qty'],
            'description' => $description,
        );
        return $offer_items;
    }

    public function get_return_or_cancel_url($args, $session_key, $cancel = false) {
        $url = get_permalink($args['step_id']);
        $args = array(
            'wcf-order' => $args['order_id'],
            'wcf-key' => $args['order_key'],
            'wcf-sk' => $session_key,
        );
        if ($cancel) {
            $args['wcf-ppcp-angelleye-cancel'] = true;
        } else {
            $args['wcf-ppcp-angelleye-return'] = true;
        }
        return add_query_arg($args, $url);
    }

    public function is_api_refund() {
        return $this->is_api_refund;
    }

    public function store_offer_transaction($order, $response, $product) {
        wcf()->logger->log('PayPal Payments : Store Offer Transaction :: Transaction ID = ' . $response['id'] . ' Captured');
        $order->update_meta_data('cartflows_offer_txn_resp_' . $product['step_id'], $response['id']);
        $order->save();
    }

    public function angelleye_ppcp_offer_refund_request_data($request, $order, $amount, $reason) {
        if (isset($_POST['cartflows_refund'])) {
            $payment_method = $order->get_payment_method();
            if ($this->key === $payment_method) {
                if (isset($_POST['transaction_id']) && !empty($_POST['transaction_id'])) {
                    $request['TRANSACTIONID'] = sanitize_text_field(wp_unslash($_POST['transaction_id']));
                }
            }
        }
        return $request;
    }

    public function process_offer_refund($order, $offer_data) {
        $order_id = $offer_data['order_id'];
        $transaction_id = $offer_data['transaction_id'];
        $refund_amount = $offer_data['refund_amount'];
        $refund_reason = $offer_data['refund_reason'];
        $response = false;
        $gateway = $this->get_wc_gateway();
        if ($this->is_api_refund) {
            $result = $gateway->process_refund($order->get_id(), $refund_amount, $refund_reason);
            if (is_wp_error($result)) {
                wcf()->logger->log("PayPal offer refund failed. Order: {$order_id}, Error: " . print_r($result->get_error_message(), true)); // phpcs:ignore
            } elseif ($result) {
                $response = $result;
            }
        }
        return $response;
    }

    public function get_wc_gateway() {
        global $woocommerce;
        $gateways = $woocommerce->payment_gateways->payment_gateways();
        return $gateways[$this->key];
    }

    public function add_subscription_payment_meta_for_paypal($subscription, $order, $offer_product) {
        if ('angelleye_ppcp_cc' === $order->get_payment_method()) {
            $subscription_id = $subscription->get_id();
            update_post_meta($subscription_id, '_ppcp_paypal_order_id', $order->get_meta('_ppcp_paypal_order_id', true));
            update_post_meta($subscription_id, 'payment_token_id', $order->get_meta('payment_token_id', true));
        }
    }

    public function angelleye_ppcp_store_required_meta_keys_for_refund($parent_order, $child_order, $transaction_id) {
        if (!empty($transaction_id)) {
            $paypal_order_id = $parent_order->get_meta('cartflows_paypal_order_id_' . $parent_order->get_id());
            $child_order->update_meta_data('_ppcp_paypal_order_id', $paypal_order_id);
            $child_order->update_meta_data('_ppcp_paypal_intent', 'CAPTURE');
            $child_order->save();
        }
    }

    public function angelleye_ppcp_cartflows_offer_supported_payment_gateway_slugs($gateways) {
        $gateways[] = 'angelleye_ppcp_cc';
        return $gateways;
    }

    public function angelleye_ppcp_cartflows_offer_js_localize($localize) {
        if (!empty($localize) && ($localize['payment_method'] === 'angelleye_ppcp' || $localize['payment_method'] === 'angelleye_ppcp_cc')) {
            $localize['skip_offer'] = 'yes';
        }
        return $localize;
    }

}

Cartflows_Pro_Gateway_PayPal_PPCP_AngellEYE::get_instance();
