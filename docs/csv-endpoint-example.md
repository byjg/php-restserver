---
sidebar_position: 23
sidebar_label: CSV Endpoint Example
---

# Creating CSV File Download Endpoints

This guide shows how to create endpoints that return CSV files for download using PHP RestServer.

## Using the CsvOutputProcessor

The `CsvOutputProcessor` allows you to return data as a CSV file. When combined with the appropriate headers, the
browser will prompt the user to download the file.

## Example Implementation

### 1. The CSV Output Processor

`ByJG\RestServer\OutputProcessor\CsvOutputProcessor` is built in. It sets the `text/csv` content type and formats the
response with `ByJG\Serializer\Formatter\CsvFormatter`. It is also selected automatically for `Accept: text/csv`, so
there is nothing to register.

### 2. Create an Endpoint with the CSV Output Processor

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

### 3. Key Points for CSV File Downloads

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

4. **Plain CSV content**: If you need to create the CSV content manually, use the Plain output mode:
   ```php
   $response->getResponseBody()->serializeAs(OutputMode::Plain);
   $csvContent = "id,name,email\n1,\"John Doe\",john@example.com\n2,\"Jane Smith\",jane@example.com";
   $response->write($csvContent);
   ```

## Testing the CSV Endpoint

To test the CSV endpoint, navigate to:

```
http://your-server/export-csv
```

Your browser should prompt you to download a CSV file with the sample data.
