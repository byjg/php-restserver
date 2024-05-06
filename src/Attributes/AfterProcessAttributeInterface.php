<?php

namespace ByJG\RestServer\Attributes;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

interface AfterProcessAttributeInterface
{
    public function process(HttpResponse $response, HttpRequest $request): void;
}
