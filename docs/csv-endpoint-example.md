---
sidebar_position: 21
sidebar_label: CSV Endpoint Example
---

# Creating CSV File Download Endpoints

This guide shows how to create endpoints that return CSV files for download using PHP RestServer.

## Using the CsvOutputProcessor

The `CsvOutputProcessor` allows you to return data as a CSV file. When combined with the appropriate headers, the
browser will prompt the user to download the file.

## Example Implementation

### 1. Create a CSV Output Processor

First, create a custom output processor for CSV files:

```php
<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\SerializationRuleEnum;
use ByJG\Serializer\Formatter\FormatterInterface;

class CsvOutputProcessor extends BaseOutputProcessor
{
    public function __construct()
    {
        $this->contentType = "text/csv";
    }

    #[\Override]
    public function getFormatter(): FormatterInterface
    {
        return new class implements FormatterInterface {
            public function process($data): string|false
            {
                if (!is_array($data)) {
                    return false;
                }

                // Create CSV output
                $output = fopen('php://temp', 'r+');

                // Add headers if we have an associative array
                if (isset($data[0]) && is_array($data[0])) {
                    fputcsv($output, array_keys($data[0]));

                    // Add data rows
                    foreach ($data as $row) {
                        fputcsv($output, $row);
                    }
                } else {
                    // It's a single record
                    fputcsv($output, array_keys($data));
                    fputcsv($output, array_values($data));
                }

                rewind($output);
                $csvContent = stream_get_contents($output);
                fclose($output);

                return $csvContent;
            }
        };
    }
}
```

### 2. Register the CSV Output Processor

Register the CSV output processor for the MIME type:

```php
// Register the CSV output processor for the MIME type
BaseOutputProcessor::$mimeTypeOutputProcessor["text/csv"] = CsvOutputProcessor::class;
```

### 3. Create an Endpoint with the CSV Output Processor

Create an endpoint that uses the CSV output processor:

```php
<?php

namespace YourNamespace;

use ByJG\RestServer\Attributes\RouteDefinition;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\OutputProcessor\CsvOutputProcessor;

class YourController
{
    #[RouteDefinition('GET', '/export-csv', CsvOutputProcessor::class)]
    public function exportCsv(HttpResponse $response, HttpRequest $request): void
    {
        // Set headers for file download
        $filename = 'data-export-' . date('Y-m-d') . '.csv';
        $response->addHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        // Sample data for CSV
        $data = [
            [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ],
            [
                'id' => 2,
                'name' => 'Jane Smith',
                'email' => 'jane@example.com'
            ]
        ];

        // Write the data to the response
        $response->write($data);
    }
}
```

### 4. Key Points for CSV File Downloads

1. **Set the Content-Disposition header**: This tells the browser to download the file instead of displaying it:
   ```php
   $response->addHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
   ```

2. **Structure your data properly**: The CSV formatter expects an array of arrays, where each inner array represents a
   row.

3. **Dynamic filenames**: You can generate dynamic filenames based on the current date, user information, or other
   parameters:
   ```php
   $filename = 'user-data-' . $userId . '-' . date('Y-m-d') . '.csv';
   ```

4. **Raw CSV content**: If you need to create the CSV content manually, use the Raw serialization rule:
   ```php
   $response->getResponseBag()->setSerializationRule(SerializationRuleEnum::Raw);
   $csvContent = "id,name,email\n1,\"John Doe\",john@example.com\n2,\"Jane Smith\",jane@example.com";
   $response->write($csvContent);
   ```

## Complete Example

See the `CsvEndpointExample` class and `csv-example.php` file for a complete working example.

## Testing the CSV Endpoint

To test the CSV endpoint, navigate to:

```
http://your-server/csv-example.php/export-csv
```

Your browser should prompt you to download a CSV file with the sample data.
