<?php

namespace ByJG\RestServer\Middleware;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

class DummyAfterMiddleware implements AfterMiddlewareInterface
{

    /**
     * @inheritDoc
     */
    public function afterProcess(HttpResponse $response, HttpRequest $request, $class, $method, $exception)
    {
        // TODO: Implement afterProcess() method.
        return MiddlewareResult::continue();
    }
}