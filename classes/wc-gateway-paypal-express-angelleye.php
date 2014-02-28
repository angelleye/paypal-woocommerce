<?php
class WC_Gateway_PayPal_Express_AngellEYE extends WC_Payment_Gateway {
    /**
     * __construct function.
     *
     * @access public
     * @return void
     */
    public function __construct() {
        $this->id                 = 'paypal_express';
        $this->method_title       = __( 'PayPal Express Checkout ', 'wc-paypal-express' );
        $this->method_description = __( 'PayPal Express Checkout is designed to make the checkout experience for buyers using PayPal much more quick and easy than filling out billing and shipping forms.  Customers will be taken directly to PayPal to sign in and authorize the payment, and are then returned back to your store to choose a shipping method, review the final order total, and complete the payment.', 'wc-paypal-express' );
        $this->has_fields         = false;
        // Load the form fields
        $this->init_form_fields();
        // Load the settings.
        $this->init_settings();
        // Get setting values
        $this->enabled                 = $this->settings['enabled'];
        $this->title                   = $this->settings['title'];
        $this->description             = $this->settings['description'];
        $this->api_username            = $this->settings['api_username'];
        $this->api_password            = $this->settings['api_password'];
        $this->api_signature           = $this->settings['api_signature'];
        $this->testmode                = $this->settings['testmode'];
        $this->debug                   = $this->settings['debug'];
        $this->checkout_with_pp_button = $this->settings['checkout_with_pp_button'];
        $this->hide_checkout_button    = $this->settings['hide_checkout_button'];
        $this->show_on_checkout        = $this->settings['show_on_checkout'];
        $this->paypal_account_optional = $this->settings['paypal_account_optional'];
        $this->landing_page            = $this->settings['landing_page'];
        /*
        ' Define the PayPal Redirect URLs.
        ' 	This is the URL that the buyer is first sent to do authorize payment with their paypal account
        ' 	change the URL depending if you are testing on the sandbox or the live PayPal site
        '
        ' For the sandbox, the URL is       https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=
        ' For the live site, the URL is     https://www.paypal.com/webscr&cmd=_express-checkout&token=
        */
        if ( $this->testmode == 'yes' ) {
            $this->API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
            $this->PAYPAL_URL   = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
            $this->api_username            = $this->settings['sandbox_api_username'];
            $this->api_password            = $this->settings['sandbox_api_password'];
            $this->api_signature           = $this->settings['sandbox_api_signature'];
        }
        else {
            $this->API_Endpoint = "https://api-3t.paypal.com/nvp";
            $this->PAYPAL_URL   = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
        }
        $this->version="64";  // PayPal SetExpressCheckout API version
        // Actions
        add_action( 'woocommerce_api_' . strtolower( get_class() ), array( $this, 'paypal_express_checkout' ), 12 );
        add_action( 'woocommerce_receipt_paypal_express', array( $this, 'receipt_page' ) );
        add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        if ( $this->show_on_checkout == 'yes' )
            add_action( 'woocommerce_before_checkout_form', array( $this, 'checkout_message' ), 5 );
        add_action( 'woocommerce_ppe_do_payaction', array($this, 'get_confirm_order'));

    }
    /**
     * Override this method so this gateway does not appear on checkout page
     *
     * @since 1.0.0
     */
    public function get_confirm_order($order){
        $this->confirm_order_id = $order->id;
    }
    function is_available() {
        return false;
    }
    /**
     * Use WooCommerce logger if debug is enabled.
     */
    function add_log( $message ) {
        if ( $this->debug=='yes' ) {
            if ( empty( $this->log ) )
                $this->log = new WC_Logger();
            $this->log->add( 'paypal_express', $message );
        }
    }
    /**
     * Initialize Gateway Settings Form Fields
     */
    function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Enable/Disable', 'wc-paypal-express' ),
                'label' => __( 'Enable PayPal Express', 'wc-paypal-express' ),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'title' => array(
                'title' => __( 'Title', 'wc-paypal-express' ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'wc-paypal-express' ),
                'default' => __( 'PayPal Express', 'wc-paypal-express' )
            ),
            'description' => array(
                'title' => __( 'Description', 'wc-paypal-express' ),
                'type' => 'textarea',
                'description' => __( 'This controls the description which the user sees during checkout.', 'wc-paypal-express' ),
                'default' => __( "Pay via PayPal; you can pay with your credit card if you don't have a PayPal account", 'wc-paypal-express' )
            ),
            'sandbox_api_username' => array(
                'title' => __( 'Sandbox API User Name', 'wc-paypal-express' ),
                'type' => 'text',
                'description' => __( 'You may create sandbox accounts and obtain credentials from your PayPal account profile.', 'wc-paypal-express' ),
                'default' => ''
            ),
            'sandbox_api_password' => array(
                'title' => __( 'Sandbox API Password', 'wc-paypal-express' ),
                'type' => 'password',
                'description' => __( 'You may create sandbox accounts and obtain credentials from your PayPal account profile.', 'wc-paypal-express' ),
                'default' => ''
            ),
            'sandbox_api_signature' => array(
                'title' => __( 'Sandbox API Signature', 'wc-paypal-express' ),
                'type' => 'password',
                'description' => __( 'You may create sandbox accounts and obtain credentials from your PayPal account profile.', 'wc-paypal-express' ),
                'default' => ''
            ),
            'api_username' => array(
                'title' => __( 'Live API User Name', 'wc-paypal-express' ),
                'type' => 'text',
                'description' => __( 'You may obtain your API credentials from your PayPal account profile.', 'wc-paypal-express' ),
                'default' => ''
            ),
            'api_password' => array(
                'title' => __( 'Live API Password', 'wc-paypal-express' ),
                'type' => 'password',
                'description' => __( 'You may obtain your API credentials from your PayPal account profile.', 'wc-paypal-express' ),
                'default' => ''
            ),
            'api_signature' => array(
                'title' => __( 'Live API Signature', 'wc-paypal-express' ),
                'type' => 'password',
                'description' => __( 'You may obtain your API credentials from your PayPal account profile.', 'wc-paypal-express' ),
                'default' => ''
            ),
            'testmode' => array(
                'title' => __( 'PayPal Sandbox', 'wc-paypal-express' ),
                'type' => 'checkbox',
                'label' => __( 'Enable PayPal Sandbox', 'wc-paypal-express' ),
                'default' => 'yes'
            ),
            'debug' => array(
                'title' => __( 'Debug', 'wc-paypal-express' ),
                'type' => 'checkbox',
                'label' => __( 'Enable logging ( <code>woocommerce/logs/paypal_express.txt</code> )', 'wc-paypal-express' ),
                'default' => 'no'
            ),
            'checkout_with_pp_button' => array(
                'title' => __( 'Checkout Button Style', 'wc-paypal-express' ),
                'type' => 'checkbox',
                'label' => __( 'Use "Checkout with PayPal" image button', 'wc-paypal-express' ),
                'default' => 'yes'
            ),
            'hide_checkout_button' => array(
                'title' => __( 'Standard Checkout Button', 'wc-paypal-express' ),
                'type' => 'checkbox',
                'label' => __( 'Hide standard checkout button on cart page', 'wc-paypal-express' ),
                'default' => 'no'
            ),
            'show_on_checkout' => array(
                'title' => __( 'Standard Checkout', 'wc-paypal-express' ),
                'type' => 'checkbox',
                'label' => __( 'Show express checkout button on checkout page', 'wc-paypal-express' ),
                'default' => 'yes'
            ),
            'paypal_account_optional' => array(
                'title' => __( 'PayPal Account Optional', 'wc-paypal-express' ),
                'type' => 'checkbox',
                'label' => __( 'Allow customers to checkout without a PayPal account using their credit card. "PayPal Account Optional" must be turned on in your PayPal account. ', 'wc-paypal-express' ),
                'default' => 'no'
            ),
            'landing_page' => array(
                'title' => __( 'Landing Page', 'wc-paypal-express' ),
                'type' => 'select',
                'description' => __( 'Type of PayPal page to display as default. "PayPal Account Optional" must be checked for this option to be used.' ),
                'options' => array('login' => 'Login',
                    'billing' => 'Billing'),
                'default' => 'login',
            ),
        );
    }
    /**
     *  Checkout Message
     */
    function checkout_message() {
        if ( WC()->cart->total > 0 ) {
            echo '<p class="woocommerce-info info"><a class="paypal_checkout_button" href="' . add_query_arg( 'pp_action', 'expresscheckout', add_query_arg( 'wc-api', get_class(), home_url( '/' ) ) ) . '">';
            echo "<img src='https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif' width='145' height='42' style='width: 145px; height: 42px; ' border='0' align='top' alt='Check out with PayPal'/>";
            echo '</a> ' . apply_filters( 'woocommerce_ppe_checkout_message', __( 'Have a PayPal account?', 'wc-paypal-express' ) ) . '</p>';
        }
    }
    /**
     *  PayPal Express Checkout
     *
     *  Main action function that handles PPE actions:
     *  1. 'expresscheckout' - Initiates the Express Checkout process; called by the checkout button.
     *  2. 'revieworder' - Customer has reviewed the order. Saves shipping info to order.
     *  3. 'payaction' - Customer has pressed "Place Order" on the review page.
     */
    function paypal_express_checkout() {
        if ( isset( $_GET['pp_action'] ) && $_GET['pp_action'] == 'expresscheckout' ) {
            if ( sizeof( WC()->cart->get_cart() ) > 0 ) {

                // The customer has initiated the Express Checkout process with the button on the cart page
                if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) )
                    define( 'WOOCOMMERCE_CHECKOUT', true );
                $this->add_log( 'Start Express Checkout' );
                WC()->cart->calculate_totals();
                $paymentAmount    = WC()->cart->get_total();
                $returnURL        = urlencode( add_query_arg( 'pp_action', 'revieworder', get_permalink( woocommerce_get_page_id( 'review_order' )) ) );
                $cancelURL        = urlencode( WC()->cart->get_cart_url() );
                $resArray         = $this->CallSetExpressCheckout( $paymentAmount, $returnURL, $cancelURL );
                $ack              = strtoupper( $resArray["ACK"] );
                if ( $ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING" ) {
                    $this->add_log( 'Redirecting to PayPal' );
                    $this->RedirectToPayPal( $resArray["TOKEN"] );
                } else {
                    //Display a user friendly Error on the page and log details
                    $ErrorCode         = urldecode( $resArray["L_ERRORCODE0"] );
                    $ErrorShortMsg     = urldecode( $resArray["L_SHORTMESSAGE0"] );
                    $ErrorLongMsg      = urldecode( $resArray["L_LONGMESSAGE0"] );
                    $ErrorSeverityCode = urldecode( $resArray["L_SEVERITYCODE0"] );
                    $this->add_log( 'SetExpressCheckout API call failed. ' );
                    $this->add_log( 'Detailed Error Message: ' . $ErrorLongMsg );
                    $this->add_log( 'Short Error Message: ' . $ErrorShortMsg );
                    $this->add_log( 'Error Code: ' . $ErrorCode );
                    $this->add_log( 'Error Severity Code: ' . $ErrorSeverityCode );
                    // Notice admin if has any issue from Paypal
                    $admin_email = get_option("admin_email");
                    $message="SetExpressCheckout API call failed.\n";
                    $message.='Detailed Error Message: ' . $ErrorLongMsg ."\n";
                    $message.='Short Error Message: ' . $ErrorShortMsg ."\n";
                    $message.='Error Code: ' . $ErrorCode."\n";
                    $message.='Error Severity Code: ' . $ErrorSeverityCode."\n";
                    wp_mail($admin_email, "PayPal Express Checkout Error Notification",$message);
                    wc_add_notice(  sprintf( __( 'Please try a different a different payment method.', 'wc-paypal-express' ) ), 'error' );
                    wp_redirect( get_permalink( wc_get_page_id( 'cart' ) ) );
                    exit;
                }
            }
        } elseif ( isset( $_GET['pp_action'] ) && $_GET['pp_action'] == 'revieworder' ) {
            // The customer has logged into PayPal and approved order.
            // Retrieve the shipping details and present the order for completion.
            if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) )
                define( 'WOOCOMMERCE_CHECKOUT', true );
            $this->add_log( 'Start Review Order' );
            if ( isset( $_GET['token'] ) ) {
                $token = $_GET['token'];
                $this->set_session( 'TOKEN', $token );            }
            if ( isset( $_GET['PayerID'] ) ) {
                $payerID = $_GET['PayerID'];
                $this->set_session( 'PayerID', $payerID );
            }
            $this->add_log( "...Token:" . $this->get_session( 'TOKEN' ) );
            $this->add_log( "...PayerID: " . $this->get_session( 'PayerID' ) );
            $result = $this->CallGetShippingDetails( $this->get_session( 'TOKEN' ) );
            $this->add_log( print_r($result, true) );
            if ( ! empty( $result ) ) {
                if ( isset( $result['SHIPTONAME'] ) ) WC()->customer->shiptoname =  $result['SHIPTONAME'] ;
                if ( isset( $result['SHIPTOSTREET'] ) ) WC()->customer->set_address( $result['SHIPTOSTREET'] );
                if ( isset( $result['SHIPTOCITY'] ) ) WC()->customer->set_city( $result['SHIPTOCITY'] );
                if ( isset( $result['SHIPTOCOUNTRYCODE'] ) ) WC()->customer->set_country( $result['SHIPTOCOUNTRYCODE'] );
                if ( isset( $result['SHIPTOSTATE'] ) ) WC()->customer->set_state( $this->get_state_code( $result['SHIPTOCOUNTRYCODE'], $result['SHIPTOSTATE'] ) );
                if ( isset( $result['SHIPTOZIP'] ) ) WC()->customer->set_postcode( $result['SHIPTOZIP'] );
                if ( isset( $result['SHIPTOCOUNTRYCODE'] ) ) WC()->customer->set_shipping_country( $result['SHIPTOCOUNTRYCODE'] );
                if ( isset( $result['SHIPTOSTATE'] ) ) WC()->customer->set_shipping_state( $this->get_state_code( $result['SHIPTOCOUNTRYCODE'], $result['SHIPTOSTATE'] ) );
                if ( isset( $result['SHIPTOZIP'] ) ) WC()->customer->set_shipping_postcode( $result['SHIPTOZIP'] );
                WC()->cart->calculate_totals();
            } else {
                $this->add_log( "...ERROR: GetShippingDetails returned empty result" );
            }
        } elseif ( isset( $_GET['pp_action'] ) && $_GET['pp_action'] == 'payaction' ) {
            if ( isset( $_POST ) ) {

                // Update customer shipping and payment method to posted method
                $chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

                if ( isset( $_POST['shipping_method'] ) && is_array( $_POST['shipping_method'] ) )
                    foreach ( $_POST['shipping_method'] as $i => $value )
                        $chosen_shipping_methods[ $i ] = wc_clean( $value );

                WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );

                if ( WC()->cart->needs_shipping() ) {
                    // Validate Shipping Methods
                    $packages               = WC()->shipping->get_packages();
                    WC()->checkout()->shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
                }


                $this->add_log( 'Start Pay Action' );
                if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) )
                    define( 'WOOCOMMERCE_CHECKOUT', true );
                WC()->cart->calculate_totals();
                if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '<' ) )
                    $order_id = $this->prepare_order();
                else
                    $order_id = WC()->checkout()->create_order();
                $result = $this->CallGetShippingDetails( $this->get_session( 'TOKEN' ) );
                if ( ! empty( $result ) ) {
                    update_post_meta( $order_id, '_payment_method',   $this->id );
                    update_post_meta( $order_id, '_payment_method_title',  $this->title );
                    update_post_meta( $order_id, '_billing_email',    $result['EMAIL'] );
                    update_post_meta( $order_id, '_shipping_first_name',  $result['SHIPTONAME'] );
                    update_post_meta( $order_id, '_shipping_last_name',  "" );
                    update_post_meta( $order_id, '_shipping_company',   "" );
                    update_post_meta( $order_id, '_shipping_address_1',  $result['SHIPTOSTREET'] );
                    update_post_meta( $order_id, '_shipping_address_2',  ( isset( $result['SHIPTOSTREET2'] ) ) ? $result['SHIPTOSTREET2'] : '' );
                    update_post_meta( $order_id, '_shipping_city',    $result['SHIPTOCITY'] );
                    update_post_meta( $order_id, '_shipping_postcode',   $result['SHIPTOZIP'] );
                    update_post_meta( $order_id, '_shipping_country',   $result['SHIPTOCOUNTRYCODE'] );
                    update_post_meta( $order_id, '_shipping_state',   $this->get_state_code( $result['SHIPTOCOUNTRYCODE'], $result['SHIPTOSTATE'] ) );
                } else {
                    $this->add_log( "...ERROR: GetShippingDetails returned empty result" );
                }
                $this->add_log( '...Order ID: ' . $order_id );
                $order = new WC_Order( $order_id );
                do_action( 'woocommerce_ppe_do_payaction', $order );
                $this->add_log( '...Order Total: ' . $order->order_total );
                $this->add_log( '...Cart Total: '.WC()->cart->get_total() );
                $this->add_log( "...Token:" . $this->get_session( 'TOKEN' ) );
                $result = $this->ConfirmPayment( $order->order_total );
                if ( $result['ACK'] == 'Success' ) {
                    $this->add_log( 'Payment confirmed with PayPal successfully' );
                    $result = apply_filters( 'woocommerce_payment_successful_result', $result );
                    $order->add_order_note( __( 'PayPal Express payment completed', 'wc-paypal-express' ) .
                    ' ( Response Code: ' . $result['ACK'] . ", " .
                    ' TransactionID: '.$result['PAYMENTINFO_0_TRANSACTIONID'] . ' )' );
                    $order->payment_complete();
                    // Empty the Cart
                    WC()->cart->empty_cart();
                } else {
                    $this->add_log( '...Error confirming order '.$order_id.' with PayPal' );
                    $this->add_log( '...response:'.print_r( $result, true ) );
                    wc_add_notice(  sprintf( __( 'PayPal Express Checkout is not available at this time.', 'wc-paypal-express' ) ), 'error' );
                }
                wp_redirect( $this->get_return_url( $order ) );
                exit;
            }
        }
    }
    /**
     * Prepare Order
     *
     * Save the cart session to an order that can be retrieved when customer returns from PayPal.
     */
    function prepare_order() {
        global $woocommerce;
        $order_id = "";
        if ( sizeof( WC()->cart->get_cart() ) == 0 )
            $woocommerce->add_error( sprintf( __( 'Sorry, your session has expired. <a href="%s">Return to homepage &rarr;</a>', 'wc-paypal-express' ), home_url() ) );
        if ( $woocommerce->cart->needs_shipping() ) {
            // Shipping Method
            $available_methods = WC()->shipping->get_shipping_methods();
            if ( !isset( $available_methods[$_SESSION['_chosen_shipping_method']] ) ) {
                $woocommerce->add_error( __( 'Invalid shipping method.', 'wc-paypal-express' ), home_url() );
                return 0;
            }
        }
        // Create Order ( send cart variable so we can record items and reduce inventory ).
        // Only create if this is a new order, not if the payment was rejected last time.
        $order_data = array(
            'post_type' => 'shop_order',
            'post_title' => 'Order &ndash; '.date( 'F j, Y @ h:i A' ),
            'post_status' => 'publish',
            'ping_status' => 'closed',
            'post_excerpt' => '',
            'post_author' => 1
        );
        // Cart items
        $order_items = array();
        foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
            $_product = $values['data'];
            // Store any item meta data - item meta class lets plugins add item meta in a standardized way
            $item_meta = new order_item_meta();
            $item_meta->new_order_item( $values );
            // Store variation data in meta so admin can view it
            if ( $values['variation'] && is_array( $values['variation'] ) ) {
                foreach ( $values['variation'] as $key => $value ) {
                    $item_meta->add( esc_attr( str_replace( 'attribute_', '', $key ) ), $value );
                }
            }
            $order_items[] = apply_filters( 'new_order_item', array(
                'id'     => $values['product_id'],
                'variation_id'   => $values['variation_id'],
                'name'     => html_entity_decode($_product->get_title(), ENT_NOQUOTES, 'UTF-8'),
                'qty'     => ( int ) $values['quantity'],
                'item_meta'   => $item_meta->meta,
                'line_subtotal'  => rtrim( rtrim( number_format( $values['line_subtotal'], 4, '.', '' ), '0' ), '.' ), // Line subtotal ( before discounts )
                'line_subtotal_tax' => rtrim( rtrim( number_format( $values['line_subtotal_tax'], 4, '.', '' ), '0' ), '.' ), // Line tax ( before discounts )
                'line_total'  => rtrim( rtrim( number_format( $values['line_total'], 4, '.', '' ), '0' ), '.' ),   // Line total ( after discounts )
                'line_tax'    => rtrim( rtrim( number_format( $values['line_tax'], 4, '.', '' ), '0' ), '.' ),   // Line Tax ( after discounts )
                'tax_class'   => $_product->get_tax_class()        // Tax class ( adjusted by filters )
            ), $values );
        }
        // Insert or update the post data
        $create_new_order = true;
        if ( isset( $_SESSION['order_awaiting_payment'] ) && $_SESSION['order_awaiting_payment'] > 0 ) {
            $order_id = ( int ) $_SESSION['order_awaiting_payment'];
            /* Check order is unpaid */
            $order = new WC_Order( $order_id );
            if ( $order->status == 'pending' ) {
                // Resume the unpaid order
                $order_data['ID'] = $order_id;
                wp_update_post( $order_data );
                do_action( 'woocommerce_resume_order', $order_id );
                $create_new_order = false;
            }
        }
        if ( $create_new_order ) {
            $order_id = wp_insert_post( $order_data );
            if ( is_wp_error( $order_id ) ) {
                $woocommerce->add_error( 'Error: Unable to create order. Please try again.' );
            } else {
                // Inserted successfully
                do_action( 'woocommerce_new_order', $order_id );
            }
        }
        // Get better formatted billing method ( title )
        if ( isset( $_SESSION['_chosen_shipping_method'] ) ) {
            $shipping_method = $_SESSION['_chosen_shipping_method'];
            if ( isset( $available_methods ) && isset( $available_methods[$_SESSION['_chosen_shipping_method']] ) )
                $shipping_method = $available_methods[$_SESSION['_chosen_shipping_method']]->label;
        }
        // Prepare order taxes for storage
        $order_taxes = array();
        foreach ( array_keys( $woocommerce->cart->taxes + WC()->cart->shipping_taxes ) as $key ) {
            $is_compound = ( $woocommerce->cart->tax->is_compound( $key ) ) ? 1 : 0;
            $cart_tax = ( isset( $woocommerce->cart->taxes[$key] ) ) ? WC()->cart->taxes[$key] : 0;
            $shipping_tax = ( isset( $woocommerce->cart->shipping_taxes[$key] ) ) ? WC()->cart->shipping_taxes[$key] : 0;
            $order_taxes[] = array(
                'label' => $woocommerce->cart->tax->get_rate_label( $key ),
                'compound' => $is_compound,
                'cart_tax' => number_format( $cart_tax, 2, '.', '' ),
                'shipping_tax' => number_format( $shipping_tax, 2, '.', '' )
            );
        }
        // These fields are not returned from PayPal Express
        update_post_meta( $order_id, '_billing_company',   "" );
        update_post_meta( $order_id, '_billing_address_1',   "" );
        update_post_meta( $order_id, '_billing_address_2',   "" );
        update_post_meta( $order_id, '_billing_city',    "" );
        update_post_meta( $order_id, '_billing_postcode',   "" );
        update_post_meta( $order_id, '_billing_country',   "" );
        update_post_meta( $order_id, '_billing_state',    "" );
        update_post_meta( $order_id, '_billing_email',    "" );
        update_post_meta( $order_id, '_billing_phone',    "" );
        if ( isset( $_SESSION['_chosen_shipping_method'] ) ) {
            update_post_meta( $order_id, '_shipping_method',   $_SESSION['_chosen_shipping_method'] );
        }
        update_post_meta( $order_id, '_payment_method',   $this->id );
        update_post_meta( $order_id, '_shipping_method_title',  $shipping_method );
        update_post_meta( $order_id, '_payment_method_title',  $this->title );
        update_post_meta( $order_id, '_order_shipping',   number_format( $woocommerce->cart->shipping_total, 2, '.', '' ) );
        update_post_meta( $order_id, '_order_discount',   number_format( $woocommerce->cart->get_order_discount_total(), 2, '.', '' ) );
        update_post_meta( $order_id, '_cart_discount',    number_format( $woocommerce->cart->get_cart_discount_total(), 2, '.', '' ) );
        update_post_meta( $order_id, '_order_tax',     number_format( $woocommerce->cart->tax_total, 2, '.', '' ) );
        update_post_meta( $order_id, '_order_shipping_tax',  number_format( $woocommerce->cart->shipping_tax_total, 2, '.', '' ) );
        update_post_meta( $order_id, '_order_total',    number_format( $woocommerce->cart->total, 2, '.', '' ) );
        update_post_meta( $order_id, '_order_key',     apply_filters( 'woocommerce_generate_order_key', uniqid( 'order_' ) ) );
        update_post_meta( $order_id, '_customer_user',    ( int ) get_current_user_id() );
        update_post_meta( $order_id, '_order_items',    $order_items );
        update_post_meta( $order_id, '_order_taxes',    $order_taxes );
        update_post_meta( $order_id, '_order_currency',   get_option( 'woocommerce_currency' ) );
        update_post_meta( $order_id, '_prices_include_tax',  get_option( 'woocommerce_prices_include_tax' ) );
        // Order status
        wp_set_object_terms( $order_id, 'pending', 'shop_order_status' );
        // Discount code meta
        if ( $applied_coupons = $woocommerce->cart->get_applied_coupons() ) update_post_meta( $order_id, 'coupons', implode( ', ', $applied_coupons ) );
        return $order_id;
    }
    /**
     * CallSetExpress Checkout
     *
     * Prepares the parameters for the SetExpressCheckout API Call.
     * Inputs:
     *  paymentAmount:   Total value of the shopping cart
     *  returnURL:   the page where buyers return to after they are done with the payment review on PayPal
     *  cancelURL:   the page where buyers return to when they cancel the payment review on PayPal
     *
     */
    function CallSetExpressCheckout( $paymentAmount, $returnURL, $cancelURL ) {
        if ( sizeof( WC()->cart->get_cart() ) == 0 )
            wc_add_notice( sprintf( __( 'Sorry, your session has expired. <a href="%s">Return to homepage &rarr;</a>', 'wc-paypal-express' ), home_url() ) , "error");
        // Construct the parameter string that describes the SetExpressCheckout API call in the shortcut implementation
        $nvpstr  = "&RETURNURL=" . $returnURL;
        $nvpstr .= "&CANCELURL=" . $cancelURL;
        // Add all items in the cart to the parameter string.

        $ctr = $total_items = $total_discount = $total_tax = $order_total = 0;
        foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
            $_product          = $values['data'];
            $qty               = absint( $values['quantity'] );
            $sku = $_product->get_sku();
            $values['name'] = html_entity_decode($_product->get_title(), ENT_NOQUOTES, 'UTF-8');

            if ($_product->product_type=='variation') {

                $meta = WC()->cart->get_item_data($values, true);

                if (empty($sku)) {
                    $sku = $_product->parent->get_sku();
                }

                if (!empty($meta)) {
                    $values['name'] .= " - ". str_replace(", \n", " - ",$meta);
                }
            }
            $nvpstr .= "&L_PAYMENTREQUEST_0_NUMBER" . $ctr . "=" . urlencode($sku);
            $nvpstr .= "&L_PAYMENTREQUEST_0_NAME" . $ctr . "=" . urlencode($values['name']);
            $nvpstr .= "&L_PAYMENTREQUEST_0_QTY" . $ctr . "=" . $qty;

            if ( get_option( 'woocommerce_prices_include_tax' ) == 'yes' ) {
                $product_price = number_format( $_product->get_price_including_tax(), 2, '.', '' );
            } else {
                $product_price = number_format( $_product->get_price_excluding_tax(), 2, '.', '' );
            }
            $nvpstr .= "&L_PAYMENTREQUEST_0_AMT" . $ctr . "=" . $product_price;

            $total_items +=  $product_price*$values['quantity'];

            $ctr++;
        }
        // Add coupons as line items if there are any

        if ( WC()->cart->get_cart_discount_total() ) {
            foreach ( WC()->cart->get_coupons( 'cart' ) as $code => $coupon ) {
                $nvpstr .= "&L_PAYMENTREQUEST_0_NUMBER" . $ctr . "=".urlencode($code);
                $nvpstr .= "&L_PAYMENTREQUEST_0_NAME" . $ctr . "=Cart Discount";
                $nvpstr .= "&L_PAYMENTREQUEST_0_QTY" . $ctr . "=1";
                $nvpstr .= "&L_PAYMENTREQUEST_0_AMT" . $ctr . "=-" . WC()->cart->coupon_discount_amounts[ $code ];
                $ctr++;
            }
            $total_discount -= WC()->cart->get_cart_discount_total();
        }
        if ( WC()->cart->get_order_discount_total() ) {
            foreach ( WC()->cart->get_coupons( 'order' ) as $code => $coupon ) {
                $nvpstr .= "&L_PAYMENTREQUEST_0_NUMBER" . $ctr . "=".urlencode($code);
                $nvpstr .= "&L_PAYMENTREQUEST_0_NAME" . $ctr . "=Order Discount";
                $nvpstr .= "&L_PAYMENTREQUEST_0_QTY" . $ctr . "=1";
                $nvpstr .= "&L_PAYMENTREQUEST_0_AMT" . $ctr . "=-" . WC()->cart->coupon_discount_amounts[ $code ];
                $ctr++;
            }
            $total_discount -= WC()->cart->get_order_discount_total();
        }
        $order_total = $total_items+$total_discount;
        $nvpstr .= "&PAYMENTREQUEST_0_ITEMAMT=" . $order_total;

        if ( get_option( 'woocommerce_prices_include_tax' ) == 'yes' ) {
            $shipping 		= WC()->cart->shipping_total + WC()->cart->shipping_tax_total;
            $tax			= '0.00';
        } else {
            $shipping 		= WC()->cart->shipping_total;
            $tax 			= WC()->cart->get_taxes_total();
        }

        $nvpstr .= "&PAYMENTREQUEST_0_TAXAMT=".$tax;
        $nvpstr .= "&PAYMENTREQUEST_0_SHIPDISCAMT=0";
        $nvpstr .= "&MAXAMT=". number_format( ( $paymentAmount + ( $paymentAmount * .5 ) ), 2, '.', '' );
        $nvpstr .= "&PAYMENTREQUEST_0_SHIPPINGAMT="  . number_format( $shipping, 2, '.', '' );
        $nvpstr .= "&PAYMENTREQUEST_0_HANDLINGAMT="  . "0.00";
        $nvpstr .= "&PAYMENTREQUEST_0_AMT="    . number_format( WC()->cart->total, 2, '.', '' );
        $nvpstr .= "&PAYMENTREQUEST_0_PAYMENTACTION="  . "Sale";
        $nvpstr .= "&PAYMENTREQUEST_0_CURRENCYCODE="  . get_option( 'woocommerce_currency' );
        if ( $this->paypal_account_optional == 'yes' ) {
            $nvpstr .= "&SOLUTIONTYPE=Sole";
            if ( $this->landing_page == 'login' ) {
                $nvpstr .= "&LANDINGPAGE=Login";
            } else {
                $nvpstr .= "&LANDINGPAGE=Billing";
            }
        }
        $resArray = $this->hash_call( "SetExpressCheckout", $nvpstr );
        $ack = strtoupper( $resArray["ACK"] );
        if ( $ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING" ) {
            $token = urldecode( $resArray["TOKEN"] );
            $this->set_session( 'TOKEN', $token );
        }
        return $resArray;
    }
    /**
     * CallGetShippingDetails
     *
     * Prepares the parameters for the GetExpressCheckoutDetails API Call.
     *
     * Inputs: None
     * Returns: The NVP Collection object of the GetExpressCheckoutDetails Call Response.
     */
    function CallGetShippingDetails( $token ) {
        //'--------------------------------------------------------------
        //' At this point, the buyer has completed authorizing the payment
        //' at PayPal.  The function will call PayPal to obtain the details
        //' of the authorization, incuding any shipping information of the
        //' buyer.  Remember, the authorization is not a completed transaction
        //' at this state - the buyer still needs an additional step to finalize
        //' the transaction
        //'--------------------------------------------------------------
        //'---------------------------------------------------------------------------
        //' Build a second API request to PayPal, using the token as the
        //'  ID to get the details on the payment authorization
        //'---------------------------------------------------------------------------
        $nvpstr = "&TOKEN=" . $token;
        //'---------------------------------------------------------------------------
        //' Make the API call and store the results in an array.
        //' If the call was a success, show the authorization details, and provide
        //'  an action to complete the payment.
        //' If failed, show the error
        //'---------------------------------------------------------------------------
        $resArray = $this->hash_call( "GetExpressCheckoutDetails", $nvpstr );
        $ack = strtoupper( $resArray["ACK"] );
        if ( $ack == "SUCCESS" || $ack=="SUCCESSWITHWARNING" ) {
            $this->set_session( 'payer_id', $resArray['PAYERID'] );
        }
        return $resArray;
    }
    /**
     * ConfirmPayment
     *
     * Prepares the parameters for the GetExpressCheckoutDetails API Call.
     *
     * Inputs:
     *  sBNCode: The BN code used by PayPal to track the transactions from a given shopping cart.
     * Returns:
     *  The NVP Collection object of the GetExpressCheckoutDetails Call Response.
     */
    function ConfirmPayment( $FinalPaymentAmt ) {
        /* Gather the information to make the final call to
           finalize the PayPal payment.  The variable nvpstr
           holds the name value pairs
        */
        $token      = urlencode( $this->get_session( 'TOKEN' ) );
        $payerID    = urlencode( $this->get_session( 'payer_id' ) );
        $serverName = urlencode( $_SERVER['SERVER_NAME'] );
        $nvpstr  = '&TOKEN=' . $token . '&PAYERID=' . $payerID . '&PAYMENTREQUEST_0_PAYMENTACTION=' . "Sale" . '&PAYMENTREQUEST_0_AMT=' . number_format( $FinalPaymentAmt, 2, '.', '' );
        if (!empty($this->confirm_order_id)){
            $order =  new WC_Order($this->confirm_order_id);
            $nvpstr .= '&PAYMENTREQUEST_0_INVNUM='. str_replace("#","",$order->get_order_number());
            $ctr = 0;
            $ITEMAMT = 0;
            if ( $order->customer_note )
                $nvpstr  .= '&PAYMENTREQUEST_0_NOTETEXT='.wptexturize( $order->customer_note );
            if (sizeof($order->get_items())>0) {
                foreach ($order->get_items() as $values) {
                    $_product = $order->get_product_from_item($values);
                    $qty               = absint( $values['qty'] );
                    $sku = $_product->get_sku();
                    $values['name'] = html_entity_decode($values['name'], ENT_NOQUOTES, 'UTF-8');
                    if ($_product->product_type=='variation') {
                        if (empty($sku)) {
                            $sku = $_product->parent->get_sku();
                        }

                        //$this->log->add('paypal-pro', print_r($item['item_meta'], true));

                        $item_meta = new WC_Order_Item_Meta( $values['item_meta'] );
                        $meta = $item_meta->display(true, true);
                        if (!empty($meta)) {
                            $values['name'] .= " - ".str_replace(", \n", " - ",$meta);
                        }
                    }
                    $nvpstr .= "&L_PAYMENTREQUEST_0_NUMBER" . $ctr . "=" . urlencode( $sku);
                    $nvpstr .= "&L_PAYMENTREQUEST_0_NAME" . $ctr . "=" . urlencode( $values['name']);
                    $nvpstr .= "&L_PAYMENTREQUEST_0_QTY" . $ctr . "=" . $qty;
                    if ( get_option( 'woocommerce_prices_include_tax' ) == 'yes' ) {
                        $product_price = $order->get_item_subtotal( $values, true, false );
                    } else {
                        $product_price = $order->get_item_subtotal( $values, false, true );
                    }

                    $nvpstr .= "&L_PAYMENTREQUEST_0_AMT" . $ctr . "=" . $product_price;
                    $ITEMAMT += $product_price * $values['qty'];
                    $ctr++;
                }
            }

            if ( $order->get_cart_discount()>0 ) {
                foreach ( WC()->cart->get_coupons( 'cart' ) as $code => $coupon ) {
                    $nvpstr .= "&L_PAYMENTREQUEST_0_NUMBER" . $ctr . "=".urlencode($code);
                    $nvpstr .= "&L_PAYMENTREQUEST_0_NAME" . $ctr . "=Cart Discount";
                    $nvpstr .= "&L_PAYMENTREQUEST_0_QTY" . $ctr . "=1";
                    $nvpstr .= "&L_PAYMENTREQUEST_0_AMT" . $ctr . "=-" . WC()->cart->coupon_discount_amounts[ $code ];
                    $ctr++;
                }
                $ITEMAMT -= $order->get_cart_discount();
            }
            if ( $order->get_order_discount()>0 ) {
                foreach ( WC()->cart->get_coupons( 'order' ) as $code => $coupon ) {
                    $nvpstr .= "&L_PAYMENTREQUEST_0_NUMBER" . $ctr . "=".urlencode($code);
                    $nvpstr .= "&L_PAYMENTREQUEST_0_NAME" . $ctr . "=Order Discount";
                    $nvpstr .= "&L_PAYMENTREQUEST_0_QTY" . $ctr . "=1";
                    $nvpstr .= "&L_PAYMENTREQUEST_0_AMT" . $ctr . "=-" . WC()->cart->coupon_discount_amounts[ $code ];
                    $ctr++;
                }
                $ITEMAMT -= $order->get_order_discount();
            }


            if ( get_option( 'woocommerce_prices_include_tax' ) == 'yes' ) {
                $shipping 		= $order->get_total_shipping() + $order->get_shipping_tax();
                $tax			= 0;
            } else {
                $shipping 		= $order->get_total_shipping();
                $tax 			= $order->get_total_tax();
            }

            //total item amount
            $nvpstr .= "&PAYMENTREQUEST_0_ITEMAMT=".number_format($ITEMAMT, 2, '.', '');

            //tax
            if ($tax>0)
                $nvpstr .= "&PAYMENTREQUEST_0_TAXAMT=".number_format( $tax, 2, '.', '' );

            // Shipping Cost item - paypal only allows shipping per item, we want to send shipping for the order
            if ( $shipping > 0 )
                $nvpstr .= "&PAYMENTREQUEST_0_SHIPPINGAMT=" . number_format( $shipping, 2, '.', '' );

            $nvpstr .= "&PAYMENTREQUEST_0_SHIPTONAME=".$order->shipping_first_name . ' ' . $order->shipping_last_name;
            $nvpstr .= "&PAYMENTREQUEST_0_SHIPTOSTREET=".$order->shipping_address_1;
            $nvpstr .= "&PAYMENTREQUEST_0_SHIPTOCITY=".$order->shipping_city;
            $nvpstr .= "&PAYMENTREQUEST_0_SHIPTOSTATE=".$order->shipping_state;
            $nvpstr .= "&PAYMENTREQUEST_0_SHIPTOZIP=".$order->shipping_postcode;
            $nvpstr .= "&PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE=".$order->shipping_country;
            $nvpstr .= "&EMAIL=".$order->billing_email;
        }
        //order_id
        $nvpstr .= '&PAYMENTREQUEST_0_CURRENCYCODE=' . get_option( 'woocommerce_currency' ) . '&IPADDRESS=' . $serverName;
        $nvpstr .= '&BUTTONSOURCE=AngellEYE_PHPClass';
        /* Make the call to PayPal to finalize payment
            If an error occured, show the resulting errors
            */
        $resArray = $this->hash_call( "DoExpressCheckoutPayment", $nvpstr );
        /* Display the API response back to the browser.
           If the response from PayPal was a success, display the response parameters'
           If the response was an error, display the errors received using APIError.php.
           */
        $ack = strtoupper( $resArray["ACK"] );
        $this->remove_session('TOKEN');
        return $resArray;
    }
    /**
     * hash_call
     *
     * Function to perform the API call to PayPal using API signature
     * @methodName is name of API  method.
     * @nvpStr is nvp string.
     * returns an associtive array containing the response from the server.
     */
    function hash_call( $methodName, $nvpStr ) {
        parse_str( $nvpStr, $parsed_nvp );
        unset($parsed_nvp['BUTTONSOURCE']);
        $params = array(
            'body'          => array_merge( $parsed_nvp, array(
                'METHOD'       => $methodName,
                'VERSION'      => $this->version,
                'PWD'          => $this->api_password,
                'USER'         => $this->api_username,
                'SIGNATURE'    => $this->api_signature,
                'BUTTONSOURCE' => 'AngellEYE_PHPClass'
            ) ),
            'sslverify' 	=> false,
            'timeout' 		=> 30,
            'user-agent'	=> 'WooCommerce/' . WC()->version
        );
        $this->add_log( 'Test Mode: ' .$this->testmode );
        $this->add_log( 'PayPal Endpoint: ' . $this->API_Endpoint );
        $this->add_log( 'hash call: ' . print_r($params,true) );
        $response = wp_remote_post( $this->API_Endpoint, $params );
        //convrting NVPResponse to an Associative Array
        $nvpResArray = $this->deformatNVP( $response['body'] );
        $this->add_log( 'return: ' . print_r($nvpResArray,true) );
        return $nvpResArray;
    }
    /**
     * RedirectToPayPal
     *
     * Redirects to PayPal.com site.
     * Inputs:  NVP string.
     * Returns:
     */
    function RedirectToPayPal( $token ) {
        // Redirect to paypal.com here
        $payPalURL = $this->PAYPAL_URL . $token;
        wp_redirect( $payPalURL , 302 );
        exit;
    }
    /**
     * deformatNVP
     *
     * This function will take NVPString and convert it to an Associative Array and it will decode the response.
     * It is usefull to search for a particular key and displaying arrays.
     * @nvpstr is NVPString.
     * @nvpArray is Associative Array.
     */
    function deformatNVP( $nvpstr ) {
        $intial = 0;
        $nvpArray = array();
        while ( strlen( $nvpstr ) ) {
            //postion of Key
            $keypos = strpos( $nvpstr, '=' );
            //position of value
            $valuepos = strpos( $nvpstr, '&' ) ? strpos( $nvpstr, '&' ): strlen( $nvpstr );
            /*getting the Key and Value values and storing in a Associative Array*/
            $keyval = substr( $nvpstr, $intial, $keypos );
            $valval = substr( $nvpstr, $keypos+1, $valuepos-$keypos-1 );
            //decoding the respose
            $nvpArray[ urldecode( $keyval ) ] = urldecode( $valval );
            $nvpstr = substr( $nvpstr, $valuepos+1, strlen( $nvpstr ) );
        }
        return $nvpArray;
    }
    /**
     * get_state
     *
     * @param $country - country code sent by PayPal
     * @param $state - state name or code sent by PayPal
     */
    function get_state_code( $country, $state ) {
        // If not US address, then convert state to abbreviation
        if ( $country != 'US' ) {
            $local_states = WC()->countries->states[ WC()->customer->get_country() ];
            if ( ! empty( $local_states ) && in_array($state, $local_states)) {
                foreach ( $local_states as $key => $val ) {
                    if ( $val == $state) {
                        $state = $key;
                    }
                }
            }
        }
        return $state;
    }
    /**
     * set_session function.
     *
     * @access private
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    private function set_session( $key, $value ) {
        if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '<' ) )
            $_SESSION[ $key ] = $value;
        else
            WC()->session->$key = $value;
    }
    /**
     * get_session function.
     *
     * @access private
     * @param mixed $key
     * @return void
     */
    private function get_session( $key ) {
        if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '<' ) ) {
            if ( ! empty( $_SESSION[ $key ] ) )
                return $_SESSION[ $key ];
        } else {
            if ( ! empty( WC()->session->$key ) )
                return WC()->session->$key;
        }
        return '';
    }
    private function remove_session( $key ) {
        if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '<' ) )
            unset($_SESSION[ $key ]);
        else
            WC()->session->$key = "";
    }


    static function woocommerce_before_cart() {
        global $pp_settings, $pp_pro, $pp_payflow;
        if (@$pp_settings['enabled']=='yes' && 0 < WC()->cart->total ) {
            echo "<style>table.cart td.actions .input-text, table.cart td.actions .button, table.cart td.actions .checkout-button {margin-bottom: 0.53em !important}</style>";
            $pp_pro = get_option('woocommerce_paypal_pro_settings');
            $pp_payflow = get_option('woocommerce_paypal_pro_payflow_settings');
            if (@$pp_pro['enabled']=='yes' || @$pp_payflow['enabled']=='yes' || !WC()->payment_gateways->get_available_payment_gateways() ) {
                echo '<style>.cart input.checkout-button,
                            .cart a.checkout-button {
                                display: none !important;
                            }</style>';
            }


        }
    }
    /**
     *  Checkout Button
     *
     *  Triggered from the 'woocommerce_proceed_to_checkout' action.
     *  Displays the PayPal Express button.
     */
    static function woocommerce_paypal_express_checkout_button_angelleye() {
        global $pp_settings, $pp_pro, $pp_payflow;
        if (@$pp_settings['enabled']=='yes' && 0 < WC()->cart->total ) {

            if(!WC()->payment_gateways->get_available_payment_gateways())  {
                echo '<a class="paypal_checkout_button button alt" href="'. add_query_arg( 'pp_action', 'expresscheckout', add_query_arg( 'wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url( '/' ) ) ) .'">' . __('Proceed to Checkout', 'wc-paypal-express') .'</a>';
            }
            else {
                $pp_pro = get_option('woocommerce_paypal_pro_settings');
                $pp_payflow = get_option('woocommerce_paypal_pro_payflow_settings');
                if (@$pp_pro['enabled']=='yes' || @$pp_payflow['enabled']=='yes') {
                    echo '<a class="paypal_checkout_button button alt" href="#" onclick="jQuery(\'.checkout-button\').click(); return false;">' . __('Pay with Credit Card', 'wc-paypal-express') .'</a> &nbsp;';
                }
                if ( ! empty( $pp_settings['checkout_with_pp_button'] ) && $pp_settings['checkout_with_pp_button'] == 'yes' ) {
                    echo '<a class="paypal_checkout_button" href="' . add_query_arg( 'pp_action', 'expresscheckout', add_query_arg( 'wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url( '/' ) ) ) .'">';
                    echo "<img src='https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif' width='145' height='42' style='width: 145px; height: 42px; float:right; margin-right: 10px;' border='0' align='top' alt='Check out with PayPal'/>";
                    echo "</a>";
                } else {
                    echo '<a class="paypal_checkout_button button alt" href="'. add_query_arg( 'pp_action', 'expresscheckout', add_query_arg( 'wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url( '/' ) ) ) .'" style="margin-top:10px;">' . __('Pay with PayPal', 'wc-paypal-express') .'</a>';
                }
            }

        }
    }


}