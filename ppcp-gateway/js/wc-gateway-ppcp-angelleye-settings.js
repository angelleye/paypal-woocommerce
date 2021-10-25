function onboardingCallback(authCode, sharedId) {
    const is_sandbox = document.querySelector('#woocommerce_angelleye_ppcp_testmode');
    fetch(ppcp_angelleye_param.angelleye_ppcp_onboarding_endpoint, {
        method: 'POST',
        headers: {
            'content-type': 'application/json'
        },
        body: JSON.stringify({
            authCode: authCode,
            sharedId: sharedId,
            nonce: ppcp_angelleye_param.angelleye_ppcp_onboarding_endpoint_nonce,
            env: is_sandbox && is_sandbox.checked ? 'sandbox' : 'production'
        })
    });
}
jQuery(function ($) {
    if (typeof ppcp_angelleye_param === 'undefined') {
        return false;
    }
    var psb_available = $('#woocommerce_angelleye_ppcp_enable_advanced_card_payments, #woocommerce_angelleye_ppcp_threed_secure_enabled').closest('tr');
    if (ppcp_angelleye_param.is_advanced_card_payments === 'yes') {
        psb_available.show();
    } else {
        psb_available.hide();
    }
    if ($('#ppcp_success_notice_onboarding').length > 0) {
        $('#ppcp_success_notice_onboarding').show(1000);
    }
    if (ppcp_angelleye_param.woocommerce_enable_guest_checkout === 'no') {
        jQuery("#woocommerce_angelleye_ppcp_skip_final_review").prop("checked", false);
        jQuery("#woocommerce_angelleye_ppcp_skip_final_review").attr("disabled", true);
    }
    jQuery('#woocommerce_angelleye_ppcp_skip_final_review').change(function () {
        disable_term = jQuery('#woocommerce_angelleye_ppcp_disable_term').closest('tr');
        if (jQuery(this).is(':checked')) {
            $('#woocommerce_angelleye_ppcp_order_review_page_enable_coupons').closest('tr').hide();
            if (ppcp_angelleye_param.disable_terms === 'yes') {
                disable_term.show();
            } else {
                disable_term.hide();
            }
        } else {
            $('#woocommerce_angelleye_ppcp_order_review_page_enable_coupons').closest('tr').show();
            disable_term.hide();
        }
    }).change();
    jQuery('#woocommerce_angelleye_ppcp_disable_term').change(function () {
        term_notice = jQuery('.terms_notice');
        if (jQuery(this).is(':checked')) {
            term_notice.hide();
        } else {
            term_notice.show();
        }
    }).change();
    $('#woocommerce_angelleye_ppcp_enable_advanced_card_payments').change(function () {
        if ($(this).is(':checked')) {
            $('#woocommerce_angelleye_ppcp_threed_secure_enabled').closest('tr').show();
            if ($("#woocommerce_angelleye_ppcp_checkout_disallowed_funding_methods option[value='card']").length !== 0) {
                $('#woocommerce_angelleye_ppcp_checkout_disallowed_funding_methods option[value="card"]').remove();
            }
            $('#woocommerce_angelleye_ppcp_cart_disallowed_funding_methods option[value="card"]').prop("selected", true);
            $("#woocommerce_angelleye_ppcp_cart_disallowed_funding_methods").trigger("change");
        } else {
            if ($("#woocommerce_angelleye_ppcp_checkout_disallowed_funding_methods option[value='card']").length === 0) {
                $('#woocommerce_angelleye_ppcp_checkout_disallowed_funding_methods option:eq(0)').before(jQuery("<option></option>").attr("value", "card").text("Credit or Debit Card"));
            }
            $('#woocommerce_angelleye_ppcp_threed_secure_enabled').closest('tr').hide();
            $('#woocommerce_angelleye_ppcp_cart_disallowed_funding_methods option[value="card"]').prop("selected", false);
            $("#woocommerce_angelleye_ppcp_cart_disallowed_funding_methods").trigger("change");
        }
    }).change();
    $('#woocommerce_angelleye_ppcp_sandbox_disconnect, #woocommerce_angelleye_ppcp_live_disconnect, #woocommerce_angelleye_ppcp_sandbox_onboarding, #woocommerce_angelleye_ppcp_live_onboarding, #woocommerce_angelleye_ppcp_live_merchant_id, #woocommerce_angelleye_ppcp_sandbox_merchant_id').closest('tr').hide();
    $('#woocommerce_angelleye_ppcp_testmode').change(function () {
        var ppcp_production_onboarding_connect_fields = $('#woocommerce_angelleye_ppcp_live_onboarding').closest('tr');
        var ppcp_sandbox_onboarding_connect_fields = $('#woocommerce_angelleye_ppcp_sandbox_onboarding').closest('tr');
        var ppcp_production_onboarding_disconnect_fields = $('#woocommerce_angelleye_ppcp_live_disconnect').closest('tr');
        var ppcp_sandbox_onboarding_disconnect_fields = $('#woocommerce_angelleye_ppcp_sandbox_disconnect').closest('tr');
        if ($(this).is(':checked')) {
            $('#woocommerce_angelleye_ppcp_live_merchant_id').closest('tr').hide();
            ppcp_production_onboarding_connect_fields.hide();
            ppcp_production_onboarding_disconnect_fields.hide();
            if (ppcp_angelleye_param.is_sandbox_seller_onboarding_done === 'yes') {
                ppcp_sandbox_onboarding_connect_fields.hide();
                ppcp_sandbox_onboarding_disconnect_fields.show();
                $('#woocommerce_angelleye_ppcp_sandbox_merchant_id').closest('tr').show();
            } else {
                if (ppcp_angelleye_param.angelleye_ppcp_is_local_server === 'yes') {
                    ppcp_sandbox_onboarding_connect_fields.hide();
                    ppcp_sandbox_onboarding_disconnect_fields.hide();
                } else {
                    ppcp_sandbox_onboarding_connect_fields.show();
                    ppcp_sandbox_onboarding_disconnect_fields.hide();
                }
            }
        } else {
            $('#woocommerce_angelleye_ppcp_sandbox_merchant_id').closest('tr').hide();
            ppcp_sandbox_onboarding_connect_fields.hide();
            ppcp_sandbox_onboarding_disconnect_fields.hide();
            if (ppcp_angelleye_param.is_live_seller_onboarding_done === 'yes') {
                $('#woocommerce_angelleye_ppcp_live_merchant_id').closest('tr').show();
                ppcp_production_onboarding_connect_fields.hide();
                ppcp_production_onboarding_disconnect_fields.show();
            } else {
                if (ppcp_angelleye_param.angelleye_ppcp_is_local_server === 'yes') {
                    ppcp_production_onboarding_connect_fields.hide();
                    ppcp_production_onboarding_disconnect_fields.hide();
                } else {
                    ppcp_production_onboarding_connect_fields.show();
                    ppcp_production_onboarding_disconnect_fields.hide();
                }
            }
        }
    }).change();
    jQuery("#woocommerce_angelleye_ppcp_product_button_layout").change(function () {
        var angelleye_ppcp_product_tagline = jQuery("#woocommerce_angelleye_ppcp_product_button_tagline").closest('tr');
        if (this.value === 'vertical') {
            angelleye_ppcp_product_tagline.hide();
        } else {
            angelleye_ppcp_product_tagline.show();
        }
    }).change();
    jQuery("#woocommerce_angelleye_ppcp_cart_button_layout").change(function () {
        var angelleye_ppcp_cart_tagline = jQuery("#woocommerce_angelleye_ppcp_cart_button_tagline").closest('tr');
        if (this.value === 'vertical') {
            angelleye_ppcp_cart_tagline.hide();
        } else {
            angelleye_ppcp_cart_tagline.show();
        }
    }).change();
    jQuery("#woocommerce_angelleye_ppcp_checkout_button_layout").change(function () {
        var angelleye_ppcp_checkout_tagline = jQuery("#woocommerce_angelleye_ppcp_checkout_button_tagline").closest('tr');
        if (this.value === 'vertical') {
            angelleye_ppcp_checkout_tagline.hide();
        } else {
            angelleye_ppcp_checkout_tagline.show();
        }
    }).change();
    jQuery("#woocommerce_angelleye_ppcp_mini_cart_button_layout").change(function () {
        var angelleye_ppcp_mini_cart_tagline = jQuery("#woocommerce_angelleye_ppcp_mini_cart_button_tagline").closest('tr');
        if (this.value === 'vertical') {
            angelleye_ppcp_mini_cart_tagline.hide();
        } else {
            angelleye_ppcp_mini_cart_tagline.show();
        }
    }).change();
    $(".angelleye-ppcp-disconnect").click(function () {
        if ($('#woocommerce_angelleye_ppcp_testmode').is(':checked')) {
            $('#woocommerce_angelleye_ppcp_sandbox_merchant_id').val('');
        } else {
            $('#woocommerce_angelleye_ppcp_live_merchant_id').val('');
        }
        $('.woocommerce-save-button').click();
    });
    $('#woocommerce_angelleye_ppcp_enable_product_button').change(function () {
        if ($(this).is(':checked')) {
            $('.angelleye_ppcp_product_button_settings').closest('tr').show();
        } else {
            $('.angelleye_ppcp_product_button_settings').closest('tr').hide();
        }
    }).change();
    $('#woocommerce_angelleye_ppcp_enable_cart_button').change(function () {
        if ($(this).is(':checked')) {
            $('.angelleye_ppcp_cart_button_settings').closest('tr').show();
        } else {
            $('.angelleye_ppcp_cart_button_settings').closest('tr').hide();
        }
    }).change();
    $('#woocommerce_angelleye_ppcp_enable_checkout_button').change(function () {
        if ($(this).is(':checked')) {
            $('.angelleye_ppcp_checkout_button_settings').closest('tr').show();
        } else {
            $('.angelleye_ppcp_checkout_button_settings').closest('tr').hide();
        }
    }).change();
    $('#woocommerce_angelleye_ppcp_enable_mini_cart_button').change(function () {
        if ($(this).is(':checked')) {
            $('.angelleye_ppcp_mini_cart_button_settings').closest('tr').show();
        } else {
            $('.angelleye_ppcp_mini_cart_button_settings').closest('tr').hide();
        }
    }).change();
    var home_page_pay_later_messaging_preview = function () {
        var home_style_object = {};
        home_style_object['layout'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_home_layout_type').val();
        if (home_style_object['layout'] === 'text') {
            home_style_object['logo'] = {};
            home_style_object['logo']['type'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_home_text_layout_logo_type').val();
            if (home_style_object['logo']['type'] === 'primary' || home_style_object['logo']['type'] === 'alternative') {
                home_style_object['logo']['position'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_home_text_layout_logo_position').val();
            }
            home_style_object['text'] = {};
            home_style_object['text']['size'] = parseInt(jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_home_text_layout_text_size').val());
            home_style_object['text']['color'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_home_text_layout_text_color').val();
        } else {
            home_style_object['color'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_home_flex_layout_color').val();
            home_style_object['ratio'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_home_flex_layout_ratio').val();
        }
        if (typeof paypal !== 'undefined' && is_pay_later_messaging_home_page_enable()) {
            paypal.Messages({
                amount: 500,
                placement: 'home',
                style: home_style_object
            }).render('.pp_message_home');
        }
    };
    var hide_show_home_shortcode = function () {
        jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_home_shortcode').change(function () {
            var home_preview_shortcode = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_home_preview_shortcode').closest('tr');
            if (jQuery(this).is(':checked')) {
                if (is_pay_later_messaging_enable() === true && is_pay_later_messaging_home_page_enable()) {
                    home_preview_shortcode.show();
                }
            } else {
                home_preview_shortcode.hide();
            }
        }).change();
    };
    var hide_show_category_shortcode = function () {
        jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_category_shortcode').change(function () {
            var category_preview_shortcode = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_category_preview_shortcode').closest('tr');
            if (jQuery(this).is(':checked')) {
                if (is_pay_later_messaging_enable() === true && is_pay_later_messaging_category_page_enable()) {
                    category_preview_shortcode.show();
                }
            } else {
                category_preview_shortcode.hide();
            }
        }).change();
    };
    var ppcp_copy_text = function (css_class) {
        jQuery(document.body).on('click', css_class, function (evt) {
            evt.preventDefault();
            wcClearClipboard();
            wcSetClipboard(jQuery.trim(jQuery(this).prev('input').val()), jQuery(css_class));
        }).on('aftercopy', css_class, function () {
            jQuery(css_class).tipTip({
                'attribute': 'data-tip',
                'activation': 'focus',
                'fadeIn': 50,
                'fadeOut': 50,
                'delay': 0
            }).focus();
        });
    };
    var hide_show_product_shortcode = function () {
        jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_product_shortcode').change(function () {
            var product_preview_shortcode = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_product_preview_shortcode').closest('tr');
            if (jQuery(this).is(':checked')) {
                if (is_pay_later_messaging_enable() === true && is_pay_later_messaging_product_page_enable()) {
                    product_preview_shortcode.show();
                }
            } else {
                product_preview_shortcode.hide();
            }
        }).change();
    };
    var hide_show_cart_shortcode = function () {
        jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_cart_shortcode').change(function () {
            var cart_preview_shortcode = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_cart_preview_shortcode').closest('tr');
            if (jQuery(this).is(':checked')) {
                if (is_pay_later_messaging_enable() === true && is_pay_later_messaging_cart_page_enable()) {
                    cart_preview_shortcode.show();
                }
            } else {
                cart_preview_shortcode.hide();
            }
        }).change();
    };
    var hide_show_payment_shortcode = function () {
        jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_payment_shortcode').change(function () {
            var payment_preview_shortcode = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_payment_preview_shortcode').closest('tr');
            if (jQuery(this).is(':checked')) {
                if (is_pay_later_messaging_enable() === true && is_pay_later_messaging_payment_page_enable()) {
                    payment_preview_shortcode.show();
                }
            } else {
                payment_preview_shortcode.hide();
            }
        }).change();
    };
    var category_page_pay_later_messaging_preview = function () {
        var category_style_object = {};
        category_style_object['layout'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_category_layout_type').val();
        if (category_style_object['layout'] === 'text') {
            category_style_object['logo'] = {};
            category_style_object['logo']['type'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_category_text_layout_logo_type').val();
            if (category_style_object['logo']['type'] === 'primary' || category_style_object['logo']['type'] === 'alternative') {
                category_style_object['logo']['position'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_category_text_layout_logo_position').val();
            }
            category_style_object['text'] = {};
            category_style_object['text']['size'] = parseInt(jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_category_text_layout_text_size').val());
            category_style_object['text']['color'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_category_text_layout_text_color').val();
        } else {
            category_style_object['color'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_category_flex_layout_color').val();
            category_style_object['ratio'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_category_flex_layout_ratio').val();
        }
        if (typeof paypal !== 'undefined' && is_pay_later_messaging_category_page_enable()) {
            paypal.Messages({
                amount: 500,
                placement: 'category',
                style: category_style_object
            }).render('.pp_message_category');
        }
    };
    var product_page_pay_later_messaging_preview = function () {
        var product_style_object = {};
        product_style_object['layout'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_product_layout_type').val();
        if (product_style_object['layout'] === 'text') {
            product_style_object['logo'] = {};
            product_style_object['logo']['type'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_product_text_layout_logo_type').val();
            if (product_style_object['logo']['type'] === 'primary' || product_style_object['logo']['type'] === 'alternative') {
                product_style_object['logo']['position'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_product_text_layout_logo_position').val();
            }
            product_style_object['text'] = {};
            product_style_object['text']['size'] = parseInt(jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_product_text_layout_text_size').val());
            product_style_object['text']['color'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_product_text_layout_text_color').val();
        } else {
            product_style_object['color'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_product_flex_layout_color').val();
            product_style_object['ratio'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_product_flex_layout_ratio').val();
        }
        if (typeof paypal !== 'undefined' && is_pay_later_messaging_product_page_enable()) {
            paypal.Messages({
                amount: 500,
                placement: 'product',
                style: product_style_object
            }).render('.pp_message_product');
        }
    };
    var cart_page_pay_later_messaging_preview = function () {
        var cart_style_object = {};
        cart_style_object['layout'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_cart_layout_type').val();
        if (cart_style_object['layout'] === 'text') {
            cart_style_object['logo'] = {};
            cart_style_object['logo']['type'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_cart_text_layout_logo_type').val();
            if (cart_style_object['logo']['type'] === 'primary' || cart_style_object['logo']['type'] === 'alternative') {
                cart_style_object['logo']['position'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_cart_text_layout_logo_position').val();
            }
            cart_style_object['text'] = {};
            cart_style_object['text']['size'] = parseInt(jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_cart_text_layout_text_size').val());
            cart_style_object['text']['color'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_cart_text_layout_text_color').val();
        } else {
            cart_style_object['color'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_cart_flex_layout_color').val();
            cart_style_object['ratio'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_cart_flex_layout_ratio').val();
        }
        if (typeof paypal !== 'undefined' && is_pay_later_messaging_cart_page_enable()) {
            paypal.Messages({
                amount: 500,
                placement: 'cart',
                style: cart_style_object
            }).render('.pp_message_cart');
        }
    };
    var payment_page_pay_later_messaging_preview = function () {
        var payment_style_object = {};
        payment_style_object['layout'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_payment_layout_type').val();
        if (payment_style_object['layout'] === 'text') {
            payment_style_object['logo'] = {};
            payment_style_object['logo']['type'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_payment_text_layout_logo_type').val();
            if (payment_style_object['logo']['type'] === 'primary' || payment_style_object['logo']['type'] === 'alternative') {
                payment_style_object['logo']['position'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_payment_text_layout_logo_position').val();
            }
            payment_style_object['text'] = {};
            payment_style_object['text']['size'] = parseInt(jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_payment_text_layout_text_size').val());
            payment_style_object['text']['color'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_payment_text_layout_text_color').val();
        } else {
            payment_style_object['color'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_payment_flex_layout_color').val();
            payment_style_object['ratio'] = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_payment_flex_layout_ratio').val();
        }
        if (typeof paypal !== 'undefined' && is_pay_later_messaging_payment_page_enable()) {
            paypal.Messages({
                amount: 500,
                placement: 'payment',
                style: payment_style_object
            }).render('.pp_message_payment');
        }
    };
    jQuery(document).ready(function ($) {
        jQuery('.pay_later_messaging_home_field').change(function () {
            home_page_pay_later_messaging_preview();
        });
        jQuery('.pay_later_messaging_category_field').change(function () {
            category_page_pay_later_messaging_preview();
        });
        jQuery('.pay_later_messaging_product_field').change(function () {
            product_page_pay_later_messaging_preview();
        });
        jQuery('.pay_later_messaging_cart_field').change(function () {
            cart_page_pay_later_messaging_preview();
        });
        jQuery('.pay_later_messaging_payment_field').change(function () {
            payment_page_pay_later_messaging_preview();
        });
        home_page_pay_later_messaging_preview();
        category_page_pay_later_messaging_preview();
        product_page_pay_later_messaging_preview();
        cart_page_pay_later_messaging_preview();
        payment_page_pay_later_messaging_preview();
    });
    setTimeout(function () {
        jQuery('#woocommerce_angelleye_ppcp_enabled_pay_later_messaging').trigger('change');

    }, 5000);
    ppcp_copy_text('.home_copy_text');
    ppcp_copy_text('.category_copy_text');
    ppcp_copy_text('.product_copy_text');
    ppcp_copy_text('.cart_copy_text');
    ppcp_copy_text('.payment_copy_text');
    var is_pay_later_messaging_enable = function () {
        if (jQuery('#woocommerce_angelleye_ppcp_enabled_pay_later_messaging').is(':checked')) {
            return true;
        }
        return false;
    };
    var is_pay_later_messaging_home_page_enable = function () {
        if (is_pay_later_messaging_enable() === false) {
            return false;
        }
        if (jQuery.inArray('home', jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_page_type').val()) === -1) {
            return false;
        }
        return true;
    };
    var pay_later_messaging_home_page_hide_show = function () {
        pay_later_messaging_home_field_parent = jQuery('.pay_later_messaging_home_field').closest('tr');
        pay_later_messaging_home_field_p_tag = jQuery('.pay_later_messaging_home_field').next("p");
        pay_later_messaging_home_field = jQuery('.pay_later_messaging_home_field');
        pay_later_messaging_home_base_field_parent = jQuery('.pay_later_messaging_home_base_field').closest('tr');
        pay_later_messaging_home_base_field_p_tag = jQuery('.pay_later_messaging_home_base_field').next("p");
        pay_later_messaging_home_base_field = jQuery('.pay_later_messaging_home_base_field');
        pay_later_messaging_home_preview = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_home_preview');
        if (is_pay_later_messaging_home_page_enable()) {
            pay_later_messaging_home_field_parent.show();
            pay_later_messaging_home_field.show();
            pay_later_messaging_home_field_p_tag.show();
            pay_later_messaging_home_base_field_parent.show();
            pay_later_messaging_home_base_field.show();
            pay_later_messaging_home_base_field_p_tag.show();
            pay_later_messaging_home_preview.show();
        } else {
            pay_later_messaging_home_field_parent.hide();
            pay_later_messaging_home_field.hide();
            pay_later_messaging_home_field_p_tag.hide();
            pay_later_messaging_home_base_field_parent.hide();
            pay_later_messaging_home_base_field.hide();
            pay_later_messaging_home_base_field_p_tag.hide();
            pay_later_messaging_home_preview.hide();
        }
        jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_home_layout_type').trigger('change');
        hide_show_home_shortcode();
    };
    var is_pay_later_messaging_category_page_enable = function () {
        if (is_pay_later_messaging_enable() === false) {
            return false;
        }
        if (jQuery.inArray('category', jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_page_type').val()) === -1) {
            return false;
        }
        return true;
    };
    var pay_later_messaging_category_page_hide_show = function () {
        pay_later_messaging_category_field_parent = jQuery('.pay_later_messaging_category_field').closest('tr');
        pay_later_messaging_category_field_p_tag = jQuery('.pay_later_messaging_category_field').next("p");
        pay_later_messaging_category_field = jQuery('.pay_later_messaging_category_field');
        pay_later_messaging_category_base_field_parent = jQuery('.pay_later_messaging_category_base_field').closest('tr');
        pay_later_messaging_category_base_field_p_tag = jQuery('.pay_later_messaging_category_base_field').next("p");
        pay_later_messaging_category_base_field = jQuery('.pay_later_messaging_category_base_field');
        pay_later_messaging_category_preview = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_category_preview');
        if (is_pay_later_messaging_category_page_enable()) {
            pay_later_messaging_category_field_parent.show();
            pay_later_messaging_category_field.show();
            pay_later_messaging_category_field_p_tag.show();
            pay_later_messaging_category_base_field_parent.show();
            pay_later_messaging_category_base_field.show();
            pay_later_messaging_category_base_field_p_tag.show();
            pay_later_messaging_category_preview.show();
        } else {
            pay_later_messaging_category_field_parent.hide();
            pay_later_messaging_category_field.hide();
            pay_later_messaging_category_field_p_tag.hide();
            pay_later_messaging_category_base_field_parent.hide();
            pay_later_messaging_category_base_field.hide();
            pay_later_messaging_category_base_field_p_tag.hide();
            pay_later_messaging_category_preview.hide();
        }
        jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_category_layout_type').trigger('change');
        hide_show_category_shortcode();
    };
    var is_pay_later_messaging_product_page_enable = function () {
        if (is_pay_later_messaging_enable() === false) {
            return false;
        }
        if (jQuery.inArray('product', jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_page_type').val()) === -1) {
            return false;
        }
        return true;
    };
    var pay_later_messaging_product_page_hide_show = function () {
        pay_later_messaging_product_field_parent = jQuery('.pay_later_messaging_product_field').closest('tr');
        pay_later_messaging_product_field_p_tag = jQuery('.pay_later_messaging_product_field').next("p");
        pay_later_messaging_product_field = jQuery('.pay_later_messaging_product_field');
        pay_later_messaging_product_base_field_parent = jQuery('.pay_later_messaging_product_base_field').closest('tr');
        pay_later_messaging_product_base_field_p_tag = jQuery('.pay_later_messaging_product_base_field').next("p");
        pay_later_messaging_product_base_field = jQuery('.pay_later_messaging_product_base_field');
        pay_later_messaging_product_preview = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_product_preview');
        if (is_pay_later_messaging_product_page_enable()) {
            pay_later_messaging_product_field_parent.show();
            pay_later_messaging_product_field.show();
            pay_later_messaging_product_field_p_tag.show();
            pay_later_messaging_product_base_field_parent.show();
            pay_later_messaging_product_base_field.show();
            pay_later_messaging_product_base_field_p_tag.show();
            pay_later_messaging_product_preview.show();
        } else {
            pay_later_messaging_product_field_parent.hide();
            pay_later_messaging_product_field.hide();
            pay_later_messaging_product_field_p_tag.hide();
            pay_later_messaging_product_base_field_parent.hide();
            pay_later_messaging_product_base_field.hide();
            pay_later_messaging_product_base_field_p_tag.hide();
            pay_later_messaging_product_preview.hide();
        }
        jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_product_layout_type').trigger('change');
        hide_show_product_shortcode();
    };
    var is_pay_later_messaging_cart_page_enable = function () {
        if (is_pay_later_messaging_enable() === false) {
            return false;
        }
        if (jQuery.inArray('cart', jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_page_type').val()) === -1) {
            return false;
        }
        return true;
    };
    var pay_later_messaging_cart_page_hide_show = function () {
        pay_later_messaging_cart_field_parent = jQuery('.pay_later_messaging_cart_field').closest('tr');
        pay_later_messaging_cart_field_p_tag = jQuery('.pay_later_messaging_cart_field').next("p");
        pay_later_messaging_cart_field = jQuery('.pay_later_messaging_cart_field');
        pay_later_messaging_cart_base_field_parent = jQuery('.pay_later_messaging_cart_base_field').closest('tr');
        pay_later_messaging_cart_base_field_p_tag = jQuery('.pay_later_messaging_cart_base_field').next("p");
        pay_later_messaging_cart_base_field = jQuery('.pay_later_messaging_cart_base_field');
        pay_later_messaging_cart_preview = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_cart_preview');
        if (is_pay_later_messaging_cart_page_enable()) {
            pay_later_messaging_cart_field_parent.show();
            pay_later_messaging_cart_field.show();
            pay_later_messaging_cart_field_p_tag.show();
            pay_later_messaging_cart_base_field_parent.show();
            pay_later_messaging_cart_base_field.show();
            pay_later_messaging_cart_base_field_p_tag.show();
            pay_later_messaging_cart_preview.show();
        } else {
            pay_later_messaging_cart_field_parent.hide();
            pay_later_messaging_cart_field.hide();
            pay_later_messaging_cart_field_p_tag.hide();
            pay_later_messaging_cart_base_field_parent.hide();
            pay_later_messaging_cart_base_field.hide();
            pay_later_messaging_cart_base_field_p_tag.hide();
            pay_later_messaging_cart_preview.hide();
        }
        jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_cart_layout_type').trigger('change');
        hide_show_cart_shortcode();
    };
    var is_pay_later_messaging_payment_page_enable = function () {
        if (is_pay_later_messaging_enable() === false) {
            return false;
        }
        if (jQuery.inArray('payment', jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_page_type').val()) === -1) {
            return false;
        }
        return true;
    };
    var pay_later_messaging_payment_page_hide_show = function () {
        pay_later_messaging_payment_field_parent = jQuery('.pay_later_messaging_payment_field').closest('tr');
        pay_later_messaging_payment_field_p_tag = jQuery('.pay_later_messaging_payment_field').next("p");
        pay_later_messaging_payment_field = jQuery('.pay_later_messaging_payment_field');
        pay_later_messaging_payment_base_field_parent = jQuery('.pay_later_messaging_payment_base_field').closest('tr');
        pay_later_messaging_payment_base_field_p_tag = jQuery('.pay_later_messaging_payment_base_field').next("p");
        pay_later_messaging_payment_base_field = jQuery('.pay_later_messaging_payment_base_field');
        pay_later_messaging_payment_preview = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_payment_preview');
        if (is_pay_later_messaging_payment_page_enable()) {
            pay_later_messaging_payment_field_parent.show();
            pay_later_messaging_payment_field.show();
            pay_later_messaging_payment_field_p_tag.show();
            pay_later_messaging_payment_base_field_parent.show();
            pay_later_messaging_payment_base_field.show();
            pay_later_messaging_payment_base_field_p_tag.show();
            pay_later_messaging_payment_preview.show();
        } else {
            pay_later_messaging_payment_field_parent.hide();
            pay_later_messaging_payment_field.hide();
            pay_later_messaging_payment_field_p_tag.hide();
            pay_later_messaging_payment_base_field_parent.hide();
            pay_later_messaging_payment_base_field.hide();
            pay_later_messaging_payment_base_field_p_tag.hide();
            pay_later_messaging_payment_preview.hide();
        }
        jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_payment_layout_type').trigger('change');
        hide_show_payment_shortcode();
    };
    jQuery('#woocommerce_angelleye_ppcp_enabled_pay_later_messaging').change(function () {
        pay_later_messaging_field_parent = jQuery('.pay_later_messaging_field').closest('tr');
        pay_later_messaging_field_p_tag = jQuery('.pay_later_messaging_field').next("p");
        pay_later_messaging_field = jQuery('.pay_later_messaging_field');
        if (jQuery(this).is(':checked')) {
            pay_later_messaging_field_parent.show();
            pay_later_messaging_field.show();
            pay_later_messaging_field_p_tag.show();
        } else {
            pay_later_messaging_field_parent.hide();
            pay_later_messaging_field.hide();
            pay_later_messaging_field_p_tag.hide();
        }
        jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_page_type').trigger('change');
    }).change();
    jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_page_type').change(function () {
        pay_later_messaging_home_page_hide_show();
        pay_later_messaging_category_page_hide_show();
        pay_later_messaging_product_page_hide_show();
        pay_later_messaging_cart_page_hide_show();
        pay_later_messaging_payment_page_hide_show();
    }).change();
    jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_home_layout_type').change(function () {
        pay_later_messaging_home_text_layout_field_parent = jQuery('.pay_later_messaging_home_text_layout_field').closest('tr');
        pay_later_messaging_home_text_layout_field_p_tag = jQuery('.pay_later_messaging_home_text_layout_field').next("p");
        pay_later_messaging_home_text_layout_field = jQuery('.pay_later_messaging_home_text_layout_field');
        pay_later_messaging_home_flex_layout_field_parent = jQuery('.pay_later_messaging_home_flex_layout_field').closest('tr');
        pay_later_messaging_home_flex_layout_field_p_tag = jQuery('.pay_later_messaging_home_flex_layout_field').next("p");
        pay_later_messaging_home_flex_layout_field = jQuery('.pay_later_messaging_home_flex_layout_field');
        if (this.value === 'text') {
            if (is_pay_later_messaging_home_page_enable()) {
                pay_later_messaging_home_text_layout_field_parent.show();
                pay_later_messaging_home_text_layout_field.show();
                pay_later_messaging_home_text_layout_field_p_tag.show();
                pay_later_messaging_home_flex_layout_field_parent.hide();
                pay_later_messaging_home_flex_layout_field_p_tag.hide();
                pay_later_messaging_home_flex_layout_field.hide();
            }
        } else {
            if (is_pay_later_messaging_home_page_enable()) {
                pay_later_messaging_home_flex_layout_field_parent.show();
                pay_later_messaging_home_flex_layout_field_p_tag.show();
                pay_later_messaging_home_flex_layout_field.show();
            }
            pay_later_messaging_home_text_layout_field_parent.hide();
            pay_later_messaging_home_text_layout_field.hide();
            pay_later_messaging_home_text_layout_field_p_tag.hide();
        }
        jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_home_text_layout_logo_type').trigger('change');
    }).change();
    jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_home_text_layout_logo_type').change(function () {
        pay_later_messaging_home_text_layout_logo_position = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_home_text_layout_logo_position').closest('tr');
        if (jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_home_layout_type').val() === 'text' && (this.value === 'primary' || this.value === 'alternative')) {
            if (is_pay_later_messaging_home_page_enable()) {
                pay_later_messaging_home_text_layout_logo_position.show();
            }
        } else {
            pay_later_messaging_home_text_layout_logo_position.hide();
        }
    }).change();
    jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_category_layout_type').change(function () {
        pay_later_messaging_category_text_layout_field_parent = jQuery('.pay_later_messaging_category_text_layout_field').closest('tr');
        pay_later_messaging_category_text_layout_field_p_tag = jQuery('.pay_later_messaging_category_text_layout_field').next("p");
        pay_later_messaging_category_text_layout_field = jQuery('.pay_later_messaging_category_text_layout_field');
        pay_later_messaging_category_flex_layout_field_parent = jQuery('.pay_later_messaging_category_flex_layout_field').closest('tr');
        pay_later_messaging_category_flex_layout_field_p_tag = jQuery('.pay_later_messaging_category_flex_layout_field').next("p");
        pay_later_messaging_category_flex_layout_field = jQuery('.pay_later_messaging_category_flex_layout_field');
        if (this.value === 'text') {
            if (is_pay_later_messaging_category_page_enable()) {
                pay_later_messaging_category_text_layout_field_parent.show();
                pay_later_messaging_category_text_layout_field.show();
                pay_later_messaging_category_text_layout_field_p_tag.show();
                pay_later_messaging_category_flex_layout_field_parent.hide();
                pay_later_messaging_category_flex_layout_field_p_tag.hide();
                pay_later_messaging_category_flex_layout_field.hide();
            }
        } else {
            if (is_pay_later_messaging_category_page_enable()) {
                pay_later_messaging_category_flex_layout_field_parent.show();
                pay_later_messaging_category_flex_layout_field_p_tag.show();
                pay_later_messaging_category_flex_layout_field.show();
            }
            pay_later_messaging_category_text_layout_field_parent.hide();
            pay_later_messaging_category_text_layout_field.hide();
            pay_later_messaging_category_text_layout_field_p_tag.hide();
        }
        jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_category_text_layout_logo_type').trigger('change');
    }).change();
    jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_category_text_layout_logo_type').change(function () {
        pay_later_messaging_category_text_layout_logo_position = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_category_text_layout_logo_position').closest('tr');
        if (jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_category_layout_type').val() === 'text' && (this.value === 'primary' || this.value === 'alternative')) {
            if (is_pay_later_messaging_category_page_enable()) {
                pay_later_messaging_category_text_layout_logo_position.show();
            }
        } else {
            pay_later_messaging_category_text_layout_logo_position.hide();
        }
    }).change();
    jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_product_layout_type').change(function () {
        pay_later_messaging_product_text_layout_field_parent = jQuery('.pay_later_messaging_product_text_layout_field').closest('tr');
        pay_later_messaging_product_text_layout_field_p_tag = jQuery('.pay_later_messaging_product_text_layout_field').next("p");
        pay_later_messaging_product_text_layout_field = jQuery('.pay_later_messaging_product_text_layout_field');
        pay_later_messaging_product_flex_layout_field_parent = jQuery('.pay_later_messaging_product_flex_layout_field').closest('tr');
        pay_later_messaging_product_flex_layout_field_p_tag = jQuery('.pay_later_messaging_product_flex_layout_field').next("p");
        pay_later_messaging_product_flex_layout_field = jQuery('.pay_later_messaging_product_flex_layout_field');
        if (this.value === 'text') {
            if (is_pay_later_messaging_product_page_enable()) {
                pay_later_messaging_product_text_layout_field_parent.show();
                pay_later_messaging_product_text_layout_field.show();
                pay_later_messaging_product_text_layout_field_p_tag.show();
                pay_later_messaging_product_flex_layout_field_parent.hide();
                pay_later_messaging_product_flex_layout_field_p_tag.hide();
                pay_later_messaging_product_flex_layout_field.hide();
            }
        } else {
            if (is_pay_later_messaging_product_page_enable()) {
                pay_later_messaging_product_flex_layout_field_parent.show();
                pay_later_messaging_product_flex_layout_field_p_tag.show();
                pay_later_messaging_product_flex_layout_field.show();
            }
            pay_later_messaging_product_text_layout_field_parent.hide();
            pay_later_messaging_product_text_layout_field.hide();
            pay_later_messaging_product_text_layout_field_p_tag.hide();
        }
        jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_product_text_layout_logo_type').trigger('change');
    }).change();
    jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_product_text_layout_logo_type').change(function () {
        pay_later_messaging_product_text_layout_logo_position = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_product_text_layout_logo_position').closest('tr');
        if (jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_product_layout_type').val() === 'text' && (this.value === 'primary' || this.value === 'alternative')) {
            if (is_pay_later_messaging_product_page_enable()) {
                pay_later_messaging_product_text_layout_logo_position.show();
            }
        } else {
            pay_later_messaging_product_text_layout_logo_position.hide();
        }
    }).change();
    jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_cart_layout_type').change(function () {
        pay_later_messaging_cart_text_layout_field_parent = jQuery('.pay_later_messaging_cart_text_layout_field').closest('tr');
        pay_later_messaging_cart_text_layout_field_p_tag = jQuery('.pay_later_messaging_cart_text_layout_field').next("p");
        pay_later_messaging_cart_text_layout_field = jQuery('.pay_later_messaging_cart_text_layout_field');
        pay_later_messaging_cart_flex_layout_field_parent = jQuery('.pay_later_messaging_cart_flex_layout_field').closest('tr');
        pay_later_messaging_cart_flex_layout_field_p_tag = jQuery('.pay_later_messaging_cart_flex_layout_field').next("p");
        pay_later_messaging_cart_flex_layout_field = jQuery('.pay_later_messaging_cart_flex_layout_field');
        if (this.value === 'text') {
            if (is_pay_later_messaging_cart_page_enable()) {
                pay_later_messaging_cart_text_layout_field_parent.show();
                pay_later_messaging_cart_text_layout_field.show();
                pay_later_messaging_cart_text_layout_field_p_tag.show();
                pay_later_messaging_cart_flex_layout_field_parent.hide();
                pay_later_messaging_cart_flex_layout_field_p_tag.hide();
                pay_later_messaging_cart_flex_layout_field.hide();
            }
        } else {
            if (is_pay_later_messaging_cart_page_enable()) {
                pay_later_messaging_cart_flex_layout_field_parent.show();
                pay_later_messaging_cart_flex_layout_field_p_tag.show();
                pay_later_messaging_cart_flex_layout_field.show();
            }
            pay_later_messaging_cart_text_layout_field_parent.hide();
            pay_later_messaging_cart_text_layout_field.hide();
            pay_later_messaging_cart_text_layout_field_p_tag.hide();
        }
        jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_cart_text_layout_logo_type').trigger('change');
    }).change();
    jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_cart_text_layout_logo_type').change(function () {
        pay_later_messaging_cart_text_layout_logo_position = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_cart_text_layout_logo_position').closest('tr');
        if (jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_cart_layout_type').val() === 'text' && (this.value === 'primary' || this.value === 'alternative')) {
            if (is_pay_later_messaging_cart_page_enable()) {
                pay_later_messaging_cart_text_layout_logo_position.show();
            }
        } else {
            pay_later_messaging_cart_text_layout_logo_position.hide();
        }
    }).change();
    jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_payment_layout_type').change(function () {
        pay_later_messaging_payment_text_layout_field_parent = jQuery('.pay_later_messaging_payment_text_layout_field').closest('tr');
        pay_later_messaging_payment_text_layout_field_p_tag = jQuery('.pay_later_messaging_payment_text_layout_field').next("p");
        pay_later_messaging_payment_text_layout_field = jQuery('.pay_later_messaging_payment_text_layout_field');
        pay_later_messaging_payment_flex_layout_field_parent = jQuery('.pay_later_messaging_payment_flex_layout_field').closest('tr');
        pay_later_messaging_payment_flex_layout_field_p_tag = jQuery('.pay_later_messaging_payment_flex_layout_field').next("p");
        pay_later_messaging_payment_flex_layout_field = jQuery('.pay_later_messaging_payment_flex_layout_field');
        if (this.value === 'text') {
            if (is_pay_later_messaging_payment_page_enable()) {
                pay_later_messaging_payment_text_layout_field_parent.show();
                pay_later_messaging_payment_text_layout_field.show();
                pay_later_messaging_payment_text_layout_field_p_tag.show();
                pay_later_messaging_payment_flex_layout_field_parent.hide();
                pay_later_messaging_payment_flex_layout_field_p_tag.hide();
                pay_later_messaging_payment_flex_layout_field.hide();
            }
        } else {
            if (is_pay_later_messaging_payment_page_enable()) {
                pay_later_messaging_payment_flex_layout_field_parent.show();
                pay_later_messaging_payment_flex_layout_field_p_tag.show();
                pay_later_messaging_payment_flex_layout_field.show();
            }
            pay_later_messaging_payment_text_layout_field_parent.hide();
            pay_later_messaging_payment_text_layout_field.hide();
            pay_later_messaging_payment_text_layout_field_p_tag.hide();
        }
        jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_payment_text_layout_logo_type').trigger('change');
    }).change();
    jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_payment_text_layout_logo_type').change(function () {
        pay_later_messaging_payment_text_layout_logo_position = jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_payment_text_layout_logo_position').closest('tr');
        if (jQuery('#woocommerce_angelleye_ppcp_pay_later_messaging_payment_layout_type').val() === 'text' && (this.value === 'primary' || this.value === 'alternative')) {
            if (is_pay_later_messaging_payment_page_enable()) {
                pay_later_messaging_payment_text_layout_logo_position.show();
            }
        } else {
            pay_later_messaging_payment_text_layout_logo_position.hide();
        }
    }).change();
});
   