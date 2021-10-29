(function ($) {
    'use strict';
    $(function () {
        if (typeof angelleye_ppcp_manager === 'undefined') {
            return false;
        }
        var selector = '#angelleye_ppcp_' + angelleye_ppcp_manager.page;
        if ($('.variations_form').length) {
            $('.variations_form').on('show_variation', function () {
                $(selector).show();
            }).on('hide_variation', function () {
                $(selector).hide();
            });
        }
        $.angelleye_ppcp_scroll_to_notices = function (scrollElement) {
            if (scrollElement.length) {
                $('html, body').animate({
                    scrollTop: (scrollElement.offset().top - 100)
                }, 1000);
            }
        };
        var showError = function (errorMessage, selector) {
            var $container = $('.woocommerce-notices-wrapper');
            if (!$container || !$container.length) {
                $(selector).prepend(errorMessage);
                return;
            } else {
                $container = $container.first();
            }
            $('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
            $container.prepend('<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + errorMessage + '</div>');
            $container.find('.input-text, select, input:checkbox').trigger('validate').blur();
            $.angelleye_ppcp_scroll_to_notices($('.woocommerce'));
            $(document.body).trigger('checkout_error');
        };
        var is_from_checkout = 'checkout' === angelleye_ppcp_manager.page;
        var is_from_product = 'product' === angelleye_ppcp_manager.page;
        var is_sale = 'capture' === angelleye_ppcp_manager.paymentaction;
        var smart_button_render = function () {
            
            if (!$(selector).length || $(selector).children().length) {
                return;
            }
            if (typeof angelleye_paypal_sdk === 'undefined') {
                return;
            }
            var angelleye_ppcp_style = {
                layout: angelleye_ppcp_manager.style_layout,
                color: angelleye_ppcp_manager.style_color,
                shape: angelleye_ppcp_manager.style_shape,
                label: angelleye_ppcp_manager.style_label
            };
            if (angelleye_ppcp_manager.style_layout !== 'vertical') {
                angelleye_ppcp_style['tagline'] = (angelleye_ppcp_manager.style_tagline === 'yes') ? true : false;
            }
            angelleye_paypal_sdk.Buttons({
                style: angelleye_ppcp_style,
                createOrder: function (data, actions) {
                    var data;
                    if (is_from_checkout) {
                        data = $(selector).closest('form').serialize();
                    } else if (is_from_product) {
                        var add_to_cart = $("button[name='add-to-cart']").val();
                        $('<input>', {
                            type: 'hidden',
                            name: 'angelleye_ppcp-add-to-cart',
                            value: add_to_cart
                        }).appendTo('form.cart');
                        data = $('form.cart').serialize();
                    }
                    return fetch(angelleye_ppcp_manager.create_order_url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: data
                    }).then(function (res) {
                        return res.json();
                    }).then(function (data) {
                        if (typeof data.success !== 'undefined') {
                            var messages = data.data.messages ? data.data.messages : data.data;
                            if ('string' === typeof messages) {
                                showError('<ul class="woocommerce-error" role="alert">' + messages + '</ul>', $('form'));
                            } else {
                                var messageItems = messages.map(function (message) {
                                    return '<li>' + message + '</li>';
                                }).join('');
                                showError('<ul class="woocommerce-error" role="alert">' + messageItems + '</ul>', $('form'));
                            }
                            return null;
                        } else {
                            return data.orderID;
                        }
                    });
                },
                onApprove: function (data, actions) {
                    $('.woocommerce').block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}});
                    if (is_from_checkout) {
                        if (angelleye_ppcp_manager.is_pre_checkout_offer === "yes") {
                            $('.woocommerce').unblock();
                            $('form.checkout').triggerHandler("checkout_place_order");
                        } else {
                            if (is_sale) {
                                actions.order.capture().then(function (details) {
                                    if (details.error === 'INSTRUMENT_DECLINED') {
                                        return actions.restart();
                                    } else {
                                        actions.redirect(angelleye_ppcp_manager.display_order_page + '&paypal_order_id=' + data.orderID + '&paypal_payer_id=' + data.payerID + '&paypal_payment_id=' + details.id + '&from=' + angelleye_ppcp_manager.page);
                                    }
                                });
                            } else {
                                actions.order.authorize().then(function (authorization) {
                                    if (authorization.error === 'INSTRUMENT_DECLINED') {
                                        return actions.restart();
                                    } else {
                                        var authorizationID = authorization.purchase_units[0].payments.authorizations[0].id;
                                        actions.redirect(angelleye_ppcp_manager.display_order_page + '&paypal_order_id=' + data.orderID + '&paypal_payer_id=' + data.payerID + '&paypal_payment_id=' + authorizationID + '&from=' + angelleye_ppcp_manager.page);
                                    }
                                });
                            }
                        }
                    } else {
                        if (angelleye_ppcp_manager.is_skip_final_review === 'yes') {
                            actions.redirect(angelleye_ppcp_manager.direct_capture + '&paypal_order_id=' + data.orderID + '&paypal_payer_id=' + data.payerID + '&from=' + angelleye_ppcp_manager.page);
                        } else {
                            actions.redirect(angelleye_ppcp_manager.checkout_url + '&paypal_order_id=' + data.orderID + '&paypal_payer_id=' + data.payerID + '&from=' + angelleye_ppcp_manager.page);
                        }
                    }
                },
                onCancel: function (data, actions) {
                    $('.woocommerce').unblock();
                    $(document.body).trigger('angelleye_paypal_oncancel');
                    if (is_from_checkout === false) {
                        window.location.href = window.location.href;
                    }
                },
                onError: function (err) {
                    console.log(err);
                    $('.woocommerce').unblock();
                    $(document.body).trigger('angelleye_paypal_onerror');
                    if (is_from_checkout === false) {
                        window.location.href = window.location.href;
                    }
                }
            }).render(selector);
        };
        $('form.checkout').on('checkout_place_order_angelleye_ppcp', function (event) {
            if (is_angelleye_ppcp_selected()) {
                if (is_hosted_field_eligible() === true) {
                    event.preventDefault();
                    if ($('form.checkout').is('.paypal_cc_submiting')) {
                        return false;
                    } else {
                        $('form.checkout').addClass('paypal_cc_submiting');
                        $(document.body).trigger('submit_paypal_cc_form');
                    }
                    return false;
                }
            }
            return true;
        });
        var hosted_button_render = function () {
            if ($('form.checkout').is('.HostedFields')) {
                return false;
            }
            if (typeof angelleye_paypal_sdk === 'undefined') {
                return;
            }
            $('form.checkout').addClass('HostedFields');
            angelleye_paypal_sdk.HostedFields.render({
                createOrder: function () {
                    if ($('form.checkout').is('.createOrder') === false) {
                        $('form.checkout').addClass('createOrder');
                        var data;
                        if (is_from_checkout) {
                            data = $('form.checkout').serialize();
                        }
                        return fetch(angelleye_ppcp_manager.create_order_url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: data
                        }).then(function (res) {
                            return res.json();
                        }).then(function (data) {
                            if (typeof data.success !== 'undefined') {
                                var messages = data.data.messages ? data.data.messages : data.data;
                                if ('string' === typeof messages) {
                                    showError('<ul class="woocommerce-error" role="alert">' + messages + '</ul>', $('form'));
                                } else {
                                    var messageItems = messages.map(function (message) {
                                        return '<li>' + message + '</li>';
                                    }).join('');
                                    showError('<ul class="woocommerce-error" role="alert">' + messageItems + '</ul>', $('form'));
                                }
                                return null;
                            } else {
                                return data.orderID;
                            }
                        });
                    }
                },
                onCancel: function (data, actions) {
                    actions.redirect(angelleye_ppcp_manager.cancel_url);
                },
                onError: function (err) {

                },
                styles: {
                    'input': {
                        'font-size': '1.3em'
                    }
                },
                fields: {
                    number: {
                        selector: '#angelleye_ppcp-card-number',
                        placeholder: '•••• •••• •••• ••••',
                        addClass: 'input-text wc-credit-card-form-card-number'
                    },
                    cvv: {
                        selector: '#angelleye_ppcp-card-cvc',
                        placeholder: 'CVC'
                    },
                    expirationDate: {
                        selector: '#angelleye_ppcp-card-expiry',
                        placeholder: 'MM / YY'
                    }
                }
            }).then(function (hf) {
                hf.on('cardTypeChange', function (event) {
                    if (event.cards.length === 1) {
                        $('#angelleye_ppcp-card-number').removeClass().addClass(event.cards[0].type.replace("master-card", "mastercard").replace("american-express", "amex").replace("diners-club", "dinersclub").replace("-", ""));
                        $('#angelleye_ppcp-card-number').addClass("input-text wc-credit-card-form-card-number hosted-field-braintree braintree-hosted-fields-valid");
                    }
                });
                $(document.body).on('submit_paypal_cc_form', function (event) {
                    event.preventDefault();
                    var state = hf.getState();
                    var contingencies = [];
                    if (angelleye_ppcp_manager.threed_secure_enabled === 'yes') {
                        contingencies = ['SCA_WHEN_REQUIRED'];
                    }
                    $('form.checkout').addClass('processing').block({
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                    $.angelleye_ppcp_scroll_to_notices($('#order_review'));
                    hf.submit({
                        contingencies: contingencies,
                        cardholderName: document.getElementById('billing_first_name').value,
                        billingAddress: {
                            streetAddress: document.getElementById('billing_address_1').value,
                            extendedAddress: document.getElementById('billing_address_2').value,
                            region: document.getElementById('billing_state').value,
                            locality: document.getElementById('billing_city').value,
                            postalCode: document.getElementById('billing_postcode').value,
                            countryCodeAlpha2: document.getElementById('billing_country').value
                        }
                    }).then(
                            function (payload) {
                                if (payload.orderId) {
                                    if (angelleye_ppcp_manager.threed_secure_enabled === 'yes') {
                                        if (is_liability_shifted(payload) === true) {
                                            $.post(angelleye_ppcp_manager.cc_capture + "&paypal_order_id=" + payload.orderId + "&woocommerce-process-checkout-nonce=" + angelleye_ppcp_manager.woocommerce_process_checkout, function (data) {
                                                window.location.href = data.data.redirect;
                                            });
                                        } else {
                                            $('form.checkout').removeClass('processing paypal_cc_submiting HostedFields createOrder').unblock();
                                            showError('<ul class="woocommerce-error" role="alert">' + 'We cannot process your order with the payment information that you provided. Please use an alternate payment method.' + '</ul>', $('form'));
                                        }
                                    } else {
                                        $.post(angelleye_ppcp_manager.cc_capture + "&paypal_order_id=" + payload.orderId + "&woocommerce-process-checkout-nonce=" + angelleye_ppcp_manager.woocommerce_process_checkout, function (data) {
                                            window.location.href = data.data.redirect;
                                        });
                                    }
                                }
                            }, function (error) {
                        $('form.checkout').removeClass('processing paypal_cc_submiting HostedFields createOrder').unblock();
                        var error_message = '';
                        if (error.details[0]['description']) {
                            error_message = error.details[0]['description'];
                        } else {
                            error_message = error.message;
                        }
                        if (error.details[0]['issue'] === 'INVALID_RESOURCE_ID') {
                            error_message = '';
                        }

                        if (error_message !== '') {
                            showError('<ul class="woocommerce-error" role="alert">' + error_message + '</ul>', $('form'));
                        }
                    }
                    );
                });
            }).catch(function (err) {
                console.log('error: ', JSON.stringify(err));
            });
        };
        function is_liability_shifted(payload) {
            if (typeof payload.liabilityShift === 'undefined') {
                return false;
            }
            if (payload.liabilityShift.toUpperCase() === 'POSSIBLE') {
                return true;
            }
            return false;
        }
        if (is_from_checkout === false) {
            smart_button_render();
        }
        if (angelleye_ppcp_manager.is_pay_page === 'yes') {
            smart_button_render();
        }
        $(document.body).on('updated_cart_totals updated_checkout', function () {

            hide_show_place_order_button();
            setTimeout(function () {

                smart_button_render();
                if (is_hosted_field_eligible() === true) {
                    $('.checkout_cc_separator').show();
                    $('#wc-angelleye_ppcp-cc-form').show();
                    hosted_button_render();
                }
            }, 300);
        });
        $('form.checkout').on('click', 'input[name="payment_method"]', function () {
            hide_show_place_order_button();
        });
        var hide_show_place_order_button = function () {
            var isPPEC = is_angelleye_ppcp_selected();
            var toggleSubmit = isPPEC ? 'hide' : 'show';
            if (is_hosted_field_eligible() === false) {
                $('#place_order').animate({opacity: toggleSubmit, height: toggleSubmit, padding: toggleSubmit}, 230);
            }
        };
        function is_hosted_field_eligible() {
            if (is_from_checkout) {
                if (angelleye_ppcp_manager.advanced_card_payments === 'yes') {
                    if (typeof angelleye_paypal_sdk === 'undefined') {
                        return false;
                    }
                    if (angelleye_paypal_sdk.HostedFields.isEligible() == true) {
                        return true;
                    }
                }
            }
            return false;
        }
        function is_angelleye_ppcp_selected() {
            if ($('#payment_method_angelleye_ppcp').is(':checked')) {
                return true;
            } else {
                return false;
            }
        }
    });
})(jQuery);