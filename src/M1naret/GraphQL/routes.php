<?php

/** @var \Laravel\Lumen\Routing\Router|Illuminate\Routing\Router $router */
$router->group([
    'prefix'     => config('graphql.prefix'),
    'domain'     => config('graphql.domain'),
    'middleware' => config('graphql.middleware', []),
], function($router) {
    /** @var \Laravel\Lumen\Routing\Router|Illuminate\Routing\Router $router */

    //Get controllers from config
    $controllers = config('graphql.controllers', '\M1naret\GraphQL\GraphQLController@query');
    if (is_array($controllers)) {
        $queryController = array_get($controllers, 'query', null);
        $mutationController = array_get($controllers, 'mutation', null);
    } else {
        $queryController = $controllers;
        $mutationController = $controllers;
    }

    $defaultMiddleware = config('graphql.schemas.' . config('graphql.default_schema') . '.middleware', []);
    $router->post('', [
        'uses'       => $queryController,
        'middleware' => $defaultMiddleware,
    ]);
    $router->post('', [
        'uses'       => $mutationController,
        'middleware' => $defaultMiddleware,
    ]);

    /** @var array $schemas */
    $schemas = config('graphql.schemas', []);
    foreach ($schemas as $name => $schema) {
        $router->post($name, [
            'uses'       => $queryController,
            'middleware' => array_get($schema, 'middleware', []),
        ]);
        $router->post($name, [
            'uses'       => $mutationController,
            'middleware' => array_get($schema, 'middleware', []),
        ]);
    }
});
