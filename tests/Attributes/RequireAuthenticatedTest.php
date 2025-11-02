<?php

namespace Tests\Attributes;

use ByJG\RestServer\Attributes\RequireAuthenticated;
use ByJG\RestServer\Exception\Error401Exception;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpRequestHandler;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\Middleware\BeforeMiddlewareInterface;
use ByJG\RestServer\Middleware\JwtMiddleware;
use ByJG\RestServer\Middleware\MiddlewareResult;
use ByJG\RestServer\Route\RouteList;
use Override;
use PHPUnit\Framework\TestCase;
use Tests\MockServerTrait;
use Tests\Routes\RouteWithAuth;

class RequireAuthenticatedTest extends TestCase
{
    use MockServerTrait;

    #[Override]
    public function setup(): void
    {
        ini_set('output_buffering', 4096);
        $this->object = new HttpRequestHandler();
        $this->reach = false;
        $this->definition = new RouteList();

        $this->definition->addClass(RouteWithAuth::class);
    }

    public function testPublicRouteAccessible(): void
    {
        $expectedHeader = [
            "HTTP/1.1 200 OK",
            "Content-Type: application/json",
        ];
        $expectedData = '{"message":"Public access"}';

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/public";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent($this->object, $expectedHeader, $expectedData);
    }

    public function testAuthenticatedRouteWithoutAuth(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/authenticated";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        // Don't set any JWT parameters, simulating no authentication
        $this->expectException(Error401Exception::class);
        $this->expectExceptionMessage('Authentication required');

        try {
            $this->object->handle($this->definition, true, false);
        } finally {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
    }

    public function testAuthenticatedRouteWithFailedAuth(): void
    {
        $expectedHeader = [
            "HTTP/1.1 401 Unauthorized",
            "Content-Type: application/json",
        ];

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/authenticated";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        // Create a mock middleware that simulates failed JWT parsing
        $middleware = new class implements BeforeMiddlewareInterface {
            public function beforeProcess(
                mixed                         $dispatcherStatus,
                HttpResponse $response,
                HttpRequest  $request
            ): MiddlewareResult
            {
                $request->appendVars([
                    JwtMiddleware::JWT_PARAM_PARSE_STATUS => 'failed',
                    JwtMiddleware::JWT_PARAM_PARSE_MESSAGE => 'Invalid token'
                ]);
                return MiddlewareResult::continue;
            }
        };

        $this->object->withMiddleware($middleware);

        $this->expectException(Error401Exception::class);
        $this->expectExceptionMessage('Invalid token');

        try {
            $this->object->handle($this->definition, true, false);
        } finally {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
    }

    public function testAuthenticatedRouteWithValidAuth(): void
    {
        $expectedHeader = [
            "HTTP/1.1 200 OK",
            "Content-Type: application/json",
        ];
        $expectedData = '{"message":"Authenticated access"}';

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/authenticated";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        // Create a mock middleware that simulates successful JWT parsing
        $middleware = new class implements BeforeMiddlewareInterface {
            public function beforeProcess(
                mixed                         $dispatcherStatus,
                HttpResponse $response,
                HttpRequest  $request
            ): MiddlewareResult
            {
                $request->appendVars([
                    JwtMiddleware::JWT_PARAM_PARSE_STATUS => JwtMiddleware::JWT_SUCCESS
                ]);
                return MiddlewareResult::continue;
            }
        };

        $this->processAndGetContent($this->object, $expectedHeader, $expectedData, $middleware);
    }

    public function testRequireAuthenticatedAttributeDirectly(): void
    {
        $attribute = new RequireAuthenticated();
        $response = new HttpResponse();

        // Test with no authentication
        $request = new HttpRequest(
            [],
            [],
            ['REQUEST_METHOD' => 'GET'],
            [],
            []
        );

        $this->expectException(Error401Exception::class);
        $attribute->processBefore($response, $request);
    }

    public function testRequireAuthenticatedAttributeWithSuccess(): void
    {
        $attribute = new RequireAuthenticated();
        $response = new HttpResponse();

        // Test with successful authentication
        $request = new HttpRequest(
            [JwtMiddleware::JWT_PARAM_PARSE_STATUS => JwtMiddleware::JWT_SUCCESS],
            [],
            ['REQUEST_METHOD' => 'GET'],
            [],
            [],
            [JwtMiddleware::JWT_PARAM_PARSE_STATUS => JwtMiddleware::JWT_SUCCESS]
        );

        // Should not throw exception
        $attribute->processBefore($response, $request);
        $this->assertTrue(true); // If we get here, test passed
    }

    public function testRequireAuthenticatedAttributeWithFailure(): void
    {
        $attribute = new RequireAuthenticated();
        $response = new HttpResponse();

        // Test with failed authentication
        $request = new HttpRequest(
            [],
            [],
            ['REQUEST_METHOD' => 'GET'],
            [],
            [],
            [
                JwtMiddleware::JWT_PARAM_PARSE_STATUS => 'failed',
                JwtMiddleware::JWT_PARAM_PARSE_MESSAGE => 'Token expired'
            ]
        );

        $this->expectException(Error401Exception::class);
        $this->expectExceptionMessage('Token expired');
        $attribute->processBefore($response, $request);
    }
}
