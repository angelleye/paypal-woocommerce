<?php
/**
 * WC_Gateway_PayPal_Pro class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_PayPal_Pro_AngellEYE extends WC_Payment_Gateway_CC
{

    /**
     * Store client
     */
    private $centinel_client = false;
    public $customer_id;
    /**
     * __construct function.
     *
     * @access public
     * @return void
     */
    function __construct()
    {
        $this->id = 'paypal_pro';
        $this->method_title = __('PayPal Website Payments Pro (DoDirectPayment) ', 'paypal-for-woocommerce');
        $this->method_description = __('PayPal Website Payments Pro allows you to accept credit cards directly on your site without any redirection through PayPal.  You host the checkout form on your own web server, so you will need an SSL certificate to ensure your customer data is protected.', 'paypal-for-woocommerce');
        $this->has_fields = true;
        $this->liveurl = 'https://api-3t.paypal.com/nvp';
        $this->testurl = 'https://api-3t.sandbox.paypal.com/nvp';
        $this->liveurl_3ds = 'https://paypal.cardinalcommerce.com/maps/txns.asp';
        $this->testurl_3ds = 'https://centineltest.cardinalcommerce.com/maps/txns.asp';
        $this->available_card_types = apply_filters('woocommerce_paypal_pro_available_card_types', array(
            'GB' => array(
                'Visa' => 'Visa',
                'MasterCard' => 'MasterCard',
                'Maestro' => 'Maestro/Switch',
                'Solo' => 'Solo'
            ),
            'US' => array(
                'Visa' => 'Visa',
                'MasterCard' => 'MasterCard',
                'Discover' => 'Discover',
                'AmEx' => 'American Express'
            ),
            'CA' => array(
                'Visa' => 'Visa',
                'MasterCard' => 'MasterCard'
            ),
            'AU' => array(
                'Visa' => 'Visa',
                'MasterCard' => 'MasterCard',
                'Discover' => 'Discover',
                'AmEx' => 'American Express'
            )
        ));
        $this->iso4217 = apply_filters('woocommerce_paypal_pro_iso_currencies', array(
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
        // Load the form fields
        $this->init_form_fields();
        // Load the settings.
        $this->init_settings();
        // Get setting values
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->api_username = $this->get_option('api_username');
        $this->api_password = $this->get_option('api_password');
        $this->api_signature = $this->get_option('api_signature');
        $this->testmode = $this->get_option('testmode');
        $this->invoice_id_prefix = $this->get_option('invoice_id_prefix');
        $this->error_email_notify = $this->get_option('error_email_notify');
        $this->error_display_type = $this->get_option('error_display_type'); 
        $this->enable_3dsecure = 'yes' === $this->get_option('enable_3dsecure', 'no');
        $this->liability_shift = 'yes' === $this->get_option('liability_shift', 'no');
        $this->debug = 'yes' === $this->get_option('debug', 'no'); 
        $this->payment_action = $this->get_option('payment_action', 'Sale');
        $this->send_items = 'yes' === $this->get_option('send_items', 'yes');
        $this->enable_notifyurl = $this->get_option('enable_notifyurl', 'no');
        $this->is_encrypt = $this->get_option('is_encrypt', 'no');
        $this->notifyurl = '';
        if($this->enable_notifyurl == 'yes') {
            $this->notifyurl = $this->get_option('notifyurl'); 
            if( isset($this->notifyurl) && !empty($this->notifyurl)) {
                $this->notifyurl =  str_replace('&amp;', '&', $this->notifyurl);
            }
        }
        $this->enable_cardholder_first_last_name = isset($this->settings['enable_cardholder_first_last_name']) && $this->settings['enable_cardholder_first_last_name'] == 'yes' ? true : false;
        // 3DS
        if ($this->enable_3dsecure) {
            $this->centinel_pid = $this->get_option('centinel_pid');
            $this->centinel_mid = $this->get_option('centinel_mid');
            $this->centinel_pwd = $this->get_option('centinel_pwd');
            if (empty($this->centinel_pid) || empty($this->centinel_mid) || empty($this->centinel_pwd))
                $this->enable_3dsecure = false;
            $this->centinel_url = $this->testmode == "no" ? $this->liveurl_3ds : $this->testurl_3ds;
        }
        
        //fix ssl for image icon
        $this->icon = $this->get_option('card_icon', plugins_url('/assets/images/cards.png', plugin_basename(dirname(__FILE__))));
        if (is_ssl()) {
            $this->icon = preg_replace("/^http:/i", "https:", $this->icon);
        }
        $this->icon = apply_filters('woocommerce_paypal_pro_icon', $this->icon);
        $this->supports = array(
            'products',
            'refunds'
	);
        $this->enable_tokenized_payments = $this->get_option('enable_tokenized_payments', 'no');
        if($this->enable_tokenized_payments == 'yes') {
            array_push($this->supports, "tokenization");
        }
        $this->Force_tls_one_point_two = get_option('Force_tls_one_point_two', 'no');

        if ($this->testmode == 'yes') {
            $this->api_username = $this->get_option('sandbox_api_username');
            $this->api_password = $this->get_option('sandbox_api_password');
            $this->api_signature = $this->get_option('sandbox_api_signature');
        }
        // Maestro
        if (!$this->enable_3dsecure) {
            unset($this->available_card_types['GB']['Maestro']);
        }
   
        // Hooks
        add_action('woocommerce_api_wc_gateway_paypal_pro_angelleye', array($this, 'handle_3dsecure'));
        /* 1.6.6 */
        add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
        /* 2.0.0 */
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_filter('woocommerce_settings_api_sanitized_fields_' . $this->id, array($this, 'angelleye_paypal_pro_encrypt_gateway_api'), 10, 1);
        if ($this->enable_cardholder_first_last_name) {
            add_action('woocommerce_credit_card_form_start', array($this, 'angelleye_woocommerce_credit_card_form_start'), 10, 1);
        }
        
        add_filter( 'woocommerce_credit_card_form_fields', array($this, 'angelleye_paypal_pro_credit_card_form_fields'), 10, 2);
       
        $this->customer_id;
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                'label' => __('Enable PayPal Pro', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'paypal-for-woocommerce'),
                'default' => __('Credit card', 'paypal-for-woocommerce')
            ),
            'description' => array(
                'title' => __('Description', 'paypal-for-woocommerce'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'paypal-for-woocommerce'),
                'default' => __('Pay with your credit card', 'paypal-for-woocommerce')
            ),
            'testmode' => array(
                'title' => __('Test Mode', 'paypal-for-woocommerce'),
                'label' => __('Enable PayPal Sandbox/Test Mode', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Place the payment gateway in development mode.', 'paypal-for-woocommerce'),
                'default' => 'no'
            ),
            'invoice_id_prefix' => array(
                'title' => __('Invoice ID Prefix', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Add a prefix to the invoice ID sent to PayPal. This can resolve duplicate invoice problems when working with multiple websites on the same PayPal account.', 'paypal-for-woocommerce'),
            ),
            'card_icon' => array(
                'title' => __('Card Icon', 'paypal-for-woocommerce'),
                'type' => 'text',
                'default' => plugins_url('/assets/images/cards.png', plugin_basename(dirname(__FILE__)))
            ),
            'error_email_notify' => array(
                'title' => __('Error Email Notifications', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable admin email notifications for errors.', 'paypal-for-woocommerce'),
                'default' => 'yes',
                'description' => __('This will send a detailed error email to the WordPress site administrator if a PayPal API error occurs.', 'paypal-for-woocommerce')
            ),
            'sandbox_api_username' => array(
                'title' => __('Sandbox API Username', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Create sandbox accounts and obtain API credentials from within your
									<a href="http://developer.paypal.com">PayPal developer account</a>.', 'paypal-for-woocommerce'),
                'default' => ''
            ),
            'sandbox_api_password' => array(
                'title' => __('Sandbox API Password', 'paypal-for-woocommerce'),
                'type' => 'password',
                'default' => ''
            ),
            'sandbox_api_signature' => array(
                'title' => __('Sandbox API Signature', 'paypal-for-woocommerce'),
                'type' => 'password',
                'default' => ''
            ),
            'api_username' => array(
                'title' => __('Live API Username', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Get your live account API credentials from your PayPal account profile under the API Access section <br />or by using
									<a target="_blank" href="https://www.paypal.com/us/cgi-bin/webscr?cmd=_login-api-run">this tool</a>.', 'paypal-for-woocommerce'),
                'default' => ''
            ),
            'api_password' => array(
                'title' => __('Live API Password', 'paypal-for-woocommerce'),
                'type' => 'password',
                'default' => ''
            ),
            'api_signature' => array(
                'title' => __('Live API Signature', 'paypal-for-woocommerce'),
                'type' => 'password',
                'default' => ''
            ),
            'enable_3dsecure' => array(
                'title' => __('3DSecure', 'paypal-for-woocommerce'),
                'label' => __('Enable 3DSecure', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Allows UK merchants to pass 3-D Secure authentication data to PayPal for debit and credit cards. Updating your site with 3-D Secure enables your participation in the Verified by Visa and MasterCard SecureCode programs. (Required to accept Maestro)', 'paypal-for-woocommerce'),
                'default' => 'no'
            ),
            'centinel_pid' => array(
                'title' => __('Centinel PID', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('If enabling 3D Secure, enter your Cardinal Centinel Processor ID.', 'paypal-for-woocommerce'),
                'default' => ''
            ),
            'centinel_mid' => array(
                'title' => __('Centinel MID', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('If enabling 3D Secure, enter your Cardinal Centinel Merchant ID.', 'paypal-for-woocommerce'),
                'default' => ''
            ),
            'centinel_pwd' => array(
                'title' => __('Transaction Password', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => __('If enabling 3D Secure, enter your Cardinal Centinel Transaction Password.', 'paypal-for-woocommerce'),
                'default' => ''
            ),
            'liability_shift' => array(
                'title' => __('Liability Shift', 'paypal-for-woocommerce'),
                'label' => __('Require liability shift', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Only accept payments when liability shift has occurred.', 'paypal-for-woocommerce'),
                'default' => 'no'
            ),
            'error_display_type' => array(
                'title' => __('Error Display Type', 'paypal-for-woocommerce'),
                'type' => 'select',
                'label' => __('Display detailed or generic errors', 'paypal-for-woocommerce'),
                'class' => 'error_display_type_option',
                'options' => array(
                    'detailed' => __('Detailed', 'paypal-for-woocommerce'),
                    'generic' => __('Generic', 'paypal-for-woocommerce')
                ),
                'description' => __('Detailed displays actual errors returned from PayPal.  Generic displays general errors that do not reveal details
									and helps to prevent fraudulant activity on your site.', 'paypal-for-woocommerce')
            ),
            'payment_action' => array(
                'title' => __('Payment Action', 'paypal-for-woocommerce'),
                'label' => __('Whether to process as a Sale or Authorization.', 'paypal-for-woocommerce'),
                'description' => __('Sale will capture the funds immediately when the order is placed.  Authorization will authorize the payment but will not capture the funds.  You would need to capture funds from within the WooCommerce order when you are ready to deliver.'),
                'type' => 'select',
                'options' => array(
                    'Sale' => 'Sale',
                    'Authorization' => 'Authorization',
                ),
                'default' => 'Sale'
            ),
            'send_items' => array(
                'title' => __('Send Item Details', 'paypal-for-woocommerce'),
                'label' => __('Send line item details to PayPal', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Include all line item details in the payment request to PayPal so that they can be seen from the PayPal transaction details page.', 'paypal-for-woocommerce'),
                'default' => 'yes'
            ),
            'enable_notifyurl' => array(
                'title' => __('Enable PayPal IPN', 'paypal-for-woocommerce'),
                'label' => __('Enable Instant Payment Notification.', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'no',
                'class' => 'angelleye_enable_notifyurl'
            ),
            'enable_tokenized_payments' => array(
                'title' => __('Enable Tokenized Payments', 'paypal-for-woocommerce'),
                'label' => __('Enable Tokenized Payments', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Allow buyers to securely save payment details to their account for quick checkout / auto-ship orders in the future.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'class' => 'enable_tokenized_payments'
            ),
            'notifyurl' => array(
                'title' => __('PayPal IPN URL', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Your URL for receiving Instant Payment Notification (IPN) about transactions.', 'paypal-for-woocommerce'),
                'class' => 'angelleye_notifyurl'
            ),
            'enable_cardholder_first_last_name' => array(
                'title' => __('Enable Cardholder Name', 'paypal-for-woocommerce'),
                'label' => __('Adds fields for "card holder name" to checkout in addition to the "billing name" fields.', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Display card holder first and last name in credit card form.', 'paypal-for-woocommerce'),
                'default' => 'no'
            ),
            'debug' => array(
                'title' => __('Debug Log', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'paypal-for-woocommerce'),
                'default' => 'no',
                'description' => sprintf( __( 'Log PayPal events, inside <code>%s</code>', 'paypal-for-woocommerce' ), wc_get_log_file_path( 'paypal-pro' ) )
            ),
            'is_encrypt' => array(
                'title' => __('', 'paypal-for-woocommerce'),
                'label' => __('', 'paypal-for-woocommerce'),
                'type' => 'hidden',
                'default' => 'yes',
                'class' => ''
            )
        );
        $this->form_fields = apply_filters('angelleye_pc_form_fields', $this->form_fields);
    }

    /**
     * Check if this gateway is enabled and available in the user's country
     *
     * This method no is used anywhere??? put above but need a fix below
     */
    function is_available()
    {
        if ($this->enabled == "yes") :
            if ($this->testmode == "no" && get_option('woocommerce_force_ssl_checkout') == 'no' && !class_exists('WordPressHTTPS')) return false;
            // Currency check
            if (!in_array(get_woocommerce_currency(), apply_filters('woocommerce_paypal_pro_supported_currencies', array('AUD', 'CAD', 'CZK', 'DKK', 'EUR', 'HUF', 'JPY', 'NOK', 'NZD', 'PLN', 'GBP', 'SGD', 'SEK', 'CHF', 'USD')))) return false;
            // Required fields check
            if (!$this->api_username || !$this->api_password || !$this->api_signature) return false;
            return isset($this->available_card_types[WC()->countries->get_base_country()]);
        endif;
        return false;
    }

    /**
     * Add a log entry
     */
    public function log($message)
    {
        if ($this->debug) {
            if (!isset($this->log)) {
                $this->log = new WC_Logger();
            }
            $this->log->add('paypal-pro', $message);
        }
    }

    /**
     * Payment form on checkout page
     */
    public function payment_fields() {
        do_action('before_angelleye_pc_payment_fields', $this);
        if ($this->description) {
            echo '<p>' . wp_kses_post($this->description);
            if ($this->testmode == "yes") {
                echo '<p>';
                _e('NOTICE: SANDBOX (TEST) MODE ENABLED.', 'paypal-for-woocommerce');
                echo '<br />';
                _e('For testing purposes you can use the card number 4916311462114485 with any CVC and a valid expiration date.', 'paypal-for-woocommerce');
                echo '</p>';
            }
        }
        parent::payment_fields();
        do_action('payment_fields_saved_payment_methods', $this);
    }

    public function paypal_for_woocommerce_paypal_pro_credit_card_form_expiration_date_selectbox($class) {
        $form_html = "";
        $form_html .= '<p class="' . $class . '">';
        $form_html .= '<label for="cc-expire-month">' . __("Expiration Date", 'paypal-for-woocommerce') . '<span class="required">*</span></label>';
        $form_html .= '<select name="paypal_pro_card_expiration_month" id="cc-expire-month" class="woocommerce-select woocommerce-cc-month mr5">';
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
        $form_html .= '<select name="paypal_pro_card_expiration_year" id="cc-expire-year" class="woocommerce-select woocommerce-cc-year ml5">';
        $form_html .= '<option value="">' . __('Year', 'paypal-for-woocommerce') . '</option>';
        for ($i = date('y'); $i <= date('y') + 15; $i++) {
            $form_html .= '<option value=' . $i . '>20' . $i . '</option>';
        }
        $form_html .= '</select>';
        $form_html .= '</p>';
        return $form_html;
    }


    /**
     * Format and get posted details
     * @return object
     */
    private function get_posted_card()
    {
        $card_number = isset($_POST['paypal_pro-card-number']) ? wc_clean($_POST['paypal_pro-card-number']) : '';
        $card_cvc = isset($_POST['paypal_pro-card-cvc']) ? wc_clean($_POST['paypal_pro-card-cvc']) : '';
        $card_exp_month = isset($_POST['paypal_pro_card_expiration_month']) ? wc_clean($_POST['paypal_pro_card_expiration_month']) : '';
        $card_exp_year = isset($_POST['paypal_pro_card_expiration_year']) ? wc_clean($_POST['paypal_pro_card_expiration_year']) : '';

        // Format values
        $card_number = str_replace(array(' ', '-'), '', $card_number);

        if (isset($_POST['paypal_pro-card-startdate'])) {
            $card_start = wc_clean($_POST['paypal_pro-card-startdate']);
            $card_start = array_map('trim', explode('/', $card_start));
            $card_start_month = str_pad($card_start[0], 2, "0", STR_PAD_LEFT);
            $card_start_year = $card_start[1];
        } else {
            $card_start_month = '';
            $card_start_year = '';
        }

        if (strlen($card_exp_year) == 2) {
            $card_exp_year += 2000;
        }

        if (strlen($card_start_year) == 2) {
            $card_start_year += 2000;
        }

        return (object)array(
            'number' => $card_number,
            'type' => '',
            'cvc' => $card_cvc,
            'exp_month' => $card_exp_month,
            'exp_year' => $card_exp_year,
            'start_month' => $card_start_month,
            'start_year' => $card_start_year
        );
    }

    public function validate_fields()
    {
        try {
            if ( isset( $_POST['wc-paypal_pro-payment-token'] ) && 'new' !== $_POST['wc-paypal_pro-payment-token'] ) {
                $token_id = wc_clean( $_POST['wc-paypal_pro-payment-token'] );
                $token  = WC_Payment_Tokens::get( $token_id );
                if ( $token->get_user_id() !== get_current_user_id() ) {
                    throw new Exception(__('Error processing checkout. Please try again.', 'paypal-for-woocommerce'));
                }else {
                    return true;
                }
            }
            $card = $this->get_posted_card();
            do_action('before_angelleye_pro_checkout_validate_fields', $card->type, $card->number, $card->cvc, $card->exp_month, $card->exp_year);
            if (empty($card->exp_month) || empty($card->exp_year)) {
                throw new Exception(__('Card expiration date is invalid', 'paypal-for-woocommerce'));
            }

            // Validate values
            if (!ctype_digit($card->cvc)) {
                throw new Exception(__('Card security code is invalid (only digits are allowed)', 'paypal-for-woocommerce'));
            }

            if (
                !ctype_digit($card->exp_month) ||
                !ctype_digit($card->exp_year) ||
                $card->exp_month > 12 ||
                $card->exp_month < 1 ||
                $card->exp_year < date('y')
            ) {
                throw new Exception(__('Card expiration date is invalid', 'paypal-for-woocommerce'));
            }

            if (empty($card->number) || !ctype_digit($card->number)) {
                throw new Exception(__('Card number is invalid', 'paypal-for-woocommerce'));
            }

            $card_type = AngellEYE_Utility::card_type_from_account_number($card->number);

            if ($card_type == 'amex' && (get_woocommerce_currency() != 'USD' && get_woocommerce_currency() != 'AUD')) {
                throw new Exception(__('Your processor is unable to process the Card Type in the currency requested. Please try another card type', 'paypal-for-woocommerce'));
            }

            do_action('after_angelleye_pro_checkout_validate_fields', $card->type, $card->number, $card->cvc, $card->exp_month, $card->exp_year);
            return true;

        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            return false;
        }
    }


    /**
     * Process the payment
     */
    function process_payment($order_id)
    {
        $order = new WC_Order($order_id);
        
        $this->log('Processing order #' . $order_id);

        $card = $this->get_posted_card();


        /**
         * 3D Secure Handling
         */
        if ($this->enable_3dsecure) {
            if (!class_exists('CentinelClient')) include_once('lib/CentinelClient.php');
            $this->clear_centinel_session();
            $this->centinel_client = new CentinelClient;
            $this->centinel_client->add("MsgType", "cmpi_lookup");
            $this->centinel_client->add("Version", "1.7");
            $this->centinel_client->add("ProcessorId", $this->centinel_pid);
            $this->centinel_client->add("MerchantId", $this->centinel_mid);
            $this->centinel_client->add("TransactionPwd", $this->centinel_pwd);
            $this->centinel_client->add("TransactionType", 'C');
            $this->centinel_client->add('OrderNumber', $order_id);
            $this->centinel_client->add('Amount', $order->get_total() * 100);
            $this->centinel_client->add('CurrencyCode', $this->iso4217[$order->get_order_currency()]);
            $this->centinel_client->add('TransactionMode', 'S');
            $this->centinel_client->add('ProductCode', 'PHY');
            $this->centinel_client->add('CardNumber', $card->number);
            WC()->session->set('CardNumber', $card->number);
            $this->centinel_client->add('CardExpMonth', $card->exp_month);
            WC()->session->set('CardExpMonth', $card->exp_month);
            $this->centinel_client->add('CardExpYear', $card->exp_year);
            WC()->session->set('CardExpYear', $card->exp_year);
            $this->centinel_client->add('CardCode', $card->cvc);
            WC()->session->set('CardCode', $card->cvc);
            $this->centinel_client->add('BillingFirstName', $order->billing_first_name);
            $this->centinel_client->add('BillingLastName', $order->billing_last_name);
            $this->centinel_client->add('BillingAddress1', $order->billing_address_1);
            $this->centinel_client->add('BillingAddress2', $order->billing_address_2);
            $this->centinel_client->add('BillingCity', $order->billing_city);
            $this->centinel_client->add('BillingState', $order->billing_state);
            $this->centinel_client->add('BillingPostalCode', $order->billing_postcode);
            $this->centinel_client->add('BillingCountryCode', $order->billing_country);
            $this->centinel_client->add('BillingPhone', $order->billing_phone);
            $this->centinel_client->add('ShippingFirstName', $order->shipping_first_name);
            $this->centinel_client->add('ShippingLastName', $order->shipping_last_name);
            $this->centinel_client->add('ShippingAddress1', $order->shipping_address_1);
            $this->centinel_client->add('ShippingAddress2', $order->shipping_address_2);
            $this->centinel_client->add('ShippingCity', $order->shipping_city);
            $this->centinel_client->add('ShippingState', $order->shipping_state);
            $this->centinel_client->add('ShippingPostalCode', $order->shipping_postcode);
            $this->centinel_client->add('ShippingCountryCode', $order->shipping_country);

            // Items
            $item_loop = 0;
            if (sizeof($order->get_items()) > 0) {
                foreach ($order->get_items() as $item) {
                    $item_loop++;
                    $this->centinel_client->add('Item_Name_' . $item_loop, $item['name']);
                    $this->centinel_client->add('Item_Price_' . $item_loop, number_format($order->get_item_total($item, true, true) * 100), 2, '.', '');
                    $this->centinel_client->add('Item_Quantity_' . $item_loop, $item['qty']);
                    $this->centinel_client->add('Item_Desc_' . $item_loop, $item['name']);
                }
            }

            // Send request
            $this->centinel_client->sendHttp($this->centinel_url, "5000", "15000");

            $this->log('Centinal client request: ' . print_r($this->centinel_client->request, true));
            $this->log('Centinal client response: ' . print_r($this->centinel_client->response, true));


            // Save response in session
            WC()->session->set('Centinel_ErrorNo', $this->get_centinel_value("ErrorNo"));
            WC()->session->set('Centinel_ErrorDesc', $this->get_centinel_value("ErrorDesc"));
            WC()->session->set('Centinel_TransactionId', $this->get_centinel_value("TransactionId"));
            WC()->session->set('Centinel_OrderId', $this->get_centinel_value("OrderId"));
            WC()->session->set('Centinel_Enrolled', $this->get_centinel_value("Enrolled"));
            WC()->session->set('Centinel_ACSUrl', $this->get_centinel_value("ACSUrl"));
            WC()->session->set('Centinel_Payload', $this->get_centinel_value("Payload"));
            WC()->session->set('Centinel_EciFlag', $this->get_centinel_value("EciFlag"));
            WC()->session->set('Centinel_card_start_month', $card->start_month);
            WC()->session->set('Centinel_card_start_year', $card->start_year);


            if ($this->get_centinel_value("ErrorNo")) {
                wc_add_notice(apply_filters('angelleye_pc_process_payment_authentication', __('Error in 3D secure authentication: ', 'woocommerce-gateway-paypal-pro') . $this->get_centinel_value("ErrorDesc")), 'error');
                return;
            }

            if ('Y' === $this->get_centinel_value("Enrolled")) {
                $this->log('Doing 3dsecure payment authorization');
                $this->log('ASCUrl: ' . $this->get_centinel_value("ACSUrl"));
                $this->log('PaReq: ' . $this->get_centinel_value("Payload"));

                return array(
                    'result' => 'success',
                    'redirect' => add_query_arg(array('acs' => $order_id), WC()->api_request_url('WC_Gateway_PayPal_Pro_AngellEYE', is_ssl()))
                );
            } elseif ($this->liability_shift && 'N' !== $this->get_centinel_value("Enrolled")) {
                wc_add_notice(apply_filters('angelleye_pc_process_payment_authentication_unavailable', __('Authentication unavailable. Please try a different payment method or card.', 'woocommerce-gateway-paypal-pro')), 'error');
                return;
            }

        }
        // Do payment with paypal
        return $this->do_payment($order, $card->number, $card->type, $card->exp_month, $card->exp_year, $card->cvc, '', '', '', '', '', $card->start_month, $card->start_year);
    }


    /**
     * Auth 3dsecure
     */
    public function handle_3dsecure()
    {
        if (!empty($_GET['acs'])) {
            $order_id = wc_clean($_GET['acs']);
            $acsurl = WC()->session->get('Centinel_ACSUrl');
            $payload = WC()->session->get('Centinel_Payload');
            ?>
            <html>
            <head>
                <title>3DSecure Payment Authorisation</title>
            </head>
            <body>
            <form name="frmLaunchACS" id="3ds_submit_form" method="POST" action="<?php echo esc_url($acsurl); ?>">
                <input type="hidden" name="PaReq" value="<?php echo esc_attr($payload); ?>">
                <input type="hidden" name="TermUrl"
                       value="<?php echo esc_attr(WC()->api_request_url('WC_Gateway_PayPal_Pro_AngellEYE', is_ssl())); ?>">
                <input type="hidden" name="MD" value="<?php echo absint($order_id); ?>">
                <noscript>
                    <input type="submit" class="button" id="3ds_submit" value="Submit"/>
                </noscript>
            </form>
            <script>
                document.frmLaunchACS.submit();
            </script>
            </body>
            </html>
            <?php
            exit;
        } else {
            $this->authorise_3dsecure();
        }
    }

    function authorise_3dsecure()
    {

        if (!class_exists('CentinelClient')) {
            include_once('lib/CentinelClient.php');
        }

        $pares = !empty($_POST['PaRes']) ? $_POST['PaRes'] : '';
        $order_id = absint(!empty($_POST['MD']) ? $_POST['MD'] : 0);
        $order = wc_get_order($order_id);
        $redirect_url = $this->get_return_url($order);

        $this->log('authorise_3dsecure() for order ' . absint($order_id));
        $this->log('authorise_3dsecure() PARes ' . print_r($pares, true));

        /******************************************************************************/
        /*                                                                            */
        /*    If the PaRes is Not Empty then process the cmpi_authenticate message    */
        /*                                                                            */
        /******************************************************************************/
        try {
            // If the PaRes is Not Empty then process the cmpi_authenticate message
            if (!empty($pares)) {

                $this->centinel_client = new CentinelClient;
                $this->centinel_client->add('MsgType', 'cmpi_authenticate');
                $this->centinel_client->add("Version", "1.7");
                $this->centinel_client->add("ProcessorId", $this->centinel_pid);
                $this->centinel_client->add("MerchantId", $this->centinel_mid);
                $this->centinel_client->add("TransactionPwd", $this->centinel_pwd);
                $this->centinel_client->add("TransactionType", 'C');
                $this->centinel_client->add('TransactionId', WC()->session->get('Centinel_TransactionId'));
                $this->centinel_client->add('PAResPayload', $pares);
                $this->centinel_client->sendHttp($this->centinel_url, "5000", "15000");

                $response_to_log = $this->centinel_client->response;
                $response_to_log['CardNumber'] = 'XXX';
                $response_to_log['CardCode'] = 'XXX';
                $this->log('Centinal transaction ID ' . WC()->session->get('Centinel_TransactionId'));
                $this->log('Centinal client request : ' . print_r($this->centinel_client->request, true));
                $this->log('Centinal client response: ' . print_r($response_to_log, true));
                $this->log('3dsecure pa_res_status: ' . $this->get_centinel_value("PAResStatus"));

            }

            if ($this->liability_shift && ($this->get_centinel_value("EciFlag") == '07' || $this->get_centinel_value("EciFlag") == '01')) {
                $order->update_status('failed', __('3D Secure error: No liability shift', 'woocommerce-gateway-paypal-pro'));
                throw new Exception(apply_filters('angelleye_pc_3d_authentication_unavailable', __('Authentication unavailable.  Please try a different payment method or card.', 'woocommerce-gateway-paypal-pro')));
            }

            if (!$this->get_centinel_value("ErrorNo") && in_array($this->get_centinel_value("PAResStatus"), array('Y', 'A', 'U')) && "Y" === $this->get_centinel_value("SignatureVerification")) {

                // If we are here we can process the card

                $card = new stdClass();
                $card->number = $this->get_centinel_value("CardNumber");
                $card->type = '';
                $card->cvc = $this->get_centinel_value("CardCode");
                $card->exp_month = $this->get_centinel_value("CardExpMonth");
                $card->exp_year = $this->get_centinel_value("CardExpYear");
                $card->start_month = WC()->session->get('Centinel_card_start_month');
                $card->start_year = WC()->session->get('Centinel_card_start_year');

                $centinel = new stdClass();
                $centinel->paresstatus = $this->get_centinel_value("PAResStatus");
                $centinel->xid = $this->get_centinel_value("Xid");
                $centinel->cavv = $this->get_centinel_value("Cavv");
                $centinel->eciflag = $this->get_centinel_value("EciFlag");
                $centinel->enrolled = WC()->session->get('Centinel_Enrolled');

                $this->do_payment($order, $card->number, $card->type, $card->exp_month, $card->exp_year, $card->cvc, $centinel->paresstatus, "Y", $centinel->cavv, $centinel->eciflag, $centinel->xid, $card->start_month, $card->start_year);
                $this->clear_centinel_session();
                wp_redirect($redirect_url);
                exit;

            } else {
                $order->update_status('failed', sprintf(apply_filters('angelleye_pc_3d_secure_authentication', __('3D Secure error: %s', 'woocommerce-gateway-paypal-pro')), $this->get_centinel_value("ErrorDesc")));
                throw new Exception(__('Payer Authentication failed. Please try a different payment method.', 'woocommerce-gateway-paypal-pro'));
            }
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            wp_redirect($order->get_checkout_payment_url(true));
            exit;
        }
    }

    /**
     * do_payment
     *
     * Makes the request to PayPal's DoDirectPayment API
     *
     * @access public
     * @param mixed $order
     * @param mixed $card_number
     * @param mixed $card_type
     * @param mixed $card_exp_month
     * @param mixed $card_exp_year
     * @param mixed $card_csc
     * @param string $centinelPAResStatus (default: '')
     * @param string $centinelEnrolled (default: '')
     * @param string $centinelCavv (default: '')
     * @param string $centinelEciFlag (default: '')
     * @param string $centinelXid (default: '')
     * @return void
     */
    function do_payment($order, $card_number, $card_type, $card_exp_month, $card_exp_year, $card_csc, $centinelPAResStatus = '', $centinelEnrolled = '', $centinelCavv = '', $centinelEciFlag = '', $centinelXid = '', $start_month = '', $start_year = '')
    {
        /*
         * Display message to user if session has expired.
         */
        if (sizeof(WC()->cart->get_cart()) == 0) {
            $pc_session_expired_error = apply_filters('angelleye_pc_session_expired_error', sprintf(__('Sorry, your session has expired. <a href=%s>Return to homepage &rarr;</a>', 'paypal-for-woocommerce'), '"' . home_url() . '"'));
            wc_add_notice($pc_session_expired_error, "error");
        }

        /*
         * Check if the PayPal class has already been established.
         */
        if (!class_exists('Angelleye_PayPal')) {
            require_once('lib/angelleye/paypal-php-library/includes/paypal.class.php');
        }

        /*
         * Create PayPal object.
         */
        $PayPalConfig = array(
            'Sandbox' => $this->testmode == 'yes' ? TRUE : FALSE,
            'APIUsername' => $this->api_username,
            'APIPassword' => $this->api_password,
            'APISignature' => $this->api_signature,
            'Force_tls_one_point_two' => $this->Force_tls_one_point_two
        );
        $PayPal = new Angelleye_PayPal($PayPalConfig);

        if (empty($GLOBALS['wp_rewrite'])) {
            $GLOBALS['wp_rewrite'] = new WP_Rewrite();
        }

        $firstname = isset($_POST['paypal_pro-card-cardholder-first']) && !empty($_POST['paypal_pro-card-cardholder-first']) ? wc_clean($_POST['paypal_pro-card-cardholder-first']) : $order->billing_first_name;
        $lastname = isset($_POST['paypal_pro-card-cardholder-last']) && !empty($_POST['paypal_pro-card-cardholder-last']) ? wc_clean($_POST['paypal_pro-card-cardholder-last']) : $order->billing_last_name;

        $card_exp = $card_exp_month . $card_exp_year;

        /**
         * Generate PayPal request
         */
        $DPFields = array(
            'paymentaction' => !empty($this->payment_action) ? $this->payment_action : 'Sale',                        // How you want to obtain payment.  Authorization indidicates the payment is a basic auth subject to settlement with Auth & Capture.  Sale indicates that this is a final sale for which you are requesting payment.  Default is Sale.
            'ipaddress' => $this->get_user_ip(),                            // Required.  IP address of the payer's browser.
            'returnfmfdetails' => '1'                   // Flag to determine whether you want the results returned by FMF.  1 or 0.  Default is 0.
        );

        
        $CCDetails = array(
            'creditcardtype' => $card_type,                    // Required. Type of credit card.  Visa, MasterCard, Discover, Amex, Maestro, Solo.  If Maestro or Solo, the currency code must be GBP.  In addition, either start date or issue number must be specified.
            'acct' => $card_number,                                // Required.  Credit card number.  No spaces or punctuation.
            'expdate' => $card_exp,                            // Required.  Credit card expiration date.  Format is MMYYYY
            'cvv2' => $card_csc,                                // Requirements determined by your PayPal account settings.  Security digits for credit card.
            'startdate' => $start_month . $start_year,                            // Month and year that Maestro or Solo card was issued.  MMYYYY
            'issuenumber' => ''                            // Issue number of Maestro or Solo card.  Two numeric digits max.
        );

        $PayerInfo = array(
            'email' => $order->billing_email,                                // Email address of payer.
            'firstname' => $firstname,                            // Required.  Payer's first name.
            'lastname' => $lastname                            // Required.  Payer's last name.
        );

        $BillingAddress = array(
            'street' => $order->billing_address_1,                        // Required.  First street address.
            'street2' => $order->billing_address_2,                        // Second street address.
            'city' => $order->billing_city,                            // Required.  Name of City.
            'state' => $order->billing_state,                            // Required. Name of State or Province.
            'countrycode' => $order->billing_country,                    // Required.  Country code.
            'zip' => $order->billing_postcode,                            // Required.  Postal code of payer.
            'phonenum' => $order->billing_phone                        // Phone Number of payer.  20 char max.
        );

        $ShippingAddress = array(
            'shiptoname' => $order->shipping_first_name . ' ' . $order->shipping_last_name,                    // Required if shipping is included.  Person's name associated with this address.  32 char max.
            'shiptostreet' => $order->shipping_address_1,                    // Required if shipping is included.  First street address.  100 char max.
            'shiptostreet2' => $order->shipping_address_2,                    // Second street address.  100 char max.
            'shiptocity' => $order->shipping_city,                    // Required if shipping is included.  Name of city.  40 char max.
            'shiptostate' => $order->shipping_state,                    // Required if shipping is included.  Name of state or province.  40 char max.
            'shiptozip' => $order->shipping_postcode,                        // Required if shipping is included.  Postal code of shipping address.  20 char max.
            'shiptocountry' => $order->shipping_country,                    // Required if shipping is included.  Country code of shipping address.  2 char max.
            'shiptophonenum' => $order->shipping_phone                    // Phone number for shipping address.  20 char max.
        );

        $customer_note = $order->customer_note ? substr(preg_replace("/[^A-Za-z0-9 ]/", "", $order->customer_note), 0, 256) : '';
        
        $PaymentDetails = array(
            'amt' => AngellEYE_Gateway_Paypal::number_format($order->get_total()),                            // Required.  Total amount of order, including shipping, handling, and tax.
            'currencycode' => $order->get_order_currency(),                    // Required.  Three-letter currency code.  Default is USD.
            'insuranceamt' => '',                    // Total shipping insurance costs for this order.
            'shipdiscamt' => '0.00',                    // Shipping discount for the order, specified as a negative number.
            'handlingamt' => '0.00',                    // Total handling costs for the order.  If you specify handlingamt, you must also specify itemamt.
            'desc' => '',                            // Description of the order the customer is purchasing.  127 char max.
            'custom' => apply_filters( 'ae_ppddp_custom_parameter', $customer_note , $order ),                        // Free-form field for your own use.  256 char max.
            'invnum' => $invoice_number = $this->invoice_id_prefix . preg_replace("/[^a-zA-Z0-9]/", "", $order->id), // Your own invoice or tracking number
            'recurring' => ''                        // Flag to indicate a recurring transaction.  Value should be Y for recurring, or anything other than Y if it's not recurring.  To pass Y here, you must have an established billing agreement with the buyer.
        );
        
        if( isset($this->notifyurl) && !empty($this->notifyurl) ) {
            $PaymentDetails['notifyurl'] = $this->notifyurl;
        }

        $PaymentData = AngellEYE_Gateway_Paypal::calculate($order, $this->send_items);
        $OrderItems = array();
        if ($this->send_items) {
            foreach ($PaymentData['order_items'] as $item) {
                $Item = array(
                    'l_name' => $item['name'],                        // Item Name.  127 char max.
                    'l_desc' => '',                        // Item description.  127 char max.
                    'l_amt' => $item['amt'],                            // Cost of individual item.
                    'l_number' => $item['number'],                        // Item Number.  127 char max.
                    'l_qty' => $item['qty'],                            // Item quantity.  Must be any positive integer.
                    'l_taxamt' => '',                        // Item's sales tax amount.
                    'l_ebayitemnumber' => '',                // eBay auction number of item.
                    'l_ebayitemauctiontxnid' => '',        // eBay transaction ID of purchased item.
                    'l_ebayitemorderid' => ''                // eBay order ID for the item.
                );
                array_push($OrderItems, $Item);
            }
        }

        //fix: itemamt = 0, make shipping or tax as order item
        if ($PaymentData['itemamt'] == 0 && $PaymentData['shippingamt'] > 0) {
            $OrderItems = array();

            $Item = array(
                'l_name' => __(apply_filters('angelleye_paypal_pro_shipping_text', 'Shipping'), 'paypal-for-woocommerce'),                        // Item Name.  127 char max.
                'l_desc' => '',                        // Item description.  127 char max.
                'l_amt' => $PaymentData['shippingamt'],                            // Cost of individual item.
                'l_number' => '',                        // Item Number.  127 char max.
                'l_qty' => 1,                            // Item quantity.  Must be any positive integer.
                'l_taxamt' => '',                        // Item's sales tax amount.
                'l_ebayitemnumber' => '',                // eBay auction number of item.
                'l_ebayitemauctiontxnid' => '',        // eBay transaction ID of purchased item.
                'l_ebayitemorderid' => ''                // eBay order ID for the item.
            );
            array_push($OrderItems, $Item);


            if ($PaymentData['taxamt'] > 0) {
                $Item = array(
                    'l_name' => __(apply_filters('angelleye_paypal_pro_tax_text', 'Tax'), 'paypal-for-woocommerce'),                        // Item Name.  127 char max.
                    'l_desc' => '',                        // Item description.  127 char max.
                    'l_amt' => $PaymentData['taxamt'],                            // Cost of individual item.
                    'l_number' => '',                        // Item Number.  127 char max.
                    'l_qty' => 1,                            // Item quantity.  Must be any positive integer.
                    'l_taxamt' => '',                        // Item's sales tax amount.
                    'l_ebayitemnumber' => '',                // eBay auction number of item.
                    'l_ebayitemauctiontxnid' => '',        // eBay transaction ID of purchased item.
                    'l_ebayitemorderid' => ''                // eBay order ID for the item.
                );
                array_push($OrderItems, $Item);
            }

            $PaymentDetails['itemamt'] = AngellEYE_Gateway_Paypal::number_format($order->get_total());
        } else {
            /**
             * Shipping/tax/item amount
             */
            $PaymentDetails['taxamt'] = $PaymentData['taxamt'];
            $PaymentDetails['shippingamt'] = $PaymentData['shippingamt'];
            $PaymentDetails['itemamt'] = $PaymentData['itemamt'];
        }

        /**
         * 3D Secure Params
         */
        if ($this->enable_3dsecure) {
            $Secure3D = array(
                'authstatus3ds' => $centinelPAResStatus,
                'mpivendor3ds' => $centinelEnrolled,
                'cavv' => $centinelCavv,
                'eci3ds' => $centinelEciFlag,
                'xid' => $centinelXid
            );
        } else {
            $Secure3D = array();
        }

        $PayPalRequestData = array(
            'DPFields' => $DPFields,
            'CCDetails' => $CCDetails,
            'PayerInfo' => $PayerInfo,
            'BillingAddress' => $BillingAddress,
            'ShippingAddress' => $ShippingAddress,
            'PaymentDetails' => $PaymentDetails,
            'OrderItems' => $OrderItems,
            'Secure3D' => $Secure3D
        );
        
        $log = $PayPalRequestData;
        $log['CCDetails']['acct'] = empty($log['CCDetails']['acct']) ? '****' : '';
        $log['CCDetails']['cvv2'] = empty($log['CCDetails']['cvv2']) ? '****' : '';
        $this->log('Do payment request ' . print_r($log, true));
        
        if(!empty($_POST['wc-paypal_pro-payment-token']) && $_POST['wc-paypal_pro-payment-token'] != 'new') {
            $token_id = wc_clean( $_POST['wc-paypal_pro-payment-token'] );
            $token = WC_Payment_Tokens::get( $token_id );
            unset($PayPalRequestData['DPFields']);
            $PayPalRequestData['DRTFields'] = array(
                'referenceid' => $token->get_token(), 
                'paymentaction' => !empty($this->payment_action) ? $this->payment_action : 'Sale', 
                'returnfmfdetails' => '1', 
                'softdescriptor' => ''
            );
            $PayPalResult = $PayPal->DoReferenceTransaction($PayPalRequestData);
        } else {
            $PayPalResult = $PayPal->DoDirectPayment($PayPalRequestData);
        }
        
        // Pass data into class for processing with PayPal and load the response array into $PayPalResult
        

        /**
         *  cURL Error Handling #146
         * @since    1.1.8
         */

        AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($PayPalResult, $methos_name = 'DoDirectPayment', $gateway = 'PayPal Website Payments Pro (DoDirectPayment)', $this->error_email_notify);

        
        $PayPalRequest = isset($PayPalResult['RAWREQUEST']) ? $PayPalResult['RAWREQUEST'] : '';
        $PayPalResponse = isset($PayPalResult['RAWRESPONSE']) ? $PayPalResult['RAWRESPONSE'] : '';

        $this->log('Request: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalRequest)), true));
        $this->log('Response: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalResponse)), true));
       

        if (empty($PayPalResult['RAWRESPONSE'])) {
            $pc_empty_response = apply_filters('ae_ppddp_paypal_response_empty_message', __('Empty PayPal response.', 'paypal-for-woocommerce'), $PayPalResult);
            throw new Exception($pc_empty_response);
        }

        if ($PayPal->APICallSuccessful($PayPalResult['ACK'])) {
            // Add order note
            $order->add_order_note(sprintf(__('PayPal Pro payment completed (Transaction ID: %s, Correlation ID: %s)', 'paypal-for-woocommerce'), $PayPalResult['TRANSACTIONID'], $PayPalResult['CORRELATIONID']));
            //$order->add_order_note("PayPal Results: ".print_r($PayPalResult,true));

            /* Checkout Note */
            if (isset($_POST) && !empty($_POST['order_comments'])) {
                // Update post 37
                $checkout_note = array(
                    'ID' => $order->id,
                    'post_excerpt' => $_POST['order_comments'],
                );
                wp_update_post($checkout_note);
            }

            /**
             * Add order notes for AVS result
             */
            $avs_response_code = isset($PayPalResult['AVSCODE']) ? $PayPalResult['AVSCODE'] : '';
            $avs_response_message = $PayPal->GetAVSCodeMessage($avs_response_code);
            $avs_response_order_note = __('Address Verification Result', 'paypal-for-woocommerce');
            $avs_response_order_note .= "\n";
            $avs_response_order_note .= $avs_response_code;
            $avs_response_order_note .= $avs_response_message != '' ? ' - ' . $avs_response_message : '';
            $order->add_order_note($avs_response_order_note);

            /**
             * Add order notes for CVV2 result
             */
            $cvv2_response_code = isset($PayPalResult['CVV2MATCH']) ? $PayPalResult['CVV2MATCH'] : '';
            $cvv2_response_message = $PayPal->GetCVV2CodeMessage($cvv2_response_code);
            $cvv2_response_order_note = __('Card Security Code Result', 'paypal-for-woocommerce');
            $cvv2_response_order_note .= "\n";
            $cvv2_response_order_note .= $cvv2_response_code;
            $cvv2_response_order_note .= $cvv2_response_message != '' ? ' - ' . $cvv2_response_message : '';
            $order->add_order_note($cvv2_response_order_note);
            
            $is_sandbox = $this->testmode == 'yes' ? true : false;
            update_post_meta($order->id, 'is_sandbox', $is_sandbox);
            do_action('before_save_payment_token', $order->id);
            if(!empty($_POST['wc-paypal_pro-payment-token']) && $_POST['wc-paypal_pro-payment-token'] == 'new') {
                if(!empty($_POST['wc-paypal_pro-new-payment-method']) && $_POST['wc-paypal_pro-new-payment-method'] == true) {
                    $customer_id =  $order->get_user_id();
                    $TRANSACTIONID = $PayPalResult['TRANSACTIONID'];
                    $token = new WC_Payment_Token_CC();
                    $token->set_user_id( $customer_id );
                    $token->set_token( $TRANSACTIONID );
                    $token->set_gateway_id( $this->id );
                    $token->set_card_type( AngellEYE_Utility::card_type_from_account_number($PayPalRequestData['CCDetails']['acct']));
                    $token->set_last4( substr( $PayPalRequestData['CCDetails']['acct'], -4 ) );
                    $token->set_expiry_month( substr( $PayPalRequestData['CCDetails']['expdate'], 0,2 ) );
                    $token->set_expiry_year( substr( $PayPalRequestData['CCDetails']['expdate'], 2,5 ) );
                    $save_result = $token->save();
                    if ( $save_result ) {
                            $order->add_payment_token( $token );
                    }
                }
            }
            
            // Payment complete
            if ($this->payment_action == "Sale") {
                $order->payment_complete($PayPalResult['TRANSACTIONID']);
            } else {
                update_post_meta( $order->id, '_first_transaction_id', $PayPalResult['TRANSACTIONID'] );
                $payment_order_meta = array('_transaction_id' => $PayPalResult['TRANSACTIONID'], '_payment_action' => $this->payment_action);
                AngellEYE_Utility::angelleye_add_order_meta($order->id, $payment_order_meta);
                AngellEYE_Utility::angelleye_paypal_for_woocommerce_add_paypal_transaction($PayPalResult, $order, $this->payment_action);
                $angelleye_utility = new AngellEYE_Utility(null, null);
                $angelleye_utility->angelleye_get_transactionDetails($PayPalResult['TRANSACTIONID']);
                $order->payment_complete($PayPalResult['TRANSACTIONID']);
                $order->add_order_note('Payment Action: ' . $this->payment_action);
            }

            // Remove cart
            WC()->cart->empty_cart();

            // Return thank you page redirect
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        } else {
            // Get error message
            $error_code = isset($PayPalResult['ERRORS'][0]['L_ERRORCODE']) ? $PayPalResult['ERRORS'][0]['L_ERRORCODE'] : '';
            $long_message = isset($PayPalResult['ERRORS'][0]['L_LONGMESSAGE']) ? $PayPalResult['ERRORS'][0]['L_LONGMESSAGE'] : '';
            $error_message = $error_code . '-' . $long_message;

            // Notice admin if has any issue from PayPal
            if ($this->error_email_notify) {
                $admin_email = get_option("admin_email");
                $message = __("DoDirectPayment API call failed.", "paypal-for-woocommerce") . "\n\n";
                $message .= __('Error Code: ', 'paypal-for-woocommerce') . $error_code . "\n";
                $message .= __('Detailed Error Message: ', 'paypal-for-woocommerce') . $long_message . "\n";
                $message .= __('User IP: ', 'paypal-for-woocommerce') . $this->get_user_ip() . "\n";
                $message .= __('Order ID: ') . $order->id . "\n";
                $message .= __('Customer Name: ') . $order->billing_first_name . ' ' . $order->billing_last_name . "\n";
                $message .= __('Customer Email: ') . $order->billing_email . "\n";

                $pc_error_email_message = apply_filters('ae_ppddp_error_email_message', $message, $error_code, $long_message);
                $pc_error_email_subject = apply_filters('ae_ppddp_error_email_subject', "PayPal Pro Error Notification", $error_code, $long_message);

                wp_mail($admin_email, $pc_error_email_subject, $pc_error_email_message);
            }

            
             $this->log('Error ' . print_r($PayPalResult['ERRORS'], true));
            

            $order->update_status('failed', sprintf(__('PayPal Pro payment failed (Correlation ID: %s). Payment was rejected due to an error: %s',
                'paypal-for-woocommerce'), $PayPalResult['CORRELATIONID'], '(' . $PayPalResult['L_ERRORCODE0'] . ') ' . '"' . $error_message . '"'));

            // Generate error message based on Error Display Type setting
            if ($this->error_display_type == 'detailed') {
                $pc_display_type_error = __($error_message, 'paypal-for-woocommerce');
                $pc_display_type_notice = __('Payment error:', 'paypal-for-woocommerce') . ' ' . $error_message;
            } else {
                $pc_display_type_error = __('There was a problem connecting to the payment gateway.', 'paypal-for-woocommerce');
                $pc_display_type_notice = __('Payment error:', 'paypal-for-woocommerce') . ' ' . $error_message;
            }

            $pc_display_type_error = apply_filters('ae_ppddp_error_exception', $pc_display_type_error, $error_code, $long_message);
            $pc_display_type_notice = apply_filters('ae_ppddp_error_user_display_message', $pc_display_type_notice, $error_code, $long_message);
            wc_add_notice($pc_display_type_notice, "error");
            throw new Exception($pc_display_type_error);

            return;
        }
    }

    /**
     * Get user's IP address
     */
    function get_user_ip()
    {
        return WC_Geolocation::get_ip_address();
    }

    /**
     * clear_centinel_session function.
     *
     * @access public
     * @return void
     */
    function clear_centinel_session()
    {
        WC()->session->set('Centinel_ErrorNo', null);
        WC()->session->set('Centinel_ErrorDesc', null);
        WC()->session->set('Centinel_TransactionId', null);
        WC()->session->set('Centinel_OrderId', null);
        WC()->session->set('Centinel_Enrolled', null);
        WC()->session->set('Centinel_ACSUrl', null);
        WC()->session->set('Centinel_Payload', null);
        WC()->session->set('Centinel_EciFlag', null);
        WC()->session->set('Centinel_card_start_month', null);
        WC()->session->set('Centinel_card_start_year', null);
    }

    /**
     * Process a refund if supported
     * @param  int $order_id
     * @param  float $amount
     * @param  string $reason
     * @return  bool|wp_error True or false based on success, or a WP_Error object
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $order = wc_get_order($order_id);
        $this->log('Begin Refund');
        $this->log('Order ID: ' . print_r($order_id, true));
        $this->log('Transaction ID: ' . print_r($order->get_transaction_id(), true));
        if (!$order || !$order->get_transaction_id() || !$this->api_username || !$this->api_password || !$this->api_signature) {
            return false;
        }
 
        /*
         * Check if the PayPal class has already been established.
         */
        if (!class_exists('Angelleye_PayPal')) {
            require_once('lib/angelleye/paypal-php-library/includes/paypal.class.php');
        }

        /*
         * Create PayPal object.
         */
        $PayPalConfig = array(
            'Sandbox' => $this->testmode == 'yes' ? TRUE : FALSE,
            'APIUsername' => $this->api_username,
            'APIPassword' => $this->api_password,
            'APISignature' => $this->api_signature,
            'Force_tls_one_point_two' => $this->Force_tls_one_point_two
        );
        $PayPal = new Angelleye_PayPal($PayPalConfig);
        if ($reason) {
            if (255 < strlen($reason)) {
                $reason = substr($reason, 0, 252) . '...';
            }

            $reason = html_entity_decode($reason, ENT_NOQUOTES, 'UTF-8');
        }

        // Prepare request arrays
        $RTFields = array(
            'transactionid' => $order->get_transaction_id(),                            // Required.  PayPal transaction ID for the order you're refunding.
            'payerid' => '',                                // Encrypted PayPal customer account ID number.  Note:  Either transaction ID or payer ID must be specified.  127 char max
            'invoiceid' => '',                                // Your own invoice tracking number.
            'refundtype' => $order->get_total() == $amount ? 'Full' : 'Partial',                            // Required.  Type of refund.  Must be Full, Partial, or Other.
            'amt' => AngellEYE_Gateway_Paypal::number_format($amount),                                    // Refund Amt.  Required if refund type is Partial.
            'currencycode' => $order->get_order_currency(),                            // Three-letter currency code.  Required for Partial Refunds.  Do not use for full refunds.
            'note' => $reason,                                    // Custom memo about the refund.  255 char max.
            'retryuntil' => '',                            // Maximum time until you must retry the refund.  Note:  this field does not apply to point-of-sale transactions.
            'refundsource' => '',                            // Type of PayPal funding source (balance or eCheck) that can be used for auto refund.  Values are:  any, default, instant, eCheck
            'merchantstoredetail' => '',                    // Information about the merchant store.
            'refundadvice' => '',                            // Flag to indicate that the buyer was already given store credit for a given transaction.  Values are:  1/0
            'refunditemdetails' => '',                        // Details about the individual items to be returned.
            'msgsubid' => '',                                // A message ID used for idempotence to uniquely identify a message.
            'storeid' => '',                                // ID of a merchant store.  This field is required for point-of-sale transactions.  50 char max.
            'terminalid' => ''                                // ID of the terminal.  50 char max.
        );

        $PayPalRequestData = array('RTFields' => $RTFields);

        // Pass data into class for processing with PayPal and load the response array into $PayPalResult
        $PayPalResult = $PayPal->RefundTransaction($PayPalRequestData);

        /**
         *  cURL Error Handling #146
         * @since    1.1.8
         */

        AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($PayPalResult, $methos_name = 'RefundTransaction', $gateway = 'PayPal Website Payments Pro (DoDirectPayment)', $this->error_email_notify);

        $PayPalRequest = isset($PayPalResult['RAWREQUEST']) ? $PayPalResult['RAWREQUEST'] : '';
        $PayPalResponse = isset($PayPalResult['RAWRESPONSE']) ? $PayPalResult['RAWRESPONSE'] : '';

        $this->log('Refund Request: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalRequest)), true));
        $this->log('Refund Response: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalResponse)), true));
        
        if ($PayPal->APICallSuccessful($PayPalResult['ACK'])) {
            $order->add_order_note('Refund Transaction ID:' . $PayPalResult['REFUNDTRANSACTIONID']);

            $max_remaining_refund = wc_format_decimal($order->get_total() - $order->get_total_refunded());
            if (!$max_remaining_refund > 0) {
                $order->update_status('refunded');
            }

            if (ob_get_length()) ob_end_clean();
            return true;
        } else {
            $pc_message = apply_filters('ae_ppddp_refund_error_message', $PayPalResult['L_LONGMESSAGE0'], $PayPalResult['L_ERRORCODE'], $PayPalResult);
            return new WP_Error('ec_refund-error', $pc_message);
        }

    }

    public function angelleye_woocommerce_credit_card_form_start($current_id)
    {
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

    /**
    * Get and clean a value from $this->centinel_client because the SDK does a poor job of cleaning.
    * @return string
    */
    public function get_centinel_value( $key ) {
        $value = $this->centinel_client->getValue( $key );
        if( empty($value)) {
            $value = WC()->session->get( $key );
        }
        $value = wc_clean( $value );
        return $value;
    }
    
    public function field_name( $name ) {
	return ' name="' . esc_attr( $this->id . '-' . $name ) . '" ';
    }
    public function angelleye_paypal_pro_credit_card_form_fields($default_fields, $current_gateway_id) {
        if($current_gateway_id == $this->id) {
            $fields = array();
            $class = 'form-row form-row-first';
            if (isset($this->available_card_types[WC()->countries->get_base_country()]['Maestro'])) {
                $class = 'form-row form-row-last';
                $fields = array(
                    'card-number-field' => '<p class="form-row form-row-wide">
                        <label for="' . esc_attr( $this->id ) . '-card-number">' . __( 'Card number', 'woocommerce' ) . ' <span class="required">*</span></label>
                        <input id="' . esc_attr( $this->id ) . '-card-number" class="input-text wc-credit-card-form-card-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ' . $this->field_name( 'card-number' ) . ' />
                    </p>',
                    'card-expiry-field' => $this->paypal_for_woocommerce_paypal_pro_credit_card_form_expiration_date_selectbox($class),
                    '<p class="form-row form-row-last">
			<label for="' . esc_attr( $this->id ) . '-card-cvc">' . __( 'Card Security Code', 'woocommerce' ) . ' <span class="required">*</span></label>
			<input id="' . esc_attr( $this->id ) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="' . esc_attr__( 'CVC', 'woocommerce' ) . '" ' . $this->field_name( 'card-cvc' ) . ' style="width:100px" />
                    </p>',
                    'card-startdate-field' => '<p class="form-row form-row-last">
                        <label for="' . esc_attr($this->id) . '-card-startdate">' . __('Start Date (MM/YY)', 'paypal-for-woocommerce') . '</label>
                        <input id="' . esc_attr($this->id) . '-card-startdate" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="' . __('MM / YY', 'paypal-for-woocommerce') . '" name="' . $this->id . '-card-startdate' . '" />
                     </p>'
                );

            } else {
                $fields = array(
                    'card-number-field' => '<p class="form-row form-row-wide">
                        <label for="' . esc_attr( $this->id ) . '-card-number">' . __( 'Card number', 'woocommerce' ) . ' <span class="required">*</span></label>
                        <input id="' . esc_attr( $this->id ) . '-card-number" class="input-text wc-credit-card-form-card-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ' . $this->field_name( 'card-number' ) . ' />
                    </p>',
                    'card-expiry-field' => $this->paypal_for_woocommerce_paypal_pro_credit_card_form_expiration_date_selectbox($class),
                    '<p class="form-row form-row-last">
			<label for="' . esc_attr( $this->id ) . '-card-cvc">' . __( 'Card Security Code', 'woocommerce' ) . ' <span class="required">*</span></label>
			<input id="' . esc_attr( $this->id ) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="' . esc_attr__( 'CVC', 'woocommerce' ) . '" ' . $this->field_name( 'card-cvc' ) . ' style="width:100px" />
                    </p>'
                );

            }
            return $fields;
        } else {
            return $default_fields;
        }
    }
    
    public function get_transaction_url( $order ) {
        $sandbox_transaction_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
        $live_transaction_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
        $is_sandbox = get_post_meta($order->id, 'is_sandbox', true);
        if ( $is_sandbox  == true ) {
            $this->view_transaction_url = $sandbox_transaction_url;
        } else {
            if ( empty( $is_sandbox ) ) {
                if (  $this->testmode == 'yes' ) {
                    $this->view_transaction_url = $sandbox_transaction_url;
                } else {
                    $this->view_transaction_url = $live_transaction_url;
                }
            } else {
                $this->view_transaction_url = $live_transaction_url;
            }
        }
        return parent::get_transaction_url( $order );
    }
    
    public function paypal_pro_error_handler($request_name = '', $redirect_url = '', $result){
        $ErrorCode = urldecode($result["L_ERRORCODE0"]);
        $ErrorShortMsg = urldecode($result["L_SHORTMESSAGE0"]);
        $ErrorLongMsg = urldecode($result["L_LONGMESSAGE0"]);
        $ErrorSeverityCode = urldecode($result["L_SEVERITYCODE0"]);
        $this->log(__($request_name .'API call failed. ', 'paypal-for-woocommerce'));
        $this->log(__('Detailed Error Message: ', 'paypal-for-woocommerce') . $ErrorLongMsg);
        $this->log(__('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg);
        $this->log(__('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode);
        $this->log(__('Error Severity Code: ', 'paypal-for-woocommerce') . $ErrorSeverityCode);
        $message = '';
        if ($this->error_email_notify) {
            $admin_email = get_option("admin_email");
            $message .= __($request_name . " API call failed.", "paypal-for-woocommerce") . "\n\n";
            $message .= __('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode . "\n";
            $message .= __('Error Severity Code: ', 'paypal-for-woocommerce') . $ErrorSeverityCode . "\n";
            $message .= __('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg . "\n";
            $message .= __('Detailed Error Message: ', 'paypal-for-woocommerce') . $ErrorLongMsg . "\n";
            $message .= __('User IP: ', 'paypal-for-woocommerce') . $this->get_user_ip() . "\n";
            $error_email_notify_mes = apply_filters( 'ae_ppec_error_email_message', $message, $ErrorCode, $ErrorSeverityCode, $ErrorShortMsg, $ErrorLongMsg );
            $subject = "PayPal Express Checkout Error Notification";
            $error_email_notify_subject = apply_filters( 'ae_ppec_error_email_subject', $subject );
            wp_mail($admin_email, $error_email_notify_subject, $error_email_notify_mes);
        }
        if ($this->error_display_type == 'detailed') {
            $sec_error_notice = $ErrorCode . ' - ' . $ErrorLongMsg;
            $error_display_type_message = sprintf(__($sec_error_notice, 'paypal-for-woocommerce'));
        } else {
            $error_display_type_message = sprintf(__('There was a problem paying with PayPal.  Please try another method.', 'paypal-for-woocommerce'));
        }
        $error_display_type_message = apply_filters( 'ae_ppec_error_user_display_message', $error_display_type_message, $ErrorCode, $ErrorLongMsg );
        wc_add_notice($error_display_type_message, 'error');
        if (!is_ajax()) {
            wp_redirect($redirect_url);
            exit;
        } else {
            return array(
                'result'   => 'fail',
                'redirect' => $redirect_url
            );
        }
    }
    public function add_payment_method() {
        if (!class_exists('Angelleye_PayPal')) {
            require_once('lib/angelleye/paypal-php-library/includes/paypal.class.php');
        }
        $this->validate_fields();
        $card = $this->get_posted_card();
        $PayPalConfig = array(
            'Sandbox' => $this->testmode == 'yes' ? TRUE : FALSE,
            'APIUsername' => $this->api_username,
            'APIPassword' => $this->api_password,
            'APISignature' => $this->api_signature,
            'Force_tls_one_point_two' => $this->Force_tls_one_point_two
        );
        $PayPal = new Angelleye_PayPal($PayPalConfig);
        $DPFields = array(
            'paymentaction' => 'Authorization',
            'ipaddress' => $this->get_user_ip(),
            'returnfmfdetails' => '1'
        );
        $CCDetails = array(
            'creditcardtype' => $card->type,
            'acct' => $card->number,
            'expdate' => $card->exp_month . $card->exp_year,
            'cvv2' => $card->cvc
        );
        $PaymentDetails = array(
            'amt' => 0, 			
            'currencycode' => get_woocommerce_currency(), 					
        );
        $PayPalRequestData = array(
            'DPFields' => $DPFields, 
            'CCDetails' => $CCDetails, 
            'PaymentDetails' => $PaymentDetails
        );
        $result = $PayPal->DoDirectPayment($PayPalRequestData);
        if ($result['ACK'] == 'Success' || $result['ACK'] == 'SuccessWithWarning') {
            $customer_id = get_current_user_id();
            $TRANSACTIONID = $result['TRANSACTIONID'];
            $token = new WC_Payment_Token_CC();
            $token->set_user_id( $customer_id );
            $token->set_token( $TRANSACTIONID );
            $token->set_gateway_id( $this->id );
            $token->set_card_type( AngellEYE_Utility::card_type_from_account_number($PayPalRequestData['CCDetails']['acct']));
            $token->set_last4( substr( $PayPalRequestData['CCDetails']['acct'], -4 ) );
            $token->set_expiry_month( substr( $PayPalRequestData['CCDetails']['expdate'], 0,2 ) );
            $token->set_expiry_year( substr( $PayPalRequestData['CCDetails']['expdate'], 2,5 ) );
            $save_result = $token->save();
            return array(
                    'result' => 'success',
                    'redirect' => wc_get_account_endpoint_url( 'payment-methods' )
                );
           
        } else {
            $redirect_url = wc_get_account_endpoint_url( 'payment-methods' );
            $this->paypal_pro_error_handler($request_name = 'DoDirectPayment', $redirect_url, $result);
        }
    }
    
    public function angelleye_paypal_pro_encrypt_gateway_api($settings) {
        if( !empty($settings['is_encrypt']) ) {
            $gateway_settings_keys = array('sandbox_api_username', 'sandbox_api_password', 'sandbox_api_signature', 'api_username', 'api_password', 'api_signature');
            foreach ($gateway_settings_keys as $gateway_settings_key => $gateway_settings_value) {
                if( !empty( $settings[$gateway_settings_value]) ) {
                    $settings[$gateway_settings_value] = AngellEYE_Utility::crypting($settings[$gateway_settings_value], $action = 'e');
                }
            }
        }
        return $settings;
    }
}