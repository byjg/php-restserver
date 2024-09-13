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
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class MockRequestHandler extends HttpRequestHandler
{
    /**
     * @var RequestInterface|null
     */
    private ?RequestInterface $requestInterface = null;

    /**
     * MockRequestHandler constructor.
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->writer = new MemoryWriter();
        ErrorHandler::getInstance()->setLogger($logger ?? new NullLogger());
    }

    public function withRequestObject(RequestInterface $request): static
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
     * @return HttpRequest
     */
    protected function getHttpRequest(): HttpRequest
    {
        if (is_null($this->httpRequest) && !is_null($this->requestInterface)) {
            $this->httpRequest = new MockHttpRequest($this->requestInterface);
        }

        if (is_null($this->httpRequest)) {
            throw new \RuntimeException("MockRequestHandler::withRequestObject() must be called before handle method");
        }

        return $this->httpRequest;
    }

    protected Response|null $psr7Response = null;

    public function getPsr7Response()
    {
        if (is_null($this->psr7Response)) {
            /** @psalm-suppress UndefinedInterfaceMethod Always using MemoryWriter */
            $this->psr7Response = new Response($this->writer->getStatusCode());

            /** @psalm-suppress UndefinedInterfaceMethod Always using MemoryWriter */
            foreach ($this->writer->getHeaders() as $header => $value) {
                $this->psr7Response = $this->psr7Response->withHeader($header, $value);
            }

            /** @psalm-suppress UndefinedInterfaceMethod Always using MemoryWriter */
            $this->psr7Response = $this->psr7Response->withBody(new MemoryStream($this->writer->getData()));
        }


        return $this->psr7Response;
    }

    public function handle(RouteListInterface $routeDefinition, bool $outputBuffer = true, bool $session = false): bool
    {
        return parent::handle($routeDefinition, false, false);
    }
}
