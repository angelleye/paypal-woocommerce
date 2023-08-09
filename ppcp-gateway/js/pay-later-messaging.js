const payLaterMessaging = {
	init: () => {
		if (typeof angelleye_pay_later_messaging === 'undefined') {
			return false;
		}
		if (typeof angelleye_paypal_sdk === 'undefined' ) {
			console.log('Unable to render the PayLaterMessaging: PayPal lib not defined.')
			return;
		}
		// console.log('Init PayLater');
		let amount = angelleye_pay_later_messaging.amount;
		let placementsConfig = angelleye_pay_later_messaging.placements;
		let placementsKeys = Object.keys(placementsConfig);
		for (let i = 0; i < placementsKeys.length; i++) {
			let placement = placementsKeys[i];
			let placementConfig = placementsConfig[placement];
			let styleConfig = payLaterMessaging.getPayLaterStyleConfig(placementConfig);
			payLaterMessaging.render(amount, placement, styleConfig, placementConfig["css_selector"]);
		}

		// used to show hide the message
		payLaterMessaging.handleVariationProduct();

		// handle shortcodes stuff
		jQuery('.angelleye_ppcp_message_shortcode').each(function () {
			let dataKey = jQuery(this).attr('data-key');
			let shortcodeConfig = window[dataKey];
			if (typeof shortcodeConfig !== 'undefined') {
				let placement = shortcodeConfig.placement;
				let styleConfig = payLaterMessaging.getPayLaterStyleConfig(shortcodeConfig);
				payLaterMessaging.render(amount, placement, styleConfig, shortcodeConfig["css_selector"])
			}
		})
	},
	render: (amount, placement, styleConfig, renderDiv) => {
		if (typeof renderDiv === 'undefined') {
			renderDiv = '.angelleye_ppcp_message_cart';
		}
		// console.log('payLaterRender', renderDiv, jQuery(renderDiv).length, jQuery(renderDiv).is(":visible"));
		if (jQuery(renderDiv).length && jQuery(renderDiv).is(":visible")) {
			angelleye_paypal_sdk.Messages({
				amount: amount,
				placement: placement,
				style: styleConfig
			}).render(renderDiv);
		} else {
			// console.log('PayLater: selector ' + renderDiv + ' not defined');
		}
	},
	getPayLaterStyleConfig: (placementConfig) => {
		let styleConfig = {
			"layout": placementConfig["layout_type"],
			"logo": {},
			"text": {},
		};
		if (styleConfig.layout === 'text') {
			styleConfig.logo["type"] = placementConfig["text_layout_logo_type"]
			if (['primary', "alternative"].indexOf(placementConfig["text_layout_logo_type"]) > -1) {
				styleConfig.logo["position"] = placementConfig["text_layout_logo_position"];
			}
			styleConfig.text["size"] = parseInt(placementConfig["text_layout_text_size"]);
			styleConfig.text["color"] = placementConfig["text_layout_text_color"];
		} else {
			styleConfig.color = placementConfig["flex_layout_color"];
			styleConfig.ratio = placementConfig["flex_layout_ratio"];
		}
		return styleConfig;
	},
	handleVariationProduct: () => {
		if (!angelleyeOrder.isProductPage()) return;
		if (jQuery('.variations_form').length) {
			jQuery('.variations_form').on('show_variation', function () {
				jQuery('.angelleye_ppcp_message_product').show();
			}).on('hide_variation', function () {
				jQuery('.angelleye_ppcp_message_product').hide();
			});
		}
	}
};
(function () {
	'use strict';
	// angelleyeOrder.hooks.handleRaceConditionOnWooHooks();
	angelleyeLoadPayPalScript({
		url: angelleye_ppcp_manager.paypal_sdk_url,
		script_attributes: angelleye_ppcp_manager.paypal_sdk_attributes
	}, function() {
		console.log('PayPal lib loaded, initialize pay later messaging.');
		payLaterMessaging.init();

		if (angelleyeOrder.isCartPage() || angelleyeOrder.isCheckoutPage()) {
			jQuery(document.body).on('updated_cart_totals payment_method_selected updated_checkout', function () {
				payLaterMessaging.init();
			});
		}
	});
})(jQuery);
