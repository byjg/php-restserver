---
sidebar_position: 5
sidebar_label: Routes using PHP Attributes
---
# Create Routes Using PHP Attributes

PHP 8 attributes are a modern way to define routes in your code directly. This approach makes your code cleaner and
easier to understand.

## Basic Setup

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use ByJG\RestServer\Route\RouteList;
use ByJG\RestServer\HttpRequestHandler;

$routeDefinition = new RouteList();
$routeDefinition->addClass(\My\ClassName::class);

$restServer = new HttpRequestHandler();
$restServer->handle($routeDefinition);
```

## Class with Route Attributes

The class will handle the routes defined with the `RouteDefinition` attribute:

```php
<?php
namespace My;

use ByJG\RestServer\Attributes\RouteDefinition;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\OutputProcessor\XmlOutputProcessor;

class ClassName
{
    //...
    
    #[RouteDefinition('GET', '/route1')]
    public function someMethod(HttpResponse $response, HttpRequest $request)
    {
        $response->write(['result' => 'ok']);
    }

    #[RouteDefinition('PUT', '/route2', XmlOutputProcessor::class)]
    public function anotherMethod(HttpResponse $response, HttpRequest $request)
    {
        $response->write(['result' => 'ok']);
    }
    //...
}
```

## RouteDefinition Parameters

The `RouteDefinition` attribute accepts the following parameters:

```php
#[RouteDefinition(
    string $method = 'GET',          // HTTP method (GET, POST, PUT, DELETE, etc.)
    string $path = '/',              // URL path pattern
    string $outputProcessor = JsonOutputProcessor::class  // Output processor class
)]
```

By default, the output processor is set to `JsonOutputProcessor`. You can change it to any class that implements the
`OutputProcessorInterface`.

## Route Interceptors

Route interceptors are a powerful feature that allow you to execute code before or after a route handler, using PHP 8
attributes.

### Creating Interceptors

#### Before Route Interceptor

```php
<?php
namespace My\Interceptors;

use Attribute;
use ByJG\RestServer\Attributes\BeforeRouteInterface;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

#[Attribute(Attribute::TARGET_METHOD)]
class ValidateUserAccess implements BeforeRouteInterface
{
    private array $requiredRoles;

    public function __construct(array $requiredRoles = ['user'])
    {
        $this->requiredRoles = $requiredRoles;
    }

    public function processBefore(HttpResponse $response, HttpRequest $request)
    {
        $userRoles = $this->getUserRoles($request);
        
        // Check if user has required roles
        foreach ($this->requiredRoles as $role) {
            if (!in_array($role, $userRoles)) {
                throw new \ByJG\RestServer\Exception\Error403Exception('Insufficient permissions');
            }
        }
    }
    
    private function getUserRoles(HttpRequest $request): array
    {
        // Implementation to get user roles from request
        // For example, from JWT token
        return $request->param('jwt.roles') ?? [];
    }
}
```

#### After Route Interceptor

```php
<?php
namespace My\Interceptors;

use Attribute;
use ByJG\RestServer\Attributes\AfterRouteInterface;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

#[Attribute(Attribute::TARGET_METHOD)]
class LogApiCall implements AfterRouteInterface
{
    private string $logLevel;

    public function __construct(string $logLevel = 'info')
    {
        $this->logLevel = $logLevel;
    }

    public function processAfter(HttpResponse $response, HttpRequest $request)
    {
        // Implementation to log the API call
        $logger = /* Get your logger instance */;
        $logger->{$this->logLevel}('API call', [
            'path' => $request->getRequestPath(),
            'method' => $request->getMethod(),
            'status' => $response->getResponseCode(),
            'user' => $request->param('jwt.sub') ?? 'anonymous'
        ]);
    }
}
```

### Using Interceptors with Routes

You can apply interceptors to route methods by adding the attribute:

```php
<?php
namespace My\Controllers;

use ByJG\RestServer\Attributes\RouteDefinition;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use My\Interceptors\ValidateUserAccess;
use My\Interceptors\LogApiCall;

class UserController
{
    #[RouteDefinition('GET', '/users')]
    #[ValidateUserAccess(['admin'])]
    #[LogApiCall('debug')]
    public function listUsers(HttpResponse $response, HttpRequest $request)
    {
        // Only admin users can get here due to ValidateUserAccess interceptor
        $response->write(['users' => $this->getUserList()]);
        // After response is sent, LogApiCall will log this request
    }
    
    #[RouteDefinition('POST', '/users')]
    #[ValidateUserAccess(['admin'])]
    public function createUser(HttpResponse $response, HttpRequest $request)
    {
        // Only admins can create users
        $userData = $request->body();
        $userId = $this->createUserInDatabase($userData);
        $response->write(['id' => $userId]);
    }
    
