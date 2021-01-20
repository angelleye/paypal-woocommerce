<?php

class PayPal_Rest_Partner_Referrals_Handler {

    private $dcc_applies;

    public function __construct() {
        if(!class_exists('PayPal_Rest_DCC_Validate')) {
            include_once './class-paypal-rest-dcc-validate.php';
        }
        $this->dcc_applies = new PayPal_Rest_DCC_Validate();
    }

    public function nonce() {
        return 'a1233wtergfsdt4365tzrshgfbaewa36AGa1233wtergfsdt4365tzrshgfbaewa36AG';
    }

    public function data() {
        $data = $this->default_data();
        return $data;
    }

    private function default_data() {

        return array(
            'partner_config_override' => array(
                'partner_logo_url' => 'https://connect.woocommerce.com/images/woocommerce_logo.png',
                'return_url' => admin_url(
                        'admin.php?page=wc-settings&tab=checkout&section=ppcp-gateway'
                ),
                'return_url_description' => __(
                        'Return to your shop.', 'woocommerce-paypal-payments'
                ),
                'show_add_credit_card' => true,
            ),
            'products' => array(
                $this->dcc_applies->for_country_currency() ? 'PPCP' : 'EXPRESS_CHECKOUT',
            ),
            'legal_consents' => array(
                array(
                    'type' => 'SHARE_DATA_CONSENT',
                    'granted' => true,
                ),
            ),
            'operations' => array(
                array(
                    'operation' => 'API_INTEGRATION',
                    'api_integration_preference' => array(
                        'rest_api_integration' => array(
                            'integration_method' => 'PAYPAL',
                            'integration_type' => 'FIRST_PARTY',
                            'first_party_details' => array(
                                'features' => array(
                                    'PAYMENT',
                                    'FUTURE_PAYMENT',
                                    'REFUND',
                                    'ADVANCED_TRANSACTIONS_SEARCH',
                                ),
                                'seller_nonce' => $this->nonce(),
                            ),
                        ),
                    ),
                ),
            ),
        );
    }

}
