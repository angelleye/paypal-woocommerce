(function() {
       if(  is_page_name == "checkout_page"  ){
    jQuery(document).ready(function(e) {
        return e("form.checkout").length && e("body").hasClass("express-checkout") ? (e("ul.payment_methods").hide(), e(".ex-show-address-fields").on("click", function(b) {
            var c;
            return b.preventDefault(), c = e(this).data("type"), e(".woocommerce-" + c + "-fields .express-provided").removeClass("hidden"), e(this).closest(".express-provided-address").hide();
        })) : void 0;
    }); 
    }
}).call(this);