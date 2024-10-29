# Running the rest server

You need to set up your restserver to handle ALL requests to a single PHP file. 
Normally is "app.php"

## Create a file "app.php"

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

// Define your routes
// The routes can be defined:
//   1. using PHP Attributes,
//   2. Closures
//   3. manually
//   4. or auto-generate from an OpenApi definition

// Set up the RestServer
$restServer = new HttpRequestHandler();
$restServer->handle($routeDefintion);
```

## Configure your web server to handle all requests to "app.php"

### PHP Built-in server

```bash
cd public
php -S localhost:8080 app.php
```

### Nginx

```nginx
location / {
  try_files $uri $uri/ /app.php$is_args$args;
}
```

### Apache .htaccess

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ ./app.php [QSA,NC,L]
```
