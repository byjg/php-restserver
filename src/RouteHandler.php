<?php

namespace ByJG\RestServer;

use Exception;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use InvalidArgumentException;
use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\Exception\Error405Exception;

class RouteHandler
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
        // Initialize ErrorHandler with default error handler
        ErrorHandler::getInstance()->register();

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

                throw new Error404Exception('404 Not found');

			case Dispatcher::METHOD_NOT_ALLOWED:

                throw new Error405Exception('405 Method Not Allowed');

			case Dispatcher::FOUND:

				// ... 200 Process:
				$vars = array_merge($routeInfo[2], $queryStr);

				// Check Alias
				$moduleAlias = $this->getModuleAlias();
				if (isset($moduleAlias[$vars['module']]))
				{
					$vars['module'] = $moduleAlias[$vars['module']];
				}
                $vars['module'] = '\\' . str_replace('.', '\\', $vars['module']);

				// Define output
				if (!isset($vars['output']))
				{
					$vars['output'] = Output::JSON;
				}
                ErrorHandler::getInstance()->setHandler($vars['output']);

				// Check if output is set
				if ($vars['output'] != Output::JSON && $vars['output'] != Output::XML && $vars['output'] != Output::CSV)
				{
					throw new Exception('Invalid output format. Valid are XML, JSON or CSV');
				}

				// Set all default values
				foreach($vars as $key => $value)
				{
					$_REQUEST[$key] = $_GET[$key] = $vars[$key];
				}

                return [ $vars['module'], $vars['output'] ];

            default:
                throw new \Exception('Unknown');
		}
	}

}
