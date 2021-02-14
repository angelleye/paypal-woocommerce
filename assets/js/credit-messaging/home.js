jQuery(function ($) {
    if (typeof paypal_sdk === 'undefined') {
        return false;
    }
    if (typeof angelleye_credit_messaging === 'undefined') {
        return false;
    }
    var front_end_home_page_credit_messaging_preview = function () {
        var home_style_object = {};
        home_style_object['layout'] = angelleye_credit_messaging.credit_messaging_home_layout_type;
        if (home_style_object['layout'] === 'text') {
            home_style_object['logo'] = {};
            home_style_object['logo']['type'] = angelleye_credit_messaging.credit_messaging_home_text_layout_logo_type;
            if (home_style_object['logo']['type'] === 'primary' || home_style_object['logo']['type'] === 'alternative') {
                home_style_object['logo']['position'] = angelleye_credit_messaging.credit_messaging_home_text_layout_logo_position;
            }
            home_style_object['text'] = {};
            home_style_object['text']['size'] = parseInt(angelleye_credit_messaging.credit_messaging_home_text_layout_text_size);
            home_style_object['text']['color'] = angelleye_credit_messaging.credit_messaging_home_text_layout_text_color;
        } else {
            home_style_object['color'] = angelleye_credit_messaging.credit_messaging_home_flex_layout_color;
            home_style_object['ratio'] = angelleye_credit_messaging.credit_messaging_home_flex_layout_ratio;
        }
        if (typeof paypal_sdk !== 'undefined') {
            paypal_sdk.Messages({
                placement: 'home',
                style: home_style_object
            }).render('.angelleye_pp_message_home');
        }
    };
    front_end_home_page_credit_messaging_preview();
});