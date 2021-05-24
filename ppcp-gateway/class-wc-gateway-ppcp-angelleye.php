<?php

class WC_Gateway_PPCP_AngellEYE extends WC_Payment_Gateway_CC {

    public $settings;
    public $api_log;
    public $dcc_applies;
    public $api_request;
    public $seller_onboarding;
    public $payment_request;

    public function __construct() {
        try {
            $this->angelleye_ppcp_load_class();
            $this->setup_properties();
            $this->init_form_fields();
            $this->init_settings();
            $this->angelleye_get_settings();
            $this->angelleye_defind_hooks();
            if (angelleye_ppcp_has_active_session()) {
                $this->order_button_text = apply_filters('angelleye_ppcp_order_review_page_place_order_button_text', __('Confirm Your PayPal Order', 'paypal-for-woocommerce'));
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
            $this->settings = WC_Gateway_PPCP_AngellEYE_Settings::instance();
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
        $this->id = 'angelleye_ppcp';
        $this->icon = apply_filters('woocommerce_angelleye_paypal_checkout_icon', 'https://www.paypalobjects.com/webstatic/mktg/Logo/pp-logo-100px.png');
        $this->has_fields = true;
        $this->method_title = __('PayPal Complete Payments', 'paypal-for-woocommerce');
        $this->method_description = __('Accept PayPal, PayPal Credit and alternative payment types.', 'paypal-for-woocommerce');
        $this->supports = array(
            'products',
            'refunds',
            'pay_button'
        );
    }

    public function angelleye_get_settings() {
        $this->title = $this->get_option('title', 'PayPal Complete Payments');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled', 'no');
        $this->sandbox = 'yes' === $this->get_option('testmode', 'no');
        $this->sandbox_client_id = $this->get_option('sandbox_client_id', '');
        $this->sandbox_secret_key = $this->get_option('sandbox_secret_key', '');
        $this->live_client_id = $this->get_option('live_client_id', '');
        $this->live_secret_key = $this->get_option('live_secret_key', '');
        if (!empty($this->sandbox_client_id) && !empty($this->sandbox_secret_key)) {
            $this->is_sandbox_seller_onboarding_done = 'yes';
        } else {
            $this->is_sandbox_seller_onboarding_done = 'no';
        }
        if (!empty($this->live_client_id) && !empty($this->live_secret_key)) {
            $this->is_live_seller_onboarding_done = 'yes';
        } else {
            $this->is_live_seller_onboarding_done = 'no';
        }
        if ($this->sandbox) {
            $this->client_id = $this->sandbox_client_id;
            $this->secret_id = $this->sandbox_secret_key;
        } else {
            $this->client_id = $this->live_client_id;
            $this->secret_id = $this->live_secret_key;
        }

        $this->paymentaction = $this->get_option('paymentaction', 'capture');
        $this->advanced_card_payments = 'yes' === $this->get_option('enable_advanced_card_payments', 'no');
        if ($this->dcc_applies->for_country_currency() === false) {
            $this->advanced_card_payments = false;
        }
        if ($this->advanced_card_payments) {
            $this->threed_secure_enabled = 'yes' === $this->get_option('threed_secure_enabled', 'no');
        } else {
            $this->threed_secure_enabled = false;
        }
        $this->is_enabled = 'yes' === $this->get_option('enabled', 'no');
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
    }

    public function process_admin_options() {
        delete_transient('angelleye_ppcp_sandbox_access_token');
        delete_transient('angelleye_ppcp_live_access_token');
        delete_transient('angelleye_ppcp_sandbox_client_token');
        delete_transient('angelleye_ppcp_live_client_token');
        delete_option('angelleye_ppcp_snadbox_webhook_id');
        delete_option('angelleye_ppcp_live_webhook_id');
        if (function_exists('angelleye_ppcp_may_register_webhook')) {
            angelleye_ppcp_may_register_webhook();
        }
        parent::process_admin_options();
    }

    public function admin_options() {
        $this->angelleye_ppcp_admin_notices();
        //wp_enqueue_script('woocommerce_admin');
        wp_enqueue_script('wc-clipboard');
        echo '<div id="angelleye_paypal_marketing_table">';
        parent::admin_options();
        echo '</div>';
        AngellEYE_Utility::angelleye_display_marketing_sidebar($this->id);
    }

    public function init_form_fields() {
        try {
            $this->form_fields = $this->settings->angelleye_ppcp_setting_fields();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function payment_fields() {
        $description = $this->get_description();
        if ($description) {
            echo wpautop(wptexturize($description));
        }

        do_action('angelleye_ppcp_display_paypal_button_checkout_page');
        if (is_checkout() && $this->advanced_card_payments) {
            if (is_checkout_pay_page() === false) {
                parent::payment_fields();
                if ($this->threed_secure_enabled) {
                    echo '<div id="payments-sdk__contingency-lightbox"></div>';
                }
            }
        }
    }

    public function form() {
        wp_enqueue_script('wc-credit-card-form');
        $fields = array();
        $cvc_field = '<div class="form-row form-row-last">
                        <label for="' . esc_attr($this->id) . '-card-cvc">' . apply_filters('cc_form_label_card_code', __('Card code', 'paypal-for-woocommerce'), $this->id) . ' </label>
                        <div id="' . esc_attr($this->id) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc hosted-field-braintree"></div>
                    </div>';
        $default_fields = array(
            'card-number-field' => '<div class="form-row form-row-wide">
                        <label for="' . esc_attr($this->id) . '-card-number">' . apply_filters('cc_form_label_card_number', __('Card number', 'paypal-for-woocommerce'), $this->id) . '</label>
                        <div id="' . esc_attr($this->id) . '-card-number"  class="input-text wc-credit-card-form-card-number hosted-field-braintree"></div>
                    </div>',
            'card-expiry-field' => '<div class="form-row form-row-first">
                        <label for="' . esc_attr($this->id) . '-card-expiry">' . apply_filters('cc_form_label_expiry', __('Expiry (MM/YY)', 'paypal-for-woocommerce'), $this->id) . ' </label>
                        <div id="' . esc_attr($this->id) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry hosted-field-braintree"></div>
                    </div>',
        );
        if (!$this->supports('credit_card_form_cvc_on_saved_method')) {
            $default_fields['card-cvc-field'] = $cvc_field;
        }
        $fields = wp_parse_args($fields, apply_filters('woocommerce_credit_card_form_fields', $default_fields, $this->id));
        ?>
        <fieldset id="wc-<?php echo esc_attr($this->id); ?>-cc-form" class='wc-credit-card-form wc-payment-form' style="display:none;">
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
        if (!empty($this->client_id) && !empty($this->secret_id)) {
            return true;
        } else {
            return false;
        }
    }

    public function enqueue_scripts() {
        if (isset($_GET['section']) && 'angelleye_ppcp' === $_GET['section']) {
            wp_enqueue_style('wc-gateway-ppcp-angelleye-settings-css', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/css/angelleye-ppcp-gateway-admin.css', array(), VERSION_PFW, 'all');
            wp_enqueue_script('wc-gateway-ppcp-angelleye-settings', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/js/wc-gateway-ppcp-angelleye-settings.js', array('jquery'), VERSION_PFW, true);
            wp_localize_script('wc-gateway-ppcp-angelleye-settings', 'ppcp_angelleye_param', array(
                'angelleye_ppcp_is_local_server' => ( angelleye_ppcp_is_local_server() == true) ? 'yes' : 'no',
                'angelleye_ppcp_onboarding_endpoint' => WC_AJAX::get_endpoint('ppcp_login_seller'),
                'angelleye_ppcp_onboarding_endpoint_nonce' => wp_create_nonce('ppcp_login_seller'),
                'is_sandbox_seller_onboarding_done' => $this->is_sandbox_seller_onboarding_done,
                'is_live_seller_onboarding_done' => $this->is_live_seller_onboarding_done,
                'is_advanced_card_payments' => ($this->dcc_applies->for_country_currency() === false) ? 'no' : 'yes',
                'woocommerce_enable_guest_checkout' => get_option('woocommerce_enable_guest_checkout', 'yes'),
                'disable_terms' => ( apply_filters('woocommerce_checkout_show_terms', true) && function_exists('wc_terms_and_conditions_checkbox_enabled') && wc_terms_and_conditions_checkbox_enabled() && get_option('woocommerce_enable_guest_checkout', 'yes') === 'yes') ? 'yes' : 'no'
                    )
            );
        }
        wp_enqueue_script('wc-gateway-ppcp-angelleye-settings-list', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/js/wc-gateway-ppcp-angelleye-settings-list.js', array('jquery'), VERSION_PFW, true);
    }

    public function generate_angelleye_ppcp_text_html($field_key, $data) {
        if (isset($data['type']) && $data['type'] === 'angelleye_ppcp_text') {
            $field_key = $this->get_field_key($field_key);
            ob_start();
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?> <?php echo $this->get_tooltip_html($data); // WPCS: XSS ok.                                                                 ?></label>
                </th>
                <td class="forminp" id="<?php echo esc_attr($field_key); ?>">
                    <button type="button" class="button angelleye-ppcp-disconnect"><?php echo __('Disconnect', ''); ?></button>
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
            $args = array(
                'displayMode' => 'minibrowser',
            );
            $id = ($testmode === 'no') ? 'connect-to-production' : 'connect-to-sandbox';
            $label = ($testmode === 'no') ? __('Connect to PayPal', 'paypal-for-woocommerce') : __('Connect to PayPal Sandbox', 'paypal-for-woocommerce');
            ob_start();
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?> <?php echo $this->get_tooltip_html($data); // WPCS: XSS ok.                                                                 ?></label>
                </th>
                <td class="forminp" id="<?php echo esc_attr($field_key); ?>">
                    <?php
                    if ($this->is_live_seller_onboarding_done === 'no' && $testmode === 'no' || $this->is_sandbox_seller_onboarding_done === 'no' && $testmode === 'yes') {
                        $signup_link = $this->angelleye_get_signup_link($testmode);
                        if ($signup_link) {
                            $url = add_query_arg($args, $signup_link);
                            $this->angelleye_display_paypal_signup_button($url, $id, $label);
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
                            ?>
                            <a href="#" class="angelleye_ppcp_gateway_manual_credential_input"><?php echo __('Toggle to manual credential input', ''); ?></a>
                            <?php
                        }
                    }
                    ?>
                </td>
            </tr>
            <?php
            return ob_get_clean();
        }
    }

    public function angelleye_display_paypal_signup_button($url, $id, $label) {
        ?><a target="_blank" class="button-primary" id="<?php echo esc_attr($id); ?>" data-paypal-onboard-complete="onboardingCallback" href="<?php echo esc_url($url); ?>" data-paypal-button="true"><?php echo esc_html($label); ?></a>
        <span class="angelleye_ppcp_gateway_setting_sepraer"><?php echo __('OR', ''); ?></span>
        <a href="#" class="angelleye_ppcp_gateway_manual_credential_input"><?php echo __('Toggle to manual credential input', ''); ?></a>
        <?php
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
                <label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?> <?php echo $this->get_tooltip_html($data); // WPCS: XSS ok.                   ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post($data['title']); ?></span></legend>
                    <input class="input-text regular-input <?php echo esc_attr($data['class']); ?>" type="text" name="<?php echo esc_attr($field_key); ?>" id="<?php echo esc_attr($field_key); ?>" style="<?php echo esc_attr($data['css']); ?>" value="<?php echo esc_attr($this->get_option($key)); ?>" placeholder="<?php echo esc_attr($data['placeholder']); ?>" <?php disabled($data['disabled'], true); ?> <?php echo $this->get_custom_attribute_html($data); // WPCS: XSS ok.                   ?> />
                    <button type="button" class="button-secondary <?php echo esc_attr($data['button_class']); ?>" data-tip="Copied!">Copy</button>
                    <?php echo $this->get_description_html($data); // WPCS: XSS ok.    ?>
                </fieldset>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    public function angelleye_get_signup_link($testmode = 'yes') {
        try {
            include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-seller-onboarding.php');
            $this->seller_onboarding = AngellEYE_PayPal_PPCP_Seller_Onboarding::instance();
            $seller_onboarding_result = $this->seller_onboarding->angelleye_generate_signup_link($testmode);
            if (isset($seller_onboarding_result['result']) && 'success' === $seller_onboarding_result['result'] && !empty($seller_onboarding_result['body'])) {
                $json = json_decode($seller_onboarding_result['body']);
                if (isset($json->links)) {
                    foreach ($json->links as $link) {
                        if ('action_url' === $link->rel) {
                            return (string) $link->href;
                        }
                    }
                } else {
                    return false;
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function process_payment($woo_order_id) {
        $is_success = false;
        if (isset($_GET['from']) && 'checkout' === $_GET['from']) {
            angelleye_ppcp_set_session('angelleye_ppcp_checkout_post', isset($_POST) ? wc_clean($_POST) : false);
            angelleye_ppcp_set_session('angelleye_ppcp_woo_order_id', $woo_order_id);
            $this->payment_request->angelleye_ppcp_create_order_request($woo_order_id);
            exit();
        } else {
            $angelleye_ppcp_paypal_order_id = angelleye_ppcp_get_session('angelleye_ppcp_paypal_order_id');
            if (!empty($angelleye_ppcp_paypal_order_id)) {
                $order = wc_get_order($woo_order_id);
                if ($this->paymentaction === 'capture') {
                    $is_success = $this->payment_request->angelleye_ppcp_order_capture_request($woo_order_id);
                } else {
                    $is_success = $this->payment_request->angelleye_ppcp_order_auth_request($woo_order_id);
                }
                angelleye_ppcp_update_post_meta($order, '_payment_action', $this->paymentaction);
                angelleye_ppcp_update_post_meta($order, '_enviorment', ($this->sandbox) ? 'sandbox' : 'live');
                WC()->cart->empty_cart();
                if ($is_success) {
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
            }
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
        if (!empty($this->client_id) && !empty($this->secret_id)) {
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
        if ($this->is_live_seller_onboarding_done === 'yes' || $this->is_sandbox_seller_onboarding_done === 'yes') {
            return false;
        }

        $message = sprintf(
                __(
                        'PayPal Complete Payments is almost ready. To get started, <a href="%1$s">connect your account</a>.', 'paypal-for-woocommerce'
                ), admin_url('admin.php?page=wc-settings&tab=checkout&section=angelleye_ppcp#woocommerce_angelleye_ppcp_enabled')
        );
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php echo $message; ?></p>
        </div>
        <?php
    }

}
