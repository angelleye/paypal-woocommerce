<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Gateway_PPCP_AngellEYE_Google_Pay_Subscriptions extends WC_Gateway_Google_Pay_AngellEYE {
    use WC_Gateway_PPCP_Angelleye_Subscriptions_Base;
}
