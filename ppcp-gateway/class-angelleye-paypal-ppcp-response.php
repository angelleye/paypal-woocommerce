<?php

defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Response {

    public $api_log;
    protected static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->angelleye_ppcp_load_class();
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
            }
            $this->angelleye_ppcp_write_log($url, $request, $paypal_api_response, $action_name);
            return $response;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_write_log($url, $request, $response, $action_name = 'Exception') {
        global $wp_version;
        if (strpos($action_name, 'webhook') !== false) {
            $this->api_log->webhook_log('WordPress Version: ' . $wp_version);
            $this->api_log->webhook_log('WooCommerce Version: ' . WC()->version);
            $this->api_log->webhook_log('PFW Version: ' . VERSION_PFW);
            $this->api_log->webhook_log('Action: ' . $action_name);
            $this->api_log->webhook_log('Request URL: ' . $url);
            if (!empty($request['body']) && is_array($request['body'])) {
                $this->api_log->webhook_log('Request Body: ' . wc_print_r($request['body'], true));
            } elseif (isset($request['body']) && !empty($request['body']) && is_string($request['body'])) {
                $this->api_log->webhook_log('Request Body: ' . wc_print_r(json_decode($request['body'], true), true));
            }
            $this->api_log->webhook_log('Response Code: ' . wp_remote_retrieve_response_code($response));
            $this->api_log->webhook_log('Response Message: ' . wp_remote_retrieve_response_message($response));
            $this->api_log->webhook_log('Response Body: ' . wc_print_r(json_decode(wp_remote_retrieve_body($response), true), true));
        } else {
            $this->api_log->log('WordPress Version: ' . $wp_version);
            $this->api_log->log('WooCommerce Version: ' . WC()->version);
            $this->api_log->log('PFW Version: ' . VERSION_PFW);
            $this->api_log->log('Action: ' . $action_name);
            $this->api_log->log('Request URL: ' . $url);
            if (!empty($request['body']) && is_array($request['body'])) {
                $this->api_log->log('Request Body: ' . wc_print_r($request['body'], true));
            } elseif (isset($request['body']) && !empty($request['body']) && is_string($request['body'])) {
                $this->api_log->log('Request Body: ' . wc_print_r(json_decode($request['body'], true), true));
            }
            $this->api_log->log('Response Code: ' . wp_remote_retrieve_response_code($response));
            $this->api_log->log('Response Message: ' . wp_remote_retrieve_response_message($response));
            $this->api_log->log('Response Body: ' . wc_print_r(json_decode(wp_remote_retrieve_body($response), true), true));
        }
    }

    public function angelleye_ppcp_load_class() {
        try {
            if (!class_exists('AngellEYE_PayPal_PPCP_Log')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-log.php';
            }
            $this->api_log = AngellEYE_PayPal_PPCP_Log::instance();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

}
