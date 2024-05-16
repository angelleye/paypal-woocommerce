var {createElement} = wp.element;
var {registerPlugin} = wp.plugins;
var {ExperimentalOrderMeta} = wc.blocksCheckout;
var {registerExpressPaymentMethod, registerPaymentMethod} = wc.wcBlocksRegistry;
var {addAction} = wp.hooks;

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
                e[o].call(r.exports, r, r.exports, n), (r.l = !0), r.exports
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
                const l = Object(u.getSetting)("angelleye_ppcp_data", {});
                const p = () => Object(a.decodeEntities)(l.description || "");
                const {useEffect} = window.wp.element;

                const Content_PPCP_Smart_Button = (props) => {
                    const {billing, shippingData} = props;
                    jQuery(document.body).on(
                            "ppcp_checkout_updated",
                            function () {
                                console.log("101");
                                let address = {
                                    billing: billing.billingAddress,
                                    shipping: shippingData.shippingAddress,
                                };
                                angelleyeOrder.ppcp_address = [];
                                angelleyeOrder.ppcp_address = address;
                                jQuery("#angelleye_ppcp_checkout").unblock();
                                angelleyeOrder.renderPaymentButtons();
                            }
                    );
                    return createElement("div", {id: "angelleye_ppcp_checkout"});
                };

                const Content_PPCP_Smart_Button_Express = () => {
                    useEffect(() => {
                        angelleyeOrder.renderPaymentButtons();
                    }, []);
                    return createElement("div", {id: "angelleye_ppcp_checkout_top"});
                };

                const render = () => {
                    useEffect(() => {
                        jQuery(document.body).trigger("ppcp_block_paylater_ready");
                    }, []);
                    const shouldShowDiv =
                            is_paylater_enable_incart_page === "yes";
                    return shouldShowDiv ? (
                            createElement(ExperimentalOrderMeta, null, createElement("div", {className: "angelleye_ppcp_message_cart"}))
                            ) : null;
                };

                const s = {
                    name: "angelleye_ppcp",
                    label: createElement(
                            "span",
                            {style: {width: "100%"}},
                            l.title,
                            createElement("img", {
                                src: l.icon,
                                style: {float: "right", marginRight: "20px"},
                            })
                            ),
                    placeOrderButtonLabel: Object(i.__)(
                            angelleye_ppcp_manager_block.placeOrderButtonLabel
                            ),
                    content: createElement(Content_PPCP_Smart_Button, null),
                    edit: Object(r.createElement)(p, null),
                    canMakePayment: () => Promise.resolve(!0),
                    ariaLabel: Object(a.decodeEntities)(
                            l.title ||
                            Object(i.__)("Payment via PayPal", "woo-gutenberg-products-block")
                            ),
                    supports: {
                        features:
                                null !== (o = l.supports) && void 0 !== o ? o : [],
                        showSavedCards: !1,
                        showSaveOption: !1,
                    },
                };
                Object(c.registerPaymentMethod)(s);

                const ppcp_settings =
                        angelleye_ppcp_manager_block.settins;
                const {
                    is_order_confirm_page,
                    is_paylater_enable_incart_page,
                    page,
                } = angelleye_ppcp_manager_block;
                const commonExpressPaymentMethodConfig = {
                    name: "angelleye_ppcp_top",
                    label: Object(a.decodeEntities)(
                            l.title ||
                            Object(i.__)("Payment via PayPal", "woo-gutenberg-products-block")
                            ),
                    content: createElement(Content_PPCP_Smart_Button_Express, null),
                    edit: Object(r.createElement)(p, null),
                    ariaLabel: Object(a.decodeEntities)(
                            l.title ||
                            Object(i.__)("Payment via PayPal", "woo-gutenberg-products-block")
                            ),
                    canMakePayment: () => !0,
                    paymentMethodId: "angelleye_ppcp",
                    supports: {
                        features: l.supports || [],
                    },
                };

                if (
                        page === "checkout" &&
                        is_order_confirm_page === "no" &&
                        ppcp_settings &&
                        (ppcp_settings.checkout_page_display_option === "both" ||
                                ppcp_settings.checkout_page_display_option === "top")
                        ) {
                    registerExpressPaymentMethod(
                            commonExpressPaymentMethodConfig
                            );
                    registerPlugin("wc-ppcp", {render, scope: "woocommerce-checkout"});
                } else if (
                        page === "cart" &&
                        ppcp_settings &&
                        ppcp_settings.enable_cart_button === "yes"
                        ) {
                    registerExpressPaymentMethod(
                            commonExpressPaymentMethodConfig
                            );
                }



                if (
                        jQuery.inArray("cart", ppcp_settings.pay_later_messaging_page_type) !==
                        -1
                        ) {
                    registerPlugin("wc-ppcp-cart", {render, scope: "woocommerce-cart"});
                } else if (
                        jQuery.inArray(
                                "payment",
                                ppcp_settings.pay_later_messaging_page_type
                                ) !== -1
                        ) {
                    registerPlugin("wc-ppcp-checkout", {render, scope: "woocommerce-checkout"});
                }
            },
]);

document.addEventListener("DOMContentLoaded", function () {
    setTimeout(function () {
        jQuery(document.body).trigger("ppcp_block_ready");
    }, 1500);
});

const ppcp_uniqueEvents = new Set([
    "experimental__woocommerce_blocks-checkout-set-shipping-address",
    "experimental__woocommerce_blocks-checkout-set-billing-address",
    "experimental__woocommerce_blocks-checkout-set-email-address",
    "experimental__woocommerce_blocks-checkout-render-checkout-form",
    "experimental__woocommerce_blocks-checkout-set-active-payment-method",
]);

ppcp_uniqueEvents.forEach(function (action) {
    addAction(action, "c", function () {
        jQuery("#angelleye_ppcp_checkout").block({
            message: null,
            overlayCSS: {background: "#fff", opacity: 0.6},
        });
        setTimeout(function () {
            jQuery(document.body).trigger("ppcp_checkout_updated");
        }, 1500);
    });
});
