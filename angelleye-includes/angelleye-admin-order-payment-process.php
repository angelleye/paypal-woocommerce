<?php

class AngellEYE_Admin_Order_Payment_Process {

    public $gateway;
    public $payment_method;
    public $credentials;
    public $paypal;
    public $utility;
    public $gateway_calculation;
    public $gateway_settings;
    public $confirm_order_id;

    public function __construct() {
        if (is_admin() && !defined('DOING_AJAX')) {
            add_action('add_meta_boxes', array($this, 'angelleye_add_meta_box'), 10);
            add_action('woocommerce_process_shop_order_meta', array($this, 'angelleye_admin_create_reference_order'), 10, 2);
            add_action('woocommerce_process_shop_order_meta', array($this, 'angelleye_admin_order_process_payment'), 10, 2);
            add_action('angelleye_admin_create_reference_order_action_hook', array($this, 'angelleye_admin_create_reference_order_action'), 10, 1);
            add_action('angelleye_admin_order_process_payment_action_hook', array($this, 'angelleye_admin_order_process_payment_action'), 10, 1);
        }
    }

    public function angelleye_add_meta_box() {
        add_meta_box('angelleye_admin_order_payment_process', __('Reference Transaction', 'paypal-for-woocommerce'), array($this, 'admin_order_payment_process'), 'shop_order', 'side', 'default');
        add_meta_box('angelleye_admin_order_reference_order', __('Reference Transaction', 'paypal-for-woocommerce'), array($this, 'admin_order_reference_order'), 'shop_order', 'side', 'default');
    }

