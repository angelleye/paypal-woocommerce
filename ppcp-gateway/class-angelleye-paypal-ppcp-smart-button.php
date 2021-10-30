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
        $this->is_sandbox = 'yes' === $this->settings->get('testmode', 'no');
        $this->order_review_page_enable_coupons = 'yes' === $this->settings->get('order_review_page_enable_coupons', 'yes');
        $this->order_review_page_title = apply_filters('angelleye_ppcp_order_review_page_title', __('Confirm Your PayPal Order', 'paypal-for-woocommerce'));
        $this->order_review_page_description = apply_filters('angelleye_ppcp_order_review_page_description', __("<strong>You're almost done!</strong><br>Review your information before you place your order.", 'paypal-for-woocommerce'));
        $this->paymentaction = $this->settings->get('paymentaction', 'capture');
        $this->advanced_card_payments = 'yes' === $this->settings->get('enable_advanced_card_payments', 'no');
        $this->enabled_pay_later_messaging = 'yes' === $this->settings->get('enabled_pay_later_messaging', 'yes');
        $this->pay_later_messaging_page_type = $this->settings->get('pay_later_messaging_page_type', array('product', 'cart', 'payment'));
        $this->set_billing_address = 'yes' === $this->settings->get('set_billing_address', 'no');
        $this->disable_term = 'yes' === $this->settings->get('disable_term', 'no');
        $this->skip_final_review = 'yes' === $this->settings->get('skip_final_review', 'no');
        if ($this->is_sandbox) {
            $this->merchant_id = $this->settings->get('sandbox_merchant_id', '');
        } else {
            $this->merchant_id = $this->settings->get('live_merchant_id', '');
        }
        if (empty($this->pay_later_messaging_page_type)) {
            $this->enabled_pay_later_messaging = false;
        }
        if ($this->dcc_applies->for_country_currency() === false) {
            $this->advanced_card_payments = false;
        }
        if ($this->advanced_card_payments) {
            $this->threed_secure_enabled = 'yes' === $this->settings->get('threed_secure_enabled', 'no');
        } else {
            $this->threed_secure_enabled = false;
        }
    }

    public function angelleye_ppcp_default_set_properties() {
        $this->angelleye_ppcp_currency_list = array('AUD', 'BRL', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'INR', 'ILS', 'JPY', 'MYR', 'MXN', 'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'THB', 'USD');
        $this->angelleye_ppcp_currency = in_array(get_woocommerce_currency(), $this->angelleye_ppcp_currency_list) ? get_woocommerce_currency() : 'USD';
        $this->enable_product_button = 'yes' === $this->settings->get('enable_product_button', 'yes');
        $this->enable_cart_button = 'yes' === $this->settings->get('enable_cart_button', 'yes');
        $this->enable_checkout_button = 'yes' === $this->settings->get('enable_checkout_button', 'yes');
        $this->enable_mini_cart_button = 'yes' === $this->settings->get('enable_mini_cart_button', 'yes');
        $this->AVSCodes = array("A" => "Address Matches Only (No ZIP)",
            "B" => "Address Matches Only (No ZIP)",
            "C" => "This tranaction was declined.",
            "D" => "Address and Postal Code Match",
            "E" => "This transaction was declined.",
            "F" => "Address and Postal Code Match",
            "G" => "Global Unavailable - N/A",
            "I" => "International Unavailable - N/A",
            "N" => "None - Transaction was declined.",
            "P" => "Postal Code Match Only (No Address)",
            "R" => "Retry - N/A",
            "S" => "Service not supported - N/A",
            "U" => "Unavailable - N/A",
            "W" => "Nine-Digit ZIP Code Match (No Address)",
            "X" => "Exact Match - Address and Nine-Digit ZIP",
            "Y" => "Address and five-digit Zip match",
            "Z" => "Five-Digit ZIP Matches (No Address)");

        $this->CVV2Codes = array(
            "E" => "N/A",
            "M" => "Match",
            "N" => "No Match",
            "P" => "Not Processed - N/A",
            "S" => "Service Not Supported - N/A",
            "U" => "Service Unavailable - N/A",
            "X" => "No Response - N/A"
        );
    }

    public function angelleye_ppcp_smart_button_style_properties() {
        $this->disable_funding = array();
        $this->style_layout = $this->settings->get('cart_button_layout', 'vertical');
        $this->style_color = 'gold';
        $this->style_shape = 'rect';
        $this->style_label = 'paypal';
        $this->style_tagline = 'yes';
        if (is_product()) {
            $this->disable_funding = $this->settings->get('product_disallowed_funding_methods', array());
            $this->style_layout = $this->settings->get('product_button_layout', 'horizontal');
            $this->style_color = $this->settings->get('product_style_color', 'gold');
            $this->style_shape = $this->settings->get('product_style_shape', 'rect');
            $this->style_label = $this->settings->get('product_button_label', 'paypal');
            $this->style_tagline = $this->settings->get('product_button_tagline', 'yes');
        } elseif (is_cart()) {
            $this->disable_funding = $this->settings->get('cart_disallowed_funding_methods', array());
            $this->style_layout = $this->settings->get('cart_button_layout', 'vertical');
            $this->style_color = $this->settings->get('cart_style_color', 'gold');
            $this->style_shape = $this->settings->get('cart_style_shape', 'rect');
            $this->style_label = $this->settings->get('cart_button_label', 'paypal');
            $this->style_tagline = $this->settings->get('cart_button_tagline', 'yes');
        } elseif (is_checkout() || is_checkout_pay_page()) {
            $this->disable_funding = $this->settings->get('checkout_disallowed_funding_methods', array());
            $this->style_layout = $this->settings->get('checkout_button_layout', 'vertical');
            $this->style_color = $this->settings->get('checkout_style_color', 'gold');
            $this->style_shape = $this->settings->get('checkout_style_shape', 'rect');
            $this->style_label = $this->settings->get('checkout_button_label', 'paypal');
            $this->style_tagline = $this->settings->get('checkout_button_tagline', 'yes');
        }
        $this->mini_cart_disable_funding = $this->settings->get('mini_cart_disallowed_funding_methods', array());
        $this->mini_cart_style_layout = $this->settings->get('mini_cart_button_layout', 'vertical');
        $this->mini_cart_style_color = $this->settings->get('mini_cart_style_color', 'gold');
        $this->mini_cart_style_shape = $this->settings->get('mini_cart_style_shape', 'rect');
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
            add_action('woocommerce_proceed_to_checkout', array($this, 'display_paypal_button_cart_page'), 11);
        }
        if ($this->enable_checkout_button) {
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
        add_action('woocommerce_review_order_before_submit', array($this, 'angelleye_ppcp_cancel_button'));
        add_filter('sgo_js_minify_exclude', array($this, 'angelleye_ppcp_exclude_javascript'), 999);
        add_filter('sgo_javascript_combine_exclude', array($this, 'angelleye_ppcp_exclude_javascript'), 999);
        add_filter('sgo_javascript_combine_excluded_inline_content', array($this, 'angelleye_ppcp_exclude_javascript'), 999);
        add_filter('sgo_js_async_exclude', array($this, 'angelleye_ppcp_exclude_javascript'), 999);
        add_action('woocommerce_pay_order_after_submit', array($this, 'angelleye_ppcp_add_order_id'));
        add_filter('woocommerce_payment_gateways', array($this, 'angelleye_ppcp_hide_show_gateway'), 9999);
        add_filter('woocommerce_checkout_fields', array($this, 'angelleye_ppcp_woocommerce_checkout_fields'), 999);
        //add_action('http_api_debug', array($this, 'angelleye_ppcp_all_web_request'), 10, 5);
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
        global $post;
        if (is_checkout() && $this->advanced_card_payments) {
            if (!isset($_GET['paypal_order_id']) && !isset($_GET['key'])) {
                $this->client_token = $this->payment_request->angelleye_ppcp_get_generate_token();
            }
        }

        $this->angelleye_ppcp_smart_button_style_properties();
        $smart_js_arg = array();
        $smart_js_arg['currency'] = $this->angelleye_ppcp_currency;
        if (!empty($this->disable_funding) && count($this->disable_funding) > 0) {
            $smart_js_arg['disable-funding'] = implode(',', $this->disable_funding);
        }
        if ($this->is_sandbox ) {
            if(is_user_logged_in() && WC()->customer && WC()->customer->get_billing_country() && 2 === strlen( WC()->customer->get_billing_country() )) {
                $smart_js_arg['buyer-country'] = WC()->customer->get_billing_country();
            }
            $smart_js_arg['client-id'] = PAYPAL_PPCP_SNADBOX_PARTNER_CLIENT_ID;
        } else {
            $smart_js_arg['client-id'] = PAYPAL_PPCP_PARTNER_CLIENT_ID;
        }
        $smart_js_arg['merchant-id'] = $this->merchant_id;
        $is_cart = is_cart() && !WC()->cart->is_empty();
        $is_product = is_product();
        $is_checkout = is_checkout();
        $page = $is_cart ? 'cart' : ( $is_product ? 'product' : ( $is_checkout ? 'checkout' : null ) );
        $is_pay_page = 'no';
        if (is_checkout_pay_page()) {
            $page = 'checkout';
            $is_pay_page = 'yes';
        }
        $smart_js_arg['commit'] = $this->angelleye_ppcp_is_skip_final_review() ? 'true' : 'false';
        $smart_js_arg['intent'] = ( $this->paymentaction === 'capture' ) ? 'capture' : 'authorize';
        $smart_js_arg['locale'] = AngellEYE_Utility::get_button_locale_code();
        $components = array("buttons");
        if (is_checkout() && $this->advanced_card_payments) {
            array_push($components, "hosted-fields");
        }
        if ($this->enabled_pay_later_messaging) {
            array_push($components, 'messages');
        }
        if (!empty($components)) {
            $smart_js_arg['components'] = apply_filters('angelleye_paypal_checkout_sdk_components', implode(',', $components));
        }
        if( isset($post->ID) && 'yes' == get_post_meta( $post->ID, 'wcf-pre-checkout-offer', true ) ) {
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
            'style_label' => $this->style_label,
            'style_layout' => $this->style_layout,
            'style_tagline' => $this->style_tagline,
            'page' => $page,
            'is_pre_checkout_offer' => $pre_checkout_offer,
            'is_pay_page' => $is_pay_page,
            'checkout_url' => add_query_arg(array('utm_nooverride' => '1'), wc_get_checkout_url()),
            'display_order_page' => add_query_arg(array('angelleye_ppcp_action' => 'display_order_page', 'utm_nooverride' => '1'), WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action')),
            'cc_capture' => add_query_arg(array('angelleye_ppcp_action' => 'cc_capture', 'utm_nooverride' => '1'), WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action')),
            'create_order_url' => add_query_arg(array('angelleye_ppcp_action' => 'create_order', 'utm_nooverride' => '1', 'from' => is_checkout_pay_page() ? 'pay_page' : $page), WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action')),
            'cart_total' => WC()->cart->total,
            'paymentaction' => $this->paymentaction,
            'advanced_card_payments' => ($this->advanced_card_payments === true) ? 'yes' : 'no',
            'threed_secure_enabled' => ($this->threed_secure_enabled === true) ? 'yes' : 'no',
            'woocommerce_process_checkout' => wp_create_nonce('woocommerce-process_checkout'),
            'is_skip_final_review' => $this->angelleye_ppcp_is_skip_final_review() ? 'yes' : 'no',
            'direct_capture' => add_query_arg(array('angelleye_ppcp_action' => 'direct_capture', 'utm_nooverride' => '1'), WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action')),
            
                )
        );
        if (is_checkout() && empty($this->checkout_details)) {
            wp_enqueue_script($this->angelleye_ppcp_plugin_name . '-order-review', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/js/wc-gateway-ppcp-angelleye-order-review.js', array('jquery'), $this->version, false);
        } elseif (is_checkout() && !empty($this->checkout_details)) {
            wp_enqueue_script($this->angelleye_ppcp_plugin_name . '-order-capture', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/js/wc-gateway-ppcp-angelleye-order-capture.js', array('jquery'), $this->version, false);
        }
    }

    public function enqueue_styles() {
        wp_enqueue_style($this->angelleye_ppcp_plugin_name, PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/css/wc-gateway-ppcp-angelleye-public.css', array(), $this->version, 'all');
    }

    public function is_valid_for_use() {
        if (!empty($this->merchant_id) && $this->enabled) {
            return true;
        }
        return false;
    }

    public function display_paypal_button_cart_page() {
        wp_enqueue_script($this->angelleye_ppcp_plugin_name);
        if (WC()->cart->needs_payment()) {
            wp_enqueue_script('angelleye-paypal-checkout-sdk');
            echo '<div class="angelleye_ppcp-button-container"><div id="angelleye_ppcp_cart"></div><div class="angelleye_ppcp-proceed-to-checkout-button-separator">&mdash; ' . __('OR', 'paypal-for-woocommerce') . ' &mdash;</div></div>';
        }
    }

    public function display_paypal_button_product_page() {
        global $product;
        if (!is_product() || !$product->is_in_stock() || $product->is_type('external') || $product->is_type('grouped')) {
            return;
        }
        wp_enqueue_script('angelleye-paypal-checkout-sdk');
        wp_enqueue_script($this->angelleye_ppcp_plugin_name);
        echo '<div class="angelleye_ppcp-button-container"><div id="angelleye_ppcp_product"></div></div>';
    }

    public function display_paypal_button_checkout_page() {
        if (angelleye_ppcp_has_active_session() === false) {
            wp_enqueue_script('angelleye-paypal-checkout-sdk');
            wp_enqueue_script($this->angelleye_ppcp_plugin_name);
            $separator = '';
            if (is_checkout_pay_page() === false) {
                $separator = '<div class="angelleye_ppcp-proceed-to-checkout-button-separator checkout_cc_separator" style="display:none;">&mdash;&mdash; ' . __('OR', 'paypal-for-woocommerce') . ' &mdash;&mdash;</div>';
            }
            echo '<div class="angelleye_ppcp-button-container"><div id="angelleye_ppcp_checkout"></div>' . $separator . '</div>';
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
        if ($this->set_billing_address === false) {
            //remove_action('woocommerce_checkout_billing', array($checkout, 'checkout_form_billing'));
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
        if (array_key_exists('billing_phone', $billing_fields)) {
            $billing_fields['billing_phone']['required'] = false;
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
            if (angelleye_ppcp_has_active_session()) {
                unset(WC()->session->angelleye_ppcp_session);
            }
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
            if (is_checkout() && $this->advanced_card_payments) {
                $client_token = "data-client-token='{$this->client_token}'";
            }
            $tag = str_replace(' src=', ' ' . $client_token . ' data-namespace="angelleye_paypal_sdk" src=', $tag);
        }
        return $tag;
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
                            if ($this->validate_checkout($shipping_address['country'], $value, 'shipping')) {
                                $_POST['shipping_' . $field] = $this->validate_checkout($shipping_address['country'], $value, 'shipping');
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
                                if ($this->validate_checkout($shipping_address['country'], $value, 'shipping')) {
                                    $_POST['billing_' . $field] = $this->validate_checkout($shipping_address['country'], $value, 'shipping');
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
            $cancel_order_url = add_query_arg(array('angelleye_ppcp_action' => 'cancel_order', 'utm_nooverride' => '1', 'from' => 'checkout'), WC()->api_request_url('AngellEYE_PayPal_PPCP_Front_Action'));
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
        if ($this->enable_checkout_button === false && $this->advanced_card_payments === false && is_checkout()) {
            foreach ($methods as $key => $method) {
                if ($method === 'WC_Gateway_PPCP_AngellEYE') {
                    unset($methods[$key]);
                    break;
                }
            }
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
        if (is_checkout()) {
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

}
