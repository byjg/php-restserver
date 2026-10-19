<?php

namespace Tests;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\Middleware\AfterMiddlewareInterface;
use ByJG\RestServer\Middleware\MiddlewareResult;
use Exception;
use Override;

class DummyAfterMiddleware implements AfterMiddlewareInterface
{

    protected int $here = 0;
    /**
     * @inheritDoc
     */
    #[Override]
    public function afterProcess(HttpResponse $response, HttpRequest $request, string $class, string $method, ?Exception $exception): MiddlewareResult
    {
        $this->here++;
        return MiddlewareResult::Continue;
    }

    public function getHere(): int
    {
        return $this->here;
    }
}