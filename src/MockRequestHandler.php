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
    protected $request;

    /** @var MemoryWriter */
    protected $writer;

    /**
     * MockRequestHandler constructor.
     * @param RequestInterface $request
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
        $this->writer = new MemoryWriter();
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
        $handler = new MockRequestHandler($request);
        $handler->handle($routes, false, false);
        return $handler;
    }

    /**
     * @return HttpRequest|MockHttpRequest
     */
    protected function getHttpRequest()
    {
        if (is_null($this->httpRequest)) {
            $this->httpRequest = new MockHttpRequest($this->request);
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
}
