<div class="angelleye_ppcp_migration">
    <div class="angelleye_ppcp_migration_content p-50 p-md-30 mb-30 ">
        <div class="entry-content">
            <h2 class="text-center pb-30"><?php echo __('Your PayPal integration is due for a required update.', 'paypal-for-woocommerce'); ?></h2>
            <div class="d-flex d-md-flex">
                <div class="col-md-6">
                    <div class="pt-30">
                        <h4><?php echo __('Great new features and cost savings!', 'paypal-for-woocommerce'); ?></h4>
                    </div>
                    <div class="pt-15 font-bold">
                        <ul>
                            <li><?php echo __('PayPal Checkout', 'paypal-for-woocommerce'); ?></li>
                            <li><?php echo __('Venmo', 'paypal-for-woocommerce'); ?></li>
                            <li><?php echo __('Pay Later', 'paypal-for-woocommerce'); ?></li>
                            <li><?php echo __('Apple Pay', 'paypal-for-woocommerce'); ?></li>
                            <li><?php echo __('Full Support for Subscriptions / Token Payments', 'paypal-for-woocommerce'); ?></li>  
                            <li><?php echo __('Direct Credit Cards (No Login Required!)', 'paypal-for-woocommerce'); ?></li>
                            <li><?php echo __('Credit Card Fees Reduced to 2.69%!', 'paypal-for-woocommerce'); ?></li>
                            <li><?php echo __('Pro No Longer Required – No Monthly Fees!', 'paypal-for-woocommerce'); ?></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <img src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/woocommerce-payments-paypal-advanced-credit-card-checkout.png'; ?>" width="450" height="457" />
                </div>
            </div>
            <div class="pt-20 pb-20 d-flex d-md-flex">
                <div class="mr-auto">
                    <h3 class="pb-20"><?php echo __('Current PayPal Integration', 'paypal-for-woocommerce'); ?></h3>
                    <ul>
                        <li><?php echo __('Classic Express Checkout', 'paypal-for-woocommerce'); ?></li>
                        <li><?php echo __('PayPal Checkout | Venmo | Pay Later', 'paypal-for-woocommerce'); ?></li>
                        <li><?php echo __('Credit Cards via REST CC ($30/mo Fee)', 'paypal-for-woocommerce'); ?></li>
                        <li><?php echo __('Processing Fee of 2.9%', 'paypal-for-woocommerce'); ?></li>
                        <li><?php echo __('No Longer Officially Supported by PayPal', 'paypal-for-woocommerce'); ?></li>
                    </ul>
                </div>
                <div class="ml-auto">
                    <h3 class="pb-20"><?php echo __('Updated PayPal Integration', 'paypal-for-woocommerce'); ?></h3>
                    <ul>
                        <li><?php echo __('PayPal Complete Payments Platform', 'paypal-for-woocommerce'); ?></li>
                        <li><?php echo __('PayPal Checkout | Venmo | Pay Later', 'paypal-for-woocommerce'); ?></li>
                        <li><?php echo __('Direct Credit Card Processing – No Branding', 'paypal-for-woocommerce'); ?></li>
                        <li><strong><?php echo __('Total Fee of 2.69%¹', 'paypal-for-woocommerce'); ?></strong></li>
                        <li><strong><?php echo __('No Monthly Fee!', 'paypal-for-woocommerce'); ?></strong></li>
                        <li><?php echo __('Fully Supported by PayPal + Future Improvements', 'paypal-for-woocommerce'); ?></li>
                    </ul>
                </div>
            </div>
            <div class="pt-20 pb-20 text-center">
                <div class="angella_button_bg text-center paypal_woocommerce_product_onboard">
                    <?php echo $this->angelleye_ppcp_generate_onboard_button($products); ?>
                </div>
            </div>
            <div class="pt-20">
                <p><?php echo __('<strong>NOTE:</strong>' . $footer_note, 'paypal-for-woocommerce'); ?>
                </p>
            </div>
        </div>
    </div>
</div>
<?php include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/template/migration/ppcp_sidebar.php');