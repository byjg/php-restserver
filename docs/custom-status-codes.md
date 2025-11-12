---
sidebar_position: 20
sidebar_label: Custom Status Codes
---

# Custom HTTP Status Codes

RestServer provides built-in support for standard HTTP status codes, but also allows you to define and use custom status
codes for specialized use cases.

## Using Custom Status Codes

The `ErrorCustomStatusException` class allows you to throw exceptions with custom HTTP status codes:

```php
<?php
use ByJG\RestServer\Exception\ErrorCustomStatusException;

// Using a custom status code not defined in HTTP standards
throw new ErrorCustomStatusException(
    499,                          // Custom status code
    "Client Closed Request",      // Status message
    "The client closed the connection before the server finished processing",  // Detailed message
    0,                            // Error code (optional)
    null,                         // Previous exception (optional)
    ['request_id' => '12345']     // Metadata (optional)
);
```

## Common Custom Status Codes

While not part of the official HTTP specification, several custom status codes are commonly used:

| Status Code | Common Name              | Description                                         |
|-------------|--------------------------|-----------------------------------------------------|
| 420         | Enhance Your Calm        | Rate limiting, borrowed from Twitter API            |
| 498         | Invalid Token            | Token expired or invalid, from Nginx                |
| 499         | Token Required           | Token is required, from Nginx                       |
| 509         | Bandwidth Limit Exceeded | When a service has exceeded its allocated bandwidth |
| 529         | Site is overloaded       | Service temporarily overloaded                      |
| 530         | Site is frozen           | Resource access is denied                           |
| 598         | Network read timeout     | Network read timeout behind proxy                   |
| 599         | Network connect timeout  | Network connect timeout behind proxy                |

## Use Cases for Custom Status Codes

1. **API-Specific Semantics**:
   ```php
   // API-specific status for user content moderation
   throw new ErrorCustomStatusException(
       470,
       "Content Moderation Required",
       "The submitted content requires moderation before publishing"
   );
   ```

2. **Internal Service Communication**:
   ```php
   // Internal microservice status code
   throw new ErrorCustomStatusException(
       580,
       "Dependent Service Unavailable",
       "A required internal service is currently unavailable"
   );
   ```

3. **Cloud Provider Integration**:
   ```php
   // Status related to quota or limits
   throw new ErrorCustomStatusException(
       543,
       "Resource Quota Exceeded",
       "You have exceeded your allocated resource quota"
   );
   ```

## Best Practices

When using custom status codes, follow these best practices:

1. **Documentation**: Always document your custom status codes thoroughly so that API consumers understand them.

2. **Range Selection**: Choose status codes in the 4xx (client error) or 5xx (server error) range based on the nature of
   the error.

3. **Consistency**: Be consistent with your custom status codes across your API.

4. **Compatibility**: Consider how your API will be consumed; some HTTP clients might not handle non-standard status
   codes gracefully.

5. **Metadata**: Use the metadata parameter to provide additional structured information:

```php
throw new ErrorCustomStatusException(
    499,
    "Authentication Token Expired",
    "Your authentication token has expired",
    0,
    null,
    [
        'token_expiry' => $expiryTime,
        'token_issue_time' => $issueTime,
        'renewal_url' => '/api/auth/renew'
    ]
);
```

## Integration with Output Processors

Custom status codes integrate seamlessly with RestServer's output processors:

```php
<?php
namespace MyApp\Controller;

use ByJG\RestServer\Exception\ErrorCustomStatusException;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

class ResourceController
{
    public function processRequest(HttpResponse $response, HttpRequest $request)
    {
        try {
            // Business logic
            $result = $this->processBusinessLogic($request);
            
            // Return successful response
            $response->write($result);
        } catch (QuotaExceededException $ex) {
            // Convert domain exception to HTTP exception with custom status
            throw new ErrorCustomStatusException(
                543,
                "Resource Quota Exceeded",
                $ex->getMessage(),
                $ex->getCode(),
                $ex,
                ['quota_reset_time' => $ex->getResetTime()]
            );
        }
    }
}
```

By leveraging custom status codes, you can create a more expressive API that communicates precise error states to
clients. 