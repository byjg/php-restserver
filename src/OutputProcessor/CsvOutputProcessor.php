<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\Serializer\Formatter\CsvFormatter;
use ByJG\Serializer\Formatter\FormatterInterface;
use Override;
use Whoops\Handler\Handler;
use Whoops\Handler\PlainTextHandler;

class CsvOutputProcessor extends BaseOutputProcessor
{
    public function __construct()
    {
        $this->contentType = "text/csv";
    }

    /**
     * @return PlainTextHandler
     */
    #[Override]
    public function getDetailedErrorHandler(): Handler
    {
        return new PlainTextHandler();
    }

    #[Override]
    public function getErrorHandler(): Handler
    {
        return $this->getDetailedErrorHandler();
    }

    /**
     * @return FormatterInterface
     */
    #[Override]
    public function getFormatter(): FormatterInterface
    {
        return new CsvFormatter();
    }
}
