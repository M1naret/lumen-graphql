<?php

namespace M1naret\GraphQL;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GraphQLController extends BaseController
{
    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \RuntimeException
     * @throws Exception\SchemaNotFound
     */
    public function query(Request $request) : JsonResponse
    {
        $schema = $this->getSchema($request);

        $isBatch = !$request->has('query');
        $inputs = $request->all();

        if (!$isBatch) {
            $data = $this->executeQuery($schema, $inputs);
        } else {
            $data = [];
            foreach ($inputs as $input) {
                $data[] = $this->executeQuery($schema, $input);
            }
        }

        $headers = config('graphql.headers', []);
        $options = config('graphql.json_encoding_options', 0);

        $errors = !$isBatch ? array_get($data, 'errors', []) : [];
        foreach ($errors as $error){
            $headers += array_get($error, 'headers', []);
        }

        $authorized = array_reduce($errors, function($authorized, $error) {
            return !(!$authorized || array_get($error, 'message') === 'Unauthorized');
        }, true);

        if (!$authorized) {
            return response()->json($data, 403, $headers, $options);
        }
        return response()->json($data, 200, $headers, $options);
    }

    private function getSchema(Request $request)
    {
        $schema = str_replace(config('graphql.prefix', ''), '', $request->path());
        $schema = trim($schema, '/');
        if (!$schema) {
            $schema = config('graphql.default_schema');
        }
        return $schema;
    }

    /**
     * @param $schema
     * @param $input
     * @return array
     *
     * @throws \RuntimeException
     * @throws Exception\SchemaNotFound
     */
    protected function executeQuery($schema, $input) : array
    {
        $variablesInputName = config('graphql.variables_key', 'variables');
        $query = array_get($input, 'query');
        $variables = array_get($input, $variablesInputName);
        if (\is_string($variables)) {
            $variables = json_decode($variables, true);
        }
        $operation = array_get($input, 'operation');
        $context = $this->queryContext($query, $variables, $schema);

        /** @var GraphQL $graphql */
        $graphql = app('graphql');
        return $graphql->query($query, $variables, [
            'context'       => $context,
            'schema'        => $schema,
            'operation' => $operation,
        ]);
    }

    protected function queryContext($query, $variables, $schema)
    {
        try {
            return app('auth')->user();
        } catch (\Exception $e) {
            return null;
        }
    }
}