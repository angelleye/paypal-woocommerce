<?php

if (!defined('ABSPATH')) {
    exit;
}

class Angelleye_Cartflows_Pro_Helper {

    protected static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_filter('cartflows_offer_supported_payment_gateway_slugs', array($this, 'own_cartflows_offer_supported_payment_gateway_slugs'), 10, 1);
        add_filter('cartflows_offer_supported_payment_gateways', array($this, 'own_cartflows_offer_supported_payment_gateways'), 10, 1);
        add_action('wp_ajax_nopriv_cartflows_front_create_paypal_express_angelleye_checkout_token', array($this, 'generate_angelleye_express_checkout_token'), 10);
        add_action('wp_ajax_cartflows_front_create_paypal_express_angelleye_checkout_token', array($this, 'generate_angelleye_express_checkout_token'), 10);
        add_action('woocommerce_api_cartflows_paypal_express', array($this, 'maybe_handle_paypal_express_api_call'));
        add_filter('angelleye_ec_force_to_display_checkout_page', array($this, 'angelleye_express_checkout_cartflow'));
        add_filter('angelleye_paypal_payflow_allow_default_order_status', '__return_false');
    }

    /**
     * Ignore Gateways checkout processed.
     * @param string $gateways
     * @return string
     */
    public function own_cartflows_offer_supported_payment_gateway_slugs($gateways) {
        if (!isset($gateways['paypal_express'])) {
            $gateways[] = 'paypal_express';
        }
        return $gateways;
    }

    /**
     * 
     * @param type $supported_gateways
     */
    public function own_cartflows_offer_supported_payment_gateways($supported_gateways) {
        $supported_gateways['paypal_express'] = array(
            'file' => 'paypal-express-angelleye.php',
            'class' => 'Cartflows_Pro_Gateway_Paypal_Express_Angelleye',
            'path' => PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/cartflows-pro/class-cartflows-pro-gateway-paypal-express-angelleye.php'
        );
        $supported_gateways['paypal_pro'] = array(
            'file' => 'paypal-pro-angelleye.php',
            'class' => 'Cartflows_Pro_Gateway_PayPal_Pro_AngellEYE',
            'path' => PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/cartflows-pro/class-cartflows-pro-gateway-paypal-pro-angelleye.php'
        );
        $supported_gateways['paypal_pro_payflow'] = array(
            'file' => 'paypal-pro-payflow-angelleye.php',
            'class' => 'Cartflows_Pro_Gateway_PayPal_Pro_PayFlow_AngellEYE',
            'path' => PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/cartflows-pro/class-cartflows-pro-gateway-paypal-pro-payflow-angelleye.php'
        );
        $supported_gateways['braintree'] = array(
            'file' => 'braintree-angelleye.php',
            'class' => 'Cartflows_Pro_Gateway_Braintree_AngellEYE',
            'path' => PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/cartflows-pro/class-cartflows-pro-gateway-braintree-angelleye.php'
        );
        return $supported_gateways;
    }

    /**
     * Handles angelleye paypal_express API call
     */
    function maybe_handle_paypal_express_api_call() {
        wcf_pro()->gateways->load_gateway('paypal_express')->create_billing_agreement();
        wcf_pro()->gateways->load_gateway('paypal_express')->process_api_calls();
    }

    function angelleye_express_checkout_cartflow($bool) {
        $post_data = angelleye_get_session('post_data');
        if (!empty($post_data)) {
            if (!empty($post_data['_wcf_flow_id'])) {
                $order_bump = get_post_meta($post_data['_wcf_checkout_id'], 'wcf-pre-checkout-offer', true);
                if ('yes' == $order_bump) {
                    return true;
                } else {
                    return false;
                }
            }
        }
        return $bool;
    }

    function generate_angelleye_express_checkout_token() {
        wcf_pro()->gateways->load_gateway('paypal_express')->generate_express_checkout_token();
    }

}

Angelleye_Cartflows_Pro_Helper::instance();
