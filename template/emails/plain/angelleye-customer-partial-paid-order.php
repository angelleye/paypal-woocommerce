<?php

defined('ABSPATH') || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html(wp_strip_all_tags($email_heading));
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";


echo sprintf(esc_html__('Hi %s,', 'paypal-for-woocommerce'), esc_html($order->get_billing_first_name())) . "\n\n";

echo sprintf(esc_html__('Just to let you know &mdash; we\'ve received your order #%s, and it is now being processed:', 'paypal-for-woocommerce'), esc_html($order->get_order_number())) . "\n\n";

do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);

// Collect all captures with dates and amounts
$all_captures = array();
$total_captured = 0;

foreach ($order->get_items() as $item_id => $item) {
    $captures = wc_get_order_item_meta($item_id, '_ppcp_capture_details', true);
    if (!empty($captures) && is_array($captures)) {
        foreach ($captures as $capture) {
            if (isset($capture['_ppcp_transaction_amount'])) {
                $amount = (float) $capture['_ppcp_transaction_amount'];
                $total_captured += $amount;

                // Use transaction ID as key to avoid duplicate entries for same capture
                $txn_id = $capture['_ppcp_transaction_id'] ?? '';
                if (!empty($txn_id) && !isset($all_captures[$txn_id])) {
                    $all_captures[$txn_id] = array(
                        'date' => $capture['_ppcp_transaction_date'] ?? '',
                        'amount' => $amount
                    );
                } elseif (!empty($txn_id)) {
                    // Same capture applies to multiple items, sum the amounts
                    $all_captures[$txn_id]['amount'] += $amount;
                }
            }
        }
    }
}

// Sort captures by date
uasort($all_captures, function($a, $b) {
    return strtotime($a['date']) - strtotime($b['date']);
});

$order_total = (float) $order->get_total();
$balance = $order_total - $total_captured;

if ($total_captured > 0) :
?>
================================================================================
<?php echo strtoupper(__('Payment Summary', 'paypal-for-woocommerce')); ?>

================================================================================

<?php _e('Order Total:', 'paypal-for-woocommerce'); ?>                                                    <?php echo strip_tags(wc_price($order_total, array('currency' => $order->get_currency()))); ?>


<?php echo strtoupper(__('Payments Received:', 'paypal-for-woocommerce')); ?>

--------------------------------------------------------------------------------
<?php foreach ($all_captures as $txn_id => $capture_data) : ?>
  <?php echo esc_html($capture_data['date']); ?>                <?php echo str_pad(strip_tags(wc_price($capture_data['amount'], array('currency' => $order->get_currency()))), 20, ' ', STR_PAD_LEFT); ?>

<?php endforeach; ?>

--------------------------------------------------------------------------------
<?php _e('Total Paid:', 'paypal-for-woocommerce'); ?>                                                     <?php echo strip_tags(wc_price($total_captured, array('currency' => $order->get_currency()))); ?>

<?php if ($balance > 0) : ?>
================================================================================
<?php echo strtoupper(__('Balance Due:', 'paypal-for-woocommerce')); ?>                                                    <?php echo strip_tags(wc_price($balance, array('currency' => $order->get_currency()))); ?>

================================================================================

<?php _e('The remaining balance will be charged when additional items are ready to ship.', 'paypal-for-woocommerce'); ?>

<?php elseif ($balance < 0) : ?>
================================================================================
<?php echo strtoupper(__('Additional Amount Billed:', 'paypal-for-woocommerce')); ?>                                       <?php echo strip_tags(wc_price(abs($balance), array('currency' => $order->get_currency()))); ?>

================================================================================
<?php endif; ?>

<?php

echo "\n----------------------------------------\n\n";

do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);

do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);

echo "\n\n----------------------------------------\n\n";

if ($additional_content) {
    echo esc_html(wp_strip_all_tags(wptexturize($additional_content)));
    echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post(apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text')));
