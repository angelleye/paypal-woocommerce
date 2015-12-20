<?php
/**
 * PayPal for WooCommerce - Settings
 */
?>
<?php $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general_settings'; ?>

<div class="wrap">

    <h2><?php echo esc_html(get_admin_page_title()); ?></h2>

    <h2 class="nav-tab-wrapper">
        <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=express_checkout" class="nav-tab <?php echo $active_tab == 'general_settings' ? 'nav-tab-active' : ''; ?>"><?php echo __('General', $this->plugin_slug); ?></a>
        <a href="?page=<?php echo $this->plugin_slug; ?>&tab=tabs" class="nav-tab <?php echo $active_tab == 'tabs' ? 'nav-tab-active' : ''; ?>"><?php echo __('Tools', $this->plugin_slug); ?></a>
    </h2>

    <?php if ($active_tab == 'general_settings') { ?>

        <?php
        $tool_subtab = $_GET['gateway'] ? $_GET['gateway'] : 'express_checkout';
        ?>
        <h2 class="nav-tab-wrapper">
            <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=express_checkout" class="nav-tab <?php echo $tool_subtab == 'express_checkout' ? 'nav-tab-active' : ''; ?>"><?php echo __('PayPal Express Checkout', $this->plugin_slug); ?></a>
            <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=payflow" class="nav-tab <?php echo $tool_subtab == 'payflow' ? 'nav-tab-active' : ''; ?>"><?php echo __('PayPal Payments Pro (PayFlow)', $this->plugin_slug); ?></a>
            <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=dodirectpayment" class="nav-tab <?php echo $tool_subtab == 'dodirectpayment' ? 'nav-tab-active' : ''; ?>"><?php echo __('PayPal Website Payments Pro (DoDirectPayment)', $this->plugin_slug); ?></a>
        </h2>

        <?php
        if ((isset($_GET['tab']) && isset($_GET['gateway'])) && ($_GET['tab'] == 'general_settings' && $_GET['gateway'] == 'express_checkout')) {
            ?>
            <div class="wrap">
                <p><?php _e('PayPal Express Checkout is a more advanced version of the standard PayPal payment option that is included with WooCommerce. It has more features included with it and allows us to more tightly integrate PayPal into WooCommerce. It is the recommended method of enabling PayPal payments in WooCommerce.', $this->plugin_slug); ?></p>
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_gateway_paypal_express_angelleye'); ?>"><?php _e('Express Checkout Setting', $this->plugin_slug); ?></a>
            </div>
            <?php
        }
        ?>

        <?php
        if ((isset($_GET['tab']) && isset($_GET['gateway'])) && ($_GET['tab'] == 'general_settings' && $_GET['gateway'] == 'payflow')) {
            ?>
            <div class="wrap">
                <p><?php _e('PayPal Payments Pro 2.0 is the latest release of PayPal’s Pro offering. It works on PayPal’s PayFlow Gateway as opposed to their original DoDirectPayment API. You need to be sure that your account is setup for this version of Pro before configuring this payment gateway or you will end up with errors when people attempt to pay you via credit card.', $this->plugin_slug); ?></p>
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_gateway_paypal_pro_payflow_angelleye'); ?>"><?php _e('PayPal Payments Pro (PayFlow) Setting', $this->plugin_slug); ?></a>
            </div>
            <?php
        }
        ?>

        <?php
        if ((isset($_GET['tab']) && isset($_GET['gateway'])) && ($_GET['tab'] == 'general_settings' && $_GET['gateway'] == 'dodirectpayment')) {
            ?>
            <div class="wrap">
                <p><?php _e('PayPal’s Website Payments Pro 3.0 is the original Pro package that PayPal offered. It works on the DoDirectPayment API and is being slowly deprecated since the launch of Payments Pro 2.0 that works on the PayFlow API. You need to be sure that your account is setup for this version of Pro before configuring this payment gateway or you will end up with errors when people attempt to pay you via credit card.', $this->plugin_slug); ?></p>
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_gateway_paypal_pro_angelleye'); ?>"><?php _e('PayPal Website Payments Pro (DoDirectPayment) Setting', $this->plugin_slug); ?></a>
            </div>
            <?php
        }
        ?>

    <?php } else { ?>
        <form method="post" action="options.php" id="woocommerce_offers_options_form">
        </form>
    <?php } ?>
</div>