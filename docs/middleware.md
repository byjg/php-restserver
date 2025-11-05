---
sidebar_position: 8
sidebar_label: Middleware
---
# Middleware

HttpServerHandler has the ability to inject processing Before and After process the request. Using this you can inject code, change headers
or even block the processing.

You need to implement the class `BeforeMiddlewareInterface` and `AfterMiddlewareInterface` and then add to the handler:

```php
<?php
$httpHandler = new \ByJG\RestServer\HttpRequestHandler();
$httpHandler
    ->withMiddleware(/*... instance of BeforeMiddlewareInterface or AfterMiddlewareInterface ...*/, /* routing pattern */);
```

You can add how many middleware you want, however they will be processing in the order you added them.

The route pattern allow execute the middleware only for specific routes. 
The pattern is a regular expression. If you want to match all routes, don't pass the second parameter.

Some examples:

  - `'^/api/'` - Will match all routes that starts with `/api/`
  - `'sample'` - Will match all routes that contains the word 'sample'
  - '`'^((?!/test).)*$'` - Will match any route don't contain the path /test 


## Existing Middleware

* [CORS Support](middleware-cors.md)
* [Static Server Files](middleware-staticserver.md)
* [JWT Authentication](middleware-jwt.md)



## Creating your own middleware

All middleware needs to implement the `BeforeMiddlewareInterface` or `AfterMiddlewareInterface`. When added to Http Server, the handler
will determine if it will be processed before or after the request. If the same class implements both interface, then it will run before and after.

The middleware is required to return a `MiddlewareResult` enum value. The possible values are:

- MiddlewareResult::continue - It will continue to process the next middleware and process the request.
- MiddlewareResult::stopProcessingOthers - It will stop processing the next middleware and it will abort gracefully
  processing the request.
- MiddlewareResult::stopProcessing - It will allow to process the next middleware, however it will abort gracefully
  processing the request.

The key difference between stopProcessingOthers and stopProcessing is whether other middleware in the chain gets a
chance to run.
Both will prevent the actual route handler from being executed.

### Example of a middleware implementation

```php
<?php

namespace MyApp\Middleware;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\Middleware\BeforeMiddlewareInterface;
use ByJG\RestServer\Middleware\MiddlewareResult;

class MyCustomMiddleware implements BeforeMiddlewareInterface
{
    public function handleBefore(
        int $dispatcherResult,
        HttpResponse $response,
        HttpRequest $request
    ): MiddlewareResult {
        // Do something with the request
        
        if ($someCondition) {
            return MiddlewareResult::stopProcessing;
        }
        
        return MiddlewareResult::continue;
    }
}
```

