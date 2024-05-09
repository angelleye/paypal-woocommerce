<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class AngellEYE_PPCP_Checkout_Block extends AbstractPaymentMethodType {

    private $gateway;
    protected $name = 'angelleye_ppcp';
    public $pay_later;
    public $version;

    public function initialize() {
        $this->version = VERSION_PFW;
        $this->settings = get_option('woocommerce_angelleye_ppcp_settings', []);
        $this->gateway = new WC_Gateway_PPCP_AngellEYE();
        if (!class_exists('AngellEYE_PayPal_PPCP_Pay_Later')) {
            include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-pay-later-messaging.php');
        }
        $this->pay_later = AngellEYE_PayPal_PPCP_Pay_Later::instance();
    }

    public function is_active() {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {
        wp_register_style('angelleye_ppcp', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/css/wc-gateway-ppcp-angelleye-public.css', array(), $this->version, 'all');
        angelleye_ppcp_add_css_js();
        $this->pay_later->add_pay_later_script_in_frontend();
        wp_register_script('angelleye_ppcp-blocks-integration', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/checkout-block/ppcp-checkout.js', array('jquery', 'react', 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-i18n', 'wp-polyfill', 'wp-element', 'wp-plugins'), VERSION_PFW, true);
        if (angelleye_ppcp_has_active_session()) {
            $order_button_text = apply_filters('angelleye_ppcp_order_review_page_place_order_button_text', __('Confirm Your PayPal Order', 'paypal-for-woocommerce'));
        } else {
            $order_button_text = 'Proceed to PayPal';
        }
        $is_paylater_enable_incart_page = 'no';
        if ($this->pay_later->is_paypal_pay_later_messaging_enable_for_page($page = 'cart') && $this->pay_later->pay_later_messaging_cart_shortcode === false) {
            $is_paylater_enable_incart_page = 'yes';
        } else {
            $is_paylater_enable_incart_page = 'no';
        }
        $page = '';
        $is_pay_page = '';
        if (is_product()) {
            $page = 'product';
        } else if (is_cart() && !WC()->cart->is_empty()) {
            $page = 'cart';
        } elseif (is_checkout_pay_page()) {
            $page = 'checkout';
            $is_pay_page = 'yes';
        } elseif (is_checkout()) {
            $page = 'checkout';
        }
        wp_localize_script('angelleye_ppcp-blocks-integration', 'angelleye_ppcp_manager_block', array(
            'placeOrderButtonLabel' => $order_button_text,
            'is_order_confirm_page' => (angelleye_ppcp_has_active_session() === false) ? 'no' : 'yes',
            'is_paylater_enable_incart_page' => $is_paylater_enable_incart_page,
            'settins' => $this->settings,
            'page' => $page
        ));
        
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('angelleye_ppcp-blocks-integration', 'paypal-for-woocommerce');
        }
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
            'icon' => $this->gateway->icon
        ];
    }
}
