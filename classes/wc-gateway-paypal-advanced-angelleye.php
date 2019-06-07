<?php

class WC_Gateway_PayPal_Advanced_AngellEYE extends WC_Payment_Gateway {

    public $customer_id;

    public function __construct() {
        $this->id = 'paypal_advanced';
        $this->has_fields = true;
        $this->home_url = is_ssl() ? home_url('/', 'https') : home_url('/'); //set the urls (cancel or return) based on SSL
        $this->testurl = 'https://pilot-payflowpro.paypal.com';
        $this->liveurl = 'https://payflowpro.paypal.com';
        $this->relay_response_url = add_query_arg('wc-api', 'WC_Gateway_PayPal_Advanced_AngellEYE', $this->home_url);
        $this->method_title = __('PayPal Advanced', 'paypal-for-woocommerce');
        $this->secure_token_id = '';
        $this->securetoken = '';
        $this->supports = array(
            'subscriptions',
            'products',
            'refunds',
            'subscription_cancellation',
            'subscription_reactivation',
            'subscription_suspension',
            'subscription_amount_changes',
            'subscription_payment_method_change', // Subs 1.n compatibility.
            'subscription_payment_method_change_admin',
            'subscription_date_changes',
            'multiple_subscriptions',
        );

        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        $this->send_items = 'yes' === $this->get_option('send_items', 'yes');
        $this->enable_tokenized_payments = $this->get_option('enable_tokenized_payments', 'no');
        if ($this->enable_tokenized_payments == 'yes' && !is_add_payment_method_page()) {
            $this->supports = array_merge($this->supports, array('add_payment_method','tokenization'));
        }
        $this->is_enabled = 'yes' === $this->get_option('enabled', 'no');
        $this->enabled = $this->get_option('enabled', 'no');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->testmode = 'yes' === $this->get_option('testmode', 'yes');
        if ($this->testmode == false) {
            $this->testmode = AngellEYE_Utility::angelleye_paypal_for_woocommerce_is_set_sandbox_product();
        }
        if( $this->testmode == true ) {
            $this->loginid = $this->get_option('sandbox_loginid');
            $this->resellerid = $this->get_option('sandbox_resellerid');
            $this->user = $this->get_option('sandbox_user', $this->loginid);
            $this->password = $this->get_option('sandbox_password');
        } else {
            $this->loginid = $this->get_option('loginid');
            $this->resellerid = $this->get_option('resellerid');
            $this->user = $this->get_option('user', $this->loginid);
            $this->password = $this->get_option('password');
        }
        $this->debug = 'yes' === $this->get_option('debug', 'no');
        $this->invoice_id_prefix = $this->get_option('invoice_prefix', 'WC-PPADV');
        $this->page_collapse_bgcolor = $this->get_option('page_collapse_bgcolor');
        $this->page_collapse_textcolor = $this->get_option('page_collapse_textcolor');
        $this->page_button_bgcolor = $this->get_option('page_button_bgcolor');
        $this->page_button_textcolor = $this->get_option('page_button_textcolor');
        $this->label_textcolor = $this->get_option('label_textcolor');
        $this->icon = $this->get_option('card_icon', plugins_url('/assets/images/cards.png', plugin_basename(dirname(__FILE__))));
        $this->is_encrypt = $this->get_option('is_encrypt', 'no');
        $this->transtype = $this->get_option('transtype');
        $this->mobilemode = $this->get_option('mobilemode', 'yes');
        if ( is_ssl() || 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) ) {
            $this->icon = preg_replace("/^http:/i", "https:", $this->icon);
        }
        $this->icon = apply_filters('woocommerce_paypal_advanced_icon', $this->icon);
        $this->mobilemode = 'yes' === $this->get_option('mobilemode', 'yes');
        $this->layout = $this->get_option('layout', 'C');
        switch ($this->layout) {
            case 'A': $this->layout = 'TEMPLATEA';
                break;
            case 'B': $this->layout = 'TEMPLATEB';
                break;
            case 'C': $this->layout = 'MINLAYOUT';
                break;
        }


        $this->hostaddr = $this->testmode == true ? $this->testurl : $this->liveurl;
        $this->softdescriptor = $this->get_option('softdescriptor', '');
        if($this->send_items === false) {
            $this->subtotal_mismatch_behavior = 'drop';
        } else {
            $this->subtotal_mismatch_behavior = $this->get_option('subtotal_mismatch_behavior', 'add');
        }

        if ($this->debug == 'yes')
            $this->log = new WC_Logger();

        // Hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_paypal_advanced', array($this, 'receipt_page'));
        add_action('woocommerce_api_wc_gateway_paypal_advanced_angelleye', array($this, 'relay_response'));
        add_filter('woocommerce_settings_api_sanitized_fields_' . $this->id, array($this, 'angelleye_paypal_advanced_encrypt_gateway_api'), 10, 1);
        $this->customer_id;
        if (class_exists('WC_Gateway_Calculation_AngellEYE')) {
            $this->calculation_angelleye = new WC_Gateway_Calculation_AngellEYE($this->id, $this->subtotal_mismatch_behavior);
        } else {
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/wc-gateway-calculations-angelleye.php' );
            $this->calculation_angelleye = new WC_Gateway_Calculation_AngellEYE($this->id, $this->subtotal_mismatch_behavior);
        }
        do_action( 'angelleye_paypal_for_woocommerce_multi_account_api_' . $this->id, $this, null, null );
    }

    /**
     * Check if required fields for configuring the gateway are filled up by the administrator
     * @access public
     * @return void
     * */
    public function checks() {
        if (!$this->is_valid_currency() || $this->is_enabled == false) {
            return;
        }
        if (!$this->loginid) {
            echo '<div class="error angelleye-notice" style="display:none;"><div class="angelleye-notice-logo"><span></span></div><div class="angelleye-notice-message">' . sprintf(__('Paypal Advanced error: Please enter your PayPal Advanced Account Merchant Login.', 'paypal-for-woocommerce')) . '</div></div>';
        } elseif (!$this->resellerid) {
            echo '<div class="error angelleye-notice" style="display:none;"><div class="angelleye-notice-logo"><span></span></div><div class="angelleye-notice-message">' . sprintf(__('Paypal Advanced error: Please enter your PayPal Advanced Account Partner.', 'paypal-for-woocommerce')) . '</div></div>';
        } elseif (!$this->password) {
            echo '<div class="error angelleye-notice" style="display:none;"><div class="angelleye-notice-logo"><span></span></div><div class="angelleye-notice-message">' . sprintf(__('Paypal Advanced error: Please enter your PayPal Advanced Account Password.', 'paypal-for-woocommerce')) . '</div></div>';
        }
    }

    /**
     * redirect_to - redirects to the url based on layout type
     *
     * @access public
     * @return javascript code to redirect the parent to a page
     */
    public function redirect_to($redirect_url) {
        // Clean
        @ob_clean();

        // Header
        header('HTTP/1.1 200 OK');

        //redirect to the url based on layout type
        if ($this->layout != 'MINLAYOUT') {
            wp_redirect($redirect_url);
        } else {
            echo "<script>window.parent.location.href='" . $redirect_url . "';</script>";
        }
        exit;
    }

    /**
     * inquiry_transaction - Performs inquiry transaction
     *
     * @access private
     * @param  WC_Order $order
     * @param int $order_id
     * @return result code of the inquiry transaction
     */
    public function inquiry_transaction($order, $order_id) {

        //inquire transaction, whether it is really paid or not
        $paypal_args = array(
            'USER' => $this->user,
            'VENDOR' => $this->loginid,
            'PARTNER' => $this->resellerid,
            'PWD[' . strlen($this->password) . ']' => $this->password,
            'ORIGID' => wc_clean($_POST['PNREF']),
            'TENDER' => 'C',
            'TRXTYPE' => 'I',
            'BUTTONSOURCE' => 'AngellEYE_SP_WooCommerce'
        );

        $postData = ''; //stores the post data string
        foreach ($paypal_args as $key => $val) {
            $postData .= '&' . $key . '=' . $val;
        }

        $postData = trim($postData, '&');

        /* Using Curl post necessary information to the Paypal Site to generate the secured token */
        $response = wp_remote_post($this->hostaddr, array(
            'method' => 'POST',
            'body' => $postData,
            'timeout' => 70,
            'user-agent' => 'Woocommerce ' . WC_VERSION,
            'httpversion' => '1.1',
            'headers' => array('host' => 'www.paypal.com')
        ));
        if (is_wp_error($response)) {
            throw new Exception(__('There was a problem connecting to the payment gateway.', 'paypal-for-woocommerce'));
        }
        if (empty($response['body'])) {
            throw new Exception(__('Empty response.', 'paypal-for-woocommerce'));
        }

        $inquiry_result_arr = array(); //stores the response in array format
        parse_str($response['body'], $inquiry_result_arr);

        if ($inquiry_result_arr['RESULT'] == 0 && ( $inquiry_result_arr['RESPMSG'] == 'Approved' || $inquiry_result_arr['RESPMSG'] == 'Verified')) {
            $order->add_order_note(sprintf(__('Received result of Inquiry Transaction for the  (Order: %s) and is successful', 'paypal-for-woocommerce'), $order->get_order_number()));
            return 'Approved';
        } else if ( $inquiry_result_arr['RESULT'] == 0 && ( substr($_REQUEST['RESPMSG'],0,9) == 'Approved:' || $inquiry_result_arr['RESPMSG'] == 'Verified') ) {
            return 'Approved';
        } else {
            $order->add_order_note(sprintf(__('Received result of Inquiry Transaction for the  (Order: %s) and with error:%s', 'paypal-for-woocommerce'), $order->get_order_number(), $inquiry_result_arr['RESPMSG']));
             if(function_exists('wc_add_notice')) {
                wc_add_notice(__('Error:', 'paypal-for-woocommerce') . ' "' . $inquiry_result_arr['RESPMSG'] . '"', 'error');
            }
            return 'Error';
        }
    }

    /**
     * success_handler - Handles the successful transaction
     *
     * @access private
     * @param  WC_Order $order
     * @param int $order_id
     * @param bool $silent_post
     * @return
     */
    private function success_handler($order, $order_id, $silent_post) {
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        $_secure_token = $old_wc ? get_post_meta($order->id, '_secure_token', true) : get_post_meta($order->get_id(), '_secure_token', true);
        if (!empty($_REQUEST['SECURETOKEN']) && $_secure_token == $_REQUEST['SECURETOKEN']) {
            if ($this->debug == 'yes') {
                $this->log->add('paypal_advanced', __('Relay Response Tokens Match', 'paypal-for-woocommerce'));
            }
        } else { // Redirect to homepage, if any invalid request or hack
            if ($this->debug == 'yes') {
                $this->log->add('paypal_advanced', __('Relay Response Tokens Mismatch', 'paypal-for-woocommerce'));
            }
            //redirect to the checkout page, if not silent post
            if ($silent_post === false)
                $this->redirect_to($order->get_checkout_payment_url(true));
                exit;
            }

        // Add order note
        $order->add_order_note(sprintf(__('PayPal Advanced payment completed (Order: %s). Transaction number/ID: %s.', 'paypal-for-woocommerce'), $order->get_order_number(), $_POST['PNREF']));

        $inq_result = $this->inquiry_transaction($order, $order_id);

        // Handle response
        if ($inq_result == 'Approved') {//if approved
            // Payment complete
            $this->save_payment_token($order, wc_clean($_POST['PNREF']));
            
            do_action('before_save_payment_token', $order_id);
            $old_wc = version_compare(WC_VERSION, '3.0', '<');
            $is_save_payment_method = $old_wc ? get_post_meta($order->id, '_is_save_payment_method', true) : get_post_meta($order->get_id(), '_is_save_payment_method', true);
            if ($is_save_payment_method == 'yes') {
                if ( 0 != $order->get_user_id() ) {
                    $customer_id = $order->get_user_id();
                } else {
                    $customer_id = get_current_user_id();
                }
                $TRANSACTIONID = wc_clean($_POST['PNREF']);
                $token = new WC_Payment_Token_CC();
                $token->set_token($TRANSACTIONID);
                $token->set_gateway_id($this->id);
                $token->set_card_type('PayPal');
                $token->set_last4(wc_clean($_POST['ACCT']));
                $token->set_expiry_month(date('m'));
                $token->set_expiry_year(date('Y', strtotime('+1 year')));
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
            
            $order->payment_complete(wc_clean($_POST['PNREF']));
            // Remove cart
            WC()->cart->empty_cart();
            
            // Add order note
            $order->add_order_note(sprintf(__('Payment completed for the  (Order: %s)', 'paypal-for-woocommerce'), $order->get_order_number()));

            //log the completeion
            if ($this->debug == 'yes')
                $this->log->add('paypal_advanced', sprintf(__('Payment completed for the  (Order: %s)', 'paypal-for-woocommerce'), $order->get_order_number()));

            //redirect to the thanks page, if not silent post
            if ($silent_post === false) {
                $this->redirect_to($this->get_return_url($order));
            }
        }
    }

    /**
     * error_handler - Handles the error transaction
     *
     * @access private
     * @param  WC_Order $order
     * @param int $order_id
     * @param bool $silent_post
     * @return
     */
    private function error_handler($order, $order_id, $silent_post) {

        // 12-0 messages
        wc_clear_notices();
        // Add error
        wc_add_notice(__('Error:', 'paypal-for-woocommerce') . ' "' . urldecode(wc_clean($_POST['RESPMSG'])) . '"', 'error');
        $order->add_order_note( __('Payment failed via PayPal Advanced:', 'paypal-for-woocommerce') . '&nbsp;' . wc_clean($_POST['RESPMSG']));
        //redirect to the checkout page, if not silent post
        if ($silent_post === false) {
            $this->redirect_to($order->get_checkout_payment_url(true));
        }
    }

    /**
     * cancel_handler - Handles the cancel transaction
     *
     * @access private
     * @param  WC_Order $order
     * @param int $order_id
     * @return
     */
    private function cancel_handler($order, $order_id) {
        wp_redirect($order->get_cancel_order_url());
        exit;
    }

    /**
     * decline_handler - Handles the decline transaction
     *
     * @access private
     * @param  WC_Order $order
     * @param int $order_id
     * @param bool $silent_post
     * @return
     */
    private function decline_handler($order, $order_id, $silent_post) {


        $order->update_status('failed', __('Payment failed via PayPal Advanced:', 'paypal-for-woocommerce') . '&nbsp;' . wc_clean($_POST['RESPMSG']));

        if ($this->debug == 'yes') {
            $this->log->add('paypal_advanced', sprintf(__('Status has been changed to failed for order %s', 'paypal-for-woocommerce'), $order->get_order_number()));
        }
        if ($this->debug == 'yes') {
            $this->log->add('paypal_advanced', sprintf(__('Error Occurred while processing %s : %s, status: %s', 'paypal-for-woocommerce'), $order->get_order_number(), urldecode($_POST['RESPMSG']), $_POST['RESULT']));
        }
        $this->error_handler($order, $order_id, $silent_post);
    }

    /**
     * Relay response - Checks the payment transaction reponse based on that either completes the transaction or shows thows the exception and show sthe error
     *
     * @access public
     * @return javascript code to redirect the parent to a page
     */
    public function relay_response() {

        //define a variable to indicate whether it is a silent post or return
        if (isset($_REQUEST['silent']) && $_REQUEST['silent'] == 'true') {
            $silent_post = true;
        } else {
            $silent_post = false;
        }

        //log the event
        if ($silent_post === true) {
            $this->add_log(sprintf(__('Silent Relay Response Triggered: %s', 'paypal-for-woocommerce'), print_r(wp_unslash($_REQUEST, true))));
        } else {
            $this->add_log(sprintf(__('Relay Response Triggered: %s', 'paypal-for-woocommerce'), print_r(wp_unslash($_REQUEST, true))));
        }
        //if valid request
        if (!isset($_REQUEST['INVOICE'])) { // Redirect to homepage, if any invalid request or hack
            //if not silent post redirect it to home page otherwise just exit
            if ($silent_post === false) {
                wp_redirect(home_url('/'));
                exit;
            }
        }
        // get Order ID
        $order_id = absint( wp_unslash( $_REQUEST['USER1']));

        // Create order object
        $order = new WC_Order($order_id);

        //check for the status of the order, if completed or processing, redirect to thanks page. This case happens when silentpost is on
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        if($old_wc) {
            $status = isset($order->status) ? $order->status : $order->get_status();
        } else {
            $status = $order->get_status();
        }

        if ($status == 'processing' || $status == 'completed') {
            // Log
            if ($this->debug == "yes") {
                $this->log->add('paypal_advanced', sprintf(__('Redirecting to Thank You Page for order %s', 'paypal-for-woocommerce'), $order->get_order_number()));
            }

            //redirect to the thanks page, if not silent post
            if ($silent_post === false) {
                $this->redirect_to($this->get_return_url($order));
            }

            //define RESULT, if not provided in case of cancel, define with -1
            if (isset($_REQUEST['cancel_ec_trans']) && $_REQUEST['cancel_ec_trans'] == 'true') {
                $_REQUEST['RESULT'] = -1;
            }
        }
            //handle the successful transaction
            switch ($_REQUEST['RESULT']) {

                case 0 :
                    //handle exceptional cases
                    if ($_REQUEST['RESPMSG'] == 'Approved' || $_REQUEST['RESPMSG'] == 'Verified') {
                        $this->success_handler($order, $order_id, $silent_post);
                    } else if ( substr($_REQUEST['RESPMSG'],0,9) == 'Approved:') {
                        $order->add_order_note( __('Payment warning via PayPal Advanced.', 'paypal-for-woocommerce') . '&nbsp;' . wc_clean($_POST['RESPMSG']));
                        $this->success_handler($order, $order_id, $silent_post);
                    } else if ($_REQUEST['RESPMSG'] == 'Declined') {
                        $this->decline_handler($order, $order_id, $silent_post);
                    } else {
                        $this->error_handler($order, $order_id, $silent_post);
                    }
                    break;
                case 12:
                    $this->decline_handler($order, $order_id, $silent_post);
                    break;
                case -1:
                    $this->cancel_handler($order, $order_id);
                    break;
                default:
                    //handles error order
                    $this->error_handler($order, $order_id, $silent_post);
                    break;
            }
        
    }

    /**
     * Gets the secured token by passing all the required information to PayPal site
     *
     * @param order an WC_ORDER Object
     * @return secure_token as string
     */
    public function get_secure_token($order) {
        static $length_error = 0;
        $this->add_log(sprintf(__('Requesting for the Secured Token for the order %s', 'paypal-for-woocommerce'), $order->get_order_number()));
        // Generate unique id
        $this->secure_token_id = uniqid(substr(sanitize_text_field( wp_unslash($_SERVER['HTTP_HOST'])), 0, 9), true);

        // Prepare paypal_ars array to pass to paypal to generate the secure token
        $paypal_args = array();

        //override the layout with mobile template, if browsed from mobile if the exitsing layout is C or MINLAYOUT
        if (($this->layout == 'MINLAYOUT' || $this->layout == 'C') && $this->mobilemode == true) {
            $template = wp_is_mobile() ? "MOBILE" : $this->layout;
        } else {
            $template = $this->layout;
        }
       
        $this->transtype = ($order->get_total() == 0 ) ? 'A' : $this->transtype;
        $shipping_first_name = version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_first_name : $order->get_shipping_first_name();
        $shipping_last_name = version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_last_name : $order->get_shipping_last_name();
        $shipping_address_1 = version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_address_1 : $order->get_shipping_address_1();
        $shipping_address_2 = version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_address_2 : $order->get_shipping_address_2();
        $shiptostreet = $shipping_address_1 . ' ' . $shipping_address_2;
        $shipping_city = version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_city : $order->get_shipping_city();
        $shipping_postcode = version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_postcode : $order->get_shipping_postcode();
        $shipping_country = version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_country : $order->get_shipping_country();
        $shipping_state = version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_state : $order->get_shipping_state();
        $order_id = version_compare( WC_VERSION, '3.0', '<' ) ? $order->id : $order->get_id();
        
        $billing_company = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_company : $order->get_billing_company();
        $billing_first_name = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_first_name : $order->get_billing_first_name();
        $billing_last_name = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_last_name : $order->get_billing_last_name();
        $billing_address_1 = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_address_1 : $order->get_billing_address_1();
        $billing_address_2 = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_address_2 : $order->get_billing_address_2();
        $billtostreet = $billing_address_1 . ' ' . $billing_address_2;
        $billing_city = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_city : $order->get_billing_city();
        $billing_postcode = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_postcode : $order->get_billing_postcode();
        $billing_country = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_country : $order->get_billing_country();
        $billing_state = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_state : $order->get_billing_state();
        $billing_email = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_email : $order->get_billing_email();
        $billing_phone = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_phone : $order->get_billing_phone();
        
        $paypal_args = array(
            'VERBOSITY' => 'HIGH',
            'USER' => $this->user,
            'VENDOR' => $this->loginid,
            'PARTNER' => $this->resellerid,
            'PWD[' . strlen($this->password) . ']' => $this->password,
            'SECURETOKENID' => $this->secure_token_id,
            'CREATESECURETOKEN' => 'Y',
            'TRXTYPE' => $this->transtype,
            'CUSTREF' => $order->get_order_number(),
            'USER1' => $order_id,
            'INVNUM' => $this->invoice_id_prefix . preg_replace("/[^a-zA-Z0-9]/", "", str_replace("#", "", $order->get_order_number())),
            'AMT' => number_format($order->get_total(), 2, '.', ''),
            'FREIGHTAMT' => '',
            'COMPANYNAME[' . strlen($billing_company) . ']' => $billing_company,
            'CURRENCY' => version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency(),
            'EMAIL' => $billing_email,
            'BILLTOFIRSTNAME[' . strlen($billing_first_name) . ']' => $billing_first_name,
            'BILLTOLASTNAME[' . strlen($billing_last_name) . ']' => $billing_last_name,
            'BILLTOSTREET[' . strlen($billtostreet) . ']' => $billtostreet,
            'BILLTOCITY[' . strlen($billing_city) . ']' => $billing_city,
            'BILLTOSTATE[' . strlen($billing_state) . ']' => $billing_state,
            'BILLTOZIP' => $billing_postcode,
            'BILLTOCOUNTRY[' . strlen($billing_country) . ']' => $billing_country,
            'BILLTOEMAIL' => $billing_email,
            'BILLTOPHONENUM' => $billing_phone,
            'SHIPTOFIRSTNAME[' . strlen($shipping_first_name) . ']' => $shipping_first_name,
            'SHIPTOLASTNAME[' . strlen($shipping_last_name) . ']' => $shipping_last_name,
            'SHIPTOSTREET[' . strlen($shiptostreet) . ']' => $shiptostreet,
            'SHIPTOCITY[' . strlen($shipping_city) . ']' => $shipping_city,
            'SHIPTOZIP' => $shipping_postcode,
            'SHIPTOCOUNTRY[' . strlen($shipping_country) . ']' => $shipping_country,
            'BUTTONSOURCE' => 'AngellEYE_SP_WooCommerce',
            'RETURNURL[' . strlen($this->relay_response_url) . ']' => $this->relay_response_url,
            'URLMETHOD' => 'POST',
            'TEMPLATE' => $template,
            'PAGECOLLAPSEBGCOLOR' => ltrim($this->page_collapse_bgcolor, '#'),
            'PAGECOLLAPSETEXTCOLOR' => ltrim($this->page_collapse_textcolor, '#'),
            'PAGEBUTTONBGCOLOR' => ltrim($this->page_button_bgcolor, '#'),
            'PAGEBUTTONTEXTCOLOR' => ltrim($this->page_button_textcolor, '#'),
            'LABELTEXTCOLOR' => ltrim($this->settings['label_textcolor'], '#'),
            'MERCHDESCR' => $this->softdescriptor
        );

        //handle empty state exception e.g. Denmark
        if (empty($shipping_state)) {
            //replace with city
            $paypal_args['SHIPTOSTATE[' . strlen($shipping_city) . ']'] = $shipping_city;
        } else {
            //retain state
            $paypal_args['SHIPTOSTATE[' . strlen($shipping_state) . ']'] = $shipping_state;
        }

        // Determine the ERRORURL,CANCELURL and SILENTPOSTURL
        $cancelurl = add_query_arg('wc-api', 'WC_Gateway_PayPal_Advanced_AngellEYE', add_query_arg('cancel_ec_trans', 'true', $this->home_url));
        $paypal_args['CANCELURL[' . strlen($cancelurl) . ']'] = $cancelurl;

        $errorurl = add_query_arg('wc-api', 'WC_Gateway_PayPal_Advanced_AngellEYE', add_query_arg('error', 'true', $this->home_url));
        $paypal_args['ERRORURL[' . strlen($errorurl) . ']'] = $errorurl;

        $silentposturl = add_query_arg('wc-api', 'WC_Gateway_PayPal_Advanced_AngellEYE', add_query_arg('silent', 'true', $this->home_url));
        $paypal_args['SILENTPOSTURL[' . strlen($silentposturl) . ']'] = $silentposturl;
        
        $PaymentData = $this->calculation_angelleye->order_calculation($order_id);
        
        if ($PaymentData['is_calculation_mismatch'] == false && ($length_error == 0 || count($PaymentData['order_items']) < 11 )) {
            $paypal_args['ITEMAMT'] = 0;
            $item_loop = 0;
            foreach ($PaymentData['order_items'] as $_item) {
                $paypal_args['L_NUMBER' . $item_loop] = $_item['number'];
                $paypal_args['L_NAME' . $item_loop] = $_item['name'];
                $paypal_args['L_COST' . $item_loop] = $_item['amt'];
                $paypal_args['L_QTY' . $item_loop] = $_item['qty'];
                if ($_item['number']) {
                    $paypal_args['L_SKU' . $item_loop] = $_item['number'];
                }
                $item_loop++;
            }
            $paypal_args['ITEMAMT'] = $PaymentData['itemamt'];
            if( $order->get_total() != $PaymentData['shippingamt'] ) {
                $paypal_args['FREIGHTAMT'] = $PaymentData['shippingamt'];
            } else {
                $paypal_args['FREIGHTAMT'] = 0.00;
            }

            if( !empty($PaymentData['discount_amount']) && $PaymentData['discount_amount'] > 0 ) {
                $paypal_args['discount'] = $PaymentData['discount_amount'];
            }
            $paypal_args['TAXAMT'] = $PaymentData['taxamt'];
        }
        
        $paypal_param = $paypal_args;

        try {

            $postData = '';
            $logData = '';

            foreach ($paypal_args as $key => $val) {

                $postData .= '&' . $key . '=' . $val;
                if (strpos($key, 'PWD') === 0) {
                    $logData .= '&PWD=XXXX';
                    $paypal_param[$key] = 'XXXX';
                } else {
                    $logData .= '&' . $key . '=' . $val;
                }
            }

            $postData = trim($postData, '&');


            // Log
            if ($this->debug == 'yes') {
                $logData = trim($logData, '&');
                $this->log->add('paypal_advanced', sprintf(__('Requesting for the Secured Token for the order %s with following URL and Paramaters: %s', 'paypal-for-woocommerce'), $order->get_order_number(), $this->hostaddr . '?' . $logData));
                $this->log->add('paypal_advanced', 'Request: ' . print_r($paypal_param, true));
            }

            /* Using Curl post necessary information to the Paypal Site to generate the secured token */
            $response = wp_remote_post($this->hostaddr, array(
                'method' => 'POST',
                'body' => apply_filters('angelleye_woocommerce_paypal_advanced_create_secure_token_request_args', $postData),
                'timeout' => 70,
                'user-agent' => 'WooCommerce ' . WC_VERSION,
                'httpversion' => '1.1',
                'headers' => array('host' => 'www.paypal.com')
            ));

            //if error occurs, throw exception with the error message
            if (is_wp_error($response)) {

                throw new Exception($response->get_error_message());
            }
            if (empty($response['body'])) {
                throw new Exception(__('Empty response.', 'paypal-for-woocommerce'));
            }

            /* Parse and assign to array */

            parse_str($response['body'], $arr);

            // Handle response
            if ($arr['RESULT'] > 0) {
                // raise exception
                throw new Exception(__('There was an error processing your order - ' . $arr['RESPMSG'], 'paypal-for-woocommerce'));
            } else {//return the secure token
                return $arr['SECURETOKEN'];
            }
        } catch (Exception $e) {
            if ($order->has_status(array('pending', 'failed'))) {
                $this->send_failed_order_email($order_id);
            }
            $this->add_log(sprintf(__('Secured Token generation failed for the order %s with error: %s', 'paypal-for-woocommerce'), $order->get_order_number(), $e->getMessage()));
            if ($arr['RESULT'] != 7) {
                wc_add_notice(__('Error:', 'paypal-for-woocommerce') . ' "' . $e->getMessage() . '"', 'error');
                $length_error = 0;
                return;
            } else {
                $this->add_log(sprintf(__('Secured Token generation failed for the order %s with error: %s', 'paypal-for-woocommerce'), $order->get_order_number(), $e->getMessage()));
                $length_error++;
                return $this->get_secure_token($order);
            }
        }
    }

    /**
     * Check if this gateway is enabled and available in the user's country
     * @access public
     * @return boolean
     */
    public function is_available() {

        //if enabled checkbox is checked
        if ($this->is_enabled == true) {
            if (!in_array(get_woocommerce_currency(), apply_filters('woocommerce_paypal_advanced_allowed_currencies', array('USD', 'CAD')))) {
                return false;
            }
            if (!$this->user || !$this->loginid) {
                return false;
            }
            return true;
        } else {
            return false;
        }
    }

    public function is_valid_currency() {
        return in_array(get_woocommerce_currency(), apply_filters('woocommerce_paypal_advanced_supported_currencies', array('USD', 'CAD')));
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
        <h3><?php _e('PayPal Advanced', 'paypal-for-woocommerce'); ?></h3>
        <p><?php _e('PayPal Payments Advanced uses an iframe to seamlessly integrate PayPal hosted pages into the checkout process.', 'paypal-for-woocommerce'); ?></p>
        <div id="angelleye_paypal_marketing_table">
            
        
        <table class="form-table">
            <?php
            if(version_compare(WC_VERSION,'2.6','<')) {
                AngellEYE_Utility::woo_compatibility_notice();    
            } else {
               if (!in_array(get_woocommerce_currency(), array('USD', 'CAD'))) {
                    ?>
                    <div class="error angelleye-notice" style="display:none;"><div class="angelleye-notice-logo"><span></span></div><div class="angelleye-notice-message"><?php _e('Gateway Disabled', 'paypal-for-woocommerce'); ?></strong>: <?php _e('PayPal does not support your store currency.', 'paypal-for-woocommerce'); ?></div></div>
                    <?php
                    return;
                } else {
                    // Generate the HTML For the settings form.
                    $this->checks();
                    $this->generate_settings_html();
                }
            }
            wp_enqueue_script('wp-color-picker');
            wp_enqueue_style('wp-color-picker');
            ?>
        </table><!--/.form-table-->
        </div>
        <?php AngellEYE_Utility::angelleye_display_marketing_sidebar($this->id); ?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                jQuery('.paypal_for_woocommerce_color_field').wpColorPicker();
            });
             jQuery('#woocommerce_paypal_advanced_testmode').change(function () {
                var production = jQuery('#woocommerce_paypal_advanced_resellerid, #woocommerce_paypal_advanced_loginid, #woocommerce_paypal_advanced_user, #woocommerce_paypal_advanced_password').closest('tr'),
                sandbox = jQuery('#woocommerce_paypal_advanced_sandbox_resellerid, #woocommerce_paypal_advanced_sandbox_loginid, #woocommerce_paypal_advanced_sandbox_user, #woocommerce_paypal_advanced_sandbox_password').closest('tr');
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

// End admin_options()

    /**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    public function init_form_fields() {
        $this->send_items_value = ! empty( $this->settings['send_items'] ) && 'yes' === $this->settings['send_items'] ? 'yes' : 'no';
        $this->send_items = 'yes' === $this->send_items_value;
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable PayPal Advanced', 'paypal-for-woocommerce'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'paypal-for-woocommerce'),
                'default' => __('PayPal Advanced', 'paypal-for-woocommerce')
            ),
            'description' => array(
                'title' => __('Description', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the description which the user sees during checkout.', 'paypal-for-woocommerce'),
                'default' => __('PayPal Advanced description', 'paypal-for-woocommerce')
            ),
            'testmode' => array(
                'title' => __('PayPal sandbox', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable PayPal sandbox', 'paypal-for-woocommerce'),
                'default' => 'yes',
                'description' => sprintf(__('PayPal sandbox can be used to test payments. Sign up for a developer account <a href="%s">here</a>', 'paypal-for-woocommerce'), 'https://developer.paypal.com/'),
            ),
            'resellerid' => array(
                'title' => __('Partner', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Enter your PayPal Advanced Partner. If you purchased the account directly from PayPal, use PayPal.', 'paypal-for-woocommerce'),
                'default' => '',
                'custom_attributes' => array( 'autocomplete' => 'new-password'),
            ),
            'loginid' => array(
                'title' => __('Vendor (Merchant Login)', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => '',
                'default' => '',
                'custom_attributes' => array( 'autocomplete' => 'new-password'),
            ),
            'user' => array(
                'title' => __('User (optional)', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('If you created a user for API calls in your PayPal (PayFlow) Manager, enter that username here.  Otherwise, this should be the same value as Vendor (Merchant Login).', 'paypal-for-woocommerce'),
                'default' => '',
                'custom_attributes' => array( 'autocomplete' => 'new-password'),
            ),
            'password' => array(
                'title' => __('Password', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => __('Enter your PayPal Advanced account password.', 'paypal-for-woocommerce'),
                'default' => '',
                'custom_attributes' => array( 'autocomplete' => 'new-password'),
            ),
            'sandbox_resellerid' => array(
                'title' => __('Partner', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Enter your PayPal Advanced Partner. If you purchased the account directly from PayPal, use PayPal.', 'paypal-for-woocommerce'),
                'default' => '',
                'custom_attributes' => array( 'autocomplete' => 'new-password'),
            ),
            'sandbox_loginid' => array(
                'title' => __('Vendor (Merchant Login)', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => '',
                'default' => '',
                'custom_attributes' => array( 'autocomplete' => 'new-password'),
            ),
            'sandbox_user' => array(
                'title' => __('User (optional)', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('If you set up one or more additional users on the account, this value is the ID of the user authorized to process transactions. Otherwise, leave this field blank.', 'paypal-for-woocommerce'),
                'default' => '',
                'custom_attributes' => array( 'autocomplete' => 'new-password'),
            ),
            'sandbox_password' => array(
                'title' => __('Password', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => __('Enter your PayPal Advanced account password.', 'paypal-for-woocommerce'),
                'default' => '',
                'custom_attributes' => array( 'autocomplete' => 'new-password'),
            ),
            'enable_tokenized_payments' => array(
                'title' => __('Enable Tokenized Payments', 'paypal-for-woocommerce'),
                'label' => __('Enable Tokenized Payments', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Allow buyers to securely save payment details to their account for quick checkout / auto-ship orders in the future.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'class' => 'enable_tokenized_payments'
            ),
            'softdescriptor' => array(
                'title' => __('Credit Card Statement Name', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('The value entered here will be displayed on the buyer\'s credit card statement.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
            ),
            'subtotal_mismatch_behavior' => array(
		'title'       => __( 'Subtotal Mismatch Behavior', 'paypal-for-woocommerce' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'description' => __( 'Internally, WC calculates line item prices and taxes out to four decimal places; however, PayPal can only handle amounts out to two decimal places (or, depending on the currency, no decimal places at all). Occasionally, this can cause discrepancies between the way WooCommerce calculates prices versus the way PayPal calculates them. If a mismatch occurs, this option controls how the order is dealt with so payment can still be taken.', 'paypal-for-woocommerce' ),
		'default'     => ($this->send_items) ? 'add' : 'drop' ,
		'desc_tip'    => true,
		'options'     => array(
			'add'  => __( 'Add another line item', 'paypal-for-woocommerce' ),
			'drop' => __( 'Do not send line items to PayPal', 'paypal-for-woocommerce' ),
		),
            ),
            'transtype' => array(
                'title' => __('Transaction Type', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class'    => 'wc-enhanced-select',
                'label' => __('Transaction Type', 'paypal-for-woocommerce'),
                'default' => 'S',
                'description' => '',
                'options' => array('A' => 'Authorization', 'S' => 'Sale')
            ),
            'layout' => array(
                'title' => __('Layout', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class'    => 'wc-enhanced-select',
                'label' => __('Layout', 'paypal-for-woocommerce'),
                'default' => 'C',
                'description' => __('Layouts A and B redirect to PayPal\'s website for the user to pay. <br/>Layout C (recommended) is a secure PayPal-hosted page but is embedded on your site using an iFrame.', 'paypal-for-woocommerce'),
                'options' => array('A' => 'Layout A', 'B' => 'Layout B', 'C' => 'Layout C')
            ),
            'mobilemode' => array(
                'title' => __('Mobile Mode', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Display Mobile optimized form if browsed through Mobile', 'paypal-for-woocommerce'),
                'default' => 'yes',
                'description' => sprintf(__('Disable this option if your theme is not compatible with Mobile. Otherwise You would get Silent Post Error in Layout C.', 'paypal-for-woocommerce'), 'https://developer.paypal.com/'),
            ),
            'card_icon' => array(
                'title' => __('Credit Card Logo Graphic', 'paypal-for-woocommerce'),
                'type' => 'text',
                'default' => plugins_url('/assets/images/cards.png', plugin_basename(dirname(__FILE__))),
                'class' => 'button_upload'
            ),
            'invoice_prefix' => array(
                'title' => __('Invoice Prefix', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Please enter a prefix for your invoice numbers. If you use your PayPal account for multiple stores ensure this prefix is unique as PayPal will not allow orders with the same invoice number.', 'paypal-for-woocommerce'),
                'default' => 'WC-PPADV',
                'desc_tip' => true,
            ),
            'page_collapse_bgcolor' => array(
                'title' => __('Page Collapse Border Color', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Sets the color of the border around the embedded template C.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'class' => 'paypal_for_woocommerce_color_field'
            ),
            'page_collapse_textcolor' => array(
                'title' => __('Page Collapse Text Color', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Sets the color of the words "Pay with PayPal" and "Pay with credit or debit card".', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'class' => 'paypal_for_woocommerce_color_field'
            ),
            'page_button_bgcolor' => array(
                'title' => __('Page Button Background Color', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Sets the background color of the Pay Now / Submit button.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'class' => 'paypal_for_woocommerce_color_field'
            ),
            'page_button_textcolor' => array(
                'title' => __('Page Button Text Color', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Sets the color of the text on the Pay Now / Submit button.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'class' => 'paypal_for_woocommerce_color_field'
            ),
            'label_textcolor' => array(
                'title' => __('Label Text Color', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Sets the color of the text for "card number", "expiration date", ..etc.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'class' => 'paypal_for_woocommerce_color_field'
            ),
            'debug' => array(
                'title' => __('Debug Log', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'paypal-for-woocommerce'),
                'default' => 'no',
                'description' => sprintf( __( 'Log PayPal events, inside <code>%s</code>', 'paypal-for-woocommerce' ), wc_get_log_file_path( 'paypal_advanced' ) )
            ),
            'is_encrypt' => array(
                'title' => __('', 'paypal-for-woocommerce'),
                'label' => __('', 'paypal-for-woocommerce'),
                'type' => 'hidden',
                'default' => 'yes',
                'class' => ''
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

        if ($this->description) {
            echo wpautop(wptexturize($this->description));
        }
        if ($this->supports('tokenization') && is_checkout()) {
            $this->tokenization_script();
            $this->saved_payment_methods();
            if( AngellEYE_Utility::is_cart_contains_subscription() == false && AngellEYE_Utility::is_subs_change_payment() == false) {
                $this->save_payment_method_checkbox();
            }
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

    /**
     * Process the payment
     *
     * @access public
     * @return void
     * */
    public function process_payment($order_id) {
        $order = new WC_Order($order_id);
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        if (!empty($_POST['wc-paypal_advanced-new-payment-method']) && $_POST['wc-paypal_advanced-new-payment-method'] == true) {
            if ($old_wc) {
                update_post_meta($order_id, '_is_save_payment_method', 'yes');
            } else {
                update_post_meta( $order->get_id(), '_is_save_payment_method', 'yes' );
            }
        }
        if ((!empty($_POST['wc-paypal_advanced-payment-token']) && $_POST['wc-paypal_advanced-payment-token'] != 'new') || $this->is_renewal($order_id)) {
            if ($this->is_renewal($order_id)) {
                $this->angelleye_reload_gateway_credentials_for_woo_subscription_renewal_order($order);
                $payment_tokens_id = get_post_meta($order_id, '_payment_tokens_id', true);
            } else {
                $token_id = wc_clean($_POST['wc-paypal_advanced-payment-token']);
                $token = WC_Payment_Tokens::get($token_id);
                $payment_tokens_id = $token->get_token();
            }
            $result_arr = $this->create_reference_transaction($payment_tokens_id, $order);
            if ( $result_arr['RESULT'] == 0 && ( substr($result_arr['RESPMSG'],0,9) == 'Approved:' || $result_arr['RESPMSG'] == 'Verified' || $result_arr['RESPMSG'] == 'Approved') ) {
                $order->payment_complete($payment_tokens_id);
                $this->save_payment_token($order, $payment_tokens_id);
                if (!$this->is_subscription($order_id)) {
                    WC()->cart->empty_cart();
                }
                $order->add_order_note(sprintf(__('Payment completed for the  (Order: %s)', 'paypal-for-woocommerce'), $order->get_order_number()));
                if ($this->debug == 'yes') {
                    $this->log->add('paypal_advanced', sprintf(__('Payment completed for the  (Order: %s)', 'paypal-for-woocommerce'), $order->get_order_number()));
                }
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            } else {
                return array(
                    'result' => 'failure',
                    'redirect' => ''
                );
            }
        }
        //use try/catch blocks to handle exceptions while processing the payment
        try {

            //get secure token
            $this->securetoken = $this->get_secure_token($order);

            //if valid securetoken
            if ($this->securetoken != "") {

                //add token values to post meta and we can use it later
                if ($old_wc) {
                    update_post_meta($order_id, '_secure_token_id', $this->secure_token_id);
                    update_post_meta($order_id, '_secure_token', $this->securetoken);
                } else {
                    update_post_meta( $order->get_id(), '_secure_token_id', $this->secure_token_id );
                    update_post_meta( $order->get_id(), '_secure_token', $this->securetoken );
                }

                //Log
                if ($this->debug == 'yes')
                    $this->log->add('paypal_advanced', sprintf(__('Secured Token generated successfully for the order %s', 'paypal-for-woocommerce'), $order->get_order_number()));

                //redirect to pay
                return array(
                    'result' => 'success',
                    'redirect' => $order->get_checkout_payment_url(true)
                );
            }
        } catch (Exception $e) {

            //add error
            wc_add_notice(__('Error:', 'paypal-for-woocommerce') . ' "' . $e->getMessage() . '"', 'error');

            //Log
            if ($this->debug == 'yes')
                $this->log->add('paypal_advanced', 'Error Occurred while processing the order ' . $order_id);
        }
        return;
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

        if (!$order || !$order->get_transaction_id()) {
            return false;
        }

        if (!is_null($amount) && $order->get_total() > $amount) {
            return new WP_Error('paypal-advanced-error', __('Partial refund is not supported', 'paypal-for-woocommerce'));
        }



        //refund transaction, parameters
        $paypal_args = array(
            'USER' => $this->user,
            'VENDOR' => $this->loginid,
            'PARTNER' => $this->resellerid,
            'PWD[' . strlen($this->password) . ']' => $this->password,
            'ORIGID' => $order->get_transaction_id(),
            'TENDER' => 'C',
            'TRXTYPE' => 'C',
            'VERBOSITY' => 'HIGH'
        );

        $postData = ''; //stores the post data string
        foreach ($paypal_args as $key => $val) {
            $postData .= '&' . $key . '=' . $val;
        }

        $postData = trim($postData, '&');

        // Using Curl post necessary information to the Paypal Site to generate the secured token
        $response = wp_remote_post($this->hostaddr, array(
            'method' => 'POST',
            'body' => $postData,
            'timeout' => 70,
            'user-agent' => 'Woocommerce ' . WC_VERSION,
            'httpversion' => '1.1',
            'headers' => array('host' => 'www.paypal.com')
        ));
        if (is_wp_error($response)) {
            throw new Exception(__('There was a problem connecting to the payment gateway.', 'paypal-for-woocommerce'));
        }
        if (empty($response['body'])) {
            throw new Exception(__('Empty response.', 'paypal-for-woocommerce'));
        }
        // Parse and assign to array 
        $refund_result_arr = array(); //stores the response in array format
        parse_str($response['body'], $refund_result_arr);

        //Log
        if ($this->debug == 'yes') {
            $this->log->add('paypal_advanced', sprintf(__('Response of the refund transaction: %s', 'paypal-for-woocommerce'), print_r($refund_result_arr, true)));
        }

        if ($refund_result_arr['RESULT'] == 0) {
            $order->add_order_note(sprintf(__('Successfully Refunded - Refund Transaction ID: %s', 'paypal-for-woocommerce'), $refund_result_arr['PNREF']));
            update_post_meta($order_id, 'Refund Transaction ID', $refund_result_arr['PNREF']);
        } else {

            $order->add_order_note(sprintf(__('Refund Failed - Refund Transaction ID: %s, Error Msg: %s', 'paypal-for-woocommerce'), $refund_result_arr['PNREF'], $refund_result_arr['RESPMSG']));
            throw new Exception(sprintf(__('Refund Failed - Refund Transaction ID: %s, Error Msg: %s', 'paypal-for-woocommerce'), $refund_result_arr['PNREF'], $refund_result_arr['RESPMSG']));

            return false;
        }
        return true;
    }

    /**
     * Displays IFRAME/Redirect to show the hosted page in Paypal
     *
     * @access public
     * @return void
     * */
    public function receipt_page($order_id) {

        //get the mode
        $PF_MODE = $this->testmode == true ? 'TEST' : 'LIVE';
        //create order object
        $order = new WC_Order($order_id);

        //get the tokens
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        $this->secure_token_id = $old_wc ? get_post_meta($order->id, '_secure_token_id', true) : get_post_meta($order->get_id(), '_secure_token_id', true);
        $this->securetoken = $old_wc ? get_post_meta($order->id, '_secure_token', true) : get_post_meta($order->get_id(), '_secure_token', true);

        //Log the browser and its version
        if ($this->debug == 'yes')
            $this->log->add('paypal_advanced', sprintf(__('Browser Info: %s', 'paypal-for-woocommerce'), $_SERVER['HTTP_USER_AGENT']));

        //display the form in IFRAME, if it is layout C, otherwise redirect to paypal site
        if ($this->layout == 'MINLAYOUT' || $this->layout == 'C') {
            //define the url
            $location = 'https://payflowlink.paypal.com?mode=' . $PF_MODE . '&SECURETOKEN=' . $this->securetoken . '&SECURETOKENID=' . $this->secure_token_id;
            $this->add_log(sprintf(__('Show payment form(IFRAME) for the order %s as it is configured to use Layout C', 'paypal-for-woocommerce'), $order->get_order_number()));
            //display the form
            ?>
            <iframe id="paypal_for_woocommerce_iframe" src="<?php echo $location; ?>" width="550" height="565" scrolling="no" frameborder="0" border="0" allowtransparency="true"></iframe>

            <?php
        } else {
            //define the redirection url
            $this->add_log(sprintf(__('Show payment form redirecting to ' . $location . ' for the order %s as it is not configured to use Layout C', 'paypal-for-woocommerce'), $order->get_order_number()));
            $location = 'https://payflowlink.paypal.com?mode=' . $PF_MODE . '&SECURETOKEN=' . $this->securetoken . '&SECURETOKENID=' . $this->secure_token_id;

            //Log
            if ($this->debug == 'yes')
                $this->log->add('paypal_advanced', sprintf(__('Show payment form redirecting to ' . $location . ' for the order %s as it is not configured to use Layout C', 'paypal-for-woocommerce'), $order->get_order_number()));

            //redirect
            wp_redirect($location);
            exit;
        }
    }

    /**
     * Limit the length of item names
     * @param  string $item_name
     * @return string
     */
    public function paypal_advanced_item_name($item_name) {
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
    public function paypal_advanced_item_desc($item_desc) {
        if (strlen($item_desc) > 127) {
            $item_desc = substr($item_desc, 0, 124) . '...';
        }
        return html_entity_decode($item_desc, ENT_NOQUOTES, 'UTF-8');
    }

    public function create_reference_transaction($token, $order) {
        static $length_error = 0;
        $shipping_first_name = version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_first_name : $order->get_shipping_first_name();
        $shipping_last_name = version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_last_name : $order->get_shipping_last_name();
        $shipping_address_1 = version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_address_1 : $order->get_shipping_address_1();
        $shipping_address_2 = version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_address_2 : $order->get_shipping_address_2();
        $shiptostreet = $shipping_address_1 . ' ' . $shipping_address_2;
        $shipping_city = version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_city : $order->get_shipping_city();
        $shipping_state = version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_state : $order->get_shipping_state();
        $shipping_postcode = version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_postcode : $order->get_shipping_postcode();
        $shipping_country = version_compare( WC_VERSION, '3.0', '<' ) ? $order->shipping_country : $order->get_shipping_country();
        $order_id = version_compare( WC_VERSION, '3.0', '<' ) ? $order->id : $order->get_id();
        
        $billing_company = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_company : $order->get_billing_company();
        $billing_first_name = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_first_name : $order->get_billing_first_name();
        $billing_last_name = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_last_name : $order->get_billing_last_name();
        $billing_address_1 = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_address_1 : $order->get_billing_address_1();
        $billing_address_2 = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_address_2 : $order->get_billing_address_2();
        $billtostreet = $billing_address_1 . ' ' . $billing_address_2;
        $billing_city = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_city : $order->get_billing_city();
        $billing_postcode = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_postcode : $order->get_billing_postcode();
        $billing_country = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_country : $order->get_billing_country();
        $billing_state = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_state : $order->get_billing_state();
        $billing_email = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_email : $order->get_billing_email();
        $billing_phone = version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_phone : $order->get_billing_phone();
        
        $this->transtype = ($order->get_total() == 0 ) ? 'A' : $this->transtype;
        
        $paypal_args = array();
        $paypal_args = array(
            'VERBOSITY' => 'HIGH',
            'TENDER' => 'C',
            'ORIGID' => $token,
            'USER' => $this->user,
            'VENDOR' => $this->loginid,
            'PARTNER' => $this->resellerid,
            'PWD[' . strlen($this->password) . ']' => $this->password,
            'TRXTYPE' => $this->transtype,
            'CUSTREF' => $order->get_order_number(),
            'USER1' => $order_id,
            'INVNUM' => $this->invoice_id_prefix . preg_replace("/[^a-zA-Z0-9]/", "", str_replace("#", "", $order->get_order_number())),
            'AMT' => number_format($order->get_total(), 2, '.', ''),
            'FREIGHTAMT' => '',
            'COMPANYNAME[' . strlen($billing_company) . ']' => $billing_company,
            'CURRENCY' => version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency(),
            'EMAIL' => $billing_email,
            'BILLTOFIRSTNAME[' . strlen($billing_first_name) . ']' => $billing_first_name,
            'BILLTOLASTNAME[' . strlen($billing_last_name) . ']' => $billing_last_name,
            'BILLTOSTREET[' . strlen($billtostreet) . ']' => $billtostreet,
            'BILLTOCITY[' . strlen($billing_city) . ']' => $billing_city,
            'BILLTOSTATE[' . strlen($billing_state) . ']' => $billing_state,
            'BILLTOZIP' => $billing_postcode,
            'BILLTOCOUNTRY[' . strlen($billing_country) . ']' => $billing_country,
            'BILLTOEMAIL' => $billing_email,
            'BILLTOPHONENUM' => $billing_phone,
            'SHIPTOFIRSTNAME[' . strlen($shipping_first_name) . ']' => $shipping_first_name,
            'SHIPTOLASTNAME[' . strlen($shipping_last_name) . ']' => $shipping_last_name,
            'SHIPTOSTREET[' . strlen($shiptostreet) . ']' => $shiptostreet,
            'SHIPTOCITY[' . strlen($shipping_city) . ']' => $shipping_city,
            'SHIPTOZIP' => $shipping_postcode,
            'SHIPTOCOUNTRY[' . strlen($shipping_country) . ']' => $shipping_country,
            'BUTTONSOURCE' => 'AngellEYE_SP_WooCommerce',
            'MERCHDESCR' => $this->softdescriptor
        );
        if ($this->is_subscription($order_id)) {
            $paypal_args['origid'] = get_post_meta($order_id, '_payment_tokens_id', true);
        }
        if (empty($shipping_state)) {
            $paypal_args['SHIPTOSTATE[' . strlen($shipping_state) . ']'] = $shipping_state;
        } else {
            $paypal_args['SHIPTOSTATE[' . strlen($shipping_state) . ']'] = $shipping_state;
        }
        $is_prices_include_tax = version_compare(WC_VERSION, '3.0', '<') ? 'yes' === $order->prices_include_tax : $order->get_prices_include_tax();
        if (($is_prices_include_tax || $order->get_total_discount() > 0 || $length_error > 1) && $order->get_subtotal() > 0) {
            $item_names = array();
            if (sizeof($order->get_items()) > 0) {
                if ($length_error <= 1) {
                    foreach ($order->get_items() as $item) {
                        if ($item['qty']) {
                            $item_names[] = $item['name'] . ' x ' . $item['qty'];
                        }
                    }
                } else {
                    $item_names[] = "All selected items, refer to Woocommerce order details";
                }
                $items_str = sprintf(__('Order %s', 'paypal-for-woocommerce'), $order->get_order_number()) . " - " . implode(', ', $item_names);
                $items_names_str = $this->paypal_advanced_item_name($items_str);
                $items_desc_str = $this->paypal_advanced_item_desc($items_str);
                $paypal_args['L_NAME0[' . strlen($items_names_str) . ']'] = $items_names_str;
                $paypal_args['L_DESC0[' . strlen($items_desc_str) . ']'] = $items_desc_str;
                $paypal_args['L_QTY0'] = 1;
                if ($order->get_subtotal() == 0) {
                    $paypal_args['L_COST0'] = number_format(version_compare( WC_VERSION, '3.0', '<' ) ? $order->get_total_shipping() : $order->get_shipping_total() + $order->get_shipping_tax(), 2, '.', '');
                } else {
                    $paypal_args['FREIGHTAMT'] = number_format(version_compare( WC_VERSION, '3.0', '<' ) ? $order->get_total_shipping() : $order->get_shipping_total() + $order->get_shipping_tax(), 2, '.', '');
                    $paypal_args['L_COST0'] = number_format($order->get_total() - round(version_compare( WC_VERSION, '3.0', '<' ) ? $order->get_total_shipping() : $order->get_shipping_total() + $order->get_shipping_tax(), 2), 2, '.', '');
                }
                $paypal_args['ITEMAMT'] = $paypal_args['L_COST0'] * $paypal_args['L_QTY0'];
            }
        } else {
            $paypal_args['TAXAMT'] = $order->get_total_tax();
            $paypal_args['ITEMAMT'] = 0;
            $item_loop = 0;
            if (sizeof($order->get_items()) > 0 && $order->get_subtotal() > 0) {
                foreach ($order->get_items() as $item) {
                    if ($item['qty']) {
                        $product = $order->get_product_from_item($item);
                        $item_name = $item['name'];
                        $paypal_args['L_NAME' . $item_loop . '[' . strlen($item_name) . ']'] = $item_name;
                        if ($product->get_sku()) {
                            $paypal_args['L_SKU' . $item_loop] = $product->get_sku();
                        }
                        $paypal_args['L_QTY' . $item_loop] = $item['qty'];
                        $paypal_args['L_COST' . $item_loop] = $order->get_item_total($item, false, false); /* No Tax , No Round) */
                        $paypal_args['L_TAXAMT' . $item_loop] = $order->get_item_tax($item, false); /* No Round it */
                        $paypal_args['ITEMAMT'] += $order->get_line_total($item, false, false); /* No tax, No Round */
                        $item_loop++;
                    }
                }
            } else {
                $paypal_args['L_NAME0'] = sprintf(__('Shipping via %s', 'paypal-for-woocommerce'), $order->get_shipping_method());
                $paypal_args['L_QTY0'] = 1;
                $paypal_args['L_COST0'] = number_format(version_compare( WC_VERSION, '3.0', '<' ) ? $order->get_total_shipping() : $order->get_shipping_total() + $order->get_shipping_tax(), 2, '.', '');
                $paypal_args['ITEMAMT'] = number_format(version_compare( WC_VERSION, '3.0', '<' ) ? $order->get_total_shipping() : $order->get_shipping_total() + $order->get_shipping_tax(), 2, '.', '');
            }
        }
        try {
            $postData = '';
            $logData = '';
            foreach ($paypal_args as $key => $val) {
                $postData .= '&' . $key . '=' . $val;
                if (strpos($key, 'PWD') === 0) {
                    $logData .= '&PWD=XXXX';
                } else {
                    $logData .= '&' . $key . '=' . $val;
                }
            }
            $postData = trim($postData, '&');
            $response = wp_remote_post($this->hostaddr, array(
                'method' => 'POST',
                'body' => apply_filters('angelleye_woocommerce_paypal_advanced_create_reference_transaction_request_args', $postData),
                'timeout' => 70,
                'user-agent' => 'WooCommerce ' . WC_VERSION,
                'httpversion' => '1.1',
                'headers' => array('host' => 'www.paypal.com')
            ));
            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }
            if (empty($response['body'])) {
                throw new Exception(__('Empty response.', 'paypal-for-woocommerce'));
            }
            parse_str($response['body'], $arr);
            if ($arr['RESULT'] > 0) {
                throw new Exception(__('There was an error processing your order - ' . $arr['RESPMSG'], 'paypal-for-woocommerce'));
            } else {//return the secure token
                $_POST['PNREF'] = $arr['PNREF'];
                return $arr;
            }
        } catch (Exception $e) {

            if ($arr['RESULT'] != 7) {
                if(function_exists('wc_add_notice')) {
                    wc_add_notice(__('Error:', 'paypal-for-woocommerce') . ' "' . $e->getMessage() . '"', 'error');
                } else {
                    $order->add_order_note(__('Error:', 'paypal-for-woocommerce') . ' "' . $e->getMessage() . '"');
                }
                $length_error = 0;
                return;
            } else {
                $length_error++;
            }
        }
    }

    public function add_log($message) {
        if ($this->debug) {
            if (!isset($this->log)) {
                $this->log = new WC_Logger();
            }
            $this->log->add($this->id, $message);
        }
    }

    public function are_reference_transactions_enabled($token_id) {
        if ($this->supports('tokenization') && class_exists('WC_Subscriptions_Order')) {
            $are_reference_transactions_enabled = get_option('are_reference_transactions_enabled', 'no');
            if ($are_reference_transactions_enabled == 'no') {
                $customer_id = get_current_user_id();
                if (!class_exists('Angelleye_PayPal_WC')) {
                    require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.class.php' );
                }
                if (!class_exists('Angelleye_PayPal_PayFlow')) {
                    require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.payflow.class.php' );
                }
                $PayPalConfig = array(
                    'Sandbox' => $this->testmode,
                    'APIUsername' => $this->paypal_user,
                    'APIPassword' => trim($this->paypal_password),
                    'APIVendor' => $this->paypal_vendor,
                    'APIPartner' => $this->paypal_partner,
                    'Force_tls_one_point_two' => $this->Force_tls_one_point_two
                );
                $PayPal = new Angelleye_PayPal_PayFlow($PayPalConfig);
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
                $PayPalResult = $PayPal->ProcessTransaction($PayPalRequestData);
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
        $order_id = version_compare( WC_VERSION, '3.0', '<' ) ? $order->id : $order->get_id();
        if (!empty($payment_tokens_id)) {
            update_post_meta($order_id, '_payment_tokens_id', $payment_tokens_id);
        }
    }

    public function angelleye_paypal_advanced_encrypt_gateway_api($settings) {
        if( !empty($settings['resellerid'])) {
            $resellerid = $settings['resellerid'];
        } else {
            $resellerid = '';
        }
        if(strlen($resellerid) > 28 ) {
            return $settings;
        }
        if( !empty($settings['is_encrypt']) ) {
            $gateway_settings_keys = array('loginid', 'resellerid', 'user', 'password');
            foreach ($gateway_settings_keys as $gateway_settings_key => $gateway_settings_value) {
                if( !empty( $settings[$gateway_settings_value]) ) {
                    $settings[$gateway_settings_value] = AngellEYE_Utility::crypting($settings[$gateway_settings_value], $action = 'e');
                }
            }
        }
        return $settings;
    }
    
    public function is_subscription($order_id) {
        return ( function_exists('wcs_order_contains_subscription') && ( wcs_order_contains_subscription($order_id) || wcs_is_subscription($order_id) || wcs_order_contains_renewal($order_id) ) );
    }
    
    public function is_renewal($order_id) {
        return ( function_exists('wcs_order_contains_subscription') && wcs_order_contains_renewal($order_id)  );
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
                        }
                    }        
                }
            }
        }
    }
    
    public function init_settings() {
        parent::init_settings();
        $this->enabled  = ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'] ? 'yes' : 'no';
        $this->send_items_value = ! empty( $this->settings['send_items'] ) && 'yes' === $this->settings['send_items'] ? 'yes' : 'no';
        $this->send_items = 'yes' === $this->send_items_value;
    }

}