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

    // At $0 cart the CC gateway uses WooCommerce's legacy plain-text card
    // form (server-side setup-tokens-with-free-trial flow). Don't take over
    // the submit — let WC POST the form so the trait reads raw card data
    // from $_POST and forwards it to /v3/vault/setup-tokens.
    const isZeroCart = () => parseFloat(angelleye_ppcp_manager.cart_total) <= 0;

    if ($(document.body).hasClass('woocommerce-order-pay')) {
        $('#order_review').on('submit', function (event) {
            if (angelleyeOrder.isCardFieldEligible() === true && !isZeroCart()) {
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
        if (angelleyeOrder.isCardFieldEligible() === true && !isZeroCart()) {
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

    // Mini cart: the classic widget is replaced by cart fragments and the
    // Mini-Cart block changes through the Store API, so render its buttons
    // again and refresh the cart totals its wallet sheets open with.
    var refreshMiniCart = function () {
        if ($('#angelleye_ppcp_mini_cart').length && !$('#angelleye_ppcp_mini_cart').children().length) {
            angelleyeOrder.renderSmartButton();
        }
        angelleyeOrder.refreshMiniCartTotals();
    };
    $(document.body).on('added_to_cart removed_from_cart wc_fragments_refreshed wc_fragments_loaded', function () {
        setTimeout(refreshMiniCart, 200);
    });
    ['wc-blocks_added_to_cart', 'wc-blocks_removed_from_cart'].forEach(function (eventName) {
        document.body.addEventListener(eventName, function () {
            setTimeout(refreshMiniCart, 200);
        });
    });
    // Quantity changes inside the drawer fire no event, so also refresh as the
    // buyer reaches for a button.
    $(document.body).on('mouseenter touchstart', '.angelleye_ppcp_mini_cart_buttons', function () {
        angelleyeOrder.refreshMiniCartTotals();
    });
    // Older WooCommerce builds the Mini-Cart drawer in the browser, so its
    // footer never passes through the server filter that adds the buttons.
    if (angelleye_ppcp_manager.mini_cart_buttons_html && window.MutationObserver && document.querySelector('.wp-block-woocommerce-mini-cart')) {
        new MutationObserver(function () {
            var footerActions = document.querySelector('.wc-block-mini-cart__footer-actions');
            if (footerActions && !document.getElementById('angelleye_ppcp_mini_cart')) {
                footerActions.insertAdjacentHTML('beforebegin', angelleye_ppcp_manager.mini_cart_buttons_html);
                refreshMiniCart();
            }
        }).observe(document.body, {childList: true, subtree: true});
    }

    angelleyeOrder.triggerPendingEvents();

    // The Blocks express component mounted before this script ran, so its render
    // request could not reach any listener. Serve it now that the SDK is loaded.
    if (angelleyeOrder.isBlockExpressRenderPending) {
        angelleyeOrder.isBlockExpressRenderPending = false;
        angelleyeOrder.renderPaymentButtons();
    }
    angelleyeOrder.isSdkReady = true;
    angelleyeOrder.sdkReadyCallbacks.splice(0).forEach((callback) => callback());

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
            let appleResolve, appleReject;
            let applePromise = new Promise((resolve, reject) => {
                appleResolve = resolve;
                appleReject = reject;
            });
            scriptsToLoad.push({
                // Native ApplePaySession (Safari, iOS) keeps the v1 build, which older iOS can parse.
                url: typeof window.ApplePaySession !== 'undefined' ? angelleye_ppcp_manager.apple_sdk_native_url : angelleye_ppcp_manager.apple_sdk_url,
                promise: applePromise,
                onLoaded: () => { console.log('apple sdk loaded'); appleResolve(); },
                onError: () => { console.log('apple sdk failed to load'); appleReject(); }
            });
        }

        if (angelleyeOrder.isGooglePayEnabled()) {
            let googleResolve, googleReject;
            let googlePromise = new Promise((resolve, reject) => {
                googleResolve = resolve;
                googleReject = reject;
            });
            scriptsToLoad.push({
                url: angelleye_ppcp_manager.google_sdk_url,
                promise: googlePromise,
                onLoaded: () => { console.log('google sdk loaded'); googleResolve(); },
                onError: () => { console.log('google sdk failed to load'); googleReject(); }
            });
        }

        if (scriptsToLoad.length === 0) {
            initSmartButtons();
        } else {
            let allPromises = [];
            for (let i = 0; i < scriptsToLoad.length; i++) {
                allPromises.push(scriptsToLoad[i].promise);
            }
            Promise.all(allPromises).then((success) => {
                console.log('all libs loaded');
                initSmartButtons();
            }, (error) => {
                console.log('An error occurred in loading the SDKs.');
            });
            for (let i = 0; i < scriptsToLoad.length; i++) {
                angelleyeLoadPayPalScript(scriptsToLoad[i], scriptsToLoad[i].onLoaded, scriptsToLoad[i].onError);
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

// Report only errors thrown by our own scripts; errors from other plugins and
// themes are not ours to fix and can repeat thousands of times per page. A
// listener rather than window.onerror so handlers set by other scripts keep
// working.
(function () {
	// Too common to tell our code apart when a browser shows only the bare
	// function name in the stack (Firefox, Safari).
	const genericNames = ['constructor', 'render', 'renderButton', 'init', 'createOrder', 'approveOrder', 'onCancel',
		'showError', 'removeError', 'isSale', 'getCartDetails', 'setCartDetails', 'processPayment', 'handleClickEvent',
		'isCheckoutPage', 'isProductPage', 'isCartPage', 'getUrlObject', 'setQueryParam', 'getQueryParams',
		'removeQueryParam', 'removeAllParams'];
	let ourNames = null;

	// Read from the loaded code on the first error, so new methods are covered
	// without keeping a list. Class and method names survive minification.
	const getOurNames = function () {
		if (ourNames) {
			return ourNames;
		}
		ourNames = {classes: [], objectMethods: [], bareNames: ['initSmartButtons', 'angelleyeLoadPayPalScript', 'canShowPlaceOrderBtn', 'showHidePlaceOrderBtn']};
		const objects = [
			typeof angelleyeOrder !== 'undefined' ? angelleyeOrder : null,
			typeof pfwUrlHelper !== 'undefined' ? pfwUrlHelper : null
		];
		objects.forEach(function (obj) {
			if (obj) {
				Object.keys(obj).forEach(function (key) {
					if (typeof obj[key] === 'function') {
						ourNames.objectMethods.push(key);
					}
				});
			}
		});
		const classes = [
			typeof ApplePayCheckoutButton !== 'undefined' ? ApplePayCheckoutButton : null,
			typeof GooglePayCheckoutButton !== 'undefined' ? GooglePayCheckoutButton : null
		];
		classes.forEach(function (cls) {
			if (cls) {
				ourNames.classes.push(cls.name);
				ourNames.bareNames.push(...Object.getOwnPropertyNames(cls.prototype));
			}
		});
		ourNames.bareNames = ourNames.bareNames.concat(ourNames.objectMethods, ourNames.classes).filter(function (name) {
			return genericNames.indexOf(name) === -1;
		});
		return ourNames;
	};

	// For scripts combined into one file by an optimization plugin, where the
	// file name no longer tells whose code threw. Chrome names frames
	// "Object.method" / "ClassName.method", Firefox and Safari "method@";
	// Firefox names anonymous inner functions "method/<", so those count too.
	const isOurStack = function (stack) {
		const names = getOurNames();
		return stack.split('\n').some(function (frame) {
			const match = frame.match(/^\s*at (?:async )?(?:new )?([^\s(]+)/) || frame.match(/^(?:async\*)?([^@\s]+)@/);
			if (!match) {
				return false;
			}
			const name = match[1].split('/')[0];
			const dot = name.lastIndexOf('.');
			if (dot !== -1) {
				const owner = name.slice(0, dot), method = name.slice(dot + 1);
				return names.classes.indexOf(owner) !== -1 || (owner === 'Object' && names.objectMethods.indexOf(method) !== -1);
			}
			return names.bareNames.indexOf(name) !== -1;
		});
	};

	const isOurError = function (event) {
		if (typeof angelleye_ppcp_manager === 'undefined' || !angelleye_ppcp_manager.asset_url) {
			return false;
		}
		// Matched on the path, so assets served from a CDN host still count.
		try {
			const assetPath = new URL(angelleye_ppcp_manager.asset_url).pathname;
			if (typeof event.filename === 'string' && event.filename.indexOf(assetPath) !== -1) {
				return true;
			}
		} catch (e) {
		}
		return !!(event.error && typeof event.error.stack === 'string' && isOurStack(event.error.stack));
	};

	window.addEventListener('error', function (event) {
		if (!isOurError(event)) {
			return;
		}
		angelleyeJsErrorLogger.logJsError({
			'msg': event.message,
			'source': event.filename,
			'line': event.lineno,
		});
	});
})();
