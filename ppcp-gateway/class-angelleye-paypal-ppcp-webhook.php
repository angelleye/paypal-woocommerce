<?php

defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Webhook {

    public $settings;
    public $api_log;
    public $is_sandbox;
    public $access_token = false;
    public $api_request;
    public $response = array();
    public $request_header_default = array();
    public $request_default_args = array();
    public $webhook;
    public $webhook_id;
    public $webhook_url;
    public $seller_onboarding;
    protected static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->angelleye_ppcp_load_class();
        add_action('angelleyel_ppcp_create_webhook', array($this, 'angelleyel_ppcp_create_webhook'));
        if (!has_action('woocommerce_api_' . strtolower('AngellEYE_PayPal_PPCP_Webhook'))) {
            add_action('woocommerce_api_' . strtolower('AngellEYE_PayPal_PPCP_Webhook'), array($this, 'wc_webhook_api'));
        }
    }

    public function angelleye_ppcp_load_webhook_default_settings() {
        $this->is_sandbox = 'yes' === $this->settings->get('testmode', 'no');
        if ($this->is_sandbox) {
            $this->access_token = get_transient('angelleye_ppcp_sandbox_access_token');
            $this->webhook = 'https://api-m.sandbox.paypal.com/v1/notifications/webhooks';
            $this->webhook_id = 'angelleye_ppcp_snadbox_webhook_id';
            $this->webhook_url = 'https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature';
        } else {
            $this->access_token = get_transient('angelleye_ppcp_live_access_token');
            $this->webhook = 'https://api-m.paypal.com/v1/notifications/webhooks';
            $this->webhook_id = 'angelleye_ppcp_live_webhook_id';
            $this->webhook_url = 'https://api-m.paypal.com/v1/notifications/verify-webhook-signature';
        }
        if ($this->access_token == false) {
            $this->access_token = $this->api_request->angelleye_ppcp_get_access_token();
        }
        $this->request_header_default = array('Content-Type' => 'application/json', 'Authorization' => "Bearer " . $this->access_token, "prefer" => "return=representation", 'PayPal-Request-Id' => $this->generate_request_id());
        $this->request_default_args = array('method' => 'POST', 'timeout' => 60, 'redirection' => 5, 'httpversion' => '1.1', 'blocking' => true, 'headers' => $this->request_header_default, 'body' => array(), 'cookies' => array());
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
            if (!class_exists('AngellEYE_PayPal_PPCP_Seller_Onboarding')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-seller-onboarding.php';
            }
            $this->settings = WC_Gateway_PPCP_AngellEYE_Settings::instance();
            $this->api_log = AngellEYE_PayPal_PPCP_Log::instance();
            $this->api_request = AngellEYE_PayPal_PPCP_Request::instance();
            $this->seller_onboarding = AngellEYE_PayPal_PPCP_Seller_Onboarding::instance();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleyel_ppcp_webhook_delete() {
        $this->response = $this->api_request->request($this->webhook . '/' . $this->webhook_id, $this->request_default_args, 'delete_webhook');
    }

    public function angelleyel_ppcp_create_webhook() {
        if (!defined('ANGELLEYE_PPCP_WEBHOOK_ORDER_STATUS_UPDATE')) {
            return false;
        }
        $this->angelleye_ppcp_load_webhook_default_settings();
        $this->angelleye_ppcp_delete_existing_webhook();
        try {
            if ($this->access_token) {
                $webhook_request = array();
                $webhook_request['url'] = add_query_arg(array('angelleye_ppcp_action' => 'webhook_handler', 'utm_nooverride' => '1'), WC()->api_request_url('AngellEYE_PayPal_PPCP_Webhook'));
                if (defined('ANGELLEYE_PPCP_WEBHOOK_ORDER_STATUS_UPDATE')) {
                    $webhook_request['event_types'][] = array('name' => 'CHECKOUT.ORDER.APPROVED');
                    $webhook_request['event_types'][] = array('name' => 'PAYMENT.AUTHORIZATION.CREATED');
                    $webhook_request['event_types'][] = array('name' => 'PAYMENT.AUTHORIZATION.VOIDED');
                    $webhook_request['event_types'][] = array('name' => 'PAYMENT.CAPTURE.COMPLETED');
                    $webhook_request['event_types'][] = array('name' => 'PAYMENT.CAPTURE.DENIED');
                    $webhook_request['event_types'][] = array('name' => 'PAYMENT.CAPTURE.PENDING');
                    $webhook_request['event_types'][] = array('name' => 'PAYMENT.CAPTURE.REFUNDED');
                    $webhook_request['event_types'][] = array('name' => 'MERCHANT.ONBOARDING.COMPLETED');
                    $webhook_request['event_types'][] = array('name' => 'MERCHANT.PARTNER-CONSENT.REVOKED');
                }
                /* $webhook_request['event_types'][] = array('name' => 'CUSTOMER.MERCHANT-INTEGRATION.PRODUCT-SUBSCRIPTION-UPDATED');
                  $webhook_request['event_types'][] = array('name' => 'CUSTOMER.MERCHANT-INTEGRATION.CAPABILITY-UPDATED');
                  $webhook_request['event_types'][] = array('name' => 'CUSTOMER.MERCHANT-INTEGRATION.SELLER-EMAIL-CONFIRMED');
                  $webhook_request['event_types'][] = array('name' => 'MERCHANT.ONBOARDING.COMPLETED');
                  $webhook_request = angelleye_ppcp_remove_empty_key($webhook_request); */
                $webhook_request = json_encode($webhook_request);
                $this->request_default_args['method'] = 'POST';
                $this->request_default_args['body'] = $webhook_request;
                $this->response = $this->api_request->request($this->webhook, $this->request_default_args, 'create_webhook');
                ob_start();
                if (!empty($this->response['id'])) {
                    update_option($this->webhook_id, $this->response['id']);
                } else {
                    if (isset($this->response['name']) && strpos($this->response['name'], 'WEBHOOK_NUMBER_LIMIT_EXCEEDED') !== false) {
                        $this->angelleye_ppcp_delete_first_webhook();
                    } elseif ($this->response['name'] && strpos($this->response['name'], 'WEBHOOK_URL_ALREADY_EXISTS') !== false) {
                        $this->angelleye_ppcp_delete_existing_webhook();
                    }
                }
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_delete_existing_webhook() {
        try {
            if (empty($this->access_token)) {
                return false;
            }
            $this->request_default_args['body'] = array();
            $this->request_default_args['method'] = 'GET';
            $this->response = $this->api_request->request($this->webhook, $this->request_default_args, 'get_webhook');
            if (!empty($this->response['webhooks'])) {
                foreach ($this->response['webhooks'] as $key => $webhooks) {
                    if (isset($webhooks['url']) && strpos($webhooks['url'], site_url()) !== false) {
                        $this->request_default_args['method'] = 'DELETE';
                        $this->api_request->request($this->webhook . '/' . $webhooks['id'], $this->request_default_args, 'delete_webhook');
                    }
                }
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_delete_first_webhook() {
        try {
            if (empty($this->access_token)) {
                return false;
            }
            $this->request_default_args['body'] = array();
            $this->request_default_args['method'] = 'GET';
            $this->response = $this->api_request->request($this->webhook, $this->request_default_args, 'get_webhook');
            if (!empty($this->response['webhooks'])) {
                foreach ($this->response['webhooks'] as $key => $webhooks) {
                    $this->request_default_args['method'] = 'DELETE';
                    $this->request_default_args['body'] = array();
                    $this->api_request->request($this->webhook . '/' . $webhooks['id'], $this->request_default_args, 'delete_webhook');
                    return false;
                }
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_handle_webhook_request_handler() {
        if (empty($this->access_token)) {
            return false;
        }
        $this->angelleye_ppcp_load_webhook_default_settings();
        try {
            $bool = false;
            if ($this->access_token) {
                $posted_raw = angelleye_ppcp_get_raw_data();
                if (empty($posted_raw)) {
                    return false;
                }
                $headers = $this->getallheaders_value();
                $headers = array_change_key_case($headers, CASE_UPPER);
                $posted = json_decode($posted_raw, true);
                $bool = $this->angelleye_ppcp_validate_webhook_event($headers, $posted);
                if ($bool) {
                    if (defined('ANGELLEYE_PPCP_WEBHOOK_ORDER_STATUS_UPDATE')) {
                        $this->angelleye_ppcp_update_order_status($posted);
                    }
                }
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
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
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
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
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_validate_webhook_event($headers, $body) {
        try {
            $this->angelleye_ppcp_prepare_webhook_validate_request($headers, $body);
            if (!empty($this->request)) {
                $this->request_default_args['method'] = 'POST';
                $this->request_default_args['body'] = json_encode($this->request);
                $this->response = $this->api_request->request($this->webhook_url, $this->request_default_args, 'validate_webhook');
                if (!empty($this->response['verification_status']) && 'SUCCESS' === $this->response['verification_status']) {
                    return true;
                } else {
                    return false;
                }
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
            return false;
        }
        return false;
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
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
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
            $order->add_order_note('Webhooks Update: ' . $posted['summary']);
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

    public function angelleye_ppcp_update_settings($posted) {
        try {
            if (isset($posted['event_type']) && ('CUSTOMER.MERCHANT-INTEGRATION.PRODUCT-SUBSCRIPTION-UPDATED' === $posted['event_type'] || 'CUSTOMER.MERCHANT-INTEGRATION.CAPABILITY-UPDATED' === $posted['event_type'] )) {
                if (!empty($posted['resource']['merchant_id'])) {
                    $this->response = $this->seller_onboarding->angelleye_track_seller_onboarding_status($posted['resource']['merchant_id']);
                    if ($this->seller_onboarding->angelleye_is_acdc_payments_enable($this->response)) {
                        $this->settings->set('enable_advanced_card_payments', 'yes');
                    } else {
                        $this->settings->set('enable_advanced_card_payments', 'no');
                    }
                    $this->settings->persist();
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function payment_status_completed($order, $posted) {
        if ($order->has_status(wc_get_is_paid_statuses())) {
            exit;
        }
        $this->save_paypal_meta_data($order, $posted);
        if ('COMPLETED' === $posted['resource']['status']) {
            $this->payment_complete($order);
        } else {
            if ('created' === $posted['resource']['status']) {
                $this->payment_on_hold($order, __('Payment authorized. Change payment status to processing or complete to capture funds.', 'paypal-for-woocommerce'));
            } else {
                if (!empty($posted['pending_reason'])) {
                    $this->payment_on_hold($order, sprintf(__('Payment pending (%s).', 'paypal-for-woocommerce'), $posted['pending_reason']));
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

    public function payment_status_cancelled($order) {
        if (!$order->has_status(array('refunded', 'cancelled'))) {
            $order->update_status('cancelled');
        }
    }

    public function payment_status_denied($order, $posted) {
        $this->payment_status_failed($order);
    }

    public function payment_status_expired($order) {
        $this->payment_status_failed($order);
    }

    public function payment_status_voided($order, $posted) {
        $this->payment_status_cancelled($order);
    }

    public function payment_status_refunded($order) {
        if (!$order->has_status(array('refunded'))) {
            $order->update_status('refunded');
        }
    }

    public function payment_status_on_hold($order, $posted) {
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
            return false;
        }
        $order = wc_get_order($order_id);
        if (!$order) {
            $order_id = wc_get_order_id_by_order_key($order_key);
            $order = wc_get_order($order_id);
        }
        if (!$order || !hash_equals($order->get_order_key(), $order_key)) {
            return false;
        }
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

    public function wc_webhook_api() {
        if (!empty($_GET['angelleye_ppcp_action'])) {
            switch ($_GET['angelleye_ppcp_action']) {
                case "webhook_handler":
                    $this->angelleye_ppcp_handle_webhook_request_handler();
                    ob_clean();
                    header('HTTP/1.1 200 OK');
                    exit();
                    break;
            }
        }
    }

}
