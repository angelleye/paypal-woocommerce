<?php

class AngellEYE_PayPal_PPCP_Apple_Pay_Configurations
{
    public static $_instance;
    private ?AngellEYE_PayPal_PPCP_Request $api_request;
    private ?AngellEYE_PayPal_PPCP_Payment $payment_request;
    private string $host;
    private AngellEye_PayPal_PPCP_Apple_Domain_Validation $apple_pay_domain_validation;
    private string $payPalDomainValidationFile = 'https://www.paypalobjects.com/.well-known/apple-developer-domain-association';

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

    public function listApplePayDomain($returnRawResponse = false)
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
        if ($returnRawResponse) {
            return $jsonResponse;
        }

        /**
         * Add the file in physical path so that If due to some reasons server handles the path request then that should
         * find the file in path
         */
        try {
            $this->addDomainValidationFiles();
        } catch (Exception $exception) {
            echo '<div class="error">' . $exception->getMessage() . '</div>';
        }
        try {
            $checkIsDomainAdded = self::isApplePayDomainAdded($jsonResponse);
            if ($checkIsDomainAdded) {
                $successMessage = __('Your domain has been registered successfully, Close the popup and refresh the page to update the status.', 'paypal-for-woocommerce');
                $applePayGateway = WC_Gateway_Apple_Pay_AngellEYE::instance();
                $applePayGateway->update_option('apple_pay_domain_added', 'yes');
            }
        } catch (Exception $exception) {

        }
        require_once (PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/admin/templates/apple-pay-domain-list.php');
        die;
    }

    public static function isApplePayDomainAdded($response = null): bool
    {
        if (empty($response)) {
            $addedDomains = get_transient("angelleye_apple_pay_domain_list_cache");
            if (!is_array($addedDomains)) {
                $instance = AngellEYE_PayPal_PPCP_Apple_Pay_Configurations::instance();
                $addedDomains = $instance->listApplePayDomain(true);
                set_transient("angelleye_apple_pay_domain_list_cache", $addedDomains, 24 * HOUR_IN_SECONDS);
            }
        } else {
            $addedDomains = $response;
        }

        if ($addedDomains['status'] && count($addedDomains['domains'])) {
            $domainName = parse_url( get_site_url(), PHP_URL_HOST );
            foreach ($addedDomains['domains'] as $addedDomain) {
                if ($addedDomain['domain'] == $domainName) {
                    return true;
                }
            }
            return false;
        }
        throw new Exception('Unable to retrieve apple pay domain list.');
    }

    public static function autoRegisterDomain($is_domain_added = false): bool
    {
        try {
            if (!self::isApplePayDomainAdded()) {
                /**
                 * Try to register the domain max 1 time, this reduces the register attempt in case add domain fails
                 * If domain registration fails its expected user will register manually.
                 */
                $auto_register_status = get_option('ae_apple_pay_domain_reg_retries', 0);
                if ($auto_register_status > 0) {
                    return false;
                }
                update_option('ae_apple_pay_domain_reg_retries', 1);
                $instance = AngellEYE_PayPal_PPCP_Apple_Pay_Configurations::instance();

                /**
                 * Add the file in physical path so that If due to some reasons server handles the path request then that should
                 * find the file in path
                 */
                try {
                    $instance->addDomainValidationFiles();
                } catch (Exception $exception) {}

                $domainNameToRegister = parse_url( get_site_url(), PHP_URL_HOST );
                $result = $instance->registerDomain($domainNameToRegister);
                return $result['status'];
            } else {
                return true;
            }
        } catch (Exception $ex) {

        }
        return $is_domain_added;
    }

    /**
     * @throws Exception
     */
    public static function autoUnRegisterDomain(): bool
    {
        if (self::isApplePayDomainAdded()) {
            $instance = AngellEYE_PayPal_PPCP_Apple_Pay_Configurations::instance();
            $domainNameToRemove = parse_url(get_site_url(), PHP_URL_HOST);
            $result = $instance->removeDomain($domainNameToRemove);
            return $result['status'];
        }
        return false;
    }

