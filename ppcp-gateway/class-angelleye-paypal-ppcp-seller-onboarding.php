<?php

defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Seller_Onboarding {

    public $dcc_applies;
    public $on_board_host;
    public $on_board_sandbox_host;
    public $testmode;
    public $settings;
    public $host;
    public $partner_merchant_id;
    public $sandbox_partner_merchant_id;
    public $api_request;
    public $result;
    protected static $_instance = null;
    public $api_log;
    public $is_sandbox;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        try {
            $this->angelleye_ppcp_load_class();
            $this->sandbox_partner_merchant_id = PAYPAL_PPCP_SNADBOX_PARTNER_MERCHANT_ID;
            $this->on_board_sandbox_host = PAYPAL_SELLER_ONBOARDING_SANDBOX_URL;
            $this->partner_merchant_id = PAYPAL_PPCP_PARTNER_MERCHANT_ID;
            $this->on_board_host = PAYPAL_SELLER_ONBOARDING_LIVE_URL;
            add_action('wc_ajax_ppcp_login_seller', array($this, 'angelleye_ppcp_login_seller'));
            add_action('admin_init', array($this, 'angelleye_ppcp_listen_for_merchant_id'));
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
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
            if (!class_exists('AngellEYE_PayPal_PPCP_Request')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-request.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Log')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-log.php';
            }
            $this->api_log = AngellEYE_PayPal_PPCP_Log::instance();
            $this->settings = WC_Gateway_PPCP_AngellEYE_Settings::instance();
            $this->dcc_applies = AngellEYE_PayPal_PPCP_DCC_Validate::instance();
            $this->api_request = AngellEYE_PayPal_PPCP_Request::instance();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function nonce() {
        return 'a1233wtergfsdt4365tzrshgfbaewa36AGa1233wtergfsdt4365tzrshgfbaewa36AG';
    }

    public function data() {
        $data = $this->default_data();
        return $data;
    }

    public function angelleye_generate_signup_link($testmode) {
        $this->is_sandbox = ( $testmode === 'yes' ) ? true : false;
        if ($this->is_sandbox) {
            $host_url = $this->on_board_sandbox_host;
        } else {
            $host_url = $this->on_board_host;
        }
        $args = array(
            'method' => 'POST',
            'body' => $this->data(),
            'headers' => array(),
        );
        return $this->api_request->request($host_url, $args, 'generate_signup_link');
    }

    private function default_data() {
        return array(
            'testmode' => ($this->is_sandbox) ? 'yes' : 'no',
            'return_url' => admin_url(
                    'admin.php?page=wc-settings&tab=checkout&section=angelleye_ppcp'
            ),
            'return_url_description' => __(
                    'Return to your shop.', 'paypal-for-woocommerce'
            ),
            'products' => array(
                $this->dcc_applies->for_country_currency() ? 'PPCP' : 'EXPRESS_CHECKOUT',
        ));
    }

    public function angelleye_ppcp_login_seller() {
        try {
            $posted_raw = angelleye_ppcp_get_raw_data();
            if (empty($posted_raw)) {
                return false;
            }
            $data = json_decode($posted_raw, true);
            $this->angelleye_ppcp_get_credentials($data);
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_get_credentials($data) {
        try {
            $this->is_sandbox = isset($data['env']) && 'sandbox' === $data['env'];
            $this->host = ($this->is_sandbox) ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
            $this->settings->set('testmode', ($this->is_sandbox) ? 'yes' : 'no');
            $this->settings->persist();
            $token = $this->angelleye_ppcp_get_access_token($data);
            $credentials = $this->angelleye_ppcp_get_seller_rest_api_credentials($token);
            if (!empty($credentials['client_secret']) && !empty($credentials['client_id'])) {
                if ($this->is_sandbox) {
                    $this->settings->set('sandbox_secret_key', $credentials['client_secret']);
                    $this->settings->set('sandbox_client_id', $credentials['client_id']);
                    delete_transient('angelleye_ppcp_sandbox_access_token');
                    delete_transient('angelleye_ppcp_sandbox_client_token');
                } else {
                    $this->settings->set('live_secret_key', $credentials['client_secret']);
                    $this->settings->set('live_client_id', $credentials['client_id']);
                    delete_transient('angelleye_ppcp_live_access_token');
                    delete_transient('angelleye_ppcp_live_client_token');
                }
                $this->settings->persist();
                if ($this->is_sandbox) {
                    set_transient('angelleye_ppcp_sandbox_seller_onboarding_process_done', 'yes', 29000);
                } else {
                    set_transient('angelleye_ppcp_live_seller_onboarding_process_done', 'yes', 29000);
                }

                if (function_exists('angelleye_ppcp_may_register_webhook')) {
                    angelleye_ppcp_may_register_webhook();
                }
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_get_access_token($data) {
        try {
            $authCode = $data['authCode'];
            $sharedId = $data['sharedId'];
            $url = trailingslashit($this->host) . 'v1/oauth2/token/';
            $args = array(
                'method' => 'POST',
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode($sharedId . ':'),
                ),
                'body' => array(
                    'grant_type' => 'authorization_code',
                    'code' => $authCode,
                    'code_verifier' => $this->nonce(),
                ),
            );
            $this->result = $this->api_request->request($url, $args, 'get_access_token');
            if (isset($this->result['access_token'])) {
                return $this->result['access_token'];
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
            return false;
        }
    }

    public function angelleye_ppcp_get_seller_rest_api_credentials($token) {
        if ($this->is_sandbox) {
            $partner_merchant_id = $this->sandbox_partner_merchant_id;
        } else {
            $partner_merchant_id = $this->partner_merchant_id;
        }
        try {
            $url = trailingslashit($this->host) .
                    'v1/customer/partners/' . $partner_merchant_id .
                    '/merchant-integrations/credentials/';
            $args = array(
                'method' => 'GET',
                'headers' => array(
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ),
            );
            $this->result = $this->api_request->request($url, $args, 'get_credentials');
            if (!isset($this->result['client_id']) || !isset($this->result['client_secret'])) {
                return false;
            }
            return $this->result;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
            return false;
        }
    }

    public function angelleye_ppcp_listen_for_merchant_id() {
        try {
            if (!$this->is_valid_site_request()) {
                return;
            }
            if (!isset($_GET['merchantIdInPayPal']) || !isset($_GET['merchantId'])) {
                return;
            }
            $merchant_id = sanitize_text_field(wp_unslash($_GET['merchantIdInPayPal']));
            $merchant_email = sanitize_text_field(wp_unslash($_GET['merchantId']));
            $this->is_sandbox = 'yes' === $this->settings->get('testmode', 'no');
            if ($this->is_sandbox) {
                $this->settings->set('sandbox_merchant_id', $merchant_id);
                $this->settings->set('sandbox_email_address', $merchant_email);
            } else {
                $this->settings->set('live_merchant_id', $merchant_id);
                $this->settings->set('live_email_address', $merchant_email);
            }
            $this->settings->persist();
            $redirect_url = admin_url('admin.php?page=wc-settings&tab=checkout&section=angelleye_ppcp');
            wp_safe_redirect($redirect_url, 302);
            exit;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function is_valid_site_request() {
        if (!isset($_REQUEST['section']) || !in_array(sanitize_text_field(wp_unslash($_REQUEST['section'])), array('angelleye_ppcp'), true)) {
            return false;
        }
        if (!current_user_can('manage_options')) {
            return false;
        }
        return true;
    }

}
