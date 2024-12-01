<?php

namespace ByJG\RestServer\Whoops;

use ByJG\RestServer\ErrorHandler;
use ByJG\RestServer\Exception\HttpResponseException;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\OutputProcessor\BaseOutputProcessor;
use ByJG\RestServer\OutputProcessor\OutputProcessorInterface;
use ReflectionMethod;
use Throwable;
use Whoops\Handler\Handler;
use Whoops\Inspector\InspectorInterface;
use Whoops\RunInterface;

class WhoopsWrapper extends Handler
{
    /** @var Handler */
    protected $effectiveHandler = null;

    /** @var BaseOutputProcessor */
    protected $outputProcessor;

    /** @var HttpResponse */
    protected $response;
    private HttpRequest $request;

    public function __construct()
    {
        $this->effectiveHandler = new PlainResponseErrorHandler();
        $this->request = new HttpRequest([], [], [], [], []);
        $this->response = new HttpResponse();
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

    public function setOutputProcessor(OutputProcessorInterface $processor, HttpResponse $response, HttpRequest $request): void
    {
        $this->outputProcessor = $processor;
        $this->response = $response;
        $this->request = $request;
    }

    /* *******************************************************
     *
     * HandlerInterface
     *
     ********************************************************* */

    /**
     * @return int|null A handler may return nothing, or a Handler::HANDLE_* constant
     * @throws \ReflectionException
     */
    public function handle()
    {
        $r = new ReflectionMethod($this->effectiveHandler, 'getException');
        $r->setAccessible(true); // That's necessary because error handler doesn't expose `getException`
        $exception = $r->invoke($this->effectiveHandler);
        if ($exception instanceof HttpResponseException) {
            $exception->setResponse($this->response);
            $exception->sendHeader();
        } elseif (!empty($this->response)) {
            $this->response->setResponseCode(500, 'Internal Error');
        }

        if (!empty($this->outputProcessor)) {
            $this->response->emptyResponse();
            $this->outputProcessor->writeHeader($this->response);
        }

        $logData = [
            'path' => $this->request->getRequestPath(),
            'method' => $this->request->server('REQUEST_METHOD'),
            'trace' => explode("\n", $exception->getTraceAsString() ?? '')
        ];

        ErrorHandler::getInstance()->getLogger()->error(
            $exception->getMessage(),
            $logData
        );
        return $this->effectiveHandler->handle();
    }

    /**
     * @param  RunInterface  $run
     * @return void|null
     */
    public function setRun(RunInterface $run)
    {
        return $this->effectiveHandler->setRun($run);
    }

    /**
     * @param  Throwable $exception
     * @return void|null
     */
    public function setException($exception)
    {
        return $this->effectiveHandler->setException($exception);
    }

    /**
     * @param  InspectorInterface $inspector
     * @return void|null
     */
    public function setInspector(InspectorInterface $inspector)
    {
        return $this->effectiveHandler->setInspector($inspector);
    }
}
