(function () {
    'use strict';
    function initializePayPal() {
        console.log('PayPal lib loaded, initialize add payment method.');
        angelleyeOrder.addPaymentMethodAdvancedCreditCard();
    }
    angelleyeLoadPayPalScript({
        url: angelleye_ppcp_manager.paypal_sdk_url,
        script_attributes: angelleye_ppcp_manager.paypal_sdk_attributes
    }, initializePayPal);
})(jQuery);
