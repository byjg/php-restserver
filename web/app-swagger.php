<?php

require_once __DIR__ . '/../vendor/autoload.php';

$restServer = new \ByJG\RestServer\ServerRequestHandler();

$restServer->setRoutesSwagger(__DIR__ . '/../tests/swagger-example.json');

$restServer->handle();
