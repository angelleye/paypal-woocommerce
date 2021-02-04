<?php

defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Response {

    public $settings;
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

    public function parse_response($paypal_api_response, $url, $request) {
        try {
            if (is_wp_error($paypal_api_response)) {
                $response = array(
                    'result' => 'faild',
                    'body' => array('error_message' => $paypal_api_response->get_error_message(), 'error_code' => $paypal_api_response->get_error_code())
                );
            } else {
                $body = wp_remote_retrieve_body($paypal_api_response);
                $response = json_decode($body, true);
            }
            $this->angelleye_ppcp_write_log($url, $request, $paypal_api_response);
            return $response;
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_write_log($url, $request, $response) {
        $this->api_log->log('PFW Version : ' . VERSION_PFW);
        $this->api_log->log('Request URL : ' . $url);
        $this->api_log->log('Request Body : ' . wc_print_r($request, true));
        $this->api_log->log('Response Code: ' . wp_remote_retrieve_response_code($response));
        $this->api_log->log('Response Message: ' . wp_remote_retrieve_response_message($response));
        $this->api_log->log('Response Body : ' . wc_print_r(json_decode(wp_remote_retrieve_body($response), true), true));
    }

    public function angelleye_ppcp_load_class() {
        try {
            if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-ppcp-angelleye-settings.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Log')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-log.php';
            }
            $this->settings = WC_Gateway_PPCP_AngellEYE_Settings::instance();
            $this->api_log = AngellEYE_PayPal_PPCP_Log::instance();
        } catch (Exception $ex) {
            
        }
    }

}
