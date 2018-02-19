;
(function ($, window, document) {
    if (angelleye_in_content_param.is_product == 'no') {
        window.paypalCheckoutReady = function () {
            setInterval(function () {
                $('.woocommerce').unblock();
            }, 3000);
            
            var angelleye_button_selector = [];
            
            if( angelleye_in_content_param.is_cart == 'yes') {
                if(angelleye_in_content_param.cart_button_possition == 'both') {
                    angelleye_button_selector.push(".angelleye_smart_button_top", ".angelleye_smart_button_bottom");
                } else if(angelleye_in_content_param.cart_button_possition == 'bottom') {
                    angelleye_button_selector.push(".angelleye_smart_button_bottom");
                } else if (angelleye_in_content_param.cart_button_possition == 'top') {
                    angelleye_button_selector.push(".angelleye_smart_button_top");
                }
            } else if(angelleye_in_content_param.is_checkout == 'yes') {
                angelleye_button_selector.push(".angelleye_smart_button_checkout_top");
            }
            
            angelleye_button_selector.forEach(function (selector) {
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