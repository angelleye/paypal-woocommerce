<?php
/**
 * Payment methods
 *
 * Shows customer payment methods on the account page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/payment-methods.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 2.6.0
 */
defined('ABSPATH') || exit;

if (!class_exists('AngellEYE_PayPal_PPCP_Vault_Sync')) {
    include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-vault-sync.php');
}

$vault_sync = AngellEYE_PayPal_PPCP_Vault_Sync::instance();
$saved_methods = $vault_sync->angelleye_ppcp_wc_get_customer_saved_methods_list();

$has_methods = (bool) $saved_methods;
$types = wc_get_account_payment_methods_types();

$ccEndingText = function ($method) {
    return sprintf(esc_html__('%1$s ending in %2$s', 'woocommerce'), esc_html(wc_get_credit_card_type_label($method['method']['brand'])), esc_html($method['method']['last4']));
};
do_action('woocommerce_before_account_payment_methods', $has_methods);
$available_payment_gateways = WC()->payment_gateways->get_available_payment_gateways()
?>

<?php if ($has_methods) : ?>

    <table class="woocommerce-MyAccount-paymentMethods shop_table shop_table_responsive account-payment-methods-table">
        <thead>
            <tr>
                <?php foreach (wc_get_account_payment_methods_columns() as $column_id => $column_name) : ?>
                    <th class="woocommerce-PaymentMethod woocommerce-PaymentMethod--<?php echo esc_attr($column_id); ?> payment-method-<?php echo esc_attr($column_id); ?>"><span class="nobr"><?php echo esc_html($column_name); ?></span></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <?php foreach ($saved_methods as $type => $methods) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited    ?>
            <?php foreach ($methods as $method) : ?>
                <tr class="payment-method<?php echo!empty($method['is_default']) ? ' default-payment-method' : ''; ?>">
                    <?php foreach (wc_get_account_payment_methods_columns() as $column_id => $column_name) : ?>
                        <td class="woocommerce-PaymentMethod woocommerce-PaymentMethod--<?php echo esc_attr($column_id); ?> payment-method-<?php echo esc_attr($column_id); ?>" data-title="<?php echo esc_attr($column_name); ?>">
                            <?php
                            if (has_action('woocommerce_account_payment_methods_column_' . $column_id)) {
                                do_action('woocommerce_account_payment_methods_column_' . $column_id, $method);
                            } elseif ('method' === $column_id) {
                                if (!empty($method['method']['last4'])) {
                                    if (in_array($method['method']['gateway'], ['angelleye_ppcp', 'angelleye_ppcp_apple_pay'])) {
                                        $paymentMethod = $method['_angelleye_ppcp_used_payment_method'];
                                        // FIXME Check if there are any other payment methods in PPCP which will fall to this as we don't have fallback logic here
                                        if (in_array($paymentMethod, ['apple_pay', 'paypal', 'venmo'])) {
                                            $image_path = PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/icon/' . $paymentMethod . '.png';
                                            ?>
                                            <img class='ppcp_payment_method_icon' src='<?php echo $image_path; ?>' alt='<?php echo ucwords(str_replace('_', ' ', $paymentMethod)) ?>'><?php
                                            echo $paymentMethod == 'apple_pay' ? $ccEndingText($method) : '&nbsp;&nbsp;&nbsp;&nbsp;' . esc_html(wc_get_credit_card_type_label($method['method']['brand']));
                                        }
                                    } elseif ($method['method']['gateway'] === 'angelleye_ppcp_cc') {
                                        $brand = strtolower($method['method']['brand']);
                                        $brand = str_replace(['-', '_'], '', $brand);
                                        $icon_url = array(
                                            'visa' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/icon/credit-cards/visa.png',
                                            'amex' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/icon/credit-cards/amex.png',
                                            'diners' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/icon/credit-cards/diners.png',
                                            'discover' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/icon/credit-cards/discover.png',
                                            'jcb' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/icon/credit-cards/jcb.png',
                                            'laser' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/icon/credit-cards/laser.png',
                                            'maestro' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/icon/credit-cards/maestro.png',
                                            'mastercard' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/icon/credit-cards/mastercard.png'
                                        );
                                        if (isset($icon_url[$brand])) {
                                            echo sprintf('<img class="ppcp_payment_method_icon" src="%s" alt="Credit Card" />', $icon_url[$brand]);
                                        }
                                        echo $ccEndingText($method);
                                        do_action('angelleye_ppcp_display_deprecated_tag_myaccount', $method, $available_payment_gateways);
                                    } else {
                                        echo $ccEndingText($method);
                                        do_action('angelleye_ppcp_display_deprecated_tag_myaccount', $method, $available_payment_gateways);
                                    }
                                } else {
                                    echo esc_html(wc_get_credit_card_type_label($method['method']['brand']));
                                }
                            } elseif ('expires' === $column_id) {
                                echo $method['method']['gateway'] !== 'angelleye_ppcp' ? esc_html($method['expires']) : 'N/A';
                            } elseif ('actions' === $column_id) {
                                foreach ($method['actions'] as $key => $action) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                                    echo sprintf('<a href="%s" class="button %s">%s</a>', esc_url($action['url']), sanitize_html_class($key), esc_html($action['name']));
                                }
                            }
                            ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </table>

<?php else : ?>

    <p class="woocommerce-Message woocommerce-Message--info woocommerce-info"><?php esc_html_e('No saved methods found.', 'paypal-for-woocommerce'); ?></p>

<?php endif; ?>

<?php do_action('woocommerce_after_account_payment_methods', $has_methods); ?>

<?php if ($available_payment_gateways) : ?>
    <a class="button" href="<?php echo esc_url(wc_get_endpoint_url('add-payment-method')); ?>"><?php esc_html_e('Add payment method', 'paypal-for-woocommerce'); ?></a>
<?php endif; ?>
