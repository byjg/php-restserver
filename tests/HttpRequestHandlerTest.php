<?php

namespace Tests;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpRequestHandler;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\Middleware\AfterMiddlewareInterface;
use ByJG\RestServer\Middleware\BeforeMiddlewareInterface;
use ByJG\RestServer\Middleware\MiddlewareResult;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\Writer\MemoryWriter;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use stdClass;

class HttpRequestHandlerTest extends TestCase
{
    private HttpRequestHandler $handler;

    /**
     * Set up the test environment
     */
    #[Override]
    protected function setUp(): void
    {
        $this->handler = new HttpRequestHandler();
    }

    /**
     * Test that withErrorHandlerDisabled() returns the instance for chaining
     */
    public function testWithErrorHandlerDisabled(): void
    {
        $result = $this->handler->withErrorHandlerDisabled();

        $this->assertInstanceOf(HttpRequestHandler::class, $result);
        $this->assertSame($this->handler, $result);

        // Test that useErrorHandler is set to false
        $reflectionProperty = new ReflectionProperty(HttpRequestHandler::class, 'useErrorHandler');
        $reflectionProperty->setAccessible(true);
        $this->assertFalse($reflectionProperty->getValue($this->handler));
    }

    /**
     * Test that withDetailedErrorHandler() returns the instance for chaining
     */
    public function testWithDetailedErrorHandler(): void
    {
        $result = $this->handler->withDetailedErrorHandler();

        $this->assertInstanceOf(HttpRequestHandler::class, $result);
        $this->assertSame($this->handler, $result);

        // Test that detailedErrorHandler is set to true
        $reflectionProperty = new ReflectionProperty(HttpRequestHandler::class, 'detailedErrorHandler');
        $reflectionProperty->setAccessible(true);
        $this->assertTrue($reflectionProperty->getValue($this->handler));
    }

    /**
     * Test that withDefaultOutputProcessor() with a valid processor class returns the instance for chaining
     */
    public function testWithDefaultOutputProcessorValidClass(): void
    {
        $result = $this->handler->withDefaultOutputProcessor(JsonOutputProcessor::class);

        $this->assertInstanceOf(HttpRequestHandler::class, $result);
        $this->assertSame($this->handler, $result);

        // Test that defaultOutputProcessor is set correctly
        $reflectionProperty = new ReflectionProperty(HttpRequestHandler::class, 'defaultOutputProcessor');
        $reflectionProperty->setAccessible(true);
        $this->assertEquals(JsonOutputProcessor::class, $reflectionProperty->getValue($this->handler));
    }

    /**
     * Test that withDefaultOutputProcessor() with an invalid processor class throws an exception
     */
    public function testWithDefaultOutputProcessorInvalidClass(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // \stdClass is not a subclass of BaseOutputProcessor
        $this->handler->withDefaultOutputProcessor(stdClass::class);
    }

    /**
     * Test that withDefaultOutputProcessor() with a closure returns the instance for chaining
     */
    public function testWithDefaultOutputProcessorClosure(): void
    {
        $closure = function () {
            return new JsonOutputProcessor();
        };

        $result = $this->handler->withDefaultOutputProcessor($closure);

        $this->assertInstanceOf(HttpRequestHandler::class, $result);
        $this->assertSame($this->handler, $result);

        // Test that defaultOutputProcessor is set to the closure
        $reflectionProperty = new ReflectionProperty(HttpRequestHandler::class, 'defaultOutputProcessor');
        $reflectionProperty->setAccessible(true);
        $this->assertSame($closure, $reflectionProperty->getValue($this->handler));
    }

    /**
     * Test that withWriter() returns the instance for chaining
     */
    public function testWithWriter(): void
    {
        $writer = new MemoryWriter();
        $result = $this->handler->withWriter($writer);

        $this->assertInstanceOf(HttpRequestHandler::class, $result);
        $this->assertSame($this->handler, $result);

        // Test that writer is set correctly
        $reflectionProperty = new ReflectionProperty(HttpRequestHandler::class, 'writer');
        $reflectionProperty->setAccessible(true);
        $this->assertSame($writer, $reflectionProperty->getValue($this->handler));
    }

