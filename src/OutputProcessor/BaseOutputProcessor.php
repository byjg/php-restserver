<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\RestServer\ErrorHandler;
use ByJG\RestServer\Exception\HttpResponseException;
use ByJG\RestServer\Exception\OperationIdInvalidException;
use ByJG\RestServer\Handler\ExceptionFormatter;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\SerializationRuleEnum;
use ByJG\RestServer\Writer\WriterInterface;
use Override;
use Throwable;

abstract class BaseOutputProcessor implements OutputProcessorInterface
{
    protected bool $buildNull = true;
    protected bool $onlyString = false;
    protected array $header = [];
    protected string $contentType = "";

    protected WriterInterface $writer;

    #[Override]
    public function setWriter(WriterInterface $writer): void
    {
        $this->writer = $writer;
    }

    /**
     * @throws OperationIdInvalidException
     */
    public static function getFromContentType(string $contentType): string
    {
        $mimeTypeOutputProcessor = [
            "text/xml" => XmlOutputProcessor::class,
            "text/csv" => CsvOutputProcessor::class,
            "application/xml" => XmlOutputProcessor::class,
            "text/html" => HtmlOutputProcessor::class,
            "application/json" => JsonOutputProcessor::class,
            "text/plain" => PlainTextOutputProcessor::class,
            "*/*" => JsonOutputProcessor::class,
        ];

        if (!isset($mimeTypeOutputProcessor[$contentType])) {
            throw new OperationIdInvalidException("There is no output processor for $contentType");
        }

        return $mimeTypeOutputProcessor[$contentType];
    }

    /**
     * @return OutputProcessorInterface|null
     * @throws OperationIdInvalidException
     */
    protected static function getFromHttpAccept(): OutputProcessorInterface|null
    {
        $accept = $_SERVER["HTTP_ACCEPT"] ?? "application/json";

        $acceptList = explode(",", $accept);

        return self::getFromClassName(self::getFromContentType($acceptList[0]));
    }

    /**
     * @param string|null $className
     * @return OutputProcessorInterface|null
     */
    protected static function getFromClassName(string|null $className): ?OutputProcessorInterface
    {
        if (empty($className)) {
            return null;

        }
        /** @var class-string<OutputProcessorInterface> $className */
        return new $className();
    }

    #[Override]
    public function writeContentType(): void
    {
        $this->writer->header("Content-Type: $this->contentType");
    }

    #[Override]
    public function getContentType(): string
    {
        return $this->contentType;
    }

    #[Override]
    public function writeHeader(HttpResponse $response): void
    {
        $this->writer->responseCode($response->getResponseCode(), $response->getResponseCodeDescription());

        foreach ($response->getHeaders() as $header => $value) {
            if (is_array($value)) {
                $this->writer->header("$header: " . array_shift($value));
                foreach ($value as $headerValue) {
                    $this->writer->header("$header: $headerValue", false);
                }
            } else {
                $this->writer->header("$header: $value");
            }
        }
    }

    protected function getLogData(Throwable $exception, HttpResponse $response, HttpRequest $request)
    {
        // Set HTTP response code
        if ($exception instanceof HttpResponseException) {
            $exception->setResponse($response);
            $exception->sendHeader();
        } else {
            $response->setResponseCode(500, 'Internal Error');
        }

        // Empty previous response and write headers
        $response->emptyResponse();
        $this->writeHeader($response);

        // Log the error
        $logData = [
            'path' => $request->getRequestPath(),
            'method' => $request->server('REQUEST_METHOD'),
            'trace' => explode("\n", $exception->getTraceAsString())
        ];
        ErrorHandler::getInstance()->getLogger()->error($exception->getMessage(), $logData);
    }

    /**
     * Handle an exception and output appropriate error response
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
        // Set HTTP response code
        $this->getLogData($exception, $response, $request);

        // Format exception data
        $errorData = ExceptionFormatter::format($exception, $detailed);
        $title = ExceptionFormatter::beautifyClassName($errorData['type']);

        $error = [
            'type' => $title,
            'message' => $errorData['message']
        ];

        // Add meta if available from HttpResponseException
        if ($exception instanceof HttpResponseException && !empty($exception->getMeta())) {
            $error['meta'] = $exception->getMeta();
        }

        // Add trace if detailed mode
        if ($detailed && isset($errorData['trace'])) {
            $error['trace'] = $errorData['trace'];
            $error['file'] = $errorData['file'];
            $error['line'] = $errorData['line'];
        }

        // Output formatted error
        $this->writeData($this->getFormatter()->process(['error' => $error]));
    }

    public function writeData(string|bool $data): void
    {
        if (is_bool($data)) {
            $this->writer->echo($data ? 'true' : 'false');
        } else {
            $this->writer->echo($data);
        }
    }

    #[Override]
    public function processResponse(HttpResponse $response): void
    {
        $this->writeHeader($response);

        $serialized = $response
            ->getResponseBag()
            ->process($this->buildNull, $this->onlyString);

        if ($response->getResponseBag()->getSerializationRule() === SerializationRuleEnum::Raw) {
            $this->writeData(is_array($serialized) ? json_encode($serialized) : $serialized);
        } else {
            $this->writeData(
                is_string($serialized) ? $serialized : $this->getFormatter()->process($serialized)
            );
        }

        $this->writer->flush();
    }

    /**
     * @throws OperationIdInvalidException
     */
    public static function factory(OutputProcessorInterface|string|array|null $class = null): OutputProcessorInterface|null
    {
        $outputProcessor = null;
        if (is_object($class)) {
            return $class;
        } elseif (empty($class)) {
            $outputProcessor = BaseOutputProcessor::getFromHttpAccept();
        } elseif (is_array($class)) {
            $currentContentType = BaseOutputProcessor::getFromHttpAccept();
            if ($currentContentType !== null) {
                foreach ($class as $className) {
                    $validProcessor = BaseOutputProcessor::factory($className);
                    if ($validProcessor !== null && $validProcessor->getContentType() === $currentContentType->getContentType()) {
                        $outputProcessor = $validProcessor;
                        break;
                    }
                }
            }
        } else {
            if (str_contains($class, "/")) {
                $class = BaseOutputProcessor::getFromContentType($class);
            }
            $outputProcessor = BaseOutputProcessor::getFromClassName($class);
        }

        return $outputProcessor;
    }
}
