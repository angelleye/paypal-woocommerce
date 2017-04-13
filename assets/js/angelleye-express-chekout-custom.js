jQuery(function($) {    
    if( is_page_name == "single_product_page" ){        
        jQuery("a.single_add_to_cart_button.paypal_checkout_button").click(function() {
            if (!$('.woocommerce-variation-add-to-cart').hasClass('woocommerce-variation-add-to-cart-disabled')) {
                block_div_to_payment();
            var express_checkout_action = $(this).attr('href');
            $(this).append("<input type='hidden' name='express_checkout_button_product_page' value=" + express_checkout_action + " /> ");
            $('form.variations_form').attr('action', express_checkout_action);
            $(this).attr('disabled', 'disabled');
            $('form.variations_form').submit();
            return true;
            }
        });
        
    } else if(  is_page_name == "checkout_page"  ){        
        jQuery("a.single_add_to_cart_button").click(function () {           
            block_div_to_payment();
        });
var is_set_class = $("div").is(".express-provided-address");
if( is_set_class ){
        jQuery('#express_checkout_button_chekout_page').hide();
        jQuery('#express_checkout_button_text').hide();
            jQuery('.woocommerce-message').hide();
            jQuery('.woocommerce-info').hide();            
        }
        
    } else if(is_page_name == "cart_page"){
        jQuery("a.single_add_to_cart_button.paypal_checkout_button").click(function () {
        block_div_to_payment();
    });
    }
   
    function block_div_to_payment() {
        var ec_class = "";
         if (is_page_name == 'cart_page') {
            ec_class = "div.wc-proceed-to-checkout";
        } else if (is_page_name == 'single_product_page') {
            ec_class = "form.cart";
        } else if (is_page_name == 'checkout_page') {
            ec_class = "div.woocommerce";
        }
        if (ec_class.toString().length > 0) {
            $(ec_class).block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        }
    }
});