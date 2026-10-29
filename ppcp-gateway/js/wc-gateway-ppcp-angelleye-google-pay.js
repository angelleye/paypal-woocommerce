
class GooglePayCheckoutButton {
    static googlePayConfig;
    static isConfigLoading;
    static configPromise;
    static googlePayObject;
    static baseRequest = {
        apiVersion: 2,
        apiVersionMinor: 0,
    }
    /**
     * @param {Object|null} blockHandlers Set when the button is rendered by the
     *     Blocks checkout: onClick(), onApproved(orderID, paymentData),
     *     onCancel(), onError(message), getCartDetails(cartDetails), the
     *     amounts from the Blocks cart, optionally beforeCreateOrder(), a
     *     promise that rejects to stop the payment, and useCheckoutAddress, to
     *     take the address from the checkout form instead of the sheet. The
     *     order is then completed by the Blocks checkout instead of
     *     approveOrder().
     */
    constructor(blockHandlers = null) {
        this.blockHandlers = blockHandlers;
    }

    getCartDetails() {
        return this.blockHandlers ? this.blockHandlers.getCartDetails(angelleyeOrder.getCartDetails()) : angelleyeOrder.getCartDetails();
    }

    showError(message) {
        if (this.blockHandlers) {
            angelleyeOrder.ppcp_block_wallet = null;
            this.blockHandlers.onError(message && typeof message === 'object' && message.message ? message.message : message);
        } else {
            angelleyeOrder.showError(message);
        }
    }

    /**
     * This function makes sure google pay config is loaded once for all the render calls.
     * @returns {Promise<*>}
     */
    async initGooglePayConfig() {
        if (GooglePayCheckoutButton.isConfigLoading) {
            if (!GooglePayCheckoutButton.configPromise) {
                GooglePayCheckoutButton.configPromise = new Promise((resolve, reject) => {
                    let configLoop = setInterval(() => {
                        if (GooglePayCheckoutButton.googlePayConfig) {
                            resolve(GooglePayCheckoutButton.googlePayConfig);
                            clearInterval(configLoop);
                        }
                    }, 100);
                });
            }
            return GooglePayCheckoutButton.configPromise;
        }
        if (GooglePayCheckoutButton.googlePayConfig) {
            return GooglePayCheckoutButton.googlePayConfig;
        }
        GooglePayCheckoutButton.isConfigLoading = true;
        GooglePayCheckoutButton.googlePayConfig = await GooglePayCheckoutButton.googlePay().config();
        return GooglePayCheckoutButton.googlePayConfig;
    }

    static googlePay() {
        if (!GooglePayCheckoutButton.googlePayObject) {
            GooglePayCheckoutButton.googlePayObject = angelleye_paypal_sdk.Googlepay();
        }
        return GooglePayCheckoutButton.googlePayObject;
    }

    render(containerSelector) {
        if (jQuery(containerSelector).length === 0) {
            return;
        }
        this.initGooglePayConfig().then(() => {
            let thisObj = this;
            let paymentsClient = this.getGooglePaymentsClient();
            let allowedPaymentMethods = GooglePayCheckoutButton.googlePayConfig.allowedPaymentMethods;
            paymentsClient.isReadyToPay(this.getGoogleIsReadyToPayRequest(allowedPaymentMethods))
                .then((response) => {
                    if (response.result) {
                        this.showGooglePayPaymentMethod();
                        this.renderButton(containerSelector);
                    }
                })
                .catch((err) => {
                    console.error('errorcatch',err);
                    this.removeGooglePayPaymentMethod(containerSelector);
                });
        });
    }

    getGoogleIsReadyToPayRequest(allowedPaymentMethods) {
        return Object.assign({}, GooglePayCheckoutButton.baseRequest, {
            allowedPaymentMethods: allowedPaymentMethods,
        });
    }

    isShippingRequired() {
        if (this.blockHandlers) {
            // The express button takes the address from the sheet, the checkout
            // payment method from the form.
            return !this.blockHandlers.useCheckoutAddress && !!this.getCartDetails().shippingRequired;
        }
        const cartDetails = angelleyeOrder.getCartDetails();
        return !angelleyeOrder.isCheckoutPage() && cartDetails && typeof cartDetails.shippingRequired !== 'undefined' && cartDetails.shippingRequired;
    }

