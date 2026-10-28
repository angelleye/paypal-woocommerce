/**
 * Apple Pay for the Cart and Checkout blocks, as an express payment method.
 *
 * Apple only lets an ApplePaySession start from the buyer's own click, so the
 * button is Apple's and not the Blocks "Place order" button. The sheet, the
 * shipping updates and confirmOrder() are the classic ApplePayCheckoutButton
 * flow; once PayPal has confirmed the payment, onSubmit() runs the Blocks
 * checkout, the onPaymentSetup subscriber hands it the PayPal order id and the
 * wallet addresses, and the Apple Pay gateway's process_payment() captures.
 */
(function () {
    const {createElement, useEffect, useRef} = wp.element;
    const {registerExpressPaymentMethod} = wc.wcBlocksRegistry;
    const {getSetting} = wc.wcSettings;
    const {decodeEntities} = wp.htmlEntities;

    const settings = getSetting('angelleye_ppcp_apple_pay_data', {});
    const title = decodeEntities(settings.title || 'Apple Pay');
    const containerId = 'angelleye_ppcp_apple_pay_block';

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

    const ApplePayExpress = (props) => {
        // The button outlives renders, so its handlers read the latest props.
        const propsRef = useRef(props);
        propsRef.current = props;
        const approvedRef = useRef(null);
        const {onPaymentSetup} = props.eventRegistration;

        useEffect(() => onPaymentSetup(() => {
            const approved = approvedRef.current;
            // Express components stay mounted, so this also runs when the
            // buyer checks out with another payment method.
            if (!approved) {
                return true;
            }
            approvedRef.current = null;
            return {
                type: propsRef.current.emitResponse.responseTypes.SUCCESS,
                meta: {
                    paymentMethodData: {
                        paypal_order_id: approved.orderID
                    },
                    billingAddress: approved.billing,
                    shippingAddress: approved.shipping
                }
            };
        }), [onPaymentSetup]);

        useEffect(() => {
            angelleyeOrder.onSdkReady(() => {
                (new ApplePayCheckoutButton({
                    onClick: () => {
                        propsRef.current.setExpressPaymentError('');
                        propsRef.current.onClick();
                    },
                    onApproved: (orderID, payment) => {
                        const shippingContact = payment.shippingContact || {};
                        const billing = toBlocksAddress(payment.billingContact, shippingContact);
                        approvedRef.current = {
                            orderID,
                            billing,
                            shipping: (shippingContact.addressLines || []).length ? toBlocksAddress(shippingContact) : billing
                        };
                        propsRef.current.onSubmit();
                    },
                    onCancel: () => {
                        propsRef.current.onClose();
                    },
                    onError: (message) => {
                        propsRef.current.setExpressPaymentError(toText(message));
                        propsRef.current.onClose();
                    }
                })).render('#' + containerId);
            });
        }, []);

        return createElement('div', {id: containerId});
    };

    registerExpressPaymentMethod({
        name: 'angelleye_ppcp_apple_pay',
        paymentMethodId: 'angelleye_ppcp_apple_pay',
        content: createElement(ApplePayExpress, null),
        edit: createElement('div', null, title),
        canMakePayment,
        supports: {
            features: settings.supports || ['products']
        }
    });
})();
