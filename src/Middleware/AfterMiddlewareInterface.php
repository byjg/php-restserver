<?php

namespace ByJG\RestServer\Middleware;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use Exception;

interface AfterMiddlewareInterface
{
    /**
     * Process the middleware list
     *
     * @param HttpResponse $response
     * @param HttpRequest $request
     * @param string $class
     * @param string $method
     * @param Exception|null $exception
     * @return MiddlewareResult
     */
    public function afterProcess(HttpResponse $response, HttpRequest $request, string $class, string $method, ?Exception $exception): MiddlewareResult;
}
