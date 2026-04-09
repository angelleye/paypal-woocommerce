function initSmartButtons() {
    console.log('initSmartButtons');
    let $ = jQuery;
    if (typeof angelleye_ppcp_manager === 'undefined') {
        return false;
    }
    
    let checkoutSelector = angelleyeOrder.getCheckoutSelectorCss();
    const triggerPpcpCcSubmit = () => {
        if (!angelleyeOrder.isPpcpCcSubmitHookReady()) {
            angelleyeOrder.renderHostedButtons();
        }

        if (!angelleyeOrder.isPpcpCcSubmitHookReady()) {
            angelleyeOrder.clearPpcpCcSubmittingState(checkoutSelector);
            return false;
        }

        angelleyeOrder.showProcessingSpinner();
        $(checkoutSelector).addClass('paypal_cc_submiting');
        angelleyeOrder.startPpcpCcSubmitWatchdog(checkoutSelector);
        $(document.body).trigger('submit_paypal_cc_form');
        return false;
    };
    if ($('.variations_form').length) {
            let div_to_hide_show = '#angelleye_ppcp_product, #angelleye_ppcp_product_google_pay, #angelleye_ppcp_product_apple_pay';
            $('.variations_form').on('show_variation', function () {
                    $(div_to_hide_show).show();
            }).on('hide_variation', function () {
                    $(div_to_hide_show).hide();
            });
    }

    if ($(document.body).hasClass('woocommerce-order-pay')) {
        $('#order_review').on('submit', function (event) {
            if (angelleyeOrder.isCardFieldEligible() === true) {
                event.preventDefault();
                if ($('input[name="wc-angelleye_ppcp_cc-payment-token"]').length) {
                    if ('new' !== $('input[name="wc-angelleye_ppcp_cc-payment-token"]:checked').val()) {
                        return true;
                    }
                }
                if ($(checkoutSelector).is('.paypal_cc_submiting')) {
                    return false;
                } else {
                    return triggerPpcpCcSubmit();
                }
                return false;
            }
            return true;
        });
    }
    
    $(checkoutSelector).on('checkout_place_order_angelleye_ppcp_cc', function (event) {
        if (angelleyeOrder.isCardFieldEligible() === true) {
            event.preventDefault();
            if ($('input[name="wc-angelleye_ppcp_cc-payment-token"]').length) {
                if ('new' !== $('input[name="wc-angelleye_ppcp_cc-payment-token"]:checked').val()) {
                    return true;
                }
            }
            if ($(checkoutSelector).is('.paypal_cc_submiting')) {
                return false;
            } else {
                return triggerPpcpCcSubmit();
            }
            return false;
        }
        return true;
    });

    angelleyeOrder.isCheckoutPage() === false ? angelleyeOrder.renderSmartButton() : null;

    if (angelleye_ppcp_manager.is_pay_page === 'yes') {
        angelleyeOrder.hideShowPlaceOrderButton();
        setTimeout(function () {
            angelleyeOrder.renderSmartButton();
            if (angelleyeOrder.isCardFieldEligible() === true) {
                if ($('#angelleye_ppcp_cc-card-number iframe').length === 0) {
                    $(angelleyeOrder.getCheckoutSelectorCss()).removeClass('CardFields');
                }
                $('.checkout_cc_separator').show();
                $('#wc-angelleye_ppcp-cc-form').show();
                angelleyeOrder.renderHostedButtons();
            }
        }, 300);
    }

    angelleyeOrder.updateCartTotalsInEnvironment();
    angelleyeOrder.hooks.onPaymentCancellation();
    angelleyeOrder.hooks.handleWooEvents();

    // Re-render PayPal buttons and sync cart totals when FunnelKit sliding cart updates.
    // Listens to WC core add/remove events plus FKCart-specific events for in-cart qty/coupon changes.
    var fkcartUpdateEvents = 'added_to_cart removed_from_cart fkcart_fragments_refreshed fkcart_fragments_loaded fkcart_quantity_updated fkcart_coupon_applied fkcart_coupon_remove';
    $(document.body).on(fkcartUpdateEvents, function() {
        setTimeout(function() {
            // Re-render PayPal button if container exists but is empty (FKCart replaced the HTML)
            if ($('#angelleye_ppcp_fkcart').length && !$('#angelleye_ppcp_fkcart').children().length) {
                angelleyeOrder.renderSmartButton();
            }
            // Sync cart totals so PayPal popup reflects latest qty/coupon changes
            if (typeof angelleyeOrder.updateCartTotalsInEnvironment === 'function') {
                angelleyeOrder.updateCartTotalsInEnvironment();
            }
        }, 200);
    });

    angelleyeOrder.triggerPendingEvents();

    $(document.body).on('removed_coupon_in_checkout', function () {
        window.location.href = window.location.href;
    });
}

(function () {
    'use strict';

    angelleyeOrder.hooks.handleRaceConditionOnWooHooks();

    const paypalSdkLoadCallback = () => {
        console.log('PayPal lib loaded, initialize buttons.');
        let scriptsToLoad = [];
        if (angelleyeOrder.isApplePayEnabled()) {
            let appleResolveOnLoad = new Promise((resolve) => {
                console.log('apple sdk loaded');
                resolve();
            });
            scriptsToLoad.push({
                url: angelleye_ppcp_manager.apple_sdk_url,
                callback: appleResolveOnLoad
            });
        }

        if (angelleyeOrder.isGooglePayEnabled()) {
            let googleResolveOnLoad = new Promise((resolve) => {
                console.log('google sdk loaded');
                resolve();
            });
            scriptsToLoad.push({
                url: angelleye_ppcp_manager.google_sdk_url,
                callback: googleResolveOnLoad
            });
        }

        if (scriptsToLoad.length === 0) {
            initSmartButtons();
        } else {
            let allPromises = [];
            for (let i = 0; i < scriptsToLoad.length; i++) {
                allPromises.push(scriptsToLoad[i].callback);
            }
            Promise.all(allPromises).then((success) => {
                console.log('all libs loaded');
                initSmartButtons();
            }, (error) => {
                console.log('An error occurred in loading the SDKs.');
            });
            for (let i = 0; i < scriptsToLoad.length; i++) {
                angelleyeLoadPayPalScript(scriptsToLoad[i], scriptsToLoad[i].callback);
            }
        }
    };

    window.angelleyeLoadAsyncLibs = (callback, errorCallback) => {
        angelleyeLoadPayPalScript({
            url: angelleye_ppcp_manager.paypal_sdk_url,
            script_attributes: angelleye_ppcp_manager.paypal_sdk_attributes
        }, callback, errorCallback);
    };

    window.angelleyeLoadAsyncLibs(paypalSdkLoadCallback);



    
})(jQuery);

window.onerror = function (msg, source, lineNo) {
	angelleyeJsErrorLogger.logJsError({
		'msg': msg,
		'source': source,
		'line': lineNo,
	});
}
