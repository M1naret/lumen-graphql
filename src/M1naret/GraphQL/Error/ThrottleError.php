<?php

namespace M1naret\GraphQL\Error;

class ThrottleError extends Error
{
    protected $code = 429;

    public $message = 'Too Many Attempts';

    public function __construct($headers)
    {
        $this->setHeaders($headers);

        parent::__construct($this->message);
    }
}