const angelleyeOrder = {
	isCheckoutPage: () => {
		return 'checkout' === angelleye_ppcp_manager.page;
	},
	isProductPage: () => {
		return 'product' === angelleye_ppcp_manager.page;
	},
	isCartPage: () => {
		return 'cart' === angelleye_ppcp_manager.page;
	},
	isSale: () => {
		return 'capture' === angelleye_ppcp_manager.paymentaction;
	},
	isOrderCompletePage: () => {
		const url = new URL(window.location.href);
		//  && url.searchParams.has('paypal_payer_id')
		return url.searchParams.has('paypal_order_id');
	},
	getSelectedPaymentMethod: () => {
		return jQuery('input[name="payment_method"]:checked').val();
	},
	isApplePayPaymentMethodSelected: () => {
		return angelleyeOrder.getSelectedPaymentMethod() === 'angelleye_ppcp_apple_pay';
	},
	isPpcpPaymentMethodSelected: () => {
		return angelleyeOrder.getSelectedPaymentMethod() === 'angelleye_ppcp';
	},
	isAngelleyePpcpPaymentMethodSelected: () => {
		let paymentMethod = angelleyeOrder.getSelectedPaymentMethod();
		return paymentMethod === 'angelleye_ppcp' || paymentMethod === 'angelleye_ppcp_apple_pay';
	},
	isAngelleyePaymentMethodSelected: () => {
		let paymentMethod = angelleyeOrder.getSelectedPaymentMethod();
		return paymentMethod === 'paypal_express' || paymentMethod === 'angelleye_ppcp' || paymentMethod === 'angelleye_ppcp_apple_pay';
	},
	isApplePayEnabled: () => {
		return angelleye_ppcp_manager.apple_sdk_url !== "";
	},
	getCheckoutSelectorCss: () => {
		let checkoutSelector = '.woocommerce';
		if (angelleye_ppcp_manager.page === 'checkout') {
			if (angelleye_ppcp_manager.is_pay_page === 'yes') {
				checkoutSelector = 'form#order_review';
			} else {
				checkoutSelector = 'form.checkout';
			}
		}
		return checkoutSelector;
	},
	scrollToWooCommerceNoticesSection: () => {
		let scrollElement = jQuery('.woocommerce-NoticeGroup-updateOrderReview, .woocommerce-NoticeGroup-checkout');
		if (!scrollElement.length) {
			scrollElement = jQuery('form.checkout');
		}
		if (!scrollElement.length) {
			scrollElement = jQuery('form#order_review');
		}
		if (scrollElement.length) {
			jQuery('html, body').animate({
				scrollTop: (scrollElement.offset().top - 100)
			}, 1000);
		}
	},
	createSmartButtonOrder: ({angelleye_ppcp_button_selector}) => {
		return angelleyeOrder.createOrder({angelleye_ppcp_button_selector}).then((data) => {
			return data.orderID;
		})
	},
	createOrder: ({angelleye_ppcp_button_selector, billingDetails, shippingDetails, apiUrl, callback}) => {
		if (typeof apiUrl == 'undefined') {
			apiUrl = angelleye_ppcp_manager.create_order_url;
		}
		jQuery('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
		let formData;
		let is_from_checkout = angelleyeOrder.isCheckoutPage();
		let is_from_product = angelleyeOrder.isProductPage();
		let is_sale = angelleyeOrder.isSale();
		let billingField = null;
		let shippingField = null;
		if (billingDetails) {
			billingField = jQuery('<input>', {
				type: 'hidden',
				name: 'billing_address_source',
				value: JSON.stringify(billingDetails)
			});
		}
		if (shippingDetails) {
			shippingField = jQuery('<input>', {
				type: 'hidden',
				name: 'shipping_address_source',
				value: JSON.stringify(shippingDetails)
			});
		}
		if (is_from_checkout) {
			if (angelleye_ppcp_button_selector === '#angelleye_ppcp_checkout_top') {
				formData = '';
			} else {
				formData = jQuery(angelleye_ppcp_button_selector).closest('form').serialize();
			}
		} else if (is_from_product) {
			let add_to_cart = jQuery("[name='add-to-cart']").val();
			jQuery('<input>', {
				type: 'hidden',
				name: 'angelleye_ppcp-add-to-cart',
				value: add_to_cart
			}).appendTo('form.cart');

			if (billingField) {
				jQuery('form.cart').find('input[name=billing_address_source]').remove();
				billingField.appendTo('form.cart');
			}
			if (shippingField) {
				jQuery('form.cart').find('input[name=shipping_address_source]').remove();
				shippingField.appendTo('form.cart');
			}
			formData = jQuery('form.cart').serialize();
		} else {
			if (billingField) {
				jQuery('form.cart').find('input[name=billing_address_source]').remove();
				billingField.appendTo('form.woocommerce-cart-form');
			}
			if (shippingField) {
				jQuery('form.cart').find('input[name=shipping_address_source]').remove();
				shippingField.appendTo('form.woocommerce-cart-form');
			}
			formData = jQuery('form.woocommerce-cart-form').serialize();
		}
		return fetch(apiUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded'
			},
			body: formData
		}).then(function (res) {
			console.log('createOrder response', {res, apiUrl, redirected:res.redirected, url: res.url, status: res.status});
			if (res.redirected) {
				window.location.href = res.url;
			} else {
				return res.json();
			}
		}).then(function (data) {
			if (typeof callback === 'function') {
				callback(data);
				return;
			}
			if (typeof data.success !== 'undefined') {
				let messages = data.data.messages ? data.data.messages : data.data;
				if ('string' !== typeof messages) {
					messages = messages.map(function (message) {
						return '<li>' + message + '</li>';
					}).join('');
					messages = '<div>Unable to create the order due to below errors.</div>' + messages;
				}
				throw new Error(messages);
			} else {
				return data;
			}
		});
	},
	approveOrder: ({orderID, payerID}) => {
		if (angelleyeOrder.isCheckoutPage()) {
			jQuery.post(angelleye_ppcp_manager.cc_capture + "&paypal_order_id=" + orderID + "&woocommerce-process-checkout-nonce=" + angelleye_ppcp_manager.woocommerce_process_checkout, function (data) {
				window.location.href = data.data.redirect;
			});
		} else {
			if (angelleye_ppcp_manager.is_skip_final_review === 'yes') {
				window.location.href = angelleye_ppcp_manager.direct_capture + '&paypal_order_id=' + orderID + '&paypal_payer_id=' + payerID + '&from=' + angelleye_ppcp_manager.page;
			} else {
				window.location.href = angelleye_ppcp_manager.checkout_url + '&paypal_order_id=' + orderID + (payerID ? '&paypal_payer_id=' + payerID : '') + '&from=' + angelleye_ppcp_manager.page;
			}
		}
	},
	shippingAddressUpdate: (shippingDetails) => {
		return angelleyeOrder.createOrder({apiUrl: angelleye_ppcp_manager.shipping_update_url, shippingDetails});
	},
	onCancel: () => {
		jQuery(document.body).trigger('angelleye_paypal_oncancel');
		if (angelleyeOrder.isCheckoutPage() === false) {
			angelleyeOrder.showProcessingSpinner();
			window.location.reload();
		}
	},
	prepareWooErrorMessage: (message) => {
		return '<div class="woocommerce-error">' + message + '</div>'
	},
	showError: (error_message) => {
		error_message = angelleyeOrder.prepareWooErrorMessage(error_message);
		let checkoutSelector = angelleyeOrder.getCheckoutSelectorCss();
		jQuery(checkoutSelector).prepend('<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + error_message + '</div>');
		jQuery(checkoutSelector).removeClass('processing').unblock();
		jQuery(checkoutSelector).find('.input-text, select, input:checkbox').trigger('validate').trigger('blur');
		angelleyeOrder.scrollToWooCommerceNoticesSection();
	},
	showProcessingSpinner: (containerSelector) => {
		if (typeof containerSelector === 'undefined') {
			containerSelector = '.woocommerce';
		}
		if (jQuery(containerSelector).length) {
			jQuery(containerSelector).block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}});
		}
	},
	hideProcessingSpinner: (containerSelector) => {
		if (typeof containerSelector === 'undefined') {
			containerSelector = '.woocommerce';
		}
		if (jQuery(containerSelector).length) {
			jQuery(containerSelector).unblock();
		}
	},
	handleCreateOrderError: (error) => {
		console.log(error);
		angelleyeOrder.hideProcessingSpinner();
		jQuery(document.body).trigger('angelleye_paypal_onerror');
		let errorMessage = error.message;
		if ((errorMessage.toLowerCase()).indexOf('expected an order id to be passed') > -1) {
			if ((errorMessage.toLowerCase()).indexOf('required fields') < 0) {
				errorMessage = 'Unable to create the order, please contact the support.';
			}
		}
		if (errorMessage !== '') {
			angelleyeOrder.showError(errorMessage);
		}
		angelleyeOrder.scrollToWooCommerceNoticesSection();
		if (angelleyeOrder.isCheckoutPage() === false) {
			//  window.location.href = window.location.href;
		}
	},
	isHostedFieldEligible: () => {
		if (angelleyeOrder.isCheckoutPage()) {
			if (angelleye_ppcp_manager.advanced_card_payments === 'yes') {
				return typeof angelleye_paypal_sdk === 'undefined' || typeof angelleye_paypal_sdk.HostedFields == 'undefined'
					? false : (angelleye_paypal_sdk.HostedFields.isEligible() === true);
			}
		}
		return false;
	},
	showPpcpPaymentMethods: () => {
		if (angelleyeOrder.isApplePayPaymentMethodSelected()) {
			jQuery('#angelleye_ppcp_checkout').hide();
			jQuery('#angelleye_ppcp_checkout_apple_pay').show();
		} else {
			jQuery('#angelleye_ppcp_checkout_apple_pay').hide();
			jQuery('#angelleye_ppcp_checkout').show();
		}
	},
	hidePpcpPaymentMethods: () => {
		jQuery('#angelleye_ppcp_checkout, #angelleye_ppcp_checkout_apple_pay').hide();
	},
	hideShowPlaceOrderButton: () => {
		let selectedPaymentMethod = angelleyeOrder.getSelectedPaymentMethod();
		console.log('hideShowPlaceOrderButton', selectedPaymentMethod)
		let isAePpcpMethodSelected = angelleyeOrder.isAngelleyePpcpPaymentMethodSelected();
		if (isAePpcpMethodSelected === true) {
			jQuery('.wcf-pre-checkout-offer-action').val('');
		}
		if (angelleyeOrder.isHostedFieldEligible() === false) {
			jQuery('.payment_method_angelleye_ppcp_cc').hide();
		}
		if (isAePpcpMethodSelected === true && angelleye_ppcp_manager.is_checkout_disable_smart_button === 'no') {
			showHidePlaceOrderBtn();
			angelleyeOrder.showPpcpPaymentMethods();
		} else {
			angelleyeOrder.hidePpcpPaymentMethods();
			showHidePlaceOrderBtn();
		}
	},
	createHiddenInputField: ({fieldId, fieldName, fieldValue, fieldType, appendToSelector}) => {
		if (jQuery('#' + fieldId).length > 0) {
			jQuery('#' + fieldId).remove();
		}
		jQuery('<input>', {
			type: typeof fieldType == 'undefined' ? 'hidden' : fieldType,
			id: fieldId,
			name: fieldName,
			value: fieldValue
		}).appendTo(appendToSelector)
	},
	getWooFormSelector: () => {
		let payment_method_element_selector = '';
		if (angelleyeOrder.isProductPage()) {
			payment_method_element_selector = 'form.cart';
		} else if (angelleyeOrder.isCartPage()) {
			payment_method_element_selector = 'form.woocommerce-cart-form';
		} else if (angelleyeOrder.isCheckoutPage()) {
			payment_method_element_selector = angelleyeOrder.getCheckoutSelectorCss();
		}
		return payment_method_element_selector;
	},
	setPaymentMethodSelector: (paymentMethod) => {
		let payment_method_element_selector = angelleyeOrder.getWooFormSelector();

		angelleyeOrder.createHiddenInputField({
			fieldId: 'angelleye_ppcp_payment_method_title',
			fieldName: 'angelleye_ppcp_payment_method_title',
			fieldValue: paymentMethod,
			appendToSelector: payment_method_element_selector
		});
	},
	renderSmartButton: () => {
		console.log('render smart buttons');
		jQuery.each(angelleye_ppcp_manager.button_selector, function (key, angelleye_ppcp_button_selector) {
			if (!jQuery(angelleye_ppcp_button_selector).length || jQuery(angelleye_ppcp_button_selector).children().length) {
				return;
			}
			if (typeof angelleye_paypal_sdk === 'undefined') {
				return;
			}
			let angelleye_ppcp_style = {
				layout: angelleye_ppcp_manager.style_layout,
				color: angelleye_ppcp_manager.style_color,
				shape: angelleye_ppcp_manager.style_shape,
				label: angelleye_ppcp_manager.style_label
			};
			if (angelleye_ppcp_manager.style_height !== '') {
				angelleye_ppcp_style['height'] = parseInt(angelleye_ppcp_manager.style_height);
			}
			if (angelleye_ppcp_manager.style_layout !== 'vertical') {
				angelleye_ppcp_style['tagline'] = (angelleye_ppcp_manager.style_tagline === 'yes') ? true : false;
			}

			angelleye_paypal_sdk.Buttons({
				style: angelleye_ppcp_style,
				createOrder: function (data, actions) {
					return angelleyeOrder.createSmartButtonOrder({
						angelleye_ppcp_button_selector
					})
				},
				onApprove: function (data, actions) {
					angelleyeOrder.showProcessingSpinner();
					angelleyeOrder.approveOrder(data);
				},
				onCancel: function (data, actions) {
					angelleyeOrder.hideProcessingSpinner();
					angelleyeOrder.onCancel();
				}, onClick: function (data, actions) {
					angelleyeOrder.setPaymentMethodSelector(data.fundingSource);
				},
				onError: function (err) {
					angelleyeOrder.handleCreateOrderError(err);
				}
			}).render(angelleye_ppcp_button_selector);
			if (angelleyeOrder.isApplePayEnabled()) {
				(new ApplePayCheckoutButton()).render(angelleye_ppcp_button_selector);
			}
		});
	},
	renderHostedButtons: () => {
		let checkoutSelector = angelleyeOrder.getCheckoutSelectorCss();
		if (jQuery(checkoutSelector).is('.HostedFields')) {
			return false;
		}
		if (typeof angelleye_paypal_sdk === 'undefined') {
			return;
		}
		jQuery(checkoutSelector).addClass('HostedFields');
		angelleye_paypal_sdk.HostedFields.render({
			createOrder: function () {
				jQuery('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
				if (jQuery(checkoutSelector).is('.createOrder') === false) {
					jQuery(checkoutSelector).addClass('createOrder');
					let data;
					if (angelleyeOrder.isCheckoutPage()) {
						data = jQuery(checkoutSelector).serialize();
					}
					return fetch(angelleye_ppcp_manager.create_order_url, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded'
						},
						body: data
					}).then(function (res) {
						return res.json();
					}).then(function (data) {
						if (typeof data.success !== 'undefined') {
							let messages = data.data.messages ? data.data.messages : data.data;
							if ('string' === typeof messages) {
								angelleyeOrder.showError(messages);
							} else {
								let messageItems = messages.map(function (message) {
									return '<li>' + message + '</li>';
								}).join('');
								angelleyeOrder.showError('<ul>' + messageItems + '</ul>');
							}
							return '';
						} else {
							return data.orderID;
						}
					});
				}
			},
			onCancel: function (data, actions) {
				actions.redirect(angelleye_ppcp_manager.cancel_url);
			},
			onError: function (err) {
				console.log(err);
			},
			styles: {
				'input': {
					'font-size': '1.3em'
				}
			},
			fields: {
				number: {
					selector: '#angelleye_ppcp_cc-card-number',
					placeholder: '•••• •••• •••• ••••',
					addClass: 'input-text wc-credit-card-form-card-number'
				},
				cvv: {
					selector: '#angelleye_ppcp_cc-card-cvc',
					placeholder: 'CVC'
				},
				expirationDate: {
					selector: '#angelleye_ppcp_cc-card-expiry',
					placeholder: 'MM / YY'
				}
			}
		}).then(function (hf) {
			hf.on('cardTypeChange', function (event) {
				if (event.cards.length > 0) {
					let cardname = event.cards[0].type.replace("master-card", "mastercard").replace("american-express", "amex").replace("diners-club", "dinersclub").replace("-", "");
					if (jQuery.inArray(cardname, angelleye_ppcp_manager.disable_cards) !== -1) {
						jQuery('#angelleye_ppcp_cc-card-number').addClass('ppcp-invalid-cart');
						jQuery('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
						angelleyeOrder.showError(angelleye_ppcp_manager.card_not_supported);
					} else {
						jQuery('#angelleye_ppcp_cc-card-number').removeClass().addClass(cardname);
						jQuery('#angelleye_ppcp_cc-card-number').addClass("input-text wc-credit-card-form-card-number hosted-field-braintree braintree-hosted-fields-valid");
					}
					let payment_method_element_selector;
					if (angelleye_ppcp_manager.page === 'product') {
						payment_method_element_selector = 'form.cart';
					} else if (angelleye_ppcp_manager.page === 'cart') {
						payment_method_element_selector = 'form.woocommerce-cart-form';
					} else if (angelleye_ppcp_manager.page === 'checkout') {
						if (angelleye_ppcp_manager.is_pay_page === 'yes') {
							payment_method_element_selector = '#order_review';
						} else {
							payment_method_element_selector = checkoutSelector;
						}
					}
					if (jQuery('#angelleye_ppcp_cc_cc_payment_method_title').length > 0) {
						jQuery('#angelleye_ppcp_cc_cc_payment_method_title').empty();
					}
					jQuery('<input>', {
						type: 'hidden',
						id: 'angelleye_ppcp_cc_payment_method_title',
						name: 'angelleye_ppcp_cc_payment_method_title',
						value: angelleye_ppcp_manager.advanced_card_payments_title
					}).appendTo(payment_method_element_selector);
				}
			});

			jQuery(document.body).on('submit_paypal_cc_form', function (event) {
				event.preventDefault();
				let state = hf.getState();
				if (typeof state.cards !== 'undefined') {
					if (state.fields.number.isValid) {
						let cardname = state.cards[0].type;
						if (typeof cardname !== 'undefined' && cardname !== null || cardname.length !== 0) {
							if (jQuery.inArray(cardname, angelleye_ppcp_manager.disable_cards) !== -1) {
								jQuery(checkoutSelector).removeClass('processing paypal_cc_submiting HostedFields createOrder').unblock();
								jQuery('#angelleye_ppcp_cc-card-number').addClass('ppcp-invalid-cart');
								jQuery('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
								angelleyeOrder.showError(angelleye_ppcp_manager.card_not_supported);
								return;
							}
						}
					}
				} else {
					jQuery(checkoutSelector).removeClass('processing paypal_cc_submiting HostedFields createOrder').unblock();
					jQuery('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
					angelleyeOrder.showError(angelleye_ppcp_manager.fields_not_valid);
					return;
				}
				let formValid = Object.keys(state.fields).every(function (key) {
					return state.fields[key].isValid;
				});
				if (formValid === false) {
					jQuery('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
					jQuery(checkoutSelector).removeClass('processing paypal_cc_submiting HostedFields createOrder').unblock();
					angelleyeOrder.showError(angelleye_ppcp_manager.fields_not_valid);
					return;
				}
				let contingencies = [];
				contingencies = [angelleye_ppcp_manager.three_d_secure_contingency];
				jQuery(checkoutSelector).addClass('processing').block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});
				angelleyeOrder.scrollToWooCommerceNoticesSection();
				let firstName;
				let lastName;
				if (angelleye_ppcp_manager.is_pay_page === 'yes') {
					firstName = angelleye_ppcp_manager.first_name;
					lastName = angelleye_ppcp_manager.last_name;
				} else {
					firstName = document.getElementById('billing_first_name') ? document.getElementById('billing_first_name').value : '';
					lastName = document.getElementById('billing_last_name') ? document.getElementById('billing_last_name').value : '';
				}
				hf.submit({
					contingencies: contingencies,
					cardholderName: firstName + ' ' + lastName
				}).then(
					function (payload) {
						if (payload.orderId) {
							jQuery.post(angelleye_ppcp_manager.cc_capture + "&paypal_order_id=" + payload.orderId + "&woocommerce-process-checkout-nonce=" + angelleye_ppcp_manager.woocommerce_process_checkout + "&is_pay_page=" + angelleye_ppcp_manager.is_pay_page, function (data) {
								window.location.href = data.data.redirect;
							});
						}
					}, function (error) {
						jQuery(checkoutSelector).removeClass('processing paypal_cc_submiting HostedFields createOrder').unblock();
						let error_message = '';
						if (Array.isArray(error.details) && error.details[0]['description']) {
							error_message = error.details[0]['description'];
						} else if (error.message) {
							error_message = error.message;
						}
						if (Array.isArray(error.details) && error.details[0]['issue'] === 'INVALID_RESOURCE_ID') {
							error_message = '';
						}

						if (error_message !== '') {
							angelleyeOrder.showError(error_message);
						}
					}
				);
			});
		}).catch(function (err) {
			console.log('error: ', JSON.stringify(err));
		});
	},
	queuedEvents: {},
	addEventsForCallback: (eventType, event, data) => {
		angelleyeOrder.queuedEvents[eventType] = {event, data};
	},
	dequeueEvent: (eventType) => {
		if (eventType in angelleyeOrder.queuedEvents) {
			delete angelleyeOrder.queuedEvents[eventType];
		}
	},
	isPendingEventTriggering: false,
	triggerPendingEvents: () => {
		angelleyeOrder.isPendingEventTriggering = true;
		for (let event in angelleyeOrder.queuedEvents) {
			if (angelleyeOrder.queuedEvents[event].data) {
				jQuery(document.body).trigger(event, [angelleyeOrder.queuedEvents[event].data]);
			} else {
				jQuery(document.body).trigger(event);
			}
		}
	},
	hooks: {
		onPaymentMethodChange: () => {
			jQuery(document.body).on('updated_cart_totals payment_method_selected updated_checkout', function (event) {
				angelleyeOrder.dequeueEvent(event.type);
				angelleyeOrder.hideShowPlaceOrderButton();
				setTimeout(function () {
					angelleyeOrder.renderSmartButton();
					if (angelleyeOrder.isHostedFieldEligible() === true) {
						jQuery('#angelleye_ppcp_cc-card-number iframe').length === 0 ? jQuery(angelleyeOrder.getCheckoutSelectorCss()).removeClass('HostedFields') : null;
						jQuery('.checkout_cc_separator').show();
						jQuery('#wc-angelleye_ppcp-cc-form').show();
						angelleyeOrder.renderHostedButtons();
					}
				}, 300);
			});
			if (angelleyeOrder.isCartPage()) {
				jQuery(document.body).on('updated_cart_totals', async function (event) {
					angelleyeOrder.dequeueEvent(event.type);
					if (angelleyeOrder.isApplePayEnabled()) {
						// block the apple pay button UI to make sure nobody can click it while its updating.
						angelleyeOrder.showProcessingSpinner('#angelleye_ppcp_cart_apple_pay');
						// trigger an ajax call to update the total amount, in case there is no shipping required object
						let response = await angelleyeOrder.shippingAddressUpdate({});
						angelleyeOrder.hideProcessingSpinner('#angelleye_ppcp_cart_apple_pay');
						if (typeof response.totalAmount !== 'undefined') {
							// successful response
							window.angelleye_cart_totals = response;
						} else {
							// in case of unsuccessful response, refresh the page.
							window.location.reload();
						}
					}
				});
			}
		},
		onCartValueUpdate: () => {
			jQuery(document.body).on('updated_checkout', function (event, data) {
				angelleyeOrder.dequeueEvent(event.type);
				if (typeof data !== 'undefined' && typeof data["fragments"] !== 'undefined' && typeof data["fragments"]["angelleye_payments_data"] !== "undefined") {
					window.angelleye_cart_totals = JSON.parse(data["fragments"]["angelleye_payments_data"]);
				}
				console.log('cart updated',data, window.angelleye_cart_totals);
			});
		},
		handleRaceConditionOnWooHooks: () => {
			jQuery(document.body).on('updated_cart_totals payment_method_selected updated_checkout', function (event, data) {
				if (!angelleyeOrder.isPendingEventTriggering) {
					angelleyeOrder.addEventsForCallback(event.type, event, data);
				}
			});
		}
	}
}
