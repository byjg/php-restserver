# The OutputProcessors

An OutputProcessor will parse the `$response->write($obj)` and output in the proper format.
The available handlers are:

- JsonOutputProcessor
- XmlOutputProcessor
- HtmlHandler
- JsonCleanOutputProcessor

### Using Custom Response Handler

The Default Response Handler will process all "$response->write" into a JSON.
You can choose another Handlers. See below for a list of Available Response Handlers.

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

$routeDefinition = new \ByJG\RestServer\Route\RouteList();
$routeDefinition->addRoute(\ByJG\RestServer\Route\Route::get("/test")
    ->withOutputProcessor(XmlOutputProcessor::class)
    ->withClass(\My\ClassName::class, "someMethod")
);

$restServer = new \ByJG\RestServer\HttpRequestHandler();
$restServer->handle($routeDefinition);
```
