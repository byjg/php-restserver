<?php

namespace ByJG\RestServer\Route;

use Closure;

class Route
{
    protected array|string $method;
    protected string $path;
    protected ?string $outputProcessor = null;
    protected array|string|Closure|null $class = null;

    /**
     * Route constructor.
     *
     * @param array|string $method
     * @param string $path
     */
    public function __construct(array|string $method, string $path)
    {
        $this->setMethod($method);
        $this->setPath($path);
    }

    public function withOutputProcessor(string $outputProcessor): static
    {
        $this->setOutputProcessor($outputProcessor);
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

    /**
     * @return array|string
     */
    public function getMethod(): array|string
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     * @return static
     */
    protected function setMethod(array|string $method): static
    {
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
     * @return string
     */
    public function getOutputProcessor(): ?string
    {
        return $this->outputProcessor;
    }

    /**
     * @param mixed $outputProcessor
     * @return static
     */
    protected function setOutputProcessor(string $outputProcessor): static
    {
        $this->outputProcessor = $outputProcessor;
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
    public static function get(string $path): Route
    {
        return new Route('GET', $path);
    }

    /**
     * Route Factory for "POST" method
     *
     * @param string $path
     * @return static
     */
    public static function post(string $path): Route
    {
        return new Route('POST', $path);
    }

    /**
     * Route Factory for "PUT" method
     *
     * @param string $path
     * @return static
     */
    public static function put(string $path): Route
    {
        return new Route('PUT', $path);
    }

    /**
     * Route Factory for "DELETE" method
     *
     * @param string $path
     * @return static
     */
    public static function delete(string $path): Route
    {
        return new Route('DELETE', $path);
    }
}
