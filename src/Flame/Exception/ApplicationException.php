<?php

declare(strict_types=1);

namespace Igniter\Flame\Exception;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ApplicationException extends Exception
{
    /**
     * @param string $message Error message.
     * @param int $code Error code.
     * @param Exception|null $previous Previous exception.
     */
    public function __construct(string $message = '', int $code = 500, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
