<?php

namespace M1naret\GraphQL\Support;

use M1naret\GraphQL\Support\Facades\GraphQL;

class Mutation extends Field
{
    /**
     * @return null
     */
    public function type()
    {
        return GraphQL::type($this->get('type'));
    }
}
