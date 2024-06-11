<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {

    class WC_Gateway_PPCP_AngellEYE_Settings {

        public $angelleye_ppcp_gateway_setting;
        public $gateway_key;
        public $setting_obj;
        public $dcc_applies;
        protected static $_instance = null;
        public $need_to_display_paypal_vault_onboard_button = false;
        public $is_paypal_vault_enable = false;
        public $is_apple_pay_enable = false;
        public $is_apple_pay_approved = false;
        public $is_google_pay_enable = false;
        public $is_google_pay_approved = false;
        public $need_to_display_apple_pay_button = false;
        private bool $need_to_display_google_pay_button = false;
        public $merchant_id;
        public bool $is_ppcp_connected;
        public $is_sandbox;
        public $enable_tokenized_payments;
        public $is_multi_account_active;

        public static function instance() {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        public function __construct() {
            $this->gateway_key = 'woocommerce_angelleye_ppcp_settings';
            $this->angelleye_ppcp_load_class();
            $this->setting_obj = array();
        }

        public function angelleye_ppcp_load_class() {
            try {
                if (!class_exists('AngellEYE_PayPal_PPCP_DCC_Validate')) {
                    include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-dcc-validate.php');
                }
                if (!class_exists('AngellEYE_PayPal_PPCP_Request')) {
                    include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-request.php';
                }
                $this->dcc_applies = AngellEYE_PayPal_PPCP_DCC_Validate::instance();
                //add_filter('wcml_gateway_text_keys_to_translate', [$this, 'wpml_add_translatable_setting_fields']);
            } catch (Exception $ex) {
                
            }
        }

        public function wpml_add_translatable_setting_fields($text_keys) {
            $text_keys = array_merge($text_keys, ['advanced_card_payments_title']);
            return $text_keys;
        }

        public function get($id, $default = false) {
            if (!$this->has($id)) {
                return $default;
            }
            return empty($this->setting_obj[$id]) ? $default : $this->setting_obj[$id];
        }

        public function get_load() {
            return get_option($this->gateway_key, array());
        }

        public function has($id) {
            $this->load();
            return array_key_exists($id, $this->setting_obj);
        }

        public function set($id, $value) {
            $this->load();
            $this->setting_obj[$id] = $value;
        }

        public function persist() {
            update_option($this->gateway_key, $this->setting_obj);
        }

        public function load() {
            if (!empty($this->setting_obj)) {
                return false;
            }
            $this->setting_obj = get_option($this->gateway_key, array());
            $defaults = array('enabled' => 'no',
                'title' => __('PayPal', 'paypal-for-woocommerce'),
                'description' => __(
                        'The easiest one-stop solution for accepting PayPal, Venmo, Debit/Credit Cards with cheaper fees than other processors!', 'paypal-for-woocommerce'
                ),
                'account_settings' => '',
                'testmode' => 'no',
                'live_onboarding' => '',
                'live_disconnect' => '',
                'sandbox_onboarding' => '',
                'sandbox_disconnect' => '',
                'api_client_id' => '',
                'api_secret' => '',
                'live_merchant_id' => '',
                'sandbox_client_id' => '',
                'sandbox_api_secret' => '',
                'sandbox_merchant_id' => '',
                'product_button_settings' => '',
                'enable_product_button' => 'yes',
                'product_disallowed_funding_methods' => '',
                'product_button_layout' => 'horizontal',
                'product_style_color' => 'gold',
                'product_style_shape' => 'rect',
                'product_button_size' => 'responsive',
                'product_button_height' => '',
                'product_button_label' => 'paypal',
                'product_button_tagline' => 'yes',
                'product_button_width' => '',
                'product_google_style_color' => 'default',
                'product_google_button_type' => 'plain',
                'product_google_button_height' => '',
                'product_apple_style_color' => 'black',
                'product_apple_button_type' => 'plain',
                'product_apple_button_height' => '',
                'cart_button_settings' => '',
                'enable_cart_button' => 'yes',
                'cart_button_position' => 'bottom',
                'cart_disallowed_funding_methods' => '',
                'cart_button_layout' => 'vertical',
                'cart_style_color' => 'gold',
                'cart_style_shape' => 'rect',
                'cart_button_size' => 'responsive',
                'cart_button_height' => '',
                'cart_button_label' => 'paypal',
                'cart_button_tagline' => 'yes',
                'cart_button_width' => '',
                'cart_google_style_color' => 'default',
                'cart_google_button_type' => 'plain',
                'cart_google_button_height' => '',
                'cart_apple_style_color' => 'black',
                'cart_apple_button_type' => 'plain',
                'cart_apple_button_height' => '',
                'checkout_button_settings' => '',
                'enable_paypal_checkout_page' => 'yes',
                'checkout_page_display_option' => 'regular',
                'checkout_disable_smart_button' => 'no',
                'checkout_disallowed_funding_methods' => '',
                'checkout_button_layout' => 'vertical',
                'checkout_style_color' => 'gold',
                'checkout_style_shape' => 'rect',
                'checkout_button_size' => 'responsive',
                'checkout_button_height' => '',
                'checkout_button_label' => 'paypal',
                'checkout_button_tagline' => 'yes',
                'checkout_button_width' => '',
                'checkout_google_style_color' => 'default',
                'checkout_google_button_type' => 'checkout',
                'checkout_google_button_height' => '',
                'checkout_apple_style_color' => 'black',
                'checkout_apple_button_type' => 'plain',
                'checkout_apple_button_height' => '',
                'mini_cart_button_settings' => '',
                'enable_mini_cart_button' => 'yes',
                'mini_cart_disallowed_funding_methods' => '',
                'mini_cart_button_layout' => 'vertical',
                'mini_cart_style_color' => 'gold',
                'mini_cart_style_shape' => 'rect',
                'mini_cart_button_size' => 'responsive',
                'mini_cart_button_height' => '',
                'mini_cart_button_label' => 'paypal',
                'mini_cart_button_tagline' => 'yes',
                'pay_later_messaging_settings' => '',
                'enabled_pay_later_messaging' => 'yes',
                'pay_later_messaging_page_type' => array
                    (
                    '0' => 'product',
                    '1' => 'cart',
                    '2' => 'payment'
                ),
                'pay_later_messaging_home_page_settings' => '',
                'pay_later_messaging_home_layout_type' => 'flex',
                'pay_later_messaging_home_text_layout_logo_type' => 'primary',
                'pay_later_messaging_home_text_layout_logo_position' => 'left',
                'pay_later_messaging_home_text_layout_text_size' => '12',
                'pay_later_messaging_home_text_layout_text_color' => 'black',
                'pay_later_messaging_home_flex_layout_color' => 'blue',
                'pay_later_messaging_home_flex_layout_ratio' => '8x1',
                'pay_later_messaging_home_shortcode' => 'no',
                'pay_later_messaging_home_preview_shortcode' => '[aepfw_bnpl_message placement="home"]',
                'pay_later_messaging_category_page_settings' => '',
                'pay_later_messaging_category_layout_type' => 'flex',
                'pay_later_messaging_category_text_layout_logo_type' => 'primary',
                'pay_later_messaging_category_text_layout_logo_position' => 'left',
                'pay_later_messaging_category_text_layout_text_size' => '12',
                'pay_later_messaging_category_text_layout_text_color' => 'black',
                'pay_later_messaging_category_flex_layout_color' => 'blue',
                'pay_later_messaging_category_flex_layout_ratio' => '8x1',
                'pay_later_messaging_category_shortcode' => 'no',
                'pay_later_messaging_category_preview_shortcode' => '[aepfw_bnpl_message placement="category"]',
                'pay_later_messaging_product_page_settings' => '',
                'pay_later_messaging_product_layout_type' => 'text',
                'pay_later_messaging_product_text_layout_logo_type' => 'primary',
                'pay_later_messaging_product_text_layout_logo_position' => 'left',
                'pay_later_messaging_product_text_layout_text_size' => '12',
                'pay_later_messaging_product_text_layout_text_color' => 'black',
                'pay_later_messaging_product_flex_layout_color' => 'blue',
                'pay_later_messaging_product_flex_layout_ratio' => '8x1',
                'pay_later_messaging_product_shortcode' => 'no',
                'pay_later_messaging_product_preview_shortcode' => '[aepfw_bnpl_message placement="product"]',
                'pay_later_messaging_cart_page_settings' => '',
                'pay_later_messaging_cart_layout_type' => 'text',
                'pay_later_messaging_cart_text_layout_logo_type' => 'primary',
                'pay_later_messaging_cart_text_layout_logo_position' => 'left',
                'pay_later_messaging_cart_text_layout_text_size' => '12',
                'pay_later_messaging_cart_text_layout_text_color' => 'black',
                'pay_later_messaging_cart_flex_layout_color' => 'blue',
                'pay_later_messaging_cart_flex_layout_ratio' => '8x1',
                'pay_later_messaging_cart_shortcode' => 'no',
                'pay_later_messaging_cart_preview_shortcode' => '[aepfw_bnpl_message placement="cart"]',
                'pay_later_messaging_payment_page_settings' => '',
                'pay_later_messaging_payment_layout_type' => 'text',
                'pay_later_messaging_payment_text_layout_logo_type' => 'primary',
                'pay_later_messaging_payment_text_layout_logo_position' => 'left',
                'pay_later_messaging_payment_text_layout_text_size' => '12',
                'pay_later_messaging_payment_text_layout_text_color' => 'black',
                'pay_later_messaging_payment_flex_layout_color' => 'blue',
                'pay_later_messaging_payment_flex_layout_ratio' => '8x1',
                'pay_later_messaging_payment_shortcode' => 'no',
                'pay_later_messaging_payment_preview_shortcode' => '[aepfw_bnpl_message placement="payment"]',
                'advanced_settings' => '',
                'paymentaction' => 'capture',
                'paymentstatus' => 'wc-default',
                'invoice_prefix' => 'WC-PPCP',
                'skip_final_review' => 'no',
                'brand_name' => 'PPCP',
                'landing_page' => 'NO_PREFERENCE',
                'payee_preferred' => 'no',
                'send_items' => 'yes',
                'enable_advanced_card_payments' => 'no',
                '3d_secure_contingency' => 'SCA_WHEN_REQUIRED',
                'advanced_card_payments_title' => 'Credit Card',
                'advanced_card_payments_display_position' => 'after',
                'disable_cards' => '',
                'cards_input_size' => '',
                'cards_input_color' => '',
                'cards_input_style' => '',
                'cards_input_weight' => '',
                'cards_input_padding' => '',
                'soft_descriptor' => 'PPCP',
                'error_email_notification' => 'yes',
                'debug' => 'everything',
                'enable_google_pay' => 'no',
                'enable_apple_pay' => 'no'
            );
            foreach ($defaults as $key => $value) {
                if (isset($this->setting_obj[$key])) {
                    continue;
                }
                $this->setting_obj[$key] = $value;
            }
            return true;
        }

        public function angelleye_ppcp_setting_fields() {
            unset($this->setting_obj);
            $this->setting_obj = array();
            $this->load();
            $this->is_sandbox = 'yes' === $this->get('testmode', 'no');
            if ($this->is_sandbox) {
                $this->merchant_id = $this->get('sandbox_merchant_id', '');
            } else {
                $this->merchant_id = $this->get('live_merchant_id', '');
            }
            $this->enable_tokenized_payments = $this->get('enable_tokenized_payments', 'no');
            $paymentaction_options = array();
            if (class_exists('Paypal_For_Woocommerce_Multi_Account_Management')) {
                $paymentaction_options = array(
                    'capture' => __('Capture', 'paypal-for-woocommerce')
                );
            } else {
                $paymentaction_options = array(
                    'capture' => __('Capture', 'paypal-for-woocommerce'),
                    'authorize' => __('Authorize', 'paypal-for-woocommerce'),
                );
            }
            $cards_list = array(
                'visa' => _x('Visa', 'Name of credit card', 'paypal-for-woocommerce'),
                'mastercard' => _x('Mastercard', 'Name of credit card', 'paypal-for-woocommerce'),
                'amex' => _x('American Express', 'Name of credit card', 'paypal-for-woocommerce'),
                'discover' => _x('Discover', 'Name of credit card', 'paypal-for-woocommerce'),
                'jcb' => _x('JCB', 'Name of credit card', 'paypal-for-woocommerce'),
                'elo' => _x('Elo', 'Name of credit card', 'paypal-for-woocommerce'),
                'hiper' => _x('Hiper', 'Name of credit card', 'paypal-for-woocommerce'),
            );
            $skip_final_review_option_not_allowed_guest_checkout = '';
            $skip_final_review_option_not_allowed_terms = '';
            $woocommerce_enable_guest_checkout = get_option('woocommerce_enable_guest_checkout');
            if (isset($woocommerce_enable_guest_checkout) && ( $woocommerce_enable_guest_checkout === "no" )) {
                $skip_final_review_option_not_allowed_guest_checkout = ' (The WooCommerce guest checkout option is disabled.  Therefore, the review page is required for login / account creation, and this option will be overridden.)';
            }
            if (apply_filters('woocommerce_checkout_show_terms', true) && function_exists('wc_terms_and_conditions_checkbox_enabled') && wc_terms_and_conditions_checkbox_enabled()) {
                $skip_final_review_option_not_allowed_terms = ' (You currently have a Terms &amp; Conditions page set, which requires the review page, and will override this option.)';
            }
            $button_height = array(
                '' => __('Default Height (Recommended)', 'paypal-for-woocommerce')
            );
            $button_width = array(
                '' => __('Default Width (Recommended)', 'paypal-for-woocommerce')
            );
            for ($i = 25; $i < 100; $i++) {
                $button_height[$i] = __($i . ' px', 'paypal-for-woocommerce');
            }
            for ($i = 160; $i < 300; $i++) {
                $button_width[$i] = __($i . ' px', 'paypal-for-woocommerce');
            }
            if (isset($_GET['section']) && 'angelleye_ppcp' === $_GET['section']) {
                if (!empty($this->merchant_id)) {
                    $available_endpoints = AngellEYE_PayPal_PPCP_Request::angelleye_ppcp_get_available_endpoints($this->merchant_id);
                } else {
                    $available_endpoints = false;
                }
            } else {
                $available_endpoints = false;
            }
            $google_pay_supported_country = angelleye_ppcp_apple_google_vault_supported_country();
            $apple_pay_supported_country = angelleye_ppcp_apple_google_vault_supported_country();
            $advanced_cc_text = '';
            $vaulting_advanced_text = '';
            $applePayText = '';
            $googlePayText = '';
            $advanced_cc_custom_attributes = array();
            $vaulting_custom_attributes = array();
            $this->is_paypal_vault_enable = false;
            $this->is_apple_pay_enable = false;
            $this->is_google_pay_enable = false;
            $this->is_ppcp_connected = !empty($this->merchant_id);
            $region = wc_get_base_location();
            $default_country = $region['country'];

            if ($available_endpoints === false) {
                
            } elseif (!isset($available_endpoints['advanced_cc'])) {
                $advanced_cc_text = sprintf(__('The Advanced Credit Cards feature is not yet active on your PayPal account. Please <a href="%s">return to the PayPal Connect screen</a> to apply for this feature and get cheaper rates.', 'paypal-for-woocommerce'), admin_url('options-general.php?page=paypal-for-woocommerce'));
                $advanced_cc_custom_attributes = array('disabled' => 'disabled');
            }
            if ($available_endpoints === false) {
                $vaulting_advanced_text = __('Allow buyers to securely save payment details to their account. This enables features like Subscriptions, Auto-Ship, and token payments of any kind.', 'paypal-for-woocommerce');
                $this->need_to_display_paypal_vault_onboard_button = false;
                $this->is_paypal_vault_enable = false;
            } elseif (!isset($available_endpoints['vaulting_advanced'])) {
                $vaulting_advanced_text = __('The Vault functionality required for this feature is not enabled on your PayPal account.', 'paypal-for-woocommerce');
                $vaulting_custom_attributes = array('disabled' => 'disabled');
                $this->need_to_display_paypal_vault_onboard_button = true;
                $this->is_paypal_vault_enable = false;
            }
            if (isset($available_endpoints['vaulting_advanced'])) {
                $this->is_paypal_vault_enable = true;
                $vaulting_advanced_text = __('The Vault / Subscriptions feature is enabled on your PayPal account.  You need to enable Tokenized Payments here in order this to be available on your site.', 'paypal-for-woocommerce');
            }
            if ($available_endpoints === false) {
                $applePayText = __('Allow buyers to pay using Apple Pay.', 'paypal-for-woocommerce');
                $this->need_to_display_apple_pay_button = false;
                $this->is_apple_pay_enable = false;
            } elseif (!isset($available_endpoints['apple_pay'])) {
                $applePayText = __('Apple Pay is not enabled on your PayPal account.', 'paypal-for-woocommerce');
                $this->need_to_display_apple_pay_button = in_array($default_country, $apple_pay_supported_country);
                $this->is_apple_pay_enable = true;
            } elseif (isset($available_endpoints['apple_pay'])) {
                $this->is_apple_pay_enable = true;
                $this->is_apple_pay_approved = true; //$available_endpoints['apple_pay'] == 'APPROVED';
                $applePayText = __('Apple Pay feature is enabled on your PayPal account.', 'paypal-for-woocommerce');
            }
            $apple_pay_custom_attributes = $this->is_apple_pay_approved ? [] : array('disabled' => 'disabled');

            if ($available_endpoints === false) {
                $googlePayText = __('Allow buyers to pay using Google Pay.', 'paypal-for-woocommerce');
                $this->need_to_display_google_pay_button = false;
                $this->is_google_pay_enable = false;
            } elseif (!isset($available_endpoints['google_pay'])) {
                $googlePayText = __('Google Pay is not enabled on your PayPal account.', 'paypal-for-woocommerce');
                $this->need_to_display_google_pay_button = in_array($default_country, $google_pay_supported_country);
                $this->is_google_pay_enable = true;
            } elseif (isset($available_endpoints['google_pay'])) {
                $this->is_google_pay_enable = true;
                $this->is_google_pay_approved = true; //$available_endpoints['apple_pay'] == 'APPROVED';
                $googlePayText = __('Google Pay feature is enabled on your PayPal account.', 'paypal-for-woocommerce');
            }
            $google_pay_custom_attributes = $this->is_google_pay_approved ? [] : array('disabled' => 'disabled');
            $applePayBtnTypes = [
                'plain' => __('Plain (Recommended)', 'paypal-for-woocommerce'),
                'buy' => __('Buy', 'paypal-for-woocommerce'),
                'donate' => __('Donate', 'paypal-for-woocommerce'),
                'book' => __('Book', 'paypal-for-woocommerce'),
                'check-out' => __('Checkout', 'paypal-for-woocommerce'),
                'subscribe' => __('Subscribe', 'paypal-for-woocommerce'),
                'contribute' => __('Contribute', 'paypal-for-woocommerce'),
                'order' => __('Order', 'paypal-for-woocommerce'),
                'reload' => __('Reload', 'paypal-for-woocommerce'),
                'rent' => __('Rent', 'paypal-for-woocommerce'),
                'support' => __('Support', 'paypal-for-woocommerce'),
                'tip' => __('Tip', 'paypal-for-woocommerce'),
                'top-up' => __('Top Up', 'paypal-for-woocommerce'),
                'continue' => __('Continue', 'paypal-for-woocommerce'),
            ];
            $woo_pre_order_payment_mode = array();
            $woo_pre_order_payment_mode['authorize'] = __('Authorize / Capture', 'paypal-for-woocommerce');
            if ($this->is_paypal_vault_enable) {
                $woo_pre_order_payment_mode['vault'] = __('PayPal Vault (Recommended)', 'paypal-for-woocommerce');
            }

            $this->angelleye_ppcp_gateway_setting = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'label' => __(sprintf('%s', AE_PPCP_NAME), 'paypal-for-woocommerce'),
                    'default' => 'no',
                ),
                'title' => array(
                    'title' => __('Title', 'paypal-for-woocommerce'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'paypal-for-woocommerce'),
                    'default' => __('PayPal', 'paypal-for-woocommerce'),
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
                    'title' => __('PayPal Account Settings', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'description' => '',
                    'class' => 'ppcp_separator_heading',
                ),
                'testmode' => array(
                    'title' => __('PayPal Sandbox', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable PayPal Sandbox', 'paypal-for-woocommerce'),
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
                    'title' => __('PayPal Connection', 'paypal-for-woocommerce'),
                    'type' => 'angelleye_ppcp_text',
                    'mode' => 'live',
                    'description' => __('Click to reset current credentials and use another account.', 'paypal-for-woocommerce'),
                    'desc_tip' => '',
                ),
                'sandbox_onboarding' => array(
                    'title' => __('Connect to PayPal Sandbox', 'paypal-for-woocommerce'),
                    'type' => 'angelleye_ppcp_onboarding',
                    'gateway' => 'angelleye_ppcp',
                    'mode' => 'sandbox',
                    'description' => __('Setup or link an existing PayPal account.', 'paypal-for-woocommerce'),
                    'desc_tip' => ''
                ),
                'sandbox_disconnect' => array(
                    'title' => __('PayPal Connection', 'paypal-for-woocommerce'),
                    'type' => 'angelleye_ppcp_text',
                    'mode' => 'sandbox',
                    'description' => __('Click to reset current credentials and use another account.', 'paypal-for-woocommerce'),
                    'desc_tip' => ''
                ),
                'api_client_id' => array(
                    'title' => __('PayPal Client ID', 'paypal-for-woocommerce'),
                    'type' => 'password',
                    'description' => __('Enter your PayPal Client ID.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true
                ),
                'api_secret' => array(
                    'title' => __('PayPal Secret', 'paypal-for-woocommerce'),
                    'type' => 'password',
                    'description' => __('Enter your PayPal Secret.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true
                ),
                'live_merchant_id' => array(
                    'title' => __('Live Merchant ID', 'paypal-for-woocommerce'),
                    'type' => 'text',
                    'description' => '',
                    'default' => '',
                    'custom_attributes' => array('readonly' => 'readonly'),
                    'desc_tip' => true
                ),
                'sandbox_client_id' => array(
                    'title' => __('Sandbox Client ID', 'paypal-for-woocommerce'),
                    'type' => 'password',
                    'description' => __('Enter your PayPal Sandbox Client ID.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true
                ),
                'sandbox_api_secret' => array(
                    'title' => __('Sandbox Secret', 'paypal-for-woocommerce'),
                    'type' => 'password',
                    'description' => __('Enter your PayPal Sandbox Secret.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true
                ),
                'sandbox_merchant_id' => array(
                    'title' => __('Sandbox Merchant ID', 'paypal-for-woocommerce'),
                    'type' => 'text',
                    'description' => '',
                    'default' => '',
                    'custom_attributes' => array('readonly' => 'readonly'),
                    'desc_tip' => true
                ),
                'product_button_settings' => array(
                    'title' => __('Product Page Settings', 'paypal-for-woocommerce'),
                    'description' => __('Enable the Product specific button settings, and the options set will be applied to the PayPal Smart buttons on your Product pages.', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'class' => 'ppcp_separator_heading',
                ),
                'enable_product_button' => array(
                    'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                    'class' => '',
                    'type' => 'checkbox',
                    'label' => __('Enable PayPal Smart Button on the Product Pages.', 'paypal-for-woocommerce'),
                    'default' => 'yes',
                    'desc_tip' => true,
                    'description' => __('', 'paypal-for-woocommerce'),
                ),
                'product_disallowed_funding_methods' => array(
                    'title' => __('Hide Funding Method(s)', 'paypal-for-woocommerce'),
                    'type' => 'multiselect',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Funding methods selected here will be hidden from buyers during checkout.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                    'options' => array(
                        'card' => __('Credit or Debit Card', 'paypal-for-woocommerce'),
                        'credit' => __('PayPal Credit', 'paypal-for-woocommerce'),
                        'paylater' => __('Pay Later', 'paypal-for-woocommerce'),
                        'bancontact' => __('Bancontact', 'paypal-for-woocommerce'),
                        'blik' => __('BLIK', 'paypal-for-woocommerce'),
                        'eps' => __('eps', 'paypal-for-woocommerce'),
                        'giropay' => __('giropay', 'paypal-for-woocommerce'),
                        'ideal' => __('iDEAL', 'paypal-for-woocommerce'),
                        'mercadopago' => __('Mercado Pago', 'paypal-for-woocommerce'),
                        'mybank' => __('MyBank', 'paypal-for-woocommerce'),
                        'p24' => __('Przelewy24', 'paypal-for-woocommerce'),
                        'sepa' => __('SEPA-Lastschrift', 'paypal-for-woocommerce'),
                        'sofort' => __('Sofort', 'paypal-for-woocommerce'),
                        'venmo' => __('Venmo', 'paypal-for-woocommerce')
                    ),
                ),
                'product_button_layout' => array(
                    'title' => __('Button Layout', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Select Vertical for stacked buttons, and Horizontal for side-by-side buttons.', 'paypal-for-woocommerce'),
                    'default' => 'horizontal',
                    'desc_tip' => true,
                    'options' => array(
                        'horizontal' => __('Horizontal (Recommended)', 'paypal-for-woocommerce'),
                        'vertical' => __('Vertical', 'paypal-for-woocommerce')
                    ),
                ),
                'product_style_color' => array(
                    'title' => __('Button Color', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the color you would like to use for the PayPal button.', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'default' => 'gold',
                    'options' => array(
                        'gold' => __('Gold (Recommended)', 'paypal-for-woocommerce'),
                        'blue' => __('Blue', 'paypal-for-woocommerce'),
                        'silver' => __('Silver', 'paypal-for-woocommerce'),
                        'white' => __('White', 'paypal-for-woocommerce'),
                        'black' => __('Black', 'paypal-for-woocommerce')
                    ),
                ),
                'product_style_shape' => array(
                    'title' => __('Button Shape', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the shape you would like to use for the buttons.', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'default' => 'rect',
                    'options' => array(
                        'rect' => __('Rect (Recommended)', 'paypal-for-woocommerce'),
                        'pill' => __('Pill', 'paypal-for-woocommerce')
                    ),
                ),
                'product_button_size' => array(
                    'title' => __('Button Size', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the size of the buttons you would like displayed. Responsive will fit to the current element on the page.', 'paypal-for-woocommerce'),
                    'default' => 'responsive',
                    'desc_tip' => true,
                    'options' => array(
                        'small' => __('Small', 'paypal-for-woocommerce'),
                        'medium' => __('Medium', 'paypal-for-woocommerce'),
                        'large' => __('Large', 'paypal-for-woocommerce'),
                        'responsive' => __('Responsive (Recommended)', 'paypal-for-woocommerce'),
                    ),
                ),
                'product_button_height' => array(
                    'title' => __('Button Height', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the height of the buttons you would like displayed.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                    'options' => $button_height,
                ),
                'product_button_label' => array(
                    'title' => __('Button Label', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the label type you would like to use for the PayPal button.', 'paypal-for-woocommerce'),
                    'default' => 'paypal',
                    'desc_tip' => true,
                    'options' => array(
                        'paypal' => __('PayPal (Recommended)', 'paypal-for-woocommerce'),
                        'checkout' => __('Checkout', 'paypal-for-woocommerce'),
                        'buynow' => __('Buy Now', 'paypal-for-woocommerce'),
                        'pay' => __('Pay', 'paypal-for-woocommerce'),
                    ),
                ),
                'product_button_tagline' => array(
                    'title' => __('Tagline', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'class' => 'angelleye_ppcp_product_button_settings',
                    'default' => 'yes',
                    'label' => __('Enable tagline', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'description' => __(
                            'Add the tagline. This line will only show up, if you select a horizontal layout.', 'paypal-for-woocommerce'
                    ),
                ),
                'product_button_width' => array(
                    'title' => __('Buttons Width', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the width of the buttons (e.g. PayPal, Apple Pay, Google Pay) you would like displayed.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                    'options' => $button_width,
                ),
                'product_google_style_color' => array(
                    'title' => __('Google Pay Button Color', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the color you would like to use for the Google Pay button.', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'default' => 'default',
                    'options' => array(
                        'default' => __('Default (Recommended)', 'paypal-for-woocommerce'),
                        'black' => __('Black', 'paypal-for-woocommerce'),
                        'white' => __('White', 'paypal-for-woocommerce'),
                    ),
                ),
                'product_google_button_type' => array(
                    'title' => __('Google Pay Button Type', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the color you would like to use for the Google Pay button.', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'default' => 'default',
                    'options' => array(
                        'plain' => __('Plain (Recommended)', 'paypal-for-woocommerce'),
                        'book' => __('Book', 'paypal-for-woocommerce'),
                        'buy' => __('Buy', 'paypal-for-woocommerce'),
                        'checkout' => __('Checkout', 'paypal-for-woocommerce'),
                        'donate' => __('Donate', 'paypal-for-woocommerce'),
                        'order' => __('Order', 'paypal-for-woocommerce'),
                        'pay' => __('Pay', 'paypal-for-woocommerce'),
                        'subscribe' => __('Subscribe', 'paypal-for-woocommerce'),
                    ),
                ),
                'product_google_button_height' => array(
                    'title' => __('Google Pay Button Height', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the height of the Google Pay button you would like displayed.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                    'options' => $button_height,
                ),
                'product_apple_style_color' => array(
                    'title' => __('Apple Pay Button Color', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the color you would like to use for the Apple Pay button.', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'default' => 'default',
                    'options' => array(
                        'black' => __('Black (Recommended)', 'paypal-for-woocommerce'),
                        'white' => __('White', 'paypal-for-woocommerce'),
                        'white-outline' => __('White Outline', 'paypal-for-woocommerce'),
                    ),
                ),
                'product_apple_button_type' => array(
                    'title' => __('Apple Pay Button Type', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the color you would like to use for the Apple Pay button.', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'default' => 'plain',
                    'options' => $applePayBtnTypes,
                ),
                'product_apple_button_height' => array(
                    'title' => __('Apple Pay Button Height', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the height of the Apple Pay button you would like displayed.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                    'options' => $button_height,
                ),
                'cart_button_settings' => array(
                    'title' => __('Cart Page Settings', 'paypal-for-woocommerce'),
                    'description' => __('Enable the Cart specific button settings, and the options set will be applied to the PayPal buttons on your Cart page.', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'class' => 'ppcp_separator_heading',
                ),
                'enable_cart_button' => array(
                    'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                    'class' => '',
                    'type' => 'checkbox',
                    'label' => __('Enable PayPal Smart Button on the Cart page.', 'paypal-for-woocommerce'),
                    'default' => 'yes',
                    'desc_tip' => true,
                    'description' => __('', 'paypal-for-woocommerce'),
                ),
                'cart_button_position' => array(
                    'title' => __('Cart Button Position', 'paypal-for-woocommerce'),
                    'label' => __('Where to display PayPal Smart button(s).', 'paypal-for-woocommerce'),
                    'description' => __('Set where to display the PayPal Smart button(s).'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_cart_button_settings',
                    'options' => array(
                        'top' => 'At the top, above the shopping cart details.',
                        'bottom' => 'At the bottom, below the shopping cart details.',
                        'both' => 'Both at the top and bottom, above and below the shopping cart details.'
                    ),
                    'default' => 'bottom',
                    'desc_tip' => true,
                ),
                'cart_disallowed_funding_methods' => array(
                    'title' => __('Hide Funding Method(s)', 'paypal-for-woocommerce'),
                    'type' => 'multiselect',
                    'class' => 'wc-enhanced-select angelleye_ppcp_cart_button_settings',
                    'description' => __('Funding methods selected here will be hidden from buyers during checkout.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                    'options' => array(
                        'card' => __('Credit or Debit Card', 'paypal-for-woocommerce'),
                        'credit' => __('PayPal Credit', 'paypal-for-woocommerce'),
                        'paylater' => __('Pay Later', 'paypal-for-woocommerce'),
                        'bancontact' => __('Bancontact', 'paypal-for-woocommerce'),
                        'blik' => __('BLIK', 'paypal-for-woocommerce'),
                        'eps' => __('eps', 'paypal-for-woocommerce'),
                        'giropay' => __('giropay', 'paypal-for-woocommerce'),
                        'ideal' => __('iDEAL', 'paypal-for-woocommerce'),
                        'mercadopago' => __('Mercado Pago', 'paypal-for-woocommerce'),
                        'mybank' => __('MyBank', 'paypal-for-woocommerce'),
                        'p24' => __('Przelewy24', 'paypal-for-woocommerce'),
                        'sepa' => __('SEPA-Lastschrift', 'paypal-for-woocommerce'),
                        'sofort' => __('Sofort', 'paypal-for-woocommerce'),
                        'venmo' => __('Venmo', 'paypal-for-woocommerce')
                    ),
                ),
                'cart_button_layout' => array(
                    'title' => __('Button Layout', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_cart_button_settings',
                    'description' => __('Select Vertical for stacked buttons, and Horizontal for side-by-side buttons.', 'paypal-for-woocommerce'),
                    'default' => 'vertical',
                    'desc_tip' => true,
                    'options' => array(
                        'vertical' => __('Vertical (Recommended)', 'paypal-for-woocommerce'),
                        'horizontal' => __('Horizontal', 'paypal-for-woocommerce'),
                    ),
                ),
                'cart_style_color' => array(
                    'title' => __('Button Color', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_cart_button_settings',
                    'description' => __('Set the color you would like to use for the PayPal button.', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'default' => 'gold',
                    'options' => array(
                        'gold' => __('Gold (Recommended)', 'paypal-for-woocommerce'),
                        'blue' => __('Blue', 'paypal-for-woocommerce'),
                        'silver' => __('Silver', 'paypal-for-woocommerce'),
                        'white' => __('White', 'paypal-for-woocommerce'),
                        'black' => __('Black', 'paypal-for-woocommerce')
                    ),
                ),
                'cart_style_shape' => array(
                    'title' => __('Button Shape', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_cart_button_settings',
                    'description' => __('Set the shape you would like to use for the buttons.', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'default' => 'rect',
                    'options' => array(
                        'rect' => __('Rect (Recommended)', 'paypal-for-woocommerce'),
                        'pill' => __('Pill', 'paypal-for-woocommerce')
                    ),
                ),
                'cart_button_size' => array(
                    'title' => __('Button Size', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_cart_button_settings',
                    'description' => __('Set the size of the buttons you would like displayed. Responsive will fit to the current element on the page.', 'paypal-for-woocommerce'),
                    'default' => 'responsive',
                    'desc_tip' => true,
                    'options' => array(
                        'small' => __('Small', 'paypal-for-woocommerce'),
                        'medium' => __('Medium', 'paypal-for-woocommerce'),
                        'large' => __('Large', 'paypal-for-woocommerce'),
                        'responsive' => __('Responsive (Recommended)', 'paypal-for-woocommerce'),
                    ),
                ),
                'cart_button_height' => array(
                    'title' => __('Button Height', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_cart_button_settings',
                    'description' => __('Set the height of the buttons you would like displayed.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                    'options' => $button_height,
                ),
                'cart_button_label' => array(
                    'title' => __('Button Label', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_cart_button_settings',
                    'description' => __('Set the label type you would like to use for the PayPal button.', 'paypal-for-woocommerce'),
                    'default' => 'paypal',
                    'desc_tip' => true,
                    'options' => array(
                        'paypal' => __('PayPal (Recommended)', 'paypal-for-woocommerce'),
                        'checkout' => __('Checkout', 'paypal-for-woocommerce'),
                        'buynow' => __('Buy Now', 'paypal-for-woocommerce'),
                        'pay' => __('Pay', 'paypal-for-woocommerce'),
                    ),
                ),
                'cart_button_tagline' => array(
                    'title' => __('Tagline', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'class' => 'angelleye_ppcp_cart_button_settings',
                    'default' => 'yes',
                    'label' => __('Enable tagline', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'description' => __(
                            'Add the tagline. This line will only show up, if you select a horizontal layout.', 'paypal-for-woocommerce'
                    ),
                ),
                'cart_button_width' => array(
                    'title' => __('Buttons Width', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the width of the buttons (e.g. PayPal, Apple Pay, Google Pay) you would like displayed.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                    'options' => $button_width,
                ),
                'cart_google_style_color' => array(
                    'title' => __('Google Pay Button Color', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the color you would like to use for the Google Pay button.', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'default' => 'default',
                    'options' => array(
                        'default' => __('Default (Recommended)', 'paypal-for-woocommerce'),
                        'black' => __('Black', 'paypal-for-woocommerce'),
                        'white' => __('White', 'paypal-for-woocommerce'),
                    ),
                ),
                'cart_google_button_type' => array(
                    'title' => __('Google Pay Button Type', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the color you would like to use for the Google Pay button.', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'default' => 'default',
                    'options' => array(
                        'plain' => __('Plain (Recommended)', 'paypal-for-woocommerce'),
                        'book' => __('Book', 'paypal-for-woocommerce'),
                        'buy' => __('Buy', 'paypal-for-woocommerce'),
                        'checkout' => __('Checkout', 'paypal-for-woocommerce'),
                        'donate' => __('Donate', 'paypal-for-woocommerce'),
                        'order' => __('Order', 'paypal-for-woocommerce'),
                        'pay' => __('Pay', 'paypal-for-woocommerce'),
                        'subscribe' => __('Subscribe', 'paypal-for-woocommerce'),
                    ),
                ),
                'cart_google_button_height' => array(
                    'title' => __('Google Pay Button Height', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the height of the Google Pay button you would like displayed.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                    'options' => $button_height,
                ),
                'cart_apple_style_color' => array(
                    'title' => __('Apple Pay Button Color', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the color you would like to use for the Apple Pay button.', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'default' => 'default',
                    'options' => array(
                        'black' => __('Black (Recommended)', 'paypal-for-woocommerce'),
                        'white' => __('White', 'paypal-for-woocommerce'),
                        'white-outline' => __('White Outline', 'paypal-for-woocommerce'),
                    ),
                ),
                'cart_apple_button_type' => array(
                    'title' => __('Apple Pay Button Type', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the color you would like to use for the Apple Pay button.', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'default' => 'plain',
                    'options' => $applePayBtnTypes,
                ),
                'cart_apple_button_height' => array(
                    'title' => __('Apple Pay Button Height', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the height of the Apple Pay button you would like displayed.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                    'options' => $button_height,
                ),
                'checkout_button_settings' => array(
                    'title' => __('Checkout Page Settings', 'paypal-for-woocommerce'),
                    'description' => __('Enable the checkout specific button settings, and the options set will be applied to the PayPal buttons on your checkout page.', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'class' => 'ppcp_separator_heading',
                ),
                'enable_paypal_checkout_page' => array(
                    'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                    'class' => '',
                    'type' => 'checkbox',
                    'label' => __('Enable PayPal Payments on the Checkout page.', 'paypal-for-woocommerce'),
                    'default' => 'yes',
                    'desc_tip' => true,
                    'description' => __('if this option is disable, PayPal will be not display in checkout page.', 'paypal-for-woocommerce'),
                ),
                'checkout_page_display_option' => array(
                    'title' => __('Checkout Page Display', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'options' => array(
                        'top' => __('Display at the top of the checkout page.', 'paypal-for-woocommerce'),
                        'regular' => __('Display in general list of enabled gateways on checkout page.', 'paypal-for-woocommerce'),
                        'both' => __('Display both at the top and in the general list of gateways on the checkout page.')),
                    'default' => 'regular',
                    'class' => 'wc-enhanced-select',
                    'description' => __('Displaying the checkout button at the top of the checkout page will allow users to skip filling out the forms and can potentially increase conversion rates.'),
                    'desc_tip' => true,
                    'css' => 'min-width: 440px;',
                ),
                'checkout_disable_smart_button' => array(
                    'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                    'class' => '',
                    'type' => 'checkbox',
                    'label' => __('Disable smart buttons in the regular list of payment gateways.', 'paypal-for-woocommerce'),
                    'default' => 'no',
                    'desc_tip' => true,
                    'description' => __('', 'paypal-for-woocommerce'),
                ),
                'checkout_disallowed_funding_methods' => array(
                    'title' => __('Hide Funding Method(s)', 'paypal-for-woocommerce'),
                    'type' => 'multiselect',
                    'class' => 'wc-enhanced-select angelleye_ppcp_checkout_button_settings',
                    'description' => __('Funding methods selected here will be hidden from buyers during checkout.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                    'options' => array(
                        'card' => __('Credit or Debit Card', 'paypal-for-woocommerce'),
                        'credit' => __('PayPal Credit', 'paypal-for-woocommerce'),
                        'paylater' => __('Pay Later', 'paypal-for-woocommerce'),
                        'bancontact' => __('Bancontact', 'paypal-for-woocommerce'),
                        'blik' => __('BLIK', 'paypal-for-woocommerce'),
                        'eps' => __('eps', 'paypal-for-woocommerce'),
                        'giropay' => __('giropay', 'paypal-for-woocommerce'),
                        'ideal' => __('iDEAL', 'paypal-for-woocommerce'),
                        'mercadopago' => __('Mercado Pago', 'paypal-for-woocommerce'),
                        'mybank' => __('MyBank', 'paypal-for-woocommerce'),
                        'p24' => __('Przelewy24', 'paypal-for-woocommerce'),
                        'sepa' => __('SEPA-Lastschrift', 'paypal-for-woocommerce'),
                        'sofort' => __('Sofort', 'paypal-for-woocommerce'),
                        'venmo' => __('Venmo', 'paypal-for-woocommerce')
                    ),
                ),
                'checkout_button_layout' => array(
                    'title' => __('Button Layout', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_checkout_button_settings',
                    'description' => __('Select Vertical for stacked buttons, and Horizontal for side-by-side buttons.', 'paypal-for-woocommerce'),
                    'default' => 'vertical',
                    'desc_tip' => true,
                    'options' => array(
                        'vertical' => __('Vertical (Recommended)', 'paypal-for-woocommerce'),
                        'horizontal' => __('Horizontal', 'paypal-for-woocommerce'),
                    ),
                ),
                'checkout_style_color' => array(
                    'title' => __('Button Color', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_checkout_button_settings',
                    'description' => __('Set the color you would like to use for the PayPal button.', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'default' => 'gold',
                    'options' => array(
                        'gold' => __('Gold (Recommended)', 'paypal-for-woocommerce'),
                        'blue' => __('Blue', 'paypal-for-woocommerce'),
                        'silver' => __('Silver', 'paypal-for-woocommerce'),
                        'white' => __('White', 'paypal-for-woocommerce'),
                        'black' => __('Black', 'paypal-for-woocommerce')
                    ),
                ),
                'checkout_style_shape' => array(
                    'title' => __('Button Shape', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_checkout_button_settings',
                    'description' => __('Set the shape you would like to use for the buttons.', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'default' => 'rect',
                    'options' => array(
                        'rect' => __('Rect (Recommended)', 'paypal-for-woocommerce'),
                        'pill' => __('Pill', 'paypal-for-woocommerce')
                    ),
                ),
                'checkout_button_size' => array(
                    'title' => __('Button Size', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_checkout_button_settings',
                    'description' => __('Set the size of the buttons you would like displayed. Responsive will fit to the current element on the page.', 'paypal-for-woocommerce'),
                    'default' => 'responsive',
                    'desc_tip' => true,
                    'options' => array(
                        'small' => __('Small', 'paypal-for-woocommerce'),
                        'medium' => __('Medium', 'paypal-for-woocommerce'),
                        'large' => __('Large', 'paypal-for-woocommerce'),
                        'responsive' => __('Responsive (Recommended)', 'paypal-for-woocommerce'),
                    ),
                ),
                'checkout_button_height' => array(
                    'title' => __('Button Height', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_checkout_button_settings',
                    'description' => __('Set the height of the buttons you would like displayed.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                    'options' => $button_height,
                ),
                'checkout_button_label' => array(
                    'title' => __('Button Label', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_checkout_button_settings',
                    'description' => __('Set the label type you would like to use for the PayPal button.', 'paypal-for-woocommerce'),
                    'default' => 'paypal',
                    'desc_tip' => true,
                    'options' => array(
                        'paypal' => __('PayPal', 'paypal-for-woocommerce'),
                        'checkout' => __('Checkout', 'paypal-for-woocommerce'),
                        'buynow' => __('Buy Now', 'paypal-for-woocommerce'),
                        'pay' => __('Pay', 'paypal-for-woocommerce'),
                    ),
                ),
                'checkout_button_tagline' => array(
                    'title' => __('Tagline', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'class' => 'angelleye_ppcp_checkout_button_settings',
                    'default' => 'yes',
                    'label' => __('Enable tagline', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'description' => __(
                            'Add the tagline. This line will only show up, if you select a horizontal layout.', 'paypal-for-woocommerce'
                    ),
                ),
                'checkout_button_width' => array(
                    'title' => __('Buttons Width', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the width of the buttons (e.g. PayPal, Apple Pay, Google Pay) you would like displayed.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                    'options' => $button_width,
                ),
                'checkout_google_style_color' => array(
                    'title' => __('Google Pay Button Color', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the color you would like to use for the Google Pay button.', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'default' => 'default',
                    'options' => array(
                        'default' => __('Default (Recommended)', 'paypal-for-woocommerce'),
                        'black' => __('Black', 'paypal-for-woocommerce'),
                        'white' => __('White', 'paypal-for-woocommerce'),
                    ),
                ),
                'checkout_google_button_type' => array(
                    'title' => __('Google Pay Button Type', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the color you would like to use for the Google Pay button.', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'default' => 'checkout',
                    'options' => array(
                        'plain' => __('Plain (Recommended)', 'paypal-for-woocommerce'),
                        'book' => __('Book', 'paypal-for-woocommerce'),
                        'buy' => __('Buy', 'paypal-for-woocommerce'),
                        'checkout' => __('Checkout', 'paypal-for-woocommerce'),
                        'donate' => __('Donate', 'paypal-for-woocommerce'),
                        'order' => __('Order', 'paypal-for-woocommerce'),
                        'pay' => __('Pay', 'paypal-for-woocommerce'),
                        'subscribe' => __('Subscribe', 'paypal-for-woocommerce'),
                    ),
                ),
                'checkout_google_button_height' => array(
                    'title' => __('Google Pay Button Height', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the height of the Google Pay button you would like displayed.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                    'options' => $button_height,
                ),
                'checkout_apple_style_color' => array(
                    'title' => __('Apple Pay Button Color', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the color you would like to use for the Apple Pay button.', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'default' => 'default',
                    'options' => array(
                        'black' => __('Black (Recommended)', 'paypal-for-woocommerce'),
                        'white' => __('White', 'paypal-for-woocommerce'),
                        'white-outline' => __('White Outline', 'paypal-for-woocommerce'),
                    ),
                ),
                'checkout_apple_button_type' => array(
                    'title' => __('Apple Pay Button Type', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the color you would like to use for the Apple Pay button.', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'default' => 'plain',
                    'options' => $applePayBtnTypes,
                ),
                'checkout_apple_button_height' => array(
                    'title' => __('Apple Pay Button Height', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Set the height of the Apple Pay button you would like displayed.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                    'options' => $button_height,
                ),
                'mini_cart_button_settings' => array(
                    'title' => __('Mini Cart Page Settings', 'paypal-for-woocommerce'),
                    'description' => __('Enable the Mini Cart specific button settings, and the options set will be applied to the PayPal buttons on your Mini Cart page.', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'class' => 'ppcp_separator_heading',
                ),
                'enable_mini_cart_button' => array(
                    'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                    'class' => '',
                    'type' => 'checkbox',
                    'label' => __('Enable PayPal Smart Button on the Mini Cart page.', 'paypal-for-woocommerce'),
                    'default' => 'yes',
                    'desc_tip' => true,
                    'description' => __('', 'paypal-for-woocommerce'),
                ),
                'mini_cart_disallowed_funding_methods' => array(
                    'title' => __('Hide Funding Method(s)', 'paypal-for-woocommerce'),
                    'type' => 'multiselect',
                    'class' => 'wc-enhanced-select angelleye_ppcp_mini_cart_button_settings',
                    'description' => __('Funding methods selected here will be hidden from buyers during checkout.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                    'options' => array(
                        'card' => __('Credit or Debit Card', 'paypal-for-woocommerce'),
                        'credit' => __('PayPal Credit', 'paypal-for-woocommerce'),
                        'paylater' => __('Pay Later', 'paypal-for-woocommerce'),
                        'bancontact' => __('Bancontact', 'paypal-for-woocommerce'),
                        'blik' => __('BLIK', 'paypal-for-woocommerce'),
                        'eps' => __('eps', 'paypal-for-woocommerce'),
                        'giropay' => __('giropay', 'paypal-for-woocommerce'),
                        'ideal' => __('iDEAL', 'paypal-for-woocommerce'),
                        'mercadopago' => __('Mercado Pago', 'paypal-for-woocommerce'),
                        'mybank' => __('MyBank', 'paypal-for-woocommerce'),
                        'p24' => __('Przelewy24', 'paypal-for-woocommerce'),
                        'sepa' => __('SEPA-Lastschrift', 'paypal-for-woocommerce'),
                        'sofort' => __('Sofort', 'paypal-for-woocommerce'),
                        'venmo' => __('Venmo', 'paypal-for-woocommerce')
                    ),
                ),
                'mini_cart_button_layout' => array(
                    'title' => __('Button Layout', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_mini_cart_button_settings',
                    'description' => __('Select Vertical for stacked buttons, and Horizontal for side-by-side buttons.', 'paypal-for-woocommerce'),
                    'default' => 'vertical',
                    'desc_tip' => true,
                    'options' => array(
                        'vertical' => __('Vertical (Recommended)', 'paypal-for-woocommerce'),
                        'horizontal' => __('Horizontal', 'paypal-for-woocommerce'),
                    ),
                ),
                'mini_cart_style_color' => array(
                    'title' => __('Button Color', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_mini_cart_button_settings',
                    'description' => __('Set the color you would like to use for the PayPal button.', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'default' => 'gold',
                    'options' => array(
                        'gold' => __('Gold (Recommended)', 'paypal-for-woocommerce'),
                        'blue' => __('Blue', 'paypal-for-woocommerce'),
                        'silver' => __('Silver', 'paypal-for-woocommerce'),
                        'white' => __('White', 'paypal-for-woocommerce'),
                        'black' => __('Black', 'paypal-for-woocommerce')
                    ),
                ),
                'mini_cart_style_shape' => array(
                    'title' => __('Button Shape', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_mini_cart_button_settings',
                    'description' => __('Set the shape you would like to use for the buttons.', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'default' => 'rect',
                    'options' => array(
                        'rect' => __('Rect (Recommended)', 'paypal-for-woocommerce'),
                        'pill' => __('Pill', 'paypal-for-woocommerce')
                    ),
                ),
                'mini_cart_button_size' => array(
                    'title' => __('Button Size', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_mini_cart_button_settings',
                    'description' => __('Set the size of the buttons you would like displayed. Responsive will fit to the current element on the page.', 'paypal-for-woocommerce'),
                    'default' => 'responsive',
                    'desc_tip' => true,
                    'options' => array(
                        'small' => __('Small', 'paypal-for-woocommerce'),
                        'medium' => __('Medium', 'paypal-for-woocommerce'),
                        'large' => __('Large', 'paypal-for-woocommerce'),
                        'responsive' => __('Responsive (Recommended)', 'paypal-for-woocommerce'),
                    ),
                ),
                'mini_cart_button_height' => array(
                    'title' => __('Button Height', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_mini_cart_button_settings',
                    'description' => __('Set the height of the buttons you would like displayed.', 'paypal-for-woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                    'options' => $button_height,
                ),
                'mini_cart_button_label' => array(
                    'title' => __('Button Label', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select angelleye_ppcp_mini_cart_button_settings',
                    'description' => __('Set the label type you would like to use for the PayPal button.', 'paypal-for-woocommerce'),
                    'default' => 'mini_cart',
                    'desc_tip' => true,
                    'options' => array(
                        'paypal' => __('PayPal (Recommended)', 'paypal-for-woocommerce'),
                        'checkout' => __('Checkout', 'paypal-for-woocommerce'),
                        'buynow' => __('Buy Now', 'paypal-for-woocommerce'),
                        'pay' => __('Pay', 'paypal-for-woocommerce'),
                    ),
                ),
                'mini_cart_button_tagline' => array(
                    'title' => __('Tagline', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'class' => 'angelleye_ppcp_mini_cart_button_settings',
                    'default' => 'yes',
                    'label' => __('Enable tagline', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'description' => __(
                            'Add the tagline. This line will only show up, if you select a horizontal layout.', 'paypal-for-woocommerce'
                    ),
                ),
                'pay_later_messaging_settings' => array(
                    'title' => __('Pay Later Messaging Settings', 'paypal-for-woocommerce'),
                    'description' => '',
                    'type' => 'title',
                    'class' => 'ppcp_separator_heading',
                ),
                'enabled_pay_later_messaging' => array(
                    'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                    'label' => __('Enable Pay Later Messaging', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'description' => '<div style="font-size: smaller">Displays Pay Later messaging for available offers. Restrictions apply. <a target="_blank" href="https://developer.paypal.com/docs/business/pay-later/commerce-platforms/angelleye/">See terms and learn more</a></div>',
                    'default' => 'yes'
                ),
                'pay_later_messaging_page_type' => array(
                    'title' => __('Page Type', 'paypal-for-woocommerce'),
                    'type' => 'multiselect',
                    'css' => 'width: 100%;',
                    'class' => 'wc-enhanced-select pay_later_messaging_field',
                    'default' => array('product', 'cart', 'payment'),
                    'options' => array('home' => __('Home', 'paypal-for-woocommerce'), 'category' => __('Category', 'paypal-for-woocommerce'), 'product' => __('Product', 'paypal-for-woocommerce'), 'cart' => __('Cart', 'paypal-for-woocommerce'), 'payment' => __('Payment', 'paypal-for-woocommerce')),
                    'description' => '<div style="font-size: smaller;">Set the page(s) you want to display messaging on, and then adjust that page\'s display option below.</div>',
                ),
                'pay_later_messaging_home_page_settings' => array(
                    'title' => __('Home Page', 'paypal-for-woocommerce'),
                    'class' => 'pay_later_messaging_field pay_later_messaging_home_field',
                    'description' => __('Customize the appearance of <a target="_blank" href="https://www.paypal.com/us/business/buy-now-pay-later">Pay Later Messaging</a> on the Home page to promote special financing offers which help increase sales.', 'paypal-for-woocommerce'),
                    'type' => 'title',
                ),
                'pay_later_messaging_home_layout_type' => array(
                    'title' => __('Layout Type', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_home_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'flex',
                    'desc_tip' => true,
                    'options' => array('text' => __('Text Layout', 'paypal-for-woocommerce'), 'flex' => __('Flex Layout', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_home_text_layout_logo_type' => array(
                    'title' => __('Logo Type', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_home_field pay_later_messaging_home_text_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'primary',
                    'desc_tip' => true,
                    'options' => array('primary' => __('Primary', 'paypal-for-woocommerce'), 'alternative' => __('Alternative', 'paypal-for-woocommerce'), 'inline' => __('Inline', 'paypal-for-woocommerce'), 'none' => __('None', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_home_text_layout_logo_position' => array(
                    'title' => __('Logo Position', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_home_field pay_later_messaging_home_text_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'left',
                    'desc_tip' => true,
                    'options' => array('left' => __('Left', 'paypal-for-woocommerce'), 'right' => __('Right', 'paypal-for-woocommerce'), 'top' => __('Top', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_home_text_layout_text_size' => array(
                    'title' => __('Text Size', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_home_field pay_later_messaging_home_text_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => '12',
                    'desc_tip' => true,
                    'options' => array('10' => __('10 px', 'paypal-for-woocommerce'), '11' => __('11 px', 'paypal-for-woocommerce'), '12' => __('12 px', 'paypal-for-woocommerce'), '13' => __('13 px', 'paypal-for-woocommerce'), '14' => __('14 px', 'paypal-for-woocommerce'), '15' => __('15 px', 'paypal-for-woocommerce'), '16' => __('16 px', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_home_text_layout_text_color' => array(
                    'title' => __('Text Color', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_home_field pay_later_messaging_home_text_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'black',
                    'desc_tip' => true,
                    'options' => array('black' => __('Black', 'paypal-for-woocommerce'), 'white' => __('White', 'paypal-for-woocommerce'), 'monochrome' => __('Monochrome', 'paypal-for-woocommerce'), 'grayscale' => __('Grayscale', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_home_flex_layout_color' => array(
                    'title' => __('Color', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_home_field pay_later_messaging_home_flex_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'blue',
                    'desc_tip' => true,
                    'options' => array('blue' => __('Blue', 'paypal-for-woocommerce'), 'black' => __('Black', 'paypal-for-woocommerce'), 'white' => __('White', 'paypal-for-woocommerce'), 'white-no-border' => __('White (No Border)', 'paypal-for-woocommerce'), 'gray' => __('Gray', 'paypal-for-woocommerce'), 'monochrome' => __('Monochrome', 'paypal-for-woocommerce'), 'grayscale' => __('Grayscale', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_home_flex_layout_ratio' => array(
                    'title' => __('Ratio', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_home_field pay_later_messaging_home_flex_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => '8x1',
                    'desc_tip' => true,
                    'options' => array('1x1' => __('Flexes between 120px and 300px wide', 'paypal-for-woocommerce'), '1x4' => __('160px wide', 'paypal-for-woocommerce'), '8x1' => __('Flexes between 250px and 768px wide', 'paypal-for-woocommerce'), '20x1' => __('Flexes between 250px and 1169px wide', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_home_shortcode' => array(
                    'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                    'label' => __('I need a shortcode so that I can place the message in a better spot on Home page.', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'class' => 'pay_later_messaging_field pay_later_messaging_home_field pay_later_messaging_home_shortcode',
                    'description' => '',
                    'default' => 'no'
                ),
                'pay_later_messaging_home_preview_shortcode' => array(
                    'title' => __('Shortcode', 'paypal-for-woocommerce'),
                    'type' => 'copy_text',
                    'class' => 'pay_later_messaging_field pay_later_messaging_home_field pay_later_messaging_home_preview_shortcode preview_shortcode',
                    'description' => '',
                    'custom_attributes' => array('readonly' => 'readonly'),
                    'button_class' => 'home_copy_text',
                    'default' => '[aepfw_bnpl_message placement="home"]'
                ),
                'pay_later_messaging_category_page_settings' => array(
                    'title' => __('Category Page', 'paypal-for-woocommerce'),
                    'description' => __('Customize the appearance of <a target="_blank" href="https://www.paypal.com/us/business/buy-now-pay-later">Pay Later Messaging</a> on the Category page to promote special financing offers which help increase sales.', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'class' => 'pay_later_messaging_field pay_later_messaging_category_field',
                ),
                'pay_later_messaging_category_layout_type' => array(
                    'title' => __('Layout Type', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_category_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'flex',
                    'desc_tip' => true,
                    'options' => array('text' => __('Text Layout', 'paypal-for-woocommerce'), 'flex' => __('Flex Layout', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_category_text_layout_logo_type' => array(
                    'title' => __('Logo Type', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_category_field pay_later_messaging_category_text_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'primary',
                    'desc_tip' => true,
                    'options' => array('primary' => __('Primary', 'paypal-for-woocommerce'), 'alternative' => __('Alternative', 'paypal-for-woocommerce'), 'inline' => __('Inline', 'paypal-for-woocommerce'), 'none' => __('None', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_category_text_layout_logo_position' => array(
                    'title' => __('Logo Position', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_category_field pay_later_messaging_category_text_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'left',
                    'desc_tip' => true,
                    'options' => array('left' => __('Left', 'paypal-for-woocommerce'), 'right' => __('Right', 'paypal-for-woocommerce'), 'top' => __('Top', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_category_text_layout_text_size' => array(
                    'title' => __('Text Size', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_category_field pay_later_messaging_category_text_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => '12',
                    'desc_tip' => true,
                    'options' => array('10' => __('10 px', 'paypal-for-woocommerce'), '11' => __('11 px', 'paypal-for-woocommerce'), '12' => __('12 px', 'paypal-for-woocommerce'), '13' => __('13 px', 'paypal-for-woocommerce'), '14' => __('14 px', 'paypal-for-woocommerce'), '15' => __('15 px', 'paypal-for-woocommerce'), '16' => __('16 px', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_category_text_layout_text_color' => array(
                    'title' => __('Text Color', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_category_field pay_later_messaging_category_text_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'black',
                    'desc_tip' => true,
                    'options' => array('black' => __('Black', 'paypal-for-woocommerce'), 'white' => __('White', 'paypal-for-woocommerce'), 'monochrome' => __('Monochrome', 'paypal-for-woocommerce'), 'grayscale' => __('Grayscale', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_category_flex_layout_color' => array(
                    'title' => __('Color', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_category_field pay_later_messaging_category_flex_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'blue',
                    'desc_tip' => true,
                    'options' => array('blue' => __('Blue', 'paypal-for-woocommerce'), 'black' => __('Black', 'paypal-for-woocommerce'), 'white' => __('White', 'paypal-for-woocommerce'), 'white-no-border' => __('White (No Border)', 'paypal-for-woocommerce'), 'gray' => __('Gray', 'paypal-for-woocommerce'), 'monochrome' => __('Monochrome', 'paypal-for-woocommerce'), 'grayscale' => __('Grayscale', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_category_flex_layout_ratio' => array(
                    'title' => __('Ratio', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_category_field pay_later_messaging_category_flex_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => '8x1',
                    'desc_tip' => true,
                    'options' => array('1x1' => __('Flexes between 120px and 300px wide', 'paypal-for-woocommerce'), '1x4' => __('160px wide', 'paypal-for-woocommerce'), '8x1' => __('Flexes between 250px and 768px wide', 'paypal-for-woocommerce'), '20x1' => __('Flexes between 250px and 1169px wide', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_category_shortcode' => array(
                    'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                    'label' => __('I need a shortcode so that I can place the message in a better spot on category page.', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'class' => 'pay_later_messaging_field pay_later_messaging_category_field pay_later_messaging_category_shortcode',
                    'description' => '',
                    'default' => 'no'
                ),
                'pay_later_messaging_category_preview_shortcode' => array(
                    'title' => __('Shortcode', 'paypal-for-woocommerce'),
                    'type' => 'copy_text',
                    'class' => 'pay_later_messaging_field pay_later_messaging_category_field pay_later_messaging_category_preview_shortcode preview_shortcode',
                    'description' => '',
                    'button_class' => 'category_copy_text',
                    'custom_attributes' => array('readonly' => 'readonly'),
                    'default' => '[aepfw_bnpl_message placement="category"]'
                ),
                'pay_later_messaging_product_page_settings' => array(
                    'title' => __('Product Page', 'paypal-for-woocommerce'),
                    'description' => __('Customize the appearance of <a target="_blank" href="https://www.paypal.com/us/business/buy-now-pay-later">Pay Later Messaging</a> on the Product page to promote special financing offers which help increase sales.', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'class' => 'pay_later_messaging_field pay_later_messaging_product_field',
                ),
                'pay_later_messaging_product_layout_type' => array(
                    'title' => __('Layout Type', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_product_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'text',
                    'desc_tip' => true,
                    'options' => array('text' => __('Text Layout', 'paypal-for-woocommerce'), 'flex' => __('Flex Layout', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_product_text_layout_logo_type' => array(
                    'title' => __('Logo Type', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_product_field pay_later_messaging_product_text_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'primary',
                    'desc_tip' => true,
                    'options' => array('primary' => __('Primary', 'paypal-for-woocommerce'), 'alternative' => __('Alternative', 'paypal-for-woocommerce'), 'inline' => __('Inline', 'paypal-for-woocommerce'), 'none' => __('None', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_product_text_layout_logo_position' => array(
                    'title' => __('Logo Position', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_product_field pay_later_messaging_product_text_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'left',
                    'desc_tip' => true,
                    'options' => array('left' => __('Left', 'paypal-for-woocommerce'), 'right' => __('Right', 'paypal-for-woocommerce'), 'top' => __('Top', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_product_text_layout_text_size' => array(
                    'title' => __('Text Size', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_product_field pay_later_messaging_product_text_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => '12',
                    'desc_tip' => true,
                    'options' => array('10' => __('10 px', 'paypal-for-woocommerce'), '11' => __('11 px', 'paypal-for-woocommerce'), '12' => __('12 px', 'paypal-for-woocommerce'), '13' => __('13 px', 'paypal-for-woocommerce'), '14' => __('14 px', 'paypal-for-woocommerce'), '15' => __('15 px', 'paypal-for-woocommerce'), '16' => __('16 px', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_product_text_layout_text_color' => array(
                    'title' => __('Text Color', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_product_field pay_later_messaging_product_text_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'black',
                    'desc_tip' => true,
                    'options' => array('black' => __('Black', 'paypal-for-woocommerce'), 'white' => __('White', 'paypal-for-woocommerce'), 'monochrome' => __('Monochrome', 'paypal-for-woocommerce'), 'grayscale' => __('Grayscale', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_product_flex_layout_color' => array(
                    'title' => __('Color', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_product_field pay_later_messaging_product_flex_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'blue',
                    'desc_tip' => true,
                    'options' => array('blue' => __('Blue', 'paypal-for-woocommerce'), 'black' => __('Black', 'paypal-for-woocommerce'), 'white' => __('White', 'paypal-for-woocommerce'), 'white-no-border' => __('White (No Border)', 'paypal-for-woocommerce'), 'gray' => __('Gray', 'paypal-for-woocommerce'), 'monochrome' => __('Monochrome', 'paypal-for-woocommerce'), 'grayscale' => __('Grayscale', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_product_flex_layout_ratio' => array(
                    'title' => __('Ratio', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_product_field pay_later_messaging_product_flex_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => '8x1',
                    'desc_tip' => true,
                    'options' => array('1x1' => __('Flexes between 120px and 300px wide', 'paypal-for-woocommerce'), '1x4' => __('160px wide', 'paypal-for-woocommerce'), '8x1' => __('Flexes between 250px and 768px wide', 'paypal-for-woocommerce'), '20x1' => __('Flexes between 250px and 1169px wide', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_product_shortcode' => array(
                    'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                    'label' => __('I need a shortcode so that I can place the message in a better spot on product page.', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'class' => 'pay_later_messaging_field pay_later_messaging_product_field pay_later_messaging_product_shortcode',
                    'description' => '',
                    'default' => 'no'
                ),
                'pay_later_messaging_product_preview_shortcode' => array(
                    'title' => __('Shortcode', 'paypal-for-woocommerce'),
                    'type' => 'copy_text',
                    'class' => 'pay_later_messaging_field pay_later_messaging_product_field pay_later_messaging_product_preview_shortcode preview_shortcode',
                    'description' => '',
                    'button_class' => 'product_copy_text',
                    'custom_attributes' => array('readonly' => 'readonly'),
                    'default' => '[aepfw_bnpl_message placement="product"]'
                ),
                'pay_later_messaging_cart_page_settings' => array(
                    'title' => __('Cart Page', 'paypal-for-woocommerce'),
                    'description' => __('Customize the appearance of <a target="_blank" href="https://www.paypal.com/us/business/buy-now-pay-later">Pay Later Messaging</a> on the Cart page to promote special financing offers which help increase sales.', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'class' => 'pay_later_messaging_field pay_later_messaging_cart_field',
                ),
                'pay_later_messaging_cart_layout_type' => array(
                    'title' => __('Layout Type', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_cart_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'text',
                    'desc_tip' => true,
                    'options' => array('text' => __('Text Layout', 'paypal-for-woocommerce'), 'flex' => __('Flex Layout', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_cart_text_layout_logo_type' => array(
                    'title' => __('Logo Type', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_cart_field pay_later_messaging_cart_text_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'primary',
                    'desc_tip' => true,
                    'options' => array('primary' => __('Primary', 'paypal-for-woocommerce'), 'alternative' => __('Alternative', 'paypal-for-woocommerce'), 'inline' => __('Inline', 'paypal-for-woocommerce'), 'none' => __('None', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_cart_text_layout_logo_position' => array(
                    'title' => __('Logo Position', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_cart_field pay_later_messaging_cart_text_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'left',
                    'desc_tip' => true,
                    'options' => array('left' => __('Left', 'paypal-for-woocommerce'), 'right' => __('Right', 'paypal-for-woocommerce'), 'top' => __('Top', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_cart_text_layout_text_size' => array(
                    'title' => __('Text Size', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_cart_field pay_later_messaging_cart_text_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => '12',
                    'desc_tip' => true,
                    'options' => array('10' => __('10 px', 'paypal-for-woocommerce'), '11' => __('11 px', 'paypal-for-woocommerce'), '12' => __('12 px', 'paypal-for-woocommerce'), '13' => __('13 px', 'paypal-for-woocommerce'), '14' => __('14 px', 'paypal-for-woocommerce'), '15' => __('15 px', 'paypal-for-woocommerce'), '16' => __('16 px', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_cart_text_layout_text_color' => array(
                    'title' => __('Text Color', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_cart_field pay_later_messaging_cart_text_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'black',
                    'desc_tip' => true,
                    'options' => array('black' => __('Black', 'paypal-for-woocommerce'), 'white' => __('White', 'paypal-for-woocommerce'), 'monochrome' => __('Monochrome', 'paypal-for-woocommerce'), 'grayscale' => __('Grayscale', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_cart_flex_layout_color' => array(
                    'title' => __('Color', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_cart_field pay_later_messaging_cart_flex_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'blue',
                    'desc_tip' => true,
                    'options' => array('blue' => __('Blue', 'paypal-for-woocommerce'), 'black' => __('Black', 'paypal-for-woocommerce'), 'white' => __('White', 'paypal-for-woocommerce'), 'white-no-border' => __('White (No Border)', 'paypal-for-woocommerce'), 'gray' => __('Gray', 'paypal-for-woocommerce'), 'monochrome' => __('Monochrome', 'paypal-for-woocommerce'), 'grayscale' => __('Grayscale', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_cart_flex_layout_ratio' => array(
                    'title' => __('Ratio', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_cart_field pay_later_messaging_cart_flex_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => '8x1',
                    'desc_tip' => true,
                    'options' => array('1x1' => __('Flexes between 120px and 300px wide', 'paypal-for-woocommerce'), '1x4' => __('160px wide', 'paypal-for-woocommerce'), '8x1' => __('Flexes between 250px and 768px wide', 'paypal-for-woocommerce'), '20x1' => __('Flexes between 250px and 1169px wide', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_cart_shortcode' => array(
                    'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                    'label' => __('I need a shortcode so that I can place the message in a better spot on cart page.', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'class' => 'pay_later_messaging_field pay_later_messaging_cart_field pay_later_messaging_cart_shortcode',
                    'description' => '',
                    'default' => 'no'
                ),
                'pay_later_messaging_cart_preview_shortcode' => array(
                    'title' => __('Shortcode', 'paypal-for-woocommerce'),
                    'type' => 'copy_text',
                    'class' => 'pay_later_messaging_field pay_later_messaging_cart_field pay_later_messaging_cart_preview_shortcode preview_shortcode',
                    'description' => '',
                    'button_class' => 'cart_copy_text',
                    'custom_attributes' => array('readonly' => 'readonly'),
                    'default' => '[aepfw_bnpl_message placement="cart"]'
                ),
                'pay_later_messaging_payment_page_settings' => array(
                    'title' => __('Payment Page', 'paypal-for-woocommerce'),
                    'description' => __('Customize the appearance of <a target="_blank" href="https://www.paypal.com/us/business/buy-now-pay-later">Pay Later Messaging</a> on the Payment page to promote special financing offers which help increase sales.', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'class' => 'pay_later_messaging_field pay_later_messaging_payment_field',
                ),
                'pay_later_messaging_payment_layout_type' => array(
                    'title' => __('Layout Type', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_payment_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'text',
                    'desc_tip' => true,
                    'options' => array('text' => __('Text Layout', 'paypal-for-woocommerce'), 'flex' => __('Flex Layout', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_payment_text_layout_logo_type' => array(
                    'title' => __('Logo Type', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_payment_field pay_later_messaging_payment_text_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'primary',
                    'desc_tip' => true,
                    'options' => array('primary' => __('Primary', 'paypal-for-woocommerce'), 'alternative' => __('Alternative', 'paypal-for-woocommerce'), 'inline' => __('Inline', 'paypal-for-woocommerce'), 'none' => __('None', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_payment_text_layout_logo_position' => array(
                    'title' => __('Logo Position', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_payment_field pay_later_messaging_payment_text_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'left',
                    'desc_tip' => true,
                    'options' => array('left' => __('Left', 'paypal-for-woocommerce'), 'right' => __('Right', 'paypal-for-woocommerce'), 'top' => __('Top', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_payment_text_layout_text_size' => array(
                    'title' => __('Text Size', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_payment_field pay_later_messaging_payment_text_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => '12',
                    'desc_tip' => true,
                    'options' => array('10' => __('10 px', 'paypal-for-woocommerce'), '11' => __('11 px', 'paypal-for-woocommerce'), '12' => __('12 px', 'paypal-for-woocommerce'), '13' => __('13 px', 'paypal-for-woocommerce'), '14' => __('14 px', 'paypal-for-woocommerce'), '15' => __('15 px', 'paypal-for-woocommerce'), '16' => __('16 px', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_payment_text_layout_text_color' => array(
                    'title' => __('Text Color', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_payment_field pay_later_messaging_payment_text_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'black',
                    'desc_tip' => true,
                    'options' => array('black' => __('Black', 'paypal-for-woocommerce'), 'white' => __('White', 'paypal-for-woocommerce'), 'monochrome' => __('Monochrome', 'paypal-for-woocommerce'), 'grayscale' => __('Grayscale', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_payment_flex_layout_color' => array(
                    'title' => __('Color', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_payment_field pay_later_messaging_payment_flex_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => 'blue',
                    'desc_tip' => true,
                    'options' => array('blue' => __('Blue', 'paypal-for-woocommerce'), 'black' => __('Black', 'paypal-for-woocommerce'), 'white' => __('White', 'paypal-for-woocommerce'), 'white-no-border' => __('White (No Border)', 'paypal-for-woocommerce'), 'gray' => __('Gray', 'paypal-for-woocommerce'), 'monochrome' => __('Monochrome', 'paypal-for-woocommerce'), 'grayscale' => __('Grayscale', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_payment_flex_layout_ratio' => array(
                    'title' => __('Ratio', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select pay_later_messaging_field pay_later_messaging_payment_field pay_later_messaging_payment_flex_layout_field',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'default' => '8x1',
                    'desc_tip' => true,
                    'options' => array('1x1' => __('Flexes between 120px and 300px wide', 'paypal-for-woocommerce'), '1x4' => __('160px wide', 'paypal-for-woocommerce'), '8x1' => __('Flexes between 250px and 768px wide', 'paypal-for-woocommerce'), '20x1' => __('Flexes between 250px and 1169px wide', 'paypal-for-woocommerce'))
                ),
                'pay_later_messaging_payment_shortcode' => array(
                    'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                    'label' => __('I need a shortcode so that I can place the message in a better spot on payment page.', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'class' => 'pay_later_messaging_field pay_later_messaging_payment_field pay_later_messaging_payment_shortcode',
                    'description' => '',
                    'default' => 'no'
                ),
                'pay_later_messaging_payment_preview_shortcode' => array(
                    'title' => __('Shortcode', 'paypal-for-woocommerce'),
                    'type' => 'copy_text',
                    'class' => 'pay_later_messaging_field pay_later_messaging_payment_field pay_later_messaging_payment_preview_shortcode preview_shortcode',
                    'description' => '',
                    'button_class' => 'payment_copy_text',
                    'custom_attributes' => array('readonly' => 'readonly'),
                    'default' => '[aepfw_bnpl_message placement="payment"]'
                ),
                'tokenization_subscriptions' => array(
                    'title' => __('Tokenization / Subscriptions', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'description' => '',
                    'class' => 'ppcp_separator_heading',
                ),
                'enable_tokenized_payments' => array(
                    'title' => __('Enable Tokenized Payments', 'paypal-for-woocommerce'),
                    'label' => __('Enable Tokenized Payments', 'paypal-for-woocommerce'),
                    'type' => 'checkbox_enable_paypal_vault',
                    'description' => $vaulting_advanced_text,
                    'default' => 'no',
                    'desc_tip' => true,
                    'class' => 'enable_tokenized_payments',
                    'need_to_display_paypal_vault_onboard_button' => $this->need_to_display_paypal_vault_onboard_button,
                    'is_paypal_vault_enable' => $this->is_paypal_vault_enable,
                    'custom_attributes' => $vaulting_custom_attributes
                ),
                'apple_pay_authorizations' => array(
                    'title' => __('Apple Pay', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'description' => '',
                    'class' => 'ppcp_separator_heading',
                ),
                'enable_apple_pay' => array(
                    'title' => __('Enable Apple Pay', 'paypal-for-woocommerce'),
                    'label' => __('Enable Apple Pay', 'paypal-for-woocommerce'),
                    'type' => 'checkbox_enable_paypal_apple_pay',
                    'description' => $applePayText,
                    'default' => 'no',
                    'desc_tip' => true,
                    'class' => 'enable_apple_pay',
                    'need_to_display_apple_pay_button' => $this->need_to_display_apple_pay_button,
                    'is_apple_pay_enable' => $this->is_apple_pay_enable,
                    'is_apple_pay_approved' => $this->is_apple_pay_approved,
                    'custom_attributes' => $apple_pay_custom_attributes,
                    'is_ppcp_connected' => $this->is_ppcp_connected
                ),
                'apple_pay_payments_title' => array(
                    'title' => __('Apple Pay Title', 'paypal-for-woocommerce'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'paypal-for-woocommerce'),
                    'default' => __('Apple Pay', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                ),
                'apple_pay_payments_description' => array(
                    'title' => __('Apple Pay Payment Description', 'paypal-for-woocommerce'),
                    'type' => 'text',
                    'description' => __('This controls the description which the user sees when they select Apple Pay payment method during checkout.', 'paypal-for-woocommerce'),
                    'default' => __('Accept payments using Apple Pay.', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                ),
                'apple_pay_rec_payment_desc' => array(
                    'title' => __('Apple Pay Billing Agreement Title', 'paypal-for-woocommerce'),
                    'type' => 'text',
                    'description' => __('A description of the recurring payment that Apple Pay displays to the user in the payment sheet.', 'paypal-for-woocommerce'),
                    'default' => __('Billing Agreement', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                ),
                'apple_pay_rec_billing_agreement_desc' => array(
                    'title' => __('Apple Pay Billing Agreement Description', 'paypal-for-woocommerce'),
                    'type' => 'text',
                    'description' => __('A localized billing agreement that the payment sheet displays to the user before the user authorizes the payment.', 'paypal-for-woocommerce'),
                    'default' => __('Billing Agreement', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                ),
                'google_pay_authorizations' => array(
                    'title' => __('Google Pay', 'paypal-for-woocommerce'),
                    'class' => 'ppcp_separator_heading',
                    'type' => 'title',
                ),
                'enable_google_pay' => array(
                    'title' => __('Enable Google Pay', 'paypal-for-woocommerce'),
                    'label' => __('Enable Google Pay', 'paypal-for-woocommerce'),
                    'type' => 'checkbox_enable_paypal_google_pay',
                    'description' => $googlePayText,
                    'default' => 'no',
                    'desc_tip' => true,
                    'class' => 'enable_google_pay',
                    'need_to_display_google_pay_button' => $this->need_to_display_google_pay_button,
                    'is_google_pay_enable' => $this->is_google_pay_enable,
                    'is_google_pay_approved' => $this->is_google_pay_approved,
                    'custom_attributes' => $google_pay_custom_attributes,
                    'is_ppcp_connected' => $this->is_ppcp_connected
                ),
                'google_pay_payments_title' => array(
                    'title' => __('Google Pay Title', 'paypal-for-woocommerce'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'paypal-for-woocommerce'),
                    'default' => __('Google Pay', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                ),
                'google_pay_payments_description' => array(
                    'title' => __('Google Pay Payment Description', 'paypal-for-woocommerce'),
                    'type' => 'text',
                    'description' => __('This controls the description which the user sees when they select Google Pay payment method during checkout.', 'paypal-for-woocommerce'),
                    'default' => __('Accept payments using Google Pay.', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                ),
                'paypal_shipment_tracking' => array(
                    'title' => __('PayPal Shipment Tracking', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'description' => '',
                    'class' => 'ppcp_separator_heading',
                ),
                'enable_paypal_shipment_tracking' => array(
                    'title' => __('Enable PayPal Shipment Tracking', 'paypal-for-woocommerce'),
                    'label' => __('Enable PayPal Shipment Tracking', 'paypal-for-woocommerce'),
                    'type' => 'paypal_shipment_tracking',
                    'description' => '',
                    'default' => 'no',
                    'desc_tip' => true,
                    'class' => 'enable_package_tracking',
                ),
                'paypal_for_woocommerce_multi_account_management' => array(
                    'title' => __('Multi-Account Management', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'description' => '',
                    'class' => 'ppcp_separator_heading',
                ),
                'enable_paypal_for_woocommerce_multi_account_management' => array(
                    'title' => __('Enable Multi-Account Management', 'paypal-for-woocommerce'),
                    'label' => __('Enable Multi-Account Management', 'paypal-for-woocommerce'),
                    'type' => 'paypal_for_woocommerce_multi_account_management',
                    'description' => '',
                    'default' => 'no',
                    'desc_tip' => true,
                    'class' => 'enable_pfwma',
                ),
                'woo_pre_order' => array(
                    'title' => __('WooCommerce Pre-Orders Settings', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'description' => '',
                    'class' => 'ppcp_separator_heading',
                ),
                'woo_pre_order_payment_mode' => array(
                    'title' => __('Pre-Orders Payment Mode', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'description' => __('Choose whether you wish to Auth/capture OR PayPal Vault.', 'paypal-for-woocommerce'),
                    'default' => $this->is_paypal_vault_enable ? 'vault' : 'authorize',
                    'desc_tip' => true,
                    'options' => $woo_pre_order_payment_mode,
                ),
                'advanced_settings' => array(
                    'title' => __('Advanced Settings', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'description' => '',
                    'class' => 'ppcp_separator_heading',
                ),
                'paymentaction' => array(
                    'title' => __('Payment Action', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'description' => __('Choose whether you wish to capture funds immediately or authorize payment only.', 'paypal-for-woocommerce'),
                    'default' => 'capture',
                    'desc_tip' => true,
                    'options' => $paymentaction_options,
                ),
                'auto_capture_auth' => array(
                    'title' => __('Automatic Capture of Pending Authorizations', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Automatically capture a pending authorization when the order status is updated to Processing or Completed.', 'paypal-for-woocommerce'),
                    'default' => 'yes',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'desc_tip' => true
                ),
                'paymentstatus' => array(
                    'title' => __('Order Status', 'paypal-for-woocommerce'),
                    'description' => __('Select the status you wish to apply after the successful order. The default setting adheres to WooCommerce rules for order status.', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'default' => 'wc-default',
                    'desc_tip' => true,
                    'options' => $this->angelleye_get_order_statuses(),
                ),
                'invoice_prefix' => array(
                    'title' => __('Invoice Prefix', 'paypal-for-woocommerce'),
                    'type' => 'text',
                    'description' => __('Please enter a prefix for your invoice numbers. If you use your PayPal account for multiple stores ensure this prefix is unique as PayPal will not allow orders with the same invoice number.', 'paypal-for-woocommerce'),
                    'default' => 'WC-PPCP',
                    'desc_tip' => true,
                ),
                'skip_final_review' => array(
                    'title' => __('Skip Final Review', 'paypal-for-woocommerce'),
                    'label' => __('Enables the option to skip the final review page.', 'paypal-for-woocommerce'),
                    'description' => __('By default, users will be returned from PayPal and presented with a final review page which includes shipping and tax in the order details. Enable this option to eliminate this page in the checkout process.  This only applies when the WooCommerce checkout page is skipped.  If the WooCommerce checkout page is used, the final review page will always be skipped.') . '<br /><b class="final_review_notice"><span class="guest_checkout_notice">' . $skip_final_review_option_not_allowed_guest_checkout . '</span></b>' . '<b class="final_review_notice"><span class="terms_notice">' . $skip_final_review_option_not_allowed_terms . '</span></b>',
                    'type' => 'checkbox',
                    'default' => 'no'
                ),
                'order_review_page_enable_coupons' => array(
                    'title' => __('Coupon Codes', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable the use of coupon codes on the final review page.', 'paypal-for-woocommerce'),
                    'description' => '',
                    'default' => 'yes',
                ),
                'disable_term' => array(
                    'title' => __('Disable Terms and Conditions', 'paypal-for-woocommerce'),
                    'label' => __('Disable Terms and Conditions for Express Checkout orders.', 'paypal-for-woocommerce'),
                    'description' => __('By default, if a Terms and Conditions page is set in WooCommerce, this would require the review page and would override the Skip Final Review option.  Check this option to disable Terms and Conditions for Express Checkout orders only so that you can use the Skip Final Review option.'),
                    'type' => 'checkbox',
                    'default' => 'no',
                    'class' => 'disable_term',
                ),
                'brand_name' => array(
                    'title' => __('Brand Name', 'paypal-for-woocommerce'),
                    'type' => 'text',
                    'description' => __('This controls what users see as the brand / company name on PayPal review pages.', 'paypal-for-woocommerce'),
                    'default' => __(get_bloginfo('name'), 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                ),
                'landing_page' => array(
                    'title' => __('Landing Page', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'description' => __('The type of landing page to show on the PayPal site for customer checkout. PayPal Account Optional must be checked for this option to be used.', 'paypal-for-woocommerce'),
                    'options' => array('LOGIN' => __('Login', 'paypal-for-woocommerce'),
                        'BILLING' => __('Billing', 'paypal-for-woocommerce'),
                        'NO_PREFERENCE' => __('No Preference', 'paypal-for-woocommerce')),
                    'default' => 'NO_PREFERENCE',
                    'desc_tip' => true,
                ),
                'payee_preferred' => array(
                    'title' => __('Instant Payments ', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'default' => 'no',
                    'desc_tip' => true,
                    'description' => __(
                            'If you enable this setting, PayPal will be instructed not to allow the buyer to use funding sources that take additional time to complete (for example, eChecks). Instead, the buyer will be required to use an instant funding source, such as an instant transfer, a credit/debit card, or PayPal Credit.', 'paypal-for-woocommerce'
                    ),
                    'label' => __('Require Instant Payment', 'paypal-for-woocommerce'),
                ),
                'set_billing_address' => array(
                    'title' => __('Billing Address', 'paypal-for-woocommerce'),
                    'label' => __('Set billing address in WooCommerce using the address returned by PayPal.', 'paypal-for-woocommerce'),
                    'description' => __('This does not apply when a billing address is provided by WooCommerce through the checkout page or from a logged in user profile.', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'default' => 'yes',
                    'desc_tip' => false,
                ),
                'send_items' => array(
                    'title' => __('Send Item Details', 'paypal-for-woocommerce'),
                    'label' => __('Send line item details to PayPal', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'description' => __('Include all line item details in the payment request to PayPal so that they can be seen from the PayPal transaction details page.', 'paypal-for-woocommerce'),
                    'default' => 'yes'
                ),
                'enable_advanced_card_payments' => array(
                    'title' => __('Advanced Credit Cards', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable advanced credit and debit card payments.', 'paypal-for-woocommerce'),
                    'default' => 'no',
                    'description' => 'PayPal currently supports direct credit card processing for AU, AT, BE, BG, CA, CY, CZ, DK, EE, FI, FR, DE, GR, HU, IE, IT, JP, LV, LI, LT, LU, MT, MX, NL, PL, PT, RO, SK, SI, ES, SE, GB, US and NO. <br> <br>' . '<b>' . $advanced_cc_text . '</b>',
                    'custom_attributes' => $advanced_cc_custom_attributes
                ),
                '3d_secure_contingency' => array(
                    'title' => __('Contingency for 3D Secure', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select advanced_cc_fields_group',
                    'options' => array(
                        'SCA_WHEN_REQUIRED' => __('3D Secure when required', 'paypal-for-woocommerce'),
                        'SCA_ALWAYS' => __('Always trigger 3D Secure', 'paypal-for-woocommerce'),
                    ),
                    'default' => 'SCA_WHEN_REQUIRED',
                    'desc_tip' => true,
                    'description' => __('3D Secure benefits cardholders and merchants by providing an additional layer of verification using Verified by Visa, MasterCard SecureCode and American Express SafeKey.', 'paypal-for-woocommerce'),
                ),
                'advanced_card_payments_title' => array(
                    'title' => __('Advanced Credit Cards Title', 'paypal-for-woocommerce'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'paypal-for-woocommerce'),
                    'default' => __('Credit Card', 'paypal-for-woocommerce'),
                    'desc_tip' => true,
                    'class' => 'advanced_cc_fields_group'
                ),
                'advanced_card_payments_display_position' => array(
                    'title' => __('Advanced Credit Cards Position', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select advanced_cc_fields_group',
                    'options' => array(
                        'before' => __('Before PayPal Smart Button', 'paypal-for-woocommerce'),
                        'after' => __('After PayPal Smart Button', 'paypal-for-woocommerce'),
                    ),
                    'default' => 'before',
                    'desc_tip' => true,
                    'description' => __('This controls the gateway position which the user sees during checkout.', 'paypal-for-woocommerce'),
                ),
                'disable_cards' => array(
                    'title' => __('Disable specific credit cards', 'paypal-for-woocommerce'),
                    'type' => 'multiselect',
                    'class' => 'wc-enhanced-select advanced_cc_fields_group',
                    'default' => array(),
                    'desc_tip' => true,
                    'description' => __(
                            'By default all possible credit cards will be accepted. You can disable some cards, if you wish.',
                            'paypal-for-woocommerce'
                    ),
                    'options' => $cards_list,
                ),
                'cards_input_size' => array(
                    'title' => __('Card Text Size', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select advanced_cc_fields_group',
                    'default' => '',
                    'desc_tip' => true,
                    'description' => __('Choose the font size for the field.', 'paypal-for-woocommerce'),
                    'options' => $this->get_size_listing(10, 50, 2, 'px')
                ),
                'cards_input_color' => array(
                    'title' => __('Card Text Color', 'paypal-for-woocommerce'),
                    'type' => 'color_picker',
                    'class' => 'advanced_cc_fields_group',
                    'default' => '#000000',
                    'desc_tip' => true,
                    'description' => __('', 'paypal-for-woocommerce')
                ),
                'cards_input_style' => array(
                    'title' => __('Card Text Style', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select advanced_cc_fields_group',
                    'default' => 'normal',
                    'desc_tip' => true,
                    'description' => __('', 'paypal-for-woocommerce'),
                    'options' => array('normal' => __('Normal', 'paypal-for-woocommerce'), 'italic' => __('Italic', 'paypal-for-woocommerce'), 'inherit' => __('Inherit', 'paypal-for-woocommerce'), 'revert' => __('Revert', 'paypal-for-woocommerce'))
                ),
                'cards_input_weight' => array(
                    'title' => __('Card Text Weight', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select advanced_cc_fields_group',
                    'default' => '',
                    'desc_tip' => true,
                    'description' => __('', 'paypal-for-woocommerce'),
                    'options' => array('' => __('Default', 'paypal-for-woocommerce'), '100' => __('100', 'paypal-for-woocommerce'), '200' => __('200', 'paypal-for-woocommerce'), '300' => __('300', 'paypal-for-woocommerce'), '400' => __('400', 'paypal-for-woocommerce'), '500' => __('500', 'paypal-for-woocommerce'), '600' => __('600', 'paypal-for-woocommerce'), 'bold' => __('Bold', 'paypal-for-woocommerce'))
                ),
                'cards_input_padding' => array(
                    'title' => __('Card Text Padding', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select advanced_cc_fields_group',
                    'default' => '',
                    'desc_tip' => true,
                    'description' => __('', 'paypal-for-woocommerce'),
                    'options' => $this->get_size_listing(1, 20, 1, 'px')
                ),
                'soft_descriptor' => array(
                    'title' => __('Credit Card Statement Name', 'paypal-for-woocommerce'),
                    'type' => 'text',
                    'description' => __('The value entered here will be displayed on the buyer\'s credit card statement.', 'paypal-for-woocommerce'),
                    'default' => substr(get_bloginfo('name'), 0, 21),
                    'desc_tip' => true,
                    'custom_attributes' => array('maxlength' => '22'),
                ),
                'error_email_notification' => array(
                    'title' => __('Error Email Notifications', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable admin email notifications for errors.', 'paypal-for-woocommerce'),
                    'default' => 'yes',
                    'description' => __('This will send a detailed error email to the WordPress site administrator if a PayPal API error occurs.', 'paypal-for-woocommerce'),
                    'desc_tip' => true
                ),
                'debug' => array(
                    'title' => __('Debug log', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'description' => sprintf(__('Log PayPal events, such as Payment, Refund inside %s Note: this may log personal information. We recommend using this for debugging purposes only and deleting the logs when finished.', 'paypal-for-woocommerce'), '<code>' . WC_Log_Handler_File::get_log_file_path('angelleye_ppcp') . '</code>'),
                    'options' => array(
                        'everything' => __('Everything', 'paypal-for-woocommerce'),
                        'errors_warnings_only' => __('Errors and Warnings Only', 'paypal-for-woocommerce'),
                        'disabled' => __('Disabled', 'paypal-for-woocommerce')
                    ),
                    'default' => 'everything'
                )
            );
            if(class_exists('WC_Pre_Orders') === false) {
                unset($this->angelleye_ppcp_gateway_setting['woo_pre_order']);
                unset($this->angelleye_ppcp_gateway_setting['woo_pre_order_payment_mode']);
            }
            if (wc_ship_to_billing_address_only() === true) {
                unset($this->angelleye_ppcp_gateway_setting['set_billing_address']);
            }
            if (angelleye_ppcp_is_local_server()) {
                unset($this->angelleye_ppcp_gateway_setting['live_onboarding']);
                unset($this->angelleye_ppcp_gateway_setting['live_disconnect']);
                unset($this->angelleye_ppcp_gateway_setting['sandbox_onboarding']);
                unset($this->angelleye_ppcp_gateway_setting['sandbox_disconnect']);
            }
            if (wc_coupons_enabled() === false) {
                unset($this->angelleye_ppcp_gateway_setting['order_review_page_enable_coupons']);
            }
            if (get_option('woocommerce_enable_guest_checkout') === 'no') {
                unset($this->angelleye_ppcp_gateway_setting['skip_final_review']);
                unset($this->angelleye_ppcp_gateway_setting['disable_term']);
            }
            if ((apply_filters('woocommerce_checkout_show_terms', true) && function_exists('wc_terms_and_conditions_checkbox_enabled') && wc_terms_and_conditions_checkbox_enabled()) === false) {
                //disable_term
                unset($this->angelleye_ppcp_gateway_setting['disable_term']);
            }
            return $this->angelleye_ppcp_gateway_setting;
        }

        public function get_size_listing($from, $to, $step, $postfix): array {
            $numbers = array('' => 'Default');
            for (; $from <= $to; $from = $from + $step) {
                $numbers[$from . $postfix] = $from . $postfix;
            }
            return $numbers;
        }

        public function angelleye_get_order_statuses() {
            return array_merge(["wc-default" => "Default"], wc_get_order_statuses());
        }
    }

}
