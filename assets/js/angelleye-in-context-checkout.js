;
(function ($, window, document) {
    var $angelleye_ec_in_content = {
        init: function () {
            window.paypalCheckoutReady = function () {
                paypal.checkout.setup(
                        angelleye_in_content_param.payer_id,
                        {
                            environment: angelleye_in_content_param.environment,
                            button: ['.paypal_checkout_button', '.paypal_checkout_button_cc'],
                            locale: angelleye_in_content_param.locale,
                            container: ['.paypal_checkout_button', '.paypal_checkout_button_cc']
                        }
                );
                paypal.checkout.closeFlow(
                        jQuery(document.body).unblock()
                )
            }
        }
    }
    
    var costs_updated = false;
    $('a.paypal_checkout_button', 'a.paypal_checkout_button_cc').click(function (event) {
        if (costs_updated) {
            costs_updated = false;
            return;
        }
        event.stopPropagation();
        var data = {
            'nonce': angelleye_in_content_param.update_shipping_costs_nonce,
        };
        var href = $(this).attr('href');
        $.ajax({
            type: 'POST',
            data: data,
            url: angelleye_in_content_param.ajaxurl,
            success: function (response) {
                costs_updated = true;
                $('a.paypal_checkout_button').click();
            }
        });
    });
    if (angelleye_in_content_param.show_modal) {
        $angelleye_ec_in_content.init();
    }
})(jQuery, window, document);