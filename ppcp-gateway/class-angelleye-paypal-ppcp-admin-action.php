<?php

defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Admin_Action {

    private $angelleye_ppcp_plugin_name;
    public $api_log;
    public $payment_request;
    protected static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->angelleye_ppcp_plugin_name = 'angelleye_ppcp';
        $this->angelleye_ppcp_load_class();
        $this->angelleye_ppcp_add_hooks();
    }

    public function angelleye_ppcp_load_class() {
        try {
            if (!class_exists('AngellEYE_PayPal_PPCP_Log')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-log.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Payment')) {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-payment.php');
            }
            $this->api_log = AngellEYE_PayPal_PPCP_Log::instance();
            $this->payment_request = AngellEYE_PayPal_PPCP_Payment::instance();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_add_hooks() {
        add_action('woocommerce_order_status_processing', array($this, 'angelleye_ppcp_capture_payment'));
        add_action('woocommerce_order_status_completed', array($this, 'angelleye_ppcp_capture_payment'));
        add_action('woocommerce_order_status_cancelled', array($this, 'angelleye_ppcp_cancel_authorization'));
        add_action('woocommerce_order_status_refunded', array($this, 'angelleye_ppcp_cancel_authorization'));
        add_filter('woocommerce_order_actions', array($this, 'angelleye_ppcp_add_capture_charge_order_action'));
        add_action('woocommerce_order_action_angelleye_ppcp_capture_charge', array($this, 'angelleye_ppcp_maybe_capture_charge'));
    }

    public function angelleye_ppcp_capture_payment($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        $payment_method = $old_wc ? $order->payment_method : $order->get_payment_method();
        $payment_action = angelleye_ppcp_get_post_meta($order, '_payment_action');
        $auth_transaction_id = angelleye_ppcp_get_post_meta($order, '_auth_transaction_id');
        if ('angelleye_ppcp' === $payment_method && $payment_action === 'authorize' && !empty($auth_transaction_id)) {
            $trans_details = $this->payment_request->angelleye_ppcp_show_details_authorized_payment($auth_transaction_id);
            if ($this->angelleye_ppcp_is_authorized_only($trans_details)) {
                $this->payment_request->angelleye_ppcp_capture_authorized_payment($order_id);
            }
        }
    }

    public function angelleye_ppcp_cancel_authorization($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        $payment_method = $old_wc ? $order->payment_method : $order->get_payment_method();
        $transaction_id = $order->get_transaction_id();
        $payment_action = angelleye_ppcp_get_post_meta($order, '_payment_action');
        if ('angelleye_ppcp' === $payment_method && $transaction_id && $payment_action === 'authorize') {
            $trans_details = $this->payment_request->angelleye_ppcp_show_details_authorized_payment($transaction_id);
            if ($this->angelleye_ppcp_is_authorized_only($trans_details)) {
                $this->payment_request->angelleye_ppcp_void_authorized_payment($transaction_id);
            }
        }
    }

    public function angelleye_ppcp_add_capture_charge_order_action($actions) {
        if (!isset($_REQUEST['post'])) {
            return $actions;
        }
        $order = wc_get_order($_REQUEST['post']);
        if (empty($order)) {
            return $actions;
        }
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        $payment_method = $old_wc ? $order->payment_method : $order->get_payment_method();
        $paypal_status = angelleye_ppcp_get_post_meta($order, '_payment_status');
        $payment_action = angelleye_ppcp_get_post_meta($order, '_payment_action');
        if ('angelleye_ppcp' !== $payment_method) {
            return $actions;
        }
        if (!is_array($actions)) {
            $actions = array();
        }
        if ('CREATED' == $paypal_status && $payment_action === 'authorize') {
            $actions['angelleye_ppcp_capture_charge'] = esc_html__('Capture Charge', 'paypal-for-woocommerce');
        }
        return $actions;
    }

    public function angelleye_ppcp_maybe_capture_charge($order) {
        if (!is_object($order)) {
            $order = wc_get_order($order);
        }
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        $this->angelleye_ppcp_capture_payment($order_id);
        return true;
    }

    public function angelleye_ppcp_is_authorized_only($trans_details = array()) {
        if (!is_wp_error($trans_details) && !empty($trans_details)) {
            $payment_status = '';
            if (isset($trans_details->status) && !empty($trans_details->status)) {
                $payment_status = $trans_details->status;
            }
            if ('CREATED' === $payment_status) {
                return true;
            }
        }
        return false;
    }

}