    #[RouteDefinition('GET', '/users/profile')]
    #[ValidateUserAccess(['user', 'admin'])]
    public function getUserProfile(HttpResponse $response, HttpRequest $request)
    {
        // Both users and admins can access profiles
        $userId = $request->param('jwt.sub');
        $profile = $this->getUserProfile($userId);
        $response->write($profile);
    }
}
```

### Multiple Interceptors

You can apply multiple interceptors to a single route. They will be executed in the order they are declared:

```php
#[RouteDefinition('POST', '/sensitive-operation')]
#[ValidateUserAccess(['admin'])]
#[ValidateCSRFToken]
#[RateLimitOperation(10)]
#[LogApiCall('warning')]
public function performSensitiveOperation(HttpResponse $response, HttpRequest $request)
{
    // Implementation...
}
```

### Common Use Cases for Interceptors

1. **Authentication and Authorization**
   - Validate user roles and permissions
   - Check API keys
   - Verify tokens

2. **Input Validation**
   - Validate request parameters
   - Check required fields
   - Sanitize inputs

3. **Logging and Monitoring**
   - Log API calls
   - Track performance metrics
   - Record audit trails

4. **Rate Limiting**
   - Limit frequency of requests
   - Prevent abuse

5. **Data Transformation**
   - Transform request data before processing
   - Format response data after processing

6. **Caching**
   - Implement response caching
   - Cache invalidation

7. **Cross-cutting Concerns**
   - Transaction management
   - Error handling

Interceptors help keep your route handler methods focused on business logic by moving cross-cutting concerns into
reusable attributes.

## Built-in Route Attributes

RestServer provides built-in attributes for common authentication and authorization scenarios.

### RequireAuthenticated

The `RequireAuthenticated` attribute ensures that a route can only be accessed by authenticated users. It works in
conjunction with the JWT middleware.

```php
<?php
namespace My\Controllers;

use ByJG\RestServer\Attributes\RouteDefinition;
use ByJG\RestServer\Attributes\RequireAuthenticated;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

class SecureController
{
    #[RouteDefinition('GET', '/profile')]
    #[RequireAuthenticated]
    public function getProfile(HttpResponse $response, HttpRequest $request)
    {
        // Only authenticated users can access this endpoint
        $userId = $request->param('jwt.sub');
        $response->write(['user_id' => $userId]);
    }
}
```

If the user is not authenticated (JWT token is missing or invalid), the attribute will throw a `Error401Exception` with
the message from the JWT middleware.

### RequireRole

The `RequireRole` attribute ensures that a route can only be accessed by users with a specific role. It also checks for
authentication first.

```php
<?php
namespace My\Controllers;

use ByJG\RestServer\Attributes\RouteDefinition;
use ByJG\RestServer\Attributes\RequireRole;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

class AdminController
{
    #[RouteDefinition('GET', '/admin/users')]
    #[RequireRole('admin')]
    public function listUsers(HttpResponse $response, HttpRequest $request)
    {
        // Only users with 'admin' role can access this endpoint
        $response->write(['users' => $this->getAllUsers()]);
    }

    #[RouteDefinition('GET', '/moderator/reports')]
    #[RequireRole('moderator', 'jwt.role')]
    public function viewReports(HttpResponse $response, HttpRequest $request)
    {
        // Check the role from a custom JWT parameter
        $response->write(['reports' => $this->getReports()]);
    }
}
```

**Constructor parameters:**

- `$role` (string, required): The required role value
- `$roleParam` (string, optional, default: 'role'): The parameter path where the role is stored
- `$roleKey` (string|null, optional): Optional key to extract from the parameter if it's an array or object

**Examples:**

```php
// Basic usage - checks if $request->param('role') === 'admin'
#[RequireRole('admin')]

// Custom parameter - checks if $request->param('jwt.role') === 'moderator'
#[RequireRole('moderator', 'jwt.role')]

// Extract from array - if $request->param('user') returns ['role' => 'admin'],
// checks if $user['role'] === 'admin'
#[RequireRole('admin', 'user', 'role')]

// Extract from object - if $request->param('user') returns object with role property,
// checks if $user->role === 'editor'
#[RequireRole('editor', 'user', 'role')]
```

**Exceptions:**

- Throws `Error401Exception` if the user is not authenticated
- Throws `Error403Exception` if the user doesn't have the required role

### Combining Authentication Attributes

You can use both attributes together, though `RequireRole` already checks authentication:

```php
#[RouteDefinition('POST', '/admin/settings')]
#[RequireAuthenticated]  // Optional - RequireRole already checks this
#[RequireRole('admin')]
public function updateSettings(HttpResponse $response, HttpRequest $request)
{
    // Implementation...
}
```