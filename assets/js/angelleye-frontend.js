jQuery(document).ready(function ($){
    if (angelleye_frontend.is_product == "yes"){
        jQuery("#paypal_ec_button_product input, input.single_variation_wrap_angelleye").click(function(){
       		if (!$('.woocommerce-variation-add-to-cart').hasClass('woocommerce-variation-add-to-cart-disabled')) {
	            $( '.cart' ).block({
	            message: null,
	            overlayCSS: {
	            background: '#fff',
	            opacity: 0.6
	            }
	            });
	            var angelleye_action = $(this).data('action');
	            $('form.cart').attr( 'action', angelleye_action );
	            $(this).attr('disabled', 'disabled');
	            $('form.cart').submit();
	            return false;
       		}
        });
    }
    if (angelleye_frontend.is_cart == "yes"){
        $(".paypal_checkout_button").click(function(){
            $('.woocommerce').block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
            var angelleye_action;
            if ($("#wc-paypal_express-new-payment-method").is(':checked')) {
                angelleye_action = $(this).attr("href");
                angelleye_action = angelleye_action + '&ec_save_to_account=true';
            } else {
                angelleye_action = $(this).attr("href");
            }
            $(this).attr("href", angelleye_action);
        });
    }
    
    if (angelleye_frontend.is_checkout == "yes"){
        var is_set_class = $("div").is(".express-provided-address");
        if (is_set_class) {
            jQuery('#express_checkout_button_chekout_page').hide();
            jQuery('#express_checkout_button_text').hide();
            jQuery('.woocommerce-message').hide();
            jQuery('#checkout_paypal_message').hide();
            }
        jQuery(".paypal_checkout_button").click(function () {
            $('.woocommerce').block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
            }
        });
            if ($("#wc-paypal_express-new-payment-method").is(':checked')) {
                angelleye_action = $(this).attr("href");
                angelleye_action = angelleye_action + '&ec_save_to_account=true';
            } else {
                angelleye_action = $(this).attr("href");
            }
            $(this).attr("href", angelleye_action);
            return true;
        });
    }
		// Let themes/plugins override our event handlers
		// NOTE: The jQuery .on() function attached to listen to the event below MUST
		// be included somewhere on the page itself...so it gets registered before we load
		// asyncronous JavaScript resources. Otherwise your function listening might not fire.

		$(".paypal_checkout_button").trigger('angelleye_paypal_checkout_button_js_loaded');
});
