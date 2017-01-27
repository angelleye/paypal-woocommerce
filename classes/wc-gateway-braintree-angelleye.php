<?php

/**
 * WC_Gateway_Braintree_AngellEYE class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_Braintree_AngellEYE extends WC_Payment_Gateway_CC {

    /**
     * Constuctor
     */
    public $customer_id;
    function __construct() {
        $this->id = 'braintree';
        $this->icon = $this->get_option('card_icon', plugins_url('/assets/images/cards.png', plugin_basename(dirname(__FILE__))));
        if (is_ssl()) {
            $this->icon = preg_replace("/^http:/i", "https:", $this->icon);
        }
        $this->icon = apply_filters('woocommerce_braintree_icon', $this->icon);
        $this->has_fields = true;
        $this->method_title = 'Braintree';
        $this->method_description = __('Credit Card payments Powered by PayPal / Braintree.', 'paypal-for-woocommerce');
        $this->supports = array(
            'products',
            'refunds'
        );
        $this->init_form_fields();
        $this->init_settings();
        $this->enable_tokenized_payments = $this->get_option('enable_tokenized_payments', 'no');
        if($this->enable_tokenized_payments == 'yes') {
            array_push($this->supports, "tokenization");
        }
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->sandbox = $this->get_option('sandbox');
        $this->environment = $this->sandbox == 'no' ? 'production' : 'sandbox';
        $this->merchant_id = $this->sandbox == 'no' ? $this->get_option('merchant_id') : $this->get_option('sandbox_merchant_id');
        $this->private_key = $this->sandbox == 'no' ? $this->get_option('private_key') : $this->get_option('sandbox_private_key');
        $this->public_key = $this->sandbox == 'no' ? $this->get_option('public_key') : $this->get_option('sandbox_public_key');
        $this->enable_braintree_drop_in = $this->get_option('enable_braintree_drop_in') === "yes" ? true : false;
        $this->merchant_account_id = $this->sandbox == 'no' ? $this->get_option('merchant_account_id') : $this->get_option('sandbox_merchant_account_id');
        $this->debug = 'yes' === $this->get_option('debug', 'no');
        $this->is_encrypt = $this->get_option('is_encrypt', 'no');
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_filter('woocommerce_settings_api_sanitized_fields_' . $this->id, array($this, 'angelleye_braintree_encrypt_gateway_api'), 10, 1);
        $this->response = '';
        if ($this->enable_braintree_drop_in) {
            add_action('wp_enqueue_scripts', array($this, 'payment_scripts'), 0);
        }
        add_action('admin_notices', array($this, 'checks'));
        add_filter( 'woocommerce_credit_card_form_fields', array($this, 'angelleye_braintree_credit_card_form_fields'), 10, 2);
        $this->customer_id;
       
    }

    /**
     * Admin Panel Options
     */
    public function admin_options() {
        ?>
        <h3><?php _e('Braintree', 'paypal-for-woocommerce'); ?></h3>
        <p><?php _e($this->method_description, 'paypal-for-woocommerce'); ?></p>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
            <script type="text/javascript">
                jQuery('#woocommerce_braintree_sandbox').change(function () {
                    sandbox = jQuery('#woocommerce_braintree_sandbox_public_key, #woocommerce_braintree_sandbox_private_key, #woocommerce_braintree_sandbox_merchant_id, #woocommerce_braintree_sandbox_merchant_account_id').closest('tr'),
                    production = jQuery('#woocommerce_braintree_public_key, #woocommerce_braintree_private_key, #woocommerce_braintree_merchant_id, #woocommerce_braintree_merchant_account_id').closest('tr');
                    if (jQuery(this).is(':checked')) {
                        sandbox.show();
                        production.hide();
                    } else {
                        sandbox.hide();
                        production.show();
                    }
                }).change();
            </script>
        </table> <?php
    }

    /**
     * Check if SSL is enabled and notify the user
     */
    public function checks() {
        if ($this->enabled == 'no') {
            return;
        }
        if (version_compare(phpversion(), '5.2.1', '<')) {
            echo '<div class="error"><p>' . sprintf(__('Braintree Error: Braintree requires PHP 5.2.1 and above. You are using version %s.', 'paypal-for-woocommerce'), phpversion()) . '</p></div>';
        } 
        if ('no' == get_option('woocommerce_force_ssl_checkout') && !class_exists('WordPressHTTPS') && $this->enable_braintree_drop_in == false && $this->sandbox == 'no') {
            echo '<div class="error"><p>' . sprintf(__('Braintree is enabled, but the <a href="%s">force SSL option</a> is disabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate - Braintree custome credit card UI will only work in sandbox mode.', 'paypal-for-woocommerce'), admin_url('admin.php?page=wc-settings&tab=checkout')) . '</p></div>';
        }
        $this->add_dependencies_admin_notices();
    }

    /**
     * Check if this gateway is enabled
     */
    public function is_available() {
        if ('yes' != $this->enabled) {
            return false;
        }
        if (!$this->merchant_id || !$this->public_key || !$this->private_key) {
            return false;
        }
        return true;
    }

    public function validate_fields() {
        if (!$this->enable_braintree_drop_in) {
            try {
                if ( isset( $_POST['wc-braintree-payment-token'] ) && 'new' !== $_POST['wc-braintree-payment-token'] ) {
                    $token_id = wc_clean( $_POST['wc-braintree-payment-token'] );
                    $token  = WC_Payment_Tokens::get( $token_id );
                    if ( $token->get_user_id() !== get_current_user_id() ) {
                        throw new Exception(__('Error processing checkout. Please try again.', 'paypal-for-woocommerce'));
                    }else {
                        return true;
                    }
                } else {
                    $card = $this->get_posted_card();
                    if (empty($card->exp_month) || empty($card->exp_year)) {
                        throw new Exception(__('Card expiration date is invalid', 'paypal-for-woocommerce'));
                    }
                    if (!ctype_digit($card->cvc)) {
                        throw new Exception(__('Card security code is invalid (only digits are allowed)', 'paypal-for-woocommerce'));
                    }
                    if (!ctype_digit($card->exp_month) || !ctype_digit($card->exp_year) || $card->exp_month > 12 || $card->exp_month < 1 || $card->exp_year < date('y')) {
                        throw new Exception(__('Card expiration date is invalid', 'paypal-for-woocommerce'));
                    }
                    if (empty($card->number) || !ctype_digit($card->number)) {
                        throw new Exception(__('Card number is invalid', 'paypal-for-woocommerce'));
                    }
                    return true;
                }
            } catch (Exception $e) {
                wc_add_notice($e->getMessage(), 'error');
                return false;
            }
            return true;
        } else {
            try {
                if (isset($_POST['braintree_token']) && !empty($_POST['braintree_token'])) {
                    return true;
                } else {
                    throw new Exception(__('Braintree payment method nonce is empty', 'paypal-for-woocommerce'));
                }
            } catch (Exception $e) {
                wc_add_notice($e->getMessage(), 'error');
                return false;
            }
            return true;
        }
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                'label' => __('Enable Braintree Payment Gateway', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'paypal-for-woocommerce'),
                'default' => __('Credit Card', 'paypal-for-woocommerce'),
                'desc_tip' => true
            ),
            'description' => array(
                'title' => __('Description', 'paypal-for-woocommerce'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'paypal-for-woocommerce'),
                'default' => 'Pay securely with your credit card.',
                'desc_tip' => true
            ),
            'enable_braintree_drop_in' => array(
                'title' => __('Enable Drop-in Payment UI', 'paypal-for-woocommerce'),
                'label' => __('Enable Drop-in Payment UI', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Rather than showing a credit card form on your checkout, this shows the form on it\'s own page, thus making the process more secure and more PCI friendly.', 'paypal-for-woocommerce'),
                'default' => 'yes'
            ),
            'sandbox' => array(
                'title' => __('Sandbox', 'paypal-for-woocommerce'),
                'label' => __('Enable Sandbox Mode', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Place the payment gateway in sandbox mode using sandbox API keys (real payments will not be taken).', 'paypal-for-woocommerce'),
                'default' => 'yes'
            ),
            'sandbox_public_key' => array(
                'title' => __('Sandbox Public Key', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => __('Get your API keys from your Braintree account.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'sandbox_private_key' => array(
                'title' => __('Sandbox Private Key', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => __('Get your API keys from your Braintree account.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'sandbox_merchant_id' => array(
                'title' => __('Sandbox Merchant ID', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => __('Get your API keys from your Braintree account.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'sandbox_merchant_account_id' => array (
                 'type' => 'text',
                 'default' => '',
                 'title' => __ ( 'Sandbox Merchant Account Id', 'paypal-for-woocommerce' ),
                 'tool_tip' => true,
                 'description' => __ ( 'NOTE: Not to be confused with the API key Merchant ID. The Merchant Account ID determines the currency that the payment is settled in. You can find your Merchant Account Id by logging into Braintree,
                                 and clicking Settings > Processing and scrolling to the bottom of the page.', 'paypal-for-woocommerce' ) 
             ),
            'public_key' => array(
                'title' => __('Live Public Key', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => __('Get your API keys from your Braintree account.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'private_key' => array(
                'title' => __('Live Private Key', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => __('Get your API keys from your Braintree account.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'merchant_id' => array(
                'title' => __('Live Merchant ID', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => __('Get your API keys from your Braintree account.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'merchant_account_id' => array (
                 'type' => 'text',
                 'default' => '',
                 'title' => __ ( 'Live Merchant Account Id', 'paypal-for-woocommerce' ),
                 'tool_tip' => true,
                 'description' => __ ( 'NOTE: Not to be confused with the API key Merchant ID. The Merchant Account ID determines the currency that the payment is settled in. You can find your Merchant Account Id by logging into Braintree,
                                 and clicking Settings > Processing and scrolling to the bottom of the page.', 'paypal-for-woocommerce' ) 
             ),
            'enable_tokenized_payments' => array(
                'title' => __('Enable Tokenized Payments', 'paypal-for-woocommerce'),
                'label' => __('Enable Tokenized Payments', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Allow buyers to securely save payment details to their account for quick checkout / auto-ship orders in the future.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'class' => 'enable_tokenized_payments'
            ),
            'card_icon' => array(
                'title' => __('Card Icon', 'paypal-for-woocommerce'),
                'type' => 'text',
                'default' => plugins_url('/assets/images/cards.png', plugin_basename(dirname(__FILE__)))
            ),
            'debug' => array(
                'title' => __('Debug Log', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'paypal-for-woocommerce'),
                'default' => 'no',
                'description' => sprintf( __( 'Log PayPal/Braintree events, inside <code>%s</code>', 'paypal-for-woocommerce' ), wc_get_log_file_path( 'braintree' ) )
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

    public function payment_fields() {
        global $woocommerce;
        if ($this->description) {
            echo wpautop(wptexturize($this->description));
        }
        $this->tokenization_script();
        if ($this->enable_braintree_drop_in) {
            $this->angelleye_braintree_lib();
            $this->add_log('Begin Braintree_ClientToken::generate Request');
            try {
                if(is_user_logged_in()) {
                    $customer_id = get_current_user_id();
                    $braintree_customer_id = get_user_meta($customer_id, 'braintree_customer_id', true);
                    if( !empty($braintree_customer_id) ) {
                        $clientToken = Braintree_ClientToken::generate(array('customerId' => $braintree_customer_id));
                    } else {
                        $clientToken = Braintree_ClientToken::generate();
                    }
                } else {
                    $clientToken = Braintree_ClientToken::generate();
                }
                
            } catch (Braintree_Exception_Authentication $e ) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->add_log("Braintree_ClientToken::generate Exception: API keys are incorrect, Please double-check that you haven't accidentally tried to use your sandbox keys in production or vice-versa.");
                wp_redirect($woocommerce->cart->get_cart_url());
                exit;
            } catch (Braintree_Exception_Authorization $e) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->add_log("Braintree_ClientToken::generate Exception: The API key that you're using is not authorized to perform the attempted action according to the role assigned to the user who owns the API key.");
                wp_redirect($woocommerce->cart->get_cart_url());
                exit;
            } catch (Braintree_Exception_DownForMaintenance $e) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->add_log("Braintree_ClientToken::generate Exception: Request times out.");
                wp_redirect($woocommerce->cart->get_cart_url());
                exit;
            } catch (Braintree_Exception_ServerError $e) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->add_log("Braintree_ClientToken::generate Braintree_Exception_ServerError" . $e->getMessage());
                wp_redirect($woocommerce->cart->get_cart_url());
                exit;
            } catch (Braintree_Exception_SSLCertificate $e) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->add_log("Braintree_ClientToken::generate Braintree_Exception_SSLCertificate" . $e->getMessage());
                wp_redirect($woocommerce->cart->get_cart_url());
                exit;
            } catch (Exception $ex) {
                $this->add_log("Braintree_ClientToken::generate Exception:" . $ex->getMessage());
                wp_redirect($woocommerce->cart->get_cart_url());
                exit;
            }
            
            ?>
        <div id="braintree-cc-form" class="wc-payment-form">
                <fieldset>
                    <div id="braintree-payment-form"></div>
                </fieldset>
            </div>
            <script>
                var $form = jQuery('form.checkout, #order_review');
                var ccForm = jQuery('form.checkout');
                var clientToken = "<?php echo $clientToken; ?>";
                braintree.setup(clientToken, "dropin", {
                    container: "braintree-payment-form",
                    onReady : function() {
                        jQuery.each(jQuery('#braintree-payment-form').children('iFrame'),
                        function(index) {
                                if (index > 0) {
                                        jQuery(this).remove();
                                }
                        });
                    },
                    onError: function (a) {
                        if ("VALIDATION" === a.type) {
                            if (is_angelleye_braintree_selected()) {
                                console.log("configuration error " + a.message);
                                jQuery( '.woocommerce-error, .braintree-token', ccForm ).remove();
                                ccForm.prepend('<ul class="woocommerce-error"><li>' + a.message + '</li></ul>');
                                return $form.unblock();
                            }
                        } else {
                            jQuery( '.woocommerce-error, .braintree-token', ccForm ).remove();
                            ccForm.prepend('<ul class="woocommerce-error"><li>' + a.message + '</li></ul>');
                            console.log("configuration error " + a.message);
                            return $form.unblock();
                        }
                    },
                    onPaymentMethodReceived: function (obj) {
                        braintreeResponseHandler(obj);
                    }
                });

                function is_angelleye_braintree_selected() {
                    if (jQuery('#payment_method_braintree').is(':checked')) {
                        return true;
                    } else {
                        return false;
                    }
                }
                function braintreeResponseHandler(obj) {
                    var $form = jQuery('form.checkout, #order_review, #add_payment_method'),
                            ccForm = jQuery('#braintree-cc-form');
                    if (obj.nonce) {
                        jQuery( '.woocommerce-error, .braintree-token', ccForm ).remove();
                        ccForm.append('<input type="hidden" class="braintree-token" name="braintree_token" value="' + obj.nonce + '"/>');
                        $form.submit();
                    }
                }
                jQuery('form.checkout').on('checkout_place_order_braintree', function () {
                    return braintreeFormHandler();
                });
                function braintreeFormHandler() {
                   if (jQuery('#payment_method_braintree').is(':checked')) {
                            if (0 === jQuery('input.braintree-token').size()) {
                                return false;
                            }
                        }
                    return true;
                    
                }
            </script>
            <?php
        } else {
            parent::payment_fields();
            do_action('payment_fields_saved_payment_methods', $this);
        }
    }

    private function get_posted_card() {
        $card_number = isset($_POST['braintree-card-number']) ? wc_clean($_POST['braintree-card-number']) : '';
        $card_cvc = isset($_POST['braintree-card-cvc']) ? wc_clean($_POST['braintree-card-cvc']) : '';
        $card_expiry = isset($_POST['braintree-card-expiry']) ? wc_clean($_POST['braintree-card-expiry']) : '';
        $card_number = str_replace(array(' ', '-'), '', $card_number);
        $card_expiry = array_map('trim', explode('/', $card_expiry));
        $card_exp_month = str_pad($card_expiry[0], 2, "0", STR_PAD_LEFT);
        $card_exp_year = isset($card_expiry[1]) ? $card_expiry[1] : '';
        if (strlen($card_exp_year) == 2) {
            $card_exp_year += 2000;
        }
        return (object) array(
                    'number' => $card_number,
                    'type' => '',
                    'cvc' => $card_cvc,
                    'exp_month' => $card_exp_month,
                    'exp_year' => $card_exp_year,
        );
    }

    /**
     * Process the payment
     */
    public function process_payment($order_id) {
        $order = new WC_Order($order_id);
        $success = $this->angelleye_do_payment($order);
        if($success == true) {
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        } else {
            WC()->session->reload_checkout = true;
            return array(
                'result'   => 'fail',
                'redirect' => ''
            );
        }
    }

    public function angelleye_do_payment($order) {
        $success = true;
        global $woocommerce;
        try {
            if ($this->enable_braintree_drop_in) {
                $payment_method_nonce = self::get_posted_variable( 'braintree_token' );
		if ( empty( $payment_method_nonce ) ) {
                    $this->add_log("Error: The payment_method_nonce was unexpectedly empty" );
                    wc_add_notice( __( 'Error: PayPal Powered by Braintree did not supply a payment nonce. Please try again later or use another means of payment.', 'paypal-for-woocommerce' ), 'error' );
                    return false;
		}
            }
            $request_data = array();
            $this->angelleye_braintree_lib();
            $card = $this->get_posted_card();
            $request_data['billing'] = array(
                'firstName' => $order->billing_first_name,
                'lastName' => $order->billing_last_name,
                'company' => $order->billing_company,
                'streetAddress' => $order->billing_address_1,
                'extendedAddress' => $order->billing_address_2,
                'locality' => $order->billing_city,
                'region' => $order->billing_state,
                'postalCode' => $order->billing_postcode,
                'countryCodeAlpha2' => $order->billing_country,
            );
            $request_data['shipping'] = array(
                'firstName' => $order->shipping_first_name,
                'lastName' => $order->shipping_last_name,
                'company' => $order->shipping_company,
                'streetAddress' => $order->shipping_address_1,
                'extendedAddress' => $order->shipping_address_2,
                'locality' => $order->shipping_city,
                'region' => $order->shipping_state,
                'postalCode' => $order->shipping_postcode,
                'countryCodeAlpha2' => $order->shipping_country,
            );
            if ($this->enable_braintree_drop_in == false) {
                if( (!empty($_POST['wc-braintree-payment-token']) && $_POST['wc-braintree-payment-token'] == 'new') || empty($_POST['wc-braintree-payment-token'])) {
                        $request_data['creditCard'] = array(
                            'number' => $card->number,
                            'expirationDate' => $card->exp_month . '/' . $card->exp_year,
                            'cvv' => $card->cvc,
                            'cardholderName' => $order->billing_first_name . ' ' . $order->billing_last_name
                        );
                } else if( is_user_logged_in() && (!empty($_POST['wc-braintree-payment-token']) && $_POST['wc-braintree-payment-token'] != 'new') ) {
                        $customer_id = get_current_user_id();
                        $token_id = wc_clean( $_POST['wc-braintree-payment-token'] );
                        $token = WC_Payment_Tokens::get( $token_id );
                        $braintree_customer_id = get_user_meta($customer_id, 'braintree_customer_id', true);
                        $request_data['paymentMethodToken'] = $token->get_token();
                    
                } 
            } else {
                $request_data['paymentMethodNonce'] = $payment_method_nonce;
            }
            if(is_user_logged_in()) {
                $customer_id = get_current_user_id();
                $braintree_customer_id = get_user_meta($customer_id, 'braintree_customer_id', true);
                if( !empty($braintree_customer_id) ) {
                    $request_data['customerId'] = $braintree_customer_id;
                } else {
                    $request_data['customer'] = array(
                        'firstName' => $order->billing_first_name,
                        'lastName' => $order->billing_last_name,
                        'company' => $order->billing_company,
                        'phone' => $order->billing_phone,
                        'email' => $order->billing_email,
                    );
                }
            }
            $request_data['amount'] = number_format($order->get_total(), 2, '.', '');
            if( isset($this->merchant_account_id) && !empty($this->merchant_account_id)) {
                 $request_data['merchantAccountId'] = $this->merchant_account_id;
             }
            $request_data['orderId'] = $order->get_order_number();
            $request_data['options'] = $this->get_braintree_options();
            $request_data['channel'] = 'AngellEYEPayPalforWoo_BT';
            if ($this->debug) {
                $this->add_log('Begin Braintree_Transaction::sale request');
                $this->add_log('Order: ' . print_r($order->get_order_number(), true));
                $log = $request_data;
                if ($this->enable_braintree_drop_in == false) {
                    $log['creditCard'] = array(
                        'number' => '**** **** **** ****',
                        'expirationDate' => '**' . '/' . '****',
                        'cvv' => '***'
                    );
                } else {
                    $log['paymentMethodNonce'] = '*********************';
                }
                $this->add_log('Braintree_Transaction::sale Reuest Data ' . print_r($log, true));
            }
            
            try {
                $this->response = Braintree_Transaction::sale($request_data);
            } catch (Braintree_Exception_Authentication $e ) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->add_log("Braintree_Transaction::sale Braintree_Exception_Authentication: API keys are incorrect, Please double-check that you haven't accidentally tried to use your sandbox keys in production or vice-versa.");
                return $success = false;
            } catch (Braintree_Exception_Authorization $e) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->add_log("Braintree_Transaction::sale Braintree_Exception_Authorization: The API key that you're using is not authorized to perform the attempted action according to the role assigned to the user who owns the API key.");
                return $success = false;
            } catch (Braintree_Exception_DownForMaintenance $e) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->add_log("Braintree_Transaction::sale Braintree_Exception_DownForMaintenance: Request times out.");
                return $success = false;
            } catch (Braintree_Exception_ServerError $e) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->add_log("Braintree_Transaction::sale Braintree_Exception_ServerError " . $e->getMessage());
                return $success = false;
            } catch (Braintree_Exception_SSLCertificate $e) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->add_log("Braintree_Transaction::sale Braintree_Exception_SSLCertificate " . $e->getMessage());
                return $success = false;
            } catch (Exception $e) {
                wc_add_notice( __( 'Error: PayPal Powered by Braintree was unable to complete the transaction. Please try again later or use another means of payment.', 'paypal-for-woocommerce' ), 'error' );
                $this->add_log('Error: Unable to complete transaction. Reason: ' . $e->getMessage() );
                return $success = false;
            }
            
            if ( !$this->response->success ) {
                $notice = sprintf( __( 'Error: PayPal Powered by Braintree was unable to complete the transaction. Please try again later or use another means of payment. Reason: %s', 'paypal-for-woocommerce' ), $this->response->message );
                wc_add_notice( $notice, 'error' );
                $this->add_log( "Error: Unable to complete transaction. Reason: {$this->response->message}" );
                return $success = false;
            }
            
            $this->add_log('Braintree_Transaction::sale Response code: ' . print_r($this->get_status_code(), true));
            $this->add_log('Braintree_Transaction::sale Response message: ' . print_r($this->get_status_message(), true));
            
            $maybe_settled_later = array(
			'settling',
			'settlement_pending',
			'submitted_for_settlement',
		);
            
            if (in_array( $this->response->transaction->status, $maybe_settled_later )) {
                $is_sandbox = $this->sandbox == 'no' ? false : true;
                update_post_meta($order->id, 'is_sandbox', $is_sandbox);
                $order->payment_complete($this->response->transaction->id);
                do_action('before_save_payment_token', $order->id);
                if((!empty($_POST['wc-braintree-payment-token']) && $_POST['wc-braintree-payment-token'] == 'new') || ( $this->enable_braintree_drop_in && $this->supports( 'tokenization' ))) {
                    if((!empty($_POST['wc-braintree-new-payment-method']) && $_POST['wc-braintree-new-payment-method'] == true) || ($this->enable_braintree_drop_in && $this->supports( 'tokenization' ))) {
                        try {
                            $transaction = Braintree_Transaction::find($this->response->transaction->id);
                            if( !empty($transaction->creditCard) && !empty($transaction->customer['id'])) {
                                $customer_id =  $order->get_user_id();
                                update_user_meta($customer_id, 'braintree_customer_id', $transaction->customer['id']);
                                $payment_method_token = $transaction->creditCard['token'];
                                $wc_existing_token = $this->get_token_by_token( $payment_method_token );
                                if( $wc_existing_token == null ) {
                                    $token = new WC_Payment_Token_CC();
                                    $token->set_user_id( $customer_id );
                                    $token->set_token( $payment_method_token );
                                    $token->set_gateway_id( $this->id );
                                    $token->set_card_type( $transaction->creditCard['cardType']);
                                    $token->set_last4( $transaction->creditCard['last4'] );
                                    $token->set_expiry_month( $transaction->creditCard['expirationMonth'] );
                                    $token->set_expiry_year( $transaction->creditCard['expirationYear'] );
                                    $save_result = $token->save();
                                    if ( $save_result ) {
                                        $order->add_payment_token( $token );
                                    }
                                } else {
                                    $order->add_payment_token( $wc_existing_token );
                                }
                            }
                            
                        } catch (Braintree_Exception_NotFound $e) {
                            $this->add_log("Braintree_Transaction::find Braintree_Exception_NotFound: " . $e->getMessage());
                            return new WP_Error(404, $e->getMessage());
                        } catch (Exception $ex) {
                            $this->add_log("Braintree_Transaction::find Exception: " . $e->getMessage());
                            return new WP_Error(404, $e->getMessage());
                        }
                    }
                }
                $order->add_order_note(sprintf(__('%s payment approved! Trnsaction ID: %s', 'paypal-for-woocommerce'), $this->title, $this->response->transaction->id));
                WC()->cart->empty_cart();
            } else {
                $this->add_log( sprintf( 'Info: unhandled transaction id = %s, status = %s', $this->response->transaction->id, $this->response->transaction->status ) );
		$order->update_status( 'on-hold', sprintf( __( 'Transaction was submitted to PayPal Braintree but not handled by WooCommerce order, transaction_id: %s, status: %s. Order was put in-hold.', 'paypal-for-woocommerce' ), $this->response->transaction->id, $this->response->transaction->status ) );
            }
        } catch (Exception $ex) {
            wc_add_notice($ex->getMessage(), 'error');
            return $success = false;
        }
        return $success;
    }

    public function get_braintree_options() {
        return array('submitForSettlement' => true, 'storeInVaultOnSuccess' => 'true');
    }

    public static function multibyte_loaded() {
        return extension_loaded('mbstring');
    }

    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        if (!$order || !$order->get_transaction_id()) {
            return false;
        }
        
        $this->angelleye_braintree_lib();
        
        try {
            $transaction = Braintree_Transaction::find($order->get_transaction_id());
        } catch (Braintree_Exception_NotFound $e) {
            $this->add_log("Braintree_Transaction::find Braintree_Exception_NotFound" . $e->getMessage());
            return new WP_Error(404, $e->getMessage());
        } catch (Braintree_Exception_Authentication $e ) {
            $this->add_log("Braintree_Transaction::find Braintree_Exception_Authentication: API keys are incorrect, Please double-check that you haven't accidentally tried to use your sandbox keys in production or vice-versa.");
            return new WP_Error(404, $e->getMessage());
        } catch (Braintree_Exception_Authorization $e) {
            $this->add_log("Braintree_Transaction::find Braintree_Exception_Authorization: The API key that you're using is not authorized to perform the attempted action according to the role assigned to the user who owns the API key.");
            return new WP_Error(404, $e->getMessage());
        } catch (Braintree_Exception_DownForMaintenance $e) {
            $this->add_log("Braintree_Transaction::find Braintree_Exception_DownForMaintenance: Request times out.");
            return new WP_Error(404, $e->getMessage());
        } catch (Exception $e) {
            $this->add_log($e->getMessage());
            return new WP_Error(404, $e->getMessage());
        }
    
        if (isset($transaction->status) && $transaction->status == 'submitted_for_settlement') {
            if ($amount == $order->get_total()) {
                try {
                    $result = Braintree_Transaction::void($order->get_transaction_id());
                    if ($result->success) {
                        $order->add_order_note(sprintf(__('Refunded %s - Transaction ID: %s', 'paypal-for-woocommerce'), wc_price(number_format($amount, 2, '.', '')), $result->transaction->id));
                        return true;
                    } else {
                        $error = '';
                        foreach (($result->errors->deepAll()) as $error) {
                            return new WP_Error(404, 'ec_refund-error', $error->message);
                        }
                    }
                } catch (Braintree_Exception_NotFound $e) {
                    $this->add_log("Braintree_Transaction::void Braintree_Exception_NotFound: " . $e->getMessage());
                    return new WP_Error(404, $e->getMessage());
                } catch (Exception $ex) {
                    $this->add_log("Braintree_Transaction::void Exception: " . $e->getMessage());
                    return new WP_Error(404, $e->getMessage());
                }
                
            } else {
                return new WP_Error(404, 'braintree_refund-error', __('Oops, you cannot partially void this order. Please use the full order amount.', 'paypal-for-woocommerce'));
            }
        } elseif (isset($transaction->status) && ($transaction->status == 'settled' || $transaction->status == 'settling')) {
            try {
                $result = Braintree_Transaction::refund($order->get_transaction_id(), $amount);
                if ($result->success) {
                    $order->add_order_note(sprintf(__('Refunded %s - Transaction ID: %s', 'paypal-for-woocommerce'), wc_price(number_format($amount, 2, '.', '')), $result->transaction->id));
                    return true;
                } else {
                    $error = '';
                    foreach (($result->errors->deepAll()) as $error) {
                        return new WP_Error(404, 'ec_refund-error', $error->message);
                    }
                }
            } catch (Braintree_Exception_NotFound $e) {
                $this->add_log("Braintree_Transaction::refund Braintree_Exception_NotFound: " . $e->getMessage());
                return new WP_Error(404, $e->getMessage());
            } catch (Exception $ex) {
                $this->add_log("Braintree_Transaction::refund Exception: " . $e->getMessage());
                return new WP_Error(404, $e->getMessage());
            }
        } else {
            $this->add_log("Error: The transaction cannot be voided nor refunded in its current state: state = {$transaction->status}" );
            return new WP_Error ( 404, "Error: The transaction cannot be voided nor refunded in its current state: state = {$transaction->status}" );
        }
    }

    public function angelleye_braintree_lib() {
        try {
            require_once( 'lib/Braintree/Braintree.php' );
            Braintree_Configuration::environment($this->environment);
            Braintree_Configuration::merchantId($this->merchant_id);
            Braintree_Configuration::publicKey($this->public_key);
            Braintree_Configuration::privateKey($this->private_key);
        } catch (Exception $ex) {
            $this->add_log('Error: Unable to Load Braintree. Reason: ' . $ex->getMessage() );
            WP_Error(404, 'Error: Unable to Load Braintree. Reason: ' . $ex->getMessage());
        }
        
    }

    public function add_dependencies_admin_notices() {
        $missing_extensions = $this->get_missing_dependencies();
        if (count($missing_extensions) > 0) {
            $message = sprintf(
                    _n(
                            '%s requires the %s PHP extension to function.  Contact your host or server administrator to configure and install the missing extension.', '%s requires the following PHP extensions to function: %s.  Contact your host or server administrator to configure and install the missing extensions.', count($missing_extensions), 'paypal-for-woocommerce'
                    ), "PayPal For WooCoomerce - Braintree", '<strong>' . implode(', ', $missing_extensions) . '</strong>'
            );
            echo '<div class="error"><p>' . $message . '</p></div>';
        }
    }

    public function get_missing_dependencies() {
        $missing_extensions = array();
        foreach ($this->get_dependencies() as $ext) {
            if (!extension_loaded($ext)) {
                $missing_extensions[] = $ext;
            }
        }
        return $missing_extensions;
    }

    public function get_dependencies() {
        return array('curl', 'dom', 'hash', 'openssl', 'SimpleXML', 'xmlwriter');
    }

    public function add_log($message) {
        if ($this->debug == 'yes') {
            if (empty($this->log))
                $this->log = new WC_Logger();
            $this->log->add('braintree', $message);
        }
    }

    public function get_status_code() {
        if ($this->response->success) {
            return $this->get_success_status_info('code');
        } else {
            return $this->get_failure_status_info('code');
        }
    }

    public function get_status_message() {
        if ($this->response->success) {
            return $this->get_success_status_info('message');
        } else {
            return $this->get_failure_status_info('message');
        }
    }

    public function get_success_status_info($type) {
        $transaction = !empty($this->response->transaction) ? $this->response->transaction : $this->response->creditCardVerification;
        if (isset($transaction->processorSettlementResponseCode) && !empty($transaction->processorSettlementResponseCode)) {
            $status = array(
                'code' => $transaction->processorSettlementResponseCode,
                'message' => $transaction->processorSettlementResponseText,
            );
        } else {
            $status = array(
                'code' => $transaction->processorResponseCode,
                'message' => $transaction->processorResponseText,
            );
        }
        return isset($status[$type]) ? $status[$type] : null;
    }

    public function get_failure_status_info($type) {
        if ($this->has_validation_errors()) {
            $errors = $this->get_validation_errors();
            return implode(', ', ( 'code' === $type ? array_keys($errors) : array_values($errors)));
        }
        $transaction = !empty($this->response->transaction) ? $this->response->transaction : $this->response->creditCardVerification;
        switch ($transaction->status) {
            case 'gateway_rejected':
                $status = array(
                    'code' => $transaction->gatewayRejectionReason,
                    'message' => $this->response->message,
                );
                break;
            case 'processor_declined':
                $status = array(
                    'code' => $transaction->processorResponseCode,
                    'message' => $transaction->processorResponseText . (!empty($transaction->additionalProcessorResponse) ? ' (' . $transaction->additionalProcessorResponse . ')' : '' ),
                );
                break;
            case 'settlement_declined':
                $status = array(
                    'code' => $transaction->processorSettlementResponseCode,
                    'message' => $transaction->processorSettlementResponseText,
                );
                break;
            default:
                $status = array(
                    'code' => $transaction->status,
                    'message' => $this->response->message,
                );
        }
        return isset($status[$type]) ? $status[$type] : null;
    }

    public function has_validation_errors() {
        return isset($this->response->errors) && $this->response->errors->deepSize();
    }

    public function get_validation_errors() {
        $errors = array();
        if ($this->has_validation_errors()) {
            foreach ($this->response->errors->deepAll() as $error) {
                $errors[$error->code] = $error->message;
            }
        }
        return $errors;
    }

    public function get_user_message($message_id) {
        $message = null;
        switch ($message_id) {
            case 'error': $message = __('An error occurred, please try again or try an alternate form of payment', 'paypal-for-woocommerce');
                break;
            case 'decline': $message = __('We cannot process your order with the payment information that you provided. Please use a different payment account or an alternate payment method.', 'paypal-for-woocommerce');
                break;
            case 'held_for_review': $message = __('This order is being placed on hold for review. Please contact us to complete the transaction.', 'paypal-for-woocommerce');
                break;
            case 'held_for_incorrect_csc': $message = __('This order is being placed on hold for review due to an incorrect card verification number.  You may contact the store to complete the transaction.', 'paypal-for-woocommerce');
                break;
            case 'csc_invalid': $message = __('The card verification number is invalid, please try again.', 'paypal-for-woocommerce');
                break;
            case 'csc_missing': $message = __('Please enter your card verification number and try again.', 'paypal-for-woocommerce');
                break;
            case 'card_type_not_accepted': $message = __('That card type is not accepted, please use an alternate card or other form of payment.', 'paypal-for-woocommerce');
                break;
            case 'card_type_invalid': $message = __('The card type is invalid or does not correlate with the credit card number.  Please try again or use an alternate card or other form of payment.', 'paypal-for-woocommerce');
                break;
            case 'card_type_missing': $message = __('Please select the card type and try again.', 'paypal-for-woocommerce');
                break;
            case 'card_number_type_invalid': $message = __('The card type is invalid or does not correlate with the credit card number.  Please try again or use an alternate card or other form of payment.', 'paypal-for-woocommerce');
                break;
            case 'card_number_invalid': $message = __('The card number is invalid, please re-enter and try again.', 'paypal-for-woocommerce');
                break;
            case 'card_number_missing': $message = __('Please enter your card number and try again.', 'paypal-for-woocommerce');
                break;
            case 'card_expiry_invalid': $message = __('The card expiration date is invalid, please re-enter and try again.', 'paypal-for-woocommerce');
                break;
            case 'card_expiry_month_invalid': $message = __('The card expiration month is invalid, please re-enter and try again.', 'paypal-for-woocommerce');
                break;
            case 'card_expiry_year_invalid': $message = __('The card expiration year is invalid, please re-enter and try again.', 'paypal-for-woocommerce');
                break;
            case 'card_expiry_missing': $message = __('Please enter your card expiration date and try again.', 'paypal-for-woocommerce');
                break;
            case 'bank_aba_invalid': $message_id = __('The bank routing number is invalid, please re-enter and try again.', 'paypal-for-woocommerce');
                break;
            case 'bank_account_number_invalid': $message_id = __('The bank account number is invalid, please re-enter and try again.', 'paypal-for-woocommerce');
                break;
            case 'card_expired': $message = __('The provided card is expired, please use an alternate card or other form of payment.', 'paypal-for-woocommerce');
                break;
            case 'card_declined': $message = __('The provided card was declined, please use an alternate card or other form of payment.', 'paypal-for-woocommerce');
                break;
            case 'insufficient_funds': $message = __('Insufficient funds in account, please use an alternate card or other form of payment.', 'paypal-for-woocommerce');
                break;
            case 'card_inactive': $message = __('The card is inactivate or not authorized for card-not-present transactions, please use an alternate card or other form of payment.', 'paypal-for-woocommerce');
                break;
            case 'credit_limit_reached': $message = __('The credit limit for the card has been reached, please use an alternate card or other form of payment.', 'paypal-for-woocommerce');
                break;
            case 'csc_mismatch': $message = __('The card verification number does not match. Please re-enter and try again.', 'paypal-for-woocommerce');
                break;
            case 'avs_mismatch': $message = __('The provided address does not match the billing address for cardholder. Please verify the address and try again.', 'paypal-for-woocommerce');
                break;
        }
        return apply_filters('wc_payment_gateway_transaction_response_user_message', $message, $message_id, $this);
    }

    public function get_message() {
        $messages = array();
        $message_id = array();
        $decline_codes = array(
            'cvv' => 'csc_mismatch',
            'avs' => 'avs_mismatch',
            '2000' => 'card_declined',
            '2001' => 'insufficient_funds',
            '2002' => 'credit_limit_reached',
            '2003' => 'card_declined',
            '2004' => 'card_expired',
            '2005' => 'card_number_invalid',
            '2006' => 'card_expiry_invalid',
            '2007' => 'card_type_invalid',
            '2008' => 'card_number_invalid',
            '2010' => 'csc_mismatch',
            '2012' => 'card_declined',
            '2013' => 'card_declined',
            '2014' => 'card_declined',
            '2016' => 'error',
            '2017' => 'card_declined',
            '2018' => 'card_declined',
            '2023' => 'card_type_not_accepted',
            '2024' => 'card_type_not_accepted',
            '2038' => 'card_declined',
            '2046' => 'card_declined',
            '2056' => 'credit_limit_reached',
            '2059' => 'avs_mismatch',
            '2060' => 'avs_mismatch',
            '2075' => 'paypal_closed',
        );
        $response_codes = $this->get_validation_errors();
        if (isset($response_codes) && !empty($response_codes) && is_array($response_codes)) {
            foreach ($response_codes as $key => $value) {
                $messages[] = isset($decline_codes[$key]) ? $this->get_user_message($key) : $value;
            }
        }
        return implode(' ', $messages);
    }

    public function payment_scripts() {
        if (!$this->is_available()) {
            return;
        }
        if( $this->enable_braintree_drop_in ) {
            wp_enqueue_script('braintree-gateway', 'https://js.braintreegateway.com/js/braintree-2.29.0.min.js', array(), WC_VERSION, false);
        }
    }
    
    public static function get_posted_variable( $variable, $default = '' ) {
        return ( isset( $_POST[$variable] ) ? $_POST[$variable] : $default );
    }
    
    function get_transaction_url( $order ) {
        $transaction_id = $order->get_transaction_id();
        if ( empty( $transaction_id ) ) {
                return false;
        }
        $is_sandbox = get_post_meta($order->id, 'is_sandbox', true);
        if ( $is_sandbox  == true ) {
            $server = "sandbox.braintreegateway.com";
        } else {
            if ( empty( $is_sandbox ) ) {
                if (  $this->sandbox == 'yes' ) {
                   $server = "sandbox.braintreegateway.com";
                } else {
                    $server = "braintreegateway.com";
                }
            } else {
                $server = "braintreegateway.com";
            }
        }
        return "https://" . $server . "/merchants/" . urlencode( $this->merchant_id ). "/transactions/" . urlencode( $transaction_id );
    }
    public function field_name( $name ) {
	return ' name="' . esc_attr( $this->id . '-' . $name ) . '" ';
    }
    public function angelleye_braintree_credit_card_form_fields($default_fields, $current_gateway_id) {
        if($current_gateway_id == $this->id) {
		$fields = array(
                    'card-number-field' => '<p class="form-row form-row-wide">
                        <label for="' . esc_attr( $this->id ) . '-card-number">' . __( 'Card number', 'woocommerce' ) . ' <span class="required">*</span></label>
                        <input id="' . esc_attr( $this->id ) . '-card-number" class="input-text wc-credit-card-form-card-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ' . $this->field_name( 'card-number' ) . ' />
                    </p>',
                    'card-expiry-field' => '<p class="form-row form-row-first">
                        <label for="' . esc_attr( $this->id ) . '-card-expiry">' . __( 'Expiry (MM/YY)', 'woocommerce' ) . ' <span class="required">*</span></label>
                        <input id="' . esc_attr( $this->id ) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" inputmode="numeric" autocomplete="cc-exp" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="' . esc_attr__( 'MM / YY', 'woocommerce' ) . '" ' . $this->field_name( 'card-expiry' ) . ' />
                    </p>',
                    '<p class="form-row form-row-last">
                        <label for="' . esc_attr( $this->id ) . '-card-cvc">' . __( 'Card code', 'woocommerce' ) . ' <span class="required">*</span></label>
                        <input id="' . esc_attr( $this->id ) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="' . esc_attr__( 'CVC', 'woocommerce' ) . '" ' . $this->field_name( 'card-cvc' ) . ' style="width:100px" />
                    </p>'
		);
                return $fields;
        } else {
            return $default_fields;
        }
    }
    
    public function get_token_by_token( $token_id, $token_result = null ) {
        global $wpdb;
        if ( is_null( $token_result ) ) {
            $token_result = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE token = %s",
                $token_id
            ) );
            if ( empty( $token_result ) ) {
                    return null;
            }
        }
        $token_class = 'WC_Payment_Token_' . $token_result->type;
        if ( class_exists( $token_class ) ) {
            $meta =  get_metadata( 'payment_token', $token_result->token_id );
            $passed_meta = array();
            if ( ! empty( $meta ) ) {
                foreach( $meta as $meta_key => $meta_value ) {
                        $passed_meta[ $meta_key ] = $meta_value[0];
                }
            }
            return new $token_class( $token_result->token_id, (array) $token_result, $passed_meta );
        }
        return null;
    }
    public function add_payment_method() {
        $this->validate_fields();
        $this->angelleye_braintree_lib();
        $customer_id = get_current_user_id();
        $braintree_customer_id = get_user_meta($customer_id, 'braintree_customer_id', true);
        if( !empty($braintree_customer_id)) {
            $result = $this->braintree_create_payment_method($braintree_customer_id);
            if($result->success == true) {
                $return = $this->braintree_save_payment_method($customer_id, $result);
                return $return;
            }
        } else {
            $braintree_customer_id = $this->braintree_create_customer($customer_id);
            if( !empty($braintree_customer_id) ) {
                $result = $this->braintree_create_payment_method($braintree_customer_id);
                if($result->success == true) {
                    $return = $this->braintree_save_payment_method($customer_id, $result);
                    return $return;
                }
            } 
        }
    }
    
    public function braintree_create_payment_method($braintree_customer_id) {
        if($this->enable_braintree_drop_in) {
            $payment_method_nonce = self::get_posted_variable( 'braintree_token' );
            if( !empty($payment_method_nonce) ) {
                $payment_method_request = array('customerId' => $braintree_customer_id, 'paymentMethodNonce' => $payment_method_nonce, 'options' => array('failOnDuplicatePaymentMethod' => true));
                if( isset($this->merchant_account_id) && !empty($this->merchant_account_id)) {
                    $payment_method_request['options']['verificationMerchantAccountId'] = $this->merchant_account_id;
                }
            } else {
                $this->add_log("Error: The payment_method_nonce was unexpectedly empty" );
                wc_add_notice( __( 'Error: PayPal Powered by Braintree did not supply a payment nonce. Please try again later or use another means of payment.', 'paypal-for-woocommerce' ), 'error' );
                return false;
            }
        } else {
            $card = $this->get_posted_card();
            $payment_method_request = array('customerId' => $braintree_customer_id, 'cvv' => $card->cvc, 'expirationDate' => $card->exp_month . '/' . $card->exp_year,  'number' => $card->number);
            if( isset($this->merchant_account_id) && !empty($this->merchant_account_id)) {
                $payment_method_request['options']['verificationMerchantAccountId'] = $this->merchant_account_id;
            }
        }
        try {
            if($this->enable_braintree_drop_in) {
                $result = Braintree_PaymentMethod::create($payment_method_request);
            } else {
                $result = Braintree_CreditCard::create($payment_method_request);
            }
            return $result;
        } catch (Braintree_Exception_Authentication $e ) {
            wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
            $this->add_log("Braintree_ClientToken::generate Exception: API keys are incorrect, Please double-check that you haven't accidentally tried to use your sandbox keys in production or vice-versa.");
            wp_redirect(wc_get_account_endpoint_url( 'payment-methods' ));
            exit;
        } catch (Braintree_Exception_Authorization $e) {
            wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
            $this->add_log("Braintree_ClientToken::generate Exception: The API key that you're using is not authorized to perform the attempted action according to the role assigned to the user who owns the API key.");
            wp_redirect(wc_get_account_endpoint_url( 'payment-methods' ));
            exit;
        } catch (Braintree_Exception_DownForMaintenance $e) {
            wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
            $this->add_log("Braintree_ClientToken::generate Exception: Request times out.");
            wp_redirect(wc_get_account_endpoint_url( 'payment-methods' ));
            exit;
        } catch (Braintree_Exception_ServerError $e) {
            wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
            $this->add_log("Braintree_ClientToken::generate Braintree_Exception_ServerError" . $e->getMessage());
            wp_redirect(wc_get_account_endpoint_url( 'payment-methods' ));
            exit;
        } catch (Braintree_Exception_SSLCertificate $e) {
            wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
            $this->add_log("Braintree_ClientToken::generate Braintree_Exception_SSLCertificate" . $e->getMessage());
            wp_redirect(wc_get_account_endpoint_url( 'payment-methods' ));
            exit;
        } catch (Exception $ex) {
            $this->add_log("Braintree_ClientToken::generate Exception:" . $ex->getMessage());
            wp_redirect(wc_get_account_endpoint_url( 'payment-methods' ));
            exit;
        }
    }
    
    public function braintree_save_payment_method($customer_id, $result) {
        if( !empty($result->creditCard) ) {
            $braintree_method = $result->creditCard;
        } else {
            $braintree_method = $braintree_method;
        }
        update_user_meta($customer_id, 'braintree_customer_id', $braintree_method->customerId);
        $payment_method_token = $braintree_method->token;
        $wc_existing_token = $this->get_token_by_token( $payment_method_token );
        if( $wc_existing_token == null ) {
            $token = new WC_Payment_Token_CC();
            $token->set_user_id( $customer_id );
            $token->set_token( $payment_method_token );
            $token->set_gateway_id( $this->id );
            $token->set_card_type( $braintree_method->cardType);
            $token->set_last4( $braintree_method->last4 );
            $token->set_expiry_month( $braintree_method->expirationMonth );
            $token->set_expiry_year( $braintree_method->expirationYear );
            $save_result = $token->save();
            if ( $save_result ) {
                return array(
                    'result' => 'success',
                    'redirect' => wc_get_account_endpoint_url( 'payment-methods' )
                );
            } else {
                wp_redirect(wc_get_account_endpoint_url( 'payment-methods' ));
                exit;
            }
        } else {
            wp_redirect(wc_get_account_endpoint_url( 'payment-methods' ));
            exit;
        } 
    }
    
    public function braintree_create_customer($customer_id) {
        $user = get_user_by( 'id', $customer_id );
        $firstName = (get_user_meta( $customer_id, 'billing_first_name', true )) ? get_user_meta( $customer_id, 'billing_first_name', true ) : get_user_meta( $customer_id, 'shipping_first_name', true );
        $lastName = (get_user_meta( $customer_id, 'billing_last_name', true )) ? get_user_meta( $customer_id, 'billing_last_name', true ) : get_user_meta( $customer_id, 'shipping_last_name', true );
        $company = (get_user_meta( $customer_id, 'billing_company', true )) ? get_user_meta( $customer_id, 'billing_company', true ) : get_user_meta( $customer_id, 'shipping_company', true );
        $billing_email = (get_user_meta( $customer_id, 'billing_email', true )) ? get_user_meta( $customer_id, 'billing_email', true ) : get_user_meta( $customer_id, 'shipping_last_name', true );
        $billing_email = ($billing_email) ? $billing_email : $user->user_email;
        $firstName = ($firstName) ? $firstName : $user->first_name;
        $lastName = ($lastName) ? $lastName : $user->last_name;
        $create_customer_request = array('firstName' => $firstName,
            'lastName' => $lastName,
            'company' => $company,
            'email' => $billing_email,
            'phone' => '',
            'fax' => '',
            'website' => ''
        );
        $result = Braintree_Customer::create($create_customer_request);
        if($result->success == true) {
            if(!empty($result->customer->id)) {
                update_user_meta($customer_id, 'braintree_customer_id', $result->customer->id);
                return $result->customer->id;
            }
        }
    }
    
    public function angelleye_braintree_encrypt_gateway_api($settings) {
        if( !empty($settings['is_encrypt']) ) {
            $gateway_settings_key_array = array('sandbox_public_key', 'sandbox_private_key', 'sandbox_merchant_id', 'sandbox_merchant_account_id', 'public_key', 'private_key', 'merchant_id', 'merchant_account_id');
            foreach ($gateway_settings_key_array as $gateway_settings_key => $gateway_settings_value) {
                if( !empty( $settings[$gateway_settings_value]) ) {
                    $settings[$gateway_settings_value] = AngellEYE_Utility::crypting($settings[$gateway_settings_value], $action = 'e');
                }
            }
        }
        return $settings;
    }
}