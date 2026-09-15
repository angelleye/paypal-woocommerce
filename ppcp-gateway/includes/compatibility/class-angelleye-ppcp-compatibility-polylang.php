<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('AngellEYE_PPCP_Compatibility_Polylang', false)) {

    /**
     * All Polylang-specific code lives here. Detection and language /
     * locale lookup via the `pll_*` functions. Kept independent of
     * WPML — old code had Polylang branches unreachable because they
     * were nested inside a WPML constant guard; here they stand alone.
     */
    class AngellEYE_PPCP_Compatibility_Polylang {

        /**
         * @return bool True when Polylang (free or Pro) is active.
         */
        public static function is_active() {
            return function_exists('pll_current_language');
        }

        /**
         * @return string Short language code or ''.
         */
        public static function get_language_code() {
            if (function_exists('pll_current_language')) {
                $code = pll_current_language();
                if (!empty($code)) {
                    return (string) $code;
                }
            }
            if (function_exists('pll_default_language')) {
                $code = pll_default_language();
                if (!empty($code)) {
                    return (string) $code;
                }
            }
            return '';
        }

        /**
         * @return string Full locale or ''.
         */
        public static function get_locale() {
            if (function_exists('pll_current_language')) {
                $locale = pll_current_language('locale');
                if (!empty($locale)) {
                    return (string) $locale;
                }
            }
            if (function_exists('pll_default_language')) {
                $locale = pll_default_language('locale');
                if (!empty($locale)) {
                    return (string) $locale;
                }
            }
            return '';
        }
    }

}
