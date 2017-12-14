;
(function ($, window, document) {
    if (angelleye_in_content_param.is_product == 'no') {
        var target_url = '';
        window.paypalCheckoutReady = function () {
            setInterval(function () {
                $('.woocommerce').unblock();
            }, 3000);
            ['.paypal_checkout_button', '.paypal_checkout_button_cc_top', '.paypal_checkout_button_cc_bottom', '.paypal_checkout_button_top', '.paypal_checkout_button_bottom', '.paypal_checkout_button_cc'].forEach(function (selector) {
                paypal.checkout.setup(
                        angelleye_in_content_param.payer_id,
                        {
                            environment: angelleye_in_content_param.environment,
                            button: selector,
                            locale: angelleye_in_content_param.locale,
                            click: function (event) {
                                event.preventDefault();
                                paypal.checkout.initXO();
                                target_url = $(event.target).parent().attr("href");
                                if (typeof target_url === 'undefined' || target_url === null) {
                                    target_url = $(event.target).attr("href");
                                }
                                if ($("#wc-paypal_express-new-payment-method").is(':checked')) {
                                    target_url = target_url + "&ec_save_to_account=true";
                                }
                                paypal.checkout.startFlow(target_url);
                            }
                        }
                );
            });
        };
    } else {
        window.paypalCheckoutReady = function () {
            paypal.checkout.setup(angelleye_in_content_param.payer_id, {
                environment: angelleye_in_content_param.environment,
                click: function (event) {
                    event.preventDefault();
                    paypal.checkout.initXO();
                    $('.cart').block({
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
                    var data = {
                        'nonce': angelleye_in_content_param.generate_cart_nonce,
                        'qty': $('.quantity .qty').val(),
                        'attributes': $('.variations_form').length ? get_attributes().data : [],
                        'wc-paypal_express-new-payment-method': $("#wc-paypal_express-new-payment-method").is(':checked'),
                        'is_cc': $(event.target).hasClass('ec_checkout_page_button_type_pc'),
                        'product_id' : $("input[name=add-to-cart]").val()
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
                button: ['.paypal_checkout_button', '.paypal_checkout_button_cc'],
                condition: function () {
                    if ($('.paypal_checkout_button').hasClass("disabled")) {
                        return false;
                    } else {
                        return true;
                    }
                }
            });
        };
    }
})(jQuery, window, document);