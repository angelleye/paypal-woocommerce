jQuery(function ($) {
    jQuery(document).ready(function ($) {
        $('#woocommerce-order-items').on('click', 'button.angelleye-ppcp-admin-action', function (e) {
            //$('div.wc-order-data-row-toggle').not('div.wc-order-refund-items').slideUp();
            $('.wc-order-data-row.wc-order-bulk-actions.wc-order-data-row-toggle').slideUp();
            $('#woocommerce-order-items').find('div.refund').show();
            $( 'div.wc-order-data-row.wc-order-add-item.wc-order-data-row-toggle button' ).not('.cancel-action').slideUp();
            $( 'div.wc-order-data-row.wc-order-add-item.wc-order-data-row-toggle' ).slideDown();
            $('.ppcp_auth_void_amount').slideDown();
            window.wcTracks.recordEvent('order_edit_refund_button_click', {
                order_id: woocommerce_admin_meta_boxes.post_id,
                status: $('#order_status').val()
            });
            return false;
        });
        $('#woocommerce-order-items').on('click', 'button.cancel-action', function (e) {
            //$('div.wc-order-data-row-toggle').not('div.wc-order-refund-items').slideUp();
           $('.ppcp_auth_void_amount').slideUp();
        });
    });
});
