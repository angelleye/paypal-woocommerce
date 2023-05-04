<?php

class WC_Gateway_PPCP_AngellEYE extends WC_Payment_Gateway {

    public $setting_obj;
    public $settings_fields;
    public $api_log;
    public $dcc_applies;
    public $api_request;
    public $seller_onboarding;
    public $payment_request;
    public $advanced_card_payments;
    public $checkout_disable_smart_button;
    public $minified_version;

    public function __construct() {
        try {
            $this->id = 'angelleye_ppcp';
            $this->setup_properties();
            $this->angelleye_ppcp_load_class();
            $this->init_form_fields();
            $this->init_settings();
            $this->angelleye_get_settings();
            $this->angelleye_defind_hooks();
            if (angelleye_ppcp_has_active_session()) {
                $this->order_button_text = apply_filters('angelleye_ppcp_order_review_page_place_order_button_text', __('Complete Order Payment', 'paypal-for-woocommerce'));
            }
            $this->enable_tokenized_payments = 'yes' === $this->get_option('enable_tokenized_payments', 'no');
            if (isset($_GET['paypal_order_id']) && isset($_GET['paypal_payer_id']) && $this->enable_tokenized_payments) {
                $this->supports = array(
                    'products',
                    'refunds',
                    'pay_button',
                    'subscriptions',
                    'subscription_cancellation',
                    'subscription_reactivation',
                    'subscription_suspension',
                    'subscription_amount_changes',
                    'subscription_payment_method_change', // Subs 1.n compatibility.
                    'subscription_payment_method_change_customer',
                    'subscription_payment_method_change_admin',
                    'subscription_date_changes',
                    'multiple_subscriptions',
                    'add_payment_method'
                );
            } elseif ($this->enable_tokenized_payments) {
                $this->supports = array(
                    'products',
                    'refunds',
                    'pay_button',
                    'subscriptions',
                    'subscription_cancellation',
                    'subscription_reactivation',
                    'subscription_suspension',
                    'subscription_amount_changes',
                    'subscription_payment_method_change', // Subs 1.n compatibility.
                    'subscription_payment_method_change_customer',
                    'subscription_payment_method_change_admin',
                    'subscription_date_changes',
                    'multiple_subscriptions',
                    'add_payment_method',
                    'tokenization'
                );
            } elseif (isset($_GET['page']) && isset($_GET['tab']) && 'wc-settings' === $_GET['page'] && 'checkout' === $_GET['tab']) {
                $this->supports = array(
                    'products',
                    'refunds',
                    'pay_button',
                    'subscriptions',
                    'subscription_cancellation',
                    'subscription_reactivation',
                    'subscription_suspension',
                    'subscription_amount_changes',
                    'subscription_payment_method_change', // Subs 1.n compatibility.
                    'subscription_payment_method_change_customer',
                    'subscription_payment_method_change_admin',
                    'subscription_date_changes',
                    'multiple_subscriptions',
                    'add_payment_method',
                    'tokenization'
                );
            } else {
                $this->supports = array(
                    'products',
                    'refunds',
                    'pay_button'
                );
            }
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
            if (!class_exists('AngellEYE_PayPal_PPCP_Log')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-log.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Request')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-request.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_DCC_Validate')) {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-dcc-validate.php');
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Payment')) {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-payment.php');
            }
            $this->setting_obj = WC_Gateway_PPCP_AngellEYE_Settings::instance();
            $this->setting_obj_fields = $this->setting_obj->angelleye_ppcp_setting_fields();
            $this->api_log = AngellEYE_PayPal_PPCP_Log::instance();
            $this->api_request = AngellEYE_PayPal_PPCP_Request::instance();
            $this->dcc_applies = AngellEYE_PayPal_PPCP_DCC_Validate::instance();
            $this->payment_request = AngellEYE_PayPal_PPCP_Payment::instance();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function setup_properties() {
        $this->icon = apply_filters('woocommerce_angelleye_paypal_checkout_icon', 'https://www.paypalobjects.com/webstatic/mktg/Logo/pp-logo-100px.png');
        $this->has_fields = true;
        $this->method_title = apply_filters('angelleye_ppcp_gateway_method_title', __('PayPal Commerce - Built by Angelleye', 'paypal-for-woocommerce'));
        $this->method_description = __('The easiest one-stop solution for accepting PayPal, Venmo, Debit/Credit Cards with cheaper fees than other processors!', 'paypal-for-woocommerce');
    }

