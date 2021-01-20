;
(function ($) {
    'use strict';
    $(function () {
        $('#woocommerce_angelleye_ppcp_testmode').change(function () {
            var production = jQuery('#woocommerce_angelleye_ppcp_live_email_address, #woocommerce_angelleye_ppcp_live_merchant_id, #woocommerce_angelleye_ppcp_live_client_id, #woocommerce_angelleye_ppcp_live_secret_key').closest('tr');
            var sandbox = jQuery('#woocommerce_angelleye_ppcp_sandbox_email_address, #woocommerce_angelleye_ppcp_sandbox_merchant_id, #woocommerce_angelleye_ppcp_sandbox_client_id, #woocommerce_angelleye_ppcp_sandbox_secret_key').closest('tr');
            if ($(this).is(':checked')) {
                sandbox.show();
                $('#woocommerce_angelleye_ppcp_live_onboarding, #woocommerce_angelleye_ppcp_live_onboarding + p, #woocommerce_angelleye_ppcp_live_disconnect, #woocommerce_angelleye_ppcp_live_disconnect + p').hide();
                $('#woocommerce_angelleye_ppcp_sandbox_onboarding, #woocommerce_angelleye_ppcp_sandbox_onboarding + p, #woocommerce_angelleye_ppcp_sandbox_disconnect , #woocommerce_angelleye_ppcp_sandbox_disconnect + p').show();
                production.hide();
            } else {
                sandbox.hide();
                $('#woocommerce_angelleye_ppcp_live_onboarding, #woocommerce_angelleye_ppcp_live_onboarding + p, #woocommerce_angelleye_ppcp_live_disconnect, #woocommerce_angelleye_ppcp_live_disconnect + p').show();
                $('#woocommerce_angelleye_ppcp_sandbox_onboarding, #woocommerce_angelleye_ppcp_sandbox_onboarding + p, #woocommerce_angelleye_ppcp_sandbox_disconnect , #woocommerce_angelleye_ppcp_sandbox_disconnect + p').hide();
                production.show();
            }
        }).change();
    });
}(jQuery));