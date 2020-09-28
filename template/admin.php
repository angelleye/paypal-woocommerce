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
    <h2 class="nav-tab-wrapper">
        <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=paypal_payment_gateway_products" class="nav-tab <?php echo $active_tab == 'general_settings' ? 'nav-tab-active' : ''; ?>"><?php echo __('General', 'paypal-for-woocommerce'); ?></a>
        <a href="?page=<?php echo $this->plugin_slug; ?>&tab=tools" class="nav-tab <?php echo $active_tab == 'tools' ? 'nav-tab-active' : ''; ?>"><?php echo __('Tools', 'paypal-for-woocommerce'); ?></a>
        <?php do_action('angelleye_setting_main_menu_tab'); ?>
    </h2>
    <?php if ($active_tab == 'general_settings') { ?>
        <h2 class="nav-tab-wrapper">
            <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=paypal_payment_gateway_products" class="nav-tab <?php echo $gateway == 'paypal_payment_gateway_products' ? 'nav-tab-active' : ''; ?>"><?php echo __('PayPal Payment Gateway Products', 'paypal-for-woocommerce'); ?></a>
            <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=paypal_woocommerce_premium_extension" class="nav-tab <?php echo $gateway == 'paypal_woocommerce_premium_extension' ? 'nav-tab-active' : ''; ?>"><?php echo __('Premium Extensions / Support', 'paypal-for-woocommerce'); ?></a>
            <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=global" class="nav-tab <?php echo $gateway == 'global' ? 'nav-tab-active' : ''; ?>"><?php echo __('Global', 'paypal-for-woocommerce'); ?></a>
            <?php do_action('angelleye_paypal_for_woocommerce_general_settings_tab'); ?>
        </h2>
        <?php
        if ($gateway == 'paypal_payment_gateway_products') {
            ?>
            <div class="wrap angelleye_addons_wrap">
                <div id="angelleye_paypal_marketing_table">
                    <ul class="products">
                        <li class="product">
                            <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=paypal_express'); ?>">
                                <img src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/images/paypal-for-woo-gateway-logos/paypal-express-checkout-logo.png'; ?>">
                                <p><?php echo wp_trim_words(__('PayPal Express Checkout is a more advanced version of the standard PayPal payment option that is included with WooCommerce. It has more features included with it and allows us to more tightly integrate PayPal into WooCommerce. It is the recommended method of enabling PayPal payments in WooCommerce.', 'paypal-for-woocommerce'), $num_words = 40); ?></p>
                            </a>
                        </li>
                        <li class="product">
                            <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=paypal_pro_payflow'); ?>">
                                <img src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/images/paypal-for-woo-gateway-logos/paypal-payflow-pro-logo.png'; ?>">
                                <p><?php echo wp_trim_words(__('PayPal Payments Pro 2.0 is the latest release of PayPal’s Pro offering. It works on PayPal’s PayFlow Gateway as opposed to their original DoDirectPayment API. You need to be sure that your account is setup for this version of Pro before configuring this payment gateway or you will end up with errors when people attempt to pay you via credit card.', 'paypal-for-woocommerce'), $num_words = 48); ?></p>
                            </a>
                        </li>
                        <li class="product">
                            <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=paypal_pro'); ?>">
                                <img src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/images/paypal-for-woo-gateway-logos/paypal-payments-pro-logo.png'; ?>">
                                <p><?php echo wp_trim_words(__('PayPal’s Website Payments Pro 3.0 is the original Pro package that PayPal offered. It works on the DoDirectPayment API and is being slowly deprecated since the launch of Payments Pro 2.0 that works on the PayFlow API. You need to be sure that your account is setup for this version of Pro before configuring this payment gateway or you will end up with errors when people attempt to pay you via credit card.', 'paypal-for-woocommerce'), $num_words = 52); ?></p>
                            </a>
                        </li>
                        <li class="product">
                            <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=braintree'); ?>">
                                <img src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/images/paypal-for-woo-gateway-logos/paypal-braintree-logo.png'; ?>">
                                <p><?php echo wp_trim_words(__('Credit Card payments Powered by PayPal / Braintree. Checkout is seamless either via credit cards or PayPal, and customers can save a payment method to their account for future use or manage saved payment methods with a few clicks', 'paypal-for-woocommerce'), $num_words = 55); ?></p>
                            </a>
                        </li>
                        <li class="product">
                            <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=paypal_credit_card_rest'); ?>">
                                <img src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/images/paypal-for-woo-gateway-logos/paypal-rest-credit-cards-logo.png'; ?>">
                                <p><?php echo wp_trim_words(__('PayPal direct credit card payments using the REST API.  This allows you to accept credit cards directly on the site without the need for the full Payments Pro.', 'paypal-for-woocommerce'), $num_words = 55); ?></p>
                            </a>
                        </li>
                        <li class="product">
                            <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=paypal_advanced'); ?>">
                                <img src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/images/paypal-for-woo-gateway-logos/paypal-payments-advanced-logo.png'; ?>">
                                <p><?php echo wp_trim_words(__('PayPal Payments Advanced uses an iframe to seamlessly integrate PayPal hosted pages into the checkout process.', 'paypal-for-woocommerce'), $num_words = 55); ?></p>
                            </a>
                        </li>
                        <li class="product">
                            <a target="_blank" href="https://www.angelleye.com/product/woocommerce-paypal-plus-plugin">
                                <img src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/images/paypal-for-woo-gateway-logos/paypal-plus-logo.png'; ?>">
                                <p>
                                    <?php echo __('PayPal Plus is a solution where PayPal offers PayPal, Credit Card and ELV as individual payment options on the payment selection page. The available payment methods are provided in a PayPal hosted iFrame.', 'paypal-for-woocommerce'); ?><br/><br/>
                                    <?php echo __('PayPal Plus is a solution where PayPal offers PayPal, Credit Card and ELV as individual payment options on the payment selection page. The available payment methods are provided in a PayPal hosted iFrame.', 'paypal-for-woocommerce'); ?><br/><br/>
                                    <?php echo __('PayPal Plus is a solution where PayPal offers PayPal, Credit Card and ELV as individual payment options on the payment selection page. The available payment methods are provided in a PayPal hosted iFrame.', 'paypal-for-woocommerce'); ?>
                                </p>
                            </a>
                        </li>

                    </ul>
                </div>
                <?php AngellEYE_Utility::angelleye_display_marketing_sidebar($id = 'admin_setting'); ?>
            </div>
            <?php
        } elseif ($gateway == 'paypal_woocommerce_premium_extension') {
            if (false === ( $addons = get_transient('angelleye_addons_data_paypal_woocommerce_premium_extension') )) {
                $addons_json = wp_remote_get('https://www.angelleye.com/web-services/woocommerce/api/getinfo.php?tag=paypal_woocommerce_premium_extension', array('timeout' => 120));
                if (!is_wp_error($addons_json)) {
                    $addons = json_decode(wp_remote_retrieve_body($addons_json));
                    if ($addons) {
                        set_transient('angelleye_addons_data_paypal_woocommerce_premium_extension', $addons, HOUR_IN_SECONDS);
                    }
                }
            }
            if (isset($addons) && !empty($addons)) {
                ?>
                <div class="wrap angelleye_addons_wrap paypal_woocommerce_premium_extension">
                    <div id="angelleye_paypal_marketing_table">
                        <ul class="products">
                            <?php
                            foreach ($addons as $addon) {
                                echo '<li class="product">';
                                echo '<a target="_blank" href="' . $addon->permalink . '">';
                                if (isset($addon->price) && !empty($addon->price)) {
                                    echo '<span class="price">' . $addon->price . '</span>';
                                }
                                $images = (!empty($addon->images[0]->src) ) ? $addon->images[0]->src : '';
                                if (!empty($images)) {
                                    echo "<img src='$images'>";
                                }
                                $description = (!empty($addon->short_description)) ? $addon->short_description : $addon->description;
                                if (isset($description) && !empty($description)) {
                                    $description = strip_tags($description);
                                    echo '<p>' . wp_trim_words($description, $num_words = 55) . '</p>';
                                }
                                echo '</a>';
                                echo '</li>';
                            }
                            ?>
                        </ul>
                    </div>
                    <?php AngellEYE_Utility::angelleye_display_marketing_sidebar($id = 'admin_setting'); ?>
                </div>
                <?php
            } else {
                echo '<p>Premium extension available at <a target="_blank" href="https://www.angelleye.com/store/?utm_source=paypal_woocommerce_premium_extension&utm_medium=premium_extensions">www.angelleye.com</a></p>';
            }
        } elseif ($gateway == 'global') {
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
                <?php AngellEYE_Utility::angelleye_display_marketing_sidebar($id = 'admin_setting'); ?>
            </div>
            <?php
        } else {
            do_action('angelleye_paypal_for_woocommerce_general_settings_tab_content');
        }
        ?>
    <?php } elseif ($_GET['tab'] == 'tools') {
        ?>
        <div class="wrap">
            <div id="angelleye_paypal_marketing_table">
                <div class="angelleye-paypal-for-woocommerce-shipping-tools-wrap">
                    <form id="woocommerce_paypal-for-woocommerce_options_form_bulk_tool_shipping" autocomplete="off" action="<?php echo admin_url('options-general.php?page=' . $this->plugin_slug . '&tab=tools'); ?>" method="post">
                        <a name="pfw-t1"></a>
                        <h3><?php echo __('Bulk Edit Tool for Products', 'paypal-for-woocommerce'); ?></h3>
                        <div><?php echo __('Use the options below to enable/disable product-level settings for all products at once or a filtered sub-set of products.', 'paypal-for-woocommerce'); ?></div>
                        <div class="angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section pfw-bulk-action-type">
                            <label for="pfw-bulk-action-type"><?php echo __('Action', 'paypal-for-woocommerce'); ?></label>
                            <div>
                                <select name="pfw_bulk_action_type" id="pfw-bulk-action-type" required="required">
                                    <option value=""><?php echo __('- Select...', 'paypal-for-woocommerce'); ?></option>
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
                                    <option value=""><?php echo __('- Select...', 'paypal-for-woocommerce'); ?></option>
                                    <option value="Sale"><?php echo __('Sale', 'paypal-for-woocommerce'); ?></option>
                                    <option value="Authorization"><?php echo __('Authorization', 'paypal-for-woocommerce'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section pfw-bulk-action-payment-authorization-type">
                            <label for="pfw-bulk-action-payment-authorization-type"><?php echo __('Authorization Type', 'paypal-for-woocommerce'); ?></label>
                            <div>
                                <select name="pfw-bulk-action-payment-authorization-type" id="pfw-bulk-action-payment-authorization-type">
                                    <option value=""><?php echo __('- Select...', 'paypal-for-woocommerce'); ?></option>
                                    <option value="Full Authorization"><?php echo __('Full Authorization', 'paypal-for-woocommerce'); ?></option>
                                    <option value="Card Verification"><?php echo __('Card Verification', 'paypal-for-woocommerce'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section pfw-bulk-action-target-type">
                            <label for="pfw-bulk-action-target-type"><?php echo __('Target', 'paypal-for-woocommerce'); ?></label>
                            <div>
                                <select name="pfw_bulk_action_target_type" id="pfw-bulk-action-target-type" required="required">
                                    <option value=""><?php echo __('- Select...', 'paypal-for-woocommerce'); ?></option>
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
            </div>
            <?php AngellEYE_Utility::angelleye_display_marketing_sidebar($id = 'admin_setting'); ?>
        </div>
    <?php } ?>
</div>