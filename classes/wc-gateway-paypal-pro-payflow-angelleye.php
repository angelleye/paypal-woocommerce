<?php

/**
 * WC_Gateway_PayPal_Pro_PayFlow class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_PayPal_Pro_PayFlow_AngellEYE extends WC_Payment_Gateway {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
        $pp_payflow = get_option('woocommerce_paypal_pro_payflow_settings');
		$this->id					= 'paypal_pro_payflow';
		$this->method_title 		= __( 'PayPal Payments Pro 2.0 (PayFlow)', 'paypal-for-woocommerce' );
		$this->method_description 	= __( 'PayPal Payments Pro allows you to accept credit cards directly on your site without any redirection through PayPal.  You host the checkout form on your own web server, so you will need an SSL certificate to ensure your customer data is protected.', 'paypal-for-woocommerce' );
		$this->icon 				= (!empty($pp_payflow['cart_icon'])) ? $pp_payflow['cart_icon'] : WP_PLUGIN_URL . "/" . plugin_basename( dirname( dirname( __FILE__ ) ) ) . '/assets/images/cards.png';
		$this->has_fields 			= true;
		$this->liveurl				= 'https://payflowpro.paypal.com';
		$this->testurl				= 'https://pilot-payflowpro.paypal.com';
		$this->allowed_currencies   = apply_filters( 'woocommerce_paypal_pro_allowed_currencies', array( 'USD', 'EUR', 'GBP', 'CAD', 'JPY', 'AUD' ) );


        // Load the form fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

		// Get setting values
		$this->title          		= $this->settings['title'];
		$this->description    		= $this->settings['description'];
		$this->enabled        		= $this->settings['enabled'];

		$this->paypal_vendor  		= $this->settings['paypal_vendor'];
		$this->paypal_partner 		= ! empty( $this->settings['paypal_partner'] ) ? $this->settings['paypal_partner'] : 'PayPal';
		$this->paypal_password 		= $this->settings['paypal_password'];
		$this->paypal_user     		= ! empty( $this->settings['paypal_user'] ) ? $this->settings['paypal_user'] : $this->paypal_vendor;

		$this->testmode        		= $this->settings['testmode'];
		$this->debug		   		= isset( $this->settings['debug'] ) && $this->settings['debug'] == 'yes' ? true : false;
		$this->error_email_notify   = isset($this->settings['error_email_notify']) && $this->settings['error_email_notify'] == 'yes' ? true : false;
		$this->error_display_type 	= isset($this->settings['error_display_type']) ? $this->settings['error_display_type'] : '';


        if ($this->testmode=="yes") {
            $this->paypal_vendor   	= $this->settings['sandbox_paypal_vendor'];
            $this->paypal_partner  	= ! empty( $this->settings['sandbox_paypal_partner'] ) ? $this->settings['sandbox_paypal_partner'] : 'PayPal';
            $this->paypal_password 	= $this->settings['sandbox_paypal_password'];
            $this->paypal_user     	= ! empty( $this->settings['sandbox_paypal_user'] ) ? $this->settings['sandbox_paypal_user'] : $this->paypal_vendor;
        }

		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

		/* 1.6.6 */
		add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );

		/* 2.0.0 */
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}
    function add_log( $message ) {
        if ( empty( $this->log ) )
            $this->log = new WC_Logger();
        $this->log->add( 'paypal_payflow', $message );
    }
	/**
	 * payment_scripts function.
	 *
	 * @access public
	 */
	function payment_scripts() {

		if ( ! is_checkout() )
			return;

		wp_enqueue_style( 'wc-paypal-pro', plugins_url( 'assets/css/checkout.css', dirname( __FILE__ ) ) );
		wp_enqueue_script( 'card-type-detection', plugins_url( 'assets/js/card-type-detection.min.js', dirname( __FILE__ ) ), 'jquery', '1.0.0', true );
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
            'cart_icon'        => array(
                'title'       => __( 'Cart Icon', 'paypal-for-woocommerce' ),
                'type'        => 'text',
                'default'     => WP_PLUGIN_URL . "/" . plugin_basename( dirname( dirname( __FILE__ ) ) ) . '/assets/images/cards.png'
            ),
			'debug' => array(
                'title' => __( 'Debug Log', 'woocommerce' ),
                'type' => 'checkbox',
                'label' => __( 'Enable logging', 'woocommerce' ),
                'default' => 'no',
                'description' => __( 'Log PayPal events inside <code>woocommerce/logs/paypal-payflow.txt</code>' ),
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
			);
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
			if ( ! in_array( get_option('woocommerce_currency'), $this->allowed_currencies ) )
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

		$card_number = ! empty( $_POST['paypal_pro_payflow_card_number']) ? str_replace( array( ' ', '-' ), '', woocommerce_clean( $_POST['paypal_pro_payflow_card_number'] ) ) : '';
		$card_csc    = ! empty( $_POST['paypal_pro_payflow_card_csc']) ? woocommerce_clean( $_POST['paypal_pro_payflow_card_csc'] ) : '';
		$card_exp    = ! empty( $_POST['paypal_pro_payflow_card_expiration']) ? woocommerce_clean( $_POST['paypal_pro_payflow_card_expiration'] ) : '';

		// Do payment with paypal
		return $this->do_payment( $order, $card_number, $card_exp, $card_csc );
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
            wc_add_notice(sprintf(__( 'Sorry, your session has expired. <a href=%s>Return to homepage &rarr;</a>', 'paypal-for-woocommerce' ), '"'.home_url().'"'),"error");
		}
		
		/*
		 * Check if the PayPal_PayFlow class has already been established.
		 */
		if(!class_exists('PayPal_PayFlow' )) 
		{
			require_once('lib/angelleye/paypal-php-library/includes/paypal.class.php');
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
						'APIPartner' => $this->paypal_partner
					  );
		$PayPal = new PayPal_PayFlow($PayPalConfig);
		
		/**
		 * Pulled from original Woo extension.
		 */
		if(empty($GLOBALS['wp_rewrite']))
		{
        	$GLOBALS['wp_rewrite'] = new WP_Rewrite();
		}
		
		if($this->debug)
		{
			$this->add_log($order->get_checkout_order_received_url());
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
			$PayPalRequestData = array(
					'tender'=>'C', 				// Required.  The method of payment.  Values are: A = ACH, C = Credit Card, D = Pinless Debit, K = Telecheck, P = PayPal
					'trxtype'=>'S', 				// Required.  Indicates the type of transaction to perform.  Values are:  A = Authorization, B = Balance Inquiry, C = Credit, D = Delayed Capture, F = Voice Authorization, I = Inquiry, L = Data Upload, N = Duplicate Transaction, S = Sale, V = Void
					'acct'=>$card_number, 				// Required for credit card transaction.  Credit card or purchase card number.
					'expdate'=>$card_exp, 				// Required for credit card transaction.  Expiration date of the credit card.  Format:  MMYY
					'amt'=>$order->get_total(), 					// Required.  Amount of the transaction.  Must have 2 decimal places. 
					'currency'=>get_option('woocommerce_currency'), // 
					'dutyamt'=>'', 				//
					'freightamt'=>'', 			//
					'taxamt'=>'', 				//
					'taxexempt'=>'', 			// 
					'comment1'=>$order->customer_note ? wptexturize($order->customer_note) : '', 			// Merchant-defined value for reporting and auditing purposes.  128 char max
					'comment2'=>'', 			// Merchant-defined value for reporting and auditing purposes.  128 char max
					'cvv2'=>$card_csc, 				// A code printed on the back of the card (or front for Amex)
					'recurring'=>'', 			// Identifies the transaction as recurring.  One of the following values:  Y = transaction is recurring, N = transaction is not recurring. 
					'swipe'=>'', 				// Required for card-present transactions.  Used to pass either Track 1 or Track 2, but not both.
					'orderid'=>preg_replace("/[^0-9,.]/", "", $order->get_order_number()), // Checks for duplicate order.  If you pass orderid in a request and pass it again in the future the response returns DUPLICATE=2 along with the orderid
					'orderdesc'=>'Order ' . $order->get_order_number() . ' on ' . get_bloginfo( 'name' ), //
					'billtoemail'=>$order->billing_email, 			// Account holder's email address.
					'billtophonenum'=>'', 		// Account holder's phone number.
					'billtofirstname'=>$order->billing_first_name, 		// Account holder's first name.
					'billtomiddlename'=>'', 	// Account holder's middle name.
					'billtolastname'=>$order->billing_last_name, 		// Account holder's last name.
					'billtostreet'=>$order->billing_address_1.' '.$order->billing_address_2, 		// The cardholder's street address (number and street name).  150 char max
					'billtocity'=>$order->billing_city, 			// Bill to city.  45 char max
					'billtostate'=>$order->billing_state, 			// Bill to state.  
					'billtozip'=>$order->billing_postcode, 			// Account holder's 5 to 9 digit postal code.  9 char max.  No dashes, spaces, or non-numeric characters
					'billtocountry'=>$order->billing_country, 		// Bill to Country.  3 letter country code.
					'origid'=>'', 				// Required by some transaction types.  ID of the original transaction referenced.  The PNREF parameter returns this ID, and it appears as the Transaction ID in PayPal Manager reports.  
					'custref'=>'', 				// 
					'custcode'=>'', 			// 
					'custip'=>$this->get_user_ip(), 				// 
					'invnum'=>str_replace("#","",$order->get_order_number()), 				// 
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
					
			/* Send Item details */
            $item_loop = 0;
            if(sizeof($order->get_items()) > 0)
			{
                $ITEMAMT = 0;
                foreach($order->get_items() as $item)
				{
                    $item['name'] = html_entity_decode($item['name'], ENT_NOQUOTES, 'UTF-8');
                    $_product = $order->get_product_from_item($item);
                    if($item['qty'])
					{
                        $sku = $_product->get_sku();
                        if ($_product->product_type=='variation')
						{
                            if (empty($sku))
							{
                                $sku = $_product->parent->get_sku();
                            }
                            $item_meta = new WC_Order_Item_Meta( $item['item_meta'] );
                            $meta = $item_meta->display(true, true);
                            if (!empty($meta))
							{
                                $item['name'] .= " - ".str_replace(", \n", " - ",$meta);
                            }
                        }
                        if ( get_option( 'woocommerce_prices_include_tax' ) == 'yes' )
						{
                            $product_price = $order->get_item_subtotal( $item, true, false );
                        }
						else
						{
                            $product_price = $order->get_item_subtotal( $item, false, true );
                        }

                        $PayPalRequestData['L_NUMBER' . $item_loop ] = $sku;
                        $PayPalRequestData['L_NAME' . $item_loop ] = $item['name'];
                        $PayPalRequestData['L_COST' . $item_loop ] = $product_price;
                        $PayPalRequestData['L_QTY' . $item_loop ]  = $item['qty'];
                        if ($sku)
						{
                            $PayPalRequestData[ 'L_SKU' . $item_loop ] = $sku;
						}
						$ITEMAMT += $product_price * $item['qty'];
                        $item_loop++;
                    }
                }


                //Cart Discount
                if ( $order->get_cart_discount()>0 )
				{
                    foreach ( WC()->cart->get_coupons( 'cart' ) as $code => $coupon )
					{

                        $PayPalRequestData['L_NUMBER' . $item_loop ]	= $code;
                        $PayPalRequestData['L_NAME' . $item_loop ]		= 'Cart Discount';
                        $PayPalRequestData['L_AMT' . $item_loop ]		= '-' . WC()->cart->coupon_discount_amounts[ $code ];
                        $PayPalRequestData['L_QTY' . $item_loop ]		= 1;
                        $item_loop++;
                    }
                    $ITEMAMT = $ITEMAMT - $order->get_cart_discount();
                }

                //Order Discount
                if ( $order->get_order_discount()>0 )
				{
                    foreach ( WC()->cart->get_coupons( 'order' ) as $code => $coupon )
					{
                        $PayPalRequestData['L_NUMBER' . $item_loop ]	= $code;
                        $PayPalRequestData['L_NAME' . $item_loop ]		= 'Order Discount';
                        $PayPalRequestData['L_AMT' . $item_loop ]		= '-' . WC()->cart->coupon_discount_amounts[ $code ];
                        $PayPalRequestData['L_QTY' . $item_loop ]		= 1;
                        $item_loop++;
                    }
                    $ITEMAMT = $ITEMAMT - $order->get_order_discount();
                }

                if( get_option( 'woocommerce_prices_include_tax' ) == 'yes' )
				{
                    $shipping 		= $order->get_total_shipping() + $order->get_shipping_tax();
                    $tax			= 0;
                }
				else
				{
                    $shipping 		= $order->get_total_shipping();
                    $tax 			= $order->get_total_tax();
                }

                //tax
                if($tax>0)
				{
                    $PayPalRequestData['TAXAMT'] = $tax;
                }

                // Shipping
                if($shipping > 0)
				{
                    $PayPalRequestData['FREIGHTAMT'] = $shipping;
                }
            }
			
			/**
			 * Add custom Woo cart fees as line items
			 */
			foreach ( WC()->cart->get_fees() as $fee )
			{
				$PayPalRequestData['L_NUMBER' . $item_loop ]	= $fee->id;
				$PayPalRequestData['L_NAME' . $item_loop ]		= $fee->name;
				$PayPalRequestData['L_AMT' . $item_loop ]		= number_format($fee->amount,2,'.','');
				$PayPalRequestData['L_QTY' . $item_loop ]		= 1;
				$item_loop++;
				
				$ITEMAMT += $fee->amount*$Item['qty'];
			}
			
			$PayPalRequestData['ITEMAMT'] = number_format($ITEMAMT,2,'.','');
			
			/**
			 * Woo's original extension wasn't sending the request with 
			 * character count like it's supposed to.  This was added
			 * to fix that, but now that we're using my library it's
			 * already handled correctly so this won't be necessary.
			 */
            /*foreach ($post_data as $key=>$value){
                $send_data[]= $key."[".strlen($value)."]=$value";
            }
            $send_data = implode("&", $send_data);*/
			
			/**
			 * Pass data to to the class and store the $PayPalResult
			 */
			$PayPalResult = $PayPal->ProcessTransaction($PayPalRequestData);
			
			/**
			 * Log results
			 */
			if($this->debug)
			{
				$this->add_log('PayFlow Endpoint: '.$PayPal->APIEndPoint);
            	$this->add_log(print_r($PayPalResult,true));
			}
			
			/**
			 * Error check
			 */
			if(empty($PayPalResult['RAWRESPONSE']))
			{
                throw new Exception(__('Empty PayPal response.', 'paypal-for-woocommerce'));
			}
			
			/** 
			 * More logs
			 */
			if($this->debug)
			{
				$this->add_log(add_query_arg('key',$order->order_key,add_query_arg('order',$order->id,get_permalink(woocommerce_get_page_id('thanks')))));
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
                    $order->add_order_note(sprintf(__('PayPal Pro payment completed (PNREF: %s)','paypal-for-woocommerce'),$PayPalResult['PNREF']));
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
                $order->payment_complete();

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
                	wc_add_notice( __( 'Payment error:', 'paypal-for-woocommerce' ) . ' ' . $PayPalResult['RESULT'].'-'.$PayPalResult['RESPMSG'], "error" );
				}
				else
				{
                	wc_add_notice( __( 'Payment error:', 'paypal-for-woocommerce' ) . ' There was a problem processing your payment.  Please try another method.', "error" );
				}
				
				// Notice admin if has any issue from PayPal
				if($this->error_email_notify)
				{
					$admin_email = get_option("admin_email");
					$message .= __( "PayFlow API call failed." , "paypal-for-woocommerce" )."\n\n";
					$message .= __( 'Error Code: ' ,'paypal-for-woocommerce' ) . $PayPalResult['RESULT'] ."\n";
					$message .= __( 'Detailed Error Message: ' , 'paypal-for-woocommerce') . $PayPalResult['RESPMSG'];
					$message .= isset($PayPalResult['PREFPSMSG']) && $PayPalResult['PREFPSMSG'] != '' ? ' - ' . $PayPalResult['PREFPSMSG'] ."\n" : "\n";
	
					wp_mail($admin_email, "PayPal Pro Error Notification",$message);
				}
				
                return;

            }
		}
		catch(Exception $e)
		{
            wc_add_notice( __('Connection error:', 'paypal-for-woocommerce' ) . ': "' . $e->getMessage() . '"', "error");
            return;
        }	
	}

	/**
     * Payment form on checkout page
     */
	function payment_fields() {

		if ( $this->description ) {
			echo '<p>';
			if ( $this->testmode == 'yes' )
				echo __('TEST MODE/SANDBOX ENABLED', 'paypal-for-woocommerce') . ' ';
			echo $this->description;
			echo '</p>';
		}
		?>
        <style type="text/css">
            #paypal_pro_payflow_card_type_image {
                background: url(<?php echo $this->settings['cart_icon']; ?>) no-repeat 32px 0;
            }
        </style>
		<fieldset class="paypal_pro_credit_card_form">
			<p class="form-row form-row-wide validate-required paypal_pro_payflow_card_number_wrap">
				<label for="paypal_pro_payflow_card_number"><?php _e( "Card number", "wc_paypal_pro" ) ?></label>
				<input type="text" class="input-text" name="paypal_pro_payflow_card_number" id="paypal_pro_payflow_card_number" pattern="[0-9]{12,19}" />
				<span id="paypal_pro_payflow_card_type_image"></span>
			</p>
			<p class="form-row form-row-first validate-required">
				<label for="paypal_pro_payflow_card_expiration"><?php _e( "Expiry date <small>(MMYY)</small>", "wc_paypal_pro" ) ?></label>
				<input type="text" class="input-text" placeholder="MMYY" name="paypal_pro_payflow_card_expiration" id="paypal_pro_payflow_card_expiration" size="4" maxlength="4" max="1299" min="0100" pattern="[0-9]+" />
			</p>
			<p class="form-row form-row-last validate-required">
				<label for="paypal_pro_payflow_card_csc"><?php _e( "Card security code", "wc_paypal_pro" ) ?></label>
				<input type="text" class="input-text" id="paypal_pro_payflow_card_csc" name="paypal_pro_payflow_card_csc" maxlength="4" size="4" pattern="[0-9]+" />
			</p>
			<div class="clear"></div>
			<?php /*<p class="form-row form-row-wide">
				<label for="paypal_pro_payflow_card_type"><?php _e( "Card type", 'paypal-for-woocommerce' ) ?></label>
				<select id="paypal_pro_payflow_card_type" name="paypal_pro_payflow_card_type" class="woocommerce-select">
					<?php foreach ( $available_cards as $card => $label ) : ?>
								<option value="<?php echo $card ?>"><?php echo $label; ?></options>
					<?php endforeach; ?>
					<option value="other"><?php _e( 'Other', 'woocommerce' ); ?></options>
				</select>
			</p>*/ ?>
		</fieldset>
		<?php

        wc_enqueue_js( "
			/*jQuery('body').bind('updated_checkout', function() {
				jQuery('#paypal_pro_payflow_card_type').parent().hide(); // Use JS detection if JS enabled
			});*/

			jQuery('form.checkout, #order_review').on( 'keyup change blur', '#paypal_pro_payflow_card_number', function() {
				var csc = jQuery('#paypal_pro_payflow_card_csc').parent();
				var card_number = jQuery('#paypal_pro_payflow_card_number').val();

				jQuery('#paypal_pro_payflow_card_type_image').attr('class', '');

				if ( is_valid_card( card_number ) ) {

					var card_type = get_card_type( card_number );

					if ( card_type ) {
						jQuery('#paypal_pro_payflow_card_type_image').addClass( card_type );

						if ( card_type == 'visa' || card_type == 'amex' || card_type == 'discover' || card_type == 'mastercard' ) {
							csc.show();
						} else {
							csc.hide();
						}

						//jQuery('#paypal_pro_payflow_card_type').val(card_type);
					} else {
						//jQuery('#paypal_pro_payflow_card_type').val('other');
					}

					jQuery('#paypal_pro_payflow_card_number').parent().addClass('woocommerce-validated').removeClass('woocommerce-invalid');
				} else {
					jQuery('#paypal_pro_payflow_card_number').parent().removeClass('woocommerce-validated').addClass('woocommerce-invalid');
				}
			}).change();
		" );
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
}