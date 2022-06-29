(function ($) {
    'use strict';
    $(function () {
        $('form.checkout').on('click', 'input[name="payment_method"]', function () {
            var isPPEC = $(this).is('#payment_method_angelleye_ppcp');
            var togglePPEC = isPPEC ? 'show' : 'hide';
            $('#paypal-button-container').animate({opacity: togglePPEC, height: togglePPEC, padding: togglePPEC}, 230);
        });
        $(document.body).on('removed_coupon_in_checkout', function () {
            window.location.href = window.location.href;
        });
    });
})(jQuery);