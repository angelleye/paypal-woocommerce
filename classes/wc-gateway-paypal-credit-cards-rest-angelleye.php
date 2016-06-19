<?php

/**
 * WC_Gateway_PayPal_Credit_Card_Rest_AngellEYE class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_PayPal_Credit_Card_Rest_AngellEYE extends WC_Payment_Gateway {

    /**
     * Constuctor
     */
    protected $paypal_rest_api;

    function __construct() {
        $this->id = 'paypal_credit_card_rest';
        $this->icon = apply_filters('woocommerce_braintree_icon', plugins_url('/assets/images/cards.png', plugin_basename(dirname(__FILE__))));
        $this->has_fields = true;
        $this->method_title = 'PayPal Credit Card (REST)';
        $this->woocommerce_paypal_supported_currencies = array('AUD', 'BRL', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MYR', 'MXN', 'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'THB', 'TRY', 'USD');
        $this->method_description = __('PayPal direct credit card payments using the REST API.  This allows you to accept credit cards directly on the site without the need for the full Payments Pro.', 'paypal-for-woocommerce');
        $this->supports = array(
            'products',
            'refunds'
        );
        $this->init_form_fields();
        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->testmode = 'yes' === $this->get_option('testmode', 'no');
        $this->mode = $this->testmode == 'yes' ? "SANDBOX" : "LIVE";
        $this->debug = 'yes' === $this->get_option('debug', 'no');
        if ($this->testmode) {
            $this->rest_client_id = $this->get_option('rest_client_id_sandbox', false);
            $this->rest_secret_id = $this->get_option('rest_secret_id_sandbox', false);
        } else {
            $this->rest_client_id = $this->get_option('rest_client_id', false);
            $this->rest_secret_id = $this->get_option('rest_secret_id', false);
        }
        add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('admin_notices', array($this, 'checks'));
    }

    /**
     * @since    1.2
     */
    public function init_form_fields() {
        $this->form_fields = AngellEYE_Utility::angelleye_paypal_credit_card_rest_setting_fields();
    }

    /**
     * @since    1.2
     */
    public function admin_options() {
        if ($this->is_valid_for_use()) {
            ?>
            <h3><?php echo (!empty($this->method_title) ) ? $this->method_title : __('Settings', 'paypal-for-woocommerce'); ?></h3>
            <?php echo (!empty($this->method_description) ) ? wpautop($this->method_description) : ''; ?>
            <table class="form-table">
                <?php $this->generate_settings_html(); ?>
            </table>
            <script type="text/javascript">
                jQuery('#woocommerce_paypal_credit_card_rest_testmode').change(function () {
                    var sandbox = jQuery('#woocommerce_paypal_credit_card_rest_rest_client_id_sandbox, #woocommerce_paypal_credit_card_rest_rest_secret_id_sandbox').closest('tr'),
                            production = jQuery('#woocommerce_paypal_credit_card_rest_rest_client_id, #woocommerce_paypal_credit_card_rest_rest_secret_id').closest('tr');
                    if (jQuery(this).is(':checked')) {
                        sandbox.show();
                        production.hide();
                    } else {
                        sandbox.hide();
                        production.show();
                    }
                }).change();
            </script><?php
            } else {
                ?><div class="inline error"><p><strong><?php _e('Gateway Disabled', 'paypal-for-woocommerce'); ?></strong>: <?php _e('PayPal does not support your store currency.', 'paypal-for-woocommerce'); ?></p></div> <?php
        }
    }

    /**
     * @since    1.2
     * Check if SSL is enabled and notify the user
     */
    public function checks() {
        $this->add_rest_api_utility();
        if ($this->enabled == 'no') {
            return;
        }
        if (version_compare(phpversion(), '5.2.1', '<')) {
            echo '<div class="error"><p>' . sprintf(__('PayPal Credit Card (REST):  PayPal Credit Card (REST) requires PHP 5.2.1 and above. You are using version %s.', 'paypal-for-woocommerce'), phpversion()) . '</p></div>';
        }
        $this->paypal_rest_api->add_dependencies_admin_notices();
    }

    /**
     * @since    1.2
     * @return boolean
     */
    public function is_available() {
        if ($this->enabled === "yes") {
            if (!$this->rest_client_id || !$this->rest_secret_id) {
                return false;
            }
            if (!in_array(get_woocommerce_currency(), apply_filters('paypal_rest_api_supported_currencies', $this->woocommerce_paypal_supported_currencies))) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * @since    1.2
     */
    public function payment_fields() {
        if ($this->description) {
            echo wpautop(wptexturize($this->description));
        }
        if(class_exists('WC_Payment_Gateway_CC')) {
            $cc_form = new WC_Payment_Gateway_CC;
            $cc_form->id       = $this->id;
            $cc_form->supports = $this->supports;
            $cc_form->form();
        } else {
            $this->credit_card_form();
        }
    }

    /**
     * @since    1.2
     * @return type
     */
    public function is_valid_for_use() {
        return in_array(get_woocommerce_currency(), apply_filters('paypal_rest_api_supported_currencies', $this->woocommerce_paypal_supported_currencies));
    }

    /**
     * @since    1.2
     * @return boolean
     * @throws Exception
     */
    public function validate_fields() {
        $this->add_rest_api_utility();
        try {
            $card = $this->paypal_rest_api->get_posted_card();
            if (empty($card->exp_month) || empty($card->exp_year)) {
                throw new Exception(__('Card expiration date is invalid', 'woocommerce-gateway-paypal-pro'));
            }
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
     * @since    1.2
     * Process the payment
     */
    public function process_payment($order_id) {
        $this->add_rest_api_utility();
        $order = wc_get_order($order_id);
        $card = $this->paypal_rest_api->get_posted_card();
        return $this->do_payment($order, $card);
    }

    /**
     * @since    1.2
     * @param type $order
     * @param type $card
     */
    public function do_payment($order, $card) {
        $this->add_rest_api_utility();
        $this->paypal_rest_api->create_payment($order, $card);
    }

    /**
     * @since    1.2
     * @param type $order_id
     * @param type $amount
     * @param type $reason
     * @return type
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        $this->add_rest_api_utility();
        $return = $this->paypal_rest_api->payment_refund($order_id, $amount, $reason);
        if ($return) {
            return $return;
        }
    }

    /**
     * @since    1.2
     */
    public function add_rest_api_utility() {
        if (empty($this->paypal_rest_api)) {
            if (class_exists('PayPal_Rest_API_Utility')) {
                $this->paypal_rest_api = new PayPal_Rest_API_Utility();
            } else {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/paypal-rest-api-utility.php' );
                $this->paypal_rest_api = new PayPal_Rest_API_Utility();
            }
        }
    }

}