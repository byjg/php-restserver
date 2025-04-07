---
sidebar_position: 12
---
# The OutputProcessors

An OutputProcessor will parse the `$response->write($obj)` and output in the proper format.
The available output processors are:

- `JsonOutputProcessor` - Outputs data as JSON
- `XmlOutputProcessor` - Outputs data as XML
- `HtmlOutputProcessor` - Outputs data as HTML
- `JsonCleanOutputProcessor` - Same as JsonOutputProcessor but doesn't output empty keys
- `JsonTwirpOutputProcessor` - JSON output format compatible with Twirp service handler

## What is an OutputProcessor?

An OutputProcessor is a class that will handle the output of the route.

The main responsibilities are:
- Parse the object returned by the `HttpResponse::write()` and output in the proper format.
- Handle the exceptions and output in the proper format.

## How it works

The HttpRequestHandler will call the route and the route will call the OutputProcessor to process the
proper output for that route.

You can create a route in several ways. e.g.:

- [Using closure](routes-using-closures.md);
- [Using a class and method](routes-manually.md);
- [Using PHP Attributes](routes-using-php-attributes.md);
- [From an OpenAPI file](autogenerator-routes-openapi.md);

Each option has its own way to define the OutputProcessor. Check the documentation for each one.

Once you have the route defined, you can initialize the HttpRequestHandler and handle the request.

```php
<?php
use ByJG\RestServer\HttpRequestHandler;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;

$server = new HttpRequestHandler();

// This is the default processor for the routes that don't have a specific output processor
$server->withDefaultOutputProcessor(JsonOutputProcessor::class);
// $server->withErrorHandlerDisabled(); // Disable the error handler completely
// $server->withDetailedErrorHandler(); // Enable the detailed error handler, for debug purposes
// $server->withWriter(new CustomWriter()); // Use a custom writer for output

// Handle the request
$server->handle($routeList);
```

## Content Negotiation

By default, the OutputProcessor is determined by the route definition or the default processor set in
HttpRequestHandler.
However, the client can request a specific output format using the `Accept` header. The RestServer will use the first
content type in the Accept header that matches an available OutputProcessor.

Available MIME types:

- `application/json` - Uses JsonOutputProcessor
- `text/xml` or `application/xml` - Uses XmlOutputProcessor
- `text/html` - Uses HtmlOutputProcessor
- `*/*` - Falls back to JsonOutputProcessor

The following methods are available for selecting an output processor:

```php
// From a specific class name
BaseOutputProcessor::getFromClassName(JsonOutputProcessor::class);

// From an HTTP Accept header
BaseOutputProcessor::getFromHttpAccept();

// From a specific content type
BaseOutputProcessor::getFromContentType("application/json");

// Get an instance directly from a content type
BaseOutputProcessor::getOutputProcessorInstance("application/json");
```

## Creating your own OutputProcessor

You can create your own OutputProcessor by implementing the `OutputProcessorInterface` or
extending the `BaseOutputProcessor` class.

```php
<?php

namespace MyApp\OutputProcessor;

use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\OutputProcessor\BaseOutputProcessor;
use ByJG\Serializer\Formatter\FormatterInterface;
use Whoops\Handler\Handler;

class MyCustomOutputProcessor extends BaseOutputProcessor
{
    protected string $contentType = "application/custom-format";
    
    public function getFormatter(): FormatterInterface
    {
        return new class implements FormatterInterface {
            public function process($data): string|false
            {
                // Process the data into your custom format
                return json_encode($data, JSON_PRETTY_PRINT);
            }
        };
    }
    
    public function getErrorHandler(): Handler
    {
        return new class extends Handler {
            public function handle(): int
            {
                $exception = $this->getException();
                $this->getRun()->sendHttpCode($exception->getCode());
                echo json_encode([
                    'error' => $exception->getMessage(),
                    'code' => $exception->getCode()
                ]);
                return Handler::QUIT;
            }
        };
    }
    
    public function getDetailedErrorHandler(): Handler
    {
        return new class extends Handler {
            public function handle(): int
            {
                $exception = $this->getException();
                $this->getRun()->sendHttpCode($exception->getCode());
                echo json_encode([
                    'error' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'trace' => $exception->getTraceAsString(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine()
                ]);
                return Handler::QUIT;
            }
        };
    }
}
```

Then use it in your route definition or as the default processor:

```php
$server->withDefaultOutputProcessor(MyCustomOutputProcessor::class);
```

## Writer Interface

The OutputProcessor uses a Writer to output the data. The default writer is `HttpWriter`, which outputs directly to the
HTTP response.

You can create your own writer by implementing the `WriterInterface` and setting it in the HttpRequestHandler:

```php
use ByJG\RestServer\Writer\WriterInterface;

class MyCustomWriter implements WriterInterface
{
    public function responseCode(int $code, string $reasonPhrase = ""): void
    {
        // Set HTTP response code
    }
    
    public function header(string $header, bool $replace = true): void
    {
        // Set HTTP header
    }
    
    public function echo(string $output): void
    {
        // Output data
    }
    
    public function flush(): void
    {
        // Flush output
    }
}

$server->withWriter(new MyCustomWriter());
```
