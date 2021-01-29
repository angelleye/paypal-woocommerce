<?php

if (!function_exists('angelleye_ppcp_is_local_server')) {

    function angelleye_ppcp_is_local_server() {
        return false;
        if (!isset($_SERVER['HTTP_HOST'])) {
            return;
        }
        if ($_SERVER['HTTP_HOST'] === 'localhost' || substr($_SERVER['REMOTE_ADDR'], 0, 3) === '10.' || substr($_SERVER['REMOTE_ADDR'], 0, 7) === '192.168') {
            return true;
        }
        $live_sites = [
            'HTTP_CLIENT_IP',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
        ];
        foreach ($live_sites as $ip) {
            if (!empty($_SERVER[$ip])) {
                return false;
            }
        }
        if (in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'))) {
            return true;
        }
        $fragments = explode('.', site_url());
        if (in_array(end($fragments), array('dev', 'local', 'localhost', 'test'))) {
            return true;
        }
        return false;
    }

    if (!function_exists('angelleye_ppcp_get_raw_data')) {

        function angelleye_ppcp_get_raw_data() {
            try {
                if (function_exists('phpversion') && version_compare(phpversion(), '5.6', '>=')) {
                    return file_get_contents('php://input');
                }
                global $HTTP_RAW_POST_DATA;
                if (!isset($HTTP_RAW_POST_DATA)) {
                    $HTTP_RAW_POST_DATA = file_get_contents('php://input');
                }
                return $HTTP_RAW_POST_DATA;
            } catch (Exception $ex) {
                
            }
        }

    }

    if (!function_exists('angelleye_ppcp_remove_empty_key')) {

        function angelleye_ppcp_remove_empty_key($data) {
            $original = $data;
            $data = array_filter($data);
            $data = array_map(function ($e) {
                return is_array($e) ? angelleye_ppcp_remove_empty_key($e) : $e;
            }, $data);
            return $original === $data ? $data : angelleye_ppcp_remove_empty_key($data);
        }

    }
}
