<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class AngellEYE_PPCP_Checkout_Block extends AbstractPaymentMethodType {
    
    protected $name = 'angelleye_ppcp';// your payment gateway name

    public function initialize() {
        $this->settings = get_option('woocommerce_angelleye_ppcp_settings', []);
        $this->gateway = new WC_Gateway_PPCP_AngellEYE();
    }

    public function is_active() {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {

        wp_register_script(
                'angelleye_ppcp-blocks-integration',
                PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/checkout-block/ppcp-checkout.js',
                [
                    'wc-blocks-registry',
                    'wc-settings',
                    'wp-element',
                    'wp-html-entities',
                    'wp-i18n',
                ],
                null,
                true
        );
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('angelleye_ppcp-blocks-integration');
        }
        return ['angelleye_ppcp-blocks-integration'];
    }

    public function get_payment_method_data() {
        return [
            'title' => $this->gateway->title,
                'description' => 'test',
        ];
    }
}
