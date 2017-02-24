<?php
/**
 * PayPal for WooCommerce - Settings
 */
?>
<?php
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general_settings';
$gateway = isset($_GET['gateway']) ? $_GET['gateway'] : 'express_checkout';
?>

<div class="wrap">
    <h2><?php echo esc_html(get_admin_page_title()); ?></h2>
    <h2 class="nav-tab-wrapper">
        <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=express_checkout" class="nav-tab <?php echo $active_tab == 'general_settings' ? 'nav-tab-active' : ''; ?>"><?php echo __('General', 'paypal-for-woocommerce'); ?></a>
        <a href="?page=<?php echo $this->plugin_slug; ?>&tab=tabs" class="nav-tab <?php echo $active_tab == 'tabs' ? 'nav-tab-active' : ''; ?>"><?php echo __('Tools', 'paypal-for-woocommerce'); ?></a>
    </h2>

<?php if ($active_tab == 'general_settings') { ?>

        <h2 class="nav-tab-wrapper">
            <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=express_checkout" class="nav-tab <?php echo $gateway == 'express_checkout' ? 'nav-tab-active' : ''; ?>"><?php echo __('PayPal Express Checkout', 'paypal-for-woocommerce'); ?></a>
            <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=payflow" class="nav-tab <?php echo $gateway == 'payflow' ? 'nav-tab-active' : ''; ?>"><?php echo __('PayPal Payments Pro (PayFlow)', 'paypal-for-woocommerce'); ?></a>
            <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=dodirectpayment" class="nav-tab <?php echo $gateway == 'dodirectpayment' ? 'nav-tab-active' : ''; ?>"><?php echo __('PayPal Website Payments Pro (DoDirectPayment)', 'paypal-for-woocommerce'); ?></a>
            <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=paypal_plus" class="nav-tab <?php echo $gateway == 'paypal_plus' ? 'nav-tab-active' : ''; ?>"><?php echo __('PayPal Plus', 'paypal-for-woocommerce'); ?></a>
            <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=wc_gateway_braintree_angelleye" class="nav-tab <?php echo $gateway == 'wc_gateway_braintree_angelleye' ? 'nav-tab-active' : ''; ?>"><?php echo __('Braintree', 'paypal-for-woocommerce'); ?></a>
            <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=wc_gateway_paypal_advanced_angelleye" class="nav-tab <?php echo $gateway == 'wc_gateway_paypal_advanced_angelleye' ? 'nav-tab-active' : ''; ?>"><?php echo __('PayPal Advanced', 'paypal-for-woocommerce'); ?></a>
            <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=paypal_credit_card_rest" class="nav-tab <?php echo $gateway == 'paypal_credit_card_rest' ? 'nav-tab-active' : ''; ?>"><?php echo __('PayPal Credit Card (REST)', 'paypal-for-woocommerce'); ?></a>
            <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=global" class="nav-tab <?php echo $gateway == 'global' ? 'nav-tab-active' : ''; ?>"><?php echo __('Global', 'paypal-for-woocommerce'); ?></a>
        </h2>

    <?php
    if ($gateway == 'express_checkout') {
        ?>
            <div class="wrap">
                <p><?php _e('PayPal Express Checkout is a more advanced version of the standard PayPal payment option that is included with WooCommerce. It has more features included with it and allows us to more tightly integrate PayPal into WooCommerce. It is the recommended method of enabling PayPal payments in WooCommerce.', 'paypal-for-woocommerce'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_gateway_paypal_express_angelleye'); ?>"><?php _e('Express Checkout Setting', 'paypal-for-woocommerce'); ?></a>
            </div>
        <?php
    } elseif ($gateway == 'payflow') {
        ?>
            <div class="wrap">
                <p><?php _e('PayPal Payments Pro 2.0 is the latest release of PayPal’s Pro offering. It works on PayPal’s PayFlow Gateway as opposed to their original DoDirectPayment API. You need to be sure that your account is setup for this version of Pro before configuring this payment gateway or you will end up with errors when people attempt to pay you via credit card.', 'paypal-for-woocommerce'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_gateway_paypal_pro_payflow_angelleye'); ?>"><?php _e('PayPal Payments Pro (PayFlow) Setting', 'paypal-for-woocommerce'); ?></a>
            </div>
        <?php
    } elseif ($gateway == 'dodirectpayment') {
        ?>
            <div class="wrap">
                <p><?php _e('PayPal’s Website Payments Pro 3.0 is the original Pro package that PayPal offered. It works on the DoDirectPayment API and is being slowly deprecated since the launch of Payments Pro 2.0 that works on the PayFlow API. You need to be sure that your account is setup for this version of Pro before configuring this payment gateway or you will end up with errors when people attempt to pay you via credit card.', 'paypal-for-woocommerce'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_gateway_paypal_pro_angelleye'); ?>"><?php _e('PayPal Website Payments Pro (DoDirectPayment) Setting', 'paypal-for-woocommerce'); ?></a>
            </div>
        <?php
    } elseif ($gateway == 'paypal_plus') {
        ?>
            <div class="wrap">
                <p><?php _e('PayPal Plus is a solution where PayPal offers PayPal, Credit Card and ELV as individual payment options on the payment selection page. The available payment methods are provided in a PayPal hosted iFrame.', 'paypal-for-woocommerce'); ?></p>
                <p><?php _e('PayPal Plus is designed for non-U.S. based PayPal accounts, and because of this, PayPal does not support us the way they do with other PayPal products.  As such, we were forced to move PayPal Plus to its own paid plugin separate from this one.', 'paypal-for-woocommerce'); ?></p>
                <p><?php _e('This will make it possible for us to dedicate resources to PayPal Plus in order to maintain and support it thoroughly.', 'paypal-for-woocommerce'); ?></p>
                <p><a target="_blank" href="https://www.angelleye.com/product/woocommerce-paypal-plus-plugin">Get the PayPal Plus Plugin!</a></p>
            </div>
        <?php
    } elseif ($gateway == 'wc_gateway_braintree_angelleye') {
        ?>
            <div class="wrap">
                <p><?php _e('Credit Card payments Powered by PayPal / Braintree.', 'paypal-for-woocommerce'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_gateway_braintree_angelleye'); ?>"><?php _e('Braintree Setting', 'paypal-for-woocommerce'); ?></a>
            </div>
        <?php
    } elseif ($gateway == 'wc_gateway_paypal_advanced_angelleye') {
        ?>
            <div class="wrap">
                <p><?php _e('PayPal Payments Advanced uses an iframe to seamlessly integrate PayPal hosted pages into the checkout process.', 'paypal-for-woocommerce'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_gateway_paypal_advanced_angelleye'); ?>"><?php _e('PayPal Advanced Setting', 'paypal-for-woocommerce'); ?></a>
            </div>
        <?php
    } elseif ($gateway == 'paypal_credit_card_rest') {
        ?>
            <div class="wrap">
                <p><?php _e('PayPal direct credit card payments using the REST API.  This allows you to accept credit cards directly on the site without the need for the full Payments Pro.', 'paypal-for-woocommerce'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_gateway_paypal_credit_card_rest_angelleye'); ?>"><?php _e('PayPal Credit Card (REST) Setting', 'paypal-for-woocommerce'); ?></a>
            </div>
        <?php
        } elseif ($gateway == 'global') {
            ?>
            <div class="wrap">
                <?php
                if (isset($_POST['submit']) && !empty($_POST['submit'])) {
                    update_option('Force_tls_one_point_two', (isset($_POST['Force_tls_one_point_two']) && wc_clean($_POST['Force_tls_one_point_two']) == 'on') ? 'yes' : 'no' );
                    echo '<div class="updated"><p>' . __('Settings were saved successfully.', 'paypal-for-woocommerce') . '</p></div>';
                }
                $Force_tls_one_point_two = get_option('Force_tls_one_point_two');
                ?>
                <form method="post">
                    <table class="form-table">
                        <tbody>
                            <tr valign="top" class="">
                                <th class="titledesc" scope="row"><?php echo __('Force TLS 1.2', 'paypal-for-woocommerce'); ?></th>
                                <td class="forminp forminp-checkbox">
                                    <fieldset>
                                        <label for="Force_tls_one_point_two">
                                            <input type="checkbox" <?php echo (isset($Force_tls_one_point_two) && $Force_tls_one_point_two == 'yes') ? 'checked="checked"' : '' ?> class="" id="Force_tls_one_point_two" name="Force_tls_one_point_two"> 
                                            <?php echo __('Enable Force TLS 1.2', 'paypal-for-woocommerce'); ?>					
                                        </label> 														
                                    </fieldset>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <?php submit_button(); ?>
                </form>

            </div>
            <?php
    }
    ?>
    <?php } else { 
            if ( !empty( $_GET['_paypal_for_woocommerce_remove_log_file'] ) || wp_verify_nonce( $_GET['_paypal_for_woocommerce_remove_log_file'], 'paypal-for-woocommerce-remove-log-file' ) ) {
                   $paypal_for_woocommerce_logfile_path = array('paypal_credit_card_rest' => wc_get_log_file_path('paypal_credit_card_rest'),
                    'braintree' => wc_get_log_file_path( 'braintree' ),
                    'paypal_advanced' => wc_get_log_file_path( 'paypal_advanced' ),
                    'paypal_express' => wc_get_log_file_path( 'paypal_express' ),
                    'paypal-pro' => wc_get_log_file_path( 'paypal-pro' ),
                    'paypal_payflow' => wc_get_log_file_path( 'paypal_payflow' ) 
                );
                $count = 0;
                foreach ($paypal_for_woocommerce_logfile_path as $key => $value) {
                    if ( file_exists( $value ) ) {
                        unlink( $value );
                        $count = $count + 1;
                    }
                }
                $remove_logfile_tool_url = remove_query_arg( '_paypal_for_woocommerce_remove_log_file' );
                wp_redirect( esc_url_raw( add_query_arg( 'count', $count, $remove_logfile_tool_url ) ) );
                exit;
            }
            if( !empty($_GET['count']) && $_GET['count'] > 0) {
                 echo '<div class="updated"><p>' . __( 'Logfile has been successfully deleted.', 'paypal-for-woocommerce' ) . '</p></div>';
            }
            
        ?>
        <div class="wrap">
            <div class="angelleye-paypal-for-woocommerce-shipping-tools-wrap">
                <form id="woocommerce_paypal-for-woocommerce_options_form_bulk_tool_shipping" autocomplete="off" action="<?php echo admin_url('options-general.php?page=' . $this->plugin_slug . '&tab=tools'); ?>" method="post">
                    <a name="pfw-t1"></a>
                    <h3><?php echo __('Bulk Edit Tool for Products', 'paypal-for-woocommerce'); ?></h3>
                    <div><?php echo __('Select from the options below to enable / disable No shipping required or PayPal Billing Agreement on multiple products at once.', 'paypal-for-woocommerce'); ?></div>
                    <div class="angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section pfw-bulk-action-type">
                        <label for="pfw-bulk-action-type"><?php echo __('Action', 'paypal-for-woocommerce'); ?></label>
                        <div>
                            <select name="pfw_bulk_action_type" id="pfw-bulk-action-type" required="required">
                                <option value=""><?php echo __('- Select option', 'paypal-for-woocommerce'); ?></option>
                                <option value="enable_no_shipping"><?php echo __('Enable No shipping required', 'paypal-for-woocommerce'); ?></option>
                                <option value="disable_no_shipping"><?php echo __('Disable No shipping required', 'paypal-for-woocommerce'); ?></option>
                                <option value="enable_paypal_billing_agreement"><?php echo __('Enable PayPal Billing Agreement', 'paypal-for-woocommerce'); ?></option>
                                <option value="disable_paypal_billing_agreement"><?php echo __('Disable PayPal Billing Agreement', 'paypal-for-woocommerce'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section pfw-bulk-action-target-type">
                        <label for="pfw-bulk-action-target-type"><?php echo __('Target', 'paypal-for-woocommerce'); ?></label>
                        <div>
                            <select name="pfw_bulk_action_target_type" id="pfw-bulk-action-target-type" required="required">
                                <option value=""><?php echo __('- Select option', 'paypal-for-woocommerce'); ?></option>
                                <option value="all"><?php echo __('All products', 'paypal-for-woocommerce'); ?></option>
                                <option value="all_downloadable"><?php echo __('All downloadable', 'paypal-for-woocommerce'); ?></option>
                                <option value="all_virtual"><?php echo __('All virtual', 'paypal-for-woocommerce'); ?></option>
                                <option value="featured"><?php echo __('Featured products', 'paypal-for-woocommerce'); ?></option>
                                <option value="where"><?php echo __('Where...', 'paypal-for-woocommerce'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section pfw-bulk-action-target-where-type angelleye-hidden">
                        <label for="pfw-bulk-action-target-where-type"><?php echo __('Where', 'paypal-for-woocommerce'); ?></label>
                        <div>
                            <select name="pfw_bulk_action_target_where_type" id="pfw-bulk-action-target-where-type">
                                <option value=""><?php echo __('- Select option', 'paypal-for-woocommerce'); ?></option>
                                <option value="category"><?php echo __('Category...', 'paypal-for-woocommerce'); ?></option>
                                <option value="product_type"><?php echo __('Product type...', 'paypal-for-woocommerce'); ?></option>
                                <option value="price_greater"><?php echo __('Price greater than...', 'paypal-for-woocommerce'); ?></option>
                                <option value="price_less"><?php echo __('Price less than...', 'paypal-for-woocommerce'); ?></option>
                                <option value="stock_greater"><?php echo __('Stock greater than...', 'paypal-for-woocommerce'); ?></option>
                                <option value="stock_less"><?php echo __('Stock less than...', 'paypal-for-woocommerce'); ?></option>
                                <option value="instock"><?php echo __('In-stock', 'paypal-for-woocommerce'); ?></option>
                                <option value="outofstock"><?php echo __('Out-of-stock', 'paypal-for-woocommerce'); ?></option>
                                <option value="sold_individually"><?php echo __('Sold individually', 'paypal-for-woocommerce'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section pfw-bulk-action-target-where-category angelleye-hidden">
                        <label for="pfw-bulk-action-target-where-category"><?php echo __('Category', 'paypal-for-woocommerce'); ?></label>
                        <div>
                            <select name="pfw_bulk_action_target_where_category" id="pfw-bulk-action-target-where-category">
                                <option value=""><?php echo __('- Select option', 'paypal-for-woocommerce'); ?></option>
                                <?php
                                if($product_cats) {
                                    foreach($product_cats as $cat) {
                                        echo '<option value="'.$cat->slug.'">'.$cat->cat_name.'</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section pfw-bulk-action-target-where-product-type angelleye-hidden">
                        <label for="pfw-bulk-action-target-where-product-type">Product type</label>
                        <div>
                            <select name="pfw_bulk_action_target_where_product_type" id="pfw-bulk-action-target-where-product-type">
                                <option value=""><?php echo __('- Select option', 'paypal-for-woocommerce'); ?></option>
                                <option value="simple"><?php echo __('Simple', 'paypal-for-woocommerce'); ?></option>
                                <option value="variable"><?php echo __('Variable', 'paypal-for-woocommerce'); ?></option>
                                <option value="grouped"><?php echo __('Grouped', 'paypal-for-woocommerce'); ?></option>
                                <option value="external"><?php echo __('External', 'paypal-for-woocommerce'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section pfw-bulk-action-target-where-price-value angelleye-hidden">
                        <label for="pfw-bulk-action-target-where-price-value"></label>
                        <div>
                            <input type="text" name="pfw_bulk_action_target_where_price_value" id="pfw-bulk-action-target-where-price-value">
                        </div>
                    </div>
                    <div class="angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section pfw-bulk-action-target-where-stock-value angelleye-hidden">
                        <label for="pfw-bulk-action-target-where-stock-value"></label>
                        <div>
                            <input type="text" name="pfw_bulk_action_target_where_stock_value" id="pfw-bulk-action-target-where-stock-value">
                        </div>
                    </div>
                    <div class="angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section">
                        <label for="pfw-bulk-action-target-where-stock-value"></label>
                        <div>
                            <button class="button button-primary" id="bulk-enable-tool-submit" name="bulk_enable_tool_submit"><?php echo __('Process', 'paypal-for-woocommerce'); ?></button>
                        </div>
                    </div>
                    <div class="angelleye-offers-clearfix"></div>
                </form>
            </div>
        </div>
        <div class="wrap">
            <div class="angelleye-paypal-for-woocommerce-shipping-tools-wrap">
                <div><br></div>
                <p><a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'paypal_for_woocommerce_remove_log_file' ), 'paypal-for-woocommerce-remove-log-file', '_paypal_for_woocommerce_remove_log_file' ) ); ?>" class="delete_template button"><?php _e( 'Clear All Log Files', 'paypal-for-woocommerce' ); ?></a><span><?php echo str_repeat('&nbsp;', 3); echo __( 'This will remove all the log file of PayPal for WooCoomerce plugin.', 'paypal-for-woocommerce' ); ?> </span></p>
            </div>
        </div>
        <?php 
        wc_enqueue_js( "jQuery( 'a.delete_template' ).click( function() {
                if ( window.confirm('" . esc_js( __( 'Are you sure you want to delete all the logfile of PayPal for WooCoomerce plugin?', 'paypal-for-woocommerce' ) ) . "') ) {
                        return true;
                }

                return false;
        });");
        ?>
<?php } ?>
</div>