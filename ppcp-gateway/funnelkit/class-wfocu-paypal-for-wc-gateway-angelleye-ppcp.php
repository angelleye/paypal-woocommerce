<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WFOCU_Paypal_For_WC_Gateway_AngellEYE_PPCP
 *
 */
if(class_exists("WFOCU_Paypal_For_WC_Gateway_AngellEYE_PPCP")) {
	return;
}
class WFOCU_Paypal_For_WC_Gateway_AngellEYE_PPCP extends WFOCU_Gateway {
	public $key = 'angelleye_ppcp';
	public $token = false;
	public $is_sandbox;
    public $api_request;
    public $paymentaction;
    public $setting_obj;
	protected static $ins = null;
	protected $paypal_order_id = null;

	/**
	 * WFOCU_Paypal_For_WC_Gateway_AngellEYE_PPCP constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->angelleye_ppcp_load_class();
		$this->is_sandbox = 'yes' === $this->setting_obj->get('testmode', 'no');
        $this->paymentaction = $this->setting_obj->get('paymentaction', 'capture');
        if ($this->is_sandbox) {
            $this->merchant_id = $this->setting_obj->get('sandbox_merchant_id', '');
        } else {
            $this->merchant_id = $this->setting_obj->get('live_merchant_id', '');
        }
        $this->invoice_prefix = $this->setting_obj->get('invoice_prefix', 'WC-PPCP');
                
		add_filter( 'wfocu_allow_ajax_actions_for_charge_setup', array( $this, 'allow_action' ) );
		add_action( 'wc_ajax_angelleye_wfocu_front_handle_paypal_payments', array($this, 'angelleye_handle_paypal_payments') );
		add_action( 'woocommerce_api_wfocu_angelleye_paypal_payments', array($this, 'angelleye_capture_upsell_order') );
        add_action('wp_enqueue_scripts', array($this, 'wfocu_angelleye_ppcp_frontend_scripts'));

		$this->refund_supported = true;
	}

	/**
	 * @return null|WFOCU_Paypal_For_WC_Gateway_Express_Checkout
	 */
	public static function get_instance() {
		if ( null == self::$ins ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	public function angelleye_ppcp_load_class() {
        try {
            if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-ppcp-angelleye-settings.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Request')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-request.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Log')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-log.php';
            }
            $this->api_log = AngellEYE_PayPal_PPCP_Log::instance();
            $this->api_request = AngellEYE_PayPal_PPCP_Request::instance();
            $this->setting_obj = WC_Gateway_PPCP_AngellEYE_Settings::instance();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    // Enable to run without the payment token
	public function is_run_without_token() {
		return true;
	}

	public function wfocu_angelleye_ppcp_frontend_scripts() {
		$order = WFOCU_Core()->data->get_current_order();

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		if ( $this->get_key() !== $order->get_payment_method() ) {
			return;
		}
		if ( ! $this->is_enabled() ) {
			return;
		}

		wp_enqueue_script("angelleye-ppcp-funnelkit", PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/funnelkit/js/funnelkit-frontend.js', array('jquery'), VERSION_PFW, true);

	}

	
	public function allow_action($actions) {
		array_push( $actions, 'angelleye_wfocu_front_handle_paypal_payments' );

		return $actions;
	}

	/**
	 * Process paypal payment order
	 */
	public function angelleye_handle_paypal_payments() {
		$this->process_client_order();
	}

	/**
	 * Process order request
	 */
	public function process_client_order() {
		$get_current_offer      = WFOCU_Core()->data->get( 'current_offer' );
		$get_current_offer_meta = WFOCU_Core()->offers->get_offer_meta( $get_current_offer );
		WFOCU_Core()->data->set( '_offer_result', true );
		$posted_data = WFOCU_Core()->process_offer->parse_posted_data( $_POST );
		$ppcp_data = $this->get_ppcp_meta();

		if ( true === WFOCU_AJAX_Controller::validate_charge_request( $posted_data ) ) {

			WFOCU_Core()->process_offer->execute( $get_current_offer_meta );
			$get_order = WFOCU_Core()->data->get_parent_order();

			$offer_package = WFOCU_Core()->data->get( '_upsell_package' );
			WFOCU_Core()->data->set( 'upsell_package', $offer_package, 'paypal' );
			WFOCU_Core()->data->save( 'paypal' );
			WFOCU_Core()->data->save();

			
			$data = array(
				'intent'              => $ppcp_data['intent'],
				'purchase_units'      => $this->get_purchase_units( $get_order, $offer_package, $ppcp_data ),
				'application_context' => array(
					'user_action'  => 'CONTINUE',
					'landing_page' => 'NO_PREFERENCE',
					'brand_name'   => html_entity_decode( get_bloginfo( 'name' ), ENT_NOQUOTES, 'UTF-8' ),
					'return_url'   => add_query_arg( array( 'wfocu-si' => WFOCU_Core()->data->get_transient_key() ), WC()->api_request_url( 'wfocu_angelleye_paypal_payments' ) ),
					'cancel_url'   => add_query_arg( array( 'wfocu-si' => WFOCU_Core()->data->get_transient_key() ), WFOCU_Core()->public->get_the_upsell_url( WFOCU_Core()->data->get_current_offer() ) ),

				),
				'payment_method'      => array(
					'payee_preferred' => 'UNRESTRICTED',
					'payer_selected'  => 'PAYPAL',
				),
				'payment_instruction' => array(
					'disbursement_mode' => 'INSTANT',

				),

			);
			$payment_env = $get_order->get_meta('_enviorment');
			WFOCU_Core()->log->log( "Order: #" . $get_order->get_id() . " paypal args" . print_r( $data, true ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			$arguments   = apply_filters( 'wfocu_ppcp_gateway_process_client_order_api_args', array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type'  		=> 'application/json',
					'Authorization' 		=> '',
					"prefer" 				=> "return=representation",
					'PayPal-Request-Id' 	=> $this->generate_request_id(),
					'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion($payment_env),
				),
				'body'    => $data,
			), $get_order, $posted_data, $offer_package );

			$url = $this->get_api_base( $payment_env ) . 'v2/checkout/orders';
			$ppcp_resp = $this->api_request->request($url, $arguments, 'create_order');
			
			if ( !isset($ppcp_resp['id']) || empty($ppcp_resp['id']) ) {

				$data = WFOCU_Core()->process_offer->_handle_upsell_charge( false );

				$json_response = array(
					'status'       => false,
					'redirect_url' => $data['redirect_url'],
				);


				WFOCU_Core()->log->log( 'Order #' . WFOCU_WC_Compatibility::get_order_id( $get_order ) . ': Unable to create paypal Order refer error below' . print_r( $ppcp_resp, true ) );  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

				wp_send_json( $json_response );

			} else {

				/*
				 * The call to Orders API to create or initiate a charge using a PayPal account as the payment source results in a PAYER_ACTION_REQUIRED contingency.
				 * Once the buyer has identified their PayPal account, authenticated, and been redirected
				 */
				if (  'CREATED' === $ppcp_resp['status'] || 'PAYER_ACTION_REQUIRED' === $ppcp_resp['status'] ) {

					$approve_link = $ppcp_resp['links'][1]['href'];

					// Update Order Created ID (PayPal Order ID) in the order.
					$get_order->update_meta_data( 'wfocu_ppcp_order_current', $ppcp_resp['id'] );
					$get_order->save();

					WFOCU_Core()->log->log( 'Order #' . WFOCU_WC_Compatibility::get_order_id( $get_order ) . ': PayPal Order successfully created' );  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

					$json_response = array(
						'status'       => true,
						'redirect_url' => $approve_link,
					);

				} else {
					$data = WFOCU_Core()->process_offer->_handle_upsell_charge( false );

					$json_response = array(
						'status'       => false,
						'redirect_url' => $data['redirect_url'],
					);


					WFOCU_Core()->log->log( 'Order #' . WFOCU_WC_Compatibility::get_order_id( $get_order ) . ': Unable to create paypal Order refer error below' . print_r( $ppcp_resp, true ) );  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

					wp_send_json( $json_response );
				}
			}

			wp_send_json( $json_response );
			die();
		}
	}

	/**
	 * Get paypal settings configuration
	 */
	public function get_ppcp_meta() {
        $this->paymentaction = apply_filters('angelleye_ppcp_paymentaction', $this->paymentaction, null);
        return array(
            'environment' => ($this->is_sandbox) ? 'sandbox' : '',
            'intent' => ($this->paymentaction === 'capture') ? 'CAPTURE' : 'AUTHORIZE',
            'merchant_id' => $this->merchant_id,
            'invoice_prefix' => $this->invoice_prefix,
        );
    }

    /**
     * Create purchase units data
     */
    public function get_purchase_units($order, $offer_package, $args) {
    	$invoice_id = $ppcp_data['invoice_prefix'] . '-wfocu-' . $this->get_order_number( $order );
    	$total_amount = $offer_package['total'];
        $purchase_unit = array(
            'reference_id' => 'default',
            'amount' => array(
                'currency_code' => $order->get_currency(),
                'value' => (string) $this->round( $total_amount ),
                'breakdown' => $this->get_item_breakdown( $order, $offer_package ),
            ),
            'description' => __('One Time Offer - ' . $order->get_id(), 'upstroke-woocommerce-one-click-upsell-paypal-angell-eye'), // phpcs:ignore
            'items' => $this->add_offer_item_data($order, $offer_package),
            'payee' => array(
                'merchant_id' => $ppcp_data['merchant_id']
            ),
            'shipping' => array(
                'name' => array(
                    'full_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                ),
            ),
            'custom_id' => apply_filters('angelleye_ppcp_custom_id', $invoice_id, $order),
            'invoice_id' => $invoice_id
        );
        return array($purchase_unit);
    }

    /**
     * Create item breakdown array
     */
    public function get_item_breakdown($order, $package) {
    	$breakdown      = array();
		$order_subtotal = 0.00;
		
		// Get order items total
		foreach ( $package['products'] as $item ) {

			$order_subtotal += $item['args']['total'];
		}
		
		$breakdown['item_total'] = array(
			'currency_code' => $order->get_currency(),
			'value'         => (string) $this->round( $order_subtotal ),
		);
		
		$breakdown['tax_total']  = array(
			'currency_code' => $order->get_currency(),
			'value'         => ( isset( $package['taxes'] ) ) ? ( (string) $this->validate_tax( $package ) ) : '0',
		);

		if ( ( isset( $package['shipping'] ) && isset( $package['shipping']['diff'] ) ) ) {
			
			if ( 0 <= $package['shipping']['diff']['cost'] ) {
				$shipping = ( isset( $package['shipping'] ) && isset( $package['shipping']['diff'] ) ) ? ( (string) $package['shipping']['diff']['cost'] ) : 0;

				if ( ! empty( $shipping ) && 0 < intval( $shipping ) ) {
					$breakdown['shipping'] = array(
						'currency_code' => $order->get_currency(),
						'value'         => (string) $this->round( $shipping ),
					);
				}

			} else {
				$shipping = ( isset( $package['shipping'] ) && isset( $package['shipping']['diff'] ) ) ? ( (string) $package['shipping']['diff']['cost'] ) : 0;


				$breakdown['shipping_discount'] = array(
					'currency_code' => $order->get_currency(),
					'value'         => (string) abs($this->round( $shipping )),
				);
				$breakdown['shipping']          = array(
					'currency_code' => $order->get_currency(),
					'value'         => '0.00',
				);

			}
		}


		return $breakdown;
    }

    /**
     * Add offer items data
     */
    public function add_offer_item_data($order, $package) {
    	
    	$order_items = [];
		foreach ( $package['products'] as $item ) {

			$product = $item['data'];
			$title   = $product->get_title();
			if ( strlen( $title ) > 127 ) {
				$title = substr( $title, 0, 124 ) . '...';
			}
			$order_items[] = array(
				'name'        => $title,
				'unit_amount' => array(
					'currency_code' => $order->get_currency(),
					'value'         => (string) $this->round( $item['price'] ),
				),
				'quantity'    => 1,
				'description' => $this->get_item_description( $product ),
			);

		};
        return $order_items;
    }

    /**
     * Round a float
     */
    private function round( $number, $precision = 2 ) {
		return round( (float) $number, $precision );
	}

	/**
	 * validate tax amount some time total of items and tax amount mismatch
	 */
	public function validate_tax( $offer_package ) {
		$tax = $this->round( $offer_package['taxes'] );

		$total_amount = (float) $offer_package['total'];
		$shipping = ( isset( $offer_package['shipping'] ) && isset( $offer_package['shipping']['diff'] ) ) ? ( (string) $offer_package['shipping']['diff']['cost'] ) : 0;

		$item_total   = 0;
		foreach ( $offer_package['products'] as $item ) {
			$item_total += $this->round( $item['price'] );

		};
		if ( $total_amount === ( $item_total + $tax ) ) {
			return $tax;
		}

		if ( $total_amount !== ( $item_total + $tax ) ) {
			$tax += $total_amount - ( $item_total + $tax + $this->round( $shipping ) );
		}
		if ( $tax < 0 ) {
			return $this->round( 0 );
		}

		return $this->round( $tax );
	}

	/**
	 * Helper method to return the item description, which is composed of item
	 * meta flattened into a comma-separated string, if available.
	 *
	 * The description is automatically truncated to the 127 char limit.
	 */
	private function get_item_description( $product_or_str ) {

		if ( is_string( $product_or_str ) ) {
			$str = $product_or_str;
		} else {
			$str = $product_or_str->get_short_description();
		}
		$item_desc = wp_strip_all_tags( wp_specialchars_decode( wp_staticize_emoji( $str ) ) );
		$item_desc = preg_replace( '/[\x00-\x1F\x80-\xFF]/', '', $item_desc );
		$item_desc = str_replace( "\n", ', ', rtrim( $item_desc ) );
		if ( strlen( $item_desc ) > 127 ) {
			$item_desc = substr( $item_desc, 0, 124 ) . '...';
		}

		return html_entity_decode( $item_desc, ENT_NOQUOTES, 'UTF-8' );

	}

	
	public function get_api_base( $mode = '' ) {
		$live_url    = 'https://api-m.paypal.com/';
		$sandbox_url = 'https://api-m.sandbox.paypal.com/';

		if ( empty( $mode ) ) {
			return ($this->is_sandbox == 'yes') ? $sandbox_url : $live_url;
		} else {
			return ( 'live' === $mode ) ? $live_url : $sandbox_url;
		}
	}

	public function angelleye_capture_upsell_order() {
		/**
		 * Setting up necessary data for this api call
		 */
		add_filter( 'wfocu_valid_state_for_data_setup', '__return_true' );
		WFOCU_Core()->template_loader->set_offer_id( WFOCU_Core()->data->get_current_offer() );

		WFOCU_Core()->template_loader->maybe_setup_offer();

		$get_order       = WFOCU_Core()->data->get_parent_order();
		$paypal_order_id = $get_order->get_meta( 'wfocu_ppcp_order_current' );
		$environment     = $get_order->get_meta( '_enviorment' );
		$capture_args    = array(
			'method'  => 'POST',
			'headers' => array(
				'Authorization'         => '',
				'Content-Type'          => 'application/json',
				'prefer' 				=> "return=representation",
				'PayPal-Request-Id' 	=> $this->generate_request_id(),
				'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion($environment),
			),
		);


		$capture_url = $this->get_api_base( $environment ) . 'v2/checkout/orders/' . $paypal_order_id . '/capture';

		$ppcp_resp = $this->api_request->request($capture_url, $capture_args, 'capture_order');

		$existing_package = WFOCU_Core()->data->get( 'upsell_package', '', 'paypal' );
		
		WFOCU_Core()->data->set( '_upsell_package', $existing_package );

		if ( !isset($ppcp_resp['id']) || empty($ppcp_resp['id']) ) {

			$data = WFOCU_Core()->process_offer->_handle_upsell_charge( false );
			WFOCU_Core()->log->log( 'Order #' . WFOCU_WC_Compatibility::get_order_id( $get_order ) . ': Unable to capture paypal Order refer error below' . print_r( $ppcp_resp, true ) );  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

		} else {

			
			if ( isset( $ppcp_resp['status'] ) && 'COMPLETED' === $ppcp_resp['status'] ) {
				if( isset( $ppcp_resp['payment_source']['paypal']['attributes']['vault']['id'] ) && isset( $ppcp_resp['payment_source']['paypal']['attributes']['vault']['status'] ) && 'CREATED' === $ppcp_resp['payment_source']['paypal']['attributes']['vault']['status'] ) {
					/*
					 * Successfully created vault token
					 * This token can be used in subsequent transactions to charge the buyer's PayPal account, than requiring them to identify and log in for every purchase.
					 */
					$txn_id = $ppcp_resp['payment_source']['paypal']['attributes']['vault']['id'];

					update_post_meta( WFOCU_WC_Compatibility::get_order_id( $get_order ), 'wfocu_ppcp_renewal_payment_token', $txn_id );
					WFOCU_Core()->log->log( 'Order #' . WFOCU_WC_Compatibility::get_order_id( $get_order ) . ': vault token created' );  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r


				} else {
					$txn_id = $ppcp_resp['purchase_units'][0]['payments']['captures'][0]['id'];
				}

				WFOCU_Core()->data->set( '_transaction_id', $txn_id );
				add_action( 'wfocu_db_event_row_created_' . WFOCU_DB_Track::OFFER_ACCEPTED_ACTION_ID, array( $this, 'add_order_id_as_meta' ) );
				add_action( 'wfocu_offer_new_order_created_'.$this->get_key(), array( $this, 'add_paypal_meta_in_new_order' ), 10, 2 );

				$this->payal_order_id = $paypal_order_id;
				$data                 = WFOCU_Core()->process_offer->_handle_upsell_charge( true );


			} else if ( isset( $ppcp_resp['details'] ) && is_array( $ppcp_resp['details'] ) && ( 'ORDER_ALREADY_CAPTURED' === $ppcp_resp['details'][0]['issue'] ) ) {
				$get_offer            = WFOCU_Core()->offers->get_the_next_offer();
				$data                 = [];
				$data['redirect_url'] = WFOCU_Core()->public->get_the_upsell_url( $get_offer );

			} else {
				$data = WFOCU_Core()->process_offer->_handle_upsell_charge( false );
				WFOCU_Core()->log->log( 'Order #' . WFOCU_WC_Compatibility::get_order_id( $get_order ) . ': Unable to capture paypal Order refer error below' . print_r( $ppcp_resp, true ) );  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

			}
		}

		wp_redirect( $data['redirect_url'] );
		exit;

	}

	public function add_order_id_as_meta( $event ) {
		if ( ! empty( $this->payal_order_id ) ) {
			WFOCU_Core()->track->add_meta( $event, '_paypal_order_id', $this->payal_order_id );
		}
	}

	public function add_paypal_meta_in_new_order( $get_order ) {
		if ( ! empty( $this->payal_order_id ) ) {
			$get_order->update_meta_data( '_ppcp_paypal_order_id', $this->payal_order_id );
			$get_order->update_meta_data( '_ppcp_paypal_intent', 'CAPTURE' );
			$get_order->save();
		}
	}

	/**
	 * Handling refund offer request
	 *
	 * @param $order
	 *
	 * @return bool
	 */
	public function process_refund_offer( $order ) {

		$refund_data = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$order_id    = WFOCU_WC_Compatibility::get_order_id( $order );
		$amount      = isset( $refund_data['amt'] ) ? $refund_data['amt'] : '';
		$event_id    = isset( $refund_data['event_id'] ) ? $refund_data['event_id'] : '';
		$txn_id      = isset( $refund_data['txn_id'] ) ? $refund_data['txn_id'] : '';
		$response    = false;

		if ( ! empty( $event_id ) && ! empty( $order_id ) && ! empty( $txn_id ) ) {
			if ( ! is_null( $amount ) ) {
				$environment = $order->get_meta( '_enviorment' );
				$api_url     = $this->get_api_base( $environment ) . 'v2/payments/captures/' . $txn_id . '/refund';


				$data      = array(
					'amount' => array(
						'currency_code' => $order->get_currency(),
						'value'         => (string) $this->round( $amount ),
					),
				);
				$arguments = array(
					'method'  => 'POST',
					'headers' => array(
						'Authorization' 		=> '',
						'Content-Type'  		=> 'application/json',
						"prefer" 				=> "return=representation",
						'PayPal-Request-Id' 	=> $this->generate_request_id(),
						'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion($environment)
					),
					'body'    => wp_json_encode( $data ),
				);
				
				$resp = $this->api_request->request($api_url, $arguments, 'refund_order');
				if ( !isset($resp['status']) || !$resp['status'] == "COMPLETED" ) {
					return false;
				} else {
					return $resp['id'];
				}
			}
		}

		return $response;
	}

	public function generate_request_id() {
        static $pid = -1;
        static $addr = -1;

        if ($pid == -1) {
            $pid = uniqid('angelleye-pfw', true);
        }

        if ($addr == -1) {
            if (array_key_exists('SERVER_ADDR', $_SERVER)) {
                $addr = ip2long($_SERVER['SERVER_ADDR']);
            } else {
                $addr = php_uname('n');
            }
        }
        return $addr . $pid . $_SERVER['REQUEST_TIME'] . mt_rand(0, 0xffff);
    }

    public function angelleye_ppcp_paypalauthassertion($payment_env) {

        $temp = array(

            "alg" => "none"

        );
        $partner_client_id = ($payment_env == 'sandbox') ? PAYPAL_PPCP_SANDBOX_PARTNER_CLIENT_ID : PAYPAL_PPCP_PARTNER_CLIENT_ID;
		$merchant_id = ($payment_env == 'sandbox') ? $this->setting_obj->get('sandbox_merchant_id', '') : $this->setting_obj->get('live_merchant_id', '');
        $returnData = base64_encode(json_encode($temp)) . '.';

        $temp = array(

            "iss" => $partner_client_id,

            "payer_id" => $merchant_id

        );

        $returnData .= base64_encode(json_encode($temp)) . '.';

        return $returnData;

    }
}