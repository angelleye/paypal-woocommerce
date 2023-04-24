<?php

defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Seller_Onboarding {

    public $dcc_applies;
    public $ppcp_host;
    public $testmode;
    public $setting_obj;
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
            if (is_angelleye_aws_down() == false) {
                $this->ppcp_host = PAYPAL_FOR_WOOCOMMERCE_PPCP_AWS_WEB_SERVICE;
            } else {
                $this->ppcp_host = PAYPAL_FOR_WOOCOMMERCE_PPCP_ANGELLEYE_WEB_SERVICE;
            }
            $this->angelleye_ppcp_load_class();
            $this->sandbox_partner_merchant_id = PAYPAL_PPCP_SANDBOX_PARTNER_MERCHANT_ID;
            $this->partner_merchant_id = PAYPAL_PPCP_PARTNER_MERCHANT_ID;
            //add_action('wc_ajax_ppcp_login_seller', array($this, 'angelleye_ppcp_login_seller'));
            add_action('admin_init', array($this, 'angelleye_ppcp_listen_for_merchant_id'));
            add_action('wp_ajax_angelleye_ppcp_onboard_email_sendy_subscription', array($this, 'angelleye_ppcp_onboard_email_sendy_subscription'));
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
            $this->setting_obj = WC_Gateway_PPCP_AngellEYE_Settings::instance();
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

    public function angelleye_generate_signup_link($testmode, $page) {
        $this->is_sandbox = ( $testmode === 'yes' ) ? true : false;
        $body = $this->default_data();
        if ($page === 'gateway_settings') {
            $body['return_url'] = add_query_arg(array('place' => 'gateway_settings', 'utm_nooverride' => '1'), untrailingslashit($body['return_url']));
        } else {
            $body['return_url'] = add_query_arg(array('place' => 'admin_settings_onboarding', 'utm_nooverride' => '1'), untrailingslashit($body['return_url']));
        }
        if ($this->is_sandbox) {
            $tracking_id = angelleye_key_generator();
            $body['tracking_id'] = $tracking_id;
            update_option('angelleye_ppcp_sandbox_tracking_id', $tracking_id);
        } else {
            $tracking_id = angelleye_key_generator();
            $body['tracking_id'] = $tracking_id;
            update_option('angelleye_ppcp_live_tracking_id', $tracking_id);
        }
        $host_url = $this->ppcp_host . 'generate-signup-link';
        $args = array(
            'method' => 'POST',
            'body' => wp_json_encode($body),
            'headers' => array('Content-Type' => 'application/json'),
        );
        return $this->api_request->request($host_url, $args, 'generate_signup_link');
    }
    
    public function angelleye_generate_signup_link_with_vault($testmode, $page) {
        $this->is_sandbox = ( $testmode === 'yes' ) ? true : false;
        $body = $this->ppcp_vault_data();
        if ($page === 'gateway_settings') {
            $body['return_url'] = add_query_arg(array('place' => 'gateway_settings', 'utm_nooverride' => '1'), untrailingslashit($body['return_url']));
        } else {
            $body['return_url'] = add_query_arg(array('place' => 'admin_settings_onboarding', 'utm_nooverride' => '1'), untrailingslashit($body['return_url']));
        }
        if ($this->is_sandbox) {
            $tracking_id = angelleye_key_generator();
            $body['tracking_id'] = $tracking_id;
            update_option('angelleye_ppcp_sandbox_tracking_id', $tracking_id);
        } else {
            $tracking_id = angelleye_key_generator();
            $body['tracking_id'] = $tracking_id;
            update_option('angelleye_ppcp_live_tracking_id', $tracking_id);
        }
        $host_url = $this->ppcp_host . 'generate-signup-link';
        $args = array(
            'method' => 'POST',
            'body' => wp_json_encode($body),
            'headers' => array('Content-Type' => 'application/json'),
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
                $this->dcc_applies->for_country_currency() ? 'PPCP' : 'EXPRESS_CHECKOUT'
        ));
    }
    
    private function ppcp_vault_data() {
        $testmode = ($this->is_sandbox) ? 'yes' : 'no';
        return array(
            'testmode' => $testmode,
            'return_url' => admin_url(
                    'admin.php?page=wc-settings&tab=checkout&section=angelleye_ppcp&testmode=' . $testmode
            ),
            'return_url_description' => __(
                    'Return to your shop.', 'paypal-for-woocommerce'
            ),
            'capabilities' => array('PAYPAL_WALLET_VAULTING_ADVANCED'),
            'third_party_features' => array('VAULT', 'BILLING_AGREEMENT'),
            'products' => array(
                $this->dcc_applies->for_country_currency() ? 'PPCP' : 'EXPRESS_CHECKOUT',
                'ADVANCED_VAULTING'
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
            $this->setting_obj->set('testmode', ($this->is_sandbox) ? 'yes' : 'no');
            $this->setting_obj->persist();
            if ($this->is_sandbox) {
                $this->setting_obj->set('enabled', 'yes');
            } else {
                $this->setting_obj->set('enabled', 'yes');
            }
            $this->setting_obj->persist();
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
            $this->setting_obj->set('enabled', 'yes');
            $this->setting_obj->set('testmode', ($this->is_sandbox) ? 'yes' : 'no');
            $this->host = ($this->is_sandbox) ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
            $merchant_id = sanitize_text_field(wp_unslash($_GET['merchantIdInPayPal']));
            if (isset($_GET['merchantId'])) {
                $merchant_email = sanitize_text_field(wp_unslash($_GET['merchantId']));
            } else {
                $merchant_email = '';
            }
            if ($this->is_sandbox) {
                $this->setting_obj->set('sandbox_merchant_id', $merchant_id);
                set_transient('angelleye_ppcp_sandbox_seller_onboarding_process_done', 'yes', 29000);
                $this->api_log->log("sandbox_merchant_id: " . $merchant_id, 'error');
                $this->setting_obj->set('enabled', 'yes');
            } else {
                $this->setting_obj->set('live_merchant_id', $merchant_id);
                set_transient('angelleye_ppcp_live_seller_onboarding_process_done', 'yes', 29000);
                $this->setting_obj->set('enabled', 'yes');
            }
            $this->setting_obj->persist();
            $this->angelleye_get_seller_onboarding_status();
            if (isset($_GET['place']) && $_GET['place'] === 'gateway_settings') {
                $redirect_url = admin_url('admin.php?page=wc-settings&tab=checkout&section=angelleye_ppcp');
            } else {
                $redirect_url = admin_url('options-general.php?page=paypal-for-woocommerce&tab=general_settings&gateway=paypal_payment_gateway_products');
            }
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
                $tracking_id = get_option('angelleye_ppcp_sandbox_tracking_id', '');
                $body['tracking_id'] = $tracking_id;
                $body['testmode'] = ($this->is_sandbox) ? 'yes' : 'no';
            } else {
                $tracking_id = get_option('angelleye_ppcp_live_tracking_id', '');
                $body['tracking_id'] = $tracking_id;
                $body['testmode'] = ($this->is_sandbox) ? 'yes' : 'no';
            }
            $args = array(
                'method' => 'POST',
                'body' => wp_json_encode($body),
                'headers' => array('Content-Type' => 'application/json'),
            );
            $host_url = $this->ppcp_host . 'get-tracking-status';
            $seller_onboarding_status = $this->api_request->request($host_url, $args, 'get_tracking_status');
            if(!isset($seller_onboarding_status['merchant_id'])) {
                $seller_onboarding_status['merchant_id'] = sanitize_text_field(wp_unslash($_GET['merchantIdInPayPal']));
            }
            if (!empty($seller_onboarding_status['merchant_id'])) {
                if ($this->is_sandbox) {
                    $this->setting_obj->set('sandbox_client_id', '');
                    $this->setting_obj->set('sandbox_api_secret', '');
                    $this->setting_obj->set('sandbox_merchant_id', $seller_onboarding_status['merchant_id']);
                    $this->setting_obj->set('enabled', 'yes');
                } else {
                    $this->setting_obj->set('api_client_id', '');
                    $this->setting_obj->set('api_secret', '');
                    $this->setting_obj->set('live_merchant_id', $seller_onboarding_status['merchant_id']);
                    $this->setting_obj->set('enabled', 'yes');
                }
                $this->setting_obj->persist();
                $this->result = $this->angelleye_track_seller_onboarding_status($seller_onboarding_status['merchant_id']);
                if (!empty($this->result['primary_email'])) {
                    own_angelleye_sendy_list($this->result['primary_email']);
                }
                if ($this->angelleye_is_acdc_payments_enable($this->result)) {
                    $this->setting_obj->set('enable_advanced_card_payments', 'yes');
                    $this->setting_obj->persist();
                } else {
                    $this->setting_obj->set('enable_advanced_card_payments', 'no');
                    $this->setting_obj->persist();
                }
                if ($this->angelleye_is_vaulting_enable($this->result)) {
                    $this->setting_obj->set('enable_tokenized_payments', 'yes');
                    $this->setting_obj->persist();
                } else {
                    $this->setting_obj->set('enable_tokenized_payments', 'no');
                    $this->setting_obj->persist();
                }
                if ($this->angelleye_ppcp_is_fee_enable($this->result)) {
                    set_transient(AE_FEE, 'yes', 24 * DAY_IN_SECONDS);
                } else {
                    set_transient(AE_FEE, 'no', 24 * DAY_IN_SECONDS);
                }
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
            return false;
        }
    }

    public function angelleye_track_seller_onboarding_status($merchant_id) {
        $this->is_sandbox = 'yes' === $this->setting_obj->get('testmode', 'no');
        $this->host = ($this->is_sandbox) ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
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
    
    public function angelleye_is_vaulting_enable($result) {
    
    if (isset($result['products']) && isset($result['capabilities']) && !empty($result['products']) && !empty($result['products'])) {
            foreach ($result['products'] as $key => $product) {
                if (isset($product['vetting_status']) && ('SUBSCRIBED' === $product['vetting_status'] || 'APPROVED' === $product['vetting_status'] ) && isset($product['capabilities']) && is_array($product['capabilities']) && in_array('PAYPAL_WALLET_VAULTING_ADVANCED', $product['capabilities'])) {
                    foreach ($result['capabilities'] as $key => $capabilities) {
                        if (isset($capabilities['name']) && 'PAYPAL_WALLET_VAULTING_ADVANCED' === $capabilities['name'] && 'ACTIVE' === $capabilities['status']) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    public function angelleye_ppcp_is_fee_enable($response) {
        try {
            if (!empty($response)) {
                if (isset($response['oauth_integrations']['0']['integration_type']) && 'OAUTH_THIRD_PARTY' === $response['oauth_integrations']['0']['integration_type']) {
                    if (isset($response['oauth_integrations']['0']['oauth_third_party']['0']['scopes']) && is_array($response['oauth_integrations']['0']['oauth_third_party']['0']['scopes'])) {
                        foreach ($response['oauth_integrations']['0']['oauth_third_party']['0']['scopes'] as $key => $scope) {
                            if (strpos($scope, 'payments/partnerfee') !== false) {
                                return true;
                            }
                        }
                    }
                }
            }
            return false;
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_onboard_email_sendy_subscription() {
        global $wp;
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $current_url = $_SERVER['HTTP_REFERER'];
        } else {
            $current_url = home_url(add_query_arg(array(), $wp->request));
        }
        $url = 'https://sendy.angelleye.com/subscribe';
        $response = wp_remote_post($url, array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'body' => array('list' => 'oV0I12rDwJdMDL2jYzvwPQ',
                'boolean' => 'true',
                'email' => $_POST['email'],
                'gdpr' => 'true',
                'silent' => 'true',
                'api_key' => 'qFcoVlU2uG3AMYabNTrC',
                'referrer' => $current_url
            ),
            'cookies' => array()
                )
        );
        if (is_wp_error($response)) {
            wp_send_json(wp_remote_retrieve_body($response));
        } else {
            $body = wp_remote_retrieve_body($response);
            $apiResponse = strval($body);
            switch ($apiResponse) {
                case 'true':
                case '1':
                    prepareResponse("true", 'Thank you for subscribing!');
                case 'Already subscribed.':
                    prepareResponse("true", 'Already subscribed!');
                default:
                    prepareResponse("false", $apiResponse);
            }
        }
    }

}
