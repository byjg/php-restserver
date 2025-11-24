<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\Serializer\Formatter\FormatterInterface;
use ByJG\Serializer\Formatter\PlainTextFormatter;
use Override;

class PlainTextOutputProcessor extends BaseOutputProcessor
{
    public function __construct()
    {
        $this->contentType = "text/plain";
    }

    #[Override]
    public function getFormatter(): FormatterInterface
    {
        return new PlainTextFormatter();
    }
}