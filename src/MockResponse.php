<?php

namespace ByJG\RestServer;

use ByJG\RestServer\Exception\OperationIdInvalidException;
use ByJG\RestServer\OutputProcessor\BaseOutputProcessor;
use ByJG\RestServer\OutputProcessor\OutputProcessorInterface;
use ByJG\RestServer\Route\Route;
use ByJG\RestServer\Route\RouteListInterface;
use ByJG\RestServer\Writer\MemoryWriter;
use Psr\Http\Message\RequestInterface;
use Throwable;

class MockResponse
{
    /**
     * @throws OperationIdInvalidException
     */
    public static function errorHandlerFromRequest(Throwable|string $exception, OutputProcessorInterface $defaultProcessor, RouteListInterface $routeList, ?RequestInterface $request = null): bool|string
    {
        if ($request === null) {
            return self::errorHandlerFromRoute($exception, $defaultProcessor, null);
        }
        return self::errorHandlerFromEndpoint($exception, $defaultProcessor, $routeList, $request->getMethod(), $request->getUri()->getPath());
    }

    /**
     * @throws OperationIdInvalidException
     */
    public static function errorHandlerFromEndpoint(Throwable|string $exception, OutputProcessorInterface $defaultProcessor, RouteListInterface $routeList, string $method, string $path): bool|string
    {
        $route = $routeList->getRoute($method, $path);
        return self::errorHandlerFromRoute($exception, $defaultProcessor, $route);
    }

    /**
     * @throws OperationIdInvalidException
     */
    public static function errorHandlerFromRoute(Throwable|string $exception, OutputProcessorInterface $defaultProcessor, ?Route $route): bool|string
    {
        $outputProcessor = BaseOutputProcessor::factory($route?->getOutputProcessor() ?? $defaultProcessor) ?? $defaultProcessor;

        if (is_string($exception)) {
            /** @var class-string<Throwable> $exceptionClass */
            $exceptionClass = $exception;
            $exception = new $exceptionClass();
        }

        // Create mock request and response
        $request = new HttpRequest([], [], [], [], []);
        $response = new HttpResponse();

        // Set up memory writer to capture output
        $writer = new MemoryWriter();
        $outputProcessor->setWriter($writer);

        // Handle the exception (detailed=false for production-like behavior)
        $outputProcessor->handle($exception, $response, $request, false);

        // Get output from writer (consistent with normal response handling)
        return $writer->getData();
    }
}