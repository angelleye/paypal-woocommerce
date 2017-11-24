jQuery(document).ready(function ($) {
    if (angelleye_frontend.is_product == "yes") {
        jQuery(".single_add_to_cart_button.paypal_checkout_button").click(function () {
            if (!$('.woocommerce-variation-add-to-cart').hasClass('woocommerce-variation-add-to-cart-disabled')) {
                $('.cart').block({
                    message: null,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                    }
                });
                var angelleye_action = $(this).data('action');
                if( typeof angelleye_action === 'undefined' || angelleye_action === null ){
                    angelleye_action = $(this).attr("href");
                }
                $('form.cart').attr('action', angelleye_action);
                $(this).attr('disabled', 'disabled');
                $('form.cart').submit();
                return false;
            }
        });
    }
    if (angelleye_frontend.is_cart == "yes") {
        $(".paypal_checkout_button").click(function () {
            $('.woocommerce').block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
            var angelleye_action;
            if ($("#wc-paypal_express-new-payment-method").is(':checked')) {
                angelleye_action = $(this).attr("href");
                angelleye_action = angelleye_action + '&ec_save_to_account=true';
            } else {
                angelleye_action = $(this).attr("href");
            }
            $(this).attr("href", angelleye_action);
        });
    }

    if (angelleye_frontend.is_checkout == "yes") {
        
        jQuery(".paypal_checkout_button").click(function () {
            $('.woocommerce').block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
            if ($("#wc-paypal_express-new-payment-method").is(':checked')) {
                angelleye_action = $(this).attr("href");
                angelleye_action = angelleye_action + '&ec_save_to_account=true';
            } else {
                angelleye_action = $(this).attr("href");
            }
            $(this).attr("href", angelleye_action);
            return true;
        });
    }
    // Let themes/plugins override our event handlers
    // NOTE: The jQuery .on() function attached to listen to the event below MUST
    // be included somewhere on the page itself...so it gets registered before we load
    // asyncronous JavaScript resources. Otherwise your function listening might not fire.

    $(".paypal_checkout_button").trigger('angelleye_paypal_checkout_button_js_loaded');
    
    if( $('.angelleye_button_single').width() < 327 ) {
        $('.angelleye_button_single a.paypal_checkout_button img.ppcreditlogo.ec_checkout_page_button_type_pc').css('margin', '0px 0px');
    } 
});
