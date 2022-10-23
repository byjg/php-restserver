<?php

namespace ByJG\RestServer\Middleware;

use ByJG\RestServer\Exception\Error415Exception;
use ByJG\RestServer\Exception\Error500Exception;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\OutputProcessor\HtmlOutputProcessor;
use ByJG\RestServer\ResponseBag;
use ByJG\Util\Uri;
use FastRoute\Dispatcher;

class ServerStaticMiddleware implements BeforeMiddlewareInterface
{
    /**
     * Undocumented function
     *
     * @param mixed $dispatcherStatus
     * @param HttpResponse $response
     * @param HttpRequest $request
     * @return MiddlewareResult
     */
    public function beforeProcess(
        $dispatcherStatus,
        HttpResponse $response,
        HttpRequest $request
    )
    {
        if ($dispatcherStatus != Dispatcher::NOT_FOUND) {
            return MiddlewareResult::continue();
        }

        $requestUri = new Uri($_SERVER['REQUEST_URI']);
        if ($requestUri->getScheme() === "file") {
            $file = $requestUri->getPath();
        } else {
            $script = explode('/', $_SERVER['SCRIPT_FILENAME']);
            $script[count($script)-1] = ltrim($requestUri->getPath(), '/');
            $file = implode('/', $script);
        }

        if (file_exists($file)) {
            $mime = $this->mimeContentType($file);

            if (empty($mime)) {
                return MiddlewareResult::continue();
            }

            $response->addHeader("Content-Type", $mime);
            $response->emptyResponse();
            $response->getResponseBag()->setSerializationRule(ResponseBag::RAW);
            $response->write(file_get_contents($file));
            return MiddlewareResult::stopProcessingOthers(HtmlOutputProcessor::class);
        }

        return MiddlewareResult::continue();
    }

    /**
     * Get the Mime Type based on the filename
     *
     * @param string $filename
     * @return string
     */
    public function mimeContentType($filename)
    {
        if (!file_exists($filename)) {
            return null;
        }

        $prohibitedTypes = [
            "php",
            "vb",
            "cs",
            "rb",
            "py",
            "py3",
            "lua"
        ];

        $ext = substr(strrchr($filename, "."), 1);
        if (in_array($ext, $prohibitedTypes)) {
            throw new Error415Exception("File type not supported");
        }

        if (!function_exists('finfo_open')) {
            throw new Error500Exception("ServerStaticMiddleware requires finfo extension");
        }

        $finfo = finfo_open(FILEINFO_MIME);
        $mimetype = finfo_file($finfo, $filename);
        finfo_close($finfo);
        return $mimetype;
    }

}
