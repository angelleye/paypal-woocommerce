<?php

if (!defined('ABSPATH')) {
    exit;
}

class Angelleye_PayPal_Express_Checkout_Helper {

    public $setting;
    public $function_helper;

    public function __construct() {
        try {
            global $wpdb;
            $this->version = '1.0.1';
            $row = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'woocommerce_paypal_express_settings'));
            $this->setting = isset($row->option_value) ? maybe_unserialize($row->option_value) : array();
            $this->enable_tokenized_payments = !empty($this->setting['enable_tokenized_payments']) ? $this->setting['enable_tokenized_payments'] : 'no';
            $this->save_abandoned_checkout = 'yes' == !empty($this->setting['save_abandoned_checkout']) ? $this->setting['enable_tokenized_payments'] : 'no';
            $this->checkout_with_pp_button_type = !empty($this->setting['checkout_with_pp_button_type']) ? $this->setting['checkout_with_pp_button_type'] : 'paypalimage';
            $this->pp_button_type_text_button = !empty($this->setting['pp_button_type_text_button']) ? $this->setting['pp_button_type_text_button'] : 'Proceed to Checkout';
            $this->pp_button_type_my_custom = !empty($this->setting['pp_button_type_my_custom']) ? $this->setting['pp_button_type_my_custom'] :  WC_Gateway_PayPal_Express_AngellEYE::angelleye_get_paypalimage();
            $this->show_on_product_page = !empty($this->setting['show_on_product_page']) ? $this->setting['show_on_product_page'] : 'no';
            $this->enabled = !empty($this->setting['enabled']) ? $this->setting['enabled'] : 'no';
            $this->show_on_checkout = !empty($this->setting['show_on_checkout']) ? $this->setting['show_on_checkout'] : 'top';
            $this->button_position = !empty($this->setting['button_position']) ? $this->setting['button_position'] : 'bottom';
            $this->show_on_cart = !empty($this->setting['show_on_cart']) ? $this->setting['show_on_cart'] : 'yes';
            $this->testmode = !empty($this->setting['testmode']) ? $this->setting['testmode'] : 'yes';
            if (substr(get_option("woocommerce_default_country"), 0, 2) == 'US' || substr(get_option("woocommerce_default_country"), 0, 2) == 'UK') {
                $this->not_us_or_uk = false;
            } else {
                $this->not_us_or_uk = true;
            }
            $this->show_paypal_credit = !empty($this->setting['show_paypal_credit']) ? $this->setting['show_paypal_credit'] : 'yes';
            if ($this->not_us_or_uk) {
                $this->show_paypal_credit = 'no';
            }
            if ($this->testmode == 'yes') {
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
            add_action('woocommerce_after_add_to_cart_button', array($this, 'buy_now_button'));
            if($this->save_abandoned_checkout == false) {
                add_action('woocommerce_after_checkout_validation', array($this, 'angelleye_paypal_express_checkout_redirect_to_paypal'), 99);
            }
            add_action('woocommerce_add_to_cart_redirect', array($this, 'add_to_cart_redirect'));
            add_action('woocommerce_checkout_billing', array($this, 'ec_set_checkout_post_data'));
            add_action('woocommerce_available_payment_gateways', array($this, 'ec_disable_gateways'));
            add_filter('body_class', array($this, 'ec_add_body_class'));
            add_action('woocommerce_checkout_fields', array($this, 'ec_display_checkout_fields'));
            add_action('woocommerce_before_checkout_billing_form', array($this, 'ec_formatted_billing_address'), 9);
            add_action('woocommerce_review_order_after_submit', array($this, 'ec_cancel_link'));
            add_filter('woocommerce_terms_is_checked_default', array($this, 'ec_terms_express_checkout'));
            add_action('woocommerce_cart_emptied', array($this, 'ec_clear_session_data'));
            add_filter('woocommerce_thankyou_order_received_text', array($this, 'ec_order_received_text'), 10, 2);
            add_action('wp_enqueue_scripts', array($this, 'ec_enqueue_scripts_product_page'));
            add_action('woocommerce_before_cart_table', array($this, 'top_cart_button'));
            if ($this->is_express_checkout_credentials_is_set()) {
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
            $this->is_order_completed = true;
        } catch (Exception $ex) {

        }
    }

