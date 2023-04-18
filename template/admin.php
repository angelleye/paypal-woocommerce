<?php
/**
 * PayPal for WooCommerce - Settings
 */
?>
<?php
$active_tab = isset($_GET['tab']) ? wc_clean($_GET['tab']) : 'general_settings';
$gateway = isset($_GET['gateway']) ? wc_clean($_GET['gateway']) : 'paypal_payment_gateway_products';
?>
<div class="wrap">

    <h2><?php echo esc_html(get_admin_page_title()); ?></h2>
    <br>
    <?php if ($active_tab == 'general_settings') { ?>
        <h2 class="nav-tab-wrapper">
            <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=paypal_payment_gateway_products" class="nav-tab <?php echo $gateway == 'paypal_payment_gateway_products' ? 'nav-tab-active' : ''; ?>"><?php echo __('PayPal Commerce - Built by Angelleye', 'paypal-for-woocommerce'); ?></a>
            <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=tool" class="nav-tab <?php echo $gateway == 'tool' ? 'nav-tab-active' : ''; ?>"><?php echo __('Tools', 'paypal-for-woocommerce'); ?></a>
            <?php do_action('angelleye_paypal_for_woocommerce_general_settings_tab'); ?>
        </h2>
        <?php
        if ($gateway == 'paypal_payment_gateway_products') {
            if (!class_exists('AngellEYE_PayPal_PPCP_Admin_Onboarding')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-admin-onboarding.php';
            }
            $admin_onboarding = AngellEYE_PayPal_PPCP_Admin_Onboarding::instance();
            ?>
            <div class="wrap angelleye_addons_wrap">
                <?php
                $admin_onboarding->view();
                ?>
            </div>
        <?php
        } elseif ($gateway == 'tool') {
            ?>
            <div class="wrap">
                <?php
                if (isset($_POST['submit']) && !empty($_POST['submit'])) {
                    update_option('Force_tls_one_point_two', (isset($_POST['Force_tls_one_point_two']) && wc_clean($_POST['Force_tls_one_point_two']) == 'on') ? 'yes' : 'no' );
                    update_option('pfw_hide_frontend_mark', (isset($_POST['pfw_hide_frontend_mark']) && wc_clean($_POST['pfw_hide_frontend_mark']) == 'on') ? 'yes' : 'no' );
                    update_option('change_proceed_checkout_button_text', isset($_POST['change_proceed_checkout_button_text']) ? wc_clean($_POST['change_proceed_checkout_button_text']) : '' );
                    echo '<div class="updated"><p>' . __('Settings were saved successfully.', 'paypal-for-woocommerce') . '</p></div>';
                }
                $Force_tls_one_point_two = get_option('Force_tls_one_point_two');
                $change_proceed_checkout_button_text = get_option('change_proceed_checkout_button_text');
                $pfw_hide_frontend_mark = get_option('pfw_hide_frontend_mark');
                ?>
                <div id="angelleye_paypal_marketing_table">
                    <div class="angelleye-paypal-for-woocommerce-shipping-tools-wrap">
                        <form id="woocommerce_paypal-for-woocommerce_options_form_bulk_tool_shipping" autocomplete="off" action="<?php echo admin_url('options-general.php?page=' . $this->plugin_slug . '&tab=general_settings&gateway=tool'); ?>" method="post">
                            <a name="pfw-t1"></a>
                            <h3><?php echo __('Bulk Edit Tool for Products', 'paypal-for-woocommerce'); ?></h3>
                            <div><?php echo __('Use the options below to enable/disable product-level settings for all products at once or a filtered sub-set of products.', 'paypal-for-woocommerce'); ?></div>
                            <div class="angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section pfw-bulk-action-type">
                                <label for="pfw-bulk-action-type"><?php echo __('Action', 'paypal-for-woocommerce'); ?></label>
                                <div>
                                    <select name="pfw_bulk_action_type" id="pfw-bulk-action-type" required="required">
                                        <option value=""><?php echo __('Select', 'paypal-for-woocommerce'); ?></option>
                                        <option value="enable_no_shipping"><?php echo __('Enable No Shipping Required', 'paypal-for-woocommerce'); ?></option>
                                        <option value="disable_no_shipping"><?php echo __('Disable No Shipping Required', 'paypal-for-woocommerce'); ?></option>
                                        <option value="enable_paypal_billing_agreement"><?php echo __('Enable PayPal Billing Agreement', 'paypal-for-woocommerce'); ?></option>
                                        <option value="disable_paypal_billing_agreement"><?php echo __('Disable PayPal Billing Agreement', 'paypal-for-woocommerce'); ?></option>
                                        <option value="enable_express_checkout_button"><?php echo __('Enable Express Checkout Button', 'paypal-for-woocommerce'); ?></option>
                                        <option value="disable_express_checkout_button"><?php echo __('Disable Express Checkout Button', 'paypal-for-woocommerce'); ?></option>
                                        <option value="enable_sandbox_mode"><?php echo __('Enable Sandbox Mode', 'paypal-for-woocommerce'); ?></option>
                                        <option value="disable_sandbox_mode"><?php echo __('Disable Sandbox Mode', 'paypal-for-woocommerce'); ?></option>
                                        <option value="enable_payment_action"><?php echo __('Enable Payment Action', 'paypal-for-woocommerce'); ?></option>
                                        <option value="disable_payment_action"><?php echo __('Disable Payment Action', 'paypal-for-woocommerce'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section pfw-bulk-action-payment-action-type">
                                <label for="pfw-bulk-action-payment-action-type"><?php echo __('Payment Action', 'paypal-for-woocommerce'); ?></label>
                                <div>
                                    <select name="pfw-bulk-action-payment-action-type" id="pfw-bulk-action-payment-action-type">
                                        <option value=""><?php echo __('Select', 'paypal-for-woocommerce'); ?></option>
                                        <option value="Sale"><?php echo __('Sale', 'paypal-for-woocommerce'); ?></option>
                                        <option value="Authorization"><?php echo __('Authorization', 'paypal-for-woocommerce'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section pfw-bulk-action-payment-authorization-type">
                                <label for="pfw-bulk-action-payment-authorization-type"><?php echo __('Authorization Type', 'paypal-for-woocommerce'); ?></label>
                                <div>
                                    <select name="pfw-bulk-action-payment-authorization-type" id="pfw-bulk-action-payment-authorization-type">
                                        <option value=""><?php echo __('Select', 'paypal-for-woocommerce'); ?></option>
                                        <option value="Full Authorization"><?php echo __('Full Authorization', 'paypal-for-woocommerce'); ?></option>
                                        <option value="Card Verification"><?php echo __('Card Verification', 'paypal-for-woocommerce'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section pfw-bulk-action-target-type">
                                <label for="pfw-bulk-action-target-type"><?php echo __('Target', 'paypal-for-woocommerce'); ?></label>
                                <div>
                                    <select name="pfw_bulk_action_target_type" id="pfw-bulk-action-target-type" required="required">
                                        <option value=""><?php echo __('Select', 'paypal-for-woocommerce'); ?></option>
                                        <option value="all"><?php echo __('All Products', 'paypal-for-woocommerce'); ?></option>
                                        <option value="all_downloadable"><?php echo __('All Downloadable Products', 'paypal-for-woocommerce'); ?></option>
                                        <option value="all_virtual"><?php echo __('All Virtual Products', 'paypal-for-woocommerce'); ?></option>
                                        <option value="featured"><?php echo __('Featured Products', 'paypal-for-woocommerce'); ?></option>
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
                                        if ($product_cats) {
                                            foreach ($product_cats as $cat) {
                                                echo '<option value="' . $cat->slug . '">' . $cat->cat_name . '</option>';
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
                    <div class="angelleye-paypal-for-woocommerce-shipping-tools-wrap">
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
                                    <tr valign="top" class="">
                                        <th class="titledesc" scope="row"><?php echo __('Checkout Button Text', 'paypal-for-woocommerce'); ?></th>
                                        <td class="forminp forminp-checkbox">
                                            <fieldset>
                                                <div>
                                                    <input type="text" class="regular-text" name="change_proceed_checkout_button_text" value="<?php echo $change_proceed_checkout_button_text; ?>"><span><br/><?php echo __('Set a value here to override the "Proceed to Checkout" text displayed on the WooCommerce cart page.', 'paypal-for-woocommerce'); ?></span>
                                                </div> 														
                                            </fieldset>
                                        </td>
                                    </tr>
                                    <tr valign="top" class="">
                                        <th class="titledesc" scope="row"><?php echo __('Hide Version Tag', 'paypal-for-woocommerce'); ?></th>
                                        <td class="forminp forminp-checkbox">
                                            <fieldset>
                                                <label for="pfw_hide_frontend_mark">
                                                    <input name="pfw_hide_frontend_mark" id="pfw_hide_frontend_mark" type="checkbox" <?php echo (isset($pfw_hide_frontend_mark) && $pfw_hide_frontend_mark == 'yes') ? 'checked="checked"' : '' ?>> 
                                                    <?php echo __('Hide plugin version tag from front end source code.', 'paypal-for-woocommerce'); ?>
                                                </label>
                                                <p class="description"><?php echo __('Removes the PayPal for WooCommerce plugin version from front end source code.', 'paypal-for-woocommerce'); ?></p>																
                                            </fieldset>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <?php submit_button(); ?>
                        </form>
                    </div>

                </div>
                <?php AngellEYE_Utility::angelleye_display_marketing_sidebar($id = 'admin_setting'); ?>
            </div>
            <?php
        } else {
            do_action('angelleye_paypal_for_woocommerce_general_settings_tab_content');
        }
    }
    ?>
</div>