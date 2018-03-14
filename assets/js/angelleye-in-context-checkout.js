;
(function ($, window, document) {
     function is_funding_icon_should_show_in_content() {
        var disallowed_funding_methods = jQuery('#woocommerce_paypal_express_disallowed_funding_methods').val();
        if (disallowed_funding_methods === null) {
            disallowed_funding_methods = [];
        }
        if (jQuery.inArray('card', disallowed_funding_methods)  > -1 ) {
            return false;
        } else {
            return true;
        }
    }
    if (angelleye_in_content_param.is_product == 'no') {
        window.paypalCheckoutReady = function () {
            var angelleye_button_selector = [];
            if (angelleye_in_content_param.is_cart == 'yes') {
                if (angelleye_in_content_param.cart_button_possition == 'both') {
                    angelleye_button_selector.push(".angelleye_smart_button_top", ".angelleye_smart_button_bottom");
                } else if (angelleye_in_content_param.cart_button_possition == 'bottom') {
                    angelleye_button_selector.push(".angelleye_smart_button_bottom");
                } else if (angelleye_in_content_param.cart_button_possition == 'top') {
                    angelleye_button_selector.push(".angelleye_smart_button_top");
                }
            } else if (angelleye_in_content_param.is_checkout == 'yes') {
                angelleye_button_selector.push(".angelleye_smart_button_checkout_top");
            }
            allowed_funding_methods_var = jQuery.parseJSON(angelleye_in_content_param.allowed_funding_methods);
            disallowed_funding_methods_var = jQuery.parseJSON(angelleye_in_content_param.disallowed_funding_methods);
            if (angelleye_in_content_param.is_us_or_uk == "no") {
                angelleye_in_content_param.disallowed_funding_methods.push("credit");
            }
            angelleye_cart_style_object = {size: angelleye_in_content_param.button_size,
                color: angelleye_in_content_param.button_color,
                shape: angelleye_in_content_param.button_shape,
                label: angelleye_in_content_param.button_label,
                layout: angelleye_in_content_param.button_layout,
                tagline: angelleye_in_content_param.button_tagline

            };
            
            angelleye_button_selector.forEach(function (selector) {
                if(selector.length > 0) {
                    if (angelleye_in_content_param.button_layout === 'horizontal' && is_funding_icon_should_show_in_content() === true && angelleye_in_content_param.button_label !== 'credit') {
                        angelleye_cart_style_object['fundingicons'] = angelleye_in_content_param.button_fundingicons;
                    }
                    paypal.Button.render({
                        env: angelleye_in_content_param.environment,
                        style: angelleye_cart_style_object,
                        funding: {
                            allowed: allowed_funding_methods_var,
                            disallowed: jQuery.parseJSON(angelleye_in_content_param.disallowed_funding_methods)
                        },
                        client: {
                            sandbox: angelleye_in_content_param.payer_id,
                            production: angelleye_in_content_param.payer_id
                        },
                        payment: function () {
                            $(selector).block({
                                message: null,
                                overlayCSS: {
                                    background: '#fff',
                                    opacity: 0.6
                                }
                            });
                            var data_param = {
                                request_from: 'JSv4'
                            };
                            
                            return paypal.request.post(angelleye_in_content_param.set_express_checkout, data_param).then(function (data) {
                                return data.token;
                            });
                        },
                        onAuthorize: function (data, actions) {
                            var params = {
                                paymentToken: data.paymentToken,
                                payerID: data.payerID,
                                token: data.paymentToken,
                                request_from: 'JSv4'

                            };
                            paypal.request.post(data.returnUrl, params).then(function (res) {
                                data.returnUrl = res.url;
                                actions.redirect();
                            });
                        },
                        onCancel: function (data, actions) {
                            $(selector).unblock();
                            return actions.redirect();
                        },
                        onError: function (err, actions) {
                            $(selector).unblock();
                           window.location.href = angelleye_in_content_param.cancel_page;
                        }
                    }, selector);
                }
                });
                
        };
    } else {

        if( jQuery('.angelleye_button_single').length ) { 
       

        angelleye_in_content_param.allowed_funding_methods = jQuery.parseJSON(angelleye_in_content_param.allowed_funding_methods);
        angelleye_in_content_param.disallowed_funding_methods = jQuery.parseJSON(angelleye_in_content_param.disallowed_funding_methods);
        
        if (angelleye_in_content_param.is_us_or_uk == "no") {
            angelleye_in_content_param.disallowed_funding_methods.push("credit");
        }
        
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
            angelleye_cart_style_object = {size: angelleye_in_content_param.button_size,
                color: angelleye_in_content_param.button_color,
                shape: angelleye_in_content_param.button_shape,
                label: angelleye_in_content_param.button_label,
                layout: angelleye_in_content_param.button_layout,
                tagline: angelleye_in_content_param.button_tagline
            };
            
            if (angelleye_in_content_param.button_layout === 'horizontal' && is_funding_icon_should_show_in_content() === true && angelleye_in_content_param.button_label !== 'credit' && angelleye_in_content_param.button_fundingicons === "true") {
                angelleye_cart_style_object['fundingicons'] = angelleye_in_content_param.button_fundingicons;
            }
            
            
            
            paypal.Button.render({
                env: angelleye_in_content_param.environment,
                style: angelleye_cart_style_object,
                funding: {
                    allowed: angelleye_in_content_param.allowed_funding_methods,
                    disallowed: angelleye_in_content_param.disallowed_funding_methods
                },
                client: {
                    sandbox: angelleye_in_content_param.payer_id
                },
                payment: function (data, actions) {
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
                        'product_id': $("input[name=add-to-cart]").val(),
                        'request_from': 'JSv4'
                    };
                    return paypal.request.post(angelleye_in_content_param.add_to_cart_ajaxurl, data_param).then(function (data) {
                        var params = {
                            request_from: 'JSv4'
                        };
                        return paypal.request.post(data.url, params).then(function (res) {
                            return res.token;
                        });
                    });
                },
                onAuthorize: function (data, actions) {
                    var params = {
                        paymentToken: data.paymentToken,
                        payerID: data.payerID,
                        token: data.paymentToken,
                        request_from: 'JSv4'
                    };
                    paypal.request.post(data.returnUrl, params).then(function (res) {
                        data.returnUrl = res.url;
                        actions.redirect();
                    });
                },
                onCancel: function (data, actions) {
                     $('.cart').unblock();
                    return actions.redirect();
                },
                onError: function (err, actions) {
                    $('.cart').unblock();
                    if( jQuery('.angelleye_button_single').length ) { 
                        window.location.href = angelleye_in_content_param.cancel_page;
                    }
                    
                }
            }, '.angelleye_button_single');
        };
    }
    }
})(jQuery, window, document);