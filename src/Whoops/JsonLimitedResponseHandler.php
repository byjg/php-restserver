<?php
/**
 * Whoops - php errors for cool kids
 * @author Filipe Dobreira <http://github.com/filp>
 */

namespace ByJG\RestServer\Whoops;

use Whoops\Exception\Formatter;
use Whoops\Handler\Handler;
use Whoops\Handler\JsonResponseHandler as ParentJsonErrorHandler;

/**
 * Catches an exception and converts it to a JSON
 * response. Additionally can also return exception
 * frames for consumption by an API.
 */
class JsonLimitedResponseHandler extends ParentJsonErrorHandler
{

    use WhoopsDebugTrait;
    use WhoopsHeaderTrait;

    /**
     * @return int
     */
    public function handle()
    {
        $errorData = Formatter::formatExceptionAsDataArray(
            $this->getInspector(),
            false
        );

        $refClass = new \ReflectionClass($errorData["type"]);
        $className = $refClass->getShortName();

        $response = array(
            'error' => [
                "type" => $className,
                "message" => $errorData["message"]
            ]
        );

        $this->setProperHeader($this->getException());

        echo json_encode($response, defined('JSON_PARTIAL_OUTPUT_ON_ERROR') ? JSON_PARTIAL_OUTPUT_ON_ERROR : 0);

        return Handler::QUIT;
    }
}
