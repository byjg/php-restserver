<?php

namespace ByJG\RestServer\Util;

class GeneralUtil
{
    public static function getExtension(string $filename, bool $requireFileExists = false): string|false
    {
        if ($requireFileExists) {
            if (!file_exists($filename)) {
                return false;
            }
        }

        $extDot = strrchr($filename, ".");
        if ($extDot === false) {
            return '';
        }

        return substr($extDot, 1);
    }
}