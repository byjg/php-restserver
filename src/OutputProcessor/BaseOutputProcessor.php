<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\SerializationRuleEnum;
use ByJG\RestServer\Writer\WriterInterface;
use Override;

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

    public static array $mimeTypeOutputProcessor = [
        "text/xml" => XmlOutputProcessor::class,
        "application/xml" => XmlOutputProcessor::class,
        "text/html" => HtmlOutputProcessor::class,
        "text/csv" => CsvOutputProcessor::class,
        "application/json" => JsonOutputProcessor::class,
        "*/*" => JsonOutputProcessor::class,
    ];

    public static function getFromContentType(string $contentType): ?string
    {
        return self::$mimeTypeOutputProcessor[$contentType] ?? null;
    }

    /**
     * @return OutputProcessorInterface
     */
    protected static function getFromHttpAccept(): OutputProcessorInterface
    {
        $accept = $_SERVER["HTTP_ACCEPT"] ?? "application/json";

        $acceptList = explode(",", $accept);

        return self::getFromClassName(self::getFromContentType($acceptList[0]));
    }

    /**
     * @param string|null $className
     * @return object|null
     */
    protected static function getFromClassName(string|null $className): ?object
    {
        if (empty($className)) {
            return null;

        }
        return new $className();
    }

    public static function getOutputProcessorInstance($contentType): object
    {
        $class = self::getFromContentType($contentType);

        return new $class();
    }

    #[Override]
    public function writeContentType(): void
    {
        $this->writer->header("Content-Type: $this->contentType", true);
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

    #[Override]
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

    public static function factory(string|array|null $class = null): OutputProcessorInterface|null
    {
        $outputProcessor = null;
        if (empty($class)) {
            $outputProcessor = BaseOutputProcessor::getFromHttpAccept();
        } else {
            foreach ((array)$class as $className) {
                $outputProcessor = BaseOutputProcessor::getFromClassName($class);
                if (!empty($outputProcessor)) {
                    break;
                }
            }
        }

        return $outputProcessor;
    }
}
