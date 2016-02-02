<?php

/**
 * WC_Gateway_Braintree_AngellEYE class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_Braintree_AngellEYE extends WC_Payment_Gateway {

    /**
     * Constuctor
     */
    function __construct() {
        $this->id = 'braintree';
        $this->icon = apply_filters('woocommerce_braintree_icon', plugins_url('assets/images/cards.png', __DIR__));
        $this->has_fields = true;
        $this->method_title = 'Braintree';
        $this->method_description = 'Braintree Payment Gateway authorizes credit card payments and processes them securely with your merchant account.';
        $this->supports = array(
            'products',
            'refunds'
        );
        $this->available_card_types = array(
            'VISA' => 'Visa',
            'MC' => 'MasterCard',
            'AMEX' => 'American Express',
            'DISC' => 'Discover',
            'DINERS' => 'Diners',
            'JCB' => 'JCB',
        );

        $this->available_card_types = apply_filters('woocommerce_braintree_card_types', $this->available_card_types);

        $this->iso4217 = apply_filters('woocommerce_braintree_iso_currencies', array(
            'AUD' => '036',
            'CAD' => '124',
            'CZK' => '203',
            'DKK' => '208',
            'EUR' => '978',
            'HUF' => '348',
            'JPY' => '392',
            'NOK' => '578',
            'NZD' => '554',
            'PLN' => '985',
            'GBP' => '826',
            'SGD' => '702',
            'SEK' => '752',
            'CHF' => '756',
            'USD' => '840'
        ));


        // Load the form fields
        $this->init_form_fields();

        // Load the settings
        $this->init_settings();

        // Get setting values
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->sandbox = $this->get_option('sandbox');
        $this->environment = $this->sandbox == 'no' ? 'production' : 'sandbox';
        $this->merchant_id = $this->sandbox == 'no' ? $this->get_option('merchant_id') : $this->get_option('sandbox_merchant_id');
        $this->private_key = $this->sandbox == 'no' ? $this->get_option('private_key') : $this->get_option('sandbox_private_key');
        $this->public_key = $this->sandbox == 'no' ? $this->get_option('public_key') : $this->get_option('sandbox_public_key');

        // Hooks

        add_action('admin_notices', array($this, 'checks'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('wp_enqueue_scripts', array($this, 'braintree_enqueue_scripts'));
    }

    /**
     * Admin Panel Options
     */
    public function admin_options() {
        ?>
        <h3><?php _e('Braintree', 'woocommerce'); ?></h3>
        <p><?php _e($this->method_description, 'woocommerce'); ?></p>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
            <script type="text/javascript">
                jQuery('#woocommerce_braintree_sandbox').change(function () {
                    var sandbox = jQuery('#woocommerce_braintree_sandbox_public_key, #woocommerce_braintree_sandbox_private_key, #woocommerce_braintree_sandbox_merchant_id').closest('tr'),
                            production = jQuery('#woocommerce_braintree_public_key, #woocommerce_braintree_private_key, #woocommerce_braintree_merchant_id').closest('tr');

                    if (jQuery(this).is(':checked')) {
                        sandbox.show();
                        production.hide();
                    } else {
                        sandbox.hide();
                        production.show();
                    }
                }).change();
            </script>
        </table> <?php
    }

    /**
     * Check if SSL is enabled and notify the user
     */
    public function checks() {
        if ($this->enabled == 'no') {
            return;
        }

        // PHP Version
        if (version_compare(phpversion(), '5.2.1', '<')) {
            echo '<div class="error"><p>' . sprintf(__('Braintree Error: Braintree requires PHP 5.2.1 and above. You are using version %s.', 'woocommerce'), phpversion()) . '</p></div>';
        }

        // Show message if enabled and FORCE SSL is disabled and WordpressHTTPS plugin is not detected
        elseif ('no' == get_option('woocommerce_force_ssl_checkout') && !class_exists('WordPressHTTPS')) {
            echo '<div class="error"><p>' . sprintf(__('Braintree is enabled, but the <a href="%s">force SSL option</a> is disabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate - Braintree will only work in sandbox mode.', 'woocommerce'), admin_url('admin.php?page=wc-settings&tab=checkout')) . '</p></div>';
        }
    }

    /**
     * Check if this gateway is enabled
     */
    public function is_available() {
        if ('yes' != $this->enabled) {
            return false;
        }

        if (!is_ssl() && 'yes' != $this->sandbox) {
            return false;
        }

        // Required fields check
        if (!$this->merchant_id || !$this->public_key || !$this->private_key) {
            return false;
        }

        return true;
    }

    /**
     * Validate the payment form
     */
    public function validate_fields() {
        try {

            $card = $this->get_posted_card();

            if (empty($card->exp_month) || empty($card->exp_year)) {
                throw new Exception(__('Card expiration date is invalid', 'woocommerce-gateway-paypal-pro'));
            }

            // Validate values
            if (!ctype_digit($card->cvc)) {
                throw new Exception(__('Card security code is invalid (only digits are allowed)', 'woocommerce-gateway-paypal-pro'));
            }

            if (!ctype_digit($card->exp_month) || !ctype_digit($card->exp_year) || $card->exp_month > 12 || $card->exp_month < 1 || $card->exp_year < date('y')) {
                throw new Exception(__('Card expiration date is invalid', 'woocommerce-gateway-paypal-pro'));
            }

            if (empty($card->number) || !ctype_digit($card->number)) {
                throw new Exception(__('Card number is invalid', 'woocommerce-gateway-paypal-pro'));
            }

            return true;
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'label' => __('Enable Braintree Payment Gateway', 'woocommerce'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                'default' => __('Braintree Credit card', 'woocommerce'),
                'desc_tip' => true
            ),
            'description' => array(
                'title' => __('Description', 'woocommerce'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
                'default' => 'Pay securely with your credit card.',
                'desc_tip' => true
            ),
            'sandbox' => array(
                'title' => __('Sandbox', 'woocommerce'),
                'label' => __('Enable Sandbox Mode', 'woocommerce'),
                'type' => 'checkbox',
                'description' => __('Place the payment gateway in sandbox mode using sandbox API keys (real payments will not be taken).', 'woocommerce'),
                'default' => 'yes'
            ),
            'sandbox_merchant_id' => array(
                'title' => __('Sandbox Merchant ID', 'woocommerce'),
                'type' => 'text',
                'description' => __('Get your API keys from your Braintree account.', 'woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'sandbox_public_key' => array(
                'title' => __('Sandbox Public Key', 'woocommerce'),
                'type' => 'text',
                'description' => __('Get your API keys from your Braintree account.', 'woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'sandbox_private_key' => array(
                'title' => __('Sandbox Private Key', 'woocommerce'),
                'type' => 'text',
                'description' => __('Get your API keys from your Braintree account.', 'woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'merchant_id' => array(
                'title' => __('Live Merchant ID', 'woocommerce'),
                'type' => 'text',
                'description' => __('Get your API keys from your Braintree account.', 'woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'public_key' => array(
                'title' => __('Live Public Key', 'woocommerce'),
                'type' => 'text',
                'description' => __('Get your API keys from your Braintree account.', 'woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'private_key' => array(
                'title' => __('Live Private Key', 'woocommerce'),
                'type' => 'text',
                'description' => __('Get your API keys from your Braintree account.', 'woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
        );

        // card types support

        $this->form_fields = $this->add_card_types_form_fields($this->form_fields);
    }

    public function add_card_types_form_fields($form_fields) {



        $form_fields['card_types'] = array(
            'title' => _x('Accepted Card Types', 'Supports card types', 'woocommerce'),
            'type' => 'multiselect',
            'desc_tip' => _x('Select which card types you accept.', 'Supports card types', 'woocommerce'),
            'default' => array_keys($this->get_available_card_types()),
            'class' => 'wc-enhanced-select chosen_select',
            'css' => 'width: 350px;',
            'options' => $this->get_available_card_types(),
        );

        return $form_fields;
    }

    public function get_available_card_types() {



        // default available card types
        if (!isset($this->available_card_types)) {

            $this->available_card_types = array(
                'VISA' => 'Visa',
                'MC' => 'MasterCard',
                'AMEX' => 'American Express',
                'DISC' => 'Discover',
                'DINERS' => 'Diners',
                'JCB' => 'JCB',
            );
        }

        // return the default card types
        return apply_filters('wc_' . $this->id . '_available_card_types', $this->available_card_types);
    }

    /**
     * Initialise Credit Card Payment Form Fields
     */
    public function payment_fields() {
        $fields = array();




        $fields = array(
          
            'card-number-field' => '<p class="form-row form-row-wide">
				<label for="' . esc_attr($this->id) . '-card-number">' . __('Card Number', 'woocommerce') . ' <span class="required">*</span></label>
				<input id="' . esc_attr($this->id) . '-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="•••• •••• •••• ••••" data-braintree-name="number" />
			</p>',
            'card-expiry-field' => '<p class="form-row form-row-first">
				<label for="' . esc_attr($this->id) . '-card-expiry">' . __('Expiry (MM/YY)', 'woocommerce') . ' <span class="required">*</span></label>
				<input id="' . esc_attr($this->id) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="' . esc_attr__('MM / YY', 'woocommerce') . '" data-braintree-name="expiration_date" />
			</p>',
            'card-cvc-field' => '<p class="form-row form-row-last">
				<label for="' . esc_attr($this->id) . '-card-cvc">' . __('Card Code', 'woocommerce') . ' <span class="required">*</span></label>
				<input id="' . esc_attr($this->id) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="' . esc_attr__('CVC', 'woocommerce') . '" data-braintree-name="cvv" />
			</p>'
        );

        $this->credit_card_form(array(), $fields);
    }

    private function get_posted_card() {
        $card_number = isset($_POST['number']) ? wc_clean($_POST['number']) : '';
        $card_cvc = isset($_POST['cvv']) ? wc_clean($_POST['cvv']) : '';
        $card_expiry = isset($_POST['expiration_date']) ? wc_clean($_POST['expiration_date']) : '';

        // Format values
        $card_number = str_replace(array(' ', '-'), '', $card_number);
        $card_expiry = array_map('trim', explode('/', $card_expiry));
        $card_exp_month = str_pad($card_expiry[0], 2, "0", STR_PAD_LEFT);
        $card_exp_year = isset($card_expiry[1]) ? $card_expiry[1] : '';



        if (strlen($card_exp_year) == 2) {
            $card_exp_year += 2000;
        }


        return (object) array(
                    'number' => $card_number,
                    'type' => '',
                    'cvc' => $card_cvc,
                    'exp_month' => $card_exp_month,
                    'exp_year' => $card_exp_year,
        );
    }

    /**
     * Process the payment
     */
    public function process_payment($order_id) {
        global $woocommerce;

        $order = new WC_Order($order_id);

        require_once( 'lib/Braintree/Braintree.php' );

        Braintree_Configuration::environment($this->environment);
        Braintree_Configuration::merchantId($this->merchant_id);
        Braintree_Configuration::publicKey($this->public_key);
        Braintree_Configuration::privateKey($this->private_key);

        $card = $this->get_posted_card();

        $result = Braintree_Transaction::sale(array(
                    'amount' => $order->order_total,
                    'creditCard' => array(
                        'number' => $card->number,
                        'cardholderName' => $card->number,
                        'expirationDate' => $card->exp_month . '/' . $card->exp_year,
                        'cvv' => $card->cvc
                    ),
        ));


        if ($result->success) {
            $order->payment_complete($result->transaction->id);
            $order->add_order_note(sprintf(__('%s payment approved! Trnsaction ID: %s', 'woocommerce'), $this->title, $result->transaction->id));
            WC()->cart->empty_cart();
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        } else if ($result->transaction) {
            $order->add_order_note(sprintf(__('%s payment declined.<br />Error: %s<br />Code: %s', 'woocommerce'), $this->title, $result->message, $result->transaction->processorResponseCode));
        } else {
            foreach (($result->errors->deepAll()) as $error) {
                wc_add_notice("Validation error - " . $error->message, 'error');
            }
            return array(
                'result' => 'fail',
                'redirect' => ''
            );
        }
    }

    public function braintree_enqueue_scripts() {

        require_once( 'lib/Braintree/Braintree.php' );

        Braintree_Configuration::environment($this->environment);
        Braintree_Configuration::merchantId($this->merchant_id);
        Braintree_Configuration::publicKey($this->public_key);
        Braintree_Configuration::privateKey($this->private_key);

        $clientToken = Braintree_ClientToken::generate();
        
        wp_enqueue_script('braintree-main-js', 'https://js.braintreegateway.com/v2/braintree.js', array(), '101', false);
        wp_enqueue_script('braintree-main-setup', plugins_url('assets/js/braintree-main-js.js', __DIR__), array(), '101', true);
        if (wp_script_is('braintree-main-setup')) {
            wp_localize_script('braintree-main-setup', 'paypal_for_woocommerce_braintree', apply_filters('paypal_for_woocommerce_braintree_params', array(
                'Braintree_ClientToken' => $clientToken
            )));
        }
    }

}
