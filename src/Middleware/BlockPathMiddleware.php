<?php

namespace ByJG\RestServer\Middleware;

use ByJG\RestServer\Exception\Error403Exception;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use Override;

class BlockPathMiddleware implements BeforeMiddlewareInterface
{
    protected array $startWithPaths = [];
    protected array $regExPatterns = [];

    public function withBlockedStartWith(array $paths): static
    {
        $this->startWithPaths = array_merge($this->startWithPaths, $paths);
        return $this;
    }

    public function withBlockedRegEx(array $patterns): static
    {
        $this->regExPatterns = array_merge($this->regExPatterns, $patterns);
        return $this;
    }

    /**
     * @throws Error403Exception
     */
    #[Override]
    public function beforeProcess(
        mixed        $dispatcherStatus,
        HttpResponse $response,
        HttpRequest  $request
    ): MiddlewareResult
    {
        $requestPath = $request->getRequestPath() ?? '/';

        foreach ($this->startWithPaths as $prefix) {
            if (str_starts_with($requestPath, $prefix)) {
                throw new Error403Exception("Access denied");
            }
        }

        foreach ($this->regExPatterns as $pattern) {
            if (preg_match($pattern, $requestPath)) {
                throw new Error403Exception("Access denied");
            }
        }

        return MiddlewareResult::continue;
    }
}