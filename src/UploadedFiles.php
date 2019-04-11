<?php

namespace ByJG\RestServer;

use InvalidArgumentException;

class UploadedFiles
{
    public function count()
    {
        return count($_FILES);
    }

    public function getKeys()
    {
        return array_keys($_FILES);
    }

    public function isOk($key)
    {
        return $this->getFileByKey($key, 'error');
    }

    public function getUploadedFile($key)
    {
        return file_get_contents($this->getFileByKey($key, 'tmp_name'));
    }

    public function getFileName($key)
    {
        return $this->getFileByKey($key, 'name');
    }

    public function getFileType($key)
    {
        return $this->getFileByKey($key, 'type');
    }

    public function saveTo($key, $destinationPath, $newName = "")
    {
        if (empty($newName)) {
            $newName = $this->getFileName($key);
        }

        move_uploaded_file($this->getFileByKey($key, 'tmp_name'), $destinationPath . '/' . $newName);
    }

    public function clearTemp($key)
    {
        unlink($this->getFileByKey($key, 'tmp_name'));
    }

    private function getFileByKey($key, $property)
    {
        if (!isset($_FILES[$key])) {
            throw new InvalidArgumentException("The upload '$key' does not exists");
        }

        return $_FILES[$key][$property];
    }
}
