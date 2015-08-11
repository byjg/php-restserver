<?php

use ByJG\RestServer\RouteHandler;
use ByJG\RestServer\ServiceHandler;

\RouteManager::autoload();
\RouteManager::processRoute();






// --------------------------------------------------------------------------
// You do not need change from this point
// --------------------------------------------------------------------------

class RouteManager
{

    public static function processRoute($moduleAlias = [], $routePattern = null, $version = '1.0', $cors = false)
    {
        /**
         * @var RouteWrapper
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
        if (empty($routePattern)) {
            $routePattern = [
                [ "method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{action}/{id:[0-9]+}/{secondid}.{output}', "handler" => 'service' ],
                [ "method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{action}/{id:[0-9]+}.{output}', "handler" => 'service' ],
                [ "method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{id:[0-9]+}/{action}.{output}', "handler" => 'service' ],
                [ "method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{id:[0-9]+}.{output}', "handler" => 'service' ],
                [ "method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{action}.{output}', "handler" => 'service' ],
                [ "method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}.{output}', "handler" => 'service' ]
            ];
        }
        $route->setDefaultMethods($routePattern);

        // --------------------------------------------------------------------------
        // You do not need change from this point
        // --------------------------------------------------------------------------

        list($class, $output) = $process = $route->process();

        $handler = new ServiceHandler($output);
        $handler->setHeader();
        if (!$cors || ($cors && $handler->setHeaderCors())) {
            echo $handler->execute($class);
        }
    }

    public static function autoload()
    {
        ob_start();
        session_start();

        // Try to autoload class
        $autoloadDir = [
            __DIR__."/../vendor/autoload.php", // In a sub-folder in the same level of 'vendor'
            __DIR__."/../../../autoload.php", // Symbolic link to composer requirement
            __DIR__."vendor/autoload.php"      // In the same folder of router.
        ];
        $loaded = false;
        foreach ($autoloadDir as $autoload) {
            if (file_exists($autoload)) {
                require_once $autoload;
                $loaded = true;
                break;
            }
        }
        if (!$loaded) {
            throw new \Exception('Autoload not found. Did you run `composer dump-autload`?');
        }

        // If request is a valid PHP file, load it instead process on Route;
        $request = ".".$_SERVER['REQUEST_URI'];
        if (file_exists($request)) {
            require $request;
            return;
        }
    }
}
