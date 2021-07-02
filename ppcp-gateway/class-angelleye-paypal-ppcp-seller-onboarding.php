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
            //add_action('wc_ajax_ppcp_login_seller', array($this, 'angelleye_ppcp_login_seller'));
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
        $body = $this->data();
        if ($this->is_sandbox) {
            $host_url = $this->on_board_sandbox_host . 'seller-onboarding.php';
            $tracking_id = angelleye_key_generator();
            $body['tracking_id'] = $tracking_id;
            update_option('angelleye_ppcp_sandbox_tracking_id', $tracking_id);
        } else {
            $host_url = $this->on_board_host . 'seller-onboarding.php';
            $tracking_id = angelleye_key_generator();
            $body['tracking_id'] = $tracking_id;
            update_option('angelleye_ppcp_live_tracking_id', $tracking_id);
        }
        $args = array(
            'method' => 'POST',
            'body' => $body,
            'headers' => array(),
        );
        return $this->api_request->request($host_url, $args, 'generate_signup_link');
    }

    private function default_data() {
        $testmode = ($this->is_sandbox) ? 'yes' : 'no';
        return array(
            'testmode' => $testmode,
            'return_url' => admin_url(
                    'admin.php?page=wc-settings&tab=checkout&section=angelleye_ppcp&testmode=' . $testmode
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
            $this->api_log->log('angelleye_ppcp_login_seller', 'error');
            $this->api_log->log(print_r($posted_raw, true), 'error');
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
            if ($this->is_sandbox) {
                $this->settings->set('enabled', 'yes');
            } else {
                $this->settings->set('enabled', 'yes');
            }
            $this->settings->persist();
            if ($this->is_sandbox) {
                set_transient('angelleye_ppcp_sandbox_seller_onboarding_process_done', 'yes', 29000);
            } else {
                set_transient('angelleye_ppcp_live_seller_onboarding_process_done', 'yes', 29000);
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_listen_for_merchant_id() {
        try {
            $this->is_sandbox = false;
            if (!$this->is_valid_site_request()) {
                return;
            }
            if (!isset($_GET['merchantIdInPayPal'])) {
                return;
            }
            if (!isset($_GET['testmode'])) {
                return;
            }
            if (isset($_GET['testmode']) && 'yes' === $_GET['testmode']) {
                $this->is_sandbox = true;
            }
            $this->settings->set('enabled', 'yes');
            $this->settings->set('testmode', ($this->is_sandbox) ? 'yes' : 'no');
            $this->host = ($this->is_sandbox) ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
            $merchant_id = sanitize_text_field(wp_unslash($_GET['merchantIdInPayPal']));
            if (isset($_GET['merchantId'])) {
                $merchant_email = sanitize_text_field(wp_unslash($_GET['merchantId']));
            } else {
                $merchant_email = '';
            }
            if ($this->is_sandbox) {
                $this->settings->set('sandbox_merchant_id', $merchant_id);
                set_transient('angelleye_ppcp_sandbox_seller_onboarding_process_done', 'yes', 29000);
                $this->settings->set('enabled', 'yes');
            } else {
                $this->settings->set('live_merchant_id', $merchant_id);
                set_transient('angelleye_ppcp_live_seller_onboarding_process_done', 'yes', 29000);
                $this->settings->set('enabled', 'yes');
            }
            $this->settings->persist();
            $this->angelleye_get_seller_onboarding_status();
            $redirect_url = admin_url('admin.php?page=wc-settings&tab=checkout&section=angelleye_ppcp');
            wp_safe_redirect($redirect_url, 302);
            exit;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_get_seller_onboarding_status() {
        try {
            if ($this->is_sandbox) {
                $host_url = $this->on_board_sandbox_host . 'merchant-integrations.php';
                $tracking_id = get_option('angelleye_ppcp_sandbox_tracking_id', '');
                $body['tracking_id'] = $tracking_id;
                $body['testmode'] = ($this->is_sandbox) ? 'yes' : 'no';
            } else {
                $host_url = $this->on_board_host . 'merchant-integrations.php';
                $tracking_id = get_option('angelleye_ppcp_live_tracking_id', '');
                $body['tracking_id'] = $tracking_id;
                $body['testmode'] = ($this->is_sandbox) ? 'yes' : 'no';
            }
            $args = array(
                'method' => 'POST',
                'body' => $body,
                'headers' => array(),
            );
            $seller_onboarding_status = $this->api_request->request($host_url, $args, 'get_tracking_status');
            if (isset($seller_onboarding_status['result']) && 'success' === $seller_onboarding_status['result'] && !empty($seller_onboarding_status['body'])) {
                $json = json_decode($seller_onboarding_status['body']);
                if (!empty($json->merchant_id)) {
                    if ($this->is_sandbox) {
                        $this->settings->set('sandbox_merchant_id', $json->merchant_id);
                        $this->settings->set('enabled', 'yes');
                    } else {
                        $this->settings->set('live_merchant_id', $json->merchant_id);
                        $this->settings->set('enabled', 'yes');
                    }
                    $this->settings->persist();
                    $this->result = $this->angelleye_track_seller_onboarding_status($json->merchant_id);
                    if ($this->angelleye_is_acdc_payments_enable($this->result)) {
                        $this->settings->set('enable_advanced_card_payments', 'yes');
                    } else {
                        $this->settings->set('enable_advanced_card_payments', 'no');
                    }
                }
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
            return false;
        }
    }

    public function angelleye_track_seller_onboarding_status($merchant_id) {
        $this->is_sandbox = 'yes' === $this->settings->get('testmode', 'no');
        if ($this->is_sandbox) {
            $partner_merchant_id = $this->sandbox_partner_merchant_id;
        } else {
            $partner_merchant_id = $this->partner_merchant_id;
        }
        try {
            $this->api_request = new AngellEYE_PayPal_PPCP_Request();
            $url = trailingslashit($this->host) .
                    'v1/customer/partners/' . $partner_merchant_id .
                    '/merchant-integrations/' . $merchant_id;
            $args = array(
                'method' => 'GET',
                'headers' => array(
                    'Authorization' => '',
                    'Content-Type' => 'application/json',
                ),
            );
            $this->result = $this->api_request->request($url, $args, 'seller_onboarding_status');
            return $this->result;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
            return false;
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

    public function angelleye_is_acdc_payments_enable($result) {
        if (isset($result['products']) && isset($result['capabilities']) && !empty($result['products']) && !empty($result['products'])) {
            foreach ($result['products'] as $key => $product) {
                if (isset($product['vetting_status']) && ('SUBSCRIBED' === $product['vetting_status'] || 'APPROVED' === $product['vetting_status'] ) && isset($product['capabilities']) && is_array($product['capabilities']) && in_array('CUSTOM_CARD_PROCESSING', $product['capabilities'])) {
                    foreach ($result['capabilities'] as $key => $capabilities) {
                        if (isset($capabilities['name']) && 'CUSTOM_CARD_PROCESSING' === $capabilities['name'] && 'ACTIVE' === $capabilities['status']) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

}
