<?php

/**
 * Google Pay for the Cart and Checkout blocks; see AngellEYE_PPCP_Wallet_Block.
 */
final class AngellEYE_PPCP_Google_Pay_Block extends AngellEYE_PPCP_Wallet_Block {

    protected $name = 'angelleye_ppcp_google_pay';
    protected $gateway_class = 'WC_Gateway_Google_Pay_AngellEYE';
    protected $script_file = 'ppcp-google-pay.js';
    protected $description_property = 'google_pay_payments_description';
}
