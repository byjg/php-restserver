<?php

namespace ByJG\RestServer\HandleOutput;

use ByJG\RestServer\ServiceAbstract;

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

    public function writeHeader()
    {
        foreach ($this->options['header'] as $header) {
            header($header);
        }
    }

    public function writeOutput(ServiceAbstract $instance)
    {
        $instanceHeaders = $instance->getResponse()->getHeaders();
        foreach ($instanceHeaders as $header) {
            header($header[0], $header[1]);
        }

        http_response_code($instance->getResponse()->getResponseCode());

        $serialized = $instance
            ->getResponse()
            ->getResponseBag()
            ->process($this->options['build-null'], $this->options['only-string']);

        return $this->getFormatter()->process($serialized);
    }
}
