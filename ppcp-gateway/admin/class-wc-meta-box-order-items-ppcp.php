<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use Automattic\WooCommerce\Utilities\OrderUtil;

if (class_exists('WC_Meta_Box_Order_Items')) {

    class Custom_WC_Meta_Box_Order_Items extends WC_Meta_Box_Order_Items {

        public static function output($post) {
            global $post, $thepostid, $theorder;
            OrderUtil::init_theorder_object($post);
            if (!is_int($thepostid) && ( $post instanceof WP_Post )) {
                $thepostid = $post->ID;
            }
            $order = $theorder;
            $data = ( $post instanceof WP_Post ) ? get_post_meta($post->ID) : array();
            include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/template/wc-admin/html-order-items.php');
        }

        public static function save($post_id) {
            parent::save($post_id);
        }
    }

}
