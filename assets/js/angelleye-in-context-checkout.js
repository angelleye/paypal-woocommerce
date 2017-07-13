;
(function ($, window, document) {
    if (angelleye_in_content_param.is_product == 'no') {
       $angelleye_ec_in_content = {
            init: function () {
                window.paypalCheckoutReady = function () {
                    setInterval(function () {
                        $('.woocommerce').unblock();
                    }, 3000);
                    [ '.paypal_checkout_button', '.paypal_checkout_button_cc_top', '.paypal_checkout_button_cc_bottom', '.paypal_checkout_button_top', '.paypal_checkout_button_bottom', '.paypal_checkout_button_cc'].forEach(function(selector) {
                        paypal.checkout.setup(
                            angelleye_in_content_param.payer_id,
                            {
                                environment: angelleye_in_content_param.environment,
                                button: selector,
                                locale: angelleye_in_content_param.locale
                            }
                        );
                     });
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
                'nonce': angelleye_in_content_param.update_shipping_costs_nonce
            };
            $.ajax({
                type: 'POST',
                data: data,
                url: angelleye_in_content_param.ajaxurl,
                success: function (response) {
                    costs_updated = true;
                    $( this ).click();
                }
            });
        });
        
        if (angelleye_in_content_param.show_modal) {
            $angelleye_ec_in_content.init();
        }
        
    } else {
        
        window.paypalCheckoutReady = function () {
            paypal.checkout.setup(angelleye_in_content_param.payer_id, {
                environment: angelleye_in_content_param.environment,
                click: function (event) {
                    $( '.cart' ).block({
                            message: null,
                            overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                            }
	            });
                    var get_attributes = function () {
                        var select = $('.variations_form').find('.variations select'),
                                data = {},
                                count = 0,
                                chosen = 0;

                        select.each(function () {
                            var attribute_name = $(this).data('attribute_name') || $(this).attr('name');
                            var value = $(this).val() || '';

                            if (value.length > 0) {
                                chosen++;
                            }

                            count++;
                            data[ attribute_name ] = value;
                        });
                        return {
                            'count': count,
                            'chosenCount': chosen,
                            'data': data
                        };
                    };
                    event.preventDefault();
                    
                    var data = {
                            'nonce':      angelleye_in_content_param.generate_cart_nonce,
                            'qty':        $( '.quantity .qty' ).val(),
                            'attributes': $( '.variations_form' ).length ? get_attributes().data : [],
                            'wc-paypal_express-new-payment-method' : $("#wc-paypal_express-new-payment-method").is(':checked')
                    };

                    $.ajax({
                        type: 'POST',
                        data: data,
                        url: angelleye_in_content_param.add_to_cart_ajaxurl,
                        success: function (data) {
                            $('.cart').unblock();
                            paypal.checkout.startFlow(data.url);
                        },
                        error: function (e) {
                            alert("Error in ajax post:" + e.statusText);
                            $('.cart').unblock();
                            paypal.checkout.closeFlow();
                        }
                    });
                },
                button: ['.paypal_checkout_button', '.paypal_checkout_button_cc']
            });
        }
        
    }
})(jQuery, window, document);