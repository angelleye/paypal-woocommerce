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
                $company = !empty($response['BUSINESS']) ? str_replace("\\","", $response['BUSINESS']) : '';
                $phone = '';
                if( !empty($response['SHIPTOPHONENUM']) ) {
                    $phone = $response['SHIPTOPHONENUM'];
                } elseif( !empty($response['PHONENUM']) ) {
                    $phone = $response['PHONENUM'];
                }
                $details = array(
                    'first_name' => isset($shipping_first_name) ? $shipping_first_name : $response['FIRSTNAME'],
                    'last_name' => isset($shipping_last_name) ? $shipping_last_name : $response['LASTNAME'],
                    'company' => $company,
                    'email' => isset($response['EMAIL']) ? $response['EMAIL'] : '',
                    'phone' => $phone,
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

    public function ec_get_state_code($country, $state) {
        try {
            $valid_states = WC()->countries->get_states($country);
            if (!empty($valid_states) && is_array($valid_states)) {
                $valid_state_values = array_flip(array_map('strtolower', $valid_states));
                if (isset($valid_state_values[strtolower($state)])) {
                    $state_value = $valid_state_values[strtolower($state)];
                    return $state_value;
                }
            } else {
                return $state;
            }
            if (!empty($valid_states) && is_array($valid_states) && sizeof($valid_states) > 0) {
                if (!in_array(strtoupper($state), array_keys($valid_states))) {
                    return false;
                } else {
                    return strtoupper($state);
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
        if (!empty($paypal_response['ACK']) && strtoupper($paypal_response['ACK']) == 'SUCCESS') {
            return true;
        }
    }

    public function ec_is_response_success_or_successwithwarning($paypal_response) {
        if ( !empty($paypal_response['ACK']) && strtoupper($paypal_response['ACK']) == 'SUCCESS' || strtoupper($paypal_response['ACK']) == "SUCCESSWITHWARNING") {
            return true;
        }
    }

    public function ec_is_response_successwithwarning($paypal_response) {
        if ( !empty($paypal_response['ACK']) && strtoupper($paypal_response['ACK']) == 'SUCCESSWITHWARNING') {
            return true;
        }
    }
    
    public function ec_is_response_partialsuccess($paypal_response) {
        if (!empty($paypal_response['ACK']) && strtoupper($paypal_response['ACK']) == 'PARTIALSUCCESS') {
            return true;
        }
    }
}
