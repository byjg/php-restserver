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
     * @param string $class
     * @param string $method
     * @param string|null $exception
     * @return MiddlewareResult
     */
    public function afterProcess(HttpResponse $response, HttpRequest $request, string $class, string $method, ?string $exception): MiddlewareResult;
}
