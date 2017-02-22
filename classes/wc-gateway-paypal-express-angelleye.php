<?php 

class WC_Gateway_PayPal_Express_AngellEYE extends WC_Payment_Gateway {

    /**
     * __construct function.  
     *
     * @access public
     * @return void
     */
    public function __construct() {
        $this->id = 'paypal_express';
        $this->method_title = __('PayPal Express Checkout ', 'paypal-for-woocommerce');
        $this->method_description = __('PayPal Express Checkout is designed to make the checkout experience for buyers using PayPal much more quick and easy than filling out billing and shipping forms.  Customers will be taken directly to PayPal to sign in and authorize the payment, and are then returned back to your store to choose a shipping method, review the final order total, and complete the payment.', 'paypal-for-woocommerce');
        $this->has_fields = false;
        $this->order_button_text  = __( 'Proceed to PayPal', 'paypal-for-woocommerce' );
        $this->supports = array(
            'products',
            'refunds'
        );
       
        if (substr(get_option("woocommerce_default_country"),0,2) != 'US') {
            $this->not_us= true;
        } else {
            $this->not_us = false;
        }

        // Load the form fields
        $this->init_form_fields();
        // Load the settings.
        $this->init_settings();
        // Get setting values
        $this->enable_tokenized_payments = $this->get_option('enable_tokenized_payments', 'no');
        if($this->enable_tokenized_payments == 'yes') {
            array_push($this->supports, "tokenization");
        }
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->testmode = $this->get_option('testmode', 'yes');
        $this->debug = $this->get_option('debug', 'no');
        $this->error_email_notify = 'yes' === $this->get_option('error_email_notify', 'no'); 
        $this->invoice_id_prefix = $this->get_option('invoice_id_prefix');
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
        if($this->enable_notifyurl == 'yes') {
            $this->notifyurl = $this->get_option('notifyurl'); 
            if( isset($this->notifyurl) && !empty($this->notifyurl)) {
                $this->notifyurl =  str_replace('&amp;', '&', $this->notifyurl);
            }
        }

        if ($this->not_us){
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
        $this->version = "64";  // PayPal SetExpressCheckout API version
        
        $this->Force_tls_one_point_two = get_option('Force_tls_one_point_two', 'no');
        
        $this->page_style = $this->get_option('page_style', ''); 
                
        // Actions
        add_action('woocommerce_api_' . strtolower(get_class()), array($this, 'paypal_express_checkout'), 12);
        add_action('woocommerce_receipt_paypal_express', array($this, 'receipt_page'));

        //Save settings
        add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_filter('woocommerce_settings_api_sanitized_fields_' . $this->id, array($this, 'angelleye_express_checkout_encrypt_gateway_api'), 10, 1);
        
        if ($this->enabled == 'yes' && ($this->show_on_checkout == 'top' || $this->show_on_checkout == 'both'))
            add_action('woocommerce_before_checkout_form', array($this, 'checkout_message'), 5);
        add_action('woocommerce_ppe_do_payaction', array($this, 'get_confirm_order'));
        add_action('woocommerce_after_checkout_validation', array($this, 'regular_checkout'), 99);
        add_action('woocommerce_before_cart_table', array($this, 'top_cart_button'));
        
        if(class_exists('WC_EU_VAT_Number')) {
            add_action( 'angelleye_wc_eu_vat_number', array('WC_EU_VAT_Number', 'process_checkout'), 10);
        }
        
    }
    
    /**
    * Admin Panel Options
    */
    public function admin_options() {
        $guest_checkout = get_option('woocommerce_enable_guest_checkout', 'yes');
        if ( wc_get_page_id( 'terms' ) > 0 && apply_filters( 'woocommerce_checkout_show_terms', true ) ) {
            if($guest_checkout === 'yes') {
                $display_disable_terms = 'yes';
            } else {
                $display_disable_terms = 'no';
            }
        } else {
            $display_disable_terms = 'no';
        }
        ?>
        <h3><?php _e('PayPal Express Checkout', 'paypal-for-woocommerce'); ?></h3>
        <p><?php _e($this->method_description, 'paypal-for-woocommerce'); ?></p>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
            <script type="text/javascript">
                var display_disable_terms = "<?php echo $display_disable_terms; ?>";
                <?php
                if( $guest_checkout === 'no' ) { ?>
                    jQuery("#woocommerce_paypal_express_skip_final_review").prop("checked", false);
                    jQuery("#woocommerce_paypal_express_skip_final_review").attr("disabled", true);
                <?php } ?>
                jQuery('#woocommerce_paypal_express_skip_final_review').change(function () {
                    disable_term = jQuery('#woocommerce_paypal_express_disable_term').closest('tr');
                    if (jQuery(this).is(':checked')) {
                        if(display_disable_terms === 'yes') {
                            disable_term.show();
                        } else {
                            disable_term.hide();
                        }
                    } else {
                        disable_term.hide();
                    }
                }).change();
                
                jQuery('#woocommerce_paypal_express_disable_term').change(function () {
                    term_notice = jQuery('.terms_notice');
                    if (jQuery(this).is(':checked')) {
                        term_notice.hide();
                    } else {
                        term_notice.show();
                    }
                }).change();
            </script>
        </table> <?php
    }

    /**
     * get_icon function.
     *
     * @access public
     * @return string
     */
    public function get_icon() {
        global $pp_settings;
        $image_path = plugins_url() . "/" . plugin_basename(dirname(dirname(__FILE__))) . '/assets/images/paypal.png';
        if ($this->show_paypal_credit == 'yes') {
            $image_path = plugins_url() . "/" . plugin_basename(dirname(dirname(__FILE__))) . '/assets/images/paypal-credit-card-logos.png';
        }
        if (empty($pp_settings['checkout_with_pp_button_type'])) {
            $pp_settings['checkout_with_pp_button_type'] = 'paypalimage';
        }
        if( !empty( $pp_settings['checkout_with_pp_button_type'] ) && $pp_settings['checkout_with_pp_button_type'] == 'customimage' ) {
            if( isset($pp_settings['pp_button_type_my_custom']) && !empty($pp_settings['pp_button_type_my_custom'])) {
                $image_path = $pp_settings['pp_button_type_my_custom'];
            }
        }
        $icon = "<img src=\"$image_path\" alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
        return apply_filters('woocommerce_paypal_express_icon', $icon, $this->id);
    }

    public function get_confirm_order($order) {
        $this->confirm_order_id = $order->id;
    }

    public function is_express_checkout_credentials_is_set() {
        if ('yes' != $this->enabled) {
            return false;
        }
        if (!$this->api_username || !$this->api_password || !$this->api_signature) {
            return false;
        }
        return true;
    }
    
    function is_available() {
        if(!$this->is_express_checkout_credentials_is_set()) {
            return false;
        }
        if(!AngellEYE_Utility::is_valid_for_use_paypal_express()) {
                return false;
        }
        if( $this->show_on_checkout == 'regular' || $this->show_on_checkout == 'both') {
            return true;
        }
        return false;
    }
    
    /**
     * Use WooCommerce logger if debug is enabled.
     */
    function add_log($message) {
        if ($this->debug == 'yes') {
            if (empty($this->log))
                $this->log = new WC_Logger();
            $this->log->add('paypal_express', $message);
        }
    }



    /**
     * Initialize Gateway Settings Form Fields
     */
    function init_form_fields() {

        $require_ssl = '';
        $display_disable_terms = false;
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
        if ( wc_get_page_id( 'terms' ) > 0 && apply_filters( 'woocommerce_checkout_show_terms', true ) ) {
            $skip_final_review_option_not_allowed_terms = ' (You currently have a Terms &amp; Conditions page set, which requires the review page, and will override this option.)';
        }
        $this->enable_tokenized_payments = $this->get_option('enable_tokenized_payments', 'no');
        if($this->enable_tokenized_payments == 'yes') {
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
                'default' => __('PayPal Express', 'paypal-for-woocommerce')
            ),
            'description' => array(
                'title' => __('Description', 'paypal-for-woocommerce'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'paypal-for-woocommerce'),
                'default' => __("Pay via PayPal; you can pay with your credit card if you don't have a PayPal account", 'paypal-for-woocommerce')
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
            'testmode' => array(
                'title' => __('PayPal Sandbox', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable PayPal Sandbox', 'paypal-for-woocommerce'),
                'default' => 'yes',
                'description' => __('The sandbox is PayPal\'s test environment and is only for use with sandbox accounts created within your <a href="http://developer.paypal.com" target="_blank">PayPal developer account</a>.', 'paypal-for-woocommerce')
            ),
            'debug' => array(
                'title' => __('Debug', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => sprintf( __( 'Enable logging<code>%s</code>', 'paypal-for-woocommerce' ), wc_get_log_file_path( 'paypal_express' ) ),
                'default' => 'no'
            ),
            
            'error_email_notify' => array(
                'title' => __('Error Email Notifications', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable admin email notifications for errors.', 'paypal-for-woocommerce'),
                'default' => 'yes',
                'description' => __('This will send a detailed error email to the WordPress site administrator if a PayPal API error occurs.', 'paypal-for-woocommerce')
            ),
            'invoice_id_prefix' => array(
                'title' => __('Invoice ID Prefix', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Add a prefix to the invoice ID sent to PayPal. This can resolve duplicate invoice problems when working with multiple websites on the same PayPal account.', 'paypal-for-woocommerce'),
            ),
            /*
              'checkout_with_pp_button' => array(
              'title' => __( 'Checkout Button Style', 'paypal-for-woocommerce' ),
              'type' => 'checkbox',
              'label' => __( 'Use Checkout with PayPal image button', 'paypal-for-woocommerce' ),
              'default' => 'yes'
              ),
             */
            'checkout_with_pp_button_type' => array(
                'title' => __('Checkout Button Type', 'paypal-for-woocommerce'),
                'type' => 'select',
                'label' => __('Use Checkout with PayPal image button', 'paypal-for-woocommerce'),
                'class' => 'checkout_with_pp_button_type',
                'options' => array(
                    'paypalimage' => __('PayPal Image', 'paypal-for-woocommerce'),
                    'textbutton' => __('Text Button', 'paypal-for-woocommerce'),
                    'customimage' => __('Custom Image', 'paypal-for-woocommerce')
                ) // array of options for select/multiselects only
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
            /* 'hide_checkout_button' => array(
              'title' => __( 'Standard Checkout Button', 'paypal-for-woocommerce' ),
              'type' => 'checkbox',
              'label' => __( 'Hide standard checkout button on cart page', 'paypal-for-woocommerce' ),
              'default' => 'no'
              ), */
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
                'default' => 'bottom'
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
                'description' => __('Displaying the checkout button at the top of the checkout page will allow users to skip filling out the forms and can potentially increase conversion rates.')
            ),
            'show_on_product_page' => array(
                'title' => __('Product Page', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Show the Express Checkout button on product detail pages.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'description' => __('Allows customers to checkout using PayPal directly from a product page.', 'paypal-for-woocommerce')
            ),
            'paypal_account_optional' => array(
                'title' => __('PayPal Account Optional', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Allow customers to checkout without a PayPal account using their credit card.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'description' => __('PayPal Account Optional must be turned on in your PayPal account profile under Website Preferences.', 'paypal-for-woocommerce')
            ),
            'landing_page' => array(
                'title' => __('Landing Page', 'paypal-for-woocommerce'),
                'type' => 'select',
                'description' => __('Type of PayPal page to display as default. PayPal Account Optional must be checked for this option to be used.', 'paypal-for-woocommerce'),
                'options' => array('login' => __('Login', 'paypal-for-woocommerce'),
                    'billing' => __('Billing', 'paypal-for-woocommerce')),
                'default' => 'login',
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
									and helps to prevent fraudulant activity on your site.', 'paypal-for-woocommerce'),
                'default' => 'detailed',
            ),
            'show_paypal_credit' => array(
                'title' => __('Enable PayPal Credit', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Show the PayPal Credit button next to the Express Checkout button.', 'paypal-for-woocommerce'),
                'default' => 'yes',
                'description' => ($this->not_us) ? __('Currently disabled because PayPal Credit is only available for U.S. merchants.', 'paypal-for-woocommerce'):""
            ),
            'use_wp_locale_code' => array(
                'title' => __('Use WordPress Locale Code', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Pass the WordPress Locale Code setting to PayPal in order to display localized PayPal pages to buyers.', 'paypal-for-woocommerce'),
                'default' => 'yes'
            ),
            'page_style' => array(
                'title' => __( 'Page Style', 'paypal-for-woocommerce' ), 
                'type' => 'text', 
                'description' => __( 'If you wish to use a <a target="_blank" href="https://www.paypal.com/customize">custom page style configured in your PayPal account</a>, enter the name of the page style here.', 'paypal-for-woocommerce' ),
                'default' => ''
            ),
            'brand_name' => array(
                'title' => __('Brand Name', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls what users see as the brand / company name on PayPal review pages.', 'paypal-for-woocommerce'),
                'default' => __(get_bloginfo('name'), 'paypal-for-woocommerce')
            ),
            'checkout_logo' => array(
                'title' => __('PayPal Checkout Logo (190x60px)', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls what users see as the logo on PayPal review pages. ', 'paypal-for-woocommerce') . $require_ssl,
                'default' => ''
            ),
            'checkout_logo_hdrimg' => array(
                'title' => __('PayPal Checkout Banner (750x90px)', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls what users see as the header banner on PayPal review pages. ', 'paypal-for-woocommerce') . $require_ssl,
                'default' => ''
            ),
            'customer_service_number' => array(
                'title' => __('Customer Service Number', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls what users see for your customer service phone number on PayPal review pages.', 'paypal-for-woocommerce'),
                'default' => ''
            ),
            'gift_wrap_enabled' => array(
                'title' => __('Gift Wrap', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enables the gift wrap options for buyers.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'description' => __('This will display a gift wrap option to buyers during checkout based on the following Gift Wrap settings.', 'paypal-for-woocommerce')
            ),
            'gift_message_enabled' => array(
                'title' => __('Gift Message', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enables the gift message widget on PayPal pages.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'description' => __('This will allow buyers to enter a message they would like to include with the item as a gift.', 'paypal-for-woocommerce')
            ),
            'gift_receipt_enabled' => array(
                'title' => __('Gift Receipt', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enables the gift receipt widget on PayPal pages.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'description' => __('This will allow buyers to choose whether or not to include a gift receipt in the order.', 'paypal-for-woocommerce')
            ),
            'gift_wrap_name' => array(
                'title' => __('Gift Wrap Name', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Label for the gift wrap option on PayPal pages, such as "Box with ribbon"', 'paypal-for-woocommerce'),
                'default' => __('Box with ribbon', 'paypal-for-woocommerce')
            ),
            'gift_wrap_amount' => array(
                'title' => __('Gift Wrap Amount', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Amount to be charged to the buyer for adding the gift wrap option.', 'paypal-for-woocommerce'),
                'default' => __('0.00', 'paypal-for-woocommerce')
            ),
            'angelleye_skip_text' => array(
                'title' => __('Express Checkout Message', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This message will be displayed next to the PayPal Express Checkout button at the top of the checkout page.'),
                'default' => __('Skip the forms and pay faster with PayPal!', 'paypal-for-woocommerce')
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
                'default' => 'Sale'
            ),
            'billing_address' => array(
                'title' => __('Billing Address', 'paypal-for-woocommerce'),
                'label' => __('Set billing address in WooCommerce using the address returned by PayPal.', 'paypal-for-woocommerce'),
                'description' => __('PayPal only returns a shipping address back to the website.  Enable this option if you would like to use this address for both billing and shipping in WooCommerce.'),
                'type' => 'checkbox',
                'default' => 'no'
            ),
            'cancel_page' => array(
                'title' => __('Cancel Page', 'paypal-for-woocommerce'),
                'description' => __('Sets the page users will be returned to if they click the Cancel link on the PayPal checkout pages.'),
                'type' => 'select',
                'options' => $cancel_page,
            ),
            'send_items' => array(
                'title' => __('Send Item Details', 'paypal-for-woocommerce'),
                'label' => __('Send line item details to PayPal.', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Include all line item details in the payment request to PayPal so that they can be seen from the PayPal transaction details page.', 'paypal-for-woocommerce'),
                'default' => 'yes'
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
                'class' => 'angelleye_enable_notifyurl'
            ),
            'notifyurl' => array(
                'title' => __('PayPal IPN URL', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Your URL for receiving Instant Payment Notification (IPN) for transactions.', 'paypal-for-woocommerce'),
                'class' => 'angelleye_notifyurl'
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
                'default' => 'disabled'
             ),
            
            'email_notify_order_cancellations' => array(
                'title' => __('Order canceled/refunded Email Notifications', 'paypal-for-woocommerce'),
                'label' => __('Enable buyer email notifications for Order canceled/refunded', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('This will send buyer email notifications for Order canceled/refunded when Auto Cancel / Refund Orders option is selected.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'class' => 'email_notify_order_cancellations'
            ),
            'is_encrypt' => array(
                'title' => __('', 'paypal-for-woocommerce'),
                'label' => __('', 'paypal-for-woocommerce'),
                'type' => 'hidden',
                'default' => 'yes',
                'class' => ''
            )
                /* 'Locale' => array(
                  'title' => __( 'Locale', 'paypal-for-woocommerce' ),
                  'type' => 'select',
                  'description' => __( 'Locale of pages displayed by PayPal during Express Checkout. It is one of the following values supported by PayPal', 'paypal-for-woocommerce'  ),
                  'options' => array(
                  "AU"=>"Australia",
                  "AT"=>"Austria",
                  "BE"=>"Belgium",
                  "BR"=>"Brazil",
                  "CA"=>"Canada",
                  "CH"=>"Switzerland",
                  "CN"=>"China",
                  "DE"=>"Germany",
                  "ES"=>"Spain",
                  "GB"=>"United Kingdom",
                  "FR"=>"France",
                  "IT"=>"Italy",
                  "NL"=>"Netherlands",
                  "PL"=>"Poland",
                  "PT"=>"Portugal",
                  "RU"=>"Russia",
                  "US"=>"United States"),
                  'default' => 'US',
                  ) */
        );
        $this->form_fields = apply_filters('angelleye_ec_form_fields', $this->form_fields);
    }

    /**
     *  Checkout Message
     */
    function checkout_message() {
        global $pp_settings;

        if(!$this->is_express_checkout_credentials_is_set()) {
                return false;
        }
        if(!AngellEYE_Utility::is_valid_for_use_paypal_express()) {
                return false;
        }
        if (WC()->cart->total > 0) {
            wp_enqueue_script('angelleye_button');
            $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
            // Pay with Credit Card

            unset($payment_gateways['paypal_pro']);
            unset($payment_gateways['paypal_pro_payflow']);
            echo '<div id="checkout_paypal_message" class="woocommerce-info info">';
            echo '<div id="paypal_box_button">';
            if (empty($pp_settings['checkout_with_pp_button_type']))
                $pp_settings['checkout_with_pp_button_type'] = 'paypalimage';

            $_angelleyeOverlay = '<div class="blockUI blockOverlay angelleyeOverlay" style="display:none;z-index: 1000; border: none; margin: 0px; padding: 0px; width: 100%; height: 100%; top: 0px; left: 0px; opacity: 0.6; cursor: default; position: absolute; background: url('. WC()->plugin_url() .'/assets/images/select2-spinner.gif) 50% 50% / 16px 16px no-repeat rgb(255, 255, 255);"></div>';
            
            switch ($pp_settings['checkout_with_pp_button_type']) {
                case "textbutton":
                    if (!empty($pp_settings['pp_button_type_text_button'])) {
                        $button_text = $pp_settings['pp_button_type_text_button'];
                    } else {
                        $button_text = __('Proceed to Checkout', 'paypal-for-woocommerce');
                    }
                    echo '<div class="paypal_ec_textbutton">';
                    echo '<a class="paypal_checkout_button paypal_checkout_button_text button alt" href="' . esc_url(add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">' . $button_text . '</a>';
                    echo $_angelleyeOverlay;
                    echo '</div>';
                    break;
                case "paypalimage":
                    echo '<div id="paypal_ec_button">';
                    echo '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">';
                    echo "<img src='".WC_Gateway_PayPal_Express_AngellEYE::angelleye_get_paypalimage()."' border='0' alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
                    echo "</a>";
                    echo $_angelleyeOverlay;
                    echo '</div>';
                    break;
                case "customimage":
                    $button_img = $pp_settings['pp_button_type_my_custom'];
                    echo '<div id="paypal_ec_button">';
                    echo '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">';
                    echo "<img src='{$button_img}' width='150' border='0' alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
                    echo "</a>";
                    echo $_angelleyeOverlay;
                    echo '</div>';
                    break;
            }
            //echo '<div id="paypal_ec_button">';
            //echo '<a class="paypal_checkout_button" href="' . add_query_arg( 'pp_action', 'expresscheckout', add_query_arg( 'wc-api', get_class(), home_url( '/' ) ) ) . '">';
            //echo "<img src='https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif' width='150' alt='Check out with PayPal'/>";
            //echo '</a>';
            //echo '</div>';
            /**
             * Displays the PayPal Credit checkout button if enabled in EC settings.
             */
            if ($this->show_paypal_credit == 'yes') {
                // PayPal Credit button
                $paypal_credit_button_markup = '<div id="paypal_ec_paypal_credit_button">';
                $paypal_credit_button_markup .= '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('use_paypal_credit', 'true', add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/'))))) . '" >';
                $paypal_credit_button_markup .= "<img src='https://www.paypalobjects.com/webstatic/en_US/i/buttons/ppcredit-logo-small.png' alt='Check out with PayPal Credit'/>";
                $paypal_credit_button_markup .= '</a>';
                $paypal_credit_button_markup .= $_angelleyeOverlay;
                $paypal_credit_button_markup .= '</div>';

                echo $paypal_credit_button_markup;
            }

            echo '<div class="woocommerce_paypal_ec_checkout_message">';
            if (!isset($this->settings['angelleye_skip_text'])) {
                echo '<p class="checkoutStatus">', __('Skip the forms and pay faster with PayPal!', 'paypal-for-woocommerce'), '</p>';
            } else {
                echo '<p class="checkoutStatus">', $this->angelleye_skip_text, '</p>';
            }
            echo '</div>';
            echo '<div class="clear"></div></div>';
            ?>
            <!--<div class="blockUI blockOverlay angelleyeOverlay" style="display:none;z-index: 1000; border: none; margin: 0px; padding: 0px; width: 100%; height: 100%; top: 0px; left: 0px; opacity: 0.6; cursor: default; position: absolute; background: url(<?php /*echo WC()->plugin_url(); */?>/assets/images/select2-spinner.gif) 50% 50% / 16px 16px no-repeat rgb(255, 255, 255);"></div>-->
            <?php
            echo '</div>';
            echo '<div style="clear:both; margin-bottom:10px;"></div>';

            //echo apply_filters( 'woocommerce_ppe_checkout_message', __( 'Have a PayPal account?', 'paypal-for-woocommerce' ) ) . '</p>';
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
    function paypal_express_checkout($posted = null) {
        
        if (!empty($posted) || ( isset($_GET['pp_action']) && $_GET['pp_action'] == 'expresscheckout' )) {
            $this->angelleye_check_cart_items();
            if (sizeof(WC()->cart->get_cart()) > 0) {

                // The customer has initiated the Express Checkout process with the button on the cart page
                if (!defined('WOOCOMMERCE_CHECKOUT'))
                    define('WOOCOMMERCE_CHECKOUT', true);
                $this->add_log('Start Express Checkout');

                /**
                 * Check if the EC button used was the PayPal Credit button.
                 * This $usePayPalCredit flag will be used to adjust the SEC request accordingly.
                 */
                if (isset($_GET['use_paypal_credit']) && 'true' == $_GET['use_paypal_credit']) {
                    $usePayPalCredit = true;
                } else {
                    $usePayPalCredit = false;
                }

                WC()->cart->calculate_totals();
                //$paymentAmount    = WC()->cart->get_total();
                $paymentAmount = AngellEYE_Gateway_Paypal::number_format(WC()->cart->total);

                //Check if review order page is exist, otherwise re-create it on the fly
                $review_order_page_url = get_permalink(wc_get_page_id('review_order'));
                if (!$review_order_page_url) {
                    $this->add_log(__('Review Order Page not found, re-create it. ', 'paypal-for-woocommerce'));
                    include_once( WC()->plugin_path() . '/includes/admin/wc-admin-functions.php' );
                    $page_id = wc_create_page(esc_sql(_x('review-order', 'page_slug', 'paypal-for-woocommerce')), 'woocommerce_review_order_page_id', __('Checkout &rarr; Review Order', 'paypal-for-woocommerce'), '[woocommerce_review_order]', wc_get_page_id('checkout'));
                    $review_order_page_url = get_permalink($page_id);
                }
                $review_order_page_url = add_query_arg( 'utm_nooverride', '1', $review_order_page_url );
                $returnURL = urlencode(add_query_arg('pp_action', 'revieworder', $review_order_page_url));
                $cancelURL = isset($this->settings['cancel_page']) ? get_permalink($this->settings['cancel_page']) : WC()->cart->get_cart_url();
                $cancelURL = apply_filters('angelleye_express_cancel_url', urlencode($cancelURL));
                $resArray = $this->CallSetExpressCheckout($paymentAmount, $returnURL, $cancelURL, $usePayPalCredit, $posted);
                $ack = strtoupper($resArray["ACK"]);

                /**
                 * I've replaced the original redirect URL's here with
                 * what the PayPal class library returns so that options like
                 * "skip details" will work correctly with PayPal's review pages.
                 */
                if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING") {
                    $this->add_log('Redirecting to PayPal');
                    if (is_ajax()) {
                        $result = array(
                            //'redirect' => $this->PAYPAL_URL . $resArray["TOKEN"],
                            'redirect' => $resArray['REDIRECTURL'],
                            'result' => 'success'
                        );

                        echo json_encode($result);
                        exit;
                    } else {
                        //$this->RedirectToPayPal( $resArray["TOKEN"] );
                        wp_redirect($resArray['REDIRECTURL']);
                        exit;
                    }
                } else {
                    // Display a user friendly Error on the page and log details
                    $ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
                    $ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
                    $ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
                    $ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);
                    $this->add_log(__('SetExpressCheckout API call failed. ', 'paypal-for-woocommerce'));
                    $this->add_log(__('Detailed Error Message: ', 'paypal-for-woocommerce') . $ErrorLongMsg);
                    $this->add_log(__('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg);
                    $this->add_log(__('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode);
                    $this->add_log(__('Error Severity Code: ', 'paypal-for-woocommerce') . $ErrorSeverityCode);

                    // Notice admin if has any issue from PayPal
                    $message = '';
                    if ($this->error_email_notify) {
                        $admin_email = get_option("admin_email");
                        $message .= __("SetExpressCheckout API call failed.", "paypal-for-woocommerce") . "\n\n";
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

                    // Generate error message based on Error Display Type setting
                    if ($this->error_display_type == 'detailed') {
                        $sec_error_notice = $ErrorCode . ' - ' . $ErrorLongMsg;
                        $error_display_type_message = sprintf(__($sec_error_notice, 'paypal-for-woocommerce'));
                    } else {
                        $error_display_type_message = sprintf(__('There was a problem paying with PayPal.  Please try another method.', 'paypal-for-woocommerce'));
                    }
                    $error_display_type_message = apply_filters( 'ae_ppec_error_user_display_message', $error_display_type_message, $ErrorCode, $ErrorLongMsg );
                    wc_add_notice($error_display_type_message, 'error');
                    if (!is_ajax()) {
                        wp_redirect(get_permalink(wc_get_page_id('cart')));
                        exit;
                    } else {
                        return;
                    }
                }
            }
        }
        elseif (isset($_GET['pp_action']) && $_GET['pp_action'] == 'revieworder') {
            // The customer has logged into PayPal and approved order.
            // Retrieve the shipping details and present the order for completion.
            if (!defined('WOOCOMMERCE_CHECKOUT'))
                define('WOOCOMMERCE_CHECKOUT', true);
            $this->add_log('Start Review Order');
            if (isset($_GET['token'])) {
                $token = $_GET['token'];
                $this->set_session('TOKEN', $token);
            }
            if (isset($_GET['PayerID'])) {
                $payerID = $_GET['PayerID'];
                $this->set_session('PayerID', $payerID);
            }
            $this->add_log("...Token:" . $this->get_session('TOKEN'));
            $this->add_log("...PayerID: " . $this->get_session('PayerID'));

            //if empty TOKEN redirect to cart page
            if (empty(WC()->session->TOKEN)) {
                $ms = sprintf(__('Sorry, your session has expired. <a href=%s>Return to homepage &rarr;</a>', 'paypal-for-woocommerce'), '"' . home_url() . '"');
                $ec_confirm_message = apply_filters('angelleye_ec_confirm_message', $ms);
                wc_add_notice($ec_confirm_message, "error");
                wp_redirect(get_permalink(wc_get_page_id('cart')));
            } else {

                $result = $this->CallGetShippingDetails($this->get_session('TOKEN'));

                if (!empty($result)) {
                    $this->set_session('RESULT', serialize($result));
                    if (isset($result['SHIPTOCOUNTRYCODE'])) {
                        /**
                         * Check if shiptocountry is in the allowed countries list
                         */
                        if (!array_key_exists($result['SHIPTOCOUNTRYCODE'], WC()->countries->get_allowed_countries())) {
                            wc_add_notice(sprintf(__('We do not sell in your country, please try again with another address.', 'paypal-for-woocommerce')), 'error');
                            wp_redirect(get_permalink(wc_get_page_id('cart')));
                            exit;
                        };
                        $this->paypal_for_woocommerce_update_user_meta($result);
                        WC()->customer->set_shipping_country($result['SHIPTOCOUNTRYCODE']);
                    }

                    if (isset($result['FIRSTNAME'])) {
                        WC()->customer->firstname = $result['FIRSTNAME'];
                    }
                    if (isset($result['LASTNAME'])) {
                        WC()->customer->lastname = $result['LASTNAME'];
                    }
                    if (isset($result['SHIPTONAME'])) {
                        WC()->customer->shiptoname = $result['SHIPTONAME'];
                    }
                    if (isset($result['SHIPTOSTREET'])) {
                        WC()->customer->set_shipping_address($result['SHIPTOSTREET']);
                    }
                    if (isset($result['SHIPTOSTREET2'])) {
                        WC()->customer->set_shipping_address_2($result['SHIPTOSTREET2']);
                    }
                    if (isset($result['SHIPTOCITY'])) {
                        WC()->customer->set_shipping_city($result['SHIPTOCITY']);
                    }
                    if (isset($result['SHIPTOCOUNTRYCODE'])) {
                        WC()->customer->set_shipping_country($result['SHIPTOCOUNTRYCODE']);
                    }
                    if (isset($result['SHIPTOSTATE'])) {
                        WC()->customer->set_shipping_state($this->get_state_code($result['SHIPTOCOUNTRYCODE'], $result['SHIPTOSTATE']));
                    }
                    if (isset($result['SHIPTOZIP'])) {
                        WC()->customer->set_shipping_postcode($result['SHIPTOZIP']);
                    }
                    
                    if ($this->billing_address == 'yes') {
                        if (isset($result['SHIPTOSTREET'])) {
                            WC()->customer->set_address($result['SHIPTOSTREET']);
                        }
                        if (isset($result['SHIPTOSTREET2'])) {
                            WC()->customer->set_address_2($result['SHIPTOSTREET2']);
                        }
                        if (isset($result['SHIPTOCITY'])) {
                            WC()->customer->set_city($result['SHIPTOCITY']);
                        }
                        if (isset($result['SHIPTOCOUNTRYCODE'])) {
                            WC()->customer->set_country($result['SHIPTOCOUNTRYCODE']);
                        }
                        if (isset($result['SHIPTOSTATE'])) {
                            WC()->customer->set_state($this->get_state_code($result['SHIPTOCOUNTRYCODE'], $result['SHIPTOSTATE']));
                        }
                        if (isset($result['SHIPTOZIP'])) {
                            WC()->customer->set_postcode($result['SHIPTOZIP']);
                        }
                    }

                    /**
                     * Save GECD data in sessions for use in DECP
                     */
                    $this->set_session('company', isset($result['BUSINESS']) ? $result['BUSINESS'] : '');
                    $this->set_session('firstname', isset($result['FIRSTNAME']) ? $result['FIRSTNAME'] : '');
                    $this->set_session('lastname', isset($result['LASTNAME']) ? $result['LASTNAME'] : '');
                    $this->set_session('shiptoname', isset($result['SHIPTONAME']) ? $result['SHIPTONAME'] : '');
                    $this->set_session('shiptostreet', isset($result['SHIPTOSTREET']) ? $result['SHIPTOSTREET'] : '');
                    $this->set_session('shiptostreet2', isset($result['SHIPTOSTREET2']) ? $result['SHIPTOSTREET2'] : '');
                    $this->set_session('shiptocity', isset($result['SHIPTOCITY']) ? $result['SHIPTOCITY'] : '');
                    $this->set_session('shiptocountrycode', isset($result['SHIPTOCOUNTRYCODE']) ? $result['SHIPTOCOUNTRYCODE'] : '');
                    $this->set_session('shiptostate', isset($result['SHIPTOSTATE']) ? $result['SHIPTOSTATE'] : '');
                    $this->set_session('shiptozip', isset($result['SHIPTOZIP']) ? $result['SHIPTOZIP'] : '');
                    $this->set_session('payeremail', isset($result['EMAIL']) ? $result['EMAIL'] : '');
                    $this->set_session('giftmessage', isset($result['GIFTMESSAGE']) ? $result['GIFTMESSAGE'] : '');
                    $this->set_session('giftreceiptenable', isset($result['GIFTRECEIPTENABLE']) ? $result['GIFTRECEIPTENABLE'] : '');
                    $this->set_session('giftwrapname', isset($result['GIFTWRAPNAME']) ? $result['GIFTWRAPNAME'] : '');
                    $this->set_session('giftwrapamount', isset($result['GIFTWRAPAMOUNT']) ? $result['GIFTWRAPAMOUNT'] : '');
                    $this->set_session('customer_notes', isset($result['PAYMENTREQUEST_0_NOTETEXT']) ? $result['PAYMENTREQUEST_0_NOTETEXT'] : '');
                    $this->set_session('phonenum', isset($result['PHONENUM']) ? $result['PHONENUM'] : '');
                }
                else {
                    $this->add_log("...ERROR: GetShippingDetails returned empty result");
                }
                WC()->cart->calculate_totals();
                if( $this->skip_final_review == 'yes' && ( get_option('woocommerce_enable_guest_checkout') === "yes" && apply_filters('woocommerce_enable_guest_checkout', get_option('woocommerce_enable_guest_checkout')) == "yes" || is_user_logged_in() )) {
                    if($this->supports( 'tokenization' )) {
                        return;
                    }
                    //check terms enable
                    $checkout_form_data = maybe_unserialize(WC()->session->checkout_form);
                    if ($this->disable_term == 'yes' || ( !( wc_get_page_id( 'terms' ) > 0 && apply_filters( 'woocommerce_checkout_show_terms', true ) && empty( $checkout_form_data['terms'] )))) {
                        $url = add_query_arg(array( 'pp_action' => 'payaction'));
                        wp_redirect($url);
                        exit();
                    }
                }

                if (isset($_POST['createaccount'])) {
                    $this->customer_id = apply_filters('woocommerce_checkout_customer_id', get_current_user_id());
                    if (empty($_POST['username'])) {
                        wc_add_notice(__('Username is required', 'paypal-for-woocommerce'), 'error');
                    } elseif (username_exists($_POST['username'])) {
                        wc_add_notice(__('This username is already registered.', 'paypal-for-woocommerce'), 'error');
                    } elseif (empty($_POST['email']) || !is_email($_POST['email'])) {
                        wc_add_notice(__('Please provide a valid email address.', 'paypal-for-woocommerce'), 'error');
                    } elseif (empty($_POST['password']) || empty($_POST['repassword'])) {
                        wc_add_notice(__('Password is required.', 'paypal-for-woocommerce'), 'error');
                    } elseif ($_POST['password'] != $_POST['repassword']) {
                        wc_add_notice(__('Passwords do not match.', 'paypal-for-woocommerce'), 'error');
                    } elseif (get_user_by('email', $_POST['email']) != false) {
                        wc_add_notice(__('This email address is already registered.', 'paypal-for-woocommerce'), 'error');
                    } else {

                        $username = !empty($_POST['username']) ? $_POST['username'] : '';
                        $password = !empty($_POST['password']) ? $_POST['password'] : '';
                        $email = $_POST['email'];

                        try {

                            // Anti-spam trap
                            if (!empty($_POST['email_2'])) {
                                throw new Exception(__('Anti-spam field was filled in.', 'paypal-for-woocommerce'));
                                wc_add_notice('<strong>' . __('Anti-spam field was filled in.', 'paypal-for-woocommerce') . ':</strong> ', 'error');
                            }

                            $new_customer = wc_create_new_customer(sanitize_email($email), wc_clean($username), $password);

                            if (is_wp_error($new_customer)) {
                                wc_add_notice($new_customer->get_error_message(), 'error');
                            }

                            if (apply_filters('paypal-for-woocommerce_registration_auth_new_customer', true, $new_customer)) {
                                wc_set_customer_auth_cookie($new_customer);
                            }

                             $creds = array(
                                'user_login' => wc_clean($username),
                                'user_password' => $password,
                                'remember' => true,
                            );
                            $user = wp_signon( $creds, false );
                            if ( is_wp_error($user) ) {
                                wc_add_notice($user->get_error_message(), 'error');
                            } else {
                                wp_set_current_user($user->ID); //Here is where we update the global user variables
                                $secure_cookie = is_ssl() ? true : false;
                                wp_set_auth_cookie( $user->ID, true, $secure_cookie );
                            }

                        } catch (Exception $e) {
                            wc_add_notice('<strong>' . __('Error', 'paypal-for-woocommerce') . ':</strong> ' . $e->getMessage(), 'error');
                        }

                        $this->customer_id = $user->ID;

                             // As we are now logged in, checkout will need to refresh to show logged in data
                        WC()->session->set('reload_checkout', true);
                        
                        $this->paypal_for_woocommerce_update_user_meta($result);

                        // Also, recalculate cart totals to reveal any role-based discounts that were unavailable before registering
                        WC()->cart->calculate_totals();

                        

                        //reload the page
                        wp_redirect(add_query_arg(array( 'pp_action' => 'revieworder')));
                        exit();
                    }
                }
            }
        } elseif (isset($_GET['pp_action']) && $_GET['pp_action'] == 'payaction') {
            if( isset($_POST) || ( $this->skip_final_review == 'yes' && (get_option('woocommerce_enable_guest_checkout') === "yes" || apply_filters('woocommerce_enable_guest_checkout', get_option('woocommerce_enable_guest_checkout')) == "yes" ) || is_user_logged_in() ) ) {
               $result = unserialize(WC()->session->RESULT);
                /* create account start */
                if (isset($_POST['createaccount']) && !empty($_POST['createaccount'])) {
                    $this->customer_id = apply_filters('woocommerce_checkout_customer_id', get_current_user_id());
                    $create_user_email = $_POST['email'];
                    $create_user_name = sanitize_user( current( explode( '@', $create_user_email ) ), true );

                    // Ensure username is unique
                    $append     = 1;
                    $o_username = $create_user_name;
                    while ( username_exists( $create_user_name ) ) {
                        $create_user_name = $o_username . $append;
                        $append ++;
                    }
                    //If have any issue, redirect to review-order page
                    $create_acc_error = false;
                    if (empty($_POST['email']) || !is_email($_POST['email'])) {
                        wc_add_notice(__('Please provide a valid email address.', 'paypal-for-woocommerce'), 'error');
                    } elseif (get_user_by('email', $create_user_email) != false) {
                        wc_add_notice(__('This email address is already registered.', 'paypal-for-woocommerce'), 'error');
                        $create_acc_error = true;
                    } elseif (empty($_POST['create_act'])) {
                        wc_add_notice(__('Password is required.', 'paypal-for-woocommerce'), 'error');
                        $create_acc_error = true;
                    } else {
                        $username = !empty($create_user_name) ? $create_user_name : '';
                        $password = !empty($_POST['create_act']) ? $_POST['create_act'] : '';
                        $email = $create_user_email;
                        try {

                            //try to create user
                            $new_customer = wc_create_new_customer(sanitize_email($email), wc_clean($username), $password);
                            if (is_wp_error($new_customer)) {
                                wc_add_notice($new_customer->get_error_message(), 'error');
                                $create_acc_error = true;
                            }
                            if (apply_filters('paypal-for-woocommerce_registration_auth_new_customer', true, $new_customer)) {
                                wc_set_customer_auth_cookie($new_customer);
                            }

                            //Log user in
                            $creds = array(
                                'user_login' => wc_clean($username),
                                'user_password' => $password,
                                'remember' => true,
                            );
                            $user = wp_signon($creds, false);
                            if (is_wp_error($user)) {
                                wc_add_notice($user->get_error_message(), 'error');
                                $create_acc_error = true;
                            } else {
                                wp_set_current_user($user->ID); //Here is where we update the global user variables
                                $secure_cookie = is_ssl() ? true : false;
                                wp_set_auth_cookie($user->ID, true, $secure_cookie);
                                $this->customer_id = $user->ID;
                            }
                        } catch (Exception $e) {
                            wc_add_notice('<strong>' . __('Error', 'paypal-for-woocommerce') . ':</strong> ' . $e->getMessage(), 'error');
                            $create_acc_error = true;
                        }
                    }
                    if ($create_acc_error) {
                        wp_redirect(add_query_arg(array( 'pp_action' => 'revieworder')));
                        exit();
                    }
                }

                // Update customer shipping and payment method to posted method
                $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');

                if (isset($_POST['shipping_method']) && is_array($_POST['shipping_method']))
                    foreach ($_POST['shipping_method'] as $i => $value)
                        $chosen_shipping_methods[$i] = wc_clean($value);

                WC()->session->set('chosen_shipping_methods', $chosen_shipping_methods);

                if (WC()->cart->needs_shipping()) {
                    // Validate Shipping Methods
                    $packages = WC()->shipping->get_packages();
                    WC()->checkout()->shipping_methods = WC()->session->get('chosen_shipping_methods');
                }


                $this->add_log('Start Pay Action');
                if (!defined('WOOCOMMERCE_CHECKOUT'))
                    define('WOOCOMMERCE_CHECKOUT', true);
                WC()->cart->calculate_totals();
                $this->angelleye_check_cart_items();
                if (sizeof(WC()->cart->get_cart()) == 0 || empty(WC()->session->TOKEN)) {
                    $ms = sprintf(__('Sorry, your session has expired. <a href=%s>Return to homepage &rarr;</a>', 'paypal-for-woocommerce'), '"' . home_url() . '"');
                    $ec_confirm_message = apply_filters('angelleye_ec_confirm_message', $ms);
                    wc_add_notice($ec_confirm_message, "error");
                    wp_redirect(get_permalink(wc_get_page_id('cart')));
                    exit();
                } 
                
                $paid_order_id = $this->angelleye_prevent_duplicate_payment_request(WC()->session->TOKEN);
                
                if( $paid_order_id ) {
                    $ms = sprintf(__('Sorry, A successful transaction has already been completed for this token. <a href=%s>Return to homepage &rarr;</a>', 'paypal-for-woocommerce'), '"' . home_url() . '"');
                    $ec_confirm_message = apply_filters('angelleye_ec_confirm_message', $ms);
                    wc_add_notice($ec_confirm_message, "error");
                    wp_redirect(get_permalink(wc_get_page_id('cart')));
                    exit();
                } 
                
                if( isset(WC()->session->checkout_form) && !empty(WC()->session->checkout_form)) {
                    WC()->checkout()->posted = maybe_unserialize(WC()->session->checkout_form);
                }
                //Set POST data from SESSION
                $checkout_form_post_data = maybe_unserialize($this->get_session('post_data'));
                if( isset($checkout_form_post_data) && !empty($checkout_form_post_data) ) {
                    $_POST = $checkout_form_post_data;
                }
                $order_id = WC()->checkout()->create_order();

                do_action( 'woocommerce_checkout_order_processed', $order_id, $checkout_form_post_data);

                /**
                 * Update meta data with session data
                 */
                // Parse SHIPTONAME to fist and last name
                 require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/NameParser.php' );
                $parser = new FullNameParser();
               $shiptoname_from_session = $this->get_session('shiptoname');
                if( isset($shiptoname_from_session) && !empty($shiptoname_from_session) ) {
                    $split_name = $parser->split_full_name($this->get_session('shiptoname'));
                    $shipping_first_name = $split_name['fname'];
                    $shipping_last_name = $split_name['lname'];
                    $full_name = $split_name['fullname'];
                } else {
                    if( !isset($shipping_first_name) ) {
                        $shipping_first_name = '';
                    } 
                    if( !isset($shipping_last_name) ) {
                        $shipping_last_name = '';
                    }
                }
                
               	$this->set_session('firstname', isset($result['FIRSTNAME']) ? $result['FIRSTNAME'] : $shipping_first_name);
                $this->set_session('lastname', isset($result['LASTNAME']) ?  $result['LASTNAME'] : $shipping_last_name);

                update_post_meta($order_id, '_payment_method', $this->id);
                update_post_meta($order_id, '_payment_method_title', $this->title);
               
                $checkout_form_data = maybe_unserialize($this->get_session('checkout_form'));
                $user_email_address = '';
                if (is_user_logged_in()) {
                    $userLogined = wp_get_current_user();
                    $user_email_address = $userLogined->user_email;
                    update_post_meta($order_id, '_billing_email', $userLogined->user_email);
                    update_post_meta($order_id, '_customer_user', $userLogined->ID);
                } else {
                    if (isset($checkout_form_data['billing_email'])) {
                        $user_email_address = $checkout_form_data['billing_email'];
                        update_post_meta($order_id, '_billing_email', $checkout_form_data['billing_email']);
                    }
                    else{
                        $user_email_address = $this->get_session('payeremail');
                        update_post_meta($order_id, '_billing_email', $this->get_session('payeremail'));
                    }
                }

                //save PayPal email
                update_post_meta($order_id, 'paypal_email', $this->get_session('payeremail'));
                do_action( 'angelleye_wc_eu_vat_number', $order_id, $call_third_party = true );
                do_action( 'woocommerce_checkout_update_order_meta', $order_id, $checkout_form_data );
                if ((isset($this->billing_address) && $this->billing_address =='yes' ) || (empty($checkout_form_data['billing_country']))) {
                    $checkout_form_data = array();
                }
                if (isset($checkout_form_data) && !empty($checkout_form_data)) {
                    foreach ($checkout_form_data as $key => $value) {
                        if (strpos($key, 'billing_') !== false && !empty($value) && !is_array($value)) {
                        	if( isset($checkout_form_data['ship_to_different_address']) && $checkout_form_data['ship_to_different_address'] == false) {
                        		$shipping_key = str_replace('billing_', 'shipping_', $key);
                        		update_user_meta($this->customer_id, $shipping_key, $value);
                        		update_post_meta($order_id, '_'.$shipping_key, $value);
                        	}
                            update_user_meta($this->customer_id, $key, $value);
                            update_post_meta($order_id, '_'.$key, $value);
                        } elseif (WC()->cart->needs_shipping() && strpos($key, 'shipping_') !== false && !empty($value) && !is_array($value)) {
                            update_user_meta($this->customer_id, $key, $value);
                            update_post_meta($order_id, '_'.$key, $value);
                        }
                    }
                } else {
                	update_post_meta($order_id, '_shipping_first_name', isset($shipping_first_name) ? $shipping_first_name : '');
	                update_post_meta($order_id, '_shipping_last_name', isset($shipping_last_name) ? $shipping_last_name : '');
	                update_post_meta($order_id, '_shipping_full_name', isset($full_name) ? $full_name : '');
	                update_post_meta($order_id, '_shipping_company', $this->get_session('company'));
	                update_post_meta($order_id, '_billing_phone', $this->get_session('phonenum'));
	                update_post_meta($order_id, '_shipping_address_1', $this->get_session('shiptostreet'));
	                update_post_meta($order_id, '_shipping_address_2', $this->get_session('shiptostreet2'));
	                update_post_meta($order_id, '_shipping_city', $this->get_session('shiptocity'));
	                update_post_meta($order_id, '_shipping_postcode', $this->get_session('shiptozip'));
	                update_post_meta($order_id, '_shipping_country', $this->get_session('shiptocountrycode'));
	                update_post_meta($order_id, '_shipping_state', $this->get_state_code($this->get_session('shiptocountrycode'), $this->get_session('shiptostate')));
	                update_post_meta($order_id, '_customer_user', get_current_user_id());
	                if ($this->billing_address == 'yes') {
	                	update_post_meta( $order_id, '_billing_first_name',  $this->get_session('firstname') );
	                    update_post_meta( $order_id, '_billing_last_name',  $this->get_session('lastname') );
	                    update_post_meta( $order_id, '_billing_full_name',  isset($full_name) ?  $full_name : '' );
	                    update_post_meta( $order_id, '_billing_company',   $this->get_session('company') );
	                    update_post_meta( $order_id, '_billing_address_1',  $this->get_session('shiptostreet'));
	                    update_post_meta( $order_id, '_billing_address_2',  $this->get_session('shiptostreet2'));
	                    update_post_meta( $order_id, '_billing_city',    $this->get_session('shiptocity'));
	                    update_post_meta( $order_id, '_billing_postcode',   $this->get_session('shiptozip'));
	                    update_post_meta( $order_id, '_billing_country',   $this->get_session('shiptocountrycode'));
	                    update_post_meta( $order_id, '_billing_state',   $this->get_state_code( $this->get_session('shiptocountrycode'), $this->get_session('shiptostate')));
	                }
                }

                $this->add_log('...Order ID: ' . $order_id);
                $order = new WC_Order($order_id);
                do_action('woocommerce_ppe_do_payaction', $order);
                $this->add_log('...Order Total: ' . $order->order_total);
                $this->add_log('...Cart Total: ' . WC()->cart->get_total());
                $this->add_log("...Token:" . $this->get_session('TOKEN'));
                $result = $this->ConfirmPayment($order->order_total);

                // Set Customer Name
                if (!get_current_user_id()) {
                    update_post_meta($order_id, '_billing_first_name', $this->get_session('firstname'));
                    update_post_meta($order_id, '_billing_last_name', $this->get_session('lastname'));
                }

                /**
                 * Customer Notes
                 */
                if ($this->get_session('customer_notes') != '') {
                   $customer_notes = $this->get_session('customer_notes');
                } else {
                   if (isset($checkout_form_data['order_comments'])) $customer_notes = $checkout_form_data['order_comments'];
                }
                if (!empty($customer_notes)) {
                    // Update post 37
                    $checkout_note = array(
                        'ID' => $order_id,
                        'post_excerpt' => $customer_notes,
                    );
                    wp_update_post($checkout_note);
                    $checkout_form_data['order_comments'] ='';
                    unset($checkout_form_data['order_comments']);
                }


                 if ($result['ACK'] == 'Success' || $result['ACK'] == 'SuccessWithWarning') {
                    /**
                     * Check for Seller Protection Settings
                     */
                    if(AngellEYE_Gateway_Paypal::angelleye_woocommerce_sellerprotection_should_cancel_order($this,$result)) {
                        $this->add_log('Order '.$order_id.' ('.$result['PAYMENTINFO_0_TRANSACTIONID'].') did not meet our Seller Protection requirements. Cancelling and refunding order.');
                        $order->add_order_note(__('Transaction did not meet our Seller Protection requirements. Cancelling and refunding order.', 'paypal-for-woocommerce'));
                        $admin_email = get_option("admin_email");
                        if($this->email_notify_order_cancellations == true) {
                            if( isset($user_email_address) && !empty($user_email_address)) {
                                wp_mail($user_email_address, __('PayPal Express Checkout payment declined due to our Seller Protection Settings', 'paypal-for-woocommerce'), __('Order #', 'paypal-for-woocommerce').$order_id);
                            }
                        }
                        wp_mail($admin_email, __('PayPal Express Checkout payment declined due to our Seller Protection Settings', 'paypal-for-woocommerce'), __('Order #', 'paypal-for-woocommerce').$order_id);
                        // Payment was succesfull, add transaction ID to our order so we can refund it
                        
                        update_post_meta($order_id, '_transaction_id', $result['PAYMENTINFO_0_TRANSACTIONID']);
                        // Also add the express token
                        update_post_meta($order_id, '_express_checkout_token', $this->get_session('TOKEN'));
                        // Process the refund
                        $this->process_refund($order_id,$order->order_total,__('There was a problem processing your order. Please contact customer support.', 'paypal-for-woocommerce'));
                        $order->cancel_order();
                        wc_add_notice(__('Thank you for your recent order. Unfortunately it has been cancelled and refunded. Please contact our customer support team.', 'paypal-for-woocommerce'), 'error');
                        wp_redirect(get_permalink(wc_get_page_id('cart')));
                        exit();
                    }
                    
                    $SuccessWithWarning_order_note = '';
                    
                    if($result['ACK'] == 'SuccessWithWarning') {
                        
                        $ErrorCode = urldecode($result["L_ERRORCODE0"]);
                        $ErrorShortMsg = urldecode($result["L_SHORTMESSAGE0"]);
                        $ErrorLongMsg = urldecode($result["L_LONGMESSAGE0"]);
                        $ErrorSeverityCode = urldecode($result["L_SEVERITYCODE0"]);
                        
                        $this->add_log('DoExpressCheckoutPayment API call SuccessWithWarning. ');
                        $this->add_log('Detailed Error Message: ' . $ErrorLongMsg);
                        $this->add_log('Short Error Message: ' . $ErrorShortMsg);
                        $this->add_log('Error Code: ' . $ErrorCode);
                        $this->add_log('Error Severity Code: ' . $ErrorSeverityCode);
                        
                        if($this->error_email_notify) {
                            $admin_email = get_option("admin_email");
                            $message = '';
                            $message .= __( "DoExpressCheckoutPayment API call SuccessWithWarning." , "paypal-for-woocommerce" )."\n\n";
                            $message .= __( 'Error Code: ' ,'paypal-for-woocommerce' ) . $ErrorCode."\n";
                            $message .= __( 'Error Severity Code: ' , 'paypal-for-woocommerce' ) . $ErrorSeverityCode."\n";
                            $message .= __( 'Short Error Message: ' , 'paypal-for-woocommerce' ) . $ErrorShortMsg ."\n";
                            $message .= __( 'Detailed Error Message: ' , 'paypal-for-woocommerce') . $ErrorLongMsg ."\n";
                            $message .= __( 'User IP: ', 'paypal-for-woocommerce') . $this->get_user_ip() . "\n";
                            $message .= __( 'Order ID: ' ).$order_id ."\n";
                            $message .= __( 'Customer Name: ' ).$this->get_session('shiptoname')."\n";
                            $message .= __( 'Customer Email: ' ).$this->get_session('payeremail')."\n";

                            $error_email_notify_mes = apply_filters( 'ae_ppec_error_email_message', $message, $ErrorCode, $ErrorSeverityCode, $ErrorShortMsg, $ErrorLongMsg );
                            $subject = "PayPal Express Checkout Error Notification";
                            $error_email_notify_subject = apply_filters( 'ae_ppec_error_email_subject', $subject );

                            wp_mail($admin_email, $error_email_notify_subject, $error_email_notify_mes);
                        }
                        
                        $SuccessWithWarning_order_note = ' Error Code: ' . $ErrorCode . ", " . ' Error Severity Code: ' . $ErrorSeverityCode . ", " . ' Short Error Message: ' . $ErrorShortMsg . ", " . ' Detailed Error Message: ' . $ErrorLongMsg . ", ";
                        
                    } else {
                        $this->add_log('Payment confirmed with PayPal successfully');
                    }
                    $result = apply_filters('woocommerce_payment_successful_result', $result, $order_id);

                    /**
                     * Gift Wrap Notes
                     */
                    if ($this->get_session('giftwrapamount') != '') {
                        update_post_meta($order_id, 'giftwrapamount', $this->get_session('giftwrapamount'));
                        if ($this->get_session('giftmessage') != '' ) {
                            update_post_meta($order_id, 'giftmessage', $this->get_session('giftmessage'));
                        }
                        if ($this->get_session('giftwrapname') != '' ) {
                            update_post_meta($order_id, 'giftwrapname', $this->get_session('giftwrapname'));
                        }
                        if ($this->get_session('giftmessage') != '' ) {
                            update_post_meta($order_id, 'giftmessage', $this->get_session('giftmessage'));
                        }
                        $giftreceiptenable = strtolower($this->get_session('giftreceiptenable')) == 'true' ? 'true' : 'false';
                        update_post_meta($order_id, 'giftreceiptenable', $giftreceiptenable);
                    }

                    update_post_meta($order_id, '_express_checkout_token', $this->get_session('TOKEN'));
                    update_post_meta( $order_id, '_first_transaction_id', $result['PAYMENTINFO_0_TRANSACTIONID'] );
                    do_action('before_save_payment_token', $order_id);
                    if( isset($result['BILLINGAGREEMENTID']) && !empty($result['BILLINGAGREEMENTID']) ) {
                        update_post_meta( $order_id, 'billing_agreement_id', $result['BILLINGAGREEMENTID'] );
                        if(!empty($_POST['wc-paypal_express-new-payment-method']) && $_POST['wc-paypal_express-new-payment-method'] == true) {
                            $customer_id =  $order->get_user_id();
                            $billing_agreement_id = $result['BILLINGAGREEMENTID'];
                            $token = new WC_Payment_Token_CC();
                            $token->set_user_id( $customer_id );
                            $token->set_token( $billing_agreement_id );
                            $token->set_gateway_id( $this->id );
                            $token->set_card_type( 'PayPal Billing Agreement' );
                            $token->set_last4( substr( $billing_agreement_id, -4 ) );
                            $token->set_expiry_month( date( 'm' ) );
                            $token->set_expiry_year( date( 'Y', strtotime( '+20 years' ) ) );
                            $save_result = $token->save();
                            if ( $save_result ) {
                                    $order->add_payment_token( $token );
                            }
                        }
                        add_post_meta( $order->id, 'BILLINGAGREEMENTID', $result['BILLINGAGREEMENTID'] );
                    }
                    unset(WC()->session->TOKEN);
                    $order->add_order_note(__('PayPal Express payment completed', 'paypal-for-woocommerce') .
                            ' ( Response Code: ' . $result['ACK'] . ", " . $SuccessWithWarning_order_note .
                            ' TransactionID: ' . $result['PAYMENTINFO_0_TRANSACTIONID'] . ' )');
                    $REVIEW_RESULT = unserialize($this->get_session('RESULT'));

                    if( isset($REVIEW_RESULT['PAYERSTATUS']) && !empty($REVIEW_RESULT['PAYERSTATUS']) ) {
                        $payerstatus_note = __('Payer Status: ', 'paypal-for-woocommerce');
                        $payerstatus_note .= ucfirst($REVIEW_RESULT['PAYERSTATUS']);
                        $order->add_order_note($payerstatus_note);
                    }
                    if( isset($REVIEW_RESULT['ADDRESSSTATUS']) && !empty($REVIEW_RESULT['ADDRESSSTATUS']) ) {
                        $addressstatus_note = __('Address Status: ', 'paypal-for-woocommerce');
                        $addressstatus_note .= ucfirst($REVIEW_RESULT['ADDRESSSTATUS']);
                        $order->add_order_note($addressstatus_note);
                    }
                    $is_sandbox = $this->testmode == 'yes' ? true : false;
                    update_post_meta($order->id, 'is_sandbox', $is_sandbox);
                    if($this->payment_action != 'Sale') {
                        AngellEYE_Utility::angelleye_paypal_for_woocommerce_add_paypal_transaction($result, $order, $this->payment_action);
                        $payment_order_meta = array('_transaction_id' => $result['PAYMENTINFO_0_TRANSACTIONID'], '_payment_action' => $this->payment_action);
                        AngellEYE_Utility::angelleye_add_order_meta($order_id, $payment_order_meta);
                    }

                    switch( strtolower( $result['PAYMENTINFO_0_PAYMENTSTATUS'] ) ) :
			case 'completed' :

                                if ( $order->status == 'completed' ) {
					break;
				}

				if ( ! in_array( strtolower( $result['PAYMENTINFO_0_TRANSACTIONTYPE'] ), array( 'cart', 'instant', 'expresscheckout', 'web_accept', 'masspay', 'send_money' ) ) ) {
					break;
				}
                                $order->add_order_note( __( 'Payment Completed via Express Checkout', 'paypal-for-woocommerce' ) );
                                $order->payment_complete($result['PAYMENTINFO_0_TRANSACTIONID']);
				break;
			case 'pending' :

                            if ( ! in_array( strtolower( $result['PAYMENTINFO_0_TRANSACTIONTYPE'] ), array( 'cart', 'instant', 'expresscheckout', 'web_accept', 'masspay', 'send_money' ) ) ) {
					break;
				}
                                $order_status = 'on-hold';
				switch( strtolower( $result['PAYMENTINFO_0_PENDINGREASON'] ) ) {
					case 'address':
						$pending_reason = __( 'Address: The payment is pending because your customer did not include a confirmed shipping address and your Payment Receiving Preferences is set such that you want to manually accept or deny each of these payments. To change your preference, go to the Preferences section of your Profile.', 'paypal-for-woocommerce' );
						break;
					case 'authorization':
                                                $order_status = 'complete';
						$pending_reason = __( 'Authorization: The payment is pending because it has been authorized but not settled. You must capture the funds first.', 'paypal-for-woocommerce' );
						break;
					case 'echeck':
						$pending_reason = __( 'eCheck: The payment is pending because it was made by an eCheck that has not yet cleared.', 'paypal-for-woocommerce' );
						break;
					case 'intl':
						$pending_reason = __( 'intl: The payment is pending because you hold a non-U.S. account and do not have a withdrawal mechanism. You must manually accept or deny this payment from your Account Overview.', 'paypal-for-woocommerce' );
						break;
					case 'multicurrency':
					case 'multi-currency':
						$pending_reason = __( 'Multi-currency: You do not have a balance in the currency sent, and you do not have your Payment Receiving Preferences set to automatically convert and accept this payment. You must manually accept or deny this payment.', 'paypal-for-woocommerce' );
						break;
					case 'order':
						$pending_reason = __( 'Order: The payment is pending because it is part of an order that has been authorized but not settled.', 'paypal-for-woocommerce' );
						break;
					case 'paymentreview':
						$pending_reason = __( 'Payment Review: The payment is pending while it is being reviewed by PayPal for risk.', 'paypal-for-woocommerce' );
						break;
					case 'unilateral':
						$pending_reason = __( 'Unilateral: The payment is pending because it was made to an email address that is not yet registered or confirmed.', 'paypal-for-woocommerce' );
						break;
					case 'verify':
						$pending_reason = __( 'Verify: The payment is pending because you are not yet verified. You must verify your account before you can accept this payment.', 'paypal-for-woocommerce' );
						break;
					case 'other':
						$pending_reason = __( 'Other: For more information, contact PayPal customer service.', 'paypal-for-woocommerce' );
						break;
					case 'none':
					default:
						$pending_reason = __( 'No pending reason provided.', 'paypal-for-woocommerce' );
						break;
				}
				$order->add_order_note( sprintf( __( 'Payment via Express Checkout Pending. PayPal reason: %s.', 'paypal-for-woocommerce' ), $pending_reason ) );
                                if( $order_status == 'on-hold' ) {
                                    $order->update_status( 'on-hold' );
                                } else {
                                    $order->payment_complete($result['PAYMENTINFO_0_TRANSACTIONID']);
                                }
				break;
			case 'denied' :
			case 'expired' :
			case 'failed' :
			case 'voided' :
				// Order failed
				$order->update_status( 'failed', sprintf( __( 'Payment %s via Express Checkout.', 'paypal-for-woocommerce' ), strtolower( $result['PAYMENTINFO_0_PAYMENTSTATUS'] ) ) );
				break;
			default:
				break;

                    endswitch;

                    unset(WC()->session->checkout_form);
                    unset(WC()->session->checkout_form_post_data);

                    // Empty the Cart
                    WC()->cart->empty_cart();
                    $this->pfw_remove_checkout_session_data();
                    wp_redirect($this->get_return_url($order));
                    exit();
                } else {
                    $this->add_log('...Error confirming order ' . $order_id . ' with PayPal');
                    // Display a user friendly Error on the page and log details
                    $ErrorCode = urldecode($result["L_ERRORCODE0"]);
                    $ErrorShortMsg = urldecode($result["L_SHORTMESSAGE0"]);
                    $ErrorLongMsg = urldecode($result["L_LONGMESSAGE0"]);
                    $ErrorSeverityCode = urldecode($result["L_SEVERITYCODE0"]);
                    $this->add_log('DoExpressCheckoutPayment API call failed. ');
                    $this->add_log('Detailed Error Message: ' . $ErrorLongMsg);
                    $this->add_log('Short Error Message: ' . $ErrorShortMsg);
                    $this->add_log('Error Code: ' . $ErrorCode);
                    $this->add_log('Error Severity Code: ' . $ErrorSeverityCode);
                    if ($ErrorCode == '10486') {
                        $this->RedirectToPayPal($this->get_session('TOKEN'));
                    }

                    // Notice admin if has any issue from PayPal
                    $message = '';

					if($this->error_email_notify)
					{
						$admin_email = get_option("admin_email");
						$message .= __( "DoExpressCheckoutPayment API call failed." , "paypal-for-woocommerce" )."\n\n";
						$message .= __( 'Error Code: ' ,'paypal-for-woocommerce' ) . $ErrorCode."\n";
						$message .= __( 'Error Severity Code: ' , 'paypal-for-woocommerce' ) . $ErrorSeverityCode."\n";
						$message .= __( 'Short Error Message: ' , 'paypal-for-woocommerce' ) . $ErrorShortMsg ."\n";
						$message .= __( 'Detailed Error Message: ' , 'paypal-for-woocommerce') . $ErrorLongMsg ."\n";
                                                $message .= __( 'User IP: ', 'paypal-for-woocommerce') . $this->get_user_ip() . "\n";
                        $message .= __( 'Order ID: ' ).$order_id ."\n";
                        $message .= __( 'Customer Name: ' ).$this->get_session('shiptoname')."\n";
                        $message .= __( 'Customer Email: ' ).$this->get_session('payeremail')."\n";

                        $error_email_notify_mes = apply_filters( 'ae_ppec_error_email_message', $message, $ErrorCode, $ErrorSeverityCode, $ErrorShortMsg, $ErrorLongMsg );
                        $subject = "PayPal Express Checkout Error Notification";
                        $error_email_notify_subject = apply_filters( 'ae_ppec_error_email_subject', $subject );

                        wp_mail($admin_email, $error_email_notify_subject, $error_email_notify_mes);
                    }

                    // Generate error message based on Error Display Type setting
                    if ($this->error_display_type == 'detailed') {
                        $sec_error_notice = $ErrorCode . ' - ' . $ErrorLongMsg;
                        $error_display_type_message = sprintf(__($sec_error_notice, 'paypal-for-woocommerce'));
                    } else {
                        $error_display_type_message = sprintf(__('There was a problem paying with PayPal.  Please try another method.', 'paypal-for-woocommerce'));
                    }
                    $error_display_type_message = apply_filters( 'ae_ppec_error_user_display_message', $error_display_type_message, $ErrorCode, $ErrorLongMsg );
                    wc_add_notice($error_display_type_message, 'error');

                    wp_redirect(get_permalink(wc_get_page_id('cart')));
                    exit();
                }
            }
        }
    }

    /**
     * CallSetExpressCheckout
     *
     * Makes a request to PayPal's SetExpressCheckout API
     * to setup the checkout and obtain a token.
     *
     * @paymentAmount (double) Total payment amount of the order.
     * @returnURL (string) URL for PayPal to send the buyer to after review and continue from PayPal.
     * @cancelURL (string) URL for PayPal to send the buyer to if they cancel the payment.
     */
    function CallSetExpressCheckout($paymentAmount, $returnURL, $cancelURL, $usePayPalCredit = false, $posted) {
        /*
         * Display message to user if session has expired.
         */
        $this->angelleye_check_cart_items();
        if (sizeof(WC()->cart->get_cart()) == 0) {
            $ms = sprintf(__('Sorry, your session has expired. <a href=%s>Return to homepage &rarr;</a>', 'paypal-for-woocommerce'), '"' . home_url() . '"');
            $set_ec_message = apply_filters('angelleye_set_ec_message', $ms);
            wc_add_notice($set_ec_message, "error");
        }

        /*
         * Check if the PayPal class has already been established.
         */
        if (!class_exists('Angelleye_PayPal')) {
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.class.php' );
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

        /**
         * Prepare PayPal request data.
         */
        /**
         * If Gift Wrap options are enabled, then MAXAMT is required
         * in SetExpressCheckout.
         *
         * https://github.com/angelleye/paypal-woocommerce/issues/142
         */
        if ($this->gift_wrap_enabled == 'yes') {
            $maxAmount = $paymentAmount * 2;
            $maxAmount = $maxAmount + $this->gift_wrap_amount;
            $maxAmount = AngellEYE_Gateway_Paypal::number_format($maxAmount);
        } else {
            $maxAmount = '';
        }


        //prefill email
        if (isset($posted['billing_email'])) {
            $customer_email = $posted['billing_email'];
        } elseif(is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $customer_email = $current_user->user_email;
        } else {
            $customer_email = '';
        }


        $SECFields = array(
            'token' => '', // A timestamped token, the value of which was returned by a previous SetExpressCheckout call.
            'maxamt' => $maxAmount, // The expected maximum total amount the order will be, including S&H and sales tax.
            'returnurl' => urldecode($returnURL), // Required.  URL to which the customer will be returned after returning from PayPal.  2048 char max.
            'cancelurl' => urldecode($cancelURL), // Required.  URL to which the customer will be returned if they cancel payment on PayPal's site.
            'callback' => '', // URL to which the callback request from PayPal is sent.  Must start with https:// for production.
            'callbacktimeout' => '', // An override for you to request more or less time to be able to process the callback request and response.  Acceptable range for override is 1-6 seconds.  If you specify greater than 6 PayPal will use default value of 3 seconds.
            'callbackversion' => '', // The version of the Instant Update API you're using.  The default is the current version.
            'reqconfirmshipping' => '', // The value 1 indicates that you require that the customer's shipping address is Confirmed with PayPal.  This overrides anything in the account profile.  Possible values are 1 or 0.
            'noshipping' => '', // The value 1 indiciates that on the PayPal pages, no shipping address fields should be displayed.  Maybe 1 or 0.
            'allownote' => 1, // The value 1 indiciates that the customer may enter a note to the merchant on the PayPal page during checkout.  The note is returned in the GetExpresscheckoutDetails response and the DoExpressCheckoutPayment response.  Must be 1 or 0.
            'addroverride' => '', // The value 1 indiciates that the PayPal pages should display the shipping address set by you in the SetExpressCheckout request, not the shipping address on file with PayPal.  This does not allow the customer to edit the address here.  Must be 1 or 0.
            'localecode' => ($this->use_wp_locale_code == 'yes' && get_locale() != '') ? get_locale() : '', // Locale of pages displayed by PayPal during checkout.  Should be a 2 character country code.  You can retrive the country code by passing the country name into the class' GetCountryCode() function.
            'pagestyle' => $this->page_style, // Sets the Custom Payment Page Style for payment pages associated with this button/link.
            'hdrimg' => $this->checkout_logo_hdrimg, // URL for the image displayed as the header during checkout.  Max size of 750x90.  Should be stored on an https:// server or you'll get a warning message in the browser.
            'logoimg' => $this->checkout_logo,
            'hdrbordercolor' => '', // Sets the border color around the header of the payment page.  The border is a 2-pixel permiter around the header space.  Default is black.
            'hdrbackcolor' => '', // Sets the background color for the header of the payment page.  Default is white.
            'payflowcolor' => '', // Sets the background color for the payment page.  Default is white.
            'skipdetails' => $this->skip_final_review == 'yes' ? '1' : '0', // This is a custom field not included in the PayPal documentation.  It's used to specify whether you want to skip the GetExpressCheckoutDetails part of checkout or not.  See PayPal docs for more info.
            'email' => $customer_email, // Email address of the buyer as entered during checkout.  PayPal uses this value to pre-fill the PayPal sign-in page.  127 char max.
            'channeltype' => '', // Type of channel.  Must be Merchant (non-auction seller) or eBayItem (eBay auction)
            'giropaysuccessurl' => '', // The URL on the merchant site to redirect to after a successful giropay payment.  Only use this field if you are using giropay or bank transfer payment methods in Germany.
            'giropaycancelurl' => '', // The URL on the merchant site to redirect to after a canceled giropay payment.  Only use this field if you are using giropay or bank transfer methods in Germany.
            'banktxnpendingurl' => '', // The URL on the merchant site to transfer to after a bank transfter payment.  Use this field only if you are using giropay or bank transfer methods in Germany.
            'brandname' => $this->brand_name, // A label that overrides the business name in the PayPal account on the PayPal hosted checkout pages.  127 char max.
            'customerservicenumber' => $this->customer_service_number, // Merchant Customer Service number displayed on the PayPal Review page. 16 char max.
            'buyeremailoptionenable' => '', // Enable buyer email opt-in on the PayPal Review page. Allowable values are 0 and 1
            'surveyquestion' => '', // Text for the survey question on the PayPal Review page. If the survey question is present, at least 2 survey answer options need to be present.  50 char max.
            'surveyenable' => '', // Enable survey functionality. Allowable values are 0 and 1
            'totaltype' => '', // Enables display of "estimated total" instead of "total" in the cart review area.  Values are:  Total, EstimatedTotal
            'notetobuyer' => '', // Displays a note to buyers in the cart review area below the total amount.  Use the note to tell buyers about items in the cart, such as your return policy or that the total excludes shipping and handling.  127 char max.
            'buyerid' => '', // The unique identifier provided by eBay for this buyer. The value may or may not be the same as the username. In the case of eBay, it is different. 255 char max.
            'buyerusername' => '', // The user name of the user at the marketplaces site.
            'buyerregistrationdate' => '', // Date when the user registered with the marketplace.
            'allowpushfunding' => '', // Whether the merchant can accept push funding.  0 = Merchant can accept push funding : 1 = Merchant cannot accept push funding.
            'taxidtype' => '', // The buyer's tax ID type.  This field is required for Brazil and used for Brazil only.  Values:  BR_CPF for individuals and BR_CNPJ for businesses.
            'taxid' => ''        // The buyer's tax ID.  This field is required for Brazil and used for Brazil only.  The tax ID is 11 single-byte characters for individutals and 14 single-byte characters for businesses.
        );




        /**
         * If Gift Wrap options are enabled, add them to SEC
         */
        if (strtolower($this->gift_wrap_enabled) == 'yes') {
            $SECFields['giftwrapenable'] = '1';      // Enable gift wrap widget on the PayPal Review page.  Allowable values are 0 and 1.
            $SECFields['giftmessageenable'] = $this->gift_message_enabled ? '1' : '';      // Enable gift message widget on the PayPal Review page. Allowable values are 0 and 1
            $SECFields['giftreceiptenable'] = $this->gift_receipt_enabled ? '1' : '';      // Enable gift receipt widget on the PayPal Review page. Allowable values are 0 and 1
            $SECFields['giftwrapname'] = $this->gift_wrap_name;       // Label for the gift wrap option such as "Box with ribbon".  25 char max.
            $SECFields['giftwrapamount'] = $this->gift_wrap_amount;   // Amount charged for gift-wrap service.
        }

        /**
         * If PayPal Credit is being used, override the necessary parameters
         */
        if ($usePayPalCredit) {
            $SECFields['solutiontype'] = 'Sole';
            $SECFields['landingpage'] = 'Billing';
            $SECFields['userselectedfundingsource'] = 'BML';
        } elseif (strtolower($this->paypal_account_optional) == 'yes' && strtolower($this->landing_page) == 'billing') {
            $SECFields['solutiontype'] = 'Sole';
            $SECFields['landingpage'] = 'Billing';
            $SECFields['userselectedfundingsource'] = 'CreditCard';
        } elseif (strtolower($this->paypal_account_optional) == 'yes' && strtolower($this->landing_page) == 'login') {
            $SECFields['solutiontype'] = 'Sole';
            $SECFields['landingpage'] = 'Login';
        }

        // Basic array of survey choices.  Nothing but the values should go in here.
        $SurveyChoices = array('Choice 1', 'Choice2', 'Choice3', 'etc');

        $Payments = array();
        $Payment = array(
            'amt' => AngellEYE_Gateway_Paypal::number_format(WC()->cart->total), // Required.  The total cost of the transaction to the customer.  If shipping cost and tax charges are known, include them in this value.  If not, this value should be the current sub-total of the order.
            'currencycode' => get_woocommerce_currency(), // A three-character currency code.  Default is USD.
            'shippingamt' => '', // Total shipping costs for this order.  If you specify SHIPPINGAMT you mut also specify a value for ITEMAMT.
            'shippingdiscamt' => '', // Shipping discount for this order, specified as a negative number.
            'insuranceamt' => '', // Total shipping insurance costs for this order.
            'insuranceoptionoffered' => '', // If true, the insurance drop-down on the PayPal review page displays the string 'Yes' and the insurance amount.  If true, the total shipping insurance for this order must be a positive number.
            'handlingamt' => '', // Total handling costs for this order.  If you specify HANDLINGAMT you mut also specify a value for ITEMAMT.
            'taxamt' => '', // Required if you specify itemized L_TAXAMT fields.  Sum of all tax items in this order.
            'desc' => '', // Description of items on the order.  127 char max.
            'custom' => apply_filters( 'ae_ppec_custom_parameter', '', $posted ), // Free-form field for your own use.  256 char max.
            'invnum' => '', // Your own invoice or tracking number.  127 char max.
            'notifyurl' => '', // URL for receiving Instant Payment Notifications
            'shiptoname' => '', // Required if shipping is included.  Person's name associated with this address.  32 char max.
            'shiptostreet' => '', // Required if shipping is included.  First street address.  100 char max.
            'shiptostreet2' => '', // Second street address.  100 char max.
            'shiptocity' => '', // Required if shipping is included.  Name of city.  40 char max.
            'shiptostate' => '', // Required if shipping is included.  Name of state or province.  40 char max.
            'shiptozip' => '', // Required if shipping is included.  Postal code of shipping address.  20 char max.
            'shiptocountrycode' => '', // Required if shipping is included.  Country code of shipping address.  2 char max.
            'shiptophonenum' => '', // Phone number for shipping address.  20 char max.
            'notetext' => isset($posted['order_comments']) ? $posted['order_comments'] : '', // Note to the merchant.  255 char max.
            'allowedpaymentmethod' => '', // The payment method type.  Specify the value InstantPaymentOnly.
            'paymentaction' => !empty($this->payment_action) ? $this->payment_action : 'Sale', // How you want to obtain the payment.  When implementing parallel payments, this field is required and must be set to Order.
            'paymentrequestid' => '', // A unique identifier of the specific payment request, which is required for parallel payments.
            'sellerpaypalaccountid' => ''   // A unique identifier for the merchant.  For parallel payments, this field is required and must contain the Payer ID or the email address of the merchant.
        );

        /**
         * If checkout like regular payment
         */
        if (!empty($posted) && WC()->cart->needs_shipping()) {
            $SECFields['addroverride'] = 1;
            if (@$posted['ship_to_different_address']) {
                $Payment['shiptoname'] = $posted['shipping_first_name'] . ' ' . $posted['shipping_last_name'];
                $Payment['shiptostreet'] = $posted['shipping_address_1'];
                $Payment['shiptostreet2'] = @$posted['shipping_address_2'];
                $Payment['shiptocity'] = wc_clean( stripslashes( @$posted['shipping_city'] ) );
                $Payment['shiptostate'] = @$posted['shipping_state'];
                $Payment['shiptozip'] = @$posted['shipping_postcode'];
                $Payment['shiptocountrycode'] = @$posted['shipping_country'];
                $Payment['shiptophonenum'] = @$posted['shipping_phone'];

            } else {
                $Payment['shiptoname'] = $posted['billing_first_name'] . ' ' . $posted['billing_last_name'];
                $Payment['shiptostreet'] = $posted['billing_address_1'];
                $Payment['shiptostreet2'] = @$posted['billing_address_2'];
                $Payment['shiptocity'] = wc_clean( stripslashes( @$posted['billing_city'] ) );
                $Payment['shiptostate'] = @$posted['billing_state'];
                $Payment['shiptozip'] = @$posted['billing_postcode'];
                $Payment['shiptocountrycode'] = @$posted['billing_country'];
                $Payment['shiptophonenum'] = @$posted['billing_phone'];
            }
        } elseif (is_user_logged_in()) {
            $user_mata = get_user_meta( $this->customer_id );
            $user_mata_data = array_filter( array_map( function( $a ) {
                                    return $a[0];
                    }, $user_mata ) );
            $first_name = ( isset($user_mata_data['billing_first_name']) && !empty($user_mata_data['billing_first_name']) ) ? $user_mata_data['billing_first_name'] : ( isset($user_mata_data['shipping_first_name']) && !empty($user_mata_data['shipping_first_name']) ) ? $user_mata_data['shipping_first_name'] : get_user_meta($this->customer_id, 'display_name', true);
            $last_name = ( isset($user_mata_data['billing_last_name']) && !empty($user_mata_data['billing_last_name']) ) ? $user_mata_data['billing_last_name'] : ( isset($user_mata_data['shipping_last_name']) && !empty($user_mata_data['shipping_last_name']) ) ? $user_mata_data['shipping_last_name'] : '';
            $Payment['shiptoname'] = $first_name . ' ' . $last_name;
            $Payment['shiptostreet'] = ( isset($user_mata_data['billing_address_1']) && !empty($user_mata_data['billing_address_1']) ) ? $user_mata_data['billing_address_1'] : ( isset($user_mata_data['shipping_address_1']) && !empty($user_mata_data['shipping_address_1']) ) ? $user_mata_data['shipping_address_1'] : '';
            $Payment['shiptostreet2'] = ( isset($user_mata_data['billing_address_2']) && !empty($user_mata_data['billing_address_2']) ) ? $user_mata_data['billing_address_2'] : ( isset($user_mata_data['shipping_address_2']) && !empty($user_mata_data['shipping_address_2']) ) ? $user_mata_data['shipping_address_2'] : '';
            $Payment['shiptocity'] = ( isset($user_mata_data['billing_city']) && !empty($user_mata_data['billing_city']) ) ? $user_mata_data['billing_city'] : ( isset($user_mata_data['shipping_city']) && !empty($user_mata_data['shipping_city']) ) ? $user_mata_data['shipping_city'] : '';
            $Payment['shiptocountrycode'] = ( isset($user_mata_data['billing_country']) && !empty($user_mata_data['billing_country']) ) ? $user_mata_data['billing_country'] : ( isset($user_mata_data['shipping_country']) && !empty($user_mata_data['shipping_country']) ) ? $user_mata_data['shipping_country'] : '';
            $Payment['shiptostate'] = ( isset($user_mata_data['billing_state']) && !empty($user_mata_data['billing_state']) ) ? $user_mata_data['billing_state'] : ( isset($user_mata_data['shipping_state']) && !empty($user_mata_data['shipping_state']) ) ? $user_mata_data['shipping_state'] : '';
            $Payment['shiptozip'] = ( isset($user_mata_data['billing_postcode']) && !empty($user_mata_data['billing_postcode']) ) ? $user_mata_data['billing_postcode'] : ( isset($user_mata_data['shipping_postcode']) && !empty($user_mata_data['shipping_postcode']) ) ? $user_mata_data['shipping_postcode'] : '';
            $user_email = get_user_meta($this->customer_id, 'user_email', true);
            $Payment['email'] = ( isset($user_mata_data['billing_email']) && !empty($user_mata_data['billing_email']) ) ? $user_mata_data['billing_email'] : ( isset($user_email) && !empty($user_email) ) ? $user_email : '';
            $Payment['shiptophonenum'] = ( isset($user_mata_data['billing_phone']) && !empty($user_mata_data['billing_phone']) ) ? $user_mata_data['billing_phone'] : '';
        }
        $SECFields = AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_needs_shipping($SECFields);
        
        $PaymentData = AngellEYE_Gateway_Paypal::calculate(null, true);
        $PaymentOrderItems = array();
        $ctr = $total_items = $total_discount = $total_tax = $order_total = 0;
        if ($this->send_items){
            foreach ($PaymentData['order_items'] as $item) {
                $Item = array(
                    'name' => $item['name'], // Item name. 127 char max.
                    'desc' => '', // Item description. 127 char max.
                    'amt' => $item['amt'], // Cost of item.
                    'number' =>  $item['number'], // Item number.  127 char max.
                    'qty' => $item['qty'], // Item qty on order.  Any positive integer.
                    'taxamt' => '', // Item sales tax
                    'itemurl' => '', // URL for the item.
                    'itemcategory' => '', // One of the following values:  Digital, Physical
                    'itemweightvalue' => '', // The weight value of the item.
                    'itemweightunit' => '', // The weight unit of the item.
                    'itemheightvalue' => '', // The height value of the item.
                    'itemheightunit' => '', // The height unit of the item.
                    'itemwidthvalue' => '', // The width value of the item.
                    'itemwidthunit' => '', // The width unit of the item.
                    'itemlengthvalue' => '', // The length value of the item.
                    'itemlengthunit' => '', // The length unit of the item.
                    'ebayitemnumber' => '', // Auction item number.
                    'ebayitemauctiontxnid' => '', // Auction transaction ID number.
                    'ebayitemorderid' => '', // Auction order ID number.
                    'ebayitemcartid' => ''      // The unique identifier provided by eBay for this order from the buyer. These parameters must be ordered sequentially beginning with 0 (for example L_EBAYITEMCARTID0, L_EBAYITEMCARTID1). Character length: 255 single-byte characters
                );
                array_push($PaymentOrderItems, $Item);
            }
            $Payment['order_items'] = $PaymentOrderItems;
        } else {
            $Payment['order_items'] = array();
        }

        /**
         * Shipping/tax/item amount
         */
        $Payment['taxamt']       = $PaymentData['taxamt'];
        $Payment['shippingamt']  = $PaymentData['shippingamt'];
        $Payment['itemamt']      = $PaymentData['itemamt'];

        /*
         * Then we load the payment into the $Payments array
         */
        array_push($Payments, $Payment);

        $BuyerDetails = array(
            'buyerid' => '', // The unique identifier provided by eBay for this buyer.  The value may or may not be the same as the username.  In the case of eBay, it is different.  Char max 255.
            'buyerusername' => '', // The username of the marketplace site.
            'buyerregistrationdate' => '' // The registration of the buyer with the marketplace.
        );

        // For shipping options we create an array of all shipping choices similar to how order items works.
        $ShippingOptions = array();
        $Option = array(
            'l_shippingoptionisdefault' => '', // Shipping option.  Required if specifying the Callback URL.  true or false.  Must be only 1 default!
            'l_shippingoptionname' => '', // Shipping option name.  Required if specifying the Callback URL.  50 character max.
            'l_shippingoptionlabel' => '', // Shipping option label.  Required if specifying the Callback URL.  50 character max.
            'l_shippingoptionamount' => ''      // Shipping option amount.  Required if specifying the Callback URL.
        );
        array_push($ShippingOptions, $Option);
	global $pp_settings, $pp_pro, $pp_payflow;
        
        $PayPalRequestData = array(
            'SECFields' => $SECFields,
            'SurveyChoices' => $SurveyChoices,
            'Payments' => $Payments,

       	);
	
       $PayPalRequestData = AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_paypal_billing_agreement($PayPalRequestData, $this->supports( 'tokenization' ));

        // Pass data into class for processing with PayPal and load the response array into $PayPalResult
        $PayPalResult = $PayPal->SetExpressCheckout($PayPalRequestData);
        
        /**
         *  cURL Error Handling #146 
         *  @since    1.1.8
         */
        
        AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($PayPalResult, $methos_name = 'SetExpressCheckout', $gateway = 'PayPal Express Checkout', $this->error_email_notify);

        /*
         * Log API result
         */
        $this->add_log('Test Mode: ' . $this->testmode);
        $this->add_log('Endpoint: ' . $this->API_Endpoint);

        $PayPalRequest = isset($PayPalResult['RAWREQUEST']) ? $PayPalResult['RAWREQUEST'] : '';
        $PayPalResponse = isset($PayPalResult['RAWRESPONSE']) ? $PayPalResult['RAWRESPONSE'] : '';

        $this->add_log('Request: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalRequest)), true));
        $this->add_log('Response: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalResponse)), true));

        /*
         * Error handling
         */
        if ($PayPal->APICallSuccessful($PayPalResult['ACK'])) {
            $token = urldecode($PayPalResult["TOKEN"]);
            $this->set_session('TOKEN', $token);
        }

        /*
         * Return the class library result array.
         */
        return $PayPalResult;
    }

    /**
     * CallGetShippingDetails
     *
     * Makes a call to PayPal's GetExpressCheckoutDetails API to obtain
     * information about the order and the buyer.
     *
     * @token (string) The token obtained from the previous SetExpressCheckout request.
     */
    function CallGetShippingDetails($token) {
        /*
         * Display message to user if session has expired.
         */
        $this->angelleye_check_cart_items();
        if (sizeof(WC()->cart->get_cart()) == 0) {
            $ms = sprintf(__('Sorry, your session has expired. <a href=%s>Return to homepage &rarr;</a>', 'paypal-for-woocommerce'), '"' . home_url() . '"');
            $ec_cgsd_message = apply_filters('angelleye_get_shipping_ec_message', $ms);
            wc_add_notice($ec_cgsd_message, "error");
            wp_redirect(get_permalink(wc_get_page_id('cart')));
            exit();
        }

        /*
         * Check if the PayPal class has already been established.
         */
        if (!class_exists('Angelleye_PayPal')) {
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.class.php' );
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

        /*
         * Call GetExpressCheckoutDetails
         */
        $PayPalResult = $PayPal->GetExpressCheckoutDetails($token);
        
         /**
         *  cURL Error Handling #146 
         *  @since    1.1.8
         */
        
        AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($PayPalResult, $methos_name = 'GetExpressCheckoutDetails', $gateway = 'PayPal Express Checkout', $this->error_email_notify);

        /*
         * Log API result
         */
        $this->add_log('Test Mode: ' . $this->testmode);
        $this->add_log('Endpoint: ' . $this->API_Endpoint);

        $PayPalRequest = isset($PayPalResult['RAWREQUEST']) ? $PayPalResult['RAWREQUEST'] : '';
        $PayPalResponse = isset($PayPalResult['RAWRESPONSE']) ? $PayPalResult['RAWRESPONSE'] : '';

        
        
        $this->add_log('Request: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalRequest)), true));
        $this->add_log('Response: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalResponse)), true));

        /*
         * Error handling
         */
        if ($PayPal->APICallSuccessful($PayPalResult['ACK'])) {
            $this->set_session('payer_id', $PayPalResult['PAYERID']);
        }
        do_action('enable_automated_account_creation_for_guest_checkouts_paypal_express', $PayPalResult);
        /*
         * Return the class library result array.
         */
        return $PayPalResult;
    }

    /**
     * ConfirmPayment
     *
     * Finalizes the checkout with PayPal's DoExpressCheckoutPayment API
     *
     * @FinalPaymentAmt (double) Final payment amount for the order.
     */
    function ConfirmPayment($FinalPaymentAmt) {
        /*
         * Display message to user if session has expired.
         */
        if (sizeof(WC()->cart->get_cart()) == 0 || empty(WC()->session->TOKEN)) {
            $ms = sprintf(__('Sorry, your session has expired. <a href=%s>Return to homepage &rarr;</a>', 'paypal-for-woocommerce'), '"' . home_url() . '"');
            $ec_confirm_message = apply_filters('angelleye_ec_confirm_message', $ms);
            wc_add_notice($ec_confirm_message, "error");
            wp_redirect(get_permalink(wc_get_page_id('cart')));
        }

        /*
         * Check if the PayPal class has already been established.
         */
        if (!class_exists('Angelleye_PayPal')) {
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.class.php' );
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

        /*
         * Get data from WooCommerce object
         */
        if (!empty($this->confirm_order_id)) {
            $order = new WC_Order($this->confirm_order_id);
            $invoice_number = preg_replace("/[^a-zA-Z0-9]/", "", $order->get_order_number());

            if ($order->customer_note) {
                $customer_notes = wptexturize($order->customer_note);
            }

            
        }

        // Prepare request arrays
        $DECPFields = array(
            'token' => urlencode($this->get_session('TOKEN')), // Required.  A timestamped token, the value of which was returned by a previous SetExpressCheckout call.
            'payerid' => urlencode($this->get_session('payer_id')), // Required.  Unique PayPal customer id of the payer.  Returned by GetExpressCheckoutDetails, or if you used SKIPDETAILS it's returned in the URL back to your RETURNURL.
            'returnfmfdetails' => 1, // Flag to indiciate whether you want the results returned by Fraud Management Filters or not.  1 or 0.
            'giftmessage' => $this->get_session('giftmessage'), // The gift message entered by the buyer on the PayPal Review page.  150 char max.
            'giftreceiptenable' => $this->get_session('giftreceiptenable'), // Pass true if a gift receipt was selected by the buyer on the PayPal Review page. Otherwise pass false.
            'giftwrapname' => $this->get_session('giftwrapname'), // The gift wrap name only if the gift option on the PayPal Review page was selected by the buyer.
            'giftwrapamount' => $this->get_session('giftwrapamount'), // The amount only if the gift option on the PayPal Review page was selected by the buyer.
            'buyermarketingemail' => '', // The buyer email address opted in by the buyer on the PayPal Review page.
            'surveyquestion' => '', // The survey question on the PayPal Review page.  50 char max.
            'surveychoiceselected' => '', // The survey response selected by the buyer on the PayPal Review page.  15 char max.
            'allowedpaymentmethod' => ''     // The payment method type. Specify the value InstantPaymentOnly.
        );

        $Payments = array();
        $Payment = array(
            'amt' => AngellEYE_Gateway_Paypal::number_format($FinalPaymentAmt), // Required.  The total cost of the transaction to the customer.  If shipping cost and tax charges are known, include them in this value.  If not, this value should be the current sub-total of the order.
            'currencycode' => $order->get_order_currency(), // A three-character currency code.  Default is USD.
            'shippingdiscamt' => '', // Total shipping discount for this order, specified as a negative number.
            'insuranceoptionoffered' => '', // If true, the insurance drop-down on the PayPal review page displays the string 'Yes' and the insurance amount.  If true, the total shipping insurance for this order must be a positive number.
            'handlingamt' => '', // Total handling costs for this order.  If you specify HANDLINGAMT you mut also specify a value for ITEMAMT.
            'desc' => '', // Description of items on the order.  127 char max.
            'custom' => '', // Free-form field for your own use.  256 char max.
            'invnum' => $this->invoice_id_prefix . $invoice_number, // Your own invoice or tracking number.  127 char max.
            'notetext' => $this->get_session('customer_notes'), // Note to the merchant.  255 char max.
            'allowedpaymentmethod' => '', // The payment method type.  Specify the value InstantPaymentOnly.
            'paymentaction' => !empty($this->payment_action) ? $this->payment_action : 'Sale', // How you want to obtain the payment.  When implementing parallel payments, this field is required and must be set to Order.
            'paymentrequestid' => '', // A unique identifier of the specific payment request, which is required for parallel payments.
            'sellerpaypalaccountid' => '', // A unique identifier for the merchant.  For parallel payments, this field is required and must contain the Payer ID or the email address of the merchant.
            'sellerid' => '', // The unique non-changing identifer for the seller at the marketplace site.  This ID is not displayed.
            'sellerusername' => '', // The current name of the seller or business at the marketplace site.  This name may be shown to the buyer.
            'sellerregistrationdate' => '', // Date when the seller registered at the marketplace site.
            'softdescriptor' => ''     // A per transaction description of the payment that is passed to the buyer's credit card statement.
        );
        
        if( isset($this->notifyurl) && !empty($this->notifyurl)) {
           $Payment['notifyurl'] = $this->notifyurl;
        }

        $PaymentData = AngellEYE_Gateway_Paypal::calculate($order, $this->send_items);
        $PaymentOrderItems = array();
        if ($this->send_items) {
            foreach ($PaymentData['order_items'] as $item) {
                $Item = array(
                    'name'      => $item['name'], // Item name. 127 char max.
                    'desc'      => '', // Item description. 127 char max.
                    'amt'       => $item['amt'], // Cost of item.
                    'number'    => $item['number'], // Item number.  127 char max.
                    'qty'       => $item['qty'], // Item qty on order.  Any positive integer.
                    'taxamt'    => '', // Item sales tax
                    'itemurl'   => '', // URL for the item.
                    'itemcategory' => '', // One of the following values:  Digital, Physical
                    'itemweightvalue' => '', // The weight value of the item.
                    'itemweightunit' => '', // The weight unit of the item.
                    'itemheightvalue' => '', // The height value of the item.
                    'itemheightunit' => '', // The height unit of the item.
                    'itemwidthvalue' => '', // The width value of the item.
                    'itemwidthunit' => '', // The width unit of the item.
                    'itemlengthvalue' => '', // The length value of the item.
                    'itemlengthunit' => '', // The length unit of the item.
                    'ebayitemnumber' => '', // Auction item number.
                    'ebayitemauctiontxnid' => '', // Auction transaction ID number.
                    'ebayitemorderid' => '', // Auction order ID number.
                    'ebayitemcartid' => ''                        // The unique identifier provided by eBay for this order from the buyer. These parameters must be ordered sequentially beginning with 0 (for example L_EBAYITEMCARTID0, L_EBAYITEMCARTID1). Character length: 255 single-byte characters
                );
                array_push($PaymentOrderItems, $Item);
            }
        }

        /*
         * Set tax, shipping, itemamt
         */
        $Payment['taxamt']      = AngellEYE_Gateway_Paypal::number_format($PaymentData['taxamt']);       // Required if you specify itemized L_TAXAMT fields.  Sum of all tax items in this order.
        $Payment['shippingamt'] = AngellEYE_Gateway_Paypal::number_format($PaymentData['shippingamt']);      // Total shipping costs for this order.  If you specify SHIPPINGAMT you mut also specify a value for ITEMAMT.
        $Payment['itemamt']     = AngellEYE_Gateway_Paypal::number_format($PaymentData['itemamt']);      // Total shipping costs for this order.  If you specify SHIPPINGAMT you mut also specify a value for ITEMAMT.

        $Payment['order_items'] = $PaymentOrderItems;
       

        $UserSelectedOptions = array(
            'shippingcalculationmode' => '', // Describes how the options that were presented to the user were determined.  values are:  API - Callback   or   API - Flatrate.
            'insuranceoptionselected' => '', // The Yes/No option that you chose for insurance.
            'shippingoptionisdefault' => '', // Is true if the buyer chose the default shipping option.
            'shippingoptionamount' => '', // The shipping amount that was chosen by the buyer.
            'shippingoptionname' => '', // Is true if the buyer chose the default shipping option...??  Maybe this is supposed to show the name..??
        );
        
        $REVIEW_RESULT = unserialize($this->get_session('RESULT'));
      
        $PaymentRedeemedOffers = array();
        
        if( (isset($REVIEW_RESULT) && !empty($REVIEW_RESULT)) && isset($REVIEW_RESULT['WALLETTYPE0'])) {
           $i = 0;
           while(isset($REVIEW_RESULT['WALLETTYPE' . $i])) {
                $RedeemedOffer = array(
                    'redeemedoffername' => $REVIEW_RESULT['WALLETDESCRIPTION' . $i], 						    // The name of the buyer's wallet item offer redeemed in this transaction, such as, a merchant coupon or a loyalty program card.
                    'redeemedofferdescription' => '',                    // Description of the offer redeemed in this transaction, such as, a merchant coupon or a loyalty program.
                    'redeemedofferamount' => '',                         // Amount of the offer redeemed in this transaction
                    'redeemedoffertype' => $REVIEW_RESULT['WALLETTYPE' . $i],                           // The type of the offer redeemed in this transaction
                    'redeemedofferid' => $REVIEW_RESULT['WALLETID' . $i],                             // Unique ID of the offer redeemed in this transaction or the buyer's loyalty card account number.
                    'redeemedofferpointsaccrued' => '',                  // The number of loyalty points accrued in this transaction.
                    'cummulativepointsname' => '',          // The name of the loyalty points program in which the buyer earned points in this transaction.
                    'cummulativepointsdescription' => '',   // Description of the loyalty points program.
                    'cummulativepointstype' => '',          // Type of discount or loyalty program.  Values:  LOYALTY_CARD
                    'cummulativepointsid' => '',            // Unique ID of the buyer's loyalty points account.
                    'cummulativepointsaccrued' => '',       // The cummulative number of loyalty points the buyer has accrued.
                );
                
               $i = $i + 1;
               array_push($PaymentRedeemedOffers, $RedeemedOffer);
           }
           $Payment['redeemed_offers'] = $PaymentRedeemedOffers;
           array_push($Payments, $Payment);
        } else {
             array_push($Payments, $Payment);
        }
        
        if(WC()->cart->needs_shipping()) {
        
            $shipping_first_name = $order->shipping_first_name;
            $shipping_last_name = $order->shipping_last_name;
            $shipping_address_1 = $order->shipping_address_1;
            $shipping_address_2 = $order->shipping_address_2;
            $shipping_city = $order->shipping_city;
            $shipping_state = $order->shipping_state;
            $shipping_postcode = $order->shipping_postcode;
            $shipping_country = $order->shipping_country;

            $Payment = array('shiptoname' => $shipping_first_name . ' ' . $shipping_last_name, // Required if shipping is included.  Person's name associated with this address.  32 char max.
                    'shiptostreet' => $shipping_address_1, // Required if shipping is included.  First street address.  100 char max.
                    'shiptostreet2' => $shipping_address_2, // Second street address.  100 char max.
                    'shiptocity' => wc_clean( stripslashes( $shipping_city ) ), // Required if shipping is included.  Name of city.  40 char max.
                    'shiptostate' => $shipping_state, // Required if shipping is included.  Name of state or province.  40 char max.
                    'shiptozip' => $shipping_postcode, // Required if shipping is included.  Postal code of shipping address.  20 char max.
                    'shiptocountrycode' => $shipping_country, // Required if shipping is included.  Country code of shipping address.  2 char max.
                    'shiptophonenum' => '', // Phone number for shipping address.  20 char max.
             );
            
            array_push($Payments, $Payment);
        }
        
        
        $PayPalRequestData = array(
            'DECPFields' => $DECPFields,
            'Payments' => $Payments,
            //'UserSelectedOptions' => $UserSelectedOptions
        );

        $paid_order_id = $this->angelleye_prevent_duplicate_payment_request(WC()->session->TOKEN);
                
        if( $paid_order_id ) {
            $ms = sprintf(__('Sorry, A successful transaction has already been completed for this token. <a href=%s>Return to homepage &rarr;</a>', 'paypal-for-woocommerce'), '"' . home_url() . '"');
            $ec_confirm_message = apply_filters('angelleye_ec_confirm_message', $ms);
            wc_add_notice($ec_confirm_message, "error");
            wp_redirect(get_permalink(wc_get_page_id('cart')));
            exit();
        }
        // Pass data into class for processing with PayPal and load the response array into $PayPalResult
        $PayPalResult = $PayPal->DoExpressCheckoutPayment($PayPalRequestData);

        /**
         *  cURL Error Handling #146 
         *  @since    1.1.8
         */
        
        AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($PayPalResult, $methos_name = 'DoExpressCheckoutPayment', $gateway = 'PayPal Express Checkout', $this->error_email_notify);
        
        /*
         * Log API result
         */
        $this->add_log('Test Mode: ' . $this->testmode);
        $this->add_log('Endpoint: ' . $this->API_Endpoint);

        $PayPalRequest = isset($PayPalResult['RAWREQUEST']) ? $PayPalResult['RAWREQUEST'] : '';
        $PayPalResponse = isset($PayPalResult['RAWRESPONSE']) ? $PayPalResult['RAWRESPONSE'] : '';

        $this->add_log('Request: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalRequest)), true));
        $this->add_log('Response: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalResponse)), true));

        /*
         * Return the class library result array.
         */
        return $PayPalResult;
    }

    /**
     * RedirectToPayPal
     *
     * Redirects to PayPal.com site.
     * Inputs:  NVP string.
     * Returns:
     */
    function RedirectToPayPal($token) {
        // Redirect to paypal.com here
        $payPalURL = $this->PAYPAL_URL . $token;
        wp_redirect($payPalURL, 302);
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
    function deformatNVP($nvpstr) {
        $intial = 0;
        $nvpArray = array();
        while (strlen($nvpstr)) {
            //postion of Key
            $keypos = strpos($nvpstr, '=');
            //position of value
            $valuepos = strpos($nvpstr, '&') ? strpos($nvpstr, '&') : strlen($nvpstr);
            /* getting the Key and Value values and storing in a Associative Array */
            $keyval = substr($nvpstr, $intial, $keypos);
            $valval = substr($nvpstr, $keypos + 1, $valuepos - $keypos - 1);
            //decoding the respose
            $nvpArray[urldecode($keyval)] = urldecode($valval);
            $nvpstr = substr($nvpstr, $valuepos + 1, strlen($nvpstr));
        }
        return $nvpArray;
    }

    /**
     * get_state
     *
     * @param $country - country code sent by PayPal
     * @param $state - state name or code sent by PayPal
     */
    function get_state_code($country, $state) {
        // If not US address, then convert state to abbreviation
        if ($country != 'US') {
            if( isset(WC()->countries->states[WC()->customer->get_country()]) && !empty(WC()->countries->states[WC()->customer->get_country()]) ) {
                $local_states = WC()->countries->states[WC()->customer->get_country()];
                if (!empty($local_states) && in_array($state, $local_states)) {
                    foreach ($local_states as $key => $val) {
                        if ($val == $state) {
                            $state = $key;
                        }
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
    public function set_session($key, $value) {
        WC()->session->$key = $value;
    }

    /**
     * get_session function.
     *
     * @access private
     * @param mixed $key
     * @return void
     */
    public function get_session($key) {
        return WC()->session->$key;
    }

    public function remove_session($key) {
        WC()->session->$key = "";
    }

    static function woocommerce_before_cart() {
        global $pp_settings, $pp_pro, $pp_payflow;
        $payment_gateways_count = 0;
        echo "<style>table.cart td.actions .input-text, table.cart td.actions .button, table.cart td.actions .checkout-button {margin-bottom: 0.53em !important}</style>";
        if ((@$pp_settings['enabled'] == 'yes') && 0 < WC()->cart->total) {
            $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
            unset($payment_gateways['paypal_pro']);
            unset($payment_gateways['paypal_pro_payflow']);
            if ((isset($pp_settings['show_on_checkout']) && $pp_settings['show_on_checkout'] == 'regular')) {
                $payment_gateways_count = 1;
            }
            if ((empty($payment_gateways) || @$pp_settings['enabled'] == 'yes') && (count($payment_gateways) == $payment_gateways_count)) {
                if (@$pp_pro['enabled'] == 'yes' || @$pp_payflow['enabled'] == 'yes') {
                    $checkout_button_display_text = !empty($pp_settings['show_on_cart']) && $pp_settings['show_on_cart'] == 'yes' ? __('Pay with Credit Card', 'paypal-for-woocommerce') : __('Proceed to Checkout','paypal-for-woocommerce');
                    echo '<script type="text/javascript">
                                jQuery(document).ready(function(){
                                    if (jQuery(".checkout-button").is("input")) {
                                        jQuery(".checkout-button").val("' . $checkout_button_display_text . '");
                                    } else {
                                        jQuery(".checkout-button").html("<span>' . $checkout_button_display_text . '</span>");
                                    }
                                });
                              </script>';
                } elseif (empty($pp_settings['show_on_cart']) || $pp_settings['show_on_cart'] == 'yes') {
                    echo '<style> input.checkout-button,
                                 a.checkout-button {
                                    display: none !important;
                                }</style>';
                }
            }
        }
    }

    /**
     * Checkout Button
     *
     * Triggered from the 'woocommerce_proceed_to_checkout' action.
     * Displays the PayPal Express button.
     */
    static function woocommerce_paypal_express_checkout_button_angelleye() {
        global $pp_settings;
        if(!AngellEYE_Utility::is_valid_for_use_paypal_express()) {
                return false;
        }
        if (@$pp_settings['enabled'] == 'yes' && (empty($pp_settings['show_on_cart']) || $pp_settings['show_on_cart'] == 'yes') && 0 < WC()->cart->total) {
            if (empty($pp_settings['checkout_with_pp_button_type'])) {
                $pp_settings['checkout_with_pp_button_type'] = 'paypalimage';
            }
            $angelleyeOverlay = '<div class="blockUI blockOverlay angelleyeOverlay" style="display:none;z-index: 1000; border: none; margin: 0px;  width: 100%; height: 100%; top: 0px; left: 0px; opacity: 0.6; cursor: default; position: absolute; background: url('. WC()->plugin_url() .'/assets/images/select2-spinner.gif) 50% 50% / 16px 16px no-repeat rgb(255, 255, 255);"></div>';
            switch ($pp_settings['checkout_with_pp_button_type']) {
                case "textbutton":
                    if (!empty($pp_settings['pp_button_type_text_button'])) {
                        $button_text = $pp_settings['pp_button_type_text_button'];
                    } else {
                        $button_text = __('Proceed to Checkout', 'paypal-for-woocommerce');
                    }
                    echo '<a style="margin-bottom:1em; border: none; " class="paypal_checkout_button button alt" href="' . esc_url(add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">' . $button_text . '</a>';
                    echo $angelleyeOverlay;
                    break;
                case "paypalimage":
                    echo '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">';
                    echo '<img src='.WC_Gateway_PayPal_Express_AngellEYE::angelleye_get_paypalimage().' style="width: auto; height: auto; margin: 3px 5px 3px 0; border: none; padding: 0;" align="top" alt="' . __( 'Pay with PayPal', 'paypal-for-woocommerce' ) . '" />';
                    echo "</a>";
                    echo $angelleyeOverlay;
                    break;
                case "customimage":
                    $button_img = $pp_settings['pp_button_type_my_custom'];
                    echo '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">';
                    echo '<img src="'.$button_img.'" style="margin: 3px 0 0 0; border: none; padding: 0;" align="top" alt="' . __( 'Pay with PayPal', 'paypal-for-woocommerce' ) . '" />';
                    echo "</a>";
                    echo $angelleyeOverlay;
                    break;
            }

            /**
             * Displays the PayPal Credit checkout button if enabled in EC settings.
             */
            if (isset($pp_settings['show_paypal_credit']) && $pp_settings['show_paypal_credit'] == 'yes') {
                $paypal_credit_button_markup = '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('use_paypal_credit', 'true', add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/'))))) . '" >';
                $paypal_credit_button_markup .= '<img src="https://www.paypalobjects.com/webstatic/en_US/i/buttons/ppcredit-logo-small.png" width="148" height="26" style="width: 148px; height: 26px; border: none; padding: 0; margin: 0;" align="top" alt="' . __( 'Check out with PayPal Credit', 'paypal-for-woocommerce' ) . '" />';
                $paypal_credit_button_markup .= '</a>';
                $paypal_credit_button_markup .= $angelleyeOverlay;
                echo $paypal_credit_button_markup;
            }
        }
    }

    static function get_button_locale_code() {
        $_supportedLocale = array(
            'en_US', 'fr_XC', 'es_XC', 'zh_XC', 'en_AU', 'de_DE', 'nl_NL',
            'fr_FR', 'pt_BR', 'fr_CA', 'zh_CN', 'ru_RU', 'en_GB', 'zh_HK',
            'he_IL', 'it_IT', 'ja_JP', 'pl_PL', 'pt_PT', 'es_ES', 'sv_SE', 'zh_TW', 'tr_TR'
	);
        $locale = get_locale();
        if ( ! in_array( $locale, $_supportedLocale ) ) {
                $locale = 'en_US';
        }
        return $locale;
    }
    
    public static function angelleye_get_paypalimage() {
        if( self::get_button_locale_code() == 'en_US') {
            return "https://www.paypalobjects.com/webstatic/".self::get_button_locale_code()."/i/buttons/checkout-logo-medium.png";
        } else {
            return esc_url(add_query_arg('cmd', '_dynamic-image', add_query_arg('locale', self::get_button_locale_code(), 'https://fpdbs.paypal.com/dynamicimageweb')));
        }
        
        
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
        $this->add_log('Begin Refund');
        $this->add_log('Order: ' . print_r($order, true));
        $this->add_log('Transaction ID: ' . print_r($order->get_transaction_id(), true));
        $this->add_log('API Username: ' . print_r($this->api_username, true));
        $this->add_log('API Password: ' . print_r($this->api_password, true));
        $this->add_log('API Signature: ' . print_r($this->api_signature, true));
        if (!$order || !$order->get_transaction_id() || !$this->api_username || !$this->api_password || !$this->api_signature) {
            return false;
        }
        $this->add_log('Include Class Request');
        /*
         * Check if the PayPal class has already been established.
         */
        if (!class_exists('Angelleye_PayPal')) {
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.class.php' );
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
            'transactionid' => $order->get_transaction_id(), // Required.  PayPal transaction ID for the order you're refunding.
            'payerid' => '', // Encrypted PayPal customer account ID number.  Note:  Either transaction ID or payer ID must be specified.  127 char max
            'invoiceid' => '', // Your own invoice tracking number.
            'refundtype' => $order->get_total() == $amount ? 'Full' : 'Partial', // Required.  Type of refund.  Must be Full, Partial, or Other.
            'amt' => AngellEYE_Gateway_Paypal::number_format($amount), // Refund Amt.  Required if refund type is Partial.
            'currencycode' => $order->get_order_currency(), // Three-letter currency code.  Required for Partial Refunds.  Do not use for full refunds.
            'note' => $reason, // Custom memo about the refund.  255 char max.
            'retryuntil' => '', // Maximum time until you must retry the refund.  Note:  this field does not apply to point-of-sale transactions.
            'refundsource' => '', // Type of PayPal funding source (balance or eCheck) that can be used for auto refund.  Values are:  any, default, instant, eCheck
            'merchantstoredetail' => '', // Information about the merchant store.
            'refundadvice' => '', // Flag to indicate that the buyer was already given store credit for a given transaction.  Values are:  1/0
            'refunditemdetails' => '', // Details about the individual items to be returned.
            'msgsubid' => '', // A message ID used for idempotence to uniquely identify a message.
            'storeid' => '', // ID of a merchant store.  This field is required for point-of-sale transactions.  50 char max.
            'terminalid' => ''        // ID of the terminal.  50 char max.
        );

        $PayPalRequestData = array('RTFields' => $RTFields);
        $this->add_log('Refund Request: ' . print_r($PayPalRequestData, true));
        // Pass data into class for processing with PayPal and load the response array into $PayPalResult
        $PayPalResult = $PayPal->RefundTransaction($PayPalRequestData);
        
         /**
         *  cURL Error Handling #146 
         *  @since    1.1.8
         */
        
        AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($PayPalResult, $methos_name = 'RefundTransaction', $gateway = 'PayPal Express Checkout', $this->error_email_notify);
        
        $this->add_log('Refund Information: ' . print_r($PayPalResult, true));
        if ($PayPal->APICallSuccessful($PayPalResult['ACK'])) {

            $order->add_order_note('Refund Transaction ID:' . $PayPalResult['REFUNDTRANSACTIONID']);

            $max_remaining_refund = wc_format_decimal( $order->get_total() - $order->get_total_refunded() );
            if ( !$max_remaining_refund > 0 ) {
                $order->update_status('refunded');
            }

            if (ob_get_length()) ob_end_clean();
            return true;
        } else {
            $ec_message = apply_filters( 'ae_ppec_refund_error_message', $PayPalResult['L_LONGMESSAGE0'], $PayPalResult['L_ERRORCODE0'], $PayPalResult );
            return new WP_Error('ec_refund-error', $ec_message);
        }
    }

    function top_cart_button() {
        if($this->is_express_checkout_credentials_is_set()) {
            if (!empty($this->settings['button_position']) && ($this->settings['button_position'] == 'top' || $this->settings['button_position'] == 'both')) {
                echo '<div class="wc-proceed-to-checkout angelleye_cart_button">';
                $this->woocommerce_paypal_express_checkout_button_angelleye();
                echo '</div>';
            }
        }
    }

    /**
     * Regular checkout process
     */
    function regular_checkout($posted) {
        if(!empty($_POST['wc-paypal_express-payment-token']) && $_POST['wc-paypal_express-payment-token'] != 'new') {
            return;
        }
        if ($posted['payment_method'] == 'paypal_express' && wc_notice_count('error') == 0) {

            if (!is_user_logged_in() && (get_option( 'woocommerce_enable_guest_checkout' ) != 'yes' || (isset($posted['createaccount']) && $posted['createaccount'] == 1) )) {

                $this->customer_id = apply_filters('woocommerce_checkout_customer_id', get_current_user_id());
                $username = !empty($posted['account_username']) ? $posted['account_username'] : '';
                $password = !empty($posted['account_password']) ? $posted['account_password'] : '';
                $new_customer = wc_create_new_customer($posted['billing_email'], $username, $password);

                if (is_wp_error($new_customer)) {
                    throw new Exception($new_customer->get_error_message());
                }

                $this->customer_id = $new_customer;

                wc_set_customer_auth_cookie($this->customer_id);

                // As we are now logged in, checkout will need to refresh to show logged in data
                WC()->session->set('reload_checkout', true);

                // Also, recalculate cart totals to reveal any role-based discounts that were unavailable before registering
                WC()->cart->calculate_totals();

                // Add customer info from other billing fields
                if ($posted['billing_first_name'] && apply_filters('woocommerce_checkout_update_customer_data', true, $this)) {
                    $userdata = array(
                        'ID' => $this->customer_id,
                        'first_name' => $posted['billing_first_name'] ? $posted['billing_first_name'] : '',
                        'last_name' => $posted['billing_last_name'] ? $posted['billing_last_name'] : '',
                        'display_name' => $posted['billing_first_name'] ? $posted['billing_first_name'] : ''
                    );
                    wp_update_user(apply_filters('woocommerce_checkout_customer_userdata', $userdata, $this));
                }
            }
            do_action( 'woocommerce_checkout_update_user_meta', $this->customer_id, $posted );
            $this->set_session('checkout_form', serialize($posted));
            if( isset($_POST) && !empty($_POST)) {
                $this->set_session('post_data', serialize($_POST));
            }
            $this->paypal_express_checkout($posted);
            return;
        }
    }
    
    /**
     * Get user's IP address
     */
    function get_user_ip() {
        return (isset($_SERVER['HTTP_X_FORWARD_FOR']) && !empty($_SERVER['HTTP_X_FORWARD_FOR'])) ? $_SERVER['HTTP_X_FORWARD_FOR'] : $_SERVER['REMOTE_ADDR'];
    }
    
    public function angelleye_prevent_duplicate_payment_request($token) {
        if( isset($token) && !empty($token) ) {
            $args = array(
                'post_type' => 'shop_order',
                'post_status' => 'any',
                'meta_query' => array(
                    array(
                        'key' => '_express_checkout_token',
                        'value' => $token,
                        'compare' => '='
                    )
                )
            );

            $posts = get_posts($args);
            if ( ! empty( $posts ) && ( isset($posts[0]->ID) && !empty($posts[0]->ID) ) ) {
                $this->add_log('...Prevented Duplicate Request: A successful transaction has already been completed for this token, Order ID: ' . print_r($posts[0]->ID, true));
                return $posts[0]->ID;
            } else {
                return false;
            }
            
        } else {
            return false;
        }
    }
    
    public function angelleye_check_cart_items() {
        if( WC()->cart->check_cart_items() == false) {
            wp_redirect(get_permalink(wc_get_page_id('cart')));
            exit();
        }
    }
    
    public function pfw_remove_checkout_session_data() {
        $checkout_session_array = array('TOKEN', 'PayerID', 'RESULT', 'company', 'firstname', 'lastname', 'shiptoname', 'shiptostreet', 'shiptostreet2', 'shiptocity', 'shiptocountrycode', 'shiptostate', 'shiptozip', 'payeremail', 'giftmessage', 'giftreceiptenable', 'giftwrapname', 'giftwrapamount', 'customer_notes', 'phonenum', 'payer_id', 'checkout_form', 'post_data');
        foreach ($checkout_session_array as $key => $value) {
            if(isset( WC()->session->$value ) ) {
                unset( WC()->session->$value );
            }
        }
    }
    
    public function paypal_for_woocommerce_update_user_meta($result) {
        if($this->customer_id == 0) {
            return false;
        }
        require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/NameParser.php' );
        $parser = new FullNameParser();
        if( isset($result['SHIPTONAME']) && !empty($result['SHIPTONAME'])) {
            $split_name = $parser->split_full_name($result['SHIPTONAME']);
            $shipping_first_name = $split_name['fname'];
            $shipping_last_name = $split_name['lname'];
            $full_name = $split_name['fullname'];
        }

        // Add customer info from other billing fields
        if (isset($result)) {
            update_user_meta($this->customer_id, 'first_name', isset($result['FIRSTNAME']) ? $result['FIRSTNAME'] : '');
            update_user_meta($this->customer_id, 'last_name', isset($result['LASTNAME']) ? $result['LASTNAME'] : '');
            update_user_meta($this->customer_id, 'shipping_first_name', isset($shipping_first_name) ? $shipping_first_name : '');
            update_user_meta($this->customer_id, 'shipping_last_name', isset($shipping_last_name) ? $shipping_first_name : '');
            update_user_meta($this->customer_id, 'shipping_company', isset($result['BUSINESS']) ? $result['BUSINESS'] : '' );
            update_user_meta($this->customer_id, 'shipping_address_1', isset($result['SHIPTOSTREET']) ? $result['SHIPTOSTREET'] : '');
            update_user_meta($this->customer_id, 'shipping_address_2', isset($result['SHIPTOSTREET2']) ? $result['SHIPTOSTREET2'] : '');
            update_user_meta($this->customer_id, 'shipping_city', isset($result['SHIPTOCITY']) ? $result['SHIPTOCITY'] : '' );
            update_user_meta($this->customer_id, 'shipping_postcode', isset($result['SHIPTOZIP']) ? $result['SHIPTOZIP'] : '');
            update_user_meta($this->customer_id, 'shipping_country', isset($result['SHIPTOCOUNTRYCODE']) ? $result['SHIPTOCOUNTRYCODE'] : '');
            update_user_meta($this->customer_id, 'shipping_state', isset($result['SHIPTOSTATE']) ? $result['SHIPTOSTATE'] : '');
            $user_submit_form = maybe_unserialize(WC()->session->checkout_form);
            if( (isset($user_submit_form) && !empty($user_submit_form) && is_array($user_submit_form) )) {
                update_user_meta($this->customer_id, 'billing_first_name', isset($user_submit_form['billing_first_name']) ?  $user_submit_form['billing_first_name'] : $result['FIRSTNAME']);
                update_user_meta($this->customer_id, 'billing_last_name', isset($user_submit_form['billing_last_name']) ?  $user_submit_form['billing_last_name'] : $result['LASTNAME']);
                update_user_meta($this->customer_id, 'billing_address_1', isset($user_submit_form['billing_address_1']) ?  $user_submit_form['billing_address_1'] : $result['SHIPTOSTREET']);
                update_user_meta($this->customer_id, 'billing_address_2', isset($user_submit_form['billing_address_2']) ?  $user_submit_form['billing_address_2'] : $result['SHIPTOSTREET2']);
                update_user_meta($this->customer_id, 'billing_city', isset($user_submit_form['billing_city']) ?  wc_clean( stripslashes( $user_submit_form['billing_city'] ) ) : $result['SHIPTOCITY']);
                update_user_meta($this->customer_id, 'billing_postcode', isset($user_submit_form['billing_postcode']) ?  $user_submit_form['billing_postcode'] : $result['SHIPTOZIP']);
                update_user_meta($this->customer_id, 'billing_country', isset($user_submit_form['billing_country']) ?  $user_submit_form['billing_country'] : $result['SHIPTOCOUNTRYCODE']);
                update_user_meta($this->customer_id, 'billing_state', isset($user_submit_form['billing_state']) ?  $user_submit_form['billing_state'] : $result['SHIPTOSTATE']);
                update_user_meta($this->customer_id, 'billing_phone', isset($user_submit_form['billing_phone']) ?  $user_submit_form['billing_phone'] : $result['PHONENUM']);
                update_user_meta($this->customer_id, 'billing_email', isset($user_submit_form['billing_email']) ?  $user_submit_form['billing_email'] : $result['EMAIL']);
            } else {
                
                update_user_meta($this->customer_id, 'billing_first_name', $result['FIRSTNAME']);
                update_user_meta($this->customer_id, 'billing_last_name', $result['LASTNAME']);
                update_user_meta($this->customer_id, 'billing_address_1', isset($result['SHIPTOSTREET']) ? $result['SHIPTOSTREET'] : '');
                update_user_meta($this->customer_id, 'billing_address_2', isset($result['SHIPTOSTREET2']) ? $result['SHIPTOSTREET2'] : '');
                update_user_meta($this->customer_id, 'billing_city', isset($result['SHIPTOCITY']) ? $result['SHIPTOCITY'] : '');
                update_user_meta($this->customer_id, 'billing_postcode', isset($result['SHIPTOZIP']) ? $result['SHIPTOZIP'] : '');
                update_user_meta($this->customer_id, 'billing_country', isset($result['SHIPTOCOUNTRYCODE']) ? $result['SHIPTOCOUNTRYCODE'] : '');
                update_user_meta($this->customer_id, 'billing_state', isset($result['SHIPTOSTATE']) ? $result['SHIPTOSTATE'] : '');
                update_user_meta($this->customer_id, 'billing_phone', isset($result['PHONENUM']) ? $result['PHONENUM'] : '');
                update_user_meta($this->customer_id, 'billing_email', isset($result['EMAIL']) ? $result['EMAIL'] : '');
            }
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
    
    public function payment_fields() {
        if ( $description = $this->get_description() ) {
            echo wpautop( wptexturize( $description ) );
	}
        $this->new_method_label = __( 'Create a new billing agreement', 'paypal-for-woocommerce' );
        if ( $this->supports( 'tokenization' ) && is_checkout() ) {
            $this->tokenization_script();
            $this->saved_payment_methods();
            $this->save_payment_method_checkbox();
            do_action('payment_fields_saved_payment_methods', $this);
        }
    }
    
   public function process_payment($order_id) {
       if(!empty($_POST['wc-paypal_express-payment-token']) && $_POST['wc-paypal_express-payment-token'] != 'new') {
            $result = $this->DoReferenceTransaction($order_id);
            if ($result['ACK'] == 'Success' || $result['ACK'] == 'SuccessWithWarning') {
                $order = wc_get_order($order_id);
                $order->payment_complete($result['TRANSACTIONID']);
                $order->add_order_note(sprintf(__('%s payment approved! Trnsaction ID: %s', 'paypal-for-woocommerce'), $this->title, $result['TRANSACTIONID']));
                WC()->cart->empty_cart();
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
                   
            } else {
                $redirect_url = get_permalink(wc_get_page_id('cart'));
                $this->paypal_express_checkout_error_handler($request_name = 'DoReferenceTransaction', $redirect_url, $result);
            }
        }
    }
    
    public function DoReferenceTransaction($order_id) {
        $PayPalRequestData = array();
        $token_id = $_POST['wc-paypal_express-payment-token'];
        $token    = WC_Payment_Tokens::get( $token_id );
        if ( $token->get_user_id() !== get_current_user_id() ) {
            return;
        }
        $order = wc_get_order($order_id);
        if (sizeof(WC()->cart->get_cart()) == 0 ) {
            $ms = sprintf(__('Sorry, your session has expired. <a href=%s>Return to homepage &rarr;</a>', 'paypal-for-woocommerce'), '"' . home_url() . '"');
            $ec_confirm_message = apply_filters('angelleye_ec_confirm_message', $ms);
            wc_add_notice($ec_confirm_message, "error");
            wp_redirect(get_permalink(wc_get_page_id('cart')));
        }
        if (!class_exists('Angelleye_PayPal')) {
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.class.php' );
        }
        $PayPalConfig = array(
            'Sandbox' => $this->testmode == 'yes' ? TRUE : FALSE,
            'APIUsername' => $this->api_username,
            'APIPassword' => $this->api_password,
            'APISignature' => $this->api_signature,
            'Force_tls_one_point_two' => $this->Force_tls_one_point_two
        );
        $PayPal = new Angelleye_PayPal($PayPalConfig);
        if (!empty($this->confirm_order_id)) {
            $order = new WC_Order($this->confirm_order_id);
            $invoice_number = preg_replace("/[^a-zA-Z0-9]/", "", $order->get_order_number());
            if ($order->customer_note) {
                $customer_notes = wptexturize($order->customer_note);
            } else {
                $customer_notes = '';
            }
        } else {
            $invoice_number = $order->id;
        }
        $DRTFields = array(
            'referenceid' => $token->get_token(), 
            'paymentaction' => !empty($this->payment_action) ? $this->payment_action : 'Sale', 
            'returnfmfdetails' => '1', 
            'softdescriptor' => ''
        );
        $PayPalRequestData['DRTFields'] = $DRTFields;
        $PaymentDetails = array(
            'amt' => AngellEYE_Gateway_Paypal::number_format($order->order_total), 							// Required. Total amount of the order, including shipping, handling, and tax.
            'currencycode' => $order->get_order_currency(), 					// A three-character currency code.  Default is USD.
            'itemamt' => '', 						// Required if you specify itemized L_AMT fields. Sum of cost of all items in this order.  
            'shippingamt' => '', 					// Total shipping costs for this order.  If you specify SHIPPINGAMT you mut also specify a value for ITEMAMT.
            'insuranceamt' => '', 
            'shippingdiscount' => '', 
            'handlingamt' => '', 					// Total handling costs for this order.  If you specify HANDLINGAMT you mut also specify a value for ITEMAMT.
            'taxamt' => '', 						// Required if you specify itemized L_TAXAMT fields.  Sum of all tax items in this order. 
            'insuranceoptionoffered' => '', 		// If true, the insurance drop-down on the PayPal review page displays Yes and shows the amount.
            'desc' => '', 							// Description of items on the order.  127 char max.
            'custom' => '', 						// Free-form field for your own use.  256 char max.
            'invnum' => $this->invoice_id_prefix . $invoice_number, 						// Your own invoice or tracking number.  127 char max.
            'buttonsource' => ''					// URL for receiving Instant Payment Notifications
        );
        if( isset($this->notifyurl) && !empty($this->notifyurl)) {
           $PaymentDetails['notifyurl'] = $this->notifyurl;
        }
        if(WC()->cart->needs_shipping()) {
            $shipping_first_name = $order->shipping_first_name;
            $shipping_last_name = $order->shipping_last_name;
            $shipping_address_1 = $order->shipping_address_1;
            $shipping_address_2 = $order->shipping_address_2;
            $shipping_city = $order->shipping_city;
            $shipping_state = $order->shipping_state;
            $shipping_postcode = $order->shipping_postcode;
            $shipping_country = $order->shipping_country;
            $ShippingAddress = array('shiptoname' => $shipping_first_name . ' ' . $shipping_last_name, // Required if shipping is included.  Person's name associated with this address.  32 char max.
                    'shiptostreet' => $shipping_address_1, // Required if shipping is included.  First street address.  100 char max.
                    'shiptostreet2' => $shipping_address_2, // Second street address.  100 char max.
                    'shiptocity' => wc_clean( stripslashes( $shipping_city ) ), // Required if shipping is included.  Name of city.  40 char max.
                    'shiptostate' => $shipping_state, // Required if shipping is included.  Name of state or province.  40 char max.
                    'shiptozip' => $shipping_postcode, // Required if shipping is included.  Postal code of shipping address.  20 char max.
                    'shiptocountrycode' => $shipping_country, // Required if shipping is included.  Country code of shipping address.  2 char max.
                    'shiptophonenum' => '', // Phone number for shipping address.  20 char max.
             );
            $PayPalRequestData['ShippingAddress'] = $ShippingAddress;
        }
        $PaymentData = AngellEYE_Gateway_Paypal::calculate($order, $this->send_items);
        $OrderItems = array();
        if ($this->send_items) {
            foreach ($PaymentData['order_items'] as $item) {
                $Item = array(
                    'name'      => $item['name'], // Item name. 127 char max.
                    'desc'      => '', // Item description. 127 char max.
                    'amt'       => $item['amt'], // Cost of item.
                    'number'    => $item['number'], // Item number.  127 char max.
                    'qty'       => $item['qty'], // Item qty on order.  Any positive integer.
                );
                array_push($OrderItems, $Item);
            }
            $PayPalRequestData['OrderItems'] = $OrderItems;
        }
        $PaymentDetails['taxamt']      = AngellEYE_Gateway_Paypal::number_format($PaymentData['taxamt']);       // Required if you specify itemized L_TAXAMT fields.  Sum of all tax items in this order.
        $PaymentDetails['shippingamt'] = AngellEYE_Gateway_Paypal::number_format($PaymentData['shippingamt']);      // Total shipping costs for this order.  If you specify SHIPPINGAMT you mut also specify a value for ITEMAMT.
        $PaymentDetails['itemamt']     = AngellEYE_Gateway_Paypal::number_format($PaymentData['itemamt']);      // Total shipping costs for this order.  If you specify SHIPPINGAMT you mut also specify a value for ITEMAMT.
        $PayPalRequestData['PaymentDetails'] = $PaymentDetails;
        $PayPalResult = $PayPal->DoReferenceTransaction($PayPalRequestData);
        AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($PayPalResult, $methos_name = 'DoExpressCheckoutPayment', $gateway = 'PayPal Express Checkout', $this->error_email_notify);
        $this->add_log('Test Mode: ' . $this->testmode);
        $this->add_log('Endpoint: ' . $this->API_Endpoint);
        $PayPalRequest = isset($PayPalResult['RAWREQUEST']) ? $PayPalResult['RAWREQUEST'] : '';
        $PayPalResponse = isset($PayPalResult['RAWRESPONSE']) ? $PayPalResult['RAWRESPONSE'] : '';
        $this->add_log('Request: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalRequest)), true));
        $this->add_log('Response: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalResponse)), true));
        return $PayPalResult;
    }
    
    public function add_payment_method() {
        $SECFields = array(
            'returnurl' => add_query_arg( array(
                        'do_action' => 'update_payment_method',
                        'action_name' => 'SetExpressCheckout',
                        'method_name' => 'paypal_express',
                        'customer_id' => get_current_user_id()
                ), home_url('/') ), 
            'cancelurl' => wc_get_account_endpoint_url( 'add-payment-method' ), 							
            'noshipping' => '1', 						
        );
        $Payments = array(
            'amt' => '0', 							
            'currencycode' => get_woocommerce_currency(), 					
            'paymentaction' => 'AUTHORIZATION', 					
        );
        $BillingAgreements = array();
        $Item = array(
            'l_billingtype' => 'MerchantInitiatedBilling', 							
            'l_billingagreementdescription' => 'Billing Agreement', 			
            'l_paymenttype' => 'Any', 							
            'l_billingagreementcustom' => ''					
        );
        array_push($BillingAgreements, $Item);
        $PayPalRequest = array(
            'SECFields' => $SECFields, 
            'BillingAgreements' => $BillingAgreements, 
            'Payments' => $Payments
        );
        $result = $this->paypal_express_checkout_token_request_handler( $PayPalRequest, 'SetExpressCheckout' );
        if ( $result['ACK'] == 'Success' ) {
                return array(
                        'result'   => 'success',
                        'redirect' => $this->PAYPAL_URL . $result['TOKEN']
                );
        } else {
            $redirect_url = wc_get_account_endpoint_url( 'add-payment-method' );
            $this->paypal_express_checkout_error_handler($request_name = 'SetExpressCheckout', $redirect_url, $result);
        }
    }
    
    public function paypal_express_checkout_token_request_handler($PayPalRequest = array(), $action_name = '') {
        if (!class_exists('Angelleye_PayPal')) {
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.class.php' );
        }
        $PayPalConfig = array(
            'Sandbox' => $this->testmode == 'yes' ? TRUE : FALSE,
            'APIUsername' => $this->api_username,
            'APIPassword' => $this->api_password,
            'APISignature' => $this->api_signature,
            'Force_tls_one_point_two' => $this->Force_tls_one_point_two
        );
        $PayPal = new Angelleye_PayPal($PayPalConfig);
        if( !empty($PayPalRequest) && !empty($action_name)) {
            if('SetExpressCheckout' == $action_name) {
                $PayPalResult = $PayPal->SetExpressCheckout($PayPalRequest);
                AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($PayPalResult, $methos_name = 'SetExpressCheckout', $gateway = 'PayPal Express Checkout', $this->error_email_notify);
                $this->add_log('Test Mode: ' . $this->testmode);
                $this->add_log('Endpoint: ' . $this->API_Endpoint);
                $PayPalRequest = isset($PayPalResult['RAWREQUEST']) ? $PayPalResult['RAWREQUEST'] : '';
                $PayPalResponse = isset($PayPalResult['RAWRESPONSE']) ? $PayPalResult['RAWRESPONSE'] : '';
                $this->add_log('Request: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalRequest)), true));
                $this->add_log('Response: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalResponse)), true));
                return $PayPalResult;
            }
        }
        if( !empty($_GET['method_name']) && $_GET['method_name'] == 'paypal_express') {
            if($_GET['action_name'] == 'SetExpressCheckout') {
                $PayPalResult = $PayPal->GetExpressCheckoutDetails($_GET['token']);
                if ( $PayPalResult['ACK'] == 'Success' ) {
                    $data = array(
                            'METHOD' => 'CreateBillingAgreement',
                            'TOKEN' => $_GET['token']
                    );
                    $billing_result = $PayPal->CreateBillingAgreement($_GET['token']);
                    if ( $billing_result['ACK'] == 'Success' ) {
                        if ( ! empty( $billing_result['BILLINGAGREEMENTID'] ) ) {
                            $billing_agreement_id = $billing_result['BILLINGAGREEMENTID'];
                            $token = new WC_Payment_Token_CC();
                            $customer_id = get_current_user_id();
                            $token->set_user_id( $customer_id );
                            $token->set_token( $billing_agreement_id );
                            $token->set_gateway_id( $this->id );
                            $token->set_card_type( 'PayPal Billing Agreement' );
                            $token->set_last4( substr( $billing_agreement_id, -4 ) );
                            $token->set_expiry_month( date( 'm' ) );
                            $token->set_expiry_year( date( 'Y', strtotime( '+20 year' ) ) );
                            $save_result = $token->save();
                            wp_redirect( wc_get_account_endpoint_url( 'payment-methods' ) );
                            exit();
                        }
                    }
                } else {
                   $redirect_url = wc_get_account_endpoint_url( 'add-payment-method' );
                   $this->paypal_express_checkout_error_handler($request_name = 'GetExpressCheckoutDetails', $redirect_url, $PayPalResult);
                }
            }
        }
    }
    
    public function paypal_express_checkout_error_handler($request_name = '', $redirect_url = '', $result){
        $ErrorCode = urldecode($result["L_ERRORCODE0"]);
        $ErrorShortMsg = urldecode($result["L_SHORTMESSAGE0"]);
        $ErrorLongMsg = urldecode($result["L_LONGMESSAGE0"]);
        $ErrorSeverityCode = urldecode($result["L_SEVERITYCODE0"]);
        $this->add_log(__($request_name .'API call failed. ', 'paypal-for-woocommerce'));
        $this->add_log(__('Detailed Error Message: ', 'paypal-for-woocommerce') . $ErrorLongMsg);
        $this->add_log(__('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg);
        $this->add_log(__('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode);
        $this->add_log(__('Error Severity Code: ', 'paypal-for-woocommerce') . $ErrorSeverityCode);
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
    public function angelleye_express_checkout_encrypt_gateway_api($settings) {
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