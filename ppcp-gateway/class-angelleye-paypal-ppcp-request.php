<?php

defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Request {

    public $is_sandbox;
    public $token_url;
    public $paypal_oauth_api;
    public $generate_token_url;
    public $basicAuth;
    public $client_token;
    public $api_response;
    public $result;
    public $settings;
    public $api_request;
    protected static $_instance = null;
    public $api_log;
    public $ppcp_host;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->angelleye_ppcp_load_class();
        $this->angelleye_get_settings();
    }

    public function angelleye_get_settings() {
        $this->is_sandbox = 'yes' === $this->settings->get('testmode', 'no');
        $this->paymentaction = $this->settings->get('paymentaction', 'capture');
        $this->sandbox_client_id = $this->settings->get('sandbox_client_id', '');
        $this->sandbox_secret_id = $this->settings->get('sandbox_api_secret', '');
        $this->live_client_id = $this->settings->get('api_client_id', '');
        $this->live_secret_id = $this->settings->get('api_secret', '');
        if (!empty($this->sandbox_client_id) && !empty($this->sandbox_secret_id)) {
            $this->is_sandbox_first_party_used = 'yes';
            $this->is_sandbox_third_party_used = 'no';
        } else if (!empty($this->sandbox_merchant_id)) {
            $this->is_sandbox_third_party_used = 'yes';
            $this->is_sandbox_first_party_used = 'no';
        } else {
            $this->is_sandbox_third_party_used = 'no';
            $this->is_sandbox_first_party_used = 'no';
        }
        if (!empty($this->live_client_id) && !empty($this->live_secret_id)) {
            $this->is_live_first_party_used = 'yes';
            $this->is_live_third_party_used = 'no';
        } else if (!empty($this->live_merchant_id)) {
            $this->is_live_third_party_used = 'yes';
            $this->is_live_first_party_used = 'no';
        } else {
            $this->is_live_third_party_used = 'no';
            $this->is_live_first_party_used = 'no';
        }
        if ($this->is_sandbox) {
            $this->merchant_id = $this->settings->get('sandbox_merchant_id', '');
            $this->client_id = $this->sandbox_client_id;
            $this->secret_id = $this->sandbox_secret_id;
            if ($this->is_sandbox_first_party_used === 'yes') {
                $this->is_first_party_used = 'yes';
            } else {
                $this->is_first_party_used = 'no';
            }
        } else {
            $this->merchant_id = $this->settings->get('live_merchant_id', '');
            $this->client_id = $this->live_client_id;
            $this->secret_id = $this->live_secret_id;
            if ($this->is_live_first_party_used === 'yes') {
                $this->is_first_party_used = 'yes';
            } else {
                $this->is_first_party_used = 'no';
            }
        }
        $this->basicAuth = base64_encode($this->client_id . ":" . $this->secret_id);
        if (is_angelleye_aws_down() == false) {
            $this->ppcp_host = PAYPAL_FOR_WOOCOMMERCE_PPCP_AWS_WEB_SERVICE;
        } else {
            $this->ppcp_host = PAYPAL_FOR_WOOCOMMERCE_PPCP_ANGELLEYE_WEB_SERVICE;
        }
        if ($this->is_sandbox) {
            $this->token_url = 'https://api-m.sandbox.paypal.com/v1/oauth2/token';
            $this->paypal_oauth_api = 'https://api-m.sandbox.paypal.com/v1/oauth2/token/';
            $this->generate_token_url = 'https://api-m.sandbox.paypal.com/v1/identity/generate-token';
        } else {
            $this->token_url = 'https://api-m.paypal.com/v1/oauth2/token';
            $this->paypal_oauth_api = 'https://api-m.paypal.com/v1/oauth2/token/';
            $this->generate_token_url = 'https://api-m.paypal.com/v1/identity/generate-token';
        }
    }

    public function angelleye_ppcp_remote_get($paypal_url, $args, $action_name) {
        $body['testmode'] = ($this->is_sandbox) ? 'yes' : 'no';
        $body['paypal_url'] = $paypal_url;
        $body['paypal_header'] = $args['headers'];
        $body['paypal_method'] = isset($args['method']) ? $args['method'] : 'GET';
        if (isset($args['body']) && is_array($args['body'])) {
            $body['paypal_body'] = $args['body'];
        } else {
            $body['paypal_body'] = null;
        }
        $body['action_name'] = $action_name;
        $args['method'] = 'POST';
        $args['body'] = wp_json_encode($body);
        $args['timeout'] = 70;
        $args['user-agent'] = 'PFW_PPCP';
        $args['headers'] = array('Content-Type' => 'application/json');
        $this->result = wp_remote_get($this->ppcp_host . 'ppcp-request', $args);
        return $this->result;
    }

    public function request($url, $args, $action_name = 'default') {
        try {
            $this->angelleye_get_settings();
            if (strpos($url, 'paypal.com') === false) {
                $args['timeout'] = '60';
                $args['user-agent'] = 'PFW_PPCP';
                $this->result = wp_remote_get($url, $args);
            } else if ($this->is_first_party_used === 'yes') {
                unset($args['headers']['Paypal-Auth-Assertion']);
                $args['headers']['Authorization'] = "Basic " . $this->basicAuth;
                if (isset($args['body']) && is_array($args['body'])) {
                    $args['body'] = wp_json_encode($args['body']);
                }
                $args['timeout'] = '60';
                $args['user-agent'] = 'PFW_PPCP';
                $this->result = wp_remote_get($url, $args);
            } else {
                $args['timeout'] = '60';
                $args['user-agent'] = 'PFW_PPCP';
                $this->result = $this->angelleye_ppcp_remote_get($url, $args, $action_name);
            }

            return $this->api_response->parse_response($this->result, $url, $args, $action_name);
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
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
            if (!class_exists('AngellEYE_PayPal_PPCP_Log')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-log.php';
            }
            $this->api_log = AngellEYE_PayPal_PPCP_Log::instance();
            $this->settings = WC_Gateway_PPCP_AngellEYE_Settings::instance();
            $this->api_response = AngellEYE_PayPal_PPCP_Response::instance();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

}
