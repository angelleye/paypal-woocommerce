<?php

class AngellEYE_PayPal_PPCP_Log {

    public $log_enabled = true;
    public $logger = false;

    public function __construct() {
        $this->angelleye_ppcp_load_class();
    }

    public function log($message, $level = 'info') {
        if ($this->log_enabled) {
            if (empty($this->logger)) {
                $this->logger = wc_get_logger();
            }
            $this->logger->log($level, $message, array('source' => 'angelleye_ppcp'));
        }
    }

    public function angelleye_ppcp_load_class() {
        try {
            if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-ppcp-angelleye-settings.php';
            }
            $this->settings = new WC_Gateway_PPCP_AngellEYE_Settings();
        } catch (Exception $ex) {

        }
    }

}
