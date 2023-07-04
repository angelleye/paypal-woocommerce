<?php

class AngellEYE_PayPal_PPCP_Apple_Pay_Configurations
{
    public static $_instance;
    private ?AngellEYE_PayPal_PPCP_Request $api_request;
    private ?AngellEYE_PayPal_PPCP_Payment $payment_request;
    private string $host;
    private AngellEye_PayPal_PPCP_Apple_Domain_Validation $apple_pay_domain_validation;

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        if (!class_exists('AngellEYE_PayPal_PPCP_Request')) {
            include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-request.php';
        }
        if (!class_exists('AngellEYE_PayPal_PPCP_Payment')) {
            include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-payment.php');
        }
        $this->payment_request = AngellEYE_PayPal_PPCP_Payment::instance();
        $this->api_request = AngellEYE_PayPal_PPCP_Request::instance();
        $this->apple_pay_domain_validation = AngellEye_PayPal_PPCP_Apple_Domain_Validation::instance();
        if ($this->payment_request->is_sandbox) {
            $this->host = 'api.sandbox.paypal.com';
        } else {
            $this->host = 'api.paypal.com';
        }

        add_action('wp_ajax_angelleye_list_apple_pay_domain', [$this, 'listApplePayDomain']);
        add_action('wp_ajax_angelleye_register_apple_pay_domain', [$this, 'registerApplePayDomain']);
        add_action('wp_ajax_angelleye_remove_apple_pay_domain', [$this, 'removeApplePayDomain']);
    }

    private function getApiCallHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Authorization' => '',
            'prefer' => 'return=representation',
            'Paypal-Auth-Assertion' => $this->payment_request->angelleye_ppcp_paypalauthassertion(),
            'X-PAYPAL-SECURITY-CONTEXT' => ''
        ];
    }

    public function listApplePayDomain()
    {
        $args = [
            'method' => 'GET',
            'timeout' => 60,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
            'body' => [],
            'headers' => $this->getApiCallHeaders(),
            'cookies' => []
        ];

        $domainQueryParams = [
            'provider_type' => 'APPLE_PAY', 'page_size' => 10, 'page' => 1
        ];
        $domainGetUrl = add_query_arg($domainQueryParams, 'https://' . $this->host . '/v1/customer/wallet-domains');
        $response = $this->api_request->request($domainGetUrl, $args, 'apple_pay_domain_list');
        $jsonResponse = ['status' => false];
        if (isset($response['total_items'])) {
            $jsonResponse['status'] = true;
            $allDomains = [];
            if ($response['total_items'] > 0) {
                foreach ($response['wallet_domains'] as $domains) {
                    $allDomains[] = ['domain' => $domains['domain']['name'], 'provider_type' => $domains['provider_type']];
                }
            }
            $jsonResponse['domains'] = $allDomains;
            $jsonResponse['message'] = __('Domain listing retrieved successfully.', 'paypal-for-woocommerce');
        } else {
            $this->payment_request->error_email_notification = false;
            $jsonResponse['message'] = $this->payment_request->angelleye_ppcp_get_readable_message($response);
        }

        require_once (PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/admin/templates/apple-pay-domain-list.php');
        die;
    }

    public function registerApplePayDomain()
    {
        $domainNameToRegister = $_POST['apple_pay_domain'] ?? parse_url( get_site_url(), PHP_URL_HOST );

        if (!filter_var($domainNameToRegister, FILTER_VALIDATE_DOMAIN)) {
            wp_send_json(['status' => false, 'message' => __('Please enter valid domain name to register.', 'paypal-for-woocommerce')]);
            die;
        }
        $domainParams = [
            "provider_type" => "APPLE_PAY",
            "domain" => [
                "name" => $domainNameToRegister
            ]
        ];
        $args = [
            'method' => 'POST',
            'timeout' => 60,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
            'body' => $domainParams,
            'headers' => $this->getApiCallHeaders(),
            'cookies' => array()
        ];
        $domainGetUrl = 'https://' . $this->host . '/v1/customer/wallet-domains';
        $response = $this->api_request->request($domainGetUrl, $args, 'apple_pay_domain_add');
        if (isset($response['domain'])) {
            wp_send_json([
                'status' => true,
                'domain' => $domainNameToRegister,
                'message' => __('Domain has been added successfully.', 'paypal-for-woocommerce'),
                'remove_url' => add_query_arg(['domain' => $domainNameToRegister, 'action' => 'angelleye_remove_apple_pay_domain'], admin_url('admin-ajax.php'))
            ]);
        } else {
            $this->payment_request->error_email_notification = false;
            $message = $this->payment_request->angelleye_ppcp_get_readable_message($response);
            if (str_contains($message, 'DOMAIN_ALREADY_REGISTERED')) {
                $message = __('Domain is already registered.', 'paypal-for-woocommerce');
            } elseif (str_contains($message, 'DOMAIN_REGISTERED_WITH_ANOTHER_MERCHANT')) {
                $message = __('Domain is registered with another merchant.', 'paypal-for-woocommerce');
            }
            wp_send_json(['status' => false, 'message' => __('An error occurred.', 'paypal-for-woocommerce') . "\n\n" . $message]);
        }
        die;
    }

    public function removeApplePayDomain()
    {
        $domainNameToRemove = $_REQUEST['domain'] ?? parse_url( get_site_url(), PHP_URL_HOST );
        $domainParams = [
            "provider_type" => "APPLE_PAY",
            "domain" => [
                "name" => $domainNameToRemove
            ],
            "reason" => "Requested by site administrator"
        ];
        $args = [
            'method' => 'POST',
            'timeout' => 60,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
            'body' => $domainParams,
            'headers' => $this->getApiCallHeaders(),
            'cookies' => array()
        ];
        $domainGetUrl = 'https://' . $this->host . '/v1/customer/unregister-wallet-domain';
        $response = $this->api_request->request($domainGetUrl, $args, 'apple_pay_domain_remove');
        if (isset($response['domain'])) {
            wp_send_json([
                'status' => true,
                'message' => __('Domain has been removed successfully.', 'paypal-for-woocommerce')
            ]);
        } else {
            $this->payment_request->error_email_notification = false;
            $message = $this->payment_request->angelleye_ppcp_get_readable_message($response);
            wp_send_json(['status' => false, 'message' => 'An error occurred.' . "\n\n" .$message]);
        }
    }
}
