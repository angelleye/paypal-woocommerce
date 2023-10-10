<?php

/**
 * AngellEye Session Manager
 * This class stores the data related to PPCP checkout process to support the payments
 */
class AngellEye_Session_Manager
{
    private static ?AngellEye_Session_Manager $instance = null;
    private string $sessionName = 'angelleye_ppcp_session';
    private ?array $_data = null;

    public static function instance(): AngellEye_Session_Manager
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function save()
    {
        if (!class_exists('WooCommerce') || WC()->session == null) {
            return false;
        }
        WC()->session->set($this->sessionName, $this->_data);
    }

    public function getData($key, $default = null)
    {
        if ($this->_data == null) {
            if (!class_exists('WooCommerce') || empty(WC()->session)) {
                return false;
            }

            $this->_data = WC()->session->get($this->sessionName, []);
        }
        $key = sanitize_key( $key );
        return $this->_data[$key] ?? $default;
    }

    public function setData($key, $value = null)
    {
        if ( $value !== $this->getData( $key ) ) {
            $this->_data[ sanitize_key( $key ) ] = $value;
            $this->save();
        }
    }

    public function removeData($key)
    {
        if (isset($this->_data[sanitize_key($key)])) {
            unset($this->_data[sanitize_key($key)]);
            $this->save();
        }
    }

    public function clearSession()
    {
        $this->_data = null;
        $this->save();
    }

    public static function get($key, $default = null)
    {
        $instance = self::instance();
        return $instance->getData($key, $default);
    }

    public static function set($key, $value = null): void
    {
        $instance = self::instance();
        $instance->setData($key, $value);
    }

    public static function unset($key)
    {
        $instance = self::instance();
        $instance->removeData($key);
    }

    /**
     * It will clear complete session
     */
    public static function clear()
    {
        $instance = self::instance();
        $instance->clearSession();
    }
}
