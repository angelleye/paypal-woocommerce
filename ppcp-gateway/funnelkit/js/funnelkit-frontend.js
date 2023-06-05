jQuery( function ($) {
    $(document).on('wfocu_external', function (e, Bucket) {

        /**
         * Check if we need to mark inoffer transaction to prevent default behavior of page
         */
        if (0 !== Bucket.getTotal()) {

            Bucket.inOfferTransaction = true;
            var getBucketData = Bucket.getBucketSendData();
            console.log(getBucketData, "bucketdata 1");
            var postData = $.extend(getBucketData, {action: 'angelleye_wfocu_front_handle_paypal_payments'});
            console.log(postData, "bucketdata 1");

            if (typeof wfocu_vars.wc_ajax_url !== "undefined") {
                var action = $.post(wfocu_vars.wc_ajax_url.toString().replace('%%endpoint%%', 'angelleye_wfocu_front_handle_paypal_payments'), postData);

            } else {
                var action = $.post(wfocu_vars.ajax_url, postData);

            }

            action.done(function (data) {

                if (data.status === true) {
                    window.location = data.redirect_url;
                } else {
                    Bucket.swal.show({'text': wfocu_vars.messages.offer_msg_pop_failure, 'type': 'warning'});
                    window.location = wfocu_vars.redirect_url + '&ec=ppec_token_not_found';
                }

            });

            action.fail(function () {
                Bucket.swal.show({'text': wfocu_vars.messages.offer_msg_pop_failure, 'type': 'warning'});
                /** move to order received page */
                if (typeof wfocu_vars.order_received_url !== 'undefined') {

                    window.location = wfocu_vars.order_received_url + '&ec=' + jqXHR.status;

                }
            });
        }
    });
});