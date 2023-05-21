<?php

namespace ByJG\RestServer\Middleware;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\OutputProcessor\BaseOutputProcessor;

interface AfterMiddlewareInterface
{
    /**
     * Undocumented function
     *
     * @param HttpResponse $response
     * @param HttpRequest $request
     * @return MiddlewareResult
     */
    public function afterProcess(HttpResponse $response, HttpRequest $request);
}
