
class ApplePayCheckoutButton {
    static applePayConfig;
    static isConfigLoading;
    static configPromise;
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
        return angelleye_paypal_sdk.Applepay()
    }

    render(containerSelector) {
        if (!angelleyeOrder.isCheckoutPage()) {
            return;
        }
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
        let container = jQuery(containerSelector + '_apple_pay');
        console.log('rendering button', container);
        let applePayBtn = jQuery('<button type="button" id="apple-pay-btn" class="apple-pay-button apple-pay-button-black">Apple Pay</button>');
        applePayBtn.on('click', this.handleClickEvent);
        let seperatorApplePay = jQuery('<div class="angelleye_ppcp-proceed-to-checkout-button-separator">&mdash; OR &mdash;</div><br>');
        container.html(seperatorApplePay);
        container.append(applePayBtn);
    }

    async handleClickEvent(containerSelector) {

        angelleyeOrder.showProcessingSpinner();
        console.log('button clicked');
        let paymentRequest = {
            countryCode: ApplePayCheckoutButton.applePayConfig.countryCode,
            currencyCode: window.angelleye_cart_totals.currencyCode,
            merchantCapabilities: ApplePayCheckoutButton.applePayConfig.merchantCapabilities,
            supportedNetworks: ApplePayCheckoutButton.applePayConfig.supportedNetworks,
            requiredBillingContactFields: ["name", "phone", "email"],
            requiredShippingContactFields: [],
            total: {
                label: "Total Amount",
                amount: `${window.angelleye_cart_totals.totalAmount}`,
                type: "final",
            },
        }

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
                console.error(error);
                session.abort();
            });
        };

        session.onpaymentmethodselected = (event) => {
            session.completePaymentMethodSelection({
                newTotal: paymentRequest.total,
            });
        };

        session.onpaymentauthorized = async (event) => {
            try {
                console.log('payment event', event);
                // create the order to send a payment request
                let orderID = await angelleyeOrder.createOrder({angelleye_ppcp_button_selector: this.containerSelector}).then((orderData) => {
                    console.log('orderCreated', orderData);
                    return orderData.orderID;
                });

                /**
                 * Confirm Payment
                 */
                await ApplePayCheckoutButton.applePay().confirmOrder({ orderId: orderID, token: event.payment.token, billingContact: event.payment.billingContact , shippingContact: event.payment.shippingContact });

                await session.completePayment({
                    status: window.ApplePaySession.STATUS_SUCCESS,
                });

                angelleyeOrder.showProcessingSpinner();
                angelleyeOrder.approveOrder({orderID: orderID, payerID: ''});
            } catch (error) {
                console.error(error);
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
