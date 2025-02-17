<?php

declare(strict_types=1);

namespace Igniter\Flame\Pagic\Exception;

use RuntimeException;

class InvalidFileNameException extends RuntimeException
{
    /**
     * Name of the affected file name.
     */
    protected string $invalidFileName;

    /**
     * Set the affected file name.
     *
     * @return $this
     */
    public function setInvalidFileName(string $invalidFileName): self
    {
        $this->invalidFileName = $invalidFileName;

        $this->message = "The specified file name [{$invalidFileName}] is invalid.";

        return $this;
    }
}
