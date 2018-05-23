<?php

namespace M1naret\GraphQL;

use GraphQL\GraphQL as GraphQLBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Schema;
use M1naret\GraphQL\Exception\SchemaNotFound;
use M1naret\GraphQL\Support\PaginationType;

class GraphQL
{
    /** @noinspection PhpUndefinedClassInspection */
    /** @noinspection PhpUndefinedNamespaceInspection */
    /**
     * @var \Illuminate\Foundation\Application|\Laravel\Lumen\Application
     */
    protected $app;

    /**
     * @var array
     */
    protected $schemas = [];

    protected $types = [];

    protected $typesInstances = [];

    /**
     * GraphQL constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

        /**
     * Check if the schema expects a nest URI name and return the formatted version
     * Eg. 'user/me'
     * will open the query path /graphql/user/me
     *
     * @param $name
     * @param $schemaParameterPattern
     * @param $queryRoute
     *
     * @return mixed
     */
    public static function routeNameTransformer($name, $schemaParameterPattern, $queryRoute)
    {
        $multiLevelPath = explode('/', $name);
        $routeName = null;

        if (\count($multiLevelPath) > 1) {
            foreach ($multiLevelPath as $multiName) {
                $routeName = !$routeName ? null : $routeName . '/';
                $routeName .= preg_replace($schemaParameterPattern, '{' . $multiName . '}', $queryRoute);
            }
        }

        return $routeName ?: preg_replace($schemaParameterPattern, '{' . $name . '}', $queryRoute);
    }/** @noinspection ArrayTypeOfParameterByDefaultValueInspection */
    /** @noinspection ArrayTypeOfParameterByDefaultValueInspection */

    /**
     * @param            $query
     * @param null|array $params
     * @param array|null $opts - additional options, like 'schema', 'context' or 'operationName'
     *
     * @return array
     *
     * @throws \RuntimeException
     * @throws SchemaNotFound
     */
    public function query($query, $params = [], $opts = []): array
    {
        $executionResult = $this->queryAndReturnResult($query, $params, $opts);

        $data = [
            'data' => $executionResult->data,
        ];

        // Add errors
        if (!empty($executionResult->errors)) {
            $errorFormatter = config('graphql.error_formatter', ['\M1naret\GraphQL', 'formatError']);

            $data['errors'] = array_map($errorFormatter, $executionResult->errors);
        }

        return $data;
    }

    /** @noinspection ArrayTypeOfParameterByDefaultValueInspection */
    /** @noinspection ArrayTypeOfParameterByDefaultValueInspection */
    /**
     * @param            $query
     * @param null|array $params
     * @param null|array $opts
     *
     * @return \GraphQL\Executor\ExecutionResult|\GraphQL\Executor\Promise\Promise
     *
     * @throws \RuntimeException
     * @throws SchemaNotFound
     */
    public function queryAndReturnResult($query, $params = [], $opts = [])
    {
        $context = array_get($opts, 'context');
        $schemaName = array_get($opts, 'schema');
        $operationName = array_get($opts, 'operationName');
        $schema = $this->schema($schemaName);

        return GraphQLBase::executeQuery($schema, $query, null, $context, $params, $operationName);
    }

/**
     * @param null $schema
     *
     * @return array|Schema|mixed|null|string
     *
     * @throws \RuntimeException
     * @throws SchemaNotFound
     */
    public function schema($schema = null)
    {
        if ($schema instanceof Schema) {
            return $schema;
        }

        $this->typesInstances = [];
        foreach ($this->types as $name => $type) {
            $this->type($name);
        }

        $schemaName = \is_string($schema) ? $schema : config('graphql.default_schema', 'default');

        if (!\is_array($schema) && !isset($this->schemas[$schemaName])) {
            throw new SchemaNotFound('Type ' . $schemaName . ' not found.');
        }

        $schema = \is_array($schema) ? $schema : $this->schemas[$schemaName];

        $schemaQuery = array_get($schema, 'query', []);
        $schemaMutation = array_get($schema, 'mutation', []);
        $schemaSubscription = array_get($schema, 'subscription', []);

        /** @var array $schemaTypes */
        $schemaTypes = array_get($schema, 'types', []);

        //Get the types either from the schema, or the global types.
        $types = [];
        if (\count($schemaTypes)) {
            foreach ($schemaTypes as $name => $type) {
                $objectType = $this->objectType($type, is_numeric($name) ? [] : [
                    'name' => $name,
                ]);
                $this->typesInstances[$name] = $objectType;
                $types[] = $objectType;
            }
        } else {
            foreach ($this->types as $name => $type) {
                $types[] = $this->type($name);
            }
        }

        $query = $this->objectType($schemaQuery, [
            'name' => 'Query',
        ]);

        $mutation = $this->objectType($schemaMutation, [
            'name' => 'Mutation',
        ]);

        $subscription = $this->objectType($schemaSubscription, [
            'name' => 'Subscription',
        ]);

        return new Schema([
            'query' => $query,
            'mutation' => !empty($schemaMutation) ? $mutation : null,
            'subscription' => !empty($schemaSubscription) ? $subscription : null,
            'types' => $types,
        ]);
    }

/**
     * @param      $name
     * @param bool $fresh
     *
     * @return mixed
     * @throws \RuntimeException
     */
    public function type($name, $fresh = false)
    {
        if (!isset($this->types[$name])) {
            throw new \RuntimeException('Type ' . $name . ' not found.');
        }

        if (!$fresh && isset($this->typesInstances[$name])) {
            return $this->typesInstances[$name];
        }

        $type = $this->types[$name];
        if (!\is_object($type)) {
            $type = app($type);
        }

        $instance = $type->toType();
        $this->typesInstances[$name] = $instance;

        return $instance;
    }

