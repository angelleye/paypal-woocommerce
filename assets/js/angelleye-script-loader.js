let loadedScripts = []
function angelleyeLoadPayPalScript(config, onLoaded) {
    if (loadedScripts.indexOf(config.url) > -1) {
        onLoaded();
        return;
    }

    let script = document.createElement('script');
    // delay the onload event to let the PayPal lib initialized in the env
    script.addEventListener('load', onLoaded);
    script.setAttribute('src', config.url);
    if (config.script_attributes) {
        Object.entries(config.script_attributes).forEach((keyValue) => {
            script.setAttribute(keyValue[0], keyValue[1]);
        });
    }

    document.body.appendChild(script);
}

function canShowPlaceOrderBtn() {
    // This is to check if the user is on review order page then there we need to show the place order button.
    // For logged in user we see the payment method that's why on checkout page we keep seeing the place order button
    // that we need to fix by using a way to identify if its checkout or order review page
    let isOrderCompletePage = angelleyeOrder.isOrderCompletePage();
    // console.log('canShowPlaceOrderBtn', isOrderCompletePage, angelleyeOrder.isAngelleyePaymentMethodSelected());
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
