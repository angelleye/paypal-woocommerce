<?php

/**
 * WC_Gateway_Braintree_AngellEYE class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_Braintree_AngellEYE extends WC_Payment_Gateway_CC {

    /**
     * Constructor
     */
    public $customer_id;

    function __construct() {
        $this->id = 'braintree';
        $this->icon = $this->get_option('card_icon', plugins_url('/assets/images/cards.png', plugin_basename(dirname(__FILE__))));
        if ( is_ssl() || get_option( 'woocommerce_force_ssl_checkout' ) == 'yes' ) {
            $this->icon = preg_replace("/^http:/i", "https:", $this->icon);
        }
        $this->icon = apply_filters('woocommerce_braintree_icon', $this->icon);
        $this->has_fields = true;
        $this->method_title = 'Braintree';
        $this->method_description = __('Credit Card payments Powered by PayPal / Braintree.', 'paypal-for-woocommerce');
        $this->supports = array(
            'subscriptions',
            'products',
            'refunds',
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
        $this->init_form_fields();
        $this->init_settings();
        $this->enable_tokenized_payments = $this->get_option('enable_tokenized_payments', 'no');
        if ($this->enable_tokenized_payments == 'yes') {
            $this->supports = array_merge($this->supports, array('add_payment_method', 'tokenization'));
        }
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->sandbox = 'yes' === $this->get_option('sandbox', 'yes');
        if ($this->sandbox == false) {
            $this->sandbox = AngellEYE_Utility::angelleye_paypal_for_woocommerce_is_set_sandbox_product();
        }
        $this->merchant_account_id = '';
        $this->environment = $this->sandbox == false ? 'production' : 'sandbox';
        $this->merchant_id = $this->sandbox == false ? $this->get_option('merchant_id') : $this->get_option('sandbox_merchant_id');
        $this->private_key = $this->sandbox == false ? $this->get_option('private_key') : $this->get_option('sandbox_private_key');
        $this->public_key = $this->sandbox == false ? $this->get_option('public_key') : $this->get_option('sandbox_public_key');
        $this->enable_braintree_drop_in = $this->get_option('enable_braintree_drop_in') === "yes" ? true : false;
        $this->debug = 'yes' === $this->get_option('debug', 'no');
        $this->is_encrypt = $this->get_option('is_encrypt', 'no');
        $this->softdescriptor_value = $this->get_option('softdescriptor', '');
        $this->softdescriptor = $this->get_softdescriptor();
        $this->fraud_tool = $this->get_option('fraud_tool', 'basic');
        $this->kount_merchant_id = ($this->fraud_tool == 'kount_custom') ? $this->get_option('kount_merchant_id') : '';
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_filter('woocommerce_settings_api_sanitized_fields_' . $this->id, array($this, 'angelleye_braintree_encrypt_gateway_api'), 10, 1);
        $this->response = '';
        if ($this->enable_braintree_drop_in) {
            add_action('wp_enqueue_scripts', array($this, 'payment_scripts'), 0);
        }
        add_action('admin_notices', array($this, 'checks'));
        add_filter('woocommerce_credit_card_form_fields', array($this, 'angelleye_braintree_credit_card_form_fields'), 10, 2);
        $this->customer_id;
        if ($this->fraud_tool == 'kount_standard' || $this->fraud_tool == 'kount_custom') {
            add_filter('clean_url', array($this, 'adjust_fraud_script_tag'));
            add_action('wp_print_footer_scripts', array($this, 'render_fraud_js'), 1);
        }

        add_action('woocommerce_admin_order_data_after_order_details', array($this, 'woocommerce_admin_order_data_after_order_details'), 10, 1);
        add_filter('woocommerce_settings_api_sanitized_fields_' . $this->id, array($this, 'angelleye_update_settings'), 10, 1);
        do_action( 'angelleye_paypal_for_woocommerce_multi_account_api_' . $this->id, $this, null, null );
    }

    /**
     * Admin Panel Options
     */
    public function admin_options() {
        ?>
        <h3><?php _e('Braintree', 'paypal-for-woocommerce'); ?></h3>
        <p><?php _e($this->method_description, 'paypal-for-woocommerce'); ?></p>
        <table class="form-table">
            <?php 
            if(version_compare(WC_VERSION,'2.6','<')) {
                AngellEYE_Utility::woo_compatibility_notice();    
            } elseif (version_compare(phpversion(), '5.4.0', '<')) {
                echo '<div class="inline error"><p>' . __('PayPal for WooCommerce requires PHP version 5.4.0 or higher.','paypal-for-woocommerce') . '</p></div>';
            } else {
               $this->generate_settings_html(); 
               $this->angelleye_display_mid_ui();
            }
            ?>
            <script type="text/javascript">
                jQuery('.form-table').on('click', '.js-remove-merchant-account-id', function (e) {
                    e.preventDefault();
                    jQuery(this).closest('tr').delay(50).fadeOut(400, function () {
                        jQuery(this).remove();
                    });
                });
                jQuery('#woocommerce_braintree_sandbox').change(function () {
                    sandbox = jQuery('#woocommerce_braintree_sandbox_public_key, #woocommerce_braintree_sandbox_private_key, #woocommerce_braintree_sandbox_merchant_id').closest('tr'),
                    production = jQuery('#woocommerce_braintree_public_key, #woocommerce_braintree_private_key, #woocommerce_braintree_merchant_id').closest('tr');
                    if (jQuery(this).is(':checked')) {
                        sandbox.show();
                        production.hide();
                    } else {
                        sandbox.hide();
                        production.show();
                    }
                }).change();
                jQuery('.js-add-merchant-account-id').click(function (e) {
                    e.preventDefault();
                    var row_fragment = '<?php echo $this->generate_merchant_account_id_html(); ?>',
                    currency = jQuery('select#wc_braintree_merchant_account_id_currency').val();
                    row_fragment = row_fragment.replace(/{{currency_display}}/g, currency).replace(/{{currency_code}}/g, currency.toLowerCase());
                    if (jQuery('input[name="' + jQuery(row_fragment).find('.js-merchant-account-id-input').attr('name') + '"]').length) {
                        return;
                    }
                    if (jQuery('.js-merchant-account-id-input').length) {
                        jQuery('.js-merchant-account-id-input').closest('tr').last().after(row_fragment);
                    } else {
                        jQuery(this).closest('tr').after(row_fragment);
                    }
                });
                jQuery('select.angelleye-fraud-tool').change(function () {
                    var $kount_id_row = jQuery('.angelleye-kount-merchant-id').closest('tr');
                    if ('kount_custom' === jQuery(this).val()) {
                        $kount_id_row.show();
                    } else {
                        $kount_id_row.hide();
                    }
                }).change();
                jQuery('select#wc_braintree_merchant_account_id_currency').change(function () {
                    jQuery('.js-add-merchant-account-id').text('<?php esc_html_e('Add merchant account ID for ', 'paypal-for-woocommerce'); ?>' + jQuery(this).val())
                });
                jQuery('#woocommerce_braintree_enable_braintree_drop_in').change(function () {
                    var $kount_id_row = jQuery('.angelleye-kount-merchant-id').closest('tr');
                    if (jQuery(this).is(':checked')) {
                        if( jQuery("#woocommerce_braintree_fraud_tool option[value='kount_custom']").length == 0) {
                            jQuery('#woocommerce_braintree_fraud_tool').append(jQuery("<option></option>").attr("value","kount_custom").text("Kount Custom")); 
                        } 
                    } else {
                        jQuery('#woocommerce_braintree_fraud_tool option[value="kount_custom"]').remove();
                        $kount_id_row.hide();
                    }
                }).change();
            </script>
        </table> <?php
    }

    /**
     * Check if SSL is enabled and notify the user
     */
    public function checks() {
        if ($this->enabled == 'no') {
            return;
        }
        if (version_compare(phpversion(), '5.2.1', '<')) {
            echo '<div class="error"><p>' . sprintf(__('Braintree Error: Braintree requires PHP 5.2.1 and above. You are using version %s.', 'paypal-for-woocommerce'), phpversion()) . '</p></div>';
        }
        if ('no' == get_option('woocommerce_force_ssl_checkout') && !class_exists('WordPressHTTPS') && $this->enable_braintree_drop_in == false && $this->sandbox == false) {
            echo '<div class="error"><p>' . sprintf(__('Braintree is enabled, but the <a href="%s">force SSL option</a> is disabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate - Braintree custome credit card UI will only work in sandbox mode.', 'paypal-for-woocommerce'), admin_url('admin.php?page=wc-settings&tab=checkout')) . '</p></div>';
        }
        $this->add_dependencies_admin_notices();
    }

    /**
     * Check if this gateway is enabled
     */
    public function is_available() {
        if ('yes' != $this->enabled) {
            return false;
        }
        if (!$this->merchant_id || !$this->public_key || !$this->private_key) {
            return false;
        }
        return true;
    }

    public function validate_fields() {
        if (!$this->enable_braintree_drop_in) {
            try {
                if (isset($_POST['wc-braintree-payment-token']) && 'new' !== $_POST['wc-braintree-payment-token']) {
                    $token_id = wc_clean($_POST['wc-braintree-payment-token']);
                    $token = WC_Payment_Tokens::get($token_id);
                    if ($token->get_user_id() !== get_current_user_id()) {
                        throw new Exception(__('Error processing checkout. Please try again.', 'paypal-for-woocommerce'));
                    } else {
                        return true;
                    }
                } else {
                    $card = $this->get_posted_card();
                    if (empty($card->exp_month) || empty($card->exp_year)) {
                        throw new Exception(__('Card expiration date is invalid', 'paypal-for-woocommerce'));
                    }
                    if (!ctype_digit($card->cvc)) {
                        throw new Exception(__('Card security code is invalid (only digits are allowed)', 'paypal-for-woocommerce'));
                    }
                    if (!ctype_digit($card->exp_month) || !ctype_digit($card->exp_year) || $card->exp_month > 12 || $card->exp_month < 1 || $card->exp_year < date('y')) {
                        throw new Exception(__('Card expiration date is invalid', 'paypal-for-woocommerce'));
                    }
                    if (empty($card->number) || !ctype_digit($card->number)) {
                        throw new Exception(__('Card number is invalid', 'paypal-for-woocommerce'));
                    }
                    return true;
                }
            } catch (Exception $e) {
                wc_add_notice($e->getMessage(), 'error');
                return false;
            }
            return true;
        } else {
            try {
                if (isset($_POST['braintree_token']) && !empty($_POST['braintree_token'])) {
                    return true;
                } else {
                    throw new Exception(__('Braintree payment method nonce is empty', 'paypal-for-woocommerce'));
                }
            } catch (Exception $e) {
                wc_add_notice($e->getMessage(), 'error');
                return false;
            }
            return true;
        }
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                'label' => __('Enable Braintree Payment Gateway', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'paypal-for-woocommerce'),
                'default' => __('Credit Card', 'paypal-for-woocommerce'),
                'desc_tip' => true
            ),
            'description' => array(
                'title' => __('Description', 'paypal-for-woocommerce'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'paypal-for-woocommerce'),
                'default' => 'Pay securely with your credit card.',
                'desc_tip' => true
            ),
            'enable_braintree_drop_in' => array(
                'title' => __('Enable Drop-in Payment UI', 'paypal-for-woocommerce'),
                'label' => __('Enable Drop-in Payment UI', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Rather than showing a credit card form on your checkout, this shows the form on it\'s own page, thus making the process more secure and more PCI friendly.', 'paypal-for-woocommerce'),
                'default' => 'yes'
            ),
            'sandbox' => array(
                'title' => __('Sandbox', 'paypal-for-woocommerce'),
                'label' => __('Enable Sandbox Mode', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Place the payment gateway in sandbox mode using sandbox API keys (real payments will not be taken).', 'paypal-for-woocommerce'),
                'default' => 'yes'
            ),
            'sandbox_public_key' => array(
                'title' => __('Sandbox Public Key', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => __('Get your API keys from your Braintree account.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'sandbox_private_key' => array(
                'title' => __('Sandbox Private Key', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => __('Get your API keys from your Braintree account.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'sandbox_merchant_id' => array(
                'title' => __('Sandbox Merchant ID', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => __('Get your API keys from your Braintree account.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'public_key' => array(
                'title' => __('Live Public Key', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => __('Get your API keys from your Braintree account.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'private_key' => array(
                'title' => __('Live Private Key', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => __('Get your API keys from your Braintree account.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'merchant_id' => array(
                'title' => __('Live Merchant ID', 'paypal-for-woocommerce'),
                'type' => 'password',
                'description' => __('Get your API keys from your Braintree account.', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true
            ),
            'enable_tokenized_payments' => array(
                'title' => __('Enable Tokenized Payments', 'paypal-for-woocommerce'),
                'label' => __('Enable Tokenized Payments', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Allow buyers to securely save payment details to their account for quick checkout / auto-ship orders in the future.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'class' => 'enable_tokenized_payments'
            ),
            'softdescriptor' => array(
                'title' => __('Credit Card Statement Name', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('The value entered here will be displayed on the buyer\'s credit card statement. Company name/DBA section must be either 3, 7 or 12 characters and the product descriptor can be up to 18, 14, or 9 characters respectively (with an * in between for a total descriptor name of 22 characters).', 'paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
            ),
            'card_icon' => array(
                'title' => __('Card Icon', 'paypal-for-woocommerce'),
                'type' => 'text',
                'default' => plugins_url('/assets/images/cards.png', plugin_basename(dirname(__FILE__))),
                'class' => 'button_upload'
            ),
            'debug' => array(
                'title' => __('Debug Log', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'paypal-for-woocommerce'),
                'default' => 'no',
                'description' => sprintf(__('Log PayPal/Braintree events, inside <code>%s</code>', 'paypal-for-woocommerce'), wc_get_log_file_path('braintree'))
            ),
            'is_encrypt' => array(
                'title' => __('', 'paypal-for-woocommerce'),
                'label' => __('', 'paypal-for-woocommerce'),
                'type' => 'hidden',
                'default' => 'yes',
                'class' => ''
            ),
            'advanced' => array(
                'title' => __('Fraud Settings (Kount)', 'paypal-for-woocommerce'),
                'type' => 'title',
                'description' => 'Advanced Fraud Tools help to identify and prevent fraudulent activity before a transaction or verification ever reaches a customer’s bank. With the help of our partner, Kount, we use hundreds of fraud detection tests – ranging from device fingerprinting to proxy piercing – to analyze each credit card transaction or verification within milliseconds.',
            ),
            'fraud_tool' => array(
                'title' => __('Fraud Tool', 'paypal-for-woocommerce'),
                'type' => 'select',
                'class' => 'angelleye-fraud-tool wc-enhanced-select',
                'default' => 'basic',
                'desc_tip' => __('Select the fraud tool you want to use. Basic is enabled by default and requires no additional configuration. Kount Standard requires you to enable advanced fraud tools in your Braintree control panel. To use Kount Custom you must contact Braintree support.', 'paypal-for-woocommerce'),
                'options' => array(
                    'basic' => __('Basic', 'paypal-for-woocommerce'),
                    'kount_standard' => __('Kount Standard', 'paypal-for-woocommerce'),
                    'kount_custom' => __('Kount Custom', 'paypal-for-woocommerce')
                )
            ),
            'kount_merchant_id' => array(
                'title' => __('Kount Merchant ID', 'paypal-for-woocommerce'),
                'type' => 'text',
                'class' => 'angelleye-kount-merchant-id',
                'desc_tip' => __('Speak with your account management team at Braintree to obtain your Kount Merchant ID.', 'paypal-for-woocommerce'),
            ),
            'merchant_account_id_title' => array(
                'title' => __('Merchant Account IDs', 'paypal-for-woocommerce'),
                'type' => 'title',
                'description' => sprintf(
                        esc_html__('Enter additional merchant account IDs if you do not want to use your Braintree account default. %1$sLearn more about merchant account IDs%2$s', 'paypal-for-woocommerce'), '<a target="_blank" href="https://articles.braintreepayments.com/control-panel/important-gateway-credentials#merchant-account-id-vs.-merchant-id">', '&nbsp;&rarr;</a>'
                ),
            )
        );
    }

    public function payment_fields() {
        global $woocommerce;
        if ($this->description) {
            echo wpautop(wptexturize($this->description));
        }
        $this->tokenization_script();
        if ($this->enable_braintree_drop_in) {
            $this->angelleye_braintree_lib();
            $this->add_log('Begin Braintree_ClientToken::generate Request');
            try {
                if (is_user_logged_in()) {
                    $customer_id = get_current_user_id();
                    $braintree_customer_id = get_user_meta($customer_id, 'braintree_customer_id', true);
                    $this->merchant_account_id = $this->angelleye_braintree_get_merchant_account_id();
                    if (!empty($braintree_customer_id) && !empty($this->merchant_account_id)) {
                        $clientToken = Braintree_ClientToken::generate(array('customerId' => $braintree_customer_id, 'merchantAccountId' => $this->merchant_account_id));
                    } else if (!empty($braintree_customer_id)) {
                        $clientToken = Braintree_ClientToken::generate(array('customerId' => $braintree_customer_id));
                    } else {
                        $clientToken = Braintree_ClientToken::generate();
                    }
                } else {
                    $clientToken = Braintree_ClientToken::generate();
                }
            } catch (Braintree_Exception_Authentication $e) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->add_log("Braintree_ClientToken::generate Exception: API keys are incorrect, Please double-check that you haven't accidentally tried to use your sandbox keys in production or vice-versa.");
                wp_redirect(wc_get_cart_url());
                exit;
            } catch (Braintree_Exception_Authorization $e) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->add_log("Braintree_ClientToken::generate Exception: The API key that you're using is not authorized to perform the attempted action according to the role assigned to the user who owns the API key.");
                wp_redirect(wc_get_cart_url());
                exit;
            } catch (Braintree_Exception_DownForMaintenance $e) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->add_log("Braintree_ClientToken::generate Exception: Request times out.");
                wp_redirect(wc_get_cart_url());
                exit;
            } catch (Braintree_Exception_ServerError $e) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->add_log("Braintree_ClientToken::generate Braintree_Exception_ServerError" . $e->getMessage());
                wp_redirect(wc_get_cart_url());
                exit;
            } catch (Braintree_Exception_SSLCertificate $e) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->add_log("Braintree_ClientToken::generate Braintree_Exception_SSLCertificate" . $e->getMessage());
                wp_redirect(wc_get_cart_url());
                exit;
            } catch (InvalidArgumentException $e) {
                if ($e->getMessage() == 'Customer specified by customer_id does not exist') {
                    if (is_user_logged_in()) {
                        $customer_id = get_current_user_id();
                        delete_user_meta($customer_id, 'braintree_customer_id');
                        $clientToken = Braintree_ClientToken::generate();
                    }
                } else {
                    wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                    $this->add_log("Braintree_ClientToken::generate Braintree_Exception_NotFound" . $e->getMessage());
                    wp_redirect(wc_get_cart_url());
                    exit;
                }
            } catch (Exception $ex) {

                $this->add_log("Braintree_ClientToken::generate Exception:" . $ex->getMessage());
                wp_redirect(wc_get_cart_url());
                exit;
            }
            ?>
            <div id="braintree-cc-form" class="wc-payment-form">
                <fieldset>
                    <div id="braintree-payment-form"></div>
                </fieldset>
            </div>
            <script>
                var $form = jQuery('form.checkout, #order_review');
                var ccForm = jQuery('form.checkout');
                var clientToken = "<?php echo $clientToken; ?>";
                braintree.setup(clientToken, "dropin", {
                    container: "braintree-payment-form",
                    onReady: function () {
                        jQuery.each(jQuery('#braintree-payment-form').children('iFrame'),
                                function (index) {
                                    if (index > 0) {
                                        jQuery(this).remove();
                                    }
                                });
                    },
                    onError: function (a) {
                        if ("VALIDATION" === a.type) {
                            if (is_angelleye_braintree_selected()) {
                                jQuery('.woocommerce-error, .braintree-token', ccForm).remove();
                                ccForm.prepend('<ul class="woocommerce-error"><li>' + a.message + '</li></ul>');
                                return $form.unblock();
                            }
                        } else {
                            jQuery('.woocommerce-error, .braintree-token', ccForm).remove();
                            ccForm.prepend('<ul class="woocommerce-error"><li>' + a.message + '</li></ul>');
                            return $form.unblock();
                        }
                    },
                    onPaymentMethodReceived: function (obj) {
                        braintreeResponseHandler(obj);
                    }
                });

                function is_angelleye_braintree_selected() {
                    if (jQuery('#payment_method_braintree').is(':checked')) {
                        return true;
                    } else {
                        return false;
                    }
                }
                function braintreeResponseHandler(obj) {
                    var $form = jQuery('form.checkout, #order_review, #add_payment_method'),
                     ccForm = jQuery('#braintree-cc-form');
                    if (obj.nonce) {
                        jQuery('.woocommerce-error, .braintree-token', ccForm).remove();
                        if (jQuery('#device_data').length) {
                            ccForm.append('<input type="hidden" id="device_data" name="device_data" value="' + jQuery('#device_data').val() + '"/>');
                        }
                        ccForm.append('<input type="hidden" class="braintree-token" name="braintree_token" value="' + obj.nonce + '"/>');
                        $form.submit();
                    }
                }
                jQuery('form.checkout').on('checkout_place_order_braintree', function () {
                    return braintreeFormHandler();
                });
                function braintreeFormHandler() {
                    if (jQuery('#payment_method_braintree').is(':checked')) {
                        if (0 === jQuery('input.braintree-token').size()) {
                            return false;
                        }
                    }
                    return true;

                }
            </script>
            <?php
        } else {
            parent::payment_fields();
            do_action('payment_fields_saved_payment_methods', $this);
        }
    }

    public function save_payment_method_checkbox() {
        printf(
                '<p class="form-row woocommerce-SavedPaymentMethods-saveNew">
                        <input id="wc-%1$s-new-payment-method" name="wc-%1$s-new-payment-method" type="checkbox" value="true" style="width:auto;" />
                        <label for="wc-%1$s-new-payment-method" style="display:inline;">%2$s</label>
                </p>',
                esc_attr( $this->id ),
                apply_filters( 'cc_form_label_save_to_account', __( 'Save payment method to my account.', 'woocommerce' ), $this->id)
        );
    }
    
    private function get_posted_card() {
        $card_number = isset($_POST['braintree-card-number']) ? wc_clean($_POST['braintree-card-number']) : '';
        $card_cvc = isset($_POST['braintree-card-cvc']) ? wc_clean($_POST['braintree-card-cvc']) : '';
        $card_expiry = isset($_POST['braintree-card-expiry']) ? wc_clean($_POST['braintree-card-expiry']) : '';
        $card_number = str_replace(array(' ', '-'), '', $card_number);
        $card_expiry = array_map('trim', explode('/', $card_expiry));
        $card_exp_month = str_pad($card_expiry[0], 2, "0", STR_PAD_LEFT);
        $card_exp_year = isset($card_expiry[1]) ? $card_expiry[1] : '';
        if (strlen($card_exp_year) == 2) {
            $card_exp_year += 2000;
        }
        return (object) array(
                    'number' => $card_number,
                    'type' => '',
                    'cvc' => $card_cvc,
                    'exp_month' => $card_exp_month,
                    'exp_year' => $card_exp_year,
        );
    }

    /**
     * Process the payment
     */
    public function process_payment($order_id) {
        $order = new WC_Order($order_id);
        $success = $this->angelleye_do_payment($order);
        if ($success == true) {
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        } else {
            WC()->session->set('reload_checkout', true);
            return array(
                'result' => 'fail',
                'redirect' => ''
            );
        }
    }

    public function angelleye_do_payment($order) {
        $success = true;
        global $woocommerce;
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        try {
            if ($this->enable_braintree_drop_in) {
                $payment_method_nonce = self::get_posted_variable('braintree_token');
                if (empty($payment_method_nonce)) {
                    $this->add_log("Error: The payment_method_nonce was unexpectedly empty");
                    wc_add_notice(__('Error: PayPal Powered by Braintree did not supply a payment nonce. Please try again later or use another means of payment.', 'paypal-for-woocommerce'), 'error');
                    return false;
                }
            }

            $request_data = array();
            $this->angelleye_braintree_lib();
            $card = $this->get_posted_card();

            $billing_company = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_company : $order->get_billing_company();
            $billing_first_name = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_first_name : $order->get_billing_first_name();
            $billing_last_name = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_last_name : $order->get_billing_last_name();
            $billing_address_1 = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_address_1 : $order->get_billing_address_1();
            $billing_address_2 = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_address_2 : $order->get_billing_address_2();
            $billing_city = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_city : $order->get_billing_city();
            $billing_postcode = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_postcode : $order->get_billing_postcode();
            $billing_country = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_country : $order->get_billing_country();
            $billing_state = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_state : $order->get_billing_state();

            $request_data['billing'] = array(
                'firstName' => $billing_first_name,
                'lastName' => $billing_last_name,
                'company' => $billing_company,
                'streetAddress' => $billing_address_1,
                'extendedAddress' => $billing_address_2,
                'locality' => $billing_city,
                'region' => $billing_state,
                'postalCode' => $billing_postcode,
                'countryCodeAlpha2' => $billing_country,
            );

            if ($this->fraud_tool == 'kount_standard' || $this->fraud_tool == 'kount_custom') {
                $device_data = self::get_posted_variable('device_data');
                if (!empty($device_data)) {
                    $device_data = wp_unslash($device_data);
                    $request_data['deviceData'] = $device_data;
                }
            }

            $request_data['shipping'] = array(
                'firstName' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_first_name : $order->get_shipping_first_name(),
                'lastName' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_last_name : $order->get_shipping_last_name(),
                'company' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_company : $order->get_shipping_company(),
                'streetAddress' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_address_1 : $order->get_shipping_address_1(),
                'extendedAddress' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_address_2 : $order->get_shipping_address_2(),
                'locality' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_city : $order->get_shipping_city(),
                'region' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_state : $order->get_shipping_state(),
                'postalCode' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_postcode : $order->get_shipping_postcode(),
                'countryCodeAlpha2' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_country : $order->get_shipping_country(),
            );
            if ($this->enable_braintree_drop_in == false) {
                if ((!empty($_POST['wc-braintree-payment-token']) && $_POST['wc-braintree-payment-token'] == 'new') || empty($_POST['wc-braintree-payment-token'])) {
                    $billing_first_name = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_first_name : $order->get_billing_first_name();
                    $billing_last_name = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_last_name : $order->get_billing_last_name();
                    $request_data['creditCard'] = array(
                        'number' => $card->number,
                        'expirationDate' => $card->exp_month . '/' . $card->exp_year,
                        'cvv' => $card->cvc,
                        'cardholderName' => $billing_first_name . ' ' . $billing_last_name
                    );
                } else if (is_user_logged_in() && (!empty($_POST['wc-braintree-payment-token']) && $_POST['wc-braintree-payment-token'] != 'new')) {
                    $customer_id = get_current_user_id();
                    $token_id = wc_clean($_POST['wc-braintree-payment-token']);
                    $token = WC_Payment_Tokens::get($token_id);
                    $braintree_customer_id = get_user_meta($customer_id, 'braintree_customer_id', true);
                    $request_data['paymentMethodToken'] = $token->get_token();
                }
            } else {
                $request_data['paymentMethodNonce'] = $payment_method_nonce;
            }
            if (is_user_logged_in()) {
                $customer_id = get_current_user_id();
                $braintree_customer_id = get_user_meta($customer_id, 'braintree_customer_id', true);
                if (!empty($braintree_customer_id)) {
                    $request_data['customerId'] = $braintree_customer_id;
                } else {
                    $request_data['customer'] = array(
                        'firstName' => version_compare(WC_VERSION, '3.0', '<') ? $order->billing_first_name : $order->get_billing_first_name(),
                        'lastName' => version_compare(WC_VERSION, '3.0', '<') ? $order->billing_last_name : $order->get_billing_last_name(),
                        'company' => version_compare(WC_VERSION, '3.0', '<') ? $order->billing_company : $order->get_billing_company(),
                        'phone' => version_compare(WC_VERSION, '3.0', '<') ? $order->billing_phone : $order->get_billing_phone(),
                        'email' => version_compare(WC_VERSION, '3.0', '<') ? $order->billing_email : $order->get_billing_email(),
                    );
                }
            }
            $request_data['amount'] = number_format($order->get_total(), 2, '.', '');
            $this->merchant_account_id = $this->angelleye_braintree_get_merchant_account_id($order_id);
            if (isset($this->merchant_account_id) && !empty($this->merchant_account_id)) {
                $request_data['merchantAccountId'] = $this->merchant_account_id;
            }
            $request_data['orderId'] = $order->get_order_number();
            $request_data['options'] = $this->get_braintree_options();
            $request_data['channel'] = 'AngellEYEPayPalforWoo_BT';
            if (!empty($this->softdescriptor)) {
                $request_data['descriptor'] = array('name' => $this->softdescriptor);
            }

            if ($this->debug) {
                $this->add_log('Begin Braintree_Transaction::sale request');
                $this->add_log('Order: ' . print_r($order->get_order_number(), true));
                $log = $request_data;
                if ($this->enable_braintree_drop_in == false) {
                    $log['creditCard'] = array(
                        'number' => '**** **** **** ****',
                        'expirationDate' => '**' . '/' . '****',
                        'cvv' => '***'
                    );
                } else {
                    $log['paymentMethodNonce'] = '*********************';
                }
                $this->add_log('Braintree_Transaction::sale Reuest Data ' . print_r($log, true));
            }

            try {
                $this->response = Braintree_Transaction::sale(apply_filters('angelleye_woocommerce_braintree_sale_request_args', $request_data));
            } catch (Braintree_Exception_Authentication $e) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->add_log("Braintree_Transaction::sale Braintree_Exception_Authentication: API keys are incorrect, Please double-check that you haven't accidentally tried to use your sandbox keys in production or vice-versa.");
                $order->add_order_note("Braintree_Transaction::sale Braintree_Exception_Authentication: API keys are incorrect, Please double-check that you haven't accidentally tried to use your sandbox keys in production or vice-versa.");
                return $success = false;
            } catch (Braintree_Exception_Authorization $e) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->add_log("Braintree_Transaction::sale Braintree_Exception_Authorization: The API key that you're using is not authorized to perform the attempted action according to the role assigned to the user who owns the API key.");
                $order->add_order_note("Braintree_Transaction::sale Braintree_Exception_Authorization: The API key that you're using is not authorized to perform the attempted action according to the role assigned to the user who owns the API key.");
                return $success = false;
            } catch (Braintree_Exception_DownForMaintenance $e) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->add_log("Braintree_Transaction::sale Braintree_Exception_DownForMaintenance: Request times out.");
                $order->add_order_note("Braintree_Transaction::sale Braintree_Exception_DownForMaintenance: Request times out.");
                return $success = false;
            } catch (Braintree_Exception_ServerError $e) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->add_log("Braintree_Transaction::sale Braintree_Exception_ServerError " . $e->getMessage());
                $order->add_order_note("Braintree_Transaction::sale Braintree_Exception_ServerError " . $e->getMessage());
                return $success = false;
            } catch (Braintree_Exception_SSLCertificate $e) {
                wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
                $this->add_log("Braintree_Transaction::sale Braintree_Exception_SSLCertificate " . $e->getMessage());
                $order->add_order_note("Braintree_Transaction::sale Braintree_Exception_SSLCertificate " . $e->getMessage());
                return $success = false;
            } catch (Exception $e) {
                wc_add_notice(__('Error: PayPal Powered by Braintree was unable to complete the transaction. Please try again later or use another means of payment.', 'paypal-for-woocommerce'), 'error');
                $this->add_log('Error: Unable to complete transaction. Reason: ' . $e->getMessage());
                $order->add_order_note("Error: Unable to complete transaction. Reason: " . $e->getMessage());
                return $success = false;
            }

            if (!$this->response->success) {
                if ($this->has_risk_data()) {
                    $order->add_order_note(sprintf(__('Risk decision for this transaction %s, Risk ID for this transaction: %s', 'paypal-for-woocommerce'), $this->get_risk_decision(), $this->get_risk_id()));
                    update_post_meta($order_id, 'risk_id', $this->get_risk_id());
                    update_post_meta($order_id, 'risk_decision', $this->get_risk_decision());
                }
                $notice = $this->get_message();
                if( empty($notice) ) {
                    $notice = sprintf(__('Error: PayPal Powered by Braintree was unable to complete the transaction. Please try again later or use another means of payment. Reason: %s', 'paypal-for-woocommerce'), $this->response->message);
                }
                wc_add_notice($notice, 'error');
                $this->add_log("Error: Unable to complete transaction. Reason: {$this->response->message}");
                $order->add_order_note("Error: Unable to complete transaction. Reason: {$this->response->message}");
                $this->add_log('Braintree_Transaction::sale Response code: ' . print_r($this->get_status_code(), true));
                $this->add_log('Braintree_Transaction::sale Response message: ' . print_r($this->get_status_message(), true));
                return $success = false;
            }
            $this->add_log('Braintree_Transaction::sale Response code: ' . print_r($this->get_status_code(), true));
            $this->add_log('Braintree_Transaction::sale Response message: ' . print_r($this->get_status_message(), true));

            $maybe_settled_later = array(
                'settling',
                'settlement_pending',
                'submitted_for_settlement',
            );

            if (in_array($this->response->transaction->status, $maybe_settled_later)) {
                if ($old_wc) {
                    update_post_meta($order_id, 'is_sandbox', $this->sandbox);
                } else {
                    update_post_meta($order->get_id(), 'is_sandbox', $this->sandbox);
                }
                $transaction = Braintree_Transaction::find($this->response->transaction->id);
                $this->save_payment_token($order, $transaction->creditCard['token']);
                do_action('before_save_payment_token', $order_id);
                if ($this->supports('tokenization')) {
                    try {
                        if (!empty($transaction->creditCard) && !empty($transaction->customer['id'])) {
                            if (0 != $order->get_user_id()) {
                                $customer_id = $order->get_user_id();
                            } else {
                                $customer_id = get_current_user_id();
                            }
                            update_user_meta($customer_id, 'braintree_customer_id', $transaction->customer['id']);
                            $payment_method_token = $transaction->creditCard['token'];
                            $wc_existing_token = $this->get_token_by_token($payment_method_token);
                            $paymentMethod = Braintree_PaymentMethod::find($payment_method_token);
                            if ($wc_existing_token == null) {
                                if (!empty($transaction->creditCard['cardType']) && !empty($transaction->creditCard['last4'])) {
                                    $token = new WC_Payment_Token_CC();
                                    $token->set_token($payment_method_token);
                                    $token->set_gateway_id($this->id);
                                    $token->set_card_type($transaction->creditCard['cardType']);
                                    $token->set_last4($transaction->creditCard['last4']);
                                    $token->set_expiry_month($transaction->creditCard['expirationMonth']);
                                    $token->set_expiry_year($transaction->creditCard['expirationYear']);
                                    $token->set_user_id($customer_id);
                                    if( $token->validate() ) {
                                        $save_result = $token->save();
                                        if ($save_result) {
                                            $order->add_payment_token($token);
                                        }
                                    } else {
                                        $order->add_order_note('ERROR MESSAGE: ' .  __( 'Invalid or missing payment token fields.', 'paypal-for-woocommerce' ));
                                    }
                                    
                                } else {
                                    if (!empty($paymentMethod->billingAgreementId)) {
                                        $token = new WC_Payment_Token_CC();
                                        $customer_id = get_current_user_id();
                                        $token->set_token($paymentMethod->billingAgreementId);
                                        $token->set_gateway_id($this->id);
                                        $token->set_card_type('PayPal Billing Agreement');
                                        $token->set_last4(substr($paymentMethod->billingAgreementId, -4));
                                        $token->set_expiry_month(date('m'));
                                        $token->set_expiry_year(date('Y', strtotime('+20 year')));
                                        $token->set_user_id($customer_id);
                                        if( $token->validate() ) {
                                            $save_result = $token->save();
                                            if ($save_result) {
                                                $order->add_payment_token($token);
                                            }
                                        } else {
                                            $order->add_order_note('ERROR MESSAGE: ' .  __( 'Invalid or missing payment token fields.', 'paypal-for-woocommerce' ));
                                        }
                                    }
                                }
                            } else {
                                $order->add_payment_token($wc_existing_token);
                            }
                        }
                    } catch (Braintree_Exception_NotFound $e) {
                        $this->add_log("Braintree_Transaction::find Braintree_Exception_NotFound: " . $e->getMessage());
                        return new WP_Error(404, $e->getMessage());
                    } catch (Exception $ex) {
                        $this->add_log("Braintree_Transaction::find Exception: " . $ex->getMessage());
                        return new WP_Error(404, $ex->getMessage());
                    }
                }
                $order->payment_complete($this->response->transaction->id);
                $order->add_order_note(sprintf(__('%s payment approved! Transaction ID: %s', 'paypal-for-woocommerce'), $this->title, $this->response->transaction->id));
                WC()->cart->empty_cart();
            } else {
                $this->add_log(sprintf('Info: unhandled transaction id = %s, status = %s', $this->response->transaction->id, $this->response->transaction->status));
                $order->update_status('on-hold', sprintf(__('Transaction was submitted to PayPal Braintree but not handled by WooCommerce order, transaction_id: %s, status: %s. Order was put in-hold.', 'paypal-for-woocommerce'), $this->response->transaction->id, $this->response->transaction->status));
                $old_wc = version_compare(WC_VERSION, '3.0', '<');
                $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
                if ($old_wc) {
                    if (!get_post_meta($order_id, '_order_stock_reduced', true)) {
                        $order->reduce_order_stock();
                    }
                } else {
                    wc_maybe_reduce_stock_levels($order_id);
                }
            }
        } catch (Exception $ex) {
            wc_add_notice($ex->getMessage(), 'error');
            return $success = false;
        }
        return $success;
    }

    public function get_braintree_options() {
        return array('submitForSettlement' => true, 'storeInVaultOnSuccess' => 'true');
    }

    public static function multibyte_loaded() {
        return extension_loaded('mbstring');
    }

    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        if (!$order || !$order->get_transaction_id()) {
            return false;
        }

        $this->angelleye_braintree_lib();

        try {
            $transaction = Braintree_Transaction::find($order->get_transaction_id());
        } catch (Braintree_Exception_NotFound $e) {
            $this->add_log("Braintree_Transaction::find Braintree_Exception_NotFound" . $e->getMessage());
            return new WP_Error(404, $e->getMessage());
        } catch (Braintree_Exception_Authentication $e) {
            $this->add_log("Braintree_Transaction::find Braintree_Exception_Authentication: API keys are incorrect, Please double-check that you haven't accidentally tried to use your sandbox keys in production or vice-versa.");
            return new WP_Error(404, $e->getMessage());
        } catch (Braintree_Exception_Authorization $e) {
            $this->add_log("Braintree_Transaction::find Braintree_Exception_Authorization: The API key that you're using is not authorized to perform the attempted action according to the role assigned to the user who owns the API key.");
            return new WP_Error(404, $e->getMessage());
        } catch (Braintree_Exception_DownForMaintenance $e) {
            $this->add_log("Braintree_Transaction::find Braintree_Exception_DownForMaintenance: Request times out.");
            return new WP_Error(404, $e->getMessage());
        } catch (Exception $e) {
            $this->add_log($e->getMessage());
            return new WP_Error(404, $e->getMessage());
        }

        if (isset($transaction->status) && $transaction->status == 'submitted_for_settlement') {
            if ($amount == $order->get_total()) {
                try {
                    $result = Braintree_Transaction::void($order->get_transaction_id());
                    if ($result->success) {
                        $braintree_refunded_id = array();
                        $braintree_refunded_id[$result->transaction->id] = $result->transaction->id;
                        $order->add_order_note(sprintf(__('Refunded %s - Transaction ID: %s', 'paypal-for-woocommerce'), wc_price(number_format($amount, 2, '.', '')), $result->transaction->id));
                        update_post_meta($order_id, 'Refund Transaction ID', $result->transaction->id);   
                        update_post_meta($order_id, 'braintree_refunded_id', $braintree_refunded_id);
                        return true;
                    } else {
                        $error = '';
                        foreach (($result->errors->deepAll()) as $error) {
                            return new WP_Error(404, 'ec_refund-error', $error->message);
                        }
                    }
                } catch (Braintree_Exception_NotFound $e) {
                    $this->add_log("Braintree_Transaction::void Braintree_Exception_NotFound: " . $e->getMessage());
                    return new WP_Error(404, $e->getMessage());
                } catch (Exception $ex) {
                    $this->add_log("Braintree_Transaction::void Exception: " . $e->getMessage());
                    return new WP_Error(404, $e->getMessage());
                }
            } else {
                return new WP_Error(404, __('Oops, you cannot partially void this order. Please use the full order amount.', 'paypal-for-woocommerce'));
            }
        } elseif (isset($transaction->status) && ($transaction->status == 'settled' || $transaction->status == 'settling')) {
            try {
                $result = Braintree_Transaction::refund($order->get_transaction_id(), $amount);
                if ($result->success) {
                    $braintree_refunded_id = array();
                    $braintree_refunded_id[$result->transaction->id] = $result->transaction->id;
                    update_post_meta($order_id, 'braintree_refunded_id', $braintree_refunded_id);
                    $order->add_order_note(sprintf(__('Refunded %s - Transaction ID: %s', 'paypal-for-woocommerce'), wc_price(number_format($amount, 2, '.', '')), $result->transaction->id));
                    return true;
                } else {
                    $error = '';
                    foreach (($result->errors->deepAll()) as $error) {
                        return new WP_Error(404, 'ec_refund-error', $error->message);
                    }
                }
            } catch (Braintree_Exception_NotFound $e) {
                $this->add_log("Braintree_Transaction::refund Braintree_Exception_NotFound: " . $e->getMessage());
                return new WP_Error(404, $e->getMessage());
            } catch (Exception $ex) {
                $this->add_log("Braintree_Transaction::refund Exception: " . $e->getMessage());
                return new WP_Error(404, $e->getMessage());
            }
        } else {
            $this->add_log("Error: The transaction cannot be voided nor refunded in its current state: state = {$transaction->status}");
            return new WP_Error(404, "Error: The transaction cannot be voided nor refunded in its current state: state = {$transaction->status}");
        }
    }

    public function angelleye_braintree_lib() {
        try {
            require_once( 'lib/lib/Braintree.php' );
            Braintree_Configuration::environment($this->environment);
            Braintree_Configuration::merchantId($this->merchant_id);
            Braintree_Configuration::publicKey($this->public_key);
            Braintree_Configuration::privateKey($this->private_key);
        } catch (Exception $ex) {
            $this->add_log('Error: Unable to Load Braintree. Reason: ' . $ex->getMessage());
            WP_Error(404, 'Error: Unable to Load Braintree. Reason: ' . $ex->getMessage());
        }
    }

    public function add_dependencies_admin_notices() {
        $missing_extensions = $this->get_missing_dependencies();
        if (count($missing_extensions) > 0) {
            $message = sprintf(
                    _n(
                            '%s requires the %s PHP extension to function.  Contact your host or server administrator to configure and install the missing extension.', '%s requires the following PHP extensions to function: %s.  Contact your host or server administrator to configure and install the missing extensions.', count($missing_extensions), 'paypal-for-woocommerce'
                    ), "PayPal For WooCoomerce - Braintree", '<strong>' . implode(', ', $missing_extensions) . '</strong>'
            );
            echo '<div class="error"><p>' . $message . '</p></div>';
        }
    }

    public function get_missing_dependencies() {
        $missing_extensions = array();
        foreach ($this->get_dependencies() as $ext) {
            if (!extension_loaded($ext)) {
                $missing_extensions[] = $ext;
            }
        }
        return $missing_extensions;
    }

    public function get_dependencies() {
        return array('curl', 'dom', 'hash', 'openssl', 'SimpleXML', 'xmlwriter');
    }

    public function add_log($message, $level = 'info') {
        if ($this->debug) {
            if (version_compare(WC_VERSION, '3.0', '<')) {
                if (empty($this->log)) {
                    $this->log = new WC_Logger();
                }
                $this->log->add('braintree', $message);
            } else {
                if (empty($this->log)) {
                    $this->log = wc_get_logger();
                }
                $this->log->log($level, $message, array('source' => 'braintree'));
            }
        }
    }

    public function get_status_code() {
        if ($this->response->success) {
            return $this->get_success_status_info('code');
        } else {
            return $this->get_failure_status_info('code');
        }
    }

    public function get_status_message() {
        if ($this->response->success) {
            return $this->get_success_status_info('message');
        } else {
            return $this->get_failure_status_info('message');
        }
    }

    public function get_success_status_info($type) {
        $transaction = !empty($this->response->transaction) ? $this->response->transaction : $this->response->creditCardVerification;
        if (isset($transaction->processorSettlementResponseCode) && !empty($transaction->processorSettlementResponseCode)) {
            $status = array(
                'code' => $transaction->processorSettlementResponseCode,
                'message' => $transaction->processorSettlementResponseText,
            );
        } else {
            $status = array(
                'code' => $transaction->processorResponseCode,
                'message' => $transaction->processorResponseText,
            );
        }
        return isset($status[$type]) ? $status[$type] : null;
    }

    public function get_failure_status_info($type) {
        if ($this->has_validation_errors()) {
            $errors = $this->get_validation_errors();
            return implode(', ', ( 'code' === $type ? array_keys($errors) : array_values($errors)));
        }
        $transaction = !empty($this->response->transaction) ? $this->response->transaction : $this->response->creditCardVerification;
        switch ($transaction->status) {
            case 'gateway_rejected':
                $status = array(
                    'code' => $transaction->gatewayRejectionReason,
                    'message' => $this->response->message,
                );
                break;
            case 'processor_declined':
                $status = array(
                    'code' => $transaction->processorResponseCode,
                    'message' => $transaction->processorResponseText . (!empty($transaction->additionalProcessorResponse) ? ' (' . $transaction->additionalProcessorResponse . ')' : '' ),
                );
                break;
            case 'settlement_declined':
                $status = array(
                    'code' => $transaction->processorSettlementResponseCode,
                    'message' => $transaction->processorSettlementResponseText,
                );
                break;
            default:
                $status = array(
                    'code' => $transaction->status,
                    'message' => $this->response->message,
                );
        }
        return isset($status[$type]) ? $status[$type] : null;
    }

    public function has_validation_errors() {
        return isset($this->response->errors) && $this->response->errors->deepSize();
    }

    public function get_validation_errors() {
        $errors = array();
        if ($this->has_validation_errors()) {
            foreach ($this->response->errors->deepAll() as $error) {
                $errors[$error->code] = $error->message;
            }
        }
        return $errors;
    }

    public function get_user_message($message_id) {
        $message = null;
        switch ($message_id) {
            case 'error': $message = __('An error occurred, please try again or try an alternate form of payment', 'paypal-for-woocommerce');
                break;
            case 'decline': $message = __('We cannot process your order with the payment information that you provided. Please use a different payment account or an alternate payment method.', 'paypal-for-woocommerce');
                break;
            case 'held_for_review': $message = __('This order is being placed on hold for review. Please contact us to complete the transaction.', 'paypal-for-woocommerce');
                break;
            case 'held_for_incorrect_csc': $message = __('This order is being placed on hold for review due to an incorrect card verification number.  You may contact the store to complete the transaction.', 'paypal-for-woocommerce');
                break;
            case 'csc_invalid': $message = __('The card verification number is invalid, please try again.', 'paypal-for-woocommerce');
                break;
            case 'csc_missing': $message = __('Please enter your card verification number and try again.', 'paypal-for-woocommerce');
                break;
            case 'card_type_not_accepted': $message = __('That card type is not accepted, please use an alternate card or other form of payment.', 'paypal-for-woocommerce');
                break;
            case 'card_type_invalid': $message = __('The card type is invalid or does not correlate with the credit card number.  Please try again or use an alternate card or other form of payment.', 'paypal-for-woocommerce');
                break;
            case 'card_type_missing': $message = __('Please select the card type and try again.', 'paypal-for-woocommerce');
                break;
            case 'card_number_type_invalid': $message = __('The card type is invalid or does not correlate with the credit card number.  Please try again or use an alternate card or other form of payment.', 'paypal-for-woocommerce');
                break;
            case 'card_number_invalid': $message = __('The card number is invalid, please re-enter and try again.', 'paypal-for-woocommerce');
                break;
            case 'card_number_missing': $message = __('Please enter your card number and try again.', 'paypal-for-woocommerce');
                break;
            case 'card_expiry_invalid': $message = __('The card expiration date is invalid, please re-enter and try again.', 'paypal-for-woocommerce');
                break;
            case 'card_expiry_month_invalid': $message = __('The card expiration month is invalid, please re-enter and try again.', 'paypal-for-woocommerce');
                break;
            case 'card_expiry_year_invalid': $message = __('The card expiration year is invalid, please re-enter and try again.', 'paypal-for-woocommerce');
                break;
            case 'card_expiry_missing': $message = __('Please enter your card expiration date and try again.', 'paypal-for-woocommerce');
                break;
            case 'bank_aba_invalid': $message_id = __('The bank routing number is invalid, please re-enter and try again.', 'paypal-for-woocommerce');
                break;
            case 'bank_account_number_invalid': $message_id = __('The bank account number is invalid, please re-enter and try again.', 'paypal-for-woocommerce');
                break;
            case 'card_expired': $message = __('The provided card is expired, please use an alternate card or other form of payment.', 'paypal-for-woocommerce');
                break;
            case 'card_declined': $message = __('The provided card was declined, please use an alternate card or other form of payment.', 'paypal-for-woocommerce');
                break;
            case 'insufficient_funds': $message = __('Insufficient funds in account, please use an alternate card or other form of payment.', 'paypal-for-woocommerce');
                break;
            case 'card_inactive': $message = __('The card is inactivate or not authorized for card-not-present transactions, please use an alternate card or other form of payment.', 'paypal-for-woocommerce');
                break;
            case 'credit_limit_reached': $message = __('The credit limit for the card has been reached, please use an alternate card or other form of payment.', 'paypal-for-woocommerce');
                break;
            case 'csc_mismatch': $message = __('The card verification number does not match. Please re-enter and try again.', 'paypal-for-woocommerce');
                break;
            case 'avs_mismatch': $message = __('The provided address does not match the billing address for cardholder. Please verify the address and try again.', 'paypal-for-woocommerce');
                break;
        }
        return apply_filters('wc_payment_gateway_transaction_response_user_message', $message, $message_id, $this);
    }

    public function get_message() {
        $messages = array();
        $message_id = array();
        $decline_codes = array(
            'cvv' => 'csc_mismatch',
            'avs' => 'avs_mismatch',
            '2000' => 'card_declined',
            '2001' => 'insufficient_funds',
            '2002' => 'credit_limit_reached',
            '2003' => 'card_declined',
            '2004' => 'card_expired',
            '2005' => 'card_number_invalid',
            '2006' => 'card_expiry_invalid',
            '2007' => 'card_type_invalid',
            '2008' => 'card_number_invalid',
            '2010' => 'csc_mismatch',
            '2012' => 'card_declined',
            '2013' => 'card_declined',
            '2014' => 'card_declined',
            '2016' => 'error',
            '2017' => 'card_declined',
            '2018' => 'card_declined',
            '2023' => 'card_type_not_accepted',
            '2024' => 'card_type_not_accepted',
            '2038' => 'card_declined',
            '2046' => 'card_declined',
            '2056' => 'credit_limit_reached',
            '2059' => 'avs_mismatch',
            '2060' => 'avs_mismatch',
            '2075' => 'paypal_closed',
        );
        $response_codes = $this->get_validation_errors();
        if (isset($response_codes) && !empty($response_codes) && is_array($response_codes)) {
            foreach ($response_codes as $key => $value) {
                $messages[] = isset($decline_codes[$key]) ? $this->get_user_message($key) : $value;
            }
        } else {
            $code = $this->get_status_code();
            if( !empty($decline_codes[$code]) ) {
                $messages[] = $this->get_user_message($decline_codes[$code]);
            }
        }
        return implode(' ', $messages);
    }

    public function payment_scripts() {
        if (!$this->is_available()) {
            return;
        }
        if ($this->enable_braintree_drop_in) {
            wp_enqueue_script('braintree-gateway', 'https://js.braintreegateway.com/v2/braintree.js', array(), WC_VERSION, false);
        }
        if ($this->fraud_tool == 'kount_standard' || $this->fraud_tool == 'kount_custom') {
            wp_enqueue_script('braintree-data', 'https://js.braintreegateway.com/v1/braintree-data.js', array('braintree-gateway'), WC_VERSION, true);
        }
    }

    public static function get_posted_variable($variable, $default = '') {
        return ( isset($_POST[$variable]) ? $_POST[$variable] : $default );
    }

    function get_transaction_url($order) {
        $transaction_id = $order->get_transaction_id();
        if (empty($transaction_id)) {
            return false;
        }
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        $is_sandbox = $old_wc ? get_post_meta($order->id, 'is_sandbox', true) : get_post_meta($order->get_id(), 'is_sandbox', true);
        if ($is_sandbox == true) {
            $server = "sandbox.braintreegateway.com";
        } else {
            if (empty($is_sandbox)) {
                if ($this->sandbox == true) {
                    $server = "sandbox.braintreegateway.com";
                } else {
                    $server = "braintreegateway.com";
                }
            } else {
                $server = "braintreegateway.com";
            }
        }
        return "https://" . $server . "/merchants/" . urlencode($this->merchant_id) . "/transactions/" . urlencode($transaction_id);
    }

    public function field_name($name) {
        return ' name="' . esc_attr($this->id . '-' . $name) . '" ';
    }

    public function angelleye_braintree_credit_card_form_fields($default_fields, $current_gateway_id) {
        if ($current_gateway_id == $this->id) {
            $fields = array(
                'card-number-field' => '<p class="form-row form-row-wide">
                        <label for="' . esc_attr($this->id) . '-card-number">' . apply_filters( 'cc_form_label_card_number', __('Card number', 'paypal-for-woocommerce'), $this->id) . ' <span class="required">*</span></label>
                        <input id="' . esc_attr($this->id) . '-card-number" class="input-text wc-credit-card-form-card-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ' . $this->field_name('card-number') . ' />
                    </p>',
                'card-expiry-field' => '<p class="form-row form-row-first">
                        <label for="' . esc_attr($this->id) . '-card-expiry">' . apply_filters( 'cc_form_label_expiry', __('Expiry (MM/YY)', 'paypal-for-woocommerce'), $this->id) . ' <span class="required">*</span></label>
                        <input id="' . esc_attr($this->id) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" inputmode="numeric" autocomplete="cc-exp" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="' . esc_attr__('MM / YY', 'paypal-for-woocommerce') . '" ' . $this->field_name('card-expiry') . ' />
                    </p>',
                '<p class="form-row form-row-last">
                        <label for="' . esc_attr($this->id) . '-card-cvc">' . apply_filters( 'cc_form_label_card_code', __('Card code', 'paypal-for-woocommerce'), $this->id) . ' <span class="required">*</span></label>
                        <input id="' . esc_attr($this->id) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="' . esc_attr__('CVC', 'paypal-for-woocommerce') . '" ' . $this->field_name('card-cvc') . ' style="width:100px" />
                    </p>'
            );
            return $fields;
        } else {
            return $default_fields;
        }
    }

    public function get_token_by_token($token_id, $token_result = null) {
        global $wpdb;
        if (is_null($token_result)) {
            $token_result = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE token = %s", $token_id
            ));
            if (empty($token_result)) {
                return null;
            }
        }
        $token_class = 'WC_Payment_Token_' . $token_result->type;
        if (class_exists($token_class)) {
            $meta = get_metadata('payment_token', $token_result->token_id);
            $passed_meta = array();
            if (!empty($meta)) {
                foreach ($meta as $meta_key => $meta_value) {
                    $passed_meta[$meta_key] = $meta_value[0];
                }
            }
            return new $token_class($token_result->token_id, (array) $token_result, $passed_meta);
        }
        return null;
    }

    public function add_payment_method($zero_amount_payment = false) {
        $this->validate_fields();
        $this->angelleye_braintree_lib();
        $customer_id = get_current_user_id();
        $braintree_customer_id = get_user_meta($customer_id, 'braintree_customer_id', true);
        if (!empty($braintree_customer_id)) {
            $result = $this->braintree_create_payment_method($braintree_customer_id, $zero_amount_payment);
            if ($result->success == true) {
                $return = $this->braintree_save_payment_method($customer_id, $result, $zero_amount_payment);
                return $return;
            }
        } else {
            $braintree_customer_id = $this->braintree_create_customer($customer_id);
            if (!empty($braintree_customer_id)) {
                $result = $this->braintree_create_payment_method($braintree_customer_id, $zero_amount_payment);
                if ($result->success == true) {
                    $return = $this->braintree_save_payment_method($customer_id, $result, $zero_amount_payment);
                    return $return;
                }
            }
        }
    }

    public function braintree_create_payment_method($braintree_customer_id, $zero_amount_payment = false) {
        if ($this->enable_braintree_drop_in) {
            $payment_method_nonce = self::get_posted_variable('braintree_token');
            if (!empty($payment_method_nonce)) {
                $payment_method_request = array('customerId' => $braintree_customer_id, 'paymentMethodNonce' => $payment_method_nonce, 'options' => array('failOnDuplicatePaymentMethod' => true));
                $this->merchant_account_id = $this->angelleye_braintree_get_merchant_account_id();
                if (isset($this->merchant_account_id) && !empty($this->merchant_account_id)) {
                    $payment_method_request['options']['verificationMerchantAccountId'] = $this->merchant_account_id;
                }
            } else {
                $this->add_log("Error: The payment_method_nonce was unexpectedly empty");
                wc_add_notice(__('Error: PayPal Powered by Braintree did not supply a payment nonce. Please try again later or use another means of payment.', 'paypal-for-woocommerce'), 'error');
                return false;
            }
        } else {
            $card = $this->get_posted_card();
            $payment_method_request = array('customerId' => $braintree_customer_id, 'cvv' => $card->cvc, 'expirationDate' => $card->exp_month . '/' . $card->exp_year, 'number' => $card->number);
            $this->merchant_account_id = $this->angelleye_braintree_get_merchant_account_id();
            if (isset($this->merchant_account_id) && !empty($this->merchant_account_id)) {
                $payment_method_request['options']['verificationMerchantAccountId'] = $this->merchant_account_id;
            }
        }
        try {
            if ($this->enable_braintree_drop_in) {
                $result = Braintree_PaymentMethod::create($payment_method_request);
            } else {
                $result = Braintree_CreditCard::create($payment_method_request);
            }
            return $result;
        } catch (Braintree_Exception_Authentication $e) {
            wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
            $this->add_log("Braintree_ClientToken::generate Exception: API keys are incorrect, Please double-check that you haven't accidentally tried to use your sandbox keys in production or vice-versa.");
            if ($zero_amount_payment == false) {
                wp_redirect(wc_get_account_endpoint_url('payment-methods'));
                exit;
            } else {
                return false;
            }
        } catch (Braintree_Exception_Authorization $e) {
            wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
            $this->add_log("Braintree_ClientToken::generate Exception: The API key that you're using is not authorized to perform the attempted action according to the role assigned to the user who owns the API key.");
            if ($zero_amount_payment == false) {
                wp_redirect(wc_get_account_endpoint_url('payment-methods'));
                exit;
            } else {
                return false;
            }
        } catch (Braintree_Exception_DownForMaintenance $e) {
            wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
            $this->add_log("Braintree_ClientToken::generate Exception: Request times out.");
            if ($zero_amount_payment == false) {
                wp_redirect(wc_get_account_endpoint_url('payment-methods'));
                exit;
            } else {
                return false;
            }
        } catch (Braintree_Exception_ServerError $e) {
            wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
            $this->add_log("Braintree_ClientToken::generate Braintree_Exception_ServerError" . $e->getMessage());
            if ($zero_amount_payment == false) {
                wp_redirect(wc_get_account_endpoint_url('payment-methods'));
                exit;
            } else {
                return false;
            }
        } catch (Braintree_Exception_SSLCertificate $e) {
            wc_add_notice(__("Error processing checkout. Please try again. ", 'paypal-for-woocommerce'), 'error');
            $this->add_log("Braintree_ClientToken::generate Braintree_Exception_SSLCertificate" . $e->getMessage());
            if ($zero_amount_payment == false) {
                wp_redirect(wc_get_account_endpoint_url('payment-methods'));
                exit;
            } else {
                return false;
            }
        } catch (Exception $ex) {
            $this->add_log("Braintree_ClientToken::generate Exception:" . $ex->getMessage());
            if ($zero_amount_payment == false) {
                wp_redirect(wc_get_account_endpoint_url('payment-methods'));
                exit;
            } else {
                return false;
            }
        }
    }

    public function braintree_save_payment_method($customer_id, $result) {
        if (!empty($result->paymentMethod)) {
            $braintree_method = $result->paymentMethod;
        } elseif ($result->creditCard) {
            $braintree_method = $result->creditCard;
        } else {
            wp_redirect(wc_get_account_endpoint_url('payment-methods'));
            exit;
        }
        update_user_meta($customer_id, 'braintree_customer_id', $braintree_method->customerId);
        $payment_method_token = $braintree_method->token;
        $wc_existing_token = $this->get_token_by_token($payment_method_token);
        if ($wc_existing_token == null) {
            $token = new WC_Payment_Token_CC();
            
            $token->set_token($payment_method_token);
            $token->set_gateway_id($this->id);
            $token->set_card_type($braintree_method->cardType);
            $token->set_last4($braintree_method->last4);
            $token->set_expiry_month($braintree_method->expirationMonth);
            $token->set_expiry_year($braintree_method->expirationYear);
            $token->set_user_id($customer_id);
            if( $token->validate() ) {
            $save_result = $token->save();
                if ($save_result) {
                    return array(
                        'result' => 'success',
                        '_payment_tokens_id' => $payment_method_token,
                        'redirect' => wc_get_account_endpoint_url('payment-methods')
                    );
                } else {
                    if ($zero_amount_payment == false) {
                        wp_redirect(wc_get_account_endpoint_url('payment-methods'));
                        exit;
                    } else {
                        return array(
                            'result' => 'success',
                            '_payment_tokens_id' => $payment_method_token,
                            'redirect' => wc_get_account_endpoint_url('payment-methods')
                        );
                    }
                }
            } else {
                throw new Exception( __( 'Invalid or missing payment token fields.', 'paypal-for-woocommerce' ) );
            }
        } else {
            if ($zero_amount_payment == false) {
                wp_redirect(wc_get_account_endpoint_url('payment-methods'));
                exit;
            } else {
                return array(
                    'result' => 'success',
                    '_payment_tokens_id' => $payment_method_token,
                    'redirect' => wc_get_account_endpoint_url('payment-methods')
                );
            }
        }
    }

    public function braintree_create_customer($customer_id) {
        $user = get_user_by('id', $customer_id);
        $firstName = (get_user_meta($customer_id, 'billing_first_name', true)) ? get_user_meta($customer_id, 'billing_first_name', true) : get_user_meta($customer_id, 'shipping_first_name', true);
        $lastName = (get_user_meta($customer_id, 'billing_last_name', true)) ? get_user_meta($customer_id, 'billing_last_name', true) : get_user_meta($customer_id, 'shipping_last_name', true);
        $company = (get_user_meta($customer_id, 'billing_company', true)) ? get_user_meta($customer_id, 'billing_company', true) : get_user_meta($customer_id, 'shipping_company', true);
        $billing_email = (get_user_meta($customer_id, 'billing_email', true)) ? get_user_meta($customer_id, 'billing_email', true) : get_user_meta($customer_id, 'shipping_last_name', true);
        $billing_email = ($billing_email) ? $billing_email : $user->user_email;
        $firstName = ($firstName) ? $firstName : $user->first_name;
        $lastName = ($lastName) ? $lastName : $user->last_name;
        $create_customer_request = array('firstName' => $firstName,
            'lastName' => $lastName,
            'company' => $company,
            'email' => $billing_email,
            'phone' => '',
            'fax' => '',
            'website' => ''
        );
        $result = Braintree_Customer::create(apply_filters('angelleye_woocommerce_braintree_create_customer_request_args', $create_customer_request));
        if ($result->success == true) {
            if (!empty($result->customer->id)) {
                update_user_meta($customer_id, 'braintree_customer_id', $result->customer->id);
                return $result->customer->id;
            }
        }
    }

    public function subscription_process_payment($order_id) {
        $this->angelleye_braintree_lib();
        $order = new WC_Order($order_id);
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        if (isset($_POST['wc-braintree-payment-token']) && 'new' !== $_POST['wc-braintree-payment-token']) {
            $token_id = wc_clean($_POST['wc-braintree-payment-token']);
            $token = WC_Payment_Tokens::get($token_id);
            if ($token->get_user_id() !== get_current_user_id()) {
                throw new Exception(__('Error processing checkout. Please try again.', 'paypal-for-woocommerce'));
            } else {
                update_post_meta($order_id, 'is_sandbox', $this->sandbox);
                $payment_tokens_id = $token->get_token();
                $this->save_payment_token($order, $payment_tokens_id);
                $order->payment_complete($payment_tokens_id);
                WC()->cart->empty_cart();
                $result = array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
                if (is_ajax()) {
                    wp_send_json($result);
                } else {
                    wp_redirect($result['redirect']);
                    exit;
                }
            }
        } else {
            $result = $this->add_payment_method($zero_amount_payment = true);
            if ($result['result'] == 'success') {
                update_post_meta($order_id, 'is_sandbox', $this->sandbox);
                $payment_tokens_id = (!empty($result['_payment_tokens_id'])) ? $result['_payment_tokens_id'] : '';
                $this->save_payment_token($order, $payment_tokens_id);
                $order->payment_complete($payment_tokens_id);
                WC()->cart->empty_cart();
                $result = array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
                if (is_ajax()) {
                    wp_send_json($result);
                } else {
                    wp_redirect($result['redirect']);
                    exit;
                }
            } else {
                WC()->session->set('reload_checkout', true);
                return array(
                    'result' => 'fail',
                    'redirect' => ''
                );
            }
        }
    }

    public function send_failed_order_email($order_id) {
        $emails = WC()->mailer()->get_emails();
        if (!empty($emails) && !empty($order_id)) {
            $emails['WC_Email_Failed_Order']->trigger($order_id);
        }
    }

    public function save_payment_token($order, $payment_tokens_id) {
        // Store source in the order
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        if (!empty($payment_tokens_id)) {
            update_post_meta($order_id, '_payment_tokens_id', $payment_tokens_id);
        }
    }

    public function is_subscription($order_id) {
        return ( function_exists('wcs_order_contains_subscription') && ( wcs_order_contains_subscription($order_id) || wcs_is_subscription($order_id) || wcs_order_contains_renewal($order_id) ) );
    }

    public function process_subscription_payment($order, $amount, $payment_token = null) {
        $this->angelleye_reload_gateway_credentials_for_woo_subscription_renewal_order($order);
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        $request_data = array();
        $this->angelleye_braintree_lib();

        $billing_company = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_company : $order->get_billing_company();
        $billing_first_name = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_first_name : $order->get_billing_first_name();
        $billing_last_name = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_last_name : $order->get_billing_last_name();
        $billing_address_1 = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_address_1 : $order->get_billing_address_1();
        $billing_address_2 = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_address_2 : $order->get_billing_address_2();
        $billing_city = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_city : $order->get_billing_city();
        $billing_postcode = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_postcode : $order->get_billing_postcode();
        $billing_country = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_country : $order->get_billing_country();
        $billing_state = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_state : $order->get_billing_state();

        $request_data['billing'] = array(
            'firstName' => $billing_first_name,
            'lastName' => $billing_last_name,
            'company' => $billing_company,
            'streetAddress' => $billing_address_1,
            'extendedAddress' => $billing_address_2,
            'locality' => $billing_city,
            'region' => $billing_state,
            'postalCode' => $billing_postcode,
            'countryCodeAlpha2' => $billing_country,
        );

        $request_data['shipping'] = array(
            'firstName' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_first_name : $order->get_shipping_first_name(),
            'lastName' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_last_name : $order->get_shipping_last_name(),
            'company' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_company : $order->get_shipping_company(),
            'streetAddress' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_address_1 : $order->get_shipping_address_1(),
            'extendedAddress' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_address_2 : $order->get_shipping_address_2(),
            'locality' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_city : $order->get_shipping_city(),
            'region' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_state : $order->get_shipping_state(),
            'postalCode' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_postcode : $order->get_shipping_postcode(),
            'countryCodeAlpha2' => version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_country : $order->get_shipping_country(),
        );

        if ($this->is_subscription($order_id)) {
            $request_data['paymentMethodToken'] = get_post_meta($order_id, '_payment_tokens_id', true);
        }
        if (!empty($payment_token)) {
            $request_data['paymentMethodToken'] = $payment_token;
        }
        if (is_user_logged_in()) {
            $customer_id = get_current_user_id();
            $braintree_customer_id = get_user_meta($customer_id, 'braintree_customer_id', true);
            if (!empty($braintree_customer_id)) {
                $request_data['customerId'] = $braintree_customer_id;
            } else {
                $request_data['customer'] = array(
                    'firstName' => version_compare(WC_VERSION, '3.0', '<') ? $order->billing_first_name : $order->get_billing_first_name(),
                    'lastName' => version_compare(WC_VERSION, '3.0', '<') ? $order->billing_last_name : $order->get_billing_last_name(),
                    'company' => version_compare(WC_VERSION, '3.0', '<') ? $order->billing_company : $order->get_billing_company(),
                    'phone' => version_compare(WC_VERSION, '3.0', '<') ? $order->billing_phone : $order->get_billing_phone(),
                    'email' => version_compare(WC_VERSION, '3.0', '<') ? $order->billing_email : $order->get_billing_email(),
                );
            }
        }
        $request_data['amount'] = number_format($order->get_total(), 2, '.', '');
        $this->merchant_account_id = $this->angelleye_braintree_get_merchant_account_id($order_id);
        if (isset($this->merchant_account_id) && !empty($this->merchant_account_id)) {
            $request_data['merchantAccountId'] = $this->merchant_account_id;
        }
        $request_data['orderId'] = $order->get_order_number();
        $request_data['options'] = $this->get_braintree_options();
        $request_data['channel'] = 'AngellEYEPayPalforWoo_BT';
        if ($this->debug) {
            $this->add_log('Begin Braintree_Transaction::sale request');
            $this->add_log('Order: ' . print_r($order->get_order_number(), true));
        }
        try {
            $this->response = Braintree_Transaction::sale($request_data);
        } catch (Braintree_Exception_Authentication $e) {
            $this->add_log("Braintree_Transaction::sale Braintree_Exception_Authentication: API keys are incorrect, Please double-check that you haven't accidentally tried to use your sandbox keys in production or vice-versa.");
            return $success = false;
        } catch (Braintree_Exception_Authorization $e) {
            $this->add_log("Braintree_Transaction::sale Braintree_Exception_Authorization: The API key that you're using is not authorized to perform the attempted action according to the role assigned to the user who owns the API key.");
            return $success = false;
        } catch (Braintree_Exception_DownForMaintenance $e) {
            $this->add_log("Braintree_Transaction::sale Braintree_Exception_DownForMaintenance: Request times out.");
            return $success = false;
        } catch (Braintree_Exception_ServerError $e) {
            $this->add_log("Braintree_Transaction::sale Braintree_Exception_ServerError " . $e->getMessage());
            return $success = false;
        } catch (Braintree_Exception_SSLCertificate $e) {
            $this->add_log("Braintree_Transaction::sale Braintree_Exception_SSLCertificate " . $e->getMessage());
            return $success = false;
        } catch (Exception $e) {
            $this->add_log('Error: Unable to complete transaction. Reason: ' . $e->getMessage());
            return $success = false;
        }
        if (!$this->response->success) {
            $this->add_log("Error: Unable to complete transaction. Reason: {$this->response->message}");
            return $success = false;
        }
        $this->add_log('Braintree_Transaction::sale Response code: ' . print_r($this->get_status_code(), true));
        $this->add_log('Braintree_Transaction::sale Response message: ' . print_r($this->get_status_message(), true));
        $maybe_settled_later = array(
            'settling',
            'settlement_pending',
            'submitted_for_settlement',
        );
        if (in_array($this->response->transaction->status, $maybe_settled_later)) {
            update_post_meta($order_id, 'is_sandbox', $this->sandbox);
            $order->payment_complete($this->response->transaction->id);
            $order->add_order_note(sprintf(__('%s payment approved! Transaction ID: %s', 'paypal-for-woocommerce'), $this->title, $this->response->transaction->id));
        } else {
            $this->add_log(sprintf('Info: unhandled transaction id = %s, status = %s', $this->response->transaction->id, $this->response->transaction->status));
            $order->update_status('on-hold', sprintf(__('Transaction was submitted to PayPal Braintree but not handled by WooCommerce order, transaction_id: %s, status: %s. Order was put in-hold.', 'paypal-for-woocommerce'), $this->response->transaction->id, $this->response->transaction->status));
            $old_wc = version_compare(WC_VERSION, '3.0', '<');
            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            if ($old_wc) {
                if (!get_post_meta($order_id, '_order_stock_reduced', true)) {
                    $order->reduce_order_stock();
                }
            } else {
                wc_maybe_reduce_stock_levels($order_id);
            }
        }
    }

    public function angelleye_braintree_encrypt_gateway_api($settings) {
        if (!empty($settings['is_encrypt'])) {
            $gateway_settings_key_array = array('sandbox_public_key', 'sandbox_private_key', 'sandbox_merchant_id', 'public_key', 'private_key', 'merchant_id');
            foreach ($gateway_settings_key_array as $gateway_settings_key => $gateway_settings_value) {
                if (!empty($settings[$gateway_settings_value])) {
                    $settings[$gateway_settings_value] = AngellEYE_Utility::crypting($settings[$gateway_settings_value], $action = 'e');
                }
            }
        }
        return $settings;
    }

    public function angelleye_braintree_get_merchant_account_id($order_id = null) {
        try {
            $merchant_account_id = $this->get_option('merchant_account_id');
            $currencycode = '';
            if (is_null($order_id)) {
                $currencycode = get_woocommerce_currency();
            } else {
                $order = wc_get_order($order_id);
                $currencycode = version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency();
            }
            if (!empty($merchant_account_id)) {
                foreach ($merchant_account_id as $key => $value) {
                    $currency = substr($key, -3);
                    if (strtoupper($currency) == strtoupper($currencycode)) {
                        $this->merchant_account_id = $value;
                        return $this->merchant_account_id;
                    }
                }
            }
            $this->angelleye_braintree_lib();
            try {
                $gateway = Braintree_Configuration::gateway();
                $merchantAccountIterator = $gateway->merchantAccount()->all();
                foreach ($merchantAccountIterator as $merchantAccount) {
                    if ($currencycode == $merchantAccount->currencyIsoCode) {
                        $this->merchant_account_id = $merchantAccount->id;
                        return $this->merchant_account_id;
                    }
                }
                foreach ($merchantAccountIterator as $merchantAccount) {
                    if ($merchantAccount->default == true) {
                        $this->merchant_account_id = $merchantAccount->id;
                        return $this->merchant_account_id;
                    }
                }
            } catch (Braintree_Exception_NotFound $e) {
                return '';
            } catch (Braintree_Exception_Authentication $e) {
                return '';
            } catch (Braintree_Exception_Authorization $e) {
                return '';
            } catch (Braintree_Exception_DownForMaintenance $e) {
                return '';
            } catch (Exception $e) {
                return '';
            }
        } catch (Exception $ex) {
            
        }
    }

    public function get_softdescriptor() {
        if (!empty($this->softdescriptor_value)) {
            $softdescriptor_array = explode('*', $this->softdescriptor_value);
            if (!empty($softdescriptor_array[0]) && !empty($softdescriptor_array[1])) {
                $company_name_len = strlen($softdescriptor_array[0]);
                $company_name = $softdescriptor_array[0];
                if ($company_name_len == 3 || $company_name_len == 7 || $company_name_len == 12) {
                    
                } else {
                    if ($company_name_len > 12) {
                        $company_name = substr($company_name, 0, 12);
                    } elseif ($company_name_len > 7) {
                        $company_name = substr($company_name, 0, 7);
                    } elseif ($company_name_len > 3) {
                        $company_name = substr($company_name, 0, 3);
                    }
                }
                $company_name = trim($company_name);
                $company_name_new_len = strlen($company_name);
                $product_descriptor = '';
                if (!empty($softdescriptor_array[1])) {
                    $product_descriptor = $softdescriptor_array[1];
                }
                $product_descriptor = '* ' . trim($product_descriptor);
                $softdescriptor = $company_name . $product_descriptor;
                $softdescriptor_len = $company_name_new_len + strlen($product_descriptor);
                if ($softdescriptor_len > 22) {
                    $softdescriptor = substr($softdescriptor, 0, 22);
                } else {
                    $diff = 22 - $softdescriptor_len;
                    for ($i = 0; $i < $diff; $i ++) {
                        $softdescriptor .= ' ';
                    }
                }
                return $softdescriptor;
            }
        }
        return '';
    }

    public function adjust_fraud_script_tag($url) {
        if (strpos($url, 'braintree-data.js') !== false) {
            $url = "{$url}' async='true";
        }
        if (strpos($url, 'https://js.braintreegateway.com/v2/braintree.js') !== false) {
            return "$url' data-log-level='error";
        }
        return $url;
    }

    public function render_fraud_js() {
        $environment = 'BraintreeData.environments.' . $this->environment;
        $this->kount_merchant_id = ($this->fraud_tool == 'kount_custom') ? $this->get_option('kount_merchant_id') : '';

        if ($this->fraud_tool == 'kount_custom' && !empty($this->kount_merchant_id)) {
            $environment .= '.withId' . $this->kount_merchant_id;
        }
        ?>
        <script>
            jQuery(function ($) {
                var form_id;
                if ($('form.checkout').length) {
                    form_id = $('form.checkout').attr('id') || 'checkout';
                    if ('checkout' === form_id) {
                        $('form.checkout').attr('id', form_id);
                    }

                } else if ($('form#order_review').length) {
                    form_id = 'order_review'
                } else if ($('form#add_payment_method').length) {
                    form_id = 'add_payment_method'
                }
                if (!form_id) {
                    return;
                }
                window.onBraintreeDataLoad = function () {
                    BraintreeData.setup('<?php echo esc_js($this->merchant_id); ?>', form_id, <?php echo esc_js($environment); ?>);
                }
            });
        </script>
        <?php
    }

    public function has_risk_data() {
        return isset($this->response->transaction->riskData);
    }

    public function get_risk_id() {
        return !empty($this->response->transaction->riskData->id) ? $this->response->transaction->riskData->id : null;
    }

    public function get_risk_decision() {
        return !empty($this->response->transaction->riskData->decision) ? $this->response->transaction->riskData->decision : null;
    }

    public function angelleye_reload_gateway_credentials_for_woo_subscription_renewal_order($order) {
        if ($this->sandbox == false) {
            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            if ($this->is_subscription($order_id)) {
                foreach ($order->get_items() as $cart_item_key => $values) {
                    $product = $order->get_product_from_item($values);
                    $product_id = $product->get_id();
                    if (!empty($product_id)) {
                        $_enable_sandbox_mode = get_post_meta($product_id, '_enable_sandbox_mode', true);
                        if ($_enable_sandbox_mode == 'yes') {
                            $this->sandbox = true;
                            $this->environment = $this->sandbox == false ? 'production' : 'sandbox';
                            $this->merchant_id = $this->sandbox == false ? $this->get_option('merchant_id') : $this->get_option('sandbox_merchant_id');
                            $this->private_key = $this->sandbox == false ? $this->get_option('private_key') : $this->get_option('sandbox_private_key');
                            $this->public_key = $this->sandbox == false ? $this->get_option('public_key') : $this->get_option('sandbox_public_key');
                        }
                    }
                }
            }
        }
    }

    public function woocommerce_admin_order_data_after_order_details($order) {
        $payment_method = version_compare(WC_VERSION, '3.0', '<') ? $order->payment_method : $order->get_payment_method();
        if ('braintree' == $payment_method && $order->get_status() != 'refunded') {
            $this->angelleye_braintree_lib();
            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            $transaction_id = $order->get_transaction_id();
            if( !empty($transaction_id) ) {
                $transaction = Braintree_Transaction::find($transaction_id);
                if (!empty($transaction->refundIds)) {
                    foreach ($transaction->refundIds as $key => $value) {
                        $braintree_refunded_id = get_post_meta($order_id, 'braintree_refunded_id', true);
                        if (empty($braintree_refunded_id)) {
                            $braintree_refunded_id = array();
                        }
                        if (!in_array($value, $braintree_refunded_id)) {
                            $refund_transaction = Braintree_Transaction::find($value);
                            $default_args = array(
                                'amount' => $refund_transaction->amount,
                                'reason' => 'Data Synchronization from Braintree',
                                'order_id' => $order_id,
                                'refund_id' => 0,
                                'line_items' => array(),
                                'refund_payment' => false,
                                'restock_items' => false,
                            );
                            wc_create_refund($default_args);
                            $order->add_order_note(sprintf(__('Refunded %s - Transaction ID: %s', 'paypal-for-woocommerce'), wc_price(number_format($refund_transaction->amount, 2, '.', '')), $value));
                            $braintree_refunded_id[$value] = $value;
                            update_post_meta($order_id, 'braintree_refunded_id', $braintree_refunded_id);
                        }
                    }
                } 
                if( !empty($transaction->status) && $transaction->status == 'voided' ) {
                    $braintree_refunded_id = get_post_meta($order_id, 'braintree_refunded_id', true);
                    if (empty($braintree_refunded_id)) {
                        $braintree_refunded_id = array();
                    }
                    if (!in_array($transaction->id, $braintree_refunded_id)) {
                        $default_args = array(
                            'amount' => $transaction->amount,
                            'reason' => 'Data Synchronization from Braintree',
                            'order_id' => $order_id,
                            'refund_id' => 0,
                            'line_items' => array(),
                            'refund_payment' => false,
                            'restock_items' => false,
                        );
                        wc_create_refund($default_args);
                        $order->add_order_note(sprintf(__('Voided %s - Transaction ID: %s', 'paypal-for-woocommerce'), wc_price(number_format($transaction->amount, 2, '.', '')), $transaction->id));
                        update_post_meta($order_id, 'braintree_refunded_id', $transaction->id);
                    }
                }
            }
        }
    }

    public function angelleye_update_settings($settings) {
        if (!empty($_POST['woocommerce_braintree_merchant_account_id'])) {
            $settings['merchant_account_id'] = $_POST['woocommerce_braintree_merchant_account_id'];
        } else {
            $settings['merchant_account_id'] = '';
        }
        return $settings;
    }

    public function angelleye_display_mid_ui() {
        $base_currency = get_woocommerce_currency();
        $button_text = sprintf(__('Add merchant account ID for %s', 'paypal-for-woocommerce'), $base_currency);
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <select id="wc_braintree_merchant_account_id_currency" class="wc-enhanced-select">
                    <?php foreach (get_woocommerce_currencies() as $code => $name) : ?>
                        <option <?php selected($code, $base_currency); ?> value="<?php echo esc_attr($code); ?>">
                            <?php echo esc_html(sprintf('%s (%s)', ucwords($name), get_woocommerce_currency_symbol($code))); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </th>
            <td class="forminp">
                <a href="#" class="button js-add-merchant-account-id"><?php echo esc_html($button_text); ?></a>
            </td>
        </tr>
        <?php
        $html = ob_get_clean();
        $merchant_account_id = $this->get_option('merchant_account_id');
        if (!empty($merchant_account_id)) {
            foreach ($merchant_account_id as $key => $value) {
                $currency = substr($key, -3);
                $html .= $this->generate_merchant_account_id_html($currency, $value);
            }
        }

        echo $html;
    }

    public function generate_merchant_account_id_html($currency_code = null, $value = null) {
        if (is_null($currency_code)) {
            $currency_display = '{{currency_display}}';
            $currency_code = '{{currency_code}}';
        } else {
            $currency_display = strtoupper($currency_code);
            $currency_code = strtolower($currency_code);
        }
        $id = sprintf('woocommerce_%s_merchant_account_id_%s', $this->id, $currency_code);
        $title = sprintf(__('Merchant Account ID (%s)', 'paypal-for-woocommerce'), $currency_display);
        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($title) ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo esc_html($title) ?></span></legend>
                    <input class="input-text regular-input js-merchant-account-id-input" type="text" name="<?php printf('woocommerce_%s_merchant_account_id[%s]', esc_attr($this->id), esc_attr($currency_code)); ?>" id="<?php echo esc_attr($id); ?>" value="<?php echo esc_attr($value); ?>" placeholder="<?php esc_attr_e('Enter merchant account ID', 'paypal-for-woocommerce'); ?>" />
                    <a href="#" title="<?php esc_attr_e('Remove this merchant account ID', 'paypal-for-woocommerce'); ?>" class="js-remove-merchant-account-id"><span class="dashicons dashicons-trash"></span></a>
                </fieldset>
            </td>
        </tr>
        <?php
        return trim(preg_replace("/[\n\r\t]/", '', ob_get_clean()));
    }

}