<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\RestServer\Handler\ErrorHandlerInterface;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\Writer\WriterInterface;
use ByJG\Serializer\Formatter\FormatterInterface;

/**
 * Interface for output processors that format REST responses
 *
 * @author jg
 */
interface OutputProcessorInterface extends ErrorHandlerInterface
{
    /**
     * Set the writer for output
     *
     * @param WriterInterface $writer
     * @return void
     */
    public function setWriter(WriterInterface $writer): void;

    /**
     * Write the content-type header
     *
     * @return void
     */
    public function writeContentType(): void;

    /**
     * Get the content type for this processor
     *
     * @return string
     */
    public function getContentType(): string;

    /**
     * Process and output the response
     *
     * @param HttpResponse $response
     * @return void
     */
    public function processResponse(HttpResponse $response): void;

    /**
     * Get the formatter for serializing data
     *
     * @return FormatterInterface
     */
    public function getFormatter(): FormatterInterface;

    /**
     * Write HTTP headers for the response
     *
     * @param HttpResponse $response
     * @return void
     */
    public function writeHeader(HttpResponse $response): void;
}
