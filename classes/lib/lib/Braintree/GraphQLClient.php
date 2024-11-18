<?php

namespace Braintree;

/**
 * Braintree GraphQL Client
 * process GraphQL requests using curl
 */
class GraphQLClient
{
    private $_service;

    public function __construct($config)
    {
        $this->_service = new GraphQL($config);
    }

    public function query($definition, $variables = null)
    {
        return $this->_service->request($definition, $variables);
    }
}
