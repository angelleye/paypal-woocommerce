<?php

trait AngellEye_PPCP_Core
{
    public ?WC_Gateway_PPCP_AngellEYE_Settings $setting_obj;
    public ?AngellEYE_PayPal_PPCP_Log $api_log;
    public ?AngellEYE_PayPal_PPCP_Request $api_request;
    public ?AngellEYE_PayPal_PPCP_DCC_Validate $dcc_applies;
    public ?AngellEYE_PayPal_PPCP_Payment $payment_request;
    public ?WC_AngellEYE_PayPal_PPCP_Payment_Token $ppcp_payment_token;
    public $setting_obj_fields;
    public static $_instance;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function angelleye_ppcp_load_class($loadSettingsFields = false) {
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
            if (!class_exists('WC_AngellEYE_PayPal_PPCP_Payment_Token')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/ppcp-payment-token/class-angelleye-paypal-ppcp-payment-token.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Migration')) {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-migration.php');
            }
            AngellEYE_PayPal_PPCP_Migration::instance();
            $this->setting_obj = WC_Gateway_PPCP_AngellEYE_Settings::instance();
            $this->api_log = AngellEYE_PayPal_PPCP_Log::instance();
            $this->api_request = AngellEYE_PayPal_PPCP_Request::instance();
            $this->dcc_applies = AngellEYE_PayPal_PPCP_DCC_Validate::instance();
            $this->payment_request = AngellEYE_PayPal_PPCP_Payment::instance();
            $this->ppcp_payment_token = WC_AngellEYE_PayPal_PPCP_Payment_Token::instance();
            if ($loadSettingsFields) {
                $this->setting_obj_fields = $this->setting_obj->angelleye_ppcp_setting_fields();
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' .$ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function isSandbox(): bool
    {
        return 'yes' === $this->setting_obj->get('testmode', 'no');
    }
}
