<?php

namespace ByJG\RestServer;

class RoutePattern
{
    /**
     * @var string|array
     */
    protected $method;

    /**
     * @var string
     */
    protected $pattern;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var \Closure|string
     */
    protected $function;

    /**
     * @var string
     */
    protected $handler;

    /**
     * RoutePattern constructor.
     *
     * @param array|string $method
     * @param string $pattern
     * @param string $handler
     * @param \Closure|string $function
     * @param string|null $class
     */
    public function __construct($method, $pattern, $handler, $function, $class = null)
    {
        $this->method = $method;
        $this->pattern = $pattern;
        $this->handler = $handler;
        $this->function = $function;
        $this->class = $class;
    }

    /**
     * @return array|string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * @return string
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * @param array|string $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @param string $pattern
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;
    }

    /**
     * @param string $handler
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param string $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * @return \Closure|string
     */
    public function getFunction()
    {
        return $this->function;
    }

    /**
     * @param \Closure|string $function
     */
    public function setFunction($function)
    {
        $this->function = $function;
    }
}
