/**
 * Apple Pay for the Cart and Checkout blocks; see ppcp-wallet-block.js.
 */
(function () {
    const isApplePaySupported = () => {
        try {
            return typeof window.ApplePaySession !== 'undefined'
                    && window.ApplePaySession.supportsVersion(4)
                    && window.ApplePaySession.canMakePayments();
        } catch (e) {
            return false;
        }
    };

    // Apple contact -> Blocks address. Apple returns email and phone only on
    // the shipping contact, so they are taken from there for billing.
    const toBlocksAddress = (contact, fallback) => {
        contact = contact || {};
        fallback = fallback || {};
        const lines = contact.addressLines || [];
        return {
            first_name: contact.givenName || fallback.givenName || '',
            last_name: contact.familyName || fallback.familyName || '',
            company: '',
            address_1: lines[0] || '',
            address_2: lines[1] || '',
            city: contact.locality || '',
            state: (contact.administrativeArea || '').toUpperCase(),
            postcode: contact.postalCode || '',
            country: (contact.countryCode || '').toUpperCase(),
            email: contact.emailAddress || fallback.emailAddress || '',
            phone: contact.phoneNumber || fallback.phoneNumber || ''
        };
    };

    angelleyePpcpWalletBlock.register({
        gatewayId: 'angelleye_ppcp_apple_pay',
        settings: wc.wcSettings.getSetting('angelleye_ppcp_apple_pay_data', {title: 'Apple Pay'}),
        createButton: (handlers) => new ApplePayCheckoutButton(handlers),
        // In Chrome, Edge and Firefox ApplePaySession only exists after the
        // Apple Pay SDK has loaded, which isEligible() runs after.
        isEligible: () => {
            if (!isApplePaySupported()) {
                return false;
            }
            return (new ApplePayCheckoutButton()).initApplePayConfig().then((config) => !!(config && config.isEligible));
        },
        toBlocksAddresses: (payment) => {
            const shippingContact = payment.shippingContact || {};
            const billing = toBlocksAddress(payment.billingContact, shippingContact);
            return {
                billing,
                shipping: (shippingContact.addressLines || []).length ? toBlocksAddress(shippingContact) : billing
            };
        }
    });
})();
