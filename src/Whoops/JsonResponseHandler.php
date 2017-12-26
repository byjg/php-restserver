<?php
/**
 * Whoops - php errors for cool kids
 * @author Filipe Dobreira <http://github.com/filp>
 */

namespace ByJG\RestServer\Whoops;

use Whoops\Exception\Formatter;
use Whoops\Handler\Handler;
use Whoops\Handler\JsonResponseHandler as OriginalJsonHandler;

/**
 * Catches an exception and converts it to a JSON
 * response. Additionally can also return exception
 * frames for consumption by an API.
 */
class JsonResponseHandler extends OriginalJsonHandler
{

    use WhoopsDebugTrait;
    use WhoopsHeaderTrait;

    /**
     * @return int
     */
    public function handle()
    {
        $response = array(
            'error' => Formatter::formatExceptionAsDataArray(
                $this->getInspector(),
                $this->addTraceToOutput()
            ),
        );

        $debug = $this->getDataTable();
        if (count($debug) > 0) {
            $response["debug"] = $debug;
        }

        $this->setProperHeader($this->getException());

        echo json_encode($response, defined('JSON_PARTIAL_OUTPUT_ON_ERROR') ? JSON_PARTIAL_OUTPUT_ON_ERROR : 0);

        return Handler::QUIT;
    }
}
