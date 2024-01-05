# Running the rest server

You need to set up your restserver to handle ALL requests to a single PHP file. 
Normally is "app.php"

## PHP Built-in server

```bash
cd public
php -S localhost:8080 app.php
```

## Nginx

```nginx
location / {
  try_files $uri $uri/ /app.php$is_args$args;
}
```

## Apache .htaccess

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ ./app.php [QSA,NC,L]
```
