<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use WC_Gateway_PPCP_AngellEYE;

final class AngellEYE_PPCP_Checkout_Block extends AbstractPaymentMethodType {

    private $gateway;
    protected $name = 'angelleye_ppcp'; 

    public function initialize() {
        $this->settings = get_option('woocommerce_angelleye_ppcp_settings', []);
        $this->gateway = new WC_Gateway_PPCP_AngellEYE();
    }

    public function is_active() {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {
        angelleye_ppcp_add_css_js();
        wp_register_script('angelleye_ppcp-blocks-integration', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/checkout-block/ppcp-checkout.js', array('jquery', 'react', 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-i18n', 'wp-polyfill', 'wp-element', 'wp-plugins'), VERSION_PFW, true);
        if (angelleye_ppcp_has_active_session()) {
            $order_button_text = apply_filters('angelleye_ppcp_order_review_page_place_order_button_text', __('Confirm Your PayPal Order', 'paypal-for-woocommerce'));
        } else {
            $order_button_text = 'Proceed to PayPal';
        }
        wp_localize_script('angelleye_ppcp-blocks-integration', 'angelleye_ppcp_manager_block', array(
            'placeOrderButtonLabel' => $order_button_text,
            'is_order_confirm_page' => (angelleye_ppcp_has_active_session() === false) ? 'no' : 'yes'
        ));
        if (is_product()) {
            $page = 'product';
        } elseif (is_cart() && !WC()->cart->is_empty()) {
            $page = 'cart';
        } elseif (is_checkout()) {
            $page = 'checkout';
        } elseif (is_checkout_pay_page()) {
            $page = 'checkout';
        }
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('angelleye_ppcp-blocks-integration', 'paypal-for-woocommerce');
        }
        wp_enqueue_script('angelleye_ppcp');
        if (angelleye_ppcp_has_active_session() === false && $page === 'cart') {
            do_action('angelleye_ppcp_woo_cart_block_pay_later_message');
        }
        return ['angelleye_ppcp-blocks-integration'];
    }

    public function get_payment_method_data() {
        return [
            'title' => $this->get_setting('title'),
            'description' => $this->get_setting('description'),
            'supports' => $this->get_supported_features(),
        ];
    }
}