    /**
     * @throws Exception
     */
    public function registerDomain($domainNameToRegister)
    {
        if (!filter_var($domainNameToRegister, FILTER_VALIDATE_DOMAIN)) {
            throw new Exception(__('Please enter valid domain name to register.', 'paypal-for-woocommerce'));
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
            delete_transient('angelleye_apple_pay_domain_list_cache');
            return [
                'status' => true,
                'domain' => $domainNameToRegister,
                'message' => __('Domain has been added successfully.', 'paypal-for-woocommerce'),
                'remove_url' => add_query_arg(['domain' => $domainNameToRegister, 'action' => 'angelleye_remove_apple_pay_domain'], admin_url('admin-ajax.php'))
            ];
        } else {
            $this->payment_request->error_email_notification = false;
            $message = $this->payment_request->angelleye_ppcp_get_readable_message($response);
            if (str_contains($message, 'DOMAIN_ALREADY_REGISTERED')) {
                $message = __('Domain is already registered.', 'paypal-for-woocommerce');
            } elseif (str_contains($message, 'DOMAIN_REGISTERED_WITH_ANOTHER_MERCHANT')) {
                $message = __('Domain is registered with another merchant.', 'paypal-for-woocommerce');
            }
            return ['status' => false, 'message' => __('An error occurred.', 'paypal-for-woocommerce') . "\n\n" . $message];
        }
    }

    public function removeDomain($domainNameToRemove): array
    {
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
            delete_transient('angelleye_apple_pay_domain_list_cache');
            return [
                'status' => true,
                'message' => __('Domain has been removed successfully.', 'paypal-for-woocommerce')
            ];
        } else {
            $this->payment_request->error_email_notification = false;
            $message = $this->payment_request->angelleye_ppcp_get_readable_message($response);
            return ['status' => false, 'message' => 'An error occurred.' . "\n\n" .$message];
        }
    }

    public function registerApplePayDomain()
    {
        $domainNameToRegister = $_POST['apple_pay_domain'] ?? parse_url( get_site_url(), PHP_URL_HOST );

        try {
            $result = $this->registerDomain($domainNameToRegister);
            wp_send_json($result);
        } catch (Exception $ex) {
            wp_send_json(['status' => false, 'message' => $ex->getMessage()]);
        }
        die;
    }

    public function removeApplePayDomain()
    {
        $domainNameToRemove = $_REQUEST['domain'] ?? parse_url( get_site_url(), PHP_URL_HOST );
        $result = $this->removeDomain($domainNameToRemove);
        wp_send_json($result);
        die;
    }

    private function addDomainValidationFiles()
    {
        $fileDir = ABSPATH.'.well-known';
        if (!is_dir($fileDir)) {
            mkdir($fileDir);
        }
        $localFileLoc = $this->apple_pay_domain_validation->getDomainAssociationLibFilePath();
        $domainValidationFile = $this->apple_pay_domain_validation->getDomainAssociationFilePath();

        $targetLocation = get_home_path() . $domainValidationFile;

        // PFW-1554 - Handles the INCORRECT_DOMAIN_VERIFICATION_FILE error
        try {
            if (!$this->apple_pay_domain_validation->isSandbox()) {
                $this->updateDomainVerificationFileContent($localFileLoc);
            }
        } catch (Exception $exception) {
            throw new Exception("Unable to update the verification file content. Error: " . $exception->getMessage());
        }
        if (file_exists($targetLocation)) {
            @unlink($targetLocation);
            @unlink($targetLocation . '.txt');
        }
        if (!copy($localFileLoc, $targetLocation)) {
            throw new Exception(sprintf('Unable to copy the files from %s to location %s', $localFileLoc, $targetLocation));
        }
        // Add the .txt version to make sure it works.
        copy($localFileLoc, $targetLocation . '.txt');
        return true;
    }

    private function updateDomainVerificationFileContent($localFileLocation)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payPalDomainValidationFile);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $resultStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (in_array($resultStatus, [200, 304])) {
            $fp = fopen($localFileLocation, "w");
            fwrite($fp, $response);
            fclose($fp);
        }
    }
}
