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

### Handling a PSR-7 request

`Server::withRequestObject()` makes the Server handle a PSR-7 `ServerRequestInterface`
instead of the request PHP's globals describe, for a PSR-7 runtime or a persistent worker
where `php://input` is not the request body:

```php
$server->withRequestObject($psr7Request)->handle($routes);
```

It relies on the new `Psr7RequestAdapter::toHttpRequest()`, the counterpart of
`fromHttpRequest()`, and on `HttpRequest`'s new optional `$payload` constructor argument
(`null`, the default, still reads `php://input`). The request's uploaded files are available
through `HttpRequest::uploadedFiles()` as the new `Psr7UploadedFiles`, read only when used (new
`HttpRequest::setUploadedFiles()`). What the request does not have, the adapter parses from a form
body: the POST data when there is no parsed body (most PSR-7 implementations do not parse the body),
and the uploaded files of a multipart body when the request has none (the runtime did not parse it, as
in a worker or a test).

`MockServer` handles its request through the same adapter. While the request is handled, it
fills PHP's globals from it as a web server would (each upload in its own temporary file), and
restores them afterward, so a test no longer leaves the previous request in the globals.

The conversion is cheap: `MockServer` copies the request's headers with byjg/webrequest's
`withHeaders()` (one copy instead of one per header), and `Psr7RequestAdapter::toHttpRequest()` reads the
parsed body only for a form body, which is the only one that becomes the POST data: for a JSON body it
used to parse the body for nothing, and for an empty one it threw and caught an exception. About
21 us -> 8 us per request.

### Compiled routes: `CompiledRouteList`

`CompiledRouteList::compile($routeList)` writes a route list as PHP code: the routes and FastRoute's dispatch data,
as constant arrays that OPcache keeps in memory. `new CompiledRouteList($file)` loads it: in a web request, where
the route list is built again for each request, the routes and the dispatcher cost ~1 us instead of reading the
schema or the cache and building the dispatcher (~420 us for php-gluo's 25 routes, as `OpenApiRouteList` read
them). A `Route` is created only for the route a request matches. See [Caching Routes](docs/caching-routes.md).

### `OpenApiRouteList` reads the schema only to generate the routes

The constructor parsed the whole schema file, with `Serialize::fromJson()->toArray()`, even when the routes came
from the cache (`withCache()`): ~390 us per request for php-gluo's `openapi.json`. The file is now read when the
routes are generated or `getSchema()` is called, and a JSON file with `json_decode()` (the same array). An invalid
schema (not JSON, no `paths`) throws then, instead of in the constructor; a missing file or an unknown extension
still throws in the constructor.

### Trusted proxies: `Server::withTrustedProxies()`

