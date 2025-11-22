<?php

namespace ByJG\RestServer\Route;

use ByJG\WebRequest\HttpMethod;
use Closure;

class Route
{
    protected array|string $method;
    protected string $path;
    protected string|array|null $outputProcessor = null;
    protected bool $strict = false;
    protected array|string|Closure|null $class = null;
    protected array $metadata = [];

    /**
     * Route constructor.
     *
     * @param HttpMethod|array|string $method
     * @param string $path
     */
    public function __construct(HttpMethod|array|string $method, string $path)
    {
        $this->setMethod($method);
        $this->setPath($path);
    }

    public static function create(HttpMethod|array|string $method, string $path): static
    {
        return new static($method, $path);
    }

    public function withOutputProcessor(string|array $outputProcessor, bool $strict = false): static
    {
        $this->setOutputProcessor($outputProcessor, $strict);
        return $this;
    }

    public function withClosure(Closure $closure): static
    {
        $this->setClass($closure);
        return $this;
    }

    public function withClass(string $class, string $methodName): static
    {
        $this->setClass([$class, $methodName]);
        return $this;
    }

    public function withMetadata(array $metadata): Route
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @return array|string
     */
    public function getMethod(): array|string
    {
        return $this->method;
    }

    public function isOutputContentTypeStrict(): bool
    {
        return $this->strict;
    }

    /**
     * @param mixed $method
     * @return static
     */
    protected function setMethod(HttpMethod|array|string $method): static
    {
        if ($method instanceof HttpMethod) {
            $method = $method->value;
        }
        $this->method = $method;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param mixed $path
     * @return static
     */
    protected function setPath(string $path): static
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return string|array|null
     */
    public function getOutputProcessor(): string|array|null
    {
        return $this->outputProcessor;
    }

    /**
     * @param mixed $outputProcessor
     * @param bool $strict
     * @return static
     */
    protected function setOutputProcessor(string|array $outputProcessor, bool $strict): static
    {
        $this->outputProcessor = $outputProcessor;
        $this->strict = $strict;
        return $this;
    }

    /**
     * @return array|string|Closure|null
     */
    public function getClass(): array|string|Closure|null
    {
        return $this->class;
    }

    /**
     * @param mixed $class
     * @return static
     */
    protected function setClass(array|string|Closure $class): static
    {
        $this->class = $class;
        return $this;
    }


    /**
     * Route Factory for "GET" method
     *
     * @param string $path
     * @return static
     */
    public static function get(string $path): static
    {
        return new static('GET', $path);
    }

    /**
     * Route Factory for "POST" method
     *
     * @param string $path
     * @return static
     */
    public static function post(string $path): static
    {
        return new static('POST', $path);
    }

    /**
     * Route Factory for "PUT" method
     *
     * @param string $path
     * @return static
     */
    public static function put(string $path): static
    {
        return new static('PUT', $path);
    }

    /**
     * Route Factory for "DELETE" method
     *
     * @param string $path
     * @return static
     */
    public static function delete(string $path): static
    {
        return new static('DELETE', $path);
    }
}
