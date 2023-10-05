jQuery(function ($) {
    jQuery(document).ready(function ($) {
        $('#woocommerce-order-items').on('click', 'button.angelleye-ppcp-admin-action', function (e) {
            //$('div.wc-order-data-row-toggle').not('div.wc-order-refund-items').slideUp();
            $('.wc-order-data-row.wc-order-bulk-actions.wc-order-data-row-toggle').slideUp();
            $('#woocommerce-order-items').find('div.refund').show();
            $( 'div.wc-order-data-row.wc-order-add-item.wc-order-data-row-toggle button' ).not('.cancel-action').slideUp();
            $( 'div.wc-order-data-row.wc-order-add-item.wc-order-data-row-toggle' ).slideDown();
            $('.ppcp_auth_void_option').slideDown();
            $('.ppcp_auth_void_border').slideDown();
            $('.ppcp_auth_void_amount input[name="ppcp_refund_amount"]').attr('name', 'refund_amount');
            $('.ppcp_auth_void_amount input[id="ppcp_refund_amount"]').attr('id', 'refund_amount');
            window.wcTracks.recordEvent('order_edit_refund_button_click', {
                order_id: woocommerce_admin_meta_boxes.post_id,
                status: $('#order_status').val()
            });
            return false;
        });
        $('#woocommerce-order-items').on('click', 'button.cancel-action', function (e) {
            //$('div.wc-order-data-row-toggle').not('div.wc-order-refund-items').slideUp();
            $('.ppcp_auth_void_border').slideUp();
            $('.ppcp_auth_void_option').slideUp();
           $('.ppcp_auth_void_amount').slideUp();
           $('.ppcp_auth_void_amount input[name="refund_amount"]').attr('name', 'ppcp_refund_amount');
            $('.ppcp_auth_void_amount input[id="refund_amount"]').attr('id', 'ppcp_refund_amount');
        });
    });
});
