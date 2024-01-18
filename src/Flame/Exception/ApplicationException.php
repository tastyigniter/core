<?php

namespace Igniter\Flame\Exception;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ApplicationException extends \Exception
{
    /**
     * @param string $message Error message.
     * @param int $code Error code.
     * @param \Exception|null $previous Previous exception.
     */
    public function __construct($message = '', $code = 500, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function render(Request $request): Response
    {
        $message = $this->getMessage();

        if (config('app.debug', false)) {
            $message = sprintf('"%s" on line %s of %s',
                $this->getMessage(),
                $this->getLine(),
                $this->getFile()
            );

            $message .= $this->getTraceAsString();
        }

        return response($message, $this->code);
    }
}
