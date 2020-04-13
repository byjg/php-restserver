<?php

namespace ByJG\RestServer\Route;

use Closure;

class RoutePattern
{
    protected $method;
    protected $pattern;
    protected $outputProcessor;
    protected $class;

    /**
     * RoutePattern constructor.
     *
     * @param array|string $method
     * @param string $pattern
     * @param string $outputProcessor
     * @param Closure|string $class
     * @param string|null $methodName
     */
    public function __construct($method, $pattern, $outputProcessor, $class, $methodName = "")
    {
        $this->setMethod($method);
        $this->setPattern($pattern);
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
     * @return RoutePattern
     */
    protected function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * @param mixed $pattern
     * @return RoutePattern
     */
    protected function setPattern($pattern)
    {
        $this->pattern = $pattern;
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
     * @return RoutePattern
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
     * @return RoutePattern
     */
    protected function setClass($class)
    {
        $this->class = $class;
        return $this;
    }


    /**
     * RoutePattern Factory for "GET" method
     *
     * @param string $pattern
     * @param string $outputProcessor
     * @param string $class
     * @param null $methodName
     * @return RoutePattern
     */
    public static function get($pattern, $outputProcessor, $class, $methodName = null)
    {
        return new RoutePattern('GET', $pattern, $outputProcessor, $class, $methodName);
    }

    /**
     * RoutePattern Factory for "POST" method
     *
     * @param string $pattern
     * @param string $outputProcessor
     * @param string $class
     * @param null $methodName
     * @return RoutePattern
     */
    public static function post($pattern, $outputProcessor, $class, $methodName = null)
    {
        return new RoutePattern('POST', $pattern, $outputProcessor, $class, $methodName);
    }

    /**
     * RoutePattern Factory for "PUT" method
     *
     * @param string $pattern
     * @param string $outputProcessor
     * @param string $class
     * @param null $methodName
     * @return RoutePattern
     */
    public static function put($pattern, $outputProcessor, $class, $methodName = null)
    {
        return new RoutePattern('PUT', $pattern, $outputProcessor, $class, $methodName);
    }

    /**
     * RoutePattern Factory for "DELETE" method
     *
     * @param string $pattern
     * @param string $outputProcessor
     * @param string $class
     * @param null $methodName
     * @return RoutePattern
     */
    public static function delete($pattern, $outputProcessor, $class, $methodName = null)
    {
        return new RoutePattern('DELETE', $pattern, $outputProcessor, $class, $methodName);
    }
}
