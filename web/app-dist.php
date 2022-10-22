<?php

namespace My;


use ByJG\RestServer\Route\RouteList;

/**
 * Basic Handler Object
 *
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Defining Routes
$routeDefinition = new RouteList();

$routeDefinition->addRoute(\ByJG\RestServer\Route\Route::get(
    "/testjson",
    \ByJG\RestServer\OutputProcessor\JsonOutputProcessor::class,
    \My\ClassName::class,
    "someMethod"
));

$routeDefinition->addRoute(\ByJG\RestServer\Route\Route::get(
    "/testxml",
    \ByJG\RestServer\OutputProcessor\XmlOutputProcessor::class,
    \My\ClassName::class,
    "someMethod"
));

$routeDefinition->addRoute(\ByJG\RestServer\Route\Route::get(
    "/testclosure",
    \ByJG\RestServer\OutputProcessor\JsonOutputProcessor::class,
    function ($response, $request) {
        // throw new Error501Exception("Teste");
        $response->write('OK');
    }
));

// Handle Request
$restServer = new \ByJG\RestServer\HttpRequestHandler();
// $restServer->withDetailedErrorHandler();
// $restServer->withCorsOrigins('localhost.*');
$restServer->handle($routeDefinition);

/**
 * Class ClassName
 *
 * This is an example class for process the request
 *
 * @package My
 */
class ClassName
{
    public function someMethod($response, $request)
    {
        $response->write(["name" => 'It worked']);
    }
}