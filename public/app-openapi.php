<?php

use ByJG\RestServer\Route\OpenApiRouteList;
use ByJG\RestServer\Server;

require_once __DIR__ . '/../vendor/autoload.php';

$routeDefinition = new OpenApiRouteList(__DIR__ . '/../tests/fixtures/openapi-example.json');

$restServer = new Server();
$restServer->handle($routeDefinition);
