jQuery(function () {
    jQuery(document).off('click', '#angelleye-updater-notice .notice-dismiss').on('click', '#angelleye-updater-notice .notice-dismiss',function(event) {
        var r = confirm("If you do not install the Updater plugin you will not receive automated updates for Angell EYE products going forward!");
        if (r == true) {
            var data = {
                action : 'angelleye_updater_dismissible_admin_notice'
            };
            jQuery.post(ajaxurl, data, function (response) {
                var $el = jQuery( '#angelleye-updater-notice' );
                event.preventDefault();
                $el.fadeTo( 100, 0, function() {
                        $el.slideUp( 100, function() {
                                $el.remove();
                        });
                });
            });
        } 
    });
    var el_notice = jQuery(".angelleye-notice");
    jQuery('[id^=angelleye_notification]').each(function (i) {
        jQuery('[id="' + this.id + '"]').slice(1).remove();
    });
    el_notice.fadeIn(750);
    jQuery(".angelleye-notice-dismiss").click(function(e){
        e.preventDefault();
        jQuery( this ).parent().parent(".angelleye-notice").fadeOut(600, function () {
            jQuery( this ).parent().parent(".angelleye-notice").remove();
        });
        notify_wordpress(jQuery( this ).data("msg"));
    });
    function notify_wordpress(message) {
        var param = {
            action: 'angelleye_dismiss_notice',
            data: message
        };
        jQuery.post(ajaxurl, param);
    }
    var opt_in_logging = jQuery("#angelleye_send_opt_in_logging_details");
    jQuery('#angelleye_send_opt_in_logging_details').each(function (i) {
        jQuery('[id="' + this.id + '"]').slice(1).remove();
    });
    opt_in_logging.fadeIn(750);
});
jQuery(document).ready(function ($) {
    jQuery('#woocommerce_paypal_express_disallowed_funding_methods').closest('table').addClass('angelleye_smart_button_setting_left');
    if (angelleye_admin.is_paypal_credit_enable == "no") {
        jQuery("#woocommerce_paypal_express_show_paypal_credit").attr("disabled", true);
        jQuery("label[for='woocommerce_paypal_express_show_paypal_credit']").css('color', '#666');
    }
    jQuery("#woocommerce_paypal_express_enable_in_context_checkout_flow").change(function () {
        if (jQuery(this).is(':checked')) {
            jQuery('.in_context_checkout_part_other').show();
            jQuery('.in_context_checkout_part_other').next('p').show();
            jQuery('.in_context_checkout_part_other').parents('table').show();
            jQuery('.display_smart_button_previews').show();
            jQuery('.angelleye_button_settings_selector').show();
            jQuery('#woocommerce_paypal_express_show_paypal_credit').closest('tr').hide();
            jQuery('woocommerce_paypal_express_enable_google_analytics_click').closest('tr').hide();
            jQuery('#woocommerce_paypal_express_checkout_with_pp_button_type').closest('tr').hide();
            jQuery('.in_context_checkout_part').show();
            jQuery('.in_context_checkout_part').next('p').show();
            jQuery('.in_context_checkout_part').parents('tr').show();
        } else {
            jQuery('.in_context_checkout_part_other').hide();
            jQuery('.in_context_checkout_part_other').next('p').hide();
            jQuery('.in_context_checkout_part_other').parents('table').hide();
            jQuery('.display_smart_button_previews').hide();
            jQuery('.angelleye_button_settings_selector').hide();
            jQuery('#woocommerce_paypal_express_show_paypal_credit').closest('tr').show();
            jQuery('woocommerce_paypal_express_enable_google_analytics_click').closest('tr').show();
            jQuery('#woocommerce_paypal_express_checkout_with_pp_button_type').closest('tr').show();
            jQuery('.in_context_checkout_part').show();
            jQuery('.in_context_checkout_part').next('p').hide();
            jQuery('.in_context_checkout_part').parents('tr').hide();
        }
    }).change();
    $("#woocommerce_paypal_express_customer_service_number").attr("maxlength", "16");
    if ($("#woocommerce_paypal_express_checkout_with_pp_button_type").val() == "customimage") {
        jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_my_custom').each(function (i, el) {
            jQuery(el).closest('tr').show();
        });
    } else {
        jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_my_custom').each(function (i, el) {
            jQuery(el).closest('tr').hide();
        });
    }
    if ($("#woocommerce_paypal_express_checkout_with_pp_button_type").val() == "textbutton") {
        jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_text_button').each(function (i, el) {
            jQuery(el).closest('tr').show();
        });
    } else {
        jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_text_button').each(function (i, el) {
            jQuery(el).closest('tr').hide();
        });
    }
    $("#woocommerce_paypal_express_checkout_with_pp_button_type").change(function () {
        if ($(this).val() == "customimage") {
            jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_my_custom').each(function (i, el) {
                jQuery(el).closest('tr').show();
            });
            jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_text_button').each(function (i, el) {
                jQuery(el).closest('tr').hide();
            });
        } else if ($(this).val() == "textbutton") {
            jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_text_button').each(function (i, el) {
                jQuery(el).closest('tr').show();
            });
            jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_my_custom').each(function (i, el) {
                jQuery(el).closest('tr').hide();
            });
        } else {
            jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_my_custom').each(function (i, el) {
                jQuery(el).closest('tr').hide();
            });
            jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_text_button').each(function (i, el) {
                jQuery(el).closest('tr').hide();
            });
        }
    });
    if (angelleye_admin.is_ssl == "yes") {
        jQuery("#woocommerce_paypal_express_checkout_logo").after('<a href="#" id="checkout_logo" class="button_upload button">Upload</a>');
        jQuery("#woocommerce_paypal_express_checkout_logo_hdrimg").after('<a href="#" id="checkout_logo_hdrimg" class="button_upload button">Upload</a>');
        jQuery("#woocommerce_paypal_plus_checkout_logo").after('<a href="#" id="checkout_logo" class="button_upload button">Upload</a>');
    }
    jQuery("#woocommerce_paypal_express_pp_button_type_my_custom, #woocommerce_paypal_pro_card_icon, #woocommerce_paypal_pro_payflow_card_icon, #woocommerce_paypal_advanced_card_icon, #woocommerce_paypal_credit_card_rest_card_icon, #woocommerce_braintree_card_icon").css({float: "left"});
    jQuery("#woocommerce_paypal_express_pp_button_type_my_custom, #woocommerce_paypal_pro_card_icon, #woocommerce_paypal_pro_payflow_card_icon, #woocommerce_paypal_advanced_card_icon, #woocommerce_paypal_credit_card_rest_card_icon, #woocommerce_braintree_card_icon").after('<a href="#" id="upload" class="button_upload button">Upload</a>');
    var custom_uploader;
    $('.button_upload').click(function (e) {
        var BTthis = jQuery(this);
        e.preventDefault();
        //Extend the wp.media object
        custom_uploader = wp.media.frames.file_frame = wp.media({
            title: angelleye_admin.choose_image,
            button: {
                text: angelleye_admin.choose_image
            },
            multiple: false
        });
        //When a file is selected, grab the URL and set it as the text field's value
        custom_uploader.on('select', function () {
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            var pre_input = BTthis.prev();
            var url = attachment.url;
            if (BTthis.attr('id') != 'upload') {
                if (attachment.url.indexOf('http:') > -1) {
                    url = url.replace('http', 'https');
                }
            }
            pre_input.val(url);
        });
        //Open the uploader dialog
        custom_uploader.open();
    });
    // change target type -- toggle where input
    $('#pfw-bulk-action-target-type').change(function () {
        $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-target-where-category').hide();
        $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-target-where-product-type').hide();
        $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-target-where-price-value').hide();
        $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-target-where-stock-value').hide();
        
        $('#pfw-bulk-action-target-where-category').removeAttr('required');
        $('#pfw-bulk-action-target-where-product-type').removeAttr('required');
        $('#pfw-bulk-action-target-where-price-value').removeAttr('required');
        $('#pfw-bulk-action-target-where-stock-value').removeAttr('required');
        if ($(this).val() == 'where')
        {
            $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-target-where-type').show();
            $('#pfw-bulk-action-target-where-type').attr('required', 'required');
        } else
        {
            $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-target-where-type').hide();
            $('#pfw-bulk-action-target-where-type').removeAttr('required');
        }
    });
    // change target where type -- toggle categories/value inputs
    $('#pfw-bulk-action-type').change(function () {
        $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-payment-action-type').hide(); 
        $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-payment-authorization-type').hide();
        if ($(this).val() == 'enable_payment_action') {
            $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-payment-action-type').show(); 
        } 
    }).change();
    $('#woo_product_payment_action').change(function () {
        if ( $(this).val() === 'Authorization' ) {
            $('#woo_product_payment_action_authorization').closest('p').show();
        } else {
            $('#woo_product_payment_action_authorization').closest('p').hide();
        }
    }).change();
    $('#enable_payment_action').change(function () {
        if ($(this).is(':checked')) {
            $('.woo_product_payment_action_field').show();
            if( $('#woo_product_payment_action').val() == 'Authorization') {
                $('.woo_product_payment_action_authorization_field').show();
            }
        } else {
            $('.woo_product_payment_action_field').hide();
            $('.woo_product_payment_action_authorization_field').hide();
        }
    }).change();
    $('#pfw-bulk-action-payment-action-type').change(function () {
        if ($(this).val() == 'Authorization') {
            $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-payment-authorization-type').show(); 
        } else {
            $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-payment-authorization-type').hide(); 
        }
    }).change();
    
    
    $('#pfw-bulk-action-target-where-type').change(function () {
        if ($(this).val() == 'category')
        {
            $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-target-where-category').show();
            $('#pfw-bulk-action-target-where-category').attr('required', 'required');
            $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-target-where-product-type').hide();
            $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-target-where-price-value').hide();
            $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-target-where-stock-value').hide();
            $('#pfw-bulk-action-target-where-product-type').removeAttr('required');
            $('#pfw-bulk-action-target-where-price-value').removeAttr('required');
            $('#pfw-bulk-action-target-where-stock-value').removeAttr('required');
        } else if ($(this).val() == 'product_type')
        {
            $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-target-where-product-type').show();
            $('#pfw-bulk-action-target-where-product-type').attr('required', 'required');
            $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-target-where-category').hide();
            $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-target-where-price-value').hide();
            $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-target-where-stock-value').hide();
            $('#pfw-bulk-action-target-where-category').removeAttr('required');
            $('#pfw-bulk-action-target-where-price-value').removeAttr('required');
            $('#pfw-bulk-action-target-where-stock-value').removeAttr('required');
        } else
        {
            $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-target-where-category').hide();
            $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-target-where-product-type').hide();
            $('#pfw-bulk-action-target-where-category').removeAttr('required');
            $('#pfw-bulk-action-target-where-product-type').removeAttr('required');
            if ($(this).val() == 'price_greater' || $(this).val() == 'price_less')
            {
                $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-target-where-price-value').show();
                $('#pfw-bulk-action-target-where-price-value').attr('required', 'required');
                $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-target-where-stock-value').hide();
                $('#pfw-bulk-action-target-where-stock-value').removeAttr('required');
            } else if ($(this).val() == 'stock_greater' || $(this).val() == 'stock_less')
            {
                $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-target-where-price-value').hide();
                $('#pfw-bulk-action-target-where-price-value').removeAttr('required');
                $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-target-where-stock-value').show();
                $('#pfw-bulk-action-target-where-stock-value').attr('required', 'required');
            } else
            {
                $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-target-where-price-value').hide();
                $('#pfw-bulk-action-target-where-price-value').removeAttr('required');
                $('.angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section.pfw-bulk-action-target-where-stock-value').hide();
                $('#pfw-bulk-action-target-where-stock-value').removeAttr('required');
            }
        }
    });
    
    

    // AJAX - Bulk enable/disable tool
    $('#woocommerce_paypal-for-woocommerce_options_form_bulk_tool_shipping').submit(function ()
    {
        // show processing status
        $('#bulk-enable-tool-submit').attr('disabled', 'disabled');
        $('#bulk-enable-tool-submit').removeClass('button-primary');
        $('#bulk-enable-tool-submit').html('<i class="ofwc-spinner"></i> Processing, please wait...');
        $('#bulk-enable-tool-submit i.spinner').show();
        var actionType = $('#pfw-bulk-action-type').val();
        var actionTargetType = $('#pfw-bulk-action-target-type').val();
        var actionTargetWhereType = $('#pfw-bulk-action-target-where-type').val();
        var actionTargetWhereCategory = $('#pfw-bulk-action-target-where-category').val();
        var actionTargetWhereProductType = $('#pfw-bulk-action-target-where-product-type').val();
        var actionTargetWherePriceValue = $('#pfw-bulk-action-target-where-price-value').val();
        var actionTargetWhereStockValue = $('#pfw-bulk-action-target-where-stock-value').val();
        var payment_action = $('#pfw-bulk-action-payment-action-type').val();
        var authorization_type = $('#pfw-bulk-action-payment-authorization-type').val();
        var data = {
            'action': 'pfw_ed_shipping_bulk_tool',
            'actionType': actionType,
            'actionTargetType': actionTargetType,
            'actionTargetWhereType': actionTargetWhereType,
            'actionTargetWhereCategory': actionTargetWhereCategory,
            'actionTargetWhereProductType': actionTargetWhereProductType,
            'actionTargetWherePriceValue': actionTargetWherePriceValue,
            'actionTargetWhereStockValue': actionTargetWhereStockValue,
            'payment_action' : payment_action,
            'authorization_type' : authorization_type
        };

        // post it
        $.post(ajaxurl, data, function (response) {
            if ('failed' !== response)
            {
                var redirectUrl = response;
                top.location.replace(redirectUrl);
                return true;
            } else
            {
                alert('Error updating records.');
                return false;
            }
        });
        /*End Post*/
        return false;
    });

    jQuery('.angelleye_enable_notifyurl').change(function () {
        var express_notifyurl = jQuery('.angelleye_notifyurl').closest('tr');
        if (jQuery(this).is(':checked')) {
            express_notifyurl.show();
        } else {
            express_notifyurl.hide();
        }
    }).change();

    jQuery('.order_cancellations').change(function () {
        var email_notify_order_cancellations = jQuery('.email_notify_order_cancellations').closest('tr');
        if (jQuery(this).val() !== 'disabled') {
            email_notify_order_cancellations.show();
        } else {
            email_notify_order_cancellations.hide();
        }
    }).change();

    jQuery('#angelleye_payment_action').change(function () {
        if (jQuery(this).val() == 'DoCapture') {
            if (angelleye_admin.payment_action == 'Order') {
                jQuery("#angelleye_paypal_capture_transaction_dropdown").show();
            }
        } else {
            jQuery("#angelleye_paypal_capture_transaction_dropdown").hide();
        }

        if (jQuery(this).val() == 'DoAuthorization') {
            jQuery(".angelleye_authorization_box").show();
        } else {
            jQuery(".angelleye_authorization_box").hide();
        }

        if (jQuery(this).val() == 'DoCapture') {
            if (angelleye_admin.payment_action != 'Order') {
                jQuery(".angelleye_authorization_box").show();
            }
        }

        if (jQuery(this).val() == 'DoVoid') {
            jQuery("#angelleye_paypal_dovoid_transaction_dropdown").show();
        } else {
            jQuery("#angelleye_paypal_dovoid_transaction_dropdown").hide();
        }

        if (jQuery(this).val() == 'DoReauthorization') {
            jQuery("#angelleye_paypal_doreauthorization_transaction_dropdown").show();
        } else {
            jQuery("#angelleye_paypal_doreauthorization_transaction_dropdown").hide();
        }
        if (jQuery(this).val().length === 0) {
            jQuery('#angelleye_payment_submit_button').hide();
            return false;
        } else {
            jQuery('#angelleye_payment_submit_button').show();
        }
    });
    jQuery('.in_context_checkout_part').change(function () {
        display_angelleye_smart_button();
    });

    window.paypalCheckoutReady = function () {

        display_angelleye_smart_button();
    };

    function display_angelleye_smart_button() {
        if( $('#woocommerce_paypal_express_testmode').length ) {
            if( jQuery('#woocommerce_paypal_express_testmode').is(':checked') ) {
                var api_username = ($('#woocommerce_paypal_express_sandbox_api_username').val().length > 0) ? $('#woocommerce_paypal_express_sandbox_api_username').val() : $('#woocommerce_paypal_express_sandbox_api_username').text();
                var api_password = ($('#woocommerce_paypal_express_sandbox_api_password').val().length > 0) ? $('#woocommerce_paypal_express_sandbox_api_password').val() : $('#woocommerce_paypal_express_sandbox_api_password').text();
                var api_signature = ($('#woocommerce_paypal_express_sandbox_api_signature').val().length > 0) ? $('#woocommerce_paypal_express_sandbox_api_signature').val() : $('#woocommerce_paypal_express_sandbox_api_signature').text();
            } else {
                var api_username = ($('#woocommerce_paypal_express_api_username').val().length > 0) ? $('#woocommerce_paypal_express_api_username').val() : $('#woocommerce_paypal_express_api_username').text();
                var api_password = ($('#woocommerce_paypal_express_api_password').val().length > 0) ? $('#woocommerce_paypal_express_api_password').val() : $('#woocommerce_paypal_express_api_password').text();
                var api_signature = ($('#woocommerce_paypal_express_api_signature').val().length > 0) ? $('#woocommerce_paypal_express_api_signature').val() : $('#woocommerce_paypal_express_api_signature').text();
            }
        } else {
            return false;
        }
        
        if( api_username.length === 0 || api_password.length === 0 || api_signature.length === 0 ) {
            return false;
        }
        jQuery(".display_smart_button_previews").html('');
        var angelleye_env = jQuery('#woocommerce_paypal_express_testmode').is(':checked') ? 'sandbox' : 'production';
        if (angelleye_env === 'sandbox') {
            var payer_id = jQuery('#woocommerce_paypal_express_sandbox_api_username').val();
        } else {
            var payer_id = jQuery('#woocommerce_paypal_express_api_username').val();
        }
        var angelleye_size = jQuery("#woocommerce_paypal_express_button_size").val();
        var angelleye_height = jQuery("#woocommerce_paypal_express_button_height").val();
        var angelleye_color = jQuery("#woocommerce_paypal_express_button_color").val();
        var angelleye_shape = jQuery("#woocommerce_paypal_express_button_shape").val();
        var angelleye_label = jQuery("#woocommerce_paypal_express_button_label").val();
        var angelleye_layout = jQuery("#woocommerce_paypal_express_button_layout").val();
        var angelleye_tagline = jQuery("#woocommerce_paypal_express_button_tagline").val();
        var angelleye_fundingicons = jQuery("#woocommerce_paypal_express_button_fundingicons").val();
        if (angelleye_admin.is_paypal_credit_enable == "no") {
            var angelleye_woocommerce_paypal_express_allowed_funding_methods = ['card', 'elv', 'venmo'];
        } else {
            var angelleye_woocommerce_paypal_express_allowed_funding_methods = ['credit', 'card', 'elv', 'venmo'];
        }
        var angelleye_woocommerce_paypal_express_disallowed_funding_methods = jQuery('#woocommerce_paypal_express_disallowed_funding_methods').val();
        if (angelleye_woocommerce_paypal_express_disallowed_funding_methods === null) {
            angelleye_woocommerce_paypal_express_disallowed_funding_methods = [];
        }
        if (angelleye_layout === 'vertical') {
            angelleye_tagline = '';
            angelleye_fundingicons = '';
            if (angelleye_size === 'small') {
                angelleye_size = 'medium';
            }
            if (angelleye_label === 'credit') {
                angelleye_label = 'checkout';
            }
        }
        if (angelleye_label === 'credit') {
            angelleye_color = 'darkblue';
            angelleye_fundingicons = '';
        }

        var style_object = {size: angelleye_size,
            color: angelleye_color,
            shape: angelleye_shape,
            label: angelleye_label,
            layout: angelleye_layout,
            tagline: ( angelleye_tagline === "true" ) ? true : false
        };

        var disallowed_funding_methods = jQuery('#woocommerce_paypal_express_disallowed_funding_methods').val();
        if (disallowed_funding_methods === null) {
            disallowed_funding_methods = [];
        }
        if (angelleye_layout === 'horizontal' && (jQuery.inArray('card', disallowed_funding_methods) > -1 === false) && angelleye_label !== 'credit' && angelleye_fundingicons === "true") {
            style_object['fundingicons'] = ( angelleye_fundingicons === "true" ) ? true : false;
        }
        if( angelleye_height !== '' ) {
            style_object['height'] = parseInt(angelleye_height);
        }
        angelleye_woocommerce_paypal_express_allowed_funding_methods = jQuery.grep(angelleye_woocommerce_paypal_express_allowed_funding_methods, function (value) {
            return jQuery.inArray(value, angelleye_woocommerce_paypal_express_disallowed_funding_methods) < 0;
        });
        
        if (angelleye_admin.is_paypal_credit_enable == "no") {
            angelleye_woocommerce_paypal_express_disallowed_funding_methods.push("credit");
        }
        if(jQuery.inArray('card', angelleye_woocommerce_paypal_express_allowed_funding_methods) > -1 === true) {
            var woocommerce_paypal_express_disallowed_card_types = jQuery('#woocommerce_paypal_express_disallowed_card_types').val();
            if (woocommerce_paypal_express_disallowed_card_types === null) {
                woocommerce_paypal_express_disallowed_card_types = [];
            }
            jQuery.grep(woocommerce_paypal_express_disallowed_card_types, function (value) {
                angelleye_woocommerce_paypal_express_disallowed_funding_methods.push(value);
            });
        }
        window.paypalCheckoutReady = function () {
            paypal.Button.render({
                env: 'sandbox',
                client: {
                    sandbox:    'AZDxjDScFpQtjWTOUtWKbyN_bDt4OgqaF4eYXlewfBP4-8aqX3PiV8e1GWU6liB2CUXlkA59kJXE7M6R'
                },
                style: style_object,
                locale: angelleye_admin.locale,
                funding: {
                    allowed: angelleye_woocommerce_paypal_express_allowed_funding_methods,
                    disallowed: angelleye_woocommerce_paypal_express_disallowed_funding_methods
                },
                payment: function(data, actions) {
                    return actions.payment.create({
                        payment: {
                            transactions: [
                                {
                                    amount: { total: '0.01', currency: 'USD' }
                                }
                            ]
                        }
                    });
                },
                onAuthorize: function (data, actions) {
                    return actions.payment.execute().then(function() {
                       window.alert('Payment Complete!');
                   });
                },
                onCancel: function (data, actions) {

                },
                onError: function (err) {
                }
            }, '.display_smart_button_previews');

        };
    }
    
    jQuery('.show-on-product-page').change(function () {
        var express_default_enable = jQuery('.enable-newly-products-bydefault').closest('tr');
        if (jQuery(this).is(':checked')) {
            express_default_enable.show();
        } else {
            express_default_enable.hide();
        }
    }).change();
    
});