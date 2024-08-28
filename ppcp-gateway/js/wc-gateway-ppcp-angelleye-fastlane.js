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
                    <div class="fastlane-card-expiry">Expires: ${this.profileData.card.paymentSource.card.expiry}</div>
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
            this.processEmailLookup().then(() => {
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
            angelleyeOrder.showProcessingSpinner();
            event.preventDefault();
            try {
                let paymentToken = this.paymentToken;
                if (!paymentToken) {
                    const billingAddress = this.getBillingAddress();
                    const shippingAddress = this.getShippingAddress();
                    if (!billingAddress || !shippingAddress) {
                        throw new Error("Billing or shipping address is missing.");
                    }

                    if (!fastlaneCardComponent) {
                        throw new Error("FastlaneCardComponent is not initialized.");
                    }
                    paymentToken = await fastlaneCardComponent.getPaymentToken({
                        billingAddress,
                        shippingAddress
                    });
                    this.paymentToken = paymentToken;
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
                        console.log(orderData);
                        if (orderData.redirected) {
                            window.location.href = orderData.url;
                        } else {
                            jQuery('.wc-block-components-checkout-place-order-button .wc-block-components-spinner').remove();
                            jQuery('.wc-block-components-checkout-place-order-button, .wp-block-woocommerce-checkout-fields-block #contact-fields, .wp-block-woocommerce-checkout-fields-block #billing-fields, .wp-block-woocommerce-checkout-fields-block #payment-method').unblock();
                            console.error("Failed to place order:", orderData.data.messages);
                            angelleyeOrder.showError("Failed to place order: " + orderData.data.messages);
                        }
                    });
                }
            } catch (error) {
                console.log(error);
                jQuery('.wc-block-components-checkout-place-order-button .wc-block-components-spinner').remove();
                angelleyeOrder.hideProcessingSpinner();
                angelleyeOrder.showError(error);
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


        // Classic WooCommerce Checkout Fields Update
        updateField('#billing_first_name', profileData.name?.firstName);
        updateField('#billing_last_name', profileData.name?.lastName);
        updateField('#billing_address_1', billingAddress.addressLine1);
        updateField('#billing_city', billingAddress.adminArea2);
        updateField('#billing_postcode', billingAddress.postalCode);
        updateField('#billing_country', billingAddress.countryCode);
        updateField('#billing_state', billingAddress.adminArea1);
        updateField('#billing_phone', profileData.shippingAddress?.phoneNumber?.nationalNumber);
        updateField('#billing_email', profileData.email);

        updateField('#shipping_first_name', profileData.shippingAddress?.name?.firstName);
        updateField('#shipping_last_name', profileData.shippingAddress?.name?.lastName);
        updateField('#shipping_address_1', shippingAddress.addressLine1);
        updateField('#shipping_city', shippingAddress.adminArea2);
        updateField('#shipping_postcode', shippingAddress.postalCode);
        updateField('#shipping_country', shippingAddress.countryCode);
        updateField('#shipping_state', shippingAddress.adminArea1);

        // Refresh checkout for classic checkout
        jQuery(document.body).trigger('custom_action_to_refresh_checkout', profileData);
        
        jQuery(document.body).trigger('trigger_angelleye_ppcp_fastlane');


        // AJAX Request to save the profile data (Same for both classic and block checkouts)
        jQuery.ajax({
            url: fastlane_object.ajaxurl,
            method: 'POST',
            data: {
                action: 'angelleye_ppcp_save_fastlane_data',
                profileData: profileData
            },
            success: function (response) {
                console.log(response);
                if (response.success) {
                    if (jQuery('#place_order').length) {
                        jQuery('html, body').animate({
                            scrollTop: (jQuery('#place_order').offset().top - 500)
                        }, 1000);
                    }
                    console.log('Checkout fields saved successfully.');
                } else {
                    console.log('Failed to save checkout fields.');
                }
            },
            error: function () {
                console.log('Error during AJAX request.');
            }
        });

        // Force WooCommerce to update the payment method selection

        this.setPaymentMethod(this.paymentMethodId);

    }

    setPaymentMethod(paymentMethodId) {
        const paymentMethod = jQuery(`#payment_method_${paymentMethodId}`);
        console.log('292');
        if (paymentMethod.length > 0) {
            console.log('294');
            paymentMethod.prop('checked', true);
            this.isPaymentMethodSet = true;
            jQuery('#payment_method_angelleye_ppcp_fastlane').trigger('click');
        } else {
            console.log('299');
            jQuery('#radio-control-wc-payment-method-options-angelleye_ppcp_fastlane').prop('checked', true);
            this.isPaymentMethodSet = true;
            jQuery('#radio-control-wc-payment-method-options-angelleye_ppcp_fastlane').parent('label').parent('div').trigger('click');
        }
    }

    async processEmailLookup() {
        const email = jQuery('input[name="fastlane-email"]').val();
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
        jQuery('.fastlane-submit-button').off('click').on('click', async (event) => {
            event.preventDefault();
            const button = jQuery('.fastlane-submit-button');
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
