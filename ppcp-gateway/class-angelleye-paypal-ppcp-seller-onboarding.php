<?php

class AngellEYE_PayPal_PPCP_Seller_Onboarding {

    public $dcc_applies;
    public $on_board_host;
    public $testmode;
    public $settings;
    public $host;
    public $partner_merchant_id;
    public $api_request;
    public $webhook;
    public $result;

    public function __construct() {
        try {
            $this->angelleye_ppcp_load_class();
            $this->partner_merchant_id = PAYPAL_PPCP_SNADBOX_PARTNER_MERCHANT_ID;
            $this->on_board_host = 'http://kcppdevelopers.aetesting.xyz/paypal-seller-onboarding/seller-onboarding.php';
            add_action('wc_ajax_ppcp_login_seller', array($this, 'angelleye_ppcp_login_seller'));
            add_action('admin_init', array($this, 'angelleye_ppcp_listen_for_merchant_id'));
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
            if (!class_exists('AngellEYE_PayPal_PPCP_Request')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-request.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Webhook')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-webhook.php';
            }
            $this->settings = new WC_Gateway_PPCP_AngellEYE_Settings();
            $this->dcc_applies = new AngellEYE_PayPal_PPCP_DCC_Validate();
            $this->api_request = new AngellEYE_PayPal_PPCP_Request();
            $this->webhook = new AngellEYE_PayPal_PPCP_Webhook();
        } catch (Exception $ex) {
            
        }
    }

    public function nonce() {
        return 'a1233wtergfsdt4365tzrshgfbaewa36AGa1233wtergfsdt4365tzrshgfbaewa36AG';
    }

    public function data() {
        $data = $this->default_data();
        return $data;
    }

    public function angelleye_genrate_signup_link($testmode) {
        $this->testmode = $testmode;
        $args = array(
            'method' => 'POST',
            'body' => $this->data(),
            'headers' => array(),
        );
        return $this->api_request->request($this->on_board_host, $args);
    }

    private function default_data() {
        return array(
            'testmode' => $this->testmode,
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
            
        }
    }

    public function angelleye_ppcp_get_credentials($data) {
        try {
            $is_sandbox = isset($data['env']) && 'sandbox' === $data['env'];
            $this->host = ($is_sandbox) ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
            $this->settings->set('testmode', ($is_sandbox) ? 'yes' : 'no');
            $this->settings->persist();
            $token = $this->angelleye_ppcp_get_access_token($data);
            $credentials = $this->angelleye_ppcp_get_seller_rest_api_credentials($token);
            if ($is_sandbox) {
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
            // need to add single time cron here to create webhook
            //$this->webhook->angelleye_ppcp_create_webhooks_request();
        } catch (Exception $ex) {
            
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
            $this->result = $this->api_request->request($url, $args);
            if (isset($this->result['access_token'])) {
                return $this->result['access_token'];
            }
        } catch (Exception $ex) {
            return false;
        }
    }

    public function angelleye_ppcp_get_seller_rest_api_credentials($token) {
        try {
            $url = trailingslashit($this->host) .
                    'v1/customer/partners/' . $this->partner_merchant_id .
                    '/merchant-integrations/credentials/';
            $args = array(
                'method' => 'GET',
                'headers' => array(
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ),
            );
            $this->result = $this->api_request->request($url, $args);
            if (!isset($this->result['client_id']) || !isset($this->result['client_secret'])) {
                return false;
            }
            return $this->result;
        } catch (Exception $ex) {
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
            $this->settings->set('merchant_id', $merchant_id);
            $this->settings->set('merchant_email', $merchant_email);
            $is_sandbox = $this->settings->has('testmode') && $this->settings->get('testmode');
            if ($is_sandbox) {
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

new AngellEYE_PayPal_PPCP_Seller_Onboarding();
