<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles responses from PayPal IPN.
 */
class PayPal_WooCoomerce_IPN_Handler {

    public function __construct() {
        $this->sandbox = '';
        $this->add_log = new WC_Logger();
        $payment_status = array('completed', 'pending', 'failed', 'denied', 'expired', 'voided', 'refunded', 'reversed', 'canceled_reversal');
        foreach ($payment_status as $key => $value) {
            add_action('paypal_ipn_for_wordpress_payment_status_' . strtolower($value), 'angelleye_paypal_woocommerce_order_status_handler', 10, 1);
        }
    }

    public function angelleye_paypal_woocommerce_order_status_handler($posted) {
        if (!empty($posted['custom']) && ( $order = $this->get_paypal_order($posted['custom']) )) {
            $posted['payment_status'] = strtolower($posted['payment_status']);
            if (isset($posted['test_ipn']) && 1 == $posted['test_ipn'] && 'pending' == $posted['payment_status']) {
                $posted['payment_status'] = 'completed';
            }
            $order_id = version_compare( WC_VERSION, '3.0', '<' ) ? $order->id : $order->get_id();
            $this->add_log->add('paypal_woocommerce_ipn', 'Found order #' . $$order_id);
            $this->add_log->add('paypal_woocommerce_ipn', 'Payment status: ' . $posted['payment_status']);
            if (method_exists($this, 'payment_status_' . $posted['payment_status'])) {
                call_user_func(array($this, 'payment_status_' . $posted['payment_status']), $order, $posted);
            }
        }
    }

    /**
     * Check for a valid transaction type.
     * @param string $txn_type
     */
    protected function validate_transaction_type($txn_type) {
        $accepted_types = array('cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money');
        if (!in_array(strtolower($txn_type), $accepted_types)) {
            $this->add_log->add('paypal_woocommerce_ipn', 'Aborting, Invalid type:' . $txn_type);
            exit;
        }
    }

    /**
     * Check currency from IPN matches the order.
     * @param WC_Order $order
     * @param string $currency
     */
    protected function validate_currency($order, $currency) {
        if (version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency() != $currency) {
            $this->add_log->add('paypal_woocommerce_ipn', 'Payment error: Currencies do not match (sent "' . version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency() . '" | returned "' . $currency . '")');
            $order->update_status('on-hold', sprintf(__('Validation error: PayPal currencies do not match (code %s).', 'woo-paypal-plus'), $currency));
            exit;
        }
    }

    /**
     * Check payment amount from IPN matches the order.
     * @param WC_Order $order
     * @param int $amount
     */
    protected function validate_amount($order, $amount) {
        if (number_format($order->get_total(), 2, '.', '') != number_format($amount, 2, '.', '')) {
            $this->add_log->add('paypal_woocommerce_ipn', 'Payment error: Amounts do not match (gross ' . $amount . ')');
            $order->update_status('on-hold', sprintf(__('Validation error: PayPal amounts do not match (gross %s).', 'woo-paypal-plus'), $amount));
            exit;
        }
    }

    /**
     * Handle a completed payment.
     * @param WC_Order $order
     * @param array $posted
     */
    protected function payment_status_completed($order, $posted) {
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        if ($order->has_status('completed')) {
            $this->add_log->add('paypal_woocommerce_ipn', 'Aborting, Order #' . $order_id . ' is already complete.');
            exit;
        }
        $this->validate_transaction_type($posted['txn_type']);
        $this->validate_currency($order, $posted['mc_currency']);
        $this->validate_amount($order, $posted['mc_gross']);
        $this->save_paypal_meta_data($order, $posted);
        if ('completed' === $posted['payment_status']) {
            $this->payment_complete($order, (!empty($posted['txn_id']) ? wc_clean($posted['txn_id']) : ''), __('IPN payment completed', 'woo-paypal-plus'));
            if (!empty($posted['mc_fee'])) {
                if ($old_wc) {
                    update_post_meta($order_id, 'PayPal Transaction Fee', wc_clean($posted['mc_fee']));
                } else {
                    update_post_meta( $order->get_id(), 'PayPal Transaction Fee', wc_clean($posted['mc_fee']) );
                }
            }
        } else {
            $this->payment_on_hold($order, sprintf(__('Payment pending: %s', 'woo-paypal-plus'), $posted['pending_reason']));
        }
    }

