<?php

namespace ByJG\RestServer\Whoops;

use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\OutputProcessor\BaseOutputProcessor;
use ByJG\Util\Psr7\Response;
use Whoops\Exception\Inspector;
use Whoops\Handler\Handler;
use Whoops\RunInterface;

class WhoopsWrapper extends Handler
{
    /** @var Handler */
    protected $effectiveHandler = null;

    /** @var BaseOutputProcessor */
    protected $outputProcessor;

    /** @var HttpResponse */
    protected $response;

    public function __construct()
    {
        $this->effectiveHandler = new PlainResponseErrorHandler();
    }

    /**
     * Set the effective handler
     *
     * @param Handler $handler
     * @return void
     */
    public function setHandler(Handler $handler)
    {
        $this->effectiveHandler = $handler;
    }

    public function setOutputProcessor(BaseOutputProcessor $processor, HttpResponse $response)
    {
        $this->outputProcessor = $processor;
        $this->response = $response;
    }

    /* *******************************************************
     *
     * HandlerInterface
     *
     ********************************************************* */

     /**
     * @return int|null A handler may return nothing, or a Handler::HANDLE_* constant
     */
    public function handle()
    {
        if (!empty($this->outputProcessor)) {
            $this->response->emptyResponse();
            $this->outputProcessor->writeHeader($this->response);
        }
        return $this->effectiveHandler->handle();
    }

    /**
     * @param  RunInterface  $run
     * @return void
     */
    public function setRun(RunInterface $run)
    {
        return $this->effectiveHandler->setRun($run);
    }

    /**
     * @param  \Throwable $exception
     * @return void
     */
    public function setException($exception)
    {
        return $this->effectiveHandler->setException($exception);
    }

    /**
     * @param  Inspector $inspector
     * @return void
     */
    public function setInspector(Inspector $inspector)
    {
        return $this->effectiveHandler->setInspector($inspector);
    }
}
