<?php

namespace Igniter\Flame\Pagic\Exception;

use RuntimeException;

class CreateDirectoryException extends RuntimeException
{
    /**
     * Name of the affected directory path.
     */
    protected string $invalidPath;

    /**
     * Set the affected directory path.
     */
    public function setInvalidPath(string $path): self
    {
        $this->invalidPath = $path;

        $this->message = "Error creating directory [{$path}]. Please check write permissions.";

        return $this;
    }

    /**
     * Get the affected directory path.
     */
    public function getInvalidPath(): string
    {
        return $this->invalidPath;
    }
}
