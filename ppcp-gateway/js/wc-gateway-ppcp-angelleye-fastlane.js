class PayPalFastlane {
    constructor(containerSelector) {
        this.containerSelector = containerSelector;
        this.fastlaneInstance = null;
        this.profileData = null;
        this.paymentToken = null;
        this.savedCardHtml = ''; // Store the saved card HTML
        this.paymentMethodId = 'angelleye_ppcp_fastlane';
        this.isCardDetailsRestored = false; // Flag to prevent infinite loop
        this.isPaymentMethodSet = false; // Flag to prevent infinite loop
        this.fastlaneCardComponent = null; // Store the FastlaneCardComponent instance
    }

    async initialize() {
        try {
            this.fastlaneInstance = await angelleye_paypal_sdk.Fastlane({});
            this.fastlaneInstance.setLocale('en_us');
            this.bindEmailLookupEvent();
            this.bindWooCommerceEvents(); // Bind to WooCommerce events
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

    async renderCardDetails() {
        if (this.profileData?.card) {
            this.savedCardHtml = `
                <div id="paypal-fastlane-saved-card" class="fastlane-card">
                    <div class="fastlane-card-number">•••• •••• •••• ${this.profileData.card.paymentSource.card.lastDigits}</div>
                    <div class="fastlane-card-expiry">${this.profileData.card.paymentSource.card.expiry}</div>
                    <button id="change-card">Change Card</button>
                </div>
            `;
            jQuery(this.containerSelector).html(this.savedCardHtml);
            this.bindChangeCardEvent();

            // Initialize the FastlaneCardComponent to bind the place order event
            await this.initializeFastlaneCardComponent();
            this.bindPlaceOrderEvent(this.fastlaneCardComponent); // Bind the place order event
        } else {
            this.renderCardForm();
        }
    }

    async initializeFastlaneCardComponent() {
        try {
            if (!this.fastlaneCardComponent) {
                console.log("Initializing FastlaneCardComponent...");

                this.fastlaneCardComponent = await this.fastlaneInstance.FastlaneCardComponent({
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

                if (!this.fastlaneCardComponent) {
                    throw new Error("FastlaneCardComponent initialization failed.");
                }

                // Debugging: Confirm component initialization
                console.log("FastlaneCardComponent initialized:", this.fastlaneCardComponent);
            }
        } catch (error) {
            console.error("Error initializing FastlaneCardComponent:", error);
            throw error; // Rethrow to prevent further execution
        }
    }

    async renderCardForm() {
        try {
            await this.initializeFastlaneCardComponent();
            this.fastlaneCardComponent.render(this.containerSelector);
            this.bindPlaceOrderEvent(this.fastlaneCardComponent);
        } catch (error) {
            console.error("Error rendering card form:", error);
        }
    }

    restoreCardDetails() {
        const existingCardSection = jQuery('#paypal-fastlane-saved-card');

        if (!existingCardSection.length && this.savedCardHtml) {
            // Restore the saved card HTML if it's available
            jQuery(this.containerSelector).html(this.savedCardHtml);
            this.bindChangeCardEvent();
        } else if (!existingCardSection.length && !this.savedCardHtml) {
            // Only call processEmailLookup if both the saved card HTML and the existing card section are not present
            console.log("No saved card found, processing email lookup...");
            this.processEmailLookup().then(() => {
                // After processing, make sure to update the checkout fields if necessary
                this.updateWooCheckoutFields(this.profileData);
            });
        }
    }

    bindChangeCardEvent() {
        jQuery(document).on('click', '#change-card', async (event) => {
            event.preventDefault();  // Prevent the default behavior
            event.stopPropagation(); // Stop the event from bubbling up

            try {
                const {selectedCard} = await this.fastlaneInstance.profile.showCardSelector();
                if (selectedCard) {
                    this.profileData.card = selectedCard;
                    this.paymentToken = selectedCard.id;  // Set the paymentToken to the selected card's ID
                    this.renderCardDetails();  // Update the UI with the selected card
                }
            } catch (error) {
                console.error("Error changing card:", error);
            }
        });
    }

    bindPlaceOrderEvent(fastlaneCardComponent) {
        jQuery(document.body).off('submit_angelleye_ppcp_fastlane').on('submit_angelleye_ppcp_fastlane', async (event) => {
            event.preventDefault();
            try {
                let paymentToken = this.paymentToken; // Default to using the existing paymentToken

                // If no paymentToken exists, generate a new one using FastlaneCardComponent
                if (!paymentToken) {
                    const billingAddress = this.getBillingAddress();
                    const shippingAddress = this.getShippingAddress();

                    if (!billingAddress || !shippingAddress) {
                        throw new Error("Billing or shipping address is missing.");
                    }

                    if (!fastlaneCardComponent) {
                        throw new Error("FastlaneCardComponent is not initialized.");
                    }

                    // Generate a new payment token
                    console.log("Attempting to get payment token...");
                    paymentToken = await fastlaneCardComponent.getPaymentToken({
                        billingAddress,
                        shippingAddress
                    });

                    // Update the instance variable with the new token
                    this.paymentToken = paymentToken;
                    console.log("Generated new payment token:", paymentToken);
                } else {
                    console.log("Using existing payment token:", paymentToken);
                }

                if (!paymentToken) {
                    throw new Error("Failed to retrieve payment token.");
                }

                // Proceed with order creation
                let checkoutSelector = angelleyeOrder.getCheckoutSelectorCss();
                angelleyeOrder.createHiddenInputField({
                    fieldId: 'fastlane_payment_token',
                    fieldName: 'fastlane_payment_token',
                    fieldValue: paymentToken.id || paymentToken,
                    appendToSelector: checkoutSelector
                });

                if (!jQuery(checkoutSelector).hasClass('createOrder')) {
                    let errorLogId = angelleyeJsErrorLogger.generateErrorId();
                    angelleyeJsErrorLogger.addToLog(errorLogId, 'Fastlane Payment Started');
                    jQuery(checkoutSelector).addClass('createOrder');
                    await angelleyeOrder.createOrder({errorLogId}).then((orderData) => {
                        console.log('orderCreated', orderData);
                        if (orderData.redirected) {
                            window.location.href = orderData.url;
                        }
                    });
                }
            } catch (error) {
                console.error("Failed to place order:", error.message);
                angelleyeOrder.showError("Failed to place order: " + error.message);
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

        // Force WooCommerce to update the payment method selection
        this.setPaymentMethod(this.paymentMethodId);
    }

    setPaymentMethod(paymentMethodId) {
        const paymentMethod = jQuery(`#payment_method_${paymentMethodId}`);
        if (paymentMethod.length > 0) {
            paymentMethod.prop('checked', true);
            this.isPaymentMethodSet = true;
            jQuery('#payment_method_angelleye_ppcp_fastlane').trigger('click');
        }
    }

    async processEmailLookup() {
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
    }

    bindEmailLookupEvent() {
        jQuery('#lookup_ppcp_fastlane_email_button').off('click').on('click', async (event) => {
            event.preventDefault();
            const button = jQuery('#lookup_ppcp_fastlane_email_button');
            button.prop('disabled', true);
            try {
                await this.processEmailLookup();
                if (!this.isPaymentMethodSet) {
                    this.setPaymentMethod(this.paymentMethodId);
                }
            } catch (error) {
                console.error("Error during email lookup event:", error);
            } finally {
                button.prop('disabled', false);
            }
        });
    }

    bindWooCommerceEvents() {
        jQuery(document.body).on('updated_checkout', () => {
            this.isCardDetailsRestored = false; // Reset flag
            this.isPaymentMethodSet = false; // Reset flag
            this.restoreCardDetails();
            // Delay setting the payment method to ensure it does not cause an infinite loop
            setTimeout(() => {
                if (!this.isPaymentMethodSet) {
                    this.setPaymentMethod(this.paymentMethodId);
                }
                console.log(this);
            }, 200);
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
