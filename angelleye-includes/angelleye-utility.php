<?php

/**
 * @class       AngellEYE_Utility
 * @version	1.1.9.2
 * @package	paypal-for-woocommerce
 * @category	Class
 * @author      Angell EYE <service@angelleye.com>
 */
class AngellEYE_Utility {

    public $plugin_name;
    public $version;
    public $paypal;
    public $testmode;
    public $api_username;
    public $api_password;
    public $api_signature;
    public $ec_debug;
    public $payment_method;
    public $error_email_notify;
    public $angelleye_woocommerce_order_actions;
    public $total_Order;
    public $total_DoVoid;
    public $total_DoCapture;
    public $total_Pending_DoAuthorization;
    public $total_Completed_DoAuthorization;
    public $total_DoReauthorization;
    public $max_authorize_amount;
    public $remain_authorize_amount;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->load_dependencies();
    }

    public function add_ec_angelleye_paypal_php_library() {
        if (!class_exists('WC_Payment_Gateway')) {
            return false;
        }
        if (!class_exists('Angelleye_PayPal')) {
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.class.php' );
        }
        if( empty($this->payment_method) || $this->payment_method == false) {
            $this->angelleye_set_payment_method();
        }
        if( empty($this->payment_method) || $this->payment_method == false) {
            return false;
        }
        if ($this->payment_method == 'paypal_express') {
            $gateway_obj = new WC_Gateway_PayPal_Express_AngellEYE();
        } else if($this->payment_method == 'paypal_pro') {
            $gateway_obj = new WC_Gateway_PayPal_Pro_AngellEYE();
        } else {
            return false;
        }
        $this->testmode = $gateway_obj->get_option('testmode');
        if ($this->testmode == 'yes') {
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

    public function load_dependencies() {
        add_action('init', array($this, 'paypal_for_woocommerce_paypal_transaction_history'), 5);
        if (is_admin() && !defined('DOING_AJAX')) {
            add_action('add_meta_boxes', array($this, 'angelleye_paypal_for_woocommerce_order_action_meta_box'), 10, 2);
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
        add_action('woocommerce_process_shop_order_meta', array($this, 'save'), 51, 2);
    }

    public function angelleye_woocommerce_order_actions($order_actions = array()) {
        global $post;
        $order_id = $post->ID;
        if (!is_object($order_id)) {
            $order = wc_get_order($order);
        }
        $paypal_payment_action = array();
        $this->payment_method = get_post_meta($order_id, '_payment_method', true);
        $payment_action = get_post_meta($order_id, '_payment_action', true);
        if ((isset($this->payment_method) && !empty($this->payment_method)) && (isset($payment_action) && !empty($payment_action)) && !$this->has_authorization_expired($post->ID)) {
            switch ($this->payment_method) {
                case 'paypal_express': {
                        $paypal_payment_action = array();
                        $this->total_Order = self::get_total('Order', 'Pending', $order_id);
                        $this->total_DoVoid = self::get_total('DoVoid', '', $order_id);
                        $this->total_DoCapture = self::get_total('DoCapture', 'Completed', $order_id);
                        $this->total_Pending_DoAuthorization = self::get_total('DoAuthorization', 'Pending', $order_id);
                        $this->total_Completed_DoAuthorization = self::get_total('DoAuthorization', 'Completed', $order_id);
                        $this->total_DoReauthorization = self::get_total('DoReauthorization', '', $order_id);
                        switch ($payment_action) {
                            case ($payment_action == 'Order'):
                                $this->angelleye_max_authorize_amount($order_id);
                                $this->angelleye_remain_authorize_amount();
                                if ($this->max_authorize_amount == $this->total_DoVoid || $this->max_authorize_amount == $this->total_DoCapture) {
                                    return $paypal_payment_action;
                                } else {
                                    $paypal_payment_action = array('DoCapture' => 'Capture Authorization', 'DoVoid' => 'Void Authorization', 'DoAuthorization' => 'Authorization');
                                    if ($this->total_Completed_DoAuthorization == $this->total_Pending_DoAuthorization || $this->total_Pending_DoAuthorization == 0 || $this->total_Pending_DoAuthorization == $this->total_DoCapture || $this->total_DoCapture == $order->get_total() - $order->get_total_refunded()) {
                                        unset($paypal_payment_action['DoCapture']);
                                    }
                                    if ($this->total_Pending_DoAuthorization == 0 && $this->total_Completed_DoAuthorization > 0 || $this->total_Pending_DoAuthorization == $this->total_DoCapture) {
                                        unset($paypal_payment_action['DoVoid']);
                                    }
                                    if ($this->max_authorize_amount == self::round($this->total_Pending_DoAuthorization + $this->total_Completed_DoAuthorization)) {
                                        unset($paypal_payment_action['DoAuthorization']);
                                    }
                                    return $paypal_payment_action;
                                }
                                break;
                            case ($payment_action == 'Authorization'):
                                $paypal_payment_action = array('DoCapture' => 'Capture Authorization', 'DoReauthorization' => 'Authorization', 'DoVoid' => 'Void Authorization');
                                $transaction_id = get_post_meta($order_id, '_first_transaction_id', true);
                                if (!$this->has_authorization_inside_honor_period($transaction_id)) {
                                    unset($paypal_payment_action['DoReauthorization']);
                                }
                                if (!is_object($order_id)) {
                                    $order = wc_get_order($order_id);
                                }
                                if ($order->order_total == $this->total_DoVoid || $this->total_Completed_DoAuthorization == $order->order_total || $order->order_total == $this->total_DoCapture || $this->total_DoCapture == $order->get_total() - $order->get_total_refunded()) {
                                    unset($paypal_payment_action['DoCapture']);
                                    unset($paypal_payment_action['DoVoid']);
                                }
                                return $paypal_payment_action;
                        }
                    }
                case 'paypal_pro': {
                        switch ($payment_action) {
                            case ($payment_action == 'Authorization'):
                                $this->total_DoVoid = self::get_total('DoVoid', '', $order_id);
                                $this->total_DoCapture = self::get_total('DoCapture', 'Completed', $order_id);
                                if ($payment_action == 'Order') {
                                    $Authorization = 'DoAuthorization';
                                } else {
                                    $Authorization = 'authorization';
                                }
                                $this->total_Pending_DoAuthorization = self::get_total($Authorization, 'Pending', $order_id);
                                $this->total_Completed_DoAuthorization = self::get_total($Authorization, 'Completed', $order_id);
                                $this->total_DoReauthorization = self::get_total('DoReauthorization', '', $order_id);
                                $paypal_payment_action = array('DoCapture' => 'Capture Authorization', 'DoReauthorization' => 'Reauthorization', 'DoVoid' => 'Void Authorization');
                                $this->angelleye_max_authorize_amount($order_id);
                                $this->angelleye_remain_authorize_amount();
                                $transaction_id = get_post_meta($order_id, '_first_transaction_id', true);
                                if (!$this->has_authorization_inside_honor_period($transaction_id)) {
                                    unset($paypal_payment_action['DoReauthorization']);
                                }
                                if (!is_object($order_id)) {
                                    $order = wc_get_order($order_id);
                                }
                                if ($order->get_total() == $this->total_DoVoid || $this->total_Completed_DoAuthorization == $order->get_total() - $order->get_total_refunded() || $this->total_DoCapture == $order->get_total() - $order->get_total_refunded()) {
                                    unset($paypal_payment_action['DoCapture']);
                                    unset($paypal_payment_action['DoVoid']);
                                }
                                return $paypal_payment_action;
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

    public function has_authorization_inside_honor_period($transaction_id) {
        $transaction_post_is = $this->get_post_by_title($transaction_id);
        $transaction_time = strtotime(get_post_meta($transaction_post_is, '_trans_date', true));
        return floor(( time() - $transaction_time ) / 3600) > 72;
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
        if (isset($_POST['angelleye_paypal_capture_transaction_dropdown']) && !empty($_POST['angelleye_paypal_capture_transaction_dropdown'])) {
            $transaction_id = $_POST['angelleye_paypal_capture_transaction_dropdown'];
        } else {
            $transaction_id = get_post_meta($order->id, '_transaction_id', true);
        }
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
        $transaction_id = get_post_meta($order->id, '_transaction_id', true);
        remove_action('woocommerce_order_action_wc_paypal_pro_docapture', array($this, 'angelleye_wc_paypal_pro_docapture'));
        remove_action('woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Data::save', 40, 2);
        $this->pfw_do_capture($order, $transaction_id, $order->order_total);
    }

    public function pfw_do_capture($order, $transaction_id = null, $capture_total = null) {
        $this->add_ec_angelleye_paypal_php_library();
        $this->ec_add_log('DoCapture API call');

        if($capture_total == null)
            $AMT = $this->get_amount_by_transaction_id($transaction_id);
        else
            $AMT = $capture_total;

        $AMT = self::round($AMT - $order->get_total_refunded());
        $DataArray = array(
            'AUTHORIZATIONID' => $transaction_id,
            'AMT' => $AMT,
            'CURRENCYCODE' => $order->get_order_currency(),
            'COMPLETETYPE' => 'NotComplete',
        );
        $PayPalRequest = array(
            'DCFields' => $DataArray
        );
        $do_capture_result = $this->paypal->DoCapture($PayPalRequest);
        $this->angelleye_write_request_response_api_log($do_capture_result);
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
            if ($do_capture_result['PAYMENTSTATUS'] == 'Completed') {
                $AUTHORIZATIONID = $this->get_post_by_title($transaction_id);
                if ($AUTHORIZATIONID != null) {
                    update_post_meta($AUTHORIZATIONID, 'PAYMENTSTATUS', $do_capture_result['PAYMENTSTATUS']);
                }
            }
            $payment_order_meta = array('_transaction_id' => $do_capture_result['TRANSACTIONID']);
            self::angelleye_add_order_meta($order->id, $payment_order_meta);
            self::angelleye_paypal_for_woocommerce_add_paypal_transaction($do_capture_result, $order, 'DoCapture');
            $this->angelleye_paypal_for_woocommerce_order_status_handler($order);
        } else {
            $ErrorCode = urldecode($do_capture_result["L_ERRORCODE0"]);
            $ErrorShortMsg = urldecode($do_capture_result["L_SHORTMESSAGE0"]);
            $ErrorLongMsg = urldecode($do_capture_result["L_LONGMESSAGE0"]);
            $ErrorSeverityCode = urldecode($do_capture_result["L_SEVERITYCODE0"]);
            $this->ec_add_log(__('PayPal DoCapture API call failed. ', 'paypal-for-woocommerce'));
            $this->ec_add_log(__('Detailed Error Message: ', 'paypal-for-woocommerce') . $ErrorLongMsg);
            $this->ec_add_log(__('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg);
            $this->ec_add_log(__('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode);
            $this->ec_add_log(__('Error Severity Code: ', 'paypal-for-woocommerce') . $ErrorSeverityCode);
            $order->add_order_note(__('PayPal DoCapture API call failed. ', 'paypal-for-woocommerce') .
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
        remove_action('woocommerce_order_action_wc_paypal_express_dovoid', array($this, 'angelleye_wc_paypal_pro_dovoid'));
        remove_action('woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Data::save', 40, 2);
        $this->call_do_void($order);
    }

    public function call_do_void($order) {
        $this->add_ec_angelleye_paypal_php_library();
        $this->ec_add_log('DoVoid API call');
        if (isset($_POST['angelleye_paypal_dovoid_transaction_dropdown']) && !empty($_POST['angelleye_paypal_dovoid_transaction_dropdown'])) {
            $transaction_id = $_POST['angelleye_paypal_dovoid_transaction_dropdown'];
        } else {
            $transaction_id = get_post_meta($order->id, '_first_transaction_id', true);
        }
        if (isset($transaction_id) && !empty($transaction_id)) {
            $DVFields = array(
                'authorizationid' => $transaction_id,
                'note' => '',
                'msgsubid' => ''
            );
            $PayPalRequestData = array('DVFields' => $DVFields);
            $do_void_result = $this->paypal->DoVoid($PayPalRequestData);
            $this->angelleye_write_request_response_api_log($do_void_result);
            $ack = strtoupper($do_void_result["ACK"]);
            if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING") {
                $order->add_order_note(__('PayPal DoVoid', 'paypal-for-woocommerce') .
                        ' ( Response Code: ' . $do_void_result["ACK"] . ", " .
                        ' DoVoid AUTHORIZATIONID: ' . $do_void_result['AUTHORIZATIONID'] . ' )'
                );
                $this->angelleye_get_transactionDetails($do_void_result['AUTHORIZATIONID']);
                $payment_order_meta = array('_transaction_id' => $do_void_result['AUTHORIZATIONID']);
                self::angelleye_add_order_meta($order->id, $payment_order_meta);
                self::angelleye_paypal_for_woocommerce_add_paypal_transaction($do_void_result, $order, 'DoVoid');
                $this->angelleye_paypal_for_woocommerce_order_status_handler($order);
            } else {
                $ErrorCode = urldecode($do_void_result["L_ERRORCODE0"]);
                $ErrorShortMsg = urldecode($do_void_result["L_SHORTMESSAGE0"]);
                $ErrorLongMsg = urldecode($do_void_result["L_LONGMESSAGE0"]);
                $ErrorSeverityCode = urldecode($do_void_result["L_SEVERITYCODE0"]);
                $this->ec_add_log(__('PayPal DoVoid API call failed. ', 'paypal-for-woocommerce'));
                $this->ec_add_log(__('Detailed Error Message: ', 'paypal-for-woocommerce') . $ErrorLongMsg);
                $this->ec_add_log(__('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg);
                $this->ec_add_log(__('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode);
                $this->ec_add_log(__('Error Severity Code: ', 'paypal-for-woocommerce') . $ErrorSeverityCode);
                $order->add_order_note(__('PayPal DoVoid API call failed. ', 'paypal-for-woocommerce') .
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
        $this->payment_method = get_post_meta($order->id, '_payment_method', true);
        remove_action('woocommerce_order_action_wc_paypal_express_doreauthorization', array($this, 'angelleye_wc_paypal_express_doreauthorization'));
        remove_action('woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Data::save', 40, 2);
        $this->call_do_reauthorization($order);
    }

    public function call_do_reauthorization($order) {
        $this->add_ec_angelleye_paypal_php_library();
        $this->ec_add_log('DoReauthorization API call');
        if (isset($_POST['angelleye_paypal_doreauthorization_transaction_dropdown']) && !empty($_POST['angelleye_paypal_doreauthorization_transaction_dropdown'])) {
            $transaction_id = $_POST['angelleye_paypal_doreauthorization_transaction_dropdown'];
        } else {
            $transaction_id = get_post_meta($order->id, '_first_transaction_id', true);
        }
        $AMT = $this->get_amount_by_transaction_id($transaction_id);
        if (isset($transaction_id) && !empty($transaction_id)) {
            $DRFields = array(
                'authorizationid' => $transaction_id, // Required. The value of a previously authorized transaction ID returned by PayPal.
                'amt' => $AMT, // Required. Must have two decimal places.  Decimal separator must be a period (.) and optional thousands separator must be a comma (,)
                'currencycode' => $order->get_order_currency(), // Three-character currency code.
                'msgsubid' => ''      // A message ID used for idempotence to uniquely identify a message.
            );
            $PayPalRequestData = array('DRFields' => $DRFields);
            $do_reauthorization_result = $this->paypal->DoReauthorization($PayPalRequestData);
            $this->angelleye_write_request_response_api_log($do_reauthorization_result);
            $ack = strtoupper($do_reauthorization_result["ACK"]);
            if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING") {
                $order->add_order_note(__('PayPal DoReauthorization', 'paypal-for-woocommerce') .
                        ' ( Response Code: ' . $do_reauthorization_result["ACK"] . ", " .
                        ' DoReauthorization AUTHORIZATIONID: ' . $do_reauthorization_result['AUTHORIZATIONID'] . ' )'
                );
                $payment_order_meta = array('_transaction_id' => $do_reauthorization_result['AUTHORIZATIONID']);
                self::angelleye_add_order_meta($order->id, $payment_order_meta);
                self::angelleye_paypal_for_woocommerce_add_paypal_transaction($do_reauthorization_result, $order, 'DoReauthorization');
            } else {
                $ErrorCode = urldecode($do_reauthorization_result["L_ERRORCODE0"]);
                $ErrorShortMsg = urldecode($do_reauthorization_result["L_SHORTMESSAGE0"]);
                $ErrorLongMsg = urldecode($do_reauthorization_result["L_LONGMESSAGE0"]);
                $ErrorSeverityCode = urldecode($do_reauthorization_result["L_SEVERITYCODE0"]);
                $this->ec_add_log(__('PayPal DoReauthorization API call failed. ', 'paypal-for-woocommerce'));
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
        remove_action('woocommerce_order_action_wc_paypal_express_doauthorization', array($this, 'angelleye_wc_paypal_express_doauthorization'));
        remove_action('woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Data::save', 40, 2);
        $this->call_do_authorization($order);
    }

    public function call_do_authorization($order) {
        $this->add_ec_angelleye_paypal_php_library();
        $this->ec_add_log('DoAuthorization API call');
        $transaction_id = get_post_meta($order->id, '_first_transaction_id', true);
        if (isset($transaction_id) && !empty($transaction_id)) {
            $DRFields = array(
                'TRANSACTIONID' => $transaction_id, // Required. The value of a previously authorized transaction ID returned by PayPal.
                'AMT' => $_POST['_regular_price'], // Required. Must have two decimal places.  Decimal separator must be a period (.) and optional thousands separator must be a comma (,)
                'CURRENCYCODE' => $order->get_order_currency()
            );
            $PayPalRequestData = array('DAFields' => $DRFields);
            $do_authorization_result = $this->paypal->DoAuthorization($PayPalRequestData);
            $this->angelleye_write_request_response_api_log($do_authorization_result);
            $ack = strtoupper($do_authorization_result["ACK"]);
            if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING") {
                $order->add_order_note(__('PayPal authorization', 'paypal-for-woocommerce') .
                        ' ( Response Code: ' . $do_authorization_result["ACK"] . ", " .
                        ' DoAuthorization AUTHORIZATIONID: ' . $do_authorization_result['TRANSACTIONID'] . ' )'
                );
                $payment_order_meta = array('_transaction_id' => $do_authorization_result['TRANSACTIONID']);
                self::angelleye_add_order_meta($order->id, $payment_order_meta);
                self::angelleye_paypal_for_woocommerce_add_paypal_transaction($do_authorization_result, $order, 'DoAuthorization');
            } else {
                $ErrorCode = urldecode($do_authorization_result["L_ERRORCODE0"]);
                $ErrorShortMsg = urldecode($do_authorization_result["L_SHORTMESSAGE0"]);
                $ErrorLongMsg = urldecode($do_authorization_result["L_LONGMESSAGE0"]);
                $ErrorSeverityCode = urldecode($do_authorization_result["L_SEVERITYCODE0"]);
                $this->ec_add_log(__('PayPal DoAuthorization API call failed. ', 'paypal-for-woocommerce'));
                $this->ec_add_log(__('Detailed Error Message: ', 'paypal-for-woocommerce') . $ErrorLongMsg);
                $this->ec_add_log(__('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg);
                $this->ec_add_log(__('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode);
                $this->ec_add_log(__('Error Severity Code: ', 'paypal-for-woocommerce') . $ErrorSeverityCode);
                $order->add_order_note(__('PayPal DoAuthorization API call failed. ', 'paypal-for-woocommerce') .
                        ' ( Detailed Error Message: ' . $ErrorLongMsg . ", " .
                        ' Short Error Message: ' . $ErrorShortMsg . ' )' .
                        ' Error Code: ' . $ErrorCode . ' )' .
                        ' Error Severity Code: ' . $ErrorSeverityCode . ' )'
                );
                $this->call_error_email_notifications($subject = 'DoAuthorization failed', $method_name = 'DoAuthorization', $resArray = $do_authorization_result);
            }
        }
    }

    public function ec_add_log($message) {
        if ($this->ec_debug == 'yes') {
            if (empty($this->log)) {
                $this->log = new WC_Logger();
            }
            $this->log->add(str_replace("_","-", $this->payment_method) , $message);
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
        if( empty($post->ID) ) {
           return false;
        }
        $order_id = $post->ID;
        $payment_action = '';
        if ($current->id == 'paypal_express' || $current->id == 'paypal_pro') {
            $payment_action = get_post_meta($order_id, '_payment_action', true);
            if ($payment_action == 'Sale' || $payment_action == 'DoCapture' || empty($payment_action)) {
                return $boolean;
            } else {
                return false;
            }
        } else {
            return $boolean;
        }
    }

    public function angelleye_write_request_response_api_log($PayPalResult) {
        $PayPalRequest = isset($PayPalResult['RAWREQUEST']) ? $PayPalResult['RAWREQUEST'] : '';
        $PayPalResponse = isset($PayPalResult['RAWRESPONSE']) ? $PayPalResult['RAWRESPONSE'] : '';
        $this->ec_add_log('Request: ' . print_r($this->paypal->NVPToArray($this->paypal->MaskAPIResult($PayPalRequest)), true));
        $this->ec_add_log('Response: ' . print_r($this->paypal->NVPToArray($this->paypal->MaskAPIResult($PayPalResponse)), true));
    }

    public static function angelleye_paypal_credit_card_rest_setting_fields() {
        return array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable PayPal Credit Card (REST)', 'paypal-for-woocommerce'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'paypal-for-woocommerce'),
                'default' => __('PayPal Credit Card (REST)', 'paypal-for-woocommerce')
            ),
            'description' => array(
                'title' => __('Description', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the description which the user sees during checkout.', 'paypal-for-woocommerce'),
                'default' => __('PayPal Credit Card (REST) description', 'paypal-for-woocommerce')
            ),
            'testmode' => array(
                'title' => __('Test Mode', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable PayPal Sandbox/Test Mode', 'paypal-for-woocommerce'),
                'default' => 'yes',
                'description' => sprintf(__('Place the payment gateway in development mode. Sign up for a developer account <a href="%s" target="_blank">here</a>', 'paypal-for-woocommerce'), 'https://developer.paypal.com/'),
            ),
            'rest_client_id_sandbox' => array(
                'title' => __('Sandbox Client ID', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => 'Enter your Sandbox PayPal Rest API Client ID',
                'default' => ''
            ),
            'rest_secret_id_sandbox' => array(
                'title' => __('Sandbox Secret ID', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => __('Enter your Sandbox PayPal Rest API Secret ID.', 'paypal-for-woocommerce'),
                'default' => ''
            ),
            'rest_client_id' => array(
                'title' => __('Live Client ID', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => 'Enter your PayPal Rest API Client ID',
                'default' => ''
            ),
            'rest_secret_id' => array(
                'title' => __('Live Secret ID', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => __('Enter your PayPal Rest API Secret ID.', 'paypal-for-woocommerce'),
                'default' => ''
            ),
            'enable_tokenized_payments' => array(
                'title' => __('Enable Tokenized Payments', 'paypal-for-woocommerce'),
                'label' => __('Enable Tokenized Payments', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Allow buyers to securely save payment details to their account for quick checkout / auto-ship orders in the future.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'class' => 'enable_tokenized_payments'
            ),
            'invoice_prefix' => array(
                'title' => __('Invoice Prefix', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Please enter a prefix for your invoice numbers. If you use your PayPal account for multiple stores ensure this prefix is unique as PayPal will not allow orders with the same invoice number.', 'paypal-for-woocommerce'),
                'default' => 'WC-PCCR',
                'desc_tip' => true,
            ),
            'card_icon'        => array(
                'title'       => __( 'Card Icon', 'paypal-for-woocommerce' ),
                'type'        => 'text',
                'default'     => plugins_url('/assets/images/cards.png', plugin_basename(dirname(__FILE__)))
            ),
            'debug' => array(
                'title' => __('Debug Log', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'paypal-for-woocommerce'),
                'default' => 'no',
                'description' => sprintf(__('Log PayPal events, such as Secured Token requests, inside <code>%s</code>', 'paypal-for-woocommerce'), wc_get_log_file_path('paypal_credit_card_rest')),
            ),
            'is_encrypt' => array(
                'title' => __('', 'paypal-for-woocommerce'),
                'label' => __('', 'paypal-for-woocommerce'),
                'type' => 'hidden',
                'default' => 'yes',
                'class' => ''
            )
        );
    }

    public static function card_type_from_account_number($account_number) {
        $types = array(
            'visa' => '/^4/',
            'mastercard' => '/^5[1-5]/',
            'amex' => '/^3[47]/',
            'discover' => '/^(6011|65|64[4-9]|622)/',
            'diners' => '/^(36|38|30[0-5])/',
            'jcb' => '/^35/',
            'maestro' => '/^(5018|5020|5038|6304|6759|676[1-3])/',
            'laser' => '/^(6706|6771|6709)/',
        );
        foreach ($types as $type => $pattern) {
            if (1 === preg_match($pattern, $account_number)) {
                return $type;
            }
        }
        return null;
    }

    public static function is_express_checkout_credentials_is_set() {
        $pp_settings = get_option('woocommerce_paypal_express_settings');
        $testmode = isset($pp_settings['testmode']) ? $pp_settings['testmode'] : 'yes';
        $enabled = (isset($pp_settings['enabled']) && $pp_settings['enabled'] == 'yes') ? 'yes' : 'no';
        if ($testmode == 'yes') {
            $api_username = isset($pp_settings['sandbox_api_username']) ? $pp_settings['sandbox_api_username'] : '';
            $api_password = isset($pp_settings['sandbox_api_password']) ? $pp_settings['sandbox_api_password'] : '';
            $api_signature = isset($pp_settings['sandbox_api_signature']) ? $pp_settings['sandbox_api_signature'] : '';
        } else {
            $api_username = isset($pp_settings['api_username']) ? $pp_settings['api_username'] : '';
            $api_password = isset($pp_settings['api_password']) ? $pp_settings['api_password'] : '';
            $api_signature = isset($pp_settings['api_signature']) ? $pp_settings['api_signature'] : '';
        }
        if ('yes' != $enabled) {
            return false;
        }
        if (!$api_username || !$api_password || !$api_signature) {
            return false;
        }
        return true;
    }

    public function paypal_for_woocommerce_paypal_transaction_history() {

        if (post_type_exists('paypal_transaction')) {
            return;
        }

        do_action('paypal_for_woocommerce_register_post_type');

        register_post_type('paypal_transaction', apply_filters('paypal_for_woocommerce_register_post_type_paypal_transaction_history', array(
            'labels' => array(
                'name' => __('PayPal Transaction', 'paypal-for-woocommerce'),
                'singular_name' => __('PayPal Transaction', 'paypal-for-woocommerce'),
                'menu_name' => _x('PayPal Transaction', 'Admin menu name', 'paypal-for-woocommerce'),
                'add_new' => __('Add PayPal Transaction', 'paypal-for-woocommerce'),
                'add_new_item' => __('Add New PayPal Transaction', 'paypal-for-woocommerce'),
                'edit' => __('Edit', 'paypal-for-woocommerce'),
                'edit_item' => __('View PayPal Transaction', 'paypal-for-woocommerce'),
                'new_item' => __('New PayPal Transaction', 'paypal-for-woocommerce'),
                'view' => __('View PayPal Transaction', 'paypal-for-woocommerce'),
                'view_item' => __('View PayPal Transaction', 'paypal-for-woocommerce'),
                'search_items' => __('Search PayPal Transaction', 'paypal-for-woocommerce'),
                'not_found' => __('No PayPal Transaction found', 'paypal-for-woocommerce'),
                'not_found_in_trash' => __('No PayPal Transaction found in trash', 'paypal-for-woocommerce'),
                'parent' => __('Parent PayPal Transaction', 'paypal-for-woocommerce')
            ),
            'description' => __('This is where you can add new PayPal Transaction to your store.', 'paypal-for-woocommerce'),
            'public' => false,
            'show_ui' => false,
            'capability_type' => 'post',
            'capabilities' => array(
                'create_posts' => false, // Removes support for the "Add New" function
            ),
            'map_meta_cap' => true,
            'publicly_queryable' => true,
            'exclude_from_search' => false,
            'hierarchical' => false, // Hierarchical causes memory issues - WP loads all records!
            'rewrite' => array('slug' => 'paypal_ipn'),
            'query_var' => true,
            'supports' => array('', ''),
            'has_archive' => true,
            'show_in_nav_menus' => FALSE
                        )
                )
        );
    }

    public function angelleye_paypal_for_woocommerce_order_action_meta_box($post_type, $post) {
        if (isset($post->ID) && !empty($post->ID)) {
            if ($this->angelleye_is_display_paypal_transaction_details($post->ID)) {
                add_meta_box('angelleye-pw-order-action', __('PayPal Transaction History', 'paypal-for-woocommerce'), array($this, 'angelleye_paypal_for_woocommerce_order_action_callback'), 'shop_order', 'normal', 'high', null);
            }
        }
    }

    public function angelleye_paypal_for_woocommerce_order_action_callback($post) {
        
        $args = array(
                'post_type' => 'paypal_transaction',
                'posts_per_page' => -1,
                'meta_key' => 'order_id',
                'meta_value' => $post->ID,
                'order' => 'ASC',
                'post_status' => 'any'
            );
            $posts_array = get_posts($args);
            foreach ($posts_array as $post_data):
                $payment_status = get_post_meta($post_data->ID, 'PAYMENTSTATUS', true);
                if( isset($post->post_title) && !empty($post_data->post_title) && isset($payment_status) && $payment_status == 'Pending' ) {
                    $this->angelleye_get_transactionDetails($post_data->post_title);
                }
            endforeach;
            $order = wc_get_order($post->ID);
        
        if (empty($this->angelleye_woocommerce_order_actions)) {
            $this->angelleye_woocommerce_order_actions = $this->angelleye_woocommerce_order_actions();
        }
        ?>
        <div class='wrap'>
            <?php
            if (isset($this->angelleye_woocommerce_order_actions) && !empty($this->angelleye_woocommerce_order_actions)) {
                ?>
                <select name="angelleye_payment_action" id="angelleye_payment_action"> 
                    <?php
                    $i = 0;
                    foreach ($this->angelleye_woocommerce_order_actions as $k => $v) :
                        if ($i == 0) {
                            echo '<option value="" >Select Action</option>';
                        }
                        ?>
                        <option value="<?php echo esc_attr($k); ?>" ><?php echo esc_html($v); ?></option>
                        <?php
                        $i = $i + 1;
                    endforeach;
                    ?>
                </select> 
                <div class="angelleye_authorization_box" style="display: none;">
                    <?php
                    if (isset($this->remain_authorize_amount)) {
                        $remain_authorize_amount_text = 'less than ' . $this->remain_authorize_amount;
                    } else {
                        $remain_authorize_amount_text = '';
                    }
                    ?>
                    <input type="text" placeholder="Enter amount <?php echo $remain_authorize_amount_text; ?>" id="_regular_price" name="_regular_price" class="short wc_input_price text-box" style="width: 220px">
                </div>
                <?php $this->angelleye_express_checkout_transaction_capture_dropdownbox($post->ID); ?>
                <input type="submit" id="angelleye_payment_submit_button" value="Submit" name="save" class="button button-primary" style="display: none">
                <br/><br/><br/>
                <script>
                (function($) {
                    "use strict";
                    
                    //Asking confirm for the capture
                    $('#angelleye_payment_submit_button').on('click', function(){
                        var selected = $('#angelleye_payment_action option:checked').val();
                        if(selected == 'DoCapture') {
                            var amt = $('.angelleye_order_action_table:first tr:first td:last').text();

                            return confirm('You are capturing: ' + amt + '. Are you sure?');
                        }
                    })

                    MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
                    var observer = new MutationObserver(function(mutations, observer) {
                        var currency_symbol = window.woocommerce_admin_meta_boxes.currency_format_symbol;
                        
                        for(var i = 0, len = mutations.length; i < len; i++) {
                            //Updating the total order action table field
                            if(mutations[i].target.className == 'inside' && mutations[i].addedNodes.length > 0) {
                                var new_amt_with_curr = $('.wc-order-refund-items .wc-order-totals tr td.total .amount:last').text();
                                //Adjusting price with paypal-for-woocommerce amount format
                                new_amt_with_curr = currency_symbol + new_amt_with_curr.replace(currency_symbol, '');
                                $('.angelleye_order_action_table:first tr:first td:last').text(new_amt_with_curr);
                            }
                        }
                    });

                    //Setting an observer to know about total new total amount
                    $(document).ready(function () {
                        var target = document.getElementById('woocommerce-order-items').getElementsByClassName('inside')[0];
                        observer.observe(target, {
                            childList: true,
                        });
                    });
                })(jQuery);
                </script>
                <?php
            }
            
            ?>
            <table class="widefat angelleye_order_action_table" style="width: 190px;float: right;">
                <tbody>
                    <tr>
                        <td><?php echo __('Order Total:', 'paypal-for-woocommerce'); ?></td>
                        <td><?php echo $order->get_formatted_order_total(); ?></td>
                    </tr>
                    <tr>
                        <td><?php echo __('Total Capture:', 'paypal-for-woocommerce'); ?></td>
                        <td><?php echo get_woocommerce_currency_symbol() . $this->total_DoCapture; ?></td>
                    </tr>
                </tbody>
            </table>
            <br/><br/>
            <table class="widefat angelleye_order_action_table">
                <thead>
                    <tr>
                        <th><?php echo __('Transaction ID', 'paypal-for-woocommerce'); ?></th>
                        <th><?php echo __('Date', 'paypal-for-woocommerce'); ?></th>       
                        <th><?php echo __('Amount', 'paypal-for-woocommerce'); ?></th>
                        <th><?php echo __('Payment Status', 'paypal-for-woocommerce'); ?></th>
                        <th><?php echo __('Payment Action', 'paypal-for-woocommerce'); ?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th><?php echo __('Transaction ID', 'paypal-for-woocommerce'); ?></th>
                        <th><?php echo __('Date', 'paypal-for-woocommerce'); ?></th>       
                        <th><?php echo __('Amount', 'paypal-for-woocommerce'); ?></th>
                        <th><?php echo __('Payment Status', 'paypal-for-woocommerce'); ?></th>
                        <th><?php echo __('Payment Action', 'paypal-for-woocommerce'); ?></th>
                    </tr>
                </tfoot>
                <tbody>
                    <?php
                    foreach ($posts_array as $post):
                        ?>
                        <tr>
                            <td><?php echo $post->post_title; ?></td>
                            <td><?php echo esc_attr(get_post_meta($post->ID, 'TIMESTAMP', true)); ?></th>       
                            <td><?php echo get_woocommerce_currency_symbol() . esc_attr(get_post_meta($post->ID, 'AMT', true)); ?></td>
                            <?php $PENDINGREASON = esc_attr(get_post_meta($post->ID, 'PENDINGREASON', true)); ?>
                            <td <?php echo ($PENDINGREASON) ? sprintf('title="%s"', $PENDINGREASON) : ""; ?> ><?php echo esc_attr(get_post_meta($post->ID, 'PAYMENTSTATUS', true)); ?></td>
                            <td><?php echo esc_attr(get_post_meta($post->ID, 'payment_action', true)); ?> </td>
                        </tr>
                        <?php
                    endforeach;
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function angelleye_paypal_for_woocommerce_add_paypal_transaction($response, $order, $payment_action) {
        if ($payment_action == 'Authorization') {
            $payment_action = 'DoAuthorization';
        }
        $TRANSACTIONID = '';
        if (isset($response['PAYMENTINFO_0_TRANSACTIONID']) && !empty($response['PAYMENTINFO_0_TRANSACTIONID'])) {
            $TRANSACTIONID = $response['PAYMENTINFO_0_TRANSACTIONID'];
        } elseif (isset($response['TRANSACTIONID']) && !empty($response['TRANSACTIONID'])) {
            $TRANSACTIONID = $response['TRANSACTIONID'];
        } elseif (isset($response['AUTHORIZATIONID'])) {
            $TRANSACTIONID = $response['AUTHORIZATIONID'];
        }
        $insert_paypal_transaction = array(
            'ID' => '',
            'post_type' => 'paypal_transaction',
            'post_status' => $payment_action,
            'post_title' => $TRANSACTIONID,
            'post_parent' => $order->id
        );
        unset($response['ERRORS']);
        unset($response['REQUESTDATA']);
        unset($response['RAWREQUEST']);
        unset($response['RAWRESPONSE']);
        unset($response['PAYMENTS']);
        $post_id = wp_insert_post($insert_paypal_transaction);
        $response['order_id'] = $order->id;
        $response['payment_action'] = $payment_action;
        $response['_trans_date'] = current_time('mysql');
        update_post_meta($post_id, 'paypal_transaction', $response);
        foreach ($response as $metakey => $metavalue) {
            $metakey = str_replace('PAYMENTINFO_0_', '', $metakey);
            update_post_meta($post_id, $metakey, $metavalue);
        }
        if ($payment_action == 'DoVoid') {
            $post_id_value = self::get_post_id_by_meta_key_and_meta_value('TRANSACTIONID', $response['AUTHORIZATIONID']);
            $AMT = get_post_meta($post_id_value, 'AMT', true);
            update_post_meta($post_id, 'AMT', $AMT);
        }
    }

    public static function get_post_id_by_meta_key_and_meta_value($key, $value) {
        global $wpdb;
        $post_id_value = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE `meta_key` LIKE '%s' AND `meta_value` LIKE '%s'", $key, $value));
        return $post_id_value;
    }

    public function save($post_id, $post) {
        if (empty($this->payment_method)) {
            $this->payment_method = get_post_meta($post_id, '_payment_method', true);
        }
        $order = wc_get_order($post_id);
        if (!empty($_POST['angelleye_payment_action'])) {
            $action = wc_clean($_POST['angelleye_payment_action']);
            $hook_name = 'wc_' . $this->payment_method . '_' . strtolower($action);
            if (!did_action('woocommerce_order_action_' . sanitize_title($hook_name))) {
                do_action('woocommerce_order_action_' . sanitize_title($hook_name), $order);
            }
        }
    }

    public static function get_total($action, $status, $order_id) {
        global $wpdb;
        if ($action == 'DoVoid') {
            $total = $wpdb->get_var($wpdb->prepare("
			SELECT SUM( postmeta.meta_value )
			FROM $wpdb->postmeta AS postmeta
			INNER JOIN $wpdb->posts AS posts ON ( posts.post_type = 'paypal_transaction' AND posts.post_status LIKE '%s' AND post_parent = %d )
			WHERE postmeta.meta_key = 'AMT' 
			AND postmeta.post_id = posts.ID LIMIT 0, 99
		", $action, $order_id));
        } else {
            if ($action == 'DoCapture') {
                $total = $wpdb->get_var($wpdb->prepare("
                            SELECT SUM( postmeta.meta_value )
                            FROM $wpdb->postmeta AS postmeta
                            JOIN $wpdb->postmeta pm2 ON pm2.post_id = postmeta.post_id
                            INNER JOIN $wpdb->posts AS posts ON ( posts.post_type = 'paypal_transaction' AND posts.post_status LIKE '%s' AND post_parent = %d )
                            WHERE postmeta.meta_key = 'AMT' AND pm2.meta_key = 'PAYMENTSTATUS' AND (pm2.meta_value LIKE '%s' OR pm2.meta_value LIKE 'Pending')
                            AND postmeta.post_id = posts.ID LIMIT 0, 99
                    ", $action, $order_id, $status));
            } else {
                $total = $wpdb->get_var($wpdb->prepare("
                            SELECT SUM( postmeta.meta_value )
                            FROM $wpdb->postmeta AS postmeta
                            JOIN $wpdb->postmeta pm2 ON pm2.post_id = postmeta.post_id
                            INNER JOIN $wpdb->posts AS posts ON ( posts.post_type = 'paypal_transaction' AND posts.post_status LIKE '%s' AND post_parent = %d )
                            WHERE postmeta.meta_key = 'AMT' AND pm2.meta_key = 'PAYMENTSTATUS' AND pm2.meta_value LIKE '%s'
                            AND postmeta.post_id = posts.ID LIMIT 0, 99
                    ", $action, $order_id, $status));
            }
        }
        if ($total == NULL) {
            $total = 0;
        }
        return self::number_format($total);
    }

    public function angelleye_paypal_for_woocommerce_order_status_handler($order) {
        $this->angelleye_woocommerce_order_actions = $this->angelleye_woocommerce_order_actions();
        if (!is_object($order)) {
            $order = wc_get_order($order);
        }
        $_first_transaction_id = get_post_meta($order->id, '_first_transaction_id', true);
        if( empty($_first_transaction_id) ) {
            return false;
        }
        $this->angelleye_get_transactionDetails($_first_transaction_id);
        $_payment_action = get_post_meta($order->id, '_payment_action', true);
        if (isset($_payment_action) && !empty($_payment_action) && $_payment_action == 'Order') {
            if (($this->max_authorize_amount <= $this->total_DoVoid) || ($this->total_Pending_DoAuthorization == 0 && $this->total_Completed_DoAuthorization == 0 && $this->total_DoVoid == $order->order_total)) {
                $order->update_status('cancelled');
            }
            if ($order->get_total() - $order->get_total_refunded() <= $this->total_Completed_DoAuthorization && $this->total_Pending_DoAuthorization == 0) {
                do_action( 'woocommerce_order_status_pending_to_processing', $order->id );
                $order->payment_complete($_first_transaction_id);
                do_action('woocommerce_checkout_order_processed', $order->id, $posted = array());
                $order->reduce_order_stock();
            }
        }

        if ($order->order_total == $this->total_DoVoid) {
                $order->update_status('cancelled');
            }
            
       /* if (isset($_payment_action) && !empty($_payment_action) && $_payment_action == 'Authorization') {
            
            if ($order->order_total == $this->total_Completed_DoAuthorization && $this->total_Pending_DoAuthorization == 0) {
		do_action( 'woocommerce_order_status_pending_to_processing', $order->id );
                $order->payment_complete($_first_transaction_id);
                do_action('woocommerce_checkout_order_processed', $order->id, $posted = array());
                $order->reduce_order_stock();
            }
        } */
    }

    public function angelleye_express_checkout_transaction_capture_dropdownbox($post_id) {
        global $wpdb;
        $order = wc_get_order($post_id);
        wp_reset_postdata();
        $payment_action = get_post_meta($order->id, '_payment_action', true);
        if ($this->total_DoCapture == 0 && $this->total_Pending_DoAuthorization == 0) {
            if ('Order' == $payment_action) {
                $post_status = 'Order';
            } else {
                $post_status = 'DoAuthorization';
            }
        } else {
            $post_status = 'DoAuthorization';
        } 
        if ($this->total_Completed_DoAuthorization < $this->total_Order || $this->total_Pending_DoAuthorization > 0) {
            $posts = $wpdb->get_results($wpdb->prepare("SELECT $wpdb->posts.ID, $wpdb->posts.post_title FROM $wpdb->posts INNER JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id ) WHERE 1=1 AND $wpdb->posts.post_status LIKE '%s' AND $wpdb->posts.post_parent = %d AND ( ( $wpdb->postmeta.meta_key = 'PAYMENTSTATUS' AND CAST($wpdb->postmeta.meta_value AS CHAR) = 'Pending' ) ) AND $wpdb->posts.post_type = 'paypal_transaction' GROUP BY $wpdb->posts.ID ORDER BY $wpdb->posts.post_date DESC LIMIT 0, 99", $post_status, $order->id), ARRAY_A);
            if (empty($posts)) {
                return false;
            }
            ?>
            <select name="angelleye_paypal_capture_transaction_dropdown" id="angelleye_paypal_capture_transaction_dropdown" style="display: none"> 
                <?php
                $i = 0;
                foreach ($posts as $post):
                    if ($i == 0) {
                        echo '<option value="" >Select Transaction ID</option>';
                    }
                    ?>
                    <option value="<?php echo esc_attr($post['post_title']); ?>" ><?php echo esc_html($post['post_title']); ?></option>
                    <?php
                    $i = $i + 1;
                endforeach;
                ?>
            </select>  
            <?php
        }
        if (($this->total_Completed_DoAuthorization == $this->total_DoCapture && $this->total_DoCapture > 0) || $this->total_Pending_DoAuthorization >= 0) {
            ?>
            <select name="angelleye_paypal_dovoid_transaction_dropdown" id="angelleye_paypal_dovoid_transaction_dropdown" style="display: none"> 
                <?php
                $i = 0;
                if (empty($posts)) {
                    $posts = $wpdb->get_results($wpdb->prepare("SELECT $wpdb->posts.ID, $wpdb->posts.post_title FROM $wpdb->posts INNER JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id ) WHERE 1=1 AND $wpdb->posts.post_status LIKE '%s' AND $wpdb->posts.post_parent = %d AND ( ( $wpdb->postmeta.meta_key = 'PAYMENTSTATUS' AND CAST($wpdb->postmeta.meta_value AS CHAR) = 'Pending' ) ) AND $wpdb->posts.post_type = 'paypal_transaction' GROUP BY $wpdb->posts.ID ORDER BY $wpdb->posts.post_date DESC LIMIT 0, 99", $post_status, $order->id), ARRAY_A);
                }
                foreach ($posts as $post):
                    if ($i == 0) {
                        echo '<option value="" >Select Transaction ID</option>';
                    }
                    ?>
                    <option value="<?php echo esc_attr($post['post_title']); ?>" ><?php echo esc_html($post['post_title']); ?></option>
                    <?php
                    $i = $i + 1;
                endforeach;
                ?>
            </select>  
            <?php
        }
        if (($this->total_Completed_DoAuthorization == $this->total_DoCapture && $this->total_DoCapture > 0) || $this->total_Pending_DoAuthorization >= 0) {
            ?>
            <select name="angelleye_paypal_doreauthorization_transaction_dropdown" id="angelleye_paypal_doreauthorization_transaction_dropdown" style="display: none"> 
                <?php
                $i = 0;
                if (empty($posts)) {
                    $posts = $wpdb->get_results($wpdb->prepare("SELECT $wpdb->posts.ID, $wpdb->posts.post_title FROM $wpdb->posts INNER JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id ) WHERE 1=1 AND $wpdb->posts.post_status LIKE '%s' AND $wpdb->posts.post_parent = %d AND ( ( $wpdb->postmeta.meta_key = 'PAYMENTSTATUS' AND CAST($wpdb->postmeta.meta_value AS CHAR) = 'Pending' ) ) AND $wpdb->posts.post_type = 'paypal_transaction' GROUP BY $wpdb->posts.ID ORDER BY $wpdb->posts.post_date DESC LIMIT 0, 99", $post_status, $order->id), ARRAY_A);
                }
                foreach ($posts as $post):
                    if ($i == 0) {
                        echo '<option value="" >Select Transaction ID</option>';
                    }
                    ?>
                    <option value="<?php echo esc_attr($post['post_title']); ?>" ><?php echo esc_html($post['post_title']); ?></option>
                    <?php
                    $i = $i + 1;
                endforeach;
                ?>
            </select>  
            <?php
        }
    }

    public function angelleye_get_transactionDetails($transaction_id) {
        if( empty($this->payment_method) && $this->payment_method == false) {
            $this->angelleye_set_payment_method_using_transaction_id($transaction_id);
        }
        $this->add_ec_angelleye_paypal_php_library();
        $GTDFields = array(
            'transactionid' => $transaction_id
        );
        $PayPalRequestData = array('GTDFields' => $GTDFields);
        $get_transactionDetails_result = $this->paypal->GetTransactionDetails($PayPalRequestData);
        $this->angelleye_write_request_response_api_log($get_transactionDetails_result);
        $ack = strtoupper($get_transactionDetails_result["ACK"]);
        if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING") {
            $AUTHORIZATIONID = $this->get_post_by_title($transaction_id);
            if ($AUTHORIZATIONID != null) {
                update_post_meta($AUTHORIZATIONID, 'PAYMENTSTATUS', $get_transactionDetails_result['PAYMENTSTATUS']);
            }
        }
    }

    function get_post_by_title($page_title) {
        global $wpdb;
        $post = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type='paypal_transaction'", $page_title));
        if ($post) {
            return $post;
        }
        return null;
    }

    public function get_amount_by_transaction_id($transaction_id) {
        $meta_post_id = self::get_post_id_by_meta_key_and_meta_value('TRANSACTIONID', $transaction_id);
        $AMT = get_post_meta($meta_post_id, 'AMT', true);
        return self::number_format($AMT);
    }

    public function angelleye_max_authorize_amount($order_id) {
        if (!is_object($order_id)) {
            $order = wc_get_order($order_id);
        }
        $percentage = 115;
        if (isset($order->order_total) && !empty($order->order_total)) {
            $new_percentage_amount = self::round(($percentage / 100) * $order->order_total);
        }
        $diff_percentage_amount = self::round($new_percentage_amount - $order->order_total);
        if ($diff_percentage_amount > 75) {
            $max_authorize_amount = self::round($order->order_total + 75);
        } else {
            $max_authorize_amount = $new_percentage_amount;
        }
        $this->max_authorize_amount = self::number_format($max_authorize_amount);
    }

    public function angelleye_remain_authorize_amount() {
        $this->remain_authorize_amount = self::number_format($this->max_authorize_amount - ( self::round($this->total_Pending_DoAuthorization + $this->total_Completed_DoAuthorization)));
    }

    public static function currency_has_decimals($currency) {
        if (in_array($currency, array('HUF', 'JPY', 'TWD'))) {
            return false;
        }
        return true;
    }

    public static function round($price) {
        $precision = 2;
        if (!self::currency_has_decimals(get_woocommerce_currency())) {
            $precision = 0;
        }
        return round($price, $precision);
    }

    /**
     * @since    1.1.8.1
     * Non-decimal currency bug..?? #384 
     * Round prices
     * @param type $price
     * @return type
     */
    public static function number_format($price) {
        $decimals = 2;
        if (!self::currency_has_decimals(get_woocommerce_currency())) {
            $decimals = 0;
        }
        return number_format($price, $decimals, '.', '');
    }

    public function angelleye_is_display_paypal_transaction_details($post_id) {
        $_payment_method = get_post_meta($post_id, '_payment_method', true);
        $_payment_action = get_post_meta($post_id, '_payment_action', true);

        if (isset($_payment_method) && !empty($_payment_method) && isset($_payment_action) && !empty($_payment_action)) {
            if (($_payment_method == 'paypal_pro' || $_payment_method == 'paypal_express') && $_payment_method != "Sale") {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    public static function is_valid_for_use_paypal_express() {
	return in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_paypal_express_supported_currencies', array( 'AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP' ) ) );
    }
    
    public function angelleye_set_payment_method() {
        if( empty($this->payment_method) || $this->payment_method == false) {
            global $post;
            $order_id = $post->ID;
            $this->payment_method = get_post_meta($order_id, '_payment_method', true);
        }
    }
    
    public function angelleye_set_payment_method_using_transaction_id($transaction) {
        if( empty($this->payment_method) || $this->payment_method == false) {
            global $wpdb;
            $results = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_value = %s ORDER BY meta_id", $transaction ));
            if( !empty($results[0]->post_id) ) {
                $this->payment_method = get_post_meta($results[0]->post_id, '_payment_method', true);
            }
        }
    }
    
    public static function crypting( $string, $action = 'e' ) {
        $secret_key = AUTH_SALT;
        $secret_iv = SECURE_AUTH_SALT;
        $output = false;
        $encrypt_method = "AES-256-CBC";
        $key = hash( 'sha256', $secret_key );
        $iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );
        if( $action == 'e' ) {
            $output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
        }
        else if( $action == 'd' ){
            $output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
        }
        return $output;
    }
}
