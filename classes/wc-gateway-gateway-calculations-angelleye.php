<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WC_Gateway_Calculation_AngellEYE')) :
class WC_Gateway_Calculation_AngellEYE {

    public $order_total;
    public $taxamt;
    public $shippingamt;
    public $order_items;
    public $itemamt;
    public $zdp_currencies = array('HUF', 'JPY', 'TWD');
    public $payment;
    public $discount_amount;
    public $decimals;

    public function __construct() {
        $is_zdp_currency = in_array(get_woocommerce_currency(), $this->zdp_currencies);
        if ($is_zdp_currency) {
            $this->decimals = 0;
        } else {
            $this->decimals = 2;
        }
    }

    public function cart_calculation() {
        if(!defined('WOOCOMMERCE_CHECKOUT')) {
            define('WOOCOMMERCE_CHECKOUT', true);
        } 
        if(!defined('WOOCOMMERCE_CART')) {
            define('WOOCOMMERCE_CART', true);
        } 
        WC()->cart->calculate_totals();
        $this->payment = array();
        $this->itemamt = 0;
        $this->order_items = array();
        $roundedPayPalTotal = 0;
        $this->discount_amount = round(WC()->cart->get_cart_discount_total(), $this->decimals);
        foreach (WC()->cart->cart_contents as $cart_item_key => $values) {
            $amount = round($values['line_subtotal'] / $values['quantity'], $this->decimals);
            $item = array(
                'name' => $values['data']->post->post_title,
                'desc' => '',
                'number' => $values['data']->get_sku(),
                'qty' => $values['quantity'],
                'amt' => $amount,
            );
            $this->order_items[] = $item;
            $roundedPayPalTotal += round($amount * $values['quantity'], $this->decimals);
        }
   
        
        $this->taxamt = round(WC()->cart->tax_total + WC()->cart->shipping_tax_total, $this->decimals);
        $this->shippingamt = round(WC()->cart->shipping_total, $this->decimals);
        $this->itemamt = round(WC()->cart->cart_contents_total, $this->decimals) + $this->discount_amount;
        $this->order_total = round($this->itemamt + $this->taxamt + $this->shippingamt, $this->decimals);
        if ($this->itemamt == $this->discount_amount) {
            unset($this->order_items);
            $this->itemamt -= $this->discount_amount;
            $this->order_total -= $this->discount_amount;
        } else {
            if ($this->discount_amount > 0) {
                $discLineItem = array(
                    'name' => 'Discount',
                    'desc' => 'Discount Amount',
                    'qty' => 1,
                    'number' => '',
                    'amt' => '-' . $this->discount_amount
                );
                $this->order_items[] = $discLineItem;
            }
            $this->itemamt -= $this->discount_amount;
            $this->order_total -= $this->discount_amount;
        }
        if(!defined('WOOCOMMERCE_CHECKOUT')) {
            define('WOOCOMMERCE_CHECKOUT', true);
        } 
        if(!defined('WOOCOMMERCE_CART')) {
            define('WOOCOMMERCE_CART', true);
        } 
        WC()->cart->calculate_totals();
        $wooOrderTotal = round(WC()->cart->total, $this->decimals);
        if ($wooOrderTotal != $this->order_total) {
            $this->taxamt += $wooOrderTotal - $this->order_total;
            $this->order_total = $wooOrderTotal;
        }
        $this->taxamt = round($this->taxamt, $this->decimals);
        if (!is_numeric($this->shippingamt)) {
            $this->shippingamt = 0;
        }
        $this->cart_re_calculate();
        $this->payment['itemamt'] = round($this->itemamt, $this->decimals);
        $this->payment['taxamt'] = round($this->taxamt, $this->decimals);
        $this->payment['shippingamt'] = AngellEYE_Gateway_Paypal::number_format(round($this->shippingamt, $this->decimals));
        $this->payment['order_items'] = $this->order_items;
        $this->payment['discount_amount'] = round($this->discount_amount, $this->decimals);
        return $this->payment;
    }

