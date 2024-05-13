window.loadedScripts = []

function finalLoaded(scriptUrl, isLoaded, event) {
    loadedScripts[scriptUrl].status = isLoaded ? 'loaded' : 'error';
    let scheduledCallbacks = isLoaded ? loadedScripts[scriptUrl].queue : loadedScripts[scriptUrl].error_queue;
    if (!isLoaded) {
        loadedScripts[scriptUrl].queue = [];
    } else {
        loadedScripts[scriptUrl].error_queue = [];
    }
    if (scheduledCallbacks.length) {
        for(let i = 0; i < scheduledCallbacks.length; i++) {
            if (typeof scheduledCallbacks[i] === 'function') {
                scheduledCallbacks[i]();
                if (isLoaded) {
                    delete loadedScripts[scriptUrl].queue[i];
                } else {
                    delete loadedScripts[scriptUrl].error_queue[i];
                }
            }
        }
    }
}

function angelleyeLoadPayPalScript(config, onLoaded, onError) {
    if (typeof loadedScripts[config.url] !== 'undefined' && loadedScripts[config.url]['status'] !== 'error') {
        if (loadedScripts[config.url]['status'] === 'loaded') {
            onLoaded();
        } else {
            loadedScripts[config.url].queue.push(onLoaded);
            typeof onError === 'function' && loadedScripts[config.url].error_queue.push(onError);
        }
        return;
    }

    loadedScripts[config.url] = {status: 'pending', queue: [], error_queue: []};
    loadedScripts[config.url].queue.push(onLoaded);
    typeof onError === 'function' && loadedScripts[config.url].error_queue.push(onError);

    let script = document.createElement('script');
    let scriptUrl = config.url;
    // delay the onload event to let the PayPal lib initialized in the env
    script.addEventListener('load', finalLoaded.bind(null, scriptUrl, true));
    script.addEventListener('error', finalLoaded.bind(null, scriptUrl, false));
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
    if (angelleyeOrder.isPpcpPaymentMethodSelected() && angelleye_ppcp_manager.is_checkout_disable_smart_button === 'yes') {
        return true;
    }
    if (angelleyeOrder.isPpcpPaymentMethodSelected() && angelleye_ppcp_manager.is_hide_place_order_button === 'no') {
        return true;
    }
    return !(!isOrderCompletePage
        && angelleyeOrder.isAngelleyePaymentMethodSelected()
        && !angelleyeOrder.isSavedPaymentMethodSelected());
}

function showHidePlaceOrderBtn() {
    if (canShowPlaceOrderBtn()) {
        jQuery('#place_order, .wc-block-components-checkout-place-order-button').removeClass('hide_place_order_btn').show();
    } else {
        jQuery('#place_order, .wc-block-components-checkout-place-order-button').addClass('hide_place_order_btn').hide();
    }
}

jQuery(document).ready(function () {
    jQuery(document.body).on('change', 'input[name="payment_method"]', function () {
        canShowPlaceOrderBtn();
    });
})
