<?php

defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Log {

    public $log_option;
    public $logger = false;
    protected static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->angelleye_ppcp_load_class();
        $this->log_option = $this->settings->get('debug', 'everything');
    }

    public function log($message, $level = 'info') {
        if ($this->log_option == 'everything' || ( $level == 'error' && $this->log_option == 'errors_warnings_only')) {
            if (empty($this->logger)) {
                $this->logger = wc_get_logger();
            }
            $this->logger->log($level, $message, array('source' => 'angelleye_ppcp'));
        }
    }

    public function temp_log($message, $level = 'info') {
        if (empty($this->logger)) {
            $this->logger = wc_get_logger();
        }
        $this->logger->log($level, $message, array('source' => 'angelleye_ppcp_temp'));
    }

    public function angelleye_ppcp_load_class() {
        try {
            if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-ppcp-angelleye-settings.php';
            }
            $this->settings = WC_Gateway_PPCP_AngellEYE_Settings::instance();
        } catch (Exception $ex) {
            $this->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->log($ex->getMessage(), 'error');
        }
    }

}
