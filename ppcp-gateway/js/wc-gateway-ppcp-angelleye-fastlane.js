class PayPalFastlane {
    constructor(containerSelector) {
        this.containerSelector = containerSelector;
        this.fastlaneInstance = null;
        this.profileData = null;
        this.paymentToken = null;
        this.savedCardHtml = ''; // Store the saved card HTML
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
            const {customerContextId} = await this.fastlaneInstance.identity.lookupCustomerByEmail(email);
            return customerContextId;
        } catch (error) {
            console.error("Error looking up customer by email:", error);
            return null;
        }
    }

    async authenticateCustomer(customerContextId) {
        try {
            const {authenticationState, profileData} = await this.fastlaneInstance.identity.triggerAuthenticationFlow(customerContextId);
            if (authenticationState === 'succeeded') {
                this.profileData = profileData;
                this.paymentToken = profileData.card?.id || null;
                this.updateWooCheckoutFields(profileData);
            }
            return authenticationState === 'succeeded';
        } catch (error) {
            console.error("Error authenticating customer:", error);
            return false;
        }
    }

    renderCardDetails() {
        if (this.profileData?.card) {
            this.savedCardHtml = `
                <div class="fastlane-card">
                    <div class="fastlane-card-number">•••• •••• •••• ${this.profileData.card.paymentSource.card.lastDigits}</div>
                    <div class="fastlane-card-expiry">${this.profileData.card.paymentSource.card.expiry}</div>
                    <button id="change-card">Change Card</button>
                </div>
            `;
            jQuery(this.containerSelector).html(this.savedCardHtml);
            this.bindChangeCardEvent();
        } else {
            this.renderCardForm();
        }
    }

    // Restore the saved card details after WooCommerce checkout updates
    restoreCardDetails() {
        if (this.savedCardHtml) {
            jQuery(this.containerSelector).html(this.savedCardHtml);
            this.bindChangeCardEvent();
        }
    }

    bindChangeCardEvent() {
        jQuery('#change-card').on('click', async () => {
            try {
                const {selectedCard} = await this.fastlaneInstance.profile.showCardSelector();
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
                        prefill: `${jQuery('#billing_first_name').val()} ${jQuery('#billing_last_name').val()}`,
                        enabled: true
                    },
                    phoneNumber: {
                        enabled: true,
                        prefill: jQuery('#billing_phone').val() || ''
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

                if (!jQuery(checkoutSelector).hasClass('createOrder')) {
                    let errorLogId = angelleyeJsErrorLogger.generateErrorId();
                    angelleyeJsErrorLogger.addToLog(errorLogId, 'Advanced CC Payment Started');
                    jQuery(checkoutSelector).addClass('createOrder');
                    await angelleyeOrder.createOrder({errorLogId});
                }
            } catch (error) {
                console.error("Failed to place order:", error);
                angelleyeOrder.showError(error);
            } finally {
                this.restoreCardDetails(); // Restore the card details after updating checkout
            }
        });
    }

    getBillingAddress() {
        return {
            addressLine1: jQuery('#billing_address_1').val(),
            adminArea1: jQuery('#billing_state').val(),
            adminArea2: jQuery('#billing_city').val(),
            postalCode: jQuery('#billing_postcode').val(),
            countryCode: jQuery('#billing_country').val()
        };
    }

    getShippingAddress() {
        return {
            addressLine1: jQuery('#shipping_address_1').val(),
            adminArea1: jQuery('#shipping_state').val(),
            adminArea2: jQuery('#shipping_city').val(),
            postalCode: jQuery('#shipping_postcode').val(),
            countryCode: jQuery('#shipping_country').val()
        };
    }

    updateWooCheckoutFields(profileData) {
        const updateField = (selector, value) => {
            if (value) {
                jQuery(selector).val(value).trigger('change');
            }
        };

        const billingAddress = profileData.card?.paymentSource?.card?.billingAddress || {};
        const shippingAddress = profileData.shippingAddress?.address || {};

        updateField('#billing_first_name', profileData.name?.firstName);
        updateField('#billing_last_name', profileData.name?.lastName);
        updateField('#billing_address_1', billingAddress.addressLine1);
        updateField('#billing_city', billingAddress.adminArea2);
        updateField('#billing_postcode', billingAddress.postalCode);
        updateField('#billing_country', billingAddress.countryCode);
        updateField('#billing_state', billingAddress.adminArea1);
        updateField('#billing_phone', profileData.shippingAddress?.phoneNumber?.nationalNumber);

        updateField('#shipping_first_name', profileData.shippingAddress?.name?.firstName);
        updateField('#shipping_last_name', profileData.shippingAddress?.name?.lastName);
        updateField('#shipping_address_1', shippingAddress.addressLine1);
        updateField('#shipping_city', shippingAddress.adminArea2);
        updateField('#shipping_postcode', shippingAddress.postalCode);
        updateField('#shipping_country', shippingAddress.countryCode);
        updateField('#shipping_state', shippingAddress.adminArea1);
    }

    bindEmailLookupEvent() {
        jQuery('#lookup_ppcp_fastlane_email_button').off('click').on('click', async (event) => {
            event.preventDefault();
            const button = jQuery('#lookup_ppcp_fastlane_email_button');
            button.prop('disabled', true);

            try {
                const email = jQuery('input[name="ppcp_fastlane_email"]').val();
                jQuery('input[name="billing_email"]').val(email);
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

                // Trigger WooCommerce checkout update and restore card details afterward
                jQuery(document.body).trigger('update_checkout');
                this.restoreCardDetails();

            } catch (error) {
                console.error("Error during email lookup event:", error);
            } finally {
                button.prop('disabled', false);
            }
        });
    }

    render() {
        if (this.profileData?.card) {
            this.renderCardDetails();
        } else {
            this.renderCardForm();
        }
    }
}
