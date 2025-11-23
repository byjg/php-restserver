---
sidebar_position: 15
sidebar_label: Output Processors
---
# The OutputProcessors

An OutputProcessor will parse the `$response->write($obj)` and output in the proper format.
The available output processors are:

- `JsonOutputProcessor` - Outputs data as JSON (content-type: `application/json`)
- `XmlOutputProcessor` - Outputs data as XML (content-type: `text/xml` or `application/xml`)
- `HtmlOutputProcessor` - Outputs data as HTML (content-type: `text/html`)
- `PlainTextOutputProcessor` - Outputs data as plain text (content-type: `text/plain`)
- `JsonCleanOutputProcessor` - Same as JsonOutputProcessor but doesn't output empty/null keys
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
- `text/plain` - Uses PlainTextOutputProcessor
- `*/*` - Falls back to JsonOutputProcessor

The following methods are available for selecting an output processor:

```php
// From a specific class name
BaseOutputProcessor::getFromClassName(JsonOutputProcessor::class);

// From an HTTP Accept header
BaseOutputProcessor::getFromHttpAccept();

// From a specific content type
BaseOutputProcessor::getFromContentType("application/json");
```

## Creating your own OutputProcessor

You can create your own OutputProcessor by implementing the `OutputProcessorInterface` or
extending the `BaseOutputProcessor` class.

```php
<?php

namespace MyApp\OutputProcessor;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\OutputProcessor\BaseOutputProcessor;
use ByJG\RestServer\Handler\ExceptionFormatter;
use ByJG\Serializer\Formatter\FormatterInterface;
use Throwable;

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

    public function handle(Throwable $exception, HttpResponse $response, HttpRequest $request, bool $detailed = false): void
    {
        // Set HTTP response code and log the error
        $this->getLogData($exception, $response, $request);

        // Format the exception
        $errorData = ExceptionFormatter::format($exception, $detailed);

        // Write custom error response
        $response->write([
            'error' => $errorData['message'],
            'type' => ExceptionFormatter::beautifyClassName($errorData['type'])
        ]);

        if ($detailed) {
            $response->appendContent([
                'file' => $errorData['file'],
                'line' => $errorData['line'],
                'trace' => $errorData['trace'] ?? []
            ]);
        }

        // Process and write the response
        $this->processResponse($response);
    }
}
```

Then use it in your route definition or as the default processor:

```php
$server->withDefaultOutputProcessor(MyCustomOutputProcessor::class);
```

## Advanced Output Processor Customization

### Data Transformation

You can use output processors to transform data before it's sent to the client:

```php
<?php
namespace MyApp\OutputProcessor;

use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\Serializer\Formatter\FormatterInterface;

class EnhancedJsonOutputProcessor extends JsonOutputProcessor
{
    public function getFormatter(): FormatterInterface
    {
        return new class implements FormatterInterface {
            public function process($data): string|false
            {
                // Add metadata to all responses
                if (is_array($data)) {
                    $data['api_version'] = '1.2.3';
                    $data['generated_at'] = date('c');
                    
                    // Remove sensitive fields
                    $this->removeSensitiveData($data);
                }
                
                // Convert to JSON with formatting options
                return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }
            
            private function removeSensitiveData(&$data)
            {
                if (is_array($data)) {
                    unset($data['password'], $data['secret_key']);
                    
                    foreach ($data as &$value) {
                        if (is_array($value)) {
                            $this->removeSensitiveData($value);
                        }
                    }
                }
            }
        };
    }
}
```

### Format-Specific Exception Handling

Customize error responses based on your application's needs:

