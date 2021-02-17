(function ($) {
    'use strict';
    $(function () {
        $('form.checkout').on('click', 'input[name="payment_method"]', function () {
            var isPPEC = $(this).is('#payment_method_angelleye_ppcp');
            var togglePPEC = isPPEC ? 'show' : 'hide';
            var toggleSubmit = isPPEC ? 'hide' : 'show';
            $('#paypal-button-container').animate({opacity: togglePPEC, height: togglePPEC, padding: togglePPEC}, 230);
            // $('#place_order').animate({opacity: toggleSubmit, height: toggleSubmit, padding: toggleSubmit}, 230);
        });
        

    });
})(jQuery);