# PHP Rest Server

[![Build Status](https://github.com/byjg/restserver/actions/workflows/phpunit.yml/badge.svg?branch=master)](https://github.com/byjg/restserver/actions/workflows/phpunit.yml)
[![Opensource ByJG](https://img.shields.io/badge/opensource-byjg-success.svg)](http://opensource.byjg.com)
[![GitHub source](https://img.shields.io/badge/Github-source-informational?logo=github)](https://github.com/byjg/restserver/)
[![GitHub license](https://img.shields.io/github/license/byjg/restserver.svg)](https://opensource.byjg.com/opensource/licensing.html)
[![GitHub release](https://img.shields.io/github/release/byjg/restserver.svg)](https://github.com/byjg/restserver/releases/)

Create RESTFull services with different and customizable output handlers (JSON, XML, Html, etc.).
Auto-Generate routes from swagger.json definition.

## Installation

```bash
composer require "byjg/restserver=4.0.*"
```

## Understanding the RestServer library

Basically the RestServer library enables you to create a full feature RESTFul
application on top of the well-known [FastRoute](https://github.com/nikic/FastRoute) library.

You can get this working in a few minutes. Just follow this steps:

1. Create the Routes
    - Using Clousures (Easiest)
    - Using Classes
    - Using the OpenApi 2 (former Swagger) and OpenApi 3 documentation (the most reliable and for long-term and maintable applications)

2. Process the Request and output the Response

Each "Path" or "Route" can have your own handle for output the response.
The are several handlers implemented and you can implement your own.

## 1. Creating the Routes

### Using Closures

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

$routeDefinition = new \ByJG\RestServer\Route\RouteList();
$routeDefinition->addRoute(\ByJG\RestServer\Route\Route::get("/testclosure")
    ->withOutputProcessor(\ByJG\RestServer\OutputProcessor\JsonOutputProcessor::class)
    ->withClosure(function ($response, $request) {
        $response->write('OK');
    })
);

$restServer = new \ByJG\RestServer\HttpRequestHandler();
$restServer->handle($routeDefinition);
```

### Using Classes

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

$routeDefintion = new \ByJG\RestServer\Route\RouteList();
$routeDefinition->addRoute(\ByJG\RestServer\Route\Route::get("/testxml")
    ->withOutputProcessor(XmlOutputProcessor::class)
    ->withClass(\My\ClassName::class, "someMethod")
);

$restServer = new \ByJG\RestServer\HttpRequestHandler();
$restServer->handle($routeDefintion);
```

the class will handle this:

```php
<?php
namespace My;

class ClassName
{
    //...
    
    /**
     * @param \ByJG\RestServer\HttpResponse $response 
     * @param \ByJG\RestServer\HttpRequest $request
     */
    public function someMethod($response, $request)
    {
        $response->write(['result' => 'ok']);
    }
    //...
}
```

### Auto-Generate from an OpenApi definition

[OpenApi](https://www.openapis.org/) is the world's largest framework of API developer tools for the
OpenAPI Specification(OAS), enabling development across the entire API lifecycle, from design and documentation,
to test and deployment.

Restserver supports both specifications 2.0 (former Swagger) and 3.0. 

There are several tools for create and maintain the definition. Once you're using this concept/methodology
you can apply here and generate automatically the routes without duplicate your work.

First you need to create a swagger.json file. 
The "operationId" must have the `Namespace\\Class::method` like the example below:

```json
{
  ...
  "paths": {
    "/pet": {
      "post": {
        "summary": "Add a new pet to the store",
        "description": "",
        "operationId": "PetStore\\Pet::addPet"
      }
    }
  }
  ...
}
```

We recommend you use the [zircote/swagger-php](https://github.com/zircote/swagger-php) tool
to generate automatically your JSON file from PHPDocs comments.

In that case the `operationId` will be generated automatically. The format will be: `HTTP_METHOD::PATH::Namespace\\Class::method` (e.g. `GET::/pet::PetStore\\Pet::getPet`)

```bash
vendor/bin/openapi -c operationid.hash=false src
```

After you have the proper swagger.json just call the `HttpRequestHandler`
and set automatic routes:

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

$routeDefinition = new \ByJG\RestServer\Route\OpenApiRouteList(__DIR__ . '/swagger.json');

$restServer = new \ByJG\RestServer\HttpRequestHandler();
$restServer->handle($routeDefinition);
```

### Caching the Routes

It is possible to cache the route by adding any PSR-16 instance on the second parameter of the constructor:

```php
<?php
$routeDefinition = new \ByJG\RestServer\Route\OpenApiRouteList(__DIR__ . '/swagger.json'); 
$routeDefinition->withCache(new \ByJG\Cache\Psr16\FileSystemCacheEngine());
```

### Customizing the Handlers

As the Swagger process is fully automated, you can define the handler by Mime Type or Route:

*Mime Type*

```php
<?php
$routeDefinition = new \ByJG\RestServer\Route\OpenApiRouteList(__DIR__ . '/swagger.json');
$routeDefinition->withOutputProcessorForMimeType(
    "application/json",
    \ByJG\RestServer\OutputProcessor\JsonCleanOutputProcessor::class
);
```

*Route*

```php
<?php
$routeDefinition = new \ByJG\RestServer\Route\OpenApiRouteList(__DIR__ . '/swagger.json');
$routeDefinition->withOutputProcessorForRoute(
    "GET",
    "/pet/{petId}",
    \ByJG\RestServer\OutputProcessor\JsonOutputProcessor::class
);
```

## 2. Processing the Request and Response

You need to implement a method, function or clousure with two parameters - Response and Request - in that order. 

## The HttpRequest and HttpResponse object

The HttpRequest and the HttpResponse always will be passed to the function will process the request

The HttpRequest have all information about the request, and the HttpResponse will be used to send back
informations to the requester.

**HttpRequest**

- get($var): get a value passed in the query string
- post($var): get a value passed by the POST Form
- server($var): get a value passed in the Request Header (eg. HTTP_REFERER)
- session($var): get a value from session;
- cookie($var): get a value from a cookie;
- request($var): get a value from the get() OR post()
- payload(): get a value passed in the request body;
- getRequestIp(): get the request IP (even if behing a proxy);
- getRequestServer(): get the request server name;
- uploadedFiles(): Return a instance of the UploadedFiles();

**HttpResponse**

- setSession($var, $value): set a value in the session;
- removeSession($var): remove a value from the session;
- addCookie($name, $value, $expire, $path, $domain): Add a cookie
- removeCookie($var): remove a value from the cookies;
- getResponseBag(): returns the ResponseBag object;
- write($object): See below;
- writeDebug($object): add information to be displayed in case of error;
- emptyResponse(): Empty all previously write responses;
- addHeader($header, $value): Add an header entry;
- setResponseCode($value): Set the HTTP response code (eg. 200, 401, etc)

### Output your data

To output your data you *have to* use the `$response->write($object)`. 
The write method supports you output a object, stdclass, array or string. The Handler object will
parse the output and setup in the proper format. 

For example:

```php
<?php

/**
 * @param \ByJG\RestServer\HttpResponse $response
 * @param \ByJG\RestServer\HttpRequest $request
 */
function ($response, $request) {
    $response->getResponseBag()->setSerializationRule(ResponseBag::SINGLE_OBJECT);
    
    // Output an array
    $array = ["field" => "value"];
    $response->write($array);

    // Output a stdClass
    $obj = new \stdClass();
    $obj->MyField = [ "teste1" => "value1", "test2" => [ "3", "4"]];
    $obj->OtherField = "OK";
    $response->write($obj);

    // Model  
    // Can be an object :
    //    - with public properties 
    //    - with getters and setters
    //    - with mixed public properties and getters and setters
    // See more about object transformations in the project https://github.com/byjg/anydataset
    // For this example, assume that Model have two properties: prop1 and prop2
    $model = new Model('tests', 'another test');
    $this->getResponse()->write($model);
}
```

The result will be something like:

```json
{
    "field":"value",
    "MyField":{
        "teste1":"value1",
        "test2":["3","4"]
    },
    "OtherField":"OK",
    "Model":{
        "prop1":"tests",
        "prop2":"another test"
    }
}
```

## The OutputProcessors

An OutputProcessor will parse the `$response->write($obj)` and output in the proper format. 
The available handlers are:

- JsonOutputProcessor
- XmlOutputProcessor
- HtmlHandler
- JsonCleanOutputProcessor

### Using Custom Response Handler

The Default Response Handler will process all "$response->write" into a JSON.
You can choose another Handlers. See below for a list of Available Response Handlers.

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

$routeDefinition = new \ByJG\RestServer\Route\RouteList();
$routeDefinition->addRoute(\ByJG\RestServer\Route\Route::get("/test")
    ->withOutputProcessor(XmlOutputProcessor::class)
    ->withClass(\My\ClassName::class, "someMethod")
);

$restServer = new \ByJG\RestServer\HttpRequestHandler();
$restServer->handle($routeDefinition);
```

## Defining a Route

You can define route with constant and/or variable. For example:

| Pattern                | Description |
|------------------------|---------------------------------|
| /myroute               | Matches exactly "/myroute"      |
| /myroute/{id}          | Matches /myroute + any character combination and set to ID |
| /myroute/{id:[0-9]+}   | Matches /myroute + any number combination and set to ID |

All variables defined above will be available as a parameter. In the example above,
if the route matches the "id" you can get using `$request->param('id');`

Creating the pattern:

- {variable} - Match anything and sets to "variable".
- {variable:specific} - Match only if the value is "specific" and sets to "variable"
- {variable:[0-9]+} - Match the regex "[0-9]+" and sets to variable;

all matches values can be obtained by

```php
$this->getRequest()->param('variable');
```

## Error Handler

This project uses the project `flip/whoops` to handle the errors. The default behavior is return the error with the minimum information necessary.

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

## Middleware

HttpServerHandler has the ability to inject processing Before and After process the request. Using this you can inject code, change headers
or even block the processing.

You need to implement the class `BeforeMiddlewareInterface` and `AfterMiddlewareInterface` and then add to the handler:

```php
<?php
$httpHandler = new \ByJG\RestServer\HttpRequestHandler();
$httpHandler
    ->withMiddleware(/*... instance of BeforeMiddlewareInterface or AfterMiddlewareInterface ...*/);
```

You can add how many middleware you want, however they will be processing in the order you added them.

### Existing Middleware

#### CORS support

Enable the Server Handler process the CORS headers and block the access if the origin doesn't match.

```php
<?php
$corsMiddleware = new \ByJG\RestServer\Middleware\CorsMiddleware();
$corsMiddleware
    ->withCorsOrigins([/* list of accepted origin */])  // Required to enable CORS
    ->withAcceptCorsMethods([/* list of methods */])     // Optional. Default all methods. Don't need to pass 'OPTIONS'
    ->withAcceptCorsHeaders([/* list of headers */])     // Optional. Default all headers
```

Note that the method `withCorsOrigins` accept a list of hosts regular expressions. e.g.

- `example\.com` - Accept only example.com
- `example\.(com|org)` - Accept both example.com and example.org
- `example\.com(\.br)?` -Accept both example.com and example.com.br

#### Server Static Files

By default, Http Server Handler will only process the defined routes. Using this middleware, if a route is not found,
the middleware will try to find a file that matches with the request path and output it.

```php
<?php
$serverStatic = new ServerStaticMiddleware();
```

### Creating your own middleware

All middleware needs to implement the `BeforeMiddlewareInterface` or `AfterMiddlewareInterface`. When added to Http Server, the handler
will determine if it will be processed before or after the request. If the same class implements both interface, then it will run before and after.

The middleware is required to return a `MiddlewareResult` class. The possible values are:

- Middleware::continue() - It will continue to process the next middleware and process the request.
- Middleware::stopProcessingOthers() - It will stop processing the next middleware and it will abort gracefully processing the request.
- Middleware::stopProcessing() - It will allow to process the next middleware, however it will abort gracefully processing the request.

## Running the rest server

You need to set up your restserver to handle ALL requests to a single PHP file. Normally is "app.php"

### PHP Built-in server

```bash
cd web
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

----
[Open source ByJG](http://opensource.byjg.com)

