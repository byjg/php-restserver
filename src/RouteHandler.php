<?php

namespace ByJG\RestServer;

use Exception;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use InvalidArgumentException;
use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\Exception\Error405Exception;
use ByJG\RestServer\ServiceHandler;

class RouteHandler
{

    use \ByJG\DesignPattern\Singleton;

    const OK = "OK";
    const METHOD_NOT_ALLOWED = "NOT_ALLOWED";
    const NOT_FOUND = "NOT FOUND";

    protected $_defaultMethods = [
        // Service
        [ "method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{action}/{id:[0-9]+}/{secondid}.{output}', "handler" => 'service'],
        [ "method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{action}/{id:[0-9]+}.{output}', "handler" => 'service'],
        [ "method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{id:[0-9]+}/{action}.{output}', "handler" => 'service'],
        [ "method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{id:[0-9]+}.{output}', "handler" => 'service'],
        [ "method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{action}.{output}', "handler" => 'service'],
        [ "method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}.{output}', "handler" => 'service']
    ];
    protected $_moduleAlias = [];
    protected $_defaultVersion = '1.0';

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
        return $this->_defaultVersion;
    }

    public function setDefaultRestVersion($version)
    {
        $this->_defaultVersion = $version;
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

        // Generic Dispatcher for XMLNuke
        $dispatcher = \FastRoute\simpleDispatcher(function(RouteCollector $r) {

            foreach ($this->getDefaultMethods() as $route) {
                $r->addRoute(
                    $route['method'], str_replace('{version}', $this->getDefaultRestVersion(), $route['pattern']),
                    isset($route['handler']) ? $route['handler'] : 'default'
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
                    $vars['output'] = Output::JSON;
                }
                ErrorHandler::getInstance()->setHandler($vars['output']);

                // Check if output is set
                if ($vars['output'] != Output::JSON && $vars['output'] != Output::XML && $vars['output'] != Output::CSV && $vars['output']
                    != Output::RDF) {
                    throw new Exception('Invalid output format. Valid are XML, JSON or CSV');
                }

                // Set all default values
                foreach ($vars as $key => $value) {
                    $_REQUEST[$key] = $_GET[$key] = $vars[$key];
                }

                return [ $vars['module'], $vars['output']];

            default:
                throw new \Exception('Unknown');
        }
    }

    /**
     * Process the ROUTE (see httpdocs/route-dist.php)
     *
     * ModuleAlias needs to be an array like:
     *  [ 'alias' => 'Full.Namespace.To.Class' ]
     *
     * RoutePattern needs to be an array like:
     * [
     * 		[ "method" => ['GET'], "pattern" => '/{version}/{module}/{action}/{id:[0-9]+}/{secondid}.{output}', "handler" => 'service' ],
     * ]
     *
     * @param array $moduleAlias
     * @param array $routePattern
     * @param string $version
     * @param bool $cors
     */
    public static function processRoute($moduleAlias = [], $routePattern = null, $version = '1.0', $cors = false)
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
        foreach ((array) $moduleAlias as $alias => $module) {
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

        if (!empty($_SERVER['SCRIPT_FILENAME']) && file_exists($_SERVER['SCRIPT_FILENAME']) 
            && basename($_SERVER['SCRIPT_FILENAME']) !== "route.php") {
            $file = $_SERVER['SCRIPT_FILENAME'];
            if (strpos($file, '.php') !== false) {
                require_once($file);
            } else {
                header("Content-Type: " . RouteHandler::mimeContentType($file));

                echo file_get_contents($file);
            }
            return;
        }

        list($class, $output) = $route->process();

        $handler = new ServiceHandler($output);
        $handler->setHeader();
        if (!$cors || ($cors && $handler->setHeaderCors())) {
            echo $handler->execute($class);
        }
    }

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
