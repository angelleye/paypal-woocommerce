
function angelleyeLoadPayPalScript(config, onLoaded) {
    if (typeof angelleye_paypal_sdk !== 'undefined') {
        onLoaded();
        return;
    }

    let script = document.createElement('script');
    // delay the onload event to let the PayPal lib initialized in the env
    script.addEventListener('load', onLoaded);
    script.setAttribute('src', config.url);
    Object.entries(config.script_attributes).forEach((keyValue) => {
        script.setAttribute(keyValue[0], keyValue[1]);
    });

    document.body.appendChild(script);
}

function canShowPlaceOrderBtn() {
    let isOrderCompletePage = jQuery('#angelleye_order_review_payment_method').length;
    if (!isOrderCompletePage && (angelleyeOrder.isAngelleyePaymentMethodSelected())) {
        return false;
    }
    return true;
}

function showHidePlaceOrderBtn() {
    // console.log(canShowPlaceOrderBtn(), abc.sss);
    if (canShowPlaceOrderBtn()) {
        jQuery('#place_order').show();
    } else {
        jQuery('#place_order').hide();
    }
}

jQuery(document).ready(function () {
    jQuery(document.body).on('change', 'input[name="payment_method"]', function () {
        canShowPlaceOrderBtn();
    });
})
