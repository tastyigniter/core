<?php

declare(strict_types=1);

namespace Igniter\Flame\Pagic\Exception;

use RuntimeException;

class CreateFileException extends RuntimeException
{
    /**
     * Name of the affected file path.
     */
    protected string $invalidPath;

    /**
     * Set the affected file path.
     */
    public function setInvalidPath(string $path): self
    {
        $this->invalidPath = $path;

        $this->message = "Error creating file [{$path}]. Please check write permissions.";

        return $this;
    }
}
