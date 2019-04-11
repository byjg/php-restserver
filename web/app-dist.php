<?php

namespace My;

/**
 * Basic Handler Object
 *
 */

require_once __DIR__ . '/../vendor/autoload.php';

$restServer = new \ByJG\RestServer\ServerRequestHandler();

$restServer->addRoute(new \ByJG\RestServer\RoutePattern(
    'GET',
    '/test',
    \ByJG\RestServer\HandleOutput\JsonHandler::class,
    'someMethod',
    \My\ClassName::class
));

$restServer->addRoute(new \ByJG\RestServer\RoutePattern(
    'GET',
    '/testclosure',
    \ByJG\RestServer\HandleOutput\JsonHandler::class,
    function ($response, $request) {
        $response->write('OK');
    }
));

$restServer->handle();

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
        $response->write('It worked');
    }
}