---
sidebar_position: 8
sidebar_label: File Uploads
---

# File Uploads

RestServer provides a convenient `UploadedFiles` class to handle file uploads with a clean, object-oriented API. This
class simplifies working with PHP's `$_FILES` superglobal and provides useful methods for validating, accessing, and
saving uploaded files.

## Accessing Uploaded Files

Access uploaded files through the `HttpRequest::uploadedFiles()` method:

```php
<?php
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\Route\Route;

Route::post('/upload')
    ->withClosure(function (HttpResponse $response, HttpRequest $request) {
        $uploadedFiles = $request->uploadedFiles();

        // Work with uploaded files
        if ($uploadedFiles->count() > 0) {
            $response->write(['message' => 'Files uploaded successfully']);
        } else {
            $response->write(['message' => 'No files uploaded']);
        }
    });
```

## UploadedFiles Methods

### Checking File Count

Get the number of uploaded files:

```php
<?php
$count = $uploadedFiles->count();
// Returns: int - Number of files in $_FILES
```

### Getting File Keys

Get all upload field names:

```php
<?php
$keys = $uploadedFiles->getKeys();
// Returns: array - ['file1', 'file2', 'avatar', ...]

// Example: Check if specific file was uploaded
if (in_array('avatar', $keys)) {
    // Process avatar
}
```

### Validating Upload Success

Check if a file was uploaded successfully (no errors):

```php
<?php
if ($uploadedFiles->isOk('avatar')) {
    // File uploaded successfully
    $uploadedFiles->saveTo('avatar', '/path/to/uploads');
} else {
    // Upload failed
    $errorCode = $uploadedFiles->getErrorCode('avatar');
    // Handle error
}
```

### Getting Error Code

Retrieve PHP upload error code for a file:

```php
<?php
$errorCode = $uploadedFiles->getErrorCode('avatar');

// Error codes (PHP constants):
// UPLOAD_ERR_OK (0) - No error, file uploaded successfully
// UPLOAD_ERR_INI_SIZE (1) - File exceeds upload_max_filesize
// UPLOAD_ERR_FORM_SIZE (2) - File exceeds MAX_FILE_SIZE from form
// UPLOAD_ERR_PARTIAL (3) - File was only partially uploaded
// UPLOAD_ERR_NO_FILE (4) - No file was uploaded
// UPLOAD_ERR_NO_TMP_DIR (6) - Missing temporary folder
// UPLOAD_ERR_CANT_WRITE (7) - Failed to write file to disk
// UPLOAD_ERR_EXTENSION (8) - Upload stopped by PHP extension
```

### Getting File Information

#### File Name

```php
<?php
$fileName = $uploadedFiles->getFileName('avatar');
// Returns: string - Original filename from client (e.g., 'profile.jpg')
```

#### File Type

```php
<?php
$fileType = $uploadedFiles->getFileType('avatar');
// Returns: string - MIME type (e.g., 'image/jpeg', 'application/pdf')
```

:::caution
The MIME type comes from the client and can be spoofed. Always validate file types server-side by checking file
contents, not just the reported MIME type.
:::

#### File Size

```php
<?php
$fileSize = $uploadedFiles->getFileSize('avatar');
// Returns: int - File size in bytes
```

### Reading File Contents

Read the uploaded file contents into memory:

```php
<?php
$contents = $uploadedFiles->getUploadedFile('avatar');
// Returns: string|false - File contents or false on failure

if ($contents !== false) {
    // Process file contents
    // Example: Parse CSV, analyze image, etc.
}
```

:::warning
Be cautious when reading large files into memory. Use `saveTo()` for large files instead.
:::

### Saving Files

Save an uploaded file to a destination directory:

```php
<?php
// Save with original filename
$uploadedFiles->saveTo('avatar', '/path/to/uploads');
// Result: /path/to/uploads/original-filename.jpg

// Save with custom filename
$uploadedFiles->saveTo('avatar', '/path/to/uploads', 'user-123-avatar.jpg');
// Result: /path/to/uploads/user-123-avatar.jpg
```

**Parameters:**

- `$key` - Upload field name
- `$destinationPath` - Destination directory path (without trailing slash)
- `$newName` - Optional new filename (defaults to original filename)

:::tip
The destination directory must exist and be writable by the web server user.
:::

### Cleaning Up Temporary Files

Remove uploaded temporary file manually (not usually needed):

```php
<?php
$uploadedFiles->clearTemp('avatar');
// Deletes the temporary file from server
```

:::info
PHP automatically deletes temporary files at the end of request execution, so you typically don't need to call this
method.
:::

## Complete Upload Example

Here's a complete example handling file uploads with validation:

