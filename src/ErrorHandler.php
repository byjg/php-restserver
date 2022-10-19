<?php

namespace ByJG\RestServer;

use ByJG\DesignPattern\Singleton;
use ByJG\RestServer\OutputProcessor\BaseOutputProcessor;
use ByJG\RestServer\Whoops\WhoopsWrapper;
use Whoops\Handler\Handler;
use Whoops\Run;

class ErrorHandler
{

    use Singleton;

    /**
     *
     * @var Run
     */
    protected $whoops = null;

    /**
     *
     * @var WhoopsWrapper
     */
    protected $handler = null;

    protected function __construct()
    {
        $this->whoops = new Run();
        $this->handler = new WhoopsWrapper();

        $this->whoops->popHandler();
        $this->whoops->pushHandler($this->handler);
    }

    /**
     * Set the proper Error Handler based on the Output of the page
     *
     * @param Handler $handler
     */
    public function setHandler(Handler $handler)
    {
        $this->handler->setHandler($handler);
    }

    /**
     * Set Whoops as the default error and exception handler used by PHP:
     */
    public function register()
    {
        $this->whoops->register();
    }

    /**
     * Disable Whoops as the default error and exception handler used by PHP:
     */
    public function unregister()
    {
        $this->whoops->unregister();
    }

    public function setOutputProcessor(BaseOutputProcessor $processor, HttpResponse $response)
    {
        $this->handler->setOutputProcessor($processor, $response);
    }

    // @todo Review
    // /**
    //  * Added extra information for debug purposes on the error handler screen
    //  *
    //  * @param string $name
    //  * @param string $value
    //  */
    // public function addExtraInfo($name, $value)
    // {
    //     if (method_exists($this->handler, 'addDataTable')) {
    //         $data = $this->handler->getDataTable();
    //         $this->handler->addDataTable('Info #' . (count($data) + 1), array($name => $value));
    //     }
    // }
}
