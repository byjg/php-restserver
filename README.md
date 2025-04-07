[![Build Status](https://github.com/byjg/php-restserver/actions/workflows/phpunit.yml/badge.svg?branch=master)](https://github.com/byjg/php-restserver/actions/workflows/phpunit.yml)
[![Opensource ByJG](https://img.shields.io/badge/opensource-byjg-success.svg)](http://opensource.byjg.com)
[![GitHub source](https://img.shields.io/badge/Github-source-informational?logo=github)](https://github.com/byjg/php-restserver/)
[![GitHub license](https://img.shields.io/github/license/byjg/php-restserver.svg)](https://opensource.byjg.com/opensource/licensing.html)
[![GitHub release](https://img.shields.io/github/release/byjg/php-restserver.svg)](https://github.com/byjg/php-restserver/releases/)

# PHP Rest Server

Create RESTFull services with different and customizable output handlers (JSON, XML, Html, etc.).
Auto-Generate routes from swagger.json definition.

## Documentation

Setup:
- [Set up the RestServer](docs/setup.md)

Creating and customizing routes:
- [Defining Route Names](docs/defining-route-names.md)
- [Create Routes using Closures](docs/routes-using-closures.md)
- [Create Routes Manually](docs/routes-manually.md)
- [Create Routes using PHP Attributes](docs/routes-using-php-attributes.md)
- [Auto-Generate from an OpenApi definition](docs/autogenerator-routes-openapi.md) (**hot**)

Processing the request and output the response:
- [HttpRequest and HttpResponse object](docs/httprequest-httpresponse.md)

Advanced:
- [Middleware](docs/middleware.md)
- [Error Handler](docs/error-handler.md)
- [Intercepting the Request](docs/intercepting-request.md)
- [Output Processors](docs/outprocessor.md)
- [Caching Routes](docs/caching-routes.md)

## Installation

```bash
composer require "byjg/restserver"
```

## Dependencies

```mermaid
flowchart TD
   byjg/restserver --> byjg/serializer
   byjg/restserver --> byjg/singleton-pattern
   byjg/restserver --> nikikc/fast-route
   byjg/restserver --> filp/whoops
   byjg/restserver --> byjg/cache-engine
   byjg/restserver --> byjg/webrequest
   byjg/restserver --> byjg/jwt-wrapper
   byjg/restserver --> ext-json
```

----
[Open source ByJG](http://opensource.byjg.com)

