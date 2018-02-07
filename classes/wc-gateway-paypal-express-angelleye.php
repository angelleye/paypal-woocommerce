<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_PayPal_Express_AngellEYE extends WC_Payment_Gateway {

    public $function_helper;
    public static $log_enabled = false;
    public static $log = false;
    public $checkout_fields;
    public $posted;

    public function __construct() {
        $this->id = 'paypal_express';
        $this->home_url = is_ssl() ? home_url('/', 'https') : home_url('/'); 
        $this->method_title = __('PayPal Express Checkout ', 'paypal-for-woocommerce');
        $this->method_description = __('PayPal Express Checkout is designed to make the checkout experience for buyers using PayPal much more quick and easy than filling out billing and shipping forms.  Customers will be taken directly to PayPal to sign in and authorize the payment, and are then returned back to your store to choose a shipping method, review the final order total, and complete the payment.', 'paypal-for-woocommerce');
        $this->has_fields = false;
        $this->supports = array(
            'products',
            'refunds',
            'subscriptions',
            'subscription_cancellation',
            'subscription_reactivation',
            'subscription_suspension',
            'subscription_amount_changes',
            'subscription_payment_method_change', // Subs 1.n compatibility.
            'subscription_payment_method_change_customer',
            'subscription_payment_method_change_admin',
            'subscription_date_changes',
            'multiple_subscriptions',
        );
        if (substr(get_option("woocommerce_default_country"), 0, 2) == 'US' || substr(get_option("woocommerce_default_country"), 0, 2) == 'GB') {
            $this->is_us_or_uk = true;
        } else {
            $this->is_us_or_uk = false;
        }
        if(substr(get_option("woocommerce_default_country"), 0, 2) == 'US') {
            $this->is_us = true;
        } else {
            $this->is_us = false;
        }
        $this->init_form_fields();
        $this->init_settings();
        $this->enable_tokenized_payments = $this->get_option('enable_tokenized_payments', 'no');
        if ($this->enable_tokenized_payments == 'yes') {
            $this->supports = array_merge($this->supports, array('add_payment_method','tokenization'));
        }
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->prevent_to_add_additional_item = 'yes' === $this->get_option('prevent_to_add_additional_item', 'no');
        $this->testmode = 'yes' === $this->get_option('testmode', 'yes');
        if ($this->testmode == false) {
            $this->testmode = AngellEYE_Utility::angelleye_paypal_for_woocommerce_is_set_sandbox_product();
        }
        $this->debug = 'yes' === $this->get_option('debug', 'no');
        $this->save_abandoned_checkout = 'yes' === $this->get_option('save_abandoned_checkout', 'no');
        self::$log_enabled = $this->debug;
        $this->error_email_notify = 'yes' === $this->get_option('error_email_notify', 'no');
        $this->show_on_checkout = $this->get_option('show_on_checkout', 'top');
        $this->paypal_account_optional = $this->get_option('paypal_account_optional', 'no');
        $this->error_display_type = $this->get_option('error_display_type', 'detailed');
        $this->landing_page = $this->get_option('landing_page', 'login');
        $this->checkout_logo = $this->get_option('checkout_logo', '');
        $this->checkout_logo_hdrimg = $this->get_option('checkout_logo_hdrimg', '');
        $this->show_paypal_credit = $this->get_option('show_paypal_credit', 'yes');
        $this->brand_name = $this->get_option('brand_name', get_bloginfo('name'));
        $this->customer_service_number = $this->get_option('customer_service_number', '');
        $this->use_wp_locale_code = $this->get_option('use_wp_locale_code', 'yes');
        $this->angelleye_skip_text = $this->get_option('angelleye_skip_text', 'Skip the forms and pay faster with PayPal!');
        $this->skip_final_review = $this->get_option('skip_final_review', 'no');
        $this->disable_term = $this->get_option('disable_term', 'no');
        $this->payment_action = $this->get_option('payment_action', 'Sale');
        $this->billing_address = 'yes' === $this->get_option('billing_address', 'no');
        $this->send_items = 'yes' === $this->get_option('send_items', 'yes');
        $this->order_cancellations = $this->get_option('order_cancellations', 'disabled');
        $this->email_notify_order_cancellations = 'yes' === $this->get_option('email_notify_order_cancellations', 'no');
        $this->customer_id = get_current_user_id();
        $this->enable_notifyurl = $this->get_option('enable_notifyurl', 'no');
        $this->notifyurl = '';
        $this->is_encrypt = $this->get_option('is_encrypt', 'no');
        $this->cancel_page_id = $this->get_option('cancel_page', '');
        $this->fraud_management_filters = $this->get_option('fraud_management_filters', 'place_order_on_hold_for_further_review');
        $this->invoice_id_prefix = $this->get_option('invoice_id_prefix', '');
        $this->paypal_marketing_solutions_cid_production = $this->get_option('paypal_marketing_solutions_cid_production', '');
        $this->show_on_minicart = $this->get_option('show_on_minicart', 'yes');
        $this->pending_authorization_order_status = $this->get_option('pending_authorization_order_status', 'On Hold');
        if ($this->enable_notifyurl == 'yes') {
            $this->notifyurl = $this->get_option('notifyurl');
            if (isset($this->notifyurl) && !empty($this->notifyurl)) {
                $this->notifyurl = str_replace('&amp;', '&', $this->notifyurl);
            }
        }
        if ($this->is_us_or_uk == false) {
            $this->show_paypal_credit = 'no';
        }
        if ($this->testmode == true) {
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
        $this->pp_button_type_my_custom = $this->get_option('pp_button_type_my_custom', self::angelleye_get_paypalimage());
        $this->softdescriptor = $this->get_option('softdescriptor', '');
        $this->version = "64";
        $this->Force_tls_one_point_two = get_option('Force_tls_one_point_two', 'no');
        $this->page_style = $this->get_option('page_style', '');
        
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'), 999);
        add_filter('woocommerce_settings_api_sanitized_fields_' . $this->id, array($this, 'angelleye_express_checkout_encrypt_gateway_api'), 10, 1);
        if (!has_action('woocommerce_api_' . strtolower('WC_Gateway_PayPal_Express_AngellEYE'))) {
            add_action('woocommerce_api_' . strtolower('WC_Gateway_PayPal_Express_AngellEYE'), array($this, 'handle_wc_api'));
        }
        if (!class_exists('WC_Gateway_PayPal_Express_Function_AngellEYE')) {
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/express-checkout/class-wc-gateway-paypal-express-function-angelleye.php' );
        }
        $this->function_helper = new WC_Gateway_PayPal_Express_Function_AngellEYE();
        $this->order_button_text = ($this->function_helper->ec_is_express_checkout() == false) ?  __('Proceed to PayPal', 'paypal-for-woocommerce') :  __( 'Place order', 'paypal-for-woocommerce' );
        do_action( 'angelleye_paypal_for_woocommerce_multi_account_api_' . $this->id, $this, null, null );
        
        
    }

    public function admin_options() {
        ?>
        <h3><?php _e('PayPal Express Checkout', 'paypal-for-woocommerce'); ?></h3>
        <p><?php _e($this->method_description, 'paypal-for-woocommerce'); ?></p>
        <table class="form-table">
             <?php 
            if(version_compare(WC_VERSION,'2.6','<')) {
                AngellEYE_Utility::woo_compatibility_notice();    
            } else {
               $this->generate_settings_html(); 
            }
            ?>
        </table> 
        <?php
        add_thickbox();
        $guest_checkout = get_option('woocommerce_enable_guest_checkout', 'yes');
        if( 'yes' === get_option( 'woocommerce_registration_generate_username' ) && 'yes' === get_option( 'woocommerce_registration_generate_password' ) ) {
            $guest_checkout = 'yes';
        }
        if (wc_get_page_id('terms') > 0 && apply_filters('woocommerce_checkout_show_terms', true)) {
            if ($guest_checkout === 'yes') {
                $display_disable_terms = 'yes';
            } else {
                $display_disable_terms = 'no';
            }
        } else {
            $display_disable_terms = 'no';
        }
        $this->paypal_marketing_solutions_cid_production = $this->get_option('paypal_marketing_solutions_cid_production', '');
        $report_home = 'https://business.paypal.com/merchantdata/reportHome?cid='.$this->paypal_marketing_solutions_cid_production;
        ?>
        <?php if( $this->is_us == true ) { ?>
        <div id="more-info-popup" style="display:none;">
          <iframe width="889" height="554" src="https://www.youtube.com/embed/hXWFn8_jUDc" frameborder="0" gesture="media" allow="encrypted-media" allowfullscreen></iframe>
        </div>
        
        <script src='https://www.paypalobjects.com/muse/partners/muse-button-bundle.js'></script>
        <script>
        function display_notice_and_disable_marketing_solution() {
            jQuery("#woocommerce_paypal_express_paypal_marketing_solutions_enabled").prop('disabled', 'disabled');
            jQuery("#woocommerce_paypal_express_paypal_marketing_solutions_enabled").prop('readonly', 'readonly'); 
            jQuery('.display_msg_when_activated').html('');
            jQuery('.display_msg_when_activated').html('<span class="pms-red">PayPal Marketing Solutions only available for Live mode.<br/><br/></span>');
        }
        var muse_options_production = {
            onContainerCreate: callback_onsuccess_production,
            url: '<?php echo $this->home_url; ?>',
            parnter_name: 'Angell EYE',
            bn_code: 'AngellEYE_SP_MarketingSolutions',
            promotionsEnabled: 'True',
            env: 'production',
            cid: '<?php echo $this->paypal_marketing_solutions_cid_production; ?>'
        }
        jQuery('#woocommerce_paypal_express_paypal_marketing_solutions_cid_production').closest('tr').hide();
        jQuery('#woocommerce_paypal_express_paypal_marketing_solutions_enabled').closest('tr').find('th').hide(); 
        
        <?php
        if (!empty($this->paypal_marketing_solutions_cid_production)) {
            ?> jQuery('#pms-paypalInsightsLink').show();
                jQuery('.display_when_deactivated').hide();
                jQuery('.pms-view-more').hide();
                
                jQuery('#woocommerce_paypal_express_paypal_marketing_solutions_enabled').closest('table').css({'display': 'none'}); 
            <?php
        } else {
            ?> 
            display_notice_and_disable_marketing_solution();
            jQuery('#woocommerce_paypal_express_paypal_marketing_solutions_enabled').closest('table').css({'width': '50%', 'top': '-65px'}); jQuery('#pms-paypalInsightsLink').hide(); jQuery('#angelleye_wp_marketing_solutions_button_production').hide(); 
            <?php
        }
        ?>
        jQuery("#woocommerce_paypal_express_testmode, #woocommerce_paypal_express_api_username, #woocommerce_paypal_express_api_password, #woocommerce_paypal_express_api_signature").on('keyup change keypress', function (){
            if (jQuery(this).is(':checked') === false) {
                var api_username = (jQuery('#woocommerce_paypal_express_api_username').val().length > 0) ? jQuery('#woocommerce_paypal_express_api_username').val() : jQuery('#woocommerce_paypal_express_api_username').text();
                    var api_password = (jQuery('#woocommerce_paypal_express_api_password').val().length > 0) ? jQuery('#woocommerce_paypal_express_api_password').val() : jQuery('#woocommerce_paypal_express_api_password').text();
                    var api_signature = (jQuery('#woocommerce_paypal_express_api_signature').val().length > 0) ? jQuery('#woocommerce_paypal_express_api_signature').val() : jQuery('#woocommerce_paypal_express_api_signature').text();
                    if( api_username.length > 0 && api_password.length > 0 && api_signature.length > 0 ) {
                        jQuery('.display_msg_when_activated').html('');
                        jQuery("#woocommerce_paypal_express_paypal_marketing_solutions_enabled").prop('disabled', '');
                        jQuery("#woocommerce_paypal_express_paypal_marketing_solutions_enabled").prop('readonly', '');
                    } else {
                        display_notice_and_disable_marketing_solution();
                    }
            } else { 
                display_notice_and_disable_marketing_solution();
            }
        }).change();
            
        jQuery('.view-paypal-insight-result').on('click', function (event) {
            event.preventDefault();
            var win = window.open('<?php echo $report_home; ?>', '_blank');
            win.focus();
        });
        function callback_onsuccess_production(containerId) {
            muse_options_production.cid = containerId;
        }
        MUSEButton('angelleye_wp_marketing_solutions_button_production', muse_options_production);
        </script>
        <?php } ?>
        <script type="text/javascript">
            jQuery('#woocommerce_paypal_express_payment_action').change(function () {
                if ( this.value === 'Authorization' ) {
                    jQuery('#woocommerce_paypal_express_pending_authorization_order_status').closest('tr').show();
                } else {
                    jQuery('#woocommerce_paypal_express_pending_authorization_order_status').closest('tr').hide();
                }
            }).change();
            var display_disable_terms = "<?php echo $display_disable_terms; ?>";
            <?php if ($guest_checkout === 'no') { ?>
                        jQuery("#woocommerce_paypal_express_skip_final_review").prop("checked", false);
                        jQuery("#woocommerce_paypal_express_skip_final_review").attr("disabled", true);
            <?php } ?>
            jQuery('#woocommerce_paypal_express_skip_final_review').change(function () {
                disable_term = jQuery('#woocommerce_paypal_express_disable_term').closest('tr');
                if (jQuery(this).is(':checked')) {
                    if (display_disable_terms === 'yes') {
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
            jQuery('#woocommerce_paypal_express_show_on_cart').change(function () {
                var show_on_minicart = jQuery('#woocommerce_paypal_express_show_on_minicart').closest('tr');
                if (jQuery(this).is(':checked')) {
                    show_on_minicart.show();
                } else {
                    show_on_minicart.hide();
                }
            }).change();
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
         <?php
    }

    /**
     * get_icon function.
     *
     * @access public
     * @return string
     */
    public function get_icon() {
        $image_path = plugins_url('/assets/images/paypal.png', plugin_basename(dirname(__FILE__)));
        if( $this->paypal_account_optional == 'no' && $this->show_paypal_credit == 'no' ) {
            $image_path = plugins_url('/assets/images/paypal.png', plugin_basename(dirname(__FILE__)));
        }
        if ($this->paypal_account_optional == 'yes' && $this->show_paypal_credit == 'no' ) {
            $image_path = plugins_url('/assets/images/paypal-credit-card-logos.png', plugin_basename(dirname(__FILE__)));
        }
        if ($this->paypal_account_optional == 'yes' && $this->show_paypal_credit == 'yes' ) {
            $image_path = plugins_url('/assets/images/paypal-paypal-credit-card-logos.png', plugin_basename(dirname(__FILE__)));
        }
        if ($this->checkout_with_pp_button_type == 'customimage') {
            $image_path = $this->pp_button_type_my_custom;
        }
        if ( is_ssl() || get_option( 'woocommerce_force_ssl_checkout' ) == 'yes' ) {
            $image_path = str_replace( 'http:', 'https:', $image_path );
        }
        if ($this->paypal_account_optional == 'no' && $this->show_paypal_credit == 'yes' && $this->checkout_with_pp_button_type == 'paypalimage') {
            $image_path = plugins_url('/assets/images/paypal.png', plugin_basename(dirname(__FILE__)));
            if ( is_ssl() || get_option( 'woocommerce_force_ssl_checkout' ) == 'yes' ) {
                $image_path = str_replace( 'http:', 'https:', $image_path );
            }
            $image_path_two = plugins_url('/assets/images/PP_credit_logo.png', plugin_basename(dirname(__FILE__)));
            if ( is_ssl() || get_option( 'woocommerce_force_ssl_checkout' ) == 'yes' ) {
                $image_path_two = str_replace( 'http:', 'https:', $image_path_two );
            }
            $icon = "<img src=\"$image_path\" alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
            $icon_two = "<img src=\"$image_path_two\" alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
            return apply_filters('angelleye_ec_checkout_icon', $icon.$icon_two, $this->id);
        } else {
            $icon = "<img src=\"$image_path\" alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
            return apply_filters('angelleye_ec_checkout_icon', $icon, $this->id);
        }
    }

    public function init_form_fields() {
        $rest_url = get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=paypal_express&pms_reset=true';
        $require_ssl = '';
        if (!AngellEYE_Gateway_Paypal::is_ssl()) {
            $require_ssl = __('This image requires an SSL host.  Please upload your image to <a target="_blank" href="http://www.sslpic.com">www.sslpic.com</a> and enter the image URL here.', 'paypal-for-woocommerce');
        }
        $skip_final_review_option_not_allowed_guest_checkout = '';
        $skip_final_review_option_not_allowed_terms = '';
        $skip_final_review_option_not_allowed_tokenized_payments = '';
        $woocommerce_enable_guest_checkout = get_option('woocommerce_enable_guest_checkout');
        if( 'yes' === get_option( 'woocommerce_registration_generate_username' ) && 'yes' === get_option( 'woocommerce_registration_generate_password' ) ) {
            $woocommerce_enable_guest_checkout = 'yes';
        }
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
        $this->testmode = 'yes' === $this->get_option('testmode', 'yes');
        $this->paypal_marketing_solutions_cid_production = $this->get_option('paypal_marketing_solutions_cid_production', '');
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
                'description' => __('Get your live account API credentials from your PayPal account profile <br />or by using <a target="_blank" href="https://www.paypal.com/us/cgi-bin/webscr?cmd=_login-api-run">this tool</a>.', 'paypal-for-woocommerce'),
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
            'review_title_page' => array(
                'title' => __('Order Review Page Title', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title of order review page which the user sees during checkout.', 'paypal-for-woocommerce'),
                'desc_tip' => true,
                'default' => 'Review Order'
            ),
            'checkout_with_pp_button_type' => array(
                'title' => __('Checkout Button Type', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class'    => 'checkout_with_pp_button_type wc-enhanced-select',
                'label' => __('Use Checkout with PayPal image button', 'paypal-for-woocommerce'),
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
                'class' => 'pp_button_type_my_custom, button_upload',
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
            'show_on_minicart' => array(
                'title' => __('Minicart', 'paypal-for-woocommerce'),
                'label' => __('Show Express Checkout button in the WooCommerce Minicart.', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'default' => 'yes'
            ),
            'button_position' => array(
                'title' => __('Cart Button Position', 'paypal-for-woocommerce'),
                'label' => __('Where to display PayPal Express Checkout button(s).', 'paypal-for-woocommerce'),
                'description' => __('Set where to display the PayPal Express Checkout button(s).'),
                'type' => 'select',
                'class'    => 'wc-enhanced-select',
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
                'class'    => 'wc-enhanced-select',
                'description' => __('Displaying the checkout button at the top of the checkout page will allow users to skip filling out the forms and can potentially increase conversion rates.'),
                'desc_tip' => true,
            ),
            'show_on_product_page' => array(
                'title' => __('Product Page', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Show the Express Checkout button on product detail pages.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'description' => sprintf(__('Allows customers to checkout using PayPal directly from a product page. Do not forget to enable Express Checkout on product details page.  You can use the <a href="%s" target="_blank">Bulk Update Tool</a> to Enable Express Checkout on multiple products at once.', 'paypal-for-woocommerce'), admin_url( 'options-general.php?page=paypal-for-woocommerce&tab=tabs' )),
                'desc_tip' => false,
                
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
                'class'    => 'wc-enhanced-select',
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
                'class' => 'error_display_type_option wc-enhanced-select',
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
                'description' => ($this->is_us_or_uk == false) ? __('Currently disabled because PayPal Credit is only available for U.S. and U.K merchants.', 'paypal-for-woocommerce') : "",
                'desc_tip' => ($this->is_us_or_uk) ? true : false,
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
            'softdescriptor' => array(
                'title' => __('Credit Card Statement Name', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('The value entered here will be displayed on the buyer\'s credit card statement.', 'paypal-for-woocommerce'),
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
                'class'    => 'wc-enhanced-select',
                'options' => array(
                    'Sale' => 'Sale',
                    'Authorization' => 'Authorization',
                    'Order' => 'Order'
                ),
                'default' => 'Sale',
                'desc_tip' => true,
            ),
            'pending_authorization_order_status' => array(
                'title' => __('Pending Authorization Order Status', 'paypal-for-woocommerce'),
                'label' => __('Pending Authorization Order Status.', 'paypal-for-woocommerce'),
                'description' => __('Set the order status you would like to use when an order has been authorized but has not yet been captured.'),
                'type' => 'select',
                'class'    => 'wc-enhanced-select',
                'options' => array(
                    'On Hold' => 'On Hold',
                    'Processing' => 'Processing'
                ),
                'default' => 'On Hold',
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
                'class'    => 'wc-enhanced-select',
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
            'prevent_to_add_additional_item' => array(
                'title' => __('Prevent Adding Extra Item', 'paypal-for-woocommerce'),
                'label' => __('Prevent adding an addition unit to the shopping cart when the Express Checkout button is pushed from the product page.', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('By default, clicking the PayPal Express Checkout button from the product page adds 1 unit of that item to the cart before sending the user to PayPal.  This option will disable that, and send the user without adding an additional unit.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'class' => '',
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
                'class' => 'order_cancellations wc-enhanced-select',
                'options' => array(
                    'no_seller_protection' => __('Do *not* have PayPal Seller Protection', 'paypal-for-woocommerce'),
                    'no_unauthorized_payment_protection' => __('Do *not* have PayPal Unauthorized Payment Protection', 'paypal-for-woocommerce'),
                    'disabled' => __('Do not cancel any orders', 'paypal-for-woocommerce'),
                ),
                'default' => 'disabled',
                'desc_tip' => true,
            ),
            'fraud_management_filters' => array(
                'title' => __('Fraud Management Filters ', 'paypal-for-woocommerce'),
                'label' => '',
                'description' => __('Choose how you would like to handle orders when Fraud Management Filters are flagged.', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class'    => 'wc-enhanced-select',
                'options' => array(
                    'ignore_warnings_and_proceed_as_usual' => __('Ignore warnings and proceed as usual.', 'paypal-for-woocommerce'),
                    'place_order_on_hold_for_further_review' => __('Place order On Hold for further review.', 'paypal-for-woocommerce'),
                ),
                'default' => 'place_order_on_hold_for_further_review',
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
            'save_abandoned_checkout' => array(
                'title' => __('Save Abandoned Checkouts', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('If a buyer choose to pay with PayPal from the WooCommerce checkout page, but they never return from PayPal, this will save the order as pending with all available details to that point.  Note that this will not work when Express Checkout Shortcut buttons are used.'),
                'default' => 'no'
            ),
            'enable_in_context_checkout_flow' => array(
                'title' => __('Enable In-Context', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('The enhanced PayPal Express Checkout with In-Context gives your customers a simplified checkout experience that keeps them at your website throughout the payment authorization process.', 'paypal-for-woocommerce'),
                'default' => 'no'
            ),
            
            'debug' => array(
                'title' => __('Debug', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => sprintf(__('Enable logging<code>%s</code>', 'paypal-for-woocommerce'), version_compare(WC_VERSION, '3.0', '<') ? wc_get_log_file_path('paypal_express') : WC_Log_Handler_File::get_log_file_path('paypal_express')),
                'default' => 'no'
            ),
            'is_encrypt' => array(
                'title' => __('', 'paypal-for-woocommerce'),
                'label' => __('', 'paypal-for-woocommerce'),
                'type' => 'hidden',
                'default' => 'yes',
                'class' => ''
            ),
         );
        if( $this->is_us == true ) {
            $this->form_fields['paypal_marketing_solutions'] = array(
                'title'       => __( '<hr></hr>PayPal Marketing Solutions', 'paypal-for-woocommerce' ),
		'type'        => 'title',
		'description' => __( '<div id="pms-muse-container">
				<div class="pms-muse-left-container">
					<div class="pms-muse-description">
						<p>' . __('Get free business insights into your customers’ shopping habits; like how often they shop, how much they spend, and how they interact with your website.', 'wp-paypal-marketing-solutions') . '</p>
                                                <p>' . __('Help drive sales by displaying relevant PayPal offers and promotional messages to your customers on your website. Manage Settings to choose which messages, if any, you want to show, as well as how and where these messages appear on your website.', 'wp-paypal-marketing-solutions') . '</p>
                                                <p class="display_when_deactivated">' . __('All FREE to you as a valued PayPal merchant. Simply ‘Enable’ now!', 'wp-paypal-marketing-solutions') . '</p>
                                                <p class="display_when_deactivated">' . __('By enabling, you acknowledge that you have the right to use the PayPal Insights tool and to collect information from shoppers on your site.  See <a target="_blank" href="https://www.paypal.com/webapps/mpp/ua/useragreement-full">terms and conditions</a>.', 'wp-paypal-marketing-solutions') . '</p> 
                                                <p class="display_when_deactivated">' . __('By enabling, you acknowledge that you have agreed to, and accepted the terms of, the PayPal User Agreement, including the <a target="_blank" href="https://www.paypal.com/webapps/mpp/ua/useragreement-full">terms and conditions</a> thereof applicable to the PayPal Advertising Program.', 'wp-paypal-marketing-solutions') . '</p> 
                                                <p class="display_msg_when_activated"></p>
					</div>
                                        <div class="wrap">
                                            <div id="angelleye_wp_marketing_solutions_button_production"></div>
                                            <div id="pms-paypalInsightsLink"><button class="paypal-px-btn view-paypal-insight-result">' . __('View Shopper Insights', '') . '</button></div>
                                        </div>
				</div>
				<div class="pms-muse-right-container">
					<div>
                                            <img src="' . PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/images/muse1.png"/>
                                            <div><p>' . __('Merchants like you have increased their average order value (AOV) by <b>up to 68%*</b>.', 'wp-paypal-marketing-solutions') . '</p></div>
					</div>
					<div>
                                            <img src="' . PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/images/muse2.png"/>
                                            <div><p>' . __('Join <b>20,000 merchants</b> who are promoting financing options on their site to boost sales.', 'wp-paypal-marketing-solutions') . '</p></div>
					</div>
					<div>
                                            <img src="' . PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/images/muse3.png"/>
                                            <div><p>' . __('<b>Get insights</b> about your visitors and how they shop on your site.', 'wp-paypal-marketing-solutions') . '</p></div>
					</div>
                                        <div class="wrap pms-center-moreinfo">
                                        <div>
                                            <div><a href="#TB_inline?&width=889&height=558&inlineId=more-info-popup" class="thickbox"><button class="pms-view-more paypal-px-btn">More Info</button></a></div>
                                        </div>
				</div>
			</div>
                ', 'paypal-for-woocommerce' ),
            );
            $this->form_fields['paypal_marketing_solutions_enabled'] = array(
                  'title'       => __( '', 'paypal-for-woocommerce' ),
                  'type'        => 'checkbox',
                  'label'       => '&nbsp;&nbsp;Enable PayPal Marketing Solutions',
                  'default'     => 'no',
                 'class' => 'checkbox',
                  'desc_tip'    => true,
                  'description' => __( 'This enables PayPal Marketing Solutions for valuable customer insights.' ),
            );
            $this->form_fields['paypal_marketing_solutions_cid_production'] = array(
                'type'        => 'hidden',
                'default'     => '',

            );
            $this->form_fields['paypal_marketing_solutions_details_note'] = array(
                'type'        => 'title',
                'default'     => '',
                'description' => '<p class="font11">' . __("* As reported in Nielsen’s PayPal Credit Average Order Value Study for activity occurring from April 2015 to March 2016 (small merchants) and October 2015 to March 2016 (midsize merchants), which compared PayPal Credit transactions to credit and debit card transactions on websites that offer PayPal Credit as a payment option or within the PayPal Wallet. Nielsen measured 284890 transactions across 27 mid and small merchants. Copyright Nielsen 2016.", 'paypal-for-woocommerce') . '<hr>',
            );
        }
        $this->form_fields = apply_filters('angelleye_ec_form_fields', $this->form_fields);
    }

    public function is_available() {
        if (AngellEYE_Utility::is_express_checkout_credentials_is_set() == false) {
            return false;
        }
        if(!AngellEYE_Utility::is_valid_for_use_paypal_express()) {
           return false;
        }
        if( $this->show_on_checkout != 'regular' && $this->show_on_checkout != 'both') {
            if ($this->function_helper->ec_is_express_checkout()) {
                return true;
            } else {
                return false;
            }
        }
        return parent::is_available();
    }

    public function payment_fields() {
        if ($description = $this->get_description()) {
            echo wpautop(wptexturize($description));
        }
        if($this->function_helper->ec_is_express_checkout() == false) {
            $this->new_method_label = __('Create a new billing agreement', 'paypal-for-woocommerce');
            if ($this->supports('tokenization') && is_checkout()) {
                $this->tokenization_script();
                $this->saved_payment_methods();
                 if( AngellEYE_Utility::is_cart_contains_subscription() == false ) {
                    $this->save_payment_method_checkbox();
                 }
                do_action('payment_fields_saved_payment_methods', $this);
            }
        }
    }
    
    public function save_payment_method_checkbox() {
        printf(
                '<p class="form-row woocommerce-SavedPaymentMethods-saveNew">
                        <input id="wc-%1$s-new-payment-method" name="wc-%1$s-new-payment-method" type="checkbox" value="true" style="width:auto;" />
                        <label for="wc-%1$s-new-payment-method" style="display:inline;">%2$s</label>
                </p>',
                esc_attr( $this->id ),
                apply_filters( 'cc_form_label_save_to_account', __( 'Save payment method to my account.', 'woocommerce' ), $this->id)
        );
    }

    public function process_subscription_payment($order_id) {
        $order = wc_get_order($order_id);
        if ($this->is_subscription($order_id)) {
            $this->angelleye_reload_gateway_credentials_for_woo_subscription_renewal_order($order);
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/express-checkout/class-wc-gateway-paypal-express-request-angelleye.php' );
            $paypal_express_request = new WC_Gateway_PayPal_Express_Request_AngellEYE($this);
            $result = $paypal_express_request->DoReferenceTransaction($order_id);
            if ($result['ACK'] == 'Success' || $result['ACK'] == 'SuccessWithWarning') {
                $paypal_express_request->update_payment_status_by_paypal_responce($order_id, $result);
                return array(
                    'result' => 'success',
                    'redirect' => add_query_arg( 'utm_nooverride', '1', $this->get_return_url($order) )
                );
            } else {
                $redirect_url = get_permalink(wc_get_page_id('cart'));
                $this->paypal_express_checkout_error_handler($request_name = 'DoReferenceTransaction', $redirect_url, $result);
            }
        }
    }
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        try {
            if (!empty($_POST['wc-paypal_express-payment-token']) && $_POST['wc-paypal_express-payment-token'] != 'new') {
                $result = $this->angelleye_ex_doreference_transaction($order_id);
                if ($result['ACK'] == 'Success' || $result['ACK'] == 'SuccessWithWarning') {
                    $_POST = WC()->session->get( 'post_data' );
                    $order->payment_complete($result['TRANSACTIONID']);
                    $order->add_order_note(sprintf(__('%s payment approved! Transaction ID: %s', 'paypal-for-woocommerce'), $this->title, $result['TRANSACTIONID']));
                    WC()->cart->empty_cart();
                    return array(
                        'result' => 'success',
                        'redirect' => add_query_arg( 'utm_nooverride', '1', $this->get_return_url($order) )
                    );
                } else {
                    $redirect_url = get_permalink(wc_get_page_id('cart'));
                    $this->paypal_express_checkout_error_handler($request_name = 'DoReferenceTransaction', $redirect_url, $result);
                }
            }
            if ($this->function_helper->ec_is_express_checkout()) {
                $return_url = add_query_arg('order_id', $order_id, $this->function_helper->ec_get_checkout_url('do_express_checkout_payment', $order_id));
                if( is_user_logged_in() && !empty($_POST['ship_to_different_address']) && $_POST['ship_to_different_address'] == '1') {
                } else {
                    if( empty($_POST['shipping_country'] ) ) {
                        $paypal_express_checkout = WC()->session->get( 'paypal_express_checkout' );
                        $shipping_details = isset($paypal_express_checkout['shipping_details']) ? $paypal_express_checkout['shipping_details'] : array();
                        AngellEYE_Utility::angelleye_set_address($order_id, $shipping_details, 'shipping');
                    }
                }
                $post_data = WC()->session->get('post_data');
                if ($this->billing_address && empty($post_data)) {
                    if( empty($_POST['billing_country'] ) ) {
                        $paypal_express_checkout = WC()->session->get( 'paypal_express_checkout' );
                        $shipping_details = isset($paypal_express_checkout['shipping_details']) ? $paypal_express_checkout['shipping_details'] : array();
                        AngellEYE_Utility::angelleye_set_address($order_id, $shipping_details, 'billing');
                    }
                }
                $args = array(
                    'result' => 'success',
                    'redirect' => $return_url,
                );
                if (isset($_POST['terms']) && wc_get_page_id('terms') > 0) {
                    WC()->session->set( 'paypal_express_terms', true );
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
                require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/express-checkout/class-wc-gateway-paypal-express-request-angelleye.php' );
                $paypal_express_request = new WC_Gateway_PayPal_Express_Request_AngellEYE($this);
                if( !empty($_GET['pay_for_order']) && $_GET['pay_for_order'] == true) {
                    $paypal_express_request->angelleye_set_express_checkout();
                }
                if (isset($_POST['terms']) && wc_get_page_id('terms') > 0) {
                    WC()->session->set( 'paypal_express_terms', true );
                }
                WC()->session->set( 'post_data', $_POST);
                $_GET['pp_action'] = 'set_express_checkout';
                $this->handle_wc_api();
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ex_doreference_transaction($order_id) {
        require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/express-checkout/class-wc-gateway-paypal-express-request-angelleye.php' );
        $paypal_express_request = new WC_Gateway_PayPal_Express_Request_AngellEYE($this);
        return $paypal_response = $paypal_express_request->DoReferenceTransaction($order_id);
    }

    public function angelleye_express_checkout_encrypt_gateway_api($settings) {
        if( !empty($settings['sandbox_api_password'])) {
            $api_password = $settings['sandbox_api_password'];
        } else {
            $api_password = $settings['api_password'];
        }
        if(strlen($api_password) > 35 ) {
            return $settings;
        }
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
        $wpml_locale = self::angelleye_ec_get_wpml_locale();
        if ($wpml_locale) {
            if (in_array($wpml_locale, $_supportedLocale)) {
                return $wpml_locale;
            }
        }
        $locale = get_locale();
        if (get_locale() != '') {
            $locale = substr(get_locale(), 0, 5);
        }
        if (!in_array($locale, $_supportedLocale)) {
            $locale = 'en_US';
        }
        return $locale;
    }

    public static function angelleye_get_paypalimage() {
        if (self::get_button_locale_code() == 'en_US') {
            $image_path = plugins_url('/assets/images/dynamic-image/' . self::get_button_locale_code() . '.png', plugin_basename(dirname(__FILE__)));
        } else {
            $image_path = plugins_url('/assets/images/dynamic-image/' . self::get_button_locale_code() . '.gif', plugin_basename(dirname(__FILE__)));
            if ( is_ssl() || get_option( 'woocommerce_force_ssl_checkout' ) == 'yes' ) {
                $image_path = preg_replace("/^http:/i", "https:", $image_path);
            }
        }
        
        return $image_path;
    }

    public function handle_wc_api() {
        try {
            $this->angelleye_check_cart_items();
            $old_wc = version_compare(WC_VERSION, '3.0', '<');
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/express-checkout/class-wc-gateway-paypal-express-request-angelleye.php' );
            $paypal_express_request = new WC_Gateway_PayPal_Express_Request_AngellEYE($this);
            if (!isset($_GET['pp_action'])) {
                return;
            }
            if (!defined('WOOCOMMERCE_CHECKOUT')) {
                define('WOOCOMMERCE_CHECKOUT', true);
            }
            if (!defined('WOOCOMMERCE_CART')) {
                define('WOOCOMMERCE_CART', true);
            }
            WC()->cart->calculate_totals();
            WC()->cart->calculate_shipping();
            if (version_compare(WC_VERSION, '3.0', '<')) {
                WC()->customer->calculated_shipping(true);
            } else {
                WC()->customer->set_calculated_shipping(true);
            }

            if (WC()->cart->cart_contents_total <= 0 && WC()->cart->total <= 0 && AngellEYE_Utility::is_cart_contains_subscription() == false) {
                if( empty($_GET['pay_for_order']) ) {
                    if( AngellEYE_Utility::is_cart_contains_subscription() == false ) {
                    wc_add_notice(__('your order amount is zero, We were unable to process your order, please try again.', 'paypal-for-woocommerce'), 'error');
                    }
                    $paypal_express_request->angelleye_redirect();
                    exit;
                }
            }

            switch ($_GET['pp_action']) {
                case 'cancel_order':
                    $this->function_helper->ec_clear_session_data();
                     $cancel_url = !empty($this->cancel_page_id) ? get_permalink($this->cancel_page_id) : wc_get_cart_url();
                     wp_safe_redirect( $cancel_url );
                     exit;
                case 'set_express_checkout':
                    if ((isset($_POST['wc-paypal_express-new-payment-method']) && $_POST['wc-paypal_express-new-payment-method'] = 'on') || ( isset($_GET['ec_save_to_account']) && $_GET['ec_save_to_account'] == true)) {
                        WC()->session->set( 'ec_save_to_account', 'on' );
                    }
                    $paypal_express_request->angelleye_set_express_checkout();
                    break;
                case 'get_express_checkout_details':
                    $paypal_express_request->angelleye_get_express_checkout_details();
                    $order_id = absint(WC()->session->get('order_awaiting_payment'));
                    if( !empty($_GET['pay_for_order']) && $_GET['pay_for_order'] == true ) {
                    } else {
                        if ( $order_id > 0 && ( $order = wc_get_order( $order_id ) ) && $order->has_status( array( 'pending', 'failed' ) ) ) {
                            $_POST = WC()->session->get( 'post_data' );
                            $this->posted = WC()->session->get( 'post_data' );
                            $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');
                            if (isset($_POST['shipping_method']) && is_array($_POST['shipping_method']))
                                foreach ($_POST['shipping_method'] as $i => $value)
                                    $chosen_shipping_methods[$i] = wc_clean($value);
                            WC()->session->set('chosen_shipping_methods', $chosen_shipping_methods);
                            if (WC()->cart->needs_shipping()) {
                                // Validate Shipping Methods
                                WC()->shipping->get_shipping_methods();
                                $packages = WC()->shipping->get_packages();
                                WC()->checkout()->shipping_methods = WC()->session->get('chosen_shipping_methods');
                            }
                            if (empty($this->posted)) {
                                $this->posted = array();
                                $paypal_express_checkout = WC()->session->get( 'paypal_express_checkout' );
                                if( !empty($paypal_express_checkout['shipping_details']['email'])) {
                                    $this->posted['billing_email'] = $paypal_express_checkout['shipping_details']['email'];
                                }
                                if( !empty($paypal_express_checkout['shipping_details']['first_name'])) {
                                    $this->posted['billing_first_name'] = $paypal_express_checkout['shipping_details']['first_name'];
                                }
                                if( !empty($paypal_express_checkout['shipping_details']['last_name'])) {
                                    $this->posted['billing_last_name'] = $paypal_express_checkout['shipping_details']['last_name'];
                                }
                                $this->posted['payment_method'] = $this->id;

                            }
                            $this->angelleye_check_cart_items();
                            $order_id = WC()->checkout()->create_order($this->posted);
                            if (is_wp_error($order_id)) {
                                throw new Exception($order_id->get_error_message());
                            }
                            if ( ! is_user_logged_in() && WC()->checkout->is_registration_required($order_id) ) {
                                $paypal_express_request->angelleye_process_customer($order_id);
                            }
                            do_action('woocommerce_checkout_order_processed', $order_id, $this->posted);
                        } else {
                            $_POST = WC()->session->get( 'post_data' );
                            $this->posted = WC()->session->get( 'post_data' );
                        }
                        if ( $order_id == 0 ) {
                            $_POST = WC()->session->get( 'post_data' );
                            $this->posted = WC()->session->get( 'post_data' );
                            $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');
                            if (isset($_POST['shipping_method']) && is_array($_POST['shipping_method']))
                                foreach ($_POST['shipping_method'] as $i => $value)
                                    $chosen_shipping_methods[$i] = wc_clean($value);
                            WC()->session->set('chosen_shipping_methods', $chosen_shipping_methods);
                            if (WC()->cart->needs_shipping()) {
                                // Validate Shipping Methods
                                WC()->shipping->get_shipping_methods();
                                $packages = WC()->shipping->get_packages();
                                WC()->checkout()->shipping_methods = WC()->session->get('chosen_shipping_methods');
                            }
                            if (empty($this->posted)) {
                                $this->posted = array();
                                $paypal_express_checkout = WC()->session->get( 'paypal_express_checkout' );
                                if( !empty($paypal_express_checkout['shipping_details']['email'])) {
                                    $this->posted['billing_email'] = $paypal_express_checkout['shipping_details']['email'];
                                } elseif( !empty ($paypal_express_checkout['ExpresscheckoutDetails']['EMAIL'])) {
                                    $this->posted['billing_email'] = $paypal_express_checkout['ExpresscheckoutDetails']['EMAIL'];
                                }
                                if( !empty($paypal_express_checkout['shipping_details']['first_name'])) {
                                    $this->posted['billing_first_name'] = $paypal_express_checkout['shipping_details']['first_name'];
                                } elseif( !empty ($paypal_express_checkout['ExpresscheckoutDetails']['FIRSTNAME'])) {
                                    $this->posted['billing_first_name'] = $paypal_express_checkout['ExpresscheckoutDetails']['FIRSTNAME'];
                                }
                                if( !empty($paypal_express_checkout['shipping_details']['last_name'])) {
                                    $this->posted['billing_last_name'] = $paypal_express_checkout['shipping_details']['last_name'];
                                } elseif( !empty ($paypal_express_checkout['ExpresscheckoutDetails']['LASTNAME'])) {
                                    $this->posted['billing_last_name'] = $paypal_express_checkout['ExpresscheckoutDetails']['LASTNAME'];
                                }
                                $this->posted['payment_method'] = $this->id;
                            }
                            $this->angelleye_check_cart_items();
                            $order_id = WC()->checkout()->create_order($this->posted);
                            if (is_wp_error($order_id)) {
                                throw new Exception($order_id->get_error_message());
                            }
                            if ( ! is_user_logged_in() && WC()->checkout->is_registration_required() ) {
                                $paypal_express_request->angelleye_process_customer($order_id);
                            }
                            do_action('woocommerce_checkout_order_processed', $order_id, $this->posted);
                        }
                        $order = wc_get_order($order_id);
                        $post_data = WC()->session->get('post_data');
                        if ($this->billing_address && empty($post_data)) {
                            $paypal_express_checkout = WC()->session->get( 'paypal_express_checkout' );
                            $shipping_details = isset($paypal_express_checkout['shipping_details']) ? $paypal_express_checkout['shipping_details'] : array();
                            AngellEYE_Utility::angelleye_set_address($order_id, $shipping_details, 'billing');
                        } else {
                            $billing_address = array();
                            $checkout_fields['billing'] = WC()->countries->get_address_fields(WC()->checkout->get_value('billing_country'), 'billing_');
                            if ($checkout_fields['billing']) {
                                foreach (array_keys($checkout_fields['billing']) as $field) {
                                    $field_name = str_replace('billing_', '', $field);
                                    $billing_address[$field_name] = $this->angelleye_ec_get_posted_address_data($field_name);
                                }
                            }
                            AngellEYE_Utility::angelleye_set_address($order_id, $billing_address, 'billing');
                        }
                        $paypal_express_checkout = WC()->session->get( 'paypal_express_checkout' );
                        $shipping_details = isset($paypal_express_checkout['shipping_details']) ? $paypal_express_checkout['shipping_details'] : array();
                        AngellEYE_Utility::angelleye_set_address($order_id, $shipping_details, 'shipping');
                        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
                        if ($old_wc) {
                            update_post_meta($order_id, '_payment_method', $this->id);
                            update_post_meta($order_id, '_payment_method_title', $this->title);
                            update_post_meta($order_id, '_customer_user', get_current_user_id());
                        } else {
                            $order->set_payment_method($this);
                            update_post_meta($order->get_id(), '_customer_user', get_current_user_id());
                        }
                        $post_data = WC()->session->get( 'post_data' );
                        if (!empty($post_data['billing_phone'])) {
                            if ($old_wc) {
                                update_post_meta($order_id, '_billing_phone', $post_data['billing_phone']);
                            } else {
                                update_post_meta($order->get_id(), '_billing_phone', $post_data['billing_phone']);
                            }
                        }
                        if (!empty($post_data['order_comments'])) {
                            if ($old_wc) {
                                update_post_meta($order_id, 'order_comments', $post_data['order_comments']);
                            } else {
                                update_post_meta($order->get_id(), 'order_comments', $post_data['order_comments']);
                            }
                            $my_post = array(
                                'ID' => $order_id,
                                'post_excerpt' => $post_data['order_comments'],
                            );
                            wp_update_post($my_post);
                        }
                        $_GET['order_id'] = $order_id;
                    }
                    $paypal_express_request->angelleye_do_express_checkout_payment();
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
            $old_wc = version_compare(WC_VERSION, '3.0', '<');
            $is_sandbox = $old_wc ? get_post_meta($order->id, 'is_sandbox', true) : get_post_meta($order->get_id(), 'is_sandbox', true);
            if ($is_sandbox == true) {
                $this->view_transaction_url = $sandbox_transaction_url;
            } else {
                if (empty($is_sandbox)) {
                    if ($this->testmode == true) {
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

    public function add_payment_method() {
        $SECFields = array(
            'returnurl' => add_query_arg(array(
                'do_action' => 'update_payment_method',
                'action_name' => 'SetExpressCheckout',
                'method_name' => 'paypal_express',
                'customer_id' => get_current_user_id()
                    ), home_url('/')),
            'cancelurl' => wc_get_account_endpoint_url('add-payment-method'),
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
        $result = $this->paypal_express_checkout_token_request_handler($PayPalRequest, 'SetExpressCheckout');
        if ($result['ACK'] == 'Success') {
            return array(
                'result' => 'success',
                'redirect' => $this->PAYPAL_URL . $result['TOKEN']
            );
        } else {
            $redirect_url = wc_get_account_endpoint_url('add-payment-method');
            $this->paypal_express_checkout_error_handler($request_name = 'SetExpressCheckout', $redirect_url, $result);
        }
    }

    public function paypal_express_checkout_token_request_handler($PayPalRequest = array(), $action_name = '') {
        if (!class_exists('Angelleye_PayPal')) {
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.class.php' );
        }
        $PayPalConfig = array(
            'Sandbox' => $this->testmode,
            'APIUsername' => $this->api_username,
            'APIPassword' => $this->api_password,
            'APISignature' => $this->api_signature,
            'Force_tls_one_point_two' => $this->Force_tls_one_point_two
        );
        $PayPal = new Angelleye_PayPal($PayPalConfig);
        if (!empty($PayPalRequest) && !empty($action_name)) {
            if ('SetExpressCheckout' == $action_name) {
                $PayPalResult = $PayPal->SetExpressCheckout(apply_filters('angelleye_woocommerce_express_set_express_checkout_request_args', $PayPalRequest));
                AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($PayPalResult, $methos_name = 'SetExpressCheckout', $gateway = 'PayPal Express Checkout', $this->error_email_notify);
                self::log('Test Mode: ' . $this->testmode);
                self::log('Endpoint: ' . $this->API_Endpoint);
                $PayPalRequest = isset($PayPalResult['RAWREQUEST']) ? $PayPalResult['RAWREQUEST'] : '';
                $PayPalResponse = isset($PayPalResult['RAWRESPONSE']) ? $PayPalResult['RAWRESPONSE'] : '';
                self::log('Request: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalRequest)), true));
                self::log('Response: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalResponse)), true));
                return $PayPalResult;
            }
        }
        if (!empty($_GET['method_name']) && $_GET['method_name'] == 'paypal_express') {
            if ($_GET['action_name'] == 'SetExpressCheckout') {
                $PayPalResult = $PayPal->GetExpressCheckoutDetails($_GET['token']);
                if ($PayPalResult['ACK'] == 'Success') {
                    $data = array(
                        'METHOD' => 'CreateBillingAgreement',
                        'TOKEN' => $_GET['token']
                    );
                    $billing_result = $PayPal->CreateBillingAgreement($_GET['token']);
                    if ($billing_result['ACK'] == 'Success') {
                        if (!empty($billing_result['BILLINGAGREEMENTID'])) {
                            $billing_agreement_id = $billing_result['BILLINGAGREEMENTID'];
                            $token = new WC_Payment_Token_CC();
                            $customer_id = get_current_user_id();
                            $token->set_token($billing_agreement_id);
                            $token->set_gateway_id($this->id);
                            $token->set_card_type('PayPal Billing Agreement');
                            $token->set_last4(substr($billing_agreement_id, -4));
                            $token->set_expiry_month(date('m'));
                            $token->set_expiry_year(date('Y', strtotime('+20 year')));
                            $token->set_user_id($customer_id);
                            if( $token->validate() ) {
                                $save_result = $token->save();
                                wp_redirect(wc_get_account_endpoint_url('payment-methods'));
                                exit();
                            } else {
                                throw new Exception( __( 'Invalid or missing payment token fields.', 'paypal-for-woocommerce' ) );
                            }
                        }
                    }
                } else {
                    $redirect_url = wc_get_account_endpoint_url('add-payment-method');
                    $this->paypal_express_checkout_error_handler($request_name = 'GetExpressCheckoutDetails', $redirect_url, $PayPalResult);
                }
            }
        }
    }

    public function paypal_express_checkout_error_handler($request_name = '', $redirect_url = '', $result) {
        $ErrorCode = urldecode($result["L_ERRORCODE0"]);
        $ErrorShortMsg = urldecode($result["L_SHORTMESSAGE0"]);
        $ErrorLongMsg = urldecode($result["L_LONGMESSAGE0"]);
        $ErrorSeverityCode = urldecode($result["L_SEVERITYCODE0"]);
        self::log(__($request_name . 'API call failed. ', 'paypal-for-woocommerce'));
        self::log(__('Detailed Error Message: ', 'paypal-for-woocommerce') . $ErrorLongMsg);
        self::log(__('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg);
        self::log(__('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode);
        self::log(__('Error Severity Code: ', 'paypal-for-woocommerce') . $ErrorSeverityCode);
        $message = '';
        if ($this->error_email_notify) {
            $admin_email = get_option("admin_email");
            $message .= __($request_name . " API call failed.", "paypal-for-woocommerce") . "\n\n";
            $message .= __('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode . "\n";
            $message .= __('Error Severity Code: ', 'paypal-for-woocommerce') . $ErrorSeverityCode . "\n";
            $message .= __('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg . "\n";
            $message .= __('Detailed Error Message: ', 'paypal-for-woocommerce') . $ErrorLongMsg . "\n";
            $message .= __('User IP: ', 'paypal-for-woocommerce') . AngellEYE_Utility::get_user_ip() . "\n";
            $error_email_notify_mes = apply_filters('ae_ppec_error_email_message', $message, $ErrorCode, $ErrorSeverityCode, $ErrorShortMsg, $ErrorLongMsg);
            $subject = "PayPal Express Checkout Error Notification";
            $error_email_notify_subject = apply_filters('ae_ppec_error_email_subject', $subject);
            wp_mail($admin_email, $error_email_notify_subject, $error_email_notify_mes);
        }
        if ($this->error_display_type == 'detailed') {
            $sec_error_notice = $ErrorCode . ' - ' . $ErrorLongMsg;
            $error_display_type_message = sprintf(__($sec_error_notice, 'paypal-for-woocommerce'));
        } else {
            $error_display_type_message = sprintf(__('There was a problem paying with PayPal.  Please try another method.', 'paypal-for-woocommerce'));
        }
        $error_display_type_message = apply_filters('ae_ppec_error_user_display_message', $error_display_type_message, $ErrorCode, $ErrorLongMsg);
        if( AngellEYE_Utility::is_cart_contains_subscription() == false ) {
            if(function_exists('wc_add_notice')) {
                wc_add_notice($error_display_type_message, 'error');
            }
        }
        if(is_admin()) {
            return false;
        }
        if (!is_ajax()) {
            wp_redirect($redirect_url);
            exit;
        } else {
            return array(
                'result' => 'fail',
                'redirect' => $redirect_url
            );
        }
    }

    public static function log($message, $level = 'info', $source = null) {
        if($source == null ) {
            $source = 'paypal_express';
        }
        if (self::$log_enabled) {
            if (version_compare(WC_VERSION, '3.0', '<')) {
                if (empty(self::$log)) {
                    self::$log = new WC_Logger();
                }
                self::$log->add('paypal_express', $message);
            } else {
                if (empty(self::$log)) {
                    self::$log = wc_get_logger();
                }
                self::$log->log($level, $message, array('source' => $source));
            }
        }
    }

    public function angelleye_ec_get_posted_address_data($key, $type = 'billing') {
        if ('billing' === $type || false === $this->posted['ship_to_different_address']) {
            $return = isset($this->posted['billing_' . $key]) ? $this->posted['billing_' . $key] : '';
        } else {
            $return = isset($this->posted['shipping_' . $key]) ? $this->posted['shipping_' . $key] : '';
        }
        if ('email' === $key && empty($return) && is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $return = $current_user->user_email;
        }
        return $return;
    }

    public static function angelleye_ec_get_wpml_locale() {
        $locale = false;
        if(defined('ICL_LANGUAGE_CODE') && function_exists('icl_object_id')){
            global $sitepress;
            if ( isset( $sitepress )) { // avoids a fatal error with Polylang
                $locale = $sitepress->get_current_language();
            } else if ( function_exists( 'pll_current_language' ) ) { // adds Polylang support
                $locale = pll_current_language('locale'); //current selected language requested on the broswer
            } else if ( function_exists( 'pll_default_language' ) ) {
                $locale = pll_default_language('locale'); //default lanuage of the blog
            }
        } 
        return $locale;
    }

    public function free_signup_order_payment($order_id) {
        $order = new WC_Order($order_id);
        $this->log('Processing order #' . $order_id);
        if (!empty($_POST['wc-paypal_express-payment-token']) && $_POST['wc-paypal_express-payment-token'] != 'new') {
            $token_id = wc_clean($_POST['wc-paypal_express-payment-token']);
            $token = WC_Payment_Tokens::get($token_id);
            $order->payment_complete($token->get_token());
            update_post_meta($order_id, '_first_transaction_id', $token->get_token());
            $order->add_order_note('Payment Action: ' . $this->payment_action);
            WC()->cart->empty_cart();
            return array(
                'result' => 'success',
                'redirect' => add_query_arg( 'utm_nooverride', '1', $this->get_return_url($order) )
            );
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
        require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/express-checkout/class-wc-gateway-paypal-express-request-angelleye.php' );
        $paypal_express_request = new WC_Gateway_PayPal_Express_Request_AngellEYE($this);
        $response = $paypal_express_request->angelleye_process_refund($order_id, $amount, $reason);
        if ( is_wp_error( $response ) ) {
            self::log('Refund Error: ' . $response->get_error_message());
            throw new Exception( $response->get_error_message() );
        }
        if($response == true) {
            return true;
        }
    }
    
    public function is_subscription($order_id) {
        return ( function_exists('wcs_order_contains_subscription') && ( wcs_order_contains_subscription($order_id) || wcs_is_subscription($order_id) || wcs_order_contains_renewal($order_id) ) );
    }
    
    public function angelleye_check_cart_items() {
        try {
            WC()->checkout->check_cart_items();
        } catch (Exception $ex) {

        }            
        if( wc_notice_count( 'error' ) > 0 ) {
           self::log(print_r(wc_get_notices(), true));
            wc_clear_notices();
            $redirect_url = get_permalink(wc_get_page_id('cart'));
            wp_redirect($redirect_url);
            exit();
        }
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
                            $this->API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
                            $this->PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
                            $this->api_username = $this->get_option('sandbox_api_username');
                            $this->api_password = $this->get_option('sandbox_api_password');
                            $this->api_signature = $this->get_option('sandbox_api_signature');
                        }
                    }        
                }
            }
        }
    }
}
