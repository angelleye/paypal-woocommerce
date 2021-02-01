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
            console.log('32');
            ppcp_production_fields.hide();
            ppcp_production_onboarding_connect_fields.hide();
            ppcp_production_onboarding_disconnect_fields.hide();
            $('#woocommerce_angelleye_ppcp_api_credentials, #woocommerce_angelleye_ppcp_api_credentials + p').hide();
            if (ppcp_angelleye_param.is_sandbox_seller_onboarding_done === 'yes') {
                console.log('38');
                $('#woocommerce_angelleye_ppcp_sandbox_api_credentials, #woocommerce_angelleye_ppcp_sandbox_api_credentials + p').show();
                ppcp_sandbox_fields.show();
                ppcp_sandbox_onboarding_connect_fields.hide();
                ppcp_sandbox_onboarding_disconnect_fields.show();
            } else {
                console.log('44');
                if (ppcp_angelleye_param.angelleye_ppcp_is_local_server === 'yes') {
                    console.log('46');
                    $('#woocommerce_angelleye_ppcp_sandbox_api_credentials, #woocommerce_angelleye_ppcp_sandbox_api_credentials + p').show();
                    ppcp_sandbox_fields.show();
                    ppcp_sandbox_onboarding_connect_fields.hide();
                    ppcp_sandbox_onboarding_disconnect_fields.hide();
                } else {
                    console.log('52');
                    $('#woocommerce_angelleye_ppcp_sandbox_api_credentials, #woocommerce_angelleye_ppcp_sandbox_api_credentials + p').hide();
                    ppcp_sandbox_fields.hide();
                    ppcp_sandbox_onboarding_connect_fields.show();
                    ppcp_sandbox_onboarding_disconnect_fields.hide();
                }

            }
        } else {
            ppcp_sandbox_fields.hide();
            ppcp_sandbox_onboarding_connect_fields.hide();
            ppcp_sandbox_onboarding_disconnect_fields.hide();
            $('#woocommerce_angelleye_ppcp_sandbox_api_credentials, #woocommerce_angelleye_ppcp_sandbox_api_credentials + p').hide();
            if (ppcp_angelleye_param.is_live_seller_onboarding_done === 'yes') {
                $('#woocommerce_angelleye_ppcp_api_credentials, #woocommerce_angelleye_ppcp_api_credentials + p').show();
                ppcp_production_fields.show();
                ppcp_production_onboarding_connect_fields.hide();
                ppcp_production_onboarding_disconnect_fields.show();
            } else {
                if (ppcp_angelleye_param.angelleye_ppcp_is_local_server === 'yes') {
                    $('#woocommerce_angelleye_ppcp_api_credentials, #woocommerce_angelleye_ppcp_api_credentials + p').show();
                    ppcp_production_fields.show();
                    ppcp_production_onboarding_connect_fields.hide();
                    ppcp_production_onboarding_disconnect_fields.hide();
                } else {
                    $('#woocommerce_angelleye_ppcp_api_credentials, #woocommerce_angelleye_ppcp_api_credentials + p').hide();
                    ppcp_production_fields.hide();
                    ppcp_production_onboarding_connect_fields.show();
                    ppcp_production_onboarding_disconnect_fields.hide();
                }

            }
        }
    }).change();
    $(".angelleye_ppcp_gateway_manual_credential_input").on('click', function (e) {
        e.preventDefault();
        var ppcp_production_fields = $('#woocommerce_angelleye_ppcp_live_email_address, #woocommerce_angelleye_ppcp_live_merchant_id, #woocommerce_angelleye_ppcp_live_client_id, #woocommerce_angelleye_ppcp_live_secret_key').closest('tr');
        var ppcp_sandbox_fields = $('#woocommerce_angelleye_ppcp_sandbox_email_address, #woocommerce_angelleye_ppcp_sandbox_merchant_id, #woocommerce_angelleye_ppcp_sandbox_client_id, #woocommerce_angelleye_ppcp_sandbox_secret_key').closest('tr');
        if ($('#woocommerce_angelleye_ppcp_testmode').is(':checked')) {
            ppcp_sandbox_fields.toggle();
            $('#woocommerce_angelleye_ppcp_sandbox_api_credentials, #woocommerce_angelleye_ppcp_sandbox_api_credentials + p').toggle();
        } else {
            ppcp_production_fields.toggle();
            $('#woocommerce_angelleye_ppcp_api_credentials, #woocommerce_angelleye_ppcp_api_credentials + p').toggle();
        }
    });
    $(".angelleye-ppcp-disconnect").click(function () {
        if ($('#woocommerce_angelleye_ppcp_testmode').is(':checked')) {
            $('#woocommerce_angelleye_ppcp_sandbox_email_address, #woocommerce_angelleye_ppcp_sandbox_merchant_id, #woocommerce_angelleye_ppcp_sandbox_client_id, #woocommerce_angelleye_ppcp_sandbox_secret_key').val('');
        } else {
            $('#woocommerce_angelleye_ppcp_live_email_address, #woocommerce_angelleye_ppcp_live_merchant_id, #woocommerce_angelleye_ppcp_live_client_id, #woocommerce_angelleye_ppcp_live_secret_key').val('');
        }
        $('.woocommerce-save-button').click();
    });
    
    $('#woocommerce_angelleye_ppcp_enable_product_button_settings').change(function () {
        if ($(this).is(':checked')) {
            $('.angelleye_ppcp_product_button_settings').closest('tr').show();
        } else {
            $('.angelleye_ppcp_product_button_settings').closest('tr').hide();
        }
    }).change();
    $('#woocommerce_angelleye_ppcp_enable_cart_button_settings').change(function () {
        if ($(this).is(':checked')) {
            $('.angelleye_ppcp_cart_button_settings').closest('tr').show();
        } else {
            $('.angelleye_ppcp_cart_button_settings').closest('tr').hide();
        }
    }).change();
    $('#woocommerce_angelleye_ppcp_enable_checkout_button_settings').change(function () {
        if ($(this).is(':checked')) {
            $('.angelleye_ppcp_checkout_button_settings').closest('tr').show();
        } else {
            $('.angelleye_ppcp_checkout_button_settings').closest('tr').hide();
        }
    }).change();
    $('#woocommerce_angelleye_ppcp_enable_mini_cart_button_settings').change(function () {
        if ($(this).is(':checked')) {
            $('.angelleye_ppcp_mini_cart_button_settings').closest('tr').show();
        } else {
            $('.angelleye_ppcp_mini_cart_button_settings').closest('tr').hide();
        }
    }).change();

});
   