---
sidebar_position: 3
sidebar_label: Routes using Closures
---
# Creating Routes Using Closures

> **Note:** For complete setup instructions including HttpRequestHandler configuration, see [Setup](setup.md).

Closures provide a quick and simple way to define routes inline without creating separate classes. They are ideal for
prototyping, testing, or simple endpoints that don't require complex logic.

## Basic Example

```php
<?php
use ByJG\RestServer\Route\RouteList;
use ByJG\RestServer\Route\Route;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;

$routeDefinition = new RouteList();
$routeDefinition->addRoute(
    Route::get("/api/test")
        ->withOutputProcessor(JsonOutputProcessor::class)
        ->withClosure(function (HttpResponse $response, HttpRequest $request) {
            $response->write(['message' => 'Success']);
        })
);
```

## When to Use Closures

**Best for:**

- Simple routes with minimal logic
- Prototyping and quick testing
- Routes that don't need to be reused elsewhere

**Consider alternatives for:**

- Complex business logic → Use [Routes Manually](routes-manually.md)
- Reusable endpoints → Use [PHP Attributes](routes-using-php-attributes.md)
- Large applications → Use class-based approaches

## Route Parameters

Access route parameters in closures:

```php
<?php
$routeDefinition->addRoute(
    Route::get("/api/user/{id}")
        ->withClosure(function (HttpResponse $response, HttpRequest $request) {
            $userId = $request->param('id');
            $response->write(['user_id' => $userId]);
        })
);
```

## Additional Configuration

For information on:

- Output processors: See [Output Processors](outprocessor.md)
- Route metadata: See [Route Metadata](route-metadata.md)
- Complete setup: See [Setup](setup.md)
