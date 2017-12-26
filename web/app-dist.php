<?php

require_once __DIR__ . '/../vendor/autoload.php';

$routes = [];

$routes[] = new \ByJG\RestServer\RoutePattern(
    'GET',
    '/test',
    \ByJG\RestServer\HandleOutput\JsonHandler::class,
    'SomeMethod',
    '\\Some\\Class'
);

$routes[] = new \ByJG\RestServer\RoutePattern(
    'GET',
    '/testclosure',
    \ByJG\RestServer\HandleOutput\JsonHandler::class,
    function ($request, $response) {
        $response->write('OK');
    }
);

\ByJG\RestServer\ServerRequestHandler::handle($routes);
