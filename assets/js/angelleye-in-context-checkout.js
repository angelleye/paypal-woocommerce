jQuery(function ($) {
    if (typeof angelleye_in_content_param === 'undefined') {
        return false;
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
            $('.angelleye_button_single').empty();
            paypal.Buttons({
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
                    window.location.href = window.location.href;
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
                    $('.woocommerce').unblock();
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
                paypal.Buttons({
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
                        window.location.href = window.location.href;
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
                paypal.Buttons({
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
                        window.location.href = window.location.href;
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
                paypal.Buttons({
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
                        window.location.href = window.location.href;
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
                    paypal.Buttons({
                        style: angelleye_cart_style_object,
                        createOrder: function () {
                            $('.woocommerce').block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}});
                            var data = $(selector).closest('form')
                                    .add($('<input type="hidden" name="request_from" /> ')
                                            .attr('value', 'JSv4')
                                            )
                                    .add($('<input type="hidden" name="from_checkout" /> ')
                                            .attr('value', 'yes')
                                            )
                                    .serialize();
                            return paypal.request({
                                method: 'post',
                                url: angelleye_in_content_param.set_express_checkout,
                                body: data,
                            }).then(function (response) {
                                return response.token;
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
                            window.location.href = window.location.href;
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
                            window.location.href = window.location.href;
                        }
                    }).render(selector);
                }
                if (selector === "angelleye_smart_button_checkout_bottom") {
                    return false;
                }
            });
    }

    $(document.body).on('cart_totals_refreshed updated_shipping_method wc_fragments_refreshed updated_checkout updated_wc_div updated_cart_totals wc_fragments_loaded', function (event) {
        display_smart_button_on_cart_checkout();
    });
    if (angelleye_in_content_param.checkout_page_disable_smart_button === "no") {
        $(document.body).on('updated_shipping_method wc_fragments_refreshed updated_checkout', function (event) {
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
        $(document.body).on('updated_checkout wc-credit-card-form-init update_checkout', function (event) {
            angelleye_manage_smart_button();
        });
        function angelleye_manage_smart_button() {
            var is_checked = $('#payment_method_paypal_express').is(':checked');
            if ($('input[name="wc-paypal_express-payment-token"]:checked').length > 0) {
                if (is_checked && $('input[name="wc-paypal_express-payment-token"]').length && $('input[name="wc-paypal_express-payment-token"]:checked').val() === 'new') {
                    $('#place_order').hide();
                    $('.angelleye_smart_button_checkout_bottom').show();
                } else if (is_checked && $('input[name="wc-paypal_express-payment-token"]').length && $('input[name="wc-paypal_express-payment-token"]:checked').val() !== 'new') {
                    $('#place_order').show();
                    $('.angelleye_smart_button_checkout_bottom').hide();
                } else if (is_checked) {
                    $('.angelleye_smart_button_checkout_bottom').show();
                    $('#place_order').hide();
                } else {
                    $('.angelleye_smart_button_checkout_bottom').hide();
                    $('#place_order').show();
                }
            } else {
                if (is_checked) {
                    $('.angelleye_smart_button_checkout_bottom').show();
                    $('#place_order').hide();
                } else {
                    $('.angelleye_smart_button_checkout_bottom').hide();
                    $('#place_order').show();
                }
            }
        }
        $('form.checkout').on('click', 'input[name="payment_method"]', function () {
            angelleye_manage_smart_button();
        });
        $('form.checkout').on('click', 'input[name="wc-paypal_express-payment-token"]', function () {
            if ($(this).val() === 'new') {
                $('#place_order').hide();
                $('.angelleye_smart_button_checkout_bottom').show();
            } else if ($(this).val() !== 'new') {
                $('#place_order').show();
                $('.angelleye_smart_button_checkout_bottom').hide();
            }
        });
    }
});