
class ApplePayCheckoutButton {
    static applePayConfig;
    static isConfigLoading;
    static configPromise;
    static applePayObject;
    constructor() {

    }

    /**
     * This function makes sure apple pay config is loaded once for all the render calls.
     * @returns {Promise<*>}
     */
    async initApplePayConfig() {
        if (ApplePayCheckoutButton.isConfigLoading) {
            if (!ApplePayCheckoutButton.configPromise) {
                ApplePayCheckoutButton.configPromise = new Promise((resolve, reject) => {
                    let configLoop = setInterval(() => {
                        if (ApplePayCheckoutButton.applePayConfig) {
                            resolve(ApplePayCheckoutButton.applePayConfig);
                            clearInterval(configLoop);
                        }
                    }, 100);
                });
            }
            return ApplePayCheckoutButton.configPromise;
        }
        if (ApplePayCheckoutButton.applePayConfig) {
            return ApplePayCheckoutButton.applePayConfig;
        }
        ApplePayCheckoutButton.isConfigLoading = true;
        ApplePayCheckoutButton.applePayConfig = await ApplePayCheckoutButton.applePay().config();
        return ApplePayCheckoutButton.applePayConfig;
    }

    static applePay() {
        if (!ApplePayCheckoutButton.applePayObject) {
            ApplePayCheckoutButton.applePayObject = angelleye_paypal_sdk.Applepay();
        }
        return ApplePayCheckoutButton.applePayObject;
    }

    render(containerSelector) {
        if (typeof ApplePaySession !== 'undefined' && ApplePaySession?.supportsVersion(4) && ApplePaySession?.canMakePayments()) {
            this.initApplePayConfig().then(() => {
                if (!ApplePayCheckoutButton.applePayConfig.isEligible) {
                    // throw new Error('Apple Pay is not eligible.');
                    this.removeApplePayPaymentMethod();
                    return;
                }
                this.showApplePayPaymentMethod();
                this.renderButton(containerSelector);
            });
        } else {
            console.log('apple pay not supported');
            this.removeApplePayPaymentMethod(containerSelector);
        }
    }

    showApplePayPaymentMethod() {
        if (angelleyeOrder.isCheckoutPage()) {
            jQuery('.wc_payment_method.payment_method_angelleye_ppcp_apple_pay').show();
        }
    }

    removeApplePayPaymentMethod(containerSelector) {
        if (angelleyeOrder.isCheckoutPage()) {
            jQuery('.wc_payment_method.payment_method_angelleye_ppcp_apple_pay').hide();
        }
        if (jQuery(containerSelector).length) {
            jQuery(containerSelector).remove();
        }
    }

    renderButton(containerSelector) {
        this.containerSelector = containerSelector;
        this.initProductCartPage();
        let container = jQuery(containerSelector);
        container.html('');
        console.log('rendering apple_pay button', containerSelector, container);
        // let applePayBtn = jQuery('<button type="button" id="apple-pay-btn" class="apple-pay-button apple-pay-button-black">Apple Pay</button>');
        let buttonColor = 'black';
        let buttonType = 'plain';
        let containerStyle = '';
        if (typeof angelleye_ppcp_manager.apple_pay_button_props !== 'undefined') {
            buttonColor = angelleye_ppcp_manager.apple_pay_button_props.buttonColor;
            buttonType = angelleye_ppcp_manager.apple_pay_button_props.buttonType;
            let height = angelleye_ppcp_manager.apple_pay_button_props.height;
            height = height !== '' ? 'height: ' + height + 'px;' : '';
            containerStyle = height;
        }
        let applePayContainer = jQuery('<div class="apple-pay-container" style="'+(containerStyle !== '' ? containerStyle  : '')+'"></div>');

        let applePayBtn = jQuery('<apple-pay-button id="btn-appl" buttonstyle="' + buttonColor + '" type="' + buttonType + '" locale="en">');
        applePayBtn.on('click', {thisObject: this}, this.handleClickEvent);
        applePayContainer.append(applePayBtn);

        // Remove the separator
        // if (!angelleyeOrder.isCheckoutPage()) {
        //     let separatorApplePay = jQuery('<div class="angelleye_ppcp-proceed-to-checkout-button-separator">&mdash; OR &mdash;</div><br>');
        //     container.html(separatorApplePay);
        // }
        container.append(applePayContainer);
    }

