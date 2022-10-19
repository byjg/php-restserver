<?php

namespace My;

use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\MockRequestHandler;
use ByJG\RestServer\Route\RouteList;
use ByJG\Util\Psr7\Request;
use ByJG\Util\Uri;

/**
 * Basic Handler Object
 *
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Defining Routes
$routeDefintion = new RouteList();

$routeDefintion->addRoute(\ByJG\RestServer\Route\Route::get(
    "/testjson",
    \ByJG\RestServer\OutputProcessor\JsonOutputProcessor::class,
    \My\ClassName::class,
    "someMethod"
));

$routeDefintion->addRoute(\ByJG\RestServer\Route\Route::get(
    "/testxml",
    \ByJG\RestServer\OutputProcessor\XmlOutputProcessor::class,
    \My\ClassName::class,
    "someMethod"
));

$routeDefintion->addRoute(\ByJG\RestServer\Route\Route::get(
    "/testclosure",
    \ByJG\RestServer\OutputProcessor\JsonOutputProcessor::class,
    function ($response, $request) {
        $response->write('OK');
        throw new Error404Exception("Bla");
    }
));

$request = Request::getInstance(Uri::getInstanceFromString("http://localhost/testxml"));

// Handle Request
$response = MockRequestHandler::mock($routeDefintion, $request);

print_r($response->getStatusCode());
echo "\n";
print_r($response->getHeaders());
print_r($response->getBody()->getContents());


$request = Request::getInstance(Uri::getInstanceFromString("http://localhost/testclosure"));

// Handle Request
$response = MockRequestHandler::mock($routeDefintion, $request);

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