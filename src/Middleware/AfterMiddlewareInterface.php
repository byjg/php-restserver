<?php

namespace ByJG\RestServer\Middleware;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

interface AfterMiddlewareInterface
{
    /**
     * Process the middleware list
     *
     * @param HttpResponse $response
     * @param HttpRequest $request
     * @param $class
     * @param $method
     * @param $exception
     * @return MiddlewareResult
     */
    public function afterProcess(HttpResponse $response, HttpRequest $request, $class, $method, $exception);
}
