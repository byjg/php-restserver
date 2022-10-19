<?php

namespace ByJG\RestServer\Route;

use Closure;

class Route
{
    protected $method;
    protected $path;
    protected $outputProcessor;
    protected $class;

    /**
     * Route constructor.
     *
     * @param array|string $method
     * @param string $path
     * @param string $outputProcessor
     * @param Closure|string $class
     * @param string|null $methodName
     */
    public function __construct($method, $path, $outputProcessor, $class, $methodName = "")
    {
        $this->setMethod($method);
        $this->setPath($path);
        $this->setOutputProcessor($outputProcessor);
        
        if ($class instanceof \Closure) {
            $this->setClass($class);
        } else {
            $this->setClass([$class, $methodName]);
        }
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
        return $this;
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
        return $this;
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
        return $this;
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
        return $this;
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
    public static function get($path, $outputProcessor, $class, $methodName = null)
    {
        return new Route('GET', $path, $outputProcessor, $class, $methodName);
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
    public static function post($path, $outputProcessor, $class, $methodName = null)
    {
        return new Route('POST', $path, $outputProcessor, $class, $methodName);
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
    public static function put($path, $outputProcessor, $class, $methodName = null)
    {
        return new Route('PUT', $path, $outputProcessor, $class, $methodName);
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
    public static function delete($path, $outputProcessor, $class, $methodName = null)
    {
        return new Route('DELETE', $path, $outputProcessor, $class, $methodName);
    }
}
