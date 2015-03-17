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
        $this->method_title       = __( 'PayPal Express Checkout ', 'paypal-for-woocommerce' );
        $this->method_description = __( 'PayPal Express Checkout is designed to make the checkout experience for buyers using PayPal much more quick and easy than filling out billing and shipping forms.  Customers will be taken directly to PayPal to sign in and authorize the payment, and are then returned back to your store to choose a shipping method, review the final order total, and complete the payment.', 'paypal-for-woocommerce' );
        $this->has_fields         = false;
        $this->supports 			= array(
            'products',
            'refunds'
        );
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
        $this->error_email_notify      = isset( $this->settings['error_email_notify'] ) && $this->settings['error_email_notify'] == 'yes' ? true : false;
        $this->invoice_id_prefix       = isset( $this->settings['invoice_id_prefix'] ) ? $this->settings['invoice_id_prefix'] : '';
        //$this->checkout_with_pp_button = $this->settings['checkout_with_pp_button'];
        //$this->hide_checkout_button    = $this->settings['hide_checkout_button'];
        $this->show_on_checkout        = isset( $this->settings['show_on_checkout'] ) ? $this->settings['show_on_checkout'] : 'both';
        $this->paypal_account_optional = isset( $this->settings['paypal_account_optional'] ) ? $this->settings['paypal_account_optional'] : '';
		$this->error_display_type 	   = isset( $this->settings['error_display_type'] ) ? $this->settings['error_display_type'] : '';
        $this->landing_page            = isset( $this->settings['landing_page'] ) ? $this->settings['landing_page'] : '';
        $this->checkout_logo           = isset( $this->settings['checkout_logo'] ) ? $this->settings['checkout_logo'] : '';
        $this->checkout_logo_hdrimg    = isset( $this->settings['checkout_logo_hdrimg'] ) ? $this->settings['checkout_logo_hdrimg'] : '';
		$this->show_paypal_credit	   = isset( $this->settings['show_paypal_credit'] ) ? $this->settings['show_paypal_credit'] : '';
		$this->brand_name	  		   = isset( $this->settings['brand_name'] ) ? $this->settings['brand_name'] : '';
		$this->customer_service_number = isset( $this->settings['customer_service_number'] ) ? $this->settings['customer_service_number'] : '';
		$this->gift_wrap_enabled	   = isset( $this->settings['gift_wrap_enabled'] ) ? $this->settings['gift_wrap_enabled'] : '';
		$this->gift_message_enabled	   = isset( $this->settings['gift_message_enabled'] ) ? $this->settings['gift_message_enabled'] : '';
		$this->gift_receipt_enabled	   = isset( $this->settings['gift_receipt_enabled'] ) ? $this->settings['gift_receipt_enabled'] : '';
		$this->gift_wrap_name		   = isset( $this->settings['gift_wrap_name'] ) ? $this->settings['gift_wrap_name'] : '';
		$this->gift_wrap_amount		   = isset( $this->settings['gift_wrap_amount'] ) ? $this->settings['gift_wrap_amount'] : '';
        $this->use_wp_locale_code      = isset( $this->settings['use_wp_locale_code'] ) ? $this->settings['use_wp_locale_code'] : '';
        $this->angelleye_skip_text     = isset( $this->settings['angelleye_skip_text'] ) ? $this->settings['angelleye_skip_text'] : '';
        $this->skip_final_review	   = isset( $this->settings['skip_final_review'] ) ? $this->settings['skip_final_review'] : '';
        $this->billing_address	       = isset( $this->settings['billing_address'] ) ? $this->settings['billing_address'] : 'no';

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

        //Save settings
        add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );


        if ( $this->enabled == 'yes' && ($this->show_on_checkout == 'top' || $this->show_on_checkout == 'both') )
            add_action( 'woocommerce_before_checkout_form', array( $this, 'checkout_message' ), 5 );
        add_action( 'woocommerce_ppe_do_payaction', array($this, 'get_confirm_order'));
        add_action( 'woocommerce_after_checkout_validation', array($this, 'regular_checkout'));
        add_action( 'woocommerce_before_cart_table', array( $this, 'top_cart_button') );
    }

    /**
     * get_icon function.
     *
     * @access public
     * @return string
     */
    public function get_icon() {

        $image_path = WP_PLUGIN_URL . "/" . plugin_basename( dirname( dirname( __FILE__ ) ) ) . '/assets/images/paypal.png';
        if ($this->show_paypal_credit=='yes') {
            $image_path = WP_PLUGIN_URL . "/" . plugin_basename( dirname( dirname( __FILE__ ) ) ) . '/assets/images/paypal-credit.png';
        }
        $icon = "<img src=\"$image_path\" alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
        return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
    }
    /**
     * Override this method so this gateway does not appear on checkout page
     *
     * @since 1.0.0
     */
    public function admin_options() { ?>

<h3><?php echo isset( $this->method_title ) ? $this->method_title : __( 'Settings', 'paypal-for-woocommerce' ) ; ?></h3>
<?php echo isset( $this->method_description ) ? wpautop( $this->method_description ) : ''; ?>
<table class="form-table">
  <?php $this->generate_settings_html(); ?>
</table>
<?php
        $this->scriptAdminOption();
    }
    public function scriptAdminOption(){
        ?>
<script type="text/javascript">
            jQuery(document).ready(function ($){
                $("#woocommerce_paypal_express_customer_service_number").attr("maxlength","16");
                if ($("#woocommerce_paypal_express_checkout_with_pp_button_type").val() == "customimage") {
                    jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_my_custom').each(function (i, el) {
                        jQuery(el).closest('tr').show();
                    });
                } else {
                    jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_my_custom').each(function (i, el) {
                        jQuery(el).closest('tr').hide();
                    });
                }
                if ($("#woocommerce_paypal_express_checkout_with_pp_button_type").val() == "textbutton") {
                    jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_text_button').each(function (i, el) {
                        jQuery(el).closest('tr').show();
                    });
                } else {
                    jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_text_button').each(function (i, el) {
                        jQuery(el).closest('tr').hide();
                    });
                }
                $("#woocommerce_paypal_express_checkout_with_pp_button_type").change(function () {
                    if ($(this).val() == "customimage") {
                        jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_my_custom').each(function (i, el) {
                            jQuery(el).closest('tr').show();
                        });
                        jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_text_button').each(function (i, el) {
                            jQuery(el).closest('tr').hide();
                        });
                    } else if($(this).val() == "textbutton") {
                        jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_text_button').each(function (i, el) {
                            jQuery(el).closest('tr').show();
                        });
                        jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_my_custom').each(function (i, el) {
                            jQuery(el).closest('tr').hide();
                        });
                    }else{
                        jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_my_custom').each(function (i, el) {
                            jQuery(el).closest('tr').hide();
                        });
                        jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_text_button').each(function (i, el) {
                            jQuery(el).closest('tr').hide();
                        });
                    }
                });
                jQuery("#woocommerce_paypal_express_pp_button_type_my_custom").css({float: "left"});
                jQuery("#woocommerce_paypal_express_pp_button_type_my_custom").after('<a href="#" id="upload" class="button_upload button">Upload</a>');
                <?php if ($this->is_ssl()) { ?>
                    jQuery("#woocommerce_paypal_express_checkout_logo").after('<a href="#" id="checkout_logo" class="button_upload button">Upload</a>');
                    jQuery("#woocommerce_paypal_express_checkout_logo_hdrimg").after('<a href="#" id="checkout_logo_hdrimg" class="button_upload button">Upload</a>');
                <?php
                    }
                ?>
                var custom_uploader;
                $('.button_upload').click(function (e) {
                    var BTthis = jQuery(this);
                    e.preventDefault();
                    //If the uploader object has already been created, reopen the dialog
                    /*if (custom_uploader) {
                        custom_uploader.open();
                        return;
                    }*/
                    //Extend the wp.media object
                    custom_uploader = wp.media.frames.file_frame = wp.media({
                        title: '<?php _e('Choose Image','paypal-for-woocommerce'); ?>',
                        button: {
                            text: '<?php _e('Choose Image','paypal-for-woocommerce'); ?>'
                        },
                        multiple: false
                    });
                    //When a file is selected, grab the URL and set it as the text field's value
                    custom_uploader.on('select', function () {
                        var attachment = custom_uploader.state().get('selection').first().toJSON();
                        var pre_input = BTthis.prev();
                        var url = attachment.url;
                        if (BTthis.attr('id')!='upload'){
                            if (attachment.url.indexOf('http:') > -1) {
                                url = url.replace('http', 'https');
                            }
                        }
                        pre_input.val(url);
                    });
                    //Open the uploader dialog
                    custom_uploader.open();
                });
            });
        </script>
