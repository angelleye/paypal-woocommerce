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
        <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=express_checkout" class="nav-tab <?php echo $active_tab == 'general_settings' ? 'nav-tab-active' : ''; ?>"><?php echo __('General', $this->plugin_slug); ?></a>
        <a href="?page=<?php echo $this->plugin_slug; ?>&tab=tabs" class="nav-tab <?php echo $active_tab == 'tabs' ? 'nav-tab-active' : ''; ?>"><?php echo __('Tools', $this->plugin_slug); ?></a>
    </h2>

<?php if ($active_tab == 'general_settings') { ?>

        <h2 class="nav-tab-wrapper">
            <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=express_checkout" class="nav-tab <?php echo $gateway == 'express_checkout' ? 'nav-tab-active' : ''; ?>"><?php echo __('PayPal Express Checkout', $this->plugin_slug); ?></a>
            <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=payflow" class="nav-tab <?php echo $gateway == 'payflow' ? 'nav-tab-active' : ''; ?>"><?php echo __('PayPal Payments Pro (PayFlow)', $this->plugin_slug); ?></a>
            <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=dodirectpayment" class="nav-tab <?php echo $gateway == 'dodirectpayment' ? 'nav-tab-active' : ''; ?>"><?php echo __('PayPal Website Payments Pro (DoDirectPayment)', $this->plugin_slug); ?></a>
            <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=paypal_plus" class="nav-tab <?php echo $gateway == 'paypal_plus' ? 'nav-tab-active' : ''; ?>"><?php echo __('PayPal Plus', $this->plugin_slug); ?></a>
        </h2>

    <?php
    if ($gateway == 'express_checkout') {
        ?>
            <div class="wrap">
                <p><?php _e('PayPal Express Checkout is a more advanced version of the standard PayPal payment option that is included with WooCommerce. It has more features included with it and allows us to more tightly integrate PayPal into WooCommerce. It is the recommended method of enabling PayPal payments in WooCommerce.', $this->plugin_slug); ?></p>
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_gateway_paypal_express_angelleye'); ?>"><?php _e('Express Checkout Setting', $this->plugin_slug); ?></a>
            </div>
        <?php
    } elseif ($gateway == 'payflow') {
        ?>
            <div class="wrap">
                <p><?php _e('PayPal Payments Pro 2.0 is the latest release of PayPal’s Pro offering. It works on PayPal’s PayFlow Gateway as opposed to their original DoDirectPayment API. You need to be sure that your account is setup for this version of Pro before configuring this payment gateway or you will end up with errors when people attempt to pay you via credit card.', $this->plugin_slug); ?></p>
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_gateway_paypal_pro_payflow_angelleye'); ?>"><?php _e('PayPal Payments Pro (PayFlow) Setting', $this->plugin_slug); ?></a>
            </div>
        <?php
    } elseif ($gateway == 'dodirectpayment') {
        ?>
            <div class="wrap">
                <p><?php _e('PayPal’s Website Payments Pro 3.0 is the original Pro package that PayPal offered. It works on the DoDirectPayment API and is being slowly deprecated since the launch of Payments Pro 2.0 that works on the PayFlow API. You need to be sure that your account is setup for this version of Pro before configuring this payment gateway or you will end up with errors when people attempt to pay you via credit card.', $this->plugin_slug); ?></p>
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_gateway_paypal_pro_angelleye'); ?>"><?php _e('PayPal Website Payments Pro (DoDirectPayment) Setting', $this->plugin_slug); ?></a>
            </div>
        <?php
    } elseif ($gateway == 'paypal_plus') {
        ?>
            <div class="wrap">
                <p><?php _e('PayPal PLUS is a solution where PayPal offers PayPal, Credit Card and ELV as individual payment options on the payment selection page. The available payment methods are provided in a PayPal hosted iFrame.', $this->plugin_slug); ?></p>
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_gateway_paypal_plus_angelleye'); ?>"><?php _e('PayPal Plus Setting', $this->plugin_slug); ?></a>
            </div>
        <?php
    }
    ?>
    <?php } else { ?>
        <div class="wrap">
            <div class="angelleye-paypal-for-woocommerce-shipping-tools-wrap">
                <form id="woocommerce_paypal-for-woocommerce_options_form_bulk_tool_shipping" autocomplete="off" action="<?php echo admin_url('options-general.php?page=' . $this->plugin_slug . '&tab=tools'); ?>" method="post">
                    <a name="pfw-t1"></a>
                    <h3><?php echo __('Bulk Edit Tool for Products', $this->plugin_slug); ?></h3>
                    <div><?php echo __('Select from the options below to enable / disable No shipping required or PayPal Billing Agreement on multiple products at once.', $this->plugin_slug); ?></div>
                    <div class="angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section pfw-bulk-action-type">
                        <label for="pfw-bulk-action-type"><?php echo __('Action', $this->plugin_slug); ?></label>
                        <div>
                            <select name="pfw_bulk_action_type" id="pfw-bulk-action-type" required="required">
                                <option value=""><?php echo __('- Select option', $this->plugin_slug); ?></option>
                                <option value="enable_no_shipping"><?php echo __('Enable No shipping required', $this->plugin_slug); ?></option>
                                <option value="disable_no_shipping"><?php echo __('Disable No shipping required', $this->plugin_slug); ?></option>
                                <option value="enable_paypal_billing_agreement"><?php echo __('Enable PayPal Billing Agreement', $this->plugin_slug); ?></option>
                                <option value="disable_paypal_billing_agreement"><?php echo __('Disable PayPal Billing Agreement', $this->plugin_slug); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section pfw-bulk-action-target-type">
                        <label for="pfw-bulk-action-target-type"><?php echo __('Target', $this->plugin_slug); ?></label>
                        <div>
                            <select name="pfw_bulk_action_target_type" id="pfw-bulk-action-target-type" required="required">
                                <option value=""><?php echo __('- Select option', $this->plugin_slug); ?></option>
                                <option value="all"><?php echo __('All products', $this->plugin_slug); ?></option>
                                <option value="featured"><?php echo __('Featured products', $this->plugin_slug); ?></option>
                                <option value="where"><?php echo __('Where...', $this->plugin_slug); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section pfw-bulk-action-target-where-type angelleye-hidden">
                        <label for="pfw-bulk-action-target-where-type"><?php echo __('Where', $this->plugin_slug); ?></label>
                        <div>
                            <select name="pfw_bulk_action_target_where_type" id="pfw-bulk-action-target-where-type">
                                <option value=""><?php echo __('- Select option', $this->plugin_slug); ?></option>
                                <option value="category"><?php echo __('Category...', $this->plugin_slug); ?></option>
                                <option value="product_type"><?php echo __('Product type...', $this->plugin_slug); ?></option>
                                <option value="price_greater"><?php echo __('Price greater than...', $this->plugin_slug); ?></option>
                                <option value="price_less"><?php echo __('Price less than...', $this->plugin_slug); ?></option>
                                <option value="stock_greater"><?php echo __('Stock greater than...', $this->plugin_slug); ?></option>
                                <option value="stock_less"><?php echo __('Stock less than...', $this->plugin_slug); ?></option>
                                <option value="instock"><?php echo __('In-stock', $this->plugin_slug); ?></option>
                                <option value="outofstock"><?php echo __('Out-of-stock', $this->plugin_slug); ?></option>
                                <option value="sold_individually"><?php echo __('Sold individually', $this->plugin_slug); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="angelleye-paypal-for-woocommerce-shipping-tools-bulk-action-section pfw-bulk-action-target-where-category angelleye-hidden">
                        <label for="pfw-bulk-action-target-where-category"><?php echo __('Category', $this->plugin_slug); ?></label>
                        <div>
                            <select name="pfw_bulk_action_target_where_category" id="pfw-bulk-action-target-where-category">
                                <option value=""><?php echo __('- Select option', $this->plugin_slug); ?></option>
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
                                <option value=""><?php echo __('- Select option', $this->plugin_slug); ?></option>
                                <option value="simple"><?php echo __('Simple', $this->plugin_slug); ?></option>
                                <option value="variable"><?php echo __('Variable', $this->plugin_slug); ?></option>
                                <option value="grouped"><?php echo __('Grouped', $this->plugin_slug); ?></option>
                                <option value="external"><?php echo __('External', $this->plugin_slug); ?></option>
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
                            <button class="button button-primary" id="bulk-enable-tool-submit" name="bulk_enable_tool_submit"><?php echo __('Process', $this->plugin_slug); ?></button>
                        </div>
                    </div>
                    <div class="angelleye-offers-clearfix"></div>
                </form>
            </div>
        </div>
<?php } ?>
</div>