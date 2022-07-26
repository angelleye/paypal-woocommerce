<?php

if (!defined('ABSPATH')) {
    exit;
}

class AngellEYE_PayPal_PPCP_Webhooks {

    protected static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        try {
            $this->angelleye_ppcp_load_class();
            $this->is_sandbox = 'yes' === $this->settings->get('testmode', 'no');
            $this->enabled = 'yes' === $this->settings->get('enabled', 'no');
            $this->error_email_notification = 'yes' === $this->settings->get('error_email_notification', 'yes');
            if ($this->is_sandbox) {
                $this->merchant_id = $this->settings->get('sandbox_merchant_id', '');
                $this->client_id = $this->settings->get('sandbox_client_id', '');
                $this->secret_id = $this->settings->get('sandbox_api_secret', '');
                $this->webhook = 'https://api.sandbox.paypal.com/v1/notifications/webhooks';
                $this->webhook_id = 'angelleye_ppcp_sandbox_webhook_id';
                $this->webhook_verify_url = 'https://api.sandbox.paypal.com/v1/notifications/verify-webhook-signature';
                $this->partner_client_id = PAYPAL_PPCP_SNADBOX_PARTNER_CLIENT_ID;
            } else {
                $this->webhook = 'https://api.paypal.com/v1/notifications/webhooks';
                $this->webhook_id = 'angelleye_ppcp_live_webhook_id';
                $this->webhook_verify_url = 'https://api.paypal.com/v1/notifications/verify-webhook-signature';
                $this->merchant_id = $this->settings->get('live_merchant_id', '');
                $this->client_id = $this->settings->get('api_client_id', '');
                $this->secret_id = $this->settings->get('api_secret', '');
                $this->partner_client_id = PAYPAL_PPCP_PARTNER_CLIENT_ID;
            }
        } catch (Exception $ex) {
            
        }
    }

    public function is_valid_for_use() {
        if ($this->enabled === false) {
            return false;
        }
        if (!empty($this->merchant_id) || (!empty($this->client_id) && !empty($this->secret_id))) {
            return true;
        }
        return false;
    }

    public function angelleye_ppcp_paypalauthassertion() {
        $temp = array(
            "alg" => "none"
        );
        $returnData = base64_encode(json_encode($temp)) . '.';
        $temp = array(
            "iss" => $this->partner_client_id,
            "payer_id" => $this->merchant_id
        );
        $returnData .= base64_encode(json_encode($temp)) . '.';
        return $returnData;
    }

    public function angelleye_ppcp_create_webhook() {
        try {
            set_transient('angelleye_ppcp_is_webhook_process_started', 'done', 3 * HOUR_IN_SECONDS);
            $webhook_request = array();
            $webhook_request['url'] = add_query_arg(array('angelleye_ppcp_action' => 'webhook_handler', 'utm_nooverride' => '1'), WC()->api_request_url('AngellEYE_PayPal_PPCP_Webhooks'));
            $webhook_request['event_types'][] = array('name' => 'CHECKOUT.ORDER.APPROVED');
            $webhook_request['event_types'][] = array('name' => 'PAYMENT.AUTHORIZATION.CREATED');
            $webhook_request['event_types'][] = array('name' => 'PAYMENT.AUTHORIZATION.VOIDED');
            $webhook_request['event_types'][] = array('name' => 'PAYMENT.CAPTURE.COMPLETED');
            $webhook_request['event_types'][] = array('name' => 'PAYMENT.CAPTURE.DENIED');
            $webhook_request['event_types'][] = array('name' => 'PAYMENT.CAPTURE.PENDING');
            $webhook_request['event_types'][] = array('name' => 'PAYMENT.CAPTURE.REFUNDED');
            $webhook_request = angelleye_ppcp_remove_empty_key($webhook_request);
            $args = array(
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id(), 'Paypal-Auth-Assertion' => $this->angelleye_ppcp_paypalauthassertion()),
                'body' => $webhook_request
            );

            $response = $this->api_request->request($this->webhook, $args, 'create_webhook');
            if (is_wp_error($response)) {
                delete_transient('angelleye_ppcp_is_webhook_process_started');
                $error_message = $response->get_error_message();
                $this->api_log->log('Error Message : ' . wc_print_r($error_message, true));
            } else {
                ob_start();
                $return_response = array();
                $api_response = json_decode(wp_remote_retrieve_body($response), true);
                $this->api_log->log('function called: angelleye_ppcp_create_webhooks_request');
                if (!empty($api_response['id'])) {
                    $this->api_log->log('Response Code: ' . wp_remote_retrieve_response_code($response));
                    $this->api_log->log('Response Message: ' . wp_remote_retrieve_response_message($response));
                    $this->api_log->log('Response Body: ' . wc_print_r($api_response, true));
                    update_option($this->webhook_id, $api_response['id']);
                } else {
                    $this->api_log->log('Response Body: ' . wc_print_r($api_response, true));
                    $error = $this->angelleye_ppcp_get_readable_message($api_response);
                    $this->api_log->log('Response Message: ' . wc_print_r($error, true));
                    if (isset($api_response['name']) && strpos($api_response['name'], 'WEBHOOK_NUMBER_LIMIT_EXCEEDED') !== false) {
                        $this->angelleye_ppcp_delete_first_webhook();
                        delete_transient('angelleye_ppcp_is_webhook_process_started');
                    } elseif ($api_response['name'] && strpos($api_response['name'], 'WEBHOOK_URL_ALREADY_EXISTS') !== false) {
                        $this->angelleye_ppcp_delete_exiting_webhook();
                        delete_transient('angelleye_ppcp_is_webhook_process_started');
                    } elseif ($api_response['details']['0']['field'] !== 'url') {
                        delete_transient('angelleye_ppcp_is_webhook_process_started');
                    }
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_delete_first_webhook() {
        try {
            $response = wp_remote_get($this->webhook, array('headers' => array('Content-Type' => 'application/json', 'Authorization' => "Basic " . $this->basicAuth, "prefer" => "return=representation", 'PayPal-Partner-Attribution-Id' => 'MBJTechnolabs_SI_SPB')));
            $api_response = json_decode(wp_remote_retrieve_body($response), true);
            if (!empty($api_response['webhooks'])) {
                foreach ($api_response['webhooks'] as $key => $webhooks) {
                    $response = wp_remote_request($this->webhook . $webhooks['id'], array(
                        'timeout' => 60,
                        'method' => 'DELETE',
                        'redirection' => 5,
                        'httpversion' => '1.1',
                        'blocking' => true,
                        'headers' => array('Content-Type' => 'application/json', 'Authorization' => "Basic " . $this->basicAuth, "prefer" => "return=representation", 'PayPal-Partner-Attribution-Id' => 'MBJTechnolabs_SI_SPB', 'PayPal-Request-Id' => $this->generate_request_id()),
                        'cookies' => array()
                            )
                    );
                    $this->angelleye_ppcp_log('Response Code: ' . wp_remote_retrieve_response_code($response));
                    $this->angelleye_ppcp_log('Response Message: ' . wp_remote_retrieve_response_message($response));
                    $this->angelleye_ppcp_log('Response Body: ' . wc_print_r($api_response, true));
                    return false;
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_delete_exiting_webhook() {
        try {
            $response = wp_remote_get($this->webhook, array('headers' => array('Content-Type' => 'application/json', 'Authorization' => "Basic " . $this->basicAuth, "prefer" => "return=representation", 'PayPal-Partner-Attribution-Id' => 'MBJTechnolabs_SI_SPB')));
            $api_response = json_decode(wp_remote_retrieve_body($response), true);
            if (!empty($api_response['webhooks'])) {
                foreach ($api_response['webhooks'] as $key => $webhooks) {
                    if (isset($webhooks['url']) && strpos($webhooks['url'], site_url()) !== false) {
                        $response = wp_remote_request($this->webhook . '/' . $webhooks['id'], array(
                            'timeout' => 60,
                            'method' => 'DELETE',
                            'redirection' => 5,
                            'httpversion' => '1.1',
                            'blocking' => true,
                            'headers' => array('Content-Type' => 'application/json', 'Authorization' => "Basic " . $this->basicAuth, "prefer" => "return=representation", 'PayPal-Partner-Attribution-Id' => 'MBJTechnolabs_SI_SPB', 'PayPal-Request-Id' => $this->generate_request_id()),
                            'cookies' => array()
                                )
                        );
                        $this->angelleye_ppcp_log('Response Code: ' . wp_remote_retrieve_response_code($response));
                        $this->angelleye_ppcp_log('Response Message: ' . wp_remote_retrieve_response_message($response));
                        $this->angelleye_ppcp_log('Response Body: ' . wc_print_r($api_response, true));
                    }
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_update_webhook() {
        try {
            
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_load_class() {
        try {
            if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-ppcp-angelleye-settings.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Log')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-log.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Request')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-request.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_DCC_Validate')) {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-dcc-validate.php');
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Payment')) {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-payment.php');
            }
            $this->settings = WC_Gateway_PPCP_AngellEYE_Settings::instance();
            $this->api_log = AngellEYE_PayPal_PPCP_Log::instance();
            $this->api_request = AngellEYE_PayPal_PPCP_Request::instance();
            $this->dcc_applies = AngellEYE_PayPal_PPCP_DCC_Validate::instance();
            $this->payment_request = AngellEYE_PayPal_PPCP_Payment::instance();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_handle_webhook_request_handler() {
        try {
            $bool = false;
            if ($this->is_valid_for_use() === true) {
                $posted_raw = angelleye_ppcp_get_raw_data();
                if (empty($posted_raw)) {
                    return false;
                }
                $headers = $this->getallheaders_value();
                $headers = array_change_key_case($headers, CASE_UPPER);
                $posted = json_decode($posted_raw, true);
                $this->angelleye_ppcp_log('Response Body: ' . wc_print_r($posted, true));
                $this->angelleye_ppcp_log('Headers: ' . wc_print_r($headers, true));
                $bool = $this->angelleye_ppcp_validate_webhook_event($headers, $posted);
                if ($bool) {
                    $this->angelleye_ppcp_update_order_status($posted);
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getallheaders_value() {
        try {
            if (!function_exists('getallheaders')) {
                return $this->getallheaders_custome();
            } else {
                return getallheaders();
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getallheaders_custome() {
        try {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
            return $headers;
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_validate_webhook_event($headers, $body) {
        try {
            $this->angelleye_ppcp_prepare_webhook_validate_request($headers, $body);
            if (!empty($this->request)) {
                $response = wp_remote_post($this->webhook_url, array(
                    'method' => 'POST',
                    'timeout' => 60,
                    'redirection' => 5,
                    'httpversion' => '1.1',
                    'blocking' => true,
                    'headers' => array('Content-Type' => 'application/json', 'Authorization' => "Basic " . $this->basicAuth, "prefer" => "return=representation", 'PayPal-Partner-Attribution-Id' => 'MBJTechnolabs_SI_SPB', 'PayPal-Request-Id' => $this->generate_request_id()),
                    'body' => json_encode($this->request),
                    'cookies' => array()
                        )
                );
            } else {
                return false;
            }
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                $this->angelleye_ppcp_log('Webhook Error Message : ' . wc_print_r($error_message, true));
                return false;
            } else {
                $return_response = array();
                $api_response = json_decode(wp_remote_retrieve_body($response), true);
                $this->angelleye_ppcp_log('Response Body: ' . wc_print_r($api_response, true));
                if (!empty($api_response['verification_status']) && 'SUCCESS' === $api_response['verification_status']) {
                    $this->angelleye_ppcp_log('Response Code: ' . wp_remote_retrieve_response_code($response));
                    $this->angelleye_ppcp_log('Response Message: ' . wp_remote_retrieve_response_message($response));
                    return true;
                } else {
                    return false;
                }
            }
        } catch (Exception $ex) {
            return false;
        }
    }

    public function angelleye_ppcp_prepare_webhook_validate_request($headers, $body) {
        try {
            $this->request = array();
            $webhook_id = get_option($this->webhook_id, false);
            $this->request['transmission_id'] = $headers['PAYPAL-TRANSMISSION-ID'];
            $this->request['transmission_time'] = $headers['PAYPAL-TRANSMISSION-TIME'];
            $this->request['cert_url'] = $headers['PAYPAL-CERT-URL'];
            $this->request['auth_algo'] = $headers['PAYPAL-AUTH-ALGO'];
            $this->request['transmission_sig'] = $headers['PAYPAL-TRANSMISSION-SIG'];
            $this->request['webhook_id'] = $webhook_id;
            $this->request['webhook_event'] = $body;
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_update_order_status($posted) {
        $order = false;
        if (!empty($posted['resource']['purchase_units'][0]['custom_id'])) {
            $order = $this->angelleye_ppcp_get_paypal_order($posted['resource']['purchase_units'][0]['custom_id']);
        } elseif (!empty($posted['resource']['custom_id'])) {
            $order = $this->angelleye_ppcp_get_paypal_order($posted['resource']['custom_id']);
        }
        if ($order && isset($posted['event_type']) && !empty($posted['event_type'])) {
            $order->add_order_note('Webhooks Update : ' . $posted['summary']);
            if (isset($posted['resource']['status']) && !empty($posted['resource']['status'])) {
                $this->angelleye_ppcp_log('Payment status: ' . $posted['resource']['status']);
            }
            if (isset($posted['resource']['id']) && !empty($posted['resource']['id'])) {
                $this->angelleye_ppcp_log('PayPal Transaction ID: ' . $posted['resource']['id']);
            }
            if (isset($posted['resource']['status']) && isset($posted['resource']['id'])) {
                switch ($posted['event_type']) {
                    case 'PAYMENT.AUTHORIZATION.CREATED' :
                        $this->payment_status_on_hold($order, $posted);
                        break;
                    case 'PAYMENT.AUTHORIZATION.VOIDED' :
                        $this->payment_status_voided($order, $posted);
                        break;
                    case 'PAYMENT.CAPTURE.COMPLETED' :
                        $this->payment_status_completed($order, $posted);
                        break;
                    case 'PAYMENT.CAPTURE.DENIED' :
                        $this->payment_status_denied($order, $posted);
                        break;
                    case 'PAYMENT.CAPTURE.PENDING' :
                        $this->payment_status_on_hold($order, $posted);
                        break;
                    case 'PAYMENT.CAPTURE.REFUNDED' :
                        $this->payment_status_refunded($order, $posted);
                        break;
                }
            }
        }
    }

    public function payment_status_completed($order, $posted) {
        if ($order->has_status(wc_get_is_paid_statuses())) {
            $this->angelleye_ppcp_log('Aborting, Order #' . $order->get_id() . ' is already complete.');
            exit;
        }
        $this->save_paypal_meta_data($order, $posted);
        if ('COMPLETED' === $posted['resource']['status']) {
            $this->payment_complete($order);
        } else {
            if ('created' === $posted['resource']['status']) {
                $this->payment_on_hold($order, __('Payment authorized. Change payment status to processing or complete to capture funds.', 'smart-paypal-checkout-for-woocommerce'));
            } else {
                if (!empty($posted['pending_reason'])) {
                    $this->payment_on_hold($order, sprintf(__('Payment pending (%s).', 'smart-paypal-checkout-for-woocommerce'), $posted['pending_reason']));
                }
            }
        }
    }

    public function payment_complete($order, $txn_id = '', $note = '') {
        if (!$order->has_status(array('processing', 'completed'))) {
            $order->add_order_note($note);
            $order->payment_complete($txn_id);
            WC()->cart->empty_cart();
        }
    }

    public function payment_on_hold($order, $reason = '') {
        if (!$order->has_status(array('processing', 'completed', 'refunded'))) {
            $order->update_status('on-hold', $reason);
        }
    }

    public function payment_status_pending($order, $posted) {
        if (!$order->has_status(array('processing', 'completed', 'refunded'))) {
            $this->payment_status_completed($order, $posted);
        }
    }

    public function payment_status_failed($order) {
        if (!$order->has_status(array('failed'))) {
            $order->update_status('failed');
        }
    }

    public function payment_status_denied($order) {
        $this->payment_status_failed($order);
    }

    public function payment_status_expired($order) {
        $this->payment_status_failed($order);
    }

    public function payment_status_voided($order) {
        $this->payment_status_failed($order);
    }

    public function payment_status_refunded($order) {
        if (!$order->has_status(array('refunded'))) {
            $order->update_status('refunded');
        }
    }

    public function payment_status_on_hold($order) {
        if ($order->has_status(array('pending'))) {
            $order->update_status('on-hold');
        }
    }

    public function save_paypal_meta_data($order, $posted) {
        if (!empty($posted['resource']['id'])) {
            update_post_meta($order->get_id(), '_transaction_id', wc_clean($posted['resource']['id']));
        }
        if (!empty($posted['resource']['status'])) {
            update_post_meta($order->get_id(), '_paypal_status', wc_clean($posted['resource']['status']));
        }
    }

    public function angelleye_ppcp_get_paypal_order($raw_custom) {
        $custom = json_decode($raw_custom);
        if ($custom && is_object($custom)) {
            $order_id = $custom->order_id;
            $order_key = $custom->order_key;
        } else {
            $this->angelleye_ppcp_log('Order ID and key were not found in "custom_id".');
            return false;
        }
        $order = wc_get_order($order_id);
        if (!$order) {
            $order_id = wc_get_order_id_by_order_key($order_key);
            $order = wc_get_order($order_id);
        }
        if (!$order || !hash_equals($order->get_order_key(), $order_key)) {
            $this->angelleye_ppcp_log('Order Keys do not match.');
            return false;
        }
        $this->angelleye_ppcp_log('Order  match : ' . $order_id);

        return $order;
    }

    public function generate_request_id() {
        static $pid = -1;
        static $addr = -1;

        if ($pid == -1) {
            $pid = getmypid();
        }

        if ($addr == -1) {
            if (array_key_exists('SERVER_ADDR', $_SERVER)) {
                $addr = ip2long($_SERVER['SERVER_ADDR']);
            } else {
                $addr = php_uname('n');
            }
        }

        return $addr . $pid . $_SERVER['REQUEST_TIME'] . mt_rand(0, 0xffff);
    }

    public function angelleye_ppcp_set_payer_details($woo_order_id, $body_request) {
        if ($woo_order_id != null) {
            $order = wc_get_order($woo_order_id);
            $old_wc = version_compare(WC_VERSION, '3.0', '<');
            $first_name = $old_wc ? $order->billing_first_name : $order->get_billing_first_name();
            $last_name = $old_wc ? $order->billing_last_name : $order->get_billing_last_name();
            $billing_email = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_email : $order->get_billing_email();
            $billing_phone = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_phone : $order->get_billing_phone();
            if (!empty($billing_email)) {
                $body_request['payer']['email_address'] = $billing_email;
            }
            if (!empty($billing_phone)) {
                $body_request['payer']['phone']['phone_number']['national_number'] = preg_replace('/[^0-9]/', '', $billing_phone);
            }
            if (!empty($first_name)) {
                $body_request['payer']['name']['given_name'] = $first_name;
            }
            if (!empty($last_name)) {
                $body_request['payer']['name']['surname'] = $last_name;
            }
            $address_1 = $old_wc ? $order->billing_address_1 : $order->get_billing_address_1();
            $address_2 = $old_wc ? $order->billing_address_2 : $order->get_billing_address_2();
            $city = $old_wc ? $order->billing_city : $order->get_billing_city();
            $state = $old_wc ? $order->billing_state : $order->get_billing_state();
            $postcode = $old_wc ? $order->billing_postcode : $order->get_billing_postcode();
            $country = $old_wc ? $order->billing_country : $order->get_billing_country();
            if (!empty($address_1) && !empty($city) && !empty($state) && !empty($postcode) && !empty($country)) {
                $body_request['payer']['address'] = array(
                    'address_line_1' => $address_1,
                    'address_line_2' => $address_2,
                    'admin_area_2' => $city,
                    'admin_area_1' => $state,
                    'postal_code' => $postcode,
                    'country_code' => $country,
                );
            }
        } else {
            if (is_user_logged_in()) {
                $customer = WC()->customer;
                $old_wc = version_compare(WC_VERSION, '3.0', '<');
                $first_name = $old_wc ? $customer->billing_first_name : $customer->get_billing_first_name();
                $last_name = $old_wc ? $customer->billing_last_name : $customer->get_billing_last_name();
                $address_1 = $old_wc ? $customer->get_address() : $customer->get_billing_address_1();
                $address_2 = $old_wc ? $customer->get_address_2() : $customer->get_billing_address_2();
                $city = $old_wc ? $customer->get_city() : $customer->get_billing_city();
                $state = $old_wc ? $customer->get_state() : $customer->get_billing_state();
                $postcode = $old_wc ? $customer->get_postcode() : $customer->get_billing_postcode();
                $country = $old_wc ? $customer->get_country() : $customer->get_billing_country();
                $email_address = $old_wc ? WC()->customer->billing_email : WC()->customer->get_billing_email();
                $billing_phone = $old_wc ? $customer->billing_phone : $customer->get_billing_phone();
                if (!empty($first_name)) {
                    $body_request['payer']['name']['given_name'] = $first_name;
                }
                if (!empty($last_name)) {
                    $body_request['payer']['name']['surname'] = $last_name;
                }
                if (!empty($email_address)) {
                    $body_request['payer']['email_address'] = $email_address;
                }
                if (!empty($billing_phone)) {
                    $body_request['payer']['phone']['phone_number']['national_number'] = preg_replace('/[^0-9]/', '', $billing_phone);
                }
                if (!empty($address_1) && !empty($city) && !empty($state) && !empty($postcode) && !empty($country)) {
                    $body_request['payer']['address'] = array(
                        'address_line_1' => $address_1,
                        'address_line_2' => $address_2,
                        'admin_area_2' => $city,
                        'admin_area_1' => $state,
                        'postal_code' => $postcode,
                        'country_code' => $country,
                    );
                }
            }
        }
        return $body_request;
    }

    public function angelleye_ppcp_get_generate_token() {
        try {
            $args = array(
                'method' => 'POST',
                'timeout' => 60,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking' => true,
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => "Basic " . $this->basicAuth, 'Accept-Language' => 'en_US'),
                'cookies' => array(),
                'body' => array(),
            );
            $paypal_api_response = wp_remote_get($this->generate_token_url, $args);
            $body = wp_remote_retrieve_body($paypal_api_response);
            $api_response = !empty($body) ? json_decode($body, true) : '';
            if (!empty($api_response['client_token'])) {
                return $api_response['client_token'];
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_get_readable_message($error, $error_email_notification_param = array()) {
        $message = '';
        if (isset($error['name'])) {
            switch ($error['name']) {
                case 'VALIDATION_ERROR':
                    foreach ($error['details'] as $e) {
                        $message .= "\t" . $e['field'] . "\n\t" . $e['issue'] . "\n\n";
                    }
                    break;
                case 'INVALID_REQUEST':
                    foreach ($error['details'] as $e) {
                        if (isset($e['field']) && isset($e['description'])) {
                            $message .= "\t" . $e['field'] . "\n\t" . $e['description'] . "\n\n";
                        } elseif (isset($e['issue'])) {
                            $message .= "\t" . $e['issue'] . "n\n";
                        }
                    }
                    break;
                case 'BUSINESS_ERROR':
                    $message .= $error['message'];
                    break;
                case 'UNPROCESSABLE_ENTITY' :
                    foreach ($error['details'] as $e) {
                        $message .= "\t" . $e['issue'] . ": " . $e['description'] . "\n\n";
                    }
                    break;
            }
        }
        if (!empty($message)) {
            
        } else if (!empty($error['message'])) {
            $message = $error['message'];
        } else if (!empty($error['error_description'])) {
            $message = $error['error_description'];
        } else {
            $message = $error;
        }
        if ($this->error_email_notification) {
            $this->angelleye_ppcp_error_email_notification($error_email_notification_param, $message);
        }
        return $message;
    }

    public function angelleye_ppcp_error_email_notification($error_email_notification_param, $error_message) {
        if (function_exists('WC')) {
            try {
                $mailer = WC()->mailer();
                $error_email_notify_subject = apply_filters('ae_ppec_error_email_subject', 'PayPal Complete Payments Error Notification');
                $message = '';
                if (!empty($error_email_notification_param['request'])) {
                    $message .= sprintf("<strong>" . __('Action: ', 'paypal-for-woocommerce') . "</strong>" . ucwords(str_replace('_', ' ', $error_email_notification_param['request'])) . PHP_EOL);
                }
                if (!empty($error_message)) {
                    $message .= sprintf("<strong>" . __('Error: ', 'paypal-for-woocommerce') . "</strong>" . $error_message . PHP_EOL);
                }
                if (!empty($error_email_notification_param['order_id'])) {
                    $message .= sprintf("<strong>" . __('Order ID: ', 'paypal-for-woocommerce') . "</strong>" . $error_email_notification_param['order_id'] . PHP_EOL);
                }
                if (is_user_logged_in()) {
                    $userLogined = wp_get_current_user();
                    $message .= sprintf("<strong>" . __('User ID: ', 'paypal-for-woocommerce') . "</strong>" . $userLogined->ID . PHP_EOL);
                    $message .= sprintf("<strong>" . __('User Email: ', 'paypal-for-woocommerce') . "</strong>" . $userLogined->user_email . PHP_EOL);
                }
                $message .= sprintf("<strong>" . __('User IP: ', 'paypal-for-woocommerce') . "</strong>" . WC_Geolocation::get_ip_address() . PHP_EOL);
                $message = apply_filters('ae_ppec_error_email_message', $message);
                $message = $mailer->wrap_message($error_email_notify_subject, $message);
                $mailer->send(get_option('admin_email'), strip_tags($error_email_notify_subject), $message);
            } catch (Exception $ex) {
                $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
                $this->api_log->log($ex->getMessage(), 'error');
            }
        }
    }

}
