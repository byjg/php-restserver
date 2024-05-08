# Jwt Middleware

The JWT Middleware is a middleware that will validate the JWT token in the Authorization header.
If the token is valid, it will add the `jwt` attribute to the request with the decoded token.

## Usage

### Define the JwtKey

```php
<?php

use ByJG\RestServer\Middleware\JwtMiddleware;

$jwtKey = new JwtKeySecret("password", false);
$jwtWrapper = new JwtWrapper("localhost", $jwtKey);
```

### Create the JwtMiddleware and add to the HttpHandler

```php
$jwtMiddleware = new JwtMiddleware($jwtWrapper);
```

### Getting the Parsed Token    

Once the page is processed, the middleware will add the `jwt` attribute to the request with the decoded parameters.

#### Return if the token is valid or not

Valid values are:
- JwtMiddleware::JWT_PARAM_PARSE_STATUS_OK
- JwtMiddleware::JWT_PARAM_PARSE_STATUS_ERROR

```php
$request->param(JwtMiddleware::JWT_PARAM_PARSE_STATUS)
```

#### Return the reason why the token is invalid

```php
$request->param(JwtMiddleware::JWT_PARAM_PARSE_MESSAGE)
```

#### Return the decoded token

```php
// KEY is the key defined in the token
$request->param(JwtMiddleware::JWT_PARAM_PREFIX . "." . $KEY);
```
