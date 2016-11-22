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
                    'display_name' => $posted['billing_first_name'] ? $posted['billing_first_name'] : ''
                );
                wp_update_user(apply_filters('woocommerce_checkout_customer_userdata', $userdata, $posted));
                wc_clear_notices();
                return $customer_id;
            }
        }
    }

}

if (!function_exists('angelleye_automated_account_creation_for_guest_checkouts_for_express_checkout')) {

    function angelleye_automated_account_creation_for_guest_checkouts_for_express_checkout($posted) {
        if (!empty($posted['FIRSTNAME']) && !empty($posted['EMAIL'])) {
            if (email_exists($posted['EMAIL'])) {
                $customer_id = email_exists($posted['EMAIL']);
            } else {
                $username = sanitize_user(current(explode('@', $posted['EMAIL'])), true);
                $append = 1;
                $o_username = $username;
                while (username_exists($username)) {
                    $username = $o_username . $append;
                    $append++;
                }
                $password = wp_generate_password();
                $new_customer = wc_create_new_customer($posted['EMAIL'], $username, $password);
                if (is_wp_error($new_customer)) {
                    throw new Exception($new_customer->get_error_message());
                } else {
                    $customer_id = absint($new_customer);
                }
            }
            wc_set_customer_auth_cookie($customer_id);
            WC()->session->set('reload_checkout', true);
            WC()->cart->calculate_totals();
            if ($posted['FIRSTNAME'] && apply_filters('woocommerce_checkout_update_customer_data', true, $posted)) {
                $userdata = array(
                    'ID' => $customer_id,
                    'first_name' => $posted['FIRSTNAME'] ? $posted['FIRSTNAME'] : '',
                    'last_name' => $posted['LASTNAME'] ? $posted['LASTNAME'] : '',
                    'display_name' => $posted['FIRSTNAME'] ? $posted['FIRSTNAME'] : ''
                );
                wp_update_user(apply_filters('woocommerce_checkout_customer_userdata', $userdata, $posted));
                return $customer_id;
            }
        }
    } 

}

if (!function_exists('angelleye_wc_autoship_cart_has_autoship_item')) {
    
    function angelleye_wc_autoship_cart_has_autoship_item() {
        $cart = WC()->cart;
        if ( empty( $cart ) ) {
           return false;
        }
        $has_autoship_items = false;
        foreach ( $cart->get_cart() as $item ) {
            if ( isset( $item['wc_autoship_frequency'] ) ) {
                $has_autoship_items = true;
                break;
            }
        }
        return $has_autoship_items;
    }
    
}