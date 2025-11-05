---
sidebar_position: 10
sidebar_label: Error Handler
---
# Error Handler

RestServer uses by default the project `flip/whoops` to handle all the errors. 

It will intercept any exception and return a formatted error message according to the 
OutputProcessor defined in the route.

## How it works

1. Each route has an OutputProcessor that will handle the output of the route. (See [OutputProcessor](outprocessor.md))
2. Initialize the HttpRequestHandler (it will handle the request and call the route)
3. Once an exception is thrown, the OutputProcessor will call the ErrorHandler 
to handle the exception and return a detailed message (debug, dev, etc) or a simple one suitable
for production.

## Configuration

### Setting a Logger

You can provide a PSR-3 compatible logger to the HttpRequestHandler when initializing it:

```php
<?php
use ByJG\RestServer\HttpRequestHandler;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Create a logger
$logger = new Logger('app');
$logger->pushHandler(new StreamHandler('path/to/your.log', Logger::WARNING));

// Initialize with logger
$server = new HttpRequestHandler($logger);
$server->handle($routeList);
```

If no logger is provided, a NullLogger instance will be used (no logging).

### Disabling the Error Handler

You can disable the error handler completely and handle the exceptions by yourself.

```php
<?php
use ByJG\RestServer\HttpRequestHandler;

$server = new HttpRequestHandler();
$server->withErrorHandlerDisabled(); // Disable the error handler completely
try {
    $server->handle($routeList);
} catch (\Exception $ex) {
    // Handle the exception
}
```

### Enabling the Detailed Error Handler

You can enable the detailed error handler. It will return a detailed message with the exception message,
stack trace, etc.

```php
<?php
use ByJG\RestServer\HttpRequestHandler;

$server = new HttpRequestHandler();
$server->withDetailedErrorHandler(); // Enable the detailed error handler, for debug purposes
$server->handle($routeList);
```

## Enabling The Twirp Error Handler

If you are implementing a callback or API that connects to a service handler
then you might need to return the errors as the twirp service expects.

To do that change the OutputProcessor to `JsonTwirpOutputProcessor`.

See how to change the OutputProcessor [here](outprocessor.md).

## Exception Types

RestServer provides several exception types that map to different HTTP status codes:

| Exception Class   | HTTP Status Code | Description            |
|-------------------|:----------------:|------------------------|
| Error400Exception |       400        | Bad Request            |
| Error401Exception |       401        | Unauthorized           |
| Error402Exception |       402        | Payment Required       |
| Error403Exception |       403        | Forbidden              |
| Error404Exception |       404        | Not Found              |
| Error405Exception |       405        | Method Not Allowed     |
| Error406Exception |       406        | Not Acceptable         |
| Error408Exception |       408        | Request Timeout        |
| Error409Exception |       409        | Conflict               |
| Error412Exception |       412        | Precondition Failed    |
| Error415Exception |       415        | Unsupported Media Type |
| Error422Exception |       422        | Unprocessable Entity   |
| Error429Exception |       429        | Too Many Requests      |
| Error500Exception |       500        | Internal Server Error  |
| Error501Exception |       501        | Not Implemented        |
| Error503Exception |       503        | Service Unavailable    |
| Error520Exception |       520        | Unknown Error          |

### Using Custom Status Codes

You can use the `ErrorCustomStatusException` to define your own HTTP status codes:

```php
<?php
use ByJG\RestServer\Exception\ErrorCustomStatusException;

// Throws an exception with custom status code 499 and message
throw new ErrorCustomStatusException(499, "Custom Status Message", "Detailed error explanation");
```

## Using Metadata with Exceptions

All HTTP exceptions in RestServer support metadata, which allows you to include additional structured information with
your error responses:

```php
<?php
use ByJG\RestServer\Exception\Error400Exception;

// Basic exception
throw new Error400Exception("Validation failed");

// Exception with metadata
throw new Error400Exception("Validation failed", 0, null, [
    'fields' => [
        'email' => 'Invalid email format',
        'password' => 'Password must be at least 8 characters'
    ],
    'request_id' => '12345-abcde',
    'documentation_url' => 'https://example.com/api/errors#validation'
]);
```

The metadata will be included in the response according to the OutputProcessor format.

## Custom Error Handling with Metadata

You can create custom error handlers that process exception metadata in specific ways:

```php
<?php
// Example of a controller with custom error handling
public function processRequest(HttpRequest $request, HttpResponse $response)
{
    try {
        // Business logic...
        $this->validateData($request->body());
    } catch (Error400Exception $ex) {
        $metadata = $ex->getMeta();
        
        // Log specific metadata fields
        $this->logger->error("Validation error", [
            'fields' => $metadata['fields'] ?? [],
            'request_id' => $metadata['request_id'] ?? null
        ]);
        
        // Rethrow the exception for the framework to handle
        throw $ex;
    }
}
```