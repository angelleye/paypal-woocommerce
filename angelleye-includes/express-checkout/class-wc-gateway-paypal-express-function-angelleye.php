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

    public function ec_get_checkout_url($action) {
        return add_query_arg( array( 'pp_action' => $action, 'utm_nooverride' => '1'), WC()->api_request_url('WC_Gateway_PayPal_Express_AngellEYE') );
    }

    public function ec_is_express_checkout() {
        if ( ! class_exists( 'WooCommerce' ) || WC()->session == null ) {
            return false;
        }
        $paypal_express_checkout = WC()->session->get( 'paypal_express_checkout' );
        return isset($paypal_express_checkout);
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
                if ((isset($_POST['terms']) || isset($_POST['legal'])) && wc_get_page_id('terms') > 0) {
                    WC()->session->set( 'paypal_express_terms', 1);
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
        if( AngellEYE_Utility::is_cart_contains_subscription() == false ) {
            return sprintf(
                    '<div class="angelleye_ec_save_to_accoount_box">
                        <p class="form-row woocommerce-SavedPaymentMethods-saveNew">
                            <label for="wc-%1$s-new-payment-method"><input id="wc-%1$s-new-payment-method" name="wc-%1$s-new-payment-method" type="checkbox" />%2$s</label>
                        </p>
                    </div>', esc_attr('paypal_express'), esc_html__('Save PayPal account for future use', 'paypal-for-woocommerce')
            );
        } else {
            return '';
        }
        
    }
    
    public function angelleye_paypal_for_woocommerce_needs_shipping($SECFields) {
        $is_required = 0;
        $is_not_required = 0;
        if (sizeof(WC()->cart->get_cart()) != 0) {
            foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                $product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
                $_no_shipping_required = get_post_meta($product_id, '_no_shipping_required', true);
                if( $_no_shipping_required == 'yes' ) {
                    $is_not_required = $is_not_required + 1;
                } else {
                    $is_required = $is_required + 1;
                }   
            }
        }
        if( $is_required > 0 ) {
            return $SECFields;
        } elseif ($is_not_required > 0) {
            $SECFields['noshipping'] = 1;
            return $SECFields;
        }
        return $SECFields;
    }
    
    function ec_clear_session_data() {
        unset(WC()->session->paypal_express_checkout);
        unset(WC()->session->paypal_express_terms);
        unset(WC()->session->ec_save_to_account);
        unset(WC()->session->held_order_received_text);
        unset(WC()->session->post_data);
        unset(WC()->session->shiptoname);
        unset(WC()->session->payeremail);
    }
}
