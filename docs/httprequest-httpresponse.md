# Processing the Request and Response

You need to implement a method, function or clousure with two parameters - Response and Request - in that order.

## The HttpRequest and HttpResponse object

The HttpRequest and the HttpResponse will always be passed to the function will process the request

The HttpRequest have all information about the request, and the HttpResponse will be used to send back
informations to the requester.

## HttpRequest

| Method             | Description                                                 |
|--------------------|-------------------------------------------------------------|
| get($var)          | Get a value passed in the query string                      |
| post($var)         | Get a value passed by the POST Form                         |
| server($var)       | Get a value passed in the Request Header (eg. HTTP_REFERER) |
| session($var)      | Get a value from session;                                   |
| cookie($var)       | Get a value from a cookie;                                  |
| request($var)      | Get a value from the get() OR post()                        |
| payload()          | Get a value passed in the request body;                     |
| getRequestIp()     | Get the request IP (even if behing a proxy);                |
| getRequestServer() | Get the request server name;                                |
| uploadedFiles()    | Return a instance of the UploadedFiles();                   |

Example:

```php
function ($response, $request) {

    // Get a value passed in the query string
    // http://localhost/?myvar=123
    $request->get('myvar');
    
    // Get a value passed by the POST Form
    // <form method="post"><input type="text" name="myvar" value="123" /></form>
    $request->post('myvar');
    
    // Get a value passed in the Request Header (eg. HTTP_REFERER)
    // http://localhost/?myvar=123
    $request->server('HTTP_REFERER');
    
    // Get a payload passed in the request body
    // {"myvar": 123}
    $json = json_decode($request->payload('myvar'));    
}
```

## HttpResponse

| Method                                            | Description                                       |
|---------------------------------------------------|---------------------------------------------------|
| setSession($var, $value)                          | Set a value in the session;                       |
| removeSession($var)                               | Remove a value from the session;                  |
| addCookie($name, $value, $expire, $path, $domain) | Add a cookie;                                     |
| removeCookie($var)                                | Remove a value from the cookies;                  |
| getResponseBag()                                  | Returns the ResponseBag object;                   |
| write($object)                                    | See below;                                        |
| writeDebug($object)                               | Add information to be displayed in case of error; |
| emptyResponse()                                   | Empty all previously write responses;             |
| addHeader($header, $value)                        | Add an header entry;                              |
| setResponseCode($value)                           | Set the HTTP response code (eg. 200, 401, etc);   |

### Output your data

To output your data you *have to* use the `$response->write($object)`.
The write method supports you output a object, stdclass, array or string. The Handler object will
parse the output and setup in the proper format.

Example:

```php
function ($response, $request) {
    $response->getResponseBag()->setSerializationRule(ResponseBag::SINGLE_OBJECT);

    $myDto = new MyDto();
    
    // The command bellow will convert the $myDto to an array
    // and output to browser according to the formatter
    $response->write($myDto);
}
```

### Chainning multiple outputs

Every `$response->write($object)` will be appended to the previous one as an array. 

For example:

```php
function ($response, $request) {
    $response->getResponseBag()->setSerializationRule(ResponseBag::SINGLE_OBJECT);

    $response->write(['status' => 1]);
    $response->write(['result' => 'ok']);
}
```

Will produce the following output:

```json
[
    {"status": 1},
    {"result": "ok"}
]
```

### Chainning multiple outputs as a single object

We can change the behavior of the output to be a single object 
using the method `getResponseBag()->setSerializationRule(ResponseBag::SINGLE_OBJECT)`

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

