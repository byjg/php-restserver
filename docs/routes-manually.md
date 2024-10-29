# Create Routes Using Classes

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

$routeDefintion = new RouteList();
$routeDefinition->addRoute(Route::get("/testxml")
    ->withOutputProcessor(XmlOutputProcessor::class)
    ->withClass(\My\ClassName::class, "someMethod")
);

$restServer = new HttpRequestHandler();
$restServer->handle($routeDefintion);
```

the class will handle this:

```php
<?php
namespace My;

class ClassName
{
    //...
    
    /**
     * @param \ByJG\RestServer\HttpResponse $response 
     * @param \ByJG\RestServer\HttpRequest $request
     */
    public function someMethod($response, $request)
    {
        $response->write(['result' => 'ok']);
    }
    //...
}
```
