<?php

//// -------------------------------------------------------------------
//// CORS - Better do that in your http server, but you can enable here
//// -------------------------------------------------------------------
//header('Access-Control-Allow-Origin: *');
//header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
//header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
//$method = $_SERVER['REQUEST_METHOD'];
//if($method == "OPTIONS") {
//	die();
//}
//// -------------------------------------------------------------------

use Xmlnuke\Core\Wrapper\HtmlWrapper;
use Xmlnuke\Core\Wrapper\RouteWrapper;

require "../vendor/autoload.php";

/**
 * @var RouteWrapper
 */
$route = Route::getInstance();

/**
 * Module Alias contains the alias for full namespace class.
 *
 * For example, instead to request:
 * http://somehost/module/Full.NameSpace.To.Module
 *
 * you can request only:
 * http://somehost/module/somealias
 */
//$routeWraper->addModuleAlias('somealias', 'Full.NameSpace.To.Module');

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
//$routeWraper->setDefaultRestVersion('1.0');

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
//$routeWraper->setDefaultMethods([
//	// Service
//	[ "method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{action}/{id:[0-9]+}/{secondid}.{output}', "handler" => 'service' ],
//	[ "method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{action}/{id:[0-9]+}.{output}', "handler" => 'service' ],
//	[ "method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{id:[0-9]+}/{action}.{output}', "handler" => 'service' ],
//	[ "method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{id:[0-9]+}.{output}', "handler" => 'service' ],
//	[ "method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{action}.{output}', "handler" => 'service' ],
//	[ "method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}.{output}', "handler" => 'service' ]
//]);

// --------------------------------------------------------------------------
// You do not need change from this point
// --------------------------------------------------------------------------

$process = $route->Process();

switch ($process)
{
    case RouteWrapper::NOT_FOUND:

        // ... 404 Not Found
		header('HTTP/1.0 404 Not Found', true, 404);
		echo "<h1>Not Found</h1>";

        break;

    case RouteWrapper::METHOD_NOT_ALLOWED:

        // ... 405 Method Not Allowed
		header('HTTP/1.0 405 Method Not Allowed', true, 405);
		echo "<h1>Method Not allowed</h1>";
		echo "Allowed methods: " . print_r($routeInfo[1], true);
        break;

    case RouteWrapper::OK:

		// ... 200 Process:

		HtmlWrapper::getInstance()->Process();
        break;

	default:
		throw new \Exception('Unexpected error');
}


