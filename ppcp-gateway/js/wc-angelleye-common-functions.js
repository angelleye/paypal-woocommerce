const angelleyeOrder = {
    productAddToCart: true,
    lastApiResponse: null,
    ppcp_address: [],
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
    isOrderPayPage: () => {
        const url = new URL(window.location.href);
        return url.searchParams.has('pay_for_order');
    },
    isOrderCompletePage: () => {
        const url = new URL(window.location.href);
        //  && url.searchParams.has('paypal_payer_id')
        return url.searchParams.has('paypal_order_id');
    },
    getSelectedPaymentMethod: () => {
        if (jQuery('input[name="payment_method"]').length) {
            return jQuery('input[name="payment_method"]:checked').val();
        } else if(jQuery('input[name="radio-control-wc-payment-method-options"]').length) {
            return jQuery('input[name="radio-control-wc-payment-method-options"]:checked').val();
        }
    },
    isApplePayPaymentMethodSelected: () => {
        return angelleyeOrder.getSelectedPaymentMethod() === 'angelleye_ppcp_apple_pay';
    },
    isPpcpPaymentMethodSelected: () => {
        return angelleyeOrder.getSelectedPaymentMethod() === 'angelleye_ppcp';
    },
    isCCPaymentMethodSelected: () => {
        return angelleyeOrder.getSelectedPaymentMethod() === 'angelleye_ppcp_cc';
    },
    isGooglePayPaymentMethodSelected: () => {
        return angelleyeOrder.getSelectedPaymentMethod() === 'angelleye_ppcp_google_pay';
    },
    isAngelleyePpcpPaymentMethodSelected: () => {
        let paymentMethod = angelleyeOrder.getSelectedPaymentMethod();
        return paymentMethod === 'angelleye_ppcp' || paymentMethod === 'angelleye_ppcp_apple_pay' || paymentMethod === 'angelleye_ppcp_google_pay';
    },
    isAngelleyePpcpAdditionalPaymentMethodSelected: () => {
        let paymentMethod = angelleyeOrder.getSelectedPaymentMethod();
        return paymentMethod === 'angelleye_ppcp_apple_pay' || paymentMethod === 'angelleye_ppcp_google_pay';
    },
    isAngelleyePaymentMethodSelected: () => {
        let paymentMethod = angelleyeOrder.getSelectedPaymentMethod();
        return paymentMethod === 'paypal_express' || paymentMethod === 'angelleye_ppcp' || paymentMethod === 'angelleye_ppcp_apple_pay' || paymentMethod === 'angelleye_ppcp_google_pay';
    },
    isSavedPaymentMethodSelected: () => {
        let paymentMethod = angelleyeOrder.getSelectedPaymentMethod();
        let paymentToken = jQuery('input[name="wc-' + paymentMethod + '-payment-token"]:checked');
        if (paymentToken.length) {
            let val = paymentToken.val();
            if (typeof val !== 'undefined' && val !== 'new') {
                return true;
            }
        }
        return false;
    },
    isApplePayEnabled: () => {
        return angelleye_ppcp_manager.apple_sdk_url !== "";
    },
    isGooglePayEnabled: () => {
        return angelleye_ppcp_manager.google_sdk_url !== "";
    },
    getConstantValue: (constantName, defaultValue) => {
        return angelleye_ppcp_manager.constants && angelleye_ppcp_manager.constants[constantName] ? angelleye_ppcp_manager.constants[constantName] : defaultValue;
    },
    getCheckoutSelectorCss: () => {
        let checkoutSelector = '.woocommerce';
        if (angelleyeOrder.isCheckoutPage()) {
            if (angelleye_ppcp_manager.is_pay_page === 'yes') {
                checkoutSelector = 'form#order_review';
            } else {
                checkoutSelector = 'form.checkout';
            }
        } else if (angelleye_ppcp_manager.page === 'add_payment_method') {
            checkoutSelector = 'form#add_payment_method';
        }
        if (jQuery(checkoutSelector).length === 0) {
            checkoutSelector = 'form.wc-block-checkout__form';
        }

        return checkoutSelector;
    },
    getWooNoticeAreaSelector: () => {
        let wooNoticeClass = '.woocommerce-notices-wrapper:first';
        // On some step checkout pages (e.g CheckoutWC) there are different notice wrappers under each form so this adds support to display in relevant section
        const checkoutFormSelector = angelleyeOrder.getCheckoutSelectorCss();
        if (jQuery(checkoutFormSelector).find(wooNoticeClass).length && jQuery(checkoutFormSelector).find(wooNoticeClass).is(':visible')) {
            return `${checkoutFormSelector} ${wooNoticeClass}`;
        }
        if (jQuery(wooNoticeClass).length) {
            return wooNoticeClass;
        }
        return angelleyeOrder.getCheckoutSelectorCss();
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
    updateWooCheckoutFormNonce: (nonce) => {
        angelleye_ppcp_manager.woocommerce_process_checkout = nonce;
        jQuery("#woocommerce-process-checkout-nonce").val(nonce);
    },
    createSmartButtonOrder: ({angelleye_ppcp_button_selector, errorLogId}) => {
        return angelleyeOrder.createOrder({angelleye_ppcp_button_selector, errorLogId}).then((data) => {
            return data.orderID;
        });
    },
    createOrder: ({angelleye_ppcp_button_selector, billingDetails, shippingDetails, apiUrl, errorLogId, callback}) => {
        if (typeof apiUrl == 'undefined') {
            apiUrl = angelleye_ppcp_manager.create_order_url;
        }
        angelleyeOrder.lastApiResponse = null;
        let formSelector = angelleyeOrder.getWooFormSelector();
        angelleyeOrder.removeError();
        let formData;
        let is_from_checkout = angelleyeOrder.isCheckoutPage();
        let is_from_product = angelleyeOrder.isProductPage();
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

        console.log('formSelector', angelleye_ppcp_button_selector, formSelector, jQuery(formSelector).length);
        let topCheckoutSelectors = ['#angelleye_ppcp_checkout_top', '#angelleye_ppcp_checkout_top_google_pay', '#angelleye_ppcp_checkout_top_apple_pay'];
        if (is_from_checkout && topCheckoutSelectors.indexOf(angelleye_ppcp_button_selector) > -1) {
            formData = '';
        } else {
            if (is_from_product) {
                jQuery(formSelector).find('input[name=angelleye_ppcp-add-to-cart]').remove();
                if (angelleyeOrder.productAddToCart) {
                    jQuery('<input>', {
                        type: 'hidden',
                        name: 'angelleye_ppcp-add-to-cart',
                        value: jQuery("[name='add-to-cart']").val()
                    }).appendTo(formSelector);
                    angelleyeOrder.productAddToCart = false;
                }
            }
            if (billingField) {
                jQuery(formSelector).find('input[name=billing_address_source]').remove();
                billingField.appendTo(formSelector);
            }
            if (shippingField) {
                jQuery(formSelector).find('input[name=shipping_address_source]').remove();
                shippingField.appendTo(formSelector);
            }
            formData = jQuery(formSelector).serialize();
            if (formData === '') {
                formData = 'angelleye_ppcp_payment_method_title=' + jQuery('#angelleye_ppcp_payment_method_title').val();
                if (angelleyeOrder.ppcp_address !== null && angelleyeOrder.ppcp_address !== undefined && angelleyeOrder.ppcp_address !== '') {
                    formData += "&woocommerce-process-checkout-nonce=" + angelleye_ppcp_manager.woocommerce_process_checkout + "&address=" + JSON.stringify(angelleyeOrder.ppcp_address);
                }
            } else {
                if (angelleyeOrder.ppcp_address !== null && angelleyeOrder.ppcp_address !== undefined && angelleyeOrder.ppcp_address !== '') {
                    formData += "&woocommerce-process-checkout-nonce=" + angelleye_ppcp_manager.woocommerce_process_checkout + "&address=" + JSON.stringify(angelleyeOrder.ppcp_address);
                }
            }
        }
        angelleyeJsErrorLogger.addToLog(errorLogId, {
            context: 'api_request',
            url: apiUrl,
            method: 'POST',
            body: formData,
            time: new Date()
        });
        return fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        }).then(async function (res) {
            angelleyeJsErrorLogger.addToLog(errorLogId, {
                context: 'api_response',
                response: res,
                redirected: res.redirected,
                status: res.status,
                time: new Date()
            });
            console.log('createOrder response', {
                res,
                apiUrl,
                redirected: res.redirected,
                url: res.url,
                status: res.status
            });
            if (res.redirected) {
                window.location.href = res.url;
            } else {
                angelleyeOrder.lastApiResponse = await res.clone().text();
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
                    if (localizedMessages.error_message_checkout_validation !== "") {
                        messages = '<li>' + localizedMessages.error_message_checkout_validation + '</li>' + messages;
                    }
                } else {
                    messages = '<li>' + messages + '</li>';
                }
                throw messages;
            } else {
                return data;
            }
        }).then((data) => {
            if (angelleyeOrder.isCheckoutPage() && typeof data.nonce !== 'undefined') {
                angelleyeOrder.updateWooCheckoutFormNonce(data.nonce);
            }
            return data;
        });
    },
    approveOrder: ({orderID, payerID, errorLogId}) => {
        if (angelleyeOrder.isCheckoutPage()) {
            angelleyeOrder.checkoutFormCapture({payPalOrderId: orderID, errorLogId})
        } else {
            if (angelleye_ppcp_manager.is_skip_final_review === 'yes') {
                window.location.href = angelleye_ppcp_manager.direct_capture + '&paypal_order_id=' + orderID + '&paypal_payer_id=' + payerID + '&from=' + angelleye_ppcp_manager.page;
            } else {
                window.location.href = angelleye_ppcp_manager.checkout_url + '&paypal_order_id=' + orderID + (payerID ? '&paypal_payer_id=' + payerID : '') + '&from=' + angelleye_ppcp_manager.page;
            }
    }
    },
    shippingAddressUpdate: (shippingDetails, billingDetails, errorLogId) => {
        return angelleyeOrder.createOrder({apiUrl: angelleye_ppcp_manager.shipping_update_url, shippingDetails, billingDetails, errorLogId});
    },
    triggerPaymentCancelEvent: () => {
        jQuery(document.body).trigger('angelleye_paypal_oncancel');
    },
    onCancel: () => {
        angelleyeOrder.triggerPaymentCancelEvent();
        if (!angelleyeOrder.isCheckoutPage()) {
            angelleyeOrder.showProcessingSpinner();
            if (!angelleyeOrder.isProductPage()) {
                window.location.reload();
            }
        }
    },
    prepareWooErrorMessage: (messages) => {
        return '<ul class="woocommerce-error">' + messages + '</ul>'
    },
    removeError: () => {
        jQuery('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
    },
    showError: (errorMessage) => {
        errorMessage = angelleyeOrder.prepareWooErrorMessage(errorMessage);
        let errorMessageLocation = angelleyeOrder.getWooNoticeAreaSelector();
        jQuery(errorMessageLocation).prepend('<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + errorMessage + '</div>');
        jQuery(errorMessageLocation).removeClass('processing').unblock();
        if (!jQuery(errorMessageLocation).is(':visible'))
            jQuery(errorMessageLocation).css('display', 'block');
        jQuery(errorMessageLocation).find('.input-text, select, input:checkbox').trigger('validate').trigger('blur');
        angelleyeOrder.scrollToWooCommerceNoticesSection();
    },
    showProcessingSpinner: (containerSelector) => {
        if (typeof containerSelector === 'undefined') {
            containerSelector = '.woocommerce';
        }
        if (jQuery('.wp-block-woocommerce-checkout-fields-block').length) {
            jQuery('.wp-block-woocommerce-checkout-fields-block #contact-fields, .wp-block-woocommerce-checkout-fields-block #billing-fields, .wp-block-woocommerce-checkout-fields-block #payment-method').block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}});
        } else if(jQuery(containerSelector).length) {
            jQuery(containerSelector).block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}});
        }
        
    },
    hideProcessingSpinner: (containerSelector) => {
        if (typeof containerSelector === 'undefined') {
            containerSelector = '.woocommerce';
        }
        if (jQuery('.wp-block-woocommerce-checkout-fields-block').length) {
            jQuery('.wc-block-components-checkout-place-order-button, .wp-block-woocommerce-checkout-fields-block #contact-fields, .wp-block-woocommerce-checkout-fields-block #billing-fields, .wp-block-woocommerce-checkout-fields-block #payment-method').unblock();
        } else if (jQuery(containerSelector).length) {
            jQuery(containerSelector).unblock();
        }
        
    },
    handleCreateOrderError: (error, errorLogId) => {
        console.log('create_order_error', error, angelleyeOrder.lastApiResponse);
        angelleyeOrder.hideProcessingSpinner();
        jQuery(document.body).trigger('angelleye_paypal_onerror');
        let errorMessage = error.message ? error.message : error;
        if ((errorMessage.toLowerCase()).indexOf('expected an order id to be passed') > -1) {
            if ((errorMessage.toLowerCase()).indexOf('required fields') < 0) {
                errorMessage = localizedMessages.create_order_error;
            }
        } else if ((errorMessage.toLowerCase()).indexOf('unexpected token') > -1) {
            let lastErrorHtmlEncoded = jQuery("<textarea/>").text(angelleyeOrder.lastApiResponse).html();
            angelleyeJsErrorLogger.logJsError('InvalidJSON, Received Response: ' + lastErrorHtmlEncoded, errorLogId);
            errorMessage = '<li>' + localizedMessages.create_order_error + '</li>';
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
        jQuery('#angelleye_ppcp_checkout, #angelleye_ppcp_checkout_apple_pay, #angelleye_ppcp_checkout_google_pay').hide();
        if (angelleyeOrder.isApplePayPaymentMethodSelected()) {
            jQuery('#angelleye_ppcp_checkout_apple_pay').show();
        } else if (angelleyeOrder.isGooglePayPaymentMethodSelected()) {
            jQuery('#angelleye_ppcp_checkout_google_pay').show();
        } else {
            jQuery('#angelleye_ppcp_checkout').show();
        }
    },
    hidePpcpPaymentMethods: () => {
        jQuery('#angelleye_ppcp_checkout, #angelleye_ppcp_checkout_apple_pay, #angelleye_ppcp_checkout_google_pay').hide();
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
        if ((isAePpcpMethodSelected === true && angelleye_ppcp_manager.is_checkout_disable_smart_button === 'no') ||
                angelleyeOrder.isAngelleyePpcpAdditionalPaymentMethodSelected()) {
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
        if (jQuery(payment_method_element_selector).length === 0) {
            payment_method_element_selector = 'form.wc-block-checkout__form';
        }
        console.log(payment_method_element_selector);
        return payment_method_element_selector;
    },
    setPaymentMethodSelector: (paymentMethod) => {
        let payment_method_element_selector = angelleyeOrder.getWooFormSelector();
        var element = document.querySelector(payment_method_element_selector);
        if (!element) {
            payment_method_element_selector = document.body; // Use body as the default if appendToSelector doesn't exist
        }
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
            console.log(angelleye_ppcp_button_selector);
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
            let errorLogId = null;
            angelleye_paypal_sdk.Buttons({
                style: angelleye_ppcp_style,
                createOrder: function (data, actions) {
                    errorLogId = angelleyeJsErrorLogger.generateErrorId();
                    angelleyeJsErrorLogger.addToLog(errorLogId, 'PayPal Smart Button Payment Started');
                    return angelleyeOrder.createSmartButtonOrder({
                        angelleye_ppcp_button_selector, errorLogId
                    })
                },
                onApprove: function (data, actions) {
                    angelleyeOrder.showProcessingSpinner();
                    angelleyeOrder.approveOrder({...data, errorLogId});
                },
                onCancel: function (data, actions) {
                    angelleyeOrder.hideProcessingSpinner();
                    angelleyeOrder.onCancel();
                },
                onClick: function (data, actions) {
                    angelleyeOrder.setPaymentMethodSelector(data.fundingSource);
                },
                onError: function (err) {
                    angelleyeOrder.handleCreateOrderError(err, errorLogId);
                }
            }).render(angelleye_ppcp_button_selector);
        });
        if (angelleyeOrder.isApplePayEnabled()) {
            jQuery.each(angelleye_ppcp_manager.apple_pay_btn_selector, function (key, angelleye_ppcp_apple_button_selector) {
                (new ApplePayCheckoutButton()).render(angelleye_ppcp_apple_button_selector);
            });
        }
        if (angelleyeOrder.isGooglePayEnabled()) {
            jQuery.each(angelleye_ppcp_manager.google_pay_btn_selector, function (key, angelleye_ppcp_google_button_selector) {
                (new GooglePayCheckoutButton()).render(angelleye_ppcp_google_button_selector);
            });
        }
    },
    checkoutFormCapture: ({checkoutSelector, payPalOrderId, errorLogId}) => {
        if (typeof checkoutSelector === 'undefined') {
            checkoutSelector = angelleyeOrder.getCheckoutSelectorCss();
        }
        let captureUrl = angelleye_ppcp_manager.cc_capture + "&paypal_order_id=" + payPalOrderId + "&woocommerce-process-checkout-nonce=" + angelleye_ppcp_manager.woocommerce_process_checkout + "&is_pay_page=" + angelleye_ppcp_manager.is_pay_page;
        let data;
        if (angelleyeOrder.isCheckoutPage()) {
            data = jQuery(checkoutSelector).serialize();
        }
        // Fluid-Checkout compatibility to stop showing the Leave popup on beforeunload event
        if (typeof window.can_update_checkout !== 'undefined') {
            jQuery(checkoutSelector).on('checkout_place_order_' + angelleyeOrder.getSelectedPaymentMethod(), function () {
                return false;
            });
            jQuery(checkoutSelector).submit();
        }
        fetch(captureUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: data
        }).then(function (res) {
            return res.json();
        }).then(function (data) {
            window.location.href = data.data.redirect;
        }).catch((error) => {
            console.log('capture error', error);
            jQuery(checkoutSelector).removeClass('processing paypal_cc_submiting HostedFields createOrder');
            angelleyeOrder.handleCreateOrderError(error, errorLogId);
            angelleyeOrder.hideProcessingSpinner('#customer_details, .woocommerce-checkout-review-order');
        });
    },
    renderHostedButtons: () => {
        let checkoutSelector = angelleyeOrder.getCheckoutSelectorCss();
        if (jQuery(checkoutSelector).is('.HostedFields')) {
            return false;
        }
        if (angelleyeOrder.isCCPaymentMethodSelected() === false) {
            return false;
        }
        if (typeof angelleye_paypal_sdk === 'undefined') {
            return;
        }
        let spinnerSelectors = checkoutSelector;
        if (jQuery('#customer_details').length && jQuery('.woocommerce-checkout-review-order').length) {
            spinnerSelectors = '#customer_details, .woocommerce-checkout-review-order';
        }
        jQuery(checkoutSelector).addClass('HostedFields');
        let errorLogId = null;
        angelleye_paypal_sdk.HostedFields.render({
            createOrder: function () {
                jQuery('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
                if (jQuery(checkoutSelector).is('.createOrder') === false) {
                    errorLogId = angelleyeJsErrorLogger.generateErrorId();
                    angelleyeJsErrorLogger.addToLog(errorLogId, 'Advanced CC Payment Started');
                    jQuery(checkoutSelector).addClass('createOrder');
                    return angelleyeOrder.createOrder({errorLogId}).then(function (data) {
                        return data.orderID;
                    }).catch((error) => {
                        angelleyeOrder.showError(error);
                        return '';
                    })
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
                    'font-size': angelleye_ppcp_manager.card_style_props.font_size,
                    'color': angelleye_ppcp_manager.card_style_props.color,
                    'font-weight': angelleye_ppcp_manager.card_style_props.font_weight,
                    'font-style': angelleye_ppcp_manager.card_style_props.font_style,
                    'padding': angelleye_ppcp_manager.card_style_props.padding,
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
                    placeholder: localizedMessages.cvc_placeholder
                },
                expirationDate: {
                    selector: '#angelleye_ppcp_cc-card-expiry',
                    placeholder: localizedMessages.expiry_date_placeholder
                }
            }
        }).then(function (hf) {
            hf.on('cardTypeChange', function (event) {
                if (event.cards.length > 0) {
                    let cardname = event.cards[0].type.replace("master-card", "mastercard").replace("american-express", "amex").replace("diners-club", "dinersclub").replace("-", "");
                    if (jQuery.inArray(cardname, angelleye_ppcp_manager.disable_cards) !== -1) {
                        jQuery('#angelleye_ppcp_cc-card-number').addClass('ppcp-invalid-cart');
                        jQuery('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
                        angelleyeOrder.showError(localizedMessages.card_not_supported);
                    } else {
                        jQuery('#angelleye_ppcp_cc-card-number').removeClass().addClass(cardname);
                        jQuery('#angelleye_ppcp_cc-card-number').addClass("input-text wc-credit-card-form-card-number hosted-field-braintree braintree-hosted-fields-valid w48");
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
            // Unhook previous handlers so that we don't run the same handler(s) again to create the orders
            jQuery(document.body).off('submit_paypal_cc_form');
            jQuery(document.body).on('submit_paypal_cc_form', (event) => {
                console.log('submit_paypal_cc_form called');
                event.preventDefault();
                let state = hf.getState();
                if (typeof state.cards !== 'undefined') {
                    if (state.fields.number.isValid) {
                        let cardname = state.cards[0].type;
                        if (typeof cardname !== 'undefined' && cardname !== null || cardname.length !== 0) {
                            if (jQuery.inArray(cardname, angelleye_ppcp_manager.disable_cards) !== -1) {
                                jQuery(checkoutSelector).removeClass('processing paypal_cc_submiting HostedFields createOrder');
                                angelleyeOrder.hideProcessingSpinner(spinnerSelectors);
                                jQuery('#angelleye_ppcp_cc-card-number').addClass('ppcp-invalid-cart');
                                jQuery('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
                                angelleyeOrder.showError(localizedMessages.card_not_supported);
                                return;
                            }
                        }
                    }
                } else {
                    jQuery(checkoutSelector).removeClass('processing paypal_cc_submiting HostedFields createOrder');
                    angelleyeOrder.hideProcessingSpinner(spinnerSelectors);
                    jQuery('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
                    angelleyeOrder.showError(localizedMessages.fields_not_valid);
                    return;
                }
                let formValid = Object.keys(state.fields).every(function (key) {
                    return state.fields[key].isValid;
                });
                if (formValid === false) {
                    jQuery('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
                    jQuery(checkoutSelector).removeClass('processing paypal_cc_submiting HostedFields createOrder');
                    angelleyeOrder.hideProcessingSpinner(spinnerSelectors);
                    angelleyeOrder.showError(localizedMessages.fields_not_valid);
                    return;
                }
                let contingencies = [];
                contingencies = [angelleye_ppcp_manager.three_d_secure_contingency];
                let firstName;
                let lastName;
                if (angelleye_ppcp_manager.is_pay_page === 'yes') {
                    firstName = angelleye_ppcp_manager.first_name;
                    lastName = angelleye_ppcp_manager.last_name;
                } else {
                    firstName = document.getElementById('billing_first_name') ? document.getElementById('billing_first_name').value : '';
                    lastName = document.getElementById('billing_last_name') ? document.getElementById('billing_last_name').value : '';
                }
                // Remove any existing processing spinner as we don't know if a spinner has been added by woocommerce on
                // place order button click or any third party button added/not added the spinner
                angelleyeOrder.hideProcessingSpinner(spinnerSelectors);
                angelleyeOrder.showProcessingSpinner(spinnerSelectors);
                hf.submit({
                    contingencies: contingencies,
                    cardholderName: firstName + ' ' + lastName
                }).then(
                        function (payload) {
                            if (payload.orderId) {
                                angelleyeOrder.checkoutFormCapture({checkoutSelector, payPalOrderId: payload.orderId, errorLogId});
                            }
                        }, function (error) {
                    console.log('hf_submit_error_handler', error)
                    jQuery(checkoutSelector).removeClass('processing paypal_cc_submiting HostedFields createOrder');
                    angelleyeOrder.hideProcessingSpinner(spinnerSelectors);
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
                        angelleyeJsErrorLogger.logJsError(error_message, errorLogId);
                        angelleyeOrder.showError(error_message);
                    }
                }
                ).catch((error) => {
                    angelleyeJsErrorLogger.logJsError(error, errorLogId);
                    console.log('hf_submit_exception_handler', error);
                });
            });
        }).catch(function (error) {
            // We don't need to display this error to customers as this is unrelated. This usually throws an error like:
            // {"name":"BraintreeError","code":"HOSTED_FIELDS_TIMEOUT","message":"Hosted Fields timed out when attempting to set up.","type":"UNKNOWN"}
            angelleyeJsErrorLogger.logJsError(JSON.stringify(error), errorLogId);
            console.log('error: ', JSON.stringify(error));
        });
    },
    applePayDataInit: async () => {
        // This function is deprecated as we don't use it because its already loaded in environment
        if (angelleyeOrder.isApplePayEnabled()) {
            // block the apple pay button UI to make sure nobody can click it while its updating.
            angelleyeOrder.showProcessingSpinner('#angelleye_ppcp_cart_apple_pay');
            // trigger an ajax call to update the total amount, in case there is no shipping required object
            let response = await angelleyeOrder.shippingAddressUpdate({});
            angelleyeOrder.hideProcessingSpinner('#angelleye_ppcp_cart_apple_pay');
            if (typeof response.totalAmount !== 'undefined') {
                // successful response
                angelleye_ppcp_manager.angelleye_cart_totals = response;
            } else {
                // in case of unsuccessful response, refresh the page.
                window.location.reload();
            }
        }
    },
    getCartDetails: () => {
        return angelleye_ppcp_manager.angelleye_cart_totals;
    },
    updateCartTotalsInEnvironment: (data) => {
        let cartTotals;
        let response = {renderNeeded: true};
        if (data) {
            cartTotals = data;
        } else if (jQuery('#angelleye_cart_totals').length) {
            cartTotals = JSON.parse(jQuery('#angelleye_cart_totals').text());
        }
        if (cartTotals) {
            // Check if the currency changed then reload the JS SDK with latest currency
            const updateCartTotal = () => {
                console.log('angelleye_cart_total_updated', cartTotals);
                angelleye_ppcp_manager.angelleye_cart_totals = cartTotals;
                jQuery(document.body).trigger('angelleye_cart_total_updated');
            };
            const cartDetails = angelleyeOrder.getCartDetails();
            if (cartDetails.currencyCode !== cartTotals.currencyCode) {
                console.log(`Currency changed, refreshing PayPal Lib SDK: ${cartDetails.currencyCode} => ${cartTotals.currencyCode}`);
                let checkoutSelector = angelleyeOrder.getCheckoutSelectorCss();
                angelleyeOrder.showProcessingSpinner(checkoutSelector);
                angelleye_ppcp_manager.paypal_sdk_url = pfwUrlHelper.setQueryParam('currency', cartTotals.currencyCode, angelleye_ppcp_manager.paypal_sdk_url);
                window.angelleyeLoadAsyncLibs(() => {
                    updateCartTotal();
                    angelleyeOrder.renderPaymentButtons();
                    angelleyeOrder.hideProcessingSpinner(checkoutSelector);
                }, () => {
                    console.log('Unable to refresh the PayPal Lib');
                    angelleyeOrder.showError('<li>' + localizedMessages.currency_change_js_load_error + '</li>');
                    angelleyeOrder.hideProcessingSpinner(checkoutSelector);
                });
                response.renderNeeded = false;
            } else {
                updateCartTotal();
            }
        }
        return response;
    },
    addPaymentMethodAdvancedCreditCard: () => {
        if (typeof angelleye_paypal_sdk === 'undefined') {
            return;
        }
        let addPaymentMethodForm = angelleyeOrder.getCheckoutSelectorCss();
        const cardFields = angelleye_paypal_sdk.CardFields({
            createVaultSetupToken: async () => {
                angelleyeOrder.showProcessingSpinner(addPaymentMethodForm);
                const result = await fetch(angelleye_ppcp_manager.angelleye_ppcp_cc_setup_tokens, {
                    method: "POST"
                });
                const {id} = await result.json();
                return id;
            },
            onApprove: async (data) => {
                const approvalTokenIdParamName = angelleyeOrder.getConstantValue('approval_token_id');
                const endpoint = angelleye_ppcp_manager.advanced_credit_card_create_payment_token;
                const url = `${endpoint}&${approvalTokenIdParamName}=${data.vaultSetupToken}`;
                fetch(url, {method: "POST"}).then(response => {
                    return response.json();
                }).then(data => {
                    window.location.href = data.redirect;
                }).catch(error => {
                    angelleyeOrder.showError(error);
                    angelleyeOrder.hideProcessingSpinner(addPaymentMethodForm);
                    console.error('An error occurred:', error);
                });
            },
            onError: (error) => {
                angelleyeOrder.hideProcessingSpinner(addPaymentMethodForm);
                angelleyeOrder.showError(error);
                console.error('Something went wrong:', error)
            }
        });
        if (cardFields.isEligible()) {
            cardFields.NameField().render("#ppcp-my-account-card-holder-name");
            cardFields.NumberField().render("#ppcp-my-account-card-number");
            cardFields.ExpiryField().render("#ppcp-my-account-expiration-date");
            cardFields.CVVField().render("#ppcp-my-account-cvv");
        } else {
            jQuery('.payment_method_angelleye_ppcp_cc').hide();
        }

        jQuery(addPaymentMethodForm).unbind('submit').on('submit', (event) => {
            angelleyeOrder.removeError();
            if (angelleyeOrder.isCCPaymentMethodSelected() || angelleyeOrder.isPpcpPaymentMethodSelected()) {
                angelleyeOrder.showProcessingSpinner(addPaymentMethodForm);
                if (angelleyeOrder.isCCPaymentMethodSelected() === true) {
                    event.preventDefault();
                    cardFields.submit().then((hf) => {
                        console.log("add_payment_method_submit_success");
                    }).catch((error) => {
                        angelleyeOrder.hideProcessingSpinner(addPaymentMethodForm);
                        angelleyeOrder.showError(error);
                        console.error("add_payment_method_submit_error:", error);
                    });
                }
            }
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
            console.log(event);
        }
    },
    renderPaymentButtons: () => {
        angelleyeOrder.hideShowPlaceOrderButton();
        angelleyeOrder.renderSmartButton();
        if (angelleyeOrder.isHostedFieldEligible() === true) {
            jQuery('#angelleye_ppcp_cc-card-number iframe').length === 0 ? jQuery(angelleyeOrder.getCheckoutSelectorCss()).removeClass('HostedFields') : null;
            jQuery('.checkout_cc_separator').show();
            jQuery('#wc-angelleye_ppcp-cc-form').show();
            angelleyeOrder.renderHostedButtons();
        }
    },
    hooks: {
        handleWooEvents: () => {
            jQuery(document.body).on('updated_cart_totals payment_method_selected updated_checkout', function (event, data) {
                console.log(`hook_received => ${event.type}`, data, angelleyeOrder.getCartDetails());
                angelleyeOrder.dequeueEvent(event.type);

                let response;
                if (typeof data !== 'undefined' && typeof data["fragments"] !== 'undefined' && typeof data["fragments"]["angelleye_payments_data"] !== "undefined") {
                    response = angelleyeOrder.updateCartTotalsInEnvironment(JSON.parse(data["fragments"]["angelleye_payments_data"]));
                } else if (event.type === 'updated_cart_totals') {
                    response = angelleyeOrder.updateCartTotalsInEnvironment();
                }

                if (!response || response.renderNeeded) {

                    angelleyeOrder.renderPaymentButtons();
                }
            });
            jQuery(document.body).on('trigger_angelleye_ppcp_cc', function (event) {
                angelleyeOrder.renderPaymentButtons();
            });
        },
        handleRaceConditionOnWooHooks: () => {
            jQuery(document.body).on('updated_cart_totals payment_method_selected updated_checkout ppcp_block_ready', function (event, data) {
                if (!angelleyeOrder.isPendingEventTriggering) {
                    angelleyeOrder.addEventsForCallback(event.type, event, data);
                }
            });
        },
        onPaymentCancellation: () => {
            jQuery(document.body).on('angelleye_paypal_oncancel', function (event) {
                event.preventDefault();
                if (angelleyeOrder.isProductPage() && angelleyeOrder.productAddToCart === false) {
                    fetch(angelleye_ppcp_manager.update_cart_oncancel, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: jQuery(angelleyeOrder.getWooFormSelector()).serialize()
                    }).then(function (res) {
                        return res.json();
                    }).then(function (data) {
                        window.location.reload();
                    });
                }
            });
        }
    }
}

__ = wp.i18n.__;
const localizedMessages = {
    card_not_supported: __('Unfortunately, we do not support this credit card type. Please try another card type.', 'paypal-for-woocommerce'),
    fields_not_valid: __('Unfortunately, your credit card details are not valid. Please review the card details and try again.', 'paypal-for-woocommerce'),
    error_message_checkout_validation: __('Unable to create the order due to the following errors.', 'paypal-for-woocommerce'),
    expiry_date_placeholder: __('MM / YY', 'paypal-for-woocommerce'),
    cvc_placeholder: __('CVC', 'paypal-for-woocommerce', 'paypal-for-woocommerce'),
    empty_cart_message: __('Your shopping cart seems to be empty.', 'paypal-for-woocommerce'),
    total_amount_placeholder: __('Total Amount', 'paypal-for-woocommerce'),
    apple_pay_pay_error: __('An error occurred while initiating the ApplePay payment.', 'paypal-for-woocommerce'),
    error_validating_merchant: __('This merchant is not enabled to process requested payment method. please contact website owner.', 'paypal-for-woocommerce'),
    general_error_message: __('We are unable to process your request at the moment, please contact website owner.', 'paypal-for-woocommerce'),
    shipping_amount_update_error: __('Unable to update the shipping amount.', 'paypal-for-woocommerce'),
    shipping_amount_pull_error: __('Unable to pull the shipping amount details based on selected address', 'paypal-for-woocommerce'),
    currency_change_js_load_error: __('We encountered an issue loading the updated currency. Please refresh the page or contact support for assistance.', 'paypal-for-woocommerce'),
    create_order_error: __('Unable to create the order, please contact the support.', 'paypal-for-woocommerce'),
    create_order_error_with_content: __('Unable to create the order, please contact the support with following error message.', 'paypal-for-woocommerce')
};

const pfwUrlHelper = {
    getUrlObject: (url) => {
        if (!url) {
            url = window.location.href;
        }
        return new URL(url);
    },
    setQueryParam: (name, value, url) => {
        url = pfwUrlHelper.getUrlObject(url);
        let searchParams = url.searchParams;
        searchParams.set(name, value);
        url.search = searchParams.toString();
        return url.toString();
    },
    getQueryParams: (url) => {
        url = pfwUrlHelper.getUrlObject(url);
        return url.searchParams;
    },
    removeQueryParam: (name, url) => {
        url = pfwUrlHelper.getUrlObject(url);
        let searchParams = url.searchParams;
        searchParams.delete(name);
        url.search = searchParams.toString();
        return url.toString();
    },
    removeAllParams: (url) => {
        url = pfwUrlHelper.getUrlObject(url);
        url.search = '';
        return url.toString();
    }
}
const angelleyeJsErrorLogger = {
    errorStackMeta: {},
    generateErrorId: () => {
        return Date.now() + Math.floor(Math.random() * 101);
    },
    addToLog: (errorLogId, metaData) => {
        if (typeof angelleyeJsErrorLogger.errorStackMeta[errorLogId] === 'undefined') {
            angelleyeJsErrorLogger.errorStackMeta[errorLogId] = [];
        }
        angelleyeJsErrorLogger.errorStackMeta[errorLogId].push(metaData);
    },
    getLogTrace: (errorLogId) => {
        return typeof angelleyeJsErrorLogger.errorStackMeta[errorLogId] !== 'undefined' ?
                angelleyeJsErrorLogger.errorStackMeta[errorLogId] : [];
    },
    logJsError: (error, errorLogId) => {
        fetch(angelleye_ppcp_manager.handle_js_errors, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({error, logTrace: angelleyeJsErrorLogger.getLogTrace(errorLogId)}),
        }).then(function (res) {
            //alert(res.json());
        }).then(function (data) {
            //alert(data);
        });
    }
}
