# PHP Rest Server
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/byjg/restserver/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/byjg/restserver/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/40968662-27b2-4a31-9872-a29bdd68da2b/mini.png)](https://insight.sensiolabs.com/projects/40968662-27b2-4a31-9872-a29bdd68da2b)
[![Build Status](https://travis-ci.org/byjg/restserver.svg?branch=master)](https://travis-ci.org/byjg/restserver)

## Description

Enable to create RESTFull services with strong model schema. The main goal is to abstract the class transformation 
into JSON/XML and encapsulate the server commands.

## Usage

The main purpose of this package is abstract all complexity to process a RESTFull request and handle the object response. 

The quick guide is:

- Create an empty class exteding from \ByJG\RestServer\ServiceAbstract
- Implement the methods that will handle the HTTP METHODS (Get, Post, Delete or Put);

For example, if you want to process the HTTP method POST you have to do:

```php
<?php
namespace Sample;

class MyClass extends \ByJG\RestServer\ServiceAbstract
{

    public function post()
    {
        $id = $this->getRequest()->get('id');

        // Do something here...

        $this->getResponse()->write( [ 'result' => 'ok' ] );
    }
}
```

The usual url for call this class is (see more in Routing below):

```
http://yourserver.com/Sample.MyClass/1234     # Or .xml or .csv
```

### Processing the request

All $_GET, $_SERVER, $_POST, etc are encapsulated in the HttpRequest object. Inside the ServiceAbstract class you just call
`$this->getRequest()` method. 

The available options are:
* get('key') - Get a parameter passed by GET (the same as $_GET). If not found return false.
* post('key') - Get a parameter passed by POST (the same as $_POST). If not found return false.
* server('key') - Get the parameters sent by server (the same as $_SERVER). If not found return false.
* cookie('key') - Get the cookie sent by the client (the same as $_COOKIE). If not found return false.
* session('key') - Get a server session value(the same as $_SESSION). If not found return false.
* request('key') - Get a value from any of get, post, server, cookie or session. If not found return false.
* payload() - Get the payload passed during the request(the same as php://input). If not found return empty.
* getRequestIP() - Get the client request IP. It handles proxies and firewalls to get the correct IP;
* getRequestServer() - Get the sername. It handles the different environments;


### Output your data 

The main goal of the RestServer ByJG is work with the objects in your native form. The processing to the proper output like
JSON, XML or CSV is done by the platform. See below some examples:

```php
<?php
namespace Sample;

class MyClass extends \ByJG\RestServer\ServiceAbstract
{

    public function get()
    {
        // Output an array
        $array = ["field" => "value"];
        $this->getResponse()->write($array);

        // Output a stdClass
        $obj = new \stdClass();
        $obj->MyField = [ "teste1" => "value1", "test2" => [ "3", "4"]];
        $obj->OtherField = "OK";
        $this->getResponse()->write($obj);

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


### Combining HTTP Methods with ACTION

If you pass a query parameter called action you can combine the HTTP Request and the action for create a specific method for
handle this specific action. Some examples below:


| HTTP Method  | Action Parameter | Method in the class  |
|--------------|------------------|----------------------|
| GET          | -                | get()                |
| POST         | -                | post()               |
| DELETE       | -                | delete()             |
| PUT          | -                | put()                |
| GET          | someaction       | getSomeaction()      |
| POST         | someaction       | postSomeaction()     |
| PUT          | someaction       | putSomeaction()      |
| DELETE       | someactiom       | deleteSomeaction()   |


### Routing

RestServer ByJG uses the Nikic/FastRoute project to do the routing. Yout need copy the file web/app-dist.php as app.php
into the root of your public folder accessible throught the web.

The app-dist.php file looks like to:

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

\ByJG\RestServer\ServerRequestHandler::handle();
```

This file setup all routing process and handle the execution of the proper rest class.

There some pre-defined routes as you can see below but you can change it any time you want.

The pre-defined routes are:

| Pattern                                     | Exeample                        |
|---------------------------------------------|---------------------------------|
| /{module}/{action}/{id:[0-9]+}/{secondid}   | /MyNameSpace.Module/list/1/2345 |
| /{module}/{action}/{id:[0-9]+}              | /MyNameSpace.Module/list/1      |
| /{module}/{id:[0-9]+}/{action}              | /MyNameSpace.Module/1/list      |
| /{module}/{id:[0-9]+}                       | /MyNameSpace.Module/1           |
| /{module}/{action}                          | /MyNameSpace.Module/list        |
| /{module}                                   | /MyNameSpace.Module             |

All variables defined above will be available throught the $_GET. The variables module and action having a special
meaning into the system:

- **module** will be the full namespace to your class. You have to separate the namespaces with "period" (.). Do not use back slash (\);
- **action** will match a specific action inside your class

Creating the pattern:

- {variable} - Match anything and sets to "variable".
- {variable:specific} - Match only if the value is "specific" and sets to "variable"
- {variable:[0-9]+} - Match the regex "[0-9]+" and sets to variable;

all matches values can be obtained by

```php
$this->getRequest()->get('variable')
```

#### Creating Module Alias

By default you have to call in the browser the URL with the full namespace separated by points. 
Instead to pass the full namespace class you can create a module alias. 
Just add in the route.php file the follow code:

```php
<?php
\ByJG\RestServer\ServerRequestHandler::handle([ 'somealias' => 'Full.NameSpace.To.Module' ]);
```

In the example above if the parameter "module" matches with the value "somealias" will be mapped to the class "\Full\NameSpace\To\Module"

#### Creating your own routes

You can override the default route values and create your own. See an example:

```php
<?php
\ByJG\RestServer\ServerRequestHandler::handle(
    [
        'somealias' => 'Namespace.to.My.Class',
        'another' => 'Another.Namespace.To.Class'
    ], 
    [ 
        [
            "method" => ['GET'], 
            "pattern" => '/{module:somealias}/{action}/{id:[0-9]+}', 
            "handler" => \ByJG\RestServer\HandleOutput\JsonHandler::class 
        ],
        [
            "method" => ['POST'], 
            "pattern" => '/{module:another}/{action:name}/{field}', 
            "handler" => \ByJG\RestServer\HandleOutput\JsonHandler::class 
        ],
    ]
);
```

**Available Handlers**

- JsonHandler
- XmlHandler
- HtmlHandler
- JsonCleanHandler


## Install

Just type: `composer install "byjg/restserver=2.0.*"`


## Running the rest server

You need to setup your restserver to handle ALL requests to a single PHP file. Normally is "app.php" 

#### PHP Built-in server

```
php -S localhost:8080 app.php
```

#### Nginx 

```
location / {
  try_files $uri $uri/ /app.php$is_args$args;
}
```

#### Apache .htaccess

```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ ./app.php [QSA,NC,L]
```

----
[Open source ByJG](http://opensource.byjg.com)
