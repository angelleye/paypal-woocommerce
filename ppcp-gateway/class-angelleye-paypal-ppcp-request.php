<?php

class AngellEYE_PayPal_PPCP_Request {

    public $api_response;
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
            if (!class_exists('AngellEYE_PayPal_PPCP_Response')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-response.php';
            }
            $this->api_response = new AngellEYE_PayPal_PPCP_Response();
        } catch (Exception $ex) {
            
        }
    }

}
