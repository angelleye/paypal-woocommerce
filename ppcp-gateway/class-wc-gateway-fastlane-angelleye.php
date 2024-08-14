<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Fastlane_AngellEYE extends WC_Payment_Gateway_CC {

    use WC_Gateway_Base_AngellEYE;

    public $enable_paypal_checkout_page;
    public $checkout_page_display_option;
    public $sandbox;
    public $sandbox_merchant_id;
    public $live_merchant_id;
    public $sandbox_client_id;
    public $sandbox_secret_id;
    public $live_client_id;
    public $live_secret_id;
    public $merchant_id;
    public $client_id;
    public $secret_id;
    public bool $enable_tokenized_payments;
    public $paymentaction;
    public $checkout_disable_smart_button;
    public $is_enabled;
    public $enable_ppcp_fastlane;

    public function __construct() {
        try {
            $this->id = 'angelleye_ppcp_fastlane';
            $this->icon = apply_filters('woocommerce_angelleye_ppcp_fastlane_icon', plugins_url('/assets/images/cards.png', plugin_basename(dirname(__FILE__))));
            $this->has_fields = true;
            $this->angelleye_ppcp_load_class();
            $this->setGatewaySupports();
            $this->title = "debit or credit";
            $this->sandbox = 'yes' === $this->setting_obj->get('testmode', 'no');
            $this->sandbox_merchant_id = $this->setting_obj->get('sandbox_merchant_id', '');
            $this->live_merchant_id = $this->setting_obj->get('live_merchant_id', '');
            $this->paymentaction = $this->setting_obj->get('paymentaction', 'capture');
            $this->enable_ppcp_fastlane = 'yes' === $this->setting_obj->get('enable_ppcp_fastlane', 'no');
            $this->enabled = $this->setting_obj->get('enabled', 'no');
            $this->is_enabled = 'yes' === $this->setting_obj->get('enabled', 'no');
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
            'maestro' => _x(
                    'Maestro',
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

    public function get_icon() {
        $icons = $this->setting_obj->get('disable_cards', array());
        $title_options = $this->card_labels();
        $images = [];
        $totalIcons = 0;
        foreach ($title_options as $icon_key => $icon_value) {
            if (!in_array($icon_key, $icons)) {
                if ($this->dcc_applies->can_process_card($icon_key)) {
                    $iconUrl = esc_url(PAYPAL_FOR_WOOCOMMERCE_ASSET_URL) . 'ppcp-gateway/images/' . esc_attr($icon_key) . '.svg';
                    $iconTitle = esc_attr($icon_value);
                    $images[] = sprintf('<img title="%s" src="%s" class="ppcp-card-icon ae-icon-%s" />', $iconTitle, $iconUrl, $iconTitle);
                    $totalIcons++;
                }
            }
        }
        return implode('', $images) . '<div class="ppcp-clearfix"></div>';
    }

    public function get_block_icon() {
        $icons = $this->setting_obj->get('disable_cards', array());
        $title_options = $this->card_labels();
        $images = [];
        foreach ($title_options as $icon_key => $icon_value) {
            if (!in_array($icon_key, $icons)) {
                if ($this->dcc_applies->can_process_card($icon_key)) {
                    $iconUrl = esc_url(PAYPAL_FOR_WOOCOMMERCE_ASSET_URL) . 'ppcp-gateway/images/' . esc_attr($icon_key) . '.svg';
                    $images[] = $iconUrl;
                }
            }
        }
        return $images;
    }

    public function process_payment($woo_order_id) {
        try {
            if (!empty($_POST['wc-angelleye_ppcp_fastlane-payment-token']) && $_POST['wc-angelleye_ppcp_fastlane-payment-token'] != 'new') {
                $order = wc_get_order($woo_order_id);
                $token_id = wc_clean($_POST['wc-angelleye_ppcp_fastlane-payment-token']);
                $token = WC_Payment_Tokens::get($token_id);
                $order->update_meta_data('_angelleye_ppcp_used_payment_method', 'card');
                angelleye_ppcp_add_used_payment_method_name_to_subscription($woo_order_id);
                $order->update_meta_data('_payment_tokens_id', $token->get_token());
                $order->update_meta_data('_enviorment', ($this->sandbox) ? 'sandbox' : 'live');
                $order->save();
                $this->payment_request->save_payment_token($order, $token->get_token());
                $is_success = $this->payment_request->angelleye_ppcp_capture_order_using_payment_method_token($woo_order_id);
                if ($is_success) {
                    WC()->cart->empty_cart();
                    AngellEye_Session_Manager::clear();
                    if (ob_get_length()) {
                        ob_end_clean();
                    }
                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url($order),
                    );
                } else {
                    AngellEye_Session_Manager::clear();
                    if (ob_get_length()) {
                        ob_end_clean();
                    }
                    return array(
                        'result' => 'success',
                        'redirect' => wc_get_checkout_url()
                    );
                }
            }
            $angelleye_ppcp_paypal_order_id = AngellEye_Session_Manager::get('paypal_order_id');
            $is_success = false;
            if (isset($_GET['from']) && 'checkout' === $_GET['from']) {
                AngellEye_Session_Manager::set('checkout_post', isset($_POST) ? wc_clean($_POST) : false);
                $this->payment_request->angelleye_ppcp_create_order_request($woo_order_id);
                exit();
            } elseif (!empty($angelleye_ppcp_paypal_order_id)) {
                $order = wc_get_order($woo_order_id);
                if ($this->paymentaction === 'capture') {
                    $is_success = $this->payment_request->angelleye_ppcp_order_capture_request($woo_order_id);
                } else {
                    $is_success = $this->payment_request->angelleye_ppcp_order_auth_request($woo_order_id);
                }
                $order->update_meta_data('_paymentaction', $this->paymentaction);
                $order->update_meta_data('_enviorment', ($this->sandbox) ? 'sandbox' : 'live');
                $order->save();
                if ($is_success) {
                    WC()->cart->empty_cart();
                    AngellEye_Session_Manager::clear();
                    if (ob_get_length()) {
                        ob_end_clean();
                    }
                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url($order),
                    );
                } else {
                    AngellEye_Session_Manager::clear();
                    if (ob_get_length()) {
                        ob_end_clean();
                    }
                    return array(
                        'result' => 'failure',
                        'redirect' => wc_get_cart_url()
                    );
                }
            } elseif ($this->checkout_disable_smart_button === true) {
                $result = $this->payment_request->angelleye_ppcp_order_capture_request($woo_order_id);
                if (ob_get_length()) {
                    ob_end_clean();
                }
                return $result;
            } else {
                $result = $this->payment_request->angelleye_ppcp_order_capture_request($woo_order_id);
                if (ob_get_length()) {
                    ob_end_clean();
                }
                return $result;
            }
        } catch (Exception $ex) {
            
        }
    }

    public function is_available() {
        return $this->enable_ppcp_fastlane === true && $this->is_enabled === true && $this->is_credentials_set();
    }

    public function payment_fields() {
        ?>
        <div id="angelleye_ppcp_checkout_fastlane"></div>
        <input id="fastlane_payment_token" type="hidden">
        <?php
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
}
