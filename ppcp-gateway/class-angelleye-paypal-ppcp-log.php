<?php

class AngellEYE_PayPal_PPCP_Log {
    
    public $log_enabled = true;
    public $logger = false;

    public function __construct() {
        
    }

    public function log($message, $level = 'info') {
        if ($this->log_enabled) {
            if (empty($this->logger)) {
                $this->logger = wc_get_logger();
            }
            $this->logger->log($level, $message, array('source' => 'angelleye_ppcp'));
        }
    }

}
