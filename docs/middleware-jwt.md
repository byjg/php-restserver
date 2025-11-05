---
sidebar_position: 9.3
sidebar_label: JWT Authentication
---
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
$server->withMiddleware($jwtMiddleware);
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

## Ignoring Paths

You can configure the JWT middleware to ignore specific paths, which allows public access to certain routes without
requiring JWT authentication:

```php
<?php
// Define paths to ignore (using regular expressions)
$ignorePaths = [
    '^/public/.*',   // Ignore all paths starting with /public/
    '/auth/login',   // Ignore the login endpoint
    '/docs/.*'       // Ignore documentation routes
];

// Create the middleware with ignore paths
$jwtMiddleware = new JwtMiddleware($jwtWrapper, $ignorePaths);
$server->withMiddleware($jwtMiddleware);
```

### How Path Ignoring Works

1. For each request, the middleware checks if the request path matches any pattern in the ignore list
2. If a match is found, the middleware skips token validation and allows the request to proceed
3. If no match is found, the middleware validates the JWT token as usual

### Path Pattern Format

The ignore paths use PHP's regular expression format:

- `^/api/public/.*` - All paths that start with `/api/public/`
- `/auth/login` - Exact match for `/auth/login`
- `.*\.(jpg|png|gif)$` - All paths that end with .jpg, .png or .gif

### Example Use Cases

- Public API documentation endpoints
- Authentication endpoints (login, register)
- Public resource endpoints
- Health check endpoints
- Static file serving
