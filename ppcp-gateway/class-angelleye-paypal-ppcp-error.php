<?php

defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Error {

    protected static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        
    }

}
