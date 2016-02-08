<?php

/**
 * WC_Gateway_Braintree_AngellEYE class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_Braintree_AngellEYE extends WC_Payment_Gateway {

    /**
     * Constuctor
     */
    function __construct() {
        $this->id = 'braintree';
        $this->icon = apply_filters('woocommerce_braintree_icon', plugins_url('assets/images/cards.png', __DIR__));
        $this->has_fields = true;
        $this->method_title = 'Braintree';
        $this->method_description = 'Braintree Payment Gateway authorizes credit card payments and processes them securely with your merchant account.';
        $this->supports = array(
            'products',
            'refunds'
        );
        $this->available_card_types = array(
            'VISA' => 'Visa',
            'MC' => 'MasterCard',
            'AMEX' => 'American Express',
            'DISC' => 'Discover',
            'DINERS' => 'Diners',
            'JCB' => 'JCB',
        );
        $this->available_card_types = apply_filters('woocommerce_braintree_card_types', $this->available_card_types);
        $this->iso4217 = apply_filters('woocommerce_braintree_iso_currencies', array(
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
        $this->init_form_fields();
        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->sandbox = $this->get_option('sandbox');
        $this->environment = $this->sandbox == 'no' ? 'production' : 'sandbox';
        $this->merchant_id = $this->sandbox == 'no' ? $this->get_option('merchant_id') : $this->get_option('sandbox_merchant_id');
        $this->private_key = $this->sandbox == 'no' ? $this->get_option('private_key') : $this->get_option('sandbox_private_key');
        $this->public_key = $this->sandbox == 'no' ? $this->get_option('public_key') : $this->get_option('sandbox_public_key');
        $this->enable_braintree_drop_in = $this->get_option('enable_braintree_drop_in') === "yes" ? true : false;
        if ($this->enable_braintree_drop_in) {
            $this->order_button_text = __('Enter payment details', 'paypal-for-woocommerce');
        }

        add_action('admin_notices', array($this, 'checks'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action('woocommerce_api_wc_gateway_braintree_angelleye', array($this, 'return_handler'));
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
                jQuery('#woocommerce_braintree_sandbox').change(function() {
                    var sandbox = jQuery('#woocommerce_braintree_sandbox_public_key, #woocommerce_braintree_sandbox_private_key, #woocommerce_braintree_sandbox_merchant_id').closest('tr'),
                            production = jQuery('#woocommerce_braintree_public_key, #woocommerce_braintree_private_key, #woocommerce_braintree_merchant_id').closest('tr');

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
            echo '<div class="error"><p>' . sprintf(__('Braintree Error: Braintree requires PHP 5.2.1 and above. You are using version %s.', 'woocommerce'), phpversion()) . '</p></div>';
        } elseif ('no' == get_option('woocommerce_force_ssl_checkout') && !class_exists('WordPressHTTPS')) {
            echo '<div class="error"><p>' . sprintf(__('Braintree is enabled, but the <a href="%s">force SSL option</a> is disabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate - Braintree will only work in sandbox mode.', 'paypal-for-woocommerce'), admin_url('admin.php?page=wc-settings&tab=checkout')) . '</p></div>';
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
        if (!is_ssl() && 'yes' != $this->sandbox) {
            return false;
        }
        if (!$this->merchant_id || !$this->public_key || !$this->private_key) {
            return false;
        }
        return true;
    }

    /**
     * Validate the payment form
     */
    public function validate_fields() {
        if (!$this->enable_braintree_drop_in) {
            try {
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
            } catch (Exception $e) {
                wc_add_notice($e->getMessage(), 'error');
                return false;
            }
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
                'default' => __('Braintree Credit card', 'paypal-for-woocommerce'),
                'desc_tip' => true
            ),
            'description' => array(
                'title' => __('Description', 'paypal-for-woocommerce'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'paypal-for-woocommerce'),
                'default' => 'Pay securely with your credit card.',
                'desc_tip' => true
            ),
            'sandbox' => array(
                'title' => __('Sandbox', 'paypal-for-woocommerce'),
                'label' => __('Enable Sandbox Mode', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Place the payment gateway in sandbox mode using sandbox API keys (real payments will not be taken).', 'paypal-for-woocommerce'),
                'default' => 'yes'
            ),
            'enable_braintree_drop_in' => array(
                'title' => __('Enable Drop-in Payment UI', 'paypal-for-woocommerce'),
                'label' => __('Enable Drop-in Payment UI', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Rather than showing a credit card form on your checkout, this shows the form on it\'s own page, thus making the process more secure and more PCI friendly.', 'paypal-for-woocommerce'),
                'default' => 'no'
            ),
            'sandbox_merchant_id' => array(
                'title' => __('Sandbox Merchant ID', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Get your API keys from your Braintree account.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'sandbox_public_key' => array(
                'title' => __('Sandbox Public Key', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Get your API keys from your Braintree account.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'sandbox_private_key' => array(
                'title' => __('Sandbox Private Key', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Get your API keys from your Braintree account.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'merchant_id' => array(
                'title' => __('Live Merchant ID', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Get your API keys from your Braintree account.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'public_key' => array(
                'title' => __('Live Public Key', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Get your API keys from your Braintree account.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'private_key' => array(
                'title' => __('Live Private Key', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Get your API keys from your Braintree account.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
        );
        //$this->form_fields = $this->add_card_types_form_fields($this->form_fields);
    }

    public function add_card_types_form_fields($form_fields) {
        $form_fields['card_types'] = array(
            'title' => _x('Accepted Card Types', 'Supports card types', 'paypal-for-woocommerce'),
            'type' => 'multiselect',
            'desc_tip' => _x('Select which card types you accept.', 'Supports card types', 'paypal-for-woocommerce'),
            'default' => array_keys($this->get_available_card_types()),
            'class' => 'wc-enhanced-select chosen_select',
            'css' => 'width: 350px;',
            'options' => $this->get_available_card_types(),
        );
        return $form_fields;
    }

    public function get_available_card_types() {
        if (!isset($this->available_card_types)) {
            $this->available_card_types = array(
                'VISA' => 'Visa',
                'MC' => 'MasterCard',
                'AMEX' => 'American Express',
                'DISC' => 'Discover',
                'DINERS' => 'Diners',
                'JCB' => 'JCB',
            );
        }
        return apply_filters('wc_' . $this->id . '_available_card_types', $this->available_card_types);
    }

    /**
     * Initialise Credit Card Payment Form Fields
     */
    public function payment_fields() {
        if ($this->description) {
            echo wpautop(wptexturize($this->description));
        }
        if (!$this->enable_braintree_drop_in) {
            $this->credit_card_form();
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
        if ($this->enable_braintree_drop_in) {
            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            );
        } else {
            $this->angelleye_do_payment($order);
            if (is_ajax()) {
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            } else {
                wp_redirect($this->get_return_url($order));
                exit();
            }
        }
    }

    public function angelleye_do_payment($order, $payment_method_nonce = null) {
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
        if (is_null($payment_method_nonce)) {
            $request_data['creditCard'] = array(
                'number' => $card->number,
                'cardholderName' => $card->number,
                'expirationDate' => $card->exp_month . '/' . $card->exp_year,
                'cvv' => $card->cvc,
                'cardholderName' => $order->billing_first_name . ' ' . $order->billing_last_name
            );
        } else {
            $request_data['paymentMethodNonce'] = $payment_method_nonce;
        }
        $request_data['customer'] = array(
            'firstName' => $order->billing_first_name,
            'lastName' => $order->billing_last_name,
            'company' => $order->billing_company,
            'phone' => $this->str_truncate(preg_replace('/[^\d-().]/', '', $order->billing_phone), 14, ''),
            'email' => $order->billing_email,
        );
        $request_data['amount'] = number_format($order->get_total(), 2, '.', '');
        $request_data['orderId'] = $order->get_order_number();
        $request_data['options'] = $this->get_braintree_options();
        $request_data['channel'] = 'AngellEYEPayPalforWoo_BT';
        $result = Braintree_Transaction::sale($request_data);
        if ($result->success) {
            $order->payment_complete($result->transaction->id);
            $order->add_order_note(sprintf(__('%s payment approved! Trnsaction ID: %s', 'paypal-for-woocommerce'), $this->title, $result->transaction->id));
            WC()->cart->empty_cart();
        } else if ($result->transaction) {
            $order->add_order_note(sprintf(__('%s payment declined.<br />Error: %s<br />Code: %s', 'paypal-for-woocommerce'), $this->title, $result->message, $result->transaction->processorResponseCode));
        } else {
            foreach (($result->errors->deepAll()) as $error) {
                wc_add_notice("Validation error - " . $error->message, 'error');
            }
        }
    }

    public function str_truncate($string, $length, $omission = '...') {
        if (self::multibyte_loaded()) {
            if (mb_strlen($string) <= $length) {
                return $string;
            }
            $length -= mb_strlen($omission);
            return mb_substr($string, 0, $length) . $omission;
        } else {
            $string = self::str_to_ascii($string);
            if (strlen($string) <= $length) {
                return $string;
            }
            $length -= strlen($omission);
            return substr($string, 0, $length) . $omission;
        }
    }

    public function get_braintree_options() {
        return array('submitForSettlement' => true, 'storeInVaultOnSuccess' => '');
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
        $transaction = Braintree_Transaction::find($order->get_transaction_id());
        if (isset($transaction->status) && $transaction->status == 'submitted_for_settlement') {
            if ($amount == $order->get_total()) {
                $result = Braintree_Transaction::void($order->get_transaction_id());
                if ($result->success) {
                    $order->add_order_note(sprintf(__('Refunded %s - Transaction ID: %s', 'paypal-for-woocommerce'), wc_price(number_format($amount, 2, '.', '')), $result->transaction->id));
                    return true;
                } else {
                    $error = '';
                    foreach (($result->errors->deepAll()) as $error) {
                        return new WP_Error('ec_refund-error', $error->message);
                    }
                }
            } else {
                return new WP_Error('braintree_refund-error', __('Oops, you cannot partially void this order. Please use the full order amount.', 'paypal-for-woocommerce'));
            }
        } elseif (isset($transaction->status) && ($transaction->status == 'settled' || $transaction->status == 'settling')) {
            $result = Braintree_Transaction::refund($order->get_transaction_id(), $amount);
            if ($result->success) {
                $order->add_order_note(sprintf(__('Refunded %s - Transaction ID: %s', 'paypal-for-woocommerce'), wc_price(number_format($amount, 2, '.', '')), $result->transaction->id));
                return true;
            } else {
                $error = '';
                foreach (($result->errors->deepAll()) as $error) {
                    return new WP_Error('ec_refund-error', $error->message);
                }
            }
        }
    }

    /**
     * Receipt_page for showing the payment form which sends data to authorize.net
     */
    public function receipt_page($order_id) {
        if ($this->enable_braintree_drop_in) {
            $this->angelleye_braintree_lib();
            $this->home_url = is_ssl() ? home_url('/', 'https') : home_url('/'); //set the urls (cancel or return) based on SSL
            $this->relay_response_url = add_query_arg(array('wc-api' => 'WC_Gateway_Braintree_AngellEYE', 'order_id' => $order_id), $this->home_url);
            echo wpautop(__('Enter your payment details below and click "Confirm and pay" to securely pay for your order.', 'paypal-for-woocommerce'));
            ?>
            <form method="POST" action="<?php echo $this->relay_response_url; ?>">
                <div id="payment">
                    <label style="padding:10px 0 0 10px;display:block;"><?php echo $this->title . ' ' . '<div style="vertical-align:middle;display:inline-block;margin:2px 0 0 .5em;">' . $this->get_icon() . '</div>'; ?></label>
                    <div class="payment_box">
                        <?php
                        if ($this->description) {
                            echo wpautop(wptexturize($this->description));
                        }
                        ?>
                        <fieldset>
                            <div id="payment-form"></div>
                            <input type="submit" value="<?php _e('Confirm and pay', 'paypal-for-woocommerce'); ?>" class="submit buy button" style="float:right;"/>
                        </fieldset>
                    </div>
                </div>
            </form>
            <script src="https://js.braintreegateway.com/v2/braintree.js"></script>
            <script>
                var clientToken = "<?php echo($clientToken = Braintree_ClientToken::generate()); ?>";
                braintree.setup(clientToken, "dropin", {
                    container: "payment-form"
                });
            </script>
            <?php
        }
    }

    /**
     * handles return data and does redirects
     */
    public function return_handler() {
        @ob_clean();
        header('HTTP/1.1 200 OK');
        $order_id = $_GET['order_id'];
        $order = new WC_Order($order_id);
        $payment_method_nonce = $_POST['payment_method_nonce'];
        if (!isset($payment_method_nonce) || empty($payment_method_nonce)) {
            wc_add_notice(__('Oops, there was a temporary payment error. Please try another payment method or contact us to complete your transaction.', 'paypal-for-woocommerce'), 'error');
            wp_redirect($order->get_checkout_payment_url(true));
            exit();
        }
        $this->angelleye_do_payment($order, $payment_method_nonce);
        wp_redirect($this->get_return_url($order));
        exit();
    }

    public function angelleye_braintree_lib() {
        require_once( 'lib/Braintree/Braintree.php' );
        Braintree_Configuration::environment($this->environment);
        Braintree_Configuration::merchantId($this->merchant_id);
        Braintree_Configuration::publicKey($this->public_key);
        Braintree_Configuration::privateKey($this->private_key);
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

}