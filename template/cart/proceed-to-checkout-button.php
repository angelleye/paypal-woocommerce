<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

$change_proceed_checkout_button_text = get_option('change_proceed_checkout_button_text');
if (!empty($change_proceed_checkout_button_text)) {
    ?>
    <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="checkout-button button alt wc-forward<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : '' ); ?>">
        <?php echo apply_filters('angelleye_ppcp_proceed_to_checkout_button', $change_proceed_checkout_button_text); ?>
    </a>
<?php } else { ?>
    <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="checkout-button button alt wc-forward<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : '' ); ?>">
        <?php esc_html_e('Proceed to checkout', 'woocommerce'); ?>
    </a>
    <?php
}