`HttpRequest::getRequestIp()` reads the client's address from `X-Forwarded-For` (or `Forwarded`) only for a
request whose connection comes from a proxy given to `Server::withTrustedProxies()`, and only the addresses
those proxies added: the header is read from the right, and the first address that is not a trusted proxy is
the client. See [The client IP behind a proxy](docs/httprequest-httpresponse.md#the-client-ip-behind-a-proxy).

### The route dispatcher is built once

`RouteList::getDispatcher()` built FastRoute's dispatcher on every request (~30 us with 25 routes). It is now built
once and kept until a route is added (`addRoute()`, `setRoutes()`, `addClass()`), for a route list that serves many
requests (a DI singleton in a persistent worker, or in tests). It compiles only the method and the path of each
route: a match reads the output processor, the class and the metadata from the `Route` (the new
`RouteDispatcher`), so a route changed after a request, through `getRoute()`, is dispatched as it is now. A match
gives the same route metadata as before.

`Server::handle()` now starts every request with a new response and forgets the request once
handled, so one `Server` can serve many requests. A `Server` bound as a DI singleton, as Gluo
does, used to answer the next request with the previous one's status and body when the process
outlives the request (a persistent worker).

---

## Bug Fixes

- **`JwtMiddleware` reads the token from the request it handles.** It called `JwtWrapper::extractData()` without
  the token, which reads `$_SERVER['HTTP_AUTHORIZATION']`: a PSR-7 request (`Server::withRequestObject()`, a
  persistent worker) was never authenticated (401), and a worker that fills the globals could be authenticated with
  another request's token. It now passes the request's `Authorization` header (`HttpRequest::getHeader()`, which
  PHP-FPM fills from `$_SERVER` as before).

- **`RouteList::getRoute()` finds every route a request matches**: it built a regex from the route key, so a
  placeholder with a pattern (`{id:\d+}`) gave an invalid regex (a PHP warning, and no route), the methods of a
  route with several (`PUT|PATCH`) became an alternation (`getRoute('PUT', '/anything')` matched it), and a static
  route with several methods was not found. It now compares the method with the route's methods, and the path with
  FastRoute's parser, as the dispatcher reads it (patterns, optional segments).

- **`MockServer` loses no query string**: a query given in the path of the request's URI, as
  php-swagger-test sends it (`->withPath('/project?page=2&size=10')`), stayed in the path with the old
  `MockHttpRequest`, so the route got no query: every list returned its first page. The URI is parsed again
  from its string, so it is the query.
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
- **After-middleware on a failed route**: `afterProcess()` declared the exception as
  `?string` but received the exception object, a `TypeError`. See Breaking Changes.
- **Path traversal in `ServerStaticMiddleware`**: a request path with `..` could serve any
  file the PHP process could read, and a `file://` request URI was served from its absolute
  path. Now only files inside the directory of the entry script are served; anything that
  resolves outside it (`..`, symlinks, `file://`) is a 404.
- **Path traversal in `UploadedFiles::saveTo()`**: the file name (the client's, when no new
  name is given) was appended to the destination as it came, so `../app.php` wrote outside
  it. A name that is not a plain file name now throws `InvalidArgumentException`.
- **`#[RouteDefinition]` with PATCH, OPTIONS or any method other than GET/POST/PUT/DELETE**:
  the route was built with `Route::$method()`, which only exists for those four, so
  registering the class failed with "Call to undefined method". It now uses `Route::create()`.
- **`Error500Exception`** extended `\Exception` instead of `HttpResponseException`, unlike every
  other `Error*Exception`, and the status list had no 500 entry. It is now an
  `HttpResponseException` with status message "Internal Server Error". See Breaking Changes.
- **`ServerInterface::handle()`** declared `$session = true` while `Server` and `MockServer`
  default to `false` (the documented default). The interface now says `false`.

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
| `getHeader()`      | `header()` — plus `headerString()`; `getHeader()` is deprecated and removed in 8.0 |

Also added: `cookieString()`, `serverString()`, `sessionString()`.

`header($name)` reads only request headers, in any case (`Content-Type` or `content-type`), and
returns all of them, keyed by lowercase name, when called without a name. `getHeader()` also
fell back to any `$_SERVER` key (`getHeader('REQUEST_METHOD')` returned the method); `header()`
does not.

### Stricter return types

- `HttpRequest::getRequestPath()` returns `?string` instead of `bool|array|string|null`;
  a `parse_url()` failure now yields `null`. Code guarding this value with `is_array()` can
  drop those checks.

### Renamed methods

| **Before (6.x)**                       | **After (7.0)**                      |
|----------------------------------------|--------------------------------------|
| `HttpResponse::getResponseBag()`       | `HttpResponse::getResponseBody()`    |
| `ResponseBag::setSerializationRule()`  | `ResponseBody::serializeAs()`        |
| `ResponseBag::getSerializationRule()`  | `ResponseBody::getOutputMode()`      |

### `AfterMiddlewareInterface::afterProcess()` receives the exception

- The last parameter is `?\Exception $exception` instead of `?string $exception`. The server
  always passed the exception object, so a route that threw while an after-middleware was
  registered failed with a `TypeError`. Change the signature in your after-middleware:

  ```php
  public function afterProcess(HttpResponse $response, HttpRequest $request, string $class, string $method, ?\Exception $exception): MiddlewareResult
  ```

### Changed exceptions and status codes

- `CorsMiddleware` rejects an origin with `Error403Exception` (403) instead of
  `Error401Exception` (401).
- `Error500Exception` now extends `HttpResponseException` instead of `\Exception`. A
  `catch (HttpResponseException $e)` now also catches it, and the response status line is
  `500 Internal Server Error` instead of `500 Internal Error`.
- `InvalidClassException` now extends `Error500Exception` instead of `\Exception`, so it is an
  `HttpResponseException` reported as 500.
- `BaseOutputProcessor::getFromContentType()` throws `Error406Exception` instead of
  `OperationIdInvalidException` for an unknown content type. This also applies when
  `OpenApiRouteList` generates routes for a content type with no output processor.
- `MockHttpRequest` is removed: `MockServer` converts the PSR-7 request with
  `Psr7RequestAdapter`. A test that built a `MockHttpRequest` should send its request through
  `MockServer`. A multipart field no longer ends with the line break the old parser kept.
- `HttpRequest::getRequestIp()` returns `REMOTE_ADDR` unless the `Server` has trusted proxies
  (`Server::withTrustedProxies()`, see New Features). It returned the first address of the first of
  `X-Forwarded-For`, `X-Forwarded`, `Forwarded-For`, `Forwarded`, `CF-Connecting-IP`,
  `X-Original-Forwarded-For` it found, then `REMOTE_ADDR`, `SERVER_ADDR` or `Client-IP`. Any client can send
  those headers, so it returned the address the client chose: an application using the IP for rate limits,
  lockouts, audit logs or allow-lists could be bypassed or misled. `Forwarded` was returned unparsed
  (`for=...;proto=...`), and without `REMOTE_ADDR` the server's own `SERVER_ADDR` was returned. An
  application behind a proxy now gets the proxy's address until it names its proxies.
- The static `HttpRequest::ip()` and `HttpRequest::userAgent()` are removed. They read PHP's
  `$_SERVER`, which does not describe the request when it reaches the `Server` as a PSR-7 request
  (`Server::withRequestObject()` in RoadRunner, Swoole, ...). Use `$request->getRequestIp()` and
  `$request->getUserAgent()` on the `HttpRequest` the route receives.

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

Then rename the methods: `getResponseBag()` → `getResponseBody()`,
`setSerializationRule()` → `serializeAs()` and `getSerializationRule()` → `getOutputMode()`.

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
