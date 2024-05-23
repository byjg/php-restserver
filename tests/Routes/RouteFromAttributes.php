<?php

namespace Tests\Routes;

use ByJG\RestServer\Attributes\RouteDefinition;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

class RouteFromAttributes
{
    #[RouteDefinition('GET', '/route1')]
    #[BeforeProcess]
    public function route1(HttpResponse $response, HttpRequest $request)
    {
        $response->write(["a" => 'Route 1 get']);
    }

    #[RouteDefinition('POST', '/route1')]
    #[AfterProcess]
    public function route1Post(HttpResponse $response, HttpRequest $request)
    {
        $response->write(["a" => 'Route 1 post']);
    }
}