<?php

namespace Tests\Routes;

use Attribute;
use ByJG\RestServer\Attributes\AfterRouteInterface;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

#[Attribute(Attribute::TARGET_METHOD)]
class AfterProcess implements AfterRouteInterface
{

    #[\Override]
    public function processAfter(HttpResponse $response, HttpRequest $request): void
    {
        $response->write(["x" => 'After Process']);
    }
}