const ppcp_settings = window.wc.wcSettings.getSetting( 'angelleye_ppcp_data', {} );
console.log('2');
console.log(ppcp_settings);
const ppcp_label = window.wp.htmlEntities.decodeEntities( ppcp_settings.title ) || window.wp.i18n.__( 'PayPal', 'angelleye_ppcp' );
const ppcp_content = () => {
    return window.wp.htmlEntities.decodeEntities( ppcp_settings.description || '' );
};
const ppcp_block_gateway = {
    name: 'angelleye_ppcp',
    label: ppcp_label,
    content: Object( window.wp.element.createElement )( ppcp_content, null ),
    edit: Object( window.wp.element.createElement )( ppcp_content, null ),
    canMakePayment: () => true,
    ariaLabel: ppcp_label,
    supports: {
        features: ppcp_settings.supports
    }
};
window.wc.wcBlocksRegistry.registerPaymentMethod( ppcp_block_gateway );