    public function order_calculation($order_id) {
        $this->payment = array();
        $order = wc_get_order($order_id);
        $this->itemamt = 0;
        $this->discount_amount = 0;
        $this->order_items = array();
        $roundedPayPalTotal = 0;
        $this->discount_amount = round($order->get_total_discount(), $this->decimals);
        foreach ($order->get_items() as $cart_item_key => $values) {
            $product = $order->get_product_from_item( $values );
	    $sku = $product ? $product->get_sku() : '';
            $amount = round($values['line_subtotal'] / $values['qty'], $this->decimals);
            $item = array(
                'name' => $values['name'],
                'qty' => $values['qty'],
                'desc' => '',
                'number' => $sku,
                'amt' => $amount,
            );
            $this->order_items[] = $item;
            $roundedPayPalTotal += round($amount * $values['qty'], $this->decimals);
        }
        $this->taxamt = round($order->get_total_tax(), $this->decimals);
        $this->shippingamt = round($order->get_total_shipping(), $this->decimals);
        $this->itemamt = round($order->get_subtotal(), $this->decimals);
        $this->order_total = round($this->itemamt + $this->taxamt + $this->shippingamt, $this->decimals);
        if ($this->itemamt == $this->discount_amount) {
            unset($this->order_items);
            $this->itemamt -= $this->discount_amount;
            $this->order_total -= $this->discount_amount;
        } else {
            if ($this->discount_amount > 0) {
                $discLineItem = array(
                    'name' => 'Discount',
                    'desc' => 'Discount Amount',
                    'number' => '',
                    'qty' => 1,
                    'amt' => '-' . $this->discount_amount
                );
                $this->order_items[] = $discLineItem;
                $this->itemamt -= $this->discount_amount;
                $this->order_total -= $this->discount_amount;
            }
        }
        $wooOrderTotal = round($order->get_total(), $this->decimals);
        if ($wooOrderTotal != $this->order_total) {
            $this->taxamt += $wooOrderTotal - $this->order_total;
            $this->order_total = $wooOrderTotal;
        }
        $this->taxamt = round($this->taxamt, $this->decimals);
        if (!is_numeric($this->shippingamt)) {
            $this->shippingamt = 0;
        }
        $this->order_re_calculate($order);
        $this->payment['itemamt'] = round($this->itemamt, $this->decimals);
        $this->payment['taxamt'] = round($this->taxamt, $this->decimals);
        $this->payment['shippingamt'] = round($this->shippingamt, $this->decimals);
        $this->payment['order_items'] = $this->order_items;
        $this->payment['discount_amount'] = round($this->discount_amount, $this->decimals);
        return $this->payment;
    }
    
    public function cart_re_calculate(){
        $temp_roundedPayPalTotal = 0;
        if ( ! empty( $this->order_items ) && is_array( $this->order_items ) ) {
            foreach ($this->order_items as $key => $values) {
                $temp_roundedPayPalTotal += round($values['amt'] * $values['qty'], $this->decimals);
            }
        }
        $this->itemamt = round($temp_roundedPayPalTotal, $this->decimals);
        if( round(WC()->cart->total, $this->decimals) != round($this->itemamt + $this->taxamt + $this->shippingamt, $this->decimals) ) {
            $cartItemAmountDifference = round(WC()->cart->total, $this->decimals) - round($this->itemamt + $this->taxamt + $this->shippingamt, $this->decimals);
            if($this->shippingamt > 0) {
                $this->shippingamt += round($cartItemAmountDifference, $this->decimals);
            } elseif ($this->taxamt > 0) {
                $this->taxamt += round($cartItemAmountDifference, $this->decimals);
            } else {
                $this->order_items[0]['amt'] = $this->order_items[0]['amt'] + round($cartItemAmountDifference, $this->decimals);
                $this->order_total += round($cartItemAmountDifference, $this->decimals);
            }
        }
    }
    public function order_re_calculate($order){
        $temp_roundedPayPalTotal = 0;
        if ( ! empty( $this->order_items ) && is_array( $this->order_items ) ) {
            foreach ($this->order_items as $key => $values) {
                $temp_roundedPayPalTotal += round($values['amt'] * $values['qty'], $this->decimals);
            }
        }
        $this->itemamt = $temp_roundedPayPalTotal;
        if( round($order->get_total(), $this->decimals) != round($this->itemamt + $this->taxamt + $this->shippingamt, $this->decimals) ) {
            $cartItemAmountDifference = round($order->get_total(), $this->decimals) - round($this->itemamt + $this->taxamt + $this->shippingamt, $this->decimals);
            if($this->shippingamt > 0) {
                $this->shippingamt += round($cartItemAmountDifference, $this->decimals);
            } elseif ($this->taxamt > 0) {
                $this->taxamt += round($cartItemAmountDifference, $this->decimals);
            } else {
                $this->order_items[0]['amt'] = $this->order_items[0]['amt'] + round($cartItemAmountDifference, $this->decimals);
                $this->order_total += round($cartItemAmountDifference, $this->decimals);
            }
        }
    }
}
endif;