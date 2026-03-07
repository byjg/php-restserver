---
tags: [php, http]
---

# Rest Server

Create RESTFull services with different and customizable output handlers (JSON, XML, Html, etc.).
Auto-Generate routes from swagger.json definition.

[![Sponsor](https://img.shields.io/badge/Sponsor-%23ea4aaa?logo=githubsponsors&logoColor=white&labelColor=0d1117)](https://github.com/sponsors/byjg)
[![Build Status](https://github.com/byjg/php-restserver/actions/workflows/phpunit.yml/badge.svg?branch=master)](https://github.com/byjg/php-restserver/actions/workflows/phpunit.yml)
[![Opensource ByJG](https://img.shields.io/badge/opensource-byjg-success.svg)](http://opensource.byjg.com)
[![GitHub source](https://img.shields.io/badge/Github-source-informational?logo=github)](https://github.com/byjg/php-restserver/)
[![GitHub license](https://img.shields.io/github/license/byjg/php-restserver.svg)](https://opensource.byjg.com/opensource/licensing.html)
[![GitHub release](https://img.shields.io/github/release/byjg/php-restserver.svg)](https://github.com/byjg/php-restserver/releases/)

## Documentation

Setup:
- [Set up the RestServer](setup)

Creating and customizing routes:
- [Defining Route Names](defining-route-names)
- [Create Routes using Closures](routes-using-closures)
- [Create Routes Manually](routes-manually)
- [Create Routes using PHP Attributes](routes-using-php-attributes)
- [Auto-Generate from an OpenApi definition](autogenerator-routes-openapi) (**hot**)

Processing the request and output the response:
- [HttpRequest and HttpResponse object](httprequest-httpresponse)
- [File Uploads](file-uploads)

Advanced:
- [Middleware](middleware)
    - [CORS Support](middleware-cors)
    - [Static Server Files](middleware-staticserver)
    - [JWT Authentication](middleware-jwt)
- [Error Handler](error-handler)
- [Intercepting the Request](intercepting-request)
- [Output Processors](outprocessor)
- [Caching Routes](caching-routes)

Additional topics:
- [Mock Testing](mock-testing)
- [Route Metadata](route-metadata)
- [Content Negotiation](content-negotiation)
- [Custom HTTP Status Codes](custom-status-codes)
- [CSV Endpoint Example](csv-endpoint-example)
- [PSR-7 Adapters](psr7-adapters)

## Installation

```bash
composer require "byjg/restserver"
```

## Dependencies

```mermaid
flowchart TD
    byjg/restserver --> byjg/serializer
    byjg/restserver --> byjg/singleton-pattern
    byjg/restserver --> byjg/cache-engine
    byjg/restserver --> byjg/webrequest
    byjg/restserver --> byjg/jwt-wrapper
```

----
[Open source ByJG](http://opensource.byjg.com)
