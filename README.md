# PHP Rest Server

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
namespace Sample

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
http://yourserver.com/1.0/Sample.MyClass/1234.json     # Or xml or csv
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
* getRequestIP() - Get the client request IP. It handles proxies and firewalls to get the corret IP;
* getRequestServer() - Get the sername. It handles the different environments;


### Output your data 

The main goal of the RestServer ByJG is work with the objects in your native form. The processing to the proper output like
JSON, XML or CSV is done by the platform. See below some examples:

```php
namespace Sample

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
        //    - with getter and setters
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


### Combining HTTP Method with ACTION

If you pass a parameter called action you can combine the HTTP Request and the action for create a specific method for
handle this specific action. Some examples below:


| HTTP Method  | Action      | Method in the class  |
|--------------|-------------|----------------------|
| GET          | account     | getAccount()         |
| POST         | account     | postAccount()        |
| PUT          | account     | putAccount()         |
| DELETE       | account     | deleteAccount()      |
| PUT          | -           | put()                |


### Routing


## Install

Just type: `composer install "byjg/restserver=~1.0"`

## Running Tests

