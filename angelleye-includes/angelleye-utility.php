<?php

/**
 * @class       AngellEYE_Utility
 * @version	1.1.9.2
 * @package	paypal-for-woocommerce
 * @category	Class
 * @author      Angell EYE <service@angelleye.com>
 */
class AngellEYE_Utility {

    private $plugin_name;
    private $version;
    private $paypal;
    private $testmode;
    private $api_username;
    private $api_password;
    private $api_signature;
    private $ec_debug;
    private $payment_method;
    private $error_email_notify;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->load_dependencies();
    }

    public function add_ec_angelleye_paypal_php_library() {
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }
        if (!class_exists('Angelleye_PayPal')) {
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.class.php' );
        }
        if ($this->payment_method = 'paypal_express') {
            $gateway_obj = new WC_Gateway_PayPal_Express_AngellEYE();
        } else {
            $gateway_obj = new WC_Gateway_PayPal_Pro_AngellEYE();
        }
        $this->testmode = $gateway_obj->get_option('testmode');
        if ($this->testmode == true) {
            $this->api_username = $gateway_obj->get_option('sandbox_api_username');
            $this->api_password = $gateway_obj->get_option('sandbox_api_password');
            $this->api_signature = $gateway_obj->get_option('sandbox_api_signature');
        } else {
            $this->api_username = $gateway_obj->get_option('api_username');
            $this->api_password = $gateway_obj->get_option('api_password');
            $this->api_signature = $gateway_obj->get_option('api_signature');
        }
        $this->error_email_notify = $gateway_obj->get_option('error_email_notify');
        $this->ec_debug = $gateway_obj->get_option('debug');
        $PayPalConfig = array(
            'Sandbox' => $this->testmode == 'yes' ? TRUE : FALSE,
            'APIUsername' => $this->api_username,
            'APIPassword' => $this->api_password,
            'APISignature' => $this->api_signature
        );
        $this->paypal = new Angelleye_PayPal($PayPalConfig);
    }

    private function load_dependencies() {
        if (is_admin() && !defined('DOING_AJAX')) {
            add_filter('woocommerce_order_actions', array($this, 'angelleye_woocommerce_order_actions'));
            $hook_name = '';
            $payment_action_with_gateway = array('paypal_express' => array('DoAuthorization', 'DoCapture', 'DoVoid', 'DoReauthorization'), 'paypal_pro' => array('DoAuthorization', 'DoCapture', 'DoVoid'));
            foreach ($payment_action_with_gateway as $payment_method_name => $payment_action_name) {
                foreach ($payment_action_name as $action_name) {
                    $hook_name = 'wc_' . $payment_method_name . '_' . strtolower($action_name);
                    add_action('woocommerce_order_action_' . $hook_name, array($this, 'angelleye_' . $hook_name));
                }
            }
            add_filter('woocommerce_payment_gateway_supports', array($this, 'angelleye_woocommerce_payment_gateway_supports'), 10, 3);
        }
    }

    public function angelleye_woocommerce_order_actions($order_actions = array()) {
        global $post;
        $order_id = $post->ID;
        $paypal_payment_action = array();
        $this->payment_method = get_post_meta($order_id, '_payment_method', true);
        $payment_action = get_post_meta($order_id, '_payment_action', true);
        if ((isset($this->payment_method) && !empty($this->payment_method)) && (isset($payment_action) && !empty($payment_action)) && !$this->has_authorization_expired($post->ID)) {
            switch ($this->payment_method) {
                case 'paypal_express': {
                        switch ($payment_action) {
                            case ($payment_action == 'Order'):
                                $paypal_payment_action = array('DoCapture', 'DoVoid');
                                break;
                            case ($payment_action == 'Authorization' || $payment_action == 'DoReauthorization'):
                                $paypal_payment_action = array('DoCapture', 'DoReauthorization', 'DoVoid');
                                break;
                        }
                    }
                case 'paypal_pro': {
                        switch ($payment_action) {
                            case ($payment_action == 'Authorization' || $payment_action == 'DoAuthorization'):
                                $paypal_payment_action = array('DoReauthorization', 'DoCapture', 'DoVoid');
                                break;
                        }
                    }
            }
        }
        if (isset($paypal_payment_action) && !empty($paypal_payment_action)) {
            foreach ($paypal_payment_action as $key => $value) {
                $order_actions['wc_' . $this->payment_method . '_' . strtolower($value)] = _x($value, $value, $this->plugin_name);
            }
        }
        return $order_actions;
    }

    /**
     * $_transaction_id, $payment_action, $gateway_name
     * @param type $order_id
     */
    public static function angelleye_add_order_meta($order_id, $payment_order_meta) {
        foreach ($payment_order_meta as $key => $value) {
            update_post_meta($order_id, $key, $value);
        }
        update_post_meta($order_id, '_trans_date', current_time('mysql'));
    }

    /**
     *
     * @param type $order_id
     * @return type
     */
    public function has_authorization_expired($order_id) {
        $transaction_time = strtotime(get_post_meta($order_id, '_trans_date', true));
        return floor(( time() - $transaction_time ) / 3600) > 720;
    }

    /**
     *
     * @param type $order
     */
    public function angelleye_wc_paypal_express_docapture($order) {
        if (!is_object($order)) {
            $order = wc_get_order($order);
        }
        // ensure the authorization is still valid for capture
        if ($this->has_authorization_expired($order->id)) {
            return;
        }
        $this->payment_method = get_post_meta($order->id, '_payment_method', true);
        $transaction_id = get_post_meta($order->id, '_transaction_id', true);
        remove_action('woocommerce_order_action_wc_paypal_express_docapture', array($this, 'angelleye_wc_paypal_express_docapture'));
        remove_action('woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Data::save', 40, 2);
        $this->pfw_do_capture($order, $transaction_id, $order->order_total);
    }

    /**
     *
     * @param type $order
     */
    public function angelleye_wc_paypal_pro_docapture($order) {
        if (!is_object($order)) {
            $order = wc_get_order($order);
        }
        // ensure the authorization is still valid for capture
        if ($this->has_authorization_expired($order->id)) {
            return;
        }
        $this->payment_method = get_post_meta($order->id, '_payment_method', true);
        $transaction_id = get_post_meta($order->id, '_transaction_id', true);
        remove_action('woocommerce_order_action_wc_paypal_pro_docapture', array($this, 'angelleye_wc_paypal_pro_docapture'));
        remove_action('woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Data::save', 40, 2);
        $this->pfw_do_capture($order, $transaction_id, $order->order_total);
    }

    public function pfw_do_capture($order, $transaction_id = null, $capture_total = null) {
        $this->add_ec_angelleye_paypal_php_library();
        $this->ec_add_log('pfw_do_capture function call');
        $DataArray = array(
            'AUTHORIZATIONID' => $transaction_id,
            'AMT' => $capture_total,
            'CURRENCYCODE' => get_woocommerce_currency(),
            'COMPLETETYPE' => 'Complete',
            'INVNUM' => '',
            'NOTE' => '',
        );
        $PayPalRequest = array(
            'DCFields' => $DataArray
        );
        $do_capture_result = $this->paypal->DoCapture($PayPalRequest);
        $ack = strtoupper($do_capture_result["ACK"]);
        if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING") {
            $order->add_order_note(__('PayPal DoCapture', 'paypal-for-woocommerce') .
                    ' ( Response Code: ' . $do_capture_result["ACK"] . ", " .
                    ' DoCapture TransactionID: ' . $do_capture_result['TRANSACTIONID'] . ' )' .
                    ' Authorization ID: ' . $do_capture_result['AUTHORIZATIONID'] . ' )'
            );
            $order->add_order_note('Payment Action: DoCapture');
            $payerstatus_note = __('Payment Status: ', 'paypal-for-woocommerce');
            $payerstatus_note .= ucfirst($do_capture_result['PAYMENTSTATUS']);
            $order->add_order_note($payerstatus_note);
            $payment_order_meta = array('_transaction_id' => $do_capture_result['TRANSACTIONID'], '_payment_action' => 'DoCapture');
            self::angelleye_add_order_meta($order->id, $payment_order_meta);
            $order->payment_complete($do_capture_result['TRANSACTIONID']);
        } else {
            $ErrorCode = urldecode($do_capture_result["L_ERRORCODE0"]);
            $ErrorShortMsg = urldecode($do_capture_result["L_SHORTMESSAGE0"]);
            $ErrorLongMsg = urldecode($do_capture_result["L_LONGMESSAGE0"]);
            $ErrorSeverityCode = urldecode($do_capture_result["L_SEVERITYCODE0"]);
            $this->ec_add_log(__('PayPal DoCapture API call failed. ', $this->plugin_name));
            $this->ec_add_log(__('Detailed Error Message: ', 'paypal-for-woocommerce') . $ErrorLongMsg);
            $this->ec_add_log(__('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg);
            $this->ec_add_log(__('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode);
            $this->ec_add_log(__('Error Severity Code: ', 'paypal-for-woocommerce') . $ErrorSeverityCode);
            $order->add_order_note(__('PayPal DoCapture API call failed. ', $this->plugin_name) .
                    ' ( Detailed Error Message: ' . $ErrorLongMsg . ", " .
                    ' Short Error Message: ' . $ErrorShortMsg . ' )' .
                    ' Error Code: ' . $ErrorCode . ' )' .
                    ' Error Severity Code: ' . $ErrorSeverityCode . ' )'
            );
            $this->call_error_email_notifications($subject = 'DoCapture failed', $method_name = 'DoCapture', $resArray = $do_capture_result);
        }
    }

    /**
     *
     * @param type $order
     */
    public function angelleye_wc_paypal_express_dovoid($order) {
        $this->add_ec_angelleye_paypal_php_library();
        if (!is_object($order)) {
            $order = wc_get_order($order);
        }
        // ensure the authorization is still valid for capture
        if ($this->has_authorization_expired($order->id)) {
            return;
        }
        $this->payment_method = get_post_meta($order->id, '_payment_method', true);
        remove_action('woocommerce_order_action_wc_paypal_express_dovoid', array($this, 'angelleye_wc_paypal_express_dovoid'));
        remove_action('woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Data::save', 40, 2);
        $this->call_do_void($order);
    }

    /**
     *
     * @param type $order
     */
    public function angelleye_wc_paypal_pro_dovoid($order) {
        $this->add_ec_angelleye_paypal_php_library();
        if (!is_object($order)) {
            $order = wc_get_order($order);
        }
        // ensure the authorization is still valid for capture
        if ($this->has_authorization_expired($order->id)) {
            return;
        }
        $this->payment_method = get_post_meta($order->id, '_payment_method', true);
        remove_action('woocommerce_order_action_wc_paypal_express_dovoid', array($this, 'angelleye_wc_paypal_pro_dovoid'));
        remove_action('woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Data::save', 40, 2);
        $this->call_do_void($order);
    }

    public function call_do_void($order) {
        $this->add_ec_angelleye_paypal_php_library();
        $this->ec_add_log('call_do_void function call');
        $transaction_id = get_post_meta($order->id, '_transaction_id', true);
        if (isset($transaction_id) && !empty($transaction_id)) {
            $DVFields = array(
                'authorizationid' => $transaction_id,
                'note' => '',
                'msgsubid' => ''
            );
            $PayPalRequestData = array('DVFields' => $DVFields);
            $do_void_result = $this->paypal->DoVoid($PayPalRequestData);
            $ack = strtoupper($do_void_result["ACK"]);
            if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING") {
                $order->add_order_note(__('PayPal DoVoid', 'paypal-for-woocommerce') .
                        ' ( Response Code: ' . $do_void_result["ACK"] . ", " .
                        ' DoVoid AUTHORIZATIONID: ' . $do_void_result['AUTHORIZATIONID'] . ' )'
                );
                $payment_order_meta = array('_transaction_id' => $do_void_result['AUTHORIZATIONID'], '_payment_action' => 'DoVoid');
                self::angelleye_add_order_meta($order->id, $payment_order_meta);
                $order->update_status('cancelled');
            } else {
                $ErrorCode = urldecode($do_capture_result["L_ERRORCODE0"]);
                $ErrorShortMsg = urldecode($do_capture_result["L_SHORTMESSAGE0"]);
                $ErrorLongMsg = urldecode($do_capture_result["L_LONGMESSAGE0"]);
                $ErrorSeverityCode = urldecode($do_capture_result["L_SEVERITYCODE0"]);
                $this->ec_add_log(__('PayPal DoVoid API call failed. ', $this->plugin_name));
                $this->ec_add_log(__('Detailed Error Message: ', 'paypal-for-woocommerce') . $ErrorLongMsg);
                $this->ec_add_log(__('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg);
                $this->ec_add_log(__('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode);
                $this->ec_add_log(__('Error Severity Code: ', 'paypal-for-woocommerce') . $ErrorSeverityCode);
                $order->add_order_note(__('PayPal DoVoid API call failed. ', $this->plugin_name) .
                        ' ( Detailed Error Message: ' . $ErrorLongMsg . ", " .
                        ' Short Error Message: ' . $ErrorShortMsg . ' )' .
                        ' Error Code: ' . $ErrorCode . ' )' .
                        ' Error Severity Code: ' . $ErrorSeverityCode . ' )'
                );
                $this->call_error_email_notifications($subject = 'DoVoid failed', $method_name = 'DoVoid', $resArray = $do_void_result);
            }
        }
    }

    /**
     *
     * @param type $order
     */
    public function angelleye_wc_paypal_express_doreauthorization($order) {
        if (!is_object($order)) {
            $order = wc_get_order($order);
        }
        // ensure the authorization is still valid for capture
        if ($this->has_authorization_expired($order->id)) {
            return;
        }
        $this->payment_method = get_post_meta($order->id, '_payment_method', true);
        remove_action('woocommerce_order_action_wc_paypal_express_doreauthorization', array($this, 'angelleye_wc_paypal_express_doreauthorization'));
        remove_action('woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Data::save', 40, 2);
        $this->call_do_reauthorization($order);
    }

    public function call_do_reauthorization($order) {
        $this->add_ec_angelleye_paypal_php_library();
        $this->ec_add_log('call_do_reauthorization function call');
        $transaction_id = get_post_meta($order->id, '_transaction_id', true);
        if (isset($transaction_id) && !empty($transaction_id)) {
            $DRFields = array(
                'authorizationid' => $transaction_id, // Required. The value of a previously authorized transaction ID returned by PayPal.
                'amt' => $order->order_total, // Required. Must have two decimal places.  Decimal separator must be a period (.) and optional thousands separator must be a comma (,)
                'currencycode' => get_woocommerce_currency(), // Three-character currency code.
                'msgsubid' => ''      // A message ID used for idempotence to uniquely identify a message.
            );
            $PayPalRequestData = array('DRFields' => $DRFields);
            $do_reauthorization_result = $this->paypal->DoReauthorization($PayPalRequestData);
            $ack = strtoupper($do_reauthorization_result["ACK"]);
            if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING") {
                $order->add_order_note(__('PayPal DoReauthorization', 'paypal-for-woocommerce') .
                        ' ( Response Code: ' . $do_reauthorization_result["ACK"] . ", " .
                        ' DoReauthorization AUTHORIZATIONID: ' . $do_reauthorization_result['AUTHORIZATIONID'] . ' )'
                );
                $payment_order_meta = array('_transaction_id' => $do_reauthorization_result['AUTHORIZATIONID'], '_payment_action' => 'DoReauthorization');
                self::angelleye_add_order_meta($order->id, $payment_order_meta);
                $order->update_status('on-hold');
            } else {
                $ErrorCode = urldecode($do_reauthorization_result["L_ERRORCODE0"]);
                $ErrorShortMsg = urldecode($do_reauthorization_result["L_SHORTMESSAGE0"]);
                $ErrorLongMsg = urldecode($do_reauthorization_result["L_LONGMESSAGE0"]);
                $ErrorSeverityCode = urldecode($do_reauthorization_result["L_SEVERITYCODE0"]);
                $this->ec_add_log(__('PayPal DoReauthorization API call failed. ', $this->plugin_name));
                $this->ec_add_log(__('Detailed Error Message: ', 'paypal-for-woocommerce') . $ErrorLongMsg);
                $this->ec_add_log(__('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg);
                $this->ec_add_log(__('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode);
                $this->ec_add_log(__('Error Severity Code: ', 'paypal-for-woocommerce') . $ErrorSeverityCode);
                $order->add_order_note(__('PayPal DoReauthorization API call failed. ', $this->plugin_name) .
                        ' ( Detailed Error Message: ' . $ErrorLongMsg . ", " .
                        ' Short Error Message: ' . $ErrorShortMsg . ' )' .
                        ' Error Code: ' . $ErrorCode . ' )' .
                        ' Error Severity Code: ' . $ErrorSeverityCode . ' )'
                );
                $this->call_error_email_notifications($subject = 'DoReauthorization failed', $method_name = 'DoReauthorization', $resArray = $do_reauthorization_result);
            }
        }
    }

    /**
     *
     * @param type $order
     */
    public function angelleye_wc_paypal_pro_doreauthorization($order) {
        $this->add_ec_angelleye_paypal_php_library();
        if (!is_object($order)) {
            $order = wc_get_order($order);
        }
        // ensure the authorization is still valid for capture
        if ($this->has_authorization_expired($order->id)) {
            return;
        }
        $this->payment_method = get_post_meta($order->id, '_payment_method', true);
        remove_action('woocommerce_order_action_wc_paypal_pro_doreauthorization', array($this, 'angelleye_wc_paypal_pro_doreauthorization'));
        remove_action('woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Data::save', 40, 2);
        $this->call_do_reauthorization($order);
    }

    /**
     *
     * @param type $order
     */
    public function angelleye_wc_paypal_express_doauthorization($order) {
        $this->add_ec_angelleye_paypal_php_library();
        if (!is_object($order)) {
            $order = wc_get_order($order);
        }
        // ensure the authorization is still valid for capture
        if ($this->has_authorization_expired($order->id)) {
            return;
        }
        $this->payment_method = get_post_meta($order->id, '_payment_method', true);
        remove_action('woocommerce_order_action_wc_paypal_express_doauthorization', array($this, 'angelleye_wc_paypal_express_doauthorization'));
        remove_action('woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Data::save', 40, 2);
        $this->call_do_authorization($order);
    }

    public function call_do_authorization($order) {
        $this->add_ec_angelleye_paypal_php_library();
        $this->ec_add_log('call_do_reauthorization function call');
        $transaction_id = get_post_meta($order->id, '_transaction_id', true);
        if (isset($transaction_id) && !empty($transaction_id)) {
            $DRFields = array(
                'TRANSACTIONID' => $transaction_id, // Required. The value of a previously authorized transaction ID returned by PayPal.
                'AMT' => $order->order_total, // Required. Must have two decimal places.  Decimal separator must be a period (.) and optional thousands separator must be a comma (,)
                'CURRENCYCODE' => get_woocommerce_currency(), // Three-character currency code.
                'TRANSACTIONENTITY' => 'Order'
            );
            $PayPalRequestData = array('DAFields' => $DRFields);
            $do_reauthorization_result = $this->paypal->DoAuthorization($PayPalRequestData);
            $ack = strtoupper($do_reauthorization_result["ACK"]);
            if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING") {
                $order->add_order_note(__('PayPal DoReauthorization', 'paypal-for-woocommerce') .
                        ' ( Response Code: ' . $do_reauthorization_result["ACK"] . ", " .
                        ' DoReauthorization AUTHORIZATIONID: ' . $do_reauthorization_result['AUTHORIZATIONID'] . ' )'
                );
                $payment_order_meta = array('_transaction_id' => $do_reauthorization_result['AUTHORIZATIONID'], '_payment_action' => 'DoReauthorization');
                self::angelleye_add_order_meta($order->id, $payment_order_meta);
                $order->update_status('on-hold');
            } else {
                $ErrorCode = urldecode($do_reauthorization_result["L_ERRORCODE0"]);
                $ErrorShortMsg = urldecode($do_reauthorization_result["L_SHORTMESSAGE0"]);
                $ErrorLongMsg = urldecode($do_reauthorization_result["L_LONGMESSAGE0"]);
                $ErrorSeverityCode = urldecode($do_reauthorization_result["L_SEVERITYCODE0"]);
                $this->ec_add_log(__('PayPal DoReauthorization API call failed. ', $this->plugin_name));
                $this->ec_add_log(__('Detailed Error Message: ', 'paypal-for-woocommerce') . $ErrorLongMsg);
                $this->ec_add_log(__('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg);
                $this->ec_add_log(__('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode);
                $this->ec_add_log(__('Error Severity Code: ', 'paypal-for-woocommerce') . $ErrorSeverityCode);
                $order->add_order_note(__('PayPal DoReauthorization API call failed. ', $this->plugin_name) .
                        ' ( Detailed Error Message: ' . $ErrorLongMsg . ", " .
                        ' Short Error Message: ' . $ErrorShortMsg . ' )' .
                        ' Error Code: ' . $ErrorCode . ' )' .
                        ' Error Severity Code: ' . $ErrorSeverityCode . ' )'
                );
                $this->call_error_email_notifications($subject = 'DoReauthorization failed', $method_name = 'DoReauthorization', $resArray = $do_reauthorization_result);
            }
        }
    }

    function ec_add_log($message) {
        if ($this->ec_debug == 'yes') {
            if (empty($this->log))
                $this->log = new WC_Logger();
            $this->log->add($this->payment_method, $message);
        }
    }

    public function call_error_email_notifications($subject = null, $method_name = null, $resArray = null) {
        if ((isset($resArray["L_ERRORCODE0"]) && !empty($resArray["L_ERRORCODE0"])) && ( isset($resArray["L_SHORTMESSAGE0"]) && !empty($resArray["L_SHORTMESSAGE0"]))) {
            $ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
            $ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
            $ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
            $ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);
            $this->ec_add_log(__($method_name . ' API call failed. ', 'paypal-for-woocommerce'));
            $this->ec_add_log(__('Detailed Error Message: ', 'paypal-for-woocommerce') . $ErrorLongMsg);
            $this->ec_add_log(__('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg);
            $this->ec_add_log(__('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode);
            $this->ec_add_log(__('Error Severity Code: ', 'paypal-for-woocommerce') . $ErrorSeverityCode);
            $message = '';
            if ($this->error_email_notify) {
                $admin_email = get_option("admin_email");
                $message .= __($method_name, "paypal-for-woocommerce") . "\n\n";
                $message .= __('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode . "\n";
                $message .= __('Error Severity Code: ', 'paypal-for-woocommerce') . $ErrorSeverityCode . "\n";
                $message .= __('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg . "\n";
                $message .= __('Detailed Error Message: ', 'paypal-for-woocommerce') . $ErrorLongMsg . "\n";
                $ofw_error_email_notify_mes = apply_filters('angelleye_error_email_notify_message', $message, $ErrorCode, $ErrorSeverityCode, $ErrorShortMsg, $ErrorLongMsg);
                $ofw_error_email_notify_subject = apply_filters('angelleye_error_email_notify_subject', $subject);
                wp_mail($admin_email, $ofw_error_email_notify_subject, $ofw_error_email_notify_mes);
            }
        }
        if ((isset($resArray["Errors"][0]['ErrorID']) && !empty($resArray["Errors"][0]['ErrorID'])) && ( isset($resArray["Errors"][0]['Message']) && !empty($resArray["Errors"][0]['Message']))) {
            $ErrorCode = $resArray["Errors"][0]['ErrorID'];
            $ErrorShortMsg = $resArray["Errors"][0]['Message'];
            $this->ec_add_log(__($method_name . ' API call failed. ', 'paypal-for-woocommerce'));
            $this->ec_add_log(__('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg);
            $this->ec_add_log(__('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode);
            $message = '';
            if ($this->error_email_notify) {
                $admin_email = get_option("admin_email");
                $message .= __($method_name, "paypal-for-woocommerce") . "\n\n";
                $message .= __('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode . "\n";
                $message .= __('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg . "\n";
                $ofw_error_email_notify_mes = apply_filters('angelleye_error_email_notify_message', $message, $ErrorCode, $ErrorShortMsg);
                $ofw_error_email_notify_subject = apply_filters('angelleye_error_email_notify_subject', $subject);
                wp_mail($admin_email, $ofw_error_email_notify_subject, $ofw_error_email_notify_mes);
            }
        }
    }

    public function angelleye_woocommerce_payment_gateway_supports($boolean, $feature, $current) {
        global $post;
        $order_id = $post->ID;
        if ($current->id == 'paypal_express' || $current->id == 'paypal_pro' ) {
            $payment_action = get_post_meta($order_id, '_payment_action', true);
            if($payment_action == 'Sale' || $payment_action == 'DoCapture') {
                return $boolean;
            } else {
                return false;
            }
        } else {
            return $boolean;
        }
        
    }

}
