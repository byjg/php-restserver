<?php
/**
 * Whoops - php errors for cool kids
 * @author Filipe Dobreira <http://github.com/filp>
 */

namespace ByJG\RestServer\Whoops;

use Whoops\Exception\Formatter;
use Whoops\Handler\Handler;

/**
 * Catches an exception and converts it to a JSON
 * response. Additionally can also return exception
 * frames for consumption by an API.
 */
class PlainResponseErrorHandler extends Handler
{

    use WhoopsDebugTrait;
    use ClassNameBeautifier;

    /**
     * @var bool
     */
    private $returnFrames = false;

    /**
     * @param  bool|null $returnFrames
     * @return bool|$this
     */
    public function addTraceToOutput($returnFrames = null)
    {
        if (func_num_args() == 0) {
            return $this->returnFrames;
        }

        $this->returnFrames = (bool)$returnFrames;
        return $this;
    }

    /**
     * @return int
     */
    public function handle()
    {
        $response = Formatter::formatExceptionAsDataArray(
            $this->getInspector(),
            false
        );

        $title = $this->getClassAsTitle($response["type"]);

        echo "<html><h1>${title}</h1><p>${response['message']}</p></html>";

        return Handler::QUIT;
    }
}
