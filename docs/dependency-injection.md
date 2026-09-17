---
sidebar_position: 23
sidebar_label: Dependency Injection
---
# Dependency Injection

By default the Server instantiates a route's controller with `new $className()`, so a
controller cannot have constructor arguments and has to fetch its collaborators itself.

Pass any PSR-11 container to `withContainer()` and controllers are resolved from it
instead, letting them declare dependencies in the constructor:

```php
<?php
use ByJG\RestServer\Server;

$server = new Server();
$server->withContainer($container);

$server->handle($routeDefinition);
```

```php
<?php
class ProductController
{
    public function __construct(protected ProductService $service)
    {
    }

    public function list(HttpResponse $response, HttpRequest $request): void
    {
        $response->write($this->service->all());
    }
}
```

## Strict by default

Setting a container changes how every controller is built, so a controller missing from
the container is **not** quietly constructed with `new`. That would skip constructor
injection and fail somewhere far from the real cause. Instead it raises
`ControllerNotRegisteredException` (HTTP 501):

| Container | Controller registered? | Result                                   |
|-----------|------------------------|------------------------------------------|
| not set   | —                      | `new $className()` — unchanged behaviour  |
| set       | yes                    | resolved from the container               |
| set       | no *(default)*         | `ControllerNotRegisteredException`         |
| set       | no, `allowUnregistered: true` | `new $className()`                 |

If the container returns something that is not an instance of the requested class, an
`InvalidClassException` is raised naming what came back.

## Migrating an existing application

Registering every controller at once is rarely practical. `allowUnregistered` lets the two
styles coexist while you migrate:

```php
<?php
// Controllers already registered are injected; the rest keep using `new`.
$server->withContainer($container, allowUnregistered: true);
```

Move controllers over one at a time, then drop the flag. It is a migration tool, not a
permanent setting — the strict default is what protects controllers added later.

## Notes

- Not adding a container leaves the previous behaviour untouched, so this is backward
  compatible.
- `withContainer()` is defined on `Server`, not on `ServerInterface`, so custom
  implementations of that interface are unaffected. `MockServer` inherits it.
- Controllers are resolved per request. Whether a controller is shared between requests
  is decided by the container's own binding (singleton vs. instance).

----
[Open source ByJG](http://opensource.byjg.com)
