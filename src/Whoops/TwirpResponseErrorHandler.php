<?php
/**
 * Whoops - php errors for cool kids
 * @author Filipe Dobreira <http://github.com/filp>
 */

namespace ByJG\RestServer\Whoops;

use ByJG\RestServer\Exception\HttpResponseException;
use Override;
use Whoops\Exception\Formatter;
use Whoops\Handler\Handler;

/**
 * Catches an exception and converts it to a JSON
 * response. Additionally, can also return exception
 * frames for consumption by an API.
 */
class TwirpResponseErrorHandler extends JsonLimitedResponseHandler
{

    use WhoopsDebugTrait;
    use ClassNameBeautifier;

    /**
     * @return int
     */
    #[Override]
    public function handle()
    {
        $errorData = Formatter::formatExceptionAsDataArray(
            $this->getInspector(),
            false
        );

        $exception = $this->getInspector()->getException();

        $twirpCode = "internal";
        if ($exception instanceof HttpResponseException) {
            $twirpCode = match ($exception->getStatusCode()) {
                408 => "canceled",
                400, 422 => "invalid_argument",
                404 => "not_found",
                403 => "permission_denied",
                401 => "unauthenticated",
                429 => "resource_exhausted",
                412 => "failed_precondition",
                409 => "aborted",
                501 => "unimplemented",
                503 => "unavailable",
                default => "internal"
            };

        }

        $response = array(
            'code' => $twirpCode,
            'msg' => $errorData["message"],
        );

        if ($exception instanceof HttpResponseException && !empty($exception->getMeta())) {
            $response['meta'] = $exception->getMeta();
        }

        echo json_encode($response, defined('JSON_PARTIAL_OUTPUT_ON_ERROR') ? JSON_PARTIAL_OUTPUT_ON_ERROR : 0);

        return Handler::QUIT;
    }
}
