---
sidebar_position: 22
sidebar_label: PSR-7 Adapters
---

# PSR-7 Adapters

RestServer provides bidirectional adapters to convert between its native `HttpRequest`/`HttpResponse` objects and PSR-7
compliant interfaces. This enables seamless interoperability with PSR-7 middleware, frameworks, and libraries.

## Overview

The PSR-7 adapters allow you to:

- Convert RestServer's `HttpRequest` to PSR-7 `ServerRequestInterface`
- Convert RestServer's `HttpResponse` to PSR-7 `ResponseInterface`
- Convert PSR-7 `ResponseInterface` back to RestServer's `HttpResponse`
- Use RestServer with any PSR-7-compliant middleware or framework

## Converting HttpRequest to PSR-7

Use `Psr7RequestAdapter::fromHttpRequest()` to convert RestServer's HttpRequest to a PSR-7 ServerRequest:

```php
<?php
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\Psr7\Psr7RequestAdapter;

// Create HttpRequest (typically created by RestServer automatically)
$httpRequest = new HttpRequest($_GET, $_POST, $_SERVER, $_SESSION ?? [], $_COOKIE);

// Convert to PSR-7 ServerRequest
$psr7Request = Psr7RequestAdapter::fromHttpRequest($httpRequest);

// Now you can use $psr7Request with any PSR-7 middleware
```

### What Gets Converted

The adapter converts all relevant data from HttpRequest to PSR-7:

| HttpRequest Data  | PSR-7 Mapping                               |
|-------------------|---------------------------------------------|
| HTTP method       | `$request->getMethod()`                     |
| URI components    | `$request->getUri()`                        |
| Headers           | `$request->getHeaders()`                    |
| Query parameters  | `$request->getQueryParams()`                |
| POST data         | `$request->getParsedBody()`                 |
| Payload           | `$request->getBody()`                       |
| Route parameters  | `$request->getAttribute($name)`             |
| Route metadata    | `$request->getAttribute('_route_metadata')` |
| Cookies           | `$request->getCookieParams()`               |
| Server parameters | `$request->getServerParams()`               |

## Converting HttpResponse to PSR-7

Use `Psr7ResponseAdapter::fromHttpResponse()` to convert RestServer's HttpResponse to a PSR-7 Response:

```php
<?php
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\Psr7\Psr7ResponseAdapter;

// Create and populate HttpResponse
$httpResponse = new HttpResponse();
$httpResponse->write(['status' => 'success', 'data' => ['id' => 123]]);
$httpResponse->setResponseCode(200);
$httpResponse->addHeader('X-Custom-Header', 'value');

// Convert to PSR-7 Response
$psr7Response = Psr7ResponseAdapter::fromHttpResponse($httpResponse);

// Optionally specify default content type (default is 'application/json')
$psr7Response = Psr7ResponseAdapter::fromHttpResponse($httpResponse, 'application/xml');
```

### Response Body Conversion

The adapter automatically converts the ResponseBag content to a string:

- **JSON content**: Encodes data with `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`
- **Single item arrays**: Flattened to the item itself (e.g., `[{...}]` becomes `{...}`)
- **Other content types**: Converts to string representation

## Converting PSR-7 Response Back to HttpResponse

Use `Psr7ResponseAdapter::toHttpResponse()` to convert a PSR-7 Response back to HttpResponse:

```php
<?php
use ByJG\RestServer\Psr7\Psr7ResponseAdapter;
use Psr\Http\Message\ResponseInterface;

// Assume $psr7Response is returned from PSR-7 middleware
/** @var ResponseInterface $psr7Response */

// Convert to HttpResponse
$httpResponse = Psr7ResponseAdapter::toHttpResponse($psr7Response);

// Or update an existing HttpResponse
$existingResponse = new HttpResponse();
$httpResponse = Psr7ResponseAdapter::toHttpResponse($psr7Response, $existingResponse);
```

### JSON Auto-Detection

When converting back, the adapter automatically detects JSON content-type and decodes the body:

- If `Content-Type` is `application/json`, the body is decoded and passed to `$httpResponse->write()`
- For other content types, the raw body string is written

## Using with PSR-7 Middleware

Here's a complete example using RestServer with PSR-7 middleware:

```php
<?php
use ByJG\RestServer\HttpRequestHandler;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\Psr7\Psr7RequestAdapter;
use ByJG\RestServer\Psr7\Psr7ResponseAdapter;
use ByJG\RestServer\Route\RouteList;
use ByJG\RestServer\Route\Route;

// Your PSR-7 middleware (example)
class LoggingMiddleware
{
    public function process($request, $handler)
    {
        error_log("Request: " . $request->getUri());
        $response = $handler->handle($request);
        error_log("Response: " . $response->getStatusCode());
        return $response;
    }
}

// Define routes
$routeList = new RouteList();
$routeList->addRoute(
    Route::get('/api/user/{id}')
        ->withClosure(function (HttpResponse $response, HttpRequest $request) {
            // Your route logic
            $userId = $request->param('id');
            $response->write(['user_id' => $userId, 'name' => 'John Doe']);
        })
);

// Create request handler with PSR-7 middleware integration
$handler = new HttpRequestHandler();
$handler->withMiddleware(new class extends \ByJG\RestServer\Middleware\BeforeMiddlewareInterface {
    public function beforeProcess($dispatcherStatus, $response, $request)
    {
        // Convert to PSR-7
        $psr7Request = Psr7RequestAdapter::fromHttpRequest($request);

        // Process with PSR-7 middleware
        $middleware = new LoggingMiddleware();
        // ... apply middleware logic

        return \ByJG\RestServer\Middleware\MiddlewareResult::continue;
    }
});

$handler->handle($routeList);
```

