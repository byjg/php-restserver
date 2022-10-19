<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ByJG\RestServer\OutputProcessor;

use ByJG\RestServer\HttpResponse;
use ByJG\Serializer\Formatter\FormatterInterface;
use Whoops\Handler\Handler;

/**
 *
 * @author jg
 */
interface OutputProcessorInterface
{

    /**
     * @return void
     */
    public function writeContentType();

    /**
     * @return string
     */
    public function getContentType();

    /**
     * @param HttpResponse $class
     * @return string
     */
    public function processResponse(HttpResponse $class);

    /**
     * @return Handler
     */
    public function getDetailedErrorHandler();

        /**
     * @return Handler
     */
    public function getErrorHandler();

    /**
     * @return FormatterInterface
     */
    public function getFormatter();

    /**
     * @param HttpResponse $response
     * @return void
     */
    public function writeHeader(HttpResponse $response);
}
