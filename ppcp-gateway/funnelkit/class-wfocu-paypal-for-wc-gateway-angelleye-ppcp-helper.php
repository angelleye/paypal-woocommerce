<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WFOCU_Paypal_For_WC_Gateway_AngellEYE_PPCP_Helper')) {
    class WFOCU_Paypal_For_WC_Gateway_AngellEYE_PPCP_Helper {

        public static function is_wfocu_batching_mode() {
            try {
                $order_behavior = WFOCU_Core()->funnels->get_funnel_option('order_behavior');
                return ('batching' === $order_behavior);
            } catch (Exception $ex) {
                return false;
            }
        }

        public static function store_wfocu_batching_upsell_payment($parent_order, $ppcp_resp, $transaction_id, $offer_id, $gateway) {
            try {
                if (!is_a($parent_order, 'WC_Order')) {
                    return;
                }

                $existing = $parent_order->get_meta('_angelleye_wfocu_ppcp_upsell_payments', true);
                if (!is_array($existing)) {
                    $existing = array();
                }

                $existing[] = array(
                    'gateway' => $gateway,
                    'funnel_id' => WFOCU_Core()->data->get_funnel_id(),
                    'offer_id' => $offer_id,
                    'paypal_order_id' => isset($ppcp_resp['id']) ? $ppcp_resp['id'] : '',
                    'capture_id' => $transaction_id,
                    'transaction_id' => $transaction_id,
                    'status' => isset($ppcp_resp['status']) ? $ppcp_resp['status'] : '',
                    'created_at' => time(),
                );

                $parent_order->update_meta_data('_angelleye_wfocu_ppcp_upsell_payments', $existing);
                $parent_order->save();
            } catch (Exception $ex) {
                
            }
        }
    }
}
