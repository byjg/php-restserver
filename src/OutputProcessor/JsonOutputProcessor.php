<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\Serializer\Formatter\FormatterInterface;
use ByJG\Serializer\Formatter\JsonFormatter;
use Override;

class JsonOutputProcessor extends BaseOutputProcessor
{
    public function __construct()
    {
        $this->contentType = "application/json";
    }

    /**
     * @return JsonFormatter
     */
    #[Override]
    public function getFormatter(): FormatterInterface
    {
        return new JsonFormatter();
    }
}
