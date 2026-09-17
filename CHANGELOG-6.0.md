# Changelog - Version 6.0

## Overview

Version 6.0 represents a major release with significant improvements in error handling, type safety, authentication,
PSR-7 compatibility, and PHP 8.4 support. This release removes the dependency on `filp/whoops` in favor of a custom
error handling implementation and introduces several new features for route-level security and content processing.

---

## New Features

### Authentication and Authorization

- **RequireAuthenticated Attribute**: New route-level attribute for enforcing authentication requirements on specific
  endpoints
- **RequireRole Attribute**: New route-level attribute for role-based access control with support for:
    - Extracting roles from arrays
    - Extracting roles from objects
    - Enhanced validation logic

### PSR-7 Adapters

- **PSR-7 Compatibility**: New adapter implementation for `HttpRequest` and `HttpResponse` to work seamlessly with PSR-7
  compliant libraries
- Full support for PSR-7 HTTP message interfaces
- Comprehensive test coverage for PSR-7 adapters

### Content Processing

- **CSV Output Processor**: New output processor for generating CSV responses from API endpoints
- **Plain Text Processor**: Support for plain text responses in addition to JSON and XML
- **Strict Mode**: Enhanced output processor validation with strict mode support for MIME type checking
- **Content Negotiation**: Improved content negotiation capabilities with better MIME type handling

### Development Tools

- **Docker-based Development Setup**: Complete Docker development environment with `docker-compose.yml`
- **Enhanced Testing**: Mock testing capabilities with improved `MockRequestHandler`
- **Gitpod Support**: Pre-configured `.gitpod.yml` for cloud-based development

### Documentation

- Comprehensive new documentation sections:
    - File Uploads guide
    - PSR-7 Adapters guide
    - Mock Testing guide
    - Route Metadata guide
    - Content Negotiation guide
    - Custom HTTP Status Codes guide
    - CSV Endpoint Example
    - Enhanced middleware documentation (CORS, Static Server, JWT)

---

## Improvements

### Type Safety and Code Quality

- Enhanced type safety across the entire codebase with nullable type hints
- Improved null handling in request processing and output processors
- Better type checks and casting for request handling, routes, and middleware
- Refined Psalm configuration with improved annotations
- Added Psalm cache configuration for faster static analysis

### Error Handling

- Custom error handling system replacing `filp/whoops`
- Better exception hierarchy with `HttpResponseException` base class
- Enhanced exception metadata support
- Improved error handler cleanup in testing
- More detailed error messages with configurable verbosity

### Header Handling

- Normalized header handling with case-insensitive comparisons
- Enhanced header updates across different sources
- Better content-type override capabilities

### Testing

- Stricter PHPUnit failure conditions
- Enhanced test coverage for authentication and authorization
- Route-level tests for new attributes
- Better test organization and assertions

### Workflow and CI/CD

- Updated GitHub Actions workflow to use privileged containers
- Latest checkout action version support
- Enhanced PHPUnit workflow configuration

---

## Breaking Changes

| Area                 | Before (5.x)                                                                                                                                  | After (6.0)                                                                                                                                   | Description                                                              |
|----------------------|-----------------------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------------------------|
| **PHP Version**      | `>=8.1 <8.4`                                                                                                                                  | `>=8.3 <8.6`                                                                                                                                  | Minimum PHP version raised to 8.3, added support for 8.4 and 8.5         |
| **Dependencies**     | `byjg/serializer: ^5.0`<br>`byjg/singleton-pattern: ^5.0`<br>`byjg/cache-engine: ^5.0`<br>`byjg/webrequest: ^5.0`<br>`byjg/jwt-wrapper: ^5.0` | `byjg/serializer: ^6.0`<br>`byjg/singleton-pattern: ^6.0`<br>`byjg/cache-engine: ^6.0`<br>`byjg/webrequest: ^6.0`<br>`byjg/jwt-wrapper: ^6.0` | All ByJG dependencies updated to version 6.0                             |
| **Error Handler**    | Used `filp/whoops` library                                                                                                                    | Custom built-in error handler                                                                                                                 | Removed dependency on `filp/whoops`; replaced with native error handling |
| **ErrorHandler API** | `setHandler(Handler $handler)`                                                                                                                | `setOutputProcessor(OutputProcessorInterface $processor, HttpResponse $response, HttpRequest $request, bool $detailed = false)`               | Complete refactor of error handler initialization and configuration      |
| **PHPUnit**          | `^5.7\|^7.4\|^9.6`                                                                                                                            | `^10.5\|^11.5`                                                                                                                                | Updated to modern PHPUnit versions only                                  |
| **Psalm**            | `^5.9`                                                                                                                                        | `^5.9\|^6.13`                                                                                                                                 | Added support for Psalm 6.x                                              |
| **PSR Dependencies** | PSR-7 not explicitly supported                                                                                                                | Added `psr/http-client: ^1.0`<br>`psr/http-factory: ^1.0\|^1.1`                                                                               | New PSR-7 support requires additional dependencies                       |

