<?php

namespace ByJG\RestServer;

use InvalidArgumentException;

class UploadedFiles
{
    /**
     * @psalm-return int<0, max>
     */
    public function count(): int
    {
        return count($_FILES);
    }

    /**
     * @return string[]
     *
     * @psalm-return list<non-empty-string>
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
        return empty($this->getFileByKey($key, 'error'));
    }

    public function getErrorCode(string $key): int|string|null
    {
        return $this->getFileByKey($key, 'error');
    }

    /**
     * @return false|string
     */
    public function getUploadedFile(string $key): string|false
    {
        return file_get_contents((string)$this->getFileByKey($key, 'tmp_name'));
    }

    public function getFileName(string $key): int|string|null
    {
        return $this->getFileByKey($key, 'name');
    }

    /**
     * @psalm-param 'test' $key
     */
    public function getFileType(string $key): int|string|null
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

    /**
     * @param string $key
     * @param string $property
     * @return int|string
     *
     */
    private function getFileByKey(string $key, string $property): string|int
    {
        if (!isset($_FILES[$key])) {
            throw new InvalidArgumentException("The upload '$key' does not exists");
        }

        return $_FILES[$key][$property];
    }
}
