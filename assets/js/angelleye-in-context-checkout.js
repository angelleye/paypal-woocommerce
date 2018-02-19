;
(function ($, window, document) {
    if (angelleye_in_content_param.is_product == 'no') {
        var target_url = '';
        window.paypalCheckoutReady = function () {
            setInterval(function () {
                $('.woocommerce').unblock();
            }, 3000);
            
            ['.angelleye_smart_button_top', '.angelleye_smart_button_bottom', '.angelleye_smart_button_checkout_top'].forEach(function (selector) {
            paypal.Button.render({
                env: 'sandbox',
                style: {
                    size: 'medium',
                    layout: 'vertical',
                    shape: 'rect',
                    tagline: false
                },
                funding: {
                    allowed: [paypal.FUNDING.CREDIT]
                },
                client: {
                    sandbox: 'testoneusa_api1.gmail.com'
                },
                payment: function () {
                    $('.cart').block({
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                    return paypal.request.post(angelleye_in_content_param.set_express_checkout).then(function (data) {
                        return data.token;
                    });
                },
                onAuthorize: function (data, actions) {
                    var params = {
                        paymentToken: data.paymentToken,
                        payerID: data.payerID,
                        token: data.paymentToken
                    };
                    paypal.request.post(data.returnUrl, params).then(function (res) {
                        data.returnUrl = res.url;
                        actions.redirect();
                    });
                },
                onCancel: function (data, actions) {
                    return actions.redirect();
                },
                onError: function (err) {
                    return actions.redirect();
                }
            }, selector );
            
            });
            
            //angelleye_in_content_param.set_express_checkout
            
            /*['.paypal_checkout_button', '.paypal_checkout_button_cc_top', '.paypal_checkout_button_cc_bottom', '.paypal_checkout_button_top', '.paypal_checkout_button_bottom', '.paypal_checkout_button_cc'].forEach(function (selector) {
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
            });*/
        };
    } else {
        window.paypalCheckoutReady = function () {
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
            paypal.Button.render({
                env: 'sandbox',
                style: {
                    size: 'medium',
                    shape: 'rect',
                    tagline: false
                },
                funding: {
                    allowed: [paypal.FUNDING.CREDIT]
                },
                client: {
                    sandbox: 'testoneusa_api1.gmail.com'
                },
                payment: function (data, actions) {
                    console.log(data);
                    $('.cart').block({
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                    var data_param = {
                        'nonce': angelleye_in_content_param.generate_cart_nonce,
                        'qty': $('.quantity .qty').val(),
                        'attributes': $('.variations_form').length ? get_attributes().data : [],
                        'wc-paypal_express-new-payment-method': $("#wc-paypal_express-new-payment-method").is(':checked'),
                        'is_cc': '',
                        'product_id': $("input[name=add-to-cart]").val()
                    };
                    return paypal.request.post(angelleye_in_content_param.add_to_cart_ajaxurl, data_param).then(function (data) {
                        return paypal.request.post(data.url).then(function (res) {
                            return res.token;
                        });
                    });
                },
                onAuthorize: function (data, actions) {
                    var params = {
                        paymentToken: data.paymentToken,
                        payerID: data.payerID,
                        token: data.paymentToken
                    };
                    paypal.request.post(data.returnUrl, params).then(function (res) {
                        data.returnUrl = res.url;
                        actions.redirect();
                    });
                },
                onCancel: function (data, actions) {
                    return actions.redirect();
                },
                onError: function (err) {
                    return actions.redirect();
                }
            }, '.angelleye_button_single');
        };
    }
})(jQuery, window, document);