```php
<?php
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\Route\Route;

Route::post('/upload/avatar')
    ->withClosure(function (HttpResponse $response, HttpRequest $request) {
        $uploadedFiles = $request->uploadedFiles();

        // Check if file was uploaded
        if ($uploadedFiles->count() === 0) {
            $response->setResponseCode(400);
            $response->write(['error' => 'No file uploaded']);
            return;
        }

        $fileKey = 'avatar';

        // Check if specific file exists
        if (!in_array($fileKey, $uploadedFiles->getKeys())) {
            $response->setResponseCode(400);
            $response->write(['error' => 'Avatar field not found']);
            return;
        }

        // Check for upload errors
        if (!$uploadedFiles->isOk($fileKey)) {
            $errorCode = $uploadedFiles->getErrorCode($fileKey);
            $response->setResponseCode(400);
            $response->write(['error' => 'Upload failed', 'code' => $errorCode]);
            return;
        }

        // Validate file type
        $fileType = $uploadedFiles->getFileType($fileKey);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($fileType, $allowedTypes)) {
            $response->setResponseCode(400);
            $response->write(['error' => 'Invalid file type. Only images allowed.']);
            return;
        }

        // Validate file size (2MB max)
        $fileSize = $uploadedFiles->getFileSize($fileKey);
        $maxSize = 2 * 1024 * 1024; // 2MB in bytes

        if ($fileSize > $maxSize) {
            $response->setResponseCode(400);
            $response->write(['error' => 'File too large. Maximum 2MB allowed.']);
            return;
        }

        // Generate unique filename
        $originalName = $uploadedFiles->getFileName($fileKey);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $newFilename = uniqid('avatar_', true) . '.' . $extension;

        // Save file
        $uploadDir = __DIR__ . '/../uploads/avatars';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        try {
            $uploadedFiles->saveTo($fileKey, $uploadDir, $newFilename);

            $response->write([
                'success' => true,
                'filename' => $newFilename,
                'size' => $fileSize,
                'type' => $fileType,
            ]);
        } catch (\Exception $e) {
            $response->setResponseCode(500);
            $response->write(['error' => 'Failed to save file']);
        }
    });
```

## Multiple File Upload Example

Handling multiple files from a single form:

```php
<?php
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\Route\Route;

Route::post('/upload/documents')
    ->withClosure(function (HttpResponse $response, HttpRequest $request) {
        $uploadedFiles = $request->uploadedFiles();

        if ($uploadedFiles->count() === 0) {
            $response->setResponseCode(400);
            $response->write(['error' => 'No files uploaded']);
            return;
        }

        $uploadDir = __DIR__ . '/../uploads/documents';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $results = [];
        $keys = $uploadedFiles->getKeys();

        foreach ($keys as $key) {
            if (!$uploadedFiles->isOk($key)) {
                $results[$key] = [
                    'success' => false,
                    'error' => 'Upload error: ' . $uploadedFiles->getErrorCode($key),
                ];
                continue;
            }

            $filename = $uploadedFiles->getFileName($key);
            $newFilename = time() . '_' . $filename;

            try {
                $uploadedFiles->saveTo($key, $uploadDir, $newFilename);
                $results[$key] = [
                    'success' => true,
                    'filename' => $newFilename,
                    'size' => $uploadedFiles->getFileSize($key),
                ];
            } catch (\Exception $e) {
                $results[$key] = [
                    'success' => false,
                    'error' => 'Failed to save file',
                ];
            }
        }

        $response->write(['results' => $results]);
    });
```

## HTML Form Example

Example HTML form for uploading files:

```html
<!DOCTYPE html>
<html>
<head>
    <title>File Upload</title>
</head>
<body>
    <h1>Upload Avatar</h1>

    <!-- Single file upload -->
    <form action="/upload/avatar" method="POST" enctype="multipart/form-data">
        <label>
            Avatar:
            <input type="file" name="avatar" accept="image/*" required>
        </label>
        <button type="submit">Upload</button>
    </form>

    <hr>

    <h1>Upload Multiple Documents</h1>

    <!-- Multiple file upload -->
    <form action="/upload/documents" method="POST" enctype="multipart/form-data">
        <label>
            Document 1:
            <input type="file" name="doc1" required>
        </label>
        <br>
        <label>
            Document 2:
            <input type="file" name="doc2" required>
        </label>
        <br>
        <label>
            Document 3:
            <input type="file" name="doc3">
        </label>
        <br>
        <button type="submit">Upload Documents</button>
    </form>
</body>
</html>
```

:::important
Always set `enctype="multipart/form-data"` on forms that upload files.
:::

## Security Best Practices

### 1. Validate File Types

Never trust the client-reported MIME type. Validate by checking file contents:

