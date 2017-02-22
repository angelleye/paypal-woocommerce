<?php

use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use PayPal\Api\Amount;
use PayPal\Api\CreditCard;
use PayPal\Api\CreditCardToken;
use PayPal\Api\Details;
use PayPal\Api\FundingInstrument;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\Transaction;
use PayPal\Api\Refund;
use PayPal\Api\Sale;

class PayPal_Rest_API_Utility {

    public $card;
    public $FundingInstrument;
    public $Payer;
    public $order_item;
    public $item;
    public $item_list;
    public $details;
    public $payment_data;
    public $amount;
    public $transaction;
    public $payment;
    public $payment_method;
    public $gateway;
    public $CreditCardToken;

    public function __construct() {
        $this->add_paypal_rest_api_lib();
        $this->create_transaction_method_obj();
        $this->payment_method = (isset($_POST['payment_method'])) ? $_POST['payment_method'] : 'paypal_credit_card_rest';
        if ($this->payment_method == 'paypal_credit_card_rest') {
            $this->gateway = new WC_Gateway_PayPal_Credit_Card_Rest_AngellEYE();
        }
        $this->testmode = 'yes' === $this->gateway->get_option('testmode', 'no');
        $this->mode = $this->testmode == 'yes' ? 'SANDBOX' : 'LIVE';
        $this->debug = 'yes' === $this->gateway->get_option('debug', 'no');
        if ($this->testmode) {
            $this->rest_client_id = $this->gateway->get_option('rest_client_id_sandbox', false);
            $this->rest_secret_id = $this->gateway->get_option('rest_secret_id_sandbox', false);
        } else {
            $this->rest_client_id = $this->gateway->get_option('rest_client_id', false);
            $this->rest_secret_id = $this->gateway->get_option('rest_secret_id', false);
        }
    }

