<?php

namespace ByJG\RestServer;

use ByJG\RestServer\Exception\ClassNotFoundException;
use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\Exception\Error405Exception;
use ByJG\RestServer\Exception\Error520Exception;
use ByJG\RestServer\Exception\InvalidClassException;
use ByJG\RestServer\OutputProcessor\BaseOutputProcessor;
use ByJG\RestServer\OutputProcessor\OutputProcessorInterface;
use ByJG\RestServer\Route\RouteDefinition;
use ByJG\RestServer\Route\RouteDefinitionInterface;
use Closure;
use FastRoute\Dispatcher;

class HttpRequestHandler implements RequestHandler
{
    const OK = "OK";
    const METHOD_NOT_ALLOWED = "NOT_ALLOWED";
    const NOT_FOUND = "NOT FOUND";

    protected $useErrorHandler = true;

    /**
     * @param RouteDefinition $routeDefinition
     * @throws ClassNotFoundException
     * @throws Error404Exception
     * @throws Error405Exception
     * @throws Error520Exception
     * @throws InvalidClassException
     */
    protected function process(RouteDefinition $routeDefinition)
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
        parse_str(parse_url($request->server('REQUEST_URI'), PHP_URL_QUERY), $queryStr);

        // Generic Dispatcher for RestServer
        $dispatcher = $routeDefinition->getDispatcher();

        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

        // Processing
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new Error404Exception("404 Route '$uri' Not found");

            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new Error405Exception('405 Method Not Allowed');

            case Dispatcher::FOUND:
                // ... 200 Process:
                $vars = array_merge($routeInfo[2], $queryStr);

                // Get the Selected Route
                $selectedRoute = $routeInfo[1];

                // Default Handler for errors
                $outputProcessor = BaseOutputProcessor::getFromClassName($selectedRoute["output_processor"]);

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
        // Write Header info
        $outputProcessor->writeContentType();
        ErrorHandler::getInstance()->setHandler($outputProcessor->getErrorHandler());

        // Create the Request and Response methods
        $response = new HttpResponse();

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

        if (!$this->deliveryPhysicalFile()) {
            return $this->process($routeDefinition);
        }

        return true;
    }

    protected function deliveryPhysicalFile()
    {
        $debugBacktrace =  debug_backtrace();
        if (!empty($_SERVER['SCRIPT_FILENAME'])
            && file_exists($_SERVER['SCRIPT_FILENAME'])
            && basename($_SERVER['SCRIPT_FILENAME']) !== basename($debugBacktrace[1]['file'])
        ) {
            $file = $_SERVER['SCRIPT_FILENAME'];
            if (strrchr($file, '.') === ".php") {
                require_once($file);
            } else {
                if (!defined("RESTSERVER_TEST")) {
                    header("Content-Type: " . $this->mimeContentType($file));
                }

                echo file_get_contents($file);
            }
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

        $mimeTypes = array(
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
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
        );

        if (!file_exists($filename)) {
            throw new Error404Exception();
        }

        $ext = substr(strrchr($filename, "."), 1);
        if (array_key_exists($ext, $mimeTypes)) {
            return $mimeTypes[$ext];
        } elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            return $mimetype;
        } else {
            return 'application/octet-stream';
        }
    }
}
