<?php

namespace ByJG\RestServer\Writer;

class StdoutWriter implements WriterInterface
{
    protected $headerList = [];
    protected $data = '';
    protected $statusCode = 0;

    public function header($header, $replace = true)
    {
        if (preg_match("~^HTTP/~", $header) === 1) {
            array_unshift($this->headerList, $header);
            return;
        }

        if ($replace) {
            $headerParts = explode(':', $header);
            for ($i=0; $i<count($this->headerList); $i++) {
                if (preg_match("~^" . $headerParts[0] . "~", $this->headerList[$i]) === 1) {
                    $this->headerList[$i] = $header;
                    return;
                }
            }
        }

        $this->headerList[] = $header;
    }

    public function responseCode($responseCode, $description)
    {
        $this->header("HTTP/1.1 $responseCode $description");
        $this->statusCode = $responseCode;
    }

    public function echo($data)
    {
        $this->data .= $data;
    }

    public function flush()
    {
        echo implode("\r\n", $this->headerList);
        echo "\r\n";
        echo "\r\n";
        echo $this->data;
    }
}