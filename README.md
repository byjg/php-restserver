---
sidebar_key: restserver
tags: [php, http]
---

# Rest Server

A REST server whose routes can come straight from your OpenAPI specification: document the API once, and that document is the router.

[![Sponsor](https://img.shields.io/badge/Sponsor-%23ea4aaa?logo=githubsponsors&logoColor=white&labelColor=0d1117)](https://github.com/sponsors/byjg)
[![Build Status](https://github.com/byjg/php-restserver/actions/workflows/phpunit.yml/badge.svg?branch=master)](https://github.com/byjg/php-restserver/actions/workflows/phpunit.yml)
[![Opensource ByJG](https://img.shields.io/badge/opensource-byjg-success.svg)](https://opensource.byjg.com)
[![Install MCP Server](https://img.shields.io/badge/Install-MCP_Server-8A2BE2?logo=modelcontextprotocol&logoColor=white)](https://opensource.byjg.com/docs/ai/mcpserver-byjg-docs/)
[![GitHub source](https://img.shields.io/badge/Github-source-informational?logo=github)](https://github.com/byjg/php-restserver/)
[![GitHub license](https://img.shields.io/github/license/byjg/php-restserver.svg)](https://opensource.byjg.com/license/)
[![GitHub release](https://img.shields.io/github/release/byjg/php-restserver.svg)](https://github.com/byjg/php-restserver/releases/)

## Why

In most frameworks the routes and the API documentation are two separate things, written
twice and kept in sync by hand. Sooner or later they disagree, and the documentation is the
one that is wrong.

RestServer can read the routes from the OpenAPI specification itself. Each `operationId`
names the method that handles it:

```php
$restServer = new Server();
$restServer->handle(new OpenApiRouteList(__DIR__ . '/openapi.json'));
```

- **The specification is the router.** An endpoint that is not documented does not exist,
  so the documentation cannot drift from the code. Generate the specification from
  attributes with [swagger-php](https://github.com/zircote/swagger-php), or write it by hand.
- **Write objects, not responses.** A controller writes data to an `HttpResponse`; the output
  handler serializes it to JSON, XML, CSV or HTML, chosen by content negotiation.
- **Routes the usual ways too:** PHP attributes, closures, or a list built by hand.

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
- [File Uploads](docs/file-uploads.md)

Advanced:
- [Middleware](docs/middleware.md)
    - [CORS Support](docs/middleware-cors.md)
    - [Static Server Files](docs/middleware-staticserver.md)
    - [JWT Authentication](docs/middleware-jwt.md)
    - [Block Path](docs/middleware-blockpath.md)
- [Error Handler](docs/error-handler.md)
- [Dependency Injection](docs/dependency-injection.md)
- [Intercepting the Request](docs/intercepting-request.md)
- [Output Processors](docs/outprocessor.md)
- [Caching Routes](docs/caching-routes.md)

Additional topics:
- [Mock Testing](docs/mock-testing.md)
- [Route Metadata](docs/route-metadata.md)
- [Content Negotiation](docs/content-negotiation.md)
- [Custom HTTP Status Codes](docs/custom-status-codes.md)
- [CSV Endpoint Example](docs/csv-endpoint-example.md)
- [PSR-7 Adapters](docs/psr7-adapters.md)

## Installation

```bash
composer require "byjg/restserver"
```

## Running Tests

```bash
docker compose up -d
vendor/bin/phpunit
```

----
[Open source ByJG](https://opensource.byjg.com)
