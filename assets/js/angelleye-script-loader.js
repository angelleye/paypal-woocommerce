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
    let paymentMethod = jQuery('input[name="payment_method"]:checked').val();
    if (paymentMethod === 'paypal_express' || paymentMethod === 'angelleye_ppcp') {
        return false;
    }
    return true;
}

function showHidePlaceOrderBtn() {
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
