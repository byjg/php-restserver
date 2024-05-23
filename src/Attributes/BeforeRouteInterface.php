<?php

namespace ByJG\RestServer\Attributes;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

interface BeforeRouteInterface
{
    public function processBefore(HttpResponse $response, HttpRequest $request): void;
}
