(function ($) {
    'use strict';
    $(function () {
        if (typeof angelleye_ppcp_manager === 'undefined') {
            return false;
        }
        console.log(angelleye_ppcp_manager);
        var checkout_selector = '';
        if (angelleye_ppcp_manager.page === 'checkout') {
            if (angelleye_ppcp_manager.is_pay_page === 'yes') {
                checkout_selector = 'form#order_review';
            } else {
                checkout_selector = 'form.checkout';
            }
        }
        var selector = '#angelleye_ppcp_' + angelleye_ppcp_manager.page;
        if ($('.variations_form').length) {
            $('.variations_form').on('show_variation', function () {
                $(selector).show();
            }).on('hide_variation', function () {
                $(selector).hide();
            });
        }
        var hide_show_place_order_button = function () {
            console.log('25');
            if (is_angelleye_ppcp_selected() === true) {
                $('.wcf-pre-checkout-offer-action').val('');
            }
            if (is_hosted_field_eligible() === false) {
                console.log('30');
                $('.payment_method_angelleye_ppcp_cc').hide();
            }
            if (angelleye_ppcp_manager.advanced_card_payments === 'yes') {
                console.log('34');
                if (angelleye_ppcp_manager.enable_separate_payment_method === 'no') {
                    console.log('36');
                    if (is_hosted_field_eligible() === true) {
                        console.log('38');
                        $('#place_order').show();
                    } else if (is_angelleye_ppcp_selected() === true && angelleye_ppcp_manager.is_checkout_disable_smart_button === 'yes') {
                        console.log('41');
                        $('#place_order').show();
                    } else {
                        console.log('44');
                        $('#place_order').hide();
                    }
                } else if (angelleye_ppcp_manager.enable_separate_payment_method === 'yes') {
                    if (is_angelleye_ppcp_selected() === true && angelleye_ppcp_manager.is_checkout_disable_smart_button === 'no') {
                        $('#place_order').hide();
                    } else {
                        $('#place_order').show();
                    }
                }
            } else {
                if (is_angelleye_ppcp_selected() === true && angelleye_ppcp_manager.is_checkout_disable_smart_button === 'no') {
                    $('#place_order').hide();
                } else {
                    $('#place_order').show();
                }
            }
        };
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
            if (angelleye_ppcp_manager.style_height !== '') {
                angelleye_ppcp_style['height'] = parseInt(angelleye_ppcp_manager.style_height);
            }
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
                        var add_to_cart = $("[name='add-to-cart']").val();
                        $('<input>', {
                            type: 'hidden',
                            name: 'angelleye_ppcp-add-to-cart',
                            value: add_to_cart
                        }).appendTo('form.cart');
                        data = $('form.cart').serialize();
                    } else {
                        data = $('form.woocommerce-cart-form').serialize();
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
                        $.post(angelleye_ppcp_manager.cc_capture + "&paypal_order_id=" + data.orderID + "&woocommerce-process-checkout-nonce=" + angelleye_ppcp_manager.woocommerce_process_checkout, function (data) {
                            window.location.href = data.data.redirect;
                        });
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
                }, onClick: function (data, actions) {
                    var payment_method_element_selector;
                    if (angelleye_ppcp_manager.page === 'product') {
                        payment_method_element_selector = 'form.cart';
                    } else if (angelleye_ppcp_manager.page === 'cart') {
                        payment_method_element_selector = 'form.woocommerce-cart-form';
                    } else if (angelleye_ppcp_manager.page === 'checkout') {
                        payment_method_element_selector = checkout_selector;
                    }
                    if ($('#angelleye_ppcp_payment_method_title').length > 0) {
                        $('#angelleye_ppcp_payment_method_title').empty();
                    }
                    $('<input>', {
                        type: 'hidden',
                        id: 'angelleye_ppcp_payment_method_title',
                        name: 'angelleye_ppcp_payment_method_title',
                        value: data.fundingSource
                    }).appendTo(payment_method_element_selector);
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

        if ($(document.body).hasClass('woocommerce-order-pay')) {
            $('#order_review').on('submit', function (event) {
                if (is_hosted_field_eligible() === true) {
                    event.preventDefault();
                    if ($(checkout_selector).is('.paypal_cc_submiting')) {
                        return false;
                    } else {
                        $(checkout_selector).addClass('paypal_cc_submiting');
                        $(document.body).trigger('submit_paypal_cc_form');
                    }
                    return false;
                }
                return true;
            });
        }
        $(checkout_selector).on('checkout_place_order_' + angelleye_ppcp_manager.prefix_cc_field, function (event) {
            if (is_hosted_field_eligible() === true) {
                event.preventDefault();
                if ($(checkout_selector).is('.paypal_cc_submiting')) {
                    return false;
                } else {
                    $(checkout_selector).addClass('paypal_cc_submiting');
                    $(document.body).trigger('submit_paypal_cc_form');
                }
                return false;
            }
            return true;
        });
        var hosted_button_render = function () {
            if ($(checkout_selector).is('.HostedFields')) {
                return false;
            }
            if (typeof angelleye_paypal_sdk === 'undefined') {
                return;
            }
            $(checkout_selector).addClass('HostedFields');
            angelleye_paypal_sdk.HostedFields.render({
                createOrder: function () {
                    if ($(checkout_selector).is('.createOrder') === false) {
                        $(checkout_selector).addClass('createOrder');
                        var data;
                        if (is_from_checkout) {
                            data = $(checkout_selector).serialize();
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
                    if (event.cards.length > 0) {
                        var cardname = event.cards[0].type.replace("master-card", "mastercard").replace("american-express", "amex").replace("diners-club", "dinersclub").replace("-", "");
                        if (jQuery.inArray(cardname, angelleye_ppcp_manager.disable_cards) !== -1) {
                            $('#angelleye_ppcp-card-number').addClass('ppcp-invalid-cart');
                            showError('<ul class="woocommerce-error" role="alert">' + angelleye_ppcp_manager.card_not_supported + '</ul>', $('form'));
                        } else {
                            $('#angelleye_ppcp-card-number').removeClass().addClass(cardname);
                            $('#angelleye_ppcp-card-number').addClass("input-text wc-credit-card-form-card-number hosted-field-braintree braintree-hosted-fields-valid");
                        }
                        var payment_method_element_selector;
                        if (angelleye_ppcp_manager.page === 'product') {
                            payment_method_element_selector = 'form.cart';
                        } else if (angelleye_ppcp_manager.page === 'cart') {
                            payment_method_element_selector = 'form.woocommerce-cart-form';
                        } else if (angelleye_ppcp_manager.page === 'checkout') {
                            if (angelleye_ppcp_manager.is_pay_page === 'yes') {
                                payment_method_element_selector = '#order_review';
                            } else {
                                payment_method_element_selector = checkout_selector;
                            }
                        }
                        if ($('#angelleye_ppcp_payment_method_title').length > 0) {
                            $('#angelleye_ppcp_payment_method_title').empty();
                        }
                        $('<input>', {
                            type: 'hidden',
                            id: 'angelleye_ppcp_payment_method_title',
                            name: 'angelleye_ppcp_payment_method_title',
                            value: 'Advanced Credit Cards'
                        }).appendTo(payment_method_element_selector);
                    }
                });

                $(document.body).on('submit_paypal_cc_form', function (event) {
                    event.preventDefault();
                    var state = hf.getState();
                    if (typeof state.cards !== 'undefined') {
                        if (state.fields.number.isValid) {
                            var cardname = state.cards[0].type;
                            if (typeof cardname !== 'undefined' && cardname !== null || cardname.length !== 0) {
                                if (jQuery.inArray(cardname, angelleye_ppcp_manager.disable_cards) !== -1) {
                                    $(checkout_selector).removeClass('processing paypal_cc_submiting HostedFields createOrder').unblock();
                                    $('#angelleye_ppcp-card-number').addClass('ppcp-invalid-cart');
                                    showError('<ul class="woocommerce-error" role="alert">' + angelleye_ppcp_manager.card_not_supported + '</ul>', $('form'));
                                    return;
                                }
                            }
                        }
                    } else {
                        $(checkout_selector).removeClass('processing paypal_cc_submiting HostedFields createOrder').unblock();
                        showError('<ul class="woocommerce-error" role="alert">' + angelleye_ppcp_manager.fields_not_valid + '</ul>', $('form'));
                        return;
                    }
                    var formValid = Object.keys(state.fields).every(function (key) {
                        return state.fields[key].isValid;
                    });
                    if (formValid === false) {
                        $(checkout_selector).removeClass('processing paypal_cc_submiting HostedFields createOrder').unblock();
                        showError('<ul class="woocommerce-error" role="alert">' + angelleye_ppcp_manager.fields_not_valid + '</ul>', $('form'));
                        return;
                    }
                    var contingencies = [];
                    contingencies = [angelleye_ppcp_manager.three_d_secure_contingency];
                    $(checkout_selector).addClass('processing').block({
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                    $.angelleye_ppcp_scroll_to_notices($('#order_review'));
                    var firstName;
                    var lastName;
                    if (angelleye_ppcp_manager.is_pay_page === 'yes') {
                        firstName = angelleye_ppcp_manager.first_name;
                        lastName = angelleye_ppcp_manager.last_name;
                    } else {
                        firstName = document.getElementById('billing_first_name') ? document.getElementById('billing_first_name').value : '';
                        lastName = document.getElementById('billing_last_name') ? document.getElementById('billing_last_name').value : '';
                    }
                    if (!firstName || !lastName) {
                        showError('<ul class="woocommerce-error" role="alert">' + angelleye_ppcp_manager.cardholder_name_required + '</ul>', $('form'));
                        $(checkout_selector).removeClass('processing paypal_cc_submiting HostedFields createOrder').unblock();
                        return;
                    }
                    hf.submit({
                        contingencies: contingencies,
                        cardholderName: firstName + ' ' + lastName
                    }).then(
                            function (payload) {
                                if (payload.orderId) {
                                    $.post(angelleye_ppcp_manager.cc_capture + "&paypal_order_id=" + payload.orderId + "&woocommerce-process-checkout-nonce=" + angelleye_ppcp_manager.woocommerce_process_checkout, function (data) {
                                        window.location.href = data.data.redirect;
                                    });
                                }
                            }, function (error) {
                        console.log(error);
                        $(checkout_selector).removeClass('processing paypal_cc_submiting HostedFields createOrder').unblock();
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
        if (is_from_checkout === false) {
            smart_button_render();
        }
        if (angelleye_ppcp_manager.is_pay_page === 'yes') {
            hide_show_place_order_button();
            setTimeout(function () {
                smart_button_render();
                if (is_hosted_field_eligible() === true) {
                    if ($('#angelleye_ppcp-card-number iframe').length === 0) {
                        $(checkout_selector).removeClass('HostedFields');
                    }
                    $('.checkout_cc_separator').show();
                    $('#wc-angelleye_ppcp-cc-form').show();
                    hosted_button_render();
                }
            }, 300);
        }
        $(document.body).on('updated_cart_totals updated_checkout', function () {
            hide_show_place_order_button();
            setTimeout(function () {
                smart_button_render();
                if (is_hosted_field_eligible() === true) {
                    if ($('#angelleye_ppcp-card-number iframe').length === 0) {
                        $(checkout_selector).removeClass('HostedFields');
                    }
                    $('.checkout_cc_separator').show();
                    $('#wc-angelleye_ppcp-cc-form').show();
                    hosted_button_render();
                }
            }, 300);
        });
        $(checkout_selector).on('click', 'input[name="payment_method"]', function () {
            hide_show_place_order_button();
        });

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