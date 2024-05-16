const payLaterMessaging = {
    init: () => {
        if (typeof angelleye_pay_later_messaging === 'undefined') {
            return false;
        }
        if (typeof angelleye_paypal_sdk === 'undefined') {
            console.log('Unable to render the PayLaterMessaging: PayPal lib not defined.')
            return;
        }
        if (typeof angelleye_paypal_sdk.Messages === 'undefined') {
            console.log('PayLaterMessaging is not enabled for this merchant account.')
            return;
        }
        // console.log('Init PayLater');
        let amount = angelleye_pay_later_messaging.amount;
        let currencyCode = angelleye_pay_later_messaging.currencyCode;
        let placementsConfig = angelleye_pay_later_messaging.placements;
        let placementsKeys = Object.keys(placementsConfig);
        for (let i = 0; i < placementsKeys.length; i++) {
            let placement = placementsKeys[i];
            let placementConfig = placementsConfig[placement];
            let styleConfig = payLaterMessaging.getPayLaterStyleConfig(placementConfig);
            payLaterMessaging.render(amount, currencyCode, placement, styleConfig, placementConfig["css_selector"]);
        }

        // used to show hide the message
        payLaterMessaging.handleVariationProduct();

        // handle shortcodes stuff
        jQuery('.angelleye_ppcp_message_shortcode').each(function () {
            let dataKey = jQuery(this).attr('data-key');
            let shortcodeConfig = window[dataKey];
            if (typeof shortcodeConfig !== 'undefined') {
                let placement = shortcodeConfig.placement;
                let styleConfig = payLaterMessaging.getPayLaterStyleConfig(shortcodeConfig);
                payLaterMessaging.render(amount, currencyCode, placement, styleConfig, shortcodeConfig["css_selector"])
            }
        })
    },
    render: (amount, currencyCode, placement, styleConfig, renderDiv) => {
        if (typeof renderDiv === 'undefined') {
            renderDiv = '.angelleye_ppcp_message_cart';
        }
        if (jQuery(renderDiv).length && jQuery(renderDiv).is(":visible")) {
            // Known issues, if we pass the difference currency than merchant account currency it will not work
            // https://www.paypal-community.com/t5/PayPal-Payments-Standard/PayPal-Pay-Later-message-it-says-invalid-currency/td-p/3045658
            const payLaterConfig = {
                amount: amount,
                currency: currencyCode,
                placement: placement,
                style: styleConfig
            };
            angelleye_paypal_sdk.Messages(payLaterConfig).render(renderDiv);
        } else {
        }
    },
    getPayLaterStyleConfig: (placementConfig) => {
        let styleConfig = {
            "layout": placementConfig["layout_type"],
            "logo": {},
            "text": {},
        };
        if (styleConfig.layout === 'text') {
            styleConfig.logo["type"] = placementConfig["text_layout_logo_type"]
            if (['primary', "alternative"].indexOf(placementConfig["text_layout_logo_type"]) > -1) {
                styleConfig.logo["position"] = placementConfig["text_layout_logo_position"];
            }
            styleConfig.text["size"] = parseInt(placementConfig["text_layout_text_size"]);
            styleConfig.text["color"] = placementConfig["text_layout_text_color"];
        } else {
            styleConfig.color = placementConfig["flex_layout_color"];
            styleConfig.ratio = placementConfig["flex_layout_ratio"];
        }
        return styleConfig;
    },
    handleVariationProduct: () => {
        if (!angelleyeOrder.isProductPage())
            return;
        if (jQuery('.variations_form').length) {
            jQuery('.variations_form').on('show_variation', function () {
                jQuery('.angelleye_ppcp_message_product').show();
            }).on('hide_variation', function () {
                jQuery('.angelleye_ppcp_message_product').hide();
            });
        }
    }
};
(function () {
    'use strict';
    angelleyeLoadPayPalScript({
        url: angelleye_ppcp_manager.paypal_sdk_url,
        script_attributes: angelleye_ppcp_manager.paypal_sdk_attributes
    }, function () {
        console.log('PayPal lib loaded, initialize pay later messaging.');
        jQuery(document.body).on('ppcp_block_ready, ppcp_block_paylater_ready', async function () {
            payLaterMessaging.init();
        });
        payLaterMessaging.init();
        if (angelleyeOrder.isCartPage() || angelleyeOrder.isCheckoutPage()) {
            jQuery(document.body).on('angelleye_cart_total_updated ppcp_block_ready', async function () {
                const cartDetails = angelleyeOrder.getCartDetails();
                // console.log('PayLater amount update', cartDetails.totalAmount);
                angelleye_pay_later_messaging.amount = cartDetails.totalAmount;
                angelleye_pay_later_messaging.currencyCode = cartDetails.currencyCode;
                payLaterMessaging.init();
            });
        }

        if( angelleyeOrder.isProductPage()) {
            let variationsForm = jQuery('form.variations_form');
            if( variationsForm.length > 0 ) {
                jQuery('form.variations_form select').on('change', function() {
                    const myTimeout = setTimeout( function (){
                        let variationPrice = variationsForm.find( '.single_variation_wrap .woocommerce-variation-price' ).text();
                        variationPrice = (variationPrice) ? variationPrice.replace( angelleye_pay_later_messaging.currencySymbol, "" ): 0;
                        angelleye_pay_later_messaging.amount = variationPrice;
                        payLaterMessaging.init();
                        clearTimeout(myTimeout);
                    }, 500);
                });
            }
        }
    });
})(jQuery);
