
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
        let paymentRequest = {
            countryCode: ApplePayCheckoutButton.applePayConfig.countryCode,
            currencyCode: window.angelleye_cart_totals.currencyCode,
            merchantCapabilities: ApplePayCheckoutButton.applePayConfig.merchantCapabilities,
            supportedNetworks: ApplePayCheckoutButton.applePayConfig.supportedNetworks,
            requiredBillingContactFields: ["name", "phone", "email", "postalAddress"],
            requiredShippingContactFields: ["postalAddress", "name", "email"],
            total: {
                label: "Total Amount",
                amount: `${window.angelleye_cart_totals.totalAmount}`,
                type: "final",
            },
        };

        let session = new ApplePaySession(4, paymentRequest);

        session.onvalidatemerchant = (event) => {
            ApplePayCheckoutButton.applePay().validateMerchant({
                validationUrl: event.validationURL,
            })
            .then((payload) => {
                session.completeMerchantValidation(payload.merchantSession);
            })
            .catch((error) => {
                angelleyeOrder.hideProcessingSpinner();
                angelleyeOrder.showError(error);
                console.log(error);
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
            const newTotal = {
                label: "Total Amount",
                amount: `${window.angelleye_cart_totals.totalAmount}`,
                type: "final",
            };

            let shippingContactUpdate = {
                newTotal,
                errors: [],
            };
            session.completeShippingContactSelection(shippingContactUpdate);
        };

        session.onshippingmethodselected = async (event) => {
            console.log('on shipping method selected', event);
            let shippingMethodUpdate = {}
            session.completeShippingMethodSelection(shippingMethodUpdate);
        }

        session.onpaymentauthorized = async (event) => {
            try {
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
                    status: window.ApplePaySession.STATUS_SUCCESS,
                });
                angelleyeOrder.approveOrder({orderID: orderID, payerID: ''});
            } catch (error) {
                // TODO Handle PayPalApplePayError codes
                console.log(error);
                angelleyeOrder.hideProcessingSpinner();
                angelleyeOrder.showError(error);
                session.completePayment({
                    status: window.ApplePaySession.STATUS_FAILURE,
                });
            }
        };

        session.oncancel  = (event) => {
            console.log("Apple Pay Cancelled !!")
            angelleyeOrder.hideProcessingSpinner();
        }

        session.begin();
    }
}
