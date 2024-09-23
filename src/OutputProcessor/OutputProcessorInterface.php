<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ByJG\RestServer\OutputProcessor;

use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\Writer\WriterInterface;
use ByJG\Serializer\Formatter\FormatterInterface;
use Whoops\Handler\Handler;

/**
 *
 * @author jg
 */
interface OutputProcessorInterface
{
    /**
     * Undocumented function
     *
     * @param WriterInterface $writer
     * @return void
     */
    public function setWriter(WriterInterface $writer): void;

    /**
     * @return void
     */
    public function writeContentType(): void;

    /**
     * @return string
     */
    public function getContentType(): string;

    /**
     * @param HttpResponse $response
     * @return void
     */
    public function processResponse(HttpResponse $response): void;

    /**
     * @return Handler
     */
    public function getDetailedErrorHandler(): Handler;

        /**
     * @return Handler
     */
    public function getErrorHandler(): Handler;

    /**
     * @return FormatterInterface
     */
    public function getFormatter(): FormatterInterface;

    /**
     * @param HttpResponse $response
     * @return void
     */
    public function writeHeader(HttpResponse $response): void;
}
