---
sidebar_position: 20
sidebar_label: Route Metadata
---

# Route Metadata

RestServer supports attaching metadata to routes, which provides a powerful way to add additional information and
behavior to your API endpoints.

## Basic Usage

You can attach metadata to a route with the `Route::withMetadata()` method:

```php
<?php
use ByJG\RestServer\Route\Route;
use ByJG\RestServer\Route\RouteList;

$routeList = new RouteList();

// Add route with metadata
$routeList->addRoute(
    Route::get('/api/resource')
        ->withClass(\MyNamespace\MyClass::class, 'method')
        ->withMetadata([
            'description' => 'Get a specific resource',
            'roles' => ['admin', 'user'],
            'cache_ttl' => 3600,
            'rate_limit' => 100
        ])
);
```

`withMetadata()` replaces any metadata set before on the same route.

## Accessing Metadata in Controllers

You can access the route metadata in your controller methods:

```php
<?php
namespace MyNamespace;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

class MyClass
{
    public function method(HttpResponse $response, HttpRequest $request)
    {
        // Get all metadata
        $allMetadata = $request->getRouteMetadata();
        
        // Get specific metadata field
        $roles = $request->getRouteMetadata('roles');
        
        // Use metadata to customize behavior
        if (in_array('admin', $roles)) {
            // Include admin-only data
        }
        
        $response->write(['result' => 'data']);
    }
}
```

## Using Metadata with PHP 8 Attributes

The `RouteDefinition` attribute and `RouteList::addClass()` do not accept metadata. To attach metadata to an
attribute-defined route, define that route with `Route::withMetadata()` as shown above.

## Predefined Metadata Keys

RestServer uses some predefined metadata keys for special functionality:

| Key                                       | Description                                                     |
|-------------------------------------------|-----------------------------------------------------------------|
| `RouteList::META_CLASS`                   | The closure, or `[class, method]` array, that handles the route |
| `RouteList::META_OUTPUT_PROCESSOR`        | The output processor class to use                               |
| `RouteList::META_OUTPUT_PROCESSOR_STRICT` | Whether the route output processor is strict                    |

## Common Use Cases

### Authentication and Authorization

```php
<?php
// Define routes with authentication requirements
$routeList->addRoute(
    Route::get('/api/public')
        ->withClass(PublicController::class, 'getPublicData')
        ->withMetadata(['requires_auth' => false])
);

$routeList->addRoute(
    Route::get('/api/protected')
        ->withClass(ProtectedController::class, 'getProtectedData')
        ->withMetadata(['requires_auth' => true, 'roles' => ['admin']])
);

// Create a middleware that checks the authorization requirements
class AuthMiddleware implements BeforeMiddlewareInterface
{
    public function beforeProcess($dispatcherStatus, HttpResponse $response, HttpRequest $request): MiddlewareResult
    {
        $requiresAuth = $request->getRouteMetadata('requires_auth') ?? false;
        
        if ($requiresAuth) {
            // Check authentication
            if (!$this->isAuthenticated($request)) {
                throw new Error401Exception('Authentication required');
            }
            
            // Check authorization
            $requiredRoles = $request->getRouteMetadata('roles') ?? [];
            if (!$this->hasRequiredRoles($request, $requiredRoles)) {
                throw new Error403Exception('Insufficient permissions');
            }
        }
        
        return MiddlewareResult::Continue;
    }
}
```

### Caching

```php
<?php
// Define routes with caching metadata
$routeList->addRoute(
    Route::get('/api/products')
        ->withClass(ProductController::class, 'listProducts')
        ->withMetadata(['cache_ttl' => 3600]) // Cache for 1 hour
);

// Create a caching middleware
class CacheMiddleware implements BeforeMiddlewareInterface
{
    public function beforeProcess($dispatcherStatus, HttpResponse $response, HttpRequest $request): MiddlewareResult
    {
        $cacheTtl = $request->getRouteMetadata('cache_ttl');
        
        if ($cacheTtl) {
            $cacheKey = $this->generateCacheKey($request);
            $cachedResponse = $this->getCachedResponse($cacheKey);
            
            if ($cachedResponse) {
                // Return cached response
                $response->write($cachedResponse);
                return MiddlewareResult::StopProcessing;
            }
        }
        
        return MiddlewareResult::Continue;
    }
}
```

## Custom Metadata Handlers

You can create custom middleware or components that process specific metadata:

```php
<?php
// Rate limiting middleware using metadata
class RateLimitMiddleware implements BeforeMiddlewareInterface
{
    public function beforeProcess($dispatcherStatus, HttpResponse $response, HttpRequest $request): MiddlewareResult
    {
        $rateLimit = $request->getRouteMetadata('rate_limit');
        
        if ($rateLimit) {
            $client = $this->getClientIdentifier($request);
            if ($this->hasExceededRateLimit($client, $rateLimit)) {
                throw new Error429Exception('Rate limit exceeded');
            }
        }
        
        return MiddlewareResult::Continue;
    }
}
```

By leveraging route metadata, you can add powerful capabilities to your API without cluttering your controller code. 