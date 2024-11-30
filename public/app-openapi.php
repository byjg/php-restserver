<?php

require_once __DIR__ . '/../vendor/autoload.php';

$routeDefinition = new \ByJG\RestServer\Route\OpenApiRouteList(__DIR__ . '/../tests/fixtures/openapi-example.json');

$restServer = new \ByJG\RestServer\HttpRequestHandler();
$restServer->handle($routeDefinition);
