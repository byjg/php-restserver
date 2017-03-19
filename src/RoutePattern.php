<?php

namespace ByJG\RestServer;

use ByJG\RestServer\HandleOutput\JsonHandler;

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
    protected $handler;

    /**
     * RoutePattern constructor.
     *
     * @param array|string $method
     * @param string $pattern
     * @param string $handler
     */
    public function __construct($method, $pattern, $handler = null)
    {
        $this->method = $method;
        $this->pattern = $pattern;

        if (is_null($handler)) {
            $handler = JsonHandler::class;
        }
        $this->handler = $handler;
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
}