    public function angelleye_hide_reference_order_metabox() {
        ?>
        <style type="text/css">
            #angelleye_admin_order_reference_order {
                display: none;
            }
            label[for="angelleye_admin_order_reference_order-hide"] {
                display: none;
             }
        </style>
        <?php
    }

    public function angelleye_show_reference_order_metabox() {
        ?>
        <style type="text/css">
            #angelleye_admin_order_reference_order {
                display: block;
            }
            label[for="angelleye_admin_order_reference_order-hide"] {
                display: inline;
             }
        </style>
        <?php
    }

    public function angelleye_hide_order_payment_metabox() {
        ?>
        <style type="text/css">
            #angelleye_admin_order_payment_process {
                display: none;
            }
            label[for="angelleye_admin_order_payment_process-hide"] {
                display: none;
             }
        </style>
        <?php
    }

    public function angelleye_show_order_payment_metabox() {
        ?>
        <style type="text/css">
            #angelleye_admin_order_payment_process {
                display: block;
            }
            label[for="angelleye_admin_order_payment_process-hide"] {
                display: inline;
             }
        </style>
        <?php
    }

    public function admin_order_reference_order($post) {
        $is_disable_button = false;
        $order_id = $post->ID;
        $order = wc_get_order($order_id);
        if ($this->angelleye_is_order_need_payment($order) && $this->angelleye_is_admin_order_payment_method_available($order) == true && $this->angelleye_is_order_created_by_create_new_reference_order($order) == false) {
            $reason_array = $this->angelleye_get_reason_why_create_reference_transaction_order_button_not_available($order);
            if (count($reason_array) > 1) {
                $is_disable_button = true;
            }
            $reason_message = $this->angelleye_reason_array_to_nice_message($reason_array);
            $this->angelleye_create_order_button($reason_message, $is_disable_button);
            $this->angelleye_show_reference_order_metabox();
        } else {
            $this->angelleye_hide_reference_order_metabox();
        }
    }

    public function admin_order_payment_process($post) {
        $is_disable_button = false;
        $order_id = $post->ID;
        $order = wc_get_order($order_id);
        if ($this->angelleye_is_order_created_by_create_new_reference_order($order) && $this->angelleye_is_order_status_pending($order) == true) {
            $reason_array = $this->angelleye_get_reason_why_process_reference_transaction_button_not_available($order);
            if (count($reason_array) > 1) {
                $is_disable_button = true;
            }
            $reason_message = $this->angelleye_reason_array_to_nice_message($reason_array);
            $this->angelleye_place_order_button($reason_message, $is_disable_button);
            $this->angelleye_show_order_payment_metabox();
        } else {
            $this->angelleye_hide_order_payment_metabox();
        }
    }

    public function angelleye_place_order_button($reason_message, $is_disable_button) {
        $is_disable = '';
        if ($is_disable_button == true) {
            $is_disable = 'disabled';
        }
        echo '<div class="wrap angelleye_admin_payment_process">' . $reason_message . '<input type="hidden" name="angelleye_admin_order_payment_process_sec" value="' . wp_create_nonce('angelleye_admin_order_payment_process_sec') . '" /><input type="submit" ' . $is_disable . ' id="angelleye_admin_order_payment_process_submit_button" value="Process Reference Transaction" name="angelleye_admin_order_payment_process_submit_button" class="button button-primary"></div>';
    }

    public function angelleye_create_order_button($reason_message, $is_disable_button) {
        $is_disable = '';
        if ($is_disable_button == true) {
            $is_disable = 'disabled';
        }
        $checkbox = '<br><label><input type="checkbox" name="copy_items_to_new_invoice">Copy items to new order?</label><br>';
        echo '<div class="wrap angelleye_create_reference_order_section">' . $reason_message . '<input type="hidden" name="angelleye_create_reference_order_sec" value="' . wp_create_nonce('angelleye_create_reference_order_sec') . '" /><input type="submit" ' . $is_disable . ' id="angelleye_create_reference_order_submit_button" value="Create Reference Transaction Order" name="angelleye_create_reference_order_submit_button" class="button button-primary">' . $checkbox . '</div>';
    }

    public function angelleye_is_order_status_pending($order) {
        return ($order->get_status() == 'pending') ? true : false;
    }

    public function angelleye_admin_create_reference_order($post_id, $post) {
        if (!empty($_POST['angelleye_create_reference_order_submit_button']) && $_POST['angelleye_create_reference_order_submit_button'] == 'Create Reference Transaction Order') {
            if (wp_verify_nonce($_POST['angelleye_create_reference_order_sec'], 'angelleye_create_reference_order_sec')) {
                if (empty($post_id)) {
                    return false;
                }
                if ($post->post_type != 'shop_order') {
                    return false;
                }
                $order = wc_get_order($post_id);
                do_action('angelleye_admin_create_reference_order_action_hook', $order);
            }
        }
    }

    public function angelleye_admin_order_process_payment($post_id, $post) {
        if (!empty($_POST['angelleye_admin_order_payment_process_submit_button']) && $_POST['angelleye_admin_order_payment_process_submit_button'] == 'Process Reference Transaction') {
            if (wp_verify_nonce($_POST['angelleye_admin_order_payment_process_sec'], 'angelleye_admin_order_payment_process_sec')) {
                if (empty($post_id)) {
                    return false;
                }
                if ($post->post_type != 'shop_order') {
                    return false;
                }
                $order = wc_get_order($post_id);
                do_action('angelleye_admin_order_process_payment_action_hook', $order);
            }
        }
    }

    public function angelleye_admin_create_reference_order_action($order) {
        $this->payment_method = version_compare( WC_VERSION, '3.0', '<' ) ? $order->payment_method : $order->get_payment_method();
        if (in_array($this->payment_method, array('paypal_express', 'paypal_pro', 'paypal_pro_payflow'))) {
            $this->angelleye_admin_create_new_order($order);
        }
        remove_action('woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Data::save', 40, 2);
    }

    public function angelleye_admin_order_process_payment_action($order) {
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        $this->payment_method = version_compare( WC_VERSION, '3.0', '<' ) ? $order->payment_method : $order->get_payment_method();
        switch ($this->payment_method) {
            case 'paypal_express': {
                    $this->angelleye_ec_pp_pf_reference_transaction($order);
                }
                break;
            case 'paypal_pro': {
                    $this->angelleye_ec_pp_pf_reference_transaction($order);
                }
                break;
            case 'paypal_pro_payflow': {
                    $this->angelleye_paypal_pro_payflow_reference_transaction($order);
                }
                break;
        }
        update_post_meta($order_id, '_created_via', 'admin_order_process_payment');
        remove_action('woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Data::save', 40, 2);
    }

    public function angelleye_paypal_pro_payflow_reference_transaction($order) {
        $token_id = $this->get_usable_reference_transaction($order);
        if (!empty($token_id)) {
            $this->angelleye_load_payment_method_setting($order);
            if (class_exists('WC_Gateway_PayPal_Pro_PayFlow_AngellEYE')) {
                $paypal_pro_payflow = new WC_Gateway_PayPal_Pro_PayFlow_AngellEYE();
                $paypal_pro_payflow->process_subscription_payment($order, $amount = '', $token_id);
            }
        }
    }

    public function angelleye_admin_create_new_order($order) {
        $shipping_first_name = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_first_name : $order->get_shipping_first_name();
        $shipping_last_name = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_last_name : $order->get_shipping_last_name();
        $shipping_address_1 = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_address_1 : $order->get_shipping_address_1();
        $shipping_address_2 = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_address_2 : $order->get_shipping_address_2();
        $shipping_city = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_city : $order->get_shipping_city();
        $shipping_state = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_state : $order->get_shipping_state();
        $shipping_postcode = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_postcode : $order->get_shipping_postcode();
        $shipping_country = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_country : $order->get_shipping_country();
        $shipping_company = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_company : $order->get_shipping_company();
        $billing_company = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_company : $order->get_billing_company();
        $billing_first_name = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_first_name : $order->get_billing_first_name();
        $billing_last_name = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_last_name : $order->get_billing_last_name();
        $billing_address_1 = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_address_1 : $order->get_billing_address_1();
        $billing_address_2 = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_address_2 : $order->get_billing_address_2();
        $billing_city = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_city : $order->get_billing_city();
        $billing_postcode = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_postcode : $order->get_billing_postcode();
        $billing_country = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_country : $order->get_billing_country();
        $billing_state = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_state : $order->get_billing_state();
        $billing_email = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_email : $order->get_billing_email();
        $billing_phone = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_phone : $order->get_billing_phone();
        $args = array(
            'customer_id' => $order->get_user_id(),
            'customer_note' => version_compare(WC_VERSION, '3.0', '<') ? wptexturize($order->customer_note) : wptexturize($order->get_customer_note()),
            'order_id' => 0,
        );
        $shipping_details = array(
            'first_name' => $shipping_first_name,
            'last_name' => $shipping_last_name,
            'company' => $shipping_company,
            'address_1' => $shipping_address_1,
            'address_2' => $shipping_address_2,
            'city' => $shipping_city,
            'state' => $shipping_state,
            'postcode' => $shipping_postcode,
            'country' => $shipping_country,
        );
        $billing_details = array(
            'first_name' => $billing_first_name,
            'last_name' => $billing_last_name,
            'company' => $billing_company,
            'address_1' => $billing_address_1,
            'address_2' => $billing_address_2,
            'city' => $billing_city,
            'state' => $billing_state,
            'postcode' => $billing_postcode,
            'country' => $billing_country,
            'email' => $billing_email,
            'phone' => $billing_phone,
        );
        $new_order = wc_create_order($args);
        $old_get_items = $order->get_items();
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        if ($old_wc) {
            foreach ($order->get_items() as $cart_item_key => $values) {
                wc_add_order_item_meta($values, $cart_item_key, $values);
            }
        } else {
            $new_order->add_item($old_get_items);
        }
        $new_order_id = version_compare(WC_VERSION, '3.0', '<') ? $new_order->id : $new_order->get_id();
        AngellEYE_Utility::angelleye_set_address($new_order_id, $shipping_details, 'shipping');
        AngellEYE_Utility::angelleye_set_address($new_order_id, $billing_details, 'billing');
        $this->payment_method = version_compare( WC_VERSION, '3.0', '<' ) ? $order->payment_method : $order->get_payment_method();
        if ($old_wc) {
            update_post_meta($new_order_id, '_payment_method', $this->payment_method);
        } else {
            $new_order->set_payment_method($this->payment_method);
        }
        $payment_method_title = version_compare( WC_VERSION, '3.0', '<' ) ? $order->payment_method_title : $order->get_payment_method_title();
        update_post_meta($new_order_id, '_payment_method_title', $payment_method_title);
        update_post_meta($new_order_id, '_created_via', 'create_new_reference_order');
        $token_id = $this->get_usable_reference_transaction($order);
        if (!empty($token_id)) {
            update_post_meta($new_order_id, '_first_transaction_id', $token_id);
        }
        if (!empty($_POST['copy_items_to_new_invoice']) && $_POST['copy_items_to_new_invoice'] == 'on') {
            $this->angelleye_update_order_meta($order, $new_order);
        }
        $order->add_order_note('Order Created: Create Reference Transaction Order', 0, false);
        if (!$old_wc) {
            $new_order->save();
        }
        $new_order->calculate_totals();
        wp_redirect(get_edit_post_link($new_order_id, 'url'));
        exit();
    }

    public function angelleye_update_order_meta($order, $new_order) {
        foreach ($order->get_items() as $item_id => $item) {
            $old_wc = version_compare(WC_VERSION, '3.0', '<');
            if ($old_wc) {
                $product = $order->get_product_from_item($item);
                $new_order->add_product($product, $item['qty']);
            } else {
                $product = $item->get_product();
                $new_order->add_product($product, $item['qty']);
            }
        }
    }

    public function angelleye_is_order_created_by_create_new_reference_order($order) {
        return ($this->angelleye_get_created_via($order) == 'create_new_reference_order' ) ? true : false;
    }

    public function angelleye_is_order_need_payment($order) {
        return ($order->get_total() > 0) ? true : false;
    }

    public function angelleye_is_order_payment_method_selected($order) {
        $this->payment_method = version_compare( WC_VERSION, '3.0', '<' ) ? $order->payment_method : $order->get_payment_method();
        return ($this->payment_method != '') ? true : false;
    }

    public function angelleye_is_admin_order_payment_method_available($order) {
        $this->payment_method = version_compare( WC_VERSION, '3.0', '<' ) ? $order->payment_method : $order->get_payment_method();
        if (in_array($this->payment_method, array('paypal_express', 'paypal_pro', 'paypal_pro_payflow'))) {
            return true;
        } else {
            return false;
        }
    }

    public function angelleye_get_reason_why_process_reference_transaction_button_not_available($order) {
        $reason_array = array();
        $token_id = $this->angelleye_is_usable_reference_transaction_avilable($order);
        if ($this->angelleye_is_order_payment_method_selected($order) == false) {
            $reason_array[] = __('Payment method is not available for payment process, Please select Payment method from Billing details section.', 'paypal-for-woocommerce');
        } else {
            if (empty($token_id) && $this->angelleye_is_order_user_selected($order) == true) {
                $reason_array[] = __('Payment Token Or Reference transaction ID is not available for payment process.', 'paypal-for-woocommerce');
            }
        }
        if ($this->angelleye_is_order_need_payment($order) == false) {
            $reason_array[] = __('Order total must be greater than zero to process a reference transaction.', 'paypal-for-woocommerce');
        }
        $reason_array[] = __("Make any necessary adjustments to the item(s) on the order and calculate totals.  Remember to click Update if any adjustments were made, and then click Process Reference Transaction.", 'paypal-for-woocommerce');
        return $reason_array;
    }

    public function angelleye_get_reason_why_create_reference_transaction_order_button_not_available($order) {
        $reason_array = array();
        $token_list = $this->angelleye_is_usable_reference_transaction_avilable($order);
        if ($this->angelleye_is_order_user_selected($order) == false) {
            //$reason_array[] = __('Customer must be selected for order.', 'paypal-for-woocommerce');
        }
        if ($this->angelleye_is_order_payment_method_selected($order) == false) {
            $reason_array[] = __('Payment method is not available for payment process, Please select Payment method from Billing details section.', 'paypal-for-woocommerce');
        } else {
            if (empty($token_list) && $this->angelleye_is_order_user_selected($order) == true) {
                $reason_array[] = __('Payment Token Or Reference transaction ID is not available for payment process.', 'paypal-for-woocommerce');
            }
        }
        if ($this->angelleye_is_order_need_payment($order) == false) {
            $reason_array[] = __('Order total must be greater than zero to process a reference transaction.', 'paypal-for-woocommerce');
        }
        return $reason_array;
    }

    public function angelleye_reason_array_to_nice_message($reason_array) {
        $reason_message = '';
        if (!empty($reason_array)) {
            $reason_message .= '<ul>';
            foreach ($reason_array as $key => $value) {
                $reason_message .= '<li>' . $value . '</li>';
            }
            $reason_message .= '</ul>';
        }
        return $reason_message;
    }

    public function angelleye_is_usable_reference_transaction_avilable($order) {
        $payment_token = $this->get_usable_reference_transaction($order);
        return (!empty($payment_token)) ? $payment_token : false;
    }

    public function get_usable_reference_transaction($order) {
        $this->payment_method = version_compare( WC_VERSION, '3.0', '<' ) ? $order->payment_method : $order->get_payment_method();
        $user_id = $order->get_user_id();
        if (in_array($this->payment_method, array('paypal_express', 'paypal_pro', 'paypal_pro_payflow'))) {
            return $this->angelleye_get_payment_token($user_id, $order);
        }
    }

    public function angelleye_get_payment_token($user_id, $order) {
        $this->payment_method = version_compare( WC_VERSION, '3.0', '<' ) ? $order->payment_method : $order->get_payment_method();
        if (in_array($this->payment_method, array('paypal_pro', 'paypal_pro_payflow'))) {
            $order_transaction_ids = $this->angelleye_get_transaction_id_by_order_id($order);
            if (!empty($order_transaction_ids)) {
                return $order_transaction_ids;
            }
            return $this->angelleye_get_customer_or_order_tokens($user_id, $order);
        } elseif ($this->payment_method == 'paypal_express') {
            $this->payment_method = version_compare( WC_VERSION, '3.0', '<' ) ? $order->payment_method : $order->get_payment_method();
            $customer_billing_agreement_id = get_user_meta($user_id, 'baid', true);
            if (!empty($customer_billing_agreement_id)) {
                return $customer_billing_agreement_id;
            }
            return $this->angelleye_get_customer_or_order_tokens($user_id, $order);
        } 
    }

    public function angelleye_get_customer_or_order_tokens($user_id, $order) {
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        $this->payment_method = version_compare( WC_VERSION, '3.0', '<' ) ? $order->payment_method : $order->get_payment_method();
        $order_tokens = WC_Payment_Tokens::get_order_tokens($order_id);
        if (!empty($order_tokens)) {
            return $this->angelleye_get_payment_token_list($order_tokens);
        }
        $customer_tokens = WC_Payment_Tokens::get_customer_tokens($user_id, $this->payment_method);
        if (!empty($customer_tokens)) {
            return $this->angelleye_get_payment_token_list($customer_tokens);
        }
        return false;
    }

    public function angelleye_get_payment_token_list($tokens) {
        foreach ($tokens as $key => $token) {
            return $token->get_token();
        }
    }
    
    public function angelleye_get_created_via($order) {
        return version_compare(WC_VERSION, '3.0', '<') ? $order->created_via : $order->get_created_via();
        
    }

    public function angelleye_is_order_created_by_admin($order) {
        return ($this->angelleye_get_created_via($order) == '') ? true : false;
    }

    public function angelleye_is_order_status_auto_draft($order) {
        return ($order->get_status() == 'auto-draft') ? true : false;
    }

    public function angelleye_is_order_user_selected($order) {
        return ($order->get_user_id() != '0') ? true : false;
    }

    public function is_display_admin_order_payment_process_box($order) {
        $this->payment_method = version_compare( WC_VERSION, '3.0', '<' ) ? $order->payment_method : $order->get_payment_method();
        if ($order->get_status() == 'pending' && $this->angelleye_get_created_via($order) == '' && $order->get_total() > 0 && $this->payment_method != '' && $order->get_user_id() != '0') {
            return true;
        } else {
            false;
        }
    }

    public function angelleye_get_transaction_id_by_payment_method($order, $payment_method) {
        global $wpdb;
        $tokens_array = array();
        $user_id = $order->get_user_id();
        $ids = $wpdb->get_results("SELECT id
		FROM $wpdb->posts AS posts
		LEFT JOIN {$wpdb->postmeta} AS meta on posts.ID = meta.post_id
                LEFT JOIN {$wpdb->postmeta} AS meta1 on posts.ID = meta1.post_id
                WHERE
		meta.meta_key = '_customer_user' AND   meta.meta_value = {$user_id} AND
                meta1.meta_key = '_payment_method' AND   meta1.meta_value = '{$payment_method}'
		AND   posts.post_type = 'shop_order'
		ORDER BY posts.ID DESC
	", ARRAY_A);
        if (!empty($ids)) {
            foreach ($ids as $key => $value) {
                $first_transaction_id = get_post_meta($value['id'], '_first_transaction_id', true);
                if (!empty($first_transaction_id)) {
                    $tokens_array[] = $first_transaction_id;
                }
                $transaction_id = $order->get_transaction_id();
                if (!empty($transaction_id)) {
                    $tokens_array[] = $transaction_id;
                }
            }
            $tokens_array = array_unique($tokens_array);
        }
        return $tokens_array;
    }

    public function angelleye_get_transaction_id_by_order_id($order) {
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        $transaction_id = $order->get_transaction_id();
        if (!empty($transaction_id)) {
            return $transaction_id;
        }
        $first_transaction_id = get_post_meta($order_id, '_first_transaction_id', true);
        if (!empty($first_transaction_id)) {
            return $first_transaction_id;
        }
        return false;
    }

    public function angelleye_load_payment_method_setting($order) {
        $this->payment_method = version_compare( WC_VERSION, '3.0', '<' ) ? $order->payment_method : $order->get_payment_method();
        if (WC()->payment_gateways()) {
            $payment_gateways = WC()->payment_gateways->payment_gateways();
            if (isset($payment_gateways[$this->payment_method])) {
                $this->gateway_settings = $payment_gateways[$this->payment_method]->settings;
                $this->gateway = $payment_gateways[$this->payment_method];
            }
        }
        switch ($this->payment_method) {
            case (in_array($this->payment_method, array('paypal_express', 'paypal_pro', 'paypal_pro_payflow'))): {
                    if (empty($this->utility)) {
                        $this->utility = new AngellEYE_Utility(null, null);
                    }
                    $this->utility->add_ec_angelleye_paypal_php_library();
                    $this->paypal = $this->utility->paypal;
                }
                break;
        }
    }

    public function angelleye_ec_pp_pf_reference_transaction($order) {
        $token_id = $this->get_usable_reference_transaction($order);
        if (!empty($token_id)) {
            $this->angelleye_load_payment_method_setting($order);
            $PayPalRequestData = $this->angelleye_reference_transaction_request_ec_pp_pf($order, $token_id);
            $result = $this->paypal->DoReferenceTransaction($PayPalRequestData);
            if (!empty($result['ACK']) && ($result['ACK'] == 'Success' || $result['ACK'] == 'SuccessWithWarning')) {
                $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
                if ($this->gateway_settings['payment_action'] != 'Sale') {
                    $order->set_transaction_id($result['TRANSACTIONID']);
                    $payment_order_meta = array('_payment_action' => $this->gateway_settings['payment_action'], '_first_transaction_id' => $result['TRANSACTIONID']);
                    AngellEYE_Utility::angelleye_add_order_meta($order_id, $payment_order_meta);
                }
                AngellEYE_Utility::angelleye_paypal_for_woocommerce_add_paypal_transaction($result, $order, $this->gateway_settings['payment_action']);
                $order->payment_complete($result['TRANSACTIONID']);
                update_post_meta($order_id, '_first_transaction_id', $result['TRANSACTIONID']);
                $order->add_order_note(sprintf(__('%s payment Transaction ID: %s', 'paypal-for-woocommerce'), $this->payment_method, $result['TRANSACTIONID']));
            } else {
                if (!empty($result['L_ERRORCODE0'])) {
                    $ErrorCode = urldecode($result['L_ERRORCODE0']);
                } else {
                    $ErrorCode = '';
                }
                if (!empty($result['L_SHORTMESSAGE0'])) {
                    $ErrorShortMsg = urldecode($result['L_SHORTMESSAGE0']);
                } else {
                    $ErrorShortMsg = '';
                }
                if (!empty($result['L_LONGMESSAGE0'])) {
                    $ErrorLongMsg = urldecode($result['L_LONGMESSAGE0']);
                } else {
                    $ErrorLongMsg = '';
                }
                if (!empty($result['L_SEVERITYCODE0'])) {
                    $ErrorSeverityCode = urldecode($result['L_SEVERITYCODE0']);
                } else {
                    $ErrorSeverityCode = '';
                }
                $message = sprintf(__('PayPal %s API call failed', 'paypal-for-woocommerce') . PHP_EOL . __('Detailed Error Message: %s', 'paypal-for-woocommerce') . PHP_EOL . __('Short Error Message: %s', 'paypal-for-woocommerce') . PHP_EOL . __('Error Code: %s', 'paypal-for-woocommerce') . PHP_EOL . __('Error Severity Code: %s', 'paypal-for-woocommerce'), 'DoReferenceTransaction', $ErrorLongMsg, $ErrorShortMsg, $ErrorCode, $ErrorSeverityCode);
                $order->add_order_note($message);
            }
        }
    }

    public function angelleye_reference_transaction_request_ec_pp_pf($order, $referenceid) {
        $this->angelleye_load_calculation();
        $this->confirm_order_id = $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        $PayPalRequestData = array();
        $DRTFields = array(
            'referenceid' => $referenceid,
            'paymentaction' => ($this->gateway_settings['payment_action'] == 'Authorization' || $order->get_total() == 0 ) ? 'Authorization' : $this->gateway_settings['payment_action'],
            'returnfmfdetails' => '1',
            'softdescriptor' => $this->gateway_settings['softdescriptor']
        );
        $PayPalRequestData['DRTFields'] = $DRTFields;
        $customer_note_value = version_compare(WC_VERSION, '3.0', '<') ? wptexturize($order->customer_note) : wptexturize($order->get_customer_note());
        $customer_notes = $customer_note_value ? substr(preg_replace("/[^A-Za-z0-9 ]/", "", $customer_note_value), 0, 256) : '';
        $PaymentDetails = array(
            'amt' => AngellEYE_Gateway_Paypal::number_format($order->get_total(), $order),
            'currencycode' => version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency(),
            'custom' => apply_filters('ae_ppec_custom_parameter', json_encode(array('order_id' => $order_id, 'order_key' => version_compare(WC_VERSION, '3.0', '<') ? $order->order_key : $order->get_order_key()))),
            'invnum' => $this->gateway_settings['invoice_id_prefix'] . str_replace("#", "", $order->get_order_number()),
            'notetext' => $customer_notes
        );
        if (isset($this->gateway_settings['notifyurl']) && !empty($this->gateway_settings['notifyurl'])) {
            $PaymentDetails['notifyurl'] = $this->gateway_settings['notifyurl'];
        }
        if ($order->needs_shipping_address()) {
            $shipping_first_name = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_first_name : $order->get_shipping_first_name();
            $shipping_last_name = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_last_name : $order->get_shipping_last_name();
            $shipping_address_1 = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_address_1 : $order->get_shipping_address_1();
            $shipping_address_2 = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_address_2 : $order->get_shipping_address_2();
            $shipping_city = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_city : $order->get_shipping_city();
            $shipping_state = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_state : $order->get_shipping_state();
            $shipping_postcode = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_postcode : $order->get_shipping_postcode();
            $shipping_country = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_country : $order->get_shipping_country();
            $ShippingAddress = array('shiptoname' => $shipping_first_name . ' ' . $shipping_last_name,
                'shiptostreet' => $shipping_address_1,
                'shiptostreet2' => $shipping_address_2,
                'shiptocity' => wc_clean(stripslashes($shipping_city)),
                'shiptostate' => $shipping_state,
                'shiptozip' => $shipping_postcode,
                'shiptocountrycode' => $shipping_country,
                'shiptophonenum' => '',
            );
            $PayPalRequestData['ShippingAddress'] = $ShippingAddress;
        }
        $this->send_items = 'yes' === $this->gateway->get_option('send_items', 'yes');
        if( $this->send_items ) {
            $this->order_param = $this->gateway_calculation->order_calculation($this->confirm_order_id);
        } else {
            $this->order_param = array('is_calculation_mismatch' => true);
        }
        if( $this->order_param['is_calculation_mismatch'] == false ) {
            $Payment['order_items'] = $this->order_param['order_items'];
            $PaymentDetails['taxamt'] = AngellEYE_Gateway_Paypal::number_format($this->order_param['taxamt'], $order);
            $PaymentDetails['shippingamt'] = AngellEYE_Gateway_Paypal::number_format($this->order_param['shippingamt'], $order);
            $PaymentDetails['itemamt'] = AngellEYE_Gateway_Paypal::number_format($this->order_param['itemamt'], $order);
            if( $order->get_total() != $PaymentDetails['shippingamt'] ) {
                $PaymentDetails['shippingamt'] = $PaymentDetails['shippingamt'];
            } else {
                $PaymentDetails['shippingamt'] = 0.00;
            }
        } else {
            $Payment['order_items'] = array();
        }
        $PayPalRequestData['PaymentDetails'] = $PaymentDetails;
        return $PayPalRequestData;
    }

    public function angelleye_load_calculation() {
        if (!class_exists('WC_Gateway_Calculation_AngellEYE')) {
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/wc-gateway-calculations-angelleye.php' );
        }
        $subtotal_mismatch_behavior =  ( isset($this->gateway_settings['subtotal_mismatch_behavior']) && ( $this->gateway_settings['subtotal_mismatch_behavior'] == 'drop') ) ? 'drop' : 'add';
        $this->gateway_calculation = new WC_Gateway_Calculation_AngellEYE(null, $subtotal_mismatch_behavior);
    }
}