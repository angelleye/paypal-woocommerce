<?php

if (class_exists('WC_Checkout')) {

    class AngellEYE_PayPal_PPCP_Checkout extends WC_Checkout {

        public static function instance() {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function process_checkout() {
            try {
                wc_maybe_define_constant('WOOCOMMERCE_CHECKOUT', true);
                wc_set_time_limit(0);
                do_action('woocommerce_before_checkout_process');
                if (WC()->cart->is_empty()) {
                    throw new Exception(sprintf(__('Sorry, your session has expired. <a href="%s" class="wc-backward">Return to shop</a>', 'woocommerce'), esc_url(wc_get_page_permalink('shop'))));
                }
                do_action('woocommerce_checkout_process');
                $errors = new WP_Error();
                $posted_data = $this->get_posted_data();
                $this->update_session($posted_data);
                $this->process_customer($posted_data);
                $order_id = $this->create_order($posted_data);
                $order = wc_get_order($order_id);
                if (is_wp_error($order_id)) {
                    throw new Exception($order_id->get_error_message());
                }
                if (!$order) {
                    throw new Exception(__('Unable to create order.', 'woocommerce'));
                }
                do_action('woocommerce_checkout_order_processed', $order_id, $posted_data, $order);
                $this->process_order_payment($order_id, $posted_data['payment_method']);
            } catch (Exception $e) {
                wc_add_notice($e->getMessage(), 'error');
            }
            $this->send_ajax_failure_response();
        }

    }

}


