<?php

namespace Igniter\Flame\Exception;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ApplicationException extends BaseException
{
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
