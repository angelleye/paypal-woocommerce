<?php

namespace PayPal\Api;

use PayPal\Common\PayPalModel;
use PayPal\Converter\FormatConverter;
use PayPal\Validation\NumericValidator;
use PayPal\Validation\UrlValidator;

/**
 * Class Item
 *
 * An item being paid for.
 *
 * @package PayPal\Api
 *
 * @property string quantity
 * @property string name
 * @property string description
 * @property string price
 * @property string tax
 * @property string currency
 * @property string sku
 * @property string url
 * @property string category
 * @property \PayPal\Api\Measurement weight
 * @property \PayPal\Api\Measurement length
 * @property \PayPal\Api\Measurement height
 * @property \PayPal\Api\Measurement width
 * @property \PayPal\Api\NameValuePair[] supplementary_data
 * @property \PayPal\Api\NameValuePair[] postback_data
 */
class Item extends PayPalModel
{
    /**
     * Number of items.
     *
     * @param string $quantity
     * 
     * @return $this
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * Number of items.
     *
     * @return string
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Name of the item.
     *
     * @param string $name
     * 
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Name of the item.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Description of the item.
     *
     * @param string $description
     * 
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Description of the item.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Cost of the item.
     *
     * @param string|double $price
     * 
     * @return $this
     */
    public function setPrice($price)
    {
        NumericValidator::validate($price, "Price");
        $price = FormatConverter::formatToPrice($price, $this->getCurrency());
        $this->price = $price;
        return $this;
    }

    /**
     * Cost of the item.
     *
     * @return string
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * tax of the item.
     *
     * @param string|double $tax
     * 
     * @return $this
     */
    public function setTax($tax)
    {
        NumericValidator::validate($tax, "Tax");
        $tax = FormatConverter::formatToPrice($tax, $this->getCurrency());
        $this->tax = $tax;
        return $this;
    }

    /**
     * tax of the item.
     *
     * @return string
     */
    public function getTax()
    {
        return $this->tax;
    }

    /**
     * 3-letter Currency Code
     *
     * @param string $currency
     * 
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * 3-letter Currency Code
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Number or code to identify the item in your catalog/records.
     *
     * @param string $sku
     * 
     * @return $this
     */
    public function setSku($sku)
    {
        $this->sku = $sku;
        return $this;
    }

    /**
     * Number or code to identify the item in your catalog/records.
     *
     * @return string
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * URL linking to item information. Available to payer in transaction history.
     *
     * @param string $url
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setUrl($url)
    {
        UrlValidator::validate($url, "Url");
        $this->url = $url;
        return $this;
    }

    /**
     * URL linking to item information. Available to payer in transaction history.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Category type of the item.
     * Valid Values: ["DIGITAL", "PHYSICAL"]
     *
     * @param string $category
     * 
     * @return $this
     */
    public function setCategory($category)
    {
        $this->category = $category;
        return $this;
    }

    /**
     * Category type of the item.
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Weight of the item.
     *
     * @param \PayPal\Api\Measurement $weight
     * 
     * @return $this
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
        return $this;
    }

    /**
     * Weight of the item.
     *
     * @return \PayPal\Api\Measurement
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Length of the item.
     *
     * @param \PayPal\Api\Measurement $length
     * 
     * @return $this
     */
    public function setLength($length)
    {
        $this->length = $length;
        return $this;
    }

    /**
     * Length of the item.
     *
     * @return \PayPal\Api\Measurement
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Height of the item.
     *
     * @param \PayPal\Api\Measurement $height
     * 
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    /**
     * Height of the item.
     *
     * @return \PayPal\Api\Measurement
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Width of the item.
     *
     * @param \PayPal\Api\Measurement $width
     * 
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }

    /**
     * Width of the item.
     *
     * @return \PayPal\Api\Measurement
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set of optional data used for PayPal risk determination.
     *
     * @param \PayPal\Api\NameValuePair[] $supplementary_data
     * 
     * @return $this
     */
    public function setSupplementaryData($supplementary_data)
    {
        $this->supplementary_data = $supplementary_data;
        return $this;
    }

    /**
     * Set of optional data used for PayPal risk determination.
     *
     * @return \PayPal\Api\NameValuePair[]
     */
    public function getSupplementaryData()
    {
        return $this->supplementary_data;
    }

    /**
     * Append SupplementaryData to the list.
     *
     * @param \PayPal\Api\NameValuePair $nameValuePair
     * @return $this
     */
    public function addSupplementaryData($nameValuePair)
    {
        if (!$this->getSupplementaryData()) {
            return $this->setSupplementaryData(array($nameValuePair));
        } else {
            return $this->setSupplementaryData(
                array_merge($this->getSupplementaryData(), array($nameValuePair))
            );
        }
    }

    /**
     * Remove SupplementaryData from the list.
     *
     * @param \PayPal\Api\NameValuePair $nameValuePair
     * @return $this
     */
    public function removeSupplementaryData($nameValuePair)
    {
        return $this->setSupplementaryData(
            array_diff($this->getSupplementaryData(), array($nameValuePair))
        );
    }

    /**
     * Set of optional data used for PayPal post-transaction notifications.
     *
     * @param \PayPal\Api\NameValuePair[] $postback_data
     * 
     * @return $this
     */
    public function setPostbackData($postback_data)
    {
        $this->postback_data = $postback_data;
        return $this;
    }

    /**
     * Set of optional data used for PayPal post-transaction notifications.
     *
     * @return \PayPal\Api\NameValuePair[]
     */
    public function getPostbackData()
    {
        return $this->postback_data;
    }

    /**
     * Append PostbackData to the list.
     *
     * @param \PayPal\Api\NameValuePair $nameValuePair
     * @return $this
     */
    public function addPostbackData($nameValuePair)
    {
        if (!$this->getPostbackData()) {
            return $this->setPostbackData(array($nameValuePair));
        } else {
            return $this->setPostbackData(
                array_merge($this->getPostbackData(), array($nameValuePair))
            );
        }
    }

    /**
     * Remove PostbackData from the list.
     *
     * @param \PayPal\Api\NameValuePair $nameValuePair
     * @return $this
     */
    public function removePostbackData($nameValuePair)
    {
        return $this->setPostbackData(
            array_diff($this->getPostbackData(), array($nameValuePair))
        );
    }

}
