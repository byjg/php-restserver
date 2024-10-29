<?php


namespace ByJG\RestServer\Route;

use ByJG\Cache\Psr16\NoCacheEngine;
use ByJG\RestServer\Exception\OperationIdInvalidException;
use ByJG\RestServer\Exception\SchemaInvalidException;
use ByJG\RestServer\Exception\SchemaNotFoundException;
use ByJG\RestServer\OutputProcessor\BaseOutputProcessor;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\Serializer\Serialize;
use ByJG\Util\Uri;
use Psr\SimpleCache\CacheInterface;

class OpenApiRouteList extends RouteList
{
    protected CacheInterface $cache;
    protected array $schema;
    protected string $defaultProcessor;
    protected array $overrideOutputProcessor = [];

    /**
     * @param string $openApiDefinition
     * @throws SchemaInvalidException
     * @throws SchemaNotFoundException
     */
    public function __construct(string $openApiDefinition)
    {
        if (!file_exists($openApiDefinition)) {
            throw new SchemaNotFoundException("Schema '$openApiDefinition' not found");
        }

        $ext = substr(strrchr($openApiDefinition, "."), 1);
        $contents = file_get_contents($openApiDefinition);

        if ($ext == "json") {
            $this->schema = Serialize::fromJson($contents)->toArray();
        } elseif ($ext == "yaml" || $ext == "yml") {
            $this->schema = Serialize::fromYaml($contents)->toArray();
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
     * @param string $method
     * @param string $path
     * @param string $processor
     * @return $this
     */
    public function withOutputProcessorForRoute(string $method, string $path, string $processor): static
    {
        $this->overrideOutputProcessor[strtoupper($method) . " " . $path] = $processor;
        return $this;
    }

    public function withOutputProcessorForMimeType(string $mimeType, string $processor): static
    {
        $this->overrideOutputProcessor[$mimeType] = $processor;
        return $this;
    }

    public function withDefaultProcessor(string $processor): static
    {
        $this->defaultProcessor = $processor;
        return $this;
    }

    public function withCache(CacheInterface $cache): static
    {
        $this->cache = $cache;
        return $this;
    }

    public function getRoutes(): array
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
     * @return Route[]
     *
     * @throws OperationIdInvalidException
     */
    protected function generateRoutes(): array
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

    /**
     * @param array $pathList
     * @return array
     */
    protected function sortPaths(array $pathList): array
    {
        usort($pathList, function ($left, $right) {
            if (!str_contains($left, '{') && str_contains($right, '{')) {
                return -16384;
            }
            if (str_contains($left, '{') && !str_contains($right, '{')) {
                return 16384;
            }
            if (str_contains($left, $right)) {
                return -16384;
            }
            if (str_contains($right, $left)) {
                return 16384;
            }
            return strcmp($left, $right);
        });

        return $pathList;
    }

    /**
     * @param string $method
     * @param string $path
     * @param array $properties
     * @return string
     * @throws OperationIdInvalidException
     */
    protected function getMethodOutputProcessor(string $method, string $path, array $properties): string
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

    public function getSchema(): array
    {
        return $this->schema;
    }
}