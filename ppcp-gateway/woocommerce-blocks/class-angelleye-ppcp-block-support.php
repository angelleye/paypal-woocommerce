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
        wp_register_script('wc-payment-method-angelleye_ppcp', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/woocommerce-blocks/js/index.js', array('jquery', 'react', 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-i18n', 'wp-polyfill', 'wp-element', 'wp-plugins'), VERSION_PFW, true);
        if (angelleye_ppcp_has_active_session()) {
            $order_button_text = apply_filters('angelleye_ppcp_order_review_page_place_order_button_text', __('Confirm Your PayPal Order', 'paypal-for-woocommerce'));
        } else {
            $order_button_text = 'Proceed to PayPal';
        }
        wp_localize_script('wc-payment-method-angelleye_ppcp', 'angelleye_ppcp_manager_block', array(
            'placeOrderButtonLabel' => $order_button_text
        ));
        wp_set_script_translations('wc-payment-method-angelleye_ppcp', 'woocommerce-square');
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
