# Auto-Generate from an OpenApi definition

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

$routeDefinition = new OpenApiRouteList(__DIR__ . '/swagger.json');

$restServer = new HttpRequestHandler();
$restServer->handle($routeDefinition);
```

## Customizing the Handlers

The OpenApi specificy the proper output encoding. 
We can override the default output processor for a specific route or mime type.

### Mime Type

```php
<?php
$routeDefinition = new \ByJG\RestServer\Route\OpenApiRouteList(__DIR__ . '/swagger.json');
$routeDefinition->withOutputProcessorForMimeType(
    "application/json",
    \ByJG\RestServer\OutputProcessor\JsonCleanOutputProcessor::class
);
```

### Specific Route

```php
<?php
$routeDefinition = new \ByJG\RestServer\Route\OpenApiRouteList(__DIR__ . '/swagger.json');
$routeDefinition->withOutputProcessorForRoute(
    "GET",
    "/pet/{petId}",
    \ByJG\RestServer\OutputProcessor\JsonOutputProcessor::class
);
```
