<?php
/**
 * Whoops - php errors for cool kids
 * @author Filipe Dobreira <http://github.com/filp>
 */

namespace ByJG\RestServer\Whoops;

use ByJG\RestServer\Exception\HttpResponseException;
use Whoops\Exception\Formatter;
use Whoops\Handler\Handler;
use Whoops\Handler\JsonResponseHandler as ParentJsonErrorHandler;

/**
 * Catches an exception and converts it to a JSON
 * response. Additionally, can also return exception
 * frames for consumption by an API.
 */
class JsonLimitedResponseHandler extends ParentJsonErrorHandler
{

    use WhoopsDebugTrait;
    use ClassNameBeautifier;

    /**
     * @return int
     * @throws \ReflectionException
     */
    public function handle()
    {
        $errorData = Formatter::formatExceptionAsDataArray(
            $this->getInspector(),
            false
        );

        $title = $this->getClassAsTitle($errorData["type"]);

        $response = [
            'error' => [
                "type" => $title,
                "message" => $errorData["message"]
            ]
        ];

        $exception = $this->getInspector()->getException();
        if ($exception instanceof HttpResponseException && !empty($exception->getMeta())) {
            $response['error']['meta'] = $exception->getMeta();
        }

        echo json_encode($response, defined('JSON_PARTIAL_OUTPUT_ON_ERROR') ? JSON_PARTIAL_OUTPUT_ON_ERROR : 0);

        return Handler::QUIT;
    }
}
