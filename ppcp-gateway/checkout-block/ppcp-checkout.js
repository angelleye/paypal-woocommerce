const settings = window.wc.wcSettings.getSetting('angelleye_ppcp_data', {});
const label = window.wp.htmlEntities.decodeEntities(settings.title) || window.wp.i18n.__('PPCP PayPal', 'paypal-for-woocommerce');
const Content = () => {
    return window.wp.htmlEntities.decodeEntities(settings.description || '');
};
const Block_Gateway = {
    name: 'angelleye_ppcp',
    label: label,
    content: Object(window.wp.element.createElement)(Content, null),
    edit: Object(window.wp.element.createElement)(Content, null),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports
    }
};

window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway);

const Express_Block_Gateway = {
    name: "PayPal PPCPPC",
    edit: Object(window.wp.element.createElement)(Content, null),
    content: Object(r.createElement)("div", {id: "angelleye_ppcp_checkout"}),
    canMakePayment: () => true,
    paymentMethodId: "angelleye_ppcp",
    supports: {
        features: settings.supports
    }
};

window.wc.wcBlocksRegistry.registerExpressPaymentMethod(Express_Block_Gateway);