    public function angelleye_get_settings() {
        $this->title = $this->get_option('title', 'PayPal');
        if (isset($_GET['page']) && 'wc-settings' === $_GET['page'] && isset($_GET['tab']) && 'checkout' === $_GET['tab']) {
            $this->title = __('PayPal Commerce - Built by Angelleye', 'paypal-for-woocommerce');
        }
        $this->description = $this->get_option('description', '');
        $this->enabled = $this->get_option('enabled', 'no');
        $this->sandbox = 'yes' === $this->get_option('testmode', 'no');
        $this->sandbox_merchant_id = $this->get_option('sandbox_merchant_id', '');
        $this->live_merchant_id = $this->get_option('live_merchant_id', '');
        $this->checkout_disable_smart_button = 'yes' === $this->get_option('checkout_disable_smart_button', 'no');
        $this->sandbox_client_id = $this->get_option('sandbox_client_id', '');
        $this->sandbox_secret_id = $this->get_option('sandbox_api_secret', '');
        $this->live_client_id = $this->get_option('api_client_id', '');
        $this->live_secret_id = $this->get_option('api_secret', '');
        $this->soft_descriptor = $this->get_option('soft_descriptor', substr(get_bloginfo('name'), 0, 21));
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
            $this->merchant_id = $this->get_option('sandbox_merchant_id', '');
            $this->client_id = $this->sandbox_client_id;
            $this->secret_id = $this->sandbox_secret_id;
        } else {
            $this->merchant_id = $this->get_option('live_merchant_id', '');
            $this->client_id = $this->live_client_id;
            $this->secret_id = $this->live_secret_id;
        }
        $this->paymentaction = $this->get_option('paymentaction', 'capture');
        $this->advanced_card_payments = 'yes' === $this->get_option('enable_advanced_card_payments', 'no');
        $this->enable_tokenized_payments = 'yes' === $this->get_option('enable_tokenized_payments', 'no');
        $this->three_d_secure_contingency = $this->get_option('3d_secure_contingency', 'SCA_WHEN_REQUIRED');
        $this->is_enabled = 'yes' === $this->get_option('enabled', 'no');
        $this->minified_version = ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ) ? '' : '.min';
    }

    public function is_available() {
        if ($this->is_enabled == true) {
            if ($this->is_credentials_set()) {
                return true;
            }
            return false;
        } else {
            return false;
        }
    }

    public function angelleye_defind_hooks() {
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('woocommerce_admin_order_totals_after_total', array($this, 'angelleye_ppcp_display_order_fee'));
        if (apply_filters('woocommerce_checkout_show_terms', true) && function_exists('wc_terms_and_conditions_checkbox_enabled') && wc_terms_and_conditions_checkbox_enabled()) {
            add_action('woocommerce_review_order_before_submit', array($this, 'ppcp_payment_fields'));
        }
        if ($this->enable_tokenized_payments === false) {
            add_filter('woocommerce_payment_gateways_renewal_support_status_html', array($this, 'payment_gateways_support_tooltip'), 10, 1);
        }
    }

    public function process_admin_options() {
        delete_transient(AE_FEE);
        parent::process_admin_options();
    }

    public function admin_options() {
        $this->angelleye_ppcp_admin_notices();
        wp_deregister_script('woocommerce_settings');
        wp_enqueue_script('wc-clipboard');
        echo '<div id="angelleye_paypal_marketing_table">';
        parent::admin_options();
        echo '</div>';
        AngellEYE_Utility::angelleye_display_marketing_sidebar($this->id);
    }

    public function init_form_fields() {
        try {
            $this->form_fields = $this->setting_obj_fields;
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function payment_fields() {
        if ($this->supports('tokenization')) {
            $this->tokenization_script();
        }
        $description = $this->get_description();
        if ($description) {
            echo wpautop(wp_kses_post($description));
        }
        if (is_checkout() && angelleye_ppcp_get_order_total() === 0) {
            if (angelleye_ppcp_get_order_total() === 0 && angelleye_ppcp_is_cart_subscription() === true || angelleye_ppcp_is_subs_change_payment() === true) {
                if (count($this->get_tokens()) > 0) {
                    $this->saved_payment_methods();
                }
            }
        } elseif (angelleye_ppcp_is_subs_change_payment() === true) {
            if (count($this->get_tokens()) > 0) {
                $this->saved_payment_methods();
            }
        }

        if ($this->checkout_disable_smart_button === false && angelleye_ppcp_get_order_total() > 0 && angelleye_ppcp_is_subs_change_payment() === false) {
            do_action('angelleye_ppcp_display_paypal_button_checkout_page');
            if (angelleye_ppcp_is_cart_subscription() === false && $this->enable_tokenized_payments) {
                if ($this->supports('tokenization') && is_account_page() === false) {
                    $html = '<ul class="woocommerce-SavedPaymentMethods wc-saved-payment-methods" data-count="">';
                    $html .= '</ul>';
                    echo $html;
                    $this->save_payment_method_checkbox();
                }
            }
        }
    }

    public function ppcp_payment_fields($bool = true) {
        echo '<div id="ppcp_payment_field_bottom">';
        if ($this->checkout_disable_smart_button === false) {
            do_action('angelleye_ppcp_display_paypal_button_checkout_page');
        }
        echo '</div>';
    }

    public function form() {
        wp_enqueue_script('wc-credit-card-form');
        $fields = array();
        $cvc_field = '<div class="form-row form-row-last">
                        <label for="' . esc_attr($this->id) . '-card-cvc">' . apply_filters('cc_form_label_card_code', __('Card Security Code', 'paypal-for-woocommerce'), $this->id) . ' </label>
                        <div id="' . esc_attr($this->id) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc hosted-field-braintree"></div>
                    </div>';
        $default_fields = array(
            'card-number-field' => '<div class="form-row form-row-wide">
                        <label for="' . esc_attr($this->id) . '-card-number">' . apply_filters('cc_form_label_card_number', __('Card number', 'paypal-for-woocommerce'), $this->id) . '</label>
                        <div id="' . esc_attr($this->id) . '-card-number"  class="input-text wc-credit-card-form-card-number hosted-field-braintree"></div>
                    </div>',
            'card-expiry-field' => '<div class="form-row form-row-first">
                        <label for="' . esc_attr($this->id) . '-card-expiry">' . apply_filters('cc_form_label_expiry', __('Expiration Date', 'paypal-for-woocommerce'), $this->id) . ' </label>
                        <div id="' . esc_attr($this->id) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry hosted-field-braintree"></div>
                    </div>',
        );
        if (!$this->supports('credit_card_form_cvc_on_saved_method')) {
            $default_fields['card-cvc-field'] = $cvc_field;
        }
        $fields = wp_parse_args($fields, apply_filters('woocommerce_credit_card_form_fields', $default_fields, $this->id));
        ?>
        <fieldset id="wc-<?php echo esc_attr($this->id); ?>-cc-form" class='wc-credit-card-form wc-payment-form' >
            <?php do_action('woocommerce_credit_card_form_start', $this->id); ?>
            <?php
            foreach ($fields as $field) {
                echo $field;
            }
            ?>
            <?php do_action('woocommerce_credit_card_form_end', $this->id); ?>
            <div class="clear"></div>
        </fieldset>
        <?php
        if ($this->supports('credit_card_form_cvc_on_saved_method')) {
            echo '<fieldset>' . $cvc_field . '</fieldset>';
        }
    }

    public function is_valid_for_use() {
        return in_array(
                get_woocommerce_currency(), apply_filters(
                        'woocommerce_paypal_supported_currencies', array('AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP', 'RMB', 'RUB', 'INR')
                ), true
        );
    }

    public function is_credentials_set() {

        if (!empty($this->merchant_id) || (!empty($this->client_id) && !empty($this->secret_id))) {
            return true;
        } else {
            return false;
        }
    }

    public function enqueue_scripts() {
        if (isset($_GET['section']) && 'angelleye_ppcp' === $_GET['section']) {
            wp_enqueue_style('wc-gateway-ppcp-angelleye-settings-css', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/css/angelleye-ppcp-gateway-admin' . $this->minified_version . '.css', array(), VERSION_PFW, 'all');
            wp_enqueue_script('wc-gateway-ppcp-angelleye-settings', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/js/wc-gateway-ppcp-angelleye-settings' . $this->minified_version . '.js', array('jquery'), VERSION_PFW, true);
            wp_localize_script('wc-gateway-ppcp-angelleye-settings', 'ppcp_angelleye_param', array(
                'angelleye_ppcp_is_local_server' => ( angelleye_ppcp_is_local_server() == true) ? 'yes' : 'no',
                'angelleye_ppcp_onboarding_endpoint' => WC_AJAX::get_endpoint('ppcp_login_seller'),
                'angelleye_ppcp_onboarding_endpoint_nonce' => wp_create_nonce('ppcp_login_seller'),
                'is_sandbox_first_party_used' => $this->is_sandbox_first_party_used,
                'is_sandbox_third_party_used' => $this->is_sandbox_third_party_used,
                'is_live_first_party_used' => $this->is_live_first_party_used,
                'is_live_third_party_used' => $this->is_live_third_party_used,
                'is_advanced_card_payments' => ($this->dcc_applies->for_country_currency() === false) ? 'no' : 'yes',
                'woocommerce_enable_guest_checkout' => get_option('woocommerce_enable_guest_checkout', 'yes'),
                'disable_terms' => ( apply_filters('woocommerce_checkout_show_terms', true) && function_exists('wc_terms_and_conditions_checkbox_enabled') && wc_terms_and_conditions_checkbox_enabled() && get_option('woocommerce_enable_guest_checkout', 'yes') === 'yes') ? 'yes' : 'no'
                    )
            );
        }
        wp_enqueue_script('wc-gateway-ppcp-angelleye-settings-list', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/js/wc-gateway-ppcp-angelleye-settings-list' . $this->minified_version . '.js', array('jquery'), VERSION_PFW, true);
    }

    public function generate_angelleye_ppcp_text_html($field_key, $data) {
        if (isset($data['type']) && $data['type'] === 'angelleye_ppcp_text') {
            $field_key = $this->get_field_key($field_key);
            ob_start();
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?> <?php echo $this->get_tooltip_html($data); // WPCS: XSS ok.                                                                                                                                                     ?></label>
                </th>
                <td class="forminp" id="<?php echo esc_attr($field_key); ?>">
                    <div class="ppcp_paypal_connection_image">
                        <div class="ppcp_paypal_connection_image_status">
                            <img src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/images/ppcp_check_mark_status.png'; ?>" width="65" height="65">
                        </div>
                    </div>
                    <div class="ppcp_paypal_connection">
                        <div class="ppcp_paypal_connection_status">
                            <h3><?php echo __('Congratulations, PayPal Commerce is Connected!', 'paypal-for-woocommerce'); ?></h3>
                        </div>
                    </div>
                    <button type="button" class="button angelleye-ppcp-disconnect"><?php echo __('Disconnect', 'paypal-for-woocommerce'); ?></button>
                    <p class="description"><?php echo wp_kses_post($data['description']); ?></p>
                </td>
            </tr>
            <?php
            return ob_get_clean();
        }
    }

    public function generate_angelleye_ppcp_onboarding_html($field_key, $data) {
        if (isset($data['type']) && $data['type'] === 'angelleye_ppcp_onboarding') {
            $field_key = $this->get_field_key($field_key);
            $testmode = ( $data['mode'] === 'live' ) ? 'no' : 'yes';
            ob_start();
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?> <?php echo $this->get_tooltip_html($data); // WPCS: XSS ok.                                                                                                                                                     ?></label>
                </th>
                <td class="forminp" id="<?php echo esc_attr($field_key); ?>">
                    <?php
                    if (($this->is_live_first_party_used !== 'yes' && $this->is_live_third_party_used !== 'yes' && $testmode === 'no') || ($this->is_sandbox_first_party_used !== 'yes' && $this->is_sandbox_third_party_used !== 'yes' && $testmode === 'yes')) {
                        $setup_url = add_query_arg(array('testmode' => $testmode, 'utm_nooverride' => '1'), untrailingslashit(admin_url('options-general.php?page=paypal-for-woocommerce&tab=general_settings&gateway=paypal_payment_gateway_products')));
                        ?>
                        <a class="button-primary" href="<?php echo $setup_url; ?>"><?php echo __('Go To Setup', 'paypal-for-woocommerce'); ?></a>
                        <?php
                    }
                    ?>
                </td>
            </tr>
            <?php
            return ob_get_clean();
        }
    }

    public function generate_copy_text_html($key, $data) {
        $field_key = $this->get_field_key($key);
        $defaults = array(
            'title' => '',
            'disabled' => false,
            'class' => '',
            'css' => '',
            'placeholder' => '',
            'type' => 'text',
            'desc_tip' => false,
            'description' => '',
            'custom_attributes' => array(),
        );

        $data = wp_parse_args($data, $defaults);

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?> <?php echo $this->get_tooltip_html($data); // WPCS: XSS ok.                                                           ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post($data['title']); ?></span></legend>
                    <input class="input-text regular-input <?php echo esc_attr($data['class']); ?>" type="text" name="<?php echo esc_attr($field_key); ?>" id="<?php echo esc_attr($field_key); ?>" style="<?php echo esc_attr($data['css']); ?>" value="<?php echo esc_attr($this->get_option($key)); ?>" placeholder="<?php echo esc_attr($data['placeholder']); ?>" <?php disabled($data['disabled'], true); ?> <?php echo $this->get_custom_attribute_html($data); // WPCS: XSS ok.                                                           ?> />
                    <button type="button" class="button-secondary <?php echo esc_attr($data['button_class']); ?>" data-tip="Copied!">Copy</button>
                    <?php echo $this->get_description_html($data); // WPCS: XSS ok.         ?>
                </fieldset>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    public function process_payment($woo_order_id) {
        try {
            $this->paymentaction = apply_filters('angelleye_ppcp_paymentaction', $this->paymentaction, $woo_order_id);
            $angelleye_ppcp_paypal_order_id = angelleye_ppcp_get_session('angelleye_ppcp_paypal_order_id');
            $angelleye_ppcp_payment_method_title = angelleye_ppcp_get_session('angelleye_ppcp_payment_method_title');
            $angelleye_ppcp_used_payment_method = angelleye_ppcp_get_session('angelleye_ppcp_used_payment_method');
            update_post_meta($woo_order_id, '_angelleye_ppcp_used_payment_method', $angelleye_ppcp_used_payment_method);
            if (!empty($angelleye_ppcp_payment_method_title)) {
                update_post_meta($woo_order_id, '_payment_method_title', $angelleye_ppcp_payment_method_title);
            }
            $is_success = false;
            if (!empty($_POST['wc-angelleye_ppcp-payment-token']) && $_POST['wc-angelleye_ppcp-payment-token'] != 'new') {
                $order = wc_get_order($woo_order_id);
                $token_id = wc_clean($_POST['wc-angelleye_ppcp-payment-token']);
                $token = WC_Payment_Tokens::get($token_id);
                $used_payment_method = get_metadata('payment_token', $token_id, '_angelleye_ppcp_used_payment_method', true);
                update_post_meta($woo_order_id, '_angelleye_ppcp_used_payment_method', $used_payment_method);
                update_post_meta($woo_order_id, '_payment_tokens_id', $token->get_token());
                angelleye_ppcp_add_used_payment_method_name_to_subscription($woo_order_id);
                $this->payment_request->save_payment_token($order, $token->get_token());
                $is_success = $this->payment_request->angelleye_ppcp_capture_order_using_payment_method_token($woo_order_id);
                if ($is_success) {
                    WC()->cart->empty_cart();
                    unset(WC()->session->angelleye_ppcp_session);
                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url($order),
                    );
                }
                exit();
            } else {
                if (isset($_GET['from']) && 'checkout' === $_GET['from']) {
                    angelleye_ppcp_set_session('angelleye_ppcp_checkout_post', isset($_POST) ? wc_clean($_POST) : false);
                    $this->payment_request->angelleye_ppcp_create_order_request($woo_order_id);
                    exit();
                } elseif (!empty($angelleye_ppcp_paypal_order_id)) {
                    $order = wc_get_order($woo_order_id);
                    if ($this->paymentaction === 'capture') {
                        $is_success = $this->payment_request->angelleye_ppcp_order_capture_request($woo_order_id);
                    } else {
                        $is_success = $this->payment_request->angelleye_ppcp_order_auth_request($woo_order_id);
                    }
                    angelleye_ppcp_update_post_meta($order, '_paymentaction', $this->paymentaction);
                    angelleye_ppcp_update_post_meta($order, '_enviorment', ($this->sandbox) ? 'sandbox' : 'live');
                    if ($is_success) {
                        WC()->cart->empty_cart();
                        unset(WC()->session->angelleye_ppcp_session);
                        return array(
                            'result' => 'success',
                            'redirect' => $this->get_return_url($order),
                        );
                    } else {
                        unset(WC()->session->angelleye_ppcp_session);
                        return array(
                            'result' => 'failure',
                            'redirect' => wc_get_cart_url()
                        );
                    }
                } elseif ($this->checkout_disable_smart_button === true) {
                    $result = $this->payment_request->angelleye_ppcp_regular_create_order_request($woo_order_id);
                    return $result;
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function get_title() {
        try {
            $payment_method_title = '';
            if (isset($_GET['post'])) {
                $theorder = wc_get_order($_GET['post']);
                if ($theorder) {
                    $payment_method_title = angelleye_ppcp_get_post_meta($theorder, '_payment_method_title', true);
                }
            }
            if (!empty($payment_method_title)) {
                return $payment_method_title;
            } else {
                return parent::get_title();
            }
        } catch (Exception $ex) {
            
        }
    }

    public function get_transaction_url($order) {
        $enviorment = angelleye_ppcp_get_post_meta($order, '_enviorment', true);
        if ($enviorment === 'sandbox') {
            $this->view_transaction_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
        } else {
            $this->view_transaction_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
        }
        return parent::get_transaction_url($order);
    }

    public function can_refund_order($order) {
        $has_api_creds = false;
        if ($this->is_credentials_set()) {
            $has_api_creds = true;
        }
        return $order && $order->get_transaction_id() && $has_api_creds;
    }

    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        if (!$this->can_refund_order($order)) {
            return new WP_Error('error', __('Refund failed.', 'paypal-for-woocommerce'));
        }
        $transaction_id = $order->get_transaction_id();
        $bool = $this->payment_request->angelleye_ppcp_refund_order($order_id, $amount, $reason, $transaction_id);
        return $bool;
    }

    public function angelleye_ppcp_display_order_fee($order_id) {
        $order = wc_get_order($order_id);
        $payment_method = version_compare(WC_VERSION, '3.0', '<') ? $order->payment_method : $order->get_payment_method();
        if ('angelleye_ppcp' !== $payment_method) {
            return false;
        }
        $fee = angelleye_ppcp_get_post_meta($order, '_paypal_fee', true);
        $currency = angelleye_ppcp_get_post_meta($order, '_paypal_fee_currency_code', true);
        if ($order->get_status() == 'refunded') {
            return true;
        }
        ?>
        <tr>
            <td class="label stripe-fee">
                <?php echo wc_help_tip(__('This represents the fee PayPal collects for the transaction.', 'paypal-for-woocommerce')); ?>
                <?php esc_html_e('PayPal Fee:', 'paypal-for-woocommerce'); ?>
            </td>
            <td width="1%"></td>
            <td class="total">
                -&nbsp;<?php echo wc_price($fee, array('currency' => $currency)); ?>
            </td>
        </tr>
        <?php
    }

    public function get_icon() {
        $icon = $this->icon ? '<img src="' . WC_HTTPS::force_https_url($this->icon) . '" alt="' . esc_attr($this->get_title()) . '" />' : '';
        return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
    }

    public function angelleye_ppcp_admin_notices() {
        $is_saller_onboarding_done = false;
        $is_saller_onboarding_failed = false;
        if (false !== get_transient('angelleye_ppcp_sandbox_seller_onboarding_process_done')) {
            $is_saller_onboarding_done = true;
            delete_transient('angelleye_ppcp_sandbox_seller_onboarding_process_done');
        } elseif (false !== get_transient('angelleye_ppcp_live_seller_onboarding_process_done')) {
            $is_saller_onboarding_done = true;
            delete_transient('angelleye_ppcp_live_seller_onboarding_process_done');
        }
        if ($is_saller_onboarding_done) {
            echo '<div class="notice notice-success angelleye-notice is-dismissible" id="ppcp_success_notice_onboarding" style="display:none;">'
            . '<div class="angelleye-notice-logo-original">'
            . '<div class="ppcp_success_logo"><img src="' . PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/images/ppcp_check_mark.png" width="65" height="65"></div>'
            . '</div>'
            . '<div class="angelleye-notice-message">'
            . '<h3>PayPal onboarding process successfully completed.</h3>'
            . '</div>'
            . '</div>';
        } else {
            if (false !== get_transient('angelleye_ppcp_sandbox_seller_onboarding_process_failed')) {
                $is_saller_onboarding_failed = true;
                delete_transient('angelleye_ppcp_sandbox_seller_onboarding_process_failed');
            } elseif (false !== get_transient('angelleye_ppcp_live_seller_onboarding_process_failed')) {
                $is_saller_onboarding_failed = true;
                delete_transient('angelleye_ppcp_live_seller_onboarding_process_failed');
            }
            if ($is_saller_onboarding_failed) {
                echo '<div class="notice notice-error is-dismissible">'
                . '<p>We could not properly connect to PayPal. Please reload the page to continue.</p>'
                . '</div>';
            }
        }
        if (($this->is_live_first_party_used === 'yes' || $this->is_live_third_party_used === 'yes') || ($this->is_sandbox_first_party_used === 'yes' || $this->is_sandbox_third_party_used === 'yes')) {
            return false;
        }

        $message = sprintf(
                __(
                        'PayPal Commerce - Built by Angelleye is almost ready. To get started, <a href="%1$s">connect your account</a>.', 'paypal-for-woocommerce'
                ), admin_url('options-general.php?page=paypal-for-woocommerce&tab=general_settings&gateway=paypal_payment_gateway_products')
        );
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php echo $message; ?></p>
        </div>
        <?php
    }

    public function process_subscription_payment($order, $amount_to_charge) {
        try {
            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            $this->payment_request->angelleye_ppcp_capture_order_using_payment_method_token($order_id);
        } catch (Exception $ex) {
            
        }
    }

    public function subscription_change_payment($order_id) {
        try {
            if ((!empty($_POST['wc-angelleye_ppcp-payment-token']) && $_POST['wc-angelleye_ppcp-payment-token'] != 'new')) {
                $order = wc_get_order($order_id);
                $token_id = wc_clean($_POST['wc-angelleye_ppcp-payment-token']);
                $token = WC_Payment_Tokens::get($token_id);
                $used_payment_method = get_metadata('payment_token', $token_id, '_angelleye_ppcp_used_payment_method', true);
                update_post_meta($order_id, '_angelleye_ppcp_used_payment_method', $used_payment_method);
                $this->payment_request->save_payment_token($order, $token->get_token());
                return array(
                    'result' => 'success',
                    'redirect' => angelleye_ppcp_get_view_sub_order_url($order_id)
                );
            } else {
                return $this->payment_request->angelleye_ppcp_paypal_setup_tokens_sub_change_payment($order_id);
            }
        } catch (Exception $ex) {
            
        }
    }

    public function free_signup_order_payment($order_id) {
        try {
            if ((!empty($_POST['wc-angelleye_ppcp-payment-token']) && $_POST['wc-angelleye_ppcp-payment-token'] != 'new')) {
                $order = wc_get_order($order_id);
                $token_id = wc_clean($_POST['wc-angelleye_ppcp-payment-token']);
                $token = WC_Payment_Tokens::get($token_id);
                $order->payment_complete($token->get_token());
                $this->payment_request->save_payment_token($order, $token->get_token());
                WC()->cart->empty_cart();
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            }
        } catch (Exception $ex) {
            
        }
    }

    public function add_payment_method() {
        try {
            return $this->payment_request->angelleye_ppcp_paypal_setup_tokens();
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_process_free_signup_with_free_trial($order_id) {
        try {
            return $this->payment_request->angelleye_ppcp_paypal_setup_tokens_free_signup_with_free_trial($order_id);
        } catch (Exception $ex) {
            
        }
    }

    public function save_payment_method_checkbox() {
        $html = sprintf(
                '<p class="form-row woocommerce-SavedPaymentMethods-saveNew">
				<input id="wc-%1$s-new-payment-method" name="wc-%1$s-new-payment-method" type="checkbox" value="true" style="width:auto;" />
				<label for="wc-%1$s-new-payment-method" style="display:inline;">%2$s</label>
			</p>',
                esc_attr($this->id),
                esc_html__('Save payment method to my account.', 'paypal-for-woocommerce')
        );

        echo apply_filters('woocommerce_payment_gateway_save_new_payment_method_option_html', $html, $this); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    public function get_saved_payment_method_option_html($token) {
        try {
            $angelleye_ppcp_used_payment_method = get_metadata('payment_token', $token->get_id(), '_angelleye_ppcp_used_payment_method', true);
            if (!empty($angelleye_ppcp_used_payment_method)) {
                if ($angelleye_ppcp_used_payment_method === 'paypal') {
                    $image_url = PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/icon/paypal.png';
                } elseif ($angelleye_ppcp_used_payment_method === 'venmo') {
                    $image_url = PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/icon/venmo.png';
                }
                $image_path = '<img class="ppcp_payment_method_icon" src="' . $image_url . '" alt="Credit card">';
                $html = sprintf(
                        '<li class="woocommerce-SavedPaymentMethods-token">
				<input id="wc-%1$s-payment-token-%2$s" type="radio" name="wc-%1$s-payment-token" value="%2$s" style="width:auto;" class="woocommerce-SavedPaymentMethods-tokenInput" %4$s />
				<label for="wc-%1$s-payment-token-%2$s">%5$s  %3$s</label>
			</li>',
                        esc_attr($this->id),
                        esc_attr($token->get_id()),
                        esc_html($token->get_card_type()),
                        checked($token->is_default(), true, false),
                        $image_path
                );
                return apply_filters('woocommerce_payment_gateway_get_saved_payment_method_option_html', $html, $token, $this);
            } else {
                parent::get_saved_payment_method_option_html($token);
            }
        } catch (Exception $ex) {
            parent::get_saved_payment_method_option_html($token);
        }
    }

    public function generate_checkbox_enable_paypal_vault_html($key, $data) {
        if (isset($data['type']) && $data['type'] === 'checkbox_enable_paypal_vault') {
            $testmode = $this->sandbox ? 'yes' : 'no';
            $field_key = $this->get_field_key($key);
            $defaults = array(
                'title' => '',
                'label' => '',
                'disabled' => false,
                'class' => '',
                'css' => '',
                'type' => 'text',
                'desc_tip' => false,
                'description' => '',
                'custom_attributes' => array(),
            );
            $data = wp_parse_args($data, $defaults);
            if (!$data['label']) {
                $data['label'] = $data['title'];
            }
            ob_start();
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?> <?php echo $this->get_tooltip_html($data); // WPCS: XSS ok.         ?></label>
                </th>
                <td class="forminp">
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php echo wp_kses_post($data['title']); ?></span></legend>
                        <label for="<?php echo esc_attr($field_key); ?>">
                            <input <?php disabled($data['disabled'], true); ?> class="<?php echo esc_attr($data['class']); ?>" type="checkbox" name="<?php echo esc_attr($field_key); ?>" id="<?php echo esc_attr($field_key); ?>" style="<?php echo esc_attr($data['css']); ?>" value="1" <?php checked($this->get_option($key), 'yes'); ?> <?php echo $this->get_custom_attribute_html($data); // WPCS: XSS ok.          ?> /> <?php echo wp_kses_post($data['label']); ?>
                            <?php
                            if (isset($data['is_paypal_vault_enable']) && true === $data['is_paypal_vault_enable']) {
                                ?>
                                <img src="<?php echo PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'assets/images/ppcp_check_mark_status.png'; ?>" width="25" height="25" style="display: inline-block;margin: 0 5px -10px 10px;">
                                <b><?php echo __('Vault is Connected!', 'paypal-for-woocommerce'); ?></b>
                            <?php } ?>
                        </label>
                        <?php
                        echo $this->get_description_html($data);
                        if (isset($data['need_to_display_paypal_vault_onboard_button']) && true === $data['need_to_display_paypal_vault_onboard_button']) {
                            $signup_link = $this->angelleye_get_signup_link($testmode);
                            if ($signup_link) {
                                $args = array(
                                    'displayMode' => 'minibrowser',
                                );
                                $url = add_query_arg($args, $signup_link);
                                ?>
                                <br>
                                <a target="_blank" class="wplk-button button-primary" id="<?php echo esc_attr('wplk-button'); ?>" data-paypal-onboard-complete="onboardingCallback" href="<?php echo esc_url($url); ?>" data-paypal-button="true"><?php echo __('Activate PayPal Vault', 'paypal-for-woocommerce'); ?></a>
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
                    </fieldset>
                </td>
            </tr>
            <?php
            return ob_get_clean();
        }
    }

    public function angelleye_get_signup_link($testmode) {
        try {
            if (!class_exists('AngellEYE_PayPal_PPCP_Seller_Onboarding')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-seller-onboarding.php';
            }
            $seller_onboarding = AngellEYE_PayPal_PPCP_Seller_Onboarding::instance();
            $seller_onboarding_result = $seller_onboarding->angelleye_generate_signup_link_with_vault($testmode, 'gateway_settings');
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

    public function payment_gateways_support_tooltip($status_html) {
        try {
            $status_html = '<span class="status-enabled tips" data-tip="' . esc_attr__('Note: You will need to activate Tokenization in settings to enable Subscription functionality.', 'woocommerce-subscriptions') . '">' . esc_html__('Yes', 'woocommerce-subscriptions') . '</span>';
            return $status_html;
        } catch (Exception $ex) {
            
        }
    }

}