```php
<?php
namespace MyApp\OutputProcessor;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\Exception\HttpResponseException;
use ByJG\RestServer\Handler\ExceptionFormatter;
use Throwable;

class ApiJsonOutputProcessor extends JsonOutputProcessor
{
    public function handle(Throwable $exception, HttpResponse $response, HttpRequest $request, bool $detailed = false): void
    {
        // Set HTTP response code and log the error
        $this->getLogData($exception, $response, $request);

        $statusCode = 500;
        $errorData = [
            'status' => 'error',
            'message' => $exception->getMessage()
        ];

        // If it's our HTTP exception type, use its data
        if ($exception instanceof HttpResponseException) {
            $statusCode = $exception->getStatusCode();

            // Add any metadata from the exception
            $meta = $exception->getMeta();
            if (!empty($meta)) {
                $errorData['details'] = $meta;
            }

            // Add standard fields for specific error types
            if ($statusCode === 400) {
                $errorData['error_type'] = 'validation_error';
            } elseif ($statusCode === 404) {
                $errorData['error_type'] = 'resource_not_found';
            } elseif ($statusCode === 401 || $statusCode === 403) {
                $errorData['error_type'] = 'authentication_error';
            }
        }

        // Add request ID for tracking
        $errorData['request_id'] = $this->generateRequestId();

        // Write the error response
        $response->write($errorData);

        // Process and write the response
        $this->processResponse($response);
    }

    private function generateRequestId(): string
    {
        return uniqid('req-', true);
    }
}
```

### Caching Integration

Integrate caching with your output processor:

```php
<?php
namespace MyApp\OutputProcessor;

use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use Psr\Cache\CacheItemPoolInterface;

class CachedJsonOutputProcessor extends JsonOutputProcessor
{
    private CacheItemPoolInterface $cache;
    private int $defaultTtl;
    
    public function __construct(CacheItemPoolInterface $cache, int $defaultTtl = 3600)
    {
        parent::__construct();
        $this->cache = $cache;
        $this->defaultTtl = $defaultTtl;
    }
    
    public function processResponse(HttpResponse $response): void
    {
        $cacheKey = $this->generateCacheKey($response);
        $cacheItem = $this->cache->getItem($cacheKey);
    
        if ($cacheItem->isHit()) {
            $payload = $cacheItem->get();        // Already formatted JSON string
            $response->addHeader('Content-Type', $this->contentType);
            $this->writeHeader($response);
            $this->writeData($payload);
            $this->writer->flush();
            return;
        }
    
        // Build the payload the same way JsonOutputProcessor does, so we can store it
        $serialized = $response->getResponseBag()->process($this->buildNull, $this->onlyString);
        $payload = $this->getFormatter()->process($serialized);
    
        $cacheItem->set($payload);
        $cacheItem->expiresAfter($this->defaultTtl);
        $this->cache->save($cacheItem);
    
        // Ensure headers contain the exact Content-Type we'll emit
        $response->addHeader('Content-Type', $this->contentType);
        $this->writeHeader($response);
        $this->writeData($payload);
        $this->writer->flush();
    }
    
    private function generateCacheKey(HttpResponse $response): string
    {
        // Create a unique key based on the serialized data and response code
        return sprintf(
            'api_response_%d_%s',
            $response->getResponseCode(),
            md5(serialize($response->getResponseBag()->getCollection()))
        );
    }
}
```

### ContentType Negotiation Extension

Extend your output processor to handle additional MIME types:

For a complete example of creating a custom CSV OutputProcessor with file download capabilities,
see [CSV Endpoint Example](csv-endpoint-example.md).

## Writer Interface

The OutputProcessor uses a Writer to control how data is sent to the client. RestServer provides three built-in writers,
and you can create custom ones by implementing the `WriterInterface`.

### Built-in Writers

#### HttpWriter (Default)

The default writer that sends output directly to the HTTP response using PHP's native functions.

```php
<?php
use ByJG\RestServer\Writer\HttpWriter;
use ByJG\RestServer\HttpRequestHandler;

$server = new HttpRequestHandler();
// HttpWriter is used by default - no need to set explicitly
```

**Use HttpWriter when:**

- Running in a web server environment (Apache, Nginx, PHP-FPM)
- Sending standard HTTP responses to browsers or API clients
- You want default behavior

