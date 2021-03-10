<?php
if (!defined('ABSPATH')) {
    exit;
}

class Angelleye_PayPal_Express_Checkout_Helper {

    public $setting;
    public $function_helper;
    public $posted;
    public $version;
    public $is_fraudnet_ready = false;

    /**
     * The single instance of the class
     * @var Angelleye_PayPal_Express_Checkout_Helper
     */
    protected static $_instance = null;

    /**
     * Main Angelleye_PayPal_Express_Checkout_Helper Instance.
     *
     * Ensures only one instance of Angelleye_PayPal_Express_Checkout_Helper is loaded or can be loaded.
     *
     * @since 2.1
     * @static
     * @return WC_Emails Main instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        try {
            global $wpdb;
            $this->version = VERSION_PFW;
            $this->setting = AngellEYE_Utility::angelleye_get_pre_option(false, 'woocommerce_paypal_express_settings');
            $this->setting = !empty($this->setting) ? $this->setting : array();
            $this->enabled = !empty($this->setting['enabled']) ? $this->setting['enabled'] : 'no';
            if ($this->enabled == 'yes') {
                $this->paypal_flow_setting = AngellEYE_Utility::angelleye_get_pre_option(false, 'woocommerce_paypal_pro_payflow_settings');
                $this->paypal_flow_setting = !empty($this->paypal_flow_setting) ? $this->paypal_flow_setting : array();
                $this->paypal_pro_setting = AngellEYE_Utility::angelleye_get_pre_option(false, 'woocommerce_paypal_pro_settings');
                $this->paypal_pro_setting = isset($this->paypal_pro_setting) ? $this->paypal_pro_setting : array();
                $this->paypal_flow_enabled = !empty($this->paypal_flow_setting['enabled']) ? $this->paypal_flow_setting['enabled'] : 'no';
                $this->paypal_pro_enabled = !empty($this->paypal_pro_setting['enabled']) ? $this->paypal_pro_setting['enabled'] : 'no';
                $this->enable_tokenized_payments = !empty($this->setting['enable_tokenized_payments']) ? $this->setting['enable_tokenized_payments'] : 'no';
                if (class_exists('Paypal_For_Woocommerce_Multi_Account_Management')) {
                    $this->enable_tokenized_payments = 'no';
                }
                $this->checkout_with_pp_button_type = !empty($this->setting['checkout_with_pp_button_type']) ? $this->setting['checkout_with_pp_button_type'] : 'paypalimage';
                $this->pp_button_type_text_button = !empty($this->setting['pp_button_type_text_button']) ? $this->setting['pp_button_type_text_button'] : 'Proceed to Checkout';
                $this->pp_button_type_my_custom = !empty($this->setting['pp_button_type_my_custom']) ? $this->setting['pp_button_type_my_custom'] : WC_Gateway_PayPal_Express_AngellEYE::angelleye_get_paypalimage();
                $this->show_on_product_page = !empty($this->setting['show_on_product_page']) ? $this->setting['show_on_product_page'] : 'no';
                $this->review_title_page = !empty($this->setting['review_title_page']) ? $this->setting['review_title_page'] : 'Review Order';
                $this->show_on_checkout = !empty($this->setting['show_on_checkout']) ? $this->setting['show_on_checkout'] : 'both';
                $this->button_position = !empty($this->setting['button_position']) ? $this->setting['button_position'] : 'bottom';
                $this->show_on_cart = !empty($this->setting['show_on_cart']) ? $this->setting['show_on_cart'] : 'yes';
                $this->show_on_minicart = !empty($this->setting['show_on_minicart']) ? $this->setting['show_on_minicart'] : 'no';
                $this->prevent_to_add_additional_item_value = !empty($this->setting['prevent_to_add_additional_item']) ? $this->setting['prevent_to_add_additional_item'] : 'no';
                $this->prevent_to_add_additional_item = 'yes' === $this->prevent_to_add_additional_item_value;
                $this->testmode_value = !empty($this->setting['testmode']) ? $this->setting['testmode'] : 'yes';
                $this->testmode = 'yes' === $this->testmode_value;
                $this->billing_address_value = !empty($this->setting['billing_address']) ? $this->setting['billing_address'] : 'no';
                // Globale setting
                $this->disallowed_funding_methods = !empty($this->setting['disallowed_funding_methods']) ? (array) $this->setting['disallowed_funding_methods'] : array();
                $this->button_size = !empty($this->setting['button_size']) ? $this->setting['button_size'] : 'small';
                $this->button_height = !empty($this->setting['button_height']) ? $this->setting['button_height'] : '';
                $this->button_color = !empty($this->setting['button_color']) ? $this->setting['button_color'] : 'gold';
                $this->button_shape = !empty($this->setting['button_shape']) ? $this->setting['button_shape'] : 'pill';
                $this->button_label = !empty($this->setting['button_label']) ? $this->setting['button_label'] : 'checkout';
                $this->button_tagline = !empty($this->setting['button_tagline']) ? $this->setting['button_tagline'] : 'false';
                $this->button_layout = !empty($this->setting['button_layout']) ? $this->setting['button_layout'] : 'horizontal';
                // Product Page
                $this->single_product_configure_settings_value = !empty($this->setting['single_product_configure_settings']) ? $this->setting['single_product_configure_settings'] : 'no';
                $this->single_product_configure_settings = 'yes' === $this->single_product_configure_settings_value;
                if ($this->single_product_configure_settings) {
                    $this->single_product_button_layout = !empty($this->setting['single_product_button_layout']) ? $this->setting['single_product_button_layout'] : 'horizontal';
                    $this->single_product_button_size = !empty($this->setting['single_product_button_size']) ? $this->setting['single_product_button_size'] : 'small';
                    $this->single_product_button_height = !empty($this->setting['single_product_button_height']) ? $this->setting['single_product_button_height'] : '';
                    $this->single_product_button_label = !empty($this->setting['single_product_button_label']) ? $this->setting['single_product_button_label'] : 'checkout';
                    $this->single_product_disallowed_funding_methods = !empty($this->setting['single_product_disallowed_funding_methods']) ? $this->setting['single_product_disallowed_funding_methods'] : array();
                }
                // Cart Page
                $this->cart_configure_settings_value = !empty($this->setting['cart_configure_settings']) ? $this->setting['cart_configure_settings'] : 'no';
                $this->cart_configure_settings = 'yes' === $this->cart_configure_settings_value;
                if ($this->cart_configure_settings) {
                    $this->cart_button_layout = !empty($this->setting['cart_button_layout']) ? $this->setting['cart_button_layout'] : 'horizontal';
                    $this->cart_button_size = !empty($this->setting['cart_button_size']) ? $this->setting['cart_button_size'] : 'small';
                    $this->cart_button_height = !empty($this->setting['cart_button_height']) ? $this->setting['cart_button_height'] : '';
                    $this->cart_button_label = !empty($this->setting['cart_button_label']) ? $this->setting['cart_button_label'] : 'checkout';
                    $this->cart_disallowed_funding_methods = !empty($this->setting['cart_disallowed_funding_methods']) ? $this->setting['cart_disallowed_funding_methods'] : array();
                }
                // Mini Cart Page
                $this->mini_cart_configure_settings_value = !empty($this->setting['mini_cart_configure_settings']) ? $this->setting['mini_cart_configure_settings'] : 'no';
                $this->mini_cart_configure_settings = 'yes' === $this->mini_cart_configure_settings_value;
                if ($this->mini_cart_configure_settings) {
                    $this->mini_cart_button_layout = !empty($this->setting['mini_cart_button_layout']) ? $this->setting['mini_cart_button_layout'] : 'horizontal';
                    $this->mini_cart_button_size = !empty($this->setting['mini_cart_button_size']) ? $this->setting['mini_cart_button_size'] : 'small';
                    $this->mini_cart_button_height = !empty($this->setting['mini_cart_button_height']) ? $this->setting['mini_cart_button_height'] : '';
                    $this->mini_cart_button_label = !empty($this->setting['mini_cart_button_label']) ? $this->setting['mini_cart_button_label'] : 'checkout';
                    $this->mini_cart_disallowed_funding_methods = !empty($this->setting['mini_cart_disallowed_funding_methods']) ? $this->setting['mini_cart_disallowed_funding_methods'] : array();
                }
                // Checkout Page
                $this->checkout_page_configure_settings_value = !empty($this->setting['checkout_page_configure_settings']) ? $this->setting['checkout_page_configure_settings'] : 'no';
                $this->checkout_page_configure_settings = 'yes' === $this->checkout_page_configure_settings_value;
                if ($this->checkout_page_configure_settings) {
                    $this->checkout_page_button_layout = !empty($this->setting['checkout_page_button_layout']) ? $this->setting['checkout_page_button_layout'] : 'horizontal';
                    $this->checkout_page_button_size = !empty($this->setting['checkout_page_button_size']) ? $this->setting['checkout_page_button_size'] : 'small';
                    $this->checkout_page_button_height = !empty($this->setting['checkout_page_button_height']) ? $this->setting['checkout_page_button_height'] : '';
                    $this->checkout_page_button_label = !empty($this->setting['checkout_page_button_label']) ? $this->setting['checkout_page_button_label'] : 'checkout';
                    $this->checkout_page_disallowed_funding_methods = !empty($this->setting['checkout_page_disallowed_funding_methods']) ? $this->setting['checkout_page_disallowed_funding_methods'] : array();
                }
                // Woo Side Cart
                $this->wsc_cart_configure_settings_value = !empty($this->setting['wsc_cart_configure_settings']) ? $this->setting['wsc_cart_configure_settings'] : 'no';
                $this->wsc_cart_configure_settings = 'yes' === $this->wsc_cart_configure_settings_value;
                if ($this->wsc_cart_configure_settings) {
                    $this->wsc_cart_button_layout = !empty($this->setting['wsc_cart_button_layout']) ? $this->setting['wsc_cart_button_layout'] : 'horizontal';
                    $this->wsc_cart_button_size = !empty($this->setting['wsc_cart_button_size']) ? $this->setting['wsc_cart_button_size'] : 'small';
                    $this->wsc_cart_button_height = !empty($this->setting['wsc_cart_button_height']) ? $this->setting['wsc_cart_button_height'] : '';
                    $this->wsc_cart_button_label = !empty($this->setting['wsc_cart_button_label']) ? $this->setting['wsc_cart_button_label'] : 'checkout';
                    $this->wsc_cart_disallowed_funding_methods = !empty($this->setting['wsc_cart_disallowed_funding_methods']) ? $this->setting['wsc_cart_disallowed_funding_methods'] : array();
                }
                $this->wsc_cart_disable_smart_button = !empty($this->setting['wsc_cart_disable_smart_button']) ? $this->setting['wsc_cart_disable_smart_button'] : 'no';
                $this->billing_address = 'yes' === $this->billing_address_value;
                $this->cancel_page = !empty($this->setting['cancel_page']) ? $this->setting['cancel_page'] : '';
                $this->order_review_page_custom_message = !empty($this->setting['order_review_page_custom_message']) ? $this->setting['order_review_page_custom_message'] : '';
                $this->use_wp_locale_code = !empty($this->setting['use_wp_locale_code']) ? $this->setting['use_wp_locale_code'] : 'yes';
                $this->enable_in_context_checkout_flow = !empty($this->setting['enable_in_context_checkout_flow']) ? $this->setting['enable_in_context_checkout_flow'] : 'yes';
                if ($this->testmode == false) {
                    $this->testmode = AngellEYE_Utility::angelleye_paypal_for_woocommerce_is_set_sandbox_product();
                }
                $this->is_paypal_credit_enable = true;
                $this->show_paypal_credit = !empty($this->setting['show_paypal_credit']) ? $this->setting['show_paypal_credit'] : 'yes';
                $this->enable_google_analytics_click = !empty($this->setting['enable_google_analytics_click']) ? $this->setting['enable_google_analytics_click'] : 'no';

                $this->payment_action = !empty($this->setting['payment_action']) ? $this->setting['payment_action'] : 'Sale';

                if ($this->is_paypal_credit_enable == false) {
                    $this->show_paypal_credit = 'no';
                }
                if ($this->testmode == true) {
                    $this->API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
                    $this->PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
                    $this->api_username = !empty($this->setting['sandbox_api_username']) ? $this->setting['sandbox_api_username'] : '';
                    $this->api_password = !empty($this->setting['sandbox_api_password']) ? $this->setting['sandbox_api_password'] : '';
                    $this->api_signature = !empty($this->setting['sandbox_api_signature']) ? $this->setting['sandbox_api_signature'] : '';
                    $this->client_id = 'sb';
                } else {
                    $this->API_Endpoint = "https://api-3t.paypal.com/nvp";
                    $this->PAYPAL_URL = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
                    $this->api_username = !empty($this->setting['api_username']) ? $this->setting['api_username'] : '';
                    $this->api_password = !empty($this->setting['api_password']) ? $this->setting['api_password'] : '';
                    $this->api_signature = !empty($this->setting['api_signature']) ? $this->setting['api_signature'] : '';
                    $this->client_id = 'AUESd5dCP7FmcZnzB7v32UIo-gGgnJupvdfLle9TBJwOC4neACQhDVONBv3hc1W-pXlXS6G-KA5y4Kzv';
                }
                $this->angelleye_skip_text = !empty($this->setting['angelleye_skip_text']) ? $this->setting['angelleye_skip_text'] : __('Skip the forms and pay faster with PayPal!', 'paypal-for-woocommerce');
                if($this->angelleye_skip_text === 'Skip the forms and pay faster with PayPal!') {
                    $this->angelleye_skip_text = __('Skip the forms and pay faster with PayPal!', 'paypal-for-woocommerce');
                }
                $this->skip_final_review = !empty($this->setting['skip_final_review']) ? $this->setting['skip_final_review'] : 'no';
                $this->disable_term_value = !empty($this->setting['disable_term']) ? $this->setting['disable_term'] : 'no';
                $this->disable_term = 'yes' === $this->disable_term_value;
                $this->checkout_page_disable_smart_button_value = !empty($this->setting['checkout_page_disable_smart_button']) ? $this->setting['checkout_page_disable_smart_button'] : 'no';
                $this->checkout_page_disable_smart_button = 'yes' === $this->checkout_page_disable_smart_button_value;
                
                $this->enabled_credit_messaging_value = !empty($this->setting['enabled_credit_messaging']) ? $this->setting['enabled_credit_messaging'] : 'no';
                
                $this->credit_messaging_page_type = !empty($this->setting['credit_messaging_page_type']) ? $this->setting['credit_messaging_page_type'] : array('home', 'category', 'product', 'cart', 'payment');
                if (empty($this->credit_messaging_page_type)) {
                    $this->enabled_credit_messaging_value = 'no';
                }
                $this->enabled_credit_messaging = 'yes' === $this->enabled_credit_messaging_value;
                if ($this->enabled_credit_messaging) {
                    $this->credit_messaging_home_shortcode_value = isset($this->setting['credit_messaging_home_shortcode']) ? $this->setting['credit_messaging_home_shortcode'] : 'no';
                    $this->credit_messaging_home_shortcode = 'yes' === $this->credit_messaging_home_shortcode_value;
                    $this->credit_messaging_category_shortcode_value = isset($this->setting['credit_messaging_category_shortcode']) ? $this->setting['credit_messaging_category_shortcode'] : 'no';
                    $this->credit_messaging_category_shortcode = 'yes' === $this->credit_messaging_category_shortcode_value;
                    $this->credit_messaging_product_shortcode_value = isset($this->setting['credit_messaging_product_shortcode']) ? $this->setting['credit_messaging_product_shortcode'] : 'no';
                    $this->credit_messaging_product_shortcode = 'yes' === $this->credit_messaging_product_shortcode_value;
                    $this->credit_messaging_cart_shortcode_value = isset($this->setting['credit_messaging_cart_shortcode']) ? $this->setting['credit_messaging_cart_shortcode'] : 'no';
                    $this->credit_messaging_cart_shortcode = 'yes' === $this->credit_messaging_cart_shortcode_value;
                    $this->credit_messaging_payment_shortcode_value = isset($this->setting['credit_messaging_payment_shortcode']) ? $this->setting['credit_messaging_payment_shortcode'] : 'no';
                    $this->credit_messaging_payment_shortcode = 'yes' === $this->credit_messaging_payment_shortcode_value;
                }
                add_action('woocommerce_after_add_to_cart_button', array($this, 'buy_now_button'), 11);

                add_action('woocommerce_add_to_cart_redirect', array($this, 'add_to_cart_redirect'), 9999);
                add_action('woocommerce_checkout_billing', array($this, 'ec_set_checkout_post_data'));
                add_action('woocommerce_available_payment_gateways', array($this, 'ec_disable_gateways'));
                add_filter('body_class', array($this, 'ec_add_body_class'));
                add_action('woocommerce_checkout_fields', array($this, 'ec_display_checkout_fields'));
                add_action('woocommerce_before_checkout_billing_form', array($this, 'ec_formatted_billing_address'), 9);
                add_action('woocommerce_before_checkout_shipping_form', array($this, 'angelleye_shipping_sec_title'), 10);
                add_filter('woocommerce_terms_is_checked_default', array($this, 'ec_terms_express_checkout'));
                add_action('woocommerce_cart_emptied', array($this, 'ec_clear_session_data'));
                add_action('wp_enqueue_scripts', array($this, 'ec_enqueue_scripts'), 10);
                add_action('woocommerce_before_cart_table', array($this, 'top_cart_button'), 10);
                if ($this->enable_in_context_checkout_flow == 'no') {
                    if ($this->show_on_cart == 'yes' && $this->show_on_minicart == 'yes') {
                        add_action('woocommerce_after_mini_cart', array($this, 'mini_cart_button'), 20);
                    }
                } else {
                    if ($this->show_on_cart == 'yes' && $this->show_on_minicart == 'yes') {
                        add_action('woocommerce_widget_shopping_cart_buttons', array($this, 'mini_cart_button'), 20);
                    }
                }
                add_action('woocommerce_cart_contents', array($this, 'woocommerce_before_cart'), 12);
                add_filter('woocommerce_is_sold_individually', array($this, 'angelleye_woocommerce_is_sold_individually'), 10, 2);
                add_filter('woocommerce_ship_to_different_address_checked', array($this, 'angelleye_ship_to_different_address_checked'), 10, 1);
                add_filter('woocommerce_order_button_html', array($this, 'angelleye_woocommerce_order_button_html'), 10, 1);
                add_filter('woocommerce_coupons_enabled', array($this, 'angelleye_woocommerce_coupons_enabled'), 10, 1);
                add_action('woocommerce_cart_shipping_packages', array($this, 'maybe_add_shipping_information'));
                add_action('admin_notices', array($this, 'angelleye_billing_agreement_notice'));
                add_action('wc_ajax_wc_angelleye_ppec_update_shipping_costs', array($this, 'wc_ajax_update_shipping_costs'));
                add_filter('clean_url', array($this, 'angelleye_in_content_js'));
                add_action('wc_ajax_angelleye_ajax_generate_cart', array($this, 'angelleye_ajax_generate_cart'));
                if (AngellEYE_Utility::is_express_checkout_credentials_is_set()) {
                    if ($this->button_position == 'bottom' || $this->button_position == 'both') {
                        add_action('woocommerce_proceed_to_checkout', array($this, 'woocommerce_paypal_express_checkout_button_angelleye'), 22);
                    }
                }
                add_action('woocommerce_before_checkout_form', array($this, 'angelleye_display_custom_message_review_page'), 5);
                if ($this->enabled == 'yes' && ($this->show_on_checkout == 'top' || $this->show_on_checkout == 'both')) {
                    add_action('woocommerce_before_checkout_form', array($this, 'checkout_message'), 5);
                }
                if (!class_exists('WC_Gateway_PayPal_Express_Function_AngellEYE')) {
                    require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/express-checkout/class-wc-gateway-paypal-express-function-angelleye.php' );
                }
                $this->function_helper = new WC_Gateway_PayPal_Express_Function_AngellEYE();
                if ($this->function_helper->ec_is_express_checkout()) {
                    remove_all_actions('woocommerce_review_order_before_payment');
                }
                add_filter('the_title', array($this, 'angelleye_paypal_for_woocommerce_page_title'), 99, 1);
                add_action('template_redirect', array($this, 'angelleye_redirect_to_checkout_page'));
                add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'), 100);
                add_filter('body_class', array($this, 'add_body_classes'));
                if ($this->checkout_page_disable_smart_button == false && $this->enable_in_context_checkout_flow == 'yes') {
                    add_action('woocommerce_review_order_after_submit', array($this, 'angelleye_display_paypal_button_checkout_page'));
                }
                $this->is_order_completed = true;
                add_filter('woocommerce_locate_template', array($this, 'angelleye_woocommerce_locate_template'), 10, 3);
                if ($this->wsc_cart_disable_smart_button == 'no') {
                    add_action('xoo_wsc_after_footer_btns', array($this, 'angelleye_xoo_cu_wsc_paypal_express'), 10);
                }
                add_action('widget_title', array($this, 'angelleye_maybe_enqueue_checkout_js'), 10, 3);
                add_action('woocommerce_before_checkout_process', array($this, 'angelleye_woocommerce_before_checkout_process'), 10);
                add_action('angelleye_fraudnet_hook', array($this, 'own_angelleye_fraudnet_hook'), 99, 1);
                add_action('wp_enqueue_scripts', array($this, 'own_angelleye_fraudnet_script'), 99, 1);
                add_filter( 'sgo_js_minify_exclude', array($this, 'angelleye_exclude_javascript'), 999);
                add_filter( 'sgo_javascript_combine_exclude', array($this, 'angelleye_exclude_javascript'), 999);
                add_filter( 'sgo_javascript_combine_excluded_inline_content', array($this, 'angelleye_exclude_javascript'), 999);
                add_filter( 'sgo_js_async_exclude', array($this, 'angelleye_exclude_javascript'), 999);
                if ($this->enabled_credit_messaging) {
                    if ($this->is_paypal_credit_messaging_enable_for_page($page = 'home') && $this->credit_messaging_home_shortcode === false) {
                        add_filter('the_content', array($this, 'angelleye_display_credit_messaging_home_page_content'), 10);
                        add_action('woocommerce_before_shop_loop', array($this, 'angelleye_display_credit_messaging_home_page'), 10, 99);
                    }
                    if ($this->is_paypal_credit_messaging_enable_for_page($page = 'category') && $this->credit_messaging_category_shortcode === false) {
                        add_action('woocommerce_before_shop_loop', array($this, 'angelleye_display_credit_messaging_category_page'), 10, 99);
                    }
                    if ($this->is_paypal_credit_messaging_enable_for_page($page = 'product') && $this->credit_messaging_product_shortcode === false) {
                        add_action('woocommerce_single_product_summary', array($this, 'angelleye_display_credit_messaging_product_page'), 11);
                    }
                    if ($this->is_paypal_credit_messaging_enable_for_page($page = 'cart') && $this->credit_messaging_cart_shortcode === false) {
                        add_action('woocommerce_before_cart_table', array($this, 'angelleye_display_credit_messaging_cart_page'), 9);
                        add_filter('angelleye_bottom_cart_page', array($this, 'angelleye_display_credit_messaging_bottom_cart_page'), 10, 1);
                    }
                    if ($this->is_paypal_credit_messaging_enable_for_page($page = 'payment') && $this->credit_messaging_payment_shortcode === false) {
                        add_action('woocommerce_before_checkout_form', array($this, 'angelleye_display_credit_messaging_payment_page'), 4);
                        if ($this->checkout_page_disable_smart_button == false && $this->enable_in_context_checkout_flow == 'yes') {
                            add_action('woocommerce_review_order_after_submit', array($this, 'angelleye_display_credit_messaging_payment_page'), 9);
                        }
                    }
                    add_shortcode('aepfw_bnpl_message', array($this, 'aepfw_bnpl_message_shortcode'), 10);
                }
            }
        } catch (Exception $ex) {

        }
    }

    public function buy_now_button() {
        try {
            global $product;
            $is_ec_button_enable_product_level = get_post_meta($product->get_id(), '_enable_ec_button', true);
            if ($this->enabled == 'yes' && $this->show_on_product_page == 'yes' && $is_ec_button_enable_product_level == 'yes') {
                $ec_html_button = '';
                $_product = wc_get_product($product->get_id());
                $_allowed_product_type = apply_filters('angelleye_ec_product_type_allowed', array('variation', 'variable', 'simple'));
                if (in_array($_product->get_type(), $_allowed_product_type, true)) {
                    if ($_product->is_type('simple') && (version_compare(WC_VERSION, '3.0', '<') == false)) {
                        ?>
                        <input type="hidden" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" />
                        <?php
                    }
                    if ($_product->is_type('simple') && ($_product->get_price() == 0 || $_product->get_price() == '')) {
                        return false;
                    }
                    $button_dynamic_class = 'single_variation_wrap_angelleye_' . $product->get_id();
                    $hide = '';
                    $ec_html_button .= '<div class="angelleye_button_single single_add_to_cart_button" style="' . $hide . '">';
                    if ($this->enable_in_context_checkout_flow == 'no') {
                        $add_to_cart_action = esc_url(add_query_arg('express_checkout', '1'));
                        switch ($this->checkout_with_pp_button_type) {
                            case 'textbutton':
                                $ec_html_button .= '<input data-action="' . esc_url($add_to_cart_action) . '" type="button" style="' . $hide . '"  class="single_add_to_cart_button button alt paypal_checkout_button single_variation_wrap_angelleye paypal_checkout_button button alt ec_product_page_button_type_textbutton "' . $button_dynamic_class . '" name="express_checkout"  value="' . $this->pp_button_type_text_button . '"/>';
                                break;
                            case "paypalimage":
                                $button_img = WC_Gateway_PayPal_Express_AngellEYE::angelleye_get_paypalimage();
                                $ec_html_button .= '<input data-action="' . esc_url($add_to_cart_action) . '" type="image" src="' . $button_img . '" style="' . $hide . '"  class="single_add_to_cart_button button alt paypal_checkout_button single_variation_wrap_angelleye ec_product_page_button_type_paypalimage ' . $button_dynamic_class . '" name="express_checkout" value="' . __('Pay with PayPal', 'paypal-for-woocommerce') . '"/>';
                                break;
                            case "customimage":
                                $ec_html_button .= '<input data-action="' . esc_url($add_to_cart_action) . '" type="image" src="' . $this->pp_button_type_my_custom . '" style="' . $hide . '"  class="single_add_to_cart_button button alt paypal_checkout_button single_variation_wrap_angelleye ec_product_page_button_type_customimage ' . $button_dynamic_class . '" name="express_checkout" value="' . __('Pay with PayPal', 'paypal-for-woocommerce') . '"/>';
                                break;
                        }
                        if ($this->show_paypal_credit == 'yes') {
                            $paypal_credit_button_markup = '<a  style="' . $hide . '" class="single_add_to_cart_button paypal_checkout_button paypal_checkout_button_cc" href="' . esc_url(add_query_arg('use_paypal_credit', 'true', add_query_arg('pp_action', 'set_express_checkout', untrailingslashit(WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE'))))) . '" >';
                            $paypal_credit_button_markup .= '<img src="https://www.paypalobjects.com/webstatic/en_US/i/buttons/ppcredit-logo-small.png" width="148" height="26" class="ppcreditlogo ec_checkout_page_button_type_pc"  align="top" alt="' . __('Check out with PayPal Credit', 'paypal-for-woocommerce') . '" />';
                            $paypal_credit_button_markup .= '</a>';
                            $ec_html_button .= $paypal_credit_button_markup;
                        }
                    } else {
                        wp_enqueue_script('angelleye-in-context-checkout-js');
                        wp_enqueue_script('angelleye-in-context-checkout-js-frontend');
                        do_action('angelleye_fraudnet_hook', $this->setting);
                    }
                    $ec_html_button .= '</div>';
                    if ($this->enable_tokenized_payments == 'yes') {
                        $ec_html_button .= $this->function_helper->angelleye_ec_save_payment_method_checkbox();
                    }
                    echo apply_filters('angelleye_ec_product_page_buy_now_button', $ec_html_button);
                }
            }
        } catch (Exception $ex) {

        }
    }

    public function add_to_cart_redirect($url = null) {
        try {
            if (isset($_REQUEST['express_checkout']) || isset($_REQUEST['express_checkout_x'])) {
                wc_clear_notices();
                if ((isset($_POST['wc-paypal_express-new-payment-method']) && $_POST['wc-paypal_express-new-payment-method'] == 'true') || ( isset($_GET['ec_save_to_account']) && $_GET['ec_save_to_account'] == true)) {
                    angelleye_set_session('ec_save_to_account', 'on');
                } else {
                    unset(WC()->session->ec_save_to_account);
                }
                $url = esc_url_raw(add_query_arg('pp_action', 'set_express_checkout', untrailingslashit(WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE'))));
            }
            return $url;
        } catch (Exception $ex) {

        }
    }

    public function ec_get_session_data($key = '') {
        try {
            $session_data = angelleye_get_session('paypal_express_checkout');
            if (isset($session_data[$key])) {
                $session_data = $session_data[$key];
            }
            return $session_data;
        } catch (Exception $ex) {

        }
    }

    public function ec_is_available() {
        try {
            return $this->function_helper->express_checkout_is_available();
        } catch (Exception $ex) {

        }
    }

    public function ec_set_checkout_post_data() {
        try {
            if (!$this->function_helper->ec_is_express_checkout() || !$this->ec_get_session_data('shipping_details')) {
                return;
            }
            $post_data = angelleye_get_session('post_data');
            foreach ($this->ec_get_session_data('shipping_details') as $field => $value) {
                if (!empty($value)) {
                    if ('state' == $field) {
                        $shipping_details = $this->ec_get_session_data('shipping_details');
                        if ($this->angelleye_is_need_to_set_billing_address() == true) {
                            if ($this->validate_checkout($shipping_details['country'], $value, 'billing')) {
                                $_POST['billing_' . $field] = $this->validate_checkout($shipping_details['country'], $value, 'billing');
                            } else {
                                $_POST['billing_' . $field] = '';
                            }
                        }
                        if ($this->validate_checkout($shipping_details['country'], $value, 'shipping')) {
                            $_POST['shipping_' . $field] = $this->validate_checkout($shipping_details['country'], $value, 'shipping');
                        } else {
                            $_POST['shipping_' . $field] = '';
                        }
                    } else {
                        if ($this->angelleye_is_need_to_set_billing_address() == true) {
                            $_POST['billing_' . $field] = wc_clean(stripslashes($value));
                        } elseif (empty($post_data)) {
                            $_POST['billing_' . $field] = '';
                        }
                        $_POST['shipping_' . $field] = wc_clean(stripslashes($value));
                    }
                }
            }

            $_POST['order_comments'] = isset($post_data['order_comments']) ? wc_clean($post_data['order_comments']) : '';
            if (!empty($post_data)) {
                foreach ($post_data as $key => $value) {
                    if (!empty($value)) {
                        $_POST[$key] = is_string($value) ? wc_clean(stripslashes($value)) : $value;
                    }
                }
            }
            $this->chosen = true;
        } catch (Exception $ex) {

        }
    }

    public function ec_display_checkout_fields($checkout_fields) {
        try {
            if ($this->function_helper->ec_is_express_checkout() && $this->ec_get_session_data('shipping_details')) {
                foreach ($this->ec_get_session_data('shipping_details') as $field_key => $value) {
                    if (isset($checkout_fields['billing']) && isset($checkout_fields['billing']['billing_' . $field_key])) {
                        $required = isset($checkout_fields['billing']['billing_' . $field_key]['required']) && $checkout_fields['billing']['billing_' . $field_key]['required'];
                        if (!$required || $required && !empty($value)) {
                            $checkout_fields['billing']['billing_' . $field_key]['class'][] = 'express-provided';
                            $checkout_fields['billing']['billing_' . $field_key]['class'][] = 'hidden';
                        }
                    }
                }
            }
            return $checkout_fields;
        } catch (Exception $ex) {

        }
    }

    public function ec_formatted_address($type) {

        try {
            if (!$this->function_helper->ec_is_express_checkout()) {
                return;
            }
            if (!$this->is_order_completed) {
                return;
            }
            ?>
            <div class="express-provided-address">
                <a href="#" class="ex-show-address-fields" data-type="<?php echo esc_attr('billing'); ?>"><?php esc_html_e('Edit', 'paypal-for-woocommerce'); ?></a>
                <address>
                    <?php
                    $address = array(
                        'first_name' => WC()->checkout->get_value($type . '_first_name'),
                        'last_name' => WC()->checkout->get_value($type . '_last_name'),
                        'company' => WC()->checkout->get_value($type . '_company'),
                        'address_1' => WC()->checkout->get_value($type . '_address_1'),
                        'address_2' => WC()->checkout->get_value($type . '_address_2'),
                        'city' => WC()->checkout->get_value($type . '_city'),
                        'state' => WC()->checkout->get_value($type . '_state'),
                        'postcode' => WC()->checkout->get_value($type . '_postcode'),
                        'country' => WC()->checkout->get_value($type . '_country'),
                    );

                    $shipping_details = $this->ec_get_session_data('shipping_details');
                    $email = WC()->checkout->get_value($type . '_email');
                    if (empty($email)) {
                        $email = !empty($shipping_details['email']) ? $shipping_details['email'] : '';
                    }
                    $phone = WC()->checkout->get_value($type . '_phone');
                    if (empty($phone)) {
                        $phone = !empty($shipping_details['phone']) ? $shipping_details['phone'] : '';
                    }
                    $formatted_address = WC()->countries->get_formatted_address($address);
                    $formatted_address = str_replace('<br/>-<br/>', '<br/>', $formatted_address);
                    echo $formatted_address;
                    if (!empty($shipping_details)) {
                        echo!empty($email) ? '<p class="angelleye-woocommerce-customer-details-email">' . $email . '</p>' : '';
                        echo!empty($phone) ? '<p class="angelleye-woocommerce-customer-details-phone">' . $phone . '</p>' : '';
                    }
                    ?>
                </address>

            </div>

            <?php
        } catch (Exception $ex) {

        }
    }

    public function ec_disable_gateways($gateways) {
        $new_sorted_gateways = array();
        try {
            if ($this->function_helper->ec_is_express_checkout()) {
                foreach ($gateways as $id => $gateway) {
                    if ($id !== 'paypal_express') {
                        unset($gateways[$id]);
                    }
                }
            } else {
                if ($this->enable_in_context_checkout_flow == 'yes' && $this->checkout_page_disable_smart_button == false) {
                    foreach ($gateways as $id => $gateway) {
                        if ($id !== 'paypal_express') {
                            $new_sorted_gateways[$id] = $gateway;
                        }
                    }
                    foreach ($gateways as $id => $gateway) {
                        if ($id == 'paypal_express') {
                            $new_sorted_gateways[$id] = $gateway;
                        }
                    }
                    return $new_sorted_gateways;
                }
            }
            if (is_cart() || ( is_checkout() && !is_checkout_pay_page() )) {
                if (isset($gateways['paypal_express']) && (!isset(WC()->cart) || WC()->cart->needs_payment() == false )) {
                    unset($gateways['paypal_express']);
                }
            }
            return $gateways;
        } catch (Exception $ex) {

        }
    }

    public function ec_add_body_class($classes) {
        try {
            if (!class_exists('WooCommerce') || WC()->session == null) {
                return $classes;
            }
            $paypal_express_terms = angelleye_get_session('paypal_express_terms');
            if ($this->ec_is_checkout() && $this->function_helper->ec_is_express_checkout()) {
                $classes[] = 'express-checkout';
                if ($this->show_on_checkout && $paypal_express_terms === true) {
                    $classes[] = 'express-hide-terms';
                }
            }
            return $classes;
        } catch (Exception $ex) {

        }
    }

    public function ec_formatted_billing_address() {
        if ($this->function_helper->ec_is_express_checkout()) {
            $this->ec_formatted_address('billing');
        }
    }

    public function ec_terms_express_checkout($checked_default) {
        if (!class_exists('WooCommerce') || WC()->session == null) {
            return $checked_default;
        }
        if (!$this->ec_is_available() || !$this->function_helper->ec_is_express_checkout()) {
            return $checked_default;
        }
        $paypal_express_terms = angelleye_get_session('paypal_express_terms');
        if ($this->show_on_checkout && $paypal_express_terms === true) {
            $checked_default = true;
        }
        return $checked_default;
    }

    public function ec_clear_session_data() {
        unset(WC()->session->paypal_express_checkout);
        unset(WC()->session->paypal_express_terms);
        unset(WC()->session->ec_save_to_account);
        unset(WC()->session->post_data);
        unset(WC()->session->shiptoname);
        unset(WC()->session->payeremail);
        unset(WC()->session->validate_data);
        unset(WC()->session->angelleye_fraudnet_f);
    }

    public function ec_is_checkout() {
        if (function_exists('is_checkout')) {
            if (is_checkout()) {
                return true;
            }
        }
        return false;
    }

    public function is_paypal_sdk_required() {
        global $post;
        if ($this->function_helper->ec_is_express_checkout()) {
            return false;
        }
        if ($this->enabled_credit_messaging) {
            if (is_product_taxonomy() || is_product_category() || is_product_tag() || is_shop() || (is_home() || is_front_page() || is_product())) {
                return true;
            }
            if (is_page() || (!empty($post->post_content) && strstr($post->post_content, '[aepfw_bnpl_message') )) {
                return true;
            }
        }
        return false;
    }

    public function ec_enqueue_scripts($is_mini_cart = false) {
        global $post;
        if (is_admin()) {
            return;
        }
        try {
            if (is_order_received_page()) {
                return false;
            }
            if ($this->is_paypal_sdk_required() == false && $this->is_angelleye_product_page() == false && (!isset(WC()->cart) || WC()->cart->needs_payment() == false )) {
                if ($this->show_on_cart == 'no' && $this->show_on_minicart == 'no') {
                    if (apply_filters('is_paypal_sdk_required', false) === false) {
                        return false;
                    }
                }
            }
            $smart_cancel_page = '';
            if ($this->mini_cart_configure_settings == false) {
                $this->mini_cart_button_layout = $this->button_layout;
                $this->mini_cart_button_size = $this->button_size;
                $this->mini_cart_button_height = $this->button_height;
                $this->mini_cart_button_label = $this->button_label;
                $this->mini_cart_disallowed_funding_methods = $this->disallowed_funding_methods;
                $smart_cancel_page = wc_get_cart_url();
            }
            if ($this->wsc_cart_configure_settings == false) {
                $this->wsc_cart_button_layout = $this->button_layout;
                $this->wsc_cart_button_size = $this->button_size;
                $this->wsc_cart_button_height = $this->button_height;
                $this->wsc_cart_button_label = $this->button_label;
                $this->wsc_cart_disallowed_funding_methods = $this->disallowed_funding_methods;
            }
            if ($this->is_angelleye_product_page() && $this->single_product_configure_settings) {
                $this->button_layout = $this->single_product_button_layout;
                $this->button_size = $this->single_product_button_size;
                $this->button_height = $this->single_product_button_height;
                $this->button_label = $this->single_product_button_label;
                $this->disallowed_funding_methods = $this->single_product_disallowed_funding_methods;
            } elseif (is_cart() && $this->cart_configure_settings) {
                $this->button_layout = $this->cart_button_layout;
                $this->button_size = $this->cart_button_size;
                $this->button_height = $this->cart_button_height;
                $this->button_label = $this->cart_button_label;
                $this->disallowed_funding_methods = $this->cart_disallowed_funding_methods;
            } elseif (is_checkout() && $this->checkout_page_configure_settings) {
                $this->button_layout = $this->checkout_page_button_layout;
                $this->button_size = $this->checkout_page_button_size;
                $this->button_height = $this->checkout_page_button_height;
                $this->button_label = $this->checkout_page_button_label;
                $this->disallowed_funding_methods = $this->checkout_page_disallowed_funding_methods;
            }
            if ($this->button_layout == 'vertical') {
                $this->button_tagline = '';
            }
            if ($this->mini_cart_button_layout == 'vertical') {
                $this->button_tagline = '';
            }
            if ($this->wsc_cart_button_layout == 'vertical') {
                $this->button_tagline = '';
            }

            if ($this->testmode == false) {
                $this->testmode = AngellEYE_Utility::angelleye_paypal_for_woocommerce_is_set_sandbox_product();
            }
            $js_value = array('is_page_name' => '', 'enable_in_context_checkout_flow' => ( $this->enable_in_context_checkout_flow == 'yes' ? 'yes' : 'no'));
            if (isset($post->ID) && 'checkout' === get_post_meta($post->ID, 'wcf-step-type', true)) {
                $is_cartflow = "yes";
            } else {
                $is_cartflow = "no";
            }
            if (isset($post->ID) && 'yes' == get_post_meta($post->ID, 'wcf-pre-checkout-offer', true)) {
                $pre_checkout_offer = "yes";
            } else {
                $pre_checkout_offer = "no";
            }
            if ($this->angelleye_is_in_context_enable() == true || $this->is_paypal_sdk_required()) {
                $cancel_url = !empty($this->cancel_page) ? get_permalink($this->cancel_page) : wc_get_cart_url();
                $disallowed_funding_methods_json = json_encode($this->disallowed_funding_methods);
                $mini_cart_disallowed_funding_methods_json = json_encode($this->mini_cart_disallowed_funding_methods);
                $wsc_cart_disallowed_funding_methods_json = json_encode($this->wsc_cart_disallowed_funding_methods);
                $smart_js_arg = array();
                $smart_js_arg['client-id'] = $this->client_id;
                $smart_js_arg['currency'] = get_woocommerce_currency();
                if (($funding_key = array_search('elv', $this->disallowed_funding_methods)) !== false) {
                    unset($this->disallowed_funding_methods[$funding_key]);
                }
                if ($this->disallowed_funding_methods !== false && count($this->disallowed_funding_methods) > 0) {
                    $smart_js_arg['disable-funding'] = implode(',', $this->disallowed_funding_methods);
                }
                if ($this->testmode) {
                    $smart_js_arg['buyer-country'] = WC()->countries->get_base_country();
                } 
                $merchant_id_array = get_option('angelleye_express_checkout_default_pal');
                if (!empty($merchant_id_array) && !empty($merchant_id_array['PAL'])) {
                    $smart_js_arg['merchant-id'] = $merchant_id_array['PAL'];
                }
                $is_cart = is_cart() && !WC()->cart->is_empty();
                $is_product = is_product();
                $is_checkout = is_checkout();
                $page = $is_cart ? 'cart' : ( $is_product ? 'product' : ( $is_checkout ? 'checkout' : null ) );
                $smart_js_arg['commit'] = $this->angelleye_ec_force_to_display_checkout_page_js() == true ? 'false' : 'true';
                if ($this->enabled_credit_messaging) {
                    $smart_js_arg['components'] = 'buttons,messages';
                }
                $sdk_intend = 'capture';
                if ($this->payment_action === 'Sale') {
                    $sdk_intend = 'capture';
                } elseif ($this->payment_action === 'Authorization') {
                    $sdk_intend = 'authorize';
                } else {
                    $sdk_intend = 'order';
                }
                $smart_js_arg['intent'] = $sdk_intend;
                $smart_js_arg['locale'] = AngellEYE_Utility::get_button_locale_code();
                wp_register_script('angelleye-in-context-checkout-js', add_query_arg($smart_js_arg, 'https://www.paypal.com/sdk/js'), array(), null, true);
                wp_register_script('angelleye-in-context-checkout-js-frontend', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/js/angelleye-in-context-checkout.min-v2.js', array('jquery', 'angelleye-in-context-checkout-js'), time(), true);
                wp_localize_script('angelleye-in-context-checkout-js-frontend', 'angelleye_in_content_param', array(
                    'environment' => ( $this->testmode == true) ? 'sandbox' : 'production',
                    'locale' => ($this->use_wp_locale_code === 'yes' && AngellEYE_Utility::get_button_locale_code() != '') ? AngellEYE_Utility::get_button_locale_code() : '',
                    'start_flow' => esc_url(add_query_arg(array('startcheckout' => 'true'), wc_get_cart_url())),
                    'show_modal' => apply_filters('woocommerce_paypal_express_checkout_show_cart_modal', true),
                    'update_shipping_costs_nonce' => wp_create_nonce('_wc_angelleye_ppec_update_shipping_costs_nonce'),
                    'ajaxurl' => WC_AJAX::get_endpoint('wc_angelleye_ppec_update_shipping_costs'),
                    'generate_cart_nonce' => wp_create_nonce('_angelleye_generate_cart_nonce'),
                    'add_to_cart_ajaxurl' => WC_AJAX::get_endpoint('angelleye_ajax_generate_cart'),
                    'is_product' => $this->is_angelleye_product_page() ? "yes" : "no",
                    'is_cart' => is_cart() ? "yes" : "no",
                    'is_checkout' => is_checkout() ? "yes" : "no",
                    'cart_button_possition' => $this->button_position,
                    'is_display_on_checkout' => ($this->show_on_checkout == 'top' || $this->show_on_checkout == 'both' ) ? 'yes' : 'no',
                    'button_height' => $this->button_height,
                    'mini_cart_button_height' => $this->mini_cart_button_height,
                    'mini_cart_button_label' => $this->mini_cart_button_label,
                    'button_color' => $this->button_color,
                    'button_shape' => $this->button_shape,
                    'button_label' => $this->button_label,
                    'button_tagline' => $this->button_tagline,
                    'button_layout' => $this->button_layout,
                    'mini_cart_button_layout' => $this->mini_cart_button_layout,
                    'wsc_cart_button_layout' => $this->wsc_cart_button_layout,
                    'wsc_cart_button_height' => $this->wsc_cart_button_height,
                    'wsc_cart_button_label' => $this->wsc_cart_button_label,
                    'cancel_page' => add_query_arg('pp_action', 'cancel_order', untrailingslashit(WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE'))),
                    'get_express_checkout_details' => add_query_arg(array('pp_action' => 'get_express_checkout_details', 'utm_nooverride' => 1, 'request_from' => 'JSv4'), untrailingslashit(WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE'))),
                    'is_paypal_credit_enable' => $this->is_paypal_credit_enable ? "yes" : 'no',
                    'disallowed_funding_methods' => $disallowed_funding_methods_json,
                    'mini_cart_disallowed_funding_methods' => $mini_cart_disallowed_funding_methods_json,
                    'wsc_cart_disallowed_funding_methods' => $wsc_cart_disallowed_funding_methods_json,
                    'enable_google_analytics_click' => $this->enable_google_analytics_click,
                    'set_express_checkout' => add_query_arg('pp_action', 'set_express_checkout', untrailingslashit(WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE'))),
                    'zcommit' => $this->angelleye_ec_force_to_display_checkout_page_js() == true ? 'false' : 'true',
                    'checkout_page_disable_smart_button' => $this->checkout_page_disable_smart_button ? "yes" : "no",
                    'is_cartflow' => $is_cartflow,
                    'is_pre_checkout_offer' => $pre_checkout_offer,
                    'button_size' => $this->button_size,
                    'mini_cart_button_size' => $this->mini_cart_button_size,
                    'wsc_cart_button_size' => $this->wsc_cart_button_size,
                        )
                );
            }
            if ($this->enable_in_context_checkout_flow === 'yes' && $this->enabled == 'yes') {
                if (is_checkout() || is_cart() || $this->is_angelleye_product_page()) {
                    wp_enqueue_script('angelleye-in-context-checkout-js');
                    wp_enqueue_script('angelleye-in-context-checkout-js-frontend');
                    do_action('angelleye_fraudnet_hook', $this->setting);
                }
                if ($this->show_on_cart == 'yes' && $this->show_on_minicart == 'yes') {
                    wp_enqueue_script('angelleye-in-context-checkout-js');
                    wp_enqueue_script('angelleye-in-context-checkout-js-frontend');
                    do_action('angelleye_fraudnet_hook', $this->setting);
                }
            }
            if (is_checkout()) {
                $js_value['is_page_name'] = 'checkout_page';
                wp_enqueue_script('angelleye-express-checkout-js', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/js/angelleye-express-checkout.js', array(), $this->version, true);
                wp_localize_script('angelleye-express-checkout-js', 'angelleye_js_value', $js_value);
            }
            
            wp_enqueue_style('angelleye-express-checkout-css', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/css/angelleye-express-checkout.css', array(), $this->version, 'all');
        } catch (Exception $ex) {

        }
    }

    public function top_cart_button() {
        if (AngellEYE_Utility::is_express_checkout_credentials_is_set()) {
            $top_cart_button_html = '';
            if ($this->button_position == 'top' || $this->button_position == 'both') {
                do_action('angelleye_ec_before_top_cart_button', $this);
                $top_cart_button_html .= '<div class="wc-proceed-to-checkout angelleye_cart_button">';
                $top_cart_button_html .= $this->woocommerce_paypal_express_checkout_button_angelleye($return = true, 'top');
                $top_cart_button_html .= '</div>';
                echo apply_filters('angelleye_ec_top_cart_button', $top_cart_button_html);
                do_action('angelleye_ec_after_top_cart_button', $this);
            }
        }
    }

    public function mini_cart_button() {
        if (AngellEYE_Utility::is_express_checkout_credentials_is_set()) {
            $this->woocommerce_before_cart();
            wp_enqueue_script('angelleye-in-context-checkout-js');
            wp_enqueue_script('angelleye-in-context-checkout-js-frontend');
            do_action('angelleye_fraudnet_hook', $this->setting);
            $mini_cart_button_html = '';
            $mini_cart_button_html .= $this->woocommerce_paypal_express_checkout_button_angelleye($return = true, 'mini');
            $mini_cart_button_html .= "<div class='clear'></div>";
            echo apply_filters('angelleye_ec_mini_cart_button_html', $mini_cart_button_html);
        }
    }

    public function woocommerce_paypal_express_checkout_button_angelleye($return = false, $possition = null) {
        if (!defined('WOOCOMMERCE_CART')) {
            define('WOOCOMMERCE_CART', true);
        }
        if (!AngellEYE_Utility::is_valid_for_use_paypal_express()) {
            return false;
        }
        if ($this->enabled == 'yes' && $this->show_on_cart == 'yes' && isset(WC()->cart) && WC()->cart->needs_payment()) {
            $cart_button_html = '';
            if ($return == false) {
                do_action('angelleye_ec_before_buttom_cart_button', $this);
            }
            if ($possition == 'top') {
                $class_top = 'paypal_checkout_button_top';
                $class_cc_top = 'paypal_checkout_button_cc_top';
                $angelleye_smart_button = 'angelleye_smart_button_top';
            } elseif ($possition == 'mini') {
                $class_top = 'paypal_checkout_button_top';
                $class_cc_top = 'paypal_checkout_button_cc_top';
                $angelleye_smart_button = 'angelleye_smart_button_mini';
            } elseif ($possition == 'wsc') {
                $class_top = 'paypal_checkout_button_top';
                $class_cc_top = 'paypal_checkout_button_cc_top';
                $angelleye_smart_button = 'angelleye_smart_button_wsc';
            } else {
                $class_top = 'paypal_checkout_button_bottom';
                $class_cc_top = 'paypal_checkout_button_cc_bottom';
                $angelleye_smart_button = 'angelleye_smart_button_bottom';
                $angelleye_proceed_to_checkout_button_separator = '<div class="angelleye-proceed-to-checkout-button-separator">&mdash; ' . __('OR', 'paypal-for-woocommerce') . ' &mdash;</div>';
                $cart_button_html .= apply_filters('angelleye_proceed_to_checkout_button_separator', $angelleye_proceed_to_checkout_button_separator);
                $cart_button_html = apply_filters('angelleye_bottom_cart_page', $cart_button_html);
            }
            if ($this->enable_in_context_checkout_flow == 'no') {
                switch ($this->checkout_with_pp_button_type) {
                    case 'textbutton':
                        $cart_button_html .= '<a class="paypal_checkout_button button ' . $class_top . ' alt ec_checkout_page_button_type_textbutton" href="' . esc_url(add_query_arg('pp_action', 'set_express_checkout', untrailingslashit(WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE')))) . '">' . $this->pp_button_type_text_button . '</a>';
                        break;
                    case 'paypalimage':
                        $cart_button_html .= '<a class="paypal_checkout_button ' . $class_top . '" href="' . esc_url(add_query_arg('pp_action', 'set_express_checkout', untrailingslashit(WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE')))) . '">';
                        $cart_button_html .= '<img src=' . WC_Gateway_PayPal_Express_AngellEYE::angelleye_get_paypalimage() . ' class="ec_checkout_page_button_type_paypalimage"  align="top" alt="' . __('Pay with PayPal', 'paypal-for-woocommerce') . '" />';
                        $cart_button_html .= "</a>";
                        break;
                    case 'customimage':
                        $cart_button_html .= '<a class="paypal_checkout_button ' . $class_top . '" href="' . esc_url(add_query_arg('pp_action', 'set_express_checkout', untrailingslashit(WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE')))) . '">';
                        $cart_button_html .= '<img src="' . $this->pp_button_type_my_custom . '" class="ec_checkout_page_button_type_customimage" align="top" alt="' . __('Pay with PayPal', 'paypal-for-woocommerce') . '" />';
                        $cart_button_html .= "</a>";
                        break;
                }
                if ($this->show_paypal_credit == 'yes') {
                    $paypal_credit_button_markup = '<a class="paypal_checkout_button ' . $class_cc_top . '" href="' . esc_url(add_query_arg('use_paypal_credit', 'true', add_query_arg('pp_action', 'set_express_checkout', untrailingslashit(WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE'))))) . '" >';
                    $paypal_credit_button_markup .= '<img src="https://www.paypalobjects.com/webstatic/en_US/i/buttons/ppcredit-logo-small.png" width="148" height="26" class="ppcreditlogo ec_checkout_page_button_type_pc"  align="top" alt="' . __('Check out with PayPal Credit', 'paypal-for-woocommerce') . '" />';
                    $paypal_credit_button_markup .= '</a>';
                    $cart_button_html .= $paypal_credit_button_markup;
                }
            } else {
                wp_enqueue_script('angelleye-in-context-checkout-js');
                wp_enqueue_script('angelleye-in-context-checkout-js-frontend');
                do_action('angelleye_fraudnet_hook', $this->setting);
                $cart_button_html .= "<div class='$angelleye_smart_button'></div>";
            }
            if ($this->enable_tokenized_payments == 'yes') {
                if ($class_top == 'paypal_checkout_button_bottom') {
                    $cart_button_html .= $this->function_helper->angelleye_ec_save_payment_method_checkbox(true);
                } else {
                    $cart_button_html .= $this->function_helper->angelleye_ec_save_payment_method_checkbox();
                }
            }
            if ($return == true) {
                return $cart_button_html;
            } else {
                echo $cart_button_html;
            }
            do_action('angelleye_ec_after_buttom_cart_button', $this);
        }
    }

    public function checkout_message() {
        if (!defined('WOOCOMMERCE_CART')) {
            define('WOOCOMMERCE_CART', true);
        }
        if (AngellEYE_Utility::is_express_checkout_credentials_is_set() == false) {
            return false;
        }
        if (!AngellEYE_Utility::is_valid_for_use_paypal_express()) {
            return false;
        }
        if (isset(WC()->cart) && WC()->cart->needs_payment()) {
            $ec_top_checkout_button = '';
            wp_enqueue_script('angelleye_button');
            $ec_top_checkout_button .= '<div id="checkout_paypal_message" class="woocommerce-info info">';
            do_action('angelleye_ec_checkout_page_before_checkout_button', $this);
            $ec_top_checkout_button .= '<div id="paypal_box_button">';
            if ($this->enable_in_context_checkout_flow == 'no') {
                switch ($this->checkout_with_pp_button_type) {
                    case "textbutton":
                        $ec_top_checkout_button .= '<div class="paypal_ec_textbutton">';
                        $ec_top_checkout_button .= '<a  class="paypal_checkout_button paypal_checkout_button_text button alt" href="' . esc_url(add_query_arg('pp_action', 'set_express_checkout', untrailingslashit(WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE')))) . '">' . $this->pp_button_type_text_button . '</a>';
                        $ec_top_checkout_button .= '</div>';
                        break;
                    case "paypalimage":
                        $ec_top_checkout_button .= '<div id="paypal_ec_button">';
                        $ec_top_checkout_button .= '<a  class="paypal_checkout_button" href="' . esc_url(add_query_arg('pp_action', 'set_express_checkout', untrailingslashit(WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE')))) . '">';
                        $ec_top_checkout_button .= "<img src='" . WC_Gateway_PayPal_Express_AngellEYE::angelleye_get_paypalimage() . "' class='ec_checkout_page_button_type_paypalimage'  border='0' alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
                        $ec_top_checkout_button .= "</a>";
                        $ec_top_checkout_button .= '</div>';
                        break;
                    case "customimage":
                        $button_img = $this->pp_button_type_my_custom;
                        $ec_top_checkout_button .= '<div id="paypal_ec_button">';
                        $ec_top_checkout_button .= '<a  class="paypal_checkout_button" href="' . esc_url(add_query_arg('pp_action', 'set_express_checkout', untrailingslashit(WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE')))) . '">';
                        $ec_top_checkout_button .= "<img src='{$button_img}' class='ec_checkout_page_button_type_paypalimage' width='150' border='0' alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
                        $ec_top_checkout_button .= "</a>";
                        $ec_top_checkout_button .= '</div>';
                        break;
                }
                if ($this->show_paypal_credit == 'yes') {
                    $paypal_credit_button_markup = '<div id="paypal_ec_paypal_credit_button">';
                    $paypal_credit_button_markup .= '<a  class="paypal_checkout_button paypal_checkout_button_cc" href="' . esc_url(add_query_arg('use_paypal_credit', 'true', add_query_arg('pp_action', 'set_express_checkout', untrailingslashit(WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE'))))) . '" >';
                    $paypal_credit_button_markup .= "<img src='https://www.paypalobjects.com/webstatic/en_US/i/buttons/ppcredit-logo-small.png' class='ec_checkout_page_button_type_paypalimage' alt='Check out with PayPal Credit'/>";
                    $paypal_credit_button_markup .= '</a>';
                    $paypal_credit_button_markup .= '</div>';
                    $ec_top_checkout_button .= $paypal_credit_button_markup;
                }
            }
            if ($this->enable_in_context_checkout_flow == 'yes') {
                wp_enqueue_script('angelleye-in-context-checkout-js');
                wp_enqueue_script('angelleye-in-context-checkout-js-frontend');
                do_action('angelleye_fraudnet_hook', $this->setting);
                $ec_top_checkout_button .= "<div class='angelleye_smart_button_checkout_top'></div>";
            }
            if ($this->enable_tokenized_payments == 'yes') {
                $ec_top_checkout_button .= $this->function_helper->angelleye_ec_save_payment_method_checkbox();
            }
            $ec_top_checkout_button .= '<div class="woocommerce_paypal_ec_checkout_message">';
            $ec_top_checkout_button .= '<p class="checkoutStatus">' . $this->angelleye_skip_text . '</p>';
            $ec_top_checkout_button .= '</div>';
            $ec_top_checkout_button .= '<div class="clear"></div></div>';
            $ec_top_checkout_button .= '</div>';
            $ec_top_checkout_button .= '<div style="clear:both; margin-bottom:10px;"></div>';
            echo apply_filters('angelleye_ec_checkout_page_buy_now_nutton', $ec_top_checkout_button);
            do_action('angelleye_ec_checkout_page_after_checkout_button', $this);
        }
    }

    public function angelleye_woocommerce_is_sold_individually($return, $data) {
        if (isset($_REQUEST['express_checkout']) || isset($_REQUEST['express_checkout_x'])) {
            if ($this->prevent_to_add_additional_item) {
                return true;
            }
        }
        return $return;
    }

    public function angelleye_ship_to_different_address_checked($bool) {
        if (!class_exists('WooCommerce') || WC()->session == null) {
            return $bool;
        }
        $post_data = angelleye_get_session('post_data');
        if (!empty($post_data['ship_to_different_address']) && $post_data['ship_to_different_address'] == '1') {
            return 1;
        }
        if ($this->function_helper->ec_is_express_checkout()) {
            return 1;
        }
        return $bool;
    }

    public function woocommerce_before_cart() {
        if (!defined('WOOCOMMERCE_CART')) {
            define('WOOCOMMERCE_CART', true);
        }
        $payment_gateways_count = 0;
        echo "<style>table.cart td.actions .input-text, table.cart td.actions .button, table.cart td.actions .checkout-button {margin-bottom: 0.53em !important;}</style>";
        if ($this->enabled == 'yes' && isset(WC()->cart) && WC()->cart->needs_payment()) {
            $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
            unset($payment_gateways['paypal_pro']);
            unset($payment_gateways['paypal_pro_payflow']);
            $payment_gateway_count = count($payment_gateways);
            if ($this->show_on_checkout != 'regular' && $this->show_on_checkout != 'both') {
                $payment_gateway_count = $payment_gateway_count + 1;
            }
            if ($this->enabled == 'yes' && $payment_gateway_count == 1) {
                if ($this->paypal_pro_enabled == 'yes' || $this->paypal_flow_enabled == 'yes') {

                } elseif ($this->show_on_cart == 'yes') {
                    echo '<style> input.checkout-button,
                                 a.checkout-button, .button.checkout.wc-forward, a.checkout-button.wc-forward {
                                    display: none !important;
                                }
                                .angelleye-proceed-to-checkout-button-separator {
                                    display: none !important;
                                }
                                </style>';
                }
            }
        }
    }

    public function angelleye_woocommerce_order_button_html($order_button_hrml) {
        if ($this->function_helper->ec_is_express_checkout()) {
            $order_button_text = __('Cancel order', 'paypal-for-woocommerce');
            $cancel_order_url = add_query_arg('pp_action', 'cancel_order', untrailingslashit(WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE')));
            $order_button_hrml = apply_filters('angelleye_review_order_cance_button_html', '<a class="button alt angelleye_cancel" name="woocommerce_checkout_place_order" href="' . esc_attr($cancel_order_url) . '" >' . $order_button_text . '</a>' . $order_button_hrml);
        }
        return $order_button_hrml;
    }

    public function angelleye_woocommerce_coupons_enabled($is_coupons_enabled) {
        if ($this->function_helper->ec_is_express_checkout()) {
            return $is_coupons_enabled = false;
        } else {
            return $is_coupons_enabled;
        }
    }

    public function maybe_add_shipping_information($packages) {
        if (!is_ajax()) {
            if ($this->function_helper->ec_is_express_checkout() || $this->ec_get_session_data('shipping_details')) {
                $destination = $this->ec_get_session_data('shipping_details');
                if (!empty($destination['country'])) {
                    $packages[0]['destination']['country'] = isset($destination['country']) ? $destination['country'] : '';
                    $packages[0]['destination']['state'] = isset($destination['state']) ? $destination['state'] : '';
                    $packages[0]['destination']['postcode'] = isset($destination['postcode']) ? $destination['postcode'] : '';
                    $packages[0]['destination']['city'] = isset($destination['city']) ? $destination['city'] : '';
                    $packages[0]['destination']['address'] = isset($destination['address_1']) ? $destination['address_1'] : '';
                    $packages[0]['destination']['address_2'] = isset($destination['address_2']) ? $destination['address_2'] : '';
                }
            }
        }
        return $packages;
    }

    public function angelleye_billing_agreement_notice() {
        if (AngellEYE_Utility::is_display_angelleye_billing_agreement_notice($this) == true) {
            echo '<div class="error angelleye-notice" style="display:none;"><div class="angelleye-notice-logo"><span></span></div><div class="angelleye-notice-message">' . sprintf(__("PayPal Express Checkout Billing Agreements / Reference Transactions require specific approval by PayPal. Please contact PayPal to enable this feature before using it on your site. ", 'paypal-for-woocommerce')) . '</div><div class="angelleye-notice-cta"><button class="angelleye-notice-dismiss angelleye-dismiss-welcome" data-msg="ignore_billing_agreement_notice">Dismiss</button></div></div>';
        }
    }

    public function wc_ajax_update_shipping_costs() {
        if (!wp_verify_nonce($_POST['nonce'], '_wc_angelleye_ppec_update_shipping_costs_nonce')) {
            wp_die(__('Cheatin&#8217; huh?', 'paypal-for-woocommerce'));
        }
        if (!defined('WOOCOMMERCE_CART')) {
            define('WOOCOMMERCE_CART', true);
        }
        WC()->shipping->reset_shipping();
        WC()->cart->calculate_totals();
        if ((isset($_POST['wc-paypal_express-new-payment-method']) && $_POST['wc-paypal_express-new-payment-method'] == 'true') || ( isset($_GET['ec_save_to_account']) && $_GET['ec_save_to_account'] == true)) {
            angelleye_set_session('ec_save_to_account', 'on');
        } else {
            unset(WC()->session->ec_save_to_account);
        }
        wp_send_json(new stdClass());
    }

    public function angelleye_ajax_generate_cart() {
        global $wpdb, $post, $product;
        $product_id = '';
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], '_angelleye_generate_cart_nonce')) {
            wp_die(__('Cheatin&#8217; huh?', 'paypal-for-woocommerce'));
        }
        WC()->shipping->reset_shipping();
        $product_id = absint(wp_unslash($_POST['product_id']));
        $url = esc_url_raw(add_query_arg('pp_action', 'set_express_checkout', untrailingslashit(WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE'))));
        if ((isset($_POST['wc-paypal_express-new-payment-method']) && $_POST['wc-paypal_express-new-payment-method'] == 'true') || ( isset($_GET['ec_save_to_account']) && $_GET['ec_save_to_account'] == true)) {
            $url = add_query_arg('ec_save_to_account', 'true', $url);
        }
        if ((isset($_POST['wc-paypal_express-new-payment-method']) && $_POST['wc-paypal_express-new-payment-method'] == 'true') || ( isset($_GET['ec_save_to_account']) && $_GET['ec_save_to_account'] == true)) {
            angelleye_set_session('ec_save_to_account', 'on');
            $url = add_query_arg('ec_save_to_account', 'true', $url);
        } else {
            unset(WC()->session->ec_save_to_account);
        }
        if (!empty($_POST['is_cc']) && $_POST['is_cc'] == 'true') {
            $url = add_query_arg('use_paypal_credit', 'true', $url);
        }
        try {
            $product = wc_get_product($product_id);
            if (is_object($product)) {
                if (!defined('WOOCOMMERCE_CART')) {
                    define('WOOCOMMERCE_CART', true);
                }
                $qty = !isset($_POST['qty']) ? 1 : absint($_POST['qty']);
                if ($product->is_type('variable')) {
                    $attributes = array_map('wc_clean', json_decode(stripslashes($_POST['attributes']), true));
                    if (!empty($_POST['variation_id'])) {
                        $variation_id = absint(wp_unslash($_POST['variation_id']));
                    } else {
                        if (version_compare(WC_VERSION, '3.0', '<')) {
                            $variation_id = $product->get_matching_variation($attributes);
                        } else {
                            $data_store = WC_Data_Store::load('product');
                            $variation_id = $data_store->find_matching_product_variation($product, $attributes);
                        }
                    }
                    $bool = $this->angelleye_is_product_already_in_cart($product->get_id(), $qty, $variation_id, $attributes);
                    if ($bool == false) {
                        WC()->cart->add_to_cart($product->get_id(), $qty, $variation_id, $attributes);
                    }
                } elseif ($product->is_type('simple')) {
                    $bool = $this->angelleye_is_product_already_in_cart($product->get_id(), $qty);
                    if ($bool == false) {
                        WC()->cart->add_to_cart($product->get_id(), $qty);
                    }
                }
                WC()->cart->calculate_totals();
            }
            if (ob_get_length()) {
                ob_end_clean();
            }
            ob_start();
            wp_send_json(array('url' => $url));
        } catch (Exception $ex) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            ob_start();
            wp_send_json(array('url' => $url));
        }
    }

    public function validate_checkout($country, $state, $sec) {
        $state_value = '';
        $valid_states = WC()->countries->get_states(isset($country) ? $country : ( 'billing' === $sec ? WC()->customer->get_country() : WC()->customer->get_shipping_country() ));
        if (!empty($valid_states) && is_array($valid_states)) {
            $valid_state_values = array_flip(array_map('strtolower', $valid_states));
            if (isset($valid_state_values[strtolower($state)])) {
                $state_value = $valid_state_values[strtolower($state)];
                return $state_value;
            }
        } else {
            return $state;
        }
        if (!empty($valid_states) && is_array($valid_states) && sizeof($valid_states) > 0) {
            if (!in_array($state, array_keys($valid_states))) {
                return false;
            } else {
                return $state;
            }
        }

        return $state_value;
    }

    public function angelleye_paypal_for_woocommerce_page_title($page_title) {
        if (!class_exists('WooCommerce') || WC()->session == null) {
            return $page_title;
        }
        $paypal_express_checkout = angelleye_get_session('paypal_express_checkout');
        if (!is_admin() && is_main_query() && in_the_loop() && is_page() && is_checkout() && !empty($paypal_express_checkout)) {
            remove_filter('the_title', array($this, 'angelleye_paypal_for_woocommerce_page_title'));
            return $this->review_title_page;
        } else {
            return $page_title;
        }
    }

    public function angelleye_redirect_to_checkout_page() {
        if (!is_cart()) {
            return;
        }
        if ($this->function_helper->ec_is_express_checkout() && is_cart()) {
            wp_redirect(wc_get_checkout_url());
            exit;
        }
    }

    public function angelleye_is_need_to_set_billing_address() {
        if ('billing_only' === get_option('woocommerce_ship_to_destination') || $this->billing_address) {
            return true;
        } else {
            return false;
        }
    }

    public function angelleye_shipping_sec_title() {
        if ($this->function_helper->ec_is_express_checkout()) {
            ?><h3><?php _e('Shipping details', 'paypal-for-woocommerce'); ?></h3> <?php
        }
    }

    public function angelleye_is_in_context_enable() {
        global $post;
        if ($this->enable_in_context_checkout_flow === 'yes' && $this->enabled == 'yes') {
            if ($this->function_helper->ec_is_express_checkout()) {
                return false;
            }
            if ($this->is_angelleye_product_page()) {
                $post_id = get_the_ID();
                $is_ec_button_enable_product_level = get_post_meta($post_id, '_enable_ec_button', true);
                if ($this->enabled == 'yes' && $this->show_on_product_page == 'yes' && $is_ec_button_enable_product_level == 'yes') {
                    return true;
                }
            }
            if (is_checkout()) {
                if ($this->show_on_checkout == 'top' || $this->show_on_checkout == 'both' || ( $this->show_on_checkout == 'regular' && $this->checkout_page_disable_smart_button == false)) {
                    return true;
                }
            }
            if (is_cart()) {
                if ($this->show_on_cart == 'yes') {
                    return true;
                }
            }
            if ($this->show_on_cart == 'yes' && $this->show_on_minicart == 'yes') {
                return true;
            }
            if (!empty($post->post_content) && strstr($post->post_content, '[product_page')) {
                return true;
            }
            if ($this->is_paypal_sdk_required()) {
                return true;
            }
        } else {
            return false;
        }
        return false;
    }

    /**
     * frontend_scripts function.
     *
     * @access public
     * @return void
     */
    public function frontend_scripts() {
        global $post;
        $_enable_ec_button = 'no';
        $this->setting['enabled'] = !empty($this->setting['enabled']) ? $this->setting['enabled'] : '';
        $this->setting['show_on_product_page'] = !empty($this->setting['show_on_product_page']) ? $this->setting['show_on_product_page'] : '';
        $enable_in_context_checkout_flow = !empty($this->setting['enable_in_context_checkout_flow']) ? $this->setting['enable_in_context_checkout_flow'] : 'yes';
        wp_register_script('angelleye_frontend', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/js/angelleye-frontend.js', array('jquery'), $this->version, true);
        $translation_array = array(
            'is_product' => $this->is_angelleye_product_page() ? "yes" : "no",
            'is_cart' => is_cart() ? "yes" : "no",
            'is_checkout' => is_checkout() ? "yes" : "no",
            'three_digits' => __('3 digits usually found on the signature strip.', 'paypal-for-woocommerce'),
            'four_digits' => __('4 digits usually found on the front of the card.', 'paypal-for-woocommerce'),
            'enable_in_context_checkout_flow' => $enable_in_context_checkout_flow
        );
        if ($enable_in_context_checkout_flow == 'no') {
            wp_localize_script('angelleye_frontend', 'angelleye_frontend', $translation_array);
            wp_enqueue_script('angelleye_frontend');
        }
        if (!is_admin() && is_cart()) {
            wp_enqueue_style('ppe_cart', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/css/cart.css', array(), $this->version);
        }
        if (!is_admin() && is_checkout()) {
            wp_enqueue_style('ppe_checkout', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/css/checkout.css', array(), $this->version);
        }
        if (!is_admin() && $this->is_angelleye_product_page() && $this->setting['enabled'] == 'yes' && $this->setting['show_on_product_page'] == 'yes') {
            if (!empty($post)) {
                $_enable_ec_button = get_post_meta($post->ID, '_enable_ec_button', true);
            }
            if (!empty($post->post_content) && strstr($post->post_content, '[product_page')) {
                $_enable_ec_button = 'yes';
            }
            if ($_enable_ec_button == 'yes') {
                wp_enqueue_style('ppe_single', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/css/single.css', array(), $this->version);
                wp_enqueue_script('angelleye_button');
            }
        }
    }

    /*
     * Adds class name to HTML body to enable easy conditional CSS styling
     * @access public
     * @param array $classes
     * @return array
     */

    public function add_body_classes($classes) {
        if (!empty($this->setting['enabled']) && $this->setting['enabled'] == 'yes')
            $classes[] = 'has_paypal_express_checkout';
        return $classes;
    }

    public function angelleye_is_product_already_in_cart($product_id = 0, $quantity = 1, $variation_id = 0, $variation = array(), $cart_item_data = array()) {
        $product_id = absint($product_id);
        $variation_id = absint($variation_id);
        if ('product_variation' === get_post_type($product_id)) {
            $variation_id = $product_id;
            $product_id = wp_get_post_parent_id($variation_id);
        }
        $product_data = wc_get_product($variation_id ? $variation_id : $product_id);
        $quantity = apply_filters('woocommerce_add_to_cart_quantity', $quantity, $product_id);
        if ($quantity <= 0 || !$product_data || 'trash' === $product_data->get_status()) {
            return false;
        }
        $cart_item_data = (array) apply_filters('woocommerce_add_cart_item_data', $cart_item_data, $product_id, $variation_id, $quantity);
        $cart_id = WC()->cart->generate_cart_id($product_id, $variation_id, $variation, $cart_item_data);
        $cart_item_key = WC()->cart->find_product_in_cart($cart_id);
        if ($product_data->is_sold_individually()) {
            $quantity = apply_filters('woocommerce_add_to_cart_sold_individually_quantity', 1, $quantity, $product_id, $variation_id, $cart_item_data);
            $found_in_cart = apply_filters('woocommerce_add_to_cart_sold_individually_found_in_cart', $cart_item_key && WC()->cart->cart_contents[$cart_item_key]['quantity'] > 0, $product_id, $variation_id, $cart_item_data, $cart_id);
            if ($found_in_cart) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    public function angelleye_display_custom_message_review_page() {
        if (!empty($this->order_review_page_custom_message)) {
            if (!is_admin() && is_main_query() && in_the_loop() && is_page() && is_checkout() && $this->function_helper->ec_is_express_checkout()) {
                echo '<div class="woocommerce-info angelleye-order-review-page-message" role="alert">' . $this->order_review_page_custom_message . '</div>';
            }
        }
    }

    public function angelleye_ec_force_to_display_checkout_page_js() {
        $this->enable_guest_checkout = get_option('woocommerce_enable_guest_checkout') == 'yes' ? true : false;
        $this->must_create_account = $this->enable_guest_checkout || is_user_logged_in() ? false : true;
        $force_to_display_checkout_page = true;
        if ($this->skip_final_review == 'no') {
            return apply_filters('angelleye_ec_force_to_display_checkout_page', true);
        }
        if ('yes' === get_option('woocommerce_registration_generate_username') && 'yes' === get_option('woocommerce_registration_generate_password')) {
            $this->must_create_account = false;
        }
        if ($this->must_create_account) {
            return apply_filters('angelleye_ec_force_to_display_checkout_page', true);
        }
        if (AngellEYE_Utility::is_cart_contains_subscription() == true) {
            return apply_filters('angelleye_ec_force_to_display_checkout_page', true);
        }
        $paypal_express_terms = angelleye_get_session('paypal_express_terms');
        if (wc_get_page_id('terms') > 0 && apply_filters('woocommerce_checkout_show_terms', true)) {
            if ($this->disable_term) {
                return apply_filters('angelleye_ec_force_to_display_checkout_page', false);
            } elseif ((isset($_POST['terms']) || isset($_POST['legal'])) && $_POST['terms'] == 'on') {
                return apply_filters('angelleye_ec_force_to_display_checkout_page', false);
            } elseif (!empty($paypal_express_terms) && $paypal_express_terms == true) {
                return apply_filters('angelleye_ec_force_to_display_checkout_page', false);
            }
        }
        if ($this->skip_final_review == 'yes') {
            return apply_filters('angelleye_ec_force_to_display_checkout_page', false);
        }
        return apply_filters('angelleye_ec_force_to_display_checkout_page', $force_to_display_checkout_page);
    }

    public function angelleye_woocommerce_locate_template($template, $template_name, $template_path) {
        if ($template_name != 'cart/proceed-to-checkout-button.php') {
            return $template;
        }
        $change_proceed_checkout_button_text = get_option('change_proceed_checkout_button_text');
        if (empty($change_proceed_checkout_button_text)) {
            return $template;
        }
        global $woocommerce;
        $_template = $template;
        if (!$template_path) {
            $template_path = $woocommerce->template_url;
        }
        $plugin_path = PAYPAL_FOR_WOOCOMMERCE_DIR_PATH . '/template/';
        $template = locate_template(array($template_path . $template_name, $template_name));
        if (!$template && file_exists($plugin_path . $template_name)) {
            $template = $plugin_path . $template_name;
        }
        if (!$template) {
            $template = $_template;
        }
        return $template;
    }

    public function angelleye_display_paypal_button_checkout_page() {
        if (!isset(WC()->cart) && WC()->cart->needs_payment() == false) {
            return;
        }
        if ($this->show_on_checkout != 'regular' && $this->show_on_checkout != 'both') {
            return;
        }
        wp_enqueue_script('angelleye-in-context-checkout-js');
        wp_enqueue_script('angelleye-in-context-checkout-js-frontend');
        do_action('angelleye_fraudnet_hook', $this->setting);
        ?>
        <div class="angelleye_smart_button_checkout_bottom"></div>
        <?php
    }

    public function wsc_cart_button() {
        if (AngellEYE_Utility::is_express_checkout_credentials_is_set()) {
            $this->woocommerce_before_cart();
            $mini_cart_button_html = '';
            $mini_cart_button_html .= $this->woocommerce_paypal_express_checkout_button_angelleye($return = true, 'wsc');
            $mini_cart_button_html .= "<div class='clear'></div>";
            return apply_filters('angelleye_ec_mini_cart_button_html', $mini_cart_button_html);
        }
    }

    public function angelleye_xoo_cu_wsc_paypal_express() {
        echo '<div class="widget_shopping_cart">';
        echo $this->wsc_cart_button();
        echo '</div>';
    }

    public function angelleye_maybe_enqueue_checkout_js($widget_title, $widget_instance = array(), $widget_id = null) {
        if ('woocommerce_widget_cart' === $widget_id) {
            if (AngellEYE_Utility::is_express_checkout_credentials_is_set()) {
                if ($this->enable_in_context_checkout_flow === 'yes' && $this->enabled == 'yes' && $this->show_on_minicart == 'yes') {
                    wp_print_scripts('angelleye-in-context-checkout-js');
                    wp_print_scripts('angelleye-in-context-checkout-js-frontend');
                }
            }
        }
        return $widget_title;
    }

    public function angelleye_woocommerce_before_checkout_process() {
        if (isset($_POST['_wcf_checkout_id']) && isset($_POST['_wcf_flow_id'])) {
            $_GET['wc-ajax'] = 'checkout';
            wc_maybe_define_constant('DOING_AJAX', true);
            wc_maybe_define_constant('WC_DOING_AJAX', true);
        }
    }

    public function is_angelleye_product_page() {
        global $post;
        if (is_product() || (!empty($post->post_content) && strstr($post->post_content, '[product_page') )) {
            return true;
        }

        return false;
    }

    public function angelleye_display_credit_messaging_home_page_content($content) {
        if (AngellEYE_Utility::is_cart_contains_subscription() == true) {
            return $content;
        }
        if ((is_home() || is_front_page())) {
            wp_enqueue_script('angelleye-in-context-checkout-js');
            wp_enqueue_script('angelleye-credit-messaging-home', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/js/credit-messaging/home.js', array('jquery'), $this->version, true);
            $this->angelleye_paypal_credit_messaging_js_enqueue($placement = 'home');
            $content = '<div class="angelleye_pp_message_home"></div>' . $content;
            return $content;
        }
        return $content;
    }

    public function angelleye_display_credit_messaging_home_page() {
        if (AngellEYE_Utility::is_cart_contains_subscription() == true) {
            return false;
        }
        if (is_shop()) {
            wp_enqueue_script('angelleye-in-context-checkout-js');
            wp_enqueue_script('angelleye-credit-messaging-home', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/js/credit-messaging/home.js', array('jquery'), $this->version, true);
            $this->angelleye_paypal_credit_messaging_js_enqueue($placement = 'home');
            echo '<div class="angelleye_pp_message_home"></div>';
        }
    }

    public function angelleye_display_credit_messaging_category_page() {
        if (AngellEYE_Utility::is_cart_contains_subscription() == true) {
            return false;
        }
        if (is_shop() === false && $this->credit_messaging_category_shortcode === false) {
            wp_enqueue_script('angelleye-in-context-checkout-js');
            wp_enqueue_script('angelleye-credit-messaging-category', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/js/credit-messaging/category.js', array('jquery'), $this->version, true);
            $this->angelleye_paypal_credit_messaging_js_enqueue($placement = 'category');
            echo '<div class="angelleye_pp_message_category"></div>';
        }
    }

    public function angelleye_display_credit_messaging_product_page() {
        if (AngellEYE_Utility::is_cart_contains_subscription() == true) {
            return false;
        }
        wp_enqueue_script('angelleye-in-context-checkout-js');
        wp_enqueue_script('angelleye-credit-messaging-product', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/js/credit-messaging/product.js', array('jquery'), $this->version, true);
        $this->angelleye_paypal_credit_messaging_js_enqueue($placement = 'product');
        echo '<div class="angelleye_pp_message_product"></div>';
    }

    public function angelleye_display_credit_messaging_cart_page() {
        if (WC()->cart->is_empty()) {
            return false;
        }
        if (AngellEYE_Utility::is_cart_contains_subscription() == true) {
            return false;
        }
        wp_enqueue_script('angelleye-in-context-checkout-js');
        wp_enqueue_script('angelleye-credit-messaging-cart', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/js/credit-messaging/cart.js', array('jquery'), $this->version, true);
        $this->angelleye_paypal_credit_messaging_js_enqueue($placement = 'cart');
        echo '<div class="angelleye_pp_message_cart"></div>';
    }

    public function angelleye_display_credit_messaging_bottom_cart_page($button) {
        if (WC()->cart->is_empty()) {
            return $button;
        }
        if (AngellEYE_Utility::is_cart_contains_subscription() == true) {
            return false;
        }
        wp_enqueue_script('angelleye-credit-messaging-cart', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/js/credit-messaging/cart.js', array('jquery'), $this->version, true);
        $this->angelleye_paypal_credit_messaging_js_enqueue($placement = 'cart');
        $button .= '<div class="angelleye_pp_message_cart"></div>';
        return $button;
    }

    public function angelleye_display_credit_messaging_payment_page() {
        if (WC()->cart->is_empty()) {
            return false;
        }
        if (AngellEYE_Utility::is_cart_contains_subscription() == true) {
            return false;
        }
        wp_enqueue_script('angelleye-in-context-checkout-js');
        wp_enqueue_script('angelleye-credit-messaging-payment', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/js/credit-messaging/payment.js', array('jquery'), $this->version, true);
        $this->angelleye_paypal_credit_messaging_js_enqueue($placement = 'payment');
        echo '<div class="angelleye_pp_message_payment"></div>';
    }

    public function is_paypal_credit_messaging_enable_for_page($page = '') {
        if ($this->enabled_credit_messaging) {
            if (empty($page)) {
                return false;
            }
            if (in_array($page, $this->credit_messaging_page_type)) {
                return true;
            }
        }
        return false;
    }

    public function angelleye_paypal_credit_messaging_js_enqueue($placement = '', $atts = null) {
        if (!empty($placement)) {
            $enqueue_script_param = array();
            $enqueue_script_param['amount'] = $this->angelleye_ec_get_order_total();
            switch ($placement) {
                case 'home':
                    $required_keys = array(
                        'credit_messaging_home_layout_type' => 'flex',
                        'credit_messaging_home_text_layout_logo_type' => 'primary',
                        'credit_messaging_home_text_layout_logo_position' => 'left',
                        'credit_messaging_home_text_layout_text_size' => '12',
                        'credit_messaging_home_text_layout_text_color' => 'black',
                        'credit_messaging_home_flex_layout_color' => 'blue',
                        'credit_messaging_home_flex_layout_ratio' => '8x1'
                    );
                    foreach ($required_keys as $key => $value) {
                        $enqueue_script_param[$key] = isset($this->setting[$key]) ? $this->setting[$key] : $value;
                    }
                    wp_localize_script('angelleye-credit-messaging-home', 'angelleye_credit_messaging', $enqueue_script_param);
                    break;
                case 'category':
                    $required_keys = array(
                        'credit_messaging_category_layout_type' => 'flex',
                        'credit_messaging_category_text_layout_logo_type' => 'primary',
                        'credit_messaging_category_text_layout_logo_position' => 'left',
                        'credit_messaging_category_text_layout_text_size' => '12',
                        'credit_messaging_category_text_layout_text_color' => 'black',
                        'credit_messaging_category_flex_layout_color' => 'blue',
                        'credit_messaging_category_flex_layout_ratio' => '8x1'
                    );
                    foreach ($required_keys as $key => $value) {
                        $enqueue_script_param[$key] = isset($this->setting[$key]) ? $this->setting[$key] : $value;
                    }
                    wp_localize_script('angelleye-credit-messaging-category', 'angelleye_credit_messaging', $enqueue_script_param);
                    break;
                case 'product':
                    $required_keys = array(
                        'credit_messaging_product_layout_type' => 'text',
                        'credit_messaging_product_text_layout_logo_type' => 'primary',
                        'credit_messaging_product_text_layout_logo_position' => 'left',
                        'credit_messaging_product_text_layout_text_size' => '12',
                        'credit_messaging_product_text_layout_text_color' => 'black',
                        'credit_messaging_product_flex_layout_color' => 'blue',
                        'credit_messaging_product_flex_layout_ratio' => '8x1'
                    );
                    foreach ($required_keys as $key => $value) {
                        $enqueue_script_param[$key] = isset($this->setting[$key]) ? $this->setting[$key] : $value;
                    }
                    wp_localize_script('angelleye-credit-messaging-product', 'angelleye_credit_messaging', $enqueue_script_param);
                    break;
                case 'cart':
                    $required_keys = array(
                        'credit_messaging_cart_layout_type' => 'text',
                        'credit_messaging_cart_text_layout_logo_type' => 'primary',
                        'credit_messaging_cart_text_layout_logo_position' => 'left',
                        'credit_messaging_cart_text_layout_text_size' => '12',
                        'credit_messaging_cart_text_layout_text_color' => 'black',
                        'credit_messaging_cart_flex_layout_color' => 'blue',
                        'credit_messaging_cart_flex_layout_ratio' => '8x1'
                    );
                    foreach ($required_keys as $key => $value) {
                        $enqueue_script_param[$key] = isset($this->setting[$key]) ? $this->setting[$key] : $value;
                    }
                    wp_localize_script('angelleye-credit-messaging-cart', 'angelleye_credit_messaging', $enqueue_script_param);
                    break;
                case 'payment':
                    $required_keys = array(
                        'credit_messaging_payment_layout_type' => 'text',
                        'credit_messaging_payment_text_layout_logo_type' => 'primary',
                        'credit_messaging_payment_text_layout_logo_position' => 'left',
                        'credit_messaging_payment_text_layout_text_size' => '12',
                        'credit_messaging_payment_text_layout_text_color' => 'black',
                        'credit_messaging_payment_flex_layout_color' => 'blue',
                        'credit_messaging_payment_flex_layout_ratio' => '8x1'
                    );
                    foreach ($required_keys as $key => $value) {
                        $enqueue_script_param[$key] = isset($this->setting[$key]) ? $this->setting[$key] : $value;
                    }
                    wp_localize_script('angelleye-credit-messaging-payment', 'angelleye_credit_messaging', $enqueue_script_param);
                    break;
                case 'shortcode':
                    $atts['amount'] = $enqueue_script_param['amount'];
                    wp_localize_script('angelleye-credit-messaging-shortcode', 'angelleye_credit_messaging', $atts);
                    break;
                default:
                    break;
            }
        }
    }

    public function angelleye_get_default_attribute_paypal_credit($placement = '') {
        if (!empty($placement)) {
            $enqueue_script_param = array();
            $enqueue_script_param['amount'] = $this->angelleye_ec_get_order_total();
            switch ($placement) {
                case 'home':
                    $required_keys = array(
                        'credit_messaging_home_layout_type' => 'flex',
                        'credit_messaging_home_text_layout_logo_type' => 'primary',
                        'credit_messaging_home_text_layout_logo_position' => 'left',
                        'credit_messaging_home_text_layout_text_size' => '12',
                        'credit_messaging_home_text_layout_text_color' => 'black',
                        'credit_messaging_home_flex_layout_color' => 'blue',
                        'credit_messaging_home_flex_layout_ratio' => '8x1'
                    );
                    foreach ($required_keys as $key => $value) {
                        $enqueue_script_param[$key] = isset($this->setting[$key]) ? $this->setting[$key] : $value;
                    }
                    return $enqueue_script_param;
                case 'category':
                    $required_keys = array(
                        'credit_messaging_category_layout_type' => 'flex',
                        'credit_messaging_category_text_layout_logo_type' => 'primary',
                        'credit_messaging_category_text_layout_logo_position' => 'left',
                        'credit_messaging_category_text_layout_text_size' => '12',
                        'credit_messaging_category_text_layout_text_color' => 'black',
                        'credit_messaging_category_flex_layout_color' => 'blue',
                        'credit_messaging_category_flex_layout_ratio' => '8x1'
                    );
                    foreach ($required_keys as $key => $value) {
                        $enqueue_script_param[$key] = isset($this->setting[$key]) ? $this->setting[$key] : $value;
                    }
                    return $enqueue_script_param;
                case 'product':
                    $required_keys = array(
                        'credit_messaging_product_layout_type' => 'text',
                        'credit_messaging_product_text_layout_logo_type' => 'primary',
                        'credit_messaging_product_text_layout_logo_position' => 'left',
                        'credit_messaging_product_text_layout_text_size' => '12',
                        'credit_messaging_product_text_layout_text_color' => 'black',
                        'credit_messaging_product_flex_layout_color' => 'blue',
                        'credit_messaging_product_flex_layout_ratio' => '8x1'
                    );
                    foreach ($required_keys as $key => $value) {
                        $enqueue_script_param[$key] = isset($this->setting[$key]) ? $this->setting[$key] : $value;
                    }
                    return $enqueue_script_param;
                case 'cart':
                    $required_keys = array(
                        'credit_messaging_cart_layout_type' => 'text',
                        'credit_messaging_cart_text_layout_logo_type' => 'primary',
                        'credit_messaging_cart_text_layout_logo_position' => 'left',
                        'credit_messaging_cart_text_layout_text_size' => '12',
                        'credit_messaging_cart_text_layout_text_color' => 'black',
                        'credit_messaging_cart_flex_layout_color' => 'blue',
                        'credit_messaging_cart_flex_layout_ratio' => '8x1'
                    );
                    foreach ($required_keys as $key => $value) {
                        $enqueue_script_param[$key] = isset($this->setting[$key]) ? $this->setting[$key] : $value;
                    }
                    return $enqueue_script_param;
                case 'payment':
                    $required_keys = array(
                        'credit_messaging_payment_layout_type' => 'text',
                        'credit_messaging_payment_text_layout_logo_type' => 'primary',
                        'credit_messaging_payment_text_layout_logo_position' => 'left',
                        'credit_messaging_payment_text_layout_text_size' => '12',
                        'credit_messaging_payment_text_layout_text_color' => 'black',
                        'credit_messaging_payment_flex_layout_color' => 'blue',
                        'credit_messaging_payment_flex_layout_ratio' => '8x1'
                    );
                    foreach ($required_keys as $key => $value) {
                        $enqueue_script_param[$key] = isset($this->setting[$key]) ? $this->setting[$key] : $value;
                    }
                    return $enqueue_script_param;
                default:
                    break;
            }
        }
    }

    public function angelleye_ec_get_order_total() {
        global $product;
        $total = 0;
        $order_id = absint(get_query_var('order-pay'));
        if (is_product()) {
            $total = $product->get_price();
        } elseif (0 < $order_id) {
            $order = wc_get_order($order_id);
            $total = (float) $order->get_total();
        } elseif (isset(WC()->cart) && 0 < WC()->cart->total) {
            $total = (float) WC()->cart->total;
        }
        return $total;
    }

    public function aepfw_bnpl_message_shortcode($atts) {
        if (empty($atts['placement'])) {
            return '';
        }
        if (!in_array($atts['placement'], array('home', 'category', 'product', 'cart', 'payment'))) {
            return;
        }

        if ($this->is_paypal_credit_messaging_enable_for_page($page = $atts['placement']) === false) {
            return false;
        }

        if ($this->is_paypal_credit_messaging_enable_for_shoerpage($page = $atts['placement']) === false) {
            return false;
        }

        $placement = $atts['placement'];

        if (!isset($atts['style'])) {
            $atts['style'] = $this->angelleye_credit_messaging_get_default_value('style', $placement);
        }
        if ($atts['style'] === 'text') {
            $default_array = array(
                'placement' => 'home',
                'style' => $atts['style'],
                'logotype' => $this->angelleye_credit_messaging_get_default_value('logotype', $placement),
                'logoposition' => $this->angelleye_credit_messaging_get_default_value('logoposition', $placement),
                'textsize' => $this->angelleye_credit_messaging_get_default_value('textsize', $placement),
                'textcolor' => $this->angelleye_credit_messaging_get_default_value('textcolor', $placement),
            );
        } else {
            $default_array = array(
                'placement' => 'home',
                'style' => $atts['style'],
                'color' => $this->angelleye_credit_messaging_get_default_value('color', $placement),
                'ratio' => $this->angelleye_credit_messaging_get_default_value('ratio', $placement)
            );
        }
        $atts = array_merge(
                $default_array, (array) $atts
        );

        wp_enqueue_script('angelleye-in-context-checkout-js');
        wp_enqueue_script('angelleye-credit-messaging-shortcode', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/js/credit-messaging/shortcode.js', array('jquery'), $this->version, true);
        $this->angelleye_paypal_credit_messaging_js_enqueue($placement_default = 'shortcode', $atts);
        return '<div class="angelleye_pp_message_shortcode"></div>';
    }

    public function angelleye_credit_messaging_get_default_value($key, $placement) {
        if (!empty($key) && !empty($placement)) {
            $param = $this->angelleye_get_default_attribute_paypal_credit($placement);
            $map_keys = array('placement' => '', 'style' => 'credit_messaging_default_layout_type', 'logotype' => 'credit_messaging_default_text_layout_logo_type', 'logoposition' => 'credit_messaging_default_text_layout_logo_position', 'textsize' => 'credit_messaging_default_text_layout_text_size', 'textcolor' => 'credit_messaging_default_text_layout_text_color', 'color' => 'credit_messaging_default_flex_layout_color', 'ratio' => 'credit_messaging_default_flex_layout_ratio');
            if (!empty($map_keys[$key])) {
                $default_key = str_replace('default', $placement, $map_keys[$key]);
                if (!empty($param[$default_key])) {
                    return $param[$default_key];
                }
            }
            return '';
        }
    }

    public function is_paypal_credit_messaging_enable_for_shoerpage($page = '') {
        switch ($page) {
            case 'home':
                if ($this->credit_messaging_home_shortcode) {
                    return true;
                }
                break;
            case 'category':
                if ($this->credit_messaging_category_shortcode) {
                    return true;
                }
                break;
            case 'product':
                if ($this->credit_messaging_product_shortcode) {
                    return true;
                }
                break;
            case 'cart':
                if ($this->credit_messaging_cart_shortcode) {
                    return true;
                }
                break;
            case 'payment':
                if ($this->credit_messaging_payment_shortcode) {
                    return true;
                }
                break;
            default:
                break;
        }
        return false;
    }
    
    public function own_angelleye_fraudnet_hook($setting) {
        $this->is_fraudnet_ready = true;
    }

    public function own_angelleye_fraudnet_script() {
        if ( !isset(WC()->cart)) {
            return false;
        }
        if( WC()->cart->is_empty()) {
            return false;
        }
        if($this->is_fraudnet_ready === false) {
            return false;
        }
        $angelleye_fraudnet_f = angelleye_get_session('angelleye_fraudnet_f');
        if (!empty($angelleye_fraudnet_f)) {
            return false;
        }
        $settings = $this->setting;
        if( !empty($settings['enable_fraudnet_integration']) && 'yes' === $settings['enable_fraudnet_integration'] && !empty($settings['fraudnet_swi'])) {
            $uuid = $this->angelleye_generate_request_id();
            angelleye_set_session( 'angelleye_fraudnet_f', $uuid);
            ?>
            <!-- PayPal BEGIN -->
            <script type="application/json" fncls="fnparams-dede7cc5-15fd-4c75-a9f4-36c430ee3a99">
                {
                    "f":"<?php echo $uuid; ?>",
                    "s":"<?php echo $settings['fraudnet_swi']; ?>"
                }
            </script>
            }
            <script type="text/javascript" src="https://c.paypal.com/da/r/fb.js"></script>
            <!-- PayPal END -->
            <?php
        }
    }
    
    public function angelleye_generate_request_id() {
        static $pid = -1;
        static $addr = -1;
        if ($pid == -1) {
            $pid = getmypid();
        }
        if ($addr == -1) {
            if (array_key_exists('SERVER_ADDR', $_SERVER)) {
                $addr = ip2long($_SERVER['SERVER_ADDR']);
            } else {
                $addr = php_uname('n');
            }
        }
        $str = $addr . $pid . $_SERVER['REQUEST_TIME'] . mt_rand(0, 0xffff);
        if (32 < strlen($str)) {
            $str = substr($str, 0, 32);
        }
        return $str;
    }
    
    public function angelleye_in_content_js($url) {
        if (strpos($url, 'https://www.paypal.com/sdk/js') !== false) {
            $url = "$url' data-namespace='paypal_sdk";
        }
        return $url;
    }
    
    public function angelleye_exclude_javascript($excluded_handles) {
        $excluded_handles[] = 'jquery-core';
        $excluded_handles[] = 'angelleye-in-context-checkout-js';
        $excluded_handles[] = 'angelleye-in-context-checkout-js-frontend';
        return $excluded_handles;
    }
}