    public function buy_now_button() {
        try {
            global $post, $product;
            $_enable_ec_button = get_post_meta($product->id, '_enable_ec_button', true);
            if($_enable_ec_button == 'no') {
                return;
            }
            if ($this->enabled == 'yes' && ($this->show_on_product_page == 'yes' || $_enable_ec_button == 'yes')) {
                $ec_html_button = '';
                if ($this->enable_tokenized_payments == 'yes') {
                    $ec_html_button .= $this->function_helper->angelleye_ec_save_payment_method_checkbox();
                }
                $ec_html_button .= '<div class="angelleye_button_single">';
                $_product = wc_get_product($post->ID);
                $button_dynamic_class = 'single_variation_wrap_angelleye_' . $product->id;
                $hide = '';
                if ($_product->product_type == 'variation' || $_product->is_type('external') || $_product->get_price() == 0 || $_product->get_price() == '') {
                    $hide = 'display:none;';
                }
                $add_to_cart_action = esc_url(add_query_arg('express_checkout', '1'));
                switch ($this->checkout_with_pp_button_type) {
                    case 'textbutton':
                        $ec_html_button .= '<input data-action="' . esc_url($add_to_cart_action) . '" type="button" style="float: left; clear: both; margin: 3px 0 0 0; border: none; "'.$hide.'" class="single_add_to_cart_button button alt paypal_checkout_button single_variation_wrap_angelleye paypal_checkout_button button alt "' . $button_dynamic_class . '" name="express_checkout"  value="' . $this->pp_button_type_text_button . '"/>';
                        break;
                    case "paypalimage":
                        $button_img = WC_Gateway_PayPal_Express_AngellEYE::angelleye_get_paypalimage();
                        $ec_html_button .= '<input data-action="' . esc_url($add_to_cart_action) . '" type="image" src="'.$button_img.'" style="width: auto; height: auto;float: left; clear: both; margin: 3px 0 3px 0; border: none; padding: 0;"'.$hide.'" class="single_add_to_cart_button button alt paypal_checkout_button single_variation_wrap_angelleye ' . $button_dynamic_class . '" name="express_checkout" value="' . __('Pay with PayPal', 'paypal-for-woocommerce') . '"/>';
                        break;
                    case "customimage":
                        $ec_html_button .= '<input data-action="' . esc_url($add_to_cart_action) . '" type="image" src="'.$this->pp_button_type_my_custom.'" style="float: left; clear: both; margin: 3px 0 3px 0; border: none; padding: 0;"'.$hide.'" class="single_add_to_cart_button button alt paypal_checkout_button single_variation_wrap_angelleye ' . $button_dynamic_class . '" name="express_checkout" value="' . __('Pay with PayPal', 'paypal-for-woocommerce') . '"/>';
                        break;
                }
                $ec_html_button .= '</div>';
            }
            echo apply_filters('angelleye_ec_product_page_buy_now_button', $ec_html_button);
        } catch (Exception $ex) {

        }
    }

    public function angelleye_paypal_express_checkout_redirect_to_paypal() {
        try {
            if (isset($_POST['payment_method']) && 'paypal_express' === $_POST['payment_method'] && $this->function_helper->ec_notice_count('error') == 0) {
                WC()->session->post_data = $_POST;
                $this->function_helper->ec_redirect_after_checkout();
            }
        } catch (Exception $ex) {

        }
    }

