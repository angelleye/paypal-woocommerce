
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
                    this.removeGooglePayPaymentMethod();
                });
        });
    }

    getGoogleIsReadyToPayRequest(allowedPaymentMethods) {
        return Object.assign({}, GooglePayCheckoutButton.baseRequest, {
            allowedPaymentMethods: allowedPaymentMethods,
        });
    }

    getGooglePaymentsClient(data) {
        console.log('onPaymentAuthorized', this.containerSelector, data);
        return new google.payments.api.PaymentsClient({
            environment: "TEST",
            paymentDataCallbacks: {
                // onPaymentDataChanged: this.onPaymentDataChanged,
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

    removeGooglePayPaymentMethod() {
        if (angelleyeOrder.isCheckoutPage()) {
            jQuery('.wc_payment_method.payment_method_angelleye_ppcp_google_pay').hide();
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

    onPaymentDataChanged(paymentData) {
        console.log('on payment changed', paymentData)
    }

    async processPayment(additionalData, paymentData) {
        try {
            console.log('processPayment', additionalData, paymentData);

            const { status } = await GooglePayCheckoutButton.googlePay().confirmOrder({
                orderId: additionalData.orderID,
                paymentMethodData: paymentData.paymentMethodData,
            });
            if (status === "APPROVED") {
                /* Capture the Order */
                angelleyeOrder.approveOrder({orderID: additionalData.orderID, payerID: ''});
                return { transactionState: "SUCCESS" };
            } else {
                return { transactionState: "ERROR" };
            }
        } catch (error) {
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

        let googlePayContainer = jQuery('<div class="google-pay-container"></div>');
        let thisObject = this;
        const paymentsClient = this.getGooglePaymentsClient();
        const button = paymentsClient.createButton({
            onClick: async (event) => {
                await thisObject.handleClickEvent(event, thisObject);
            }
        });
        googlePayContainer.append(button);

        if (!angelleyeOrder.isCheckoutPage()) {
            let separator = jQuery('<div class="angelleye_ppcp-proceed-to-checkout-button-separator">&mdash; OR &mdash;</div><br>');
            container.html(separator);
        }
        container.append(googlePayContainer);
    }

    initProductCartPage() {
        if (angelleyeOrder.isProductPage() || angelleyeOrder.isCartPage() || angelleyeOrder.isOrderPayPage()) {
            window.angelleye_cart_totals = angelleye_ppcp_manager.product_cart_details;
        }
    }

    getGoogleTransactionInfo() {
        return {
            // displayItems: window.angelleye_cart_totals.lineItems,
            // countryCode: "US",
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
        paymentDataRequest.callbackIntents = ["PAYMENT_AUTHORIZATION"];

        // paymentDataRequest.callbackIntents = ["PAYMENT_AUTHORIZATION", "SHIPPING_ADDRESS"];
        //paymentDataRequest.shippingAddressRequired = true;
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
                return orderData.orderID;
            });
        } catch (error) {
            angelleyeOrder.hideProcessingSpinner();
            angelleyeOrder.showError(error);
            return;
        }
        const paymentDataRequest = thisObject.getGooglePaymentDataRequest();
        const paymentsClient = thisObject.getGooglePaymentsClient({orderID});

        paymentsClient.loadPaymentData(paymentDataRequest).then((success) => {
            console.log('success', success);
        }, (e) => {
            angelleyeOrder.hideProcessingSpinner(thisObject.containerSelector);
            console.log('error handler click', e);
        });

    }
}
