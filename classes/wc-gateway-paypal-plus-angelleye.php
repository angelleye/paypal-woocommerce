<?php

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Payment;
use PayPal\Api\Payer;
use PayPal\Api\PayerInfo;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\ItemList;
use PayPal\Api\RedirectUrls;
use PayPal\Api\PaymentExecution;
use PayPal\Api\CreditCard;
use PayPal\Api\CreditCardToken;
use PayPal\Api\FundingInstrument;
use PayPal\Api\ShippingAddress;
use PayPal\Api\Address;
use PayPal\Api\Details;
use PayPal\Api\ExecutePayment;

class WC_Gateway_PayPal_Plus_AngellEYE extends WC_Payment_Gateway {

    public function __construct() {
        // Necessary Properties

        $this->id = 'paypal_plus';
        $this->icon = apply_filters('woocommerce_paypal_plus_icon', '');
        $this->has_fields = true;
        $this->home_url = is_ssl() ? home_url('/', 'https') : home_url('/'); //set the urls (cancel or return) based on SSL
        $this->relay_response_url = add_query_arg('wc-api', 'WC_Gateway_PayPal_Plus_AngellEYE', $this->home_url);
        $this->method_title = __('PayPal Plus', 'paypal-for-woocommerce');
        $this->secure_token_id = '';
        $this->securetoken = '';
        $this->supports = array(
            'products',
            'refunds'
        );
        // Load the form fields.
        $this->init_form_fields();
        // Load the settings.
        $this->init_settings();
        // Define user set variables
        $this->title = $this->settings['title'];
        $this->description = $this->settings['description'];
        $this->mode = $this->settings['testmode']=='yes'? "SANDBOX":"LIVE";
        $this->rest_client_id = $this->settings['rest_client_id'];
        $this->rest_secret_id = $this->settings['rest_secret_id'];
        $this->debug = $this->settings['debug'];
        $this->invoice_prefix = $this->settings['invoice_prefix'];
        $this->send_items = 'yes';
        $this->billing_address = isset($this->settings['billing_address']) ? $this->settings['billing_address'] : 'no';

        // Enable Logs if user configures to debug
        if ($this->debug == 'yes')
            $this->log = new WC_Logger();
        // Hooks
        add_action('admin_notices', array($this, 'checks')); //checks for availability of the plugin
        /* 1.6.6 */
        add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
        /* 2.0.0 */
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        add_action('woocommerce_receipt_paypal_plus', array($this, 'receipt_page')); // Payment form hook

        add_action('woocommerce_api_' . strtolower(get_class()), array($this, 'executepay'), 12);

        add_action('woocommerce_create_order', array($this, 'remove_old_order'));

        if (!$this->is_available())
            $this->enabled = false;

        if (!defined('CLIENT_ID')) define('CLIENT_ID', $this->rest_client_id); //your PayPal client ID
        if (!defined('CLIENT_SECRET')) define('CLIENT_SECRET', $this->rest_secret_id); //PayPal Secret

        if (!defined('CANCEL_URL')) define('CANCEL_URL', site_url()); //cancel URL
        if (!defined('PP_CURRENCY')) define('PP_CURRENCY', get_woocommerce_currency()); //Currency code

        include_once( 'lib/autoload.php' ); //include PayPal SDK

        if (!defined("PP_CONFIG_PATH")) {
            define("PP_CONFIG_PATH", __DIR__);
        }
    }

    /**
     * Check if required fields for configuring the gateway are filled up by the administrator
     * @access public
     * @return void
     * */
    public function checks() {
        if ($this->enabled == 'no' || @$_GET['section']=='wc_gateway_paypal_plus_angelleye') {
            return;
        }
        // Check required fields
        if (!$this->rest_client_id || !$this->rest_secret_id) {
            echo '<div class="error"><p>' . sprintf(__('Paypal Plus error: Please enter your Rest API Cient ID and Secret ID <a href="%s">here</a>', 'paypal-for-woocommerce'), admin_url('admin.php?page=wc-settings&tab=checkout&section=' . strtolower('WC_Gateway_PayPal_Plus_AngellEYE'))) . '</p></div>';
        }

        return;
    }

