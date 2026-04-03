<?php

defined('ABSPATH') || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html(wp_strip_all_tags($email_heading));
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html__('Hello,', 'paypal-for-woocommerce') . "\n\n";

echo sprintf(esc_html__('Just one more step to connect your PayPal account to %s and begin receiving payments for your products and services.', 'paypal-for-woocommerce'), wp_specialchars_decode(get_option('blogname'), ENT_QUOTES)) . "\n\n";

echo esc_html__('Click the link below to begin the process. Simply log in to your PayPal account and follow the steps to get connected.', 'paypal-for-woocommerce') . "\n\n";

do_action('angelleye_pppc_seller_onboard_html', $post_id);

echo "\n\n";

echo esc_html__('Make sure to click the "Return to Store" button at the end of the procedure.', 'paypal-for-woocommerce') . "\n\n";

if ($additional_content) {
    echo "---\n\n";
    echo esc_html(wp_strip_all_tags(wptexturize($additional_content)));
    echo "\n\n";
}

echo wp_kses_post(apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text')));
