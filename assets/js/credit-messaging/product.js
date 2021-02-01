jQuery(function ($) {
    if (typeof paypal_sdk === 'undefined') {
        return false;
    }
    if (typeof angelleye_credit_messaging === 'undefined') {
        return false;
    }
  
    var front_end_product_page_credit_messaging_preview = function () {
        var product_style_object = {};
        product_style_object['layout'] = angelleye_credit_messaging.credit_messaging_product_layout_type;
        if (product_style_object['layout'] === 'text') {
            product_style_object['logo'] = {};
            product_style_object['logo']['type'] = angelleye_credit_messaging.credit_messaging_product_text_layout_logo_type;
            if (product_style_object['logo']['type'] === 'primary' || product_style_object['logo']['type'] === 'alternative') {
                product_style_object['logo']['position'] = angelleye_credit_messaging.credit_messaging_product_text_layout_logo_position;
            }
            product_style_object['text'] = {};
            product_style_object['text']['size'] = parseInt(angelleye_credit_messaging.credit_messaging_product_text_layout_text_size);
            product_style_object['text']['color'] = angelleye_credit_messaging.credit_messaging_product_text_layout_text_color;
        } else {
            product_style_object['color'] = angelleye_credit_messaging.credit_messaging_product_flex_layout_color;
            product_style_object['ratio'] = angelleye_credit_messaging.credit_messaging_product_flex_layout_ratio;
        }
        if (typeof paypal_sdk !== 'undefined') {
            paypal_sdk.Messages({
                amount: angelleye_credit_messaging.amount,
                placement: 'product',
                style: product_style_object
            }).render('.angelleye_pp_message_product');
        }
    };
    front_end_product_page_credit_messaging_preview();
});