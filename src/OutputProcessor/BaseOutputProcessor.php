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
     * @return OutputProcessorInterface|null
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
        return new $className();
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

    public static function factory(OutputProcessorInterface|string|array|null $class = null): OutputProcessorInterface|null
    {
        $outputProcessor = null;
        if (is_object($class)) {
            return $class;
        } elseif (empty($class)) {
            $outputProcessor = BaseOutputProcessor::getFromHttpAccept();
        } elseif (is_array($class)) {
            $currentContentType = BaseOutputProcessor::getFromHttpAccept();
            foreach ($class as $className) {
                $validProcessor = BaseOutputProcessor::factory($className);
                if ($validProcessor->getContentType() === $currentContentType->getContentType()) {
                    $outputProcessor = $validProcessor;
                    break;
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