    /**
     * Check if this gateway is enabled and available in the user's country
     * @access public
     * @return boolean
     */
    public function is_available() {
        //if enabled checkbox is checked
        if ($this->enabled == 'yes')
            return true;
        return false;
    }

    /**
     * Admin Panel Options
     * - Settings
     *
     * @access public
     * @return void
     */
    public function admin_options() {
        ?>
        <h3><?php _e('PayPal Plus', 'paypal-for-woocommerce'); ?></h3>
        <p><?php _e('PayPal Payments Plus uses an iframe to seamlessly integrate PayPal hosted pages into the checkout process.', 'paypal-for-woocommerce'); ?></p>
        <table class="form-table">
            <?php
            //if user's currency is USD
            if (!in_array(get_woocommerce_currency(), array('EUR', 'CAD'))) {
                ?>
                <div class="inline error"><p><strong><?php _e('Gateway Disabled', 'paypal-for-woocommerce'); ?></strong>: <?php _e('PayPal Plus does not support your store currency (Supports: EUR, CAD).', 'paypal-for-woocommerce'); ?></p></div>
                <?php
                return;
            } else {
                // Generate the HTML For the settings form.
                $this->generate_settings_html();
            }
            ?>
        </table><!--/.form-table-->
    <?php
    }

// End admin_options()
    /**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable PayPal Plus', 'paypal-for-woocommerce'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Title', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'paypal-for-woocommerce'),
                'default' => __('PayPal Plus', 'paypal-for-woocommerce')
            ),
            'description' => array(
                'title' => __('Description', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the description which the user sees during checkout.', 'paypal-for-woocommerce'),
                'default' => __('PayPal Plus description', 'paypal-for-woocommerce')
            ),
            'rest_client_id' => array(
                'title' => __('Client ID', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => 'Enter your PayPal Rest API Client ID',
                'default' => ''
            ),
            'rest_secret_id' => array(
                'title' => __('Secret ID', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => __('Enter your PayPal Rest API Secret ID.', 'paypal-for-woocommerce'),
                'default' => ''
            ),
            'testmode' => array(
                'title' => __('PayPal sandbox', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable PayPal sandbox', 'paypal-for-woocommerce'),
                'default' => 'yes',
                'description' => sprintf(__('PayPal sandbox can be used to test payments. Sign up for a developer account <a href="%s">here</a>', 'paypal-for-woocommerce'), 'https://developer.paypal.com/'),
            ),
            'billing_address' => array(
                'title' => __('Billing Address', 'paypal-for-woocommerce'),
                'label' => __('Set billing address in WooCommerce using the address returned by PayPal.', 'paypal-for-woocommerce'),
                'description' => __('PayPal only returns a shipping address back to the website.  Enable this option if you would like to use this address for both billing and shipping in WooCommerce.'),
                'type' => 'checkbox',
                'default' => 'no'
            ),
            'invoice_prefix' => array(
                'title' => __('Invoice Prefix', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Please enter a prefix for your invoice numbers. If you use your PayPal account for multiple stores ensure this prefix is unique as PayPal will not allow orders with the same invoice number.', 'woocommerce'),
                'default' => 'WC-PPADV',
                'desc_tip' => true,
            ),
            'debug' => array(
                'title' => __('Debug Log', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'paypal-for-woocommerce'),
                'default' => 'no',
                'description' => __('Log PayPal events, such as Secured Token requests, inside <code>woocommerce/logs/paypal_plus.txt</code>', 'paypal-for-woocommerce'),
            )
        );
    }

// End init_form_fields()
    /**
     * There are no payment fields for paypal, but we want to show the description if set.
     *
     * @access public
     * @return void
     * */

    public function payment_fields() {
        if ($this->description)
            echo wpautop(wptexturize($this->description));
    }

