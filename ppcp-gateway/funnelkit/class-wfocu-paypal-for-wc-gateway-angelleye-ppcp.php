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
    public $payal_order_id;
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

        // Force tokenization if funnel is initiated
        add_filter( "angelleye_ppcp_is_save_payment_method", array( $this, "angelleye_force_token_save" ), 20 );
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
            if (!class_exists('AngellEYE_PayPal_PPCP_Payment')) {
	            include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-payment.php');
	        }
            
            $this->api_log = AngellEYE_PayPal_PPCP_Log::instance();
            $this->api_request = AngellEYE_PayPal_PPCP_Request::instance();
            $this->setting_obj = WC_Gateway_PPCP_AngellEYE_Settings::instance();
            $this->payment_request = AngellEYE_PayPal_PPCP_Payment::instance();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    // Enable to run without the payment token
	public function is_run_without_token() {
		return true;
	}

	public function angelleye_force_token_save($is_enabled) {
		if ( false !== $this->should_tokenize() ) {

			return true;
		}

		return $is_enabled;
	}

	/**
	 * Try and get the payment token saved by the gateway
	 *
	 * @param WC_Order $order
	 *
	 * @return boolean on success false otherwise
	 */
	public function has_token( $order ) {
		if ( !is_a( $variable, 'WC_Order' ) ) {
			return false;
		}
		$get_id      = $order->get_id();
		$this->token = get_post_meta( $get_id, '_payment_tokens_id', true );
		if ( ! empty( $this->token ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Try and get the payment token saved by the gateway
	 *
	 * @param WC_Order $order
	 *
	 * @return boolean on success false otherwise
	 */
	public function get_token( $order ) { 
		$get_id      = $order->get_id();
		$this->token = get_post_meta( $get_id, '_payment_tokens_id', true );
		
		if ( empty($this->token) ) {
            $payment_tokens_list = $this->payment_request->angelleye_ppcp_get_all_payment_tokens();
            $payment_method = $order->get_payment_method();
            if($payment_method == 'angelleye_ppcp') {
            	foreach ($payment_tokens_list as $key => $token) {
            		if(isset($token['payment_source']['paypal']) && !empty($token['payment_source']['paypal'])) {
            			$this->token = $token['id'];
            			break;
            		}
            	}
            }
		}

		if ( ! empty( $this->token ) ) {
			return $this->token;
		}

		return false;

	}

	/**
	 * This function is placed here as a fallback function when JS client side integration fails mysteriosly
	 */
	public function process_charge( $order ) {
		WFOCU_Core()->log->log( 'process charge paypal advanced credit card' );
		$is_successful = false;
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
			$data = array();
			$data['timeout'] = 30;
			$data['intent'] = $ppcp_data['intent'];
			$data['purchase_units'] = $this->get_purchase_units( $get_order, $offer_package, $ppcp_data );
			$data['application_context'] = array(
				'user_action'  => 'CONTINUE',
				'landing_page' => 'NO_PREFERENCE',
				'brand_name'   => html_entity_decode( get_bloginfo( 'name' ), ENT_NOQUOTES, 'UTF-8' ),
				'return_url'   => add_query_arg( array( 'wfocu-si' => WFOCU_Core()->data->get_transient_key() ), WC()->api_request_url( 'wfocu_angelleye_paypal_payments' ) ),
				'cancel_url'   => add_query_arg( array( 'wfocu-si' => WFOCU_Core()->data->get_transient_key() ), WFOCU_Core()->public->get_the_upsell_url( WFOCU_Core()->data->get_current_offer() ) ),
			);
			$data['payment_instruction'] = array(
				'disbursement_mode' => 'INSTANT',
			);
			$data['payment_method'] = array(
				'payee_preferred' => 'UNRESTRICTED',
				'payer_selected'  => 'PAYPAL',
			);
			$payment_token = $this->get_token( $get_order );
			$token_id = angelleye_ppcp_get_token_id_by_token($payment_token);
			$data_store = WC_Data_Store::load( 'payment-token' );
			$token_metadata = $data_store->get_metadata( $token_id );
			$data['payment_source'] = array(
				$token_metadata['_angelleye_ppcp_used_payment_method'][0] => array(
					'vault_id' => $payment_token,
				)
			);

			if($token_metadata['_angelleye_ppcp_used_payment_method'][0] === 'card') {
				$data['payment_source'][$token_metadata['_angelleye_ppcp_used_payment_method'][0]]['stored_credential'] = array(
                    'payment_initiator' => 'MERCHANT',
                    'payment_type' => 'UNSCHEDULED',
                    'usage' => 'SUBSEQUENT'
                );
			}
			
			WFOCU_Core()->log->log( "Order: #" . $get_order->get_id() . " paypal args" . print_r( $data, true ) );

			$payment_env = $get_order->get_meta( '_enviorment' );
			$arguments   = apply_filters( 'wfocu_ppcp_gateway_process_client_order_api_args', array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => '',
					"prefer" => "return=representation",
					'PayPal-Request-Id' => $this->generate_request_id(),
					'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion($payment_env),
				),
				'body'    => $data,
			), $get_order, $posted_data, $offer_package );
			
			$url = $this->get_api_base( $payment_env ) . 'v2/checkout/orders';

			$ppcp_resp = $this->api_request->request($url, $arguments, 'create_order');
			WFOCU_Core()->log->log( "Order: #" . $get_order->get_id() . " paypal response" . print_r( $ppcp_resp, true ) );
			
			if ( !isset($ppcp_resp['id']) || empty($ppcp_resp['id']) ) {

				$data = WFOCU_Core()->process_offer->_handle_upsell_charge( false );
				$is_successful = false;
				WFOCU_Core()->log->log( 'Order #' . WFOCU_WC_Compatibility::get_order_id( $get_order ) . ': Unable to create paypal Order refer error below' . print_r( $ppcp_resp, true ) );  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

			} else {

				angelleye_ppcp_update_post_meta($order, '_paypal_order_id', $ppcp_resp['id']);
				$this->payal_order_id = $ppcp_resp['id'];

				if ( 'COMPLETED' == $ppcp_resp['status'] ) {

					// Update Order Created ID (PayPal Order ID) in the order.
					$get_order->update_meta_data( 'wfocu_ppcp_order_current', $ppcp_resp['id'] );
					$get_order->save();

					WFOCU_Core()->log->log( 'Order #' . WFOCU_WC_Compatibility::get_order_id( $get_order ) . ': PayPal Order successfully created' );  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
					$transaction_id = $ppcp_resp['purchase_units'][0]['payments']['captures']['id'];
					WFOCU_Core()->data->set( '_transaction_id', $transaction_id );
					$is_successful = true;

				} else {
					
					$is_successful = false;

					WFOCU_Core()->log->log( 'Order #' . WFOCU_WC_Compatibility::get_order_id( $get_order ) . ': Unable to create paypal Order refer error below' . print_r( $ppcp_resp, true ) );  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
				}
			}
			
		} else {
			$is_successful = false;
		}

		add_action( 'wfocu_offer_new_order_created_'.$this->get_key(), array( $this, 'add_paypal_meta_in_new_order' ), 10, 2 );
		return $this->handle_result( $is_successful );
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

    public function add_paypal_meta_in_new_order( $get_order ) {
		if ( ! empty( $this->payal_order_id ) ) {
			$get_order->update_meta_data( '_transaction_id', $this->payal_order_id );
			$get_order->save();
		}
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