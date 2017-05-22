<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_PayPal_Express_Response_AngellEYE {

    public function ec_get_shipping_details($response) {
        try {
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/NameParser.php' );
            $parser = new FullNameParser();
            $details = array();
            if (isset($response['SHIPTONAME'])) {
                $split_name = $parser->split_full_name($response['SHIPTONAME']);
                $shipping_first_name = $split_name['fname'];
                $shipping_last_name = $split_name['lname'];
                $details = array(
                    'first_name' => isset($shipping_first_name) ? $shipping_first_name : $response['FIRSTNAME'],
                    'last_name' => isset($shipping_last_name) ? $shipping_last_name : $response['LASTNAME'],
                    'company' => isset($response['BUSINESS']) ? $response['BUSINESS'] : '',
                    'email' => isset($response['EMAIL']) ? $response['EMAIL'] : '',
                    'phone' => isset($response['PHONENUM']) ? $response['PHONENUM'] : '',
                    'address_1' => isset($response['SHIPTOSTREET']) ? $response['SHIPTOSTREET'] : '',
                    'address_2' => isset($response['SHIPTOSTREET2']) ? $response['SHIPTOSTREET2'] : '',
                    'city' => isset($response['SHIPTOCITY']) ? $response['SHIPTOCITY'] : '',
                    'postcode' => isset($response['SHIPTOZIP']) ? $response['SHIPTOZIP'] : '',
                    'country' => isset($response['SHIPTOCOUNTRYCODE']) ? $response['SHIPTOCOUNTRYCODE'] : '',
                    'state' => (isset($response['SHIPTOCOUNTRYCODE']) && isset($response['SHIPTOSTATE'])) ? $this->ec_get_state_code($response['SHIPTOCOUNTRYCODE'], $response['SHIPTOSTATE']) : ''
                );
            }
            return $details;
        } catch (Exception $ex) {

        }
    }

    public function ec_get_state_code($country_code, $state) {
        try {
            if ($country_code !== 'US' && isset(WC()->countries->states[$country_code])) {
                $local_states = WC()->countries->states[$country_code];
                if (!empty($local_states) && in_array($state, $local_states)) {
                    foreach ($local_states as $key => $val) {
                        if ($val === $state) {
                            return $key;
                        }
                    }
                }
            }
            return $state;
        } catch (Exception $ex) {

        }
    }

    public function ec_get_note_text($response) {
        return isset($response['PAYMENTREQUEST_0_NOTETEXT']) ? $response['PAYMENTREQUEST_0_NOTETEXT'] : '';
    }

    public function ec_get_payer_id($response) {
        return isset($response['PAYERID']) ? $response['PAYERID'] : '';
    }

    public function ec_is_response_success($paypal_response) {
        if (strtoupper($paypal_response['ACK']) == 'SUCCESS') {
            return true;
        }
    }

    public function ec_is_response_success_or_successwithwarning($paypal_response) {
        if (strtoupper($paypal_response['ACK']) == 'SUCCESS' || strtoupper($paypal_response['ACK']) == "SUCCESSWITHWARNING") {
            return true;
        }
    }

    public function ec_is_response_successwithwarning($paypal_response) {
        if (strtoupper($paypal_response['ACK']) == 'SUCCESSWITHWARNING') {
            return true;
        }
    }
}