    getGooglePaymentsClient(data) {
        return new google.payments.api.PaymentsClient({
            environment: angelleye_ppcp_manager.sandbox_mode === '1' ? "TEST" : "PRODUCTION",
            paymentDataCallbacks: {
                onPaymentDataChanged: (this.isShippingRequired() ? this.onPaymentDataChanged.bind(null, {
                    thisObject: this
                }) : null),
                onPaymentAuthorized: this.onPaymentAuthorized.bind(null, {
                    thisObject: this
                }),
            },
        });
    }

    showGooglePayPaymentMethod() {
        if (angelleyeOrder.isCheckoutPage()) {
            jQuery('.wc_payment_method.payment_method_angelleye_ppcp_google_pay').show();
        }
    }

    removeGooglePayPaymentMethod(containerSelector) {
        if (angelleyeOrder.isCheckoutPage()) {
            jQuery('.wc_payment_method.payment_method_angelleye_ppcp_google_pay').hide();
        }
        if (jQuery(containerSelector).length) {
            jQuery(containerSelector).remove();
        }
    }

    parseErrorMessage(errorObject) {
        console.log(errorObject, JSON.stringify(errorObject));
        if (errorObject.name === 'PayPalGooglePayError') {
            let debugID = errorObject.paypalDebugId;
            switch (errorObject.errorName) {
                case 'ERROR_VALIDATING_MERCHANT':
                    return localizedMessages.error_validating_merchant + ' [GooglePay DebugId:' + debugID + ']';
                //case 'UNPROCESSABLE_ENTITY':
                //    return JSON.stringify(errorObject);
                default:
                    return localizedMessages.general_error_message + ' [GooglePay DebugId:' + debugID + ']';
            }
        }
        return errorObject;
    }

    onPaymentAuthorized(additionalData, paymentData) {
        // let thisObj = this;
        console.log('onPaymentAuthorized', additionalData, paymentData);

        return new Promise( (resolve, reject) => {
            if (!additionalData.thisObject.blockHandlers) {
                angelleyeOrder.showProcessingSpinner();
            }
            additionalData.thisObject.processPayment(additionalData, paymentData)
                .then(function (data) {
                    resolve({ transactionState: "SUCCESS" });
                })
                .catch(function (error) {
                    let errorMessage = additionalData.thisObject.parseErrorMessage(error);
                    angelleyeOrder.hideProcessingSpinner();
                    additionalData.thisObject.showError(errorMessage);
                    resolve({ transactionState: "ERROR" });
                });
        });
    }

    onPaymentDataChanged(additionalData, intermediatePaymentData) {
        return new Promise(async function(resolve, reject) {
            console.log('on shipping changed', intermediatePaymentData);
            let shippingAddress = intermediatePaymentData.shippingAddress;
            let paymentDataRequestUpdate = {};

            if (intermediatePaymentData.callbackTrigger == "INITIALIZE" || intermediatePaymentData.callbackTrigger == "SHIPPING_ADDRESS") {

                try {
                    let shippingDetails = {
                        administrativeArea: shippingAddress.administrativeArea,
                        countryCode: shippingAddress.countryCode,
                        locality: shippingAddress.locality,
                        postalCode: shippingAddress.postalCode
                    };
                    let response = await angelleyeOrder.shippingAddressUpdate({shippingDetails: shippingDetails}, undefined, undefined, additionalData.thisObject.containerSelector);
                    if (typeof response.totalAmount !== 'undefined') {
                        angelleyeOrder.updateCartTotalsInEnvironment(response);
                        paymentDataRequestUpdate.newTransactionInfo = additionalData.thisObject.getGoogleTransactionInfo(response);
                    } else {
                        throw new Error(localizedMessages.shipping_amount_update_error);
                    }
                } catch (error) {
                    console.log('shipping change error');
                    angelleyeOrder.handleCreateOrderError(additionalData.thisObject.parseErrorMessage(error), additionalData.thisObject.errorLogId);
                    paymentDataRequestUpdate.error = localizedMessages.shipping_amount_pull_error;
                    reject(localizedMessages.shipping_amount_pull_error);
                }
            }

            resolve(paymentDataRequestUpdate);
        });
    }

