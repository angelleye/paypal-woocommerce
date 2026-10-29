<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Apple Pay for the Cart and Checkout blocks. Registered on the client as an
 * express payment method and as a checkout payment method with Apple's button
 * in place of "Place order" (ppcp-apple-pay.js); the payment itself is
 * completed by WC_Gateway_Apple_Pay_AngellEYE::process_payment() with the
 * PayPal order id the Blocks checkout posts.
 */
final class AngellEYE_PPCP_Apple_Pay_Block extends AbstractPaymentMethodType {

    private $gateway;
    protected $name = 'angelleye_ppcp_apple_pay';

    public function initialize() {
        $this->settings = get_option('woocommerce_angelleye_ppcp_settings', []);
    }

    private function get_gateway() {
        // Gateway classes are loaded lazily; see angelleye-ppcp-cc-block.php.
        if (null === $this->gateway && class_exists('WC_Gateway_Apple_Pay_AngellEYE')) {
            $this->gateway = new WC_Gateway_Apple_Pay_AngellEYE();
        }
        return $this->gateway;
    }

    public function is_active() {
        $gateway = $this->get_gateway();
        return $gateway && $gateway->is_available();
    }

    public function get_payment_method_script_handles() {
        // angelleyeOrder, angelleye_ppcp_manager and ApplePayCheckoutButton, and
        // the SDK loading, all come from the classic scripts.
        angelleye_ppcp_add_css_js();
        wp_register_script('angelleye_ppcp_apple_pay-blocks-integration', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/checkout-block/ppcp-apple-pay.js', array('jquery', 'wc-blocks-registry', 'wc-blocks-data-store', 'wc-settings', 'wp-data', 'wp-element', 'wp-html-entities', 'wp-i18n'), VERSION_PFW, true);
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('angelleye_ppcp_apple_pay-blocks-integration', 'paypal-for-woocommerce');
        }
        return ['angelleye_ppcp_apple_pay-blocks-integration'];
    }

    public function get_payment_method_data() {
        $gateway = $this->get_gateway();
        return [
            'title' => $gateway ? $gateway->title : __('Apple Pay', 'paypal-for-woocommerce'),
            'description' => $gateway ? $gateway->apple_pay_payments_description : '',
            'icon' => $gateway ? $gateway->icon : '',
            'supports' => $gateway ? array_values(array_filter($gateway->supports, array($gateway, 'supports'))) : ['products'],
        ];
    }
}
