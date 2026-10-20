---
sidebar_position: 10
sidebar_label: CORS Support
---
# CORS support

> **Note:** For general middleware usage patterns, see [Middleware](middleware.md).

Enable the Server Handler process the CORS headers and block the access if the origin doesn't match.

```php
<?php
$corsMiddleware = new \ByJG\RestServer\Middleware\CorsMiddleware();
$corsMiddleware
    ->withCorsOrigins([/* list of accepted origin */])  // Optional. Default: any origin ('.*')
    ->withAcceptCorsMethods([/* list of methods */])     // Optional. Default GET, POST, PUT, DELETE, PATCH. Don't need to pass 'OPTIONS'
    ->withAcceptCorsHeaders([/* list of headers */])     // Optional. Default Authorization, Content-Type, Accept, Origin, User-Agent, Cache-Control, Keep-Alive, X-Requested-With, If-Modified-Since
```

If the request has an `Origin` header that does not match any accepted origin, the middleware throws
`Error403Exception` (403 Forbidden). A matching `OPTIONS` pre-flight request is answered with `204 No Content`.

Note that the method `withCorsOrigins` accept a list of hosts regular expressions. e.g.

- `example\.com` - Accept only example.com
- `example\.(com|org)` - Accept both example.com and example.org
- `example\.com(\.br)?` -Accept both example.com and example.com.br
