<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('AngellEYE_PPCP_Compatibility_WPML', false)) {

    /**
     * All WPML-specific code lives here. Detection, current language
     * lookup, locale lookup — anything that references WPML constants
     * or `$sitepress` globals. Keeps WPML knowledge out of every
     * other file in the plugin.
     */
    class AngellEYE_PPCP_Compatibility_WPML {

        /**
         * @return bool True when WPML core is installed and active.
         */
        public static function is_active() {
            return defined('ICL_LANGUAGE_CODE') && function_exists('icl_object_id');
        }

        /**
         * @return string Short language code (e.g. "de"), or ''.
         */
        public static function get_language_code() {
            if (defined('ICL_LANGUAGE_CODE') && ICL_LANGUAGE_CODE) {
                return (string) ICL_LANGUAGE_CODE;
            }
            return '';
        }

        /**
         * @return string Full locale (e.g. "de_DE") via $sitepress,
         *                or '' when the sitepress global isn't ready.
         */
        public static function get_locale() {
            global $sitepress;
            if (!isset($sitepress) || !is_object($sitepress)) {
                return '';
            }
            if (!method_exists($sitepress, 'get_current_language')) {
                return '';
            }
            $code = $sitepress->get_current_language();
            if (empty($code)) {
                return '';
            }
            if (method_exists($sitepress, 'get_locale_from_language_code')) {
                $locale = $sitepress->get_locale_from_language_code($code);
                if (!empty($locale)) {
                    return (string) $locale;
                }
            }
            return (string) $code;
        }
    }

}
