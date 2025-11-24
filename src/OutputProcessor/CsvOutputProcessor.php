<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\Serializer\Formatter\CsvFormatter;
use ByJG\Serializer\Formatter\FormatterInterface;
use Override;

class CsvOutputProcessor extends BaseOutputProcessor
{
    public function __construct()
    {
        $this->contentType = "text/csv";
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