    initProductCartPage() {
        // if (angelleyeOrder.isProductPage() || angelleyeOrder.isCartPage() || angelleyeOrder.isOrderPayPage()) {
        //     window.angelleye_cart_totals = angelleye_ppcp_manager.angelleye_cart_totals;
        // }
    }

    /**
     * Decides whether to ask Apple for a recurring merchant token or a one-time
     * token. angelleye_ppcp_is_apple_pay_recurring_token() in
     * angelleye-paypal-ppcp-common-functions.php mirrors this condition when it
     * builds payment_source.apple_pay - PayPal rejects confirmOrder() if the
     * order's stored_credential and the token disagree, so the two must move
     * together.
     */
    static addPaymentMethodSaveParams () {
        let isNewPaymentMethodSelected = jQuery('input#wc-angelleye_ppcp_apple_pay-new-payment-method:checked').val();
        const cartDetails = angelleyeOrder.getCartDetails();
        if (isNewPaymentMethodSelected === 'true' || cartDetails.isSubscriptionRequired) {
            return {
                recurringPaymentRequest: {
                    paymentDescription: angelleye_ppcp_manager.apple_pay_recurring_params.paymentDescription,
                    regularBilling: {
                        label: "Recurring",
                        amount: `${cartDetails.totalAmount}`,
                        paymentTiming: "recurring",
                        recurringPaymentStartDate: new Date()
                    },
                    billingAgreement: angelleye_ppcp_manager.apple_pay_recurring_params.billingAgreement,
                    managementURL: angelleye_ppcp_manager.apple_pay_recurring_params.managementURL,
                    tokenNotificationURL: ApplePayCheckoutButton.applePayConfig.tokenNotificationURL
                },
            }
        }
        return {};
    }

