<?php

namespace ByJG\RestServer;

use ByJG\RestServer\Exception\ClassNotFoundException;
use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\Exception\Error405Exception;
use ByJG\RestServer\Exception\Error520Exception;
use ByJG\RestServer\Exception\InvalidClassException;
use ByJG\RestServer\Route\RouteListInterface;
use ByJG\RestServer\Writer\MemoryWriter;
use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\WebRequest\Psr7\Response;
use Override;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

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
    public function __construct(?LoggerInterface $logger = null)
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
    #[Override]
    protected function getHttpRequest(): HttpRequest
    {
        if (is_null($this->httpRequest) && !is_null($this->requestInterface)) {
            $this->httpRequest = new MockHttpRequest($this->requestInterface);
        }

        if (is_null($this->httpRequest)) {
            throw new RuntimeException("MockRequestHandler::withRequestObject() must be called before handle method");
        }

        return $this->httpRequest;
    }

    protected Response|null $psr7Response = null;

    public function getPsr7Response(): ResponseInterface
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

    #[Override]
    public function handle(RouteListInterface $routeDefinition, bool $outputBuffer = true, bool $session = false): bool
    {
        try {
            return parent::handle($routeDefinition, false, false);
        } finally {
            // Cleanup: unregister error handler after mock request processing completes
            // This prevents global state pollution and "risky test" warnings in PHPUnit
            // For MockRequestHandler, we always want to clean up since it's only used in testing
            ErrorHandler::getInstance()->unregister();
        }
    }
}
