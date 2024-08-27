var {createElement} = wp.element;
var {registerPlugin} = wp.plugins;
var {ExperimentalOrderMeta} = wc.blocksCheckout;
var {registerExpressPaymentMethod, registerPaymentMethod} = wc.wcBlocksRegistry;
var cartStore = wp.data.select('wc/store/cart');

(function (e) {
    var t = {};

    function n(o) {
        if (t[o])
            return t[o].exports;
        var r = (t[o] = {i: o, l: !1, exports: {}});
        return e[o].call(r.exports, r, r.exports, n), (r.l = !0), r.exports;
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
                Object.defineProperty(e, Symbol.toStringTag, {value: "Module"});
        Object.defineProperty(e, "__esModule", {value: !0});
    };
    n.t = function (e, t) {
        if (1 & t && (e = n(e)), 8 & t)
            return e;
        if (4 & t && "object" == typeof e && e && e.__esModule)
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

                const l = Object(u.getSetting)("angelleye_ppcp_fastlane_data", {});
                const iconsElements = l.icons.map(icon =>
                    createElement("img", {src: icon, style: {float: "right", marginRight: "10px"}})
                );
                const p = () => Object(a.decodeEntities)(l.description || "");
                const ppcp_settings = angelleye_ppcp_manager_block.settins;
                const {is_order_confirm_page, is_paylater_enable_incart_page, page} = angelleye_ppcp_manager_block;
                const {useEffect} = window.wp.element;

                const Content_PPCP_CC = (props) => {
                    const {eventRegistration, emitResponse, onSubmit, billing, shippingData} = props;
                    const {onPaymentSetup} = eventRegistration;

                    useEffect(() => {
                        jQuery(document.body).trigger('trigger_angelleye_ppcp_fastlane');
                        jQuery(document.body).on('ppcp_fastlane_checkout_updated', function () {
                            let address = {
                                'billing': billing.billingAddress,
                                'shipping': shippingData.shippingAddress,
                            };
                            angelleyeOrder.ppcp_address = [];
                            angelleyeOrder.ppcp_address = address;
                            jQuery('#wc-angelleye_ppcp_fastlane-form').unblock();
                            angelleyeOrder.renderPaymentButtons();
                        });

                        const unsubscribe = onPaymentSetup(async () => {
                            wp.data.dispatch(wc.wcBlocksData.CHECKOUT_STORE_KEY).__internalSetIdle();
                            jQuery(document.body).trigger('submit_paypal_cc_form');
                            jQuery('.wc-block-components-checkout-place-order-button').append('<span class="wc-block-components-spinner" aria-hidden="true"></span>');
                            jQuery('.wc-block-components-checkout-place-order-button, .wp-block-woocommerce-checkout-fields-block #contact-fields, .wp-block-woocommerce-checkout-fields-block #billing-fields, .wp-block-woocommerce-checkout-fields-block #payment-method').block({
                                message: null,
                                overlayCSS: {background: '#fff', opacity: 0.6},
                            });
                        });

                    }, [onPaymentSetup]);

                    return createElement("div", {id: "angelleye_ppcp_checkout_fastlane"});
                };

                const s = {
                    name: "angelleye_ppcp_fastlane",
                    label: createElement(
                            "span",
                            {style: {width: "100%"}},
                            l.cc_title,
                            iconsElements
                            ),
                    icons: ["https://www.paypalobjects.com/webstatic/mktg/Logo/pp-logo-100px.png"],
                    placeOrderButtonLabel: Object(i.__)(angelleye_ppcp_fastlane_manager_block.placeOrderButtonLabel),
                    content: createElement(Content_PPCP_CC, null),
                    edit: Object(r.createElement)(p, null),
                    canMakePayment: () => Promise.resolve(true),
                    ariaLabel: Object(a.decodeEntities)(l.cc_title || Object(i.__)("Payment via PayPal", "woo-gutenberg-products-block")),
                    supports: {
                        features: null !== (o = l.supports) && void 0 !== o ? o : [],
                        showSavedCards: false,
                        showSaveOption: false,
                    },
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

                registerPlugin('wc-ppcp-fastlane-checkout', {render, scope: 'woocommerce-checkout'});
            }
]);

document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        jQuery(document.body).trigger('ppcp_block_ready');
    }, 2000);
});

const ppcp_fastlane_uniqueEvents = new Set([
    'experimental__woocommerce_blocks-checkout-set-shipping-address',
    'experimental__woocommerce_blocks-checkout-set-billing-address',
    'experimental__woocommerce_blocks-checkout-set-email-address',
    'experimental__woocommerce_blocks-checkout-render-checkout-form',
    'experimental__woocommerce_blocks-checkout-set-active-payment-method',
]);

ppcp_fastlane_uniqueEvents.forEach(function (action) {
    addAction(action, 'c', function () {
        setTimeout(function () {
            jQuery(document.body).trigger('ppcp_fastlane_checkout_updated');
        }, 2000);
    });
});

jQuery(document.body).on('custom_action_to_refresh_checkout', function (event, profileData) {
    // Ensure wp.data and WooCommerce Blocks store are available
    if (typeof wp !== 'undefined' && typeof wp.data !== 'undefined' && wp.data.select('wc/store/cart')) {
        // Use wp.data.dispatch to get the dispatch function for WooCommerce Blocks store
        const { dispatch } = wp.data;

        // Get the specific actions from the checkout store
        const { setBillingAddress, setShippingAddress } = dispatch('wc/store/cart');

        // Extract billing and shipping addresses from profileData
        const billingAddress = profileData.card?.paymentSource?.card?.billingAddress || {};
        const shippingAddress = profileData.shippingAddress?.address || {};
        const email = profileData.email || jQuery('#fastlane-email').val() || '';
        console.log(JSON.stringify(profileData));
        // Update billing address using setBillingAddress action
        setBillingAddress({
            first_name: profileData.name?.firstName || '',
            last_name: profileData.name?.lastName || '',
            address_1: billingAddress.addressLine1 || '',
            city: billingAddress.adminArea2 || '',
            postcode: billingAddress.postalCode || '',
            country: billingAddress.countryCode || '',
            state: billingAddress.adminArea1 || '',
            email: email,
        });

        // Update shipping address using setShippingAddress action
        setShippingAddress({
            first_name: profileData.shippingAddress?.name?.firstName || '',
            last_name: profileData.shippingAddress?.name?.lastName || '',
            address_1: shippingAddress.addressLine1 || '',
            city: shippingAddress.adminArea2 || '',
            postcode: shippingAddress.postalCode || '',
            country: shippingAddress.countryCode || '',
            state: shippingAddress.adminArea1 || '',
            phone: profileData.shippingAddress?.phoneNumber?.nationalNumber || ''
        });

        jQuery('.wc-block-components-address-address-wrapper').removeClass('is-editing');
        // Refresh the checkout state if necessary
    } else {
        console.error('WooCommerce Blocks or wp.data is not available.');
    }
});


