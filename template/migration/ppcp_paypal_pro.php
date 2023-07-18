<div class="angelleye_ppcp_migration">
    <div class="angelleye_ppcp_migration_content p-50 p-md-30 mb-30 ">
        <div class="entry-content">
            <h2 class="text-center pb-30"><?php echo __('Your PayPal integration is due for a required update.', 'paypal-for-woocommerce'); ?></h2>
            <div class="d-flex d-md-flex">
                <div class="col-md-6">
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
                        <li><?php echo __('Credit Cards via PayPal Pro ($30/mo Fee)', 'paypal-for-woocommerce'); ?></li>
                        <li><?php echo __('Processing Fee of 2.9%', 'paypal-for-woocommerce'); ?></li>
                        <li><?php echo __('No Longer Officially Supported by PayPal', 'paypal-for-woocommerce'); ?></li>
                    </ul>
                    <div>
                        <p><strong><?php echo __('PayPal Payments', 'paypal-for-woocommerce'); ?></strong></p>
                        <p>We notice that you do not have Express Checkout<br> 
                            enabled.&nbsp; By updating you still have the option to leave<br> 
                            PayPal buttons disabled and only use Direct Credit Card<br> 
                            Processing on your site.&nbsp;&nbsp;</p>
                    </div>
                </div>
                <div class="ml-auto">
                    <h3 class="pb-20"><?php echo __('Updated PayPal Integration', 'paypal-for-woocommerce'); ?></h3>
                    <ul>
                        <li><?php echo __('PayPal Commerce Platform', 'paypal-for-woocommerce'); ?></li>
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
                    <?php
                    $product_list = json_decode(urldecode($products));
                    if (in_array("paypal_pro_payflow", $product_list)) {
                        $bool = false;
                        foreach (WC()->payment_gateways->get_available_payment_gateways() as $gateway) {
                            if ($gateway->id === 'paypal_pro_payflow' && $gateway->paypal_partner === 'PayPal') {
                                $bool = true;
                            }
                        }
                        if ($bool) {
                            ?>
                            <br>
                            <P>We noticed that you are running PayPal PayFlow Pro.  Please make sure that the PayPal account you are connecting during the upgrade is the same as the PayPal account that you have been using with PayFlow Pro.</P><br><br>
                        <?php } else { ?>
                            <br>
                            <p>By connecting PayPal Commerce you will be moving away from PayFlow entirely, and you will be using PayPal to process all of your credit card payments as well as PayPal, Pay Later, Venmo, Apple Pay, etc.  If this is not what you are intending, please submit a ticket so we can help you through this process more directly.</p><br><br>
                            <?php
                        }
                    }
                    ?>
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
<?php
include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/template/migration/ppcp_sidebar.php');
