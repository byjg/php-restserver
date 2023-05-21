<?php

namespace My;

use ByJG\RestServer\Exception\Error401Exception;
use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\MockRequestHandler;
use ByJG\RestServer\OutputProcessor\XmlOutputProcessor;
use ByJG\RestServer\Route\RouteList;
use ByJG\Util\Psr7\Request;
use ByJG\Util\Uri;

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
        $response->write('OK');
        throw new Error401Exception("Bla");
    })
);

$request = Request::getInstance(Uri::getInstanceFromString("http://localhost/testxml"));

// Handle Request
$mockHandler = MockRequestHandler::mock($routeDefinition, $request);

print_r($mockHandler->getPsr7Response()->getStatusCode());
echo "\n";
print_r($mockHandler->getPsr7Response()->getHeaders());
print_r($mockHandler->getPsr7Response()->getBody()->getContents());


// $request = Request::getInstance(Uri::getInstanceFromString("http://localhost/testclosure"));
// $mockHandler = MockRequestHandler::mock($routeDefintion, $request);

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