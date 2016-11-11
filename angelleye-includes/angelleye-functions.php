<?php

if (!function_exists('angelleye_automated_account_creation_for_guest_checkouts')) {

    function angelleye_automated_account_creation_for_guest_checkouts($posted) {
        if (!empty($posted['billing_first_name']) && !empty($posted['billing_email'])) {
            if (email_exists($posted['billing_email'])) {
                $customer_id = email_exists($posted['billing_email']);
            } else {
                $username = sanitize_user(current(explode('@', $posted['billing_email'])), true);
                $append = 1;
                $o_username = $username;
                while (username_exists($username)) {
                    $username = $o_username . $append;
                    $append++;
                }
                $password = wp_generate_password();
                $new_customer = wc_create_new_customer($posted['billing_email'], $username, $password);
                if (is_wp_error($new_customer)) {
                    throw new Exception($new_customer->get_error_message());
                } else {
                    $customer_id = absint($new_customer);
                }
            }
            wc_set_customer_auth_cookie($customer_id);
            WC()->session->set('reload_checkout', true);
            WC()->cart->calculate_totals();
            if ($posted['billing_first_name'] && apply_filters('woocommerce_checkout_update_customer_data', true, $posted)) {
                $userdata = array(
                    'ID' => $customer_id,
                    'first_name' => $posted['billing_first_name'] ? $posted['billing_first_name'] : '',
                    'last_name' => $posted['billing_last_name'] ? $posted['billing_last_name'] : '',
                    'display_name' => $this->posted['billing_first_name'] ? $posted['billing_first_name'] : ''
                );
                wp_update_user(apply_filters('woocommerce_checkout_customer_userdata', $userdata, $posted));
            }
        }
    }

}