    /**
     * Test that withMiddleware() adds BeforeMiddleware to the beforeMiddlewareList
     */
    public function testWithBeforeMiddleware(): void
    {
        // Create a mock BeforeMiddleware
        $middleware = $this->createMock(BeforeMiddlewareInterface::class);

        $result = $this->handler->withMiddleware($middleware, '/test');

        $this->assertInstanceOf(HttpRequestHandler::class, $result);
        $this->assertSame($this->handler, $result);

        // Test that middleware is added to beforeMiddlewareList
        $reflectionProperty = new ReflectionProperty(HttpRequestHandler::class, 'beforeMiddlewareList');
        $reflectionProperty->setAccessible(true);
        $beforeList = $reflectionProperty->getValue($this->handler);

        $this->assertCount(1, $beforeList);

        // Use foreach to safely access the array
        $found = false;
        foreach ($beforeList as $item) {
            $this->assertIsArray($item);
            $this->assertArrayHasKey('middleware', $item);
            $this->assertArrayHasKey('routePattern', $item);

            if ($item['middleware'] === $middleware && $item['routePattern'] === '/test') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'The middleware was not found in the beforeMiddlewareList');
    }

    /**
     * Test that withMiddleware() adds AfterMiddleware to the afterMiddlewareList
     */
    public function testWithAfterMiddleware(): void
    {
        // Create a mock AfterMiddleware
        $middleware = $this->createMock(AfterMiddlewareInterface::class);

        $result = $this->handler->withMiddleware($middleware, '/test');

        $this->assertInstanceOf(HttpRequestHandler::class, $result);
        $this->assertSame($this->handler, $result);

        // Test that middleware is added to afterMiddlewareList
        $reflectionProperty = new ReflectionProperty(HttpRequestHandler::class, 'afterMiddlewareList');
        $reflectionProperty->setAccessible(true);
        $afterList = $reflectionProperty->getValue($this->handler);

        $this->assertCount(1, $afterList);

        // Use foreach to safely access the array
        $found = false;
        foreach ($afterList as $item) {
            $this->assertIsArray($item);
            $this->assertArrayHasKey('middleware', $item);
            $this->assertArrayHasKey('routePattern', $item);

            if ($item['middleware'] === $middleware && $item['routePattern'] === '/test') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'The middleware was not found in the afterMiddlewareList');
    }

    /**
     * Test that withMiddleware() adds a middleware implementing both interfaces to both lists
     */
    public function testWithBothTypeMiddleware(): void
    {
        // Create a middleware implementing both interfaces
        $middleware = new class implements BeforeMiddlewareInterface, AfterMiddlewareInterface {
            #[Override]
            public function beforeProcess(mixed $dispatcherStatus, HttpResponse $response, HttpRequest $request): MiddlewareResult
            {
                return MiddlewareResult::continue;
            }

            #[Override]
            public function afterProcess(HttpResponse $response, HttpRequest $request, string $class, string $method, ?string $exception): MiddlewareResult
            {
                return MiddlewareResult::continue;
            }
        };

        $result = $this->handler->withMiddleware($middleware);

        // Test that middleware is added to both lists
        $reflectionBefore = new ReflectionProperty(HttpRequestHandler::class, 'beforeMiddlewareList');
        $reflectionBefore->setAccessible(true);
        $beforeList = $reflectionBefore->getValue($this->handler);

        $reflectionAfter = new ReflectionProperty(HttpRequestHandler::class, 'afterMiddlewareList');
        $reflectionAfter->setAccessible(true);
        $afterList = $reflectionAfter->getValue($this->handler);

        $this->assertCount(1, $beforeList);
        $this->assertCount(1, $afterList);

        // Check beforeList
        $foundBefore = false;
        foreach ($beforeList as $item) {
            $this->assertIsArray($item);
            $this->assertArrayHasKey('middleware', $item);

            if ($item['middleware'] === $middleware) {
                $foundBefore = true;
                break;
            }
        }
        $this->assertTrue($foundBefore, 'The middleware was not found in the beforeMiddlewareList');

        // Check afterList
        $foundAfter = false;
        foreach ($afterList as $item) {
            $this->assertIsArray($item);
            $this->assertArrayHasKey('middleware', $item);

            if ($item['middleware'] === $middleware) {
                $foundAfter = true;
                break;
            }
        }
        $this->assertTrue($foundAfter, 'The middleware was not found in the afterMiddlewareList');
    }
} 