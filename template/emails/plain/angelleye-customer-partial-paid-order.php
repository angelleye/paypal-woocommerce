<?php

defined('ABSPATH') || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html(wp_strip_all_tags($email_heading));
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";


echo sprintf(esc_html__('Hi %s,', 'paypal-for-woocommerce'), esc_html($order->get_billing_first_name())) . "\n\n";

echo sprintf(esc_html__('Just to let you know &mdash; we\'ve received your order #%s, and it is now being processed:', 'paypal-for-woocommerce'), esc_html($order->get_order_number())) . "\n\n";

do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);

// Capture aggregation + refund subtraction + date localization +
// plain-text currency formatting all live in
// AngellEYE_PPCP_Partial_Payment_Data (ppcp-gateway/includes/).
// Required by classes/wc-email-customer-partial-paid-order.php, so the
// helper class is guaranteed to be loaded by the time this template
// renders.
$pfw_summary = AngellEYE_PPCP_Partial_Payment_Data::get_capture_summary($order);
$all_captures = $pfw_summary['captures'];
$total_captured = $pfw_summary['total_captured'];
$order_total = $pfw_summary['order_total'];
$balance = $pfw_summary['balance'];
$pfw_currency = $pfw_summary['currency'];

if ($total_captured > 0) :
?>
================================================================================
<?php echo strtoupper(__('Payment Summary', 'paypal-for-woocommerce')); ?>

================================================================================

<?php _e('Order Total:', 'paypal-for-woocommerce'); ?>                                                    <?php echo esc_html(AngellEYE_PPCP_Partial_Payment_Data::format_plain_price($order_total, $pfw_currency)); ?>


<?php echo strtoupper(__('Payments Received:', 'paypal-for-woocommerce')); ?>

--------------------------------------------------------------------------------
<?php foreach ($all_captures as $txn_id => $capture_data) : ?>
  <?php echo esc_html($capture_data['date']); ?>                <?php echo str_pad(esc_html(AngellEYE_PPCP_Partial_Payment_Data::format_plain_price($capture_data['amount'], $pfw_currency)), 20, ' ', STR_PAD_LEFT); ?>

<?php endforeach; ?>

--------------------------------------------------------------------------------
<?php _e('Total Paid:', 'paypal-for-woocommerce'); ?>                                                     <?php echo esc_html(AngellEYE_PPCP_Partial_Payment_Data::format_plain_price($total_captured, $pfw_currency)); ?>

<?php if ($balance > 0) : ?>
================================================================================
<?php echo strtoupper(__('Balance Due:', 'paypal-for-woocommerce')); ?>                                                    <?php echo esc_html(AngellEYE_PPCP_Partial_Payment_Data::format_plain_price($balance, $pfw_currency)); ?>

================================================================================

<?php _e('The remaining balance will be charged when additional items are ready to ship.', 'paypal-for-woocommerce'); ?>

<?php elseif ($balance < 0) : ?>
================================================================================
<?php echo strtoupper(__('Additional Amount Billed:', 'paypal-for-woocommerce')); ?>                                       <?php echo esc_html(AngellEYE_PPCP_Partial_Payment_Data::format_plain_price(abs($balance), $pfw_currency)); ?>

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