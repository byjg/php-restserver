<?php

namespace My;

use ByJG\RestServer\Middleware\ServerStaticMiddleware;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\OutputProcessor\XmlOutputProcessor;
use ByJG\RestServer\Route\RouteList;

/**
 * Basic Handler Object
 *
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Defining Routes
$routeDefinition = new RouteList();

$routeDefinition->addRoute(\ByJG\RestServer\Route\Route::get("/testjson")
    ->withClass(\My\ClassName::class, "someMethod")
);

$routeDefinition->addRoute(\ByJG\RestServer\Route\Route::get("/testxml")
    ->withOutputProcessor(XmlOutputProcessor::class)
    ->withClass(\My\ClassName::class, "someMethod")
);

$routeDefinition->addRoute(\ByJG\RestServer\Route\Route::get("/testclosure")
    ->withClosure(function ($response, $request) {
        // throw new Error501Exception("Teste");
        $response->write('OK');
    })
);

// Handle Request
$restServer = new \ByJG\RestServer\HttpRequestHandler();
$restServer->withDefaultOutputProcessor(JsonOutputProcessor::class);
// $restServer->withDetailedErrorHandler();
// $restServer->withCorsOrigins('localhost.*');
$restServer->withMiddleware(new ServerStaticMiddleware());
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