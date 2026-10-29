<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Cart and Checkout block support shared by the wallets (Apple Pay, Google
 * Pay). Each is registered on the client as an express payment method and as
 * a checkout payment method with the wallet's button in place of "Place order"
 * (ppcp-wallet-block.js); the payment itself is completed by the gateway's
 * process_payment() with the PayPal order id the Blocks checkout posts.
 */
abstract class AngellEYE_PPCP_Wallet_Block extends AbstractPaymentMethodType {

    private $gateway;

    /** @var string Gateway class. */
    protected $gateway_class;

    /** @var string Wallet script in ppcp-gateway/checkout-block/. */
    protected $script_file;

    /** @var string Gateway property holding the payment method description. */
    protected $description_property;

    public function initialize() {
        $this->settings = get_option('woocommerce_angelleye_ppcp_settings', []);
    }

    private function get_gateway() {
        // Gateway classes are loaded lazily; see angelleye-ppcp-cc-block.php.
        if (null === $this->gateway && class_exists($this->gateway_class)) {
            $this->gateway = new $this->gateway_class();
        }
        return $this->gateway;
    }

    public function is_active() {
        $gateway = $this->get_gateway();
        return $gateway && $gateway->is_available();
    }

    public function get_payment_method_script_handles() {
        // angelleyeOrder, angelleye_ppcp_manager, the wallet button classes and
        // the SDK loading all come from the classic scripts.
        angelleye_ppcp_add_css_js();
        wp_register_script('angelleye_ppcp_wallet-blocks', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/checkout-block/ppcp-wallet-block.js', array('jquery', 'wc-blocks-registry', 'wc-blocks-data-store', 'wc-settings', 'wp-data', 'wp-element', 'wp-html-entities', 'wp-i18n'), VERSION_PFW, true);
        $handle = $this->name . '-blocks-integration';
        wp_register_script($handle, PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/checkout-block/' . $this->script_file, array('angelleye_ppcp_wallet-blocks', 'wc-settings'), VERSION_PFW, true);
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('angelleye_ppcp_wallet-blocks', 'paypal-for-woocommerce');
            wp_set_script_translations($handle, 'paypal-for-woocommerce');
        }
        return [$handle];
    }

    public function get_payment_method_data() {
        $gateway = $this->get_gateway();
        if (!$gateway) {
            return ['supports' => ['products']];
        }
        return [
            'title' => $gateway->title,
            'description' => $gateway->{$this->description_property},
            'icon' => $gateway->icon,
            'supports' => array_values(array_filter($gateway->supports, array($gateway, 'supports'))),
        ];
    }
}
