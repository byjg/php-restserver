<?php

namespace ByJG\RestServer;

use Exception;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use InvalidArgumentException;

class Route
{
	use \ByJG\DesignPattern\Singleton;

	const OK = "OK";
	const METHOD_NOT_ALLOWED = "NOT_ALLOWED";
	const NOT_FOUND = "NOT FOUND";

	protected $_defaultMethods = [
			// Service
			[ "method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{action}/{id:[0-9]+}/{secondid}.{output}', "handler" => 'service' ],
			[ "method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{action}/{id:[0-9]+}.{output}', "handler" => 'service' ],
			[ "method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{id:[0-9]+}/{action}.{output}', "handler" => 'service' ],
			[ "method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{id:[0-9]+}.{output}', "handler" => 'service' ],
			[ "method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}/{action}.{output}', "handler" => 'service' ],
			[ "method" => ['GET', 'POST', 'PUT', 'DELETE'], "pattern" => '/{version}/{module}.{output}', "handler" => 'service' ]
		];

	protected $_moduleAlias = [];

	protected $_defaultVersion = '1.0';

	public function getDefaultMethods()
	{
		return $this->_defaultMethods;
	}

	public function setDefaultMethods($methods)
	{
		if (!is_array($methods))
		{
			throw new InvalidArgumentException('You need pass an array');
		}

		foreach ($methods as $value)
		{
			if (!isset($value['method']) || !isset($value['pattern']))
			{
				throw new InvalidArgumentException('Array has not the valid format');
			}
		}
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
		// Get the URL parameters
		$httpMethod = $_SERVER['REQUEST_METHOD'];
		$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $queryStr);

		// Generic Dispatcher for XMLNuke
		$dispatcher = \FastRoute\simpleDispatcher(function(RouteCollector $r) {

			foreach ($this->getDefaultMethods() as $route)
			{
			    $r->addRoute(
					$route['method'],
					str_replace('{version}', $this->getDefaultRestVersion(), $route['pattern']),
					isset($route['handler']) ? $route['handler'] : 'default'
				);
			}
		});

		$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

		switch ($routeInfo[0])
		{
			case Dispatcher::NOT_FOUND:

				// ... 404 Not Found
				return self::NOT_FOUND;

			case Dispatcher::METHOD_NOT_ALLOWED:

				// ... 405 Method Not Allowed
				return self::METHOD_NOT_ALLOWED;

			case Dispatcher::FOUND:

				// ... 200 Process:
				$handler = $routeInfo[1];
				$vars = array_merge($routeInfo[2], $queryStr);

				// Check Alias
				$moduleAlias = $this->getModuleAlias();
				if (isset($moduleAlias[$vars['module']]))
				{
					$vars['module'] = $moduleAlias[$vars['module']];
				}

				// Define output
				if (!isset($vars['output']))
				{
					$vars['output'] = 'json';
				}

				// Check if output is set
				if ($vars['output'] != 'json' && $vars['output'] != 'xml')
				{
					throw new Exception('Invalid output format. Valid are XML and JSON');
				}

				// Set all default values
				foreach($vars as $key => $value)
				{
					$_REQUEST[$key] = $_GET[$key] = $vars[$key];
				}
				$_REQUEST['raw'] = $_GET['raw'] = isset($vars['output']) ? $vars['output'] : 'json';

				return self::OK;
		}
	}

}
