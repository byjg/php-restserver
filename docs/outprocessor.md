# The OutputProcessors

An OutputProcessor will parse the `$response->write($obj)` and output in the proper format.
The available handlers are:

- JsonOutputProcessor
- XmlOutputProcessor
- HtmlOutputProcessor
- JsonCleanOutputProcessor (same as JsonOutputProcessor but don't output empty keys)

## What is an OutputProcessor?

An OutputProcessor is a class that will handle the output of the route.

The main responsibility are:
- Parse the object returned by the `HttpResponse::write()` and output in the proper format.
- Handle the exceptions and output in the proper format.

## How it works

The HttpRequestHandler will call the route and the route will call the OutputProcessor to process the
proper output for that route.

You can create a route on several ways. e.g.:

- [Using closure](routes-using-closures.md);
- [Using a class and method](routes-manually.md);
- [Using PHP Attributes](routes-using-php-attributes.md);
- [From an OpenAPI file](autogenerator-routes-openapi.md);

Each option has your own way to define the OutputProcessor. Check the documentation for each one.

Once you have the route defined, you can initialize the HttpRequestHandler and handle the request.

```php
<?php
use ByJG\RestServer\HttpRequestHandler;

$server = new HttpRequestHandler();

// This is the default processor for the routes that don't have a specific output processor
$server->withDefaultOutputProcessor(JsonOutputProcessor::class);
// $server->withErrorHandlerDisabled(); // Disable the error handler completely
// $server->withDetailedErrorHandler(); // Enable the detailed error handler, for debug purposes

// Handle the request
$server->handle($routeList);
```
## Creating your own OutputProcessor

You can create your own OutputProcessor. Just create a class that implements the `OutputProcessorInterface` or
inherit from any existent OutputProcessor.
