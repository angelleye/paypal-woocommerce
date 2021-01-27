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
            echo print_r($ex->getMessage());
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
    }

    public function angelleye_defind_hooks() {
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function init_form_fields() {
        try {
            include PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-ppcp-angelleye-settings.php';
            $gateway_settings = new WC_Gateway_PPCP_AngellEYE_Settings();
            $this->form_fields = $gateway_settings->angelleye_ppcp_setting_fields();
        } catch (Exception $ex) {
            
        }
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
        if (isset($_GET['section']) && 'angelleye_ppcp' === $_GET['section']) {
            wp_enqueue_style('wc-gateway-ppcp-angelleye-settings-css', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/css/angelleye-ppcp-gateway-admin.css', array(), time(), 'all');
            wp_enqueue_script('wc-gateway-ppcp-angelleye-settings', PAYPAL_FOR_WOOCOMMERCE_ASSET_URL . 'ppcp-gateway/js/wc-gateway-ppcp-angelleye-settings.js', array('jquery'), time(), true);
            wp_localize_script('wc-gateway-ppcp-angelleye-settings', 'ppcp_angelleye_param', array(
                'angelleye_ppcp_is_local_server' => ( angelleye_ppcp_is_local_server() == true) ? 'yes' : 'no',
                'angelleye_ppcp_onboarding_endpoint' => WC_AJAX::get_endpoint('ppcp_login_seller'),
                'angelleye_ppcp_onboarding_endpoint_nonce' => wp_create_nonce('ppcp_login_seller'),
                'is_sandbox_seller_onboarding_done' => $this->is_sandbox_seller_onboarding_done,
                'is_live_seller_onboarding_done' => $this->is_live_seller_onboarding_done,
                'abc' => $this->settings
                    )
            );
        }
    }

    public function generate_angelleye_ppcp_text_html($field_key, $data) {
        if (isset($data['type']) && $data['type'] === 'angelleye_ppcp_text') {
            $field_key = $this->get_field_key($field_key);
            ob_start();
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?> <?php echo $this->get_tooltip_html($data); // WPCS: XSS ok.                                   ?></label>
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
                    <label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?> <?php echo $this->get_tooltip_html($data); // WPCS: XSS ok.                                   ?></label>
                </th>
                <td class="forminp" id="<?php echo esc_attr($field_key); ?>">
                    <?php
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

    public function angelleye_get_signup_link($testmode = 'yes') {
        try {
            include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-seller-onboarding.php');
            $seller_onboarding = new AngellEYE_PayPal_PPCP_Seller_Onboarding();
            $seller_onboarding_result = $seller_onboarding->angelleye_genrate_signup_link($testmode);
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

}
