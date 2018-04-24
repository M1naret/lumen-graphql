<?php /** @noinspection ReturnTypeCanBeDeclaredInspection */

namespace M1naret\GraphQL\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Fluent;
use M1naret\GraphQL\Error\AuthorizationError;
use M1naret\GraphQL\Error\ValidationError;

class Field extends Fluent
{
    /**
     * Override this in your queries or mutations
     * to provide custom authorization
     *
     * @param array $args
     *
     * @return bool
     */
    public function authorize(array $args) : bool
    {
        return true;
    }

    public function attributes() : array
    {
        return [];
    }

    public function type()
    {
        return null;
    }

    public function args()
    {
        return [];
    }

    protected function rules(array $args = []) : array
    {
        return [];
    }

    public function getRules()
    {
        $arguments = \func_get_args();

        $rules = \call_user_func_array([$this, 'rules'], $arguments);
        $argsRules = [];
        foreach ($this->args() as $name => $arg) {
            if (isset($arg['rules'])) {
                if (\is_callable($arg['rules'])) {
                    $argsRules[$name] = \call_user_func_array($arg['rules'], $arguments);
                } else {
                    $argsRules[$name] = $arg['rules'];
                }
            }
        }

        return array_merge($argsRules, $rules);
    }

    protected function getResolver()
    {
        if (!method_exists($this, 'resolve')) {
            return null;
        }

        $resolver = [$this, 'resolve'];
        $authorize = [$this, 'authorize'];

        return function() use ($resolver, $authorize) {
            $arguments = \func_get_args();

            // Get all given arguments
            if (null !== $arguments[2] && \is_array($arguments[2])) {
                $arguments[1] = array_merge($arguments[1], $arguments[2]);
            }

            // Authorize
            /** @noinspection TypeUnsafeComparisonInspection */
            if ($authorize($arguments[1]) != true) {
                throw with(new AuthorizationError('Unauthorized'));
            }

            $args = array_get($arguments, 1, []);

            if (method_exists($this, 'prepareRequest')) {
                $args = $this->prepareRequest($args);
            }

            // Validate mutation arguments
            if (method_exists($this, 'getRules')) {
                $rules = $this->getRules($args);

                if (\count($rules)) {
                    $validator = Validator::make($args, $rules);
                    if ($validator->fails()) {
                        throw with(new ValidationError('validation'))->setValidator($validator);
                    }
                }
            }

            // на этом моменте уже все variables перезаписаны константами

            $this->parsePaginationFromArgs($args);

            $arguments[1] = $args;

            // Replace the context argument with 'selects and relations'
            // $arguments[1] is direct args given with the query
            // $arguments[2] is context (params given with the query)
            // $arguments[3] is ResolveInfo
            if (isset($arguments[3])) {
                $fields = new SelectFields($arguments[3], $this->type(), $arguments[1]);
                $arguments[2] = $fields;
            }

            return \call_user_func_array($resolver, $arguments);
        };
    }

    protected function parsePaginationFromArgs(array $args = []) : void
    {
        /** @var Request $request */
        $request = app('request');

        $request->merge($args);
    }

    /**
     * Get the attributes from the container.
     *
     * @return array
     */
    public function getAttributes()
    {
        $attributes = $this->attributes();

        $attributes = array_merge($this->attributes, [
            'args' => $this->args(),
        ], $attributes);

        $type = $this->type();
        if ($type !== null) {
            $attributes['type'] = $type;
        }

        $resolver = $this->getResolver();
        if ($resolver !== null) {
            $attributes['resolve'] = $resolver;
        }

        return $attributes;
    }

    /**
     * Convert the Fluent instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getAttributes();
    }

    /**
     * Dynamically retrieve the value of an attribute.
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        $attributes = $this->getAttributes();

        return $attributes[$key] ?? null;
    }

    /**
     * Dynamically check if an attribute is set.
     *
     * @param  string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        $attributes = $this->getAttributes();

        return isset($attributes[$key]);
    }

}
