# Create Routes Using PHP Attributes

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

$routeDefintion = new RouteList();
$routeDefinition->addClass(\My\ClassName::class);

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
    
    #[RouteDefinition('GET', '/route1')]
    public function someMethod(HttpResponse $response, HttpRequest $request)
    {
        $response->write(['result' => 'ok']);
    }

    #[RouteDefinition('PUT', '/route2')]
    public function anotherMethod(HttpResponse $response, HttpRequest $request)
    {
        $response->write(['result' => 'ok']);
    }
    //...
}
```
