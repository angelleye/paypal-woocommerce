<?php

use Automattic\WooCommerce\Blocks\Assets\Api as AssetApi;

add_action('woocommerce_blocks_loaded', 'angelleye_ppcp_woocommerce_blocks_support');

function angelleye_ppcp_woocommerce_blocks_support() {
    if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        include ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/woocommerce-blocks/class-angelleye-ppcp-block-support.php');
        add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                    $container = Automattic\WooCommerce\Blocks\Package::container();
                    
                    $container->register(
                            Automattic\WooCommerce\Blocks\Payments\Integrations\Angelleye_PPCP_Block_Support::class,
                            function ($container) {
                                $asset_api = $container->get( AssetApi::class );
                                return new Automattic\WooCommerce\Blocks\Payments\Integrations\Angelleye_PPCP_Block_Support($asset_api);
                            }
                    );

                    $payment_method_registry->register(
                            $container->get(Automattic\WooCommerce\Blocks\Payments\Integrations\Angelleye_PPCP_Block_Support::class)
                    );
                }
        );
    }
}
