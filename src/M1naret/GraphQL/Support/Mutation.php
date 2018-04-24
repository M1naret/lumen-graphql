<?php

namespace M1naret\GraphQL\Support;

class Mutation extends Field
{
    /**
     * @var array
     */
    private $customArgs = [];

    /**
     * @return array
     */
    public function args() : array
    {
        return $this->customArgs;
    }

    /**
     * @param array $args
     */
    protected function setArgs(array $args) : void
    {
        $this->customArgs = $args;
    }

    /**
     * @param array $args
     */
    protected function pushArgs(array $args) : void
    {
        $this->customArgs = array_merge($this->customArgs, $args);
    }

}
