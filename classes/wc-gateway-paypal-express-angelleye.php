<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_PayPal_Express_AngellEYE extends WC_Payment_Gateway {

    public $function_helper;
    public static $log_enabled = false;
    public static $log = false;
    public $checkout_fields;
    public $posted;
    public $is_multi_account_active;

    public function __construct() {
        $this->id = 'paypal_express';
        $this->home_url = is_ssl() ? home_url('/', 'https') : home_url('/');
        $this->method_title = __('PayPal Express Checkout ', 'paypal-for-woocommerce');
        $this->method_description = __('PayPal Express Checkout is designed to make the checkout experience for buyers using PayPal much more quick and easy than filling out billing and shipping forms.  Customers will be taken directly to PayPal to sign in and authorize the payment, and are then returned back to your store to choose a shipping method, review the final order total, and complete the payment.', 'paypal-for-woocommerce');
        $this->has_fields = false;
        $this->supports = array(
            'products',
            'refunds',
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
        );
        $this->is_paypal_credit_enable = true;
        
        $this->disallowed_funding_methods_array = array(
            'credit' => __('PayPal Credit', 'paypal-for-woocommerce'),
            'card' => __('Credit or Debit Card', 'paypal-for-woocommerce'),
            'bancontact' => __('Bancontact', 'paypal-for-woocommerce'),
            'blik' => __('BLIK', 'paypal-for-woocommerce'),
            'eps' => __('eps', 'paypal-for-woocommerce'),
            'giropay' => __('giropay', 'paypal-for-woocommerce'),
            'ideal' => __('iDEAL', 'paypal-for-woocommerce'),
            'mybank' => __('MyBank', 'paypal-for-woocommerce'),
            'p24' => __('Przelewy24', 'paypal-for-woocommerce'),
            'sepa' => __('SEPA-Lastschrift', 'paypal-for-woocommerce'),
            'sofort' => __('Sofort', 'paypal-for-woocommerce'),
            'venmo' => __('Venmo', 'paypal-for-woocommerce')
        );
        $this->button_label_array = array(
            'checkout' => __('Checkout', 'paypal-for-woocommerce'),
            'pay' => __('Pay', 'paypal-for-woocommerce'),
            'buynow' => __('Buy Now', 'paypal-for-woocommerce'),
            'paypal' => __('PayPal', 'paypal-for-woocommerce')
        );
        
        $this->init_form_fields();
        $this->init_settings();
        $this->send_items = 'yes' === $this->get_option('send_items', 'yes');
        $this->enable_tokenized_payments = $this->get_option('enable_tokenized_payments', 'no');
        if (class_exists('Paypal_For_Woocommerce_Multi_Account_Management')) {
            $this->enable_tokenized_payments = 'no';
            $this->is_multi_account_active = 'yes';
        } else {
            $this->is_multi_account_active = 'no';
        }
        if ($this->enable_tokenized_payments == 'yes') {
            $this->supports = array_merge($this->supports, array('add_payment_method', 'tokenization'));
        }
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->prevent_to_add_additional_item = 'yes' === $this->get_option('prevent_to_add_additional_item', 'no');
        $this->testmode = 'yes' === $this->get_option('testmode', 'yes');
        if ($this->testmode == false) {
            $this->testmode = AngellEYE_Utility::angelleye_paypal_for_woocommerce_is_set_sandbox_product();
        }
        $this->debug = 'yes' === $this->get_option('debug', 'no');
        self::$log_enabled = $this->debug;
        $this->error_email_notify = 'yes' === $this->get_option('error_email_notify', 'no');
        $this->show_on_checkout = $this->get_option('show_on_checkout', 'both');
        $this->paypal_account_optional = $this->get_option('paypal_account_optional', 'no');
        $this->error_display_type = $this->get_option('error_display_type', 'detailed');
        $this->landing_page = $this->get_option('landing_page', 'login');
        $this->checkout_logo = $this->get_option('checkout_logo', '');
        $this->checkout_logo_hdrimg = $this->get_option('checkout_logo_hdrimg', '');
        $this->show_paypal_credit = $this->get_option('show_paypal_credit', 'yes');
        $this->brand_name = $this->get_option('brand_name', get_bloginfo('name'));
        $this->customer_service_number = $this->get_option('customer_service_number', '');
        $this->use_wp_locale_code = $this->get_option('use_wp_locale_code', 'yes');
        $this->angelleye_skip_text = !empty($this->setting['angelleye_skip_text']) ? $this->setting['angelleye_skip_text'] : __('Skip the forms and pay faster with PayPal!', 'paypal-for-woocommerce');
        if($this->angelleye_skip_text === 'Skip the forms and pay faster with PayPal!') {
            $this->angelleye_skip_text = __('Skip the forms and pay faster with PayPal!', 'paypal-for-woocommerce');
        }
        $this->skip_final_review = $this->get_option('skip_final_review', 'no');
        $this->disable_term = $this->get_option('disable_term', 'no');
        $this->payment_action = $this->get_option('payment_action', 'Sale');
        $this->billing_address = 'yes' === $this->get_option('billing_address', 'no');
        if ($this->send_items === false) {
            $this->subtotal_mismatch_behavior = 'drop';
        } else {
            $this->subtotal_mismatch_behavior = $this->get_option('subtotal_mismatch_behavior', 'add');
        }
        $this->order_cancellations = $this->get_option('order_cancellations', 'disabled');
        $this->email_notify_order_cancellations = 'yes' === $this->get_option('email_notify_order_cancellations', 'no');
        $this->customer_id = get_current_user_id();
        $this->enable_notifyurl = $this->get_option('enable_notifyurl', 'no');
        $this->notifyurl = '';
        $this->is_encrypt = $this->get_option('is_encrypt', 'no');
        $this->cancel_page_id = $this->get_option('cancel_page', '');
        $this->fraud_management_filters = $this->get_option('fraud_management_filters', 'place_order_on_hold_for_further_review');
        $this->invoice_id_prefix = $this->get_option('invoice_id_prefix', '');
        $this->show_on_minicart = $this->get_option('show_on_minicart', 'no');
        $this->pending_authorization_order_status = $this->get_option('pending_authorization_order_status', 'On Hold');
        $this->enable_in_context_checkout_flow = $this->get_option('enable_in_context_checkout_flow', 'yes');
        if ($this->enable_notifyurl == 'yes') {
            $this->notifyurl = $this->get_option('notifyurl');
            if (isset($this->notifyurl) && !empty($this->notifyurl)) {
                $this->notifyurl = str_replace('&amp;', '&', $this->notifyurl);
            }
        }
        if ($this->is_paypal_credit_enable == false) {
            $this->show_paypal_credit = 'no';
        }
        if ($this->testmode == true) {
            $this->API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
            $this->PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
            $this->api_username = $this->get_option('sandbox_api_username');
            $this->api_password = $this->get_option('sandbox_api_password');
            $this->api_signature = $this->get_option('sandbox_api_signature');
        } else {
            $this->API_Endpoint = "https://api-3t.paypal.com/nvp";
            $this->PAYPAL_URL = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
            $this->api_username = $this->get_option('api_username');
            $this->api_password = $this->get_option('api_password');
            $this->api_signature = $this->get_option('api_signature');
        }
        $this->button_position = $this->get_option('button_position', 'bottom');
        $this->show_on_cart = $this->get_option('show_on_cart', 'yes');
        $this->checkout_with_pp_button_type = $this->get_option('checkout_with_pp_button_type', 'paypalimage');
        $this->pp_button_type_text_button = $this->get_option('pp_button_type_text_button', 'Proceed to Checkout');
        $this->pp_button_type_my_custom = $this->get_option('pp_button_type_my_custom', self::angelleye_get_paypalimage());
        $this->softdescriptor = $this->get_option('softdescriptor', '');
        $this->version = "64";
        $this->Force_tls_one_point_two = get_option('Force_tls_one_point_two', 'no');
        $this->page_style = $this->get_option('page_style', '');
        $this->review_button_label = $this->get_option('review_button_label', __('Place Order', 'paypal-for-woocommerce'));
        $this->checkout_button_label = $this->get_option('checkout_button_label', __('Proceed to PayPal', 'paypal-for-woocommerce'));
        $this->checkout_page_disallowed_funding_methods = $this->get_option('checkout_page_disallowed_funding_methods', array());
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'), 999);
        add_filter('woocommerce_settings_api_sanitized_fields_' . $this->id, array($this, 'angelleye_express_checkout_encrypt_gateway_api'), 10, 1);
        if (!has_action('woocommerce_api_' . strtolower('WC_Gateway_PayPal_Express_AngellEYE'))) {
            add_action('woocommerce_api_' . strtolower('WC_Gateway_PayPal_Express_AngellEYE'), array($this, 'handle_wc_api'));
        }
        if (!class_exists('WC_Gateway_PayPal_Express_Function_AngellEYE')) {
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/express-checkout/class-wc-gateway-paypal-express-function-angelleye.php' );
        }
        $this->function_helper = new WC_Gateway_PayPal_Express_Function_AngellEYE();
        $this->order_button_text = ($this->function_helper->ec_is_express_checkout() == false) ? $this->checkout_button_label : $this->review_button_label;
        //do_action( 'angelleye_paypal_for_woocommerce_multi_account_api_' . $this->id, $this, null, null );
        
        if (!empty($_POST['wc-paypal_express-payment-token']) && $_POST['wc-paypal_express-payment-token'] != 'new') {
            return;
        } else {
            if (version_compare(WC_VERSION, '3.0', '<')) {
                add_action('woocommerce_after_checkout_validation', array($this, 'angelleye_paypal_express_checkout_redirect_to_paypal'), 99, 1);
            } else {
                add_action('woocommerce_after_checkout_validation', array($this, 'angelleye_paypal_express_checkout_redirect_to_paypal'), 99, 2);
            }
        }
        
    }

    public function process_admin_options() {
        parent::process_admin_options();
        delete_option('angelleye_express_checkout_default_pal');
        $this->angelleye_get_merchant_id();
    }

    public function admin_options() {
        wp_enqueue_script( 'woocommerce_admin' );
        wp_enqueue_script( 'wc-clipboard' );
        
        global $current_user;
        $user_id = $current_user->ID;
        $GLOBALS['hide_save_button'] = true;
        ?>
        <h3><?php _e('PayPal Express Checkout', 'paypal-for-woocommerce'); ?></h3>
        <p><?php _e($this->method_description, 'paypal-for-woocommerce'); ?></p>
        <div id="angelleye_paypal_marketing_table">
            <table class="form-table">
                <?php
                if (version_compare(WC_VERSION, '2.6', '<')) {
                    AngellEYE_Utility::woo_compatibility_notice();
                } else {
                    $this->generate_settings_html();
                }
                ?>
            </table>
            <p class="submit">
                <button name="save" class="button-primary woocommerce-save-button" type="submit" value="<?php esc_attr_e('Save changes', 'paypal-for-woocommerce'); ?>"><?php esc_html_e('Save changes', 'paypal-for-woocommerce'); ?></button>
                <?php wp_nonce_field('woocommerce-settings'); ?>
            </p>
        </div>
        <?php
        AngellEYE_Utility::angelleye_display_marketing_sidebar($this->id);
        add_thickbox();
        $guest_checkout = get_option('woocommerce_enable_guest_checkout', 'yes');
        if ('yes' === get_option('woocommerce_registration_generate_username') && 'yes' === get_option('woocommerce_registration_generate_password')) {
            $guest_checkout = 'yes';
        }
        if (wc_get_page_id('terms') > 0 && apply_filters('woocommerce_checkout_show_terms', true)) {
            if ($guest_checkout === 'yes') {
                $display_disable_terms = 'yes';
            } else {
                $display_disable_terms = 'no';
            }
        } else {
            $display_disable_terms = 'no';
        }
        ?>
        
        <style>
            .woocommerce table.form-table .select2-container {
                min-width: 150px !important;
            }
            #pms-muse-container .form-table th {
                width: 100px !important;
            }
        </style>
        
        <script type="text/javascript">
        
            jQuery('.pms-view-more').on('click', function (event) {
                event.preventDefault();
                var win = window.open('https://www.angelleye.com/paypal-buy-now-pay-later/?utm_source=pfw&utm_medium=settings_more_info&utm_campaign=bnpl', '_blank');
                win.focus();
            });
            var home_page_credit_messaging_preview = function () {
                var home_style_object = {};
                home_style_object['layout'] = jQuery('#woocommerce_paypal_express_credit_messaging_home_layout_type').val();
                if (home_style_object['layout'] === 'text') {
                    home_style_object['logo'] = {};
                    home_style_object['logo']['type'] = jQuery('#woocommerce_paypal_express_credit_messaging_home_text_layout_logo_type').val();
                    if (home_style_object['logo']['type'] === 'primary' || home_style_object['logo']['type'] === 'alternative') {
                        home_style_object['logo']['position'] = jQuery('#woocommerce_paypal_express_credit_messaging_home_text_layout_logo_position').val();
                    }
                    home_style_object['text'] = {};
                    home_style_object['text']['size'] = parseInt(jQuery('#woocommerce_paypal_express_credit_messaging_home_text_layout_text_size').val());
                    home_style_object['text']['color'] = jQuery('#woocommerce_paypal_express_credit_messaging_home_text_layout_text_color').val();
                } else {
                    home_style_object['color'] = jQuery('#woocommerce_paypal_express_credit_messaging_home_flex_layout_color').val();
                    home_style_object['ratio'] = jQuery('#woocommerce_paypal_express_credit_messaging_home_flex_layout_ratio').val();
                }
                if (typeof paypal !== 'undefined' && is_credit_messaging_home_page_enable()) {
                    paypal.Messages({
                        amount: 500,
                        placement: 'home',
                        style: home_style_object
                    }).render('.pp_message_home');
                }
            };

            var hide_show_home_shortcode = function () {

                jQuery('#woocommerce_paypal_express_credit_messaging_home_shortcode').change(function () {
                    var home_preview_shortcode = jQuery('#woocommerce_paypal_express_credit_messaging_home_preview_shortcode').closest('tr');
                    if (jQuery(this).is(':checked')) {
                        if (is_credit_messaging_enable() === true && is_credit_messaging_home_page_enable()) {
                            home_preview_shortcode.show();
                        }
                    } else {
                        home_preview_shortcode.hide();
                    }
                }).change();

            };

            var hide_show_category_shortcode = function () {

                jQuery('#woocommerce_paypal_express_credit_messaging_category_shortcode').change(function () {
                    var category_preview_shortcode = jQuery('#woocommerce_paypal_express_credit_messaging_category_preview_shortcode').closest('tr');
                    if (jQuery(this).is(':checked')) {
                        if (is_credit_messaging_enable() === true && is_credit_messaging_category_page_enable()) {
                            category_preview_shortcode.show();
                        }
                    } else {
                        category_preview_shortcode.hide();
                    }
                }).change();

            };

            var initTipTip = function( css_class ) {
                    jQuery( document.body )
                            .on( 'click', css_class, function( evt ) {
                                    evt.preventDefault();
                                    wcClearClipboard();
                                    wcSetClipboard( jQuery.trim( jQuery( this ).prev( 'input' ).val() ), jQuery( css_class ) );

                            } )
                            .on( 'aftercopy', css_class, function() {
                                    jQuery( '#copy-error' ).text( '' );
                                    jQuery( css_class ).tipTip( {
                                            'attribute':  'data-tip',
                                            'activation': 'focus',
                                            'fadeIn':     50,
                                            'fadeOut':    50,
                                            'delay':      0
                                    } ).focus();
                            } );

            };

            var hide_show_product_shortcode = function () {

                jQuery('#woocommerce_paypal_express_credit_messaging_product_shortcode').change(function () {
                    var product_preview_shortcode = jQuery('#woocommerce_paypal_express_credit_messaging_product_preview_shortcode').closest('tr');
                    if (jQuery(this).is(':checked')) {
                        if (is_credit_messaging_enable() === true && is_credit_messaging_product_page_enable()) {
                            product_preview_shortcode.show();
                        }
                    } else {
                        product_preview_shortcode.hide();
                    }
                }).change();

            };


            var hide_show_cart_shortcode = function () {

                jQuery('#woocommerce_paypal_express_credit_messaging_cart_shortcode').change(function () {
                    var cart_preview_shortcode = jQuery('#woocommerce_paypal_express_credit_messaging_cart_preview_shortcode').closest('tr');
                    if (jQuery(this).is(':checked')) {
                        if (is_credit_messaging_enable() === true && is_credit_messaging_cart_page_enable()) {
                            cart_preview_shortcode.show();
                        }
                    } else {
                        cart_preview_shortcode.hide();
                    }
                }).change();

            };

            var hide_show_payment_shortcode = function () {

                jQuery('#woocommerce_paypal_express_credit_messaging_payment_shortcode').change(function () {
                    var payment_preview_shortcode = jQuery('#woocommerce_paypal_express_credit_messaging_payment_preview_shortcode').closest('tr');
                    if (jQuery(this).is(':checked')) {
                        if (is_credit_messaging_enable() === true && is_credit_messaging_payment_page_enable()) {
                            payment_preview_shortcode.show();
                        }
                    } else {
                        payment_preview_shortcode.hide();
                    }
                }).change();

            };

            var category_page_credit_messaging_preview = function () {
                var category_style_object = {};
                category_style_object['layout'] = jQuery('#woocommerce_paypal_express_credit_messaging_category_layout_type').val();
                if (category_style_object['layout'] === 'text') {
                    category_style_object['logo'] = {};
                    category_style_object['logo']['type'] = jQuery('#woocommerce_paypal_express_credit_messaging_category_text_layout_logo_type').val();
                    if (category_style_object['logo']['type'] === 'primary' || category_style_object['logo']['type'] === 'alternative') {
                        category_style_object['logo']['position'] = jQuery('#woocommerce_paypal_express_credit_messaging_category_text_layout_logo_position').val();
                    }
                    category_style_object['text'] = {};
                    category_style_object['text']['size'] = parseInt(jQuery('#woocommerce_paypal_express_credit_messaging_category_text_layout_text_size').val());
                    category_style_object['text']['color'] = jQuery('#woocommerce_paypal_express_credit_messaging_category_text_layout_text_color').val();
                } else {
                    category_style_object['color'] = jQuery('#woocommerce_paypal_express_credit_messaging_category_flex_layout_color').val();
                    category_style_object['ratio'] = jQuery('#woocommerce_paypal_express_credit_messaging_category_flex_layout_ratio').val();
                }
                if (typeof paypal !== 'undefined' && is_credit_messaging_category_page_enable()) {
                    paypal.Messages({
                        amount: 500,
                        placement: 'category',
                        style: category_style_object
                    }).render('.pp_message_category');
                }
            };
            var product_page_credit_messaging_preview = function () {
                var product_style_object = {};
                product_style_object['layout'] = jQuery('#woocommerce_paypal_express_credit_messaging_product_layout_type').val();
                if (product_style_object['layout'] === 'text') {
                    product_style_object['logo'] = {};
                    product_style_object['logo']['type'] = jQuery('#woocommerce_paypal_express_credit_messaging_product_text_layout_logo_type').val();
                    if (product_style_object['logo']['type'] === 'primary' || product_style_object['logo']['type'] === 'alternative') {
                        product_style_object['logo']['position'] = jQuery('#woocommerce_paypal_express_credit_messaging_product_text_layout_logo_position').val();
                    }
                    product_style_object['text'] = {};
                    product_style_object['text']['size'] = parseInt(jQuery('#woocommerce_paypal_express_credit_messaging_product_text_layout_text_size').val());
                    product_style_object['text']['color'] = jQuery('#woocommerce_paypal_express_credit_messaging_product_text_layout_text_color').val();
                } else {
                    product_style_object['color'] = jQuery('#woocommerce_paypal_express_credit_messaging_product_flex_layout_color').val();
                    product_style_object['ratio'] = jQuery('#woocommerce_paypal_express_credit_messaging_product_flex_layout_ratio').val();
                }
                if (typeof paypal !== 'undefined' && is_credit_messaging_product_page_enable()) {
                    paypal.Messages({
                        amount: 500,
                        placement: 'product',
                        style: product_style_object
                    }).render('.pp_message_product');
                }
            };
            var cart_page_credit_messaging_preview = function () {
                var cart_style_object = {};
                cart_style_object['layout'] = jQuery('#woocommerce_paypal_express_credit_messaging_cart_layout_type').val();
                if (cart_style_object['layout'] === 'text') {
                    cart_style_object['logo'] = {};
                    cart_style_object['logo']['type'] = jQuery('#woocommerce_paypal_express_credit_messaging_cart_text_layout_logo_type').val();
                    if (cart_style_object['logo']['type'] === 'primary' || cart_style_object['logo']['type'] === 'alternative') {
                        cart_style_object['logo']['position'] = jQuery('#woocommerce_paypal_express_credit_messaging_cart_text_layout_logo_position').val();
                    }
                    cart_style_object['text'] = {};
                    cart_style_object['text']['size'] = parseInt(jQuery('#woocommerce_paypal_express_credit_messaging_cart_text_layout_text_size').val());
                    cart_style_object['text']['color'] = jQuery('#woocommerce_paypal_express_credit_messaging_cart_text_layout_text_color').val();
                } else {
                    cart_style_object['color'] = jQuery('#woocommerce_paypal_express_credit_messaging_cart_flex_layout_color').val();
                    cart_style_object['ratio'] = jQuery('#woocommerce_paypal_express_credit_messaging_cart_flex_layout_ratio').val();
                }
                if (typeof paypal !== 'undefined' && is_credit_messaging_cart_page_enable()) {
                    paypal.Messages({
                        amount: 500,
                        placement: 'cart',
                        style: cart_style_object
                    }).render('.pp_message_cart');
                }
            };
            var payment_page_credit_messaging_preview = function () {
                var payment_style_object = {};
                payment_style_object['layout'] = jQuery('#woocommerce_paypal_express_credit_messaging_payment_layout_type').val();
                if (payment_style_object['layout'] === 'text') {
                    payment_style_object['logo'] = {};
                    payment_style_object['logo']['type'] = jQuery('#woocommerce_paypal_express_credit_messaging_payment_text_layout_logo_type').val();
                    if (payment_style_object['logo']['type'] === 'primary' || payment_style_object['logo']['type'] === 'alternative') {
                        payment_style_object['logo']['position'] = jQuery('#woocommerce_paypal_express_credit_messaging_payment_text_layout_logo_position').val();
                    }
                    payment_style_object['text'] = {};
                    payment_style_object['text']['size'] = parseInt(jQuery('#woocommerce_paypal_express_credit_messaging_payment_text_layout_text_size').val());
                    payment_style_object['text']['color'] = jQuery('#woocommerce_paypal_express_credit_messaging_payment_text_layout_text_color').val();
                } else {
                    payment_style_object['color'] = jQuery('#woocommerce_paypal_express_credit_messaging_payment_flex_layout_color').val();
                    payment_style_object['ratio'] = jQuery('#woocommerce_paypal_express_credit_messaging_payment_flex_layout_ratio').val();
                }
                if (typeof paypal !== 'undefined' && is_credit_messaging_payment_page_enable()) {
                    paypal.Messages({
                        amount: 500,
                        placement: 'payment',
                        style: payment_style_object
                    }).render('.pp_message_payment');
                }
            };
            jQuery(document).ready(function ($) {
                jQuery('.credit_messaging_home_field').change(function () {
                    home_page_credit_messaging_preview();
                });
                jQuery('.credit_messaging_category_field').change(function () {
                    category_page_credit_messaging_preview();
                });
                jQuery('.credit_messaging_product_field').change(function () {
                    product_page_credit_messaging_preview();
                });
                jQuery('.credit_messaging_cart_field').change(function () {
                    cart_page_credit_messaging_preview();
                });
                jQuery('.credit_messaging_payment_field').change(function () {
                    payment_page_credit_messaging_preview();
                });
                home_page_credit_messaging_preview();
                category_page_credit_messaging_preview();
                product_page_credit_messaging_preview();
                cart_page_credit_messaging_preview();
                payment_page_credit_messaging_preview();
                $('.preview_shortcode').after('<button type="button" class="button-secondary copy-shortcode" data-tip="Copied!">Copy</button>');

            });
            setTimeout(function() {
                jQuery('#woocommerce_paypal_express_enabled_credit_messaging').trigger('change');
                initTipTip( '.copy-shortcode' );
              }, 5000);
            var is_credit_messaging_enable = function () {
                if (jQuery('#woocommerce_paypal_express_enabled_credit_messaging').is(':checked')) {
                    return true;
                }
                return false;
            };
            var is_credit_messaging_home_page_enable = function () {
                if (is_credit_messaging_enable() === false) {
                    return false;
                }
                if (jQuery.inArray('home', jQuery('#woocommerce_paypal_express_credit_messaging_page_type').val()) === -1) {
                    return false;
                }
                return true;
            };
            var credit_messaging_home_page_hide_show = function () {
                credit_messaging_home_field_parent = jQuery('.credit_messaging_home_field').closest('tr');
                credit_messaging_home_field_p_tag = jQuery('.credit_messaging_home_field').next("p");
                credit_messaging_home_field = jQuery('.credit_messaging_home_field');
                credit_messaging_home_base_field_parent = jQuery('.credit_messaging_home_base_field').closest('tr');
                credit_messaging_home_base_field_p_tag = jQuery('.credit_messaging_home_base_field').next("p");
                credit_messaging_home_base_field = jQuery('.credit_messaging_home_base_field');
                credit_messaging_home_preview = jQuery('#woocommerce_paypal_express_credit_messaging_home_preview');
                if (is_credit_messaging_home_page_enable()) {
                    credit_messaging_home_field_parent.show();
                    credit_messaging_home_field.show();
                    credit_messaging_home_field_p_tag.show();
                    credit_messaging_home_base_field_parent.show();
                    credit_messaging_home_base_field.show();
                    credit_messaging_home_base_field_p_tag.show();
                    credit_messaging_home_preview.show();
                } else {
                    credit_messaging_home_field_parent.hide();
                    credit_messaging_home_field.hide();
                    credit_messaging_home_field_p_tag.hide();
                    credit_messaging_home_base_field_parent.hide();
                    credit_messaging_home_base_field.hide();
                    credit_messaging_home_base_field_p_tag.hide();
                    credit_messaging_home_preview.hide();
                }
                jQuery('#woocommerce_paypal_express_credit_messaging_home_layout_type').trigger('change');
                hide_show_home_shortcode();
            };
            var is_credit_messaging_category_page_enable = function () {
                if (is_credit_messaging_enable() === false) {
                    return false;
                }
                if (jQuery.inArray('category', jQuery('#woocommerce_paypal_express_credit_messaging_page_type').val()) === -1) {
                    return false;
                }
                return true;
            };
            var credit_messaging_category_page_hide_show = function () {
                credit_messaging_category_field_parent = jQuery('.credit_messaging_category_field').closest('tr');
                credit_messaging_category_field_p_tag = jQuery('.credit_messaging_category_field').next("p");
                credit_messaging_category_field = jQuery('.credit_messaging_category_field');
                credit_messaging_category_base_field_parent = jQuery('.credit_messaging_category_base_field').closest('tr');
                credit_messaging_category_base_field_p_tag = jQuery('.credit_messaging_category_base_field').next("p");
                credit_messaging_category_base_field = jQuery('.credit_messaging_category_base_field');
                credit_messaging_category_preview = jQuery('#woocommerce_paypal_express_credit_messaging_category_preview');
                if (is_credit_messaging_category_page_enable()) {
                    credit_messaging_category_field_parent.show();
                    credit_messaging_category_field.show();
                    credit_messaging_category_field_p_tag.show();
                    credit_messaging_category_base_field_parent.show();
                    credit_messaging_category_base_field.show();
                    credit_messaging_category_base_field_p_tag.show();
                    credit_messaging_category_preview.show();
                } else {
                    credit_messaging_category_field_parent.hide();
                    credit_messaging_category_field.hide();
                    credit_messaging_category_field_p_tag.hide();
                    credit_messaging_category_base_field_parent.hide();
                    credit_messaging_category_base_field.hide();
                    credit_messaging_category_base_field_p_tag.hide();
                    credit_messaging_category_preview.hide();
                }
                jQuery('#woocommerce_paypal_express_credit_messaging_category_layout_type').trigger('change');
                hide_show_category_shortcode();
            };
            var is_credit_messaging_product_page_enable = function () {
                if (is_credit_messaging_enable() === false) {
                    return false;
                }
                if (jQuery.inArray('product', jQuery('#woocommerce_paypal_express_credit_messaging_page_type').val()) === -1) {
                    return false;
                }
                return true;
            };
            var credit_messaging_product_page_hide_show = function () {
                credit_messaging_product_field_parent = jQuery('.credit_messaging_product_field').closest('tr');
                credit_messaging_product_field_p_tag = jQuery('.credit_messaging_product_field').next("p");
                credit_messaging_product_field = jQuery('.credit_messaging_product_field');
                credit_messaging_product_base_field_parent = jQuery('.credit_messaging_product_base_field').closest('tr');
                credit_messaging_product_base_field_p_tag = jQuery('.credit_messaging_product_base_field').next("p");
                credit_messaging_product_base_field = jQuery('.credit_messaging_product_base_field');
                credit_messaging_product_preview = jQuery('#woocommerce_paypal_express_credit_messaging_product_preview');
                if (is_credit_messaging_product_page_enable()) {
                    credit_messaging_product_field_parent.show();
                    credit_messaging_product_field.show();
                    credit_messaging_product_field_p_tag.show();
                    credit_messaging_product_base_field_parent.show();
                    credit_messaging_product_base_field.show();
                    credit_messaging_product_base_field_p_tag.show();
                    credit_messaging_product_preview.show();
                } else {
                    credit_messaging_product_field_parent.hide();
                    credit_messaging_product_field.hide();
                    credit_messaging_product_field_p_tag.hide();
                    credit_messaging_product_base_field_parent.hide();
                    credit_messaging_product_base_field.hide();
                    credit_messaging_product_base_field_p_tag.hide();
                    credit_messaging_product_preview.hide();
                }
                jQuery('#woocommerce_paypal_express_credit_messaging_product_layout_type').trigger('change');
                hide_show_product_shortcode();
            };
            var is_credit_messaging_cart_page_enable = function () {
                if (is_credit_messaging_enable() === false) {
                    return false;
                }
                if (jQuery.inArray('cart', jQuery('#woocommerce_paypal_express_credit_messaging_page_type').val()) === -1) {
                    return false;
                }
                return true;
            };
            var credit_messaging_cart_page_hide_show = function () {
                credit_messaging_cart_field_parent = jQuery('.credit_messaging_cart_field').closest('tr');
                credit_messaging_cart_field_p_tag = jQuery('.credit_messaging_cart_field').next("p");
                credit_messaging_cart_field = jQuery('.credit_messaging_cart_field');
                credit_messaging_cart_base_field_parent = jQuery('.credit_messaging_cart_base_field').closest('tr');
                credit_messaging_cart_base_field_p_tag = jQuery('.credit_messaging_cart_base_field').next("p");
                credit_messaging_cart_base_field = jQuery('.credit_messaging_cart_base_field');
                credit_messaging_cart_preview = jQuery('#woocommerce_paypal_express_credit_messaging_cart_preview');
                if (is_credit_messaging_cart_page_enable()) {
                    credit_messaging_cart_field_parent.show();
                    credit_messaging_cart_field.show();
                    credit_messaging_cart_field_p_tag.show();
                    credit_messaging_cart_base_field_parent.show();
                    credit_messaging_cart_base_field.show();
                    credit_messaging_cart_base_field_p_tag.show();
                    credit_messaging_cart_preview.show();
                } else {
                    credit_messaging_cart_field_parent.hide();
                    credit_messaging_cart_field.hide();
                    credit_messaging_cart_field_p_tag.hide();
                    credit_messaging_cart_base_field_parent.hide();
                    credit_messaging_cart_base_field.hide();
                    credit_messaging_cart_base_field_p_tag.hide();
                    credit_messaging_cart_preview.hide();
                }
                jQuery('#woocommerce_paypal_express_credit_messaging_cart_layout_type').trigger('change');
                hide_show_cart_shortcode();
            };
            var is_credit_messaging_payment_page_enable = function () {
                if (is_credit_messaging_enable() === false) {
                    return false;
                }
                if (jQuery.inArray('payment', jQuery('#woocommerce_paypal_express_credit_messaging_page_type').val()) === -1) {
                    return false;
                }
                return true;
            };
            var credit_messaging_payment_page_hide_show = function () {
                credit_messaging_payment_field_parent = jQuery('.credit_messaging_payment_field').closest('tr');
                credit_messaging_payment_field_p_tag = jQuery('.credit_messaging_payment_field').next("p");
                credit_messaging_payment_field = jQuery('.credit_messaging_payment_field');
                credit_messaging_payment_base_field_parent = jQuery('.credit_messaging_payment_base_field').closest('tr');
                credit_messaging_payment_base_field_p_tag = jQuery('.credit_messaging_payment_base_field').next("p");
                credit_messaging_payment_base_field = jQuery('.credit_messaging_payment_base_field');
                credit_messaging_payment_preview = jQuery('#woocommerce_paypal_express_credit_messaging_payment_preview');
                if (is_credit_messaging_payment_page_enable()) {
                    credit_messaging_payment_field_parent.show();
                    credit_messaging_payment_field.show();
                    credit_messaging_payment_field_p_tag.show();
                    credit_messaging_payment_base_field_parent.show();
                    credit_messaging_payment_base_field.show();
                    credit_messaging_payment_base_field_p_tag.show();
                    credit_messaging_payment_preview.show();
                } else {
                    credit_messaging_payment_field_parent.hide();
                    credit_messaging_payment_field.hide();
                    credit_messaging_payment_field_p_tag.hide();
                    credit_messaging_payment_base_field_parent.hide();
                    credit_messaging_payment_base_field.hide();
                    credit_messaging_payment_base_field_p_tag.hide();
                    credit_messaging_payment_preview.hide();
                }
                jQuery('#woocommerce_paypal_express_credit_messaging_payment_layout_type').trigger('change');
                hide_show_payment_shortcode();
            };
            jQuery('#woocommerce_paypal_express_enabled_credit_messaging').change(function () {
                credit_messaging_field_parent = jQuery('.credit_messaging_field').closest('tr');
                credit_messaging_field_p_tag = jQuery('.credit_messaging_field').next("p");
                credit_messaging_field = jQuery('.credit_messaging_field');
                if (jQuery(this).is(':checked')) {
                    credit_messaging_field_parent.show();
                    credit_messaging_field.show();
                    credit_messaging_field_p_tag.show();
                } else {
                    credit_messaging_field_parent.hide();
                    credit_messaging_field.hide();
                    credit_messaging_field_p_tag.hide();
                }
                jQuery('#woocommerce_paypal_express_credit_messaging_page_type').trigger('change');
            }).change();
            jQuery('#woocommerce_paypal_express_credit_messaging_page_type').change(function () {
                credit_messaging_home_page_hide_show();
                credit_messaging_category_page_hide_show();
                credit_messaging_product_page_hide_show();
                credit_messaging_cart_page_hide_show();
                credit_messaging_payment_page_hide_show();
            }).change();

            jQuery('#woocommerce_paypal_express_credit_messaging_home_layout_type').change(function () {
                credit_messaging_home_text_layout_field_parent = jQuery('.credit_messaging_home_text_layout_field').closest('tr');
                credit_messaging_home_text_layout_field_p_tag = jQuery('.credit_messaging_home_text_layout_field').next("p");
                credit_messaging_home_text_layout_field = jQuery('.credit_messaging_home_text_layout_field');
                credit_messaging_home_flex_layout_field_parent = jQuery('.credit_messaging_home_flex_layout_field').closest('tr');
                credit_messaging_home_flex_layout_field_p_tag = jQuery('.credit_messaging_home_flex_layout_field').next("p");
                credit_messaging_home_flex_layout_field = jQuery('.credit_messaging_home_flex_layout_field');
                if (this.value === 'text') {
                    if (is_credit_messaging_home_page_enable()) {
                        credit_messaging_home_text_layout_field_parent.show();
                        credit_messaging_home_text_layout_field.show();
                        credit_messaging_home_text_layout_field_p_tag.show();
                        credit_messaging_home_flex_layout_field_parent.hide();
                        credit_messaging_home_flex_layout_field_p_tag.hide();
                        credit_messaging_home_flex_layout_field.hide();
                    }
                } else {
                    if (is_credit_messaging_home_page_enable()) {
                        credit_messaging_home_flex_layout_field_parent.show();
                        credit_messaging_home_flex_layout_field_p_tag.show();
                        credit_messaging_home_flex_layout_field.show();
                    }
                    credit_messaging_home_text_layout_field_parent.hide();
                    credit_messaging_home_text_layout_field.hide();
                    credit_messaging_home_text_layout_field_p_tag.hide();
                }
                jQuery('#woocommerce_paypal_express_credit_messaging_home_text_layout_logo_type').trigger('change');
            }).change();
            jQuery('#woocommerce_paypal_express_credit_messaging_home_text_layout_logo_type').change(function () {
                credit_messaging_home_text_layout_logo_position = jQuery('#woocommerce_paypal_express_credit_messaging_home_text_layout_logo_position').closest('tr');
                if (jQuery('#woocommerce_paypal_express_credit_messaging_home_layout_type').val() === 'text' && (this.value === 'primary' || this.value === 'alternative')) {
                    if (is_credit_messaging_home_page_enable()) {
                        credit_messaging_home_text_layout_logo_position.show();
                    }
                } else {
                    credit_messaging_home_text_layout_logo_position.hide();
                }
            }).change();
            jQuery('#woocommerce_paypal_express_credit_messaging_category_layout_type').change(function () {
                credit_messaging_category_text_layout_field_parent = jQuery('.credit_messaging_category_text_layout_field').closest('tr');
                credit_messaging_category_text_layout_field_p_tag = jQuery('.credit_messaging_category_text_layout_field').next("p");
                credit_messaging_category_text_layout_field = jQuery('.credit_messaging_category_text_layout_field');
                credit_messaging_category_flex_layout_field_parent = jQuery('.credit_messaging_category_flex_layout_field').closest('tr');
                credit_messaging_category_flex_layout_field_p_tag = jQuery('.credit_messaging_category_flex_layout_field').next("p");
                credit_messaging_category_flex_layout_field = jQuery('.credit_messaging_category_flex_layout_field');
                if (this.value === 'text') {
                    if (is_credit_messaging_category_page_enable()) {
                        credit_messaging_category_text_layout_field_parent.show();
                        credit_messaging_category_text_layout_field.show();
                        credit_messaging_category_text_layout_field_p_tag.show();
                        credit_messaging_category_flex_layout_field_parent.hide();
                        credit_messaging_category_flex_layout_field_p_tag.hide();
                        credit_messaging_category_flex_layout_field.hide();
                    }
                } else {
                    if (is_credit_messaging_category_page_enable()) {
                        credit_messaging_category_flex_layout_field_parent.show();
                        credit_messaging_category_flex_layout_field_p_tag.show();
                        credit_messaging_category_flex_layout_field.show();
                    }
                    credit_messaging_category_text_layout_field_parent.hide();
                    credit_messaging_category_text_layout_field.hide();
                    credit_messaging_category_text_layout_field_p_tag.hide();
                }
                jQuery('#woocommerce_paypal_express_credit_messaging_category_text_layout_logo_type').trigger('change');
            }).change();
            jQuery('#woocommerce_paypal_express_credit_messaging_category_text_layout_logo_type').change(function () {
                credit_messaging_category_text_layout_logo_position = jQuery('#woocommerce_paypal_express_credit_messaging_category_text_layout_logo_position').closest('tr');
                if (jQuery('#woocommerce_paypal_express_credit_messaging_category_layout_type').val() === 'text' && (this.value === 'primary' || this.value === 'alternative')) {
                    if (is_credit_messaging_category_page_enable()) {
                        credit_messaging_category_text_layout_logo_position.show();
                    }
                } else {
                    credit_messaging_category_text_layout_logo_position.hide();
                }
            }).change();
            // Product
            jQuery('#woocommerce_paypal_express_credit_messaging_product_layout_type').change(function () {
                credit_messaging_product_text_layout_field_parent = jQuery('.credit_messaging_product_text_layout_field').closest('tr');
                credit_messaging_product_text_layout_field_p_tag = jQuery('.credit_messaging_product_text_layout_field').next("p");
                credit_messaging_product_text_layout_field = jQuery('.credit_messaging_product_text_layout_field');
                credit_messaging_product_flex_layout_field_parent = jQuery('.credit_messaging_product_flex_layout_field').closest('tr');
                credit_messaging_product_flex_layout_field_p_tag = jQuery('.credit_messaging_product_flex_layout_field').next("p");
                credit_messaging_product_flex_layout_field = jQuery('.credit_messaging_product_flex_layout_field');
                if (this.value === 'text') {
                    if (is_credit_messaging_product_page_enable()) {
                        credit_messaging_product_text_layout_field_parent.show();
                        credit_messaging_product_text_layout_field.show();
                        credit_messaging_product_text_layout_field_p_tag.show();
                        credit_messaging_product_flex_layout_field_parent.hide();
                        credit_messaging_product_flex_layout_field_p_tag.hide();
                        credit_messaging_product_flex_layout_field.hide();
                    }
                } else {
                    if (is_credit_messaging_product_page_enable()) {
                        credit_messaging_product_flex_layout_field_parent.show();
                        credit_messaging_product_flex_layout_field_p_tag.show();
                        credit_messaging_product_flex_layout_field.show();
                    }
                    credit_messaging_product_text_layout_field_parent.hide();
                    credit_messaging_product_text_layout_field.hide();
                    credit_messaging_product_text_layout_field_p_tag.hide();
                }
                jQuery('#woocommerce_paypal_express_credit_messaging_product_text_layout_logo_type').trigger('change');
            }).change();
            jQuery('#woocommerce_paypal_express_credit_messaging_product_text_layout_logo_type').change(function () {
                credit_messaging_product_text_layout_logo_position = jQuery('#woocommerce_paypal_express_credit_messaging_product_text_layout_logo_position').closest('tr');
                if (jQuery('#woocommerce_paypal_express_credit_messaging_product_layout_type').val() === 'text' && (this.value === 'primary' || this.value === 'alternative')) {
                    if (is_credit_messaging_product_page_enable()) {
                        credit_messaging_product_text_layout_logo_position.show();
                    }
                } else {
                    credit_messaging_product_text_layout_logo_position.hide();
                }
            }).change();
            // Cart
            jQuery('#woocommerce_paypal_express_credit_messaging_cart_layout_type').change(function () {
                credit_messaging_cart_text_layout_field_parent = jQuery('.credit_messaging_cart_text_layout_field').closest('tr');
                credit_messaging_cart_text_layout_field_p_tag = jQuery('.credit_messaging_cart_text_layout_field').next("p");
                credit_messaging_cart_text_layout_field = jQuery('.credit_messaging_cart_text_layout_field');
                credit_messaging_cart_flex_layout_field_parent = jQuery('.credit_messaging_cart_flex_layout_field').closest('tr');
                credit_messaging_cart_flex_layout_field_p_tag = jQuery('.credit_messaging_cart_flex_layout_field').next("p");
                credit_messaging_cart_flex_layout_field = jQuery('.credit_messaging_cart_flex_layout_field');
                if (this.value === 'text') {
                    if (is_credit_messaging_cart_page_enable()) {
                        credit_messaging_cart_text_layout_field_parent.show();
                        credit_messaging_cart_text_layout_field.show();
                        credit_messaging_cart_text_layout_field_p_tag.show();
                        credit_messaging_cart_flex_layout_field_parent.hide();
                        credit_messaging_cart_flex_layout_field_p_tag.hide();
                        credit_messaging_cart_flex_layout_field.hide();
                    }
                } else {
                    if (is_credit_messaging_cart_page_enable()) {
                        credit_messaging_cart_flex_layout_field_parent.show();
                        credit_messaging_cart_flex_layout_field_p_tag.show();
                        credit_messaging_cart_flex_layout_field.show();
                    }
                    credit_messaging_cart_text_layout_field_parent.hide();
                    credit_messaging_cart_text_layout_field.hide();
                    credit_messaging_cart_text_layout_field_p_tag.hide();
                }
                jQuery('#woocommerce_paypal_express_credit_messaging_cart_text_layout_logo_type').trigger('change');
            }).change();
            jQuery('#woocommerce_paypal_express_credit_messaging_cart_text_layout_logo_type').change(function () {
                credit_messaging_cart_text_layout_logo_position = jQuery('#woocommerce_paypal_express_credit_messaging_cart_text_layout_logo_position').closest('tr');
                if (jQuery('#woocommerce_paypal_express_credit_messaging_cart_layout_type').val() === 'text' && (this.value === 'primary' || this.value === 'alternative')) {
                    if (is_credit_messaging_cart_page_enable()) {
                        credit_messaging_cart_text_layout_logo_position.show();
                    }
                } else {
                    credit_messaging_cart_text_layout_logo_position.hide();
                }
            }).change();
            // Checkout
            jQuery('#woocommerce_paypal_express_credit_messaging_payment_layout_type').change(function () {
                credit_messaging_payment_text_layout_field_parent = jQuery('.credit_messaging_payment_text_layout_field').closest('tr');
                credit_messaging_payment_text_layout_field_p_tag = jQuery('.credit_messaging_payment_text_layout_field').next("p");
                credit_messaging_payment_text_layout_field = jQuery('.credit_messaging_payment_text_layout_field');
                credit_messaging_payment_flex_layout_field_parent = jQuery('.credit_messaging_payment_flex_layout_field').closest('tr');
                credit_messaging_payment_flex_layout_field_p_tag = jQuery('.credit_messaging_payment_flex_layout_field').next("p");
                credit_messaging_payment_flex_layout_field = jQuery('.credit_messaging_payment_flex_layout_field');
                if (this.value === 'text') {
                    if (is_credit_messaging_payment_page_enable()) {
                        credit_messaging_payment_text_layout_field_parent.show();
                        credit_messaging_payment_text_layout_field.show();
                        credit_messaging_payment_text_layout_field_p_tag.show();
                        credit_messaging_payment_flex_layout_field_parent.hide();
                        credit_messaging_payment_flex_layout_field_p_tag.hide();
                        credit_messaging_payment_flex_layout_field.hide();
                    }
                } else {
                    if (is_credit_messaging_payment_page_enable()) {
                        credit_messaging_payment_flex_layout_field_parent.show();
                        credit_messaging_payment_flex_layout_field_p_tag.show();
                        credit_messaging_payment_flex_layout_field.show();
                    }
                    credit_messaging_payment_text_layout_field_parent.hide();
                    credit_messaging_payment_text_layout_field.hide();
                    credit_messaging_payment_text_layout_field_p_tag.hide();
                }
                jQuery('#woocommerce_paypal_express_credit_messaging_payment_text_layout_logo_type').trigger('change');
            }).change();
            jQuery('#woocommerce_paypal_express_credit_messaging_payment_text_layout_logo_type').change(function () {
                credit_messaging_payment_text_layout_logo_position = jQuery('#woocommerce_paypal_express_credit_messaging_payment_text_layout_logo_position').closest('tr');
                if (jQuery('#woocommerce_paypal_express_credit_messaging_payment_layout_type').val() === 'text' && (this.value === 'primary' || this.value === 'alternative')) {
                    if (is_credit_messaging_payment_page_enable()) {
                        credit_messaging_payment_text_layout_logo_position.show();
                    }
                } else {
                    credit_messaging_payment_text_layout_logo_position.hide();
                }
            }).change();
            jQuery("#woocommerce_paypal_express_button_layout").change(function () {
                var angelleye_button_tagline = jQuery("#woocommerce_paypal_express_button_tagline").closest('tr');
                if (this.value === 'vertical') {
                    angelleye_button_tagline.hide();
                }
            }).change();
            jQuery('#woocommerce_paypal_express_payment_action').change(function () {
                if (this.value === 'Authorization') {
                    jQuery('#woocommerce_paypal_express_pending_authorization_order_status').closest('tr').show();
                } else {
                    jQuery('#woocommerce_paypal_express_pending_authorization_order_status').closest('tr').hide();
                }
            }).change();
            jQuery('#woocommerce_paypal_express_button_label').change(function () {
                var paypal_express_button_tagline = jQuery('#woocommerce_paypal_express_button_tagline').closest('tr').hide();
                if (jQuery('#woocommerce_paypal_express_button_label').val() === 'buynow') {
                    paypal_express_button_tagline.hide();
                }
            }).change();
            var display_disable_terms = "<?php echo $display_disable_terms; ?>";
        <?php if ($guest_checkout === 'no') { ?>
                jQuery("#woocommerce_paypal_express_skip_final_review").prop("checked", false);
                jQuery("#woocommerce_paypal_express_skip_final_review").attr("disabled", true);
        <?php } ?>
            jQuery('#woocommerce_paypal_express_skip_final_review').change(function () {
                disable_term = jQuery('#woocommerce_paypal_express_disable_term').closest('tr');
                if (jQuery(this).is(':checked')) {
                    if (display_disable_terms === 'yes') {
                        disable_term.show();
                    } else {
                        disable_term.hide();
                    }
                } else {
                    disable_term.hide();
                }
            }).change();
            jQuery('#woocommerce_paypal_express_disable_term').change(function () {
                term_notice = jQuery('.terms_notice');
                if (jQuery(this).is(':checked')) {
                    term_notice.hide();
                } else {
                    term_notice.show();
                }
            }).change();
            jQuery('#woocommerce_paypal_express_show_on_cart').change(function () {
                var show_on_minicart = jQuery('#woocommerce_paypal_express_show_on_minicart').closest('tr');
                if (jQuery(this).is(':checked')) {
                    show_on_minicart.show();
                } else {
                    show_on_minicart.hide();
                }
            }).change();
            jQuery('#woocommerce_paypal_express_testmode').change(function () {
                sandbox = jQuery('#woocommerce_paypal_express_sandbox_api_username, #woocommerce_paypal_express_sandbox_api_password, #woocommerce_paypal_express_sandbox_api_signature').closest('tr'),
                        production = jQuery('#woocommerce_paypal_express_api_username, #woocommerce_paypal_express_api_password, #woocommerce_paypal_express_api_signature').closest('tr');
                if (jQuery(this).is(':checked')) {
                    sandbox.show();
                    production.hide();
                } else {
                    sandbox.hide();
                    production.show();
                }
            }).change();
            jQuery('#woocommerce_paypal_express_enable_fraudnet_integration').change(function () {
                paypal_express_fraudnet_swi = jQuery('#woocommerce_paypal_express_fraudnet_swi').closest('tr');
                if (jQuery(this).is(':checked')) {
                    paypal_express_fraudnet_swi.show();
                } else {
                    paypal_express_fraudnet_swi.hide();
                }
            }).change();
            jQuery('#woocommerce_paypal_express_send_items').change(function () {
                var subtotal_mismatch_behavior = jQuery('#woocommerce_paypal_express_subtotal_mismatch_behavior').closest('tr');
                if (jQuery(this).is(':checked')) {
                    subtotal_mismatch_behavior.show();
                } else {
                    subtotal_mismatch_behavior.hide();
                }
            }).change();
            jQuery('#woocommerce_paypal_express_show_on_checkout').change(function () {
                var paypal_express_show_on_checkout = jQuery(this).find('option:selected').val();
                if (paypal_express_show_on_checkout === 'no') {
                    jQuery('#woocommerce_paypal_express_checkout_page_button_settings').hide();
                    jQuery('#woocommerce_paypal_express_checkout_page_button_settings').next('p').hide();
                    jQuery('#woocommerce_paypal_express_checkout_page_button_settings').next('p').next('table').hide();
                } else if (paypal_express_show_on_checkout === 'top') {
                    jQuery('#woocommerce_paypal_express_checkout_page_disable_smart_button').closest('tr').hide();
                } else if (paypal_express_show_on_checkout === 'regular') {
                    jQuery('#woocommerce_paypal_express_checkout_page_button_settings').show();
                    jQuery('#woocommerce_paypal_express_checkout_page_button_settings').next('p').show();
                    jQuery('#woocommerce_paypal_express_checkout_page_button_settings').next('p').next('table').show();
                    jQuery('#woocommerce_paypal_express_checkout_page_disable_smart_button').closest('tr').show();
                } else if (paypal_express_show_on_checkout === 'both') {
                    jQuery('#woocommerce_paypal_express_checkout_page_button_settings').show();
                    jQuery('#woocommerce_paypal_express_checkout_page_button_settings').next('p').show();
                    jQuery('#woocommerce_paypal_express_checkout_page_button_settings').next('p').next('table').show();
                    jQuery('#woocommerce_paypal_express_checkout_page_disable_smart_button').closest('tr').show();
                } else {
                    jQuery('#woocommerce_paypal_express_checkout_page_button_settings').show();
                    jQuery('#woocommerce_paypal_express_checkout_page_button_settings').next('p').show();
                    jQuery('#woocommerce_paypal_express_checkout_page_button_settings').next('p').next('table').show();
                    jQuery('#woocommerce_paypal_express_checkout_page_disable_smart_button').closest('tr').show();
                }
            }).change();
        <?php
        if (!empty($this->is_multi_account_active == 'yes')) {
            ?> jQuery('#woocommerce_paypal_express_enable_tokenized_payments').prop("disabled", true);
                jQuery('#woocommerce_paypal_express_enable_tokenized_payments').prop('checked', false);
        <?php }
        ?>
        </script>
        <?php
    }

    /**
     * get_icon function.
     *
     * @access public
     * @return string
     */
    public function get_icon() {
        if (in_array('credit', $this->checkout_page_disallowed_funding_methods)) {
            $this->show_paypal_credit = 'no';
        }
        $image_path = plugins_url('/assets/images/paypal.png', plugin_basename(dirname(__FILE__)));
        if ($this->paypal_account_optional == 'no' && $this->show_paypal_credit == 'no') {
            $image_path = plugins_url('/assets/images/paypal.png', plugin_basename(dirname(__FILE__)));
        }
        if ($this->paypal_account_optional == 'yes' && $this->show_paypal_credit == 'no') {
            $image_path = plugins_url('/assets/images/paypal-credit-card-logos.png', plugin_basename(dirname(__FILE__)));
        }
        if ($this->paypal_account_optional == 'yes' && $this->show_paypal_credit == 'yes') {
            $image_path = plugins_url('/assets/images/paypal-paypal-credit-card-logos.png', plugin_basename(dirname(__FILE__)));
        }
        if ($this->checkout_with_pp_button_type == 'customimage') {
            $image_path = $this->pp_button_type_my_custom;
        }
        if (is_ssl() || 'yes' === get_option('woocommerce_force_ssl_checkout')) {
            $image_path = str_replace('http:', 'https:', $image_path);
        }
        if ($this->paypal_account_optional == 'no' && $this->show_paypal_credit == 'yes' && $this->checkout_with_pp_button_type == 'paypalimage') {
            $image_path = plugins_url('/assets/images/paypal.png', plugin_basename(dirname(__FILE__)));
            if (is_ssl() || 'yes' === get_option('woocommerce_force_ssl_checkout')) {
                $image_path = str_replace('http:', 'https:', $image_path);
            }
            $image_path_two = plugins_url('/assets/images/PP_credit_logo.png', plugin_basename(dirname(__FILE__)));
            if (is_ssl() || 'yes' === get_option('woocommerce_force_ssl_checkout')) {
                $image_path_two = str_replace('http:', 'https:', $image_path_two);
            }
            $icon = "<img src=\"$image_path\" alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
            $icon_two = "<img src=\"$image_path_two\" alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
            return apply_filters('angelleye_ec_checkout_icon', $icon . $icon_two, $this->id);
        } else {
            $icon = "<img src=\"$image_path\" alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
            return apply_filters('angelleye_ec_checkout_icon', $icon, $this->id);
        }
    }

    public function init_form_fields() {
        
        $this->send_items_value = !empty($this->settings['send_items']) && 'yes' === $this->settings['send_items'] ? 'yes' : 'no';
        $this->send_items = 'yes' === $this->send_items_value;
        $rest_url = get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=paypal_express&pms_reset=true';
        $require_ssl = '';
        if (is_ssl() || 'yes' === get_option('woocommerce_force_ssl_checkout')) {
            $require_ssl = __('This image requires an SSL host.  Please upload your image to <a target="_blank" href="http://www.sslpic.com">www.sslpic.com</a> and enter the image URL here.', 'paypal-for-woocommerce');
        }
        $skip_final_review_option_not_allowed_guest_checkout = '';
        $skip_final_review_option_not_allowed_terms = '';
        $skip_final_review_option_not_allowed_tokenized_payments = '';
        $woocommerce_enable_guest_checkout = get_option('woocommerce_enable_guest_checkout');
        if ('yes' === get_option('woocommerce_registration_generate_username') && 'yes' === get_option('woocommerce_registration_generate_password')) {
            $woocommerce_enable_guest_checkout = 'yes';
        }
        if (isset($woocommerce_enable_guest_checkout) && ( $woocommerce_enable_guest_checkout === "no" )) {
            $skip_final_review_option_not_allowed_guest_checkout = ' (The WooCommerce guest checkout option is disabled.  Therefore, the review page is required for login / account creation, and this option will be overridden.)';
        }
        if (wc_get_page_id('terms') > 0 && apply_filters('woocommerce_checkout_show_terms', true)) {
            $skip_final_review_option_not_allowed_terms = ' (You currently have a Terms &amp; Conditions page set, which requires the review page, and will override this option.)';
        }
        $this->enable_tokenized_payments = $was_enable_tokenized_payments = $this->get_option('enable_tokenized_payments', 'no');
        if (class_exists('Paypal_For_Woocommerce_Multi_Account_Management')) {
            $this->enable_tokenized_payments = 'no';
            $this->is_multi_account_active = 'yes';
        } else {
            $this->is_multi_account_active = 'no';
        }
        if ($was_enable_tokenized_payments == 'yes' && $this->is_multi_account_active == 'yes') {
            $enable_tokenized_payments_text = __('Payment tokenization is not available when using the PayPal Multi-Account add-on, and it has been disabled.', 'paypal-for-woocommerce');
        } elseif ($was_enable_tokenized_payments == 'no' && $this->is_multi_account_active == 'yes') {
            $enable_tokenized_payments_text = __('Token payments are not available when using the PayPal Multi-Account add-on.', 'paypal-for-woocommerce');
        } else {
            $enable_tokenized_payments_text = __('Allow buyers to securely save payment details to their account for quick checkout / auto-ship orders in the future. (Currently considered BETA for Express Checkout.)', 'paypal-for-woocommerce');
        }
        if ($this->enable_tokenized_payments == 'yes') {
            $skip_final_review_option_not_allowed_tokenized_payments = ' (Payments tokens are enabled, which require the review page, and that will override this option.)';
        }
        $button_height = array(
            '' => __('Default Height', 'paypal-for-woocommerce'),
            25 => __('25 px', 'paypal-for-woocommerce'),
            26 => __('26 px', 'paypal-for-woocommerce'),
            27 => __('27 px', 'paypal-for-woocommerce'),
            28 => __('28 px', 'paypal-for-woocommerce'),
            29 => __('29 px', 'paypal-for-woocommerce'),
            30 => __('30 px', 'paypal-for-woocommerce'),
            31 => __('31 px', 'paypal-for-woocommerce'),
            32 => __('32 px', 'paypal-for-woocommerce'),
            33 => __('33 px', 'paypal-for-woocommerce'),
            34 => __('34 px', 'paypal-for-woocommerce'),
            35 => __('35 px', 'paypal-for-woocommerce'),
            36 => __('36 px', 'paypal-for-woocommerce'),
            37 => __('37 px', 'paypal-for-woocommerce'),
            38 => __('38 px', 'paypal-for-woocommerce'),
            39 => __('39 px', 'paypal-for-woocommerce'),
            40 => __('40 px', 'paypal-for-woocommerce'),
            41 => __('41 px', 'paypal-for-woocommerce'),
            42 => __('42 px', 'paypal-for-woocommerce'),
            43 => __('43 px', 'paypal-for-woocommerce'),
            44 => __('44 px', 'paypal-for-woocommerce'),
            45 => __('45 px', 'paypal-for-woocommerce'),
            46 => __('46 px', 'paypal-for-woocommerce'),
            47 => __('47 px', 'paypal-for-woocommerce'),
            48 => __('48 px', 'paypal-for-woocommerce'),
            49 => __('49 px', 'paypal-for-woocommerce'),
            50 => __('50 px', 'paypal-for-woocommerce'),
            51 => __('51 px', 'paypal-for-woocommerce'),
            52 => __('52 px', 'paypal-for-woocommerce'),
            53 => __('53 px', 'paypal-for-woocommerce'),
            54 => __('51 px', 'paypal-for-woocommerce'),
            55 => __('55 px', 'paypal-for-woocommerce')
        );
        $args = array(
            'sort_order' => 'ASC',
            'sort_column' => 'post_title',
            'hierarchical' => 1,
            'exclude' => '',
            'include' => '',
            'meta_key' => '',
            'meta_value' => '',
            'authors' => '',
            'child_of' => 0,
            'parent' => -1,
            'exclude_tree' => '',
            'number' => '',
            'offset' => 0,
            'post_type' => 'page',
            'post_status' => 'publish'
        );
        $pages = get_pages($args);
        $cancel_page = array();
        foreach ($pages as $p) {
            $cancel_page[$p->ID] = $p->post_title;
        }
        $this->testmode = 'yes' === $this->get_option('testmode', 'yes');
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                'label' => __('Enable PayPal Express', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'paypal-for-woocommerce'),
                'default' => __('PayPal Express', 'paypal-for-woocommerce'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'paypal-for-woocommerce'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'paypal-for-woocommerce'),
                'default' => __("Pay via PayPal; you can pay with your credit card if you don't have a PayPal account", 'paypal-for-woocommerce'),
                'desc_tip' => true,
            ),
            'api_details' => array(
                'title' => __('API Credentials', 'paypal-for-woocommerce'),
                'type' => 'title',
                'description' => '',
            ),
            'testmode' => array(
                'title' => __('PayPal Sandbox', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable PayPal Sandbox', 'paypal-for-woocommerce'),
                'default' => 'yes',
                'description' => __('The sandbox is PayPal\'s test environment and is only for use with sandbox accounts created within your <a href="http://developer.paypal.com" target="_blank">PayPal developer account</a>.', 'paypal-for-woocommerce')
            ),
            'sandbox_api_username' => array(
                'title' => __('Sandbox API User Name', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Create sandbox accounts and obtain API credentials from within your <a href="http://developer.paypal.com">PayPal developer account</a>.', 'paypal-for-woocommerce'),
                'default' => '',
                'custom_attributes' => array('autocomplete' => 'new-password'),
            ),
            'sandbox_api_password' => array(
                'title' => __('Sandbox API Password', 'paypal-for-woocommerce'),
                'type' => 'password',
                'default' => '',
                'custom_attributes' => array('autocomplete' => 'new-password'),
            ),
            'sandbox_api_signature' => array(
                'title' => __('Sandbox API Signature', 'paypal-for-woocommerce'),
                'type' => 'password',
                'default' => '',
                'custom_attributes' => array('autocomplete' => 'new-password'),
            ),
            'api_username' => array(
                'title' => __('Live API User Name', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Get your live account API credentials from your PayPal account profile <br />or by using <a target="_blank" href="https://www.paypal.com/us/cgi-bin/webscr?cmd=_login-api-run">this tool</a>.', 'paypal-for-woocommerce'),
                'default' => '',
                'custom_attributes' => array('autocomplete' => 'new-password'),
            ),
            'api_password' => array(
                'title' => __('Live API Password', 'paypal-for-woocommerce'),
                'type' => 'password',
                'default' => '',
                'custom_attributes' => array('autocomplete' => 'new-password'),
            ),
            'api_signature' => array(
                'title' => __('Live API Signature', 'paypal-for-woocommerce'),
                'type' => 'password',
                'default' => '',
                'custom_attributes' => array('autocomplete' => 'new-password'),
            ),
            'shopping_cart_checkout_page_display' => array(
                'title' => __('Shopping Cart, Checkout and Product Page Display', 'paypal-for-woocommerce'),
                'type' => 'title',
                'description' => '',
            ),
            'review_title_page' => array(
                'title' => __('Order Review Page Title', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title of order review page which the user sees during checkout.', 'paypal-for-woocommerce'),
                'desc_tip' => true,
                'default' => __('Review Order', 'paypal-for-woocommerce')
            ),
            'review_button_label' => array(
                'title' => __('Order Review Page Button Label', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the label of the button on the order review page which the buyer sees during checkout.', 'paypal-for-woocommerce'),
                'desc_tip' => true,
                'default' => __('Place order', 'paypal-for-woocommerce')
            ),
            'checkout_button_label' => array(
                'title' => __('Checkout Page Button Label', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the label of the button on the checkout page which the buyer sees during checkout.', 'paypal-for-woocommerce'),
                'desc_tip' => true,
                'default' => __('Proceed to PayPal', 'paypal-for-woocommerce')
            ),
            'order_review_page_custom_message' => array(
                'title' => __('Order Review Message', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This message will be displayed below the page header on the Order Review page.', 'paypal-for-woocommerce'),
                'desc_tip' => true,
                'default' => ''
            ),
            'checkout_with_pp_button_type' => array(
                'title' => __('Checkout Button Type', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'checkout_with_pp_button_type wc-enhanced-select',
                'label' => __('Use Checkout with PayPal image button', 'paypal-for-woocommerce'),
                'options' => array(
                    'paypalimage' => __('PayPal Image', 'paypal-for-woocommerce'),
                    'textbutton' => __('Text Button', 'paypal-for-woocommerce'),
                    'customimage' => __('Custom Image', 'paypal-for-woocommerce')
                ),
                'default' => 'paypalimage',
            ),
            'pp_button_type_my_custom' => array(
                'title' => __('Select Image', 'paypal-for-woocommerce'),
                'type' => 'text',
                'label' => __('Use Checkout with PayPal image button', 'paypal-for-woocommerce'),
                'class' => 'pp_button_type_my_custom, button_upload',
            ),
            'pp_button_type_text_button' => array(
                'title' => __('Custom Text', 'paypal-for-woocommerce'),
                'type' => 'text',
                'class' => 'pp_button_type_text_button',
                'default' => __('Proceed to Checkout', 'paypal-for-woocommerce'),
            ),
            'show_on_cart' => array(
                'title' => __('Cart Page', 'paypal-for-woocommerce'),
                'label' => __('Show Express Checkout button on shopping cart page.', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'default' => 'yes'
            ),
            'show_on_minicart' => array(
                'title' => __('Minicart', 'paypal-for-woocommerce'),
                'label' => __('Show Express Checkout button in the WooCommerce Minicart.', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'default' => 'no',
                'description' => __('Enabling this option will cause the PayPal button JS to load on every page, which could negatively affect page load times on your site.'),
            ),
            'button_position' => array(
                'title' => __('Cart Button Position', 'paypal-for-woocommerce'),
                'label' => __('Where to display PayPal Express Checkout button(s).', 'paypal-for-woocommerce'),
                'description' => __('Set where to display the PayPal Express Checkout button(s).'),
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'options' => array(
                    'top' => 'At the top, above the shopping cart details.',
                    'bottom' => 'At the bottom, below the shopping cart details.',
                    'both' => 'Both at the top and bottom, above and below the shopping cart details.'
                ),
                'default' => 'bottom',
                'desc_tip' => true,
            ),
            'show_on_checkout' => array(
                'title' => __('Checkout Page Display', 'paypal-for-woocommerce'),
                'type' => 'select',
                'options' => array(
                    'no' => __("Do not display on checkout page.", 'paypal-for-woocommerce'),
                    'top' => __('Display at the top of the checkout page.', 'paypal-for-woocommerce'),
                    'regular' => __('Display in general list of enabled gateways on checkout page.', 'paypal-for-woocommerce'),
                    'both' => __('Display both at the top and in the general list of gateways on the checkout page.')),
                'default' => 'both',
                'class' => 'wc-enhanced-select',
                'description' => __('Displaying the checkout button at the top of the checkout page will allow users to skip filling out the forms and can potentially increase conversion rates.'),
                'desc_tip' => true,
            ),
            'angelleye_skip_text' => array(
                'title' => __('Express Checkout Message', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This message will be displayed next to the PayPal Express Checkout button at the top of the checkout page.'),
                'default' => __('Skip the forms and pay faster with PayPal!', 'paypal-for-woocommerce'),
                'desc_tip' => true,
            ),
            'show_on_product_page' => array(
                'title' => __('Product Page', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Show the Express Checkout button on product detail pages.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'description' => sprintf(__('Allows customers to checkout using PayPal directly from a product page. Do not forget to enable Express Checkout on product details page.  You can use the <a href="%s" target="_blank">Bulk Update Tool</a> to Enable Express Checkout on multiple products at once.', 'paypal-for-woocommerce'), admin_url('options-general.php?page=paypal-for-woocommerce&tab=tools')),
                'desc_tip' => false,
                'class' => 'show-on-product-page'
            ),
            'enable_newly_products' => array(
                'title' => '',
                'type' => 'checkbox',
                'label' => __('Enable by default on all new products.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'description' => '',
                'desc_tip' => false,
                'class' => 'enable-newly-products-bydefault'
            ),
            'show_paypal_credit' => array(
                'title' => __('Enable PayPal Credit', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Show the PayPal Credit button next to the Express Checkout button.', 'paypal-for-woocommerce'),
                'default' => 'yes',
                'description' => ($this->is_paypal_credit_enable == false) ? __('Currently disabled because PayPal Credit is only available for U.S.', 'paypal-for-woocommerce') : "",
                'desc_tip' => ($this->is_paypal_credit_enable) ? true : false,
            ),
            'branding' => array(
                'title' => __('Branding', 'paypal-for-woocommerce'),
                'type' => 'title',
                'description' => '',
            ),
            'paypal_account_optional' => array(
                'title' => __('PayPal Account Optional', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Allow customers to checkout without a PayPal account using their credit card.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'description' => __('PayPal Account Optional must be turned on in your PayPal account profile under Website Preferences.', 'paypal-for-woocommerce'),
                'desc_tip' => true,
            ),
            'landing_page' => array(
                'title' => __('Landing Page', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'description' => __('Type of PayPal page to display as default. PayPal Account Optional must be checked for this option to be used.', 'paypal-for-woocommerce'),
                'options' => array('login' => __('Login', 'paypal-for-woocommerce'),
                    'billing' => __('Billing', 'paypal-for-woocommerce')),
                'default' => 'login',
                'desc_tip' => true,
            ),
            'error_display_type' => array(
                'title' => __('Error Display Type', 'paypal-for-woocommerce'),
                'type' => 'select',
                'label' => __('Display detailed or generic errors', 'paypal-for-woocommerce'),
                'class' => 'error_display_type_option wc-enhanced-select',
                'options' => array(
                    'detailed' => __('Detailed', 'paypal-for-woocommerce'),
                    'generic' => __('Generic', 'paypal-for-woocommerce')
                ),
                'description' => __('Detailed displays actual errors returned from PayPal.  Generic displays general errors that do not reveal details and helps to prevent fraudulant activity on your site.', 'paypal-for-woocommerce'),
                'default' => 'detailed',
                'desc_tip' => true,
            ),
            'use_wp_locale_code' => array(
                'title' => __('Use WordPress Locale Code', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Pass the WordPress Locale Code setting to PayPal in order to display localized PayPal pages to buyers.', 'paypal-for-woocommerce'),
                'default' => 'yes'
            ),
            'page_style' => array(
                'title' => __('Page Style', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('If you wish to use a <a target="_blank" href="https://www.paypal.com/customize">custom page style configured in your PayPal account</a>, enter the name of the page style here.', 'paypal-for-woocommerce'),
                'default' => ''
            ),
            'brand_name' => array(
                'title' => __('Brand Name', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls what users see as the brand / company name on PayPal review pages.', 'paypal-for-woocommerce'),
                'default' => __(get_bloginfo('name'), 'paypal-for-woocommerce'),
                'desc_tip' => true,
            ),
            'checkout_logo' => array(
                'title' => __('PayPal Checkout Logo (190x60px)', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls what users see as the logo on PayPal review pages. ', 'paypal-for-woocommerce') . $require_ssl,
                'default' => '',
                'desc_tip' => true,
            ),
            'checkout_logo_hdrimg' => array(
                'title' => __('PayPal Checkout Banner (750x90px)', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls what users see as the header banner on PayPal review pages. ', 'paypal-for-woocommerce') . $require_ssl,
                'default' => '',
                'desc_tip' => true,
            ),
            'customer_service_number' => array(
                'title' => __('Customer Service Number', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls what users see for your customer service phone number on PayPal review pages.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
            ),
            'softdescriptor' => array(
                'title' => __('Credit Card Statement Name', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('The value entered here will be displayed on the buyer\'s credit card statement.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
            ),
            'tokenization_subscriptions' => array(
                'title' => __('Tokenization / Subscriptions', 'paypal-for-woocommerce'),
                'type' => 'title',
                'description' => '',
            ),
            'enable_tokenized_payments' => array(
                'title' => __('Enable Tokenized Payments', 'paypal-for-woocommerce'),
                'label' => __('Enable Tokenized Payments', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => $enable_tokenized_payments_text,
                'default' => 'no',
                'class' => 'enable_tokenized_payments'
            ),
            'fraud_management'           => array(
                'title'       => __( 'Fraud Management', 'paypal-for-woocommerce' ),
                'type'        => 'title',
                'description' => '',
            ),
            'fraud_management_filters' => array(
                'title' => __('Fraud Management Filters ', 'paypal-for-woocommerce'),
                'label' => '',
                'description' => __('Choose how you would like to handle orders when Fraud Management Filters are flagged.', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class'    => 'wc-enhanced-select',
                'options' => array(
                    'ignore_warnings_and_proceed_as_usual' => __('Ignore warnings and proceed as usual.', 'paypal-for-woocommerce'),
                    'place_order_on_hold_for_further_review' => __('Place order On Hold for further review.', 'paypal-for-woocommerce'),
                ),
                'default' => 'place_order_on_hold_for_further_review',
                'desc_tip' => true,
            ),
            'enable_fraudnet_integration' => array(
                'title' => __('Enable FraudNet Integration', 'paypal-for-woocommerce'),
                'label' => __('FraudNet Protection Integration (only required for Reference Transactions.)', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('FraudNet is a JavaScript library developed by PayPal and embedded into a merchant’s web page to collect browser-based data to help reduce fraud. Upon checkout, these data elements are sent directly to PayPal Risk Services for fraud and risk assessment.','paypal-for-woocommerce'),
                'default' => 'no',
                'class' => '',
                'desc_tip' => true,
            ),
            'fraudnet_swi' => array(
                'title' => __('Source Website Identifier', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This field is now required to be filled in for all new PayPal Express Checkout merchants. Existing users who already have Reference Transactions enabled are not required to use Fraudnet protection and an SWI (Source Website Identifier), although to take advantage of Fraudnet protection, you will be required to add one in. PayPal support will provide you with your personal source Website Identifier.', 'paypal-for-woocommerce'),
                'default' => '',
                'css' => 'min-width: 440px;',
                'placeholder' => __('Your Personal source Website Identifier (provided by PayPal support.)', ''),
            ),
            'seller_protection' => array(
                'title' => __('Seller Protection', 'paypal-for-woocommerce'),
                'type' => 'title',
                'description' => '',
            ),
            'order_cancellations' => array(
                'title' => __('Auto Cancel / Refund Orders ', 'paypal-for-woocommerce'),
                'label' => '',
                'description' => __('Allows you to cancel and refund orders that do not meet PayPal\'s Seller Protection criteria.', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'order_cancellations wc-enhanced-select',
                'options' => array(
                    'no_seller_protection' => __('Do *not* have PayPal Seller Protection', 'paypal-for-woocommerce'),
                    'no_unauthorized_payment_protection' => __('Do *not* have PayPal Unauthorized Payment Protection', 'paypal-for-woocommerce'),
                    'disabled' => __('Do not cancel any orders', 'paypal-for-woocommerce'),
                ),
                'default' => 'disabled',
                'desc_tip' => true,
            ),
            'email_notify_order_cancellations' => array(
                'title' => __('Order Cancelled / Refunded Email Notifications', 'paypal-for-woocommerce'),
                'label' => __('Enable buyer email notifications for Order cancelled/refunded', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('This will send buyer email notifications for Order canceled/refunded when Auto Cancel / Refund Orders option is selected.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'class' => 'email_notify_order_cancellations',
                'desc_tip' => true,
            ),
            'advanced' => array(
                'title' => __('Advanced Options', 'paypal-for-woocommerce'),
                'type' => 'title',
                'description' => '',
            ),
            'error_email_notify' => array(
                'title' => __('Error Email Notifications', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable admin email notifications for errors.', 'paypal-for-woocommerce'),
                'default' => 'yes',
                'description' => __('This will send a detailed error email to the WordPress site administrator if a PayPal API error occurs.', 'paypal-for-woocommerce'),
                'desc_tip' => true
            ),
            'invoice_id_prefix' => array(
                'title' => __('Invoice ID Prefix', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Add a prefix to the invoice ID sent to PayPal. This can resolve duplicate invoice problems when working with multiple websites on the same PayPal account.', 'paypal-for-woocommerce'),
                'desc_tip' => true,
                'default' => 'WC-EC'
            ),
            'skip_final_review' => array(
                'title' => __('Skip Final Review', 'paypal-for-woocommerce'),
                'label' => __('Enables the option to skip the final review page.', 'paypal-for-woocommerce'),
                'description' => __('By default, users will be returned from PayPal and presented with a final review page which includes shipping and tax in the order details.  Enable this option to eliminate this page in the checkout process.') . '<br /><b class="final_review_notice"><span class="guest_checkout_notice">' . $skip_final_review_option_not_allowed_guest_checkout . '</span></b>' . '<b class="final_review_notice"><span class="terms_notice">' . $skip_final_review_option_not_allowed_terms . '</span></b>' . '<b class="final_review_notice"><span class="tokenized_payments_notice">' . $skip_final_review_option_not_allowed_tokenized_payments . '</span></b>',
                'type' => 'checkbox',
                'default' => 'no'
            ),
            'disable_term' => array(
                'title' => __('Disable Terms and Conditions', 'paypal-for-woocommerce'),
                'label' => __('Disable Terms and Conditions for Express Checkout orders.', 'paypal-for-woocommerce'),
                'description' => __('By default, if a Terms and Conditions page is set in WooCommerce, this would require the review page and would override the Skip Final Review option.  Check this option to disable Terms and Conditions for Express Checkout orders only so that you can use the Skip Final Review option.'),
                'type' => 'checkbox',
                'default' => 'no',
                'class' => 'disable_term',
            ),
            'payment_action' => array(
                'title' => __('Payment Action', 'paypal-for-woocommerce'),
                'label' => __('Whether to process as a Sale or Authorization.', 'paypal-for-woocommerce'),
                'description' => __('Sale will capture the funds immediately when the order is placed.  Authorization will authorize the payment but will not capture the funds.'),
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'options' => array(
                    'Sale' => 'Sale',
                    'Authorization' => 'Authorization',
                    'Order' => 'Order'
                ),
                'default' => 'Sale',
                'desc_tip' => true,
            ),
            'pending_authorization_order_status' => array(
                'title' => __('Pending Authorization Order Status', 'paypal-for-woocommerce'),
                'label' => __('Pending Authorization Order Status.', 'paypal-for-woocommerce'),
                'description' => __('Set the order status you would like to use when an order has been authorized but has not yet been captured.'),
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'options' => array(
                    'On Hold' => 'On Hold',
                    'Processing' => 'Processing'
                ),
                'default' => 'On Hold',
                'desc_tip' => true,
            ),
            'billing_address' => array(
                'title' => __('Billing Address', 'paypal-for-woocommerce'),
                'label' => __('Set billing address in WooCommerce using the address returned by PayPal.', 'paypal-for-woocommerce'),
                'description' => __('PayPal only returns a shipping address back to the website.  Enable this option if you would like to use this address for both billing and shipping in WooCommerce.'),
                'type' => 'checkbox',
                'default' => 'no',
                'desc_tip' => true,
            ),
            'cancel_page' => array(
                'title' => __('Cancel Page', 'paypal-for-woocommerce'),
                'description' => __('Sets the page users will be returned to if they click the Cancel link on the PayPal checkout pages. As such, this option will be ignored when CartFlows checkout is used'),
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'options' => $cancel_page,
                'desc_tip' => true,
            ),
            'send_items' => array(
                'title' => __('Send Item Details', 'paypal-for-woocommerce'),
                'label' => __('Send line item details to PayPal', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Include all line item details in the payment request to PayPal so that they can be seen from the PayPal transaction details page.', 'paypal-for-woocommerce'),
                'default' => 'yes'
            ),
            'subtotal_mismatch_behavior' => array(
                'title' => __('Subtotal Mismatch Behavior', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'description' => __('Internally, WC calculates line item prices and taxes out to four decimal places; however, PayPal can only handle amounts out to two decimal places (or, depending on the currency, no decimal places at all). Occasionally, this can cause discrepancies between the way WooCommerce calculates prices versus the way PayPal calculates them. If a mismatch occurs, this option controls how the order is dealt with so payment can still be taken.', 'paypal-for-woocommerce'),
                'default' => ($this->send_items) ? 'add' : 'drop',
                'desc_tip' => true,
                'options' => array(
                    'add' => __('Add another line item', 'paypal-for-woocommerce'),
                    'drop' => __('Do not send line items to PayPal', 'paypal-for-woocommerce'),
                ),
            ),
            'enable_notifyurl' => array(
                'title' => __('Enable PayPal IPN', 'paypal-for-woocommerce'),
                'label' => __('Configure an IPN URL to be included with Express Checkout payments.', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('This will override any URL configured in your PayPal account profile.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'class' => 'angelleye_enable_notifyurl',
                'desc_tip' => true,
            ),
            'notifyurl' => array(
                'title' => __('PayPal IPN URL', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Your URL for receiving Instant Payment Notification (IPN) for transactions.', 'paypal-for-woocommerce'),
                'class' => 'angelleye_notifyurl',
                'desc_tip' => true,
            ),
            'prevent_to_add_additional_item' => array(
                'title' => __('Prevent Adding Extra Item', 'paypal-for-woocommerce'),
                'label' => __('Prevent adding an addition unit to the shopping cart when the Express Checkout button is pushed from the product page.', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('By default, clicking the PayPal Express Checkout button from the product page adds 1 unit of that item to the cart before sending the user to PayPal.  This option will disable that, and send the user without adding an additional unit.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'class' => '',
                'desc_tip' => true,
            ),
            'debug' => array(
                'title' => __('Debug', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => sprintf(__('Enable logging<code>%s</code>', 'paypal-for-woocommerce'), version_compare(WC_VERSION, '3.0', '<') ? wc_get_log_file_path('paypal_express') : WC_Log_Handler_File::get_log_file_path('paypal_express')),
                'default' => 'no'
            ),
            'is_encrypt' => array(
                'title' => __('', 'paypal-for-woocommerce'),
                'label' => __('', 'paypal-for-woocommerce'),
                'type' => 'hidden',
                'default' => 'yes',
                'class' => ''
            ),
            'smart_buttons' => array(
                'title' => __('Smart Payment Buttons', 'paypal-for-woocommerce'),
                'type' => 'title',
                'description' => '',
            ),
            'angelleye_smart_button_preview_title' => array(
                'title' => __('', 'paypal-for-woocommerce'),
                'type' => 'title',
                'class' => '',
                'description' => '<div class="display_smart_button_previews"></div>',
            ),
            'enable_in_context_checkout_flow' => array(
                'title' => __('Enable Smart Buttons', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('The enhanced PayPal Express Checkout with In-Context gives your customers a simplified checkout experience that keeps them at your website throughout the payment authorization process.', 'paypal-for-woocommerce'),
                'default' => 'yes'
            ),
            'disallowed_funding_methods' => array(
                'title' => __('Hide Funding Method(s)', 'paypal-for-woocommerce'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select in_context_checkout_part admin_smart_button_preview',
                'description' => __('Funding methods selected here will be hidden from buyers during checkout.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'options' => $this->disallowed_funding_methods_array,
            ),
            'button_layout' => array(
                'title' => __('Button Layout', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select in_context_checkout_part admin_smart_button_preview',
                'description' => __('Select Vertical for stacked buttons, and Horizontal for side-by-side buttons.', 'paypal-for-woocommerce'),
                'default' => 'horizontal',
                'desc_tip' => true,
                'options' => array(
                    'horizontal' => __('Horizontal', 'paypal-for-woocommerce'),
                    'vertical' => __('Vertical', 'paypal-for-woocommerce')
                ),
            ),
            'button_size' => array(
                'title' => __('Button Size', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select in_context_checkout_part admin_smart_button_preview',
                'description' => __('Set the size of the buttons you would like displayed.  Responsive will fit to the current element on the page.', 'paypal-for-woocommerce'),
                'default' => 'small',
                'desc_tip' => true,
                'options' => array(
                    'small' => __('Small', 'paypal-for-woocommerce'),
                    'medium' => __('Medium', 'paypal-for-woocommerce'),
                    'large' => __('Large', 'paypal-for-woocommerce'),
                    'responsive' => __('Responsive', 'paypal-for-woocommerce'),
                ),
            ),
            'button_height' => array(
                'title' => __('Button Height', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'in_context_checkout_part admin_smart_button_preview',
                'description' => __('Set the height of the buttons you would like displayed.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'options' => $button_height,
            ),
            'button_label' => array(
                'title' => __('Button Label', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select in_context_checkout_part admin_smart_button_preview',
                'description' => __('Set the label type you would like to use for the PayPal button.', 'paypal-for-woocommerce'),
                'default' => 'checkout',
                'desc_tip' => true,
                'options' => $this->button_label_array,
            ),
            'button_color' => array(
                'title' => __('Button Color', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select in_context_checkout_part admin_smart_button_preview',
                'description' => __('Set the color you would like to use for the PayPal button.', 'paypal-for-woocommerce'),
                'default' => 'gold',
                'desc_tip' => true,
                'options' => array(
                    'gold' => __('Gold', 'paypal-for-woocommerce'),
                    'blue' => __('Blue', 'paypal-for-woocommerce'),
                    'silver' => __('Silver', 'paypal-for-woocommerce'),
                    'white' => __('White', 'paypal-for-woocommerce'),
                    'black' => __('Black', 'paypal-for-woocommerce')
                ),
            ),
            'button_shape' => array(
                'title' => __('Button Shape', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select in_context_checkout_part admin_smart_button_preview',
                'description' => __('Set the shape you would like to use for the buttons.', 'paypal-for-woocommerce'),
                'default' => 'pill',
                'desc_tip' => true,
                'options' => array(
                    'pill' => __('Pill', 'paypal-for-woocommerce'),
                    'rect' => __('Rectangle', 'paypal-for-woocommerce')
                ),
            ),
            'button_tagline' => array(
                'title' => __('Button Tagline ', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select in_context_checkout_part_tagline in_context_checkout_part admin_smart_button_preview',
                'description' => __('Enable this to display a tagline below the PayPal buttons..', 'paypal-for-woocommerce'),
                'default' => 'false',
                'desc_tip' => true,
                'options' => array(
                    'false' => __('Disable', 'paypal-for-woocommerce'),
                    'true' => __('Enable', 'paypal-for-woocommerce')
                ),
            ),
            'enable_google_analytics_click' => array(
                'title' => __('Google Analytics', 'paypal-for-woocommerce'),
                'class' => 'in_context_checkout_part admin_smart_button_preview',
                'type' => 'checkbox',
                'label' => __('Enable Google Analytics Click Tracking.'),
                'default' => 'no'
            ),
            'single_product_button_settings' => array(
                'title' => __('Single Product Button Settings', 'paypal-for-woocommerce'),
                'class' => 'in_context_checkout_part',
                'description' => __('Enable the Product specific button settings, and the options set will be applied to the PayPal buttons on your Product pages.', 'paypal-for-woocommerce'),
                'type' => 'title',
                'class' => 'in_context_checkout_part_other',
            ),
            'single_product_configure_settings' => array(
                'title' => __('Enable', 'paypal-for-woocommerce'),
                'class' => 'in_context_checkout_part',
                'type' => 'checkbox',
                'label' => __('Configure settings specific to Single Product pages.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'desc_tip' => true,
                'description' => __('Optionally override global button settings above and configure buttons specific to Product pages.', 'paypal-for-woocommerce'),
            ),
            'single_product_button_layout' => array(
                'title' => __('Button Layout', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select in_context_checkout_part_other',
                'description' => __('Select Vertical for stacked buttons, and Horizontal for side-by-side buttons.', 'paypal-for-woocommerce'),
                'default' => 'horizontal',
                'desc_tip' => true,
                'options' => array(
                    'horizontal' => __('Horizontal', 'paypal-for-woocommerce'),
                    'vertical' => __('Vertical', 'paypal-for-woocommerce')
                ),
            ),
            'single_product_button_size' => array(
                'title' => __('Button Size', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select in_context_checkout_part_other',
                'description' => __('Set the size of the buttons you would like displayed.  Responsive will fit to the current element on the page.', 'paypal-for-woocommerce'),
                'default' => 'small',
                'desc_tip' => true,
                'options' => array(
                    'small' => __('Small', 'paypal-for-woocommerce'),
                    'medium' => __('Medium', 'paypal-for-woocommerce'),
                    'large' => __('Large', 'paypal-for-woocommerce'),
                    'responsive' => __('Responsive', 'paypal-for-woocommerce'),
                ),
            ),
            'single_product_button_height' => array(
                'title' => __('Button Height', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'in_context_checkout_part_other',
                'description' => __('Set the height of the buttons you would like displayed.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'options' => $button_height,
            ),
            'single_product_button_label' => array(
                'title' => __('Button Label', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select in_context_checkout_part',
                'description' => __('Set the label type you would like to use for the PayPal button.', 'paypal-for-woocommerce'),
                'default' => 'checkout',
                'desc_tip' => true,
                'options' => $this->button_label_array,
            ),
            'single_product_disallowed_funding_methods' => array(
                'title' => __('Hide Funding Method(s)', 'paypal-for-woocommerce'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select in_context_checkout_part_other',
                'description' => __('Funding methods selected here will be hidden from buyers during checkout.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'options' => $this->disallowed_funding_methods_array,
            ),
            'cart_button_settings' => array(
                'title' => __('Cart Button Settings', 'paypal-for-woocommerce'),
                'class' => 'in_context_checkout_part',
                'description' => __('Enable the Cart specific button settings, and the options set will be applied to the PayPal buttons on your shopping cart page.', 'paypal-for-woocommerce'),
                'type' => 'title',
                'class' => 'in_context_checkout_part_other',
            ),
            'cart_configure_settings' => array(
                'title' => __('Enable', 'paypal-for-woocommerce'),
                'class' => 'in_context_checkout_part',
                'type' => 'checkbox',
                'label' => __('Configure settings specific to the Cart page.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'desc_tip' => true,
                'description' => __('Optionally override global button settings above and configure buttons specific to the shopping cart page.', 'paypal-for-woocommerce'),
            ),
            'cart_button_layout' => array(
                'title' => __('Button Layout', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select in_context_checkout_part_other',
                'description' => __('Select Vertical for stacked buttons, and Horizontal for side-by-side buttons.', 'paypal-for-woocommerce'),
                'default' => 'horizontal',
                'desc_tip' => true,
                'options' => array(
                    'horizontal' => __('Horizontal', 'paypal-for-woocommerce'),
                    'vertical' => __('Vertical', 'paypal-for-woocommerce')
                ),
            ),
            'cart_button_size' => array(
                'title' => __('Button Size', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select in_context_checkout_part_other',
                'description' => __('Set the size of the buttons you would like displayed.  Responsive will fit to the current element on the page.', 'paypal-for-woocommerce'),
                'default' => 'small',
                'desc_tip' => true,
                'options' => array(
                    'small' => __('Small', 'paypal-for-woocommerce'),
                    'medium' => __('Medium', 'paypal-for-woocommerce'),
                    'large' => __('Large', 'paypal-for-woocommerce'),
                    'responsive' => __('Responsive', 'paypal-for-woocommerce'),
                ),
            ),
            'cart_button_height' => array(
                'title' => __('Button Height', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'in_context_checkout_part_other',
                'description' => __('Set the height of the buttons you would like displayed.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'options' => $button_height,
            ),
            'cart_button_label' => array(
                'title' => __('Button Label', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select in_context_checkout_part',
                'description' => __('Set the label type you would like to use for the PayPal button.', 'paypal-for-woocommerce'),
                'default' => 'checkout',
                'desc_tip' => true,
                'options' => $this->button_label_array,
            ),
            'cart_disallowed_funding_methods' => array(
                'title' => __('Hide Funding Method(s)', 'paypal-for-woocommerce'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select in_context_checkout_part_other',
                'description' => __('Funding methods selected here will be hidden from buyers during checkout.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'options' => $this->disallowed_funding_methods_array,
            ),
            'mini_cart_button_settings' => array(
                'title' => __('Mini-cart Button Settings', 'paypal-for-woocommerce'),
                'class' => 'in_context_checkout_part',
                'description' => __('Enable the Mini-Cart specific button settings, and the options set will be applied to the PayPal buttons on your mini-cart.', 'paypal-for-woocommerce'),
                'type' => 'title',
                'class' => 'in_context_checkout_part_other',
            ),
            'mini_cart_configure_settings' => array(
                'title' => __('Enable', 'paypal-for-woocommerce'),
                'class' => 'in_context_checkout_part',
                'type' => 'checkbox',
                'label' => __('Configure settings specific to the mini-cart display.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'desc_tip' => true,
                'description' => __('Optionally override global button settings above and configure buttons specific to the mini-cart.', 'paypal-for-woocommerce'),
            ),
            'mini_cart_button_layout' => array(
                'title' => __('Button Layout', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select in_context_checkout_part_other',
                'description' => __('Select Vertical for stacked buttons, and Horizontal for side-by-side buttons.', 'paypal-for-woocommerce'),
                'default' => 'horizontal',
                'desc_tip' => true,
                'options' => array(
                    'horizontal' => __('Horizontal', 'paypal-for-woocommerce'),
                    'vertical' => __('Vertical', 'paypal-for-woocommerce')
                ),
            ),
            'mini_cart_button_size' => array(
                'title' => __('Button Size', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select in_context_checkout_part_other',
                'description' => __('Set the size of the buttons you would like displayed.  Responsive will fit to the current element on the page.', 'paypal-for-woocommerce'),
                'default' => 'small',
                'desc_tip' => true,
                'options' => array(
                    'small' => __('Small', 'paypal-for-woocommerce'),
                    'medium' => __('Medium', 'paypal-for-woocommerce'),
                    'large' => __('Large', 'paypal-for-woocommerce'),
                    'responsive' => __('Responsive', 'paypal-for-woocommerce'),
                ),
            ),
            'mini_cart_button_height' => array(
                'title' => __('Button Height', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'in_context_checkout_part_other',
                'description' => __('Set the height of the buttons you would like displayed.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'options' => $button_height,
            ),
            'mini_cart_button_label' => array(
                'title' => __('Button Label', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select in_context_checkout_part',
                'description' => __('Set the label type you would like to use for the PayPal button.', 'paypal-for-woocommerce'),
                'default' => 'checkout',
                'desc_tip' => true,
                'options' => $this->button_label_array,
            ),
            'mini_cart_disallowed_funding_methods' => array(
                'title' => __('Hide Funding Method(s)', 'paypal-for-woocommerce'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select in_context_checkout_part_other',
                'description' => __('Funding methods selected here will be hidden from buyers during checkout.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'options' => $this->disallowed_funding_methods_array,
            ),
            'checkout_page_button_settings' => array(
                'title' => __('Checkout Page Button Settings', 'paypal-for-woocommerce'),
                'class' => 'in_context_checkout_part',
                'description' => __('Enable the Checkout Page specific button settings, and the options set will be applied to the PayPal buttons on your Checkout page.', 'paypal-for-woocommerce'),
                'type' => 'title',
                'class' => 'in_context_checkout_part_other',
            ),
            'checkout_page_configure_settings' => array(
                'title' => __('Enable', 'paypal-for-woocommerce'),
                'class' => 'in_context_checkout_part',
                'type' => 'checkbox',
                'label' => __('Configure settings specific to the Checkout page.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'desc_tip' => true,
                'description' => __('Optionally override global button settings above and configure buttons specific to the Checkout page.', 'paypal-for-woocommerce'),
            ),
            'checkout_page_disable_smart_button' => array(
                'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                'class' => 'in_context_checkout_part',
                'type' => 'checkbox',
                'label' => __('Disable smart buttons in the regular list of payment gateways.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'desc_tip' => true,
                'description' => __('', 'paypal-for-woocommerce'),
            ),
            'checkout_page_button_layout' => array(
                'title' => __('Button Layout', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select in_context_checkout_part_other',
                'description' => __('Select Vertical for stacked buttons, and Horizontal for side-by-side buttons.', 'paypal-for-woocommerce'),
                'default' => 'horizontal',
                'desc_tip' => true,
                'options' => array(
                    'horizontal' => __('Horizontal', 'paypal-for-woocommerce'),
                    'vertical' => __('Vertical', 'paypal-for-woocommerce')
                ),
            ),
            'checkout_page_button_size' => array(
                'title' => __('Button Size', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select in_context_checkout_part_other',
                'description' => __('Set the size of the buttons you would like displayed.  Responsive will fit to the current element on the page.', 'paypal-for-woocommerce'),
                'default' => 'small',
                'desc_tip' => true,
                'options' => array(
                    'small' => __('Small', 'paypal-for-woocommerce'),
                    'medium' => __('Medium', 'paypal-for-woocommerce'),
                    'large' => __('Large', 'paypal-for-woocommerce'),
                    'responsive' => __('Responsive', 'paypal-for-woocommerce'),
                ),
            ),
            'checkout_page_button_height' => array(
                'title' => __('Button Height', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'in_context_checkout_part_other',
                'description' => __('Set the height of the buttons you would like displayed.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'options' => $button_height,
            ),
            'checkout_page_button_label' => array(
                'title' => __('Button Label', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select in_context_checkout_part',
                'description' => __('Set the label type you would like to use for the PayPal button.', 'paypal-for-woocommerce'),
                'default' => 'checkout',
                'desc_tip' => true,
                'options' => $this->button_label_array,
            ),
            'checkout_page_disallowed_funding_methods' => array(
                'title' => __('Hide Funding Method(s)', 'paypal-for-woocommerce'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select in_context_checkout_part_other',
                'description' => __('Funding methods selected here will be hidden from buyers during checkout.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'options' => $this->disallowed_funding_methods_array,
            ),
        );
        if (defined('XOO_WSC_PATH')) {
            $this->form_fields['wsc_cart_button_settings'] = array(
                'title' => __('Woo Side Cart Button Settings', 'paypal-for-woocommerce'),
                'class' => 'in_context_checkout_part',
                'description' => __('Enable the Woo Side Cart specific button settings, and the options set will be applied to the PayPal buttons on your Woo Side Cart.', 'paypal-for-woocommerce'),
                'type' => 'title',
                'class' => 'in_context_checkout_part_other',
            );
            $this->form_fields['wsc_cart_configure_settings'] = array(
                'title' => __('Enable', 'paypal-for-woocommerce'),
                'class' => 'in_context_checkout_part',
                'type' => 'checkbox',
                'label' => __('Configure settings specific to the Woo Side Cart display.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'desc_tip' => true,
                'description' => __('Optionally override global button settings above and configure buttons specific to the Woo Side Cart.', 'paypal-for-woocommerce'),
            );
            $this->form_fields['wsc_cart_disable_smart_button'] = array(
                'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                'class' => 'in_context_checkout_part',
                'type' => 'checkbox',
                'label' => __('Disable the buttons in the Woo Side Cart', 'paypal-for-woocommerce'),
                'default' => 'no',
                'class' => 'in_context_checkout_part_other',
                'desc_tip' => true,
                'description' => __('', 'paypal-for-woocommerce'),
            );
            $this->form_fields['wsc_cart_button_layout'] = array(
                'title' => __('Button Layout', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select in_context_checkout_part_other',
                'description' => __('Select Vertical for stacked buttons, and Horizontal for side-by-side buttons.', 'paypal-for-woocommerce'),
                'default' => 'horizontal',
                'desc_tip' => true,
                'options' => array(
                    'horizontal' => __('Horizontal', 'paypal-for-woocommerce'),
                    'vertical' => __('Vertical', 'paypal-for-woocommerce')
                ),
            );
            $this->form_fields['wsc_cart_button_size'] = array(
                'title' => __('Button Size', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select in_context_checkout_part_other',
                'description' => __('Set the size of the buttons you would like displayed.  Responsive will fit to the current element on the page.', 'paypal-for-woocommerce'),
                'default' => 'small',
                'desc_tip' => true,
                'options' => array(
                    'small' => __('Small', 'paypal-for-woocommerce'),
                    'medium' => __('Medium', 'paypal-for-woocommerce'),
                    'large' => __('Large', 'paypal-for-woocommerce'),
                    'responsive' => __('Responsive', 'paypal-for-woocommerce'),
                ),
            );
            $this->form_fields['wsc_cart_button_height'] = array(
                'title' => __('Button Height', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'in_context_checkout_part_other',
                'description' => __('Set the height of the buttons you would like displayed.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'options' => $button_height,
            );
            $this->form_fields['wsc_cart_button_label'] = array(
                'title' => __('Button Label', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select in_context_checkout_part',
                'description' => __('Set the label type you would like to use for the PayPal button.', 'paypal-for-woocommerce'),
                'default' => 'checkout',
                'desc_tip' => true,
                'options' => $this->button_label_array,
            );
            $this->form_fields['wsc_cart_disallowed_funding_methods'] = array(
                'title' => __('Hide Funding Method(s)', 'paypal-for-woocommerce'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select in_context_checkout_part_other',
                'description' => __('Funding methods selected here will be hidden from buyers during checkout.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'options' => $this->disallowed_funding_methods_array,
            );
        }
        
            $this->form_fields['credit_messaging'] = array(
                'title' => __('', 'paypal-for-woocommerce'),
                'type' => 'title',
                'description' => '<div id="pms-muse-container">
                                <div class="pms-muse-left-container">
                                        <div class="pms-muse-description">
                                                <h2>PayPal Pay Later Messaging</h2>
                                                <h3>Offer &#8220;Buy Now Pay Later&#8221; to Buyers</h3>
                                                <p>PayPal Credit is a revolving line of credit that gives your customers the flexibility to buy now and pay over time, while you receive full payment immediately.</p>
                                                <p>Buyer-facing messaging allows you to present this option to your buyers, increasing conversion rates and average order total.</p>
                                        </div>'
            );
            $this->form_fields['enabled_credit_messaging'] = array(
                'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                'label' => __('Enable PayPal Pay Later Messaging - Buy Now Pay Later', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => '<div style="font-size: smaller">Displays Pay Later messaging for available offers. Restrictions apply. <a target="_blank" href="https://developer.paypal.com/docs/business/pay-later/commerce-platforms/angelleye/">See terms and learn more</a></div>',
                'default' => 'no'
            );
            $this->form_fields['credit_messaging_page_type'] = array(
                'title' => __('Page Type', 'paypal-for-woocommerce'),
                'type' => 'multiselect',
                'css'  => 'width: 100%;',
                'class' => 'wc-enhanced-select credit_messaging_field',
                'default' => array('home', 'category', 'product', 'cart', 'payment'),
                'options' => array('home' => __('Home', 'paypal-for-woocommerce'), 'category' => __('Category', 'paypal-for-woocommerce'), 'product' => __('Product', 'paypal-for-woocommerce'), 'cart' => __('Cart', 'paypal-for-woocommerce'), 'payment' => __('Payment', 'paypal-for-woocommerce')),
                'description' => '<div style="font-size: smaller;">Set the page(s) you want to display messaging on, and then adjust that page\'s display option below.</div>',

            );
            $this->form_fields['credit_messaging_data'] = array(
                'title' => __('', 'paypal-for-woocommerce'),
                'type' => 'title',
                'description' => '</div><div class="pms-muse-right-container">
                                        <h2>Why Add Buy Now Pay Later?</h2>
                                        <h3>Help Turn Browsers into Buyers!</h3>
                                        <ul>
                                        <li>Grow Sales - Businesses that promoted PayPal Credit on their websites saw a 21% increase in sales vs. those that did not.&#185;</li>
                                        <li>Attract New Customers - 85% of PayPal Credit users surveyed are more likely to shop at a retailer or online shop that offers interest-free credit options.&#178;</li>
                                        <li>Increase Average Order Value - Merchants with pay-over-time messaging on their websites saw a 56% increase in overall PayPal average order value.&#179;</li>
                                        <li>28% of shoppers now prefer retailers that offer an instant-financing solution.&#8308;</li>
                                        <li>56% of consumers agree that they prefer to pay a purchase back with installments rather than a credit card.&#8309;</li>
                                        <li>42% of PayPal Credit users would not have made their most recent purchase if PayPal Credit wasn’t offered.&#8310;</li>
                                        <li>Businesses that promoted PayPal Credit on their site and at checkout saw 214% larger PayPal Credit transactions than those who did not.&#8311;</li>
                                        </ul>
                                        <br>
                                        <div>
                                        <p style="font-size: smaller;">
                                        &#185;Average annual incremental sales based on PayPal’s analysis of internal data among 210 merchants with messaging and buttons against a broader group of merchants that did not, with 24-month continuous DCC volume between January 2016 and November 2019.<br><br>
                                        &#178;Online study commissioned by PayPal and conducted by Logica Research in May 2020 involving 2,000 U.S. consumers, where half were PayPal Credit users and half were non-PayPal Credit users, May 2020<br><br>
                                        &#179;Average lift in overall PayPal AOV for merchants with PayPal Pay Later Messaging  vs. those without, 2019 PayPal internal data<br><br>
                                        &#8308;Excerpted from Payments Journal, “Does the Answer to POS Consumer Financing Lie in Bank-Fintech Collaboration?”, Yaacov Martin, February 15, 2019<br><br>
                                        &#8309;Online study commissioned by PayPal and conducted by Logica Research in May 2020 involving 2,000 U.S. consumers, half were PayPal Credit users and half were non-PayPal Credit users, May 2020<br><br>
                                        &#8310;Online study commissioned by PayPal and conducted by Logica Research in November 2018 involving 2,000 U.S. consumers, half were PayPal Credit users and half were non-PayPal Credit users.<br><br>
                                        &#8311;Based on PayPal’s analysis of internal data of all PayPal and PayPal Credit active customers and volume from December 2017 – November 2018.<br><br>
                                        </p>
                                        </div>
                                        <div class="wrap pms-center-moreinfo">
                                            <div>
                                                <div><a target="_blank" href="https://www.angelleye.com/paypal-buy-now-pay-later/?utm_source=pfw&utm_medium=settings_more_info&utm_campaign=bnpl"><button class="pms-view-more paypal-px-btn">More Info</button></a></div>
                                            </div>
                                        </div>
                                        <br>
                                </div>
                         </div>'


            );
            $this->form_fields['credit_messaging_home'] = array(
                'title' => __('Home Page Settings', 'paypal-for-woocommerce'),
                'type' => 'title',
                'class' => 'credit_messaging_field credit_messaging_home_base_field',
                'description' => __('Configure Home Page specific settings for PayPal Pay Later Messaging.', 'paypal-for-woocommerce'),
            );
            $this->form_fields['credit_messaging_home_preview'] = array(
                'title' => __('', 'paypal-for-woocommerce'),
                'type' => 'title',
                'class' => '',
                'description' => '<div class="pp_message_home credit_messaging_field credit_messaging_home_field"></div>',
            );
            $this->form_fields['credit_messaging_home_layout_type'] = array(
                'title' => __('Layout Type', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_home_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'flex',
                'desc_tip' => true,
                'options' => array('text' => __('Text Layout', 'paypal-for-woocommerce'), 'flex' => __('Flex Layout', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_home_text_layout_logo_type'] = array(
                'title' => __('Logo Type', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_home_field credit_messaging_home_text_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'primary',
                'desc_tip' => true,
                'options' => array('primary' => __('Primary', 'paypal-for-woocommerce'), 'alternative' => __('Alternative', 'paypal-for-woocommerce'), 'inline' => __('Inline', 'paypal-for-woocommerce'), 'none' => __('None', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_home_text_layout_logo_position'] = array(
                'title' => __('Logo Position', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_home_field credit_messaging_home_text_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'left',
                'desc_tip' => true,
                'options' => array('left' => __('Left', 'paypal-for-woocommerce'), 'right' => __('Right', 'paypal-for-woocommerce'), 'top' => __('Top', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_home_text_layout_text_size'] = array(
                'title' => __('Text Size', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_home_field credit_messaging_home_text_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => '12',
                'desc_tip' => true,
                'options' => array('10' => __('10 px', 'paypal-for-woocommerce'), '11' => __('11 px', 'paypal-for-woocommerce'), '12' => __('12 px', 'paypal-for-woocommerce'), '13' => __('13 px', 'paypal-for-woocommerce'), '14' => __('14 px', 'paypal-for-woocommerce'), '15' => __('15 px', 'paypal-for-woocommerce'), '16' => __('16 px', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_home_text_layout_text_color'] = array(
                'title' => __('Text Color', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_home_field credit_messaging_home_text_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'black',
                'desc_tip' => true,
                'options' => array('black' => __('Black', 'paypal-for-woocommerce'), 'white' => __('White', 'paypal-for-woocommerce'), 'monochrome' => __('Monochrome', 'paypal-for-woocommerce'), 'grayscale' => __('Grayscale', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_home_flex_layout_color'] = array(
                'title' => __('Color', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_home_field credit_messaging_home_flex_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'blue',
                'desc_tip' => true,
                'options' => array('blue' => __('Blue', 'paypal-for-woocommerce'), 'black' => __('Black', 'paypal-for-woocommerce'), 'white' => __('White', 'paypal-for-woocommerce'), 'white-no-border' => __('White (No Border)', 'paypal-for-woocommerce'), 'gray' => __('Gray', 'paypal-for-woocommerce'), 'monochrome' => __('Monochrome', 'paypal-for-woocommerce'), 'grayscale' => __('Grayscale', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_home_flex_layout_ratio'] = array(
                'title' => __('Ratio', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_home_field credit_messaging_home_flex_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => '8x1',
                'desc_tip' => true,
                'options' => array('1x1' => __('Flexes between 120px and 300px wide', 'paypal-for-woocommerce'), '1x4' => __('160px wide', 'paypal-for-woocommerce'), '8x1' => __('Flexes between 250px and 768px wide', 'paypal-for-woocommerce'), '20x1' => __('Flexes between 250px and 1169px wide', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_home_shortcode'] = array(
                'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                'label' => __('I need a shortcode so that I can place the message in a better spot on Home page.', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'class' => 'credit_messaging_field credit_messaging_home_field credit_messaging_home_shortcode',
                'description' => '',
                'default' => 'no'
            );
            $this->form_fields['credit_messaging_home_preview_shortcode'] = array(
                'title' => __('Shortcode', 'paypal-for-woocommerce'),
                'type' => 'text',
                'class' => 'credit_messaging_field credit_messaging_home_field credit_messaging_home_preview_shortcode preview_shortcode',
                'description' => '',
                'custom_attributes' => array('readonly' => 'readonly'),
                'default' => '[aepfw_bnpl_message placement="home"]'
            );
            $this->form_fields['credit_messaging_category'] = array(
                'title' => __('Category Page Settings', 'paypal-for-woocommerce'),
                'type' => 'title',
                'class' => 'credit_messaging_field credit_messaging_category_base_field',
                'description' => __('Configure Category Page specific settings for PayPal Pay Later Messaging.', 'paypal-for-woocommerce'),
            );
            $this->form_fields['credit_messaging_category_preview'] = array(
                'title' => __('', 'paypal-for-woocommerce'),
                'type' => 'title',
                'class' => '',
                'description' => '<div class="pp_message_category credit_messaging_field credit_messaging_category_field"></div>',
            );
            $this->form_fields['credit_messaging_category_layout_type'] = array(
                'title' => __('Layout Type', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_category_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'flex',
                'desc_tip' => true,
                'options' => array('text' => __('Text Layout', 'paypal-for-woocommerce'), 'flex' => __('Flex Layout', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_category_text_layout_logo_type'] = array(
                'title' => __('Logo Type', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_category_field credit_messaging_category_text_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'primary',
                'desc_tip' => true,
                'options' => array('primary' => __('Primary', 'paypal-for-woocommerce'), 'alternative' => __('Alternative', 'paypal-for-woocommerce'), 'inline' => __('Inline', 'paypal-for-woocommerce'), 'none' => __('None', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_category_text_layout_logo_position'] = array(
                'title' => __('Logo Position', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_category_field credit_messaging_category_text_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'left',
                'desc_tip' => true,
                'options' => array('left' => __('Left', 'paypal-for-woocommerce'), 'right' => __('Right', 'paypal-for-woocommerce'), 'top' => __('Top', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_category_text_layout_text_size'] = array(
                'title' => __('Text Size', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_category_field credit_messaging_category_text_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => '12',
                'desc_tip' => true,
                'options' => array('10' => __('10 px', 'paypal-for-woocommerce'), '11' => __('11 px', 'paypal-for-woocommerce'), '12' => __('12 px', 'paypal-for-woocommerce'), '13' => __('13 px', 'paypal-for-woocommerce'), '14' => __('14 px', 'paypal-for-woocommerce'), '15' => __('15 px', 'paypal-for-woocommerce'), '16' => __('16 px', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_category_text_layout_text_color'] = array(
                'title' => __('Text Color', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_category_field credit_messaging_category_text_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'black',
                'desc_tip' => true,
                'options' => array('black' => __('Black', 'paypal-for-woocommerce'), 'white' => __('White', 'paypal-for-woocommerce'), 'monochrome' => __('Monochrome', 'paypal-for-woocommerce'), 'grayscale' => __('Grayscale', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_category_flex_layout_color'] = array(
                'title' => __('Color', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_category_field credit_messaging_category_flex_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'blue',
                'desc_tip' => true,
                'options' => array('blue' => __('Blue', 'paypal-for-woocommerce'), 'black' => __('Black', 'paypal-for-woocommerce'), 'white' => __('White', 'paypal-for-woocommerce'), 'white-no-border' => __('White (No Border)', 'paypal-for-woocommerce'), 'gray' => __('Gray', 'paypal-for-woocommerce'), 'monochrome' => __('Monochrome', 'paypal-for-woocommerce'), 'grayscale' => __('Grayscale', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_category_flex_layout_ratio'] = array(
                'title' => __('Ratio', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_category_field credit_messaging_category_flex_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => '8x1',
                'desc_tip' => true,
                'options' => array('1x1' => __('Flexes between 120px and 300px wide', 'paypal-for-woocommerce'), '1x4' => __('160px wide', 'paypal-for-woocommerce'), '8x1' => __('Flexes between 250px and 768px wide', 'paypal-for-woocommerce'), '20x1' => __('Flexes between 250px and 1169px wide', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_category_shortcode'] = array(
                'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                'label' => __('I need a shortcode so that I can place the message in a better spot on Category page.', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'class' => 'credit_messaging_field credit_messaging_category_field credit_messaging_category_shortcode',
                'description' => '',
                'default' => 'no'
            );
            $this->form_fields['credit_messaging_category_preview_shortcode'] = array(
                'title' => __('Shortcode', 'paypal-for-woocommerce'),
                'type' => 'text',
                'class' => 'credit_messaging_field credit_messaging_category_field credit_messaging_category_preview_shortcode preview_shortcode',
                'description' => '',
                'custom_attributes' => array('readonly' => 'readonly'),
                'default' => '[aepfw_bnpl_message placement="category"]'
            );
            $this->form_fields['credit_messaging_product'] = array(
                'title' => __('Product Page Settings', 'paypal-for-woocommerce'),
                'type' => 'title',
                'class' => 'credit_messaging_field credit_messaging_product_base_field',
                'description' => __('Configure Product Page specific settings for PayPal Pay Later Messaging.', 'paypal-for-woocommerce'),
            );
            $this->form_fields['credit_messaging_product_preview'] = array(
                'title' => __('', 'paypal-for-woocommerce'),
                'type' => 'title',
                'class' => '',
                'description' => '<div class="pp_message_product credit_messaging_field credit_messaging_product_field"></div>',
            );
            $this->form_fields['credit_messaging_product_layout_type'] = array(
                'title' => __('Layout Type', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_product_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'text',
                'desc_tip' => true,
                'options' => array('text' => __('Text Layout', 'paypal-for-woocommerce'), 'flex' => __('Flex Layout', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_product_text_layout_logo_type'] = array(
                'title' => __('Logo Type', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_product_field credit_messaging_product_text_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'primary',
                'desc_tip' => true,
                'options' => array('primary' => __('Primary', 'paypal-for-woocommerce'), 'alternative' => __('Alternative', 'paypal-for-woocommerce'), 'inline' => __('Inline', 'paypal-for-woocommerce'), 'none' => __('None', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_product_text_layout_logo_position'] = array(
                'title' => __('Logo Position', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_product_field credit_messaging_product_text_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'left',
                'desc_tip' => true,
                'options' => array('left' => __('Left', 'paypal-for-woocommerce'), 'right' => __('Right', 'paypal-for-woocommerce'), 'top' => __('Top', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_product_text_layout_text_size'] = array(
                'title' => __('Text Size', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_product_field credit_messaging_product_text_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => '12',
                'desc_tip' => true,
                'options' => array('10' => __('10 px', 'paypal-for-woocommerce'), '11' => __('11 px', 'paypal-for-woocommerce'), '12' => __('12 px', 'paypal-for-woocommerce'), '13' => __('13 px', 'paypal-for-woocommerce'), '14' => __('14 px', 'paypal-for-woocommerce'), '15' => __('15 px', 'paypal-for-woocommerce'), '16' => __('16 px', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_product_text_layout_text_color'] = array(
                'title' => __('Text Color', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_product_field credit_messaging_product_text_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'black',
                'desc_tip' => true,
                'options' => array('black' => __('Black', 'paypal-for-woocommerce'), 'white' => __('White', 'paypal-for-woocommerce'), 'monochrome' => __('Monochrome', 'paypal-for-woocommerce'), 'grayscale' => __('Grayscale', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_product_flex_layout_color'] = array(
                'title' => __('Color', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_product_field credit_messaging_product_flex_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'blue',
                'desc_tip' => true,
                'options' => array('blue' => __('Blue', 'paypal-for-woocommerce'), 'black' => __('Black', 'paypal-for-woocommerce'), 'white' => __('White', 'paypal-for-woocommerce'), 'white-no-border' => __('White (No Border)', 'paypal-for-woocommerce'), 'gray' => __('Gray', 'paypal-for-woocommerce'), 'monochrome' => __('Monochrome', 'paypal-for-woocommerce'), 'grayscale' => __('Grayscale', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_product_flex_layout_ratio'] = array(
                'title' => __('Ratio', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_product_field credit_messaging_product_flex_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => '1x1',
                'desc_tip' => true,
                'options' => array('1x1' => __('Flexes between 120px and 300px wide', 'paypal-for-woocommerce'), '1x4' => __('160px wide', 'paypal-for-woocommerce'), '8x1' => __('Flexes between 250px and 768px wide', 'paypal-for-woocommerce'), '20x1' => __('Flexes between 250px and 1169px wide', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_product_shortcode'] = array(
                'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                'label' => __('I need a shortcode so that I can place the message in a better spot on Product page.', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'class' => 'credit_messaging_field credit_messaging_product_field credit_messaging_product_shortcode',
                'description' => '',
                'default' => 'no'
            );
            $this->form_fields['credit_messaging_product_preview_shortcode'] = array(
                'title' => __('Shortcode', 'paypal-for-woocommerce'),
                'type' => 'text',
                'class' => 'credit_messaging_field credit_messaging_product_field credit_messaging_product_preview_shortcode preview_shortcode',
                'description' => '',
                'custom_attributes' => array('readonly' => 'readonly'),
                'default' => '[aepfw_bnpl_message placement="product"]'
            );
            $this->form_fields['credit_messaging_cart'] = array(
                'title' => __('Cart Page Settings', 'paypal-for-woocommerce'),
                'type' => 'title',
                'class' => 'credit_messaging_field credit_messaging_cart_base_field',
                'description' => __('Configure Cart Page specific settings for PayPal Pay Later Messaging.', 'paypal-for-woocommerce'),
            );
            $this->form_fields['credit_messaging_cart_preview'] = array(
                'title' => __('', 'paypal-for-woocommerce'),
                'type' => 'title',
                'class' => '',
                'description' => '<div class="pp_message_cart credit_messaging_field credit_messaging_cart_field"></div>',
            );
            $this->form_fields['credit_messaging_cart_layout_type'] = array(
                'title' => __('Layout Type', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_cart_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'text',
                'desc_tip' => true,
                'options' => array('text' => __('Text Layout', 'paypal-for-woocommerce'), 'flex' => __('Flex Layout', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_cart_text_layout_logo_type'] = array(
                'title' => __('Logo Type', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_cart_field credit_messaging_cart_text_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'primary',
                'desc_tip' => true,
                'options' => array('primary' => __('Primary', 'paypal-for-woocommerce'), 'alternative' => __('Alternative', 'paypal-for-woocommerce'), 'inline' => __('Inline', 'paypal-for-woocommerce'), 'none' => __('None', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_cart_text_layout_logo_position'] = array(
                'title' => __('Logo Position', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_cart_field credit_messaging_cart_text_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'left',
                'desc_tip' => true,
                'options' => array('left' => __('Left', 'paypal-for-woocommerce'), 'right' => __('Right', 'paypal-for-woocommerce'), 'top' => __('Top', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_cart_text_layout_text_size'] = array(
                'title' => __('Text Size', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_cart_field credit_messaging_cart_text_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => '12',
                'desc_tip' => true,
                'options' => array('10' => __('10 px', 'paypal-for-woocommerce'), '11' => __('11 px', 'paypal-for-woocommerce'), '12' => __('12 px', 'paypal-for-woocommerce'), '13' => __('13 px', 'paypal-for-woocommerce'), '14' => __('14 px', 'paypal-for-woocommerce'), '15' => __('15 px', 'paypal-for-woocommerce'), '16' => __('16 px', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_cart_text_layout_text_color'] = array(
                'title' => __('Text Color', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_cart_field credit_messaging_cart_text_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'black',
                'desc_tip' => true,
                'options' => array('black' => __('Black', 'paypal-for-woocommerce'), 'white' => __('White', 'paypal-for-woocommerce'), 'monochrome' => __('Monochrome', 'paypal-for-woocommerce'), 'grayscale' => __('Grayscale', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_cart_flex_layout_color'] = array(
                'title' => __('Color', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_cart_field credit_messaging_cart_flex_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'blue',
                'desc_tip' => true,
                'options' => array('blue' => __('Blue', 'paypal-for-woocommerce'), 'black' => __('Black', 'paypal-for-woocommerce'), 'white' => __('White', 'paypal-for-woocommerce'), 'white-no-border' => __('White (No Border)', 'paypal-for-woocommerce'), 'gray' => __('Gray', 'paypal-for-woocommerce'), 'monochrome' => __('Monochrome', 'paypal-for-woocommerce'), 'grayscale' => __('Grayscale', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_cart_flex_layout_ratio'] = array(
                'title' => __('Ratio', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_cart_field credit_messaging_cart_flex_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => '1x1',
                'desc_tip' => true,
                'options' => array('1x1' => __('Flexes between 120px and 300px wide', 'paypal-for-woocommerce'), '1x4' => __('160px wide', 'paypal-for-woocommerce'), '8x1' => __('Flexes between 250px and 768px wide', 'paypal-for-woocommerce'), '20x1' => __('Flexes between 250px and 1169px wide', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_cart_shortcode'] = array(
                'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                'label' => __('I need a shortcode so that I can place the message in a better spot on Cart page.', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'class' => 'credit_messaging_field credit_messaging_cart_field credit_messaging_cart_shortcode',
                'description' => '',
                'default' => 'no'
            );
            $this->form_fields['credit_messaging_cart_preview_shortcode'] = array(
                'title' => __('Shortcode', 'paypal-for-woocommerce'),
                'type' => 'text',
                'class' => 'credit_messaging_field credit_messaging_cart_field credit_messaging_cart_preview_shortcode preview_shortcode',
                'description' => '',
                'custom_attributes' => array('readonly' => 'readonly'),
                'default' => '[aepfw_bnpl_message placement="cart"]'
            );
            $this->form_fields['credit_messaging_payment'] = array(
                'title' => __('Payment Page Settings', 'paypal-for-woocommerce'),
                'type' => 'title',
                'class' => 'credit_messaging_field credit_messaging_payment_base_field',
                'description' => __('Configure Home Page specific settings for PayPal Pay Later Messaging.', 'paypal-for-woocommerce'),
            );
            $this->form_fields['credit_messaging_payment_preview'] = array(
                'title' => __('', 'paypal-for-woocommerce'),
                'type' => 'title',
                'class' => '',
                'description' => '<div class="pp_message_payment credit_messaging_field credit_messaging_payment_field"></div>',
            );
            $this->form_fields['credit_messaging_payment_layout_type'] = array(
                'title' => __('Layout Type', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_payment_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'text',
                'desc_tip' => true,
                'options' => array('text' => __('Text Layout', 'paypal-for-woocommerce'), 'flex' => __('Flex Layout', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_payment_text_layout_logo_type'] = array(
                'title' => __('Logo Type', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_payment_field credit_messaging_payment_text_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'primary',
                'desc_tip' => true,
                'options' => array('primary' => __('Primary', 'paypal-for-woocommerce'), 'alternative' => __('Alternative', 'paypal-for-woocommerce'), 'inline' => __('Inline', 'paypal-for-woocommerce'), 'none' => __('None', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_payment_text_layout_logo_position'] = array(
                'title' => __('Logo Position', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_payment_field credit_messaging_payment_text_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'left',
                'desc_tip' => true,
                'options' => array('left' => __('Left', 'paypal-for-woocommerce'), 'right' => __('Right', 'paypal-for-woocommerce'), 'top' => __('Top', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_payment_text_layout_text_size'] = array(
                'title' => __('Text Size', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_payment_field credit_messaging_payment_text_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => '12',
                'desc_tip' => true,
                'options' => array('10' => __('10 px', 'paypal-for-woocommerce'), '11' => __('11 px', 'paypal-for-woocommerce'), '12' => __('12 px', 'paypal-for-woocommerce'), '13' => __('13 px', 'paypal-for-woocommerce'), '14' => __('14 px', 'paypal-for-woocommerce'), '15' => __('15 px', 'paypal-for-woocommerce'), '16' => __('16 px', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_payment_text_layout_text_color'] = array(
                'title' => __('Text Color', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_payment_field credit_messaging_payment_text_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'black',
                'desc_tip' => true,
                'options' => array('black' => __('Black', 'paypal-for-woocommerce'), 'white' => __('White', 'paypal-for-woocommerce'), 'monochrome' => __('Monochrome', 'paypal-for-woocommerce'), 'grayscale' => __('Grayscale', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_payment_flex_layout_color'] = array(
                'title' => __('Color', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_payment_field credit_messaging_payment_flex_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => 'blue',
                'desc_tip' => true,
                'options' => array('blue' => __('Blue', 'paypal-for-woocommerce'), 'black' => __('Black', 'paypal-for-woocommerce'), 'white' => __('White', 'paypal-for-woocommerce'), 'white-no-border' => __('White (No Border)', 'paypal-for-woocommerce'), 'gray' => __('Gray', 'paypal-for-woocommerce'), 'monochrome' => __('Monochrome', 'paypal-for-woocommerce'), 'grayscale' => __('Grayscale', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_payment_flex_layout_ratio'] = array(
                'title' => __('Ratio', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select credit_messaging_field credit_messaging_payment_field credit_messaging_payment_flex_layout_field',
                'description' => __('', 'paypal-for-woocommerce'),
                'default' => '1x1',
                'desc_tip' => true,
                'options' => array('1x1' => __('Flexes between 120px and 300px wide', 'paypal-for-woocommerce'), '1x4' => __('160px wide', 'paypal-for-woocommerce'), '8x1' => __('Flexes between 250px and 768px wide', 'paypal-for-woocommerce'), '20x1' => __('Flexes between 250px and 1169px wide', 'paypal-for-woocommerce'))
            );
            $this->form_fields['credit_messaging_payment_shortcode'] = array(
                'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                'label' => __('I need a shortcode so that I can place the message in a better spot on Payment page.', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'class' => 'credit_messaging_field credit_messaging_payment_field credit_messaging_payment_shortcode',
                'description' => '',
                'default' => 'no'
            );
            $this->form_fields['credit_messaging_payment_preview_shortcode'] = array(
                'title' => __('Shortcode', 'paypal-for-woocommerce'),
                'type' => 'text',
                'class' => 'credit_messaging_field credit_messaging_payment_field credit_messaging_payment_preview_shortcode preview_shortcode',
                'description' => '',
                'custom_attributes' => array('readonly' => 'readonly'),
                'default' => '[aepfw_bnpl_message placement="payment"]'
            );
        
        $this->form_fields = apply_filters('angelleye_ec_form_fields', $this->form_fields);
    }

    public function is_available() {
        if (AngellEYE_Utility::is_express_checkout_credentials_is_set() == false) {
            return false;
        }
        if (!AngellEYE_Utility::is_valid_for_use_paypal_express()) {
            return false;
        }
        if ($this->show_on_checkout != 'regular' && $this->show_on_checkout != 'both') {
            if ($this->function_helper->ec_is_express_checkout()) {
                return true;
            } else {
                return false;
            }
        }
        return parent::is_available();
    }

    public function payment_fields() {
        if ($description = $this->get_description()) {
            echo wpautop(wptexturize($description));
        }
        if ($this->function_helper->ec_is_express_checkout() == false) {
            if ($this->supports('tokenization') && is_checkout()) {
                $this->tokenization_script();
                $this->saved_payment_methods();
                if (AngellEYE_Utility::is_cart_contains_subscription() == false && AngellEYE_Utility::is_subs_change_payment() == false) {
                    $this->save_payment_method_checkbox();
                }
                do_action('payment_fields_saved_payment_methods', $this);
            }
        }
    }

    public function save_payment_method_checkbox() {
        printf(
                '<p class="form-row woocommerce-SavedPaymentMethods-saveNew">
                        <input id="wc-%1$s-new-payment-method" name="wc-%1$s-new-payment-method" type="checkbox" value="true" style="width:auto;" />
                        <label style="display:inline;">%2$s</label>
                </p>', esc_attr($this->id), apply_filters('cc_form_label_save_to_account', __('Save payment method to my account.', 'paypal-for-woocommerce'), $this->id)
        );
    }

    public function process_subscription_payment($order_id) {
        $order = wc_get_order($order_id);
        if ($this->is_subscription($order_id)) {
            $this->angelleye_reload_gateway_credentials_for_woo_subscription_renewal_order($order);
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/express-checkout/class-wc-gateway-paypal-express-request-angelleye.php' );
            $paypal_express_request = new WC_Gateway_PayPal_Express_Request_AngellEYE($this);
            $result = $paypal_express_request->DoReferenceTransaction($order_id);
            if (!empty($result['ACK']) && $result['ACK'] == 'Success' || $result['ACK'] == 'SuccessWithWarning') {
                $paypal_express_request->update_payment_status_by_paypal_responce($order_id, $result);
                if (isset(WC()->cart) || '' != WC()->cart) {
                    if (!WC()->cart->is_empty()) {
                        WC()->cart->empty_cart();
                        return array(
                            'result' => 'success',
                            'redirect' => add_query_arg('utm_nooverride', '1', $this->get_return_url($order))
                        );
                    }
                }
            } else {
                $ErrorCode = urldecode(!empty($result["L_ERRORCODE0"]) ? $result["L_ERRORCODE0"] : '');
                $ErrorLongMsg = urldecode(!empty($result["L_LONGMESSAGE0"]) ? $result["L_LONGMESSAGE0"] : '');
                $order->add_order_note($ErrorCode . ' - ' . $ErrorLongMsg);
                $this->paypal_express_checkout_error_handler($request_name = 'DoReferenceTransaction', '', $result);
            }
        }
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        try {
            if (!empty($_POST['wc-paypal_express-payment-token']) && $_POST['wc-paypal_express-payment-token'] != 'new') {
                $result = $this->angelleye_ex_doreference_transaction($order_id);
                if (!empty($result['ACK']) && $result['ACK'] == 'Success' || $result['ACK'] == 'SuccessWithWarning') {
                    // @note Skylar L check for duplicate order
                    if ($result['ACK'] == 'SuccessWithWarning' && !empty($result['L_ERRORCODE0']) && '11607' == $result['L_ERRORCODE0']) {
                        $order->update_status('on-hold', empty($result['L_LONGMESSAGE0']) ? $result['L_SHORTMESSAGE0'] : $result['L_LONGMESSAGE0']);
                    } else {
                        $order->payment_complete($result['TRANSACTIONID']);
                        $order->add_order_note(sprintf(__('%s payment approved! Transaction ID: %s', 'paypal-for-woocommerce'), $this->title, $result['TRANSACTIONID']));
                    }
                    WC()->cart->empty_cart();
                    return array(
                        'result' => 'success',
                        'redirect' => add_query_arg('utm_nooverride', '1', $this->get_return_url($order))
                    );
                } else {
                    $redirect_url = wc_get_cart_url();
                    $this->paypal_express_checkout_error_handler($request_name = 'DoReferenceTransaction', $redirect_url, $result);
                    if (!is_ajax()) {
                        wp_redirect($redirect_url);
                        exit;
                    } else {
                        return array(
                            'result' => 'fail',
                            'redirect' => $redirect_url
                        );
                    }
                }
            }
            if ($this->function_helper->ec_is_express_checkout()) {
                $return_url = add_query_arg('order_id', $order_id, $this->function_helper->ec_get_checkout_url('do_express_checkout_payment', $order_id));
                if (is_user_logged_in() && !empty($_POST['ship_to_different_address']) && $_POST['ship_to_different_address'] == '1') {
                    
                } else {
                    if (empty($_POST['shipping_country'])) {
                        $paypal_express_checkout = angelleye_get_session('paypal_express_checkout');
                        $shipping_details = isset($paypal_express_checkout['shipping_details']) ? wp_unslash($paypal_express_checkout['shipping_details']) : array();
                        AngellEYE_Utility::angelleye_set_address($order_id, $shipping_details, 'shipping');
                    }
                }
                $post_data = angelleye_get_session('post_data');
                if ($this->billing_address && empty($post_data)) {
                    if (empty($_POST['billing_country'])) {
                        $paypal_express_checkout = angelleye_get_session('paypal_express_checkout');
                        $shipping_details = isset($paypal_express_checkout['shipping_details']) ? wp_unslash($paypal_express_checkout['shipping_details']) : array();
                        AngellEYE_Utility::angelleye_set_address($order_id, $shipping_details, 'billing');
                    }
                }
                $args = array(
                    'result' => 'success',
                    'redirect' => $return_url,
                );
                if (isset($_POST['terms']) && wc_get_page_id('terms') > 0) {
                    angelleye_set_session('paypal_express_terms', true);
                }
                if (is_ajax()) {
                    if ($this->function_helper->ec_is_version_gte_2_4()) {
                        wp_send_json($args);
                    } else {
                        echo '<!--WC_START-->' . json_encode($args) . '<!--WC_END-->';
                    }
                } else {
                    wp_redirect($args['redirect']);
                }
                exit;
            } else {
                require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/express-checkout/class-wc-gateway-paypal-express-request-angelleye.php' );
                $paypal_express_request = new WC_Gateway_PayPal_Express_Request_AngellEYE($this);
                if ((isset($_POST['wc-paypal_express-new-payment-method']) && $_POST['wc-paypal_express-new-payment-method'] == 'true') || ( isset($_GET['ec_save_to_account']) && $_GET['ec_save_to_account'] == true)) {
                    angelleye_set_session('ec_save_to_account', 'on');
                } else {
                    unset(WC()->session->ec_save_to_account);
                }
                if (!empty($_GET['pay_for_order']) && $_GET['pay_for_order'] == true) {
                    $paypal_express_request->angelleye_set_express_checkout();
                }
                if (isset($_POST['terms']) && wc_get_page_id('terms') > 0) {
                    angelleye_set_session('paypal_express_terms', true);
                }
                angelleye_set_session('post_data', wp_unslash($_POST));
                //$_GET['pp_action'] = 'set_express_checkout';
                $paypal_express_request->angelleye_set_express_checkout();
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ex_doreference_transaction($order_id) {
        require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/express-checkout/class-wc-gateway-paypal-express-request-angelleye.php' );
        $paypal_express_request = new WC_Gateway_PayPal_Express_Request_AngellEYE($this);
        return $paypal_response = $paypal_express_request->DoReferenceTransaction($order_id);
    }

    public function angelleye_express_checkout_encrypt_gateway_api($settings) {
        if (!empty($settings['sandbox_api_password'])) {
            $api_password = $settings['sandbox_api_password'];
        } else {
            $api_password = $settings['api_password'];
        }
        if (strlen($api_password) > 35) {
            return $settings;
        }
        if (!empty($settings['is_encrypt'])) {
            $gateway_settings_keys = array('sandbox_api_username', 'sandbox_api_password', 'sandbox_api_signature', 'api_username', 'api_password', 'api_signature');
            foreach ($gateway_settings_keys as $gateway_settings_key => $gateway_settings_value) {
                if (!empty($settings[$gateway_settings_value])) {
                    $settings[$gateway_settings_value] = AngellEYE_Utility::crypting($settings[$gateway_settings_value], $action = 'e');
                }
            }
        }
        return $settings;
    }

    public static function angelleye_get_paypalimage() {
        if (AngellEYE_Utility::get_button_locale_code() == 'en_US') {
            $image_path = plugins_url('/assets/images/dynamic-image/' . AngellEYE_Utility::get_button_locale_code() . '.png', plugin_basename(dirname(__FILE__)));
        } else {
            $image_path = plugins_url('/assets/images/dynamic-image/' . AngellEYE_Utility::get_button_locale_code() . '.gif', plugin_basename(dirname(__FILE__)));
            if (is_ssl() || 'yes' === get_option('woocommerce_force_ssl_checkout')) {
                $image_path = preg_replace("/^http:/i", "https:", $image_path);
            }
        }

        return $image_path;
    }

    public function handle_wc_api() {
        try {
            if (isset($_POST['from_checkout']) && 'yes' === $_POST['from_checkout']) {
                WC()->checkout->process_checkout();
            }
            $old_wc = version_compare(WC_VERSION, '3.0', '<');
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/express-checkout/class-wc-gateway-paypal-express-request-angelleye.php' );
            $paypal_express_request = new WC_Gateway_PayPal_Express_Request_AngellEYE($this);
            if (wc_notice_count('error') > 0) {
                $paypal_express_request->angelleye_redirect();
                exit;
            }
            if (!isset($_GET['pp_action'])) {
                return;
            }
            if (!defined('WOOCOMMERCE_CHECKOUT')) {
                define('WOOCOMMERCE_CHECKOUT', true);
            }
            if (!defined('WOOCOMMERCE_CART')) {
                define('WOOCOMMERCE_CART', true);
            }
            WC()->cart->calculate_shipping();
            if (version_compare(WC_VERSION, '3.0', '<')) {
                WC()->customer->calculated_shipping(true);
            } else {
                WC()->customer->set_calculated_shipping(true);
            }

            if (WC()->cart->cart_contents_total <= 0 && WC()->cart->total <= 0 && AngellEYE_Utility::is_cart_contains_subscription() == false) {
                if (empty($_GET['pay_for_order'])) {
                    if (AngellEYE_Utility::is_cart_contains_subscription() == false) {
                        wc_add_notice(__('your order amount is zero, We were unable to process your order, please try again.', 'paypal-for-woocommerce'), 'error');
                    }
                    $paypal_express_request->angelleye_redirect();
                    exit;
                }
            }

            switch ($_GET['pp_action']) {
                case 'cancel_order':
                    $this->function_helper->ec_clear_session_data();
                    $cancel_url = !empty($this->cancel_page_id) ? get_permalink($this->cancel_page_id) : wc_get_cart_url();
                    wp_safe_redirect($cancel_url);
                    exit;
                case 'set_express_checkout':
                    $this->angelleye_check_cart_items();
                    if ((isset($_POST['wc-paypal_express-new-payment-method']) && $_POST['wc-paypal_express-new-payment-method'] == 'true') || ( isset($_GET['ec_save_to_account']) && $_GET['ec_save_to_account'] == true)) {
                        angelleye_set_session('ec_save_to_account', 'on');
                    } else {
                        unset(WC()->session->ec_save_to_account);
                    }
                    $paypal_express_request->angelleye_set_express_checkout();
                    break;
                case 'get_express_checkout_details':
                    $this->angelleye_check_cart_items();
                    $paypal_express_request->angelleye_get_express_checkout_details();
                    $order_id = absint(angelleye_get_session('order_awaiting_payment'));
                    if (!empty($_GET['pay_for_order']) && $_GET['pay_for_order'] == true) {
                        
                    } else {
                        if ($order_id > 0 && ( $order = wc_get_order($order_id) ) && $order->has_status(array('pending', 'failed'))) {
                            $_POST = angelleye_get_session('post_data');
                            $_POST['post_data'] = angelleye_get_session('post_data');
                            $this->posted = angelleye_get_session('post_data');
                            $chosen_shipping_methods = angelleye_get_session('chosen_shipping_methods');
                            if (isset($_POST['shipping_method']) && is_array($_POST['shipping_method']))
                                foreach ($_POST['shipping_method'] as $i => $value) {
                                    $chosen_shipping_methods[$i] = wc_clean($value);
                                }
                            angelleye_set_session('chosen_shipping_methods', $chosen_shipping_methods);
                            if (WC()->cart->needs_shipping()) {
                                // Validate Shipping Methods
                                WC()->shipping->get_shipping_methods();
                                $packages = WC()->shipping->get_packages();
                                WC()->checkout()->shipping_methods = angelleye_get_session('chosen_shipping_methods');
                            }
                            if (empty($this->posted)) {
                                $this->posted = array();
                                $paypal_express_checkout = angelleye_get_session('paypal_express_checkout');
                                if (!empty($paypal_express_checkout['shipping_details']['email'])) {
                                    $this->posted['billing_email'] = $paypal_express_checkout['shipping_details']['email'];
                                }
                                if (!empty($paypal_express_checkout['shipping_details']['first_name'])) {
                                    $this->posted['billing_first_name'] = $paypal_express_checkout['shipping_details']['first_name'];
                                }
                                if (!empty($paypal_express_checkout['shipping_details']['last_name'])) {
                                    $this->posted['billing_last_name'] = $paypal_express_checkout['shipping_details']['last_name'];
                                }
                                $this->posted['payment_method'] = $this->id;
                            }

                            $validate_data = angelleye_get_session('validate_data');

                            if (!empty($validate_data)) {
                                $order_id = WC()->checkout()->create_order($validate_data);
                            } else {
                                $order_id = WC()->checkout()->create_order($this->posted);
                            }

                            if (is_wp_error($order_id)) {
                                throw new Exception($order_id->get_error_message());
                            }

                            /** Creating Order Object for fresh created order */
                            $order = wc_get_order($order_id);

                            if (!is_user_logged_in() && WC()->checkout->is_registration_required($order_id)) {
                                $paypal_express_request->angelleye_process_customer($order_id);
                            }
                            do_action('woocommerce_checkout_order_processed', $order_id, $this->posted, $order);
                        } else {
                            $_POST = angelleye_get_session('post_data');
                            $_POST['post_data'] = angelleye_get_session('post_data');
                            $this->posted = angelleye_get_session('post_data');
                        }
                        if ($order_id == 0) {
                            $_POST = angelleye_get_session('post_data');
                            $_POST['post_data'] = angelleye_get_session('post_data');
                            $this->posted = angelleye_get_session('post_data');
                            $chosen_shipping_methods = angelleye_get_session('chosen_shipping_methods');
                            if (isset($_POST['shipping_method']) && is_array($_POST['shipping_method']))
                                foreach ($_POST['shipping_method'] as $i => $value)
                                    $chosen_shipping_methods[$i] = wc_clean($value);
                            angelleye_set_session('chosen_shipping_methods', $chosen_shipping_methods);
                            if (WC()->cart->needs_shipping()) {
                                // Validate Shipping Methods
                                WC()->shipping->get_shipping_methods();
                                $packages = WC()->shipping->get_packages();
                                WC()->checkout()->shipping_methods = angelleye_get_session('chosen_shipping_methods');
                            }
                            if (empty($this->posted)) {
                                $this->posted = array();
                                $paypal_express_checkout = angelleye_get_session('paypal_express_checkout');
                                if (!empty($paypal_express_checkout['shipping_details']['email'])) {
                                    $this->posted['billing_email'] = $paypal_express_checkout['shipping_details']['email'];
                                } elseif (!empty($paypal_express_checkout['ExpresscheckoutDetails']['EMAIL'])) {
                                    $this->posted['billing_email'] = $paypal_express_checkout['ExpresscheckoutDetails']['EMAIL'];
                                }
                                if (!empty($paypal_express_checkout['shipping_details']['first_name'])) {
                                    $this->posted['billing_first_name'] = $paypal_express_checkout['shipping_details']['first_name'];
                                } elseif (!empty($paypal_express_checkout['ExpresscheckoutDetails']['FIRSTNAME'])) {
                                    $this->posted['billing_first_name'] = $paypal_express_checkout['ExpresscheckoutDetails']['FIRSTNAME'];
                                }
                                if (!empty($paypal_express_checkout['shipping_details']['last_name'])) {
                                    $this->posted['billing_last_name'] = $paypal_express_checkout['shipping_details']['last_name'];
                                } elseif (!empty($paypal_express_checkout['ExpresscheckoutDetails']['LASTNAME'])) {
                                    $this->posted['billing_last_name'] = $paypal_express_checkout['ExpresscheckoutDetails']['LASTNAME'];
                                }
                                $this->posted['payment_method'] = $this->id;
                            }

                            $validate_data = angelleye_get_session('validate_data');

                            if (!empty($validate_data)) {
                                $order_id = WC()->checkout()->create_order($validate_data);
                            } else {
                                $order_id = WC()->checkout()->create_order($this->posted);
                            }

                            if (is_wp_error($order_id)) {
                                throw new Exception($order_id->get_error_message());
                            }

                            /** Creating Order Object for fresh created order */
                            $order = wc_get_order($order_id);

                            if (!is_user_logged_in() && WC()->checkout->is_registration_required()) {
                                $paypal_express_request->angelleye_process_customer($order_id);
                            }
                            do_action('woocommerce_checkout_order_processed', $order_id, $this->posted, $order);
                        }
                        if (!$order instanceof WC_Order) {
                            $order = wc_get_order($order_id);
                        }
                        $post_data = angelleye_get_session('post_data');
                        if ($this->billing_address && empty($post_data)) {
                            $paypal_express_checkout = angelleye_get_session('paypal_express_checkout');
                            $shipping_details = isset($paypal_express_checkout['shipping_details']) ? $paypal_express_checkout['shipping_details'] : array();
                            AngellEYE_Utility::angelleye_set_address($order_id, $shipping_details, 'billing');
                        } else {
                            $billing_address = array();
                            $checkout_fields['billing'] = WC()->countries->get_address_fields(WC()->checkout->get_value('billing_country'), 'billing_');
                            if ($checkout_fields['billing']) {
                                foreach (array_keys($checkout_fields['billing']) as $field) {
                                    $field_name = str_replace('billing_', '', $field);
                                    $billing_address[$field_name] = $this->angelleye_ec_get_posted_address_data($field_name);
                                }
                            }
                            AngellEYE_Utility::angelleye_set_address($order_id, $billing_address, 'billing');
                        }
                        $paypal_express_checkout = angelleye_get_session('paypal_express_checkout');
                        $shipping_details = isset($paypal_express_checkout['shipping_details']) ? wp_unslash($paypal_express_checkout['shipping_details']) : array();
                        AngellEYE_Utility::angelleye_set_address($order_id, $shipping_details, 'shipping');
                        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
                        if ($old_wc) {
                            update_post_meta($order_id, '_payment_method', $this->id);
                            update_post_meta($order_id, '_payment_method_title', $this->title);
                            update_post_meta($order_id, '_customer_user', get_current_user_id());
                        } else {
                            $order->set_payment_method($this);
                            update_post_meta($order->get_id(), '_customer_user', get_current_user_id());
                        }
                        $post_data = angelleye_get_session('post_data');
                        if (!empty($post_data['billing_phone'])) {
                            if ($old_wc) {
                                update_post_meta($order_id, '_billing_phone', $post_data['billing_phone']);
                            } else {
                                update_post_meta($order->get_id(), '_billing_phone', $post_data['billing_phone']);
                            }
                        }
                        if (!empty($post_data['order_comments'])) {
                            if ($old_wc) {
                                update_post_meta($order_id, 'order_comments', $post_data['order_comments']);
                            } else {
                                update_post_meta($order->get_id(), 'order_comments', $post_data['order_comments']);
                            }
                            $my_post = array(
                                'ID' => $order_id,
                                'post_excerpt' => $post_data['order_comments'],
                            );
                            wp_update_post($my_post);
                        }
                        $_GET['order_id'] = $order_id;
                    }
                    $paypal_express_request->angelleye_do_express_checkout_payment();
                    break;
                case 'do_express_checkout_payment':
                    $paypal_express_request->angelleye_do_express_checkout_payment();
                    break;
            }
        } catch (Exception $ex) {
            
        }
    }

    public function get_transaction_url($order) {
        if (!$this->supports('tokenization')) {
            $sandbox_transaction_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
            $live_transaction_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
            $old_wc = version_compare(WC_VERSION, '3.0', '<');
            $is_sandbox = $old_wc ? get_post_meta($order->id, 'is_sandbox', true) : get_post_meta($order->get_id(), 'is_sandbox', true);
            if ($is_sandbox == true) {
                $this->view_transaction_url = $sandbox_transaction_url;
            } else {
                if (empty($is_sandbox)) {
                    if ($this->testmode == true) {
                        $this->view_transaction_url = $sandbox_transaction_url;
                    } else {
                        $this->view_transaction_url = $live_transaction_url;
                    }
                } else {
                    $this->view_transaction_url = $live_transaction_url;
                }
            }
        }
        return parent::get_transaction_url($order);
    }

    public function add_payment_method($order_id = null) {
        $SECFields = array(
            'returnurl' => add_query_arg(array(
                'do_action' => 'update_payment_method',
                'action_name' => 'SetExpressCheckout',
                'method_name' => 'paypal_express',
                'customer_id' => get_current_user_id()
                    ), home_url('/')),
            'cancelurl' => wc_get_account_endpoint_url('add-payment-method'),
            'noshipping' => '1',
        );
        if (AngellEYE_Utility::is_subs_change_payment()) {
            $SECFields['returnurl'] = add_query_arg(array('do_action' => 'change_payment_method', 'order_id' => $order_id, 'action_name' => 'SetExpressCheckout', 'method_name' => 'paypal_express', 'customer_id' => get_current_user_id()), home_url('/'));
        }
        $Payments = array(
            'amt' => '0',
            'currencycode' => get_woocommerce_currency(),
            'paymentaction' => 'AUTHORIZATION',
        );
        $BillingAgreements = array();
        $Item = array(
            'l_billingtype' => 'MerchantInitiatedBilling',
            'l_billingagreementdescription' => 'Billing Agreement',
            'l_paymenttype' => 'Any',
            'l_billingagreementcustom' => ''
        );
        array_push($BillingAgreements, $Item);
        $PayPalRequest = array(
            'SECFields' => $SECFields,
            'BillingAgreements' => $BillingAgreements,
            'Payments' => $Payments
        );
        $result = $this->paypal_express_checkout_token_request_handler($PayPalRequest, 'SetExpressCheckout');
        if (!empty($result['ACK']) && $result['ACK'] == 'Success') {
            return array(
                'result' => 'success',
                'redirect' => $this->PAYPAL_URL . $result['TOKEN']
            );
        } else {
            $redirect_url = wc_get_account_endpoint_url('add-payment-method');
            $this->paypal_express_checkout_error_handler($request_name = 'SetExpressCheckout', $redirect_url, $result);
        }
    }

    public function paypal_express_checkout_token_request_handler($PayPalRequest = array(), $action_name = '') {
        if (!class_exists('Angelleye_PayPal_WC')) {
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.class.php' );
        }
        $PayPalConfig = array(
            'Sandbox' => $this->testmode,
            'APIUsername' => $this->api_username,
            'APIPassword' => $this->api_password,
            'APISignature' => $this->api_signature,
            'Force_tls_one_point_two' => $this->Force_tls_one_point_two
        );
        $PayPal = new Angelleye_PayPal_WC($PayPalConfig);
        if (!empty($PayPalRequest) && !empty($action_name)) {
            if ('SetExpressCheckout' == $action_name) {
                $PayPalResult = $PayPal->SetExpressCheckout(apply_filters('angelleye_woocommerce_express_set_express_checkout_request_args', $PayPalRequest));
                AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($PayPalResult, $methos_name = 'SetExpressCheckout', $gateway = 'PayPal Express Checkout', $this->error_email_notify);
                self::log('Test Mode: ' . $this->testmode);
                self::log('Endpoint: ' . $this->API_Endpoint);
                $PayPalRequest = isset($PayPalResult['RAWREQUEST']) ? $PayPalResult['RAWREQUEST'] : '';
                $PayPalResponse = isset($PayPalResult['RAWRESPONSE']) ? $PayPalResult['RAWRESPONSE'] : '';
                self::log('Request: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalRequest)), true));
                self::log('Response: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalResponse)), true));
                return $PayPalResult;
            }
        }
        if (!empty($_GET['method_name']) && $_GET['method_name'] == 'paypal_express') {
            if ($_GET['action_name'] == 'SetExpressCheckout') {
                $PayPalResult = $PayPal->GetExpressCheckoutDetails(wc_clean($_GET['token']));
                if ($PayPalResult['ACK'] == 'Success') {
                    $billing_result = $PayPal->CreateBillingAgreement(wc_clean($_GET['token']));
                    if ($billing_result['ACK'] == 'Success') {
                        if (!empty($billing_result['BILLINGAGREEMENTID'])) {
                            $billing_agreement_id = $billing_result['BILLINGAGREEMENTID'];
                            $token = new WC_Payment_Token_CC();
                            $customer_id = get_current_user_id();
                            $token->set_token($billing_agreement_id);
                            $token->set_gateway_id($this->id);
                            $token->set_card_type('PayPal Billing Agreement');
                            $token->set_last4(substr($billing_agreement_id, -4));
                            $token->set_expiry_month(date('m'));
                            $token->set_expiry_year(date('Y', strtotime('+20 year')));
                            $token->set_user_id($customer_id);
                            if ($token->validate()) {
                                $save_result = $token->save();
                                wp_redirect(wc_get_account_endpoint_url('payment-methods'));
                                exit();
                            } else {
                                throw new Exception(__('Invalid or missing payment token fields.', 'paypal-for-woocommerce'));
                            }
                        }
                    }
                } else {
                    $redirect_url = wc_get_account_endpoint_url('add-payment-method');
                    $this->paypal_express_checkout_error_handler($request_name = 'GetExpressCheckoutDetails', $redirect_url, $PayPalResult);
                }
            }
        }
    }

    public function paypal_express_checkout_error_handler($request_name = '', $redirect_url = '', $result) {
        $ErrorCode = urldecode($result["L_ERRORCODE0"]);
        $ErrorShortMsg = urldecode($result["L_SHORTMESSAGE0"]);
        $ErrorLongMsg = urldecode($result["L_LONGMESSAGE0"]);
        $ErrorSeverityCode = urldecode($result["L_SEVERITYCODE0"]);
        self::log(__($request_name . 'API call failed. ', 'paypal-for-woocommerce'));
        self::log(__('Detailed Error Message: ', 'paypal-for-woocommerce') . $ErrorLongMsg);
        self::log(__('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg);
        self::log(__('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode);
        self::log(__('Error Severity Code: ', 'paypal-for-woocommerce') . $ErrorSeverityCode);
        $message = '';
        if ($this->error_email_notify) {
            $admin_email = get_option("admin_email");
            $message .= __($request_name . " API call failed.", "paypal-for-woocommerce") . "\n\n";
            $message .= __('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode . "\n";
            $message .= __('Error Severity Code: ', 'paypal-for-woocommerce') . $ErrorSeverityCode . "\n";
            $message .= __('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg . "\n";
            $message .= __('Long Error Message: ', 'paypal-for-woocommerce') . $ErrorLongMsg . "\n";
            $message .= __('User IP: ', 'paypal-for-woocommerce') . WC_Geolocation::get_ip_address() . "\n";
            $error_email_notify_mes = apply_filters('ae_ppec_error_email_message', $message, $ErrorCode, $ErrorSeverityCode, $ErrorShortMsg, $ErrorLongMsg);
            $subject = "PayPal Express Checkout Error Notification";
            $error_email_notify_subject = apply_filters('ae_ppec_error_email_subject', $subject);
            wp_mail($admin_email, $error_email_notify_subject, $error_email_notify_mes);
        }
        if ($this->error_display_type == 'detailed') {
            $sec_error_notice = $ErrorCode . ' - ' . $ErrorLongMsg;
            $error_display_type_message = sprintf(__($sec_error_notice, 'paypal-for-woocommerce'));
        } else {
            $error_display_type_message = sprintf(__('There was a problem paying with PayPal.  Please try another method.', 'paypal-for-woocommerce'));
        }
        $error_display_type_message = apply_filters('ae_ppec_error_user_display_message', $error_display_type_message, $ErrorCode, $ErrorLongMsg);
        if (AngellEYE_Utility::is_cart_contains_subscription() == false) {
            if (function_exists('wc_add_notice')) {
                wc_add_notice($error_display_type_message, 'error');
            }
            if (is_admin()) {
                return false;
            }
            if (!is_ajax()) {
                wp_redirect($redirect_url);
                exit;
            } else {
                return array(
                    'result' => 'fail',
                    'redirect' => $redirect_url
                );
            }
        }
    }

    public static function log($message, $level = 'info', $source = null) {
        if ($source == null) {
            $source = 'paypal_express';
        }
        if (self::$log_enabled) {
            if (version_compare(WC_VERSION, '3.0', '<')) {
                if (empty(self::$log)) {
                    self::$log = new WC_Logger();
                }
                self::$log->add('paypal_express', $message);
            } else {
                if (empty(self::$log)) {
                    self::$log = wc_get_logger();
                }
                self::$log->log($level, $message, array('source' => $source));
            }
        }
    }

    public function angelleye_ec_get_posted_address_data($key, $type = 'billing') {
        if ('billing' === $type || false === $this->posted['ship_to_different_address']) {
            $return = isset($this->posted['billing_' . $key]) ? $this->posted['billing_' . $key] : '';
        } else {
            $return = isset($this->posted['shipping_' . $key]) ? $this->posted['shipping_' . $key] : '';
        }
        if ('email' === $key && empty($return) && is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $return = $current_user->user_email;
        }
        return $return;
    }

    public function free_signup_order_payment($order_id) {
        $order = wc_get_order($order_id);
        $this->log('Processing order #' . $order_id);
        if (!empty($_POST['wc-paypal_express-payment-token']) && $_POST['wc-paypal_express-payment-token'] != 'new') {
            $token_id = wc_clean($_POST['wc-paypal_express-payment-token']);
            $token = WC_Payment_Tokens::get($token_id);
            $order->payment_complete($token->get_token());
            $payment_tokens_id = $token->get_token();
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/express-checkout/class-wc-gateway-paypal-express-request-angelleye.php' );
            $paypal_express_request = new WC_Gateway_PayPal_Express_Request_AngellEYE($this);
            $paypal_express_request->save_payment_token($order, $payment_tokens_id);
            update_post_meta($order_id, '_first_transaction_id', $token->get_token());
            $order->add_order_note('Payment Action: ' . $this->payment_action);
            WC()->cart->empty_cart();
            return array(
                'result' => 'success',
                'redirect' => add_query_arg('utm_nooverride', '1', $this->get_return_url($order))
            );
        }
    }

    /**
     * Process a refund if supported
     * @param  int $order_id
     * @param  float $amount
     * @param  string $reason
     * @return  bool|wp_error True or false based on success, or a WP_Error object
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/express-checkout/class-wc-gateway-paypal-express-request-angelleye.php' );
        $paypal_express_request = new WC_Gateway_PayPal_Express_Request_AngellEYE($this);
        if (apply_filters('angelleye_is_express_checkout_parallel_payment_not_used', true, $order_id)) {
            $response = $paypal_express_request->angelleye_process_refund($order_id, $amount, $reason);
            if (is_wp_error($response)) {
                self::log('Refund Error: ' . $response->get_error_message());
                throw new Exception($response->get_error_message());
            }
            if ($response == true) {
                return true;
            }
        } else {
            return apply_filters('angelleye_is_express_checkout_parallel_payment_handle', true, $order_id, $this);
        }
    }

    public function is_subscription($order_id) {
        return ( function_exists('wcs_order_contains_subscription') && ( wcs_order_contains_subscription($order_id) || wcs_is_subscription($order_id) || wcs_order_contains_renewal($order_id) ) );
    }

    public function angelleye_check_cart_items() {
        try {
            WC()->cart->check_cart_items();
        } catch (Exception $ex) {
            
        }
        if (wc_notice_count('error') > 0) {
            self::log(print_r(wc_get_notices(), true));
            $redirect_url = wc_get_cart_url();
            wp_redirect($redirect_url);
            exit();
        }
    }

    public function angelleye_reload_gateway_credentials_for_woo_subscription_renewal_order($order) {
        if ($this->testmode == false) {
            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            if ($this->is_subscription($order_id)) {
                foreach ($order->get_items() as $cart_item_key => $values) {
                    $product = version_compare(WC_VERSION, '3.0', '<') ? $order->get_product_from_item($values) : $values->get_product();
                    $product_id = $product->get_id();
                    if (!empty($product_id)) {
                        $product_type = get_post_type($product_id);
                        if ($product_type == 'product_variation') {
                            $product_id = wp_get_post_parent_id($product_id);
                        }
                        $_enable_sandbox_mode = get_post_meta($product_id, '_enable_sandbox_mode', true);
                        if ($_enable_sandbox_mode == 'yes') {
                            $this->testmode = true;
                            $this->API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
                            $this->PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
                            $this->api_username = $this->get_option('sandbox_api_username');
                            $this->api_password = $this->get_option('sandbox_api_password');
                            $this->api_signature = $this->get_option('sandbox_api_signature');
                        }
                    }
                }
            }
        }
    }

    public function angelleye_paypal_express_checkout_redirect_to_paypal($data, $errors = null) {
        $notice_count = 0;
        if (!empty($errors)) {
            foreach ($errors->get_error_messages() as $message) {
                $notice_count = $notice_count + 1;
                if (isset($_POST['from_checkout']) && 'yes' === $_POST['from_checkout']) {
                    wc_add_notice($message, 'error');
                }
            }
            if ($notice_count > 0) {
                if (isset($_POST['from_checkout']) && 'yes' === $_POST['from_checkout']) {
                    wp_send_json(array(
                        'url' => wc_get_checkout_url()
                    ));
                    exit();
                }
            }
        } else {
            $notice_count = wc_notice_count('error');
        }
        require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/express-checkout/class-wc-gateway-paypal-express-request-angelleye.php' );
        $paypal_express_request = new WC_Gateway_PayPal_Express_Request_AngellEYE($this);
        if (empty($_POST['woocommerce_checkout_update_totals']) && 0 === $notice_count) {
            try {
                angelleye_set_session('post_data', wp_slash($_POST));
                angelleye_set_session('validate_data', $data);
                if (isset($_POST['from_checkout']) && 'yes' === $_POST['from_checkout']) {
                    if ((isset($_POST['wc-paypal_express-new-payment-method']) && $_POST['wc-paypal_express-new-payment-method'] == 'true') || ( isset($_GET['ec_save_to_account']) && $_GET['ec_save_to_account'] == true)) {
                        angelleye_set_session('ec_save_to_account', 'on');
                    } else {
                        unset(WC()->session->ec_save_to_account);
                    }
                    unset($_POST['from_checkout']);
                    $paypal_express_request->angelleye_set_express_checkout();
                }
                if (isset($_POST['payment_method']) && 'paypal_express' === $_POST['payment_method'] && $this->function_helper->ec_notice_count('error') == 0) {
                    $this->function_helper->ec_redirect_after_checkout();
                }
            } catch (Exception $ex) {
                
            }
        }
    }

    public function init_settings() {
        parent::init_settings();
        $this->enabled = !empty($this->settings['enabled']) && 'yes' === $this->settings['enabled'] ? 'yes' : 'no';
        $this->send_items_value = !empty($this->settings['send_items']) && 'yes' === $this->settings['send_items'] ? 'yes' : 'no';
        $this->send_items = 'yes' === $this->send_items_value;
    }

    public function subscription_change_payment($order_id) {
        if (isset($_POST['wc-paypal_express-payment-token']) && 'new' !== $_POST['wc-paypal_express-payment-token']) {
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/express-checkout/class-wc-gateway-paypal-express-request-angelleye.php' );
            $order = wc_get_order($order_id);
            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            $token_id = wc_clean($_POST['wc-paypal_express-payment-token']);
            $token = WC_Payment_Tokens::get($token_id);
            do_action('angelleye_set_multi_account', $token_id, $order_id);
            $this->angelleye_reload_gateway_credentials_for_woo_subscription_renewal_order($order);
            if ($token->get_user_id() !== get_current_user_id()) {
                throw new Exception(__('Error processing checkout. Please try again.', 'paypal-for-woocommerce'));
            } else {
                update_post_meta($order_id, 'is_sandbox', $this->sandbox);
                $payment_tokens_id = $token->get_token();
                $paypal_express_request = new WC_Gateway_PayPal_Express_Request_AngellEYE($this);
                $paypal_express_request->save_payment_token($order, $payment_tokens_id);
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            }
        } else {
            $result = $this->add_payment_method($order_id);
            if (!is_ajax()) {
                wp_redirect($result['redirect']);
                exit;
            } else {
                return array(
                    'result' => 'success',
                    'redirect' => $result['redirect']
                );
            }
        }
    }

    public function paypal_express_checkout_change_payment_method() {
        if (!class_exists('Angelleye_PayPal_WC')) {
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.class.php' );
        }
        $PayPalConfig = array(
            'Sandbox' => $this->testmode,
            'APIUsername' => $this->api_username,
            'APIPassword' => $this->api_password,
            'APISignature' => $this->api_signature,
            'Force_tls_one_point_two' => $this->Force_tls_one_point_two
        );
        $PayPal = new Angelleye_PayPal_WC($PayPalConfig);
        $order_id = absint(wp_unslash($_GET['order_id']));
        $order = wc_get_order($order_id);
        $this->angelleye_reload_gateway_credentials_for_woo_subscription_renewal_order($order);
        require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/express-checkout/class-wc-gateway-paypal-express-request-angelleye.php' );
        $paypal_express_request = new WC_Gateway_PayPal_Express_Request_AngellEYE($this);
        if (!empty($_GET['method_name']) && $_GET['method_name'] == 'paypal_express') {
            if ($_GET['action_name'] == 'SetExpressCheckout') {
                $PayPalResult = $PayPal->GetExpressCheckoutDetails(wc_clean($_GET['token']));
                if ($PayPalResult['ACK'] == 'Success') {
                    $billing_result = $PayPal->CreateBillingAgreement(wc_clean($_GET['token']));
                    if ($billing_result['ACK'] == 'Success') {
                        if (!empty($billing_result['BILLINGAGREEMENTID'])) {
                            $billing_agreement_id = $billing_result['BILLINGAGREEMENTID'];
                            $token = new WC_Payment_Token_CC();
                            if (0 != $order->get_user_id()) {
                                $customer_id = $order->get_user_id();
                            } else {
                                $customer_id = get_current_user_id();
                            }
                            $token->set_token($billing_agreement_id);
                            $token->set_gateway_id($this->id);
                            $token->set_card_type('PayPal Billing Agreement');
                            $token->set_last4(substr($billing_agreement_id, -4));
                            $token->set_expiry_month(date('m'));
                            $token->set_expiry_year(date('Y', strtotime('+20 year')));
                            $token->set_user_id($customer_id);
                            $paypal_express_request->save_payment_token($order, $billing_agreement_id);
                            if ($token->validate()) {
                                $save_result = $token->save();
                                $_multi_account_api_username = get_post_meta($order_id, '_multi_account_api_username', true);
                                if (!empty($_multi_account_api_username)) {
                                    add_metadata('payment_token', $save_result, '_multi_account_api_username', $_multi_account_api_username);
                                }
                                wc_add_notice(__('Payment method updated.', 'paypal-for-woocommerce'), 'success');
                                if (!is_ajax()) {
                                    wp_redirect(wc_get_account_endpoint_url('payment-methods'));
                                    exit;
                                } else {
                                    return array(
                                        'result' => 'success',
                                        'redirect' => $this->get_return_url($order)
                                    );
                                }
                            } else {
                                throw new Exception(__('Invalid or missing payment token fields.', 'paypal-for-woocommerce'));
                            }
                        }
                    }
                } else {
                    $redirect_url = wc_get_account_endpoint_url('add-payment-method');
                    $this->paypal_express_checkout_error_handler($request_name = 'GetExpressCheckoutDetails', $redirect_url, $PayPalResult);
                }
            }
        }
    }

    public function angelleye_get_merchant_id() {
        require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/angelleye-includes/express-checkout/class-wc-gateway-paypal-express-request-angelleye.php' );
        $paypal_express_request = new WC_Gateway_PayPal_Express_Request_AngellEYE($this);
        $paypal_express_request->angelleye_get_paldetails($this);
    }
    
    public function get_saved_payment_method_option_html( $token ) {
        $html = sprintf(
                '<li class="woocommerce-SavedPaymentMethods-token">
                        <input id="wc-%1$s-payment-token-%2$s" type="radio" name="wc-%1$s-payment-token" value="%2$s" style="width:auto;" class="woocommerce-SavedPaymentMethods-tokenInput" %4$s />
                        <label for="wc-%1$s-payment-token-%2$s">%3$s</label>
                </li>',
                esc_attr( $this->id ),
                esc_attr( $token->get_id() ),
                esc_html( $this->angelleye_get_display_name($token) ),
                checked( $token->is_default(), true, false )
        );
        return apply_filters( 'woocommerce_payment_gateway_get_saved_payment_method_option_html', $html, $token, $this );
    }
    
    public function angelleye_get_display_name( $token ) {
        $display = sprintf(
                __( '%1$s ending in %2$s', 'paypal-for-woocommerce' ),
                $token->get_card_type(),
                $token->get_last4()
        );
        return $display;
    }

}
