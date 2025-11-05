---
sidebar_position: 12
sidebar_label: Output Processors
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

use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\Exception\HttpResponseException;
use Whoops\Handler\Handler;

class ApiJsonOutputProcessor extends JsonOutputProcessor
{
    public function getErrorHandler(): Handler
    {
        return new class extends Handler {
            public function handle(): int
            {
                $exception = $this->getException();
                
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
                
                $this->getRun()->sendHttpCode($statusCode);
                
                // Add request ID for tracking
                $errorData['request_id'] = $this->generateRequestId();
                
                echo json_encode($errorData, JSON_PRETTY_PRINT);
                return Handler::QUIT;
            }
            
            private function generateRequestId(): string
            {
                return uniqid('req-', true);
            }
        };
    }
}
```

### Caching Integration

Integrate caching with your output processor:

```php
<?php
namespace MyApp\OutputProcessor;

use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\SerializationRuleEnum;
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
    
    public function process(HttpResponse $response, mixed $object, int $intSerialization = SerializationRuleEnum::Serial_StopOnObject): void
    {
        // Generate cache key based on response data
        $cacheKey = $this->generateCacheKey($object);
        
        // Try to get from cache
        $cacheItem = $this->cache->getItem($cacheKey);
        
        if ($cacheItem->isHit()) {
            // Use cached output
            $output = $cacheItem->get();
            $response->setContentType($this->contentType);
            $response->write($output, false);
        } else {
            // Process normally
            parent::process($response, $object, $intSerialization);
            
            // Cache the output for future requests
            $cacheItem->set($response->getBody());
            $cacheItem->expiresAfter($this->defaultTtl);
            $this->cache->save($cacheItem);
        }
    }
    
    private function generateCacheKey($data): string
    {
        // Create a unique key based on the data and other factors
        return 'api_response_' . md5(serialize($data));
    }
}
```

### ContentType Negotiation Extension

Extend your output processor to handle additional MIME types:

```php
<?php
namespace MyApp\OutputProcessor;

use ByJG\RestServer\OutputProcessor\BaseOutputProcessor;
use ByJG\Serializer\Formatter\FormatterInterface;

class CsvOutputProcessor extends BaseOutputProcessor
{
    protected string $contentType = "text/csv";
    
    public function getFormatter(): FormatterInterface
    {
        return new class implements FormatterInterface {
            public function process($data): string|false
            {
                if (!is_array($data)) {
                    return false;
                }
                
                // Create CSV output
                $output = fopen('php://temp', 'r+');
                
                // Add headers if we have an associative array
                if (isset($data[0]) && is_array($data[0])) {
                    fputcsv($output, array_keys($data[0]));
                    
                    // Add data rows
                    foreach ($data as $row) {
                        fputcsv($output, $row);
                    }
                } else {
                    // It's a single record
                    fputcsv($output, array_keys($data));
                    fputcsv($output, array_values($data));
                }
                
                rewind($output);
                $csvContent = stream_get_contents($output);
                fclose($output);
                
                return $csvContent;
            }
        };
    }
}
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
