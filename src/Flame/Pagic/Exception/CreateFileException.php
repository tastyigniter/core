<?php

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

    /**
     * Get the affected file path.
     */
    public function getInvalidPath(): string
    {
        return $this->invalidPath;
    }
}
