jQuery(function ($) {
    if (typeof angelleye_in_content_param === 'undefined') {
        return false;
    }
    var angelleye_hide_button = function () {
        $('#place_order').show();
        $('.angelleye_pp_message_payment:eq(1)').hide();
        $('.angelleye_smart_button_checkout_bottom').hide();
    };

    var angelleye_is_paypal_js_loaded = function () {
        if (typeof paypal_sdk === 'undefined') {
            console.log("PayPal Js not loaded");
            return false;
        }
    };
    
    var angelleye_show_button = function () {
        $('#place_order').hide();
        $('.angelleye_pp_message_payment:eq(1)').show();
        $('.angelleye_smart_button_checkout_bottom').show();
    };
    
    var angelleye_manage_smart_button = function () {
            var is_checked = $('#payment_method_paypal_express').is(':checked');
            if ($('input[name="wc-paypal_express-payment-token"]:checked').length > 0) {
                if (is_checked && $('input[name="wc-paypal_express-payment-token"]').length && $('input[name="wc-paypal_express-payment-token"]:checked').val() === 'new') {
                    angelleye_show_button();
                } else if (is_checked && $('input[name="wc-paypal_express-payment-token"]').length && $('input[name="wc-paypal_express-payment-token"]:checked').val() !== 'new') {
                    angelleye_hide_button();
                } else if (is_checked) {
                    angelleye_show_button();
                } else {
                    angelleye_hide_button();
                }
            } else {
                if (is_checked) {
                    angelleye_show_button();
                } else {
                    angelleye_hide_button();
                }
            }
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

    if (angelleye_in_content_param.is_checkout !== 'yes') {
        display_smart_button_on_cart_checkout();
    }
    display_smart_button_on_min_cart();
    display_smart_button_on_product_page();
    display_smart_button_on_wsc_cart();
    function display_smart_button_on_product_page() {
        if ($('.angelleye_button_single').length > 0) {
            $('.angelleye_button_single').empty();
            angelleye_cart_style_object = {
                color: angelleye_in_content_param.button_color,
                shape: angelleye_in_content_param.button_shape,
                label: angelleye_in_content_param.button_label,
                layout: angelleye_in_content_param.button_layout,
                tagline: (angelleye_in_content_param.button_tagline === "true") ? true : false
            };
            if (typeof angelleye_in_content_param.button_height !== "undefined" && angelleye_in_content_param.button_height !== '') {
                angelleye_cart_style_object['height'] = parseInt(angelleye_in_content_param.button_height);
            }
            $(".angelleye_button_single").removeClass("angelleye_horizontal_small angelleye_horizontal_medium angelleye_horizontal_large angelleye_vertical_small angelleye_vertical_medium angelleye_vertical_large");
            $('.angelleye_button_single').addClass('angelleye_' + angelleye_in_content_param.button_layout + '_' + angelleye_in_content_param.button_size);
            angelleye_is_paypal_js_loaded();
            paypal_sdk.Buttons({
                style: angelleye_cart_style_object,
                createOrder: function () {
                    var data_param = {
                        'nonce': angelleye_in_content_param.generate_cart_nonce,
                        'qty': $('.quantity .qty').val(),
                        'attributes': $('.variations_form').length ? JSON.stringify(get_attributes().data) : [],
                        'is_cc': '',
                        'product_id': $("input[name=add-to-cart]").val(),
                        'variation_id': $("input[name=variation_id]").val(),
                        'request_from': 'JSv4',
                        'express_checkout': 'true'
                    };
                    var angelleye_action;
                    angelleye_action = angelleye_in_content_param.add_to_cart_ajaxurl;
                    if ($("#wc-paypal_express-new-payment-method").is(':checked')) {
                        angelleye_action = angelleye_action + '&ec_save_to_account=true';
                    }
                    return $.post(angelleye_action, data_param).then(function (data) {
                        var params = {
                            request_from: 'JSv4'
                        };
                        return $.post(data.url, params).then(function (res) {
                            return res.token;
                        });
                    });
                },
                onApprove: function (data, actions) {
                    $('.woocommerce').block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}});
                    var params = {
                        paymentToken: data.orderID,
                        payerID: data.payerID,
                        token: data.orderID,
                        request_from: 'JSv4'
                    };
                    $.post(angelleye_in_content_param.get_express_checkout_details + '&token=' + data.orderID, params).then(function (res) {
                        window.location.href = res.url;
                    });
                },
                onCancel: function (data, actions) {
                    $('.woocommerce').unblock();
                    $(document.body).trigger('angelleye_paypal_oncancel');
                    window.location.href = window.location.href;
                },
                onClick: function () {
                    $(document.body).trigger('angelleye_paypal_onclick');
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
                    $('.woocommerce').unblock();
                    $(document.body).trigger('angelleye_paypal_onerror');
                    window.location.href = angelleye_in_content_param.cancel_page;
                }
            }).render('.angelleye_button_single');
        }
    }
    function display_smart_button_on_cart_checkout() {
        var angelleye_button_selector = [];
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
        angelleye_cart_style_object = {
            color: angelleye_in_content_param.button_color,
            shape: angelleye_in_content_param.button_shape,
            label: angelleye_in_content_param.button_label,
            layout: angelleye_in_content_param.button_layout,
            tagline: (angelleye_in_content_param.button_tagline === "true") ? true : false
        };
        if (typeof angelleye_in_content_param.button_height !== "undefined" && angelleye_in_content_param.button_height !== '') {
            angelleye_cart_style_object['height'] = parseInt(angelleye_in_content_param.button_height);
        }
        angelleye_button_selector.forEach(function (selector) {
            $(selector).html("");
            if (selector.length > 0 && $(selector).length > 0) {
                $(selector).removeClass("angelleye_horizontal_small angelleye_horizontal_medium angelleye_horizontal_large angelleye_vertical_small angelleye_vertical_medium angelleye_vertical_large");
                $(selector).addClass('angelleye_' + angelleye_in_content_param.button_layout + '_' + angelleye_in_content_param.button_size);
                angelleye_is_paypal_js_loaded();
                paypal_sdk.Buttons({
                    style: angelleye_cart_style_object,
                    createOrder: function () {
                        var data_param = {
                            'request_from': 'JSv4'
                        };
                        var angelleye_action;
                        angelleye_action = angelleye_in_content_param.set_express_checkout;
                        if ($("#wc-paypal_express-new-payment-method").is(':checked')) {
                            angelleye_action = angelleye_action + '&ec_save_to_account=true';
                        } else if ($("#wc-paypal_express-new-payment-method_bottom").is(':checked')) {
                            angelleye_action = angelleye_action + '&ec_save_to_account=true';
                        }
                        return $.post(angelleye_action, data_param).then(function (data) {
                            return data.token;
                        });
                    },
                    onApprove: function (data, actions) {
                        $('.woocommerce').block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}});
                        var params = {
                            paymentToken: data.orderID,
                            payerID: data.payerID,
                            token: data.orderID,
                            request_from: 'JSv4'
                        };
                        $.post(angelleye_in_content_param.get_express_checkout_details + '&token=' + data.orderID, params).then(function (res) {
                            window.location.href = res.url;
                        });
                    },
                    onCancel: function (data, actions) {
                        $('.woocommerce').unblock();
                        $(document.body).trigger('angelleye_paypal_oncancel');
                        window.location.href = window.location.href;
                    },
                    onClick: function () {
                        $(document.body).trigger('angelleye_paypal_onclick');
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
                        $(document.body).trigger('angelleye_paypal_onerror');
                        window.location.href = angelleye_in_content_param.cancel_page;
                    }
                }).render(selector);
            }
            if (selector === "angelleye_smart_button_checkout_top") {
                return false;
            }
        });
    }

    function display_smart_button_on_min_cart() {

        var angelleye_button_selector = [];

        angelleye_button_selector.push(".angelleye_smart_button_mini");

        angelleye_cart_style_object = {
            color: angelleye_in_content_param.button_color,
            shape: angelleye_in_content_param.button_shape,
            label: angelleye_in_content_param.mini_cart_button_label,
            layout: angelleye_in_content_param.mini_cart_button_layout,
            tagline: (angelleye_in_content_param.button_tagline === "true") ? true : false
        };
        if (typeof angelleye_in_content_param.mini_cart_button_height !== "undefined" && angelleye_in_content_param.mini_cart_button_height !== '') {
            angelleye_cart_style_object['height'] = parseInt(angelleye_in_content_param.mini_cart_button_height);
        }
        angelleye_button_selector.forEach(function (selector) {
            $(selector).html("");
            if (selector.length > 0 && $(selector).length > 0) {
                $(selector).removeClass("angelleye_horizontal_small angelleye_horizontal_medium angelleye_horizontal_large angelleye_vertical_small angelleye_vertical_medium angelleye_vertical_large");
                $(selector).addClass('angelleye_' + angelleye_in_content_param.button_layout + '_' + angelleye_in_content_param.button_size);
                angelleye_is_paypal_js_loaded();
                paypal_sdk.Buttons({
                    style: angelleye_cart_style_object,
                    createOrder: function () {
                        var data_param = {
                            request_from: 'JSv4'
                        };
                        var angelleye_action;
                        angelleye_action = angelleye_in_content_param.set_express_checkout;
                        if ($("#wc-paypal_express-new-payment-method").is(':checked')) {
                            angelleye_action = angelleye_action + '&ec_save_to_account=true';
                        }
                        return $.post(angelleye_action, data_param).then(function (data) {
                            return data.token;
                        });
                    },
                    onApprove: function (data, actions) {
                        $('.woocommerce').block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}});
                        var params = {
                            paymentToken: data.orderID,
                            payerID: data.payerID,
                            token: data.orderID,
                            request_from: 'JSv4'
                        };
                        $.post(angelleye_in_content_param.get_express_checkout_details + '&token=' + data.orderID, params).then(function (res) {
                            window.location.href = res.url;
                        });
                    },
                    onCancel: function (data, actions) {
                        $('.woocommerce').unblock();
                        $(document.body).trigger('angelleye_paypal_oncancel');
                        window.location.href = window.location.href;
                    },
                    onClick: function () {
                        $(document.body).trigger('angelleye_paypal_onclick');
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
                        $(document.body).trigger('angelleye_paypal_onerror');
                        window.location.href = angelleye_in_content_param.cancel_page;
                    }
                }).render(selector);
            }
        });

    }

    function display_smart_button_on_wsc_cart() {
        var angelleye_button_selector = [];
        angelleye_button_selector.push(".angelleye_smart_button_wsc");
        angelleye_cart_style_object = {
            color: angelleye_in_content_param.button_color,
            shape: angelleye_in_content_param.button_shape,
            label: angelleye_in_content_param.wsc_cart_button_label,
            layout: angelleye_in_content_param.wsc_cart_button_layout,
            tagline: (angelleye_in_content_param.button_tagline === "true") ? true : false
        };
        if (typeof angelleye_in_content_param.wsc_cart_button_height !== "undefined" && angelleye_in_content_param.wsc_cart_button_height !== '') {
            angelleye_cart_style_object['height'] = parseInt(angelleye_in_content_param.wsc_cart_button_height);
        }
        angelleye_button_selector.forEach(function (selector) {
            $(selector).html("");
            if (selector.length > 0 && $(selector).length > 0) {
                $(selector).removeClass("angelleye_horizontal_small angelleye_horizontal_medium angelleye_horizontal_large angelleye_vertical_small angelleye_vertical_medium angelleye_vertical_large");
                $(selector).addClass('angelleye_' + angelleye_in_content_param.wsc_cart_button_layout + '_' + angelleye_in_content_param.wsc_cart_button_size);
                angelleye_is_paypal_js_loaded();
                paypal_sdk.Buttons({
                    style: angelleye_cart_style_object,
                    createOrder: function () {
                        var data_param = {
                            request_from: 'JSv4'
                        };
                        var angelleye_action;
                        angelleye_action = angelleye_in_content_param.set_express_checkout;
                        if ($("#wc-paypal_express-new-payment-method").is(':checked')) {
                            angelleye_action = angelleye_action + '&ec_save_to_account=true';
                        }
                        return $.post(angelleye_action, data_param).then(function (data) {
                            return data.token;
                        });
                    },
                    onApprove: function (data, actions) {
                        $('.woocommerce').block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}});
                        var params = {
                            paymentToken: data.orderID,
                            payerID: data.payerID,
                            token: data.orderID,
                            request_from: 'JSv4'
                        };
                        $.post(angelleye_in_content_param.get_express_checkout_details + '&token=' + data.orderID, params).then(function (res) {
                            window.location.href = res.url;
                        });
                    },
                    onCancel: function (data, actions) {
                        $('.woocommerce').unblock();
                        $(document.body).trigger('angelleye_paypal_oncancel');
                        window.location.href = window.location.href;
                    },
                    onClick: function () {
                        $(document.body).trigger('angelleye_paypal_onclick');
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
                        $(document.body).trigger('angelleye_paypal_onerror');
                        window.location.href = angelleye_in_content_param.cancel_page;
                    }
                }).render(selector);
            }
        });
    }

    function display_smart_button_checkout_bottom() {
        var angelleye_button_selector = [];
        angelleye_button_selector.push(".angelleye_smart_button_checkout_bottom");
        angelleye_cart_style_object = {
            color: angelleye_in_content_param.button_color,
            shape: angelleye_in_content_param.button_shape,
            label: angelleye_in_content_param.button_label,
            layout: angelleye_in_content_param.button_layout,
            tagline: (angelleye_in_content_param.button_tagline === "true") ? true : false
        };
        if (typeof angelleye_in_content_param.button_height !== "undefined" && angelleye_in_content_param.button_height !== '') {
            angelleye_cart_style_object['height'] = parseInt(angelleye_in_content_param.button_height);
        }
        angelleye_button_selector.forEach(function (selector) {
            $(selector).html("");
            if (selector.length > 0 && $(selector).length > 0) {
                $(selector).removeClass("angelleye_horizontal_small angelleye_horizontal_medium angelleye_horizontal_large angelleye_vertical_small angelleye_vertical_medium angelleye_vertical_large");
                $(selector).addClass('angelleye_' + angelleye_in_content_param.button_layout + '_' + angelleye_in_content_param.button_size);
                angelleye_is_paypal_js_loaded();
                paypal_sdk.Buttons({
                    style: angelleye_cart_style_object,
                    createOrder: function () {
                        var data = $(selector).closest('form')
                                .add($('<input type="hidden" name="request_from" /> ')
                                        .attr('value', 'JSv4')
                                        )
                                .add($('<input type="hidden" name="from_checkout" /> ')
                                        .attr('value', 'yes')
                                        )
                                .serialize();

                        return $.post(angelleye_in_content_param.set_express_checkout, data).then(function (data) {
                            return data.token;
                        });

                    },
                    onApprove: function (data, actions) {
                        $('.woocommerce').block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}});
                        var params = {
                            paymentToken: data.orderID,
                            payerID: data.payerID,
                            token: data.orderID,
                            request_from: 'JSv4'
                        };
                        $.post(angelleye_in_content_param.get_express_checkout_details + '&token=' + data.orderID, params).then(function (res) {
                            if (angelleye_in_content_param.is_pre_checkout_offer === "no") {
                                window.location.href = res.url;
                            } else {
                                $('.woocommerce').unblock();
                                $('form.checkout').triggerHandler("checkout_place_order");
                            }

                        });
                    },
                    onCancel: function (data, actions) {
                        $('.woocommerce').unblock();
                        $(document.body).trigger('angelleye_paypal_oncancel');
                        window.location.href = window.location.href;
                    },
                    onClick: function () {
                        $(document.body).trigger('angelleye_paypal_onclick');
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
                        $(document.body).trigger('angelleye_paypal_onerror');
                        window.location.href = window.location.href;
                    }
                }).render(selector);
            }
            if (selector === "angelleye_smart_button_checkout_bottom") {
                return false;
            }
        });
    }

    $(document.body).on('updated_cart_totals updated_checkout', function (event) {
        display_smart_button_on_cart_checkout();
    });
    if (angelleye_in_content_param.checkout_page_disable_smart_button === "no") {
        $(document.body).on('updated_cart_totals updated_checkout', function (event) {
            display_smart_button_checkout_bottom();
        });
    }
    $(document.body).on('wc_fragments_loaded wc_fragments_refreshed', function () {
        var $button = $('.angelleye_smart_button_mini');
        if ($button.length) {
            $button.empty();
            display_smart_button_on_min_cart();
        }
        var $single = $('.angelleye_button_single');
        if ($single.length) {
            $single.empty();
            display_smart_button_on_product_page();
        }
        var $button_wsc = $('.angelleye_smart_button_wsc');
        if ($button_wsc.length) {
            $button_wsc.empty();
            display_smart_button_on_wsc_cart();
        }
    });

    if (angelleye_in_content_param.checkout_page_disable_smart_button === "no") {
        $(document.body).on('updated_cart_totals updated_checkout', function (event) {
            angelleye_manage_smart_button();
        });
        
        $('form.checkout').on('click', 'input[name="payment_method"]', function () {
            angelleye_manage_smart_button();
        });
        $('form.checkout').on('click', 'input[name="wc-paypal_express-payment-token"]', function () {
            if ($(this).val() === 'new') {
                angelleye_show_button();
            } else if ($(this).val() !== 'new') {
                angelleye_hide_button();
            }
        });
    }
});