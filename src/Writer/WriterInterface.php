<?php

namespace ByJG\RestServer\Writer;

interface WriterInterface
{
    public function header($header, $replace = true);

    public function responseCode($responseCode, $description);

    public function echo($data);

    public function flush();
}
