jQuery(function ($) {
    if (typeof angelleye_pay_later_messaging === 'undefined') {
        return false;
    }
    var front_end_shortcode_page_pay_later_messaging_preview = function () {
        var shortcode_style_object = {};
        shortcode_style_object['layout'] = angelleye_pay_later_messaging.style;
        if (shortcode_style_object['layout'] === 'text') {
            shortcode_style_object['logo'] = {};
            shortcode_style_object['logo']['type'] = angelleye_pay_later_messaging.logotype;
            if (shortcode_style_object['logo']['type'] === 'primary' || angelleye_pay_later_messaging.logotype === 'alternative') {
                shortcode_style_object['logo']['position'] = angelleye_pay_later_messaging.logoposition;
            }
            shortcode_style_object['text'] = {};
            shortcode_style_object['text']['size'] = parseInt(angelleye_pay_later_messaging.textsize);
            shortcode_style_object['text']['color'] = angelleye_pay_later_messaging.textcolor;
        } else {
            shortcode_style_object['color'] = angelleye_pay_later_messaging.color;
            shortcode_style_object['ratio'] = angelleye_pay_later_messaging.ratio;
        }
        if (typeof angelleye_paypal_sdk !== 'undefined') {
            angelleye_paypal_sdk.Messages({
                amount: angelleye_pay_later_messaging.amount,
                placement: angelleye_pay_later_messaging.placement,
                style: shortcode_style_object
            }).render('.angelleye_ppcp_message_shortcode');
        }
    };
    front_end_shortcode_page_pay_later_messaging_preview();
});