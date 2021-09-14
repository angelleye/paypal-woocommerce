<?php

defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_DCC_Validate {

    protected static $_instance = null;

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
    );

    /**
     * Which countries support which credit cards.
     */
    private $country_card_matrix = array(
        'AU' => array(
            'mastercard' => array(),
            'visa' => array(),
        ),
        'ES' => array(
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
    );

    /**
     * Returns whether DCC can be used in the current country and the current currency used.
     */
    public function for_country_currency() {
        $country = $this->country();
        $currency = get_woocommerce_currency();
        if (!in_array($country, array_keys($this->allowed_country_currency_matrix), true)) {
            return false;
        }
        $applies = in_array($currency, $this->allowed_country_currency_matrix[$country], true);
        return $applies;
    }

    /**
     * Returns credit cards, which can be used.
     */
    public function valid_cards() {
        $country = $this->country();
        $cards = array();
        if (!isset($this->country_card_matrix[$country])) {
            return $cards;
        }

        $supported_currencies = $this->country_card_matrix[$country];
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
    }

    /**
     * Whether a card can be used or not.
     */
    public function can_process_card($card) {
        $country = $this->country();
        if (!isset($this->country_card_matrix[$country])) {
            return false;
        }
        if (!isset($this->country_card_matrix[$country][$card])) {
            return false;
        }
        $supported_currencies = $this->country_card_matrix[$country][$card];
        $currency = get_woocommerce_currency();
        return empty($supported_currencies) || in_array($currency, $supported_currencies, true);
    }

    private function country() {
        $region = wc_get_base_location();
        $country = $region['country'];
        return $country;
    }

}
