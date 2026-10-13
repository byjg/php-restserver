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

| Method                             | Description                                                                 |
|------------------------------------|-----------------------------------------------------------------------------|
| query($var, $default)              | Get a value from the query string ($_GET) or all values if $var is null     |
| body($var, $default)               | Get a value from the request body ($_POST) or all values if $var is null    |
| server($var, $default)             | Get a value from $_SERVER or all values if $var is null                     |
| session($var, $default)            | Get a value from the session ($_SESSION) or all values if $var is null      |
| cookie($var, $default)             | Get a value from the cookies ($_COOKIE) or all values if $var is null       |
| input($var, $default)              | Get a value from any source (query, body, server, session, cookie merged)   |
| attribute($var, $default)          | Get a value injected by middleware or the framework (not URL or HTTP input) |
| payload()                          | Get the raw request body (php://input)                                      |
| getRequestIp()                     | Get the client IP address (checks proxy headers before REMOTE_ADDR)         |
| ip()                               | Static method to get the client IP                                          |
| getUserAgent()                     | Get the user agent                                                          |
| userAgent()                        | Static method to get the user agent                                         |
| getServerName()                    | Get the server name                                                         |
| getRequestServer($port, $protocol) | Get the server name, optionally with port and/or protocol                   |
| getHeader($header)                 | Get a specific request header value                                         |
| getRequestPath()                   | Get the request path                                                        |
| uploadedFiles()                    | Return an instance of the UploadedFiles class                               |
| addAttributes($array)              | Bulk-add values to the attribute bag (middleware/framework use)             |
| routeMethod()                      | Get the HTTP method used for the current route                              |
| getRouteMetadata($key)             | Get route metadata by key or all metadata if no key is provided             |
| setRouteMetadata($routeMetadata)   | Set route metadata                                                          |

### Typed getters

Each source also has typed variants that always return `?string` or `?array`:

| Method                          | Source                                           |
|---------------------------------|--------------------------------------------------|
| queryString($key, $default)     | $_GET                                            |
| queryArray($key, $default)      | $_GET                                            |
| bodyString($key, $default)      | $_POST                                           |
| bodyArray($key, $default)       | $_POST                                           |
| serverString($key, $default)    | $_SERVER                                         |
| cookieString($key, $default)    | $_COOKIE                                         |
| sessionString($key, $default)   | $_SESSION                                        |
| attributeString($key, $default) | attribute bag (route params + middleware values) |
| inputString($key, $default)     | any source                                       |

Example:

```php
function ($response, $request) {

    // Get a value from the query string
    // http://localhost/?myvar=123
    $request->query('myvar');

    // Typed variant — guaranteed string or null
    $myvar = $request->queryString('myvar');

    // Get a value from the POST body
    // <form method="post"><input type="text" name="myvar" value="123" /></form>
    $request->body('myvar');

    // Get a value from $_SERVER (eg. HTTP_REFERER)
    $request->server('HTTP_REFERER');

    // Get the raw request body
    // {"myvar": 123}
    $json = json_decode($request->payload());

    // Get a URL route placeholder (Route: /user/{id} -> URL: /user/123)
    $userId = $request->attributeString('id');

    // Get a middleware-injected attribute value
    $jwtSub = $request->attributeString('jwt.sub');

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
| getResponseBody()                                 | Returns the ResponseBody object;                        |
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
    $response->getResponseBody()->serializeAs(OutputMode::SingleObject);

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
    // Default behavior is OutputMode::Automatic
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
using the method `getResponseBody()->serializeAs(OutputMode::SingleObject)`

```php
<?php

/**
 * @param \ByJG\RestServer\HttpResponse $response
 * @param \ByJG\RestServer\HttpRequest $request
 */
function ($response, $request) {
    $response->getResponseBody()->serializeAs(OutputMode::SingleObject);
    
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

### Available output modes

The ResponseBody supports the following output modes:

| Enum Value               | Description                                             |
|--------------------------|---------------------------------------------------------|
| OutputMode::Automatic    | Auto-detect the best format based on inputs             |
| OutputMode::SingleObject | Merge all outputs into a single object                  |
| OutputMode::ObjectList   | Always return the outputs as a list of objects          |
| OutputMode::Plain        | Return the outputs as a plain string (no serialization) |

