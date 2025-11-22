<?php


namespace ByJG\RestServer\Route;

use ByJG\Cache\Psr16\NoCacheEngine;
use ByJG\RestServer\Exception\OperationIdInvalidException;
use ByJG\RestServer\Exception\SchemaInvalidException;
use ByJG\RestServer\Exception\SchemaNotFoundException;
use ByJG\RestServer\OutputProcessor\BaseOutputProcessor;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\Util\GeneralUtil;
use ByJG\Serializer\Serialize;
use ByJG\Util\Uri;
use ByJG\WebRequest\HttpMethod;
use Override;
use Psr\SimpleCache\CacheInterface;

class OpenApiRouteList extends RouteList
{
    const OPENAPI_BASE_PATH = 'openapi_base_path';
    const OPENAPI_PATH = 'openapi_path';

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
        $ext = GeneralUtil::getExtension($openApiDefinition, requireFileExists: true);
        if ($ext === false) {
            throw new SchemaNotFoundException("Schema '$openApiDefinition' not found");
        }

        $contents = file_get_contents($openApiDefinition);
        if ($contents === false) {
            throw new SchemaNotFoundException("Failed to read schema '$openApiDefinition'");
        }

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

    #[Override]
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

                $route = Route::create(HttpMethod::from(strtoupper($method)), $basePath . $path);
                if ($outputProcessor !== null) {
                    $route = $route->withOutputProcessor($outputProcessor);
                }
                $routes[] = $route
                    ->withClass($parts[count($parts) - 2], $parts[count($parts) - 1])
                    ->withMetadata([
                        self::OPENAPI_BASE_PATH => $basePath,
                        self::OPENAPI_PATH => $path
                    ]);
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
     * @return array|string|null
     * @throws OperationIdInvalidException
     */
    protected function getMethodOutputProcessor(string $method, string $path, array $properties): array|string|null
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

        $returnOutputProcessor = [];
        foreach ($produces as $produce) {
            if (isset($this->overrideOutputProcessor[$produce])) {
                return $this->overrideOutputProcessor[$produce];
            }

            $returnOutputProcessor[$produce] = BaseOutputProcessor::getFromContentType($produce);
        }

        $returnOutputProcessor = array_values($returnOutputProcessor);
        return (count($returnOutputProcessor) === 1) ? $returnOutputProcessor[0] : $returnOutputProcessor;
    }

    public function getSchema(): array
    {
        return $this->schema;
    }

    #[Override]
    public function getRoute(string $method, string $path): ?Route
    {
        if (empty($this->routes)) {
            $this->getRoutes();
        }

        return parent::getRoute($method, $path);
    }
}
