jQuery(function ($) {
    if (typeof angelleye_pay_later_messaging === 'undefined') {
        return false;
    }
    var front_end_payment_page_pay_later_messaging_preview = function () {
        var payment_style_object = {};
        payment_style_object['layout'] = angelleye_pay_later_messaging.pay_later_messaging_payment_layout_type;
        if (payment_style_object['layout'] === 'text') {
            payment_style_object['logo'] = {};
            payment_style_object['logo']['type'] = angelleye_pay_later_messaging.pay_later_messaging_payment_text_layout_logo_type;
            if (payment_style_object['logo']['type'] === 'primary' || payment_style_object['logo']['type'] === 'alternative') {
                payment_style_object['logo']['position'] = angelleye_pay_later_messaging.pay_later_messaging_payment_text_layout_logo_position;
            }
            payment_style_object['text'] = {};
            payment_style_object['text']['size'] = parseInt(angelleye_pay_later_messaging.pay_later_messaging_payment_text_layout_text_size);
            payment_style_object['text']['color'] = angelleye_pay_later_messaging.pay_later_messaging_payment_text_layout_text_color;
        } else {
            payment_style_object['color'] = angelleye_pay_later_messaging.pay_later_messaging_payment_flex_layout_color;
            payment_style_object['ratio'] = angelleye_pay_later_messaging.pay_later_messaging_payment_flex_layout_ratio;
        }
        if (typeof angelleye_paypal_sdk !== 'undefined' && angelleye_pay_later_messaging.amount > 0) {
            angelleye_paypal_sdk.Messages({
                amount: angelleye_pay_later_messaging.amount,
                placement: 'payment',
                style: payment_style_object
            }).render('.angelleye_ppcp_message_payment');
        }
    };
    $(document.body).on('updated_checkout', function () {
        front_end_payment_page_pay_later_messaging_preview();
    });
});