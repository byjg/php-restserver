<?php
/**
 * Whoops - php errors for cool kids
 * @author Filipe Dobreira <http://github.com/filp>
 */

namespace ByJG\RestServer\Whoops;

use Whoops\Exception\Formatter;
use Whoops\Handler\Handler;
use Whoops\Handler\XmlResponseHandler;

/**
 * Catches an exception and converts it to an XML
 * response. Additionally can also return exception
 * frames for consumption by an API.
 */
class XmlResponseHandler extends XmlResponseHandler
{
    use \ByJG\RestServer\Whoops\WhoopsDebugTrait;

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
        
        echo $this->toXml($response);

        return Handler::QUIT;
    }

}
