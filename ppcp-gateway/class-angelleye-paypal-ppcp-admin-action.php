<?php
defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Admin_Action {

    private $angelleye_ppcp_plugin_name;
    public $api_log;
    public $payment_request;
    public $payment_response;
    public $ae_capture_amount = 0;
    public $ae_refund_amount = 0;
    public $ae_auth_amount = 0;
    public $order;
    public $currency_code;
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
        if (is_admin() && !defined('DOING_AJAX')) {
            add_action('add_meta_boxes', array($this, 'angelleye_ppcp_order_action_meta_box'), 10, 2);
        }
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
            if ('CREATED' === $payment_status || 'PARTIALLY_CAPTURED' === $payment_status) {
                return true;
            }
        }
        return false;
    }

    public function angelleye_ppcp_order_action_meta_box($post_type, $post) {
        try {
            if (isset($post->ID) && !empty($post->ID) && $post_type == 'shop_order') {
                if ($this->angelleye_ppcp_is_display_paypal_transaction_details($post->ID)) {
                    add_meta_box('angelleye-ppcp-order-action', __('PayPal Transaction Activity', 'paypal-for-woocommerce'), array($this, 'angelleye_ppcp_order_action_callback'), 'shop_order', 'normal', 'high', null);
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_is_display_paypal_transaction_details($post_id) {
        try {
            $order = wc_get_order($post_id);
            if (empty($order)) {
                return false;
            }
            $old_wc = version_compare(WC_VERSION, '3.0', '<');
            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            $payment_method = $old_wc ? $order->payment_method : $order->get_payment_method();
            $payment_action = $old_wc ? get_post_meta($order_id, '_payment_action', true) : get_post_meta($order->get_id(), '_payment_action', true);
            if (isset($payment_method) && !empty($payment_method) && isset($payment_action) && !empty($payment_action)) {
                if (($payment_method == 'angelleye_ppcp_cc' || $payment_method == 'angelleye_ppcp') && ($payment_action === "authorize" && $order->get_total() > 0)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_order_action_callback() {
        try {
            global $theorder;
            $order = $theorder;
            $this->order = $order;
            $this->ae_capture_amount = 0;
            $this->ae_refund_amount = 0;
            $this->ae_auth_amount = 0;
            $html_table_row = array();
            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            $paypal_order_id = angelleye_ppcp_get_post_meta($order_id, '_paypal_order_id');
            $this->payment_response = $this->payment_request->angelleye_ppcp_get_paypal_order_details($paypal_order_id);
            if (isset($this->payment_response) && !empty($this->payment_response) && $this->payment_response['intent'] === 'AUTHORIZE') {
                if (isset($this->payment_response['purchase_units']['0']['payments']['authorizations']) && !empty($this->payment_response['purchase_units']['0']['payments']['authorizations'])) {
                    krsort($this->payment_response['purchase_units']['0']['payments']['captures']);
                    foreach ($this->payment_response['purchase_units']['0']['payments']['captures'] as $key => $captures) {
                        $this->currency_code = $captures['amount']['currency_code'];
                        $line_item = array();
                        $line_item['transaction_id'] = isset($captures['id']) ? $captures['id'] : 'N/A';
                        $line_item['amount'] = isset($captures['amount']['value']) ? wc_price($captures['amount']['value'], array('currency' => $captures['amount']['currency_code'])) : 'N/A';
                        $line_item['payment_status'] = isset($captures['status']) ? ucwords(str_replace('_', ' ', strtolower($captures['status']))) : 'N/A';
                        $line_item['expired_date'] = isset($captures['expiration_time']) ? $captures['expiration_time'] : 'N/A';
                        $line_item['payment_action'] = __('Capture', '');
                        if (isset($captures['status']) && $captures['status'] === 'COMPLETED') {
                            $this->ae_capture_amount = $this->ae_capture_amount + $captures['amount']['value'];
                        } elseif (isset($captures['status']) && $captures['status'] === 'REFUNDED') {
                            $this->ae_refund_amount = $this->ae_refund_amount + $captures['amount']['value'];
                        }
                        $html_table_row[] = $line_item;
                    }
                    foreach ($this->payment_response['purchase_units']['0']['payments']['authorizations'] as $key => $authorizations) {
                        $this->currency_code = $authorizations['amount']['currency_code'];
                        $line_item = array();
                        $line_item['transaction_id'] = isset($authorizations['id']) ? $authorizations['id'] : 'N/A';
                        $line_item['amount'] = isset($authorizations['amount']['value']) ? wc_price($authorizations['amount']['value'], array('currency' => $authorizations['amount']['currency_code'])) : 'N/A';
                        $line_item['payment_status'] = isset($authorizations['status']) ? ucwords(str_replace('_', ' ', strtolower($authorizations['status']))) : 'N/A';
                        $line_item['expired_date'] = isset($authorizations['expiration_time']) ? $authorizations['expiration_time'] : 'N/A';
                        $line_item['payment_action'] = isset($this->payment_response['intent']) ? ucwords(str_replace('_', ' ', strtolower($this->payment_response['intent']))) : 'N/A';
                        $html_table_row[] = $line_item;
                        $this->ae_auth_amount = $this->ae_auth_amount + $authorizations['amount']['value'];
                    }
                    //if($order->get_total() <= $auth && )
                    $this->angelleye_ppcp_display_payment_action();
                    $this->angelleye_ppcp_display_paypal_activity_table($html_table_row);
                }
            } elseif (isset($this->payment_response) && $this->payment_response['name'] === 'RESOURCE_NOT_FOUND') {
                $auth_transaction_id = angelleye_ppcp_get_post_meta($order, '_auth_transaction_id');
                $trans_details = $this->payment_request->angelleye_ppcp_get_authorized_payment($auth_transaction_id);
                $this->currency_code = $trans_details['amount']['currency_code'];
                $line_item = array();
                $line_item['transaction_id'] = isset($trans_details['id']) ? $trans_details['id'] : 'N/A';
                $line_item['amount'] = isset($trans_details['amount']['value']) ? wc_price($trans_details['amount']['value'], array('currency' => $trans_details['amount']['currency_code'])) : 'N/A';
                $line_item['payment_status'] = isset($trans_details['status']) ? ucwords(str_replace('_', ' ', strtolower($trans_details['status']))) : 'N/A';
                $line_item['expired_date'] = isset($trans_details['expiration_time']) ? $trans_details['expiration_time'] : 'N/A';
                $line_item['payment_action'] = __('Authorize', '');
                if (isset($trans_details['status']) && $trans_details['status'] === 'COMPLETED') {
                    $capture = $capture + $trans_details['amount']['value'];
                } elseif (isset($trans_details['status']) && $trans_details['status'] === 'REFUNDED') {
                    $refund = $refund + $trans_details['amount']['value'];
                }
                $html_table_row[] = $line_item;
                $this->angelleye_ppcp_display_paypal_activity_table($html_table_row);
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_display_payment_action() {
        ?>
        <table class="widefat angelleye_order_action_table" style="width: 190px;float: right;">
            <tbody>
                <tr>
                    <td><?php echo __('Order Total:', 'paypal-for-woocommerce'); ?></td>
                    <td><?php echo $this->order->get_formatted_order_total(); ?></td>
                </tr>
                <tr>
                    <td><?php echo __('Total Capture:', 'paypal-for-woocommerce'); ?></td>
                    <td><?php echo wc_price($this->ae_capture_amount, array('currency' => $this->currency_code)); ?></td>
                </tr>
            </tbody>
        </table>
        <br/><br/>
        <br/><br/>
        <br/><br/>
        <?php
    }

    public function angelleye_ppcp_display_paypal_activity_table($table_rows) {
        try {
            ?>
            <table class="widefat angelleye_order_action_table">
                <thead>
                    <tr>
                        <th><?php echo __('Transaction ID', 'paypal-for-woocommerce'); ?></th>
                        <th><?php echo __('Amount', 'paypal-for-woocommerce'); ?></th>
                        <th><?php echo __('Payment Status', 'paypal-for-woocommerce'); ?></th>
                        <th><?php echo __('Expired Date', 'paypal-for-woocommerce'); ?></th>
                        <th><?php echo __('Payment Action', 'paypal-for-woocommerce'); ?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th><?php echo __('Transaction ID', 'paypal-for-woocommerce'); ?></th>
                        <th><?php echo __('Amount', 'paypal-for-woocommerce'); ?></th>
                        <th><?php echo __('Payment Status', 'paypal-for-woocommerce'); ?></th>
                        <th><?php echo __('Expired Date', 'paypal-for-woocommerce'); ?></th>
                        <th><?php echo __('Payment Action', 'paypal-for-woocommerce'); ?></th>
                    </tr>
                </tfoot>
                <tbody>
                    <?php
                    foreach ($table_rows as $key => $table_field) {
                        echo '<tr>';
                        echo '<td>' . $table_field['transaction_id'] . '</td>';
                        echo '<td>' . $table_field['amount'] . '</td>';
                        echo '<td>' . $table_field['payment_status'] . '</td>';
                        echo '<td>' . $table_field['expired_date'] . '</td>';
                        echo '<td>' . $table_field['payment_action'] . '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
            <?php
        } catch (Exception $ex) {
            
        }
    }

}
