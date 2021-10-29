(function ($) {
    var wcf_init_create_paypal_ppcp_angelleye_payments_order = function (ajax_data, gateway) {
        ajax_data.action = 'wcf_create_paypal_ppcp_angelleye_payments_order';
        $.ajax({
            url: cartflows.ajax_url,
            data: ajax_data,
            dataType: 'json',
            type: 'POST',
            success: function (response) {
                if ('success' === response.status) {
                    window.location.href = response.redirect;
                } else {
                    ajax_data.action = 'wcf_' + ajax_data.offer_type + '_accepted';
                    angelleye_ppcp_wcf_process_offer(ajax_data);
                }
            }
        });
    };
    var wcf_capture_paypal_ppcp_angelleye_payments_order = function () {
        if ('undefined' !== typeof cartflows_offer) {
            var is_ppcp_return = CartFlowsHelper.getUrlParameter('wcf-ppcp-angelleye-return');
            var ppcp_nonce = '';
            if (is_ppcp_return) {
                var ajax_data = {
                    action: 'wcf_capture_paypal_ppcp_angelleye_order',
                    step_id: cartflows_offer.step_id,
                    order_id: cartflows_offer.order_id
                };
                $('body').trigger('wcf-show-loader', 'yes');
                $.ajax({
                    url: cartflows.ajax_url,
                    data: ajax_data,
                    dataType: 'json',
                    type: 'POST',
                    success: function (response) {
                        if ('success' === response.status) {
                            var offer_type = cartflows_offer.offer_type, nonce = 'cartflows.wcf_' + offer_type + '_accepted_nonce';
                            var ajax_data = {
                                action: 'wcf_' + offer_type + '_accepted',
                                offer_action: 'yes',
                                offer_type: offer_type,
                                step_id: cartflows_offer.step_id,
                                product_id: cartflows_offer.product_id,
                                order_id: cartflows_offer.order_id,
                                order_key: cartflows_offer.order_key,
                                flow_id: cartflows.current_flow,
                                stripe_sca_payment: false,
                                stripe_intent_id: '',
                                _nonce: ppcp_nonce
                            };
                            angelleye_ppcp_wcf_process_offer(ajax_data);
                        } else {
                            ajax_data.action = 'wcf_' + ajax_data.offer_type + '_accepted';
                            angelleye_ppcp_wcf_process_offer(ajax_data);
                        }
                    }
                });
            }
        }
    };

    var angelleye_ppcp_wcf_offer_button_action = function () {
        $('a[href*="wcf-up-offer-yes"]').each(function (e) {
            var $this = $(this);
            if (e === 0) {
                $this.attr('id', 'wcf-upsell-offer');
            } else {
                $this.attr('id', 'wcf-upsell-offer-' + e);
            }
        });
        $('a[href*="wcf-down-offer-yes"]').each(function (e) {
            var $this = $(this);
            if (e === 0) {
                $this.attr('id', 'wcf-downsell-offer');
            } else {
                $this.attr('id', 'wcf-downsell-offer-' + e);
            }
        });
        $(document).on('click', 'a[href*="wcf-up-offer"], a[href*="wcf-down-offer"]', function (e) {
            e.preventDefault();
            var $this = $(this),
                    href = $this.attr('href'),
                    offer_action = 'yes',
                    offer_type = 'upsell',
                    step_id = cartflows_offer.step_id,
                    product_id = cartflows_offer.product_id,
                    order_id = cartflows_offer.order_id,
                    order_key = cartflows_offer.order_key,
                    variation_id = 0,
                    input_qty = 0,
                    flow_id = cartflows.current_flow;
            if (href.indexOf('wcf-up-offer') !== -1) {
                offer_type = 'upsell';
                if (href.indexOf('wcf-up-offer-yes') !== -1) {
                    offer_action = 'yes';
                } else {
                    offer_action = 'no';
                }
            }
            if (href.indexOf('wcf-down-offer') !== -1) {
                offer_type = 'downsell';
                if (href.indexOf('wcf-down-offer-yes') !== -1) {
                    offer_action = 'yes';
                } else {
                    offer_action = 'no';
                }
            }
            if ('yes' === offer_action) {
                var variation_wrapper = $('.wcf-offer-product-variation');
                if (variation_wrapper.length > 0) {
                    var variation_form = variation_wrapper.find('.variations_form'), variation_input = variation_form.find('input.variation_id');
                    variation_id = parseInt(variation_input.val());
                    if ($('.var_not_selected').length > 0 || '' === variation_id || 0 === variation_id) {
                        variation_form.find('.variations select').each(function () {
                            if ($(this).val().length == 0) {
                                $(this).addClass(
                                        'var_not_selected'
                                        );
                            }
                        });
                        $([document.documentElement, document.body]).animate({
                            scrollTop:
                                    variation_form
                                    .find('.variations select')
                                    .offset().top - 100,
                        }, 1000);
                        return false;
                    }
                }
            }
            $('body').trigger('wcf-show-loader', offer_action);
            if ('yes' === offer_action) {
                action = 'wcf_' + offer_type + '_accepted';
            } else {
                action = 'wcf_' + offer_type + '_rejected';
            }
            var quantity_wrapper = $('.wcf-offer-product-quantity');
            if (quantity_wrapper.length > 0) {
                var quantity_input = quantity_wrapper.find('input[name="quantity"]');
                var quantity_value = parseInt(quantity_input.val());
                if (quantity_value > 0) {
                    input_qty = quantity_value;
                }
            }
            var ajax_data = {
                action: '',
                offer_action: offer_action,
                offer_type: offer_type,
                step_id: step_id,
                product_id: product_id,
                variation_id: variation_id,
                input_qty: input_qty,
                order_id: order_id,
                order_key: order_key,
                flow_id: flow_id,
                stripe_sca_payment: false,
                stripe_intent_id: '',
                _nonce: ''
            };
            if ('yes' === offer_action) {
                if ('angelleye_ppcp' === cartflows_offer.payment_method) {
                    wcf_init_create_paypal_ppcp_angelleye_payments_order(ajax_data, cartflows_offer.payment_method);
                }
            }
            return false;
        }
        );
    };

    var angelleye_ppcp_wcf_process_offer = function (ajax_data) {
        ajax_data._nonce = cartflows_offer[ ajax_data.action + '_nonce' ];
        $.ajax({
            url: cartflows.ajax_url,
            data: ajax_data,
            dataType: 'json',
            type: 'POST',
            success: function (data) {
                var msg = data.message;
                var msg_class = 'wcf-payment-' + data.status;
                $('body').trigger('wcf-update-msg', [msg, msg_class]);
                setTimeout(function () {
                    window.location.href = data.redirect;
                }, 500);
            }
        });
    };

    $(function ($) {
        angelleye_ppcp_wcf_offer_button_action();
        wcf_capture_paypal_ppcp_angelleye_payments_order();
    });

})(jQuery);