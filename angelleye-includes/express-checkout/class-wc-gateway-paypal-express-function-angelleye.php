<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_PayPal_Express_Function_AngellEYE {

    public static function is_ssl_enable() {
        try {
            if (is_ssl() || get_option('woocommerce_force_ssl_checkout') == 'yes') {
                return true;
            } else {
                return false;
            }
        } catch (Exception $ex) {
            
        }
    }

    public function express_checkout_is_available() {
        try {
            $is_enable = $this->express_checkout_get_option('enabled');
            if (isset($is_enable) && $is_enable == 'yes') {
                return true;
            } else {
                return false;
            }
        } catch (Exception $ex) {
            
        }
    }

    public function express_checkout_get_option($option_name) {
        try {
            $woocommerce_express_checkout_settings = get_option('woocommerce_express_checkout_settings');
            if (isset($woocommerce_express_checkout_settings[$option_name]) && !empty($woocommerce_express_checkout_settings[$option_name])) {
                return $woocommerce_express_checkout_settings[$option_name];
            } else {
                return false;
            }
        } catch (Exception $ex) {
            
        }
    }

    public function ec_express_checkout_button() {
        try {
            global $post;
            $display_style = 'display: none;';
            if (!$this->express_checkout_is_available()) {
                return;
            }
            if (is_checkout()) {
                if ('no' == $this->express_checkout_get_option('show_on_checkout')) {
                    return;
                }
            }
            if (is_cart()) {
                if ('no' == $this->express_checkout_get_option('show_on_cart_page')) {
                    return;
                }
            }
            if (is_product()) {
                if ($this->ec_get_product_type($post->ID)) {
                    $display_style = 'display: block;';
                }
                if ('no' == $this->express_checkout_get_option('show_on_product_page')) {
                    return;
                }
            }
            if (WC()->cart->needs_payment() || is_product()) {
                $ec_button_output = '';
                $ec_button_link = $this->ec_get_checkout_url('set_express_checkout');
                if ('image' === $this->express_checkout_get_option('checkout_button_style')) {
                    $ec_button_output .= '<div class="express_checkout_button"><a href="' . $ec_button_link . '" class="single_add_to_cart_button paypal_checkout_button paypal-express-checkout-button ec_clearfix">';
                    $ec_button_output .= '<input type="image" class="single_add_to_cart_button" src="https://www.paypalobjects.com/webstatic/' . $this->ec_get_locale() . '/i/buttons/checkout-logo-medium.png" width="170" height="32" style="width: 170px; height: 32px; float: right; clear: both; margin: 3px 0px 6px 0; border: none; padding: 0;" align="top" alt="' . __('Check out with PayPal', 'paypal-for-woocommerce') . '" />';
                    $ec_button_output .= "</a></div>";
                } else {
                    $ec_button_output .= '<a class="single_add_to_cart_button paypal_checkout_button paypal-express-checkout-button button alt" href="' . $ec_button_link . '">' . __('Check out with PayPal &rarr;', 'paypal-for-woocommerce') . '</a>';
                }
                if ($this->ec_show_paypal_credit()) {
                    $ec_button_output .= '<div class="express_checkout_button_cradit_card"><a href="' . esc_url(add_query_arg('use_bml', 'true', $ec_button_link)) . '" class="single_add_to_cart_button paypal_checkout_button ec_clearfix">';
                    $ec_button_output .= '<input type="image" class="single_add_to_cart_button paypal_checkout_button" src="https://www.paypalobjects.com/webstatic/en_US/btn/btn_bml_SM.png" width="145" height="32" style="width: 145px; height: 32px; float: right; clear: both; border: none; padding: 0; margin: 0;" align="top" alt="' . __('Check out with PayPal', 'paypal-for-woocommerce') . '" />';
                    $ec_button_output .= '</a>';
                    $ec_button_output .= '</div>';
                }
                if (is_checkout()) {
                    $ec_button_output = '<div class="col2-set" id="customer_details"><div class="express_checkout_button_chekout_page"><div id="express_checkout_button_chekout_page" >' . $ec_button_output . '</div><div id="express_checkout_button_text" >' . $this->express_checkout_get_option('skip_text') . '</div></div></div>';
                }
                if (is_product()) {
                    $ec_button_output = '<div id="express_checkout_button_product_page" style="' . $display_style . '">' . $ec_button_output . '</div>';
                }
                echo apply_filters('wc_ec_button', $ec_button_output, $ec_button_link);
            }
        } catch (Exception $ex) {
            
        }
    }

    public function ec_get_product_type($ID) {
        try {
            $result = FALSE;
            $product = wc_get_product($ID);
            if ($product->is_type('simple') || $product->is_type('variable')) {
                $result = TRUE;
            }
            return $result;
        } catch (Exception $ex) {
            
        }
    }

    public function ec_show_paypal_credit() {
        try {
            $show_paypal_credit = ($this->express_checkout_get_option('show_paypal_credit')) ? $this->express_checkout_get_option('show_paypal_credit') : 'no';
            $is_us = false;
            if ($show_paypal_credit == 'yes') {
                if (substr(get_option("woocommerce_default_country"), 0, 2) == 'US') {
                    $is_us = true;
                }
            }
            return $is_us;
        } catch (Exception $ex) {
            
        }
    }

    public function ec_get_checkout_url($action) {
        return add_query_arg('pp_action', $action, WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE'));
    }

    public function ec_get_locale() {
        try {
            $locale = ($this->express_checkout_get_option('use_wp_locale_code')) ? $this->express_checkout_get_option('use_wp_locale_code') : get_locale();
            $safe_locales = array(
                'en_US',
                'de_DE',
                'en_AU',
                'nl_NL',
                'fr_FR',
                'zh_XC',
                'es_XC',
                'zh_CN',
                'fr_XC',
                'en_GB',
                'it_IT',
                'pl_PL',
                'ja_JP',
            );
            if (!in_array($locale, $safe_locales)) {
                $locale = 'en_US';
            }
            return apply_filters('wc_ec_button_language', $locale);
        } catch (Exception $ex) {
            
        }
    }

    public function ec_is_express_checkout() {
        return isset(WC()->session->paypal_express_checkout);
    }

    public function ec_notice_count($notice_type = '') {
        if (function_exists('wc_notice_count')) {
            return wc_notice_count($notice_type);
        }
        return 0;
    }

    public function ec_redirect_after_checkout() {
        try {
            if (!$this->ec_is_express_checkout()) {
                $args = array(
                    'result' => 'success',
                    'redirect' => $this->ec_get_checkout_url('set_express_checkout'),
                );
                if (isset($_POST['terms']) && wc_get_page_id('terms') > 0) {
                    WC()->session->paypal_express_terms = 1;
                }
                if (is_ajax()) {
                    if ($this->ec_is_version_gte_2_4()) {
                        wp_send_json($args);
                    } else {
                        echo json_encode($args);
                    }
                } else {
                    wp_redirect($args['redirect']);
                }
                exit;
            }
        } catch (Exception $ex) {
            
        }
    }

    public function ec_is_version_gte_2_4() {
        return $this->ec_get_version() && version_compare($this->ec_get_version(), '2.4', '>=');
    }

    public function ec_get_version() {
        return defined('WC_VERSION') && WC_VERSION ? WC_VERSION : null;
    }

    public function angelleye_ec_save_payment_method_checkbox() {
        echo sprintf(
                '<div class="angelleye_ec_save_to_accoount_box"><p class="form-row woocommerce-SavedPaymentMethods-saveNew">
                            <input id="wc-%1$s-new-payment-method" name="wc-%1$s-new-payment-method" type="checkbox" style="width:auto;" />
                            <label for="wc-%1$s-new-payment-method" style="display:inline;">%2$s</label>
                    </p></div>', esc_attr('paypal_express'), esc_html__('Save PayPal account for future use', 'woocommerce')
        );
    }
    
    public function angelleye_paypal_for_woocommerce_needs_shipping($SECFields) {
        if (sizeof(WC()->cart->get_cart()) != 0) {
            foreach (WC()->cart->get_cart() as $key => $value) {
                $_product = $value['data'];
                if (isset($_product->id) && !empty($_product->id) ) {
                    $_no_shipping_required = get_post_meta($_product->id, '_no_shipping_required', true);
                    if( $_no_shipping_required == 'yes' ) {
                        $SECFields['noshipping'] = 1;
                        return $SECFields;
                    }   
                }
            }
        }
        return $SECFields;
    }
}
