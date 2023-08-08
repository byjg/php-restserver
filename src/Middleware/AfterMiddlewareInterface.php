<?php

namespace ByJG\RestServer\Middleware;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

interface AfterMiddlewareInterface
{
    /**
     * Undocumented function
     *
     * @param HttpResponse $response
     * @param HttpRequest $request
     * @param $class
     * @param $method
     * @return MiddlewareResult
     */
    public function afterProcess(HttpResponse $response, HttpRequest $request, $class, $method, $exception);
}
