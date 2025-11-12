<?php

namespace My;

use ByJG\RestServer\HttpRequestHandler;
use ByJG\RestServer\Middleware\ServerStaticMiddleware;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\OutputProcessor\XmlOutputProcessor;
use ByJG\RestServer\Route\Route;
use ByJG\RestServer\Route\RouteList;

/**
 * Basic Handler Object
 *
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Defining Routes
$routeDefinition = new RouteList();

$routeDefinition->addRoute(Route::get("/testjson")
    ->withClass(ClassName::class, "someMethod")
);

$routeDefinition->addRoute(Route::get("/testxml")
    ->withOutputProcessor(XmlOutputProcessor::class)
    ->withClass(ClassName::class, "someMethod")
);

$routeDefinition->addRoute(Route::get("/testclosure")
    ->withClosure(function ($response, $request) {
        // throw new Error501Exception("Teste");
        $response->write('OK');
    })
);

$routeDefinition->addRoute(Route::get("/testerror/{code}")
    ->withClosure(function ($response, $request) {
        $code = $request->param('code');
        $class = "ByJG\RestServer\Exception\Error" . $code . "Exception";
        throw new $class("Teste");
    })
);

// Test content-type override: XML processor configured, but override to JSON at runtime
$routeDefinition->addRoute(Route::get("/testoverride/xml-to-json")
    ->withOutputProcessor(XmlOutputProcessor::class)
    ->withClosure(function ($response, $request) {
        $response->addHeader('Content-Type', 'application/json');
        $response->write(["override" => "xml-to-json"]);
    })
);

// Test content-type override: JSON processor configured, but override to XML at runtime
$routeDefinition->addRoute(Route::get("/testoverride/json-to-xml")
    ->withOutputProcessor(JsonOutputProcessor::class)
    ->withClosure(function ($response, $request) {
        $response->addHeader('Content-Type', 'application/xml');
        $response->write(["override" => "json-to-xml"]);
    })
);

// Handle Request
$restServer = new HttpRequestHandler();
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