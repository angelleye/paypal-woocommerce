<?php

namespace Automattic\WooCommerce\Blocks\Payments\Integrations;

use Automattic\WooCommerce\Blocks\Domain\Package;
use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets as BlockAssets;
use Automattic\WooCommerce\Blocks\Assets\Api as AssetApi;

final class Angelleye_PPCP_Block_Support extends AbstractPaymentMethodType {

    protected $name = 'angelleye_ppcp';
    private $asset_api;

    public function __construct($asset_api) {

        $this->asset_api = $asset_api;
    }

    public function initialize() {
        $this->settings = get_option('woocommerce_angelleye_ppcp_settings', []);
    }

    public function is_active() {
        return filter_var($this->get_setting('enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function get_payment_method_script_handles() {
        wp_register_script('wc-payment-method-angelleye_ppcp', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/woocommerce-blocks/js/index.js', array('jquery', 'react', 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-i18n', 'wp-polyfill'), VERSION_PFW, true);
        wp_set_script_translations( 'wc-payment-method-angelleye_ppcp', 'woocommerce-square' );
        return ['wc-payment-method-angelleye_ppcp'];
    }

    public function get_payment_method_data() {
        return [
            'title' => $this->get_setting('title'),
            'description' => $this->get_setting('description'),
            'supports' => $this->get_supported_features(),
        ];
    }

}
