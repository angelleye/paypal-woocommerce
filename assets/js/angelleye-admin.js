jQuery(document).ready(function ($) {
    if (angelleye_admin.shop_based_us=="no") {
        jQuery("#woocommerce_paypal_express_show_paypal_credit").attr("disabled",true);
        jQuery("label[for='woocommerce_paypal_express_show_paypal_credit']").css('color','#666');
    }

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
    jQuery("#woocommerce_paypal_express_pp_button_type_my_custom").css({float: "left"});
    jQuery("#woocommerce_paypal_express_pp_button_type_my_custom").after('<a href="#" id="upload" class="button_upload button">Upload</a>');
    if (angelleye_admin.is_ssl=="yes") {
        jQuery("#woocommerce_paypal_express_checkout_logo").after('<a href="#" id="checkout_logo" class="button_upload button">Upload</a>');
        jQuery("#woocommerce_paypal_express_checkout_logo_hdrimg").after('<a href="#" id="checkout_logo_hdrimg" class="button_upload button">Upload</a>');
    }
    //PayPal Pro
    jQuery("#woocommerce_paypal_pro_card_icon").css({float: "left"});
    jQuery("#woocommerce_paypal_pro_card_icon").after('<a href="#" id="upload" class="button_upload button">Upload</a>');


    //PayPal Flow
    jQuery("#woocommerce_paypal_pro_payflow_card_icon").css({float: "left"});
    jQuery("#woocommerce_paypal_pro_payflow_card_icon").after('<a href="#" id="upload" class="button_upload button">Upload</a>');

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
});