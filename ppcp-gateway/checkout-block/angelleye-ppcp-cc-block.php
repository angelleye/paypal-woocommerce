<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class AngellEYE_PPCP_CC_Block extends AbstractPaymentMethodType {

    private $gateway;
    protected $name = 'angelleye_ppcp_cc';
    public $pay_later;
    public $version;

    public function initialize() {
        $this->version = VERSION_PFW;
        $this->settings = get_option('woocommerce_angelleye_ppcp_settings', []);
        $this->gateway = new WC_Gateway_CC_AngellEYE();
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
        wp_register_script('angelleye_ppcp_cc-blocks-integration', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/checkout-block/ppcp-cc.js', array(), VERSION_PFW, true);
        if (angelleye_ppcp_has_active_session()) {
            $order_button_text = apply_filters('angelleye_ppcp_cc_order_review_page_place_order_button_text', __('Confirm Your PayPal Order', 'paypal-for-woocommerce'));
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
        wp_localize_script('angelleye_ppcp_cc-blocks-integration', 'angelleye_ppcp_cc_manager_block', array(
            'placeOrderButtonLabel' => $order_button_text,
            'is_order_confirm_page' => (angelleye_ppcp_has_active_session() === false) ? 'no' : 'yes',
            'is_paylater_enable_incart_page' => $is_paylater_enable_incart_page,
            'settins' => $this->settings,
            'page' => $page
        ));
        
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('angelleye_ppcp_cc-blocks-integration', 'paypal-for-woocommerce');
        }
        wp_enqueue_script('angelleye_ppcp_cc');
        if (angelleye_ppcp_has_active_session() === false && $page === 'cart') {
            do_action('angelleye_ppcp_cc_woo_cart_block_pay_later_message');
        }
        return ['angelleye_ppcp_cc-blocks-integration'];
    }

    public function get_payment_method_data() {
        return [
            'cc_title' => $this->get_setting('advanced_card_payments_title'),
            'description' => $this->get_setting('description'),
            'supports' => $this->get_supported_features(),
            'icons' => $this->gateway->get_block_icon()
        ];
    }
}
