<?php

namespace ByJG\RestServer;

use ByJG\DesignPattern\Singleton;
use ByJG\RestServer\Exception\BadActionException;
use ByJG\RestServer\Exception\ClassNotFoundException;
use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\Exception\Error405Exception;
use ByJG\RestServer\Exception\Error520Exception;
use ByJG\RestServer\Exception\InvalidClassException;
use ByJG\RestServer\HandleOutput\HandleOutputInterface;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use InvalidArgumentException;

class ServerRequestHandler
{

    use Singleton;

    const OK = "OK";
    const METHOD_NOT_ALLOWED = "NOT_ALLOWED";
    const NOT_FOUND = "NOT FOUND";

    protected $routes = null;
    protected $_moduleAlias = [];

    public function getRoutes()
    {
        if (is_null($this->routes)) {
            $this->routes = [
                new RoutePattern(['GET', 'POST', 'PUT', 'DELETE'], '/{module}/{action}/{id:[0-9]+}/{secondid}'),
                new RoutePattern(['GET', 'POST', 'PUT', 'DELETE'], '/{module}/{action}/{id:[0-9]+}'),
                new RoutePattern(['GET', 'POST', 'PUT', 'DELETE'], '/{module}/{id:[0-9]+}/{action}'),
                new RoutePattern(['GET', 'POST', 'PUT', 'DELETE'], '/{module}/{id:[0-9]+}'),
                new RoutePattern(['GET', 'POST', 'PUT', 'DELETE'], '/{module}/{action}'),
                new RoutePattern(['GET', 'POST', 'PUT', 'DELETE'], '/{module}')
            ];
        }
        return $this->routes;
    }

    public function addRoute(RoutePattern $route)
    {
        if (is_null($this->routes)) {
            $this->routes = [];
        }
        $this->routes[] = $route;
    }

    /**
     * There are a couple of basic routes pattern for the default parameters
     *
     * e.g.
     *   /1.0/command/1.json
     *   /1.0/command/1.xml
     *
     * You can create your own route pattern by define the methods here
     *
     * @param $methods
     */
    public function setRoutes($methods)
    {
        if ($methods == null) {
            $this->routes = null;
            return;
        }

        if (!is_array($methods)) {
            throw new InvalidArgumentException('You need pass an array');
        }

        foreach ($methods as $value) {
            $routeHandler = $value;
            if (is_array($routeHandler)) {
                if (!isset($value['method']) || !isset($value['pattern'])) {
                    throw new InvalidArgumentException('Array have to be the format ["method"=>"", "pattern"=>""]');
                }
                $routeHandler = new RoutePattern($value['method'], $value['pattern']);
            }
            $this->addRoute($routeHandler);
        }
    }

    public function getModuleAlias()
    {
        return $this->_moduleAlias;
    }

    /**
     * Module Alias contains the alias for full namespace class.
     *
     * For example, instead to request:
     * http://somehost/module/Full.NameSpace.To.Module
     *
     * you can request only:
     * http://somehost/module/somealias
     *
     * @param $moduleAlias
     */
    public function setModuleAlias($moduleAlias)
    {
        foreach ((array)$moduleAlias as $alias => $module) {
            $this->addModuleAlias($alias, $module);
        }
    }

    public function addModuleAlias($alias, $module)
    {
        $this->_moduleAlias[$alias] = $module;
    }

