<?php

namespace ByJG\RestServer;

use ByJG\DesignPattern\Singleton;
use ByJG\RestServer\Whoops\JsonResponseHandler;
use ByJG\RestServer\Whoops\PlainResponseHandler;
use Whoops\Handler\XmlResponseHandler;
use Whoops\Handler\Handler;
use Whoops\Run;

class ErrorHandler
{

    use Singleton;

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
        $this->setHandler();
    }

    /**
     * Set the proper Error Handler based on the Output of the page
     *
     * @param string $output
     */
    public function setHandler($output = null)
    {
        $this->_whoops->popHandler();

        if ($output == Output::JSON) {
            $this->_handler = new JsonResponseHandler();
        } else if ($output == Output::XML) {
            $this->_handler = new XmlResponseHandler();
        } else {
            $this->_handler = new PlainResponseHandler();
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
     * @param string $value
     */
    public function addExtraInfo($name, $value)
    {
        if (method_exists($this->_handler, 'addDataTable')) {
            $data = $this->_handler->getDataTable();
            $this->_handler->addDataTable('Info #' . (count($data) + 1), array($name => $value));
        }
    }
}
