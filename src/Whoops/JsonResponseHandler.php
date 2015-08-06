<?php
/**
 * Whoops - php errors for cool kids
 * @author Filipe Dobreira <http://github.com/filp>
 */

namespace ByJG\RestServer\Whoops;

use Whoops\Exception\Formatter;
use Whoops\Handler\Handler;
use Whoops\Handler\JsonResponseHandler as OriginalJsonHandler;
use Whoops\Util\Misc;

/**
 * Catches an exception and converts it to a JSON
 * response. Additionally can also return exception
 * frames for consumption by an API.
 */
class JsonResponseHandler extends OriginalJsonHandler
{
    use \ByJG\RestServer\Whoops\WhoopsDebugTrait;


    /**
     * @return int
     */
    public function handle()
    {
        if ($this->onlyForAjaxRequests() && !$this->isAjaxRequest()) {
            return Handler::DONE;
        }

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

        if (Misc::canSendHeaders()) {
            header('Content-Type: application/json');
        }

        echo json_encode($response);
        return Handler::QUIT;
    }
}
