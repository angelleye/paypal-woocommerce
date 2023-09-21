(function () {
    'use strict';
    function initializePayPal() {
        console.log('PayPal lib loaded, initialize pay later messaging.');
        angelleyeOrder.CCAddPaymentMethod();
    }
    angelleyeLoadPayPalScript({
        url: angelleye_ppcp_manager.paypal_sdk_url,
        script_attributes: angelleye_ppcp_manager.paypal_sdk_attributes
    }, initializePayPal);
})(jQuery);