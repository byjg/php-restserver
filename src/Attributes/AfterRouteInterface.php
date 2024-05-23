<?php

namespace ByJG\RestServer\Attributes;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

interface AfterRouteInterface
{
    public function processAfter(HttpResponse $response, HttpRequest $request): void;
}
