<?php

namespace ByJG\RestServer\Attributes;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

interface BeforeProcessAttributeInterface
{
    public function process(HttpResponse $response, HttpRequest $request): void;
}
