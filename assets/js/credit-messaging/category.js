jQuery(function ($) {
    if (typeof paypal_sdk === 'undefined') {
        return false;
    }
    if (typeof angelleye_credit_messaging === 'undefined') {
        return false;
    }
    var front_end_category_page_credit_messaging_preview = function () {
        var category_style_object = {};
        category_style_object['layout'] = angelleye_credit_messaging.credit_messaging_category_layout_type;
        if (category_style_object['layout'] === 'text') {
            category_style_object['logo'] = {};
            category_style_object['logo']['type'] = angelleye_credit_messaging.credit_messaging_category_text_layout_logo_type;
            if (category_style_object['logo']['type'] === 'primary' || category_style_object['logo']['type'] === 'alternative') {
                category_style_object['logo']['position'] = angelleye_credit_messaging.credit_messaging_category_text_layout_logo_position;
            }
            category_style_object['text'] = {};
            category_style_object['text']['size'] = parseInt(angelleye_credit_messaging.credit_messaging_category_text_layout_text_size);
            category_style_object['text']['color'] = angelleye_credit_messaging.credit_messaging_category_text_layout_text_color;
        } else {
            category_style_object['color'] = angelleye_credit_messaging.credit_messaging_category_flex_layout_color;
            category_style_object['ratio'] = angelleye_credit_messaging.credit_messaging_category_flex_layout_ratio;
        }
        if (typeof paypal_sdk !== 'undefined') {
            paypal_sdk.Messages({
                placement: 'category',
                style: category_style_object
            }).render('.angelleye_pp_message_category');
        }
    };
    front_end_category_page_credit_messaging_preview();
});