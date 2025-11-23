<?php

namespace ByJG\RestServer\Handler;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use Throwable;

/**
 * Interface for handling exceptions and errors in REST responses
 */
interface ErrorHandlerInterface
{
    /**
     * Handle an exception and output appropriate error response
     *
     * @param Throwable $exception The exception to handle
     * @param HttpResponse $response The HTTP response object
     * @param HttpRequest $request The HTTP request object
     * @param bool $detailed Whether to include detailed error information (stack trace, etc.)
     * @return void
     */
    public function handle(Throwable $exception, HttpResponse $response, HttpRequest $request, bool $detailed = false): void;
}