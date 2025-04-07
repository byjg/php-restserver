<?php

namespace Tests;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\Middleware\AfterMiddlewareInterface;
use ByJG\RestServer\Middleware\MiddlewareResult;

class DummyAfterMiddleware implements AfterMiddlewareInterface
{

    protected int $here = 0;
    /**
     * @inheritDoc
     */
    #[\Override]
    public function afterProcess(HttpResponse $response, HttpRequest $request, string $class, string $method, ?string $exception): MiddlewareResult
    {
        $this->here++;
        return MiddlewareResult::continue;
    }

    public function getHere(): int
    {
        return $this->here;
    }
}