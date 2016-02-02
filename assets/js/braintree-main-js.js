/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


jQuery(function ($) {
    'use strict';
   
       
        braintree.setup(paypal_for_woocommerce_braintree.Braintree_ClientToken, "custom", {id: "checkout_braintree"});
 
$(document).on("submit", "form", function(e){
    e.preventDefault();
    alert('it works!');
    return  false;
});
//    braintree.setup("<?php print $clientToken; ?>", "dropin", {container:
//                jQuery("#dropin"), form: jQuery("#checkout"),
//        paymentMethodNonceReceived: function (event, nonce) {
//            // do something
//        }
//    });


});