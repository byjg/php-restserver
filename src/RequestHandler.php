<?php


namespace ByJG\RestServer;

use ByJG\RestServer\Middleware\AfterMiddlewareInterface;
use ByJG\RestServer\Middleware\BeforeMiddlewareInterface;
use ByJG\RestServer\Route\RouteListInterface;

interface RequestHandler
{
    public function handle(RouteListInterface $routeDefinition, bool $outputBuffer = true, bool $session = true);

    public function withErrorHandlerDisabled();

    public function withDetailedErrorHandler();

    public function withMiddleware(AfterMiddlewareInterface|BeforeMiddlewareInterface $middleware, string $routePattern = null): static;

    public function withDefaultOutputProcessor(string|\Closure $processor, array $args = []): static;
}