jQuery(function ($) {
    if (typeof paypal_sdk === 'undefined') {
        return false;
    }
    if (typeof angelleye_credit_messaging === 'undefined') {
        return false;
    }
    var front_end_shortcode_page_credit_messaging_preview = function () {
        var shortcode_style_object = {};
        shortcode_style_object['layout'] = angelleye_credit_messaging.style;
        if (shortcode_style_object['layout'] === 'text') {
            shortcode_style_object['logo'] = {};
            shortcode_style_object['logo']['type'] = angelleye_credit_messaging.logotype;
            if (shortcode_style_object['logo']['type'] === 'primary' || angelleye_credit_messaging.logotype === 'alternative') {
                shortcode_style_object['logo']['position'] = angelleye_credit_messaging.logoposition;
            }
            shortcode_style_object['text'] = {};
            shortcode_style_object['text']['size'] = parseInt(angelleye_credit_messaging.textsize);
            shortcode_style_object['text']['color'] = angelleye_credit_messaging.textcolor;
        } else {
            shortcode_style_object['color'] = angelleye_credit_messaging.color;
            shortcode_style_object['ratio'] = angelleye_credit_messaging.ratio;
        }
        if (typeof paypal_sdk !== 'undefined') {
            paypal_sdk.Messages({
                amount: angelleye_credit_messaging.amount,
                placement: angelleye_credit_messaging.placement,
                style: shortcode_style_object
            }).render('.angelleye_pp_message_shortcode');
        }
    };
    front_end_shortcode_page_credit_messaging_preview();
});