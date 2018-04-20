<?php

namespace M1naret\GraphQL\Support;

use GraphQL\Type\Definition\Type;
use Illuminate\Http\Request;

class Query extends Field
{
    private $customArgs = [];

    private $defaultArgs = [];

    /** @var array */
    private $variables = [];

    public function __construct($attributes = [])
    {
        parent::__construct($attributes);

        $this->parseVariablesFromRequest();

        $this->setDefaultArgs();
    }

    protected function parseVariablesFromRequest(): void
    {
        /** @var Request $request */
        $request = app('request');

        \is_array($variables = $request->get('variables', [])) && $this->setVariables($variables);

        if ($perPage = (int)$this->getVariable('per_page')) {
            $request->merge([
                'per_page' => $perPage,
            ]);
        }
        if ($page = (int)$this->getVariable('page')) {
            $request->merge([
                'page' => $page,
            ]);
        }
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
        $this->defaultArgs = [
            'return' => [
                'name' => 'return',
                'type' => Type::string(),
            ],
        ];
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
