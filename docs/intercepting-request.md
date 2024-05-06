# Intercepting Request

It is possible add a PHP attribute to intercept the request before or after the route is executed.

## Create the class to intercept the request

### Intercepting Before Execute the Route

```php
<?php

namespace My;

use ByJG\RestServer\Attributes\BeforeRouteInterface;

#[Attribute(Attribute::TARGET_METHOD)]
class MyBeforeProcess implements BeforeRouteInterface
{
    public function processBefore(HttpResponse $response, HttpRequest $request)
    {
        // Do something before the route is executed
    }
}
```

### Intercepting After Execute the Route

```php
<?php

namespace My;

use ByJG\RestServer\Attributes\AfterRouteInterface;

#[Attribute(Attribute::TARGET_METHOD)]
class MyAfterProcess implements AfterRouteInterface
{
    public function processAfter(HttpResponse $response, HttpRequest $request)
    {
        // Do something after the route is executed
    }
}
```

## Set the attributes in the class:

```php
<?php
namespace My;

class ClassName
{
    //...
    
    #[RouteDefinition('GET', '/route1')]
    #[MyBeforeProcess()]
    public function someMethod(HttpResponse $response, HttpRequest $request)
    {
        $response->write(['result' => 'ok']);
    }

    #[RouteDefinition('PUT', '/route2')]
    #[MyAfterProcess()]
    public function anotherMethod(HttpResponse $response, HttpRequest $request)
    {
        $response->write(['result' => 'ok']);
    }
    //...
}
```
