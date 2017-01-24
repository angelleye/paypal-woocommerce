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
	function __construct() {
            $this->id = 'paypal_pro_payflow';
            $this->method_title	= __( 'PayPal Payments Pro 2.0 (PayFlow)', 'paypal-for-woocommerce' );
            $this->method_description = __( 'PayPal Payments Pro allows you to accept credit cards directly on your site without any redirection through PayPal.  You host the checkout form on your own web server, so you will need an SSL certificate to ensure your customer data is protected.', 'paypal-for-woocommerce' );
            $this->has_fields = true;
            $this->liveurl = 'https://payflowpro.paypal.com';
            $this->testurl = 'https://pilot-payflowpro.paypal.com';
            $this->allowed_currencies   = apply_filters( 'woocommerce_paypal_pro_payflow_supported_currencies', array( 'USD', 'EUR', 'GBP', 'CAD', 'JPY', 'AUD', 'NZD' ) );

            // Load the form fields
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();

            // Get setting values
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');
            $this->paypal_vendor = $this->get_option('paypal_vendor');
            $this->paypal_partner = $this->get_option('paypal_partner', 'PayPal');
            $this->paypal_password = $this->settings['paypal_password'];
            $this->paypal_user = $this->get_option('paypal_user', $this->paypal_vendor); 
            $this->testmode = $this->get_option('testmode', 'no');
            $this->invoice_id_prefix = $this->get_option('invoice_id_prefix', '');
            $this->debug = 'yes' === $this->get_option('debug', 'no'); 
            $this->error_email_notify = 'yes' === $this->get_option('error_email_notify', 'no');
            $this->error_display_type = $this->get_option('error_display_type', 'no');
            $this->send_items = 'yes' === $this->get_option('send_items', 'yes');
            $this->payment_action = $this->get_option('payment_action', 'Sale'); 

            //fix ssl for image icon
            $this->icon = $this->get_option('card_icon', plugins_url('/assets/images/payflow-cards.png', plugin_basename(dirname(__FILE__))));
            if (is_ssl()) {
                $this->icon = preg_replace("/^http:/i", "https:", $this->icon);
            }
            $this->icon = apply_filters('woocommerce_paypal_pro_payflow_icon', $this->icon);
            if ($this->testmode=="yes") {
                $this->paypal_vendor = $this->get_option('sandbox_paypal_vendor');
                $this->paypal_partner = $this->get_option('sandbox_paypal_partner', 'PayPal');
                $this->paypal_password = $this->get_option('sandbox_paypal_password');
                $this->paypal_user = $this->get_option('sandbox_paypal_user', $this->paypal_vendor);
            }

            $this->supports = array(
                'products',
                'refunds'
            );

            $this->enable_tokenized_payments = $this->get_option('enable_tokenized_payments', 'no');
            if($this->enable_tokenized_payments == 'yes') {
                array_push($this->supports, "tokenization");
            }
            $this->Force_tls_one_point_two = get_option('Force_tls_one_point_two', 'no');
            $this->enable_cardholder_first_last_name = 'yes' === $this->get_option('enable_cardholder_first_last_name', 'no'); 
            $this->is_encrypt = $this->get_option('is_encrypt', 'no');
            /* 1.6.6 */
            add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );

            /* 2.0.0 */
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

            add_filter( 'woocommerce_credit_card_form_fields', array($this, 'angelleye_paypal_pro_payflow_credit_card_form_fields'), 10, 2);
            add_filter('woocommerce_settings_api_sanitized_fields_' . $this->id, array($this, 'angelleye_paypal_pro_payflow_encrypt_gateway_api'), 10, 1);
            if ($this->enable_cardholder_first_last_name) {
                add_action('woocommerce_credit_card_form_start', array($this, 'angelleye_woocommerce_credit_card_form_start'), 10, 1);
            }
            
            $this->customer_id;
	}
    
    
    public function add_log($message) {
        if ($this->debug) {
            if (!isset($this->log)) {
                $this->log = new WC_Logger();
            }
            $this->log->add('paypal_payflow', $message);
        }
    }
	
	/**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields() {

    	$this->form_fields = array(
			'enabled'         => array(
							'title'       => __( 'Enable/Disable', 'paypal-for-woocommerce' ),
							'label'       => __( 'Enable PayPal Pro Payflow Edition', 'paypal-for-woocommerce' ),
							'type'        => 'checkbox',
							'description' => '',
							'default'     => 'no'
						),
			'title'           => array(
							'title'       => __( 'Title', 'paypal-for-woocommerce' ),
							'type'        => 'text',
							'description' => __( 'This controls the title which the user sees during checkout.', 'paypal-for-woocommerce' ),
							'default'     => __( 'Credit card', 'paypal-for-woocommerce' )
						),
			'description'     => array(
							'title'       => __( 'Description', 'paypal-for-woocommerce' ),
							'type'        => 'textarea',
							'description' => __( 'This controls the description which the user sees during checkout.', 'paypal-for-woocommerce' ),
							'default'     => __( 'Pay with your credit card.', 'paypal-for-woocommerce' )
						),
			'testmode'        => array(
							'title'       => __( 'Test Mode', 'paypal-for-woocommerce' ),
							'label'       => __( 'Enable PayPal Sandbox/Test Mode', 'paypal-for-woocommerce' ),
							'type'        => 'checkbox',
							'description' => __( 'Place the payment gateway in development mode.', 'paypal-for-woocommerce' ),
							'default'     => 'no'
						),
            'invoice_id_prefix'           => array(
                'title'       => __( 'Invoice ID Prefix', 'paypal-for-woocommerce' ),
                'type'        => 'text',
                'description' => __( 'Add a prefix to the invoice ID sent to PayPal. This can resolve duplicate invoice problems when working with multiple websites on the same PayPal account.', 'paypal-for-woocommerce' ),
            ),
            'card_icon'        => array(
                'title'       => __( 'Card Icon', 'paypal-for-woocommerce' ),
                'type'        => 'text',
                'default'     => plugins_url('/assets/images/payflow-cards.png', plugin_basename(dirname(__FILE__)))
            ),
			'debug' => array(
                'title' => __( 'Debug Log', 'paypal-for-woocommerce' ),
                'type' => 'checkbox',
                'label' => __( 'Enable logging', 'paypal-for-woocommerce' ),
                'default' => 'no',
                'description' => sprintf( __( 'Log PayPal events inside <code>%s</code>', 'paypal-for-woocommerce' ), wc_get_log_file_path( 'paypal_payflow' ) ),
            ),
			'error_email_notify' => array(
                'title' => __( 'Error Email Notifications', 'paypal-for-woocommerce' ),
                'type' => 'checkbox',
                'label' => __( 'Enable admin email notifications for errors.', 'paypal-for-woocommerce' ),
                'default' => 'yes', 
				'description' => __( 'This will send a detailed error email to the WordPress site administrator if a PayPal API error occurs.','paypal-for-woocommerce' )
            ),
			'error_display_type' => array(
                'title' => __( 'Error Display Type', 'paypal-for-woocommerce' ),
                'type' => 'select',
                'label' => __( 'Display detailed or generic errors', 'paypal-for-woocommerce' ),
                'class' => 'error_display_type_option',
                'options' => array(
                    'detailed' => 'Detailed',
                    'generic' => 'Generic'
                ),
				'description' => __( 'Detailed displays actual errors returned from PayPal.  Generic displays general errors that do not reveal details 
									and helps to prevent fraudulant activity on your site.' , 'paypal-for-woocommerce' )
            ),
            'sandbox_paypal_vendor'   => array(
                'title'       => __( 'Sandbox PayPal Vendor', 'paypal-for-woocommerce' ),
                'type'        => 'text',
                'description' => __( 'Your merchant login ID that you created when you registered for the account.', 'paypal-for-woocommerce' ),
                'default'     => ''
            ),
            'sandbox_paypal_password' => array(
                'title'       => __( 'Sandbox PayPal Password', 'paypal-for-woocommerce' ),
                'type'        => 'password',
                'description' => __( 'The password that you defined while registering for the account.', 'paypal-for-woocommerce' ),
                'default'     => ''
            ),
            'sandbox_paypal_user'     => array(
                'title'       => __( 'Sandbox PayPal User', 'paypal-for-woocommerce' ),
                'type'        => 'text',
                'description' => __( 'If you set up one or more additional users on the account, this value is the ID
of the user authorized to process transactions. Otherwise, leave this field blank.', 'paypal-for-woocommerce' ),
                'default'     => ''
            ),
            'sandbox_paypal_partner'  => array(
                'title'       => __( 'Sandbox PayPal Partner', 'paypal-for-woocommerce' ),
                'type'        => 'text',
                'description' => __( 'The ID provided to you by the authorized PayPal Reseller who registered you
for the Payflow SDK. If you purchased your account directly from PayPal, use PayPal or leave blank.', 'paypal-for-woocommerce' ),
                'default'     => 'PayPal'
            ),
			'paypal_vendor'   => array(
							'title'       => __( 'Live PayPal Vendor', 'paypal-for-woocommerce' ),
							'type'        => 'text',
							'description' => __( 'Your merchant login ID that you created when you registered for the account.', 'paypal-for-woocommerce' ),
							'default'     => ''
						),
			'paypal_password' => array(
							'title'       => __( 'Live PayPal Password', 'paypal-for-woocommerce' ),
							'type'        => 'password',
							'description' => __( 'The password that you defined while registering for the account.', 'paypal-for-woocommerce' ),
							'default'     => ''
						),
			'paypal_user'     => array(
							'title'       => __( 'Live PayPal User', 'paypal-for-woocommerce' ),
							'type'        => 'text',
							'description' => __( 'If you set up one or more additional users on the account, this value is the ID
of the user authorized to process transactions. Otherwise, leave this field blank.', 'paypal-for-woocommerce' ),
							'default'     => ''
						),
			'paypal_partner'  => array(
							'title'       => __( 'Live PayPal Partner', 'paypal-for-woocommerce' ),
							'type'        => 'text',
							'description' => __( 'The ID provided to you by the authorized PayPal Reseller who registered you
for the Payflow SDK. If you purchased your account directly from PayPal, use PayPal or leave blank.', 'paypal-for-woocommerce' ),
							'default'     => 'PayPal'
						),
            'send_items' => array(
                'title' => __( 'Send Item Details', 'paypal-for-woocommerce' ),
                'label' => __( 'Send line item details to PayPal', 'paypal-for-woocommerce' ),
                'type' => 'checkbox',
                'description' => __( 'Include all line item details in the payment request to PayPal so that they can be seen from the PayPal transaction details page.', 'paypal-for-woocommerce' ),
                'default' => 'yes'
            ),
            'payment_action' => array(
                'title' => __('Payment Action', 'paypal-for-woocommerce'),
                'label' => __('Whether to process as a Sale or Authorization.', 'paypal-for-woocommerce'),
                'description' => __('Sale will capture the funds immediately when the order is placed.  Authorization will authorize the payment but will not capture the funds.  You would need to capture funds through your PayPal account when you are ready to deliver.'),
                'type' => 'select',
                'options' => array(
                    'Sale' => 'Sale',
                    'Authorization' => 'Authorization',
                ),
                'default' => 'Sale'
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
            'is_encrypt' => array(
                'title' => __('', 'paypal-for-woocommerce'),
                'label' => __('', 'paypal-for-woocommerce'),
                'type' => 'hidden',
                'default' => 'yes',
                'class' => ''
            )
		);
        $this->form_fields = apply_filters( 'angelleye_fc_form_fields', $this->form_fields );
    }

	/**
     * Check if this gateway is enabled and available in the user's country
     *
     * This method no is used anywhere??? put above but need a fix below
     */
	function is_available() {

		if ( $this->enabled == "yes" ) {

			if ( $this->testmode == "no" && get_option('woocommerce_force_ssl_checkout')=='no' && !class_exists( 'WordPressHTTPS' ) )
				return false;

			// Currency check
			if ( ! in_array( get_woocommerce_currency(), $this->allowed_currencies ) )
				return false;

			// Required fields check
			if ( ! $this->paypal_vendor || ! $this->paypal_password )
				return false;

			return true;
		}

		return false;
	}

	/**
     * Process the payment
     */
	function process_payment( $order_id ) {

		if ( ! session_id() )
			session_start();

		$order = new WC_Order( $order_id );

                
                $card_number    = isset( $_POST['paypal_pro_payflow-card-number'] ) ? wc_clean( $_POST['paypal_pro_payflow-card-number'] ) : '';
                $card_cvc       = isset( $_POST['paypal_pro_payflow-card-cvc'] ) ? wc_clean( $_POST['paypal_pro_payflow-card-cvc'] ) : '';
                $card_exp_year    = isset( $_POST['paypal_pro_payflow_card_expiration_year'] ) ? wc_clean( $_POST['paypal_pro_payflow_card_expiration_year'] ) : '';
                $card_exp_month    = isset( $_POST['paypal_pro_payflow_card_expiration_month'] ) ? wc_clean( $_POST['paypal_pro_payflow_card_expiration_month'] ) : '';

                // Format values
                $card_number    = str_replace( array( ' ', '-' ), '', $card_number );
               
                $card_type = AngellEYE_Utility::card_type_from_account_number($card_number);
                
                if($card_type == 'amex' && (get_woocommerce_currency() != 'USD' && get_woocommerce_currency() != 'AUD')) {
                    throw new Exception( __( 'Your processor is unable to process the Card Type in the currency requested. Please try another card type', 'paypal-for-woocommerce' ) );
                }

                if ( strlen( $card_exp_year ) == 4 ) {
                        $card_exp_year = $card_exp_year - 2000;
                }
                
                $card_exp_month = (int) $card_exp_month;
                if ($card_exp_month < 10) {
                    $card_exp_month = '0'.$card_exp_month;
                }
		// Do payment with paypal
		return $this->do_payment( $order, $card_number, $card_exp_month . $card_exp_year, $card_cvc );
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
    function do_payment( $order, $card_number, $card_exp, $card_csc, $centinelPAResStatus = '', $centinelEnrolled = '', $centinelCavv = '', $centinelEciFlag = '', $centinelXid = '')
	{
		/*
		 * Display message to user if session has expired.
		 */
		if(sizeof(WC()->cart->get_cart()) == 0)
		{
            $fc_session_expired = apply_filters( 'angelleye_fc_session_expired', sprintf(__( 'Sorry, your session has expired. <a href=%s>Return to homepage &rarr;</a>', 'paypal-for-woocommerce' ), '"'.home_url().'"'), $this );
            wc_add_notice( $fc_session_expired, "error" );
		}
		
                /*
                 * Check if the PayPal_PayFlow class has already been established.
                 */
                if (!class_exists('Angelleye_PayPal')) {
                    require_once('lib/angelleye/paypal-php-library/includes/paypal.class.php');
                }
                if(!class_exists('Angelleye_PayPal_PayFlow' )) {
                    require_once('lib/angelleye/paypal-php-library/includes/paypal.payflow.class.php');	
                }
		
		/**
		 * Create PayPal_PayFlow object.
		 */
		$PayPalConfig = array(
						'Sandbox' => ($this->testmode=='yes')? true:false, 
						'APIUsername' => $this->paypal_user, 
						'APIPassword' => trim($this->paypal_password), 
						'APIVendor' => $this->paypal_vendor, 
						'APIPartner' => $this->paypal_partner,
                                                'Force_tls_one_point_two' => $this->Force_tls_one_point_two
					  );
		$PayPal = new Angelleye_PayPal_PayFlow($PayPalConfig);
		
		/**
		 * Pulled from original Woo extension.
		 */
		if(empty($GLOBALS['wp_rewrite']))
		{
        	$GLOBALS['wp_rewrite'] = new WP_Rewrite();
		}
		
		try
		{
			/**
			 * Parameter set by original Woo.  I can probably ditch this, but leaving it for now.
			 */
			$url = $this->testmode == 'yes' ? $this->testurl : $this->liveurl;
			
			/**
			 * PayPal PayFlow Gateway Request Params
			 */
                        
                        $customer_note = $order->customer_note ? substr(preg_replace("/[^A-Za-z0-9 ]/", "", $order->customer_note), 0, 256) : '';
                        
                        $firstname = isset($_POST['paypal_pro-card-cardholder-first']) && !empty($_POST['paypal_pro_payflow-card-cardholder-first']) ? wc_clean($_POST['paypal_pro_payflow-card-cardholder-first']) : $order->billing_first_name;
                        $lastname = isset($_POST['paypal_pro-card-cardholder-last']) && !empty($_POST['paypal_pro_payflow-card-cardholder-last']) ? wc_clean($_POST['paypal_pro_payflow-card-cardholder-last']) : $order->billing_last_name;
                        
			$PayPalRequestData = array(
					'tender'=>'C', 				// Required.  The method of payment.  Values are: A = ACH, C = Credit Card, D = Pinless Debit, K = Telecheck, P = PayPal
					'trxtype'=> $this->payment_action == 'Authorization' ? 'A' : 'S', 				// Required.  Indicates the type of transaction to perform.  Values are:  A = Authorization, B = Balance Inquiry, C = Credit, D = Delayed Capture, F = Voice Authorization, I = Inquiry, L = Data Upload, N = Duplicate Transaction, S = Sale, V = Void
					'acct'=>$card_number, 				// Required for credit card transaction.  Credit card or purchase card number.
					'expdate'=>$card_exp, 				// Required for credit card transaction.  Expiration date of the credit card.  Format:  MMYY
					'amt'=> AngellEYE_Gateway_Paypal::number_format($order->get_total()), 					// Required.  Amount of the transaction.  Must have 2 decimal places. 
					'currency'=>$order->get_order_currency(), // 
					'dutyamt'=>'', 				//
					'freightamt'=>'', 			//
					'taxamt'=>'', 				//
					'taxexempt'=>'', 			// 
					'custom' =>  apply_filters( 'ae_pppf_custom_parameter', $customer_note , $order ),                        // Free-form field for your own use. 
					'comment1'=> apply_filters( 'ae_pppf_comment1_parameter', '' , $order ), 			// Merchant-defined value for reporting and auditing purposes.  128 char max
					'comment2'=> apply_filters( 'ae_pppf_comment2_parameter', '' , $order ), 			// Merchant-defined value for reporting and auditing purposes.  128 char max
					'cvv2'=>$card_csc, 				// A code printed on the back of the card (or front for Amex)
					'recurring'=>'', 			// Identifies the transaction as recurring.  One of the following values:  Y = transaction is recurring, N = transaction is not recurring. 
					'swipe'=>'', 				// Required for card-present transactions.  Used to pass either Track 1 or Track 2, but not both.
					'orderid'=> $this->invoice_id_prefix . preg_replace("/[^a-zA-Z0-9]/", "", $order->get_order_number()), // Checks for duplicate order.  If you pass orderid in a request and pass it again in the future the response returns DUPLICATE=2 along with the orderid
					'orderdesc'=>'Order ' . $order->get_order_number() . ' on ' . get_bloginfo( 'name' ), //
					'billtoemail'=>$order->billing_email, 			// Account holder's email address.
					'billtophonenum'=>'', 		// Account holder's phone number.
					'billtofirstname'=> $firstname, 		// Account holder's first name.
					'billtomiddlename'=>'', 	// Account holder's middle name.
					'billtolastname'=> $lastname, 		// Account holder's last name.
					'billtostreet'=>$order->billing_address_1.' '.$order->billing_address_2, 		// The cardholder's street address (number and street name).  150 char max
					'billtocity'=>$order->billing_city, 			// Bill to city.  45 char max
					'billtostate'=>$order->billing_state, 			// Bill to state.  
					'billtozip'=>$order->billing_postcode, 			// Account holder's 5 to 9 digit postal code.  9 char max.  No dashes, spaces, or non-numeric characters
					'billtocountry'=>$order->billing_country, 		// Bill to Country.  3 letter country code.
					'origid'=>'', 				// Required by some transaction types.  ID of the original transaction referenced.  The PNREF parameter returns this ID, and it appears as the Transaction ID in PayPal Manager reports.  
					'custref'=>'', 				// 
					'custcode'=>'', 			// 
					'custip'=>$this->get_user_ip(), 				// 
					'invnum'=>$this->invoice_id_prefix . str_replace("#","",$order->get_order_number()), 				//
					'ponum'=>'', 				// 
					'starttime'=>'', 			// For inquiry transaction when using CUSTREF to specify the transaction.
					'endtime'=>'', 				// For inquiry transaction when using CUSTREF to specify the transaction.
					'securetoken'=>'', 			// Required if using secure tokens.  A value the Payflow server created upon your request for storing transaction data.  32 char
					'partialauth'=>'', 			// Required for partial authorizations.  Set to Y to submit a partial auth.    
					'authcode'=>'' 			// Rrequired for voice authorizations.  Returned only for approved voice authorization transactions.  AUTHCODE is the approval code received over the phone from the processing network.  6 char max
					);
			
			/**
			 * Shipping info
			 */
			if($order->shipping_address_1)
			{
                $PayPalRequestData['SHIPTOFIRSTNAME']   = $order->shipping_first_name;
                $PayPalRequestData['SHIPTOLASTNAME']    = $order->shipping_last_name;
                $PayPalRequestData['SHIPTOSTREET']      = $order->shipping_address_1 . ' ' . $order->shipping_address_2;
                $PayPalRequestData['SHIPTOCITY']        = $order->shipping_city;
                $PayPalRequestData['SHIPTOSTATE']       = $order->shipping_state;
                $PayPalRequestData['SHIPTOCOUNTRY']     = $order->shipping_country;
                $PayPalRequestData['SHIPTOZIP']         = $order->shipping_postcode;
            }

            $PaymentData = AngellEYE_Gateway_Paypal::calculate($order, $this->send_items);
            $OrderItems = array();
            if ($this->send_items){
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
            }

            /**
             * Shipping/tax/item amount
             */
            $PayPalRequestData['taxamt']       = $PaymentData['taxamt'];
            $PayPalRequestData['freightamt']   = $PaymentData['shippingamt'];
            $PayPalRequestData['ITEMAMT']      = $PaymentData['itemamt'];

            if( $this->send_items ) {
                $PayPalRequestData = array_merge($PayPalRequestData, $OrderItems);
            }
        
                        $log = $PayPalRequestData;
                        if(!empty($_POST['wc-paypal_pro_payflow-payment-token']) && $_POST['wc-paypal_pro_payflow-payment-token'] != 'new') {
                             $token_id = wc_clean( $_POST['wc-paypal_pro_payflow-payment-token'] );
                             $token = WC_Payment_Tokens::get( $token_id );
                             $PayPalRequestData['origid'] = $token->get_token();
                             $PayPalRequestData['expdate'] = '';
                        } else {
                            $log['acct'] = '****';
                            $log['cvv2'] = '****';
                        }
                        $this->add_log('PayFlow Request: '.print_r( $log, true ) );
			$PayPalResult = $PayPal->ProcessTransaction($PayPalRequestData);
                        
                        /**
                        *  cURL Error Handling #146 
                        *  @since    1.1.8
                        */

                        AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($PayPalResult, $methos_name = 'do_payment', $gateway = 'PayPal Payments Pro 2.0 (PayFlow)', $this->error_email_notify);
			
			
                        $this->add_log('PayFlow Endpoint: '.$PayPal->APIEndPoint);
                        $this->add_log('PayFlow Response: '.print_r($PayPalResult,true));
			

			/**
			 * Error check
			 */
			if(empty($PayPalResult['RAWRESPONSE']))
			{
                $fc_empty_response = apply_filters( 'ae_pppf_paypal_response_empty_message', __('Empty PayPal response.', 'paypal-for-woocommerce'), $PayPalResult );
                throw new Exception( $fc_empty_response );
			}

			/**
			 * Check for errors or fraud filter warnings and proceed accordingly.
			 */
			if(isset($PayPalResult['RESULT']) && ($PayPalResult['RESULT'] == 0 || $PayPalResult['RESULT'] == 126))
			{
                // Add order note
                if ($PayPalResult['RESULT'] == 126)
				{
                    $order->add_order_note( $PayPalResult['RESPMSG']);
                    $order->add_order_note( $PayPalResult['PREFPSMSG']);
                    $order->add_order_note( "The payment was flagged by a fraud filter, please check your PayPal Manager account to review and accept or deny the payment.");
                }
				else
				{
                                
                                if( isset($PayPalResult['PPREF']) && !empty($PayPalResult['PPREF']) ) {
                                    add_post_meta($order->id, 'PPREF', $PayPalResult['PPREF']);
                                    $order->add_order_note(sprintf(__('PayPal Pro payment completed (PNREF: %s) (PPREF: %s)','paypal-for-woocommerce'),$PayPalResult['PNREF'], $PayPalResult['PPREF']));
                                } else {
                                    $order->add_order_note(sprintf(__('PayPal Pro payment completed (PNREF: %s)','paypal-for-woocommerce'),$PayPalResult['PNREF']));
                                }
                                /* Checkout Note */
                                if (isset($_POST) && !empty($_POST['order_comments'])) {
                                    // Update post 37
                                    $checkout_note = array(
                                        'ID' => $order->id,
                                        'post_excerpt' => $_POST['order_comments'],
                                    );
                                    wp_update_post($checkout_note);
                                }
                }
				
				/**
				 * Add order notes for AVS result
				 */
				$avs_address_response_code = isset($PayPalResult['AVSADDR']) ? $PayPalResult['AVSADDR'] : '';
				$avs_zip_response_code = isset($PayPalResult['AVSZIP']) ? $PayPalResult['AVSZIP'] : '';
				
				$avs_response_order_note = __('Address Verification Result','paypal-for-woocommerce');
				$avs_response_order_note .= "\n";
				$avs_response_order_note .= sprintf(__('Address Match: %s','paypal-for-woocommerce'),$avs_address_response_code);
				$avs_response_order_note .= "\n";
				$avs_response_order_note .= sprintf(__('Postal Match: %s','paypal-for-woocommerce'),$avs_zip_response_code);
				$order->add_order_note($avs_response_order_note);
				
				/**
				 * Add order notes for CVV2 result
				 */
				$cvv2_response_code = isset($PayPalResult['CVV2MATCH']) ? $PayPalResult['CVV2MATCH'] : '';
				$cvv2_response_order_note = __('Card Security Code Result','paypal-for-woocommerce');
				$cvv2_response_order_note .= "\n";
				$cvv2_response_order_note .= sprintf(__('CVV2 Match: %s','paypal-for-woocommerce'),$cvv2_response_code);
				$order->add_order_note($cvv2_response_order_note);

                // Payment complete
                //$order->add_order_note("PayPal Result".print_r($PayPalResult,true));
                do_action('before_save_payment_token', $order->id);               
                if(!empty($_POST['wc-paypal_pro_payflow-payment-token']) && $_POST['wc-paypal_pro_payflow-payment-token'] == 'new') {
                    if(!empty($_POST['wc-paypal_pro_payflow-new-payment-method']) && $_POST['wc-paypal_pro_payflow-new-payment-method'] == true) {
                        $customer_id =  $order->get_user_id();
                        $TRANSACTIONID = $PayPalResult['PNREF'];
                        $token = new WC_Payment_Token_CC();
                        $token->set_user_id( $customer_id );
                        $token->set_token( $TRANSACTIONID );
                        $token->set_gateway_id( $this->id );
                        $token->set_card_type( AngellEYE_Utility::card_type_from_account_number($PayPalRequestData['acct']));
                        $token->set_last4( substr( $PayPalRequestData['acct'], -4 ) );
                        $token->set_expiry_month( substr( $PayPalRequestData['expdate'], 0,2 ) );
                        $expiry_year = substr( $PayPalRequestData['expdate'], 2,3 );
                        if ( strlen( $expiry_year ) == 2 ) {
                            $expiry_year = $expiry_year + 2000;
                        }
                        $token->set_expiry_year( $expiry_year );
                        $save_result = $token->save();
                        if ( $save_result ) {
                                $order->add_payment_token( $token );
                        }
                    }
                }
                $order->payment_complete($PayPalResult['PNREF']);

                // Remove cart
                WC()->cart->empty_cart();

                // Return thank you page redirect
                return array(
                    'result' 	=> 'success',
                    'redirect'	=> $this->get_return_url($order)
                );
            }
			else
			{
				$order->update_status( 'failed', __('PayPal Pro payment failed. Payment was rejected due to an error: ', 'paypal-for-woocommerce' ) . '(' . $PayPalResult['RESULT'] . ') ' . '"' . $PayPalResult['RESPMSG'] . '"' );
				
				// Generate error message based on Error Display Type setting
				if($this->error_display_type == 'detailed')
				{
                    $fc_error_display_type = __( 'Payment error:', 'paypal-for-woocommerce' ) . ' ' . $PayPalResult['RESULT'].'-'.$PayPalResult['RESPMSG'];
				}
				else
				{
                    $fc_error_display_type = __( 'Payment error:', 'paypal-for-woocommerce' ) . ' There was a problem processing your payment.  Please try another method.';
				}
                $fc_error_display_type = apply_filters( 'ae_pppf_error_user_display_message', $fc_error_display_type, $PayPalResult['RESULT'], $PayPalResult['RESPMSG'], $PayPalResult );
                wc_add_notice( $fc_error_display_type, "error" );
				
				// Notice admin if has any issue from PayPal
				if($this->error_email_notify)
				{
					$admin_email = get_option("admin_email");
					$message = __( "PayFlow API call failed." , "paypal-for-woocommerce" )."\n\n";
					$message .= __( 'Error Code: ' ,'paypal-for-woocommerce' ) . $PayPalResult['RESULT'] ."\n";
					$message .= __( 'Detailed Error Message: ' , 'paypal-for-woocommerce') . $PayPalResult['RESPMSG'];
					$message .= isset($PayPalResult['PREFPSMSG']) && $PayPalResult['PREFPSMSG'] != '' ? ' - ' . $PayPalResult['PREFPSMSG'] ."\n" : "\n";
                                        $message .= __( 'User IP: ', 'paypal-for-woocommerce') . $this->get_user_ip() . "\n";
                    $message .= __( 'Order ID: ' ).$order->id ."\n";
                    $message .= __( 'Customer Name: ' ).$order->billing_first_name.' '.$order->billing_last_name."\n";
                    $message .= __( 'Customer Email: ' ).$order->billing_email."\n";
	                $message = apply_filters( 'ae_pppf_error_email_message', $message );
	                $subject = apply_filters( 'ae_pppf_error_email_subject', "PayPal Pro Error Notification" );
					wp_mail( $admin_email, $subject, $message );
				}
				
                return;

            }
		}
		catch(Exception $e)
		{
            $fc_connect_error = apply_filters( 'angelleye_fc_connect_error', $e->getMessage() , $e  );
            wc_add_notice( $fc_connect_error, "error");
            return;
        }	
	}

        
        public function payment_fields() {
            do_action( 'angelleye_before_fc_payment_fields', $this );
            if ( $this->description ) {
                echo '<p>' . wp_kses_post( $this->description );
                if($this->testmode == "yes")
                {
                        echo '<p>';
                        _e('NOTICE: SANDBOX (TEST) MODE ENABLED.', 'paypal-for-woocommerce');
                        echo '<br />';
                        _e('For testing purposes you can use the card number 4111111111111111 with any CVC and a valid expiration date.', 'paypal-for-woocommerce');
                        echo '</p>';
                }
            }
            parent::payment_fields();
            do_action('payment_fields_saved_payment_methods', $this);
	}
        
        public function paypal_for_woocommerce_paypal_pro_payflow_credit_card_form_expiration_date_selectbox() {
            $form_html = "";
            $form_html .= '<p class="form-row form-row-first">';
            $form_html .= '<label for="cc-expire-month">' . __("Expiration Date", 'paypal-for-woocommerce') . '<span class="required">*</span></label>';
            $form_html .= '<select name="paypal_pro_payflow_card_expiration_month" id="cc-expire-month" class="woocommerce-select woocommerce-cc-month mr5">';
            $form_html .= '<option value="">' . __('Month', 'paypal-for-woocommerce') . '</option>';
            $months = array();
            for ($i = 1; $i <= 12; $i++) :
                $timestamp = mktime(0, 0, 0, $i, 1);
                $months[date('n', $timestamp)] = date_i18n(_x('F', 'Month Names', 'paypal-for-woocommerce'), $timestamp);
            endfor;
            foreach ($months as $num => $name) {
                $form_html .= '<option value=' . $num . '>' . $name . '</option>';
            }
            $form_html .= '</select>';
            $form_html .= '<select name="paypal_pro_payflow_card_expiration_year" id="cc-expire-year" class="woocommerce-select woocommerce-cc-year ml5">';
            $form_html .= '<option value="">' . __('Year', 'paypal-for-woocommerce') . '</option>';
            for ($i = date('y'); $i <= date('y') + 15; $i++) {
                $form_html .= '<option value=' . $i . '>20' . $i . '</option>';
            }
            $form_html .= '</select>';
            $form_html .= '</p>';
            return $form_html;
        }


	/**
     * Get user's IP address
     */
	function get_user_ip() {
		return ! empty( $_SERVER['HTTP_X_FORWARD_FOR'] ) ? $_SERVER['HTTP_X_FORWARD_FOR'] : $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * clear_centinel_session function.
	 *
	 * @access public
	 * @return void
	 */
	function clear_centinel_session() {
        unset( $_SESSION['Message'] );
        foreach ( $_SESSION as $key => $value ) {
            if ( preg_match( "/^Centinel_.*/", $key ) > 0 ) {
                unset( $_SESSION[ $key ] );
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
    public function process_refund( $order_id, $amount = null, $reason = '' ) {

        do_action( 'angelleye_before_fc_refund', $order_id, $amount, $reason );

        $order = wc_get_order( $order_id );
        $this->add_log( 'Begin Refund' );
        $this->add_log( 'Order ID: '. print_r($order_id, true) );
        $this->add_log( 'Transaction ID: '. print_r($order->get_transaction_id(), true) );
        if ( ! $order || ! $order->get_transaction_id() || ! $this->paypal_user || ! $this->paypal_password || ! $this->paypal_vendor ) {
            return false;
        }

        /**
         * Check if the PayPal_PayFlow class has already been established.
         */
        if (!class_exists('Angelleye_PayPal')) {
            require_once('lib/angelleye/paypal-php-library/includes/paypal.class.php');
        }
        if(!class_exists('Angelleye_PayPal_PayFlow' )) {
            require_once('lib/angelleye/paypal-php-library/includes/paypal.payflow.class.php');
        }

        /**
         * Create PayPal_PayFlow object.
         */
        $PayPalConfig = array(
            'Sandbox' => ($this->testmode=='yes')? true:false,
            'APIUsername' => $this->paypal_user,
            'APIPassword' => trim($this->paypal_password),
            'APIVendor' => $this->paypal_vendor,
            'APIPartner' => $this->paypal_partner,
            'Force_tls_one_point_two' => $this->Force_tls_one_point_two
        );
        $PayPal = new Angelleye_PayPal_PayFlow($PayPalConfig);
        $PayPalRequestData = array(
            'TENDER' => 'C', // C = credit card, P = PayPal
            'TRXTYPE' => 'C', //  S=Sale, A= Auth, C=Credit, D=Delayed Capture, V=Void
            'ORIGID' => $order->get_transaction_id(),
            'AMT' => $amount,
            'CURRENCY' => $order->get_order_currency()
        );
    
        $PayPalResult = $PayPal->ProcessTransaction($PayPalRequestData);
        
        $PayPalRequest = isset($PayPalResult['RAWREQUEST']) ? $PayPalResult['RAWREQUEST'] : '';
        $PayPalResponse = isset($PayPalResult['RAWRESPONSE']) ? $PayPalResult['RAWRESPONSE'] : '';

        $this->add_log('Refund Request: ' . print_r($PayPalRequestData, true));
        $this->add_log('Refund Response: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalResponse)), true));
        
         /**
         *  cURL Error Handling #146 
         *  @since    1.1.8
         */
        
        AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($PayPalResult, $methos_name = 'Refund Request', $gateway = 'PayPal Payments Pro 2.0 (PayFlow)', $this->error_email_notify);
        
        add_action( 'angelleye_after_refund', $PayPalResult, $order, $amount, $reason );
        if(isset($PayPalResult['RESULT']) && ($PayPalResult['RESULT'] == 0 || $PayPalResult['RESULT'] == 126)){

			$order->add_order_note('Refund Transaction ID:' . $PayPalResult['PNREF']);

			$max_remaining_refund = wc_format_decimal( $order->get_total() - $order->get_total_refunded() );
			if ( !$max_remaining_refund > 0 ) {
				$order->update_status('refunded');
			}

            if (ob_get_length()) ob_end_clean();
            return true;
        }else{
            $fc_refund_error = apply_filters( 'ae_pppf_refund_error_message', $PayPalResult['RESPMSG'], $PayPalResult );
            return new WP_Error( 'paypal-error', $fc_refund_error );
        }
        return false;
    }
    
    /**
     * Validate the payment form
     * PayFlow - Empty Card Data Validation Problem #220 
     * @since    1.1.7.6
     */
    public function validate_fields() {

        if ( isset( $_POST['wc-paypal_pro_payflow-payment-token'] ) && 'new' !== $_POST['wc-paypal_pro_payflow-payment-token'] ) {
            $token_id = wc_clean( $_POST['wc-paypal_pro_payflow-payment-token'] );
            $token  = WC_Payment_Tokens::get( $token_id );
            if ( $token->get_user_id() !== get_current_user_id() ) {
                throw new Exception(__('Error processing checkout. Please try again.', 'paypal-for-woocommerce'));
            }else {
                return true;
            }
         }
        $card_number    = isset( $_POST['paypal_pro_payflow-card-number'] ) ? wc_clean( $_POST['paypal_pro_payflow-card-number'] ) : '';
        $card_cvc       = isset( $_POST['paypal_pro_payflow-card-cvc'] ) ? wc_clean( $_POST['paypal_pro_payflow-card-cvc'] ) : '';
        $card_exp_year    = isset( $_POST['paypal_pro_payflow_card_expiration_year'] ) ? wc_clean( $_POST['paypal_pro_payflow_card_expiration_year'] ) : '';
        $card_exp_month    = isset( $_POST['paypal_pro_payflow_card_expiration_month'] ) ? wc_clean( $_POST['paypal_pro_payflow_card_expiration_month'] ) : '';


        // Format values
        $card_number    = str_replace( array( ' ', '-' ), '', $card_number );

        if ( strlen( $card_exp_year ) == 4 ) {
                $card_exp_year = $card_exp_year - 2000;
        }

        do_action('before_angelleye_pro_payflow_checkout_validate_fields', $card_number, $card_cvc, $card_exp_month, $card_exp_year);

        // Check card security code

        if (!ctype_digit($card_cvc)) {
            wc_add_notice(__('Card security code is invalid (only digits are allowed)', 'paypal-for-woocommerce'), "error");
            return false;
        }

        // Check card expiration data

        if (!ctype_digit($card_exp_month) || !ctype_digit($card_exp_year) || $card_exp_month > 12 || $card_exp_month < 1 || $card_exp_year < date('y') || $card_exp_year > date('y') + 20) {
            wc_add_notice(__('Card expiration date is invalid', 'paypal-for-woocommerce'), "error");
            return false;
        }

        // Check card number

        if (empty($card_number) || !ctype_digit($card_number)) {
            wc_add_notice(__('Card number is invalid', 'paypal-for-woocommerce'), "error");
            return false;
        }

        do_action('after_angelleye_pro_payflow_checkout_validate_fields', $card_number, $card_cvc, $card_exp_month, $card_exp_year);

        return true;
    }
    
    public function field_name( $name ) {
	return ' name="' . esc_attr( $this->id . '-' . $name ) . '" ';
    }
    public function angelleye_paypal_pro_payflow_credit_card_form_fields($default_fields, $current_gateway_id) {
        if($current_gateway_id == $this->id) {
              $fields = array(
			'card-number-field' => '<p class="form-row form-row-wide">
                            <label for="' . esc_attr( $this->id ) . '-card-number">' . __( 'Card number', 'woocommerce' ) . ' <span class="required">*</span></label>
                            <input id="' . esc_attr( $this->id ) . '-card-number" class="input-text wc-credit-card-form-card-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ' . $this->field_name( 'card-number' ) . ' />
                        </p>',
			'card-expiry-field' => $this->paypal_for_woocommerce_paypal_pro_payflow_credit_card_form_expiration_date_selectbox(),
			'<p class="form-row form-row-last">
                            <label for="' . esc_attr( $this->id ) . '-card-cvc">' . __( 'Card Security Code', 'woocommerce' ) . ' <span class="required">*</span></label>
                            <input id="' . esc_attr( $this->id ) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="' . esc_attr__( 'CVC', 'woocommerce' ) . '" ' . $this->field_name( 'card-cvc' ) . ' style="width:100px" />
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
                    <label for="' . esc_attr($this->id) . '-card-cvc">' . __('Cardholder First Name', 'paypal-for-woocommerce') . '</label>
                    <input id="' . esc_attr($this->id) . '-card-cvc" class="input-text wc-credit-card-form-cardholder" type="text" autocomplete="off" placeholder="' . esc_attr__('First Name', 'paypal-for-woocommerce') . '" name="' . $current_id . '-card-cardholder-first' . '" />
            </p>';
            $fields['card-cardholder-last'] = '<p class="form-row form-row-last">
                    <label for="' . esc_attr($this->id) . '-card-startdate">' . __('Cardholder Last Name', 'paypal-for-woocommerce') . '</label>
                    <input id="' . esc_attr($this->id) . '-card-startdate" class="input-text wc-credit-card-form-cardholder" type="text" autocomplete="off" placeholder="' . __('Last Name', 'paypal-for-woocommerce') . '" name="' . $current_id . '-card-cardholder-last' . '" />
            </p>';

            foreach ($fields as $field) {
                echo $field;
            }
        }
    }
    
    public function get_posted_card()
    {
        try {
            $card_number    = isset( $_POST['paypal_pro_payflow-card-number'] ) ? wc_clean( $_POST['paypal_pro_payflow-card-number'] ) : '';
            $card_cvc       = isset( $_POST['paypal_pro_payflow-card-cvc'] ) ? wc_clean( $_POST['paypal_pro_payflow-card-cvc'] ) : '';
            $card_exp_year    = isset( $_POST['paypal_pro_payflow_card_expiration_year'] ) ? wc_clean( $_POST['paypal_pro_payflow_card_expiration_year'] ) : '';
            $card_exp_month    = isset( $_POST['paypal_pro_payflow_card_expiration_month'] ) ? wc_clean( $_POST['paypal_pro_payflow_card_expiration_month'] ) : '';
            $card_number    = str_replace( array( ' ', '-' ), '', $card_number );
            $card_type = AngellEYE_Utility::card_type_from_account_number($card_number);
            if($card_type == 'amex' && (get_woocommerce_currency() != 'USD' && get_woocommerce_currency() != 'AUD')) {
                throw new Exception( __( 'Your processor is unable to process the Card Type in the currency requested. Please try another card type', 'paypal-for-woocommerce' ) );
            }
            if ( strlen( $card_exp_year ) == 4 ) {
                $card_exp_year = $card_exp_year - 2000;
            }
            $card_exp_month = (int) $card_exp_month;
            if ($card_exp_month < 10) {
                $card_exp_month = '0'.$card_exp_month;
            }
            return (object)array(
                'number' => $card_number,
                'type' => '',
                'cvc' => $card_cvc,
                'exp_month' => $card_exp_month,
                'exp_year' => $card_exp_year,
                'start_month' => '',
                'start_year' => ''
            );
        } catch (Exception $ex) {

        }
        
    }

    public function add_payment_method() {
        $customer_id = get_current_user_id();
        if (!class_exists('Angelleye_PayPal')) {
            require_once('lib/angelleye/paypal-php-library/includes/paypal.class.php');
        }
        if(!class_exists('Angelleye_PayPal_PayFlow' )) {
            require_once('lib/angelleye/paypal-php-library/includes/paypal.payflow.class.php');
        }
        $PayPalConfig = array(
            'Sandbox' => ($this->testmode=='yes')? true:false,
            'APIUsername' => $this->paypal_user,
            'APIPassword' => trim($this->paypal_password),
            'APIVendor' => $this->paypal_vendor,
            'APIPartner' => $this->paypal_partner,
            'Force_tls_one_point_two' => $this->Force_tls_one_point_two
        );
        $PayPal = new Angelleye_PayPal_PayFlow($PayPalConfig);
        $this->validate_fields();
        $card = $this->get_posted_card();
        
        $billtofirstname = (get_user_meta( $customer_id, 'billing_first_name', true )) ? get_user_meta( $customer_id, 'billing_first_name', true ) : get_user_meta( $customer_id, 'shipping_first_name', true );
        $billtolastname = (get_user_meta( $customer_id, 'billing_last_name', true )) ? get_user_meta( $customer_id, 'billing_last_name', true ) : get_user_meta( $customer_id, 'shipping_last_name', true );
        $billtostate = (get_user_meta( $customer_id, 'billing_state', true )) ? get_user_meta( $customer_id, 'billing_state', true ) : get_user_meta( $customer_id, 'shipping_state', true );
        $billtocountry = (get_user_meta( $customer_id, 'billing_country', true )) ? get_user_meta( $customer_id, 'billing_country', true ) : get_user_meta( $customer_id, 'shipping_country', true );
        $billtozip = (get_user_meta( $customer_id, 'billing_postcode', true )) ? get_user_meta( $customer_id, 'billing_postcode', true ) : get_user_meta( $customer_id, 'shipping_postcode', true );
        
        $PayPalRequestData = array(
            'tender' => 'C',
            'trxtype' => 'A',
            'acct' => $card->number,
            'expdate' => $card->exp_month . $card->exp_year,
            'amt' => '0.00',
            'currency' => get_woocommerce_currency(), 
            'cvv2' => $card->cvc,
            'orderid'=> '',
            'orderdesc'=> '',
            'billtoemail' => '',
            'billtophonenum' => '', 
            'billtofirstname' => $billtofirstname,
            'billtomiddlename' => '',
            'billtolastname'=> $billtolastname,
            'billtostreet' => '', 
            'billtocity' => '', 			
            'billtostate' => $billtostate, 			
            'billtozip' => $billtozip, 			
            'billtocountry' => $billtocountry, 		
            'origid' => '', 				
            'custref' => '',
            'custcode'=>'', 
            'custip' => $this->get_user_ip(),
            'invnum'=> '',
            'ponum' => '',
            'starttime' => '',
            'endtime' => '',
            'securetoken' => '',
            'partialauth' => '',
            'authcode' => ''
        );
        $PayPalResult = $PayPal->ProcessTransaction($PayPalRequestData);
        if(isset($PayPalResult['RESULT']) && ($PayPalResult['RESULT'] == 0 || $PayPalResult['RESULT'] == 126)) {
            if ($PayPalResult['RESULT'] == 126) {
                wc_add_notice( __( 'The payment was flagged by a fraud filter, please check your PayPal Manager account to review and accept or deny the payment.', 'woocommerce' ), 'error' );
                wp_redirect( wc_get_account_endpoint_url( 'payment-methods' ) );
                exit();
            } else {
                $customer_id = get_current_user_id();
                $TRANSACTIONID = $PayPalResult['PNREF'];
                $token = new WC_Payment_Token_CC();
                $token->set_user_id( $customer_id );
                $token->set_token( $TRANSACTIONID );
                $token->set_gateway_id( $this->id );
                $token->set_card_type( AngellEYE_Utility::card_type_from_account_number($PayPalRequestData['acct']));
                $token->set_last4( substr( $PayPalRequestData['acct'], -4 ) );
                $token->set_expiry_month( substr( $PayPalRequestData['expdate'], 0,2 ) );
                $expiry_year = substr( $PayPalRequestData['expdate'], 2,3 );
                if ( strlen( $expiry_year ) == 2 ) {
                    $expiry_year = $expiry_year + 2000;
                }
                $token->set_expiry_year( $expiry_year );
                $save_result = $token->save();
                if ( $save_result ) {
                    return array(
                        'result' => 'success',
                        'redirect' => wc_get_account_endpoint_url( 'payment-methods' )
                    );
                }
            }
        } else {
            wc_add_notice( __( $PayPalResult['RESPMSG'], 'woocommerce' ), 'error' );
            wp_redirect( wc_get_account_endpoint_url( 'payment-methods' ) );
            exit();
        }
    }
    
    public function angelleye_paypal_pro_payflow_encrypt_gateway_api($settings) {
        if( !empty($settings['is_encrypt']) ) {
            $gateway_settings_keys = array('sandbox_paypal_vendor', 'sandbox_paypal_password', 'sandbox_paypal_user', 'sandbox_paypal_partner', 'paypal_vendor', 'paypal_password', 'paypal_user', 'paypal_partner');
            foreach ($gateway_settings_keys as $gateway_settings_key => $gateway_settings_value) {
                if( !empty( $settings[$gateway_settings_value]) ) {
                    $settings[$gateway_settings_value] = AngellEYE_Utility::crypting($settings[$gateway_settings_value], $action = 'e');
                }
            }
        }
        return $settings;
    }
}