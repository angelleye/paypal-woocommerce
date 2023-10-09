
class GooglePayCheckoutButton {
    static googlePayConfig;
    static isConfigLoading;
    static configPromise;
    static googlePayObject;
    static baseRequest = {
        apiVersion: 2,
        apiVersionMinor: 0,
    }
    constructor() {

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
        console.log('containerSelector', containerSelector)
        this.initGooglePayConfig().then(() => {
            let thisObj = this;
            let paymentsClient = this.getGooglePaymentsClient();
            let allowedPaymentMethods = GooglePayCheckoutButton.googlePayConfig.allowedPaymentMethods;
            paymentsClient.isReadyToPay(this.getGoogleIsReadyToPayRequest(allowedPaymentMethods))
                .then((response) => {
                    console.log('isReadyToPay', response);
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
        return window.angelleye_cart_totals && typeof window.angelleye_cart_totals.shippingRequired !== 'undefined' && window.angelleye_cart_totals.shippingRequired;
    }

    getGooglePaymentsClient(data) {
        console.log('onPaymentAuthorized', this.containerSelector, data);
        return new google.payments.api.PaymentsClient({
            environment: angelleye_ppcp_manager.sandbox_mode === '1' ? "TEST" : "PRODUCTION",
            paymentDataCallbacks: {
                onPaymentDataChanged: (this.isShippingRequired() ? this.onPaymentDataChanged.bind(null, {
                    thisObject: this,
                    orderID: data && data.orderID ? data.orderID : null
                }) : null),
                onPaymentAuthorized: this.onPaymentAuthorized.bind(null, {
                    thisObject: this,
                    orderID: data && data.orderID ? data.orderID : null
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

    onPaymentAuthorized(additionalData, paymentData) {
        // let thisObj = this;
        console.log('onPaymentAuthorized', additionalData, paymentData);
        let parseErrorMessage = (errorObject) => {
            console.error('parseErrorMessage', errorObject)
            console.log(JSON.stringify(errorObject));
            if (errorObject.name === 'PayPalApplePayError') {
                let debugID = errorObject.paypalDebugId;
                switch (errorObject.errorName) {
                    case 'ERROR_VALIDATING_MERCHANT':
                        return 'This merchant is not enabled to process apple pay. please contact website owner. [DebugId: ' + debugID + ']';
                    default:
                        return 'We are unable to process your request at the moment, please contact website owner. [DebugId: ' + debugID + ']'
                }
            }
            return errorObject;
        };

        return new Promise( (resolve, reject) => {
            angelleyeOrder.showProcessingSpinner();
            additionalData.thisObject.processPayment(additionalData, paymentData)
                .then(function (data) {
                    resolve({ transactionState: "SUCCESS" });
                })
                .catch(function (error) {
                    let errorMessage = parseErrorMessage(error);
                    angelleyeOrder.hideProcessingSpinner();
                    angelleyeOrder.showError(errorMessage);

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
                    let response = await angelleyeOrder.shippingAddressUpdate({shippingDetails: shippingDetails});
                    if (typeof response.totalAmount !== 'undefined') {
                        if (angelleyeOrder.isCheckoutPage()) {
                            // ship_to_different_address
                            jQuery('input[name="ship_to_different_address"]')
                        }
                        window.angelleye_cart_totals = response;
                        paymentDataRequestUpdate.newTransactionInfo = additionalData.thisObject.getGoogleTransactionInfo();
                    } else {
                        throw new Error("Unable to update the shipping amount.");
                    }
                } catch (error) {
                    angelleyeOrder.hideProcessingSpinner();
                    angelleyeOrder.showError(error);
                    paymentDataRequestUpdate.error = 'Unable to pull the shipping amount details based on selected address';
                    reject('Unable to pull the shipping amount details based on selected address');
                }
            }

            resolve(paymentDataRequestUpdate);
        });
    }

    async processPayment(additionalData, paymentData) {
        try {
            console.log('processPayment', additionalData, JSON.stringify(paymentData));

            const { status } = await GooglePayCheckoutButton.googlePay().confirmOrder({
                orderId: additionalData.orderID,
                paymentMethodData: paymentData.paymentMethodData,
            });
            if (status === "APPROVED") {
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
                    await angelleyeOrder.shippingAddressUpdate({shippingDetails}, {billingDetails});
                }
                /* Capture the Order */
                angelleyeOrder.approveOrder({orderID: additionalData.orderID, payerID: ''});
                return { transactionState: "SUCCESS" };
            } else {
                return { transactionState: "ERROR" };
            }
        } catch (error) {
            console.log('processPaymentError', error);
            angelleyeOrder.hideProcessingSpinner();
            angelleyeOrder.showError(error);

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
        this.initProductCartPage();
        let container = jQuery(containerSelector);
        container.html('');
        console.log('rendering google_pay button', containerSelector, container);

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

    initProductCartPage() {
        if (angelleyeOrder.isProductPage() || angelleyeOrder.isCartPage() || angelleyeOrder.isOrderPayPage()) {
            window.angelleye_cart_totals = angelleye_ppcp_manager.product_cart_details;
        }
    }

    getGoogleTransactionInfo() {
        let displayItems = [];
        for (let i = 0; i < (window.angelleye_cart_totals.lineItems).length; i++) {
            let type = "LINE_ITEM";
            let prodLabel = window.angelleye_cart_totals.lineItems[i].label;
            if (prodLabel.toLowerCase() === 'tax') {
                type = 'TAX';
            }
            displayItems.push({
                'label': window.angelleye_cart_totals.lineItems[i].label,
                'price': window.angelleye_cart_totals.lineItems[i].amount,
                'type': type,
            })
        }

        return {
            displayItems: displayItems,
            currencyCode: window.angelleye_cart_totals.currencyCode,
            totalPriceStatus: "FINAL",
            totalPrice: window.angelleye_cart_totals.totalAmount,
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
        return paymentDataRequest;
    }

    async handleClickEvent(event, thisObject) {
        console.log('click event', event, thisObject.containerSelector);

        if (window.angelleye_cart_totals.totalAmount <= 0) {
            angelleyeOrder.showError("Your shopping cart seems to be empty.");
        }
        angelleyeOrder.setPaymentMethodSelector('google_pay');
        let orderID;
        try {
            angelleyeOrder.showProcessingSpinner();
            /* Create Order */
            orderID = await angelleyeOrder.createOrder({
                angelleye_ppcp_button_selector: thisObject.containerSelector
            }).then((orderData) => {
                console.log('orderCreated', orderData);
                angelleye_ppcp_manager.product_cart_details = orderData;
                thisObject.initProductCartPage();
                return orderData.orderID;
            });
        } catch (error) {
            error = error.message;
            angelleyeOrder.hideProcessingSpinner();
            angelleyeOrder.showError(error);
            return;
        }
        const paymentDataRequest = thisObject.getGooglePaymentDataRequest();
        const paymentsClient = thisObject.getGooglePaymentsClient({orderID});

        paymentsClient.loadPaymentData(paymentDataRequest).then((success) => {
            console.log('success', success);
        }, (e) => {
            angelleyeOrder.triggerPaymentCancelEvent();
            angelleyeOrder.hideProcessingSpinner(thisObject.containerSelector);
            angelleyeOrder.hideProcessingSpinner();
            console.log('error handler click', e);
        });

    }
}
