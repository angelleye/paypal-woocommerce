class PayPalFastlane {
    constructor(containerSelector) {
        this.containerSelector = containerSelector;
        this.fastlaneInstance = null;
        this.profileData = null;
        this.paymentToken = null;
    }

    async initialize() {
        try {
            this.fastlaneInstance = await angelleye_paypal_sdk.Fastlane({});
            this.fastlaneInstance.setLocale('en_us');
            this.bindEmailLookupEvent();
        } catch (error) {
            console.error("Failed to initialize Fastlane:", error);
        }
    }

    async lookupCustomerByEmail(email) {
        try {
            const { customerContextId } = await this.fastlaneInstance.identity.lookupCustomerByEmail(email);
            return customerContextId;
        } catch (error) {
            console.error("Error looking up customer by email:", error);
            return null;
        }
    }

    async authenticateCustomer(customerContextId) {
        try {
            const { authenticationState, profileData } = await this.fastlaneInstance.identity.triggerAuthenticationFlow(customerContextId);
            if (authenticationState === 'succeeded') {
                this.profileData = profileData;
                this.paymentToken = profileData.card ? profileData.card.id : null;
                this.updateWooCheckoutFields(profileData);
            }
            return authenticationState === 'succeeded';
        } catch (error) {
            console.error("Error authenticating customer:", error);
            return false;
        }
    }

    renderCardDetails() {
        try {
            if (this.profileData && this.profileData.card) {
                $(this.containerSelector).html(`
                    <div class="fastlane-card">
                        <div class="fastlane-card-number">•••• •••• •••• ${this.profileData.card.last4}</div>
                        <div class="fastlane-card-expiry">${this.profileData.card.expiry}</div>
                        <button id="change-card">Change Card</button>
                    </div>
                `);
                this.bindChangeCardEvent();
            } else {
                this.renderCardForm();
            }
        } catch (error) {
            console.error("Error rendering card details:", error);
        }
    }

    bindChangeCardEvent() {
        $('#change-card').on('click', async () => {
            try {
                const { selectedCard } = await this.fastlaneInstance.profile.showCardSelector();
                if (selectedCard) {
                    this.profileData.card = selectedCard;
                    this.paymentToken = selectedCard.id;
                    this.renderCardDetails();
                }
            } catch (error) {
                console.error("Error changing card:", error);
            }
        });
    }

    async renderCardForm() {
        try {
            const fastlaneCardComponent = await this.fastlaneInstance.FastlaneCardComponent({
                fields: {
                    cardholderName: {
                        prefill: `${$('#billing_first_name').val()} ${$('#billing_last_name').val()}`,
                        enabled: true
                    }
                }
            });
            fastlaneCardComponent.render(this.containerSelector);
            this.bindPlaceOrderEvent(fastlaneCardComponent);
        } catch (error) {
            console.error("Error rendering card form:", error);
        }
    }

    bindPlaceOrderEvent(fastlaneCardComponent) {
        jQuery(document.body).on('submit_angelleye_ppcp_fastlane', async (event) => {
            event.preventDefault();
            try {
                const billingAddress = this.getBillingAddress();
                const shippingAddress = this.getShippingAddress();

                this.paymentToken = await fastlaneCardComponent.getPaymentToken({
                    billingAddress,
                    shippingAddress
                });

                let checkoutSelector = angelleyeOrder.getCheckoutSelectorCss();
                angelleyeOrder.createHiddenInputField({
                    fieldId: 'fastlane_payment_token',
                    fieldName: 'fastlane_payment_token',
                    fieldValue: this.paymentToken,
                    appendToSelector: checkoutSelector
                });

                if (jQuery(checkoutSelector).is('.createOrder') === false) {
                    let errorLogId = angelleyeJsErrorLogger.generateErrorId();
                    angelleyeJsErrorLogger.addToLog(errorLogId, 'Advanced CC Payment Started');
                    jQuery(checkoutSelector).addClass('createOrder');
                    await angelleyeOrder.createOrder({ errorLogId });
                }
            } catch (error) {
                console.error("Failed to place order:", error);
                angelleyeOrder.showError(error);
            }
        });
    }

    getBillingAddress() {
        try {
            return {
                addressLine1: $('#billing_address_1').val(),
                adminArea1: $('#billing_state').val(),
                adminArea2: $('#billing_city').val(),
                postalCode: $('#billing_postcode').val(),
                countryCode: $('#billing_country').val()
            };
        } catch (error) {
            console.error("Error getting billing address:", error);
            return null;
        }
    }

    getShippingAddress() {
        try {
            return {
                addressLine1: $('#shipping_address_1').val(),
                adminArea1: $('#shipping_state').val(),
                adminArea2: $('#shipping_city').val(),
                postalCode: $('#shipping_postcode').val(),
                countryCode: $('#shipping_country').val()
            };
        } catch (error) {
            console.error("Error getting shipping address:", error);
            return null;
        }
    }

    updateWooCheckoutFields(profileData) {
        try {
            if (profileData.billingAddress) {
                $('#billing_address_1').val(profileData.billingAddress.addressLine1);
                $('#billing_city').val(profileData.billingAddress.adminArea2);
                $('#billing_state').val(profileData.billingAddress.adminArea1);
                $('#billing_postcode').val(profileData.billingAddress.postalCode);
                $('#billing_country').val(profileData.billingAddress.countryCode);
            }

            if (profileData.shippingAddress) {
                $('#shipping_address_1').val(profileData.shippingAddress.addressLine1);
                $('#shipping_city').val(profileData.shippingAddress.adminArea2);
                $('#shipping_state').val(profileData.shippingAddress.adminArea1);
                $('#shipping_postcode').val(profileData.shippingAddress.postalCode);
                $('#shipping_country').val(profileData.shippingAddress.countryCode);
            }
        } catch (error) {
            console.error("Error updating WooCommerce checkout fields:", error);
        }
    }

    bindEmailLookupEvent() {
        $('#lookup_ppcp_fastlane_email_button').on('click', async () => {
            try {
                const email = $('input[name="ppcp_fastlane_email"]').val();
                const customerContextId = await this.lookupCustomerByEmail(email);
                if (customerContextId) {
                    const authenticated = await this.authenticateCustomer(customerContextId);
                    if (authenticated) {
                        this.renderCardDetails();
                    } else {
                        this.renderCardForm();
                    }
                } else {
                    this.renderCardForm();
                }
            } catch (error) {
                console.error("Error during email lookup event:", error);
            }
        });
    }

    render() {
        try {
            if (this.profileData && this.profileData.card) {
                this.renderCardDetails();
            } else {
                this.renderCardForm();
            }
        } catch (error) {
            console.error("Error during rendering:", error);
        }
    }
}