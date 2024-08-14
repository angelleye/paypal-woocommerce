class PayPalFastlane {
    constructor(containerSelector) {
        this.containerSelector = containerSelector;
        this.fastlaneInstance = null;
        this.profileData = null;
        this.paymentToken = null;
    }

    async initialize() {
        this.fastlaneInstance = await angelleye_paypal_sdk.Fastlane({});
        this.fastlaneInstance.setLocale('en_us');
        this.bindEmailLookupEvent();
    }

    async lookupCustomerByEmail(email) {
        const { customerContextId } = await this.fastlaneInstance.identity.lookupCustomerByEmail(email);
        return customerContextId;
    }

    async authenticateCustomer(customerContextId) {
        const { authenticationState, profileData } = await this.fastlaneInstance.identity.triggerAuthenticationFlow(customerContextId);
        if (authenticationState === 'succeeded') {
            this.profileData = profileData;
            this.paymentToken = profileData.card ? profileData.card.id : null;
        }
        return authenticationState === 'succeeded';
    }

    renderCardDetails() {
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
    }

    bindChangeCardEvent() {
        $('#change-card').on('click', async () => {
            const { selectedCard } = await this.fastlaneInstance.profile.showCardSelector();
            if (selectedCard) {
                this.profileData.card = selectedCard;
                this.paymentToken = selectedCard.id;
                this.renderCardDetails();
            }
        });
    }

    async renderCardForm() {
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
    }

    bindPlaceOrderEvent(fastlaneCardComponent) {
        jQuery(document.body).on('submit_angelleye_ppcp_fastlane', async (event) => {
            const billingAddress = this.getBillingAddress();
            this.paymentToken = await fastlaneCardComponent.getPaymentToken({ billingAddress });
            $('#fastlane_payment_token').val(this.paymentToken);
            
        });
    }

    getBillingAddress() {
        return {
            addressLine1: $('#billing_address_1').val(),
            adminArea1: $('#billing_state').val(),
            adminArea2: $('#billing_city').val(),
            postalCode: $('#billing_postcode').val(),
            countryCode: $('#billing_country').val()
        };
    }

    bindEmailLookupEvent() {
        $('#lookup_ppcp_fastlane_email_button').on('click', async () => {
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
        });
    }

    render() {
        if (this.profileData && this.profileData.card) {
            this.renderCardDetails();
        } else {
            this.renderCardForm();
        }
    }
}

// Initialize the Fastlane class and call the initialize method

