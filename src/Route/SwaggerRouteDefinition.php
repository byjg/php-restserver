<?php


namespace ByJG\RestServer\Route;

use ByJG\Cache\Psr16\NoCacheEngine;
use ByJG\RestServer\Exception\OperationIdInvalidException;
use ByJG\RestServer\Exception\SchemaInvalidException;
use ByJG\RestServer\Exception\SchemaNotFoundException;
use ByJG\RestServer\OutputProcessor\BaseOutputProcessor;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\Util\Uri;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class SwaggerRouteDefinition extends RouteDefinition
{
    protected $cache;
    protected $schema;
    protected $defaultProcessor;
    protected $overrideOutputProcessor = [];

    /**
     * @param $swaggerJson
     * @param string $defaultProcessor
     * @param CacheInterface|null $cache
     * @throws InvalidArgumentException
     * @throws OperationIdInvalidException
     * @throws SchemaInvalidException
     * @throws SchemaNotFoundException
     */
    public function __construct($swaggerJson, $defaultProcessor = JsonOutputProcessor::class, CacheInterface $cache = null)
    {
        if (!file_exists($swaggerJson)) {
            throw new SchemaNotFoundException("Schema '$swaggerJson' not found");
        }

        $this->schema = json_decode(file_get_contents($swaggerJson), true);
        if (!isset($this->schema['paths'])) {
            throw new SchemaInvalidException("Schema '$swaggerJson' is invalid");
        }

        if (is_null($this->cache)) {
            $this->cache = new NoCacheEngine();
        }

        $this->defaultProcessor = $defaultProcessor;
    }

    /**
     * @param $method
     * @param $path
     * @param string $processor
     * @return $this
     */
    public function withOutputProcessorFor($method, $path, $processor)
    {
        $this->overrideOutputProcessor[strtoupper($method) . " " . $path] = $processor;
        return $this;
    }

    public function getRoutes()
    {
        if (empty($this->routes)) {
            $routePattern = $this->cache->get('SERVERHANDLERROUTES', false);
            if ($routePattern === false) {
                $routePattern = $this->generateRoutes();
                $this->cache->set('SERVERHANDLERROUTES', $routePattern);
            }
            $this->setRoutes($routePattern);
        }

        return parent::getRoutes();
    }

    /**
     * @return array
     * @throws OperationIdInvalidException
     */
    protected function generateRoutes()
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
                if (!isset($properties['operationId'])) {
                    throw new OperationIdInvalidException('OperationId was not found');
                }

                $parts = explode('::', $properties['operationId']);
                if (count($parts) !== 2) {
                    throw new OperationIdInvalidException(
                        'OperationId needs to be in the format Namespace\\class::method'
                    );
                }

                $outputProcessor = $this->getMethodOutputProcessor($method, $basePath. $path, $properties);

                $routes[] = new RoutePattern(
                    strtoupper($method),
                    $basePath . $path,
                    $outputProcessor,
                    $parts[0],
                    $parts[1]
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

    /**
     * @param $method
     * @param $path
     * @param $properties
     * @return string
     * @throws OperationIdInvalidException
     */
    protected function getMethodOutputProcessor($method, $path, $properties)
    {
        $key = strtoupper($method) . " " . $path;
        if (isset($this->overrideOutputProcessor[$key])) {
            return $this->overrideOutputProcessor[$key];
        }

        $produces = null;
        if (isset($properties['produces'])) {
            $produces = (array) $properties['produces'];
        }
        if (empty($produces) && isset($properties["responses"]["200"]["content"])) {
            $produces = array_keys($properties["responses"]["200"]["content"]);
        }

        if (empty($produces)) {
            return $this->defaultProcessor;
        }

        $produces = $produces[0];

        return BaseOutputProcessor::getFromContentType($produces);
    }
}