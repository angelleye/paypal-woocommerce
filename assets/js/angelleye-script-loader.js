window.loadedScripts = []

function finalLoaded(scriptUrl, event) {
    loadedScripts[scriptUrl].status = 'loaded';
    let scheduledCallbacks = loadedScripts[scriptUrl].queue;

    if (scheduledCallbacks.length) {
        for(let i = 0; i < scheduledCallbacks.length; i++) {
            if (typeof scheduledCallbacks[i] === 'function') {
                scheduledCallbacks[i]();
                delete loadedScripts[scriptUrl].queue[i];
            }
        }
    }
}

function angelleyeLoadPayPalScript(config, onLoaded) {
    if (typeof loadedScripts[config.url] !== 'undefined') {
        if (loadedScripts[config.url]['status'] === 'loaded') {
            onLoaded();
        } else {
            loadedScripts[config.url].queue.push(onLoaded);
        }
        return;
    }

    loadedScripts[config.url] = {'status': 'pending', 'queue': []};
    loadedScripts[config.url].queue.push(onLoaded);
    let script = document.createElement('script');
    let scriptUrl = config.url;
    // delay the onload event to let the PayPal lib initialized in the env
    script.addEventListener('load', finalLoaded.bind(null, scriptUrl));
    script.setAttribute('src', config.url);
    script.async = true;
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
    if (!isOrderCompletePage && angelleyeOrder.isAngelleyePaymentMethodSelected() && !angelleyeOrder.isSavedPaymentMethodSelected()) {
        return false;
    }
    return true;
}

function showHidePlaceOrderBtn() {
    // console.log(canShowPlaceOrderBtn(), abc.sss);
    if (canShowPlaceOrderBtn()) {
        jQuery('#place_order').removeClass('hide_place_order_btn').show();
    } else {
        jQuery('#place_order').addClass('hide_place_order_btn').hide();
    }
}

jQuery(document).ready(function () {
    jQuery(document.body).on('change', 'input[name="payment_method"]', function () {
        canShowPlaceOrderBtn();
    });
})
