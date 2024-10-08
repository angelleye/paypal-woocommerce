var {createElement} = wp.element;
var {registerPlugin} = wp.plugins;
var {ExperimentalOrderMeta} = wc.blocksCheckout;
var {registerExpressPaymentMethod, registerPaymentMethod} = wc.wcBlocksRegistry;
var cartStore = wp.data.select('wc/store/cart');
const {PAYMENT_STORE_KEY} = window.wc.wcBlocksData;
const {registerPaymentMethodExtensionCallbacks} = window.wc.wcBlocksRegistry;

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
                const Content_PPCP_Fastlane = (props) => {
                    const {eventRegistration, emitResponse, onSubmit, billing, shippingData} = props;
                    const {onPaymentSetup} = eventRegistration;
                    useEffect(() => {
                        jQuery(document.body).trigger('trigger_angelleye_ppcp_fastlane');
                        jQuery(document.body).on('ppcp_fastlane_checkout_updated', function () {
                            let address = {
                                'billing': billing.billingAddress,
                                'shipping': shippingData.shippingAddress
                            };
                            angelleyeOrder.ppcp_address = [];
                            angelleyeOrder.ppcp_address = address;
                        });
                        const unsubscribe = onPaymentSetup(async () => {
                            if (jQuery('#fastlane-email').length > 0 && jQuery('#fastlane-email').val().trim() === '') {
                                jQuery('#fastlane-email').addClass('fastlane-input-error');
                                return {
                                    type: emitResponse.responseTypes.ERROR,
                                    message: 'Email address is required in the Fastlane email field to continue.'
                                };
                            } else {
                                jQuery('#fastlane-email').removeClass('fastlane-input-error');
                            }
                            let address = {
                                'billing': billing.billingAddress,
                                'shipping': shippingData.shippingAddress
                            };
                            angelleyeOrder.ppcp_address = [];
                            angelleyeOrder.ppcp_address = address;
                            wp.data.dispatch(wc.wcBlocksData.CHECKOUT_STORE_KEY).__internalSetIdle();
                            jQuery(document.body).trigger('submit_angelleye_ppcp_fastlane');
                            jQuery('.wc-block-components-checkout-place-order-button').append('<span class="wc-block-components-spinner" aria-hidden="true"></span>');
                            jQuery('.wc-block-components-checkout-place-order-button, .wp-block-woocommerce-checkout-fields-block #contact-fields, .wp-block-woocommerce-checkout-fields-block #billing-fields, .wp-block-woocommerce-checkout-fields-block #payment-method').block({
                                message: null,
                                overlayCSS: {background: '#fff', opacity: 0.6},
                            });
                        });

                    },
                    [emitResponse.responseTypes.ERROR, onPaymentSetup]);
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
                    content: createElement(Content_PPCP_Fastlane, null),
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


            }
]);

document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        jQuery(document.body).trigger('ppcp_block_ready');
    }, 2000);
});

const ppcp_fastlane_uniqueEvents = new Set([
    'experimental__woocommerce_blocks-checkout-render-checkout-form',
    'experimental__woocommerce_blocks-checkout-set-active-payment-method'
]);

ppcp_fastlane_uniqueEvents.forEach(function (action) {
    addAction(action, 'c', function () {
        setTimeout(function () {
            jQuery(document.body).trigger('ppcp_fastlane_checkout_updated');
        }, 20);
    });
});



jQuery(document.body).on('custom_action_to_refresh_checkout', function (event, profileData) {
    // Ensure wp.data and WooCommerce Blocks store are available
    if (typeof wp !== 'undefined' && typeof wp.data !== 'undefined' && wp.data.select('wc/store/cart')) {
        const {dispatch} = wp.data;

        // Get the specific actions from the cart store
        const {setBillingAddress, setShippingAddress, updateCustomerData} = dispatch('wc/store/cart');

        // Extract billing and shipping addresses from profileData
        const billingAddress = profileData.card?.paymentSource?.card?.billingAddress || {};
        const shippingAddress = profileData.shippingAddress?.address || {};
        const email = profileData.email || jQuery('#fastlane-email').val() || '';

        const {__internalSetActivePaymentMethod: setActivePaymentMethod} = dispatch(PAYMENT_STORE_KEY);


        // Update billing address using setBillingAddress action
        setBillingAddress({
            first_name: profileData.name?.firstName || '',
            last_name: profileData.name?.lastName || '',
            company: billingAddress.company || '',
            address_1: billingAddress.addressLine1 || '',
            address_2: billingAddress.addressLine2 || '',
            city: billingAddress.adminArea2 || '',
            state: billingAddress.adminArea1 || '',
            postcode: billingAddress.postalCode || '',
            country: billingAddress.countryCode || '',
            email: email,
            phone: profileData.shippingAddress?.phoneNumber?.nationalNumber || ''
        });

        // Update shipping address using setShippingAddress action
        setShippingAddress({
            first_name: profileData.shippingAddress?.name?.firstName || '',
            last_name: profileData.shippingAddress?.name?.lastName || '',
            company: shippingAddress.company || '',
            address_1: shippingAddress.addressLine1 || '',
            address_2: shippingAddress.addressLine2 || '',
            city: shippingAddress.adminArea2 || '',
            state: shippingAddress.adminArea1 || '',
            postcode: shippingAddress.postalCode || '',
            country: shippingAddress.countryCode || ''
        });

        // Prepare the customer data object
        const customerData = {
            shippingAddress: {
                first_name: profileData.shippingAddress?.name?.firstName || '',
                last_name: profileData.shippingAddress?.name?.lastName || '',
                company: shippingAddress.company || '',
                address_1: shippingAddress.addressLine1 || '',
                address_2: shippingAddress.addressLine2 || '',
                city: shippingAddress.adminArea2 || '',
                state: shippingAddress.adminArea1 || '',
                postcode: shippingAddress.postalCode || '',
                country: shippingAddress.countryCode || ''
            },
            billingAddress: {
                first_name: profileData.name?.firstName || '',
                last_name: profileData.name?.lastName || '',
                company: billingAddress.company || '',
                address_1: billingAddress.addressLine1 || '',
                address_2: billingAddress.addressLine2 || '',
                city: billingAddress.adminArea2 || '',
                state: billingAddress.adminArea1 || '',
                postcode: billingAddress.postalCode || '',
                country: billingAddress.countryCode || '',
                email: email,
                phone: profileData.shippingAddress?.phoneNumber?.nationalNumber || ''
            }
        };

        // Update customer data using updateCustomerData action
        updateCustomerData(customerData, true);

        if (setActivePaymentMethod) {
            setActivePaymentMethod('angelleye_ppcp_fastlane');
        }

        // Remove 'is-editing' class from the address wrapper if it exists
        // jQuery('.wc-block-components-address-address-wrapper').removeClass('is-editing');
    } else {
        console.error('WooCommerce Blocks or wp.data is not available.');
    }
});

jQuery(document.body).on('custom_action_to_refresh_checkout_email', function (event) {
    if (typeof wp !== 'undefined' && typeof wp.data !== 'undefined' && wp.data.select('wc/store/cart')) {
        const {dispatch} = wp.data;
        const {setBillingAddress} = dispatch('wc/store/cart');
        const email = jQuery('#fastlane-email').val() || '';
        const {__internalSetActivePaymentMethod: setActivePaymentMethod} = dispatch(PAYMENT_STORE_KEY);
        if (setActivePaymentMethod) {
            setActivePaymentMethod('angelleye_ppcp_fastlane');
        }
        setBillingAddress({
            email: email
        });
    } else {
        console.error('WooCommerce Blocks or wp.data is not available.');
    }


});
