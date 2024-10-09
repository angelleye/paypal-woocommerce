class PayPalFastlane {
    constructor(containerSelector) {
        this.containerSelector = '#angelleye_ppcp_checkout_fastlane';
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
            this.bindEmailLookupEvent(); // Calling the event binding
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
            this.bindChangeCardEvent();
            await this.initializeFastlaneCardComponent();
            this.bindPlaceOrderEvent(this.fastlaneCardComponent);
            const containerExists = jQuery('#angelleye_ppcp_checkout_fastlane').length > 0;
            if (!containerExists) {
                return;
            }
            this.savedCardHtml = `
                <div id="paypal-fastlane-saved-card" class="fastlane-card">
                    <div class="fastlane-card-number">•••• •••• •••• ${this.profileData.card.paymentSource.card.lastDigits}</div>
                    <div class="fastlane-card-expiry">Expires: ${this.profileData.card.paymentSource.card.expiry}</div>
                    <button id="change-card">Change Card</button>
                </div>
            `;
            jQuery('#angelleye_ppcp_checkout_fastlane').html(this.savedCardHtml);
            jQuery('#paypal-fastlane-saved-card').css('display', 'block');
        } else {
            this.renderCardForm();
        }
    }

    async initializeFastlaneCardComponent() {
        try {
            if (!this.fastlaneCardComponent) {
                const firstNameSelector = jQuery('#billing_first_name').length ? '#billing_first_name' : '#billing-first_name';
                const lastNameSelector = jQuery('#billing_last_name').length ? '#billing_last_name' : '#billing-last_name';
                const phoneSelector = jQuery('#billing_phone').length ? '#billing_phone' : '#billing-phone';
                const firstName = jQuery(firstNameSelector).val() ? jQuery(firstNameSelector).val().trim() : '';
                const lastName = jQuery(lastNameSelector).val() ? jQuery(lastNameSelector).val().trim() : '';
                const phoneNumber = jQuery(phoneSelector).val() ? jQuery(phoneSelector).val().trim() : '';
                const fields = {
                    cardholderName: {
                        enabled: true,
                        ...(firstName && lastName ? {prefill: `${firstName} ${lastName}`} : {})
                    },
                    phoneNumber: {
                        enabled: true,
                        ...(phoneNumber ? {prefill: phoneNumber} : {})
                    }
                };
                this.fastlaneCardComponent = await this.fastlaneInstance.FastlaneCardComponent({
                    fields: fields
                });

                if (!this.fastlaneCardComponent) {
                    throw new Error("FastlaneCardComponent initialization failed.");
                }
            }
        } catch (error) {
            throw error;
        }
    }

    async renderCardForm() {
        try {
            await this.initializeFastlaneCardComponent();
            this.fastlaneCardComponent.render('#angelleye_ppcp_checkout_fastlane');
            this.bindPlaceOrderEvent(this.fastlaneCardComponent);
        } catch (error) {
            console.error("Error rendering card form:", error);
        }
    }

    restoreCardDetails() {
        const existingCardSection = jQuery('#paypal-fastlane-saved-card');
        if (!existingCardSection.length && this.savedCardHtml) {
            jQuery('#angelleye_ppcp_checkout_fastlane').html(this.savedCardHtml);
            this.bindChangeCardEvent();
        } else if (!existingCardSection.length && !this.savedCardHtml) {
            this.processEmailLookup().then(() => {
                this.updateWooCheckoutFields(this.profileData);
            });
        }
    }

    bindChangeCardEvent() {
        jQuery(document).off('click', '#change-card');
        jQuery(document).on('click', '#change-card', async (event) => {
            event.preventDefault();
            event.stopPropagation();
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

    bindPlaceOrderEvent(fastlaneCardComponent) {
        jQuery(document.body).on('submit_angelleye_ppcp_fastlane', async (event) => {
            if (jQuery('#fastlane-email').length > 0 && jQuery('#fastlane-email').val().trim() === '') {
                jQuery('#fastlane-email').addClass('fastlane-input-error');
                angelleyeOrder.hideProcessingSpinner();
                angelleyeOrder.removeError();
                angelleyeOrder.showError('Email address is required in the Fastlane email field to continue.');
                throw new Error("Email address is required in the Fastlane email field to continue.");
            } else {
                jQuery('#fastlane-email').removeClass('fastlane-input-error');
            }

            console.log('fastlane submit');
            event.preventDefault();
            angelleyeOrder.showProcessingSpinner();

            try {
                let paymentToken = this.paymentToken;
                console.log('161', paymentToken);

                // Initialize billingAddress and shippingAddress with default values
                let billingAddress = this.getBillingAddress() || {
                    name: {
                        firstName: '',
                        lastName: ''
                    },
                    address: {
                        addressLine1: '',
                        addressLine2: '',
                        adminArea1: '',
                        adminArea2: '',
                        postalCode: '',
                        countryCode: ''
                    },
                    phoneNumber: {
                        countryCode: '',
                        nationalNumber: ''
                    },
                    email: ''
                };

                let shippingAddress = this.getShippingAddress() || {
                    name: {
                        firstName: '',
                        lastName: ''
                    },
                    address: {
                        addressLine1: '',
                        addressLine2: '',
                        adminArea1: '',
                        adminArea2: '',
                        postalCode: '',
                        countryCode: ''
                    },
                    phoneNumber: {
                        countryCode: '',
                        nationalNumber: ''
                    }
                };


                if (!paymentToken) {
                    console.log('163', paymentToken);

                    if (!fastlaneCardComponent) {
                        throw new Error("FastlaneCardComponent is not initialized.");
                    }

                    // Ensure shippingAddress falls back to billingAddress if needed
                    if (!shippingAddress.addressLine1) {
                        shippingAddress = {...billingAddress};
                    }

                    // Ensure billingAddress falls back to shippingAddress if needed
                    if (!billingAddress.addressLine1) {
                        billingAddress = {...shippingAddress};
                    }

                    console.log('for payment token billingAddress', billingAddress);
                    console.log('for payment token shippingAddress', shippingAddress);

                    paymentToken = await fastlaneCardComponent.getPaymentToken({
                        billingAddress,
                        shippingAddress
                    });

                    this.paymentToken = paymentToken;
                } else {
                    console.log("Using existing payment token:", paymentToken);

                    if (!fastlaneCardComponent) {
                        throw new Error("FastlaneCardComponent is not initialized.");
                    }

                    if (!shippingAddress.addressLine1) {
                        shippingAddress = {...billingAddress};
                    }

                    if (!billingAddress.addressLine1) {
                        billingAddress = {...shippingAddress};
                    }
                }

                if (!paymentToken) {
                    throw new Error("Failed to retrieve payment token.");
                }
                console.log('shippingAddress', shippingAddress);
                console.log('billingAddress', billingAddress);
                let checkoutSelector = angelleyeOrder.getCheckoutSelectorCss();
                angelleyeOrder.createHiddenInputField({
                    fieldId: 'fastlane_payment_token',
                    fieldName: 'fastlane_payment_token',
                    fieldValue: paymentToken.id || paymentToken,
                    appendToSelector: checkoutSelector
                });
                let errorLogId = angelleyeJsErrorLogger.generateErrorId();
                angelleyeJsErrorLogger.addToLog(errorLogId, 'Fastlane Payment Started');
                let billingDetails = {
                    first_name: billingAddress?.name?.firstName || '',
                    last_name: billingAddress?.name?.lastName || '',
                    address_1: billingAddress?.addressLine1 || '',
                    address_2: billingAddress?.addressLine2 || '', // Fallback if addressLine2 is missing
                    city: billingAddress?.adminArea2 || '',
                    state: billingAddress?.adminArea1 || '',
                    postcode: billingAddress?.postalCode || '',
                    country: billingAddress?.countryCode || ''
                };
                let shippingDetails = {
                    first_name: shippingAddress?.name?.firstName || '',
                    last_name: shippingAddress?.name?.lastName || '',
                    address_1: shippingAddress?.addressLine1 || '',
                    address_2: shippingAddress?.addressLine2 || '', // Fallback if addressLine2 is missing
                    city: shippingAddress?.adminArea2 || '',
                    state: shippingAddress?.adminArea1 || '',
                    postcode: shippingAddress?.postalCode || '',
                    country: shippingAddress?.countryCode || ''
                };
                let address = {
                    'billing': billingDetails,
                    'shipping': shippingDetails
                };
                angelleyeOrder.ppcp_address = [];
                angelleyeOrder.ppcp_address = address;
                console.log('billingDetails', billingDetails);
                console.log('shippingDetails', shippingDetails);
                await angelleyeOrder.createOrder({shippingDetails, billingDetails, errorLogId}).then((orderData) => {
                    if (orderData.redirected) {
                        window.location.href = orderData.url;
                    } else {
                        console.error("Failed to place order:", orderData.data.messages);
                        angelleyeOrder.showError("Failed to place order: " + orderData.data.messages);
                    }
                });

            } catch (error) {
                console.log('236', error);
                jQuery('.wc-block-components-checkout-place-order-button .wc-block-components-spinner').remove();
                angelleyeOrder.hideProcessingSpinner();
            }
        });
    }

    getBillingAddress() {
        let addressLine1 = jQuery('#billing_address_1').val();
        let addressLine2 = jQuery('#billing_address_2').val();
        let adminArea1 = jQuery('#billing_state').val();
        let adminArea2 = jQuery('#billing_city').val();
        let postalCode = jQuery('#billing_postcode').val();
        let countryCode = jQuery('#billing_country').val();
        let firstName = jQuery('#billing_first_name').val();
        let lastName = jQuery('#billing_last_name').val();
        let phoneNumber = jQuery('#billing_phone').val();
        let email = jQuery('#billing_email').val();

        // Fallback for different field selectors
        if (!addressLine1 && jQuery('#billing-address_1').length > 0) {
            const customerData = wp.data.select('wc/store/cart').getCustomerData();
            const { billingAddress } = customerData;
            addressLine1 = billingAddress.address_1;
            addressLine2 = billingAddress.address_2;
            adminArea1 = billingAddress.state;
            adminArea2 = billingAddress.city;
            postalCode = billingAddress.postcode;
            countryCode = billingAddress.country;
            firstName = billingAddress.first_name;
            lastName = billingAddress.last_name;
            phoneNumber = billingAddress.phone;
            email = billingAddress.email;
        }

        return {
            name: {
                firstName: firstName,
                lastName: lastName
            },
            address: {
                addressLine1: addressLine1,
                addressLine2: addressLine2,
                adminArea1: adminArea1,
                adminArea2: adminArea2,
                postalCode: postalCode,
                countryCode: countryCode
            },
            phoneNumber: {
                countryCode: countryCode,
                nationalNumber: phoneNumber
            },
            email: email
        };
    }

    getShippingAddress() {
        let addressLine1 = jQuery('#shipping_address_1').val();
        let addressLine2 = jQuery('#shipping_address_2').val();
        let adminArea1 = jQuery('#shipping_state').val();
        let adminArea2 = jQuery('#shipping_city').val();
        let postalCode = jQuery('#shipping_postcode').val();
        let countryCode = jQuery('#shipping_country').val();
        let firstName = jQuery('#shipping_first_name').val();
        let lastName = jQuery('#shipping_last_name').val();
        let phoneNumber = jQuery('#shipping_phone').val(); // If shipping phone is used

        // Fallback for different field selectors
        if (!addressLine1 && jQuery('#shipping-address_1').length > 0) {
            const customerData = wp.data.select('wc/store/cart').getCustomerData();
            const { billingAddress } = customerData;
            console.log(customerData);
            addressLine1 = shippingAddress.address_1;
            addressLine2 = shippingAddress.address_2;
            adminArea1 = shippingAddress.state;
            adminArea2 = shippingAddress.city;
            postalCode = shippingAddress.postcode;
            countryCode = shippingAddress.country;
            firstName = shippingAddress.first_name;
            lastName = shippingAddress.last_name;
            phoneNumber = shippingAddress.phone;
        }

        return {
            name: {
                firstName: firstName,
                lastName: lastName
            },
            address: {
                addressLine1: addressLine1,
                addressLine2: addressLine2,
                adminArea1: adminArea1,
                adminArea2: adminArea2,
                postalCode: postalCode,
                countryCode: countryCode
            },
            phoneNumber: {
                countryCode: countryCode,
                nationalNumber: phoneNumber
            }
        };
    }

    updateWooCheckoutFields(profileData) {
        if (!profileData || !profileData.card) {
            return;
        }
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
        updateField('#billing_address_2', billingAddress.addressLine2);
        updateField('#billing_city', billingAddress.adminArea2);
        updateField('#billing_postcode', billingAddress.postalCode);
        updateField('#billing_country', billingAddress.countryCode);
        updateField('#billing_state', billingAddress.adminArea1);
        updateField('#billing_phone', profileData.shippingAddress?.phoneNumber?.nationalNumber);
        updateField('#billing_email', profileData.email);
        updateField('#shipping_first_name', profileData.shippingAddress?.name?.firstName);
        updateField('#shipping_last_name', profileData.shippingAddress?.name?.lastName);
        updateField('#shipping_address_1', shippingAddress.addressLine1);
        updateField('#shipping_address_2', shippingAddress.addressLine2);
        updateField('#shipping_city', shippingAddress.adminArea2);
        updateField('#shipping_postcode', shippingAddress.postalCode);
        updateField('#shipping_country', shippingAddress.countryCode);
        updateField('#shipping_state', shippingAddress.adminArea1);
        jQuery(document.body).trigger('custom_action_to_refresh_checkout', profileData);
        jQuery.ajax({
            url: fastlane_object.ajaxurl,
            method: 'POST',
            data: {
                action: 'angelleye_ppcp_save_fastlane_data',
                profileData: profileData
            },
            success: function (response) {
                if (response.success) {
                    if (jQuery('#place_order').length) {
                        jQuery('html, body').animate({
                            scrollTop: (jQuery('#place_order').offset().top - 500)
                        }, 1000);
                    }
                }
            },
            error: function () {
                console.log('Error during AJAX request.');
            }
        });
        this.setPaymentMethod(this.paymentMethodId);
    }

    setPaymentMethod(paymentMethodId) {
        const paymentMethod = jQuery(`#payment_method_${paymentMethodId}`);
        if (paymentMethod.length > 0) {
            paymentMethod.prop('checked', true);
            this.isPaymentMethodSet = true;
            jQuery('#payment_method_angelleye_ppcp_fastlane').trigger('click');
        } else {
            jQuery('#radio-control-wc-payment-method-options-angelleye_ppcp_fastlane').prop('checked', true);
            this.isPaymentMethodSet = true;
            jQuery('#radio-control-wc-payment-method-options-angelleye_ppcp_fastlane').parent('label').parent('div').trigger('click');
        }
        angelleyeOrder.hideShowPlaceOrderButton();
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
                jQuery(document.body).trigger('custom_action_to_refresh_checkout_email');
                this.setPaymentMethod(this.paymentMethodId);
                this.renderCardForm();
                this.scrolltobottom();
            }
        } else {
            jQuery(document.body).trigger('custom_action_to_refresh_checkout_email');
            this.setPaymentMethod(this.paymentMethodId);
            this.renderCardForm();
            this.scrolltobottom();
        }
    }

    scrolltobottom() {
        if (jQuery('#place_order').length) {
            jQuery('html, body').animate({
                scrollTop: (jQuery('#place_order').offset().top - 500)
            }, 1000);
        }
    }

    bindEmailLookupEvent() {
        jQuery('.fastlane-submit-button').off('click');
        jQuery('.fastlane-submit-button').on('click', async (event) => {
            event.preventDefault();
            const emailInput = jQuery('#fastlane-email');
            const emailValue = emailInput.val().trim();
            if (!emailValue) {
                emailInput.addClass('fastlane-input-error');
                return;
            }
            emailInput.removeClass('fastlane-input-error');
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
                button.prop('disabled', false); // Re-enable the button
            }
        });
    }

    bindWooCommerceEvents() {
        jQuery(document.body).on('updated_checkout ppcp_fastlane_checkout_updated updated_cart_totals payment_method_selected', () => {
            var selectedpayment = $('input[name="payment_method"]:checked').val() || $('input[name="radio-control-wc-payment-method-options"]:checked').val();
            if (selectedpayment === 'angelleye_ppcp_fastlane') {
                this.isCardDetailsRestored = false;
                this.isPaymentMethodSet = false;
                this.restoreCardDetails();
                setTimeout(() => {
                    if (!this.isPaymentMethodSet) {
                        this.setPaymentMethod(this.paymentMethodId);
                    }
                }, 1000);
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
