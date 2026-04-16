<?php
if (!defined('WFACP_TEMPLATE_DIR')) {
    return '';
}
if (!defined('PFW_EXPRESS_DEBUG')) {
    define('PFW_EXPRESS_DEBUG', false);
}

if (apply_filters('wfacp_skip_form_printing', false)) {
    return;
}
if (!WFACP_Core()->public->is_checkout_override() && true == WC()->cart->is_empty()) {
    $product = WFACP_Core()->public->get_product_list();
    if (count($product) == 0) {
        wc_print_notice('Sorry, no product(s) added to checkout', 'error');
        return;
    }
}
$checkout = WC()->checkout();
add_filter('wfacp_autopopulate_fields', function () {
    return 'no';
});
add_filter('wfacp_autopopulatestate_fields', function () {
    return 'no';
});
do_action('wfacp_checkout_preview_form_start', $checkout);
$permalink = get_the_permalink();
?>
<div class="wfacp_main_form woocommerce">
    <?php
    do_action('outside_header');
    if (!$checkout->is_registration_enabled() && $checkout->is_registration_required() && !is_user_logged_in()) {
        echo apply_filters('woocommerce_checkout_must_be_logged_in_message', __('You must be logged in to checkout.', 'woocommerce'));
        return;
    }
    $payment_needed = false;
    $instance = wfacp_template();
    $checkout = WC()->checkout();
    // NOTE:
    // WooCommerce's WC_Countries::get_formatted_address() trims all received values.
    // In PHP 8.1+, trim(null) emits a deprecation warning.
    // FunnelKit/PayPal details arrays can contain nullable or extra non-address fields,
    // so we pass a strict address-only payload and normalize null/non-scalar values to ''.
    $normalize_address_details = static function ($details) {
        $source = is_array($details) ? $details : [];
        $keys = [
            'first_name',
            'last_name',
            'company',
            'address_1',
            'address_2',
            'city',
            'state',
            'postcode',
            'country',
        ];
        $details = [];

        foreach ($keys as $key) {
            if (!array_key_exists($key, $source) || is_null($source[$key])) {
                $details[$key] = '';
            } elseif (is_scalar($source[$key])) {
                $details[$key] = (string) $source[$key];
            } else {
                $details[$key] = '';
            }
        }

        return $details;
    };

    $normalized_billing_details = $normalize_address_details(WFACP_Core()->public->billing_details);
    $normalized_shipping_details = $normalize_address_details(WFACP_Core()->public->shipping_details);
    $fieldsets = $instance->get_fieldsets();
    if (!is_array($fieldsets)) {
        return;
    }
    $checkout_fields = $instance->get_checkout_fields();
    $current_step = $instance->get_current_step();
    $selected_template_slug = $instance->get_template_slug();
    $template_type = $instance->get_template_type();
    $phone_number_present = [];
    if (isset($checkout_fields['billing']) && isset($checkout_fields['billing']['billing_phone'])) {
        $phone_number_present = $checkout_fields['billing']['billing_phone'];
    }
    include_once WFACP_TEMPLATE_COMMON . '/form_internal_css.php';
    add_filter('wfacp_print_shipping_hidden_fields', '__return_false');
    do_action('woocommerce_before_checkout_form', $checkout);
    ?>
    <style>
        .wfacp_shipping_fields {
            display: none;
        }

        .wfacp_shipping_fields.wfacp_shipping_field_hide {
            display: block !important;
        }

        .wfacp_billing_fields.wfacp_billing_field_hide {
            display: block !important;
        }

        .wfacp_address_container .wfacp_express_billing_address {
            display: none;
            margin-bottom: 15px;
        }
        .wfacp_address_container .wfacp_express_shipping_address {
            display: none;
            margin-bottom: 15px;
        }
        .woocommerce-checkout .wfacp_payment {
            display: block;
        }
        .wfacp_express_formatted_address {
            margin-bottom: 25px;
        }
        .wfacp_express_formatted_billing_address {
            float: left;
            width: 47%;
            margin-right: 3%;
        }
        .wfacp_express_formatted_shipping_address {
            float: left;
            width: 47%;
        }
        .wfacp_express_formatted_address h3 {
            display: inline-block;
            color: #333;
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .wfacp_express_billing_address p.wfacp-form-control-wrapper, .wfacp_express_shipping_address p.wfacp-form-control-wrapper {
            padding: 0 12px 0 0
        }
        .wfacp_express_billing_address p.wfacp-form-control-wrapper label, .wfacp_express_shipping_address p.wfacp-form-control-wrapper label {
            left: 12px
        }
        .wfacp_express_billing_address h3, .wfacp_express_shipping_address h3 {
            display: block;
            color: #333;
            font-size: 16px;
            font-weight: bold;
            margin-top: 0
        }
        .wfacp_express_formatted_address address {
            font-style: normal;
        }
        #wfacp-e-form .wfacp_main_form.woocommerce #shipping_calculator_field.wfacp-form-control-wrapper {
            padding: 0;
        }
        .wfacp_shipping_options {
            padding: 0 0px;
        }

        @media (max-width: 599px) {
            .wfacp_express_formatted_billing_address, .wfacp_express_formatted_shipping_address {
                width: 100%;
                margin: 0;
                float: none
            }
        }
    </style>
    <?php
    // Diagnostic dump for the PayPal-express FunnelKit Aero review page.
    // Enable with `define('PFW_EXPRESS_DEBUG', true);` in wp-config.php on
    // the affected site. Shows raw PayPal checkout_details, the mapped
    // billing/shipping arrays, WFACP_Core's public detail arrays, the
    // WC()->customer snapshot and relevant $_POST keys — so we can see
    // exactly where the data is coming in and where it is dropping.
    if (defined('PFW_EXPRESS_DEBUG') && PFW_EXPRESS_DEBUG) {
        $pfw_dbg_checkout_details = null;
        if (class_exists('AngellEye_Session_Manager')) {
            $pfw_dbg_checkout_details = AngellEye_Session_Manager::get('paypal_transaction_details');
        }
        $pfw_dbg_customer = null;
        if (function_exists('WC') && WC()->customer) {
            $pfw_dbg_customer = array(
                'billing_first_name' => WC()->customer->get_billing_first_name(),
                'billing_last_name' => WC()->customer->get_billing_last_name(),
                'billing_email' => WC()->customer->get_billing_email(),
                'billing_address_1' => WC()->customer->get_billing_address_1(),
                'billing_address_2' => WC()->customer->get_billing_address_2(),
                'billing_city' => WC()->customer->get_billing_city(),
                'billing_state' => WC()->customer->get_billing_state(),
                'billing_postcode' => WC()->customer->get_billing_postcode(),
                'billing_country' => WC()->customer->get_billing_country(),
                'billing_phone' => WC()->customer->get_billing_phone(),
                'shipping_first_name' => WC()->customer->get_shipping_first_name(),
                'shipping_last_name' => WC()->customer->get_shipping_last_name(),
                'shipping_address_1' => WC()->customer->get_shipping_address_1(),
                'shipping_address_2' => WC()->customer->get_shipping_address_2(),
                'shipping_city' => WC()->customer->get_shipping_city(),
                'shipping_state' => WC()->customer->get_shipping_state(),
                'shipping_postcode' => WC()->customer->get_shipping_postcode(),
                'shipping_country' => WC()->customer->get_shipping_country(),
            );
        }
        $pfw_dbg_post = array();
        foreach (array('billing_first_name', 'billing_last_name', 'billing_email', 'billing_address_1', 'billing_city', 'billing_state', 'billing_postcode', 'billing_country', 'billing_phone', 'shipping_first_name', 'shipping_last_name', 'shipping_address_1', 'shipping_city', 'shipping_state', 'shipping_postcode', 'shipping_country') as $pfw_dbg_key) {
            if (isset($_POST[$pfw_dbg_key])) {
                $pfw_dbg_post[$pfw_dbg_key] = $_POST[$pfw_dbg_key];
            }
        }
        ?>
        <div style="background:#fff8dc;border:2px solid #d4a017;padding:12px;margin:12px 0;font-family:monospace;font-size:11px;line-height:1.4;max-height:600px;overflow:auto;">
            <strong style="font-size:13px;color:#a00;">PFW PayPal Express Debug Dump</strong>
            <p style="margin:8px 0;">Remove <code>define('PFW_EXPRESS_DEBUG', true);</code> from wp-config.php when done.</p>
            <details><summary><strong>1. Raw PayPal checkout_details (from session)</strong></summary><pre><?php echo esc_html(print_r($pfw_dbg_checkout_details, true)); ?></pre></details>
            <details open><summary><strong>2. WFACP_Core()->public->billing_details (what template reads for billing form)</strong></summary><pre><?php echo esc_html(print_r(WFACP_Core()->public->billing_details, true)); ?></pre></details>
            <details open><summary><strong>3. WFACP_Core()->public->shipping_details (what template reads for shipping form)</strong></summary><pre><?php echo esc_html(print_r(WFACP_Core()->public->shipping_details, true)); ?></pre></details>
            <details open><summary><strong>4. WC()->customer snapshot</strong></summary><pre><?php echo esc_html(print_r($pfw_dbg_customer, true)); ?></pre></details>
            <details><summary><strong>5. $_POST billing/shipping keys</strong></summary><pre><?php echo esc_html(print_r($pfw_dbg_post, true)); ?></pre></details>
            <details><summary><strong>6. Normalized billing (for summary card at top)</strong></summary><pre><?php echo esc_html(print_r($normalized_billing_details, true)); ?></pre></details>
            <details><summary><strong>7. Normalized shipping (for summary card at top)</strong></summary><pre><?php echo esc_html(print_r($normalized_shipping_details, true)); ?></pre></details>
        </div>
        <?php
    }
    ?>
    <form name="checkout" method="post" class="checkout woocommerce-checkout wfacp_paypal_express" action="<?php echo esc_url(get_the_permalink()); ?>" enctype="multipart/form-data" id="wfacp_checkout_form">
        <input type="hidden" name="_wfacp_post_id" class="_wfacp_post_id" value="<?php echo WFACP_Common::get_id(); ?>">
        <input type="hidden" name="wfacp_cart_hash" value="<?php esc_html_e(WC()->session->get('wfacp_cart_hash', '')); ?>">
        <input type="hidden" name="wfacp_has_active_multi_checkout" id="wfacp_has_active_multi_checkout" value="">
        <input type="hidden" id="product_switcher_need_refresh" name="product_switcher_need_refresh" value="0">
        <input type="hidden" id="wfacp_exchange_keys" name="wfacp_exchange_keys" class="wfacp_exchange_keys" value="">
        <input type="hidden" id="wfacp_input_hidden_data" name="wfacp_input_hidden_data" class="wfacp_input_hidden_data" value="{}">
        <input type="hidden" id="wfacp_input_phone_field" name="wfacp_input_phone_field" class="wfacp_input_phone_field" value="{}">
        <input type="hidden" id="wfacp_timezone" name="wfacp_timezone" value="">
        <?php do_action('wfacp_before_checkout_form_fields', $checkout); 
        do_action( 'woocommerce_checkout_before_customer_details' );
        ?>
        
        <div class="wfacp-section  wfacp-hg-by-box">
            <div class="wfacp-comm-title">
                <h2 class="wfacp_section_heading wfacp_section_title">
                    <?php
                    $confirm_order_title = apply_filters('wfacp_comfirm_your_paypal_order_title', __('Confirm your PayPal order', 'woofunnels-aero-checkout'));
                    echo apply_filters('wfacp_comfirm_your_paypal_order_title', $confirm_order_title);
                    ?>
                </h2>
            </div>
            <div class="wfacp_express_formatted_address clearfix">
                <div class="wfacp_express_formatted_billing_address">
                    <h3><?php _e('Billing details', 'paypal-for-woocommerce'); ?></h3>
                    <?php
                    if (WFACP_Core()->public->paypal_billing_address) {
                        ?>
                        <div>
                            <strong><?php _e('Address', 'paypal-for-woocommerce'); ?></strong>
                            <address>
                                <?php
                                $formatted_address = WC()->countries->get_formatted_address($normalized_billing_details);
                                $formatted_address = str_replace('<br/>-<br/>', '<br/>', $formatted_address);
                                echo $formatted_address;
                                $formatted_address = '';
                                ?>
                            </address>
                            <?php
                            echo!empty(WFACP_Core()->public->billing_details['email']) ? '<p>' . WFACP_Core()->public->billing_details['email'] . '</p>' : '';
                            echo!empty(WFACP_Core()->public->billing_details['phone']) ? '<p>' . WFACP_Core()->public->billing_details['phone'] . '</p>' : '';
                            ?>
                        </div>
                        <?php
                    } else {
                        do_action('wfacp_express_checkout_paypal_billing_address_not_present');
                    }
                    ?>
                    <?php
                    if ($instance->have_billing_address()) {
                        ?>
                        <a href="#" class="wfacp_edit_address" data-type="billing"><?php _e('Edit', 'paypal-for-woocommerce'); ?></a>
                        <?php
                    }
                    ?>
                </div>
                <div class="wfacp_express_formatted_shipping_address">
                    <h3><?php _e('Shipping details', 'paypal-for-woocommerce'); ?></h3>
                    <div>
                        <strong><?php _e('Address', 'paypal-for-woocommerce'); ?></strong>
                        <address>
                            <?php
                            $formatted_address = WC()->countries->get_formatted_address($normalized_shipping_details);
                            $formatted_address = str_replace('<br/>-<br/>', '<br/>', $formatted_address);
                            echo $formatted_address;
                            $formatted_address = '';
                            ?>
                        </address>
                        <?php
                        echo!empty(WFACP_Core()->public->shipping_details['email']) ? '<p class="angelleye-woocommerce-customer-details-email">' . WFACP_Core()->public->shipping_details['email'] . '</p>' : '';
                        echo!empty(WFACP_Core()->public->shipping_details['phone']) ? '<p class="angelleye-woocommerce-customer-details-phone">' . WFACP_Core()->public->shipping_details['phone'] . '</p>' : '';
                        if ($instance->have_shipping_address()) {
                            ?>
                            <a href="#" class="wfacp_edit_address" data-type="shipping"><?php _e('Edit', 'paypal-for-woocommerce'); ?></a>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="wfacp-comm-form-detail clearfix">
                <div class="wfacp_address_container">
                    <?php
                    if ($instance->have_billing_address()) {
                        ?>
                        <div class="wfacp_express_billing_address clearfix">
                            <h3><?php _e('Billing Address', 'paypal-for-woocommerce'); ?></h3>
                            <?php
                            $fields = $checkout->get_checkout_fields('billing');
                            if (defined('PFW_EXPRESS_DEBUG') && PFW_EXPRESS_DEBUG) {
                                echo '<div style="background:#e3f2fd;border:1px solid #0288d1;padding:8px;margin:8px 0;font-family:monospace;font-size:11px;"><strong>In-loop BILLING debug</strong>';
                                echo '<br>$_POST[billing_city] at loop entry = <code>' . esc_html(isset($_POST['billing_city']) ? $_POST['billing_city'] : '(unset)') . '</code>';
                                echo '<br>$_POST[billing_postcode] at loop entry = <code>' . esc_html(isset($_POST['billing_postcode']) ? $_POST['billing_postcode'] : '(unset)') . '</code>';
                                echo '<br>billing_details[city] at loop entry = <code>' . esc_html(isset(WFACP_Core()->public->billing_details['city']) ? WFACP_Core()->public->billing_details['city'] : '(unset)') . '</code>';
                                echo '<br>billing_details[postcode] at loop entry = <code>' . esc_html(isset(WFACP_Core()->public->billing_details['postcode']) ? WFACP_Core()->public->billing_details['postcode'] : '(unset)') . '</code>';
                                echo '<br>customer.get_billing_city() = <code>' . esc_html(WC()->customer->get_billing_city()) . '</code>';
                                echo '</div>';
                            }
                            foreach ($fields as $key => $field) {
                                if ('billing_same_as_shipping' == $key) {
                                    continue;
                                }
                                $field = apply_filters('wfacp_forms_field', $field, $key);
                                if (isset($field['country_field'], $fields[$field['country_field']])) {
                                    $field['country'] = $checkout->get_value($field['country_field']);
                                }
                                $temp_vl = str_replace('billing_', '', $key);
                                if (isset(WFACP_Core()->public->billing_details[$temp_vl])) {
                                    $value = WFACP_Core()->public->billing_details[$temp_vl];
                                } else {
                                    $value = $checkout->get_value($key);
                                }
                                if (defined('PFW_EXPRESS_DEBUG') && PFW_EXPRESS_DEBUG && in_array($key, array('billing_city', 'billing_postcode', 'billing_country', 'billing_phone', 'billing_state'), true)) {
                                    $pfw_before_filter = $value;
                                }
                                $value = apply_filters('wfacp_default_values', $value, $key, $field);
                                if (defined('PFW_EXPRESS_DEBUG') && PFW_EXPRESS_DEBUG && in_array($key, array('billing_city', 'billing_postcode', 'billing_country', 'billing_phone', 'billing_state'), true)) {
                                    echo '<div style="background:#fce4ec;border:1px solid #c2185b;padding:4px;margin:2px 0;font-family:monospace;font-size:11px;"><strong>' . esc_html($key) . '</strong> before wfacp_default_values filter: <code>' . esc_html((string) $pfw_before_filter) . '</code> | after filter: <code>' . esc_html((string) $value) . '</code></div>';
                                }
                                woocommerce_form_field($key, $field, $value);
                            }
                            ?>
                        </div>
                        <p class="form-row wfacp-form-control-wrapper wfacp-col-full  wfacp_checkbox_field wfacp-anim-wrap woocommerce-validated" id="billing_same_as_shipping_field" style="display: none">
                            <input type="checkbox" class="input-checkbox wfacp-form-control" name="billing_same_as_shipping" id="billing_same_as_shipping" value="1" checked style="display: none">
                        </p>
                        <?php
                    }
                    if ($instance->have_shipping_address()) {
                        ?>
                        <div class="wfacp_express_shipping_address clearfix">
                            <h3><?php _e('Shipping Address', 'paypal-for-woocommerce'); ?></h3>
                            <?php
                            $fields = $checkout->get_checkout_fields('shipping');
                            foreach ($fields as $key => $field) {
                                if ('shipping_same_as_billing' == $key) {
                                    continue;
                                }
                                $field = apply_filters('wfacp_forms_field', $field, $key);
                                if (isset($field['country_field'], $fields[$field['country_field']])) {
                                    $field['country'] = $checkout->get_value($field['country_field']);
                                }

                                $temp_vl = str_replace('shipping_', '', $key);
                                if (isset(WFACP_Core()->public->shipping_details[$temp_vl])) {
                                    $value = WFACP_Core()->public->shipping_details[$temp_vl];
                                } else {
                                    $value = $checkout->get_value($key);
                                }
                                $value = apply_filters('wfacp_default_values', $value, $key, $field);
                                woocommerce_form_field($key, $field, $value);
                            }
                            ?>
                            <div id='ship-to-different-address'>
                                <input id="ship-to-different-address-checkbox" class="ship_to_different_address" type="checkbox" name="ship_to_different_address" style="display:none" checked>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                    <div class='wfacp_advanced_fields wfacp-row'>
                        <?php
                        if (!empty($phone_number_present)) {
                            if (!isset($checkout_fields['advanced'])) {
                                $checkout_fields['advanced'] = [];
                            }
                            $phone_number_present['is_wfacp_field'] = true;
                            $checkout_fields['advanced'] = $checkout_fields['advanced'];
                        }
                        if (isset($checkout_fields['advanced'])) {
                            $fields = $checkout_fields['advanced'];
                            foreach ($fields as $key => $field) {
                                if ((!isset($field['is_wfacp_field']) || 'wfacp_html' == $field['type'])) {
                                    continue;
                                }
                                $field = apply_filters('wfacp_forms_field', $field, $key);
                                $value = '';
                                $temp_key = str_replace('billing_', '', $key);
                                if (isset(WFACP_Core()->public->billing_details[$temp_key])) {
                                    $value = WFACP_Core()->public->billing_details[$temp_key];
                                }
                                $temp_key = str_replace('shipping_', '', $key);
                                if ('' == $value && isset(WFACP_Core()->public->shipping_details[$temp_key])) {
                                    $value = WFACP_Core()->public->shipping_details[$temp_key];
                                }
                                if ('' == $value) {
                                    $value = $checkout->get_value($key);
                                }
                                $value = apply_filters('wfacp_default_values', $value, $key, $field);
                                woocommerce_form_field($key, $field, $value);
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php
            include WFACP_TEMPLATE_COMMON . '/account.php';
            if (isset($checkout_fields['advanced'])) {
                if (isset($checkout_fields['advanced']['shipping_calculator'])) {
                    ?>
                    <div class="wfacp-comm-form-detail clearfix">
                        <?php woocommerce_form_field('shipping_calculator', $checkout_fields['advanced']['shipping_calculator']); ?>
                    </div>
                    <?php
                }
                if (isset($checkout_fields['advanced']['order_summary'])) {
                    ?>
                    <div class="wfacp-comm-form-detail clearfix">
                        <?php woocommerce_form_field('order_summary', $checkout_fields['advanced']['order_summary']); ?>
                    </div>
                    <?php
                }
                if (isset($checkout_fields['advanced']['order_comments'])) {
                    ?>
                    <div class="wfacp-comm-form-detail clearfix">
                        <?php woocommerce_form_field('order_comments', $checkout_fields['advanced']['order_comments']); ?>
                    </div>
                    <?php
                }
            }
            do_action('wfacp_before_payment_section');
            include WFACP_TEMPLATE_COMMON . '/payment.php';
            do_action('wfacp_after_payment_section');
            ?>
        </div>
        <input type="hidden" id="wfacp_source" name="wfacp_source" value="<?php echo esc_url($permalink); ?>">
        <input type="hidden" id="wfacp_exchange_keys" name="wfacp_exchange_keys" class="wfacp_exchange_keys" value="">
        <input type="hidden" id="wfacp_input_hidden_data" name="wfacp_input_hidden_data" class="wfacp_input_hidden_data" value="{}">
    </form>
    <?php do_action('woocommerce_after_checkout_form', $checkout); ?>
</div>
<?php
do_action('wfacp_checkout_preview_form_end', $checkout);
