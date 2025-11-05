---
sidebar_position: 8.2
sidebar_label: Static Server Files
---
# Server Static Files

By default, Http Server Handler will only process the defined routes. Using this middleware, if a route is not found,
the middleware will try to find a file that matches with the request path and output it.

```php
<?php
$serverStatic = new ServerStaticMiddleware();
$restServer->withMiddleware($serverStatic);
```

## Features

The ServerStaticMiddleware:

- Serves static files when no matching route is found
- Automatically detects appropriate MIME types for various file extensions
- Sets the correct Content-Type header based on the file extension
- Returns appropriate HTTP error codes when files cannot be accessed

## How It Works

1. When a route is not found (dispatcher status is `Dispatcher::NOT_FOUND`), the middleware checks if a file exists at
   the requested path
2. If the file exists and is readable, it serves the file with the appropriate MIME type
3. If the file doesn't exist or can't be accessed, the request continues to the next middleware or returns a 404

## Directory Base

This component will try to find the file in the directory starting from where the `app.php` is located.

## MIME Type Detection

The middleware uses the PHP `finfo` extension to detect MIME types. If the extension is not available, it falls back to
a built-in list of common MIME types.

## Example Use Cases

- Serving static HTML, CSS, and JavaScript files
- Serving images, documents, and other media files
- Creating a hybrid application with both API routes and static content
