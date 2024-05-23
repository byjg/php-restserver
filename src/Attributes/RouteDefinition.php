<?php

namespace ByJG\RestServer\Attributes;

use Attribute;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\Route\Route;

#[Attribute(Attribute::TARGET_METHOD)]
class RouteDefinition
{
    protected string $method;
    protected string $path;
    protected ?string $outputProcessor;

    public function __construct(string $method = 'GET', string $path = "/", string $outputProcessor = JsonOutputProcessor::class)
    {
        $this->method = $method;
        $this->path = $path;
        $this->outputProcessor = $outputProcessor;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function createRoute(string $class, string $methodName): Route
    {
        return (new Route($this->method, $this->path))
            ->withClass($class, $methodName)
            ->withOutputProcessor($this->outputProcessor);
    }

}