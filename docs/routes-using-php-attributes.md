---
sidebar_position: 5
---
# Create Routes Using PHP Attributes

PHP 8 attributes are a modern way to define routes in your code directly. This approach makes your code cleaner and
easier to understand.

## Basic Setup

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use ByJG\RestServer\Route\RouteList;
use ByJG\RestServer\HttpRequestHandler;

$routeDefinition = new RouteList();
$routeDefinition->addClass(\My\ClassName::class);

$restServer = new HttpRequestHandler();
$restServer->handle($routeDefinition);
```

## Class with Route Attributes

The class will handle the routes defined with the `RouteDefinition` attribute:

```php
<?php
namespace My;

use ByJG\RestServer\Attributes\RouteDefinition;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\OutputProcessor\XmlOutputProcessor;

class ClassName
{
    //...
    
    #[RouteDefinition('GET', '/route1')]
    public function someMethod(HttpResponse $response, HttpRequest $request)
    {
        $response->write(['result' => 'ok']);
    }

    #[RouteDefinition('PUT', '/route2', XmlOutputProcessor::class)]
    public function anotherMethod(HttpResponse $response, HttpRequest $request)
    {
        $response->write(['result' => 'ok']);
    }
    //...
}
```

## RouteDefinition Parameters

The `RouteDefinition` attribute accepts the following parameters:

```php
#[RouteDefinition(
    string $method = 'GET',          // HTTP method (GET, POST, PUT, DELETE, etc.)
    string $path = '/',              // URL path pattern
    string $outputProcessor = JsonOutputProcessor::class  // Output processor class
)]
```

By default, the output processor is set to `JsonOutputProcessor`. You can change it to any class that implements the
`OutputProcessorInterface`.
