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
                        <p><?php echo __('Everything PayPal Express Checkout and PayPal Pro includes and more.', 'paypal-for-woocommerce'); ?></p>
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
                    <img src="https://www.angelleye.com/wp-content/uploads/2022/12/woocommerce-payments-paypal-advanced-credit-card-checkout.png" width="450" height="457" />
                </div>
            </div>
            <div class="pt-30 pb-50 text-center">
                <div class="angella_button_bg text-center">
                    <?php echo $this->angelleye_ppcp_generate_onboard_button($products); ?>
                </div>
            </div>
            <div class="d-flex d-md-flex">
                <div class="mr-auto">
                    <h3 class="pb-30"><?php echo __('Current PayPal Integration', 'paypal-for-woocommerce'); ?></h3>
                    <ul>
                        <li><?php echo __('Classic Express Checkout', 'paypal-for-woocommerce'); ?></li>
                        <li><?php echo __('PayPal Checkout | Venmo | Pay Later', 'paypal-for-woocommerce'); ?></li>
                        <li><?php echo __('Credit Cards via PayPal Pro ($30/mo Fee)', 'paypal-for-woocommerce'); ?></li>
                        <li><?php echo __('Processing Fee of 2.9%', 'paypal-for-woocommerce'); ?></li>
                        <li><?php echo __('No Longer Officially Supported by PayPal', 'paypal-for-woocommerce'); ?></li>
                    </ul>
                </div>
                <div class="ml-auto">
                    <h3 class="pb-30"><?php echo __('Updated PayPal Integration', 'paypal-for-woocommerce'); ?></h3>
                    <ul>
                        <li><?php echo __('PayPal Complete Payments', 'paypal-for-woocommerce'); ?></li>
                        <li><?php echo __('PayPal Checkout | Venmo | Pay Later', 'paypal-for-woocommerce'); ?></li>
                        <li><?php echo __('Direct Credit Card Processing – No Branding', 'paypal-for-woocommerce'); ?></li>
                        <li><strong><?php echo __('Reduced Fee of 2.69% for Credit Cards!', 'paypal-for-woocommerce'); ?></strong></li>
                        <li><strong><?php echo __('No Monthly Fee!', 'paypal-for-woocommerce'); ?></strong></li>
                        <li><?php echo __('Fully Supported Now and with Future Improvements', 'paypal-for-woocommerce'); ?></li>
                    </ul>
                </div>
            </div>
            <div class="pt-30">
                <p><?php echo __('<strong>NOTE:</strong> All of
                    PayPal’s new features and functionality will be released on the Complete
                    Payments platform.&nbsp; The Classic Gateways are no longer officially
                    supported.&nbsp; We have an agreement with PayPal to stop supporting the
                    Classic gateways, and we need to get you updated by <strong>March 31,
                        2023</strong>
                    in order to avoid potential interruptions.', 'paypal-for-woocommerce'); ?>

                </p>
            </div>
        </div>
    </div>
</div>
<ul class="paypal_woocommerce_support_downloads paypal_woocommerce_product_onboard ppcp_email_confirm">
    <?php if (($this->on_board_status === 'CONNECTED_BUT_NOT_ACC' || $this->on_board_status === 'FULLY_CONNECTED') && !empty($this->email_confirm_text_1)) { ?>
        <li>
            <?php echo '<p>' . $this->email_confirm_text_1 . '</p>'; ?>
            <?php if (!empty($this->email_confirm_text_2)) { ?>
                <?php echo '<p>' . $this->email_confirm_text_2 . '</p>'; ?>
                <p>
                    <?php echo __('Please verify which email is best for us to send future notices about PayPal and payments in general so that you are always informed.', 'paypal-for-woocommerce'); ?>
                </p>
            <?php } ?>
            <br>
            <div class="ppcp_sendy_confirm_parent">
                <input type="text" class="ppcp_sendy_confirm" id="angelleye_ppcp_sendy_email" placeholder="Your Email Address" value="<?php echo!empty($this->result['primary_email']) ? $this->result['primary_email'] : 'paypal-for-woocommerce' ?>">
                <button id="angelleye_ppcp_email_confirm" type="button" class="button button-primary button-primary-own"><?php echo __('Submit', 'paypal-for-woocommerce'); ?></button>
            </div>
            <div id="angelleye_ppcp_sendy_msg"></div>
        </li>
    <?php } ?>
    <li>
        <p><?php echo __('Have A Question Or Need Expert Help?', 'paypal-for-woocommerce'); ?></p>
        <a class="wplk-button" href="https://angelleye.com/support" target="_blank"><?php echo __('Contact Support', 'paypal-for-woocommerce'); ?></a>
    </li>
    <li>
        <p><?php echo __('Plugin Documentation', 'paypal-for-woocommerce'); ?></p>
        <a class="wplk-button" href="https://www.angelleye.com/paypal-complete-payments-setup-guide/" target="_blank"><?php echo __('Plugin Documentation', 'paypal-for-woocommerce'); ?></a>
    </li>
</ul>
