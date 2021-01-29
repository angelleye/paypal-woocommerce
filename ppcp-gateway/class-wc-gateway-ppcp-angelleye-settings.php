<?php

if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {

    class WC_Gateway_PPCP_AngellEYE_Settings {

        public $angelleye_ppcp_gateway_setting;
        public $gateway_key;
        public $settings = array();

        public function __construct() {
            $this->gateway_key = 'woocommerce_angelleye_ppcp_settings';
        }

        public function get($id) {
            if (!$this->has($id)) {
                return false;
            }
            return $this->settings[$id];
        }

        public function has($id) {
            $this->load();
            return array_key_exists($id, $this->settings);
        }

        public function set($id, $value) {
            $this->load();
            $this->settings[$id] = $value;
        }

        public function persist() {
            update_option($this->gateway_key, $this->settings);
        }

        public function load() {
            if ($this->settings) {
                return false;
            }
            $this->settings = get_option($this->gateway_key, array());
            $defaults = array(
                'title' => __('PayPal Checkout', 'paypal-for-woocommerce'),
                'description' => __(
                        'Accept PayPal, PayPal Credit and alternative payment types.', 'paypal-for-woocommerce'
                )
            );
            foreach ($defaults as $key => $value) {
                if (isset($this->settings[$key])) {
                    continue;
                }
                $this->settings[$key] = $value;
            }
            return true;
        }

        public function angelleye_ppcp_setting_fields() {
            $this->angelleye_ppcp_gateway_setting = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable PayPal Checkout', 'paypal-for-woocommerce'),
                    'default' => 'no',
                ),
                'title' => array(
                    'title' => __('Title', 'paypal-for-woocommerce'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'paypal-for-woocommerce'),
                    'default' => __('PayPal Checkout', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => __('Description', 'paypal-for-woocommerce'),
                    'type' => 'text',
                    'description' => __('Payment method description that the customer will see on your checkout.', 'paypal-for-woocommerce'),
                    'default' => __('Accept PayPal, PayPal Credit and alternative payment types.', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                ),
                'account_settings' => array(
                    'title' => __('Account Settings', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'description' => '',
                ),
                'testmode' => array(
                    'title' => __('PayPal sandbox', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable PayPal sandbox', 'paypal-for-woocommerce'),
                    'default' => 'no',
                    'description' => __('Check this box to enable test mode so that all transactions will hit PayPal’s sandbox server instead of the live server. This should only be used during development as no real transactions will occur when this is enabled.', 'paypal-for-woocommerce'),
                    'desc_tip' => true
                ),
                'live_onboarding' => array(
                    'title' => __('Connect to PayPal', 'paypal-for-woocommerce'),
                    'type' => 'angelleye_ppcp_onboarding',
                    'gateway' => 'angelleye_ppcp',
                    'mode' => 'live',
                    'description' => __('Setup or link an existing PayPal account.', 'paypal-for-woocommerce'),
                    'desc_tip' => ''
                ),
                'live_disconnect' => array(
                    'title' => __('Disconnect from PayPal', 'paypal-for-woocommerce'),
                    'type' => 'angelleye_ppcp_text',
                    'mode' => 'live',
                    'description' => __('Click to reset current credentials and use another account.', 'paypal-for-woocommerce'),
                    'desc_tip' => '',
                ),
                'sandbox_onboarding' => array(
                    'title' => __('Connect to PayPal', 'paypal-for-woocommerce'),
                    'type' => 'angelleye_ppcp_onboarding',
                    'gateway' => 'angelleye_ppcp',
                    'mode' => 'sandbox',
                    'description' => __('Setup or link an existing PayPal account.', 'paypal-for-woocommerce'),
                    'desc_tip' => ''
                ),
                'sandbox_disconnect' => array(
                    'title' => __('Disconnect from PayPal', 'paypal-for-woocommerce'),
                    'type' => 'angelleye_ppcp_text',
                    'mode' => 'sandbox',
                    'description' => __('Click to reset current credentials and use another account.', 'paypal-for-woocommerce'),
                    'desc_tip' => ''
                ),
                'api_credentials' => array(
                    'title' => __('API Credentials', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'description' => '',
                ),
                'live_email_address' => array(
                    'title' => __('Live Email address', 'paypal-for-woocommerce'),
                    'type' => 'text',
                    'description' => __('The email address of your PayPal account.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true
                ),
                'live_merchant_id' => array(
                    'title' => __('Live Merchant Id', 'paypal-for-woocommerce'),
                    'type' => 'text',
                    'description' => __('Enter your PayPal Secret.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true
                ),
                'live_client_id' => array(
                    'title' => __('Live Client Id', 'paypal-for-woocommerce'),
                    'type' => 'password',
                    'description' => __('Enter your PayPal Client ID.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true
                ),
                'live_secret_key' => array(
                    'title' => __('Live Secret Key', 'paypal-for-woocommerce'),
                    'type' => 'password',
                    'description' => __('Enter your PayPal Secret.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true
                ),
                'sandbox_api_credentials' => array(
                    'title' => __('Sandbox API Credentials', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'description' => 'Your account setting is set to sandbox, no real charging takes place. To accept live payments, switch your environment to live and connect your PayPal account.',
                ),
                'sandbox_email_address' => array(
                    'title' => __('Sandbox Email address', 'paypal-for-woocommerce'),
                    'type' => 'text',
                    'description' => __('The email address of your PayPal account.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true
                ),
                'sandbox_merchant_id' => array(
                    'title' => __('Sandbox Merchant Id', 'paypal-for-woocommerce'),
                    'type' => 'text',
                    'description' => __('Enter your PayPal Secret.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true
                ),
                'sandbox_client_id' => array(
                    'title' => __('Sandbox Client Id', 'paypal-for-woocommerce'),
                    'type' => 'password',
                    'description' => __('Enter your PayPal Sandbox Client ID.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true
                ),
                'sandbox_secret_key' => array(
                    'title' => __('Sandbox Secret Key', 'paypal-for-woocommerce'),
                    'type' => 'password',
                    'description' => __('Enter your PayPal Sandbox Secret.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true
                )
            );
            if (angelleye_ppcp_is_local_server()) {
                unset($this->angelleye_ppcp_gateway_setting['live_onboarding']);
                unset($this->angelleye_ppcp_gateway_setting['live_disconnect']);
                unset($this->angelleye_ppcp_gateway_setting['sandbox_onboarding']);
                unset($this->angelleye_ppcp_gateway_setting['sandbox_disconnect']);
            }
            return $this->angelleye_ppcp_gateway_setting;
        }

    }

}