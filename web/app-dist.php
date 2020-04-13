<?php

namespace My;

use ByJG\RestServer\Route\RouteDefinition;

/**
 * Basic Handler Object
 *
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Defining Routes
$routeDefintion = new RouteDefinition();

$routeDefintion->addRoute(\ByJG\RestServer\Route\RoutePattern::get(
    "/testjson",
    \ByJG\RestServer\OutputProcessor\JsonOutputProcessor::class,
    \My\ClassName::class,
    "someMethod"
));

$routeDefintion->addRoute(\ByJG\RestServer\Route\RoutePattern::get(
    "/testxml",
    \ByJG\RestServer\OutputProcessor\XmlOutputProcessor::class,
    \My\ClassName::class,
    "someMethod"
));

$routeDefintion->addRoute(\ByJG\RestServer\Route\RoutePattern::get(
    "/testclosure",
    \ByJG\RestServer\OutputProcessor\JsonOutputProcessor::class,
    function ($response, $request) {
        $response->write('OK');
    }
));

// Handle Request
$restServer = new \ByJG\RestServer\ServerRequestHandler();
$restServer->handle($routeDefintion);

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