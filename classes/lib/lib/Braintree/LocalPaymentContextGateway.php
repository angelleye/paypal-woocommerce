<?php

namespace Braintree;

/**
 * Creates and manages local payment contexts
 */
class LocalPaymentContextGateway
{
    private $graphQLClient;

    const CREATE_LOCAL_PAYMENT_CONTEXT_MUTATION = <<<'GRAPHQL'
    mutation CreateLocalPaymentContext($input: CreateLocalPaymentContextInput!) {
        createLocalPaymentContext(input: $input) {
            paymentContext {
                id
                legacyId
                type
                paymentId
                orderId
                approvalUrl
                merchantAccountId
                amount {
                    value
                    currencyCode
                }
                createdAt
                updatedAt
                transactedAt
                approvedAt
                expiredAt
            }
        }
    }
    GRAPHQL;

    const FIND_LOCAL_PAYMENT_CONTEXT_QUERY = <<<'GRAPHQL'
    query Node($id: ID!) {
        node(id: $id) {
            ... on LocalPaymentContext {
                id
                legacyId
                type
                paymentId
                orderId
                approvalUrl
                merchantAccountId
                amount {
                    value
                    currencyIsoCode
                }
                createdAt
                updatedAt
                transactedAt
                approvedAt
                expiredAt
            }
        }
    }
    GRAPHQL;

    // phpcs:ignore PEAR.Commenting.FunctionComment.Missing
    public function __construct($graphQLClient)
    {
        $this->graphQLClient = $graphQLClient;
    }

    /**
     * Creates a new local payment context
     *
     * Example:
     *   $result = $gateway->localPayment()->create([
     *       'amount' => [
     *           'value' => '10.00',
     *           'currencyCode' => 'EUR'
     *       ],
     *       'type' => LocalPaymentType::MBWAY,
     *       'payerInfo' => [
     *           'givenName' => 'John',
     *           'surname' => 'Doe',
     *           'email' => 'john@example.com',
     *           'billingAddress' => [
     *               'countryCode' => 'PT',
     *               'streetAddress' => 'Rua da Liberdade, 79',
     *               'locality' => 'Lisbon',
     *               'postalCode' => '1250-140'
     *           ]
     *       ],
     *       'returnUrl' => 'https://example.com/return',
     *       'cancelUrl' => 'https://example.com/cancel',
     *       'merchantAccountId' => 'eur_pwpp_multi_account_merchant_account'
     *   ]);
     *
     * @param array $attributes The attributes for creating a local payment context
     *
     * @return Result\Error|Result\Successful
     */
    public function create($attributes)
    {
        Util::verifyKeys(self::createSignature(), $attributes);
        return $this->_doCreate($attributes);
    }

    /**
     * Finds a local payment context by ID
     *
     * Example:
     *   $result = $gateway->localPayment()->find('payment_context_id');
     *
     * @param string $id The ID of the local payment context
     *
     * @return LocalPayment
     *
     * @throws Exception\NotFound
     */
    public function find($id)
    {
        $response = $this->graphQLClient->query(self::FIND_LOCAL_PAYMENT_CONTEXT_QUERY, ['id' => $id]);

        $errors = GraphQLClient::getValidationErrors($response);
        if ($errors) {
            throw new Exception\NotFound('Local payment context not found');
        }

        if (!isset($response['data']['node'])) {
            throw new Exception\NotFound('Local payment context not found');
        }

        return LocalPayment::factory($response['data']['node']);
    }

    /**
     * Returns the signature for create validation
     *
     * @return array
     */
    public static function createSignature()
    {
        return [
            ['amount' => ['value', 'currencyCode']],
            'type',
            ['payerInfo' => [
                'givenName',
                'surname',
                'email',
                'phoneNumber',
                'phoneCountryCode',
                ['billingAddress' => [
                    'countryCode',
                    'streetAddress',
                    'extendedAddress',
                    'locality',
                    'region',
                    'postalCode'
                ]],
                ['shippingAddress' => [
                    'countryCode',
                    'streetAddress',
                    'extendedAddress',
                    'locality',
                    'region',
                    'postalCode'
                ]]
            ]],
            'returnUrl',
            'cancelUrl',
            'merchantAccountId',
            'orderId',
            'countryCode',
            'expiryDate',
            'paymentId'
        ];
    }

    /**
     * Performs the create operation via GraphQL
     *
     * @param array $attributes
     *
     * @return Result\Error|Result\Successful
     */
    private function _doCreate($attributes)
    {
        $variables = ['input' => ['paymentContext' => $this->_prepareAttributes($attributes)]];
        $response = $this->graphQLClient->query(self::CREATE_LOCAL_PAYMENT_CONTEXT_MUTATION, $variables);

        $errors = GraphQLClient::getValidationErrors($response);
        if ($errors) {
            return new Result\Error(['errors' => $errors]);
        }

        if (!isset($response['data']['createLocalPaymentContext']['paymentContext'])) {
            throw new Exception\ServerError("Couldn't parse server response");
        }

        $paymentContextData = $response['data']['createLocalPaymentContext']['paymentContext'];
        return new Result\Successful(LocalPayment::factory($paymentContextData));
    }

    /**
     * Prepares attributes for GraphQL mutation
     *
     * @param array $attributes
     *
     * @return array
     */
    private function _prepareAttributes($attributes)
    {
        $prepared = [];

        if (isset($attributes['amount'])) {
            $prepared['amount'] = $attributes['amount'];
        }

        if (isset($attributes['type'])) {
            $prepared['type'] = $attributes['type'];
        }

        if (isset($attributes['payerInfo'])) {
            $prepared['payerInfo'] = $attributes['payerInfo'];
        }

        if (isset($attributes['returnUrl'])) {
            $prepared['returnUrl'] = $attributes['returnUrl'];
        }

        if (isset($attributes['cancelUrl'])) {
            $prepared['cancelUrl'] = $attributes['cancelUrl'];
        }

        if (isset($attributes['merchantAccountId'])) {
            $prepared['merchantAccountId'] = $attributes['merchantAccountId'];
        }

        if (isset($attributes['orderId'])) {
            $prepared['orderId'] = $attributes['orderId'];
        }

        if (isset($attributes['countryCode'])) {
            $prepared['countryCode'] = $attributes['countryCode'];
        }

        if (isset($attributes['expiryDate'])) {
            $prepared['expiryDate'] = $attributes['expiryDate'];
        }

        if (isset($attributes['paymentId'])) {
            $prepared['paymentId'] = $attributes['paymentId'];
        }

        return $prepared;
    }
}
