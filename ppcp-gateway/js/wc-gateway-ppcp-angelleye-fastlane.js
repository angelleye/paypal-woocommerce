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
        try {
            if (this.profileData?.card) {
                jQuery(this.containerSelector).html(`
                    <div class="fastlane-card">
                        <div class="fastlane-card-number">•••• •••• •••• ${this.profileData.card.paymentSource.card.lastDigits}</div>
                        <div class="fastlane-card-expiry">${this.profileData.card.paymentSource.card.expiry}</div>
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
            }
        });
    }

    getBillingAddress() {
        try {
            return {
                addressLine1: jQuery('#billing_address_1').val(),
                adminArea1: jQuery('#billing_state').val(),
                adminArea2: jQuery('#billing_city').val(),
                postalCode: jQuery('#billing_postcode').val(),
                countryCode: jQuery('#billing_country').val()
            };
        } catch (error) {
            console.error("Error getting billing address:", error);
            return null;
        }
    }

    getShippingAddress() {
        try {
            return {
                addressLine1: jQuery('#shipping_address_1').val(),
                adminArea1: jQuery('#shipping_state').val(),
                adminArea2: jQuery('#shipping_city').val(),
                postalCode: jQuery('#shipping_postcode').val(),
                countryCode: jQuery('#shipping_country').val()
            };
        } catch (error) {
            console.error("Error getting shipping address:", error);
            return null;
        }
    }

    updateWooCheckoutFields(profileData) {
        try {
            // Update billing fields
            if (profileData.card && profileData.card.paymentSource.card.billingAddress) {
                jQuery('#billing_first_name').val(profileData.name.firstName);
                jQuery('#billing_last_name').val(profileData.name.lastName);
                jQuery('#billing_address_1').val(profileData.card.paymentSource.card.billingAddress.addressLine1);
                jQuery('#billing_city').val(profileData.card.paymentSource.card.billingAddress.adminArea2);
                jQuery('#billing_postcode').val(profileData.card.paymentSource.card.billingAddress.postalCode);
                jQuery('#billing_phone').val(profileData.shippingAddress.phoneNumber.nationalNumber || '');

                // Update the country field and trigger the WooCommerce event
                jQuery('#billing_country').val(profileData.card.paymentSource.card.billingAddress.countryCode).trigger('change');

                // Update the state field once the country has changed
                jQuery(document.body).on('country_to_state_changed', function () {
                    jQuery('#billing_state').val(profileData.card.paymentSource.card.billingAddress.adminArea1).trigger('change');
                });

                // Trigger WooCommerce's country to state change event manually
                jQuery('#billing_country').trigger('change');
            }

            // Update shipping fields
            if (profileData.shippingAddress) {
                jQuery('#shipping_first_name').val(profileData.shippingAddress.name.firstName);
                jQuery('#shipping_last_name').val(profileData.shippingAddress.name.lastName);
                jQuery('#shipping_address_1').val(profileData.shippingAddress.address.addressLine1);
                jQuery('#shipping_city').val(profileData.shippingAddress.address.adminArea2);
                jQuery('#shipping_postcode').val(profileData.shippingAddress.address.postalCode);
                jQuery('#shipping_country').val(profileData.shippingAddress.address.countryCode).trigger('change');

                jQuery(document.body).on('country_to_state_changed', function () {
                    jQuery('#shipping_state').val(profileData.shippingAddress.address.adminArea1).trigger('change');
                });

                jQuery('#shipping_country').trigger('change');
            }
        } catch (error) {
            console.error("Error updating WooCommerce checkout fields:", error);
        }
    }

    bindEmailLookupEvent() {
        jQuery('#lookup_ppcp_fastlane_email_button').off('click').on('click', async (event) => {
            event.preventDefault();
            const button = jQuery('#lookup_ppcp_fastlane_email_button');
            button.prop('disabled', true);
            var paymentMethod = jQuery('#payment_method_angelleye_ppcp_fastlane');
            if (paymentMethod.length > 0) {
                paymentMethod.prop('checked', true).trigger('change');
                jQuery(document.body).trigger('update_checkout');
            }
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
            } catch (error) {
                console.error("Error during email lookup event:", error);
            } finally {
                button.prop('disabled', false);
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
