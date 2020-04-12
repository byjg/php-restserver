<?php

namespace ByJG\RestServer;

use ByJG\Cache\Psr16\NoCacheEngine;
use ByJG\RestServer\Exception\ClassNotFoundException;
use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\Exception\Error405Exception;
use ByJG\RestServer\Exception\Error520Exception;
use ByJG\RestServer\Exception\InvalidClassException;
use ByJG\RestServer\Exception\OperationIdInvalidException;
use ByJG\RestServer\Exception\SchemaInvalidException;
use ByJG\RestServer\Exception\SchemaNotFoundException;
use ByJG\RestServer\OutputProcessor\OutputProcessorInterface;
use ByJG\RestServer\OutputProcessor\HtmlOutputProcessor;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\OutputProcessor\XmlOutputProcessor;
use Closure;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use function FastRoute\simpleDispatcher;

class ServerRequestHandler
{
    const OK = "OK";
    const METHOD_NOT_ALLOWED = "NOT_ALLOWED";
    const NOT_FOUND = "NOT FOUND";

    protected $routes = null;

    protected $defaultOutputProcessor = null;

    protected $mimeTypeOutputProcessor = [
        "text/xml" => XmlOutputProcessor::class,
        "application/xml" => XmlOutputProcessor::class,
        "text/html" => HtmlOutputProcessor::class,
        "application/json" => JsonOutputProcessor::class
    ];

    protected $pathOutputProcessor = [];

    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * @param RoutePattern[] $routes
     */
    public function setRoutes($routes)
    {
        foreach ((array)$routes as $route) {
            $this->addRoute($route);
        }
    }

    /**
     * @param RoutePattern $route
     */
    public function addRoute(RoutePattern $route)
    {
        if (is_null($this->routes)) {
            $this->routes = [];
        }
        $this->routes[] = $route;
    }

    /**
     * @return OutputProcessorInterface
     */
    public function getDefaultOutputProcessor()
    {
        if (empty($this->defaultOutputProcessor)) {
            $this->defaultOutputProcessor = new JsonOutputProcessor();
        }
        return $this->defaultOutputProcessor;
    }

    /**
     * @param OutputProcessorInterface $defaultOutputProcessor
     */
    public function setDefaultOutputProcessor(OutputProcessorInterface $defaultOutputProcessor)
    {
        $this->defaultOutputProcessor = $defaultOutputProcessor;
    }

    /**
     * @throws ClassNotFoundException
     * @throws Error404Exception
     * @throws Error405Exception
     * @throws Error520Exception
     * @throws InvalidClassException
     */
    protected function process()
    {
        // Initialize ErrorHandler with default error handler
        ErrorHandler::getInstance()->register();

        // Get the URL parameters
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $queryStr);

        // Generic Dispatcher for RestServer
        $dispatcher = simpleDispatcher(function (RouteCollector $r) {

            foreach ($this->getRoutes() as $route) {
                $r->addRoute(
                    $route->properties('method'),
                    $route->properties('pattern'),
                    [
                        "handler" => $route->properties('handler'),
                        "class" => $route->properties('class'),
                        "function" => $route->properties('function')
                    ]
                );
            }
        });

        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

        // Default Handler for errors
        $this->getDefaultOutputProcessor()->writeHeader();
        ErrorHandler::getInstance()->setHandler($this->getDefaultOutputProcessor()->getErrorHandler());

        // Processing
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new Error404Exception("404 Route '$uri' Not found");

            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new Error405Exception('405 Method Not Allowed');

            case Dispatcher::FOUND:
                // ... 200 Process:
                $vars = array_merge($routeInfo[2], $queryStr);

                // Instantiate the Service Handler
                $handlerRequest = $routeInfo[1];

                // Execute the request
                $handler = !empty($handlerRequest['handler']) ? $handlerRequest['handler'] : $this->getDefaultOutputProcessor();
                $this->executeRequest(
                    new $handler(),
                    $handlerRequest['class'],
                    $handlerRequest['function'],
                    $vars
                );

                break;

