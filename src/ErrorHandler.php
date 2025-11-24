<?php

namespace ByJG\RestServer;

use ByJG\DesignPattern\Singleton;
use ByJG\RestServer\OutputProcessor\OutputProcessorInterface;
use ErrorException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

class ErrorHandler
{
    use Singleton;

    protected LoggerInterface $logger;

    protected ?OutputProcessorInterface $outputProcessor = null;

    protected ?HttpResponse $response = null;

    protected ?HttpRequest $request = null;

    protected bool $detailed = false;

    protected bool $registered = false;

    protected function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * Set the output processor and context for error handling
     *
     * @param OutputProcessorInterface $processor
     * @param HttpResponse $response
     * @param HttpRequest $request
     * @param bool $detailed
     */
    public function setOutputProcessor(
        OutputProcessorInterface $processor,
        HttpResponse             $response,
        HttpRequest              $request,
        bool                     $detailed = false
    ): void
    {
        $this->outputProcessor = $processor;
        $this->response = $response;
        $this->request = $request;
        $this->detailed = $detailed;
    }

    /**
     * Register as the default error and exception handler
     */
    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        $this->registered = true;
    }

    /**
     * Unregister as the default error and exception handler
     */
    public function unregister(): void
    {
        if (!$this->registered) {
            return;
        }

        restore_error_handler();
        restore_exception_handler();
        $this->registered = false;
    }

    /**
     * Handle PHP errors by converting them to ErrorExceptions
     */
    public function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (!(error_reporting() & $level)) {
            // This error code is not included in error_reporting
            return false;
        }

        throw new ErrorException($message, 0, $level, $file, $line);
    }

    /**
     * Handle exceptions
     */
    public function handleException(Throwable $exception): void
    {
        if ($this->outputProcessor && $this->response && $this->request) {
            $this->outputProcessor->handle($exception, $this->response, $this->request, $this->detailed);
        } else {
            // Fallback if no output processor is set
            $this->logger->error($exception->getMessage(), [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => explode("\n", $exception->getTraceAsString())
            ]);

            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json');
            }

            echo json_encode([
                'error' => [
                    'type' => 'Internal Error',
                    'message' => $exception->getMessage()
                ]
            ]);
        }
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
