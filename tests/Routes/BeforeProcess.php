<?php

namespace Tests\Routes;

use Attribute;
use ByJG\RestServer\Attributes\BeforeRouteInterface;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

#[Attribute(Attribute::TARGET_METHOD)]
class BeforeProcess implements BeforeRouteInterface
{
    public function processBefore(HttpResponse $response, HttpRequest $request): void
    {
        $response->write(["x" => 'Before Process']);
    }
}