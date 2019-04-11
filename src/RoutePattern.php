<?php

namespace ByJG\RestServer;

use Closure;

class RoutePattern
{
    /**
     * @var string[]
     */
    protected $properties = [];

    /**
     * RoutePattern constructor.
     *
     * @param array|string $method
     * @param string $pattern
     * @param string $handler
     * @param Closure|string $function
     * @param string|null $class
     */
    public function __construct($method, $pattern, $handler, $function, $class = null)
    {
        $this->properties['method'] = $method;
        $this->properties['pattern'] = $pattern;
        $this->properties['handler'] = $handler;
        $this->properties['function'] = $function;
        $this->properties['class'] = $class;
    }

    /**
     * @param string $property
     * @return string
     */
    public function properties($property = null)
    {
        if (empty($property)) {
            return $this->properties;
        }

        return $this->properties[$property];
    }

    /**
     * RoutePattern Factory for "GET" method
     *
     * @param string $pattern
     * @param string $function
     * @param string $class
     * @param string $handler
     * @return RoutePattern
     */
    public static function get($pattern, $function, $class = null, $handler = null)
    {
        return new RoutePattern('GET', $pattern, $handler, $function, $class);
    }

    /**
     * RoutePattern Factory for "POST" method
     *
     * @param string $pattern
     * @param string $function
     * @param string $class
     * @param string $handler
     * @return RoutePattern
     */
    public static function post($pattern, $function, $class = null, $handler = null)
    {
        return new RoutePattern('POST', $pattern, $handler, $function, $class);
    }

    /**
     * RoutePattern Factory for "PUT" method
     *
     * @param string $pattern
     * @param string $function
     * @param string $class
     * @param string $handler
     * @return RoutePattern
     */
    public static function put($pattern, $function, $class = null, $handler = null)
    {
        return new RoutePattern('PUT', $pattern, $handler, $function, $class);
    }

    /**
     * RoutePattern Factory for "DELETE" method
     *
     * @param string $pattern
     * @param string $function
     * @param string $class
     * @param string $handler
     * @return RoutePattern
     */
    public static function delete($pattern, $function, $class = null, $handler = null)
    {
        return new RoutePattern('DELETE', $pattern, $handler, $function, $class);
    }
}
