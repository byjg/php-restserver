<?php

namespace ByJG\RestServer;

use ByJG\DesignPattern\Singleton;
use ByJG\RestServer\Exception\BadActionException;
use ByJG\RestServer\Exception\ClassNotFoundException;
use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\Exception\Error405Exception;
use ByJG\RestServer\Exception\Error520Exception;
use ByJG\RestServer\Exception\InvalidClassException;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use InvalidArgumentException;

class RouteHandler
{

    use Singleton;

    const OK = "OK";
    const METHOD_NOT_ALLOWED = "NOT_ALLOWED";
    const NOT_FOUND = "NOT FOUND";

    protected $_defaultMethods = [
        // Service
        ["method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{action}/{id:[0-9]+}/{secondid}'],
        ["method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{action}/{id:[0-9]+}'],
        ["method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{id:[0-9]+}/{action}'],
        ["method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{id:[0-9]+}'],
        ["method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{action}'],
        ["method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}']
    ];
    protected $_moduleAlias = [];
    protected $_defaultRestVersion = '1.0';
    protected $_defaultHandler = '\ByJG\RestServer\ServiceHandler';
    protected $_defaultOutput = null;

    public function getDefaultMethods()
    {
        return $this->_defaultMethods;
    }

    public function setDefaultMethods($methods)
    {
        if (!is_array($methods)) {
            throw new InvalidArgumentException('You need pass an array');
        }

        foreach ($methods as $value) {
            if (!isset($value['method']) || !isset($value['pattern'])) {
                throw new InvalidArgumentException('Array has not the valid format');
            }
        }

        $this->_defaultMethods = $methods;
    }

    public function getDefaultRestVersion()
    {
        return $this->_defaultRestVersion;
    }

    public function setDefaultRestVersion($version)
    {
        $this->_defaultRestVersion = $version;
    }

    public function getDefaultHandler()
    {
        return $this->_defaultHandler;
    }

    public function setDefaultHandler($value)
    {
        $this->_defaultHandler = $value;
    }

    public function getDefaultOutput()
    {
        return empty($this->_defaultOutput) ? Output::JSON : $this->_defaultOutput;
    }

    public function setDefaultOutput($defaultOutput)
    {
        $this->_defaultOutput = $defaultOutput;
    }

    public function getModuleAlias()
    {
        return $this->_moduleAlias;
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

            foreach ($this->getDefaultMethods() as $route) {
                $r->addRoute(
                    $route['method'],
                    str_replace('{version}', $this->getDefaultRestVersion(), $route['pattern']),
                    isset($route['handler']) ? $route['handler'] : $this->getDefaultHandler()
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

                // Check Alias
                $moduleAlias = $this->getModuleAlias();
                if (isset($moduleAlias[$vars['module']])) {
                    $vars['module'] = $moduleAlias[$vars['module']];
                }
                $vars['module'] = '\\' . str_replace('.', '\\', $vars['module']);

                // Define output
                if (!isset($vars['output'])) {
                    $vars['output'] = $this->getDefaultOutput();
                }
                ErrorHandler::getInstance()->setHandler($vars['output']);

                // Set all default values
                foreach ($vars as $key => $value) {
                    $_REQUEST[$key] = $_GET[$key] = $vars[$key];
                }

                // Instantiate the Service Handler
                $handlerInstance = $this->getHandler($routeInfo[1], $vars['output']);
                $instance = $this->executeAction($vars['module']);

                echo $handlerInstance->execute($instance);
                break;

            default:
                throw new Error520Exception('Unknown');
        }
    }

    /**
     * Get the Handler based on the string
     *
     * @param string $handler
     * @param string $output
     * @throws ClassNotFoundException
     * @throws InvalidClassException
     * @return HandlerInterface Return the Handler Interface
     */
    public function getHandler($handler, $output)
    {
        if (!class_exists($handler)) {
            throw new ClassNotFoundException("Handler $handler not found");
        }
        $handlerInstance = new $handler();
        if (!($handlerInstance instanceof HandlerInterface)) {
            throw new InvalidClassException("Handler $handler is not a HandlerInterface");
        }
        $handlerInstance->setOutput($output);
        $handlerInstance->setHeader();

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
    public function executeAction($class)
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
     *         "pattern" => '/{version}/{module}/{action}/{id:[0-9]+}/{secondid}',
     *         "handler" => '\ByJG\RestServer\ServiceHandler'
     *    ],
     * ]
     *
     * @param array $moduleAlias
     * @param array $routePattern
     * @param string $version
     * @param string $defaultOutput
     */
    public static function handleRoute($moduleAlias = [], $routePattern = null, $version = '1.0', $defaultOutput = Output::JSON)
    {
        ob_start();
        session_start();

        /**
         * @var RouteHandler
         */
        $route = RouteHandler::getInstance();

        /**
         * Module Alias contains the alias for full namespace class.
         *
         * For example, instead to request:
         * http://somehost/module/Full.NameSpace.To.Module
         *
         * you can request only:
         * http://somehost/module/somealias
         */
        foreach ((array)$moduleAlias as $alias => $module) {
            $route->addModuleAlias($alias, $module);
        }

        /**
         * You can create RESTFul compliant URL by adding the version.
         *
         * In the route pattern:
         * /{version}/someurl
         *
         * Setting the value here XMLNuke route will automatically replace it.
         *
         * The default value is "1.0"
         */
        $route->setDefaultRestVersion($version);

        /**
         * You can set the defaultOutput where is not necessary to set the output in the URL
         */
        $route->setDefaultOutput($defaultOutput);

        /**
         * There are a couple of basic routes pattern for the default parameters
         *
         * e.g.
         *
         * /1.0/command/1.json
         * /1.0/command/1.xml
         *
         * You can create your own route pattern by define the methods here
         */
        if (!empty($routePattern)) {
            $route->setDefaultMethods($routePattern);
        }

        // --------------------------------------------------------------------------
        // You do not need change from this point
        // --------------------------------------------------------------------------

        $debugBacktrace =  debug_backtrace();
        if (!empty($_SERVER['SCRIPT_FILENAME'])
            && file_exists($_SERVER['SCRIPT_FILENAME'])
            && $_SERVER['SCRIPT_FILENAME'] !== $debugBacktrace[0]['file']
        ) {
            $file = $_SERVER['SCRIPT_FILENAME'];
            if (strpos($file, '.php') !== false) {
                require_once($file);
            } else {
                header("Content-Type: " . RouteHandler::mimeContentType($file));

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
