# Creating Routes Using Closures

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

$routeDefinition = new RouteList();
$routeDefinition->addRoute(Route::get("/testclosure")
    ->withOutputProcessor(JsonOutputProcessor::class)
    ->withClosure(function ($response, $request) {
        $response->write('OK');
    })
);

$restServer = new HttpRequestHandler();
$restServer->handle($routeDefinition);
```
