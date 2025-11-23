<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\Serializer\Formatter\FormatterInterface;
use ByJG\Serializer\Formatter\XmlFormatter;
use Override;

class XmlOutputProcessor extends BaseOutputProcessor
{
    public function __construct()
    {
        $this->contentType = "application/xml";
    }

    /**
     * @return XmlFormatter
     */
    #[Override]
    public function getFormatter(): FormatterInterface
    {
        return new XmlFormatter();
    }
}
