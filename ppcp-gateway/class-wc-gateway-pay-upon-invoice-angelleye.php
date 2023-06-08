<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Pay_Upon_Invoice_AngellEYE extends WC_Gateway_PPCP_AngellEYE {
    protected bool $enable_pay_upon_invoice;
    const PAYMENT_METHOD = 'pay_upon_invoice';
    /**
     * @var false|mixed
     */
    private mixed $payments_description;

    public function __construct() {
        parent::__construct();
        try {
            $this->id = 'angelleye_ppcp_pay_upon_invoice';
            $this->icon = apply_filters('woocommerce_angelleye_ppcp_pay_upon_invoice_icon', plugins_url('/ppcp-gateway/images/icon/pay_upon_invoice.png', plugin_basename(dirname(__FILE__))));
            $this->method_description = __('Accept payments using Pay Upon Invoice.', 'paypal-for-woocommerce');
            $this->has_fields = true;
            $this->angelleye_ppcp_load_class();
            $this->setGatewaySupports();

            $this->title = $this->setting_obj->get('pay_upon_invoice_payments_title', 'Pay Upon Invoice');
            $this->method_title = apply_filters('angelleye_ppcp_gateway_method_title', $this->title);
            $this->enable_pay_upon_invoice = 'yes' === $this->setting_obj->get('enable_pay_upon_invoice', 'no');
            $this->payments_description = $this->setting_obj->get('pay_upon_invoice_payments_description', 'Complete your purchase using Pay Upon Invoice payment method.');
            if ($this->is_available()) {
                add_filter('woocommerce_billing_fields', [$this, 'pay_upon_invoice_required_fields'], 1, 1000);
                add_action('woocommerce_checkout_update_order_meta', [$this, 'pay_upon_invoice_checkout_field_update_order_meta']);
                //add_action('woocommerce_checkout_process', [$this, 'pay_upon_invoice_checkout_field_process']);
                add_action( 'woocommerce_after_checkout_validation', [$this, 'pay_upon_invoice_field_validation'], 10, 2 );
            }
        } catch (Exception $ex) {

        }
    }

    function pay_upon_invoice_required_fields($fields)
    {
        if (defined('DOING_AJAX') && DOING_AJAX && $this->payment_request->checkout_payment_gateway !== $this->id) {
            return $fields;
        }
        if (!isset($fields['billing_phone'])) {
            $fields['billing_phone'] = array(
                'label' => __('Phone', 'woocommerce'),
                'placeholder' => __('Phone', 'woocommerce'),
                'required' => true,
                'clear' => false,
                'type' => 'tel',
                'class' => array('pay-upon-invoice-phone-number')
            );
        } else {
            $fields['billing_phone']['placeholder'] = '+1-1234567891';
            $fields['billing_phone']['required'] = true;
        }
        if (!isset($fields['billing_birth_date'])) {
            $fields['billing_birth_date'] = array(
                'label' => __('Birth Date', 'paypal-for-woocommerce'),
                'placeholder' => __('Birth Date', 'paypal-for-woocommerce'),
                'required' => true,
                'clear' => false,
                'type' => 'date',
                'class' => array('pay-upon-invoice-birth-date'),
                'default' => WC()->session->get('billing_birth_date')
            );
        }

        return $fields;
    }

    function pay_upon_invoice_field_validation($fields, $errors)
    {
        WC()->session->set('billing_birth_date', sanitize_text_field($_POST['billing_birth_date']));
        preg_match('/^\+[0-9]{1,3}[-][0-9]{4,14}(?:x.+)?$/', $fields['billing_phone'], $matches);
        if (empty($matches)){
            $errors->add( 'billing_phone_error', 'Billing phone number should be in the +1-1234567891 format');
        }
//        $billing_phone = $_POST['billing_phone'];
//        if (str_starts_with())
//        $calling_code = '';
//        $country_code = $_POST['billing_country'] ?? '';
//
//        if( $country_code ){
//            $calling_code = WC()->countries->get_country_calling_code( $country_code );
//            $calling_code = is_array( $calling_code ) ? $calling_code[0] : $calling_code;
//        }
//        echo $calling_code;
//        die('calling code');
    }

    function pay_upon_invoice_checkout_field_update_order_meta($order_id)
    {
        if (!empty( $_POST['billing_birth_date'] ) ) {
            update_post_meta( $order_id, 'billing_birth_date', sanitize_text_field( $_POST['billing_birth_date'] ) );
        }
    }

    protected function isSubscriptionSupported(): bool
    {
        return false;
    }

    public function is_available() {
        return $this->enable_pay_upon_invoice == true && $this->is_credentials_set();
    }

    public function payment_fields() {
        try {
            if ($this->supports('tokenization')) {
                $this->tokenization_script();
            }
            $this->form();
        } catch (Exception $ex) {

        }
    }

    public function form() {
        try {
            ?>
            <p><?php echo __($this->payments_description, 'paypal-for-woocommerce'); ?></p>
            <fieldset id="wc-<?php echo esc_attr($this->id); ?>-form" class='wc-apple-pay-form wc-payment-form'>
                <?php do_action('woocommerce_pay_upon_invoice_form_start', $this->id); ?>
                <div class="clear"></div>
            </fieldset>
            <?php
        } catch (Exception $ex) {

        }
    }

    public function field_name($name) {
        return ' name="' . esc_attr($this->id . '-' . $name) . '" ';
    }

    public static function getUniqueSessionIdentifier(): string
    {
        $uuid32 = WC()->session->get('pay_upon_invoice_session_identifier');
        //if (empty($uuid32)) {
            $fraudnetSessionIdentifier = wp_generate_uuid4();
            $uuid32 = str_replace('-', '', $fraudnetSessionIdentifier);
            WC()->session->set('pay_upon_invoice_session_identifier', $uuid32);
        //}
        return $uuid32;
    }

    public static function clearUniqueSessionIdentifier()
    {
        WC()->session->set('pay_upon_invoice_session_identifier', null);
    }
}
