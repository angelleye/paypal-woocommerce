<?php
if (!defined('ABSPATH')) {
    exit;
}


do_action('woocommerce_email_header', $email_heading, $email);
?>

<?php ?>
<p><?php __('Hi There,', 'paypal-for-woocommerce'); ?></p>
<?php ?>
<p><?php printf(esc_html__('Just to let you know &mdash; yo\'ve received invitation for linked your account on %s.', 'paypal-for-woocommerce'), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES )); ?></p>

<?php

do_action('angelleye_pppc_seller_onboard_html', $post_id);


if ($additional_content) {
    echo wp_kses_post(wpautop(wptexturize($additional_content)));
}

do_action('woocommerce_email_footer', $email);