            default:
                throw new Error520Exception('Unknown');
        }
    }

    /**
     * @param OutputProcessorInterface $handler
     * @param string $class
     * @param string $function
     * @param array $vars
     * @throws ClassNotFoundException
     * @throws InvalidClassException
     */
    protected function executeRequest($handler, $class, $function, $vars)
    {
        // Setting Default Headers and Error Handler
        $handler->writeHeader();
        ErrorHandler::getInstance()->setHandler($handler->getErrorHandler());

        // Set all default values
        foreach (array_keys($vars) as $key) {
            $_REQUEST[$key] = $_GET[$key] = $vars[$key];
        }

        // Create the Request and Response methods
        $request = new HttpRequest($_GET, $_POST, $_SERVER, isset($_SESSION) ? $_SESSION : [], $_COOKIE);
        $response = new HttpResponse();

        // Process Closure
        if ($function instanceof Closure) {
            $function($response, $request);
            echo $handler->processResponse($response);
            return;
        }

        // Process Class::Method()
        if (!class_exists($class)) {
            throw new ClassNotFoundException("Class '$class' defined in the route is not found");
        }
        $instance = new $class();
        if (!method_exists($instance, $function)) {
            throw new InvalidClassException("There is no method '$class::$function''");
        }
        $instance->$function($response, $request);
        $handler->processResponse($response);
    }

    /**
     * Handle the ROUTE (see web/app-dist.php)
     *
     * @param RoutePattern[]|null $routePattern
     * @param bool $outputBuffer
     * @param bool $session
     * @return bool|void
     * @throws ClassNotFoundException
     * @throws Error404Exception
     * @throws Error405Exception
     * @throws Error520Exception
     * @throws InvalidClassException
     */
    public function handle($routePattern = null, $outputBuffer = true, $session = true)
    {
        if ($outputBuffer) {
            ob_start();
        }
        if ($session) {
            session_start();
        }

        /**
         * @var ServerRequestHandler
         */
        $this->setRoutes($routePattern);

        // --------------------------------------------------------------------------
        // Check if script exists or if is itself
        // --------------------------------------------------------------------------

        $debugBacktrace =  debug_backtrace();
        if (!empty($_SERVER['SCRIPT_FILENAME'])
            && file_exists($_SERVER['SCRIPT_FILENAME'])
            && basename($_SERVER['SCRIPT_FILENAME']) !== basename($debugBacktrace[0]['file'])
        ) {
            $file = $_SERVER['SCRIPT_FILENAME'];
            if (strrchr($file, '.') === ".php") {
                require_once($file);
            } else {
                if (!defined("RESTSERVER_TEST")) {
                    header("Content-Type: " . $this->mimeContentType($file));
                }

                echo file_get_contents($file);
            }
            return true;
        }

        return $this->process();
    }

    /**
     * Get the Mime Type based on the filename
     *
     * @param string $filename
     * @return string
     * @throws Error404Exception
     */
    public function mimeContentType($filename)
    {

        $mimeTypes = array(
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',
            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',
            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',
            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );

        if (!file_exists($filename)) {
            throw new Error404Exception();
        }

        $ext = substr(strrchr($filename, "."), 1);
        if (array_key_exists($ext, $mimeTypes)) {
            return $mimeTypes[$ext];
        } elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            return $mimetype;
        } else {
            return 'application/octet-stream';
        }
    }

    /**
     * @param $swaggerJson
     * @param CacheInterface|null $cache
     * @throws SchemaInvalidException
     * @throws SchemaNotFoundException
     * @throws OperationIdInvalidException
     * @throws InvalidArgumentException
     */
    public function setRoutesSwagger($swaggerJson, CacheInterface $cache = null)
    {
        $swaggerWrapper = new SwaggerWrapper($swaggerJson, $this);

        if (is_null($cache)) {
            $cache = new NoCacheEngine();
        }

        $routePattern = $cache->get('SERVERHANDLERROUTES', false);
        if ($routePattern === false) {
            $routePattern = $swaggerWrapper->generateRoutes();
            $cache->set('SERVERHANDLERROUTES', $routePattern);
        }
        $this->setRoutes($routePattern);
    }

    public function setMimeTypeOutputProcessor($mimetype, $handler)
    {
        $this->mimeTypeOutputProcessor[$mimetype] = $handler;
    }

    public function setPathOutputProcessor($method, $path, $handler)
    {
        $method = strtoupper($method);
        $this->pathOutputProcessor["$method::$path"] = $handler;
    }

    /**
     * @param $method
     * @param $path
     * @param $properties
     * @return string
     * @throws OperationIdInvalidException
     */
    public function getMethodOutputProcessor($method, $path, $properties)
    {
        $method = strtoupper($method);
        if (isset($this->pathOutputProcessor["$method::$path"])) {
            return $this->pathOutputProcessor["$method::$path"];
        }

        $produces = null;
        if (isset($properties['produces'])) {
            $produces = (array) $properties['produces'];
        }
        if (empty($produces) && isset($properties["responses"]["200"]["content"])) {
            $produces = array_keys($properties["responses"]["200"]["content"]);
        }

        if (empty($produces)) {
            return get_class($this->getDefaultOutputProcessor());
        }

        $produces = $produces[0];

        if (!isset($this->mimeTypeOutputProcessor[$produces])) {
            throw new OperationIdInvalidException("There is no handler for $produces");
        }

        return $this->mimeTypeOutputProcessor[$produces];
    }
}
