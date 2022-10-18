<?php

namespace ByJG\RestServer;

use ByJG\RestServer\Exception\ClassNotFoundException;
use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\Exception\Error405Exception;
use ByJG\RestServer\Exception\Error520Exception;
use ByJG\RestServer\Exception\InvalidClassException;
use ByJG\RestServer\OutputProcessor\BaseOutputProcessor;
use ByJG\RestServer\OutputProcessor\OutputProcessorInterface;
use ByJG\RestServer\Route\RouteDefinitionInterface;
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
    protected $corsOrigins = [];
    protected $corsMethods = [ 'GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
    protected $corsHeaders = [
        'Authorization',
        'Content-Type',
        'Accept',
        'Origin',
        'User-Agent',
        'Cache-Control',
        'Keep-Alive',
        'X-Requested-With',
        'If-Modified-Since'
    ];

    protected $defaultOutputProcessor = null;
    protected $defaultOutputProcessorArgs = [];

    /**
     * @param RouteDefinitionInterface $routeDefinition
     * @return bool
     * @throws ClassNotFoundException
     * @throws Error404Exception
     * @throws Error405Exception
     * @throws Error520Exception
     * @throws InvalidClassException
     */
    protected function process(RouteDefinitionInterface $routeDefinition)
    {
        // Initialize ErrorHandler with default error handler
        if ($this->useErrorHandler) {
            ErrorHandler::getInstance()->register();
        }

        // Get HttpRequest
        $request = $this->getHttpRequest();

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

        // Processing
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                if ($this->tryDeliveryPhysicalFile() === false) {
                    $this->prepareToOutput();
                    throw new Error404Exception("Route '$uri' not found");
                }
                return true;

            case Dispatcher::METHOD_NOT_ALLOWED:
                $outputProcessor = $this->prepareToOutput();
                if (strtoupper($httpMethod) == "OPTIONS" && !empty($this->corsOrigins)) {
                    $this->executeRequest($outputProcessor, function () {}, $request);
                    return;
                }
                throw new Error405Exception('Method not allowed');

            case Dispatcher::FOUND:
                // ... 200 Process:
                $vars = array_merge($routeInfo[2], $queryStr);

                // Get the Selected Route
                $selectedRoute = $routeInfo[1];

                // Default Handler for errors and
                $outputProcessor = $this->prepareToOutput($selectedRoute["output_processor"]);

                // Class
                $class = $selectedRoute["class"];
                $request->appendVars($vars);

                // Execute the request
                $this->executeRequest($outputProcessor, $class, $request);

                break;

            default:
                throw new Error520Exception('Unknown');
        }
    }

    protected function prepareToOutput($class = null)
    {
        if (!empty($class)) {
            $outputProcessor = BaseOutputProcessor::getFromClassName($class);
        } elseif (!empty($this->defaultOutputProcessor)) {
            $outputProcessor = BaseOutputProcessor::getFromClassName($this->defaultOutputProcessor);
        } else {
            $outputProcessor = BaseOutputProcessor::getFromHttpAccept();
        }
        $outputProcessor->writeContentType();
        if ($this->detailedErrorHandler) {
            ErrorHandler::getInstance()->setHandler($outputProcessor->getDetailedErrorHandler());
        } else {
            ErrorHandler::getInstance()->setHandler($outputProcessor->getErrorHandler());
        }

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
    protected function executeRequest(OutputProcessorInterface $outputProcessor, $class, HttpRequest $request)
    {
        // Create the Request and Response methods
        $response = new HttpResponse();

        if (!empty($this->corsOrigins)) {
            // Allow from any origin
            if (!empty($request->server('HTTP_ORIGIN'))) {
                foreach ((array)$this->corsOrigins as $origin) {
                    if (preg_match("~^.*//$origin$~", $request->server('HTTP_ORIGIN'))) {
                        $response->addHeader("Access-Control-Allow-Origin", $request->server('HTTP_ORIGIN'));
                        $response->addHeader('Access-Control-Allow-Credentials', 'true');
                        $response->addHeader('Access-Control-Max-Age', '86400');    // cache for 1 day

                        // Access-Control headers are received during OPTIONS requests
                        if ($request->server('REQUEST_METHOD') == 'OPTIONS') {
                            $response->addHeader("Access-Control-Allow-Methods", implode(",", array_merge(['OPTIONS'], $this->corsMethods)));
                            $response->addHeader("Access-Control-Allow-Headers", implode(",", $this->corsHeaders));
                            $outputProcessor->processResponse($response);
                            return;
                        }
                        break;
                    }
                }
            }
        }

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
        $outputProcessor->processResponse($response);
    }

    /**
     * Handle the ROUTE (see web/app-dist.php)
     *
     * @param RouteDefinitionInterface $routeDefinition
     * @param bool $outputBuffer
     * @param bool $session
     * @return bool|void
     * @throws ClassNotFoundException
     * @throws Error404Exception
     * @throws Error405Exception
     * @throws Error520Exception
     * @throws InvalidClassException
     */
    public function handle(RouteDefinitionInterface $routeDefinition, $outputBuffer = true, $session = false)
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

    /**
     * @return bool
     * @throws Error404Exception
     */
    protected function tryDeliveryPhysicalFile()
    {
        $file = $_SERVER['SCRIPT_FILENAME'];
        if (!empty($file) && file_exists($file)) {
            $mime = $this->mimeContentType($file);

            if ($mime === false) {
                return false;
            }

            if (!defined("RESTSERVER_TEST")) {
                header("Content-Type: $mime");
            }
            echo file_get_contents($file);
            return true;
        }

        return false;
    }

    /**
     * Get the Mime Type based on the filename
     *
     * @param string $filename
     * @return string
     * @throws Error404Exception
     */
    protected function mimeContentType($filename)
    {
        $prohibitedTypes = [
            "php",
            "vb",
            "cs",
            "rb",
            "py",
            "py3",
            "lua"
        ];

        $mimeTypes = [
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',
            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',
            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',
            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        ];

        if (!file_exists($filename)) {
            throw new Error404Exception();
        }

        $ext = substr(strrchr($filename, "."), 1);
        if (!in_array($ext, $prohibitedTypes)) {
            if (array_key_exists($ext, $mimeTypes)) {
                return $mimeTypes[$ext];
            } elseif (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME);
                $mimetype = finfo_file($finfo, $filename);
                finfo_close($finfo);
                return $mimetype;
            }
        }

        return false;
    }

    public function withDoNotUseErrorHandler()
    {
        $this->useErrorHandler = false;
    }

    public function withDetailedErrorHandler()
    {
        $this->detailedErrorHandler = true;
    }

    public function withCorsOrigins($origins)
    {
        $this->corsOrigins = $origins;
        return $this;
    }

    public function withAcceptCorsHeaders($headers)
    {
        $this->corsHeaders = $headers;
        return $this;
    }

    public function withAcceptCorsMethods($methods)
    {
        $this->corsMethods = $methods;
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
}
