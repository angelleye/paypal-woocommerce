<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Apple_Pay_AngellEYE extends WC_Gateway_PPCP_AngellEYE {

    protected bool $enable_apple_pay;
    const PAYMENT_METHOD = 'apple_pay';
    public $sandbox;
    public $sandbox_merchant_id;
    public $live_merchant_id;
    public $sandbox_client_id;
    public $sandbox_secret_id;
    public $live_client_id;
    public $live_secret_id;
    public $soft_descriptor;
    public $is_sandbox_third_party_used;
    public $is_sandbox_first_party_used;
    public $is_live_third_party_used;
    public $is_live_first_party_used;
    public $merchant_id;
    public $client_id;
    public $secret_id;
    public $paymentaction;
    public $three_d_secure_contingency;
    public $is_enabled;
    public $ppcp_enabled;
    
    /**
     * @var ?string
     */
    public ?string $apple_pay_payments_description;

    public function __construct() {
        parent::__construct();
        try {
            $this->id = 'angelleye_ppcp_apple_pay';
            $this->icon = apply_filters('woocommerce_angelleye_ppcp_apple_pay_icon', plugins_url('/ppcp-gateway/images/icon/apple_pay.png', plugin_basename(dirname(__FILE__))));
            $this->method_description = __('Accept payments using Apple Pay.', 'paypal-for-woocommerce');
            $this->has_fields = true;
            $this->angelleye_ppcp_load_class();
            // disable temp $this->setGatewaySupports();

            $baseSupports = array(
                'products',
                'refunds',
                'pay_button'
            );

            $subscriptionSupports = [
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
            ];

            $this->enable_tokenized_payments = 'yes' === $this->setting_obj->get('enable_tokenized_payments', 'no');

            if (isset($_GET['paypal_order_id']) && isset($_GET['paypal_payer_id']) && $this->enable_tokenized_payments) {
                $this->supports = array_merge($baseSupports, $subscriptionSupports);
            } elseif ($this->enable_tokenized_payments || (isset($_GET['page']) && isset($_GET['tab']) && 'wc-settings' === $_GET['page'] && 'checkout' === $_GET['tab'])) {
                $this->supports = array_merge($baseSupports, $subscriptionSupports, array('tokenization'));
            } else {
                $this->supports = $baseSupports;
            }
            $this->ppcp_enabled = 'yes' === $this->setting_obj->get('enabled', 'no');
            $this->method_title = apply_filters('angelleye_ppcp_gateway_method_title', $this->setting_obj->get('apple_pay_payments_title', 'Apple Pay'));
            $this->title = $this->setting_obj->get('apple_pay_payments_title', 'Apple Pay');

            $is_domain_added = $this->setting_obj->get('apple_pay_domain_added', 'no') == 'yes';
            $this->enable_apple_pay = $is_domain_added && 'yes' === $this->setting_obj->get('enable_apple_pay', 'no');
            $this->apple_pay_payments_description = $this->setting_obj->get('apple_pay_payments_description', 'Complete your purchase by selecting your saved payment methods or using Apple Pay.');
        } catch (Exception $ex) {

        }
    }

    public function is_available() {
        return $this->ppcp_enabled === true && $this->enable_apple_pay == true && $this->is_credentials_set();
    }

    public function payment_fields() {
        try {
            if ($this->supports('tokenization')) {
                $this->tokenization_script();
            }
            angelleye_ppcp_add_css_js();
            if (angelleye_ppcp_is_subs_change_payment() === true) {
                $this->form();
            } elseif (is_checkout() || is_checkout_pay_page()) {
                $orderTotal = angelleye_ppcp_get_order_total();
                if ($orderTotal > 0) {
                    $this->form();
                    angelleye_ppcp_add_css_js();
                } elseif ($orderTotal === 0) {
                    $this->form();
                }
            }
        } catch (Exception $ex) {

        }
    }

    public function form() {
        try {
            ?>
            <p><?php echo __($this->apple_pay_payments_description, 'paypal-for-woocommerce'); ?></p>
            <fieldset id="wc-<?php echo esc_attr($this->id); ?>-form" class='wc-apple-pay-form wc-payment-form'>
                <?php do_action('woocommerce_apple_pay_form_start', $this->id); ?>
                <?php do_action('woocommerce_apple_pay_form_start', $this->id); ?>
                <div class="clear"></div>
            </fieldset>
            <?php
        } catch (Exception $ex) {

        }
    }

    public function field_name($name) {
        return ' name="' . esc_attr($this->id . '-' . $name) . '" ';
    }

    public function get_saved_payment_method_option_html($token) {
        $card_type = str_replace(['-', '_'], '', strtolower($token->get_card_type()));
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
