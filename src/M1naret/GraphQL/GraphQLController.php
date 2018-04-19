<?php namespace M1naret\GraphQL;

use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;

class GraphQLController extends Controller {

    public function query(Request $request, $schema = null)
    {
        // If there are multiple route params we can expect that there
        // will be a schema name that has to be built
        if (\count($request->route()->parameters) > 1) {
            $schema = implode('/', $request->route()->parameters);
        }

        if( ! $schema)
        {
            $schema = config('graphql.default_schema');
        }

        // If a singular query was not found, it means the queries are in batch
        $isBatch = ! $request->has('query');
        $batch = $isBatch ? $request->all() : [$request->all()];

        $completedQueries = [];
        $paramsKey = config('graphql.params_key');

        $opts = [
            'context'   => $this->queryContext(),
            'schema'    => $schema,
        ];

        // Complete each query in order
        foreach($batch as $batchItem)
        {
            $query = $batchItem['query'];
            $params = array_get($batchItem, $paramsKey);

            if(\is_string($params))
            {
                $params = json_decode($params, true);
            }

            $completedQueries[] = app('graphql')->query($query, $params, array_merge($opts, [
                'operationName' => array_get($batchItem, 'operationName'),
            ]));
        }

        return $isBatch ? $completedQueries : $completedQueries[0];
    }

    protected function queryContext()
    {
        try {
            return app('auth')->user();
        } catch (\Exception $e) {
            return null;
        }
    }

}
