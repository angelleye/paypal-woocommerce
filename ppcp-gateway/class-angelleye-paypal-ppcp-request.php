<?php

defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Request {

    public $is_sandbox;
    public $token_url;
    public $paypal_oauth_api;
    public $generate_token_url;
    public $client_id;
    public $secret_key;
    public $access_token;
    public $basicAuth;
    public $client_token;
    public $api_response;
    public $result;
    public $settings;
    public $api_request;
    protected static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->angelleye_ppcp_load_class();
        $this->is_sandbox = $this->settings->has('testmode') && $this->settings->get('testmode');
        $this->paymentaction = $this->settings->get('paymentaction', 'capture');
        if ($this->is_sandbox) {
            $this->token_url = 'https://api.sandbox.paypal.com/v1/oauth2/token';
            $this->paypal_oauth_api = 'https://api.sandbox.paypal.com/v1/oauth2/token/';
            $this->generate_token_url = 'https://api.sandbox.paypal.com/v1/identity/generate-token';
            $this->client_id = $this->settings->get('sandbox_client_id');
            $this->secret_key = $this->settings->get('sandbox_secret_key');
            $this->access_token = get_transient('angelleye_ppcp_sandbox_access_token');
            $this->basicAuth = base64_encode($this->client_id . ":" . $this->secret_key);
            $this->client_token = get_transient('angelleye_ppcp_sandbox_client_token');
        } else {
            $this->token_url = 'https://api.paypal.com/v1/oauth2/token';
            $this->paypal_oauth_api = 'https://api.paypal.com/v1/oauth2/token/';
            $this->generate_token_url = 'https://api.paypal.com/v1/identity/generate-token';
            $this->client_id = $this->settings->get('live_client_id');
            $this->secret_key = $this->settings->get('live_secret_key');
            $this->basicAuth = base64_encode($this->client_id . ":" . $this->secret_key);
            $this->access_token = get_transient('angelleye_ppcp_live_access_token');
            $this->client_token = get_transient('angelleye_ppcp_live_client_token');
        }
        if (!$this->access_token) {
            if (!empty($this->client_id) && !empty($this->secret_key)) {
                $this->angelleye_ppcp_get_access_token();
            }
        }
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
            if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-ppcp-angelleye-settings.php';
            }
            $this->settings = WC_Gateway_PPCP_AngellEYE_Settings::instance();
            $this->api_response = AngellEYE_PayPal_PPCP_Response::instance();
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_get_access_token() {
        try {
            $args = array(
                'method' => 'POST',
                'timeout' => 60,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking' => true,
                'headers' => array('Accept' => 'application/json', 'Authorization' => "Basic " . $this->basicAuth, 'PayPal-Partner-Attribution-Id' => 'Angelleye-123'),
                'body' => array('grant_type' => 'client_credentials'),
                'cookies' => array()
            );
            $api_response = $this->request($this->paypal_oauth_api, $args);
            if (!empty($api_response['access_token'])) {
                if ($this->is_sandbox) {
                    set_transient('angelleye_ppcp_sandbox_access_token', $api_response['access_token'], 29000);
                } else {
                    set_transient('angelleye_ppcp_live_access_token', $api_response['access_token'], 29000);
                }
                $this->access_token = $api_response['access_token'];
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_get_genrate_token() {
        try {
            if ($this->access_token) {
                $args = array(
                    'method' => 'POST',
                    'timeout' => 60,
                    'redirection' => 5,
                    'httpversion' => '1.1',
                    'blocking' => true,
                    'headers' => array('Content-Type' => 'application/json', 'Authorization' => "Bearer " . $this->access_token, 'Accept-Language' => 'en_US'),
                    'cookies' => array()
                );
                $api_response = $this->request($this->generate_token_url, $args);
                if (!empty($api_response['client_token'])) {
                    if ($this->is_sandbox) {
                        set_transient('angelleye_ppcp_sandbox_client_token', $api_response['client_token'], 3000);
                    } else {
                        set_transient('angelleye_ppcp_live_client_token', $api_response['client_token'], 3000);
                    }
                    $this->client_token = $api_response['client_token'];
                    return $this->client_token;
                }
            }
        } catch (Exception $ex) {
            
        }
    }

}
