jQuery(function ($) {
    if (typeof paypal === 'undefined') {
        return false;
    }
    if (typeof angelleye_credit_messaging === 'undefined') {
        return false;
    }
    var front_end_checkout_page_credit_messaging_preview = function () {
        var checkout_style_object = {};
        checkout_style_object['layout'] = angelleye_credit_messaging.credit_messaging_checkout_layout_type;
        if (checkout_style_object['layout'] === 'text') {
            checkout_style_object['logo'] = {};
            checkout_style_object['logo']['type'] = angelleye_credit_messaging.credit_messaging_checkout_text_layout_logo_type;
            if (checkout_style_object['logo']['type'] === 'primary' || checkout_style_object['logo']['type'] === 'alternative') {
                checkout_style_object['logo']['position'] = angelleye_credit_messaging.credit_messaging_checkout_text_layout_logo_position;
            }
            checkout_style_object['text'] = {};
            checkout_style_object['text']['size'] = parseInt(angelleye_credit_messaging.credit_messaging_checkout_text_layout_text_size);
            checkout_style_object['text']['color'] = angelleye_credit_messaging.credit_messaging_checkout_text_layout_text_color;
        } else {
            checkout_style_object['color'] = angelleye_credit_messaging.credit_messaging_checkout_flex_layout_color;
            checkout_style_object['ratio'] = angelleye_credit_messaging.credit_messaging_checkout_flex_layout_ratio;
        }
        if (typeof paypal !== 'undefined') {
            paypal.Messages({
                amount: 500,
                placement: 'checkout',
                style: checkout_style_object
            }).render('.angelleye_pp_message_checkout');
        }
    };
    front_end_checkout_page_credit_messaging_preview();
});