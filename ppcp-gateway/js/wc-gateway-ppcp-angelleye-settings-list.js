jQuery(function ($) {
    $('tr[data-gateway_id="paypal_express"] td.status > a').hide();
    $('tr[data-gateway_id="paypal_express"] td.action > a').text('View List');
    $('tr[data-gateway_id="paypal_express"] td.sort  > div').append('<input type="hidden" name="gateway_order[]" value="paypal_pro" /><input type="hidden" name="gateway_order[]" value="paypal_pro_payflow" /><input type="hidden" name="gateway_order[]" value="paypal_advanced" /><input type="hidden" name="gateway_order[]" value="paypal_credit_card_rest" />');
});