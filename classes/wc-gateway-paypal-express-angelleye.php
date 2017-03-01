<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_PayPal_Express_AngellEYE extends WC_Payment_Gateway {

    public $function_helper;
    public static $log_enabled = false;
    public static $log = false;

    public function __construct() {
        $this->id = 'paypal_express';
        $this->method_title = __('PayPal Express Checkout ', 'paypal-for-woocommerce');
        $this->method_description = __('PayPal Express Checkout is designed to make the checkout experience for buyers using PayPal much more quick and easy than filling out billing and shipping forms.  Customers will be taken directly to PayPal to sign in and authorize the payment, and are then returned back to your store to choose a shipping method, review the final order total, and complete the payment.', 'paypal-for-woocommerce');
        $this->has_fields = false;
        $this->order_button_text = __('Proceed to PayPal', 'paypal-for-woocommerce');
        $this->supports = array(
            'products',
            'refunds'
        );
        if (substr(get_option("woocommerce_default_country"), 0, 2) != 'US') {
            $this->not_us = true;
        } else {
            $this->not_us = false;
        }
        $this->init_form_fields();
        $this->init_settings();
        $this->enable_tokenized_payments = $this->get_option('enable_tokenized_payments', 'no');
        if ($this->enable_tokenized_payments == 'yes') {
            array_push($this->supports, "tokenization");
        }
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->testmode = $this->get_option('testmode', 'yes');
        $this->debug = 'yes' === $this->get_option('debug', 'no');
        self::$log_enabled = $this->debug;
        $this->error_email_notify = 'yes' === $this->get_option('error_email_notify', 'no');
        $this->invoice_id_prefix = $this->get_option('invoice_id_prefix', 'WC-EC');
        $this->show_on_checkout = $this->get_option('show_on_checkout', 'top');
        $this->paypal_account_optional = $this->get_option('paypal_account_optional', 'no');
        $this->error_display_type = $this->get_option('error_display_type', 'detailed');
        $this->landing_page = $this->get_option('landing_page', 'login');
        $this->checkout_logo = $this->get_option('checkout_logo', '');
        $this->checkout_logo_hdrimg = $this->get_option('checkout_logo_hdrimg', '');
        $this->show_paypal_credit = $this->get_option('show_paypal_credit', 'yes');
        $this->brand_name = $this->get_option('brand_name', get_bloginfo('name'));
        $this->customer_service_number = $this->get_option('customer_service_number', '');
        $this->gift_wrap_enabled = $this->get_option('gift_wrap_enabled', '');
        $this->gift_message_enabled = $this->get_option('gift_message_enabled', '');
        $this->gift_receipt_enabled = $this->get_option('gift_receipt_enabled', '');
        $this->gift_wrap_name = $this->get_option('gift_wrap_name', '');
        $this->gift_wrap_amount = $this->get_option('gift_wrap_amount', '');
        $this->use_wp_locale_code = $this->get_option('use_wp_locale_code', 'yes');
        $this->angelleye_skip_text = $this->get_option('angelleye_skip_text', 'Skip the forms and pay faster with PayPal!');
        $this->skip_final_review = $this->get_option('skip_final_review', 'no');
        $this->disable_term = $this->get_option('disable_term', 'no');
        $this->payment_action = $this->get_option('payment_action', 'Sale');
        $this->billing_address = $this->get_option('billing_address', 'no');
        $this->send_items = 'yes' === $this->get_option('send_items', 'yes');
        $this->order_cancellations = $this->get_option('order_cancellations', 'disabled');
        $this->email_notify_order_cancellations = 'yes' === $this->get_option('email_notify_order_cancellations', 'no');
        $this->customer_id = get_current_user_id();
        $this->enable_notifyurl = $this->get_option('enable_notifyurl', 'no');
        $this->notifyurl = '';
        $this->is_encrypt = $this->get_option('is_encrypt', 'no');
        $this->cancel_page_id = $this->get_option('cancel_page', '');
        if ($this->enable_notifyurl == 'yes') {
            $this->notifyurl = $this->get_option('notifyurl');
            if (isset($this->notifyurl) && !empty($this->notifyurl)) {
                $this->notifyurl = str_replace('&amp;', '&', $this->notifyurl);
            }
        }
        if ($this->not_us) {
            $this->show_paypal_credit = 'no';
        }
        if ($this->testmode == 'yes') {
            $this->API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
            $this->PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
            $this->api_username = $this->get_option('sandbox_api_username');
            $this->api_password = $this->get_option('sandbox_api_password');
            $this->api_signature = $this->get_option('sandbox_api_signature');
        } else {
            $this->API_Endpoint = "https://api-3t.paypal.com/nvp";
            $this->PAYPAL_URL = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
            $this->api_username = $this->get_option('api_username');
            $this->api_password = $this->get_option('api_password');
            $this->api_signature = $this->get_option('api_signature');
        }
        $this->button_position = $this->get_option('button_position', 'bottom');
        $this->show_on_cart = $this->get_option('show_on_cart', 'yes');
        $this->checkout_with_pp_button_type = $this->get_option('checkout_with_pp_button_type', 'paypalimage');
        $this->pp_button_type_text_button = $this->get_option('pp_button_type_text_button', 'Proceed to Checkout');
        $this->pp_button_type_my_custom = $this->get_option('pp_button_type_my_custom', $this->checkout_with_pp_button_type);
        $this->version = "64";
        $this->Force_tls_one_point_two = get_option('Force_tls_one_point_two', 'no');
        $this->page_style = $this->get_option('page_style', '');
        add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_filter('woocommerce_settings_api_sanitized_fields_' . $this->id, array($this, 'angelleye_express_checkout_encrypt_gateway_api'), 10, 1);

        if (!has_action('woocommerce_api_' . strtolower(get_class($this)))) {
            add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'handle_wc_api'));
        }
        if (!class_exists('WC_Gateway_PayPal_Express_Function_AngellEYE')) {
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/express-checkout/class-wc-gateway-paypal-express-function-angelleye.php' );
        }
        $this->function_helper = new WC_Gateway_PayPal_Express_Function_AngellEYE();
    }

    public function admin_options() {
        ?>
        <h3><?php _e('Braintree', 'paypal-for-woocommerce'); ?></h3>
        <p><?php _e($this->method_description, 'paypal-for-woocommerce'); ?></p>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
            <script type="text/javascript">
                jQuery('#woocommerce_paypal_express_testmode').change(function () {
                    sandbox = jQuery('#woocommerce_paypal_express_sandbox_api_username, #woocommerce_paypal_express_sandbox_api_password, #woocommerce_paypal_express_sandbox_api_signature').closest('tr'),
                            production = jQuery('#woocommerce_paypal_express_api_username, #woocommerce_paypal_express_api_password, #woocommerce_paypal_express_api_signature').closest('tr');
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

    public function init_form_fields() {
        $require_ssl = '';
        if (!AngellEYE_Gateway_Paypal::is_ssl()) {
            $require_ssl = __('This image requires an SSL host.  Please upload your image to <a target="_blank" href="http://www.sslpic.com">www.sslpic.com</a> and enter the image URL here.', 'paypal-for-woocommerce');
        }
        $skip_final_review_option_not_allowed_guest_checkout = '';
        $skip_final_review_option_not_allowed_terms = '';
        $skip_final_review_option_not_allowed_tokenized_payments = '';
        $woocommerce_enable_guest_checkout = get_option('woocommerce_enable_guest_checkout');
        if (isset($woocommerce_enable_guest_checkout) && ( $woocommerce_enable_guest_checkout === "no" )) {
            $skip_final_review_option_not_allowed_guest_checkout = ' (The WooCommerce guest checkout option is disabled.  Therefore, the review page is required for login / account creation, and this option will be overridden.)';
        }
        if (wc_get_page_id('terms') > 0 && apply_filters('woocommerce_checkout_show_terms', true)) {
            $skip_final_review_option_not_allowed_terms = ' (You currently have a Terms &amp; Conditions page set, which requires the review page, and will override this option.)';
        }
        $this->enable_tokenized_payments = $this->get_option('enable_tokenized_payments', 'no');
        if ($this->enable_tokenized_payments == 'yes') {
            $skip_final_review_option_not_allowed_tokenized_payments = ' (Payments tokens are enabled, which require the review page, and that will override this option.)';
        }
        $args = array(
            'sort_order' => 'ASC',
            'sort_column' => 'post_title',
            'hierarchical' => 1,
            'exclude' => '',
            'include' => '',
            'meta_key' => '',
            'meta_value' => '',
            'authors' => '',
            'child_of' => 0,
            'parent' => -1,
            'exclude_tree' => '',
            'number' => '',
            'offset' => 0,
            'post_type' => 'page',
            'post_status' => 'publish'
        );
        $pages = get_pages($args);
        $cancel_page = array();
        foreach ($pages as $p) {
            $cancel_page[$p->ID] = $p->post_title;
        }
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                'label' => __('Enable PayPal Express', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'paypal-for-woocommerce'),
                'default' => __('PayPal Express', 'paypal-for-woocommerce'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'paypal-for-woocommerce'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'paypal-for-woocommerce'),
                'default' => __("Pay via PayPal; you can pay with your credit card if you don't have a PayPal account", 'paypal-for-woocommerce'),
                'desc_tip' => true,
            ),
            'testmode' => array(
                'title' => __('PayPal Sandbox', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable PayPal Sandbox', 'paypal-for-woocommerce'),
                'default' => 'yes',
                'description' => __('The sandbox is PayPal\'s test environment and is only for use with sandbox accounts created within your <a href="http://developer.paypal.com" target="_blank">PayPal developer account</a>.', 'paypal-for-woocommerce')
            ),
            'sandbox_api_username' => array(
                'title' => __('Sandbox API User Name', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Create sandbox accounts and obtain API credentials from within your <a href="http://developer.paypal.com">PayPal developer account</a>.', 'paypal-for-woocommerce'),
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
                'title' => __('Live API User Name', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Get your live account API credentials from your PayPal account profile under the API Access section <br />or by using <a target="_blank" href="https://www.paypal.com/us/cgi-bin/webscr?cmd=_login-api-run">this tool</a>.', 'paypal-for-woocommerce'),
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
            'error_email_notify' => array(
                'title' => __('Error Email Notifications', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable admin email notifications for errors.', 'paypal-for-woocommerce'),
                'default' => 'yes',
                'description' => __('This will send a detailed error email to the WordPress site administrator if a PayPal API error occurs.', 'paypal-for-woocommerce'),
                'desc_tip' => true
            ),
            'invoice_id_prefix' => array(
                'title' => __('Invoice ID Prefix', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Add a prefix to the invoice ID sent to PayPal. This can resolve duplicate invoice problems when working with multiple websites on the same PayPal account.', 'paypal-for-woocommerce'),
                'desc_tip' => true,
                'default' => 'WC-EC'
            ),
            'checkout_with_pp_button_type' => array(
                'title' => __('Checkout Button Type', 'paypal-for-woocommerce'),
                'type' => 'select',
                'label' => __('Use Checkout with PayPal image button', 'paypal-for-woocommerce'),
                'class' => 'checkout_with_pp_button_type',
                'options' => array(
                    'paypalimage' => __('PayPal Image', 'paypal-for-woocommerce'),
                    'textbutton' => __('Text Button', 'paypal-for-woocommerce'),
                    'customimage' => __('Custom Image', 'paypal-for-woocommerce')
                ),
                'default' => 'paypalimage',
            ),
            'pp_button_type_my_custom' => array(
                'title' => __('Select Image', 'paypal-for-woocommerce'),
                'type' => 'text',
                'label' => __('Use Checkout with PayPal image button', 'paypal-for-woocommerce'),
                'class' => 'pp_button_type_my_custom',
            ),
            'pp_button_type_text_button' => array(
                'title' => __('Custom Text', 'paypal-for-woocommerce'),
                'type' => 'text',
                'class' => 'pp_button_type_text_button',
                'default' => 'Proceed to Checkout',
            ),
            'show_on_cart' => array(
                'title' => __('Cart Page', 'paypal-for-woocommerce'),
                'label' => __('Show Express Checkout button on shopping cart page.', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'default' => 'yes'
            ),
            'button_position' => array(
                'title' => __('Cart Button Position', 'paypal-for-woocommerce'),
                'label' => __('Where to display PayPal Express Checkout button(s).', 'paypal-for-woocommerce'),
                'description' => __('Set where to display the PayPal Express Checkout button(s).'),
                'type' => 'select',
                'options' => array(
                    'top' => 'At the top, above the shopping cart details.',
                    'bottom' => 'At the bottom, below the shopping cart details.',
                    'both' => 'Both at the top and bottom, above and below the shopping cart details.'
                ),
                'default' => 'bottom',
                'desc_tip' => true,
            ),
            'show_on_checkout' => array(
                'title' => __('Checkout Page Display', 'paypal-for-woocommerce'),
                'type' => 'select',
                'options' => array(
                    'no' => __("Do not display on checkout page.", 'paypal-for-woocommerce'),
                    'top' => __('Display at the top of the checkout page.', 'paypal-for-woocommerce'),
                    'regular' => __('Display in general list of enabled gatways on checkout page.', 'paypal-for-woocommerce'),
                    'both' => __('Display both at the top and in the general list of gateways on the checkout page.')),
                'default' => 'top',
                'description' => __('Displaying the checkout button at the top of the checkout page will allow users to skip filling out the forms and can potentially increase conversion rates.'),
                'desc_tip' => true,
            ),
            'show_on_product_page' => array(
                'title' => __('Product Page', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Show the Express Checkout button on product detail pages.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'description' => __('Allows customers to checkout using PayPal directly from a product page.', 'paypal-for-woocommerce'),
                'desc_tip' => true,
            ),
            'paypal_account_optional' => array(
                'title' => __('PayPal Account Optional', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Allow customers to checkout without a PayPal account using their credit card.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'description' => __('PayPal Account Optional must be turned on in your PayPal account profile under Website Preferences.', 'paypal-for-woocommerce'),
                'desc_tip' => true,
            ),
            'landing_page' => array(
                'title' => __('Landing Page', 'paypal-for-woocommerce'),
                'type' => 'select',
                'description' => __('Type of PayPal page to display as default. PayPal Account Optional must be checked for this option to be used.', 'paypal-for-woocommerce'),
                'options' => array('login' => __('Login', 'paypal-for-woocommerce'),
                    'billing' => __('Billing', 'paypal-for-woocommerce')),
                'default' => 'login',
                'desc_tip' => true,
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
                'description' => __('Detailed displays actual errors returned from PayPal.  Generic displays general errors that do not reveal details and helps to prevent fraudulant activity on your site.', 'paypal-for-woocommerce'),
                'default' => 'detailed',
                'desc_tip' => true,
            ),
            'show_paypal_credit' => array(
                'title' => __('Enable PayPal Credit', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Show the PayPal Credit button next to the Express Checkout button.', 'paypal-for-woocommerce'),
                'default' => 'yes',
                'description' => ($this->not_us) ? __('Currently disabled because PayPal Credit is only available for U.S. merchants.', 'paypal-for-woocommerce') : "",
                'desc_tip' => ($this->not_us) ? true : false,
            ),
            'use_wp_locale_code' => array(
                'title' => __('Use WordPress Locale Code', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Pass the WordPress Locale Code setting to PayPal in order to display localized PayPal pages to buyers.', 'paypal-for-woocommerce'),
                'default' => 'yes'
            ),
            'page_style' => array(
                'title' => __('Page Style', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('If you wish to use a <a target="_blank" href="https://www.paypal.com/customize">custom page style configured in your PayPal account</a>, enter the name of the page style here.', 'paypal-for-woocommerce'),
                'default' => ''
            ),
            'brand_name' => array(
                'title' => __('Brand Name', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls what users see as the brand / company name on PayPal review pages.', 'paypal-for-woocommerce'),
                'default' => __(get_bloginfo('name'), 'paypal-for-woocommerce'),
                'desc_tip' => true,
            ),
            'checkout_logo' => array(
                'title' => __('PayPal Checkout Logo (190x60px)', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls what users see as the logo on PayPal review pages. ', 'paypal-for-woocommerce') . $require_ssl,
                'default' => '',
                'desc_tip' => true,
            ),
            'checkout_logo_hdrimg' => array(
                'title' => __('PayPal Checkout Banner (750x90px)', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls what users see as the header banner on PayPal review pages. ', 'paypal-for-woocommerce') . $require_ssl,
                'default' => '',
                'desc_tip' => true,
            ),
            'customer_service_number' => array(
                'title' => __('Customer Service Number', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls what users see for your customer service phone number on PayPal review pages.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
            ),
            'angelleye_skip_text' => array(
                'title' => __('Express Checkout Message', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This message will be displayed next to the PayPal Express Checkout button at the top of the checkout page.'),
                'default' => __('Skip the forms and pay faster with PayPal!', 'paypal-for-woocommerce'),
                'desc_tip' => true,
            ),
            'skip_final_review' => array(
                'title' => __('Skip Final Review', 'paypal-for-woocommerce'),
                'label' => __('Enables the option to skip the final review page.', 'paypal-for-woocommerce'),
                'description' => __('By default, users will be returned from PayPal and presented with a final review page which includes shipping and tax in the order details.  Enable this option to eliminate this page in the checkout process.') . '<br /><b class="final_review_notice"><span class="guest_checkout_notice">' . $skip_final_review_option_not_allowed_guest_checkout . '</span></b>' . '<b class="final_review_notice"><span class="terms_notice">' . $skip_final_review_option_not_allowed_terms . '</span></b>' . '<b class="final_review_notice"><span class="tokenized_payments_notice">' . $skip_final_review_option_not_allowed_tokenized_payments . '</span></b>',
                'type' => 'checkbox',
                'default' => 'no'
            ),
            'disable_term' => array(
                'title' => __('Disable Terms and Conditions', 'paypal-for-woocommerce'),
                'label' => __('Disable Terms and Conditions for Express Checkout orders.', 'paypal-for-woocommerce'),
                'description' => __('By default, if a Terms and Conditions page is set in WooCommerce, this would require the review page and would override the Skip Final Review option.  Check this option to disable Terms and Conditions for Express Checkout orders only so that you can use the Skip Final Review option.'),
                'type' => 'checkbox',
                'default' => 'no',
                'class' => 'disable_term',
            ),
            'payment_action' => array(
                'title' => __('Payment Action', 'paypal-for-woocommerce'),
                'label' => __('Whether to process as a Sale or Authorization.', 'paypal-for-woocommerce'),
                'description' => __('Sale will capture the funds immediately when the order is placed.  Authorization will authorize the payment but will not capture the funds.  You would need to capture funds from within the WooCommerce order when you are ready to deliver.'),
                'type' => 'select',
                'options' => array(
                    'Sale' => 'Sale',
                    'Authorization' => 'Authorization',
                    'Order' => 'Order'
                ),
                'default' => 'Sale',
                'desc_tip' => true,
            ),
            'billing_address' => array(
                'title' => __('Billing Address', 'paypal-for-woocommerce'),
                'label' => __('Set billing address in WooCommerce using the address returned by PayPal.', 'paypal-for-woocommerce'),
                'description' => __('PayPal only returns a shipping address back to the website.  Enable this option if you would like to use this address for both billing and shipping in WooCommerce.'),
                'type' => 'checkbox',
                'default' => 'no',
                'desc_tip' => true,
            ),
            'cancel_page' => array(
                'title' => __('Cancel Page', 'paypal-for-woocommerce'),
                'description' => __('Sets the page users will be returned to if they click the Cancel link on the PayPal checkout pages.'),
                'type' => 'select',
                'options' => $cancel_page,
                'desc_tip' => true,
            ),
            'send_items' => array(
                'title' => __('Send Item Details', 'paypal-for-woocommerce'),
                'label' => __('Send line item details to PayPal.', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Include all line item details in the payment request to PayPal so that they can be seen from the PayPal transaction details page.', 'paypal-for-woocommerce'),
                'default' => 'yes',
                'desc_tip' => true,
            ),
            'enable_tokenized_payments' => array(
                'title' => __('Enable Tokenized Payments', 'paypal-for-woocommerce'),
                'label' => __('Enable Tokenized Payments', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Allow buyers to securely save payment details to their account for quick checkout / auto-ship orders in the future. (Currently considered BETA for Express Checkout.)', 'paypal-for-woocommerce'),
                'default' => 'no',
                'class' => 'enable_tokenized_payments'
            ),
            'enable_notifyurl' => array(
                'title' => __('Enable PayPal IPN', 'paypal-for-woocommerce'),
                'label' => __('Configure an IPN URL to be included with Express Checkout payments.', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('This will override any URL configured in your PayPal account profile.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'class' => 'angelleye_enable_notifyurl',
                'desc_tip' => true,
            ),
            'notifyurl' => array(
                'title' => __('PayPal IPN URL', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Your URL for receiving Instant Payment Notification (IPN) for transactions.', 'paypal-for-woocommerce'),
                'class' => 'angelleye_notifyurl',
                'desc_tip' => true,
            ),
            'order_cancellations' => array(
                'title' => __('Auto Cancel / Refund Orders ', 'paypal-for-woocommerce'),
                'label' => '',
                'description' => __('Allows you to cancel and refund orders that do not meet PayPal\'s Seller Protection criteria.', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'order_cancellations',
                'options' => array(
                    'no_seller_protection' => __('Do *not* have PayPal Seller Protection', 'paypal-for-woocommerce'),
                    'no_unauthorized_payment_protection' => __('Do *not* have PayPal Unauthorized Payment Protection', 'paypal-for-woocommerce'),
                    'disabled' => __('Do not cancel any orders', 'paypal-for-woocommerce'),
                ),
                'default' => 'disabled',
                'desc_tip' => true,
            ),
            'email_notify_order_cancellations' => array(
                'title' => __('Order canceled/refunded Email Notifications', 'paypal-for-woocommerce'),
                'label' => __('Enable buyer email notifications for Order canceled/refunded', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('This will send buyer email notifications for Order canceled/refunded when Auto Cancel / Refund Orders option is selected.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'class' => 'email_notify_order_cancellations',
                'desc_tip' => true,
            ),
            'debug' => array(
                'title' => __('Debug', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => sprintf(__('Enable logging<code>%s</code>', 'paypal-for-woocommerce'), wc_get_log_file_path('paypal_express')),
                'default' => 'no'
            ),
            'is_encrypt' => array(
                'title' => __('', 'paypal-for-woocommerce'),
                'label' => __('', 'paypal-for-woocommerce'),
                'type' => 'hidden',
                'default' => 'yes',
                'class' => ''
            )
        );
        $this->form_fields = apply_filters('angelleye_ec_form_fields', $this->form_fields);
    }

    public function is_available() {
        return parent::is_available();
    }

    public function payment_fields() {
        if ($description = $this->get_description()) {
            echo wpautop(wptexturize($description));
        }
        $this->new_method_label = __('Create a new billing agreement', 'paypal-for-woocommerce');
        if ($this->supports('tokenization') && is_checkout()) {
            $this->tokenization_script();
            $this->saved_payment_methods();
            $this->save_payment_method_checkbox();
            do_action('payment_fields_saved_payment_methods', $this);
        }
    }

    public function process_payment($order_id) {
        try {
            if ($this->function_helper->ec_is_express_checkout()) {
                $return_url = add_query_arg('order_id', $order_id, $this->function_helper->ec_get_checkout_url('do_express_checkout_payment', $order_id));
                $args = array(
                    'result' => 'success',
                    'redirect' => $return_url,
                );
                if (isset($_POST['terms']) && wc_get_page_id('terms') > 0) {
                    WC()->session->paypal_express_terms = 1;
                }
                if (is_ajax()) {
                    if ($this->function_helper->ec_is_version_gte_2_4()) {
                        wp_send_json($args);
                    } else {
                        echo '<!--WC_START-->' . json_encode($args) . '<!--WC_END-->';
                    }
                } else {
                    wp_redirect($args['redirect']);
                }
                exit;
            } else {
                $_GET['pp_action'] = 'set_express_checkout';
                $this->handle_wc_api();
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_express_checkout_encrypt_gateway_api($settings) {
        if (!empty($settings['is_encrypt'])) {
            $gateway_settings_keys = array('sandbox_api_username', 'sandbox_api_password', 'sandbox_api_signature', 'api_username', 'api_password', 'api_signature');
            foreach ($gateway_settings_keys as $gateway_settings_key => $gateway_settings_value) {
                if (!empty($settings[$gateway_settings_value])) {
                    $settings[$gateway_settings_value] = AngellEYE_Utility::crypting($settings[$gateway_settings_value], $action = 'e');
                }
            }
        }
        return $settings;
    }

    public static function get_button_locale_code() {
        $_supportedLocale = array(
            'en_US', 'fr_XC', 'es_XC', 'zh_XC', 'en_AU', 'de_DE', 'nl_NL',
            'fr_FR', 'pt_BR', 'fr_CA', 'zh_CN', 'ru_RU', 'en_GB', 'zh_HK',
            'he_IL', 'it_IT', 'ja_JP', 'pl_PL', 'pt_PT', 'es_ES', 'sv_SE', 'zh_TW', 'tr_TR'
        );
        $locale = get_locale();
        if (!in_array($locale, $_supportedLocale)) {
            $locale = 'en_US';
        }
        return $locale;
    }

    public static function angelleye_get_paypalimage() {
        if (self::get_button_locale_code() == 'en_US') {
            return "https://www.paypalobjects.com/webstatic/" . self::get_button_locale_code() . "/i/buttons/checkout-logo-medium.png";
        } else {
            return esc_url(add_query_arg('cmd', '_dynamic-image', add_query_arg('locale', self::get_button_locale_code(), 'https://fpdbs.paypal.com/dynamicimageweb')));
        }
    }

    public function handle_wc_api() {
        try {
            if (!isset($_GET['pp_action'])) {
                return;
            }
            if (WC()->cart->cart_contents_total <= 0) {
                wc_add_notice(__('your order amount is zero, We were unable to process your order, please try again.', 'paypal-for-woocommerce'), 'error');
                wp_redirect(WC()->cart->get_cart_url());
                exit;
            }
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/express-checkout/class-wc-gateway-paypal-express-request-angelleye.php' );
            $paypal_express_request = new WC_Gateway_PayPal_Express_Request_AngellEYE($this);
            switch ($_GET['pp_action']) {
                case 'set_express_checkout':
                    $paypal_express_request->angelleye_set_express_checkout();
                    break;
                case 'get_express_checkout_details':
                    $paypal_express_request->angelleye_get_express_checkout_details();
                    if ($this->skip_final_review == 'yes') {
                        if (!defined('WOOCOMMERCE_CHECKOUT')) {
                            define('WOOCOMMERCE_CHECKOUT', true);
                        }
                        if (!defined('WOOCOMMERCE_CART')) {
                            define('WOOCOMMERCE_CART', true);
                        }
                        $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');
                        if (isset($_POST['shipping_method']) && is_array($_POST['shipping_method'])) {
                            foreach ($_POST['shipping_method'] as $i => $value) {
                                $chosen_shipping_methods[$i] = wc_clean($value);
                            }
                        }
                        WC()->session->set('chosen_shipping_methods', $chosen_shipping_methods);

                        if (WC()->cart->needs_shipping()) {
                            $packages = WC()->shipping->get_packages();
                            WC()->checkout()->shipping_methods = WC()->session->get('chosen_shipping_methods');
                        }
                        WC()->cart->calculate_totals();
                        $order_id = WC()->checkout()->create_order();
                        if (is_wp_error($order_id)) {
                            throw new Exception($order_id->get_error_message());
                        }
                        $order = wc_get_order($order_id);
                        $order->set_address(WC()->session->paypal_express_checkout['shipping_details'], 'billing');
                        $order->set_address(WC()->session->paypal_express_checkout['shipping_details'], 'shipping');
                        $order->set_payment_method($this->id);
                        update_post_meta($order_id, '_payment_method', $this->id);
                        update_post_meta($order_id, '_payment_method_title', $this->title);
                        update_post_meta($order_id, '_customer_user', get_current_user_id());
                        if (!empty(WC()->session->post_data['billing_phone'])) {
                            update_post_meta($order_id, '_billing_phone', WC()->session->post_data['billing_phone']);
                        }
                        if (!empty(WC()->session->post_data['order_comments'])) {
                            update_post_meta($order_id, 'order_comments', WC()->session->post_data['order_comments']);
                            $my_post = array(
                                'ID' => $order_id,
                                'post_excerpt' => WC()->session->post_data['order_comments'],
                            );
                            wp_update_post($my_post);
                        }
                        $_GET['order_id'] = $order_id;
                        do_action('woocommerce_checkout_order_processed', $order_id, array());
                        $paypal_express_request->angelleye_do_express_checkout_payment();
                    }
                    break;
                case 'do_express_checkout_payment':
                    $paypal_express_request->angelleye_do_express_checkout_payment();
                    break;
            }
        } catch (Exception $ex) {
            
        }
    }

    public function get_transaction_url($order) {
        if (!$this->supports('tokenization')) {
            $sandbox_transaction_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
            $live_transaction_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
            $is_sandbox = get_post_meta($order->id, 'is_sandbox', true);
            if ($is_sandbox == true) {
                $this->view_transaction_url = $sandbox_transaction_url;
            } else {
                if (empty($is_sandbox)) {
                    if ($this->testmode == 'yes') {
                        $this->view_transaction_url = $sandbox_transaction_url;
                    } else {
                        $this->view_transaction_url = $live_transaction_url;
                    }
                } else {
                    $this->view_transaction_url = $live_transaction_url;
                }
            }
        }
        return parent::get_transaction_url($order);
    }

    public static function log($message) {
        if (self::$log_enabled) {
            if (empty(self::$log)) {
                self::$log = new WC_Logger();
            }
            self::$log->add('paypal_express', $message);
        }
    }

}
