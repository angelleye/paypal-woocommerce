<?php

defined('ABSPATH') || exit;

if (!function_exists('is_angelleye_ec_review_page')) {

    /**
     * is_angelleye_ec_review_page - Returns true when viewing the Express Checkout order review page.
     *
     * @return bool
     */
    function is_angelleye_ec_review_page() {
        $paypal_express_checkout = angelleye_get_session('paypal_express_checkout');
        if (isset($paypal_express_checkout['token']) && !empty($paypal_express_checkout['token']) && isset($paypal_express_checkout['payer_id']) && !empty($paypal_express_checkout['payer_id'])) {
            return true;
        } else {
            return false;
        }
    }

}
if (!function_exists('is_angelleye_multi_account_active')) {

    function is_angelleye_multi_account_active() {
        if (function_exists('run_paypal_for_woocommerce_multi_account_management')) {
            return true;
        }
        return false;
    }

}

if (!function_exists('angelleye_is_us_based_store')) {

    function angelleye_is_us_based_store() {
        $base_location = wc_get_base_location();
        return in_array($base_location['country'], array('US', 'PR', 'GU', 'VI', 'AS', 'MP'), true);
    }

}