    /**
     * @param            $type
     * @param null|array $opts
     *
     * @return ObjectType|null
     */
    public function objectType($type, $opts = [])
    {
        // If it's already an ObjectType, just update properties and return it.
        // If it's an array, assume it's an array of fields and build ObjectType
        // from it. Otherwise, build it from a string or an instance.
        $objectType = null;
        if ($type instanceof ObjectType) {
            $objectType = $type;
            foreach ($opts as $key => $value) {
                if (property_exists($objectType, $key)) {
                    $objectType->{$key} = $value;
                }
                if (isset($objectType->config[$key])) {
                    $objectType->config[$key] = $value;
                }
            }
        } elseif (\is_array($type)) {
            $objectType = $this->buildObjectTypeFromFields($type, $opts);
        } else {
            $objectType = $this->buildObjectTypeFromClass($type, $opts);
        }

        return $objectType;
    }/** @noinspection ArrayTypeOfParameterByDefaultValueInspection */

        /**
     * @param array $fields
     * @param array|null $opts
     *
     * @return ObjectType
     */
    protected function buildObjectTypeFromFields(array $fields, $opts = []): ObjectType
    {
        $typeFields = [];
        foreach ($fields as $name => $field) {
            if (\is_string($field)) {
                $field = $this->app->make($field);
                $name = is_numeric($name) ? $field->name : $name;
                $field->name = $name;
                $field = $field->toArray();
            } else {
                $name = is_numeric($name) ? $field['name'] : $name;
                $field['name'] = $name;
            }
            $typeFields[$name] = $field;
        }

        return new ObjectType(array_merge([
            'fields' => $typeFields,
        ], $opts));
    }/** @noinspection ArrayTypeOfParameterByDefaultValueInspection */

    /**
     * @param            $type
     * @param null|array $opts
     *
     * @return mixed
     */
    protected function buildObjectTypeFromClass($type, $opts = [])
    {
        if (!\is_object($type)) {
            $type = $this->app->make($type);
        }

        foreach ($opts as $key => $value) {
            $type->{$key} = $value;
        }

        return $type->toType();
    }/** @noinspection ArrayTypeOfParameterByDefaultValueInspection */

    public function addTypes(array $types)
    {
        foreach ($types as $name => $type) {
            $this->addType($type, is_numeric($name) ? null : $name);
        }
    }

    public function addType($class, $name = null)
    {
        if (!$name) {
            $type = \is_object($class) ? $class : app($class);
            $name = $type->name;
        }

        $this->types[$name] = $class;
    }

    public function addSchema($name, $schema)
    {
        $this->schemas[$name] = $schema;
    }

    public function clearType($name)
    {
        if (isset($this->types[$name])) {
            unset($this->types[$name]);
        }
    }

    public function clearSchema($name)
    {
        if (isset($this->schemas[$name])) {
            unset($this->schemas[$name]);
        }
    }

    public function clearTypes()
    {
        $this->types = [];
    }

    public function clearSchemas()
    {
        $this->schemas = [];
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function getSchemas(): array
    {
        return $this->schemas;
    }

    public function paginate($typeName, $customName = null)
    {
        $name = $customName ?: $typeName . '_pagination';

        if (!isset($this->typesInstances[$name])) {
            $this->typesInstances[$name] = new PaginationType($typeName, $customName);
        }

        return $this->typesInstances[$name];
    }

    protected function clearTypeInstances()
    {
        $this->typesInstances = [];
    }

    protected function getTypeName($class, $name = null): string
    {
        if ($name) {
            return $name;
        }

        return \is_object($class) ? $class : $this->app->make($class)->name;
    }
}
