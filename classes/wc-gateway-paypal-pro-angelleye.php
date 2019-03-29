<?php

/**
 * WC_Gateway_PayPal_Pro class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_PayPal_Pro_AngellEYE extends WC_Payment_Gateway_CC {

    /**
     * Store client
     */
    private $centinel_client = false;
    public $customer_id;
    public $PayPal;
    public $gateway;
    /**
     * __construct function.
     *
     * @access public
     * @return void
     */
    function __construct() {
        $this->id = 'paypal_pro';
        $this->method_title = __('PayPal Website Payments Pro (DoDirectPayment) ', 'paypal-for-woocommerce');
        $this->method_description = __('PayPal Website Payments Pro allows you to accept credit cards directly on your site without any redirection through PayPal.  You host the checkout form on your own web server, so you will need an SSL certificate to ensure your customer data is protected.', 'paypal-for-woocommerce');
        $this->has_fields = true;
        $this->liveurl = 'https://api-3t.paypal.com/nvp';
        $this->testurl = 'https://api-3t.sandbox.paypal.com/nvp';
        $this->liveurl_3ds = 'https://paypal.cardinalcommerce.com/maps/txns.asp';
        $this->testurl_3ds = 'https://centineltest.cardinalcommerce.com/maps/txns.asp';
        $this->available_card_types = apply_filters('woocommerce_paypal_pro_available_card_types', array(
            'GB' => array(
                'Visa' => 'Visa',
                'MasterCard' => 'MasterCard',
                'Maestro' => 'Maestro/Switch',
                'Solo' => 'Solo'
            ),
            'US' => array(
                'Visa' => 'Visa',
                'MasterCard' => 'MasterCard',
                'Discover' => 'Discover',
                'AmEx' => 'American Express'
            ),
            'CA' => array(
                'Visa' => 'Visa',
                'MasterCard' => 'MasterCard'
            ),
            'AU' => array(
                'Visa' => 'Visa',
                'MasterCard' => 'MasterCard',
                'Discover' => 'Discover',
                'AmEx' => 'American Express'
            )
        ));
        $this->iso4217 = apply_filters('woocommerce_paypal_pro_iso_currencies', array(
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
        // Load the settings.
        $this->init_settings();
        // Get setting values
        $this->send_items = 'yes' === $this->get_option('send_items', 'yes');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->api_username = $this->get_option('api_username');
        $this->api_password = $this->get_option('api_password');
        $this->api_signature = $this->get_option('api_signature');
        $this->testmode = 'yes' === $this->get_option('testmode', 'no');
        if( $this->testmode == false ) {
            $this->testmode = AngellEYE_Utility::angelleye_paypal_for_woocommerce_is_set_sandbox_product();
        }
        $this->invoice_id_prefix = $this->get_option('invoice_id_prefix');
        $this->error_email_notify = $this->get_option('error_email_notify');
        $this->error_display_type = $this->get_option('error_display_type');
        $this->enable_3dsecure = 'yes' === $this->get_option('enable_3dsecure', 'no');
        $this->liability_shift = 'yes' === $this->get_option('liability_shift', 'no');
        $this->debug = 'yes' === $this->get_option('debug', 'no');
        $this->payment_action = $this->get_option('payment_action', 'Sale');
        if($this->send_items === false) {
            $this->subtotal_mismatch_behavior = 'drop';
        } else {
            $this->subtotal_mismatch_behavior = $this->get_option('subtotal_mismatch_behavior', 'add');
        }
        $this->enable_notifyurl = $this->get_option('enable_notifyurl', 'no');
        $this->is_encrypt = $this->get_option('is_encrypt', 'no');
        $this->softdescriptor = $this->get_option('softdescriptor', '');
        $this->avs_cvv2_result_admin_email = 'yes' === $this->get_option('avs_cvv2_result_admin_email', 'no');
        $this->fraud_management_filters = $this->get_option('fraud_management_filters', 'place_order_on_hold_for_further_review');
        $this->notifyurl = '';
        if ($this->enable_notifyurl == 'yes') {
            $this->notifyurl = $this->get_option('notifyurl');
            if (isset($this->notifyurl) && !empty($this->notifyurl)) {
                $this->notifyurl = str_replace('&amp;', '&', $this->notifyurl);
            }
        }
        $this->enable_cardholder_first_last_name = 'yes' === $this->get_option('enable_cardholder_first_last_name', 'no');
        // 3DS
        if ($this->enable_3dsecure) {
            $this->centinel_pid = $this->get_option('centinel_pid');
            $this->centinel_mid = $this->get_option('centinel_mid');
            $this->centinel_pwd = $this->get_option('centinel_pwd');
            if (empty($this->centinel_pid) || empty($this->centinel_mid) || empty($this->centinel_pwd))
                $this->enable_3dsecure = false;
            $this->centinel_url = $this->testmode == false ? $this->liveurl_3ds : $this->testurl_3ds;
        }

        //fix ssl for image icon
        $this->icon = $this->get_option('card_icon', plugins_url('/assets/images/cards.png', plugin_basename(dirname(__FILE__))));
        if ( is_ssl() || 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) ) {
            $this->icon = preg_replace("/^http:/i", "https:", $this->icon);
        }
        $this->icon = apply_filters('woocommerce_paypal_pro_icon', $this->icon);
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
            'add_payment_method',
        );
        $this->enable_tokenized_payments = $this->get_option('enable_tokenized_payments', 'no');
        if($this->enable_tokenized_payments == 'yes') {
            $this->supports = array_merge($this->supports, array('add_payment_method','tokenization'));
        }
        $this->Force_tls_one_point_two = get_option('Force_tls_one_point_two', 'no');
        $this->credit_card_month_field = $this->get_option('credit_card_month_field', 'names');
        $this->credit_card_year_field = $this->get_option('credit_card_year_field', 'four_digit');
        $this->pending_authorization_order_status = $this->get_option('pending_authorization_order_status', 'On Hold');
        if ($this->testmode == true) {
            $this->api_username = $this->get_option('sandbox_api_username');
            $this->api_password = $this->get_option('sandbox_api_password');
            $this->api_signature = $this->get_option('sandbox_api_signature');
        }
        // Maestro
        if (!$this->enable_3dsecure) {
            unset($this->available_card_types['GB']['Maestro']);
        }

        // Hooks
        add_action('woocommerce_api_wc_gateway_paypal_pro_angelleye', array($this, 'handle_3dsecure'));
        /* 2.0.0 */
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_filter('woocommerce_settings_api_sanitized_fields_' . $this->id, array($this, 'angelleye_paypal_pro_encrypt_gateway_api'), 10, 1);
        if ($this->enable_cardholder_first_last_name) {
            add_action('woocommerce_credit_card_form_start', array($this, 'angelleye_woocommerce_credit_card_form_start'), 10, 1);
        }

        add_filter('woocommerce_credit_card_form_fields', array($this, 'angelleye_paypal_pro_credit_card_form_fields'), 10, 2);
        if( $this->avs_cvv2_result_admin_email ) {
            add_action( 'woocommerce_email_before_order_table', array( $this, 'angelleye_paypal_pro_email_instructions' ), 10, 3 );
        }

        $this->customer_id;
        if (class_exists('WC_Gateway_Calculation_AngellEYE')) {
            $this->calculation_angelleye = new WC_Gateway_Calculation_AngellEYE();
        } else {
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/wc-gateway-calculations-angelleye.php' );
            $this->calculation_angelleye = new WC_Gateway_Calculation_AngellEYE();
        }
        do_action( 'angelleye_paypal_for_woocommerce_multi_account_api_' . $this->id, $this, null, null );
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
                'label' => __('Enable PayPal Pro', 'paypal-for-woocommerce'),
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
                'default' => __('Pay with your credit card', 'paypal-for-woocommerce')
            ),

            'invoice_id_prefix' => array(
                'title' => __('Invoice ID Prefix', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Add a prefix to the invoice ID sent to PayPal. This can resolve duplicate invoice problems when working with multiple websites on the same PayPal account.', 'paypal-for-woocommerce'),
            ),
            'card_icon' => array(
                'title' => __('Card Icon', 'paypal-for-woocommerce'),
                'type' => 'text',
                'default' => plugins_url('/assets/images/cards.png', plugin_basename(dirname(__FILE__))),
                'class' => 'button_upload',
            ),
            'error_email_notify' => array(
                'title' => __('Error Email Notifications', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable admin email notifications for errors.', 'paypal-for-woocommerce'),
                'default' => 'yes',
                'description' => __('This will send a detailed error email to the WordPress site administrator if a PayPal API error occurs.', 'paypal-for-woocommerce')
            ),
             'testmode' => array(
                'title' => __('Test Mode', 'paypal-for-woocommerce'),
                'label' => __('Enable PayPal Sandbox/Test Mode', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Place the payment gateway in development mode.', 'paypal-for-woocommerce'),
                'default' => 'no'
            ),
            'sandbox_api_username' => array(
                'title' => __('Sandbox API Username', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Create sandbox accounts and obtain API credentials from within your
									<a href="http://developer.paypal.com">PayPal developer account</a>.', 'paypal-for-woocommerce'),
                'default' => '',
                'custom_attributes' => array( 'autocomplete' => 'off'),
            ),
            'sandbox_api_password' => array(
                'title' => __('Sandbox API Password', 'paypal-for-woocommerce'),
                'type' => 'password',
                'default' => '',
                'custom_attributes' => array( 'autocomplete' => 'off'),
            ),
            'sandbox_api_signature' => array(
                'title' => __('Sandbox API Signature', 'paypal-for-woocommerce'),
                'type' => 'password',
                'default' => '',
                'custom_attributes' => array( 'autocomplete' => 'off'),
            ),
            'api_username' => array(
                'title' => __('Live API Username', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Get your live account API credentials from your PayPal account profile <br />or by using
									<a target="_blank" href="https://www.paypal.com/us/cgi-bin/webscr?cmd=_login-api-run">this tool</a>.', 'paypal-for-woocommerce'),
                'default' => '',
                'custom_attributes' => array( 'autocomplete' => 'off'),
            ),
            'api_password' => array(
                'title' => __('Live API Password', 'paypal-for-woocommerce'),
                'type' => 'password',
                'default' => '',
                'custom_attributes' => array( 'autocomplete' => 'off'),
            ),
            'api_signature' => array(
                'title' => __('Live API Signature', 'paypal-for-woocommerce'),
                'type' => 'password',
                'default' => '',
                'custom_attributes' => array( 'autocomplete' => 'off'),
            ),
            'enable_3dsecure' => array(
                'title' => __('3DSecure', 'paypal-for-woocommerce'),
                'label' => __('Enable 3DSecure', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Allows UK merchants to pass 3-D Secure authentication data to PayPal for debit and credit cards. Updating your site with 3-D Secure enables your participation in the Verified by Visa and MasterCard SecureCode programs. (Required to accept Maestro)', 'paypal-for-woocommerce'),
                'default' => 'no'
            ),
            'centinel_pid' => array(
                'title' => __('Centinel PID', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('If enabling 3D Secure, enter your Cardinal Centinel Processor ID.', 'paypal-for-woocommerce'),
                'default' => ''
            ),
            'centinel_mid' => array(
                'title' => __('Centinel MID', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('If enabling 3D Secure, enter your Cardinal Centinel Merchant ID.', 'paypal-for-woocommerce'),
                'default' => ''
            ),
            'centinel_pwd' => array(
                'title' => __('Transaction Password', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => __('If enabling 3D Secure, enter your Cardinal Centinel Transaction Password.', 'paypal-for-woocommerce'),
                'default' => ''
            ),
            'liability_shift' => array(
                'title' => __('Liability Shift', 'paypal-for-woocommerce'),
                'label' => __('Require liability shift', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Only accept payments when liability shift has occurred.', 'paypal-for-woocommerce'),
                'default' => 'no'
            ),
            'error_display_type' => array(
                'title' => __('Error Display Type', 'paypal-for-woocommerce'),
                'type' => 'select',
                'label' => __('Display detailed or generic errors', 'paypal-for-woocommerce'),
                'css'      => 'max-width:150px;',
                'class' => 'error_display_type_option, wc-enhanced-select',
                'options' => array(
                    'detailed' => __('Detailed', 'paypal-for-woocommerce'),
                    'generic' => __('Generic', 'paypal-for-woocommerce')
                ),
                'description' => __('Detailed displays actual errors returned from PayPal.  Generic displays general errors that do not reveal details
									and helps to prevent fraudulant activity on your site.', 'paypal-for-woocommerce')
            ),
            'payment_action' => array(
                'title' => __('Payment Action', 'paypal-for-woocommerce'),
                'label' => __('Whether to process as a Sale or Authorization.', 'paypal-for-woocommerce'),
                'description' => __('Sale will capture the funds immediately when the order is placed.  Authorization will authorize the payment but will not capture the funds.'),
                'type' => 'select',
                'css'      => 'max-width:150px;',
                'class'    => 'wc-enhanced-select',
                'options' => array(
                    'Sale' => 'Sale',
                    'Authorization' => 'Authorization',
                ),
                'default' => 'Sale'
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
            'enable_notifyurl' => array(
                'title' => __('Enable PayPal IPN', 'paypal-for-woocommerce'),
                'label' => __('Enable Instant Payment Notification.', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'no',
                'class' => 'angelleye_enable_notifyurl'
            ),
            'enable_tokenized_payments' => array(
                'title' => __('Enable Tokenized Payments', 'paypal-for-woocommerce'),
                'label' => __('Enable Tokenized Payments', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Allow buyers to securely save payment details to their account for quick checkout / auto-ship orders in the future.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'class' => 'enable_tokenized_payments'
            ),
            'notifyurl' => array(
                'title' => __('PayPal IPN URL', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Your URL for receiving Instant Payment Notification (IPN) about transactions.', 'paypal-for-woocommerce'),
                'class' => 'angelleye_notifyurl'
            ),
            'fraud_management_filters' => array(
                'title' => __('Fraud Management Filters ', 'paypal-for-woocommerce'),
                'label' => '',
                'description' => __('Choose how you would like to handle orders when Fraud Management Filters are flagged.', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class'    => 'wc-enhanced-select',
                'options' => array(
                    'ignore_warnings_and_proceed_as_usual' => __('Ignore warnings and proceed as usual.', 'paypal-for-woocommerce'),
                    'place_order_on_hold_for_further_review' => __('Place order On Hold for further review.', 'paypal-for-woocommerce'),
                ),
                'default' => 'place_order_on_hold_for_further_review',
                'desc_tip' => true,
            ),
            'enable_cardholder_first_last_name' => array(
                'title' => __('Enable Cardholder Name', 'paypal-for-woocommerce'),
                'label' => __('Adds fields for "card holder name" to checkout in addition to the "billing name" fields.', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Display card holder first and last name in credit card form.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'desc_tip' => true,
            ),
            'avs_cvv2_result_admin_email' => array(
                'title' => __('AVS / CVV2 Results in Admin Order Email', 'paypal-for-woocommerce'),
                'label' => __('Adds the AVS / CVV2 results to the admin order email notification.', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Display Address Verification Result (AVS) and Card Security Code Result (CVV2) Results in Admin Order Email.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'desc_tip' => true,
            ),
            'softdescriptor' => array(
                'title' => __('Credit Card Statement Name', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('The value entered here will be displayed on the buyer\'s credit card statement.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
            ),
            'credit_card_month_field' => array(
                'title' => __('Credit Card Month Format', 'paypal-for-woocommerce'),
                'label' => __('Credit Card Month Display Format.', 'paypal-for-woocommerce'),
                'description' => __('Choose whether you wish to display Name format or Number format of Month field in the credit card form.'),
                'type' => 'select',
                'css'      => 'max-width:200px;',
                'class'    => 'wc-enhanced-select',
                'options' => array(
                    'numbers' => 'Numbers',
                    'names' => 'Names',
                ),
                'default' => 'names'
            ),
            'credit_card_year_field' => array(
                'title' => __('Credit Card Year Format', 'paypal-for-woocommerce'),
                'label' => __('Credit Card Year Display Format.', 'paypal-for-woocommerce'),
                'description' => __('Choose whether you wish to display two digit format or four digit of Year field in the credit card form.'),
                'type' => 'select',
                'css'      => 'max-width:200px;',
                'class'    => 'wc-enhanced-select',
                'options' => array(
                    'two_digit' => 'Show Two Digit Years',
                    'four_digit' => 'Show Four Digit Years',
                ),
                'default' => 'four_digit'
            ),
            'debug' => array(
                'title' => __('Debug Log', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'paypal-for-woocommerce'),
                'default' => 'no',
                'description' => sprintf( __( 'Log PayPal events, inside <code>%s</code>', 'paypal-for-woocommerce' ), wc_get_log_file_path( 'paypal-pro' ) )
            ),
            'is_encrypt' => array(
                'title' => __('', 'paypal-for-woocommerce'),
                'label' => __('', 'paypal-for-woocommerce'),
                'type' => 'hidden',
                'default' => 'yes',
                'class' => ''
            )
        );
        $this->form_fields = apply_filters('angelleye_pc_form_fields', $this->form_fields);
    }


    public function admin_options() {
        echo '<h2>' . esc_html( $this->get_method_title() ) . '</h2>';
        echo wp_kses_post( wpautop( $this->get_method_description() ) );
        ?>
        <div id="angelleye_paypal_marketing_table">
        <table class="form-table">
            <?php
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
            jQuery('#woocommerce_paypal_pro_payment_action').change(function () {
                if ( this.value === 'Authorization' ) {
                    jQuery('#woocommerce_paypal_pro_pending_authorization_order_status').closest('tr').show();
                } else {
                    jQuery('#woocommerce_paypal_pro_pending_authorization_order_status').closest('tr').hide();
                }
            }).change();
            jQuery('#woocommerce_paypal_pro_testmode').change(function () {
                var sandbox = jQuery('#woocommerce_paypal_pro_sandbox_api_username, #woocommerce_paypal_pro_sandbox_api_password, #woocommerce_paypal_pro_sandbox_api_signature').closest('tr'),
                production = jQuery('#woocommerce_paypal_pro_api_username, #woocommerce_paypal_pro_api_password, #woocommerce_paypal_pro_api_signature').closest('tr');
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
    function is_available() {
        if ($this->enabled == "yes") :
            if ($this->testmode == false && !is_ssl()) return false;
            // Currency check
            if (!in_array(get_woocommerce_currency(), apply_filters('woocommerce_paypal_pro_supported_currencies', array('AUD', 'CAD', 'CZK', 'DKK', 'EUR', 'HUF', 'JPY', 'NOK', 'NZD', 'PLN', 'GBP', 'SGD', 'SEK', 'CHF', 'USD')))) return false;
            // Required fields check
            if (!$this->api_username || !$this->api_password || !$this->api_signature) return false;
            return isset($this->available_card_types[WC()->countries->get_base_country()]);
        endif;
        return false;
    }

    /**
     * Add a log entry
     */
    public function log($message, $level = 'info') {
        if ($this->debug) {
            if (version_compare(WC_VERSION, '3.0', '<')) {
                if (empty($this->log)) {
                    $this->log = new WC_Logger();
                }
                $this->log->add('paypal-pro', $message);
            } else {
                if (empty($this->log)) {
                    $this->log = wc_get_logger();
                }
                $this->log->log($level, $message, array('source' => 'paypal-pro'));
            }
        }
    }

    /**
     * Payment form on checkout page
     */
    public function payment_fields() {
        do_action('before_angelleye_pc_payment_fields', $this);
        if ($this->description) {
            echo '<p>' . wp_kses_post($this->description);
            if ($this->testmode == true) {
                echo '<p>';
                _e('NOTICE: SANDBOX (TEST) MODE ENABLED.', 'paypal-for-woocommerce');
                echo '<br />';
                _e('For testing purposes you can use the card number 4916311462114485 with any CVC and a valid expiration date.', 'paypal-for-woocommerce');
                echo '</p>';
            }
        }
       if ( $this->supports( 'tokenization' ) && is_checkout() ) {
            $this->tokenization_script();
            $this->saved_payment_methods();
            $this->form();
            if( AngellEYE_Utility::is_cart_contains_subscription() == false && AngellEYE_Utility::is_subs_change_payment() == false) {
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

    public function paypal_for_woocommerce_paypal_pro_credit_card_form_expiration_date_selectbox($class) {
        $form_html = "";
        $form_html .= '<p class="' . $class . '">';
        $form_html .= '<label for="cc-expire-month">' . apply_filters( 'cc_form_label_expiry', __("Expiration Date", 'paypal-for-woocommerce'), $this->id ) . '<span class="required">*</span></label>';
        $form_html .= '<select name="paypal_pro_card_expiration_month" id="cc-expire-month" class="woocommerce-select woocommerce-cc-month mr5">';
        $form_html .= '<option value="">' . __('Month', 'paypal-for-woocommerce') . '</option>';
        $months = array();
        for ($i = 1; $i <= 12; $i++) :
            $timestamp = mktime(0, 0, 0, $i, 1);
            $months[date('n', $timestamp)] = date_i18n(_x('F', 'Month Names', 'paypal-for-woocommerce'), $timestamp);
        endfor;
        foreach ($months as $num => $name) {
            if($this->credit_card_month_field == 'names') {
            $form_html .= '<option value=' . $num . '>' . $name . '</option>';
            } else {
                $month_value = ($num < 10) ? '0'.$num : $num;
                $form_html .= '<option value=' . $num . '>' . $month_value . '</option>';
            }
        }
        $form_html .= '</select>';
        $form_html .= '<select name="paypal_pro_card_expiration_year" id="cc-expire-year" class="woocommerce-select woocommerce-cc-year ml5">';
        $form_html .= '<option value="">' . __('Year', 'paypal-for-woocommerce') . '</option>';
        for ($i = date('y'); $i <= date('y') + 15; $i++) {
            if($this->credit_card_year_field == 'four_digit') {
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
     * Format and get posted details
     * @return object
     */
    private function get_posted_card() {
        $card_number = isset($_POST['paypal_pro-card-number']) ? wc_clean($_POST['paypal_pro-card-number']) : '';
        $card_cvc = isset($_POST['paypal_pro-card-cvc']) ? wc_clean($_POST['paypal_pro-card-cvc']) : '';
        $card_exp_month = isset($_POST['paypal_pro_card_expiration_month']) ? wc_clean($_POST['paypal_pro_card_expiration_month']) : '';
        $card_exp_year = isset($_POST['paypal_pro_card_expiration_year']) ? wc_clean($_POST['paypal_pro_card_expiration_year']) : '';

        // Format values
        $card_number = str_replace(array(' ', '-'), '', $card_number);

        if (isset($_POST['paypal_pro-card-startdate'])) {
            $card_start = wc_clean($_POST['paypal_pro-card-startdate']);
            $card_start = array_map('trim', explode('/', $card_start));
            $card_start_month = str_pad($card_start[0], 2, "0", STR_PAD_LEFT);
            $card_start_year = $card_start[1];
        } else {
            $card_start_month = '';
            $card_start_year = '';
        }

        $card_exp_month = (int) $card_exp_month;
        if ($card_exp_month < 10) {
            $card_exp_month = '0' . $card_exp_month;
        }

        if (strlen($card_exp_year) == 2) {
            $card_exp_year += 2000;
        }

        if (strlen($card_start_year) == 2) {
            $card_start_year += 2000;
        }
        
        $card_type = AngellEYE_Utility::card_type_from_account_number($card_number);
        
        return (object) array(
                    'number' => $card_number,
                    'type' => $card_type,
                    'cvc' => $card_cvc,
                    'exp_month' => $card_exp_month,
                    'exp_year' => $card_exp_year,
                    'start_month' => $card_start_month,
                    'start_year' => $card_start_year
        );
    }

    public function validate_fields() {
        try {
            if (isset($_POST['wc-paypal_pro-payment-token']) && 'new' !== $_POST['wc-paypal_pro-payment-token']) {
                $token_id = wc_clean($_POST['wc-paypal_pro-payment-token']);
                $token = WC_Payment_Tokens::get($token_id);
                if ($token->get_user_id() !== get_current_user_id()) {
                    throw new Exception(__('Error processing checkout. Please try again.', 'paypal-for-woocommerce'));
                } else {
                    return true;
                }
            }
            $card = $this->get_posted_card();
            do_action('before_angelleye_pro_checkout_validate_fields', $card->type, $card->number, $card->cvc, $card->exp_month, $card->exp_year);
            
            if (empty($card->number) || !ctype_digit((string) $card->number)) {
                throw new Exception(__('Card number is invalid', 'paypal-for-woocommerce'));
            }
            if (empty($card->exp_month) || empty($card->exp_year)) {
                throw new Exception(__('Card expiration date is invalid', 'paypal-for-woocommerce'));
            }

            // Validate values
            if (!ctype_digit((string) $card->cvc)) {
                throw new Exception(__('Card security code is invalid (only digits are allowed)', 'paypal-for-woocommerce'));
            }

            if (
                    !ctype_digit((string) $card->exp_month) ||
                    !ctype_digit((string) $card->exp_year) ||
                    $card->exp_month > 12 ||
                    $card->exp_month < 1 ||
                    $card->exp_year < date('y')
            ) {
                throw new Exception(__('Card expiration date is invalid', 'paypal-for-woocommerce'));
            }

            $card_type = AngellEYE_Utility::card_type_from_account_number($card->number);

            if ($card_type == 'amex' && (get_woocommerce_currency() != 'USD' && get_woocommerce_currency() != 'AUD')) {
                throw new Exception(__('Your processor is unable to process the Card Type in the currency requested. Please try another card type', 'paypal-for-woocommerce'));
            }

            do_action('after_angelleye_pro_checkout_validate_fields', $card->type, $card->number, $card->cvc, $card->exp_month, $card->exp_year);
            return true;

        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            return false;
        }
    }


    /**
     * Process the payment
     */
    function process_payment($order_id) {
        $order = new WC_Order($order_id);

        $this->log('Processing order #' . $order_id);

        $card = $this->get_posted_card();


        /**
         * 3D Secure Handling
         */
        if (!empty($_POST['wc-paypal_pro-payment-token']) && $_POST['wc-paypal_pro-payment-token'] != 'new') {
            $this->enable_3dsecure = false;
        }
        if ($this->enable_3dsecure) {
            if (!class_exists('CentinelClient')) include_once('lib/CentinelClient.php');
            $this->clear_centinel_session();
            $this->centinel_client = new CentinelClient;
            $this->centinel_client->add("MsgType", "cmpi_lookup");
            $this->centinel_client->add("Version", "1.7");
            $this->centinel_client->add("ProcessorId", $this->centinel_pid);
            $this->centinel_client->add("MerchantId", $this->centinel_mid);
            $this->centinel_client->add("TransactionPwd", $this->centinel_pwd);
            $this->centinel_client->add("TransactionType", 'CC');
            $this->centinel_client->add('OrderNumber', $order_id);
            $this->centinel_client->add('Amount', $order->get_total() * 100);
            $this->centinel_client->add('CurrencyCode', $this->iso4217[version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency()]);
            $this->centinel_client->add('TransactionMode', 'S');
            $this->centinel_client->add('ProductCode', 'PHY');
            $this->centinel_client->add('CardNumber', $card->number);
            WC()->session->set('CardNumber', $card->number);
            $this->centinel_client->add('CardExpMonth', $card->exp_month);
            WC()->session->set('CardExpMonth', $card->exp_month);
            $this->centinel_client->add('CardExpYear', $card->exp_year);
            WC()->session->set('CardExpYear', $card->exp_year);
            $this->centinel_client->add('CardCode', $card->cvc);
            WC()->session->set('CardCode', $card->cvc);

            $billing_first_name = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_first_name : $order->get_billing_first_name();
            $billing_last_name = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_last_name : $order->get_billing_last_name();
            $billing_address_1 = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_address_1 : $order->get_billing_address_1();
            $billing_address_2 = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_address_2 : $order->get_billing_address_2();
            $billing_city = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_city : $order->get_billing_city();
            $billing_postcode = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_postcode : $order->get_billing_postcode();
            $billing_country = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_country : $order->get_billing_country();
            $billing_state = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_state : $order->get_billing_state();
            $billing_phone = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_phone : $order->get_billing_phone();

            $this->centinel_client->add('BillingFirstName', $billing_first_name);
            $this->centinel_client->add('BillingLastName', $billing_last_name);
            $this->centinel_client->add('BillingAddress1', $billing_address_1);
            $this->centinel_client->add('BillingAddress2', $billing_address_2);
            $this->centinel_client->add('BillingCity', $billing_city);
            $this->centinel_client->add('BillingState', $billing_state);
            $this->centinel_client->add('BillingPostalCode', $billing_postcode);
            $this->centinel_client->add('BillingCountryCode', $billing_country);
            $this->centinel_client->add('BillingPhone', $billing_phone);
            $this->centinel_client->add('ShippingFirstName', version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_first_name : $order->get_shipping_first_name());
            $this->centinel_client->add('ShippingLastName', version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_last_name : $order->get_shipping_last_name());
            $this->centinel_client->add('ShippingAddress1', version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_address_1 : $order->get_shipping_address_1());
            $this->centinel_client->add('ShippingAddress2', version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_address_2 : $order->get_shipping_address_2());
            $this->centinel_client->add('ShippingCity', version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_city : $order->get_shipping_city());
            $this->centinel_client->add('ShippingState', version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_state : $order->get_shipping_state());
            $this->centinel_client->add('ShippingPostalCode', version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_postcode : $order->get_shipping_postcode());
            $this->centinel_client->add('ShippingCountryCode', version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_country : $order->get_shipping_country());

            // Items
            $item_loop = 0;
            if (sizeof($order->get_items()) > 0) {
                foreach ($order->get_items() as $item) {
                    $item_loop++;
                    $this->centinel_client->add('Item_Name_' . $item_loop, $item['name']);
                    $this->centinel_client->add('Item_Price_' . $item_loop, number_format($order->get_item_total($item, true, true) * 100), 2, '.', '');
                    $this->centinel_client->add('Item_Quantity_' . $item_loop, $item['qty']);
                    $this->centinel_client->add('Item_Desc_' . $item_loop, $item['name']);
                }
            }

            // Send request
            $this->centinel_client->sendHttp($this->centinel_url, "5000", "15000");

            $this->log('Centinal client request: ' . print_r($this->centinel_client->request, true));
            $this->log('Centinal client response: ' . print_r($this->centinel_client->response, true));


            // Save response in session
            WC()->session->set('Centinel_ErrorNo', $this->get_centinel_value("ErrorNo"));
            WC()->session->set('Centinel_ErrorDesc', $this->get_centinel_value("ErrorDesc"));
            WC()->session->set('Centinel_TransactionId', $this->get_centinel_value("TransactionId"));
            WC()->session->set('Centinel_OrderId', $this->get_centinel_value("OrderId"));
            WC()->session->set('Centinel_Enrolled', $this->get_centinel_value("Enrolled"));
            WC()->session->set('Centinel_ACSUrl', $this->get_centinel_value("ACSUrl"));
            WC()->session->set('Centinel_Payload', $this->get_centinel_value("Payload"));
            WC()->session->set('Centinel_EciFlag', $this->get_centinel_value("EciFlag"));
            WC()->session->set('Centinel_card_start_month', $card->start_month);
            WC()->session->set('Centinel_card_start_year', $card->start_year);


            if ($this->get_centinel_value("ErrorNo")) {
                wc_add_notice(apply_filters('angelleye_pc_process_payment_authentication', __('Error in 3D secure authentication: ', 'woocommerce-gateway-paypal-pro') . $this->get_centinel_value("ErrorDesc")), 'error');
                return;
            }

            if ('Y' === $this->get_centinel_value("Enrolled")) {
                $this->log('Doing 3dsecure payment authorization');
                $this->log('ASCUrl: ' . $this->get_centinel_value("ACSUrl"));
                $this->log('PaReq: ' . $this->get_centinel_value("Payload"));

                return array(
                    'result' => 'success',
                    'redirect' => add_query_arg(array('acs' => $order_id), WC()->api_request_url('WC_Gateway_PayPal_Pro_AngellEYE', is_ssl()))
                );
            } elseif ($this->liability_shift && 'N' !== $this->get_centinel_value("Enrolled")) {
                wc_add_notice(apply_filters('angelleye_pc_process_payment_authentication_unavailable', __('Authentication unavailable. Please try a different payment method or card.', 'woocommerce-gateway-paypal-pro')), 'error');
                return;
            }

        }
        // Do payment with paypal
        return $this->do_payment($order, $card->number, $card->type, $card->exp_month, $card->exp_year, $card->cvc, '', '', '', '', '', $card->start_month, $card->start_year);
    }


    /**
     * Auth 3dsecure
     */
    public function handle_3dsecure() {
        if (!empty($_GET['acs'])) {
            $order_id = wc_clean($_GET['acs']);
            $acsurl = WC()->session->get('Centinel_ACSUrl');
            $payload = WC()->session->get('Centinel_Payload');
            ?>
            <html>
                <head>
                    <title>3DSecure Payment Authorisation</title>
                </head>
                <body>
                    <form name="frmLaunchACS" id="3ds_submit_form" method="POST" action="<?php echo esc_url($acsurl); ?>">
                        <input type="hidden" name="PaReq" value="<?php echo esc_attr($payload); ?>">
                        <input type="hidden" name="TermUrl"
                               value="<?php echo esc_attr(WC()->api_request_url('WC_Gateway_PayPal_Pro_AngellEYE', is_ssl())); ?>">
                        <input type="hidden" name="MD" value="<?php echo absint($order_id); ?>">
                        <noscript>
                        <input type="submit" class="button" id="3ds_submit" value="Submit"/>
                        </noscript>
                    </form>
                    <script>
                        document.frmLaunchACS.submit();
                    </script>
                </body>
            </html>
            <?php
            exit;
        } else {
            $this->authorise_3dsecure();
        }
    }

    function authorise_3dsecure() {

        if (!class_exists('CentinelClient')) {
            include_once('lib/CentinelClient.php');
        }

        $pares = !empty($_POST['PaRes']) ? $_POST['PaRes'] : '';
        $order_id = absint(!empty($_POST['MD']) ? $_POST['MD'] : 0);
        $order = wc_get_order($order_id);
        $redirect_url = $this->get_return_url($order);

        $this->log('authorise_3dsecure() for order ' . absint($order_id));
        $this->log('authorise_3dsecure() PARes ' . print_r($pares, true));

        /*         * *************************************************************************** */
        /*                                                                            */
        /*    If the PaRes is Not Empty then process the cmpi_authenticate message    */
        /*                                                                            */
        /*         * *************************************************************************** */
        try {
            // If the PaRes is Not Empty then process the cmpi_authenticate message
            if (!empty($pares)) {

                $this->centinel_client = new CentinelClient;
                $this->centinel_client->add('MsgType', 'cmpi_authenticate');
                $this->centinel_client->add("Version", "1.7");
                $this->centinel_client->add("ProcessorId", $this->centinel_pid);
                $this->centinel_client->add("MerchantId", $this->centinel_mid);
                $this->centinel_client->add("TransactionPwd", $this->centinel_pwd);
                $this->centinel_client->add("TransactionType", 'C');
                $this->centinel_client->add('TransactionId', WC()->session->get('Centinel_TransactionId'));
                $this->centinel_client->add('PAResPayload', $pares);
                $this->centinel_client->sendHttp($this->centinel_url, "5000", "15000");

                $response_to_log = $this->centinel_client->response;
                $response_to_log['CardNumber'] = 'XXX';
                $response_to_log['CardCode'] = 'XXX';
                $this->log('Centinal transaction ID ' . WC()->session->get('Centinel_TransactionId'));
                $this->log('Centinal client request : ' . print_r($this->centinel_client->request, true));
                $this->log('Centinal client response: ' . print_r($response_to_log, true));
                $this->log('3dsecure pa_res_status: ' . $this->get_centinel_value("PAResStatus"));

            }

            if ($this->liability_shift && ($this->get_centinel_value("EciFlag") == '07' || $this->get_centinel_value("EciFlag") == '01')) {
                $order->update_status('failed', __('3D Secure error: No liability shift', 'woocommerce-gateway-paypal-pro'));
                throw new Exception(apply_filters('angelleye_pc_3d_authentication_unavailable', __('Authentication unavailable.  Please try a different payment method or card.', 'woocommerce-gateway-paypal-pro')));
            }

            if (!$this->get_centinel_value("ErrorNo") && in_array($this->get_centinel_value("PAResStatus"), array('Y', 'A', 'U')) && "Y" === $this->get_centinel_value("SignatureVerification")) {

                // If we are here we can process the card

                $card = new stdClass();
                $card->number = $this->get_centinel_value("CardNumber");
                $card->type = '';
                $card->cvc = $this->get_centinel_value("CardCode");
                $card->exp_month = $this->get_centinel_value("CardExpMonth");
                $card->exp_year = $this->get_centinel_value("CardExpYear");
                $card->start_month = WC()->session->get('Centinel_card_start_month');
                $card->start_year = WC()->session->get('Centinel_card_start_year');

                $centinel = new stdClass();
                $centinel->paresstatus = $this->get_centinel_value("PAResStatus");
                $centinel->xid = $this->get_centinel_value("Xid");
                $centinel->cavv = $this->get_centinel_value("Cavv");
                $centinel->eciflag = $this->get_centinel_value("EciFlag");
                $centinel->enrolled = WC()->session->get('Centinel_Enrolled');
                $this->do_payment($order, $card->number, $card->type, $card->exp_month, $card->exp_year, $card->cvc, $centinel->paresstatus, "Y", $centinel->cavv, $centinel->eciflag, $centinel->xid, $card->start_month, $card->start_year);
                $this->clear_centinel_session();
                wp_safe_redirect($redirect_url);
                exit();

            } else {
                $order->update_status('failed', sprintf(apply_filters('angelleye_pc_3d_secure_authentication', __('3D Secure error: %s', 'woocommerce-gateway-paypal-pro')), $this->get_centinel_value("ErrorDesc")));
                throw new Exception(__('Payer Authentication failed. Please try a different payment method.', 'woocommerce-gateway-paypal-pro'));
            }
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            wp_redirect($order->get_checkout_payment_url(true));
            exit;
        }
    }

    /**
     * do_payment
     *
     * Makes the request to PayPal's DoDirectPayment API
     *
     * @access public
     * @param mixed $order
     * @param mixed $card_number
     * @param mixed $card_type
     * @param mixed $card_exp_month
     * @param mixed $card_exp_year
     * @param mixed $card_csc
     * @param string $centinelPAResStatus (default: '')
     * @param string $centinelEnrolled (default: '')
     * @param string $centinelCavv (default: '')
     * @param string $centinelEciFlag (default: '')
     * @param string $centinelXid (default: '')
     * @return void
     */
    function do_payment($order, $card_number, $card_type, $card_exp_month, $card_exp_year, $card_csc, $centinelPAResStatus = '', $centinelEnrolled = '', $centinelCavv = '', $centinelEciFlag = '', $centinelXid = '', $start_month = '', $start_year = '') {
        /*
         * Display message to user if session has expired.
         */
        if (sizeof(WC()->cart->get_cart()) == 0) {
            $pc_session_expired_error = apply_filters('angelleye_pc_session_expired_error', sprintf(__('Sorry, your session has expired. <a href=%s>Return to homepage &rarr;</a>', 'paypal-for-woocommerce'), '"' . home_url() . '"'));
            wc_add_notice($pc_session_expired_error, "error");
        }
        $card = $this->get_posted_card();
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        $this->angelleye_load_paypal_pro_class($this->gateway, $this, $order_id);

        if (empty($GLOBALS['wp_rewrite'])) {
            $GLOBALS['wp_rewrite'] = new WP_Rewrite();
        }

        if(!empty($_POST['paypal_pro-card-cardholder-first'])) {
            $firstname = wc_clean($_POST['paypal_pro-card-cardholder-first']);
        } else {
            $firstname = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_first_name : $order->get_billing_first_name();
        }

        if(!empty($_POST['paypal_pro-card-cardholder-last'])) {
            $lastname = wc_clean($_POST['paypal_pro-card-cardholder-last']);
        } else {
            $lastname = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_last_name : $order->get_billing_last_name();
        }

        $card_exp = $card_exp_month . $card_exp_year;

        /**
         * Generate PayPal request
         */
        $DPFields = array(
            'paymentaction' => ($order->get_total() > 0) ? $this->payment_action : 'Authorization', // How you want to obtain payment.  Authorization indidicates the payment is a basic auth subject to settlement with Auth & Capture.  Sale indicates that this is a final sale for which you are requesting payment.  Default is Sale.
            'ipaddress' => AngellEYE_Utility::get_user_ip(),                            // Required.  IP address of the payer's browser.
            'returnfmfdetails' => '1',                   // Flag to determine whether you want the results returned by FMF.  1 or 0.  Default is 0.
            'softdescriptor' => $this->softdescriptor
        );


        $CCDetails = array(
            'creditcardtype' => $card_type, // Required. Type of credit card.  Visa, MasterCard, Discover, Amex, Maestro, Solo.  If Maestro or Solo, the currency code must be GBP.  In addition, either start date or issue number must be specified.
            'acct' => $card_number, // Required.  Credit card number.  No spaces or punctuation.
            'expdate' => $card_exp, // Required.  Credit card expiration date.  Format is MMYYYY
            'cvv2' => $card_csc, // Requirements determined by your PayPal account settings.  Security digits for credit card.
            'startdate' => $start_month . $start_year, // Month and year that Maestro or Solo card was issued.  MMYYYY
            'issuenumber' => ''                            // Issue number of Maestro or Solo card.  Two numeric digits max.
        );


        $billing_address_1 = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_address_1 : $order->get_billing_address_1();
        $billing_address_2 = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_address_2 : $order->get_billing_address_2();
        $billing_city = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_city : $order->get_billing_city();
        $billing_postcode = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_postcode : $order->get_billing_postcode();
        $billing_country = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_country : $order->get_billing_country();
        $billing_state = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_state : $order->get_billing_state();
        $billing_email = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_email : $order->get_billing_email();
        $billing_phone = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_phone : $order->get_billing_phone();


        $PayerInfo = array(
            'email' => $billing_email,                                // Email address of payer.
            'firstname' => $firstname, // Required.  Payer's first name.
            'lastname' => $lastname                            // Required.  Payer's last name.
        );

        $BillingAddress = array(
            'street' => $billing_address_1,                        // Required.  First street address.
            'street2' => $billing_address_2,                        // Second street address.
            'city' => $billing_city,                            // Required.  Name of City.
            'state' => $billing_state,                            // Required. Name of State or Province.
            'countrycode' => $billing_country,                    // Required.  Country code.
            'zip' => $billing_postcode,                            // Required.  Postal code of payer.
            'phonenum' => $billing_phone                        // Phone Number of payer.  20 char max.
        );

        $shipping_first_name = version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_first_name : $order->get_shipping_first_name();
        $shipping_last_name = version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_last_name : $order->get_shipping_last_name();
        $ShippingAddress = array(
            'shiptoname' => $shipping_first_name . ' ' . $shipping_last_name,                    // Required if shipping is included.  Person's name associated with this address.  32 char max.
            'shiptostreet' => version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_address_1 : $order->get_shipping_address_1(),                    // Required if shipping is included.  First street address.  100 char max.
            'shiptostreet2' => version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_address_2 : $order->get_shipping_address_2(),                    // Second street address.  100 char max.
            'shiptocity' => version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_city : $order->get_shipping_city(),                    // Required if shipping is included.  Name of city.  40 char max.
            'shiptostate' => version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_state : $order->get_shipping_state(),                    // Required if shipping is included.  Name of state or province.  40 char max.
            'shiptozip' => version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_postcode : $order->get_shipping_postcode(),                        // Required if shipping is included.  Postal code of shipping address.  20 char max.
            'shiptocountry' => version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_country : $order->get_shipping_country(),                    // Required if shipping is included.  Country code of shipping address.  2 char max.
            'shiptophonenum' => version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_phone : $order->get_billing_phone()                    // Phone number for shipping address.  20 char max.
        );

        $customer_note_value = version_compare(WC_VERSION, '3.0', '<') ? wptexturize($order->customer_note) : wptexturize($order->get_customer_note());
        $customer_note = $customer_note_value ? substr(preg_replace("/[^A-Za-z0-9 ]/", "", $customer_note_value), 0, 256) : '';

        $PaymentDetails = array(
            'amt' => AngellEYE_Gateway_Paypal::number_format($order->get_total()),                            // Required.  Total amount of order, including shipping, handling, and tax.
            'currencycode' => version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency(),                    // Required.  Three-letter currency code.  Default is USD.
            'insuranceamt' => '',                    // Total shipping insurance costs for this order.
            'desc' => '',                            // Description of the order the customer is purchasing.  127 char max.
            'custom' => apply_filters( 'ae_ppddp_custom_parameter', json_encode( array( 'order_id' => version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id(), 'order_key' => version_compare( WC_VERSION, '3.0', '<' ) ? $order->order_key : $order->get_order_key() ) ) , $order ),                        // Free-form field for your own use.  256 char max.
            'invnum' => $this->invoice_id_prefix . preg_replace("/[^a-zA-Z0-9]/", "", str_replace("#","",$order->get_order_number())), // Your own invoice or tracking number
            'recurring' => ''                        // Flag to indicate a recurring transaction.  Value should be Y for recurring, or anything other than Y if it's not recurring.  To pass Y here, you must have an established billing agreement with the buyer.
        );

        if (isset($this->notifyurl) && !empty($this->notifyurl)) {
            $PaymentDetails['notifyurl'] = $this->notifyurl;
        }
        $PaymentData = $this->calculation_angelleye->order_calculation($order_id);
        $OrderItems = array();
        if( $PaymentData['is_calculation_mismatch'] == false ) {
            foreach ($PaymentData['order_items'] as $item) {
                $Item = array(
                    'l_name' => $item['name'], // Item Name.  127 char max.
                    'l_desc' => '', // Item description.  127 char max.
                    'l_amt' => $item['amt'], // Cost of individual item.
                    'l_number' => $item['number'], // Item Number.  127 char max.
                    'l_qty' => $item['qty'], // Item quantity.  Must be any positive integer.
                    'l_taxamt' => '', // Item's sales tax amount.
                    'l_ebayitemnumber' => '', // eBay auction number of item.
                    'l_ebayitemauctiontxnid' => '', // eBay transaction ID of purchased item.
                    'l_ebayitemorderid' => ''                // eBay order ID for the item.
                );
                array_push($OrderItems, $Item);
            }
            $PaymentDetails['taxamt'] = $PaymentData['taxamt'];
            $PaymentDetails['shippingamt'] = $PaymentData['shippingamt'];
            $PaymentDetails['itemamt'] = AngellEYE_Gateway_Paypal::number_format($PaymentData['itemamt']);
        } 

        /**
         * 3D Secure Params
         */
        if ($this->enable_3dsecure) {
            $Secure3D = array(
                'authstatus3ds' => $centinelPAResStatus,
                'mpivendor3ds' => $centinelEnrolled,
                'cavv' => $centinelCavv,
                'eci3ds' => $centinelEciFlag,
                'xid' => $centinelXid
            );
        } else {
            $Secure3D = array();
        }

        $PayPalRequestData = array(
            'DPFields' => $DPFields,
            'CCDetails' => $CCDetails,
            'PayerInfo' => $PayerInfo,
            'BillingAddress' => $BillingAddress,
            'ShippingAddress' => $ShippingAddress,
            'PaymentDetails' => $PaymentDetails,
            'OrderItems' => $OrderItems,
            'Secure3D' => $Secure3D
        );

        $log = $PayPalRequestData;
        $log['CCDetails']['acct'] = empty($log['CCDetails']['acct']) ? '****' : '';
        $log['CCDetails']['cvv2'] = empty($log['CCDetails']['cvv2']) ? '****' : '';
        $this->log('Do payment request ' . print_r($log, true));

        if (!empty($_POST['wc-paypal_pro-payment-token']) && $_POST['wc-paypal_pro-payment-token'] != 'new') {
            $token_id = wc_clean($_POST['wc-paypal_pro-payment-token']);
            $token = WC_Payment_Tokens::get($token_id);
            unset($PayPalRequestData['DPFields']);
            $PayPalRequestData['DRTFields'] = array(
                'referenceid' => $token->get_token(),
                'paymentaction' => ($order->get_total() > 0) ? $this->payment_action : 'Authorization',
                'returnfmfdetails' => '1',
                'softdescriptor' => $this->softdescriptor
            );
            $PayPalResult = $this->PayPal->DoReferenceTransaction(apply_filters('angelleye_woocommerce_paypal_pro_do_reference_transaction_request_args', $PayPalRequestData));
        } else {
            $PayPalResult = $this->PayPal->DoDirectPayment(apply_filters('angelleye_woocommerce_paypal_pro_do_direct_payment_request_args', $PayPalRequestData));
            $token = '';
        }

        // Pass data into class for processing with PayPal and load the response array into $PayPalResult


        /**
         *  cURL Error Handling #146
         * @since    1.1.8
         */

        AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($PayPalResult, $methos_name = 'DoDirectPayment', $gateway = 'PayPal Website Payments Pro (DoDirectPayment)', $this->error_email_notify);


        $PayPalRequest = isset($PayPalResult['RAWREQUEST']) ? $PayPalResult['RAWREQUEST'] : '';
        $PayPalResponse = isset($PayPalResult['RAWRESPONSE']) ? $PayPalResult['RAWRESPONSE'] : '';

        $this->log('Request: ' . print_r($this->PayPal->NVPToArray($this->PayPal->MaskAPIResult($PayPalRequest)), true));
        $this->log('Response: ' . print_r($this->PayPal->NVPToArray($this->PayPal->MaskAPIResult($PayPalResponse)), true));


        if (empty($PayPalResult['RAWRESPONSE'])) {
            $pc_empty_response = apply_filters('ae_ppddp_paypal_response_empty_message', __('Empty PayPal response.', 'paypal-for-woocommerce'), $PayPalResult);
            throw new Exception($pc_empty_response);
        }

        if ($this->PayPal->APICallSuccessful($PayPalResult['ACK'])) {
            // Add order note
            $order->add_order_note(sprintf(__('PayPal Pro (Transaction ID: %s, Correlation ID: %s)', 'paypal-for-woocommerce'), $PayPalResult['TRANSACTIONID'], $PayPalResult['CORRELATIONID']));
            //$order->add_order_note("PayPal Results: ".print_r($PayPalResult,true));

            /* Checkout Note */
            if (isset($_POST) && !empty($_POST['order_comments'])) {
                // Update post 37
                $order_id = version_compare( WC_VERSION, '3.0', '<' ) ? $order->id : $order->get_id();
                $checkout_note = array(
                    'ID' => $order_id,
                    'post_excerpt' => wc_clean($_POST['order_comments']),
                );
                wp_update_post($checkout_note);
            }

            /**
             * Add order notes for AVS result
             */
            $avs_response_code = isset($PayPalResult['AVSCODE']) ? $PayPalResult['AVSCODE'] : '';
            $avs_response_message = $this->PayPal->GetAVSCodeMessage($avs_response_code);
            $avs_response_order_note = __('Address Verification Result', 'paypal-for-woocommerce');
            $avs_response_order_note .= "\n";
            $avs_response_order_note .= $avs_response_code;
            $avs_response_order_note .= $avs_response_message != '' ? ' - ' . $avs_response_message : '';
            $order->add_order_note($avs_response_order_note);
            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            $old_wc = version_compare(WC_VERSION, '3.0', '<');
            if ($old_wc) {
                update_post_meta($order_id, '_AVSCODE', $avs_response_code);
            } else {
                update_post_meta($order->get_id(), '_AVSCODE', $avs_response_code);
            }
            /**
             * Add order notes for CVV2 result
             */
            $cvv2_response_code = isset($PayPalResult['CVV2MATCH']) ? $PayPalResult['CVV2MATCH'] : '';
            $cvv2_response_message = $this->PayPal->GetCVV2CodeMessage($cvv2_response_code);
            $cvv2_response_order_note = __('Card Security Code Result', 'paypal-for-woocommerce');
            $cvv2_response_order_note .= "\n";
            $cvv2_response_order_note .= $cvv2_response_code;
            $cvv2_response_order_note .= $cvv2_response_message != '' ? ' - ' . $cvv2_response_message : '';
            $order->add_order_note($cvv2_response_order_note);
            if ($old_wc) {
                update_post_meta($order_id, '_CVV2MATCH', $cvv2_response_code);
                update_post_meta($order_id, 'is_sandbox', $this->testmode);
            } else {
                update_post_meta($order->get_id(), '_CVV2MATCH', $cvv2_response_code);
                update_post_meta($order->get_id(), 'is_sandbox', $this->testmode);
            }
            do_action('ae_add_custom_order_note', $order, $card, $token, $PayPalResult);
            do_action('before_save_payment_token', $order_id);
            if(AngellEYE_Utility::angelleye_is_save_payment_token($this, $order_id)) {
                if( !empty($_POST['wc-paypal_pro-payment-token']) && $_POST['wc-paypal_pro-payment-token'] != 'new' ) {
                      $token_id = wc_clean( $_POST['wc-paypal_pro-payment-token'] );
                      $token = WC_Payment_Tokens::get( $token_id );
                      $order->add_payment_token($token);
                } else {
                    $TRANSACTIONID = $PayPalResult['TRANSACTIONID'];
                    $token = new WC_Payment_Token_CC();
                    if ( 0 != $order->get_user_id() ) {
                        $customer_id = $order->get_user_id();
                    } else {
                        $customer_id = get_current_user_id();
                    }
                    $token->set_token( $TRANSACTIONID );
                    $token->set_gateway_id( $this->id );
                    $token->set_card_type( AngellEYE_Utility::card_type_from_account_number($PayPalRequestData['CCDetails']['acct']));
                    $token->set_last4( substr( $PayPalRequestData['CCDetails']['acct'], -4 ) );
                    $token->set_expiry_month( substr( $PayPalRequestData['CCDetails']['expdate'], 0,2 ) );
                    $token->set_expiry_year( substr( $PayPalRequestData['CCDetails']['expdate'], 2,5 ) );
                    $token->set_user_id($customer_id);
                    if( $token->validate() ) {
                        $this->save_payment_token($order, $TRANSACTIONID);
                        $save_result = $token->save();
                        if ($save_result) {
                           $order->add_payment_token($token);
                        }
                    } else {
                        $order->add_order_note('ERROR MESSAGE: ' .  __( 'Invalid or missing payment token fields.', 'paypal-for-woocommerce' ));
                    }
                }
            } else {
                if( $this->is_subscription($order_id) ) {
                    $TRANSACTIONID = $PayPalResult['TRANSACTIONID'];
                    $this->save_payment_token($order, $TRANSACTIONID);
                }
            }
            // Payment complete
            if($PayPalResult['ACK'] == 'SuccessWithWarning' && !empty($PayPalResult['L_ERRORCODE0'])) {
                if($this->fraud_management_filters == 'place_order_on_hold_for_further_review' && $PayPalResult['L_ERRORCODE0'] == '11610') {
                    $error = !empty($PayPalResult['L_LONGMESSAGE0']) ? $PayPalResult['L_LONGMESSAGE0'] : $PayPalResult['L_SHORTMESSAGE0'];
                    $order->update_status('on-hold', $error);
                    $old_wc = version_compare(WC_VERSION, '3.0', '<');
                    if ( $old_wc ) {
                        if ( ! get_post_meta( $order_id, '_order_stock_reduced', true ) ) {
                            $order->reduce_order_stock();
                        }
                    } else {
                        wc_maybe_reduce_stock_levels( $order_id );
                    }
                } elseif ($PayPalResult['L_ERRORCODE0'] == '10574') {
                    $error = !empty($PayPalResult['L_LONGMESSAGE0']) ? $PayPalResult['L_LONGMESSAGE0'] : $PayPalResult['L_SHORTMESSAGE0'];
                    $order->add_order_note('ERROR MESSAGE: ' . $error);
                    $this->angelleye_update_status($order, $PayPalResult['TRANSACTIONID']);
                } elseif (!empty($PayPalResult['L_ERRORCODE0'])) {
                    $error = !empty($PayPalResult['L_LONGMESSAGE0']) ? $PayPalResult['L_LONGMESSAGE0'] : $PayPalResult['L_SHORTMESSAGE0'];
                    $order->add_order_note('ERROR MESSAGE: ' . $error);
                    $order->update_status('on-hold', $error);
                    $old_wc = version_compare(WC_VERSION, '3.0', '<');
                    if ( $old_wc ) {
                        if ( ! get_post_meta( $order_id, '_order_stock_reduced', true ) ) {
                            $order->reduce_order_stock();
                        }
                    } else {
                        wc_maybe_reduce_stock_levels( $order_id );
                    }
                } else {
                    $this->angelleye_update_status($order, $PayPalResult['TRANSACTIONID']);
                }
            } else {
                $this->angelleye_update_status($order, $PayPalResult['TRANSACTIONID']);
            }

            if ($this->payment_action == "Authorization") {
                if ($old_wc) {
                    update_post_meta($order_id, '_first_transaction_id', $PayPalResult['TRANSACTIONID']);
                } else {
                    update_post_meta($order->get_id(), '_first_transaction_id', $PayPalResult['TRANSACTIONID']);
                }
                $payment_order_meta = array('_transaction_id' => $PayPalResult['TRANSACTIONID'], '_payment_action' => $this->payment_action);
                AngellEYE_Utility::angelleye_add_order_meta($order_id, $payment_order_meta);
                AngellEYE_Utility::angelleye_paypal_for_woocommerce_add_paypal_transaction($PayPalResult, $order, $this->payment_action);
                $angelleye_utility = new AngellEYE_Utility(null, null);
                $angelleye_utility->angelleye_get_transactionDetails($PayPalResult['TRANSACTIONID']);
                $order->add_order_note('Payment Action: ' . $this->payment_action);
            }

            // Remove cart
            WC()->cart->empty_cart();

            // Return thank you page redirect
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        } else {
            // Get error message
            $error_code = isset($PayPalResult['ERRORS'][0]['L_ERRORCODE']) ? $PayPalResult['ERRORS'][0]['L_ERRORCODE'] : '';
            $long_message = isset($PayPalResult['ERRORS'][0]['L_LONGMESSAGE']) ? $PayPalResult['ERRORS'][0]['L_LONGMESSAGE'] : '';
            $error_message = $error_code . '-' . $long_message;

            // Notice admin if has any issue from PayPal
            if ($this->error_email_notify) {
                $admin_email = get_option("admin_email");
                $message = __("DoDirectPayment API call failed.", "paypal-for-woocommerce") . "\n\n";
                $message .= __('Error Code: ', 'paypal-for-woocommerce') . $error_code . "\n";
                $message .= __('Detailed Error Message: ', 'paypal-for-woocommerce') . $long_message . "\n";
                $message .= __('User IP: ', 'paypal-for-woocommerce') . AngellEYE_Utility::get_user_ip() . "\n";
                $message .= __('Order ID: ') . $order_id . "\n";
                $message .= __('Customer Name: ') . $firstname . ' ' . $lastname . "\n";
                $message .= __('Customer Email: ') . $billing_email . "\n";

                $pc_error_email_message = apply_filters('ae_ppddp_error_email_message', $message, $error_code, $long_message);
                $pc_error_email_subject = apply_filters('ae_ppddp_error_email_subject', "PayPal Pro Error Notification", $error_code, $long_message);

                wp_mail($admin_email, $pc_error_email_subject, $pc_error_email_message);
            }


            $this->log('Error ' . print_r($PayPalResult['ERRORS'], true));


            $order->update_status('failed', sprintf(__('PayPal Pro payment failed (Correlation ID: %s). Payment was rejected due to an error: %s',
                'paypal-for-woocommerce'), $PayPalResult['CORRELATIONID'], '(' . $PayPalResult['L_ERRORCODE0'] . ') ' . '"' . $error_message . '"'));

            // Generate error message based on Error Display Type setting
            if ($this->error_display_type == 'detailed') {
                $pc_display_type_error = __($error_message, 'paypal-for-woocommerce');
                $pc_display_type_notice = __('Payment error:', 'paypal-for-woocommerce') . ' ' . $error_message;
            } else {
                $pc_display_type_error = __('There was a problem connecting to the payment gateway.', 'paypal-for-woocommerce');
                $pc_display_type_notice = __('Payment error:', 'paypal-for-woocommerce') . ' ' . $error_message;
            }

            $pc_display_type_error = apply_filters('ae_ppddp_error_exception', $pc_display_type_error, $error_code, $long_message);
            $pc_display_type_notice = apply_filters('ae_ppddp_error_user_display_message', $pc_display_type_notice, $error_code, $long_message);
            throw new Exception($pc_display_type_error);
            return;
        }
    }

    /**
     * clear_centinel_session function.
     *
     * @access public
     * @return void
     */
    function clear_centinel_session() {
        WC()->session->set('Centinel_ErrorNo', null);
        WC()->session->set('Centinel_ErrorDesc', null);
        WC()->session->set('Centinel_TransactionId', null);
        WC()->session->set('Centinel_OrderId', null);
        WC()->session->set('Centinel_Enrolled', null);
        WC()->session->set('Centinel_ACSUrl', null);
        WC()->session->set('Centinel_Payload', null);
        WC()->session->set('Centinel_EciFlag', null);
        WC()->session->set('Centinel_card_start_month', null);
        WC()->session->set('Centinel_card_start_year', null);
    }

    /**
     * Process a refund if supported
     * @param  int $order_id
     * @param  float $amount
     * @param  string $reason
     * @return  bool|wp_error True or false based on success, or a WP_Error object
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        $this->log('Begin Refund');
        $this->log('Order ID: ' . print_r($order_id, true));
        $this->log('Transaction ID: ' . print_r($order->get_transaction_id(), true));
        if (!$order || !$order->get_transaction_id() || !$this->api_username || !$this->api_password || !$this->api_signature) {
            return false;
        }

        $this->angelleye_load_paypal_pro_class($this->gateway, $this, $order_id);
        if ($reason) {
            if (255 < strlen($reason)) {
                $reason = substr($reason, 0, 252) . '...';
            }

            $reason = html_entity_decode($reason, ENT_NOQUOTES, 'UTF-8');
        }

        // Prepare request arrays
        $RTFields = array(
            'transactionid' => $order->get_transaction_id(), // Required.  PayPal transaction ID for the order you're refunding.
            'payerid' => '', // Encrypted PayPal customer account ID number.  Note:  Either transaction ID or payer ID must be specified.  127 char max
            'invoiceid' => '', // Your own invoice tracking number.
            'refundtype' => $order->get_total() == $amount ? 'Full' : 'Partial', // Required.  Type of refund.  Must be Full, Partial, or Other.
            'amt' => AngellEYE_Gateway_Paypal::number_format($amount), // Refund Amt.  Required if refund type is Partial.
            'currencycode' => version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency(),                            // Three-letter currency code.  Required for Partial Refunds.  Do not use for full refunds.
            'note' => $reason, // Custom memo about the refund.  255 char max.
            'retryuntil' => '', // Maximum time until you must retry the refund.  Note:  this field does not apply to point-of-sale transactions.
            'refundsource' => '', // Type of PayPal funding source (balance or eCheck) that can be used for auto refund.  Values are:  any, default, instant, eCheck
            'merchantstoredetail' => '', // Information about the merchant store.
            'refundadvice' => '', // Flag to indicate that the buyer was already given store credit for a given transaction.  Values are:  1/0
            'refunditemdetails' => '', // Details about the individual items to be returned.
            'msgsubid' => '', // A message ID used for idempotence to uniquely identify a message.
            'storeid' => '', // ID of a merchant store.  This field is required for point-of-sale transactions.  50 char max.
            'terminalid' => ''                                // ID of the terminal.  50 char max.
        );

        $PayPalRequestData = array('RTFields' => $RTFields);

        // Pass data into class for processing with PayPal and load the response array into $PayPalResult
        $PayPalResult = $this->PayPal->RefundTransaction(apply_filters('angelleye_woocommerce_paypal_pro_refund_request_args', $PayPalRequestData));

        /**
         *  cURL Error Handling #146
         * @since    1.1.8
         */

        AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($PayPalResult, $methos_name = 'RefundTransaction', $gateway = 'PayPal Website Payments Pro (DoDirectPayment)', $this->error_email_notify);

        $PayPalRequest = isset($PayPalResult['RAWREQUEST']) ? $PayPalResult['RAWREQUEST'] : '';
        $PayPalResponse = isset($PayPalResult['RAWRESPONSE']) ? $PayPalResult['RAWRESPONSE'] : '';

        $this->log('Refund Request: ' . print_r($this->PayPal->NVPToArray($this->PayPal->MaskAPIResult($PayPalRequest)), true));
        $this->log('Refund Response: ' . print_r($this->PayPal->NVPToArray($this->PayPal->MaskAPIResult($PayPalResponse)), true));

        if ($this->PayPal->APICallSuccessful($PayPalResult['ACK'])) {
            update_post_meta($order_id, 'Refund Transaction ID', $PayPalResult['REFUNDTRANSACTIONID']);
            $order->add_order_note('Refund Transaction ID:' . $PayPalResult['REFUNDTRANSACTIONID']);
            if (ob_get_length()) ob_end_clean();
            return true;
        } else {
            $pc_message = apply_filters('ae_ppddp_refund_error_message', $PayPalResult['L_LONGMESSAGE0'], $PayPalResult['L_ERRORCODE'], $PayPalResult);
            return new WP_Error('ec_refund-error', $pc_message);
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

    /**
     * Get and clean a value from $this->centinel_client because the SDK does a poor job of cleaning.
     * @return string
     */
    public function get_centinel_value($key) {
        $value = $this->centinel_client->getValue($key);
        if (empty($value)) {
            $value = WC()->session->get($key);
        }
        $value = wc_clean($value);
        return $value;
    }

    public function field_name($name) {
        return ' name="' . esc_attr($this->id . '-' . $name) . '" ';
    }

    public function angelleye_paypal_pro_credit_card_form_fields($default_fields, $current_gateway_id) {
        if ($current_gateway_id == $this->id) {
            $fields = array();
            $class = 'form-row form-row-first';
            if (isset($this->available_card_types[WC()->countries->get_base_country()]['Maestro'])) {
                $class = 'form-row form-row-last';
                $fields = array(
                    'card-number-field' => '<p class="form-row form-row-wide">
                        <label for="' . esc_attr($this->id) . '-card-number">' . apply_filters( 'cc_form_label_card_number', __('Card number', 'paypal-for-woocommerce'), $this->id) . ' <span class="required">*</span></label>
                        <input id="' . esc_attr($this->id) . '-card-number" class="input-text wc-credit-card-form-card-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ' . $this->field_name('card-number') . ' />
                    </p>',
                    'card-expiry-field' => $this->paypal_for_woocommerce_paypal_pro_credit_card_form_expiration_date_selectbox($class),
                    '<p class="form-row form-row-last">
			<label for="' . esc_attr($this->id) . '-card-cvc">' . apply_filters( 'cc_form_label_card_code', __('Card Security Code', 'paypal-for-woocommerce'), $this->id) . ' <span class="required">*</span></label>
			<input id="' . esc_attr($this->id) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="' . esc_attr__('CVC', 'paypal-for-woocommerce') . '" ' . $this->field_name('card-cvc') . ' style="width:100px" />
                    </p>',
                    'card-startdate-field' => '<p class="form-row form-row-last">
                        <label for="' . esc_attr($this->id) . '-card-startdate">' . apply_filters( 'cc_form_label_start_expiry', __('Start Date (MM/YY)', 'paypal-for-woocommerce'), $this->id ) . '</label>
                        <input id="' . esc_attr($this->id) . '-card-startdate" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="' . __('MM / YY', 'paypal-for-woocommerce') . '" name="' . $this->id . '-card-startdate' . '" />
                     </p>'
                );

            } else {
                $fields = array(
                    'card-number-field' => '<p class="form-row form-row-wide">
                        <label for="' . esc_attr($this->id) . '-card-number">' . apply_filters( 'cc_form_label_card_number', __('Card number', 'paypal-for-woocommerce'), $this->id) . ' <span class="required">*</span></label>
                        <input id="' . esc_attr($this->id) . '-card-number" class="input-text wc-credit-card-form-card-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ' . $this->field_name('card-number') . ' />
                    </p>',
                    'card-expiry-field' => $this->paypal_for_woocommerce_paypal_pro_credit_card_form_expiration_date_selectbox($class),
                    '<p class="form-row form-row-last">
			<label for="' . esc_attr($this->id) . '-card-cvc">' . apply_filters( 'cc_form_label_card_code', __('Card Security Code', 'paypal-for-woocommerce'), $this->id) . ' <span class="required">*</span></label>
			<input id="' . esc_attr($this->id) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="' . esc_attr__('CVC', 'paypal-for-woocommerce') . '" ' . $this->field_name('card-cvc') . ' style="width:100px" />
                    </p>'
                );

            }
            return $fields;
        } else {
            return $default_fields;
        }
    }

    public function get_transaction_url($order) {
        $sandbox_transaction_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
        $live_transaction_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
        $old_wc = version_compare( WC_VERSION, '3.0', '<' );
        $is_sandbox = $old_wc ? get_post_meta( $order->id, 'is_sandbox', true ) : get_post_meta($order->get_id(), 'is_sandbox', true);
        if ($is_sandbox == true) {
            $this->view_transaction_url = $sandbox_transaction_url;
        } else {
            if (empty($is_sandbox)) {
                if (  $this->testmode == true ) {
                    $this->view_transaction_url = $sandbox_transaction_url;
                } else {
                    $this->view_transaction_url = $live_transaction_url;
                }
            } else {
                $this->view_transaction_url = $live_transaction_url;
            }
        }
        return parent::get_transaction_url($order);
    }

    public function paypal_pro_error_handler($request_name = '', $redirect_url = '', $result) {
        $ErrorCode = urldecode($result["L_ERRORCODE0"]);
        $ErrorShortMsg = urldecode($result["L_SHORTMESSAGE0"]);
        $ErrorLongMsg = urldecode($result["L_LONGMESSAGE0"]);
        $ErrorSeverityCode = urldecode($result["L_SEVERITYCODE0"]);
        $this->log(__($request_name . 'API call failed. ', 'paypal-for-woocommerce'));
        $this->log(__('Detailed Error Message: ', 'paypal-for-woocommerce') . $ErrorLongMsg);
        $this->log(__('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg);
        $this->log(__('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode);
        $this->log(__('Error Severity Code: ', 'paypal-for-woocommerce') . $ErrorSeverityCode);
        $message = '';
        if ($this->error_email_notify) {
            $admin_email = get_option("admin_email");
            $message .= __($request_name . " API call failed.", "paypal-for-woocommerce") . "\n\n";
            $message .= __('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode . "\n";
            $message .= __('Error Severity Code: ', 'paypal-for-woocommerce') . $ErrorSeverityCode . "\n";
            $message .= __('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg . "\n";
            $message .= __('Detailed Error Message: ', 'paypal-for-woocommerce') . $ErrorLongMsg . "\n";
            $message .= __('User IP: ', 'paypal-for-woocommerce') . AngellEYE_Utility::get_user_ip() . "\n";
            $error_email_notify_mes = apply_filters('ae_ppec_error_email_message', $message, $ErrorCode, $ErrorSeverityCode, $ErrorShortMsg, $ErrorLongMsg);
            $subject = "PayPal Pro Error Notification";
            $error_email_notify_subject = apply_filters('ae_ppec_error_email_subject', $subject);
            wp_mail($admin_email, $error_email_notify_subject, $error_email_notify_mes);
        }
        if ($this->error_display_type == 'detailed') {
            $sec_error_notice = $ErrorCode . ' - ' . $ErrorLongMsg;
            $error_display_type_message = sprintf(__($sec_error_notice, 'paypal-for-woocommerce'));
        } else {
            $error_display_type_message = sprintf(__('There was a problem paying with PayPal.  Please try another method.', 'paypal-for-woocommerce'));
        }
        $error_display_type_message = apply_filters('ae_ppec_error_user_display_message', $error_display_type_message, $ErrorCode, $ErrorLongMsg);
        wc_add_notice($error_display_type_message, 'error');
        if (!is_ajax()) {
            wp_redirect($redirect_url);
            exit;
        } else {
            return array(
                'result' => 'fail',
                'redirect' => $redirect_url
            );
        }
    }

    public function add_payment_method($order = null) {
        $this->validate_fields();
        $card = $this->get_posted_card();
        $this->angelleye_load_paypal_pro_class($this->gateway, $this, null);
        $DPFields = array(
            'paymentaction' => 'Authorization',
            'ipaddress' => AngellEYE_Utility::get_user_ip(),
            'returnfmfdetails' => '1',
            'softdescriptor' => $this->softdescriptor
        );
        $CCDetails = array(
            'creditcardtype' => $card->type,
            'acct' => $card->number,
            'expdate' => $card->exp_month . $card->exp_year,
            'cvv2' => $card->cvc
        );
        $PaymentDetails = array(
            'amt' => 0,
            'currencycode' => get_woocommerce_currency(),
        );
        $PayPalRequestData = array(
            'DPFields' => $DPFields,
            'CCDetails' => $CCDetails,
            'PaymentDetails' => $PaymentDetails
        );
        $result = $this->PayPal->DoDirectPayment(apply_filters('angelleye_woocommerce_do_direct_payment_request_args', $PayPalRequestData));
        $this->log('Response: ' . print_r($result, true));
        $redirect_url = wc_get_account_endpoint_url('payment-methods');
        if( isset($result['CURL_ERROR']) ) {
            $this->log($result['CURL_ERROR']);
        }
        AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($result, $methos_name = 'DoDirectPayment', $gateway = 'PayPal Website Payments Pro (DoDirectPayment)', $this->error_email_notify, $redirect_url);
        if ($result['ACK'] == 'Success' || $result['ACK'] == 'SuccessWithWarning') {
            $customer_id = get_current_user_id();
            $TRANSACTIONID = $result['TRANSACTIONID'];
            $token = new WC_Payment_Token_CC();
            $token->set_token( $TRANSACTIONID );
            $token->set_gateway_id( $this->id );
            $token->set_card_type( AngellEYE_Utility::card_type_from_account_number($PayPalRequestData['CCDetails']['acct']));
            $token->set_last4( substr( $PayPalRequestData['CCDetails']['acct'], -4 ) );
            $token->set_expiry_month( substr( $PayPalRequestData['CCDetails']['expdate'], 0,2 ) );
            $token->set_expiry_year( substr( $PayPalRequestData['CCDetails']['expdate'], 2,5 ) );
            $token->set_user_id( $customer_id );
            if( $token->validate() ) {
                $save_result = $token->save();
                return array(
                    'result' => 'success',
                    'redirect' => wc_get_account_endpoint_url('payment-methods')
                );
            } else {
                throw new Exception( __( 'Invalid or missing payment token fields.', 'paypal-for-woocommerce' ) );
            }


        } else {
            $redirect_url = wc_get_account_endpoint_url('payment-methods');
            $this->paypal_pro_error_handler($request_name = 'DoDirectPayment', $redirect_url, $result);
        }
    }

    public function angelleye_paypal_pro_encrypt_gateway_api($settings) {
        if( !empty($settings['sandbox_api_password'])) {
            $api_password = $settings['sandbox_api_password'];
        } else {
            $api_password = $settings['api_password'];
        }
        if(strlen($api_password) > 35 ) {
            return $settings;
        }
        if( !empty($settings['is_encrypt']) ) {
            $gateway_settings_keys = array('sandbox_api_username', 'sandbox_api_password', 'sandbox_api_signature', 'api_username', 'api_password', 'api_signature');
            foreach ($gateway_settings_keys as $gateway_settings_key => $gateway_settings_value) {
                if( !empty( $settings[$gateway_settings_value]) ) {
                    $settings[$gateway_settings_value] = AngellEYE_Utility::crypting($settings[$gateway_settings_value], $action = 'e');
                }
            }
        }
        return $settings;
    }

    public function angelleye_paypal_pro_email_instructions($order, $sent_to_admin, $plain_text = false) {
        $payment_method = version_compare( WC_VERSION, '3.0', '<' ) ? $order->payment_method : $order->get_payment_method();
        if ( $sent_to_admin && 'paypal_pro' === $payment_method ) {
            $old_wc = version_compare( WC_VERSION, '3.0', '<' );
            $order_id = version_compare( WC_VERSION, '3.0', '<' ) ? $order->id : $order->get_id();
            $this->angelleye_load_paypal_pro_class($this->gateway, $this, $order_id);
            $avscode = $old_wc ? get_post_meta( $order->id, '_AVSCODE', true ) : get_post_meta($order->get_id(), '_AVSCODE', true);
            if ( ! empty( $avscode ) ) {
                $avs_response_message = $this->PayPal->GetAVSCodeMessage($avscode);
                echo '<section class="woocommerce-bacs-bank-details"><h3 class="wc-avs-details-heading">' . __( 'Address Verification Details', 'paypal-for-woocommerce' ) . '</h3>' . PHP_EOL;
                echo '<ul class="wc-avs-details order_details avs_details">' . PHP_EOL;
                $avs_details_fields = apply_filters( 'angelleye_avs_details_fields', array(
                        'avs_response_code'=> array(
                                'label' => __( 'AVS Response Code', 'paypal-for-woocommerce' ),
                                'value' => $avscode
                        ),
                        'avs_response_message'          => array(
                                'label' => __( 'AVS Response Message', 'paypal-for-woocommerce' ),
                                'value' => $avs_response_message
                        )
                ), $order_id );
                foreach ( $avs_details_fields as $field_key => $field ) {
                        if ( ! empty( $field['value'] ) ) {
                                echo '<li class="' . esc_attr( $field_key ) . '">' . esc_attr( $field['label'] ) . ': <strong>' . wptexturize( $field['value'] ) . '</strong></li>' . PHP_EOL;
              }
                }
                echo '</ul></section>';
            }
            $old_wc = version_compare( WC_VERSION, '3.0', '<' );
            $cvvmatch = $old_wc ? get_post_meta( $order->id, '_CVV2MATCH', true ) : get_post_meta($order->get_id(), '_CVV2MATCH', true);
            if ( ! empty( $cvvmatch ) ) {
                $cvv2_response_message = $this->PayPal->GetCVV2CodeMessage($cvvmatch);
                echo '<section class="woocommerce-bacs-bank-details"><h3 class="wc-cvv2-details-heading">' . __( 'Card Security Code Details', 'paypal-for-woocommerce' ) . '</h3>' . PHP_EOL;
                echo '<ul class="wc-cvv2-details order_details cvv2_details">' . PHP_EOL;
                $cvv_details_fields = apply_filters( 'angelleye_cvv2_details_fields', array(
                        'cvv2_response_code'=> array(
                                'label' => __( 'CVV2 Response Code', 'paypal-for-woocommerce' ),
                                'value' => $cvvmatch
                        ),
                        'cvv2_response_message'          => array(
                                'label' => __( 'CVV2 Response Message', 'paypal-for-woocommerce' ),
                                'value' => $cvv2_response_message
                        )
                ), $order_id );
                foreach ( $cvv_details_fields as $field_key => $field ) {
                        if ( ! empty( $field['value'] ) ) {
                                echo '<li class="' . esc_attr( $field_key ) . '">' . esc_attr( $field['label'] ) . ': <strong>' . wptexturize( $field['value'] ) . '</strong></li>' . PHP_EOL;
                        }
                }
                echo '</ul></section>';
            }
        }
    }

    public function process_subscription_payment($order) {
       $this->angelleye_reload_gateway_credentials_for_woo_subscription_renewal_order($order);
       $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
       $this->angelleye_load_paypal_pro_class($this->gateway, $this, $order_id);
       $card = $this->get_posted_card();
        if (!class_exists('WC_Gateway_Calculation_AngellEYE')) {
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/wc-gateway-calculations-angelleye.php' );
        }
        $this->calculation_angelleye = new WC_Gateway_Calculation_AngellEYE(null, $this->subtotal_mismatch_behavior);
        $DPFields = array(
            'paymentaction' => !empty($this->payment_action) ? $this->payment_action : 'Sale', // How you want to obtain payment.  Authorization indidicates the payment is a basic auth subject to settlement with Auth & Capture.  Sale indicates that this is a final sale for which you are requesting payment.  Default is Sale.
            'ipaddress' => AngellEYE_Utility::get_user_ip(), // Required.  IP address of the payer's browser.
            'returnfmfdetails' => '1',                   // Flag to determine whether you want the results returned by FMF.  1 or 0.  Default is 0.
            'softdescriptor' => $this->softdescriptor
        );
        $billing_first_name = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_first_name : $order->get_billing_first_name();
        $billing_last_name = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_last_name : $order->get_billing_last_name();
        $billing_address_1 = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_address_1 : $order->get_billing_address_1();
        $billing_address_2 = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_address_2 : $order->get_billing_address_2();
        $billing_city = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_city : $order->get_billing_city();
        $billing_postcode = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_postcode : $order->get_billing_postcode();
        $billing_country = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_country : $order->get_billing_country();
        $billing_state = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_state : $order->get_billing_state();
        $billing_email = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_email : $order->get_billing_email();
        $billing_phone = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_phone : $order->get_billing_phone();

        $PayerInfo = array(
            'email' => $billing_email, // Email address of payer.
            'firstname' => $billing_first_name, // Required.  Payer's first name.
            'lastname' => $billing_last_name                            // Required.  Payer's last name.
        );
        $BillingAddress = array(
            'street' => $billing_address_1, // Required.  First street address.
            'street2' => $billing_address_2, // Second street address.
            'city' => $billing_city, // Required.  Name of City.
            'state' => $billing_state, // Required. Name of State or Province.
            'countrycode' => $billing_country, // Required.  Country code.
            'zip' => $billing_postcode, // Required.  Postal code of payer.
            'phonenum' => $billing_phone                        // Phone Number of payer.  20 char max.
        );

        $shipping_first_name = version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_first_name : $order->get_shipping_first_name();
        $shipping_last_name = version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_last_name : $order->get_shipping_last_name();

        $ShippingAddress = array(
            'shiptoname' => $shipping_first_name . ' ' . $shipping_last_name,                    // Required if shipping is included.  Person's name associated with this address.  32 char max.
            'shiptostreet' => version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_address_1 : $order->get_shipping_address_1(),                    // Required if shipping is included.  First street address.  100 char max.
            'shiptostreet2' => version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_address_2 : $order->get_shipping_address_2(),                    // Second street address.  100 char max.
            'shiptocity' => version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_city : $order->get_shipping_city(),                    // Required if shipping is included.  Name of city.  40 char max.
            'shiptostate' => version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_state : $order->get_shipping_state(),                    // Required if shipping is included.  Name of state or province.  40 char max.
            'shiptozip' => version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_postcode : $order->get_shipping_postcode(),                        // Required if shipping is included.  Postal code of shipping address.  20 char max.
            'shiptocountry' => version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_country : $order->get_shipping_country(),                    // Required if shipping is included.  Country code of shipping address.  2 char max.
            'shiptophonenum' => version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_phone : $order->get_billing_phone()                    // Phone number for shipping address.  20 char max.
        );

        $customer_note_value = version_compare(WC_VERSION, '3.0', '<') ? wptexturize($order->customer_note) : wptexturize($order->get_customer_note());
        $customer_note = $customer_note_value ? substr(preg_replace("/[^A-Za-z0-9 ]/", "", $customer_note_value), 0, 256) : '';
        $PaymentDetails = array(
            'amt' => AngellEYE_Gateway_Paypal::number_format($order->get_total()), // Required.  Total amount of order, including shipping, handling, and tax.
            'currencycode' => get_woocommerce_currency(), // Required.  Three-letter currency code.  Default is USD.
            'insuranceamt' => '', // Total shipping insurance costs for this order.
            'shipdiscamt' => '0.00', // Shipping discount for the order, specified as a negative number.
            'handlingamt' => '0.00', // Total handling costs for the order.  If you specify handlingamt, you must also specify itemamt.
            'desc' => '', // Description of the order the customer is purchasing.  127 char max.
            'custom' => apply_filters('ae_ppddp_custom_parameter', $customer_note, $order), // Free-form field for your own use.  256 char max.
            'invnum' => $this->invoice_id_prefix . preg_replace("/[^a-zA-Z0-9]/", "", $order->get_order_number()), // Your own invoice or tracking number
            'recurring' => ''                        // Flag to indicate a recurring transaction.  Value should be Y for recurring, or anything other than Y if it's not recurring.  To pass Y here, you must have an established billing agreement with the buyer.
        );
        if (isset($this->notifyurl) && !empty($this->notifyurl)) {
            $PaymentDetails['notifyurl'] = $this->notifyurl;
        }
        $PaymentData = $this->calculation_angelleye->order_calculation($order_id);
        $OrderItems = array();
        if( $PaymentData['is_calculation_mismatch'] == false ) {
            foreach ($PaymentData['order_items'] as $item) {
                $Item = array(
                    'l_name' => $item['name'], // Item Name.  127 char max.
                    'l_desc' => '', // Item description.  127 char max.
                    'l_amt' => $item['amt'], // Cost of individual item.
                    'l_number' => $item['number'], // Item Number.  127 char max.
                    'l_qty' => $item['qty'], // Item quantity.  Must be any positive integer.
                    'l_taxamt' => '', // Item's sales tax amount.
                    'l_ebayitemnumber' => '', // eBay auction number of item.
                    'l_ebayitemauctiontxnid' => '', // eBay transaction ID of purchased item.
                    'l_ebayitemorderid' => ''                // eBay order ID for the item.
                );
                array_push($OrderItems, $Item);
            }
        } else {
            $PaymentDetails['taxamt'] = $PaymentData['taxamt'];
            $PaymentDetails['shippingamt'] = $PaymentData['shippingamt'];
            $PaymentDetails['itemamt'] = AngellEYE_Gateway_Paypal::number_format($PaymentData['itemamt']);
        }
        $PayPalRequestData = array(
            'DPFields' => $DPFields,
            'PayerInfo' => $PayerInfo,
            'BillingAddress' => $BillingAddress,
            'ShippingAddress' => $ShippingAddress,
            'PaymentDetails' => $PaymentDetails,
            'OrderItems' => $OrderItems
        );
        $PayPalRequestData['DRTFields'] = array(
            'referenceid' => get_post_meta($order_id, '_payment_tokens_id', true),
            'paymentaction' => !empty($this->payment_action) ? $this->payment_action : 'Sale',
            'returnfmfdetails' => '1',
            'softdescriptor' => $this->softdescriptor
        );
        $PayPalResult = $this->PayPal->DoReferenceTransaction($PayPalRequestData);
        AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($PayPalResult, $methos_name = 'DoReferenceTransaction', $gateway = 'PayPal Website Payments Pro (DoDirectPayment)', $this->error_email_notify);
        $PayPalRequest = isset($PayPalResult['RAWREQUEST']) ? $PayPalResult['RAWREQUEST'] : '';
        $PayPalResponse = isset($PayPalResult['RAWRESPONSE']) ? $PayPalResult['RAWRESPONSE'] : '';
        $this->log('Request: ' . print_r($this->PayPal->NVPToArray($this->PayPal->MaskAPIResult($PayPalRequest)), true));
        $this->log('Response: ' . print_r($this->PayPal->NVPToArray($this->PayPal->MaskAPIResult($PayPalResponse)), true));
        if (empty($PayPalResult['RAWRESPONSE'])) {
            $pc_empty_response = apply_filters('ae_ppddp_paypal_response_empty_message', __('Empty PayPal response.', 'paypal-for-woocommerce'), $PayPalResult);
            throw new Exception($pc_empty_response);
        }
        if ($this->PayPal->APICallSuccessful($PayPalResult['ACK'])) {
            $order->add_order_note(sprintf(__('PayPal Pro payment completed (Transaction ID: %s, Correlation ID: %s)', 'paypal-for-woocommerce'), $PayPalResult['TRANSACTIONID'], $PayPalResult['CORRELATIONID']));
            $avs_response_code = isset($PayPalResult['AVSCODE']) ? $PayPalResult['AVSCODE'] : '';
            $avs_response_message = $this->PayPal->GetAVSCodeMessage($avs_response_code);
            $avs_response_order_note = __('Address Verification Result', 'paypal-for-woocommerce');
            $avs_response_order_note .= "\n";
            $avs_response_order_note .= $avs_response_code;
            $avs_response_order_note .= $avs_response_message != '' ? ' - ' . $avs_response_message : '';
            $order->add_order_note($avs_response_order_note);
            $cvv2_response_code = isset($PayPalResult['CVV2MATCH']) ? $PayPalResult['CVV2MATCH'] : '';
            $cvv2_response_message = $this->PayPal->GetCVV2CodeMessage($cvv2_response_code);
            $cvv2_response_order_note = __('Card Security Code Result', 'paypal-for-woocommerce');
            $cvv2_response_order_note .= "\n";
            $cvv2_response_order_note .= $cvv2_response_code;
            $cvv2_response_order_note .= $cvv2_response_message != '' ? ' - ' . $cvv2_response_message : '';
            $order->add_order_note($cvv2_response_order_note);
            do_action('ae_add_custom_order_note', $order, $card, $token = null, $PayPalResult);
            $is_sandbox = $this->testmode;
            update_post_meta($order_id, 'is_sandbox', $is_sandbox);
            if ($this->payment_action == "Sale") {
                $this->save_payment_token($order, $PayPalResult['TRANSACTIONID']);
                $order->payment_complete($PayPalResult['TRANSACTIONID']);
            } else {
                $this->save_payment_token($order, $PayPalResult['TRANSACTIONID']);
                update_post_meta($order_id, '_first_transaction_id', $PayPalResult['TRANSACTIONID']);
                $payment_order_meta = array('_transaction_id' => $PayPalResult['TRANSACTIONID'], '_payment_action' => $this->payment_action);
                AngellEYE_Utility::angelleye_add_order_meta($order_id, $payment_order_meta);
                AngellEYE_Utility::angelleye_paypal_for_woocommerce_add_paypal_transaction($PayPalResult, $order, $this->payment_action);
                $angelleye_utility = new AngellEYE_Utility(null, null);
                $angelleye_utility->angelleye_get_transactionDetails($PayPalResult['TRANSACTIONID']);
                $order->update_status('on-hold');
                $old_wc = version_compare(WC_VERSION, '3.0', '<');
                if ( $old_wc ) {
                    if ( ! get_post_meta( $order_id, '_order_stock_reduced', true ) ) {
                        $order->reduce_order_stock();
                    }
                } else {
                    wc_maybe_reduce_stock_levels( $order_id );
                }
                $order->add_order_note('Payment Action: ' . $this->payment_action);
            }
            return true;
        } else {
            $error_code = isset($PayPalResult['ERRORS'][0]['L_ERRORCODE']) ? $PayPalResult['ERRORS'][0]['L_ERRORCODE'] : '';
            $long_message = isset($PayPalResult['ERRORS'][0]['L_LONGMESSAGE']) ? $PayPalResult['ERRORS'][0]['L_LONGMESSAGE'] : '';
            $error_message = $error_code . '-' . $long_message;
            if ($this->error_email_notify) {
                $admin_email = get_option("admin_email");
                $message = __("DoDirectPayment API call failed.", "paypal-for-woocommerce") . "\n\n";
                $message .= __('Error Code: ', 'paypal-for-woocommerce') . $error_code . "\n";
                $message .= __('Detailed Error Message: ', 'paypal-for-woocommerce') . $long_message . "\n";
                $message .= __('User IP: ', 'paypal-for-woocommerce') . AngellEYE_Utility::get_user_ip() . "\n";
                $message .= __('Order ID: ') . $order_id . "\n";
                $message .= __('Customer Name: ') . $billing_first_name . ' ' . $billing_last_name . "\n";
                $message .= __('Customer Email: ') . $billing_email . "\n";
                $pc_error_email_message = apply_filters('ae_ppddp_error_email_message', $message, $error_code, $long_message);
                $pc_error_email_subject = apply_filters('ae_ppddp_error_email_subject', "PayPal Pro Error Notification", $error_code, $long_message);
                wp_mail($admin_email, $pc_error_email_subject, $pc_error_email_message);
            }
            $this->log('Error ' . print_r($PayPalResult['ERRORS'], true));
            $order->update_status('failed', sprintf(__('PayPal Pro payment failed (Correlation ID: %s). Payment was rejected due to an error: %s', 'paypal-for-woocommerce'), $PayPalResult['CORRELATIONID'], '(' . $PayPalResult['L_ERRORCODE0'] . ') ' . '"' . $error_message . '"'));
            if ($this->error_display_type == 'detailed') {
                $pc_display_type_error = __($error_message, 'paypal-for-woocommerce');
                $pc_display_type_notice = __('Payment error:', 'paypal-for-woocommerce') . ' ' . $error_message;
            } else {
                $pc_display_type_error = __('There was a problem connecting to the payment gateway.', 'paypal-for-woocommerce');
                $pc_display_type_notice = __('Payment error:', 'paypal-for-woocommerce') . ' ' . $error_message;
            }
        }
    }


    public function save_payment_token($order, $payment_tokens_id) {
        // Store source in the order
        $order_id = version_compare( WC_VERSION, '3.0', '<' ) ? $order->id : $order->get_id();
        if (!empty($payment_tokens_id)) {
            update_post_meta($order_id, '_payment_tokens_id', $payment_tokens_id);
        }
    }

    public function free_signup_order_payment($order_id) {
        $order = new WC_Order($order_id);
        $this->log('Processing order #' . $order_id);
        if ( (!empty($_POST['wc-paypal_pro-payment-token']) && $_POST['wc-paypal_pro-payment-token'] != 'new') || $this->is_subscription($order_id)) {
            $token_id = wc_clean($_POST['wc-paypal_pro-payment-token']);
            $token = WC_Payment_Tokens::get($token_id);
            $order->payment_complete($token->get_token());
            $this->save_payment_token($order, $token->get_token());
            update_post_meta($order_id, '_first_transaction_id', $token->get_token());
            $order->add_order_note('Payment Action: ' . $this->payment_action);
            WC()->cart->empty_cart();
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        }
    }

    public function is_subscription($order_id) {
        return ( function_exists('wcs_order_contains_subscription') && ( wcs_order_contains_subscription($order_id) || wcs_is_subscription($order_id) || wcs_order_contains_renewal($order_id) ) );
    }

    public function angelleye_update_status($order, $transaction_id ) {
        if( $this->payment_action == 'Sale') {
            $order->payment_complete($transaction_id);
        } else {
            if ( $this->payment_action  == 'Authorization') {
                $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
                $this->angelleye_get_transaction_details($order_id, $transaction_id);
            } else {
                $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
                $old_wc = version_compare(WC_VERSION, '3.0', '<');
                if ( $old_wc ) {
                    if ( ! get_post_meta( $order_id, '_order_stock_reduced', true ) ) {
                        $order->reduce_order_stock();
                    }
                } else {
                    wc_maybe_reduce_stock_levels( $order_id );
                }
                $order->update_status('on-hold');
            }
        }
    }

    public function angelleye_reload_gateway_credentials_for_woo_subscription_renewal_order($order) {
        if( $this->testmode == false ) {
            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            if( $this->is_subscription($order_id) ) {
                foreach ($order->get_items() as $cart_item_key => $values) {
                    $product = $order->get_product_from_item($values);
                    $product_id = $product->get_id();
                    if( !empty($product_id) ) {
                        $_enable_sandbox_mode = get_post_meta($product_id, '_enable_sandbox_mode', true);
                        if ($_enable_sandbox_mode == 'yes') {
                            $this->testmode = true;
                            $this->api_username = $this->get_option('sandbox_api_username');
                            $this->api_password = $this->get_option('sandbox_api_password');
                            $this->api_signature = $this->get_option('sandbox_api_signature');
                        }
                    }
                }
            }
        }
    }

    public function angelleye_get_transaction_details($order_id, $transaction_id) {
        $this->angelleye_load_paypal_pro_class($this->gateway, $this, $order_id);
        $GTDFields = array(
            'transactionid' => $transaction_id
        );
        $PayPalRequestData = array('GTDFields' => $GTDFields);
        $get_transactionDetails_result = $this->PayPal->GetTransactionDetails($PayPalRequestData);
        $this->log(print_r($get_transactionDetails_result, true));
        $this->update_payment_status_by_paypal_responce($order_id, $get_transactionDetails_result, $transaction_id);
    }

    public function update_payment_status_by_paypal_responce($orderid, $result, $transaction_id) {
        try {
            $order = wc_get_order($orderid);
            $old_wc = version_compare( WC_VERSION, '3.0', '<' );
            if(!empty($result['PAYMENTINFO_0_PAYMENTSTATUS'])) {
                $payment_status = $result['PAYMENTINFO_0_PAYMENTSTATUS'];
            } elseif ( !empty ($result['PAYMENTSTATUS'])) {
                $payment_status = $result['PAYMENTSTATUS'];
            }
            if( !empty($result['PAYMENTINFO_0_TRANSACTIONTYPE']) ) {
                $transaction_type = $result['PAYMENTINFO_0_TRANSACTIONTYPE'];
            } elseif ( !empty ($result['TRANSACTIONTYPE'])) {
                $transaction_type = $result['TRANSACTIONTYPE'];
            }
            if( !empty($result['PAYMENTINFO_0_TRANSACTIONID']) ) {
                $transaction_id = $result['PAYMENTINFO_0_TRANSACTIONID'];
            } elseif ( !empty ($result['BILLINGAGREEMENTID'])) {
                $transaction_id = $result['BILLINGAGREEMENTID'];
            }
            if( !empty($result['PAYMENTINFO_0_PENDINGREASON']) ) {
                $pending_reason = $result['PAYMENTINFO_0_PENDINGREASON'];
            } elseif ( !empty ($result['PENDINGREASON'])) {
                $pending_reason = $result['PENDINGREASON'];
            }
            switch (strtolower($payment_status)) :
                case 'completed' :
                    $order_status = version_compare(WC_VERSION, '3.0', '<') ? $order->status : $this->order->get_status();
                    if ($order_status == 'completed') {
                        break;
                    }
                    if (!in_array(strtolower($transaction_type), array('merchtpmt', 'cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money'))) {
                        break;
                    }
                    $order->add_order_note(__('Payment Completed via PayPal Pro', 'paypal-for-woocommerce'));
                    $order->payment_complete($transaction_id);
                    break;
                case 'pending' :
                    if (!in_array(strtolower($transaction_type), array('merchtpmt', 'cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money', 'expresscheckout'))) {
                        break;
                    }
                    switch (strtolower($pending_reason)) {
                        case 'address':
                            $pending_reason_text = __('Address: The payment is pending because your customer did not include a confirmed shipping address and your Payment Receiving Preferences is set such that you want to manually accept or deny each of these payments. To change your preference, go to the Preferences section of your Profile.', 'paypal-for-woocommerce');
                            break;
                        case 'authorization':
                            $pending_reason_text = __('Authorization: The payment is pending because it has been authorized but not settled. You must capture the funds first.', 'paypal-for-woocommerce');
                            break;
                        case 'echeck':
                            $pending_reason_text = __('eCheck: The payment is pending because it was made by an eCheck that has not yet cleared.', 'paypal-for-woocommerce');
                            break;
                        case 'intl':
                            $pending_reason_text = __('intl: The payment is pending because you hold a non-U.S. account and do not have a withdrawal mechanism. You must manually accept or deny this payment from your Account Overview.', 'paypal-for-woocommerce');
                            break;
                        case 'multicurrency':
                        case 'multi-currency':
                            $pending_reason_text = __('Multi-currency: You do not have a balance in the currency sent, and you do not have your Payment Receiving Preferences set to automatically convert and accept this payment. You must manually accept or deny this payment.', 'paypal-for-woocommerce');
                            break;
                        case 'order':
                            $pending_reason_text = __('Order: The payment is pending because it is part of an order that has been authorized but not settled.', 'paypal-for-woocommerce');
                            break;
                        case 'paymentreview':
                            $pending_reason_text = __('Payment Review: The payment is pending while it is being reviewed by PayPal for risk.', 'paypal-for-woocommerce');
                            break;
                        case 'unilateral':
                            $pending_reason_text = __('Unilateral: The payment is pending because it was made to an email address that is not yet registered or confirmed.', 'paypal-for-woocommerce');
                            break;
                        case 'verify':
                            $pending_reason_text = __('Verify: The payment is pending because you are not yet verified. You must verify your account before you can accept this payment.', 'paypal-for-woocommerce');
                            break;
                        case 'other':
                            $pending_reason_text = __('Other: For more information, contact PayPal customer service.', 'paypal-for-woocommerce');
                            break;
                        case 'none':
                        default:
                            $pending_reason_text = __('No pending reason provided.', 'paypal-for-woocommerce');
                            break;
                    }
                    $order->add_order_note(sprintf(__('Payment via PayPal Pro Pending. PayPal reason: %s', 'paypal-for-woocommerce'), $pending_reason_text));
                    if ( strtolower($pending_reason) == 'authorization' && $this->pending_authorization_order_status == 'Processing' ) {
                        $order->payment_complete($transaction_id);
                    } else {
                        $order->update_status('on-hold');
                        if ( $old_wc ) {
                            if ( ! get_post_meta( $orderid, '_order_stock_reduced', true ) ) {
                                $order->reduce_order_stock();
                            }
                        } else {
                            wc_maybe_reduce_stock_levels( $orderid );
                        }
                    }
                    break;
                case 'denied' :
                case 'expired' :
                case 'failed' :
                case 'voided' :
                    $order->update_status('failed', sprintf(__('Payment %s via PayPal Pro.', 'paypal-for-woocommerce'), strtolower($payment_status)));
                    break;
                default:
                    break;
            endswitch;
            return;
        } catch (Exception $ex) {

        }
    }

    public function angelleye_load_paypal_pro_class($gateway, $current, $order_id = null) {
        do_action('angelleye_paypal_for_woocommerce_multi_account_api_paypal_pro', $gateway, $current, $order_id);
        if (!class_exists('Angelleye_PayPal_WC')) {
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.class.php' );
        }
        $PayPalConfig = array(
            'Sandbox' => $this->testmode,
            'APIUsername' => $this->api_username,
            'APIPassword' => $this->api_password,
            'APISignature' => $this->api_signature,
            'Force_tls_one_point_two' => $this->Force_tls_one_point_two
        );
        $this->PayPal = new Angelleye_PayPal_WC($PayPalConfig);
    }
    
    public function init_settings() {
        parent::init_settings();
        $this->enabled  = ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'] ? 'yes' : 'no';
        $this->send_items_value = ! empty( $this->settings['send_items'] ) && 'yes' === $this->settings['send_items'] ? 'yes' : 'no';
        $this->send_items = 'yes' === $this->send_items_value;
    }
    
    public function subscription_change_payment($order_id) {
        $order = wc_get_order($order_id);
        if ( (!empty($_POST['wc-paypal_pro-payment-token']) && $_POST['wc-paypal_pro-payment-token'] != 'new')) {
            $token_id = wc_clean($_POST['wc-paypal_pro-payment-token']);
            $token = WC_Payment_Tokens::get($token_id);
            $this->save_payment_token($order, $token->get_token());
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        } else {
            $this->validate_fields();
            $card = $this->get_posted_card();
            $this->angelleye_load_paypal_pro_class($this->gateway, $this, null);
            $DPFields = array(
                'paymentaction' => 'Authorization',
                'ipaddress' => AngellEYE_Utility::get_user_ip(),
                'returnfmfdetails' => '1',
                'softdescriptor' => $this->softdescriptor
            );
            $CCDetails = array(
                'creditcardtype' => $card->type,
                'acct' => $card->number,
                'expdate' => $card->exp_month . $card->exp_year,
                'cvv2' => $card->cvc
            );
            $PaymentDetails = array(
                'amt' => 0,
                'currencycode' => get_woocommerce_currency(),
            );
            $PayPalRequestData = array(
                'DPFields' => $DPFields,
                'CCDetails' => $CCDetails,
                'PaymentDetails' => $PaymentDetails
            );
            $result = $this->PayPal->DoDirectPayment(apply_filters('angelleye_woocommerce_do_direct_payment_request_args', $PayPalRequestData));
            $this->log('Response: ' . print_r($result, true));
            $redirect_url = wc_get_account_endpoint_url('payment-methods');
            if( isset($result['CURL_ERROR']) ) {
                $this->log($result['CURL_ERROR']);
            }
            AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($result, $methos_name = 'DoDirectPayment', $gateway = 'PayPal Website Payments Pro (DoDirectPayment)', $this->error_email_notify, $redirect_url);
            if ($result['ACK'] == 'Success' || $result['ACK'] == 'SuccessWithWarning') {
                $customer_id = get_current_user_id();
                $TRANSACTIONID = $result['TRANSACTIONID'];
                $token = new WC_Payment_Token_CC();
                $token->set_token( $TRANSACTIONID );
                $this->save_payment_token($order, $TRANSACTIONID);
                $token->set_gateway_id( $this->id );
                $token->set_card_type( AngellEYE_Utility::card_type_from_account_number($PayPalRequestData['CCDetails']['acct']));
                $token->set_last4( substr( $PayPalRequestData['CCDetails']['acct'], -4 ) );
                $token->set_expiry_month( substr( $PayPalRequestData['CCDetails']['expdate'], 0,2 ) );
                $token->set_expiry_year( substr( $PayPalRequestData['CCDetails']['expdate'], 2,5 ) );
                $token->set_user_id( $customer_id );
                if( $token->validate() ) {
                    $save_result = $token->save();
                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url($order)
                    );
                } else {
                    throw new Exception( __( 'Invalid or missing payment token fields.', 'paypal-for-woocommerce' ) );
                }
            } else {
                $redirect_url = wc_get_account_endpoint_url('payment-methods');
                $this->paypal_pro_error_handler($request_name = 'DoDirectPayment', $redirect_url, $result);
            }
        }
    }
}
