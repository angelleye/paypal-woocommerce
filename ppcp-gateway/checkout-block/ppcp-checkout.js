if (typeof wp !== 'undefined' && typeof wp.element !== 'undefined' && typeof wp.element.createElement === 'undefined') {
    const {createElement} = wp.element;
}
if (typeof wp !== 'undefined' && typeof wp.plugins !== 'undefined' && typeof wp.plugins.registerPlugin === 'undefined') {
    const {registerPlugin} = wp.plugins;
}
if (typeof wc !== 'undefined' && typeof wc.blocksCheckout !== 'undefined' && typeof wc.blocksCheckout.ExperimentalOrderMeta === 'undefined') {
    const {ExperimentalOrderMeta} = wc.blocksCheckout;
}
// Check if wc and wc.wcBlocksRegistry are defined
if (typeof wc !== 'undefined' && typeof wc.wcBlocksRegistry !== 'undefined') {
  // Check if registerExpressPaymentMethod is not defined
  if (typeof wc.wcBlocksRegistry.registerExpressPaymentMethod === 'undefined') {
    const { registerExpressPaymentMethod, registerPaymentMethod } = wc.wcBlocksRegistry;
    // Now you can use registerExpressPaymentMethod and registerPaymentMethod as needed
  } else {
    console.log('registerExpressPaymentMethod is already declared');
  }
} else {
  console.log('wc or wc.wcBlocksRegistry is not defined');
}


(function (e) {
    var t = {};

    function n(o) {
        if (t[o]) return t[o].exports;
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
        if (1 & t && (e = n(e)), 8 & t) return e;
        if (4 & t && "object" == typeof e && e && e.__esModule) return e;
        var o = Object.create(null);
        if ((n.r(o), Object.defineProperty(o, "default", { enumerable: !0, value: e }), 2 & t && "string" != typeof e))
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

        const l = Object(u.getSetting)("angelleye_ppcp_data", {});
        const p = () => Object(a.decodeEntities)(l.description || "");

        const content = wp.element.createElement(
            "div",
            { className: "angelleye_ppcp_checkout_parent" },
            wp.element.createElement(
                "div",
                { className: "angelleye_ppcp_checkout" },
                wp.element.createElement("input", {
                    type: "hidden",
                    name: "form",
                    value: "checkout"
                })
            )
        );

        const s = {
            name: "angelleye_ppcp",
            label: Object(a.decodeEntities)(l.title || Object(i.__)("Payment via PayPal", "woo-gutenberg-products-block")),
            icons: ["https://www.paypalobjects.com/webstatic/mktg/Logo/pp-logo-100px.png"],
            placeOrderButtonLabel: Object(i.__)(angelleye_ppcp_manager_block.placeOrderButtonLabel),
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
        const ppcp_settings = angelleye_ppcp_manager_block.settins;
        const { is_order_confirm_page, is_paylater_enable_incart_page, page } = angelleye_ppcp_manager_block;
        console.log(angelleye_ppcp_manager_block);
        const commonExpressPaymentMethodConfig = {
            name: "angelleye_ppcp_top",
            label: Object(a.decodeEntities)(l.title || Object(i.__)("Payment via PayPal", "woo-gutenberg-products-block")),
            content: Object(r.createElement)("div", { id: "angelleye_ppcp_checkout_top" }),
            edit: Object(r.createElement)(p, null),
            ariaLabel: Object(a.decodeEntities)(l.title || Object(i.__)("Payment via PayPal", "woo-gutenberg-products-block")),
            canMakePayment: () => true,
            paymentMethodId: 'angelleye_ppcp',
            supports: {
                features: l.supports || []
            }
        };
        if (page === 'checkout' && is_order_confirm_page === 'no' && ppcp_settings && (ppcp_settings.checkout_page_display_option === 'both' || ppcp_settings.checkout_page_display_option === 'top')) {
            registerExpressPaymentMethod(commonExpressPaymentMethodConfig);

            const render = () => {
                const shouldShowDiv = is_paylater_enable_incart_page === 'yes';
                return shouldShowDiv && (
                    wp.element.createElement(ExperimentalOrderMeta, null,
                        Object(r.createElement)("div", { className: "angelleye_ppcp_message_cart" })
                    )
                );
            };
            registerPlugin('wc-ppcp', { render, scope: 'woocommerce-checkout' });
        } else if (page === 'cart' && ppcp_settings && ppcp_settings.enable_cart_button === 'yes') {
            registerExpressPaymentMethod(commonExpressPaymentMethodConfig);
            const render = () => {
                const shouldShowDiv = is_paylater_enable_incart_page === 'yes';
                return shouldShowDiv && (
                    wp.element.createElement(ExperimentalOrderMeta, null,
                        Object(r.createElement)("div", { className: "angelleye_ppcp_message_cart" })
                    )
                );
            };
            registerPlugin('wc-ppcp', { render, scope: 'woocommerce-checkout' });
        }
    }
]);

document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        jQuery(document.body).trigger('ppcp_block_ready');
    }, 1000);
});