#### MemoryWriter

Captures all output (headers and body) in memory without sending it to the client. Extends `StdoutWriter` and adds
methods to retrieve captured data.

```php
<?php
use ByJG\RestServer\Writer\MemoryWriter;
use ByJG\RestServer\HttpRequestHandler;

$writer = new MemoryWriter();
$server = new HttpRequestHandler();
$server->withWriter($writer);
$server->handle($routeDefinition);

// Retrieve captured output
$statusCode = $writer->getStatusCode();  // e.g., 200
$headers = $writer->getHeaders();         // ['HTTP/1.1 200 OK', 'Content-Type: application/json']
$body = $writer->getData();               // '{"result":"success"}'
```

**Use MemoryWriter when:**

- Writing unit tests and need to assert response content
- Capturing output for post-processing or logging
- Building mock responses for testing
- You don't want output sent to client immediately

**Available methods:**

- `getData(): string` - Get captured response body
- `getHeaders(): array` - Get all headers that would be sent
- `getStatusCode(): int` - Get HTTP status code

#### StdoutWriter

Outputs headers and body to stdout (standard output) instead of HTTP. Useful for CLI/console applications.

```php
<?php
use ByJG\RestServer\Writer\StdoutWriter;
use ByJG\RestServer\HttpRequestHandler;

$server = new HttpRequestHandler();
$server->withWriter(new StdoutWriter());
$server->handle($routeDefinition);

// Output format:
// HTTP/1.1 200 OK
// Content-Type: application/json
//
// {"result":"success"}
```

**Use StdoutWriter when:**

- Running RestServer in CLI/console mode
- Debugging and want to see raw HTTP output
- Piping output to files or other commands
- Testing HTTP responses in terminal

### Creating Custom Writers

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

## Custom Writer Examples

### Logging Writer

```php
<?php
use ByJG\RestServer\Writer\WriterInterface;
use ByJG\RestServer\Writer\HttpWriter;
use Psr\Log\LoggerInterface;

class LoggingWriter implements WriterInterface
{
    private WriterInterface $innerWriter;
    private LoggerInterface $logger;
    
    public function __construct(LoggerInterface $logger, WriterInterface $innerWriter = null)
    {
        $this->logger = $logger;
        $this->innerWriter = $innerWriter ?? new HttpWriter();
    }
    
    public function responseCode(int $code, string $reasonPhrase = ""): void
    {
        $this->logger->info("Setting response code: $code $reasonPhrase");
        $this->innerWriter->responseCode($code, $reasonPhrase);
    }
    
    public function header(string $header, bool $replace = true): void
    {
        $this->logger->debug("Setting header: $header");
        $this->innerWriter->header($header, $replace);
    }
    
    public function echo(string $output): void
    {
        $this->logger->debug("Output length: " . strlen($output));
        $this->innerWriter->echo($output);
    }
    
    public function flush(): void
    {
        $this->logger->debug("Flushing output");
        $this->innerWriter->flush();
    }
}
```

### Testing Writer

```php
<?php
use ByJG\RestServer\Writer\WriterInterface;

class TestingWriter implements WriterInterface
{
    private int $responseCode = 200;
    private array $headers = [];
    private string $output = '';
    
    public function responseCode(int $code, string $reasonPhrase = ""): void
    {
        $this->responseCode = $code;
    }
    
    public function header(string $header, bool $replace = true): void
    {
        $this->headers[] = $header;
    }
    
    public function echo(string $output): void
    {
        $this->output .= $output;
    }
    
    public function flush(): void
    {
        // Do nothing in test mode
    }
    
    // Helper methods for testing
    public function getResponseCode(): int
    {
        return $this->responseCode;
    }
    
    public function getHeaders(): array
    {
        return $this->headers;
    }
    
    public function getOutput(): string
    {
        return $this->output;
    }
}
```

Using these customization capabilities, you can extend RestServer's output processing to meet your specific application
requirements.
