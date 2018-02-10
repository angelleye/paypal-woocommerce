<?php
if (!defined('ABSPATH')) {
    exit;
}

class Angelleye_PayPal_Express_Checkout_Helper {

    public $setting;
    public $function_helper;
    public $posted;

    public function __construct($version) {
        try {
            global $wpdb;
            $this->version = $version;
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
                $this->save_abandoned_checkout_value = !empty($this->setting['save_abandoned_checkout']) ? $this->setting['save_abandoned_checkout'] : 'no';
                $this->save_abandoned_checkout = 'yes' === $this->save_abandoned_checkout_value;
                $this->checkout_with_pp_button_type = !empty($this->setting['checkout_with_pp_button_type']) ? $this->setting['checkout_with_pp_button_type'] : 'paypalimage';
                $this->pp_button_type_text_button = !empty($this->setting['pp_button_type_text_button']) ? $this->setting['pp_button_type_text_button'] : 'Proceed to Checkout';
                $this->pp_button_type_my_custom = !empty($this->setting['pp_button_type_my_custom']) ? $this->setting['pp_button_type_my_custom'] : WC_Gateway_PayPal_Express_AngellEYE::angelleye_get_paypalimage();
                $this->show_on_product_page = !empty($this->setting['show_on_product_page']) ? $this->setting['show_on_product_page'] : 'no';
                $this->review_title_page = !empty($this->setting['review_title_page']) ? $this->setting['review_title_page'] : 'Review Order';
                $this->show_on_checkout = !empty($this->setting['show_on_checkout']) ? $this->setting['show_on_checkout'] : 'top';
                $this->button_position = !empty($this->setting['button_position']) ? $this->setting['button_position'] : 'bottom';
                $this->show_on_cart = !empty($this->setting['show_on_cart']) ? $this->setting['show_on_cart'] : 'yes';
                $this->show_on_minicart = !empty($this->setting['show_on_minicart']) ? $this->setting['show_on_minicart'] : 'yes';
                $this->prevent_to_add_additional_item_value = !empty($this->setting['prevent_to_add_additional_item']) ? $this->setting['prevent_to_add_additional_item'] : 'no';
                $this->prevent_to_add_additional_item = 'yes' === $this->prevent_to_add_additional_item_value;
                $this->testmode_value = !empty($this->setting['testmode']) ? $this->setting['testmode'] : 'yes';
                $this->testmode = 'yes' === $this->testmode_value;
                $this->billing_address_value = !empty($this->setting['billing_address']) ? $this->setting['billing_address'] : 'no';
                $this->billing_address = 'yes' === $this->billing_address_value;
                $this->cancel_page = !empty($this->setting['cancel_page']) ? $this->setting['cancel_page'] : '';
                $this->use_wp_locale_code = !empty($this->setting['use_wp_locale_code']) ? $this->setting['use_wp_locale_code'] : 'yes';
                $this->paypal_marketing_solutions_enabled = !empty($this->setting['paypal_marketing_solutions_enabled']) ? $this->setting['paypal_marketing_solutions_enabled'] : 'no';
                $this->paypal_marketing_solutions_cid_production = !empty($this->setting['paypal_marketing_solutions_cid_production']) ? $this->setting['paypal_marketing_solutions_cid_production'] : '';
                $this->enable_in_context_checkout_flow = !empty($this->setting['enable_in_context_checkout_flow']) ? $this->setting['enable_in_context_checkout_flow'] : 'no';
                if ($this->testmode == false) {
                    $this->testmode = AngellEYE_Utility::angelleye_paypal_for_woocommerce_is_set_sandbox_product();
                }
                if (substr(get_option("woocommerce_default_country"), 0, 2) == 'US' || substr(get_option("woocommerce_default_country"), 0, 2) == 'GB') {
                    $this->is_us_or_uk = true;
                } else {
                    $this->is_us_or_uk = false;
                }
                $this->show_paypal_credit = !empty($this->setting['show_paypal_credit']) ? $this->setting['show_paypal_credit'] : 'yes';
                if ($this->is_us_or_uk == false) {
                    $this->show_paypal_credit = 'no';
                }
                if ($this->testmode == true) {
                    $this->API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
                    $this->PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
                    $this->api_username = !empty($this->setting['sandbox_api_username']) ? $this->setting['sandbox_api_username'] : '';
                    $this->api_password = !empty($this->setting['sandbox_api_password']) ? $this->setting['sandbox_api_password'] : '';
                    $this->api_signature = !empty($this->setting['sandbox_api_signature']) ? $this->setting['sandbox_api_signature'] : '';
                } else {
                    $this->API_Endpoint = "https://api-3t.paypal.com/nvp";
                    $this->PAYPAL_URL = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
                    $this->api_username = !empty($this->setting['api_username']) ? $this->setting['api_username'] : '';
                    $this->api_password = !empty($this->setting['api_password']) ? $this->setting['api_password'] : '';
                    $this->api_signature = !empty($this->setting['api_signature']) ? $this->setting['api_signature'] : '';
                }
                $this->angelleye_skip_text = !empty($this->setting['angelleye_skip_text']) ? $this->setting['angelleye_skip_text'] : 'Skip the forms and pay faster with PayPal!';
                add_action('woocommerce_after_add_to_cart_button', array($this, 'buy_now_button'), 10);
                if ($this->save_abandoned_checkout == false) {
                    if (version_compare(WC_VERSION, '3.0', '<')) {
                        add_action('woocommerce_after_checkout_validation', array($this, 'angelleye_paypal_express_checkout_redirect_to_paypal'), 99, 1);
                    } else {
                        add_action('woocommerce_after_checkout_validation', array($this, 'angelleye_paypal_express_checkout_redirect_to_paypal'), 99, 2);
                    }
                }
                add_action('woocommerce_add_to_cart_redirect', array($this, 'add_to_cart_redirect'));
                add_action('woocommerce_checkout_billing', array($this, 'ec_set_checkout_post_data'));
                add_action('woocommerce_available_payment_gateways', array($this, 'ec_disable_gateways'));
                add_filter('body_class', array($this, 'ec_add_body_class'));
                add_action('woocommerce_checkout_fields', array($this, 'ec_display_checkout_fields'));
                add_action('woocommerce_before_checkout_billing_form', array($this, 'ec_formatted_billing_address'), 9);
                add_action('woocommerce_before_checkout_shipping_form', array($this, 'angelleye_shipping_sec_title'), 10);
                add_filter('woocommerce_terms_is_checked_default', array($this, 'ec_terms_express_checkout'));
                add_action('woocommerce_cart_emptied', array($this, 'ec_clear_session_data'));
                add_filter('woocommerce_thankyou_order_received_text', array($this, 'ec_order_received_text'), 10, 2);
                add_action('wp_enqueue_scripts', array($this, 'ec_enqueue_scripts_product_page'), 0);
                add_action('woocommerce_before_cart_table', array($this, 'top_cart_button'));
                if ($this->show_on_cart == 'yes' && $this->show_on_minicart == 'yes') {
                    add_action('woocommerce_after_mini_cart', array($this, 'mini_cart_button'));
                }
                add_action('woocommerce_cart_contents', array($this, 'woocommerce_before_cart'), 12);
                add_filter('woocommerce_is_sold_individually', array($this, 'angelleye_woocommerce_is_sold_individually'), 10, 2);
                add_filter('woocommerce_ship_to_different_address_checked', array($this, 'angelleye_ship_to_different_address_checked'), 10, 1);
                add_filter('woocommerce_order_button_html', array($this, 'angelleye_woocommerce_order_button_html'), 10, 1);
                add_filter('woocommerce_coupons_enabled', array($this, 'angelleye_woocommerce_coupons_enabled'), 10, 1);
                add_action('woocommerce_cart_shipping_packages', array($this, 'maybe_add_shipping_information'));
                add_action('admin_notices', array($this, 'angelleye_billing_agreement_notice'));
                add_action('wc_ajax_wc_angeleye_ppec_update_shipping_costs', array($this, 'wc_ajax_update_shipping_costs'));
                add_filter('clean_url', array($this, 'angelleye_in_content_js'));
                add_action('wc_ajax_angelleye_ajax_generate_cart', array($this, 'angelleye_ajax_generate_cart'));
                if (AngellEYE_Utility::is_express_checkout_credentials_is_set()) {
                    if ($this->button_position == 'bottom' || $this->button_position == 'both') {
                        add_action('woocommerce_proceed_to_checkout', array($this, 'woocommerce_paypal_express_checkout_button_angelleye'), 22);
                    }
                }
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
                add_filter('woocommerce_billing_fields', array($this, 'angelleye_optional_billing_fields'), 10, 1);
                add_action('wp_enqueue_scripts', array($this, 'angelleye_paypal_marketing_solutions'), 10);
                add_action('woocommerce_update_options_payment_gateways_paypal_express', array($this, 'angelleye_update_marketing_solution'), 10);
                add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'), 100);
                add_filter('body_class', array($this, 'add_body_classes'));
                $this->is_order_completed = true;
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
                if ($_product->is_type('simple') && (version_compare(WC_VERSION, '3.0', '<') == false)) {
                    ?>
                    <input type="hidden" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" />
                    <?php
                }
                $ec_html_button .= '<div class="angelleye_button_single">';
                $button_dynamic_class = 'single_variation_wrap_angelleye_' . $product->get_id();
                $hide = '';
                if ($_product->is_type('variation') || $_product->is_type('external') || $_product->get_price() == 0 || $_product->get_price() == '') {
                    $hide = 'display:none;';
                }
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
                    $paypal_credit_button_markup = '<a  style="' . $hide . '" class="single_add_to_cart_button paypal_checkout_button paypal_checkout_button_cc" href="' . esc_url(add_query_arg('use_paypal_credit', 'true', add_query_arg('pp_action', 'set_express_checkout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/'))))) . '" >';
                    $paypal_credit_button_markup .= '<img src="https://www.paypalobjects.com/webstatic/en_US/i/buttons/ppcredit-logo-small.png" width="148" height="26" class="ppcreditlogo ec_checkout_page_button_type_pc"  align="top" alt="' . __('Check out with PayPal Credit', 'paypal-for-woocommerce') . '" />';
                    $paypal_credit_button_markup .= '</a>';
                    $ec_html_button .= $paypal_credit_button_markup;
                }
                $ec_html_button .= '</div>';
                if ($this->enable_tokenized_payments == 'yes') {
                    $ec_html_button .= $this->function_helper->angelleye_ec_save_payment_method_checkbox();
                }
                echo apply_filters('angelleye_ec_product_page_buy_now_button', $ec_html_button);
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_paypal_express_checkout_redirect_to_paypal($data, $errors = null) {
        $notice_count = 0;
        if (!empty($errors)) {
            foreach ($errors->get_error_messages() as $message) {
                $notice_count = $notice_count + 1;
            }
        } else {
            $notice_count = wc_notice_count('error');
        }
        if (empty($_POST['woocommerce_checkout_update_totals']) && 0 === $notice_count) {
            try {
                WC()->session->set('post_data', $_POST);
                if (isset($_POST['payment_method']) && 'paypal_express' === $_POST['payment_method'] && $this->function_helper->ec_notice_count('error') == 0) {
                    $this->function_helper->ec_redirect_after_checkout();
                }
            } catch (Exception $ex) {
                
            }
        }
    }

    public function add_to_cart_redirect($url = null) {
        try {
            if (isset($_REQUEST['express_checkout']) || isset($_REQUEST['express_checkout_x'])) {
                wc_clear_notices();
                if (isset($_POST['wc-paypal_express-new-payment-method']) && $_POST['wc-paypal_express-new-payment-method'] = 'on') {
                    WC()->session->set('ec_save_to_account', 'on');
                }
                $url = esc_url_raw(add_query_arg('pp_action', 'set_express_checkout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/'))));
            }
            return $url;
        } catch (Exception $ex) {
            
        }
    }

    public function ec_get_session_data($key = '') {
        try {
            if (!class_exists('WooCommerce') || WC()->session == null) {
                return false;
            }
            $session_data = WC()->session->get('paypal_express_checkout');
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
                        }
                        $_POST['shipping_' . $field] = wc_clean(stripslashes($value));
                    }
                }
            }
            $post_data = WC()->session->get('post_data');
            $_POST['order_comments'] = isset($post_data['order_comments']) ? $post_data['order_comments'] : '';
            if (!empty($post_data)) {
                foreach ($post_data as $key => $value) {
                    if (!empty($value)) {
                        $_POST[$key] = is_string($value) ? wc_clean(stripslashes($value)) : $value;
                    }
                }
            } else {
                if ($this->angelleye_is_need_to_set_billing_address() == false) {
                    $shipping_details = $this->ec_get_session_data('shipping_details');
                    if (!empty($shipping_details)) {
                        $_POST['billing_first_name'] = $shipping_details['first_name'];
                        $_POST['billing_last_name'] = $shipping_details['last_name'];
                        $_POST['billing_company'] = !empty($shipping_details['company']) ? wc_clean(stripslashes($shipping_details['company'])) : '';
                        $_POST['billing_email'] = $shipping_details['email'];
                        $_POST['billing_phone'] = $shipping_details['phone'];
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
                    $formatted_address = WC()->countries->get_formatted_address($address);
                    $formatted_address = str_replace('<br/>-<br/>', '<br/>', $formatted_address);
                    echo $formatted_address;
                    $shipping_details = $this->ec_get_session_data('shipping_details');
                    if (!empty($shipping_details)) {
                        echo!empty($shipping_details['email']) ? '<p class="angelleye-woocommerce-customer-details-email">' . $shipping_details['email'] . '</p>' : '';
                        echo!empty($shipping_details['phone']) ? '<p class="angelleye-woocommerce-customer-details-phone">' . $shipping_details['phone'] . '</p>' : '';
                    }
                    ?>
                </address>

            </div>

            <?php
        } catch (Exception $ex) {
            
        }
    }

    public function ec_disable_gateways($gateways) {
        try {
            if ($this->function_helper->ec_is_express_checkout()) {
                foreach ($gateways as $id => $gateway) {
                    if ($id !== 'paypal_express') {
                        unset($gateways[$id]);
                    }
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
            $paypal_express_terms = WC()->session->get('paypal_express_terms');
            if ($this->ec_is_checkout() && $this->function_helper->ec_is_express_checkout()) {
                $classes[] = 'express-checkout';
                if ($this->show_on_checkout && isset($paypal_express_terms)) {
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
        $paypal_express_terms = WC()->session->get('paypal_express_terms');
        if ($this->show_on_checkout && isset($paypal_express_terms)) {
            $checked_default = true;
        }
        return $checked_default;
    }

    public function ec_clear_session_data() {
        unset(WC()->session->paypal_express_checkout);
        unset(WC()->session->paypal_express_terms);
        unset(WC()->session->ec_save_to_account);
        unset(WC()->session->held_order_received_text);
        unset(WC()->session->post_data);
        unset(WC()->session->shiptoname);
        unset(WC()->session->payeremail);
    }

    public function ec_is_checkout() {
        return is_page(wc_get_page_id('checkout')) || apply_filters('woocommerce_is_checkout', false);
    }

    public function ec_order_received_text($text, $order) {
        $held_order_received_text = WC()->session->get('held_order_received_text');
        if ($order && $order->has_status('on-hold') && isset($held_order_received_text)) {
            $text = $held_order_received_text;
            unset(WC()->session->held_order_received_text);
        }
        return $text;
    }

    public function ec_enqueue_scripts_product_page() {
        try {
            $js_value = array('is_page_name' => '', 'enable_in_context_checkout_flow' => ( $this->enable_in_context_checkout_flow == 'yes' ? 'yes' : 'no'));
            if ($this->angelleye_is_in_context_enable() == true) {
                wp_enqueue_script('angelleye-in-context-checkout-js', 'https://www.paypalobjects.com/api/checkout.js', array(), null, true);
                wp_enqueue_script('angelleye-in-context-checkout-js-frontend', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/js/angelleye-in-context-checkout.js', array('jquery'), $this->version, true);
                wp_localize_script('angelleye-in-context-checkout-js-frontend', 'angelleye_in_content_param', array(
                    'payer_id' => AngellEYE_Utility::crypting($this->api_username, $action = 'd'),
                    'environment' => ( $this->testmode == true) ? 'sandbox' : 'production',
                    'locale' => ($this->use_wp_locale_code === 'yes' && get_locale() != '') ? get_locale() : '',
                    'start_flow' => esc_url(add_query_arg(array('startcheckout' => 'true'), wc_get_page_permalink('cart'))),
                    'show_modal' => apply_filters('woocommerce_paypal_express_checkout_show_cart_modal', true),
                    'update_shipping_costs_nonce' => wp_create_nonce('_wc_angeleye_ppec_update_shipping_costs_nonce'),
                    'ajaxurl' => WC_AJAX::get_endpoint('wc_angeleye_ppec_update_shipping_costs'),
                    'generate_cart_nonce' => wp_create_nonce('_angelleye_generate_cart_nonce'),
                    'add_to_cart_ajaxurl' => WC_AJAX::get_endpoint('angelleye_ajax_generate_cart'),
                    'is_product' => is_product() ? "yes" : "no",
                        )
                );
            }

            if (is_checkout()) {
                $js_value['is_page_name'] = 'checkout_page';
                wp_enqueue_script('angelleye-express-checkout-js', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/js/angelleye-express-checkout.js', array(), $this->version, 'all');
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
            $mini_cart_button_html = '';
            $mini_cart_button_html .= $this->woocommerce_paypal_express_checkout_button_angelleye($return = true);
            $mini_cart_button_html .= "<div class='clear'></div>";
            echo apply_filters('angelleye_ec_mini_cart_button_html', $mini_cart_button_html);
        }
    }

    public function woocommerce_paypal_express_checkout_button_angelleye($return = false, $possition = null) {
        if (!defined('WOOCOMMERCE_CART')) {
            define('WOOCOMMERCE_CART', true);
        }
        WC()->cart->calculate_totals();
        if (!AngellEYE_Utility::is_valid_for_use_paypal_express()) {
            return false;
        }

        if ($possition == 'top') {
            $class_top = 'paypal_checkout_button_top';
            $class_cc_top = 'paypal_checkout_button_cc_top';
        } else {
            $class_top = 'paypal_checkout_button_bottom';
            $class_cc_top = 'paypal_checkout_button_cc_bottom';
        }

        if ($this->enabled == 'yes' && $this->show_on_cart == 'yes' && 0 < WC()->cart->total) {
            $cart_button_html = '';
            if ($return == false) {
                do_action('angelleye_ec_before_buttom_cart_button', $this);
            }
            switch ($this->checkout_with_pp_button_type) {
                case 'textbutton':
                    $cart_button_html .= '<a class="paypal_checkout_button button ' . $class_top . ' alt ec_checkout_page_button_type_textbutton" href="' . esc_url(add_query_arg('pp_action', 'set_express_checkout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">' . $this->pp_button_type_text_button . '</a>';
                    break;
                case 'paypalimage':
                    $cart_button_html .= '<a class="paypal_checkout_button ' . $class_top . '" href="' . esc_url(add_query_arg('pp_action', 'set_express_checkout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">';
                    $cart_button_html .= '<img src=' . WC_Gateway_PayPal_Express_AngellEYE::angelleye_get_paypalimage() . ' class="ec_checkout_page_button_type_paypalimage"  align="top" alt="' . __('Pay with PayPal', 'paypal-for-woocommerce') . '" />';
                    $cart_button_html .= "</a>";
                    break;
                case 'customimage':
                    $cart_button_html .= '<a class="paypal_checkout_button ' . $class_top . '" href="' . esc_url(add_query_arg('pp_action', 'set_express_checkout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">';
                    $cart_button_html .= '<img src="' . $this->pp_button_type_my_custom . '" class="ec_checkout_page_button_type_customimage" align="top" alt="' . __('Pay with PayPal', 'paypal-for-woocommerce') . '" />';
                    $cart_button_html .= "</a>";
                    break;
            }
            if ($this->show_paypal_credit == 'yes') {
                $paypal_credit_button_markup = '<a class="paypal_checkout_button ' . $class_cc_top . '" href="' . esc_url(add_query_arg('use_paypal_credit', 'true', add_query_arg('pp_action', 'set_express_checkout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/'))))) . '" >';
                $paypal_credit_button_markup .= '<img src="https://www.paypalobjects.com/webstatic/en_US/i/buttons/ppcredit-logo-small.png" width="148" height="26" class="ppcreditlogo ec_checkout_page_button_type_pc"  align="top" alt="' . __('Check out with PayPal Credit', 'paypal-for-woocommerce') . '" />';
                $paypal_credit_button_markup .= '</a>';
                $cart_button_html .= $paypal_credit_button_markup;
            }
            if ($this->enable_tokenized_payments == 'yes') {
                $cart_button_html .= $this->function_helper->angelleye_ec_save_payment_method_checkbox();
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
        WC()->cart->calculate_totals();
        if (AngellEYE_Utility::is_express_checkout_credentials_is_set() == false) {
            return false;
        }
        if (!AngellEYE_Utility::is_valid_for_use_paypal_express()) {
            return false;
        }
        if (WC()->cart->total > 0) {
            $ec_top_checkout_button = '';
            wp_enqueue_script('angelleye_button');
            echo '<div id="checkout_paypal_message" class="woocommerce-info info">';

            do_action('angelleye_ec_checkout_page_before_checkout_button', $this);
            $ec_top_checkout_button .= '<div id="paypal_box_button">';
            switch ($this->checkout_with_pp_button_type) {
                case "textbutton":
                    $ec_top_checkout_button .= '<div class="paypal_ec_textbutton">';
                    $ec_top_checkout_button .= '<a  class="paypal_checkout_button paypal_checkout_button_text button alt" href="' . esc_url(add_query_arg('pp_action', 'set_express_checkout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">' . $this->pp_button_type_text_button . '</a>';
                    $ec_top_checkout_button .= '</div>';
                    break;
                case "paypalimage":
                    $ec_top_checkout_button .= '<div id="paypal_ec_button">';
                    $ec_top_checkout_button .= '<a  class="paypal_checkout_button" href="' . esc_url(add_query_arg('pp_action', 'set_express_checkout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">';
                    $ec_top_checkout_button .= "<img src='" . WC_Gateway_PayPal_Express_AngellEYE::angelleye_get_paypalimage() . "' class='ec_checkout_page_button_type_paypalimage'  border='0' alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
                    $ec_top_checkout_button .= "</a>";
                    $ec_top_checkout_button .= '</div>';
                    break;
                case "customimage":
                    $button_img = $this->pp_button_type_my_custom;
                    $ec_top_checkout_button .= '<div id="paypal_ec_button">';
                    $ec_top_checkout_button .= '<a  class="paypal_checkout_button" href="' . esc_url(add_query_arg('pp_action', 'set_express_checkout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">';
                    $ec_top_checkout_button .= "<img src='{$button_img}' class='ec_checkout_page_button_type_paypalimage' width='150' border='0' alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
                    $ec_top_checkout_button .= "</a>";
                    $ec_top_checkout_button .= '</div>';
                    break;
            }
            if ($this->show_paypal_credit == 'yes') {
                $paypal_credit_button_markup = '<div id="paypal_ec_paypal_credit_button">';
                $paypal_credit_button_markup .= '<a  class="paypal_checkout_button paypal_checkout_button_cc" href="' . esc_url(add_query_arg('use_paypal_credit', 'true', add_query_arg('pp_action', 'set_express_checkout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/'))))) . '" >';
                $paypal_credit_button_markup .= "<img src='https://www.paypalobjects.com/webstatic/en_US/i/buttons/ppcredit-logo-small.png' class='ec_checkout_page_button_type_paypalimage' alt='Check out with PayPal Credit'/>";
                $paypal_credit_button_markup .= '</a>';
                $paypal_credit_button_markup .= '</div>';
                $ec_top_checkout_button .= $paypal_credit_button_markup;
            }
            if ($this->enable_tokenized_payments == 'yes') {
                $ec_top_checkout_button .= $this->function_helper->angelleye_ec_save_payment_method_checkbox();
            }
            $ec_top_checkout_button .= '<div class="woocommerce_paypal_ec_checkout_message">';
            $ec_top_checkout_button .= '<p class="checkoutStatus">' . $this->angelleye_skip_text . '</p>';
            $ec_top_checkout_button .= '</div>';
            echo apply_filters('angelleye_ec_checkout_page_buy_now_nutton', $ec_top_checkout_button);
            do_action('angelleye_ec_checkout_page_after_checkout_button', $this);
            echo '<div class="clear"></div></div>';
            echo '</div>';
            echo '<div style="clear:both; margin-bottom:10px;"></div>';
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
        $post_data = WC()->session->get('post_data');
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
        WC()->cart->calculate_totals();
        $payment_gateways_count = 0;
        echo "<style>table.cart td.actions .input-text, table.cart td.actions .button, table.cart td.actions .checkout-button {margin-bottom: 0.53em !important}</style>";
        if ($this->enabled == 'yes' && 0 < WC()->cart->total) {
            $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
            unset($payment_gateways['paypal_pro']);
            unset($payment_gateways['paypal_pro_payflow']);
            $payment_gateway_count = count($payment_gateways);
            if ($this->show_on_checkout != 'regular' && $this->show_on_checkout != 'both') {
                $payment_gateway_count = $payment_gateway_count + 1;
            }
            if ($this->enabled == 'yes' && $payment_gateway_count == 1) {
                if ($this->paypal_pro_enabled == 'yes' || $this->paypal_flow_enabled == 'yes') {
                    $checkout_button_display_text = $this->show_on_cart == 'yes' ? __('Pay with Credit Card', 'paypal-for-woocommerce') : __('Proceed to Checkout', 'paypal-for-woocommerce');
                    echo '<script type="text/javascript">
                                jQuery(document).ready(function(){
                                    if (jQuery(".checkout-button, .button.checkout.wc-forward").is("input")) {
                                        jQuery(".checkout-button, .button.checkout.wc-forward").val("' . $checkout_button_display_text . '");
                                    } else {
                                        jQuery(".checkout-button, .button.checkout.wc-forward").html("<span>' . $checkout_button_display_text . '</span>");
                                    }
                                });
                              </script>';
                } elseif ($this->show_on_cart == 'yes') {
                    echo '<style> input.checkout-button,
                                 a.checkout-button, .button.checkout.wc-forward, a.checkout-button.wc-forward {
                                    display: none !important;
                                }</style>';
                }
            }
        }
    }

    public function angelleye_woocommerce_order_button_html($order_button_hrml) {
        if ($this->function_helper->ec_is_express_checkout()) {
            $order_button_text = __('Cancel order', 'paypal-for-woocommerce');
            $cancel_order_url = add_query_arg('pp_action', 'cancel_order', WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE'));
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
        if ($this->function_helper->ec_is_express_checkout() || $this->ec_get_session_data('shipping_details')) {
            $destination = $this->ec_get_session_data('shipping_details');
            if (!empty($destination)) {
                $packages[0]['destination']['country'] = $destination['country'];
                $packages[0]['destination']['state'] = $destination['state'];
                $packages[0]['destination']['postcode'] = $destination['postcode'];
                $packages[0]['destination']['city'] = $destination['city'];
                $packages[0]['destination']['address'] = $destination['address_1'];
                $packages[0]['destination']['address_2'] = $destination['address_2'];
            }
        }
        return $packages;
    }

    public function angelleye_billing_agreement_notice() {
        if (AngellEYE_Utility::is_display_angelleye_billing_agreement_notice($this) == true) {
            echo '<div class="error"><p>' . sprintf(__("PayPal Express Checkout Billing Agreements / Reference Transactions require specific approval by PayPal. Please contact PayPal to enable this feature before using it on your site. <a href=%s>%s</a>", 'paypal-for-woocommerce'), '"' . esc_url(add_query_arg("ignore_billing_agreement_notice", 0)) . '"', __("Hide this notice", 'paypal-for-woocommerce')) . '</p></div>';
        }
    }

    public function wc_ajax_update_shipping_costs() {
        if (!wp_verify_nonce($_POST['nonce'], '_wc_angeleye_ppec_update_shipping_costs_nonce')) {
            wp_die(__('Cheatin&#8217; huh?', 'woocommerce-gateway-paypal-express-checkout'));
        }
        if (!defined('WOOCOMMERCE_CART')) {
            define('WOOCOMMERCE_CART', true);
        }
        WC()->shipping->reset_shipping();
        WC()->cart->calculate_totals();
        if (!empty($_POST['wc-paypal_express-new-payment-method']) && $_POST['wc-paypal_express-new-payment-method'] == true) {
            WC()->session->set('ec_save_to_account', 'on');
        }
        wp_send_json(new stdClass());
    }

    public function angelleye_ajax_generate_cart() {
        global $wpdb, $post, $product;
        $product_id = '';
        if (!wp_verify_nonce($_POST['nonce'], '_angelleye_generate_cart_nonce')) {
            wp_die(__('Cheatin&#8217; huh?', 'woocommerce-gateway-paypal-express-checkout'));
        }
        WC()->shipping->reset_shipping();
        if (empty($post->ID)) {
            $product_id = $_POST['product_id'];
        } else {
            $product_id = $post->ID;
        }
        $product = wc_get_product($product_id);
        if (is_object($product)) {
            if (!defined('WOOCOMMERCE_CART')) {
                define('WOOCOMMERCE_CART', true);
            }
            $qty = !isset($_POST['qty']) ? 1 : absint($_POST['qty']);
            if ($product->is_type('variable')) {
                $attributes = array_map('wc_clean', $_POST['attributes']);
                if (version_compare(WC_VERSION, '3.0', '<')) {
                    $variation_id = $product->get_matching_variation($attributes);
                } else {
                    $data_store = WC_Data_Store::load('product');
                    $variation_id = $data_store->find_matching_product_variation($product, $attributes);
                }
                WC()->cart->add_to_cart($product->get_id(), $qty, $variation_id, $attributes);
            } elseif ($product->is_type('simple')) {
                WC()->cart->add_to_cart($product->get_id(), $qty);
            }
            WC()->cart->calculate_totals();
        }
        $url = esc_url_raw(add_query_arg('pp_action', 'set_express_checkout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/'))));
        if (!empty($_POST['wc-paypal_express-new-payment-method']) && $_POST['wc-paypal_express-new-payment-method'] == 'true') {
            $url = add_query_arg('ec_save_to_account', 'true', $url);
        }
        if (!empty($_POST['is_cc']) && $_POST['is_cc'] == 'true') {
            $url = add_query_arg('use_paypal_credit', 'true', $url);
        }
        wp_send_json(array('url' => $url));
    }

    public function angelleye_in_content_js($url) {
        if (strpos($url, 'https://www.paypalobjects.com/api/checkout.js') !== false) {
            return "$url' async data-log-level='error";
        }
        return $url;
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
        $paypal_express_checkout = WC()->session->get('paypal_express_checkout');
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
            wp_redirect(wc_get_page_permalink('checkout'));
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

    public function angelleye_optional_billing_fields($address_fields) {
        if ($this->function_helper->ec_is_express_checkout()) {
            $address_fields['billing_email']['required'] = false;
            $address_fields['billing_country']['required'] = false;
            $address_fields['billing_state']['required'] = false;
            $address_fields['billing_first_name']['required'] = false;
            $address_fields['billing_last_name']['required'] = false;
            $address_fields['billing_address_1']['required'] = false;
            $address_fields['billing_address_2']['required'] = false;
            $address_fields['billing_postcode']['required'] = false;
            $address_fields['billing_city']['required'] = false;
        }
        return $address_fields;
    }

    public function angelleye_shipping_sec_title() {
        if ($this->function_helper->ec_is_express_checkout()) {
            ?><h3><?php _e('Shipping details', 'woocommerce'); ?></h3> <?php
        }
    }

    public function angelleye_paypal_marketing_solutions() {
        if (!empty($this->paypal_marketing_solutions_enabled) && $this->paypal_marketing_solutions_enabled == 'yes') {
            if ($this->testmode == true) {
                if (!empty($this->paypal_marketing_solutions_cid_sandbox)) {
                    ?>
                    <!-- PayPal BEGIN -->
                    <script>
                        ;
                        (function (a, t, o, m, s) {
                            a[m] = a[m] || [];
                            a[m].push({t: new Date().getTime(), event: 'snippetRun'});
                            var f = t.getElementsByTagName(o)[0], e = t.createElement(o), d = m !== 'paypalDDL' ? '&m=' + m : '';
                            e.async = !0;
                            e.src = 'https://www.sandbox.paypal.com/tagmanager/pptm.js?id=' + s + d;
                            f.parentNode.insertBefore(e, f);
                        })(window, document, 'script', 'paypalDDL', '<?php echo $this->paypal_marketing_solutions_cid_sandbox; ?>');
                    </script>
                    <!-- PayPal END -->
                    <?php
                }
            } else {

                if (!empty($this->paypal_marketing_solutions_cid_production)) {
                    ?>
                    <!-- PayPal BEGIN -->
                    <script>
                        ;
                        (function (a, t, o, m, s) {
                            a[m] = a[m] || [];
                            a[m].push({t: new Date().getTime(), event: 'snippetRun'});
                            var f = t.getElementsByTagName(o)[0], e = t.createElement(o), d = m !== 'paypalDDL' ? '&m=' + m : '';
                            e.async = !0;
                            e.src = 'https://www.paypal.com/tagmanager/pptm.js?id=' + s + d;
                            f.parentNode.insertBefore(e, f);
                        })(window, document, 'script', 'paypalDDL', '<?php echo $this->paypal_marketing_solutions_cid_production; ?>');
                    </script>
                    <!-- PayPal END -->
                    <?php
                }
            }
        }
    }

    public function angelleye_is_in_context_enable() {

        if ($this->enable_in_context_checkout_flow === 'yes' && $this->enabled == 'yes') {
            if ($this->function_helper->ec_is_express_checkout()) {
                return false;
            }
            if (is_product()) {
                $post_id = get_the_ID();
                $is_ec_button_enable_product_level = get_post_meta($post_id, '_enable_ec_button', true);
                if ($this->enabled == 'yes' && $this->show_on_product_page == 'yes' && $is_ec_button_enable_product_level == 'yes') {
                    return true;
                }
            }
            if (is_checkout()) {
                if ($this->show_on_checkout == 'top' || $this->show_on_checkout == 'both') {
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
        } else {
            return false;
        }
        return false;
    }

    public function angelleye_update_marketing_solution() {
        if (!empty($_POST['reset']) && 'reset' == $_POST['reset']) {
            unset($_POST['reset']);
            return false;
        }
        if (!empty($_POST['woocommerce_paypal_express_paypal_marketing_solutions_enabled']) && $_POST['woocommerce_paypal_express_paypal_marketing_solutions_enabled'] == true) {
            if (empty($_POST['woocommerce_paypal_express_testmode'])) {
                if ($_POST['woocommerce_paypal_express_api_username'] != $this->api_username || empty($this->paypal_marketing_solutions_cid_production)) {
                    $cid_production = '';
                    $result = array();
                    $website_name = get_bloginfo('name');
                    $website_url = get_bloginfo('url');
                    $website_url = str_ireplace('www.', '', parse_url($website_url, PHP_URL_HOST));
                    $post = '{"owner_id":"woocommerce_container","owner_type":"PAYPAL","application_context":{"terms_accepted":true,"bn_code":"AngellEYE_SP_WooCommerce_MS","partner_name":"' . $website_name . '"},"name":"woocommerce_container","description":"Container created from PayPal for WooCommerce plugin","url":"' . $website_url . '","published":true,"tags":[{"tag_definition_id":"credit","enabled":true,"configuration":[{"id":"analytics-id","value":"' . $_POST['woocommerce_paypal_express_api_username'] . '-1"},{"id":"variant","value":"slide-up"},{"id":"flow","value":"credit"},{"id":"mobile-flow","value":"credit"},{"id":"is-mobile-enabled","value":"true"},{"id":"is-desktop-enabled","value":"true"},{"id":"limit","value":"3"}]}, {"tag_definition_id": "analytics", "enabled": true, "configuration": [{"id": "analytics-id", "value": "' . $_POST['woocommerce_paypal_express_api_username'] . '-1"}]}]}';
                    $headers = array(
                        'Accept: application/json',
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($post),
                        "x_nvp_pwd: " . $_POST['woocommerce_paypal_express_api_password'],
                        "x_nvp_signature: " . $_POST['woocommerce_paypal_express_api_signature'],
                        "x_nvp_user: " . $_POST['woocommerce_paypal_express_api_username']
                    );
                    $result_response = $this->angelleye_paypal_marketing_solutions_request($post, $headers);
                    if (!empty($result_response['response'])) {
                        WC_Gateway_PayPal_Express_AngellEYE::log('PayPal Marketing Solution request: ' . print_r($post, true), 'info', 'paypal_marketing_solutions');
                        $Response = json_decode($result_response['response']);
                        WC_Gateway_PayPal_Express_AngellEYE::log('PayPal Marketing Solution response: ' . json_encode($Response), 'info', 'paypal_marketing_solutions');
                        if (!empty($result_response['httpCode']) && $result_response['httpCode'] == 400) {
                            if (!empty($Response->details[0]->issue) && 'EXISTING_CONTAINER' == $Response->details[0]->issue) {
                                $cid_production = !empty($Response->details[0]->value) ? $Response->details[0]->value : '';
                                $_POST['woocommerce_paypal_express_paypal_marketing_solutions_cid_production'] = $cid_production;
                            } else {
                                unset($_POST['woocommerce_paypal_express_paypal_marketing_solutions_enabled']);
                                $result['error_msg'] = !empty($Response->message) ? $Response->message : '';
                                echo '<div class="notice error"><p>' . $result['error_msg'] . '</p></div>';
                            }
                        } elseif (!empty($result_response['httpCode']) && $result_response['httpCode'] == 201) {
                            $result['success'] = true;
                            $link = $Response->links[0];
                            $e = explode('/', $link->href);
                            $_POST['woocommerce_paypal_express_paypal_marketing_solutions_cid_production'] = end($e);
                        }
                    } else {
                        unset($_POST['woocommerce_paypal_express_paypal_marketing_solutions_enabled']);
                    }
                }
            }
        }
    }

    public function angelleye_paypal_marketing_solutions_request($post, $headers) {
        $result = array();
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_VERBOSE, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_URL, 'https://api.paypal.com/proxy/v1/offers/containers');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_SSLVERSION, 6);
        $result['response'] = curl_exec($curl);
        $result['httpCode'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return $result;
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
        $enable_in_context_checkout_flow = !empty($this->setting['enable_in_context_checkout_flow']) ? $this->setting['enable_in_context_checkout_flow'] : 'no';
        wp_register_script('angelleye_frontend', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . '/assets/js/angelleye-frontend.js', array('jquery'), WC_VERSION, true);
        $translation_array = array(
            'is_product' => is_product() ? "yes" : "no",
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
            wp_enqueue_style('ppe_cart', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/css/cart.css');
        }
        if (!is_admin() && is_checkout()) {
            wp_enqueue_style('ppe_checkout', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/css/checkout.css');
        }
        if (!is_admin() && is_single() && $this->setting['enabled'] == 'yes' && $this->setting['show_on_product_page'] == 'yes') {
            if (!empty($post)) {
                $_enable_ec_button = get_post_meta($post->ID, '_enable_ec_button', true);
            }
            if ($_enable_ec_button == 'yes') {
                wp_enqueue_style('ppe_single', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/css/single.css');
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

}
