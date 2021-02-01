jQuery(function ($) {
    if (typeof paypal_sdk === 'undefined') {
        return false;
    }
    if (typeof angelleye_credit_messaging === 'undefined') {
        return false;
    }
    var front_end_payment_page_credit_messaging_preview = function () {
        var payment_style_object = {};
        payment_style_object['layout'] = angelleye_credit_messaging.credit_messaging_payment_layout_type;
        if (payment_style_object['layout'] === 'text') {
            payment_style_object['logo'] = {};
            payment_style_object['logo']['type'] = angelleye_credit_messaging.credit_messaging_payment_text_layout_logo_type;
            if (payment_style_object['logo']['type'] === 'primary' || payment_style_object['logo']['type'] === 'alternative') {
                payment_style_object['logo']['position'] = angelleye_credit_messaging.credit_messaging_payment_text_layout_logo_position;
            }
            payment_style_object['text'] = {};
            payment_style_object['text']['size'] = parseInt(angelleye_credit_messaging.credit_messaging_payment_text_layout_text_size);
            payment_style_object['text']['color'] = angelleye_credit_messaging.credit_messaging_payment_text_layout_text_color;
        } else {
            payment_style_object['color'] = angelleye_credit_messaging.credit_messaging_payment_flex_layout_color;
            payment_style_object['ratio'] = angelleye_credit_messaging.credit_messaging_payment_flex_layout_ratio;
        }
        if (typeof paypal_sdk !== 'undefined') {
            paypal_sdk.Messages({
                amount: angelleye_credit_messaging.amount,
                placement: 'payment',
                style: payment_style_object
            }).render('.angelleye_pp_message_payment');
        }
    };
    $(document.body).on('updated_shipping_method wc_fragments_refreshed updated_checkout', function (event) {
        front_end_payment_page_credit_messaging_preview();
    });
    front_end_payment_page_credit_messaging_preview();
});