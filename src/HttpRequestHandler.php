<?php

namespace ByJG\RestServer;

use ByJG\RestServer\Attributes\AfterRouteInterface;
use ByJG\RestServer\Attributes\AttributeParse;
use ByJG\RestServer\Attributes\BeforeRouteInterface;
use ByJG\RestServer\Exception\ClassNotFoundException;
use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\Exception\Error405Exception;
use ByJG\RestServer\Exception\Error422Exception;
use ByJG\RestServer\Exception\Error520Exception;
use ByJG\RestServer\Exception\InvalidClassException;
use ByJG\RestServer\Middleware\AfterMiddlewareInterface;
use ByJG\RestServer\Middleware\BeforeMiddlewareInterface;
use ByJG\RestServer\Middleware\MiddlewareManagement;
use ByJG\RestServer\Middleware\MiddlewareResult;
use ByJG\RestServer\OutputProcessor\BaseOutputProcessor;
use ByJG\RestServer\OutputProcessor\OutputProcessorInterface;
use ByJG\RestServer\Route\RouteList;
use ByJG\RestServer\Route\RouteListInterface;
use ByJG\RestServer\Writer\HttpWriter;
use ByJG\RestServer\Writer\WriterInterface;
use Closure;
use Exception;
use FastRoute\Dispatcher;
use InvalidArgumentException;
use Override;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class HttpRequestHandler implements RequestHandler
{
    const OK = "OK";
    const METHOD_NOT_ALLOWED = "NOT_ALLOWED";
    const NOT_FOUND = "NOT FOUND";

    protected bool $useErrorHandler = true;
    protected bool $detailedErrorHandler = false;

    protected string|null $defaultOutputProcessor = null;

    protected array $afterMiddlewareList = [];
    protected array $beforeMiddlewareList = [];

    protected ?HttpRequest $httpRequest = null;
    protected ?HttpResponse $httpResponse = null;

    /** @var WriterInterface */
    protected WriterInterface $writer;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->writer = new HttpWriter();
        ErrorHandler::getInstance()->setLogger($logger ?? new NullLogger());
    }

    /**
     * @throws ClassNotFoundException
     * @throws Error404Exception
     * @throws Error405Exception
     * @throws Error520Exception
     * @throws InvalidClassException
     * @throws Exception
     *
     * @return true
     */
    protected function process(RouteListInterface $routeDefinition): bool
    {

        // Initialize ErrorHandler with default error handler
        if ($this->useErrorHandler) {
            ErrorHandler::getInstance()->register();
        }

        // Get the URL parameters
        $httpMethod = $this->getHttpRequest()->server('REQUEST_METHOD');
        $uri = parse_url($this->getHttpRequest()->server('REQUEST_URI'), PHP_URL_PATH);
        $query = parse_url($this->getHttpRequest()->server('REQUEST_URI'), PHP_URL_QUERY);
        $queryStr = [];
        if (!empty($query)) {
            parse_str($query, $queryStr);
        }

        // Generic Dispatcher for RestServer
        $dispatcher = $routeDefinition->getDispatcher();
        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
        $this->getHttpRequest()->setRouteMetadata($routeInfo[1] ?? []);
        $this->getHttpRequest()->appendVars(array_merge($routeInfo[2] ?? [], $queryStr));

        // Get OutputProcessor
        $outputProcessor = $this->initializeProcessor(
            $this->getHttpRequest()->getRouteMetadata(RouteList::META_OUTPUT_PROCESSOR),
            $this->getHttpRequest()->getRouteMetadata(RouteList::META_OUTPUT_PROCESSOR_STRICT) ?? false,
        );
        
        // Process Before Middleware
        try {
            $middlewareResult = MiddlewareManagement::processBefore(
                $this->beforeMiddlewareList,
                $routeInfo[0],
                $this->getHttpResponse(),
                $this->getHttpRequest()
            );
        } catch (Exception $ex) {
            $outputProcessor->processResponse($this->getHttpResponse());
            throw $ex;
        }
        
        if ($middlewareResult != MiddlewareResult::continue) {
            $outputProcessor->processResponse($this->getHttpResponse());
            return true;
        }

        // Processing
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND: // 0
                $outputProcessor->processResponse($this->getHttpResponse());
                throw new Error404Exception("Route '$uri' not found");

            case Dispatcher::METHOD_NOT_ALLOWED: // 2
                $outputProcessor->processResponse($this->getHttpResponse());
                throw new Error405Exception('Method not allowed');

            case Dispatcher::FOUND:  // 1
                // ... 200 Process:
                // Execute the request
                $this->executeRequest($outputProcessor, $this->getHttpRequest()->getRouteMetadata(RouteList::META_CLASS));

                break;

            default:
                throw new Error520Exception('Unknown');
        }

        return true;
    }

    /**
     * Initialize the OutputProcessor
     * @param array|string|null $class
     * @param bool $strict
     * @return mixed
     * @throws Error422Exception
     */
    protected function initializeProcessor(array|string|null $class = null, bool $strict = false): mixed
    {
        $outputProcessor = BaseOutputProcessor::factory($class);
        if (empty($outputProcessor) && !$strict) {
            $outputProcessor = BaseOutputProcessor::factory($this->defaultOutputProcessor);
        }
        if (empty($outputProcessor)) {
            throw new Error422Exception('Accept content not allowed');
        }
        $outputProcessor->setWriter($this->writer);
        $outputProcessor->writeContentType();

        if ($this->detailedErrorHandler) {
            ErrorHandler::getInstance()->setHandler($outputProcessor->getDetailedErrorHandler());
        } else {
            ErrorHandler::getInstance()->setHandler($outputProcessor->getErrorHandler());
        }
        ErrorHandler::getInstance()->setOutputProcessor($outputProcessor, $this->getHttpResponse(), $this->getHttpRequest());
        
        return $outputProcessor;
    }

    /**
     * Undocumented function
     *
     * @return HttpRequest
     */
    protected function getHttpRequest(): HttpRequest
    {
        if (is_null($this->httpRequest)) {
            $this->httpRequest = new HttpRequest($_GET, $_POST, $_SERVER, $_SESSION ?? [], $_COOKIE);
        }

        return $this->httpRequest;
    }

    /**
     * Undocumented function
     *
     * @return HttpResponse
     */
    protected function getHttpResponse(): HttpResponse
    {
        if (is_null($this->httpResponse)) {
            $this->httpResponse = new HttpResponse();
        }

        return $this->httpResponse;
    }

    /**
     * @param OutputProcessorInterface $outputProcessor
     * @param $classDefinition
     * @throws Exception
     */
    protected function executeRequest(
        OutputProcessorInterface $outputProcessor,
        $classDefinition
    ): void
    {
        $className = null;
        $methodName = null;
        $exception = null;
        try {
            if ($classDefinition instanceof Closure) {
                // Process Closure
                $className = 'Closure';
                $methodName = $this->getHttpRequest()->getRequestPath();
                $classDefinition($this->getHttpResponse(), $this->getHttpRequest());
            } else {
                // Process Class::Method()
                $className = $classDefinition[0];
                $methodName = $classDefinition[1];
                if (!class_exists($className)) {
                    throw new ClassNotFoundException("Class '$className' defined in the route is not found");
                }
                $instance = new $className();
                if (!method_exists($instance, $methodName)) {
                    throw new InvalidClassException("There is no method '$className::$methodName''");
                }
                AttributeParse::processAttribute(BeforeRouteInterface::class, $instance, $methodName, $this->getHttpResponse(), $this->getHttpRequest());
                $instance->$methodName($this->getHttpResponse(), $this->getHttpRequest());
                AttributeParse::processAttribute(AfterRouteInterface::class, $instance, $methodName, $this->getHttpResponse(), $this->getHttpRequest());
            }
        } catch (Exception $ex) {
            $exception = $ex;
        } finally {
            MiddlewareManagement::processAfter(
                $this->afterMiddlewareList,
                $this->getHttpResponse(),
                $this->getHttpRequest(),
                $className,
                $methodName,
                $exception
            );

            if ($exception !== null) {
                throw $exception;
            }
        }


        $outputProcessor->processResponse($this->getHttpResponse());
    }

    /**
     * Handle the ROUTE (see web/app-dist.php)
     *
     * @param RouteListInterface $routeDefinition
     * @param bool $outputBuffer
     * @param bool $session
     * @return bool
     * @throws ClassNotFoundException
     * @throws Error404Exception
     * @throws Error405Exception
     * @throws Error520Exception
     * @throws InvalidClassException
     */
    #[Override]
    public function handle(RouteListInterface $routeDefinition, bool $outputBuffer = true, bool $session = false): bool
    {
        if ($outputBuffer) {
            ob_start();
        }
        if ($session) {
            session_start();
        }

        // --------------------------------------------------------------------------
        // Check if script exists or if is itself
        // --------------------------------------------------------------------------
        return $this->process($routeDefinition);
    }

    #[Override]
    public function withErrorHandlerDisabled(): static
    {
        $this->useErrorHandler = false;
        return $this;
    }

    #[Override]
    public function withDetailedErrorHandler(): static
    {
        $this->detailedErrorHandler = true;
        return $this;
    }

    #[Override]
    public function withMiddleware(AfterMiddlewareInterface|BeforeMiddlewareInterface $middleware, ?string $routePattern = null): static
    {
        $item = [
            'middleware' => $middleware,
            'routePattern' => $routePattern
        ];

        if ($middleware instanceof BeforeMiddlewareInterface) {
            $this->beforeMiddlewareList[] = $item;
        }
        if ($middleware instanceof AfterMiddlewareInterface) {
            $this->afterMiddlewareList[] = $item;
        }

        return $this;
    }

    #[Override]
    public function withDefaultOutputProcessor(string $processor): static
    {
        if (!is_subclass_of($processor, BaseOutputProcessor::class)) {
            throw new InvalidArgumentException("Needs to be a class of " . BaseOutputProcessor::class);
        }

        $this->defaultOutputProcessor = $processor;

        return $this;
    }

    public function withWriter(WriterInterface $writer): static
    {
        $this->writer = $writer;
        return $this;
    }
}
