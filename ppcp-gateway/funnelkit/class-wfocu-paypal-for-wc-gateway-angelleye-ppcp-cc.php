<?php

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists("WFOCU_Paypal_For_WC_Gateway_AngellEYE_PPCP_CC") || !class_exists("WFOCU_Gateway")) {
    return;
}

class WFOCU_Paypal_For_WC_Gateway_AngellEYE_PPCP_CC extends WFOCU_Gateway {

    public $key = 'angelleye_ppcp_cc';
    public $is_sandbox;
    public $setting_obj;
    public $api_request;
    public $token = false;
    public $paymentaction;
    protected static $ins = null;
    public $partner_client_id = null;
    public $payal_order_id;
    public $paypal_order_id = null;
    public $enable_tokenized_payments;

    public function __construct() {
        parent::__construct();
        $this->refund_supported = true;
        $this->angelleye_ppcp_load_class();
        $this->is_sandbox = 'yes' === $this->setting_obj->get('testmode', 'no');
        $this->paymentaction = $this->setting_obj->get('paymentaction', 'capture');
        if ($this->is_sandbox) {
            $this->merchant_id = $this->setting_obj->get('sandbox_merchant_id', '');
        } else {
            $this->merchant_id = $this->setting_obj->get('live_merchant_id', '');
        }
        $this->invoice_prefix = $this->setting_obj->get('invoice_prefix', 'WC-PPCP');
        $this->enable_tokenized_payments = 'yes' === $this->setting_obj->get('enable_tokenized_payments', 'no');
        $this->landing_page = $this->setting_obj->get('landing_page', 'NO_PREFERENCE');
        $this->payee_preferred = 'yes' === $this->setting_obj->get('payee_preferred', 'no');
        if ($this->enable_tokenized_payments === false) {
            add_action('wfocu_footer_before_print_scripts', array($this, 'maybe_render_in_offer_transaction_scripts'), 999);
            add_filter('wfocu_allow_ajax_actions_for_charge_setup', array($this, 'allow_action'));
        } else {
            add_filter("angelleye_ppcp_is_save_payment_method", array($this, "angelleye_force_token_save"), 20);
        }
        add_action('wc_ajax_wfocu_front_handle_angelleye_ppcp_payments', array($this, 'process_client_order'));
        add_action('woocommerce_api_wfocu_angelleye_ppcp_payments', array($this, 'handle_api_calls'));
    }