    /**
     * Handle a pending payment.
     * @param WC_Order $order
     * @param array $posted
     */
    protected function payment_status_pending($order, $posted) {
        $this->payment_status_completed($order, $posted);
    }

    /**
     * Handle a failed payment.
     * @param WC_Order $order
     * @param array $posted
     */
    protected function payment_status_failed($order, $posted) {
        $order->update_status('failed', sprintf(__('Payment %s via IPN.', 'woo-paypal-plus'), wc_clean($posted['payment_status'])));
    }

    /**
     * Handle a denied payment.
     * @param WC_Order $order
     * @param array $posted
     */
    protected function payment_status_denied($order, $posted) {
        $this->payment_status_failed($order, $posted);
    }

    /**
     * Handle an expired payment.
     * @param WC_Order $order
     * @param array $posted
     */
    protected function payment_status_expired($order, $posted) {
        $this->payment_status_failed($order, $posted);
    }

    /**
     * Handle a voided payment.
     * @param WC_Order $order
     * @param array $posted
     */
    protected function payment_status_voided($order, $posted) {
        $this->payment_status_failed($order, $posted);
    }

    /**
     * Handle a refunded order.
     * @param WC_Order $order
     * @param array $posted
     */
    protected function payment_status_refunded($order, $posted) {
        if ($order->get_total() == ( $posted['mc_gross'] * -1 )) {
            $order_id = version_compare( WC_VERSION, '3.0', '<' ) ? $order->id : $order->get_id();
            $order->update_status('refunded', sprintf(__('Payment %s via IPN.', 'woo-paypal-plus'), strtolower($posted['payment_status'])));
            $this->send_ipn_email_notification(
                    sprintf(__('Payment for order %s refunded', 'woo-paypal-plus'), '<a class="link" href="' . esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')) . '">' . $order->get_order_number() . '</a>'), sprintf(__('Order #%s has been marked as refunded - PayPal reason code: %s', 'woo-paypal-plus'), $order->get_order_number(), $posted['reason_code'])
            );
        }
    }

    /**
     * Handle a reveral.
     * @param WC_Order $order
     * @param array $posted
     */
    protected function payment_status_reversed($order, $posted) {
        $order_id = version_compare( WC_VERSION, '3.0', '<' ) ? $order->id : $order->get_id();
        $order->update_status('on-hold', sprintf(__('Payment %s via IPN.', 'woo-paypal-plus'), wc_clean($posted['payment_status'])));
        $this->send_ipn_email_notification(
                sprintf(__('Payment for order %s reversed', 'woo-paypal-plus'), '<a class="link" href="' . esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')) . '">' . $order->get_order_number() . '</a>'), sprintf(__('Order #%s has been marked on-hold due to a reversal - PayPal reason code: %s', 'woo-paypal-plus'), $order->get_order_number(), wc_clean($posted['reason_code']))
        );
    }

    /**
     * Handle a cancelled reveral.
     * @param WC_Order $order
     * @param array $posted
     */
    protected function payment_status_canceled_reversal($order, $posted) {
        $order_id = version_compare( WC_VERSION, '3.0', '<' ) ? $order->id : $order->get_id();
        $this->send_ipn_email_notification(
                sprintf(__('Reversal cancelled for order #%s', 'woo-paypal-plus'), $order->get_order_number()), sprintf(__('Order #%s has had a reversal cancelled. Please check the status of payment and update the order status accordingly here: %s', 'woo-paypal-plus'), $order->get_order_number(), esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')))
        );
    }

    /**
     * Save important data from the IPN to the order.
     * @param WC_Order $order
     * @param array $posted
     */
    protected function save_paypal_meta_data($order, $posted) {
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        if (!empty($posted['payer_email'])) {
            if ($old_wc) {
                update_post_meta($order_id, 'Payer PayPal address', wc_clean($posted['payer_email']));
            } else {
                update_post_meta( $order->get_id(), 'Payer PayPal address', wc_clean($posted['payer_email']) );
            }
        }
        if (!empty($posted['first_name'])) {
            if ($old_wc) {
                update_post_meta($order_id, 'Payer first name', wc_clean($posted['first_name']));
            } else {
                update_post_meta( $order->get_id(), 'Payer first name', wc_clean($posted['first_name']) );
            }
        }
        if (!empty($posted['last_name'])) {
            if ($old_wc) {
                update_post_meta($order_id, 'Payer last name', wc_clean($posted['last_name']));
            } else {
                update_post_meta( $order->get_id(), 'Payer last name', wc_clean($posted['last_name']) );
            }
        }
        if (!empty($posted['payment_type'])) {
            if ($old_wc) {
                update_post_meta($order_id, 'Payment type', wc_clean($posted['payment_type']));
            } else {
                update_post_meta( $order->get_id(), 'Payment type', wc_clean($posted['payment_type']) );
            }
        }
    }

    /**
     * Send a notification to the user handling orders.
     * @param string $subject
     * @param string $message
     */
    protected function send_ipn_email_notification($subject, $message) {
        $new_order_settings = get_option('woocommerce_new_order_settings', array());
        $mailer = WC()->mailer();
        $message = $mailer->wrap_message($subject, $message);
        $mailer->send(!empty($new_order_settings['recipient']) ? $new_order_settings['recipient'] : get_option('admin_email'), strip_tags($subject), $message);
    }

    /**
     * Get the order from the PayPal 'Custom' variable.
     * @param  string $raw_custom JSON Data passed back by PayPal
     * @return bool|WC_Order object
     */
    protected function get_paypal_order($raw_custom) {
        if (( $custom = json_decode($raw_custom) ) && is_object($custom)) {
            $order_id = $custom->order_id;
            $order_key = version_compare(WC_VERSION, '3.0', '<') ? $custom->order_key : $custom->get_order_key();
        } elseif (preg_match('/^a:2:{/', $raw_custom) && !preg_match('/[CO]:\+?[0-9]+:"/', $raw_custom) && ( $custom = maybe_unserialize($raw_custom) )) {
            $order_id = $custom[0];
            $order_key = $custom[1];
        } else {
            $this->add_log->add('paypal_woocommerce_ipn', 'Error: Order ID and key were not found in "custom".');
            return false;
        }
        if (!$order = wc_get_order($order_id)) {
            $order_id = wc_get_order_id_by_order_key($order_key);
            $order = wc_get_order($order_id);
        }
        $order_key_value = version_compare(WC_VERSION, '3.0', '<') ? $order->order_key : $order->get_order_key();
        if (!$order || $order_key_value !== $order_key) {
            $this->add_log->add('paypal_woocommerce_ipn', 'Error: Order Keys do not match.');
            return false;
        }
        return $order;
    }

    /**
     * Complete order, add transaction ID and note.
     * @param  WC_Order $order
     * @param  string   $txn_id
     * @param  string   $note
     */
    protected function payment_complete($order, $txn_id = '', $note = '') {
        $order->add_order_note($note);
        $order->payment_complete($txn_id);
    }

    /**
     * Hold order and add note.
     * @param  WC_Order $order
     * @param  string   $reason
     */
    protected function payment_on_hold($order, $reason = '') {
        $order->update_status('on-hold', $reason);
        if (version_compare(WC_VERSION, '3.0', '<')) {
            $order->reduce_order_stock();
        } else {
            wc_reduce_stock_levels($order->get_id());
        }
        WC()->cart->empty_cart();
    }

}
