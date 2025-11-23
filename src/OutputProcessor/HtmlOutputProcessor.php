<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\Serializer\Formatter\FormatterInterface;
use ByJG\Serializer\Formatter\PlainTextFormatter;
use Override;

class HtmlOutputProcessor extends BaseOutputProcessor
{
    public function __construct()
    {
        $this->contentType = "text/html";
    }

    /**
     * @return PlainTextFormatter
     */
    #[Override]
    public function getFormatter(): FormatterInterface
    {
        return new PlainTextFormatter();
    }
}
