<?php

namespace PayPal\Api;

use PayPal\Common\PayPalModel;

/**
 * Class ItemList
 *
 * List of items being paid for.
 *
 * @package PayPal\Api
 *
 * @property \PayPal\Api\Item[] items
 * @property \PayPal\Api\ShippingAddress shipping_address
 * @property string shipping_method
 */
class ItemList extends PayPalModel
{
    /**
     * Is this list empty?
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * List of items.
     *
     * @param \PayPal\Api\Item[] $items
     * 
     * @return $this
     */
    public function setItems($items)
    {
        $this->items = $items;
        return $this;
    }

    /**
     * List of items.
     *
     * @return \PayPal\Api\Item[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Append Items to the list.
     *
     * @param \PayPal\Api\Item $item
     * @return $this
     */
    public function addItem($item)
    {
        if (!$this->getItems()) {
            return $this->setItems(array($item));
        } else {
            return $this->setItems(
                array_merge($this->getItems(), array($item))
            );
        }
    }

    /**
     * Remove Items from the list.
     *
     * @param \PayPal\Api\Item $item
     * @return $this
     */
    public function removeItem($item)
    {
        return $this->setItems(
            array_diff($this->getItems(), array($item))
        );
    }

    /**
     * Shipping address.
     *
     * @param \PayPal\Api\ShippingAddress $shipping_address
     * 
     * @return $this
     */
    public function setShippingAddress($shipping_address)
    {
        $this->shipping_address = $shipping_address;
        return $this;
    }

    /**
     * Shipping address.
     *
     * @return \PayPal\Api\ShippingAddress
     */
    public function getShippingAddress()
    {
        return $this->shipping_address;
    }

    /**
     * Shipping method used for this payment like USPSParcel etc.
     *
     * @param string $shipping_method
     * 
     * @return $this
     */
    public function setShippingMethod($shipping_method)
    {
        $this->shipping_method = $shipping_method;
        return $this;
    }

    /**
     * Shipping method used for this payment like USPSParcel etc.
     *
     * @return string
     */
    public function getShippingMethod()
    {
        return $this->shipping_method;
    }

}
