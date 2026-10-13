<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('AngellEYE_PPCP_Compatibility_WCML', false)) {

    /**
     * All WCML-specific code lives here. WCML (WooCommerce Multilingual
     * & Multi-Currency) is a separate plugin from WPML core — it runs
     * on top of WPML and provides the String Translation UI for
     * payment gateway titles / descriptions. PFW registers its per-
     * gateway setting keys with WCML so store admins can translate
     * them per language.
     */
    class AngellEYE_PPCP_Compatibility_WCML {

        /**
         * @return bool True when WCML is active.
         */
        public static function is_active() {
            global $woocommerce_wpml;
            return isset($woocommerce_wpml) && is_object($woocommerce_wpml);
        }

        /**
         * Register WCML's gateway-text-keys filter. Called once from
         * AngellEYE_PPCP_Multilingual::init() when is_active() is true.
         */
        public static function register() {
            add_filter('wcml_gateway_text_keys_to_translate', array(__CLASS__, 'register_gateway_text_keys'), 10, 1);
        }

        /**
         * Expose PFW's per-gateway setting keys to WCML's String
         * Translation. Without this filter, only the default `title`
         * and `description` from WC core appear in String Translation
         * — PFW-specific keys (Advanced Card Payments, Apple Pay,
         * Google Pay titles/descriptions) would be invisible to
         * translators.
         *
         * @param array $text_keys
         * @return array
         */
        public static function register_gateway_text_keys($text_keys) {
            if (!is_array($text_keys)) {
                $text_keys = array();
            }
            return array_merge($text_keys, array(
                // PPCP Advanced Card Payments
                'advanced_card_payments_title',
                // Apple Pay
                'apple_pay_payments_title',
                'apple_pay_payments_description',
                // Google Pay
                'google_pay_payments_title',
                'google_pay_payments_description',
            ));
        }
    }

}
