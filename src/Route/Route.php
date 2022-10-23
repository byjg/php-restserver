<?php

namespace ByJG\RestServer\Route;

use Closure;

class Route
{
    protected $method;
    protected $path;
    protected $outputProcessor = null;
    protected $class = null;

    /**
     * Route constructor.
     *
     * @param array|string $method
     * @param string $path
     * @param string $outputProcessor
     * @param Closure|string $class
     * @param string|null $methodName
     */
    public function __construct($method, $path)
    {
        $this->setMethod($method);
        $this->setPath($path);
    }

    public function withOutputProcessor($outputProcessor)
    {
        $this->setOutputProcessor($outputProcessor);
        return $this;
    }

    public function withClosure(\Closure $closure)
    {
        $this->setClass($closure);
        return $this;
    }

    public function withClass($class, $methodName)
    {
        $this->setClass([$class, $methodName]);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     * @return Route
     */
    protected function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param mixed $path
     * @return Route
     */
    protected function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getOutputProcessor()
    {
        return $this->outputProcessor;
    }

    /**
     * @param mixed $outputProcessor
     * @return Route
     */
    protected function setOutputProcessor($outputProcessor)
    {
        $this->outputProcessor = $outputProcessor;
    }

    /**
     * @return mixed
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param mixed $class
     * @return Route
     */
    protected function setClass($class)
    {
        $this->class = $class;
    }


    /**
     * Route Factory for "GET" method
     *
     * @param string $path
     * @param string $outputProcessor
     * @param string $class
     * @param null $methodName
     * @return Route
     */
    public static function get($path)
    {
        return new Route('GET', $path);
    }

    /**
     * Route Factory for "POST" method
     *
     * @param string $path
     * @param string $outputProcessor
     * @param string $class
     * @param null $methodName
     * @return Route
     */
    public static function post($path)
    {
        return new Route('POST', $path);
    }

    /**
     * Route Factory for "PUT" method
     *
     * @param string $path
     * @param string $outputProcessor
     * @param string $class
     * @param null $methodName
     * @return Route
     */
    public static function put($path)
    {
        return new Route('PUT', $path);
    }

    /**
     * Route Factory for "DELETE" method
     *
     * @param string $path
     * @param string $outputProcessor
     * @param string $class
     * @param null $methodName
     * @return Route
     */
    public static function delete($path)
    {
        return new Route('DELETE', $path);
    }
}