```php
<?php
// Check file signature (magic bytes)
$fileContents = $uploadedFiles->getUploadedFile('avatar');
$finfo = new finfo(FILEINFO_MIME_TYPE);
$realMimeType = $finfo->buffer($fileContents);

if (!in_array($realMimeType, ['image/jpeg', 'image/png'])) {
    // Reject file
}
```

### 2. Limit File Size

Configure PHP and validate file size:

```php
// php.ini
// upload_max_filesize = 2M
// post_max_size = 2M

// In your code
$maxSize = 2 * 1024 * 1024; // 2MB
if ($uploadedFiles->getFileSize('avatar') > $maxSize) {
    // Reject file
}
```

### 3. Generate Unique Filenames

Never use client-provided filenames directly:

```php
<?php
// BAD: Security risk
$filename = $uploadedFiles->getFileName('avatar');
$uploadedFiles->saveTo('avatar', $uploadDir, $filename);

// GOOD: Generate safe filename
$extension = pathinfo($uploadedFiles->getFileName('avatar'), PATHINFO_EXTENSION);
$safeFilename = uniqid('upload_', true) . '.' . $extension;
$uploadedFiles->saveTo('avatar', $uploadDir, $safeFilename);
```

### 4. Store Outside Web Root

Store uploaded files outside the web-accessible directory:

```php
<?php
// BAD: Files accessible via web
$uploadDir = __DIR__ . '/../public/uploads';

// GOOD: Files not directly accessible
$uploadDir = __DIR__ . '/../storage/uploads';
// Serve files through a controller with access control
```

### 5. Scan for Malware

For production systems, consider scanning uploaded files:

```php
<?php
// Example using ClamAV
$uploadedFiles->saveTo('file', $tempDir, $tempFilename);
$result = shell_exec("clamscan " . escapeshellarg($tempDir . '/' . $tempFilename));

if (strpos($result, 'FOUND') !== false) {
    // Malware detected
    unlink($tempDir . '/' . $tempFilename);
    // Reject upload
}
```

## Error Handling

Handle common upload errors:

```php
<?php
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\HttpRequest;

function handleUploadError(int $errorCode): string
{
    return match($errorCode) {
        UPLOAD_ERR_OK => 'No error',
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE from HTML form',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'Upload stopped by PHP extension',
        default => 'Unknown error',
    };
}

Route::post('/upload')
    ->withClosure(function (HttpResponse $response, HttpRequest $request) {
        $uploadedFiles = $request->uploadedFiles();

        if (!$uploadedFiles->isOk('file')) {
            $errorCode = $uploadedFiles->getErrorCode('file');
            $errorMessage = handleUploadError($errorCode);

            $response->setResponseCode(400);
            $response->write([
                'error' => $errorMessage,
                'code' => $errorCode,
            ]);
            return;
        }

        // Process file...
    });
```

## API Reference

### UploadedFiles

#### `count(): int`

Returns the number of uploaded files.

#### `getKeys(): array`

Returns an array of all upload field names.

#### `isOk(string $key): bool`

Checks if a file was uploaded successfully without errors.

**Parameters:**

- `$key` - Upload field name

**Returns:** `true` if upload was successful, `false` otherwise

#### `getErrorCode(string $key): int|string|null`

Returns the PHP upload error code for a file.

**Parameters:**

- `$key` - Upload field name

**Returns:** Error code (0 = success, see `UPLOAD_ERR_*` constants)

#### `getUploadedFile(string $key): string|false`

Reads and returns the file contents.

**Parameters:**

- `$key` - Upload field name

**Returns:** File contents as string, or `false` on failure

#### `getFileName(string $key): int|string|null`

Returns the original filename from the client.

**Parameters:**

- `$key` - Upload field name

**Returns:** Original filename

#### `getFileType(string $key): int|string|null`

Returns the MIME type reported by the client.

**Parameters:**

- `$key` - Upload field name

**Returns:** MIME type (e.g., 'image/jpeg')

#### `saveTo(string $key, string $destinationPath, string $newName = ""): void`

Saves the uploaded file to a destination directory.

**Parameters:**

- `$key` - Upload field name
- `$destinationPath` - Destination directory path
- `$newName` - Optional new filename (defaults to original)

**Throws:** `InvalidArgumentException` if upload key doesn't exist

#### `clearTemp(string $key): void`

Manually deletes the temporary uploaded file.

**Parameters:**

- `$key` - Upload field name

#### `getFileSize(string $key): int|string|null`

Returns the file size in bytes.

**Parameters:**

- `$key` - Upload field name

**Returns:** File size in bytes

## See Also

- [HttpRequest and HttpResponse](httprequest-httpresponse.md)
- [Routes Manually](routes-manually.md)
- [Error Handler](error-handler.md)
