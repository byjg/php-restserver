# Middleware

HttpServerHandler has the ability to inject processing Before and After process the request. Using this you can inject code, change headers
or even block the processing.

You need to implement the class `BeforeMiddlewareInterface` and `AfterMiddlewareInterface` and then add to the handler:

```php
<?php
$httpHandler = new \ByJG\RestServer\HttpRequestHandler();
$httpHandler
    ->withMiddleware(/*... instance of BeforeMiddlewareInterface or AfterMiddlewareInterface ...*/);
```

You can add how many middleware you want, however they will be processing in the order you added them.

## Existing Middleware

### CORS support

Enable the Server Handler process the CORS headers and block the access if the origin doesn't match.

```php
<?php
$corsMiddleware = new \ByJG\RestServer\Middleware\CorsMiddleware();
$corsMiddleware
    ->withCorsOrigins([/* list of accepted origin */])  // Required to enable CORS
    ->withAcceptCorsMethods([/* list of methods */])     // Optional. Default all methods. Don't need to pass 'OPTIONS'
    ->withAcceptCorsHeaders([/* list of headers */])     // Optional. Default all headers
```

Note that the method `withCorsOrigins` accept a list of hosts regular expressions. e.g.

- `example\.com` - Accept only example.com
- `example\.(com|org)` - Accept both example.com and example.org
- `example\.com(\.br)?` -Accept both example.com and example.com.br

### Server Static Files

By default, Http Server Handler will only process the defined routes. Using this middleware, if a route is not found,
the middleware will try to find a file that matches with the request path and output it.

```php
<?php
$serverStatic = new ServerStaticMiddleware();
```

## Creating your own middleware

All middleware needs to implement the `BeforeMiddlewareInterface` or `AfterMiddlewareInterface`. When added to Http Server, the handler
will determine if it will be processed before or after the request. If the same class implements both interface, then it will run before and after.

The middleware is required to return a `MiddlewareResult` class. The possible values are:

- Middleware::continue() - It will continue to process the next middleware and process the request.
- Middleware::stopProcessingOthers() - It will stop processing the next middleware and it will abort gracefully processing the request.
- Middleware::stopProcessing() - It will allow to process the next middleware, however it will abort gracefully processing the request.

