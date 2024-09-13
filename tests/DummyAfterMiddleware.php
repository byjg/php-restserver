<?php

namespace Tests;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\Middleware\AfterMiddlewareInterface;
use ByJG\RestServer\Middleware\MiddlewareResult;

class DummyAfterMiddleware implements AfterMiddlewareInterface
{

    protected $here = 0;
    /**
     * @inheritDoc
     */
    public function afterProcess(HttpResponse $response, HttpRequest $request, $class, $method, $exception)
    {
        $this->here++;
        return MiddlewareResult::continue();
    }

    public function getHere()
    {
        return $this->here;
    }
}