    async processPayment(additionalData, paymentData) {
        try {
            console.log('processPayment', additionalData, JSON.stringify(paymentData));
            let thisObject = additionalData.thisObject;
            if (thisObject.blockHandlers) {
                if (thisObject.blockHandlers.beforeCreateOrder) {
                    await thisObject.blockHandlers.beforeCreateOrder();
                }
            } else {
                angelleyeOrder.showProcessingSpinner();
            }
            /* Create Order */
            let orderID = await angelleyeOrder.createOrder({
                angelleye_ppcp_button_selector: thisObject.containerSelector,
                errorLogId: additionalData.thisObject.errorLogId
            }).then((orderData) => {
                console.log('orderCreated', orderData);
                angelleyeOrder.updateCartTotalsInEnvironment(orderData);
                return orderData.orderID;
            });

            const { status } = await GooglePayCheckoutButton.googlePay().confirmOrder({
                orderId: orderID,
                paymentMethodData: paymentData.paymentMethodData,
            });
            if (status === "APPROVED" && thisObject.blockHandlers) {
                angelleyeOrder.ppcp_block_wallet = null;
                thisObject.blockHandlers.onApproved(orderID, paymentData);
                return { transactionState: "SUCCESS" };
            } else if (status === "APPROVED") {
                // check if the billing address details are available
                // Do not run this service on checkout page as user already provides all details there
                if (!angelleyeOrder.isCheckoutPage()) {
                    let billingDetails;
                    let shippingDetails = paymentData.shippingAddress;
                    if (paymentData.paymentMethodData && paymentData.paymentMethodData.info && paymentData.paymentMethodData.info.billingAddress) {
                        billingDetails = paymentData.paymentMethodData.info.billingAddress;
                    }
                    if (paymentData.email) {
                        if (!billingDetails) {
                            billingDetails = {};
                        }
                        billingDetails.emailAddress = paymentData.email;
                    }
                    await angelleyeOrder.shippingAddressUpdate({shippingDetails}, {billingDetails}, additionalData.thisObject.errorLogId);
                }
                /* Capture the Order */
                angelleyeOrder.approveOrder({orderID: orderID, payerID: ''});
                return { transactionState: "SUCCESS" };
            } else {
                if (thisObject.blockHandlers) {
                    thisObject.showError(localizedMessages.general_error_message);
                }
                return { transactionState: "ERROR" };
            }
        } catch (error) {
            console.log('processPaymentError', error);
            if (additionalData.thisObject.blockHandlers) {
                additionalData.thisObject.showError(additionalData.thisObject.parseErrorMessage(error));
            } else {
                angelleyeOrder.handleCreateOrderError(additionalData.thisObject.parseErrorMessage(error), additionalData.thisObject.errorLogId);
            }

            return {
                transactionState: "ERROR",
                error: {
                    message: error.message,
                },
            };
        }
    }

    renderButton(containerSelector) {
        this.containerSelector = containerSelector;
        let container = jQuery(containerSelector);
        container.html('');

        let buttonColor = 'default';
        let buttonType = 'plain';
        let containerStyle = '';
        if (typeof angelleye_ppcp_manager.google_pay_button_props !== 'undefined') {
            buttonColor = angelleye_ppcp_manager.google_pay_button_props.buttonColor;
            buttonType = angelleye_ppcp_manager.google_pay_button_props.buttonType;
            let height = angelleye_ppcp_manager.google_pay_button_props.height;
            height = height !== '' ? 'height: ' + height + 'px;' : '';
            containerStyle = height;
        }
        let googlePayContainer = jQuery('<div class="google-pay-container" style="'+(containerStyle != '' ? containerStyle  : '')+'"></div>');
        let thisObject = this;
        const paymentsClient = this.getGooglePaymentsClient();
        const button = paymentsClient.createButton({
            buttonColor: buttonColor,
            buttonType: buttonType,
            buttonSizeMode: 'fill',
            onClick: async (event) => {
                await thisObject.handleClickEvent(event, thisObject);
            }
        });
        googlePayContainer.append(button);

        // Remove the separator
        // if (!angelleyeOrder.isCheckoutPage()) {
        //     let separator = jQuery('<div class="angelleye_ppcp-proceed-to-checkout-button-separator">&mdash; OR &mdash;</div><br>');
        //     container.html(separator);
        // }
        container.append(googlePayContainer);
    }

