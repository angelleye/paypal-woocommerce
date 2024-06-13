<?php
defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Admin_Action {

    use WC_PPCP_Pre_Orders_Trait;
    use WC_Gateway_Base_AngellEYE;
    private $angelleye_ppcp_plugin_name;
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
    protected static $_instance_self = null;
    public $is_auto_capture_auth;
    public ?AngellEYE_PayPal_PPCP_Seller_Onboarding $seller_onboarding;
    public $is_sandbox;
    public $merchant_id;
    public $paymentaction;
    public $view_transaction_url;

    public static function instance() {
        if (is_null(self::$_instance_self)) {
            self::$_instance_self = new self();
        }
        return self::$_instance_self;
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
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function removeAutoCaptureHooks() {
        remove_action('woocommerce_order_status_processing', array($this, 'angelleye_ppcp_capture_payment'));
        remove_action('woocommerce_order_status_completed', array($this, 'angelleye_ppcp_capture_payment'));
    }

    public function angelleye_ppcp_add_hooks() {
        $this->paymentaction = $this->setting_obj->get('paymentaction', 'capture');
        $this->is_auto_capture_auth = false;
        if ($this->paymentaction === 'authorize') {
            $this->is_auto_capture_auth = 'yes' === $this->setting_obj->get('auto_capture_auth', 'yes');
        }
        $this->is_sandbox = 'yes' === $this->setting_obj->get('testmode', 'no');
        if ($this->is_sandbox) {
            $this->merchant_id = $this->setting_obj->get('sandbox_merchant_id', '');
        } else {
            $this->merchant_id = $this->setting_obj->get('live_merchant_id', '');
        }
        add_action('admin_notices', array($this, 'admin_notices'));
        // On checkout page these hooks conflicts when we change order status to processing or completed from our payment gateway
        // We need to apply these hooks in admin panel
        if ($this->is_auto_capture_auth) {
            add_action('woocommerce_order_status_processing', array($this, 'angelleye_ppcp_capture_payment'));
            add_action('woocommerce_order_status_completed', array($this, 'angelleye_ppcp_capture_payment'));
            add_action('woocommerce_order_status_cancelled', array($this, 'angelleye_ppcp_cancel_authorization'));
            add_action('woocommerce_order_status_refunded', array($this, 'angelleye_ppcp_cancel_authorization'));
        }
        add_action('wc_pre_order_status_completed', [$this, 'angelleye_ppcp_pre_order_order_status_completed'], 10, 1);
        add_action('woocommerce_process_shop_order_meta', array($this, 'angelleye_ppcp_save'), 10, 2);
        add_action('woocommerce_order_item_add_line_buttons', array($this, 'angelleye_ppcp_capture_void_refund_submit'), 10, 1);
        add_action('woocommerce_order_item_add_action_buttons', array($this, 'angelleye_ppcp_add_order_action_buttons'), 10, 1);
        add_action('admin_enqueue_scripts', array($this, 'angelleye_ppcp_add_order_action_js'), 10);
        add_action('woocommerce_admin_order_totals_after_total', array($this, 'angelleye_ppcp_add_order_action_item_edit'), 10, 1);
        add_action('woocommerce_after_order_itemmeta', array($this, 'angelleye_ppcp_display_capture_details'), 10, 3);
        add_action('woocommerce_after_order_itemmeta', array($this, 'angelleye_ppcp_display_refund_details'), 11, 3);
        add_filter('woocommerce_hidden_order_itemmeta', array($this, 'woocommerce_hidden_order_itemmeta'), 10, 1);
        if (!has_action('woocommerce_admin_order_totals_after_tax', array($this, 'angelleye_ppcp_display_total_capture'))) {
            add_action('woocommerce_admin_order_totals_after_tax', array($this, 'angelleye_ppcp_display_total_capture'), 1, 1);
        }
        add_action('admin_notices', array($this, 'angelleye_ppcp_display_payment_authorization_notice'));

        add_filter('angelleye_shipping_tracking_enabled_payment_methods', [$this, 'angelleye_pfw_add_ppcp_payment_methods'], 10, 2);
    }
    
    public function angelleye_ppcp_pre_order_order_status_completed($order_id) {
        if($this->has_pre_order($order_id) && $this->has_pre_order_charged_upon_release($order_id)){
            if($this->is_paypal_vault_used_for_pre_order()) {
                $this->angelleye_ppcp_vault_payment($order_id);
            } else {
                $this->angelleye_ppcp_capture_payment ($order_id);
            }
        }
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

    public function angelleye_ppcp_capture_payment($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        $payment_method = $order->get_payment_method();
        $paymentaction = angelleye_ppcp_get_post_meta($order, '_paymentaction');
        $auth_transaction_id = angelleye_ppcp_get_post_meta($order, '_auth_transaction_id');
        $auto_capture_payment_support_gateways = ['angelleye_ppcp', 'angelleye_ppcp_cc', 'angelleye_ppcp_google_pay', 'angelleye_ppcp_apple_pay'];

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

        if (in_array($payment_method, ['angelleye_ppcp_cc', 'angelleye_ppcp', 'angelleye_ppcp_apple_pay', 'angelleye_ppcp_google_pay']) && $transaction_id && $paymentaction === 'authorize') {
            $trans_details = $this->payment_request->angelleye_ppcp_show_details_authorized_payment($transaction_id);
            if ($this->angelleye_ppcp_is_authorized_only($trans_details)) {
                $response = $this->payment_request->angelleye_ppcp_void_authorized_payment($transaction_id);
                if (!is_wp_error($response)) {
                    $note = __("Authorization Voided", 'paypal-for-woocommerce');
                } else {
                    $note = __("Void Authorization Failed:", 'paypal-for-woocommerce') . ': ' . $response->get_error_message();
                }
                $order->add_order_note($note);
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
            $order = ( $post_or_order_object instanceof WP_Post ) ? wc_get_order($post_or_order_object->ID) : $post_or_order_object;
            if (!is_a($order, 'WC_Order')) {
                return;
            }
            $screen = ae_get_shop_order_screen_id();
            if (ae_is_active_screen($screen) && $this->angelleye_ppcp_is_display_paypal_transaction_details($order->get_id())) {
                add_meta_box('angelleye-ppcp-order-action', __('PayPal Transaction Activity', 'paypal-for-woocommerce'), array($this, 'angelleye_ppcp_order_action_callback'), $screen, 'normal', 'high');
            }
        } catch (Exception $ex) {

        }
    }

    public function angelleye_ppcp_is_display_paypal_transaction_details($post_id, $payment_actions = ["authorize"]) {
        try {
            $order = wc_get_order($post_id);
            if (!empty($order)) {
                $payment_method = $order->get_payment_method();
                $payment_action = angelleye_ppcp_get_post_meta($order, '_payment_action');
                if (!empty($payment_method) && !empty($payment_action) && in_array($payment_method, ['angelleye_ppcp_cc', 'angelleye_ppcp', 'angelleye_ppcp_apple_pay', 'angelleye_ppcp_google_pay']) && $order->get_total() > 0 && in_array($payment_action, $payment_actions)) {
                    return true;
                }
            }
        } catch (Exception $ex) {

        }
        return false;
    }

    public function angelleye_ppcp_save($post_id, $post_or_order_object) {
        if (!empty($_POST['is_ppcp_submited']) && 'yes' === $_POST['is_ppcp_submited']) {
            $order = ( $post_or_order_object instanceof WP_Post ) ? wc_get_order($post_or_order_object->ID) : $post_or_order_object;
            if (!is_a($order, 'WC_Order')) {
                return;
            }
            if (!empty($_POST['order_metabox_angelleye_ppcp_payment_action'])) {
                $order_data = wc_clean($_POST);
                $action = wc_clean($_POST['order_metabox_angelleye_ppcp_payment_action']);
                switch ($action) {
                    case 'void':
                        $this->angelleye_ppcp_admin_void_action_handler($order, $order_data);
                        break;
                    case 'capture':
                        $this->angelleye_ppcp_admin_capture_action_handler($order, $order_data);
                        break;
                    case 'shipment_tracking':
                        $this->angelleye_ppcp_admin_shipment_tracking_action_handler($order, $order_data);
                        break;
                    default:
                        break;
                }
            }
        }
    }

    public function admin_notices() {
        try {
            if (isset($_GET['page']) && 'paypal-for-woocommerce' === $_GET['page']) {
                return;
            }
            $notice_data['classic_upgrade'] = array(
                'id' => 'ppcp_notice_classic_upgrade',
                'ans_company_logo' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/angelleye-icon.jpg',
                'ans_message_title' => 'Important PayPal Update Required',
                'ans_message_description' => sprintf('Upgrade now to %s for better features, enhanced security, <b>reduced fees</b>, and future-proof integration. <a target="_blank" href="%s">Click to learn more about the upgrade process.</a> Don\'t miss out on the advantages of %s! <br>', AE_PPCP_NAME, 'https://www.angelleye.com/how-to-migrate-classic-paypal-to-commerce-platform/', AE_PPCP_NAME),
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
                'ans_message_title' => AE_PPCP_NAME . ' Now Supports Token Payments / Subscriptions!',
                'ans_message_description' => 'Maximize the power of '. AE_PPCP_NAME . ' in your WordPress store by enabling the Vault functionality. Unlock advanced features such as Subscriptions, One-Click Upsells, and more, for a seamless and streamlined payment experience. Upgrade your store today and take full advantage of the benefits offered by ' . AE_PPCP_NAME . '!',
                'ans_button_url' => admin_url('admin.php?page=wc-settings&tab=checkout&section=angelleye_ppcp&move=tokenization_subscriptions'),
                'ans_button_label' => 'Enable PayPal Vault',
                'is_dismiss' => true
            );
            $notice_data['enable_apple_pay'] = array(
                'id' => 'ppcp_notice_apple_pay',
                'ans_company_logo' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/angelleye-icon.jpg',
                'ans_message_title' => AE_PPCP_NAME . ' Now Supports Apple Pay!',
                'ans_message_description' => 'Unlock advanced features such as Apple Pay. Upgrade your store today and take full advantage of the benefits offered by' . AE_PPCP_NAME . '!',
                'ans_button_url' => admin_url('admin.php?page=wc-settings&tab=checkout&section=angelleye_ppcp&move=additional_authorizations'),
                'ans_button_label' => 'Enable Apple Pay',
                'is_dismiss' => true
            );
            $notice_data['vault_upgrade_enable_apple_pay'] = array(
                'id' => 'ppcp_notice_vault_upgrade_apple_pay',
                'ans_company_logo' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/angelleye-icon.jpg',
                'ans_message_title' => AE_PPCP_NAME . ' Now Supports Apple Pay and Token Payments / Subscriptions!',
                'ans_message_description' => 'Unlock advanced features such as Apple Pay, Subscriptions, One-Click Upsells, and more, for a seamless and streamlined payment experience. Upgrade your store today and take full advantage of the benefits offered by ' . AE_PPCP_NAME . '!',
                'ans_button_url' => admin_url('admin.php?page=wc-settings&tab=checkout&section=angelleye_ppcp&move=tokenization_subscriptions'),
                'ans_button_label' => 'Activate These Features',
                'is_dismiss' => true
            );
            $notice_data['outside_us'] = array(
                'id' => 'ppcp_notice_outside_us',
                'ans_company_logo' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/angelleye-icon.jpg',
                'ans_message_title' => '',
                'ans_message_description' => 'We notice that are running WooCommerce Subscriptions and your store country is outside the United States.<br>
                    Unfortunately, the '. AE_PPCP_NAME . ' Platform Vault functionality, which is required for Subscriptions, is only available for United States PayPal accounts.<br>
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
            if (!empty($ae_ppcp_account_reconnect_notice) && !empty($notice_type['active_ppcp_gateways']) && isset($notice_type['active_ppcp_gateways']['angelleye_ppcp'])) {
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
            if (isset($notice_type['vault_upgrade']) && $notice_type['vault_upgrade'] === true && isset($notice_type['enable_apple_pay']) && $notice_type['enable_apple_pay'] === true) {
                angelleye_ppcp_display_notice($notice_data->vault_upgrade_enable_apple_pay);
            } elseif (isset($notice_type['vault_upgrade']) && $notice_type['vault_upgrade'] === true) {
                angelleye_ppcp_display_notice($notice_data->vault_upgrade);
            } elseif (isset($notice_type['enable_apple_pay']) && $notice_type['enable_apple_pay'] === true) {
                angelleye_ppcp_display_notice($notice_data->enable_apple_pay);
            }
        } catch (Exception $ex) {

        }
    }

    public function angelleye_ppcp_order_action_list($order_id) {
        try {
            $order = wc_get_order($order_id);
            if (!is_a($order, 'WC_Order')) {
                echo __('Error: Unable to detect the order, please refresh again to retry or Contact PayPal For WooCommerce support.', 'paypal-for-woocommerce');
                return;
            }
            $this->order = $order;
            $order_total_amount = floatval($order->get_total(''));
            $this->angelleye_ppcp_order_status_data = array();
            $this->angelleye_ppcp_order_actions = array();
            $paypal_order_id = angelleye_ppcp_get_post_meta($order, '_paypal_order_id');
            if (empty($paypal_order_id)) {
                //echo __('PayPal order id does not exist for this order.', 'paypal-for-woocommerce');
                return;
            }
            $this->payment_response = $this->payment_request->angelleye_ppcp_get_paypal_order_details($paypal_order_id);

            if (isset($this->payment_response['name']) && $this->payment_response['name'] == 'RESOURCE_NOT_FOUND') {
                $auth_transaction_id = angelleye_ppcp_get_post_meta($order, '_auth_transaction_id');
                // This condition is to fix the old orders where paypal_order_id has been replaced with authorization_id when capture was processed during order complete status change
                if (!empty($auth_transaction_id)) {
                    $auth_response = $this->payment_request->angelleye_ppcp_get_authorized_payment($auth_transaction_id);
                    $paypal_txn_id = $auth_response['supplementary_data']['related_ids']['order_id'] ?? '';
                    if (!empty($paypal_txn_id)) {
                        $paypal_order_id = $paypal_txn_id;
                        $order->update_meta_data('_paypal_order_id', $paypal_txn_id);
                        $order->save();
                        $this->payment_response = $this->payment_request->angelleye_ppcp_get_paypal_order_details($paypal_order_id);
                    }
                }
            }
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
                                $this->angelleye_ppcp_order_status_data['refund'][$line_item['transaction_id']] = $line_item['amount'];
                            }
                            $this->ae_capture_amount = $this->ae_capture_amount + $captures['amount']['value'];
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

                            $this->ae_auth_amount = $this->ae_auth_amount + $authorizations['amount']['value'];
                            $this->angelleye_ppcp_order_status_data['capture'][$line_item['transaction_id']] = $line_item['amount'];
                            $this->angelleye_ppcp_order_status_data['void'][$line_item['transaction_id']] = $line_item['amount'];
                        }
                    }
                    if (isset($this->payment_response['purchase_units']['0']['payments']['authorizations']['0']['status']) && 'CREATED' === $this->payment_response['purchase_units']['0']['payments']['authorizations']['0']['status']) {
                        $this->angelleye_ppcp_order_actions['void'] = __('Void Authorization', '');
                        $this->angelleye_ppcp_order_actions['capture'] = __('Capture Funds', '');
                    }
                    if (isset($this->payment_response['purchase_units']['0']['payments']['authorizations']['0']['status']) && 'PARTIALLY_CAPTURED' === $this->payment_response['purchase_units']['0']['payments']['authorizations']['0']['status']) {
                        if ($this->ae_refund_amount < $this->ae_capture_amount) {
                            $this->angelleye_ppcp_order_actions['refund'] = __('Refund', '');
                            $this->angelleye_ppcp_order_actions['void'] = __('Void Authorization', '');
                            $this->angelleye_ppcp_order_actions['capture'] = __('Capture Funds', '');
                        }
                        if ($order_total_amount > $this->ae_capture_amount) {
                            $this->angelleye_ppcp_order_actions['capture'] = __('Capture Funds', '');
                        }
                    }
                    if (isset($this->payment_response['purchase_units']['0']['payments']['authorizations']['0']['status']) && 'CAPTURED' === $this->payment_response['purchase_units']['0']['payments']['authorizations']['0']['status']) {
                        if ($this->ae_refund_amount < $this->ae_capture_amount) {
                            $this->angelleye_ppcp_order_actions['refund'] = __('Refund', '');
                        }
                    }
                    if (isset($this->payment_response['purchase_units']['0']['payments']['authorizations']['0']['status']) && 'VOIDED' === $this->payment_response['purchase_units']['0']['payments']['authorizations']['0']['status']) {
                        unset($this->angelleye_ppcp_order_actions);
                    }
                }
            }
        } catch (Exception $ex) {

        }
    }

    /**
     * Add payment methods to the Shipment tracking supported gateway list
     * @param $shipping_tracking_payment_methods
     * @return array
     */
    public function angelleye_pfw_add_ppcp_payment_methods($shipping_tracking_payment_methods)
    {
        if (!is_array($shipping_tracking_payment_methods)) {
            $shipping_tracking_payment_methods = [];
        }
        $shipping_tracking_payment_methods = array_merge($shipping_tracking_payment_methods, ['angelleye_ppcp', 'angelleye_ppcp_cc', 'angelleye_ppcp_google_pay', 'angelleye_ppcp_apple_pay']);
        return array_unique($shipping_tracking_payment_methods);
    }

    public function angelleye_ppcp_add_order_action_buttons($order) {
        try {
            $shipment_tracking_enabled = defined('ANGELLEYE_PAYPAL_WOOCOMMERCE_SHIPMENT_TRACKING_VERSION');

            $should_display_transaction_details = $this->angelleye_ppcp_is_display_paypal_transaction_details($order->get_id(), ['authorize', 'capture']);

            // angelleye_ppcp_order_actions variable will be only set when order is and Authorization order
            if ($should_display_transaction_details && !empty($this->angelleye_ppcp_order_actions)) {
                wp_enqueue_script('angelleye-ppcp-order-action');
                if ($this->ae_capture_amount === 0) { ?>
                    <style>.button.refund-items {
                            display: none;
                        }</style>
                <?php } ?>
                <button type="button"
                        class="button angelleye-ppcp-order-capture" <?php echo (isset($this->angelleye_ppcp_order_actions['capture']) && !empty($this->angelleye_ppcp_order_actions)) ? '' : 'disabled'; ?>> <?php esc_html_e('Capture', 'paypal-for-woocommerce'); ?><?php echo wc_help_tip(__('Capture payment for the authorized order.', 'paypal-for-woocommerce')); ?></button>
                <button type="button"
                        class="button angelleye-ppcp-order-void" <?php echo (isset($this->angelleye_ppcp_order_actions['void']) && !empty($this->angelleye_ppcp_order_actions)) ? '' : 'disabled'; ?>><?php esc_html_e('Void Authorization', 'paypal-for-woocommerce'); ?><?php echo wc_help_tip(__('Void the authorized order to release the hold on the buyer\'s payment source.', 'paypal-for-woocommerce')); ?></button>
            <?php }

            if (in_array($order->get_status(), array('processing', 'completed', 'partial-payment')) && $shipment_tracking_enabled) {
                wp_enqueue_script('angelleye-ppcp-order-action');
                ?>
                <button type="button" class="button angelleye-ppcp-shipment-tracking"><?php esc_html_e('PayPal Shipment', 'paypal-for-woocommerce'); ?><?php echo wc_help_tip(__('Add shipment tracking details to WooCommerce and PayPal.', 'paypal-for-woocommerce')); ?></button>
            <?php } ?>
            <?php
        } catch (Exception $ex) {

        }
    }

    public function angelleye_ppcp_add_order_action_js() {
        wp_register_script('angelleye-ppcp-order-action', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/js/wc-gateway-ppcp-admin-action.js', array('jquery'), VERSION_PFW, true);
    }

    public function angelleye_ppcp_add_order_action_item_edit($order_id) {
        if ($this->angelleye_ppcp_is_display_paypal_transaction_details($order_id) === false) {
            return;
        }
        $this->angelleye_ppcp_order_action_list($order_id);
        $this->payment_request->angelleye_ppcp_sync_ppcp_capture_details($order_id);
        $this->angelleye_ppcp_order_meta_auth_capture_html();
    }

    public function angelleye_ppcp_order_meta_auth_capture_html() {
        if (isset($this->angelleye_ppcp_order_actions) && !empty($this->angelleye_ppcp_order_actions)) {
            ?>
            <tr class="ppcp_auth_void_border" style="display: none;">
                <td colspan="3" style="border-top: 1px solid #dfdfdf;">&nbsp</td>
            </tr>
        <?php } ?>
        <?php if (isset($this->angelleye_ppcp_order_status_data['capture']) && isset($this->angelleye_ppcp_order_actions['capture'])) { ?>
            <tr class="angelleye_ppcp_capture_box" style="display: none;">
                <td class="label"><?php echo __('Additional Capture Possible', 'paypal-for-woocommerce'); ?></td>
                <td width="1%"></td>
                <td class="total">
                    <fieldset>
                        <label for="additional_capture_yes"><input checked type="radio" name="additionalCapture" value="yes" id="additional_capture_yes">Yes<?php echo wc_help_tip(__('Yes (option to capture additional funds on this authorization if need)', 'paypal-for-woocommerce')); ?></label>
                        <label for="additional_capture_no"><input type="radio" name="additionalCapture" value="no" id="additional_capture_no">No<?php echo wc_help_tip(__('No (no additional capture needed; close authorization after this capture)', 'paypal-for-woocommerce')); ?></label>
                    </fieldset>
                </td>
            </tr>
            <tr class="angelleye_ppcp_capture_box" style="display: none;">
                <td class="label">
                    <label for="refund_amount">
                        <?php echo wc_help_tip(__('This will show the total amount to be capture/void', 'woocommerce')); ?>
                        <?php esc_html_e('Amount', 'paypal-for-woocommerce'); ?>:
                    </label>
                </td>
                <td width="1%"></td>
                <td class="total">
                    <input readonly="readonly" type="text" id="ppcp_refund_amount" name="ppcp_refund_amount" style="width: 250px;" class="wc_input_price"/>

                </td>
            </tr>
            <tr class="angelleye_ppcp_capture_box" style="display: none;">
                <td class="label"><?php
                    echo __('Note To Buyer (Optional)', 'paypal-for-woocommerce');
                    echo wc_help_tip(__('PayPal strongly recommends that you explain any unique circumstances (e.g. multiple captures, changes in item availability) to your buyer in detail below. Your buyer will see this note in the Transaction Details.', 'paypal-for-woocommerce'));
                    ?></td>
                <td width="1%"></td>
                <td class="total">
                    <textarea maxlength="150" rows="2" cols="20" class="wide-input" type="textarea" name="angelleye_ppcp_note_to_buyer_capture" id="angelleye_ppcp_note_to_buyer_capture" style="width: 250px;"></textarea>
                </td>
            </tr>
        <?php } ?>
        <?php if (isset($this->angelleye_ppcp_order_status_data['refund']) && isset($this->angelleye_ppcp_order_actions['refund'])) { ?>
            <tr class="angelleye_ppcp_refund_box" style="display: none;">
                <td class="label"><?php echo __('Transaction Id', ''); ?></td>
                <td width="1%"></td>
                <td class="total">
                    <select name="angelleye_ppcp_refund_data" id="angelleye_ppcp_refund_data" style="width: 250px;">
                        <?php
                        $i = 0;
                        foreach ($this->angelleye_ppcp_order_status_data['refund'] as $k => $v) :
                            if ($i == 0) {
                                echo "<option value=''>" . __('Select Transaction Id', '') . "</option>";
                            }
                            ?>
                            <option value="<?php echo esc_attr($k); ?>" ><?php echo esc_html($k) . ' - ' . $v; ?></option>
                            <?php
                            $i = $i + 1;
                        endforeach;
                        ?>
                    </select>
                </td>
            </tr>
            <tr class="angelleye_ppcp_refund_box" style="display: none;">
                <td class="label"><?php echo __('Refund Amount', 'paypal-for-woocommerce'); ?></td>
                <td width="1%"></td>
                <td class="total">
                    <fieldset>
                        <input type="text" placeholder="Enter amount" id="_regular_price" name="_angelleye_ppcp_refund_price" class="short wc_input_price text-box" style="width: 250px;">
                    </fieldset>
                </td>
            </tr>
            <tr class="angelleye_ppcp_refund_box" style="display: none;">
                <td class="label"><?php
                    echo __('Note To Buyer (Optional)', 'paypal-for-woocommerce');
                    echo wc_help_tip(__('PayPal strongly recommends that you explain any unique circumstances (e.g. multiple captures, changes in item availability) to your buyer in detail below. Your buyer will see this note in the Transaction Details.', 'paypal-for-woocommerce'));
                    ?> </td>
                <td width="1%"></td>
                <td class="total">
                    <textarea maxlength="150" rows="4" cols="50" class="wide-input" type="textarea" name="angelleye_ppcp_note_to_buyer_capture" id="angelleye_ppcp_note_to_buyer_capture" style="width: 250px;"></textarea>
                </td>
            </tr>
            <?php
        }
        ?></div>
        <?php if (isset($this->angelleye_ppcp_order_status_data['void']) && isset($this->angelleye_ppcp_order_actions['void'])) { ?>
            <tr class="angelleye_ppcp_void_box" style="display: none;">
                <td class="label">Note To Buyer (Optional)<?php echo wc_help_tip('PayPal strongly recommends that you explain any unique circumstances (e.g. multiple captures, changes in item availability) to your buyer in detail below. Your buyer will see this note in the Transaction Details.'); ?></td>
                <td width="1%"></td>
                <td class="total">
                    <textarea maxlength="150" rows="4" cols="50" class="wide-input" type="textarea" name="angelleye_ppcp_note_to_buyer_void" id="angelleye_ppcp_note_to_buyer_void" style="width: 250px;"></textarea>
                </td>
            </tr>
            <?php
        }
        ?>

        <?php
    }

    public function angelleye_ppcp_capture_void_refund_submit() {
        ?><input type="hidden" value="no" name="is_ppcp_submited" id="is_ppcp_submited"><input type="hidden" name="order_metabox_angelleye_ppcp_payment_action" id="order_metabox_angelleye_ppcp_payment_action"><button type="button" class="button angelleye-ppcp-order-action-submit button-primary"><?php esc_html_e('Submit', 'paypal-for-woocommerce'); ?></button><?php
    }

    public function angelleye_ppcp_display_capture_details($item_id, $item, $product) {
        $ppcp_capture_details = wc_get_order_item_meta($item_id, '_ppcp_capture_details', true);
        if (empty($ppcp_capture_details)) {
            return;
        }
        $order_id = wc_get_order_id_by_order_item_id($item_id);
        $order = wc_get_order($order_id);
        $enviorment = angelleye_ppcp_get_post_meta($order, '_enviorment', true);
        if ($enviorment === 'sandbox') {
            $this->view_transaction_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
        } else {
            $this->view_transaction_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
        }
        ?>
        <table cellspacing="0" class="display_meta">
            <?php
            foreach ($ppcp_capture_details as $index => $meta_array) :
                ?>
                <tr>
                    <td>
                        <?php
                        $ppcp_Capture_key_replace = array('_ppcp_transaction_id' => 'Transaction ID', '_ppcp_transaction_date' => 'Date', '_ppcp_transaction_amount' => 'Amount', 'total_refund_amount' => 'Total Refund Amount');
                        echo '<b>' . __('Capture Details', '') . '</b>: ';
                        $capture_details_html = '';
                        if (is_array($meta_array) && !empty($meta_array)) {
                            if (isset($meta_array['refund'])) {
                                $total_element = 4;
                            } else {
                                $total_element = 3;
                            }
                            $i = 1;
                            foreach ($meta_array as $key => $value) {
                                if (!is_array($value)) {
                                    if ($key === '_ppcp_transaction_date') {
                                        $capture_details_html .= esc_html(sprintf(__('%1$s at %2$s', 'woocommerce'), date_i18n(wc_date_format(), strtotime($value)), date_i18n(wc_time_format(), strtotime($value))));
                                    } elseif ($key === '_ppcp_transaction_amount' || 'total_refund_amount' === $key) {
                                        $capture_details_html .= $ppcp_Capture_key_replace[$key] . ': ' . wc_price($value, array('currency' => $order->get_currency()));
                                    } elseif ($key === '_ppcp_transaction_id') {
                                        $return_url = sprintf($this->view_transaction_url, $value);
                                        $capture_details_html .= $ppcp_Capture_key_replace[$key] . ': ' . ' <a href="' . esc_url($return_url) . '" target="_blank">' . esc_html($value) . '</a>';
                                    } else {
                                        $capture_details_html .= $ppcp_Capture_key_replace[$key] . ': ' . $value;
                                    }
                                    if ($total_element !== $i) {
                                        $capture_details_html .= ' | ';
                                    }
                                    $i = $i + 1;
                                }
                            }
                        }
                        echo $capture_details_html;
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php
    }

    public function angelleye_ppcp_display_refund_details($item_id, $item, $product) {
        $ppcp_refund_details = wc_get_order_item_meta($item_id, '_ppcp_refund_details', true);
        if (empty($ppcp_refund_details)) {
            return;
        }
        $order_id = wc_get_order_id_by_order_item_id($item_id);
        $order = wc_get_order($order_id);
        $enviorment = angelleye_ppcp_get_post_meta($order, '_enviorment', true);
        if ($enviorment === 'sandbox') {
            $this->view_transaction_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
        } else {
            $this->view_transaction_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
        }
        ?>
        <table cellspacing="0" class="display_meta">
            <?php
            foreach ($ppcp_refund_details as $index => $meta_array) :
                ?>
                <tr>
                    <td>
                        <?php
                        $ppcp_refund_key_replace = array('_ppcp_refund_id' => 'Refund ID', '_ppcp_refund_date' => 'Date', '_ppcp_refund_amount' => 'Amount');
                        echo '<b>' . __('Refund Details', 'paypal-for-woocommerce') . '</b>: ';
                        $refund_details_html = '';
                        if (is_array($meta_array) && !empty($meta_array)) {
                            $total_element = count($meta_array);
                            $i = 1;
                            foreach ($meta_array as $key => $value) {
                                if ('_ppcp_refund_amount' === $key) {
                                    $refund_details_html .= $ppcp_refund_key_replace[$key] . ': ' . wc_price($value, array('currency' => $order->get_currency()));
                                } elseif ('_ppcp_refund_date' === $key) {
                                    $refund_details_html .= esc_html(sprintf(__('%1$s at %2$s', 'woocommerce'), date_i18n(wc_date_format(), strtotime($value)), date_i18n(wc_time_format(), strtotime($value))));
                                } elseif ($key === '_ppcp_refund_id') {
                                    $return_url = sprintf($this->view_transaction_url, $value);
                                    $refund_details_html .= $ppcp_refund_key_replace[$key] . ':  <a href="' . esc_url($return_url) . '" target="_blank">' . esc_html($value) . '</a>';
                                } else {
                                    $refund_details_html .= $ppcp_refund_key_replace[$key] . ': ' . $value;
                                }
                                if ($total_element !== $i) {
                                    $refund_details_html .= ' | ';
                                }
                                $i = $i + 1;
                            }
                        }
                        echo $refund_details_html;
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php
    }

    public function woocommerce_hidden_order_itemmeta($order_itemmeta) {
        $order_itemmeta = array_merge($order_itemmeta, array('_ppcp_refund_details', '_ppcp_capture_details'));
        return $order_itemmeta;
    }

    public function angelleye_ppcp_admin_shipment_tracking_action_handler($order, $order_data) {
        try {
            do_action('angelleye_ppcp_send_shipment_tracking_line_item', $order, $order_data);
        } catch (Exception $ex) {

        }
    }

    public function angelleye_ppcp_display_total_capture($order_id) {
        $this->angelleye_ppcp_order_action_list($order_id);
        if (!$this->angelleye_ppcp_is_display_paypal_transaction_details($order_id)) {
            return;
        }
        if ($this->ae_capture_amount === 0) {
            return;
        }
        $order = wc_get_order($order_id);
        $payment_method = $order->get_payment_method();
        $paymentaction = angelleye_ppcp_get_post_meta($order, '_paymentaction');
        $payment_method = version_compare(WC_VERSION, '3.0', '<') ? $order->payment_method : $order->get_payment_method();
        $auto_capture_payment_support_gateways = ['angelleye_ppcp', 'angelleye_ppcp_google_pay', 'angelleye_ppcp_apple_pay'];
        $auth_transaction_id = angelleye_ppcp_get_post_meta($order, '_auth_transaction_id');
        if (!in_array($payment_method, $auto_capture_payment_support_gateways) && $paymentaction === 'authorize' && !empty($auth_transaction_id)) {
            return;
        }
        if ('on-hold' === $order->get_status()) {
            return false;
        }
        if ($order->get_status() == 'refunded') {
            return true;
        }
        ?>
        <tr>
            <td class="label">
                <?php esc_html_e('Total Capture:', 'paypal-for-woocommerce'); ?>
            </td>
            <td width="1%"></td>
            <td class="total">
                &nbsp;<?php echo wc_price($this->ae_capture_amount, array('currency' => $this->currency_code)); ?>
            </td>
        </tr>
        <?php
    }

    public function angelleye_ppcp_display_payment_authorization_notice() {
        global $post;
        $order = ( $post instanceof WP_Post ) ? wc_get_order( $post->ID ) : $post;
        if (!is_a($order, 'WC_Order')) {
            return;
        }
        if ('on-hold' != $order->get_status()) {
            return;
        }
        $screen = ae_is_active_screen(ae_get_shop_order_screen_id());
        if ($screen && $this->angelleye_ppcp_is_display_paypal_transaction_details($order->get_id())) {
            echo '<div class="updated woocommerce-message"><p>' . __('Capture the authorized order to receive funds in your PayPal account.') . '</p></div>';
        }
    }
    
    public function angelleye_ppcp_vault_payment($order_id) {
        $this->payment_request->angelleye_ppcp_capture_order_using_payment_method_token($order_id);
    }
}
