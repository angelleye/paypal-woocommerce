/**
 * Google Pay for the Cart and Checkout blocks; see ppcp-wallet-block.js.
 */
(function () {
    // Google address -> Blocks address. Google gives the name as one string.
    const toBlocksAddress = (address, email) => {
        address = address || {};
        const names = (address.name || '').trim().split(/\s+/);
        return {
            first_name: names[0] || '',
            last_name: names.slice(1).join(' '),
            company: '',
            address_1: address.address1 || '',
            address_2: [address.address2, address.address3].filter(Boolean).join(' '),
            city: address.locality || '',
            state: (address.administrativeArea || '').toUpperCase(),
            postcode: address.postalCode || '',
            country: (address.countryCode || '').toUpperCase(),
            email: email || '',
            phone: address.phoneNumber || ''
        };
    };

    angelleyePpcpWalletBlock.register({
        gatewayId: 'angelleye_ppcp_google_pay',
        settings: wc.wcSettings.getSetting('angelleye_ppcp_google_pay_data', {title: 'Google Pay'}),
        createButton: (handlers) => new GooglePayCheckoutButton(handlers),
        isEligible: () => {
            const button = new GooglePayCheckoutButton();
            return button.initGooglePayConfig().then((config) => {
                if (!config || config.isEligible === false) {
                    return false;
                }
                return button.getGooglePaymentsClient()
                        .isReadyToPay(button.getGoogleIsReadyToPayRequest(config.allowedPaymentMethods))
                        .then((response) => !!(response && response.result));
            });
        },
        toBlocksAddresses: (paymentData) => {
            const info = (paymentData.paymentMethodData && paymentData.paymentMethodData.info) || {};
            const shippingAddress = paymentData.shippingAddress;
            const billing = toBlocksAddress(info.billingAddress || shippingAddress, paymentData.email);
            const shipping = shippingAddress ? toBlocksAddress(shippingAddress, paymentData.email) : billing;
            billing.phone = billing.phone || shipping.phone;
            return {billing, shipping};
        }
    });
})();