    public function add_to_cart_redirect($url = null) {
        try {
            if (isset($_REQUEST['express_checkout']) || isset($_REQUEST['express_checkout_x'])) {
                wc_clear_notices();
                if( isset($_POST['wc-paypal_express-new-payment-method']) && $_POST['wc-paypal_express-new-payment-method'] = 'on' ) {
                    WC()->session->ec_save_to_account = 'on';
                }
                $url = esc_url_raw(add_query_arg('pp_action', 'set_express_checkout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/'))));
            }
            return $url;
        } catch (Exception $ex) {

        }
    }

    public function ec_get_session_data($key = '') {
        try {
            $session_data = null;
            if (empty($key)) {
                $session_data = WC()->session->paypal_express_checkout;
            } elseif (isset(WC()->session->paypal_express_checkout[$key])) {
                $session_data = WC()->session->paypal_express_checkout[$key];
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
                if ($value) {
                    $_POST['billing_' . $field] = $value;
                }
            }
            $order_note = WC()->session->post_data['order_comments'];
            if (!empty($order_note)) {
                $_POST['order_comments'] = $order_note;
            }
            if( !empty(WC()->session->post_data) ) {
                foreach (WC()->session->post_data as $key => $value) {
                    $_POST[$key] = $value;
                }
            }
            $this->chosen = true;
        } catch (Exception $ex) {

        }
    }

    public function ec_display_checkout_fields($checkout_fields) {
        try {
            if ($this->function_helper->ec_is_express_checkout() && $this->ec_get_session_data('shipping_details')) {
                foreach ($this->ec_get_session_data('shipping_details') as $field => $value) {
                    if (isset($checkout_fields['billing']) && isset($checkout_fields['billing']['billing_' . $field])) {
                        $required = isset($checkout_fields['billing']['billing_' . $field]['required']) && $checkout_fields['billing']['billing_' . $field]['required'];
                        if (!$required || $required && $value) {
                            $checkout_fields['billing']['billing_' . $field]['class'][] = 'express-provided';
                            $checkout_fields['billing']['billing_' . $field]['class'][] = 'hidden';
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
<!--                <a href="#" class="ex-show-address-fields" data-type="<?php echo esc_attr($type); ?>"><?php esc_html_e('Edit', 'paypal-for-woocommerce'); ?></a>-->
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
                    echo WC()->countries->get_formatted_address($address);
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
            if ($this->ec_is_checkout() && $this->function_helper->ec_is_express_checkout()) {
                $classes[] = 'express-checkout';
                if ($this->show_on_checkout && isset(WC()->session->paypal_express_terms)) {
                    $classes[] = 'express-hide-terms';
                }
            }
            return $classes;
        } catch (Exception $ex) {

        }
    }

    public function ec_formatted_billing_address() {
        $this->ec_formatted_address('billing');
    }

    public function ec_cancel_link() {
        if (!$this->ec_is_available() || !$this->function_helper->ec_is_express_checkout()) {
            return;
        }
        printf(
                '<a href="%1$s" class="ex-paypal-express-cancel">%2$s</a>', esc_url(add_query_arg(array('wc_paypal_express_clear_session' => true), WC()->cart->get_cart_url())), esc_html__('Cancel', 'paypal-for-woocommerce')
        );
    }

    public function ec_terms_express_checkout($checked_default) {
        if (!$this->ec_is_available() || !$this->function_helper->ec_is_express_checkout()) {
            return $checked_default;
        }
        if ($this->show_on_checkout && isset(WC()->session->paypal_express_terms)) {
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
        if ($order && $order->has_status('on-hold') && isset(WC()->session->held_order_received_text)) {
            $text = WC()->session->held_order_received_text;
            unset(WC()->session->held_order_received_text);
        }
        return $text;
    }

    public function ec_enqueue_scripts_product_page() {
        try {
            if (is_checkout()) {
                wp_enqueue_script('angelleye-express-checkout-js', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . '/assets/js/angelleye-express-chekout.js', array(), $this->version, 'all');
                wp_enqueue_script('angelleye-express-checkout-custom', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . '/assets/js/angelleye-express-chekout-custom.js', array(), $this->version, 'all');
                wp_enqueue_style('angelleye-express-checkout-css', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . '/assets/css/angelleye-express-chekout.css', array(), $this->version, 'all');
                wp_localize_script('angelleye-express-checkout-js', 'is_page_name', 'checkout_page');
                wp_localize_script('angelleye-express-checkout-custom', 'is_page_name', 'checkout_page');
            }
            if (is_product()) {
                wp_enqueue_style('angelleye-express-checkout-min-css', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . '/assets/css/angelleye-express-chekout.css', array(), $this->version, 'all');
                wp_enqueue_script('angelleye-express-checkout-custom', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . '/assets/js/angelleye-express-chekout-custom.js', array(), $this->version, 'all');
                wp_localize_script('angelleye-express-checkout-custom', 'is_page_name', 'single_product_page');
            }
            if (is_cart()) {
                wp_enqueue_script('angelleye-express-checkout-custom', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . '/assets/js/angelleye-express-chekout-custom.js', array(), $this->version, 'all');
                wp_localize_script('angelleye-express-checkout-custom', 'is_page_name', 'cart_page');
            }
            return true;
        } catch (Exception $ex) {

        }
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

    public function top_cart_button() {
        if ($this->is_express_checkout_credentials_is_set()) {
            $top_cart_button_html = '';
            if ($this->button_position == 'top' || $this->button_position == 'both') {
                do_action('angelleye_ec_before_top_cart_button', $this);
                $top_cart_button_html .= '<div class="wc-proceed-to-checkout angelleye_cart_button">';
                $top_cart_button_html .= $this->woocommerce_paypal_express_checkout_button_angelleye($return = true);
                $top_cart_button_html .= '</div>';
                echo apply_filters('angelleye_ec_top_cart_button', $top_cart_button_html);
                do_action('angelleye_ec_after_top_cart_button', $this);                
            }
        }
    }

    public function woocommerce_paypal_express_checkout_button_angelleye($return = false) {
        if (!AngellEYE_Utility::is_valid_for_use_paypal_express()) {
            return false;
        }
        if ($this->enabled == 'yes' && $this->show_on_cart == 'yes' && 0 < WC()->cart->total) {
            $cart_button_html = '';
            if ($this->enable_tokenized_payments == 'yes') {
                $cart_button_html .= $this->function_helper->angelleye_ec_save_payment_method_checkbox();
            }
            if($return == false) {
                do_action('angelleye_ec_before_buttom_cart_button', $this);
            }
            
            $angelleyeOverlay = '<div class="blockUI blockOverlay angelleyeOverlay" style="display:none;z-index: 1000; border: none; margin: 0px;  width: 100%; height: 100%; top: 0px; left: 0px; opacity: 0.6; cursor: default; position: absolute; background: url(' . WC()->plugin_url() . '/assets/images/select2-spinner.gif) 50% 50% / 16px 16px no-repeat rgb(255, 255, 255);"></div>';
            switch ($this->checkout_with_pp_button_type) {
                case 'textbutton':
                    $cart_button_html .= '<a style="margin-bottom:1em; border: none; " class="paypal_checkout_button button alt" href="' . esc_url(add_query_arg('pp_action', 'set_express_checkout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">' . $this->pp_button_type_text_button . '</a>';
                    $cart_button_html .= $angelleyeOverlay;
                    break;
                case 'paypalimage':
                    $cart_button_html .= '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('pp_action', 'set_express_checkout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">';
                    $cart_button_html .= '<img src=' . WC_Gateway_PayPal_Express_AngellEYE::angelleye_get_paypalimage() . ' style="width: auto; height: auto; margin: 3px 5px 3px 0; border: none; padding: 0;" align="top" alt="' . __('Pay with PayPal', 'paypal-for-woocommerce') . '" />';
                    $cart_button_html .= "</a>";
                    $cart_button_html .= $angelleyeOverlay;
                    break;
                case 'customimage':
                    $cart_button_html .= '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('pp_action', 'set_express_checkout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">';
                    $cart_button_html .= '<img src="' . $this->pp_button_type_my_custom . '" style="width: auto; height: auto; margin: 3px 0 0 0; border: none; padding: 0;" align="top" alt="' . __('Pay with PayPal', 'paypal-for-woocommerce') . '" />';
                    $cart_button_html .= "</a>";
                    $cart_button_html .= $angelleyeOverlay;
                    break;
            }
            if ($this->show_paypal_credit == 'yes') {
                $paypal_credit_button_markup = '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('use_paypal_credit', 'true', add_query_arg('pp_action', 'set_express_checkout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/'))))) . '" >';
                $paypal_credit_button_markup .= '<img src="https://www.paypalobjects.com/webstatic/en_US/i/buttons/ppcredit-logo-small.png" width="148" height="26" style="width: 148px; height: 26px; border: none; padding: 0; margin: 0;" align="top" alt="' . __('Check out with PayPal Credit', 'paypal-for-woocommerce') . '" />';
                $paypal_credit_button_markup .= '</a>';
                $paypal_credit_button_markup .= $angelleyeOverlay;
                $cart_button_html .= $paypal_credit_button_markup;
            }
            if($return == true) {
                return $cart_button_html;
            } else {
                echo $cart_button_html;
            }
            do_action('angelleye_ec_after_buttom_cart_button', $this);
        }
    }

    public function checkout_message() {
        if (!$this->is_express_checkout_credentials_is_set()) {
            return false;
        }
        if (!AngellEYE_Utility::is_valid_for_use_paypal_express()) {
            return false;
        }
        if (WC()->cart->total > 0) {
            $ec_top_checkout_button = '';
            wp_enqueue_script('angelleye_button');
            echo '<div id="checkout_paypal_message" class="woocommerce-info info">';
            if($this->enable_tokenized_payments == 'yes') {
                $ec_top_checkout_button .= $this->function_helper->angelleye_ec_save_payment_method_checkbox();
            }
            do_action('angelleye_ec_checkout_page_before_checkout_button', $this);
            
            $ec_top_checkout_button .= '<div id="paypal_box_button">';
            $_angelleyeOverlay = '<div class="blockUI blockOverlay angelleyeOverlay" style="display:none;z-index: 1000; border: none; margin: 0px; padding: 0px; width: 100%; height: 100%; top: 0px; left: 0px; opacity: 0.6; cursor: default; position: absolute; background: url(' . WC()->plugin_url() . '/assets/images/select2-spinner.gif) 50% 50% / 16px 16px no-repeat rgb(255, 255, 255);"></div>';
            switch ($this->checkout_with_pp_button_type) {
                case "textbutton":
                    $ec_top_checkout_button .= '<div class="paypal_ec_textbutton">';
                    $ec_top_checkout_button .= '<a class="paypal_checkout_button paypal_checkout_button_text button alt" href="' . esc_url(add_query_arg('pp_action', 'set_express_checkout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">' . $this->pp_button_type_text_button . '</a>';
                    $ec_top_checkout_button .= $_angelleyeOverlay;
                    $ec_top_checkout_button .= '</div>';
                    break;
                case "paypalimage":
                    $ec_top_checkout_button .= '<div id="paypal_ec_button">';
                    $ec_top_checkout_button .= '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('pp_action', 'set_express_checkout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">';
                    $ec_top_checkout_button .= "<img src='" . WC_Gateway_PayPal_Express_AngellEYE::angelleye_get_paypalimage() . "' style='width: auto; height: auto; margin: 3px 5px 3px 0; border: none; padding: 0;' border='0' alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
                    $ec_top_checkout_button .= "</a>";
                    $ec_top_checkout_button .= $_angelleyeOverlay;
                    $ec_top_checkout_button .= '</div>';
                    break;
                case "customimage":
                    $button_img = $this->pp_button_type_my_custom;
                    $ec_top_checkout_button .= '<div id="paypal_ec_button">';
                    $ec_top_checkout_button .= '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('pp_action', 'set_express_checkout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">';
                    $ec_top_checkout_button .= "<img src='{$button_img}' width='150' border='0' alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
                    $ec_top_checkout_button .= "</a>";
                    $ec_top_checkout_button .= $_angelleyeOverlay;
                    $ec_top_checkout_button .= '</div>';
                    break;
            }
            if ($this->show_paypal_credit == 'yes') {
                $paypal_credit_button_markup = '<div id="paypal_ec_paypal_credit_button">';
                $paypal_credit_button_markup .= '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('use_paypal_credit', 'true', add_query_arg('pp_action', 'set_express_checkout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/'))))) . '" >';
                $paypal_credit_button_markup .= "<img src='https://www.paypalobjects.com/webstatic/en_US/i/buttons/ppcredit-logo-small.png' alt='Check out with PayPal Credit'/>";
                $paypal_credit_button_markup .= '</a>';
                $paypal_credit_button_markup .= $_angelleyeOverlay;
                $paypal_credit_button_markup .= '</div>';
                $ec_top_checkout_button .= $paypal_credit_button_markup;
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
}
