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
    public $dcc_applies;
    protected static $_instance = null;
    public $ppcp_paypal_country = null;
    public $is_sandbox_third_party_used;
    public $is_sandbox_first_party_used;
    public $is_live_first_party_used;
    public $is_live_third_party_used;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        try {
            $this->angelleye_ppcp_load_class();
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
            if (!class_exists('AngellEYE_PayPal_PPCP_DCC_Validate')) {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-dcc-validate.php');
            }

            $this->dcc_applies = AngellEYE_PayPal_PPCP_DCC_Validate::instance();
            $this->settings = WC_Gateway_PPCP_AngellEYE_Settings::instance();
            $this->seller_onboarding = AngellEYE_PayPal_PPCP_Seller_Onboarding::instance();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_load_variable() {
        if (isset($_GET['testmode'])) {
            $this->sandbox = 'yes' === ($_GET['testmode'] === 'yes') ? 'yes' : 'no';
        } else {
            $this->sandbox = 'yes' === $this->settings->get('testmode', 'no');
        }
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
                if (isset($this->result['country'])) {
                    $this->ppcp_paypal_country = $this->result['country'];
                }
                if ($this->dcc_applies->for_country_currency($this->ppcp_paypal_country) === false) {
                    $this->on_board_status = 'FULLY_CONNECTED';
                } else {
                    if ($this->seller_onboarding->angelleye_is_acdc_payments_enable($this->result)) {
                        $this->on_board_status = 'FULLY_CONNECTED';
                        $this->settings->set('enable_advanced_card_payments', 'yes');
                        $this->settings->persist();
                    } else {
                        $this->on_board_status = 'CONNECTED_BUT_NOT_ACC';
                        $this->settings->set('enable_advanced_card_payments', 'no');
                        $this->settings->persist();
                    }
                    if ($this->seller_onboarding->angelleye_ppcp_is_fee_enable($this->result)) {
                        set_transient(AE_FEE, 'yes', 24 * DAY_IN_SECONDS);
                    } else {
                        set_transient(AE_FEE, 'no', 24 * DAY_IN_SECONDS);
                    }
                }
            } elseif ($this->is_sandbox_first_party_used === 'yes') {
                $this->on_board_status = 'USED_FIRST_PARTY';
            }
        } else {
            if ($this->is_live_third_party_used === 'no' && $this->is_live_first_party_used === 'no') {
                $this->on_board_status = 'NOT_CONNECTED';
            } elseif ($this->is_live_third_party_used === 'yes') {
                $this->result = $this->seller_onboarding->angelleye_track_seller_onboarding_status($this->live_merchant_id);
                if (isset($this->result['country'])) {
                    $this->ppcp_paypal_country = $this->result['country'];
                }
                if ($this->dcc_applies->for_country_currency($this->ppcp_paypal_country) === false) {
                    $this->on_board_status = 'FULLY_CONNECTED';
                } else {
                    if ($this->seller_onboarding->angelleye_is_acdc_payments_enable($this->result)) {
                        $this->on_board_status = 'FULLY_CONNECTED';
                        $this->settings->set('enable_advanced_card_payments', 'yes');
                        $this->settings->persist();
                    } else {
                        $this->on_board_status = 'CONNECTED_BUT_NOT_ACC';
                        $this->settings->set('enable_advanced_card_payments', 'no');
                        $this->settings->persist();
                    }
                    if ($this->seller_onboarding->angelleye_ppcp_is_fee_enable($this->result)) {
                        set_transient(AE_FEE, 'yes', 24 * DAY_IN_SECONDS);
                    } else {
                        set_transient(AE_FEE, 'no', 24 * DAY_IN_SECONDS);
                    }
                }
            } elseif ($this->is_live_first_party_used === 'yes') {
                $this->on_board_status = 'USED_FIRST_PARTY';
            }
        }
    }

    public function angelleye_get_signup_link($testmode = 'yes', $page) {
        try {

            $seller_onboarding_result = $this->seller_onboarding->angelleye_generate_signup_link($testmode, $page);
            if (isset($seller_onboarding_result['links'])) {
                foreach ($seller_onboarding_result['links'] as $link) {
                    if (isset($link['rel']) && 'action_url' === $link['rel']) {
                        return isset($link['href']) ? $link['href'] : false;
                    }
                }
            } else {
                return false;
            }
        } catch (Exception $ex) {
            
        }
    }

    public function view() {
        $this->angelleye_ppcp_load_variable();
        ?>    
        <div id="angelleye_paypal_marketing_table" style="width: 80%;">
            <?php if ($this->on_board_status === 'NOT_CONNECTED' || $this->on_board_status === 'USED_FIRST_PARTY') { ?>
                <div class="paypal_woocommerce_product">
                    <div class="paypal_woocommerce_product_onboard" style="text-align:center;">
                        <span class="ppcp_onbard_icon"><img class="image" src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/ppcp_admin_onbard_icon.png'; ?>"></span>
                        <br><br><br>
                        <div class="paypal_woocommerce_product_onboard_content">
                            <p><?php echo __('Welcome to the easiest one-stop solution for accepting PayPal, Debit and Credit <br>Cards, with a lower per-transaction cost for cards than most other gateways!', 'paypal-for-woocommerce'); ?></p>
                            <?php
                            if (isset($_GET['testmode'])) {
                                $testmode = ($_GET['testmode'] === 'yes') ? 'yes' : 'no';
                            } else {
                                $testmode = $this->sandbox ? 'yes' : 'no';
                            }
                            $signup_link = $this->angelleye_get_signup_link($testmode, 'admin_settings_onboarding');
                            if ($signup_link) {
                                $args = array(
                                    'displayMode' => 'minibrowser',
                                );
                                $url = add_query_arg($args, $signup_link);
                                ?>
                                <a target="_blank" class="wplk-button" id="<?php echo esc_attr('wplk-button'); ?>" data-paypal-onboard-complete="onboardingCallback" href="<?php echo esc_url($url); ?>" data-paypal-button="true"><?php echo __('Start Now', 'paypal-for-woocommerce'); ?></a>    
                                <?php
                                $script_url = 'https://www.paypal.com/webapps/merchantboarding/js/lib/lightbox/partner.js';
                                ?>
                                <script type="text/javascript">
                                    document.querySelectorAll('[data-paypal-onboard-complete=onboardingCallback]').forEach((element) => {
                                        element.addEventListener('click', (e) => {
                                            if ('undefined' === typeof PAYPAL) {
                                                e.preventDefault();
                                                alert('PayPal');
                                            }
                                        });
                                    });</script>
                                <script id="paypal-js" src="<?php echo esc_url($script_url); ?>"></script> <?php
                            } else {
                                echo __('We could not properly connect to PayPal', '');
                            }
                            ?>  
                            <p><?php echo __('Your <b>total</b> fee when buyers pay with Debit/Credit Card will be just 2.69% + 49¢.', 'paypal-for-woocommerce'); ?></p>
                        </div>
                    </div>
                </div>
                <?php
            } elseif ($this->on_board_status === 'CONNECTED_BUT_NOT_ACC') {
                wp_enqueue_style('ppcp_account_request_form_css', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/css/ppcp_account_request_form.css', null, time());
                wp_enqueue_script('ppcp_account_request_form_js', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/js/ppcp_account_request-form-modal.js', null, time(), true);
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/template/ppcp_account_request_form.php');
                ?>
                <div class="paypal_woocommerce_product">
                    <div class="paypal_woocommerce_product_onboard" style="text-align:center;">
                        <span class="ppcp_onbard_icon"><img class="image" src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/ppcp_admin_onbard_icon.png'; ?>"></span>
                        <br><br><br>
                        <div class="paypal_woocommerce_product_onboard_content">
                            <br>
                            <span><img class="green_checkmark" src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/green_checkmark.png'; ?>"></span>
                            <p><?php echo __('You’re currently setup and enjoying the benefits of <br>Complete Payments - Powered by PayPal.', 'paypal-for-woocommerce'); ?></p>
                            <p><?php echo __('However, we need additional verification to approve you for the reduced <br>rate of 2.69% on debit/credit cards.', 'paypal-for-woocommerce'); ?></p>
                            <p><?php echo __('To apply for a reduced rate, modify your setup, <br>or learn more about additional options, please use the buttons below.', 'paypal-for-woocommerce'); ?></p>    
                            <br>
                            <a class="green-button open_ppcp_account_request_form" ><?php echo __('Apply for Cheaper Fees!', 'paypal-for-woocommerce'); ?></a>
                            <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=angelleye_ppcp'); ?>" class="wplk-button"><?php echo __('Modify Setup', 'paypal-for-woocommerce'); ?></a>
                            <a href="https://www.angelleye.com/paypal-complete-payments-setup-guide/" class="slate_gray" target="_blank"><?php echo __('Learn More', 'paypal-for-woocommerce'); ?></a>
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
                            <p><?php echo __('You’re currently setup and enjoying the benefits of <br> Complete Payments - Powered by PayPal.', 'paypal-for-woocommerce'); ?></p>
                            <?php if ($this->dcc_applies->for_country_currency($this->ppcp_paypal_country) === true) { ?>
                                <p><?php echo __('This includes a reduced rate for debit / credit cards of only 2.69% + 49¢!', 'paypal-for-woocommerce'); ?></p>
                            <?php } ?>
                            <p><?php echo __('To modify your setup or learn more about additional options, <br> please use the buttons below.', 'paypal-for-woocommerce'); ?></p>   
                            <br>
                            <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=angelleye_ppcp'); ?>" class="wplk-button"><?php echo __('Modify Setup', 'paypal-for-woocommerce'); ?></a>
                            <a href="https://www.angelleye.com/paypal-complete-payments-setup-guide/" class="slate_gray" target="_blank"><?php echo __('Learn More', 'paypal-for-woocommerce'); ?></a>
                            <br><br>
                        </div>
                    </div>
                </div>
            <?php } ?>
            <ul class="paypal_woocommerce_support_downloads paypal_woocommerce_product_onboard">
                <li>
                    <p>Have A Question Or Need Expert Help?</p>
                    <a class="wplk-button" href="https://angelleye.com/support" target="_blank"><?php echo __('Contact Support', 'paypal-for-woocommerce'); ?></a>
                </li>
            </ul>
        </div>
        <?php
    }

}
