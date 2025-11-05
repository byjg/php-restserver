---
sidebar_position: 4
sidebar_label: Routes Manually
---
# Create Routes Using Classes

> **Note:** For complete setup instructions including HttpRequestHandler configuration, see [Setup](setup.md).

Creating routes with classes provides better organization, testability, and reusability compared to closures. This
approach is recommended for production applications with complex business logic.

## Basic Example

### Step 1: Define the Route

```php
<?php
use ByJG\RestServer\Route\RouteList;
use ByJG\RestServer\Route\Route;
use ByJG\RestServer\OutputProcessor\XmlOutputProcessor;

$routeDefinition = new RouteList();
$routeDefinition->addRoute(
    Route::get("/api/data")
        ->withOutputProcessor(XmlOutputProcessor::class)
        ->withClass(\My\ClassName::class, "getData")
);
```

### Step 2: Create the Handler Class

```php
<?php
namespace My;

use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\HttpRequest;

class ClassName
{
    /**
     * @param HttpResponse $response
     * @param HttpRequest $request
     */
    public function getData(HttpResponse $response, HttpRequest $request)
    {
        $response->write(['result' => 'ok']);
    }
}
```

## Using Route Factory Methods

RestServer provides convenient factory methods for all HTTP methods:

```php
<?php
use ByJG\RestServer\Route\Route;

// GET request
$routeDefinition->addRoute(
    Route::get("/api/users")->withClass(UserController::class, "list")
);

// POST request
$routeDefinition->addRoute(
    Route::post("/api/users")->withClass(UserController::class, "create")
);

// PUT request
$routeDefinition->addRoute(
    Route::put("/api/users/{id}")->withClass(UserController::class, "update")
);

// DELETE request
$routeDefinition->addRoute(
    Route::delete("/api/users/{id}")->withClass(UserController::class, "delete")
);
```

## Route Parameters

Access route parameters in your class methods:

```php
<?php
namespace My;

use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\HttpRequest;

class UserController
{
    public function getUser(HttpResponse $response, HttpRequest $request)
    {
        $userId = $request->param('id');
        // Fetch user data...
        $response->write(['user_id' => $userId]);
    }
}
```

## When to Use Class-Based Routes

**Best for:**

- Production applications
- Complex business logic
- Code that needs testing
- Reusable controllers
- Team collaboration

**Consider alternatives for:**

- Simple endpoints → Use [Closures](routes-using-closures.md)
- Modern PHP projects → Use [PHP Attributes](routes-using-php-attributes.md)

## Additional Configuration

For information on:

- Output processors: See [Output Processors](outprocessor.md)
- Route metadata: See [Route Metadata](route-metadata.md)
- Route patterns: See [Defining Route Names](defining-route-names.md)
- Complete setup: See [Setup](setup.md)
