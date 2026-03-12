<?php
if (!defined('ABSPATH')) {
    exit;
}


do_action('woocommerce_email_header', $email_heading, $email);
?>

<?php ?>
<p><?php printf(esc_html__('Hi %s,', 'paypal-for-woocommerce'), esc_html($order->get_billing_first_name())); ?></p>
<?php ?>
<p><?php printf(esc_html__('Just to let you know &mdash; we\'ve received your order #%s, and it is now being processed:', 'paypal-for-woocommerce'), esc_html($order->get_order_number())); ?></p>
<?php
do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);
?>

<?php
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
<div style="margin-bottom: 20px; padding: 15px; background-color: #fef3c7; border-left: 4px solid #f59e0b;">
    <p style="margin: 0 0 10px 0;"><strong><?php esc_html_e('Payment Summary', 'paypal-for-woocommerce'); ?></strong></p>
    <table cellspacing="0" cellpadding="0" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 6px 0;"><?php esc_html_e('Order Total:', 'paypal-for-woocommerce'); ?></td>
            <td style="padding: 6px 0; text-align: right;"><?php echo wp_kses_post(wc_price($order_total, array('currency' => $order->get_currency()))); ?></td>
        </tr>
        <tr>
            <td colspan="2" style="padding: 10px 0 6px 0; border-top: 1px solid #e5e7eb;">
                <strong><?php esc_html_e('Payments Received:', 'paypal-for-woocommerce'); ?></strong>
            </td>
        </tr>
        <?php foreach ($all_captures as $txn_id => $capture_data) : ?>
        <tr>
            <td style="padding: 4px 0 4px 15px; font-size: 0.95em;">
                <?php echo esc_html($capture_data['date']); ?>
            </td>
            <td style="padding: 4px 0; text-align: right; font-size: 0.95em;">
                <?php echo wp_kses_post(wc_price($capture_data['amount'], array('currency' => $order->get_currency()))); ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td style="padding: 6px 0; border-top: 1px solid #e5e7eb;">
                <strong><?php esc_html_e('Total Paid:', 'paypal-for-woocommerce'); ?></strong>
            </td>
            <td style="padding: 6px 0; text-align: right; border-top: 1px solid #e5e7eb;">
                <strong><?php echo wp_kses_post(wc_price($total_captured, array('currency' => $order->get_currency()))); ?></strong>
            </td>
        </tr>
        <?php if ($balance > 0) : ?>
        <tr>
            <td style="padding: 6px 0; border-top: 2px solid #d97706;">
                <strong><?php esc_html_e('Balance Due:', 'paypal-for-woocommerce'); ?></strong>
            </td>
            <td style="padding: 6px 0; text-align: right; border-top: 2px solid #d97706;">
                <strong><?php echo wp_kses_post(wc_price($balance, array('currency' => $order->get_currency()))); ?></strong>
            </td>
        </tr>
        <?php elseif ($balance < 0) : ?>
        <tr>
            <td style="padding: 6px 0; border-top: 2px solid #d97706;">
                <strong><?php esc_html_e('Additional Amount Billed:', 'paypal-for-woocommerce'); ?></strong>
            </td>
            <td style="padding: 6px 0; text-align: right; border-top: 2px solid #d97706;">
                <strong><?php echo wp_kses_post(wc_price(abs($balance), array('currency' => $order->get_currency()))); ?></strong>
            </td>
        </tr>
        <?php endif; ?>
    </table>
    <?php if ($balance > 0) : ?>
    <p style="margin: 10px 0 0 0; font-size: 0.9em;"><?php esc_html_e('The remaining balance will be charged when additional items are ready to ship.', 'paypal-for-woocommerce'); ?></p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php
do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);

do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);

if ($additional_content) {
    echo wp_kses_post(wpautop(wptexturize($additional_content)));
}

do_action('woocommerce_email_footer', $email);
