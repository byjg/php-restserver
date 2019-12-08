<?php

namespace ByJG\RestServer;

use ByJG\RestServer\Exception\OperationIdInvalidException;
use ByJG\RestServer\Exception\SchemaInvalidException;
use ByJG\RestServer\Exception\SchemaNotFoundException;
use ByJG\Util\Uri;

class SwaggerWrapper
{
    protected $schema;

    /**
     * @var ServerRequestHandler
     */
    protected $handler;

    /**
     * SwaggerWrapper constructor.
     * @param $swaggerJson
     * @param ServerRequestHandler $handler
     * @throws SchemaInvalidException
     * @throws SchemaNotFoundException
     */
    public function __construct($swaggerJson, $handler)
    {
        $this->handler = $handler;
        
        if (!file_exists($swaggerJson)) {
            throw new SchemaNotFoundException("Schema '$swaggerJson' not found");
        }

        $this->schema = json_decode(file_get_contents($swaggerJson), true);
        if (!isset($this->schema['paths'])) {
            throw new SchemaInvalidException("Schema '$swaggerJson' is invalid");
        }
    }

    /**
     * @return array
     * @throws OperationIdInvalidException
     */
    public function generateRoutes()
    {
        $basePath = isset($this->schema["basePath"]) ? $this->schema["basePath"] : "";
        if (empty($basePath) && isset($this->schema["servers"])) {
            $uri = new Uri($this->schema["servers"][0]["url"]);
            $basePath = $uri->getPath();
        }

        $pathList = $this->sortPaths(array_keys($this->schema['paths']));

        $routes = [];
        foreach ($pathList as $path) {
            foreach ($this->schema['paths'][$path] as $method => $properties) {
                $handler = $this->handler->getMethodHandler($method, $basePath . $path, $properties);
                if (!isset($properties['operationId'])) {
                    throw new OperationIdInvalidException('OperationId was not found');
                }

                $parts = explode('::', $properties['operationId']);
                if (count($parts) !== 2) {
                    throw new OperationIdInvalidException(
                        'OperationId needs to be in the format Namespace\\class::method'
                    );
                }

                $routes[] = new RoutePattern(
                    strtoupper($method),
                    $basePath . $path,
                    $handler,
                    $parts[1],
                    $parts[0]
                );
            }
        }

        return $routes;
    }

    protected function sortPaths($pathList)
    {
        usort($pathList, function ($left, $right) {
            if (strpos($left, '{') === false && strpos($right, '{') !== false) {
                return -16384;
            }
            if (strpos($left, '{') !== false && strpos($right, '{') === false) {
                return 16384;
            }
            if (strpos($left, $right) !== false) {
                return -16384;
            }
            if (strpos($right, $left) !== false) {
                return 16384;
            }
            return strcmp($left, $right);
        });

        return $pathList;
    }
}