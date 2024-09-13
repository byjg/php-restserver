<?php

namespace ByJG\RestServer;

use ByJG\DesignPattern\Singleton;
use ByJG\RestServer\OutputProcessor\OutputProcessorInterface;
use ByJG\RestServer\Whoops\LoggerErrorHandler;
use ByJG\RestServer\Whoops\WhoopsWrapper;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
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
     * @var LoggerInterface
     */
    protected $logger = null;

    /**
     *
     * @var WhoopsWrapper
     */
    protected $wrapper = null;

    protected function __construct()
    {
        $this->whoops = new Run();
        $this->wrapper = new WhoopsWrapper();

        $this->logger = new NullLogger();

        $this->whoops->popHandler();
        $this->whoops->pushHandler(new LoggerErrorHandler());
        $this->whoops->pushHandler($this->wrapper);
    }

    /**
     * Set the proper Error Handler based on the Output of the page
     *
     * @param Handler $handler
     */
    public function setHandler(Handler $handler)
    {
        $this->wrapper->setHandler($handler);
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

    public function setOutputProcessor(OutputProcessorInterface $processor, HttpResponse $response, HttpRequest $request)
    {
        $this->wrapper->setOutputProcessor($processor, $response, $request);
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
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
