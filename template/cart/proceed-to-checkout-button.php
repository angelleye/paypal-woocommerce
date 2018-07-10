<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

$change_proceed_checkout_button_text = get_option('change_proceed_checkout_button_text');
if (!empty($change_proceed_checkout_button_text)) {
    $proceed_checkout_button_text = $change_proceed_checkout_button_text;
} else {
    $proceed_checkout_button_text = __('Proceed to Checkout', 'paypal-for-woocommerce');
}
?>
<a href = "<?php echo esc_url(wc_get_checkout_url()); ?>" class = "checkout-button button alt wc-forward">
    <?php echo $proceed_checkout_button_text;  ?>
</a>
