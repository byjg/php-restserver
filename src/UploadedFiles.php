<?php

namespace ByJG\RestServer;

use InvalidArgumentException;

class UploadedFiles
{
    public function count(): int
    {
        return count($_FILES);
    }

    /**
     * @return string[]
     */
    public function getKeys(): array
    {
        return array_keys($_FILES);
    }

    /**
     * @psalm-param 'test' $key
     */
    public function isOk(string $key): bool
    {
        return empty($this->getErrorCode($key));
    }

    public function getErrorCode(string $key): int|null
    {
        return $this->getFileByKey($key)['error'] ?? null;
    }

    /**
     * @param string $key
     * @return false|string
     */
    public function getUploadedFile(string $key): string|false
    {
        return file_get_contents((string)$this->getFileByKey($key)['tmp_name']);
    }

    public function getFileName(string $key): string|null
    {
        return $this->getFileByKey($key)['name'] ?? null;
    }

    /**
     * @psalm-param 'test' $key
     */
    public function getFileType(string $key): int|string|null
    {
        return $this->getFileByKey($key)['type'];
    }

    public function saveTo(string $key, string $destinationPath, string $newName = ""): void
    {
        if (empty($newName)) {
            $newName = $this->getFileName($key);
        }

        move_uploaded_file((string)$this->getFileByKey($key)['tmp_name'], $destinationPath . '/' . $newName);
    }

    public function clearTemp(string $key): void
    {
        unlink((string)$this->getFileByKey($key)['tmp_name']);
    }

    public function getFileSize(string $key): int|null
    {
        return $this->getFileByKey($key)['size'] ?? null;
    }

    /**
     * @param string $key
     * @return array
     */
    private function getFileByKey(string $key): array
    {
        if (!isset($_FILES[$key])) {
            throw new InvalidArgumentException("The upload '$key' does not exists");
        }

        return $_FILES[$key];
    }
}
