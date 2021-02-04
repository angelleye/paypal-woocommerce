<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {

    class WC_Gateway_PPCP_AngellEYE_Settings {

        public $angelleye_ppcp_gateway_setting;
        public $gateway_key;
        public $settings = array();
        protected static $_instance = null;

        public static function instance() {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        public function __construct() {
            $this->gateway_key = 'woocommerce_angelleye_ppcp_settings';
        }

        public function get($id, $default = false) {
            if (!$this->has($id)) {
                return $default;
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
                'title' => __('PayPal Complete Payments', 'paypal-for-woocommerce'),
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
                    'label' => __('Enable PayPal Complete Payments', 'paypal-for-woocommerce'),
                    'default' => 'no',
                ),
                'title' => array(
                    'title' => __('Title', 'paypal-for-woocommerce'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'paypal-for-woocommerce'),
                    'default' => __('PayPal Complete Payments', 'paypal-for-woocommerce'),
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
                ),
                'product_button_settings' => array(
                    'title' => __('Product Page Smart Button Settings', 'paypal-for-woocommerce'),
                    'class' => '',
                    'description' => __('Enable the Product specific button settings, and the options set will be applied to the PayPal Smart buttons on your Product pages.', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'class' => '',
                ),
                'enable_product_button' => array(
                    'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                    'class' => '',
                    'type' => 'checkbox',
                    'label' => __('Enable PayPal Smart Button on the Product pages.', 'paypal-for-woocommerce'),
                    'default' => 'yes',
                    'desc_tip' => true,
                    'description' => __('', 'paypal-for-woocommerce'),
                ),
                'product_disallowed_funding_methods' => array(
                    'title' => __('Hide Funding Method(s)', 'paypal-for-woocommerce'),
                    'type' => 'multiselect',
                    'class' => 'wc-enhanced-select angelleye_ppcp_product_button_settings',
                    'description' => __('Funding methods selected here will be hidden from buyers during checkout.', 'paypal-for-woocommerce'),
                    'default' => array(),
                    'desc_tip' => true,
                    'options' => array(
                        'card' => __('Credit or Debit Card', 'paypal-for-woocommerce'),
                        'credit' => __('PayPal Credit', 'paypal-for-woocommerce'),
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
                'cart_button_settings' => array(
                    'title' => __('Cart Page Button Settings', 'paypal-for-woocommerce'),
                    'class' => '',
                    'description' => __('Enable the Cart specific button settings, and the options set will be applied to the PayPal buttons on your Cart page.', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'class' => '',
                ),
                'enable_cart_button' => array(
                    'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                    'class' => '',
                    'type' => 'checkbox',
                    'label' => __('Enable PayPal Smart Button on the Cart page.', 'paypal-for-woocommerce'),
                    'default' => 'yes',
                    'desc_tip' => true,
                    'description' => __('Optionally override global button settings above and configure buttons specific to Cart page.', 'paypal-for-woocommerce'),
                ),
                'cart_disallowed_funding_methods' => array(
                    'title' => __('Hide Funding Method(s)', 'paypal-for-woocommerce'),
                    'type' => 'multiselect angelleye_ppcp_cart_button_settings',
                    'class' => 'wc-enhanced-select',
                    'description' => __('Funding methods selected here will be hidden from buyers during checkout.', 'paypal-for-woocommerce'),
                    'default' => array(),
                    'desc_tip' => true,
                    'options' => array(
                        'card' => __('Credit or Debit Card', 'paypal-for-woocommerce'),
                        'credit' => __('PayPal Credit', 'paypal-for-woocommerce'),
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
                'checkout_button_settings' => array(
                    'title' => __('Checkout Page Button Settings', 'paypal-for-woocommerce'),
                    'class' => '',
                    'description' => __('Enable the checkout specific button settings, and the options set will be applied to the PayPal buttons on your checkout page.', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'class' => '',
                ),
                'enable_checkout_button' => array(
                    'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                    'class' => '',
                    'type' => 'checkbox',
                    'label' => __('Enable PayPal Smart Button on the Checkout page.', 'paypal-for-woocommerce'),
                    'default' => 'yes',
                    'desc_tip' => true,
                    'description' => __('Optionally override global button settings above and configure buttons specific to checkout page.', 'paypal-for-woocommerce'),
                ),
                'checkout_disallowed_funding_methods' => array(
                    'title' => __('Hide Funding Method(s)', 'paypal-for-woocommerce'),
                    'type' => 'multiselect',
                    'class' => 'wc-enhanced-select angelleye_ppcp_checkout_button_settings',
                    'description' => __('Funding methods selected here will be hidden from buyers during checkout.', 'paypal-for-woocommerce'),
                    'default' => array(),
                    'desc_tip' => true,
                    'options' => array(
                        'card' => __('Credit or Debit Card', 'paypal-for-woocommerce'),
                        'credit' => __('PayPal Credit', 'paypal-for-woocommerce'),
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
                'mini_cart_button_settings' => array(
                    'title' => __('Mini Cart Page Button Settings', 'paypal-for-woocommerce'),
                    'class' => '',
                    'description' => __('Enable the Mini Cart specific button settings, and the options set will be applied to the PayPal buttons on your Mini Cart page.', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'class' => '',
                ),
                'enable_mini_cart_button' => array(
                    'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                    'class' => '',
                    'type' => 'checkbox',
                    'label' => __('Enable PayPal Smart Button on the Mini Cart page.', 'paypal-for-woocommerce'),
                    'default' => 'yes',
                    'desc_tip' => true,
                    'description' => __('Optionally override global button settings above and configure buttons specific to Mini Cart page.', 'paypal-for-woocommerce'),
                ),
                'mini_cart_disallowed_funding_methods' => array(
                    'title' => __('Hide Funding Method(s)', 'paypal-for-woocommerce'),
                    'type' => 'multiselect',
                    'class' => 'wc-enhanced-select angelleye_ppcp_mini_cart_button_settings',
                    'description' => __('Funding methods selected here will be hidden from buyers during checkout.', 'paypal-for-woocommerce'),
                    'default' => array(),
                    'desc_tip' => true,
                    'options' => array(
                        'card' => __('Credit or Debit Card', 'paypal-for-woocommerce'),
                        'credit' => __('PayPal Credit', 'paypal-for-woocommerce'),
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
                'advanced_settings' => array(
                    'title' => __('Advanced Settings', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'description' => '',
                ),
                'paymentaction' => array(
                    'title' => __('Payment action', 'paypal-for-woocommerce'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'description' => __('Choose whether you wish to capture funds immediately or authorize payment only.', 'paypal-for-woocommerce'),
                    'default' => 'capture',
                    'desc_tip' => true,
                    'options' => array(
                        'capture' => __('Capture', 'paypal-for-woocommerce'),
                        'authorize' => __('Authorize', 'paypal-for-woocommerce'),
                    ),
                ),
                'invoice_prefix' => array(
                    'title' => __('Invoice prefix', 'paypal-for-woocommerce'),
                    'type' => 'text',
                    'description' => __('Please enter a prefix for your invoice numbers. If you use your PayPal account for multiple stores ensure this prefix is unique as PayPal will not allow orders with the same invoice number.', 'paypal-for-woocommerce'),
                    'default' => 'WC-PPCP',
                    'desc_tip' => true,
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
                    'default' => false,
                    'desc_tip' => true,
                    'description' => __(
                            'If you enable this setting, PayPal will be instructed not to allow the buyer to use funding sources that take additional time to complete (for example, eChecks). Instead, the buyer will be required to use an instant funding source, such as an instant transfer, a credit/debit card, or PayPal Credit.', 'paypal-for-woocommerce'
                    ),
                    'label' => __('Require Instant Payment', 'paypal-for-woocommerce'),
                ),
                'enable_advanced_card_payments' => array(
                    'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable advanced credit and debit card payments', 'paypal-for-woocommerce'),
                    'default' => 'no',
                    'description' => __('Currently PayPal support Unbranded payments in US, AU, UK, FR, IT and ES only. <br> <br>Advanced credit and debit cards requires that your business account be evaluated and approved by PayPal. <br><a target="_blank" href="https://www.sandbox.paypal.com/bizsignup/entry/product/ppcp">Enable for Sandbox Account</a> <span> | </span> <a target="_blank" href="https://www.paypal.com/bizsignup/entry/product/ppcp">Enable for Live Account</a><br>', 'paypal-for-woocommerce'),
                ),
                'threed_secure_enabled' => array(
                    'title' => __('3D Secure', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable 3D Secure', 'paypal-for-woocommerce'),
                    'description' => __('If you are based in Europe, you are subjected to PSD2. PayPal recommends this option', 'paypal-for-woocommerce'),
                    'default' => 'no',
                ),
                'debug' => array(
                    'title' => __('Debug log', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable logging', 'paypal-for-woocommerce'),
                    'default' => 'no',
                    'description' => sprintf(__('Log PayPal events, such as Webhook, Payment, Refund inside %s Note: this may log personal information. We recommend using this for debugging purposes only and deleting the logs when finished.', 'paypal-for-woocommerce'), '<code>' . WC_Log_Handler_File::get_log_file_path('angelleye_ppcp') . '</code>'),
                ),
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