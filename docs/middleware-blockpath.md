---
sidebar_position: 13
sidebar_label: Block Path
---
# Block Path Middleware

> **Note:** For general middleware usage patterns, see [Middleware](middleware.md).

Block access to specific paths, returning `403 Forbidden` when a request path matches a rule.
Two matching strategies are available and can be combined.

## Block by prefix

Denies any request whose path starts with one of the given prefixes:

```php
<?php
$middleware = (new \ByJG\RestServer\Middleware\BlockPathMiddleware())
    ->withBlockedStartWith(['/admin', '/internal']);
```

## Block by regular expression

Denies any request whose path matches one of the given regex patterns:

```php
<?php
$middleware = (new \ByJG\RestServer\Middleware\BlockPathMiddleware())
    ->withBlockedRegEx(['~\.env$~', '~\.(git|svn)/~']);
```

## Combining both strategies

Both methods are fluent and can be chained. Rules from both lists are checked — a request is blocked if it matches any rule from either list:

```php
<?php
$middleware = (new \ByJG\RestServer\Middleware\BlockPathMiddleware())
    ->withBlockedStartWith(['/admin', '/internal'])
    ->withBlockedRegEx(['~\.env$~', '~\.(git|svn)/~']);
```

## Registering the middleware

```php
<?php
$server = new \ByJG\RestServer\Server();
$server->withMiddleware($middleware);
```

To restrict blocking to a subset of routes, pass a route pattern as the second argument:

```php
<?php
$server->withMiddleware($middleware, '^/api/');
```