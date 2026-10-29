/**
 * Shared Blocks registration for the wallets (Apple Pay, Google Pay): an
 * express payment method, and a payment method in the checkout list whose
 * "Place order" button is the wallet's.
 *
 * A wallet sheet only opens from the buyer's own click on the wallet's
 * button, so it can not wait for the standard "Place order" button. The sheet,
 * its shipping updates and confirmOrder() are the classic button class's
 * flow; once PayPal has confirmed the payment, onSubmit() runs the Blocks
 * checkout, the onPaymentSetup subscriber hands it the PayPal order id (and,
 * for the express button, the wallet addresses), and the gateway's
 * process_payment() captures.
 */
window.angelleyePpcpWalletBlock = (function () {
    const {createElement, useEffect, useRef} = wp.element;
    const {decodeEntities} = wp.htmlEntities;
    const {__} = wp.i18n;

    // Error messages arrive as HTML; DOMParser reads the text without running
    // anything in it.
    const toText = (html) => {
        try {
            return new DOMParser().parseFromString(String(html), 'text/html').body.textContent.trim();
        } catch (e) {
            return String(html);
        }
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

    /**
     * Renders the wallet button into containerId and completes the Blocks
     * checkout once PayPal has confirmed the payment. getHandlers(propsRef)
     * returns the remaining button handlers; the button outlives renders, so
     * they read the latest props from propsRef. With useWalletAddress the
     * addresses chosen in the sheet go to the checkout; otherwise the checkout
     * keeps the form's.
     */
    const useWalletButton = (wallet, props, containerId, getHandlers, useWalletAddress) => {
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
                wallet.createButton(Object.assign({
                    onApproved: (orderID, payment) => {
                        approvedRef.current = Object.assign({orderID}, wallet.toBlocksAddresses(payment));
                        propsRef.current.onSubmit();
                    }
                }, getHandlers(propsRef))).render('#' + containerId);
            });
            return () => {
                mounted = false;
            };
        }, []);
    };

    /**
     * @param {Object} wallet
     *     gatewayId          e.g. angelleye_ppcp_apple_pay
     *     settings           the Blocks payment method data
     *     createButton(h)    the classic button with Blocks handlers h
     *     isEligible()       promise of whether the wallet can be offered
     *     toBlocksAddresses  (payment) => {billing, shipping} from the sheet
     */
    const register = (wallet) => {
        const {gatewayId, settings} = wallet;
        const title = decodeEntities(settings.title || '');
        const noticeId = gatewayId.replace(/_/g, '-');

        // Offered once the SDKs are loaded and the wallet is eligible.
        const canMakePayment = () => new Promise((resolve) => {
            const timer = setTimeout(() => resolve(false), 15000);
            angelleyeOrder.onSdkReady(() => {
                Promise.resolve().then(wallet.isEligible).then((eligible) => {
                    clearTimeout(timer);
                    resolve(!!eligible);
                }, () => {
                    clearTimeout(timer);
                    resolve(false);
                });
            });
        });

        const Express = (props) => {
            const containerId = gatewayId + '_block';
            useWalletButton(wallet, props, containerId, (propsRef) => ({
                onClick: () => {
                    propsRef.current.setExpressPaymentError('');
                    angelleyeOrder.ppcp_address = null;
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

        // Replaces "Place order" while the wallet is the selected payment
        // method. Like any checkout payment method it takes the address from
        // the form, so the order total is the one the checkout shows (the
        // Store API rejects a checkout whose total went up from the one shown).
        const PlaceOrderButton = (props) => {
            const containerId = gatewayId + '_place_order';
            const validationRef = useRef(null);
            useWalletButton(wallet, props, containerId, (propsRef) => ({
                useCheckoutAddress: true,
                onClick: () => {
                    wp.data.dispatch('core/notices').removeNotice(noticeId, 'wc/checkout');
                    angelleyeOrder.ppcp_address = getCheckoutAddress();
                    // The sheet has to open inside the click, so the checkout
                    // form is validated alongside it and checked before the
                    // PayPal order is created.
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

        wc.wcBlocksRegistry.registerExpressPaymentMethod({
            name: gatewayId + '_express',
            paymentMethodId: gatewayId,
            content: createElement(Express, null),
            edit: createElement('div', null, title),
            canMakePayment,
            supports
        });

        const description = decodeEntities(settings.description || '');
        wc.wcBlocksRegistry.registerPaymentMethod({
            name: gatewayId,
            label: createElement('span', {style: {width: '100%'}}, title,
                    settings.icon ? createElement('img', {src: settings.icon, alt: title, style: {float: 'right', marginRight: '20px'}}) : null),
            ariaLabel: title,
            content: createElement('p', null, description),
            edit: createElement('p', null, description),
            placeOrderButton: PlaceOrderButton,
            canMakePayment,
            supports
        });
        // Without placeOrderButton support (older WooCommerce) the method would
        // get the standard "Place order" button, which can not open the sheet;
        // leave the wallet to the express button there.
        const registered = wc.wcBlocksRegistry.getPaymentMethods()[gatewayId];
        if (registered && !registered.placeOrderButton && wc.wcBlocksRegistry.__experimentalDeRegisterPaymentMethod) {
            wc.wcBlocksRegistry.__experimentalDeRegisterPaymentMethod(gatewayId);
        }
    };

    return {register};
})();
