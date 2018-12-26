# PHP Rest Server
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/byjg/restserver/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/byjg/restserver/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/40968662-27b2-4a31-9872-a29bdd68da2b/mini.png)](https://insight.sensiolabs.com/projects/40968662-27b2-4a31-9872-a29bdd68da2b)
[![Build Status](https://travis-ci.org/byjg/restserver.svg?branch=master)](https://travis-ci.org/byjg/restserver)


Create RESTFull services with different and customizable output handlers (JSON, XML, Html, etc.).
Auto-Generate routes from swagger.json definition.

# Installation

```bash
composer require "byjg/restserver=3.0.*"
```
# Understanding the RestServer library

Basically the RestServer library enables you to create a full feature RESTFul 
application on top of the well-known [FastRoute](https://github.com/nikic/FastRoute) library.

You can get this working in a few minutes. Just follow this steps:

1. Create the Routes
    - Using Clousures (Easiest)
    - Using Classes
    - Using the Swagger documentation (the most reliable and for long-term and maintable applications)
    
2. Process the Request and output the Response

Each "Path" or "Route" can have your own handle for output the response. 
The are several handlers implemented and you can implement your own.

# 1. Creating the Routes

## Using Closures

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

$restServer = new \ByJG\RestServer\ServerRequestHandler();

$restServer->addRoute(
    \ByJG\RestServer\RoutePattern::get(
        '/testclosure',                   // The route
        function ($response, $request) {  // The Closure for Process the request 
            $response->write('OK');
        }
    )
);

$restServer->handle();
```

## Using Classes

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

$restServer = new \ByJG\RestServer\ServerRequestHandler();

$restServer->addRoute(
    \ByJG\RestServer\RoutePattern::get(
        '/test',                          // The Route
        'SomeMethod',                     // The method will process the request
        '\\My\\ClassName'                 // The class that have the method
    )
);

$restServer->handle();
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

## Auto-Generate from a "swagger.json" definition

[Swagger](https://swagguer.io) is the world's largest framework of API developer tools for the 
OpenAPI Specification(OAS), enabling development across 
the entire API lifecycle, from design and documentation, 
to test and deployment.

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
for auto generate your JSON file from PHPDocs comments.
Is the best way for maintain your code documented and with the Swagger definition updated. 
Since the Zircode Swagger PHP version 2.0.14 you can
generate the proper "operationId" for you. Just run on command line:

```bash
swagger --operationid
```

After you have the proper swagger.json just call the `ServiceRequestHandler`
and set automatic routes:

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

$restServer = new \ByJG\RestServer\ServerRequestHandler();

$restServer->setRoutesSwagger(__DIR__ . '/swagger.json');

$restServer->handle();
```

## Caching the Routes

It is possible to cache the route by adding any PSR-16 instance on the second parameter of the constructor:

```php
<?php
$restServer = new \ByJG\RestServer\ServerRequestHandler();
$restServer->setRoutesSwagger(
    __DIR__ . '/swagger.json',
    new \ByJG\Cache\Psr16\FileSystemCacheEngine()
);
```

## Customizing the Handlers

As the Swagger process is fully automated, you can define the handler by Mime Type or Route:

*Mime Type*

```php
<?php
$restServer->setMimeTypeHandler("application/json", \ByJG\RestServer\HandleOutput\JsonCleanHandler::class);
```

*Route*

```php
<?php
$restServer->setPathHandler("GET", "/pet/{petId}", \ByJG\RestServer\HandleOutput\JsonHandler::class);
```

# 2. Processing the Request and Response

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

## Output your data 

To output your data you *have to* use the `$response->write($object)`. 
The write method supports you output a object, stdclass, array or string. The Handler object will
parse the output and setup in the proper format. 

For example:

```php
<?php

function ($response, $request) {
    $response->getResponseBag()->serializationRule(ResponseBag::SINGLE_OBJECT);
    
    // Output an array
    $array = ["field" => "value"];
    $response()->write($array);

    // Output a stdClass
    $obj = new \stdClass();
    $obj->MyField = [ "teste1" => "value1", "test2" => [ "3", "4"]];
    $obj->OtherField = "OK";
    $response()->write($obj);

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

# The Handlers

The Handler will be parse the `$response->write($obj)` and output in the proper format. 
The available handlers are:

- JsonHandler
- XmlHandler
- HtmlHandler
- JsonCleanHandler

## Using Custom Response Handler

The Default Response Handler will process all "$response->write" into a JSON.
You can choose another Handlers. See below for a list of Available Response Handlers.

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

$restServer = new \ByJG\RestServer\ServerRequestHandler();

$restServer->addRoute(
    \ByJG\RestServer\RoutePattern::get(
        '/test',                          // The Route
        'SomeMethod',                     // The method will process the request
        '\\My\\ClassName',                // The class that have the method
        \ByJG\RestServer\HandleOutput\XmlHandler::class           // The Handler
    )
);

$restServer->handle();
```


# Defining a Route


You can define route with constant and/or variable. For example:


| Pattern                | Description |
|------------------------|---------------------------------|
| /myroute               | Matches exactly "/myroute"      |
| /myroute/{id}          | Matches /myroute + any character combination and set to ID |
| /myroute/{id:[0-9]+}   | Matches /myroute + any number combination and set to ID |

All variables defined above will be available throught the $_GET. In the example above,
if the route matches the "id" will available in the `$request->get('id');`

Creating the pattern:

- {variable} - Match anything and sets to "variable".
- {variable:specific} - Match only if the value is "specific" and sets to "variable"
- {variable:[0-9]+} - Match the regex "[0-9]+" and sets to variable;

all matches values can be obtained by

```php
$this->getRequest()->get('variable')
```

# Running the rest server

You need to setup your restserver to handle ALL requests to a single PHP file. Normally is "app.php" 

## PHP Built-in server

```
cd web
php -S localhost:8080 app.php
```

## Nginx 

```
location / {
  try_files $uri $uri/ /app.php$is_args$args;
}
```

## Apache .htaccess

```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ ./app.php [QSA,NC,L]
```

----
[Open source ByJG](http://opensource.byjg.com)