    /**
     * @since    1.2
     * @global type $woocommerce
     * @param type $order
     * @param type $card_data
     * @return type
     */
    public function create_payment($order, $card_data) {
        global $woocommerce;
        try {
            $this->set_trnsaction_obj_value($order, $card_data);
            try {
                $this->add_log(print_r($this->payment, true));
                $this->payment->create($this->getAuth());
            } catch (PayPal\Exception\PayPalConnectionException $ex) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'woo-paypal-plus'), 'error');
                return array(
                    'result' => 'fail',
                    'redirect' => ''
                );
            } catch (Exception $ex) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'woo-paypal-plus'), 'error');
                $this->add_log($ex->getMessage());
                return array(
                    'result' => 'fail',
                    'redirect' => ''
                );
            }
            
            if ($this->payment->state == "approved") {
                $transactions = $this->payment->getTransactions();
                $relatedResources = $transactions[0]->getRelatedResources();
                $sale = $relatedResources[0]->getSale();
                $saleId = $sale->getId();
                do_action('before_save_payment_token', $order->id);
                $order->add_order_note(__('PayPal Credit Card (REST) payment completed', 'paypal-for-woocommerce'));
                if(!empty($_POST['wc-paypal_credit_card_rest-payment-token']) && $_POST['wc-paypal_credit_card_rest-payment-token'] == 'new') {
                    if(!empty($_POST['wc-paypal_credit_card_rest-new-payment-method']) && $_POST['wc-paypal_credit_card_rest-new-payment-method'] == true) {
                        try {
                            $this->card->create($this->getAuth());
                            $customer_id =  $order->get_user_id();
                            $creditcard_id = $this->card->getId();
                            $token = new WC_Payment_Token_CC();
                            $token->set_user_id( $customer_id );
                            $token->set_token( $creditcard_id );
                            $token->set_gateway_id( $this->payment_method );
                            $token->set_card_type( $this->card->type );
                            $token->set_last4( substr( $this->card->number, -4 ) );
                            $token->set_expiry_month( $this->card->expire_month );
                            $token->set_expiry_year( $this->card->expire_year );
                            $save_result = $token->save();
                            if ( $save_result ) {
                                    $order->add_payment_token( $token );
                            }
                        } catch (Exception $ex) {

                        }
                        
                    }
                }
                $order->payment_complete($saleId);
                $is_sandbox = $this->mode == 'SANDBOX' ? true : false;
                update_post_meta($order->id, 'is_sandbox', $is_sandbox);
                WC()->cart->empty_cart();
                $return_url = $order->get_checkout_order_received_url();
                if (is_ajax()) {
                    wp_send_json(array(
                        'result' => 'success',
                        'redirect' => apply_filters('woocommerce_checkout_no_payment_needed_redirect', $return_url, $order)
                    ));
                } else {
                    wp_safe_redirect(
                            apply_filters('woocommerce_checkout_no_payment_needed_redirect', $return_url, $order)
                    );
                    exit;
                }
            } else {
                wc_add_notice(__('Error Payment state:' . $this->payment->state, 'paypal-for-woocommerce'), 'error');
                $this->add_log(__('Error Payment state:' . $this->payment->state, 'paypal-for-woocommerce'));
                return array(
                    'result' => 'fail',
                    'redirect' => ''
                );
            }
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
            $this->add_log($ex->getData());
            return array(
                'result' => 'fail',
                'redirect' => ''
            );
            exit;
        } catch (Exception $ex) {
            wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
            $this->add_log($ex->getMessage());
            return array(
                'result' => 'fail',
                'redirect' => ''
            );
        }
    }

    /**
     * @since    1.2
     * @param type $order
     * @param type $card_data
     */
    public function set_trnsaction_obj_value($order, $card_data) {
        if(!empty($_POST['wc-paypal_credit_card_rest-payment-token']) && $_POST['wc-paypal_credit_card_rest-payment-token'] != 'new') {
            $token_id = wc_clean( $_POST['wc-paypal_credit_card_rest-payment-token'] );
            $token = WC_Payment_Tokens::get( $token_id );
            $this->CreditCardToken = new CreditCardToken();
            $this->CreditCardToken->setCreditCardId($token->get_token());
            $this->fundingInstrument = new FundingInstrument();
            $this->fundingInstrument->setCreditCardToken($this->CreditCardToken);
        } else {
            $this->set_card_details($order, $card_data);
            $this->fundingInstrument = new FundingInstrument();
            $this->fundingInstrument->setCreditCard($this->card);
        }
        
        $this->payer = new Payer();
        $this->payer->setPaymentMethod("credit_card");
        $this->payer->setFundingInstruments(array($this->fundingInstrument));
        $this->set_item($order);
        $this->set_item_list();
        $this->set_detail_values();
        $this->set_amount_values($order);
        $this->set_transaction();
        $this->set_payment();
    }

    /**
     * @since    1.2
     * @param type $order
     */
    public function set_item($order) {
        $this->payment_data = AngellEYE_Gateway_Paypal::calculate($order, $this->send_items);
        foreach ($this->payment_data['order_items'] as $item) {
            $this->item = new Item();
            $this->item->setName($item['name']);
            $this->item->setCurrency($order->get_order_currency());
            $this->item->setQuantity($item['qty']);
            $this->item->setPrice($item['amt']);
            array_push($this->order_item, $this->item);
        }
    }

    /**
     * @since    1.2
     */
    public function set_item_list() {
        $this->item_list = new ItemList();
        $this->item_list->setItems($this->order_item);
    }

    /**
     * @since    1.2
     */
    public function set_detail_values() {
        $this->details = new Details();
        if (isset($this->payment_data['shippingamt'])) {
            $this->details->setShipping($this->payment_data['shippingamt']);
        }
        if (isset($this->payment_data['taxamt'])) {
            $this->details->setTax($this->payment_data['taxamt']);
        }
        if ($this->payment_data['itemamt']) {
            $this->details->setSubtotal($this->payment_data['itemamt']);
        }
    }

    /**
     * @since    1.2
     * @param type $order
     */
    public function set_amount_values($order) {
        $this->amount = new Amount();
        $this->amount->setCurrency($order->get_order_currency());
        $this->amount->setTotal($this->number_format($order->get_total(), $order));
        $this->amount->setDetails($this->details);
    }

    /**
     * @since    1.2
     */
    public function set_transaction() {
        $this->transaction = new Transaction();
        $this->transaction->setAmount($this->amount);
        $this->transaction->setItemList($this->item_list);
        $this->transaction->setDescription("Payment description");
        $this->transaction->setInvoiceNumber(uniqid());
    }

    /**
     * @since    1.2
     */
    public function set_payment() {
        $this->payment = new Payment();
        $this->payment->setIntent("sale");
        $this->payment->setPayer($this->payer);
        $this->payment->setTransactions(array($this->transaction));
    }

    /**
     * @since    1.2
     * @return ApiContext
     */
    public function getAuth() {
        $this->mode = $this->testmode == 'yes' ? 'SANDBOX' : 'LIVE';
        $auth = new ApiContext(new OAuthTokenCredential($this->rest_client_id, $this->rest_secret_id));
        $auth->setConfig(array('mode' => $this->mode, 'http.headers.PayPal-Partner-Attribution-Id' => 'AngellEYE_SP_WooCommerce', 'log.LogEnabled' => true, 'log.LogLevel' => 'DEBUG', 'log.FileName' => wc_get_log_file_path('paypal_credit_card_rest')));
        return $auth;
    }

    /**
     * @since    1.2
     * @param type $order
     * @param type $card_data
     */
    public function set_card_details($order, $card_data) {
        $this->set_card_type($card_data);
        $this->set_card_number($card_data);
        $this->set_card_expire_month($card_data);
        $this->set_card_expire_year($card_data);
        $this->set_card_cvv($card_data);
        $this->set_card_first_name($order);
        $this->set_card_set_last_name($order);
    }

    /**
     * @since    1.2
     * @param type $card_data
     */
    public function set_card_type($card_data) {
        $first_four = substr($card_data->number, 0, 4);
        $card_type = AngellEYE_Utility::card_type_from_account_number($first_four);
        $this->card->setType($card_type);
    }

    /**
     * @since    1.2
     * @param type $card_data
     */
    public function set_card_number($card_data) {
        $this->card->setNumber($card_data->number);
    }

    /**
     * @since    1.2
     * @param type $card_data
     */
    public function set_card_expire_month($card_data) {
        $this->card->setExpireMonth($card_data->exp_month);
    }

    /**
     * @since    1.2
     * @param type $card_data
     */
    public function set_card_expire_year($card_data) {
        $this->card->setExpireYear($card_data->exp_year);
    }

    /**
     * @since    1.2
     * @param type $card_data
     */
    public function set_card_cvv($card_data) {
        $this->card->setCvv2($card_data->cvc);
    }

    /**
     * @since    1.2
     * @param type $order
     */
    public function set_card_first_name($order) {
        $this->card->setFirstName($order->billing_first_name);
    }

    /**
     * @since    1.2
     * @param type $order
     */
    public function set_card_set_last_name($order) {
        $this->card->setLastName($order->billing_last_name);
    }

    /**
     * @since    1.2
     */
    public function create_transaction_method_obj() {
        
        $this->card = new CreditCard();
        $this->order_item = array();
        $this->send_items = true;
    }

    /**
     * @since    1.2
     * @return type
     */
    public function add_paypal_rest_api_lib() {
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }
        if (!class_exists('Angelleye_PayPal')) {
            //require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/autoload.php' );
        }
        require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/autoload.php' );
    }

    /**
     * @since    1.2
     * @param type $message
     */
    public function add_log($message) {
        if ($this->debug == 'yes' && $this->mode == 'LIVE') {
            if (empty($this->log)) {
                $this->log = new WC_Logger();
            }
            $this->log->add('paypal_credit_card_rest', $message);
        }
    }

    /**
     * @since    1.2
     * @return type
     */
    public function get_posted_card() {
        $card_number = isset($_POST['paypal_credit_card_rest-card-number']) ? wc_clean($_POST['paypal_credit_card_rest-card-number']) : '';
        $card_cvc = isset($_POST['paypal_credit_card_rest-card-cvc']) ? wc_clean($_POST['paypal_credit_card_rest-card-cvc']) : '';
        $card_expiry = isset($_POST['paypal_credit_card_rest-card-expiry']) ? wc_clean($_POST['paypal_credit_card_rest-card-expiry']) : '';
        $card_number = str_replace(array(' ', '-'), '', $card_number);
        $card_expiry = array_map('trim', explode('/', $card_expiry));
        $card_exp_month = str_pad($card_expiry[0], 2, "0", STR_PAD_LEFT);
        $card_exp_year = isset($card_expiry[1]) ? $card_expiry[1] : '';
        if (strlen($card_exp_year) == 2) {
            $card_exp_year += 2000;
        }
        return (object) array(
                    'number' => $card_number,
                    'type' => '',
                    'cvc' => $card_cvc,
                    'exp_month' => $card_exp_month,
                    'exp_year' => $card_exp_year,
        );
    }

    /**
     * @since    1.2
     */
    public function add_dependencies_admin_notices() {
        $missing_extensions = $this->get_missing_dependencies();
        if (count($missing_extensions) > 0) {
            $message = sprintf(
                    _n(
                            '%s requires the %s PHP extension to function.  Contact your host or server administrator to configure and install the missing extension.', '%s requires the following PHP extensions to function: %s.  Contact your host or server administrator to configure and install the missing extensions.', count($missing_extensions), 'paypal-for-woocommerce'
                    ), "PayPal Credit Card (REST)", '<strong>' . implode(', ', $missing_extensions) . '</strong>'
            );
            echo '<div class="error"><p>' . $message . '</p></div>';
        }
    }

    /**
     * @since    1.2
     * @return type
     */
    public function get_missing_dependencies() {
        $missing_extensions = array();
        foreach ($this->get_dependencies() as $ext) {
            if (!extension_loaded($ext)) {
                $missing_extensions[] = $ext;
            }
        }
        return $missing_extensions;
    }

    /**
     * @since    1.2
     * @return type
     */
    public function get_dependencies() {
        return array('curl', 'json', 'openssl');
    }

    /**
     * @since    1.2
     * @param type $order_id
     * @param type $amount
     * @param type $reason
     * @return \WP_Error|boolean
     */
    public function payment_Refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        $this->add_log('Begin Refund');
        $this->add_log('Order: ' . print_r($order, true));
        $this->add_log('Transaction ID: ' . print_r($order->get_transaction_id(), true));
        if (!$order || !$order->get_transaction_id() || !$this->rest_client_id || !$this->rest_secret_id) {
            return false;
        }
        if ($reason) {
            if (255 < strlen($reason)) {
                $reason = substr($reason, 0, 252) . '...';
            }

            $reason = html_entity_decode($reason, ENT_NOQUOTES, 'UTF-8');
        }
        $sale = Sale::get($order->get_transaction_id(), $this->getAuth());
        $this->amount = new Amount();
        $this->amount->setCurrency($order->get_order_currency());
        $this->amount->setTotal($this->number_format($amount, $order));
        $refund = new Refund();
        $refund->setAmount($this->amount);
        try {
            $this->add_log('Refund Request: ' . print_r($refund, true));
            $refundedSale = $sale->refund($refund, $this->getAuth());
            if ($refundedSale->state == 'completed') {
                $order->add_order_note('Refund Transaction ID:' . $refundedSale->getId());
                if (isset($reason) && !empty($reason)) {
                    $order->add_order_note('Reason for Refund :' . $reason);
                }
                $max_remaining_refund = wc_format_decimal($order->get_total() - $order->get_total_refunded());
                if (!$max_remaining_refund > 0) {
                    $order->update_status('refunded');
                }
                return true;
            }
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            $this->add_log($ex->getData());
            $error_data = json_decode($ex->getData());
            if (is_object($error_data) && !empty($error_data)) {
                $error_message = ($error_data->message) ? $error_data->message : $error_data->information_link;
                return new WP_Error('paypal_credit_card_rest_refund-error', $error_message);
            } else {
                return new WP_Error('paypal_credit_card_rest_refund-error', $ex->getData());
            }
        } catch (Exception $ex) {
            $this->add_log($ex->getMessage());
            return new WP_Error('paypal_credit_card_rest_refund-error', $ex->getMessage());
        }
    }

    /**
     * @since    1.2
     * @param type $currency
     * @return boolean
     */
    public function currency_has_decimals($currency) {
        if (in_array($currency, array('HUF', 'JPY', 'TWD'))) {
            return false;
        }
        return true;
    }

    /**
     * @since    1.2
     * @param type $price
     * @param type $order
     * @return type
     */
    public function round($price, $order) {
        $precision = 2;
        if (!$this->currency_has_decimals($order->get_order_currency())) {
            $precision = 0;
        }
        return round($price, $precision);
    }

    /**
     * @since    1.2
     * @param type $price
     * @param type $order
     * @return type
     */
    public function number_format($price, $order) {
        $decimals = 2;
        if (!$this->currency_has_decimals($order->get_order_currency())) {
            $decimals = 0;
        }
        return number_format($price, $decimals, '.', '');
    }
    
    public function save_credit_card($card_data) {
        $customer_id = get_current_user_id();
        $this->card = new CreditCard();
        $this->set_card_type($card_data);
        $this->set_card_number($card_data);
        $this->set_card_expire_month($card_data);
        $this->set_card_expire_year($card_data);
        $this->set_card_cvv($card_data);
        
        $billtofirstname = (get_user_meta( $customer_id, 'billing_first_name', true )) ? get_user_meta( $customer_id, 'billing_first_name', true ) : get_user_meta( $customer_id, 'shipping_first_name', true );
        $billtolastname = (get_user_meta( $customer_id, 'billing_last_name', true )) ? get_user_meta( $customer_id, 'billing_last_name', true ) : get_user_meta( $customer_id, 'shipping_last_name', true );
        
        $this->card->setFirstName($billtofirstname);
        $this->card->setLastName($billtolastname);
        $this->card->setMerchantId(get_bloginfo('name').'_'.$customer_id.'_'.uniqid());
        $this->card->setExternalCardId($card_data->number.'_'.uniqid());
        $this->card->setExternalCustomerId($card_data->number.'_'.$customer_id.'_'.uniqid());
        
        try {
            $this->card->create($this->getAuth());
            if($this->card->state == 'ok') {
                $customer_id = get_current_user_id();
                $creditcard_id = $this->card->getId();
                $token = new WC_Payment_Token_CC();
                $token->set_user_id( $customer_id );
                $token->set_token( $creditcard_id );
                $token->set_gateway_id( $this->payment_method );
                $token->set_card_type( $this->card->type );
                $token->set_last4( substr( $this->card->number, -4 ) );
                $token->set_expiry_month( $this->card->expire_month );
                $token->set_expiry_year( $this->card->expire_year );
                $save_result = $token->save();
                if ( $save_result ) {
                    return array(
                        'result' => 'success',
                        'redirect' => wc_get_account_endpoint_url( 'payment-methods' )
                    );
                    
                }
            } else {
                    wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                    return array(
                        'result' => 'fail',
                        'redirect' => wc_get_account_endpoint_url( 'payment-methods' )
                    );
            }
        } catch (Exception $ex) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->add_log($ex->getMessage());
                return array(
                    'result' => 'fail',
                    'redirect' => ''
                );
        }
        
    }

}