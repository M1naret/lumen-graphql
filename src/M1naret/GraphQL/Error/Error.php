<?php

namespace M1naret\GraphQL\Error;

use GraphQL\Error\Error as GraphQLError;

class Error extends GraphQLError
{
    protected $headers = [];

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * @return array
     */
    public function getHeaders() : array
    {
        return $this->headers;
    }
}