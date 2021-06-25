jQuery(function ($) {
    $('tr[data-gateway_id="paypal_express"] td.status > a').hide();
    $('tr[data-gateway_id="paypal_express"] td.action > a').text('View List');
});
   