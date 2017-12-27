<?php

namespace ByJG\RestServer;

use ByJG\DesignPattern\Singleton;
use ByJG\RestServer\Exception\ClassNotFoundException;
use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\Exception\Error405Exception;
use ByJG\RestServer\Exception\Error520Exception;
use ByJG\RestServer\Exception\InvalidClassException;
use ByJG\RestServer\HandleOutput\HandleOutputInterface;
use ByJG\RestServer\HandleOutput\JsonHandler;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

class ServerRequestHandler
{
    const OK = "OK";
    const METHOD_NOT_ALLOWED = "NOT_ALLOWED";
    const NOT_FOUND = "NOT FOUND";

    protected $routes = null;

    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * @param \ByJG\RestServer\RoutePattern[] $routes
     */
    public function setRoutes($routes)
    {
        foreach ((array)$routes as $route) {
            $this->addRoute($route);
        }
    }

    /**
     * @param \ByJG\RestServer\RoutePattern $route
     */
    public function addRoute(RoutePattern $route)
    {
        if (is_null($this->routes)) {
            $this->routes = [];
        }
        $this->routes[] = $route;
    }

    /**
     * @throws \ByJG\RestServer\Exception\ClassNotFoundException
     * @throws \ByJG\RestServer\Exception\Error404Exception
     * @throws \ByJG\RestServer\Exception\Error405Exception
     * @throws \ByJG\RestServer\Exception\Error520Exception
     * @throws \ByJG\RestServer\Exception\InvalidClassException
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
        $dispatcher = \FastRoute\simpleDispatcher(function (RouteCollector $r) {

            foreach ($this->getRoutes() as $route) {
                $r->addRoute(
                    $route->getMethod(),
                    $route->getPattern(),
                    [
                        "handler" => $route->getHandler(),
                        "class" => $route->getClass(),
                        "function" => $route->getFunction()
                    ]
                );
            }
        });

        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

        // Default Handler for errors
        $defaultHandler = new JsonHandler();
        $defaultHandler->writeHeader();
        ErrorHandler::getInstance()->setHandler($defaultHandler->getErrorHandler());

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
                $this->executeRequest(
                    new $handlerRequest['handler'],
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
     * @param HandleOutputInterface $handler
     * @param string $class
     * @param string $function
     * @param array $vars
     * @throws \ByJG\RestServer\Exception\ClassNotFoundException
     * @throws \ByJG\RestServer\Exception\InvalidClassException
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
        $request = new HttpRequest($_GET, $_POST, $_SERVER, $_SESSION, $_COOKIE);
        $response = new HttpResponse();

        // Process Closure
        if ($function instanceof \Closure) {
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
     * @param \ByJG\RestServer\RoutePattern[]|null $routePattern
     * @throws \ByJG\RestServer\Exception\ClassNotFoundException
     * @throws \ByJG\RestServer\Exception\Error404Exception
     * @throws \ByJG\RestServer\Exception\Error405Exception
     * @throws \ByJG\RestServer\Exception\Error520Exception
     * @throws \ByJG\RestServer\Exception\InvalidClassException
     */
    public function handle($routePattern = null)
    {
        ob_start();
        session_start();

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
            if (strpos($file, '.php') !== false) {
                require_once($file);
            } else {
                header("Content-Type: " . $this->mimeContentType($file));

                echo file_get_contents($file);
            }
            return;
        }

        $this->process();
    }

    /**
     * Get the Mime Type based on the filename
     *
     * @param string $filename
     * @return string
     */
    protected function mimeContentType($filename)
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

        $ext = strtolower(array_pop(explode('.', $filename)));
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
}
