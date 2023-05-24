
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
            if (ApplePayCheckoutButton.configPromise) {
                return ApplePayCheckoutButton.configPromise;
            }
            ApplePayCheckoutButton.configPromise = new Promise((resolve, reject) => {
                let configLoop = setInterval(() => {
                    if (ApplePayCheckoutButton.applePayConfig) {
                        resolve(ApplePayCheckoutButton.applePayConfig);
                        clearInterval(configLoop);
                    }
                }, 100);
            });
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
                    return;
                }
                this.renderButton(containerSelector);
            });
        } else {
            console.log('apple pay not supported');
        }
    }

    renderButton(containerSelector) {
        this.containerSelector = containerSelector;
        this.initProductCartPage();
        let container = jQuery(containerSelector + '_apple_pay');
        console.log('rendering button', container);
        let applePayBtn = jQuery('<button type="button" id="apple-pay-btn" class="apple-pay-button apple-pay-button-black">Apple Pay</button>');
        applePayBtn.on('click', {thisObject: this}, this.handleClickEvent);
        let seperatorApplePay = jQuery('<div class="angelleye_ppcp-proceed-to-checkout-button-separator">&mdash; OR &mdash;</div><br>');
        container.html(seperatorApplePay);
        container.append(applePayBtn);
    }

    initProductCartPage() {
        if (angelleyeOrder.isProductPage() || angelleyeOrder.isCartPage()) {
            window.angelleye_cart_totals = angelleye_ppcp_manager.product_cart_details;
        }
    }

    async handleClickEvent(event) {
        let containerSelector = event.data.thisObject.containerSelector;
        angelleyeOrder.showProcessingSpinner();
        angelleyeOrder.setPaymentMethodSelector('apple_pay');

        let shippingAddressRequired = [];
        if (window.angelleye_cart_totals.shippingRequired) {
            shippingAddressRequired = ["postalAddress", "name", "email"];
        }

        let paymentRequest = {
            countryCode: ApplePayCheckoutButton.applePayConfig.countryCode,
            currencyCode: window.angelleye_cart_totals.currencyCode,
            merchantCapabilities: ApplePayCheckoutButton.applePayConfig.merchantCapabilities,
            supportedNetworks: ApplePayCheckoutButton.applePayConfig.supportedNetworks,
            requiredBillingContactFields: ["name", "phone", "email", "postalAddress"],
            requiredShippingContactFields: shippingAddressRequired,
            total: {
                label: "Total Amount",
                amount: `${window.angelleye_cart_totals.totalAmount}`,
                type: "final",
            },
            lineItems: window.angelleye_cart_totals.lineItems,
            // TODO Adjust this section to work based on Subscription product
            recurringPaymentRequest: {
                paymentDescription: "A description of the recurring payment to display to the user in the payment sheet.",
                regularBilling: {
                    label: "Recurring",
                    amount: `${window.angelleye_cart_totals.totalAmount}`,
                    paymentTiming: "recurring",
                    recurringPaymentStartDate: new Date()
                },
                billingAgreement: "A localized billing agreement displayed to the user in the payment sheet prior to the payment authorization.",
                managementURL: "https://merchant.com/billingagreement1234",
                tokenNotificationURL: ApplePayCheckoutButton.applePayConfig.tokenNotificationURL
            },
        };
        console.log('paymentRequest', paymentRequest);

        let session = new ApplePaySession(4, paymentRequest);
        let parseErrorMessage = (errorObject) => {
            console.error(errorObject)
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
                session.abort();
            });
        };

        session.onpaymentmethodselected = (event) => {
            session.completePaymentMethodSelection({
                newTotal: paymentRequest.total,
            });
        };

        session.onshippingcontactselected = async (event) => {
            console.log('on shipping contact selected', event);
            let newTotal = {
                label: "Total Amount",
                amount: `${window.angelleye_cart_totals.totalAmount}`,
                type: "final",
            };

            try {
                let response = await angelleyeOrder.shippingAddressUpdate({shippingDetails: event.shippingContact});
                console.log('shipping update response', response);
                if (typeof response.totalAmount !== 'undefined') {
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
                    throw new Error("Unable to update the shipping amount.");
                }
            } catch (error) {
                let errorMessage = parseErrorMessage(error);
                angelleyeOrder.hideProcessingSpinner();
                angelleyeOrder.showError(errorMessage);
                session.completePayment({
                    status: ApplePaySession.STATUS_FAILURE,
                });
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
                let errorMessage = parseErrorMessage(error);
                angelleyeOrder.hideProcessingSpinner();
                angelleyeOrder.showError(errorMessage);
                session.completePayment({
                    status: ApplePaySession.STATUS_FAILURE,
                });
            }
        };

        session.oncancel  = (event) => {
            console.log("Apple Pay Cancelled !!", event)
            angelleyeOrder.hideProcessingSpinner();
        }

        session.begin();
    }
}
