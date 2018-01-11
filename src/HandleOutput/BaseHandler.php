<?php

namespace ByJG\RestServer\HandleOutput;

use ByJG\RestServer\HttpResponse;

abstract class BaseHandler implements HandleOutputInterface
{
    protected $buildNull = true;
    protected $onlyString = false;
    protected $header = [];

    public function writeHeader($headerList = null)
    {
        if ($headerList === null) {
            $headerList = $this->header;
        }
        foreach ($headerList as $header) {
            if (is_array($header)) {
                header($header[0], $header[1]);
                continue;
            }
            header($header);
        }
    }

    public function writeData($data)
    {
        echo $data;
    }

    public function processResponse(HttpResponse $response)
    {
        $instanceHeaders = $response->getHeaders();
        $this->writeHeader($instanceHeaders);

        http_response_code($response->getResponseCode());

        $serialized = $response
            ->getResponseBag()
            ->process($this->buildNull, $this->onlyString);

        $this->writeData(
            $this->getFormatter()->process($serialized)
        );
    }
}
