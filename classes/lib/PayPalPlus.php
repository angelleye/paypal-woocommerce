<?php

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Payment;
use PayPal\Api\Payer;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\ItemList;
use PayPal\Api\RedirectUrls;
use PayPal\Api\PaymentExecution;
use PayPal\Api\CreditCard;
use PayPal\Api\CreditCardToken;
use PayPal\Api\FundingInstrument;
use PayPal\Api\ShippingAddress;
use PayPal\Api\Address;
use PayPal\Api\Details;
use PayPal\Api\ExecutePayment;

//function gets access token from PayPal
function apiContext() {
    $apiContext = new ApiContext(new OAuthTokenCredential(CLIENT_ID, CLIENT_SECRET));
    return $apiContext;
}

//create PayPal payment method
function create_paypal_payment($total, $currency, $desc, $my_items, $redirect_url, $cancel_url) {
    $redirectUrls = new RedirectUrls();
    $redirectUrls->setReturnUrl($redirect_url);
    $redirectUrls->setCancelUrl($cancel_url);
    if (isset($_SESSION["get_order_details"]) && !empty($_SESSION["get_order_details"])) {
        $order_details = $_SESSION["get_order_details"];
    }
    $payer = new Payer();
    $payer->setPaymentMethod("paypal");

    $details = new Details();
    if ((isset($order_details['Payments'][0]['shippingamt']) && !empty($order_details['Payments'][0]['shippingamt'])) && (isset($order_details['Payments'][0]['taxamt']) && !empty($order_details['Payments'][0]['taxamt']))) {
        $details->setShipping($order_details['Payments'][0]['shippingamt'])
            ->setTax($order_details['Payments'][0]['taxamt'])
            ->setSubtotal($order_details['Payments'][0]['itemamt']);
    } elseif ((isset($order_details['Payments'][0]['shippingamt']) && !empty($order_details['Payments'][0]['shippingamt'])) && (empty($order_details['Payments'][0]['taxamt']))) {
        $details->setShipping($order_details['Payments'][0]['shippingamt'])
            ->setSubtotal($order_details['Payments'][0]['itemamt']);
    } elseif ((empty($order_details['Payments'][0]['shippingamt'])) && (isset($order_details['Payments'][0]['taxamt']) && !empty($order_details['Payments'][0]['taxamt']))) {
        $details->setTax($order_details['Payments'][0]['taxamt'])
            ->setSubtotal($order_details['ITEMAMT']);
    }



    $amount = new Amount();
    $amount->setCurrency($currency);
    $amount->setTotal($total);
    $amount->setDetails($details);

    $items = new ItemList();
    $items->setItems($my_items);


    $shipping_address = new ShippingAddress();

    $itemList = new ItemList();
//$itemList->setShippingAddress($shipping_address);

    $transaction = new Transaction();
    $transaction->setAmount($amount);
    $transaction->setDescription($desc);
    $transaction->setItemList($items);

    $payment = new Payment();
    $payment->setRedirectUrls($redirectUrls);
    $payment->setIntent("sale");
    $payment->setPayer($payer);
    $payment->setTransactions(array($transaction));

    $payment->create(apiContext());

    return $payment;
}

//executes PayPal payment
function execute_payment($payment_id, $payer_id) {



    $execution = new PaymentExecution();
    $execution->setPayerId($payer_id);

    try {
        // Execute the payment
        // (See bootstrap.php for more on `ApiContext`)
        $payment = Payment::get($payment_id, apiContext());
        $write_log = new WC_Gateway_PayPal_Plus_AngellEYE();

        $result = $payment->execute($execution, apiContext());

        try {
            $payment = Payment::get($payment_id, apiContext());
        } catch (Exception $ex) {

            return $payment;
        }
    } catch (Exception $ex) {

        $ex->getMessage();
        return $payment;
    }



    return $payment;
}

//pay with credit card
function pay_direct_with_credit_card($credit_card_params, $currency, $amount_total, $my_items, $payment_desc) {

    $card = new CreditCard();
    $card->setType($credit_card_params['type']);
    $card->setNumber($credit_card_params['number']);
    $card->setExpireMonth($credit_card_params['expire_month']);
    $card->setExpireYear($credit_card_params['expire_year']);
    $card->setCvv2($credit_card_params['cvv2']);
    $card->setFirstName($credit_card_params['first_name']);
    $card->setLastName($credit_card_params['last_name']);

    $funding_instrument = new FundingInstrument();
    $funding_instrument->setCreditCard($card);

    $payer = new Payer();
    $payer->setPayment_method("credit_card");
    $payer->setFundingInstruments(array($funding_instrument));

    $amount = new Amount();
    $amount->setCurrency($currency);
    $amount->setTotal($amount_total);

    $transaction = new Transaction();
    $transaction->setAmount($amount);
    $transaction->setDescription("creating a direct payment with credit card");

    $payment = new Payment();
    $payment->setIntent("sale");
    $payment->setPayer($payer);
    $payment->setTransactions(array($transaction));

    $payment->create(apiContext());

    return $payment;
}