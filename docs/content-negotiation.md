---
sidebar_position: 16
sidebar_label: Content Negotiation
---

# Content Negotiation

RestServer provides robust content negotiation capabilities, allowing your API to respond appropriately based on client
requests.

## How Content Negotiation Works

Content negotiation allows clients to request different representations (JSON, XML, etc.) of the same resource.
RestServer handles this through:

1. Request analysis (Accept headers, file extensions)
2. Output processor selection
3. Content-Type header setting in responses

## Accept Header Processing

RestServer automatically processes the `Accept` header to determine the best format to return:

```php
<?php
// Client request with Accept header
// GET /api/resource
// Accept: application/xml

// In your route handler
public function getResource(HttpRequest $request, HttpResponse $response)
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

You can also manually set the content type:

```php
<?php
public function getCustomData(HttpResponse $response)
{
    $response->setContentType('application/custom+json');
    $response->write(['data' => 'value']);
}
```

## File Extensions

RestServer can also determine the response format based on file extensions in the URL:

- `/api/resource.json` → JSON format
- `/api/resource.xml` → XML format

This works automatically without additional configuration.

## Accept-Language Support

RestServer also supports language negotiation through the `Accept-Language` header:

```php
<?php
public function getLocalizedData(HttpRequest $request, HttpResponse $response)
{
    // Get preferred language from Accept-Language header
    $language = $request->getPreferredLanguage(['en', 'fr', 'es']);
    
    // Return localized content
    $response->write([
        'greeting' => $this->getGreetingForLanguage($language)
    ]);
}
```

## Custom Content Negotiation

You can implement custom content negotiation logic:

```php
<?php
public function processRequest(HttpRequest $request, HttpResponse $response)
{
    // Check for a specific format parameter
    $format = $request->param('format');
    
    if ($format === 'csv') {
        $response->setContentType('text/csv');
        $response->write($this->convertToCsv($data));
    } else {
        // Use default format
        $response->write($data);
    }
}
```

By leveraging RestServer's content negotiation features, you can create APIs that provide data in multiple formats,
improving client flexibility and interoperability. 