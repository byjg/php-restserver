<?php

namespace ByJG\RestServer;

use Whoops\Handler\Handler;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\XmlResponseHandler;
use Whoops\Run;
use Xmlnuke\Core\Enum\OutputData;

class ErrorHandler
{
	use \ByJG\DesignPattern\Singleton;

	/**
	 *
	 * @var Run
	 */
	protected $_whoops = null;

	/**
	 *
	 * @var Handler
	 */
	protected $_handler = null;

	protected function __construct()
	{
		$this->_whoops = new Run();

		$output = Context::getInstance()->getOutputFormat();

		$this->setHandler($output);
	}

	/**
	 * Set the proper Error Handler based on the Output of the page
	 *
	 * @param OutputData $output
	 */
	public function setHandler($output)
	{
		$this->_whoops->popHandler();

		if ($output == OutputData::Json)
		{
			$this->_handler = new JsonResponseHandler();
		}
		else if ($output == OutputData::Xml)
		{
			$this->_handler = new XmlResponseHandler();
		}
		else
		{
			$this->_handler = new PrettyPageHandler();
			if (!Context::getInstance()->getDevelopmentStatus())
			{
				$this->_handler->addResourcePath(\WhoopsResources\Resource::getPath());
			}
		}

		$this->_whoops->pushHandler($this->_handler);
	}

	/**
	 * Set Whoops as the default error and exception handler used by PHP:
	 */
	public function register()
	{
		$this->_whoops->register();
	}

	/**
	 * Disable Whoops as the default error and exception handler used by PHP:
	 */
	public function unregister()
	{
		$this->_whoops->unregister();
	}

	/**
	 * Added extra information for debug purposes on the error handler screen
	 *
	 * @param string $name
	 * @param value $value
	 */
	public function addExtraInfo($name, $value)
	{
		if (method_exists($this->_handler, 'addDataTable')) {
            $this->_handler->addDataTable('Rest Server Debug', array($name => $value));
        }
    }

}

