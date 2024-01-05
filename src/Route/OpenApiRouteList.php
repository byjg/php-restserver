<?php


namespace ByJG\RestServer\Route;

use ByJG\Cache\Psr16\NoCacheEngine;
use ByJG\RestServer\Exception\OperationIdInvalidException;
use ByJG\RestServer\Exception\SchemaInvalidException;
use ByJG\RestServer\Exception\SchemaNotFoundException;
use ByJG\RestServer\OutputProcessor\BaseOutputProcessor;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\Serializer\SerializerObject;
use ByJG\Util\Uri;
use Psr\SimpleCache\CacheInterface;

class OpenApiRouteList extends RouteList
{
    protected $cache;
    protected $schema;
    protected $defaultProcessor;
    protected $overrideOutputProcessor = [];

    /**
     * @param $openApiDefinition
     * @throws SchemaInvalidException
     * @throws SchemaNotFoundException
     */
    public function __construct($openApiDefinition)
    {
        if (!file_exists($openApiDefinition)) {
            throw new SchemaNotFoundException("Schema '$openApiDefinition' not found");
        }

        $ext = substr(strrchr($openApiDefinition, "."), 1);
        $contents = file_get_contents($openApiDefinition);

        if ($ext == "json") {
            $this->schema = SerializerObject::instance($contents)->fromJson()->serialize();
        } elseif ($ext == "yaml" || $ext == "yml") {
            $this->schema = SerializerObject::instance($contents)->fromYaml()->serialize();
        } else {
            throw new SchemaInvalidException(
                "Cannot determine file type. Valids extensions are 'json', 'yaml' or 'yml'"
            );
        }

        if (!isset($this->schema['paths'])) {
            throw new SchemaInvalidException("Schema '$openApiDefinition' is invalid");
        }

        $this->cache = new NoCacheEngine();

        $this->defaultProcessor = JsonOutputProcessor::class;
    }

    /**
     * @param $method
     * @param $path
     * @param string $processor
     * @return $this
     */
    public function withOutputProcessorForRoute($method, $path, $processor)
    {
        $this->overrideOutputProcessor[strtoupper($method) . " " . $path] = $processor;
        return $this;
    }

    public function withOutputProcessorForMimeType($mimeType, $processor)
    {
        $this->overrideOutputProcessor[$mimeType] = $processor;
        return $this;
    }

    public function withDefaultProcessor($processor)
    {
        $this->defaultProcessor = $processor;
        return $this;
    }

    public function withCache(CacheInterface $cache)
    {
        $this->cache = $cache;
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
        $basePath = $this->schema["basePath"] ?? "";
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
                if (count($parts) !== 2 && count($parts) !== 4) {
                    throw new OperationIdInvalidException(
                        'OperationId needs to be in the format Namespace\\class::method or Method::Path::Namespace\\class::method'
                    );
                }

                $outputProcessor = $this->getMethodOutputProcessor($method, $basePath. $path, $properties);

                $routes[] = (new Route(strtoupper($method), $basePath . $path))
                    ->withOutputProcessor($outputProcessor)
                    ->withClass($parts[count($parts)-2], $parts[count($parts)-1]);
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

        if (isset($this->overrideOutputProcessor[$produces])) {
            return $this->overrideOutputProcessor[$produces];
        }

        return BaseOutputProcessor::getFromContentType($produces);
    }

    public function getSchema()
    {
        return $this->schema;
    }
}