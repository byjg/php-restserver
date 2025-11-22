<?php

namespace Tests\Attributes;

use ByJG\RestServer\Attributes\RequireRole;
use ByJG\RestServer\Exception\Error401Exception;
use ByJG\RestServer\Exception\Error403Exception;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpRequestHandler;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\Middleware\BeforeMiddlewareInterface;
use ByJG\RestServer\Middleware\JwtMiddleware;
use ByJG\RestServer\Middleware\MiddlewareResult;
use ByJG\RestServer\Route\RouteList;
use Override;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\MockServerTrait;
use Tests\Routes\RouteWithAuth;

class RequireRoleTest extends TestCase
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

    public function testAdminRouteWithoutAuth(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/admin";
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

    public function testAdminRouteWithUserRole(): void
    {
        $expectedHeader = [
            "HTTP/1.1 403 Forbidden",
            "Content-Type: application/json",
        ];

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/admin";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        // Create middleware that simulates successful JWT with 'user' role
        $middleware = new class implements BeforeMiddlewareInterface {
            #[Override]
            public function beforeProcess(
                mixed                         $dispatcherStatus,
                HttpResponse $response,
                HttpRequest  $request
            ): MiddlewareResult
            {
                $request->appendVars([
                    JwtMiddleware::JWT_PARAM_PARSE_STATUS => JwtMiddleware::JWT_SUCCESS,
                    'role' => 'user'
                ]);
                return MiddlewareResult::continue;
            }
        };

        $this->object->withMiddleware($middleware);

        $this->expectException(Error403Exception::class);
        $this->expectExceptionMessage('Insufficient privileges');

        try {
            $this->object->handle($this->definition, true, false);
        } finally {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
    }

    public function testAdminRouteWithAdminRole(): void
    {
        $expectedHeader = [
            "HTTP/1.1 200 OK",
            "Content-Type: application/json",
        ];
        $expectedData = '{"message":"Admin access"}';

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/admin";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        // Create middleware that simulates successful JWT with 'admin' role
        $middleware = new class implements BeforeMiddlewareInterface {
            #[Override]
            public function beforeProcess(
                mixed                         $dispatcherStatus,
                HttpResponse $response,
                HttpRequest  $request
            ): MiddlewareResult
            {
                $request->appendVars([
                    JwtMiddleware::JWT_PARAM_PARSE_STATUS => JwtMiddleware::JWT_SUCCESS,
                    'role' => 'admin'
                ]);
                return MiddlewareResult::continue;
            }
        };

        $this->processAndGetContent($this->object, $expectedHeader, $expectedData, $middleware);
    }

    public function testUserRouteWithUserRole(): void
    {
        $expectedHeader = [
            "HTTP/1.1 200 OK",
            "Content-Type: application/json",
        ];
        $expectedData = '{"message":"User access"}';

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/user";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        // Create middleware that simulates successful JWT with 'user' role
        $middleware = new class implements BeforeMiddlewareInterface {
            #[Override]
            public function beforeProcess(
                mixed                         $dispatcherStatus,
                HttpResponse $response,
                HttpRequest  $request
            ): MiddlewareResult
            {
                $request->appendVars([
                    JwtMiddleware::JWT_PARAM_PARSE_STATUS => JwtMiddleware::JWT_SUCCESS,
                    'role' => 'user'
                ]);
                return MiddlewareResult::continue;
            }
        };

        $this->processAndGetContent($this->object, $expectedHeader, $expectedData, $middleware);
    }

    public function testUserRouteWithAdminRole(): void
    {
        $expectedHeader = [
            "HTTP/1.1 403 Forbidden",
            "Content-Type: application/json",
        ];

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/user";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        // Create middleware that simulates successful JWT with 'admin' role
        $middleware = new class implements BeforeMiddlewareInterface {
            #[Override]
            public function beforeProcess(
                mixed                         $dispatcherStatus,
                HttpResponse $response,
                HttpRequest  $request
            ): MiddlewareResult
            {
                $request->appendVars([
                    JwtMiddleware::JWT_PARAM_PARSE_STATUS => JwtMiddleware::JWT_SUCCESS,
                    'role' => 'admin'
                ]);
                return MiddlewareResult::continue;
            }
        };

        $this->object->withMiddleware($middleware);

        $this->expectException(Error403Exception::class);
        $this->expectExceptionMessage('Insufficient privileges');

        try {
            $this->object->handle($this->definition, true, false);
        } finally {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
    }

    public function testAdminActionWithCorrectRole(): void
    {
        $expectedHeader = [
            "HTTP/1.1 200 OK",
            "Content-Type: application/json",
        ];
        $expectedData = '{"message":"Admin action executed"}';

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = "http://localhost/admin/action";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        // Create middleware that simulates successful JWT with 'admin' role
        $middleware = new class implements BeforeMiddlewareInterface {
            #[Override]
            public function beforeProcess(
                mixed                         $dispatcherStatus,
                HttpResponse $response,
                HttpRequest  $request
            ): MiddlewareResult
            {
                $request->appendVars([
                    JwtMiddleware::JWT_PARAM_PARSE_STATUS => JwtMiddleware::JWT_SUCCESS,
                    'role' => 'admin'
                ]);
                return MiddlewareResult::continue;
            }
        };

        $this->processAndGetContent($this->object, $expectedHeader, $expectedData, $middleware);
    }

    public function testRequireRoleAttributeDirectlyWithoutAuth(): void
    {
        $attribute = new RequireRole('admin');
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

    public function testRequireRoleAttributeDirectlyWithWrongRole(): void
    {
        $attribute = new RequireRole('admin');
        $response = new HttpResponse();

        // Test with wrong role
        $request = new HttpRequest(
            [],
            [],
            ['REQUEST_METHOD' => 'GET'],
            [],
            [],
            [
                JwtMiddleware::JWT_PARAM_PARSE_STATUS => JwtMiddleware::JWT_SUCCESS,
                'role' => 'user'
            ]
        );

        $this->expectException(Error403Exception::class);
        $this->expectExceptionMessage('Insufficient privileges');
        $attribute->processBefore($response, $request);
    }

    public function testRequireRoleAttributeDirectlyWithCorrectRole(): void
    {
        $attribute = new RequireRole('admin');
        $response = new HttpResponse();

        // Test with correct role
        $request = new HttpRequest(
            [],
            [],
            ['REQUEST_METHOD' => 'GET'],
            [],
            [],
            [
                JwtMiddleware::JWT_PARAM_PARSE_STATUS => JwtMiddleware::JWT_SUCCESS,
                'role' => 'admin'
            ]
        );

        // Should not throw exception
        $attribute->processBefore($response, $request);
        $this->assertTrue(true); // If we get here, test passed
    }

    public function testRequireRoleAttributeWithNoRoleInToken(): void
    {
        $attribute = new RequireRole('admin');
        $response = new HttpResponse();

        // Test with JWT data but no role field
        $request = new HttpRequest(
            [],
            [],
            ['REQUEST_METHOD' => 'GET'],
            [],
            [],
            [
                JwtMiddleware::JWT_PARAM_PARSE_STATUS => JwtMiddleware::JWT_SUCCESS,
                'username' => 'testuser' // No 'role' field
            ]
        );

        $this->expectException(Error403Exception::class);
        $this->expectExceptionMessage('Insufficient privileges');
        $attribute->processBefore($response, $request);
    }

    public function testRequireRoleAttributeConstructor(): void
    {
        $attribute = new RequireRole('superadmin');

        // Use reflection to check the role and roleParam were set correctly
        $reflection = new ReflectionClass($attribute);

        $roleProperty = $reflection->getProperty('role');
        $this->assertEquals('superadmin', $roleProperty->getValue($attribute));

        $roleParamProperty = $reflection->getProperty('roleParam');
        $this->assertEquals('role', $roleParamProperty->getValue($attribute));
    }

    public function testRequireRoleAttributeWithCustomRoleParam(): void
    {
        // Test with custom role parameter path (e.g., 'jwt.data.role')
        $attribute = new RequireRole('admin', 'jwt.data.role');
        $response = new HttpResponse();

        // Test with correct role in custom location
        $request = new HttpRequest(
            [],
            [],
            ['REQUEST_METHOD' => 'GET'],
            [],
            [],
            [
                JwtMiddleware::JWT_PARAM_PARSE_STATUS => JwtMiddleware::JWT_SUCCESS,
                'jwt.data.role' => 'admin'
            ]
        );

        // Should not throw exception
        $attribute->processBefore($response, $request);
        $this->assertTrue(true); // If we get here, test passed
    }

    public function testRequireRoleAttributeWithCustomRoleParamWrongRole(): void
    {
        // Test with custom role parameter path but wrong role
        $attribute = new RequireRole('admin', 'user.role');
        $response = new HttpResponse();

        $request = new HttpRequest(
            [],
            [],
            ['REQUEST_METHOD' => 'GET'],
            [],
            [],
            [
                JwtMiddleware::JWT_PARAM_PARSE_STATUS => JwtMiddleware::JWT_SUCCESS,
                'user.role' => 'user'
            ]
        );

        $this->expectException(Error403Exception::class);
        $this->expectExceptionMessage('Insufficient privileges');
        $attribute->processBefore($response, $request);
    }

    public function testRequireRoleAttributeWithArrayDataAndRoleKey(): void
    {
        // Test with roleKey parameter to extract role from array
        $attribute = new RequireRole('admin', 'jwt.data', 'role');
        $response = new HttpResponse();

        // Test with correct role in array
        $request = new HttpRequest(
            [],
            [],
            ['REQUEST_METHOD' => 'GET'],
            [],
            [],
            [
                JwtMiddleware::JWT_PARAM_PARSE_STATUS => JwtMiddleware::JWT_SUCCESS,
                'jwt.data' => [
                    'user_id' => 123,
                    'role' => 'admin',
                    'email' => 'admin@example.com'
                ]
            ]
        );

        // Should not throw exception
        $attribute->processBefore($response, $request);
        $this->assertTrue(true); // If we get here, test passed
    }

    public function testRequireRoleAttributeWithArrayDataAndRoleKeyWrongRole(): void
    {
        // Test with roleKey parameter but wrong role in array
        $attribute = new RequireRole('admin', 'jwt.data', 'role');
        $response = new HttpResponse();

        $request = new HttpRequest(
            [],
            [],
            ['REQUEST_METHOD' => 'GET'],
            [],
            [],
            [
                JwtMiddleware::JWT_PARAM_PARSE_STATUS => JwtMiddleware::JWT_SUCCESS,
                'jwt.data' => [
                    'user_id' => 456,
                    'role' => 'user',
                    'email' => 'user@example.com'
                ]
            ]
        );

        $this->expectException(Error403Exception::class);
        $this->expectExceptionMessage('Insufficient privileges');
        $attribute->processBefore($response, $request);
    }

    public function testRequireRoleAttributeWithArrayDataMissingRoleKey(): void
    {
        // Test with roleKey parameter but missing key in array
        $attribute = new RequireRole('admin', 'jwt.data', 'role');
        $response = new HttpResponse();

        $request = new HttpRequest(
            [],
            [],
            ['REQUEST_METHOD' => 'GET'],
            [],
            [],
            [
                JwtMiddleware::JWT_PARAM_PARSE_STATUS => JwtMiddleware::JWT_SUCCESS,
                'jwt.data' => [
                    'user_id' => 789,
                    'email' => 'test@example.com'
                    // No 'role' key
                ]
            ]
        );

        $this->expectException(Error403Exception::class);
        $this->expectExceptionMessage('Insufficient privileges');
        $attribute->processBefore($response, $request);
    }

    public function testAdminRoleWithArrayDataAndRoleKey(): void
    {
        // Test full request handling with array data and roleKey
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/array-role";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        // Add middleware to set JWT success and array data with role key
        $middleware = new class implements BeforeMiddlewareInterface {
            #[Override]
            public function beforeProcess(
                mixed        $dispatcherStatus,
                HttpResponse $response,
                HttpRequest  $request
            ): MiddlewareResult
            {
                $request->appendVars([
                    JwtMiddleware::JWT_PARAM_PARSE_STATUS => JwtMiddleware::JWT_SUCCESS,
                    'jwt.data' => [
                        'user_id' => 123,
                        'role' => 'admin',
                        'email' => 'admin@example.com'
                    ]
                ]);
                return MiddlewareResult::continue;
            }
        };

        $expectedHeader = [
            "HTTP/1.1 200 OK",
            "Content-Type: application/json",
        ];
        $expectedData = '{"message":"Admin access granted"}';

        $this->processAndGetContent($this->object, $expectedHeader, $expectedData, $middleware);
    }

    public function testAdminRoleWithArrayDataAndRoleKeyDenied(): void
    {
        // Test full request handling with array data but wrong role
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/array-role";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        // Add middleware with wrong role
        $middleware = new class implements BeforeMiddlewareInterface {
            #[Override]
            public function beforeProcess(
                mixed        $dispatcherStatus,
                HttpResponse $response,
                HttpRequest  $request
            ): MiddlewareResult
            {
                $request->appendVars([
                    JwtMiddleware::JWT_PARAM_PARSE_STATUS => JwtMiddleware::JWT_SUCCESS,
                    'jwt.data' => [
                        'user_id' => 456,
                        'role' => 'user',
                        'email' => 'user@example.com'
                    ]
                ]);
                return MiddlewareResult::continue;
            }
        };

        $this->object->withMiddleware($middleware);

        $this->expectException(Error403Exception::class);
        $this->expectExceptionMessage('Insufficient privileges');

        try {
            $this->object->handle($this->definition, true, false);
        } finally {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
    }

    public function testRequireRoleAttributeConstructorWithRoleKey(): void
    {
        $attribute = new RequireRole('superadmin', 'jwt.data', 'role');

        // Use reflection to check all properties were set correctly
        $reflection = new ReflectionClass($attribute);

        $roleProperty = $reflection->getProperty('role');
        $this->assertEquals('superadmin', $roleProperty->getValue($attribute));

        $roleParamProperty = $reflection->getProperty('roleParam');
        $this->assertEquals('jwt.data', $roleParamProperty->getValue($attribute));

        $roleKeyProperty = $reflection->getProperty('roleKey');
        $this->assertEquals('role', $roleKeyProperty->getValue($attribute));
    }
}
