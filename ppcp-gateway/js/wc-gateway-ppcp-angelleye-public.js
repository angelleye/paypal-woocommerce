function initSmartButtons() {
	console.log('initSmartButtons');
	let $ = jQuery;
	if (typeof angelleye_ppcp_manager === 'undefined') {
		return false;
	}
	let checkoutSelector = angelleyeOrder.getCheckoutSelectorCss();

	if ($('.variations_form').length) {
		$('.variations_form').on('show_variation', function () {
			$('#angelleye_ppcp_product').show();
		}).on('hide_variation', function () {
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
	angelleyeOrder.hooks.onPaymentMethodChange();
	angelleyeOrder.hooks.onCartValueUpdate();

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
	window.angelleyeLoadAsyncLibs = (callback) => {
		angelleyeLoadPayPalScript({
			url: angelleye_ppcp_manager.paypal_sdk_url,
			script_attributes: angelleye_ppcp_manager.paypal_sdk_attributes
		}, function () {
			if (angelleyeOrder.isApplePayEnabled()) {
				angelleyeLoadPayPalScript({
					url: angelleye_ppcp_manager.apple_sdk_url
				}, function () {
					console.log('apple pay lib loaded');
					callback();
				});
			} else {
				callback();
			}
		});
	}
	window.angelleyeLoadAsyncLibs(initSmartButtons);

	jQuery(document.body).on('angelleye_paypal_oncancel', function (event) {
		event.preventDefault();
		let is_from_product = angelleyeOrder.isProductPage();
		if (is_from_product) {
			fetch(angelleye_ppcp_manager.update_cart_oncancel, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded'
				},
				body: jQuery('form.cart').serialize()
			}).then(function (res) {
				return res.json();
			}).then(function (data) {
				window.location.reload();
			});
		} else {
			window.location.reload();
		}
	});
})(jQuery);
