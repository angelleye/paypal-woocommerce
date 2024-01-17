function initSmartButtons() {
    console.log('initSmartButtons');
    let $ = jQuery;
    if (typeof angelleye_ppcp_manager === 'undefined') {
        return false;
    }
    
    let checkoutSelector = angelleyeOrder.getCheckoutSelectorCss();

    if ($('.variations_form').length) {
        $('.variations_form')
            .on('show_variation', function () {
                $('#angelleye_ppcp_product').show();
            })
            .on('hide_variation', function () {
                $('#angelleye_ppcp_product').hide();
            });
    }

    if ($(document.body).hasClass('woocommerce-order-pay')) {
        $('#order_review').on('submit', function (event) {
            if (angelleyeOrder.isHostedFieldEligible() === true) {
                event.preventDefault();
                if ($('input[name="wc-angelleye_ppcp_cc-payment-token"]').length) {
                    if ('new' !== $('input[name="wc-angelleye_ppcp_cc-payment-token"]:checked').val()) {
                        return true;
                    }
                }
                if ($(checkoutSelector).is('.paypal_cc_submiting')) {
                    return false;
                } else {
                    $(checkoutSelector).addClass('paypal_cc_submiting');
                    $(document.body).trigger('submit_paypal_cc_form');
                }
                return false;
            }
            return true;
        });
    }
    
    $(checkoutSelector).on('checkout_place_order_angelleye_ppcp_cc', function (event) {
        if (angelleyeOrder.isHostedFieldEligible() === true) {
            event.preventDefault();
            if ($('input[name="wc-angelleye_ppcp_cc-payment-token"]').length) {
                if ('new' !== $('input[name="wc-angelleye_ppcp_cc-payment-token"]:checked').val()) {
                    return true;
                }
            }
            if ($(checkoutSelector).is('.paypal_cc_submiting')) {
                return false;
            } else {
                $(checkoutSelector).addClass('paypal_cc_submiting');
                $(document.body).trigger('submit_paypal_cc_form');
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
            if (angelleyeOrder.isHostedFieldEligible() === true) {
                if ($('#angelleye_ppcp_cc-card-number iframe').length === 0) {
                    $(angelleyeOrder.getCheckoutSelectorCss()).removeClass('HostedFields');
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



    document.addEventListener('DOMContentLoaded', function () {
        var cartBlock = document.querySelector('.wp-block-woocommerce-cart');
        if (cartBlock) {
            setTimeout(function () {
                jQuery(document.body).trigger('ppcp_cart_block_ready');
            }, 1);
        } 
        setTimeout(function () {
                jQuery(document.body).trigger('ppcp_checkout_top_ready');
            }, 1000);
         var checkout_top = document.querySelector('#angelleye_ppcp_checkout_top');
         console.log('checkout_top : ' + checkout_top);
         if (checkout_top) {
            setTimeout(function () {
                jQuery(document.body).trigger('ppcp_checkout_top_ready');
            }, 1000);
        } 
    });
})(jQuery);
