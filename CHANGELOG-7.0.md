# Changelog - Version 7.0

> **Status: in development.** This document tracks changes landing on the `7.0` branch.
> Nothing here is released yet, and the contents may still change.

## Overview

Version 7.0 renames the core request-handling classes to say what they are, reworks the
`HttpRequest` accessor API into an explicit and type-safe set of methods, corrects several
HTTP status codes and content negotiation bugs, and adds PSR-11 dependency injection for
route controllers.

---

## New Features

### Dependency Injection for controllers

Controllers can now declare their dependencies in the constructor instead of fetching them
themselves. Pass any PSR-11 container to the new `Server::withContainer()` and route
controllers are resolved from it rather than built with `new $className()`:

```php
$server->withContainer($container);
```

It is **strict by default**: once a container is set, a controller missing from it raises
`ControllerNotRegisteredException` (HTTP 501) rather than being silently constructed
without its dependencies. For migrating an existing application, opt into the old
behaviour per call site and remove it once every controller is registered:

```php
$server->withContainer($container, allowUnregistered: true);
```

| Container | Controller registered?         | Result                              |
|-----------|--------------------------------|-------------------------------------|
| not set   | —                              | `new $className()` — unchanged      |
| set       | yes                            | resolved from the container         |
| set       | no *(default)*                 | `ControllerNotRegisteredException`  |
| set       | no, `allowUnregistered: true`  | `new $className()`                  |

If the container returns something that is not an instance of the requested class, an
`InvalidClassException` names what came back instead of failing later as a missing method.

Not setting a container leaves the previous behaviour untouched, so this is backward
compatible. `withContainer()` lives on `Server`, not on `ServerInterface`, so custom
implementations of that interface are unaffected.

Adds a dependency on `psr/container: ^2.0|^3.0`.
See [Dependency Injection](docs/dependency-injection.md).

### BlockPathMiddleware

New middleware to restrict access by path, either by prefix or by regular expression:

```php
$server->withMiddleware(
    (new BlockPathMiddleware())
        ->withBlockedStartWith(['/admin'])
        ->withBlockedRegEx(['~^/internal/.*~'])
);
```

### Static file serving

- `ServerStaticMiddleware` now supports `directoryIndex`, so a directory request can serve
  an index file.

---

## Bug Fixes

- **Accept header negotiation**: all Accept types are now considered instead of only the
  first, so a request whose primary type is unsupported (e.g. `image/avif`) correctly falls
  back to `*/*` rather than returning 500.
- **Double-written error bodies**: removed a spurious `processResponse()` call before
  throwing 404/405, which produced invalid JSON such as `[]{"error":...}`.
- **406 instead of 422**: when no acceptable content type is found, `Error406Exception` is
  raised — the correct RFC 7231 status.
- **Error status codes**: the `ErrorHandler` fallback path now uses
  `HttpResponseException::getStatusCode()`, so non-500 exceptions are no longer reported as
  500 when no output processor is set.
- **`HttpRequest::getHeader()`** normalizes header names and falls back to non-prefixed
  keys.
- **JSON output**: uninitialized typed properties no longer break serialization (regression
  test added).
- `MockServer` no longer reports the stale name "MockRequestHandler" in its error message.

---

## Breaking Changes

### Renamed classes

| **Before (6.x)**                        | **After (7.0)**                    |
|-----------------------------------------|------------------------------------|
| `ByJG\RestServer\HttpRequestHandler`    | `ByJG\RestServer\Server`           |
| `ByJG\RestServer\RequestHandler`        | `ByJG\RestServer\ServerInterface`  |
| `ByJG\RestServer\MockRequestHandler`    | `ByJG\RestServer\MockServer`       |
| `ByJG\RestServer\ResponseBag`           | `ByJG\RestServer\ResponseBody`     |
| `ByJG\RestServer\SerializationRuleEnum` | `ByJG\RestServer\Enum\OutputMode`  |

`OutputMode` also renames one case: `Raw` becomes `Plain`. The other cases
(`Automatic`, `SingleObject`, `ObjectList`) are unchanged.

### `HttpRequest` accessors

The generic accessors were replaced by an explicit set that names the source it reads and
offers typed variants. Each `*String` / `*Array` method returns that type or `null`, which
removes the manual casting the old `mixed` returns required.

| **Before (6.x)**   | **After (7.0)**                                              |
|--------------------|--------------------------------------------------------------|
| `get()`            | `query()` — plus `queryString()`, `queryArray()`             |
| `post()`           | `body()` — plus `bodyString()`, `bodyArray()`                |
| `request()`        | `input()` — plus `inputString()`                             |
| `param()`          | `attribute()` — plus `attributeString()`                     |
| `appendVars()`     | `addAttributes()`                                            |

