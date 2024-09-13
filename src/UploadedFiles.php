<?php

namespace ByJG\RestServer;

use InvalidArgumentException;

class UploadedFiles
{
    public function count(): int
    {
        return count($_FILES);
    }

    public function getKeys(): array
    {
        return array_keys($_FILES);
    }

    public function isOk($key): bool
    {
        return empty($this->getFileByKey($key, 'error'));
    }

    public function getErrorCode(string $key): int|string|null
    {
        return $this->getFileByKey($key, 'error');
    }

    public function getUploadedFile(string $key): bool|string
    {
        return file_get_contents((string)$this->getFileByKey($key, 'tmp_name'));
    }

    public function getFileName(string $key): int|string|null
    {
        return $this->getFileByKey($key, 'name');
    }

    public function getFileType($key): int|string|null
    {
        return $this->getFileByKey($key, 'type');
    }

    public function saveTo(string $key, string $destinationPath, string $newName = ""): void
    {
        if (empty($newName)) {
            $newName = $this->getFileName($key);
        }

        move_uploaded_file((string)$this->getFileByKey($key, 'tmp_name'), $destinationPath . '/' . $newName);
    }

    public function clearTemp(string $key): void
    {
        unlink((string)$this->getFileByKey($key, 'tmp_name'));
    }

    public function getFileSize(string $key): int|string|null
    {
        return $this->getFileByKey($key, 'size');
    }

    private function getFileByKey(string $key, string $property): string|int|null
    {
        if (!isset($_FILES[$key])) {
            throw new InvalidArgumentException("The upload '$key' does not exists");
        }

        return $_FILES[$key][$property];
    }
}
