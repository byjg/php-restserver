<?php

namespace ByJG\RestServer;

use ByJG\RestServer\Exception\ClassNotFoundException;
use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\Exception\Error405Exception;
use ByJG\RestServer\Exception\Error520Exception;
use ByJG\RestServer\Exception\InvalidClassException;
use ByJG\RestServer\Route\RouteListInterface;
use ByJG\RestServer\Writer\MemoryWriter;
use ByJG\Util\Psr7\MemoryStream;
use ByJG\Util\Psr7\Response;
use Psr\Http\Message\RequestInterface;

class MockRequestHandler extends HttpRequestHandler
{
    /**
     * @var RequestInterface
     */
    protected $requestInterface;

    /**
     * MockRequestHandler constructor.
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct()
    {
        $this->writer = new MemoryWriter();
    }

    public function withRequestObject(RequestInterface $request)
    {
        $this->requestInterface = $request;
        return $this;
    }


    /**
     * @param RouteListInterface $routes
     * @param RequestInterface $request
     * @return MockRequestHandler
     * @throws ClassNotFoundException
     * @throws Error404Exception
     * @throws Error405Exception
     * @throws Error520Exception
     * @throws InvalidClassException
     */
    public static function mock(RouteListInterface $routes, RequestInterface $request)
    {
        $handler = new MockRequestHandler();
        $handler->withRequestObject($request);
        $handler->handle($routes);
        return $handler;
    }

    /**
     * @return RequestInterface
     */
    protected function getHttpRequest()
    {
        if (is_null($this->httpRequest) && !is_null($this->requestInterface)) {
            $this->httpRequest = new MockHttpRequest($this->requestInterface);
        }

        if (is_null($this->httpRequest)) {
            throw new \RuntimeException("MockRequestHandler::withRequestObject() must be called before handle method");
        }

        return $this->httpRequest;
    }

    protected $psr7Response = null;

    public function getPsr7Response()
    {
        if (is_null($this->psr7Response)) {
            $this->psr7Response = new Response($this->writer->getStatusCode());

            foreach ($this->writer->getHeaders() as $header => $value) {
                $this->psr7Response = $this->psr7Response->withHeader($header, $value);
            }

            $this->psr7Response = $this->psr7Response->withBody(new MemoryStream($this->writer->getData()));
        }


        return $this->psr7Response;
    }

    public function handle(RouteListInterface $routeDefinition, $outputBuffer = true, $session = false)
    {
        parent::handle($routeDefinition, false, false);
    }
}
