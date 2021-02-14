jQuery(function ($) {
    if (typeof paypal_sdk === 'undefined') {
        return false;
    }
    if (typeof angelleye_credit_messaging === 'undefined') {
        return false;
    }
    var front_end_cart_page_credit_messaging_preview = function () {
        var cart_style_object = {};
        cart_style_object['layout'] = angelleye_credit_messaging.credit_messaging_cart_layout_type;
        if (cart_style_object['layout'] === 'text') {
            cart_style_object['logo'] = {};
            cart_style_object['logo']['type'] = angelleye_credit_messaging.credit_messaging_cart_text_layout_logo_type;
            if (cart_style_object['logo']['type'] === 'primary' || cart_style_object['logo']['type'] === 'alternative') {
                cart_style_object['logo']['position'] = angelleye_credit_messaging.credit_messaging_cart_text_layout_logo_position;
            }
            cart_style_object['text'] = {};
            cart_style_object['text']['size'] = parseInt(angelleye_credit_messaging.credit_messaging_cart_text_layout_text_size);
            cart_style_object['text']['color'] = angelleye_credit_messaging.credit_messaging_cart_text_layout_text_color;
        } else {
            cart_style_object['color'] = angelleye_credit_messaging.credit_messaging_cart_flex_layout_color;
            cart_style_object['ratio'] = angelleye_credit_messaging.credit_messaging_cart_flex_layout_ratio;
        }
        $('.angelleye_pp_message_cart').addClass('angelleye_' + angelleye_credit_messaging.credit_messaging_cart_layout_type);
        if (typeof paypal_sdk !== 'undefined') {
            paypal_sdk.Messages({
                amount: angelleye_credit_messaging.amount,
                placement: 'cart',
                style: cart_style_object
            }).render('.angelleye_pp_message_cart');
        }
    };
    front_end_cart_page_credit_messaging_preview();
});