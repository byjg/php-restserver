<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\RestServer\Exception\HttpResponseException;
use ByJG\RestServer\Handler\ExceptionFormatter;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use Override;
use Throwable;

class JsonTwirpOutputProcessor extends JsonOutputProcessor
{
    /**
     * Handle Twirp-specific error formatting
     *
     * @param Throwable $exception
     * @param HttpResponse $response
     * @param HttpRequest $request
     * @param bool $detailed
     * @return void
     */
    #[Override]
    public function handle(Throwable $exception, HttpResponse $response, HttpRequest $request, bool $detailed = false): void
    {
        $this->getLogData($exception, $response, $request);

        // Map HTTP status code to Twirp error code
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

        // Format exception data
        $errorData = ExceptionFormatter::format($exception, false);

        $error = [
            'code' => $twirpCode,
            'msg' => $errorData['message'],
        ];

        // Add meta if available from HttpResponseException
        if ($exception instanceof HttpResponseException && !empty($exception->getMeta())) {
            $error['meta'] = $exception->getMeta();
        }

        // Output formatted error
        $this->writeData($this->getFormatter()->process($error));
    }
}
