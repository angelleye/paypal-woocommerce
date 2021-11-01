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

        public function get_load() {
            return get_option($this->gateway_key, array());
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
            $skip_final_review_option_not_allowed_guest_checkout = '';
            $skip_final_review_option_not_allowed_terms = '';
            $skip_final_review_option_not_allowed_tokenized_payments = '';
            $woocommerce_enable_guest_checkout = get_option('woocommerce_enable_guest_checkout');
            if ('yes' === get_option('woocommerce_registration_generate_username') && 'yes' === get_option('woocommerce_registration_generate_password')) {
                $woocommerce_enable_guest_checkout = 'yes';
            }
            if (isset($woocommerce_enable_guest_checkout) && ( $woocommerce_enable_guest_checkout === "no" )) {
                $skip_final_review_option_not_allowed_guest_checkout = ' (The WooCommerce guest checkout option is disabled.  Therefore, the review page is required for login / account creation, and this option will be overridden.)';
            }
            if (apply_filters('woocommerce_checkout_show_terms', true) && function_exists('wc_terms_and_conditions_checkbox_enabled') && wc_terms_and_conditions_checkbox_enabled()) {
                $skip_final_review_option_not_allowed_terms = ' (You currently have a Terms &amp; Conditions page set, which requires the review page, and will override this option.)';
            }
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
                'live_merchant_id' => array(
                    'title' => __('Live Merchant ID', 'paypal-for-woocommerce'),
                    'type' => 'text',
                    'description' => '',
                    'default' => '',
                    'custom_attributes' => array('readonly' => 'readonly'),
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
                'smart_button_header' => array(
                    'title' => __('Smart Payment Buttons Settings', 'paypal-for-woocommerce'),
                    'class' => '',
                    'description' => __('', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'class' => 'ppcp_separator_heading',
                ),
                'product_button_settings' => array(
                    'title' => __('Product Page', 'paypal-for-woocommerce'),
                    'class' => '',
                    'description' => __('Enable the Product specific button settings, and the options set will be applied to the PayPal Smart buttons on your Product pages.', 'paypal-for-woocommerce'),
                    'type' => 'title',
                    'class' => '',
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
                    'title' => __('Cart Page', 'paypal-for-woocommerce'),
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
                    'type' => 'multiselect',
                    'class' => 'wc-enhanced-select angelleye_ppcp_cart_button_settings',
                    'description' => __('Funding methods selected here will be hidden from buyers during checkout.', 'paypal-for-woocommerce'),
                    'default' => '',
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
                    'title' => __('Checkout Page', 'paypal-for-woocommerce'),
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
                    'default' => '',
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
                    'title' => __('Mini Cart Page', 'paypal-for-woocommerce'),
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
                    'default' => '',
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
                'pay_later_messaging_settings' => array(
                    'title' => __('Pay Later Messaging Settings', 'paypal-for-woocommerce'),
                    'class' => '',
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
                    'options' => array(
                        'capture' => __('Capture', 'paypal-for-woocommerce'),
                        'authorize' => __('Authorize', 'paypal-for-woocommerce'),
                    ),
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
                    'description' => __('By default, users will be returned from PayPal and presented with a final review page which includes shipping and tax in the order details. Enable this option to eliminate this page in the checkout process.  This only applies when the WooCommerce checkout page is skipped.  If the WooCommerce checkout page is used, the final review page will always be skipped.') . '<br /><b class="final_review_notice"><span class="guest_checkout_notice">' . $skip_final_review_option_not_allowed_guest_checkout . '</span></b>' . '<b class="final_review_notice"><span class="terms_notice">' . $skip_final_review_option_not_allowed_terms . '</span></b>' . '<b class="final_review_notice"><span class="tokenized_payments_notice">' . $skip_final_review_option_not_allowed_tokenized_payments . '</span></b>',
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
                    'default' => 'no',
                    'desc_tip' => false,
                ),
                'enable_advanced_card_payments' => array(
                    'title' => __('Advanced Credit Cards', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable advanced credit and debit card payments.', 'paypal-for-woocommerce'),
                    'default' => 'no',
                    'description' => 'PayPal currently supports direct credit card processing for US, AU, UK, FR, IT, CA and ES. <br> <br>If you have not already been approved for Advanced Credit Cards, please use the link below to apply. <br><br><span><a target="_blank" href="https://www.angelleye.com/advanced-credit-card-setup-for-paypal/">Apply for Advanced Credit Cards</a>',
                ),
                'threed_secure_enabled' => array(
                    'title' => __('3D Secure', 'paypal-for-woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable 3D Secure', 'paypal-for-woocommerce'),
                    'description' => __('Enable 3D Secure for additional security on direct credit card checkouts. In Europe this is required.', 'paypal-for-woocommerce'),
                    'default' => 'no',
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
            if (angelleye_ppcp_is_local_server()) {
                unset($this->angelleye_ppcp_gateway_setting['live_onboarding']);
                unset($this->angelleye_ppcp_gateway_setting['live_disconnect']);
                unset($this->angelleye_ppcp_gateway_setting['sandbox_onboarding']);
                unset($this->angelleye_ppcp_gateway_setting['sandbox_disconnect']);
            }
            if (wc_coupons_enabled() === false) {
                unset($this->angelleye_ppcp_gateway_setting['order_review_page_enable_coupons']);
            }
            if ((apply_filters('woocommerce_checkout_show_terms', true) && function_exists('wc_terms_and_conditions_checkbox_enabled') && wc_terms_and_conditions_checkbox_enabled()) === false) {
                //disable_term
                unset($this->angelleye_ppcp_gateway_setting['disable_term']);
            }
            return $this->angelleye_ppcp_gateway_setting;
        }

    }

}