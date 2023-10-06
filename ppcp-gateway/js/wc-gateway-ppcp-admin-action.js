jQuery(function ($) {
    $(document).ready(function ($) {
        $('#woocommerce-order-items').on('click', 'button.angelleye-ppcp-admin-action', function (e) {
            $('.wc-order-data-row.wc-order-bulk-actions.wc-order-data-row-toggle').slideUp();
            $('div.wc-order-data-row.wc-order-add-item.wc-order-data-row-toggle button').not('.cancel-action').slideUp();
            $('.angelleye_ppcp_capture_box input[name="ppcp_refund_amount"]').attr('name', 'refund_amount');
            $('.angelleye_ppcp_capture_box input[id="ppcp_refund_amount"]').attr('id', 'refund_amount');
            $('div.wc-order-data-row.wc-order-add-item.wc-order-data-row-toggle').slideDown();
            $('.paypal-fee-tr').slideUp();
            $('.ppcp_auth_void_option').slideDown();
            $('.ppcp_auth_void_border').slideDown();
            $('#order_metabox_angelleye_ppcp_payment_action').prop('selectedIndex', 0);
        });
        $('#woocommerce-order-items').on('click', 'button.cancel-action', function (e) {
            $('.ppcp_auth_void_border').slideUp();
            $('.ppcp_auth_void_option').slideUp();
            $('.ppcp_auth_void_amount').slideUp();
            $('.angelleye_ppcp_capture_box input[name="refund_amount"]').attr('name', 'ppcp_refund_amount');
            $('.angelleye_ppcp_capture_box input[id="refund_amount"]').attr('id', 'ppcp_refund_amount');
            $('.angelleye_ppcp_refund_box').slideUp();
            $('.angelleye_ppcp_capture_box').slideUp();
            $('.angelleye_ppcp_void_box').slideUp();
            $('.paypal-fee-tr').slideUp();
        });
    });
    $('#order_metabox_angelleye_ppcp_payment_action').change(function (e) {
        e.preventDefault();
        if ($(this).val() === 'refund') {
            $('.angelleye_ppcp_refund_box').slideDown();
            $('.angelleye_ppcp_capture_box').slideUp();
            $('.angelleye_ppcp_void_box').slideUp();
            $('#woocommerce-order-items').find('div.refund').slideUp();
        } else if ($(this).val() === 'capture') {
            $('#woocommerce-order-items').find('div.refund').slideDown();
            $('.angelleye_ppcp_capture_box').slideDown();
            $('.angelleye_ppcp_refund_box').slideUp();
            $('.angelleye_ppcp_void_box').slideUp();
        } else if ($(this).val() === 'void') {
            $('.angelleye_ppcp_capture_box').slideUp();
            $('.angelleye_ppcp_refund_box').slideUp();
            $('.angelleye_ppcp_void_box').slideDown();
            $('#woocommerce-order-items').find('div.refund').slideUp();
        } else {
            $('.angelleye_ppcp_capture_box').slideUp();
            $('.angelleye_ppcp_refund_box').slideUp();
            $('.angelleye_ppcp_void_box').slideUp();
            $('#woocommerce-order-items').find('div.refund').slideUp();
        }
        if ($(this).val().length === 0) {
            $('.angelleye-ppcp-order-action-submit').slideUp();
            return false;
        } else {
            $('.angelleye-ppcp-order-action-submit').slideDown();
        }
    }).change();
});