<?php
    }

    public function get_confirm_order($order){
        $this->confirm_order_id = $order->id;
    }

    function is_available() {
        if ($this->enabled == 'yes' && ( $this->show_on_checkout == 'regular' || $this->show_on_checkout == 'both') ) return true;
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
     * Check if site is SSL ready
     *
     */
    function is_ssl() {
        if (is_ssl() || get_option('woocommerce_force_ssl_checkout')=='yes' || class_exists( 'WordPressHTTPS' )) return true;
        return false;
    }
    /**
     * Initialize Gateway Settings Form Fields
     */
    function init_form_fields() {

        $require_ssl = '';
        if (!$this->is_ssl()) {
            $require_ssl = __( 'This image requires an SSL host.  Please upload your image to <a target="_blank" href="http://www.sslpic.com">www.sslpic.com</a> and enter the image URL here.', 'paypal-for-woocommerce' );
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
        foreach($pages as $p){
            $cancel_page[$p->ID] = $p->post_title;
        }
        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Enable/Disable', 'paypal-for-woocommerce' ),
                'label' => __( 'Enable PayPal Express', 'paypal-for-woocommerce' ),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'title' => array(
                'title' => __( 'Title', 'paypal-for-woocommerce' ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'paypal-for-woocommerce' ),
                'default' => __( 'PayPal Express', 'paypal-for-woocommerce' )
            ),
            'description' => array(
                'title' => __( 'Description', 'paypal-for-woocommerce' ),
                'type' => 'textarea',
                'description' => __( 'This controls the description which the user sees during checkout.', 'paypal-for-woocommerce' ),
                'default' => __( "Pay via PayPal; you can pay with your credit card if you don't have a PayPal account", 'paypal-for-woocommerce' )
            ),
            'sandbox_api_username' => array(
                'title' => __( 'Sandbox API User Name', 'paypal-for-woocommerce' ),
                'type' => 'text',
                'description' => __( 'Create sandbox accounts and obtain API credentials from within your <a href="http://developer.paypal.com">PayPal developer account</a>.', 'paypal-for-woocommerce' ),
                'default' => ''
            ),
            'sandbox_api_password' => array(
                'title' => __( 'Sandbox API Password', 'paypal-for-woocommerce' ),
                'type' => 'password',
                'default' => ''
            ),
            'sandbox_api_signature' => array(
                'title' => __( 'Sandbox API Signature', 'paypal-for-woocommerce' ),
                'type' => 'password',
                'default' => ''
            ),
            'api_username' => array(
                'title' => __( 'Live API User Name', 'paypal-for-woocommerce' ),
                'type' => 'text',
                'description' => __( 'Get your live account API credentials from your PayPal account profile under the API Access section <br />or by using <a target="_blank" href="https://www.paypal.com/us/cgi-bin/webscr?cmd=_login-api-run">this tool</a>.', 'paypal-for-woocommerce' ),
                'default' => ''
            ),
            'api_password' => array(
                'title' => __( 'Live API Password', 'paypal-for-woocommerce' ),
                'type' => 'password',
                'default' => ''
            ),
            'api_signature' => array(
                'title' => __( 'Live API Signature', 'paypal-for-woocommerce' ),
                'type' => 'password',
                'default' => ''
            ),
            'testmode' => array(
                'title' => __( 'PayPal Sandbox', 'paypal-for-woocommerce' ),
                'type' => 'checkbox',
                'label' => __( 'Enable PayPal Sandbox', 'paypal-for-woocommerce' ),
                'default' => 'yes',
				'description' => __( 'The sandbox is PayPal\'s test environment and is only for use with sandbox accounts created within your <a href="http://developer.paypal.com" target="_blank">PayPal developer account</a>.','paypal-for-woocommerce')
            ),
            'debug' => array(
                'title' => __( 'Debug', 'paypal-for-woocommerce' ),
                'type' => 'checkbox',
                'label' => __( 'Enable logging <code>/wc-logs/paypal_express.log</code>', 'paypal-for-woocommerce' ),
                'default' => 'no'
            ),
			'error_email_notify' => array(
                'title' => __( 'Error Email Notifications', 'paypal-for-woocommerce' ),
                'type' => 'checkbox',
                'label' => __( 'Enable admin email notifications for errors.', 'paypal-for-woocommerce' ),
                'default' => 'yes',
				'description' => __( 'This will send a detailed error email to the WordPress site administrator if a PayPal API error occurs.','paypal-for-woocommerce' )
            ),
            'invoice_id_prefix'           => array(
                'title'       => __( 'Invoice ID Prefix', 'paypal-for-woocommerce' ),
                'type'        => 'text',
                'description' => __( 'Add a prefix to the invoice ID sent to PayPal. This can resolve duplicate invoice problems when working with multiple websites on the same PayPal account.', 'paypal-for-woocommerce' ),
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
                'title' => __( 'Checkout Button Type', 'paypal-for-woocommerce' ),
                'type' => 'select',
                'label' => __( 'Use Checkout with PayPal image button', 'paypal-for-woocommerce' ),
                'class' => 'checkout_with_pp_button_type',
                'options' => array(
                    'paypalimage' => __('PayPal Image','paypal-for-woocommerce'),
                    'textbutton' => __('Text Button','paypal-for-woocommerce'),
                    'customimage' => __('Custom Image','paypal-for-woocommerce')
                ) // array of options for select/multiselects only
            ),
            'pp_button_type_my_custom' => array(
                'title' => __( 'Select Image', 'paypal-for-woocommerce' ),
                'type' => 'text',
                'label' => __( 'Use Checkout with PayPal image button', 'paypal-for-woocommerce' ),
                'class' => 'pp_button_type_my_custom',
            ),
            'pp_button_type_text_button' => array(
                'title' => __( 'Custom Text', 'paypal-for-woocommerce' ),
                'type' => 'text',
                'class' => 'pp_button_type_text_button',
                'default' => 'Proceed to Checkout',
            ),
            /*'hide_checkout_button' => array(
                'title' => __( 'Standard Checkout Button', 'paypal-for-woocommerce' ),
                'type' => 'checkbox',
                'label' => __( 'Hide standard checkout button on cart page', 'paypal-for-woocommerce' ),
                'default' => 'no'
            ),*/
            'show_on_cart' => array(
                'title' => __( 'Cart Page', 'paypal-for-woocommerce' ),
                'label' => __( 'Show Express Checkout button on shopping cart page.', 'paypal-for-woocommerce' ),
                'type' => 'checkbox',
                'default' => 'yes'
            ),
            'button_position' => array(
                'title' => __( 'Cart Button Position', 'paypal-for-woocommerce' ),
                'label' => __( 'Where to display PayPal Express Checkout button(s).', 'paypal-for-woocommerce' ),
                'description' => __( 'Set where to display the PayPal Express Checkout button(s).' ),
                'type' => 'select',
                'options' => array(
                    'top' => 'At the top, above the shopping cart details.',
                    'bottom' => 'At the bottom, below the shopping cart details.',
                    'both' => 'Both at the top and bottom, above and below the shopping cart details.'
                ),
                'default' => 'bottom'
            ),
            'show_on_checkout' => array(
                'title' => __( 'Checkout Page Display', 'paypal-for-woocommerce' ),
                'type' => 'select',
                'options' => array(
                            'no' => __( "Do not display on checkout page." , 'paypal-for-woocommerce' ),
                            'top' => __( 'Display at the top of the checkout page.' , 'paypal-for-woocommerce' ) ,
                            'regular' => __( 'Display in general list of enabled gatways on checkout page.' , 'paypal-for-woocommerce' ) , 
							'both' => __( 'Display both at the top and in the general list of gateways on the checkout page.' ) ) ,
                'default' => 'top',
				'description' => __( 'Displaying the checkout button at the top of the checkout page will allow users to skip filling out the forms and can potentially increase conversion rates.' )
            ),
            'show_on_product_page' => array(
                'title' => __( 'Product Page', 'paypal-for-woocommerce' ),
                'type' => 'checkbox',
                'label' => __( 'Show the Express Checkout button on product detail pages.', 'paypal-for-woocommerce' ),
                'default' => 'no',
				'description' => __('Allows customers to checkout using PayPal directly from a product page.','paypal-for-woocommerce')
            ),
            'paypal_account_optional' => array(
                'title' => __( 'PayPal Account Optional', 'paypal-for-woocommerce' ),
                'type' => 'checkbox',
                'label' => __( 'Allow customers to checkout without a PayPal account using their credit card.', 'paypal-for-woocommerce' ),
                'default' => 'no',
				'description' => __('PayPal Account Optional must be turned on in your PayPal account profile under Website Preferences.','paypal-for-woocommerce')
            ),
			'landing_page' => array(
                'title' => __( 'Landing Page', 'paypal-for-woocommerce' ),
                'type' => 'select',
                'description' => __( 'Type of PayPal page to display as default. PayPal Account Optional must be checked for this option to be used.', 'paypal-for-woocommerce'  ),
                'options' => array('login' => __( 'Login' , 'paypal-for-woocommerce' ),
                    'billing' => __( 'Billing' , 'paypal-for-woocommerce' ) ),
                'default' => 'login',
            ),
			'error_display_type' => array(
                'title' => __( 'Error Display Type', 'paypal-for-woocommerce' ),
                'type' => 'select',
                'label' => __( 'Display detailed or generic errors', 'paypal-for-woocommerce' ),
                'class' => 'error_display_type_option',
                'options' => array(
                    'detailed' => __( 'Detailed' , 'paypal-for-woocommerce' ),
                    'generic' => __( 'Generic' , 'paypal-for-woocommerce' )
                ),
				'description' => __( 'Detailed displays actual errors returned from PayPal.  Generic displays general errors that do not reveal details
									and helps to prevent fraudulant activity on your site.' , 'paypal-for-woocommerce' )
            ),
			'show_paypal_credit' => array(
					'title' => __( 'Enable PayPal Credit', 'paypal-for-woocommerce' ),
					'type' => 'checkbox',
					'label' => __( 'Show the PayPal Credit button next to the Express Checkout button.', 'paypal-for-woocommerce' ),
					'default' => 'yes'
				),
            'use_wp_locale_code' => array(
                'title' => __( 'Use WordPress Locale Code', 'paypal-for-woocommerce' ),
                'type' => 'checkbox',
                'label' => __( 'Pass the WordPress Locale Code setting to PayPal in order to display localized PayPal pages to buyers.', 'paypal-for-woocommerce' ),
                'default' => 'yes'
            ),
            'brand_name' => array(
                'title' => __( 'Brand Name', 'paypal-for-woocommerce' ),
                'type' => 'text',
                'description' => __( 'This controls what users see as the brand / company name on PayPal review pages.', 'paypal-for-woocommerce' ),
                'default' => __( get_bloginfo('name'), 'paypal-for-woocommerce' )
            ),
            'checkout_logo' => array(
                'title' => __('PayPal Checkout Logo (190x90px)', 'paypal-for-woocommerce'),
                'type'  => 'text',
                'description' => __( 'This controls what users see as the logo on PayPal review pages. ', 'paypal-for-woocommerce' ).$require_ssl,
                'default'   => ''
            ),
            'checkout_logo_hdrimg' => array(
                'title' => __('PayPal Checkout Banner (750x90px)', 'paypal-for-woocommerce'),
                'type'  => 'text',
                'description' => __( 'This controls what users see as the header banner on PayPal review pages. ', 'paypal-for-woocommerce' ).$require_ssl,
                'default'   => ''
            ),
			'customer_service_number' => array(
                'title' => __( 'Customer Service Number', 'paypal-for-woocommerce' ),
                'type' => 'text',
                'description' => __( 'This controls what users see for your customer service phone number on PayPal review pages.', 'paypal-for-woocommerce' ),
                'default' => ''
            ),
			'gift_wrap_enabled' => array(
                'title' => __( 'Gift Wrap', 'paypal-for-woocommerce' ),
                'type' => 'checkbox',
                'label' => __( 'Enables the gift wrap options for buyers.', 'paypal-for-woocommerce' ),
                'default' => 'no',
				'description' => __( 'This will display a gift wrap option to buyers during checkout based on the following Gift Wrap settings.' , 'paypal-for-woocommerce' )
            ),
			'gift_message_enabled' => array(
                'title' => __( 'Gift Message', 'paypal-for-woocommerce' ),
                'type' => 'checkbox',
                'label' => __( 'Enables the gift message widget on PayPal pages.', 'paypal-for-woocommerce' ),
                'default' => 'no',
				'description' => __( 'This will allow buyers to enter a message they would like to include with the item as a gift.' , 'paypal-for-woocommerce' )
            ),
			'gift_receipt_enabled' => array(
                'title' => __( 'Gift Receipt', 'paypal-for-woocommerce' ),
                'type' => 'checkbox',
                'label' => __( 'Enables the gift receipt widget on PayPal pages.', 'paypal-for-woocommerce' ),
                'default' => 'no',
				'description' => __( 'This will allow buyers to choose whether or not to include a gift receipt in the order.' , 'paypal-for-woocommerce' )
            ),
			'gift_wrap_name' => array(
                'title' => __( 'Gift Wrap Name', 'paypal-for-woocommerce' ),
                'type' => 'text',
                'description' => __( 'Label for the gift wrap option on PayPal pages, such as "Box with ribbon"', 'paypal-for-woocommerce' ),
                'default' => __('Box with ribbon', 'paypal-for-woocommerce' )
            ),
			'gift_wrap_amount' => array(
                'title' => __( 'Gift Wrap Amount', 'paypal-for-woocommerce' ),
                'type' => 'text',
                'description' => __( 'Amount to be charged to the buyer for adding the gift wrap option.', 'paypal-for-woocommerce' ),
                'default' => __('0.00', 'paypal-for-woocommerce' )
            ),
            'angelleye_skip_text' => array(
                'title' => __( 'Express Checkout Message', 'paypal-for-woocommerce' ),
                'type' => 'text',
                'description' => __( 'This message will be displayed next to the PayPal Express Checkout button at the top of the checkout page.' ),
                'default' => __('Skip the forms and pay faster with PayPal!', 'paypal-for-woocommerce' )
            ),
            'skip_final_review' => array(
                'title' => __( 'Skip Final Review', 'paypal-for-woocommerce' ),
                'label' => __( 'Enables the option to skip the final review page.', 'paypal-for-woocommerce' ),
                'description' => __( 'By default, users will be returned from PayPal and presented with a final review page which includes shipping and tax in the order details.  Enable this option to eliminate this page in the checkout process.' ),
                'type' => 'checkbox',
                'default' => 'no'
            ),
            'billing_address' => array(
                'title' => __( 'Billing Address', 'paypal-for-woocommerce' ),
                'label' => __( 'Set billing address in WooCommerce using the address returned by PayPal.', 'paypal-for-woocommerce' ),
                'description' => __( 'PayPal only returns a shipping address back to the website.  Enable this option if you would like to use this address for both billing and shipping in WooCommerce.' ),
                'type' => 'checkbox',
                'default' => 'no'
            ),
            'cancel_page' => array(
                'title' => __( 'Cancel Page', 'paypal-for-woocommerce' ),
                'description' => __( 'Sets the page users will be returned to if they click the Cancel link on the PayPal checkout pages.' ),
                'type' => 'select',
                'options' => $cancel_page,
            ),
            /*'Locale' => array(
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
            )*/
        );
        $this->form_fields = apply_filters( 'angelleye_ec_form_fields', $this->form_fields );
    }

    /**
     *  Checkout Message
     */
    function checkout_message()
	{
        global $pp_settings;

        if(WC()->cart->total > 0) {
            wp_enqueue_script('angelleye_button');
                $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
                // Pay with Credit Card

                unset($payment_gateways['paypal_pro']);
                unset($payment_gateways['paypal_pro_payflow']);
                echo '<div id="checkout_paypal_message" class="woocommerce-info info">';
                echo '<div id="paypal_box_button">';
                if (empty($pp_settings['checkout_with_pp_button_type'])) $pp_settings['checkout_with_pp_button_type'] = 'paypalimage';
                switch ($pp_settings['checkout_with_pp_button_type']) {
                    case "textbutton":
                        if (empty($payment_gateways)) {
                            $button_text = __('Proceed to Checkout', 'woocommerce');
                        } else {
                            $button_text = __('Pay with PayPal', 'paypal-for-woocommerce');
                        }
                        echo '<a class="paypal_checkout_button paypal_checkout_button_text button alt" href="' . add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/'))) . '">' . $button_text . '</a>';
                        break;
                    case "paypalimage":
                        echo '<div id="paypal_ec_button">';
                        echo '<a class="paypal_checkout_button" href="' . add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/'))) . '">';
                        echo "<img src='https://www.paypal.com/" . WC_Gateway_PayPal_Express_AngellEYE::get_button_locale_code() . "/i/btn/btn_xpressCheckout.gif' border='0' alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
                        echo "</a>";
                        echo '</div>';
                        break;
                    case "customimage":
                        $button_img = $pp_settings['pp_button_type_my_custom'];
                        echo '<div id="paypal_ec_button">';
                        echo '<a class="paypal_checkout_button" href="' . add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/'))) . '">';
                        echo "<img src='{$button_img}' width='150' border='0' alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
                        echo "</a>";
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
                    $paypal_credit_button_markup .= '<a class="paypal_checkout_button" href="' . add_query_arg('use_paypal_credit', 'true', add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '" >';
                    $paypal_credit_button_markup .= "<img src='https://www.paypalobjects.com/webstatic/en_US/i/buttons/ppcredit-logo-small.png' alt='Check out with PayPal Credit'/>";
                    $paypal_credit_button_markup .= '</a>';
                    $paypal_credit_button_markup .= '</div>';

                    echo $paypal_credit_button_markup;
                }

                echo '<div class="woocommerce_paypal_ec_checkout_message">';
                if( !isset($this->settings['angelleye_skip_text'])){
                    echo '<p class="checkoutStatus">', __('Skip the forms and pay faster with PayPal!', 'paypal-for-woocommerce' ), '</p>';
                }else{
                    echo '<p class="checkoutStatus">', $this->angelleye_skip_text, '</p>';
                }
                echo '</div>';
                echo '<div class="clear"></div></div>';
                ?>
                <div class="blockUI blockOverlay angelleyeOverlay" style="display:none;z-index: 1000; border: none; margin: 0px; padding: 0px; width: 100%; height: 100%; top: 0px; left: 0px; opacity: 0.6; cursor: default; position: absolute; background: url(<?php echo WC()->plugin_url(); ?>/assets/images/ajax-loader@2x.gif) 50% 50% / 16px 16px no-repeat rgb(255, 255, 255);"></div>
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
        if ( !empty($posted) || ( isset( $_GET['pp_action'] ) && $_GET['pp_action'] == 'expresscheckout' ) ) {
            if ( sizeof( WC()->cart->get_cart() ) > 0 ) {

                // The customer has initiated the Express Checkout process with the button on the cart page
                if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) )
                    define( 'WOOCOMMERCE_CHECKOUT', true );
                $this->add_log( 'Start Express Checkout' );

                /**
                 * Check if the EC button used was the PayPal Credit button.
                 * This $usePayPalCredit flag will be used to adjust the SEC request accordingly.
                 */
                if(isset($_GET['use_paypal_credit']) && 'true' == $_GET['use_paypal_credit'])
                {
                    $usePayPalCredit = true;
                }
                else
                {
                    $usePayPalCredit = false;
                }

                WC()->cart->calculate_totals();
                //$paymentAmount    = WC()->cart->get_total();
                $paymentAmount	  = number_format(WC()->cart->total,2,'.','');

                //Check if review order page is exist, otherwise re-create it on the fly
                $review_order_page_url =  get_permalink( wc_get_page_id( 'review_order' ) );
                if (!$review_order_page_url) {
                    $this->add_log( __( 'Review Order Page not found, re-create it. ' , 'paypal-for-woocommerce') );
                    include_once( WC()->plugin_path() . '/includes/admin/wc-admin-functions.php' );
                    $page_id = wc_create_page(esc_sql(_x('review-order','page_slug','woocommerce')),'woocommerce_review_order_page_id',__('Checkout &rarr; Review Order','paypal-for-woocommerce'),'[woocommerce_review_order]',wc_get_page_id('checkout'));
                    $review_order_page_url = get_permalink( $page_id );
                }
                $returnURL        = urlencode( add_query_arg( 'pp_action', 'revieworder', $review_order_page_url ) );
                $cancelURL        = isset( $this->settings['cancel_page'] ) ? get_the_permalink( $this->settings['cancel_page'] ) : WC()->cart->get_cart_url();
                $cancelURL        = apply_filters( 'angelleye_express_cancel_url', urlencode( $cancelURL ) );
                $resArray         = $this->CallSetExpressCheckout( $paymentAmount, $returnURL, $cancelURL, $usePayPalCredit, $posted );
                $ack              = strtoupper( $resArray["ACK"] );

                /**
                 * I've replaced the original redirect URL's here with
                 * what the PayPal class library returns so that options like
                 * "skip details" will work correctly with PayPal's review pages.
                 */
                if($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING")
                {
                    $this->add_log( 'Redirecting to PayPal' );
                    if ( is_ajax() ) {
                        $result = array (
                            //'redirect' => $this->PAYPAL_URL . $resArray["TOKEN"],
                            'redirect' => $resArray['REDIRECTURL'],
                            'result'   => 'success'
                        );

                        echo '<!--WC_START-->' . json_encode( $result ) . '<!--WC_END-->';
                        exit;
                    } else {
                        //$this->RedirectToPayPal( $resArray["TOKEN"] );
                        wp_redirect($resArray['REDIRECTURL']);
                        exit;
                    }
                }
                else
                {
                    // Display a user friendly Error on the page and log details
                    $ErrorCode         = urldecode( $resArray["L_ERRORCODE0"] );
                    $ErrorShortMsg     = urldecode( $resArray["L_SHORTMESSAGE0"] );
                    $ErrorLongMsg      = urldecode( $resArray["L_LONGMESSAGE0"] );
                    $ErrorSeverityCode = urldecode( $resArray["L_SEVERITYCODE0"] );
                    $this->add_log( __( 'SetExpressCheckout API call failed. ' , 'paypal-for-woocommerce') );
                    $this->add_log( __('Detailed Error Message: ' , 'paypal-for-woocommerce' ) . $ErrorLongMsg );
                    $this->add_log( __( 'Short Error Message: ' , 'paypal-for-woocommerce') . $ErrorShortMsg );
                    $this->add_log( __( 'Error Code: ' , 'paypal-for-woocommerce' ) . $ErrorCode );
                    $this->add_log( __( 'Error Severity Code: ' , 'paypal-for-woocommerce' ) . $ErrorSeverityCode );

                    // Notice admin if has any issue from PayPal
                    $message = '';
                    if($this->error_email_notify)
                    {
                        $admin_email = get_option("admin_email");
                        $message .= __( "SetExpressCheckout API call failed." , "paypal-for-woocommerce" )."\n\n";
                        $message .= __( 'Error Code: ' ,'paypal-for-woocommerce' ) . $ErrorCode."\n";
                        $message .= __( 'Error Severity Code: ' , 'paypal-for-woocommerce' ) . $ErrorSeverityCode."\n";
                        $message .= __( 'Short Error Message: ' , 'paypal-for-woocommerce' ) . $ErrorShortMsg ."\n";
                        $message .= __( 'Detailed Error Message: ' , 'paypal-for-woocommerce') . $ErrorLongMsg ."\n";

                        $error_email_notify_mes = apply_filters( 'angelleye_ec_error_email_notify_message', $message, $ErrorCode, $ErrorSeverityCode, $ErrorShortMsg, $ErrorLongMsg );
                        $subject = "PayPal Express Checkout Error Notification";
                        $error_email_notify_subject = apply_filters( 'angelleye_ec_error_email_notify_subject', $subject );

                        wp_mail($admin_email, $error_email_notify_subject, $error_email_notify_mes);
                    }

                    // Generate error message based on Error Display Type setting
                    if($this->error_display_type == 'detailed')
                    {
                        $sec_error_notice = $ErrorCode.' - '.$ErrorLongMsg;
                        $error_display_type_message =   sprintf( __($sec_error_notice, 'paypal-for-woocommerce' ) );
                    }
                    else
                    {
                        $error_display_type_message = sprintf( __('There was a problem paying with PayPal.  Please try another method.', 'paypal-for-woocommerce' ) );
                    }
                    $error_display_type_message = apply_filters( 'angelleye_ec_display_type_message', $error_display_type_message, $ErrorCode, $ErrorLongMsg );
                    wc_add_notice( $error_display_type_message , 'error' );
                    if ( !is_ajax() ) {
                        wp_redirect( get_permalink( wc_get_page_id( 'cart' ) ) );
                        exit;
                    } else return;
                }
            }
        }
		elseif ( isset( $_GET['pp_action'] ) && $_GET['pp_action'] == 'revieworder' )
		{
            wc_clear_notices();
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

			$result = $this->CallGetShippingDetails($this->get_session('TOKEN'));

            if(!empty($result))
			{
                $this->set_session('RESULT',serialize($result));
                if ( isset( $result['SHIPTOCOUNTRYCODE'] ) ) {
                    /**
                     * Check if shiptocountry is in the allowed countries list
                     */
                    if (!array_key_exists($result['SHIPTOCOUNTRYCODE'], WC()->countries->get_allowed_countries())) {
                        wc_add_notice(  sprintf( __('We do not sell in your country, please try again with another address.', 'paypal-for-woocommerce' ) ), 'error' );
                        wp_redirect( get_permalink( wc_get_page_id( 'cart' ) ) );
                        exit;
                    };
                    WC()->customer->set_shipping_country( $result['SHIPTOCOUNTRYCODE'] );
                }
                if ( isset( $result['SHIPTONAME'] ) ) WC()->customer->shiptoname =  $result['SHIPTONAME'] ;
                if ( isset( $result['SHIPTOSTREET'] ) ) WC()->customer->set_address( $result['SHIPTOSTREET'] );
                if ( isset( $result['SHIPTOCITY'] ) ) WC()->customer->set_city( $result['SHIPTOCITY'] );
                if ( isset( $result['SHIPTOCOUNTRYCODE'] ) ) WC()->customer->set_country( $result['SHIPTOCOUNTRYCODE'] );
                if ( isset( $result['SHIPTOSTATE'] ) ) WC()->customer->set_state( $this->get_state_code( $result['SHIPTOCOUNTRYCODE'], $result['SHIPTOSTATE'] ) );
                if ( isset( $result['SHIPTOZIP'] ) ) WC()->customer->set_postcode( $result['SHIPTOZIP'] );
                if ( isset( $result['SHIPTOSTATE'] ) ) WC()->customer->set_shipping_state( $this->get_state_code( $result['SHIPTOCOUNTRYCODE'], $result['SHIPTOSTATE'] ) );
                if ( isset( $result['SHIPTOZIP'] ) ) WC()->customer->set_shipping_postcode( $result['SHIPTOZIP'] );

				/**
				 * Save GECD data in sessions for use in DECP
				 */
				$this->set_session('firstname',isset($result['FIRSTNAME']) ? $result['FIRSTNAME'] : '');
				$this->set_session('lastname',isset($result['LASTNAME']) ? $result['LASTNAME'] : '');
				$this->set_session('shiptoname',isset($result['SHIPTONAME']) ? $result['SHIPTONAME'] : '');
				$this->set_session('shiptostreet',isset($result['SHIPTOSTREET']) ? $result['SHIPTOSTREET'] : '');
				$this->set_session('shiptostreet2',isset($result['SHIPTOSTREET2']) ? $result['SHIPTOSTREET2'] : '');
				$this->set_session('shiptocity',isset($result['SHIPTOCITY']) ? $result['SHIPTOCITY'] : '');
				$this->set_session('shiptocountrycode',isset($result['SHIPTOCOUNTRYCODE']) ? $result['SHIPTOCOUNTRYCODE'] : '');
				$this->set_session('shiptostate',isset($result['SHIPTOSTATE']) ? $result['SHIPTOSTATE'] : '');
				$this->set_session('shiptozip',isset($result['SHIPTOZIP']) ? $result['SHIPTOZIP'] : '');
				$this->set_session('payeremail',isset($result['EMAIL']) ? $result['EMAIL'] : '');
				$this->set_session('giftmessage',isset($result['GIFTMESSAGE']) ? $result['GIFTMESSAGE'] : '');
				$this->set_session('giftreceiptenable',isset($result['GIFTRECEIPTENABLE']) ? $result['GIFTRECEIPTENABLE'] : '');
				$this->set_session('giftwrapname',isset($result['GIFTWRAPNAME']) ? $result['GIFTWRAPNAME'] : '');
				$this->set_session('giftwrapamount',isset($result['GIFTWRAPAMOUNT']) ? $result['GIFTWRAPAMOUNT'] : '');
				$this->set_session('customer_notes',isset($result['PAYMENTREQUEST_0_NOTETEXT']) ? $result['PAYMENTREQUEST_0_NOTETEXT'] : '');
                $this->set_session('phonenum',isset($result['PHONENUM']) ? $result['PHONENUM'] : '');
                WC()->cart->calculate_totals();

            }
			else
			{
                $this->add_log( "...ERROR: GetShippingDetails returned empty result" );
            }
            if($this->skip_final_review == 'yes'){
                $url = add_query_arg( array('wc-api'=>'WC_Gateway_PayPal_Express_AngellEYE', 'pp_action' => 'payaction' ), home_url());
                wp_redirect($url);
                exit();
            }

            if(isset($_POST['createaccount'])){
                if(empty($_POST['username'])){
                    wc_add_notice(__('Username is required', 'paypal-for-woocommerce'), 'error');
                }elseif(username_exists( $_POST['username'] )){
                    wc_add_notice(__('This username is already registered.', 'paypal-for-woocommerce'), 'error');
                }elseif(empty($_POST['email'])){
                    wc_add_notice(__('Please provide a valid email address.', 'paypal-for-woocommerce'), 'error');
                }elseif(empty($_POST['password']) || empty($_POST['repassword'])){
                    wc_add_notice(__('Password is required.', 'paypal-for-woocommerce'), 'error');
                }elseif($_POST['password'] != $_POST['repassword']){
                    wc_add_notice(__('Passwords do not match.', 'paypal-for-woocommerce'), 'error');
                }elseif(get_user_by( 'email',  $_POST['email'])!=false){
                    wc_add_notice(__('This email address is already registered.', 'paypal-for-woocommerce'), 'error');
                }else{
                    $data  = array(
                        'user_login' => addslashes( $_POST['username'] ),
                        'user_email' => addslashes( $_POST['email'] ),
                        'user_pass' => addslashes( $_POST['password'] ),
                    );
                    $userID = wp_insert_user($data);
                    if( !is_wp_error($userID) ) {
                        update_user_meta( $userID, 'billing_first_name',  $result['FIRSTNAME'] );
                        update_user_meta( $userID, 'billing_last_name',   $result['LASTNAME'] );
                        update_user_meta( $userID, 'billing_address_1',  $result['SHIPTOSTREET'] );
                        update_user_meta( $userID, 'billing_state',   $result['SHIPTOSTATE'] );
                        update_user_meta( $userID, 'billing_email',   $result['EMAIL'] );
                        /* USER SIGON */
                        $user_login     = esc_attr($_POST["username"]);
                        $user_password  = esc_attr($_POST["password"]);
                        $user_email     = esc_attr($_POST["email"]);
                        $creds = array(
                            'user_login' => $user_login,
                            'user_password' => $user_password,
                            'remember' => true,
                        );

                        $user = wp_signon( $creds, false );
                        if ( is_wp_error($user) )
                            wc_add_notice($user->get_error_message(), 'error');
                        else
                        {
                            wp_set_current_user($user->ID); //Here is where we update the global user variables
                            header("Refresh:0");
                            die();
                        }
                    }

                }
            }
        }
		elseif ( isset( $_GET['pp_action'] ) && $_GET['pp_action'] == 'payaction' )
		{
            if ( isset( $_POST ) || $this->skip_final_review == 'yes' )
			{
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
                    $order_id = WC()->checkout()->create_order();

				/**
				 * Update meta data with session data
				 */

                // Parse SHIPTONAME to fist and last name
                require_once("lib/NameParser.php");
                $parser = new FullNameParser();
                $split_name = $parser->split_full_name($this->get_session('shiptoname'));
                $shipping_first_name    = $split_name['fname'];
                $shipping_last_name     = $split_name['lname'];
                $full_name              = $split_name['fullname'];

				update_post_meta( $order_id, '_payment_method',   $this->id );
				update_post_meta( $order_id, '_payment_method_title',  $this->title );
                if( is_user_logged_in() ){
                    $userLogined = get_current_user();
                    update_post_meta($order_id, '_billing_email', $userLogined->user_email);
                }else {
                    update_post_meta($order_id, '_billing_email', $this->get_session('payeremail'));
                }
				update_post_meta( $order_id, '_shipping_first_name',  $shipping_first_name );
				update_post_meta( $order_id, '_shipping_last_name',  $shipping_last_name );
				update_post_meta( $order_id, '_shipping_full_name',  $full_name );
				update_post_meta( $order_id, '_shipping_company',   "" );
                update_post_meta( $order_id, '_billing_phone',   $this->get_session('phonenum'));
				update_post_meta( $order_id, '_shipping_address_1',  $this->get_session('shiptostreet'));
				update_post_meta( $order_id, '_shipping_address_2',  $this->get_session('shiptostreet2'));
				update_post_meta( $order_id, '_shipping_city',    $this->get_session('shiptocity'));
				update_post_meta( $order_id, '_shipping_postcode',   $this->get_session('shiptozip'));
				update_post_meta( $order_id, '_shipping_country',   $this->get_session('shiptocountrycode'));
				update_post_meta( $order_id, '_shipping_state',   $this->get_state_code( $this->get_session('shiptocountrycode'), $this->get_session('shiptostate')));
                update_post_meta( $order_id, '_customer_user', 	get_current_user_id() );

                if( $this->billing_address == 'yes'){
                    update_post_meta( $order_id, '_billing_first_name',  $shipping_first_name );
                    update_post_meta( $order_id, '_billing_last_name',  $shipping_last_name );
                    update_post_meta( $order_id, '_billing_full_name',  $full_name );
                    update_post_meta( $order_id, '_billing_company',   "" );
                    update_post_meta( $order_id, '_billing_address_1',  $this->get_session('shiptostreet'));
                    update_post_meta( $order_id, '_billing_address_2',  $this->get_session('shiptostreet2'));
                    update_post_meta( $order_id, '_billing_city',    $this->get_session('shiptocity'));
                    update_post_meta( $order_id, '_billing_postcode',   $this->get_session('shiptozip'));
                    update_post_meta( $order_id, '_billing_country',   $this->get_session('shiptocountrycode'));
                    update_post_meta( $order_id, '_billing_state',   $this->get_state_code( $this->get_session('shiptocountrycode'), $this->get_session('shiptostate')));
                }

                $this->add_log( '...Order ID: ' . $order_id );
                $order = new WC_Order( $order_id );
                do_action( 'woocommerce_ppe_do_payaction', $order );
                $this->add_log( '...Order Total: ' . $order->order_total );
                $this->add_log( '...Cart Total: '.WC()->cart->get_total() );
                $this->add_log( "...Token:" . $this->get_session( 'TOKEN' ) );
                $result = $this->ConfirmPayment( $order->order_total );

                // Set Customer Name
                if( !get_current_user_id() ){
                    update_post_meta( $order_id, '_billing_first_name',  $this->get_session('firstname') );
                    update_post_meta( $order_id, '_billing_last_name',  $this->get_session('lastname'));
                }

				/**
				 * Customer Notes
				 */
				if($this->get_session('customer_notes') != '')
				{
					$order->add_order_note(__( 'Customer Notes: ' , 'paypal-for-woocommerce' ).$this->get_session('customer_notes'));
				}

				if($result['ACK'] == 'Success' || $result['ACK'] == 'SuccessWithWarning')
				{
                    $this->add_log( 'Payment confirmed with PayPal successfully' );
                    $result = apply_filters( 'woocommerce_payment_successful_result', $result );

					/**
					 * Gift Wrap Notes
					 */
					if ($this->get_session('giftwrapamount')!='')
					{
						$giftwrap_note = __( 'Gift Wrap Added' , 'paypal-for-woocommerce');
						$giftwrap_note .= $this->get_session('giftwrapname') != '' ? ' - ' . $this->get_session('giftwrapname') : '';
						$giftwrap_note .= $this->get_session('giftmessage') != '' ? '<br />Message: ' . $this->get_session('giftmessage') : '';
						$giftwrap_note .= '<br />'.__( 'Use Gift Receipt?: ' , 'paypal-for-woocommerce' );
						$giftwrap_note .= strtolower($this->get_session('giftreceiptenable')) == 'true' ? 'Yes' : 'No';
						//$giftwrap_note .= '<br />Fee: ' . woocommerce_price(number_format($this->get_session('giftwrapamount'),2));
                        $order->add_order_note($giftwrap_note);
                    }

                    $order->add_order_note( __( 'PayPal Express payment completed', 'paypal-for-woocommerce' ) .
                        ' ( Response Code: ' . $result['ACK'] . ", " .
                        ' TransactionID: '.$result['PAYMENTINFO_0_TRANSACTIONID'] . ' )' );
                    $REVIEW_RESULT = unserialize( $this->get_session('RESULT') );
                    $payerstatus_note = __( 'Payer Status: ' , 'paypal-for-woocommerce' );
                    $payerstatus_note .= ucfirst( $REVIEW_RESULT['PAYERSTATUS'] );
                    $order->add_order_note($payerstatus_note);
                    $addressstatus_note = __( 'Address Status: ' , 'paypal-for-woocommerce' );
                    $addressstatus_note .= ucfirst( $REVIEW_RESULT['ADDRESSSTATUS'] );
                    $order->add_order_note($addressstatus_note);
                    $order->payment_complete($result['PAYMENTINFO_0_TRANSACTIONID']);

                    //add hook
                    do_action( 'woocommerce_checkout_order_processed', $order_id );

                    // Empty the Cart
                    WC()->cart->empty_cart();

					wp_redirect( $this->get_return_url( $order ) );
					exit();
                }
				else
				{
                    $this->add_log( '...Error confirming order '.$order_id.' with PayPal' );
                    $this->add_log( '...response:'.print_r( $result, true ) );

					// Display a user friendly Error on the page and log details
                    $ErrorCode         = urldecode( $result["L_ERRORCODE0"] );
                    $ErrorShortMsg     = urldecode( $result["L_SHORTMESSAGE0"] );
                    $ErrorLongMsg      = urldecode( $result["L_LONGMESSAGE0"] );
                    $ErrorSeverityCode = urldecode( $result["L_SEVERITYCODE0"] );
                    $this->add_log( 'SetExpressCheckout API call failed. ' );
                    $this->add_log( 'Detailed Error Message: ' . $ErrorLongMsg );
                    $this->add_log( 'Short Error Message: ' . $ErrorShortMsg );
                    $this->add_log( 'Error Code: ' . $ErrorCode );
                    $this->add_log( 'Error Severity Code: ' . $ErrorSeverityCode );
                    if($ErrorCode == '10486'){
                        $this->RedirectToPayPal( $this->get_session( 'TOKEN' ) );
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

                        $error_email_notify_mes = apply_filters( 'angelleye_ec_error_email_notify_message', $message, $ErrorCode, $ErrorSeverityCode, $ErrorShortMsg, $ErrorLongMsg );
                        $subject = "PayPal Express Checkout Error Notification";
                        $error_email_notify_subject = apply_filters( 'angelleye_ec_error_email_notify_subject', $subject );

						wp_mail($admin_email, $error_email_notify_subject, $error_email_notify_mes);
					}

					// Generate error message based on Error Display Type setting
					if($this->error_display_type == 'detailed')
					{
						$sec_error_notice = $ErrorCode.' - '.$ErrorLongMsg;
						$error_display_type_message =   sprintf( __($sec_error_notice, 'paypal-for-woocommerce' ) );
					}
					else
					{
                        $error_display_type_message = sprintf( __('There was a problem paying with PayPal.  Please try another method.', 'paypal-for-woocommerce' ) );
					}
                    $error_display_type_message = apply_filters( 'angelleye_ec_display_type_message', $error_display_type_message, $ErrorCode, $ErrorLongMsg );
                    wc_add_notice( $error_display_type_message , 'error' );

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
    function CallSetExpressCheckout($paymentAmount,$returnURL,$cancelURL,$usePayPalCredit = false, $posted)
    {
        /*
         * Display message to user if session has expired.
         */
        if(sizeof(WC()->cart->get_cart()) == 0)
        {
            $ms = sprintf(__( 'Sorry, your session has expired. <a href=%s>Return to homepage &rarr;</a>', 'paypal-for-woocommerce' ), '"'.home_url().'"');
            $set_ec_message = apply_filters( 'angelleye_set_ec_message', $ms );
            wc_add_notice( $set_ec_message, "error" );
        }

        /*
         * Check if the PayPal class has already been established.
         */
        if(!class_exists('Angelleye_PayPal' ))
        {
            require_once( 'lib/angelleye/paypal-php-library/includes/paypal.class.php' );
        }

        /*
         * Create PayPal object.
         */
        $PayPalConfig = array(
            'Sandbox' => $this->testmode == 'yes' ? TRUE : FALSE,
            'APIUsername' => $this->api_username,
            'APIPassword' => $this->api_password,
            'APISignature' => $this->api_signature
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
        if($this->gift_wrap_enabled == 'yes')
        {
            $maxAmount = $paymentAmount * 2;
            $maxAmount = $maxAmount + $this->gift_wrap_amount;
            $maxAmount = number_format($maxAmount,2,'.','');
        }
        else
        {
            $maxAmount = '';
        }

        $SECFields = array(
            'token' => '', 								// A timestamped token, the value of which was returned by a previous SetExpressCheckout call.
            'maxamt' => $maxAmount, 							// The expected maximum total amount the order will be, including S&H and sales tax.
            'returnurl' => urldecode($returnURL), 							// Required.  URL to which the customer will be returned after returning from PayPal.  2048 char max.
            'cancelurl' => urldecode($cancelURL), 							// Required.  URL to which the customer will be returned if they cancel payment on PayPal's site.
            'callback' => '', 							// URL to which the callback request from PayPal is sent.  Must start with https:// for production.
            'callbacktimeout' => '', 					// An override for you to request more or less time to be able to process the callback request and response.  Acceptable range for override is 1-6 seconds.  If you specify greater than 6 PayPal will use default value of 3 seconds.
            'callbackversion' => '', 					// The version of the Instant Update API you're using.  The default is the current version.
            'reqconfirmshipping' => '', 				// The value 1 indicates that you require that the customer's shipping address is Confirmed with PayPal.  This overrides anything in the account profile.  Possible values are 1 or 0.
            'noshipping' => '', 						// The value 1 indiciates that on the PayPal pages, no shipping address fields should be displayed.  Maybe 1 or 0.
            'allownote' => '', 							// The value 1 indiciates that the customer may enter a note to the merchant on the PayPal page during checkout.  The note is returned in the GetExpresscheckoutDetails response and the DoExpressCheckoutPayment response.  Must be 1 or 0.
            'addroverride' => '', 						// The value 1 indiciates that the PayPal pages should display the shipping address set by you in the SetExpressCheckout request, not the shipping address on file with PayPal.  This does not allow the customer to edit the address here.  Must be 1 or 0.
            'localecode' => $this->use_wp_locale_code == 'yes' ? substr(WPLANG, -2) : '', 						// Locale of pages displayed by PayPal during checkout.  Should be a 2 character country code.  You can retrive the country code by passing the country name into the class' GetCountryCode() function.
            'pagestyle' => '', 							// Sets the Custom Payment Page Style for payment pages associated with this button/link.
            'hdrimg' => $this->checkout_logo_hdrimg, 							// URL for the image displayed as the header during checkout.  Max size of 750x90.  Should be stored on an https:// server or you'll get a warning message in the browser.
            'logourl' => $this->checkout_logo,
            'hdrbordercolor' => '', 					// Sets the border color around the header of the payment page.  The border is a 2-pixel permiter around the header space.  Default is black.
            'hdrbackcolor' => '', 						// Sets the background color for the header of the payment page.  Default is white.
            'payflowcolor' => '', 						// Sets the background color for the payment page.  Default is white.
            'skipdetails' => $this->skip_final_review == 'yes' ? '1' : '0', 						// This is a custom field not included in the PayPal documentation.  It's used to specify whether you want to skip the GetExpressCheckoutDetails part of checkout or not.  See PayPal docs for more info.
            'email' => '', 								// Email address of the buyer as entered during checkout.  PayPal uses this value to pre-fill the PayPal sign-in page.  127 char max.
			'channeltype' => '', 						// Type of channel.  Must be Merchant (non-auction seller) or eBayItem (eBay auction)
            'giropaysuccessurl' => '', 					// The URL on the merchant site to redirect to after a successful giropay payment.  Only use this field if you are using giropay or bank transfer payment methods in Germany.
            'giropaycancelurl' => '', 					// The URL on the merchant site to redirect to after a canceled giropay payment.  Only use this field if you are using giropay or bank transfer methods in Germany.
            'banktxnpendingurl' => '',  				// The URL on the merchant site to transfer to after a bank transfter payment.  Use this field only if you are using giropay or bank transfer methods in Germany.
            'brandname' => $this->brand_name, 							// A label that overrides the business name in the PayPal account on the PayPal hosted checkout pages.  127 char max.
            'customerservicenumber' => $this->customer_service_number, 				// Merchant Customer Service number displayed on the PayPal Review page. 16 char max.
            'buyeremailoptionenable' => '', 			// Enable buyer email opt-in on the PayPal Review page. Allowable values are 0 and 1
            'surveyquestion' => '', 					// Text for the survey question on the PayPal Review page. If the survey question is present, at least 2 survey answer options need to be present.  50 char max.
            'surveyenable' => '', 						// Enable survey functionality. Allowable values are 0 and 1
            'totaltype' => '', 							// Enables display of "estimated total" instead of "total" in the cart review area.  Values are:  Total, EstimatedTotal
            'notetobuyer' => '', 						// Displays a note to buyers in the cart review area below the total amount.  Use the note to tell buyers about items in the cart, such as your return policy or that the total excludes shipping and handling.  127 char max.
            'buyerid' => '', 							// The unique identifier provided by eBay for this buyer. The value may or may not be the same as the username. In the case of eBay, it is different. 255 char max.
            'buyerusername' => '', 						// The user name of the user at the marketplaces site.
            'buyerregistrationdate' => '',  			// Date when the user registered with the marketplace.
            'allowpushfunding' => '', 					// Whether the merchant can accept push funding.  0 = Merchant can accept push funding : 1 = Merchant cannot accept push funding.
            'taxidtype' => '', 							// The buyer's tax ID type.  This field is required for Brazil and used for Brazil only.  Values:  BR_CPF for individuals and BR_CNPJ for businesses.
            'taxid' => ''								// The buyer's tax ID.  This field is required for Brazil and used for Brazil only.  The tax ID is 11 single-byte characters for individutals and 14 single-byte characters for businesses.
        );




        /**
		 * If Gift Wrap options are enabled, add them to SEC
		 */
		if(strtolower($this->gift_wrap_enabled) == 'yes')
		{
			$SECFields['giftwrapenable'] = '1'; 					// Enable gift wrap widget on the PayPal Review page.  Allowable values are 0 and 1.
			$SECFields['giftmessageenable'] = $this->gift_message_enabled ? '1' : ''; 					// Enable gift message widget on the PayPal Review page. Allowable values are 0 and 1
            $SECFields['giftreceiptenable'] = $this->gift_receipt_enabled ? '1' : ''; 					// Enable gift receipt widget on the PayPal Review page. Allowable values are 0 and 1
            $SECFields['giftwrapname'] = $this->gift_wrap_name; 						// Label for the gift wrap option such as "Box with ribbon".  25 char max.
            $SECFields['giftwrapamount'] = $this->gift_wrap_amount;			// Amount charged for gift-wrap service.
		}

		/**
		 * If PayPal Credit is being used, override the necessary parameters
		 */
		if($usePayPalCredit)
		{
			$SECFields['solutiontype'] = 'Sole';
			$SECFields['landingpage'] = 'Billing';
			$SECFields['userselectedfundingsource'] = 'BML';
		}
		elseif(strtolower($this->paypal_account_optional) == 'yes' && strtolower($this->landing_page) == 'billing')
		{
			$SECFields['solutiontype'] = 'Sole';
			$SECFields['landingpage'] = 'Billing';
			$SECFields['userselectedfundingsource'] = 'CreditCard';
		}
		elseif(strtolower($this->paypal_account_optional) == 'yes' && strtolower($this->landing_page) == 'login')
		{
			$SECFields['solutiontype'] = 'Sole';
			$SECFields['landingpage'] = 'Login';
		}

        // Basic array of survey choices.  Nothing but the values should go in here.
        $SurveyChoices = array('Choice 1', 'Choice2', 'Choice3', 'etc');

        /*
         * Get tax amount.
         */
        if(get_option('woocommerce_prices_include_tax') == 'yes')
        {
            $shipping 		= WC()->cart->shipping_total + WC()->cart->shipping_tax_total;
            $tax			= '0.00';
        }
        else
        {
            $shipping 		= WC()->cart->shipping_total;
            $tax 			= WC()->cart->get_taxes_total();
        }

        $Payments = array();
        $Payment = array(
            'amt' => number_format(WC()->cart->total,2,'.',''), 							// Required.  The total cost of the transaction to the customer.  If shipping cost and tax charges are known, include them in this value.  If not, this value should be the current sub-total of the order.
            'currencycode' => get_woocommerce_currency(), 					// A three-character currency code.  Default is USD.
            'shippingamt' => number_format($shipping,2,'.',''), 					// Total shipping costs for this order.  If you specify SHIPPINGAMT you mut also specify a value for ITEMAMT.
            'shippingdiscamt' => '', 				// Shipping discount for this order, specified as a negative number.
            'insuranceamt' => '', 					// Total shipping insurance costs for this order.
            'insuranceoptionoffered' => '', 		// If true, the insurance drop-down on the PayPal review page displays the string 'Yes' and the insurance amount.  If true, the total shipping insurance for this order must be a positive number.
            'handlingamt' => '', 					// Total handling costs for this order.  If you specify HANDLINGAMT you mut also specify a value for ITEMAMT.
            'taxamt' => $tax, 						// Required if you specify itemized L_TAXAMT fields.  Sum of all tax items in this order.
            'desc' => '', 							// Description of items on the order.  127 char max.
            'custom' => '', 						// Free-form field for your own use.  256 char max.
            'invnum' => '', 						// Your own invoice or tracking number.  127 char max.
            'notifyurl' => '', 						// URL for receiving Instant Payment Notifications
            'shiptoname' => '', 					// Required if shipping is included.  Person's name associated with this address.  32 char max.
            'shiptostreet' => '', 					// Required if shipping is included.  First street address.  100 char max.
            'shiptostreet2' => '', 					// Second street address.  100 char max.
            'shiptocity' => '', 					// Required if shipping is included.  Name of city.  40 char max.
            'shiptostate' => '', 					// Required if shipping is included.  Name of state or province.  40 char max.
            'shiptozip' => '', 						// Required if shipping is included.  Postal code of shipping address.  20 char max.
            'shiptocountrycode' => '', 				// Required if shipping is included.  Country code of shipping address.  2 char max.
            'shiptophonenum' => '',  				// Phone number for shipping address.  20 char max.
            'notetext' => '', 						// Note to the merchant.  255 char max.
            'allowedpaymentmethod' => '', 			// The payment method type.  Specify the value InstantPaymentOnly.
            'paymentaction' => 'Sale', 					// How you want to obtain the payment.  When implementing parallel payments, this field is required and must be set to Order.
            'paymentrequestid' => '',  				// A unique identifier of the specific payment request, which is required for parallel payments.
            'sellerpaypalaccountid' => ''			// A unique identifier for the merchant.  For parallel payments, this field is required and must contain the Payer ID or the email address of the merchant.
        );

        /**
         * If checkout like regular payment
         */

        if (!empty($posted) && WC()->cart->needs_shipping() ) {
            $SECFields['addroverride']      = 1;
            if (@$posted['ship_to_different_address']) {
                $Payment['shiptoname']          = $posted['shipping_first_name'].' '.$posted['shipping_last_name'];
                $Payment['shiptostreet']        = $posted['shipping_address_1'];
                $Payment['shiptostreet2']       = @$posted['shipping_address_2'];
                $Payment['shiptocity']          = @$posted['shipping_city'];
                $Payment['shiptostate']         = @$posted['shipping_state'];
                $Payment['shiptozip']           = @$posted['shipping_postcode'];
                $Payment['shiptocountrycode']   = @$posted['shipping_country'];
                $Payment['shiptophonenum']      = @$posted['shipping_phone'];
            } else {
                $Payment['shiptoname']          = $posted['billing_first_name'].' '.$posted['billing_last_name'];
                $Payment['shiptostreet']        = $posted['billing_address_1'];
                $Payment['shiptostreet2']       = @$posted['billing_address_2'];
                $Payment['shiptocity']          = @$posted['billing_city'];
                $Payment['shiptostate']         = @$posted['billing_state'];
                $Payment['shiptozip']           = @$posted['billing_postcode'];
                $Payment['shiptocountrycode']   = @$posted['billing_country'];
                $Payment['shiptophonenum']      = @$posted['billing_phone'];
            }

        }

        $PaymentOrderItems = array();
        $ctr = $total_items = $total_discount = $total_tax = $order_total = 0;
        foreach(WC()->cart->get_cart() as $cart_item_key => $values)
        {
            /*
             * Get product data from WooCommerce
             */
            $_product          = $values['data'];
            $qty               = absint( $values['quantity'] );
            $sku = $_product->get_sku();
            $values['name'] = html_entity_decode($_product->get_title(), ENT_NOQUOTES, 'UTF-8');

            /*
             * Append variation data to name.
             */
            if ($_product->product_type=='variation') {

                $meta = WC()->cart->get_item_data($values, true);

                if (empty($sku)) {
                    $sku = $_product->parent->get_sku();
                }

                if (!empty($meta)) {
                    $values['name'] .= " - ". str_replace(", \n", " - ",$meta);
                }
            }

            /*
             * Set price based on tax option.
             */
            if(get_option('woocommerce_prices_include_tax') == 'yes')
            {
                $product_price = number_format($_product->get_price_including_tax(),2,'.','');
            }
            else
            {
                $product_price = number_format($_product->get_price_excluding_tax(),2,'.','');
            }

            $Item = array(
                'name' => $values['name'], 								// Item name. 127 char max.
                'desc' => '', 								// Item description. 127 char max.
                'amt' => number_format($product_price,2,'.',''), 								// Cost of item.
                'number' => $sku, 							// Item number.  127 char max.
                'qty' => $qty, 								// Item qty on order.  Any positive integer.
                'taxamt' => '', 							// Item sales tax
                'itemurl' => '', 							// URL for the item.
                'itemcategory' => '', 						// One of the following values:  Digital, Physical
                'itemweightvalue' => '', 					// The weight value of the item.
                'itemweightunit' => '', 					// The weight unit of the item.
                'itemheightvalue' => '', 					// The height value of the item.
                'itemheightunit' => '', 					// The height unit of the item.
                'itemwidthvalue' => '', 					// The width value of the item.
                'itemwidthunit' => '', 						// The width unit of the item.
                'itemlengthvalue' => '', 					// The length value of the item.
                'itemlengthunit' => '',  					// The length unit of the item.
                'ebayitemnumber' => '', 					// Auction item number.
                'ebayitemauctiontxnid' => '', 				// Auction transaction ID number.
                'ebayitemorderid' => '',  					// Auction order ID number.
                'ebayitemcartid' => ''						// The unique identifier provided by eBay for this order from the buyer. These parameters must be ordered sequentially beginning with 0 (for example L_EBAYITEMCARTID0, L_EBAYITEMCARTID1). Character length: 255 single-byte characters
            );
            array_push($PaymentOrderItems, $Item);

            $total_items += $product_price*$values['quantity'];
            $ctr++;
        }

		/**
		 * Add custom Woo cart fees as line items
		 */
        foreach ( WC()->cart->get_fees() as $fee ) {
            $Item = array(
                'name' => $fee->name, // Item name. 127 char max.
                'desc' => '', // Item description. 127 char max.
                'amt' => number_format($fee->amount,2,'.',''), // Cost of item.
                'number' => $fee->id, // Item number. 127 char max.
                'qty' => 1, // Item qty on order. Any positive integer.
                'taxamt' => '', // Item sales tax
                'itemurl' => '', // URL for the item.
                'itemcategory' => '', // One of the following values: Digital, Physical
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
                'ebayitemcartid' => '' // The unique identifier provided by eBay for this order from the buyer. These parameters must be ordered sequentially beginning with 0 (for example L_EBAYITEMCARTID0, L_EBAYITEMCARTID1). Character length: 255 single-byte characters
            );
            array_push($PaymentOrderItems, $Item);

            $total_items += $fee->amount*$Item['qty'];
            $ctr++;

        }

        /*
         * Get discount(s)
         */
        if(WC()->cart->get_cart_discount_total())
        {
            foreach(WC()->cart->get_coupons('cart') as $code => $coupon)
            {
                $Item = array(
                    'name' => 'Cart Discount',
                    'number' => $code,
                    'qty' => '1',
                    'amt' => '-'.number_format(WC()->cart->coupon_discount_amounts[$code],2,'.','')
                );
                array_push($PaymentOrderItems,$Item);
            }
            $total_discount -= WC()->cart->get_cart_discount_total();
        }

        if(WC()->cart->get_order_discount_total())
        {
            foreach(WC()->cart->get_coupons('order') as $code => $coupon)
            {
                $Item = array(
                    'name' => 'Order Discount',
                    'number' => $code,
                    'qty' => '1',
                    'amt' => '-'.number_format(WC()->cart->coupon_discount_amounts[$code],2,'.','')
                );
                array_push($PaymentOrderItems,$Item);
            }
            $total_discount -= WC()->cart->get_order_discount_total();
        }

        /*
         * Now that all the order items are gathered, including discounts,
         * we'll push them back into the Payment.
         */
        $Payment['order_items'] = $PaymentOrderItems;

        /*
         * Now that we've looped and calculated item totals
         * we can fill in the ITEMAMT
         */
        $Payment['itemamt'] = $total_items+$total_discount; 	// Required if you specify itemized L_AMT fields. Sum of cost of all items in this order.

        /*
         * Then we load the payment into the $Payments array
         */
        array_push($Payments, $Payment);

        $BuyerDetails = array(
            'buyerid' => '', 				// The unique identifier provided by eBay for this buyer.  The value may or may not be the same as the username.  In the case of eBay, it is different.  Char max 255.
            'buyerusername' => '', 			// The username of the marketplace site.
            'buyerregistrationdate' => ''	// The registration of the buyer with the marketplace.
        );

        // For shipping options we create an array of all shipping choices similar to how order items works.
        $ShippingOptions = array();
        $Option = array(
            'l_shippingoptionisdefault' => '', 				// Shipping option.  Required if specifying the Callback URL.  true or false.  Must be only 1 default!
            'l_shippingoptionname' => '', 					// Shipping option name.  Required if specifying the Callback URL.  50 character max.
            'l_shippingoptionlabel' => '', 					// Shipping option label.  Required if specifying the Callback URL.  50 character max.
            'l_shippingoptionamount' => '' 					// Shipping option amount.  Required if specifying the Callback URL.
        );
        array_push($ShippingOptions, $Option);

        $BillingAgreements = array();
        $Item = array(
            'l_billingtype' => '', 							// Required.  Type of billing agreement.  For recurring payments it must be RecurringPayments.  You can specify up to ten billing agreements.  For reference transactions, this field must be either:  MerchantInitiatedBilling, or MerchantInitiatedBillingSingleSource
            'l_billingagreementdescription' => '', 			// Required for recurring payments.  Description of goods or services associated with the billing agreement.
            'l_paymenttype' => '', 							// Specifies the type of PayPal payment you require for the billing agreement.  Any or IntantOnly
            'l_billingagreementcustom' => ''					// Custom annotation field for your own use.  256 char max.
        );

        array_push($BillingAgreements, $Item);

        $PayPalRequestData = array(
            'SECFields' => $SECFields,
            'SurveyChoices' => $SurveyChoices,
            'Payments' => $Payments,
            //'BuyerDetails' => $BuyerDetails,
            //'ShippingOptions' => $ShippingOptions,
            //'BillingAgreements' => $BillingAgreements
        );

        // Pass data into class for processing with PayPal and load the response array into $PayPalResult
        $PayPalResult = $PayPal->SetExpressCheckout($PayPalRequestData);

        /*
         * Log API result
         */
        $this->add_log('Test Mode: '.$this->testmode);
        $this->add_log('Endpoint: '.$this->API_Endpoint);

		$PayPalRequest = isset($PayPalResult['RAWREQUEST']) ? $PayPalResult['RAWREQUEST'] : '';
		$PayPalResponse = isset($PayPalResult['RAWRESPONSE']) ? $PayPalResult['RAWRESPONSE'] : '';

        $this->add_log('Request: '.print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalRequest)),true));
		$this->add_log('Response: '.print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalResponse)),true));

        /*
         * Error handling
         */
        if($PayPal->APICallSuccessful($PayPalResult['ACK']))
        {
            $token = urldecode($PayPalResult["TOKEN"] );
            $this->set_session('TOKEN',$token);
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
    function CallGetShippingDetails($token)
    {
        /*
         * Display message to user if session has expired.
         */
        if(sizeof(WC()->cart->get_cart()) == 0)
        {
            $ms = sprintf(__( 'Sorry, your session has expired. <a href=%s>Return to homepage &rarr;</a>', 'paypal-for-woocommerce' ), '"'.home_url().'"');
            $ec_cgsd_message = apply_filters( 'angelleye_get_shipping_ec_message', $ms );
            wc_add_notice( $ec_cgsd_message, "error" );
        }

        /*
         * Check if the PayPal class has already been established.
         */
        if(!class_exists('Angelleye_PayPal' ))
        {
            require_once( 'lib/angelleye/paypal-php-library/includes/paypal.class.php' );
        }

        /*
         * Create PayPal object.
         */
        $PayPalConfig = array(
            'Sandbox' => $this->testmode == 'yes' ? TRUE : FALSE,
            'APIUsername' => $this->api_username,
            'APIPassword' => $this->api_password,
            'APISignature' => $this->api_signature
        );
        $PayPal = new Angelleye_PayPal($PayPalConfig);

        /*
         * Call GetExpressCheckoutDetails
         */
        $PayPalResult = $PayPal->GetExpressCheckoutDetails($token);

		/*
         * Log API result
         */
        $this->add_log('Test Mode: '.$this->testmode);
        $this->add_log('Endpoint: '.$this->API_Endpoint);

		$PayPalRequest = isset($PayPalResult['RAWREQUEST']) ? $PayPalResult['RAWREQUEST'] : '';
		$PayPalResponse = isset($PayPalResult['RAWRESPONSE']) ? $PayPalResult['RAWRESPONSE'] : '';

        $this->add_log('Request: '.print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalRequest)),true));
		$this->add_log('Response: '.print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalResponse)),true));

        /*
         * Error handling
         */
        if($PayPal->APICallSuccessful($PayPalResult['ACK']))
        {
            $this->set_session('payer_id',$PayPalResult['PAYERID']);
        }

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
    function ConfirmPayment($FinalPaymentAmt)
    {
        /*
         * Display message to user if session has expired.
         */
        if(sizeof(WC()->cart->get_cart()) == 0)
        {
            $ms = sprintf(__( 'Sorry, your session has expired. <a href=%s>Return to homepage &rarr;</a>', 'paypal-for-woocommerce' ), '"'.home_url().'"');
            $ec_confirm_message = apply_filters( 'angelleye_ec_confirm_message', $ms );
            wc_add_notice( $ec_confirm_message, "error" );
        }

        /*
         * Check if the PayPal class has already been established.
         */
        if(!class_exists('Angelleye_PayPal' ))
        {
            require_once( 'lib/angelleye/paypal-php-library/includes/paypal.class.php' );
        }

        /*
         * Create PayPal object.
         */
        $PayPalConfig = array(
            'Sandbox' => $this->testmode == 'yes' ? TRUE : FALSE,
            'APIUsername' => $this->api_username,
            'APIPassword' => $this->api_password,
            'APISignature' => $this->api_signature
        );
        $PayPal = new Angelleye_PayPal($PayPalConfig);

        /*
         * Get data from WooCommerce object
         */
        if (!empty($this->confirm_order_id))
        {
            $order =  new WC_Order($this->confirm_order_id);
			$invoice_number = preg_replace("/[^0-9,.]/", "", $order->get_order_number());

            if ( $order->customer_note )
            {
                $customer_notes = wptexturize($order->customer_note);
            }

            $shipping_first_name = $order->shipping_first_name;
            $shipping_last_name = $order->shipping_last_name;
            $shipping_address_1 = $order->shipping_address_1;
            $shipping_address_2 = $order->shipping_address_2;
            $shipping_city = $order->shipping_city;
            $shipping_state = $order->shipping_state;
            $shipping_postcode = $order->shipping_postcode;
            $shipping_country = $order->shipping_country;

        }

        // Prepare request arrays
        $DECPFields = array(
            'token' => urlencode($this->get_session('TOKEN')), 								// Required.  A timestamped token, the value of which was returned by a previous SetExpressCheckout call.
            'payerid' => urlencode($this->get_session('payer_id')), 							// Required.  Unique PayPal customer id of the payer.  Returned by GetExpressCheckoutDetails, or if you used SKIPDETAILS it's returned in the URL back to your RETURNURL.
            'returnfmfdetails' => '', 					// Flag to indiciate whether you want the results returned by Fraud Management Filters or not.  1 or 0.
            'giftmessage' => $this->get_session('giftmessage'), 						// The gift message entered by the buyer on the PayPal Review page.  150 char max.
            'giftreceiptenable' => $this->get_session('giftreceiptenable'), 					// Pass true if a gift receipt was selected by the buyer on the PayPal Review page. Otherwise pass false.
            'giftwrapname' => $this->get_session('giftwrapname'), 						// The gift wrap name only if the gift option on the PayPal Review page was selected by the buyer.
            'giftwrapamount' => $this->get_session('giftwrapamount'), 					// The amount only if the gift option on the PayPal Review page was selected by the buyer.
            'buyermarketingemail' => '', 				// The buyer email address opted in by the buyer on the PayPal Review page.
            'surveyquestion' => '', 					// The survey question on the PayPal Review page.  50 char max.
            'surveychoiceselected' => '',  				// The survey response selected by the buyer on the PayPal Review page.  15 char max.
            'allowedpaymentmethod' => '' 				// The payment method type. Specify the value InstantPaymentOnly.
        );

        $Payments = array();
        $Payment = array(
            'amt' => number_format($FinalPaymentAmt,2,'.',''), 							// Required.  The total cost of the transaction to the customer.  If shipping cost and tax charges are known, include them in this value.  If not, this value should be the current sub-total of the order.
            'currencycode' => get_woocommerce_currency(), 					// A three-character currency code.  Default is USD.
            'shippingdiscamt' => '', 				// Total shipping discount for this order, specified as a negative number.
            'insuranceoptionoffered' => '', 		// If true, the insurance drop-down on the PayPal review page displays the string 'Yes' and the insurance amount.  If true, the total shipping insurance for this order must be a positive number.
            'handlingamt' => '', 					// Total handling costs for this order.  If you specify HANDLINGAMT you mut also specify a value for ITEMAMT.
            'desc' => '', 							// Description of items on the order.  127 char max.
            'custom' => '', 						// Free-form field for your own use.  256 char max.
            'invnum' => $this->invoice_id_prefix . $invoice_number, 						// Your own invoice or tracking number.  127 char max.
            'notifyurl' => '', 						// URL for receiving Instant Payment Notifications
            'shiptoname' => $shipping_first_name.' '.$shipping_last_name, 					// Required if shipping is included.  Person's name associated with this address.  32 char max.
            'shiptostreet' => $shipping_address_1, 					// Required if shipping is included.  First street address.  100 char max.
            'shiptostreet2' => $shipping_address_2, 					// Second street address.  100 char max.
            'shiptocity' => $shipping_city, 					// Required if shipping is included.  Name of city.  40 char max.
            'shiptostate' => $shipping_state, 					// Required if shipping is included.  Name of state or province.  40 char max.
            'shiptozip' => $shipping_postcode, 						// Required if shipping is included.  Postal code of shipping address.  20 char max.
            'shiptocountrycode' => $shipping_country, 				// Required if shipping is included.  Country code of shipping address.  2 char max.
            'shiptophonenum' => '',  				// Phone number for shipping address.  20 char max.
            'notetext' => $this->get_session('customer_notes'), 						// Note to the merchant.  255 char max.
            'allowedpaymentmethod' => '', 			// The payment method type.  Specify the value InstantPaymentOnly.
            'paymentaction' => 'Sale', 					// How you want to obtain the payment.  When implementing parallel payments, this field is required and must be set to Order.
            'paymentrequestid' => '',  				// A unique identifier of the specific payment request, which is required for parallel payments.
            'sellerpaypalaccountid' => '', 			// A unique identifier for the merchant.  For parallel payments, this field is required and must contain the Payer ID or the email address of the merchant.
            'sellerid' => '', 						// The unique non-changing identifer for the seller at the marketplace site.  This ID is not displayed.
            'sellerusername' => '', 				// The current name of the seller or business at the marketplace site.  This name may be shown to the buyer.
            'sellerregistrationdate' => '', 		// Date when the seller registered at the marketplace site.
            'softdescriptor' => ''					// A per transaction description of the payment that is passed to the buyer's credit card statement.
        );

        $PaymentOrderItems = array();
        $ctr = 0;
        $ITEMAMT = 0;
        if(sizeof($order->get_items())>0)
        {
            foreach ($order->get_items() as $values)
            {
                $_product = $order->get_product_from_item($values);
                $qty               = absint( $values['qty'] );
                $sku = $_product->get_sku();
                $values['name'] = html_entity_decode($values['name'], ENT_NOQUOTES, 'UTF-8');
                if ($_product->product_type=='variation')
                {
                    if (empty($sku))
                    {
                        $sku = $_product->parent->get_sku();
                    }

                    $item_meta = new WC_Order_Item_Meta( $values['item_meta'] );
                    $meta = $item_meta->display(true, true);
                    if (!empty($meta))
                    {
                        $values['name'] .= " - ".str_replace(", \n", " - ",$meta);
                    }
                }

                /*
                 * Set price based on tax option.
                 */
                if(get_option('woocommerce_prices_include_tax') == 'yes')
                {
                    $product_price = $order->get_item_subtotal($values,true,false);
                }
                else
                {
                    $product_price = $order->get_item_subtotal($values,false,true);
                }

                $Item = array(
                    'name' => $values['name'], 								// Item name. 127 char max.
                    'desc' => '', 								// Item description. 127 char max.
                    'amt' => $product_price, 								// Cost of item.
                    'number' => $sku, 							// Item number.  127 char max.
                    'qty' => $qty, 								// Item qty on order.  Any positive integer.
                    'taxamt' => '', 							// Item sales tax
                    'itemurl' => '', 							// URL for the item.
                    'itemcategory' => '', 						// One of the following values:  Digital, Physical
                    'itemweightvalue' => '', 					// The weight value of the item.
                    'itemweightunit' => '', 					// The weight unit of the item.
                    'itemheightvalue' => '', 					// The height value of the item.
                    'itemheightunit' => '', 					// The height unit of the item.
                    'itemwidthvalue' => '', 					// The width value of the item.
                    'itemwidthunit' => '', 						// The width unit of the item.
                    'itemlengthvalue' => '', 					// The length value of the item.
                    'itemlengthunit' => '',  					// The length unit of the item.
                    'ebayitemnumber' => '', 					// Auction item number.
                    'ebayitemauctiontxnid' => '', 				// Auction transaction ID number.
                    'ebayitemorderid' => '',  					// Auction order ID number.
                    'ebayitemcartid' => ''						// The unique identifier provided by eBay for this order from the buyer. These parameters must be ordered sequentially beginning with 0 (for example L_EBAYITEMCARTID0, L_EBAYITEMCARTID1). Character length: 255 single-byte characters
                );
                array_push($PaymentOrderItems, $Item);

                $ITEMAMT += $product_price * $values['qty'];
            }

			/**
			 * Add custom Woo cart fees as line items
			 */
			foreach ( WC()->cart->get_fees() as $fee ) {
				$Item = array(
					'name' => $fee->name, // Item name. 127 char max.
					'desc' => '', // Item description. 127 char max.
					'amt' => number_format($fee->amount,2,'.',''), // Cost of item.
					'number' => $fee->id, // Item number. 127 char max.
					'qty' => 1, // Item qty on order. Any positive integer.
					'taxamt' => '', // Item sales tax
					'itemurl' => '', // URL for the item.
					'itemcategory' => '', // One of the following values: Digital, Physical
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
					'ebayitemcartid' => '' // The unique identifier provided by eBay for this order from the buyer. These parameters must be ordered sequentially beginning with 0 (for example L_EBAYITEMCARTID0, L_EBAYITEMCARTID1). Character length: 255 single-byte characters
				);

				/**
				 * The gift wrap amount actually has its own parameter in
				 * DECP, so we don't want to include it as one of the line
				 * items.
				 */
				if($Item['number'] != 'gift-wrap')
				{
					array_push($PaymentOrderItems, $Item);
					$ITEMAMT += $fee->amount*$Item['qty'];
				}

				$ctr++;

			}

            /*
             * Get discounts
             */
            if($order->get_cart_discount()>0)
            {
                foreach(WC()->cart->get_coupons('cart') as $code => $coupon)
                {
                    $Item = array(
                        'name' => 'Cart Discount',
                        'number' => $code,
                        'qty' => '1',
                        'amt' => '-'.number_format(WC()->cart->coupon_discount_amounts[$code],2,'.','')
                    );
                    array_push($PaymentOrderItems,$Item);
                }
                $ITEMAMT -= $order->get_cart_discount();
            }

            if($order->get_order_discount()>0)
            {
                foreach(WC()->cart->get_coupons('order') as $code => $coupon)
                {
                    $Item = array(
                        'name' => 'Order Discount',
                        'number' => $code,
                        'qty' => '1',
                        'amt' => '-'.number_format(WC()->cart->coupon_discount_amounts[$code],2,'.','')
                    );
                    array_push($PaymentOrderItems,$Item);
                }
                $ITEMAMT -= $order->get_order_discount();
            }

            /*
             * Set shipping and tax values.
             */
            if(get_option('woocommerce_prices_include_tax') == 'yes')
            {
                $shipping 		= $order->get_total_shipping() + $order->get_shipping_tax();
                $tax			= 0;
            }
            else
            {
                $shipping 		= $order->get_total_shipping();
                $tax 			= $order->get_total_tax();
            }

            /*
             * Now that we have all items and subtotals
             * we can fill in necessary values.
             */
            $Payment['itemamt'] = number_format($ITEMAMT,2,'.',''); 						// Required if you specify itemized L_AMT fields. Sum of cost of all items in this order.

            /*
             * Set tax
             */
            if($tax>0)
            {
                $Payment['taxamt'] = number_format($tax,2,'.',''); 						// Required if you specify itemized L_TAXAMT fields.  Sum of all tax items in this order.
            }

            /*
             * Set shipping
             */
            if($shipping > 0)
            {
                $Payment['shippingamt'] = number_format($shipping,2,'.',''); 					// Total shipping costs for this order.  If you specify SHIPPINGAMT you mut also specify a value for ITEMAMT.
            }
        }

        $Payment['order_items'] = $PaymentOrderItems;
        array_push($Payments, $Payment);

        $UserSelectedOptions = array(
            'shippingcalculationmode' => '', 	// Describes how the options that were presented to the user were determined.  values are:  API - Callback   or   API - Flatrate.
            'insuranceoptionselected' => '', 	// The Yes/No option that you chose for insurance.
            'shippingoptionisdefault' => '', 	// Is true if the buyer chose the default shipping option.
            'shippingoptionamount' => '', 		// The shipping amount that was chosen by the buyer.
            'shippingoptionname' => '', 		// Is true if the buyer chose the default shipping option...??  Maybe this is supposed to show the name..??
        );

        $PayPalRequestData = array(
            'DECPFields' => $DECPFields,
            'Payments' => $Payments,
            //'UserSelectedOptions' => $UserSelectedOptions
        );

        // Pass data into class for processing with PayPal and load the response array into $PayPalResult
        $PayPalResult = $PayPal->DoExpressCheckoutPayment($PayPalRequestData);

		/*
         * Log API result
         */
        $this->add_log('Test Mode: '.$this->testmode);
        $this->add_log('Endpoint: '.$this->API_Endpoint);

		$PayPalRequest = isset($PayPalResult['RAWREQUEST']) ? $PayPalResult['RAWREQUEST'] : '';
		$PayPalResponse = isset($PayPalResult['RAWRESPONSE']) ? $PayPalResult['RAWRESPONSE'] : '';

        $this->add_log('Request: '.print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalRequest)),true));
		$this->add_log('Response: '.print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalResponse)),true));

        /*
         * Error handling
         */
        if($PayPal->APICallSuccessful($PayPalResult['ACK']))
        {
            $this->remove_session('TOKEN');
        }

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
        return WC()->session->$key;
    }
    private function remove_session( $key ) {
        WC()->session->$key = "";
    }


    static function woocommerce_before_cart() {
        global $pp_settings, $pp_pro, $pp_payflow;
        echo "<style>table.cart td.actions .input-text, table.cart td.actions .button, table.cart td.actions .checkout-button {margin-bottom: 0.53em !important}</style>";
        if ((@$pp_settings['enabled']== 'yes') && 0 < WC()->cart->total ) {
            $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
            unset($payment_gateways['paypal_pro']);
            unset($payment_gateways['paypal_pro_payflow']);
            if (empty($payment_gateways))
                if (@$pp_pro['enabled'] == 'yes' || @$pp_payflow['enabled'] == 'yes') {
                    echo '<script type="text/javascript">
                                jQuery(document).ready(function(){
                                    if (jQuery(".checkout-button").is("input")) {
                                        jQuery(".checkout-button").val("'.__('Pay with Credit Card', 'paypal-for-woocommerce').'");
                                    } else jQuery(".checkout-button").html("<span>'.__('Pay with Credit Card', 'paypal-for-woocommerce').'</span>");
                                });
                              </script>';
                } elseif(empty($pp_settings['show_on_cart']) || $pp_settings['show_on_cart']=='yes') {
                    echo '<style> input.checkout-button,
                                 a.checkout-button {
                                    display: none !important;
                                }</style>';
                }
        }
    }
    /**
     * Checkout Button
     *
     * Triggered from the 'woocommerce_proceed_to_checkout' action.
     * Displays the PayPal Express button.
     */
    static function woocommerce_paypal_express_checkout_button_angelleye()
    {
        global $pp_settings, $pp_pro, $pp_payflow;
        $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
        // Pay with Credit Card
        unset($payment_gateways['paypal_pro']);
        unset($payment_gateways['paypal_pro_payflow']);

        echo '<div class="clear"></div>';

        /**
         * Show the paypal express checkout button in cart page when express checkout is enabled and cart total > 0
         * If show_on_cart is empty so it's value default to yes
         */
        if (@$pp_settings['enabled']=='yes' && (empty($pp_settings['show_on_cart']) || $pp_settings['show_on_cart']=='yes') && 0 < WC()->cart->total )
        {
            echo '<div class="paypal_box_button" style="position: relative;">';
            if (empty($pp_settings['checkout_with_pp_button_type'])) $pp_settings['checkout_with_pp_button_type']='paypalimage';
            switch($pp_settings['checkout_with_pp_button_type']){
                case "textbutton":
                    if(!empty($pp_settings['pp_button_type_text_button'])){
                        $button_text = $pp_settings['pp_button_type_text_button'];
                    }
                    else
                    {
                        $button_text = __( 'Proceed to Checkout', 'woocommerce' );
                    }
                    echo '<a class="paypal_checkout_button button alt" href="'. add_query_arg( 'pp_action', 'expresscheckout', add_query_arg( 'wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url( '/' ) ) ) .'">' . $button_text .'</a>';
                    break;
                case "paypalimage":
                    echo '<div id="paypal_ec_button">';
                    echo '<a class="paypal_checkout_button" href="' . add_query_arg( 'pp_action', 'expresscheckout', add_query_arg( 'wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url( '/' ) ) ) .'">';
                    echo "<img src='https://www.paypal.com/".WC_Gateway_PayPal_Express_AngellEYE::get_button_locale_code()."/i/btn/btn_xpressCheckout.gif' border='0' alt='". __('Pay with PayPal', 'paypal-for-woocommerce')."'/>";
                    echo "</a>";
                    echo '</div>';
                    break;
                case "customimage":
                    $button_img = $pp_settings['pp_button_type_my_custom'];
                    echo '<div id="paypal_ec_button">';
                    echo '<a class="paypal_checkout_button" href="' . add_query_arg( 'pp_action', 'expresscheckout', add_query_arg( 'wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url( '/' ) ) ) .'">';
                    echo "<img src='{$button_img}' width='150' border='0' alt='". __('Pay with PayPal', 'paypal-for-woocommerce')."'/>";
                    echo "</a>";
                    echo '</div>';
                    break;
            }

            /**
             * Displays the PayPal Credit checkout button if enabled in EC settings.
             */
            if(isset($pp_settings['show_paypal_credit']) && $pp_settings['show_paypal_credit'] == 'yes')
            {
                // PayPal Credit button
                $paypal_credit_button_markup = '<div id="paypal_ec_paypal_credit_button">';
                $paypal_credit_button_markup .= '<a class="paypal_checkout_button" href="' . add_query_arg('use_paypal_credit', 'true', add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '" >';
                $paypal_credit_button_markup .= "<img src='https://www.paypalobjects.com/webstatic/en_US/i/buttons/ppcredit-logo-small.png' alt='Check out with PayPal Credit'/>";
                $paypal_credit_button_markup .= '</a>';
                $paypal_credit_button_markup .= '</div>';

                echo $paypal_credit_button_markup;
            }
            ?>
            <div class="blockUI blockOverlay angelleyeOverlay" style="display:none;z-index: 1000; border: none; margin: 0px; padding: 0px; width: 100%; height: 100%; top: 0px; left: 0px; opacity: 0.6; cursor: default; position: absolute; background: url(<?php echo WC()->plugin_url(); ?>/assets/images/ajax-loader@2x.gif) 50% 50% / 16px 16px no-repeat rgb(255, 255, 255);"></div>
            <?php
            echo "<div class='clear'></div></div>";
		}
    }

    static function get_button_locale_code(){
        $locale_code = defined("WPLANG") && WPLANG != '' ? WPLANG : 'en_US';
        switch(WPLANG) {
            case "de_DE": $locale_code = "de_DE/DE"; break;
            case ""     : $locale_code = "en_US"; break;
            default: $locale_code = WPLANG;
        }
        return $locale_code;
    }

    /**
     * Process a refund if supported
     * @param  int $order_id
     * @param  float $amount
     * @param  string $reason
     * @return  bool|wp_error True or false based on success, or a WP_Error object
     */
    public function process_refund( $order_id, $amount = null, $reason = '' ) {
        $order = wc_get_order( $order_id );
        $this->add_log( 'Begin Refund' );
        $this->add_log( 'Order: '. print_r($order, true) );
        $this->add_log( 'Transaction ID: '. print_r($order->get_transaction_id(), true) );
        $this->add_log( 'API Username: '. print_r($this->api_username, true) );
        $this->add_log( 'API Password: '. print_r($this->api_password, true) );
        $this->add_log( 'API Signature: '. print_r($this->api_signature, true) );
        if ( ! $order || ! $order->get_transaction_id() || ! $this->api_username || ! $this->api_password || ! $this->api_signature ) {
            return false;
        }
        $this->add_log('Include Class Request' );
        /*
         * Check if the PayPal class has already been established.
         */
        if(!class_exists('PayPal' ))
        {
            require_once( 'lib/angelleye/paypal-php-library/includes/paypal.class.php' );
        }

        /*
         * Create PayPal object.
         */
        $PayPalConfig = array(
            'Sandbox' => $this->testmode == 'yes' ? TRUE : FALSE,
            'APIUsername' => $this->api_username,
            'APIPassword' => $this->api_password,
            'APISignature' => $this->api_signature
        );
        $PayPal = new Angelleye_PayPal($PayPalConfig);
        if ( $reason ) {
            if ( 255 < strlen( $reason ) ) {
                $reason = substr( $reason, 0, 252 ) . '...';
            }

            $reason = html_entity_decode( $reason, ENT_NOQUOTES, 'UTF-8' );
        }

        // Prepare request arrays
        $RTFields = array(
            'transactionid' => $order->get_transaction_id(), 							// Required.  PayPal transaction ID for the order you're refunding.
            'payerid' => '', 								// Encrypted PayPal customer account ID number.  Note:  Either transaction ID or payer ID must be specified.  127 char max
            'invoiceid' => '', 								// Your own invoice tracking number.
            'refundtype' => $order->get_total() == $amount ? 'Full' : 'Partial', 							// Required.  Type of refund.  Must be Full, Partial, or Other.
            'amt' => number_format( $amount, 2, '.', '' ), 									// Refund Amt.  Required if refund type is Partial.
            'currencycode' => $order->get_order_currency(), 							// Three-letter currency code.  Required for Partial Refunds.  Do not use for full refunds.
            'note' => $reason,  									// Custom memo about the refund.  255 char max.
            'retryuntil' => '', 							// Maximum time until you must retry the refund.  Note:  this field does not apply to point-of-sale transactions.
            'refundsource' => '', 							// Type of PayPal funding source (balance or eCheck) that can be used for auto refund.  Values are:  any, default, instant, eCheck
            'merchantstoredetail' => '', 					// Information about the merchant store.
            'refundadvice' => '', 							// Flag to indicate that the buyer was already given store credit for a given transaction.  Values are:  1/0
            'refunditemdetails' => '', 						// Details about the individual items to be returned.
            'msgsubid' => '', 								// A message ID used for idempotence to uniquely identify a message.
            'storeid' => '', 								// ID of a merchant store.  This field is required for point-of-sale transactions.  50 char max.
            'terminalid' => ''								// ID of the terminal.  50 char max.
        );

        $PayPalRequestData = array('RTFields'=>$RTFields);
        $this->add_log('Refund Request: '.print_r( $PayPalRequestData, true ) );
        // Pass data into class for processing with PayPal and load the response array into $PayPalResult
        $PayPalResult = $PayPal->RefundTransaction($PayPalRequestData);
        $this->add_log('Refund Information: '.print_r( $PayPalResult, true ) );
        if($PayPal->APICallSuccessful($PayPalResult['ACK']))
        {
            $order->add_order_note( 'Refund Transaction ID:'. $PayPalResult['REFUNDTRANSACTIONID'] );
            $order->update_status( 'refunded' );
            return true;
        }else{
            $ec_message = apply_filters( 'angelleye_ec_refund_message', $PayPalResult['L_LONGMESSAGE0'], $PayPalResult['L_ERRORCODE'], $PayPalResult );
            return new WP_Error( 'ec_refund-error', $ec_message );
        }

    }
    function top_cart_button(){
        if( !empty($this->settings['button_position']) && ($this->settings['button_position'] == 'top' || $this->settings['button_position'] == 'both')){
            $this->woocommerce_paypal_express_checkout_button_angelleye();
        }
    }

    /**
     * Regular checkout process
     */
    function regular_checkout($posted) {
        if ($posted['payment_method'] == 'paypal_express' && wc_notice_count( 'error' ) == 0 ) {
            $this->paypal_express_checkout($posted);
        }
        return;
    }
}
