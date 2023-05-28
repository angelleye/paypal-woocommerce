<?php
defined('ABSPATH') || exit;

namespace Objectiv\Plugins\Checkout\Compatibility\Gateways;

use Objectiv\Plugins\Checkout\Compatibility\CompatibilityAbstract;

class AngellEYE_PayPal_PPCP_Checkout_WooCommerce extends CompatibilityAbstract {

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        
    }

    public function is_available(): bool {
        return class_exists('\\AngellEYE_PayPal_PPCP_Smart_Button');
    }

    public function typescript_class_and_params(array $compatibility): array {
        $compatibility[] = array(
            'class' => 'AngellEYE_PayPal_PPCP_Checkout_WooCommerce',
            'params' => array(),
        );

        return $compatibility;
    }

    public function pre_init() {
        add_action('cfw_before_process_checkout', array($this, 'maybe_unrequire_fields'));
    }

    public function maybe_unrequire_fields() {
        if (!defined('VERSION_PFW')) {
            return;
        }
        if (version_compare(VERSION_PFW, '1.5.7', '<')) {
            return;
        }
        if (!function_exists('angelleye_ppcp_has_active_session')) {
            return;
        }
        if (angelleye_ppcp_has_active_session()) {
            add_filter('cfw_enable_discrete_address_1_fields', '__return_false');
            add_filter('cfw_enable_fullname_field', '__return_false');
        }
    }

    public function run() {
        if (version_compare(VERSION_PFW, '1.5.7', '>=')) {
            $angelleye_paypal_ppcp_smart_button = \AngellEYE_PayPal_PPCP_Smart_Button::instance();
            if ($angelleye_paypal_ppcp_smart_button->is_valid_for_use() === false) {
                return;
            }
            add_filter('angelleye_ec_checkout_page_buy_now_nutton', array($this, 'modify_payment_button_output'), 10, 1);
            if (!empty($angelleye_paypal_ppcp_smart_button) && !empty($angelleye_paypal_ppcp_smart_button->checkout_page_display_option) && ( 'top' === $angelleye_paypal_ppcp_smart_button->checkout_page_display_option || 'both' === $angelleye_paypal_ppcp_smart_button->checkout_page_display_option )) {
                add_action('cfw_payment_request_buttons', array($this, 'add_paypal_express_to_checkout'));
            }
            remove_action('woocommerce_before_checkout_form', array($angelleye_paypal_ppcp_smart_button, 'display_paypal_button_top_checkout_page'), 5);
            if (angelleye_ppcp_has_active_session()) {
                add_filter('cfw_enable_discrete_address_1_fields', '__return_false');
                add_filter('cfw_enable_fullname_field', '__return_false');
                do_action('cfw_angelleye_paypal_ec_is_express_checkout');
                add_action('woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10);
                \AngellEYE_PayPal_PPCP_Smart_Button::instance()->ec_set_checkout_post_data();
                wc_maybe_define_constant('CFW_PAYMENT_BUTTON_SEPARATOR', true);
                add_filter('cfw_show_customer_information_tab', '__return_false');
                add_filter('cfw_show_shipping_tab', '__return_false');
                remove_action('cfw_checkout_before_order_review', 'cfw_breadcrumb_navigation', 10);
                remove_action('cfw_checkout_customer_info_tab', 'cfw_payment_request_buttons', 10);
                remove_action('cfw_checkout_customer_info_tab', 'cfw_customer_info_tab_heading', 20);
                remove_action('cfw_checkout_customer_info_tab', 'cfw_customer_info_tab_account', 30);
                remove_action('cfw_checkout_customer_info_tab', 'cfw_customer_info_tab_account_fields', 40);
                remove_action('cfw_checkout_customer_info_tab', 'cfw_customer_info_address', 50);
                remove_action('cfw_checkout_customer_info_tab', 'cfw_customer_info_tab_nav', 60);
                remove_action('cfw_checkout_shipping_method_tab', 'cfw_shipping_method_address_review_pane', 10);
                remove_action('cfw_checkout_shipping_method_tab', 'cfw_shipping_methods', 20);
                remove_action('cfw_checkout_shipping_method_tab', 'cfw_shipping_method_tab_nav', 30);
                remove_action('cfw_checkout_payment_method_tab', 'cfw_payment_tab_content_billing_address', 20);
                add_action(
                        'cfw_checkout_payment_method_tab',
                        function () use ($angelleye_paypal_ppcp_smart_button) {
                            echo '<h1>' . esc_html($angelleye_paypal_ppcp_smart_button->order_review_page_title) . '</h1>';
                        },
                        5
                );
                remove_all_actions('cfw_checkout_before_shipping_address');
                remove_all_actions('cfw_checkout_before_billing_address');
                add_action('cfw_checkout_payment_method_tab', 'cfw_customer_info_address', 6);
                if (WC()->cart->needs_shipping()) {
                    add_action(
                            'cfw_checkout_payment_method_tab',
                            function () {
                                cfw_shipping_methods();
                            },
                            7
                    );
                }
                add_action(
                        'cfw_checkout_payment_method_tab',
                        function () {
                            if (!WC()->cart->needs_shipping()) {
                                return;
                            }
                            ?>
                            <h3>
                                <?php
                                echo esc_html(apply_filters('cfw_billing_address_heading', __('Billing address', 'checkout-wc')));
                                ?>
                            </h3>
                            <?php
                        },
                        8
                );
                add_action('cfw_checkout_payment_method_tab', array($angelleye_paypal_ppcp_smart_button, 'paypal_billing_details'), 9);
                add_action(
                        'cfw_checkout_payment_method_tab',
                        function () {
                            ?>
                            <div id="cfw-billing-fields-container" class="cfw-radio-reveal-content <?php cfw_address_class_wrap(false); ?>">
                                <div class="cfw-input-wrap-row">
                                    <?php
                                    $billing_fields = WC()->checkout()->get_checkout_fields('billing');
                                    $email_field = $billing_fields['billing_email'];

                                    $AngellEYE_PayPal_PPCP_Smart_Button = \AngellEYE_PayPal_PPCP_Smart_Button::instance();
                                    $shipping_details = $AngellEYE_PayPal_PPCP_Smart_Button->ec_get_session_data('shipping_details');
                                    $email = WC()->checkout()->get_value('billing_email');

                                    if (empty($email)) {
                                        $email = !empty($shipping_details['email']) ? $shipping_details['email'] : '';
                                    }
                                    woocommerce_form_field('billing_email', $email_field, $email);
                                    ?>
                                </div>
                                <?php
                                if (WC()->cart->needs_shipping()) {
                                    cfw_output_billing_checkout_fields();
                                }
                                ?>
                                <div class="cfw-force-hidden">
                                    <div id="ship-to-different-address">
                                        <input id="ship-to-different-address-checkbox" type="checkbox" name="ship_to_different_address" value="<?php echo esc_attr(WC()->cart->needs_shipping_address() ? 1 : 0 ); ?>" checked="checked" />
                                    </div>
                                </div>
                            </div>
                            <?php
                        },
                        10
                );
                add_action(
                        'cfw_checkout_payment_method_tab',
                        function () {
                            ?>
                            <style type="text/css">
                                .cfw-add-field {
                                    display: none;
                                }
                                .cfw-review-pane-link a {
                                    display: none;
                                }

                                #cfw-breadcrumb {
                                    display: none !important;
                                }

                                #cfw-payment-action {
                                    display: block;
                                }

                                #cfw-payment-method {
                                    display: block !important;
                                    opacity: 1 !important;
                                }

                                #cfw-place-order {
                                    display: flex;
                                    align-items: center;
                                    justify-content: space-between;
                                }

                                #cfw-place-order > * {
                                    width: auto !important;
                                }

                                .angelleye_cancel {
                                    float: none !important;
                                }
                                .angelleye_smart_button_checkout_bottom {
                                    display: none !important;
                                }

                                #cfw-shipping-same-billing {
                                    display: none !important;
                                }

                                .cfw-return-to-shipping-btn {
                                    display: none;
                                }

                                #cfw-billing-methods {
                                    display: none;
                                }

                                .secure-notice {
                                    display: none;
                                }
                            </style>
                            <?php
                        },
                        100
                );
            }
        }
    }

    public function modify_payment_button_output($button_output) {
        $content_strings_to_remove = array(
            '<div style="clear:both; margin-bottom:10px;"></div>',
            '<div class="clear"></div>',
        );
        foreach ($content_strings_to_remove as $content_str) {
            $button_output = str_replace($content_str, '', $button_output);
        }
        return $button_output;
    }

    public function add_paypal_express_to_checkout() {
        global $AngellEYE_PayPal_PPCP_Smart_Button;
        if (cfw_is_checkout()) {
            $AngellEYE_PayPal_PPCP_Smart_Button = \AngellEYE_PayPal_PPCP_Smart_Button::instance();
            add_action(
                    'cfw_checkout_after_payment_methods',
                    function () {
                        global $AngellEYE_PayPal_PPCP_Smart_Button;
                        echo '<p class="paypal-cancel-wrapper">' . $AngellEYE_PayPal_PPCP_Smart_Button->angelleye_ppcp_cancel_button('') . '</p>';
                    }
            );
            $AngellEYE_PayPal_PPCP_Smart_Button->display_paypal_button_top_checkout_page();
            if (empty($AngellEYE_PayPal_PPCP_Smart_Button)) {
                return;
            }
            if (!angelleye_ppcp_has_active_session()) {
                add_action('cfw_after_payment_request_buttons', 'cfw_add_separator', 11);
            } else {
                add_action('cfw_checkout_before_customer_info_tab', array($this, 'add_notice'), 10);
            }
        }
    }

    public function add_notice() {
        ?>
        <div class="woocommerce-info">
            <?php esc_html_e('Logged in with PayPal. Please continue your order below.', 'checkout-wc'); ?>
        </div>
        <?php
    }

    public function hidden_email_field() {
        ob_start();
        do_action('woocommerce_checkout_billing');
        ob_get_clean();
        $billing_fields = WC()->checkout()->get_checkout_fields('billing');
        $email_field = $billing_fields['billing_email'];
        $AngellEYE_PayPal_PPCP_Smart_Button = \AngellEYE_PayPal_PPCP_Smart_Button::instance();
        $shipping_details = $AngellEYE_PayPal_PPCP_Smart_Button->ec_get_session_data('shipping_details');
        $email = WC()->checkout()->get_value('billing_email');
        if (empty($email)) {
            $email = !empty($shipping_details['email']) ? $shipping_details['email'] : '';
        }
        echo '<div style="display: none;">';
        woocommerce_form_field('billing_email', $email_field, $email);
        echo '</div>';
    }
}