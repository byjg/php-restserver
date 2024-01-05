# Error Handler

RestServer uses the project `flip/whoops` to handle the errors. The default behavior is return the error with the minimum information necessary.

```php
[
    "type" => Exception Type,
    "message" => Error Message. 
]
```

To disable completely any error handler you can:

```php
<?php

$http = (new HttpErrorHandler())
    ->withDoNotUseErrorHandler();

try {
    $http->handle(.....);
} catch (Exception $ex) {
    // You have to handle by yourself the errors
}
```

or you can get the detailed error handler with all information necessary to debug your application:

```php
<?php

$http = (new HttpErrorHandler())
    ->withDetailedErrorHandler();

$http->handle(.....);
```

The error handler return the data based on the format defined by first accept content type header.

The currently implementation are:

- HTML
- JSON
- XML