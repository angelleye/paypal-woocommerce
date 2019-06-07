<?php

/**
 * WC_Gateway_PayPal_Pro_PayFlow class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_PayPal_Pro_PayFlow_AngellEYE extends WC_Payment_Gateway_CC {

    /**
     * __construct function.
     *
     * @access public
     * @return void
     */
    public $customer_id;
    public $PayPal;
    public $credentials;
    public $gateway;

    function __construct() {
        $this->id = 'paypal_pro_payflow';
        $this->method_title = __('PayPal Payments Pro 2.0 (PayFlow)', 'paypal-for-woocommerce');
        $this->method_description = __('PayPal Payments Pro allows you to accept credit cards directly on your site without any redirection through PayPal.  You host the checkout form on your own web server, so you will need an SSL certificate to ensure your customer data is protected.', 'paypal-for-woocommerce');
        $this->has_fields = true;

        $this->allowed_currencies = apply_filters('woocommerce_paypal_pro_allowed_currencies', array('USD', 'EUR', 'GBP', 'CAD', 'JPY', 'AUD', 'NZD'));


        // Load the form fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Get setting values
        $this->send_items = 'yes' === $this->get_option('send_items', 'yes');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->testmode = 'yes' === $this->get_option('testmode', 'no');
        if ($this->testmode == false) {
            $this->testmode = AngellEYE_Utility::angelleye_paypal_for_woocommerce_is_set_sandbox_product();
        }
        $this->invoice_id_prefix = $this->get_option('invoice_id_prefix', '');
        $this->debug = 'yes' === $this->get_option('debug', 'no');
        $this->error_email_notify = 'yes' === $this->get_option('error_email_notify', 'no');
        $this->error_display_type = $this->get_option('error_display_type', 'no');
        if($this->send_items === false) {
            $this->subtotal_mismatch_behavior = 'drop';
        } else {
            $this->subtotal_mismatch_behavior = $this->get_option('subtotal_mismatch_behavior', 'add');
        }
        $this->payment_action = $this->get_option('payment_action', 'Sale');
        $this->payment_action_authorization = $this->get_option('payment_action_authorization', 'Full Authorization');

        //fix ssl for image icon
        $this->icon = $this->get_option('card_icon', plugins_url('/assets/images/payflow-cards.png', plugin_basename(dirname(__FILE__))));
        if ( is_ssl() || 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) ) {
            $this->icon = preg_replace("/^http:/i", "https:", $this->icon);
        }
        $this->icon = apply_filters('woocommerce_paypal_pro_payflow_icon', $this->icon);
        $this->paypal_partner = $this->get_option('paypal_partner', 'PayPal');
        $this->paypal_vendor = $this->get_option('paypal_vendor');
        $this->paypal_user = $this->get_option('paypal_user', $this->paypal_vendor);
        $this->paypal_password = $this->get_option('paypal_password');
        if ($this->testmode == true) {
            $this->paypal_vendor = $this->get_option('sandbox_paypal_vendor');
            $this->paypal_partner = $this->get_option('sandbox_paypal_partner', 'PayPal');
            $this->paypal_password = $this->get_option('sandbox_paypal_password');
            $this->paypal_user = $this->get_option('sandbox_paypal_user', $this->paypal_vendor);
        }

        $this->supports = array(
            'subscriptions',
            'products',
            'refunds',
            'subscription_cancellation',
            'subscription_reactivation',
            'subscription_suspension',
            'subscription_amount_changes',
            'subscription_payment_method_change', // Subs 1.n compatibility.
            'subscription_payment_method_change_customer',
            'subscription_payment_method_change_admin',
            'subscription_date_changes',
            'multiple_subscriptions',
        );

        $this->enable_tokenized_payments = $this->get_option('enable_tokenized_payments', 'no');
        if ($this->enable_tokenized_payments == 'yes') {
            $this->supports = array_merge($this->supports, array('add_payment_method', 'tokenization'));
        }

        $this->softdescriptor = $this->get_option('softdescriptor', '');
        $this->avs_cvv2_result_admin_email = 'yes' === $this->get_option('avs_cvv2_result_admin_email', 'no');
        $this->Force_tls_one_point_two = get_option('Force_tls_one_point_two', 'no');
        $this->enable_cardholder_first_last_name = 'yes' === $this->get_option('enable_cardholder_first_last_name', 'no');
        $this->is_encrypt = $this->get_option('is_encrypt', 'no');
        $this->credit_card_month_field = $this->get_option('credit_card_month_field', 'names');
        $this->credit_card_year_field = $this->get_option('credit_card_year_field', 'four_digit');
        $this->fraud_management_filters = $this->get_option('fraud_management_filters', 'place_order_on_hold_for_further_review');
        $this->pending_authorization_order_status = $this->get_option('pending_authorization_order_status', 'On Hold');
        $this->default_order_status = $this->get_option('default_order_status', 'Processing');

        /* 2.0.0 */
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));


        add_filter('woocommerce_credit_card_form_fields', array($this, 'angelleye_paypal_pro_payflow_credit_card_form_fields'), 10, 2);
        add_filter('woocommerce_settings_api_sanitized_fields_' . $this->id, array($this, 'angelleye_paypal_pro_payflow_encrypt_gateway_api'), 10, 1);
        if ($this->enable_cardholder_first_last_name) {
            add_action('woocommerce_credit_card_form_start', array($this, 'angelleye_woocommerce_credit_card_form_start'), 10, 1);
        }
        if ($this->avs_cvv2_result_admin_email) {
            add_action('woocommerce_email_before_order_table', array($this, 'angelleye_paypal_pro_payflow_email_instructions'), 10, 3);
        }

        $this->customer_id;
        if (class_exists('WC_Gateway_Calculation_AngellEYE')) {
            $this->calculation_angelleye = new WC_Gateway_Calculation_AngellEYE($this->id, $this->subtotal_mismatch_behavior);
        } else {
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/wc-gateway-calculations-angelleye.php' );
            $this->calculation_angelleye = new WC_Gateway_Calculation_AngellEYE($this->id, $this->subtotal_mismatch_behavior);
        }
        $this->fraud_codes = array('125', '128', '131', '126', '127');
        $this->fraud_error_codes = array('125', '128', '131');
        $this->fraud_warning_codes = array('126', '127');
        do_action( 'angelleye_paypal_for_woocommerce_multi_account_api_' . $this->id, $this, null, null );

    }

    public function add_log($message, $level = 'info') {
        if ($this->debug) {
            if (version_compare(WC_VERSION, '3.0', '<')) {
                if (empty($this->log)) {
                    $this->log = new WC_Logger();
                }
                $this->log->add('paypal_pro_payflow', $message);
            } else {
                if (empty($this->log)) {
                    $this->log = wc_get_logger();
                }
                $this->log->log($level,sprintf(__('PayPal for WooCommerce Version: %s', 'paypal-for-woocommerce'), VERSION_PFW),array('source' => 'paypal_pro_payflow'));
                $this->log->log($level,sprintf(__('WooCommerce Version: %s', 'paypal-for-woocommerce'), WC_VERSION),array('source' => 'paypal_pro_payflow'));
                $this->log->log($level,'Test Mode: ' . $this->testmode,array('source' => 'paypal_pro_payflow'));
                $this->log->log($level, $message, array('source' => 'paypal_pro_payflow'));
            }
        }
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields() {
        $this->send_items_value = ! empty( $this->settings['send_items'] ) && 'yes' === $this->settings['send_items'] ? 'yes' : 'no';
        $this->send_items = 'yes' === $this->send_items_value;
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                'label' => __('Enable PayPal Pro Payflow Edition', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'paypal-for-woocommerce'),
                'default' => __('Credit card', 'paypal-for-woocommerce')
            ),
            'description' => array(
                'title' => __('Description', 'paypal-for-woocommerce'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'paypal-for-woocommerce'),
                'default' => __('Pay with your credit card.', 'paypal-for-woocommerce')
            ),

            'invoice_id_prefix' => array(
                'title' => __('Invoice ID Prefix', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Add a prefix to the invoice ID sent to PayPal. This can resolve duplicate invoice problems when working with multiple websites on the same PayPal account.', 'paypal-for-woocommerce'),
            ),
            'card_icon' => array(
                'title' => __('Card Icon', 'paypal-for-woocommerce'),
                'type' => 'text',
                'default' => plugins_url('/assets/images/payflow-cards.png', plugin_basename(dirname(__FILE__))),
                'class' => 'button_upload'
            ),

            'error_email_notify' => array(
                'title' => __('Error Email Notifications', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable admin email notifications for errors.', 'paypal-for-woocommerce'),
                'default' => 'yes',
                'description' => __('This will send a detailed error email to the WordPress site administrator if a PayPal API error occurs.', 'paypal-for-woocommerce')
            ),
            'error_display_type' => array(
                'title' => __('Error Display Type', 'paypal-for-woocommerce'),
                'type' => 'select',
                'label' => __('Display detailed or generic errors', 'paypal-for-woocommerce'),
                'class' => 'error_display_type_option wc-enhanced-select',
                'options' => array(
                    'detailed' => 'Detailed',
                    'generic' => 'Generic'
                ),
                'description' => __('Detailed displays actual errors returned from PayPal.  Generic displays general errors that do not reveal details
									and helps to prevent fraudulant activity on your site.', 'paypal-for-woocommerce')
            ),
             'testmode' => array(
                'title' => __('PayPal Sandbox', 'paypal-for-woocommerce'),
                'label' => __('Enable PayPal Sandbox/Test Mode', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Place the payment gateway in development mode.', 'paypal-for-woocommerce'),
                'default' => 'no'
            ),
            'sandbox_paypal_partner' => array(
                'title' => __('Partner', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('The ID provided to you by the authorized PayPal Reseller who registered you for the Payflow SDK. If you purchased your account directly from PayPal, use PayPal or leave blank.', 'paypal-for-woocommerce'),
                'default' => 'PayPal',
                'custom_attributes' => array( 'autocomplete' => 'new-password'),
            ),
            'sandbox_paypal_vendor' => array(
                'title' => __('Vendor (Merchant Login)', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Your merchant login ID that you created when you registered for the account.', 'paypal-for-woocommerce'),
                'custom_attributes' => array( 'autocomplete' => 'new-password'),
                'default' => 'angelleye'
            ),

            'sandbox_paypal_user' => array(
                'title' => __('User (optional)', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('If you set up one or more additional users on the account, this value is the ID
of the user authorized to process transactions. Otherwise, leave this field blank.', 'paypal-for-woocommerce'),
                'custom_attributes' => array( 'autocomplete' => 'new-password'),
                'default' => 'paypalwoocommerce'
            ),
            'sandbox_paypal_password' => array(
                'title' => __('Password', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => __('The password that you defined while registering for the account.', 'paypal-for-woocommerce'),
                'custom_attributes' => array( 'autocomplete' => 'new-password'),
                'default' => 'dwG7!Yp*PLY3'
            ),
            'paypal_partner' => array(
                'title' => __('Partner', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('The ID provided to you by the authorized PayPal Reseller who registered you
for the Payflow SDK. If you purchased your account directly from PayPal, use PayPal or leave blank.', 'paypal-for-woocommerce'),
                'default' => 'PayPal',
                'custom_attributes' => array( 'autocomplete' => 'new-password'),
            ),
            'paypal_vendor' => array(
                'title' => __('Vendor (Merchant Login)', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Your merchant login ID that you created when you registered for the account.', 'paypal-for-woocommerce'),
                'default' => '',
                'custom_attributes' => array( 'autocomplete' => 'new-password'),
            ),
            'paypal_user' => array(
                'title' => __('User (optional)', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('If you set up one or more additional users on the account, this value is the ID
of the user authorized to process transactions. Otherwise, leave this field blank.', 'paypal-for-woocommerce'),
                'default' => '',
                'custom_attributes' => array( 'autocomplete' => 'new-password'),
            ),
            'paypal_password' => array(
                'title' => __('Password', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => __('The password that you defined while registering for the account.', 'paypal-for-woocommerce'),
                'default' => '',
                'custom_attributes' => array( 'autocomplete' => 'new-password'),
            ),
            'subtotal_mismatch_behavior' => array(
		'title'       => __( 'Subtotal Mismatch Behavior', 'paypal-for-woocommerce' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'description' => __( 'Internally, WC calculates line item prices and taxes out to four decimal places; however, PayPal can only handle amounts out to two decimal places (or, depending on the currency, no decimal places at all). Occasionally, this can cause discrepancies between the way WooCommerce calculates prices versus the way PayPal calculates them. If a mismatch occurs, this option controls how the order is dealt with so payment can still be taken.', 'paypal-for-woocommerce' ),
		'default'     => ($this->send_items) ? 'add' : 'drop',
		'desc_tip'    => true,
		'options'     => array(
			'add'  => __( 'Add another line item', 'paypal-for-woocommerce' ),
			'drop' => __( 'Do not send line items to PayPal', 'paypal-for-woocommerce' ),
		),
            ),
            'payment_action' => array(
                'title' => __('Payment Action', 'paypal-for-woocommerce'),
                'label' => __('Whether to process as a Sale or Authorization.', 'paypal-for-woocommerce'),
                'description' => __('Sale will capture the funds immediately when the order is placed.  Authorization will authorize the payment but will not capture the funds.'),
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'options' => array(
                    'Sale' => 'Sale',
                    'Authorization' => 'Authorization',
                ),
                'default' => 'Sale'
            ),
            'payment_action_authorization' => array(
                'title' => __('Authorization Type', 'paypal-for-woocommerce'),
                'description' => __(''),
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'options' => array(
                    'Full Authorization' => __('Full Authorization', 'paypal-for-woocommerce'),
                    'Card Verification' => __('Card Verification', 'paypal-for-woocommerce'),
                ),
                'default' => 'Full Authorization'
            ),
            'pending_authorization_order_status' => array(
                'title' => __('Pending Authorization Order Status', 'paypal-for-woocommerce'),
                'label' => __('Pending Authorization Order Status.', 'paypal-for-woocommerce'),
                'description' => __('Pending Authorization Order Status.'),
                'type' => 'select',
                'class'    => 'wc-enhanced-select',
                'options' => array(
                    'On Hold' => 'On Hold',
                    'Processing' => 'Processing'
                ),
                'default' => 'On Hold',
                'desc_tip' => true,
            ),
            'default_order_status' => array(
                'title' => __('Default Order Status', 'paypal-for-woocommerce'),
                'label' => __('Default Order Status.', 'paypal-for-woocommerce'),
                'description' => __('Set the default order status for completed PayFlow credit card transactions.'),
                'type' => 'select',
                'class'    => 'wc-enhanced-select',
                'options' => array(
                    'Processing' => 'Processing',
                    'Completed' => 'Completed'
                ),
                'default' => 'Processing',
                'desc_tip' => true,
            ),
            'softdescriptor' => array(
                'title' => __('Credit Card Statement Name', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('The value entered here will be displayed on the buyer\'s credit card statement.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
            ),
            'fraud_management_filters' => array(
                'title' => __('Fraud Management Filters ', 'paypal-for-woocommerce'),
                'label' => '',
                'description' => __('Choose how you would like to handle orders when Fraud Management Filters are flagged.', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'options' => array(
                    'ignore_warnings_and_proceed_as_usual' => __('Ignore warnings and proceed as usual.', 'paypal-for-woocommerce'),
                    'place_order_on_hold_for_further_review' => __('Place order On Hold for further review.', 'paypal-for-woocommerce'),
                ),
                'default' => 'place_order_on_hold_for_further_review',
                'desc_tip' => true,
            ),
            'avs_cvv2_result_admin_email' => array(
                'title' => __('AVS / CVV2 Results in Admin Order Email', 'paypal-for-woocommerce'),
                'label' => __('Adds the AVS / CVV2 results to the admin order email notification', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Display Address Verification Result (AVS) and Card Security Code Result (CVV2) Results in Admin Order Email.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'desc_tip' => true,
            ),
            'credit_card_month_field' => array(
                'title' => __('Choose Credit Card Month Field', 'paypal-for-woocommerce'),
                'label' => __('Choose Credit Card Month Field Format.', 'paypal-for-woocommerce'),
                'description' => __('Choose whether you wish to display Name format or Number format of Month field in the credit card form.'),
                'type' => 'select',
                'css' => 'max-width:200px;',
                'class' => 'wc-enhanced-select',
                'options' => array(
                    'numbers' => 'Numbers',
                    'names' => 'Names',
                    'number_name' => 'Numbers and Names'
                ),
                'default' => 'names'
            ),
             'credit_card_year_field' => array(
                'title' => __('Choose Credit Card Year Field', 'paypal-for-woocommerce'),
                'label' => __('Choose Credit Card Year Field Format.', 'paypal-for-woocommerce'),
                'description' => __('Choose whether you wish to display Show Two digit format or Four digit of Year field in the credit card form.'),
                'type' => 'select',
                'css' => 'max-width:200px;',
                'class' => 'wc-enhanced-select',
                'options' => array(
                    'two_digit' => 'Show Two Digit Years',
                    'four_digit' => 'Show Four Digit Years',
                ),
                'default' => 'four_digit'
            ),
            'enable_tokenized_payments' => array(
                'title' => __('Enable Tokenized Payments', 'paypal-for-woocommerce'),
                'label' => __('Enable Tokenized Payments', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Allow buyers to securely save payment details to their account for quick checkout / auto-ship orders in the future.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'class' => 'enable_tokenized_payments'
            ),
            'enable_cardholder_first_last_name' => array(
                'title' => __('Enable Cardholder Name', 'paypal-for-woocommerce'),
                'label' => __('Adds fields for "card holder name" to checkout in addition to the "billing name" fields.', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Display card holder first and last name in credit card form.', 'paypal-for-woocommerce'),
                'default' => 'no'
            ),
            'debug' => array(
                'title' => __('Debug Log', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'paypal-for-woocommerce'),
                'default' => 'no',
                'description' => sprintf(__('Log PayPal events inside <code>%s</code>', 'paypal-for-woocommerce'), wc_get_log_file_path('paypal_pro_payflow')),
            ),
            'is_encrypt' => array(
                'title' => __('', 'paypal-for-woocommerce'),
                'label' => __('', 'paypal-for-woocommerce'),
                'type' => 'hidden',
                'default' => 'yes',
                'class' => ''
            )
        );
        $this->form_fields = apply_filters('angelleye_fc_form_fields', $this->form_fields);
    }



    public function admin_options() {
        echo '<h2>' . esc_html( $this->get_method_title() ) . '</h2>';
        echo wp_kses_post( wpautop( $this->get_method_description() ) );
        echo $this->angelleye_paypal_pro_payflow_reference_transaction_notice();
        ?>
        <div id="angelleye_paypal_marketing_table">
        <table class="form-table">
            <?php
             if(!get_user_meta(get_current_user_id(), 'payflow_sb_autopopulate_credentials')){
               echo '<div class="notice notice-info"><p>'.sprintf(__("<h3>Default PayFlow Sandbox Credentials</h3>
                <p>These values have been auto-filled into the sandbox credential fields so that you can quickly run test orders. If you have your own PayPal Manager test account you can update the values accordingly.</p>
                <strong>Partner:</strong> PayPal<br/>
                <strong>Merchant Login:</strong> angelleye<br/>
               
               <strong>Username:</strong> paypalwoocommerce<br/>
               
                <strong>Password:</strong> dwG7!Yp*PLY3<br/> 
                <br /><a href=%s>%s</a>", 'paypal-for-woocommerce'),
                esc_url(add_query_arg("payflow_sb_autopopulate_credentials", 0)), __("Hide this notice.", 'paypal-for-woocommerce')) . '</p></div>';
            }
            if(version_compare(WC_VERSION,'2.6','<')) {
                AngellEYE_Utility::woo_compatibility_notice();
            } else {
               $this->generate_settings_html();
            }
            
            ?>
        </table>
        </div>
        <?php AngellEYE_Utility::angelleye_display_marketing_sidebar($this->id); ?>
        <script type="text/javascript">
            jQuery('#woocommerce_paypal_pro_payflow_payment_action').change(function () {
                if ( this.value === 'Authorization' ) {
                    jQuery('#woocommerce_paypal_pro_payflow_payment_action_authorization').closest('tr').show();
                    jQuery('#woocommerce_paypal_pro_payflow_pending_authorization_order_status').closest('tr').show();
                    jQuery('#woocommerce_paypal_pro_payflow_default_order_status').closest('tr').hide();
                } else {
                    jQuery('#woocommerce_paypal_pro_payflow_payment_action_authorization').closest('tr').hide();
                    jQuery('#woocommerce_paypal_pro_payflow_pending_authorization_order_status').closest('tr').hide();
                    jQuery('#woocommerce_paypal_pro_payflow_default_order_status').closest('tr').show();
                }
            }).change();
            jQuery('#woocommerce_paypal_pro_payflow_testmode').change(function () {
                var sandbox = jQuery('#woocommerce_paypal_pro_payflow_sandbox_paypal_partner, #woocommerce_paypal_pro_payflow_sandbox_paypal_vendor, #woocommerce_paypal_pro_payflow_sandbox_paypal_user, #woocommerce_paypal_pro_payflow_sandbox_paypal_password').closest('tr'),
                production = jQuery('#woocommerce_paypal_pro_payflow_paypal_partner, #woocommerce_paypal_pro_payflow_paypal_vendor, #woocommerce_paypal_pro_payflow_paypal_user, #woocommerce_paypal_pro_payflow_paypal_password').closest('tr');
                if (jQuery(this).is(':checked')) {
                    sandbox.show();
                    production.hide();
                } else {
                    sandbox.hide();
                    production.show();
                }
            }).change();
        </script>
        <?php
    }

    /**
     * Check if this gateway is enabled and available in the user's country
     *
     * This method no is used anywhere??? put above but need a fix below
     */
    public function is_available() {

        if ($this->enabled == "yes") {
            if (!is_ssl() && !$this->testmode) {
                return false;
            }
            // Currency check
            if (!in_array(get_woocommerce_currency(), $this->allowed_currencies)) {
                return false;
            }

            // Required fields check
            if (!$this->paypal_vendor || !$this->paypal_password) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Process the payment
     */
    function process_payment($order_id) {
        $order = new WC_Order($order_id);
        $card = $this->get_posted_card();
        // Do payment with paypal
        return $this->do_payment($order, $card->number, $card->exp_month . $card->exp_year, $card->cvc);
    }

    /**
     * do_payment
     *
     * Process the PayFlow transaction with PayPal.
     *
     * @access public
     * @param mixed $order
     * @param mixed $card_number
     * @param mixed $card_exp
     * @param mixed $card_csc
     * @param string $centinelPAResStatus (default: '')
     * @param string $centinelEnrolled (default: '')
     * @param string $centinelCavv (default: '')
     * @param string $centinelEciFlag (default: '')
     * @param string $centinelXid (default: '')
     * @return void
     */
    function do_payment($order, $card_number, $card_exp, $card_csc) {

        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        $card = $this->get_posted_card();
        $this->angelleye_load_paypal_payflow_class($this->gateway, $this, $order_id);

        try {

            $billing_address_1 = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_address_1 : $order->get_billing_address_1();
            $billing_address_2 = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_address_2 : $order->get_billing_address_2();
            $billtostreet = $billing_address_1 . ' ' . $billing_address_2;
            $billing_city = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_city : $order->get_billing_city();
            $billing_postcode = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_postcode : $order->get_billing_postcode();
            $billing_country = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_country : $order->get_billing_country();
            $billing_state = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_state : $order->get_billing_state();
            $billing_email = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_email : $order->get_billing_email();

            if (!empty($_POST['paypal_pro_payflow-card-cardholder-first'])) {
                $firstname = wc_clean($_POST['paypal_pro_payflow-card-cardholder-first']);
            } else {
                $firstname = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_first_name : $order->get_billing_first_name();
            }

            if (!empty($_POST['paypal_pro_payflow-card-cardholder-last'])) {
                $lastname = wc_clean($_POST['paypal_pro_payflow-card-cardholder-last']);
            } else {
                $lastname = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_last_name : $order->get_billing_last_name();
            }

            $order_amt = AngellEYE_Gateway_Paypal::number_format($order->get_total());
            if( $this->payment_action == 'Authorization' && $this->payment_action_authorization == 'Card Verification' ) {
                $order_amt = '0.00';
            }

            $PayPalRequestData = array(
                'tender' => 'C', // Required.  The method of payment.  Values are: A = ACH, C = Credit Card, D = Pinless Debit, K = Telecheck, P = PayPal
                'trxtype' => ($this->payment_action == 'Authorization' || $order->get_total() == 0 ) ? 'A' : 'S', // Required.  Indicates the type of transaction to perform.  Values are:  A = Authorization, B = Balance Inquiry, C = Credit, D = Delayed Capture, F = Voice Authorization, I = Inquiry, L = Data Upload, N = Duplicate Transaction, S = Sale, V = Void
                'acct' => $card_number, // Required for credit card transaction.  Credit card or purchase card number.
                'expdate' => $card_exp, // Required for credit card transaction.  Expiration date of the credit card.  Format:  MMYY
                'amt' => $order_amt, // Required.  Amount of the transaction.  Must have 2 decimal places.
                'currency' => version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency(), //
                'dutyamt' => '', //
                'freightamt' => '', //
                'taxamt' => '', //
                'taxexempt' => '', //
                'custom' => apply_filters('ae_pppf_custom_parameter', json_encode(array('order_id' => version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id(), 'order_key' => version_compare(WC_VERSION, '3.0', '<') ? $order->order_key : $order->get_order_key())), $order), // Free-form field for your own use.
                'comment1' => apply_filters('ae_pppf_comment1_parameter', '', $order), // Merchant-defined value for reporting and auditing purposes.  128 char max
                'comment2' => apply_filters('ae_pppf_comment2_parameter', '', $order), // Merchant-defined value for reporting and auditing purposes.  128 char max
                'cvv2' => $card_csc, // A code printed on the back of the card (or front for Amex)
                'recurring' => '', // Identifies the transaction as recurring.  One of the following values:  Y = transaction is recurring, N = transaction is not recurring.
                'swipe' => '', // Required for card-present transactions.  Used to pass either Track 1 or Track 2, but not both.
                'orderid' => $this->invoice_id_prefix . preg_replace("/[^a-zA-Z0-9]/", "", $order->get_order_number()), // Checks for duplicate order.  If you pass orderid in a request and pass it again in the future the response returns DUPLICATE=2 along with the orderid
                'orderdesc' => 'Order ' . $order->get_order_number() . ' on ' . get_bloginfo('name'), //
                'billtoemail' => $billing_email, // Account holder's email address.
                'billtophonenum' => '', // Account holder's phone number.
                'billtofirstname' => $firstname, // Account holder's first name.
                'billtomiddlename' => '', // Account holder's middle name.
                'billtolastname' => $lastname, // Account holder's last name.
                'billtostreet' => $billtostreet, // The cardholder's street address (number and street name).  150 char max
                'billtocity' => $billing_city, // Bill to city.  45 char max
                'billtostate' => $billing_state, // Bill to state.
                'billtozip' => $billing_postcode, // Account holder's 5 to 9 digit postal code.  9 char max.  No dashes, spaces, or non-numeric characters
                'billtocountry' => $billing_country, // Bill to Country.  3 letter country code.
                'origid' => '', // Required by some transaction types.  ID of the original transaction referenced.  The PNREF parameter returns this ID, and it appears as the Transaction ID in PayPal Manager reports.
                'custref' => '', //
                'custcode' => '', //
                'custip' => AngellEYE_Utility::get_user_ip(), //
                'invnum' => $this->invoice_id_prefix . preg_replace("/[^a-zA-Z0-9]/", "", str_replace("#", "", $order->get_order_number())), //
                'ponum' => '', //
                'starttime' => '', // For inquiry transaction when using CUSTREF to specify the transaction.
                'endtime' => '', // For inquiry transaction when using CUSTREF to specify the transaction.
                'securetoken' => '', // Required if using secure tokens.  A value the Payflow server created upon your request for storing transaction data.  32 char
                'partialauth' => '', // Required for partial authorizations.  Set to Y to submit a partial auth.
                'authcode' => '', // Rrequired for voice authorizations.  Returned only for approved voice authorization transactions.  AUTHCODE is the approval code received over the phone from the processing network.  6 char max
                'merchdescr' => $this->softdescriptor
            );

            /**
             * Shipping info
             */
            $shipping_address_1 = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_address_1 : $order->get_shipping_address_1();
            if ($shipping_address_1) {


                $shipping_address_2 = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_address_2 : $order->get_shipping_address_2();
                $PayPalRequestData['SHIPTOFIRSTNAME'] = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_first_name : $order->get_shipping_first_name();
                $PayPalRequestData['SHIPTOLASTNAME'] = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_last_name : $order->get_shipping_last_name();
                $PayPalRequestData['SHIPTOSTREET'] = $shipping_address_1 . ' ' . $shipping_address_2;
                $PayPalRequestData['SHIPTOCITY'] = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_city : $order->get_shipping_city();
                $PayPalRequestData['SHIPTOSTATE'] = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_state : $order->get_shipping_state();
                $PayPalRequestData['SHIPTOCOUNTRY'] = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_country : $order->get_shipping_country();
                $PayPalRequestData['SHIPTOZIP'] = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_postcode : $order->get_shipping_postcode();
            }
            $PaymentData = $this->calculation_angelleye->order_calculation($order_id);
            $OrderItems = array();
            if( $PaymentData['is_calculation_mismatch'] == false ) {
                if( !empty($PaymentData['discount_amount']) && $PaymentData['discount_amount'] > 0 ) {
                    $PayPalRequestData['discount'] = $PaymentData['discount_amount'];
                }
                $item_loop = 0;
                foreach ($PaymentData['order_items'] as $_item) {
                    $Item['L_NUMBER' . $item_loop] = $_item['number'];
                    $Item['L_NAME' . $item_loop] = $_item['name'];
                    $Item['L_COST' . $item_loop] = $_item['amt'];
                    $Item['L_QTY' . $item_loop] = $_item['qty'];
                    if ($_item['number']) {
                        $Item['L_SKU' . $item_loop] = $_item['number'];
                    }
                    $OrderItems = array_merge($OrderItems, $Item);
                    $item_loop++;
                }
                
                if( $order->get_total() != $PaymentData['shippingamt'] ) {
                    $PayPalRequestData['freightamt'] = $PaymentData['shippingamt'];
                } else {
                    $PayPalRequestData['freightamt'] = 0.00;
                }
                $PayPalRequestData['taxamt'] = $PaymentData['taxamt'];
                $PayPalRequestData['ITEMAMT'] = $PaymentData['itemamt'];
                $PayPalRequestData = array_merge($PayPalRequestData, $OrderItems);
                
            }

            $log = $PayPalRequestData;
            if (!empty($_POST['wc-paypal_pro_payflow-payment-token']) && $_POST['wc-paypal_pro_payflow-payment-token'] != 'new') {
                $token_id = wc_clean($_POST['wc-paypal_pro_payflow-payment-token']);
                $token = WC_Payment_Tokens::get($token_id);
                $PayPalRequestData['origid'] = $token->get_token();
                $PayPalRequestData['expdate'] = '';
                $log['origid'] = $token->get_token();
            } else {
                if ($this->is_subscription($order_id)) {
                    $PayPalRequestData['origid'] = get_post_meta($order_id, '_payment_tokens', true);
                    $log['origid'] = get_post_meta($order_id, '_payment_tokens', true);
                } else {
                    $log['acct'] = '****';
                    $log['cvv2'] = '****';
                }
            }

            $this->add_log('PayFlow Request: ' . print_r($log, true));
            $PayPalResult = $this->PayPal->ProcessTransaction(apply_filters('angelleye_woocommerce_paypal_pro_payflow_process_transaction_request_args', $PayPalRequestData));
            /**
             *  cURL Error Handling #146
             *  @since    1.1.8
             */
            AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($PayPalResult, $methos_name = 'do_payment', $gateway = 'PayPal Payments Pro 2.0 (PayFlow)', $this->error_email_notify);


            $this->add_log('PayFlow Endpoint: ' . $this->PayPal->APIEndPoint);
            $this->add_log('PayFlow Response: ' . print_r($PayPalResult, true));


            /**
             * Error check
             */
            if (empty($PayPalResult['RAWRESPONSE'])) {
                $fc_empty_response = apply_filters('ae_pppf_paypal_response_empty_message', __('Empty PayPal response.', 'paypal-for-woocommerce'), $PayPalResult);
                throw new Exception($fc_empty_response);
            }

            /**
             * Check for errors or fraud filter warnings and proceed accordingly.
             */
            if (isset($PayPalResult['RESULT']) && ( $PayPalResult['RESULT'] == 0 || in_array($PayPalResult['RESULT'], $this->fraud_warning_codes))) {
                if( $this->payment_action == 'Authorization' && $this->payment_action_authorization == 'Card Verification' ) {
                    $order->add_order_note('Card : ' . $PayPalResult['RESPMSG']);
                    add_post_meta($order_id, 'payment_action_authorization', $this->payment_action_authorization);
                }
                if( isset($PayPalResult['DUPLICATE']) && '2' == $PayPalResult['DUPLICATE']) {
                    $order->update_status('failed', __('Payment failed due to a duplicate order ID.', 'paypal-for-woocommerce'));
                    throw new Exception(__('Payment failed due to duplicate order ID', 'paypal-for-woocommerce'));
                }
                $avs_address_response_code = isset($PayPalResult['AVSADDR']) ? $PayPalResult['AVSADDR'] : '';
                $avs_zip_response_code = isset($PayPalResult['AVSZIP']) ? $PayPalResult['AVSZIP'] : '';
                $proc_avs_response_code = isset($PayPalResult['PROCAVS']) ? $PayPalResult['PROCAVS'] : '';
                $avs_response_order_note = __('Address Verification Result', 'paypal-for-woocommerce');
                $avs_response_order_note .= '<ul class="angelleye_avs_result">';
                $avs_response_order_note .= '<li>' . sprintf(__('AVS: %s', 'paypal-for-woocommerce'), $proc_avs_response_code) . '</li>';
                $avs_response_order_note .= '<ul class="angelleye_avs_result_inner">';
                $avs_response_order_note .= '<li>' . sprintf(__('Address Match: %s', 'paypal-for-woocommerce'), $avs_address_response_code) . '</li>';
                $avs_response_order_note .= '<li>' . sprintf(__('Postal Match: %s', 'paypal-for-woocommerce'), $avs_zip_response_code) . '</li>';
                $avs_response_order_note .= "<ul>";
                $avs_response_order_note .= '</ul>';
                $old_wc = version_compare(WC_VERSION, '3.0', '<');
                if ($old_wc) {
                    update_post_meta($order_id, '_AVSADDR', $avs_address_response_code);
                    update_post_meta($order_id, '_AVSZIP', $avs_zip_response_code);
                    update_post_meta($order_id, '_PROCAVS', $avs_zip_response_code);
                } else {
                    update_post_meta($order->get_id(), '_AVSADDR', $avs_address_response_code);
                    update_post_meta($order->get_id(), '_AVSZIP', $avs_zip_response_code);
                    update_post_meta($order->get_id(), '_PROCAVS', $avs_zip_response_code);
                }
                $order->add_order_note($avs_response_order_note);
                $cvv2_response_code = isset($PayPalResult['CVV2MATCH']) ? $PayPalResult['CVV2MATCH'] : '';
                $cvv2_response_order_note = __('Card Security Code Result', 'paypal-for-woocommerce');
                $cvv2_response_order_note .= "\n";
                $cvv2_response_order_note .= sprintf(__('CVV2 Match: %s', 'paypal-for-woocommerce'), $cvv2_response_code);
                if ($old_wc) {
                    update_post_meta($order_id, '_CVV2MATCH', $cvv2_response_code);
                } else {
                    update_post_meta($order->get_id(), '_CVV2MATCH', $cvv2_response_code);
                }
                $order->add_order_note($cvv2_response_order_note);
                do_action('ae_add_custom_order_note', $order, $card, $token, $PayPalResult);
                do_action('before_save_payment_token', $order_id);
                if (AngellEYE_Utility::angelleye_is_save_payment_token($this, $order_id)) {
                    $TRANSACTIONID = $PayPalResult['PNREF'];
                    $this->save_payment_token($order, $TRANSACTIONID);
                    $this->are_reference_transactions_enabled($TRANSACTIONID);
                    if (!empty($_POST['wc-' . $this->id . '-payment-token']) && $_POST['wc-' . $this->id . '-payment-token'] != 'new') {
                        $token_id = wc_clean($_POST['wc-' . $this->id . '-payment-token']);
                        $token = WC_Payment_Tokens::get($token_id);
                        $order->add_payment_token($token);
                    } else {
                        $token = new WC_Payment_Token_CC();
                        if (0 != $order->get_user_id()) {
                            $customer_id = $order->get_user_id();
                        } else {
                            $customer_id = get_current_user_id();
                        }
                        $token->set_token($TRANSACTIONID);
                        $token->set_gateway_id($this->id);
                        $token->set_card_type(AngellEYE_Utility::card_type_from_account_number($PayPalRequestData['acct']));
                        $token->set_last4(substr($PayPalRequestData['acct'], -4));
                        $token->set_expiry_month(substr($PayPalRequestData['expdate'], 0, 2));
                        $expiry_year = substr($PayPalRequestData['expdate'], 2, 3);
                        if (strlen($expiry_year) == 2) {
                            $expiry_year = $expiry_year + 2000;
                        }
                        $token->set_expiry_year($expiry_year);
                        $token->set_user_id($customer_id);
                        if( $token->validate() ) {
                            $save_result = $token->save();
                            if ($save_result) {
                                $order->add_payment_token($token);
                            }
                        } else {
                            $order->add_order_note('ERROR MESSAGE: ' .  __( 'Invalid or missing payment token fields.', 'paypal-for-woocommerce' ));
                        }
                    }
                }
                if ($this->fraud_management_filters == 'place_order_on_hold_for_further_review' && in_array($PayPalResult['RESULT'], $this->fraud_warning_codes)) {
                    $order->update_status('on-hold', $PayPalResult['RESPMSG']);
                    $old_wc = version_compare(WC_VERSION, '3.0', '<');
                    $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
                    if ($old_wc) {
                        if (!get_post_meta($order_id, '_order_stock_reduced', true)) {
                            $order->reduce_order_stock();
                        }
                    } else {
                        wc_maybe_reduce_stock_levels($order_id);
                    }
                } elseif ($this->payment_action == "Authorization" ) {
                    if (isset($PayPalResult['PPREF']) && !empty($PayPalResult['PPREF'])) {
                        $order->add_order_note(sprintf(__('PayPal Pro Payflow payment completed (PNREF: %s) (PPREF: %s)', 'paypal-for-woocommerce'), $PayPalResult['PNREF'], $PayPalResult['PPREF']));
                    } else {
                        $order->add_order_note(sprintf(__('PayPal Pro Payflow payment completed (PNREF: %s)', 'paypal-for-woocommerce'), $PayPalResult['PNREF']));
                    }
                    if( $this->pending_authorization_order_status == 'Processing' ) {
                        $order->payment_complete($PayPalResult['PNREF']);
                    } else {
                        $order->update_status('on-hold');
                        $old_wc = version_compare(WC_VERSION, '3.0', '<');
                        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
                        if ($old_wc) {
                            if (!get_post_meta($order_id, '_order_stock_reduced', true)) {
                                $order->reduce_order_stock();
                            }
                        } else {
                            wc_maybe_reduce_stock_levels($order_id);
                        }
                    }
                    if ($old_wc) {
                        update_post_meta($order_id, '_first_transaction_id', $PayPalResult['PNREF']);
                    } else {
                        update_post_meta($order->get_id(), '_first_transaction_id', $PayPalResult['PNREF']);
                    }
                    $payment_order_meta = array('_transaction_id' => $PayPalResult['PNREF'], '_payment_action' => $this->payment_action);
                    AngellEYE_Utility::angelleye_add_order_meta($order_id, $payment_order_meta);
                    AngellEYE_Utility::angelleye_paypal_for_woocommerce_add_paypal_transaction($PayPalResult, $order, $this->payment_action);
                    $angelleye_utility = new AngellEYE_Utility(null, null);
                    $angelleye_utility->angelleye_get_transactionDetails($PayPalResult['PNREF']);
                } else {
                    if (isset($PayPalResult['PPREF']) && !empty($PayPalResult['PPREF'])) {
                        $order->add_order_note(sprintf(__('PayPal Pro Payflow payment completed (PNREF: %s) (PPREF: %s)', 'paypal-for-woocommerce'), $PayPalResult['PNREF'], $PayPalResult['PPREF']));
                    } else {
                        $order->add_order_note(sprintf(__('PayPal Pro Payflow payment completed (PNREF: %s)', 'paypal-for-woocommerce'), $PayPalResult['PNREF']));
                    }
                    if( $this->default_order_status == 'Completed' ) {
                        $order->update_status('completed');
                        if ($old_wc) {
                           update_post_meta($order_id, '_transaction_id', $PayPalResult['PNREF']);
                        } else {
                           update_post_meta($order->get_id(), '_transaction_id', $PayPalResult['PNREF']);
                        }
                    } else {
                        $order->payment_complete($PayPalResult['PNREF']);
                    }
                }
                WC()->cart->empty_cart();
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            } else {
                $order->update_status('failed', __('PayPal Pro Payflow payment failed. Payment was rejected due to an error: ', 'paypal-for-woocommerce') . '(' . $PayPalResult['RESULT'] . ') ' . '"' . $PayPalResult['RESPMSG'] . '"');
                if ($this->error_display_type == 'detailed') {
                    $fc_error_display_type = __('Payment error:', 'paypal-for-woocommerce') . ' ' . $PayPalResult['RESULT'] . '-' . $PayPalResult['RESPMSG'];
                } else {
                    $fc_error_display_type = __('Payment error:', 'paypal-for-woocommerce') . ' There was a problem processing your payment.  Please try another method.';
                }
                $fc_error_display_type = apply_filters('ae_pppf_error_user_display_message', $fc_error_display_type, $PayPalResult['RESULT'], $PayPalResult['RESPMSG'], $PayPalResult);
                wc_add_notice($fc_error_display_type, "error");
                if ($this->error_email_notify) {
                    $admin_email = get_option("admin_email");
                    $message = __("PayFlow API call failed.", "paypal-for-woocommerce") . "\n\n";
                    $message .= __('Error Code: ', 'paypal-for-woocommerce') . $PayPalResult['RESULT'] . "\n";
                    $message .= __('Detailed Error Message: ', 'paypal-for-woocommerce') . $PayPalResult['RESPMSG'];
                    $message .= isset($PayPalResult['PREFPSMSG']) && $PayPalResult['PREFPSMSG'] != '' ? ' - ' . $PayPalResult['PREFPSMSG'] . "\n" : "\n";
                    $message .= __('User IP: ', 'paypal-for-woocommerce') . AngellEYE_Utility::get_user_ip() . "\n";
                    $message .= __('Order ID: ') . $order_id . "\n";
                    $message .= __('Customer Name: ') . $firstname . ' ' . $lastname . "\n";
                    $message .= __('Customer Email: ') . $billing_email . "\n";
                    $message = apply_filters('ae_pppf_error_email_message', $message);
                    $subject = apply_filters('ae_pppf_error_email_subject', "PayPal Pro Payflow Error Notification");
                    wp_mail($admin_email, $subject, $message);
                }
                return;
            }
        } catch (Exception $e) {
            if ($order->has_status(array('pending', 'failed'))) {
                $this->send_failed_order_email($order_id);
            }
            $fc_connect_error = apply_filters('angelleye_fc_connect_error', $e->getMessage(), $e);
            wc_add_notice($fc_connect_error, "error");
            return;
        }
    }

    public function payment_fields() {
        do_action('angelleye_before_fc_payment_fields', $this);
        if ($this->description) {
            echo '<p>' . wp_kses_post($this->description);
            if ($this->testmode == true) {
                echo '<p>';
                _e('NOTICE: SANDBOX (TEST) MODE ENABLED.', 'paypal-for-woocommerce');
                echo '<br />';
                _e('For testing purposes you can use the card number 4111111111111111 with any CVC and a valid expiration date.', 'paypal-for-woocommerce');
                echo '</p>';
            }
        }
        if ($this->supports('tokenization') && is_checkout()) {
            $this->tokenization_script();
            $this->saved_payment_methods();
            $this->form();
            if (AngellEYE_Utility::is_cart_contains_subscription() == false && AngellEYE_Utility::is_subs_change_payment() == false) {
                $this->save_payment_method_checkbox();
            }
        } else {
            $this->form();
        }
        do_action('payment_fields_saved_payment_methods', $this);
    }

    public function save_payment_method_checkbox() {
        printf(
                '<p class="form-row woocommerce-SavedPaymentMethods-saveNew">
                        <input id="wc-%1$s-new-payment-method" name="wc-%1$s-new-payment-method" type="checkbox" value="true" style="width:auto;" />
                        <label for="wc-%1$s-new-payment-method" style="display:inline;">%2$s</label>
                </p>',
                esc_attr( $this->id ),
                apply_filters( 'cc_form_label_save_to_account', __( 'Save payment method to my account.', 'paypal-for-woocommerce' ), $this->id)
        );
    }

    public function paypal_for_woocommerce_paypal_pro_payflow_credit_card_form_expiration_date_selectbox() {
        $form_html = "";
        $form_html .= '<p class="form-row form-row-first">';
        $form_html .= '<label for="cc-expire-month">' . apply_filters( 'cc_form_label_expiry', __("Expiration Date", 'paypal-for-woocommerce'), $this->id) . '<span class="required">*</span></label>';
        $form_html .= '<select name="paypal_pro_payflow_card_expiration_month" id="cc-expire-month" class="woocommerce-select woocommerce-cc-month mr5">';
        $form_html .= '<option value="">' . __('Month', 'paypal-for-woocommerce') . '</option>';
        $months = array();
        for ($i = 1; $i <= 12; $i++) :
            $timestamp = mktime(0, 0, 0, $i, 1);
            $months[date('n', $timestamp)] = date_i18n(_x('F', 'Month Names', 'paypal-for-woocommerce'), $timestamp);
        endfor;

        foreach ($months as $num => $name) {
            if( $this->credit_card_month_field == 'numbers' ) {
                $month_value = ($num < 10) ? '0' . $num : $num;
                $form_html .= '<option value=' . $num . '>' . $month_value . '</option>';
            } elseif( $this->credit_card_month_field == 'number_name' ) {
                $month_value = ($num < 10) ? '0' . $num : $num;
                $form_html .= '<option value=' . $num . '>' . $month_value .'-'. $name . '</option>';
            } else {
                $month_value = ($num < 10) ? '0' . $num : $num;
                $form_html .= '<option value=' . $num . '>' . $name . '</option>';
            }
        }
        $form_html .= '</select>';
        $form_html .= '<select name="paypal_pro_payflow_card_expiration_year" id="cc-expire-year" class="woocommerce-select woocommerce-cc-year ml5">';
        $form_html .= '<option value="">' . __('Year', 'paypal-for-woocommerce') . '</option>';
        for ($i = date('y'); $i <= date('y') + 15; $i++) {
            if ($this->credit_card_year_field == 'four_digit') {
                $form_html .= '<option value=' . $i . '>20' . $i . '</option>';
            } else {
                $form_html .= '<option value=' . $i . '>' . $i . '</option>';
            }
        }
        $form_html .= '</select>';
        $form_html .= '</p>';
        return $form_html;
    }

    /**
     * clear_centinel_session function.
     *
     * @access public
     * @return void
     */
    function clear_centinel_session() {
        unset($_SESSION['Message']);
        foreach ($_SESSION as $key => $value) {
            if (preg_match("/^Centinel_.*/", $key) > 0) {
                unset($_SESSION[$key]);
            }
        }
    }

    /**
     * Process a refund if supported
     * @param  int $order_id
     * @param  float $amount
     * @param  string $reason
     * @return  bool|wp_error True or false based on success, or a WP_Error object
     */
    public function process_refund($order_id, $amount = null, $reason = '') {

        do_action('angelleye_before_fc_refund', $order_id, $amount, $reason);

        $order = wc_get_order($order_id);
        $this->add_log('Begin Refund');
        $this->add_log('Order ID: ' . print_r($order_id, true));
        $this->add_log('Transaction ID: ' . print_r($order->get_transaction_id(), true));
        if (!$order || !$order->get_transaction_id() || !$this->paypal_user || !$this->paypal_password || !$this->paypal_vendor) {
            return false;
        }

        /**
         * Check if the PayPal_PayFlow class has already been established.
         */


        /**
         * Create PayPal_PayFlow object.
         */
        $this->angelleye_load_paypal_payflow_class($this->gateway, $this, $order_id);
        $PayPalRequestData = array(
            'TENDER' => 'C', // C = credit card, P = PayPal
            'TRXTYPE' => 'C', //  S=Sale, A= Auth, C=Credit, D=Delayed Capture, V=Void
            'ORIGID' => $order->get_transaction_id(),
            'AMT' => $amount,
            'CURRENCY' => version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency()
        );
        $PayPalResult = $this->PayPal->ProcessTransaction($PayPalRequestData);

        $PayPalRequest = isset($PayPalResult['RAWREQUEST']) ? $PayPalResult['RAWREQUEST'] : '';
        $PayPalResponse = isset($PayPalResult['RAWRESPONSE']) ? $PayPalResult['RAWRESPONSE'] : '';

        $this->add_log('Refund Request: ' . print_r($PayPalRequestData, true));
        $this->add_log('Refund Response: ' . print_r($this->PayPal->NVPToArray($this->PayPal->MaskAPIResult($PayPalResponse)), true));

        /**
         *  cURL Error Handling #146
         *  @since    1.1.8
         */
        AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($PayPalResult, $methos_name = 'Refund Request', $gateway = 'PayPal Payments Pro 2.0 (PayFlow)', $this->error_email_notify);

        add_action('angelleye_after_refund', $PayPalResult, $order, $amount, $reason);
        if (isset($PayPalResult['RESULT']) && $PayPalResult['RESULT'] == 0) {
            update_post_meta($order_id, 'Refund Transaction ID', $PayPalResult['PNREF']);
            $order->add_order_note('Refund Transaction ID:' . $PayPalResult['PNREF']);
            if (ob_get_length())
                ob_end_clean();
            return true;
        }else {
            $fc_refund_error = apply_filters('ae_pppf_refund_error_message', $PayPalResult['RESPMSG'], $PayPalResult);
            return new WP_Error('paypal-error', $fc_refund_error);
        }
        return false;
    }

    /**
     * Validate the payment form
     * PayFlow - Empty Card Data Validation Problem #220
     * @since    1.1.7.6
     */
    public function validate_fields() {

        if (isset($_POST['wc-paypal_pro_payflow-payment-token']) && 'new' !== $_POST['wc-paypal_pro_payflow-payment-token']) {
            $token_id = wc_clean($_POST['wc-paypal_pro_payflow-payment-token']);
            $token = WC_Payment_Tokens::get($token_id);
            if ($token->get_user_id() !== get_current_user_id()) {
                throw new Exception(__('Error processing checkout. Please try again.', 'paypal-for-woocommerce'));
            } else {
                return true;
            }
        }
        $card_number = isset($_POST['paypal_pro_payflow-card-number']) ? wc_clean($_POST['paypal_pro_payflow-card-number']) : '';
        $card_cvc = isset($_POST['paypal_pro_payflow-card-cvc']) ? wc_clean($_POST['paypal_pro_payflow-card-cvc']) : '';
        $card_exp_year = isset($_POST['paypal_pro_payflow_card_expiration_year']) ? wc_clean($_POST['paypal_pro_payflow_card_expiration_year']) : '';
        $card_exp_month = isset($_POST['paypal_pro_payflow_card_expiration_month']) ? wc_clean($_POST['paypal_pro_payflow_card_expiration_month']) : '';


        // Format values
        $card_number = str_replace(array(' ', '-'), '', $card_number);

        if (strlen($card_exp_year) == 4) {
            $card_exp_year = $card_exp_year - 2000;
        }

        do_action('before_angelleye_pro_payflow_checkout_validate_fields', $card_number, $card_cvc, $card_exp_month, $card_exp_year);
        
        // Check card number

        if (empty($card_number) || !ctype_digit((string) $card_number)) {
            wc_add_notice(__('Card number is invalid', 'paypal-for-woocommerce'), "error");
            return false;
        }

        // Check card security code

        if (!ctype_digit((string) $card_cvc)) {
            wc_add_notice(__('Card security code is invalid (only digits are allowed)', 'paypal-for-woocommerce'), "error");
            return false;
        }

        // Check card expiration data

        if (
                !ctype_digit((string) $card_exp_month) ||
                !ctype_digit((string) $card_exp_year) ||
                $card_exp_month > 12 ||
                $card_exp_month < 1 ||
                $card_exp_year < date('y') ||
                $card_exp_year > date('y') + 20
        ) {
            wc_add_notice(__('Card expiration date is invalid', 'paypal-for-woocommerce'), "error");
            return false;
        }

        do_action('after_angelleye_pro_payflow_checkout_validate_fields', $card_number, $card_cvc, $card_exp_month, $card_exp_year);

        return true;
    }

    public function field_name($name) {
        return ' name="' . esc_attr($this->id . '-' . $name) . '" ';
    }

    public function angelleye_paypal_pro_payflow_credit_card_form_fields($default_fields, $current_gateway_id) {
        if ($current_gateway_id == $this->id) {
            $fields = array(
                'card-number-field' => '<p class="form-row form-row-wide">
                            <label for="' . esc_attr($this->id) . '-card-number">' . apply_filters( 'cc_form_label_card_number', __('Card number', 'paypal-for-woocommerce'), $this->id ) . ' <span class="required">*</span></label>
                            <input id="' . esc_attr($this->id) . '-card-number" class="input-text wc-credit-card-form-card-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ' . $this->field_name('card-number') . ' />
                        </p>',
                'card-expiry-field' => $this->paypal_for_woocommerce_paypal_pro_payflow_credit_card_form_expiration_date_selectbox(),
                '<p class="form-row form-row-last">
                            <label for="' . esc_attr($this->id) . '-card-cvc">' . apply_filters( 'cc_form_label_card_code', __('Card Security Code', 'paypal-for-woocommerce'), $this->id) . ' <span class="required">*</span></label>
                            <input id="' . esc_attr($this->id) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="' . esc_attr__('CVC', 'paypal-for-woocommerce') . '" ' . $this->field_name('card-cvc') . ' style="width:100px" />
                        </p>'
            );
            return $fields;
        } else {
            return $default_fields;
        }
    }

    public function angelleye_woocommerce_credit_card_form_start($current_id) {
        if ($this->enable_cardholder_first_last_name && $current_id == $this->id) {
            $fields['card-cardholder-first'] = '<p class="form-row form-row-first">
                    <label for="' . esc_attr($this->id) . '-card-first-name">' . __('Cardholder First Name', 'paypal-for-woocommerce') . '</label>
                    <input id="' . esc_attr($this->id) . '-card-first-name" class="input-text wc-credit-card-form-cardholder" type="text" autocomplete="off" placeholder="' . esc_attr__('First Name', 'paypal-for-woocommerce') . '" name="' . $current_id . '-card-cardholder-first' . '" />
            </p>';
            $fields['card-cardholder-last'] = '<p class="form-row form-row-last">
                    <label for="' . esc_attr($this->id) . '-card-last-name">' . __('Cardholder Last Name', 'paypal-for-woocommerce') . '</label>
                    <input id="' . esc_attr($this->id) . '-card-last-name" class="input-text wc-credit-card-form-cardholder" type="text" autocomplete="off" placeholder="' . __('Last Name', 'paypal-for-woocommerce') . '" name="' . $current_id . '-card-cardholder-last' . '" />
            </p>';

            foreach ($fields as $field) {
                echo $field;
            }
        }
    }

    public function get_posted_card() {
            $card_number = isset($_POST['paypal_pro_payflow-card-number']) ? wc_clean($_POST['paypal_pro_payflow-card-number']) : '';
            $card_cvc = isset($_POST['paypal_pro_payflow-card-cvc']) ? wc_clean($_POST['paypal_pro_payflow-card-cvc']) : '';
            $card_exp_year = isset($_POST['paypal_pro_payflow_card_expiration_year']) ? wc_clean($_POST['paypal_pro_payflow_card_expiration_year']) : '';
            $card_exp_month = isset($_POST['paypal_pro_payflow_card_expiration_month']) ? wc_clean($_POST['paypal_pro_payflow_card_expiration_month']) : '';
            $card_number = str_replace(array(' ', '-'), '', $card_number);
            $card_type = AngellEYE_Utility::card_type_from_account_number($card_number);
            if ($card_type == 'amex') {
                if (WC()->countries->get_base_country() == 'CA' && get_woocommerce_currency() == 'USD' && apply_filters('angelleye_paypal_pro_payflow_amex_ca_usd', true, $this)) {
                    throw new Exception(__('Your processor is unable to process the Card Type in the currency requested. Please try another card type', 'paypal-for-woocommerce'));
                }
                if (get_woocommerce_currency() != 'USD' && get_woocommerce_currency() != 'AUD' && get_woocommerce_currency() != 'CAD') {
                    throw new Exception(__('Your processor is unable to process the Card Type in the currency requested. Please try another card type', 'paypal-for-woocommerce'));
                }
            }

            if (strlen($card_exp_year) == 4) {
                $card_exp_year = $card_exp_year - 2000;
            }
            $card_exp_month = (int) $card_exp_month;
            if ($card_exp_month < 10) {
                $card_exp_month = '0' . $card_exp_month;
            }
            return (object) array(
                        'number' => $card_number,
                        'type' => $card_type,
                        'cvc' => $card_cvc,
                        'exp_month' => $card_exp_month,
                        'exp_year' => $card_exp_year,
                        'start_month' => '',
                        'start_year' => ''
            );

    }

    public function add_payment_method() {
        $customer_id = get_current_user_id();
        $this->angelleye_load_paypal_payflow_class($this->gateway, $this, null);
        $this->validate_fields();
        $card = $this->get_posted_card();

        $billtofirstname = (get_user_meta($customer_id, 'billing_first_name', true)) ? get_user_meta($customer_id, 'billing_first_name', true) : get_user_meta($customer_id, 'shipping_first_name', true);
        $billtolastname = (get_user_meta($customer_id, 'billing_last_name', true)) ? get_user_meta($customer_id, 'billing_last_name', true) : get_user_meta($customer_id, 'shipping_last_name', true);
        $billtostate = (get_user_meta($customer_id, 'billing_state', true)) ? get_user_meta($customer_id, 'billing_state', true) : get_user_meta($customer_id, 'shipping_state', true);
        $billtocountry = (get_user_meta($customer_id, 'billing_country', true)) ? get_user_meta($customer_id, 'billing_country', true) : get_user_meta($customer_id, 'shipping_country', true);
        $billtozip = (get_user_meta($customer_id, 'billing_postcode', true)) ? get_user_meta($customer_id, 'billing_postcode', true) : get_user_meta($customer_id, 'shipping_postcode', true);

        $PayPalRequestData = array(
            'tender' => 'C',
            'trxtype' => 'A',
            'acct' => $card->number,
            'expdate' => $card->exp_month . $card->exp_year,
            'amt' => '0.00',
            'currency' => get_woocommerce_currency(),
            'cvv2' => $card->cvc,
            'orderid' => '',
            'orderdesc' => '',
            'billtoemail' => '',
            'billtophonenum' => '',
            'billtofirstname' => $billtofirstname,
            'billtomiddlename' => '',
            'billtolastname' => $billtolastname,
            'billtostreet' => '',
            'billtocity' => '',
            'billtostate' => $billtostate,
            'billtozip' => $billtozip,
            'billtocountry' => $billtocountry,
            'origid' => '',
            'custref' => '',
            'custcode' => '',
            'custip' => AngellEYE_Utility::get_user_ip(),
            'invnum' => '',
            'ponum' => '',
            'starttime' => '',
            'endtime' => '',
            'securetoken' => '',
            'partialauth' => '',
            'authcode' => ''
        );
        $PayPalResult = $this->PayPal->ProcessTransaction(apply_filters('angelleye_woocommerce_paypal_payflow_request_args', $PayPalRequestData));
        if (isset($PayPalResult['RESULT']) && ($PayPalResult['RESULT'] == 0 || in_array($PayPalResult['RESULT'], $this->fraud_codes))) {
            if (in_array($PayPalResult['RESULT'], $this->fraud_codes)) {
                wc_add_notice(__('The payment was flagged by a fraud filter, please check your PayPal Manager account to review and accept or deny the payment.', 'paypal-for-woocommerce'), 'error');
                wp_redirect(wc_get_account_endpoint_url('payment-methods'));
                exit();
            } else {
                $customer_id = get_current_user_id();
                $TRANSACTIONID = $PayPalResult['PNREF'];
                $this->are_reference_transactions_enabled($TRANSACTIONID);
                $token = new WC_Payment_Token_CC();
                $token->set_token($TRANSACTIONID);
                $token->set_gateway_id($this->id);
                $token->set_card_type(AngellEYE_Utility::card_type_from_account_number($PayPalRequestData['acct']));
                $token->set_last4(substr($PayPalRequestData['acct'], -4));
                $token->set_expiry_month(substr($PayPalRequestData['expdate'], 0, 2));
                $expiry_year = substr($PayPalRequestData['expdate'], 2, 3);
                if (strlen($expiry_year) == 2) {
                    $expiry_year = $expiry_year + 2000;
                }
                $token->set_expiry_year($expiry_year);
                $token->set_user_id($customer_id);
                if( $token->validate() ) {
                    $save_result = $token->save();
                    if ($save_result) {
                        return array(
                            'result' => 'success',
                            'redirect' => wc_get_account_endpoint_url('payment-methods')
                        );
                    }
                } else {
                    throw new Exception( __( 'Invalid or missing payment token fields.', 'paypal-for-woocommerce' ) );
                }
            }
        } else {
            wc_add_notice(__($PayPalResult['RESPMSG'], 'paypal-for-woocommerce'), 'error');
            wp_redirect(wc_get_account_endpoint_url('payment-methods'));
            exit();
        }
    }

    public function process_subscription_payment($order, $amount, $payment_token = null) {
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        $card = $this->get_posted_card();
        $this->angelleye_load_paypal_payflow_class($this->gateway, $this, $order_id);
        try {

            if (!empty($_POST['paypal_pro_payflow-card-cardholder-first'])) {
                $firstname = wc_clean($_POST['paypal_pro_payflow-card-cardholder-first']);
            } else {
                $firstname = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_first_name : $order->get_billing_first_name();
            }

            if (!empty($_POST['paypal_pro_payflow-card-cardholder-last'])) {
                $lastname = wc_clean($_POST['paypal_pro_payflow-card-cardholder-last']);
            } else {
                $lastname = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_last_name : $order->get_billing_last_name();
            }

            $billing_address_1 = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_address_1 : $order->get_billing_address_1();
            $billing_address_2 = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_address_2 : $order->get_billing_address_2();
            $billing_city = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_city : $order->get_billing_city();
            $billing_postcode = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_postcode : $order->get_billing_postcode();
            $billing_country = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_country : $order->get_billing_country();
            $billing_state = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_state : $order->get_billing_state();
            $billing_email = version_compare(WC_VERSION, '3.0', '<') ? $billing_email : $order->get_billing_email();
            $customer_note_value = version_compare(WC_VERSION, '3.0', '<') ? wptexturize($order->customer_note) : wptexturize($order->get_customer_note());
            $customer_note = $customer_note_value ? substr(preg_replace("/[^A-Za-z0-9 ]/", "", $customer_note_value), 0, 256) : '';
            $PayPalRequestData = array(
                'tender' => 'C', // Required.  The method of payment.  Values are: A = ACH, C = Credit Card, D = Pinless Debit, K = Telecheck, P = PayPal
                'trxtype' => ($this->payment_action == 'Authorization' || $order->get_total() == 0 ) ? 'A' : 'S', // Required.  Indicates the type of transaction to perform.  Values are:  A = Authorization, B = Balance Inquiry, C = Credit, D = Delayed Capture, F = Voice Authorization, I = Inquiry, L = Data Upload, N = Duplicate Transaction, S = Sale, V = Void
                'amt' => AngellEYE_Gateway_Paypal::number_format($order->get_total()), // Required.  Amount of the transaction.  Must have 2 decimal places.
                'currency' => version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency(), //
                'comment1' => apply_filters('ae_pppf_custom_parameter', $customer_note, $order), // Merchant-defined value for reporting and auditing purposes.  128 char max
                'comment2' => apply_filters('ae_pppf_comment2_parameter', '', $order), // Merchant-defined value for reporting and auditing purposes.  128 char max
                'recurring' => '', // Identifies the transaction as recurring.  One of the following values:  Y = transaction is recurring, N = transaction is not recurring.
                'swipe' => '', // Required for card-present transactions.  Used to pass either Track 1 or Track 2, but not both.
                'orderid' => $this->invoice_id_prefix . preg_replace("/[^a-zA-Z0-9]/", "", $order->get_order_number()), // Checks for duplicate order.  If you pass orderid in a request and pass it again in the future the response returns DUPLICATE=2 along with the orderid
                'orderdesc' => 'Order ' . $order->get_order_number() . ' on ' . get_bloginfo('name'), //
                'billtoemail' => $billing_email, // Account holder's email address.
                'billtophonenum' => '', // Account holder's phone number.
                'billtostreet' => $billing_address_1 . ' ' . $billing_address_2, // The cardholder's street address (number and street name).  150 char max
                'billtocity' => $billing_city, // Bill to city.  45 char max
                'billtostate' => $billing_state, // Bill to state.
                'billtozip' => $billing_postcode, // Account holder's 5 to 9 digit postal code.  9 char max.  No dashes, spaces, or non-numeric characters
                'billtocountry' => $billing_country, // Bill to Country.  3 letter country code.
                'origid' => '', // Required by some transaction types.  ID of the original transaction referenced.  The PNREF parameter returns this ID, and it appears as the Transaction ID in PayPal Manager reports.
                'custref' => '', //
                'custcode' => '', //
                'custip' => AngellEYE_Utility::get_user_ip(), //
                'invnum' => $this->invoice_id_prefix . str_replace("#", "", $order->get_order_number()), //
                'ponum' => '', //
                'starttime' => '', // For inquiry transaction when using CUSTREF to specify the transaction.
                'endtime' => '', // For inquiry transaction when using CUSTREF to specify the transaction.
                'securetoken' => '', // Required if using secure tokens.  A value the Payflow server created upon your request for storing transaction data.  32 char
                'partialauth' => '', // Required for partial authorizations.  Set to Y to submit a partial auth.
                'authcode' => ''    // Rrequired for voice authorizations.  Returned only for approved voice authorization transactions.  AUTHCODE is the approval code received over the phone from the processing network.  6 char max
            );
            /**
             * Shipping info
             */
            $shipping_first_name = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_first_name : $order->get_shipping_first_name();
            $shipping_last_name = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_last_name : $order->get_shipping_last_name();
            $shipping_address_1 = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_address_1 : $order->get_shipping_address_1();
            $shipping_address_2 = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_address_2 : $order->get_shipping_address_2();
            $shipping_city = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_city : $order->get_shipping_city();
            $shipping_postcode = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_postcode : $order->get_shipping_postcode();
            $shipping_country = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_country : $order->get_shipping_country();
            $shipping_state = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_state : $order->get_shipping_state();

            if ($shipping_address_1) {
                $PayPalRequestData['SHIPTOFIRSTNAME'] = $shipping_first_name;
                $PayPalRequestData['SHIPTOLASTNAME'] = $shipping_last_name;
                $PayPalRequestData['SHIPTOSTREET'] = $shipping_address_1 . ' ' . $shipping_address_2;
                $PayPalRequestData['SHIPTOCITY'] = $shipping_city;
                $PayPalRequestData['SHIPTOSTATE'] = $shipping_state;
                $PayPalRequestData['SHIPTOCOUNTRY'] = $shipping_country;
                $PayPalRequestData['SHIPTOZIP'] = $shipping_postcode;
            }
            $PaymentData = $this->calculation_angelleye->order_calculation($order_id);
            $OrderItems = array();
            if( $PaymentData['is_calculation_mismatch'] == false ) {
                $item_loop = 0;
                foreach ($PaymentData['order_items'] as $_item) {
                    $Item['L_NUMBER' . $item_loop] = $_item['number'];
                    $Item['L_NAME' . $item_loop] = $_item['name'];
                    $Item['L_COST' . $item_loop] = $_item['amt'];
                    $Item['L_QTY' . $item_loop] = $_item['qty'];
                    if ($_item['number']) {
                        $Item['L_SKU' . $item_loop] = $_item['number'];
                    }
                    $OrderItems = array_merge($OrderItems, $Item);
                    $item_loop++;
                }
                $PayPalRequestData['taxamt'] = $PaymentData['taxamt'];
                $PayPalRequestData['freightamt'] = $PaymentData['shippingamt'];
                $PayPalRequestData['ITEMAMT'] = $PaymentData['itemamt'];
                $PayPalRequestData = array_merge($PayPalRequestData, $OrderItems);
            }
           
            if ($this->is_subscription($order_id)) {
                $token_id = get_post_meta($order_id, '_payment_tokens_id', true);
                $PayPalRequestData['origid'] = $token_id;
                $token = WC_Payment_Tokens::get($token_id);
            }
            if (!empty($payment_token)) {
                $PayPalRequestData['origid'] = $payment_token;
                $token = WC_Payment_Tokens::get($payment_token);
            }
            $PayPalResult = $this->PayPal->ProcessTransaction($PayPalRequestData);

            $this->add_log('PayFlow Endpoint: ' . $this->PayPal->APIEndPoint);
            $this->add_log('PayFlow Response: ' . print_r($PayPalResult, true));

            if (empty($PayPalResult['RAWRESPONSE'])) {
                $fc_empty_response = apply_filters('ae_pppf_paypal_response_empty_message', __('Empty PayPal response.', 'paypal-for-woocommerce'), $PayPalResult);
                throw new Exception($fc_empty_response);
            }
            if (isset($PayPalResult['RESULT']) && ( $PayPalResult['RESULT'] == 0 || in_array($PayPalResult['RESULT'], $this->fraud_warning_codes))) {
                if( isset($PayPalResult['DUPLICATE']) && '2' == $PayPalResult['DUPLICATE']) {
                    $order->update_status('failed', __('Payment failed due to duplicate order ID', 'paypal-for-woocommerce'));
                    throw new Exception(__('Payment failed due to duplicate order ID', 'paypal-for-woocommerce'));
                }
                if (isset($PayPalResult['PPREF']) && !empty($PayPalResult['PPREF'])) {
                    add_post_meta($order_id, 'PPREF', $PayPalResult['PPREF']);
                    $order->add_order_note(sprintf(__('PayPal Pro Payflow payment completed (PNREF: %s) (PPREF: %s)', 'paypal-for-woocommerce'), $PayPalResult['PNREF'], $PayPalResult['PPREF']));
                } else {
                    $order->add_order_note(sprintf(__('PayPal Pro Payflow payment completed (PNREF: %s)', 'paypal-for-woocommerce'), $PayPalResult['PNREF']));
                }
                $avs_address_response_code = isset($PayPalResult['AVSADDR']) ? $PayPalResult['AVSADDR'] : '';
                $avs_zip_response_code = isset($PayPalResult['AVSZIP']) ? $PayPalResult['AVSZIP'] : '';
                $proc_avs_response_code = isset($PayPalResult['PROCAVS']) ? $PayPalResult['PROCAVS'] : '';
                $avs_response_order_note = __('Address Verification Result', 'paypal-for-woocommerce');
                $avs_response_order_note .= '<ul class="angelleye_avs_result">';
                $avs_response_order_note .= '<li>' . sprintf(__('AVS: %s', 'paypal-for-woocommerce'), $proc_avs_response_code) . '</li>';
                $avs_response_order_note .= '<ul class="angelleye_avs_result_inner">';
                $avs_response_order_note .= '<li>' . sprintf(__('Address Match: %s', 'paypal-for-woocommerce'), $avs_address_response_code) . '</li>';
                $avs_response_order_note .= '<li>' . sprintf(__('Postal Match: %s', 'paypal-for-woocommerce'), $avs_zip_response_code) . '</li>';
                $avs_response_order_note .= "<ul>";
                $avs_response_order_note .= '</ul>';
                $old_wc = version_compare(WC_VERSION, '3.0', '<');
                if ($old_wc) {
                    update_post_meta($order_id, '_AVSADDR', $avs_address_response_code);
                    update_post_meta($order_id, '_AVSZIP', $avs_zip_response_code);
                    update_post_meta($order_id, '_PROCAVS', $avs_zip_response_code);
                } else {
                    update_post_meta($order->get_id(), '_AVSADDR', $avs_address_response_code);
                    update_post_meta($order->get_id(), '_AVSZIP', $avs_zip_response_code);
                    update_post_meta($order->get_id(), '_PROCAVS', $avs_zip_response_code);
                }
                $order->add_order_note($avs_response_order_note);
                $cvv2_response_code = isset($PayPalResult['CVV2MATCH']) ? $PayPalResult['CVV2MATCH'] : '';
                $cvv2_response_order_note = __('Card Security Code Result', 'paypal-for-woocommerce');
                $cvv2_response_order_note .= "\n";
                $cvv2_response_order_note .= sprintf(__('CVV2 Match: %s', 'paypal-for-woocommerce'), $cvv2_response_code);
                $order->add_order_note($cvv2_response_order_note);
                do_action('ae_add_custom_order_note', $order, $card, $token, $PayPalResult);
                if ($this->fraud_management_filters == 'place_order_on_hold_for_further_review' && in_array($PayPalResult['RESULT'], $this->fraud_warning_codes)) {
                    $order->update_status('on-hold', $PayPalResult['RESPMSG']);
                    $old_wc = version_compare(WC_VERSION, '3.0', '<');
                    $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
                    if ($old_wc) {
                        if (!get_post_meta($order_id, '_order_stock_reduced', true)) {
                            $order->reduce_order_stock();
                        }
                    } else {
                        wc_maybe_reduce_stock_levels($order_id);
                    }
                } elseif ($this->payment_action == "Authorization") {
                    if (isset($PayPalResult['PPREF']) && !empty($PayPalResult['PPREF'])) {
                        $order->add_order_note(sprintf(__('PayPal Pro Payflow payment completed (PNREF: %s) (PPREF: %s)', 'paypal-for-woocommerce'), $PayPalResult['PNREF'], $PayPalResult['PPREF']));
                    } else {
                        $order->add_order_note(sprintf(__('PayPal Pro Payflow payment completed (PNREF: %s)', 'paypal-for-woocommerce'), $PayPalResult['PNREF']));
                    }
                    if ($old_wc) {
                        update_post_meta($order_id, '_first_transaction_id', $PayPalResult['PNREF']);
                    } else {
                        update_post_meta($order->get_id(), '_first_transaction_id', $PayPalResult['PNREF']);
                    }
                    $payment_order_meta = array('_transaction_id' => $PayPalResult['PNREF'], '_payment_action' => $this->payment_action);
                    AngellEYE_Utility::angelleye_add_order_meta($order_id, $payment_order_meta);
                    AngellEYE_Utility::angelleye_paypal_for_woocommerce_add_paypal_transaction($PayPalResult, $order, $this->payment_action);
                    $angelleye_utility = new AngellEYE_Utility(null, null);
                    $angelleye_utility->angelleye_get_transactionDetails($PayPalResult['PNREF']);
                } else {
                    if (isset($PayPalResult['PPREF']) && !empty($PayPalResult['PPREF'])) {
                        $order->add_order_note(sprintf(__('PayPal Pro Payflow payment completed (PNREF: %s) (PPREF: %s)', 'paypal-for-woocommerce'), $PayPalResult['PNREF'], $PayPalResult['PPREF']));
                    } else {
                        $order->add_order_note(sprintf(__('PayPal Pro Payflow payment completed (PNREF: %s)', 'paypal-for-woocommerce'), $PayPalResult['PNREF']));
                    }
                    if( $this->default_order_status == 'Completed' ) {
                        $order->update_status('completed');
                        if ($old_wc) {
                           update_post_meta($order_id, '_transaction_id', $PayPalResult['PNREF']);
                       } else {
                           update_post_meta($order->get_id(), '_transaction_id', $PayPalResult['PNREF']);
                       }
                   } else {
                       $order->payment_complete($PayPalResult['PNREF']);
                   }
                }
                $this->save_payment_token($order, $PayPalResult['PNREF']);
                $this->are_reference_transactions_enabled($PayPalResult['PNREF']);
                if ($this->is_subscription($order_id)) {
                    return true;
                }
            } else {
                $order->update_status('failed', __('PayPal Pro Payflow payment failed. Payment was rejected due to an error: ', 'paypal-for-woocommerce') . '(' . $PayPalResult['RESULT'] . ') ' . '"' . $PayPalResult['RESPMSG'] . '"');
                if ($this->error_email_notify) {
                    $admin_email = get_option("admin_email");
                    $message = __("PayFlow API call failed.", "paypal-for-woocommerce") . "\n\n";
                    $message .= __('Error Code: ', 'paypal-for-woocommerce') . $PayPalResult['RESULT'] . "\n";
                    $message .= __('Detailed Error Message: ', 'paypal-for-woocommerce') . $PayPalResult['RESPMSG'];
                    $message .= isset($PayPalResult['PREFPSMSG']) && $PayPalResult['PREFPSMSG'] != '' ? ' - ' . $PayPalResult['PREFPSMSG'] . "\n" : "\n";
                    $message .= __('User IP: ', 'paypal-for-woocommerce') . AngellEYE_Utility::get_user_ip() . "\n";
                    $message .= __('Order ID: ') . $order_id . "\n";
                    $message .= __('Customer Name: ') . $firstname . ' ' . $lastname . "\n";
                    $message .= __('Customer Email: ') . $billing_email . "\n";
                    $message = apply_filters('ae_pppf_error_email_message', $message);
                    $subject = apply_filters('ae_pppf_error_email_subject', "PayPal Pro Payflow Error Notification");
                    wp_mail($admin_email, $subject, $message);
                }
                return;
            }
        } catch (Exception $e) {
            if ($order->has_status(array('pending', 'failed'))) {
                $this->send_failed_order_email($order_id);
            }
            return;
        }
    }

    public function are_reference_transactions_enabled($token_id) {
        if ($this->supports('tokenization') && class_exists('WC_Subscriptions_Order')) {
            $are_reference_transactions_enabled = get_option('are_reference_transactions_enabled', 'no');
            if ($are_reference_transactions_enabled == 'no') {
                $customer_id = get_current_user_id();
                $this->angelleye_load_paypal_payflow_class($this->gateway, $this, null);
                $this->validate_fields();
                $card = $this->get_posted_card();
                $billtofirstname = (get_user_meta($customer_id, 'billing_first_name', true)) ? get_user_meta($customer_id, 'billing_first_name', true) : get_user_meta($customer_id, 'shipping_first_name', true);
                $billtolastname = (get_user_meta($customer_id, 'billing_last_name', true)) ? get_user_meta($customer_id, 'billing_last_name', true) : get_user_meta($customer_id, 'shipping_last_name', true);
                $billtostate = (get_user_meta($customer_id, 'billing_state', true)) ? get_user_meta($customer_id, 'billing_state', true) : get_user_meta($customer_id, 'shipping_state', true);
                $billtocountry = (get_user_meta($customer_id, 'billing_country', true)) ? get_user_meta($customer_id, 'billing_country', true) : get_user_meta($customer_id, 'shipping_country', true);
                $billtozip = (get_user_meta($customer_id, 'billing_postcode', true)) ? get_user_meta($customer_id, 'billing_postcode', true) : get_user_meta($customer_id, 'shipping_postcode', true);
                $PayPalRequestData = array(
                    'tender' => 'C',
                    'trxtype' => 'A',
                    'acct' => '',
                    'expdate' => '',
                    'amt' => '0.00',
                    'currency' => get_woocommerce_currency(),
                    'cvv2' => '',
                    'orderid' => '',
                    'orderdesc' => '',
                    'billtoemail' => '',
                    'billtophonenum' => '',
                    'billtofirstname' => $billtofirstname,
                    'billtomiddlename' => '',
                    'billtolastname' => $billtolastname,
                    'billtostreet' => '',
                    'billtocity' => '',
                    'billtostate' => $billtostate,
                    'billtozip' => $billtozip,
                    'billtocountry' => $billtocountry,
                    'origid' => $token_id,
                    'custref' => '',
                    'custcode' => '',
                    'custip' => AngellEYE_Utility::get_user_ip(),
                    'invnum' => '',
                    'ponum' => '',
                    'starttime' => '',
                    'endtime' => '',
                    'securetoken' => '',
                    'partialauth' => '',
                    'authcode' => ''
                );
                $PayPalResult = $this->PayPal->ProcessTransaction($PayPalRequestData);
                if (isset($PayPalResult['RESULT']) && ($PayPalResult['RESULT'] == 117)) {
                    $admin_email = get_option("admin_email");
                    $message = __("PayPal Reference Transactions are not enabled on your account, some subscription management features are not enabled", "paypal-for-woocommerce") . "\n\n";
                    $message .= __("PayFlow API call failed.", "paypal-for-woocommerce") . "\n\n";
                    $message .= __('Error Code: ', 'paypal-for-woocommerce') . $PayPalResult['RESULT'] . "\n";
                    $message .= __('Detailed Error Message: ', 'paypal-for-woocommerce') . $PayPalResult['RESPMSG'];
                    $message .= isset($PayPalResult['PREFPSMSG']) && $PayPalResult['PREFPSMSG'] != '' ? ' - ' . $PayPalResult['PREFPSMSG'] . "\n" : "\n";
                    $message .= __('User IP: ', 'paypal-for-woocommerce') . AngellEYE_Utility::get_user_ip() . "\n";
                    $message = apply_filters('ae_pppf_error_email_message', $message);
                    $subject = apply_filters('ae_pppf_error_email_subject', "PayPal Payments Pro (PayFlow) Error Notification");
                    wp_mail($admin_email, $subject, $message);
                    return false;
                } else {
                    update_option('are_reference_transactions_enabled', 'yes');
                }
            }
        }
    }

    public function send_failed_order_email($order_id) {
        $emails = WC()->mailer()->get_emails();
        if (!empty($emails) && !empty($order_id)) {
            $emails['WC_Email_Failed_Order']->trigger($order_id);
        }
    }

    public function save_payment_token($order, $payment_tokens_id) {
        // Store source in the order
        if (!empty($payment_tokens_id)) {
            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            update_post_meta($order_id, '_payment_tokens_id', $payment_tokens_id);
        }
    }

    public function angelleye_paypal_pro_payflow_encrypt_gateway_api($settings) {
        if (!empty($settings['sandbox_paypal_partner'])) {
            $paypal_partner = $settings['sandbox_paypal_partner'];
        } else {
            $paypal_partner = $settings['paypal_partner'];
        }
        if (strlen($paypal_partner) > 28) {
            return $settings;
        }

        if (!empty($settings['is_encrypt'])) {
            $gateway_settings_keys = array('sandbox_paypal_vendor', 'sandbox_paypal_password', 'sandbox_paypal_user', 'sandbox_paypal_partner', 'paypal_vendor', 'paypal_password', 'paypal_user', 'paypal_partner');
            foreach ($gateway_settings_keys as $gateway_settings_key => $gateway_settings_value) {
                if (!empty($settings[$gateway_settings_value])) {
                    $settings[$gateway_settings_value] = AngellEYE_Utility::crypting($settings[$gateway_settings_value], $action = 'e');
                }
            }
        }
        return $settings;
    }

    public function angelleye_paypal_pro_payflow_email_instructions($order, $sent_to_admin, $plain_text = false) {
        $payment_method = version_compare(WC_VERSION, '3.0', '<') ? $order->payment_method : $order->get_payment_method();
        if ($sent_to_admin && 'paypal_pro_payflow' === $payment_method) {
            // Store source in the order
            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            $this->angelleye_load_paypal_payflow_class($this->gateway, $this, $order_id);
            $old_wc = version_compare(WC_VERSION, '3.0', '<');
            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            $avscode = $old_wc ? get_post_meta($order->id, '_AVSADDR', true) : get_post_meta($order->get_id(), '_AVSADDR', true);
            if (!empty($avscode)) {
                $avs_response_message = $this->PayPal->GetAVSCodeMessage($avscode);
                echo '<section class="woocommerce-bacs-bank-details"><h3 class="wc-avs-details-heading">' . __('Address Verification Details', 'paypal-for-woocommerce') . '</h3>' . PHP_EOL;
                echo '<ul class="wc-avs-details order_details avs_details">' . PHP_EOL;
                $avs_details_fields = apply_filters('angelleye_avs_details_fields', array(
                    'avs_response_code' => array(
                        'label' => __('AVS Response Code', 'paypal-for-woocommerce'),
                        'value' => $avscode
                    ),
                    'avs_response_message' => array(
                        'label' => __('AVS Response Message', 'paypal-for-woocommerce'),
                        'value' => $avs_response_message
                    )
                        ), $order_id);
                foreach ($avs_details_fields as $field_key => $field) {
                    if (!empty($field['value'])) {
                        echo '<li class="' . esc_attr($field_key) . '">' . esc_attr($field['label']) . ': <strong>' . wptexturize($field['value']) . '</strong></li>' . PHP_EOL;
                    }
                }
                echo '</ul></section>';
            }
            $cvvmatch = $old_wc ? get_post_meta($order->id, '_CVV2MATCH', true) : get_post_meta($order->get_id(), '_CVV2MATCH', true);
            if (!empty($cvvmatch)) {
                $cvv2_response_message = $this->PayPal->GetCVV2CodeMessage($cvvmatch);
                echo '<section class="woocommerce-bacs-bank-details"><h3 class="wc-cvv2-details-heading">' . __('Card Security Code Details', 'paypal-for-woocommerce') . '</h3>' . PHP_EOL;
                echo '<ul class="wc-cvv2-details order_details cvv2_details">' . PHP_EOL;
                $cvv_details_fields = apply_filters('angelleye_cvv2_details_fields', array(
                    'cvv2_response_code' => array(
                        'label' => __('CVV2 Response Code', 'paypal-for-woocommerce'),
                        'value' => $cvvmatch
                    ),
                    'cvv2_response_message' => array(
                        'label' => __('CVV2 Response Message', 'paypal-for-woocommerce'),
                        'value' => $cvv2_response_message
                    )
                        ), $order_id);
                foreach ($cvv_details_fields as $field_key => $field) {
                    if (!empty($field['value'])) {
                        echo '<li class="' . esc_attr($field_key) . '">' . esc_attr($field['label']) . ': <strong>' . wptexturize($field['value']) . '</strong></li>' . PHP_EOL;
                    }
                }
                echo '</ul></section>';
            }

        }
    }

    public function is_subscription($order_id) {
        return ( function_exists('wcs_order_contains_subscription') && ( wcs_order_contains_subscription($order_id) || wcs_is_subscription($order_id) || wcs_order_contains_renewal($order_id) ) );
    }

    public function angelleye_paypal_pro_payflow_reference_transaction_notice() {
        if(class_exists('AngellEYE_Utility')) {
            if (AngellEYE_Utility::is_display_angelleye_paypal_pro_payflow_reference_transaction_notice($this) == true) {
                echo '<div class="error"><p>' . sprintf(__("If using %s with Woo Token Payments (including the use of Woo Subscriptions) you will need to <a target='_blank' href='https://www.angelleye.com/paypal-woocommerce-subscriptions/'>enable Reference Transactions</a> in your PayPal/PayFlow Manager. | <a href=%s>%s</a>", 'paypal-for-woocommerce'), $this->method_title, '"' . esc_url(add_query_arg("ignore_paypal_pro_payflow_reference_transaction_notice", 0)) . '"', __("Hide this notice", 'paypal-for-woocommerce')) . '</p></div>';
            }
        }
    }

    public function angelleye_load_paypal_payflow_class($gateway, $current, $order_id = null) {
        do_action('angelleye_paypal_for_woocommerce_multi_account_api_paypal_payflow', $gateway, $current, $order_id);
        $this->credentials = array(
            'Sandbox' => $this->testmode,
            'APIUsername' => $this->paypal_user,
            'APIPassword' => trim($this->paypal_password),
            'APIVendor' => $this->paypal_vendor,
            'APIPartner' => $this->paypal_partner,
            'Force_tls_one_point_two' => $this->Force_tls_one_point_two
        );
        try {
            if (!class_exists('Angelleye_PayPal_WC')) {
                require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.class.php' );
            }
            if (!class_exists('Angelleye_PayPal_PayFlow')) {
                require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.payflow.class.php' );
            }
            $this->PayPal = new Angelleye_PayPal_PayFlow($this->credentials);
        } catch (Exception $ex) {

        }
    }
    
    public function init_settings() {
        parent::init_settings();
        $this->enabled  = ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'] ? 'yes' : 'no';
        $this->send_items_value = ! empty( $this->settings['send_items'] ) && 'yes' === $this->settings['send_items'] ? 'yes' : 'no';
        $this->send_items = 'yes' === $this->send_items_value;
    }
    
    public function subscription_change_payment($order_id) {
        $order = wc_get_order($order_id);
        if ( (!empty($_POST['wc-paypal_pro_payflow-payment-token']) && $_POST['wc-paypal_pro_payflow-payment-token'] != 'new')) {
            $token_id = wc_clean($_POST['wc-paypal_pro_payflow-payment-token']);
            $token = WC_Payment_Tokens::get($token_id);
            $this->save_payment_token($order, $token->get_token());
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        } else {
            $customer_id = get_current_user_id();
            $this->angelleye_load_paypal_payflow_class($this->gateway, $this, null);
            $this->validate_fields();
            $card = $this->get_posted_card();
            $billtofirstname = (get_user_meta($customer_id, 'billing_first_name', true)) ? get_user_meta($customer_id, 'billing_first_name', true) : get_user_meta($customer_id, 'shipping_first_name', true);
            $billtolastname = (get_user_meta($customer_id, 'billing_last_name', true)) ? get_user_meta($customer_id, 'billing_last_name', true) : get_user_meta($customer_id, 'shipping_last_name', true);
            $billtostate = (get_user_meta($customer_id, 'billing_state', true)) ? get_user_meta($customer_id, 'billing_state', true) : get_user_meta($customer_id, 'shipping_state', true);
            $billtocountry = (get_user_meta($customer_id, 'billing_country', true)) ? get_user_meta($customer_id, 'billing_country', true) : get_user_meta($customer_id, 'shipping_country', true);
            $billtozip = (get_user_meta($customer_id, 'billing_postcode', true)) ? get_user_meta($customer_id, 'billing_postcode', true) : get_user_meta($customer_id, 'shipping_postcode', true);
            $PayPalRequestData = array(
                'tender' => 'C',
                'trxtype' => 'A',
                'acct' => $card->number,
                'expdate' => $card->exp_month . $card->exp_year,
                'amt' => '0.00',
                'currency' => version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency(),
                'cvv2' => $card->cvc,
                'orderid' => '',
                'orderdesc' => '',
                'billtoemail' => '',
                'billtophonenum' => '',
                'billtofirstname' => $billtofirstname,
                'billtomiddlename' => '',
                'billtolastname' => $billtolastname,
                'billtostreet' => '',
                'billtocity' => '',
                'billtostate' => $billtostate,
                'billtozip' => $billtozip,
                'billtocountry' => $billtocountry,
                'origid' => '',
                'custref' => '',
                'custcode' => '',
                'custip' => AngellEYE_Utility::get_user_ip(),
                'invnum' => '',
                'ponum' => '',
                'starttime' => '',
                'endtime' => '',
                'securetoken' => '',
                'partialauth' => '',
                'authcode' => ''
            );
            $PayPalResult = $this->PayPal->ProcessTransaction(apply_filters('angelleye_woocommerce_paypal_payflow_request_args', $PayPalRequestData));
            if (isset($PayPalResult['RESULT']) && ($PayPalResult['RESULT'] == 0 || in_array($PayPalResult['RESULT'], $this->fraud_codes))) {
                if (in_array($PayPalResult['RESULT'], $this->fraud_codes)) {
                    wc_add_notice(__('The payment was flagged by a fraud filter, please check your PayPal Manager account to review and accept or deny the payment.', 'paypal-for-woocommerce'), 'error');
                    wp_redirect(wc_get_account_endpoint_url('payment-methods'));
                    exit();
                } else {
                    $customer_id = get_current_user_id();
                    $TRANSACTIONID = $PayPalResult['PNREF'];
                    $this->are_reference_transactions_enabled($TRANSACTIONID);
                    $token = new WC_Payment_Token_CC();
                    $token->set_token($TRANSACTIONID);
                    $token->set_gateway_id($this->id);
                    $token->set_card_type(AngellEYE_Utility::card_type_from_account_number($PayPalRequestData['acct']));
                    $token->set_last4(substr($PayPalRequestData['acct'], -4));
                    $token->set_expiry_month(substr($PayPalRequestData['expdate'], 0, 2));
                    $expiry_year = substr($PayPalRequestData['expdate'], 2, 3);
                    if (strlen($expiry_year) == 2) {
                        $expiry_year = $expiry_year + 2000;
                    }
                    $token->set_expiry_year($expiry_year);
                    $token->set_user_id($customer_id);
                    if( $token->validate() ) {
                        $this->save_payment_token($order, $TRANSACTIONID);
                        $save_result = $token->save();
                        if ($save_result) {
                           return array(
                                'result' => 'success',
                                'redirect' => $this->get_return_url($order)
                            );
                        }
                    } else {
                        throw new Exception( __( 'Invalid or missing payment token fields.', 'paypal-for-woocommerce' ) );
                    }
                }
            } else {
                wc_add_notice(__($PayPalResult['RESPMSG'], 'paypal-for-woocommerce'), 'error');
                wp_redirect(wc_get_account_endpoint_url('payment-methods'));
                exit();
            }
        }
    }
}
