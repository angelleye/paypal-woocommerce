<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_CC_AngellEYE extends WC_Payment_Gateway_CC {

    public $setting_obj;

    public function __construct() {
        try {
            $this->id = 'angelleye_ppcp_cc';
            $this->icon = apply_filters('woocommerce_angelleye_ppcp_cc_icon', plugins_url('/assets/images/cards.png', plugin_basename(dirname(__FILE__))));
            $this->method_description = __('Accept PayPal, PayPal Credit and alternative payment types.', 'paypal-for-woocommerce');
            $this->has_fields = true;
            $this->angelleye_ppcp_load_class();
            $this->method_title = $this->setting_obj->get('advanced_card_payments_title', 'Credit Card');
            $this->enable_tokenized_payments = 'yes' === $this->setting_obj->get('enable_tokenized_payments', 'no');
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
            } else {
                $this->supports = array(
                    'products',
                    'refunds',
                    'pay_button'
                );
            }
            $this->title = $this->setting_obj->get('advanced_card_payments_title', 'Credit Card');
            $this->enable_paypal_checkout_page = 'yes' === $this->setting_obj->get('enable_paypal_checkout_page', 'yes');
            $this->checkout_page_display_option = $this->setting_obj->get('checkout_page_display_option', 'regular');
            $this->sandbox = 'yes' === $this->setting_obj->get('testmode', 'no');
            $this->sandbox_merchant_id = $this->setting_obj->get('sandbox_merchant_id', '');
            $this->live_merchant_id = $this->setting_obj->get('live_merchant_id', '');
            $this->sandbox_client_id = $this->setting_obj->get('sandbox_client_id', '');
            $this->sandbox_secret_id = $this->setting_obj->get('sandbox_api_secret', '');
            $this->live_client_id = $this->setting_obj->get('api_client_id', '');
            $this->live_secret_id = $this->setting_obj->get('api_secret', '');
            $this->advanced_card_payments = 'yes' === $this->setting_obj->get('enable_advanced_card_payments', 'no');
            if ($this->sandbox) {
                $this->merchant_id = $this->setting_obj->get('sandbox_merchant_id', '');
                $this->client_id = $this->sandbox_client_id;
                $this->secret_id = $this->sandbox_secret_id;
            } else {
                $this->merchant_id = $this->setting_obj->get('live_merchant_id', '');
                $this->client_id = $this->live_client_id;
                $this->secret_id = $this->live_secret_id;
            }
        } catch (Exception $ex) {
            
        }
    }

    public function get_icon() {
        $icon = parent::get_icon();
        $icons = $this->setting_obj->get('disable_cards', array());
        if (empty($icons)) {
            return $icon;
        }
        $title_options = $this->card_labels();
        foreach ($title_options as $icon_key => $icon_value) {
            if (!in_array($icon_key, $icons)) {
                if ($this->dcc_applies->can_process_card($icon_key)) {
                    $images[] = '<img
                 title="' . esc_attr($title_options[$icon_key]) . '"
                 src="' . esc_url(PAYPAL_FOR_WOOCOMMERCE_ASSET_URL) . 'ppcp-gateway/images/' . esc_attr($icon_key) . '.svg"
                 class="ppcp-card-icon"
                > ';
                }
            }
        }
        return implode('', $images);
    }

    private function card_labels(): array {
        return array(
            'visa' => _x(
                    'Visa',
                    'Name of credit card',
                    'paypal-for-woocommerce'
            ),
            'mastercard' => _x(
                    'Mastercard',
                    'Name of credit card',
                    'paypal-for-woocommerce'
            ),
            'amex' => _x(
                    'American Express',
                    'Name of credit card',
                    'paypal-for-woocommerce'
            ),
            'discover' => _x(
                    'Discover',
                    'Name of credit card',
                    'paypal-for-woocommerce'
            ),
            'jcb' => _x(
                    'JCB',
                    'Name of credit card',
                    'paypal-for-woocommerce'
            ),
            'elo' => _x(
                    'Elo',
                    'Name of credit card',
                    'paypal-for-woocommerce'
            ),
            'hiper' => _x(
                    'Hiper',
                    'Name of credit card',
                    'paypal-for-woocommerce'
            ),
        );
    }

    public function process_payment($woo_order_id) {
        try {
            if (!empty($_POST['wc-angelleye_ppcp_cc-payment-token']) && $_POST['wc-angelleye_ppcp_cc-payment-token'] != 'new') {
                $order = wc_get_order($woo_order_id);
                $token_id = wc_clean($_POST['wc-angelleye_ppcp_cc-payment-token']);
                $token = WC_Payment_Tokens::get($token_id);
                update_post_meta($woo_order_id, '_angelleye_ppcp_used_payment_method', 'card');
                angelleye_ppcp_add_used_payment_method_name_to_subscription($woo_order_id);
                update_post_meta($woo_order_id, '_payment_tokens_id', $token->get_token());
                $this->payment_request->save_payment_token($order, $token->get_token());
                $is_success = $this->payment_request->angelleye_ppcp_capture_order_using_payment_method_token($woo_order_id);
                if ($is_success) {
                    WC()->cart->empty_cart();
                    unset(WC()->session->angelleye_ppcp_session);
                    if (ob_get_length()) {
                        ob_end_clean();
                    }
                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url($order),
                    );
                } else {
                    unset(WC()->session->angelleye_ppcp_session);
                    if (ob_get_length()) {
                        ob_end_clean();
                    }
                    return array(
                        'result' => 'failure',
                        'redirect' => wc_get_cart_url()
                    );
                }
                exit();
            }
            $angelleye_ppcp_paypal_order_id = angelleye_ppcp_get_session('angelleye_ppcp_paypal_order_id');
            $is_success = false;
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
                    if (ob_get_length()) {
                        ob_end_clean();
                    }
                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url($order),
                    );
                } else {
                    unset(WC()->session->angelleye_ppcp_session);
                    if (ob_get_length()) {
                        ob_end_clean();
                    }
                    return array(
                        'result' => 'failure',
                        'redirect' => wc_get_cart_url()
                    );
                }
            } elseif ($this->checkout_disable_smart_button === true && $this->advanced_card_payments === false) {
                $result = $this->payment_request->angelleye_ppcp_regular_create_order_request($woo_order_id);
                return $result;
                exit();
            }
        } catch (Exception $ex) {
            
        }
    }

    public function is_credentials_set() {

        if (!empty($this->merchant_id) || (!empty($this->client_id) && !empty($this->secret_id))) {
            return true;
        } else {
            return false;
        }
    }

    public function is_available() {
        try {
            if ($this->advanced_card_payments == true) {
                if ($this->is_credentials_set()) {
                    return true;
                }
                return false;
            } else {
                return false;
            }
        } catch (Exception $ex) {
            
        }
    }

    public function payment_fields() {
        try {
            if ($this->supports('tokenization')) {
                $this->tokenization_script();
            }
            if (angelleye_ppcp_is_subs_change_payment() === true) {
                if (count($this->get_tokens()) > 0) {
                    $this->saved_payment_methods();
                }
                $this->angelleye_ppcp_cc_form();
            } elseif ((is_checkout() || is_checkout_pay_page()) && angelleye_ppcp_get_order_total() > 0) {
                if (count($this->get_tokens()) > 0) {
                    $this->saved_payment_methods();
                }
                $this->form();
                angelleye_ppcp_add_css_js();
                if (angelleye_ppcp_is_cart_subscription() === false && $this->enable_tokenized_payments) {
                    if ($this->supports('tokenization')) {
                        $this->save_payment_method_checkbox();
                    }
                }
                echo '<div id="payments-sdk__contingency-lightbox"></div>';
            } elseif ((is_checkout() || is_checkout_pay_page()) && angelleye_ppcp_get_order_total() === 0) {
                $this->angelleye_ppcp_cc_form();
            }
        } catch (Exception $ex) {
            
        }
    }

    public function form() {
        try {
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
            <fieldset id="wc-<?php echo esc_attr($this->id); ?>-form" class='wc-credit-card-form wc-payment-form'>
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
        } catch (Exception $ex) {
            
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
            $this->api_log = AngellEYE_PayPal_PPCP_Log::instance();
            $this->api_request = AngellEYE_PayPal_PPCP_Request::instance();
            $this->dcc_applies = AngellEYE_PayPal_PPCP_DCC_Validate::instance();
            $this->payment_request = AngellEYE_PayPal_PPCP_Payment::instance();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function can_refund_order($order) {
        try {
            return $order && $order->get_transaction_id();
        } catch (Exception $ex) {
            
        }
    }

    public function process_refund($order_id, $amount = null, $reason = '') {
        try {
            $order = wc_get_order($order_id);
            if (!$this->can_refund_order($order)) {
                return new WP_Error('error', __('Refund failed.', 'paypal-for-woocommerce'));
            }
            $transaction_id = $order->get_transaction_id();
            $bool = $this->payment_request->angelleye_ppcp_refund_order($order_id, $amount, $reason, $transaction_id);
            return $bool;
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_process_free_signup_with_free_trial($order_id) {
        try {
            $posted_card = $this->get_posted_card();
            return $this->payment_request->angelleye_ppcp_advanced_credit_card_setup_tokens_free_signup_with_free_trial($posted_card, $order_id);
        } catch (Exception $ex) {
            
        }
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
            if ((!empty($_POST['wc-angelleye_ppcp_cc-payment-token']) && $_POST['wc-angelleye_ppcp_cc-payment-token'] != 'new')) {
                $order = wc_get_order($order_id);
                $token_id = wc_clean($_POST['wc-angelleye_ppcp_cc-payment-token']);
                $token = WC_Payment_Tokens::get($token_id);
                update_post_meta($order_id, '_angelleye_ppcp_used_payment_method', 'card');
                $this->payment_request->save_payment_token($order, $token->get_token());
                return array(
                    'result' => 'success',
                    'redirect' => angelleye_ppcp_get_view_sub_order_url($order_id)
                );
            } else {
                $posted_card = $this->get_posted_card();
                return $this->payment_request->angelleye_ppcp_advanced_credit_card_setup_tokens_sub_change_payment($posted_card, $order_id);
            }
        } catch (Exception $ex) {
            
        }
    }

    public function free_signup_order_payment($order_id) {
        try {
            if (!empty($_POST['wc-angelleye_ppcp_cc-payment-token']) && $_POST['wc-angelleye_ppcp_cc-payment-token'] != 'new') {
                $order = wc_get_order($order_id);
                $token_id = wc_clean($_POST['wc-angelleye_ppcp_cc-payment-token']);
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

    public function get_posted_card() {
        try {
            $card_number = isset($_POST['angelleye_ppcp_cc-card-number']) ? wc_clean($_POST['angelleye_ppcp_cc-card-number']) : '';
            $cc_card_expiry = isset($_POST['angelleye_ppcp_cc-card-expiry']) ? wc_clean($_POST['angelleye_ppcp_cc-card-expiry']) : '';
            $card_number = str_replace(array(' ', '-'), '', $card_number);
            $card_expiry = array_map('trim', explode('/', $cc_card_expiry));
            $card_exp_month = str_pad($card_expiry[0], 2, "0", STR_PAD_LEFT);
            $card_exp_year = isset($card_expiry[1]) ? $card_expiry[1] : '';
            if (strlen($card_exp_year) == 2) {
                $card_exp_year += 2000;
            }
            return (object) array(
                        'number' => $card_number,
                        'exp_month' => $card_exp_month,
                        'exp_year' => $card_exp_year,
            );
        } catch (Exception $ex) {
            
        }
    }

    public function add_payment_method() {
        try {
            $posted_card = $this->get_posted_card();
            return $this->payment_request->angelleye_ppcp_advanced_credit_card_setup_tokens($posted_card);
        } catch (Exception $ex) {
            
        }
    }

    public function field_name($name) {
        return ' name="' . esc_attr($this->id . '-' . $name) . '" ';
    }

    public function angelleye_ppcp_cc_form() {
        wp_enqueue_script('wc-credit-card-form');

        $fields = array();

        $cvc_field = '<p class="form-row form-row-last">
			<label for="' . esc_attr($this->id) . '-card-cvc">' . esc_html__('Card code', 'woocommerce') . '&nbsp;<span class="required">*</span></label>
			<input id="' . esc_attr($this->id) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="' . esc_attr__('CVC', 'woocommerce') . '" ' . $this->field_name('card-cvc') . ' style="width:100px" />
		</p>';

        $default_fields = array(
            'card-number-field' => '<p class="form-row form-row-wide">
				<label for="' . esc_attr($this->id) . '-card-number">' . esc_html__('Card number', 'woocommerce') . '&nbsp;<span class="required">*</span></label>
				<input id="' . esc_attr($this->id) . '-card-number" class="input-text wc-credit-card-form-card-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ' . $this->field_name('card-number') . ' />
			</p>',
            'card-expiry-field' => '<p class="form-row form-row-first">
				<label for="' . esc_attr($this->id) . '-card-expiry">' . esc_html__('Expiry (MM/YY)', 'woocommerce') . '&nbsp;<span class="required">*</span></label>
				<input id="' . esc_attr($this->id) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" inputmode="numeric" autocomplete="cc-exp" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="' . esc_attr__('MM / YY', 'woocommerce') . '" ' . $this->field_name('card-expiry') . ' />
			</p>',
        );

        $fields = wp_parse_args($fields, apply_filters('woocommerce_credit_card_form_fields', $default_fields, $this->id));
        ?>

        <fieldset id="wc-<?php echo esc_attr($this->id); ?>-cc-form" class='wc-credit-card-form wc-payment-form'>
            <?php do_action('woocommerce_credit_card_form_start', $this->id); ?>
            <?php
            foreach ($fields as $field) {
                echo $field; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
            }
            ?>
            <?php do_action('woocommerce_credit_card_form_end', $this->id); ?>
            <div class="clear"></div>
        </fieldset>
        <?php
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
        $card_type = strtolower($token->get_card_type());
        $card_type = str_replace('-', '', $card_type);
        $card_type = str_replace('_', '', $card_type);
        $icon_url = array(
            'visa' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/icon/credit-cards/visa.png',
            'amex' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/icon/credit-cards/amex.png',
            'diners' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/icon/credit-cards/diners.png',
            'discover' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/icon/credit-cards/discover.png',
            'jcb' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/icon/credit-cards/jcb.png',
            'laser' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/icon/credit-cards/laser.png',
            'maestro' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/icon/credit-cards/maestro.png',
            'mastercard' => PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/images/icon/credit-cards/mastercard.png'
        );
        if (isset($icon_url[$card_type])) {
            $image_path = '<img class="ppcp_payment_method_icon" src="' . $icon_url[$card_type] . '" alt="Credit card">';
        } else {
            $image_path = '';
        }
        $html = sprintf(
                '<li class="woocommerce-SavedPaymentMethods-token">
				<input id="wc-%1$s-payment-token-%2$s" type="radio" name="wc-%1$s-payment-token" value="%2$s" style="width:auto;" class="woocommerce-SavedPaymentMethods-tokenInput" %4$s />
				<label for="wc-%1$s-payment-token-%2$s">%5$s %3$s</label>
			</li>',
                esc_attr($this->id),
                esc_attr($token->get_id()),
                esc_html($token->get_display_name()),
                checked($token->is_default(), true, false),
                $image_path
        );

        return apply_filters('woocommerce_payment_gateway_get_saved_payment_method_option_html', $html, $token, $this);
    }

}
