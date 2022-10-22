<?php

namespace ByJG\RestServer\Middleware;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\OutputProcessor\HtmlOutputProcessor;
use ByJG\RestServer\ResponseBag;
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

        $file = $_SERVER['SCRIPT_FILENAME'];
        if (!empty($file) && file_exists($file)) {
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
            return null;
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

        return null;
    }

}