    public function remove_old_order(){
       if (@$_POST['payment_method'] == 'paypal_plus' && isset(WC()->session->order_awaiting_payment)) {
           $order = new WC_Order(WC()->session->order_awaiting_payment);
           $order->update_status('failed');
           unset(WC()->session->order_awaiting_payment);
       }
    }
    /**
     * Process the payment
     *
     * @access public
     * @return void
     * */
    public function process_payment($order_id) {
        //create the order object
        $order = new WC_Order($order_id);
        if (isset(WC()->session->token)) {
            unset(WC()->session->paymentId);
            unset(WC()->session->PayerID);
        }
        //redirect to pay
        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );
    }

    /**
     * Displays IFRAME/Redirect to show the hosted page in Paypal
     *
     * @access public
     * @return void
     * */
    public function receipt_page($order_id) {
        //create order object
        $order = new WC_Order($order_id);
        //get the tokens
        //Log the browser and its version
        if ($this->debug == 'yes')
            $this->log->add('paypal_plus', sprintf(__('Browser Info: %s', 'paypal-for-woocommerce'), $_SERVER['HTTP_USER_AGENT']));
        //display the form in IFRAME, if it is layout C, otherwise redirect to paypal site
        //define the redirection url
        $location = $this->get_approvalurl($order_id);
        //$result = execute_payment($_SESSION["payment_id"], $_GET["PayerID"]);
        //Log
        if ($this->debug == 'yes')
            $this->log->add('paypal_plus', sprintf(__('Show payment form redirecting to ' . $location . ' for the order %s as it is not configured to use Layout C', 'paypal-for-woocommerce'), $order->get_order_number()));
        //redirect
        ?>
        <script src="https://www.paypalobjects.com/webstatic/ppplus/ppplus.min.js"type="text/javascript"></script>

        <div id="ppplus"> </div>

        <script type="application/javascript">
            var ppp = PAYPAL.apps.PPP({
                "approvalUrl": "<?php echo $location; ?>",
                "placeholder": "ppplus",
                "useraction": "commit",
                "onLoad" : "callback",
                "mode": "<?php echo strtolower($this->mode);?>"
            });
        </script>

        <?php
        exit;
    }

    /**
     * Limit the length of item names
     * @param  string $item_name
     * @return string
     */
    public function paypal_plus_item_name($item_name) {
        if (strlen($item_name) > 36) {
            $item_name = substr($item_name, 0, 33) . '...';
        }
        return html_entity_decode($item_name, ENT_NOQUOTES, 'UTF-8');
    }

    /**
     * Limit the length of item desc
     * @param  string $item_desc
     * @return string
     */
    public function paypal_plus_item_desc($item_desc) {
        if (strlen($item_desc) > 127) {
            $item_desc = substr($item_desc, 0, 124) . '...';
        }
        return html_entity_decode($item_desc, ENT_NOQUOTES, 'UTF-8');
    }

    ////////////////////////////////////////////////////////////////////////////////
    function add_log($message) {
        if (empty($this->log))
            $this->log = new WC_Logger();
        $this->log->add('paypal_plus', $message);
    }

    public function is_wc_version_greater_2_3() {
        return $this->get_wc_version() && version_compare($this->get_wc_version(), '2.3', '>=');
    }

    public function get_wc_version() {
        return defined('WC_VERSION') && WC_VERSION ? WC_VERSION : null;
    }

    function get_diffrent($amout_1, $amount_2) {
        $diff_amount = $amout_1 - $amount_2;
        return $diff_amount;
    }

    function cut_off($number) {
        $parts = explode(".", $number);
        $newnumber = $parts[0] . "." . $parts[1][0] . $parts[1][1];
        return $newnumber;
    }


    function get_order_details($order) {
        $OrderItems = array();
        $item_loop = 0;
        if ( sizeof( $order->get_items() ) > 0 ) {
            $ITEMAMT = $TAXAMT = 0;
            $inc_tax = get_option( 'woocommerce_prices_include_tax' ) == 'yes' ? true : false;
            foreach ( $order->get_items() as $item ) {
                $_product = $order->get_product_from_item($item);
                if ( $item['qty'] ) {
                    $sku = $_product->get_sku();
                    if ($_product->product_type=='variation') {
                        if (empty($sku)) {
                            $sku = $_product->parent->get_sku();
                        }
                        $item_meta = new WC_Order_Item_Meta( $item['item_meta'] );
                        $meta = $item_meta->display(true, true);
                        $item['name'] = html_entity_decode($item['name'], ENT_NOQUOTES, 'UTF-8');
                        if (!empty($meta)) {
                            $item['name'] .= " - ".str_replace(", \n", " - ",$meta);
                        }
                    }

                    $Item	 = array(
                        'name' => $item['name'], // Item Name.  127 char max.
                        'price' => round( $item['line_subtotal'] / $item['qty'], 2 ), 	// Cost of individual item.
                        'currency' => get_woocommerce_currency(),
                        'quantity' => $item['qty'], // Item quantity.  Must be any positive integer.

                    );
                    array_push($OrderItems, $Item);

                    $ITEMAMT += round( $item['line_subtotal'] / $item['qty'], 2 ) * $item['qty'];
                    $item_loop++;
                }
            }

            if (!$this->is_wc_version_greater_2_3()) {
                //Cart Discount
                if($order->get_cart_discount()>0)
                {
                    foreach(WC()->cart->get_coupons('cart') as $code => $coupon)
                    {
                        $Item	 = array(
                            'name' => 'Cart Discount', 						// Item Name.  127 char max.
                            'price' => '-'.WC()->cart->coupon_discount_amounts[$code], // Cost of individual item.
                            'currency' => get_woocommerce_currency(),
                            'quantity' => 1, // Item quantity.  Must be any positive integer.
                        );
                        array_push($OrderItems, $Item);
                    }

                    $ITEMAMT = $ITEMAMT - $order->get_cart_discount();
                }

                //Order Discount
                if($order->get_order_discount()>0)
                {
                    foreach(WC()->cart->get_coupons('order') as $code => $coupon)
                    {
                        $Item	 = array(
                            'name' => 'Order Discount', 						// Item Name.  127 char max.
                            'price' => '-'.WC()->cart->coupon_discount_amounts[$code], 							// Cost of individual item.
                            'currency' => get_woocommerce_currency(),
                            'quantity' => 1, // Item quantity.  Must be any positive integer.
                        );
                        array_push($OrderItems, $Item);
                    }

                    $ITEMAMT = $ITEMAMT - $order->get_order_discount();
                }
            } else {
                if ($order->get_total_discount() > 0) {
                    $Item = array(
                        'name' => 'Total Discount',
                        'price' => - number_format($order->get_total_discount(), 2, '.', ''),
                        'currency' => get_woocommerce_currency(),
                        'quantity' => 1, // Item quantity.  Must be any positive integer.
                    );
                    array_push($OrderItems, $Item);
                    $ITEMAMT -= number_format($order->get_total_discount(), 2, '.', '');
                }
            }

            /**
             * Get shipping and tax.
             */
            if(get_option('woocommerce_prices_include_tax' ) == 'yes')
            {
                $shipping 		= $order->get_total_shipping() + $order->get_shipping_tax();
                $tax			= 0;
            }
            else
            {
                $shipping 		= $order->get_total_shipping();
                $tax 			= $order->get_total_tax();
            }

            if('yes' === get_option( 'woocommerce_calc_taxes' ) && 'yes' === get_option( 'woocommerce_prices_include_tax' )) {
                $tax = $order->get_total_tax();
            }

            if ($tax>0)
            {
                $PaymentDetails['taxamt'] = number_format($tax, 2, '.', ''); 						// Required if you specify itemized cart tax details. Sum of tax for all items on the order.  Total sales tax.
            }

            if($shipping > 0)
            {
                $PaymentDetails['shippingamt'] = number_format($shipping, 2, '.', '');					// Total shipping costs for the order.  If you specify shippingamt, you must also specify itemamt.
            }
        }

        /**
         * Add custom Woo cart fees as line items
         */

        foreach ( WC()->cart->get_fees() as $fee )
        {
            $Item = array(
                'name' => $fee->name, // Item name. 127 char max.
                'price' => number_format($fee->amount,2,'.',''), // Cost of item.
                'currency' => get_woocommerce_currency(),
                'quantity' => 1, // Item quantity.  Must be any positive integer.
            );


            array_push($OrderItems, $Item);

            $ITEMAMT += $fee->amount*$Item['qty'];
            $item_loop++;
        }
        if( !$this->send_items ){
            $OrderItems = array();
            $PaymentDetails['itemamt'] = number_format($ITEMAMT,2,'.','');					// Required if you include itemized cart details. (L_AMTn, etc.)  Subtotal of items not including S&H, or tax.
        }else{
            $PaymentDetails['OrderItems'] = $OrderItems;
            $PaymentDetails['itemamt'] = number_format($ITEMAMT,2,'.','');					// Required if you include itemized cart details. (L_AMTn, etc.)  Subtotal of items not including S&H, or tax.
        }

        // Rounding amendment
        if (trim(number_format($order->get_total(), 2, '.', '')) !== trim(number_format($ITEMAMT,2,'.','') + number_format($tax, 2, '.', '') + number_format($shipping, 2, '.', ''))) {

            $diffrence_amount = $this->get_diffrent($order->get_total(), $ITEMAMT + $tax + number_format($shipping, 2, '.', ''));
            if($shipping > 0) {
                $PaymentDetails['shippingamt'] = number_format($shipping + $diffrence_amount, 2, '.', '');
            } elseif ($tax > 0) {
                $PaymentDetails['taxamt'] = number_format($tax + $diffrence_amount, 2, '.', '');
            } else {
                $PaymentDetails['itemamt'] = number_format($PaymentDetails['itemamt'] + $diffrence_amount, 2);
            }
        }

        return $PaymentDetails;
    }

    function getAuth() {
        $auth = new ApiContext(new OAuthTokenCredential(CLIENT_ID, CLIENT_SECRET));
        $auth->setConfig(array('mode'=> $this->mode, 'http.headers.PayPal-Partner-Attribution-Id' => 'AngellEYE_SP_WooCommerce'));
        return $auth;
    }

    function get_approvalurl($order_id) {

        $order = new WC_Order($order_id);

        $review_order_page_url = get_permalink(wc_get_page_id('review_order'));

        if (!$review_order_page_url) {
            $this->add_log(__('Review Order Page not found, re-create it. ', 'paypal-for-woocommerce'));
            include_once( WC()->plugin_path() . '/includes/admin/wc-admin-functions.php' );
            $page_id = wc_create_page(esc_sql(_x('review-order', 'page_slug', 'woocommerce')), 'woocommerce_review_order_page_id', __('Checkout &rarr; Review Order', 'paypal-for-woocommerce'), '[woocommerce_review_order]', wc_get_page_id('checkout'));
            $review_order_page_url = get_permalink($page_id);
        }

        $redirect_url = (add_query_arg('pp_action', $order_id, $review_order_page_url));


        try { // try a payment request

            $order_details = $this->get_order_details($order);

            $redirectUrls = new RedirectUrls();
            $redirectUrls->setReturnUrl($redirect_url);
            $redirectUrls->setCancelUrl(CANCEL_URL);

            $payer = new Payer();
            $payer->setPaymentMethod("paypal");

            $payerInfo = new PayerInfo();

            $billing_address = new Address();
            $billing_address ->setLine1($order->billing_address_1)
                ->setLine2($order->billing_address_2)
                ->setCity($order->billing_city)
                ->setPostalCode($order->billing_postcode)
                ->setCountryCode($order->billing_country)
                ->setPhone($order->billing_phone)
                ->setState($order->billing_state);
            $payerInfo->setBillingAddress($billing_address);

            $shipping_address = new ShippingAddress();
            $shipping_address ->setLine1($order->shipping_address_1)
                ->setLine2($order->shipping_address_2)
                ->setCity($order->shipping_city)
                ->setPostalCode($order->shipping_postcode)
                ->setCountryCode($order->shipping_country)
                ->setPhone($order->billing_phone)
                ->setState($order->shipping_state)
                ->setRecipientName($order->shipping_first_name.' ',$order->shipping_last_name);
            $payerInfo->setShippingAddress($shipping_address);

            $payer->setPayerInfo($payerInfo);

            $details = new Details();

            if (isset($order_details['shippingamt'])) $details->setShipping($order_details['shippingamt']);
            if (isset($order_details['taxamt'])) $details->setTax($order_details['taxamt']);
            $details->setSubtotal($order_details['itemamt']);

            $amount = new Amount();
            $amount->setCurrency(PP_CURRENCY);
            $amount->setTotal( $order->get_total());
            $amount->setDetails($details);

            $items = new ItemList();
            $items->setItems($order_details['OrderItems']);

            $transaction = new Transaction();
            $transaction->setAmount($amount);
            $transaction->setDescription('');
            $transaction->setItemList($items);
            $transaction->setInvoiceNumber($this->invoice_prefix.$order_id);


            $payment = new Payment();
            $payment->setRedirectUrls($redirectUrls);
            $payment->setIntent("sale");
            $payment->setPayer($payer);
            $payment->setTransactions(array($transaction));

            $payment->create($this->getAuth());

            //if payment method was PayPal, we need to redirect user to PayPal approval URL
            if ($payment->state == "created" && $payment->payer->payment_method == "paypal") {
                WC()->session->paymentId = $payment->id; //set payment id for later use, we need this to execute payment

                return $payment->links[1]->href;
            }
        } catch (PPConnectionException $ex) {

            wc_add_notice(__('Error:', 'paypal-for-woocommerce') . ' "' . $ex->getData() . '"', 'error');
        } catch (Exception $ex) {
            wc_add_notice(__('Error:', 'paypal-for-woocommerce') . ' "' . $ex->getMessage() . '"', 'error');
        }
    }

    public function executepay() {

        if (! empty( $_GET['pp_action'] ) && $_GET['pp_action'] == 'executepay') {

            if (empty(WC()->session->token) || empty(WC()->session->PayerID) || empty(WC()->session->paymentId)) return;

            $execution = new PaymentExecution();
            $execution->setPayerId(WC()->session->PayerID);

            try {
                $payment = Payment::get(WC()->session->paymentId, $this->getAuth());
                $payment->execute($execution, $this->getAuth());

                if ($payment->state == "approved") { //if state = approved continue..
                    global $wpdb;
                    $this->log->add('paypal_plus', sprintf(__('Response: %s', 'paypal-for-woocommerce'), print_r($payment,true)));

                    $order = new WC_Order(WC()->session->orderId);

                    if ($this->billing_address == 'yes') {
                        require_once("lib/NameParser.php");
                        $parser = new FullNameParser();
                        $split_name = $parser->split_full_name($payment->payer->payer_info->shipping_address->recipient_name);
                        $shipping_first_name = $split_name['fname'];
                        $shipping_last_name = $split_name['lname'];
                        $full_name = $split_name['fullname'];

                        update_post_meta(WC()->session->orderId, '_billing_first_name', $shipping_first_name);
                        update_post_meta(WC()->session->orderId, '_billing_last_name', $shipping_last_name);
                        update_post_meta(WC()->session->orderId, '_billing_full_name', $full_name);
                        update_post_meta(WC()->session->orderId, '_billing_address_1', $payment->payer->payer_info->shipping_address->line1);
                        update_post_meta(WC()->session->orderId, '_billing_address_2', $payment->payer->payer_info->shipping_address->line2);
                        update_post_meta(WC()->session->orderId, '_billing_city', $payment->payer->payer_info->shipping_address->city);
                        update_post_meta(WC()->session->orderId, '_billing_postcode', $payment->payer->payer_info->shipping_address->postal_code);
                        update_post_meta(WC()->session->orderId, '_billing_country', $payment->payer->payer_info->shipping_address->country_code);
                        update_post_meta(WC()->session->orderId, '_billing_state', $payment->payer->payer_info->shipping_address->state);
                    }

                    $order->add_order_note(__('PayPal Plus payment completed', 'paypal-for-woocommerce') );
                    $order->payment_complete($payment->id);

                    //add hook
                    do_action('woocommerce_checkout_order_processed', WC()->session->orderId);

                    wp_redirect($this->get_return_url($order));

                }
            } catch (Exception $ex) {
                wc_add_notice(__('Error:', 'paypal-for-woocommerce') . ' "' . $ex->getMessage() . '"', 'error');
            }
        }
    }

}