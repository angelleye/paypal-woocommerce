var {createElement} = wp.element;
var {registerPlugin} = wp.plugins;
var {ExperimentalOrderMeta} = wc.blocksCheckout;
var {registerExpressPaymentMethod, registerPaymentMethod} = wc.wcBlocksRegistry;

(function (e) {
    var t = {};

    function n(o) {
        if (t[o])
            return t[o].exports;
        var r = (t[o] = {
            i: o,
            l: !1,
            exports: {},
        });
        return (
                e[o].call(r.exports, r, r.exports, n),
                (r.l = !0),
                r.exports
                );
    }

    n.m = e;
    n.c = t;
    n.d = function (e, t, o) {
        n.o(e, t) ||
                Object.defineProperty(e, t, {
                    enumerable: !0,
                    get: o,
                });
    };
    n.r = function (e) {
        "undefined" != typeof Symbol &&
                Symbol.toStringTag &&
                Object.defineProperty(e, Symbol.toStringTag, {
                    value: "Module",
                });
        Object.defineProperty(e, "__esModule", {
            value: !0,
        });
    };
    n.t = function (e, t) {
        if (1 & t && (e = n(e)), 8 & t)
            return e;
        if (
                4 & t &&
                "object" == typeof e &&
                e &&
                e.__esModule
                )
            return e;
        var o = Object.create(null);
        if (
                (n.r(o),
                        Object.defineProperty(o, "default", {
                            enumerable: !0,
                            value: e,
                        }),
                        2 & t && "string" != typeof e)
                )
            for (var r in e)
                n.d(
                        o,
                        r,
                        ((t) => {
                            return e[t];
                        }).bind(null, r)
                        );
        return o;
    };
    n.n = function (e) {
        var t = e && e.__esModule ? () => e.default : () => e;
        return n.d(t, "a", t), t;
    };
    n.o = function (e, t) {
        return Object.prototype.hasOwnProperty.call(e, t);
    };
    n.p = "";
    n(n.s = 6);
})([
    function (e, t) {
        e.exports = window.wp.element;
    },
    function (e, t) {
        e.exports = window.wp.htmlEntities;
    },
    function (e, t) {
        e.exports = window.wp.i18n;
    },
    function (e, t) {
        e.exports = window.wc.wcSettings;
    },
    function (e, t) {
        e.exports = window.wc.wcBlocksRegistry;
    },
    ,
            function (e, t, n) {
                "use strict";
                n.r(t);
                var o,
                        r = n(0),
                        c = n(4),
                        i = n(2),
                        u = n(3),
                        a = n(1);

                const l = Object(u.getSetting)("angelleye_ppcp_cc_data", {});
                const iconsElements = l.icons.map(icon => (
                            createElement("img", {src: icon, style: {float: "right", marginRight: "10px"}})
                            ));
                const p = () => Object(a.decodeEntities)(l.description || "");
                const ppcp_settings = angelleye_ppcp_manager_block.settins;
                const {is_order_confirm_page, is_paylater_enable_incart_page, page} = angelleye_ppcp_manager_block;
                const {useEffect} = window.wp.element;

                const Content_PPCP_CC = (props) => {
                    const {eventRegistration, emitResponse} = props;
                    const {onPaymentSetup} = eventRegistration;
                    useEffect(() => {
                        jQuery(document.body).trigger('trigger_angelleye_ppcp_cc');
                        // Schedule a reset of the Blocks payment store to idle on the
                        // next macrotask. Called after any ERROR return so the user's
                        // retry click reaches our subscriber again instead of being
                        // short-circuited by Blocks' built-in "payment option" fallback.
                        const schedulePaymentStateReset = () => {
                            setTimeout(function () {
                                try {
                                    var paymentStoreKey = wc && wc.wcBlocksData && wc.wcBlocksData.PAYMENT_STORE_KEY;
                                    if (!paymentStoreKey) {
                                        return;
                                    }
                                    var paymentDispatch = wp.data.dispatch(paymentStoreKey);
                                    if (paymentDispatch && typeof paymentDispatch.__internalSetPaymentIdle === 'function') {
                                        paymentDispatch.__internalSetPaymentIdle();
                                    }
                                } catch (e) {
                                    // no-op — store shape may change across WC versions
                                }
                            }, 0);
                        };
                        // Scroll the user to the top-of-form notice area once Blocks has
                        // had a tick to render the error notice DOM. Used for errors
                        // returned at CHECKOUT context so the message is visible on
                        // long checkout pages.
                        const scrollToCheckoutNotice = () => {
                            setTimeout(function () {
                                try {
                                    var target = document.querySelector('.wc-block-components-notices')
                                        || document.querySelector('.wp-block-woocommerce-checkout')
                                        || document.querySelector('form.wc-block-checkout__form');
                                    if (target && typeof target.scrollIntoView === 'function') {
                                        target.scrollIntoView({behavior: 'smooth', block: 'start'});
                                    }
                                } catch (e) {
                                    // no-op
                                }
                            }, 80);
                        };
                        const unsubscribe = onPaymentSetup(async () => {
                            try {
                                // If Blocks has any unresolved validation errors (e.g. a
                                // required billing/shipping field is empty), defer to its
                                // own field-level error display. Running card validation
                                // now would show "credit card details are not valid" on
                                // top of the real issue. Blocks will have already rendered
                                // inline errors next to the offending fields, so we just
                                // return a short pointer and skip the PayPal pre-flight.
                                try {
                                    var validationStoreKey = wc && wc.wcBlocksData && wc.wcBlocksData.VALIDATION_STORE_KEY;
                                    if (validationStoreKey) {
                                        var validationStore = wp.data.select(validationStoreKey);
                                        if (validationStore && typeof validationStore.hasValidationErrors === 'function' && validationStore.hasValidationErrors()) {
                                            schedulePaymentStateReset();
                                            scrollToCheckoutNotice();
                                            return {
                                                type: emitResponse.responseTypes.ERROR,
                                                message: 'Please complete all required fields before continuing.',
                                                messageContext: emitResponse.noticeContexts.CHECKOUT,
                                            };
                                        }
                                    }
                                } catch (validationCheckErr) {
                                    // Validation store shape may vary; fall through to
                                    // the card-fields flow if we can't read it.
                                }
                                // Read LIVE address state at click time from the Blocks data
                                // store instead of a stale useEffect closure. The cart store
                                // holds customer data; the checkout store holds the
                                // "Use same address for billing" checkbox.
                                const cartStore = wp.data.select(wc.wcBlocksData.CART_STORE_KEY);
                                const checkoutStore = wp.data.select(wc.wcBlocksData.CHECKOUT_STORE_KEY);
                                const customerData = (cartStore && cartStore.getCustomerData && cartStore.getCustomerData()) || {};
                                const useShippingAsBilling = (checkoutStore && typeof checkoutStore.getUseShippingAsBilling === 'function')
                                    ? checkoutStore.getUseShippingAsBilling()
                                    : false;
                                let billingAddress = customerData.billingAddress || {};
                                const shippingAddress = customerData.shippingAddress || {};
                                if (useShippingAsBilling) {
                                    billingAddress = Object.assign({}, shippingAddress, {
                                        email: billingAddress.email || customerData.email || '',
                                        phone: billingAddress.phone || shippingAddress.phone || '',
                                    });
                                }
                                angelleyeOrder.ppcp_address = {
                                    billing: billingAddress,
                                    shipping: shippingAddress,
                                };
                                const paypalOrderId = await angelleyeOrder.runBlocksPpcpCcFlow();
                                return {
                                    type: emitResponse.responseTypes.SUCCESS,
                                    meta: {
                                        paymentMethodData: {
                                            paypal_order_id: paypalOrderId,
                                        },
                                    },
                                };
                            } catch (err) {
                                // Returning ERROR puts Blocks into a "hasPaymentError"
                                // state that would otherwise short-circuit the next click
                                // before our subscriber runs. Reset to idle so the retry
                                // reaches us cleanly.
                                schedulePaymentStateReset();
                                // Card-field invalidity should be shown next to the
                                // payment method block (where the card fields live).
                                // All other failures (API errors, capture failures,
                                // 3DS errors) go to the top-of-form notice area where
                                // standard WC notices appear.
                                const isCardInvalid = err && err.context === 'card_invalid';
                                if (!isCardInvalid) {
                                    scrollToCheckoutNotice();
                                }
                                return {
                                    type: emitResponse.responseTypes.ERROR,
                                    message: (err && err.message) || 'Unable to process PayPal card payment. Please try again.',
                                    messageContext: isCardInvalid
                                        ? emitResponse.noticeContexts.PAYMENTS
                                        : emitResponse.noticeContexts.CHECKOUT,
                                };
                            }
                        });
                        return () => {
                            if (typeof unsubscribe === 'function') {
                                unsubscribe();
                            }
                        };
                    }, [onPaymentSetup, emitResponse.responseTypes]);
                    return createElement(
                            "fieldset",
                            {id: "wc-angelleye_ppcp_cc-form", className: "wc-credit-card-form wc-payment-form"},
                            createElement("div", {id: "angelleye_ppcp_cc-card-number"}),
                            createElement("div", {id: "angelleye_ppcp_cc-card-expiry"}),
                            createElement("div", {id: "angelleye_ppcp_cc-card-cvc"})
                            );
                };

                const s = {
                    name: "angelleye_ppcp_cc",
                    label: createElement(
                            "span",
                            {style: {width: "100%"}},
                            l.cc_title,
                            iconsElements
                            ),
                    icons: ["https://www.paypalobjects.com/webstatic/mktg/Logo/pp-logo-100px.png"],
                    placeOrderButtonLabel: Object(i.__)(angelleye_ppcp_cc_manager_block.placeOrderButtonLabel),
                    content: createElement(Content_PPCP_CC, null),
                    edit: Object(r.createElement)(p, null),
                    canMakePayment: () => Promise.resolve(true),
                    ariaLabel: Object(a.decodeEntities)(l.cc_title || Object(i.__)("Payment via PayPal", "woo-gutenberg-products-block")),
                    supports: {
                        features: null !== (o = l.supports) && void 0 !== o ? o : [],
                        showSavedCards: false,
                        showSaveOption: false
                    }
                };
                Object(c.registerPaymentMethod)(s);

                const render = () => {
                    const shouldShowDiv = is_paylater_enable_incart_page === 'yes';
                    return shouldShowDiv && (
                            wp.element.createElement(ExperimentalOrderMeta, null,
                                    Object(r.createElement)("div", {className: "angelleye_ppcp_message_cart"})
                                    )
                            );
                };
                registerPlugin('wc-ppcp-cc-checkout', {render, scope: 'woocommerce-checkout'});
            }
]);

document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        jQuery(document.body).trigger('ppcp_block_ready');
    }, 2000);
});

const ppcp_cc_uniqueEvents = new Set([
    'experimental__woocommerce_blocks-checkout-set-shipping-address',
    'experimental__woocommerce_blocks-checkout-set-billing-address',
    'experimental__woocommerce_blocks-checkout-set-email-address',
    'experimental__woocommerce_blocks-checkout-render-checkout-form',
    'experimental__woocommerce_blocks-checkout-set-active-payment-method'
]);

ppcp_cc_uniqueEvents.forEach(function (action) {
    addAction(action, 'c', function () {
        setTimeout(function () {
            jQuery(document.body).trigger('ppcp_cc_checkout_updated');
        }, 2000);
    });
});
