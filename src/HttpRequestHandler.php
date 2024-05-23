<?php

namespace ByJG\RestServer;

use ByJG\RestServer\Attributes\AfterRouteInterface;
use ByJG\RestServer\Attributes\AttributeParse;
use ByJG\RestServer\Attributes\BeforeRouteInterface;
use ByJG\RestServer\Exception\ClassNotFoundException;
use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\Exception\Error405Exception;
use ByJG\RestServer\Exception\Error520Exception;
use ByJG\RestServer\Exception\InvalidClassException;
use ByJG\RestServer\Middleware\AfterMiddlewareInterface;
use ByJG\RestServer\Middleware\BeforeMiddlewareInterface;
use ByJG\RestServer\Middleware\MiddlewareManagement;
use ByJG\RestServer\Middleware\MiddlewareResult;
use ByJG\RestServer\OutputProcessor\BaseOutputProcessor;
use ByJG\RestServer\OutputProcessor\OutputProcessorInterface;
use ByJG\RestServer\Route\RouteListInterface;
use ByJG\RestServer\Writer\HttpWriter;
use ByJG\RestServer\Writer\WriterInterface;
use Closure;
use Exception;
use FastRoute\Dispatcher;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class HttpRequestHandler implements RequestHandler
{
    const OK = "OK";
    const METHOD_NOT_ALLOWED = "NOT_ALLOWED";
    const NOT_FOUND = "NOT FOUND";

    protected $useErrorHandler = true;
    protected $detailedErrorHandler = false;

    protected $defaultOutputProcessor = null;
    protected $defaultOutputProcessorArgs = [];

    protected $afterMiddlewareList = [];
    protected $beforeMiddlewareList = [];

    protected $httpRequest = null;
    protected $httpResponse = null;

    /** @var WriterInterface */
    protected $writer;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->writer = new HttpWriter();
        ErrorHandler::getInstance()->setLogger($logger ?? new NullLogger());
    }

    /**
     * @param RouteListInterface $routeDefinition
     * @return bool
     * @throws ClassNotFoundException
     * @throws Error404Exception
     * @throws Error405Exception
     * @throws Error520Exception
     * @throws InvalidClassException
     */
    protected function process(RouteListInterface $routeDefinition)
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

        // Get OutputProcessor
        $outputProcessor = $this->initializeProcessor();
        
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
        
        if ($middlewareResult->getStatus() != MiddlewareResult::CONTINUE) {
            $outputProcessor->processResponse($this->getHttpResponse());
            return true;
        }

        // Processing
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                $outputProcessor->processResponse($this->getHttpResponse());
                throw new Error404Exception("Route '$uri' not found");

            case Dispatcher::METHOD_NOT_ALLOWED:
                $outputProcessor->processResponse($this->getHttpResponse());
                throw new Error405Exception('Method not allowed');

            case Dispatcher::FOUND:
                // ... 200 Process:
                $vars = array_merge($routeInfo[2], $queryStr);

                // Get the Selected Route
                $selectedRoute = $routeInfo[1];

                // Class
                $class = $selectedRoute["class"];
                $this->getHttpRequest()->appendVars($vars);

                // Get OutputProcessor
                $outputProcessor = $this->initializeProcessor(
                    $selectedRoute["output_processor"]
                );
                
                // Execute the request
                $this->executeRequest($outputProcessor, $class);

                break;

            default:
                throw new Error520Exception('Unknown');
        }

        return true;
    }

    protected function initializeProcessor($class = null)
    {
        if (!empty($class)) {
            $outputProcessor = BaseOutputProcessor::getFromClassName($class);
        } elseif (!empty($this->defaultOutputProcessor)) {
            $outputProcessor = BaseOutputProcessor::getFromClassName($this->defaultOutputProcessor);
        } else {
            $outputProcessor = BaseOutputProcessor::getFromHttpAccept();
        }
        $outputProcessor->setWriter($this->writer);
        $outputProcessor->writeContentType();
        if ($this->detailedErrorHandler) {
            ErrorHandler::getInstance()->setHandler($outputProcessor->getDetailedErrorHandler());
        } else {
            ErrorHandler::getInstance()->setHandler($outputProcessor->getErrorHandler());
        }

        ErrorHandler::getInstance()->setOutputProcessor($outputProcessor, $this->getHttpResponse());
        
        return $outputProcessor;
    }

    /**
     * Undocumented function
     *
     * @return HttpRequest
     */
    protected function getHttpRequest()
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
    protected function getHttpResponse()
    {
        if (is_null($this->httpResponse)) {
            $this->httpResponse = new HttpResponse();
        }

        return $this->httpResponse;
    }

    /**
     * @param OutputProcessorInterface $outputProcessor
     * @param $class
     * @throws ClassNotFoundException
     * @throws InvalidClassException
     */
    protected function executeRequest(
        OutputProcessorInterface $outputProcessor,
        $classDefinition
    )
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
    public function handle(RouteListInterface $routeDefinition, $outputBuffer = true, $session = false)
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

    public function withErrorHandlerDisabled()
    {
        $this->useErrorHandler = false;
        return $this;
    }

    public function withDetailedErrorHandler()
    {
        $this->detailedErrorHandler = true;
        return $this;
    }

    public function withMiddleware($middleware)
    {
        if ($middleware instanceof BeforeMiddlewareInterface) {
            $this->beforeMiddlewareList[] = $middleware;
        }
        if ($middleware instanceof AfterMiddlewareInterface) {
            $this->afterMiddlewareList[] = $middleware;
        }

        return $this;
    }

    public function withDefaultOutputProcessor($processor, $args = [])
    {
        if (!($processor instanceof Closure)) {
            if (!is_string($processor)) {
                throw new InvalidArgumentException("Default processor needs to class name of an OutputProcessor");
            }
            if (!is_subclass_of($processor, BaseOutputProcessor::class)) {
                throw new InvalidArgumentException("Needs to be a class of " . BaseOutputProcessor::class);
            }
        }

        $this->defaultOutputProcessor = $processor;

        return $this;
    }

    public function withWriter(WriterInterface $writer)
    {
        $this->writer = $writer;
        return $this;
    }
}
