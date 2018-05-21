<?php

/** @var \Laravel\Lumen\Routing\Router|Illuminate\Routing\Router $router */
$router->group([
    'prefix'     => config('graphql.prefix'),
    'domain'     => config('graphql.domain'),
    'middleware' => config('graphql.middleware', []),
], function($router) {
    /** @var \Laravel\Lumen\Routing\Router|Illuminate\Routing\Router $router */

    //Get routes from config
    $routes = config('graphql.routes');
    if (is_array($routes)) {
        $queryRoute = array_get($routes, 'query', null);
        $mutationRoute = array_get($routes, 'mutation', null);
    } else {
        $queryRoute = (string)$routes;
        $mutationRoute = (string)$routes;
    }

    //Get controllers from config
    $controllers = config('graphql.controllers', '\M1naret\GraphQL\GraphQLController@query');
    $queryController = null;
    $mutationController = null;
    if (is_array($controllers)) {
        $queryController = array_get($controllers, 'query', null);
        $mutationController = array_get($controllers, 'mutation', null);
    } else {
        $queryController = $controllers;
        $mutationController = $controllers;
    }

    $defaultMiddleware = config('graphql.schemas.' . config('graphql.default_schema') . '.middleware', []);
    $router->post($queryRoute, [
        'uses'       => $queryController,
        'middleware' => $defaultMiddleware,
    ]);
    $router->post($mutationRoute, [
        'uses'       => $mutationController,
        'middleware' => $defaultMiddleware,
    ]);

    foreach (config('graphql.schemas') as $name => $schema) {
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
