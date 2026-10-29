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
    // Read while this script runs: WooCommerce's dependency check can only
    // tell which script touched wc.* during its own execution, and reports
    // later reads (from callbacks) as an unknown script.
    const {CART_STORE_KEY, CHECKOUT_STORE_KEY} = wc.wcBlocksData;
    const blocksRegistry = wc.wcBlocksRegistry;

    // Error messages arrive as HTML; DOMParser reads the text without running
    // anything in it.
    const toText = (html) => {
        try {
            return new DOMParser().parseFromString(String(html), 'text/html').body.textContent.trim();
        } catch (e) {
            return String(html);
        }
    };

    // Brings an error notice into view once Blocks has rendered it; same as the
    // card block. The wallet buttons can be far from where the notice shows.
    const scrollToNotice = (selectors) => {
        setTimeout(() => {
            const target = selectors.map((selector) => document.querySelector(selector)).find(Boolean);
            if (target && typeof target.scrollIntoView === 'function') {
                target.scrollIntoView({behavior: 'smooth', block: 'start'});
            }
        }, 80);
    };

    // The cart totals cached in the page are not refreshed when the Blocks cart
    // changes, so the sheets are priced from the Blocks cart store.
    const getCartDetails = (cartDetails) => {
        try {
            const cartStore = wp.data.select(CART_STORE_KEY);
            const totals = cartStore.getCartTotals();
            const decimals = totals.currency_minor_unit;
            const amount = (value) => (parseInt(value || 0, 10) / Math.pow(10, decimals)).toFixed(decimals);
            let lineItems = [{label: __('Subtotal', 'paypal-for-woocommerce'), amount: amount(totals.total_items)}];
            [
                [__('Shipping', 'paypal-for-woocommerce'), totals.total_shipping, 1],
                [__('Fees', 'paypal-for-woocommerce'), totals.total_fees, 1],
                [__('Tax', 'paypal-for-woocommerce'), totals.total_tax, 1],
                [__('Discount', 'paypal-for-woocommerce'), totals.total_discount, -1]
            ].forEach(([label, value, sign]) => {
                if (parseInt(value || 0, 10) !== 0) {
                    lineItems.push({label, amount: (sign * amount(value)).toFixed(decimals)});
                }
            });
            return Object.assign({}, cartDetails, {
                currencyCode: totals.currency_code,
                totalAmount: amount(totals.total_price),
                lineItems,
                shippingRequired: cartStore.getNeedsShipping()
            });
        } catch (e) {
            return cartDetails;
        }
    };

    // The checkout form's addresses, for the PayPal order; same as the card block.
    const getCheckoutAddress = () => {
        const customerData = wp.data.select(CART_STORE_KEY).getCustomerData() || {};
        const checkoutStore = wp.data.select(CHECKOUT_STORE_KEY);
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
                    getCartDetails,
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
                    scrollToNotice(['.wc-block-components-express-payment', '.wc-block-components-notices']);
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
                    scrollToNotice(['.wc-block-components-notices', '.wp-block-woocommerce-checkout', 'form.wc-block-checkout__form']);
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

        // Blocks passes its payment method props to content and edit, so these
        // are components rather than bare DOM elements.
        const Title = () => createElement('div', null, title);
        const description = decodeEntities(settings.description || '');
        const Description = () => createElement('p', null, description);

        blocksRegistry.registerExpressPaymentMethod({
            name: gatewayId + '_express',
            paymentMethodId: gatewayId,
            content: createElement(Express, null),
            edit: createElement(Title, null),
            canMakePayment,
            supports
        });

        blocksRegistry.registerPaymentMethod({
            name: gatewayId,
            label: createElement('span', {style: {width: '100%'}}, title,
                    settings.icon ? createElement('img', {src: settings.icon, alt: title, style: {float: 'right', marginRight: '20px'}}) : null),
            ariaLabel: title,
            content: createElement(Description, null),
            edit: createElement(Description, null),
            placeOrderButton: PlaceOrderButton,
            canMakePayment,
            supports
        });
        // Without placeOrderButton support (older WooCommerce) the method would
        // get the standard "Place order" button, which can not open the sheet;
        // leave the wallet to the express button there.
        const registered = blocksRegistry.getPaymentMethods()[gatewayId];
        if (registered && !registered.placeOrderButton && blocksRegistry.__experimentalDeRegisterPaymentMethod) {
            blocksRegistry.__experimentalDeRegisterPaymentMethod(gatewayId);
        }
    };

    return {register};
})();
