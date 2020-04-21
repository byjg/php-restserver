<?php

require_once __DIR__ . '/../vendor/autoload.php';

$routeDefinition = new \ByJG\RestServer\Route\OpenApiRouteDefinition(__DIR__ . '/../tests/swagger-example.json');

$restServer = new \ByJG\RestServer\HttpRequestHandler();
$restServer->handle($routeDefinition);
