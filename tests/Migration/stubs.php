<?php
/**
 * WordPress and WooCommerce function/class stubs for unit testing.
 *
 * These stubs provide minimal implementations of WordPress and WooCommerce
 * functions/classes so that unit tests can run without a full WP environment.
 */

// ── WordPress i18n ──────────────────────────────────────────────────────────

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('_e')) {
    function _e($text, $domain = 'default') {
        echo $text;
    }
}

// ── WordPress hooks ─────────────────────────────────────────────────────────

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $args = 1) {
        return true;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $args = 1) {
        return true;
    }
}

if (!function_exists('do_action')) {
    function do_action($hook, ...$args) {}
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value, ...$args) {
        return $value;
    }
}

// ── WordPress post meta ─────────────────────────────────────────────────────

if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key = '', $single = false) {
        return $single ? '' : [];
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta($post_id, $key, $value, $prev = '') {
        return true;
    }
}

if (!function_exists('delete_post_meta')) {
    function delete_post_meta($post_id, $key, $value = '') {
        return true;
    }
}

// ── WooCommerce Subscriptions ───────────────────────────────────────────────

/**
 * Global test subscriptions registry.
 *
 * Tests can set `$_test_subscriptions[$id] = $mock` to control
 * what `wcs_get_subscription()` returns for a given ID.
 *
 * @var array<int, WC_Subscription|null>
 */
$_test_subscriptions = [];

if (!function_exists('wcs_get_subscription')) {
    function wcs_get_subscription($subscription_id) {
        global $_test_subscriptions;
        return $_test_subscriptions[$subscription_id] ?? null;
    }
}

// ── WooCommerce class stubs ─────────────────────────────────────────────────
// Minimal stubs so PHPUnit can create mocks via createMock().

if (!class_exists('WC_Subscription')) {
    class WC_Subscription {
        public function get_id(): int { return 0; }
        public function get_meta($key, $single = true) { return ''; }
        public function update_meta_data($key, $value) {}
        public function delete_meta_data($key) {}
        public function get_payment_method(): string { return ''; }
        public function set_payment_method($method) {}
        public function get_payment_method_title(): string { return ''; }
        public function set_payment_method_title($title) {}
        public function get_customer_id(): int { return 0; }
        public function add_order_note($note) {}
        public function save() {}
    }
}

if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {
    class WC_Gateway_PPCP_AngellEYE_Settings {
        private static $instance;
        public static function instance() {
            if (!self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        public function get($key, $default = '') {
            return $default;
        }
    }
}
