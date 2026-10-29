<?php

/**
 * Apple Pay for the Cart and Checkout blocks; see AngellEYE_PPCP_Wallet_Block.
 */
final class AngellEYE_PPCP_Apple_Pay_Block extends AngellEYE_PPCP_Wallet_Block {

    protected $name = 'angelleye_ppcp_apple_pay';
    protected $gateway_class = 'WC_Gateway_Apple_Pay_AngellEYE';
    protected $script_file = 'ppcp-apple-pay.js';
    protected $description_property = 'apple_pay_payments_description';
}
