<?php
defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Smart_Button {

    private $angelleye_ppcp_plugin_name;
    private $version;
    public $api_request;
    public $checkout_details;
    public $settings;
    public $api_log;
    public $dcc_applies;
    public $payment_request;
    public $client_token;
    protected static $_instance = null;
    public $advanced_card_payments_display_position;
    public $enable_paypal_checkout_page;
    public $checkout_page_display_option;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->angelleye_ppcp_plugin_name = 'angelleye_ppcp';
        $this->version = VERSION_PFW;
        $this->angelleye_ppcp_load_class();
        $this->angelleye_ppcp_get_properties();
        $this->angelleye_ppcp_default_set_properties();
        if ($this->is_valid_for_use() === true) {
            $this->angelleye_ppcp_add_hooks();
        }
    }

    public function angelleye_ppcp_load_class() {
        try {
            if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-ppcp-angelleye-settings.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Log')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-log.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Request')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-request.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_DCC_Validate')) {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-dcc-validate.php');
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Payment')) {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-payment.php');
            }
            $this->settings = WC_Gateway_PPCP_AngellEYE_Settings::instance();
            $this->api_log = AngellEYE_PayPal_PPCP_Log::instance();
            $this->api_request = AngellEYE_PayPal_PPCP_Request::instance();
            $this->dcc_applies = AngellEYE_PayPal_PPCP_DCC_Validate::instance();
            $this->payment_request = AngellEYE_PayPal_PPCP_Payment::instance();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_get_properties() {
        $this->title = $this->settings->get('title', 'PayPal Complete Payments');
        $this->enabled = 'yes' === $this->settings->get('enabled', 'no');
        $this->enable_paypal_checkout_page = 'yes' === $this->settings->get('enable_paypal_checkout_page', 'yes');
        $this->checkout_page_display_option = $this->settings->get('checkout_page_display_option', 'regular');
        $this->is_sandbox = 'yes' === $this->settings->get('testmode', 'no');
        $this->order_review_page_enable_coupons = 'yes' === $this->settings->get('order_review_page_enable_coupons', 'yes');
        $this->order_review_page_title = apply_filters('angelleye_ppcp_order_review_page_title', __('Confirm Your PayPal Order', 'paypal-for-woocommerce'));
        $this->order_review_page_description = apply_filters('angelleye_ppcp_order_review_page_description', __("<strong>You're almost done!</strong><br>Review your information before you place your order.", 'paypal-for-woocommerce'));
        $this->paymentaction = $this->settings->get('paymentaction', 'capture');
        $this->advanced_card_payments = 'yes' === $this->settings->get('enable_advanced_card_payments', 'no');
        $this->enable_separate_payment_method = 'yes' === $this->settings->get('enable_separate_payment_method', 'no');
        $this->cart_button_position = $this->settings->get('cart_button_position', 'bottom');
        if ($this->advanced_card_payments) {
            $this->advanced_card_payments_display_position = $this->settings->get('advanced_card_payments_display_position', 'after');
            if ($this->enable_paypal_checkout_page === false || $this->checkout_page_display_option === 'top') {
                $this->enable_separate_payment_method = true;
            }
        } else {
            $this->enable_separate_payment_method = false;
        }

        $this->enabled_pay_later_messaging = 'yes' === $this->settings->get('enabled_pay_later_messaging', 'yes');
        $this->pay_later_messaging_page_type = $this->settings->get('pay_later_messaging_page_type', array('product', 'cart', 'payment'));
        if (wc_ship_to_billing_address_only()) {
            $this->set_billing_address = true;
        } else {
            $this->set_billing_address = 'yes' === $this->settings->get('set_billing_address', 'no');
        }
        $this->disable_term = 'yes' === $this->settings->get('disable_term', 'no');
        $this->skip_final_review = 'yes' === $this->settings->get('skip_final_review', 'no');
        $this->sandbox_client_id = $this->settings->get('sandbox_client_id', '');
        $this->sandbox_secret_id = $this->settings->get('sandbox_api_secret', '');
        $this->live_client_id = $this->settings->get('api_client_id', '');
        $this->live_secret_id = $this->settings->get('api_secret', '');
        if (!empty($this->sandbox_client_id) && !empty($this->sandbox_secret_id)) {
            $this->is_sandbox_first_party_used = 'yes';
            $this->is_sandbox_third_party_used = 'no';
        } else if (!empty($this->sandbox_merchant_id)) {
            $this->is_sandbox_third_party_used = 'yes';
            $this->is_sandbox_first_party_used = 'no';
        } else {
            $this->is_sandbox_third_party_used = 'no';
            $this->is_sandbox_first_party_used = 'no';
        }
        if (!empty($this->live_client_id) && !empty($this->live_secret_id)) {
            $this->is_live_first_party_used = 'yes';
            $this->is_live_third_party_used = 'no';
        } else if (!empty($this->live_merchant_id)) {
            $this->is_live_third_party_used = 'yes';
            $this->is_live_first_party_used = 'no';
        } else {
            $this->is_live_third_party_used = 'no';
            $this->is_live_first_party_used = 'no';
        }
        if ($this->is_sandbox) {
            $this->merchant_id = $this->settings->get('sandbox_merchant_id', '');
            $this->client_id = $this->sandbox_client_id;
            $this->secret_id = $this->sandbox_secret_id;
            if ($this->is_sandbox_first_party_used === 'yes') {
                $this->is_first_party_used = 'yes';
            } else {
                $this->is_first_party_used = 'no';
            }
        } else {
            $this->merchant_id = $this->settings->get('live_merchant_id', '');
            $this->client_id = $this->live_client_id;
            $this->secret_id = $this->live_secret_id;
            if ($this->is_live_first_party_used === 'yes') {
                $this->is_first_party_used = 'yes';
            } else {
                $this->is_first_party_used = 'no';
            }
        }
        if (empty($this->pay_later_messaging_page_type)) {
            $this->enabled_pay_later_messaging = false;
        }
        if ($this->dcc_applies->for_country_currency() === false) {
            $this->advanced_card_payments = false;
        }
        $this->three_d_secure_contingency = $this->settings->get('3d_secure_contingency', 'SCA_WHEN_REQUIRED');
        $this->disable_cards = $this->settings->get('disable_cards', array());
    }

    public function angelleye_ppcp_default_set_properties() {
        $this->angelleye_ppcp_currency_list = array('AUD', 'BRL', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'INR', 'ILS', 'JPY', 'MYR', 'MXN', 'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'THB', 'USD');
        $this->angelleye_ppcp_currency = in_array(get_woocommerce_currency(), $this->angelleye_ppcp_currency_list) ? get_woocommerce_currency() : 'USD';
        $this->enable_product_button = 'yes' === $this->settings->get('enable_product_button', 'yes');
        $this->enable_cart_button = 'yes' === $this->settings->get('enable_cart_button', 'yes');
        $this->checkout_disable_smart_button = 'yes' === $this->settings->get('checkout_disable_smart_button', 'no');
        $this->enable_mini_cart_button = 'yes' === $this->settings->get('enable_mini_cart_button', 'yes');
    }

    public function angelleye_ppcp_smart_button_style_properties() {
        $this->disable_funding = array();
        $this->style_layout = $this->settings->get('cart_button_layout', 'vertical');
        $this->style_color = 'gold';
        $this->style_shape = 'rect';
        $this->style_label = 'paypal';
        $this->style_tagline = 'yes';
        $this->style_size = 'responsive';
        $this->style_height = '';
        if (is_product()) {
            $this->disable_funding = $this->settings->get('product_disallowed_funding_methods', array());
            $this->style_layout = $this->settings->get('product_button_layout', 'horizontal');
            $this->style_color = $this->settings->get('product_style_color', 'gold');
            $this->style_shape = $this->settings->get('product_style_shape', 'rect');
            $this->style_size = $this->settings->get('product_button_size', 'responsive');
            $this->style_height = $this->settings->get('product_button_height', '');
            $this->style_label = $this->settings->get('product_button_label', 'paypal');
            $this->style_tagline = $this->settings->get('product_button_tagline', 'yes');
        } elseif (is_cart()) {
            $this->disable_funding = $this->settings->get('cart_disallowed_funding_methods', array());
            $this->style_layout = $this->settings->get('cart_button_layout', 'vertical');
            $this->style_color = $this->settings->get('cart_style_color', 'gold');
            $this->style_shape = $this->settings->get('cart_style_shape', 'rect');
            $this->style_size = $this->settings->get('cart_button_size', 'responsive');
            $this->style_height = $this->settings->get('cart_button_height', '');
            $this->style_label = $this->settings->get('cart_button_label', 'paypal');
            $this->style_tagline = $this->settings->get('cart_button_tagline', 'yes');
        } elseif (is_checkout() || is_checkout_pay_page()) {
            $this->disable_funding = $this->settings->get('checkout_disallowed_funding_methods', array());
            $this->style_layout = $this->settings->get('checkout_button_layout', 'vertical');
            $this->style_color = $this->settings->get('checkout_style_color', 'gold');
            $this->style_shape = $this->settings->get('checkout_style_shape', 'rect');
            $this->style_size = $this->settings->get('checkout_button_size', 'responsive');
            $this->style_height = $this->settings->get('checkout_button_height', '');
            $this->style_label = $this->settings->get('checkout_button_label', 'paypal');
            $this->style_tagline = $this->settings->get('checkout_button_tagline', 'yes');
        }
        $this->mini_cart_disable_funding = $this->settings->get('mini_cart_disallowed_funding_methods', array());
        $this->mini_cart_style_layout = $this->settings->get('mini_cart_button_layout', 'vertical');
        $this->mini_cart_style_color = $this->settings->get('mini_cart_style_color', 'gold');
        $this->mini_cart_style_shape = $this->settings->get('mini_cart_style_shape', 'rect');
        $this->mini_cart_style_size = $this->settings->get('cart_button_size', 'responsive');
        $this->mini_cart_style_height = $this->settings->get('cart_button_height', '');
        $this->mini_cart_style_label = $this->settings->get('mini_cart_button_label', 'paypal');
        $this->mini_cart_style_tagline = $this->settings->get('mini_cart_button_tagline', 'yes');
    }

    public function angelleye_ppcp_add_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 9);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        if ($this->enable_product_button) {
            add_action('woocommerce_after_add_to_cart_form', array($this, 'display_paypal_button_product_page'), 10);
        }
        if ($this->enable_cart_button) {
            if ($this->cart_button_position === 'both') {
                add_action('woocommerce_before_cart_table', array($this, 'display_paypal_button_cart_page_top'));
                add_action('woocommerce_proceed_to_checkout', array($this, 'display_paypal_button_cart_page'), 11);
            } elseif ($this->cart_button_position === 'top') {
                add_action('woocommerce_before_cart_table', array($this, 'display_paypal_button_cart_page_top'));
            } else {
                add_action('woocommerce_proceed_to_checkout', array($this, 'display_paypal_button_cart_page'), 11);
            }
        }
        if ($this->checkout_disable_smart_button === false) {
            add_action('angelleye_ppcp_display_paypal_button_checkout_page', array($this, 'display_paypal_button_checkout_page'));
        }
        add_action('init', array($this, 'init'));
        add_filter('script_loader_tag', array($this, 'angelleye_ppcp_clean_url'), 10, 2);
        add_action('wp_loaded', array($this, 'angelleye_ppcp_session_manager'), 999);
        add_filter('the_title', array($this, 'angelleye_ppcp_endpoint_page_titles'));
        add_action('woocommerce_cart_emptied', array($this, 'maybe_clear_session_data'));
        add_action('woocommerce_checkout_init', array($this, 'angelleye_ppcp_checkout_init'));
        add_action('woocommerce_available_payment_gateways', array($this, 'maybe_disable_other_gateways'));
        add_filter('woocommerce_default_address_fields', array($this, 'filter_default_address_fields'));
        add_filter('woocommerce_billing_fields', array($this, 'filter_billing_fields'));
        add_action('woocommerce_checkout_process', array($this, 'copy_checkout_details_to_post'));
        add_action('woocommerce_cart_shipping_packages', array($this, 'maybe_add_shipping_information'));
        add_filter('body_class', array($this, 'angelleye_ppcp_add_class_order_review_page'));
        add_filter('woocommerce_coupons_enabled', array($this, 'angelleye_ppcp_woocommerce_coupons_enabled'), 999, 1);
        add_action('woocommerce_before_checkout_form', array($this, 'angelleye_ppcp_order_review_page_description'), 9);
        add_action('woocommerce_before_checkout_form', array($this, 'angelleye_ppcp_update_checkout_field_details'));
        if ($this->enable_paypal_checkout_page === true && $this->checkout_page_display_option !== 'regular') {
            add_action('woocommerce_before_checkout_form', array($this, 'display_paypal_button_top_checkout_page'), 5);
        }
        add_action('woocommerce_review_order_before_submit', array($this, 'angelleye_ppcp_cancel_button'));
        add_filter('sgo_js_minify_exclude', array($this, 'angelleye_ppcp_exclude_javascript'), 999);
        add_filter('sgo_javascript_combine_exclude', array($this, 'angelleye_ppcp_exclude_javascript'), 999);
        add_filter('sgo_javascript_combine_excluded_inline_content', array($this, 'angelleye_ppcp_exclude_javascript'), 999);
        add_filter('sgo_js_async_exclude', array($this, 'angelleye_ppcp_exclude_javascript'), 999);
        add_action('woocommerce_pay_order_after_submit', array($this, 'angelleye_ppcp_add_order_id'));
        add_filter('woocommerce_payment_gateways', array($this, 'angelleye_ppcp_hide_show_gateway'), 9999);
        add_filter('woocommerce_available_payment_gateways', array($this, 'angelleye_ppcp_short_gateway'), 9999);

        add_filter('woocommerce_checkout_fields', array($this, 'angelleye_ppcp_woocommerce_checkout_fields'), 999);
        //add_action('http_api_debug', array($this, 'angelleye_ppcp_all_web_request'), 10, 5);
        add_action('woocommerce_review_order_before_order_total', array($this, 'angelleye_ppcp_display_payment_method_title_review_page'));
        add_action('wp_loaded', array($this, 'angelleye_ppcp_prevent_add_to_cart_woo_action'), 1);
        add_action('init', array($this, 'angelleye_ppcp_woocommerce_before_checkout_process'), 0);
        add_action('admin_notices', array($this, 'angelleye_ppcp_admin_notices'));
        add_filter('angelleye_ppcp_paymentaction', array($this, 'angelleye_ppcp_paymentaction_filter'), 10, 2);
        add_filter('angelleye_ppcp_paymentaction_product_page', array($this, 'angelleye_ppcp_paymentaction_product_page_filter'), 10, 2);
        add_shortcode('angelleye_ppcp_smart_button', array($this, 'angelleye_ppcp_display_paypal_smart_button_using_shortcode'), 9);
    }

    /*
     *
     * This function is for temp debug
     */

    public function angelleye_ppcp_all_web_request($response, $response_text, $Requests_text, $parsed_args, $url) {
        $this->api_log->temp_log(wc_print_r($url, true), 'error');
        $this->api_log->temp_log(wc_print_r($Requests_text, true), 'error');
        $this->api_log->temp_log(wc_print_r($parsed_args, true), 'error');
        $this->api_log->temp_log(wc_print_r($response_text, true), 'error');
        $this->api_log->temp_log(wc_print_r($response, true), 'error');
    }

    public function enqueue_scripts() {
        global $post, $wp, $product;
        if ((is_checkout() || is_checkout_pay_page() ) && $this->advanced_card_payments) {
            if (!isset($_GET['paypal_order_id'])) {
                $this->client_token = $this->payment_request->angelleye_ppcp_get_generate_token();
            }
        }
        $this->angelleye_ppcp_smart_button_style_properties();
        $smart_js_arg = array();
        $enable_funding = array();
        $smart_js_arg['currency'] = $this->angelleye_ppcp_currency;
        if (!isset($this->disable_funding['venmo'])) {
            array_push($enable_funding, 'venmo');
        }
        if (!isset($this->disable_funding['paylater'])) {
            array_push($enable_funding, 'paylater');
        }
        if (!empty($this->disable_funding) && count($this->disable_funding) > 0) {
            $smart_js_arg['disable-funding'] = implode(',', $this->disable_funding);
        }
        if ($this->is_sandbox) {
            if ($this->is_first_party_used === 'yes') {
                $smart_js_arg['client-id'] = $this->client_id;
            } else {
                $smart_js_arg['client-id'] = PAYPAL_PPCP_SNADBOX_PARTNER_CLIENT_ID;
                $smart_js_arg['merchant-id'] = apply_filters('angelleye_ppcp_merchant_id', $this->merchant_id);
            }
        } else {
            if ($this->is_first_party_used === 'yes') {
                $smart_js_arg['client-id'] = $this->client_id;
            } else {
                $smart_js_arg['client-id'] = PAYPAL_PPCP_PARTNER_CLIENT_ID;
                $smart_js_arg['merchant-id'] = apply_filters('angelleye_ppcp_merchant_id', $this->merchant_id);
            }
        }
        if ($this->is_sandbox) {
            if (is_user_logged_in() && WC()->customer && WC()->customer->get_billing_country() && 2 === strlen(WC()->customer->get_billing_country())) {
                $smart_js_arg['buyer-country'] = WC()->customer->get_billing_country();
            }
        }
        $page = '';
        $is_pay_page = 'no';
        $first_name = '';
        $last_name = '';
        $button_selector = array();
        $this->paymentaction = apply_filters('angelleye_ppcp_paymentaction', $this->paymentaction, null);
        if (is_product()) {
            $page = 'product';
            $button_selector['angelleye_ppcp_product'] = '#angelleye_ppcp_product';
            if (is_product()) {
                $product_id = $post->ID;
                $this->paymentaction = apply_filters('angelleye_ppcp_paymentaction_product_page', $this->paymentaction, $product_id);
            }
            $button_selector['angelleye_ppcp_product_shortcode'] = '#angelleye_ppcp_product_shortcode';
        } elseif (is_cart() && !WC()->cart->is_empty()) {
            $page = 'cart';
            if ($this->cart_button_position === 'both') {
                $button_selector['angelleye_ppcp_cart'] = '#angelleye_ppcp_cart';
                $button_selector['angelleye_ppcp_cart_top'] = '#angelleye_ppcp_cart_top';
            } elseif ($this->cart_button_position === 'top') {
                $button_selector['angelleye_ppcp_cart_top'] = '#angelleye_ppcp_cart_top';
            } else {
                $button_selector['angelleye_ppcp_cart'] = '#angelleye_ppcp_cart';
            }
            $button_selector['angelleye_ppcp_cart_shortcode'] = '#angelleye_ppcp_cart_shortcode';
        } elseif (is_checkout_pay_page()) {
            $page = 'checkout';
            if ($this->checkout_page_display_option === 'top') {
                $button_selector['angelleye_ppcp_checkout_top'] = '#angelleye_ppcp_checkout_top';
            } elseif ($this->checkout_page_display_option === 'regular') {
                $button_selector['angelleye_ppcp_checkout'] = '#angelleye_ppcp_checkout';
            } elseif ($this->checkout_page_display_option === 'both') {
                $button_selector['angelleye_ppcp_checkout_top'] = '#angelleye_ppcp_checkout_top';
                $button_selector['angelleye_ppcp_checkout'] = '#angelleye_ppcp_checkout';
            }
            $button_selector['angelleye_ppcp_checkout_shortcode'] = '#angelleye_ppcp_checkout_shortcode';
            $is_pay_page = 'yes';
        } elseif (is_checkout()) {
            $page = 'checkout';
            if ($this->checkout_page_display_option === 'top') {
                $button_selector['angelleye_ppcp_checkout_top'] = '#angelleye_ppcp_checkout_top';
            } elseif ($this->checkout_page_display_option === 'regular') {
                $button_selector['angelleye_ppcp_checkout'] = '#angelleye_ppcp_checkout';
            } elseif ($this->checkout_page_display_option === 'both') {
                $button_selector['angelleye_ppcp_checkout_top'] = '#angelleye_ppcp_checkout_top';
                $button_selector['angelleye_ppcp_checkout'] = '#angelleye_ppcp_checkout';
            }
            $button_selector['angelleye_ppcp_checkout_shortcode'] = '#angelleye_ppcp_checkout_shortcode';
        }
        $smart_js_arg['commit'] = $this->angelleye_ppcp_is_skip_final_review() ? 'true' : 'false';
        $smart_js_arg['intent'] = ( $this->paymentaction === 'capture' ) ? 'capture' : 'authorize';
        $smart_js_arg['locale'] = AngellEYE_Utility::get_button_locale_code();
        $components = array("buttons");
        if ((is_checkout() || is_checkout_pay_page()) && $this->advanced_card_payments) {
            array_push($components, "hosted-fields");
            if (is_checkout_pay_page()) {
                $order_id = $wp->query_vars['order-pay'];
                $order_id = absint($order_id);
                $order = wc_get_order($order_id);
                $old_wc = version_compare(WC_VERSION, '3.0', '<');
                $first_name = $old_wc ? $order->billing_first_name : $order->get_billing_first_name();
                $last_name = $old_wc ? $order->billing_last_name : $order->get_billing_last_name();
            }
        }
        if ($this->enabled_pay_later_messaging) {
            array_push($components, 'messages');
        }
        if (!empty($components)) {
            $smart_js_arg['components'] = apply_filters('angelleye_paypal_checkout_sdk_components', implode(',', $components));
        }
        if (!empty($enable_funding) && count($enable_funding) > 0) {
            $smart_js_arg['enable-funding'] = implode(',', $enable_funding);
        }
        if (isset($post->ID) && 'yes' == get_post_meta($post->ID, 'wcf-pre-checkout-offer', true)) {
            $pre_checkout_offer = "yes";
        } else {
            $pre_checkout_offer = "no";
        }
        $js_url = add_query_arg($smart_js_arg, 'https://www.paypal.com/sdk/js');

        wp_register_script('angelleye-paypal-checkout-sdk', $js_url, array(), null, false);
        wp_register_script($this->angelleye_ppcp_plugin_name, PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/js/wc-gateway-ppcp-angelleye-public.js', array('jquery', 'angelleye-paypal-checkout-sdk'), VERSION_PFW, false);
        wp_localize_script($this->angelleye_ppcp_plugin_name, 'angelleye_ppcp_manager', array(
            'style_color' => $this->style_color,
            'style_shape' => $this->style_shape,
            'style_height' => $this->style_height,
            'style_label' => $this->style_label,
            'style_layout' => $this->style_layout,
            'style_tagline' => $this->style_tagline,
            'page' => $page,
            'is_pre_checkout_offer' => $pre_checkout_offer,
            'is_pay_page' => $is_pay_page,
            'checkout_url' => add_query_arg(array('utm_nooverride' => '1'), wc_get_checkout_url()),
            'display_order_page' => add_query_arg(array('angelleye_ppcp_action' => 'display_order_page', 'utm_nooverride' => '1'), untrailingslashit(WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action'))),
            'cc_capture' => add_query_arg(array('angelleye_ppcp_action' => 'cc_capture', 'utm_nooverride' => '1'), untrailingslashit(WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action'))),
            'create_order_url' => add_query_arg(array('angelleye_ppcp_action' => 'create_order', 'utm_nooverride' => '1', 'from' => is_checkout_pay_page() ? 'pay_page' : $page), untrailingslashit(WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action'))),
            'cart_total' => WC()->cart->total,
            'paymentaction' => $this->paymentaction,
            'advanced_card_payments' => ($this->advanced_card_payments === true) ? 'yes' : 'no',
            'prefix_cc_field' => ($this->enable_separate_payment_method === true) ? 'angelleye_ppcp_cc' : 'angelleye_ppcp',
            'three_d_secure_contingency' => $this->three_d_secure_contingency,
            'woocommerce_process_checkout' => wp_create_nonce('woocommerce-process_checkout'),
            'is_skip_final_review' => $this->angelleye_ppcp_is_skip_final_review() ? 'yes' : 'no',
            'is_checkout_disable_smart_button' => ($this->checkout_disable_smart_button) ? 'yes' : 'no',
            'enable_separate_payment_method' => ($this->enable_separate_payment_method === true) ? 'yes' : 'no',
            'direct_capture' => add_query_arg(array('angelleye_ppcp_action' => 'direct_capture', 'utm_nooverride' => '1'), untrailingslashit(WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action'))),
            'cardholder_name_required' => __('Cardholder\'s first and last name are required, please fill the checkout form required fields.', 'paypal-for-woocommerce'),
            'card_not_supported' => __('Unfortunately, we do not support your credit card.', 'paypal-for-woocommerce'),
            'fields_not_valid' => __('Unfortunately, your credit card details are not valid.', 'paypal-for-woocommerce'),
            'disable_cards' => $this->disable_cards,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'button_selector' => $button_selector
                )
        );
        if ((is_checkout() || is_checkout_pay_page()) && empty($this->checkout_details)) {
            wp_enqueue_script($this->angelleye_ppcp_plugin_name . '-order-review', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/js/wc-gateway-ppcp-angelleye-order-review.js', array('jquery'), $this->version, false);
        } elseif (is_checkout() && !empty($this->checkout_details)) {
            wp_enqueue_script($this->angelleye_ppcp_plugin_name . '-order-capture', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/js/wc-gateway-ppcp-angelleye-order-capture.js', array('jquery'), $this->version, false);
        }
    }

    public function enqueue_styles() {
        wp_enqueue_style($this->angelleye_ppcp_plugin_name, PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/css/wc-gateway-ppcp-angelleye-public.css', array(), $this->version, 'all');
    }

    public function is_valid_for_use() {
        if ($this->enabled === false) {
            return false;
        }
        if (!empty($this->merchant_id) || (!empty($this->client_id) && !empty($this->secret_id))) {
            return true;
        }
        return false;
    }

    public function display_paypal_button_cart_page($is_shortcode = "") {
        if (class_exists('WC_Subscriptions_Cart') && WC_Subscriptions_Cart::cart_contains_subscription()) {
            return false;
        }
        $this->angelleye_ppcp_smart_button_style_properties();
        wp_enqueue_script($this->angelleye_ppcp_plugin_name);
        if (WC()->cart->needs_payment()) {
            wp_enqueue_script('angelleye-paypal-checkout-sdk');
            if ($is_shortcode === 'yes') {
                echo '<div class="angelleye_ppcp_smart_button_shortcode angelleye_ppcp_cart_page"><div class="angelleye_ppcp-button-container angelleye_ppcp_' . $this->style_layout . '_' . $this->style_size . '"><div id="angelleye_ppcp_cart_shortcode"></div></div></div>';
            } else {
                echo '<div class="angelleye_ppcp-button-container angelleye_ppcp_' . $this->style_layout . '_' . $this->style_size . '"><div id="angelleye_ppcp_cart"></div><div class="angelleye_ppcp-proceed-to-checkout-button-separator">&mdash; ' . __('OR', 'paypal-for-woocommerce') . ' &mdash;</div></div>';
            }
        }
    }

    public function display_paypal_button_cart_page_top() {
        if (class_exists('WC_Subscriptions_Cart') && WC_Subscriptions_Cart::cart_contains_subscription()) {
            return false;
        }
        $this->angelleye_ppcp_smart_button_style_properties();
        wp_enqueue_script($this->angelleye_ppcp_plugin_name);
        if (WC()->cart->needs_payment()) {
            wp_enqueue_script('angelleye-paypal-checkout-sdk');
            echo '<div class="angelleye_ppcp-button-container angelleye_ppcp_' . $this->style_layout . '_' . $this->style_size . '"><div id="angelleye_ppcp_cart_top"></div></div>';
        }
    }

    public function display_paypal_button_top_checkout_page() {
        if (class_exists('WC_Subscriptions_Cart') && WC_Subscriptions_Cart::cart_contains_subscription()) {
            return false;
        }
        if (angelleye_ppcp_has_active_session() === false) {
            $this->angelleye_ppcp_smart_button_style_properties();
            wp_enqueue_script($this->angelleye_ppcp_plugin_name);
            if (WC()->cart->needs_payment()) {
                wp_enqueue_script('angelleye-paypal-checkout-sdk');
                echo '<div class="angelleye_ppcp_' . $this->style_layout . '_' . $this->style_size . '"><div id="angelleye_ppcp_checkout_top"></div></div><div class="angelleye_ppcp_checkout_message_guide">Skip the forms and pay faster with PayPal!</div>';
            }
        }
    }

    public function display_paypal_button_product_page($is_shortcode = '') {
        try {
            global $product;
            $this->angelleye_ppcp_smart_button_style_properties();
            if (AngellEYE_Utility::is_cart_contains_subscription() == true) {
                return false;
            }
            if (angelleye_ppcp_is_product_purchasable($product) === true) {
                wp_enqueue_script('angelleye-paypal-checkout-sdk');
                wp_enqueue_script($this->angelleye_ppcp_plugin_name);
                if ($is_shortcode === 'yes') {
                    echo '<div class="angelleye_ppcp_smart_button_shortcode angelleye_ppcp_product_page"><div class="angelleye_ppcp-button-container angelleye_ppcp_' . $this->style_layout . '_' . $this->style_size . '"><div id="angelleye_ppcp_product_shortcode"></div></div></div>';
                } else {
                    echo '<div class="angelleye_ppcp-button-container angelleye_ppcp_' . $this->style_layout . '_' . $this->style_size . '"><div id="angelleye_ppcp_product"></div></div>';
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function display_paypal_button_checkout_page($is_shortcode = '') {
        if (angelleye_ppcp_has_active_session() === false) {
            $this->angelleye_ppcp_smart_button_style_properties();
            wp_enqueue_script('angelleye-paypal-checkout-sdk');
            wp_enqueue_script($this->angelleye_ppcp_plugin_name);
            $separator = '';
            if ($this->enable_separate_payment_method === false) {
                $separator = '<div class="angelleye_ppcp-proceed-to-checkout-button-separator checkout_cc_separator" style="display:none;">&mdash;&mdash; ' . __('OR', 'paypal-for-woocommerce') . ' &mdash;&mdash;</div>';
            }
            if ($is_shortcode === 'yes') {
                echo '<div class="angelleye_ppcp_smart_button_shortcode angelleye_ppcp_checkout_page"><div class="angelleye_ppcp-button-container angelleye_ppcp_' . $this->style_layout . '_' . $this->style_size . '"><div id="angelleye_ppcp_checkout"></div>' . $separator . '</div></div>';
            } else {
                echo '<div class="angelleye_ppcp-button-container angelleye_ppcp_' . $this->style_layout . '_' . $this->style_size . '"><div id="angelleye_ppcp_checkout"></div>' . $separator . '</div>';
            }
        }
    }

    public function angelleye_ppcp_endpoint_page_titles($title) {
        if (!is_admin() && is_main_query() && in_the_loop() && is_page() && is_checkout() && !empty($this->checkout_details)) {
            $title = $this->order_review_page_title;
            remove_filter('the_title', array($this, 'angelleye_ppcp_endpoint_page_titles'));
        }
        return $title;
    }

    public function angelleye_ppcp_checkout_init($checkout) {
        if (empty($this->checkout_details)) {
            return;
        }
        add_action('woocommerce_checkout_billing', array($this, 'paypal_billing_details'), 9);
        if (true === WC()->cart->needs_shipping_address()) {
            add_action('woocommerce_checkout_shipping', array($this, 'paypal_shipping_details'), 9);
        }
    }

    public function paypal_billing_details() {
        if (empty($this->checkout_details)) {
            return false;
        }
        $billing_edit_link = "&nbsp;&nbsp;&nbsp;<a class='angelleye_ppcp_edit_billing_address'>" . __('Edit', 'paypal-for-woocommerce') . "</a>";
        ?>
        <div class="angelleye_ppcp_billing_details">
            <?php if (wc_ship_to_billing_address_only() && WC()->cart->needs_shipping()) { ?>
                <h3><?php esc_html_e('Billing &amp; Shipping', 'paypal-for-woocommerce'); ?> <?php echo $billing_edit_link; ?></h3>
            <?php } else { ?>
                <h3>
                    <?php
                    esc_html_e('Billing details', 'paypal-for-woocommerce');
                    if ($this->set_billing_address) {
                        echo $billing_edit_link;
                    }
                    ?>
                </h3>
                <?php
            }
            $checkout_details = angelleye_ppcp_get_mapped_billing_address($this->checkout_details, ($this->set_billing_address) ? false : true);
            echo WC()->countries->get_formatted_address($checkout_details);
            echo!empty($checkout_details['email']) ? '<p class="angelleye-woocommerce-customer-details-email">' . $checkout_details['email'] . '</p>' : '';
            echo!empty($checkout_details['phone']) ? '<p class="angelleye-woocommerce-customer-details-phone">' . $checkout_details['phone'] . '</p>' : '';
            ?>
        </div>
        <?php
    }

    public function paypal_shipping_details() {
        if (empty($this->checkout_details)) {
            return false;
        }
        ?>
        <div class="angelleye_ppcp_shipping_details">
            <h3><?php _e('Shipping details', 'paypal-for-woocommerce'); ?>&nbsp;&nbsp;&nbsp;<a class="angelleye_ppcp_edit_shipping_address"><?php _e('Edit', 'paypal-for-woocommerce'); ?></a></h3>
            <?php echo WC()->countries->get_formatted_address(angelleye_ppcp_get_mapped_shipping_address($this->checkout_details)); ?>
        </div>
        <?php
    }

    public function account_registration() {
        $checkout = WC()->checkout();
        if (!is_user_logged_in() && $checkout->enable_signup) {
            if ($checkout->enable_guest_checkout) {
                ?>
                <p class="form-row form-row-wide create-account">
                    <input class="input-checkbox" id="createaccount" <?php checked(( true === $checkout->get_value('createaccount') || ( true === apply_filters('woocommerce_create_account_default_checked', false) )), true) ?> type="checkbox" name="createaccount" value="1" /> <label for="createaccount" class="checkbox"><?php _e('Create an account?', 'paypal-for-woocommerce'); ?></label>
                </p>
                <?php
            }
            if (!empty($checkout->checkout_fields['account'])) {
                ?>
                <div class="create-account">
                    <p><?php _e('Create an account by entering the information below. If you are a returning customer please login at the top of the page.', 'paypal-for-woocommerce'); ?></p>
                    <?php foreach ($checkout->checkout_fields['account'] as $key => $field) : ?>
                        <?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
                    <?php endforeach; ?>
                    <div class="clear"></div>
                </div>
                <?php
            }
        }
    }

    public function maybe_disable_other_gateways($gateways) {
        if (empty($this->checkout_details) || (isset($_GET['from']) && 'checkout' === $_GET['from'])) {
            return $gateways;
        }
        foreach ($gateways as $id => $gateway) {
            if ('angelleye_ppcp' !== $id) {
                unset($gateways[$id]);
            }
        }
        if (is_cart() || ( is_checkout() && !is_checkout_pay_page() )) {
            if (isset($gateways['angelleye_ppcp']) && ( 0 >= WC()->cart->total )) {
                unset($gateways['angelleye_ppcp']);
            }
        }
        return $gateways;
    }

    public function filter_billing_fields($billing_fields) {
        if (empty($this->checkout_details) || (isset($_GET['from']) && 'checkout' === $_GET['from'])) {
            return $billing_fields;
        }
        if ($this->enabled === false) {
            return $billing_fields;
        }
        return $billing_fields;
    }

    public function filter_default_address_fields($fields) {
        if (empty($this->checkout_details)) {
            return $fields;
        }
        if ($this->enabled === false) {
            return $fields;
        }
        if (method_exists(WC()->cart, 'needs_shipping') && !WC()->cart->needs_shipping()) {
            $not_required_fields = array('first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'postcode', 'country');
            foreach ($not_required_fields as $not_required_field) {
                if (array_key_exists($not_required_field, $fields)) {
                    $fields[$not_required_field]['required'] = false;
                }
            }
        }
        if (array_key_exists('state', $fields)) {
            $fields['state']['required'] = false;
        }
        return $fields;
    }

    public function copy_checkout_details_to_post() {
        if (empty($this->checkout_details)) {
            $this->checkout_details = angelleye_ppcp_get_session('angelleye_ppcp_paypal_transaction_details', false);
            if (empty($this->checkout_details)) {
                if (!empty($_GET['paypal_order_id'])) {
                    $this->checkout_details = $this->payment_request->angelleye_ppcp_get_checkout_details($_GET['paypal_order_id']);
                }
            }
            if (empty($this->checkout_details)) {
                return;
            }
            angelleye_ppcp_set_session('angelleye_ppcp_paypal_transaction_details', $this->checkout_details);
        }
        if (!isset($_POST['payment_method']) || ( 'angelleye_ppcp' !== $_POST['payment_method'] ) || empty($this->checkout_details)) {
            return;
        }
        $shipping_details = angelleye_ppcp_get_mapped_shipping_address($this->checkout_details);
        $billing_details = angelleye_ppcp_get_mapped_billing_address($this->checkout_details, ($this->set_billing_address) ? false : true);
        angelleye_ppcp_update_customer_addresses_from_paypal($shipping_details, $billing_details);
    }

    public function maybe_add_shipping_information($packages) {
        if (empty($this->checkout_details) || (isset($_GET['from']) && 'checkout' === $_GET['from'])) {
            return $packages;
        }
        $destination = angelleye_ppcp_get_mapped_shipping_address($this->checkout_details);
        if (!empty($destination)) {
            $packages[0]['destination']['country'] = $destination['country'];
            $packages[0]['destination']['state'] = $destination['state'];
            $packages[0]['destination']['postcode'] = $destination['postcode'];
            $packages[0]['destination']['city'] = $destination['city'];
            $packages[0]['destination']['address'] = $destination['address_1'];
            $packages[0]['destination']['address_2'] = $destination['address_2'];
        }
        return $packages;
    }

    public function init() {
        if (version_compare(WC_VERSION, '3.3', '<')) {
            add_filter('wc_checkout_params', array($this, 'filter_wc_checkout_params'), 10, 1);
        } else {
            add_filter('woocommerce_get_script_data', array($this, 'filter_wc_checkout_params'), 10, 2);
        }
    }

    public function filter_wc_checkout_params($params, $handle = '') {
        if ('wc-checkout' !== $handle && !doing_action('wc_checkout_params')) {
            return $params;
        }
        $fields = array('paypal_order_id', 'paypal_payer_id');
        $params['wc_ajax_url'] = remove_query_arg('wc-ajax', $params['wc_ajax_url']);
        foreach ($fields as $field) {
            if (!empty($_GET[$field])) {
                $params['wc_ajax_url'] = add_query_arg($field, $_GET[$field], $params['wc_ajax_url']);
            }
        }
        $params['wc_ajax_url'] = add_query_arg('wc-ajax', '%%endpoint%%', $params['wc_ajax_url']);
        return $params;
    }

    public function angelleye_ppcp_session_manager() {
        try {
            if (!empty($_GET['paypal_order_id']) && !empty($_GET['paypal_payer_id'])) {
                if (isset($_GET['from']) && 'product' === $_GET['from']) {
                    if (function_exists('wc_clear_notices')) {
                        wc_clear_notices();
                    }
                }
                angelleye_ppcp_set_session('angelleye_ppcp_paypal_order_id', wc_clean($_GET['paypal_order_id']));
                if (empty($this->checkout_details)) {
                    $this->checkout_details = angelleye_ppcp_get_session('angelleye_ppcp_paypal_transaction_details', false);
                    if ($this->checkout_details === false) {
                        $this->checkout_details = $this->payment_request->angelleye_ppcp_get_checkout_details($_GET['paypal_order_id']);
                        angelleye_ppcp_set_session('angelleye_ppcp_paypal_transaction_details', $this->checkout_details);
                    }
                }
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function maybe_clear_session_data() {
        try {
            unset(WC()->session->angelleye_ppcp_session);
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_add_class_order_review_page($classes) {
        try {
            if (!class_exists('WooCommerce') || WC()->session == null) {
                return $classes;
            }
            if (angelleye_ppcp_has_active_session()) {
                $classes[] = 'angelleye_ppcp-order-review';
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
            return $classes;
        }
        return $classes;
    }

    public function angelleye_ppcp_woocommerce_coupons_enabled($bool) {
        if (angelleye_ppcp_has_active_session()) {
            return $this->order_review_page_enable_coupons;
        }
        return $bool;
    }

    public function angelleye_ppcp_order_review_page_description() {
        if ($this->order_review_page_description && angelleye_ppcp_has_active_session()) {
            ?>
            <div class="order_review_page_description">
                <p>
                    <?php
                    echo wp_kses_post($this->order_review_page_description);
                    ?>
                </p>
            </div>
            <?php
        }
    }

    public function angelleye_ppcp_clean_url($tag, $handle) {
        if ('angelleye-paypal-checkout-sdk' === $handle) {
            $client_token = '';
            if ((is_checkout() || is_checkout_pay_page()) && $this->advanced_card_payments) {
                $client_token = "data-client-token='{$this->client_token}'";
            }
            $tag = str_replace(' src=', ' ' . $client_token . ' data-namespace="angelleye_paypal_sdk" src=', $tag);
        }
        return $tag;
    }

    public function angelleye_ppcp_update_checkout_field_details() {
        if (empty($this->checkout_details)) {
            $this->checkout_details = angelleye_ppcp_get_session('angelleye_ppcp_paypal_transaction_details', false);
            if (empty($this->checkout_details)) {
                if (!empty($_GET['paypal_order_id'])) {
                    $this->checkout_details = $this->payment_request->angelleye_ppcp_get_checkout_details($_GET['paypal_order_id']);
                }
            }
            if (empty($this->checkout_details)) {
                return;
            }
            angelleye_ppcp_set_session('angelleye_ppcp_paypal_transaction_details', $this->checkout_details);
        }
        $states_list = WC()->countries->get_states();
        if (!empty($this->checkout_details)) {
            $shipping_address = angelleye_ppcp_get_mapped_shipping_address($this->checkout_details);
            if (!empty($shipping_address)) {
                foreach ($shipping_address as $field => $value) {
                    if (!empty($value)) {
                        if ('state' == $field) {
                            if (angelleye_ppcp_validate_checkout($shipping_address['country'], $value, 'shipping')) {
                                $_POST['shipping_' . $field] = angelleye_ppcp_validate_checkout($shipping_address['country'], $value, 'shipping');
                            } else {
                                if (isset($shipping_address['country']) && isset($states_list[$shipping_address['country']])) {
                                    $state_key = array_search($value, $states_list[$shipping_address['country']]);
                                    $_POST['shipping_' . $field] = $state_key;
                                } else {
                                    $_POST['shipping_' . $field] = '';
                                }
                            }
                        } else {
                            $_POST['shipping_' . $field] = wc_clean(stripslashes($value));
                        }
                    }
                }
            }
            $billing_address = angelleye_ppcp_get_mapped_billing_address($this->checkout_details, ($this->set_billing_address) ? false : true);
            if (!empty($billing_address)) {
                foreach ($billing_address as $field => $value) {
                    if (!empty($value)) {
                        if ('state' == $field) {
                            if (!empty($shipping_address['country'])) {
                                if (angelleye_ppcp_validate_checkout($shipping_address['country'], $value, 'shipping')) {
                                    $_POST['billing_' . $field] = angelleye_ppcp_validate_checkout($shipping_address['country'], $value, 'shipping');
                                } else {
                                    if (isset($shipping_address['country']) && isset($states_list[$shipping_address['country']])) {
                                        $state_key = array_search($value, $states_list[$shipping_address['country']]);
                                        $_POST['billing_' . $field] = $state_key;
                                    } else {
                                        $_POST['billing_' . $field] = '';
                                    }
                                }
                            }
                        } else {
                            $_POST['billing_' . $field] = wc_clean(stripslashes($value));
                        }
                    }
                }
            }
        }
    }

    public function angelleye_ppcp_cancel_button() {
        if (angelleye_ppcp_has_active_session()) {
            $order_button_text = __('Cancel order', 'paypal-for-woocommerce');
            $cancel_order_url = add_query_arg(array('angelleye_ppcp_action' => 'cancel_order', 'utm_nooverride' => '1', 'from' => 'checkout'), untrailingslashit(WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action')));
            echo apply_filters('angelleye_ppcp_review_order_cance_button_html', '<a class="button alt angelleye_ppcp_cancel" name="woocommerce_checkout_cancel_order" href="' . esc_attr($cancel_order_url) . '" >' . $order_button_text . '</a>');
        }
    }

    public function angelleye_ppcp_exclude_javascript($excluded_handles) {
        $excluded_handles[] = 'jquery-core';
        $excluded_handles[] = 'angelleye_ppcp';
        $excluded_handles[] = 'angelleye-paypal-checkout-sdk';
        $excluded_handles[] = 'angelleye_ppcp-order-review';
        $excluded_handles[] = 'angelleye_ppcp-order-capture';
        $excluded_handles[] = 'angelleye-pay-later-messaging-home';
        $excluded_handles[] = 'angelleye-pay-later-messaging-category';
        $excluded_handles[] = 'angelleye-pay-later-messaging-product';
        $excluded_handles[] = 'angelleye-pay-later-messaging-cart';
        $excluded_handles[] = 'angelleye-pay-later-messaging-payment';
        $excluded_handles[] = 'angelleye-pay-later-messaging-shortcode';
        return $excluded_handles;
    }

    public function angelleye_ppcp_add_order_id() {
        global $wp;
        $order_id = absint($wp->query_vars['order-pay']);
        ?>
        <input type="hidden" name="woo_order_id" value="<?php echo $order_id; ?>" />
        <?php
    }

    public function angelleye_ppcp_hide_show_gateway($methods) {
        if ($this->enable_separate_payment_method) {
            if ((isset($_GET['page']) && 'wc-settings' === $_GET['page'])) {
                
            } else {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-cc-angelleye.php');
                $methods[] = 'WC_Gateway_CC_AngellEYE';
            }
        }
        return $methods;
    }

    public function angelleye_ppcp_short_gateway($methods) {
        if (!empty($this->checkout_details)) {
            return $methods;
        }
        $new_method = array();
        $angelleye_ppcp_cc = array();
        if ($this->enable_paypal_checkout_page === false || $this->checkout_page_display_option === 'top') {
            if (isset($methods['angelleye_ppcp'])) {
                unset($methods['angelleye_ppcp']);
            }
        }
        if ($this->enable_separate_payment_method) {
            if (isset($methods['angelleye_ppcp_cc'])) {
                $angelleye_ppcp_cc = $methods['angelleye_ppcp_cc'];
                if (count($methods) > 1) {
                    unset($methods['angelleye_ppcp_cc']);
                }
            }
            foreach ($methods as $key => $method) {
                if ($key === 'angelleye_ppcp') {
                    if ($this->advanced_card_payments_display_position === 'after') {
                        $new_method ['angelleye_ppcp'] = $methods['angelleye_ppcp'];
                        $new_method ['angelleye_ppcp_cc'] = $angelleye_ppcp_cc;
                    } else {
                        $new_method ['angelleye_ppcp_cc'] = $angelleye_ppcp_cc;
                        $new_method ['angelleye_ppcp'] = $methods['angelleye_ppcp'];
                    }
                } else {
                    $new_method [$key] = $method;
                }
            }
            return $new_method;
        }
        return $methods;
    }

    public function angelleye_ppcp_woocommerce_checkout_fields($fields) {
        if ($this->set_billing_address === false) {
            if (empty($this->checkout_details)) {
                $this->checkout_details = angelleye_ppcp_get_session('angelleye_ppcp_paypal_transaction_details', false);
            }
            if (!empty($this->checkout_details)) {
                if (!empty($fields['billing'])) {
                    foreach ($fields['billing'] as $key => $value) {
                        if (!in_array($key, apply_filters('angelleye_required_billing_fields', array('billing_first_name', 'billing_last_name', 'billing_email')))) {
                            unset($fields['billing'][$key]);
                        }
                    }
                }
            }
        }

        $states_list = WC()->countries->get_states();
        if (!empty($this->checkout_details)) {
            $shipping_address = angelleye_ppcp_get_mapped_shipping_address($this->checkout_details);
            if (!empty($shipping_address) && !empty($fields['shipping'])) {
                foreach ($fields['shipping'] as $field => $value) {
                    $address_key = str_replace('shipping_', '', $field);
                    if ($value['required'] === true && array_key_exists($address_key, $shipping_address) && empty($shipping_address[$address_key])) {
                        
                    } else {
                        $fields['shipping'][$field]['class'][0] = $fields['shipping'][$field]['class'][0] . ' angelleye_ppcp_shipping_hide';
                    }
                }
            }
            $billing_address = angelleye_ppcp_get_mapped_billing_address($this->checkout_details, ($this->set_billing_address) ? false : true);
            if (!empty($billing_address) && !empty($fields['billing'])) {
                foreach ($fields['billing'] as $field => $value) {
                    $address_key = str_replace('billing_', '', $field);
                    if ($value['required'] === true && array_key_exists($address_key, $billing_address) && empty($billing_address[$address_key])) {
                        
                    } else {
                        $fields['billing'][$field]['class'][0] = $fields['billing'][$field]['class'][0] . ' angelleye_ppcp_billing_hide';
                    }
                }
            }
        }


        return $fields;
    }

    public function angelleye_ppcp_prepare_order_data() {
        if (empty($this->checkout_details)) {
            $this->checkout_details = angelleye_ppcp_get_session('angelleye_ppcp_paypal_transaction_details', false);
            if (empty($this->checkout_details)) {
                $angelleye_ppcp_paypal_order_id = angelleye_ppcp_get_session('angelleye_ppcp_paypal_order_id');
                if (!empty($angelleye_ppcp_paypal_order_id)) {
                    $this->checkout_details = $this->payment_request->angelleye_ppcp_get_checkout_details($angelleye_ppcp_paypal_order_id);
                }
            }
            if (empty($this->checkout_details)) {
                return;
            }
            angelleye_ppcp_set_session('angelleye_ppcp_paypal_transaction_details', $this->checkout_details);
        }
        $shipping_address = angelleye_ppcp_get_mapped_shipping_address($this->checkout_details);
        $billing_address = angelleye_ppcp_get_mapped_billing_address($this->checkout_details, ($this->set_billing_address) ? false : true);
        $order_data['terms'] = 1;
        $order_data['createaccount'] = 0;
        $order_data['payment_method'] = 'angelleye_ppcp';
        $order_data['ship_to_different_address'] = false;
        $order_data['order_comments'] = '';
        $order_data['shipping_method'] = '';
        $order_data['billing_first_name'] = isset($billing_address['first_name']) ? $billing_address['first_name'] : '';
        $order_data['billing_last_name'] = isset($billing_address['last_name']) ? $billing_address['last_name'] : '';
        $order_data['billing_email'] = isset($billing_address['email']) ? $billing_address['email'] : '';
        $order_data['billing_company'] = isset($billing_address['company']) ? $billing_address['company'] : '';
        if ($this->set_billing_address) {
            $order_data['billing_address_1'] = isset($billing_address['address_1']) ? $billing_address['address_1'] : '';
            $order_data['billing_address_2'] = isset($billing_address['address_2']) ? $billing_address['address_2'] : '';
            $order_data['billing_city'] = isset($billing_address['city']) ? $billing_address['city'] : '';
            $order_data['billing_state'] = isset($billing_address['state']) ? $billing_address['state'] : '';
            $order_data['billing_postcode'] = isset($billing_address['postcode']) ? $billing_address['postcode'] : '';
            $order_data['billing_phone'] = isset($billing_address['phone']) ? $billing_address['phone'] : '';
        }
        $order_data['shipping_first_name'] = isset($shipping_address['first_name']) ? $shipping_address['first_name'] : '';
        $order_data['shipping_last_name'] = isset($shipping_address['last_name']) ? $shipping_address['last_name'] : '';
        $order_data['shipping_company'] = isset($shipping_address['company']) ? $shipping_address['company'] : '';
        $order_data['shipping_country'] = isset($shipping_address['country']) ? $shipping_address['country'] : '';
        $order_data['shipping_address_1'] = isset($shipping_address['address_1']) ? $shipping_address['address_1'] : '';
        $order_data['shipping_address_2'] = isset($shipping_address['address_2']) ? $shipping_address['address_2'] : '';
        $order_data['shipping_city'] = isset($shipping_address['city']) ? $shipping_address['city'] : '';
        $order_data['shipping_state'] = isset($shipping_address['state']) ? $shipping_address['state'] : '';
        $order_data['shipping_postcode'] = isset($shipping_address['postcode']) ? $shipping_address['postcode'] : '';
        return $order_data;
    }

    public function angelleye_ppcp_is_skip_final_review() {
        if (is_checkout() || is_checkout_pay_page()) {
            return apply_filters('angelleye_ppcp_skip_final_review', true);
        }
        $this->enable_guest_checkout = get_option('woocommerce_enable_guest_checkout') == 'yes' ? true : false;
        $this->must_create_account = $this->enable_guest_checkout || is_user_logged_in() ? false : true;
        $force_to_skip_final_review = true;
        if ($this->skip_final_review === false) {
            return apply_filters('angelleye_ppcp_skip_final_review', false);
        }
        if ($this->must_create_account === true) {
            return apply_filters('angelleye_ppcp_skip_final_review', false);
        }
        $angelleye_ppcp_checkout_post = angelleye_get_session('angelleye_ppcp_checkout_post');
        if (apply_filters('woocommerce_checkout_show_terms', true) && function_exists('wc_terms_and_conditions_checkbox_enabled') && wc_terms_and_conditions_checkbox_enabled()) {
            if ($this->disable_term) {
                return apply_filters('angelleye_ppcp_skip_final_review', true);
            } elseif ((isset($angelleye_ppcp_checkout_post['terms']) || isset($angelleye_ppcp_checkout_post['legal'])) && $angelleye_ppcp_checkout_post['terms'] == 'on') {
                return apply_filters('angelleye_ppcp_skip_final_review', true);
            }
        }
        if ($this->skip_final_review == 'yes') {
            return apply_filters('angelleye_ppcp_skip_final_review', true);
        }
        return apply_filters('angelleye_ppcp_skip_final_review', $force_to_skip_final_review);
    }

    public function angelleye_ppcp_display_payment_method_title_review_page() {
        $angelleye_ppcp_payment_method_title = angelleye_ppcp_get_session('angelleye_ppcp_payment_method_title');
        if (!empty($angelleye_ppcp_payment_method_title)) {
            ?>
            <tr>
                <th><?php esc_html_e('Payment method:', 'paypal-for-woocommerce'); ?></th>
                <td><strong><?php echo wp_kses_post($angelleye_ppcp_payment_method_title); ?></strong></td>
            </tr>
            <?php
        }
    }

    public function angelleye_ppcp_prevent_add_to_cart_woo_action() {
        if (isset($_REQUEST['angelleye_ppcp-add-to-cart'])) {
            if (isset($_REQUEST['add-to-cart'])) {
                unset($_REQUEST['add-to-cart']);
                unset($_POST['add-to-cart']);
            }
        }
    }

    public function angelleye_ppcp_woocommerce_before_checkout_process() {
        if (isset($_POST['_wcf_checkout_id']) && isset($_POST['_wcf_flow_id'])) {
            //$_GET['wc-ajax'] = 'checkout';
            $_GET['wcf_checkout_id'] = $_POST['_wcf_checkout_id'];
            wc_maybe_define_constant('DOING_AJAX', true);
            wc_maybe_define_constant('WC_DOING_AJAX', true);
        }
    }

    public function angelleye_ppcp_admin_notices() {
        try {
            if (($this->is_sandbox === true && $this->is_sandbox_first_party_used) || ($this->is_sandbox === false && $this->is_live_first_party_used)) {
                // echo '<div class="error angelleye-notice" style="display:none;"><div class="angelleye-notice-logo"><span></span></div><div class="angelleye-notice-message">' . sprintf(__('PayPal is requiring that users of our plugin onboard into our app instead of using their own PayPal App credentials.  Please make this change by December 31st, 2022 in order to continue using our plugin.  %s', 'paypal-for-woocommerce'), '<a target="_blank" href="https://www.angelleye.com/paypal-for-woocommerce-onboarding-requirement/">Learn More</a>')  . '</div><div class="angelleye-notice-cta"><button class="angelleye-notice-dismiss">Dismiss</button></div></div>';
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_paymentaction_filter($paymentaction, $order_id) {
        try {
            if ($order_id !== null) {
                $order = wc_get_order($order_id);
                if ($order) {
                    $old_wc = version_compare(WC_VERSION, '3.0', '<');
                    $paymentaction_val = angelleye_ppcp_get_post_meta($order, '_paymentaction');
                    if (!empty($paymentaction_val)) {
                        return $paymentaction_val;
                    }
                }
            }
            if (is_null(WC()->cart) || WC()->cart->is_empty()) {
                return $paymentaction;
            } else {
                $payment_action = array();
                foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                    $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
                    $is_enable_payment_action = get_post_meta($product_id, 'enable_payment_action', true);
                    if ($is_enable_payment_action === 'yes') {
                        $woo_product_payment_action = get_post_meta($product_id, 'woo_product_payment_action', true);
                        if (!empty($woo_product_payment_action)) {
                            if ($woo_product_payment_action === 'Authorization') {
                                $payment_action['authorize'] = 'authorize';
                            } elseif ($woo_product_payment_action === 'Sale') {
                                $payment_action['capture'] = 'capture';
                            }
                        }
                    }
                }
                if (isset($payment_action['authorize'])) {
                    return $payment_action['authorize'];
                } elseif (isset($payment_action['capture'])) {
                    return $payment_action['capture'];
                } else {
                    return $paymentaction;
                }
            }
        } catch (Exception $ex) {
            return $paymentaction;
        }
    }

    public function angelleye_ppcp_paymentaction_product_page_filter($paymentaction, $product_id) {
        try {
            $is_enable_payment_action = get_post_meta($product_id, 'enable_payment_action', true);
            if ($is_enable_payment_action === 'yes') {
                $woo_product_payment_action = get_post_meta($product_id, 'woo_product_payment_action', true);
                if (!empty($woo_product_payment_action)) {
                    if ($woo_product_payment_action === 'Authorization') {
                        $paymentaction = 'authorize';
                        return $paymentaction;
                    } elseif ($woo_product_payment_action === 'Sale') {
                        $paymentaction = 'capture';
                        return $paymentaction;
                    }
                }
            }
            return $paymentaction;
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_display_paypal_smart_button_using_shortcode() {
        try {
            if (is_product()) {
                $this->display_paypal_button_product_page($is_shortcode = 'yes');
            } elseif (is_cart() && !WC()->cart->is_empty()) {
                $this->display_paypal_button_cart_page($is_shortcode = 'yes');
            } elseif (is_checkout_pay_page()) {
                $this->display_paypal_button_checkout_page($is_shortcode = 'yes');
            } elseif (is_checkout()) {
                $this->display_paypal_button_checkout_page($is_shortcode = 'yes');
            }
        } catch (Exception $ex) {
            
        }
    }

}
