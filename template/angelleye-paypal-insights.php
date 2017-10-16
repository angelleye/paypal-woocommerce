<?php

class Angelleye_PayPal_Insights {

    public $version;

    public function __construct($version) {
        $this->version = $version;
        add_action('angelleye_paypal_for_woocommerce_general_settings_tab', array($this, 'angelleye_paypal_for_woocommerce_general_settings_tab'), 10);
        add_action('angelleye_paypal_for_woocommerce_general_settings_tab_content', array($this, 'angelleye_paypal_for_woocommerce_general_settings_tab_content'), 10);
        add_action('wp_ajax_angelleye_paypal_insights_cid', array($this, 'angelleye_paypal_insights_cid'), 10);
    }

    public function angelleye_paypal_for_woocommerce_general_settings_tab() {
        $gateway = isset($_GET['gateway']) ? $_GET['gateway'] : 'paypal_payment_gateway_products';
        ?>
        <a href="?page=paypal-for-woocommerce&tab=general_settings&gateway=paypal_for_wooCommerce_paypal_insights" class="nav-tab <?php echo $gateway == 'paypal_for_wooCommerce_paypal_insights' ? 'nav-tab-active' : ''; ?>"><?php echo __('PayPal Insights', 'paypal-for-woocommerce'); ?></a> <?php
    }

    public function angelleye_paypal_insights_details_save() {
        if (!empty($_POST['paypal_insights_save'])) {
            foreach ($_POST as $key => $value) {
                update_option($key, $value);
            }
        }
    }

    public function angelleye_is_paypal_insights_details_avilable() {
        $paypal_insights_merchant_website_url = get_option('paypal_insights_merchant_website_url');
        if (!empty($paypal_insights_merchant_website_url)) {
            return true;
        } else {
            return false;
        }
    }

    public function angelleye_paypal_for_woocommerce_general_settings_tab_content() {
        $this->angelleye_paypal_insights_details_save();
        $gateway = isset($_GET['gateway']) ? $_GET['gateway'] : 'paypal_payment_gateway_products';
        if ($gateway == 'paypal_for_wooCommerce_paypal_insights') {
            $paypal_insights_merchant_website_url = get_option('paypal_insights_merchant_website_url', '');
            ?>
            <div>
                <form method="post" id="mainform" action="" enctype="multipart/form-data">
                    <table class="form-table">
                        <tbody class="angelleye_micro_account_body">
                            <tr valign="top">
                                <th scope="row" class="titledesc">
                                    <label for="paypal_insights_enable"><?php echo __('Enable / Disable', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                                </th>
                                <td class="forminp">
                                    <fieldset>
                                        <label for="paypal_insights_enable">
                                            <input class="paypal_insights_enable" <?php echo (get_option('paypal_insights_enable') == 'on') ? 'checked="checked"' : '' ?> name="paypal_insights_enable" id="paypal_insights_enable" type="checkbox"><?php echo __('Enable PayPal Insights', 'paypal-for-woocommerce-multi-account-management'); ?> </label><br>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row" class="titledesc">
                                    <label for="woocommerce_paypal_express_testmode"><?php echo __('PayPal Sandbox', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                                </th>
                                <td class="forminp">
                                    <fieldset>
                                        <label for="paypal_insights_testmode">
                                            <input class="paypal_insights_testmode" <?php echo (get_option('paypal_insights_testmode') == 'on') ? 'checked="checked"' : '' ?> name="paypal_insights_testmode" id="paypal_insights_testmode" type="checkbox"><?php echo __('Enable PayPal Sandbox', 'paypal-for-woocommerce-multi-account-management'); ?> </label><br>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row" class="titledesc">
                                    <label for="paypal_insights_merchant_website_url"><?php echo __('Merchant’s website URL', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                                </th>
                                <td class="forminp">
                                    <fieldset>
                                        <input class="input-text regular-input width460" value="<?php echo $paypal_insights_merchant_website_url; ?>"  name="paypal_insights_merchant_website_url" id="paypal_insights_merchant_website_url" style="" placeholder="" type="text">
                                    </fieldset>
                                </td>
                            </tr>
                            <tr style="display: table-row;" valign="top">
                                <th scope="row" class="titledesc">
                                    <input name="paypal_insights_save" class="button-primary woocommerce-save-button" type="submit" value="<?php esc_attr_e('Save Changes', 'paypal-for-woocommerce-multi-account-management'); ?>" />
                                    <?php wp_nonce_field('paypal_insights_save'); ?>
                                </th>
                            </tr>
                            </body>
                    </table>
                </form>
            </div>
            <?php if ($this->angelleye_is_paypal_insights_details_avilable() == true) { ?>
                <div class='wrap'>
                    <div id='angelleye_muse_activate_managesettings_button_sandbox'></div>
                    <div id='angelleye_muse_activate_managesettings_button_production'></div>
                </div>
                <script src='https://www.paypalobjects.com/muse/partners/muse-button-bundle.js'></script>
                <script>
                    jQuery('#paypal_insights_testmode').change(function() {
                        var sandbox = jQuery('#angelleye_muse_activate_managesettings_button_sandbox');
                        var production = jQuery('#angelleye_muse_activate_managesettings_button_production');
                        if (jQuery(this).is(':checked')) {
                            sandbox.show();
                            production.hide();
                        } else {
                            sandbox.hide();
                            production.show();
                        }
                    }).change();
                    var muse_options_sandbox = {
                        onContainerCreate: callback_onsuccess_sandbox,
                        url: '<?php echo get_option('paypal_insights_merchant_website_url', ''); ?>',
                        parnter_name: 'Angell EYE',
                        bn_code: 'AngellEYE_PHPClass',
                        env: 'sandbox',
                        cid: '<?php echo get_option('angelleye_paypal_insights_sandbox_cid', ''); ?>'
                    }
                    function callback_onsuccess_sandbox(containerId) {
                        var data = {
                            'action': 'angelleye_paypal_insights_cid',
                            'containerId': containerId,
                            'is_sandbox': 'true'
                        };
                        jQuery.post(ajaxurl, data, function(response) {
                        });
                        muse_options_sandbox.cid = containerId;
                    }
                    MUSEButton('angelleye_muse_activate_managesettings_button_sandbox', muse_options_sandbox);
                    var muse_options_production = {
                        onContainerCreate: callback_onsuccess_production,
                        url: '<?php echo get_option('paypal_insights_merchant_website_url', ''); ?>',
                        parnter_name: 'Angell EYE',
                        bn_code: 'AngellEYE_PHPClass',
                        env: 'production',
                        cid: '<?php echo get_option('angelleye_paypal_insights_production_cid', ''); ?>'
                    }
                    function callback_onsuccess_production(containerId) {
                        var data = {
                            'action': 'angelleye_paypal_insights_cid',
                            'containerId': containerId,
                            'is_sandbox': 'false'
                        };
                        jQuery.post(ajaxurl, data, function(response) {
                        });
                        muse_options_production.cid = containerId;
                    }
                    MUSEButton('angelleye_muse_activate_managesettings_button_production', muse_options_production);
                </script>
                <?php
            }
        }
    }

    public function angelleye_paypal_insights_cid() {
        if (!empty($_POST['containerId'])) {
            if (!empty($_POST['is_sandbox']) && $_POST['is_sandbox'] == 'true') {
                update_option('angelleye_paypal_insights_sandbox_cid', $_POST['containerId']);
            } else {
                update_option('angelleye_paypal_insights_production_cid', $_POST['containerId']);
            }
        }
    }

}
?>
