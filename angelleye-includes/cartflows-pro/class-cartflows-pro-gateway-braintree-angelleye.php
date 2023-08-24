<?php

/**
 * Stripe Gateway.
 *
 * @package cartflows
 */

/**
 * Class Cartflows_Pro_Gateway_Braintree_AngellEYE.
 */
class Cartflows_Pro_Gateway_Braintree_AngellEYE {

    /**
     * Member Variable
     *
     * @var instance
     */
    private static $instance;
    public $is_api_refund = true;

    /**
     * Key name variable
     *
     * @var key
     */
    public $key = 'braintree';

    /**
     *  Initiator
     */
    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {

        add_filter('angelleye_braintree_store_in_vault_on_success', array($this, 'tokenize_if_required'), 10, 1);
    }

    /**
     * Get WooCommerce payment geteways.
     *
     * @return array
     */
    public function get_wc_gateway() {

        global $woocommerce;

        $gateways = $woocommerce->payment_gateways->payment_gateways();

        return $gateways[$this->key];
    }

    /**
     *  Force to save payment token
     *  @param bool $save_source
     */
    public function tokenize_if_required($save_source) {
        wcf()->logger->log('Started: ' . __CLASS__ . '::' . __FUNCTION__);

        $checkout_id = wcf()->utils->get_checkout_id_from_post_data();
        $flow_id = wcf()->utils->get_flow_id_from_post_data();

        if ($checkout_id && $flow_id) {

            $next_step_id = wcf()->utils->get_next_step_id($flow_id, $checkout_id);

            if (wcf()->utils->check_is_offer_page($next_step_id)) {

                $save_source = true;
                wcf()->logger->log('Force save source enabled');
            }
        }

        return $save_source;
    }

    /**
     * Check if token is present.
     *
     * @param array $order order data.
     */
    public function has_token($order) {

        $order_id = $order->get_id();

        $token = $order->get_meta( '_payment_tokens_id', true);

        if (empty($token)) {
            $token = $order->get_meta( '_payment_tokens_id', true);
        }

        if (!empty($token)) {
            return true;
        }

        return false;
    }

