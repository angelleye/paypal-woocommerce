<?php

defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Response {

    public $api_log;
    public $setting_obj;
    public $generate_signup_link_default_request_param;
    protected static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->angelleye_ppcp_load_class();
        $this->generate_signup_link_default_request_param = array(
            'tracking_id' => '',
            'partner_config_override' => array(
                'partner_logo_url' => 'https://www.angelleye.com/wp-content/uploads/2015/06/angelleye-logo-159x43.png',
                'return_url' => '',
                'return_url_description' => '',
                'show_add_credit_card' => true,
            ),
            'products' => '',
            'legal_consents' => array(
                array(
                    'type' => 'SHARE_DATA_CONSENT',
                    'granted' => true,
                ),
            ),
            'operations' => array(
                array(
                    'operation' => 'API_INTEGRATION',
                    'api_integration_preference' => array(
                        'rest_api_integration' => array(
                            'integration_method' => 'PAYPAL',
                            'integration_type' => 'THIRD_PARTY',
                            'third_party_details' => array(
                                'features' => array(
                                    'PAYMENT',
                                    'FUTURE_PAYMENT',
                                    'REFUND',
                                    'ADVANCED_TRANSACTIONS_SEARCH',
                                    'ACCESS_MERCHANT_INFORMATION',
                                    'PARTNER_FEE'
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );
        $this->is_sandbox = 'yes' === $this->setting_obj->get('testmode', 'no');
        add_action('angelleye_ppcp_request_respose_data', array($this, 'angelleye_ppcp_tpv_tracking'), 10, 3);
    }

    public function parse_response($paypal_api_response, $url, $request, $action_name) {

        try {
            if (is_wp_error($paypal_api_response)) {
                delete_transient('is_angelleye_aws_down');
                $response = array(
                    'status' => 'faild',
                    'body' => array('error_message' => $paypal_api_response->get_error_message(), 'error_code' => $paypal_api_response->get_error_code())
                );
            } else {
                $body = wp_remote_retrieve_body($paypal_api_response);
                $status_code = (int) wp_remote_retrieve_response_code($paypal_api_response);
                if (201 < $status_code && $action_name !== 'update_order') {
                    delete_transient('is_angelleye_aws_down');
                }
                $response = !empty($body) ? json_decode($body, true) : '';
                $response = isset($response['body']) ? $response['body'] : $response;
                $this->angelleye_ppcp_write_log($url, $request, $paypal_api_response, $action_name);
                if (strpos($url, 'paypal.com') !== false) {
                    do_action('angelleye_ppcp_request_respose_data', $request, $response, $action_name);
                }
                return $response;
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_write_log($url, $request, $response, $action_name = 'Exception') {
        global $wp_version;
        if($action_name === 'list_all_payment_tokens') {
           return false;
        }
        if($action_name === 'seller_onboarding_status' && !isset($_GET['merchantIdInPayPal'])) {
          //  return false;
        }
        $environment = ($this->is_sandbox === true) ? 'SANDBOX' : 'LIVE';
        $this->api_log->log('PayPal Environment: ' . $environment);
        $this->api_log->log('WordPress Version: ' . $wp_version);
        $this->api_log->log('WooCommerce Version: ' . WC()->version);
        $this->api_log->log('PFW Version: ' . VERSION_PFW);
        $this->api_log->log('Action: ' . ucwords(str_replace('_', ' ', $action_name)));
        $this->api_log->log('Request URL: ' . $url);
        $response_body = isset($response['body']) ? json_decode($response['body'], true) : $response;
        if ($action_name === 'generate_signup_link') {
            $this->angelleye_ppcp_signup_link_write_log($request);
        } elseif (!empty($request['body']) && is_array($request['body'])) {
            $this->api_log->log('Request Body: ' . wc_print_r($request['body'], true));
        } elseif (isset($request['body']) && !empty($request['body']) && is_string($request['body'])) {
            $this->api_log->log('Request Body: ' . wc_print_r(json_decode($request['body'], true), true));
        }
        if (!empty($response_body['requestId'])) {
            $this->api_log->log('Request ID: ' . wc_print_r($response_body['requestId'], true));
        }
        if (!empty($response_body['headers'])) {
            $this->api_log->log('Response Headers: ' . wc_print_r($response_body['headers'], true));
        }
        if (!empty($response_body['body']) && is_array($response_body['body'])) {
            $this->api_log->log('Response Body: ' . wc_print_r($response_body['body'], true));
        } elseif (is_array($response_body)) {
            $this->api_log->log('Response Body: ' . wc_print_r($response_body, true));
        } else {
            $this->api_log->log('Response Body: ' . wc_print_r(json_decode(wp_remote_retrieve_body($response_body), true), true));
        }
    }

    public function angelleye_ppcp_load_class() {
        try {
            if (!class_exists('AngellEYE_PayPal_PPCP_Log')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-log.php';
            }
            if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-ppcp-angelleye-settings.php';
            }
            $this->setting_obj = WC_Gateway_PPCP_AngellEYE_Settings::instance();
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
        if (isset($headers[$key_debug])) {
            return $headers[$key_debug];
        }
        if (is_string($headers)) {
            $headers = str_replace("\r\n", "\n", $headers);
            $headers = preg_replace('/\n[ \t]/', ' ', $headers);
            $headers = explode("\n", $headers);
        }
        if (isset($headers) && is_array($headers)) {
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
        return '';
    }

    public function angelleye_ppcp_signup_link_write_log($request) {
        if (isset($request['body'])) {
            $data = json_decode($request['body'], true);
            if (isset($data['tracking_id'])) {
                $this->generate_signup_link_default_request_param['tracking_id'] = $data['tracking_id'];
            }
            if (isset($data['return_url'])) {
                $this->generate_signup_link_default_request_param['partner_config_override']['return_url'] = $data['return_url'];
            }
            if (isset($data['return_url'])) {
                $this->generate_signup_link_default_request_param['partner_config_override']['return_url_description'] = $data['return_url_description'];
            }
            if (isset($data['products'])) {
                $this->generate_signup_link_default_request_param['products'] = $data['products'];
            }
            if (isset($data['capabilities'])) {
                $this->generate_signup_link_default_request_param['capabilities'] = $data['capabilities'];
            }
            if (isset($data['third_party_features'])) {
                $this->generate_signup_link_default_request_param['operations'][0]['api_integration_preference']['rest_api_integration']['third_party_details']['features'] = array_merge($this->generate_signup_link_default_request_param['operations'][0]['api_integration_preference']['rest_api_integration']['third_party_details']['features'], $data['third_party_features']);
            }
            $this->api_log->log('Request Body: ' . wc_print_r($this->generate_signup_link_default_request_param, true));
        }
    }

}
