---
sidebar_position: 11
sidebar_label: Intercepting Request
---
# Intercepting Request

It is possible add a PHP attribute to intercept the request before or after the route is executed.

## Create the class to intercept the request

### Intercepting Before Execute the Route

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
        // Do something before the route is executed
    }
}
```

### Intercepting After Execute the Route

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
        // Do something after the route is executed
    }
}
```

## Set the attributes in the class:

```php
<?php
namespace My;

use ByJG\RestServer\Attributes\RouteDefinition;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

class ClassName
{
    //...
    
    #[RouteDefinition('GET', '/route1')]
    #[MyBeforeProcess()]
    public function someMethod(HttpResponse $response, HttpRequest $request)
    {
        $response->write(['result' => 'ok']);
    }

    #[RouteDefinition('PUT', '/route2')]
    #[MyAfterProcess()]
    public function anotherMethod(HttpResponse $response, HttpRequest $request)
    {
        $response->write(['result' => 'ok']);
    }
    //...
}
```

## Combining Middleware and Request Intercepting

Middleware operates at the HTTP server level, affecting all routes or specific route patterns, while request
intercepting operates at the route level using PHP attributes. Both can be used together to provide a comprehensive
request handling pipeline.

- Use Middleware for server-wide or pattern-based functionality (authentication, CORS, static files)
- Use Request Intercepting for route-specific functionality
