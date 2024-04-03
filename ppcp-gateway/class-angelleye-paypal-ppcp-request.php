<?php

defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Request {

    public $is_sandbox;
    public $token_url;
    public $paypal_oauth_api;
    public $generate_token_url;
    public $basicAuth;
    public $client_token;
    public ?AngellEYE_PayPal_PPCP_Response $api_response;
    public $result;
    public $setting_obj;
    public $api_request;
    protected static $_instance = null;
    public $api_log;
    public $ppcp_host;
    public $payment_request;
    public static $capabilitiesToCheck = null;
    public $paymentaction;
    public $sandbox_client_id;
    public $sandbox_secret_id;
    public $live_client_id;
    public $live_secret_id;
    public $is_sandbox_third_party_used;
    public $is_sandbox_first_party_used;
    public $is_live_third_party_used;
    public $is_live_first_party_used;
    public $merchant_id;
    public $client_id;
    public $secret_id;
    public $is_first_party_used;
    public $skip_final_review;
    public $billing_address;
    public $disable_term;
    public $softdescriptor;
    public $testmode;
    public $fraud_management_filters;
    public $email_notify_order_cancellations;
    public $pending_authorization_order_status;
    public $enable_in_context_checkout_flow;
    public $id;
    public $API_Endpoint;
    public $PAYPAL_URL;
    public $api_username;
    public $api_password;
    public $api_signature;
    public $Force_tls_one_point_two;

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
        $this->is_sandbox = 'yes' === $this->setting_obj->get('testmode', 'no');
        $this->paymentaction = $this->setting_obj->get('paymentaction', 'capture');
        $this->sandbox_client_id = $this->setting_obj->get('sandbox_client_id', '');
        $this->sandbox_secret_id = $this->setting_obj->get('sandbox_api_secret', '');
        $this->live_client_id = $this->setting_obj->get('api_client_id', '');
        $this->live_secret_id = $this->setting_obj->get('api_secret', '');
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
            $this->merchant_id = $this->setting_obj->get('sandbox_merchant_id', '');
            $this->client_id = $this->sandbox_client_id;
            $this->secret_id = $this->sandbox_secret_id;
            if ($this->is_sandbox_first_party_used === 'yes') {
                $this->is_first_party_used = 'yes';
            } else {
                $this->is_first_party_used = 'no';
            }
        } else {
            $this->merchant_id = $this->setting_obj->get('live_merchant_id', '');
            $this->client_id = $this->live_client_id;
            $this->secret_id = $this->live_secret_id;
            if ($this->is_live_first_party_used === 'yes') {
                $this->is_first_party_used = 'yes';
            } else {
                $this->is_first_party_used = 'no';
            }
        }
        $this->basicAuth = base64_encode($this->client_id . ":" . $this->secret_id);
        // TODO Include this file from a better place, not a good place to include it.
        if(!function_exists('is_angelleye_aws_down')) {
            include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/angelleye-paypal-ppcp-common-functions.php');
        }
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
        $body['meta'] = [
            'plugin_version' => VERSION_PFW,
            'wp_home' => get_home_url(),
        ];
        $body['paypal_url'] = $paypal_url;
        $body['paypal_header'] = $args['headers'] ?? [];
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
        if ('seller_onboarding_status' !== $action_name) {
            if ($this->angelleye_ppcp_paypal_fee()) {
                $args['headers'][AE_FEE] = "true";
            }
        }
        $args['headers']['plugin_version_id'] = VERSION_PFW;
        if ('generate_id_token' === $action_name) {
            $this->result = wp_remote_get($this->ppcp_host . 'generate-id-token', $args);
        } else {
            $this->result = wp_remote_get($this->ppcp_host . 'ppcp-request', $args);
        }
        return $this->result;
    }

    public function request($url, $args, $action_name = 'default') {
        try {
            $this->angelleye_get_settings();
            if (!str_contains($url, 'paypal.com')) {
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
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' .$ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function multi_account_request($url, $args, $action_name = 'default') {
        try {
            $this->result = wp_remote_get($url, $args);
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
            $this->setting_obj = WC_Gateway_PPCP_AngellEYE_Settings::instance();
            $this->api_response = AngellEYE_PayPal_PPCP_Response::instance();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' .$ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_paypal_fee() {
        if (false === ( $value = get_transient(AE_FEE) )) {
            if (!class_exists('AngellEYE_PayPal_PPCP_Seller_Onboarding')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-seller-onboarding.php';
            }
            $seller_onboarding = AngellEYE_PayPal_PPCP_Seller_Onboarding::instance();
            $result = $seller_onboarding->angelleye_track_seller_onboarding_status($this->merchant_id);
            if ($seller_onboarding->angelleye_ppcp_is_fee_enable($result)) {
                set_transient(AE_FEE, 'yes', 12 * HOUR_IN_SECONDS);
                return true;
            } else {
                set_transient(AE_FEE, 'no', 12 * HOUR_IN_SECONDS);
                return false;
            }
        } else {
            return 'yes' === $value;
        }
    }

    public static function angelleye_ppcp_get_available_endpoints($merchant_id) {
        if (self::$capabilitiesToCheck !== null) {
            return self::$capabilitiesToCheck;
        }
        $availableEndpoints = array();
        if (empty($merchant_id)) {
            return false;
        }
        if (!class_exists('AngellEYE_PayPal_PPCP_Seller_Onboarding')) {
            include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-seller-onboarding.php';
        }
        $seller_onboarding = AngellEYE_PayPal_PPCP_Seller_Onboarding::instance();
        $result = $seller_onboarding->angelleye_track_seller_onboarding_status($merchant_id);
        if (!isset($result['products'])) {
            self::$capabilitiesToCheck = false;
            return false;
        }

        $capabilitiesToCheck = ['advanced_cc' => 'CUSTOM_CARD_PROCESSING',
            'apple_pay' => 'APPLE_PAY', 'google_pay' => 'GOOGLE_PAY'];

        if (isset($result['products']) && isset($result['capabilities']) && !empty($result['products']) && !empty($result['products'])) {
            foreach ($result['products'] as $key => $product) {
                foreach ($capabilitiesToCheck as $capabilityKey => $capabilityName) {
                    if (isset($product['vetting_status']) && ('SUBSCRIBED' === $product['vetting_status'] || 'APPROVED' === $product['vetting_status'] ) && isset($product['capabilities']) && is_array($product['capabilities']) && in_array($capabilityName, $product['capabilities'])) {
                        foreach ($result['capabilities'] as $key => $capabilities) {
                            if (isset($capabilities['name']) && $capabilityName === $capabilities['name'] && 'ACTIVE' === $capabilities['status']) {
                                $availableEndpoints[$capabilityKey] = $product['vetting_status'];
                            }
                        }
                    }
                }
            }
        }
        if( angelleye_is_vaulting_enable($result) ) {
            $availableEndpoints['vaulting_advanced'] = 'SUBSCRIBED';
        }
        self::$capabilitiesToCheck = $availableEndpoints;
        return $availableEndpoints;
    }
}
