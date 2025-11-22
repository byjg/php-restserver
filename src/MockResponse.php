<?php

namespace ByJG\RestServer;

use ByJG\RestServer\OutputProcessor\BaseOutputProcessor;
use ByJG\RestServer\OutputProcessor\OutputProcessorInterface;
use ByJG\RestServer\Route\Route;
use ByJG\RestServer\Route\RouteListInterface;
use Psr\Http\Message\RequestInterface;
use Throwable;
use Whoops\Inspector\InspectorFactory;

class MockResponse
{
    public static function errorHandlerFromRequest(Throwable|string $exception, OutputProcessorInterface $defaultProcessor, RouteListInterface $routeList, ?RequestInterface $request = null): bool|string
    {
        if ($request === null) {
            return self::errorHandlerFromRoute($exception, $defaultProcessor, null);
        }
        return self::errorHandlerFromEndpoint($exception, $defaultProcessor, $routeList, $request->getMethod(), $request->getUri()->getPath());
    }

    public static function errorHandlerFromEndpoint(Throwable|string $exception, OutputProcessorInterface $defaultProcessor, RouteListInterface $routeList, string $method, string $path): bool|string
    {
        $route = $routeList->getRoute($method, $path);
        return self::errorHandlerFromRoute($exception, $defaultProcessor, $route);
    }

    public static function errorHandlerFromRoute(Throwable|string $exception, OutputProcessorInterface $defaultProcessor, ?Route $route): bool|string
    {
        $outputProcessor = BaseOutputProcessor::factory($route?->getOutputProcessor() ?? $defaultProcessor) ?? $defaultProcessor;
        $handler = $outputProcessor->getErrorHandler();

        if (is_string($exception)) {
            /** @var class-string<Throwable> $exceptionClass */
            $exceptionClass = $exception;
            $exception = new $exceptionClass();
        }

        ob_start();
        $inspectorFactory = new InspectorFactory();
        $handler->setException($exception);
        $handler->setInspector($inspectorFactory->create($exception));
        $handler->handle();
        $result = ob_get_contents();
        ob_end_clean();

        return $result;
    }
}