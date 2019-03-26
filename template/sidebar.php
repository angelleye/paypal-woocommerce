<div class="angelleye_content_cell" id="angelleye-sidebar-container">
    <div id="sidebar" class="angelleye-sidebar">
        <div class="angelleye_content_cell_title angelleye-sidebar__title">
            <?php
            printf(esc_html__('%1$s recommendations for you', 'paypal-for-woocommerce'), 'Angell EYE');
            ?>
        </div>
        <div class="angelleye-sidebar__section m10">
            <h2>
                <?php
                echo __('Join Our Newsletter', 'paypal-for-woocommerce');
                ?>
            </h2>
            <div class="angelleye-wizard-text-input">
                <label for="mce-EMAIL" class="angelleye-wizard-text-input-label">Email</label>
                <input type="text" value="" id="angelleye_mailchimp_email" name="angelleye_mailchimp_email" class="email angelleye-wizard-text-input-field" placeholder="email address">
                <div style="color: rgba(0, 0, 0, 0.87); background-color: rgb(255, 255, 255); transition: all 450ms cubic-bezier(0.23, 1, 0.32, 1) 0ms; box-sizing: border-box; font-family: Roboto, sans-serif; box-shadow: rgba(0, 0, 0, 0.12) 0px 1px 6px, rgba(0, 0, 0, 0.12) 0px 1px 4px; border-radius: 2px; display: inline-block; min-width: 88px;">
                    <button style="border: 10px none; box-sizing: border-box; display: inline-block; font-family: Roboto, sans-serif; cursor: pointer; text-decoration: none; margin: 0px; padding: 0px; outline: currentcolor none medium; font-size: inherit; font-weight: inherit; position: relative; height: 36px; line-height: 36px; width: 100%; border-radius: 2px; transition: all 450ms cubic-bezier(0.23, 1, 0.32, 1) 0ms; background-color: green; text-align: center;" tabindex="0" type="button" id="angelleye_mailchimp"><div><div style="height: 36px; border-radius: 2px; transition: all 450ms cubic-bezier(0.23, 1, 0.32, 1) 0ms; top: 0px;"><svg style="display: inline-block; color: rgba(0, 0, 0, 0.87); fill: rgb(255, 255, 255); height: 24px; width: 24px; transition: all 450ms cubic-bezier(0.23, 1, 0.32, 1) 0ms; vertical-align: middle; margin-left: 12px; margin-right: 0px;" viewBox="0 0 28 28"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V8l8 5 8-5v10zm-8-7L4 6h16l-8 5z"></path></svg><span style="position: relative; opacity: 1; font-size: 14px; letter-spacing: 0px; text-transform: uppercase; font-weight: 500; margin: 0px; padding-left: 8px; padding-right: 16px; color: rgb(255, 255, 255);">Sign Up!</span></div></div></button>
                </div><br>
            </div>
            <div id="angelleye_mailchimp_msg">
            </div>
        </div>
        <script type="text/javascript">
            $("#angelleye_mailchimp").click(function () {
                var data = {
                    'action': 'angelleye_marketing_mailchimp_subscription',
                    'email': $('#angelleye_mailchimp_email').val()
                };
                $.post(ajaxurl, data, function () {
                })
                        .done(function (response) {
                            response_parsed = JSON.parse(response);
                            if( response_parsed.result === "success" ) {
                                $('#angelleye_mailchimp_msg').html(response_parsed.msg);
                            } else {
                                $('#angelleye_mailchimp_msg').html(response_parsed.msg);
                            }
                        })
                        .fail(function (response) {
                            alert(response);
                        });
            });
        </script>
        <div class="angelleye-sidebar__section m10">
            <h2><?php esc_html_e('Extend PayPal for WooCommerce', 'paypal-for-woocommerce'); ?></h2>
            <div class="wp-clearfix m10">
                <p>
                    <a href="https://www.angelleye.com/product/paypal-woocommerce-multi-account-management/?utm_source=paypal_for_woocommerce&utm_medium=sidebar" target="_blank">
                        <img src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/images/marketing/paypal-for-woocommerce-multi-account-management.png'; ?>" alt="">
                        <strong><?php esc_html_e('Multi-Account Manager', 'paypal-for-woocommerce'); ?></strong>
                    </a><br>
                    <?php esc_html_e('Allows you to send payments to different PayPal accounts based on rules that you configure.', 'paypal-for-woocommerce'); ?>
                </p>
            </div>
        </div>
        <div class="angelleye-sidebar__section m10">
            <h2><?php esc_html_e('Become an Affiliate', 'paypal-for-woocommerce'); ?></h2>
            <div class="wp-clearfix m10">
                <p>
                    <a href="https://www.angelleye.com/affiliate-area/?utm_source=paypal_for_woocommerce&utm_medium=sidebar" target="_blank">
                        <img src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/images/marketing/angelleye_affiliate.png'; ?>" alt="">
                        <strong><?php echo __('Affiliate Sign Up', 'paypal-for-woocommerce'); ?></strong>
                    </a><br>
                    <?php esc_html_e('Use your voice to inspire entrepreneurship with the Angell EYE Affiliate Program, Our affiliates include entrepreneurs, educators, influencers, and content creators.', 'paypal-for-woocommerce'); ?>
                </p>
            </div>
        </div>
        <div class="angelleye-sidebar__section m10">
            <h2><?php esc_html_e('More Free Tools', 'paypal-for-woocommerce'); ?></h2>
            <div class="wp-clearfix m10">
                <p>
                    <a href="https://www.angelleye.com/product/paypal-ipn-wordpress/?utm_source=paypal_for_woocommerce&utm_medium=sidebar" target="_blank">
                        <img src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/images/marketing/paypal-ipn-for-wordpress.jpg'; ?>" alt="">
                        <strong><?php echo __('PayPal IPN for WordPress', 'paypal-for-woocommerce'); ?></strong>
                    </a><br>
                    <?php esc_html_e('A PayPal Instant Payment Notification (IPN) toolkit that helps you automate tasks in real-time when transactions hit your PayPal account.', 'paypal-for-woocommerce'); ?>
                </p>
            </div>
            <div class="wp-clearfix m10">
                <p>
                    <a href="https://www.angelleye.com/product/wordpress-paypal-button-manager/?utm_source=paypal_for_woocommerce&utm_medium=sidebar" target="_blank">
                        <img src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/images/marketing/wordpress-paypal-button-manager.png'; ?>" alt="">
                        <strong><?php echo __('PayPal WP Button Manager', 'paypal-for-woocommerce'); ?></strong>
                    </a><br>
                    <?php esc_html_e('Create and manage secure PayPal payment buttons from within the WordPress admin panel.', 'paypal-for-woocommerce'); ?>
                </p>
            </div>
            <div class="wp-clearfix m10">
                <p>
                    <a href="https://www.angelleye.com/product/paypal-here-woocommerce-pos/?utm_source=paypal_for_woocommerce&utm_medium=sidebar" target="_blank">
                        <img src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/images/marketing/paypal-here-woocommerce-pos.png'; ?>" alt="">
                        <strong><?php echo __('PayPal Here for WooCommerce', 'paypal-for-woocommerce'); ?></strong>
                    </a><br>
                    <?php esc_html_e('PayPal Here WooCommerce POS plugin brings the PayPal Here app and your WooCommerce web store together.', 'paypal-for-woocommerce'); ?>
                </p>
            </div>
        </div>
        <div class="angelleye-sidebar__section m10">
            <h2><?php esc_html_e('Support', 'paypal-for-woocommerce'); ?></h2>
            <div class="wp-clearfix m10">
                <p>
                    <a href="https://www.angelleye.com/support" target="_blank">
                        <img src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/images/marketing/iconfinder_headphone_53883.png'; ?>" alt="">
                        <strong><?php echo __('AngellEYE Help Desk', 'paypal-for-woocommerce'); ?></strong>
                    </a><br>
                    <?php esc_html_e('Welcome! You can open an AngellEYE Help Desk ticket from the options provided.', 'paypal-for-woocommerce'); ?>
                </p>
            </div>
            <div class="wp-clearfix m10">
                <p>
                    <a href="https://www.angelleye.com/product-category/premium-support/?utm_source=paypal_for_woocommerce&utm_medium=sidebar" target="_blank">
                        <img src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/images/marketing/angelleye-paypal-premium-support.png'; ?>" alt="">
                        <strong><?php echo __('Premium Support', 'paypal-for-woocommerce'); ?></strong>
                    </a><br>
                    <?php esc_html_e('Get the PayPal help you are looking for from Drew Angell, owner of Angell EYE, and Certified PayPal Developer and Partner.', 'paypal-for-woocommerce'); ?>
                </p>
            </div>
        </div>
        <div class="angelleye-sidebar__section m10">
            <h2><?php esc_html_e('Partnerships', 'paypal-for-woocommerce'); ?></h2>
            <div class="wp-clearfix m10">
                <p>
                    <a href="https://www.checkoutwc.com/?ref=15" target="_blank">
                        <img src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/images/marketing/CheckoutWC.png'; ?>" alt="">
                        <strong><?php echo __('Checkout for WooCommerce', 'paypal-for-woocommerce'); ?></strong>
                    </a><br>
                    <?php esc_html_e('Checkout for WooCommerce replaces your checkout page with a beautiful, responsive, and conversion optimized design. Works with every theme.', 'paypal-for-woocommerce'); ?>
                </p>
            </div>
        </div>
    </div>
</div>

