---
sidebar_position: 7
sidebar_label: HttpRequest and HttpResponse
---

# Processing the Request and Response

You need to implement a method, function or clousure with two parameters - Response and Request - in that order.

## The HttpRequest and HttpResponse object

The HttpRequest and the HttpResponse will always be passed to the function will process the request

The HttpRequest have all information about the request, and the HttpResponse will be used to send back
informations to the requester.

## HttpRequest

| Method                             | Description                                                            |
|------------------------------------|------------------------------------------------------------------------|
| get($var, $default)                | Get a value passed in the query string or all values if $var is null   |
| post($var, $default)               | Get a value passed by the POST Form or all values if $var is null      |
| server($var, $default)             | Get a value passed in the Request Header or all values if $var is null |
| session($var, $default)            | Get a value from session or all values if $var is null                 |
| cookie($var, $default)             | Get a value from a cookie or all values if $var is null                |
| request($var, $default)            | Get a value from the get() OR post() or all values if $var is null     |
| param($var, $default)              | Get a parameter from URL routing or all values if $var is null         |
| payload()                          | Get the raw data passed in the request body                            |
| getRequestIp()                     | Get the request IP (even if behind a proxy)                            |
| ip()                               | Static method to get the request IP                                    |
| getUserAgent()                     | Get the user agent                                                     |
| userAgent()                        | Static method to get the user agent                                    |
| getServerName()                    | Get the server name                                                    |
| getRequestServer($port, $protocol) | Get the request server name with optional port and protocol            |
| getHeader($header)                 | Get a specific header value                                            |
| getRequestPath()                   | Get the request path                                                   |
| uploadedFiles()                    | Return an instance of the UploadedFiles class                          |
| appendVars($array)                 | Append variables to the request                                        |
| routeMethod()                      | Get the HTTP method used for the current route                         |
| getRouteMetadata($key)             | Get route metadata by key or all metadata if no key is provided        |
| setRouteMetadata($routeMetadata)   | Set route metadata                                                     |

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
    
    // Get a route parameter (from URL)
    // Route: /user/{id} -> URL: /user/123
    $userId = $request->param('id');
    
    // Get information about the request
    $ip = $request->getRequestIp();
    $server = $request->getRequestServer();
    $userAgent = $request->getUserAgent();
    
    // Get uploaded files
    $files = $request->uploadedFiles();
    $uploadedFile = $files->get('myfile');
}
```

## HttpResponse

| Method                                            | Description                                             |
|---------------------------------------------------|---------------------------------------------------------|
| setSession($var, $value)                          | Set a value in the session;                             |
| removeSession($var)                               | Remove a value from the session;                        |
| addCookie($name, $value, $expire, $path, $domain) | Add a cookie;                                           |
| removeCookie($var)                                | Remove a value from the cookies;                        |
| getResponseBag()                                  | Returns the ResponseBag object;                         |
| write($object)                                    | See below;                                              |
| writeDebug($object)                               | Add information to be displayed in case of error;       |
| emptyResponse()                                   | Empty all previously write responses;                   |
| addHeader($header, $value)                        | Add an header entry;                                    |
| getHeaders()                                      | Get all headers that have been set;                     |
| setResponseCode($code, $description = null)       | Set the HTTP response code (eg. 200, 401, etc);         |
| getResponseCode()                                 | Get the current HTTP response code;                     |
| getResponseCodeDescription()                      | Get the response reason phrase (eg. "OK", "Not Found"); |

### Output your data

To output your data you *have to* use the `$response->write($object)`.
The write method supports you output a object, stdclass, array or string. The Handler object will
parse the output and setup in the proper format.

Example:

```php
function ($response, $request) {
    $response->getResponseBag()->setSerializationRule(SerializationRuleEnum::SingleObject);

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
    // Default behavior is SerializationRuleEnum::Automatic
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
using the method `getResponseBag()->setSerializationRule(SerializationRuleEnum::SingleObject)`

```php
<?php

/**
 * @param \ByJG\RestServer\HttpResponse $response
 * @param \ByJG\RestServer\HttpRequest $request
 */
function ($response, $request) {
    $response->getResponseBag()->setSerializationRule(SerializationRuleEnum::SingleObject);
    
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
    $response->write($model);
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

### Available serialization rules

The ResponseBag supports the following serialization rules:

| Enum Value                          | Description                                             |
|-------------------------------------|---------------------------------------------------------|
| SerializationRuleEnum::Automatic    | Auto-detect the best format based on inputs             |
| SerializationRuleEnum::SingleObject | Merge all outputs into a single object                  |
| SerializationRuleEnum::ObjectList   | Always return the outputs as a list of objects          |
| SerializationRuleEnum::Raw          | Return the outputs as a raw string (no JSON formatting) |

