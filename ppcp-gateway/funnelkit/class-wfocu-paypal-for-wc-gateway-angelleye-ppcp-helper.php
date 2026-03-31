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
                    'order_item_ids' => array(),
                );

                $parent_order->update_meta_data('_angelleye_wfocu_ppcp_upsell_payments', $existing);
                $parent_order->save();

                // Hook into wfocu_offer_accepted_and_processed (fires in both batching and non-batching modes)
                // to capture the WC order item IDs added for this upsell and store them against the capture.
                $parent_order_id = $parent_order->get_id();
                $stored_transaction_id = $transaction_id;
                add_action('wfocu_offer_accepted_and_processed', function($offer_id, $package, $porder, $new_order, $txn_id, $items_added) use ($parent_order_id, $stored_transaction_id) {
                    if ($txn_id !== $stored_transaction_id) {
                        return;
                    }
                    $parent_order = wc_get_order($parent_order_id);
                    if (!is_a($parent_order, 'WC_Order')) {
                        return;
                    }
                    $existing = $parent_order->get_meta('_angelleye_wfocu_ppcp_upsell_payments', true);
                    if (!is_array($existing)) {
                        return;
                    }
                    foreach ($existing as &$entry) {
                        if (isset($entry['capture_id']) && $entry['capture_id'] === $stored_transaction_id) {
                            $entry['order_item_ids'] = is_array($items_added) ? $items_added : array();
                            break;
                        }
                    }
                    unset($entry);
                    $parent_order->update_meta_data('_angelleye_wfocu_ppcp_upsell_payments', $existing);
                    $parent_order->save();
                }, 20, 6);

            } catch (Exception $ex) {

            }
        }
    }
}
