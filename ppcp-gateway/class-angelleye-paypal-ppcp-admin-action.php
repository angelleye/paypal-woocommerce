<?php
defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Admin_Action {
    private $angelleye_ppcp_plugin_name;
    public $api_log;
    public ?AngellEYE_PayPal_PPCP_Payment $payment_request;
    public $payment_response;
    public $ae_capture_amount = 0;
    public $ae_refund_amount = 0;
    public $ae_auth_amount = 0;
    public $order;
    public $currency_code;
    public $ae_void_amount = 0;
    public $angelleye_ppcp_order_status_data = array();
    public $angelleye_ppcp_order_actions = array();
    protected static $_instance = null;
    public $setting_obj;
    public $is_auto_capture_auth;
    public ?AngellEYE_PayPal_PPCP_Seller_Onboarding $seller_onboarding;
    public $is_sandbox;
    public $merchant_id;

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
            if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-ppcp-angelleye-settings.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Seller_Onboarding')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-seller-onboarding.php';
            }
            $this->seller_onboarding = AngellEYE_PayPal_PPCP_Seller_Onboarding::instance();
            $this->api_log = AngellEYE_PayPal_PPCP_Log::instance();
            $this->payment_request = AngellEYE_PayPal_PPCP_Payment::instance();
            $this->setting_obj = WC_Gateway_PPCP_AngellEYE_Settings::instance();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' .$ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_add_hooks() {
        $this->is_auto_capture_auth = 'yes' === $this->setting_obj->get('auto_capture_auth', 'yes');
        $this->is_sandbox = 'yes' === $this->setting_obj->get('testmode', 'no');
        if ($this->is_sandbox) {
            $this->merchant_id = $this->setting_obj->get('sandbox_merchant_id', '');
        } else {
            $this->merchant_id = $this->setting_obj->get('live_merchant_id', '');
        }
        add_action('admin_notices', array($this, 'admin_notices'));
        if ($this->is_auto_capture_auth) {
            add_action('woocommerce_order_status_processing', array($this, 'angelleye_ppcp_capture_payment'));
            add_action('woocommerce_order_status_completed', array($this, 'angelleye_ppcp_capture_payment'));
            add_action('woocommerce_order_status_cancelled', array($this, 'angelleye_ppcp_cancel_authorization'));
            add_action('woocommerce_order_status_refunded', array($this, 'angelleye_ppcp_cancel_authorization'));
        }
        if (is_admin() && !defined('DOING_AJAX')) {
            add_action('add_meta_boxes', array($this, 'angelleye_ppcp_order_action_meta_box'), 0, 2);
            if (isset($_POST['is_ppcp_submited']) && 'yes' === $_POST['is_ppcp_submited']) {
                add_action('woocommerce_order_action_angelleye_ppcp_void_admin', array($this, 'angelleye_ppcp_admin_void_action_handler'), 10, 2);
                add_action('woocommerce_order_action_angelleye_ppcp_capture_admin', array($this, 'angelleye_ppcp_admin_capture_action_handler'), 10, 2);
                add_action('woocommerce_order_action_angelleye_ppcp_refund_admin', array($this, 'angelleye_ppcp_admin_refund_action_handler'), 10, 2);
            }
        }
        add_action('woocommerce_process_shop_order_meta', array($this, 'angelleye_ppcp_save'), 50, 2);
    }

    public function angelleye_ppcp_admin_void_action_handler($order, $order_data) {
        try {
            remove_action('woocommerce_order_action_angelleye_ppcp_void', array($this, 'angelleye_ppcp_admin_void_action_handler'));
            remove_action('woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Data::save', 40, 2);
            remove_action('woocommerce_order_status_cancelled', array($this, 'angelleye_ppcp_cancel_authorization'));
            remove_action('woocommerce_order_status_refunded', array($this, 'angelleye_ppcp_cancel_authorization'));
            $this->payment_request->angelleye_ppcp_void_authorized_payment_admin($order, $order_data);
        } catch (Exception $ex) {

        }
    }

    public function angelleye_ppcp_admin_capture_action_handler($order, $order_data) {
        try {
            remove_action('woocommerce_order_action_angelleye_ppcp_capture', array($this, 'angelleye_ppcp_admin_capture_action_handler'));
            remove_action('woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Data::save', 40, 2);
            remove_action('woocommerce_order_status_processing', array($this, 'angelleye_ppcp_capture_payment'));
            remove_action('woocommerce_order_status_completed', array($this, 'angelleye_ppcp_capture_payment'));
            $this->payment_request->angelleye_ppcp_capture_authorized_payment_admin($order, $order_data);
        } catch (Exception $ex) {

        }
    }

    public function angelleye_ppcp_admin_refund_action_handler($order, $order_data) {
        try {
            remove_action('woocommerce_order_action_angelleye_ppcp_refund', array($this, 'angelleye_ppcp_admin_refund_action_handler'));
            remove_action('woocommerce_order_status_processing', array($this, 'angelleye_ppcp_capture_payment'));
            remove_action('woocommerce_order_status_completed', array($this, 'angelleye_ppcp_capture_payment'));
            remove_action('woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Data::save', 40, 2);
            $this->payment_request->angelleye_ppcp_refund_order_admin($order, $order_data);
        } catch (Exception $ex) {

        }
    }

    public function angelleye_ppcp_capture_payment($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        $payment_method = $order->get_payment_method();
        $paymentaction = angelleye_ppcp_get_post_meta($order, '_paymentaction');
        $auth_transaction_id = angelleye_ppcp_get_post_meta($order, '_auth_transaction_id');
        $auto_capture_payment_support_gateways = ['angelleye_ppcp', 'angelleye_ppcp_google_pay', 'angelleye_ppcp_apple_pay'];
        if (in_array($payment_method, $auto_capture_payment_support_gateways) && $paymentaction === 'authorize' && !empty($auth_transaction_id)) {
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
        $payment_method = $order->get_payment_method();
        $transaction_id = $order->get_transaction_id();
        $paymentaction = angelleye_ppcp_get_post_meta($order, '_paymentaction');
        if ('angelleye_ppcp' === $payment_method && $transaction_id && $paymentaction === 'authorize') {
            $trans_details = $this->payment_request->angelleye_ppcp_show_details_authorized_payment($transaction_id);
            if ($this->angelleye_ppcp_is_authorized_only($trans_details)) {
                $this->payment_request->angelleye_ppcp_void_authorized_payment($transaction_id);
            }
        }
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

    public function angelleye_ppcp_order_action_meta_box($post_type, $post_or_order_object) {
        try {
            $order = ( $post_or_order_object instanceof WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
            if (!is_a($order, 'WC_Order')) {
                return;
            }
            $screen = ae_is_active_screen(AE_SHOP_ORDER_SCREENS);
            if ($screen && $this->angelleye_ppcp_is_display_paypal_transaction_details($order->get_id())) {
                add_meta_box('angelleye-ppcp-order-action', __('PayPal Transaction Activity', 'paypal-for-woocommerce'), array($this, 'angelleye_ppcp_order_action_callback'), $screen, 'normal', 'high');
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
            $payment_method = $order->get_payment_method();
            $payment_action = angelleye_ppcp_get_post_meta($order, '_payment_action', true);
            if (isset($payment_method) && !empty($payment_method) && isset($payment_action) && !empty($payment_action)) {
                if (in_array($payment_method, ['angelleye_ppcp_cc', 'angelleye_ppcp', 'angelleye_ppcp_apple_pay', 'angelleye_ppcp_google_pay']) && ($payment_action === "authorize" && $order->get_total() > 0)) {
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

    public function angelleye_ppcp_order_action_callback($post, $metabox) {
        try {
            $order = ( $post instanceof WP_Post ) ? wc_get_order( $post->ID ) : $post;
            if (!is_a($order, 'WC_Order')) {
                echo __('Error: Unable to detect the order, please refresh again to retry or Contact PayPal For WooCommerce support.', 'paypal-for-woocommerce');
                return;
            }
            $this->order = $order;
            $this->ae_capture_amount = 0;
            $this->ae_refund_amount = 0;
            $this->ae_auth_amount = 0;
            $html_table_row = array();
            $paypal_order_id = angelleye_ppcp_get_post_meta($order, '_paypal_order_id');
            if (empty($paypal_order_id)) {
                echo __('PayPal order id does not exist for this order.', 'paypal-for-woocommerce');
                return;
            }
            $this->payment_response = $this->payment_request->angelleye_ppcp_get_paypal_order_details($paypal_order_id);
            if (isset($this->payment_response) && !empty($this->payment_response) && isset($this->payment_response['intent']) && $this->payment_response['intent'] === 'AUTHORIZE') {
                if (isset($this->payment_response['purchase_units']['0']['payments']['authorizations']) && !empty($this->payment_response['purchase_units']['0']['payments']['authorizations'])) {
                    if (isset($this->payment_response['purchase_units']['0']['payments']['refunds'])) {
                        foreach ($this->payment_response['purchase_units']['0']['payments']['refunds'] as $key => $refunds) {
                            $this->currency_code = $refunds['amount']['currency_code'];
                            $line_item = array();
                            $line_item['transaction_id'] = isset($refunds['id']) ? $refunds['id'] : 'N/A';
                            $line_item['amount'] = isset($refunds['amount']['value']) ? wc_price($refunds['amount']['value'], array('currency' => $refunds['amount']['currency_code'])) : 'N/A';
                            $line_item['payment_status'] = isset($refunds['status']) ? ucwords(str_replace('_', ' ', strtolower($refunds['status']))) : 'N/A';
                            $line_item['expired_date'] = isset($refunds['expiration_time']) ? $refunds['expiration_time'] : 'N/A';
                            $line_item['payment_action'] = __('Refund', '');
                            $this->ae_refund_amount = $this->ae_refund_amount + $refunds['amount']['value'];
                            $html_table_row[] = $line_item;
                        }
                    }
                    if (isset($this->payment_response['purchase_units']['0']['payments']['captures'])) {
                        foreach ($this->payment_response['purchase_units']['0']['payments']['captures'] as $key => $captures) {
                            $this->currency_code = $captures['amount']['currency_code'];
                            $line_item = array();
                            $line_item['transaction_id'] = isset($captures['id']) ? $captures['id'] : 'N/A';
                            $line_item['amount'] = isset($captures['amount']['value']) ? wc_price($captures['amount']['value'], array('currency' => $captures['amount']['currency_code'])) : 'N/A';
                            $line_item['payment_status'] = isset($captures['status']) ? ucwords(str_replace('_', ' ', strtolower($captures['status']))) : 'N/A';
                            $line_item['expired_date'] = isset($captures['expiration_time']) ? $captures['expiration_time'] : 'N/A';
                            $line_item['payment_action'] = __('Capture', '');
                            if ('COMPLETED' === $captures['status'] || 'PARTIALLY_REFUNDED' === $captures['status']) {
                                $this->angelleye_ppcp_order_status_data['refund'][$line_item['transaction_id']] = $captures['amount']['value'];
                            }
                            $this->ae_capture_amount = $this->ae_capture_amount + $captures['amount']['value'];
                            $html_table_row[] = $line_item;
                        }
                    }
                    if (isset($this->payment_response['purchase_units']['0']['payments']['authorizations'])) {
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
                            $this->angelleye_ppcp_order_status_data['capture'][$line_item['transaction_id']] = $authorizations['amount']['value'];
                            $this->angelleye_ppcp_order_status_data['void'][$line_item['transaction_id']] = $authorizations['amount']['value'];
                        }
                    }
                    if (isset($this->payment_response['purchase_units']['0']['payments']['authorizations']['0']['status']) && 'CREATED' === $this->payment_response['purchase_units']['0']['payments']['authorizations']['0']['status']) {
                        $this->angelleye_ppcp_order_actions['void'] = __('Void Authorization', '');
                        $this->angelleye_ppcp_order_actions['capture'] = __('Capture Funds', '');
                    }
                    if (isset($this->payment_response['purchase_units']['0']['payments']['authorizations']['0']['status']) && 'PARTIALLY_CAPTURED' === $this->payment_response['purchase_units']['0']['payments']['authorizations']['0']['status']) {
                        if ($this->ae_refund_amount < $this->ae_capture_amount) {
                            $this->angelleye_ppcp_order_actions['refund'] = __('Refund', '');
                        }
                        $this->angelleye_ppcp_order_actions['capture'] = __('Capture Funds', '');
                    }
                    if (isset($this->payment_response['purchase_units']['0']['payments']['authorizations']['0']['status']) && 'CAPTURED' === $this->payment_response['purchase_units']['0']['payments']['authorizations']['0']['status']) {
                        if ($this->ae_refund_amount < $this->ae_capture_amount) {
                            $this->angelleye_ppcp_order_actions['refund'] = __('Refund', '');
                        }
                    }
                    if (isset($this->payment_response['purchase_units']['0']['payments']['authorizations']['0']['status']) && 'VOIDED' === $this->payment_response['purchase_units']['0']['payments']['authorizations']['0']['status']) {
                        unset($this->angelleye_ppcp_order_actions);
                    }
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
                $html_table_row[] = $line_item;
                $this->angelleye_ppcp_display_paypal_activity_table($html_table_row);
            }
        } catch (Exception $ex) {

        }
    }

    public function angelleye_ppcp_display_payment_action() {
        ?>
        <div class='wrap'>
            <?php if (isset($this->angelleye_ppcp_order_actions) && !empty($this->angelleye_ppcp_order_actions)) { ?>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row" class="titledesc">
                                <label for="angelleye_ppcp_payment_action"><?php echo __('Select PayPal Action', 'paypal-for-woocommerce'); ?></label>
                            </th>
                            <td class="forminp forminp-text">
                                <?php if (!empty($this->angelleye_ppcp_order_actions)) { ?>
                                    <select name="angelleye_ppcp_payment_action" id="angelleye_ppcp_payment_action">
                                        <?php
                                        $i = 0;
                                        foreach ($this->angelleye_ppcp_order_actions as $k => $v) :
                                            if ($i == 0) {
                                                echo "<option value=''>" . __('Select Action', 'paypal-for-woocommerce') . "</option>";
                                            }
                                            ?>
                                            <option value="<?php echo esc_attr($k); ?>" ><?php echo esc_html($v); ?></option>
                                            <?php
                                            $i = $i + 1;
                                        endforeach;
                                        ?>
                                    </select>
                                <?php } ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            <?php } ?>
            <?php if (isset($this->angelleye_ppcp_order_status_data['capture']) && isset($this->angelleye_ppcp_order_actions['capture'])) { ?>
                <p class="angelleye_ppcp_capture_box" style="display: none;"><b style="font-size: 14px;"><?php echo __('Enter the capture details below to move funds from your buyer\'s account to your account.', 'paypal-for-woocommerce'); ?></b></p>
                <table class="form-table angelleye_ppcp_capture_box" style="display: none;">
                    <tbody>
                        <tr>
                            <th scope="row"><?php echo __('Additional Capture Possible', 'paypal-for-woocommerce'); ?></th>
                            <td>
                                <fieldset>
                                    <label for="additional_capture_yes"><input type="radio" name="additionalCapture" value="yes" id="additional_capture_yes"><span><?php echo __('Yes (option to capture additional funds on this authorization if need)', 'paypal-for-woocommerce'); ?></span></label>
                                    <label for="additional_capture_no"><input type="radio" name="additionalCapture" value="no" id="additional_capture_no"><span><?php echo __('No (no additional capture needed; close authorization after this capture)', 'paypal-for-woocommerce'); ?></span></label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo __('Capture Amount', 'paypal-for-woocommerce'); ?></th>
                            <td>
                                <fieldset>
                                    <input type="text" placeholder="Enter amount" id="_regular_price" name="_angelleye_ppcp_regular_price" class="short wc_input_price text-box" style="width: 220px">
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th><?php echo __('Note To Buyer (Optional)', 'paypal-for-woocommerce'); ?><span class="woocommerce-help-tip" data-tip="<?php echo __('PayPal strongly recommends that you explain any unique circumstances (e.g. multiple captures, changes in item availability) to your buyer in detail below. Your buyer will see this note in the Transaction Details.', 'paypal-for-woocommerce'); ?>"></span></th>
                            <td>
                                <textarea maxlength="150" rows="4" cols="50" class="wide-input" type="textarea" name="angelleye_ppcp_note_to_buyer_capture" id="angelleye_ppcp_note_to_buyer_capture"></textarea>
                            </td>
                        </tr>
                    </tbody>
                </table>
            <?php } ?>
            <?php if (isset($this->angelleye_ppcp_order_status_data['refund']) && isset($this->angelleye_ppcp_order_actions['refund'])) { ?>
                <p class="angelleye_ppcp_refund_box" style="display: none;"><b style="font-size: 14px;"><?php echo __('You can issue a full or partial refund for 180 days after the original payment was sent.', 'paypal-for-woocommerce'); ?></b></p>
                <table class="form-table angelleye_ppcp_refund_box" style="display: none;">
                    <tbody>
                        <tr>
                            <th scope="row"><?php echo __('Transaction Id', ''); ?></th>
                            <td>
                                <select name="angelleye_ppcp_refund_data" id="angelleye_ppcp_refund_data">
                                    <?php
                                    $i = 0;
                                    foreach ($this->angelleye_ppcp_order_status_data['refund'] as $k => $v) :
                                        if ($i == 0) {
                                            echo "<option value=''>" . __('Select Transaction Id', '') . "</option>";
                                        }
                                        ?>
                                        <option value="<?php echo esc_attr($k); ?>" ><?php echo esc_html($k); ?></option>
                                        <?php
                                        $i = $i + 1;
                                    endforeach;
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo __('Refund Amount', 'paypal-for-woocommerce'); ?></th>
                            <td>
                                <fieldset>
                                    <input type="text" placeholder="Enter amount" id="_regular_price" name="_angelleye_ppcp_refund_price" class="short wc_input_price text-box" style="width: 220px">
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th><?php echo __('Note To Buyer (Optional)', 'paypal-for-woocommerce'); ?> <span class="woocommerce-help-tip" data-tip="<?php echo __('PayPal strongly recommends that you explain any unique circumstances (e.g. multiple captures, changes in item availability) to your buyer in detail below. Your buyer will see this note in the Transaction Details.', 'paypal-for-woocommerce'); ?>"></span></th>
                            <td>
                                <textarea maxlength="150" rows="4" cols="50" class="wide-input" type="textarea" name="angelleye_ppcp_note_to_buyer_capture" id="angelleye_ppcp_note_to_buyer_capture"></textarea>
                            </td>
                        </tr>
                    </tbody>
                </table><?php
            }
        ?></div>

        <?php if (isset($this->angelleye_ppcp_order_status_data['void']) && isset($this->angelleye_ppcp_order_actions['void'])) { ?>

            <p style="font-size: 14px;" class="angelleye_ppcp_void_box" style="display: none;">
                <b>
                    By initiating this void, you are canceling this authorization and will be unable to capture any funds remaining on the authorization.<br><br>
                    Note: You will not be able to submit a partial void. Any submitted voids will void the entire open authorization amount.<br><br>
                </b>
            </p>
            <table class="form-table angelleye_ppcp_void_box" style="display: none;">
                <tbody>
                    <tr>
                        <th>Note To Buyer (Optional)<span class="woocommerce-help-tip" data-tip="PayPal strongly recommends that you explain any unique circumstances (e.g. multiple captures, changes in item availability) to your buyer in detail below. Your buyer will see this note in the Transaction Details."></span></th>
                        <td>
                            <textarea maxlength="150" rows="4" cols="50" class="wide-input" type="textarea" name="angelleye_ppcp_note_to_buyer_void" id="angelleye_ppcp_note_to_buyer_void"></textarea>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php
        }
        ?>
        <input type="hidden" value="no" name="is_ppcp_submited" id="is_ppcp_submited">
        <input type="submit" id="angelleye_ppcp_payment_submit_button" value="Submit" name="save" class="button button-primary" style="display: none">

        <table class="widefat  angelleye_ppcp_order_action_table" style="width: 190px;float: right;margin-bottom: 20px;border: none;">
            <tbody>
                <tr>
                    <td><?php echo __('Order Total:', 'paypal-for-woocommerce'); ?></td>
                    <td><?php echo $this->order->get_formatted_order_total(); ?></td>
                </tr>
                <?php if (isset($this->ae_capture_amount) && $this->ae_capture_amount > 0) { ?>
                    <tr>
                        <td><?php echo __('Capture: ', 'paypal-for-woocommerce'); ?></td>
                        <td><?php echo wc_price($this->ae_capture_amount, array('currency' => $this->currency_code)); ?></td>
                    </tr>
                <?php } ?>
                <?php if (isset($this->ae_refund_amount) && $this->ae_refund_amount > 0) { ?>
                    <tr>
                        <td><?php echo __('Refund:', 'paypal-for-woocommerce'); ?></td>
                        <td><?php echo wc_price($this->ae_refund_amount, array('currency' => $this->currency_code)); ?></td>
                    </tr>
                <?php } ?>
                <?php if (isset($this->ae_void_amount) && $this->ae_void_amount > 0) { ?>
                    <tr>
                        <td><?php echo __('Void:', 'paypal-for-woocommerce'); ?></td>
                        <td><?php echo wc_price($this->ae_void_amount, array('currency' => $this->currency_code)); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
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

    public function angelleye_ppcp_save($post_id, $post_or_order_object) {
        if (!empty($_POST['save']) && $_POST['save'] == 'Submit') {
            $order = ( $post_or_order_object instanceof WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
            if (!is_a($order, 'WC_Order')) {
                return;
            }
            if (ae_is_active_screen(AE_SHOP_ORDER_SCREENS)) {
                if (!empty($_POST['angelleye_ppcp_payment_action'])) {
                    $order_data = wc_clean($_POST);
                    $action = wc_clean($_POST['angelleye_ppcp_payment_action']);
                    $hook_name = 'angelleye_ppcp_' . strtolower($action) . '_admin';
                    if (!did_action('woocommerce_order_action_' . sanitize_title($hook_name))) {
                        do_action('woocommerce_order_action_' . sanitize_title($hook_name), $order, $order_data);
                    }
                }
            }
        }
    }

    public function admin_notices() {
        try {
            if (isset($_GET['page']) && 'paypal-for-woocommerce' === $_GET['page']) {
                return;
            }
            if (class_exists('Paypal_For_Woocommerce_Multi_Account_Management')) {
                return;
            }
            $notice_data['classic_upgrade'] = array(
                'id' => 'ppcp_notice_classic_upgrade',
                'ans_company_logo' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/angelleye-icon.jpg',
                'ans_message_title' => 'Important PayPal Update Required',
                'ans_message_description' => sprintf('Upgrade now to PayPal Commerce for better features, enhanced security, <b>reduced fees</b>, and future-proof integration. <a target="_blank" href="%s">Click to learn more about the upgrade process.</a> Don\'t miss out on the advantages of PayPal Commerce! <br>', 'https://www.angelleye.com/how-to-migrate-classic-paypal-to-commerce-platform/'),
                'ans_button_url' => admin_url('options-general.php?page=paypal-for-woocommerce'),
                'ans_button_label' => 'Upgrade Now',
                'is_dismiss' => false,
                'is_button_secondary' => true,
                'ans_secondary_button_label' => "Learn More",
                'ans_secondary_button_url' => 'https://www.angelleye.com/how-to-migrate-classic-paypal-to-commerce-platform/'
            );
            $notice_data['vault_upgrade'] = array(
                'id' => 'ppcp_notice_vault_upgrade',
                'ans_company_logo' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/angelleye-icon.jpg',
                'ans_message_title' => 'PayPal Commerce Now Supports Token Payments / Subscriptions!',
                'ans_message_description' => 'Maximize the power of PayPal Commerce in your WordPress store by enabling the Vault functionality. Unlock advanced features such as Subscriptions, One-Click Upsells, and more, for a seamless and streamlined payment experience. Upgrade your store today and take full advantage of the benefits offered by PayPal Commerce!',
                'ans_button_url' => admin_url('admin.php?page=wc-settings&tab=checkout&section=angelleye_ppcp&move=tokenization_subscriptions'),
                'ans_button_label' => 'Enable PayPal Vault',
                'is_dismiss' => true
            );
             $notice_data['enable_apple_pay'] = array(
                'id' => 'ppcp_notice_apple_pay',
                'ans_company_logo' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/angelleye-icon.jpg',
                'ans_message_title' => 'PayPal Commerce Now Supports Apple Pay!',
                'ans_message_description' => 'Unlock advanced features such as Apple Pay. Upgrade your store today and take full advantage of the benefits offered by PayPal Commerce!',
                'ans_button_url' => admin_url('admin.php?page=wc-settings&tab=checkout&section=angelleye_ppcp&move=additional_authorizations'),
                'ans_button_label' => 'Enable Apple Pay',
                'is_dismiss' => true
            );
            $notice_data['vault_upgrade_enable_apple_pay'] = array(
                'id' => 'ppcp_notice_vault_upgrade_apple_pay',
                'ans_company_logo' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/angelleye-icon.jpg',
                'ans_message_title' => 'PayPal Commerce Now Supports Apple Pay and Token Payments / Subscriptions!',
                'ans_message_description' => 'Unlock advanced features such as Apple Pay, Subscriptions, One-Click Upsells, and more, for a seamless and streamlined payment experience. Upgrade your store today and take full advantage of the benefits offered by PayPal Commerce!',
                'ans_button_url' => admin_url('admin.php?page=wc-settings&tab=checkout&section=angelleye_ppcp&move=tokenization_subscriptions'),
                'ans_button_label' => 'Activate These Features',
                'is_dismiss' => true
            );
            $notice_data['outside_us'] = array(
                'id' => 'ppcp_notice_outside_us',
                'ans_company_logo' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/angelleye-icon.jpg',
                'ans_message_title' => '',
                'ans_message_description' => 'We notice that are running WooCommerce Subscriptions and your store country is outside the United States.<br>  
                    Unfortunately, the PayPal Commerce Platform Vault functionality, which is required for Subscriptions, is only available for United States PayPal accounts.<br>
                    If your PayPal account is in fact based in the United States, you can continue with this update.<br>
                    However, if your PayPal account is not based in the U.S. you will need to wait until this feature is available in your country.<br>
                    Please submit a <a href="https://angelleye.atlassian.net/servicedesk/customer/portal/1/group/1/create/1">help desk</a> ticket with any questions or concerns about this.',
                'is_dismiss' => true,
            );
            $result = $this->seller_onboarding->angelleye_track_seller_onboarding_status_from_cache($this->merchant_id);
            $notice_data = json_decode(json_encode($notice_data));
            $notice_type = angelleye_ppcp_display_upgrade_notice_type($result);

            $ae_ppcp_account_reconnect_notice = get_option('ae_ppcp_account_reconnect_notice');
            // This is to ensure to display the notice only when angelleye_ppcp (main gateway) is enabled.
            if (!empty($ae_ppcp_account_reconnect_notice) && !empty($notice_type['active_ppcp_gateways'])
            && isset($notice_type['active_ppcp_gateways']['angelleye_ppcp'])) {
                // This can be converted as a switch statement as the flag will tell use error reason
                $notice_data_account_reconnect = array(
                    'id' => 'ppcp_notice_account_reconnect',
                    'ans_company_logo' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/angelleye-icon.jpg',
                    'ans_message_title' => 'Action Required: Reconnect Your PayPal Account',
                    'ans_message_description' => "We're experiencing permission issues preventing us from making certain PayPal API calls on your behalf. To fix this, please reconnect your PayPal account from the settings page. Click the button below to go to settings and select 'Reconnect PayPal Account'.",
                    'ans_button_url' => admin_url('options-general.php?page=paypal-for-woocommerce'),
                    'ans_button_label' => 'Settings',
                    'is_dismiss' => false
                );
                angelleye_ppcp_display_notice(json_decode(json_encode($notice_data_account_reconnect)));
            }

            if (!empty($notice_type)) {
                foreach ($notice_type as $key => $type) {
                    if ('classic_upgrade' === $key && $type === true && isset($notice_data->$key)) {
                        angelleye_ppcp_display_notice($notice_data->$key);
                    }
                    if ('outside_us' === $key && $type === true && isset($notice_data->$key)) {
                        angelleye_ppcp_display_notice($notice_data->$key);
                    }
                }
            }
            if(isset($notice_type['vault_upgrade']) &&  $notice_type['vault_upgrade'] === true && isset($notice_type['enable_apple_pay']) &&  $notice_type['enable_apple_pay'] === true) {
                angelleye_ppcp_display_notice($notice_data->vault_upgrade_enable_apple_pay);
            } elseif(isset($notice_type['vault_upgrade']) &&  $notice_type['vault_upgrade'] === true) {
                angelleye_ppcp_display_notice($notice_data->vault_upgrade);
            } elseif(isset($notice_type['enable_apple_pay']) &&  $notice_type['enable_apple_pay'] === true) {
                angelleye_ppcp_display_notice($notice_data->enable_apple_pay);
            }
        } catch (Exception $ex) {

        }
    }

}