---

## Upgrade Path from 5.x to 6.0

### Step 1: Update PHP Version

Ensure you are running PHP 8.3 or higher:

```bash
php -v
```

If you're on PHP 8.1 or 8.2, upgrade your PHP installation before proceeding.

### Step 2: Update Dependencies

Update your `composer.json` to require version 6.0:

```json
{
  "require": {
    "byjg/restserver": "^6.0"
  }
}
```

Then run:

```bash
composer update byjg/restserver
```

This will automatically update all related ByJG dependencies to version 6.0.

### Step 3: Remove Whoops Integration (If Customized)

If you have custom error handlers using Whoops:

**Before (5.x):**

```php
use ByJG\RestServer\ErrorHandler;
use Whoops\Handler\PrettyPageHandler;

$errorHandler = ErrorHandler::getInstance();
$errorHandler->setHandler(new PrettyPageHandler());
$errorHandler->register();
```

**After (6.0):**

```php
use ByJG\RestServer\ErrorHandler;
use ByJG\RestServer\Application;

// Error handling is now automatic and configured via HttpRequestHandler
$server = new Application($logger);
$server->withDetailedErrorHandler(); // Optional: for development
$server->handle($routeList);
```

### Step 4: Update PHPUnit Configuration

If you have PHPUnit tests, update your `phpunit.xml.dist`:

**Before (5.x):**

```xml

<phpunit phpVersion="7.4.0"/>
```

**After (6.0):**

```xml

<phpunit phpVersion="8.3.0"/>
```

And update your `composer.json` dev dependencies:

```json
{
  "require-dev": {
    "phpunit/phpunit": "^10.5|^11.5"
  }
}
```

### Step 5: Review Custom Error Handlers

If you implemented custom error handling:

1. Review the new `ErrorHandler` API in `src/ErrorHandler.php`
2. Update any custom error handler integrations to use the new `setOutputProcessor()` method
3. Test error scenarios thoroughly

### Step 6: Test Your Application

Run your test suite:

```bash
composer test
```

Run static analysis:

```bash
composer psalm
```

### Step 7: Optional - Leverage New Features

Consider adopting new features:

1. **Authentication Attributes**: Replace custom authentication middleware with `#[RequireAuthenticated]` and
   `#[RequireRole]` attributes
2. **PSR-7 Adapters**: Use PSR-7 adapters if integrating with PSR-7 libraries
3. **CSV Output**: Implement CSV endpoints using the new `CsvOutputProcessor`
4. **Docker Development**: Use the provided `docker-compose.yml` for local development

---

## Migration Examples

### Example 1: Error Handler Configuration

**Before (5.x):**

```php
use ByJG\RestServer\ErrorHandler;
use Whoops\Handler\JsonResponseHandler;

$errorHandler = ErrorHandler::getInstance();
$errorHandler->setHandler(new JsonResponseHandler());
$errorHandler->register();

$server = new HttpRequestHandler();
$server->handle($routeList);
```

**After (6.0):**

```php
use ByJG\RestServer\Application;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('app');
$logger->pushHandler(new StreamHandler('php://stderr', Logger::ERROR));

$server = new Application($logger);
// Error handling is automatic based on the output processor
$server->handle($routeList);
```

### Example 2: Adding Authentication

**New in 6.0:**

```php
use ByJG\RestServer\Attributes\RouteDefinition;
use ByJG\RestServer\Attributes\RequireAuthenticated;
use ByJG\RestServer\Attributes\RequireRole;

class UserController
{
    #[RouteDefinition('/api/profile', method: 'GET')]
    #[RequireAuthenticated]
    public function getProfile()
    {
        // Only authenticated users can access
    }

    #[RouteDefinition('/api/admin', method: 'GET')]
    #[RequireRole(['admin', 'superuser'])]
    public function adminPanel()
    {
        // Only users with admin or superuser role can access
    }
}
```

### Example 3: CSV Output

**New in 6.0:**

```php
use ByJG\RestServer\OutputProcessor\CsvOutputProcessor;

$route->addRoute('/api/export', function() {
    return [
        ['name' => 'John', 'email' => 'john@example.com'],
        ['name' => 'Jane', 'email' => 'jane@example.com'],
    ];
})
->withOutputProcessor(CsvOutputProcessor::class);
```

---

## Notes

- The removal of `filp/whoops` reduces dependencies and gives more control over error handling
- PHP 8.3 requirement enables use of modern PHP features and better performance
- All ByJG 6.0 libraries are designed to work together - avoid mixing 5.x and 6.x versions
- The new error handling system is more lightweight and better integrated with the output processor system
- Enhanced type safety may reveal previously hidden type issues in your code - address these with proper type
  declarations

---

## Contributors

Thank you to all contributors who made this release possible!

For detailed commit history, see: https://github.com/byjg/php-restserver/compare/5.0.3...6.0.0