    async handleClickEvent(event) {
        let containerSelector = event.data.thisObject.containerSelector;
        const cartDetails = angelleyeOrder.getCartDetails();
        angelleyeOrder.showProcessingSpinner();
        angelleyeOrder.setPaymentMethodSelector('apple_pay');

        const errorLogId = angelleyeJsErrorLogger.generateErrorId();
        angelleyeJsErrorLogger.addToLog(errorLogId, 'Apple Pay Payment Started');

        // check if the saved payment method selected
        let isSavedPaymentMethodSelected = jQuery('input[name=wc-angelleye_ppcp_apple_pay-payment-token]:checked').val();
        console.log('isSavedPaymentMethodSelected', isSavedPaymentMethodSelected)
        if (isSavedPaymentMethodSelected !== 'new' && typeof isSavedPaymentMethodSelected !== 'undefined') {
            await ApplePayCheckoutButton.handleTokenPayment(event, errorLogId);
            return;
        }

        if (cartDetails.totalAmount <= 0) {
            angelleyeOrder.showError(localizedMessages.empty_cart_message);
            angelleyeOrder.hideProcessingSpinner();
            return;
        }

        let shippingAddressRequired = [];
        if (cartDetails.shippingRequired) {
            // "phone" belongs here as well as in the billing list below: without
            // it Apple returns a shipping contact with no phone number, the
            // checkout POST goes out with an empty shipping_phone, and any store
            // that marks Shipping Phone required rejects the order outright for
            // every buyer shipping to a different address.
            shippingAddressRequired = ["postalAddress", "name", "email", "phone"];
        }

        let subscriptionParams = ApplePayCheckoutButton.addPaymentMethodSaveParams();
        let paymentRequest = {
            countryCode: ApplePayCheckoutButton.applePayConfig.countryCode,
            currencyCode: cartDetails.currencyCode,
            merchantCapabilities: ApplePayCheckoutButton.applePayConfig.merchantCapabilities,
            supportedNetworks: ApplePayCheckoutButton.applePayConfig.supportedNetworks,
            requiredBillingContactFields: ["name", "phone", "email", "postalAddress"],
            requiredShippingContactFields: shippingAddressRequired,
            total: {
                label: localizedMessages.total_amount_placeholder,
                amount: `${cartDetails.totalAmount}`,
                type: "final",
            },
            lineItems: cartDetails.lineItems,
            ...subscriptionParams
        };
        console.log('paymentRequest', ApplePayCheckoutButton.applePayConfig, paymentRequest);

        let session = null;
        try {
             session = new ApplePaySession(4, paymentRequest);
        } catch (e) {
            console.log("ApplePay error session init error: ", e);
            angelleyeOrder.hideProcessingSpinner();
            angelleyeOrder.showError(localizedMessages.apple_pay_pay_error + '<br/>Error:' + e);
            angelleyeJsErrorLogger.logJsError(localizedMessages.apple_pay_pay_error + '<br/>Error:' + e, errorLogId);
            return;
        }

        // ApplePaySession completion calls are phase-specific: each is legal
        // only while the sheet is in the matching state, and every one of them
        // throws InvalidAccessError once the sheet has been dismissed or timed
        // out. Both showed up in production as a raw
        // "The object does not support the operation or argument." shown to the
        // buyer, so route every call through safeSessionCall() and track which
        // phase the sheet is in.
        let sessionClosed = false;
        let awaitingPaymentAuthorization = false;
        // True once the sheet has been repriced against the wallet address, so
        // the order must be built from it rather than the posted form fields.
        let walletAddressIsAuthoritative = false;

        let safeSessionCall = (label, fn) => {
            try {
                fn();
            } catch (e) {
                console.log('ApplePay session call failed: ' + label, e);
                angelleyeJsErrorLogger.addToLog(errorLogId, {
                    context: 'apple_pay_session_call_failed',
                    call: label,
                    message: e?.message,
                    time: new Date()
                });
            }
        };

        let paymentCancelled = (error) => {
            angelleyeOrder.triggerPaymentCancelEvent();
            angelleyeOrder.hideProcessingSpinner();
            if (error) {
                let errorMessage = parseErrorMessage(error);
                angelleyeOrder.showError(errorMessage);
                angelleyeJsErrorLogger.logJsError(errorMessage, errorLogId);

                // completePayment() is only legal once onpaymentauthorized has
                // fired. Calling it from the shipping-contact phase - which the
                // shipping update's catch used to do - throws, and the buyer is
                // left looking at a Safari internal error string.
                if (sessionClosed) {
                    return;
                }
                sessionClosed = true;
                if (awaitingPaymentAuthorization) {
                    safeSessionCall('completePayment', () => session.completePayment({
                        status: ApplePaySession.STATUS_FAILURE,
                    }));
                } else {
                    safeSessionCall('abort', () => session.abort());
                }
            }
        };

        let parseErrorMessage = (errorObject) => {
            console.error(errorObject)
            console.log(JSON.stringify(errorObject));
            // The buyer-facing sentence is deliberately generic, but it used to
            // be all we kept: errorName and message carry PayPal's actual
            // rejection reason and were dropped on the floor, so every distinct
            // failure landed in the log as the same contentless string plus a
            // debug id we could only redeem through PayPal support. Record the
            // detail on the trace before collapsing it for display.
            angelleyeJsErrorLogger.addToLog(errorLogId, {
                context: 'apple_pay_error',
                name: errorObject?.name,
                errorName: errorObject?.errorName,
                message: errorObject?.message,
                paypalDebugId: errorObject?.paypalDebugId,
                // Errors serialize to {} through JSON.stringify, so pull the
                // enumerable own properties across explicitly.
                details: (() => {
                    try {
                        return JSON.stringify(errorObject, Object.getOwnPropertyNames(Object(errorObject)));
                    } catch (e) {
                        return String(errorObject);
                    }
                })(),
                time: new Date()
            });
            if (errorObject.name === 'PayPalApplePayError') {
                let debugID = errorObject.paypalDebugId;
                switch (errorObject.errorName) {
                    case 'ERROR_VALIDATING_MERCHANT':
                        return localizedMessages.error_validating_merchant + ' [ApplePay DebugId:' + debugID + ']';
                    default:
                        return localizedMessages.general_error_message + ' [ApplePay DebugId:' + debugID + ']';
                }
            }
            // Anything else reached showError()/logJsError() as a raw object,
            // which renders as [object Object] and stringifies to {}.
            if (errorObject instanceof Error) {
                return errorObject.message;
            }
            return errorObject;
        };
        session.onvalidatemerchant = (event) => {
            ApplePayCheckoutButton.applePay().validateMerchant({
                validationUrl: event.validationURL,
            })
            .then((payload) => {
                safeSessionCall('completeMerchantValidation', () => session.completeMerchantValidation(payload.merchantSession));
            })
            .catch((error) => {
                angelleyeOrder.hideProcessingSpinner();
                let errorMessage = parseErrorMessage(error);
                angelleyeOrder.showError(errorMessage);
                angelleyeJsErrorLogger.logJsError(errorMessage, errorLogId);
                if (!sessionClosed) {
                    sessionClosed = true;
                    safeSessionCall('abort', () => session.abort());
                }
            });
        };

        session.onpaymentmethodselected = (event) => {
            safeSessionCall('completePaymentMethodSelection', () => session.completePaymentMethodSelection({
                newTotal: paymentRequest.total,
            }));
        };

        session.onshippingcontactselected = async (event) => {
            const cartDetails = angelleyeOrder.getCartDetails();
            console.log('on shipping contact selected', event);
            let newTotal = {
                label: localizedMessages.total_amount_placeholder,
                amount: `${cartDetails.totalAmount}`,
                type: "final",
            };

            try {
                let response = await angelleyeOrder.shippingAddressUpdate({shippingDetails: event.shippingContact}, undefined, undefined, containerSelector);
                console.log('shipping update response', response);
                if (typeof response.totalAmount !== 'undefined') {
                    angelleyeOrder.updateCartTotalsInEnvironment(response);
                    newTotal.amount = response.totalAmount;
                    let shippingContactUpdate = {
                        newTotal,
                        newLineItems: response.lineItems,
                        errors: [],
                    };
                    console.log('updating total amount', shippingContactUpdate);
                    Object.assign(paymentRequest, {
                        total: newTotal,
                        lineItems: response.lineItems
                    });
                    walletAddressIsAuthoritative = true;
                    safeSessionCall('completeShippingContactSelection', () => session.completeShippingContactSelection(shippingContactUpdate));
                } else {
                    throw new Error(localizedMessages.shipping_amount_update_error);
                }
            } catch (error) {
                paymentCancelled(error);
            }
        };

        session.onshippingmethodselected = async (event) => {
            console.log('on shipping method selected', event);
            let shippingMethodUpdate = {}
            safeSessionCall('completeShippingMethodSelection', () => session.completeShippingMethodSelection(shippingMethodUpdate));
        }

        session.onpaymentauthorized = async (event) => {
            awaitingPaymentAuthorization = true;
            try {
                console.log('paymentAuthorized', event);
                // create the order to send a payment request
                angelleyeOrder.setWalletAddressAuthoritative(walletAddressIsAuthoritative);
                let orderID = await angelleyeOrder.createOrder({
                    angelleye_ppcp_button_selector: containerSelector,
                    billingDetails: event.payment.billingContact,
                    shippingDetails: event.payment.shippingContact,
                    errorLogId
                }).then((orderData) => {
                    console.log('orderCreated', orderData);
                    // PayPal rejects confirmOrder() when the order total is not
                    // the total the buyer approved; say so rather than sending a
                    // confirm that is certain to fail.
                    if (typeof orderData.totalAmount !== 'undefined'
                            && `${orderData.totalAmount}` !== `${paymentRequest.total.amount}`) {
                        angelleyeJsErrorLogger.addToLog(errorLogId, {
                            context: 'apple_pay_total_mismatch',
                            sheetTotal: paymentRequest.total.amount,
                            orderTotal: orderData.totalAmount,
                            orderID: orderData.orderID,
                            time: new Date()
                        });
                        angelleyeOrder.updateCartTotalsInEnvironment(orderData);
                        throw new Error(localizedMessages.apple_pay_amount_changed_error);
                    }
                    return orderData.orderID;
                });

                /**
                 * Confirm Payment
                 */
                await ApplePayCheckoutButton.applePay().confirmOrder({ orderId: orderID, token: event.payment.token, billingContact: event.payment.billingContact, shippingContact: event.payment.shippingContact });

                sessionClosed = true;
                safeSessionCall('completePayment', () => session.completePayment({
                    status: ApplePaySession.STATUS_SUCCESS,
                }));
                angelleyeOrder.approveOrder({orderID: orderID, payerID: ''});
            } catch (error) {
                paymentCancelled(error);
            }
        };

        session.oncancel  = (event) => {
            console.log("Apple Pay Cancelled !!", event)
            sessionClosed = true;
            paymentCancelled();
        }

        session.begin();
    }

    static async handleTokenPayment(event, errorLogId) {
        let containerSelector = event.data.thisObject.containerSelector;
        // create the order to send a payment request
        angelleyeOrder.createOrder({
            angelleye_ppcp_button_selector: containerSelector,
            callback: () => {},
            errorLogId
        }).catch((error) => {
            angelleyeOrder.handleCreateOrderError(error, errorLogId);
        });
    }
}
