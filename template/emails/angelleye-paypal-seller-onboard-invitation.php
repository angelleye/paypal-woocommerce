<?php
if (!defined('ABSPATH')) {
    exit;
}
do_action('woocommerce_email_header', $email_heading, $email);
?>
<p><?php echo __('Hello,', 'paypal-for-woocommerce'); ?></p>
<p><?php printf(esc_html__('Just one more step to connect your PayPal account to %s and begin receiving payments for your products and services.', 'paypal-for-woocommerce'), wp_specialchars_decode(get_option('blogname'), ENT_QUOTES)); ?></p>
<p><?php printf(esc_html__('Click the button below to begin the progress.  Simply log in to your PayPal account and follow the steps to get connected.', 'paypal-for-woocommerce')); ?></p>

<p>
    <?php
    do_action('angelleye_pppc_seller_onboard_html', $post_id);
    ?>
</p>
<p><?php printf(esc_html__('Make sure to click the "Return to Store" button at the end of the procedure.', 'paypal-for-woocommerce')); ?></p>
<?php
if ($additional_content) {
    echo wp_kses_post(wpautop(wptexturize($additional_content)));
}
do_action('woocommerce_email_footer', $email);
