(function() {
       if(  angelleye_js_value.is_page_name == "checkout_page"){
    jQuery(document).ready(function(e) {
        return e("form.checkout").length && e("body").hasClass("express-checkout") ? (e("ul.payment_methods").hide(), e(".ex-show-address-fields").on("click", function(b) {
            var c;
            return b.preventDefault(), c = e(this).data("type"), e(".woocommerce-" + c + "-fields .express-provided").removeClass("hidden"), e(this).closest(".express-provided-address").hide();
        })) : void 0;
    }); 
    }
}).call(this);

jQuery(document).ready(function ($) {
var is_set_class = $("div").is(".express-provided-address");
if (is_set_class) {
    jQuery('#express_checkout_button_chekout_page').hide();
    jQuery('#express_checkout_button_text').hide();
    jQuery('.woocommerce-message').hide();
    jQuery('#checkout_paypal_message').hide();
    jQuery('.express-checkout.express-hide-terms .wc-terms-and-conditions input[type="checkbox"]').prop('checked', true);
}
});