---
sidebar_position: 21
sidebar_label: Content Negotiation
---

# Content Negotiation

RestServer provides robust content negotiation capabilities, allowing your API to respond appropriately based on client
requests.

## How Content Negotiation Works

Content negotiation allows clients to request different representations (JSON, XML, etc.) of the same resource.
RestServer handles this through:

1. Request analysis (Accept header)
2. Output processor selection
3. Content-Type header setting in responses

## Accept Header Processing

RestServer processes the `Accept` header to determine the format to return when the route does not set an output
processor, or sets a list of them (e.g. an OpenAPI route that declares several content types). A route with a single
output processor always uses it. The types in `Accept` are tried in order and the first supported one wins
(`text/xml`, `application/xml`, `text/csv`, `text/html`, `application/json`, `text/plain` or `*/*`). When none is
supported, the server default output processor is used, or `Error406Exception` is thrown if there is none.

```php
<?php
// Client request with Accept header
// GET /api/resource
// Accept: application/xml

// In your route handler
public function getResource(HttpResponse $response, HttpRequest $request)
{
    // RestServer will detect the Accept header and use the appropriate output processor
    $response->write(['data' => 'value']);
    
    // The response will be formatted as XML with the appropriate Content-Type header
}
```

## Output Processors

RestServer uses output processors to transform your data into different formats (JSON, XML, CSV, etc.). For complete
documentation on available output processors and how to create custom ones, see [Output Processors](outprocessor.md).

This section focuses specifically on how content negotiation works with Accept headers and determines which output
processor to use.

## Content Type Header

RestServer automatically sets the appropriate `Content-Type` header based on the output processor:

- `application/json` for JSON responses
- `application/xml` for XML responses
- Custom content types as defined by your output processors

You can override this at runtime by adding your own `Content-Type` header, using one of the content types listed above.
When you do so, `Server`
will detect the header (`HttpResponse::addHeader('Content-Type', ...)`) and automatically re-initialize the matching
output processor before the response is serialized. This makes it possible to start with an XML route and switch to JSON
for a particular response (or vice-versa).

```php
<?php
public function getCustomData(HttpResponse $response)
{
    // Forces the handler to serialize using the JSON processor, even if the route default differs
    $response->addHeader('Content-Type', 'application/json');
    $response->write(['data' => 'value']);
}
```

:::tip Runtime overrides
The sample application in `public/app-dist.php` includes `/testoverride/*` routes that demonstrate switching between
JSON and XML by setting the header inside the route closure.
:::

## Accept-Language

RestServer does not negotiate the language. Read the header yourself with
`$request->header('Accept-Language')`.

## Custom Content Negotiation

You can implement custom content negotiation logic:

```php
<?php
public function processRequest(HttpResponse $response, HttpRequest $request)
{
    // Check for a specific format parameter
    $format = $request->query('format');
    
    if ($format === 'csv') {
        $response->addHeader('Content-Type', 'text/csv');
        $response->write($this->convertToCsv($data));
        return;
    }

    // Use default format
    $response->write($data);
}
```

By leveraging RestServer's content negotiation features, you can create APIs that provide data in multiple formats,
improving client flexibility and interoperability. 