    public function process()
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
                    $route->getHandler()
                );
            }
        });

        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:

                throw new Error404Exception('404 Not found');

            case Dispatcher::METHOD_NOT_ALLOWED:

                throw new Error405Exception('405 Method Not Allowed');

            case Dispatcher::FOUND:

                // ... 200 Process:
                $vars = array_merge($routeInfo[2], $queryStr);

                // Instantiate the Service Handler
                $handlerInstance = $this->getHandler($routeInfo[1]);
                $handlerInstance->writeHeader();
                ErrorHandler::getInstance()->setHandler($handlerInstance->getErrorHandler());

                // Check Alias
                $moduleAlias = $this->getModuleAlias();
                $vars['_class'] = $vars['module'];
                if (isset($moduleAlias[$vars['module']])) {
                    $vars['_class'] = $moduleAlias[$vars['module']];
                }
                $vars['_class'] = '\\' . str_replace('.', '\\', $vars['_class']);

                // Set all default values
                foreach ($vars as $key => $value) {
                    $_REQUEST[$key] = $_GET[$key] = $vars[$key];
                }

                $instance = $this->executeServiceMethod($vars['_class']);

                echo $handlerInstance->writeOutput($instance);
                break;

            default:
                throw new Error520Exception('Unknown');
        }
    }

    /**
     * Get the Handler based on the string
     *
     * @param string $handlerStr
     * @throws ClassNotFoundException
     * @throws InvalidClassException
     * @return HandleOutputInterface Return the Handler Interface
     */
    public function getHandler($handlerStr)
    {
        if (!class_exists($handlerStr)) {
            throw new ClassNotFoundException("Handler $handlerStr not found");
        }
        $handlerInstance = new $handlerStr();
        if (!($handlerInstance instanceof HandleOutputInterface)) {
            throw new InvalidClassException("Handler $handlerStr is not a HandleOutputInterface");
        }

        return $handlerInstance;
    }

    /**
     * Instantiate the class found in the route
     *
     * @param string $class
     * @return ServiceAbstract
     * @throws ClassNotFoundException
     * @throws InvalidClassException
     * @throws BadActionException
     */
    public function executeServiceMethod($class)
    {
        // Instantiate a new class
        if (!class_exists($class)) {
            throw new ClassNotFoundException("Class $class not found");
        }
        $instance = new $class();

        if (!($instance instanceof ServiceAbstract)) {
            throw new InvalidClassException("Class $class is not an instance of ServiceAbstract");
        }

        // Execute the method
        $method = strtolower($instance->getRequest()->server("REQUEST_METHOD")); // get, post, put, delete
        $customAction = $method . ($instance->getRequest()->get('action'));
        if (method_exists($instance, $customAction)) {
            $instance->$customAction();
        } else {
            throw new BadActionException("The action '$customAction' does not exists.");
        }

        return $instance;
    }

    /**
     * Process the ROUTE (see web/app-dist.php)
     *
     * ModuleAlias needs to be an array like:
     *  [ 'alias' => 'Full.Namespace.To.Class' ]
     *
     * RoutePattern needs to be an array like:
     * [
     *     [
     *         "method" => ['GET'],
     *         "pattern" => '/{module}/{action}/{id:[0-9]+}/{secondid}',
     *         "handler" => '\ByJG\RestServer\HandleOutput\HandleOutputInterface'
     *    ],
     * ]
     *
     * @param array $moduleAlias
     * @param array $routePattern
     */
    public static function handle($moduleAlias = [], $routePattern = null)
    {
        ob_start();
        session_start();

        /**
         * @var ServerRequestHandler
         */
        $route = ServerRequestHandler::getInstance();

        $route->setModuleAlias($moduleAlias);

        $route->setRoutes($routePattern);

        // --------------------------------------------------------------------------
        // Check if script exists or if is itself
        // --------------------------------------------------------------------------

        $debugBacktrace =  debug_backtrace();
        if (!empty($_SERVER['SCRIPT_FILENAME'])
            && file_exists($_SERVER['SCRIPT_FILENAME'])
            && str_replace('//', '/', $_SERVER['SCRIPT_FILENAME']) !== $debugBacktrace[0]['file']
        ) {
            $file = $_SERVER['SCRIPT_FILENAME'];
            if (strpos($file, '.php') !== false) {
                require_once($file);
            } else {
                header("Content-Type: " . ServerRequestHandler::mimeContentType($file));

                echo file_get_contents($file);
            }
            return;
        }

        $route->process();
    }

    /**
     * Get the Mime Type based on the filename
     *
     * @param string $filename
     * @return string
     */
    protected static function mimeContentType($filename)
    {

        $mime_types = array(
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
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
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
