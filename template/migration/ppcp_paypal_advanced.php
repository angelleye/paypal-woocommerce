<div class="angelleye_ppcp_migration">
    <div class="angelleye_ppcp_migration_content p-50 p-md-30 mb-30 ">
        <div class="entry-content">
            <h2 class="text-center pb-30"><?php echo __('Your PayPal integration is due for a required update.', 'paypal-for-woocommerce'); ?></h2>
            <div class="d-flex d-md-flex">
                <div class="col-md-6">
                    <div class="pt-30">
                        <h4><?php echo __('Great new features and cost savings!', 'paypal-for-woocommerce'); ?></h4>
                    </div>
                    <div class="pt-5">
                        <p><?php echo __('Everything Payments Advanced includes and more.', 'paypal-for-woocommerce'); ?></p>
                    </div>
                    <div class="pt-15 font-bold">
                        <ul>
                            <li><?php echo __('PayPal Checkout', 'paypal-for-woocommerce'); ?></li>
                            <li><?php echo __('Venmo', 'paypal-for-woocommerce'); ?></li>
                            <li><?php echo __('Pay Later', 'paypal-for-woocommerce'); ?></li>
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
                        <li><?php echo __('PayPal Payments Advanced', 'paypal-for-woocommerce'); ?></li>
                        <li><?php echo __('PayPal Checkout', 'paypal-for-woocommerce'); ?></li>
                        <li><?php echo __('Credit Cards via Payments Advanced', 'paypal-for-woocommerce'); ?></li>
                        <li><?php echo __('Requires Monthly Fee', 'paypal-for-woocommerce'); ?></li>
                        <li><?php echo __('Processing Fee of 2.9%', 'paypal-for-woocommerce'); ?></li>
                        <li><?php echo __('No Longer Officially Supported by PayPal', 'paypal-for-woocommerce'); ?></li>
                    </ul>
                </div>
                <div class="ml-auto">
                    <h3 class="pb-20"><?php echo __('Updated PayPal Integration', 'paypal-for-woocommerce'); ?></h3>
                    <ul>
                        <li><?php echo __('PayPal Complete Payments', 'paypal-for-woocommerce'); ?></li>
                        <li><?php echo __('PayPal Checkout | Venmo | Pay Later', 'paypal-for-woocommerce'); ?></li>
                        <li><?php echo __('Direct Credit Card Processing – No Branding', 'paypal-for-woocommerce'); ?></li>
                        <li><strong><?php echo __('Total Fee of 2.69%¹', 'paypal-for-woocommerce'); ?></strong></li>
                        <li><strong><?php echo __('No Monthly Fee!', 'paypal-for-woocommerce'); ?></strong></li>
                        <li><?php echo __('Fully Supported Now and with Future Improvements', 'paypal-for-woocommerce'); ?></li>
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
<ul class="paypal_woocommerce_support_downloads paypal_woocommerce_product_onboard ppcp_email_confirm">
    <li>
        <p><?php echo __('Schedule a live meeting with Drew Angell?', 'paypal-for-woocommerce'); ?></p>
        <img class="pb-30" src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/guru-drew.png'; ?>" width="130" height="350" />
        <a class="wplk-button" target="_blank" href="https://calendar.app.google/kFcrJSmV8fW8iWny8"><?php echo __('Schedule Meeting', 'paypal-for-woocommerce'); ?></a>
    </li> 
    <li>
        <p><?php echo __('Have A Question Or Need Expert Help?', 'paypal-for-woocommerce'); ?></p>
        <a class="wplk-button" href="https://angelleye.com/support" target="_blank"><?php echo __('Contact Support', 'paypal-for-woocommerce'); ?></a>
    </li>
    <li>
        <p><?php echo __('Plugin Documentation', 'paypal-for-woocommerce'); ?></p>
        <a class="wplk-button" href="https://www.angelleye.com/paypal-complete-payments-setup-guide/" target="_blank"><?php echo __('Plugin Documentation', 'paypal-for-woocommerce'); ?></a>
    </li>
</ul>
