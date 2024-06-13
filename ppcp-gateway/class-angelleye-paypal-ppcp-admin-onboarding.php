<?php

use Automattic\WooCommerce\Utilities\OrderUtil;

defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Admin_Onboarding {

    public $setting_obj;
    public ?AngellEYE_PayPal_PPCP_Seller_Onboarding $seller_onboarding;
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
    public $subscription_support_enabled;
    public $angelleye_ppcp_migration_wizard_notice_key = 'angelleye_ppcp_migration_wizard_notice_key';
    public $angelleye_ppcp_migration_wizard_notice_data = array();
    public $setting_sandbox;

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
            if (class_exists('WC_Subscriptions') && function_exists('wcs_create_renewal_order')) {
                $this->subscription_support_enabled = true;
            } else {
                $this->subscription_support_enabled = false;
            }
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
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
            $this->api_log->log("The exception was created on line: " . $ex->getFile() . ' ' . $ex->getLine(), 'error');
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
        $this->ppcp_paypal_country = $this->dcc_applies->country();

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
                if (defined('PPCP_PAYPAL_COUNTRY')) {
                    $this->ppcp_paypal_country = PPCP_PAYPAL_COUNTRY;
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
                    if (angelleye_is_acdc_payments_enable($this->result)) {
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
                $this->is_paypal_vault_approved = angelleye_is_vaulting_enable($this->result);
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
                    if (angelleye_is_acdc_payments_enable($this->result)) {
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
                $this->is_paypal_vault_approved = angelleye_is_vaulting_enable($this->result);
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

    public function angelleye_get_signup_link_for_vault($testmode, $page) {
        try {
            $body = $this->seller_onboarding->ppcp_vault_data();
            $seller_onboarding_result = $this->seller_onboarding->angelleye_generate_signup_link_with_feature($testmode, $page, $body);
            if (isset($seller_onboarding_result['links'])) {
                foreach ($seller_onboarding_result['links'] as $link) {
                    if (isset($link['rel']) && 'action_url' === $link['rel']) {
                        return $link['href'] ?? false;
                    }
                }
            } else {
                return false;
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_get_signup_link_for_migration($testmode, $products) {
        try {
            $products_data = json_decode(urldecode($products));
            if (!empty($products_data) && is_array($products_data)) {
                foreach (WC()->payment_gateways->get_available_payment_gateways() as $gateway) {
                    if (in_array($gateway->id, $products_data) && 'yes' === $gateway->enabled && $gateway->is_available() === true) {
                        if (isset($gateway->testmode)) {
                            $testmode = ($gateway->testmode === true) ? 'yes' : 'no';
                            break;
                        }
                    }
                }
            }
            $seller_onboarding_result = $this->seller_onboarding->angelleye_generate_signup_link_for_migration($testmode, $products);
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

    public function display_view() {
        try {
            if (isset($_GET['migration_action']) && 'angelleye_ppcp_revert_changes' === $_GET['migration_action']) {
                try {
                    $result = $this->angelleye_ppcp_get_result_migrate_to_ppcp();
                    $payment_gateways = WC()->payment_gateways->payment_gateways();
                    if (!empty($result[0])) {
                        if (!class_exists('AngellEYE_PayPal_PPCP_Migration_Revert')) {
                            include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-migration-revert.php');
                        }
                        $this->ppcp_migration_revert = AngellEYE_PayPal_PPCP_Migration_Revert::instance();
                        foreach ($result as $key => $product_obj) {
                            if (isset($payment_gateways[$product_obj['Old Payment Method']])) {
                                $product = $product_obj['Old Payment Method'];
                                $this->setting_obj->set('enabled', 'no');
                                $this->setting_obj->persist();
                                switch ($product) {
                                    case 'paypal_express':
                                        $this->ppcp_migration_revert->angelleye_ppcp_to_paypal_express();
                                        break;
                                    case 'paypal_pro':
                                        $this->ppcp_migration_revert->angelleye_ppcp_to_paypal_pro();
                                        break;
                                    case 'paypal_pro_payflow':
                                        $this->ppcp_migration_revert->angelleye_ppcp_to_paypal_pro_payflow();
                                        break;
                                    case 'paypal_advanced':
                                        $this->ppcp_migration_revert->angelleye_ppcp_to_paypal_advanced();
                                        break;
                                    case 'paypal_credit_card_rest':
                                        $this->ppcp_migration_revert->angelleye_ppcp_to_paypal_credit_card_rest();
                                        break;
                                    case 'paypal':
                                        $this->ppcp_migration_revert->angelleye_ppcp_to_paypal();
                                        break;
                                    case 'ppec_paypal':
                                        $this->ppcp_migration_revert->angelleye_ppcp_to_ppec_paypal();
                                        break;
                                    default:
                                        break;
                                }
                            }
                        }
                    }
                    $this->angelleye_ppcp_revert_back_to_original_payment_method();
                    $this->angelleye_ppcp_migration_wizard_notice_data['success'][] = __('The migration has been successfully reverted! Starting from the next payment cycle, your original payment method will be used for all transactions.');
                    update_option($this->angelleye_ppcp_migration_wizard_notice_key, $this->angelleye_ppcp_migration_wizard_notice_data);
                    $redirect_url = admin_url('options-general.php?page=paypal-for-woocommerce');
                    unset($_GET);
                    wp_safe_redirect($redirect_url, 302);
                    exit();
                } catch (Exception $ex) {
                    $redirect_url = admin_url('options-general.php?page=paypal-for-woocommerce');
                    unset($_GET);
                    wp_safe_redirect($redirect_url, 302);
                    exit();
                }
            }
            $this->angelleye_ppcp_load_variable();
            $angelleye_classic_gateway_id_list = array('paypal_express', 'paypal_pro', 'paypal_pro_payflow', 'paypal_advanced', 'paypal_credit_card_rest', 'paypal', 'ppec_paypal');
            $active_classic_gateway_list = array();
            foreach (WC()->payment_gateways->get_available_payment_gateways() as $gateway) {
                if (in_array($gateway->id, $angelleye_classic_gateway_id_list) && 'yes' === $gateway->enabled && $gateway->is_available() === true) {
                    $active_classic_gateway_list[$gateway->id] = $gateway->id;
                }
            }
            $other_payment_methods = $this->angelleye_ppcp_get_other_payment_methods();
            if (!empty($other_payment_methods)) {
                foreach ($other_payment_methods as $gateway_id) {
                    if (in_array($gateway_id, array('paypal', 'ppec_paypal'))) {
                        //$active_classic_gateway_list[$gateway_id] = $gateway_id;
                    }
                }
            }
            if (count($active_classic_gateway_list) > 0) {
                $paypal_vault_supported_country = angelleye_ppcp_apple_google_vault_supported_country();
                if (in_array($this->ppcp_paypal_country, $paypal_vault_supported_country) && $this->subscription_support_enabled) {
                    $this->migration_view($active_classic_gateway_list);
                } elseif (!in_array($this->ppcp_paypal_country, $paypal_vault_supported_country) && $this->subscription_support_enabled) {
                    $this->view();
                } elseif (in_array($this->ppcp_paypal_country, $paypal_vault_supported_country) && $this->subscription_support_enabled === false) {
                    $this->migration_view($active_classic_gateway_list);
                } elseif (!in_array($this->ppcp_paypal_country, $paypal_vault_supported_country) && $this->subscription_support_enabled === false) {
                    $this->migration_view($active_classic_gateway_list);
                } else {
                    $this->view();
                }
            } else {
                $this->view();
            }
            
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_get_other_payment_methods() {
        try {
            global $wpdb;
            if (OrderUtil::custom_orders_table_usage_is_enabled()) {
                $payment_methods = $wpdb->get_col("SELECT DISTINCT payment_method FROM {$wpdb->prefix}wc_orders WHERE status IN ('wc-active', 'wc-on-hold') AND type = 'shop_subscription';");
            } else {
                $payment_methods = $wpdb->get_col("SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_payment_method' AND post_id IN (
                        SELECT ID
                        FROM {$wpdb->posts}
                        WHERE post_type = 'shop_subscription'
                        AND post_status IN ('wc-active', 'wc-on-hold')
                    )
                ");
            }

            return $payment_methods;
        } catch (Exception $ex) {
            
        }
    }

    public function migration_view($active_classic_gateway_list) {
        try {
            ?>
            <style type="text/css">
                .angelleye-tool.nav-tab {
                    display: none;
                }
            </style>
            <?php
            wp_enqueue_style('ppcp_account_request_form_css', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/css/angelleye-ppcp-admin-migration.css', null, time());
            $layout_type = '';
            if ((isset($active_classic_gateway_list['paypal_express']) || isset($active_classic_gateway_list['ppec_paypal'])) && (isset($active_classic_gateway_list['paypal_pro']) || isset($active_classic_gateway_list['paypal_pro_payflow']))) {
                $layout_type = 'paypal_express_paypal_pro';
            } elseif ((isset($active_classic_gateway_list['paypal_express']) || isset ($active_classic_gateway_list['ppec_paypal'])) && isset($active_classic_gateway_list['paypal_credit_card_rest'])) {
                $layout_type = 'paypal_express_paypal_credit_card_rest';
            } elseif ((isset($active_classic_gateway_list['paypal_pro']) || isset($active_classic_gateway_list['paypal_pro_payflow']))) {
                $layout_type = 'paypal_pro';
            } elseif (isset($active_classic_gateway_list['paypal_credit_card_rest'])) {
                $layout_type = 'paypal_credit_card_rest';
            } elseif (isset($active_classic_gateway_list['paypal_advanced'])) {
                $layout_type = 'paypal_advanced';
            } elseif (isset($active_classic_gateway_list['paypal_express'])) {
                $layout_type = 'paypal_express';
            } elseif (isset($active_classic_gateway_list['paypal'])) {
                $layout_type = 'paypal_express';
            } elseif (isset($active_classic_gateway_list['ppec_paypal'])) {
                $layout_type = 'paypal_express';
            }
            $footer_note = ' All of PayPal’s new features and functionality will be released on the '. AE_PPCP_NAME . ' Platform.  The Classic Gateways are no longer officially supported.  Please update by <strong>April 30th, 2024</strong> in order to avoid potential interruptions.';
            $footer_note .= '<br /><br /> For more details about the new fee structure please review our <a target="_blank" href="https://www.angelleye.com/woocommerce-complete-payments-paypal-angelleye-fees/">pricing page</a>.';
            $products = urlencode(wp_json_encode(array_values($active_classic_gateway_list)));
            if (!empty($layout_type)) {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/template/migration/ppcp_header.php');
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/template/migration/ppcp_' . $layout_type . '.php');
            }

            $result = $this->angelleye_ppcp_get_result_migrate_to_ppcp();
            if (!empty($result)) {
                if (!get_user_meta(get_current_user_id(), 'ppcp_migration_report')) :
                    ?>
                    <div class="paypal_woocommerce_product paypal_woocommerce_product_onboard ppcp_migration_report_parent" style="margin-top:30px;width: 57em;">
                        <button type="button" class="angelleye-notice ppcp-dismiss angelleye-notice-dismiss" data-msg="ppcp_migration_report"><span class="screen-reader-text">Dismiss this notice.</span></button>
                        <div class="ppcp_migration_report">
                            <h3 style="margin:1em 0;"><?php echo __('Subscription Migration Report', 'paypal-for-woocommerce'); ?></h3>
                            <div class="wrap" style="margin-bottom: 20px;margin-top: -10px;">
                                This report outlines all of the active / on hold subscription profiles that were updated as a part of this migration wizard.
                                If you feel you need to, you can use the "Revert Changes" button to undue this migration.
                                This will reset the payment gateway(s) and subscription profiles to use PayPal Classic again as if the migration never happened.
                            </div>
                            <div class="wrap">
                                <?php
                                echo $this->angelleye_ppcp_build_html($result);
                                ?>
                            </div>
                            <a class="wplk-button angelleye_ppcp_revert_changes" href="<?php echo admin_url('options-general.php?page=paypal-for-woocommerce&migration_action=angelleye_ppcp_revert_changes'); ?>">Revert Changes</a>
                        </div>
                    </div>
                    <?php
                endif;
            }
            if (as_has_scheduled_action('angelleye_ppcp_migration_schedule')) {
                do_action('angelleye_ppcp_migration_progress_report');
            }
        } catch (Exception $ex) {
            
        }
    }

    public function view() {

        try {
            $this->angelleye_ppcp_load_variable();
            $ae_ppcp_account_reconnect_notice = get_option('ae_ppcp_account_reconnect_notice');
            include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/template/migration/ppcp_header.php');
            ?>
            <div id="angelleye_paypal_marketing_table">
               <?php if ($this->on_board_status === 'NOT_CONNECTED' || $this->on_board_status === 'USED_FIRST_PARTY') { ?>
                    <div class="paypal_woocommerce_product">
                        <div class="paypal_woocommerce_product_onboard" style="text-align:center;">
                            <span class="ppcp_onbard_icon"><img width="150px" class="image" src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/ppcp_admin_onbard_icon.png'; ?>"></span>
                            <br><br>
                            <div class="paypal_woocommerce_product_onboard_content">
                                <p><?php echo sprintf(__('Welcome to the %s solution for WooCommerce. <br> Built by Angelleye.', 'paypal-for-woocommerce'), AE_PPCP_NAME); ?></p>
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
                                <p class="ppcp_paypal_fee"><?php echo sprintf(__('Increase average order totals and conversion rates with <br>PayPal Checkout, PayPal Credit, Buy Now Pay Later, Venmo, and more! <br>All for a total PayPal + Angelleye fee of only %s.', 'paypal-for-woocommerce'), $this->angelleye_ppcp_get_paypal_fee_structure($this->ppcp_paypal_country, 'paypal')); ?>
                                    <br><br>
                                    <?php if ($this->ppcp_paypal_country === 'DE') { ?>
                                        <?php echo sprintf(__('Fees on Visa/MasterCard/Discover transactions <br>transactions are a total PayPal + Angelleye fee of only %s.', 'paypal-for-woocommerce'), $this->angelleye_ppcp_get_paypal_fee_structure($this->ppcp_paypal_country, 'acc')); ?>
                                    <?php } else { ?>
                                        <?php echo sprintf(__('Save money on Visa/MasterCard/Discover transactions <br>with a total PayPal + Angelleye fee of only %s.', 'paypal-for-woocommerce'), $this->angelleye_ppcp_get_paypal_fee_structure($this->ppcp_paypal_country, 'acc')); ?>
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
                    $paypal_vault_supported_country = angelleye_ppcp_apple_google_vault_supported_country();
                    ?>
                    <div class="paypal_woocommerce_product">
                        <div class="paypal_woocommerce_product_onboard" style="text-align:center;">
                            <span class="ppcp_onbard_icon"><img width="150px" class="image" src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/ppcp_admin_onbard_icon.png'; ?>"></span>
                            <br><br>
                            <div class="paypal_woocommerce_product_onboard_content">
                                <br>
                                <span><img class="green_checkmark" src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/green_checkmark.png'; ?>"></span>
                                <p><?php echo sprintf(__('You’re currently set up and enjoying the benefits of %s. <br> Built by Angelleye.', 'paypal-for-woocommerce'), AE_PPCP_NAME); ?></p>
                                <p><?php echo sprintf(__('However, we need additional verification to approve you for the reduced <br>rate of %s on debit/credit cards.', 'paypal-for-woocommerce'), $this->angelleye_ppcp_get_paypal_fee_structure($this->ppcp_paypal_country, 'acc')); ?></p>
                                <p><?php echo __('To apply for a reduced rate, modify your setup, <br>or learn more about additional options, please use the buttons below.', 'paypal-for-woocommerce'); ?></p>
                                <?php if ($this->is_paypal_vault_approved === false &&  in_array($this->ppcp_paypal_country, $paypal_vault_supported_country)) { ?>
                                    <p><?php echo __('Your PayPal account is not approved for the Vault functionality<br>which is required for Subscriptions (token payments). <br>Please Reconnect your PayPal account to apply for this feature.', 'paypal-for-woocommerce'); ?></p>
                                <?php } ?>
                                <br>
                                <a class="green-button open_ppcp_account_request_form" ><?php echo __('Apply for Cheaper Fees!', 'paypal-for-woocommerce'); ?></a>
                                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=angelleye_ppcp'); ?>" class="wplk-button"><?php echo __('Modify Setup', 'paypal-for-woocommerce'); ?></a>
                                <?php
                                if (isset($_GET['testmode'])) {
                                    $testmode = ($_GET['testmode'] === 'yes') ? 'yes' : 'no';
                                } else {
                                    $testmode = $this->sandbox ? 'yes' : 'no';
                                }
                                if ($this->is_paypal_vault_approved === false && in_array($this->ppcp_paypal_country, $paypal_vault_supported_country)) {
                                    $signup_link = $this->angelleye_get_signup_link_for_vault($testmode, 'admin_settings_onboarding');
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
                                } else if (!empty($ae_ppcp_account_reconnect_notice)) {
                                    $this->print_general_reconnect_paypal_account_section($testmode);
                                }
                                ?>
                                <a href="https://www.angelleye.com/paypal-commerce-platform-setup-guide/" class="slate_gray" target="_blank"><?php echo __('Learn More', 'paypal-for-woocommerce'); ?></a>
                                <br><br>
                            </div>
                        </div>
                    </div>
                    <?php
                } elseif ($this->on_board_status === 'FULLY_CONNECTED') {
                    $is_apple_pay_approved = $this->seller_onboarding->angelleye_is_apple_pay_approved($this->result);
                    if ($is_apple_pay_approved) {
                        AngellEYE_PayPal_PPCP_Apple_Pay_Configurations::autoRegisterDomain();
                    }
                    $paypal_vault_supported_country = angelleye_ppcp_apple_google_vault_supported_country();
                    ?>
                    <div class="paypal_woocommerce_product">
                        <div class="paypal_woocommerce_product_onboard" style="text-align:center;">
                            <span class="ppcp_onbard_icon"><img width="150px" class="image" src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/ppcp_admin_onbard_icon.png'; ?>"></span>
                            <br><br>
                            <div class="paypal_woocommerce_product_onboard_content">
                                <br>
                                <span><img class="green_checkmark" src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/green_checkmark.png'; ?>"></span>
                                <p><?php echo sprintf(__('You’re currently set up and enjoying the benefits of %s. <br> Built by Angelleye.', 'paypal-for-woocommerce'), AE_PPCP_NAME); ?></p>
                                <p><?php echo __('To modify your setup or learn more about additional options, <br> please use the buttons below.', 'paypal-for-woocommerce'); ?></p>
                                <?php if ($this->is_paypal_vault_approved === false && in_array($this->ppcp_paypal_country, $paypal_vault_supported_country)) { ?>
                                    <p><?php echo __('Your PayPal account is not approved for the Vault functionality<br>which is required for Subscriptions (token payments). <br>Please Reconnect your PayPal account to apply for this feature.', 'paypal-for-woocommerce'); ?></p>
                                <?php } ?>
                                <br>
                                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=angelleye_ppcp'); ?>" class="wplk-button"><?php echo __('Modify Setup', 'paypal-for-woocommerce'); ?></a>
                                <?php
                                if (isset($_GET['testmode'])) {
                                    $testmode = ($_GET['testmode'] === 'yes') ? 'yes' : 'no';
                                } else {
                                    $testmode = $this->sandbox ? 'yes' : 'no';
                                }
                                if ($this->is_paypal_vault_approved === false && in_array($this->ppcp_paypal_country, $paypal_vault_supported_country)) {
                                    $signup_link = $this->angelleye_get_signup_link_for_vault($testmode, 'admin_settings_onboarding');
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
                                } elseif (!empty($ae_ppcp_account_reconnect_notice)) {
                                    $this->print_general_reconnect_paypal_account_section($testmode);
                                }
                                ?>
                                <a href="https://www.angelleye.com/paypal-commerce-platform-setup-guide/" class="slate_gray" target="_blank"><?php echo __('Learn More', 'paypal-for-woocommerce'); ?></a>
                                <br><br>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <?php
                $result = $this->angelleye_ppcp_get_result_migrate_to_ppcp();
                if (!empty($result)) {
                    if (!get_user_meta(get_current_user_id(), 'ppcp_migration_report')) :
                        ?>
                        <div class="paypal_woocommerce_product paypal_woocommerce_product_onboard ppcp_migration_report_parent" style="margin-top:30px;">
                            <button type="button" class="angelleye-notice ppcp-dismiss angelleye-notice-dismiss" data-msg="ppcp_migration_report"><span class="screen-reader-text">Dismiss this notice.</span></button>
                            <div class="ppcp_migration_report">
                                <h3><?php echo __('Subscription Migration Report', 'paypal-for-woocommerce'); ?></h3>
                                <div class="wrap" style="margin-bottom: 20px;margin-top: -10px;">
                                    This report outlines all of the active / on hold subscription profiles that were updated as a part of this migration wizard.
                                    If you feel you need to, you can use the "Revert Changes" button to undue this migration.
                                    This will reset the payment gateway(s) and subscription profiles to use PayPal Classic again as if the migration never happened.
                                </div>
                                <div class="wrap">
                                    <?php
                                    echo $this->angelleye_ppcp_build_html($result);
                                    ?>
                                </div>
                                <a class="wplk-button angelleye_ppcp_revert_changes" href="<?php echo admin_url('options-general.php?page=paypal-for-woocommerce&migration_action=angelleye_ppcp_revert_changes'); ?>">Revert Changes</a>
                            </div>
                        </div>
                        <?php
                    endif;
                }
                if (as_has_scheduled_action('angelleye_ppcp_migration_schedule')) {
                    do_action('angelleye_ppcp_migration_progress_report');
                }
                ?>
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
                        <a class="wplk-button" href="https://www.angelleye.com/paypal-commerce-platform-setup-guide/" target="_blank"><?php echo __('Plugin Documentation', 'paypal-for-woocommerce'); ?></a>
                    </li>
                </ul>
            </div>
            <?php
        } catch (Exception $ex) {
            
        }
    }

    public function print_general_reconnect_paypal_account_section($testmode) {
        $signup_link = $this->angelleye_get_signup_link($testmode, 'admin_settings_onboarding');
        if ($signup_link) {
            $args = array(
                'displayMode' => 'minibrowser',
            );
            $url = add_query_arg($args, $signup_link);
            ?>
            <a target="_blank" class="green-button" id="<?php echo esc_attr('wplk-button'); ?>" data-paypal-onboard-complete="generalOnboardingCallback" href="<?php echo esc_url($url); ?>" data-paypal-button="true"><?php echo __('Reconnect PayPal Account', 'paypal-for-woocommerce'); ?></a>
            <?php
            $script_url = 'https://www.paypal.com/webapps/merchantboarding/js/lib/lightbox/partner.js';
            ?>
            <script type="text/javascript">
                                        document.querySelectorAll('[data-paypal-onboard-complete=generalOnboardingCallback]').forEach((element) => {
                                            element.addEventListener('click', (e) => {
                                                if ('undefined' === typeof PAYPAL) {
                                                    e.preventDefault();
                                                    alert('PayPal error');
                                                }
                                            });
                                        });</script>
            <script id="paypal-js" src="<?php echo esc_url($script_url); ?>"></script> <?php
        } else {
            echo __('We could not properly connect to PayPal', 'paypal-for-woocommerce');
        }
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

    public function angelleye_ppcp_generate_onboard_button($products = null) {
        try {
            if (isset($_GET['testmode'])) {
                $testmode = ($_GET['testmode'] === 'yes') ? 'yes' : 'no';
            } else {
                $testmode = $this->sandbox ? 'yes' : 'no';
            }
            $signup_link = $this->angelleye_get_signup_link_for_migration($testmode, $products);
            if ($signup_link) {
                $args = array(
                    'displayMode' => 'minibrowser',
                );
                $url = add_query_arg($args, $signup_link);
                ?>
                <h3 style="text-align: center; font-weight: 600; font-size: 16px;">Log in to upgrade to the <?php echo AE_PPCP_NAME; ?> Platform</h3>
                <a class="update_to_paypal_commerce_platform" data-paypal-onboard-complete="onboardingCallback" href="<?php echo esc_url($url); ?>" data-paypal-button="true"><img alt="Qries" src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/admin/update_to_paypal_commerce_platform.png'; ?>"></a>
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
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_build_html($results) {
        $html = '<table class="widefat striped fixed">';
        $html .= '<tbody><tr>';
        foreach ($results[0] as $key => $value) {
            $html .= '<th><b>' . htmlspecialchars($key) . '</b></th>';
        }
        $html .= '</tr>';
        $payment_gateways = WC()->payment_gateways->payment_gateways();
        foreach ($results as $key => $result) {
            $html .= '<tr>';
            foreach ($result as $key => $value) {
                if (isset($payment_gateways[$value])) {
                    if (isset($payment_gateways[$value]->method_title)) {
                        $html .= '<td>' . htmlspecialchars($payment_gateways[$value]->method_title) . '</td>';
                    } else {
                        $html .= '<td>' . htmlspecialchars($value) . '</td>';
                    }
                } else {
                    $html .= '<td>' . htmlspecialchars($value) . '</td>';
                }
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';
        return $html;
    }
    
    

    public function angelleye_ppcp_get_result_migrate_to_ppcp() {
        global $wpdb;
        try {
            if (OrderUtil::custom_orders_table_usage_is_enabled()) {
                $payment_methods = $wpdb->get_results("SELECT pm2.meta_value AS 'Old Payment Method', p.payment_method AS 'New Payment Method', COUNT(DISTINCT p.id) AS 'Total Subscription'
                FROM {$wpdb->prefix}wc_orders p
                JOIN {$wpdb->prefix}wc_orders_meta pm2 ON p.id = pm2.order_id AND pm2.meta_key = '_old_payment_method'
                JOIN {$wpdb->prefix}wc_orders_meta pm3 ON p.id = pm3.order_id AND pm3.meta_key = '_angelleye_ppcp_old_payment_method'
                WHERE p.status IN ('wc-active', 'wc-on-hold')
                AND p.payment_method != pm2.meta_value
                GROUP BY pm2.meta_value, p.payment_method;", ARRAY_A);
                return $payment_methods;
            } else {
                $payment_methods = $wpdb->get_results("SELECT pm2.meta_value AS 'Old Payment Method', pm.meta_value AS 'New Payment Method', COUNT(DISTINCT p.ID) AS 'Total Subscription'
                FROM {$wpdb->posts} p
                JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_payment_method'
                JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_old_payment_method'
                JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_angelleye_ppcp_old_payment_method'
                WHERE p.post_type = 'shop_subscription'
                AND pm.meta_value != pm2.meta_value
                GROUP BY pm2.meta_value, pm.meta_value;", ARRAY_A);
                return $payment_methods;
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_revert_back_to_original_payment_method() {
        global $wpdb;
        try {
            if (OrderUtil::custom_orders_table_usage_is_enabled()) {
                $wpdb->query("UPDATE {$wpdb->prefix}wc_orders AS orders
                    LEFT JOIN {$wpdb->prefix}wc_orders_meta AS pm1 ON orders.id = pm1.order_id AND pm1.meta_key = '_old_payment_method'
                    LEFT JOIN {$wpdb->prefix}wc_orders_meta AS pm2 ON orders.id = pm2.order_id AND pm2.meta_key = '_old_payment_method_title'
                    INNER JOIN (
                        SELECT order_id
                        FROM {$wpdb->prefix}wc_orders_meta
                        WHERE meta_key = '_angelleye_ppcp_old_payment_method'
                    ) AS subquery1 ON orders.id = subquery1.order_id
                    SET orders.payment_method = IFNULL(pm1.meta_value, orders.payment_method),
                        orders.payment_method_title = IFNULL(pm2.meta_value, orders.payment_method_title)
                    WHERE orders.payment_method IS NOT NULL OR orders.payment_method_title IS NOT NULL;");
                $wpdb->query("DELETE FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key = '_angelleye_ppcp_old_payment_method';");
            } else {
                $wpdb->query("UPDATE {$wpdb->postmeta} AS pm1
                LEFT JOIN {$wpdb->postmeta} AS pm2 ON pm1.post_id = pm2.post_id AND pm2.meta_key = '_old_payment_method'
                LEFT JOIN {$wpdb->postmeta} AS pm3 ON pm1.post_id = pm3.post_id AND pm3.meta_key = '_old_payment_method_title'
                INNER JOIN (
                    SELECT post_id
                    FROM {$wpdb->postmeta}
                    WHERE meta_key = '_angelleye_ppcp_old_payment_method'
                ) AS subquery1 ON pm1.post_id = subquery1.post_id
                INNER JOIN (
                    SELECT post_id
                    FROM {$wpdb->postmeta}
                    WHERE meta_key = '_payment_method' OR meta_key = '_payment_method_title'
                ) AS subquery2 ON pm1.post_id = subquery2.post_id
                SET pm1.meta_value =
                    CASE pm1.meta_key
                        WHEN '_payment_method' THEN IFNULL(pm2.meta_value, pm1.meta_value)
                        WHEN '_payment_method_title' THEN IFNULL(pm3.meta_value, pm1.meta_value)
                    END
                WHERE pm1.meta_key IN ('_payment_method', '_payment_method_title')");
                $wpdb->query("DELETE FROM {$wpdb->postmeta}
                WHERE meta_key = '_angelleye_ppcp_old_payment_method'
                AND post_id IN (
                    SELECT post_id
                    FROM (
                        SELECT post_id
                        FROM {$wpdb->postmeta}
                        WHERE meta_key = '_payment_method' OR meta_key = '_payment_method_title'
                    ) AS subquery
                )");
            }
        } catch (Exception $ex) {
            
        }
    }
}
