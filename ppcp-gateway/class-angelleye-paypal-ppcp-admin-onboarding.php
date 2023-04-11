<?php
defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Admin_Onboarding {

    public $setting_obj;
    public $seller_onboarding;
    public $sandbox;
    public $settings_sandbox;
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
    public $email_confirm_text_1;
    public $email_confirm_text_2;
    public $paypal_fee_structure;
    public $is_paypal_vault_approved = false;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        try {
            $this->angelleye_ppcp_load_class();
            $this->paypal_fee_structure = array(
                'US' => array('paypal' => '3.59% + 49¢', 'acc' => '2.69% + 49¢'),
                'UK' => array('paypal' => '3.0% + 30¢', 'acc' => '1.30% + 30¢'),
                'CA' => array('paypal' => '3.0% + 30¢', 'acc' => '2.80% + 30¢'),
                'AU' => array('paypal' => '2.70% + 30¢', 'acc' => '1.85% + 30¢'),
                'FR' => array('paypal' => '3.00% + 35¢', 'acc' => '1.30% + 35¢'),
                'DE' => array('paypal' => '3.09% + 39¢', 'acc' => '3.09% + 39¢'),
                'IT' => array('paypal' => '3,50% + 35¢', 'acc' => '1,30% + 35¢'),
                'ES' => array('paypal' => '3,00% + 05¢', 'acc' => '1,30% + 35¢'),
                'default' => array('paypal' => '3.59% + 49¢', 'acc' => '2.69% + 49¢'),
            );
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
            $this->setting_obj = WC_Gateway_PPCP_AngellEYE_Settings::instance();
            $this->seller_onboarding = AngellEYE_PayPal_PPCP_Seller_Onboarding::instance();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_load_variable() {
        if (isset($_GET['testmode'])) {
            if (($_GET['testmode'] === 'yes')) {
                $this->sandbox = true;
            } else {
                $this->sandbox = false;
            }
        } else {
            $this->sandbox = 'yes' === $this->setting_obj->get('testmode', 'no');
        }
        $this->setting_sandbox = $this->setting_obj->get('testmode', 'no');
        $this->sandbox_merchant_id = $this->setting_obj->get('sandbox_merchant_id', '');
        $this->live_merchant_id = $this->setting_obj->get('live_merchant_id', '');
        $this->sandbox_client_id = $this->setting_obj->get('sandbox_client_id', '');
        $this->sandbox_secret_id = $this->setting_obj->get('sandbox_api_secret', '');
        $this->live_client_id = $this->setting_obj->get('api_client_id', '');
        $this->live_secret_id = $this->setting_obj->get('api_secret', '');
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
        $region = wc_get_base_location();
        $this->ppcp_paypal_country = $region['country'];
        if ($this->sandbox) {
            if ($this->is_sandbox_third_party_used === 'no' && $this->is_sandbox_first_party_used === 'no') {
                $this->on_board_status = 'NOT_CONNECTED';
            } elseif ($this->is_sandbox_third_party_used === 'yes') {
                $this->result = $this->seller_onboarding->angelleye_track_seller_onboarding_status($this->sandbox_merchant_id);
                if (isset($this->result['country'])) {
                    $this->ppcp_paypal_country = $this->result['country'];
                }
                if (!empty($this->result['primary_email'])) {
                    own_angelleye_sendy_list($this->result['primary_email']);
                    $this->email_confirm_text_1 = __('We see that your PayPal email address is', 'paypal-for-woocommerce') . ' <b>' . $this->result['primary_email'] . '</b>';
                }
                $admin_email = get_option("admin_email");
                if (isset($this->result['primary_email']) && $this->result['primary_email'] != $admin_email) {
                    $this->email_confirm_text_2 = __('We see that your site admin email address is', 'paypal-for-woocommerce') . ' <b>' . $admin_email . '</b>';
                } else {
                    $this->email_confirm_text_1 = __('We see that your email address is', 'paypal-for-woocommerce') . ' <b>' . $this->result['primary_email'] . '</b>' . ' If there is a better email to keep you informed about PayPal and payment news please let us know.';
                }
                if ($this->dcc_applies->for_country_currency($this->ppcp_paypal_country) === false) {
                    $this->on_board_status = 'FULLY_CONNECTED';
                } else {
                    if ($this->seller_onboarding->angelleye_is_acdc_payments_enable($this->result)) {
                        $this->on_board_status = 'FULLY_CONNECTED';
                    } else {
                        $this->on_board_status = 'CONNECTED_BUT_NOT_ACC';
                    }
                    if ($this->seller_onboarding->angelleye_ppcp_is_fee_enable($this->result)) {
                        set_transient(AE_FEE, 'yes', 24 * DAY_IN_SECONDS);
                    } else {
                        set_transient(AE_FEE, 'no', 24 * DAY_IN_SECONDS);
                    }
                }
                $this->is_paypal_vault_approved = $this->seller_onboarding->angelleye_is_acdc_payments_enable($this->result);
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
                if (!empty($this->result['primary_email'])) {
                    own_angelleye_sendy_list($this->result['primary_email']);
                    $this->email_confirm_text_1 = __('We see that your PayPal email address is', 'paypal-for-woocommerce') . ' <b>' . $this->result['primary_email'] . '</b>';
                }
                $admin_email = get_option("admin_email");
                if ($this->result['primary_email'] != $admin_email) {
                    $this->email_confirm_text_2 = __('We see that your site admin email address is', 'paypal-for-woocommerce') . ' <b>' . $admin_email . '</b>';
                } else {
                    $this->email_confirm_text_1 = __('We see that your email address is', 'paypal-for-woocommerce') . ' <b>' . $this->result['primary_email'] . '</b>' . ' If there is a better email to keep you informed about PayPal and payment news please let us know.';
                }
                if ($this->dcc_applies->for_country_currency($this->ppcp_paypal_country) === false) {
                    $this->on_board_status = 'FULLY_CONNECTED';
                } else {
                    if ($this->seller_onboarding->angelleye_is_acdc_payments_enable($this->result)) {
                        $this->on_board_status = 'FULLY_CONNECTED';
                    } else {
                        $this->on_board_status = 'CONNECTED_BUT_NOT_ACC';
                    }
                    if ($this->seller_onboarding->angelleye_ppcp_is_fee_enable($this->result)) {
                        set_transient(AE_FEE, 'yes', 24 * DAY_IN_SECONDS);
                    } else {
                        set_transient(AE_FEE, 'no', 24 * DAY_IN_SECONDS);
                    }
                }
                $this->is_paypal_vault_approved = $this->seller_onboarding->angelleye_is_acdc_payments_enable($this->result);
            } elseif ($this->is_live_first_party_used === 'yes' || $this->is_sandbox_third_party_used === 'yes') {
                $this->on_board_status = 'USED_FIRST_PARTY';
            }
        }
    }

    public function angelleye_get_signup_link($testmode, $page) {
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
        <div id="angelleye_paypal_marketing_table">
            <?php if ($this->on_board_status === 'NOT_CONNECTED' || $this->on_board_status === 'USED_FIRST_PARTY') { ?>
                <div class="paypal_woocommerce_product">
                    <div class="paypal_woocommerce_product_onboard" style="text-align:center;">
                        <span class="ppcp_onbard_icon"><img width="150px" class="image" src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/ppcp_admin_onbard_icon.png'; ?>"></span>
                        <br><br>
                        <div class="paypal_woocommerce_product_onboard_content">
                            <p><?php echo __('Welcome to the most PayPal Commerce solution available for WooCommerce. <br> Built by Angelleye.', 'paypal-for-woocommerce'); ?></p>
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
                                echo __('We could not properly connect to PayPal', 'paypal-for-woocommerce');
                            }
                            ?>
                            <p class="ppcp_paypal_fee"><?php echo sprintf(__('Increase average order totals and conversion rates with <br>PayPal Checkout, PayPal Credit, Buy Now Pay Later, Venmo, and more! <br>All for a total fee of only %s.', 'paypal-for-woocommerce'), $this->angelleye_ppcp_get_paypal_fee_structure($this->ppcp_paypal_country, 'paypal')); ?>
                                <br><br>
                                <?php if ($this->ppcp_paypal_country === 'DE') { ?>
                                    <?php echo sprintf(__('Fees on Visa/MasterCard/Discover transactions <br>transactions are a total fee of only %s.', 'paypal-for-woocommerce'), $this->angelleye_ppcp_get_paypal_fee_structure($this->ppcp_paypal_country, 'acc')); ?>
                                <?php } else { ?>
                                    <?php echo sprintf(__('Save money on Visa/MasterCard/Discover transactions <br>with a total fee of only %s.', 'paypal-for-woocommerce'), $this->angelleye_ppcp_get_paypal_fee_structure($this->ppcp_paypal_country, 'acc')); ?>
                                <?php } ?>
                                <br><a target="_blank" href="https://www.angelleye.com/woocommerce-complete-payments-paypal-angelleye-fees/"><small style="font-size:12px;">Learn More</small></a></p>
                        </div>
                    </div>
                </div>
                <?php
            } elseif ($this->on_board_status === 'CONNECTED_BUT_NOT_ACC') {
                wp_enqueue_style('ppcp_account_request_form_css', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/css/ppcp_account_request_form.css', null, time());
                wp_enqueue_script('ppcp_account_request_form_js', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/js/ppcp_account_request-form-modal.js', null, time(), true);
                $ppcp_account_request_form_url = add_query_arg(array('testmode' => $this->setting_sandbox), 'https://d1kjd56jkqxpts.cloudfront.net/ppcp-account-request/index.html');
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/template/ppcp_account_request_form.php');
                ?>
                <div class="paypal_woocommerce_product">
                    <div class="paypal_woocommerce_product_onboard" style="text-align:center;">
                        <span class="ppcp_onbard_icon"><img width="150px" class="image" src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/ppcp_admin_onbard_icon.png'; ?>"></span>
                        <br><br>
                        <div class="paypal_woocommerce_product_onboard_content">
                            <br>
                            <span><img class="green_checkmark" src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/green_checkmark.png'; ?>"></span>
                            <p><?php echo __('You’re currently setup and enjoying the benefits of PayPal Commerce. <br> Built by Angelleye.', 'paypal-for-woocommerce'); ?></p>
                            <p><?php echo sprintf(__('However, we need additional verification to approve you for the reduced <br>rate of %s on debit/credit cards.', 'paypal-for-woocommerce'), $this->angelleye_ppcp_get_paypal_fee_structure($this->ppcp_paypal_country, 'acc')); ?></p>
                            <p><?php echo __('To apply for a reduced rate, modify your setup, <br>or learn more about additional options, please use the buttons below.', 'paypal-for-woocommerce'); ?></p>
                            <?php if($this->is_paypal_vault_approved === false) { ?>
                            <p><?php echo __('Your PayPal account is not approved for the Vault functionality<br>which is required for Subscriptions (token payments). <br>Please Reconnect your PayPal account to apply for this feature.', 'paypal-for-woocommerce'); ?></p>
                            <?php } ?>
                            <br>
                            <a class="green-button open_ppcp_account_request_form" ><?php echo __('Apply for Cheaper Fees!', 'paypal-for-woocommerce'); ?></a>
                            <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=angelleye_ppcp'); ?>" class="wplk-button"><?php echo __('Modify Setup', 'paypal-for-woocommerce'); ?></a>
                            <?php if($this->is_paypal_vault_approved === false) { 
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
                                    <a target="_blank" class="green-button" id="<?php echo esc_attr('wplk-button'); ?>" data-paypal-onboard-complete="onboardingCallback" href="<?php echo esc_url($url); ?>" data-paypal-button="true"><?php echo __('Reconnect PayPal Account', 'paypal-for-woocommerce'); ?></a>
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
                                    echo __('We could not properly connect to PayPal', 'paypal-for-woocommerce');
                                }
                            } 
                            ?>
                            <a href="https://www.angelleye.com/paypal-complete-payments-setup-guide/" class="slate_gray" target="_blank"><?php echo __('Learn More', 'paypal-for-woocommerce'); ?></a>
                            <br><br>
                        </div>
                    </div>
                </div>
            <?php } elseif ($this->on_board_status === 'FULLY_CONNECTED') { ?>
                <div class="paypal_woocommerce_product">
                    <div class="paypal_woocommerce_product_onboard" style="text-align:center;">
                        <span class="ppcp_onbard_icon"><img width="150px" class="image" src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/ppcp_admin_onbard_icon.png'; ?>"></span>
                        <br><br>
                        <div class="paypal_woocommerce_product_onboard_content">
                            <br>
                            <span><img class="green_checkmark" src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/green_checkmark.png'; ?>"></span>
                            <p><?php echo __('You’re currently setup and enjoying the benefits of PayPal Commerce. <br> Built by Angelleye.', 'paypal-for-woocommerce'); ?></p>
                            <p><?php echo __('To modify your setup or learn more about additional options, <br> please use the buttons below.', 'paypal-for-woocommerce'); ?></p>
                            <?php if($this->is_paypal_vault_approved === false) { ?>
                            <p><?php echo __('Your PayPal account is not approved for the Vault functionality<br>which is required for Subscriptions (token payments). <br>Please Reconnect your PayPal account to apply for this feature.', 'paypal-for-woocommerce'); ?></p>
                            <?php } ?>
                            <br>
                            <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=angelleye_ppcp'); ?>" class="wplk-button"><?php echo __('Modify Setup', 'paypal-for-woocommerce'); ?></a>
                            <?php if($this->is_paypal_vault_approved === false) { 
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
                                    <a target="_blank" class="green-button" id="<?php echo esc_attr('wplk-button'); ?>" data-paypal-onboard-complete="onboardingCallback" href="<?php echo esc_url($url); ?>" data-paypal-button="true"><?php echo __('Reconnect PayPal Account', 'paypal-for-woocommerce'); ?></a>
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
                                    echo __('We could not properly connect to PayPal', 'paypal-for-woocommerce');
                                }
                            } 
                            ?>
                            <a href="https://www.angelleye.com/paypal-complete-payments-setup-guide/" class="slate_gray" target="_blank"><?php echo __('Learn More', 'paypal-for-woocommerce'); ?></a>
                            <br><br>
                        </div>
                    </div>
                </div>
            <?php } ?>
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
                            <input type="text" class="ppcp_sendy_confirm" id="angelleye_ppcp_sendy_email" placeholder="Your Email Address" value="<?php echo!empty($this->result['primary_email']) ? $this->result['primary_email'] : '' ?>">
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
        </div>
        <?php
    }

    public function angelleye_ppcp_get_paypal_fee_structure($country, $product) {
        try {
            if (isset($this->paypal_fee_structure[$country])) {
                return $this->paypal_fee_structure[$country][$product];
            } else {
                return $this->paypal_fee_structure['default'][$product];
            }
        } catch (Exception $ex) {
            
        }
    }

}
