<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('AngellEYE_PPCP_Multilingual', false)) {

    /**
     * Main entry point for PFW's multilingual compatibility layer.
     *
     * Per-plugin code lives in its own file under includes/compatibility/:
     *   - class-angelleye-ppcp-compatibility-wpml.php
     *   - class-angelleye-ppcp-compatibility-polylang.php
     *   - class-angelleye-ppcp-compatibility-wcml.php
     *
     * This class:
     *   1. Loads those per-plugin files.
     *   2. Asks each one "are you active?" and delegates language /
     *      locale lookups to the first active plugin.
     *   3. Registers cross-cutting WordPress hooks (order language
     *      stamping, email language switching, thank-you URL
     *      translation) that work regardless of which plugin is
     *      running — all three multilingual plugins listen to the
     *      same WPML-style action names.
     *
     * Single-responsibility guideline: anything WPML-specific goes
     * in the WPML file. Anything Polylang-specific goes in the
     * Polylang file. Anything cross-cutting (same behavior regardless
     * of which plugin) stays here.
     */
    final class AngellEYE_PPCP_Multilingual {

        /**
         * Ensure init() runs once per request.
         * @var bool
         */
        private static $booted = false;

        /**
         * PayPal JS SDK's supported UI locales. Any locale passed to
         * the SDK that isn't in this list is ignored (button renders
         * in English), so we clamp against this list.
         * @var string[]
         */
        private static $supported_locales = array(
            'en_US', 'fr_XC', 'es_XC', 'zh_XC', 'en_AU', 'de_DE', 'nl_NL',
            'fr_FR', 'pt_BR', 'fr_CA', 'zh_CN', 'ru_RU', 'en_GB', 'zh_HK',
            'he_IL', 'it_IT', 'ja_JP', 'pl_PL', 'pt_PT', 'es_ES', 'sv_SE',
            'zh_TW', 'tr_TR',
        );

        /**
         * Load the per-plugin compatibility classes and wire up every
         * cross-cutting hook. Safe to call more than once.
         */
        public static function init() {
            if (self::$booted) {
                return;
            }
            self::$booted = true;

            self::load_plugin_classes();

            // Plugin-specific hook registration. Each per-plugin class
            // owns its own filters (currently only WCML has one).
            if (class_exists('AngellEYE_PPCP_Compatibility_WCML')
                && AngellEYE_PPCP_Compatibility_WCML::is_active()) {
                AngellEYE_PPCP_Compatibility_WCML::register();
            }

            // Cross-cutting hooks. Safe to register unconditionally —
            // they all no-op when no multilingual plugin is active,
            // since they depend on `wpml_language` order meta / active
            // language lookups that return '' without a plugin.
            add_action('woocommerce_new_order', array(__CLASS__, 'stamp_order_language_if_missing'), 10, 1);
            add_action('woocommerce_email_before_order_table', array(__CLASS__, 'maybe_switch_language_for_email'), 1, 4);
            add_action('woocommerce_email_after_order_table', array(__CLASS__, 'maybe_restore_language_after_email'), 9999, 4);
            add_filter('woocommerce_get_checkout_order_received_url', array(__CLASS__, 'translate_order_received_url'), 10, 2);
        }

        private static function load_plugin_classes() {
            $dir = __DIR__ . '/compatibility/';
            if (!class_exists('AngellEYE_PPCP_Compatibility_WPML', false)) {
                require_once $dir . 'class-angelleye-ppcp-compatibility-wpml.php';
            }
            if (!class_exists('AngellEYE_PPCP_Compatibility_Polylang', false)) {
                require_once $dir . 'class-angelleye-ppcp-compatibility-polylang.php';
            }
            if (!class_exists('AngellEYE_PPCP_Compatibility_WCML', false)) {
                require_once $dir . 'class-angelleye-ppcp-compatibility-wcml.php';
            }
        }

        /* -----------------------------------------------------------
         * Public API — locale / language lookups. Internal callers
         * should prefer these over poking at the plugin classes
         * directly, so adding a new multilingual plugin only means
         * editing this class's delegation order.
         * ----------------------------------------------------------- */

        /**
         * Best locale for the PayPal JS SDK's `locale=` param.
         * Clamped to self::$supported_locales; falls back to en_US.
         *
         * @return string
         */
        public static function get_button_locale_code() {
            $locale = self::get_current_language_locale();
            if ($locale && in_array($locale, self::$supported_locales, true)) {
                return $locale;
            }
            $wp_locale = get_locale();
            if ($wp_locale !== '') {
                $wp_locale = substr($wp_locale, 0, 5);
            }
            if (!in_array($wp_locale, self::$supported_locales, true)) {
                return 'en_US';
            }
            return $wp_locale;
        }

        /**
         * Raw locale of the first active multilingual plugin, or false.
         *
         * @return string|false
         */
        public static function get_current_language_locale() {
            self::load_plugin_classes();
            if (AngellEYE_PPCP_Compatibility_WPML::is_active()) {
                $locale = AngellEYE_PPCP_Compatibility_WPML::get_locale();
                if ($locale !== '') {
                    return $locale;
                }
            }
            if (AngellEYE_PPCP_Compatibility_Polylang::is_active()) {
                $locale = AngellEYE_PPCP_Compatibility_Polylang::get_locale();
                if ($locale !== '') {
                    return $locale;
                }
            }
            return false;
        }

        /**
         * Short language code ("de") of the first active plugin, or ''.
         *
         * @return string
         */
        public static function get_current_language_code() {
            self::load_plugin_classes();
            if (AngellEYE_PPCP_Compatibility_WPML::is_active()) {
                $code = AngellEYE_PPCP_Compatibility_WPML::get_language_code();
                if ($code !== '') {
                    return $code;
                }
            }
            if (AngellEYE_PPCP_Compatibility_Polylang::is_active()) {
                $code = AngellEYE_PPCP_Compatibility_Polylang::get_language_code();
                if ($code !== '') {
                    return $code;
                }
            }
            return '';
        }

        /* -----------------------------------------------------------
         * Order language stamping
         * ----------------------------------------------------------- */

        /**
         * On new-order creation, stamp `wpml_language` meta if the
         * checkout integration of WPML/Polylang didn't already do so
         * (admin pay-link orders, API-driven flows, etc.).
         *
         * @param int $order_id
         */
        public static function stamp_order_language_if_missing($order_id) {
            $lang_code = self::get_current_language_code();
            if ($lang_code === '') {
                return;
            }
            $order = wc_get_order($order_id);
            if (!is_a($order, 'WC_Order')) {
                return;
            }
            if (self::get_order_language($order) !== '') {
                return;
            }
            $order->update_meta_data('wpml_language', $lang_code);
            $order->save_meta_data();
        }

        /**
         * Read the stamped language from an order. Checks both the
         * modern `wpml_language` and the legacy `_wpml_language` keys.
         *
         * @param WC_Order|int $order
         * @return string
         */
        public static function get_order_language($order) {
            if (is_numeric($order)) {
                $order = wc_get_order((int) $order);
            }
            if (!is_a($order, 'WC_Order')) {
                return '';
            }
            $lang = (string) $order->get_meta('wpml_language', true);
            if ($lang === '') {
                $lang = (string) $order->get_meta('_wpml_language', true);
            }
            return $lang;
        }

        /* -----------------------------------------------------------
         * Email language switching
         * ----------------------------------------------------------- */

        /**
         * Switch WPML / Polylang into the order's purchase language
         * before WC renders the order table in an email. Restore
         * afterwards. No-op on sites without a multilingual plugin —
         * the `wpml_*` actions just have no listeners.
         *
         * @param WC_Order $order
         * @param bool     $sent_to_admin
         * @param bool     $plain_text
         * @param object   $email
         */
        public static function maybe_switch_language_for_email($order, $sent_to_admin, $plain_text, $email) {
            if (!is_a($order, 'WC_Order')) {
                return;
            }
            $language = self::get_order_language($order);
            if ($language === '') {
                return;
            }
            do_action('wpml_switch_language_for_email', $order->get_id(), $language);
        }

        public static function maybe_restore_language_after_email($order, $sent_to_admin, $plain_text, $email) {
            if (!is_a($order, 'WC_Order')) {
                return;
            }
            if (self::get_order_language($order) === '') {
                return;
            }
            do_action('wpml_restore_language_from_email');
        }

        /* -----------------------------------------------------------
         * URL translation
         * ----------------------------------------------------------- */

        /**
         * Rewrite the WC thank-you URL through `wpml_permalink` so
         * the buyer lands in their purchase language.
         *
         * @param string   $url
         * @param WC_Order $order
         * @return string
         */
        public static function translate_order_received_url($url, $order) {
            if (!is_a($order, 'WC_Order')) {
                return $url;
            }
            $lang_code = self::get_order_language($order);
            if ($lang_code === '') {
                return $url;
            }
            return apply_filters('wpml_permalink', $url, $lang_code);
        }

        /**
         * General-purpose URL translator. Useful for PayPal return /
         * cancel URLs PFW builds manually; callers can pass an
         * explicit language code (from an order) or leave it empty to
         * use the current request's language.
         *
         * @param string $url
         * @param string $lang_code
         * @return string
         */
        public static function translate_permalink($url, $lang_code = '') {
            if ($lang_code === '') {
                $lang_code = self::get_current_language_code();
            }
            if ($lang_code === '') {
                return $url;
            }
            return apply_filters('wpml_permalink', $url, $lang_code);
        }

        /* -----------------------------------------------------------
         * Backward-compat for the wcml text-keys filter callback that
         * earlier versions of PFW exposed as a public method on this
         * class. Delegates to the WCML plugin class.
         * ----------------------------------------------------------- */

        public static function register_gateway_text_keys($text_keys) {
            self::load_plugin_classes();
            return AngellEYE_PPCP_Compatibility_WCML::register_gateway_text_keys($text_keys);
        }
    }

}
