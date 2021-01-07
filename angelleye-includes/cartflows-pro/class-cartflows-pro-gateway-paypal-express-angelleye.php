<?php
/**
 * Paypal Gateway.
 *
 * @package cartflows
 */

/**
 * Class Cartflows_Pro_Gateway_Paypal_Express.
 */
class Cartflows_Pro_Gateway_Paypal_Express_Angelleye extends Cartflows_Pro_Paypal_Gateway_helper {

    /**
     * Member Variable.
     *
     * @var instance
     */
    private static $instance;

    /**
     * Key name variable.
     *
     * @var key
     */
    public $key = 'paypal_express';
    public $is_api_refund = true;

    /**
     *  Initiator.
     */
    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'), 20);
        add_filter('angelleye_woocommerce_express_checkout_set_express_checkout_request_args', array($this, 'modify_paypal_arguments'), 999);
        add_filter('angelleye_woocommerce_express_checkout_do_reference_transaction_request_args', array($this, 'modify_do_reference_transaction_request_paypal_arguments'), 999);

        add_action('cartflows_offer_subscription_created', array($this, 'add_subscription_payment_meta_for_paypal_express'), 10, 3);
    }

    /**
     * Load paypal object payment JS.
     *
     * @return void
     */
    public function payment_scripts() {
        if (_is_wcf_base_offer_type() && !wcf_pro()->utils->is_reference_transaction() && $this->has_paypal_gateway()) {
            wp_enqueue_script(
                    'wcf-paypal-script', 'https://www.paypalobjects.com/api/checkout.js', array('jquery'), CARTFLOWS_PRO_VER, true
            );
            if (!wcf_pro()->utils->is_zero_value_offered_product()) {
                $script = $this->generate_script();
                wp_add_inline_script('wcf-paypal-script', $script);
            }
        }
    }

    /**
     * Check if current order has paypal gatway
     *
     * @return bool
     */
    public function has_paypal_gateway() {
        $order_id = isset($_GET['wcf-order']) ? absint($_GET['wcf-order']) : '';
        if (empty($order_id)) {
            return false;
        }
        $order = wc_get_order($order_id);
        $gateway = $order->get_payment_method();
        if ('paypal_express' === $gateway) {
            return true;
        }
        return false;
    }

    /**
     * Generate script for paypal payment popup.
     *
     * @return string
     */
    public function generate_script() {
        $testmode = $this->wc_gateway()->get_option('testmode', 'live');
        if ($testmode == 'yes') {
            $environment = 'sandbox';
        } else {
            $environment = 'live';
        }
        if ('sandbox' == $environment) {
            $api_prefix = 'sandbox_';
        }
        ob_start();
        ?>
        (function($){ $(document).ready(function($) {

        var $wcf_angelleye = {
        init: function () {
        var getButtons = [
        'wcf-upsell-offer',
        'wcf-downsell-offer'
        ];

        $('a[href*="wcf-up-offer-yes"], a[href*="wcf-down-offer-yes"]').each(function(e) {

        var current_id = $(this).attr('id');

        getButtons.push( current_id );
        });

        window.paypalCheckoutReady = function () {
        paypal.checkout.setup(
        '<?php echo $this->get_woo_payer_id(); ?>',
        {
        environment: '<?php echo $environment; ?>',
        buttons: getButtons,
        locale: 'en_US',

        click: function () {

        var variation_id = 0;
        var input_qty = 0;

        var variation_wrapper = $('.wcf-offer-product-variation');

        if( variation_wrapper.length > 0 ) {

        var variation_form 	 = variation_wrapper.find('.variations_form'),
        variation_input   = variation_form.find('input.variation_id');

        // Set variation id here.
        variation_id = parseInt( variation_input.val() );

        if( $('.var_not_selected').length > 0 || '' === variation_id || 0 === variation_id ){

        variation_form.find('.variations select').each(function(){

        if( $(this).val().length === 0 ){
        $(this).addClass('var_not_selected');
        }
        });

        $([ document.documentElement, document.body ]).animate({
        scrollTop: variation_form.find('.variations select').offset().top-100
        }, 1000);

        return false;
        }
        }

        var quantity_wrapper = $('.wcf-offer-product-quantity');

        if ( quantity_wrapper.length > 0 ) {

        var quantity_input = quantity_wrapper.find('input[name="quantity"]');
        var quantity_value = parseInt( quantity_input.val() );

        if( quantity_value > 0 ) {
        input_qty = quantity_value;
        }
        }

        var postData = {
        step_id: cartflows.current_step,
        variation_id: variation_id,
        input_qty: input_qty,
        order_id: <?php echo isset($_GET['wcf-order']) ? intval($_GET['wcf-order']) : 0; ?>,
        order_key: '<?php echo isset($_GET['wcf-key']) ? sanitize_text_field(wp_unslash($_GET['wcf-key'])) : ''; ?>',
        session_key: '<?php echo isset($_GET['wcf-sk']) ? sanitize_text_field(wp_unslash($_GET['wcf-sk'])) : ''; ?>',
        action: 'cartflows_front_create_paypal_express_angelleye_checkout_token'
        };

        paypal.checkout.initXO();

        var action = $.post(cartflows.ajax_url, postData);

        action.done(function (data) {
        paypal.checkout.startFlow(data.token);
        });

        action.fail(function () {
        paypal.checkout.closeFlow();
        });
        }
        }
        );
        }
        }
        };

        $wcf_angelleye.init();
        }); })(jQuery);
        <?php
        return ob_get_clean();
    }

    /**
     * Get Payer ID from option value.
     *
     * @return bool
     */
    public function get_woo_payer_id() {

        $testmode = $this->wc_gateway()->get_option('testmode', 'live');
        if ($testmode == 'yes') {
            $environment = 'sandbox';
        } else {
            $environment = 'live';
        }
        if ('sandbox' == $environment) {
            $api_prefix = 'sandbox_';
        }

        $option_key = 'woocommerce_paypal_express_payer_id_' . $environment . '_' . md5($this->wc_gateway()->get_option($api_prefix . 'api_username') . ':' . $this->wc_gateway()->get_option($api_prefix . 'api_password'));

        $payer_id = get_option($option_key);

        if ($payer_id) {
            return $payer_id;
        } else {
            $result = $this->get_woo_pal_details();

            if (!empty($result['PAL'])) {
                update_option($option_key, wc_clean($result['PAL']));

                return $payer_id;
            }
        }

        return false;
    }

    /**
     * Get Payer details from option value.
     *
     * @return bool
     */
    public function get_woo_pal_details() {

        $testmode = $this->wc_gateway()->get_option('testmode', 'live');
        if ($testmode == 'yes') {
            $environment = 'sandbox';
        } else {
            $environment = 'live';
        }
        if ('sandbox' == $environment) {
            $api_prefix = 'sandbox_';
        }

        $this->setup_api_vars($this->key, $environment, $this->wc_gateway()->get_option($api_prefix . 'api_username'), $this->wc_gateway()->get_option($api_prefix . 'api_password'), $this->wc_gateway()->get_option($api_prefix . 'api_signature'));

        $this->add_parameter('METHOD', 'GetPalDetails');
        $this->add_credentials_param($this->api_username, $this->api_password, $this->api_signature, 124);
        $request = new stdClass();
        $request->path = '';
        $request->method = 'POST';
        $request->body = $this->to_string();

        return $this->perform_request($request);
    }

    /**
     * Generates express checkout token
     *
     * @return void
     */
    public function generate_express_checkout_token() {

        $step_id = isset($_POST['step_id']) ? intval($_POST['step_id']) : 0;
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $order_key = isset($_POST['order_key']) ? sanitize_text_field(wp_unslash($_POST['order_key'])) : '';
        $session_key = isset($_POST['session_key']) ? sanitize_text_field(wp_unslash($_POST['session_key'])) : '';
        $variation_id = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : '';
        $input_qty = isset($_POST['input_qty']) ? intval($_POST['input_qty']) : '';

        $is_valid_order = true;

        if ($is_valid_order) {

            $order = wc_get_order($order_id);

            $response = $this->initiate_express_checkout_request(
                    array(
                'currency' => $order ? $order->get_currency() : get_woocommerce_currency(),
                'return_url' => $this->get_callback_url(
                        array(
                            'action' => 'cartflows_paypal_return',
                            'step_id' => $step_id,
                            'order_id' => $order_id,
                            'order_key' => $order_key,
                            'session_key' => $session_key,
                            'variation_id' => $variation_id,
                            'input_qty' => $input_qty,
                        )
                ),
                'cancel_url' => $this->get_callback_url(
                        array(
                            'action' => 'cartflows_paypal_cancel',
                            'step_id' => $step_id,
                            'order_id' => $order_id,
                            'order_key' => $order_key,
                            'session_key' => $session_key,
                            'variation_id' => $variation_id,
                            'input_qty' => $input_qty,
                        )
                ),
                'notify_url' => $this->get_callback_url('notify_url'),
                'order' => $order,
                'step_id' => $step_id,
                'variation_id' => $variation_id,
                'input_qty' => $input_qty,
                    ), true
            );

            wcf()->logger->log('Generate express checkout token'); //phpcs:ignore
            wcf()->logger->log(print_r($response, true)); //phpcs:ignore

            if (isset($response['TOKEN']) && '' !== $response['TOKEN']) {

                wp_send_json(
                        array(
                            'result' => 'success',
                            'token' => $response['TOKEN'],
                        )
                );
            }
        }

        wp_send_json(
                array(
                    'result' => 'error',
                    'response' => $response,
                )
        );
    }

    /**
     * Initiates express checkout request
     *
     * @param array $args arguments.
     * @param bool  $is_upsell is upsell.
     * @return array
     */
    public function initiate_express_checkout_request($args, $is_upsell = false) {

        $testmode = $this->wc_gateway()->get_option('testmode', 'live');
        if ($testmode == 'yes') {
            $environment = 'sandbox';
        } else {
            $environment = 'live';
        }
        if ('sandbox' == $environment) {
            $api_prefix = 'sandbox_';
        }

        $this->setup_api_vars(
                $this->key, $environment, $this->wc_gateway()->get_option($api_prefix . 'api_username'), $this->wc_gateway()->get_option($api_prefix . 'api_password'), $this->wc_gateway()->get_option($api_prefix . 'api_signature')
        );

        $this->add_express_checkout_params($args, $is_upsell);
        $this->add_credentials_param($this->api_username, $this->api_password, $this->api_signature, 124);

        $request = new stdClass();
        $request->path = '';
        $request->method = 'POST';
        $request->body = $this->to_string();

        $flow_id = wcf()->utils->get_flow_id_from_step_id($args['step_id']);

        $data = array(
            'paypal' => $this->get_parameters(),
        );

        wcf_pro()->session->update_data($flow_id, $data);

        return $this->perform_request($request);
    }

    /**
     * Adds express checkout parameters
     *
     * @param array $args arguments.
     * @param bool  $is_upsell is upsell.
     * @return void
     */
    public function add_express_checkout_params($args, $is_upsell = false) {

        // translators: placeholder is blogname.
        $default_description = sprintf(_x('Orders with %s', 'data sent to paypal', 'cartflows-pro'), get_bloginfo('name'));

        $defaults = array(
            'currency' => get_woocommerce_currency(),
            'billing_type' => 'MerchantInitiatedBillingSingleAgreement',
            'billing_description' => html_entity_decode(apply_filters('woocommerce_subscriptions_paypal_billing_agreement_description', $default_description, $args), ENT_NOQUOTES, 'UTF-8'),
            'maximum_amount' => null,
            'no_shipping' => 1,
            'page_style' => null,
            'brand_name' => html_entity_decode(get_bloginfo('name'), ENT_NOQUOTES, 'UTF-8'),
            'landing_page' => 'login',
            'payment_action' => 'Sale',
            'custom' => '',
        );

        $args = wp_parse_args($args, $defaults);

        $this->set_method('SetExpressCheckout');

        $this->add_parameters(
                array(
                    'RETURNURL' => $args['return_url'],
                    'CANCELURL' => $args['cancel_url'],
                    'PAGESTYLE' => $args['page_style'],
                    'BRANDNAME' => $args['brand_name'],
                    'LANDINGPAGE' => ( 'login' === $args['landing_page'] && false === $is_upsell ) ? 'Login' : 'Billing',
                    'NOSHIPPING' => $args['no_shipping'],
                    'MAXAMT' => $args['maximum_amount'],
                )
        );

        if (false === $is_upsell) {
            $this->add_parameter('L_BILLINGTYPE0', $args['billing_type']);
            $this->add_parameter('L_BILLINGAGREEMENTDESCRIPTION0', get_bloginfo('name'));
            $this->add_parameter('L_BILLINGAGREEMENTCUSTOM0', '');
        }

        // Add payment parameters.
        if (isset($args['order'])) {

            if (true === $is_upsell) {
                $this->add_payment_params($args['order'], $args['step_id'], $args['payment_action'], false, true, $args['variation_id'], $args['input_qty']);
            } else {
                $this->add_payment_params($args['order'], $args['step_id'], $args['payment_action'], false, false);
            }
        }

        $set_express_checkout_params = apply_filters('cartflows_gateway_paypal_param_setexpresscheckout', $this->get_parameters(), $is_upsell);
        $this->clean_params();
        $this->add_parameters($set_express_checkout_params);
    }

    /**
     * Get callback URL for paypal payment API request.
     *
     * @param array $args arguments.
     * @return string
     */
    public function get_callback_url($args) {

        $api_request_url = WC()->api_request_url('cartflows_paypal_express');

        if (is_array($args)) {

            return add_query_arg($args, $api_request_url);
        } else {

            return add_query_arg('action', $args, $api_request_url);
        }
    }

    /**
     * Get WooCommerce payment geteways.
     *
     * @return array
     */
    public function wc_gateway() {

        global $woocommerce;

        $gateways = $woocommerce->payment_gateways->payment_gateways();

        return $gateways[$this->key];
    }

    /**
     * Clean params.
     *
     * @return void
     */
    public function clean_params() {
        $this->parameters = array();
    }

    /**
     * Return the parsed response object for the request
     *
     * @since 1.0.0
     *
     * @param string $raw_response_body response body.
     *
     * @return object
     */
    protected function get_parsed_response($raw_response_body) {

        wp_parse_str(urldecode($raw_response_body), $this->response_params);

        return $this->response_params;
    }

    /**
     * Modify paypal arguements to set paramters before checkout express.
     *
     * @param array $data parameters array.
     * @return array
     */
    public function modify_paypal_arguments($data) {

        wcf()->logger->log(__CLASS__ . '::' . __FUNCTION__ . ' : Entering ');

        if (true === wcf_pro()->utils->is_reference_transaction()) {

            // translators: blog name.
            $description = sprintf(_x('Orders with %s', 'data sent to PayPal', 'cartflows-pro'), get_bloginfo('name'));

            $description = html_entity_decode($description, ENT_NOQUOTES, 'UTF-8');

            if (true === wcf_pro()->utils->is_reference_transaction() && $data && isset($_POST['_wcf_checkout_id'])) {
                $data['returnurl'] = add_query_arg(array('create-billing-agreement' => true), $data['SECFields']['returnurl']);

                $BillingAgreements = array();
                $Item = array(
                    'l_billingtype' => '',
                    'l_billingtype' => 'MerchantInitiatedBilling',
                    'l_billingagreementdescription' => $description,
                    'l_paymenttype' => '',
                    'l_paymenttype' => 'Any',
                    'l_billingagreementcustom' => ''
                );
                array_push($BillingAgreements, $Item);
                $data['BillingAgreements'] = $BillingAgreements;
            }
        }


        return $data;
    }

    public function modify_do_reference_transaction_request_paypal_arguments($data) {

        wcf()->logger->log(__CLASS__ . '::' . __FUNCTION__ . ' : Entering ');

        if (true === wcf_pro()->utils->is_reference_transaction()) {

            // translators: blog name.
            $description = sprintf(_x('Orders with %s', 'data sent to PayPal', 'cartflows-pro'), get_bloginfo('name'));

            $description = html_entity_decode($description, ENT_NOQUOTES, 'UTF-8');



            if ($data && isset($data['METHOD']) && 'DoReferenceTransaction' == $data['METHOD']) {

                $step_id = isset($_POST['step_id']) ? (int) $_POST['step_id'] : 0;
                $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;

                // Return if the step  id is not in the query string.
                if ($step_id < 1) {
                    return $data;
                }

                // Return if the order id is not in the query string.
                if ($order_id < 1) {
                    return $data;
                }

                $step_id = isset($_POST['step_id']) ? (int) $_POST['step_id'] : '';
                $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : '';
                $order = wc_get_order($order_id);
                $variation_id = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : '';
                $input_qty = isset($_POST['input_qty']) ? intval($_POST['input_qty']) : '';
                $offer_package = wcf_pro()->utils->get_offer_data($step_id, $variation_id, $input_qty, $order_id);

                /**
                 * If we do not have the current order set that means its not the upsell accept call but the call containing subscriptions.
                 */
                $data['AMT'] = $offer_package['total'];
                $data['ITEMAMT'] = $offer_package['total'];

                // shippingamt shoud be 0.
                if (( isset($offer_package['shipping']) && isset($offer_package['shipping']['diff']) ) && 0 < $offer_package['shipping']['diff']) {
                    $data['SHIPPINGAMT'] = 0;
                    $data['SHIPDISCAMT'] = ( isset($offer_package['shipping']) && isset($offer_package['shipping']['diff']) ) ? $offer_package['shipping']['diff']['cost'] : 0;
                } else {
                    $data['SHIPPINGAMT'] = ( isset($offer_package['shipping']) && isset($offer_package['shipping']['diff']) ) ? $offer_package['shipping']['diff']['cost'] : 0;
                    $data['SHIPDISCAMT'] = 0;
                }

                $data['TAXAMT'] = ( isset($offer_package['taxes']) ) ? $offer_package['taxes'] : 0;
                $data['INVNUM'] = 'WC-' . $order_id . '_' . $step_id;
                $data['INSURANCEAMT'] = 0;
                $data['HANDLINGAMT'] = 0;
                $data = $this->remove_previous_line_items($data);

                $data['L_NAME0'] = $offer_package['name'];
                $data['L_DESC0'] = $offer_package['desc'];
                $data['L_AMT0'] = wc_format_decimal($offer_package['unit_price_tax'], 2);
                $data['L_QTY0'] = $offer_package['qty'];

                $item_amt = $offer_package['total'];

                $data['ITEMAMT'] = $item_amt;
            }
        }

        return $data;
    }

    /**
     * Create billing agreement for future reference transaction.
     *
     * @throws Exception Billing agreement errors.
     */
    public function create_billing_agreement() {

        if (!isset($_GET['action'])) {
            return;
        }

        switch ($_GET['action']) {

            // create billing agreement for reference transaction.
            case 'cartflows_paypal_express_create_billing_agreement':
                // bail if no token.
                if (!isset($_GET['token'])) {
                    return;
                }

                // get token to retrieve checkout details with.
                $token = esc_attr(sanitize_text_field(wp_unslash($_GET['token'])));
                $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
                $step_id = isset($_GET['step_id']) ? intval($_GET['step_id']) : 0;

                try {

                    $express_checkout_details_response = $this->perform_express_checkout_details_request($token);

                    // Make sure the billing agreement was accepted.
                    if (1 == $express_checkout_details_response['BILLINGAGREEMENTACCEPTEDSTATUS']) {

                        $order = wc_get_order($order_id);

                        if (is_null($order)) {
                            throw new Exception(__('Unable to find order for PayPal billing agreement.', 'cartflows-pro'));
                        }

                        // we need to process an initial payment.
                        if ($order->get_total() > 0) {

                            $billing_agreement_response = $this->perform_express_checkout_request(
                                    $token, $order, array(
                                'payment_action' => 'Sale',
                                'payer_id' => $this->get_value_from_response($express_checkout_details_response, 'PAYERID'),
                                'step_id' => $step_id,
                                    )
                            );
                        } else {

                            $redirect_url = add_query_arg('utm_nooverride', '1', $order->get_checkout_order_received_url());

                            // redirect customer to order received page.
                            wp_safe_redirect(esc_url_raw($redirect_url));
                            exit;
                        }

                        if ($this->has_error_api_response($billing_agreement_response)) {

                            $redirect_url = add_query_arg('utm_nooverride', '1', $order->get_checkout_order_received_url());

                            // redirect customer to order received page.
                            wp_safe_redirect(esc_url_raw($redirect_url));
                            exit;
                        }

                        $order->set_payment_method('paypal');

                        // Store the billing agreement ID on the order and subscriptions.
                        update_post_meta(wcf_pro()->wc_common->get_order_id($order), '_paypal_subscription_id', $this->get_value_from_response($billing_agreement_response, 'BILLINGAGREEMENTID'));

                        $order->payment_complete($billing_agreement_response['PAYMENTINFO_0_TRANSACTIONID']);

                        $redirect_url = add_query_arg('utm_nooverride', '1', $order->get_checkout_order_received_url());

                        // redirect customer to order received page.
                        wp_safe_redirect(esc_url_raw($redirect_url));
                        exit;
                    } else {

                        wp_safe_redirect(wc_get_cart_url());
                        exit;
                    }
                } catch (Exception $e) {

                    wc_add_notice(__('An error occurred, please try again or try an alternate form of payment.', 'cartflows-pro'), 'error');

                    wp_safe_redirect(wc_get_cart_url());
                }

                exit;
        }
    }

    /**
     * Performs express checkout request
     *
     * @param string $token token string.
     * @param array  $order Order data.
     * @param array  $args arguments data.
     *
     * @return object
     */
    public function perform_express_checkout_request($token, $order, $args) {

        $testmode = $this->wc_gateway()->get_option('testmode', 'live');
        if ($testmode == 'yes') {
            $environment = 'sandbox';
        } else {
            $environment = 'live';
        }
        if ('sandbox' == $environment) {
            $api_prefix = 'sandbox_';
        }

        $this->setup_api_vars(
                $this->key, $environment, $this->wc_gateway()->get_option($api_prefix . 'api_username'), $this->wc_gateway()->get_option($api_prefix . 'api_password'), $this->wc_gateway()->get_option($api_prefix . 'api_signature')
        );

        $this->add_do_express_checkout_params($token, $order, $args);

        $this->add_credentials_param($this->api_username, $this->api_password, $this->api_signature, 124);

        $request = new stdClass();
        $request->path = '';
        $request->method = 'POST';
        $request->body = $this->to_string();

        return $this->perform_request($request);
    }

    /**
     * Sets up DoExpressCheckoutPayment API Call arguments
     *
     * @param string   $token Unique token of the payment initiated.
     * @param WC_Order $order order data.
     * @param array    $args arguments data.
     */
    public function add_do_express_checkout_params($token, $order, $args) {

        $this->set_method('DoExpressCheckoutPayment');

        // set base params.
        $this->add_parameters(
                array(
                    'TOKEN' => $token,
                    'PAYERID' => $args['payer_id'],
                    'RETURNFMFDETAILS' => 1,
                )
        );

        $this->add_payment_params($order, $args['step_id'], $args['payment_action'], false, false);
    }

    /**
     * Request to get express checkout details.
     *
     * @param string $token token.
     *
     * @return object
     */
    public function perform_express_checkout_details_request($token) {

        $testmode = $this->wc_gateway()->get_option('testmode', 'live');
        if ($testmode == 'yes') {
            $environment = 'sandbox';
        } else {
            $environment = 'live';
        }
        if ('sandbox' == $environment) {
            $api_prefix = 'sandbox_';
        }

        $this->setup_api_vars(
                $this->key, $environment, $this->wc_gateway()->get_option($api_prefix . 'api_username'), $this->wc_gateway()->get_option($api_prefix . 'api_password'), $this->wc_gateway()->get_option($api_prefix . 'api_signature')
        );

        $this->set_express_checkout_method($token);
        $this->add_credentials_param($this->api_username, $this->api_password, $this->api_signature, 124);
        $request = new stdClass();
        $request->path = '';
        $request->method = 'POST';
        $request->body = $this->to_string();

        return $this->perform_request($request);
    }

    /**
     * Set methods and token paramter.
     *
     * @param string $token Token string.
     */
    public function set_express_checkout_method($token) {

        $this->set_method('GetExpressCheckoutDetails');
        $this->add_parameter('TOKEN', $token);
    }

    /**
     * Processes API calls.
     *
     * @return void
     */
    public function process_api_calls() {

        if (!isset($_GET['action'])) {
            return;
        }

        $step_id = isset($_GET['step_id']) ? intval($_GET['step_id']) : 0;
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $order_key = isset($_GET['order_key']) ? sanitize_text_field(wp_unslash($_GET['order_key'])) : '';
        $session_key = isset($_GET['session_key']) ? sanitize_text_field(wp_unslash($_GET['session_key'])) : '';
        $variation_id = isset($_GET['variation_id']) ? intval($_GET['variation_id']) : '';
        $input_qty = isset($_GET['input_qty']) ? intval($_GET['input_qty']) : '';

        $order = wc_get_order($order_id);

        switch ($_GET['action']) {

            case 'cartflows_paypal_return':
                $flow_id = wcf()->utils->get_flow_id_from_step_id($step_id);

                $data = wcf_pro()->session->get_data($flow_id);

                $offer_product = wcf_pro()->utils->get_offer_data($step_id, $variation_id, $input_qty, $order_id);

                if (isset($_GET['token']) && !empty($_GET['token'])) {

                    /**
                     * Setting up necessary data for this api call.
                     */
                    $api_response_result = false;

                    /**
                     * Get the data we saved while calling setExpressCheckout call.
                     */
                    $get_paypal_data = array();

                    if (isset($data['paypal'])) {
                        $get_paypal_data = $data['paypal'];
                    }

                    $express_checkout_details_response = $this->perform_express_checkout_details_request(sanitize_text_field(wp_unslash($_GET['token'])));

                    wcf()->logger->log('Express checkout token return request'); //phpcs:ignore
                    wcf()->logger->log(print_r($express_checkout_details_response, true)); //phpcs:ignore

                    /**
                     * Check if product total is greater than 0.
                     */
                    if ($offer_product['total'] > 0) {

                        /**
                         * Prepare DoExpessCheckout Call to finally charge the user.
                         */
                        $do_express_checkout_data = array(
                            'TOKEN' => $express_checkout_details_response['TOKEN'],
                            'PAYERID' => $express_checkout_details_response['PAYERID'],
                            'METHOD' => 'DoExpressCheckoutPayment',
                        );

                        $do_express_checkout_data = wp_parse_args($do_express_checkout_data, $get_paypal_data);

                        $testmode = $this->wc_gateway()->get_option('testmode', 'live');
                        if ($testmode == 'yes') {
                            $environment = 'sandbox';
                        } else {
                            $environment = 'live';
                        }
                        if ('sandbox' == $environment) {
                            $api_prefix = 'sandbox_';
                        }

                        /**
                         * Setup & perform DoExpressCheckout API Call.
                         */
                        $this->setup_api_vars(
                                $this->key, $environment, $this->wc_gateway()->get_option($api_prefix . 'api_username'), $this->wc_gateway()->get_option($api_prefix . 'api_password'), $this->wc_gateway()->get_option($api_prefix . 'api_signature')
                        );

                        $this->add_parameters($do_express_checkout_data);
                        $this->add_credentials_param($this->api_username, $this->api_password, $this->api_signature, 124);

                        $request = new stdClass();
                        $request->path = '';
                        $request->method = 'POST';
                        $request->body = $this->to_string();

                        $response_checkout = $this->perform_request($request);

                        wcf()->logger->log('Express $response_checkout checkout token charge'); //phpcs:ignore
                        wcf()->logger->log(print_r($response_checkout, true)); //phpcs:ignore

                        if (false === $this->has_error_api_response($response_checkout)) {
                            $api_response_result = true;

                            // Store transaction ID for the CartFlows offer.
                            $this->store_offer_transaction($order, $response_checkout, $offer_product);
                        }
                    } else {
                        $api_response_result = true;

                        // Store transaction ID for the CartFlows offer.
                        $this->store_offer_transaction($order, $response_checkout, $offer_product);
                    }

                    /*                     * ** DoExpressCheckout Call Completed */
                    /**
                     * Allow our subscription addon to make subscription request.
                     */
                    $api_response_result = apply_filters('cartflows_gateway_in_offer_transaction_paypal_after_express_checkout_response', $api_response_result, $express_checkout_details_response['TOKEN'], $express_checkout_details_response['PAYERID'], $this);

                    $result = wcf_pro()->flow->after_offer_charge($step_id, $order_id, $order_key, $api_response_result, $variation_id, $input_qty);

                    wp_safe_redirect($result['redirect']);
                    exit;
                } else {

                    $result = wcf_pro()->flow->after_offer_charge($step_id, $order_id, $order_key, $api_response_result, $variation_id, $input_qty);

                    wp_safe_redirect($result['redirect']);
                    exit;
                }

                break;

            case 'cartflows_paypal_cancel':
                $url = get_permalink($step_id);

                $args = array(
                    'wcf-order' => $order_id,
                    'wcf-key' => $order_key,
                    'wcf-sk' => $session_key,
                );

                $url = add_query_arg($args, $url);

                wp_safe_redirect($url);
                exit;
        }
    }

    /**
     * Processes offer payment.
     * This will be executed if the reference transaction setting is disabled.
     *
     * @param array $order order details.
     * @param array $product product details.
     * @return bool
     */
    public function process_offer_payment($order, $product) {
        $is_successful = false;
        try {
            $order_id = $order->get_id();
            $api_prefix = '';
            $testmode = $this->wc_gateway()->settings['testmode'];
            if ($testmode == 'yes') {
                $environment = 'sandbox';
            } else {
                $environment = 'live';
            }
            if ('sandbox' == $environment) {
                $api_prefix = 'sandbox_';
            }
            $this->setup_api_vars(
                    $this->key, $environment, $this->wc_gateway()->get_option($api_prefix . 'api_username'), $this->wc_gateway()->get_option($api_prefix . 'api_password'), $this->wc_gateway()->get_option($api_prefix . 'api_signature')
            );
            $this->add_reference_trans_args($this->get_token($order), $order, array(), $product);
            $this->add_credentials_param($this->api_username, $this->api_password, $this->api_signature, 124);
            $request = new stdClass();
            $request->path = '';
            $request->method = 'POST';
            $parameters = apply_filters('angelleye_woocommerce_express_checkout_do_reference_transaction_request_args', $this->get_parameters());
            $request->body = http_build_query($parameters, '', '&');
            $response = $this->perform_request($request);
            if ($this->has_error_api_response($response)) {
                wcf()->logger->log('PayPal DoReferenceTransactionCall Failed');
                wcf()->logger->log(print_r($response, true));
                $is_successful = false;
            } else {
                wcf()->logger->log(print_r($response, true));
                $is_successful = true;
                $this->store_offer_transaction($order, $response, $product);
            }
        } catch (Exception $e) {
            // translators: exception message.
            $order_note = sprintf(__('PayPal Exp Transaction Failed (%s)', 'cartflows-pro'), $e->getMessage());
        }
        return $is_successful;
    }

    /**
     * Store Offer Trxn Charge.
     *
     * @param WC_Order $order    The order that is being paid for.
     * @param Object   $response The response that is send from the payment gateway.
     * @param array    $product  Product data.
     */
    public function store_offer_transaction($order, $response, $product) {

        $order_id = $order->get_id();
        $txn_id = '';

        if (!isset($response['PAYMENTINFO_0_TRANSACTIONID'])) {
            $txn_id = $response['TRANSACTIONID'];
        } else {
            $txn_id = $response['PAYMENTINFO_0_TRANSACTIONID'];
        }

        $order->update_meta_data('cartflows_offer_txn_resp_' . $product['step_id'], $txn_id);
        $order->save();
    }

    /**
     * Charge a payment against a reference token.
     *
     * @param string   $reference_id the ID of a reference object, e.g. billing agreement ID.
     * @param WC_Order $order order object.
     * @param array    $args arguments data.
     * @param array    $offer_product offer product data.
     * @since 1.0.0
     */
    public function add_reference_trans_args($reference_id, $order, $args = array(), $offer_product) {

        $defaults = array(
            'amount' => $offer_product['total'],
            'payment_type' => 'Any',
            'payment_action' => 'Sale',
            'return_fraud_filters' => 1,
            'notify_url' => WC()->api_request_url('WC_Gateway_Paypal'),
            'invoice_number' => $order->get_id() . '-' . $offer_product['step_id'],
        );

        $args = wp_parse_args($args, $defaults);

        $this->set_method('DoReferenceTransaction');

        // Set base params.
        $this->add_parameters(
                array(
                    'REFERENCEID' => $reference_id,
                    'RETURNFMFDETAILS' => $args['return_fraud_filters'],
                )
        );

        $this->add_payment_params($order, $offer_product['step_id'], $args['payment_action'], true, true, $args['variation_id'], $args['input_qty']);
    }

    /**
     * Limits description to 120 characters.
     *
     * @param string $description limit description.
     * @return string
     * @since 1.0.0
     */
    private function limit_description($description) {

        $description = substr($description, 0, 120);

        return $description;
    }

    /**
     * Get billing agreement ID for paypal express.
     *
     * @since 1.0.0
     *
     * @param array $order order data.
     *
     * @return string
     */
    public function get_token($order) {

        $get_id = $order->get_id();

        $token = $order->get_meta('BILLINGAGREEMENTID');
        if ('' == $token) {
            $token = get_post_meta($get_id, 'BILLINGAGREEMENTID', true);
        }
        if (!empty($token)) {
            return $token;
        }

        return false;
    }

    /**
     * Remove line items
     *
     * @since 1.0.0
     *
     * @param array $array object.
     *
     * @return array
     */
    public function remove_previous_line_items($array) {

        if (is_array($array) && count($array) > 0) {
            foreach ($array as $key => $val) {
                if (false !== strpos(strtoupper($key), 'L_')) {
                    unset($array[$key]);
                }
            }
        }

        return $array;
    }

    /**
     * Is gateway support offer refund?
     *
     * @return bool
     */
    public function is_api_refund() {

        return $this->is_api_refund;
    }

    /**
     * Process offer refund.
     *
     * @param object $order the order object.
     * @param array  $offer_data offer data.
     *
     * @return bool
     */
    public function process_offer_refund($order, $offer_data) {

        $order_id = $offer_data['order_id'];
        $transaction_id = $offer_data['transaction_id'];
        $refund_amount = $offer_data['refund_amount'];
        $refund_reason = $offer_data['refund_reason'];
        $response_id = false;

        if (!is_null($refund_amount) && class_exists('WC_Gateway_Paypal')) {

            $available_gateways = WC()->payment_gateways->payment_gateways();

            if (isset($available_gateways['paypal'])) {

                if (!class_exists('WC_Gateway_Paypal_API_Handler')) {
                    include_once wc()->plugin_path() . '/includes/gateways/paypal/includes/class-wc-gateway-paypal-api-handler.php';
                }

                $environment = $this->get_wc_gateway()->get_option('environment', 'live');
                $test_mode = ( 'live' !== $environment );

                WC_Gateway_Paypal_API_Handler::$api_username = $test_mode ? $this->get_wc_gateway()->get_option('sandbox_api_username') : $this->get_wc_gateway()->get_option('api_username');
                WC_Gateway_Paypal_API_Handler::$api_password = $test_mode ? $this->get_wc_gateway()->get_option('sandbox_api_password') : $this->get_wc_gateway()->get_option('api_password');
                WC_Gateway_Paypal_API_Handler::$api_signature = $test_mode ? $this->get_wc_gateway()->get_option('sandbox_api_signature') : $this->get_wc_gateway()->get_option('api_signature');
                WC_Gateway_Paypal_API_Handler::$sandbox = $test_mode;

                $result = WC_Gateway_Paypal_API_Handler::refund_transaction($order, $refund_amount, $refund_reason);

                if (is_wp_error($result)) {
                    wcf()->logger->log(
                            "Paypal offer refund failed. Order: {$order_id}, Error: " .
                            wp_json_encode($result->get_error_message())
                    );
                } else {
                    switch (strtolower($result->ACK)) { //phpcs:ignore
                        case 'success':
                        case 'successwithwarning':
                            $response_id = $result->REFUNDTRANSACTIONID; //phpcs:ignore
                    }
                }
                if (isset($result->L_LONGMESSAGE0)) { //phpcs:ignore
                    wcf()->logger->log(
                            'Paypal Express Checkout offer refund error message: ' .
                            wp_json_encode($result->L_LONGMESSAGE0) //phpcs:ignore
                    );
                }
            }

            wcf()->logger->log('Paypal Express Checkout offer refund response id: ' . $response_id);
        }

        return $response_id;
    }

    /**
     * Get WooCommerce payment geteways.
     *
     * @return array
     */
    public function get_wc_gateway() {

        global $woocommerce;

        $gateways = $woocommerce->payment_gateways->payment_gateways();

        return $gateways[$this->key];
    }

    /**
     * Setup the Payment data for Paypal Automatic Subscription.
     *
     * @param WC_Subscription $subscription An instance of a subscription object.
     * @param object          $order Object of order.
     * @param array           $offer_product array of offer product.
     */
    public function add_subscription_payment_meta_for_paypal_express($subscription, $order, $offer_product) {

        if ('paypal_express' === $order->get_payment_method()) {
            $subscription_id = $subscription->get_id();
            update_post_meta($subscription_id, '_payment_tokens_id', $order->get_meta('BILLINGAGREEMENTID', true));
            update_post_meta($subscription_id, 'BILLINGAGREEMENTID', $order->get_meta('BILLINGAGREEMENTID', true));
        }
    }

    /**
     * Modify offer refund data.
     *
     * @param array  $request request.
     * @param object $order the order object.
     * @param float  $amount refund amount.
     *
     * @return object
     */
    public function offer_refund_request_data($request, $order, $amount) {

        if (isset($_POST['cartflows_refund'])) {

            $payment_method = $order->get_payment_method();

            if ($this->key === $payment_method) {

                wcf()->logger->log('Paypal Express Refund Request: ' . wp_json_encode($request));

                if (isset($_POST['transaction_id']) && !empty($_POST['transaction_id'])) { //phpcs:ignore
                    $request['TRANSACTIONID'] = sanitize_text_field(wp_unslash($_POST['transaction_id']));

                    $environment = $this->get_wc_gateway()->get_option('environment', 'live');

                    if ('live' === $environment) {
                        $request['USER'] = $this->get_wc_gateway()->get_option('api_username');
                        $request['PWD'] = $this->get_wc_gateway()->get_option('api_password');
                        $request['SIGNATURE'] = $this->get_wc_gateway()->get_option('api_signature');
                    } else {
                        $request['USER'] = $this->get_wc_gateway()->get_option('sandbox_api_username');
                        $request['PWD'] = $this->get_wc_gateway()->get_option('sandbox_api_password');
                        $request['SIGNATURE'] = $this->get_wc_gateway()->get_option('sandbox_api_signature');
                    }
                }

                wcf()->logger->log('Paypal Express Modified Refund Request: ' . wp_json_encode($request));
            }
        }

        return $request;
    }

}

/**
 *  Prepare if class 'Cartflows_Pro_Gateway_Paypal_Express' exist.
 *  Kicking this off by calling 'get_instance()' method
 */
Cartflows_Pro_Gateway_Paypal_Express::get_instance();
