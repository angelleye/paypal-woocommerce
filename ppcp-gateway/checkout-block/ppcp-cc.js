var { createElement } = wp.element;
var { registerPlugin } = wp.plugins;
var { ExperimentalOrderMeta } = wc.blocksCheckout;
var { registerExpressPaymentMethod, registerPaymentMethod } = wc.wcBlocksRegistry;





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
        return e[o].call(r.exports, r, r.exports, n), (r.l = !0), r.exports;
    }

    n.m = e;
    n.c = t;
    n.d = function (e, t, o) {
        n.o(e, t) || Object.defineProperty(e, t, {
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
        if (4 & t && "object" == typeof e && e && e.__esModule)
            return e;
        var o = Object.create(null);
        if ((n.r(o), Object.defineProperty(o, "default", {enumerable: !0, value: e}), 2 & t && "string" != typeof e))
            for (var r in e)
                n.d(o, r, function (t) {
                    return e[t];
                }.bind(null, r));
        return o;
    };
    n.n = function (e) {
        var t = e && e.__esModule ? function () {
            return e.default;
        } : function () {
            return e;
        };
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
                const p = () => Object(a.decodeEntities)(l.description || "");

                const content = wp.element.createElement(
                        "div",
                        {className: "angelleye_ppcp_cc_parent"},
                        wp.element.createElement(
                                "div",
                                {className: "angelleye_ppcp_cc"},
                                wp.element.createElement("input", {
                                    type: "hidden",
                                    name: "form",
                                    value: "checkout"
                                })
                                )
                        );
                const ppcp_settings = angelleye_ppcp_manager_block.settins;
        const { is_order_confirm_page, is_paylater_enable_incart_page, page } = angelleye_ppcp_manager_block;
                const s = {
                    name: "angelleye_ppcp_cc",
                    label: Object(a.decodeEntities)(l.title || Object(i.__)("Payment via PayPal", "woo-gutenberg-products-block")),
                    icons: ["https://www.paypalobjects.com/webstatic/mktg/Logo/pp-logo-100px.png"],
                    placeOrderButtonLabel: Object(i.__)(angelleye_ppcp_cc_manager_block.placeOrderButtonLabel),
                    content: content,
                    edit: Object(r.createElement)(p, null),
                    canMakePayment: () => Promise.resolve(true),
                    ariaLabel: Object(a.decodeEntities)(l.title || Object(i.__)("Payment via PayPal", "woo-gutenberg-products-block")),
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
                        Object(r.createElement)("div", { className: "angelleye_ppcp_message_cart" })
                    )
                );
            };
            registerPlugin('wc-ppcp-cc', { render, scope: 'woocommerce-checkout' });
            }
]);

document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        jQuery(document.body).trigger('ppcp_block_ready');
    }, 1000);
});