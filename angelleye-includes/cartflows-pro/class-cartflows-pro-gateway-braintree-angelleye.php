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

        $token = get_post_meta($order_id, '_payment_tokens_id', true);

        if (empty($token)) {
            $token = get_post_meta($order_id, '_payment_tokens_id', true);
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

            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            $request_data = array();
            $gateway->angelleye_braintree_lib($order_id);
            $billing_company = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_company : $order->get_billing_company();
            $billing_first_name = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_first_name : $order->get_billing_first_name();
            $billing_last_name = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_last_name : $order->get_billing_last_name();
            $billing_address_1 = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_address_1 : $order->get_billing_address_1();
            $billing_address_2 = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_address_2 : $order->get_billing_address_2();
            $billing_city = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_city : $order->get_billing_city();
            $billing_postcode = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_postcode : $order->get_billing_postcode();
            $billing_country = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_country : $order->get_billing_country();
            $billing_state = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_state : $order->get_billing_state();

            $request_data['billing'] = array(
                'firstName' => $billing_first_name,
                'lastName' => $billing_last_name,
                'company' => $billing_company,
                'streetAddress' => $billing_address_1,
                'extendedAddress' => $billing_address_2,
                'locality' => $billing_city,
                'region' => $billing_state,
                'postalCode' => $billing_postcode,
                'countryCodeAlpha2' => $billing_country,
            );

            $request_data['shipping'] = array(
                'firstName' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_first_name : $order->get_shipping_first_name(),
                'lastName' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_last_name : $order->get_shipping_last_name(),
                'company' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_company : $order->get_shipping_company(),
                'streetAddress' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_address_1 : $order->get_shipping_address_1(),
                'extendedAddress' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_address_2 : $order->get_shipping_address_2(),
                'locality' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_city : $order->get_shipping_city(),
                'region' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_state : $order->get_shipping_state(),
                'postalCode' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_postcode : $order->get_shipping_postcode(),
                'countryCodeAlpha2' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_country : $order->get_shipping_country(),
            );

            $request_data['paymentMethodToken'] = get_post_meta($order_id, '_payment_tokens_id', true);

            if (is_user_logged_in()) {
                $customer_id = get_current_user_id();
                $braintree_customer_id = get_user_meta($customer_id, 'braintree_customer_id', true);
                if (!empty($braintree_customer_id)) {
                    $request_data['customerId'] = $braintree_customer_id;
                } else {
                    $request_data['customer'] = array(
                        'firstName' => version_compare(WC_VERSION, '3.0', '<') ? $order->billing_first_name : $order->get_billing_first_name(),
                        'lastName' => version_compare(WC_VERSION, '3.0', '<') ? $order->billing_last_name : $order->get_billing_last_name(),
                        'company' => version_compare(WC_VERSION, '3.0', '<') ? $order->billing_company : $order->get_billing_company(),
                        'phone' => version_compare(WC_VERSION, '3.0', '<') ? $order->billing_phone : $order->get_billing_phone(),
                        'email' => version_compare(WC_VERSION, '3.0', '<') ? $order->billing_email : $order->get_billing_email(),
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
                $gateway->response = Braintree_Transaction::sale($request_data);
                do_action('angelleye_paypal_response_data', $gateway->response, $request_data, '1', $gateway->sandbox, false, 'braintree');
            } catch (Braintree_Exception_Authentication $e) {
                $gateway->add_log("Braintree_Transaction::sale Braintree_Exception_Authentication: API keys are incorrect, Please double-check that you haven't accidentally tried to use your sandbox keys in production or vice-versa.");
                return $success = false;
            } catch (Braintree_Exception_Authorization $e) {
                $gateway->add_log("Braintree_Transaction::sale Braintree_Exception_Authorization: The API key that you're using is not authorized to perform the attempted action according to the role assigned to the user who owns the API key.");
                return $success = false;
            } catch (Braintree_Exception_DownForMaintenance $e) {
                $gateway->add_log("Braintree_Transaction::sale Braintree_Exception_DownForMaintenance: Request times out.");
                return $success = false;
            } catch (Braintree_Exception_ServerError $e) {
                $gateway->add_log("Braintree_Transaction::sale Braintree_Exception_ServerError " . $e->getMessage());
                return $success = false;
            } catch (Braintree_Exception_SSLCertificate $e) {
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
                update_post_meta($order_id, 'is_sandbox', $gateway->sandbox);
                $order->add_order_note(sprintf(__('%s payment approved! Transaction ID: %s', 'paypal-for-woocommerce'), $gateway->title, $gateway->response->transaction->id));
                return true;
            } else {
                $gateway->add_log(sprintf('Info: unhandled transaction id = %s, status = %s', $gateway->response->transaction->id, $gateway->response->transaction->status));
                $order->add_order_note(sprintf(__('Transaction was submitted to PayPal Braintree but not handled by WooCommerce order, transaction_id: %s, status: %s. Order was put in-hold.', 'paypal-for-woocommerce'), $gateway->response->transaction->id, $gateway->response->transaction->status));
                $old_wc = version_compare(WC_VERSION, '3.0', '<');
                $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
                if ($old_wc) {
                    if (!get_post_meta($order_id, '_order_stock_reduced', true)) {
                        $order->reduce_order_stock();
                    }
                } else {
                    wc_maybe_reduce_stock_levels($order_id);
                }
                return true;
            }
            
        } catch (Exception $ex) {
            return false;
        }
    }

}

/**
 *  Prepare if class 'Cartflows_Pro_Gateway_Braintree_AngellEYE' exist.
 *  Kicking this off by calling 'get_instance()' method
 */
Cartflows_Pro_Gateway_Braintree_AngellEYE::get_instance();
