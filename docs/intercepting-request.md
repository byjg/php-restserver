---
sidebar_position: 14
sidebar_label: Intercepting Request
---
# Intercepting Request

> **Note:** For comprehensive examples and use cases, see
> the [Route Interceptors section](routes-using-php-attributes.md#route-interceptors) in the PHP Attributes documentation.

Request intercepting allows you to execute code before or after a route handler using PHP 8 attributes. This is useful
for route-specific validation, logging, transformation, or other cross-cutting concerns at the individual route level.

## Interfaces

Interceptors are PHP 8 attributes that implement one of two interfaces:

### BeforeRouteInterface

Executes before the route handler:

```php
<?php
namespace My;

use Attribute;
use ByJG\RestServer\Attributes\BeforeRouteInterface;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

#[Attribute(Attribute::TARGET_METHOD)]
class MyBeforeProcess implements BeforeRouteInterface
{
    public function processBefore(HttpResponse $response, HttpRequest $request)
    {
        // Validation, authentication, logging, etc.
    }
}
```

### AfterRouteInterface

Executes after the route handler:

```php
<?php
namespace My;

use Attribute;
use ByJG\RestServer\Attributes\AfterRouteInterface;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

#[Attribute(Attribute::TARGET_METHOD)]
class MyAfterProcess implements AfterRouteInterface
{
    public function processAfter(HttpResponse $response, HttpRequest $request)
    {
        // Response transformation, logging, cleanup, etc.
    }
}
```

## Usage

Apply interceptor attributes to route methods:

```php
<?php
namespace My;

use ByJG\RestServer\Attributes\RouteDefinition;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

class ClassName
{
    #[RouteDefinition('GET', '/route1')]
    #[MyBeforeProcess()]
    public function someMethod(HttpResponse $response, HttpRequest $request)
    {
        $response->write(['result' => 'ok']);
    }
}
```

For complete examples including:

- Built-in interceptors (RequireAuthenticated, RequireRole)
- Multiple interceptors on a single route
- Interceptors with parameters
- Real-world use cases

See the [Route Interceptors section](routes-using-php-attributes.md#route-interceptors) in the PHP Attributes
documentation.

## Combining Middleware and Request Intercepting

Middleware operates at the HTTP server level, affecting all routes or specific route patterns, while request
intercepting operates at the route level using PHP attributes. Both can be used together to provide a comprehensive
request handling pipeline.

- Use Middleware for server-wide or pattern-based functionality (authentication, CORS, static files)
- Use Request Intercepting for route-specific functionality
