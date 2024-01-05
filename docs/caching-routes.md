# Caching the Routes

It is possible to cache the route by adding any PSR-16 instance on the second parameter of the constructor:

```php
<?php
$routeDefinition = new OpenApiRouteList(__DIR__ . '/swagger.json'); 
$routeDefinition->withCache(new FileSystemCacheEngine());
```
