<?php

class WC_Gateway_PPCP_AngellEYE extends WC_Payment_Gateway {

    public function __construct() {
        try {
            $this->setup_properties();
            $this->init_form_fields();
            $this->init_settings();
            $this->angelleye_get_settings();
            $this->angelleye_defind_hooks();
        } catch (Exception $ex) {
            
        }
    }

    public function setup_properties() {
        $this->id = 'angelleye_ppcp';
        $this->icon = apply_filters('woocommerce_angelleye_paypal_checkout_icon', '');
        $this->has_fields = false;
        $this->method_title = _x('PayPal Checkout', 'PayPal Checkout', 'woocommerce');
        $this->method_description = __('Accept PayPal, PayPal Credit and alternative payment types.', 'woocommerce');
    }

    public function angelleye_get_settings() {
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
    }

    public function angelleye_defind_hooks() {

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        }
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable PayPal Checkout', 'woocommerce'),
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                'default' => __('PayPal Checkout', 'woocommerce'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'woocommerce'),
                'type' => 'text',
                'description' => __('Payment method description that the customer will see on your checkout.', 'woocommerce'),
                'default' => __('Accept PayPal, PayPal Credit and alternative payment types.', 'woocommerce'),
                'desc_tip' => true,
            ),
            'account_settings' => array(
                'title' => __('Account Settings', 'woocommerce-gateway-paypal-express-checkout'),
                'type' => 'title',
                'description' => '',
            ),
            'testmode' => array(
                'title' => __('PayPal sandbox', 'smart-paypal-checkout-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable PayPal sandbox', 'smart-paypal-checkout-for-woocommerce'),
                'default' => 'no',
                'description' => __('Check this box to enable test mode so that all transactions will hit PayPal’s sandbox server instead of the live server. This should only be used during development as no real transactions will occur when this is enabled.', 'smart-paypal-checkout-for-woocommerce'),
                'desc_tip' => true
            ),
            'live_onboarding' => array(
                'title' => __('Connect to PayPal', 'woocommerce-paypal-payments'),
                'type' => 'angelleye_ppcp_onboarding',
                'gateway' => 'angelleye_ppcp',
                'mode' => 'live',
                'description' => __('Setup or link an existing PayPal account.', 'woocommerce-paypal-payments'),
            ),
            'live_disconnect' => array(
                'title' => __('Disconnect from PayPal', 'woocommerce-paypal-payments'),
                'type' => 'angelleye_ppcp_text',
                'mode' => 'live',
                'description' => __('Click to reset current credentials and use another account.', 'woocommerce-paypal-payments'),
            ),
            'sandbox_onboarding' => array(
                'title' => __('Connect to PayPal', 'woocommerce-paypal-payments'),
                'type' => 'angelleye_ppcp_onboarding',
                'gateway' => 'angelleye_ppcp',
                'mode' => 'sandbox',
                'description' => __('Setup or link an existing PayPal account.', 'woocommerce-paypal-payments'),
            ),
            'sandbox_disconnect' => array(
                'title' => __('Disconnect from PayPal', 'woocommerce-paypal-payments'),
                'type' => 'angelleye_ppcp_text',
                'mode' => 'sandbox',
                'description' => __('Click to reset current credentials and use another account.', 'woocommerce-paypal-payments'),
            ),
            'api_credentials' => array(
                'title' => __('API Credentials', 'woocommerce-gateway-paypal-express-checkout'),
                'type' => 'title',
                'description' => '',
            ),
            'live_email_address' => array(
                'title' => __('Live Email address', 'smart-paypal-checkout-for-woocommerce'),
                'type' => 'password',
                'description' => __('The email address of your PayPal account.', 'smart-paypal-checkout-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'live_merchant_id' => array(
                'title' => __('Live Merchant Id', 'smart-paypal-checkout-for-woocommerce'),
                'type' => 'password',
                'description' => __('Enter your PayPal Secret.', 'smart-paypal-checkout-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'live_client_id' => array(
                'title' => __('Live Client Id', 'smart-paypal-checkout-for-woocommerce'),
                'type' => 'password',
                'description' => __('Enter your PayPal Client ID.', 'smart-paypal-checkout-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'live_secret_key' => array(
                'title' => __('Live Secret Key', 'smart-paypal-checkout-for-woocommerce'),
                'type' => 'password',
                'description' => __('Enter your PayPal Secret.', 'smart-paypal-checkout-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'sandbox_email_address' => array(
                'title' => __('Sandbox Email address', 'smart-paypal-checkout-for-woocommerce'),
                'type' => 'password',
                'description' => __('The email address of your PayPal account.', 'smart-paypal-checkout-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'sandbox_merchant_id' => array(
                'title' => __('Sandbox Merchant Id', 'smart-paypal-checkout-for-woocommerce'),
                'type' => 'password',
                'description' => __('Enter your PayPal Secret.', 'smart-paypal-checkout-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'sandbox_client_id' => array(
                'title' => __('Sandbox Client Id', 'smart-paypal-checkout-for-woocommerce'),
                'type' => 'password',
                'description' => __('Enter your PayPal Sandbox Client ID.', 'smart-paypal-checkout-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'sandbox_secret_key' => array(
                'title' => __('Sandbox Secret Key', 'smart-paypal-checkout-for-woocommerce'),
                'type' => 'password',
                'description' => __('Enter your PayPal Sandbox Secret.', 'smart-paypal-checkout-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            )
        );
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $order->payment_complete();
        WC()->cart->empty_cart();
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        );
    }

    public function enqueue_scripts() {
        $screen = get_current_screen();
        if ($screen && 'woocommerce_page_wc-settings' === $screen->id && isset($_GET['tab'], $_GET['section']) && 'checkout' === $_GET['tab'] && 'angelleye_ppcp' === $_GET['section']) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            wp_enqueue_script('wc-gateway-ppcp-angelleye-settings', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'classes/ppcp-gateway/js/admin/wc-gateway-ppcp-angelleye-settings.js', array('jquery'), time(), true);
        }
    }

    public function generate_angelleye_ppcp_onboarding_html($field_key, $data) {
        if (isset($data['type']) && $data['type'] === 'angelleye_ppcp_onboarding') {
            ob_start();
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?> <?php echo $this->get_tooltip_html($data); // WPCS: XSS ok.       ?></label>
                </th>
                <td class="forminp">
                    <?php echo $this->angelleye_get_signup_link('yes'); ?>
                </td>
            </tr>
            <?php
            return ob_get_clean();
        }
    }

    public function angelleye_get_signup_link($testmode = 'yes') {
        try {
            include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/ppcp-gateway/class-paypal-rest-seller-onboarding.php');
            $seller_onboarding = new PayPal_Rest_Seller_Onboarding($testmode);
            echo $seller_onboarding->angelleye_genrate_signup_link();
        } catch (Exception $ex) {
            
        }
    }

}