    public static function get_instance() {
        try {
            if (null == self::$ins) {
                self::$ins = new self;
            }
            return self::$ins;
        } catch (Exception $ex) {
            
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
            if (!class_exists('AngellEYE_PayPal_PPCP_Payment')) {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-payment.php');
            }

            $this->api_log = AngellEYE_PayPal_PPCP_Log::instance();
            $this->api_request = AngellEYE_PayPal_PPCP_Request::instance();
            $this->setting_obj = WC_Gateway_PPCP_AngellEYE_Settings::instance();
            $this->payment_request = AngellEYE_PayPal_PPCP_Payment::instance();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function is_run_without_token() {
        try {
            return true;
        } catch (Exception $ex) {
            
        }
    }

    public function has_token($order) {
        try {
            if (!is_a($order, 'WC_Order')) {
                return false;
            }
            $this->token = angelleye_ppcp_get_post_meta($order, '_payment_tokens_id', true);
            if (!empty($this->token)) {
                return true;
            }
            return false;
        } catch (Exception $ex) {
            
        }
    }

    public function handle_api_calls() {
        try {
            add_filter('wfocu_valid_state_for_data_setup', '__return_true');
            WFOCU_Core()->template_loader->set_offer_id(WFOCU_Core()->data->get_current_offer());
            WFOCU_Core()->template_loader->maybe_setup_offer();
            $get_order = WFOCU_Core()->data->get_parent_order();
            $paypal_order_id = $get_order->get_meta('wfocu_ppcp_order_current');
            $environment = $get_order->get_meta('_enviorment');
            $capture_args = array(
                'method' => 'POST',
                'timeout' => 30,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => '',
                    "prefer" => "return=representation",
                    'PayPal-Request-Id' => $this->generate_request_id(),
                    'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion($environment),
                ),
            );
            $capture_url = $this->get_api_base($environment) . 'v2/checkout/orders/' . $paypal_order_id . '/capture';
            $resp_body = $this->api_request->request($capture_url, $capture_args, 'capture_order');
            $existing_package = WFOCU_Core()->data->get('upsell_package', '', 'paypal');
            WFOCU_Core()->data->set('_upsell_package', $existing_package);
            if (isset($resp_body['status']) && 'failed' === $resp_body['status']) {
                $data = WFOCU_Core()->process_offer->_handle_upsell_charge(false);
                WFOCU_Core()->log->log('Order #' . WFOCU_WC_Compatibility::get_order_id($get_order) . ': Unable to capture paypal Order refer error below' . print_r($resp_body, true));
            } else {
                $resp_body = json_decode($resp_body($resp_body), FALSE);
                if (isset($resp_body->status) && 'COMPLETED' === $resp_body->status) {
                    if (isset($resp_body->payment_source->paypal->attributes->vault->id) && isset($resp_body->payment_source->paypal->attributes->vault->status) && 'CREATED' === $resp_body->payment_source->paypal->attributes->vault->status) {
                        $txn_id = $resp_body->payment_source->paypal->attributes->vault->id;
                        $get_order->update_meta_data('wfocu_ppcp_renewal_payment_token', $txn_id);
                        $get_order->save();
                        WFOCU_Core()->log->log('Order #' . WFOCU_WC_Compatibility::get_order_id($get_order) . ': vault token created');
                    } else {
                        $txn_id = $resp_body->purchase_units[0]->payments->captures[0]->id;
                    }
                    WFOCU_Core()->data->set('_transaction_id', $txn_id);
                    add_action('wfocu_db_event_row_created_' . WFOCU_DB_Track::OFFER_ACCEPTED_ACTION_ID, array($this, 'add_order_id_as_meta'));
                    add_action('wfocu_offer_new_order_created_' . $this->get_key(), array($this, 'add_paypal_meta_in_new_order'), 10, 2);
                    $this->payal_order_id = $paypal_order_id;
                    $data = WFOCU_Core()->process_offer->_handle_upsell_charge(true);
                } elseif (isset($resp_body->details) && is_array($resp_body->details) && ( 'ORDER_ALREADY_CAPTURED' === $resp_body->details[0]->issue )) {
                    $get_offer = WFOCU_Core()->offers->get_the_next_offer();
                    $data = [];
                    $data['redirect_url'] = WFOCU_Core()->public->get_the_upsell_url($get_offer);
                } else {
                    $data = WFOCU_Core()->process_offer->_handle_upsell_charge(false);
                    WFOCU_Core()->log->log('Order #' . WFOCU_WC_Compatibility::get_order_id($get_order) . ': Unable to capture paypal Order refer error below' . print_r($resp_body, true));
                }
            }
            wp_redirect($data['redirect_url']);
            exit;
        } catch (Exception $ex) {
            
        }
    }

    public function process_client_order() {
        try {
            $get_current_offer = WFOCU_Core()->data->get('current_offer');
            $get_current_offer_meta = WFOCU_Core()->offers->get_offer_meta($get_current_offer);
            WFOCU_Core()->data->set('_offer_result', true);
            $posted_data = WFOCU_Core()->process_offer->parse_posted_data($_POST);
            if (true === WFOCU_AJAX_Controller::validate_charge_request($posted_data)) {
                WFOCU_Core()->process_offer->execute($get_current_offer_meta);
                $get_order = WFOCU_Core()->data->get_parent_order();
                $offer_package = WFOCU_Core()->data->get('_upsell_package');
                WFOCU_Core()->data->set('upsell_package', $offer_package, 'paypal');
                WFOCU_Core()->data->save('paypal');
                WFOCU_Core()->data->save();
                $ppcp_data = $this->get_ppcp_meta();
                $data = array(
                    'intent' => 'CAPTURE',
                    'application_context' => array(
                        'user_action' => 'PAY_NOW',
                        'landing_page' => $this->landing_page,
                        'brand_name' => html_entity_decode(get_bloginfo('name'), ENT_NOQUOTES, 'UTF-8'),
                        'return_url' => add_query_arg(array('wfocu-si' => WFOCU_Core()->data->get_transient_key()), WC()->api_request_url('wfocu_angelleye_ppcp_payments')),
                        'cancel_url' => add_query_arg(array('wfocu-si' => WFOCU_Core()->data->get_transient_key()), WFOCU_Core()->public->get_the_upsell_url(WFOCU_Core()->data->get_current_offer())),
                    ),
                    'payment_method' => array(
                        'payee_preferred' => $this->payee_preferred
                    ),
                    'purchase_units' => $this->get_purchase_units($get_order, $offer_package, $ppcp_data),
                    'payment_instruction' => array(
                        'disbursement_mode' => 'INSTANT',
                    ),
                );
                WFOCU_Core()->log->log("Order: #" . $get_order->get_id() . " paypal args" . print_r($data, true));
                $environment = $get_order->get_meta('_enviorment');
                $arguments = apply_filters('wfocu_ppcp_gateway_process_client_order_api_args', array(
                    'method' => 'POST',
                    'headers' => array(
                        'Content-Type' => 'application/json',
                        'Authorization' => '',
                        "prefer" => "return=representation",
                        'PayPal-Request-Id' => $this->generate_request_id(),
                        'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion($environment),
                    ),
                    'body' => $data
                        ), $get_order, $posted_data, $offer_package);

                $url = $this->get_api_base($environment) . 'v2/checkout/orders';
                $response = $this->api_request->request($url, $arguments, 'create_order');
                if (isset($response['status']) && 'failed' === $response['status']) {
                    $data = WFOCU_Core()->process_offer->_handle_upsell_charge(false);
                    $json_response = array(
                        'status' => false,
                        'redirect_url' => $data['redirect_url'],
                    );
                    WFOCU_Core()->log->log('Order #' . WFOCU_WC_Compatibility::get_order_id($get_order) . ': Unable to create paypal Order refer error below' . print_r($response, true));
                    wp_send_json($json_response);
                } else {
                    $response = json_decode(json_encode($response), FALSE);
                    if ('CREATED' === $response->status || 'PAYER_ACTION_REQUIRED' === $response->status) {
                        $approve_link = $response->links[1]->href;
                        $get_order->update_meta_data('wfocu_ppcp_order_current', $response->id);
                        $get_order->save();
                        WFOCU_Core()->log->log('Order #' . WFOCU_WC_Compatibility::get_order_id($get_order) . ': PayPal Order successfully created');  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
                        $json_response = array(
                            'status' => true,
                            'redirect_url' => $approve_link,
                        );
                    } else {
                        $data = WFOCU_Core()->process_offer->_handle_upsell_charge(false);
                        $json_response = array(
                            'status' => false,
                            'redirect_url' => $data['redirect_url'],
                        );
                        WFOCU_Core()->log->log('Order #' . WFOCU_WC_Compatibility::get_order_id($get_order) . ': Unable to create paypal Order refer error below' . print_r($response, true));  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
                        wp_send_json($json_response);
                    }
                }
                wp_send_json($json_response);
            }
        } catch (Exception $ex) {
            
        }
    }

    public function maybe_render_in_offer_transaction_scripts() {
        try {
            $order = WFOCU_Core()->data->get_current_order();
            if (!$order instanceof WC_Order) {
                return;
            }
            if ($this->get_key() !== $order->get_payment_method()) {
                return;
            }
            if (!$this->is_enabled()) {
                return;
            }
            ?>
            <script>
                (function ($) {
                    "use strict";
                    $(document).on('wfocu_external', function (e, Bucket) {
                        if (0 !== Bucket.getTotal()) {
                            Bucket.inOfferTransaction = true;
                            var getBucketData = Bucket.getBucketSendData();
                            var postData = $.extend(getBucketData, {action: 'wfocu_front_handle_angelleye_ppcp_payments'});
                            if (typeof wfocu_vars.wc_ajax_url !== "undefined") {
                                var action = $.post(wfocu_vars.wc_ajax_url.toString().replace('%%endpoint%%', 'wfocu_front_handle_angelleye_ppcp_payments'), postData);
                            } else {
                                var action = $.post(wfocu_vars.ajax_url, postData);
                            }
                            action.done(function (data) {
                                if (data.status === true) {
                                    window.location = data.redirect_url;
                                } else {
                                    Bucket.swal.show({'text': wfocu_vars.messages.offer_msg_pop_failure, 'type': 'warning'});
                                    window.location = wfocu_vars.redirect_url;
                                }

                            });
                            action.fail(function () {
                                Bucket.swal.show({'text': wfocu_vars.messages.offer_msg_pop_failure, 'type': 'warning'});
                                if (typeof wfocu_vars.order_received_url !== 'undefined') {
                                    window.location = wfocu_vars.order_received_url + '&ec=' + jqXHR.status;
                                }
                            });
                        }
                    });
                })
                        (jQuery);
            </script> <?php

        } catch (Exception $ex) {
            
        }
    }

    public function allow_action($actions) {
        try {
            array_push($actions, 'wfocu_front_handle_angelleye_ppcp_payments');
            return $actions;
        } catch (Exception $ex) {
            
        }
    }

    public function get_token($order) {
        try {
            $this->token = angelleye_ppcp_get_post_meta($order, '_payment_tokens_id', true);
            if (empty($this->token)) {
                $payment_tokens_list = $this->payment_request->angelleye_ppcp_get_all_payment_tokens();
                $payment_method = $order->get_payment_method();
                if ($payment_method == 'angelleye_ppcp') {
                    foreach ($payment_tokens_list as $key => $token) {
                        if (isset($token['payment_source']['paypal']) && !empty($token['payment_source']['paypal'])) {
                            $this->token = $token['id'];
                            break;
                        }
                    }
                }
            }
            if (!empty($this->token)) {
                return $this->token;
            }
            return false;
        } catch (Exception $ex) {
            
        }
    }

    public function process_charge($order) {
        try {
            if ($this->enable_tokenized_payments) {
                WFOCU_Core()->log->log('process charge paypal advanced credit card');
                $is_successful = false;
                $get_current_offer = WFOCU_Core()->data->get('current_offer');
                $get_current_offer_meta = WFOCU_Core()->offers->get_offer_meta($get_current_offer);
                WFOCU_Core()->data->set('_offer_result', true);
                $posted_data = WFOCU_Core()->process_offer->parse_posted_data($_POST);
                $ppcp_data = $this->get_ppcp_meta();
                if (true === WFOCU_AJAX_Controller::validate_charge_request($posted_data)) {
                    WFOCU_Core()->process_offer->execute($get_current_offer_meta);
                    $get_order = WFOCU_Core()->data->get_parent_order();
                    $offer_package = WFOCU_Core()->data->get('_upsell_package');
                    WFOCU_Core()->data->set('upsell_package', $offer_package, 'paypal');
                    WFOCU_Core()->data->save('paypal');
                    WFOCU_Core()->data->save();
                    $data = array();
                    $data['timeout'] = 30;
                    $data['intent'] = $ppcp_data['intent'];
                    $data['purchase_units'] = $this->get_purchase_units($get_order, $offer_package, $ppcp_data);
                    $data['application_context'] = array(
                        'user_action' => 'CONTINUE',
                        'landing_page' => $this->landing_page,
                        'brand_name' => html_entity_decode(get_bloginfo('name'), ENT_NOQUOTES, 'UTF-8'),
                        'return_url' => add_query_arg(array('wfocu-si' => WFOCU_Core()->data->get_transient_key()), WC()->api_request_url('wfocu_angelleye_angelleye_ppcp_payments')),
                        'cancel_url' => add_query_arg(array('wfocu-si' => WFOCU_Core()->data->get_transient_key()), WFOCU_Core()->public->get_the_upsell_url(WFOCU_Core()->data->get_current_offer())),
                    );
                    $data['payment_instruction'] = array(
                        'disbursement_mode' => 'INSTANT',
                    );
                    $data['payment_method'] = array(
                        'payee_preferred' => $this->payee_preferred
                    );
                    $payment_token = $this->get_token($get_order);
                    $token_id = angelleye_ppcp_get_token_id_by_token($payment_token);
                    $data_store = WC_Data_Store::load('payment-token');
                    $token_metadata = $data_store->get_metadata($token_id);
                    if ($token_metadata) {
                        $data['payment_source'] = array(
                            $token_metadata['_angelleye_ppcp_used_payment_method'][0] => array(
                                'vault_id' => $payment_token,
                            )
                        );
                    } else {
                        $data = apply_filters('angelleye_ppcp_add_payment_source', $data, $get_order->get_id());
                    }
                    if ($token_metadata['_angelleye_ppcp_used_payment_method'][0] === 'card') {
                        $data['payment_source'][$token_metadata['_angelleye_ppcp_used_payment_method'][0]]['stored_credential'] = array(
                            'payment_initiator' => 'MERCHANT',
                            'payment_type' => 'UNSCHEDULED',
                            'usage' => 'SUBSEQUENT'
                        );
                    }
                    WFOCU_Core()->log->log("Order: #" . $get_order->get_id() . " paypal args" . print_r($data, true));
                    $environment = $get_order->get_meta('_enviorment');
                    $arguments = apply_filters('wfocu_ppcp_gateway_process_client_order_api_args', array(
                        'method' => 'POST',
                        'headers' => array(
                            'Content-Type' => 'application/json',
                            'Authorization' => '',
                            "prefer" => "return=representation",
                            'PayPal-Request-Id' => $this->generate_request_id(),
                            'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion($environment),
                        ),
                        'body' => $data,
                            ), $get_order, $posted_data, $offer_package);

                    $url = $this->get_api_base($environment) . 'v2/checkout/orders';
                    $ppcp_resp = $this->api_request->request($url, $arguments, 'create_order');
                    WFOCU_Core()->log->log("Order: #" . $get_order->get_id() . " paypal response" . print_r($ppcp_resp, true));
                    if (!isset($ppcp_resp['id']) || empty($ppcp_resp['id'])) {
                        $data = WFOCU_Core()->process_offer->_handle_upsell_charge(false);
                        $is_successful = false;
                        WFOCU_Core()->log->log('Order #' . WFOCU_WC_Compatibility::get_order_id($get_order) . ': Unable to create paypal Order refer error below' . print_r($ppcp_resp, true));
                    } else {
                        $order->update_meta_data('_paypal_order_id', $ppcp_resp['id']);
                        $order->save();
                        $this->payal_order_id = $ppcp_resp['id'];
                        if ('COMPLETED' == $ppcp_resp['status']) {
                            $get_order->update_meta_data('wfocu_ppcp_order_current', $ppcp_resp['id']);
                            $get_order->save();
                            WFOCU_Core()->log->log('Order #' . WFOCU_WC_Compatibility::get_order_id($get_order) . ': PayPal Order successfully created');
                            $transaction_id = $ppcp_resp['purchase_units'][0]['payments']['captures'][0]['id'];
                            WFOCU_Core()->data->set('_transaction_id', $transaction_id);
                            $is_successful = true;
                        } else {
                            $is_successful = false;
                            WFOCU_Core()->log->log('Order #' . WFOCU_WC_Compatibility::get_order_id($get_order) . ': Unable to create paypal Order refer error below' . print_r($ppcp_resp, true));
                        }
                    }
                } else {
                    $is_successful = false;
                }
                add_action('wfocu_offer_new_order_created_' . $this->get_key(), array($this, 'add_paypal_meta_in_new_order'), 10, 2);
                return $this->handle_result($is_successful);
            } else {
                return parent::process_charge($order);
            }
        } catch (Exception $ex) {
            
        }
    }

    public function get_ppcp_meta() {
        try {
            $this->paymentaction = apply_filters('angelleye_ppcp_paymentaction', $this->paymentaction, null);
            return array(
                'environment' => ($this->is_sandbox) ? 'sandbox' : '',
                'intent' => ($this->paymentaction === 'capture') ? 'CAPTURE' : 'AUTHORIZE',
                'merchant_id' => $this->merchant_id,
                'invoice_prefix' => $this->invoice_prefix,
            );
        } catch (Exception $ex) {
            
        }
    }

    public function add_paypal_meta_in_new_order($get_order) {
        try {
            if (!empty($this->payal_order_id)) {
                $get_order->update_meta_data('_transaction_id', $this->payal_order_id);
                $get_order->save();
            }
        } catch (Exception $ex) {
            
        }
    }

    public function get_purchase_units($order, $offer_package, $args) {
        try {
            $invoice_id = $args['invoice_prefix'] . '-wfocu-' . $this->get_order_number($order);
            $total_amount = $offer_package['total'];
            $purchase_unit = array(
                'reference_id' => 'default',
                'amount' => array(
                    'currency_code' => $order->get_currency(),
                    'value' => (string) $this->round($total_amount),
                    'breakdown' => $this->get_item_breakdown($order, $offer_package),
                ),
                'description' => __('One Time Offer - ' . $order->get_id(), 'upstroke-woocommerce-one-click-upsell-paypal-angell-eye'),
                'items' => $this->add_offer_item_data($order, $offer_package),
                'payee' => array(
                    'merchant_id' => $args['merchant_id']
                ),
                'shipping' => array(
                    'name' => array(
                        'full_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                    ),
                ),
                'custom_id' => apply_filters('angelleye_ppcp_custom_id', $invoice_id, $order),
                'invoice_id' => $invoice_id
            );
            return array($purchase_unit);
        } catch (Exception $ex) {
            
        }
    }

    public function get_item_breakdown($order, $package) {
        try {
            $breakdown = array();
            $order_subtotal = 0.00;
            foreach ($package['products'] as $item) {
                $order_subtotal += $item['args']['total'];
            }
            $breakdown['item_total'] = array(
                'currency_code' => $order->get_currency(),
                'value' => (string) $this->round($order_subtotal),
            );
            $breakdown['tax_total'] = array(
                'currency_code' => $order->get_currency(),
                'value' => ( isset($package['taxes']) ) ? ( (string) $this->validate_tax($package) ) : '0',
            );
            if (( isset($package['shipping']) && isset($package['shipping']['diff']))) {
                if (0 <= $package['shipping']['diff']['cost']) {
                    $shipping = ( isset($package['shipping']) && isset($package['shipping']['diff']) ) ? ( (string) $package['shipping']['diff']['cost'] ) : 0;
                    if (!empty($shipping) && 0 < intval($shipping)) {
                        $breakdown['shipping'] = array(
                            'currency_code' => $order->get_currency(),
                            'value' => (string) $this->round($shipping),
                        );
                    }
                } else {
                    $shipping = ( isset($package['shipping']) && isset($package['shipping']['diff']) ) ? ( (string) $package['shipping']['diff']['cost'] ) : 0;
                    $breakdown['shipping_discount'] = array(
                        'currency_code' => $order->get_currency(),
                        'value' => (string) abs($this->round($shipping)),
                    );
                    $breakdown['shipping'] = array(
                        'currency_code' => $order->get_currency(),
                        'value' => '0.00',
                    );
                }
            }
            return $breakdown;
        } catch (Exception $ex) {
            
        }
    }

    public function add_offer_item_data($order, $package) {
        try {
            $order_items = [];
            foreach ($package['products'] as $item) {
                $product = $item['data'];
                $title = $product->get_title();
                if (strlen($title) > 127) {
                    $title = substr($title, 0, 124) . '...';
                }
                $order_items[] = array(
                    'name' => $title,
                    'unit_amount' => array(
                        'currency_code' => $order->get_currency(),
                        'value' => (string) $this->round($item['price']),
                    ),
                    'quantity' => 1,
                    'description' => $this->get_item_description($product),
                );
            };
            return $order_items;
        } catch (Exception $ex) {
            
        }
    }

    private function round($number, $precision = 2) {
        try {
            return round((float) $number, $precision);
        } catch (Exception $ex) {
            
        }
    }

    public function validate_tax($offer_package) {
        try {
            $tax = $this->round($offer_package['taxes']);
            $total_amount = (float) $offer_package['total'];
            $shipping = ( isset($offer_package['shipping']) && isset($offer_package['shipping']['diff']) ) ? ( (string) $offer_package['shipping']['diff']['cost'] ) : 0;
            $item_total = 0;
            foreach ($offer_package['products'] as $item) {
                $item_total += $this->round($item['price']);
            };
            if ($total_amount === ( $item_total + $tax )) {
                return $tax;
            }
            if ($total_amount !== ( $item_total + $tax )) {
                $tax += $total_amount - ( $item_total + $tax + $this->round($shipping) );
            }
            if ($tax < 0) {
                return $this->round(0);
            }
            return $this->round($tax);
        } catch (Exception $ex) {
            
        }
    }

    private function get_item_description($product_or_str) {
        try {
            if (is_string($product_or_str)) {
                $str = $product_or_str;
            } else {
                $str = $product_or_str->get_short_description();
            }
            $item_desc = wp_strip_all_tags(wp_specialchars_decode(wp_staticize_emoji($str)));
            $item_desc = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $item_desc);
            $item_desc = str_replace("\n", ', ', rtrim($item_desc));
            if (strlen($item_desc) > 127) {
                $item_desc = substr($item_desc, 0, 124) . '...';
            }
            return html_entity_decode($item_desc, ENT_NOQUOTES, 'UTF-8');
        } catch (Exception $ex) {
            
        }
    }

    public function get_api_base($mode = '') {
        $live_url = 'https://api-m.paypal.com/';
        $sandbox_url = 'https://api-m.sandbox.paypal.com/';
        if (empty($mode)) {
            return ($this->is_sandbox == 'yes') ? $sandbox_url : $live_url;
        } else {
            return ( 'live' === $mode ) ? $live_url : $sandbox_url;
        }
    }

    public function process_refund_offer($order) {
        try {
            $refund_data = $_POST;
            $order_id = WFOCU_WC_Compatibility::get_order_id($order);
            $amount = isset($refund_data['amt']) ? $refund_data['amt'] : '';
            $event_id = isset($refund_data['event_id']) ? $refund_data['event_id'] : '';
            $txn_id = isset($refund_data['txn_id']) ? $refund_data['txn_id'] : '';
            $response = false;
            if (!empty($event_id) && !empty($order_id) && !empty($txn_id)) {
                if (!is_null($amount)) {
                    $environment = $order->get_meta('_enviorment');
                    $api_url = $this->get_api_base($environment) . 'v2/payments/captures/' . $txn_id . '/refund';
                    $data = array(
                        'amount' => array(
                            'currency_code' => $order->get_currency(),
                            'value' => (string) $this->round($amount),
                        ),
                    );
                    $arguments = array(
                        'method' => 'POST',
                        'headers' => array(
                            'Authorization' => '',
                            'Content-Type' => 'application/json',
                            "prefer" => "return=representation",
                            'PayPal-Request-Id' => $this->generate_request_id(),
                            'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion($environment)
                        ),
                        'body' => $data,
                    );
                    $resp = $this->api_request->request($api_url, $arguments, 'refund_order');
                    if (!isset($resp['status']) || !$resp['status'] == "COMPLETED") {
                        return false;
                    } else {
                        return $resp['id'];
                    }
                }
            }
            return $response;
        } catch (Exception $ex) {
            
        }
    }

    public function generate_request_id() {
        try {
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
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_paypalauthassertion($environment) {
        try {
            $temp = array(
                "alg" => "none"
            );
            $partner_client_id = ($environment == 'sandbox') ? PAYPAL_PPCP_SANDBOX_PARTNER_CLIENT_ID : PAYPAL_PPCP_PARTNER_CLIENT_ID;
            $merchant_id = ($environment == 'sandbox') ? $this->setting_obj->get('sandbox_merchant_id', '') : $this->setting_obj->get('live_merchant_id', '');
            $returnData = base64_encode(json_encode($temp)) . '.';
            $temp = array(
                "iss" => $partner_client_id,
                "payer_id" => $merchant_id
            );
            $returnData .= base64_encode(json_encode($temp)) . '.';
            return $returnData;
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_force_token_save($is_enabled) {
        try {
            if (false !== $this->should_tokenize()) {
                return true;
            }
            return $is_enabled;
        } catch (Exception $ex) {
            
        }
    }

    public function add_order_id_as_meta($event) {
        if (!empty($this->payal_order_id)) {
            WFOCU_Core()->track->add_meta($event, '_paypal_order_id', $this->payal_order_id);
        }
    }
}
