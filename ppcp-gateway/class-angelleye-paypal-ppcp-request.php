<?php

class AngellEYE_PayPal_PPCP_Request {

    public $api_response;
    public $api_log;
    public $api_error;
    public $mode;
    public $settings;
    public $result;


    public function __construct() {
        $this->angelleye_ppcp_load_class();
    }

    
    public function request($url, $args) {
        try {
            $this->result = wp_remote_get($url, $args);
            return $this->api_response->parse_response($this->result, $url, $args);
        } catch (Exception $ex) {
            
        }
    }
    
    public function angelleye_ppcp_load_class() {
        try {
            if (!class_exists('AngellEYE_PayPal_PPCP_DCC_Validate')) {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-dcc-validate.php');
            }
            if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-ppcp-angelleye-settings.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Response')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-response.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Error')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-error.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Log')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-log.php';
            }
            $this->settings = new WC_Gateway_PPCP_AngellEYE_Settings();
            $this->api_response = new AngellEYE_PayPal_PPCP_Response();
            $this->api_error = new AngellEYE_PayPal_PPCP_Error();
            $this->api_log = new AngellEYE_PayPal_PPCP_Log();
        } catch (Exception $ex) {
            
        }
    }


}
