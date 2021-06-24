<?php

defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Response {

    public $api_log;
    public $settings;
    protected static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->angelleye_ppcp_load_class();
        $this->is_sandbox = 'yes' === $this->settings->get('testmode', 'no');
        add_action('angelleye_ppcp_request_respose_data', array($this, 'angelleye_ppcp_tpv_tracking'), 10, 3);
    }

    public function parse_response($paypal_api_response, $url, $request, $action_name) {
        
        try {
            if (is_wp_error($paypal_api_response)) {
                $response = array(
                    'result' => 'faild',
                    'body' => array('error_message' => $paypal_api_response->get_error_message(), 'error_code' => $paypal_api_response->get_error_code())
                );
            } else {
                $body = wp_remote_retrieve_body($paypal_api_response);
                $response = !empty($body) ? json_decode($body, true) : '';
                if (strpos($url, 'paypal.com') !== false) {
                    $response = isset($response['body']) ? json_decode($response['body'], true) : $response;
                }
            }
            do_action('angelleye_ppcp_request_respose_data', $request, $response, $action_name);
            $this->angelleye_ppcp_write_log($url, $request, $paypal_api_response, $action_name);
            return $response;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_write_log($url, $request, $response, $action_name = 'Exception') {
        global $wp_version;
        $environment = ($this->is_sandbox === true) ? 'SANDBOX' : 'LIVE';
        $this->api_log->log('PayPal Environment: ' . $environment);
        $this->api_log->log('WordPress Version: ' . $wp_version);
        $this->api_log->log('WooCommerce Version: ' . WC()->version);
        $this->api_log->log('PFW Version: ' . VERSION_PFW);
        $this->api_log->log('Action: ' . $action_name);
        $this->api_log->log('Request URL: ' . $url);
        $response_body = isset($response['body']) ? json_decode($response['body'], true) : $response;
        $this->api_log->log('PayPal Debug ID: ' . $this->angelleye_ppcp_parse_headers($response_body['headers'], 'paypal-debug-id'));
        if (!empty($request['body']) && is_array($request['body'])) {
            $this->api_log->log('Request Body: ' . wc_print_r($request['body'], true));
        } elseif (isset($request['body']) && !empty($request['body']) && is_string($request['body'])) {
            $this->api_log->log('Request Body: ' . wc_print_r(json_decode($request['body'], true), true));
        }
        $this->api_log->log('Response Code: ' . wp_remote_retrieve_response_code($response));
        $this->api_log->log('Response Message: ' . wp_remote_retrieve_response_message($response));
        $this->api_log->log('Response Body: ' . wc_print_r(json_decode(wp_remote_retrieve_body($response_body), true), true));
    }

    public function angelleye_ppcp_load_class() {
        try {
            if (!class_exists('AngellEYE_PayPal_PPCP_Log')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-log.php';
            }
            if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-ppcp-angelleye-settings.php';
            }
            $this->settings = WC_Gateway_PPCP_AngellEYE_Settings::instance();
            $this->api_log = AngellEYE_PayPal_PPCP_Log::instance();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_tpv_tracking($request, $response, $action_name) {
        try {
            $allow_payment_event = array('capture_order', 'refund_order', 'authorize_order', 'void_authorized', 'capture_authorized');
            if (in_array($action_name, $allow_payment_event)) {
                if (class_exists('AngellEYE_PFW_Payment_Logger')) {
                    $amount = '';
                    $transaction_id = '';
                    if (isset($response['purchase_units']['0']['amount']['value'])) {
                        $amount = $response['purchase_units']['0']['amount']['value'];
                    } elseif (isset($response['amount']['value'])) {
                        $amount = $response['amount']['value'];
                    }
                    if (isset($response['purchase_units']['0']['payments']['captures'][0]['id'])) {
                        $transaction_id = $response['purchase_units']['0']['payments']['captures'][0]['id'];
                    } elseif (isset($response['purchase_units']['0']['payments']['authorizations']['0']['id'])) {
                        $transaction_id = $response['purchase_units']['0']['payments']['authorizations']['0']['id'];
                    } elseif (isset($response['id'])) {
                        $transaction_id = $response['id'];
                    }
                    $opt_in = get_option('angelleye_send_opt_in_logging_details', 'no');
                    $payment_logger = AngellEYE_PFW_Payment_Logger::instance();
                    $request_param['type'] = 'ppcp_' . $action_name;
                    $request_param['amount'] = $amount;
                    $request_param['status'] = 'Success';
                    $request_param['site_url'] = get_bloginfo('url');
                    $request_param['mode'] = ($this->is_sandbox === true) ? 'sandbox' : 'live';
                    $request_param['merchant_id'] = isset($response['purchase_units']['0']['payee']['merchant_id']) ? $response['purchase_units']['0']['payee']['merchant_id'] : '';
                    $request_param['correlation_id'] = '';
                    $request_param['transaction_id'] = $transaction_id;
                    $request_param['product_id'] = '1';
                    $payment_logger->angelleye_tpv_request($request_param);
                }
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_parse_headers($headers, $key_debug) {
        if (is_string($headers)) {
            $headers = str_replace("\r\n", "\n", $headers);
            $headers = preg_replace('/\n[ \t]/', ' ', $headers);
            $headers = explode("\n", $headers);
        }
        for ($i = count($headers) - 1; $i >= 0; $i--) {
            if (!empty($headers[$i]) && false === strpos($headers[$i], ':')) {
                $headers = array_splice($headers, $i);
                break;
            }
        }
        $newheaders = array();
        foreach ((array) $headers as $tempheader) {
            if (empty($tempheader)) {
                continue;
            }
            if (strpos($tempheader, ':') !== false) {
                list($key, $value) = explode(':', $tempheader, 2);
                $key = strtolower($key);
                $value = trim($value);
                if (isset($newheaders[$key])) {
                    if (!is_array($newheaders[$key])) {
                        $newheaders[$key] = array($newheaders[$key]);
                    }
                    $newheaders[$key][] = $value;
                } else {
                    $newheaders[$key] = $value;
                }
            }
        }
        return isset($newheaders[$key_debug]) ? $newheaders[$key_debug] : '';
    }

}