Also added: `cookieString()`, `serverString()`, `sessionString()`.

### Stricter return types

- `HttpRequest::getRequestPath()` returns `?string` instead of `bool|array|string|null`;
  a `parse_url()` failure now yields `null`. Code guarding this value with `is_array()` can
  drop those checks.

### Dependency updates

| **Before (6.x)**            | **After (7.0)**              |
|-----------------------------|------------------------------|
| `byjg/webrequest: ^6.0`     | `byjg/webrequest: ^7.0`      |
| `byjg/cache-engine: ^6.0`   | `byjg/cache-engine: ^7.0`    |
| —                           | `psr/container: ^2.0\|^3.0`  |
| `phpunit/phpunit` (dev)     | `^12.5.30` (security advisories) |

---

### Enum cases renamed to PascalCase

Enum cases follow the ByJG standard and are written in PascalCase. The backed values did not
change, so `->value`, `from()`, `tryFrom()` and stored data are not affected; only code that
names a case (or reads `->name`) has to be updated.

| Enum | Before (6.x) | After (7.0) |
|---|---|---|
| `MiddlewareResult` | `stopProcessingOthers`, `stopProcessing`, `continue` | `StopProcessingOthers`, `StopProcessing`, `Continue` |

## Upgrade Path from 6.x to 7.x

### Step 1: Update the dependency

```bash
composer require "byjg/restserver:^7.0"
composer update
```

### Step 2: Rename the classes

```php
// Before
use ByJG\RestServer\HttpRequestHandler;
$server = new HttpRequestHandler();

// After
use ByJG\RestServer\Server;
$server = new Server();
```

Apply the same for `RequestHandler` → `ServerInterface`, `MockRequestHandler` →
`MockServer`, `ResponseBag` → `ResponseBody`, and `SerializationRuleEnum` →
`Enum\OutputMode` (remembering `Raw` → `Plain`).

### Step 3: Update the `HttpRequest` calls

Map each call through the table above. Where you previously cast the result, the typed
variant now does it:

```php
// Before
$id = (string)$request->get('id');

// After
$id = $request->queryString('id');
```

### Step 4: Review error handling

If you asserted on specific status codes, note that an unacceptable content type is now
**406** rather than 422, and that non-500 exceptions report their real status in the
fallback path.

### Step 5: (Optional) Adopt constructor injection

Register your controllers in a PSR-11 container and enable it in migration mode:

```php
$server->withContainer($container, allowUnregistered: true);
```

Move controllers over one at a time, keeping tests green, then drop the flag to get the
strict default.

---

For detailed documentation, visit: [https://github.com/byjg/php-restserver](https://github.com/byjg/php-restserver)

## Requirements

- PHP 8.3, 8.4, 8.5 and 8.6 are now supported: `"php": ">=8.3 <8.7"`.
  The previous `<8.6` upper bound excluded PHP 8.6, since `<8.6` is exclusive.

### ByJG dependencies

- `byjg/cache-engine` is now `^7.0`.
- `byjg/jwt-wrapper` is now `^7.0`.
- `byjg/serializer` is now `^7.0`.
- `byjg/singleton-pattern` is now `^7.0`.
- `byjg/webrequest` is now `^7.0`.

While 7.0 is unreleased these resolve to `7.0.x-dev` from each component's
`7.0` branch, via `minimum-stability: dev` with `prefer-stable: true`.

## Toolchain

- PHPUnit updated to `^12.5`.
- Psalm is installed as `psalm/phar` instead of `vimeo/psalm`.

  `vimeo/psalm` lists the PHP versions it supports and no published release includes
  8.6, so as a dev dependency it made `composer install` fail on the 8.6 build job
  before any test ran. `psalm/phar` requires only `php ^8.2` and bundles its own
  dependencies, so it installs on every PHP version in the matrix and cannot conflict
  with the project's. Psalm itself still refuses to *run* on 8.6, which is why the
  Psalm job uses 8.5. `composer psalm` runs it.

- PHPUnit 13 is deliberately **not** used. It requires PHP `>=8.4.1`, breaking the
  8.3 floor.

## Continuous Integration

- The build matrix now includes PHP 8.6.
- The Psalm job runs on PHP 8.5.

## Housekeeping

- `phpunit.xml.dist` renamed to `phpunit.xml`.