    /**
     * After payment process.
     *
     * @param array $order order data.
     * @param array $product product data.
     * @return array
     */
    public function process_offer_payment($order, $product) {
        try {
            $is_successful = false;
            if (!$this->has_token($order)) {
                return $is_successful;
            }
            $gateway = $this->get_wc_gateway();
            $order_id = $order->get_id();
            $request_data = array();
            $gateway->angelleye_braintree_lib($order_id);
            $request_data['billing'] = array(
                'firstName' => $order->get_billing_first_name(),
                'lastName' => $order->get_billing_last_name(),
                'company' => $order->get_billing_company(),
                'streetAddress' => $order->get_billing_address_1(),
                'extendedAddress' => $order->get_billing_address_2(),
                'locality' => $order->get_billing_city(),
                'region' => $order->get_billing_state(),
                'postalCode' => $order->get_billing_postcode(),
                'countryCodeAlpha2' => $order->get_billing_country(),
            );
            $request_data['shipping'] = array(
                'firstName' => $order->get_shipping_first_name(),
                'lastName' => $order->get_shipping_last_name(),
                'company' => $order->get_shipping_company(),
                'streetAddress' => $order->get_shipping_address_1(),
                'extendedAddress' => $order->get_shipping_address_2(),
                'locality' => $order->get_shipping_city(),
                'region' => $order->get_shipping_state(),
                'postalCode' => $order->get_shipping_postcode(),
                'countryCodeAlpha2' => $order->get_shipping_country(),
            );
            $request_data['paymentMethodToken'] = $order->get_meta( '_payment_tokens_id', true);
            if (is_user_logged_in()) {
                $customer_id = get_current_user_id();
                $braintree_customer_id = get_user_meta($customer_id, 'braintree_customer_id', true);
                if (!empty($braintree_customer_id)) {
                    $request_data['customerId'] = $braintree_customer_id;
                } else {
                    $request_data['customer'] = array(
                        'firstName' => $order->get_billing_first_name(),
                        'lastName' => $order->get_billing_last_name(),
                        'company' => $order->get_billing_company(),
                        'phone' => $order->get_billing_phone(),
                        'email' => $order->get_billing_email(),
                    );
                }
            }
            $request_data['amount'] = number_format($product['price'], 2, '.', '');
            $gateway->merchant_account_id = $gateway->angelleye_braintree_get_merchant_account_id($order_id);
            if (isset($gateway->merchant_account_id) && !empty($gateway->merchant_account_id)) {
                $request_data['merchantAccountId'] = $gateway->merchant_account_id;
            }
            $request_data['orderId'] = $order->get_order_number() . '-' . $product['step_id'];
            $request_data['options'] = $gateway->get_braintree_options();
            if ($gateway->enable_braintree_drop_in == false && $gateway->threed_secure_enabled === false) {
                $request_data['creditCard']['cardholderName'] = $order->get_formatted_billing_full_name();
            }
            if ($gateway->debug) {
                $gateway->add_log('Begin Braintree_Transaction::sale request');
                $gateway->add_log('Order: ' . print_r($order->get_order_number(), true));
            }
            try {
                $gateway->response = $gateway->braintree_gateway->transaction()->sale($request_data);
                do_action('angelleye_paypal_response_data', $gateway->response, $request_data, '1', $gateway->sandbox, false, 'braintree');
            } catch (\Braintree\Exception\Authentication $e) {
                $gateway->add_log("Braintree_Transaction::sale Braintree_Exception_Authentication: API keys are incorrect, Please double-check that you haven't accidentally tried to use your sandbox keys in production or vice-versa.");
                return $success = false;
            } catch (\Braintree\Exception\Authorization $e) {
                $gateway->add_log("Braintree_Transaction::sale Braintree_Exception_Authorization: The API key that you're using is not authorized to perform the attempted action according to the role assigned to the user who owns the API key.");
                return $success = false;
            } catch (\Braintree\Exception\ServiceUnavailable $e) {
                $gateway->add_log("Braintree_Transaction::sale Braintree_Exception_DownForMaintenance: Request times out.");
                return $success = false;
            } catch (\Braintree\Exception\ServerError $e) {
                $gateway->add_log("Braintree_Transaction::sale Braintree_Exception_ServerError " . $e->getMessage());
                return $success = false;
            } catch (\Braintree\Exception\SSLCertificate $e) {
                $gateway->add_log("Braintree_Transaction::sale Braintree_Exception_SSLCertificate " . $e->getMessage());
                return $success = false;
            } catch (Exception $e) {
                $gateway->add_log('Error: Unable to complete transaction. Reason: ' . $e->getMessage());
                return $success = false;
            }
            if (!$gateway->response->success) {
                $gateway->add_log("Error: Unable to complete transaction. Reason: {$gateway->response->message}");
                return $success = false;
            }
            $gateway->add_log('Braintree_Transaction::sale Response code: ' . print_r($gateway->get_status_code(), true));
            $gateway->add_log('Braintree_Transaction::sale Response message: ' . print_r($gateway->get_status_message(), true));
            $maybe_settled_later = array(
                'settling',
                'settlement_pending',
                'submitted_for_settlement',
            );
            if (in_array($gateway->response->transaction->status, $maybe_settled_later)) {
                $order->update_meta_data('is_sandbox', $gateway->sandbox);
                $order->add_order_note(sprintf(__('%s payment approved! Transaction ID: %s', 'paypal-for-woocommerce'), $gateway->title, $gateway->response->transaction->id));
                $this->store_offer_transaction($order, $gateway->response->transaction->id, $product);
                $order->save();
                return true;
            } else {
                $gateway->add_log(sprintf('Info: unhandled transaction id = %s, status = %s', $gateway->response->transaction->id, $gateway->response->transaction->status));
                $order->add_order_note(sprintf(__('Transaction was submitted to PayPal Braintree but not handled by WooCommerce order, transaction_id: %s, status: %s. Order was put in-hold.', 'paypal-for-woocommerce'), $gateway->response->transaction->id, $gateway->response->transaction->status));
                $order_id = $order->get_id();
                wc_maybe_reduce_stock_levels($order_id);
                $this->store_offer_transaction($order, $gateway->response->transaction->id, $product);
                return true;
            }
        } catch (Exception $ex) {
            return false;
        }
    }

