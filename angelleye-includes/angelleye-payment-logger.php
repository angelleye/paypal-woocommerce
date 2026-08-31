<?php

class AngellEYE_PFW_Payment_Logger {

    protected static $_instance = null;
    public $allow_method = array();
    public $api_url;
    public $api_key;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->api_url = 'https://gtctgyk7fh.execute-api.us-east-2.amazonaws.com/default/PayPalPaymentsTracker';
        $this->api_key = 'srGiuJFpDO4W7YCDXF56g2c9nT1JhlURVGqYD7oa';
        $this->allow_method = array('DoExpressCheckoutPayment', 'DoDirectPayment', 'DoCapture', 'ProcessTransaction', 'Braintree', 'PayPal Credit Card (REST)');
        add_action('angelleye_paypal_response_data', array($this, 'own_angelleye_paypal_response_data'), 10, 6);
    }

    public function own_angelleye_paypal_response_data($result_data, $request_data, $product_id = 1, $sandbox = false, $is_nvp = true, $payment_method = 'express_checkout') {
        $request_param = array();
        if (isset($result_data) && is_array($result_data) && !empty($result_data['CURL_ERROR'])) {
            return $result_data;
        } else {
            if ($is_nvp) {
                $result = $this->angelleye_nvp_to_array($result_data);
                $request = $this->angelleye_nvp_to_array($request_data);
            } else {
                $result = $result_data;
                $request = $request_data;
            }
            if($payment_method == 'paypal_advanced') {
                $request = $this->angelleye_nvp_to_array($request_data);
                $request['METHOD'] = 'ProcessTransaction';
            }
            if ($payment_method == 'braintree') {
                $request['METHOD'] = 'Braintree';
            }
            if ($payment_method == 'paypal_credit_card_rest') {
                $request['METHOD'] = 'PayPal Credit Card (REST)';
            }
            if (is_array($result) && isset($result['PNREF']) && !empty($result['PNREF']) && ( isset($request['TRXTYPE[1]']) && $request['TRXTYPE[1]'] != 'I')) {
                $request['METHOD'] = 'ProcessTransaction';
            }
            if (isset($request['METHOD']) && !empty($request['METHOD']) && in_array($request['METHOD'], $this->allow_method)) {
                $request_param['site_url'] = '';
                $request_param['merchant_id'] = '';
                $request_param['site_url'] = get_bloginfo('url');
                $request_param['type'] = $request['METHOD'];
                if (is_array($result)) {
                    $request_param['status'] = isset($result['ACK']) ? $result['ACK'] : '';
                }
                $request_param['mode'] = ($sandbox) ? 'sandbox' : 'live';
                $request_param['product_id'] = $product_id;
                if ($request['METHOD'] == 'DoExpressCheckoutPayment') {
                    if (isset($result['PAYMENTINFO_0_SECUREMERCHANTACCOUNTID']) && !empty($result['PAYMENTINFO_0_SECUREMERCHANTACCOUNTID'])) {
                        $request_param['merchant_id'] = $result['PAYMENTINFO_0_SECUREMERCHANTACCOUNTID'];
                    } elseif (isset($result['PAYMENTINFO_0_SELLERPAYPALACCOUNTID']) && !empty($result['PAYMENTINFO_0_SELLERPAYPALACCOUNTID'])) {
                        $request_param['merchant_id'] = $result['PAYMENTINFO_0_SELLERPAYPALACCOUNTID'];
                    }
                    $request_param['correlation_id'] = isset($result['CORRELATIONID']) ? $result['CORRELATIONID'] : '';
                    $request_param['transaction_id'] = isset($result['PAYMENTINFO_0_TRANSACTIONID']) ? $result['PAYMENTINFO_0_TRANSACTIONID'] : '';
                    $request_param['amount'] = isset($result['PAYMENTINFO_0_AMT']) ? $result['PAYMENTINFO_0_AMT'] : '0.00';
                    $request_param['currency'] = $result['PAYMENTINFO_0_CURRENCYCODE'] ?? $this->angelleye_nvp_lookup($request, 'PAYMENTREQUEST_0_CURRENCYCODE');
                    $request_param['custom_id'] = $this->angelleye_nvp_lookup($request, 'PAYMENTREQUEST_0_CUSTOM');
                    $request_param['invoice_id'] = $this->angelleye_nvp_lookup($request, 'PAYMENTREQUEST_0_INVNUM');
                    $request_param['debug_id'] = $request_param['correlation_id'];
                    $this->angelleye_tpv_request($request_param);
                } elseif ($request['METHOD'] == 'DoDirectPayment') {
                    if (isset($result['PAYMENTINFO_0_SECUREMERCHANTACCOUNTID']) && !empty($result['PAYMENTINFO_0_SECUREMERCHANTACCOUNTID'])) {
                        $request_param['merchant_id'] = $result['PAYMENTINFO_0_SECUREMERCHANTACCOUNTID'];
                    } elseif (isset($result['PAYMENTINFO_0_SELLERPAYPALACCOUNTID']) && !empty($result['PAYMENTINFO_0_SELLERPAYPALACCOUNTID'])) {
                        $request_param['merchant_id'] = $result['PAYMENTINFO_0_SELLERPAYPALACCOUNTID'];
                    }
                    $request_param['correlation_id'] = isset($result['CORRELATIONID']) ? $result['CORRELATIONID'] : '';
                    $request_param['transaction_id'] = isset($result['TRANSACTIONID']) ? $result['TRANSACTIONID'] : '';
                    $request_param['amount'] = isset($result['AMT']) ? $result['AMT'] : '0.00';
                    $request_param['currency'] = $result['CURRENCYCODE'] ?? $this->angelleye_nvp_lookup($request, 'CURRENCYCODE');
                    $request_param['custom_id'] = $request['CUSTOM'] ?? $this->angelleye_nvp_lookup($request, 'PAYMENTREQUEST_0_CUSTOM');
                    $request_param['invoice_id'] = $request['INVNUM'] ?? $this->angelleye_nvp_lookup($request, 'PAYMENTREQUEST_0_INVNUM');
                    $request_param['debug_id'] = $request_param['correlation_id'];
                    $this->angelleye_tpv_request($request_param);
                } elseif ($request['METHOD'] == 'DoCapture') {
                    if (isset($result['PAYMENTINFO_0_SECUREMERCHANTACCOUNTID']) && !empty($result['PAYMENTINFO_0_SECUREMERCHANTACCOUNTID'])) {
                        $request_param['merchant_id'] = $result['PAYMENTINFO_0_SECUREMERCHANTACCOUNTID'];
                    } elseif (isset($result['PAYMENTINFO_0_SELLERPAYPALACCOUNTID']) && !empty($result['PAYMENTINFO_0_SELLERPAYPALACCOUNTID'])) {
                        $request_param['merchant_id'] = $result['PAYMENTINFO_0_SELLERPAYPALACCOUNTID'];
                    }
                    $request_param['correlation_id'] = isset($result['CORRELATIONID']) ? $result['CORRELATIONID'] : '';
                    $request_param['transaction_id'] = isset($result['TRANSACTIONID']) ? $result['TRANSACTIONID'] : '';
                    $request_param['amount'] = isset($result['AMT']) ? $result['AMT'] : '0.00';
                    // DoCapture response doesn't echo CURRENCYCODE; fall back to the request value.
                    $request_param['currency'] = $result['CURRENCYCODE'] ?? $request['CURRENCYCODE'] ?? '';
                    $request_param['custom_id'] = $request['CUSTOM'] ?? '';
                    $request_param['invoice_id'] = $request['INVNUM'] ?? '';
                    $request_param['debug_id'] = $request_param['correlation_id'];
                    $this->angelleye_tpv_request($request_param);
                } elseif ($request['METHOD'] == 'ProcessTransaction') {
                    if($payment_method == 'paypal_advanced') {
                        $request_param['type'] = 'PayFlow-Advanced';
                    } else {
                        $request_param['type'] = 'PayFlow-Pro';
                    }
                    if (isset($result['RESULT']) && ( $result['RESULT'] == 0 )) {
                        $request_param['status'] = 'Success';
                    } else {
                        $request_param['status'] = 'Failure';
                    }

                    $request_param['merchant_id'] = isset($request['merchant_id']) ? $request['merchant_id'] : '';
                    $request_param['correlation_id'] = isset($result['CORRELATIONID']) ? $result['CORRELATIONID'] : '';
                    $request_param['transaction_id'] = isset($result['PNREF']) ? $result['PNREF'] : '';
                    $request_param['amount'] = isset($result['AMT']) ? $result['AMT'] : '0.00';
                    $request_param['currency'] = $result['CURRENCY'] ?? $this->angelleye_nvp_lookup($request, 'CURRENCY');
                    // Plugin never sets a Payflow custom field on outgoing requests, so leave blank.
                    $request_param['custom_id'] = '';
                    $request_param['invoice_id'] = $this->angelleye_nvp_lookup($request, 'INVNUM');
                    $request_param['debug_id'] = $request_param['correlation_id'];
                    $this->angelleye_tpv_request($request_param);
                } elseif ($request['METHOD'] == 'Braintree') {
                    if ($result->success) {
                        $request_param['status'] = 'Success';
                    } else {
                        $request_param['status'] = 'Failure';
                    }
                    if (isset($result->transaction->statusHistory[0]->user) && !empty($result->transaction->statusHistory[0]->user)) {
                        $request_param['merchant_id'] = $result->transaction->statusHistory[0]->user;
                    }
                    $request_param['correlation_id'] = '';
                    $request_param['transaction_id'] = isset($result->transaction->id) ? $result->transaction->id : '';
                    $request_param['amount'] = isset($result->transaction->amount) ? $result->transaction->amount : '0.00';
                    $request_param['currency'] = isset($result->transaction->currencyIsoCode) ? $result->transaction->currencyIsoCode : '';
                    $request_param['custom_id'] = isset($result->transaction->customFields['custom_id']) ? $result->transaction->customFields['custom_id'] : '';
                    $request_param['invoice_id'] = isset($result->transaction->orderId) ? $result->transaction->orderId : '';
                    $request_param['debug_id'] = '';
                    $this->angelleye_tpv_request($request_param);
                } elseif ($request['METHOD'] == 'PayPal Credit Card (REST)') {
                    if (isset($result->id)) {
                        $request_param['status'] = 'Success';
                        $request_param['transaction_id'] = isset($result->id) ? $result->id : '';
                    } else {
                        $request_param['status'] = 'Failure';
                    }
                    $request_param['correlation_id'] = '';
                    $request_param['amount'] = isset($result->amount->total) ? $result->amount->total : '0.00';
                    $request_param['currency'] = isset($result->amount->currency) ? $result->amount->currency : '';
                    // Sale/Authorization objects don't expose custom/invoice_number — those live on the
                    // parent Payment. Plumbing them into this hook would require upstream changes.
                    $request_param['custom_id'] = '';
                    $request_param['invoice_id'] = '';
                    $request_param['debug_id'] = '';
                    $this->angelleye_tpv_request($request_param);
                }
            }
        }
        return $result_data;
    }

    /**
     * Look up an NVP value tolerating Payflow length-prefixed keys (e.g. CURRENCY[3]).
     */
    private function angelleye_nvp_lookup($data, $key) {
        if (!is_array($data)) {
            return '';
        }
        if (isset($data[$key])) {
            return $data[$key];
        }
        foreach ($data as $k => $v) {
            if (preg_match('/^' . preg_quote($key, '/') . '\[\d+\]$/', $k)) {
                return $v;
            }
        }
        return '';
    }

    public function angelleye_nvp_to_array($NVPString) {
        $proArray = array();
        while (strlen($NVPString)) {
            $keypos = strpos($NVPString, '=');
            $keyval = substr($NVPString, 0, $keypos);
            $valuepos = strpos($NVPString, '&') ? strpos($NVPString, '&') : strlen($NVPString);
            $valval = substr($NVPString, $keypos + 1, $valuepos - $keypos - 1);
            $proArray[$keyval] = urldecode($valval);
            $NVPString = substr($NVPString, $valuepos + 1, strlen($NVPString));
        }
        return $proArray;
    }

    public function angelleye_tpv_request($request_param) {
        try {
            $payment_type = $request_param['type'];
            $amount = $request_param['amount'];
            $status = $request_param['status'];
            $site_url = $request_param['site_url'];
            $payment_mode = $request_param['mode'];
            $merchant_id = $request_param['merchant_id'];
            $correlation_id = $request_param['correlation_id'];
            $transaction_id = $request_param['transaction_id'];
            $product_id = $request_param['product_id'];
            $env_meta = [
                'php_version' => phpversion(),
                'pfw_version' => defined('VERSION_PFW') ? VERSION_PFW : '',
                'woocommerce_version' => defined('WC_VERSION') ? WC_VERSION : '',
                'wp_version' => get_bloginfo('version'),
                'bn' => angelleye_ppcp_get_partner_attribution_id(),
            ];
            $meta = !empty($request_param['meta']) && is_array($request_param['meta'])
                ? array_merge($env_meta, $request_param['meta'])
                : $env_meta;
            $params = [
                "product_id" => $product_id,
                "type" => $payment_type,
                "amount" => $amount,
                "status" => $status,
                "site_url" => $site_url,
                "mode" => $payment_mode,
                "merchant_id" => $merchant_id,
                "correlation_id" => $correlation_id,
                "transaction_id" => $transaction_id,
                "net_amount" => $request_param['net_amount'] ?? '',
                "paypal_fee" => $request_param['paypal_fee'] ?? '',
                "platform_fee" => $request_param['platform_fee'] ?? '',
                "currency" => $request_param['currency'] ?? '',
                "custom_id" => $request_param['custom_id'] ?? '',
                "invoice_id" => $request_param['invoice_id'] ?? '',
                "debug_id" => $request_param['debug_id'] ?? '',
                "meta" => $meta,
            ];
            $params = apply_filters('angelleye_log_params', $params);
            // Temporary log for TPV tracker payload verification — remove after testing
            // error_log('TPV_TRACKER_PAYLOAD: ' . json_encode($params, JSON_PRETTY_PRINT), 3, __DIR__ . '/tpv_tracker_payload.log');
            $post_args = array(
                'headers' => array(
                    'Content-Type' => 'application/json; charset=utf-8',
                    'x-api-key' => $this->api_key
                ),
                'body' => json_encode($params),
                'method' => 'POST',
                'data_format' => 'body',
            );
            $response = wp_remote_post($this->api_url, $post_args);
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                error_log(print_r($error_message, true));
                return false;
            } else {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if ($body['status']) {
                    return true;
                }
            }
            return false;
        } catch (Exception $ex) {
            
        }
    }

}