## Integration with PSR-7 Frameworks

### Example: Using with Slim Framework Middleware

```php
<?php
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\Psr7\Psr7RequestAdapter;
use Slim\Middleware\ContentLengthMiddleware;

// Create RestServer request
$httpRequest = new HttpRequest($_GET, $_POST, $_SERVER, $_SESSION ?? [], $_COOKIE);

// Convert to PSR-7
$psr7Request = Psr7RequestAdapter::fromHttpRequest($httpRequest);

// Use Slim middleware
$contentLengthMiddleware = new ContentLengthMiddleware();
// Process with middleware...
```

## Important Notes

### Immutability

PSR-7 responses are immutable. When converting from PSR-7 back to HttpResponse, a new HttpResponse object is created (or
the provided one is modified).

### Session and Cookie Handling

- **Sessions**: Managed separately by PHP/RestServer, not directly in PSR-7 interfaces
- **Cookies**: In PSR-7, cookies are set via `Set-Cookie` headers, not through dedicated cookie methods

### Route Parameters

Route parameters from RestServer are stored as PSR-7 request attributes and can be accessed via:

```php
$userId = $psr7Request->getAttribute('id'); // Route parameter {id}
$metadata = $psr7Request->getAttribute('_route_metadata'); // Route metadata
```

### Content-Type Default

When converting HttpResponse to PSR-7, if no `Content-Type` header is set, it defaults to `application/json`. You can
override this:

```php
$psr7Response = Psr7ResponseAdapter::fromHttpResponse($httpResponse, 'text/html');
```

## Common Use Cases

### Use Case 1: Integrate Third-Party PSR-7 Middleware

```php
<?php
// Use any PSR-7 middleware with RestServer
$httpRequest = new HttpRequest($_GET, $_POST, $_SERVER, $_SESSION ?? [], $_COOKIE);
$psr7Request = Psr7RequestAdapter::fromHttpRequest($httpRequest);

// Apply PSR-7 middleware chain
$psr7Response = $thirdPartyMiddleware->process($psr7Request);

// Convert back to RestServer
$httpResponse = Psr7ResponseAdapter::toHttpResponse($psr7Response);
```

### Use Case 2: Test with PSR-7 Test Tools

```php
<?php
use PHPUnit\Framework\TestCase;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\Psr7\Psr7RequestAdapter;

class ApiTest extends TestCase
{
    public function testApiEndpoint()
    {
        $httpRequest = new HttpRequest(['id' => '123'], [], ['REQUEST_METHOD' => 'GET'], [], []);
        $psr7Request = Psr7RequestAdapter::fromHttpRequest($httpRequest);

        // Use PSR-7 testing tools
        $this->assertEquals('GET', $psr7Request->getMethod());
        $this->assertEquals('123', $psr7Request->getQueryParams()['id']);
    }
}
```

### Use Case 3: Bridge to Other PSR-7 Applications

```php
<?php
// Forward RestServer request to another PSR-7 application
$httpRequest = new HttpRequest($_GET, $_POST, $_SERVER, $_SESSION ?? [], $_COOKIE);
$psr7Request = Psr7RequestAdapter::fromHttpRequest($httpRequest);

// Send to another PSR-7 app
$psr7Response = $otherPsr7App->handle($psr7Request);

// Convert response back
$httpResponse = Psr7ResponseAdapter::toHttpResponse($psr7Response);
```

## API Reference

### Psr7RequestAdapter

#### `fromHttpRequest(HttpRequest $request): ServerRequestInterface`

Converts a RestServer HttpRequest to a PSR-7 ServerRequestInterface.

**Parameters:**

- `$request` - The HttpRequest to convert

**Returns:** PSR-7 ServerRequestInterface

**Throws:**

- `MessageException` - If URI building fails
- `RequestException` - If request construction fails

---

### Psr7ResponseAdapter

#### `fromHttpResponse(HttpResponse $response, string $contentType = 'application/json'): ResponseInterface`

Converts a RestServer HttpResponse to a PSR-7 ResponseInterface.

**Parameters:**

- `$response` - The HttpResponse to convert
- `$contentType` - Default content type if not set in headers (default: `'application/json'`)

**Returns:** PSR-7 ResponseInterface

**Throws:**

- `MessageException` - If response construction fails

#### `toHttpResponse(ResponseInterface $psr7Response, ?HttpResponse $httpResponse = null): HttpResponse`

Converts a PSR-7 ResponseInterface back to a RestServer HttpResponse.

**Parameters:**

- `$psr7Response` - The PSR-7 response to convert
- `$httpResponse` - Optional existing HttpResponse to update (creates new one if null)

**Returns:** HttpResponse

## See Also

- [PSR-7: HTTP Message Interface](https://www.php-fig.org/psr/psr-7/)
- [HttpRequest and HttpResponse](httprequest-httpresponse.md)
- [Middleware](middleware.md)
- [Mock Testing](mock-testing.md)