    public function process_offer_refund($order, $offer_data) {
        $order_id = $offer_data['order_id'];
        $transaction_id = $offer_data['transaction_id'];
        $refund_amount = $offer_data['refund_amount'];
        $refund_reason = $offer_data['refund_reason'];
        $response_id = false;
        $gateway = $this->get_wc_gateway();
        $gateway->angelleye_braintree_lib($order_id);
        try {
            $transaction = $gateway->braintree_gateway->transaction()->find($order->get_transaction_id());
        } catch (Braintree\Exception\NotFound $e) {
            $gateway->add_log("Transaction::find() Braintree\Exception\NotFound" . $e->getMessage());
        } catch (Braintree\Exception\Authentication $e) {
            $gateway->add_log("Transaction::find() Braintree\Exception\Authentication: API keys are incorrect, Please double-check that you haven't accidentally tried to use your sandbox keys in production or vice-versa.");
        } catch (Braintree\Exception\Authorization $e) {
            $gateway->add_log("Transaction::find() Braintree\Exception\Authorization: The API key that you're using is not authorized to perform the attempted action according to the role assigned to the user who owns the API key.");
        } catch (Exception $e) {
            $gateway->add_log($e->getMessage());
        }
        if (isset($transaction->status) && $transaction->status == 'submitted_for_settlement') {
            try {
                $result = $gateway->braintree_gateway->transaction()->void($transaction_id);
                if ($result->success) {
                    do_action('angelleye_paypal_response_data', $result, $request_data = array(), '1', $gateway->sandbox, false, 'braintree');
                    $braintree_refunded_id = array();
                    $braintree_refunded_id[$result->transaction->id] = $result->transaction->id;
                    $order->add_order_note(sprintf(__('Refunded %s - Transaction ID: %s', 'paypal-for-woocommerce'), wc_price(number_format($refund_amount, 2, '.', '')), $result->transaction->id));
                    $order->update_meta_data('Refund Transaction ID', $result->transaction->id);
                    $order->update_meta_data('braintree_refunded_id', $braintree_refunded_id);
                    $order->save();
                    $response_id = $result->transaction->id;
                }
            } catch (Braintree\Exception\NotFound $e) {
                $gateway->add_log("Transaction::void() Braintree\Exception\NotFound: " . $e->getMessage());
            } catch (Exception $e) {
                $gateway->add_log("Transaction::void() Exception: " . $e->getMessage());
            }
        } elseif (isset($transaction->status) && ($transaction->status == 'settled' || $transaction->status == 'settling')) {
            try {
                $result = $gateway->braintree_gateway->transaction()->refund($order->get_transaction_id(), $refund_amount);
                if ($result->success) {
                    $braintree_refunded_id = array();
                    $braintree_refunded_id[$result->transaction->id] = $result->transaction->id;
                    $order->update_meta_data('braintree_refunded_id', $braintree_refunded_id);
                    $order->save();
                    $order->add_order_note(sprintf(__('Refunded %s - Transaction ID: %s', 'paypal-for-woocommerce'), wc_price(number_format($refund_amount, 2, '.', '')), $result->transaction->id));
                    $response_id = $result->transaction->id;
                }
            } catch (Braintree\Exception\NotFound $e) {
                $gateway->add_log("Transaction::refund() Braintree\Exception\NotFound: " . $e->getMessage());
            } catch (Exception $e) {
                $gateway->add_log("Transaction::refund() Exception: " . $e->getMessage());
            }
        } else {
            $gateway->add_log("Error: The transaction cannot be voided nor refunded in its current state: state = {$transaction->status}");
        }
        return $response_id;
    }

    public function is_api_refund() {
        return $this->is_api_refund;
    }

    public function store_offer_transaction($order, $response, $product) {
        $order->update_meta_data('cartflows_offer_txn_resp_' . $product['step_id'], $response);
        $order->save();
    }

}

/**
 *  Prepare if class 'Cartflows_Pro_Gateway_Braintree_AngellEYE' exist.
 *  Kicking this off by calling 'get_instance()' method
 */
Cartflows_Pro_Gateway_Braintree_AngellEYE::get_instance();
