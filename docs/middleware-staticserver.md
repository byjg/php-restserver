---
sidebar_position: 11
sidebar_label: Static Server Files
---
# Server Static Files

> **Note:** For general middleware usage patterns, see [Middleware](middleware.md).

By default, Http Server Handler will only process the defined routes. Using this middleware, if a route is not found,
the middleware will try to find a file that matches with the request path and output it.

```php
<?php
$serverStatic = new ServerStaticMiddleware();

// When the request path is a directory, serve this file from it (default: 'index.html').
// Pass an empty string to disable it.
$serverStatic = new ServerStaticMiddleware('default.htm');
```

## Features

The ServerStaticMiddleware:

- Serves static files when no matching route is found
- Automatically detects appropriate MIME types for various file extensions
- Sets the correct Content-Type header based on the file extension
- Serves the directory index file (`index.html` by default) when the request path is a directory
- Refuses to serve source files (`php`, `vb`, `cs`, `rb`, `py`, `py3`, `lua`) with `415 Unsupported Media Type`

## How It Works

1. When a route is not found (dispatcher status is `Dispatcher::NOT_FOUND`), the middleware checks if a file exists at
   the requested path
2. If the file exists and is readable, it serves the file with the appropriate MIME type
3. If the file doesn't exist or can't be accessed, the request continues to the next middleware or returns a 404

## Directory Base

This component will try to find the file in the directory starting from where the `app.php` is located.

## MIME Type Detection

The middleware maps the file extension to a MIME type using a built-in list of extensions. Unknown extensions are
served as `application/octet-stream`. The PHP `finfo` extension is required: without it the middleware throws
`Error500Exception`.

## Example Use Cases

- Serving static HTML, CSS, and JavaScript files
- Serving images, documents, and other media files
- Creating a hybrid application with both API routes and static content
