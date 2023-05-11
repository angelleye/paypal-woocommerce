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
	angelleyeOrder.hooks.onPaymentMethodChange();
	angelleyeOrder.hooks.onCartValueUpdate();

	$(document.body).on('removed_coupon_in_checkout', function () {
		window.location.href = window.location.href;
	});
}
(function () {
	'use strict';
	angelleyeLoadPayPalScript({url: angelleye_ppcp_manager.paypal_sdk_url,
		script_attributes: {
			'data-namespace': 'angelleye_paypal_sdk'
		}}, function() {
		if (angelleyeOrder.isApplePayEnabled()) {
			angelleyeLoadPayPalScript({
				url: angelleye_ppcp_manager.apple_sdk_url
			}, function () {
				initSmartButtons();
			});
		} else {
			initSmartButtons();
		}
	})
})(jQuery);
