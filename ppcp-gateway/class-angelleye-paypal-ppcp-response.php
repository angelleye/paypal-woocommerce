<?php

class AngellEYE_PayPal_PPCP_Response {

    public $settings;
    public $api_log;

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
            $this->angelleye_ppcp_write_log($url, $request, $response);
            return $response;
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_write_log($url, $request, $response) {
        $this->api_log->log('PFW Version : ' . VERSION_PFW);
        $this->api_log->log('Request URL : ' . $url);
        $this->api_log->log('Request Data : ' . wc_print_r($request, true));
        if (!empty($response)) {
            $this->api_log->log('Response Data : ' . wc_print_r($response, true));
        }
    }

    public function angelleye_ppcp_load_class() {
        try {
            if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-ppcp-angelleye-settings.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Log')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-log.php';
            }
            $this->settings = new WC_Gateway_PPCP_AngellEYE_Settings();
            $this->api_log = new AngellEYE_PayPal_PPCP_Log();
        } catch (Exception $ex) {
            
        }
    }

}
