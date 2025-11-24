---
sidebar_position: 17
sidebar_label: Mock Testing
---

# Mock Testing

RestServer provides robust mock testing capabilities that allow you to test your API endpoints without making actual
HTTP requests.

## Mock Request Handler

The `MockRequestHandler` class provides a way to simulate HTTP requests to your API and get back the response that would
be generated.

### Basic Example

```php
<?php
use ByJG\RestServer\MockRequestHandler;
use ByJG\RestServer\Route\RouteList;
use Nyholm\Psr7\Request;

// Define your routes
$routeDefinition = new RouteList();
$routeDefinition->addClass(MyApiController::class);

// Create a mock PSR-7 request
$request = new Request(
    'GET',
    '/api/products/123',
    ['Accept' => 'application/json']
);

// Create a mock handler
$mockHandler = MockRequestHandler::mock($routeDefinition, $request);

// Get the response
$statusCode = $mockHandler->getPsr7Response()->getStatusCode();
$headers = $mockHandler->getPsr7Response()->getHeaders();
$body = $mockHandler->getPsr7Response()->getBody()->getContents();

// Now you can make assertions on the response
```

## Mock HTTP Request

The `MockHttpRequest` class extends `HttpRequest` to allow testing with simulated parameters:

```php
<?php
use ByJG\RestServer\MockHttpRequest;
use Nyholm\Psr7\Request;

// Create a PSR-7 request
$psr7Request = new Request('POST', '/api/users');

// Create a mock request with simulated parameters
$mockHttpRequest = new MockHttpRequest($psr7Request, [
    "id" => 123,
    "name" => "John Doe",
    "email" => "john@example.com"
]);

// Use the mock request for testing
$id = $mockHttpRequest->param("id"); // Returns 123
```

## Mock Response

The `MockResponse` class helps capture and examine responses:

```php
<?php
use ByJG\RestServer\MockResponse;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;

// In test scenarios, you can handle exceptions with mock responses
try {
    // Code that might throw an exception
    doSomethingThatThrows();
} catch (Exception $ex) {
    // Create a mock error response
    $result = MockResponse::errorHandlerFromEndpoint(
        $ex,
        new JsonOutputProcessor(),
        $routeDefinition,
        'GET',
        '/api/resource'
    );
    
    // Examine the raw JSON returned by the error handler
    $this->assertJsonStringEqualsJsonString('{"error": "Not Found"}', $result);
}
```

## Building reusable test harnesses

Larger test suites benefit from a reusable helper that hides the boilerplate of instantiating `MockRequestHandler` and
creating PSR-7 requests. The following trait shows one way to structure that helper:

```php
<?php
namespace Tests\Support;

use ByJG\RestServer\MockRequestHandler;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\Route\Route;
use ByJG\RestServer\Route\RouteList;
use Nyholm\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

trait ApiTestTrait
{
    protected RouteList $routes;

    protected function bootRoutes(): void
    {
        $this->routes = new RouteList();
        $this->routes->addRoute(
            Route::get('/api/resource')
                ->withClosure(function ($response, $request) {
                    $response->write(['status' => 'success']);
                })
        );
    }

    protected function request(string $method, string $path, array $headers = [], ?string $body = null): ResponseInterface
    {
        $psr7Request = new Request($method, $path, $headers, $body);
        $handler = new MockRequestHandler();
        $handler
            ->withDefaultOutputProcessor(JsonOutputProcessor::class)
            ->withRequestObject($psr7Request)
            ->handle($this->routes);
        return $handler->getPsr7Response();
    }
}
```

```php
<?php
use PHPUnit\Framework\TestCase;
use Tests\Support\ApiTestTrait;

class MyApiTest extends TestCase
{
    use ApiTestTrait;

    protected function setUp(): void
    {
        $this->bootRoutes();
    }

    public function testGetEndpoint(): void
    {
        $response = $this->request('GET', '/api/resource');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"status":"success"}', (string) $response->getBody());
    }
}
```

:::note Reference implementation
The repository’s own test suite includes `tests/MockServerTrait.php`, demonstrating a more elaborate setup with
pre-registered routes, middlewares, and error handling hooks. Feel free to adapt that trait inside your project if you
need heavier integration fixtures.
:::

## Benefits of Mock Testing

- Test API endpoints without running a web server
- Fast execution of tests without network overhead
- Simulate various request scenarios (parameters, headers, body)
- Test error handling and exception cases
- Fully integrated with PHPUnit and other testing frameworks

By using these mock testing capabilities, you can ensure your API works correctly before deploying it to production. 
