jQuery(function ($) {
    if (typeof angelleye_in_content_param === 'undefined') {
        return false;
    }
    display_smart_button_on_cart_checkout();
    display_smart_button_on_min_cart();
    display_smart_button_on_product_page();
    display_smart_button_on_wsc_cart();
    function is_funding_icon_should_show_in_content() {
        var disallowed_funding_methods = angelleye_in_content_param.disallowed_funding_methods;
        if (disallowed_funding_methods === null) {
            disallowed_funding_methods = [];
        }
        if ($.inArray('card', disallowed_funding_methods) > -1) {
            return false;
        } else {
            return true;
        }
    }
    function display_smart_button_on_product_page() {
        if ($('.angelleye_button_single').length > 0) {
            $('.angelleye_button_single').empty();
            window.paypalCheckoutReady = function () {
                allowed_funding_methods_single_array = $.parseJSON(angelleye_in_content_param.allowed_funding_methods);
                disallowed_funding_methods_single_array = $.parseJSON(angelleye_in_content_param.disallowed_funding_methods);
                if (angelleye_in_content_param.is_paypal_credit_enable == "no") {
                    disallowed_funding_methods_single_array.push("credit");
                }
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
                    tagline: (angelleye_in_content_param.button_tagline === "true") ? true : false
                };
                if (angelleye_in_content_param.button_layout === 'horizontal' && is_funding_icon_should_show_in_content() === true && angelleye_in_content_param.button_label !== 'credit' && angelleye_in_content_param.button_fundingicons === "true") {
                    angelleye_cart_style_object['fundingicons'] = (angelleye_in_content_param.button_fundingicons === "true") ? true : false;
                }
                $('.angelleye_button_single').empty();
                paypal.Button.render({
                    env: angelleye_in_content_param.environment,
                    style: angelleye_cart_style_object,
                    locale: angelleye_in_content_param.locale,
                    commit: (angelleye_in_content_param.zcommit === "false") ? false : true,
                    funding: {
                        allowed: allowed_funding_methods_single_array,
                        disallowed: disallowed_funding_methods_single_array
                    },
                    payment: function (data, actions) {
                        $('.cart').block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}});
                        var data_param = {
                            'nonce': angelleye_in_content_param.generate_cart_nonce,
                            'qty': $('.quantity .qty').val(),
                            'attributes': $('.variations_form').length ? JSON.stringify(get_attributes().data) : [],
                            'wc-paypal_express-new-payment-method': $("#wc-paypal_express-new-payment-method").is(':checked'),
                            'is_cc': '',
                            'product_id': $("input[name=add-to-cart]").val(),
                            'variation_id': $("input[name=variation_id]").val(),
                            'request_from': 'JSv4',
                            'express_checkout': 'true'
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
                    onClick: function () {
                        if (angelleye_in_content_param.enable_google_analytics_click === 'yes') {
                            if (typeof ga != 'undefined' && $.isFunction(ga)) {
                                ga('send', {
                                    hitType: 'event',
                                    eventCategory: 'Checkout',
                                    eventAction: 'button_click'
                                });
                            }
                        }
                    },
                    onError: function (err, actions) {
                        $('.cart').unblock();
                        if ($('.angelleye_button_single').length) {
                            window.location.href = angelleye_in_content_param.cancel_page;
                        }
                    }
                }, '.angelleye_button_single');
            };
        }
    }

    function display_smart_button_on_cart_checkout() {
        window.paypalCheckoutReady = function () {
            var angelleye_button_selector = [];
            var allowed_funding_methods_var = [];
            var disallowed_funding_methods_var = [];
            if (angelleye_in_content_param.is_checkout == 'yes' && angelleye_in_content_param.is_display_on_checkout == 'yes') {
                angelleye_button_selector.push(".angelleye_smart_button_checkout_top");
            }
            if (angelleye_in_content_param.is_cart == 'yes') {
                if (angelleye_in_content_param.cart_button_possition == 'both') {
                    angelleye_button_selector.push(".angelleye_smart_button_top", ".angelleye_smart_button_bottom");
                } else if (angelleye_in_content_param.cart_button_possition == 'bottom') {
                    angelleye_button_selector.push(".angelleye_smart_button_bottom");
                } else if (angelleye_in_content_param.cart_button_possition == 'top') {
                    angelleye_button_selector.push(".angelleye_smart_button_top");
                }
            } 
            disallowed_funding_methods_var = $.parseJSON(angelleye_in_content_param.disallowed_funding_methods);
            allowed_funding_methods_var = $.parseJSON(angelleye_in_content_param.allowed_funding_methods);
            if (angelleye_in_content_param.is_paypal_credit_enable == "no") {
                disallowed_funding_methods_var.push("credit");
            }
            angelleye_cart_style_object = {size: angelleye_in_content_param.button_size,
                color: angelleye_in_content_param.button_color,
                shape: angelleye_in_content_param.button_shape,
                label: angelleye_in_content_param.button_label,
                layout: angelleye_in_content_param.button_layout,
                tagline: (angelleye_in_content_param.button_tagline === "true") ? true : false
            };
            angelleye_button_selector.forEach(function (selector) {
                $(selector).html("");
                disallowed_funding_methods_var = $.grep(disallowed_funding_methods_var, function (value) {
                    return value !== 'venmo';
                });
                if (selector.length > 0 && $(selector).length > 0) {
                    if (angelleye_in_content_param.button_layout === 'horizontal' && is_funding_icon_should_show_in_content() === true && angelleye_in_content_param.button_label !== 'credit') {
                        if (angelleye_in_content_param.button_fundingicons === 'true') {
                            angelleye_cart_style_object['fundingicons'] = (angelleye_in_content_param.button_fundingicons === "true") ? true : false;
                        }
                    }
                    paypal.Button.render({
                        env: angelleye_in_content_param.environment,
                        style: angelleye_cart_style_object,
                        locale: angelleye_in_content_param.locale,
                        commit: (angelleye_in_content_param.zcommit === "false") ? false : true,
                        funding: {
                            allowed: allowed_funding_methods_var,
                            disallowed: disallowed_funding_methods_var
                        },
                        payment: function () {
                            $('.woocommerce').block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}});
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
                            $('.woocommerce').unblock();
                            return actions.redirect();
                        },
                        onClick: function () {
                            if (angelleye_in_content_param.enable_google_analytics_click === 'yes') {
                                if (typeof ga != 'undefined' && $.isFunction(ga)) {
                                    ga('send', {
                                        hitType: 'event',
                                        eventCategory: 'Checkout',
                                        eventAction: 'paypal_button_click'
                                    });
                                }
                            }
                        },
                        onError: function (err, actions) {
                            $('.woocommerce').unblock();
                            window.location.href = angelleye_in_content_param.cancel_page;
                        }
                    }, selector);
                }
                if(selector === "angelleye_smart_button_checkout_top") {
                    return false;
                }
            });
        };
    }
    
    function display_smart_button_on_min_cart() {
        window.paypalCheckoutReady = function () {
            var angelleye_button_selector = [];
            var allowed_funding_methods_var = [];
            var disallowed_funding_methods_var = [];
            angelleye_button_selector.push(".angelleye_smart_button_mini");
            disallowed_funding_methods_var = $.parseJSON(angelleye_in_content_param.mini_cart_disallowed_funding_methods);
            allowed_funding_methods_var = $.parseJSON(angelleye_in_content_param.mini_cart_allowed_funding_methods);
            if (angelleye_in_content_param.is_paypal_credit_enable == "no") {
                disallowed_funding_methods_var.push("credit");
            }
            angelleye_cart_style_object = {size: angelleye_in_content_param.mini_cart_button_size,
                color: angelleye_in_content_param.button_color,
                shape: angelleye_in_content_param.button_shape,
                label: angelleye_in_content_param.button_label,
                layout: angelleye_in_content_param.mini_cart_button_layout,
                tagline: (angelleye_in_content_param.button_tagline === "true") ? true : false
            };
            angelleye_button_selector.forEach(function (selector) {
                $(selector).html("");
                disallowed_funding_methods_var = $.grep(disallowed_funding_methods_var, function (value) {
                    return value !== 'venmo';
                });
                if (selector.length > 0 && $(selector).length > 0) {
                    angelleye_cart_style_object['size'] = 'responsive';
                    if (angelleye_in_content_param.button_layout === 'horizontal' && is_funding_icon_should_show_in_content() === true && angelleye_in_content_param.button_label !== 'credit') {
                        if (angelleye_in_content_param.button_fundingicons === 'true') {
                            angelleye_cart_style_object['fundingicons'] = (angelleye_in_content_param.button_fundingicons === "true") ? true : false;
                        }
                    }
                    paypal.Button.render({
                        env: angelleye_in_content_param.environment,
                        style: angelleye_cart_style_object,
                        locale: angelleye_in_content_param.locale,
                        commit: (angelleye_in_content_param.zcommit === "false") ? false : true,
                        funding: {
                            allowed: allowed_funding_methods_var,
                            disallowed: disallowed_funding_methods_var
                        },
                        payment: function () {
                            $('.woocommerce').block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}});
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
                            $('.woocommerce').unblock();
                            return actions.redirect();
                        },
                        onClick: function () {
                            if (angelleye_in_content_param.enable_google_analytics_click === 'yes') {
                                if (typeof ga != 'undefined' && $.isFunction(ga)) {
                                    ga('send', {
                                        hitType: 'event',
                                        eventCategory: 'Checkout',
                                        eventAction: 'paypal_button_click'
                                    });
                                }
                            }
                        },
                        onError: function (err, actions) {
                            $('.woocommerce').unblock();
                            window.location.href = angelleye_in_content_param.cancel_page;
                        }
                    }, selector);
                }
            });
        };
    }
    
    function display_smart_button_on_wsc_cart() {
        window.paypalCheckoutReady = function () {
            var angelleye_button_selector = [];
            var allowed_funding_methods_var = [];
            var disallowed_funding_methods_var = [];
            angelleye_button_selector.push(".angelleye_smart_button_wsc");
            disallowed_funding_methods_var = $.parseJSON(angelleye_in_content_param.wsc_cart_disallowed_funding_methods);
            allowed_funding_methods_var = $.parseJSON(angelleye_in_content_param.wsc_cart_allowed_funding_methods);
            if (angelleye_in_content_param.is_paypal_credit_enable == "no") {
                disallowed_funding_methods_var.push("credit");
            }
            angelleye_cart_style_object = {size: angelleye_in_content_param.wsc_cart_button_size,
                color: angelleye_in_content_param.button_color,
                shape: angelleye_in_content_param.button_shape,
                label: angelleye_in_content_param.button_label,
                layout: angelleye_in_content_param.wsc_cart_button_layout,
                tagline: (angelleye_in_content_param.button_tagline === "true") ? true : false
            };
            angelleye_button_selector.forEach(function (selector) {
                $(selector).html("");
                disallowed_funding_methods_var = $.grep(disallowed_funding_methods_var, function (value) {
                    return value !== 'venmo';
                });
                if (selector.length > 0 && $(selector).length > 0) {
                    angelleye_cart_style_object['size'] = 'responsive';
                    if (angelleye_in_content_param.button_layout === 'horizontal' && is_funding_icon_should_show_in_content() === true && angelleye_in_content_param.button_label !== 'credit') {
                        if (angelleye_in_content_param.button_fundingicons === 'true') {
                            angelleye_cart_style_object['fundingicons'] = (angelleye_in_content_param.button_fundingicons === "true") ? true : false;
                        }
                    }
                    paypal.Button.render({
                        env: angelleye_in_content_param.environment,
                        style: angelleye_cart_style_object,
                        locale: angelleye_in_content_param.locale,
                        commit: (angelleye_in_content_param.zcommit === "false") ? false : true,
                        funding: {
                            allowed: allowed_funding_methods_var,
                            disallowed: disallowed_funding_methods_var
                        },
                        payment: function () {
                            $('.woocommerce').block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}});
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
                            $('.woocommerce').unblock();
                            return actions.redirect();
                        },
                        onClick: function () {
                            if (angelleye_in_content_param.enable_google_analytics_click === 'yes') {
                                if (typeof ga != 'undefined' && $.isFunction(ga)) {
                                    ga('send', {
                                        hitType: 'event',
                                        eventCategory: 'Checkout',
                                        eventAction: 'paypal_button_click'
                                    });
                                }
                            }
                        },
                        onError: function (err, actions) {
                            $('.woocommerce').unblock();
                            window.location.href = angelleye_in_content_param.cancel_page;
                        }
                    }, selector);
                }
            });
        };
    }
    
    function display_smart_button_checkout_bottom() {
        window.paypalCheckoutReady = function () {
            var angelleye_button_selector = [];
            var allowed_funding_methods_var = [];
            var disallowed_funding_methods_var = [];
            angelleye_button_selector.push(".angelleye_smart_button_checkout_bottom");
            disallowed_funding_methods_var = $.parseJSON(angelleye_in_content_param.disallowed_funding_methods);
            allowed_funding_methods_var = $.parseJSON(angelleye_in_content_param.allowed_funding_methods);
            if (angelleye_in_content_param.is_paypal_credit_enable == "no") {
                disallowed_funding_methods_var.push("credit");
            }
            angelleye_cart_style_object = {size: angelleye_in_content_param.button_size,
                color: angelleye_in_content_param.button_color,
                shape: angelleye_in_content_param.button_shape,
                label: angelleye_in_content_param.button_label,
                layout: angelleye_in_content_param.button_layout,
                tagline: (angelleye_in_content_param.button_tagline === "true") ? true : false
            };
            angelleye_button_selector.forEach(function (selector) {
                $(selector).html("");
                disallowed_funding_methods_var = $.grep(disallowed_funding_methods_var, function (value) {
                    return value !== 'venmo';
                });
                if (selector.length > 0 && $(selector).length > 0) {
                    if (angelleye_in_content_param.button_layout === 'horizontal' && is_funding_icon_should_show_in_content() === true && angelleye_in_content_param.button_label !== 'credit') {
                        if (angelleye_in_content_param.button_fundingicons === 'true') {
                            angelleye_cart_style_object['fundingicons'] = (angelleye_in_content_param.button_fundingicons === "true") ? true : false;
                        }
                    }
                    paypal.Button.render({
                        env: angelleye_in_content_param.environment,
                        style: angelleye_cart_style_object,
                        locale: angelleye_in_content_param.locale,
                        commit: (angelleye_in_content_param.zcommit === "false") ? false : true,
                        funding: {
                            allowed: allowed_funding_methods_var,
                            disallowed: disallowed_funding_methods_var
                        },
                        payment: function () {
                            $('.woocommerce').block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}});
                            
                            
                            var data = $( selector ).closest( 'form' )
						.add( $( '<input type="hidden" name="request_from" /> ' )
							.attr( 'value', 'JSv4' )
						)
						.add( $( '<input type="hidden" name="from_checkout" /> ' )
							.attr( 'value', 'yes' )
						)
						.serialize();

					return paypal.request( {
						method: 'post',
						url: angelleye_in_content_param.set_express_checkout,
						body: data,
					} ).then( function( response ) {
        			            return response.token;
					} );
                          
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
                            $('.woocommerce').unblock();
                            return actions.redirect();
                        },
                        onClick: function () {
                            if (angelleye_in_content_param.enable_google_analytics_click === 'yes') {
                                if (typeof ga != 'undefined' && $.isFunction(ga)) {
                                    ga('send', {
                                        hitType: 'event',
                                        eventCategory: 'Checkout',
                                        eventAction: 'paypal_button_click'
                                    });
                                }
                            }
                        },
                        onError: function (err, actions) {
                            $('.woocommerce').unblock();
                            window.location.href = angelleye_in_content_param.cancel_page;
                        }
                    }, selector);
                }
                if(selector === "angelleye_smart_button_checkout_bottom") {
                    return false;
                }
            });
        };
    }
    
    $(document.body).on('updated_shipping_method wc_fragments_refreshed updated_checkout updated_wc_div updated_cart_totals wc_fragments_loaded', function (event) {
        display_smart_button_on_cart_checkout();
    });
    if(angelleye_in_content_param.checkout_page_disable_smart_button === "no") {
        $(document.body).on('updated_shipping_method wc_fragments_refreshed updated_checkout', function (event) {
            display_smart_button_checkout_bottom();
        });
    }
    $( document.body ).on( 'wc_fragments_loaded wc_fragments_refreshed', function() {
            var $button = $( '.angelleye_smart_button_mini' );
            if ( $button.length ) {
                    $button.empty();
                    display_smart_button_on_min_cart();
            }
            var $single = $('.angelleye_button_single');
            if ( $single.length ) {
                    $single.empty();
                    display_smart_button_on_product_page();
            }
            var $button_wsc = $( '.angelleye_smart_button_wsc' );
            if ( $button_wsc.length ) {
                    $button_wsc.empty();
                    display_smart_button_on_wsc_cart();
            }
    } );
    if(angelleye_in_content_param.checkout_page_disable_smart_button === "no") {
        $( 'form.checkout' ).on( 'click', 'input[name="payment_method"]', function() {
                var isPPEC = $( this ).is( '#payment_method_paypal_express' );
                if ($('input[name="wc-paypal_express-payment-token"]').length && $('input[name="wc-paypal_express-payment-token"]').val() === 'new') {
                    $( '#place_order' ).toggle( ! isPPEC );
                    $( '.angelleye_smart_button_checkout_bottom' ).toggle( isPPEC );
                } else if($('input[name="wc-paypal_express-payment-token"]').length && $('input[name="wc-paypal_express-payment-token"]').val() !== 'new') {
                    $( '#place_order' ).toggle( isPPEC );
                    $( '.angelleye_smart_button_checkout_bottom' ).hide();
                } else {
                    $( '#place_order' ).toggle( ! isPPEC );
                    $( '.angelleye_smart_button_checkout_bottom' ).toggle( isPPEC );
                }
        } );
        
        $( 'form.checkout' ).on( 'click', 'input[name="wc-paypal_express-payment-token"]', function() {
                if ($(this).val() === 'new') {
                    $( '#place_order' ).hide();
                    $( '.angelleye_smart_button_checkout_bottom' ).show();
                } else if($(this).val() !== 'new') {
                    $( '#place_order' ).show();
                    $( '.angelleye_smart_button_checkout_bottom' ).hide();
                } 
        });
    }
});