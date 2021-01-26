function onboardingCallback(authCode, sharedId) {
    console.log('ajaxurl : ' + ajaxurl);
    console.log('authCode : ' + authCode);
    console.log('sharedId : ' + sharedId);
    console.log(ppcp_angelleye_param);
    const is_sandbox = document.querySelector('#woocommerce_angelleye_ppcp_testmode');
    fetch(ppcp_angelleye_param.angelleye_ppcp_onboarding_endpoint, {
        method: 'POST',
        headers: {
            'content-type': 'application/json'
        },
        body: JSON.stringify({
            authCode: authCode,
            sharedId: sharedId,
            nonce: ppcp_angelleye_param.angelleye_ppcp_onboarding_endpoint_nonce,
            env: is_sandbox && is_sandbox.checked ? 'sandbox' : 'production'
        })
    });
}
jQuery(function ($) {
    if (typeof ppcp_angelleye_param === 'undefined') {
        return false;
    }
    $('#woocommerce_angelleye_ppcp_testmode').change(function () {
        var ppcp_production_fields = $('#woocommerce_angelleye_ppcp_live_email_address, #woocommerce_angelleye_ppcp_live_merchant_id, #woocommerce_angelleye_ppcp_live_client_id, #woocommerce_angelleye_ppcp_live_secret_key').closest('tr');
        var ppcp_sandbox_fields = $('#woocommerce_angelleye_ppcp_sandbox_email_address, #woocommerce_angelleye_ppcp_sandbox_merchant_id, #woocommerce_angelleye_ppcp_sandbox_client_id, #woocommerce_angelleye_ppcp_sandbox_secret_key').closest('tr');
        var ppcp_production_onboarding_connect_fields = $('#woocommerce_angelleye_ppcp_live_onboarding').closest('tr').hide();
        var ppcp_sandbox_onboarding_connect_fields = $('#woocommerce_angelleye_ppcp_sandbox_onboarding').closest('tr').show();
        var ppcp_production_onboarding_disconnect_fields = $('#woocommerce_angelleye_ppcp_live_disconnect').closest('tr').hide();
        var ppcp_sandbox_onboarding_disconnect_fields = $('#woocommerce_angelleye_ppcp_sandbox_disconnect').closest('tr').show();
        if ($(this).is(':checked')) {
            ppcp_production_fields.hide();
            ppcp_production_onboarding_connect_fields.hide();
            ppcp_production_onboarding_disconnect_fields.hide();
            if (ppcp_angelleye_param.is_sandbox_seller_onboarding_done === 'yes') {
                $('#woocommerce_angelleye_ppcp_api_credentials').show();
                ppcp_sandbox_fields.show();
                ppcp_sandbox_onboarding_connect_fields.hide();
                ppcp_sandbox_onboarding_disconnect_fields.show();
            } else {
                $('#woocommerce_angelleye_ppcp_api_credentials').hide();
                ppcp_sandbox_fields.hide();
                ppcp_sandbox_onboarding_connect_fields.show();
                ppcp_sandbox_onboarding_disconnect_fields.hide();
            }
        } else {
            ppcp_sandbox_fields.hide();
            ppcp_sandbox_onboarding_connect_fields.hide();
            ppcp_sandbox_onboarding_disconnect_fields.hide();
            if (ppcp_angelleye_param.is_live_seller_onboarding_done === 'yes') {
                $('#woocommerce_angelleye_ppcp_api_credentials').show();
                ppcp_production_fields.show();
                ppcp_production_onboarding_connect_fields.hide();
                ppcp_production_onboarding_disconnect_fields.show();
            } else {
                $('#woocommerce_angelleye_ppcp_api_credentials').hide();
                ppcp_production_fields.hide();
                ppcp_production_onboarding_connect_fields.show();
                ppcp_production_onboarding_disconnect_fields.hide();
            }
        }
    }).change();
    $(".angelleye-ppcp-disconnect").click(function () {
        if ($('#woocommerce_angelleye_ppcp_testmode').is(':checked')) {
            $('#woocommerce_angelleye_ppcp_sandbox_email_address, #woocommerce_angelleye_ppcp_sandbox_merchant_id, #woocommerce_angelleye_ppcp_sandbox_client_id, #woocommerce_angelleye_ppcp_sandbox_secret_key').val('');
        } else {
            $('#woocommerce_angelleye_ppcp_live_email_address, #woocommerce_angelleye_ppcp_live_merchant_id, #woocommerce_angelleye_ppcp_live_client_id, #woocommerce_angelleye_ppcp_live_secret_key').val('');
        }
        $('.woocommerce-save-button').click();
    });
});
   