<?php
defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Admin_Onboarding {

    public $settings;
    public $seller_onboarding;
    public $sandbox;
    public $sandbox_merchant_id;
    public $live_merchant_id;
    public $sandbox_client_id;
    public $sandbox_secret_id;
    public $live_client_id;
    public $live_secret_id;
    public $on_board_status = 'NOT_CONNECTED';
    public $result;
    protected static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        try {
            $this->angelleye_ppcp_load_class();
            $this->angelleye_ppcp_load_variable();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_load_class() {
        try {
            if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-ppcp-angelleye-settings.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Seller_Onboarding')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-seller-onboarding.php';
            }
            $this->settings = WC_Gateway_PPCP_AngellEYE_Settings::instance();
            $this->seller_onboarding = AngellEYE_PayPal_PPCP_Seller_Onboarding::instance();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_load_variable() {
        $this->sandbox = 'yes' === $this->settings->get('testmode', 'no');
        $this->sandbox_merchant_id = $this->settings->get('sandbox_merchant_id', '');
        $this->live_merchant_id = $this->settings->get('live_merchant_id', '');
        $this->sandbox_client_id = $this->settings->get('sandbox_client_id', '');
        $this->sandbox_secret_id = $this->settings->get('sandbox_api_secret', '');
        $this->live_client_id = $this->settings->get('api_client_id', '');
        $this->live_secret_id = $this->settings->get('api_secret', '');
        if (!empty($this->sandbox_client_id) && !empty($this->sandbox_secret_id)) {
            $this->is_sandbox_first_party_used = 'yes';
            $this->is_sandbox_third_party_used = 'no';
        } else if (!empty($this->sandbox_merchant_id)) {
            $this->is_sandbox_third_party_used = 'yes';
            $this->is_sandbox_first_party_used = 'no';
        } else {
            $this->is_sandbox_third_party_used = 'no';
            $this->is_sandbox_first_party_used = 'no';
        }
        if (!empty($this->live_client_id) && !empty($this->live_secret_id)) {
            $this->is_live_first_party_used = 'yes';
            $this->is_live_third_party_used = 'no';
        } else if (!empty($this->live_merchant_id)) {
            $this->is_live_third_party_used = 'yes';
            $this->is_live_first_party_used = 'no';
        } else {
            $this->is_live_third_party_used = 'no';
            $this->is_live_first_party_used = 'no';
        }

        if ($this->sandbox) {
            if ($this->is_sandbox_third_party_used === 'no' && $this->is_sandbox_first_party_used === 'no') {
                $this->on_board_status = 'NOT_CONNECTED';
            } elseif ($this->is_sandbox_third_party_used === 'yes') {
                $this->result = $this->seller_onboarding->angelleye_track_seller_onboarding_status($this->sandbox_merchant_id);
                if ($this->seller_onboarding->angelleye_is_acdc_payments_enable($this->result)) {
                    $this->on_board_status = 'FULLY_CONNECTED';
                    $this->settings->set('enable_advanced_card_payments', 'yes');
                    $this->settings->persist();
                } else {
                    $this->on_board_status = 'CONNECTED_BUT_NOT_ACC';
                    $this->settings->set('enable_advanced_card_payments', 'no');
                    $this->settings->persist();
                }
            } elseif ($this->is_sandbox_first_party_used === 'yes') {
                $this->on_board_status = 'USED_FIRST_PARTY';
            }
        } else {
            if ($this->is_live_third_party_used === 'no' && $this->is_live_first_party_used === 'no') {
                $this->on_board_status = 'NOT_CONNECTED';
            } elseif ($this->is_live_third_party_used === 'yes') {
                $this->result = $this->seller_onboarding->angelleye_track_seller_onboarding_status($this->live_merchant_id);
                if ($this->seller_onboarding->angelleye_is_acdc_payments_enable($this->result)) {
                    $this->on_board_status = 'FULLY_CONNECTED';
                    $this->settings->set('enable_advanced_card_payments', 'yes');
                    $this->settings->persist();
                } else {
                    $this->on_board_status = 'CONNECTED_BUT_NOT_ACC';
                    $this->settings->set('enable_advanced_card_payments', 'no');
                    $this->settings->persist();
                }
            } elseif ($this->is_live_first_party_used === 'yes') {
                $this->on_board_status = 'USED_FIRST_PARTY';
            }
        }
    }

    public function view() {
        ?>    
        <div id="angelleye_paypal_marketing_table">
            <?php if ($this->on_board_status === 'NOT_CONNECTED') { ?>
                <div class="paypal_woocommerce_product">
                    <div class="paypal_woocommerce_product_onboard" style="text-align:center;">
                        <span class="ppcp_onbard_icon"><img class="image" src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/ppcp_admin_onbard_icon.png'; ?>"></span>
                        <br><br><br>
                        <div class="paypal_woocommerce_product_onboard_content">
                            <p><?php echo __('Welcome to the easiest one-stop solution for accepting PayPal, Debit and Credit <br>Cards, with a lower per-transaction cost for cards than other gateways!', ''); ?></p>
                            <a href="https://wplaunchify.com/newsletter/" class="wplk-button" target="_blank"><?php echo __('Start Now', ''); ?></a>
                            <p><?php echo __('Buyers may pay with Debit/Credit (no PayPal account required), <br>and your fee will be only 2.69% + 49¢!', ''); ?></p>
                            <p><?php echo __('Buyers may also choose to pay with <br>PayPal Checkout, Pay Later, Venmo, and more!', ''); ?></p>    
                        </div>
                    </div>
                </div>
            <?php } elseif ($this->on_board_status === 'CONNECTED_BUT_NOT_ACC') { ?>
                <div class="paypal_woocommerce_product">
                    <div class="paypal_woocommerce_product_onboard" style="text-align:center;">
                        <span class="ppcp_onbard_icon"><img class="image" src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/ppcp_admin_onbard_icon.png'; ?>"></span>
                        <br><br><br>
                        <div class="paypal_woocommerce_product_onboard_content">
                            <br>
                            <span><img class="green_checkmark" src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/green_checkmark.png'; ?>"></span>
                            <p><?php echo __('You’re currently setup and enjoying the benefits of <br>WooCommerce Complete Payments.', ''); ?></p>
                            <p><?php echo __('However, we need additional verification to approve you for the reduced <br>rate of 2.69% on debit/credit cards.', ''); ?></p>
                            <p><?php echo __('To apply for a reduced rate, modify your setup, <br>or learn more about additional options, please use the buttons below.', ''); ?></p>    
                            <br>
                            <a href="https://wplaunchify.com/newsletter/" class="green-button" target="_blank"><?php echo __('Apply for Cheaper Fees!', ''); ?></a>
                            <a href="https://wplaunchify.com/newsletter/" class="wplk-button" target="_blank"><?php echo __('Modify Setup', ''); ?></a>
                            <a href="https://wplaunchify.com/newsletter/" class="slate_gray" target="_blank"><?php echo __('Learn More', ''); ?></a>
                            <br><br>
                        </div>
                    </div>
                </div>
            <?php } elseif ($this->on_board_status === 'FULLY_CONNECTED') { ?>
                <div class="paypal_woocommerce_product">
                    <div class="paypal_woocommerce_product_onboard" style="text-align:center;">
                        <span class="ppcp_onbard_icon"><img class="image" src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/ppcp_admin_onbard_icon.png'; ?>"></span>
                        <br><br><br>
                        <div class="paypal_woocommerce_product_onboard_content">
                            <br>
                            <span><img class="green_checkmark" src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/green_checkmark.png'; ?>"></span>
                            <p><?php echo __('You’re currently setup and enjoying the benefits of <br> WooCommerce Complete Payments.', ''); ?></p>
                            <p><?php echo __('This includes a reduced rate for debit / credit cards of only 2.69% + 49¢!', ''); ?></p>
                            <p><?php echo __('To modify your setup or learn more about additional options, <br> please use the buttons below.', ''); ?></p>   
                            <br>
                            <a href="https://wplaunchify.com/newsletter/" class="wplk-button" target="_blank"><?php echo __('Modify Setup', ''); ?></a>
                            <a href="https://wplaunchify.com/newsletter/" class="slate_gray" target="_blank"><?php echo __('Learn More', ''); ?></a>
                            <br><br>
                        </div>
                    </div>
                </div>
            <?php } elseif ($this->on_board_status === 'USED_FIRST_PARTY') { ?>
                <div class="paypal_woocommerce_product">
                    <div class="paypal_woocommerce_product_onboard" style="text-align:center;">
                        <span class="ppcp_onbard_icon"><img class="image" src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/ppcp_admin_onbard_icon.png'; ?>"></span>
                        <br><br><br>
                        <div class="paypal_woocommerce_product_onboard_content">
                            <br>
                            <span><img class="green_checkmark" src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/green_checkmark.png'; ?>"></span>
                            <p><?php echo __('First Party WooCommerce Complete Payments Used.', ''); ?></p>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
        <?php
    }

}
