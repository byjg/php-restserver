# CORS support

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
