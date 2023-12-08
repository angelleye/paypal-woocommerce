<?php

defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_DCC_Validate {

    protected static $_instance = null;
    public $country;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * The matrix which countries and currency combinations can be used for DCC.
     *
     */
    private $allowed_country_currency_matrix = array(
        'AU' => array(
            'AUD',
            'CAD',
            'CHF',
            'CZK',
            'DKK',
            'EUR',
            'GBP',
            'HKD',
            'HUF',
            'JPY',
            'NOK',
            'NZD',
            'PLN',
            'SEK',
            'SGD',
            'USD',
        ),
        'BE' => array(
            'EUR',
            'USD',
            'CAD',
            'GBP',
            'PLN',
            'SEK',
            'CHF',
        ),
        'BG' => array(
            'EUR',
            'USD',
        ),
        'CY' => array(
            'EUR',
            'USD',
            'CAD',
            'GBP',
            'AUD',
            'CZK',
            'DKK',
            'NOK',
            'PLN',
            'SEK',
            'CHF',
        ),
        'CZ' => array(
            'EUR',
            'USD',
            'CZK',
        ),
        'DE' => array(
            'AUD',
            'CAD',
            'CHF',
            'CZK',
            'DKK',
            'EUR',
            'GBP',
            'HKD',
            'HUF',
            'JPY',
            'NOK',
            'NZD',
            'PLN',
            'SEK',
            'SGD',
            'USD',
        ),
        'DK' => array(
            'EUR',
            'USD',
            'DKK',
            'NOK',
        ),
        'EE' => array(
            'EUR',
            'USD',
        ),
        'ES' => array(
            'AUD',
            'CAD',
            'CHF',
            'CZK',
            'DKK',
            'EUR',
            'GBP',
            'HKD',
            'HUF',
            'JPY',
            'NOK',
            'NZD',
            'PLN',
            'SEK',
            'SGD',
            'USD',
        ),
        'FI' => array(
            'EUR',
            'USD',
        ),
        'FR' => array(
            'AUD',
            'CAD',
            'CHF',
            'CZK',
            'DKK',
            'EUR',
            'GBP',
            'HKD',
            'HUF',
            'JPY',
            'NOK',
            'NZD',
            'PLN',
            'SEK',
            'SGD',
            'USD',
        ),
        'GB' => array(
            'AUD',
            'CAD',
            'CHF',
            'CZK',
            'DKK',
            'EUR',
            'GBP',
            'HKD',
            'HUF',
            'JPY',
            'NOK',
            'NZD',
            'PLN',
            'SEK',
            'SGD',
            'USD',
        ),
        'GR' => array(
            'EUR',
            'USD',
            'GBP',
        ),
        'HU' => array(
            'EUR',
            'USD',
            'HUF',
        ),
        'IT' => array(
            'AUD',
            'CAD',
            'CHF',
            'CZK',
            'DKK',
            'EUR',
            'GBP',
            'HKD',
            'HUF',
            'JPY',
            'NOK',
            'NZD',
            'PLN',
            'SEK',
            'SGD',
            'USD',
        ),
        'LT' => array(
            'EUR',
            'USD',
            'CAD',
            'GBP',
            'JPY',
            'AUD',
            'CZK',
            'DKK',
            'HUF',
            'PLN',
            'SEK',
            'CHF',
            'NZD',
            'NOK',
        ),
        'LU' => array(
            'EUR',
            'USD',
        ),
        'LV' => array(
            'EUR',
            'USD',
            'CAD',
            'GBP',
        ),
        'US' => array(
            'AUD',
            'CAD',
            'EUR',
            'GBP',
            'JPY',
            'USD',
        ),
        'CA' => array(
            'AUD',
            'CAD',
            'CHF',
            'CZK',
            'DKK',
            'EUR',
            'GBP',
            'HKD',
            'HUF',
            'JPY',
            'NOK',
            'NZD',
            'PLN',
            'SEK',
            'SGD',
            'USD',
        ),
        'MT' => array(
            'EUR',
            'USD',
            'CAD',
            'GBP',
            'JPY',
            'AUD',
            'CZK',
            'DKK',
            'HUF',
            'NOK',
            'PLN',
            'SEK',
            'CHF',
        ),
        'MX' => array(
            'MXN',
        ),
        'NL' => array(
            'EUR',
            'GBP',
            'AUD',
            'CZK',
            'HUF',
            'CHF',
            'CAD',
            'USD',
        ),
        'NO' => array(
            'EUR',
            'USD',
            'CAD',
            'GBP',
            'NOK',
        ),
        'PL' => array(
            'EUR',
            'USD',
            'CAD',
            'GBP',
            'AUD',
            'DKK',
            'PLN',
            'SEK',
            'CZK',
        ),
        'PT' => array(
            'EUR',
            'USD',
            'CAD',
            'GBP',
            'CZK',
        ),
        'RO' => array(
            'EUR',
            'USD',
            'GBP',
        ),
        'SE' => array(
            'EUR',
            'USD',
            'NOK',
            'SEK',
        ),
        'SI' => array(
            'EUR',
            'USD',
        ),
        'SK' => array(
            'EUR',
            'USD',
            'GBP',
            'CZK',
            'HUF',
        ),
        'JP' => array(
            'AUD',
            'CAD',
            'CHF',
            'CZK',
            'DKK',
            'EUR',
            'GBP',
            'HKD',
            'HUF',
            'JPY',
            'NOK',
            'NZD',
            'PLN',
            'SEK',
            'SGD',
            'USD',
        ),
    );

    /**
     * Which countries support which credit cards.
     */
    private $country_card_matrix = array(
        'AU' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('AUD'),
        ),
        'BE' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('EUR', 'USD', 'CAD'),
        ),
        'BG' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('EUR'),
        ),
        'CY' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('EUR'),
        ),
        'CZ' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('CZK'),
        ),
        'DE' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('EUR'),
        ),
        'DK' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('DKK'),
        ),
        'EE' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array(),
        ),
        'ES' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('EUR'),
        ),
        'FI' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('EUR'),
        ),
        'FR' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('EUR'),
        ),
        'GB' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('GBP', 'USD'),
        ),
        'GR' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('EUR'),
        ),
        'HU' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('HUF'),
        ),
        'IT' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('EUR'),
        ),
        'US' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('USD'),
            'discover' => array('USD'),
        ),
        'CA' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('CAD'),
            'jcb' => array('CAD'),
        ),
        'LT' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('EUR'),
        ),
        'LU' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('EUR'),
        ),
        'LV' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('EUR', 'USD'),
        ),
        'MT' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('EUR'),
        ),
        'MX' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array(),
        ),
        'NL' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('EUR', 'USD'),
        ),
        'NO' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('NOK'),
        ),
        'PL' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('EUR', 'USD', 'GBP', 'PLN'),
        ),
        'PT' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('EUR', 'USD', 'CAD', 'GBP'),
        ),
        'RO' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('EUR', 'USD'),
        ),
        'SE' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('EUR', 'SEK'),
        ),
        'SI' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('EUR'),
        ),
        'SK' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('EUR', 'GBP'),
        ),
        'JP' => array(
            'mastercard' => array(),
            'visa' => array(),
            'amex' => array('JPY'),
            'jcb' => array('JPY'),
        ),
    );

    /**
     * Returns whether DCC can be used in the current country and the current currency used.
     */
    public function for_country_currency($country = null) {
        try {
            if ($country === null) {
                $country = $this->country();
            }
            $currency = get_woocommerce_currency();
            if (!in_array($country, array_keys($this->allowed_country_currency_matrix), true)) {
                return false;
            }
            $applies = in_array($currency, $this->allowed_country_currency_matrix[$country], true);
            return $applies;
        } catch (Exception $ex) {
            
        }
    }

    /**
     * Returns credit cards, which can be used.
     */
    public function valid_cards() {
        try {
            $this->country = $this->country();
            $cards = array();
            if (!isset($this->country_card_matrix[$this->country])) {
                return $cards;
            }

            $supported_currencies = $this->country_card_matrix[$this->country];
            foreach ($supported_currencies as $card => $currencies) {
                if ($this->can_process_card($card)) {
                    $cards[] = $card;
                }
            }
            if (in_array('amex', $cards, true)) {
                $cards[] = 'american-express';
            }
            if (in_array('mastercard', $cards, true)) {
                $cards[] = 'master-card';
            }
            return $cards;
        } catch (Exception $ex) {
            
        }
    }

    /**
     * Whether a card can be used or not.
     */
    public function can_process_card($card) {
        try {
            $this->country = $this->country();
            if (!isset($this->country_card_matrix[$this->country])) {
                return false;
            }
            if (!isset($this->country_card_matrix[$this->country][$card])) {
                return false;
            }
            $supported_currencies = $this->country_card_matrix[$this->country][$card];
            $currency = get_woocommerce_currency();
            return empty($supported_currencies) || in_array($currency, $supported_currencies, true);
        } catch (Exception $ex) {
            
        }
    }

    public function country() {
        try {
            $region = wc_get_base_location();
            $country = $region['country'];
            return $country;
        } catch (Exception $ex) {
            
        }
    }
}
