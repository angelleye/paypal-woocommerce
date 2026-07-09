const angelleyeOrder = {
    productAddToCart: true,
    lastApiResponse: null,
    ppcp_address: [],
    ppcpCcSubmitHookReady: false,
    ppcpCcSubmitRecoveryTimer: null,
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
        } else if (jQuery('input[name="radio-control-wc-payment-method-options"]').length) {
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
    isPpcpCcSubmitHookReady: () => {
        return angelleyeOrder.ppcpCcSubmitHookReady === true;
    },
    setPpcpCcSubmitHookReady: (isReady) => {
        angelleyeOrder.ppcpCcSubmitHookReady = isReady === true;
    },
    stopPpcpCcSubmitWatchdog: () => {
        if (angelleyeOrder.ppcpCcSubmitRecoveryTimer) {
            clearTimeout(angelleyeOrder.ppcpCcSubmitRecoveryTimer);
            angelleyeOrder.ppcpCcSubmitRecoveryTimer = null;
        }
    },
    clearPpcpCcSubmittingState: (checkoutSelector) => {
        angelleyeOrder.stopPpcpCcSubmitWatchdog();
        if (checkoutSelector && jQuery(checkoutSelector).length) {
            jQuery(checkoutSelector).removeClass('processing paypal_cc_submiting');
        }
        angelleyeOrder.hideProcessingSpinner();
    },
    startPpcpCcSubmitWatchdog: (checkoutSelector) => {
        angelleyeOrder.stopPpcpCcSubmitWatchdog();
        angelleyeOrder.ppcpCcSubmitRecoveryTimer = setTimeout(() => {
            if (checkoutSelector && jQuery(checkoutSelector).length && jQuery(checkoutSelector).hasClass('paypal_cc_submiting') && !jQuery(checkoutSelector).hasClass('createOrder')) {
                angelleyeOrder.clearPpcpCcSubmittingState(checkoutSelector);
            }
        }, 8000);
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
    ppcp_block_mode: false,
    blockCreateOrderError: null,
    // The #angelleye_ppcp_cc-card-number DOM node the SDK Card Fields were
    // last mounted into, and the timestamp of that render() call. Used by
    // renderHostedButtons to detect whether a (possibly still-in-flight)
    // mount already ran for the current container, so an updated_checkout
    // / payment_method_selected burst can't stack a duplicate set of
    // card-field iframes. The timestamp lets a later call distinguish an
    // in-flight render from one that silently failed, so a stuck mount
    // doesn't leave the customer with empty containers forever.
    hostedFieldsContainer: null,
    hostedFieldsRenderedAt: 0,
    /**
     * Drives the PPCP-CC card-fields submit pipeline for WooCommerce Blocks
     * and returns a Promise that the Blocks onPaymentSetup subscriber awaits.
     *   - Resolves with the PayPal order id when the SDK onApprove event
     *     fires (after 3DS / card validation completes).
     *   - Rejects with an Error when create_order / SDK / validation surface
     *     a failure, so the Blocks subscriber returns ERROR and Blocks clears
     *     the spinner and shows the message.
     * After the promise resolves, the Blocks subscriber returns SUCCESS with
     * the order id inside paymentMethodData; Blocks then POSTs the full
     * Blocks checkout payload (including all custom fields and extensions)
     * to /wc/store/v1/checkout, which routes through process_payment() for
     * the actual capture.
     */
    runBlocksPpcpCcFlow: () => {
        return new Promise((resolve, reject) => {
            let settled = false;
            angelleyeOrder.ppcp_block_mode = true;
            angelleyeOrder.blockCreateOrderError = null;
            const cleanup = () => {
                jQuery(document.body).off('angelleye_ppcp_cc_approved.ppcpBlocks', approvalHandler);
                jQuery(document.body).off('angelleye_ppcp_cc_error.ppcpBlocks', errorHandler);
                angelleyeOrder.ppcp_block_mode = false;
                angelleyeOrder.blockCreateOrderError = null;
            };
            const approvalHandler = (e, data) => {
                if (settled) return;
                settled = true;
                cleanup();
                resolve(data && data.paypalOrderId);
            };
            const errorHandler = (e, errData) => {
                if (settled) return;
                settled = true;
                cleanup();
                const err = new Error((errData && errData.message) || 'PayPal card payment failed');
                // Preserve the failure context so the Blocks subscriber can
                // decide where to display the notice (top of form vs inline
                // next to the payment method).
                err.context = (errData && errData.context) || null;
                reject(err);
            };
            jQuery(document.body).on('angelleye_ppcp_cc_approved.ppcpBlocks', approvalHandler);
            jQuery(document.body).on('angelleye_ppcp_cc_error.ppcpBlocks', errorHandler);
            jQuery(document.body).trigger('submit_paypal_cc_form');
        });
    },
    createOrder: ({angelleye_ppcp_button_selector, billingDetails, shippingDetails, apiUrl, errorLogId, callback}) => {
        // Detect FunnelKit sliding cart buttons — they should always submit cart context, not product/page context.
        let fkcartSelectors = ['#angelleye_ppcp_fkcart', '#angelleye_ppcp_fkcart_apple_pay', '#angelleye_ppcp_fkcart_google_pay'];
        let is_from_fkcart = fkcartSelectors.indexOf(angelleye_ppcp_button_selector) > -1;
        if (typeof apiUrl == 'undefined') {
            apiUrl = angelleye_ppcp_manager.create_order_url;
            if (is_from_fkcart) {
                // Force from=cart so server reads WC()->cart directly instead of treating as product/checkout request
                apiUrl = apiUrl.replace(/([?&])from=[^&]*/, '$1from=cart');
                if (apiUrl.indexOf('from=') === -1) {
                    apiUrl += (apiUrl.indexOf('?') > -1 ? '&' : '?') + 'from=cart';
                }
            }
        }
        angelleyeOrder.lastApiResponse = null;
        let formSelector = angelleyeOrder.getWooFormSelector();
        angelleyeOrder.removeError();
        let formData;
        let is_from_checkout = angelleyeOrder.isCheckoutPage();
        // FKCart buttons should never be treated as product page even if rendered on a product page
        let is_from_product = is_from_fkcart ? false : angelleyeOrder.isProductPage();
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
        let topCheckoutSelectors = ['#angelleye_ppcp_checkout_top', '#angelleye_ppcp_checkout_top_google_pay', '#angelleye_ppcp_checkout_top_apple_pay'];
        let checkoutSource = null;
        let is_from_block_checkout = angelleyeOrder.ppcp_block_mode === true;
        if (is_from_block_checkout) {
            checkoutSource = 'block_checkout';
        } else if (is_from_checkout) {
            checkoutSource = topCheckoutSelectors.indexOf(angelleye_ppcp_button_selector) > -1 ? 'checkout_top' : 'checkout_regular';
        }
        if (is_from_block_checkout) {
            // WooCommerce Blocks pre-flight. Server only needs enough data
            // to build a PayPal order against the cart (customer address so
            // the PayPal order carries shipping/billing). All order-level
            // validation, custom fields and extensions flow through the
            // Store API /wc/store/v1/checkout POST that Blocks fires after
            // the subscriber resolves.
            let paymentMethodTitle = jQuery('#angelleye_ppcp_payment_method_title').val() || 'paypal';
            formData = 'angelleye_ppcp_checkout_source=block_checkout';
            formData += '&payment_method=angelleye_ppcp_cc';
            formData += '&angelleye_ppcp_payment_method_title=' + encodeURIComponent(paymentMethodTitle);
            formData += '&woocommerce-process-checkout-nonce=' + angelleye_ppcp_manager.woocommerce_process_checkout;
            if (angelleyeOrder.ppcp_address !== null && angelleyeOrder.ppcp_address !== undefined && angelleyeOrder.ppcp_address !== '') {
                formData += '&address=' + encodeURIComponent(JSON.stringify(angelleyeOrder.ppcp_address));
            }
        } else if (is_from_checkout && topCheckoutSelectors.indexOf(angelleye_ppcp_button_selector) > -1) {
            formData = 'angelleye_ppcp_checkout_source=' + encodeURIComponent(checkoutSource);
        } else if (is_from_fkcart) {
            // FunnelKit sliding cart — server reads existing WC()->cart contents, no form serialization needed
            formData = 'angelleye_ppcp_payment_method_title=' + jQuery('#angelleye_ppcp_payment_method_title').val();
            formData += '&woocommerce-process-checkout-nonce=' + angelleye_ppcp_manager.woocommerce_process_checkout;
            // Forward the address details (e.g. Google Pay / Apple Pay shipping-address-update
            // callbacks) so the server can recalculate shipping against the existing cart —
            // without appending angelleye_ppcp-add-to-cart, which would re-add the product and
            // double the total for an item already in the sliding cart.
            if (billingDetails) {
                formData += '&billing_address_source=' + encodeURIComponent(JSON.stringify(billingDetails));
            }
            if (shippingDetails) {
                formData += '&shipping_address_source=' + encodeURIComponent(JSON.stringify(shippingDetails));
            }
            if (angelleyeOrder.ppcp_address !== null && angelleyeOrder.ppcp_address !== undefined && angelleyeOrder.ppcp_address !== '') {
                formData += '&address=' + JSON.stringify(angelleyeOrder.ppcp_address);
            }
        } else {
            if (is_from_product) {
                // Always re-add on every click. The server's product branch
                // handles re-add idempotently (empties cart then re-adds), so
                // skipping the re-add on the second click — as the old
                // one-shot flag did — left retries with no add-to-cart
                // signal and surfaced a spurious "session has expired" error
                // whenever the first click hit validation and never actually
                // populated the cart.
                jQuery(formSelector).find('input[name=angelleye_ppcp-add-to-cart]').remove();
                jQuery('<input>', {
                    type: 'hidden',
                    name: 'angelleye_ppcp-add-to-cart',
                    value: jQuery("[name='add-to-cart']").val()
                }).appendTo(formSelector);
                // Keep the flag flip so the angelleye_paypal_oncancel handler
                // still knows the PPCP flow started and can clean up cart
                // items on cancel.
                angelleyeOrder.productAddToCart = false;
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
            if (is_from_product) {
                // Product pages can expose duplicated variation inputs via
                // plugins/themes — dedupe before sending so PHP's $_POST
                // doesn't receive an empty override of the selected value.
                formData = angelleyeOrder.dedupeFormBody(formData);
            }
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
            if (checkoutSource !== null) {
                formData += '&angelleye_ppcp_checkout_source=' + encodeURIComponent(checkoutSource);
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
    shippingAddressUpdate: (shippingDetails, billingDetails, errorLogId, angelleye_ppcp_button_selector) => {
        // Forward the button selector so createOrder keeps the sliding-cart (FKCart) context.
        // Without it the shipping update is treated as a product-page buy-now and re-adds the
        // product, doubling the cart total for an item already present in the sliding cart.
        return angelleyeOrder.createOrder({angelleye_ppcp_button_selector, apiUrl: angelleye_ppcp_manager.shipping_update_url, shippingDetails, billingDetails, errorLogId});
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
    resolveProcessingContainerSelector: (containerSelector) => {
        if (typeof containerSelector === 'undefined' || !containerSelector) {
            containerSelector = '.woocommerce';
        }

        if (jQuery(containerSelector).length && jQuery(containerSelector).is(':visible')) {
            return containerSelector;
        }

        const fallbackSelectors = [
            '.woocommerce-checkout',
            '.woocommerce',
            '#customer_details, .woocommerce-checkout-review-order',
            'form.checkout'
        ];

        for (let i = 0; i < fallbackSelectors.length; i++) {
            if (jQuery(fallbackSelectors[i]).length) {
                return fallbackSelectors[i];
            }
        }

        return containerSelector;
    },
    showProcessingSpinner: (containerSelector) => {
        if (jQuery('.wp-block-woocommerce-checkout-fields-block').length) {
            jQuery('.wp-block-woocommerce-checkout-fields-block #contact-fields, .wp-block-woocommerce-checkout-fields-block #billing-fields, .wp-block-woocommerce-checkout-fields-block #payment-method').block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}});
        } else {
            const blockTarget = angelleyeOrder.resolveProcessingContainerSelector(containerSelector);
            if (jQuery(blockTarget).length) {
                jQuery(blockTarget).block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}});
            }
        }

    },
    hideProcessingSpinner: (containerSelector) => {
        if (jQuery('.wp-block-woocommerce-checkout-fields-block').length) {
            jQuery('.wc-block-components-checkout-place-order-button, .wp-block-woocommerce-checkout-fields-block #contact-fields, .wp-block-woocommerce-checkout-fields-block #billing-fields, .wp-block-woocommerce-checkout-fields-block #payment-method').unblock();
        } else {
            const unblockTargets = [
                angelleyeOrder.resolveProcessingContainerSelector(containerSelector),
                '.woocommerce',
                '.woocommerce-checkout',
                '#customer_details, .woocommerce-checkout-review-order',
                'form.checkout',
                '.woocommerce-checkout-payment',
                '.woocommerce-checkout-review-order-table',
                '#order_review'
            ];

            unblockTargets.forEach((selector) => {
                if (selector && jQuery(selector).length) {
                    jQuery(selector).unblock();
                }
            });
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
    parsePayPalSdkError: (error) => {
        let message = '';
        let debugId = '';
        let issueCode = '';

        if (typeof error === 'string') {
            message = error;
        } else if (error && typeof error === 'object') {
            if (Array.isArray(error.details) && error.details.length > 0) {
                issueCode = error.details[0].issue || '';
                message = error.details[0].description || error.details[0].issue || '';
            }
            message = message || error.message || error.name || '';
            debugId = error.debug_id || error.debugId || error.paypalDebugId || '';
        }

        // Sometimes PayPal SDK sends JSON payload as part of error.message text.
        if (!debugId && typeof message === 'string' && message.indexOf('{') > -1) {
            try {
                const payload = JSON.parse(message.substring(message.indexOf('{')));
                if (!debugId) {
                    debugId = payload.debug_id || '';
                }
                if (!issueCode && Array.isArray(payload.details) && payload.details.length > 0) {
                    issueCode = payload.details[0].issue || '';
                }
                if (!message || message === error.message) {
                    message = payload.message || message;
                }
                if (Array.isArray(payload.details) && payload.details.length > 0 && payload.details[0].description) {
                    message = payload.details[0].description;
                }
            } catch (e) {
                // keep original message
            }
        }

        const normalizedMessage = (message || '').toLowerCase();
        const normalizedIssueCode = (issueCode || '').toLowerCase();
        const genericIssueCodes = ['unprocessable_entity', 'instrument_declined'];
        const hasGenericMessage = normalizedMessage === 'unprocessable_entity' || normalizedMessage === 'instrument_declined' || normalizedMessage.indexOf('returned status 422') > -1;
        const hasGenericIssueCode = genericIssueCodes.indexOf(normalizedIssueCode) > -1;

        if (hasGenericMessage || hasGenericIssueCode) {
            message = wp.i18n.__('We could not process this card. Please check card details or try another payment method.', 'paypal-for-woocommerce');
        }

        if (!message) {
            message = localizedMessages.general_error_message;
        }
        if (debugId) {
            message += ' [PayPal Debug ID: ' + debugId + ']';
        }

        return '<li>' + message + '</li>';
    },
    isCardFieldEligible: () => {
        if (angelleyeOrder.isCheckoutPage()) {
            if (angelleye_ppcp_manager.advanced_card_payments === 'yes') {
                return typeof angelleye_paypal_sdk !== 'undefined' && typeof angelleye_paypal_sdk.CardFields !== 'undefined'
                        ? angelleye_paypal_sdk.CardFields().isEligible() === true
                        : false;
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
        if (angelleyeOrder.isCardFieldEligible() === false) {
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
    /**
     * Deduplicate a URL-encoded form body by key, preferring non-empty
     * values. Some variable-product pages (WC variations alongside
     * plugins like WooCommerce Advanced Product Fields / gtm4wp / theme
     * mirror forms) cause jQuery.serialize() to emit each field twice,
     * with one copy populated and the other empty. PHP's $_POST uses
     * last-value-wins, which picks the empty copy and fails WC's
     * variation-attribute validation with "Invalid value posted for X".
     */
    dedupeFormBody: (formData) => {
        if (!formData || typeof formData !== 'string') {
            return formData;
        }
        const parts = formData.split('&');
        const indexByKey = {};
        const result = [];
        for (const part of parts) {
            if (!part) continue;
            const eq = part.indexOf('=');
            const key = eq >= 0 ? part.substring(0, eq) : part;
            const val = eq >= 0 ? part.substring(eq + 1) : '';
            if (Object.prototype.hasOwnProperty.call(indexByKey, key)) {
                const existingIdx = indexByKey[key];
                const existing = result[existingIdx];
                const existingEq = existing.indexOf('=');
                const existingVal = existingEq >= 0 ? existing.substring(existingEq + 1) : '';
                // Replace only when the first copy is empty and the later
                // copy has a value; otherwise keep the first occurrence.
                if (existingVal === '' && val !== '') {
                    result[existingIdx] = part;
                }
            } else {
                indexByKey[key] = result.length;
                result.push(part);
            }
        }
        return result.join('&');
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
            // Use FunnelKit Sliding Cart specific style props for FKCart button
            let isFkcartButton = (angelleye_ppcp_button_selector === '#angelleye_ppcp_fkcart');
            let styleSource = (isFkcartButton && angelleye_ppcp_manager.fkcart_style) ? angelleye_ppcp_manager.fkcart_style : angelleye_ppcp_manager;
            let angelleye_ppcp_style = {
                layout: styleSource.style_layout,
                color: styleSource.style_color,
                shape: styleSource.style_shape,
                label: styleSource.style_label
            };
            if (styleSource.style_height !== '') {
                angelleye_ppcp_style['height'] = parseInt(styleSource.style_height);
            }
            if (styleSource.style_layout !== 'vertical') {
                angelleye_ppcp_style['tagline'] = (styleSource.style_tagline === 'yes') ? true : false;
            }
            let errorLogId = null;
            let buttonCallbacks = {
                createOrder: function (data, actions) {
                    errorLogId = angelleyeJsErrorLogger.generateErrorId();
                    angelleyeOrder.showProcessingSpinner();
                    angelleyeJsErrorLogger.addToLog(errorLogId, 'PayPal Smart Button Payment Started');
                    return angelleyeOrder.createSmartButtonOrder({
                        angelleye_ppcp_button_selector, errorLogId
                    }).finally(() => {
                        angelleyeOrder.hideProcessingSpinner();
                    });
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
            };

            // FunnelKit Sliding Cart with disabled funding methods: render each enabled funding source
            // explicitly so we can hide specific methods (e.g. card) without affecting other PFW buttons
            // on the same page (which all share the same global SDK load).
            let fkcartDisabled = (isFkcartButton && angelleye_ppcp_manager.fkcart_style && Array.isArray(angelleye_ppcp_manager.fkcart_style.disable_funding))
                ? angelleye_ppcp_manager.fkcart_style.disable_funding
                : [];

            if (isFkcartButton && fkcartDisabled.length > 0) {
                // Funding sources to render in vertical order. Apple/Google Pay are rendered separately below.
                let fundingSources = ['paypal', 'venmo', 'paylater', 'card', 'credit'];
                // Allowed style colors per PayPal SDK validation
                let allowedColors = {
                    paypal:   ['gold', 'blue', 'silver', 'white', 'black'],
                    venmo:    ['blue', 'silver', 'black', 'white'],
                    paylater: ['gold', 'blue', 'silver', 'white', 'black'],
                    card:     ['black', 'white', 'silver'],
                    credit:   ['darkblue', 'blue']
                };
                let renderedAny = false;
                fundingSources.forEach(function(src) {
                    if (fkcartDisabled.indexOf(src) > -1) {
                        return;
                    }
                    if (!angelleye_paypal_sdk.FUNDING || !angelleye_paypal_sdk.FUNDING[src.toUpperCase()]) {
                        return;
                    }
                    // Per-source style: clone and adjust color/label to satisfy SDK validation
                    let srcStyle = Object.assign({}, angelleye_ppcp_style);
                    if (allowedColors[src] && allowedColors[src].indexOf(srcStyle.color) === -1) {
                        srcStyle.color = allowedColors[src][0];
                    }
                    if (src === 'venmo' || src === 'card' || src === 'credit') {
                        delete srcStyle.label;
                    }
                    let btn = angelleye_paypal_sdk.Buttons(Object.assign({
                        style: srcStyle,
                        fundingSource: angelleye_paypal_sdk.FUNDING[src.toUpperCase()]
                    }, buttonCallbacks));
                    if (btn.isEligible()) {
                        btn.render(angelleye_ppcp_button_selector);
                        renderedAny = true;
                    }
                });
                if (!renderedAny) {
                    // Fallback to default auto-rendering if no funding source was eligible
                    angelleye_paypal_sdk.Buttons(Object.assign({style: angelleye_ppcp_style}, buttonCallbacks)).render(angelleye_ppcp_button_selector);
                }
            } else {
                // Default rendering: single Buttons() call that auto-renders all eligible funding sources
                angelleye_paypal_sdk.Buttons(Object.assign({style: angelleye_ppcp_style}, buttonCallbacks)).render(angelleye_ppcp_button_selector);
            }
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
            jQuery(checkoutSelector).removeClass('processing paypal_cc_submiting CardFields createOrder');
            angelleyeOrder.handleCreateOrderError(error, errorLogId);
            angelleyeOrder.hideProcessingSpinner('#customer_details, .woocommerce-checkout-review-order');
        });
    },
    renderHostedButtons: () => {
        if (typeof angelleye_paypal_sdk === 'undefined') {
            angelleyeOrder.setPpcpCcSubmitHookReady(false);
            return;
        }
        let checkoutSelector = angelleyeOrder.getCheckoutSelectorCss();
        if (jQuery(checkoutSelector).is('.CardFields')) {
            return false;
        }
        // updated_checkout swaps in a fresh review-order fragment: the
        // .CardFields class on the form survives, but the card-field
        // containers become brand-new empty nodes, and renderPaymentButtons
        // clears the class (its iframe-count check) so we re-mount. The race
        // that duplicates the fields: CardFields().render() appends its
        // iframe asynchronously, so a second event (another updated_checkout
        // / payment_method_selected) can fire while the first mount is still
        // in flight — the iframe is not in the DOM yet, the class gets
        // cleared again, and a second set is mounted into the same node.
        //
        // Guard on the container node identity *and* the iframe presence:
        //   - iframe already in the node → mounted, bail.
        //   - no iframe yet but render() was called very recently → still
        //     in flight, bail (preventing the duplicate-mount race).
        //   - no iframe and the render call is older than the grace window
        //     → the previous mount silently failed (SDK glitch, hidden
        //     container at render time, etc.); fall through and retry,
        //     otherwise the customer is stuck with empty containers.
        // A node replaced by a fresh fragment (updated_checkout) is always
        // a different reference, so re-renders into a new container always
        // proceed.
        const cardNumberContainer = document.getElementById('angelleye_ppcp_cc-card-number');
        if (cardNumberContainer && angelleyeOrder.hostedFieldsContainer === cardNumberContainer) {
            const hasIframe = cardNumberContainer.querySelector('iframe') !== null;
            const renderedAt = angelleyeOrder.hostedFieldsRenderedAt || 0;
            const stillInFlight = (Date.now() - renderedAt) < 2000;
            if (hasIframe || stillInFlight) {
                return false;
            }
        }
        if (angelleyeOrder.isCCPaymentMethodSelected() === false) {
            angelleyeOrder.setPpcpCcSubmitHookReady(false);
            return false;
        }
        // At $0 cart the CC gateway renders WooCommerce's legacy plain-text
        // card form (see WC_Gateway_CC_AngellEYE::angelleye_ppcp_cc_form),
        // because the existing free-trial flow reads raw card data from
        // $_POST and forwards it to /v3/vault/setup-tokens to vault the
        // card for future renewals. Mounting SDK Card Fields iframes on
        // top of those same DOM ids would tokenize the card client-side
        // and break the submission. We let the checkout submit normally
        // and the server-side trait flow handles vaulting.
        if (parseFloat(angelleye_ppcp_manager.cart_total) <= 0) {
            angelleyeOrder.setPpcpCcSubmitHookReady(false);
            return false;
        }
        let spinnerSelectors = checkoutSelector;
        jQuery(checkoutSelector).addClass('CardFields');
        let errorLogId = null;
        let isItApiError = false;
        const cardFields = angelleye_paypal_sdk.CardFields({
            createOrder: function (data, actions) {
                isItApiError = false;
                jQuery('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
                if (!jQuery(checkoutSelector).hasClass('createOrder')) {
                    errorLogId = angelleyeJsErrorLogger.generateErrorId();
                    angelleyeJsErrorLogger.addToLog(errorLogId, 'Advanced CC Payment Started');
                    jQuery(checkoutSelector).addClass('createOrder');
                    return angelleyeOrder.createOrder({errorLogId}).then(function (data) {
                        angelleyeOrder.stopPpcpCcSubmitWatchdog();
                        return data.orderID;
                    }).catch((error) => {
                        angelleyeOrder.hideProcessingSpinner(spinnerSelectors);
                        angelleyeOrder.stopPpcpCcSubmitWatchdog();
                        isItApiError = true;
                        // Reset hosted-card submit state so user can retry createOrder on next click.
                        jQuery(checkoutSelector).removeClass('processing paypal_cc_submiting createOrder');
                        if (angelleyeOrder.ppcp_block_mode) {
                            // Stash the server-side message so onError can surface it to the
                            // Blocks subscriber instead of the generic SDK error.
                            angelleyeOrder.blockCreateOrderError = (typeof error === 'string')
                                ? error.replace(/<[^>]+>/g, '').trim()
                                : (error && error.message) || 'PayPal card payment failed';
                        } else {
                            angelleyeOrder.showError(error);
                        }
                        return '';
                    });
                }
            },
            onApprove: function (data, actions) {
                if (data.orderID) {
                    if (angelleyeOrder.ppcp_block_mode) {
                        // Blocks-native flow: hand the PayPal order id to the
                        // runBlocksPpcpCcFlow promise. The Blocks subscriber will
                        // then return SUCCESS with paymentMethodData, and the
                        // Store API /wc/store/v1/checkout POST will carry the id
                        // into process_payment() for capture.
                        jQuery(document.body).trigger('angelleye_ppcp_cc_approved', [{paypalOrderId: data.orderID}]);
                        return;
                    }
                    angelleyeOrder.checkoutFormCapture({checkoutSelector, payPalOrderId: data.orderID, errorLogId});
                }
            },
            onError: function (err) {
                // Ensure retry remains possible after any SDK/createOrder level error.
                jQuery(checkoutSelector).removeClass('processing paypal_cc_submiting createOrder');
                angelleyeOrder.stopPpcpCcSubmitWatchdog();
                angelleyeOrder.hideProcessingSpinner(spinnerSelectors);
                if (angelleyeOrder.ppcp_block_mode) {
                    const blockMessage = angelleyeOrder.blockCreateOrderError
                        || angelleyeOrder.parsePayPalSdkError(err);
                    jQuery(document.body).trigger('angelleye_ppcp_cc_error', [{message: blockMessage}]);
                    return;
                }
                if (!isItApiError) {
                    const errorMessage = angelleyeOrder.parsePayPalSdkError(err);
                    angelleyeOrder.showError(errorMessage);
                    angelleyeJsErrorLogger.logJsError(errorMessage, errorLogId);
                }
                console.log('Error occurred:', err);
                if (typeof err === 'object' && err !== null) {
                    console.log('Error message:', err.message || 'No error message available');
                    if (err.stack) {
                        console.log('Stack trace:', err.stack);
                    }
                } else {
                    console.log('Unexpected error format:', err);
                }
            },
            style: {
                'input': {
                    'font-size': angelleye_ppcp_manager.card_style_props.font_size,
                    'color': angelleye_ppcp_manager.card_style_props.color,
                    'font-weight': angelleye_ppcp_manager.card_style_props.font_weight,
                    'font-style': angelleye_ppcp_manager.card_style_props.font_style,
                    'padding': angelleye_ppcp_manager.card_style_props.padding,
                }
            },
            inputEvents: {
                onChange: function (data) {
                    if (data.cards && data.cards.length > 0) {
                        let cardname = data.cards[0].type.replace("master-card", "mastercard")
                                .replace("american-express", "amex")
                                .replace("diners-club", "dinersclub")
                                .replace("-", "");

                        if (jQuery.inArray(cardname, angelleye_ppcp_manager.disable_cards) !== -1) {
                            jQuery('#angelleye_ppcp_cc-card-number').addClass('ppcp-invalid-cart');
                            angelleyeOrder.showError(localizedMessages.card_not_supported);
                        } else {
                            jQuery('#angelleye_ppcp_cc-card-number').removeClass().addClass(cardname);
                        }
                    }
                }
            }
        });
        if (cardFields.isEligible()) {
            // On Blocks checkout the form element gets replaced after a
            // failed validation submit, so the `.CardFields` guard above
            // doesn't prevent a re-entry. Wipe any stale iframes left over
            // from the previous render before the SDK mounts new ones;
            // otherwise the customer sees each card field duplicated.
            jQuery('#angelleye_ppcp_cc-card-number, #angelleye_ppcp_cc-card-expiry, #angelleye_ppcp_cc-card-cvc').empty();
            // Claim this container node + stamp the time before the async
            // iframe mount starts, so any re-entrant renderHostedButtons
            // call bails on the guard above (during the in-flight window)
            // instead of mounting a duplicate set.
            angelleyeOrder.hostedFieldsContainer = document.getElementById('angelleye_ppcp_cc-card-number');
            angelleyeOrder.hostedFieldsRenderedAt = Date.now();
            cardFields.NumberField().render("#angelleye_ppcp_cc-card-number");
            cardFields.ExpiryField().render("#angelleye_ppcp_cc-card-expiry");
            cardFields.CVVField().render("#angelleye_ppcp_cc-card-cvc");
        } else {
            jQuery('.payment_method_angelleye_ppcp_cc').hide();
        }
        jQuery(document.body).off('submit_paypal_cc_form.angelleyePpcpCc').on('submit_paypal_cc_form.angelleyePpcpCc', (event) => {
            event.preventDefault();
            cardFields.getState().then((data) => {
                if (data.isFormValid) {
                    angelleyeOrder.showProcessingSpinner(spinnerSelectors);
                    cardFields.submit().then(() => {
                    }).catch((error) => {
                        angelleyeOrder.stopPpcpCcSubmitWatchdog();
                        console.log(error);
                    });
                } else if (!data.isFormValid) {
                    angelleyeOrder.stopPpcpCcSubmitWatchdog();
                    angelleyeOrder.hideProcessingSpinner();
                    jQuery(checkoutSelector).removeClass('processing paypal_cc_submiting CardFields createOrder');
                    if (angelleyeOrder.ppcp_block_mode) {
                        // context='card_invalid' signals the Blocks subscriber to
                        // display this notice next to the payment method block
                        // (where the card fields live) instead of at the top of
                        // the checkout form.
                        jQuery(document.body).trigger('angelleye_ppcp_cc_error', [{
                            message: localizedMessages.fields_not_valid,
                            context: 'card_invalid'
                        }]);
                        return;
                    }
                    angelleyeOrder.removeError();
                    angelleyeOrder.showError(localizedMessages.fields_not_valid);
                    return;
                } else if (data.errors) {
                    console.log(data);
                    data.errors.forEach(error => {
                        console.log(error);
                    });
                }
            }).catch((error) => {
                // A rejected getState() must not strand the checkout in the
                // processing state. Without this catch the spinner shown by
                // triggerPpcpCcSubmit() would stay on screen forever, since
                // the .then() cleanup never runs. Clear the spinner and submit
                // guards so the customer can retry.
                angelleyeOrder.stopPpcpCcSubmitWatchdog();
                angelleyeOrder.hideProcessingSpinner();
                jQuery(checkoutSelector).removeClass('processing paypal_cc_submiting CardFields createOrder');
                console.log('cardFields.getState() failed', error);
                if (angelleyeOrder.ppcp_block_mode) {
                    jQuery(document.body).trigger('angelleye_ppcp_cc_error', [{
                        message: localizedMessages.fields_not_valid,
                        context: 'card_invalid'
                    }]);
                } else {
                    angelleyeOrder.removeError();
                    angelleyeOrder.showError(localizedMessages.fields_not_valid);
                }
            });
        });
        angelleyeOrder.setPpcpCcSubmitHookReady(true);
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
        if (angelleyeOrder.isCardFieldEligible() === true) {
            jQuery('#angelleye_ppcp_cc-card-number iframe').length === 0 ? jQuery(angelleyeOrder.getCheckoutSelectorCss()).removeClass('CardFields') : null;
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
            // Off the cart page (e.g. a sliding cart on shop/archive/product pages) adding an
            // item fires added_to_cart rather than updated_cart_totals. Refresh the cached cart
            // totals from the angelleye_payments_data fragment so express buttons read the fresh
            // total instead of the stale page-load value, which otherwise surfaced a false
            // "your shopping cart seems to be empty" error on the Google Pay/Apple Pay button.
            jQuery(document.body).on('added_to_cart', function (event, fragments) {
                if (fragments && typeof fragments['angelleye_payments_data'] !== 'undefined') {
                    angelleyeOrder.updateCartTotalsInEnvironment(JSON.parse(fragments['angelleye_payments_data']));
                    angelleyeOrder.renderPaymentButtons();
                }
            });
        },
        handleRaceConditionOnWooHooks: () => {
            // trigger_angelleye_ppcp_cc is fired by the Blocks-checkout
            // Content_PPCP_CC React component's useEffect on mount. On first
            // page load it races the async PayPal SDK load: the real handler
            // in handleWooEvents() is only wired up AFTER the SDK finishes
            // loading (inside initSmartButtons), so without queuing this
            // event here the early trigger is lost and the express button /
            // hosted card fields never render until the user refreshes.
            jQuery(document.body).on('updated_cart_totals payment_method_selected updated_checkout ppcp_block_ready trigger_angelleye_ppcp_cc', function (event, data) {
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

const localizedMessages = ( function() {
    const { __ } = wp.i18n;
    return {
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
} )();

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
