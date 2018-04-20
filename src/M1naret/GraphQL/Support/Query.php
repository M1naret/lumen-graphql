<?php

namespace M1naret\GraphQL\Support;

use GraphQL\Type\Definition\Type;

class Query extends Field
{

    /**
     * @var array
     */
    private $customArgs = [];

    /**
     * @var array
     */
    private $defaultArgs = [];

    /** @var array */
    private $variables = [];

    /**
     * Query constructor.
     *
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        parent::__construct($attributes);

        $this->setDefaultArgs();
    }

    /**
     * @param array $variables
     */
    private function setVariables(array $variables): void
    {
        $this->variables = $variables;
    }

    /**
     * @return void
     */
    private function setDefaultArgs(): void
    {
        if ($this->type() instanceof PaginationType){
            $this->defaultArgs = [
                'page'  => [
                    'name' => 'page',
                    'type' => Type::int(),
                ],
                'per_page'  => [
                    'name' => 'per_page',
                    'type' => Type::int(),
                ],
            ];
        }
    }

    /**
     * @return array|mixed
     */
    private function getVariables()
    {
        return $this->variables;
    }

    /**
     * @param      $key
     * @param null $default
     *
     * @return mixed
     */
    protected function getVariable($key, $default = null)
    {
        return array_get($this->variables, $key, $default);
    }

    /**
     * @param $key
     *
     * @return bool
     */
    protected function hasVariable($key): bool
    {
        return array_has($this->variables, $key);
    }

    /**
     * @return array
     */
    public function args(): array
    {
        return array_merge($this->defaultArgs, $this->customArgs);
    }

    /**
     * @param array $args
     */
    protected function setArgs(array $args): void
    {
        $this->customArgs = $args;
    }
}
