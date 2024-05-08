# Server Static Files

By default, Http Server Handler will only process the defined routes. Using this middleware, if a route is not found,
the middleware will try to find a file that matches with the request path and output it.

```php
<?php
$serverStatic = new ServerStaticMiddleware();
```

This component will try to find the file in the directory starting from the where the `app.php` is located.
