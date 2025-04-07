<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\RestServer\Exception\OperationIdInvalidException;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\SerializationRuleEnum;
use ByJG\RestServer\Writer\WriterInterface;
use Closure;

abstract class BaseOutputProcessor implements OutputProcessorInterface
{
    protected bool $buildNull = true;
    protected bool $onlyString = false;
    protected array $header = [];
    protected string $contentType = "";

    protected WriterInterface $writer;

    #[\Override]
    public function setWriter(WriterInterface $writer): void
    {
        $this->writer = $writer;
    }

    public static function getFromContentType(string $contentType): string
    {
        $mimeTypeOutputProcessor = [
            "text/xml" => XmlOutputProcessor::class,
            "application/xml" => XmlOutputProcessor::class,
            "text/html" => HtmlOutputProcessor::class,
            "application/json" => JsonOutputProcessor::class,
            "*/*" => JsonOutputProcessor::class,
        ];

        if (!isset($mimeTypeOutputProcessor[$contentType])) {
            throw new OperationIdInvalidException("There is no output processor for $contentType");
        }

        return $mimeTypeOutputProcessor[$contentType];
    }

    /**
     * @throws OperationIdInvalidException
     */
    public static function getFromHttpAccept(): OutputProcessorInterface
    {
        $accept = $_SERVER["HTTP_ACCEPT"] ?? "application/json";
        
        $acceptList = explode(",", $accept);
        
        return self::getFromClassName(self::getFromContentType($acceptList[0]));
    }

    /**
     * @param Closure|string $className
     * @return OutputProcessorInterface
     */
    public static function getFromClassName(Closure|string $className): object
    {
        if ($className instanceof Closure) {
            return $className();
        }
        return new $className();
    }

    public static function getOutputProcessorInstance($contentType): object
    {
        $class = self::getFromContentType($contentType);

        return new $class();
    }

    #[\Override]
    public function writeContentType(): void
    {
        $this->writer->header("Content-Type: $this->contentType", true);
    }

    #[\Override]
    public function getContentType(): string
    {
        return $this->contentType;
    }

    #[\Override]
    public function writeHeader(HttpResponse $response): void
    {
        $this->writer->responseCode($response->getResponseCode(), $response->getResponseCodeDescription());

        foreach ($response->getHeaders() as $header => $value) {
            if (is_array($value)) {
                $this->writer->header("$header: " . array_shift($value), true);
                foreach ($value as $headerValue) {
                    $this->writer->header("$header: $headerValue", false);
                }
            } else {
                $this->writer->header("$header: $value", true);
            }
        }
    }

    public function writeData(string|bool $data): void
    {
        if (is_bool($data)) {
            $this->writer->echo($data ? 'true' : 'false');
        } else {
            $this->writer->echo($data);
        }
    }

    #[\Override]
    public function processResponse(HttpResponse $response): void
    {
        $this->writeHeader($response);

        $serialized = $response
            ->getResponseBag()
            ->process($this->buildNull, $this->onlyString);

        if ($response->getResponseBag()->getSerializationRule() === SerializationRuleEnum::Raw) {
            $this->writeData($serialized);
        } else {
            $this->writeData(
                $this->getFormatter()->process($serialized)
            );
        }

        $this->writer->flush();
    }
}
