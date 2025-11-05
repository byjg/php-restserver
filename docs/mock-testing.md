---
sidebar_position: 13
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
    
    // Examine the result
    $this->assertEquals('{"error": "Not Found"}', $result->getBody());
}
```

## Testing with MockServerTrait

RestServer provides the `MockServerTrait` that simplifies testing:

```php
<?php
use ByJG\RestServer\MockServerTrait;
use PHPUnit\Framework\TestCase;

class MyApiTest extends TestCase
{
    use MockServerTrait;
    
    public function setUp(): void
    {
        // Setup routes for testing
        $this->setupRoutes();
    }
    
    public function testGetEndpoint()
    {
        // Process a mock request
        $response = $this->process(
            'GET',
            '/api/resource',
            [],  // Query parameters
            [],  // Headers
            null  // Body
        );
        
        // Make assertions
        $this->assertEquals(200, $response->getResponseCode());
        $this->assertJsonStringEqualsJsonString(
            '{"status": "success"}',
            $response->getBody()
        );
    }
}
```

## Benefits of Mock Testing

- Test API endpoints without running a web server
- Fast execution of tests without network overhead
- Simulate various request scenarios (parameters, headers, body)
- Test error handling and exception cases
- Fully integrated with PHPUnit and other testing frameworks

By using these mock testing capabilities, you can ensure your API works correctly before deploying it to production. 