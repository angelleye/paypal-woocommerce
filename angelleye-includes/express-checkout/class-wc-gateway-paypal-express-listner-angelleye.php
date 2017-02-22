<?php

class WC_Gateway_PayPal_Express_listner_AngellEYE {

    public function __construct() {
        $this->liveurl = 'https://ipnpb.paypal.com/cgi-bin/webscr';
        $this->testurl = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';
    }

    public function check_ipn_request() {
        try {
            @ob_clean();
            $ipn_response = !empty($_POST) ? $_POST : false;
            if ($ipn_response && $this->check_ipn_request_is_valid($ipn_response)) {
                header('HTTP/1.1 200 OK');
                return true;
            } else {
                return false;
            }
        } catch (Exception $ex) {
            
        }
    }

    public function check_ipn_request_is_valid($ipn_response) {
        try {
            $is_sandbox = (isset($ipn_response['test_ipn'])) ? 'yes' : 'no';
            if ('yes' == $is_sandbox) {
                $paypal_adr = $this->testurl;
            } else {
                $paypal_adr = $this->liveurl;
            }
            $validate_ipn = array('cmd' => '_notify-validate');
            $validate_ipn += stripslashes_deep($ipn_response);
            $params = array(
                'body' => $validate_ipn,
                'sslverify' => false,
                'timeout' => 60,
                'httpversion' => '1.1',
                'compress' => false,
                'decompress' => false,
                'user-agent' => 'pal-for-edd/'
            );
            $response = wp_remote_post($paypal_adr, $params);
            if (!is_wp_error($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 && strstr($response['body'], 'VERIFIED')) {
                return true;
            }
            return false;
        } catch (Exception $ex) {
            
        }
    }

    public function successful_request($IPN_status) {
        try {
            $ipn_response = !empty($_POST) ? $_POST : false;
            $ipn_response['IPN_status'] = ( $IPN_status == true ) ? 'Verified' : 'Invalid';
            $posted = stripslashes_deep($ipn_response);
            $this->third_party_API_request($posted);
            $this->ipn_response_data_handler($posted);
        } catch (Exception $ex) {
            
        }
    }

    public function third_party_API_request($posted) {
        try {
            $settings = get_option('woocommerce_express_checkout_settings');
            if (isset($settings['enable_notifyurl']) && 'no' == $settings['enable_notifyurl']) {
                return;
            }
            if (isset($settings['notifyurl']) && empty($settings['notifyurl'])) {
                return;
            }
            $express_checkout_notifyurl = site_url('?Express_Checkout&action=ipn_handler');
            $third_party_notifyurl = str_replace('&amp;', '&', $settings['notifyurl']);
            if (trim($express_checkout_notifyurl) == trim($third_party_notifyurl)) {
                return;
            }
            $params = array(
                'body' => $posted,
                'sslverify' => false,
                'timeout' => 60,
                'httpversion' => '1.1',
                'compress' => false,
                'decompress' => false,
                'user-agent' => 'paypal-ipn/'
            );
            wp_remote_post($third_party_notifyurl, $params);
            return;
        } catch (Exception $ex) {
            
        }
    }

    public function ipn_response_data_handler($posted = null) {
        try {
            $log = new WC_Logger();
            $log->add('express_chekout_post_callback', print_r($posted, true));
            if (isset($posted) && !empty($posted)) {
                if (isset($posted['parent_txn_id']) && !empty($posted['parent_txn_id'])) {
                    $PayPalRequestData = $this->get_API_for_paypal($posted['parent_txn_id']);
                    $Express_Checkout_API = new Express_Checkout_API($PayPalRequestData['ID'], $PayPalRequestData['Sandbox'], $PayPalRequestData['APIUsername'], $PayPalRequestData['APIPassword'], $PayPalRequestData['APISignature']);
                    $PayPalResult = $Express_Checkout_API->ec_get_express_checkout_transaction($PayPalRequestData);
                    $posted['payment_status'] = isset($PayPalResult['PAYMENTSTATUS']) ? $PayPalResult['PAYMENTSTATUS'] : '';
                    if (isset($posted['auth_id']) && !empty($posted['auth_id'])) {
                        $paypal_txn_id = $posted['auth_id'];
                    } else {
                        $paypal_txn_id = $posted['parent_txn_id'];
                    }
                } else if (isset($posted['txn_id']) && !empty($posted['txn_id'])) {
                    $paypal_txn_id = $posted['txn_id'];
                } else {
                    return false;
                }
                if ($this->express_checkout_exist_post_by_title($paypal_txn_id) != false) {
                    $post_id = $this->express_checkout_exist_post_by_title($paypal_txn_id);
                    $this->express_checkout_update_post_status($posted, $post_id);
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function get_API_for_paypal($parent_txn_id) {
        try {
            $settings = get_option('woocommerce_express_checkout_settings');
            $ID = "PayPal_Express_Checkout";
            $sandbox = ($settings['testmode'] == 'yes') ? TRUE : FALSE;
            $apiusername = '';
            $apipassword = '';
            $apisignature = '';
            $apisubject = '';
            $apimethod = 'GetTransactionDetails';
            $buttonsource = '';
            $transactionid = $parent_txn_id;
            if ($sandbox) {
                $apiusername = ($settings['sandbox_api_username']) ? $settings['sandbox_api_username'] : '';
                $apipassword = ($settings['sandbox_api_password']) ? $settings['sandbox_api_password'] : '';
                $apisignature = ($settings['sandbox_api_signature']) ? $settings['sandbox_api_signature'] : '';
            } else {
                $apiusername = ($settings['api_username']) ? $settings['api_username'] : '';
                $apipassword = ($settings['api_password']) ? $settings['api_password'] : '';
                $apisignature = ($settings['api_signature']) ? $settings['api_signature'] : '';
            }
            return $PayPalRequestData = array('ID' => $ID, 'Sandbox' => $sandbox, 'APIUsername' => $apiusername, 'APIPassword' => $apipassword, 'APISignature' => $apisignature, 'APISubject' => $apisubject, 'APIMethod' => $apimethod, 'Buttonsource' => $buttonsource, 'transactionid' => $transactionid);
        } catch (Exception $ex) {
            
        }
    }

    public function express_checkout_exist_post_by_title($ipn_txn_id) {
        try {
            global $wpdb;
            $post_data = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->postmeta}, {$wpdb->posts} WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_value = %s AND $wpdb->postmeta.meta_key = '_express_chekout_transactionid' AND $wpdb->posts.post_type = 'shop_order' ", $ipn_txn_id));
            if (empty($post_data)) {
                return false;
            } else {
                return $post_data;
            }
        } catch (Exception $ex) {
            
        }
    }

    public function express_checkout_update_post_status($posted, $order_id) {
        try {
            $order = new WC_Order($order_id);
            if ('completed' === strtolower($posted['payment_status'])) {
                $order->payment_complete($order, (!empty($posted['txn_id']) ? wc_clean($posted['txn_id']) : ''), __('IPN payment completed', 'woocommerce'));
                if (!empty($posted['mc_fee'])) {
                    update_post_meta($order->id, 'PayPal Transaction Fee', wc_clean($posted['mc_fee']));
                }
            } else {
                $order->update_status('on-hold', ($posted['pending_reason']) ? $posted['pending_reason'] : '');
                $order->reduce_order_stock();
                WC()->cart->empty_cart();
            }
        } catch (Exception $ex) {
            
        }
    }

}
