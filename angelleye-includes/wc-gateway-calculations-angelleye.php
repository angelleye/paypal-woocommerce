<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Calculation_AngellEYE {

    protected $order_total;
    protected $taxamt;
    protected $shippingamt;
    protected $order_items;
    protected $itemamt;
    protected $zdp_currencies = array('HUF', 'JPY', 'TWD');
    protected $payment;

    public function __construct() {
        
    }

    public function cart_calculation() {
        $this->payment = array();
        $this->itemamt = 0;
        $this->order_items = array();
        $roundedPayPalTotal = 0;
        $is_zdp_currency = in_array(get_woocommerce_currency(), $this->zdp_currencies);
        if ($is_zdp_currency) {
            $decimals = 0;
        } else {
            $decimals = 2;
        }
        //
        $discounts = round(WC()->cart->get_cart_discount_total(), $decimals);
        foreach (WC()->cart->cart_contents as $cart_item_key => $values) {
            $amount = round($values['line_subtotal'] / $values['quantity'], $decimals);
            $item = array(
                'name' => $values['data']->post->post_title,
                'desc' => '',
                'number' => $values['data']->get_sku(),
                'qty' => $values['quantity'],
                'amt' => $amount,
            );
            $this->order_items[] = $item;
            $roundedPayPalTotal += round($amount * $values['quantity'], $decimals);
        }
        $this->taxamt = round(WC()->cart->tax_total + WC()->cart->shipping_tax_total, $decimals);
        $this->shippingamt = round(WC()->cart->shipping_total, $decimals);
        $this->itemamt = round(WC()->cart->cart_contents_total, $decimals) + $discounts;
        $this->order_total = round($this->itemamt + $this->taxamt + $this->shippingamt, $decimals);
        if ($this->itemamt != $roundedPayPalTotal) {
            $cartItemAmountDifference = $this->itemamt - $roundedPayPalTotal;
            if($this->shippingamt > 0) {
                $this->shippingamt += round($cartItemAmountDifference, $decimals);
            } elseif ($this->taxamt > 0) {
                $this->taxamt += round($cartItemAmountDifference, $decimals);
            } else {
                $this->order_items[0]['amt'] = $this->order_items[0]['amt'] + round($cartItemAmountDifference, $decimals);
                $this->order_total += round($cartItemAmountDifference, $decimals);
                $this->itemamt += round($cartItemAmountDifference, $decimals);
            }
        }
        if ($this->itemamt == $discounts) {
            unset($this->order_items);
            $this->itemamt -= $discounts;
            $this->order_total -= $discounts;
        } else {
            if ($discounts > 0) {
                $discLineItem = array(
                    'name' => 'Discount',
                    'desc' => 'Discount Amount',
                    'qty' => 1,
                    'number' => '',
                    'amt' => '-' . $discounts
                );
                $this->order_items[] = $discLineItem;
            }
            $this->itemamt -= $discounts;
            $this->order_total -= $discounts;
        }
        $wooOrderTotal = round(WC()->cart->total, $decimals);
        if ($wooOrderTotal != $this->order_total) {
            $this->taxamt += $wooOrderTotal - $this->order_total;
            $this->order_total = $wooOrderTotal;
        }
        $this->taxamt = round($this->taxamt, $decimals);
        if (!is_numeric($this->shippingamt)) {
            $this->shippingamt = 0;
        }
        $this->payment['itemamt'] = $this->itemamt;
        $this->payment['taxamt'] = $this->taxamt;
        $this->payment['shippingamt'] = $this->shippingamt;
        $this->payment['order_items'] = $this->order_items;
        return $this->payment;
    }

    public function order_calculation($order_id) {
        $this->payment = array();
        $order = wc_get_order($order_id);
        $this->itemamt = 0;
        $this->order_items = array();
        $roundedPayPalTotal = 0;
        $is_zdp_currency = in_array(get_woocommerce_currency(), $this->zdp_currencies);
        if ($is_zdp_currency) {
            $decimals = 0;
        } else {
            $decimals = 2;
        }
        $discounts = round($order->get_total_discount(), $decimals);
        foreach ($order->get_items() as $cart_item_key => $values) {
            $product = $order->get_product_from_item( $values );
	    $sku = $product ? $product->get_sku() : '';
            $amount = round($values['line_subtotal'] / $values['qty'], $decimals);
            $item = array(
                'name' => $values['name'],
                'qty' => $values['qty'],
                'desc' => '',
                'number' => $sku,
                'amt' => $amount,
            );
            $this->order_items[] = $item;
            $roundedPayPalTotal += round($amount * $values['qty'], $decimals);
        }
        $this->taxamt = round($order->get_total_tax(), $decimals);
        $this->shippingamt = round($order->get_total_shipping(), $decimals);
        $this->itemamt = round($order->get_subtotal(), $decimals);
        $this->order_total = round($this->itemamt + $this->taxamt + $this->shippingamt, $decimals);
        if ($this->itemamt != $roundedPayPalTotal) {
            $cartItemAmountDifference = $this->itemamt - $roundedPayPalTotal;
            if($this->shippingamt > 0) {
                $this->shippingamt += round($cartItemAmountDifference, $decimals);
            } elseif ($this->taxamt > 0) {
                $this->taxamt += round($cartItemAmountDifference, $decimals);
            } else {
                $this->order_items[0]['amt'] = $this->order_items[0]['amt'] + round($cartItemAmountDifference, $decimals);
                $this->order_total += round($cartItemAmountDifference, $decimals);
            }
        }
        if ($this->itemamt == $discounts) {
            unset($this->order_items);
            $this->itemamt -= $discounts;
            $this->order_total -= $discounts;
        } else {
            if ($discounts > 0) {
                $discLineItem = array(
                    'name' => 'Discount',
                    'desc' => 'Discount Amount',
                    'number' => '',
                    'qty' => 1,
                    'amt' => '-' . $discounts
                );
                $this->order_items[] = $discLineItem;
                $this->itemamt -= $discounts;
                $this->order_total -= $discounts;
            }
        }
        $wooOrderTotal = round($order->get_total(), $decimals);
        if ($wooOrderTotal != $this->order_total) {
            $this->taxamt += $wooOrderTotal - $this->order_total;
            $this->order_total = $wooOrderTotal;
        }
        $this->taxamt = round($this->taxamt, $decimals);
        if (!is_numeric($this->shippingamt)) {
            $this->shippingamt = 0;
        }
        $this->payment['itemamt'] = $this->itemamt;
        $this->payment['taxamt'] = $this->taxamt;
        $this->payment['shippingamt'] = $this->shippingamt;
        $this->payment['order_items'] = $this->order_items;
        return $this->payment;
    }
}