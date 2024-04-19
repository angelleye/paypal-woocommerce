<?php

if (!defined('ABSPATH')) {
    exit;
}

trait WC_PPCP_Pre_Orders_Trait {

    private static $has_attached_pre_order_integration_hooks = false;

    public function maybe_init_pre_orders() {
        if (!$this->is_pre_orders_enabled()) {
            return;
        }
        $this->supports[] = 'pre-orders';
        
        /*if (self::$has_attached_pre_order_integration_hooks || WC_Gateway_PPCP::ID !== $this->id) {
            return;
        }
        add_filter('wc_ppcp_display_save_payment_method_checkbox', [$this, 'hide_save_payment_for_pre_orders_charged_upon_release']);
        self::$has_attached_pre_order_integration_hooks = true;*/
    }

    public function is_pre_orders_enabled() {
        return class_exists('WC_Pre_Orders');
    }

    public function has_pre_order($order_id) {
        return $this->is_pre_orders_enabled() && class_exists('WC_Pre_Orders_Order') && WC_Pre_Orders_Order::order_contains_pre_order($order_id);
    }

    public function is_pre_order_item_in_cart() {
        return $this->is_pre_orders_enabled() && class_exists('WC_Pre_Orders_Cart') && WC_Pre_Orders_Cart::cart_contains_pre_order();
    }

    public function get_pre_order_product_from_cart() {
        if (!$this->is_pre_orders_enabled() || !class_exists('WC_Pre_Orders_Cart')) {
            return false;
        }
        return WC_Pre_Orders_Cart::get_pre_order_product();
    }

    public function get_pre_order_product_from_order($order_id) {
        if (!$this->is_pre_orders_enabled() || !class_exists('WC_Pre_Orders_Order')) {
            return false;
        }
        return WC_Pre_Orders_Order::get_pre_order_product($order_id);
    }

    public function is_pre_order_product_charged_upon_release($product) {
        return $this->is_pre_orders_enabled() && class_exists('WC_Pre_Orders_Product') && WC_Pre_Orders_Product::product_is_charged_upon_release($product);
    }

    public function is_pre_order_product_charged_upfront($product) {
        return $this->is_pre_orders_enabled() && class_exists('WC_Pre_Orders_Product') && WC_Pre_Orders_Product::product_is_charged_upfront($product);
    }

    public function maybe_process_pre_orders($order_id) {
        return (
                $this->has_pre_order($order_id) &&
                WC_Pre_Orders_Order::order_requires_payment_tokenization($order_id)
                );
    }

    public function remove_order_source_before_retry($order) {
        $order->delete_meta_data('_ppcp_source_id');
        $order->delete_meta_data('_ppcp_card_id');
        $order->save();
    }

    public function mark_order_as_pre_ordered($order) {
        if (!class_exists('WC_Pre_Orders_Order')) {
            return;
        }
        WC_Pre_Orders_Order::mark_order_as_pre_ordered($order);
    }

    public function process_pre_order($order_id) {
        try {
            $order = wc_get_order($order_id);
            $this->validate_minimum_order_amount($order);
            $prepared_source = $this->prepare_source(get_current_user_id(), true);
            if (empty($prepared_source->customer) || empty($prepared_source->source)) {
                throw new WC_PPCP_Exception(__('Unable to store payment details. Please try again.', 'paypal-for-woocommerce'));
            }
            $response = [
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
            ];
            $this->save_source_to_order($order, $prepared_source);
            $intent_secret = $this->setup_intent($order, $prepared_source);
            if (!empty($intent_secret)) {
                $response['setup_intent_secret'] = $intent_secret;
                return $response;
            }
            WC()->cart->empty_cart();
            $this->mark_order_as_pre_ordered($order);
            return $response;
        } catch (WC_PPCP_Exception $e) {
            wc_add_notice($e->getLocalizedMessage(), 'error');
            WC_PPCP_Logger::log('Pre Orders Error: ' . $e->getMessage());
            return [
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true),
            ];
        }
    }

    public function process_pre_order_release_payment($order, $retry = true) {
        try {
            
        } catch (Exception $e) {
        }
    }

    public function is_pre_order_charged_upon_release_in_cart() {
        $pre_order_product = $this->get_pre_order_product_from_cart();
        return $pre_order_product && $this->is_pre_order_product_charged_upon_release($pre_order_product);
    }

    public function has_pre_order_charged_upon_release($order) {
        $pre_order_product = $this->get_pre_order_product_from_order($order);
        return $pre_order_product && $this->is_pre_order_product_charged_upon_release($pre_order_product);
    }
    
    public function has_pre_order_charged_upfront($order) {
        $pre_order_product = $this->get_pre_order_product_from_order($order);
        return $pre_order_product && $this->is_pre_order_product_charged_upfront($pre_order_product);
    }

    public function hide_save_payment_for_pre_orders_charged_upon_release($display_save_option) {
        if (!$display_save_option || !$this->is_pre_order_item_in_cart()) {
            return $display_save_option;
        }
        if ($this->is_pre_order_charged_upon_release_in_cart()) {
            return false;
        }
        return $display_save_option;
    }
}
