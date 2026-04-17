<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('AngellEYE_PPCP_Partial_Payment_Data', false)) {

    /**
     * Shared data-collection + formatting helpers for the Partially Paid
     * order email templates (HTML and plain text). Keeps the capture
     * aggregation, refund subtraction, de-duplication and date-formatting
     * logic in one place so the two template files stay thin — they only
     * handle markup and layout.
     *
     * Consumers:
     *   template/emails/angelleye-customer-partial-paid-order.php
     *   template/emails/plain/angelleye-customer-partial-paid-order.php
     */
    final class AngellEYE_PPCP_Partial_Payment_Data {

        /**
         * Aggregate every PayPal capture recorded on a WC_Order's line
         * items into a single ordered collection, subtract refund
         * amounts, and compute the outstanding balance.
         *
         * The writer at class-angelleye-paypal-ppcp-payment.php stores the
         * same capture record (same _ppcp_transaction_id) on every cart
         * item included in the capture. We dedupe by transaction id so
         * "Total Paid" and "Payments Received" are counted exactly once
         * per capture.
         *
         * Each capture record may also carry a total_refund_amount field
         * set during the refund flow — subtract it from the displayed
         * amount so figures stay accurate after partial refunds.
         *
         * @param WC_Order $order
         * @return array {
         *     @type array $captures       txn_id => [date, date_raw, amount]
         *     @type float $total_captured Sum of (transaction_amount - refund) across unique captures
         *     @type float $order_total    $order->get_total() as float
         *     @type float $balance        order_total - total_captured (positive = due, negative = over)
         *     @type string $currency      Order currency code
         * }
         */
        public static function get_capture_summary($order) {
            $summary = array(
                'captures' => array(),
                'total_captured' => 0.0,
                'order_total' => 0.0,
                'balance' => 0.0,
                'currency' => '',
            );

            if (!is_a($order, 'WC_Order')) {
                return $summary;
            }

            $summary['currency'] = $order->get_currency();
            $summary['order_total'] = (float) $order->get_total();

            $all_captures = array();
            $total_captured = 0.0;

            foreach ($order->get_items() as $item_id => $item) {
                $captures = wc_get_order_item_meta($item_id, '_ppcp_capture_details', true);
                if (empty($captures) || !is_array($captures)) {
                    continue;
                }
                foreach ($captures as $capture) {
                    if (!isset($capture['_ppcp_transaction_amount'])) {
                        continue;
                    }
                    $txn_id = $capture['_ppcp_transaction_id'] ?? '';
                    if ($txn_id === '' || isset($all_captures[$txn_id])) {
                        continue;
                    }
                    $amount = (float) $capture['_ppcp_transaction_amount']
                              - (float) ($capture['total_refund_amount'] ?? 0);
                    $total_captured += $amount;
                    $all_captures[$txn_id] = array(
                        'date_raw' => $capture['_ppcp_transaction_date'] ?? '',
                        'amount' => $amount,
                    );
                }
            }

            // Chronological sort; guard strtotime('' / false) === false so
            // malformed dates don't produce undefined ordering.
            uasort($all_captures, function ($a, $b) {
                $a_ts = !empty($a['date_raw']) ? strtotime($a['date_raw']) : 0;
                $b_ts = !empty($b['date_raw']) ? strtotime($b['date_raw']) : 0;
                return ($a_ts ?: 0) - ($b_ts ?: 0);
            });

            // Pre-format each capture date via WC's localized formatter
            // so international stores see their configured date/time
            // format and timezone instead of the m/d/y H:i string the
            // writer persists.
            foreach ($all_captures as $txn_id => $cap) {
                $all_captures[$txn_id]['date'] = self::format_capture_date($cap['date_raw']);
            }

            $summary['captures'] = $all_captures;
            $summary['total_captured'] = $total_captured;
            $summary['balance'] = $summary['order_total'] - $total_captured;

            return $summary;
        }

        /**
         * Reparse a raw capture date string and format it for display
         * using the store's locale + timezone. Falls back to the raw
         * string when parsing fails so we never swallow the data.
         *
         * @param string $raw Date string persisted by the capture writer.
         * @return string
         */
        public static function format_capture_date($raw) {
            if (empty($raw)) {
                return '';
            }
            $ts = strtotime($raw);
            if (!$ts) {
                return (string) $raw;
            }
            try {
                $dt = new WC_DateTime('@' . $ts);
                $dt->setTimezone(new DateTimeZone(wc_timezone_string()));
                return wc_format_datetime($dt);
            } catch (Exception $e) {
                return (string) $raw;
            }
        }

        /**
         * Format an amount for the plain-text email. `wc_price()` returns
         * HTML wrapped in <span> / <bdi> with HTML entities for currency
         * (&euro;, &nbsp;). `strip_tags` removes the tags but leaves
         * entities raw, which plain-text mail clients render literally.
         * This helper strips tags AND decodes entities so the customer
         * sees "549,00 €" instead of "549,00&nbsp;&euro;".
         *
         * @param float  $amount
         * @param string $currency Currency code (e.g. EUR, USD).
         * @return string
         */
        public static function format_plain_price($amount, $currency) {
            $html = wc_price((float) $amount, array('currency' => $currency));
            return trim(html_entity_decode(
                wp_strip_all_tags($html),
                ENT_QUOTES,
                'UTF-8'
            ));
        }

    }

}
