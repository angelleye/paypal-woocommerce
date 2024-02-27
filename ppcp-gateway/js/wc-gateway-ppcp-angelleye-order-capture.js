(function ($) {
    'use strict';
    $(function () {
        if ($('#ship-to-different-address-checkbox').length) {
            $('#ship-to-different-address-checkbox').prop('checked', true);
        }
        $(".angelleye_ppcp_edit_billing_address").click(function () {
            $('body').trigger('update_checkout');
            $('.angelleye_ppcp_billing_details').hide();
            $('.woocommerce-billing-fields').show();
            $('.woocommerce-billing-fields h3').show();
            $(".angelleye_ppcp-order-review p").removeClass("angelleye_ppcp_billing_hide");
        });
        $(".angelleye_ppcp_edit_shipping_address").click(function () {
            $('body').trigger('update_checkout');
            $('.angelleye_ppcp_shipping_details').hide();
            $('.woocommerce-shipping-fields').show();
            $('#ship-to-different-address').show();
            $('.woocommerce-additional-fields').show();
            $(".angelleye_ppcp-order-review p").removeClass("angelleye_ppcp_shipping_hide");
        });
        if ($('#place_order').length) {
            $('html, body').animate({
                scrollTop: ($('#place_order').offset().top - 500)
            }, 1000);
        }
    });
})(jQuery);