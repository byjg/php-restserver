<?php

namespace ByJG\RestServer\HandleOutput;

use ByJG\RestServer\HttpResponse;

abstract class BaseHandler implements HandleOutputInterface
{
    protected $options = [
        'header' => [],
        'build-null' => true,
        'only-string' => false
    ];

    public function option($option, $value)
    {
        $this->options[$option] = $value;
    }

    public function writeHeader($headerList = null)
    {
        if ($headerList === null) {
            $headerList = $this->options['header'];
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
            ->process($this->options['build-null'], $this->options['only-string']);

        $this->writeData(
            $this->getFormatter()->process($serialized)
        );
    }
}
