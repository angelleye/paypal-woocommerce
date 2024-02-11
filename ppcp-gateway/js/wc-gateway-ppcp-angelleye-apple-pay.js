
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
        }

        let shippingAddressRequired = [];
        if (cartDetails.shippingRequired) {
            shippingAddressRequired = ["postalAddress", "name", "email"];
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

        let paymentCancelled = (error) => {
            angelleyeOrder.triggerPaymentCancelEvent();
            angelleyeOrder.hideProcessingSpinner();
            if (error) {
                let errorMessage = parseErrorMessage(error);
                angelleyeOrder.showError(errorMessage);
                angelleyeJsErrorLogger.logJsError(errorMessage, errorLogId);

                session.completePayment({
                    status: ApplePaySession.STATUS_FAILURE,
                });
            }
        };

        let parseErrorMessage = (errorObject) => {
            console.error(errorObject)
            console.log(JSON.stringify(errorObject));
            if (errorObject.name === 'PayPalApplePayError') {
                let debugID = errorObject.paypalDebugId;
                switch (errorObject.errorName) {
                    case 'ERROR_VALIDATING_MERCHANT':
                        return localizedMessages.error_validating_merchant + ' [ApplePay DebugId:' + debugID + ']';
                    default:
                        return localizedMessages.general_error_message + ' [ApplePay DebugId:' + debugID + ']';
                }
            }
            return errorObject;
        };
        session.onvalidatemerchant = (event) => {
            ApplePayCheckoutButton.applePay().validateMerchant({
                validationUrl: event.validationURL,
            })
            .then((payload) => {
                session.completeMerchantValidation(payload.merchantSession);
            })
            .catch((error) => {
                angelleyeOrder.hideProcessingSpinner();
                let errorMessage = parseErrorMessage(error);
                angelleyeOrder.showError(errorMessage);
                angelleyeJsErrorLogger.logJsError(errorMessage, errorLogId);
                session.abort();
            });
        };

        session.onpaymentmethodselected = (event) => {
            session.completePaymentMethodSelection({
                newTotal: paymentRequest.total,
            });
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
                let response = await angelleyeOrder.shippingAddressUpdate({shippingDetails: event.shippingContact});
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
                    session.completeShippingContactSelection(shippingContactUpdate);
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
            session.completeShippingMethodSelection(shippingMethodUpdate);
        }

        session.onpaymentauthorized = async (event) => {
            try {
                console.log('paymentAuthorized', event);
                // create the order to send a payment request
                let orderID = await angelleyeOrder.createOrder({
                    angelleye_ppcp_button_selector: containerSelector,
                    billingDetails: event.payment.billingContact,
                    shippingDetails: event.payment.shippingContact,
                    errorLogId
                }).then((orderData) => {
                    console.log('orderCreated', orderData);
                    return orderData.orderID;
                });

                /**
                 * Confirm Payment
                 */
                await ApplePayCheckoutButton.applePay().confirmOrder({ orderId: orderID, token: event.payment.token, billingContact: event.payment.billingContact, shippingContact: event.payment.shippingContact });

                await session.completePayment({
                    status: ApplePaySession.STATUS_SUCCESS,
                });
                angelleyeOrder.approveOrder({orderID: orderID, payerID: ''});
            } catch (error) {
                paymentCancelled(error);
            }
        };

        session.oncancel  = (event) => {
            console.log("Apple Pay Cancelled !!", event)
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
