function initSmartButtons() {
	console.log('initSmartButtons');
	let $ = jQuery;
	if (typeof angelleye_ppcp_manager === 'undefined') {
		return false;
	}
	let checkoutSelector = angelleyeOrder.getCheckoutSelectorCss();

	if ($('.variations_form').length) {
		let div_to_hide_show = '#angelleye_ppcp_product, #angelleye_ppcp_product_google_pay, #angelleye_ppcp_product_apple_pay'
		$('.variations_form').on('show_variation', function () {
			$(div_to_hide_show).show();
		}).on('hide_variation', function () {
			$(div_to_hide_show).hide();
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

	// Render smart button on non checkout pages
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

	// Hook the function to run on totals, cart or checkout updates
	angelleyeOrder.updateCartTotalsInEnvironment();
	angelleyeOrder.hooks.onPaymentCancellation();
	angelleyeOrder.hooks.handleWooEvents();

	// handle the scenario where the cart updated or checkout_updated hook is already triggered before above hooks are bound
	angelleyeOrder.triggerPendingEvents();

	$(document.body).on('removed_coupon_in_checkout', function () {
		window.location.href = window.location.href;
	});
}
(function () {
	'use strict';
	// queue the woocommerce hook events immediately to trigger those later in case sdk load takes time
	angelleyeOrder.hooks.handleRaceConditionOnWooHooks();

	const paypalSdkLoadCallback = () => {
		console.log('PayPal lib loaded, initialize buttons.')
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

		if (scriptsToLoad.length === 0){
			initSmartButtons();
		} else {
			let allPromises = []
			for (let i = 0; i < scriptsToLoad.length; i++) {
				allPromises.push(scriptsToLoad[i].callback);
			}
			Promise.all(allPromises).then((success) => {
				console.log('all libs loaded');
				initSmartButtons();
			}, (error) => {
				console.log('An error occurred in loading the SDKs.')
			})
			for (let i = 0; i < scriptsToLoad.length; i++) {
				angelleyeLoadPayPalScript(scriptsToLoad[i], scriptsToLoad[i].callback);
			}
		}
	};

	// This method will be invoked by other functions to refresh the PayPal SDK with the different parameters
	// e.g. currency, intent, etc
	window.angelleyeLoadAsyncLibs = (callback, errorCallback) => {
		angelleyeLoadPayPalScript({
			url: angelleye_ppcp_manager.paypal_sdk_url,
			script_attributes: angelleye_ppcp_manager.paypal_sdk_attributes
		}, callback, errorCallback);
	}

	window.angelleyeLoadAsyncLibs(paypalSdkLoadCallback);
})(jQuery);
