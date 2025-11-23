<?php

namespace ByJG\RestServer\Handler;

use Throwable;

/**
 * Utility class to format exceptions as data arrays
 */
class ExceptionFormatter
{
    /**
     * Format an exception as a data array
     *
     * @param Throwable $exception
     * @param bool $includeTrace Whether to include stack trace
     * @return array{type: string, message: string, file: string, line: int, trace?: array}
     */
    public static function format(Throwable $exception, bool $includeTrace = false): array
    {
        $data = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        if ($includeTrace) {
            $data['trace'] = self::formatTrace($exception);
        }

        return $data;
    }

    /**
     * Format exception trace as array
     *
     * @param Throwable $exception
     * @return array
     */
    protected static function formatTrace(Throwable $exception): array
    {
        $frames = [];
        foreach ($exception->getTrace() as $frame) {
            $frames[] = [
                'file' => $frame['file'] ?? '[internal]',
                'line' => $frame['line'] ?? 0,
                'function' => $frame['function'] ?? '',
                'class' => $frame['class'] ?? '',
                'type' => $frame['type'] ?? '',
            ];
        }
        return $frames;
    }

    /**
     * Get simplified class name (without namespace)
     *
     * @param string $className
     * @return string
     */
    public static function getSimpleClassName(string $className): string
    {
        $parts = explode('\\', $className);
        return end($parts);
    }

    /**
     * Get beautified class name (remove Exception suffix, split camelCase)
     *
     * @param string $className
     * @return string
     */
    public static function beautifyClassName(string $className): string
    {
        $simple = self::getSimpleClassName($className);

        // Remove "Exception" suffix if present
        $simple = preg_replace('/Exception$/', '', $simple) ?? $simple;

        // Split camelCase and insert spaces before capital letters and numbers
        // e.g., "Error404" -> "Error 404", "ClassName" -> "Class Name"
        $spaced = preg_replace('/(?<=[a-z])(?=[A-Z])|(?<=[A-Za-z])(?=[0-9])/', ' ', $simple) ?? $simple;

        return $spaced;
    }
}