    getGoogleTransactionInfo(cartDetails = this.getCartDetails()) {
        let displayItems = [];
        for (let i = 0; i < (cartDetails.lineItems).length; i++) {
            let type = "LINE_ITEM";
            let prodLabel = cartDetails.lineItems[i].label;
            if (prodLabel.toLowerCase() === 'tax') {
                type = 'TAX';
            }
            displayItems.push({
                'label': cartDetails.lineItems[i].label,
                'price': cartDetails.lineItems[i].amount,
                'type': type,
            })
        }

        return {
            displayItems: displayItems,
            currencyCode: cartDetails.currencyCode,
            totalPriceStatus: "FINAL",
            totalPrice: cartDetails.totalAmount,
            totalPriceLabel: "Total",
        };
    }

    getGooglePaymentDataRequest() {
        const paymentDataRequest = Object.assign({}, GooglePayCheckoutButton.baseRequest);
        paymentDataRequest.allowedPaymentMethods = GooglePayCheckoutButton.googlePayConfig.allowedPaymentMethods;
        paymentDataRequest.transactionInfo = this.getGoogleTransactionInfo();
        paymentDataRequest.merchantInfo = GooglePayCheckoutButton.googlePayConfig.merchantInfo;
        paymentDataRequest.emailRequired = true;
        paymentDataRequest.callbackIntents = ["PAYMENT_AUTHORIZATION"];
        if (this.isShippingRequired()) {
            paymentDataRequest.callbackIntents.push("SHIPPING_ADDRESS");
            paymentDataRequest.shippingAddressRequired = true;
        }
        if (this.blockHandlers && !this.blockHandlers.useCheckoutAddress) {
            // The Blocks express button has no checkout form to take the
            // billing address and phone from.
            paymentDataRequest.allowedPaymentMethods = paymentDataRequest.allowedPaymentMethods.map((method) => Object.assign({}, method, {
                parameters: Object.assign({}, method.parameters, {
                    billingAddressRequired: true,
                    billingAddressParameters: {format: 'FULL', phoneNumberRequired: true}
                })
            }));
            if (paymentDataRequest.shippingAddressRequired) {
                paymentDataRequest.shippingAddressParameters = {phoneNumberRequired: true};
            }
        }
        return paymentDataRequest;
    }

    async handleClickEvent(event, thisObject) {
        console.log('click event', event, thisObject.containerSelector);
        thisObject.errorLogId = angelleyeJsErrorLogger.generateErrorId();
        const blockHandlers = thisObject.blockHandlers;
        if (blockHandlers) {
            blockHandlers.onClick();
            angelleyeOrder.ppcp_block_wallet = 'angelleye_ppcp_google_pay';
        }
        const cartDetails = thisObject.getCartDetails();
        if (cartDetails.totalAmount <= 0) {
            thisObject.showError(localizedMessages.empty_cart_message);
        }
        angelleyeJsErrorLogger.addToLog(thisObject.errorLogId, 'Google Pay Payment Started');
        angelleyeOrder.setPaymentMethodSelector('google_pay');
        const paymentDataRequest = thisObject.getGooglePaymentDataRequest();
        const paymentsClient = thisObject.getGooglePaymentsClient({});

        paymentsClient.loadPaymentData(paymentDataRequest).then((success) => {
            console.log('success', success);
        }, (e) => {
            angelleyeOrder.triggerPaymentCancelEvent();
            angelleyeOrder.hideProcessingSpinner(thisObject.containerSelector);
            angelleyeOrder.hideProcessingSpinner();
            console.log('error handler click', e);
            if (blockHandlers) {
                angelleyeOrder.ppcp_block_wallet = null;
                if (e && e.statusCode && e.statusCode !== 'CANCELED') {
                    blockHandlers.onError(e.statusMessage || localizedMessages.general_error_message);
                } else {
                    blockHandlers.onCancel();
                }
            }
        });

    }
}
