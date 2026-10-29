/**
 * Apple Pay for the Cart and Checkout blocks: an express payment method, and a
 * payment method in the checkout list whose "Place order" button is Apple's.
 *
 * Apple only lets an ApplePaySession start from the buyer's own click on its
 * button, so Apple Pay can not wait for the standard "Place order" button.
 * The sheet, the shipping updates and confirmOrder() are the classic
 * ApplePayCheckoutButton flow; once PayPal has confirmed the payment,
 * onSubmit() runs the Blocks checkout, the onPaymentSetup subscriber hands it
 * the PayPal order id and the wallet addresses, and the Apple Pay gateway's
 * process_payment() captures.
 */
(function () {
    const {createElement, useEffect, useRef} = wp.element;
    const {registerExpressPaymentMethod, registerPaymentMethod} = wc.wcBlocksRegistry;
    const {getSetting} = wc.wcSettings;
    const {decodeEntities} = wp.htmlEntities;
    const {__} = wp.i18n;

    const settings = getSetting('angelleye_ppcp_apple_pay_data', {});
    const title = decodeEntities(settings.title || 'Apple Pay');
    const noticeId = 'angelleye-ppcp-apple-pay';

    const isApplePaySupported = () => {
        try {
            return typeof window.ApplePaySession !== 'undefined'
                    && window.ApplePaySession.supportsVersion(4)
                    && window.ApplePaySession.canMakePayments();
        } catch (e) {
            return false;
        }
    };

    // Error messages arrive as HTML; DOMParser reads the text without running
    // anything in it.
    const toText = (html) => {
        try {
            return new DOMParser().parseFromString(String(html), 'text/html').body.textContent.trim();
        } catch (e) {
            return String(html);
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

    // Shown only once the SDKs are loaded and PayPal reports Apple Pay
    // eligible. In Chrome, Edge and Firefox ApplePaySession only exists after
    // the Apple Pay SDK has loaded.
    const canMakePayment = () => new Promise((resolve) => {
        const timer = setTimeout(() => resolve(false), 15000);
        angelleyeOrder.onSdkReady(() => {
            if (!isApplePaySupported()) {
                clearTimeout(timer);
                resolve(false);
                return;
            }
            (new ApplePayCheckoutButton()).initApplePayConfig().then((config) => {
                clearTimeout(timer);
                resolve(!!(config && config.isEligible));
            }, () => {
                clearTimeout(timer);
                resolve(false);
            });
        });
    });

    /**
     * Renders the Apple Pay button into containerId and completes the Blocks
     * checkout once PayPal has confirmed the payment. getHandlers(propsRef)
     * returns the remaining ApplePayCheckoutButton handlers; the button
     * outlives renders, so they read the latest props from propsRef. With
     * useWalletAddress the addresses chosen in the sheet go to the checkout;
     * otherwise the checkout keeps the form's.
     */
    const useApplePayButton = (props, containerId, getHandlers, useWalletAddress) => {
        const propsRef = useRef(props);
        propsRef.current = props;
        const approvedRef = useRef(null);
        const {onPaymentSetup} = props.eventRegistration;

        useEffect(() => onPaymentSetup(() => {
            const approved = approvedRef.current;
            // The express button stays mounted, so this also runs when the
            // buyer checks out with another payment method.
            if (!approved) {
                return true;
            }
            approvedRef.current = null;
            let meta = {
                paymentMethodData: {
                    paypal_order_id: approved.orderID
                }
            };
            if (useWalletAddress) {
                meta.billingAddress = approved.billing;
                meta.shippingAddress = approved.shipping;
            }
            return {
                type: propsRef.current.emitResponse.responseTypes.SUCCESS,
                meta
            };
        }), [onPaymentSetup]);

        useEffect(() => {
            let mounted = true;
            angelleyeOrder.onSdkReady(() => {
                if (!mounted) {
                    return;
                }
                (new ApplePayCheckoutButton(Object.assign({
                    onApproved: (orderID, payment) => {
                        const shippingContact = payment.shippingContact || {};
                        const billing = toBlocksAddress(payment.billingContact, shippingContact);
                        approvedRef.current = {
                            orderID,
                            billing,
                            shipping: (shippingContact.addressLines || []).length ? toBlocksAddress(shippingContact) : billing
                        };
                        propsRef.current.onSubmit();
                    }
                }, getHandlers(propsRef)))).render('#' + containerId);
            });
            return () => {
                mounted = false;
            };
        }, []);
    };

    const ApplePayExpress = (props) => {
        const containerId = 'angelleye_ppcp_apple_pay_block';
        useApplePayButton(props, containerId, (propsRef) => ({
            onClick: () => {
                propsRef.current.setExpressPaymentError('');
                propsRef.current.onClick();
            },
            onCancel: () => {
                propsRef.current.onClose();
            },
            onError: (message) => {
                propsRef.current.setExpressPaymentError(toText(message));
                propsRef.current.onClose();
            }
        }), true);
        return createElement('div', {id: containerId});
    };

    // The checkout form's addresses, for the PayPal order; same as the card block.
    const getCheckoutAddress = () => {
        const customerData = wp.data.select(wc.wcBlocksData.CART_STORE_KEY).getCustomerData() || {};
        const checkoutStore = wp.data.select(wc.wcBlocksData.CHECKOUT_STORE_KEY);
        const shipping = customerData.shippingAddress || {};
        let billing = customerData.billingAddress || {};
        if (checkoutStore && typeof checkoutStore.getUseShippingAsBilling === 'function' && checkoutStore.getUseShippingAsBilling()) {
            billing = Object.assign({}, shipping, {
                email: billing.email || customerData.email || '',
                phone: billing.phone || shipping.phone || ''
            });
        }
        return {billing, shipping};
    };

    // Replaces "Place order" while Apple Pay is the selected payment method.
    // Like any checkout payment method it takes the address from the form, so
    // the order total is the one the checkout shows (the Store API rejects a
    // checkout whose total went up from the one shown).
    const ApplePayPlaceOrderButton = (props) => {
        const containerId = 'angelleye_ppcp_apple_pay_place_order';
        const validationRef = useRef(null);
        useApplePayButton(props, containerId, (propsRef) => ({
            useCheckoutAddress: true,
            onClick: () => {
                wp.data.dispatch('core/notices').removeNotice(noticeId, 'wc/checkout');
                angelleyeOrder.ppcp_address = getCheckoutAddress();
                // The sheet has to open inside the click, so the checkout form
                // is validated alongside it and checked before the PayPal order
                // is created.
                validationRef.current = propsRef.current.validate();
            },
            beforeCreateOrder: () => Promise.resolve(validationRef.current).then((result) => {
                if (result && result.hasError) {
                    throw new Error(__('Please complete all required fields before continuing.', 'paypal-for-woocommerce'));
                }
            }),
            onCancel: () => {},
            onError: (message) => {
                wp.data.dispatch('core/notices').createErrorNotice(toText(message), {id: noticeId, context: 'wc/checkout'});
            }
        }), false);
        // Full width, like the "Place order" button it replaces.
        return createElement('div', {
            id: containerId,
            style: Object.assign({width: '100%'}, props.disabled ? {opacity: 0.5, pointerEvents: 'none'} : {})
        });
    };

    const supports = {
        features: settings.supports || ['products']
    };

    registerExpressPaymentMethod({
        name: 'angelleye_ppcp_apple_pay_express',
        paymentMethodId: 'angelleye_ppcp_apple_pay',
        content: createElement(ApplePayExpress, null),
        edit: createElement('div', null, title),
        canMakePayment,
        supports
    });

    const description = decodeEntities(settings.description || '');
    registerPaymentMethod({
        name: 'angelleye_ppcp_apple_pay',
        label: createElement('span', {style: {width: '100%'}}, title,
                settings.icon ? createElement('img', {src: settings.icon, alt: title, style: {float: 'right', marginRight: '20px'}}) : null),
        ariaLabel: title,
        content: createElement('p', null, description),
        edit: createElement('p', null, description),
        placeOrderButton: ApplePayPlaceOrderButton,
        canMakePayment,
        supports
    });
    // Without placeOrderButton support (older WooCommerce) the method would
    // get the standard "Place order" button, which can not open the sheet;
    // leave Apple Pay to the express button there.
    const registered = wc.wcBlocksRegistry.getPaymentMethods()['angelleye_ppcp_apple_pay'];
    if (registered && !registered.placeOrderButton && wc.wcBlocksRegistry.__experimentalDeRegisterPaymentMethod) {
        wc.wcBlocksRegistry.__experimentalDeRegisterPaymentMethod('angelleye_ppcp_apple_pay');
    }
})();
