<?php
if (!defined('ABSPATH')) {
    exit;
}
do_action('woocommerce_email_header', $email_heading, $email);
?>
<p><?php echo __('Hi There,', 'paypal-for-woocommerce'); ?></p>
<p><?php printf(esc_html__('Just to let you know &mdash; you\'ve received an invitation for linking your account on %s.', 'paypal-for-woocommerce'), wp_specialchars_decode(get_option('blogname'), ENT_QUOTES)); ?></p>
<p>
    <?php
    do_action('angelleye_pppc_seller_onboard_html', $post_id);
    ?>
</p>
<?php
if ($additional_content) {
    echo wp_kses_post(wpautop(wptexturize($additional_content)));
}
do_action('woocommerce_email_footer', $email);