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
		$this->supports = array(
		'products',
		'refunds'
		);
		// Load the form fields
		$this->init_form_fields();
		// Load the settings.
		$this->init_settings();
		// Get setting values
		$this->enabled = $this->settings['enabled'];
		$this->title = $this->settings['title'];
		$this->description = $this->settings['description'];
		$this->api_username = $this->settings['api_username'];
		$this->api_password = $this->settings['api_password'];
		$this->api_signature = $this->settings['api_signature'];
		$this->testmode = $this->settings['testmode'];
		$this->debug = $this->settings['debug'];
		$this->error_email_notify = isset($this->settings['error_email_notify']) && $this->settings['error_email_notify'] == 'yes' ? true : false;
		$this->invoice_id_prefix = isset($this->settings['invoice_id_prefix']) ? $this->settings['invoice_id_prefix'] : '';
		//$this->checkout_with_pp_button = $this->settings['checkout_with_pp_button'];
		//$this->hide_checkout_button    = $this->settings['hide_checkout_button'];
		$this->show_on_checkout = isset($this->settings['show_on_checkout']) ? $this->settings['show_on_checkout'] : 'both';
		$this->paypal_account_optional = isset($this->settings['paypal_account_optional']) ? $this->settings['paypal_account_optional'] : '';
		$this->error_display_type = isset($this->settings['error_display_type']) ? $this->settings['error_display_type'] : '';
		$this->landing_page = isset($this->settings['landing_page']) ? $this->settings['landing_page'] : '';
		$this->checkout_logo = isset($this->settings['checkout_logo']) ? $this->settings['checkout_logo'] : '';
		$this->checkout_logo_hdrimg = isset($this->settings['checkout_logo_hdrimg']) ? $this->settings['checkout_logo_hdrimg'] : '';
		$this->show_paypal_credit = isset($this->settings['show_paypal_credit']) ? $this->settings['show_paypal_credit'] : '';
		$this->brand_name = isset($this->settings['brand_name']) ? $this->settings['brand_name'] : '';
		$this->customer_service_number = isset($this->settings['customer_service_number']) ? $this->settings['customer_service_number'] : '';
		$this->gift_wrap_enabled = isset($this->settings['gift_wrap_enabled']) ? $this->settings['gift_wrap_enabled'] : '';
		$this->gift_message_enabled = isset($this->settings['gift_message_enabled']) ? $this->settings['gift_message_enabled'] : '';
		$this->gift_receipt_enabled = isset($this->settings['gift_receipt_enabled']) ? $this->settings['gift_receipt_enabled'] : '';
		$this->gift_wrap_name = isset($this->settings['gift_wrap_name']) ? $this->settings['gift_wrap_name'] : '';
		$this->gift_wrap_amount = isset($this->settings['gift_wrap_amount']) ? $this->settings['gift_wrap_amount'] : '';
		$this->use_wp_locale_code = isset($this->settings['use_wp_locale_code']) ? $this->settings['use_wp_locale_code'] : '';
		$this->angelleye_skip_text = isset($this->settings['angelleye_skip_text']) ? $this->settings['angelleye_skip_text'] : '';
		$this->skip_final_review = isset($this->settings['skip_final_review']) ? $this->settings['skip_final_review'] : '';
		$this->payment_action = isset($this->settings['payment_action']) ? $this->settings['payment_action'] : 'Sale';
		$this->billing_address = isset($this->settings['billing_address']) ? $this->settings['billing_address'] : 'no';
		$this->send_items = isset($this->settings['send_items']) && $this->settings['send_items'] == 'yes' ? true : false;
		$this->customer_id = get_current_user_id();

		/*
		' Define the PayPal Redirect URLs.
		' 	This is the URL that the buyer is first sent to do authorize payment with their paypal account
		' 	change the URL depending if you are testing on the sandbox or the live PayPal site
		'
		' For the sandbox, the URL is       https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=
		' For the live site, the URL is     https://www.paypal.com/webscr&cmd=_express-checkout&token=
		*/
		if ($this->testmode == 'yes') {
			$this->API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
			$this->PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
			$this->api_username = $this->settings['sandbox_api_username'];
			$this->api_password = $this->settings['sandbox_api_password'];
			$this->api_signature = $this->settings['sandbox_api_signature'];
		} else {
			$this->API_Endpoint = "https://api-3t.paypal.com/nvp";
			$this->PAYPAL_URL = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
		}
		$this->version = "64";  // PayPal SetExpressCheckout API version
		// Actions
		add_action('woocommerce_api_' . strtolower(get_class()), array($this, 'paypal_express_checkout'), 12);
		add_action('woocommerce_receipt_paypal_express', array($this, 'receipt_page'));

		//Save settings
		add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));


		if ($this->enabled == 'yes' && ($this->show_on_checkout == 'top' || $this->show_on_checkout == 'both'))
		add_action('woocommerce_before_checkout_form', array($this, 'checkout_message'), 5);
		add_action('woocommerce_ppe_do_payaction', array($this, 'get_confirm_order'));
		add_action('woocommerce_after_checkout_validation', array($this, 'regular_checkout'));
		add_action('woocommerce_before_cart_table', array($this, 'top_cart_button'));

	}


	/**
     * get_icon function.
     *
     * @access public
     * @return string
     */
	public function get_icon() {

		$image_path = plugins_url() . "/" . plugin_basename(dirname(dirname(__FILE__))) . '/assets/images/paypal.png';
		if ($this->show_paypal_credit == 'yes') {
			$image_path = plugins_url() . "/" . plugin_basename(dirname(dirname(__FILE__))) . '/assets/images/paypal-credit.png';
		}
		$icon = "<img src=\"$image_path\" alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
		return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
	}

	/** Override this method so this gateway does not appear on checkout page
     *
     * @since 1.0.0
     */
	public function admin_options() {
        ?>

        <h3><?php echo isset($this->method_title) ? $this->method_title : __('Settings', 'paypal-for-woocommerce'); ?></h3>
        <?php echo isset($this->method_description) ? wpautop($this->method_description) : ''; ?>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>
        <?php
        $this->scriptAdminOption();
	}

	public function scriptAdminOption() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function ($) {
        	$("#woocommerce_paypal_express_customer_service_number").attr("maxlength", "16");
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
        		} else if ($(this).val() == "textbutton") {
        			jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_text_button').each(function (i, el) {
        				jQuery(el).closest('tr').show();
        			});
        			jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_my_custom').each(function (i, el) {
        				jQuery(el).closest('tr').hide();
        			});
        		} else {
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
        			title: '<?php _e('Choose Image', 'paypal-for-woocommerce'); ?>',
        			button: {
        				text: '<?php _e('Choose Image', 'paypal-for-woocommerce'); ?>'
        			},
        			multiple: false
        		});
        		//When a file is selected, grab the URL and set it as the text field's value
        		custom_uploader.on('select', function () {
        			var attachment = custom_uploader.state().get('selection').first().toJSON();
        			var pre_input = BTthis.prev();
        			var url = attachment.url;
        			if (BTthis.attr('id') != 'upload') {
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

	public function get_confirm_order($order) {
		$this->confirm_order_id = $order->id;
	}

	function is_available() {
		if ($this->enabled == 'yes' && ( $this->show_on_checkout == 'regular' || $this->show_on_checkout == 'both'))
		return true;
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
     * Check if site is SSL ready
     *
     */
	function is_ssl() {
		if (is_ssl() || get_option('woocommerce_force_ssl_checkout') == 'yes' || class_exists('WordPressHTTPS'))
		return true;
		return false;
	}

	/**
     * Initialize Gateway Settings Form Fields
     */
	function init_form_fields() {

		$require_ssl = '';
		if (!$this->is_ssl()) {
			$require_ssl = __('This image requires an SSL host.  Please upload your image to <a target="_blank" href="http://www.sslpic.com">www.sslpic.com</a> and enter the image URL here.', 'paypal-for-woocommerce');
		}

		$woocommerce_enable_guest_checkout = get_option('woocommerce_enable_guest_checkout');
		if (isset($woocommerce_enable_guest_checkout) && ( $woocommerce_enable_guest_checkout === "no" )) {
			$skip_final_review_option_not_allowed = ' (This is not available because your WooCommerce orders require an account.)';
		} else {
			$skip_final_review_option_not_allowed = '';
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
		'label' => __('Enable logging <code>/wp-content/uploads/wc-logs/paypal_express-{tag}.log</code>', 'paypal-for-woocommerce'),
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
									and helps to prevent fraudulant activity on your site.', 'paypal-for-woocommerce')
									),
									'show_paypal_credit' => array(
									'title' => __('Enable PayPal Credit', 'paypal-for-woocommerce'),
									'type' => 'checkbox',
									'label' => __('Show the PayPal Credit button next to the Express Checkout button.', 'paypal-for-woocommerce'),
									'default' => 'yes'
									),
									'use_wp_locale_code' => array(
									'title' => __('Use WordPress Locale Code', 'paypal-for-woocommerce'),
									'type' => 'checkbox',
									'label' => __('Pass the WordPress Locale Code setting to PayPal in order to display localized PayPal pages to buyers.', 'paypal-for-woocommerce'),
									'default' => 'yes'
									),
									'brand_name' => array(
									'title' => __('Brand Name', 'paypal-for-woocommerce'),
									'type' => 'text',
									'description' => __('This controls what users see as the brand / company name on PayPal review pages.', 'paypal-for-woocommerce'),
									'default' => __(get_bloginfo('name'), 'paypal-for-woocommerce')
									),
									'checkout_logo' => array(
									'title' => __('PayPal Checkout Logo (190x90px)', 'paypal-for-woocommerce'),
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
									'description' => __('By default, users will be returned from PayPal and presented with a final review page which includes shipping and tax in the order details.  Enable this option to eliminate this page in the checkout process.' . $skip_final_review_option_not_allowed),
									'type' => 'checkbox',
									'default' => 'no'
									),
									'payment_action' => array(
									'title' => __('Payment Action', 'paypal-for-woocommerce'),
									'label' => __('Whether to process as a Sale or Authorization.', 'paypal-for-woocommerce'),
									'description' => __('Sale will capture the funds immediately when the order is placed.  Authorization will authorize the payment but will not capture the funds.  You would need to capture funds through your PayPal account when you are ready to deliver.'),
									'type' => 'select',
									'options' => array(
									'Sale' => 'Sale',
									'Authorization' => 'Authorization',
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
									'shipment_required' => array(
										'title' => __('Shipping', 'paypal-for-woocommerce'),
										'label' => __('Enable shipping address', 'paypal-for-woocommerce'),
										'type' => 'checkbox',
										'description' => 'This will show/hide the shipping address on Paypal. For digital and virtual products, it is helpful to hide shipping address.',
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
			switch ($pp_settings['checkout_with_pp_button_type']) {
				case "textbutton":
					if (!empty($pp_settings['pp_button_type_text_button'])) {
						$button_text = $pp_settings['pp_button_type_text_button'];
					} else {
						$button_text = __('Proceed to Checkout', 'woocommerce');
					}
					echo '<a class="paypal_checkout_button paypal_checkout_button_text button alt" href="' . esc_url(add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">' . $button_text . '</a>';
					break;
				case "paypalimage":
					echo '<div id="paypal_ec_button">';
					echo '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">';
					echo "<img src='https://www.paypal.com/" . WC_Gateway_PayPal_Express_AngellEYE::get_button_locale_code() . "/i/btn/btn_xpressCheckout.gif' border='0' alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
					echo "</a>";
					echo '</div>';
					break;
				case "customimage":
					$button_img = $pp_settings['pp_button_type_my_custom'];
					echo '<div id="paypal_ec_button">';
					echo '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">';
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
				$paypal_credit_button_markup .= '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('use_paypal_credit', 'true', add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/'))))) . '" >';
				$paypal_credit_button_markup .= "<img src='https://www.paypalobjects.com/webstatic/en_US/i/buttons/ppcredit-logo-small.png' alt='Check out with PayPal Credit'/>";
				$paypal_credit_button_markup .= '</a>';
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
            <div class="blockUI blockOverlay angelleyeOverlay" style="display:none;z-index: 1000; border: none; margin: 0px; padding: 0px; width: 100%; height: 100%; top: 0px; left: 0px; opacity: 0.6; cursor: default; position: absolute; background: url(<?php echo WC()->plugin_url(); ?>/assets/images/select2-spinner.gif) 50% 50% / 16px 16px no-repeat rgb(255, 255, 255);"></div>
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

				$order_id = WC()->checkout()->create_order();

				$order = new WC_Order($order_id);





				if (isset($_SESSION['line_item'])) {
					unset($_SESSION['line_item']);
				}

				$lineitems_prepare = $this->prepare_line_items($order);
				$lineitems = $_SESSION['line_item'];
				//$paymentAmount    = WC()->cart->get_total();
				$order_obj = $order->get_order_item_totals();

				$current_currency = get_woocommerce_currency_symbol(get_woocommerce_currency());

				$paymentAmount_amt = strip_tags($order_obj['order_total']['value']);

				$payment_exp_ary = explode(';', $paymentAmount_amt);
				$paymentAmount_amt_final_ec = str_replace($current_currency, '', $paymentAmount_amt);
				$paymentAmount_amt_final = str_replace(',', '', $paymentAmount_amt_final_ec);
				$paymentAmount = round($paymentAmount_amt_final, 2);

				if (isset($order->order_shipping_tax) && !empty($order->order_shipping_tax)) {
					update_post_meta($order_id,'shipping_taxamt_own',$order->order_shipping_tax);
				}

				$order->get_items();
				//Check if review order page is exist, otherwise re-create it on the fly
				$review_order_page_url = get_permalink(wc_get_page_id('review_order'));
				if (!$review_order_page_url) {
					$this->add_log(__('Review Order Page not found, re-create it. ', 'paypal-for-woocommerce'));
					include_once( WC()->plugin_path() . '/includes/admin/wc-admin-functions.php' );
					$page_id = wc_create_page(esc_sql(_x('review-order', 'page_slug', 'woocommerce')), 'woocommerce_review_order_page_id', __('Checkout &rarr; Review Order', 'paypal-for-woocommerce'), '[woocommerce_review_order]', wc_get_page_id('checkout'));
					$review_order_page_url = get_permalink($page_id);
				}
				$returnURL = urlencode(add_query_arg('pp_action', 'revieworder', $review_order_page_url));
				$cancelURL = isset($this->settings['cancel_page']) ? get_the_permalink($this->settings['cancel_page']) : WC()->cart->get_cart_url();
				$cancelURL = apply_filters('angelleye_express_cancel_url', urlencode($cancelURL));
				$resArray = $this->CallSetExpressCheckout($paymentAmount, $returnURL, $cancelURL, $usePayPalCredit, $posted, $lineitems, $order);
				$ack = strtoupper($resArray["ACK"]);

				/**
                 * I've replaced the original redirect URL's here with
                 * what the PayPal class library returns so that options like
                 * "skip details" will work correctly with PayPal's review pages.
                 */
				if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING") {

					$this->add_log('Redirecting to PayPal');
					if (isset($_SESSION['line_item'])) {
						unset($_SESSION['line_item']);
					}


					if (isset($_POST['terms']) && $_POST['terms'] =='on') {
						update_post_meta($order->id,'paypal_for_woocommerce_terms_on','yes');
					}

					if (isset($_POST['billing_address_1']) && !empty($_POST['billing_address_1'])&& (empty($_POST['createaccount']))) {
						update_post_meta($order->id,'paypal_for_woocommerce_create_act','yes');
					}



					$boolvar =  is_cart();
					if (is_ajax()) {
						$result = array(
						//'redirect' => $this->PAYPAL_URL . $resArray["TOKEN"],
						'redirect' => $resArray['REDIRECTURL'],
						'result' => 'success'
						);
						is_product();

						echo  json_encode($result);
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

						$error_email_notify_mes = apply_filters('angelleye_ec_error_email_notify_message', $message, $ErrorCode, $ErrorSeverityCode, $ErrorShortMsg, $ErrorLongMsg);
						$subject = "PayPal Express Checkout Error Notification";
						$error_email_notify_subject = apply_filters('angelleye_ec_error_email_notify_subject', $subject);

						wp_mail($admin_email, $error_email_notify_subject, $error_email_notify_mes);
					}

					// Generate error message based on Error Display Type setting
					if ($this->error_display_type == 'detailed') {
						$sec_error_notice = $ErrorCode . ' - ' . $ErrorLongMsg;
						$error_display_type_message = sprintf(__($sec_error_notice, 'paypal-for-woocommerce'));
					} else {
						$error_display_type_message = sprintf(__('There was a problem paying with PayPal.  Please try another method.', 'paypal-for-woocommerce'));
					}
					$error_display_type_message = apply_filters('angelleye_ec_display_type_message', $error_display_type_message, $ErrorCode, $ErrorLongMsg);
					wc_add_notice($error_display_type_message, 'error');
					if (!is_ajax()) {
						wp_redirect(get_permalink(wc_get_page_id('cart')));
						exit;
					} else
					return;
				}
			}
		}
		elseif ((isset($_GET['pp_action']) && $_GET['pp_action'] == 'revieworder')) {
			wc_clear_notices();
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
					WC()->customer->set_shipping_country($result['SHIPTOCOUNTRYCODE']);
				}

				if (isset($result['SHIPTONAME']))
				WC()->customer->shiptoname = $result['SHIPTONAME'];
				if (isset($result['SHIPTOSTREET']))
				WC()->customer->set_address($result['SHIPTOSTREET']);
				if (isset($result['SHIPTOCITY']))
				WC()->customer->set_city($result['SHIPTOCITY']);
				if (isset($result['SHIPTOCOUNTRYCODE']))
				WC()->customer->set_country($result['SHIPTOCOUNTRYCODE']);
				if (isset($result['SHIPTOSTATE']))
				WC()->customer->set_state($this->get_state_code($result['SHIPTOCOUNTRYCODE'], $result['SHIPTOSTATE']));
				if (isset($result['SHIPTOZIP']))
				WC()->customer->set_postcode($result['SHIPTOZIP']);
				if (isset($result['SHIPTOSTATE']))
				WC()->customer->set_shipping_state($this->get_state_code($result['SHIPTOCOUNTRYCODE'], $result['SHIPTOSTATE']));
				if (isset($result['SHIPTOZIP']))
				WC()->customer->set_shipping_postcode($result['SHIPTOZIP']);

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
				WC()->cart->calculate_totals();
			}
			else {
				$this->add_log("...ERROR: GetShippingDetails returned empty result");
			}
			if ($this->skip_final_review == 'yes' && get_option('woocommerce_enable_guest_checkout') === "yes" && wc_get_page_id( 'terms' ) < 0 ) {
				$url = add_query_arg(array('wc-api' => 'WC_Gateway_PayPal_Express_AngellEYE', 'pp_action' => 'payaction'), home_url());
				wp_redirect($url);
				exit();
			}

			$is_terms_on = get_post_meta($result['INVNUM'],'paypal_for_woocommerce_terms_on',true);
			if (($this->skip_final_review == 'yes' && get_option('woocommerce_enable_guest_checkout') === "no") && (wc_get_page_id( 'terms' ) > 0  && isset($is_terms_on) && !empty($is_terms_on))) {

				$url = add_query_arg(array('wc-api' => 'WC_Gateway_PayPal_Express_AngellEYE', 'pp_action' => 'payaction'), home_url());
				wp_redirect($url);
				exit();
			}

			if (isset($_POST['createaccount'])) {
				$this->customer_id = apply_filters('woocommerce_checkout_customer_id', get_current_user_id());
				if (empty($_POST['username'])) {
					wc_add_notice(__('Username is required', 'paypal-for-woocommerce'), 'error');
				} elseif (username_exists($_POST['username'])) {
					wc_add_notice(__('This username is already registered.', 'paypal-for-woocommerce'), 'error');
				} elseif (empty($_POST['email'])) {
					wc_add_notice(__('Please provide a valid email address.', 'paypal-for-woocommerce'), 'error');

				} elseif (empty($_POST['password']) || empty($_POST['repassword'])) {
					wc_add_notice(__('Password is required.', 'paypal-for-woocommerce'), 'error');

					wp_redirect(get_permalink(wc_get_page_id('cart')));
					exit();
				} elseif ($_POST['password'] != $_POST['repassword']) {
					wc_add_notice(__('Passwords do not match.', 'paypal-for-woocommerce'), 'error');
				} elseif (get_user_by('email', $_POST['email']) != false) {
					wc_add_notice(__('This email address is already registered.', 'paypal-for-woocommerce'), 'error');

					wp_redirect(get_permalink(wc_get_page_id('cart')));
					exit();
				} else {

					$username = !empty($_POST['username']) ? $_POST['username'] : '';
					$password = !empty($_POST['password']) ? $_POST['password'] : '';
					$email = $_POST['email'];

					try {

						// Anti-spam trap
						if (!empty($_POST['email_2'])) {
							throw new Exception(__('Anti-spam field was filled in.', 'woocommerce'));
							wc_add_notice('<strong>' . __('Anti-spam field was filled in.', 'paypal-for-woocommerce') . ':</strong> ', 'error');
						}

						$new_customer = wc_create_new_customer(sanitize_email($email), wc_clean($username), $password);

						if (is_wp_error($new_customer)) {
							wc_add_notice($user->get_error_message(), 'error');
						}

						if (apply_filters('paypal-for-woocommerce_registration_auth_new_customer', true, $new_customer)) {
							wc_set_customer_auth_cookie($new_customer);
						}

						$creds = array(
						'user_login' => wc_clean($username),
						'user_password' => $password,
						'remember' => true,
						);
						$user = wp_signon($creds, false);
						if (is_wp_error($user)) {
							wc_add_notice($user->get_error_message(), 'error');
						} else {
							wp_set_current_user($user->ID); //Here is where we update the global user variables
							$secure_cookie = is_ssl() ? true : false;
							wp_set_auth_cookie($user->ID, true, $secure_cookie);
						}
					} catch (Exception $e) {
						wc_add_notice('<strong>' . __('Error', 'paypal-for-woocommerce') . ':</strong> ' . $e->getMessage(), 'error');
					}

					$this->customer_id = $user->ID;

					// As we are now logged in, checkout will need to refresh to show logged in data
					WC()->session->set('reload_checkout', true);

					// Also, recalculate cart totals to reveal any role-based discounts that were unavailable before registering
					WC()->cart->calculate_totals();

					require_once("lib/NameParser.php");
					$parser = new FullNameParser();
					$split_name = $parser->split_full_name($result['SHIPTONAME']);
					$shipping_first_name = $split_name['fname'];
					$shipping_last_name = $split_name['lname'];
					$full_name = $split_name['fullname'];

					// Add customer info from other billing fields
					if (isset($result)) {
						update_user_meta($this->customer_id, 'first_name', isset($result['FIRSTNAME']) ? $result['FIRSTNAME'] : '');
						update_user_meta($this->customer_id, 'last_name', isset($result['LASTNAME']) ? $result['LASTNAME'] : '');
						update_user_meta($this->customer_id, 'shipping_first_name', $shipping_first_name);
						update_user_meta($this->customer_id, 'shipping_last_name', $shipping_last_name);
						update_user_meta($this->customer_id, 'shipping_company', isset($result['BUSINESS']) ? $result['BUSINESS'] : '' );
						update_user_meta($this->customer_id, 'shipping_address_1', isset($result['SHIPTOSTREET']) ? $result['SHIPTOSTREET'] : '');
						update_user_meta($this->customer_id, 'shipping_address_2', isset($result['SHIPTOSTREET2']) ? $result['SHIPTOSTREET2'] : '');
						update_user_meta($this->customer_id, 'shipping_city', isset($result['SHIPTOCITY']) ? $result['SHIPTOCITY'] : '' );
						update_user_meta($this->customer_id, 'shipping_postcode', isset($result['SHIPTOZIP']) ? $result['SHIPTOZIP'] : '');
						update_user_meta($this->customer_id, 'shipping_country', isset($result['SHIPTOCOUNTRYCODE']) ? $result['SHIPTOCOUNTRYCODE'] : '');
						update_user_meta($this->customer_id, 'shipping_state', isset($result['SHIPTOSTATE']) ? $result['SHIPTOSTATE'] : '' );
						$user_submit_form = maybe_unserialize(WC()->session->checkout_form);
						if ((isset($user_submit_form) && !empty($user_submit_form) && is_array($user_submit_form))) {
							update_user_meta($this->customer_id, 'billing_first_name', isset($user_submit_form['billing_first_name']) ? $user_submit_form['billing_first_name'] : $result['FIRSTNAME']);
							update_user_meta($this->customer_id, 'billing_last_name', isset($user_submit_form['billing_last_name']) ? $user_submit_form['billing_last_name'] : $result['LASTNAME']);
							update_user_meta($this->customer_id, 'billing_address_1', isset($user_submit_form['billing_address_1']) ? $user_submit_form['billing_address_1'] : $result['SHIPTOSTREET']);
							update_user_meta($this->customer_id, 'billing_address_2', isset($user_submit_form['billing_address_2']) ? $user_submit_form['billing_address_2'] : $result['SHIPTOSTREET2']);
							update_user_meta($this->customer_id, 'billing_city', isset($user_submit_form['billing_city']) ? $user_submit_form['billing_city'] : $result['SHIPTOCITY']);
							update_user_meta($this->customer_id, 'billing_postcode', isset($user_submit_form['billing_postcode']) ? $user_submit_form['billing_postcode'] : $result['SHIPTOZIP']);
							update_user_meta($this->customer_id, 'billing_country', isset($user_submit_form['billing_country']) ? $user_submit_form['billing_country'] : $result['SHIPTOCOUNTRYCODE']);
							update_user_meta($this->customer_id, 'billing_state', isset($user_submit_form['billing_state']) ? $user_submit_form['billing_state'] : $result['SHIPTOSTATE']);
							update_user_meta($this->customer_id, 'billing_phone', isset($user_submit_form['billing_phone']) ? $user_submit_form['billing_phone'] : $result['PHONENUM']);
							update_user_meta($this->customer_id, 'billing_email', isset($user_submit_form['billing_email']) ? $user_submit_form['billing_email'] : $result['EMAIL']);
						} else {
							update_user_meta($this->customer_id, 'billing_first_name', $shipping_first_name);
							update_user_meta($this->customer_id, 'billing_last_name', $shipping_last_name);
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
			}
		} elseif (isset($_GET['pp_action']) && $_GET['pp_action'] == 'payaction') {
			if (isset($_POST) || ($this->skip_final_review == 'yes' && get_option('woocommerce_enable_guest_checkout') === "yes") ) {

				// Update customer shipping and payment method to posted method
				$chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');

				if (isset($_POST['shipping_method']) && is_array($_POST['shipping_method'])) {
					foreach ($_POST['shipping_method'] as $i => $value){
						$chosen_shipping_methods[$i] = wc_clean($value);
					}
					if (isset($value) && !empty($value)) {
						$selected_shipping_method = wc_clean($value);
					}
					WC()->session->set('chosen_shipping_methods', $chosen_shipping_methods);
				}
				if (WC()->cart->needs_shipping()) {
					// Validate Shipping Methods
					$packages = WC()->shipping->get_packages();
					WC()->checkout()->shipping_methods = WC()->session->get('chosen_shipping_methods');
				}


				$this->add_log('Start Pay Action');
				if (!defined('WOOCOMMERCE_CHECKOUT'))
				define('WOOCOMMERCE_CHECKOUT', true);
				WC()->cart->calculate_totals();

				$resultarray = array();
				$resultarray = maybe_unserialize(WC()->session->result);
				$order_id = $resultarray['PAYMENTS'][0]['INVNUM'];


				/**
                 * Update meta data with session data
                 */
				// Parse SHIPTONAME to fist and last name
				require_once("lib/NameParser.php");
				$parser = new FullNameParser();
				$split_name = $parser->split_full_name($this->get_session('shiptoname'));
				$shipping_first_name = $split_name['fname'];
				$shipping_last_name = $split_name['lname'];
				$full_name = $split_name['fullname'];

				$this->set_session('firstname', isset($result['FIRSTNAME']) ? $result['FIRSTNAME'] : $shipping_first_name);
				$this->set_session('lastname', isset($result['LASTNAME']) ? $result['LASTNAME'] : $shipping_last_name);

				/* create account start */

				if (isset($_POST['create_act']) && !empty($_POST['create_act'])) {

					$this->customer_id = apply_filters('woocommerce_checkout_customer_id', get_current_user_id());

					$create_user_email = $resultarray['EMAIL'];
					//$create_user_name = $full_name;

					$create_user_name = sanitize_user( current( explode( '@', $create_user_email ) ), true );

					// Ensure username is unique
					$append     = 1;
					$o_username = $create_user_name;

					while ( username_exists( $create_user_name ) ) {
						$create_user_name = $o_username . $append;
						$append ++;
					}


					if (empty($create_user_name)) {
						wc_add_notice(__('Username is required', 'paypal-for-woocommerce'), 'error');
						wp_redirect(get_permalink(wc_get_page_id('cart')));
						exit();
					} elseif (username_exists($create_user_name)) {
						wc_add_notice(__('This username is already registered.', 'paypal-for-woocommerce'), 'error');
						wp_redirect(get_permalink(wc_get_page_id('cart')));
						exit();
					} elseif (empty($create_user_email)) {
						wc_add_notice(__('Please provide a valid email address.', 'paypal-for-woocommerce'), 'error');
						wp_redirect(get_permalink(wc_get_page_id('cart')));
						exit();
					} elseif (empty($_POST['create_act']) || empty($_POST['create_act'])) {
						wc_add_notice(__('Password is required.', 'paypal-for-woocommerce'), 'error');
						wp_redirect(get_permalink(wc_get_page_id('cart')));
						exit();

					} elseif ($_POST['create_act'] != $_POST['create_act']) {
						wc_add_notice(__('Passwords do not match.', 'paypal-for-woocommerce'), 'error');
						wp_redirect(get_permalink(wc_get_page_id('cart')));
						exit();

					} elseif (get_user_by('email', $create_user_email) != false) {
						wc_add_notice(__('This email address is already registered.', 'paypal-for-woocommerce'), 'error');
						wp_redirect(get_permalink(wc_get_page_id('cart')));
						exit();


					} else {

						$username = !empty($create_user_name) ? $create_user_name : '';
						$password = !empty($_POST['create_act']) ? $_POST['create_act'] : '';
						$email = $create_user_email;

						try {

							// Anti-spam trap
							if (!empty($_POST['email_2'])) {
								throw new Exception(__('Anti-spam field was filled in.', 'woocommerce'));
								wc_add_notice('<strong>' . __('Anti-spam field was filled in.', 'paypal-for-woocommerce') . ':</strong> ', 'error');
							}

							$new_customer = wc_create_new_customer(sanitize_email($email), wc_clean($username), $password);

							if (is_wp_error($new_customer)) {
								wc_add_notice($user->get_error_message(), 'error');
							}

							if (apply_filters('paypal-for-woocommerce_registration_auth_new_customer', true, $new_customer)) {
								wc_set_customer_auth_cookie($new_customer);
							}

							$creds = array(
							'user_login' => wc_clean($username),
							'user_password' => $password,
							'remember' => true,
							);
							$user = wp_signon($creds, false);
							if (is_wp_error($user)) {

								wc_add_notice($user->get_error_message(), 'error');
								wp_redirect(get_permalink(wc_get_page_id('cart')));
								exit();
							} else {
								wp_set_current_user($user->ID); //Here is where we update the global user variables
								$secure_cookie = is_ssl() ? true : false;
								wp_set_auth_cookie($user->ID, true, $secure_cookie);
							}
						} catch (Exception $e) {
							wc_add_notice('<strong>' . __('Error', 'paypal-for-woocommerce') . ':</strong> ' . $e->getMessage(), 'error');
						}

						$this->customer_id = $user->ID;
					}
				}



				update_post_meta($order_id, '_payment_method', $this->id);
				update_post_meta($order_id, '_payment_method_title', $this->title);

				if (is_user_logged_in()) {
					$userLogined = wp_get_current_user();
					update_post_meta($order_id, '_billing_email', $userLogined->user_email);
					update_post_meta($order_id, '_customer_user', $userLogined->ID);
				} else {
					update_post_meta($order_id, '_billing_email', $this->get_session('payeremail'));
				}

				$checkout_form_data = maybe_unserialize($this->get_session('checkout_form'));
				if ((isset($this->billing_address) && $this->billing_address =='yes' )&& (empty($checkout_form_data['billing_country']))) {
					$checkout_form_data = array();
				}
				if (isset($checkout_form_data) && !empty($checkout_form_data)) {
					foreach ($checkout_form_data as $key => $value) {
						if (strpos($key, 'billing_') !== false && !empty($value) && !is_array($value)) {
							if ($checkout_form_data['ship_to_different_address'] == false) {
								$shipping_key = str_replace('billing_', 'shipping_', $key);
								update_user_meta($this->customer_id, $shipping_key, $value);
								update_post_meta($order_id, '_' . $shipping_key, $value);
							}
							update_user_meta($this->customer_id, $key, $value);
							update_post_meta($order_id, '_' . $key, $value);
						} elseif (WC()->cart->needs_shipping() && strpos($key, 'shipping_') !== false && !empty($value) && !is_array($value)) {
							update_user_meta($this->customer_id, $key, $value);
							update_post_meta($order_id, '_' . $key, $value);
						}
					}
					do_action('woocommerce_checkout_update_user_meta', $this->customer_id, $checkout_form_data);
				} else {
					update_post_meta($order_id, '_shipping_first_name', $this->get_session('firstname'));
					update_post_meta($order_id, '_shipping_last_name', $this->get_session('lastname'));
					update_post_meta($order_id, '_shipping_full_name', $full_name);
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
						update_post_meta($order_id, '_billing_first_name', $this->get_session('firstname'));
						update_post_meta($order_id, '_billing_last_name', $this->get_session('lastname'));
						update_post_meta($order_id, '_billing_full_name', $full_name);
						update_post_meta($order_id, '_billing_company', $this->get_session('company'));
						update_post_meta($order_id, '_billing_address_1', $this->get_session('shiptostreet'));
						update_post_meta($order_id, '_billing_address_2', $this->get_session('shiptostreet2'));
						update_post_meta($order_id, '_billing_city', $this->get_session('shiptocity'));
						update_post_meta($order_id, '_billing_postcode', $this->get_session('shiptozip'));
						update_post_meta($order_id, '_billing_country', $this->get_session('shiptocountrycode'));
						update_post_meta($order_id, '_billing_state', $this->get_state_code($this->get_session('shiptocountrycode'), $this->get_session('shiptostate')));
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
					//  $order->add_order_note(__('Customer Notes: ', 'paypal-for-woocommerce') . $this->get_session('customer_notes'));
					$checkout_note = array(
					'ID' => $order_id,
					'post_excerpt' => $this->get_session('customer_notes'),
					);
					wp_update_post($checkout_note);
					$checkout_form_data['order_comments'] = '';
					unset($checkout_form_data['order_comments']);
				}
				if (isset($checkout_form_data) && !empty($checkout_form_data['order_comments'])) {
					// Update post 37
					$checkout_note = array(
					'ID' => $order_id,
					'post_excerpt' => $checkout_form_data['order_comments'],
					);
					wp_update_post($checkout_note);
					$checkout_form_data['order_comments'] = '';
					unset($checkout_form_data['order_comments']);
				}
				// Update the post into the database
				if (isset($my_post) && !empty($my_post)) {
					wp_update_post($my_post);
				}
				if ($result['ACK'] == 'Success' || $result['ACK'] == 'SuccessWithWarning') {
					$this->add_log('Payment confirmed with PayPal successfully');
					$result = apply_filters('woocommerce_payment_successful_result', $result);

					/**
                     * Gift Wrap Notes
                     */
					if ($this->get_session('giftwrapamount') != '') {
						$giftwrap_note = __('Gift Wrap Added', 'paypal-for-woocommerce');
						$giftwrap_note .= $this->get_session('giftwrapname') != '' ? ' - ' . $this->get_session('giftwrapname') : '';
						$giftwrap_note .= $this->get_session('giftmessage') != '' ? '<br />Message: ' . $this->get_session('giftmessage') : '';
						$giftwrap_note .= '<br />' . __('Use Gift Receipt?: ', 'paypal-for-woocommerce');
						$giftwrap_note .= strtolower($this->get_session('giftreceiptenable')) == 'true' ? 'Yes' : 'No';
						//$giftwrap_note .= '<br />Fee: ' . woocommerce_price(number_format($this->get_session('giftwrapamount'),2));
						$order->add_order_note($giftwrap_note);
					}

					$order->add_order_note(__('PayPal Express payment completed', 'paypal-for-woocommerce') .
					' ( Response Code: ' . $result['ACK'] . ", " .
					' TransactionID: ' . $result['PAYMENTINFO_0_TRANSACTIONID'] . ' )');
					$REVIEW_RESULT = unserialize($this->get_session('RESULT'));
					$payerstatus_note = __('Payer Status: ', 'paypal-for-woocommerce');
					$payerstatus_note .= ucfirst($REVIEW_RESULT['PAYERSTATUS']);
					$order->add_order_note($payerstatus_note);
					$addressstatus_note = __('Address Status: ', 'paypal-for-woocommerce');
					$addressstatus_note .= ucfirst($REVIEW_RESULT['ADDRESSSTATUS']);
					$order->add_order_note($addressstatus_note);
					$order->payment_complete($result['PAYMENTINFO_0_TRANSACTIONID']);

					//add hook
					do_action('woocommerce_checkout_order_processed', $order_id);

					// Empty the Cart
					WC()->cart->empty_cart();
					/* if (isset($this->get_session('customer_notes')) && !empty($this->get_session('customer_notes'))) {
					$this->get_session('customer_notes') == '';
					} */

					global $wpdb,$table_prefix;
					$table_name = $table_prefix . "woocommerce_order_items";
					$table_name_meta = $table_prefix . "woocommerce_order_itemmeta";
					$get_exits_record = $wpdb->get_row("select count(*)as cnt from $table_name where order_id='{$order_id}' and order_item_type ='shipping'");
					$count_record = $get_exits_record->cnt;
					if (isset($selected_shipping_method) && !empty($selected_shipping_method)) {
						$selected_shipping_method_id = $selected_shipping_method;
					}else {
						$selected_shipping_method_id = '';
					}

					if (isset($resultarray['SHIPPINGAMT']) && !empty($resultarray['SHIPPINGAMT'])) {

						if (isset($count_record) && empty($count_record) && $count_record <=0) {

							$wpdb->query( $wpdb->prepare( "INSERT INTO $table_name(order_item_name, order_item_type,order_id )VALUES (%s, %s, %s )", $selected_shipping_method_id, 'shipping', $order_id ) );
							$shipping_amt = $resultarray['SHIPPINGAMT'];
							$get_metadataid = $wpdb->get_row("select * from $table_name where order_id='{$order_id}' and order_item_type ='shipping'");
							$order_itemid = $get_metadataid->order_item_id;

							wc_add_order_item_meta( $order_itemid, 'method_id', $selected_shipping_method_id );

							wc_add_order_item_meta( $order_itemid, 'cost', wc_format_decimal( $shipping_amt ) );





							$shipping_post_meta = get_post_meta($order_id,'shipping_taxamt_own',true);
							if (isset($shipping_post_meta) && !empty($shipping_post_meta)) {
								wc_add_order_item_meta( $order_itemid, 'shipping_tax_amount', round( $shipping_post_meta,2 ) );
							}


							$get_shippingtaxamt = $wpdb->get_row("select * from $table_name_meta where order_item_id='{$order_itemid}' and meta_key ='shipping_tax_amount'");
							if (isset($get_shippingtaxamt->meta_value) && !empty($get_shippingtaxamt->meta_value)) {
								$get_shippingtaxamtkey_amt = round($get_shippingtaxamt->meta_value,2);
							}

							if (isset($get_shippingtaxamtkey_amt) && !empty($get_shippingtaxamtkey_amt)) {
								$shippingtaxamt = 'a:1:{i:1;s:10:"'.$get_shippingtaxamtkey_amt.'";}';
							} else {
								$shippingtaxamt = 'a:1:{i:1;s:10:"0.00";}';
							}

							wc_add_order_item_meta( $order_itemid, 'taxes', $shippingtaxamt );



						}

					}
					wp_redirect($this->get_return_url($order));
					exit();
				} else {
					$this->add_log('...Error confirming order ' . $order_id . ' with PayPal');
					$this->add_log('...response:' . print_r($result, true));

					// Display a user friendly Error on the page and log details
					$ErrorCode = urldecode($result["L_ERRORCODE0"]);
					$ErrorShortMsg = urldecode($result["L_SHORTMESSAGE0"]);
					$ErrorLongMsg = urldecode($result["L_LONGMESSAGE0"]);
					$ErrorSeverityCode = urldecode($result["L_SEVERITYCODE0"]);
					$this->add_log('SetExpressCheckout API call failed. ');
					$this->add_log('Detailed Error Message: ' . $ErrorLongMsg);
					$this->add_log('Short Error Message: ' . $ErrorShortMsg);
					$this->add_log('Error Code: ' . $ErrorCode);
					$this->add_log('Error Severity Code: ' . $ErrorSeverityCode);
					if ($ErrorCode == '10486') {
						$this->RedirectToPayPal($this->get_session('TOKEN'));
					}

					// Notice admin if has any issue from PayPal
					$message = '';

					if ($this->error_email_notify) {
						$admin_email = get_option("admin_email");
						$message .= __("DoExpressCheckoutPayment API call failed.", "paypal-for-woocommerce") . "\n\n";
						$message .= __('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode . "\n";
						$message .= __('Error Severity Code: ', 'paypal-for-woocommerce') . $ErrorSeverityCode . "\n";
						$message .= __('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg . "\n";
						$message .= __('Detailed Error Message: ', 'paypal-for-woocommerce') . $ErrorLongMsg . "\n";
						$message .= __('Order ID: ') . $order_id . "\n";
						$message .= __('Customer Name: ') . $this->get_session('shiptoname') . "\n";
						$message .= __('Customer Email: ') . $this->get_session('payeremail') . "\n";

						$error_email_notify_mes = apply_filters('angelleye_ec_error_email_notify_message', $message, $ErrorCode, $ErrorSeverityCode, $ErrorShortMsg, $ErrorLongMsg);
						$subject = "PayPal Express Checkout Error Notification";
						$error_email_notify_subject = apply_filters('angelleye_ec_error_email_notify_subject', $subject);

						wp_mail($admin_email, $error_email_notify_subject, $error_email_notify_mes);
					}

					// Generate error message based on Error Display Type setting
					if ($this->error_display_type == 'detailed') {
						$sec_error_notice = $ErrorCode . ' - ' . $ErrorLongMsg;
						$error_display_type_message = sprintf(__($sec_error_notice, 'paypal-for-woocommerce'));
					} else {
						$error_display_type_message = sprintf(__('There was a problem paying with PayPal.  Please try another method.', 'paypal-for-woocommerce'));
					}
					$error_display_type_message = apply_filters('angelleye_ec_display_type_message', $error_display_type_message, $ErrorCode, $ErrorLongMsg);
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
	function CallSetExpressCheckout($paymentAmount, $returnURL, $cancelURL, $usePayPalCredit = false, $posted, $lineitems, $order) {
		/*
		* Display message to user if session has expired.
		*/
		if (sizeof(WC()->cart->get_cart()) == 0) {
			$ms = sprintf(__('Sorry, your session has expired. <a href=%s>Return to homepage &rarr;</a>', 'paypal-for-woocommerce'), '"' . home_url() . '"');
			$set_ec_message = apply_filters('angelleye_set_ec_message', $ms);
			wc_add_notice($set_ec_message, "error");
		}

		/*
		* Check if the PayPal class has already been established.
		*/
		if (!class_exists('Angelleye_PayPal')) {
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
		if ($this->gift_wrap_enabled == 'yes') {
			$maxAmount = $paymentAmount * 2;
			$maxAmount = $maxAmount + $this->gift_wrap_amount;
			$maxAmount = number_format($maxAmount, 2, '.', '');
		} else {
			$maxAmount = '';
		}

		if (isset($_POST['order_comments']) && !empty($_POST['order_comments'])) {
			$is_ordernote = "0";
		} else {
			$is_ordernote = "1";
		}

		if(shipment_required) {
			$SHIPMENTREQUIRED = "1";
		} else {
			$SHIPMENTREQUIRED = "0";
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
		'noshipping' => $SHIPMENTREQUIRED, // The value 1 indiciates that on the PayPal pages, no shipping address fields should be displayed.  Maybe 1 or 0.
		'allownote' => $is_ordernote, // The value 1 indiciates that the customer may enter a note to the merchant on the PayPal page during checkout.  The note is returned in the GetExpresscheckoutDetails response and the DoExpressCheckoutPayment response.  Must be 1 or 0.
		'addroverride' => '', // The value 1 indiciates that the PayPal pages should display the shipping address set by you in the SetExpressCheckout request, not the shipping address on file with PayPal.  This does not allow the customer to edit the address here.  Must be 1 or 0.
		'localecode' => ($this->use_wp_locale_code == 'yes' && get_locale() != '') ? get_locale() : '', // Locale of pages displayed by PayPal during checkout.  Should be a 2 character country code.  You can retrive the country code by passing the country name into the class' GetCountryCode() function.
		'pagestyle' => '', // Sets the Custom Payment Page Style for payment pages associated with this button/link.
		'hdrimg' => $this->checkout_logo_hdrimg, // URL for the image displayed as the header during checkout.  Max size of 750x90.  Should be stored on an https:// server or you'll get a warning message in the browser.
		'logourl' => $this->checkout_logo,
		'hdrbordercolor' => '', // Sets the border color around the header of the payment page.  The border is a 2-pixel permiter around the header space.  Default is black.
		'hdrbackcolor' => '', // Sets the background color for the header of the payment page.  Default is white.
		'payflowcolor' => '', // Sets the background color for the payment page.  Default is white.
		'skipdetails' => $this->skip_final_review == 'yes' ? '1' : '0', // This is a custom field not included in the PayPal documentation.  It's used to specify whether you want to skip the GetExpressCheckoutDetails part of checkout or not.  See PayPal docs for more info.
		'email' => '', // Email address of the buyer as entered during checkout.  PayPal uses this value to pre-fill the PayPal sign-in page.  127 char max.
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

		/*
		* Get tax amount.
		*/


		/*         * ************************ Tax amount MD****************************************** */


		foreach ($order->get_tax_totals() as $code => $tax) {
			$tax_string_array[] = $tax->formatted_amount;
		}

		$current_currency = get_woocommerce_currency_symbol(get_woocommerce_currency());
		if (isset($tax_string_array) && !empty($tax_string_array)) {
			$striped_amt = strip_tags($tax_string_array[0]);
			$tot_tax = str_replace($current_currency, '', $striped_amt);
		}

		/*         * ************************ Tax amount MD*********************** */



		if (get_option('woocommerce_prices_include_tax') == 'yes') {
			$shipping = $order->get_total_shipping(); //+ $order->order_shipping_tax;

			$tax = '0.00';
		} else {
			$shipping = $order->get_total_shipping();
			if (isset($tot_tax) && !empty($tot_tax)) {
				$tax = $tot_tax;
			}

		}

		if ('yes' === get_option('woocommerce_calc_taxes') && 'yes' === get_option('woocommerce_prices_include_tax')) {
			// $tax = wc_round_tax_total($tot_tax + WC()->cart->shipping_tax_total);
			if (isset($tot_tax) && !empty($tot_tax)) {
				$tax = $tot_tax; // ($tot_tax + $order->order_shipping_tax);
			}
		}
		if (isset($tax) && !empty($tax)) {
			$tax = $tax;
		}else {
			$tax = '0.00';
		}

		$Payments = array();
		$Payment = array(
		'amt' => number_format($paymentAmount, 2, '.', ''), // Required.  The total cost of the transaction to the customer.  If shipping cost and tax charges are known, include them in this value.  If not, this value should be the current sub-total of the order.
		'currencycode' => get_woocommerce_currency(), // A three-character currency code.  Default is USD.
		'shippingamt' => number_format($shipping, 2, '.', ''), // Total shipping costs for this order.  If you specify SHIPPINGAMT you mut also specify a value for ITEMAMT.
		'shippingdiscamt' => '', // Shipping discount for this order, specified as a negative number.
		'insuranceamt' => '', // Total shipping insurance costs for this order.
		'insuranceoptionoffered' => '', // If true, the insurance drop-down on the PayPal review page displays the string 'Yes' and the insurance amount.  If true, the total shipping insurance for this order must be a positive number.
		'handlingamt' => '', // Total handling costs for this order.  If you specify HANDLINGAMT you mut also specify a value for ITEMAMT.
		'taxamt' => number_format($tax, 2, '.', ''), // Required if you specify itemized L_TAXAMT fields.  Sum of all tax items in this order.
		'desc' => '', // Description of items on the order.  127 char max.
		'custom' => '', // Free-form field for your own use.  256 char max.
		'invnum' => preg_replace("/[^0-9]/", "",$order->id), // Your own invoice or tracking number.  127 char max.
		'notifyurl' => '', // URL for receiving Instant Payment Notifications
		'shiptoname' => '', // Required if shipping is included.  Person's name associated with this address.  32 char max.
		'shiptostreet' => '', // Required if shipping is included.  First street address.  100 char max.
		'shiptostreet2' => '', // Second street address.  100 char max.
		'shiptocity' => '', // Required if shipping is included.  Name of city.  40 char max.
		'shiptostate' => '', // Required if shipping is included.  Name of state or province.  40 char max.
		'shiptozip' => '', // Required if shipping is included.  Postal code of shipping address.  20 char max.
		'shiptocountrycode' => '', // Required if shipping is included.  Country code of shipping address.  2 char max.
		'shiptophonenum' => '', // Phone number for shipping address.  20 char max.
		'notetext' => '', // Note to the merchant.  255 char max.
		'allowedpaymentmethod' => '', // The payment method type.  Specify the value InstantPaymentOnly.
		'paymentaction' => $this->payment_action == 'Authorization' ? 'Authorization' : 'Sale', // How you want to obtain the payment.  When implementing parallel payments, this field is required and must be set to Order.
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
				$Payment['shiptocity'] = @$posted['shipping_city'];
				$Payment['shiptostate'] = @$posted['shipping_state'];
				$Payment['shiptozip'] = @$posted['shipping_postcode'];
				$Payment['shiptocountrycode'] = @$posted['shipping_country'];
				$Payment['shiptophonenum'] = @$posted['shipping_phone'];
			} else {
				$Payment['shiptoname'] = $posted['billing_first_name'] . ' ' . $posted['billing_last_name'];
				$Payment['shiptostreet'] = $posted['billing_address_1'];
				$Payment['shiptostreet2'] = @$posted['billing_address_2'];
				$Payment['shiptocity'] = @$posted['billing_city'];
				$Payment['shiptostate'] = @$posted['billing_state'];
				$Payment['shiptozip'] = @$posted['billing_postcode'];
				$Payment['shiptocountrycode'] = @$posted['billing_country'];
				$Payment['shiptophonenum'] = @$posted['billing_phone'];
			}
		}

		$PaymentOrderItems = array();
		$order_items_own = array();
		$ctr = $total_items = $total_discount = $total_tax = $order_total = 0;
		$counter = 1;
		foreach (WC()->cart->get_cart() as $cart_item_key => $values) {
			/*
			* Get product data from WooCommerce
			*/
			$_product = $values['data'];
			$qty = absint($values['quantity']);
			$sku = $_product->get_sku();
			$values['name'] = html_entity_decode($_product->get_title(), ENT_NOQUOTES, 'UTF-8');
			/* ------------------------------------------------------------------------------------------------- */
			if (in_array($values['product_id'], $lineitems)) {

				$arraykey = array_search($values['product_id'], $lineitems);
				$item_position = str_replace('product_number_', '', $arraykey);

				$get_amountkey = 'amount_' . $counter;
				$get_qtykey = 'quantity_' . $counter;
				$switcher_amt = $lineitems[$get_amountkey];
				$switcher_qty = $lineitems[$get_qtykey];
				$counter = $counter +1;
			}
			/* ------------------------------------------------------------------------------------------------- */
			/*
			* Append variation data to name.
			*/
			if ($_product->product_type == 'variation') {

				$meta = WC()->cart->get_item_data($values, true);

				if (empty($sku)) {
					$sku = $_product->parent->get_sku();
				}

				if (!empty($meta)) {
					$values['name'] .= " - " . str_replace(", \n", " - ", $meta);
				}
			}

			$quantity = absint($values['quantity']);
			$Item = array(
			'name' => $values['name'], // Item name. 127 char max.
			'desc' => '', // Item description. 127 char max.
			'amt' => round($switcher_amt, 2), // Cost of item.
			'number' => $sku, // Item number.  127 char max.
			'qty' => $quantity, // Item qty on order.  Any positive integer.
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

			$total_items += round($switcher_amt, 2) * $switcher_qty;
			$order_items_own[] = round($switcher_amt, 2) * $switcher_qty;
			$ctr++;
		}

		/**
         * Add custom Woo cart fees as line items
         */
		foreach (WC()->cart->get_fees() as $fee) {
			$Item = array(
			'name' => $fee->name, // Item name. 127 char max.
			'desc' => '', // Item description. 127 char max.
			'amt' => number_format($fee->amount, 2, '.', ''), // Cost of item.
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

			$total_items += $fee->amount * $Item['qty'];
			$order_items_own[] = $fee->amount * $Item['qty'];
			$ctr++;
		}

		/*
		* Get discount(s)
		*/
		//if (WC()->cart->get_cart_discount_total() > 0) {
		if ($order->get_total_discount() > 0) {

			//   foreach (WC()->cart->get_coupons('cart') as $code => $coupon) {
			$Item = array(
			'name' => 'Cart Discount',
			'number' => 'Coupons',
			'qty' => '1',
			'amt' => '-' . number_format($order->get_total_discount(), 2, '.', '')
			);
			array_push($PaymentOrderItems, $Item);
			$total_discount += number_format($order->get_total_discount(), 2, '.', '');
			$order_items_own[] = '-' . round($order->get_total_discount(), 2);
			// }
		}

		if (!$this->is_wc_version_greater_2_3()) {
			//    if (WC()->cart->get_order_discount_total() > 0) {
			if ($order->get_total_discount() > 0) {
				//foreach (WC()->cart->get_coupons('order') as $code => $coupon) {
				$Item = array(
				'name' => 'Order Discount',
				'number' => 'Coupons',
				'qty' => '1',
				'amt' => '-' . number_format($order->get_total_discount(), 2, '.', '')
				);
				array_push($PaymentOrderItems, $Item);
				$total_discount += number_format($order->get_total_discount(), 2, '.', '');
				$order_items_own[] = '-' . round($order->get_total_discount(), 2);

				// }
			}
		}





		if (isset($total_discount)) {
			$total_discount = round($total_discount, 2);
		}

		if ($this->send_items) {
			/*
			* Now that all the order items are gathered, including discounts,
			* we'll push them back into the Payment.
			*/
			$Payment['order_items'] = $PaymentOrderItems;

			/*
			* Now that we've looped and calculated item totals
			* we can fill in the ITEMAMT
			*/
			$Payment['itemamt'] = number_format($total_items - $total_discount, 2, '.', '');
		} else {
			$Payment['order_items'] = array();

			/*
			* Now that we've looped and calculated item totals
			* we can fill in the ITEMAMT
			*/
			$Payment['itemamt'] = number_format($total_items - $total_discount, 2, '.', '');
		}

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

		$BillingAgreements = array();
		$Item = array(
		'l_billingtype' => '', // Required.  Type of billing agreement.  For recurring payments it must be RecurringPayments.  You can specify up to ten billing agreements.  For reference transactions, this field must be either:  MerchantInitiatedBilling, or MerchantInitiatedBillingSingleSource
		'l_billingagreementdescription' => '', // Required for recurring payments.  Description of goods or services associated with the billing agreement.
		'l_paymenttype' => '', // Specifies the type of PayPal payment you require for the billing agreement.  Any or IntantOnly
		'l_billingagreementcustom' => ''     // Custom annotation field for your own use.  256 char max.
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
		if (isset($tax) && !empty($tax)) {
			$tax = $tax;
		}

		// Rounding amendment

		if (trim(number_format($paymentAmount, 2, '.', '')) !== trim(number_format($total_items - $total_discount + $tax + $shipping, 2, '.', ''))) {
			$diffrence_amount = $this->get_diffrent($paymentAmount, $total_items - $total_discount + $tax + $shipping);
			if ($shipping > 0) {
				$PayPalRequestData['Payments'][0]['shippingamt'] = round($shipping + $diffrence_amount, 2);
			} elseif ($tax > 0) {
				$PayPalRequestData['Payments'][0]['taxamt'] = round($tax + $diffrence_amount, 2);
			} else {
				$PayPalRequestData['Payments'][0]['itemamt'] = round($PayPalRequestData['Payments'][0]['itemamt'] + $diffrence_amount, 2);
			}
		}

		/* rounding amount */
		$order_item_total = 0;
		foreach ($order_items_own as $keypayment => $valuepayment) {
			$order_item_total = $order_item_total + $valuepayment;
		}
		if ($shipping <= 0 && $tax <= 0) {
			$diffrence_amount_rounded = $this->get_diffrent($paymentAmount, $order_item_total);
			$PayPalRequestData['Payments'][0]['itemamt'] = round($PayPalRequestData['Payments'][0]['itemamt'] - $diffrence_amount_rounded, 2);
			$PayPalRequestData['Payments'][0]['amt'] = round($PayPalRequestData['Payments'][0]['amt'] - $diffrence_amount_rounded, 2);
		}


		// Pass data into class for processing with PayPal and load the response array into $PayPalResult
		$PayPalResult = $PayPal->SetExpressCheckout($PayPalRequestData);

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
		if (sizeof(WC()->cart->get_cart()) == 0) {
			$ms = sprintf(__('Sorry, your session has expired. <a href=%s>Return to homepage &rarr;</a>', 'paypal-for-woocommerce'), '"' . home_url() . '"');
			$ec_cgsd_message = apply_filters('angelleye_get_shipping_ec_message', $ms);
			wc_add_notice($ec_cgsd_message, "error");
		}

		/*
		* Check if the PayPal class has already been established.
		*/
		if (!class_exists('Angelleye_PayPal')) {
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
		if (sizeof(WC()->cart->get_cart()) == 0) {
			$ms = sprintf(__('Sorry, your session has expired. <a href=%s>Return to homepage &rarr;</a>', 'paypal-for-woocommerce'), '"' . home_url() . '"');
			$ec_confirm_message = apply_filters('angelleye_ec_confirm_message', $ms);
			wc_add_notice($ec_confirm_message, "error");
		}

		/*
		* Check if the PayPal class has already been established.
		*/
		if (!class_exists('Angelleye_PayPal')) {
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
		if (!empty($this->confirm_order_id)) {
			$order = new WC_Order($this->confirm_order_id);
			$invoice_number = preg_replace("/[^0-9]/", "",$order->get_order_number());

			if ($order->customer_note) {
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
		'token' => urlencode($this->get_session('TOKEN')), // Required.  A timestamped token, the value of which was returned by a previous SetExpressCheckout call.
		'payerid' => urlencode($this->get_session('payer_id')), // Required.  Unique PayPal customer id of the payer.  Returned by GetExpressCheckoutDetails, or if you used SKIPDETAILS it's returned in the URL back to your RETURNURL.
		'returnfmfdetails' => '', // Flag to indiciate whether you want the results returned by Fraud Management Filters or not.  1 or 0.
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
		$order_items_own = array();

		$final_order_total = $order->get_order_item_totals();

		$current_currency = get_woocommerce_currency_symbol(get_woocommerce_currency());

		$final_order_total_amt_strip_ec = strip_tags($final_order_total['order_total']['value']);
		$final_order_total_amt_strip = str_replace(',', '', $final_order_total_amt_strip_ec);
		$final_order_total_amt = str_replace($current_currency, '', $final_order_total_amt_strip);


		$Payment = array(
		'amt' => number_format($final_order_total_amt, 2, '.', ''), // Required.  The total cost of the transaction to the customer.  If shipping cost and tax charges are known, include them in this value.  If not, this value should be the current sub-total of the order.
		'currencycode' => get_woocommerce_currency(), // A three-character currency code.  Default is USD.
		'shippingdiscamt' => '', // Total shipping discount for this order, specified as a negative number.
		'insuranceoptionoffered' => '', // If true, the insurance drop-down on the PayPal review page displays the string 'Yes' and the insurance amount.  If true, the total shipping insurance for this order must be a positive number.
		'handlingamt' => '', // Total handling costs for this order.  If you specify HANDLINGAMT you mut also specify a value for ITEMAMT.
		'desc' => '', // Description of items on the order.  127 char max.
		'custom' => '', // Free-form field for your own use.  256 char max.
		'invnum' => preg_replace("/[^0-9]/", "",$this->confirm_order_id), // Your own invoice or tracking number.  127 char max.
		'notifyurl' => '', // URL for receiving Instant Payment Notifications
		'shiptoname' => $shipping_first_name . ' ' . $shipping_last_name, // Required if shipping is included.  Person's name associated with this address.  32 char max.
		'shiptostreet' => $shipping_address_1, // Required if shipping is included.  First street address.  100 char max.
		'shiptostreet2' => $shipping_address_2, // Second street address.  100 char max.
		'shiptocity' => $shipping_city, // Required if shipping is included.  Name of city.  40 char max.
		'shiptostate' => $shipping_state, // Required if shipping is included.  Name of state or province.  40 char max.
		'shiptozip' => $shipping_postcode, // Required if shipping is included.  Postal code of shipping address.  20 char max.
		'shiptocountrycode' => $shipping_country, // Required if shipping is included.  Country code of shipping address.  2 char max.
		'shiptophonenum' => '', // Phone number for shipping address.  20 char max.
		'notetext' => $this->get_session('customer_notes'), // Note to the merchant.  255 char max.
		'allowedpaymentmethod' => '', // The payment method type.  Specify the value InstantPaymentOnly.
		'paymentaction' => $this->payment_action == 'Authorization' ? 'Authorization' : 'Sale', // How you want to obtain the payment.  When implementing parallel payments, this field is required and must be set to Order.
		'paymentrequestid' => '', // A unique identifier of the specific payment request, which is required for parallel payments.
		'sellerpaypalaccountid' => '', // A unique identifier for the merchant.  For parallel payments, this field is required and must contain the Payer ID or the email address of the merchant.
		'sellerid' => '', // The unique non-changing identifer for the seller at the marketplace site.  This ID is not displayed.
		'sellerusername' => '', // The current name of the seller or business at the marketplace site.  This name may be shown to the buyer.
		'sellerregistrationdate' => '', // Date when the seller registered at the marketplace site.
		'softdescriptor' => ''     // A per transaction description of the payment that is passed to the buyer's credit card statement.
		);

		$PaymentOrderItems = array();
		$ctr = $total_items = $total_discount = $total_tax = $shipping = 0;
		$ITEMAMT = 0;
		$counter = 1;
		
		if (sizeof($order->get_items()) > 0) {
			//   if ($this->send_items) {
			foreach ($order->get_items() as $values) {
				$_product = $order->get_product_from_item($values);
				$qty = absint($values['qty']);
				$sku = $_product->get_sku();
				$values['name'] = html_entity_decode($values['name'], ENT_NOQUOTES, 'UTF-8');
				if ($_product->product_type == 'variation') {
					if (empty($sku)) {
						$sku = $_product->parent->get_sku();
					}

					//$item_meta = new WC_Order_Item_Meta($values['item_meta']);
					$item_meta = new WC_Order_Item_Meta($values,$_product);
					$meta = $item_meta->display(true, true);
					if (!empty($meta)) {
						$values['name'] .= " - " . str_replace(", \n", " - ", $meta);
					}
				}


				//////////////////////////////////////////***************************////////////////////////////////////

				$lineitems_prepare = $this->prepare_line_items($order);
				$lineitems = $_SESSION['line_item'];

				if (in_array($values['product_id'], $lineitems)) {

					$arraykey = array_search($values['product_id'], $lineitems);
					$item_position = str_replace('product_number_', '', $arraykey);

					$get_amountkey = 'amount_' . $counter;
					$get_qtykey = 'quantity_' . $counter;
					$switcher_amt = $lineitems[$get_amountkey];
					$switcher_qty = $lineitems[$get_qtykey];
					$counter = $counter + 1;
				}

				//////////////////////////////////////////***************************////////////////////////////////////

				$Item = array(
				'name' => $values['name'], // Item name. 127 char max.
				'desc' => '', // Item description. 127 char max.
				'amt' => round($switcher_amt, 2), // Cost of item.
				'number' => $sku, // Item number.  127 char max.
				'qty' => $qty, // Item qty on order.  Any positive integer.
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
				'ebayitemcartid' => ''                        // The unique identifier provided by eBay for this order from the buyer. These parameters must be ordered sequentially beginning with 0 (for example L_EBAYITEMCARTID0, L_EBAYITEMCARTID1). Character length: 255 single-byte characters
				);
				array_push($PaymentOrderItems, $Item);

				$ITEMAMT +=round($switcher_amt, 2) * $switcher_qty;
				$order_items_own[] = round($switcher_amt, 2) * $switcher_qty;
			}

			/**
             * Add custom Woo cart fees as line items
             */
			foreach (WC()->cart->get_fees() as $fee) {
				$Item = array(
				'name' => $fee->name, // Item name. 127 char max.
				'desc' => '', // Item description. 127 char max.
				'amt' => number_format($fee->amount, 2, '.', ''), // Cost of item.
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
				if ($Item['number'] != 'gift-wrap') {
					array_push($PaymentOrderItems, $Item);
					$ITEMAMT += $fee->amount * $Item['qty'];
					$order_items_own[] = $fee->amount * $Item['qty'];
				}

				$ctr++;
			}

			if (!$this->is_wc_version_greater_2_3()) {
				/*
				* Get discounts
				*/
				if ($order->get_order_discount() > 0) {
					// foreach (WC()->cart->get_coupons('cart') as $code => $coupon) {
					$Item = array(
					'name' => 'Cart Discount',
					'number' => 'Coupons',
					'qty' => '1',
					'amt' => '-' . number_format(WC()->cart->coupon_discount_amounts[$code], 2, '.', '')
					);
					array_push($PaymentOrderItems, $Item);
					//}
					$total_discount -= $order->get_cart_discount();
					$order_items_own[] = $fee->amount * $Item['qty'];
				}

				if ($order->get_order_discount() > 0) {
					//  foreach (WC()->cart->get_coupons('order') as $code => $coupon) {
					$Item = array(
					'name' => 'Order Discount',
					'number' => 'Coupons',
					'qty' => '1',
					'amt' => '-' . number_format($order->get_order_discount(), 2, '.', '')
					);
					array_push($PaymentOrderItems, $Item);
					//  }
					$total_discount -= $order->get_order_discount();
					$order_items_own[] = round($total_discount, 2);
				}
			} else {
				if ($order->get_total_discount() > 0) {
					$Item = array(
					'name' => 'Total Discount',
					'qty' => 1,
					'amt' => - number_format($order->get_total_discount(), 2, '.', ''),
					);
					array_push($PaymentOrderItems, $Item);
					$total_discount -= $order->get_total_discount();
					$order_items_own[] = round($total_discount, 2);
				}
			}
			//   } if(this->senditem)1876

			/*
			* Set shipping and tax values.
			*/
			/*             * *****************************MD******************************** */

			foreach ($order->get_tax_totals() as $code => $tax) {
				$tax_string_array[] = $tax->formatted_amount;
			}

			$current_currency = get_woocommerce_currency_symbol(get_woocommerce_currency());
			if (isset($tax_string_array) && !empty($tax_string_array)) {
				$striped_amt = strip_tags($tax_string_array[0]);
				if (isset($striped_amt) && !empty($striped_amt)) {
					$tot_tax = str_replace($current_currency, '', $striped_amt);
				}
			}
			/*             * ****************************MD******************************** */

			if (get_option('woocommerce_prices_include_tax') == 'yes') {
				$shipping = $order->get_total_shipping(); //+ $order->get_shipping_tax();
				$tax = 0;
			} else {
				$shipping = $order->get_total_shipping();

				if (isset($tot_tax) && !empty($tot_tax)) {
					$tax = $tot_tax;
				}else {
					$tax = 0;
				}
			}

			if ('yes' === get_option('woocommerce_calc_taxes') && 'yes' === get_option('woocommerce_prices_include_tax')) {
				if (isset($tot_tax) && !empty($tot_tax)) {
					$tax = $tot_tax;
				}else {
					$tax = 0;
				}
			}

			if ($tax > 0) {

				$tax = number_format($tot_tax, 2, '.', '');
			}

			if ($shipping > 0) {
				$shipping = number_format($shipping, 2, '.', '');
			}

			if ($total_discount) {
				$total_discount = round($total_discount, 2);
			}

			if ($this->send_items) {

				/*
				* Now that we have all items and subtotals
				* we can fill in necessary values.
				*/

				$Payment['itemamt'] = number_format($ITEMAMT + $total_discount, 2, '.', '');
			} else {
				$PaymentOrderItems = array();


				$Payment['itemamt'] = number_format($ITEMAMT + $total_discount, 2, '.', '');
			}

			/*
			* Set tax
			*/
			if ($tax > 0) {
				$Payment['taxamt'] = number_format($tot_tax, 2, '.', '');       // Required if you specify itemized L_TAXAMT fields.  Sum of all tax items in this order.
			}

			/*
			* Set shipping
			*/
			if ($shipping > 0) {
				$Payment['shippingamt'] = number_format($shipping, 2, '.', '');      // Total shipping costs for this order.  If you specify SHIPPINGAMT you mut also specify a value for ITEMAMT.
			}
		}

		$Payment['order_items'] = $PaymentOrderItems;
		array_push($Payments, $Payment);

		$UserSelectedOptions = array(
		'shippingcalculationmode' => '', // Describes how the options that were presented to the user were determined.  values are:  API - Callback   or   API - Flatrate.
		'insuranceoptionselected' => '', // The Yes/No option that you chose for insurance.
		'shippingoptionisdefault' => '', // Is true if the buyer chose the default shipping option.
		'shippingoptionamount' => '', // The shipping amount that was chosen by the buyer.
		'shippingoptionname' => '', // Is true if the buyer chose the default shipping option...??  Maybe this is supposed to show the name..??
		);

		$PayPalRequestData = array(
		'DECPFields' => $DECPFields,
		'Payments' => $Payments,
		//'UserSelectedOptions' => $UserSelectedOptions
		);


		// Rounding amendment

		if (isset($tax) && !empty ($tax)) {
			$tax = $tax;
		}else {
			$tax ='0.00';
		}
		if (isset($shipping) && !empty ($shipping)) {
			$shipping = $shipping;
		}else {
			$shipping ='0.00';
		}


		if (trim(number_format($final_order_total_amt, 2, '.', '')) !== trim(number_format($Payment['itemamt'] + number_format($tax, 2, '.', '') + number_format($shipping, 2, '.', ''), 2, '.', ''))) {
			$diffrence_amount = $this->get_diffrent($final_order_total_amt, $Payment['itemamt'] + $tax + number_format($shipping, 2, '.', ''));
			if ($shipping > 0) {
				$PayPalRequestData['Payments'][0]['shippingamt'] = round($shipping + $diffrence_amount, 2);
			} elseif ($tax > 0) {
				$PayPalRequestData['Payments'][0]['taxamt'] = round($tax + $diffrence_amount, 2);
			} else {
				$PayPalRequestData['Payments'][0]['itemamt'] = round($PayPalRequestData['Payments'][0]['itemamt'] + $diffrence_amount, 2);
			}
		}

		/* rounding amount */
		$order_item_total = 0;
		foreach ($order_items_own as $keypayment => $valuepayment) {
			$order_item_total = $order_item_total + $valuepayment;
		}
		if ($shipping <= 0 && $tax <= 0) {
			$diffrence_amount_rounded = $this->get_diffrent($final_order_total_amt, $order_item_total);
			$PayPalRequestData['Payments'][0]['itemamt'] = round($PayPalRequestData['Payments'][0]['itemamt'] - $diffrence_amount_rounded, 2);
			$PayPalRequestData['Payments'][0]['amt'] = round($PayPalRequestData['Payments'][0]['amt'] - $diffrence_amount_rounded, 2);
		}

		// Pass data into class for processing with PayPal and load the response array into $PayPalResult
		$PayPalResult = $PayPal->DoExpressCheckoutPayment($PayPalRequestData);

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
			$this->remove_session('TOKEN');
			if (isset($_SESSION['line_item'])) {
				unset($_SESSION['line_item']);
			}


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
			$local_states = WC()->countries->states[WC()->customer->get_country()];
			if (!empty($local_states) && in_array($state, $local_states)) {
				foreach ($local_states as $key => $val) {
					if ($val == $state) {
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
	private function set_session($key, $value) {
		WC()->session->$key = $value;
	}

	/**
     * get_session function.
     *
     * @access private
     * @param mixed $key
     * @return void
     */
	private function get_session($key) {
		return WC()->session->$key;
	}

	private function remove_session($key) {
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
					$checkout_button_display_text = !empty($pp_settings['show_on_cart']) && $pp_settings['show_on_cart'] == 'yes' ? __('Pay with Credit Card', 'paypal-for-woocommerce') : __('Proceed to Checkout', 'paypal-for-woocommerce');
					echo '<script type="text/javascript">
                                jQuery(document).ready(function(){
                                    if (jQuery(".checkout-button").is("input")) {
                                        jQuery(".checkout-button").val("' . $checkout_button_display_text . '");
                                    } else jQuery(".checkout-button").html("<span>' . $checkout_button_display_text . '</span>");
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
		if (@$pp_settings['enabled'] == 'yes' && (empty($pp_settings['show_on_cart']) || $pp_settings['show_on_cart'] == 'yes') && 0 < WC()->cart->total) {
			echo '<div class="paypal_box_button" style="position: relative;">';
			if (empty($pp_settings['checkout_with_pp_button_type']))
			$pp_settings['checkout_with_pp_button_type'] = 'paypalimage';
			switch ($pp_settings['checkout_with_pp_button_type']) {
				case "textbutton":
					if (!empty($pp_settings['pp_button_type_text_button'])) {
						$button_text = $pp_settings['pp_button_type_text_button'];
					} else {
						$button_text = __('Proceed to Checkout', 'woocommerce');
					}
					echo '<a class="paypal_checkout_button button alt" href="' . esc_url(add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">' . $button_text . '</a>';
					break;
				case "paypalimage":
					echo '<div id="paypal_ec_button">';
					echo '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">';
					echo "<img src='https://www.paypal.com/" . WC_Gateway_PayPal_Express_AngellEYE::get_button_locale_code() . "/i/btn/btn_xpressCheckout.gif' border='0' alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
					echo "</a>";
					echo '</div>';
					break;
				case "customimage":
					$button_img = $pp_settings['pp_button_type_my_custom'];
					echo '<div id="paypal_ec_button">';
					echo '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">';
					echo "<img src='{$button_img}' width='150' border='0' alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
					echo "</a>";
					echo '</div>';
					break;
			}

			/**
             * Displays the PayPal Credit checkout button if enabled in EC settings.
             */
			if (isset($pp_settings['show_paypal_credit']) && $pp_settings['show_paypal_credit'] == 'yes') {
				// PayPal Credit button
				$paypal_credit_button_markup = '<div id="paypal_ec_paypal_credit_button">';
				$paypal_credit_button_markup .= '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('use_paypal_credit', 'true', add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/'))))) . '" >';
				$paypal_credit_button_markup .= "<img src='https://www.paypalobjects.com/webstatic/en_US/i/buttons/ppcredit-logo-small.png' alt='Check out with PayPal Credit'/>";
				$paypal_credit_button_markup .= '</a>';
				$paypal_credit_button_markup .= '</div>';

				echo $paypal_credit_button_markup;
			}
            ?>
            <div class="blockUI blockOverlay angelleyeOverlay" style="display:none;z-index: 1000; border: none; margin: 0px; padding: 0px; width: 100%; height: 100%; top: 0px; left: 0px; opacity: 0.6; cursor: default; position: absolute; background: url(<?php echo WC()->plugin_url(); ?>/assets/images/select2-spinner.gif) 50% 50% / 16px 16px no-repeat rgb(255, 255, 255);"></div>
            <?php
            echo "<div class='clear'></div></div>";
		}
	}

	static function get_button_locale_code() {
		$locale_code = defined("WPLANG") && get_locale() != '' ? get_locale() : 'en_US';
		switch ($locale_code) {
			case "de_DE": $locale_code = "de_DE/DE";
			break;
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
		'amt' => number_format($amount, 2, '.', ''), // Refund Amt.  Required if refund type is Partial.
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
		$this->add_log('Refund Information: ' . print_r($PayPalResult, true));
		if ($PayPal->APICallSuccessful($PayPalResult['ACK'])) {

			$max_remaining_refund = wc_format_decimal( $order->get_total() - $order->get_total_refunded() );
			if ( $max_remaining_refund > 0 ) {
				$order->add_order_note('Refund Transaction ID:' . $PayPalResult['REFUNDTRANSACTIONID']);
			}else {
				$order->update_status('refunded');
				$order->add_order_note('Refund Transaction ID:' . $PayPalResult['REFUNDTRANSACTIONID']);
			}


			if (ob_get_length())
			ob_end_clean();
			return true;
		} else {
			$ec_message = apply_filters('angelleye_ec_refund_message', $PayPalResult['L_LONGMESSAGE0'], $PayPalResult['L_ERRORCODE0'], $PayPalResult);
			return new WP_Error('ec_refund-error', $ec_message);
		}
	}

	function top_cart_button() {
		if (!empty($this->settings['button_position']) && ($this->settings['button_position'] == 'top' || $this->settings['button_position'] == 'both')) {
			$this->woocommerce_paypal_express_checkout_button_angelleye();
		}
	}

	/**
     * Regular checkout process
     */
	function regular_checkout($posted) {

		if ($posted['payment_method'] == 'paypal_express' && wc_notice_count('error') == 0) {

			if (!is_user_logged_in() && (get_option('woocommerce_enable_guest_checkout') != 'yes' || (isset($posted['createaccount']) && $posted['createaccount'] == 1) )) {

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
			$this->set_session('checkout_form', serialize($posted));
			$this->paypal_express_checkout($posted);
			return;
		}
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

	public function is_wc_version_greater_2_3() {
		return $this->get_wc_version() && version_compare($this->get_wc_version(), '2.3', '>=');
	}

	public function get_wc_version() {
		return defined('WC_VERSION') && WC_VERSION ? WC_VERSION : null;
	}

	////////////////////////////////////////////////////////////////////////



	public function add_line_item_own($item_name, $quantity = 1, $amount = 0, $item_number = '',$productid = 0) {
		$index = ( sizeof($this->line_items) / 5 ) + 1;

		if (!$item_name || $amount < 0) {
			return false;
		}

		$this->line_items['item_name_' . $index] = html_entity_decode(wc_trim_string($item_name, 127), ENT_NOQUOTES, 'UTF-8');
		$this->line_items['quantity_' . $index] = $quantity;
		$this->line_items['amount_' . $index] = $amount;
		$this->line_items['item_number_' . $index] = $item_number;
		$this->line_items['product_number_' . $index] = $productid;
		$_SESSION['line_item'] = $this->line_items;
		return true;
	}

	public function prepare_line_items($order) {
		$this->delete_line_items();
		$calculated_total = 0;

		// Products
		foreach ($order->get_items(array('line_item', 'fee')) as $item) {
			if ('fee' === $item['type']) {
				$line_item = $this->add_line_item_own($item['name'], 1, $item['line_total']);
				$calculated_total += $item['line_total'];
			} else {
				$product = $order->get_product_from_item($item);
				if (isset($product->id) && !empty($product->id)) {
					$productid = $product->id;
				}
				$line_item = $this->add_line_item_own($item['name'], $item['qty'], $order->get_item_subtotal($item, false), $product->get_sku(),$productid);
				$calculated_total += $order->get_item_subtotal($item, false) * $item['qty'];
			}

			if (!$line_item) {
				return false;
			}
		}



		// Check for mismatched totals
		if (wc_format_decimal($calculated_total + $order->get_total_tax() + round($order->get_total_shipping(), 2) - round($order->get_total_discount(), 2), 2) != wc_format_decimal($order->get_total(), 2)) {
			return false;
		}

		return true;
	}

	/**
     * Return all line items
     */
	public function get_line_items() {
		return $this->line_items;
	}

	/**
     * Remove all line items
     */
	public function delete_line_items() {
		$this->line_items = array();
	}

	/* get_order_item_name function removed for get_formatted_legacy notice */


	public function get_line_item_args($order) {
		/**
         * Try passing a line item per product if supported
         */
		if ((!wc_tax_enabled() || !wc_prices_include_tax() ) && $this->prepare_line_items($order)) {

			$line_item_args = $this->get_line_items();
			$line_item_args['tax_cart'] = $order->get_total_tax();

			if ($order->get_total_discount() > 0) {
				$line_item_args['discount_amount_cart'] = round($order->get_total_discount(), 2);
			}

			/**
             * Send order as a single item
             *
             * For shipping, we longer use shipping_1 because paypal ignores it if *any* shipping rules are within paypal, and paypal ignores anything over 5 digits (999.99 is the max)
             */
		}

		return $line_item_args;
	}

	public function get_shipping_args($order) {
		$shipping_args = array();

		if ('yes' == $this->gateway->get_option('send_shipping')) {
			$shipping_args['address_override'] = $this->gateway->get_option('address_override') === 'yes' ? 1 : 0;
			$shipping_args['no_shipping'] = 0;

			// If we are sending shipping, send shipping address instead of billing
			$shipping_args['first_name'] = $order->shipping_first_name;
			$shipping_args['last_name'] = $order->shipping_last_name;
			$shipping_args['company'] = $order->shipping_company;
			$shipping_args['address1'] = $order->shipping_address_1;
			$shipping_args['address2'] = $order->shipping_address_2;
			$shipping_args['city'] = $order->shipping_city;
			$shipping_args['state'] = $this->get_paypal_state($order->shipping_country, $order->shipping_state);
			$shipping_args['country'] = $order->shipping_country;
			$shipping_args['zip'] = $order->shipping_postcode;
		} else {
			$shipping_args['no_shipping'] = 1;
		}

		return $shipping_args;
	}

	public function get_phone_number_args($order) {
		if (in_array($order->billing_country, array('US', 'CA'))) {
			$phone_number = str_replace(array('(', '-', ' ', ')', '.'), '', $order->billing_phone);
			$phone_args = array(
			'night_phone_a' => substr($phone_number, 0, 3),
			'night_phone_b' => substr($phone_number, 3, 3),
			'night_phone_c' => substr($phone_number, 6, 4),
			'day_phone_a' => substr($phone_number, 0, 3),
			'day_phone_b' => substr($phone_number, 3, 3),
			'day_phone_c' => substr($phone_number, 6, 4)
			);
		} else {
			$phone_args = array(
			'night_phone_b' => $order->billing_phone,
			'day_phone_b' => $order->billing_phone
			);
		}
		return $phone_args;
	}

	public function get_order_item_names($order) {
		$item_names = array();

		foreach ($order->get_items() as $item) {
			$item_names[] = $item['name'] . ' x ' . $item['qty'];
		}

		return implode(', ', $item_names);
	}

}