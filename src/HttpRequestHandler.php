<?php

namespace ByJG\RestServer;

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
use FastRoute\Dispatcher;
use InvalidArgumentException;

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

    /** @var WriterInterface */
    protected $writer;

    public function __construct()
    {
        $this->writer = new HttpWriter();
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

        // Create the Request and Response methods
        $request = $this->getHttpRequest();
        $response = new HttpResponse();

        // Get the URL parameters
        $httpMethod = $request->server('REQUEST_METHOD');
        $uri = parse_url($request->server('REQUEST_URI'), PHP_URL_PATH);
        $query = parse_url($request->server('REQUEST_URI'), PHP_URL_QUERY);
        $queryStr = [];
        if (!empty($query)) {
            parse_str($query, $queryStr);
        }

        // Generic Dispatcher for RestServer
        $dispatcher = $routeDefinition->getDispatcher();
        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

        // Get OutputProcessor
        $outputProcessor = $this->initializeProcessor(
            $response,
            $request,
            null //$middlewareResult->getOutputProcessorClass()
        );
        
        // Process Before Middleware
        try {
            $middlewareResult = MiddlewareManagement::processBefore(
                $this->beforeMiddlewareList,
                $routeInfo[0],
                $response,
                $request
            );
        } catch (\Exception $ex) {
            $outputProcessor->processResponse($response);
            throw $ex;
        }
        
        if ($middlewareResult->getStatus() != MiddlewareResult::CONTINUE) {
            $outputProcessor->processResponse($response);
            return true;
        }

        // Processing
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                $outputProcessor->processResponse($response);
                throw new Error404Exception("Route '$uri' not found");

            case Dispatcher::METHOD_NOT_ALLOWED:
                $outputProcessor->processResponse($response);
                throw new Error405Exception('Method not allowed');

            case Dispatcher::FOUND:
                // ... 200 Process:
                $vars = array_merge($routeInfo[2], $queryStr);

                // Get the Selected Route
                $selectedRoute = $routeInfo[1];

                // Class
                $class = $selectedRoute["class"];
                $request->appendVars($vars);

                // Get OutputProcessor
                $outputProcessor = $this->initializeProcessor(
                    $response,
                    $request,
                    $selectedRoute["output_processor"]
                );
                
                // Execute the request
                $this->executeRequest($outputProcessor, $class, $response, $request);

                break;

            default:
                throw new Error520Exception('Unknown');
        }
    }

    protected function initializeProcessor(HttpResponse $response, HttpRequest $request, $class = null)
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

        ErrorHandler::getInstance()->setOutputProcessor($outputProcessor, $response);
        
        return $outputProcessor;
    }

    protected function getHttpRequest()
    {
        return new HttpRequest($_GET, $_POST, $_SERVER, isset($_SESSION) ? $_SESSION : [], $_COOKIE);
    }

    /**
     * @param OutputProcessorInterface $outputProcessor
     * @param $class
     * @param HttpRequest $request
     * @throws ClassNotFoundException
     * @throws InvalidClassException
     */
    protected function executeRequest(
        OutputProcessorInterface $outputProcessor,
        $class,
        HttpResponse $response,
        HttpRequest $request
    )
    {
        // Process Closure
        if ($class instanceof Closure) {
            $class($response, $request);
            $outputProcessor->processResponse($response);
            return;
        }

        // Process Class::Method()
        $function = $class[1];
        $class =  $class[0];
        if (!class_exists($class)) {
            throw new ClassNotFoundException("Class '$class' defined in the route is not found");
        }
        $instance = new $class();
        if (!method_exists($instance, $function)) {
            throw new InvalidClassException("There is no method '$class::$function''");
        }
        $instance->$function($response, $request);

        MiddlewareManagement::processAfter(
            $this->afterMiddlewareList,
            $response,
            $request
        );
        
        $outputProcessor->processResponse($response);
    }

    /**
     * Handle the ROUTE (see web/app-dist.php)
     *
     * @param RouteListInterface $routeDefinition
     * @param bool $outputBuffer
     * @param bool $session
     * @return bool|void
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
        if (!($processor instanceof \Closure)) {
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
