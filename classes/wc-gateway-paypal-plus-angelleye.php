<?php

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Payment;
use PayPal\Api\Payer;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\ItemList;
use PayPal\Api\RedirectUrls;
use PayPal\Api\PaymentExecution;
use PayPal\Api\Details;
use PayPal\Api\ExecutePayment;
use PayPal\Api\Item;
use PayPal\Api\Refund;
use PayPal\Api\Sale;

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
        $this->mode = $this->settings['testmode'] == 'yes' ? "SANDBOX" : "LIVE";
        if ($this->mode == "LIVE") {
            $this->rest_client_id = @$this->settings['rest_client_id'];
            $this->rest_secret_id = @$this->settings['rest_secret_id'];
        } else {
            $this->rest_client_id = @$this->settings['rest_client_id_sandbox'];
            $this->rest_secret_id = @$this->settings['rest_secret_id_sandbox'];
        }

        $this->debug = $this->settings['debug'];
        $this->invoice_prefix = $this->settings['invoice_prefix'];
        $this->send_items = 'yes';
        $this->billing_address = isset($this->settings['billing_address']) ? $this->settings['billing_address'] : 'no';
        $this->cancel_url = isset($this->settings['cancel_url']) ? $this->settings['cancel_url'] : site_url();
        $this->allowed_currencies = apply_filters('woocommerce_paypal_plus_allowed_currencies', array('EUR', 'CAD'));
        $this->enabled = $this->settings['enabled'];
        $this->landing_page = isset($this->settings['landing_page']) ? $this->settings['landing_page'] : 'Login';
        $this->brand_name = isset($this->settings['brand_name']) ? $this->settings['brand_name'] : get_bloginfo('name');
        $this->checkout_logo = isset($this->settings['checkout_logo']) ? $this->settings['checkout_logo'] : '';
        // Enable Logs if user configures to debug
        if ($this->debug == 'yes') {
            $this->log = new WC_Logger();
        }
        /* 1.6.6 */
        add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
        /* 2.0.0 */
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        add_action('woocommerce_receipt_paypal_plus', array($this, 'receipt_page')); // Payment form hook
        add_action('woocommerce_review_order_before_payment', array($this, 'render_iframe')); // Payment form hook
        add_action('woocommerce_api_wc_gateway_paypal_plus_angelleye', array($this, 'executepay'));


        if (!defined('CLIENT_ID'))
            define('CLIENT_ID', $this->rest_client_id); //your PayPal client ID
        if (!defined('CLIENT_SECRET'))
            define('CLIENT_SECRET', $this->rest_secret_id); //PayPal Secret

        if (!defined('PP_CURRENCY'))
            define('PP_CURRENCY', get_woocommerce_currency()); //Currency code

        include_once( 'lib/autoload.php' ); //include PayPal SDK

        if (!defined("PP_CONFIG_PATH")) {
            define("PP_CONFIG_PATH", __DIR__);
        }
    }

    /**
     * Check if this gateway is enabled and available in the user's country
     * @access public
     * @return boolean
     */
    public function is_available() {
        //if enabled checkbox is checked
        if ($this->enabled === "yes") {
            // Currency check
            if (!in_array(get_option('woocommerce_currency'), $this->allowed_currencies)) {
                return false;
            }

            // Required fields check
            if (!$this->rest_client_id || !$this->rest_secret_id) {
                return false;
            }
            return true;
        }
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
        <p><?php _e('PayPal PLUS is a solution where PayPal offers PayPal, Credit Card and ELV as individual payment options on the payment selection page. The available payment methods are provided in a PayPal hosted iFrame.', 'paypal-for-woocommerce'); ?></p>
        <p><?php _e('NOTE: This is currently considered BETA but has been tested thoroughly in the sandbox without issue.', 'paypal-for-woocommerce'); ?></p>
        <table class="form-table">
            <?php
            if (!in_array(get_option('woocommerce_currency'), $this->allowed_currencies)) {
                ?>
                <div class="inline error"><p><strong><?php _e('Gateway Disabled', 'paypal-for-woocommerce'); ?></strong>: <?php _e('PayPal Plus does not support your store currency (Supports: EUR, CAD).', 'paypal-for-woocommerce'); ?></p></div>
                <?php
                return;
            } else {
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
         $require_ssl = '';
        if (!AngellEYE_Gateway_Paypal::is_ssl()) {
            $require_ssl = __('This image requires an SSL host.  Please upload your image to <a target="_blank" href="http://www.sslpic.com">www.sslpic.com</a> and enter the image URL here.', 'paypal-for-woocommerce');
        }
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable PayPal Plus', 'paypal-for-woocommerce'),
                'default' => 'no'
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
            'rest_client_id_sandbox' => array(
                'title' => __('Sandbox Client ID', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => 'Enter your Sandbox PayPal Rest API Client ID',
                'default' => ''
            ),
            'rest_secret_id_sandbox' => array(
                'title' => __('Sandbox Secret ID', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => __('Enter your Sandbox PayPal Rest API Secret ID.', 'paypal-for-woocommerce'),
                'default' => ''
            ),
            'rest_client_id' => array(
                'title' => __('Live Client ID', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => 'Enter your PayPal Rest API Client ID',
                'default' => ''
            ),
            'rest_secret_id' => array(
                'title' => __('Live Secret ID', 'paypal-for-woocommerce'),
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
                'description' => __('Please enter a prefix for your invoice numbers. If you use your PayPal account for multiple stores ensure this prefix is unique as PayPal will not allow orders with the same invoice number.', 'paypal-for-woocommerce'),
                'default' => 'WC-PPADV',
                'desc_tip' => true,
            ),
            'cancel_url' => array(
                'title' => __('Cancel URL', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Please enter an URL for customers to return when they cancel the order in PayPal.', 'paypal-for-woocommerce'),
                'default' => site_url(),
            ),
            'landing_page' => array(
                'title' => __('Landing Page', 'paypal-for-woocommerce'),
                'type' => 'select',
                'description' => __('Type of PayPal page to be displayed when a user lands on the PayPal site for checkout. When set to `Billing`, the Non-PayPal account landing page is used. When set to `Login`, the PayPal account login landing page is used.', 'paypal-for-woocommerce'),
                'options' => array('login' => __('Login', 'paypal-for-woocommerce'),
                    'billing' => __('Billing', 'paypal-for-woocommerce')),
                'default' => 'login',
            ),
            'brand_name' => array(
                'title' => __('Brand Name', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('A label that overrides the business name in the PayPal account on the PayPal pages.', 'paypal-for-woocommerce'),
                'default' => __(get_bloginfo('name'), 'paypal-for-woocommerce')
            ),
            'checkout_logo' => array(
                'title' => __('PayPal Checkout Logo (190x60px)', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls what users see as the logo on PayPal review pages. ', 'paypal-for-woocommerce') . $require_ssl,
                'default' => ''
            ),
            'debug' => array(
                'title' => __('Debug Log', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'paypal-for-woocommerce'),
                'default' => 'no',
                'description' => sprintf( __( 'Log PayPal events, such as Secured Token requests, inside <code>%s</code>', 'paypal-for-woocommerce' ), wc_get_log_file_path( 'paypal_plus' ) )
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
        public function render_iframe() {
        if (!$this->is_available() || WC()->cart->total <= 0)
            return;
        //display the form in IFRAME, if it is layout C, otherwise redirect to paypal site
        //define the redirection url
        $location = $this->get_approvalurl();
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        $js_array = array();
        if (!empty($available_gateways)) {
            foreach ($available_gateways as $gateway) {
                if ($gateway->id != $this->id) {
                    $third_party[] = array(
                        'methodName' => $gateway->get_title(),
                        'description' => $gateway->get_description(),
                        'redirectUrl' => home_url()
                    );
                    $js_array[$gateway->id] = $gateway->get_title();
                }
            }
        }
        ?>
        <script src="https://www.paypalobjects.com/webstatic/ppplus/ppplus.min.js"type="text/javascript"></script>

        <div id="ppplus"><?php echo __('Loading payment gates ...', 'paypal-for-woocommerce'); ?> </div>

        <script type="application/javascript">
            var ppp = PAYPAL.apps.PPP({
            "approvalUrl": "<?php echo $location; ?>",
            "placeholder": "ppplus",
            "useraction": "commit",
            "buttonLocation": "outside",
            <?php if (get_locale() != ''): ?>
                "country":  <?php echo '"', substr(get_locale(), -2), '"'; ?>,
                "language": <?php echo '"', get_locale(), '"'; ?>,
            <?php endif; ?>
            "mode": "<?php echo strtolower($this->mode); ?>",
            <?php if (isset($third_party)): //check if we have third party payment  ?>
                "thirdPartyPaymentMethods": <?php echo json_encode($third_party); ?>,
            <?php endif; ?>
            "onLoad":setPayment,
            "disableContinue": 'place_order',
            "enableContinue": setPayment,
            "styles": {
            psp:{
            "font-size": "13px"
            }
            }
            });
            function setPayment() {
                var key_array = <?php echo '["' . implode('", "', array_keys($js_array)) . '"]'; ?>;
                var name_array = <?php echo '["' . implode('", "', $js_array) . '"]'; ?>;
                var current_cookie = jQuery.parseJSON(jQuery.cookie("paypalplus_session_v2"));
                select_position = ( current_cookie != null ? name_array.indexOf(current_cookie.paymentMethod) : 0 )
                if (select_position!=-1) {
                    jQuery('#payment_method_'+key_array[select_position]).attr("checked","checked");
                } else {
                    jQuery('#payment_method_paypal_plus').attr("checked","checked");
                }
                jQuery('#place_order').prop( "disabled", false);
            }
        </script>
        <style type="text/css">
            .payment_methods  {display:none}
        </style>
        <?php
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

    public function getAuth() {
        $auth = new ApiContext(new OAuthTokenCredential(CLIENT_ID, CLIENT_SECRET));
        $auth->setConfig(array('mode' => $this->mode, 'http.headers.PayPal-Partner-Attribution-Id' => 'AngellEYE_SP_WooCommerce'));
        return $auth;
    }

    public function get_approvalurl() {
        global $woocommerce;
        try { // try a payment request
            $PaymentData = AngellEYE_Gateway_Paypal::calculate(null, $this->send_items);
            $OrderItems = array();
            if ($this->send_items) {
                foreach ($PaymentData['order_items'] as $item) {
                    $_item = new Item();
                    $_item->setName($item['name'])
                            ->setCurrency(get_woocommerce_currency())
                            ->setQuantity($item['qty'])
                            ->setPrice($item['amt']);
                    array_push($OrderItems, $_item);
                }
            }

            $redirectUrls = new RedirectUrls();
            $redirectUrls->setReturnUrl($this->relay_response_url);
            $redirectUrls->setCancelUrl($this->cancel_url);


            $payer = new Payer();
            $payer->setPaymentMethod("paypal");

            $details = new Details();

            if (isset($PaymentData['shippingamt']))
                $details->setShipping($PaymentData['shippingamt']);
            if (isset($PaymentData['taxamt']))
                $details->setTax($PaymentData['taxamt']);
            $details->setSubtotal($PaymentData['itemamt']);

            $amount = new Amount();
            $amount->setCurrency(PP_CURRENCY);
            $amount->setTotal(WC()->cart->total);
            $amount->setDetails($details);

            $items = new ItemList();
            $items->setItems($OrderItems);

            $transaction = new Transaction();
            $transaction->setAmount($amount);
            $transaction->setDescription('');
            $transaction->setItemList($items);
            
            $payment = new Payment();
            if (empty(WC()->session->experience_profile_id)) {
                WC()->session->experience_profile_id = $this->create_web_experience_profile();
                if(!empty(WC()->session->experience_profile_id)) {
                    $payment->setExperienceProfileId(WC()->session->experience_profile_id);
                }
            } else {
                $payment->setExperienceProfileId(WC()->session->experience_profile_id);
            }
            
            $payment->setRedirectUrls($redirectUrls);
            $payment->setIntent("sale");
            $payment->setPayer($payer);
            $payment->setTransactions(array($transaction));

            $payment->create($this->getAuth());
            
            $this->add_log(print_r($payment, true));
            //if payment method was PayPal, we need to redirect user to PayPal approval URL
            if ($payment->state == "created" && $payment->payer->payment_method == "paypal") {
                WC()->session->paymentId = $payment->id; //set payment id for later use, we need this to execute payment

                return $payment->links[1]->href;
            }
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
            $this->add_log($ex->getData());
            wp_redirect($woocommerce->cart->get_cart_url());
            exit;
        } catch (Exception $ex) {
            wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
            $this->add_log($ex->getMessage());
            wp_redirect($woocommerce->cart->get_cart_url());
            exit;
        }
    }

    function receipt_page($order_id) {
        global $woocommerce;
        $order = new WC_Order($order_id);
        WC()->session->ppp_order_id = $order_id;
        $PaymentData = AngellEYE_Gateway_Paypal::calculate($order, true);

        $payment = new Payment();
        $payment->setId(WC()->session->paymentId);

        $patchReplace = new \PayPal\Api\Patch();
        $patchReplace->setOp('replace')
                ->setPath('/transactions/0/amount')
                ->setValue(json_decode('{
                    "total": "' . AngellEYE_Gateway_Paypal::number_format($order->get_total()) . '",
                    "currency": "' . get_woocommerce_currency() . '",
                    "details": {
                        "subtotal": "' . $PaymentData['itemamt'] . '",
                        "shipping": "' . $PaymentData['shippingamt'] . '",
                        "tax":"' . $PaymentData['taxamt'] . '"
                    }
                }'));

        $patchRequest = new \PayPal\Api\PatchRequest();
        $invoice_number = preg_replace("/[^a-zA-Z0-9]/", "", $order_id);
        if ($order->needs_shipping_address() && !empty($order->shipping_country)) {
            //add shipping info
            $patchAdd = new \PayPal\Api\Patch();
            $patchAdd->setOp('add')
                    ->setPath('/transactions/0/item_list/shipping_address')
                    ->setValue(json_decode('{
                    "recipient_name": "' . $order->shipping_first_name . ' ' . $order->shipping_last_name . '",
                    "line1": "' . $order->shipping_address_1 . '",
                    "city": "' . $order->shipping_city . '",
                    "state": "' . $order->shipping_state . '",
                    "postal_code": "' . $order->shipping_postcode . '",
                    "country_code": "' . $order->shipping_country . '"
                }'));
            $patchAddone = new \PayPal\Api\Patch();
            $patchAddone->setOp('add')->setPath('/transactions/0/invoice_number')->setValue($this->invoice_prefix.$invoice_number);
            $patchRequest->setPatches(array($patchAdd, $patchReplace, $patchAddone));
        } else {
            $patchAdd = new \PayPal\Api\Patch();
            $patchAdd->setOp('add')->setPath('/transactions/0/invoice_number')->setValue($this->invoice_prefix.$invoice_number);
            $patchRequest->setPatches(array($patchAdd, $patchReplace));
        }

        try {
            $result = $payment->update($patchRequest, $this->getAuth());
            $this->add_log(print_r($payment, true));
            if ($result == true)
                
                ?>
                <script src="https://www.paypalobjects.com/webstatic/ppplus/ppplus.min.js"type="text/javascript"></script>
                <script>
                    jQuery(document).ready(function () {
                        jQuery.blockUI({
                            message: "<?php echo esc_js(__('Thank you for your order. We are now redirecting you to PayPal to make payment.', 'paypal-for-woocommerce')) ?>",
                        baseZ: 99999,
                        overlayCSS:
                                {
                                    background: "#fff",
                                    opacity: 0.6
                                },
                        css: {
                            padding: "20px",
                            zindex: "9999999",
                            textAlign: "center",
                            color: "#555",
                            border: "3px solid #aaa",
                            backgroundColor: "#fff",
                            cursor: "wait",
                            lineHeight: "24px"
                        }
                    });
                    PAYPAL.apps.PPP.doCheckout();
                });
            </script>
            <?php
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
            $this->add_log($ex->getData());
            wp_redirect($woocommerce->cart->get_cart_url());
            exit;
        } catch (Exception $ex) {
            wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
            $this->add_log($ex->getMessage());
            wp_redirect($woocommerce->cart->get_cart_url());
            exit;
        }
    }

    public function executepay() {

        if (isset($_GET["token"]) && !empty($_GET["token"]) && isset($_GET["PayerID"]) && !empty($_GET["PayerID"])) {

            global $woocommerce;

            WC()->session->token = $_GET["token"];

            WC()->session->paymentId = $_GET["paymentId"];
            WC()->session->PayerID = $_GET["PayerID"];

            $execution = new PaymentExecution();
            $execution->setPayerId(WC()->session->PayerID);
      
            try {
                $payment = Payment::get(WC()->session->paymentId, $this->getAuth());
                $payment->execute($execution, $this->getAuth());
               
                $this->add_log(print_r($payment, true));
                if ($payment->state == "approved") { //if state = approved continue..
                    
                    global $wpdb;
                    
                    $this->log->add('paypal_plus', sprintf(__('Response: %s', 'paypal-for-woocommerce'), print_r($payment, true)));

                    $order = new WC_Order(WC()->session->ppp_order_id);

                    if ($this->billing_address == 'yes') {
                        require_once("lib/NameParser.php");
                        $parser = new FullNameParser();
                        $split_name = $parser->split_full_name($payment->payer->payer_info->shipping_address->recipient_name);
                        $shipping_first_name = $split_name['fname'];
                        $shipping_last_name = $split_name['lname'];
                        $full_name = $split_name['fullname'];

                        update_post_meta($order->id, '_billing_first_name', $shipping_first_name);
                        update_post_meta($order->id, '_billing_last_name', $shipping_last_name);
                        update_post_meta($order->id, '_billing_full_name', $full_name);
                        update_post_meta($order->id, '_billing_address_1', $payment->payer->payer_info->shipping_address->line1);
                        update_post_meta($order->id, '_billing_address_2', $payment->payer->payer_info->shipping_address->line2);
                        update_post_meta($order->id, '_billing_city', $payment->payer->payer_info->shipping_address->city);
                        update_post_meta($order->id, '_billing_postcode', $payment->payer->payer_info->shipping_address->postal_code);
                        update_post_meta($order->id, '_billing_country', $payment->payer->payer_info->shipping_address->country_code);
                        update_post_meta($order->id, '_billing_state', $payment->payer->payer_info->shipping_address->state);
                    }
                    $transactions = $payment->getTransactions();
                    $relatedResources = $transactions[0]->getRelatedResources();
                    $sale = $relatedResources[0]->getSale();
                    $saleId = $sale->getId();
                    $order->add_order_note(__('PayPal Plus payment completed', 'paypal-for-woocommerce'));
                    $order->payment_complete($saleId);

                    //add hook
                    do_action('woocommerce_checkout_order_processed', $order->id);

                    // Remove cart
                    WC()->cart->empty_cart();
                    unset(WC()->session->experience_profile_id);

                    if (method_exists($order, 'get_checkout_order_received_url')) {
                        $redirect = $order->get_checkout_order_received_url();
                    } else {
                        $redirect = add_query_arg('key', $order->order_key, add_query_arg('order', $order->id, get_permalink(get_option('woocommerce_thanks_page_id'))));
                    }
                    wp_redirect($redirect);
                } else {
                    wc_add_notice(__('Error Payment state:' . $payment->state, 'paypal-for-woocommerce'), 'error');
                    $this->add_log(__('Error Payment state:' . $payment->state, 'paypal-for-woocommerce'));
                    wp_redirect($woocommerce->cart->get_cart_url());
                    exit;
                }
            } catch (PayPal\Exception\PayPalConnectionException $ex) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->add_log($ex->getData());
                wp_redirect($woocommerce->cart->get_cart_url());
                exit;
            } catch (Exception $ex) {
                $this->add_log($ex->getMessage()); // Prints the Error Code
                wc_add_notice(__("Error processing checkout. Please try again.", 'paypal-for-woocommerce'), 'error');
                wp_redirect($woocommerce->cart->get_cart_url());
                exit;
            }
        }
    }
    
    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        $this->add_log('Begin Refund');
        $this->add_log('Order: ' . print_r($order, true));
        $this->add_log('Transaction ID: ' . print_r($order->get_transaction_id(), true));
        if (!$order || !$order->get_transaction_id() || !$this->rest_client_id || !$this->rest_secret_id) {
            return false;
        }
       
        
        if ($reason) {
            if (255 < strlen($reason)) {
                $reason = substr($reason, 0, 252) . '...';
            }

            $reason = html_entity_decode($reason, ENT_NOQUOTES, 'UTF-8');
        }
      
        $sale = Sale::get($order->get_transaction_id(), $this->getAuth());
        $amt = new Amount();
        $amt->setCurrency($order->get_order_currency());
        $amt->setTotal(AngellEYE_Gateway_Paypal::number_format($amount));

        $refund = new Refund();
        $refund->setAmount($amt);
        
        try {
            $this->add_log('Refund Request: ' . print_r($refund, true));
            $refundedSale = $sale->refund($refund, $this->getAuth());
            if($refundedSale->state == 'completed') {
                $order->add_order_note('Refund Transaction ID:' . $refundedSale->getId());
                if( isset($reason) && !empty($reason) ) {
                    $order->add_order_note('Reason for Refund :' . $reason);
                }
                $max_remaining_refund = wc_format_decimal( $order->get_total() - $order->get_total_refunded() );
                if ( !$max_remaining_refund > 0 ) {
                    $order->update_status('refunded');
                }
                if (ob_get_length()) ob_end_clean();
                return true;
            } 
        }        
        catch (PayPal\Exception\PayPalConnectionException $ex) {
            $this->add_log($ex->getData());
            $error_data = json_decode($ex->getData());
            if(is_object($error_data) && !empty($error_data)) {
                $error_message = ($error_data->message) ? $error_data->message : $error_data->information_link;
                return new WP_Error('paypal_plus_refund-error', $error_message);
            } else {
                return new WP_Error('paypal_plus_refund-error', $ex->getData());
            }
            
        } catch (Exception $ex) {
            $this->add_log($ex->getMessage());
            return new WP_Error('paypal_plus_refund-error', $ex->getMessage());
        }
    }
    
    public function create_web_experience_profile() {
        global $woocommerce;
        $addroverride = '';
        if(WC()->cart->needs_shipping()) {
            $addroverride = 1;
        }
        $no_shipping = 0;
        if($this->angelleye_paypal_plus_needs_shipping()) {
            $no_shipping = 1;
        } 
        $default  = wc_get_base_location();
        $country  = $default['country'];
        
        
        
        $flowConfig = new \PayPal\Api\FlowConfig();
        
        $flowConfig->setLandingPageType($this->landing_page);
        
        $presentation = new \PayPal\Api\Presentation();
        
        if( isset($this->checkout_logo) && !empty($this->checkout_logo)) {
            $presentation->setLogoImage($this->checkout_logo);
        }
        if( isset($this->brand_name) && !empty($this->brand_name)) {
            $presentation->setBrandName($this->brand_name);
        }
        if( isset($country) && !empty($country)) {
            $presentation->setLocaleCode($country);
        }
        
        $inputFields = new \PayPal\Api\InputFields();
        
        $inputFields->setAllowNote(true)
                ->setNoShipping($no_shipping)
                ->setAddressOverride($addroverride);
        
        $webProfile = new \PayPal\Api\WebProfile();
        
        $webProfile->setName($this->brand_name . uniqid())
                ->setFlowConfig($flowConfig)
                ->setPresentation($presentation)
                ->setInputFields($inputFields);
        try {
            $createProfileResponse = $webProfile->create($this->getAuth());
            return $createProfileResponse->getId();
        }  catch (PayPal\Exception\PayPalConnectionException $ex) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->add_log($ex->getData());
                wp_redirect($woocommerce->cart->get_cart_url());
                exit;
            } catch (Exception $ex) {
                $this->add_log($ex->getMessage()); // Prints the Error Code
                wc_add_notice(__("Error processing checkout. Please try again.", 'paypal-for-woocommerce'), 'error');
                wp_redirect($woocommerce->cart->get_cart_url());
                exit;
            }
    }
    
    public function angelleye_paypal_plus_needs_shipping() {
            if (sizeof(WC()->cart->get_cart()) != 0) {
                foreach (WC()->cart->get_cart() as $key => $value) {
                    $_product = $value['data'];
                    if (isset($_product->id) && !empty($_product->id) ) {
                        $_no_shipping_required = get_post_meta($_product->id, '_no_shipping_required', true);
                        if( $_no_shipping_required == 'yes' ) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                }
            } 
        }
}
